<?php
class ControllerProductJcbparts extends Controller {
    public function index() {
        $start_time = microtime(true);
        $this->log->write('Starting page load...');
        
        $this->load->language('product/category');
        $this->load->model('catalog/jcbparts');
        
        // Базові змінні
        $data = [];
        $this->log->write('Before getTotalProducts');
        $total_products = $this->model_catalog_jcbparts->getTotalProducts();
        $this->log->write('After getTotalProducts: ' . $total_products);
        
        // Пагінація
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = 50;
        $start = ($page - 1) * $limit;
        
        $this->log->write('Before getProducts');
        $products = $this->model_catalog_jcbparts->getProducts($start, $limit);
        $this->log->write('After getProducts: ' . count($products));
        
        // Перевірка валідності сторінки
        $max_page = ceil($total_products / $limit);
        if ($page > $max_page && $max_page > 0) {
            $this->response->redirect($this->url->link('product/jcbparts'));
            return;
        }

        // Breadcrumbs
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];
        $data['breadcrumbs'][] = [
            'text' => 'Каталог запчастин JCB',
            'href' => $this->url->link('product/jcbparts')
        ];
        
        // SEO
        $base_title = 'Каталог запчастин JCB - Оригінальні запчастини';
        $base_description = 'Каталог оригінальних запчастин JCB. Великий вибір деталей для спецтехніки JCB з доставкою по всій Україні.';
        
        if ($page > 1) {
            $this->document->setTitle(sprintf('Сторінка %d - %s', $page, $base_title));
            $this->document->setDescription(sprintf('Сторінка %d - %s', $page, $base_description));
        } else {
            $this->document->setTitle($base_title);
            $this->document->setDescription($base_description);
        }

        // Canonical URL
        $base_url = str_replace('index.php?route=product/jcbparts', 'all-jcb-parts', $this->url->link('product/jcbparts'));

        // Remove any existing query parameters
        $base_url = strtok($base_url, '?');

        // Add page parameter if needed
        $canonical_url = $page > 1 ? $base_url . '?page=' . $page : $base_url;

        $this->document->addLink($canonical_url, 'canonical');
        // Prev/Next links
        if ($page > 1) {
            $prev_url = $page - 1 > 1 ? 
                str_replace('index.php?route=product/jcbparts', 'all-jcb-parts', $this->url->link('product/jcbparts', 'page=' . ($page - 1))) :
                str_replace('index.php?route=product/jcbparts', 'all-jcb-parts', $this->url->link('product/jcbparts'));
            $this->document->addLink($prev_url, 'prev');
        }

        if ($page < $max_page) {
            $next_url = str_replace('index.php?route=product/jcbparts', 'all-jcb-parts', $this->url->link('product/jcbparts', 'page=' . ($page + 1)));
            $this->document->addLink($next_url, 'next');
        }
        
        // Heading
        $data['heading_title'] = $page > 1 ? 
            sprintf('Сторінка %d - Каталог запчастин JCB', $page) : 
            'Каталог запчастин JCB';

        // Отримання товарів
        $products = $this->model_catalog_jcbparts->getProducts($start, $limit);
        
        // Підготовка даних для відображення
        $data['products'] = array();
        foreach ($products as $product) {
            $data['products'][] = array(
                'product_id' => $product['id'],
                'name'      => $product['name'],
                'sku'       => $product['sku'],
                'price'     => $this->currency->format(
                    $this->tax->calculate($product['price'], 0, $this->config->get('config_tax')),
                    $this->session->data['currency']
                ),
                'href'      => '/all-jcb-parts/item/' . $product['seo_url']
            );
        }

        // Пагінація
        $pagination = new Pagination();
        $pagination->total = $total_products;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = str_replace('index.php?route=product/jcbparts', 'all-jcb-parts', 
            $this->url->link('product/jcbparts')) . '?page={page}';

        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf('Показано %d-%d із %d (%d сторінок)',
            ($total_products) ? (($page - 1) * $limit) + 1 : 0,
            ((($page - 1) * $limit) > ($total_products - $limit)) ? $total_products : ((($page - 1) * $limit) + $limit),
            $total_products,
            ceil($total_products / $limit)
        );

        // Завантаження необхідних компонентів шаблону
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('product/jcbparts', $data));
        $end_time = microtime(true);
        $execution_time = ($end_time - $start_time);
        $this->log->write('Page load time: ' . $execution_time . ' seconds');
    }
}