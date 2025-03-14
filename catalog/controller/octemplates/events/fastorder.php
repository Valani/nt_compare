<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsFastorder extends Controller {

    const CHECKOUT_ROUTE = 'checkout/checkout';
    const FASTORDER_ROUTE = 'checkout/oct_fastorder';

    public function index(&$route, &$data) {
        $this->load->model('setting/setting');

        $fastorderSettings = $this->model_setting_setting->getSetting('oct_fastorder_data');
        $fastorderData = $fastorderSettings['oct_fastorder_data'];

        $isCheckoutRoute = ($route == self::CHECKOUT_ROUTE);
        $isFastorderActive = (isset($fastorderData['status']) && $fastorderData['status'] == 1);

        if ($isCheckoutRoute && $isFastorderActive) {
            $route = self::FASTORDER_ROUTE;
        }
    }
}
