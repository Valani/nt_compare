<?php
class ControllerApiExportCsv extends Controller {
    private $error = array();
    
    const LANGUAGE_ID = 3;
    const CSV_ENCODING = 'UTF-8';
    const EXPORT_DIR = 'work/';
    const ALL_PRODUCTS_FILENAME = 'export_csv_all.csv';
    const NO_IMAGE_PRODUCTS_FILENAME = 'export_csv_no_image.csv';
    
    public function index() {
        try {
            if ($this->request->server['REQUEST_METHOD'] == 'GET' || $this->request->server['REQUEST_METHOD'] == 'POST') {
                $json = array();
                
                if (!is_dir(self::EXPORT_DIR)) {
                    mkdir(self::EXPORT_DIR, 0755, true);
                }
                
                $all_products_file = $this->exportAllProducts();
                $no_image_products_file = $this->exportProductsWithoutImages();
                
                $json['success'] = true;
                $json['files'] = array(
                    'all_products' => array(
                        'filename' => $all_products_file,
                        'url' => HTTPS_SERVER . 'work/' . $all_products_file
                    ),
                    'no_image_products' => array(
                        'filename' => $no_image_products_file,
                        'url' => HTTPS_SERVER . 'work/' . $no_image_products_file
                    )
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
    
    protected function exportAllProducts() {
        $filename = self::EXPORT_DIR . self::ALL_PRODUCTS_FILENAME;
        
        $products = $this->getProductsQuery()
            ->rows;
            
        $headers = array(
            'Product ID',
            '1C ID',
            'Main category',
            'SKU',
            'SKU 1C',
            'Name',
            'Quantity'
        );
        
        $this->createCsvFile($filename, $headers, $products, true);
        
        return self::ALL_PRODUCTS_FILENAME;
    }
    
    protected function exportProductsWithoutImages() {
        $filename = self::EXPORT_DIR . self::NO_IMAGE_PRODUCTS_FILENAME;
        
        $products = $this->getProductsWithoutImagesQuery()
            ->rows;
            
        $headers = array(
            'Product ID',
            '1C ID',
            'Main category',
            'SKU',
            'SKU 1C',
            'Name',
            'Quantity'
        );
        
        $this->createCsvFile($filename, $headers, $products, true);
        
        return self::NO_IMAGE_PRODUCTS_FILENAME;
    }
    
    protected function getProductsQuery() {
        return $this->db->query("
            SELECT 
                p.product_id,
                p.id_1c,
                COALESCE((
                    SELECT cd.name 
                    FROM " . DB_PREFIX . "product_to_category ptc 
                    LEFT JOIN " . DB_PREFIX . "category_description cd 
                        ON (ptc.category_id = cd.category_id AND cd.language_id = " . self::LANGUAGE_ID . ")
                    WHERE ptc.product_id = p.product_id 
                    ORDER BY ptc.category_id ASC 
                    LIMIT 1
                ), 'Uncategorized') as main_category,
                p.sku,
                COALESCE(wpn.article, '') as sku_1c,
                COALESCE(wpn.alternative_name, pd.name) as name,
                p.quantity
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "wayforpay_product_names wpn 
                ON (p.product_id = wpn.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd 
                ON (p.product_id = pd.product_id AND pd.language_id = " . self::LANGUAGE_ID . ")
            ORDER BY p.product_id ASC"
        );
    }
    
    protected function getProductsWithoutImagesQuery() {
        return $this->db->query("
            SELECT 
                p.product_id,
                p.id_1c,
                COALESCE((
                    SELECT cd.name 
                    FROM " . DB_PREFIX . "product_to_category ptc 
                    LEFT JOIN " . DB_PREFIX . "category_description cd 
                        ON (ptc.category_id = cd.category_id AND cd.language_id = " . self::LANGUAGE_ID . ")
                    WHERE ptc.product_id = p.product_id 
                    ORDER BY ptc.category_id ASC 
                    LIMIT 1
                ), 'Uncategorized') as main_category,
                p.sku,
                COALESCE(wpn.article, '') as sku_1c,
                COALESCE(wpn.alternative_name, pd.name) as name,
                p.quantity
            FROM " . DB_PREFIX . "product p
            LEFT JOIN " . DB_PREFIX . "wayforpay_product_names wpn 
                ON (p.product_id = wpn.product_id)
            LEFT JOIN " . DB_PREFIX . "product_description pd 
                ON (p.product_id = pd.product_id AND pd.language_id = " . self::LANGUAGE_ID . ")
            WHERE p.image = '' OR p.image IS NULL
            ORDER BY p.product_id ASC"
        );
    }
    
    protected function createCsvFile($filename, $headers, $data, $includeNames = true) {
        $handle = fopen($filename, 'w');
        
        if ($handle === false) {
            throw new Exception("Cannot create file: " . $filename);
        }
        
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($handle, $headers);
        
        foreach ($data as $row) {
            $csvRow = array(
                $row['product_id'],
                $row['id_1c'],
                $row['main_category'],
                $row['sku'],
                $row['sku_1c']
            );
            
            if ($includeNames) {
                $csvRow[] = $row['name'];
            }
            
            $csvRow[] = $row['quantity'];
            
            fputcsv($handle, $csvRow);
        }
        
        fclose($handle);
    }
}