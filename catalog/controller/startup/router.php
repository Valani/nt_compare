<?php
class ControllerStartupRouter extends Controller {
    public function index() {
        // Route
        if (isset($this->request->get['route']) && $this->request->get['route'] != 'startup/router') {
            $route = $this->request->get['route'];
        } else {
            $route = $this->config->get('action_default');
        }
        
        // Перевіряємо URL на наявність шляху
        $request_uri = trim($this->request->server['REQUEST_URI'], '/');
        
        // Розділяємо URI та параметри запиту
        $uri_parts = explode('?', $request_uri);
        $path = $uri_parts[0];
        
        // Розбиваємо шлях на частини
        $path_parts = explode('/', $path);
        
        // Перевіряємо чи це шлях до JCB parts
        if ($path_parts[0] === 'all-jcb-parts') {
            if (count($path_parts) > 2 && $path_parts[1] === 'item') {
                // Це сторінка продукту JCB
                $route = 'product/jcbproduct';
                $this->request->get['seo_url'] = $path_parts[2];
                $this->request->get['route'] = $route;
                return $this->forward($route);
            } elseif (count($path_parts) === 1) {
                // Це каталог JCB parts
                $route = 'product/jcbparts';
                
                // Зберігаємо всі існуючі GET параметри
                if (isset($uri_parts[1])) {
                    parse_str($uri_parts[1], $query_params);
                    foreach ($query_params as $key => $value) {
                        $this->request->get[$key] = $value;
                    }
                }
                
                $this->request->get['route'] = $route;
                return $this->forward($route);
            }
        }
        
        // Sanitize the call
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);
        
        // Trigger the pre events
        $result = $this->event->trigger('controller/' . $route . '/before', array(&$route, &$data));
        
        if (!is_null($result)) {
            return $result;
        }
        
        // We dont want to use the loader class as it would make an controller callable.
        $action = new Action($route);
        
        // Any output needs to be another Action object.
        $output = $action->execute($this->registry); 
        
        // Trigger the post events
        $result = $this->event->trigger('controller/' . $route . '/after', array(&$route, &$data, &$output));
        
        if (!is_null($result)) {
            return $result;
        }
        
        return $output;
    }
    
    protected function forward($route) {
        $action = new Action($route);
        return $action->execute($this->registry);
    }
}