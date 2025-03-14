<?php

/**
 * @category   OpenCart
 * @package    Handy Product Manager
 * @copyright  © Serge Tkach, 2018–2024, https://sergetkach.com/
 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('HANDY_VERSION', '1.17.5');

/* For OC 3
 * Helper for update
 * Not works if it is placed in the end of file.
  --------------------------------------------------------------------------- */
function rmRec($path) {
	if (is_file($path))
		return unlink($path);
	if (is_dir($path)) {
		foreach (scandir($path) as $p)
			if (($p != '.') && ($p != '..'))
				rmRec($path . DIRECTORY_SEPARATOR . $p);
		return rmdir($path);
	}
	return false;
}

class ControllerExtensionModuleHandy extends Controller {
	private $error = [];
	private $model; // to not have too much diffs between version 2.1 - 2.3 & 3.x
	private $handy;
	private $code = 'handy';
	private $stdelog;
	private $collation;

	public function __construct($registry) {
		parent::__construct($registry);

		// StdeLog require
		$this->stdelog = new StdeLog($this->code);
		$this->registry->set('stdelog', $this->stdelog);
		$this->stdelog->setDebug($this->config->get('module_' . $this->code . '_debug'));

		// Different PHP versions
		if (version_compare(PHP_VERSION, '8.2') >= 0) {
			$php_v = '82';
		} elseif (version_compare(PHP_VERSION, '8.1') >= 0) {
			$php_v = '81';
		} elseif (version_compare(PHP_VERSION, '7.1') >= 0) {
			$php_v = '71_74';
		} elseif (version_compare(PHP_VERSION, '5.6.0') >= 0) {
			$php_v = '56_70';
		} else {
			echo "Sorry! Version for PHP 5.4 Not Supported!";
			exit;
		}

		$file = DIR_SYSTEM . 'library/handy/handy_' . $php_v . '.php';

		if (is_file($file)) {
			require_once $file;
		} else {
			echo "No file '$file'<br>";
			exit;
		}

		$this->handy = new Handy($this->stdelog, $this->config->get('module_handy_licence'));
		
		// !A  Note-1
		// Каждое массовое редактирование выделяю в отдельный лог-файл - но это надо и в конструторе еще проследить, чтобы при создании экземпляра класса сразу присваивалась правильная метка
		// Здесь только те переменные сессии, которые нужны для лог-файла
		
		if (isset($this->request->post['handy_new_submit'])) {
			// При успешном завершении импорта, $this->session->data['handy']['processing_start_time'] обнуляется и так
			// Но в случае ошибки, необходимо обнулить принудительно...
			$this->session->data['handy']['processing_start_time'] = date("H-i-s") . '_' . time(); // IT IS NOT required to be time!
			
			$this->stdelog->write(3, '__construct() :: NEW SESSION');
			
			$this->stdelog->write(2, '__construct() :: SEND LOGS TO FILE `handy_' . date("Y-m-d_H-i-s") . '_' . $this->session->data['handy']['processing_start_time'] . '.log`');
		}

		if (isset($this->session->data['handy']['processing_start_time']) && isset($this->request->post['flag_mass_edit'])) {
			$this->stdelog->setMarker($this->session->data['handy']['processing_start_time']);
		}

		$this->load->model('extension/module/handy');
		$this->model = $this->model_extension_module_handy;
		$this->collation = $this->model->collationInfo();
	}

	public function install() {
		$this->load->model('user/user_group');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/handy');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/handy');
		
		if (!is_file(DIR_APPLICATION . 'model/tool/translit.php')) {
			rename(DIR_SYSTEM . 'library/handy/translit.php', DIR_APPLICATION . 'model/tool/translit.php');
		}
		
		
		/* Preventing the loss of module settings (for 3 it is different)
		----------------------------------------------------- */
		$sql = "SELECT * FROM `" . DB_PREFIX . "setting` WHERE `code` = 'module_handy_product_manager'";

		$query = $this->db->query($sql);

		$module_data = [];

		if ($query->num_rows > 0) {
			foreach ($query->rows as $row) {
				$key = str_replace('module_handy_product_manager', 'module_handy', $row['key']);
				
				if (false !== strpos($key, 'test_mode')) {
					$key = str_replace('test_mode', 'debug', $key);
				}

				$module_data[$key] = $this->config->get($row['key']);
			}
		}

		if (count($module_data) > 0) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('module_handy', $module_data);

