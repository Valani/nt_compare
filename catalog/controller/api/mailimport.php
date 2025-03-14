<?php
const EXPORT_DIR = 'work/';

class ControllerApiMailimport extends Controller {
    public function index() {
        // Перевіряємо чи існує файл
        $file_path = EXPORT_DIR . 'mail.txt';
        if (!file_exists($file_path)) {
            $this->response->setOutput(json_encode(['error' => 'File not found']));
            return;
        }

        // Завантажуємо файл та отримуємо ID товарів
        $product_ids = array();
        $file_content = file_get_contents($file_path);
        $lines = explode("\n", $file_content);
        foreach ($lines as $line) {
            $id_1c = trim($line);
            if (!empty($id_1c)) {
                // Отримуємо product_id по id_1c
                $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE id_1c = '" . $this->db->escape($id_1c) . "'");
                if ($query->num_rows) {
                    $product_ids[] = $query->row['product_id'];
                }
            }
        }

        // 1. Видаляємо категорію 622 для всіх товарів
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE category_id = 622");

        // 2. Додаємо товари з файлу до категорії 622
        foreach ($product_ids as $product_id) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = 622");
        }

        // Очищуємо кеш категорій
        $this->cache->delete('category');

        // Повертаємо результат
        $this->response->setOutput(json_encode([
            'success' => true,
            'processed_products' => count($product_ids),
            'message' => 'Products successfully updated'
        ]));
    }

    // Допоміжна функція для перевірки авторизації (якщо потрібно)
    private function validateAccess() {
        if (!isset($this->session->data['api_id'])) {
            return false;
        }
        return true;
    }
}