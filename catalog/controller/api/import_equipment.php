<?php
class ControllerApiImportEquipment extends Controller {
    public function import() {
        $this->load->language('api/transfer');
        
        $json = array();
        $log = new Log('import_equipment.log');
        
        $file = 'work/equipment.xml';
        
        $log->write('Початок виконання import_equipment');
        $log->write('Шлях до файлу: ' . $file);
        
        try {
            $result = $this->updateProductEquipment();
            $json['success'] = $this->language->get('text_success');
            $json['log'] = $result;
            $log->write('Успішне виконання updateProductEquipment');
        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
            $log->write('Критична помилка: ' . $e->getMessage());
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    private function updateProductEquipment() {
        $file = 'work/equipment.xml';
        $log = array();
        $stats = array(
            'processed' => 0,
            'added' => 0,
            'deleted' => 0,
            'not_found' => 0,
            'invalid_model' => 0,
            'duplicates_removed' => 0
        );
        
        if (!file_exists($file)) {
            throw new Exception("Файл не знайдено за шляхом: " . $file);
        }
        
        if (!is_readable($file)) {
            throw new Exception("Файл існує, але не може бути прочитаний: " . $file);
        }
        
        $xmlContent = file_get_contents($file);
        if ($xmlContent === false) {
            throw new Exception("Не вдалося прочитати вміст файлу");
        }
        
        $feed = simplexml_load_string($xmlContent);
        if ($feed === false) {
            throw new Exception("Не вдалося розпарсити XML");
        }
        
        if (empty($feed->part)) {
            return "Немає даних у файлі!";
        }
        
        $this->db->query("START TRANSACTION");
        
        try {
            // Групуємо всі model_id за SKU та видаляємо дублікати
            $skuModels = array();
            foreach ($feed->part as $part) {
                $sku = (string)$part->sku;
                $model_id = (int)$part->model_id;
                
                if (!isset($skuModels[$sku])) {
                    $skuModels[$sku] = array();
                }
                
                if (!in_array($model_id, $skuModels[$sku])) {
                    $skuModels[$sku][] = $model_id;
                } else {
                    $stats['duplicates_removed']++;
                }
            }
            
            // Обробляємо кожен унікальний SKU
            foreach ($skuModels as $sku => $modelIds) {
                // Знаходимо товар за SKU
                $product_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "'");
                
                if ($product_query->num_rows) {
                    foreach ($product_query->rows as $product) {
                        $product_id = $product['product_id'];
                        $stats['processed']++;
                        
                        // Видаляємо всі старі зв'язки для цього товару
                        $delete_query = $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_equipment WHERE product_id = '" . (int)$product_id . "'");
                        $stats['deleted'] += $this->db->countAffected();
                        
                        // Додаємо нові унікальні зв'язки
                        foreach ($modelIds as $model_id) {
                            // Перевіряємо чи існує така модель
                            $model_query = $this->db->query("SELECT model_id FROM " . DB_PREFIX . "equipment_model WHERE model_id = '" . (int)$model_id . "'");
                            
                            if ($model_query->num_rows) {
                                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_equipment SET product_id = '" . (int)$product_id . "', model_id = '" . (int)$model_id . "'");
                                $log[] = "Додано зв'язок: товар ID " . $product_id . " з моделлю ID " . $model_id;
                                $stats['added']++;
                            } else {
                                $log[] = "Не знайдено модель з ID: " . $model_id;
                                $stats['invalid_model']++;
                            }
                        }
                    }
                } else {
                    $log[] = "Не знайдено товар з SKU: " . $sku;
                    $stats['not_found']++;
                }
            }
            
            $this->db->query("COMMIT");
            @unlink($file);
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
        
        $log[] = "Статистика імпорту:";
        $log[] = "Всього оброблено товарів: " . $stats['processed'];
        $log[] = "Видалено старих зв'язків: " . $stats['deleted'];
        $log[] = "Додано нових зв'язків: " . $stats['added'];
        $log[] = "Видалено дублікатів: " . $stats['duplicates_removed'];
        $log[] = "Не знайдено товарів: " . $stats['not_found'];
        $log[] = "Невірні ID моделей: " . $stats['invalid_model'];
        
        return $log;
    }
}