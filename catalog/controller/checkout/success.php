<?php
class ControllerCheckoutSuccess extends Controller {
	public function index() {
		$this->load->language('checkout/success');

		if (isset($this->session->data['order_id'])) {
			$this->session->data['last_order_id'] = $this->session->data['order_id'];
			$this->cart->clear();

			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['order_id']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['totals']);
		}

		if (!empty($this->session->data['last_order_id']) ) {
			$this->document->setTitle(sprintf($this->language->get('heading_title_customer'), $this->session->data['last_order_id']));
			$this->document->setRobots('noindex,follow');
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
			$this->document->setRobots('noindex,follow');
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_basket'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_checkout'),
			'href' => $this->url->link('checkout/checkout', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_success'),
			'href' => $this->url->link('checkout/success')
		);

		if (!empty($this->session->data['last_order_id'])) {
			// Генеруємо токен
			$token = md5($this->session->data['last_order_id'] . $this->config->get('config_encryption'));
			
			$pdf_link = $this->url->link('checkout/invoice_pdf', 'order_id=' . $this->session->data['last_order_id'] . '&token=' . $token);
			
			// Отримуємо інформацію про замовлення для QR-коду
			$this->load->model('checkout/order');
			$order_info = $this->model_checkout_order->getOrder($this->session->data['last_order_id']);
			
			// Форматуємо дату
			$date = new DateTime($order_info['date_added']);
			$formatted_date = $date->format('d.m.Y');
			
			// Формуємо текст призначення платежу
			$payment_purpose = sprintf(
				'Оплата згідно рахунку №%s від %s',
				$this->session->data['last_order_id'],
				$formatted_date
			);

			$data['payment_info'] = true;
            $data['text_payment_info'] = 'Реквізити для оплати';
            $data['text_recipient'] = 'Отримувач';
            $data['payment_recipient'] = 'ТОВ "НАВІТЕХ"';
            $data['payment_iban'] = 'UA813052990000026003031002657';
            $data['text_edrpou'] = 'ЄДРПОУ';
            $data['payment_edrpou'] = '39811454';
            $data['text_purpose'] = 'Призначення платежу';
            $data['payment_purpose'] = $payment_purpose;

            // Додаємо тексти для кнопок і повідомлень
            $data['button_download_invoice'] = 'Завантажити рахунок';
            $data['button_qr_payment'] = 'Оплатити через QR-код';
            $data['text_qr_payment'] = 'Оплата замовлення';
            $data['button_copy'] = 'Копіювати';
            $data['text_link_copied'] = 'Посилання скопійовано!';

            // Додаємо попередження про відповідальність
            $data['warning_message'] = 'Якщо ви дійсно впевнені в правильності підбору запчастин, 
                                      ви можете здійснити оплату за реквізитами нижче, не чекаючи дзвінка менеджера. 
                                      У такому випадку ви підтверджуєте, що берете на себе відповідальність за правильність підбору запчастин.';
			

			$data['text_manager_contact'] = 'Найближчим часом з вами зв\'яжеться наш менеджер для уточнення деталей замовлення.';
			$data['text_scan_qr'] = 'Оплата замовлення';
			$data['text_or_copy_link'] = 'Відскануйте QR-код або скопіюйте посилання для оплати';
			// Завантажуємо бібліотеку QR-коду
			require_once(DIR_SYSTEM . 'library/nbu_payment_qr.php');
			$paymentQR = new NbuPaymentQr();
			
			try {
				// Генеруємо URL для QR-коду
				$qrUrl = $paymentQR->generatePaymentQR(
					'ТОВ "НАВІТЕХ"', // Замініть на вашу назву компанії
					'39811454',      // Замініть на ваш ЄДРПОУ
					'UA813052990000026003031002657', // Замініть на ваш IBAN
					$order_info['total'],
					$payment_purpose
				);
				
				// Отримуємо QR-код як base64 для відображення
				$data['payment_qr_code'] = $paymentQR->getQRCodeAsBase64($qrUrl);
				$data['payment_url'] = $qrUrl;
				$data['show_qr_code'] = true;
				$data['payment_purpose'] = $payment_purpose; // Додаємо для відображення в шаблоні
			} catch (Exception $e) {
				$this->log->write('Payment QR Error: ' . $e->getMessage());
				$data['show_qr_code'] = false;
			}
			
			if ($this->customer->isLogged()) {
				$data['text_message'] = sprintf($this->language->get('text_customer'), 
					$this->url->link('account/order/info&order_id=' . $this->session->data['last_order_id'], '', true),
					$this->url->link('account/account', '', true),
					$this->url->link('account/order', '', true),
					$this->url->link('information/contact'),
					$this->url->link('product/special'),
					$this->session->data['last_order_id'],
					$this->url->link('account/download', '', true)
				);
			} else {
				$data['text_message'] = sprintf($this->language->get('text_guest'),
					$this->url->link('information/contact')
				);
			}
			
			$data['pdf_invoice_link'] = $pdf_link;
			$data['show_pdf_link'] = true;
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'),
				$this->url->link('information/contact')
			);
			$data['show_pdf_link'] = false;
			$data['show_qr_code'] = false;
		}
		
		$data['continue'] = $this->url->link('common/home');
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
