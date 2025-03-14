<?php
require_once(DIR_SYSTEM . 'library/tcpdf/tcpdf.php');

class MYPDF extends TCPDF {
    public function Header() {
        // Показуємо хедер тільки на першій сторінці
        if ($this->getPage() == 1) {
            $this->SetFont('dejavusans', '', 10);
            
            // Logo
            if (is_file(DIR_IMAGE . 'logo/logo_pdf.png')) {
                $this->Image(DIR_IMAGE . 'logo/logo_pdf.png', ($this->getPageWidth() - 60) / 2, 15, 60);
            }
            
            // Line after logo
            $this->SetY(40);
            $this->Cell(0, 0, '', 'T');
            
            // Warning text under logo
            $this->SetY(45);
            $this->SetFont('dejavusans', '', 9);
            $warning_text = 'Увага! Оплата цього рахунку означає погодження з умовами поставки товарів. Повідомлення про оплату є обов\'язковим, в іншому випадку не гарантується наявність товарів на складі. Товар відпускається за фактом надходження коштів на п/р Постачальника, самовивозом, за наявності довіреності та паспорта';
            $this->MultiCell(0, 5, $warning_text, 0, 'C');
        }
    }
    
    public function Footer() {
        // Показуємо футер тільки на останній сторінці
        if ($this->last_page) {
            $this->SetY(-50);
            $this->SetFont('dejavusans', '', 10);
            
            // Footer text
            $this->MultiCell(0, 5, 'Увага!!!!Рахунок дійсний для оплати протягом 3-ох банківських днів. У разі оплати після 3-ох банківських днів, обов\'язково уточняти наявність товару на складі (перед оплатою рахунку)', 0, 'C');
            
            $this->Ln(5);
            $this->SetFont('dejavusans', 'B', 10);
            $this->Cell(0, 5, 'Увага!!! ПРОХАННЯ ПОШТОВУ КОРЕСПОНДЕНЦІЮ НАДСИЛАТИ ЗА АДРЕСОЮ:', 0, 1, 'C');
            $this->SetFont('dejavusans', '', 10);
            $this->Cell(0, 5, '81114, Львівська область, Львівський район, с. Скнилів, вул. Окружна, 25.', 0, 1, 'C');
            
            $this->Ln(5);
            $this->SetFont('dejavusans', 'B', 10);
            $this->Cell(0, 5, 'Увага!!! На електробладнання та його елементи ГАРАНТІЯ НЕ РОЗПОВСЮДЖУЄТЬСЯ!', 0, 1, 'C');
        }
    }

    public function setLastPage($last) {
        $this->last_page = $last;
    }
}

