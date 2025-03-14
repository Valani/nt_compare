<?php
class ControllerApiImportJcbproduct extends Controller {
    private $cache_prefix = 'jcb_parts:';
    private $batch_size = 1000;
    private $max_execution_time = 3600; // 1 hour
    
    public function __construct($registry) {
        parent::__construct($registry);
        set_time_limit($this->max_execution_time);
        ini_set('memory_limit', '512M');
    }

    public function index() {
        $this->load->model('catalog/product');
        $this->load->model('catalog/jcbparts');
        
        // Check XML directory and get first file
        $xml_dir = DIR_APPLICATION . '../work/xml_jcbproduct/';
        $xml_files = glob($xml_dir . '*.xml');
        
        if (empty($xml_files)) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['error' => 'No XML files found in directory']));
            return;
        }

        // Take first XML file
        $xml_file = $xml_files[0];

        try {
            // Load and parse XML
            $xml = simplexml_load_file($xml_file);
            
            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($xml->product as $product) {
                try {
                    $sku = $this->db->escape((string)$product->sku);
                    $name = $this->db->escape((string)$product->name);
                    $price = (float)$product->price;
                    
                    // Check if product with this SKU already exists
                    $query = $this->db->query("SELECT sku FROM `" . DB_PREFIX . "simple_products` 
                        WHERE sku = '" . $sku . "'");
                    
                    if ($query->num_rows) {
                        // Update existing product
                        $this->db->query("UPDATE `" . DB_PREFIX . "simple_products` 
                            SET price = '" . (float)$price . "'
                            WHERE sku = '" . $sku . "'");
                        $updated++;
                    } else {
                        // Add new product
                        $seo_url = $this->generateSeoUrl($name);
                        
                        $this->db->query("INSERT INTO `" . DB_PREFIX . "simple_products` 
                            SET sku = '" . $sku . "',
                                name = '" . $name . "',
                                price = '" . (float)$price . "',
                                seo_url = '" . $this->db->escape($seo_url) . "'");
                        $imported++;
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Error processing SKU {$sku}: " . $e->getMessage();
                    $skipped++;
                }
            }

            // Delete processed XML file
            unlink($xml_file);
            
            // Refresh cache via API call
            $cache_result = $this->refreshCacheViaApi();
            
            // Regenerate sitemap via API call
            $sitemap_result = $this->regenerateSitemapViaApi();
            
            $response = [
                'success' => true,
                'import' => [
                    'processed_file' => basename($xml_file),
                    'new_imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => $errors
                ],
                'cache' => $cache_result,
                'sitemap' => $sitemap_result
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
    
    protected function refreshCacheViaApi() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://nawiteh.ua/index.php?route=api/jcbparts_refresh');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            return json_decode($result, true);
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    protected function regenerateSitemapViaApi() {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://nawiteh.ua/index.php?route=api/sitemap_jcbparts');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            return json_decode($result, true);
            
        } catch (Exception $e) {
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