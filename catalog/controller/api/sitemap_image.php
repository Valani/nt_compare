<?php
class ControllerApiSitemapImage extends Controller {
    private $batch_size = 100;
    private $output_file;
    
    public function index() {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        
        $this->output_file = DIR_APPLICATION . '../work/sitemap_image.xml';
        
        try {
            $this->initializeXmlFile();
            
            $total_products = $this->model_catalog_product->getTotalProducts();
            
            for ($offset = 0; $offset < $total_products; $offset += $this->batch_size) {
                $products = $this->model_catalog_product->getProducts([
                    'start' => $offset,
                    'limit' => $this->batch_size
                ]);
                
                $this->processBatch($products);
                
                unset($products);
                gc_collect_cycles();
            }
            
            $this->finalizeXmlFile();
            
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'success' => true,
                'total_processed' => $total_products
            ]));
            
        } catch (Exception $e) {
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }
    
    private function initializeXmlFile() {
        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"
                . '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        file_put_contents($this->output_file, "\xEF\xBB\xBF" . $header);
    }
    
    private function processBatch($products) {
        $output = '';
        
        foreach ($products as $product) {
            $images = $this->getProductImages($product);
            
            if (!empty($images)) {
                $valid_images = $this->validateImages($images);
                
                if (!empty($valid_images)) {
                    $output .= $this->generateProductXml($product, $valid_images);
                }
            }
            
            unset($images, $valid_images);
        }
        
        file_put_contents($this->output_file, $output, FILE_APPEND);
    }
    
    private function getProductImages($product) {
        $images = [];
        
        if (!empty($product['image'])) {
            $images[] = $product['image'];
        }
        
        if (!empty($product['product_id'])) {
            $additional_images = $this->model_catalog_product->getProductImages($product['product_id']);
            foreach ($additional_images as $image) {
                if (!empty($image['image'])) {
                    $images[] = $image['image'];
                }
            }
        }
        
        return $images;
    }
    
    private function validateImages($images) {
        $valid_images = [];
        foreach ($images as $image) {
            $image_url = $this->convertToWebP($this->model_tool_image->resize($image, 1200, 1200));
            if ($image_url) {
                $valid_images[] = $image_url;
            }
        }
        return $valid_images;
    }
    
    private function convertToWebP($image_url) {
        // Замінюємо .jpg або .jpeg на .webp
        $webp_url = preg_replace('/(\.jpe?g)$/i', '.webp', $image_url);
        
        // Додаємо /webp/ після /cache/ тільки якщо його ще немає
        $parts = explode('/cache/', $webp_url, 2);
        if (count($parts) == 2 && strpos($parts[1], 'webp/') !== 0) {
            $webp_url = $parts[0] . '/cache/webp/' . $parts[1];
        }
        
        return $webp_url;
    }
    
    private function generateProductXml($product, $valid_images) {
        $output = '  <url>' . "\n"
                . '    <loc>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</loc>' . "\n";
        
        foreach ($valid_images as $image_url) {
            $output .= '    <image:image>' . "\n"
                    . '      <image:loc>' . htmlspecialchars($image_url) . '</image:loc>' . "\n"
                    . '    </image:image>' . "\n";
        }
        
        $output .= '  </url>' . "\n";
        
        return $output;
    }
    
    private function finalizeXmlFile() {
        file_put_contents($this->output_file, '</urlset>', FILE_APPEND);
    }
}