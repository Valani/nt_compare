<?php

/**
 * @category   OpenCart
 * @package    SEO URL Generator PRO
 * @copyright  © Serge Tkach, 2018–2024, http://sergetkach.com/
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// for different PHP versions...
if (version_compare(PHP_VERSION, '7.2') >= 0) {
	$php_v = '72_73';
} elseif (version_compare(PHP_VERSION, '7.1') >= 0) {
	$php_v = '71';
} elseif (version_compare(PHP_VERSION, '5.6.0') >= 0) {
	$php_v = '56_70';
} elseif (version_compare(PHP_VERSION, '5.4.0') >= 0) {
	$php_v = '54_56';
} else {
	echo "Sorry! Version for PHP 5.3 Not Supported!<br>Please contact to author!";
	exit;
}

$file = DIR_SYSTEM . 'library/seo_url_generator/seo_url_generator_' . $php_v . '.php';

if (is_file($file)) {
	include $file;
} else {
	echo "No file '$file'<br>";
	exit;
}

class ControllerExtensionModuleSeoURLGenerator extends Controller {
	private $code = 'seo_url_generator';
	private $errors = array();
	private $stde;
	private $stdelog;

	function __construct($registry) {
		parent::__construct($registry);
		
		$this->extension = new SeoUrlGenerator();

    // StdE Require
		// $this->load->library('stde'); autoload
		$this->stde = new StdE($registry);
		$this->registry->set('stde', $this->stde);
		$this->stde->setCode($this->code);
		$this->stde->setType('extension_monolithic');

    // StdeLog require
		$this->stdelog = new StdeLog($this->code);
		$this->registry->set('stdelog', $this->stdelog);
		$this->stdelog->setDebug($this->config->get('module_' . $this->code . '_debug'));
	}

	public function install() {
		// -----------------------------------------------------------
		// Права на модуль
		// -----------------------------------------------------------
		
		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/' . $this->code);
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/' . $this->code);

		
		// -----------------------------------------------------------
		// Создание страницы для хранение редиректов
		// -----------------------------------------------------------		
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "seo_url_generator_redirects` (
			`seo_url_id` int(11) NOT NULL AUTO_INCREMENT,
			`store_id` int(11) NOT NULL,
			`language_id` int(11) NOT NULL,
      `seo_url_old` varchar(255) NOT NULL,
      `seo_url_actual` varchar(255) NOT NULL,
      `query` varchar(100) NOT NULL,
      PRIMARY KEY (`seo_url_id`),
      KEY `seo_url_old` (`seo_url_old`),
      KEY `seo_url_actual` (`seo_url_actual`),
      KEY `query` (`query`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$this->db->query($sql);
		
		
		// -----------------------------------------------------------
		// Автоматическое включение ЧПУ при наличии такой возможности
		// -----------------------------------------------------------

		// Автоматически включим человеку SEO URL :)
		$config_seo_url = $this->config->get('config_seo_url');

		if (!$config_seo_url) {
			$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '1' WHERE `key` = 'config_seo_url'");
		}

		// Включаем SEO PRO, если такой присутствует
		$config_seo_pro = $this->config->get('config_seo_pro');

		if (!$config_seo_pro) {
			$this->db->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '1' WHERE `key` = 'config_seo_pro'");
		}

		// Автоматически включим .htaccess, если есть права доступа к корню сайта
		$root_dir = str_replace('system/', '', DIR_SYSTEM);

		if (!is_file($root_dir . '.htaccess')) {
			if (is_file($root_dir . 'htaccess.txt')) {
				// ocStore
				@rename($root_dir . 'htaccess.txt', $root_dir . '.htaccess');
			} elseif (is_file($root_dir . '.htaccess.txt')) {
				// OpenCart.PRO & OpenCart pure
				@rename($root_dir . '.htaccess.txt', $root_dir . '.htaccess');
			} else {
				// if file wasn't uploaded on hosting
				if (is_file(DIR_SYSTEM . 'library/seo_url_generator/dist/.htaccess')) {
					$content = file_get_contents(DIR_SYSTEM . 'library/seo_url_generator/dist/.htaccess');
					@file_put_contents($root_dir . '.htaccess', $content, FILE_APPEND | LOCK_EX);
				}
			}
		}
		
		$this->statistics();
	}

	public function uninstall() {
		$this->load->model('user/user_group');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/' . $this->code);
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/' . $this->code);

		// Вроде как все чистится автоматом при деинсталляции
		// $this->load->model('setting/setting');
		// $this->model_setting_setting->editSetting($this->code, '');
	}
	
	private function statistics() {
		$url = 'https://api.sergetkach.com/notify/';

		$post = array(
			'domain' => HTTPS_CATALOG,
			'extension' => 'seo_url_generator_pro',
			'oc_version' => '3',
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Без цього воно видасть сторінку, на яку був відправлений запит
		curl_setopt($ch, CURLOPT_TIMEOUT, 6);

		curl_exec($ch);
		
		curl_close($ch);
	}




	/*
	------------------------------------------------------------------------------
	PARTS
	------------------------------------------------------------------------------
	*/

	/* Part Settings */
	public function index() {
		$this->load->language('extension/module/' . $this->code);
		$this->load->model('extension/module/' . $this->code);
		$this->load->model('setting/setting');

		$data['valid_licence'] = $this->extension->isValidLicence($this->config->get('module_seo_url_generator_licence'));

		// Save
		$data['text_success'] = '';

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateSettings()) {
			$data['text_success'] = $this->language->get('text_success'); // no redirects if success			
			
			# Magic for extension code in fields . Begin
			
			$pseudo_post = array();
			
			$post_temp = $this->request->post; // stores setting
			unset($post_temp['store_setting']); // stores setting
			
			// For common setting
			foreach ($post_temp as $key=> $value) {
				if (false === strpos($key, 'module_' . $this->code . '_')) {
					$pseudo_post['module_' . $this->code . '_' . $key] = $value;
				} else {
					$pseudo_post[$key] = $value; // no sense but...
				}
			}
			
			unset($post_temp); // stores setting
			
			// For each store setting
			$store_setting = array();
						
			foreach ($this->request->post['store_setting'] as $store_id => $store_item) {
				foreach ($store_item as $field => $value) {
					if (false === strpos($field, 'module_' . $this->code . '_')) {
						$store_setting[$store_id]['module_' . $this->code . '_' . $field] = $value;
					} else {
						$store_setting[$store_id][$field] = $value; // non sense but...
					}
				}
			}			
			
			# Magic for extension code in fields . End
			
			// Save setting for each store separately
			foreach ($store_setting as $store_id => $store_item) {
				if (0 == $store_id) {
					$store_item = array_merge($pseudo_post, $store_item);
				}
				
				$this->model_setting_setting->editSetting('module_' . $this->code, $store_item, $store_id);
			}
		}		

		// Errors
		if (isset($this->errors)) {
			$data['errors'] = $this->errors;
		}

		// Breadcrumbps & Links
		$data['breadcrumbs'] = $this->stde->breadcrumbs();

		$data['action'] = $this->stde->link('action');
		$data['cancel'] = $this->stde->link('cancel');

		$data['link_part_settings'] = $this->stde->link('index'); // A!
		$data['link_part_generate'] = $this->stde->link('part_generate');
		$data['link_part_edit_seo_url'] = $this->stde->link('part_edit_seo_url');
		$data['link_part_redirects'] = $this->stde->link('part_redirects');

		// Text
		$this->document->setTitle($this->language->get('heading_title'));

		// Data
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax
		
		$data['debug_levels'] = array(
			0=>$this->language->get('debug_0'),
			1=>$this->language->get('debug_1'),
			2=>$this->language->get('debug_2'),
			3=>$this->language->get('debug_3'),
			4=>$this->language->get('debug_4')
		);
		
		$data['translit_functions'] = $this->model_extension_module_seo_url_generator->getFunctionsList();
		
		$data['delimiter_chars'] = array(
			'hyphen'=>$this->language->get('char_hyphen'),
			'underscore'=>$this->language->get('char_underscore')
		);
		
		$data['change_delimiter_chars'] = array(
			'donot'=>$this->language->get('change_donot'),
			'underscore_to_hyphen'=>$this->language->get('change_underscore_to_hyphen'),
			'hyphen_to_underscore'=>$this->language->get('change_hyphen_to_underscore')
		);

		$data['licence'] = $this->stde->field('licence');
		
		$data['status'] = $this->stde->field('status');
		
		$data['debug'] = $this->stde->field('debug');		

		$data['limits'] = $this->model_extension_module_seo_url_generator->getLimits();
		
		$data['limit'] = $this->stde->field('limit');
		
		$this->load->model('setting/store');
		
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->stde->languages($this->model_localisation_language->getLanguages()); // for icons universal
		
		$data['config_language_code'] = $this->config->get('config_language'); // ??
		
		# Setting for stores
		
		$data['store_setting'] = array();
		
		if (isset($this->request->post['store_setting'])) {
			// POST
			
			$data['store_setting'] = $this->request->post['store_setting'];			

			// Chars array . Begin
			foreach ($data['stores'] as $store) {
				$custom_replace_from = $this->request->post['store_setting'][$store['store_id']]['custom_replace_from'];
				$custom_replace_to = $this->request->post['store_setting'][$store['store_id']]['custom_replace_to'];
				
				foreach ($data['languages'] as $language) {
					$custom_replace_from[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_from[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_from_array'][$language['language_id']] = explode('<br>', $custom_replace_from[$language['language_id']]);

					$custom_replace_to[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_to[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_to_array'][$language['language_id']] = explode('<br>', $custom_replace_to[$language['language_id']]);
				}
			}
			// Chars array . End
			
		} else {
			// SAVED DATA
			
			foreach ($data['languages'] as $language) {
				$tmp_languages[$language['language_id']] = '';
			}

			$this->stde->languages4Setting($tmp_languages);

			foreach ($data['stores'] as $store) {
				$store_setting = $this->model_setting_setting->getSetting('module_' . $this->code, $store['store_id']);

				$this->stde->settingSet($store_setting);

				$data['store_setting'][$store['store_id']]['language'] = $this->stde->fieldSetting('language');
				$data['store_setting'][$store['store_id']]['translit_function'] = $this->stde->fieldSetting('translit_function');
				$data['store_setting'][$store['store_id']]['delimiter_char'] = $this->stde->fieldSetting('delimiter_char');
				$data['store_setting'][$store['store_id']]['change_delimiter_char'] = $this->stde->fieldSetting('change_delimiter_char');

				// Chars array . Begin
				$custom_replace_from = $this->stde->fieldSetting('custom_replace_from');
				$custom_replace_to = $this->stde->fieldSetting('custom_replace_to');

				foreach ($data['languages'] as $language) {
					$custom_replace_from[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_from[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_from_array'][$language['language_id']] = explode('<br>', $custom_replace_from[$language['language_id']]);

					$custom_replace_to[$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $custom_replace_to[$language['language_id']]);
					$data['store_setting'][$store['store_id']]['custom_replace_to_array'][$language['language_id']] = explode('<br>', $custom_replace_to[$language['language_id']]);
				}
				// Chars array . End

				$data['store_setting'][$store['store_id']]['category_formula'] = $this->stde->fieldSetting('category_formula', '[category_name]');
				$data['store_setting'][$store['store_id']]['product_formula'] = $this->stde->fieldSetting('product_formula', '[product_name]');
				$data['store_setting'][$store['store_id']]['manufacturer_formula'] = $this->stde->fieldSetting('manufacturer_formula', '[manufacturer_name]');
				$data['store_setting'][$store['store_id']]['information_formula'] = $this->stde->fieldSetting('information_formula', '[information_title]');
			}
			
		}

		// Common
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		// Output
		$this->response->setOutput($this->load->view('extension/module/' . $this->code, $data));
	}

	/*
	 * Validate Settings
	 *
	 * A!
	 * Not need follow extension_code in post-fields thanking StdE Library & Magic :)
	 */
	protected function validateSettings() {
		if (!$this->user->hasPermission('modify', 'extension/module/' . $this->code)) {
			$this->errors['warning'] = $this->language->get('error_permission');
		}
		
		// A! Fields of $this->request->post is without extension prefix if follow StdE Library (!)
		// Licence
		if (isset($this->request->post['licence']) && !empty($this->request->post['licence'])) {
			if (!$this->extension->isValidLicence($this->request->post['licence'])) {
				$this->errors['licence'] = $this->language->get('error_licence_not_valid');

				$this->request->post['licence'] = '';
			}
		} else {
			// Такое возможно только, если человек попытается отобразить #module-work-area, не введя лицензию
			$this->errors['licence'] = $this->language->get('error_licence_empty');
		}
		
		$this->load->model('localisation/language');

		$data['languages'] = $this->stde->languages($this->model_localisation_language->getLanguages());
	
		
		### SETTING BY STORE ID . BEGIN
		foreach ($this->request->post['store_setting'] as $store_id => $store_item) {
			foreach ($data['languages'] as $language) {
				
				// Custom Replace
				$custom_replace_from_array = array();

				if ($store_item['custom_replace_from'][$language['language_id']]) {
					$store_item['custom_replace_from'][$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $store_item['custom_replace_from'][$language['language_id']]);

					$custom_replace_from_array = explode('<br>', $store_item['custom_replace_from'][$language['language_id']]);

					// Remove empty values from $this->request->post['custom_replace_from_array']
					foreach ($custom_replace_from_array as $key=> $value) {
						$value0 = trim($value);
						if (!$value0) {
							unset($custom_replace_from_array[$key]);
						}
					}

					$this->request->post['store_setting'][$store_id]['custom_replace_from'][$language['language_id']] = implode("\r\n", $custom_replace_from_array); // Need POST
				}

				$custom_replace_to_array = array();
				
				if ($store_item['custom_replace_to'][$language['language_id']]) {
					$store_item['custom_replace_to'][$language['language_id']] = str_replace(array("\r\n", "\n"), '<br>', $store_item['custom_replace_to'][$language['language_id']]);

					$custom_replace_to_array = explode('<br>', $store_item['custom_replace_to'][$language['language_id']]);

					// Remove empty values from $store_item['custom_replace_to']
					foreach ($custom_replace_to_array as $key=> $value) {
						$value0 = trim($value);
//						if (!$value0) {
//							unset($custom_replace_to_array[$key]);
//						}
					}

					$this->request->post['store_setting'][$store_id]['custom_replace_to'][$language['language_id']] = implode("\r\n", $custom_replace_to_array); // Need POST
				}				

				// Check Up Custom Replace
				if (count($custom_replace_from_array) > 0) {
					
					// If no values at the right column
					if (count($custom_replace_to_array) < 1) {
							$this->request->post['store_setting'][$store_id]['custom_replace_to'][$language['language_id']] = 'underscore' ==	$this->request->post['store_setting'][$store_id]['delimiter_char'][$language['language_id']] ? '_' : '-';
					}

					// Corelation between chars from the left and from the right
					if (count($custom_replace_from_array) != count($custom_replace_to_array) & count($custom_replace_to_array) > 1) {
						$this->errors[$store_id]['custom_replace_to'][$language['language_id']] = $this->language->get('error_custom_replace_to_not_1_char');
					}
					
				}
				
				// Category Formula
				$tmp['category_formula'] = $this->request->post['store_setting'][$store_id]['category_formula'][$language['language_id']] = trim($store_item['category_formula'][$language['language_id']]);
				
				if (empty($tmp['category_formula'])) {
					$this->errors[$store_id]['category_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['category_formula'], '[category_name]') && false === strstr($tmp['category_formula'], '[category_id]')) {
						$this->errors[$store_id]['category_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[category_name]',
							'[category_id]'
							), array(
							'',
							''
							), $tmp['category_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+/", $str_without_vars)) {
								$this->errors[$store_id]['category_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}
				
				// Product Formula
				$tmp['product_formula'] = $this->request->post['store_setting'][$store_id]['product_formula'][$language['language_id']] = trim($store_item['product_formula'][$language['language_id']]);

				if (empty($tmp['product_formula'])) {
					$this->errors[$store_id]['product_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['product_formula'], '[product_name]') && false === strstr($tmp['product_formula'], '[product_id]') && false === strstr($tmp['product_formula'], '[model]') && false === strstr($tmp['product_formula'], '[sku]')) {

						$this->errors[$store_id]['product_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[product_name]',
							'[product_id]',
							'[model]',
							'[sku]',
							'[manufacturer_name]'
							), array(
							'',
							'',
							'',
							''
							), $tmp['product_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+/", $str_without_vars)) {
								$this->errors[$store_id]['product_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}				
				
				// Manufacturer Formula
				$tmp['manufacturer_formula'] = $this->request->post['store_setting'][$store_id]['manufacturer_formula'][$language['language_id']] = trim($store_item['manufacturer_formula'][$language['language_id']]);

				if (empty($tmp['manufacturer_formula'])) {
					$this->errors[$store_id]['manufacturer_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['manufacturer_formula'], '[manufacturer_name]') && false === strstr($tmp['manufacturer_formula'], '[manufacturer_id]')) {
						$this->errors[$store_id]['manufacturer_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[manufacturer_name]',
							'[manufacturer_id]'
							), array(
							'',
							''
							), $tmp['manufacturer_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+/", $str_without_vars)) {
								$this->errors[$store_id]['manufacturer_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}
				
				// Information Formula
				$tmp['information_formula'] = $this->request->post['store_setting'][$store_id]['information_formula'][$language['language_id']] = trim($store_item['information_formula'][$language['language_id']]);

				if (empty($tmp['information_formula'])) {
					$this->errors[$store_id]['information_formula'][$language['language_id']] = $this->language->get('error_formula_empty');
				} else {
					// need be at least 1 variable
					if (false === strstr($tmp['information_formula'], '[information_title]') && false === strstr($tmp['information_formula'], '[information_id]')) {
						$this->errors[$store_id]['information_formula'][$language['language_id']] = $this->language->get('error_formula_less_vars');
					} else {
						$str_without_vars = str_replace(array(
							'[information_title]',
							'[information_id]'
							), array(
							'',
							''
							), $tmp['information_formula']);

						if (!empty($str_without_vars)) {
							if (!preg_match("/[a-bA-Z\-_]+/", $str_without_vars)) {
								$this->errors[$store_id]['information_formula'][$language['language_id']] = $this->language->get('error_formula_pattern');
							}
						}
					}
				}				
				
			}
		}
		### SETTING BY STORE ID . END		



		// If is any errors : common warning
		if ($this->errors && !isset($this->errors['warning'])) {
			$this->errors['warning'] = $this->language->get('error_warning');
		}

		return !$this->errors;
	}


	/* Part Generate */

	public function part_generate() {
		$this->load->language('extension/module/' . $this->code);
		$this->load->model('setting/setting');
		$this->load->model('extension/module/' . $this->code);

		// Errors
		$data['error_warning'] = '';

		$data['errors'] = array();

		$data['errors']['error_ajax_response'] = $this->language->get('error_ajax_response');

		// It is enough to check settings for default store
		if (!$this->config->get('module_seo_url_generator_category_formula')) {
			$data['errors']['error_formulas_none'] = $this->language->get('error_formulas_none');
			$this->stdelog->write(1, 'part_generate() : error_formulas_none for category');
		}

		if (!$this->config->get('module_seo_url_generator_product_formula')) {
			$data['errors']['error_formulas_none'] = $this->language->get('error_formulas_none');
			$this->stdelog->write(1, 'part_generate() : error_formulas_none for product');
		}

		if (!$this->config->get('module_seo_url_generator_manufacturer_formula')) {
			$data['errors']['error_formulas_none'] = $this->language->get('error_formulas_none');
			$this->stdelog->write(1, 'part_generate() : error_formulas_none for manufacturer');
		}

		if (!$this->config->get('module_seo_url_generator_information_formula')) {
			$data['errors']['error_formulas_none'] = $this->language->get('error_formulas_none');
			$this->stdelog->write(1, 'part_generate() : error_formulas_none for information');
		}

		// Text
		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_author'] = $this->language->get('text_author');
		$data['text_author_support'] = $this->language->get('text_author_support');

		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['text_part_settings'] = $this->language->get('text_part_settings');
		$data['text_part_generate'] = $this->language->get('text_part_generate');
		$data['text_part_edit'] = $this->language->get('text_part_edit');
		$data['text_part_redirects'] = $this->language->get('text_part_redirects');

		$data['entry_licence'] = $this->language->get('entry_licence'); // Q??

		$data['tab_product'] = $this->language->get('tab_product');
		$data['tab_category'] = $this->language->get('tab_category');
		$data['tab_manufacturer'] = $this->language->get('tab_manufacturer');
		$data['tab_information'] = $this->language->get('tab_information');

		$data['text_answer_place'] = $this->language->get('text_answer_place');
		$data['text_answer_processing'] = $this->language->get('text_answer_processing');

		$data['button_generate_seo_url_product_empty'] = $this->language->get('button_generate_seo_url_product_empty');
		$data['button_generate_seo_url_product_replace'] = $this->language->get('button_generate_seo_url_product_replace');
		$data['button_generate_seo_url_category_empty'] = $this->language->get('button_generate_seo_url_category_empty');
		$data['button_generate_seo_url_category_replace'] = $this->language->get('button_generate_seo_url_category_replace');
		$data['button_generate_seo_url_manufacturer_empty'] = $this->language->get('button_generate_seo_url_manufacturer_empty');
		$data['button_generate_seo_url_manufacturer_replace'] = $this->language->get('button_generate_seo_url_manufacturer_replace');
		$data['button_generate_seo_url_information_empty'] = $this->language->get('button_generate_seo_url_information_empty');
		$data['button_generate_seo_url_information_replace'] = $this->language->get('button_generate_seo_url_information_replace');
		$data['button_generate_seo_url_custom_empty'] = $this->language->get('button_generate_seo_url_custom_empty');
		$data['button_generate_seo_url_custom_replace'] = $this->language->get('button_generate_seo_url_custom_replace');
		
		$data['success_mass_generate_url'] = $this->language->get('success_mass_generate_url');

		// Breadcrumbs
		$data['breadcrumbs'] = $this->stde->breadcrumbs();

		$data['cancel'] = $this->stde->link('cancel');

		$data['link_part_settings'] = $this->stde->link('index'); // A!
		$data['link_part_generate'] = $this->stde->link('part_generate');
		$data['link_part_edit_seo_url'] = $this->stde->link('part_edit_seo_url');
		$data['link_part_redirects'] = $this->stde->link('part_redirects');

		// Data
		$data['user_token'] = $this->session->data['user_token']; // user_token need in js ajax

		$data['custom_tabs'] = $this->model_extension_module_seo_url_generator->getCustomTabs();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->stde->view('extension/module/' . $this->code . '_generate', $data));
	}


	/* Part edit SEO URL */

	public function part_edit_seo_url() {
		return false;
	}


	/* Part Redirects */

	public function part_redirects() {
		return false;
	}




	/*
	------------------------------------------------------------------------------
	Generation by AJAX
	------------------------------------------------------------------------------
	*/

	/*
	 * For all SEO URL - on add
	 */

	public function generateSeoUrlByAjax4All() {
		$this->stdelog->write(2, $this->request->post, 'generateSeoUrlByAjax4All() is called with $this->request->post');

		$json = ['result' => ''];
		
		if (!isset($this->request->post)) {
			return false;
		}

		### SETTING
		$a_data['setting'] = array();

		$this->load->model('setting/store');
		
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('setting/setting');
		
		$data['store_setting'] = array();
		
		foreach ($data['stores'] as $store) {
			$store_setting = $this->model_setting_setting->getSetting('module_' . $this->code, $store['store_id']);

			$this->stdelog->write(4, $store_setting, 'generateSeoUrlByAjax4All() : $store_setting for $store["store_id"] = ' . $store['store_id']);

			if (in_array($this->request->post['essence'], array('category', 'product', 'manufacturer', 'information'))) {
				$data['store_setting'][$store['store_id']]['formula'] = $store_setting['module_seo_url_generator_' . $this->request->post['essence'] . '_formula'];
			} else {
				$this->stdelog->write(2, 'generateSeoUrlByAjax4All() : is not system essence');

				foreach ($data['languages'] as $language) {
					$data['store_setting'][$store['store_id']]['formula'][$language['language_id']] = '';
				}				
			}
	
			$data['store_setting'][$store['store_id']]['language']             = $store_setting['module_seo_url_generator_language'];
			$data['store_setting'][$store['store_id']]['translit_function']     = $store_setting['module_seo_url_generator_translit_function'];
			$data['store_setting'][$store['store_id']]['delimiter_char']        = $store_setting['module_seo_url_generator_delimiter_char'];	
			$data['store_setting'][$store['store_id']]['change_delimiter_char'] = $store_setting['module_seo_url_generator_change_delimiter_char'];
			$data['store_setting'][$store['store_id']]['custom_replace_from']   = $store_setting['module_seo_url_generator_custom_replace_from'];			
			$data['store_setting'][$store['store_id']]['custom_replace_to']     = $store_setting['module_seo_url_generator_custom_replace_to'];
		}

		$this->stdelog->write(4, $data["store_setting"], 'generateSeoUrlByAjax4All() : ALL $data["store_setting"]');

		$keywords = array();

		foreach ($data['stores'] as $store) {
			foreach ($data['languages'] as $language) {
				
				if (strpos($data['store_setting'][$store['store_id']]['formula'][$language['language_id']], $this->request->post['primary_key']) && !$this->request->post['essence_id']) {
					$this->stdelog->write(1, 'generateSeoUrlByAjax4All() : formule contain $this->request->post["primary_key"]-value! But we still haven"t essence_id in reallity');

					$keywords[$store['store_id']][$language['language_id']] = '';

					continue;
				}

				$a_data = array();

				$a_data['name']						 = $this->request->post['a_lang_data']['name'][$language['language_id']];
				$a_data['primary_key']		 = $this->request->post['primary_key'];
				$a_data['essence']				 = $this->request->post['essence'];
				$a_data['essence_id']			 = $this->request->post['essence_id'];
				$a_data['store_id']				 = $store['store_id'];
				$a_data['url_language_id'] = $language['language_id'];

				if (isset($this->request->post['model'])) $a_data['model'] = $this->request->post['model'];
				if (isset($this->request->post['manufacturer_id'])) $a_data['manufacturer_id'] = $this->request->post['manufacturer_id'];
				if (isset($this->request->post['sku'])) $a_data['sku'] = $this->request->post['sku'];

				$a_data['setting'] = array(
					'language'             => $data['store_setting'][$store['store_id']]['language'][$language['language_id']],
					'translit_function'     => $data['store_setting'][$store['store_id']]['translit_function'][$language['language_id']],
					'formula'               => $data['store_setting'][$store['store_id']]['formula'][$language['language_id']],
					'delimiter_char'        => $data['store_setting'][$store['store_id']]['delimiter_char'][$language['language_id']],
					'change_delimiter_char' => $data['store_setting'][$store['store_id']]['change_delimiter_char'][$language['language_id']],
					'custom_replace_from'   => $data['store_setting'][$store['store_id']]['custom_replace_from'][$language['language_id']],
					'custom_replace_to'     => $data['store_setting'][$store['store_id']]['custom_replace_to'][$language['language_id']],
				);

				$keywords[$store['store_id']][$language['language_id']] = $this->generateSeoUrl($a_data);
			}
		}

		if (is_array($keywords)) {
			$json['result'] = $keywords;
		} else {
			$json['result'] = 'error';
		}

		$this->stdelog->write(2, $json, 'generateSeoUrlByAjax4All() : $json defore json_encode($json)');

		header('Content-type:application/json;charset=utf-8');
		echo json_encode($json);
		exit;
	}


	/*
	 * For one SEO URL - on edit
	 */

	public function generateSeoUrlByAjax4One() {
		$this->stdelog->write(2, $this->request->post, 'generateSeoUrlByAjax4One() is called by AJAX request with $this->request->post');

		$json = ['result' => ''];
		
		if (!isset($this->request->post)) {
			return false;
		}

		### SETTING
		$setting = array();

		$this->load->model('setting/setting');
		
		$store_setting = $this->model_setting_setting->getSetting('module_seo_url_generator', $this->request->post['store_id']);

		//$this->stdelog->write(4, $store_setting, 'generateSeoUrlByAjax4One(): ALL $store_setting for store_id = ' . $this->request->post['store_id']);

		if (in_array($this->request->post['essence'], array('category', 'product', 'manufacturer', 'information'))) {
			$setting['formula'] = $store_setting['module_seo_url_generator_' . $this->request->post['essence'] . '_formula'];
		} else {
			$this->stdelog->write(2, 'generateSeoUrlByAjax4One() : is not system essence');

			$setting['formula'][$this->request->post['language_id']] = '';
		}

		$setting['language']              = $store_setting['module_seo_url_generator_language'];
		$setting['translit_function']     = $store_setting['module_seo_url_generator_translit_function'];
		$setting['delimiter_char']        = $store_setting['module_seo_url_generator_delimiter_char'];
		$setting['change_delimiter_char'] = $store_setting['module_seo_url_generator_change_delimiter_char'];
		$setting['custom_replace_from']   = $store_setting['module_seo_url_generator_custom_replace_from'];
		$setting['custom_replace_to']     = $store_setting['module_seo_url_generator_custom_replace_to'];

		//$this->stdelog->write(3, $setting, 'generateSeoUrlByAjax4One(): $setting are necessary for SEO URL generation');

		$keyword = false;
				
		if (strpos($setting['formula'][$this->request->post['language_id']], $this->request->post['primary_key']) && !$this->request->post['essence_id']) {
			$this->stdelog->write(1, 'generateSeoUrlByAjax4One() : formule contain $this->request->post["primary_key"]-value! But we still haven"t essence_id in reallity');

			// $keyword = false; // by default

		} else {
			$a_data = array();

			$a_data['name']						 = $this->request->post['name'];
			$a_data['primary_key']		 = $this->request->post['primary_key'];
			$a_data['essence']				 = $this->request->post['essence'];
			$a_data['essence_id']			 = $this->request->post['essence_id'];
			$a_data['store_id']				 = $this->request->post['store_id'];
			$a_data['url_language_id'] = $this->request->post['language_id'];

			if (isset($this->request->post['model'])) $a_data['model'] = $this->request->post['model'];
			if (isset($this->request->post['manufacturer_id'])) $a_data['manufacturer_id'] = $this->request->post['manufacturer_id'];
			if (isset($this->request->post['sku'])) $a_data['sku'] = $this->request->post['sku'];

			$a_data['setting'] = array(
					'language'             => $setting['language'][$this->request->post['language_id']],
					'translit_function'     => $setting['translit_function'][$this->request->post['language_id']],
					'formula'               => $setting['formula'][$this->request->post['language_id']],
					'delimiter_char'        => $setting['delimiter_char'][$this->request->post['language_id']],
					'change_delimiter_char' => $setting['change_delimiter_char'][$this->request->post['language_id']],
					'custom_replace_from'   => $setting['custom_replace_from'][$this->request->post['language_id']],
					'custom_replace_to'     => $setting['custom_replace_to'][$this->request->post['language_id']],
				);
			
			$keyword = $this->generateSeoUrl($a_data);
		}

		if ($keyword) {
			$json['result'] = $keyword;
		} else {
			$json['result'] = 'error';
		}

		//$this->stdelog->write(4, $json, 'generateSeoUrlByAjax4One() : $json defore json_encode($json)');

		header('Content-type:application/json;charset=utf-8');
		echo json_encode($json);
		exit;
	}


	/*
	 * Base method
	 * Это у нас основой метод, который генерирует SEO URL
	 *
	 * Ему все равно, откуда поступают данные о товаре - из формы в админке или из базы при массовом редактировании
	 *
	 * Определить сущность
	 * Определить, какие переменные есть в формуле
	 * Вырезать из формулы лишние - (транслит сам это сделает)
	 * Транлитировать
	 * Запросить уникальность
	 * Если URL не уникален, то использовать индекс N - причем, это не зависит от того, есть ли в формуле генерации доп переменные или нет
	 */

	public function generateSeoUrl($a_data) {
		$this->stdelog->write(2, $a_data, 'generateSeoUrl() is called with $a_data');
		
		$this->load->model('extension/module/' . $this->code);
		
		// Setting
		// Нельзя, чтобы запрос настроек шел при каждой иттерации
		// В 3-ке надо, чтобы в данные этого метода передавались и настройки
		
		$name = $this->model_extension_module_seo_url_generator->essenceNameFilter($a_data['name'], $a_data['essence'], $a_data['setting']);
		
		$this->stdelog->write(4, $name, 'generateSeoUrl() : $name after $model->essenceNameFilter()');

		$keyword = '';
		
		if (isset($a_data['essence']) && $a_data['essence']) {
			if ('category' == $a_data['essence']) {			
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data);
			} elseif ('product' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateProductKeyword($a_data);
			} elseif ('manufacturer' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data);
			} elseif ('information' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data);
			} else {
				$keyword = $this->model_extension_module_seo_url_generator->generateNotSystemsEssenceKeyword($a_data);
			}

			$this->stdelog->write(4, $keyword, 'generateSeoUrl() : BASE WORD');
		} else {
			$this->stdelog->write(1, 'generateSeoUrl() : $a_data["essence"] is empty');
		}

		$keyword = $this->model_extension_module_seo_url_generator->translit($keyword, $a_data['setting']);

		$this->stdelog->write(3, $keyword, 'generateSeoUrl() : $keyword after $model->translit()');

		// Make unique
		if (!$this->model_extension_module_seo_url_generator->isUnique($keyword, $a_data['essence'] . '_id', $a_data['essence_id'], $a_data['store_id'], $a_data['url_language_id'])) {
			$keyword = $this->model_extension_module_seo_url_generator->makeUniqueURL($keyword, $a_data['essence'] . '_id', $a_data['essence_id'], $a_data['store_id'], $a_data['url_language_id']);
			
			$this->stdelog->write(3, $keyword, 'generateSeoUrl() : $keyword was NOT unique. Then I get value from $model->makeUniqueURL()');
		}

		$this->stdelog->write(2, $keyword, 'generateSeoUrl() : return $keyword');
		
		return $keyword;
	}
	

	/*
	------------------------------------------------------------------------------
	Массовая генерация
	------------------------------------------------------------------------------
	*/

	public function actionMassGenerateURL() {
		// Узнаю кол-во итемов в базе сущности всего
		// Определяю лимит итемов за одно обращение (по умолчанию 200 товаров за 1 иттерацию через ajax)
		// Делаю выборку и в цикле присваиваю URL
		// Возращаю порядковый номер товара для начала следующей иттерации

		$this->stdelog->write(2, "\r\n\r\n\r\n\r\n\r\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\r\n\r\n"
			. "                                      * * *                                          \r\n\r\n"
			. "               ----- actionMassGenerateURL() : is called IN ADMIN ------             \r\n\r\n"
			. "                                      * * *                                          \r\n\r\n"
			. "= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\r\n\r\n");

		$this->load->language('extension/module/' . $this->code);
		$this->load->model('extension/module/' . $this->code);

		### DATA
		$json = array();
		
		$this->stdelog->write(3, $this->config->get('module_seo_url_generator_licence'), 'actionMassGenerateURL() : $this->config->get("module_seo_url_generator_licence")');
		
		if (!$this->extension->isValidLicence($this->config->get('module_seo_url_generator_licence'))) {
			$json['status'] = 'Error';
			$json['answer'] = $this->language->get('error_licence');

			goto block_finish;
		}		

		$this->stdelog->write(2, $this->request->post, 'actionMassGenerateURL() : $this->request->post');

		// A!
		// For customized modules $primary_key may be different with key in seo_url.query!!
		// So it is necessary to use $query_key also
		// For example NewsBlog!

		$step = $this->request->post['step'];
		$essence = $this->request->post['essence'];
		$primary_key = $essence . '_id'; // A! for defaults essences
		$query_key = $essence . '_id'; // A! dummy for default essences
		$generation_type = $this->request->post['generationType'];
		
		$this->stdelog->write(4, $primary_key, 'actionMassGenerateURL() : $primary_key');
		
		// For custom_tab
		if('false' !== $this->request->post['custom_tab']) { // (A!) Value was send with ajax
			$this->stdelog->write(2, 'actionMassGenerateURL() : It has custom_tab');

			$custom_tabs = $this->model_extension_module_seo_url_generator->getCustomTabs();
			$primary_key = $custom_tabs[$essence]['primary_key']; // A! for custom essences
			$query_key = $custom_tabs[$essence]['query_key']; // A!

			$this->stdelog->write(3, $custom_tabs, 'actionMassGenerateURL() : $custom_tabs');
		}
		
		### SETTING		
		$this->load->model('setting/store');
		
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('setting/setting');
		
		$data['store_setting'] = array();
		
		foreach ($data['stores'] as $store) {
			$store_setting = $this->model_setting_setting->getSetting('module_' . $this->code, $store['store_id']);

			if (in_array($this->request->post['essence'], array('category', 'product', 'manufacturer', 'information'))) {
				$data['store_setting'][$store['store_id']]['formula'] = $store_setting['module_seo_url_generator_' . $this->request->post['essence'] . '_formula'];
			} else {
				$this->stdelog->write(1, 'actionMassGenerateURL() : is not system essence');

				foreach ($data['languages'] as $language) {
					$data['store_setting'][$store['store_id']]['formula'][$language['language_id']] = false;
				}
			}

			$data['store_setting'][$store['store_id']]['language'] = $store_setting['module_seo_url_generator_language'];
			$data['store_setting'][$store['store_id']]['translit_function'] = $store_setting['module_seo_url_generator_translit_function'];
			$data['store_setting'][$store['store_id']]['delimiter_char'] = $store_setting['module_seo_url_generator_delimiter_char'];
			$data['store_setting'][$store['store_id']]['change_delimiter_char'] = $store_setting['module_seo_url_generator_change_delimiter_char'];
			$data['store_setting'][$store['store_id']]['custom_replace_from'] = $store_setting['module_seo_url_generator_custom_replace_from'];
			$data['store_setting'][$store['store_id']]['custom_replace_to'] = $store_setting['module_seo_url_generator_custom_replace_to'];
		}

		// limit
		$limit_n = $this->config->get('module_seo_url_generator_limit');

		// default 200
		if (!$limit_n) {
			$limit_n = 200;
		}
		
		### ACTION
		$n_essence_items = $this->model_extension_module_seo_url_generator->countEssenceItems($essence);

		if (false === $n_essence_items) {
			$this->stdelog->write(1, $n_essence_items, 'actionMassGenerateURL() : $n_essence_items is false - goto block_finish;');

			$json['status'] = 'Error';
			$json['answer'] = sprintf($this->language->get('answer_step_item_error'), $step, $steps_all) . $this->language->get('error_steps_no_count');
		
			goto block_finish;
		}

		$steps_all = ceil($n_essence_items / $limit_n);

		$limits = array(
			'first_element'=>$limit_n * $step - $limit_n,
			'limit_n'=>$limit_n
		);

		$this->stdelog->write(
			3, 
			"\r\n\$n_essence_items = $n_essence_items" . PHP_EOL 
				. "\$steps_all = $steps_all" . PHP_EOL 
				. "\$first_element = " . ($limit_n * $step - $limit_n) . PHP_EOL 
				. "\$limit_n = $limit_n" . PHP_EOL 
				. "------------------------------------------" . PHP_EOL,
			'actionMassGenerateURL() variables'
		);

		$essence_list = $this->model_extension_module_seo_url_generator->getEssenceList($essence, $limits);

		$this->stdelog->write(3, count($essence_list), 'actionMassGenerateURL() : quantity of items for foreach');

		if (count($essence_list) > 0) {

			foreach ($essence_list as $essence_item) {
				$this->stdelog->write(2, "\r\n\r\n\r\n\r\n                                      + + +                                         \r\n\r\n"
					. "------------------------------------------------------------------------------------\r\n\r\n"
					. "----- actionMassGenerateURL() : NEW ITTERATION ------\r\n\r\n"
					. "------------------------------------------------------------------------------------\r\n"
					);
				
				$essence_id = $essence_item[$primary_key];
				
				$this->stdelog->write(2, $essence_id, 'actionMassGenerateURL() : ' . $essence . ' with ' . $primary_key);
				
				$keywords_old_not_all_are_present = false;
				
				$keywords_old = $this->model_extension_module_seo_url_generator->getURLs($query_key, $essence_id);
				
				foreach ($data['stores'] as $store) {
					foreach ($data['languages'] as $language) {
						if (!isset($keywords_old[$store['store_id']][$language['language_id']])) {
							$keywords_old_not_all_are_present = true;
							$keywords_old[$store['store_id']][$language['language_id']] = false;
						}						
					}
				}
				
				$this->stdelog->write(3, $keywords_old, 'actionMassGenerateURL() : $keywords_old array with all keys');

				if ($keywords_old_not_all_are_present || 'replace' == $generation_type) {
					// Default
					$a_data = array(
						'essence'=>$essence,
						'essence_id'=>$essence_id
					);
					
					// Names with all languages
					$names = array();
					
					// $data['languages'] - it is neccessary for manufacturer dummy data
					$names = $this->model_extension_module_seo_url_generator->getEssenceNames($essence, $primary_key, $essence_id, $data['languages']);
					
					$this->stdelog->write(3, $names, 'actionMassGenerateURL() : $names');

					if (count($names) < 1) {
						$this->stdelog->write(1, $names, 'actionMassGenerateURL() : $names is not valid array');
						continue;
					}
					
					// $names content check below in foreach

					
					// Product
					if ('product' == $essence) {
						// It is necessary to get these data only once a essence itteration . Begin						
						$product_data = $this->model_extension_module_seo_url_generator->getProductData($essence_id);
						
						// if [manufacturer_name] is used in even one formula
						$use_manufacturer_name = false;
						
						foreach ($data['stores'] as $store) {
							foreach ($data['languages'] as $language) {
								if (false !== strstr($data['store_setting'][$store['store_id']]['formula'][$language['language_id']], '[manufacturer_name]')) {
									$use_manufacturer_name = true;
									break;
								}
							}
						}
						
						if ($use_manufacturer_name) {
							$product_data['manufacturer_name'] = $this->model_extension_module_seo_url_generator->getManufacturerNameById($product_data['manufacturer_id']);

							$this->stdelog->write(4, $product_data['manufacturer_name'], 'actionMassGenerateURL() : $use_manufacturer_name == true; $product_data["manufacturer_name"] value is');
						}
						
						$a_data = array_merge($a_data, $product_data);
						// It is necessary to get these data ony once a essence itteration . End
					}
					
					// Stores & Languages Itterations . Begin
					foreach ($data['stores'] as $store) {
						foreach ($data['languages'] as $language) {
							$keyword_old = $keywords_old[$store['store_id']][$language['language_id']];
							
							/*
							 * Дано
							 * $keyword_old = en_mac
							 * $keyword_new = mac
							 * 
							 * $keywords_old_not_all_are_present
							 * Что будет при сравнении с разделителями и актуализацией? Все равно будерт перезапись?..
							 * Сама актуализация нужна для редактирования товара вручную, а не при массовой генерации
							 * При массовой генерации человек может нажать осознанно на оранжевую кнопку!!!
							 */
							
							if ('replace' != $generation_type && $keyword_old) {
								$this->stdelog->write(2, $keyword_old, 'actionMassGenerateURL() : $keyword_old already exist for ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
								continue;
							}
							
							
							
							// $names content check
							// Check if all languages has name value
							// Q?
							// А если формула генерации не содержит названия? :)
							// TODO... (OMG...)
							
							if (!isset($names[$language['language_id']])) {
								$this->stdelog->write(1, 'actionMassGenerateURL() : ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] . ' doesn\'t have name value so it is impossible to generate SEO URL');
								// Write to common log also
								$this->log->write('ERROR -- SEO URL Generator: actionMassGenerateURL() :: ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] .   ' doesn\'t have name value so it is impossible to generate SEO URL');
								continue;
							}
								
							if (empty(trim($names[$language['language_id']]))) {
								$this->stdelog->write(1, 'actionMassGenerateURL() : ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']. ' has empty value of name so it is impossible to generate SEO URL');
								// Write to common log also
								$this->log->write('ERROR -- SEO URL Generator: actionMassGenerateURL() :: ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] . ' has empty value of name so it is impossible to generate SEO URL');
								continue;
							}
							
							
							
							$a_data['name']						 = $names[$data['store_setting'][$store['store_id']]['language'][$language['language_id']]];
							$a_data['store_id']				 = $store['store_id'];
							$a_data['url_language_id'] = $language['language_id'];
							
							$a_data['setting'] = array(
								'language'              =>$data['store_setting'][$store['store_id']]['language'][$language['language_id']],
								'translit_function'     =>$data['store_setting'][$store['store_id']]['translit_function'][$language['language_id']],
								'formula'               =>$data['store_setting'][$store['store_id']]['formula'][$language['language_id']],
								'delimiter_char'        =>$data['store_setting'][$store['store_id']]['delimiter_char'][$language['language_id']],
								'change_delimiter_char' =>$data['store_setting'][$store['store_id']]['change_delimiter_char'][$language['language_id']],
								'custom_replace_from'   =>$data['store_setting'][$store['store_id']]['custom_replace_from'][$language['language_id']],
								'custom_replace_to'     =>$data['store_setting'][$store['store_id']]['custom_replace_to'][$language['language_id']],
							);

							$keyword_new = $this->generateSeoUrl($a_data);
							
							if (!$keyword_new) {
								$this->stdelog->write(1, 'actionMassGenerateURL() : $keyword_new is not generated for ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
								continue;
							}		

							// Тут однозначно записываем новый ЧПУ в базу
							if (!$keyword_old) {
								$this->model_extension_module_seo_url_generator->setURL($query_key, $a_data['essence_id'], $keyword_new, $store['store_id'], $language['language_id']);
								$this->stdelog->write(2, $keyword_new, 'actionMassGenerateURL() : $keyword_new is added for ' . $primary_key . ' : ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
								continue;
							}													

							// Далее блок кода касается только случаев, когда 'replace' == $generation_type
							// Если пользователь осознанно уже выбрал replace, то актуализация и настройки разделителей вообще ни при чем!!
							if ($keyword_old != $keyword_new) {
								$this->model_extension_module_seo_url_generator->deleteURL($query_key, $essence_id, $store['store_id'], $language['language_id']);
								$this->model_extension_module_seo_url_generator->setURL($query_key, $a_data['essence_id'], $keyword_new, $store['store_id'], $language['language_id']);
								$this->model_extension_module_seo_url_generator->setRedirect($keyword_new, $keyword_old, $query_key, $a_data['essence_id'], $store['store_id'], $language['language_id']);
								
								$this->stdelog->write(2, 'actionMassGenerateURL() : write redirect for ' . $primary_key . ' : ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] . ' :: ' . $keyword_old . ' => ' . $keyword_new);
								
						} else {
								$this->stdelog->write(2, 'actionMassGenerateURL() : $keyword_new is the same as $keyword_old for ' . $primary_key . ' : ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
							continue;
							}
						}
					}
					// Stores & Languages Itterations . End
					
				} else {
					$this->stdelog->write(2, 'actionMassGenerateURL() : Nothing to do.');
				}
			}
		} else {
			$this->stdelog->write(1, 'actionMassGenerateURL() : count($essence_list) < 0');
		}

		
		if (!isset($json['status'])) {
			// success

			if ($step == $steps_all) {
				$json['status'] = 'Finish';
				
				// $json['answer'] = $this->language->get('success_item_step_finish'); - поставить финальную запись
				// Хотя, там на стороне JS сделано
				$json['answer'] = sprintf($this->language->get('answer_step_item_success'), $step, $steps_all);
				
				// is different for OC 3
				if ($this->config->get('config_seo_pro')) {
					$this->cache->delete('seopro');
				}
				
			} else {
				$json['status'] = 'Continue';
				$json['answer'] = sprintf($this->language->get('answer_step_item_success'), $step, $steps_all);
			}

		} else {
			// Error - Processing (Are there any expected errors????)
			$json['answer'] = sprintf($this->language->get('error_item_step'), $step, $steps_all);
			
			$this->stdelog->write(1, 'actionMassGenerateURL() : error marker is present');
		}		

		block_finish:

		$json['step'] = $step ++;
		$json['steps_all'] = $steps_all;

		$this->stdelog->write(3, $json, 'actionMassGenerateURL() : $json before json_encode()');

		$this->response->addHeader('Content-type: application/json; charset=UTF-8');
		$this->response->setOutput(json_encode($json));
	}




	/*
	------------------------------------------------------------------------------
	Licence
	------------------------------------------------------------------------------
	*/

	public function saveLicence() {
		$this->stdelog->write(2, 'saveLicence() is called');

		$this->load->model('setting/setting');

		$this->load->language('extension/module/' . $this->code);

		$json = array();

		$licence = trim($this->request->post['licence']);

		if ($this->extension->isValidLicence($licence)) {
			$this->model_setting_setting->editSetting('module_' . $this->code, array(
				'module_' . $this->code . '_licence'=>$licence
			));
			$json['success'] = $this->language->get('success_licence');
		} else {
			$json['error'] = $this->language->get('error_licence');
		}

		header("Content-type: application/json; charset=UTF-8");
		echo json_encode($json);
		exit();
	}

}
