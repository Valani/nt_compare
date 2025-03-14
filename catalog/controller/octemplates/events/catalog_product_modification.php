<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsCatalogProductModification extends Controller {

    public function index(&$route, &$data) {
        // Product ID
        $product_id = (int)$this->request->get['product_id'];

        // Get config data
        $data['oct_showcase_data'] = $oct_showcase_data = $this->config->get('theme_oct_showcase_data');
        
        // Product Atributes
        $limit_attr  = $this->config->get('theme_oct_showcase_data_pr_atr_limit') ? $this->config->get('theme_oct_showcase_data_pr_atr_limit') : 5;
        $data['oct_attributs'] = (isset($oct_showcase_data['product_atributes']) && $oct_showcase_data['product_atributes']) ? $this->model_catalog_product->getOctProductAttributes($this->request->get['product_id'], $limit_attr) : '';

        // Popup Purchase
        if ($this->config->get('config_checkout_guest') && $this->config->get('oct_popup_purchase_status')) {
            $data['oct_popup_purchase_status'] = $this->config->get('oct_popup_purchase_status');
        }
        
        // Stock Notify
        if ($this->config->get('oct_stock_notifier_status')) {
            $data['oct_stock_notifier_status'] = $this->config->get('oct_stock_notifier_status');
            $data['oct_stock_notifier_data'] = $this->config->get('oct_stock_notifier_data');
        }

        // One Click Purchase
        if ($this->config->get('config_checkout_guest') && $this->config->get('oct_popup_purchase_byoneclick_status')) {
            $oct_byoneclick_data = $this->config->get('oct_popup_purchase_byoneclick_data');
            $oct_data['oct_byoneclick_status'] = isset($oct_byoneclick_data['product']) ? 1 : 0;
            $oct_data['oct_byoneclick_mask'] = $oct_byoneclick_data['mask'];
            $oct_data['oct_byoneclick_product_id'] = $this->request->get['product_id'];
            $oct_data['oct_byoneclick_page'] = '_product';
            $data['oct_byoneclick'] = $this->load->controller('octemplates/module/oct_popup_purchase/byoneclick', $oct_data);
        }

        // Product Tabs
        $data['oct_product_extra_tabs'] = [];

        if ($this->config->get('oct_product_tabs_status')) {
            $this->load->model('octemplates/module/oct_product_tabs');

            $oct_product_extra_tabs = $this->model_octemplates_module_oct_product_tabs->getProductTabs($product_id);

            if ($oct_product_extra_tabs) {
                foreach ($oct_product_extra_tabs as $extra_tab) {
                    $extra_text = str_replace("<img", "<img class='img-fluid'", html_entity_decode($extra_tab['text'], ENT_QUOTES, 'UTF-8'));

                    $data['oct_product_extra_tabs'][] = [
                        'title' => $extra_tab['title'],
                        'text'  => $extra_text
                    ];
                }
            }
        }

        // Product Other data
        $data['oct_popup_found_cheaper_status'] = $this->config->get('oct_popup_found_cheaper_status');
        $data['oct_stock_display'] = $this->config->get('config_stock_display');

        // Product JS Button
        if (isset($oct_showcase_data['product_js_button']) && !empty($oct_showcase_data['product_js_button'])) {
            $data['product_js_button'] = html_entity_decode($oct_showcase_data['product_js_button'], ENT_QUOTES, 'UTF-8');
        }

        // Product Adnvanced Tab
        if (isset($oct_showcase_data['product_dop_tab']) && !empty($oct_showcase_data['product_dop_tab'])) {
            $data['dop_tab'] = [
                'title' => isset($oct_showcase_data['product_dop_tab_title'][(int)$this->config->get('config_language_id')]) ? html_entity_decode($oct_showcase_data['product_dop_tab_title'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8') : '',
                'text' => isset($oct_showcase_data['product_dop_tab_text'][(int)$this->config->get('config_language_id')]) ? html_entity_decode($oct_showcase_data['product_dop_tab_text'][(int)$this->config->get('config_language_id')], ENT_QUOTES, 'UTF-8') : '',
            ];
        }

        // Product Advantages
        if ((isset($oct_showcase_data['product_advantage']) && $oct_showcase_data['product_advantage'] == 'on') && (isset($oct_showcase_data['product_advantages']) && !empty($oct_showcase_data['product_advantages']))) {
            foreach ($oct_showcase_data['product_advantages'] as $product_advantage) {
                if (isset($product_advantage[(int)$this->config->get('config_language_id')]['title']) && !empty($product_advantage[(int)$this->config->get('config_language_id')]['title'])) {
                    if (isset($product_advantage[(int)$this->config->get('config_language_id')]['link'])) {
                        if ($product_advantage[(int)$this->config->get('config_language_id')]['link'] == "#" || empty($product_advantage[(int)$this->config->get('config_language_id')]['link'])) {
                            $link = "javascript:;";
                        } else {
                            $link = $product_advantage[(int)$this->config->get('config_language_id')]['link'];
                        }
                    } else {
                        $link = "javascript:;";
                    }

                    if (is_file(DIR_IMAGE . $product_advantage['icone'])) {
                        $cached_image = $this->model_tool_image->resize($product_advantage['icone'], 60, 60);
                    } else {
                        $cached_image = $this->model_tool_image->resize('no_image.png', 60, 60);
                    }

                    $data['oct_product_advantages'][] = [
                        'information_id' => isset($product_advantage['information_id']) && !empty($product_advantage['information_id']) ? (int)$product_advantage['information_id'] : 0,
                        'popup' => (isset($product_advantage['popup']) && !empty($product_advantage['popup'])) && (isset($product_advantage['information_id']) && !empty($product_advantage['information_id'])) && (isset($product_advantage['information_id']) && !empty($product_advantage['information_id'])) ? 1 : 0,
                        'icone' => $cached_image,
                        'title' => strip_tags(html_entity_decode($product_advantage[(int)$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8')),
                        'text' => isset($product_advantage[(int)$this->config->get('config_language_id')]['text']) ? nl2br(strip_tags(html_entity_decode($product_advantage[(int)$this->config->get('config_language_id')]['text'], ENT_QUOTES, 'UTF-8'))) : '',
                        'link' => $link,
                    ];
                }
            }
        }

        // Product FaQ
        if (isset($oct_showcase_data['product_faq']) && $oct_showcase_data['product_faq']) {
            $data['oct_product_faq'] = $this->load->controller('octemplates/faq/oct_product_faq', ['from_controller' => true]);

            $data['tab_oct_faq'] = sprintf($this->language->get('tab_oct_faq'), $this->model_octemplates_faq_oct_product_faq->getTotalFaqsByProductId((int)$this->request->get['product_id']));
        }

        // Product Description
        $product_description = str_replace("<img", "<img class='img-fluid'", html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8'));
        $data['description'] = $product_description;

        // Product Timer
        if (isset($oct_showcase_data['product_timer']) && !empty($oct_showcase_data['product_timer'])) {
            $this->load->model('octemplates/timer/special_date');
            $product_info_special = $this->model_octemplates_timer_special_date->getProductSpecialDates($product_id);

            if ($product_info_special && $product_info_special['date_end'] != '0000-00-00') {
                $data['product_timer'] = true;
                $data['special_date_end'] = $product_info_special['date_end'];
            }
        }

        // Product Blog Related Posts
        if (isset($oct_showcase_data['product_blog_related']) && $oct_showcase_data['product_blog_related']) {
            $this->load->model('octemplates/blog/oct_blogarticle');
            $this->load->model('octemplates/blog/oct_blogcategory');
            $oct_blogsettings_data = $this->config->get('oct_blogsettings_data');
            $articles_results = $this->model_octemplates_blog_oct_blogarticle->getArticleRelatedProductPage($product_id);

            if (!empty($articles_results)) {

                foreach ($articles_results as $result) {
                    
                    $imageName = $result['image'] ?? 'placeholder.png';
                    $image = $this->model_tool_image->resize($imageName, $oct_blogsettings_data['dop_article_width'], $oct_blogsettings_data['dop_article_height']);

                    // Get categories 
                    $blog_category_badge = $this->model_octemplates_blog_oct_blogcategory->getBlogCategoryBadges($result['blogarticle_id']);

                    $cleanShortDescription = trim(strip_tags($result['shot_description']));
                    $description = !empty($cleanShortDescription) ? $result['shot_description'] : $result['description'];

                    $data['oct_related_articles'][] = [
                        'blogarticle_id'   => $result['blogarticle_id'],
                        'thumb'            => $image,
                        'width'            => $oct_blogsettings_data['dop_article_width'],
                        'height'           => $oct_blogsettings_data['dop_article_height'],
                        'name'             => $result['name'],
                        'blog_categories'  => $blog_category_badge,
                        'description'      => utf8_substr(trim(strip_tags(html_entity_decode($description, ENT_QUOTES, 'UTF-8'))), 0, $oct_blogsettings_data['description_length']) . '..',
                        'date_added'       => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'href'             => $this->url->link('octemplates/blog/oct_blogarticle', 'blogarticle_id=' . $result['blogarticle_id'])
                    ];
                }
            }
        }
    }
}
