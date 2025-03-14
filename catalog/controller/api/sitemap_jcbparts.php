<?php
class ControllerApiSitemapJcbparts extends Controller {
    private $urls = array();
    private $sitemapPath = 'work/';
    private $batchSize = 1000;
    private $urlsPerFile = 20000;
    
    public function index() {
        try {
            // Create work directory if it doesn't exist
            $sitemapDir = DIR_APPLICATION . '../' . $this->sitemapPath;
            if (!is_dir($sitemapDir)) {
                mkdir($sitemapDir, 0755, true);
            }
            
            // Generate sitemaps
            $sitemapFiles = $this->generateSitemaps();
            
            // Generate index file
            if ($this->generateIndex($sitemapFiles)) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'success' => true,
                    'message' => 'JCB Parts sitemaps successfully generated',
                    'url' => $this->config->get('config_url') . $this->sitemapPath . 'sitemap.xml'
                ]));
            } else {
                throw new Exception('Failed to save JCB parts sitemap index');
            }
        } catch (Exception $e) {
            $this->response->addHeader('HTTP/1.0 500 Internal Server Error');
            $this->response->setOutput(json_encode([
                'error' => $e->getMessage()
            ]));
        }
    }

    private function generateSitemaps() {
        $this->load->model('catalog/jcbparts');
        $sitemapFiles = [];
        $urlCount = 0;
        $fileIndex = 1;
        $currentSitemap = $this->startSitemap();
        
        // Add main category page to first sitemap
        $currentSitemap .= $this->getUrlEntry(
            $this->config->get('config_url') . 'all-jcb-parts'
        );
        $urlCount++;
        
        // Process products in batches
        $start = 0;
        while (true) {
            $products = $this->model_catalog_jcbparts->getProductsBatch($start, $this->batchSize);
            
            if (empty($products)) {
                break;
            }
            
            foreach ($products as $product) {
                $url = $this->config->get('config_url') . 'all-jcb-parts/item/' . $product['seo_url'];
                
                if (!in_array($url, $this->urls)) {
                    // Check if we need to start a new sitemap file
                    if ($urlCount >= $this->urlsPerFile) {
                        $currentSitemap .= $this->endSitemap();
                        $filename = $this->saveSitemap($currentSitemap, $fileIndex);
                        if ($filename) {
                            $sitemapFiles[] = $filename;
                        }
                        
                        $currentSitemap = $this->startSitemap();
                        $urlCount = 0;
                        $fileIndex++;
                    }
                    
                    $currentSitemap .= $this->getUrlEntry($url);
                    $urlCount++;
                }
            }
            
            $start = end($products)['id'];
            unset($products);
        }
        
        // Save the last sitemap if it contains any URLs
        if ($urlCount > 0) {
            $currentSitemap .= $this->endSitemap();
            $filename = $this->saveSitemap($currentSitemap, $fileIndex);
            if ($filename) {
                $sitemapFiles[] = $filename;
            }
        }
        
        return $sitemapFiles;
    }

    private function startSitemap() {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
               '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    }

    private function endSitemap() {
        return "\n" . '</urlset>';
    }

    private function getUrlEntry($url) {
        $output = "\n" . '  <url>';
        $output .= "\n" . '    <loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>';
        $output .= "\n" . '  </url>';
        
        $this->urls[] = $url;
        return $output;
    }

    private function saveSitemap($content, $index) {
        $filename = 'sitemap_jcb_' . $index . '.xml';
        $absolutePath = DIR_APPLICATION . '../' . $this->sitemapPath . $filename;
        
        if (file_put_contents($absolutePath, $content) !== false) {
            return $filename;
        }
        
        return false;
    }
    
    private function generateIndex($sitemapFiles) {
        $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($sitemapFiles as $file) {
            $output .= '  <sitemap>' . "\n";
            $output .= '    <loc>' . $this->config->get('config_url') . $this->sitemapPath . $file . '</loc>' . "\n";
            $output .= '  </sitemap>' . "\n";
        }
        
        $output .= '</sitemapindex>';
        
        return file_put_contents(
            DIR_APPLICATION . '../' . $this->sitemapPath . 'index_allparts_sitemap.xml',
            $output
        ) !== false;
    }
}