class ControllerMailOrder extends Controller {
	public function index(&$route, &$args) {
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}

		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}	

		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}
		
		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}
						
		// We need to grab the old order status ID
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		if ($order_info) {
			// If order status is 0 then becomes greater than 0 send main html email
			if (!$order_info['order_status_id'] && $order_status_id) {
				$this->add($order_info, $order_status_id, $comment, $notify);
			} 
			
			// If order status is not 0 then send update text email
			if ($order_info['order_status_id'] && $order_status_id && $notify) {
				$this->edit($order_info, $order_status_id, $comment, $notify);
			}		
		}
	}
		
	public function add($order_info, $order_status_id, $comment, $notify) {

		// Check for any downloadable products
		$download_status = false;

		$order_products = $this->model_checkout_order->getOrderProducts($order_info['order_id']);
		
		foreach ($order_products as $order_product) {
			// Check if there are any linked downloads
			$product_download_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_download` WHERE product_id = '" . (int)$order_product['product_id'] . "'");

			if ($product_download_query->row['total']) {
				$download_status = true;
			}
		}
		
		// Load the language for any mails that might be required to be sent out
		$language = new Language($order_info['language_code']);
		$language->load($order_info['language_code']);
		$language->load('mail/order_add');

		// HTML Mail
		$data['title'] = sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']);

		$data['text_greeting'] = sprintf($language->get('text_greeting'), $order_info['store_name']);
		$data['text_link'] = $language->get('text_link');
		$data['text_download'] = $language->get('text_download');
		$data['text_order_detail'] = $language->get('text_order_detail');
		$data['text_instruction'] = $language->get('text_instruction');
		$data['text_order_id'] = $language->get('text_order_id');
		$data['text_date_added'] = $language->get('text_date_added');
		$data['text_payment_method'] = $language->get('text_payment_method');
		$data['text_shipping_method'] = $language->get('text_shipping_method');
		$data['text_email'] = $language->get('text_email');
		$data['text_telephone'] = $language->get('text_telephone');
		$data['text_ip'] = $language->get('text_ip');
		$data['text_order_status'] = $language->get('text_order_status');
		$data['text_payment_address'] = $language->get('text_payment_address');
		$data['text_shipping_address'] = $language->get('text_shipping_address');
		$data['text_product'] = $language->get('text_product');
		$data['text_model'] = $language->get('text_model');
		$data['text_quantity'] = $language->get('text_quantity');
		$data['text_price'] = $language->get('text_price');
		$data['text_total'] = $language->get('text_total');
		$data['text_footer'] = $language->get('text_footer');

		$data['logo'] = $order_info['store_url'] . 'image/' . $this->config->get('config_logo');
		$data['store_name'] = $order_info['store_name'];
		$data['store_url'] = $order_info['store_url'];
		$data['customer_id'] = $order_info['customer_id'];
		$data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];

		if ($download_status) {
			$data['download'] = $order_info['store_url'] . 'index.php?route=account/download';
		} else {
			$data['download'] = '';
		}

		$data['order_id'] = $order_info['order_id'];
		$data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
		$data['payment_method'] = $order_info['payment_method'];
		$data['shipping_method'] = $order_info['shipping_method'];
		$data['email'] = $order_info['email'];
		$data['telephone'] = $order_info['telephone'];
		$data['ip'] = $order_info['ip'];

		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");
	
		if ($order_status_query->num_rows) {
			$data['order_status'] = $order_status_query->row['name'];
		} else {
			$data['order_status'] = '';
		}

		if ($comment && $notify) {
			$data['comment'] = nl2br($comment);
		} else {
			$data['comment'] = '';
		}

		if ($order_info['payment_address_format']) {
			$format = $order_info['payment_address_format'];
		} else {
			$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => $order_info['payment_firstname'],
			'lastname'  => $order_info['payment_lastname'],
			'company'   => $order_info['payment_company'],
			'address_1' => $order_info['payment_address_1'],
			'address_2' => $order_info['payment_address_2'],
			'city'      => $order_info['payment_city'],
			'postcode'  => $order_info['payment_postcode'],
			'zone'      => $order_info['payment_zone'],
			'zone_code' => $order_info['payment_zone_code'],
			'country'   => $order_info['payment_country']
		);

		$data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

		if ($order_info['shipping_address_format']) {
			$format = $order_info['shipping_address_format'];
		} else {
			$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			'{zone_code}',
			'{country}'
		);

		$replace = array(
			'firstname' => $order_info['shipping_firstname'],
			'lastname'  => $order_info['shipping_lastname'],
			'company'   => $order_info['shipping_company'],
			'address_1' => $order_info['shipping_address_1'],
			'address_2' => $order_info['shipping_address_2'],
			'city'      => $order_info['shipping_city'],
			'postcode'  => $order_info['shipping_postcode'],
			'zone'      => $order_info['shipping_zone'],
			'zone_code' => $order_info['shipping_zone_code'],
			'country'   => $order_info['shipping_country']
		);

		$data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

		$this->load->model('tool/upload');

		// Products
		$data['products'] = array();

		foreach ($order_products as $order_product) {
			$option_data = array();

			$order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);

			foreach ($order_options as $order_option) {
				if ($order_option['type'] != 'file') {
					$value = $order_option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);

					if ($upload_info) {
						$value = $upload_info['name'];
					} else {
						$value = '';
					}
				}

				$option_data[] = array(
					'name'  => $order_option['name'],
					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
				);
			}

			$data['products'][] = array(
				'name'     => $order_product['name'],
				'model'    => $order_product['model'],
				'option'   => $option_data,
				'quantity' => $order_product['quantity'],
				'price'    => $this->currency->format($order_product['price'] + ($this->config->get('config_tax') ? $order_product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
				'total'    => $this->currency->format($order_product['total'] + ($this->config->get('config_tax') ? ($order_product['tax'] * $order_product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
			);
		}

		// Vouchers
		$data['vouchers'] = array();

		$order_vouchers = $this->model_checkout_order->getOrderVouchers($order_info['order_id']);

		foreach ($order_vouchers as $order_voucher) {
			$data['vouchers'][] = array(
				'description' => $order_voucher['description'],
				'amount'      => $this->currency->format($order_voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
			);
		}

		// Order Totals
		$data['totals'] = array();
		
		$order_totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);

		foreach ($order_totals as $order_total) {
			$data['totals'][] = array(
				'title' => $order_total['title'],
				'text'  => $this->currency->format($order_total['value'], $order_info['currency_code'], $order_info['currency_value']),
			);
		}
		
        $this->load->model('setting/setting');

		// Отримуємо код методу оплати
		$payment_code = isset($order_info['payment_code']) ? $order_info['payment_code'] : '';
		$generate_pdf = ($payment_code != 'wayforpay');

		// Генеруємо PDF тільки якщо це не wayforpay
		$pdf_file = false;
		if ($generate_pdf) {
			try {
				$pdf_file = $this->generatePdfInvoice($order_info);
				if ($pdf_file) {
					$this->log->write('PDF generated successfully: ' . $pdf_file);
				} else {
					$this->log->write('Failed to generate PDF');
				}
			} catch (Exception $e) {
				$this->log->write('Error generating PDF: ' . $e->getMessage());
				$pdf_file = false;
			}
		}

		// Prepare and send email
		$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
		if (!$from) {
			$from = $this->config->get('config_email');
		}

		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($order_info['email']);
		$mail->setFrom($from);
		$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(html_entity_decode(sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), ENT_QUOTES, 'UTF-8'));
		$mail->setHtml($this->load->view('mail/order_add', $data));

		// Attach PDF if generated successfully
		if ($generate_pdf && $pdf_file && file_exists($pdf_file)) {
			try {
				$mail->addAttachment($pdf_file, 'Invoice_' . $order_info['order_id'] . '.pdf');
			} catch (Exception $e) {
				$this->log->write('Error attaching PDF: ' . $e->getMessage());
			}
		}

		// Send email
		try {
			$mail->send();
			$this->log->write('Email sent successfully');
		} catch (Exception $e) {
			$this->log->write('Error sending email: ' . $e->getMessage());
		}

		// Clean up PDF file
		if ($generate_pdf && $pdf_file && file_exists($pdf_file)) {
			try {
				unlink($pdf_file);
				$this->log->write('Temporary PDF file deleted');
			} catch (Exception $e) {
				$this->log->write('Error deleting temporary PDF file: ' . $e->getMessage());
			}
		}
	}

	protected function generatePdfInvoice($order_info) {
        try {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/wayforpay_products');
            
            // Create PDF with custom class
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($this->config->get('config_name'));
            $pdf->SetTitle('Рахунок #' . $order_info['order_id']);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(50);
            
            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, 60);
            
            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // Add first page
            $pdf->AddPage();
            
            // Invoice title
            $pdf->SetY(70);
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 10, 'Рахунок на оплату №' . $order_info['order_id'] . ' від ' . date('d.m.Y', strtotime($order_info['date_added'])), 0, 1, 'C');
            
            // Supplier information
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->Ln(5);
            $this->addSupplierInfo($pdf, $order_info['order_id'], $order_info);
            
            // Customer information
            $pdf->Ln(5);
            $this->addCustomerInfo($pdf, $order_info);
            
            // Products table
            $pdf->Ln(10);
            
            // Define column widths
            $widths = array(
                'name' => 75,
                'model' => 30,
                'quantity' => 20,
                'price' => 25,
                'total' => 25
            );
            
            $this->addProductsTable($pdf, $order_info['order_id'], $widths);
            
            $pdf->setLastPage(true);
            
            // Create file in temp directory
            $pdf_file = DIR_STORAGE . 'temp/invoice_' . $order_info['order_id'] . '_' . time() . '.pdf';
            
            // Save PDF to file
            $pdf->Output($pdf_file, 'F');
            
            return $pdf_file;
            
        } catch (Exception $e) {
            $this->log->write('Failed to generate PDF invoice: ' . $e->getMessage());
            return false;
        }
    }

    private function addSupplierInfo($pdf, $order_id, $order_info) {
        $pdf->Cell(0, 6, 'Постачальник:', 0, 1);
        $pdf->Cell(0, 6, 'Товариство з обмеженою відповідальністю "Навітех"', 0, 1);
        $pdf->Cell(0, 6, 'Отримувач: ТОВ "НАВІТЕХ"', 0, 1);
        $pdf->Cell(0, 6, 'IBAN: UA813052990000026003031002657', 0, 1);
        $pdf->Cell(0, 6, 'ЄДРПОУ: 39811454', 0, 1);
        $pdf->Cell(0, 6, 'Призначення платежу: Оплата згідно рахунку №' . $order_id . ' від ' . date('d.m.Y', strtotime($order_info['date_added'])), 0, 1);
    }
    
    private function addCustomerInfo($pdf, $order_info) {
        $pdf->Cell(0, 6, 'Покупець:', 0, 1);
        $pdf->Cell(0, 6, $order_info['firstname'] . ' ' . $order_info['lastname'], 0, 1);
        $pdf->Cell(0, 6, 'Тел.: ' . $order_info['telephone'], 0, 1);
        $pdf->Cell(0, 6, 'Email: ' . $order_info['email'], 0, 1);
    }
    
    private function addProductsTable($pdf, $order_id, $widths) {
        $pdf->SetFont('dejavusans', 'B', 10);
        
        // Table header
        $pdf->Cell($widths['name'], 7, 'Товар', 1);
        $pdf->Cell($widths['model'], 7, 'Артикул', 1);
        $pdf->Cell($widths['quantity'], 7, 'Кількість', 1);
        $pdf->Cell($widths['price'], 7, 'Ціна', 1);
        $pdf->Cell($widths['total'], 7, 'Всього', 1);
        $pdf->Ln();
        
        // Products
        $pdf->SetFont('dejavusans', '', 9);
        
        $order_products = $this->model_checkout_order->getOrderProducts($order_id);
        $total_items = count($order_products);
        $total_sum = 0;
        
        foreach ($order_products as $product) {
            $alternative_info = $this->model_extension_payment_wayforpay_products->getAlternativeProductName($product['product_id']);
            
            $name = $alternative_info ? $alternative_info['alternative_name'] : $product['name'];
            $model = $alternative_info ? $alternative_info['article'] : $product['model'];
            
            // Calculate required height for both product name and model
            $name_lines = $pdf->getNumLines($name, $widths['name']);
            $model_lines = $pdf->getNumLines($model, $widths['model']);
            $cell_height = max($name_lines, $model_lines) * 6;
            $cell_height = max($cell_height, 6);
            
            if ($pdf->GetY() + $cell_height > $pdf->getPageHeight() - 60) {
                $pdf->AddPage();
            }
            
            $start_y = $pdf->GetY();
            
            // Draw cell borders
            $pdf->Cell($widths['name'], $cell_height, '', 1);
            $pdf->Cell($widths['model'], $cell_height, '', 1);
            $pdf->Cell($widths['quantity'], $cell_height, '', 1);
            $pdf->Cell($widths['price'], $cell_height, '', 1);
            $pdf->Cell($widths['total'], $cell_height, '', 1);
            
            // Reset position and fill content
            $pdf->SetXY($pdf->GetX() - array_sum($widths), $start_y);
            
            $pdf->MultiCell($widths['name'], 6, $name, 0, 'L');
            $pdf->SetXY($pdf->GetX() + $widths['name'], $start_y);
            
            $price = ceil($product['price']);
            $total = ceil($product['quantity'] * $product['price']);
            
            $current_x = $pdf->GetX();
            $current_y = $pdf->GetY();
            
            $pdf->MultiCell($widths['model'], 6, $model, 0, 'C');
            
            $pdf->SetXY($current_x + $widths['model'], $current_y);
            
            $pdf->Cell($widths['quantity'], $cell_height, $product['quantity'], 0, 0, 'C');
            $pdf->Cell($widths['price'], $cell_height, number_format($price, 0, '.', ' '), 0, 0, 'R');
            $pdf->Cell($widths['total'], $cell_height, number_format($total, 0, '.', ' '), 0, 0, 'R');
            
            $pdf->SetY($start_y + $cell_height);
            
            $total_sum += $total;
        }
        
        $this->addTotals($pdf, $total_items, $total_sum);
    }
    
    private function addTotals($pdf, $total_items, $total_sum) {
        $pdf->Ln(15);
        $pdf->SetFont('dejavusans', '', 10);
        
        $total_sum = ceil($total_sum);
        
        // Left side - total items and sum
        $total_text = sprintf('Всього найменувань %d, на суму %s грн', 
            $total_items,
            number_format($total_sum, 0, '.', ' ')
        );
        $pdf->MultiCell(120, 6, $total_text, 0, 'L');
        
        // Right side - total and VAT
        $y_position = $pdf->GetY() - 12;
        $pdf->SetY($y_position);
        $pdf->SetX(120);
        
        // Total sum
        $pdf->Cell(45, 6, 'Разом:', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($total_sum, 0, '.', ' ') . ' грн', 0, 1, 'R');
        
        // VAT
        $vat = ceil($total_sum / 6);
        $pdf->SetX(120);
        $pdf->Cell(45, 6, 'У тому числі ПДВ:', 0, 0, 'R');
        $pdf->Cell(30, 6, number_format($vat, 0, '.', ' ') . ' грн', 0, 1, 'R');
    }
	
	
	public function edit($order_info, $order_status_id, $comment) {
		$language = new Language($order_info['language_code']);
		$language->load($order_info['language_code']);
		$language->load('mail/order_edit');

		$data['text_order_id'] = $language->get('text_order_id');
		$data['text_date_added'] = $language->get('text_date_added');
		$data['text_order_status'] = $language->get('text_order_status');
		$data['text_link'] = $language->get('text_link');
		$data['text_comment'] = $language->get('text_comment');
		$data['text_footer'] = $language->get('text_footer');

		$data['order_id'] = $order_info['order_id'];
		$data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
		
		$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");
	
		if ($order_status_query->num_rows) {
			$data['order_status'] = $order_status_query->row['name'];
		} else {
			$data['order_status'] = '';
		}

		if ($order_info['customer_id']) {
			$data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_info['order_id'];
		} else {
			$data['link'] = '';
		}

		$data['comment'] = strip_tags($comment);

		$this->load->model('setting/setting');
		
		$from = $this->model_setting_setting->getSettingValue('config_email', $order_info['store_id']);
		
		if (!$from) {
			$from = $this->config->get('config_email');
		}
		
		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($order_info['email']);
		$mail->setFrom($from);
		$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(html_entity_decode(sprintf($language->get('text_subject'), $order_info['store_name'], $order_info['order_id']), ENT_QUOTES, 'UTF-8'));
		$mail->setText($this->load->view('mail/order_edit', $data));
		$mail->send();
	}
	
	// Admin Alert Mail
	public function alert(&$route, &$args) {
		if (isset($args[0])) {
			$order_id = $args[0];
		} else {
			$order_id = 0;
		}
		
		if (isset($args[1])) {
			$order_status_id = $args[1];
		} else {
			$order_status_id = 0;
		}	
		
		if (isset($args[2])) {
			$comment = $args[2];
		} else {
			$comment = '';
		}
		
		if (isset($args[3])) {
			$notify = $args[3];
		} else {
			$notify = '';
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		if ($order_info && !$order_info['order_status_id'] && $order_status_id && in_array('order', (array)$this->config->get('config_mail_alert'))) {	
			$this->load->language('mail/order_alert');
			
			// HTML Mail
			$data['text_received'] = $this->language->get('text_received');
			$data['text_order_id'] = $this->language->get('text_order_id');
			$data['text_date_added'] = $this->language->get('text_date_added');
			$data['text_order_status'] = $this->language->get('text_order_status');
			$data['text_product'] = $this->language->get('text_product');
			$data['text_total'] = $this->language->get('text_total');
			$data['text_comment'] = $this->language->get('text_comment');
			
			$data['order_id'] = $order_info['order_id'];
			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));

			$order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

			if ($order_status_query->num_rows) {
				$data['order_status'] = $order_status_query->row['name'];
			} else {
				$data['order_status'] = '';
			}

			$this->load->model('tool/upload');
			
			$data['products'] = array();

			$order_products = $this->model_checkout_order->getOrderProducts($order_id);

			foreach ($order_products as $order_product) {
				$option_data = array();
				
				$order_options = $this->model_checkout_order->getOrderOptions($order_info['order_id'], $order_product['order_product_id']);
				
				foreach ($order_options as $order_option) {
					if ($order_option['type'] != 'file') {
						$value = $order_option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($order_option['value']);
	
						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$option_data[] = array(
						'name'  => $order_option['name'],
						'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
					);					
				}
					
				$data['products'][] = array(
					'name'     => $order_product['name'],
					'model'    => $order_product['model'],
					'quantity' => $order_product['quantity'],
					'option'   => $option_data,
					'total'    => html_entity_decode($this->currency->format($order_product['total'] + ($this->config->get('config_tax') ? ($order_product['tax'] * $order_product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8')
				);
			}
			
			$data['vouchers'] = array();
			
			$order_vouchers = $this->model_checkout_order->getOrderVouchers($order_id);

			foreach ($order_vouchers as $order_voucher) {
				$data['vouchers'][] = array(
					'description' => $order_voucher['description'],
					'amount'      => html_entity_decode($this->currency->format($order_voucher['amount'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8')
				);					
			}

			$data['totals'] = array();
			
			$order_totals = $this->model_checkout_order->getOrderTotals($order_id);

			foreach ($order_totals as $order_total) {
				$data['totals'][] = array(
					'title' => $order_total['title'],
					'value' => html_entity_decode($this->currency->format($order_total['value'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8')
				);
			}

			$data['comment'] = strip_tags($order_info['comment']);

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), $this->config->get('config_name'), $order_info['order_id']), ENT_QUOTES, 'UTF-8'));
			$mail->setText($this->load->view('mail/order_alert', $data));
			$mail->send();

			// Send to additional alert emails
			$emails = explode(',', $this->config->get('config_mail_alert_email'));

			foreach ($emails as $email) {
				$email = trim($email);
				if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$mail->setTo($email);
					$mail->send();
				}
			}
		}
	}
}
