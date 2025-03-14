<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* URL class
*/
class Url {
	private $url;
	private $ssl;
	private $rewrite = array();
	
	// Визначаємо префікси для різних типів маршрутів
	private $routePrefixes = [
		'product/product' => 'products/',
		'information/information' => 'pages/',
		'information/contact' => 'pages/',
		'octemplates/module/oct_sreview_reviews' => 'pages/'
	];
	
	// Визначаємо префікси для категорій
	private $categoryPrefixes = [
		'services' => [13, 14, 15],
		'catalog' => [11, 43, 58, 60, 74, 464, 511, 512, 591, 577, 584, 570]
	];
	
	/**
	 * Constructor
	 *
	 * @param	string	$url
	 * @param	string	$ssl
	 */
	public function __construct($url, $ssl = '') {
		$this->url = $url;
		$this->ssl = $ssl;
	}

	/**
	 * @param	object	$rewrite
	 */	
	public function addRewrite($rewrite) {
		$this->rewrite[] = $rewrite;
	}
	
	/**
	 * Отримати префікс для категорії
	 * 
	 * @param	string	$category_id
	 * @return	string
	 */
	private function getCategoryPrefix($category_id) {
		foreach ($this->categoryPrefixes as $prefix => $ids) {
			if (in_array($category_id, $ids)) {
				return $prefix . '/';
			}
		}
		return '';
	}
	
	/**
	 * Отримати префікс для маршруту
	 * 
	 * @param	string	$route
	 * @param	string	$args
	 * @return	string
	 */
	private function getPrefix($route, $args = '') {
		// Перевірка на прямі відповідності маршрутів
		if (isset($this->routePrefixes[$route])) {
			return $this->routePrefixes[$route];
		}
		
		// Обробка категорій
		if ($route == 'product/category' && !empty($args)) {
			$path = '';
			parse_str(str_replace('&amp;', '&', $args), $params);
			
			if (isset($params['path'])) {
				$category_ids = explode('_', $params['path']);
				$main_category_id = (int)reset($category_ids);
				return $this->getCategoryPrefix($main_category_id);
			}
		}
		
		return '';
	}

	/**
	 * @param	string		$route
	 * @param	mixed		$args
	 * @param	bool		$secure
	 *
	 * @return	string
	 */
	public function link($route, $args = '', $secure = false) {
		$prefix = $this->getPrefix($route, $args);
		
		// Перевірка на адмін-панель
		if (strpos($this->ssl, '/admin') === false) {
			$url = ($this->ssl && $secure ? $this->ssl : $this->url) . $prefix . 'index.php?route=' . $route;
		} else {
			$url = ($this->ssl && $secure ? $this->ssl : $this->url) . 'index.php?route=' . $route;
		}
		
		if ($args) {
			if (is_array($args)) {
				$url .= '&amp;' . http_build_query($args);
			} else {
				$url .= str_replace('&', '&amp;', '&' . ltrim($args, '&'));
			}
		}
		
		foreach ($this->rewrite as $rewrite) {
			$url = $rewrite->rewrite($url);
		}
		
		return $url;
	}
}