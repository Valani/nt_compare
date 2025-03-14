<?php
class ControllerReportRemarketingReport extends Controller {
	public function index() {
		$this->load->language('report/remarketing_report');
 
		$this->document->setTitle($this->language->get('heading_title'));

		if (version_compare(VERSION,'3.0.0.0', '>=')) {
			$token = 'user_token=' . $this->session->data['user_token'];
			$data['user_token'] = $this->session->data['user_token'];
			$extension = 'marketplace/extension';
		} else {
			$token = 'token=' . $this->session->data['token'];;
			$data['token'] = $this->session->data['token'];
			$extension = 'extension/extension';
		}
		
		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = date('Y-m-d', strtotime(date('Y') . '-' . date('m') . '-01'));
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = date('Y-m-d');
		}

		if (isset($this->request->get['filter_group'])) {
			$filter_group = $this->request->get['filter_group'];
		} else {
			$filter_group = 'week';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = 0;
		}

		if (isset($this->request->get['filter_utm_source'])) {
			$filter_utm_source = $this->request->get['filter_utm_source'];
		} else {
			$filter_utm_source = 0;
		}

		if (isset($this->request->get['filter_utm_campaign'])) {
			$filter_utm_campaign = $this->request->get['filter_utm_campaign'];
		} else {
			$filter_utm_campaign = 0;
		}

		if (isset($this->request->get['filter_utm_medium'])) {
			$filter_utm_medium = $this->request->get['filter_utm_medium'];
		} else {
			$filter_utm_medium = 0;
		}

		if (isset($this->request->get['filter_utm_term'])) {
			$filter_utm_term = $this->request->get['filter_utm_term'];
		} else {
			$filter_utm_term = 0;
		}

		if (isset($this->request->get['filter_utm_content'])) {
			$filter_utm_content = $this->request->get['filter_utm_content'];
		} else {
			$filter_utm_content = 0;
		}

		if (isset($this->request->get['filter_first_referrer'])) {
			$filter_first_referrer = $this->request->get['filter_first_referrer'];
		} else {
			$filter_first_referrer = 0;
		}

