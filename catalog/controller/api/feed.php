<?php
class ControllerApiFeed extends Controller {
    public function index() {
        // Set headers
        header('Content-Type: application/xml; charset=utf-8');
        
        // Load models
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('tool/image');
        
        // Start XML document
        $output  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $output .= '<shop>' . "\n";
        
        // Shop info
        $output .= '<name>' . $this->config->get('config_name') . '</name>' . "\n";
        $output .= '<url>' . HTTPS_SERVER . '</url>' . "\n";
        
        // Currencies
        $output .= '<currencies>' . "\n";
        $output .= '<currency id="UAH" rate="1"/>' . "\n";
        $output .= '</currencies>' . "\n";
        
        // Presence statuses
        $output .= '<presences>' . "\n";
        $output .= '<presence>В наявності</presence>' . "\n";
        $output .= '<presence>Під замовлення</presence>' . "\n";
        $output .= '<presence>Немає в наявності</presence>' . "\n";
        $output .= '</presences>' . "\n";
        
        // Categories
        $output .= '<categories>' . "\n";
        
        // Get all categories directly from database
        $query = $this->db->query("
            SELECT c.category_id, c.parent_id, cd.name 
            FROM " . DB_PREFIX . "category c 
            LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
            WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "'
            AND c.status = '1'
            ORDER BY c.sort_order, cd.name ASC
        ");
        
        foreach ($query->rows as $category) {
            $output .= '<category id="' . $category['category_id'] . '"';
            if ($category['parent_id']) {
                $output .= ' parentId="' . $category['parent_id'] . '"';
            }
            $output .= ' url="' . $this->url->link('product/category', 'path=' . $category['category_id']) . '"';
            $output .= '>' . $category['name'] . '</category>' . "\n";
        }
        
        $output .= '</categories>' . "\n";
        
        // Products
        $output .= '<offers>' . "\n";
        
        $products = $this->model_catalog_product->getProducts([
            'start' => 0,
            'limit' => 10000 // Adjust based on your needs
        ]);
        
        foreach ($products as $product) {
            $output .= '<offer id="' . $product['product_id'] . '" available="' . ($product['quantity'] > 0 ? 'true' : 'false') . '">' . "\n";
            
            // Basic product info
            $output .= '<name><![CDATA[' . $product['name'] . ']]></name>' . "\n";
            $output .= '<url>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</url>' . "\n";
            
            // Image - only add if exists
            if ($product['image'] && file_exists(DIR_IMAGE . $product['image'])) {
                $output .= '<picture>' . $this->model_tool_image->resize($product['image'], 600, 600) . '</picture>' . "\n";
            }
            
            // Get main category
            $main_category = $this->getMainCategory($product['product_id']);
            if ($main_category) {
                $output .= '<categoryId>' . $main_category . '</categoryId>' . "\n";
            }
            
            // Price and special price
            $output .= '<currencyId>UAH</currencyId>' . "\n";
            
            // Get special price from database
            $special = $this->getSpecialPrice($product['product_id']);
            
            if ($special) {
                $output .= '<oldprice>' . $this->currency->format($product['price'], 'UAH', '', false) . '</oldprice>' . "\n";
                $output .= '<price>' . $this->currency->format($special['price'], 'UAH', '', false) . '</price>' . "\n";
            } else {
                $output .= '<price>' . $this->currency->format($product['price'], 'UAH', '', false) . '</price>' . "\n";
            }
            
            // Manufacturer
            if ($product['manufacturer']) {
                $output .= '<vendor>' . $product['manufacturer'] . '</vendor>' . "\n";
            }
            
            // Model/SKU
            if ($product['model']) {
                $output .= '<vendorCode>' . $product['model'] . '</vendorCode>' . "\n";
            }
            
            // Presence
            $output .= '<presence>' . ($product['quantity'] > 0 ? 'В наявності' : 'Немає в наявності') . '</presence>' . "\n";
            
            // Description
            if ($product['description']) {
                $output .= '<description><![CDATA[' . strip_tags($product['description']) . ']]></description>' . "\n";
            }
            
            $output .= '</offer>' . "\n";
        }
        
        $output .= '</offers>' . "\n";
        $output .= '</shop>';
        
        // Save file
        $file = '/home/ilweb/nawiteh.ua/www/work/products.xml';
        file_put_contents($file, $output);
        
        echo $output;
    }
    
    // Get main category for product
    private function getMainCategory($product_id) {
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' ORDER BY main_category DESC LIMIT 1");
        
        return $query->num_rows ? $query->row['category_id'] : 0;
    }
    
    // Get special price
    private function getSpecialPrice($product_id) {
        $query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_special 
            WHERE product_id = '" . (int)$product_id . "' 
            ORDER BY priority ASC, price ASC LIMIT 1");
            
        return $query->num_rows ? $query->row : false;
    }
}