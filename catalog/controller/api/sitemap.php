<?php
class ControllerApiSitemap extends Controller {
    private $urls = array();
    private $sitemapPath = 'https://nawiteh.ua/work/sitemap.xml';
    
    public function index() {
        try {
            $sitemap = $this->generateSitemap();
            
            if ($this->saveSitemap($sitemap)) {
                $this->response->addHeader('Content-Type: application/json');
                $this->response->setOutput(json_encode([
                    'success' => true,
                    'message' => 'Sitemap successfully generated',
                    'url' => $this->sitemapPath
                ]));
            } else {
                throw new Exception('Failed to save sitemap');
            }
        } catch (Exception $e) {
            $this->response->addHeader('HTTP/1.0 500 Internal Server Error');
            $this->response->setOutput(json_encode([
                'error' => $e->getMessage()
            ]));
        }
    }

    private function generateSitemap() {
        $output  = '<?xml version="1.0" encoding="UTF-8"?>';
        $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/information');

        // Додаємо продукти
        $products = $this->model_catalog_product->getProducts();
        foreach ($products as $product) {
            $url = $this->url->link('product/product', 'product_id=' . $product['product_id']);
            if (!$this->isUrlExists($url)) {
                $output .= $this->getUrlEntry(
                    $url,
                    date('Y-m-d', strtotime($product['date_modified']))
                );
            }
        }

        // Додаємо категорії
        $output .= $this->getCategories(0);

        // Додаємо виробників
        $manufacturers = $this->model_catalog_manufacturer->getManufacturers();
        foreach ($manufacturers as $manufacturer) {
            $url = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']);
            if (!$this->isUrlExists($url)) {
                $output .= $this->getUrlEntry($url);
            }
        }

        // Додаємо інформаційні сторінки
        $informations = $this->model_catalog_information->getInformations();
        foreach ($informations as $information) {
            $url = $this->url->link('information/information', 'information_id=' . $information['information_id']);
            if (!$this->isUrlExists($url)) {
                $output .= $this->getUrlEntry($url);
            }
        }

        // Додаємо статті блогу
        $output .= $this->getBlogUrls();

        $output .= '</urlset>';
        return $output;
    }

    protected function getBlogUrls() {
        $output = '';
        $query = $this->db->query("
            SELECT ba.blogarticle_id, ba.date_modified, su.keyword 
            FROM " . DB_PREFIX . "oct_blogarticle ba 
            LEFT JOIN " . DB_PREFIX . "seo_url su ON (su.query = CONCAT('blogarticle_id=', ba.blogarticle_id)) 
            WHERE ba.status = '1' 
            AND ba.date_available <= NOW()
        ");
    
        foreach ($query->rows as $article) {
            $url = !empty($article['keyword']) 
                ? $this->config->get('config_url') . $article['keyword'] 
                : $this->url->link('blog/article', 'blogarticle_id=' . $article['blogarticle_id']);
            
            // Convert date format to YYYY-MM-DD
            $lastmod = date('Y-m-d', strtotime($article['date_modified']));
            
            $output .= $this->getUrlEntry($url, $lastmod);
        }
        
        return $output;
    }

    protected function getCategories($parent_id, $current_path = '') {
        $output = '';
        $results = $this->model_catalog_category->getCategories($parent_id);

        foreach ($results as $result) {
            if (!$current_path) {
                $new_path = $result['category_id'];
            } else {
                $new_path = $current_path . '_' . $result['category_id'];
            }

            $url = $this->url->link('product/category', 'path=' . $new_path);
            if (!$this->isUrlExists($url)) {
                $output .= $this->getUrlEntry($url);
            }

            $output .= $this->getCategories($result['category_id'], $new_path);
        }

        return $output;
    }

    private function getUrlEntry($url, $lastmod = '') {
        $output = '<url>';
        $output .= '<loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>';
        if ($lastmod) {
            $output .= '<lastmod>' . $lastmod . '</lastmod>';
        }
        $output .= '</url>';
        
        $this->urls[] = $url;
        return $output;
    }

    private function isUrlExists($url) {
        return in_array($url, $this->urls);
    }

    private function saveSitemap($content) {
        $path = parse_url($this->sitemapPath, PHP_URL_PATH);
        $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $path;
        
        // Перевіряємо чи існує директорія
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return file_put_contents($absolutePath, $content) !== false;
    }
}