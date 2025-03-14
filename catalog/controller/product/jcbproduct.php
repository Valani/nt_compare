<?php
class ControllerProductJcbproduct extends Controller {
    public function index() {
        $this->load->language('product/product');
        $this->load->model('catalog/jcbparts');
        
        $data = [];
        
        if (isset($this->request->get['seo_url'])) {
            $seo_url = $this->request->get['seo_url'];
        } else {
            $this->response->redirect($this->url->link('product/jcbparts'));
        }
        
        // Отримуємо дані продукту
        $product_info = $this->model_catalog_jcbparts->getProductBySeoUrl($seo_url);

        $data['base_url'] = rtrim(HTTP_SERVER, '/');
        
        // Create canonical URL in the correct format
        $data['canonical_url'] = $data['base_url'] . '/all-jcb-parts/item/' . $seo_url;
        
        if ($product_info['price']) {
            $price_value = $this->tax->calculate(
                $product_info['price'],
                0,
                $this->config->get('config_tax')
            );
            // Зберігаємо ціну без форматування для schema.org
            $data['price_raw'] = number_format($price_value, 2, '.', '');
            // Форматована ціна для відображення
            $data['price'] = $this->currency->format(
                $price_value,
                $this->session->data['currency']
            );
        }

        if ($product_info) {
            // Нова логіка для title
            $base_title = $product_info['name'];
            if (mb_strlen($base_title) < 30) {
                $title = $base_title . ' - купити на Nawiteh.ua';
            } else {
                $title = $base_title . ' | Nawiteh.ua';
            }
            
            // Нова логіка для meta description
            $description = sprintf(
                '%s ✅ Nawiteh.ua: • Оригінал JCB • Термін доставки 4 тижні • Допомога у підборі • Гарантія якості ☎ 093 100-00-11',
                $product_info['name']
            );
            
            $this->document->setTitle($title);
            $this->document->setDescription($description);
            $this->document->addLink($data['canonical_url'], 'canonical');
            
            // Хлібні крихти
            $data['breadcrumbs'] = [];
            
            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home')
            ];
            
            $data['breadcrumbs'][] = [
                'text' => 'Каталог запчастин JCB',
                'href' => '/all-jcb-parts'
            ];
            
            $data['breadcrumbs'][] = [
                'text' => $product_info['name'],
                'href' => '/all-jcb-parts/item/' . $seo_url
            ];
            
            // Основні дані продукту
            $data['heading_title'] = $product_info['name'];
            $data['sku'] = $product_info['sku'];
            
            // Ціна
            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $data['price'] = $this->currency->format(
                    $this->tax->calculate(
                        $product_info['price'], 
                        0, 
                        $this->config->get('config_tax')
                    ), 
                    $this->session->data['currency']
                );
            } else {
                $data['price'] = false;
            }
            
            // Кнопка купити
            $data['button_cart'] = $this->language->get('button_cart');
            $data['product_id'] = $product_info['id'];
            
            // Мінімальна кількість
            $data['minimum'] = 1;
            
            // Завантаження шаблонів
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');
            
            $this->response->setOutput($this->load->view('product/jcbproduct', $data));
        } else {
            // Якщо продукт не знайдено
            $data['breadcrumbs'][] = [
                'text' => $this->language->get('text_error'),
                'href' => $this->url->link('product/jcbproduct', 'seo_url=' . $seo_url)
            ];
            
            $this->document->setTitle($this->language->get('text_error'));
            
            $data['continue'] = $this->url->link('common/home');
            
            $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
            
            $this->response->setOutput($this->load->view('error/not_found', $data));
        }
    }
}