<?php
class ControllerApiProducts extends Controller {
    private $cache_prefix = 'jcb_parts:';
    private $batch_size = 1000;
    private $max_execution_time = 3600; // 1 hour
    
    public function __construct($registry) {
        parent::__construct($registry);
        // Increase execution time limit
        set_time_limit($this->max_execution_time);
        // Increase memory limit if needed
        ini_set('memory_limit', '512M');
    }

    public function index() {
        $this->load->model('catalog/product');
        $this->load->model('catalog/jcbparts');
        
        // Check if XML file exists
        $xml_file = DIR_APPLICATION . '../work/jcb_products.xml';
        if (!file_exists($xml_file)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => 'XML file not found']));
            return;
        }

        try {
            // Clear existing products
            $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "simple_products`");
            
            // Load and parse XML
            $xml = simplexml_load_file($xml_file);
            
            $imported = 0;
            $errors = [];
            
            foreach ($xml->product as $product) {
                try {
                    $sku = $this->db->escape((string)$product->sku);
                    $name = $this->db->escape((string)$product->name);
                    $price = (float)$product->price;
                    
                    // Generate SEO URL
                    $seo_url = $this->generateSeoUrl($name);
                    
                    // Insert product using OpenCart's query method
                    $this->db->query("INSERT INTO `" . DB_PREFIX . "simple_products` 
                        SET sku = '" . $sku . "',
                            name = '" . $name . "',
                            price = '" . (float)$price . "',
                            seo_url = '" . $this->db->escape($seo_url) . "'");
                    
                    $imported++;
                    
                } catch (Exception $e) {
                    $errors[] = "Error importing SKU {$sku}: " . $e->getMessage();
                }
            }

            // After successful import, refresh the cache
            $cache_result = $this->refreshCache();
            
            $response = [
                'success' => true,
                'import' => [
                    'imported' => $imported,
                    'errors' => $errors
                ],
                'cache' => $cache_result
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }
    
    protected function refreshCache() {
        try {
            // 1. Clear existing cache
            $this->model_catalog_jcbparts->clearCache();
            $this->log->write('Cache cleared successfully');
            
            // 2. Cache total products count
            $total_products = $this->model_catalog_jcbparts->getTotalProducts();
            $this->log->write('Total products to cache: ' . $total_products);
            
            // 3. Calculate pagination parameters
            $pages_50 = ceil($total_products / 50);
            $batches = ceil($total_products / $this->batch_size);
            
            $start_time = microtime(true);
            $processed = 0;
            
            // 4. Cache data in batches
            for ($batch = 0; $batch < $batches; $batch++) {
                $offset = $batch * $this->batch_size;
                $batch_start_time = microtime(true);
                
                $products = $this->model_catalog_jcbparts->getProductsBatch($offset, $this->batch_size);
                $batch_count = count($products);
                
                if ($batch_count > 0) {
                    // Cache the batch itself
                    $batch_key = $this->cache_prefix . 'batch:' . $offset . ':' . $this->batch_size;
                    $this->model_catalog_jcbparts->setCache($batch_key, $products);
                    
                    // Split batch into frontend page sizes
                    for ($i = 0; $i < $batch_count; $i += 50) {
                        $page_products = array_slice($products, $i, 50);
                        $page_offset = $offset + $i;
                        $page_key = $this->cache_prefix . 'products:' . $page_offset . ':50';
                        $this->model_catalog_jcbparts->setCache($page_key, $page_products);
                    }
                    
                    $processed += $batch_count;
                    $batch_time = microtime(true) - $batch_start_time;
                    
                    if ($batch % 10 === 0) {
                        $progress = round(($processed / $total_products) * 100, 2);
                        $this->log->write(sprintf(
                            'Cached batch %d/%d (%d products, %.2f%%, %.2fs)',
                            $batch + 1, 
                            $batches,
                            $processed,
                            $progress,
                            $batch_time
                        ));
                    }
                }
            }
            
            $execution_time = microtime(true) - $start_time;
            
            return [
                'success' => true,
                'message' => sprintf(
                    'Successfully cached %d products in %.2f seconds',
                    $processed,
                    $execution_time
                ),
                'stats' => [
                    'total_products' => $total_products,
                    'processed_products' => $processed,
                    'total_batches' => $batches,
                    'frontend_pages' => $pages_50,
                    'execution_time' => $execution_time
                ]
            ];
            
        } catch (Exception $e) {
            $this->log->write('Cache refresh error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    protected function generateSeoUrl($name) {
        // Remove quotes and extract main text
        $name = preg_replace("/['\"](.*?)['\"]/"," $1 ", $name);
        
        // Transliteration map for Ukrainian characters
        $ukrainian = [
            'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'h', 'ґ'=>'g', 
            'д'=>'d', 'е'=>'e', 'є'=>'ie', 'ж'=>'zh', 'з'=>'z', 
            'и'=>'y', 'і'=>'i', 'ї'=>'yi', 'й'=>'y', 'к'=>'k', 
            'л'=>'l', 'м'=>'m', 'н'=>'n', 'о'=>'o', 'п'=>'p', 
            'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u', 'ф'=>'f', 
            'х'=>'kh', 'ц'=>'ts', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'shch', 
            'ь'=>'', 'ю'=>'iu', 'я'=>'ia'
        ];
        
        // Convert to lowercase and transliterate
        $url = mb_strtolower($name, 'UTF-8');
        $url = strtr($url, $ukrainian);
        
        // Replace all non-alphanumeric characters with hyphens
        $url = preg_replace('/[^a-z0-9-]/', '-', $url);
        
        // Remove multiple consecutive hyphens
        $url = preg_replace('/-+/', '-', $url);
        
        // Trim hyphens from beginning and end
        $url = trim($url, '-');
        
        // Check if URL already exists and append number if needed
        $base_url = $url;
        $counter = 1;
        
        while ($this->urlExists($url)) {
            $url = $base_url . '-' . $counter;
            $counter++;
        }
        
        return $url;
    }
    
    protected function urlExists($seo_url) {
        $query = $this->db->query("SELECT COUNT(*) as total FROM `" . DB_PREFIX . "simple_products` 
            WHERE seo_url = '" . $this->db->escape($seo_url) . "'");
        return $query->row['total'] > 0;
    }
}