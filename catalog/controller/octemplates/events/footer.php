<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsFooter extends Controller {

    public function index(&$route, &$data) {
        $this->loadData($data);
        $this->loadCustomerPayments($data);
        $this->checkIsiOS($data);
        $this->loadSubscribeData($data);
        $this->loadFeedbackData($data);
        $this->loadPolicyValue($data);
        $this->loadLazyLoadScript();
        $this->loadFooterLinks($data);
        $this->loadInformationLinks($data);
        $this->loadContactLinks($data);
        $this->loadCategoryLinks($data);
        $this->loadContactOpen($data);
        $this->loadContactTelephones($data);
        $this->loadContactAddress($data);
        $this->loadAnalyticsData($data);
    }

    private function loadData(&$data) {
        $data['oct_showcase_data'] = $this->config->get('theme_oct_showcase_data');
        $data['oct_popup_call_phone_status'] = $this->config->get('oct_popup_call_phone_status');
        $data['oct_lang_id'] = (int)$this->config->get('config_language_id');
        $data['oct_jscode'] = html_entity_decode($this->config->get('theme_oct_showcase_js_code'), ENT_QUOTES, 'UTF-8');
    }

    private function loadCustomerPayments(&$data) {
        $oct_showcase_data = isset($data['oct_showcase_data']) ? $data['oct_showcase_data'] : [];

        $data['oct_customer_payments'] = [];

        if (isset($oct_showcase_data['payments']['customers']) && !empty($oct_showcase_data['payments']['customers'])) {
            
            $this->load->model('tool/image');

            foreach ($oct_showcase_data['payments']['customers'] as $payment) {
                if ((isset($payment['status']) && $payment['status'] == 'on') && isset($payment['image']) && !empty($payment['image']) && file_exists(DIR_IMAGE . $payment['image'])) {
                    $data['oct_customer_payments'][] = $this->model_tool_image->resize($payment['image'], 52, 32);
                }
            }
        }
    }

    private function checkIsiOS(&$data) {
        if ($this->registry->has('oct_mobiledetect')) {
            $oct_mobiledetect = $this->oct_mobiledetect;
            if (($oct_mobiledetect->isiOS() || $oct_mobiledetect->isiPad())) {
                $data['oct_isiOS'] = 1;
            }
        }
    }

    private function loadSubscribeData(&$data) {
        $data['oct_subscribe_form_data'] = $this->config->get('oct_subscribe_form_data');
        $data['oct_subscribe_status'] = $this->config->get('oct_subscribe_status');
        $data['oct_subscribe_day_now'] = date("Y-m-d H:i:s");

        if (isset($data['oct_showcase_data']['footer_subscribe']) && $data['oct_showcase_data']['footer_subscribe'] == 'on') {
            $data['oct_subscribe'] = $this->load->controller('octemplates/module/oct_subscribe');
        }
    }

    private function loadFeedbackData(&$data) {
        if ($this->config->get('theme_oct_showcase_feedback_status')) {
            $data['oct_feedback_data'] = $this->config->get('theme_oct_showcase_feedback_data');
            $data['oct_popup_call_phone_status'] = $this->config->get('oct_popup_call_phone_status');
        }
    }

    private function loadPolicyValue(&$data) {
        $oct_policy_status = $this->config->get('oct_policy_status');
        $oct_policy_data = $this->config->get('oct_policy_data');

        if (isset($oct_policy_data['value']) && $oct_policy_data['value'] && !empty($oct_policy_data['value']) && ($oct_policy_status && (!isset($this->request->cookie[$oct_policy_data['value']]) || !$this->request->cookie[$oct_policy_data['value']])) && $this->config->get('config_maintenance') == 0) {
            $data['oct_policy_value'] = $oct_policy_data['value'];
        }
    }

    private function loadLazyLoadScript() {
        $lazy_link = 'catalog/view/theme/oct_showcase/js/lozad.js';

        $oct_mobiledetect = null;
        if ($this->registry->has('oct_mobiledetect')) {
            $oct_mobiledetect = $this->oct_mobiledetect;
        }

        $is_mobile = $oct_mobiledetect && $oct_mobiledetect->isMobile();
        $is_tablet = $oct_mobiledetect && $oct_mobiledetect->isTablet();
        $is_desktop = !$is_mobile && !$is_tablet;

        $lazyload_mobile = $this->config->get('theme_oct_showcase_lazyload_mobile');
        $lazyload_tablet = $this->config->get('theme_oct_showcase_lazyload_tablet');
        $lazyload_desktop = $this->config->get('theme_oct_showcase_lazyload_desktop');

        if ($is_mobile && $lazyload_mobile) {
            $this->document->addScript($lazy_link, 'header');
        } elseif ($is_tablet && $lazyload_tablet) {
            $this->document->addScript($lazy_link, 'header');
        } elseif ($is_desktop && $lazyload_desktop) {
            $this->document->addScript($lazy_link, 'header');
        }
    }

    private function loadFooterLinks(&$data) {
        $oct_showcase_data = isset($data['oct_showcase_data']) ? $data['oct_showcase_data'] : [];

        $data['informations'] = [];

        if (isset($oct_showcase_data['footer_links']) && !empty($oct_showcase_data['footer_links'])) {
            $language_id = (int)$this->config->get('config_language_id');

            foreach ($oct_showcase_data['footer_links'] as $footer_link) {
                $title = html_entity_decode($footer_link[$language_id]['title'], ENT_QUOTES, 'UTF-8');
                $href = $footer_link[$language_id]['link'];
                $data['informations'][] = compact('title', 'href');
            }
        }
    }

    private function loadInformationLinks(&$data) {
        if (empty($data['informations'])) {
            $this->load->model('catalog/information');
            $language_id = (int)$this->config->get('config_language_id');

            foreach ($this->model_catalog_information->getInformations() as $result) {
                if ($result['bottom']) {
                    $title = $result['title'];
                    $href = $this->url->link('information/information', 'information_id=' . $result['information_id']);
                    $data['informations'][] = compact('title', 'href');
                }
            }
        }
    }

    private function loadContactLinks(&$data) {
        $oct_showcase_data = isset($data['oct_showcase_data']) ? $data['oct_showcase_data'] : [];

        $information_links = [
            'footer_link_contact' => [
                'title' => $this->language->get('text_contact'),
                'href' => $this->url->link('information/contact')
            ],
            'footer_link_return' => [
                'title' => $this->language->get('text_return'),
                'rel' => 1,
                'href' => $this->url->link('account/return/add', '', true)
            ],
            'footer_link_sitemap' => [
                'title' => $this->language->get('text_sitemap'),
                'href' => $this->url->link('information/sitemap')
            ],
            'footer_link_man' => [
                'title' => $this->language->get('text_manufacturer'),
                'href' => $this->url->link('product/manufacturer')
            ],
            'footer_link_cert' => [
                'title' => $this->language->get('text_voucher'),
                'rel' => 1,
                'href' => $this->url->link('account/voucher', '', true)
            ],
            'footer_link_specials' => [
                'title' => $this->language->get('text_special'),
                'href' => $this->url->link('product/special')
            ]
        ];

        foreach ($information_links as $link => $info) {
            if (isset($oct_showcase_data[$link]) && $oct_showcase_data[$link] == 'on') {
                $data['informations'][] = $info;
            }
        }
    }

    private function loadCategoryLinks(&$data) {
        $oct_showcase_data = isset($data['oct_showcase_data']) ? $data['oct_showcase_data'] : [];

        if (isset($oct_showcase_data['footer_category_links']) && !empty($oct_showcase_data['footer_category_links'])) {
            $language_id = (int)$this->config->get('config_language_id');
            $currency = $this->session->data['currency'];
            $http_accept = isset($this->request->server['HTTP_ACCEPT']) ? $this->request->server['HTTP_ACCEPT'] : '';
            $oct_webP = strpos($http_accept, 'webp') !== false ? '1-' . $currency : '0-' . $currency;
            $cache_key = 'octemplates.footer_category.' . $language_id . '.' . $this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . $oct_webP;

            $footer_cat_links = $this->cache->get($cache_key);

            if (!$footer_cat_links) {
                $this->load->model('catalog/category');

                $footer_cat_links = [];

                foreach ($oct_showcase_data['footer_category_links'] as $category_id) {
                    $category_info = $this->model_catalog_category->getOCTCategory($category_id);

                    if ($category_info) {
                        $path = ($category_info['path']) ? $category_info['path'] . '_' . $category_info['category_id'] : $category_info['category_id'];

                        $footer_cat_links[] = array(
                            'name' => $category_info['name'],
                            'href' => $this->url->link('product/category', 'path=' . $path, true)
                        );
                    }
                }

                $this->cache->set($cache_key, $footer_cat_links);
            }

            $data['categories'] = $footer_cat_links;
        }
    }

    private function loadContactOpen(&$data) {
        $oct_showcase_data = isset($data['oct_showcase_data']) ? $data['oct_showcase_data'] : [];

        if (isset($oct_showcase_data['contact_open'][$this->config->get('config_language_id')])) {
            $oct_contact_opens = explode(PHP_EOL, $oct_showcase_data['contact_open'][$this->config->get('config_language_id')]);

            foreach ($oct_contact_opens as $oct_contact_open) {
                if (!empty($oct_contact_open)) {
                    $data['oct_contact_opens'][] = html_entity_decode($oct_contact_open, ENT_QUOTES, 'UTF-8');
                }
            }
        }
    }

    private function loadContactTelephones(&$data) {
        $oct_contact_telephones = explode(PHP_EOL, $data['oct_showcase_data']['contact_telephone']);

        foreach ($oct_contact_telephones as $oct_contact_telephone) {
            if (!empty($oct_contact_telephone)) {
                $data['oct_contact_telephones'][] = html_entity_decode(trim($oct_contact_telephone), ENT_QUOTES, 'UTF-8');
            }
        }
    }

    private function loadContactAddress(&$data) {
        if (isset($data['oct_showcase_data']['contact_address'])) {
            foreach ($data['oct_showcase_data']['contact_address'] as $oct_lang_id => $oct_address) {
                $data['oct_showcase_data']['contact_address'][$oct_lang_id] = html_entity_decode($oct_address, ENT_QUOTES, 'UTF-8');
            }
        }
    }

    private function loadAnalyticsData(&$data) {
        $analytics_status = $this->config->get('analytics_oct_analytics_status');
        $analytics_position = $this->config->get('analytics_oct_analytics_position');

        if ($analytics_status && $analytics_position == 1) {
            $google_status = $this->config->get('analytics_oct_analytics_google_status');
            $analytics_targets = $google_status ? $this->config->get('analytics_oct_analytics_targets') : [];

            $data['oct_analytics_google_status'] = $google_status;
            $data['oct_analytics_targets'] = $analytics_targets;
        } else {
            $data['oct_analytics_google_status'] = false;
            $data['oct_analytics_targets'] = [];
        }
    }


}