<?php

/**
 * @category   OpenCart
 * @package    SEO URL Generator PRO
 * @copyright  © Serge Tkach, 2018–2024, http://sergetkach.com/
 */

class ControllerExtensionModuleSeoURLGeneratorRedirects extends Controller {
	private $parts;
	private $last_part;
	private $languages_raw = [];
	private $languages = [];
	private $has_equals = false;
	
	function __construct($registry) {
		parent::__construct($registry);
		
		$this->parts = [];
		
		if (isset($this->request->get['_route_'])) {
			$this->parts = explode('/', $this->request->get['_route_']);

			$this->parts = array_filter($this->parts); // ex oc-store-3037.loc/category/
			
			$this->last_part = mb_strtolower( end($this->parts) );
		}		
		
		$this->languages_raw = $this->model_localisation_language->getLanguages();
		
		foreach ($this->languages_raw as $language) {
			$this->languages[$language['language_id']] = $language;
		}
	}
	
	
	/* 
	 * Note 1
	 * 
	 * Maybe that SEO URL of this essence hasn't been changed, but the parent category has changed the URL
	 * (WAS) opencart-3038.loc/desktops/iphone
	 * (IS)  opencart-3038.loc/monitors/iphone
	 * 
	 * (OK) SeoPro redirect to canonical with main category
	 * (OK) OpenCart (2.3.0.3; 3.0.3.8) seo_url.php redirect to canonical without category in URL -- opencart-3038.loc/iphone
	 * (A!) Both execute before my code
	 * 
	 * SO these redirects are only for cases when $this->last_part found in redirects
	 * 
	 * Ex:
	 * 
	 * ------
	 * (OK)
	 * opencart-3038.loc/desktops/40-en
	 * ->
	 * opencart-3038.loc/monitors/iphone
	 * 
	 * ------
	 * (OK)
	 * opencart-3038.loc/monitors/40-en
	 * ->
	 * opencart-3038.loc/monitors/iphone
	 * 
	 * 
	 * 
	 * 
	 * Note 2
	 * strtolower for SeoPro
	 * 
	 * 
	 * 
	 * 
	 * Note 3
	 * We need detect language of $last_part
	 * It is necessary for cases when default language was ru and then was changed to uk, and ru was moved to ru/ virtual directory
	 * It is possible in OpenCart 3 when are used different SEO URL for each language
	 * Without own settigns of routing ( with language dir ) we cannot know exactly what type or routing it is used... with languages directories or not
	 * It is necessary not to be equals SEO URLs and redirectt OR we use system language_id defined before this controller
	 * 
	 * 
	 * 
	 * 
	 * Note 4
	 * In the `oc_seo_url_generator_redirects` table, I also store the current value of seo_url_actual, 
	 * but when generating a link, I don’t use it. I rely on the system method where is used `query` row only...
	 * oops...
	 * 
	 */
	public function index() {
		if (count($this->parts) == 0)
			return;
		
		$new_url = false;

		// Note 1-A: SELECT all $this->parts not only $this->last_part
		$in = '';

		$i = 0;
		foreach ($this->parts as $item) {
			$in	.= ($i) ? ', ' : '';
			$in	.= "'" . $this->db->escape($item) . "'";
			$i++;
		}

		$sql = "SELECT * FROM " . DB_PREFIX . "seo_url_generator_redirects WHERE seo_url_old IN ($in)";
		$sql .= " AND store_id = '" . (int) $this->config->get('config_store_id') . "'";
		$sql .= " ORDER BY seo_url_id DESC";

		$query = $this->db->query($sql);

		$res								 = [];
		$keys_with_redirects = [];
		$essence_to_keys		 = [];
		$language_of_old_url = [];
		
		$redirects_rows = [];
		
		$i = 0;
		if ($query->rows) {
			$redirects_rows = $query->rows;

			foreach ($query->rows as $item) {
				$seo_url_old = mb_strtolower($item['seo_url_old']);
				
				$keys_with_redirects[$i] = $seo_url_old;
				$essence_to_keys[$seo_url_old]	= $item['query'];
				$language_of_old_url[$seo_url_old] = $item['language_id'];
				
				$i++;
			}
		}

		if (in_array(mb_strtolower($this->last_part), $keys_with_redirects)) {
			// Essence type is known from redirects
			$res = explode('=', $essence_to_keys[$this->last_part]);
			
			// Note 3-A: equals detect - but for $this->last_part only
			$redirects_last_part = [];
			
			foreach ($redirects_rows as $i => $value) {
				if ($essence_to_keys[$this->last_part] == $value['query']) {
					$redirects_last_part[$i] = $value;
					$redirects_last_part[$i]['keyword'] = $value['seo_url_old'];
				}
			}
			
			$this->detectEquals($redirects_last_part);
			
			// Note 3-B: Actually can be equals redirects for 2 and more languages...
			if (($this->config->get('config_language_id') != $language_of_old_url[$this->last_part]) && (!$this->has_equals)) {
				$this->switchLanguage($language_of_old_url[$this->last_part]);
			}
			
		} else {
			// Note 1-B: $this->last_part no in redirects
		}
		
		
		if (count($this->parts) > 1) {
			$path = $this->getPath($this->parts, $essence_to_keys);
		} else {
			$path = '';
		}

		if (count($res) > 0) {
			$new_url = $this->buildURLForEssence($res[0], $res[1], $path);

			if ($new_url) {				
				# Switch language forcefully - don't change this string (!)
				
				$this->response->redirect($new_url, 301);
				exit;
			}
		}
		
		return;
	}
	
