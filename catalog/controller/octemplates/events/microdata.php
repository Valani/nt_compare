<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsMicrodata extends Controller {

    public function index(&$route, &$data) {
        $controllers = [
            'information/information',
            'octemplates/blog/oct_blogarticle',
            'octemplates/blog/oct_blogcategory',
            'octemplates/module/oct_sreview_reviews',
            'product/category',
            'product/manufacturer_list',
            'product/manufacturer_info',
            'product/product',
            'product/special'
        ];
    
        if (in_array($route, $controllers) || isset($data['breadcrumbs'])) {
            $oct_showcase_data = $this->config->get('theme_oct_showcase_data');
            $data['oct_showcase_data']['micro'] = $oct_showcase_data['micro'] ?? null;
        
            if ($route == 'product/product' && isset($data['oct_showcase_data']['micro'])) {
                $product_id = (int)$data['product_id'];
                $data['oct_micro_heading_title'] = htmlspecialchars($data['heading_title']);
                $data['oct_product_categories'] = $this->getProductCategoriesName($product_id);
                $data['oct_special_microdata'] = false;
                $data['oct_price_currency'] = $this->session->data['currency'];

                if (!empty($data['special'])) {
                    $data['oct_special_microdata'] = $this->getNumericPrice($data['special']);
                } else {
                    $data['oct_price_microdata'] =$this->getNumericPrice($data['price']);
                }
            
                $data['oct_description_microdata'] = $this->sanitizeDescription($data['description']);
                $data['oct_reviews_all'] = $this->getReviewsByProductId($product_id);
            }
        }
    }

    private function getNumericPrice($priceString) {
        $price = str_replace(' ', '', $priceString);
        $price = preg_replace('/[^0-9\.,]/', '', rtrim($price, '.'));
        $price = str_replace(',', '.', $price);
        return $price;
    }
    
    private function getProductCategoriesName($product_id) {
        $oct_product_categories = $this->model_catalog_product->getCategories($product_id);
        $oct_cat_info = array_map(function ($product_category) {
            return $this->model_catalog_category->getCategory($product_category['category_id']);
        }, $oct_product_categories);
    
        return implode(', ', array_column($oct_cat_info, 'name'));
    }
    
    private function sanitizeDescription($description) {
        return htmlspecialchars(strip_tags(str_replace("\r", "", str_replace("\n", "", str_replace("\\", "/", str_replace("\"", "", html_entity_decode($description, ENT_QUOTES, 'UTF-8')))))));
    }
    
    private function getReviewsByProductId($product_id) {
        $oct_reviews_all = $this->model_catalog_review->getReviewsByProductId($product_id);
    
        return array_map(function ($result) {
            return [
                'author'     => htmlspecialchars($result['author']),
                'text'       => $this->sanitizeDescription($result['text']),
                'rating'     => (int)$result['rating'],
                'date_added' => date($this->language->get('Y-m-d'), strtotime($result['date_added']))
            ];
        }, $oct_reviews_all);
    }
    
}