<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ControllerOCTemplatesEventsHelper extends Controller {

    public function compareRemove() {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

            $this->load->language('octemplates/oct_showcase');
        
            $json = array();
        
            if (!isset($this->session->data['compare']) || !isset($this->request->post['product_id'])) {
                $json['error'] = $this->language->get('text_empty');
            } else {
                $product_id = (int)$this->request->post['product_id'];
        
                $this->load->model('catalog/product');
        
                $product_info = $this->model_catalog_product->getProduct($product_id);
        
                if ($product_info) {
                    if (in_array($product_id, $this->session->data['compare'])) {
                        $key = array_search($product_id, $this->session->data['compare']);
                        unset($this->session->data['compare'][$key]);
                        $json['success'] = sprintf($this->language->get('compare_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('product/compare'));
                        $json['total_compare'] = (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);
                    }
                }
            }
        
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
    }

    public function wishlistRemove() {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->load->language('octemplates/oct_showcase');
        
            $json = array();
        
            if (isset($this->request->post['product_id'])) {
                $product_id = (int)$this->request->post['product_id'];
            } else {
                $product_id = 0;
            }
        
            $this->load->model('catalog/product');
        
            $product_info = $this->model_catalog_product->getProduct($product_id);
        
            if ($product_info) {
                if ($this->customer->isLogged()) {
                    // Edit customers wishlist
                    $this->load->model('account/wishlist');
        
                    $this->model_account_wishlist->deleteWishlist($this->request->post['product_id']);
        
                    $json['success'] = sprintf($this->language->get('wishlist_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
                    $json['total_wishlist'] = $this->model_account_wishlist->getTotalWishlist();
                } else {
                    if (isset($this->session->data['wishlist']) && (($key = array_search($this->request->post['product_id'], $this->session->data['wishlist'])) !== false)) {
                        unset($this->session->data['wishlist'][$key]);
                    }
        
                    $json['success'] = sprintf($this->language->get('wishlist_remove_success'), $this->url->link('product/product', 'product_id=' . (int)$this->request->post['product_id']), $product_info['name'], $this->url->link('account/wishlist'));
                    $json['total_wishlist'] = (isset($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0);
                }
            }
        
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
    }

    public function allCartProducts() {
        
        $this->load->model('octemplates/helper');
		$product_ids = $this->model_octemplates_helper->getOctCartProducts();

		if (!empty($product_ids)) {
			return implode(',', $product_ids);
		}

		return '';
    }

}