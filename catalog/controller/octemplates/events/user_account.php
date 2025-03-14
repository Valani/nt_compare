<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsUserAccount extends Controller {

    public function userFront(&$route, &$data) {

        $data['download_view'] = $this->config->get('module_account_download_view');
        $data['recurring_view'] = $this->config->get('module_account_recurring_view');
        $data['reward_view'] = $this->config->get('module_account_reward_view');
        $data['return_view'] = $this->config->get('module_account_return_view');
        $data['transaction_view'] = $this->config->get('module_account_transaction_view');
        $data['newsletter_view'] = $this->config->get('module_account_newsletter_view');
        $data['affiliate_view'] = $this->config->get('module_account_affiliate_view');
        $data['oct_stock_notifier_status'] = $this->config->get('oct_stock_notifier_status');
        $data['oct_stock_notifier'] = $this->url->link('account/oct_stock_notifier', '', true);

        if ($this->customer->isLogged()) {
            $this->load->model('account/customer');

            $affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());

            if (!$affiliate_info) {
                $data['affiliate'] = $this->url->link('account/affiliate/add', '', true);
                $data['tracking'] = '';
            } else {
                $data['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
                $data['tracking'] = $this->url->link('account/tracking', '', true);
            }
        } else {
            $data['affiliate'] = $this->url->link('affiliate/login', '', true);
        }
    }

    public function accountPageInfo(&$route, &$data) {

        $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
        $data['email'] = $customer_info['email'];
        $data['firstname'] = $customer_info['firstname'];
        $data['url_contacts'] = $this->url->link('information/contact', '', true);

        $data['orders'] = [];
        $this->load->model('account/order');
        $order_total = $this->model_account_order->getTotalOrders();
        $page = 1;
        $results = $this->model_account_order->getOrders(($page - 1) * 10, 10);

        foreach ($results as $result) {
            $product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
            $voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);
        
            // Генеруємо токен для безпечного доступу до PDF
            $token = md5($result['order_id'] . $this->config->get('config_encryption'));
        
            $data['orders'][] = [
                'order_id'   => $result['order_id'],
                'status'     => $result['status'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'total'      => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
                'href'       => $this->url->link('account/order/info', 'order_id=' . $result['order_id'], 'SSL'),
                'pdf_url'    => $this->url->link('checkout/invoice_pdf', 'order_id=' . $result['order_id'] . '&token=' . $token, true)
            ];
        }
    }
}