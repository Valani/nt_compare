<?php
class ControllerApiProductStickers extends Controller {
    public function addStickersFromXml() {
        $this->load->language('api/product');
        
        $json = array();
        $log = new Log('add_stickers_from_xml.log');
        
        $file = '/home/ilweb/nawiteh.ua/work/stickers.xml';
        
        $log->write('Початок виконання addStickersFromXml');
        $log->write('Шлях до файлу: ' . $file);
        
        if (!file_exists($file) || !is_readable($file)) {
            $json['error'] = 'Файл не знайдено або не може бути прочитаний: ' . $file;
            $log->write('Помилка: ' . $json['error']);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $xmlContent = file_get_contents($file);
        if ($xmlContent === false) {
            $json['error'] = 'Не вдалося прочитати вміст файлу';
            $log->write('Помилка: ' . $json['error']);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            $json['error'] = 'Не вдалося розпарсити XML';
            $log->write('Помилка: ' . $json['error']);
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        $stats = array(
            'processed' => 0,
            'updated' => 0,
            'replaced' => 0,
            'skipped' => 0,
            'not_found' => 0,
            'attributes_updated' => 0
        );
        
        $this->db->query("START TRANSACTION");
        
        try {
            foreach ($xml->item as $item) {
                $id_1c = (string)$item->id_1c;
                
                $product_query = $this->db->query("SELECT product_id, oct_stickers FROM " . DB_PREFIX . "product WHERE id_1c = '" . $this->db->escape($id_1c) . "'");
                
                if ($product_query->num_rows) {
                    $stats['processed']++;
                    $product = $product_query->row;
                    $product_id = $product['product_id'];
                    $oct_stickers = $product['oct_stickers'];
                    
                    $updated = false;
                    $is_oem = false;
                    
                    if ($oct_stickers == '') {
                        $oct_stickers = array('customer_oem' => 'customer_oem');
                        $updated = true;
                        $is_oem = true;
                        $stats['updated']++;
                    } else {
                        $oct_stickers = unserialize($oct_stickers);
                        if (isset($oct_stickers['customer_original'])) {
                            unset($oct_stickers['customer_original']);
                            $oct_stickers['customer_oem'] = 'customer_oem';
                            $updated = true;
                            $is_oem = true;
                            $stats['replaced']++;
                        } elseif (!isset($oct_stickers['customer_oem'])) {
                            $oct_stickers['customer_oem'] = 'customer_oem';
                            $updated = true;
                            $is_oem = true;
                            $stats['updated']++;
                        } else {
                            $is_oem = true;
                            $stats['skipped']++;
                        }
                    }
                    
                    if ($updated) {
                        $this->db->query("UPDATE " . DB_PREFIX . "product SET oct_stickers = '" . $this->db->escape(serialize($oct_stickers)) . "' WHERE product_id = " . (int)$product_id);
                        $log->write('Оновлено стікери для товару з ID: ' . $product_id . ' (id_1c: ' . $id_1c . ')');
                    }
                    
                    if ($is_oem) {
                        // Оновлюємо або додаємо запис в oc_product_attribute
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute 
                                          (product_id, attribute_id, language_id, text) 
                                          VALUES (" . (int)$product_id . ", 17, 3, 'Так')
                                          ON DUPLICATE KEY UPDATE text = 'Так'");
                        $stats['attributes_updated']++;
                        $log->write('Оновлено атрибут OEM для товару з ID: ' . $product_id . ' (id_1c: ' . $id_1c . ')');
                    }
                } else {
                    $log->write('Не знайдено товар з id_1c: ' . $id_1c);
                    $stats['not_found']++;
                }
            }
            
            $this->db->query("COMMIT");
            
            // Оновлюємо фільтр OEM в OCFilter
            $this->updateOCFilterOEM();
            
            $json['success'] = 'Імпорт стікерів та оновлення атрибутів завершено';
            $json['stats'] = $stats;
            
            $log->write('Імпорт завершено. Оброблено: ' . $stats['processed'] . 
                        ', Оновлено: ' . $stats['updated'] . 
                        ', Замінено (original на OEM): ' . $stats['replaced'] . 
                        ', Пропущено: ' . $stats['skipped'] . 
                        ', Не знайдено: ' . $stats['not_found'] . 
                        ', Оновлено атрибутів: ' . $stats['attributes_updated']);
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $json['error'] = 'Сталася помилка під час імпорту: ' . $e->getMessage();
            $log->write('Критична помилка: ' . $e->getMessage());
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function updateOCFilterOEM() {
        $this->load->controller('extension/module/ocfilter/copy', [
            'copy_attribute' => 1, // Копіювати атрибути
            'copy_group_as_attribute' => 0, // Групи атрибутів як фільтри
            'copy_attribute_id_exclude' => 1, // Дані для копіювання
            'copy_attribute_group_id_exclude' => 1, // Дані для копіювання
            'copy_attribute_category_id_exclude' => 1, // Дані для копіювання
            'copy_filter' => 0, // Копіювати стандартні фільтри
            'copy_option' => 0, // Копіювати опції товарів
            'copy_option_in_stock' => 0, // Тільки в наявності
            'copy_type' => 'checkbox', // Тип скопійованих фільтрів
            'copy_dropdown' => 0, // Помістити в список, що випадає
            'copy_status' => 1, // Статус скопійованих фільтрів
            'copy_truncate' => 0, // Очистити існуючі фільтри OCFilter
            'copy_category' => 0, // Прив'язати фільтри до категорій
            'copy_cron_wget' => 0, // Команда для виклику по cron (планувальник)
            'copy_value_separator' => [], // 
            'copy_attribute_id' => ['1962', '1963', '1959', '20', '5', '8', '10', '9', '19', '38', '1961', '36', '13', '15', '1966', '1967', '11', '14', '1965', '31', '30', '12', '26', '1960', '37', '29', '21', '39', '41', '18', '42', '34', '24', '27', '40', '16', '1964', '22', '33', '35'], // 
            'copy_attribute_group_id' => [], // 
            'copy_attribute_category_id' => [], // 
          ]);
    }
}