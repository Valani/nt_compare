<?php
class ControllerApiImportExtraTabs extends Controller {
    public function import_extra_tabs() {
        $this->load->language('api/transfer');

        $json = array();
        $log = new Log('import_extra_tabs.log');

        $file = '/home/ilweb/nawiteh.ua/www/work/output_extra.xml';
        
        $log->write('Початок виконання import_extra_tabs');
        $log->write('Шлях до файлу: ' . $file);

        try {
            $result = $this->updateProductExtraTabs(2);  // Використовуємо extra_tab_id = 1
            $json['success'] = $this->language->get('text_success_extra_tabs');
            $json['log'] = $result;
            $log->write('Успішне виконання updateProductExtraTabs');
        } catch (Exception $e) {
            $log->write('Попередження: ' . $e->getMessage() . ', але імпорт продовжується');
            $json['warning'] = $e->getMessage();
            
            // Спробуємо виконати імпорт навіть якщо виникла помилка з файлом
            try {
                $result = $this->updateProductExtraTabs(1);
                $json['success'] = $this->language->get('text_success_extra_tabs');
                $json['log'] = $result;
                $log->write('Успішне виконання updateProductExtraTabs після попередження');
            } catch (Exception $e2) {
                $json['error'] = $e2->getMessage();
                $log->write('Критична помилка: ' . $e2->getMessage());
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function updateProductExtraTabs($extra_tab_id = 2, $language_id = 3) {
        $file = '/home/ilweb/nawiteh.ua/www/work/output_extra.xml';
        $log = array();
        $stats = array(
            'processed' => 0,
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
            'not_found' => 0
        );

        if (!file_exists($file)) {
            $log[] = "Попередження: Файл не знайдено за шляхом: " . $file;
            $log[] = "Спроба продовжити імпорт...";
        }

        // Перевірка на можливість читання файлу
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

        if (empty($feed->item)) {
            return "Немає товарів у файлі!";
        }

        $this->db->query("START TRANSACTION");

        try {
            foreach ($feed->item as $item) {
                $sku = (string)$item->product_article;
                $description = (string)$item->description;

                $product_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku = '" . $this->db->escape($sku) . "'");

                if ($product_query->num_rows) {
                    foreach ($product_query->rows as $product) {
                        $stats['processed']++;
                        $product_id = $product['product_id'];

                        $existing_tab = $this->db->query("SELECT * FROM " . DB_PREFIX . "oct_product_extra_tabs WHERE product_id = '" . (int)$product_id . "' AND extra_tab_id = '" . (int)$extra_tab_id . "' AND language_id = '" . (int)$language_id . "'")->row;

                        if (!$existing_tab) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "oct_product_extra_tabs SET product_id = '" . (int)$product_id . "', extra_tab_id = '" . (int)$extra_tab_id . "', language_id = '" . (int)$language_id . "', text = '" . $this->db->escape($description) . "'");
                            $log[] = "Додано новий extra tab для товару з ID " . $product_id;
                            $stats['added']++;
                        } elseif (empty($existing_tab['text'])) {
                            $this->db->query("UPDATE " . DB_PREFIX . "oct_product_extra_tabs SET text = '" . $this->db->escape($description) . "' WHERE product_id = '" . (int)$product_id . "' AND extra_tab_id = '" . (int)$extra_tab_id . "' AND language_id = '" . (int)$language_id . "'");
                            $log[] = "Оновлено пустий extra tab для товару з ID " . $product_id;
                            $stats['updated']++;
                        } else {
                            $log[] = "Пропущено оновлення для товару з ID " . $product_id . " (extra tab вже містить інформацію)";
                            $stats['skipped']++;
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
        $log[] = "Додано нових extra tabs: " . $stats['added'];
        $log[] = "Оновлено існуючих extra tabs: " . $stats['updated'];
        $log[] = "Пропущено (вже мають інформацію): " . $stats['skipped'];
        $log[] = "Не знайдено товарів: " . $stats['not_found'];

        return $log;
    }
}