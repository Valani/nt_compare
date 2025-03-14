<?php

class ControllerExtensionModuleSearchSuggestion extends Controller {

	private $error = array();

	public function index() {

		$this->load->language('extension/module/search_suggestion');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('module_search_suggestion', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			//$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/search_suggestion', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['action'] = $this->url->link('extension/module/search_suggestion', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_search_suggestion_status'])) {
			$data['module_search_suggestion_status'] = $this->request->post['module_search_suggestion_status'];
		} else {
			$data['module_search_suggestion_status'] = $this->config->get('module_search_suggestion_status');
		}

		$data['modules'] = array();
		if (isset($this->request->post['module_search_suggestion_module'])) {
			$data['modules'] = $this->request->post['module_search_suggestion_module'];
		} elseif ($this->config->get('module_search_suggestion_module')) {
			$data['modules'] = $this->config->get('module_search_suggestion_module');
		}

		if (isset($this->request->post['module_search_suggestion_options'])) {
			$options = $this->request->post['module_search_suggestion_options'];
		} elseif ($this->config->get('module_search_suggestion_options')) {
			$options = $this->config->get('module_search_suggestion_options');
		}
		
		uasort($options['product']['fields'], array($this, 'sort_fields'));
		
		uasort($options['types_order'], array($this, 'sort_fields'));

		$data['options'] = $options;
		
		$this->load->model('catalog/attribute');
		$data['attributes'] = $this->model_catalog_attribute->getAttributes();
		
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('localisation/stock_status');    
    $stock_statuses = $this->model_localisation_stock_status->getStockStatuses();
		foreach($stock_statuses as $stock_status) {
			$data['stock_statuses'][$stock_status['stock_status_id']] = $stock_status;
		}

		$data['columns'] = array('center', 'left', 'right');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/search_suggestion', $data));
	}
	
	private function sort_fields ($a, $b) {
		return $a['sort'] - $b['sort'];
	}

	public function install() {
		$this->load->model('setting/setting');
		$this->load->model('extension/module/search_suggestion');
		
		$this->model_setting_setting->deleteSetting('module_search_suggestion');
		$setting['module_search_suggestion_options'] = $this->model_extension_module_search_suggestion->getDefaultOptions();
		$setting['module_search_suggestion_status'] = 1;
		$setting['module_search_suggestion_module'][0]['search_suggestion'] = 1;

		// tab titles
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		foreach($setting['module_search_suggestion_options']['types_order'] as $type => $type_order) {
			foreach($languages as $language) {
				if (isset($setting['module_search_suggestion_options'][$type]['titles'][$language['code']])) {
					$setting['module_search_suggestion_options'][$type]['title'][$language['language_id']] = $setting['module_search_suggestion_options'][$type]['titles'][$language['code']];
				}
			}
		}

		$this->model_setting_setting->editSetting('module_search_suggestion', $setting);
		
		$this->model_extension_module_search_suggestion->install();
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_search_suggestion');
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/search_suggestion')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;

	}
}
//author sv2109 (sv2109@gmail.com) license for 1 product copy granted for Nawiteh (andrew.pv.mm@gmail.com nawiteh.com.ua,www.nawiteh.com.ua,stage.nawiteh.com.ua)
