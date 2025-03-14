<?php
class ModelCatalogJcbparts extends Model {
    private $cache;
    private $cache_prefix = 'jcb_parts:';
    private $cache_ttl = 604800; // 1 тиждень
    private $is_cache_available = false;
    private $using_memcache = false;
    
    public function __construct($registry) {
        parent::__construct($registry);
        $this->is_cache_available = $this->initCache();
    }
    
    private function initCache() {
        try {
            // Спочатку пробуємо Memcached
            if (class_exists('Memcached')) {
                $this->cache = new Memcached();
                $socket_path = '/home/ilweb/.system/memcache/socket';
                
                if (file_exists($socket_path)) {
                    $this->cache->addServer($socket_path, 0);
                    
                    // Налаштування для кращої продуктивності
                    $this->cache->setOption(Memcached::OPT_COMPRESSION, true);
                    $this->cache->setOption(Memcached::OPT_NO_BLOCK, true);
                    $this->cache->setOption(Memcached::OPT_TCP_NODELAY, true);
                    
                    // Тестуємо підключення
                    if ($this->testConnection()) {
                        $this->log->write('Successfully connected to Memcached via socket');
                        return true;
                    }
                }
            }
            
            // Якщо Memcached не працює, пробуємо Memcache
            if (class_exists('Memcache')) {
                $this->cache = new Memcache();
                $socket_path = 'unix:///home/ilweb/.system/memcache/socket';
                
                if ($this->cache->connect($socket_path, 0)) {
                    $this->using_memcache = true;
                    $this->log->write('Successfully connected to Memcache via socket');
                    return true;
                }
            }
            
            $this->log->write('Both Memcached and Memcache connection attempts failed');
            return false;
            
        } catch (Exception $e) {
            $this->log->write('Cache initialization error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function testConnection() {
        try {
            $test_key = $this->cache_prefix . 'test_connection';
            $test_value = 'test_' . time();
            
            if ($this->using_memcache) {
                $set_result = $this->cache->set($test_key, $test_value, 0, 30);
            } else {
                $set_result = $this->cache->set($test_key, $test_value, 30);
            }
            
            if (!$set_result) {
                return false;
            }
            
            $get_result = $this->cache->get($test_key);
            return $get_result === $test_value;
            
        } catch (Exception $e) {
            $this->log->write('Connection test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getCache($key) {
        if (!$this->is_cache_available) {
            return false;
        }
        
        try {
            $value = $this->cache->get($key);
            
            if ($this->using_memcache) {
                return $value;
            } else {
                // Для Memcached перевіряємо код результату
                if ($this->cache->getResultCode() !== Memcached::RES_SUCCESS) {
                    return false;
                }
                return $value;
            }
        } catch (Exception $e) {
            $this->log->write('Error getting cache: ' . $e->getMessage());
            return false;
        }
    }
    
    public function setCache($key, $data, $ttl = null) {
        if (!$this->is_cache_available) {
            return false;
        }
        
        try {
            $ttl = $ttl ?: $this->cache_ttl;
            
            if ($this->using_memcache) {
                return $this->cache->set($key, $data, 0, $ttl);
            } else {
                return $this->cache->set($key, $data, $ttl);
            }
        } catch (Exception $e) {
            $this->log->write('Error setting cache: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getTotalProducts() {
        $cache_key = $this->cache_prefix . 'total';
        $total = $this->getCache($cache_key);
        
        if ($total === false) {
            try {
                $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "simple_products");
                $total = (int)$query->row['total'];
                
                $this->setCache($cache_key, $total);
                
            } catch (Exception $e) {
                $this->log->write('Error getting total products: ' . $e->getMessage());
                return 0;
            }
        }
        
        return $total;
    }
    
    public function getProducts($start = 0, $limit = 50) {
        $cache_key = $this->cache_prefix . 'products:' . $start . ':' . $limit;
        $this->log->write('Getting products with key: ' . $cache_key);
        
        $products = $this->getCache($cache_key);
        $this->log->write('Cache ' . ($products === false ? 'MISS' : 'HIT') . ' for key: ' . $cache_key);
        
        if ($products === false) {
            $this->log->write('Cache miss, querying database...');
            $time_start = microtime(true);
            
            try {
                $sql = "SELECT id, name, sku, price, seo_url 
                        FROM " . DB_PREFIX . "simple_products 
                        ORDER BY name ASC 
                        LIMIT " . (int)$start . "," . (int)$limit;
                
                $query = $this->db->query($sql);
                $products = $query->rows;
                
                $time_end = microtime(true);
                $this->log->write('Database query took: ' . ($time_end - $time_start) . ' seconds');
                
                $cache_result = $this->setCache($cache_key, $products);
                $this->log->write('Cache set result: ' . ($cache_result ? 'SUCCESS' : 'FAILED'));
                
            } catch (Exception $e) {
                $this->log->write('Error getting products: ' . $e->getMessage());
                return [];
            }
        }
        
        return $products;
    }

    // Новий метод для пакетного отримання товарів
    public function getProductsBatch($start = 0, $limit = 1000) {
        $this->log->write('Getting products batch starting from: ' . $start . ' with limit: ' . $limit);
        try {
            // Використовуємо STRAIGHT_JOIN для оптимізації великих вибірок
            $sql = "SELECT STRAIGHT_JOIN id, name, sku, price, seo_url 
                    FROM " . DB_PREFIX . "simple_products 
                    WHERE id > " . (int)$start . "
                    ORDER BY id ASC 
                    LIMIT " . (int)$limit;
            
            $query = $this->db->query($sql);
            $this->log->write('Successfully retrieved batch with ' . count($query->rows) . ' products');
            return $query->rows;
            
        } catch (Exception $e) {
            $this->log->write('Error getting products batch: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getProductBySeoUrl($seo_url) {
        $cache_key = $this->cache_prefix . 'product:' . $seo_url;
        $product_data = $this->getCache($cache_key);
        
        if ($product_data === false) {
            try {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "simple_products WHERE seo_url = '" . $this->db->escape($seo_url) . "'");
                
                $product_data = $query->row;
                
                if ($product_data) {
                    $this->setCache($cache_key, $product_data);
                }
                
            } catch (Exception $e) {
                $this->log->write('Error getting product by seo_url: ' . $e->getMessage());
                return false;
            }
        }
        
        return $product_data;
    }
    
    public function clearCache() {
        if (!$this->is_cache_available) {
            return false;
        }
        
        try {
            if ($this->using_memcache) {
                return $this->cache->flush();
            } else {
                return $this->cache->flush();
            }
        } catch (Exception $e) {
            $this->log->write('Error clearing cache: ' . $e->getMessage());
            return false;
        }
    }
    
    public function diagnostics() {
        return [
            'memcached_exists' => class_exists('Memcached'),
            'memcache_exists' => class_exists('Memcache'),
            'cache_type' => $this->using_memcache ? 'Memcache' : 'Memcached',
            'is_available' => $this->is_cache_available,
            'connection_test' => $this->testConnection(),
            'socket_memcached' => '/home/ilweb/.system/memcache/socket',
            'socket_memcache' => 'unix:///home/ilweb/.system/memcache/socket',
            'memcached_socket_exists' => file_exists('/home/ilweb/.system/memcache/socket'),
        ];
    }
}