		if (isset($this->request->get['filter_last_referrer'])) {
			$filter_last_referrer = $this->request->get['filter_last_referrer'];
		} else {
			$filter_last_referrer = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_group'])) {
			$url .= '&filter_group=' . $this->request->get['filter_group'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $token, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('report/remarketing_report', $token . $url, true)
		);

		$this->load->model('report/remarketing');

		$data['orders'] = array();

		$filter_data = array(
			'filter_date_start'     => $filter_date_start,
			'filter_date_end'       => $filter_date_end,
			'filter_group'          => $filter_group,
			'filter_utm_source'     => $filter_utm_source,
			'filter_utm_campaign'   => $filter_utm_campaign,
			'filter_utm_medium'     => $filter_utm_medium,
			'filter_utm_term'       => $filter_utm_term,
			'filter_utm_content'    => $filter_utm_content,
			'filter_first_referrer' => $filter_first_referrer,
			'filter_last_referrer'  => $filter_last_referrer,
			'filter_order_status_id'=> $filter_order_status_id, 
			'start'                 => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                 => $this->config->get('config_limit_admin')
		);

		$order_total = $this->model_report_remarketing->getTotalOrders($filter_data);

		$results = $this->model_report_remarketing->getOrders($filter_data);

		foreach ($results as $result) {
			$data['orders'][] = array(
				'date_start' => date($this->language->get('date_format_short'), strtotime($result['date_start'])),
				'date_end'   => date($this->language->get('date_format_short'), strtotime($result['date_end'])),
				'orders'     => $result['orders'],
				'order_ids'  => $result['order_ids'],
				'details'    => $this->url->link('report/remarketing_report/details', $token .  '&filter_order_ids=' . $result['order_ids'], true),
				'products'   => $result['products'],
				'total'      => $this->currency->format($result['total'], $this->config->get('config_currency'))
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_all_status'] = $this->language->get('text_all_status');

		$data['column_date_start'] = $this->language->get('column_date_start');
		$data['column_date_end'] = $this->language->get('column_date_end');
		$data['column_orders'] = $this->language->get('column_orders');
		$data['column_products'] = $this->language->get('column_products');
		$data['column_tax'] = $this->language->get('column_tax');
		$data['column_total'] = $this->language->get('column_total');

		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_group'] = $this->language->get('entry_group');
		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_filter'] = $this->language->get('button_filter');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['utm_source'] = $this->model_report_remarketing->getUtm('utm_source');
		$data['utm_campaign'] = $this->model_report_remarketing->getUtm('utm_campaign');
		$data['utm_medium'] = $this->model_report_remarketing->getUtm('utm_medium');
		$data['utm_term'] = $this->model_report_remarketing->getUtm('utm_term');
		$data['utm_content'] = $this->model_report_remarketing->getUtm('utm_content');
		$data['first_referrer'] = $this->model_report_remarketing->getUtm('first_referrer');
		$data['last_referrer'] = $this->model_report_remarketing->getUtm('last_referrer');
		
		$data['groups'] = array();

		$data['groups'][] = array(
			'text'  => $this->language->get('text_year'),
			'value' => 'year',
		);

		$data['groups'][] = array(
			'text'  => $this->language->get('text_month'),
			'value' => 'month',
		);

		$data['groups'][] = array(
			'text'  => $this->language->get('text_week'),
			'value' => 'week',
		);

		$data['groups'][] = array(
			'text'  => $this->language->get('text_day'),
			'value' => 'day',
		);

		$url = '';

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_group'])) {
			$url .= '&filter_group=' . $this->request->get['filter_group'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('report/remarketing_report', $token . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_group'] = $filter_group;
		$data['filter_order_status_id'] = $filter_order_status_id;
		$data['filter_utm_source'] = $filter_utm_source;
		$data['filter_utm_campaign'] = $filter_utm_campaign;
		$data['filter_utm_medium'] = $filter_utm_medium;
		$data['filter_utm_term'] = $filter_utm_term;
		$data['filter_utm_content'] = $filter_utm_content;
		$data['filter_first_referrer'] = $filter_first_referrer;
		$data['filter_last_referrer'] = $filter_last_referrer;
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('report/remarketing_report', $data));
	}
	
	public function details() {
		
		if (version_compare(VERSION,'3.0.0.0', '>=')) {
			$token = 'user_token=' . $this->session->data['user_token'];
			$data['user_token'] = $this->session->data['user_token'];
			$extension = 'marketplace/extension';
		} else {
			$token = 'token=' . $this->session->data['token'];
			$data['token'] = $this->session->data['token'];
			$extension = 'extension/extension';
		}
		
		$this->load->language('sale/order');
		$this->load->language('catalog/product');
 		$this->load->language('report/remarketing_report');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $token, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('report/remarketing_report', $token, true)
		);

		$this->load->model('report/remarketing');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['orders'] = [];
		$data['products'] = [];
		
		if (isset($this->request->get['filter_order_ids'])) {
			$filter_order_ids = $this->request->get['filter_order_ids'];
		} else {
			$filter_order_ids = 0;
		}
		
		$filter_data = ['filter_order_ids' => $filter_order_ids];

		$results = $this->model_report_remarketing->getOrdersByIds($filter_data);

		foreach ($results as $result) {
			$data['orders'][] = [
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'order_status'  => $result['order_status'] ? $result['order_status'] : $this->language->get('text_missing'),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
				'view'          => $this->url->link('sale/order/info', $token . '&order_id=' . $result['order_id'], true),
				'edit'          => $this->url->link('sale/order/edit', $token . '&order_id=' . $result['order_id'], true)
			];
		}

		$results = $this->model_report_remarketing->getProductsByIds($filter_data);

		foreach ($results as $result) {
			$product_info = $this->model_catalog_product->getProduct($result['product_id']);
			if (is_file(DIR_IMAGE . $product_info['image'])) {
				$image = $this->model_tool_image->resize($product_info['image'], 50, 50);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 50, 50);
			}
			
			$data['products'][] = [
				'product_id' => $result['product_id'],
				'image'      => $image,
				'name'       => $product_info['name'],
				'model'      => $product_info['model'],
				'quantity'   => $result['product_quantity'],
				'total'      => $result['product_total'],
				'edit'       => $this->url->link('catalog/product/edit', $token . '&product_id=' . $result['product_id'], true)
			];
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_all_status'] = $this->language->get('text_all_status');

		$data['column_date_start'] = $this->language->get('column_date_start');
		$data['column_date_end'] = $this->language->get('column_date_end');
		$data['column_orders'] = $this->language->get('column_orders');
		$data['column_products'] = $this->language->get('column_products');
		$data['column_tax'] = $this->language->get('column_tax');
		$data['column_total'] = $this->language->get('column_total');

		$data['entry_date_start'] = $this->language->get('entry_date_start');
		$data['entry_date_end'] = $this->language->get('entry_date_end');
		$data['entry_group'] = $this->language->get('entry_group');
		$data['entry_status'] = $this->language->get('entry_status');
		
		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_missing'] = $this->language->get('text_missing');
		$data['text_loading'] = $this->language->get('text_loading');

		$data['column_order_id'] = $this->language->get('column_order_id');
		$data['column_customer'] = $this->language->get('column_customer');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_total'] = $this->language->get('column_total');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_date_modified'] = $this->language->get('column_date_modified');
		$data['column_action'] = $this->language->get('column_action');

		$data['entry_order_id'] = $this->language->get('entry_order_id');
		$data['entry_customer'] = $this->language->get('entry_customer');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_date_added'] = $this->language->get('entry_date_added');
		$data['entry_date_modified'] = $this->language->get('entry_date_modified');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['column_image'] = $this->language->get('column_image');
		$data['column_name'] = $this->language->get('column_name');
		$data['column_category'] = $this->language->get('column_category');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_action'] = $this->language->get('column_action');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_model'] = $this->language->get('entry_model');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_image'] = $this->language->get('entry_image');
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('report/remarketing_report_details', $data));
	}
}