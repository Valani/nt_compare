<?php
class ModelOCTemplatesModuleOctLiveSearch extends Model {
    public function doSearch($key) {
        $search_data = $this->config->get('theme_oct_showcase_live_search_data');

        // Спочатку шукаємо в звичайних продуктах
        $regular_products = $this->searchRegularProducts($key, $search_data);
        
        // Якщо звичайні продукти знайдені, повертаємо їх
        if (!empty($regular_products)) {
            return $regular_products;
        }
        
        // Якщо звичайні продукти не знайдені, шукаємо в JCB продуктах
        return $this->searchJCBProducts($key);
    }
    
    private function searchRegularProducts($key, $search_data) {
        $sql = "
            SELECT
                p.product_id,
                IF(pd.name = '" . $this->db->escape($key) . "', 3, 0) +
                IF(pd.name LIKE '" . $this->db->escape($key) . "%', 2, 0) +
                IF(pd.name LIKE '%" . $this->db->escape($key) . "%', 1, 0) AS relevance
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "product_description pd
                ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_store p2s
                ON (p.product_id = p2s.product_id)
            WHERE
                p.status = '1'
                AND p.date_available <= NOW()
                AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
        ";

        if (isset($key) && !empty($key)) {
            $sql .= " AND (";

            $implode = [];

            $words = explode(' ', trim(preg_replace('/\s+/', ' ', $key)));

            foreach ($words as $word) {
                $implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
            }

            if ($implode) {
                $sql .= " " . implode(" AND ", $implode) . "";
            }

            if (isset($search_data['description']) && $search_data['description']) {
                $sql .= " OR pd.description LIKE '%" . $this->db->escape($key) . "%'";
            }

            $sql .= " OR LCASE(p.sku) LIKE '%" . $this->db->escape(utf8_strtolower($key)) . "%'";
            $sql .= " OR LCASE(p.model) LIKE '%" . $this->db->escape(utf8_strtolower($key)) . "%'";
            $sql .= ")";
        }

        $sql .= "
            GROUP BY
                p.product_id
            ORDER BY
                relevance DESC,
                p.sort_order ASC,
                LOWER(pd.name) ASC,
                LOWER(p.model) ASC
            LIMIT 15
        ";

        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    private function searchJCBProducts($key) {
        $sql = "
            SELECT 
                CONCAT('jcb_', id) as product_id,
                IF(name = '" . $this->db->escape($key) . "', 3, 0) +
                IF(name LIKE '" . $this->db->escape($key) . "%', 2, 0) +
                IF(name LIKE '%" . $this->db->escape($key) . "%', 1, 0) AS relevance
            FROM " . DB_PREFIX . "simple_products 
            WHERE (
                LOWER(name) LIKE '%" . $this->db->escape(utf8_strtolower($key)) . "%'
                OR LOWER(sku) LIKE '%" . $this->db->escape(utf8_strtolower($key)) . "%'
            )
            ORDER BY 
                relevance DESC,
                name ASC
            LIMIT 15
        ";
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}