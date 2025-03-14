<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesFaqOCTProductFaq extends Controller {
    public function index($data = []) {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $data['from_controller'] = true;
        }

        if (!isset($data['from_controller'])) {
            $this->response->redirect($this->url->link('common/home', '', true));
        }

        $this->load->language('octemplates/oct_showcase');

		$this->load->model('octemplates/faq/oct_product_faq');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['oct_faqs'] = [];

        if (isset($data['p_id']) && !empty($data['p_id'])) {
            $data['product_id'] = $product_id = $data['p_id'];
        } else {
            $data['product_id'] = $product_id = isset($this->request->get['faqp_id']) ? $this->request->get['faqp_id'] : $this->request->get['product_id'];
        }

        $data['poup_is_not'] = ((isset($data['p_id']) && !empty($data['p_id'])) || (isset($this->request->get['popup']))) ? 0 : 1;

		if ($this->customer->isLogged()) {
			$data['email_user'] = $this->customer->getEmail();
			$data['firstname'] = $this->customer->getFirstName();
		} else {
			$data['email_user'] = false;
			$data['firstname'] = false;
		}

		$faq_total = $this->model_octemplates_faq_oct_product_faq->getTotalFaqsByProductId($product_id);

		$results = $this->model_octemplates_faq_oct_product_faq->getFaqsByProductId($product_id, ($page - 1) * 25, 25);

		foreach ($results as $result) {
			$data['oct_faqs'][] = [
				'author'     => $result['author'],
				'text'       => nl2br($result['text']),
				'answer'     => nl2br($result['answer']),
				'date_added' => $this->load->controller('octemplates/main/oct_functions/OctDateTime', array($result['date_added'], 1))
			];
		}

		$pagination = new Pagination();
		$pagination->total = $faq_total;
		$pagination->page = $page;
		$pagination->limit = 25;
		$pagination->url = $this->url->link('octemplates/faq/oct_product_faq', 'faqp_id=' . $product_id . '&page={page}&popup=' . $data['poup_is_not']);

		$data['pagination'] = $pagination->render();
        $data['page'] = $page;
		$data['results'] = sprintf($this->language->get('text_pagination'), ($faq_total) ? (($page - 1) * 25) + 1 : 0, ((($page - 1) * 25) > ($faq_total - 25)) ? $faq_total : ((($page - 1) * 25) + 25), $faq_total, ceil($faq_total / 25));

        $data['oct_id_div'] = isset($data['p_id']) ? 'popup_product_questions' : 'product_questions';
        $data['oct_faqs_id'] = isset($data['p_id']) ? 'poup_oct_faqs' : 'oct_faqs';

        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !isset($data['p_id']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->response->setOutput($this->load->view('octemplates/faq/oct_product_faq', $data));
        } else {
            return $this->load->view('octemplates/faq/oct_product_faq', $data);
        }
    }

    public function write() {
		$json = [];

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {

			$this->load->model('catalog/product');

			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error']['name'] = $this->language->get('error_faq_name');
			}

			if (isset($this->request->post['email']) && (!empty($this->request->post['email'])) && (utf8_strlen($this->request->post['email']) > 96 || !preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $this->request->post['email']))) {
				$json['error']['email'] = $this->language->get('error_faq_email');
			}

			if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error']['text'] = $this->language->get('error_faq_text');
			}

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('oct_faq', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error']['captcha'] = $captcha;
				}
			}

			$data['product_id'] = $product_id = isset($this->request->get['faqp_id']) ? $this->request->get['faqp_id'] : $this->request->get['product_id'];

			if ($data['product_id']) {
				$product_info = $this->model_catalog_product->getProduct($data['product_id']);
				
				if ($product_info && !isset($json['error'])) {
					$this->load->model('octemplates/faq/oct_product_faq');

					$this->model_octemplates_faq_oct_product_faq->addFaq($this->request->get['faqp_id'], $this->request->post);

					$store_name = $this->config->get('config_name');
					
					$message = sprintf($this->language->get('text_faq_email_welcome'), html_entity_decode($store_name, ENT_QUOTES, 'UTF-8')) . "\n\n";
					$message .= sprintf(
						$this->language->get('text_faq_email_body'), 
						$product_info['name'], 
						$this->url->link('product/product', 'product_id=' . (int)$data['product_id'])
					) . "\n\n";
					$message .= html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');
					
					$mail				 = new Mail($this->config->get('config_mail_engine'));
					$mail->protocol      = $this->config->get('config_mail_protocol');
					$mail->parameter     = $this->config->get('config_mail_parameter');
					$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
					$mail->smtp_username = $this->config->get('config_mail_smtp_username');
					$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
					$mail->smtp_port     = $this->config->get('config_mail_smtp_port');
					$mail->smtp_timeout  = $this->config->get('config_mail_smtp_timeout');
					
					$mail->setTo($this->config->get('config_email'));
					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
					$mail->setSubject(sprintf($this->language->get('text_faq_email_subject'), html_entity_decode($store_name, ENT_QUOTES, 'UTF-8')));
					$mail->setText($message);
					$mail->send();

					$json['success'] = $this->language->get('text_faq_success');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
