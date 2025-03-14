<?php

class ControllerExtensionModuleSearchEngine extends Controller
{

	private $c = array();

	public function index()
	{

		$this->load->language('extension/module/search_engine');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('extension/module/search_engine');

		unset($this->session->data['indexing_process']);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			if (!empty($this->request->post['module_search_engine_options']['categories'])) {
				foreach ($this->request->post['module_search_engine_options']['categories'] as $d => $e) {
					if (!is_numeric($e['coefficient'])) {
						$this->request->post['module_search_engine_options']['categories'][$d]['coefficient'] = 1;
					}
				}
			}

			if (!empty($this->request->post['module_search_engine_options']['manufacturers'])) {
				foreach ($this->request->post['module_search_engine_options']['manufacturers'] as $d => $f) {
					if (!is_numeric($f['coefficient'])) {
						$this->request->post['module_search_engine_options']['manufacturers'][$d]['coefficient'] = 1;
					}
				}
			}
        
			$g = $this->model_extension_module_search_engine->isInexactIndexExist();

			if ($this->request->post['module_search_engine_options']['inexact_search'] == 1 && !$g) {
        $this->model_extension_module_search_engine->addIndexKey();
      } else if ($this->request->post['module_search_engine_options']['inexact_search'] != 1 && $g) {
        $this->model_extension_module_search_engine->removeIndexKey();
      }

			$this->model_setting_setting->editSetting('module_search_engine', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

					}

		if (isset($this->error['warning'])) {
			$h['error_warning'] = $this->error['warning'];
		} else {
			$h['error_warning'] = '';
		}

		if (isset($this->error['new_fields'])) {
			$h['error_new_fields'] = $this->error['new_fields'];
		} else {
			$h['error_new_fields'] = '';
		}

		if (isset($this->session->data['success'])) {
			$h['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$h['success'] = '';
		}

		$h['breadcrumbs'] = array();

		$h['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$h['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
		);

		$h['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/search_engine', 'user_token=' . $this->session->data['user_token'], true),
		);

		$h['action'] = $this->url->link('extension/module/search_engine', 'user_token=' . $this->session->data['user_token'], true);
		$h['delete'] = $this->url->link('extension/module/search_engine/delete', 'user_token=' . $this->session->data['user_token'], true);
		$h['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('localisation/language');
		$h['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($this->request->post['module_search_engine_status'])) {
			$h['status'] = $this->request->post['module_search_engine_status'];
		} else {
			$h['status'] = $this->config->get('module_search_engine_status');
		}

		if (isset($this->request->post['module_search_engine_options'])) {
			$h['options'] = $this->request->post['module_search_engine_options'];
		} elseif ($this->config->get('module_search_engine_options')) {
			$h['options'] = $this->config->get('module_search_engine_options');
		}

		if (empty($h['options']['types_order'])) {
			$h['options']['types_order'] = array(
				'fix_keyboard_layout' => array('sort' => 0),
				'transliteration' => array('sort' => 1),
				'inexact_search' => array('sort' => 2),
				'switch_from_and_to_or' => array('sort' => 3)
			);
		}

		uasort($h['options']['types_order'], array($this, 'sort_fields'));

		if (!isset($h['options']['categories'])) {
			$h['options']['categories'] = array();
		}

		if (!isset($h['options']['manufacturers'])) {
			$h['options']['manufacturers'] = array();
		}

				$this->load->model('catalog/attribute');
		$this->load->model('catalog/attribute_group');

		if (isset($this->request->post['module_search_engine_options']['fields']['pa.text']['attributes'])) {
			$i = $this->request->post['module_search_engine_options']['fields']['pa.text']['attributes'];
		} elseif (!empty($h['options']['fields']['pa.text']['attributes'])) {
			$i = $h['options']['fields']['pa.text']['attributes'];
		} else {
			$i = array();
		}

		$j = array();
		foreach ($i as $d => $k) {
			$l = $this->model_catalog_attribute->getAttribute($k['attribute_id']);
			if ($l) {
				$m = $this->model_catalog_attribute_group->getAttributeGroupDescriptions($l['attribute_group_id']);
				$n = $m[$this->config->get('config_language_id')]['name'];
				$j[$k['attribute_id']] = array(
					'attribute_id'                  => $k['attribute_id'],
					'name'                          => $l['name'],
					'group_name'					=> $n,
					'group_id'						=> $l['attribute_group_id'],
				);
			}
		}

		uasort($j, function ($o, $p) {
			return $o['group_name'] != $p['group_name']  ? strnatcmp($o['group_name'], $p['group_name']) : strnatcmp($o['name'], $p['name']);
		});

		$h['options']['fields']['pa.text']['attributes'] = $j;

		$h['fields'] = $this->model_extension_module_search_engine->getFields($h['options']);
		$h['parts_of_speech'] = $this->model_extension_module_search_engine->getPartsOfSpeech();

		$h['total_indexed'] = $this->model_extension_module_search_engine->getTotalIndexed();
		$h['total_not_indexed'] = $this->model_extension_module_search_engine->getTotalNotIndexed();

		$this->load->model('localisation/stock_status');
		$h['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
		
		if ($h['options']['inexact_search'] == 1 && !$this->model_extension_module_search_engine->isInexactIndexExist()) {
			$q = $this->model_extension_module_search_engine->isMariaDB() ? $this->language->get('error_inexact_search_mariadb') : '';
			$h['error_inexact'] = sprintf($this->language->get('error_inexact_search'), $q);
		}

		$h['user_token'] = $this->session->data['user_token'];

		$h['header'] = $this->load->controller('common/header');
		$h['column_left'] = $this->load->controller('common/column_left');
		$h['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/search_engine', $h));
	}

	private function sort_fields($o, $p)
	{
		return $o['sort'] - $p['sort'];
	}

	public function install()
	{

		$this->load->model('setting/setting');
		$this->load->model('extension/module/search_engine');

		$this->model_extension_module_search_engine->install();

		$this->model_setting_setting->deleteSetting('search_engine');

		$r['module_search_engine_options'] = $this->model_extension_module_search_engine->getDefaultOptions();
		$r['module_search_engine_status'] = 1;

		$this->model_setting_setting->editSetting('module_search_engine', $r);
	}

	public function uninstall()
	{

		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_search_engine');
		$this->load->model('extension/module/search_engine');
		$this->model_extension_module_search_engine->uninstall();
	}

	public function add()
	{

		$this->load->language('extension/module/search_engine');

		$this->load->model('extension/module/search_engine');

		$s = array();

		$t = 100;

		if ($this->validate()) {

			$u = $this->model_extension_module_search_engine->getTotalNotIndexed();

			if ($u == 0) {

				$s['progress'] = 100;
				$s['success'] = $this->language->get('text_success_index');

				unset($this->session->data['indexing_process']);
			} else {

				if (!isset($this->session->data['indexing_process'])) {
					$this->session->data['indexing_process'] = array();
					$this->session->data['indexing_process']['start_not_indexed'] = $u;
					$this->session->data['indexing_process']['last_not_indexed'] = 0;
				}

				$v = $this->session->data['indexing_process'];

				$w = number_format(($v['start_not_indexed'] - $u) * 100 / $v['start_not_indexed'], 2);

				if ($w < 100) {

					if ($v['last_not_indexed'] == 0 || ($v['last_not_indexed'] - $t) >= $u) {
						$this->session->data['indexing_process']['last_not_indexed'] = $u;
						$c = $this->model_extension_module_search_engine->addIndexes($t);
						if ($c) {
							$s['error'] = $c;
						}
					}

					$u = $this->model_extension_module_search_engine->getTotalNotIndexed();
					$w = number_format(($v['start_not_indexed'] - $u) * 100 / $v['start_not_indexed'], 2);
				}

				$s['progress'] = $w;

				if ($w >= 100) {
					$s['success'] = $this->language->get('text_success_index');;
				} else {
					$s['text'] = sprintf($this->language->get('text_index_progress'), $w);
				}
			}
		} else {
			$s['error'] = $this->error['warning'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($s));
	}

	public function stop()
	{
		sleep(2);
		unset($this->session->data['indexing_process']);
	}

	public function getTotals()
	{
		$this->load->model('extension/module/search_engine');

		$s = array();

		$s['total_indexed'] = $this->model_extension_module_search_engine->getTotalIndexed();
		$s['total_not_indexed'] = $this->model_extension_module_search_engine->getTotalNotIndexed();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($s));
	}

	public function delete()
	{

		if ($this->validate()) {
			$this->load->model('extension/module/search_engine');
			$this->model_extension_module_search_engine->deleteIndexes();
		}

		$this->response->redirect($this->url->link('extension/module/search_engine', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function validate()
	{

		if (!$this->user->hasPermission('modify', 'extension/module/search_engine')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!empty($this->request->post['module_search_engine_options']['new_fields'])) {
			foreach ($this->request->post['module_search_engine_options']['new_fields'] as $x) {
				if ((utf8_strlen(trim($x['field'])) < 1)) {
					$this->error['new_fields'][$x['field']] = $this->language->get('error_field');
					$this->error['warning'] = $this->language->get('error_warning');
				}
			}
		}

		return !$this->error ? true : false;
	}
}

//author sv2109 (sv2109@gmail.com) license for 1 product copy granted for Nawiteh ( nawiteh.com.ua,www.nawiteh.com.ua,stage.nawiteh.com.ua)
