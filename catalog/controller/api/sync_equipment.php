<?php
class ControllerApiSyncEquipment extends Controller {
    private $batch_size = 1000; // Розмір батча для обробки

    public function index() {
        $json = array();
        
        try {
            $result = $this->syncEquipment();
            $json['success'] = true;
            $json['data'] = $result;
        } catch (Exception $e) {
            $json['success'] = false;
            $json['error'] = $e->getMessage();
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    private function syncEquipment() {
        $stats = array(
            'processed' => 0,
            'duplicated' => 0,
            'no_equipment' => 0,
            'updated' => 0
        );
        
        $this->db->query("START TRANSACTION");
        
        try {
            // 1. Створюємо тимчасову таблицю для зберігання проміжних результатів
            $this->createTemporaryTables();
            
            // 2. Знаходимо всі унікальні SKU, які потребують оновлення
            $this->findProductsForUpdate();
            
            // 3. Обробляємо дані батчами
            $offset = 0;
            while (true) {
                $products = $this->getProductsBatch($offset);
                if (empty($products)) {
                    break;
                }
                
                $this->processProductsBatch($products, $stats);
                $offset += $this->batch_size;
            }
            
            // 4. Видаляємо тимчасові таблиці
            $this->dropTemporaryTables();
            
            $this->db->query("COMMIT");
            return $stats;
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            $this->dropTemporaryTables();
            throw $e;
        }
    }

    private function createTemporaryTables() {
        // Таблиця для товарів, що потребують оновлення
        $this->db->query("
            CREATE TEMPORARY TABLE IF NOT EXISTS temp_products_to_update (
                product_id int NOT NULL,
                sku varchar(64) NOT NULL,
                KEY (sku),
                KEY (product_id)
            ) ENGINE=MEMORY
        ");
        
        // Таблиця для нових зв'язків
        $this->db->query("
            CREATE TEMPORARY TABLE IF NOT EXISTS temp_equipment_links (
                product_id int NOT NULL,
                model_id int NOT NULL,
                UNIQUE KEY (product_id, model_id)
            ) ENGINE=MEMORY
        ");
    }

    private function findProductsForUpdate() {
        // Знаходимо товари без обладнання та зберігаємо їх у тимчасову таблицю
        $this->db->query("
            INSERT INTO temp_products_to_update
            SELECT p.product_id, p.sku
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_to_equipment pe ON p.product_id = pe.product_id
            WHERE pe.product_id IS NULL 
            AND p.sku != ''
        ");
    }

    private function getProductsBatch($offset) {
        $query = $this->db->query("
            SELECT product_id, sku
            FROM temp_products_to_update
            LIMIT " . (int)$offset . ", " . (int)$this->batch_size
        );
        
        return $query->rows;
    }

    private function processProductsBatch($products, &$stats) {
        if (empty($products)) {
            return;
        }

        // Збираємо всі SKU для пакетного пошуку
        $skus = array_column($products, 'sku');
        $sku_list = "'" . implode("','", array_map(array($this->db, 'escape'), $skus)) . "'";
        
        // Знаходимо всі існуючі зв'язки для цих SKU одним запитом
        $existing_equipment = $this->getExistingEquipment($sku_list);
        
        // Готуємо дані для масового вставлення
        $insert_values = array();
        foreach ($products as $product) {
            $stats['processed']++;
            
            if (isset($existing_equipment[$product['sku']])) {
                $stats['duplicated']++;
                foreach ($existing_equipment[$product['sku']] as $model_id) {
                    $insert_values[] = "(" . (int)$product['product_id'] . ", " . (int)$model_id . ")";
                    $stats['updated']++;
                }
            } else {
                $stats['no_equipment']++;
            }
        }
        
        // Масове вставлення нових зв'язків
        if (!empty($insert_values)) {
            $this->db->query("
                INSERT IGNORE INTO " . DB_PREFIX . "product_to_equipment 
                (product_id, model_id) VALUES " . implode(',', $insert_values)
            );
        }
    }

    private function getExistingEquipment($sku_list) {
        $query = $this->db->query("
            SELECT p.sku, pe.model_id
            FROM " . DB_PREFIX . "product p
            INNER JOIN " . DB_PREFIX . "product_to_equipment pe ON p.product_id = pe.product_id
            WHERE p.sku IN (" . $sku_list . ")
            GROUP BY p.sku, pe.model_id
        ");
        
        $equipment = array();
        foreach ($query->rows as $row) {
            if (!isset($equipment[$row['sku']])) {
                $equipment[$row['sku']] = array();
            }
            $equipment[$row['sku']][] = $row['model_id'];
        }
        
        return $equipment;
    }

    private function dropTemporaryTables() {
        $this->db->query("DROP TEMPORARY TABLE IF EXISTS temp_products_to_update");
        $this->db->query("DROP TEMPORARY TABLE IF EXISTS temp_equipment_links");
    }
}