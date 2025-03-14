<?php
class ControllerApiExportNoEquipment extends Controller {
    private $error = array();
    
    const LANGUAGE_ID = 3;
    const CSV_ENCODING = 'UTF-8';
    const EXPORT_DIR = 'work/';
    const FILENAME = 'export_no_equipment.csv';
    
    public function index() {
        try {
            if ($this->request->server['REQUEST_METHOD'] == 'GET' || $this->request->server['REQUEST_METHOD'] == 'POST') {
                $json = array();
                
                if (!is_dir(self::EXPORT_DIR)) {
                    mkdir(self::EXPORT_DIR, 0755, true);
                }
                
                $filename = $this->exportProducts();
                
                $json['success'] = true;
                $json['file'] = array(
                    'filename' => $filename,
                    'url' => HTTPS_SERVER . 'work/' . $filename
                );
                
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode($json));
            } else {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode(['error' => 'Invalid request method']));
            }
        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => $e->getMessage()]));
        }
    }
    
    protected function exportProducts() {
        $filename = self::EXPORT_DIR . self::FILENAME;
        
        $products = $this->getProductsQuery()
            ->rows;
            
        $headers = array(
            '1C ID',
            'SKU',
            'Name'
        );
        
        $this->createCsvFile($filename, $headers, $products);
        
        return self::FILENAME;
    }
    
    protected function getProductsQuery() {
        return $this->db->query("
            SELECT 
                p.id_1c,
                p.sku,
                COALESCE(wpn.alternative_name, pd.name) as name
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "wayforpay_product_names wpn 
                ON (p.product_id = wpn.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd 
                ON (p.product_id = pd.product_id AND pd.language_id = " . self::LANGUAGE_ID . ")
            WHERE p.product_id NOT IN (
                SELECT DISTINCT product_id 
                FROM " . DB_PREFIX . "product_to_equipment
            )
            ORDER BY p.product_id ASC"
        );
    }
    
    protected function createCsvFile($filename, $headers, $data) {
        $handle = fopen($filename, 'w');
        
        if ($handle === false) {
            throw new Exception("Cannot create file: " . $filename);
        }
        
        // Add BOM for UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($handle, $headers);
        
        // Write data
        foreach ($data as $row) {
            $csvRow = array(
                $row['id_1c'],
                $row['sku'],
                $row['name']
            );
            
            fputcsv($handle, $csvRow);
        }
        
        fclose($handle);
    }
}