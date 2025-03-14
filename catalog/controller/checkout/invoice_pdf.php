<?php
// catalog/controller/checkout/invoice_pdf.php

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

class ControllerCheckoutInvoicePdf extends Controller {
    public function index() {
        if (!isset($this->request->get['order_id'])) {
            exit('No order ID specified');
        }
    
        $order_id = (int)$this->request->get['order_id'];
        
        $this->load->model('checkout/order');
        // Додаємо завантаження моделі wayforpay_products
        $this->load->model('extension/payment/wayforpay_products');
        
        $order_info = $this->model_checkout_order->getOrder($order_id);
        
        if (!$order_info) {
            exit('Order not found');
        }
    
        // Спрощена перевірка доступу
        if ($this->customer->isLogged()) {
            // Для авторизованих перевіряємо належність замовлення
            if ($order_info['customer_id'] != $this->customer->getId()) {
                exit('Access denied');
            }
        } else {
            // Для неавторизованих перевіряємо токен
            $expected_token = md5($order_id . $this->config->get('config_encryption'));
            if (!isset($this->request->get['token']) || $this->request->get['token'] !== $expected_token) {
                exit('Access denied');
            }
        }

        // Create PDF
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($this->config->get('config_name'));
        $pdf->SetTitle('Рахунок #' . $order_id);
        
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
        
        // Invoice title (only on first page)
        $pdf->SetY(70);
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'Рахунок на оплату №' . $order_id . ' від ' . date('d.m.Y', strtotime($order_info['date_added'])), 0, 1, 'C');
        
        // Supplier information
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Ln(5);
        $this->addSupplierInfo($pdf, $order_id, $order_info);
        
        // Customer information
        $pdf->Ln(5);
        $this->addCustomerInfo($pdf, $order_info);
        
        // Products table
        $pdf->Ln(10);
        
        // Define column widths (adjust these as needed)
        $widths = array(
            'name' => 75,
            'model' => 30,
            'quantity' => 20,
            'price' => 25,
            'total' => 25
        );
        
        
        // Add products table
        $this->addProductsTable($pdf, $order_id, $widths);
        
        $pdf->setLastPage(true);

        // Output PDF
        $pdf->Output('invoice_' . $order_id . '.pdf', 'I');
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
            // Беремо максимальну кількість рядків з двох колонок і множимо на висоту рядка
            $cell_height = max($name_lines, $model_lines) * 6;
            
            // Мінімальна висота клітинки
            $cell_height = max($cell_height, 6);
            
            // Check if we need a new page
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
            
            // Product name with word wrap
            $pdf->MultiCell($widths['name'], 6, $name, 0, 'L');
            $pdf->SetXY($pdf->GetX() + $widths['name'], $start_y);
            
            // Other cells
            $price = ceil($product['price']);
            $total = ceil($product['quantity'] * $product['price']);
            
            // Зберігаємо поточну позицію
            $current_x = $pdf->GetX();
            $current_y = $pdf->GetY();
            
            // Використовуємо MultiCell для моделі
            $pdf->MultiCell($widths['model'], 6, $model, 0, 'C');
            
            // Повертаємося до позиції після колонки моделі
            $pdf->SetXY($current_x + $widths['model'], $current_y);
            
            // Додаємо решту колонок
            $pdf->Cell($widths['quantity'], $cell_height, $product['quantity'], 0, 0, 'C');
            $pdf->Cell($widths['price'], $cell_height, number_format($price, 0, '.', ' '), 0, 0, 'R');
            $pdf->Cell($widths['total'], $cell_height, number_format($total, 0, '.', ' '), 0, 0, 'R');
            
            $pdf->SetY($start_y + $cell_height);
            
            $total_sum += $total;
        }
        
        // Add totals
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
}