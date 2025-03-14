<?php
class ControllerextensionModuleSlaSoftRedirect extends Controller {
	private $codes = array(
		301 => 'Moved Permanently',
		302 => 'Moved Temporarily',
		410 => 'Not Found (GONE)',
		404 => 'Not Found',
		403 => 'Forbidden',
		307 => 'Temporary Redirect'
	);

	private $path_module ='extension/module/slasoft_redirect';
	private $path_extension = 'marketplace/extension/&type=module';
	private $module_name ='slasoft_redirect';
	private $my_model ='model_extension_module_slasoft_redirect';
	
	private $token = 'user_token';
	
	public  function index() {
		$data = $this->load->language($this->path_module);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('slasoft_redirect', $this->request->post);
			
			if (isset($this->request->post['slasoft_redirect_status']) && $this->request->post['slasoft_redirect_status']) {
				$this->installEvent();
				if (isset($this->request->post['slasoft_redirect_templates'])) {
					$templates = explode(',',$this->request->post['slasoft_redirect_templates']);
					foreach ($templates as $template) {
						$template = trim($template);
						if ($template) {
							$this->install_eventTemplate($template);
						}
					}
				}

				if (isset($this->request->post['slasoft_redirect_check404'])) {
					$this->installEventOne($this->getEventsSef('410'));
				} else {
					$this->uninstallEventOne($this->getEventsSef('410'));
				}

				if (isset($this->request->post['slasoft_redirect_check_redirect'])) {
					$this->installEventOne($this->getEventsSef('301'));
				} else {
					$this->uninstallEventOne($this->getEventsSef('301'));
				}

			} else {
				$this->uninstallEvent();
				$this->uninstall_eventTemplate();
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->makeUrl($this->path_module));
		}
		
		$this->load->model($this->path_module);
		
		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['action'] = $this->makeUrl($this->path_module);
		$data['cancel'] = $this->makeUrl($this->path_extension);
		$data['settings'] = $this->makeUrl($this->path_module);

		$data['export'] = $this->makeUrl('extension/module/slasoft_redirect/export');
		$data['import'] = $this->makeUrl('extension/module/slasoft_redirect/import');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_clear'] = $this->language->get('button_clear');

		$data[$this->token] = $this->session->data[$this->token];
		$l_codes = $this->language->get('codes');

		foreach ($this->codes as $code=>$text) {
			$data['codes'][$code] = !empty($l_codes[$code])?$l_codes[$code]:$text;
		}

		$page = 1;
		if (isset($this->request->get['page']))
			$page = $this->request->get['page'];
		
		$order = 'ASC';
		if (isset($this->request->get['order']))
			$order = $this->request->get['order'];

		$sort = 'from_url';
		if (isset($this->request->get['sort']))
			$sort = $this->request->get['sort'];