			// Remove settings with old code from db
			$this->model_setting_setting->editSetting('module_handy_product_manager', []);
		}
		
		
		
		
		/* Deactivation of the old version + activation of the new one
		  ----------------------------------------------------- */
		if (is_file(DIR_APPLICATION . 'controller/extension/module/handy_product_manager.php')) {
			$this->load->model('setting/extension');
			$this->load->model('setting/module');

			$this->model_setting_extension->uninstall('module', 'handy_product_manager');
			$this->model_setting_module->deleteModulesByCode('handy_product_manager');


			$this->model_setting_extension->install('module', 'handy');

			$this->load->model('user/user_group');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/handy');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/handy');

			unlink(DIR_APPLICATION . 'controller/extension/module/handy_product_manager.php');
			unlink(DIR_APPLICATION . 'model/extension/module/handy_product_manager.php');
			unlink(DIR_APPLICATION . 'view/stylesheet/hpm.css');

			rmRec(DIR_APPLICATION . 'view/javascript/4hpm');

			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm.twig');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_mass_edit.twig');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_mass_edit_js.tpl');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_product_list_content.twig');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_product_list_frame.twig');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_product_list_js.tpl');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_product_list_js__attributes.tpl');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_product_list_js__dynamic_content.tpl');
			unlink(DIR_APPLICATION . 'view/template/extension/module/hpm_product_list_js__options.tpl');

			if (is_file(DIR_APPLICATION . 'lanugage/ru-ru/extension/module/handy_product_manager.php'))
				unlink(DIR_APPLICATION . 'lanugage/ru-ru/extension/module/handy_product_manager.php');
			if (is_file(DIR_APPLICATION . 'lanugage/russian/extension/module/handy_product_manager.php'))
				unlink(DIR_APPLICATION . 'lanugage/russian/extension/module/handy_product_manager.php');
			if (is_file(DIR_APPLICATION . 'lanugage/en-gb/extension/module/handy_product_manager.php'))
				unlink(DIR_APPLICATION . 'lanugage/en-gb/extension/module/handy_product_manager.php');
			if (is_file(DIR_APPLICATION . 'lanugage/english/extension/module/handy_product_manager.php'))
				unlink(DIR_APPLICATION . 'lanugage/english/extension/module/handy_product_manager.php');
			if (is_file(DIR_APPLICATION . 'lanugage/uk-ua/extension/module/handy_product_manager.php'))
				unlink(DIR_APPLICATION . 'lanugage/uk-ua/extension/module/handy_product_manager.php');
			if (is_file(DIR_APPLICATION . 'lanugage/ukrainian/extension/module/handy_product_manager.php'))
				unlink(DIR_APPLICATION . 'lanugage/ukrainian/extension/module/handy_product_manager.php');
		}
	}

	public function uninstall() {
		$this->load->model('user/user_group');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/module/handy');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'modify', 'extension/module/handy');

		// Delete setting because filename is not match with key handy
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_handy'); // Is different for OC 3

		// Можно вписать удаление файлов модуля...
	}




	public function index() {
		$this->load->model('setting/setting');

		$this->load->language('extension/module/handy');

		// Prevent Error in query (1364): Field 'name' doesn't have a default value
		// For OC 2 - it is placed in install.php, not in model
		$this->model->extensionUpdateDefaultValues();
		
		$this->document->setTitle($this->language->get('handy_title'));

		// Save
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// Сохраняем негативный статус поля без его удаления
			if (isset($this->request->post['module_handy_product_list_field_custom'])) {
				foreach ($this->request->post['module_handy_product_list_field_custom'] as $key => $value) {
					if (!isset($this->request->post['module_handy_product_list_field_custom'][$key]['status'])) {
						$this->request->post['module_handy_product_list_field_custom'][$key]['status'] = '';
					}
				}
			}

			$this->model_setting_setting->editSetting('module_handy', $this->request->post);

			//$this->session->data['success'] = $this->language->get('text_success');

			//$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));

			$data['success'] = $this->language->get('text_success');
		}

		// Error
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['errors'])) {
			$data['errors'] = $this->error['errors'];
		} else {
			$data['errors'] = '';
		}

		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_2'),
			'href' => $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true)
		);

		// Default links
		$data['action'] = $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['link_part_settings']		 = $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true);
		$data['link_part_productlist'] = $this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'], true);
		$data['link_part_massedit']		 = $this->url->link('extension/module/handy/massEdit', 'user_token=' . $this->session->data['user_token'], true);

		// For OC 2 -- text_part_*...

		// Data
		$data['user_token'] = $this->session->data['user_token'];
		$data['valid_licence'] = $this->handy->isValidLicence($this->config->get('module_handy_licence'));

		// Languages
		$this->load->model('localisation/language');
		$data['languages']						 = $this->model_localisation_language->getLanguages();
		$data['config_language_code']	 = $this->config->get('config_language');

		$data['debug_levels'] = array(
			0	 => $this->language->get('debug_0'),
			1	 => $this->language->get('debug_1'),
			2	 => $this->language->get('debug_2'),
			3	 => $this->language->get('debug_3'),
			4	 => $this->language->get('debug_4')
		);

		$data['module_handy_licence']						 = $this->standartField('module_handy_licence');
		$data['module_handy_status']						 = $this->standartField('module_handy_status', 0);
		$data['module_handy_debug']							 = $this->standartField('module_handy_debug', 3);
		$data['module_handy_product_list_limit'] = $this->standartField('module_handy_product_list_limit', 20);

		// Fields in product list
		$data['a_exist_product_fields'] = [
			'image',
			'name',
			'model',
			'sku',
			'upc',
			'ean',
			'jan',
			'isbn',
			'mpn',
			'category',
			'manufacturer',
			'location',
			'quantity',
			'price',
			'discount',
			'special',
			'points',
			'status',
			'stock_status',
			'tax_class',
			'store',
			'related',
			'filter',
			'sort_order',
			'shipping',
			'subtract',
			'minimum',
			'weight',
			'dimension',
			'date_added',
			'date_available',
			'date_modified',
		];

		$h1 = $this->model->getH1();

		if ($h1) {
			$data['a_exist_product_fields'][] = $h1;
		}

		$data['a_exist_product_fields'][] = 'meta_title';
		$data['a_exist_product_fields'][] = 'meta_description';
		$data['a_exist_product_fields'][] = 'meta_keyword';
		$data['a_exist_product_fields'][] = 'tag';
		$data['a_exist_product_fields'][] = 'description';
		$data['a_exist_product_fields'][] = 'attribute';
		$data['a_exist_product_fields'][] = 'option';
		
		$data['a_required_product_field'] = [
			'image',
			'name',
			'model',
			'manufacturer',
			'price',
			'quantity',
			'status',
		];

		if ($this->model->issetField('noindex')) {
			$data['a_exist_product_fields'][] = 'noindex';
		}


		$data['text4fields'] = [
			'image'						 => $this->clearLabel('entry_image'),
			'name'						 => $this->clearLabel('entry_name'),
			'model'						 => $this->clearLabel('entry_model'),
			'sku'							 => $this->clearLabel('entry_sku'),
			'upc'							 => $this->clearLabel('entry_upc'),
			'ean'							 => $this->clearLabel('entry_ean'),
			'jan'							 => $this->clearLabel('entry_jan'),
			'isbn'						 => $this->clearLabel('entry_isbn'),
			'mpn'							 => $this->clearLabel('entry_mpn'),
			'category'				 => $this->clearLabel('handy_filter_categories'),
			'manufacturer'		 => $this->clearLabel('entry_manufacturer'),
			'location'				 => $this->clearLabel('entry_location'),
			'quantity'				 => $this->clearLabel('entry_quantity'),
			'price'						 => $this->clearLabel('entry_price'),
			'tax_class'				 => $this->clearLabel('entry_tax_class'),
			'discount'				 => $this->clearLabel('tab_discount'), // A!
			'special'					 => $this->clearLabel('tab_special'), // A!
			'points'					 => $this->clearLabel('entry_points'),
			'status'					 => $this->clearLabel('entry_status'),
			'stock_status'		 => $this->clearLabel('entry_stock_status'),
			'store'						 => $this->clearLabel('entry_store'),
			'related'					 => $this->clearLabel('entry_related'),
			'filter'					 => $this->clearLabel('entry_filter'),
			'sort_order'			 => $this->clearLabel('entry_sort_order'),
			'shipping'				 => $this->clearLabel('entry_shipping'),
			'subtract'				 => $this->clearLabel('entry_subtract'),
			'minimum'					 => $this->clearLabel('entry_minimum'),
			'weight'					 => $this->clearLabel('entry_weight'),
			'dimension'				 => $this->clearLabel('entry_dimension'),
			'date_added'			 => $this->clearLabel('handy_entry_date_added'),
			'date_modified'		 => $this->clearLabel('handy_entry_date_modified'),
			'date_available'	 => $this->clearLabel('entry_date_available'),
			'meta_title'			 => $this->clearLabel('entry_meta_title'),
			'meta_description' => $this->clearLabel('entry_meta_description'),
			'meta_keyword'		 => $this->clearLabel('entry_meta_keyword'),
			'tag'							 => $this->clearLabel('entry_tag'),
			'description'			 => $this->clearLabel('entry_description'),
			'attribute'				 => $this->clearLabel('entry_attribute'),
			'option'					 => $this->clearLabel('entry_option'),
		];

		if ($h1) {
			$data['text4fields'][$h1] = $this->clearLabel('entry_' . $h1);
		}
		
		$data['text4fields']['meta_title']			 = $this->clearLabel('entry_meta_title');
		$data['text4fields']['meta_description'] = $this->clearLabel('entry_meta_description');
		$data['text4fields']['meta_keyword']		 = $this->clearLabel('entry_meta_keyword');
		$data['text4fields']['tag']							 = $this->clearLabel('entry_tag');
		$data['text4fields']['description']			 = $this->clearLabel('entry_description');
		$data['text4fields']['attribute']				 = $this->clearLabel('entry_attribute');
		$data['text4fields']['option']					 = $this->clearLabel('entry_option');
		$data['text4fields']['noindex']					 = $this->clearLabel('entry_noindex');	

		//$data['module_handy_product_list_field'] = $this->standartField('module_handy_product_list_field', $data['a_required_product_field']);

		if (isset($this->request->post['module_handy_product_list_field'])) {
			$data['module_handy_product_list_field'] = $this->request->post['module_handy_product_list_field'];
		} elseif ($this->config->get('module_handy_product_list_field')) {
			$data['module_handy_product_list_field'] = $this->config->get('module_handy_product_list_field');
		} else {
			$data['module_handy_product_list_field'] = array_merge(
				$data['a_required_product_field'], [
				'sku',
				'category',
				'quantity',
				'discount',
				'special',
				'stock_status',
				'meta_title',
				'meta_description',
				'meta_keyword',
				'attribute',
				]
			);
		}

		foreach ($data['a_required_product_field'] as $field) {
			if (!in_array($field, $data['module_handy_product_list_field'])) {
				$data['module_handy_product_list_field'][] = $field;
			}
		}

		// Fields in product list Custom
		$data['product_table_columns_custom_exist'] = $this->model->getProductTableColumns();


		if (isset($this->request->post['module_handy_product_list_field_custom'])) {
			$data['module_handy_product_list_field_custom'] = $this->request->post['module_handy_product_list_field_custom'];
		} elseif ($this->config->get('module_handy_product_list_field_custom')) {
			$data['module_handy_product_list_field_custom'] = $this->config->get('module_handy_product_list_field_custom');
		} else {
			$data['module_handy_product_list_field_custom'] = array();

			foreach ($data['product_table_columns_custom_exist'] as $key => $value) {
				$data['module_handy_product_list_field_custom'][$key]['status'] = 0;

				foreach ($data['languages'] as $language) {
					$data['module_handy_product_list_field_custom'][$key]['description'][$language['language_id']] = $this->language->get('handy_text_custom_fields_description') . ' ' . $key . ' ' . $language['name'];
				}

				$data['module_handy_product_list_field_custom'][$key]['field_type'] = 'other';
			}
		}

		$data['custom_fields_types_exist'] = array(
			'price' => $this->language->get('handy_text_custom_fields_type_price'),
			'other' => $this->language->get('handy_text_custom_fields_type_other'),
		);

		// Automatic Change
		$data['module_handy_model_automatic_change'] = $this->standartField('module_handy_model_automatic_change', 0);
		$data['module_handy_sku_automatic_change'] = $this->standartField('module_handy_sku_automatic_change', 0);
		
		// Model Require
		$data['module_handy_product_edit_model_require'] = $this->standartField('module_handy_product_edit_model_require', true);

		// Sku Require
		$data['module_handy_product_edit_sku_require'] = $this->standartField('module_handy_product_edit_sku_require', false);

		$data['module_handy_language']					 = $this->standartField('module_handy_language');
		$data['module_handy_translit_function'] = $this->standartField('module_handy_translit_function');
		$data['module_handy_translit_formula']	 = $this->standartField('module_handy_translit_formula', '[product_name]-[product_id]');

		// translit functions
		$this->load->model('tool/translit');
		$data['translit_functions'] = $this->model_tool_translit->getFunctionsList();

		// Upload Images
		$data['a_upload_rename_modes'] = array(
			'as_is'			 => $this->language->get('text_as_is'),
			'by_formula' => $this->language->get('text_by_formula'),
		);

		$data['a_upload_modes'] = array(
			'branch'					 => $this->language->get('text_branch'),
			'dir_for_category' => $this->language->get('text_dir_for_category'),
		);

		$data['module_handy_upload_settings'] = $this->standartField('module_handy_upload_settings', array(
			'rename_mode'		 => 'by_formula',
			'rename_formula' => '[product_name]',
			'max_size_in_mb' => 2,
			'upload_mode'		 => 'dir_for_category',
		));

		$data['module_handy_categories_mode']	= $this->standartField('module_handy_categories_mode');
		
		$data['a_price_prefixes_possible'] = $this->model->getPricePrefixSymbols();
		
		$data['module_handy_price_prefixes'] = $this->standartField('module_handy_price_prefixes', []);

		$data['text_version'] = sprintf($this->language->get('text_version'), HANDY_VERSION);
		$data['check_license'] = '';
		//$data['check_license'] = sprintf($this->language->get('check_license'), HTTPS_CATALOG);

		// Parts
		$data['header']			 = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']			 = $this->load->controller('common/footer');

		// Render view
		$this->response->setOutput($this->load->view('extension/module/handy', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/handy')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Licence
		if (isset($this->request->post['module_handy_licence']) and !empty($this->request->post['module_handy_licence'])) {
		  if (!$this->handy->isValidLicence($this->request->post['module_handy_licence'])) {
				$this->error['errors']['licence'] = $this->language->get('error_licence_not_valid');
		  }
		} else {
		  // Такое возможно только, если человек попытается отобразить #module-work-area, не введя лицензию
		  $this->error['errors']['licence'] = $this->language->get('error_licence_empty');
		}

		if (empty($this->request->post['module_handy_product_list_limit'])) {
			$this->error['errors']['product_list_limit'] = $this->language->get('error_product_list_limit');
		} else {
			if ($this->request->post['module_handy_product_list_limit'] < 10) {
				$this->error['errors']['product_list_limit'] = $this->language->get('error_product_list_limit_small');
			}

			if ($this->request->post['module_handy_product_list_limit'] > 500) {
				$this->error['errors']['product_list_limit'] = $this->language->get('error_product_list_limit_big');
			}
		}

		if ('by_formula' == $this->request->post['module_handy_upload_settings']['rename_mode']) {
			if (empty($this->request->post['module_handy_upload_settings']['rename_formula'])) {
				$this->error['errors']['rename_formula'] = $this->language->get('error_rename_formula_empty');
			} else {
				$this->request->post['module_handy_upload_settings']['rename_formula'] = preg_replace(array('| |', '|,|'), array('-', ''), trim($this->request->post['module_handy_upload_settings']['rename_formula']));

				$this->request->post['module_handy_upload_settings']['rename_formula'] = preg_replace('|-+|', '-', $this->request->post['module_handy_upload_settings']['rename_formula']);

				// need be at least 1 variable
				if ( false === strstr($this->request->post['module_handy_upload_settings']['rename_formula'], '[product_name]')
					&& false === strstr($this->request->post['module_handy_upload_settings']['rename_formula'], '[model]')
					&& false === strstr($this->request->post['module_handy_upload_settings']['rename_formula'], '[sku]') ) {
					$this->error['errors']['rename_formula'] = $this->language->get('error_formula_less_vars');
				} else {
					$str_without_vars = str_replace(array('[product_name]', '[model]', '[sku]'), array('', '', ''), $this->request->post['module_handy_upload_settings']['rename_formula']);

					if (!empty($str_without_vars)) {
						if (!preg_match("/^[\-_]+$/", $str_without_vars)) {
							$this->error['errors']['rename_formula'] = $this->language->get('error_formula_pattern');
						}
					}
				}
			}

		}

		if (empty($this->request->post['module_handy_upload_settings']['max_size_in_mb'])) {
			$this->error['errors']['max_size_in_mb'] = $this->language->get('error_max_size_in_mb');
		}

		// if any errors : common warning
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	public function filter($data) {
		return $this->load->view('extension/module/handy_filter', $data);
	}

	public function productListDynamicContent() {
		$this->load->language('catalog/product');
		$this->load->language('extension/module/handy');
		$this->load->model('catalog/product');

		// Filter
		$handy_filter = $this->helperGetURLParamsFromGet();

		$url = $this->helperBuildURLParams();

		$page = $handy_filter['page'];
		
		// Setting
		$data['debug'] = $this->config->get('module_handy_debug');

		// Language
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['config_language_id'] = $this->config->get('config_language_id');

		// List Fields
		$data['a_product_list_field']	 = $this->config->get('module_handy_product_list_field');

    $data['a_price_prefixes']	= is_array($this->config->get('module_handy_price_prefixes')) ? $this->config->get('module_handy_price_prefixes') : []; // for no error on front foreach

		// Custom Fields
		$data['a_product_list_field_custom'] = $this->config->get('module_handy_product_list_field_custom');

		if (!$data['a_product_list_field_custom']) {
			$data['a_product_list_field_custom'] = array();
		}

		// Не показывать поля, у которых не включен статус
		foreach ($data['a_product_list_field_custom'] as $field => $value) {
			if ('on' != $value['status']) {
				unset($data['a_product_list_field_custom'][$field]);
			}
		}

		// Separate Custom Fields With Price
		$data['a_product_list_field_custom_price'] = array();

		foreach ($data['a_product_list_field_custom'] as $field => $value) {
			if ('price' == $value['field_type']) {
				$data['a_product_list_field_custom_price'][$field] = $data['a_product_list_field_custom'][$field];
				unset($data['a_product_list_field_custom'][$field]);
			}
		}

		// H1
		$data['h1'] = $this->model->getH1();
    $data['handy_entry_h1']	= $this->language->get('entry_' . $data['h1']);

		// Store
		$this->load->model('setting/store');

		// A! Is different for OpenCart 2 && view alsoo!
		// 
		// A! It is in OpenCart 3 only
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		// Common for all OpenCart 2 & 3
		$stores = $this->model_setting_store->getStores();

		// A! It is in OpenCart 3 only
		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		// Category
		$this->load->model('catalog/category');

		$data['categories'] = array();

		$categories = $this->model->getCategoriesLevel1();

		foreach ($categories as $category_id) {
			$data['categories'][] = $this->model->getDescendantsTreeForCategory($category_id);
		}

		// for main category
		$data['has_main_category_column'] = $this->model->hasMainCategoryColumn();
		
		if ($data['has_main_category_column']) {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset_category');
		} else {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset');
		}

		//$data['getProductMainCategoryIdExist'] = is_callable([$this->model_catalog_product, 'getProductMainCategoryId']);
		$data['getProductMainCategoryIdExist'] = is_callable([new ModelCatalogProduct($this->registry), 'getProductMainCategoryId']);
		
		$data['categories_mode'] = $this->config->get('module_handy_categories_mode');
		
		if ($data['has_main_category_column'] && $data['getProductMainCategoryIdExist']) {
			$filter_data = array(
				'sort'	 => 'name',
				'order'	 => 'ASC',
			);

			$data['all_categories'] = $this->model_catalog_category->getCategories($filter_data);
		}

		// Image
		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		// Weight Class
		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
		
		// Length Class
		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		// Manufacturer
		$this->load->model('catalog/manufacturer');

		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers([]);

		// Stock Status
		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		// Tax Class
		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		// Attribute
		$this->load->model('catalog/attribute');

		// Option
		$this->load->model('catalog/option');

		// Customer Group
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// Get Product List
		$data['products'] = array();

		// Filter data for products query
		$filter_data = $handy_filter;

		$limits['first_element'] = ($page - 1) * $this->config->get('module_handy_product_list_limit');
		$limits['limit_n'] = $this->config->get('module_handy_product_list_limit');

		$product_total = $this->model->filterCountProducts($filter_data);

		$products = $this->model->filterGetProducts($filter_data, $limits);
		
		$this->stdelog->write(4, $products, 'productListDynamicContent() :: $products');

		
		/* Product Loop . Start
		--------------------------------------------------------------------------------- */
		foreach ($products as $product_id) {
			$result = $this->model_catalog_product->getProduct($product_id);
			
			$this->stdelog->write(4, $result, 'productListDynamicContent() :: $result');
			
			// product list image main
			if (is_file(DIR_IMAGE . $result['image'])) {
				$main_thumb	 = $this->model_tool_image->resize($result['image'], 100, 100);
				$main_image	 = $result['image'];
			} else {
				$main_thumb	 = $this->model_tool_image->resize('no_image.png', 100, 100);
				$main_image	 = '';
			}

			// product list images additional
			$images = $this->model->getProductImages($result['product_id']);

			$a_images = array();

			foreach ($images as $product_image) {
				if (is_file(DIR_IMAGE . $product_image['image'])) {
					$image = $product_image['image'];
					$thumb = $product_image['image'];
				} else {
					$image = $product_image['image']; // must be for deleting in product list by name
					$thumb = 'no_image.png';
				}

				$a_images[] = array(
					'image'			 => $image,
					'thumb'			 => $this->model_tool_image->resize($thumb, 100, 100),
					'sort_order' => $product_image['sort_order']
				);
			}

			// product list product_related
			$product_relateds_0 = $this->model_catalog_product->getProductRelated($result['product_id']);
			
			$product_relateds = array();
			
			foreach ($product_relateds_0 as $product_related_id) {
				$related_info = $this->model_catalog_product->getProduct($product_related_id);

				if ($related_info) {
					$product_relateds[] = array(
						'product_id' => $related_info['product_id'],
						'name'			 => $related_info['name']
					);
				}
			}

			// product list product_filter
			$this->load->model('catalog/filter');

			$filters = $this->model_catalog_product->getProductFilters($result['product_id']);

			$product_filters = array();

			foreach ($filters as $filter_id) {
				$filter_info = $this->model_catalog_filter->getFilter($filter_id);

				if ($filter_info) {
					$product_filters[] = array(
						'filter_id'	 => $filter_info['filter_id'],
						'name'			 => $filter_info['group'] . ' &gt; ' . $filter_info['name']
					);
				}
			}

			// product list special
			$specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			$product_specials = array();

			foreach ($specials as $product_special) {
				$product_specials[] = array(
					'product_special_id' => $product_special['product_special_id'],
					'customer_group_id'	 => $product_special['customer_group_id'],
					'priority'					 => $product_special['priority'],
					'price'							 => $product_special['price'],
					'date_start'				 => ($product_special['date_start'] != '0000-00-00') ? $product_special['date_start'] : '',
					'date_end'					 => ($product_special['date_end'] != '0000-00-00') ? $product_special['date_end'] : ''
				);
			}

			// product list discount
			$discounts = $this->model_catalog_product->getProductDiscounts($result['product_id']);

			$product_discounts = array();

			foreach ($discounts as $product_discount) {
				$product_discounts[] = array(
					'product_discount_id'	 => $product_discount['product_discount_id'],
					'customer_group_id'		 => $product_discount['customer_group_id'],
					'quantity'						 => $product_discount['quantity'],
					'priority'						 => $product_discount['priority'],
					'price'								 => $product_discount['price'],
					'date_start'					 => ($product_discount['date_start'] != '0000-00-00') ? $product_discount['date_start'] : '',
					'date_end'						 => ($product_discount['date_end'] != '0000-00-00') ? $product_discount['date_end'] : ''
				);
			}

			// product list category
			$product_categories_ids = $this->model_catalog_product->getProductCategories($result['product_id']);
			
			$product_categories = [];

			foreach ($product_categories_ids as $category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);

				if ($category_info) {
					$product_categories[] = array(
						'category_id' => $category_info['category_id'],
						'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
					);
				}
			}
			
			$category_tree = '';
			
			if ('tree_view' == $data['categories_mode']) {
				$category_tree = $this->handy->getCategoriesList($data['categories'], $product_categories_ids, $level = 1);
			}			

			$main_category_id = 0;

			if ($data['has_main_category_column'] && $data['getProductMainCategoryIdExist']) {
				$main_category_id = $this->model_catalog_product->getProductMainCategoryId($result['product_id']);
			}

			// product list manufacturer
			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer($result['manufacturer_id']);

			if ($manufacturer_info) {
				$manufacturer = $manufacturer_info['name'];
			} else {
				$manufacturer = '';
			}

			// product list attribute
			$product_attributes = $this->model_catalog_product->getProductAttributes($result['product_id']);

			$data['product_attributes'] = array();

			foreach ($product_attributes as $product_attribute) {
				$attribute_info = $this->model_catalog_attribute->getAttribute($product_attribute['attribute_id']);

				if ($attribute_info) {
					$data['product_attributes'][] = array(
						'attribute_id'									 => $product_attribute['attribute_id'],
						'name'													 => $attribute_info['name'],
						'product_attribute_description'	 => $product_attribute['product_attribute_description'],
						'edit'													 => $this->url->link('catalog/attribute/edit', 'user_token=' . $this->session->data['user_token'] . '&attribute_id=' . $product_attribute['attribute_id'], true)
					);
				}
			}

			// product list option
			$product_options = $this->model_catalog_product->getProductOptions($result['product_id']);

			$data['product_options'] = array();

			foreach ($product_options as $product_option) {
				$product_option_value_data = array();

				if (isset($product_option['product_option_value'])) {
					foreach ($product_option['product_option_value'] as $product_option_value) {
						$product_option_value_data[] = array(
							'product_option_value_id'	 => $product_option_value['product_option_value_id'],
							'option_value_id'					 => $product_option_value['option_value_id'],
							'quantity'								 => $product_option_value['quantity'],
							'subtract'								 => $product_option_value['subtract'],
							'price'										 => $product_option_value['price'],
							'price_prefix'						 => $product_option_value['price_prefix'],
							'points'									 => $product_option_value['points'],
							'points_prefix'						 => $product_option_value['points_prefix'],
							'weight'									 => $product_option_value['weight'],
							'weight_prefix'						 => $product_option_value['weight_prefix']
						);
					}
				}

				$data['product_options'][] = array(
					'product_option_id'		 => $product_option['product_option_id'],
					'product_option_value' => $product_option_value_data,
					'option_id'						 => $product_option['option_id'],
					'name'								 => $product_option['name'],
					'type'								 => $product_option['type'],
					'value'								 => isset($product_option['value']) ? $product_option['value'] : '',
					'required'						 => $product_option['required'],
					'edit'								 => $this->url->link('catalog/option/edit', 'user_token=' . $this->session->data['user_token'] . '&option_id=' . $product_option['option_id'], true)
				);
			}

			$data['option_values'] = array();

			foreach ($data['product_options'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (!isset($data['option_values'][$product_option['option_id']])) {
						$data['option_values'][$product_option['option_id']] = $this->model_catalog_option->getOptionValues($product_option['option_id']);
					}
				}
			}

			// Custom Fields
			$custom_fields = array();

			foreach($data['a_product_list_field_custom'] as $key => $value) {
				if (isset($result[$key])) {
					$custom_fields[$key] = $result[$key];
				}
			}

			$custom_fields_price = array();

			foreach($data['a_product_list_field_custom_price'] as $key => $value) {
				if (isset($result[$key])) {
					$custom_fields_price[$key] = $result[$key];
				}
			}

			$data['products'][$product_id] = array(
				'product_id'					 => $result['product_id'],
				'main_category_id'		 => $main_category_id,
				'product_categories'	 => $product_categories, // for not tree selector?
				'category_tree'				 => $category_tree,
				'manufacturer_id'			 => $result['manufacturer_id'],
				'manufacturer'				 => $manufacturer,
				'thumb'								 => $main_thumb,
				'image'								 => $main_image,
				'product_image'				 => $a_images,
				'product_description'	 => $this->model->getProductDescriptions($result['product_id'], $data['h1']),
				'model'								 => $result['model'],
				'sku'									 => $result['sku'],
				'upc'									 => $result['upc'],
				'ean'									 => $result['ean'],
				'jan'									 => $result['jan'],
				'isbn'								 => $result['isbn'],
				'mpn'									 => $result['mpn'],
				'location'						 => $result['location'],
				'price'								 => $result['price'],
				'product_specials'		 => $product_specials,
				'product_discounts'		 => $product_discounts,
				'points'		           => $result['points'],
				'product_reward'		   => $this->model_catalog_product->getProductRewards($result['product_id']),
				'quantity'						 => $result['quantity'],
				'stock_status_id'			 => $result['stock_status_id'],
				'tax_class_id'			 => $result['tax_class_id'],
				'status'							 => $result['status'],
				'sort_order'					 => $result['sort_order'],
				'shipping'						 => $result['shipping'],
				'subtract'						 => $result['subtract'],
				'minimum'							 => $result['minimum'],
				'weight'							 => $result['weight'],
				'weight_class_id'			 => $result['weight_class_id'] ? $result['weight_class_id'] : $this->config->get('config_weight_class_id'),
				'length'							 => $result['length'],
				'width'								 => $result['width'],
				'height'							 => $result['height'],
				'length_class_id'			 => $result['length_class_id'] ? $result['length_class_id'] : $this->config->get('config_length_class_id'),
				'date_available'			 => '0000-00-00' == $result['date_available'] ? '' : $result['date_available'],
				'date_added'			     => '0000-00-00 00:00:00' == $result['date_added'] ? '' : $result['date_added'],
				'date_modified'			   => '0000-00-00 00:00:00' == $result['date_modified'] ? '' : $result['date_modified'],
				'noindex'							 => isset($result['noindex']) ? $result['noindex'] : '', // for OpenCart PRO
				'custom_fields'				 => $custom_fields,
				'custom_fields_price'	 => $custom_fields_price,
				'product_store'				 => $this->model_catalog_product->getProductStores($result['product_id']),
				'product_relateds'		 => $product_relateds,
				'product_filters'		   => $product_filters,
				'product_attributes'	 => $data['product_attributes'],
				'product_options'			 => $data['product_options'],
				'option_values'				 => $data['option_values'],
				'edit'								 => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'], true),
				'edit_in_system_mode'	 => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
				'view_in_catalog'			 => HTTPS_CATALOG . 'index.php?route=product/product&product_id=' . $result['product_id'],
			);
		}
		/* Product Loop . End
		--------------------------------------------------------------------------------- */
		$data['largest_product_id'] = false;

		if (isset($data['products'][0]['product_id'])) {
			$data['largest_product_id'] = $data['products'][0]['product_id'];
		}
		
		// Text
		if ($data['has_main_category_column']) {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset_category');
		} else {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset');
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array) $this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		// URL -- for pagination it is necessary to delete current page from exist url
		$url = str_replace('page=' . $page, '', $url);

		$pagination				 = new Pagination();
		$pagination->total = $product_total;
		$pagination->page	 = $page;
		$pagination->limit = $this->config->get('module_handy_product_list_limit');
		$pagination->url	 = $this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('module_handy_product_list_limit')) + 1 : 0, ((($page - 1) * $this->config->get('module_handy_product_list_limit')) > ($product_total - $this->config->get('module_handy_product_list_limit'))) ? $product_total : ((($page - 1) * $this->config->get('module_handy_product_list_limit')) + $this->config->get('module_handy_product_list_limit')), $product_total, ceil($product_total / $this->config->get('module_handy_product_list_limit')));

		$this->response->setOutput($this->load->view('extension/module/handy_product_list_response_by_ajax', $data));
	}

	public function productList() {
		$this->load->model('catalog/product');
		$this->load->language('catalog/product');
		$this->load->language('extension/module/handy');
		
		$data['user_token'] = $this->session->data['user_token'];

		$this->document->setTitle($this->language->get('handy_productlist_title'));

		$this->document->addScript('view/javascript/4handy/sortable/Sortable.js');
//		$this->document->addScript('view/javascript/4handy/jquery-ui-1.12.1/jquery-ui.min.js');
//		$this->document->addStyle('view/javascript/4handy/jquery-ui-1.12.1/jquery-ui.min.css');
//		A!
//    jquery ui conflict with input autocomplete [object Object]
//    it is possible to create custom js-file with resize only...
		$this->document->addStyle('view/stylesheet/handy.css');

		$this->document->addStyle('view/javascript/4handy/select2/select2.css');
		$this->document->addScript('view/javascript/4handy/select2/select2.min.js');

		// Filter
		$data['handy_filter'] = $this->helperGetURLParamsFromGet();

		$this->stdelog->write(4, $data['handy_filter'], 'productList() $data["handy_filter"]');

		// Text
		foreach ($this->language->all() as $key => $value) {
			$data[$key] = $value;
		}

		$url = $this->helperBuildURLParams();

		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_2'),
			'href' => $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_part_productlist'),
			'href' => $this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['add']		 = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['copy']		 = $this->url->link('extension/module/handy/copy', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete']	 = $this->url->link('extension/module/handy/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['link_part_settings']		 = $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true);
		$data['link_part_productlist'] = $this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'], true);
		$data['link_part_massedit']		 = $this->url->link('extension/module/handy/massEdit', 'user_token=' . $this->session->data['user_token'], true);

		// For OC 2 -- text_part_*...

		// Data
		$data['valid_licence'] = $this->handy->isValidLicence($this->config->get('module_handy_licence')) ? true : false;
		
		$data['user_token'] = $this->session->data['user_token'];
		
		// Setting
		$data['debug']						     = $this->config->get('module_handy_debug');
		$data['a_product_list_field']	 = $this->config->get('module_handy_product_list_field');
		$data['a_price_prefixes']      = is_array($this->config->get('module_handy_price_prefixes')) ? $this->config->get('module_handy_price_prefixes') : []; // for no error on front foreach
		$data['upload_settings']			 = $this->config->get('module_handy_upload_settings');
		$data['source_language']			 = $this->config->get('module_handy_language');
		$data['file_upload']					 = $this->handy->getKeyValue('file_upload');

		$data['module_handy_model_automatic_change'] = $this->config->get('module_handy_model_automatic_change');
		$data['module_handy_sku_automatic_change'] = $this->config->get('module_handy_sku_automatic_change');

		$data['view_sm'] = true;
		$data['output'] = false;

		// Language
		$this->load->model('localisation/language');
		$data['languages']					 = $this->model_localisation_language->getLanguages();
		$data['config_language_id']	 = $this->config->get('config_language_id');
		$data['config_language']		 = $this->config->get('config_language'); // check versions

		// Store
		$this->load->model('setting/store');

		// A! Is different for OpenCart 2 && view alsoo!
		// 
		// A! It is in OpenCart 3 only
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		// Common for all OpenCart 2 & 3
		$stores = $this->model_setting_store->getStores();

		// A! It is in OpenCart 3 only
		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		
		// Category
		$this->load->model('catalog/category');

		$filter_data = array(
			'sort'	 => 'name',
			'order'	 => 'ASC'
		);

		$data['all_categories'] = $this->model_catalog_category->getCategories($filter_data);

		$data['has_main_category_column'] = $this->model->hasMainCategoryColumn();
		
		if ($data['has_main_category_column']) {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset_category');
		} else {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset');
		}

		//$data['getProductMainCategoryIdExist'] = is_callable([$this->model_catalog_product, 'getProductMainCategoryId']);
		$data['getProductMainCategoryIdExist'] = is_callable([new ModelCatalogProduct($this->registry), 'getProductMainCategoryId']);

		// Category Tree for js included in handy_product_list.tpl
		$data['categories'] = array();

		$categories = $this->model->getCategoriesLevel1();

		foreach ($categories as $category_id) {
			$data['categories'][] = $this->model->getDescendantsTreeForCategory($category_id);
		}

		$categories_selected = array();

		if (isset($data['handy_filter']['category']) && is_array($data['handy_filter']['category'])) {
			$categories_selected = $data['handy_filter']['category'];
		}

		$data['handy_filter_category_tree'] = $this->handy->getCategoriesList($data['categories'], $categories_selected, $level = 1, 'handy_filter[category]');

		// Image
		$this->load->model('tool/image');

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['upload_settings'] = $this->config->get('module_handy_upload_settings');
		$data['source_language'] = $this->config->get('module_handy_language');
		
		$data_mod = $data['file_upload'];

		$upload_function = $this->handy->$data_mod($this->config->get('module_handy_licence'));

		if (!$upload_function) {
			$data['view_sm'] = false;
			$data['output'] = true;
		}

		// Weight Class
		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
		
		// Length Class
		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		// Manufacturer
		$this->load->model('catalog/manufacturer');

		$manufacturers = $this->model_catalog_manufacturer->getManufacturers([]);

		$data['manufacturers'] = [];
		
		foreach ($manufacturers as $manufacturer) {
			$data['manufacturers'][$manufacturer['manufacturer_id']] = $manufacturer['name'];
		}

		// Stock Status
		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		// Tax Class
		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		// Attributes
		$this->load->model('catalog/attribute');

		$data['attributes'] = $this->model_catalog_attribute->getAttributes();
		
		// Fix apostrophe in attribute name & attribute group & \r\n
		foreach ($data['attributes'] as $key => $attribute) {
			$data['attributes'][$key]['name'] = str_replace(["\n", "\r"], ["&#10;", "&#13;"], htmlspecialchars($attribute['name'], ENT_QUOTES, 'UTF-8'));
			$data['attributes'][$key]['attribute_group'] = htmlspecialchars($attribute['attribute_group'], ENT_QUOTES, 'UTF-8');
		}

		// Option
		$this->load->model('catalog/option');

		$options = $this->model_catalog_option->getOptions();

		// prevent: Uncaught SyntaxError: unexpected token: identifier . begin
		// ex: I DON'T NEED
		// A! При AJAX - работает даже с апострофами. А когда выдают json просто в строку, то беда.
		// Опять же, получается, что миме-тип по ходу тут html, а не json... А там хз
		
		$data['options'] = [];
		
		foreach ($options as $key => $value) {
			$value['name'] = str_replace("'", '&#39;', $value['name']);
			$data['options'][$key] = $value;
		}
		// prevent: Uncaught SyntaxError: unexpected token: identifier . end		

		// for js
		$data['optionsExist'] = array();

		foreach ($data['options'] as $key => $value) {
			// Fix for option tooltip . begin
			if (isset($value['tooltip'])) {
				//$value['tooltip'] = str_replace(["\r\n", "\r", "\n"], " ", $value['tooltip']); // Было подозрение на наличие в коде переноса строк - но проблема так и не была решена. А в интерфейсе модуля это все равно не выводится.
				
				unset($value['tooltip']);
			}
			// Fix for option tooltip . end
			
			$data['optionsExist'][$value['option_id']] = $value;
		}
		
		if (count($data['optionsExist']) > 0) {
			$data['optionsExist'] = json_encode($data['optionsExist'], JSON_HEX_APOS | JSON_HEX_QUOT);
		} else {
			$data['optionsExist'] = '""'; // prevent Uncaught SyntaxError: JSON.parse: unexpected end of data at line 1 column 1 of the JSON data (with empty string)
		}

		// Customer Group
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		// Get Product List
		$data['products'] = array();

		// Text
		// A! Is not available in OC 3 for $this->customInclude()
		foreach ($this->language->all() as $key => $value) {
			$data[$key] = $value;
		}
		
		if ($data['has_main_category_column']) {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset_category');
		} else {
			$data['handy_filter_text_notset_category'] = $this->language->get('handy_filter_text_notset');
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array) $this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$data['text_version'] = sprintf($this->language->get('text_version'), HANDY_VERSION);		
		$data['check_license'] = '';
		//$data['check_license'] = sprintf($this->language->get('check_license'), HTTPS_CATALOG);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']			 = $this->load->controller('common/footer');
		
		$data['filter_flag'] = 'product_list';
		$data['filter_reset_text'] = $this->language->get('handy_filter_reset_product_list');
		$data['filter_reset_link'] = $data['link_part_productlist'];
		
		$data['include_filter']	= $this->filter($data);
		
		// For OC3 only - get included files for our template file
		$data['js_product_list'] = $this->customInclude(DIR_APPLICATION . 'view/template/extension/module/handy_product_list_js.tpl', $data);
		$data['js_dynamic_content'] = $this->customInclude(DIR_APPLICATION . 'view/template/extension/module/handy_product_list_js__dynamic_content.tpl', $data);
		$data['js_attributes'] = $this->customInclude(DIR_APPLICATION . 'view/template/extension/module/handy_product_list_js__attributes.tpl', $data);
		$data['js_options'] = $this->customInclude(DIR_APPLICATION . 'view/template/extension/module/handy_product_list_js__options.tpl', $data);
		
		$this->response->setOutput($this->load->view('extension/module/handy_product_list', $data));
	}

	public function productListLiveEdit() {
		$json = array();

		$this->load->language('extension/module/handy');

		if (!$this->handy->isValidLicence($this->config->get('module_handy_licence'))) {
			$json['status']	 = 'error';
			$json['msg'] = $this->language->get('text_input_licence_mass');

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		}

		$this->stdelog->write(3, $this->request->post, '$this->request->post in productListLiveEdit()');

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			if ('add_image_additional' == $this->request->post['essence']) {
				$this->model->addProductImageAdditional($this->request->post);
			}

			if ('edit_image_main' == $this->request->post['essence']) {
				$this->model->editProductImageMain($this->request->post);
			}

			if ('edit_image_additional' == $this->request->post['essence']) {
				$this->model->editProductImageAdditional($this->request->post);
			}

			if ('edit_image_sorting' == $this->request->post['essence']) {
				$this->model->editProductImageSorting($this->request->post);
			}

			if ('edit_image_main_from_first_item' == $this->request->post['essence']) {
				$this->model->editProductImageMainFromFirstItem($this->request->post);
			}

			if ('edit_image_main_after_sorting' == $this->request->post['essence']) {
				$this->model->editProductImageMainAfterSorting($this->request->post);
			}

			if ('delete_image_main' == $this->request->post['essence']) {
				$this->model->deleteProductImageMain($this->request->post);
			}

			if ('delete_image_additional' == $this->request->post['essence']) {
				$this->model->deleteProductImageAdditional($this->request->post);
			}

			if ('edit_url' == $this->request->post['essence']) {
				$res = $this->model->editProductUrl($this->request->post);

				// is different for OC 3
				if ($this->config->get('config_seo_pro')) {
					$this->cache->delete('seopro');
				}
			}

			if ('edit_categories' == $this->request->post['essence']) {
				$res = $this->model->editProductCategories($this->request->post);
			}

			if ('edit_main_category' == $this->request->post['essence']) {
				$res = $this->model->editProductMainCategory($this->request->post);

				// is different for OC 3
				if ($this->config->get('config_seo_pro')) {
					$this->cache->delete('seopro');
				}
			}

			if ('edit_identity' == $this->request->post['essence']) {
				$res = $this->model->editProductIdentity($this->request->post);
			}

			if ('edit_product_reward' == $this->request->post['essence']) {
				$res = $this->model->editProductReward($this->request->post);
			}
			
			if ('add_discount' == $this->request->post['essence']) {
				$res = $this->model->addDiscount($this->request->post);
			}

			if ('edit_discount' == $this->request->post['essence']) {
				$res = $this->model->editDiscount($this->request->post);
			}

			if ('delete_discount' == $this->request->post['essence']) {
				$res = $this->model->deleteDiscount($this->request->post);
			}

			if ('add_special' == $this->request->post['essence']) {
				$res = $this->model->addSpecial($this->request->post);
			}

			if ('edit_special' == $this->request->post['essence']) {
				$res = $this->model->editSpecial($this->request->post);
			}

			if ('delete_special' == $this->request->post['essence']) {
				$res = $this->model->deleteSpecial($this->request->post);
			}

			if ('add_product_to_store' == $this->request->post['essence']) {
				$res = $this->model->addProductToStore($this->request->post);
			}

			if ('delete_product_from_store' == $this->request->post['essence']) {
				$res = $this->model->deleteProductFromStore($this->request->post);
			}
			
			if ('add_product_related' == $this->request->post['essence']) {
				$res = $this->model->addProductRelated($this->request->post);
			}
			
			if ('delete_product_related' == $this->request->post['essence']) {
				$res = $this->model->deleteProductRelated($this->request->post);
			}

			if ('add_product_filter' == $this->request->post['essence']) {
				$res = $this->model->addProductFilter($this->request->post);
			}
			
			if ('delete_product_filter' == $this->request->post['essence']) {
				$res = $this->model->deleteProductFilter($this->request->post);
			}

			if ('edit_description' == $this->request->post['essence']) {
				$res = $this->model->editProductDescription($this->request->post);
			}

			if ('add_new_attribute' == $this->request->post['essence']) {
				$res = $this->model->addNewAttribute($this->request->post);
			}

			if ('edit_attribute_value' == $this->request->post['essence']) {
				$res = $this->model->editProductAttributeValue($this->request->post);
			}

			if ('add_attribute_to_product' == $this->request->post['essence']) {
				$res = $this->model->addProductAttribute($this->request->post);
			}

			if ('delete_attribute_from_product' == $this->request->post['essence']) {
				$res = $this->model->deleteProductAttribute($this->request->post);
			}

			if ('edit_product_option' == $this->request->post['essence']) {
				$res = $this->model->editProductOption($this->request->post);
			}

			if ('delete_option_from_product' == $this->request->post['essence']) {
				$res = $this->model->deleteOptionFromProduct($this->request->post);
			}

			if ('add_product_option' == $this->request->post['essence']) {
				$res = $this->model->addProductOption($this->request->post);
			}

			if ('add_product_option_value' == $this->request->post['essence']) {
				$res = $this->model->addProductOptionValue($this->request->post);
			}

			if ('edit_product_option_value' == $this->request->post['essence']) {
				$res = $this->model->editProductOptionValue($this->request->post);
			}

			if ('delete_product_option_value' == $this->request->post['essence']) {
				$res = $this->model->deleteProductOptionValue($this->request->post);
			}

			// create & clone new product
			if ('add_new_product' == $this->request->post['essence']) {
				$data_input = $this->request->post;

				$this->load->model('localisation/language');
				$data_input['languages'] = $this->model_localisation_language->getLanguages();

				$res = $this->model->addNewProduct($data_input);
			}

			if ('delete_product' == $this->request->post['essence']) {
				// system method! - ни фига не систем!!!
				$this->model->deleteProduct($this->request->post['product_id']);
			}

		} else {
			$json['status']	 = 'error';
			$json['msg']		 = $this->language->get('handy_error_empty_post');
		}

		$json['status'] = 'success';

		$this->model->callByLiveEdit($this->request->post['essence']);

		if (isset($res)) {
			$json['result'] = $res;
		}

		//$json['msg'] = $this->language->get('handy_success');// пока что нигде не используется в js

		$this->stdelog->write(3, $json, 'productListLiveEdit() $json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function copy() {
		$this->stdelog->write(4, $this->request->get, 'copy() $this->request->get');
		$this->stdelog->write(4, $this->request->post, 'copy() $this->request->post');
		$this->load->language('catalog/product');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->copyProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->helperBuildURLParams();

			$this->response->redirect($this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}
	}

	public function delete() {
		$this->stdelog->write(4, $this->request->get, 'delete() $this->request->get');
		$this->stdelog->write(4, $this->request->post, 'delete() $this->request->post');

		$this->load->language('extension/module/handy');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/product');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $product_id) {
				$this->model_catalog_product->deleteProduct($product_id);
			}

			$this->session->data['success'] = $this->language->get('text_success_delete');

			$url = $this->helperBuildURLParams();

			$this->response->redirect($this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

	}

	protected function validateCopy() {
		if (!$this->user->hasPermission('modify', 'catalog/product')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/handy')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}




	/* Upload
	-------------------------------------------------------------------------- */
	public function upload() {
		$data						 = array();
		$data['answer']	 = 'error';

		$this->stdelog->write(3, 'upload() function is called');

		$settings = $this->config->get('module_handy_upload_settings');

		// for future ?
		if (!array_key_exists('resize', $settings)) {
			$settings['resize']		 = 'no';
			$settings['resize_w']	 = 1280;
			$settings['resize_h']	 = 1000;
		}

		if ('by_formula' == $settings['rename_mode']) {
			$product_info = array(
				'name'	 => isset($this->request->get['name']) ? $this->request->get['name'] : '',
				'model'	 => isset($this->request->get['model']) ? $this->request->get['model'] : '',
				'sku'		 => isset($this->request->get['sku']) ? $this->request->get['sku'] : '',
			);
		}

		$translit_function = $this->config->get('module_handy_translit_function');

		$lang_id = $this->config->get('module_handy_language');

		$this->stdelog->write(4, $this->request->post, '\$this->request->post in upload()');
		$this->stdelog->write(4, $this->request->get, '\$this->request->get in upload()');
		$this->stdelog->write(4, $_FILES, '\$_FILES in upload()');
		
		$this->load->model('tool/image'); // for resize
		$this->load->model('tool/translit'); // for translit
		$this->load->language('extension/module/handy');

		$valid_formats = $this->model->getValidFormats();

		if ('dir_for_category' == $settings['upload_mode']) {
			$category_name = $this->model->getCategoryName($this->request->get['category_id'], $lang_id);
			$this->stdelog->write(4, "\$category_name : " . $category_name);

			$category_dir = $this->model_tool_translit->$translit_function($category_name);
			$this->stdelog->write(4, "\$category_dir after translit() :: " . $category_dir);

			$category_dir = $this->model_tool_translit->clearWasteChars($category_dir);
			$this->stdelog->write(4, "\$category_dir after clearWasteChars() :: " . $category_dir);

			$uploaddir												 = DIR_IMAGE . 'catalog/' . $this->request->get['dir'] . '/' . $category_dir . '/';
			$target_dir_without_document_root	 = 'catalog/' . $this->request->get['dir'] . '/' . $category_dir . "/"; // for url

			if (!is_dir($uploaddir)) {
				// error
				$this->stdelog->write(1, "Error: no dir $uploaddir");
			}
		} else {
			$subdir = $this->handy->branchFolders(DIR_IMAGE . 'catalog/' . $this->request->get['dir']);

			if ($subdir) {
				$target_dir_without_document_root	 = 'catalog/' . $this->request->get['dir'] . "/" . basename($subdir) . "/"; // for url
				$uploaddir = $subdir . "/";
			} else {
				// error
				$this->stdelog->write(4, "Error: no subdir");
			}
		}

		if (!is_dir($uploaddir)) {
			$this->handy->createFolder($uploaddir);
		}

    $this->stdelog->write(4, $uploaddir, "\$uploaddir is");
    
		if (is_dir($uploaddir)) {
			$data['answer'] = 'success';

			foreach ($_FILES as $file) {
				if ('as_is' == $settings['rename_mode']) {
					$filename = pathinfo(stripslashes($file['name']), PATHINFO_FILENAME);
				}

				if ('by_formula' == $settings['rename_mode']) {
					$filename = $this->handy->getFileNameByFormula($product_info, $settings['rename_formula']);
				}

				$filename = $this->model_tool_translit->$translit_function($filename);

				$filename = $this->model_tool_translit->clearWasteChars($filename);

				$size = filesize($file['tmp_name']);
				//get the extension of the file in a lower case format

				$ext = strtolower(pathinfo(stripslashes($file['name']), PATHINFO_EXTENSION));

				if (in_array($ext, $valid_formats)) {
					// max file size
					if ($size < ($settings['max_size_in_mb'] * 1024 * 1024)) {
						// Проверка на уникальность названия фотографии + предотвращение перезаписи
						//if (is_file($uploaddir . $filename ." . " . $ext)) {
						$image_new_name = $this->handy->getUniqueName($uploaddir, $filename, $ext);
						//}

						if (move_uploaded_file($file['tmp_name'], $uploaddir . $image_new_name)) {
							$image			 = $target_dir_without_document_root . $image_new_name;
							$image_thumb = htmlspecialchars($this->model_tool_image->resize($image, 100, 100));

							$data['file']['thumb'] = $image_thumb;
							$data['file']['image'] = $image;

							// for future ?
							/*
							  if ('yes' == $settings['resize']) {
							  $this->model_tool_image->resize_proportionately($image, $settings['resize_w'], $settings['resize_h']);
							  }
							 *
							 */
							$image_data = array(
								'product_id'				 => $this->request->get['product_id'],
								'image'							 => $image,
								'image_additional_n' => isset($this->request->get['image_additional_n']) ? $this->request->get['image_additional_n'] : 0,
							);

							// todo - main & additional!!
							if ('main' == $this->request->get['photo_type']) {
								$this->stdelog->write(4, "upload() \$this->request->get['photo_type'] : main");
								$this->model->addProductImageMain($image_data);
							} else {
								$this->stdelog->write(4, "upload() \$this->request->get['photo_type'] : additional");
								$this->model->addProductImageAdditional($image_data);
							}
						} else {
							$data['answer']							 = 'error';
							$data['answer_description']	 = str_replace(array('[file]', '[target]'), array($file['name'], $image), $this->language->get('handy_upload_error_result'));
						}
					} else {
						$data['answer']							 = 'error';
						$data['answer_description']	 = str_replace('[file]', $file['name'], $this->language->get('handy_upload_error_max_size'));
					}
				} else {
					$data['answer']							 = 'error';
					$data['answer_description']	 = str_replace('[file]', $file['name'], $this->language->get('handy_upload_error_file_extenion'));
				}
			}
		} else {
			$data['answer']							 = 'error';
			$data['answer_description']	 = $this->language->get('error_dir');
		}


		header('Content-type:application/json;charset=utf-8');
		echo json_encode($data);
		$this->stdelog->write(4, $data, 'upload() result json-$data');
		exit;
	}




	/* Ajax actions
	-------------------------------------------------------------------------- */
	public function productAutocomplete() {
		$json = array();

		if (isset($this->request->get['name']) || isset($this->request->get['model']) || isset($this->request->get['sku'])) {
			$this->load->model('catalog/product');
			$this->load->model('catalog/option');

			if (isset($this->request->get['name'])) {
				$name = $this->request->get['name'];
			} else {
				$name = '';
			}

			if (isset($this->request->get['sku'])) {
				$sku = $this->request->get['sku'];
			} else {
				$sku = '';
			}

			if (isset($this->request->get['model'])) {
				$model = $this->request->get['model'];
			} else {
				$model = '';
			}

			if (isset($this->request->get['limit'])) {
				$limit = $this->request->get['limit'];
			} else {
				$limit = 25;
			}

			$filter_data = array(
				'filter_name'	 => $name,
				'filter_sku'	 => $sku,
				'filter_model' => $model,
				'start'				 => 0,
				'limit'				 => $limit
			);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'product_id' => $result['product_id'],
					'name'			 => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'sku'				 => $result['sku'] ? $result['sku'] : '--',
					'model'			 => $result['model'],
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getAttributeGroupList() {
		$this->stdelog->write(4, 'getAttributeGroupList() is called');

		$json = array();

		$this->load->model('catalog/attribute_group');

		$filter_data = array(
			//'product_id' => $this->request->get['product_id'],
		);

		//$results = $this->model->getAttributeGroups($filter_data);
		$results = $this->model_catalog_attribute_group->getAttributeGroups($filter_data);

		$attribute_gorup_list = array();

		foreach ($results as $result) {
			$attribute_gorup_list[] = array(
				'attribute_group_id' => $result['attribute_group_id'],
				'name'							 => $result['name'],
				'sort_order'				 => $result['sort_order'],
			);
		}

		$json['status']	 = 'success';
		$json['data']		 = $attribute_gorup_list;

		$this->stdelog->write(4, $json, '$json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getAttributeList() {
		$this->stdelog->write(4, 'getAttributeList() is called');

		$json = array();

		//$this->load->model('catalog/attribute');

		$filter_data = array(
			'product_id' => $this->request->get['product_id'],
		);

		$results = $this->model->getAttributes($filter_data);

		$attribute_list = array();

		foreach ($results as $result) {
			$attribute_list[] = array(
				'attribute_id' => $result['attribute_id'],
				'name'				 => $result['attribute_group'] . ' -- ' . str_replace(["\n", "\r"], ["&#10;", "&#13;"], strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))),
				//'attribute_group' => $result['attribute_group']
			);
		}

		$json['status']	 = 'success';
		$json['data']		 = $attribute_list;

		$this->stdelog->write(4, $json, '$json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getAllAttributeValues() {
		$this->stdelog->write(4, 'getAllAttributeValues() is called');

		$json = array();

		$attribute_all_values = array();

		$attribute_all_values = $this->model->getAllAttributeValues();

		$attribute_values = array();

		foreach ($attribute_all_values as $item) {
			$attribute_values[$item['attribute_id']][$item['language_id']][] = str_replace(["\n", "\r"], ["&#10;", "&#13;"], strip_tags(html_entity_decode($item['text'], ENT_QUOTES, 'UTF-8')));
		}

		$json['status']	 = 'success';
		$json['data']		 = $attribute_values;

		$this->stdelog->write(4, $json, 'getAttributeValues() :: $json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getAttributeValues() {
		$this->stdelog->write(4, 'getAttributeValues() is called');

		$json = array();

		$language_id = isset($this->request->get['language_id']) && $this->request->get['language_id'] ? $this->request->get['language_id'] : $this->config->get('config_language_id');

		$attribute_all_values = array();

		$attribute_all_values = $this->model->getAttributeValues($this->request->get['attribute_id'], $language_id);

		$attribute_values = array();

		foreach ($attribute_all_values as $attribute_all_value) {
			$attribute_values[] = array(
				'text' => str_replace(["\n", "\r"], ["&#10;", "&#13;"], strip_tags(html_entity_decode($attribute_all_value['text'], ENT_QUOTES, 'UTF-8'))),
			);
		}

		$json['status']	 = 'success';
		$json['data']		 = $attribute_values;

		$this->stdelog->write(4, $json, 'getAttributeValues() :: $json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getAllOptionValues() {
		$this->stdelog->write(4, 'getAllOptionValues() is called');

		$json = array();

		$option_values = $this->model->getAllOptionValues();

		$json['status'] = 'success';

		$this->stdelog->write(4, $option_values, 'getAllOptionValues() $option_values');

		// Option values array with option_id keys
		foreach ($option_values as $key => $value) {
			$json['data'][$value['option_id']][] = array(
				'option_value_id'	 => $value['option_value_id'],
				'name'						 => $value['name'],
			);
		}

		$this->stdelog->write(4, $json, 'getAllOptionValues() $json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getOptionsList() {
		$this->stdelog->write(4, 'getOptionsList() is called');

		$json = array();

		$a_options = $this->model->getOptionsList($this->request->get['product_id']);

		// Option array with option_id keys
		$json['data'] = array();

		foreach ($a_options as $key => $value) {
			$json['data'][$value['option_id']] = array(
				'option_id'	 => $value['option_id'],
				'name'			 => $value['name'],
				'type'			 => $value['type'],
			);
		}

		$json['status'] = 'success';

		$this->stdelog->write(4, $json['data'], 'getOptionsList() $json[\'data\']');

		$this->stdelog->write(4, $json, 'getOptionsList() $json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	public function getFiltersAutocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => $this->config->get('config_limit_autocomplete')
			);

			$filters = $this->model->getFilters($filter_data);

			foreach ($filters as $filter) {
				$json[] = array(
					'filter_id' => $filter['filter_id'],
					'name'      => strip_tags(html_entity_decode($filter['group'] . ' &gt; ' . $filter['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	/* Helper
	--------------------------------------------------------------------------- */
	private function standartField($key, $default_value = '') {
		if (!$key) {
			return false;
		}

		if (false === strpos($key, 'module_handy_')) {
			$key = 'module_handy_' . $key;
		}

		if (isset($this->request->post[$key])) {
			return $this->request->post[$key];
		} elseif ($this->config->get($key)) {
			return $this->config->get($key);
		} else {
			return $default_value;
		}

		return false;
	}



	/* Autocomplete
  --------------------------------------------------------------------------- */
	public function autocompleteManufacturer() {
		$this->stdelog->write(4, 'autocompleteManufacturer() :: is called');
		
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'start'       => 0,
				'limit'       => 5,
			);

			$results = $this->model->getManufacturers($filter_data);

			$this->stdelog->write(4, $results, 'autocompleteManufacturer() :: $results');

			foreach ($results as $result) {
				$json[] = array(
					'manufacturer_id' => $result['manufacturer_id'],
					'name'            => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}
		
		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}
		
		array_multisort($sort_order, SORT_ASC, $json);

		$this->stdelog->write(4, $json, 'autocompleteManufacturer() :: $json');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}




	/* SEO URL
  --------------------------------------------------------------------------- */
  public function getSeoUrlByAjax() {
		$this->stdelog->write(4, $this->request->post, 'getSeoUrlByAjax() :: $this->request->post');

		if (!isset($this->request->post)) return false;

    $data = array('result' => '');

		$formula = $this->config->get('module_handy_translit_formula');

		// Check data for formula
		$data['errors'] = array();

		if (false !== strpos($formula, '[product_name]')) {
			// A! name VS product_name
			if (!$this->request->post['name']) {
				$data['result'] = 'ERROR';
				$data['errors'][] = 'name';
			}
		}

		if (false !== strpos($formula, '[model]')) {
			if (!$this->request->post['model']) {
				$data['result'] = 'ERROR';
				$data['errors'][] = 'model';
			}
		}

		if (false !== strpos($formula, '[sku]')) {
			if (!$this->request->post['sku']) {
				$data['result'] = 'ERROR';
				$data['errors'][] = 'sku';
			}
		}

		if ('ERROR' != $data['result']) {
			unset($data['errors']);

			$result = $this->getSeoUrl($this->request->post);

			if ($result) {
				$data['result'] = $result;
			}
		}

    header('Content-type:application/json;charset=utf-8');
    echo json_encode($data);
    exit;
  }

	public function getSeoUrl($a_data) {
    /* Определить сущность
     * Определить, какие переменные есть в формуле
     * Вырезать из формулы лишние - (транслит сам это сделает)
     * Транлитировать
     * Запросить уникальность
     * Если URL не уникален, то использовать индекс N - причем, это не зависит от того, есть ли в формуле генерации доп переменные или нет
     */
		$this->stdelog->write(4, $a_data, 'getSeoUrl() :: $a_data');

    $keyword = '';

		$setting = array(
      'language_id' => $this->config->get('module_handy_language'),
      'translit_function' => $this->config->get('module_handy_translit_function'),
      'translit_formula' => $this->config->get('module_handy_translit_formula'),
    );

		$this->stdelog->write(4, $setting, 'getSeoUrl() :: $setting');

    if ($a_data['essence']) {
      if ('product' == $a_data['essence']) {
        $keyword = $this->handy->getProductKeywordByForumla($a_data, $setting);
      } else {
        $keyword = trim($a_data['name']);
      }
    }

    $keyword = $this->model->translit($keyword, $setting);

		$this->stdelog->write(4, $keyword, 'getSeoUrl() :: $keyword after translit');

    // Unique
    $keyword = $this->model->getUniqueUrl($keyword);

		$this->stdelog->write(4, $keyword, 'getSeoUrl() :: $keyword after unique');

    return $keyword;
  }




	/* Mass Edit
  --------------------------------------------------------------------------- */
	public function massEdit() {
		$this->load->model('setting/setting');
		$this->load->model('catalog/product');		
		$this->load->language('catalog/product');
		$this->load->language('extension/module/handy');

//		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
//			echo "----------------------------------------------------------------------"
//			. "</br>\$this->request->post</br>";
//			echo "<pre>";
//			print_r($this->request->post);
//			echo "</pre></br>";
//			exit;
//		}

		$this->document->setTitle($this->language->get('mass_edit_title'));
		
//		$this->document->addScript('view/javascript/4handy/jquery-ui-1.12.1/jquery-ui.min.js');
//		$this->document->addStyle('view/javascript/4handy/jquery-ui-1.12.1/jquery-ui.min.css');
//		A!
//    jquery ui conflict with input autocomplete [object Object]
//    it is possible to create custom js-file with resize only...
		$this->document->addStyle('view/stylesheet/handy.css');
		
		$this->document->addStyle('view/javascript/4handy/select2/select2.css');
		$this->document->addScript('view/javascript/4handy/select2/select2.min.js');
		
		// Text
		// A! Is not available in OC 3 for $this->customInclude()
		foreach ($this->language->all() as $key => $value) {
			$data[$key] = $value;
		}
		
		$data['entry_related'] = $this->clearLabel('entry_related');
		$data['entry_filter'] = $this->clearLabel('entry_filter');

		// H1
		$data['h1'] = $this->model->getH1();
		$data['handy_entry_h1']	= $this->language->get('entry_' . $data['h1']);
		

		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_2'),
			'href' => $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_handy_mass_edit'),
			'href' => $this->url->link('extension/module/handy/massEdit', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['link_part_settings']		 = $this->url->link('extension/module/handy', 'user_token=' . $this->session->data['user_token'], true);
		$data['link_part_productlist'] = $this->url->link('extension/module/handy/productList', 'user_token=' . $this->session->data['user_token'], true);
		$data['link_part_massedit']		 = $this->url->link('extension/module/handy/massEdit', 'user_token=' . $this->session->data['user_token'], true);

		// For OC 2 -- text_part_*...

		// Data
		$data['user_token'] = $this->session->data['user_token'];

		$data['debug'] = $this->config->get('module_handy_debug');			
		$data['a_product_list_field']	= $this->config->get('module_handy_product_list_field');

		$data['a_price_prefixes'] = is_array($this->config->get('module_handy_price_prefixes')) ? $this->config->get('module_handy_price_prefixes') : []; // for no error on front foreach
		
		$data['valid_licence'] = $this->handy->isValidLicence($this->config->get('module_handy_licence'));

		// Language
		$this->load->model('localisation/language');

		$data['languages']					 = $this->model_localisation_language->getLanguages();
		$data['config_language_id']	 = $this->config->get('config_language_id');
		$data['config_language']		 = $this->config->get('config_language'); // check versions

//		$data['a_languages_for_js'] = array();
//
//		foreach ($data['languages'] as $key => $value) {
//			$data['a_languages_for_js'][] = $value['language_id'];
//		}
//
//		$data['a_languages_for_js'] = json_encode($data['a_languages_for_js']);

		// Store
		$this->load->model('setting/store');

		// A! Is different for OpenCart 2 && view alsoo!
		// 
		// A! It is in OpenCart 3 only
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);

		// Common for all OpenCart 2 & 3
		$stores = $this->model_setting_store->getStores();

		// A! It is in OpenCart 3 only
		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		// Category
		$this->load->model('catalog/category');

		$filter_data = array(
			'sort'	 => 'name',
			'order'	 => 'ASC'
		);

		$data['all_categories'] = $this->model_catalog_category->getCategories($filter_data);

		$data['has_main_category_column'] = $this->model->hasMainCategoryColumn();

		//$data['getProductMainCategoryIdExist'] = is_callable([$this->model_catalog_product, 'getProductMainCategoryId']);
		$data['getProductMainCategoryIdExist'] = is_callable([new ModelCatalogProduct($this->registry), 'getProductMainCategoryId']);

		// Category Tree for js
		$data['categories'] = array();

		$categories = $this->model->getCategoriesLevel1();

		foreach ($categories as $category_id) {
			$data['categories'][] = $this->model->getDescendantsTreeForCategory($category_id);
		}

		$handy_filter_categories_selected = array();

		if (isset($this->request->post['handy_filter']['category'])) {
			$handy_filter_categories_selected = $this->request->post['handy_filter']['category'];
		}

		$data['handy_filter_category_tree'] = $this->handy->getCategoriesList($data['categories'], $handy_filter_categories_selected, $level = 1, 'handy_filter[category]');

		$categories_selected = array();

		if (isset($this->request->post['categories'])) {
			$categories_selected = $this->request->post['categories'];
		}

		$data['category_tree'] = $this->handy->getCategoriesList($data['categories'], $categories_selected, $level = 1, 'categories');
		
		$data['categories_mode'] = $this->config->get('module_handy_categories_mode');
		
		// List Fields
		$data['a_product_list_field'] = $this->config->get('module_handy_product_list_field');

		// Custom Fields
		$data['a_product_list_field_custom'] = $this->config->get('module_handy_product_list_field_custom');

		if (!$data['a_product_list_field_custom']) {
			$data['a_product_list_field_custom'] = array();
		}

		// Не показывать поля, у которых не включен статус
		foreach ($data['a_product_list_field_custom'] as $field => $value) {
			if ('on' != $value['status']) {
				unset($data['a_product_list_field_custom'][$field]);
			}
		}

		// Separate Custom Fields With Price
		$data['a_product_list_field_custom_price'] = array();

		foreach ($data['a_product_list_field_custom'] as $field => $value) {
			if ('price' == $value['field_type']) {
				$data['a_product_list_field_custom_price'][$field] = $data['a_product_list_field_custom'][$field];
				unset($data['a_product_list_field_custom'][$field]);
			}
		}

		// Weight Class
		$this->load->model('localisation/weight_class');

		$data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses();
		
		// Length Class
		$this->load->model('localisation/length_class');

		$data['length_classes'] = $this->model_localisation_length_class->getLengthClasses();

		// Manufacturer
		$this->load->model('catalog/manufacturer');

		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers([]);

		// Stock Status
		$this->load->model('localisation/stock_status');

		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();

		// Tax Class
		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		// Attributes
		$this->load->model('catalog/attribute');

		$data['attributes'] = $this->model_catalog_attribute->getAttributes();

		// Fix apostrophe in attribute name & attribute group & \r\n
		foreach ($data['attributes'] as $key => $attribute) {
			$data['attributes'][$key]['name'] = str_replace(["\n", "\r"], ["&#10;", "&#13;"], htmlspecialchars($attribute['name'], ENT_QUOTES, 'UTF-8'));
			$data['attributes'][$key]['attribute_group'] = htmlspecialchars($attribute['attribute_group'], ENT_QUOTES, 'UTF-8');
		}

		// Option
		$this->load->model('catalog/option');

		$options = $this->model_catalog_option->getOptions();

		// prevent: Uncaught SyntaxError: unexpected token: identifier . begin
		// ex: I DON'T NEED
		// A! При AJAX - работает даже с апострофами. А когда выдают json просто в строку, то беда.
		// Опять же, получается, что миме-тип по ходу тут html, а не json... А там хз
		
		$data['options'] = [];
		
		foreach ($options as $key => $value) {
			$value['name'] = str_replace("'", '&#39;', $value['name']);
			$data['options'][$key] = $value;
		}
		// prevent: Uncaught SyntaxError: unexpected token: identifier . end		

		// for js
		$data['optionsExist'] = array();

		foreach ($data['options'] as $key => $value) {
			// Fix for option tooltip . begin
			if (isset($value['tooltip'])) {
				//$value['tooltip'] = str_replace(["\r\n", "\r", "\n"], " ", $value['tooltip']); // Было подозрение на наличие в коде переноса строк - но проблема так и не была решена. А в интерфейсе модуля это все равно не выводится.
				
				unset($value['tooltip']);
			}
			// Fix for option tooltip . end
			
			$data['optionsExist'][$value['option_id']] = $value;
		}

		if (count($data['optionsExist']) > 0) {
			$data['optionsExist'] = json_encode($data['optionsExist'], JSON_HEX_APOS | JSON_HEX_QUOT);
		} else {
			$data['optionsExist'] = '""'; // prevent Uncaught SyntaxError: JSON.parse: unexpected end of data at line 1 column 1 of the JSON data (with empty string)
		}

		// Customer Group
		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['text_version'] = sprintf($this->language->get('text_version'), HANDY_VERSION);
		$data['check_license'] = '';
		//$data['check_license'] = sprintf($this->language->get('check_license'), HTTPS_CATALOG);

		// Parts
		$data['header']			 = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']			 = $this->load->controller('common/footer');
		
		$data['filter_flag'] = 'mass_edit';
		$data['filter_reset_text'] = $this->language->get('handy_filter_reset_mass_edit');
		$data['filter_reset_link'] = $data['link_part_massedit'];
		
		// Filter
		// POST is in massEditProcessing only! so set null as default values for all params
		$data['handy_filter'] = $this->helperGetURLParamsFromGet();
		
		$this->stdelog->write(4, $data['handy_filter'], 'massEdit() $data["handy_filter"]');
		
		$data['include_filter']	= $this->filter($data);
		
		// For OC3 only - get included files for our template file
		$data['js_mass_edit'] = $this->customInclude(DIR_APPLICATION . 'view/template/extension/module/handy_mass_edit_js.tpl', $data);

		// Render view
		$this->response->setOutput($this->load->view('extension/module/handy_mass_edit', $data));
	}


	public function massEditProcessing() {
		// Делаю сложный запрос, в котором есть подзапрос на поиск товаров по заданному фильтру
		// Так как запросы не односложные (переменные в описаниях), необходимость затирать и доавлять фильтры, опции и категории,
		// то варинат с одним запросом отпадает!
		// Шаг 1: Обрабатываю полученные данные
		// Шаг 2: Получаю список товаров
		// Шаг 3: Поочередно выполняю запросы
		// - Описания - поочередно для каждого товара (или замена подстрок прямо в SQL - если только такое возможно)
		// - Данные таблицы товаров + опять же плюсация к цене - в php или MySQL?
		// - Доп данные, вроде скидки, категории, магазины,

		//sleep(1);

		$json = array();

		$this->load->model('setting/setting');

		$this->load->model('catalog/product');

		$this->load->language('extension/module/handy');

		if (!$this->handy->isValidLicence($this->config->get('module_handy_licence'))) {
			$json['status'] = 'Error';
			$json['answer'] = $this->language->get('text_input_licence_mass');

			goto block_finish;
		}

		// Delete Old Logs
		$this->stdelog->write(4, 'massEditProcessing() :: prepare to $this->stdelog->deleteOld()');

		$logdelete_time = -microtime(true);

		$this->stdelog->deleteOld();

		$logdelete_time += microtime(true);

		$this->stdelog->write(4, number_format($logdelete_time, 6) . ' seconds', 'massEditProcessing() :: $this->stdelog->deleteOld() is finished in');

		// POST
		$this->stdelog->write(4, $this->request->post, 'massEditProcessing() :: $this->request->post');

    $handy_filter = $this->helperPrepareParamsFromPost();
    
    $this->stdelog->write(4, $handy_filter, 'massEditProcessing() :: $handy_filter');

		// H1
		$h1 = $this->model->getH1();

		if ('POST' === $this->request->server['REQUEST_METHOD']) {

			$this->stdelog->write(4, $this->request->post['description'], 'Description POST in begin: $this->request->post[\'description\']');
			
			// A! Note 2-A
			// Check if not empty FORM DATA!
			// I can check POST. But there are many not important data
			// Also each important field is checked in time of first itteration
			// So I don't have to check it twice
			/*
			$formdata = $this->request->post;
			unset($formdata['handy_filter']);
			unset($formdata['handy_filter_manufacturer_input']); // Q? For why it is separatrely in the form?
			
			// not important data
			unset($formdata['category_flag']);
			unset($formdata['round_flag']);
			unset($formdata['attribute_flag']);
			unset($formdata['option_flag']);
			unset($formdata['handy_new_submit']);
			unset($formdata['flag_mass_edit']);
			unset($formdata['step']);
			*/
			
			$step = $this->request->post['step'];

			// limit
			//$limit_n = $this->config->get('module_handy_limit');
			$limit_n = 500;

			// default 200
			if (!$limit_n) {
				$limit_n = 200;
			}

			$setting = array(
				'language_id'	=> $this->config->get('module_handy_language'),
				'translit_function' => $this->config->get('module_handy_translit_function'),
				'translit_formula' => $this->config->get('module_handy_translit_formula'),
			);

			// Get products number
			$n_products = $this->model->filterCountProducts($handy_filter);

			if (false === $n_products) {
				$json['status'] = 'Error';
				$json['answer']	= $this->language->get('error_no_count');
				goto block_finish;
			}

			if (0 == $n_products) {
				$json['status'] = 'Error';
				$json['answer']	= $this->language->get('error_no_products');
				goto block_finish;
			}

			if (1 == $step) {
				$this->session->data['steps_all'] = $steps_all = ceil($n_products / $limit_n);
			} else {
				$steps_all = $this->session->data['steps_all'];
			}

			// A!
			// При удалении смещение в LIMIT не нужно!
			// Ведь по сути товаров с первого шага больше нет. Некуда смещаться от начала новой выборки
			if (isset($this->request->post['delete_product_flag'])) {
				$limits = array(
					'first_element' => 0,
					'limit_n' => $limit_n
				);
			} else {
				$limits = array(
					'first_element' => $limit_n * $step - $limit_n,
					'limit_n' => $limit_n
				);
			}

			// Products array
			$products = $this->model->filterGetProducts($handy_filter, $limits);

			$mass_edit_processed = false;

			if (count($products) > 0) {
				$this->stdelog->write(4, $products, 'massEditProcessing() :: $products');

				// Dump DB
//				$this->stdelog->write(4, 'massEditProcessing() :: prepare to $this->dump()');
//				
//				$dump_time = -microtime(true);
//				
//				$this->dump();
//				
//				$dump_time += microtime(true);
//				
//				$this->stdelog->write(4, number_format($dump_time, 6) . ' seconds', 'massEditProcessing() :: $this->dump() is finished in');

				
				### Mass Edit Description Detect ###
				$this->stdelog->write(4, $this->request->post['description'], 'Description POST: $this->request->post["description"]');
				
				$description_is_used = false;

				foreach	($this->request->post['description'] as $language_id => $value) {
					foreach ($value as $key => $item) {
						if (!$this->helperIsEmptyString($item)) {
							$description_is_used = true;
							break;
						}
					}							
				}
				
				// Mass Edit Description Include Randomizer
				if ($description_is_used) {
					$this->stdelog->write(4, 'Description is used for this query');	
					require_once DIR_SYSTEM . 'library/handy/TextRandomizer.php';
					$tRand = new Natty_TextRandomizer();
				}
				
				// Mass Edit Description Prepare Inputed Values
				$inputed_description = array();

				foreach ($this->request->post['description'] as $language_id => $value) {
					foreach ($value as $key => $item) {
						if (!$this->helperIsEmptyString($item)) {
							$inputed_description[$language_id][$key] = $item;
						}
					}
				}
				
				### Поодиночное редактирование отдельно взятого товара ###
				foreach ($products as $product_id) {
					$this->stdelog->write(4, $product_id, '$product_id');

					$product_was_edit = false;

					// Delete
					if (isset($this->request->post['delete_product_flag'])) {
						$this->stdelog->write(4, $product_id, 'DELETE product_id');
						
						$this->model->deleteProduct($product_id);
						
						$mass_edit_processed = true;
						
						continue;
					}
					

					// SEO URL
					// Not available in handy for OpenCart 3

					// Category
					$this->stdelog->write(4, 'Prepare Category');

					if (isset($this->request->post['categories']) || (isset($this->request->post['main_category_id']) && $this->request->post['main_category_id']) || (isset($this->request->post['category_flag']) && 'delete' == $this->request->post['category_flag'])) {
						$data = array(
							'product_id' => $product_id,
							'main_category_id' => isset($this->request->post['main_category_id']) ? $this->request->post['main_category_id'] : false,
							'category_flag' => $this->request->post['category_flag'],
							'categories' => isset($this->request->post['categories']) ? $this->request->post['categories'] : array(),
						);

						$this->model->massEditCategory($data);
						
						$product_was_edit = true;
					}

					// Manufacturer
					$this->stdelog->write(4, 'Prepare Manufacturer');

					// is required
					if ($this->request->post['manufacturer_id']) {
						$this->model->massEditManufacturer($this->request->post['manufacturer_id'], $product_id);

						$product_was_edit = true;
					}
					
					// Model
					$this->stdelog->write(4, 'Prepare Model');
					if (isset($this->request->post['model']) && $this->request->post['model']) {
						$this->model->editProductIdentity(['field' => 'model', 'value' => $this->request->post['model'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}
					
					// SKU
					$this->stdelog->write(4, 'Prepare SKU');
					if (isset($this->request->post['sku']) && $this->request->post['sku']) {
						$this->model->editProductIdentity(['field' => 'sku', 'value' => $this->request->post['sku'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}
					
					// UPC
					$this->stdelog->write(4, 'Prepare UPC');
					if (isset($this->request->post['upc']) && $this->request->post['upc']) {
						$this->model->editProductIdentity(['field' => 'upc', 'value' => $this->request->post['upc'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}
					
					// EAN
					$this->stdelog->write(4, 'Prepare EAN');
					if (isset($this->request->post['ean']) && $this->request->post['ean']) {
						$this->model->editProductIdentity(['field' => 'ean', 'value' => $this->request->post['ean'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}
					
					// JAN
					$this->stdelog->write(4, 'Prepare JAN');
					if (isset($this->request->post['jan']) && $this->request->post['jan']) {
						$this->model->editProductIdentity(['field' => 'jan', 'value' => $this->request->post['jan'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}
					
					// ISBN
					$this->stdelog->write(4, 'Prepare ISBN');
					if (isset($this->request->post['isbn']) && $this->request->post['isbn']) {
						$this->model->editProductIdentity(['field' => 'isbn', 'value' => $this->request->post['isbn'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}
					
					// MPN
					$this->stdelog->write(4, 'Prepare MPN');
					if (isset($this->request->post['mpn']) && $this->request->post['mpn']) {
						$this->model->editProductIdentity(['field' => 'mpn', 'value' => $this->request->post['mpn'], 'product_id' => $product_id]);
						$product_was_edit = true;
					}

					// Price
					$this->stdelog->write(4, 'Prepare Price');

					$this->request->post['price'] = trim($this->request->post['price']);

					// is required
					if ($this->request->post['price'] || '0' === $this->request->post['price']) {
						$this->model->massEditPriceField('price', $this->request->post['price'], $product_id);

						$product_was_edit = true;
					}

					// Custom Fields With Price
					$this->stdelog->write(4, 'Prepare Custom Fields With Price');

					$data['a_product_list_field_custom'] = $this->config->get('module_handy_product_list_field_custom');

					if (!$data['a_product_list_field_custom']) {
						$data['a_product_list_field_custom'] = array();
					}

					foreach ($data['a_product_list_field_custom'] as $field => $value) {
						if ('on' != $value['status']) {
							unset($data['a_product_list_field_custom'][$field]);
						}
					}

					$data['a_product_list_field_custom_price'] = array();

					foreach ($data['a_product_list_field_custom'] as $field => $value) {
						if ('price' == $value['field_type']) {
							$data['a_product_list_field_custom_price'][$field] = $data['a_product_list_field_custom'][$field];
							unset($data['a_product_list_field_custom'][$field]);
						}
					}

					foreach ($data['a_product_list_field_custom_price'] as $field => $item) {
						if (isset($this->request->post[$field]) && $this->request->post[$field]) {
							$this->model->massEditPriceField($field, $this->request->post[$field], $product_id);

							$product_was_edit = true;
						}
					}

					// Discount
					$this->stdelog->write(4, 'Prepare Discount');

					if (isset($this->request->post['discount'])) {
						$this->model->massEditDiscount($this->request->post['discount'], $product_id);
						
						$product_was_edit = true;
					}

					// Special
					$this->stdelog->write(4, 'Prepare Special');

					if (isset($this->request->post['special'])) {
						$this->model->massEditSpecial($this->request->post['special'], $product_id);

						$product_was_edit = true;
					}

					// Points
					$this->stdelog->write(4, 'Prepare Points');

					if (isset($this->request->post['points']) && $this->request->post['points']) {
						$this->model->massEditPoints($this->request->post['points'], $product_id);

						$product_was_edit = true;
					}

					// Product Reward
					$this->stdelog->write(4, 'Prepare Product Reward');

					if (isset($this->request->post['product_reward']) && !$this->helperIsMultiArrayEmpty($this->request->post['product_reward'])) {
						$this->model->massEditProductReward($this->request->post['product_reward'], $product_id);

						$product_was_edit = true;
					}

					// Quantity
					$this->stdelog->write(4, 'Prepare Quantity');

					$this->request->post['quantity'] = trim($this->request->post['quantity']);

					if ($this->request->post['quantity'] || '0' === $this->request->post['quantity']) {
						$this->model->massEditQuantityField($this->request->post['quantity'], $product_id);

						$product_was_edit = true;
					}

					// Minimum
					$this->stdelog->write(4, 'Prepare Minimum');

					if (isset($this->request->post['minimum'])) {
						$this->request->post['minimum'] = trim($this->request->post['minimum']);

						if (!empty($this->request->post['minimum'])) {
							$this->model->massEditMinimumField($this->request->post['minimum'], $product_id);

							$product_was_edit = true;
						}
					}
					
					// Weight
					$this->stdelog->write(4, 'Prepare Weight');

					if (isset($this->request->post['weight']) && ($this->request->post['weight'] || '0' === $this->request->post['weight'])) {
						$this->model->massEditWeightField(trim($this->request->post['weight']), $product_id);

						$product_was_edit = true;
					}

					// Weight Class
					$this->stdelog->write(4, 'Prepare Weight Class');

					if (isset($this->request->post['weight_class_id']) && '*' != $this->request->post['weight_class_id']) {
						$this->model->massEditWeightClassField($this->request->post['weight_class_id'], $product_id);
						
						$product_was_edit = true;
					}
					
					// Length (Dimension)
					$this->stdelog->write(4, 'Prepare Length (Dimension)');
					
					if (
						(isset($this->request->post['length']) && ($this->request->post['length'] || '0' === $this->request->post['length']))
						|| (isset($this->request->post['width']) && ($this->request->post['width'] || '0' === $this->request->post['width']))
						|| (isset($this->request->post['height']) && ($this->request->post['height'] || '0' === $this->request->post['height']))
					) {
						$this->model->massEditDimensionFields([
							'length' => trim($this->request->post['length']),
							'width' => trim($this->request->post['width']),
							'height' => trim($this->request->post['height']),
						], $product_id);

						$product_was_edit = true;
					}

					// Length Class
					$this->stdelog->write(4, 'Prepare Length Class');

					if (isset($this->request->post['length_class_id']) && '*' != $this->request->post['length_class_id']) {
						$this->model->massEditLengthClassField($this->request->post['length_class_id'], $product_id);
						
						$product_was_edit = true;
					}

					// Stock Status
					$this->stdelog->write(4, 'Prepare Stock Status');

					if (isset($this->request->post['stock_status_id']) && '*' != $this->request->post['stock_status_id']) {
						$this->model->massEditStockStatusField($this->request->post['stock_status_id'], $product_id);
					}

					// Status
					$this->stdelog->write(4, 'Prepare Status');

					if (isset($this->request->post['status']) && '*' != $this->request->post['status']) {
						$this->model->massEditStatusField($this->request->post['status'], $product_id);

						$product_was_edit = true;
					}

					// Tax Class
					$this->stdelog->write(4, 'Prepare Tax Class');

					if (isset($this->request->post['tax_class_id']) && '*' != $this->request->post['tax_class_id']) {
						$this->model->massEditTaxClassField($this->request->post['tax_class_id'], $product_id);
						
						$product_was_edit = true;
					}

					// Noindex
					$this->stdelog->write(4, 'Prepare Noindex');

					if (isset($this->request->post['noindex']) && '*' != $this->request->post['noindex']) {
						$this->model->massEditNoindexField($this->request->post['noindex'], $product_id);
						
						$product_was_edit = true;
					}

					// Subtract
					$this->stdelog->write(4, 'Prepare Subtract');

					if (isset($this->request->post['subtract']) && '*' != $this->request->post['subtract']) {
						$this->model->massEditSubtractField($this->request->post['subtract'], $product_id);

						$product_was_edit = true;
					}

					// Shipping
					$this->stdelog->write(4, 'Prepare Shipping');

					if (isset($this->request->post['shipping']) && '*' != $this->request->post['shipping']) {
						$this->model->massEditShippingField($this->request->post['shipping'], $product_id);

						$product_was_edit = true;
					}

					// Sort Order
					$this->stdelog->write(4, 'Prepare Sort Order');

					if (isset($this->request->post['sort_order']) && '' != $this->request->post['sort_order']) {
						$this->model->massEditSimpleField('sort_order', $this->request->post['sort_order'], $product_id);
						
						$product_was_edit = true;
					}

					// Date Available
					$this->stdelog->write(4, 'Prepare Date Available');

					if (isset($this->request->post['date_available']) && !empty($this->request->post['date_available'])) {
						$this->request->post['date_available'] = trim($this->request->post['date_available']);
						
						$this->model->massEditDateAvailableField($this->request->post['date_available'], $product_id);

						$product_was_edit = true;
					}
					
					// Date Added
					$this->stdelog->write(4, 'Prepare Date Added');

					if (isset($this->request->post['date_added']) && !empty($this->request->post['date_added'])) {
						$this->request->post['date_added'] = trim($this->request->post['date_added']);
						
						$this->model->massEditDateAddedField($this->request->post['date_added'], $product_id);

						$product_was_edit = true;
					}
					
					// Date Modified
					$this->stdelog->write(4, 'Prepare Date Modified');

					if (isset($this->request->post['date_modified']) && !empty($this->request->post['date_modified'])) {
						$this->request->post['date_modified'] = trim($this->request->post['date_modified']);
						
						$this->model->massEditDateModifiedField($this->request->post['date_modified'], $product_id);

						$product_was_edit = true;
					}

					// Product Store
					$this->stdelog->write(4, 'Prepare Product Store');

					if (isset($this->request->post['product_store'])) {
						$this->model->massEditProductStore($this->request->post['product_store'], $product_id);
						
						$product_was_edit = true;
					}
					
					// Product Related
					$this->stdelog->write(4, 'Prepare Product Related');

					if (isset($this->request->post['product_related'])) {
						$this->model->massEditProductRelated($this->request->post['product_related'], $product_id);
						
						$product_was_edit = true;
					}
					
					// Product Related Delete
					$this->stdelog->write(4, 'Prepare Product Related Delete');

					if (isset($this->request->post['product_related_delete'])) {
						$this->model->massEditProductRelatedDelete($this->request->post['product_related_delete'], $product_id);
						
						$product_was_edit = true;
					}

					// Product Filter
					$this->stdelog->write(4, 'Prepare Product Filter');

					if (isset($this->request->post['product_filter'])) {
						$this->model->massEditProductFilter($this->request->post['product_filter'], $product_id);
						
						$product_was_edit = true;
					}
					
					// Product Filter Delete
					$this->stdelog->write(4, 'Prepare Product Filter Delete');

					if (isset($this->request->post['product_filter_delete'])) {
						$this->model->massEditProductFilterDelete($this->request->post['product_filter_delete'], $product_id);
						
						$product_was_edit = true;
					}

					// Description
						$this->stdelog->write(4, 'Prepare Description');

					if ($description_is_used) {
						$product_info = $this->model_catalog_product->getProduct($product_id);
						$product_description = $this->model_catalog_product->getProductDescriptions($product_id); // for [original_text] and description flag

						$this->stdelog->write(4, $inputed_description, 'Description $inputed_description');
						$this->stdelog->write(4, $product_description, 'Description : $product_description');

						$manufacturer = $this->model->getManufacturerNameById($product_info['manufacturer_id']);

						foreach	($inputed_description as $language_id => $inputed_values) {
							// Важно!
							// Когда добавили новый язык, но напарсили только в исходные, получается что в product_description нет записей для второго языка
							
							// TODO...
							// Q? Добре, а якщо людина хоче якраз таки додати описи товарам, які не мають опису...
							// А на що це вприває? Мабуть були помилки там, де немає початкового тексту за бази даних...
							
							if (!isset($product_description[$language_id])) {
								$this->stdelog->write(4, 'massEditDescription() :: $product_description[$language_id] !isset. Skip this itteration');
								$this->log->write('Handy Product Manager:: Mass text generation is impossible. No data for language_id = ' . $language_id . ' and product_id = ' . $product_id);
								continue;
							}						
							
							// Inittially just copy $inputed_values and then transform for each product and each language
							$queue_new_description = $inputed_values;

							// Flag
							$description_flag = false;
							if (isset($this->request->post['description_flag'][$language_id])) {
								$description_flag = true;
							}
							$this->stdelog->write(3, $description_flag, '$description_flag for lanugage_id ' . $language_id);
							$this->stdelog->write(3, $product_description[$language_id], '$product_description[$language_id] for lanugage_id ' . $language_id);

							// Якщо поля в базі порожні, то нехай залишаються для "генерації"
							// Але якщо вони не порожні, то лише за наявності $description_flag = true
							foreach ($product_description[$language_id] as $key => $value_from_db) {
								if (!$this->helperIsEmptyString($value_from_db) && isset($queue_new_description[$key]) && false === $description_flag) {
									unset($queue_new_description[$key]);
								}
							}

							$this->stdelog->write(3, $queue_new_description, '$queue_new_description after flag checking for lanugage_id ' . $language_id);

							### Variables ###
							$search = array(
								'&nbsp;',
								'[product_name]',
								'[manufacturer]',
								'[manufacturer_name]', // synonym (1)
								'[sku]',
								'[model]',
								'[original_text]', // Note 1-A: must be changed for each field (!)
							);

							$replace = array(
								'',
								$product_description[$language_id]['name'],
								$manufacturer,
								$manufacturer, // for synonym (2)
								$product_info['sku'],
								$product_info['model'],
								$product_description[$language_id]['name'], // Note 1-B: set default value for first processed field (name)
							);

							$fields_to_process = array(
								'name',
								$h1,
								'meta_title',
								'meta_description',
								'meta_keyword',
								'tag',
							);

							$description_with_text_processed = array();
							
							foreach ($fields_to_process as $field) {						
								if (isset($queue_new_description[$field]) && !$this->helperIsEmptyString($queue_new_description[$field])) {
									$replace[6] = $product_description[$language_id][$field]; // Note 1-B - [original_text]
									
									if ('name' == $field) {
										$description_with_text_processed['name'] = $this->model->replaceVars($search, $replace, $inputed_values['name']);
										$replace[1] = $description_with_text_processed['name']; // Update [product_name] for other text fields!
									} else {
										$tRand->setText($this->model->replaceVars($search, $replace, $inputed_values[$field]));
										$description_with_text_processed[$field] = $tRand->getText();
									}

									$product_was_edit = true;
								}								
							}

							// Description Separatelly - Why???
							if (isset($queue_new_description['description'])) {
								$new_description = html_entity_decode($queue_new_description['description']);
//							$this->stdelog->write(4, $new_description, '$new_description:html_entity_decode');

								$new_description = strip_tags($new_description);
//							$this->stdelog->write(4, $new_description, '$new_description:strip_tags');

								$new_description = preg_replace(array('/&nbsp;/', '/\s+/'), array('', ''), $new_description);

//							$this->stdelog->write(4, $new_description, '$new_description:str_replace');

								if (!empty($new_description)) {
									$this->stdelog->write(3, $new_description, 'if (!empty($new_description)) {');

									$replace[6] = $product_description[$language_id]['description']; // Note 1-C

									$tRand->setText($this->model->replaceVars($search, $replace, $queue_new_description['description']));
									$description_with_text_processed['description'] = $tRand->getText();

									$product_was_edit = true;
								}
							}

							$this->stdelog->write(4, $description_with_text_processed, 'prepare to call massEditDescription() :: with $description_with_text_processed description (language_id: ' . $language_id . ')');

							// Запрос в базу для каждого языка по отдельности
							$this->model->massEditDescription($description_with_text_processed, $product_id, $language_id, $description_flag);

							unset($description_with_text_processed);
						}
					} else {
						$this->stdelog->write(4, 'Description nothing to do');
					}

					// Attribute
					if (isset($this->request->post['attribute'])) {
						$data_input = array(
							'product_id' => $product_id,
							'attribute_flag' => $this->request->post['attribute_flag'],
							'attribute' => $this->request->post['attribute'],
							'attribute_value' => $this->request->post['attribute_value'],
						);

						$this->model->massEditAttribute($data_input);

						$product_was_edit = true;

						unset($data_input);
					}

					// Option
					if (isset($this->request->post['option'])) {
						$data_input = array(
							'product_id' => $product_id,
							'option_flag' => $this->request->post['option_flag'],
							'option' => $this->request->post['option'],
						);

						$this->model->massEditOption($data_input);

						$product_was_edit = true;

						unset($data_input);
					}

					if ($product_was_edit) {
						$this->model->massEditDate($product_id);
					}

					if ($product_was_edit) {
						$mass_edit_processed = true;
					} else {
						// A! Note 2-B
						// no data to edit
						break;
					}
				}
			}

			if (!isset($json['status'])) {
				// success

				if ($step == $steps_all) {
					$this->session->data['steps_all'] = null;
						
					$json['status'] = 'Finish';
					$json['answer'] = $this->language->get('success_item_step_finish');

					unset($this->session->data['handy']);

					// is different for OC 3
					if ($this->config->get('config_seo_pro')) {
						$this->cache->delete('seopro');
					}

					$this->model->callByMassEdit();

				} else {
					$json['status'] = 'Continue';
					$json['answer'] = sprintf($this->language->get('success_item_step'), $step, $steps_all);
				}

				// A!
				if (!$mass_edit_processed) {
					$json['status'] = 'Error';
					$json['answer'] = $this->language->get('error_nothing_todo');
				}

			} else {
				// Error - Processing (Are there any expected errors????)
				$json['status'] = 'Error';
				$json['answer'] = sprintf($this->language->get('error_item_step'), $step, $steps_all);
			}

			$this->stdelog->write(4, $json, '$json');

			$json['step'] = $step++;

			$json['steps_all'] = $steps_all;
		} else {
			// Error (Validation)
			if (isset($this->error['errors'])) {
				$json['status'] = 'Error';
				$json['answer'] = '';

				unset($this->session->data['handy']);

				$i = 0;
				foreach ($this->error['errors'] as $error) {
					$json['answer'] .= $i ? '<br>' : '';
					// description is array error...
					if (is_string($error)) {
						$json['answer'] .= $error;
					} elseif(is_array($error)) {
						foreach ($error as $error_item) {
							$n = 0;
							foreach ($error_item as $item) {
								$json['answer'] .= $n ? '<br>' : '';
								$json['answer'] .= $item;
							}
						}
					}

					$i++;
				}
			}
		}

		block_finish:

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	
	
	
	
	/* Dump
  --------------------------------------------------------------------------- */
	public function dump() {
		// Зависает безбожно, лучше выйти
		return false;

		$json = [
			'answer' => '',
			'error' => false,
		];

		$handy_backup_dir = DIR_SYSTEM . 'library/handy/backup/';

		if (!is_writable($handy_backup_dir)) {
			$json['error'] = 'no_permissions';

			goto dump_end;
		}
		
		$path_to_save = $handy_backup_dir . 'db__' . date('Y-m-d__H-i-s') . '.sql.gz';
		
		if (!$json['error']) {
			// dump . begin
			$file_inc = DIR_SYSTEM . 'library/handy/dg/mysql-dump/src/MySQLDump.php';
		
			require_once $file_inc;		
		
			$dump = new MySQLDump(new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE));
		
			$dump->save($path_to_save);
			// dump . end
		}
		
		if (!$json['error']) {
			$json['answer'] = $this->language->get('db_backup_is_created');
		}
		
		dump_end:
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	
	public function dumpDeleteOld() {
		// Удаляем все, что старше 3 дней		
		$handy_backup_dir = DIR_SYSTEM . 'library/handy/backup/';
		
		$iterator = new DirectoryIterator($handy_backup_dir);
		
		foreach ($iterator as $item) {
			if (!$item->isDot() && $item->isFile()) {
				if (time() - filemtime($handy_backup_dir . $item->getFilename()) > 60 * 60 * 24 * 3) {
					unlink($handy_backup_dir . $item->getFilename());
				}				
			}
		}
		
		return;
	}


	/* Licence
  --------------------------------------------------------------------------- */
  public function saveLicence() {
    $this->load->model('setting/setting');

    $this->load->language('extension/module/handy');

    $json = array();

    $licence = trim($this->request->post['licence']);

    if ($this->handy->isValidLicence($licence)) {
      $this->model_setting_setting->editSetting('module_handy', array('module_handy_licence' => $licence));
      $json['success'] = $this->language->get('success_licence');
    } else {
      $json['error'] = $this->language->get('error_licence');
    }

    header("Content-type: application/json; charset=UTF-8");
    echo json_encode($json);
    exit;
  }

	public function clearLabel($key) {
		$this->load->language('catalog/product');
		
//		echo '$key = ' . $key . '<br>';
//		echo $this->language->get($key) . '<br>';
		
		return rtrim($this->language->get($key), ':');
	}

	public function customInclude($full_path, $data) {
		extract($data);
		
//		echo "---<br>" . PHP_EOL;
//		echo "\$data<br>" . PHP_EOL;
//		echo "<pre>" . PHP_EOL;
//		print_r($data) . PHP_EOL;
//		echo "</pre>" . PHP_EOL;
//		
//		exit;
		
		ob_start();
		
		if (is_file(modification($full_path))) {
			include (modification($full_path));
		} elseif(is_file($full_path)) {
			include ($full_path);
		} else {
			echo "ERROR: File <strong>$full_path</strong> is not exist!<br>";
		}
		
		$content = ob_get_contents();
		ob_end_clean();
		
		return $content;
		
	}
	
	/* Helper
  --------------------------------------------------------------------------- */
	public function helperURLParams() {
		return [
			'main_category_id',
			'category',
			'category_flag',
			'manufacturer',
			'name',
			'name_flag',
			'product_id',
			'keyword',
			'model',
			'sku',
			'upc',
			'ean',
			'jan',
			'isbn',
			'mpn',
			'status',
			'image',
			'doubles',
			'option',
			'date_added_from',
			'date_added_before',
			'date_modified_from',
			'date_modified_before',
			'date_available_from',
			'date_available_before',
			'price_min',
			'price_max',
			'tax_class_id',			
			'quantity_min',
			'quantity_max',
			'weight_min',
			'weight_max',
			'weight_class_id',
			'length_min',
			'length_max',
			'width_min',
			'width_max',
			'height_min',
			'height_max',			
			'length_class_id',		
			'attribute',
			'attribute_value',
			'sort',
			'order',
			'page'
		];
	}
	
	public function helperGetURLParamsFromGet() {		
		foreach ($this->helperURLParams() as $param) {
			//$data[$param] = $this->request->get[$param] ?? null; // Error 500 on PHP 5.6...
			
			$data[$param] = (isset($this->request->get[$param])) ? $this->request->get[$param] : null;
		}
		
		$data['sort']	 = (isset($this->request->get['sort'])) ? $this->request->get['sort'] : 'p.product_id/DESC';
//		$data['order'] = (isset($this->request->get['order'])) ? $this->request->get['order'] : 'DESC';
		$data['page']	 = (isset($this->request->get['page'])) ? $this->request->get['page'] : 1;

		return $data;
	}
	
	public function helperPrepareParamsFromPost() {
		foreach ($this->helperURLParams() as $param) {
			$data[$param] = (isset($this->request->post['handy_filter'][$param])) ? $this->request->post['handy_filter'][$param] : null;
		}

		return $data;
	}
	
	public function helperBuildURLParams() {
		$url = '';
	
		foreach ($this->helperURLParams() as $param) {
			if (isset($this->request->get[$param])) {
				$value = $this->request->get[$param];

				if (is_string($value)) {
					$value = urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
					
					$url .= '&' . $param . '=' . $value;
				} elseif (is_array($value)) {
					foreach ($value as $row) {
						$row = urlencode(html_entity_decode($row, ENT_QUOTES, 'UTF-8'));
						$url .= '&' . $param . '[]=' . $row;
					}
				}
			}
		}

		if (isset($this->request->get['order']) && $this->request->get['order'] == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		return $url;
	}
	
	public function helperIsMultiArrayEmpty($array) {
		if (empty($array)) {
			return true;
		}

		foreach ($array as $value) {
			if (is_array($value)) {
				if (!$this->helperisMultiArrayEmpty($value)) {
					return false;
				}
			} else {
				if (!empty($value)) {
					return false;
				}
			}
		}

		return true;
	}

	private function helperIsEmptyString($string) {
		$string = trim($string);
		$string = html_entity_decode($string); // if it is getted from DB
		$string = strip_tags($string); // if it is textarea with visual editor there is is possible html-tags without texts
		
		if (empty($string)) {
			return true;
		}
		
		return false;
	}

}