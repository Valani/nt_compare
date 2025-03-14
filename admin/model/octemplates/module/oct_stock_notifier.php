<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctStockNotifier extends Model {

    public function checkSubscriber($subscription_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "oct_stock_notifier` WHERE subscription_id = '" . (int)$subscription_id . "'");

        return $query->row;
    }

    public function deleteSubscriber($subscription_id) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "oct_stock_notifier` WHERE subscription_id = '" . (int)$subscription_id . "'");
    }

    public function getSubscribers($data = array()) {
        $sql = "SELECT s.*, 
                COALESCE(pd.name, sp.name) as product_name,
                COALESCE(pd.product_id, sp.id) as final_product_id 
                FROM " . DB_PREFIX . "oct_stock_notifier s 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (s.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "simple_products sp ON (s.product_id = sp.id)
                WHERE 1";

        if (!empty($data['filter_email'])) {
            $sql .= " AND s.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND (COALESCE(pd.name, sp.name) LIKE '" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_phone'])) {
            $sql .= " AND s.phone LIKE '" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND s.status = '" . (int)$data['filter_status'] . "'";
        }

        $sql .= " ORDER BY s.subscribed_date DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalSubscribers($data = array()) {
        $sql = "SELECT COUNT(*) as total FROM " . DB_PREFIX . "oct_stock_notifier s 
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (s.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "simple_products sp ON (s.product_id = sp.id)
                WHERE 1";

        if (!empty($data['filter_email'])) {
            $sql .= " AND s.email LIKE '" . $this->db->escape($data['filter_email']) . "%'";
        }

        if (!empty($data['filter_product'])) {
            $sql .= " AND (COALESCE(pd.name, sp.name) LIKE '" . $this->db->escape($data['filter_product']) . "%')";
        }

        if (!empty($data['filter_phone'])) {
            $sql .= " AND s.phone LIKE '" . $this->db->escape($data['filter_phone']) . "%'";
        }

        if (isset($data['filter_status']) && $data['filter_status'] !== '') {
            $sql .= " AND s.status = '" . (int)$data['filter_status'] . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getSubscribersByProductId($product_id) {
        $sql = "SELECT sn.*,
                COALESCE(pd.name, sp.name) as product_name
                FROM " . DB_PREFIX . "oct_stock_notifier sn
                LEFT JOIN " . DB_PREFIX . "product_description pd ON (sn.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
                LEFT JOIN " . DB_PREFIX . "simple_products sp ON (sn.product_id = sp.id)
                WHERE sn.product_id = '" . (int)$product_id . "'
                AND sn.status = '0'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function updateSubscriptionStatus($subscription_id) {
        $query = $this->db->query("UPDATE " . DB_PREFIX . "oct_stock_notifier SET status = 1, notified_date = NOW() WHERE subscription_id = '" . (int)$subscription_id . "'");
    }

    public function getTotalCallArray($data = []) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "oct_stock_notifier`";
        
        if (isset($data['filter_processed']) && !is_null($data['filter_processed'])) {
            $sql .= " WHERE status = '". (int)$data['filter_processed'] ."'";
        }   
            
        $query = $this->db->query($sql);
        
        return $query->row['total'];
    }

    public function addRequest($data) {
        // Встановлюємо шаблонний email, якщо користувач не ввів свій
        $email = !empty($data['email']) ? $data['email'] : 'nawitehad@gmail.com';
        
        $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_stock_notifier` SET 
            product_id = '" . (int)$data['pid'] . "',
            customer_id = '" . (int)$data['customer_id'] . "',
            customer_name = '" . $this->db->escape($data['name']) . "',
            store_id = '" . (int)$this->config->get('config_store_id') . "',
            language_id = '" . (int)$this->config->get('config_language_id') . "',
            email = '" . $this->db->escape($email) . "',
            phone = '" . $this->db->escape($data['phone']) . "',
            subscribed_date = NOW(),
            status = '0'");
            
        return $this->db->getLastId();
    }

    public function isAlreadySubscribed($data) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "oct_stock_notifier` 
            WHERE product_id = '" . (int)$data['pid'] . "' 
            AND (phone = '" . $this->db->escape($data['phone']) . "' OR email = '" . $this->db->escape($data['email']) . "')
            AND status = '0'");
            
        return $query->row['total'] > 0;
    }

    public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "oct_stock_notifier (
                subscription_id INT(11) AUTO_INCREMENT,
                product_id INT(11) NOT NULL,
                customer_id INT(11),
                customer_name VARCHAR(255) DEFAULT NULL,
                store_id INT(11) NOT NULL DEFAULT 0,
                language_id INT(11),
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20), 
                subscribed_date DATETIME NOT NULL,
                notified_date DATETIME,
                status TINYINT(1) NOT NULL DEFAULT 0,
                
                PRIMARY KEY (subscription_id),
                INDEX idx_product_id (product_id),
                INDEX idx_customer_id (customer_id),
                INDEX idx_status (status)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "oct_stock_notifier`");
    }
}