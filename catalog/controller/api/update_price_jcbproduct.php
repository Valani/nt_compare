<?php
class ControllerApiUpdatePriceJcbproduct extends Controller {
    private $markup = 1.2; // націнка 20%
    
    public function __construct($registry) {
        parent::__construct($registry);
        set_time_limit(3600); // 1 година
        ini_set('memory_limit', '512M');
    }

    public function index() {
        $this->load->model('catalog/product');
        
        // Перевірка наявності файлу
        $xml_file = DIR_APPLICATION . '../work/jcbproduct_price.xml';
        if (!file_exists($xml_file)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => 'Price XML file not found']));
            return;
        }

        try {
            // Отримуємо курс GBP/UAH з НБУ
            $exchange_rate = $this->getNbuRate();
            if (!$exchange_rate) {
                throw new Exception('Could not get NBU exchange rate');
            }

            // Завантажуємо та парсимо XML
            $xml = simplexml_load_file($xml_file);
            
            $updated = 0;
            $errors = [];
            $batch_updates = [];
            
            foreach ($xml->part as $part) {
                try {
                    $sku = trim((string)$part->element__part_number);
                    $price_gbp = (float)str_replace(',', '.', (string)$part->gbp______);
                    
                    // Розрахунок ціни в гривнях з націнкою
                    $price_uah = round($price_gbp * $exchange_rate * $this->markup, 2);
                    
                    // Додаємо в пакетне оновлення
                    $batch_updates[] = sprintf(
                        "WHEN sku = '%s' THEN %.2f",
                        $this->db->escape($sku),
                        $price_uah
                    );
                    
                    $updated++;
                    
                    // Оновлюємо базу кожні 1000 записів
                    if (count($batch_updates) >= 1000) {
                        $this->executeBatchUpdate($batch_updates);
                        $batch_updates = [];
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error updating SKU {$sku}: " . $e->getMessage();
                }
            }
            
            // Оновлюємо залишок записів
            if (!empty($batch_updates)) {
                $this->executeBatchUpdate($batch_updates);
            }

            // Оновлюємо кеш через API
            $cache_result = $this->refreshCacheViaApi();
            
            $response = [
                'success' => true,
                'update' => [
                    'updated' => $updated,
                    'exchange_rate' => $exchange_rate,
                    'errors' => $errors
                ],
                'cache' => $cache_result
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }
    
    protected function getNbuRate() {
        try {
            $json = file_get_contents('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?json&valcode=GBP');
            $data = json_decode($json, true);
            
            if (!empty($data[0]['rate'])) {
                return (float)$data[0]['rate'];
            }
            
            return false;
        } catch (Exception $e) {
            $this->log->write('NBU Rate Error: ' . $e->getMessage());
            return false;
        }
    }
    
    protected function executeBatchUpdate($updates) {
        if (empty($updates)) {
            return;
        }
        
        $sql = "UPDATE `" . DB_PREFIX . "simple_products` 
                SET price = CASE " . implode(' ', $updates) . " END 
                WHERE sku IN ('" . implode("','", array_map(function($update) {
                    return substr($update, strpos($update, "'") + 1, strpos($update, "'", strpos($update, "'") + 1) - strpos($update, "'") - 1);
                }, $updates)) . "')";
                
        $this->db->query($sql);
    }
    
    protected function refreshCacheViaApi() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://nawiteh.ua/index.php?route=api/jcbparts_refresh');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            return json_decode($result, true);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}