	/*
	 * Пока что это костыль
	 * С другой стороны, для украниских сайтов (большинство клиентов) вроде как подойдет. Хотя айдишки языка в OpenCart отличаются от ocStore
	 * Да и подключиться можно с ретурном нужного
	 */
	private function langPrefix($language_id) {
		$languages = [
			1 => 'ru/',
			3 => '',
		];
		
		return $languages[$language_id];
	}	
	
	private function langPrefixPrepend($url, $prefix) {
		if (strpos($url, HTTPS_SERVER)) {
			$url = str_replace(HTTPS_SERVER, HTTPS_SERVER . $prefix, $url);
		} else {
			$url = str_replace(HTTP_SERVER, HTTP_SERVER . $prefix, $url);
		}

		return $url;
	}
	
	
	/*
	 *  По разному переключает языки. А почему?
	 * 
	 * Отталкиваемся от системного способа в catalog/controller/startup/startup.php
	 * Встроенный переключатель языка системы просто присваивает сессию + редиректит 
	 * $this->session->data['language'] = $this->request->post['code'];
	 * 
	 * При загрузке новой страницы там есть дефолтное присвоение
	 * $this->session->data['language'] = $code; // if !
	 * setcookie('language', $code, time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
	 * 
	 * // Overwrite the default language object
	 * $language = new Language($code);
	 * $language->load($code);
	 * $this->registry->set('language', $language);
	 * // Set the config language_id
	 * $this->config->set('config_language_id', $languages[$code]['language_id']);
	 */
	private function switchLanguage($language_id) {
		$code = $this->languages[$language_id]['code'];
		
		$this->session->data['language'] = $code;
		setcookie('language', $code, time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
		
		// Overwrite the default language object
		$language = new Language($code);
		$language->load($code);

		$this->registry->set('language', $language);

		// Set the config language_id
		$this->config->set('config_language_id', $language_id);		
	}
	
	
	private function detectEquals($rows) {
		$equals_detect = [];
		
		foreach ($rows as $row) {
			if (in_array($row['keyword'], $equals_detect)) {
				$this->has_equals = true;
				return true;
			}
			
			$equals_detect[] = $row['keyword'];
		}
		
		return false;
	}
		
	
	private function buildURLForEssence($primary_key, $essence_id, $path = '') {
		$new_url = false;

		if ('category_id' == $primary_key) {
			//$this->url->link('product/category', 'path=' . $this->request->get['path'])
			// $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id'] . $url)
			$new_url = $this->url->link('product/category', 'path=' . (($path) ? $path . '_' : '') . $essence_id);
		}

		if ('product_id' == $primary_key) {
			// $this->url->link('product/product', 'path=' . $this->request->get['path'] . '&product_id=' . $result['product_id'] . $url)
			
			if ($path) {
				$new_url = $this->url->link('product/product', 'path=' . $path . '&product_id=' . $essence_id);
			} else {
				$new_url = $this->url->link('product/product', 'product_id=' . $essence_id);
			}
		}

		if ('manufacturer_id' == $primary_key) {
			$new_url = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $essence_id);
		}

		if ('information_id' == $primary_key) {
			$new_url = $this->url->link('information/information', 'information_id=' . $essence_id);
		}
		
		// ocStore only . begin
		// SEO URL not work properly for blog in ocStore 3.0.2.0
		 if ('blog_category_id' == $primary_key) {
		 	$new_url = $this->url->link('blog/category', 'blog_category_id=' . $essence_id);
		 }
		 
		 if ('article_id' == $primary_key) {
		 	$new_url = $this->url->link('blog/article', 'article_id=' . $essence_id);
		 }
		// ocStore only . end

		return $new_url;
	}
	
	
	private function getPath($parts2, $essence_to_keys) {
		array_pop($parts2);
		
		$path = '';
		
		$i = 0;
		foreach ($parts2 as $part) {
			if (!isset($essence_to_keys[$part])) {
				continue;
			}
			
			$path .= ($i) ? '_' : '';			
			$res = explode('=', $essence_to_keys[$part]);			
			$path .= $res[1];
			
			$i++;
		}

		return $path;
	}
	
	
}