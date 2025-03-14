<?php
class ControllerApiJcbpartsRefresh extends Controller {
    private $cache_prefix = 'jcb_parts:';
    private $batch_size = 1000;
    private $max_execution_time = 3600;
    
    public function __construct($registry) {
        parent::__construct($registry);
        set_time_limit($this->max_execution_time);
        ini_set('memory_limit', '512M');
    }

    public function index() {
        $this->load->model('catalog/jcbparts');
        $json = ['success' => false];
        
        try {
            // 1. Покращене очищення кешу
            $this->forceClearCache();
            $this->log->write('Спроба очищення кешу');
            
            // Перевірка чи кеш справді очищено
            if ($this->verifyCacheClear()) {
                $this->log->write('Кеш успішно очищено');
            } else {
                throw new Exception('Не вдалося очистити кеш. Будь ласка, перевірте права доступу або очистіть кеш вручну через панель керування хостингом.');
            }
            
            // 2. Cache total products count
            $total_products = $this->model_catalog_jcbparts->getTotalProducts();
            $this->log->write('Загальна кількість продуктів для кешування: ' . $total_products);
            
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
                    $batch_key = $this->cache_prefix . 'batch:' . $offset . ':' . $this->batch_size;
                    $this->model_catalog_jcbparts->setCache($batch_key, $products);
                    
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
                            'Кешовано партію %d/%d (%d продуктів, %.2f%%, %.2fs)',
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
            
            $json = [
                'success' => true,
                'message' => sprintf(
                    'Успішно кешовано %d продуктів за %.2f секунд',
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
            
            $this->log->write('Оновлення кешу завершено: ' . json_encode($json));
            
        } catch (Exception $e) {
            $this->log->write('Помилка оновлення кешу: ' . $e->getMessage());
            $json = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function forceClearCache() {
        try {
            return $this->model_catalog_jcbparts->clearCache();
        } catch (Exception $e) {
            $this->log->write('Error clearing cache: ' . $e->getMessage());
            return false;
        }
    }

    private function verifyCacheClear() {
        // Спробуємо записати та прочитати тестові дані
        $test_key = $this->cache_prefix . 'test';
        $test_data = 'test_' . time();
        
        $this->model_catalog_jcbparts->setCache($test_key, $test_data);
        $result = $this->model_catalog_jcbparts->getCache($test_key);
        
        return $result === $test_data;
    }
}