		$filter = array();

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		}
		
		$url = '';
		
		$url = http_build_query(array("filter" => $filter));

		if (isset($this->request->get['page']))
			$url = '&page=' . $this->request->get['page'];
		
		if (isset($this->request->get['sort']))
			$url = '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order']))
			$url = '&order=' . $this->request->get['order'];
		
		
		$data['filter'] = $filter;

		$data['delete'] = $this->makeUrl($this->path_module . '/delete', $url);
		$data['add']	= $this->makeUrl($this->path_module . '/edit', $url);
		$data['clear']	= $this->makeUrl($this->path_module . '/clear', $url);
		
		$data['get_filter'] = $this->makeUrlScript($this->path_module);

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->makeUrl('common/dashboard')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->makeUrl($this->path_extension)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->makeUrl($this->path_module)
		);

		$filter_data = array (
			'page' => $page,
			'sort' => $sort,
			'order' => $order,
			'filter' => $filter
		);
		$rules = $this->model_extension_module_slasoft_redirect->getRules($filter_data);
		$data['rules'] = array();
		foreach ($rules as $rule) {
			$data['rules'][] = array(
				'from_url'  => $rule['from_url'],
				'to_url'	=> $rule['to_url'],
				'status'	=> $rule['status'],
				'code'	  => $rule['code'],
				'cnt'	   => $rule['cnt'],
				'last_date' => $rule['last_date'],
				'edit'	  => $this->makeUrl($this->path_module . '/edit', "&redirect_id=" . $rule['redirect_id'] . $url),
				'delete'	=> $this->makeUrl($this->path_module . '/delete', "&redirect_id=" . $rule['redirect_id'] . $url),
				'check'	 => $this->makeUrl($this->path_module . '/check', "&redirect_id=" . $rule['redirect_id'] . $url),
			);
		}
		$data['delimiters'] = array(
			"," => $this->language->get('text_import_delimiter_coma'),
			";" => $this->language->get('text_import_delimiter_semicolon'),
			"\t" => $this->language->get('text_import_delimiter_tab'),
		);
		
		$url = '';
		$url = http_build_query(array('filter' => $filter));
		if (isset($this->request->get['sort']))
			$url = '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order']))
			$url = '&order=' . $this->request->get['order'];

		$rule_total = $this->model_extension_module_slasoft_redirect->getTotalRules($filter_data);
		$pagination = new Pagination();
		$pagination->total = $rule_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->makeUrl($this->path_module, $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['result'] = sprintf($this->language->get('text_pagination'), ($rule_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($rule_total - $this->config->get('config_limit_admin'))) ? $rule_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $rule_total, ceil($rule_total / $this->config->get('config_limit_admin')));
		
		if (isset($this->request->post['slasoft_redirect_status'])) {
			$data['slasoft_redirect_status'] = $this->request->post['slasoft_redirect_status'];
		} else {
			$data['slasoft_redirect_status'] = $this->config->get('slasoft_redirect_status');
		}

		if (isset($this->request->post['slasoft_redirect_templates'])) {
			$data['slasoft_redirect_templates'] = $this->request->post['slasoft_redirect_templates'];
		} else {
			$data['slasoft_redirect_templates'] = $this->config->get('slasoft_redirect_templates');
		}

		if (isset($this->request->post['slasoft_redirect_check404'])) {
			$data['slasoft_redirect_check404'] = $this->request->post['slasoft_redirect_check404'];
		} else {
			$data['slasoft_redirect_check404'] = $this->config->get('slasoft_redirect_check404');
		}
		if (isset($this->request->post['slasoft_redirect_check_redirect'])) {
			$data['slasoft_redirect_check_redirect'] = $this->request->post['slasoft_redirect_check_redirect'];
		} else {
			$data['slasoft_redirect_check_redirect'] = $this->config->get('slasoft_redirect_check_redirect');
		}

		$this->footer('list', $data);
	}
	
	public function clear() {
		$this->load->language($this->path_module);
		if ($this->validate()) {
			$this->load->model($this->path_module);
			$this->model_extension_module_slasoft_redirect->clear();
		}
		$this->response->redirect($this->makeUrl($this->path_module));
	}

	public function check() {
		$this->load->language($this->path_module);
		$this->load->model($this->path_module);
		if (isset($this->request->get['redirect_id'])) {
			$rule_info = $this->model_extension_module_slasoft_redirect->getRule($this->request->get['redirect_id']);
			if ($rule_info) {
				$curl = curl_init();
				if ($this->request->server['HTTPS']) {
					$catalog = HTTPS_CATALOG;
				} else {
					$catalog = HTTP_CATALOG;
				}
				$url = $catalog . ltrim(trim($rule_info['from_url'],'#'), '/');
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_FILETIME, true);
				curl_setopt($curl, CURLOPT_NOBODY, true);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HEADER, true);
				$header = curl_exec($curl);
				$info = curl_getinfo($curl);
				curl_close($curl);
				echo "<pre>";
				print_r($header);
				print_r($info);
				echo "</pre>";
				
				
			} else {
				$data['text'] = $this->language->get('error_rule_not_found');
			}
		} else {
			$data['text'] = $this->language->get('error_rule_empty');
		}
	}

	public function edit() {
		$data = $this->load->language($this->path_module);
		$this->load->model($this->path_module);

		$this->document->setTitle($this->language->get('heading_title'));

		$url = '';

		$filter = array();

		if ($this->request->server['REQUEST_METHOD'] == 'GET' && isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		}

		$url = http_build_query(array("filter" => $filter));
		if (isset($this->request->get['page']))
			$url = '&page=' . $this->request->get['page'];
		if (isset($this->request->get['sort']))
			$url = '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order']))
			$url = '&order=' . $this->request->get['order'];

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validateForm()) {
			if (isset($this->request->get['redirect_id'])) {
				$this->model_extension_module_slasoft_redirect->editRule($this->request->get['redirect_id'], $this->request->post);
			} else {
				$this->model_extension_module_slasoft_redirect->addRule($this->request->post);
			}
			$this->response->redirect($this->makeUrl($this->path_module, $url, true));
		}

		if (isset($this->request->get['redirect_id'])) {
			$rule_info = $this->model_extension_module_slasoft_redirect->getRule($this->request->get['redirect_id']);
			if (isset($this->request->post['from_url'])) {
				$data['from_url'] = $this->request->post['from_url'];
			} else {
				$data['from_url'] = $rule_info['from_url'];
			}
			if (isset($this->request->post['to_url'])) {
				$data['to_url'] = $this->request->post['to_url'];
			} else {
				$data['to_url'] = $rule_info['to_url'];
			}
			if (isset($this->request->post['code'])) {
				$data['code'] = $this->request->post['code'];
			} else {
				$data['code'] = $rule_info['code'];
			}
			if (isset($this->request->post['status'])) {
				$data['status'] = $this->request->post['status'];
			} else {
				$data['status'] = $rule_info['status'];
			}
		} else {
			if (isset($this->request->post['from_url'])) {
				$data['from_url'] = $this->request->post['from_url'];
			} else {
				$data['from_url'] = '';
			}
			if (isset($this->request->post['to_url'])) {
				$data['to_url'] = $this->request->post['to_url'];
			} else {
				$data['to_url'] = '';
			}
			if (isset($this->request->post['code'])) {
				$data['code'] = $this->request->post['code'];
			} else {
				$data['code'] = 301;
			}
			if (isset($this->request->post['status'])) {
				$data['status'] = $this->request->post['status'];
			} else {
				$data['status'] = 1;
			}
		}
		if (isset($this->request->get['redirect_id'])){
			$data['heading_title'] = $this->language->get('heading_edit');
		} else {
			$data['heading_title'] = $this->language->get('heading_add');
		}

		$data['action'] = $this->makeUrl($this->path_module . '/edit', (isset($this->request->get['redirect_id']) ? '&redirect_id=' . $this->request->get['redirect_id'] : ''), true);
		$data['cancel'] = $this->makeUrl($this->path_module);
		$data['settings'] = $this->makeUrl($this->path_module);

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data[$this->token] = $this->session->data[$this->token];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->makeUrl('common/dashboard')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_module'),
			'href' => $this->makeUrl($this->path_extension)
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->makeUrl($this->path_module)
		];
		$data['errors'] = $this->error;

		$l_codes = $this->language->get('codes');

		foreach ($this->codes as $code=>$text) {
			$data['codes'][$code] = !empty($l_codes[$code])?$l_codes[$code]:$text;
		}

		$this->footer('form', $data);
	}
	
	public function delete() {
		$this->load->language($this->path_module);
		if ($this->validate()) {
			$this->load->model($this->path_module);
			$this->model_extension_module_slasoft_redirect->deleteRule($this->request->get['redirect_id']);
		}
		$this->response->redirect($this->makeUrl($this->path_module, (isset($this->request->get['filter']) ? '&' . http_build_query(array("filter" => $this->request->get['filter'])) : ""), true));
	}

	private function validate() {
		$errors = array();
		if (!$this->user->hasPermission('modify', $this->path_module))
			$errors['persimission'] = $this->language->get('error_permission');
		$this->error = $errors;
		return !$this->error;
	}

	private function validateForm() {
		$errors = array();
		if (!$this->user->hasPermission('modify', $this->path_module))
			$errors['persimission'] = $this->language->get('error_permission');

		if (!isset($this->request->post['from_url']) || isset($this->request->post['from_url']) && mb_strlen(trim($this->request->post['from_url'])) == 0) {
			$errors['from_url'] = $this->language->get('error_from_url');
		}

		if (isset($this->request->post['from_url']) && mb_strlen(trim($this->request->post['from_url'])) >0) {
			if (preg_match('#^(http|https):\/\/#', $this->request->post['from_url'])) {
				$errors['from_url'] = $this->language->get('error_protocol_from_url');
			}
			if ($this->{$this->my_model}->checkFromUrl($this->request->post['from_url'])) {
				$errors['from_url'] = $this->language->get('error_from_url_exists');
			}
			
		}

		if (!isset($this->request->post['code']) || (isset($this->request->post['code']) && !in_array($this->request->post['code'], array_keys($this->codes)))) {
			$errors['code'] = $this->language->get('error_code');
		}

		if (!isset($this->request->post['to_url']) || mb_strlen(trim($this->request->post['to_url'])) == 0) {
			if (isset($this->request->post['code']) && in_array($this->request->post['code'], array(301,302))) {
				$errors['to_url'] = $this->language->get('error_to_url');
			}
		}
			
		if (!isset($this->request->post['status']) || !in_array($this->request->post['status'], array('0', '1')))
			$errors['status'] = $this->language->get('error_status');

		$this->error = $errors;
		return !$this->error;
	}

	protected  function installEvent() {
		$events = $this->getEvents();
		$this->load->model('setting/event');

		foreach ($events as $code=>$value) {
			$this->model_setting_event->deleteEventByCode($code);
			$this->model_setting_event->addEvent($code, $value['trigger'], $value['action'], 1);
		}		
	}

	protected  function uninstallEvent() {
		$event_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE code LIKE 'slasoft_redirect%'");
		if ($event_query->num_rows) {
			$this->load->model('setting/event');

			foreach ($event_query->rows as $row) {
				$this->model_setting_event->deleteEventByCode($row['code']);
			}
		}
	}
	protected function installEventOne($event) {
		$this->load->model('setting/event');

		foreach ($event as $code=>$value) {
			$this->model_setting_event->deleteEventByCode($code);
			$this->model_setting_event->addEvent($code, $value['trigger'], $value['action'], 1);
		}

	}
	protected function uninstallEventOne($event) {
		$this->load->model('setting/event');

		foreach ($event as $code=>$value) {
			$this->model_setting_event->deleteEventByCode($code);
		}

	}
	
	public  function install() {
		$this->load->model($this->path_module);
		$this->model_extension_module_slasoft_redirect->install();
	}

	public  function uninstall() {
		if ($this->validate()) {
			$this->load->model($this->path_module);
			$this->model_extension_module_slasoft_redirect->uninstall();
		}
		$this->uninstallEvent();
	}

		
	protected function uninstall_eventTemplate() {
		$event_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE code LIKE 'slasoft_redirectTemplate%'");
		if ($event_query->num_rows) {
			$this->load->model('setting/event');

			foreach ($event_query->rows as $row) {
				$this->model_extension_event->deleteEventByCode($row['code']);
			}
		}
	}

	protected function install_eventTemplate($template) {
		$this->load->model('setting/event');

		$events = array(
			'slasoft_redirectTemplate' => array(
				'trigger' => 'catalog/view/' . $template,
				'action'  => 'extension/startup/slasoft_redirect/redirect301',
			)
		);
		$i = 2;
		foreach ($events as $code=>$value) {
			$event_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE code LIKE 'slasoft_redirectTemplate%' AND `trigger` = '" . $this->db->escape($value['trigger']) . "'");
			if ($event_query->num_rows) {
				foreach ($event_query->rows as $row) {
					$this->model_setting_event->deleteEventByCode($row['code']);
				}
			}
			$this->model_setting_event->addEvent($code . $i, $value['trigger'], $value['action'], 1);
			$i++;
		}
	}
		
	public function deleteProduct(&$view, &$data) {
		$results = array();
		$product_id = $data[0];
		
		$sql = "SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'config' AND `key` IN('config_ssl','config_url','config_secure')";
		$confs = $this->db->query($sql);
					
		$servers[0]['config_ssl'] = HTTPS_CATALOG;
		$servers[0]['config_url'] = HTTP_CATALOG;
					
		foreach ($confs->rows as $row) {
			$servers[$row['store_id']][$row['key']] = $row['value'];
		}
					
		foreach ($servers as $store_id=>$values) {
			if ($servers[$store_id]['config_secure']) {
				$catalog = $servers[$store_id]['config_ssl'];
			} else {
				$catalog = $servers[$store_id]['config_url'];
			}
			$result = $this->getUrl($catalog, 'product/product&product_id=' . $product_id);
			if ($result) {
				$results = json_decode(base64_decode($result),true);
				if ($results) {
					foreach ($results as $language_id=>$result) {
						$from_url = $result;

						$this->model_extension_module_slasoft_redirect->addRule(array(
							'from_url' 		=> $from_url,
							'to_url' 		=> '/',
							'code'			=> '410',
							'status'		=> 1,
						));
					}
				}
			}
		}
	}

