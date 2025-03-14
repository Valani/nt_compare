<?php
class ControllerApiWayforpayName extends Controller {
    public function index() {
        $json = array();
        
        if ($this->request->server['REQUEST_METHOD'] == 'GET') {
            $this->load->model('catalog/product');
            
            $file = '/home/ilweb/nawiteh.ua/quantities/ZalyshokXML.xml';
            
            if (!file_exists($file)) {
                $json['error'] = 'XML file not found';
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $feed = simplexml_load_string(file_get_contents($file));
            
            if (!$feed) {
                $json['error'] = 'Failed to load XML';
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
                return;
            }
            
            $total = 0;
            $updated = 0;
            
            foreach ($feed->DECLARHEAD->products->product as $product) {
                $total++;
                
                $code_1c = strval($product['code']);
                $productname = strval($product['productname']);
                $article = strval($product['article']);
                
                // Get all products with the same id_1c
                $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE id_1c = '" . $this->db->escape($code_1c) . "'");
                
                if ($query->num_rows) {
                    foreach ($query->rows as $product_row) {
                        $product_id = $product_row['product_id'];
                        
                        $existing_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "wayforpay_product_names WHERE product_id = " . (int)$product_id);
                        
                        if ($existing_query->num_rows) {
                            $this->db->query("UPDATE " . DB_PREFIX . "wayforpay_product_names 
                                            SET alternative_name = '" . $this->db->escape($productname) . "',
                                                article = '" . $this->db->escape($article) . "'
                                            WHERE product_id = " . (int)$product_id);
                        } else {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "wayforpay_product_names 
                                            SET product_id = " . (int)$product_id . ", 
                                                article = '" . $this->db->escape($article) . "',
                                                alternative_name = '" . $this->db->escape($productname) . "'");
                        }
                        
                        $updated++;
                    }
                }
            }
            
            // Process manually created products that might not have wayforpay records
            $manual_query = $this->db->query("
                SELECT p.product_id, p.id_1c 
                FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "wayforpay_product_names w ON p.product_id = w.product_id 
                WHERE w.product_id IS NULL AND p.id_1c != ''
            ");
            
            foreach ($manual_query->rows as $manual_product) {
                // Find a wayforpay record for another product with the same id_1c
                $reference_query = $this->db->query("
                    SELECT w.* 
                    FROM " . DB_PREFIX . "product p 
                    JOIN " . DB_PREFIX . "wayforpay_product_names w ON p.product_id = w.product_id 
                    WHERE p.id_1c = '" . $this->db->escape($manual_product['id_1c']) . "'
                    LIMIT 1
                ");
                
                if ($reference_query->num_rows) {
                    // Copy the wayforpay record for the manually created product
                    $this->db->query("INSERT INTO " . DB_PREFIX . "wayforpay_product_names 
                                    SET product_id = " . (int)$manual_product['product_id'] . ",
                                        article = '" . $this->db->escape($reference_query->row['article']) . "',
                                        alternative_name = '" . $this->db->escape($reference_query->row['alternative_name']) . "'");
                    $updated++;
                }
            }
            
            $json['success'] = true;
            $json['total_processed'] = $total;
            $json['total_updated'] = $updated;
            
            $log = date("Y-m-d H:i:s") . " - Completed. Processed: " . $total . ", Updated: " . $updated . "\n";
            file_put_contents(DIR_LOGS . 'alternative_names_import.log', $log, FILE_APPEND);
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function import_alternative_names() {
        $json = array();
        
        if ($this->request->server['REQUEST_METHOD'] == 'GET') {
            $this->index();
        } else {
            $json['error'] = 'Invalid Method';
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}