/*
	public function deleteCategory(&$view, &$data) {
		$results = array();
		$category_id = $data[0];
		$from_url = $this->getUrl('product/category&category_id=' . $category_id);
		$this->load->model($this->path_module);
		$this->model_extension_module_slasoft_redirect->addRule(array(
			'from_url' 		=> $from_url,
			'to_url' 		=> '/',
			'code'			=> '410',
			'status'		=> 1,
		));
	}
*/

	public function editProduct(&$view, &$data) {
		$results = array();
		$product_id = $data[0];
		$product_info = $data[1];
		if (isset($product_info['product_seo_url'])) {
			$sql = "SELECT * FROM " . DB_PREFIX . "seo_url WHERE query='product_id=" . (int)$product_id . "'";
			$res = $this->db->query($sql);
			if ($res->num_rows){
				$real_keyword = array();
				foreach ($res->rows as $row){
					$real_keyword[$row['store_id']][$row['language_id']] = $row['keyword'];
				}
				$from_urls= array();
				foreach ($product_info['product_seo_url'] as $store_id=>$keywords) {
					foreach ($keywords as $language_id=>$keyword) {
						
						$old = isset($real_keyword[$store_id][$language_id])?$real_keyword[$store_id][$language_id]:'';
						$new = isset($product_info['product_seo_url'][$store_id][$language_id])?$product_info['product_seo_url'][$store_id][$language_id]:'';
						if ($new && $new != $old) {
							$from_urls[$store_id][$language_id] = 'product/product&product_id=' . $product_id;
						}
					}
				}
				
//var_dump($from_urls);
				if ($from_urls) {
					$sql = "SELECT * FROM " . DB_PREFIX . "setting WHERE `code` = 'config' AND `key` IN('config_ssl','config_url','config_secure')";
					$confs = $this->db->query($sql);
					
					$servers[0]['config_ssl'] = HTTPS_CATALOG;
					$servers[0]['config_url'] = HTTP_CATALOG;
					
					foreach ($confs->rows as $row) {
						$servers[$row['store_id']][$row['key']] = $row['value'];
					}
					
					foreach ($servers as $store_id=>$values){
						if (isset($from_urls[$store_id])) {
							if ($servers[$store_id]['config_secure']) {
								$catalog = $servers[$store_id]['config_ssl'];
							} else {
								$catalog = $servers[$store_id]['config_url'];
							}
							$result = $this->getUrl($catalog, $from_urls[$store_id]);
							if ($result) {
								$results = json_decode(base64_decode($result),true);
								
								if ($results) {
									foreach ($results as $language_id=>$result) {
										if (isset($from_urls[$store_id][$language_id])) { 
											$from_url = $result;
											$old = isset($real_keyword[$store_id][$language_id])?$real_keyword[$store_id][$language_id]:'';
											$new = isset($product_info['product_seo_url'][$store_id][$language_id])?$product_info['product_seo_url'][$store_id][$language_id]:'';

											$to_url = str_replace($old, $new, $from_url);
											$this->load->model($this->path_module);
/*											var_dump(array(
											'from_url' 		=> $from_url,
											'to_url' 		=> $to_url,
											'code'			=> '301',
											'status'		=> 1,));
	
*/
											$this->model_extension_module_slasoft_redirect->addRule(array(
												'from_url' 		=> $from_url,
												'to_url' 		=> $to_url,
												'code'			=> '301',
												'status'		=> 1,
											));
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	protected function getUrl($catalog, $query) {
		$curl = curl_init();
		
		$sting_query= json_encode($query);
		$url = $catalog . '?route=extension/startup/slasoft_redirect/getUrl';
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FILETIME, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'query=' . base64_encode($sting_query));
		
		$result = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);
		if (isset($info['http_code']) && $info['http_code'] == 200) {
/*		echo "<pre>";
			var_dump($result);
			var_dump($info);
		echo "</pre>";
*/
		return $result;
		} else {
			return false;
		}
	}

		
	protected function getEventsSef($code) {
		$events['410'] = array(
			'slasoft_redirectDeleteProduct' => array(
				'trigger' => 'admin/model/catalog/product/deleteProduct/before',
				'action'  => $this->path_module . '/deleteProduct',
			),
/*			
			'slasoft_redirectDeleteCategory' => array(
				'trigger' => 'admin/model/catalog/product/deleteCategory/before',
				'action'  => $this->path_module . '/deleteCategory',
			),
*/
		);
		
		$events['301'] = array(
			'slasoft_redirectEditProducr' => array(
				'trigger' => 'admin/model/catalog/product/editProduct/before',
				'action'  => $this->path_module . '/editProduct',
			),

		);
		if (array_key_exists($code, $events)) {
			return $events[$code];
		} else {
			return array();
		}
	}

	protected function getEvents() {
		$events = array(
			'slasoft_redirect' => array(
				'trigger' => 'catalog/view/error/not_found/before',
				'action'  => 'extension/startup/slasoft_redirect/redirect301',
			),
		);
		return $events;
	}
	
	public  function export() {
		$this->load->language($this->path_module);
		$this->load->model($this->path_module);
		$total = $this->model_extension_module_slasoft_redirect->getTotalRules();
		$filter_data = array(
			'limit' => $total,
			'page' => 1,
		);
		
		$redirects = $this->model_extension_module_slasoft_redirect->getRules($filter_data);
		
		header("Content-Type: text/csv");
		header("Content-Disposition: attachment; filename=slasoft_redirect-".date('d-m-Y').".csv");
		header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
		header("Pragma: no-cache"); // HTTP 1.0
		header("Expires: 0"); // Proxies
		$out = fopen('php://output', 'w');
		$export_head = array(
			"From URL",
			"To URL",
			"HTTP server code",
			"Status"
		);
		fputcsv($out, $export_head);
		
		if ($redirects)
			foreach ($redirects as $rule) {
				$export = array(
					$rule['from_url'],
					$rule['to_url'],
					$rule['code'],
					$rule['status'],
				);
			fputcsv($out, $export);
			}

		fclose($out);		
	}
	
	public  function import() {
		$data = $this->load->language($this->path_module);
		if (!$this->validate()) {
			$this->response->redirect($this->makeUrl($this->path_module, (isset($this->request->get['filter']) ? '&' . http_build_query(array("filter" => $this->request->get['filter'])) : ""), true));
		}

		$this->load->model($this->path_module);
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$result = false;
		$errors = array();
		if (!isset($this->request->files['filename']) || $this->request->files['filename']['error'] != 0)
			$errors[] = $this->language->get('error_uploadfile');
		else {
			$results = array (
				'all'	   => array(
					'text' => $this->language->get('text_success_result_all'),
					'cnt' => 0
					),
				'update'	=> array(
					'text' => $this->language->get('text_success_result_update'),
					'cnt' => 0
					),
				'insert'	=> array(
					'text' => $this->language->get('text_success_result_insert'),
					'cnt' => 0
					),
				'error'	 => array(
					'text' => $this->language->get('text_success_result_error'),
					'cnt' => 0
					),
			);
			$delimiters = array(
				';',
				"\t",
				','
			);
			if (in_array($this->request->post['delimiter'],$delimiters)) {
				$delimiter = $this->request->post['delimiter'];
			} else {
				$delimiter = ',';
			}
			$line = 1;
			
			$fp = fopen($this->request->files['filename']['tmp_name'], "r");
			if ($fp !== false) {
				while (($export = fgetcsv($fp, 1000, $delimiter)) !== false) {
					if (count($export)  < 2) {
						$errors[] = sprintf($this->language->get('error_data'), $line);
						$results['error']['cnt']++;
					} else {
						if (isset($export[0]) && stristr($export[0],'from_url')) continue;
						if (isset($export[0])) $from_url = $export[0];
						if (isset($export[1])) {
							$to_url = $export[1];
						} else {
							$to_url = '';
						}
						if (isset($export[2]) && isset($this->codes[$export[2]])) {
							$code = $export[2];
						} else {
							$code = 301;
						}
						if (isset($export[3]) && in_array($export[3], array(0,1))) {
							$status = $export[3];
						} else {
							$status = 0;
						}
						$check = $this->model_extension_module_slasoft_redirect->checkFromUrl($from_url);
						if (!$check) {
							$this->model_extension_module_slasoft_redirect->addRule(array(
								'from_url' 		=> $from_url,
								'to_url' 		=> $to_url,
								'code'			=> $code,
								'status'		=> $status,
							));
							$results['insert']['cnt']++;
						} else {
							$this->model_extension_module_slasoft_redirect->editRule($check['redirect_id'], array(
								'from_url' 		=> $from_url,
								'to_url' 		=> $to_url,
								'code'			=> $code,
								'status'		=> $status,
							));
							$results['update']['cnt']++;
						}
					}
				$line++;
				$results['all']['cnt']++;
				}
			}
		}
		// view 
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['cancel'] = $this->makeUrl($this->path_extension);
		$data['link_return'] = $this->makeUrl($this->path_module);
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data[$this->token] = $this->session->data[$this->token];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->makeUrl('common/dashboard')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->makeUrl($this->path_extension)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->makeUrl($this->path_module)
		);


		$data['errors'] = $errors;
		$data['results'] = $results;

		$this->footer('import', $data);
	}

	private function footer($template, $data) {
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
		
        $data[$this->token] = $this->session->data[$this->token];
        $data['path_module'] = $this->path;
        $this->response->setOutput($this->load->view($this->path_module . '/' . $this->module_name . '_' . $template, $data));
	}

	private function makeUrl($route, $arg=''){
		if ($arg) {
			$arg = '&' . ltrim($arg,'&');
		}
		return $this->url->link ($route, $this->token . '=' . $this->session->data[$this->token] . $arg, true);
	}

	private function makeUrlScript($route, $arg=''){
		return str_replace('&amp;','&',$this->makeUrl($route, $arg));
	}

}