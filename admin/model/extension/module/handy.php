<?php

/**
 * @category   OpenCart
 * @package    Handy Product Manager
 * @copyright  © Serge Tkach, 2018–2024, https://sergetkach.com/
 * Base methods was cloned from model/catalog/product
 */

class ModelExtensionModuleHandy extends Model {
	private $matex = false;
	
	
	/* Cloned systems methods (can be modified)
	--------------------------------------------------------------------------- */

	//////////////////////////////////////////////////////////////////////////////
	public function addProduct($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int) $data['quantity'] . "', minimum = '" . (int) $data['minimum'] . "', subtract = '" . (int) $data['subtract'] . "', stock_status_id = '" . (int) $data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int) $data['manufacturer_id'] . "', shipping = '" . (int) $data['shipping'] . "', price = '" . (float) $data['price'] . "', points = '" . (int) $data['points'] . "', weight = '" . (float) $data['weight'] . "', weight_class_id = '" . (int) $data['weight_class_id'] . "', length = '" . (float) $data['length'] . "', width = '" . (float) $data['width'] . "', height = '" . (float) $data['height'] . "', length_class_id = '" . (int) $data['length_class_id'] . "', status = '" . (int) $data['status'] . "', tax_class_id = '" . (int) $data['tax_class_id'] . "', sort_order = '" . (int) $data['sort_order'] . "', date_added = NOW()");

		$product_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int) $product_id . "'");
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int) $product_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if (isset($data['product_store'])) {
			foreach ($data['product_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "'");
			}
		}

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "' AND language_id = '" . (int) $language_id . "'");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $product_id . "', attribute_id = '" . (int) $product_attribute['attribute_id'] . "', language_id = '" . (int) $language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', required = '" . (int) $product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int) $product_option_id . "', product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', option_value_id = '" . (int) $product_option_value['option_value_id'] . "', quantity = '" . (int) $product_option_value['quantity'] . "', subtract = '" . (int) $product_option_value['subtract'] . "', price = '" . (float) $product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int) $product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float) $product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int) $product_option['required'] . "'");
				}
			}
		}

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_discount['customer_group_id'] . "', quantity = '" . (int) $product_discount['quantity'] . "', priority = '" . (int) $product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_special['customer_group_id'] . "', priority = '" . (int) $product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int) $product_image['sort_order'] . "'");
			}
		}

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int) $product_id . "', download_id = '" . (int) $download_id . "'");
			}
		}

		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $product_id . "', category_id = '" . (int) $category_id . "'");
			}
		}

		// handy . begin
		if (isset($data['product_main_category'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int) $product_id . "' AND category_id = '" . (int) $data['product_main_category'] . "'");
		}
		// handy . end

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int) $product_id . "', filter_id = '" . (int) $filter_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "' AND related_id = '" . (int) $related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $product_id . "', related_id = '" . (int) $related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $related_id . "' AND related_id = '" . (int) $product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $related_id . "', related_id = '" . (int) $product_id . "'");
			}
		}

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
				if ((int) $product_reward['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $customer_group_id . "', points = '" . (int) $product_reward['points'] . "'");
				}
			}
		}

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout_id . "'");
			}
		}

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int) $product_id . ", customer_group_id = " . (int) $recurring['customer_group_id'] . ", `recurring_id` = " . (int) $recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');

		return $product_id;
	}

	public function editProduct($product_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int) $data['quantity'] . "', minimum = '" . (int) $data['minimum'] . "', subtract = '" . (int) $data['subtract'] . "', stock_status_id = '" . (int) $data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int) $data['manufacturer_id'] . "', shipping = '" . (int) $data['shipping'] . "', price = '" . (float) $data['price'] . "', points = '" . (int) $data['points'] . "', weight = '" . (float) $data['weight'] . "', weight_class_id = '" . (int) $data['weight_class_id'] . "', length = '" . (float) $data['length'] . "', width = '" . (float) $data['width'] . "', height = '" . (float) $data['height'] . "', length_class_id = '" . (int) $data['length_class_id'] . "', status = '" . (int) $data['status'] . "', tax_class_id = '" . (int) $data['tax_class_id'] . "', sort_order = '" . (int) $data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int) $product_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int) $product_id . "'");

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int) $product_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_store'])) {
			foreach ($data['product_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "'");

		if (!empty($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $product_id . "', attribute_id = '" . (int) $product_attribute['attribute_id'] . "', language_id = '" . (int) $language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int) $product_option['product_option_id'] . "', product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', required = '" . (int) $product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int) $product_option_value['product_option_value_id'] . "', product_option_id = '" . (int) $product_option_id . "', product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', option_value_id = '" . (int) $product_option_value['option_value_id'] . "', quantity = '" . (int) $product_option_value['quantity'] . "', subtract = '" . (int) $product_option_value['subtract'] . "', price = '" . (float) $product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int) $product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float) $product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int) $product_option['product_option_id'] . "', product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int) $product_option['required'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_discount['customer_group_id'] . "', quantity = '" . (int) $product_discount['quantity'] . "', priority = '" . (int) $product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_special['customer_group_id'] . "', priority = '" . (int) $product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int) $product_image['sort_order'] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int) $product_id . "', download_id = '" . (int) $download_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $product_id . "', category_id = '" . (int) $category_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int) $product_id . "', filter_id = '" . (int) $filter_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int) $product_id . "'");

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "' AND related_id = '" . (int) $related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $product_id . "', related_id = '" . (int) $related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $related_id . "' AND related_id = '" . (int) $product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $related_id . "', related_id = '" . (int) $product_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $value) {
				if ((int) $value['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $customer_group_id . "', points = '" . (int) $value['points'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int) $product_id . "'");

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout_id . "'");
			}
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int) $product_id);

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $product_recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int) $product_id . ", customer_group_id = " . (int) $product_recurring['customer_group_id'] . ", `recurring_id` = " . (int) $product_recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');
	}

	public function copyProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int) $product_id . "'");

		if ($query->num_rows) {
			$data = $query->row;

			$data['sku']	 = '';
			$data['upc']	 = '';
			$data['viewed']	 = '0';
			$data['keyword'] = '';
			$data['status']	 = '0';

			$data['product_attribute']	 = $this->getProductAttributes($product_id);
			$data['product_description'] = $this->getProductDescriptions($product_id);
			$data['product_discount']	 = $this->getProductDiscounts($product_id);
			$data['product_filter']		 = $this->getProductFilters($product_id);
			$data['product_image']		 = $this->getProductImages($product_id);
			$data['product_option']		 = $this->getProductOptions($product_id);
			$data['product_related']	 = $this->getProductRelated($product_id);
			$data['product_reward']		 = $this->getProductRewards($product_id);
			$data['product_special']	 = $this->getProductSpecials($product_id);
			$data['product_category']	 = $this->getProductCategories($product_id);
			$data['product_download']	 = $this->getProductDownloads($product_id);
			$data['product_layout']		 = $this->getProductLayouts($product_id);
			$data['product_store']		 = $this->getProductStores($product_id);
			$data['product_recurrings']	= $this->getRecurrings($product_id);

			$this->addProduct($data);
		}
	}

	public function deleteProduct($product_id) {
		$this->stdelog->write(4, $product_id, "deleteProduct() is called with : \$product_id");
		
		// delete images firstly! - Method check data in DB		
		$this->deleteImagesFilesWithProduct($product_id);
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_recurring WHERE product_id = " . (int) $product_id);
		$this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "coupon_product WHERE product_id = '" . (int) $product_id . "'");

		$this->cache->delete('product');
	}

	public function getProduct($product_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int) $product_id . "' AND pd.language_id = '" . (int) $this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getProductsByCategoryId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id) WHERE pd.language_id = '" . (int) $this->config->get('config_language_id') . "' AND p2c.category_id = '" . (int) $category_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getProductDescriptions($product_id) {
		$h1 = $this->getH1();

		$product_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_description_data[$result['language_id']] = array(
				'name'						 => $result['name'],
				'description'			 => $result['description'],
				$h1								 => isset($result[$h1]) ? $result[$h1] : '',
				'meta_title'			 => $result['meta_title'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'		 => $result['meta_keyword'],
				'tag'							 => $result['tag']
			);
		}

		return $product_description_data;
	}

	public function getProductCategories($product_id) {
		$product_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_category_data[] = $result['category_id'];
		}

		return $product_category_data;
	}

	public function getProductFilters($product_id) {
		$product_filter_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_filter_data[] = $result['filter_id'];
		}

		return $product_filter_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_data = array();

		$product_attribute_query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' GROUP BY attribute_id");

		foreach ($product_attribute_query->rows as $product_attribute) {
			$product_attribute_description_data = array();

			$product_attribute_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "'");

			foreach ($product_attribute_description_query->rows as $product_attribute_description) {
				$product_attribute_description_data[$product_attribute_description['language_id']] = array('text' => $product_attribute_description['text']);
			}

			$product_attribute_data[] = array(
				'attribute_id'					 => $product_attribute['attribute_id'],
				'product_attribute_description'	 => $product_attribute_description_data
			);
		}

		return $product_attribute_data;
	}

	public function getProductOptions($product_id) {
		$product_option_data = array();

		$product_option_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_option` po LEFT JOIN `" . DB_PREFIX . "option` o ON (po.option_id = o.option_id) LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE po.product_id = '" . (int) $product_id . "' AND od.language_id = '" . (int) $this->config->get('config_language_id') . "'");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();

			$product_option_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON(pov.option_value_id = ov.option_value_id) WHERE pov.product_option_id = '" . (int) $product_option['product_option_id'] . "' ORDER BY ov.sort_order ASC");

			foreach ($product_option_value_query->rows as $product_option_value) {
				$product_option_value_data[] = array(
					'product_option_value_id'	 => $product_option_value['product_option_value_id'],
					'option_value_id'			 => $product_option_value['option_value_id'],
					'quantity'					 => $product_option_value['quantity'],
					'subtract'					 => $product_option_value['subtract'],
					'price'						 => $product_option_value['price'],
					'price_prefix'				 => $product_option_value['price_prefix'],
					'points'					 => $product_option_value['points'],
					'points_prefix'				 => $product_option_value['points_prefix'],
					'weight'					 => $product_option_value['weight'],
					'weight_prefix'				 => $product_option_value['weight_prefix']
				);
			}

			$product_option_data[] = array(
				'product_option_id'		 => $product_option['product_option_id'],
				'product_option_value'	 => $product_option_value_data,
				'option_id'				 => $product_option['option_id'],
				'name'					 => $product_option['name'],
				'type'					 => $product_option['type'],
				'value'					 => $product_option['value'],
				'required'				 => $product_option['required']
			);
		}

		return $product_option_data;
	}

	public function getProductOptionValue($product_id, $product_option_value_id) {
		$query = $this->db->query("SELECT pov.option_value_id, ovd.name, pov.quantity, pov.subtract, pov.price, pov.price_prefix, pov.points, pov.points_prefix, pov.weight, pov.weight_prefix FROM " . DB_PREFIX . "product_option_value pov LEFT JOIN " . DB_PREFIX . "option_value ov ON (pov.option_value_id = ov.option_value_id) LEFT JOIN " . DB_PREFIX . "option_value_description ovd ON (ov.option_value_id = ovd.option_value_id) WHERE pov.product_id = '" . (int) $product_id . "' AND pov.product_option_value_id = '" . (int) $product_option_value_id . "' AND ovd.language_id = '" . (int) $this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductDiscounts($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "' ORDER BY quantity, priority, price");

		return $query->rows;
	}

	public function getProductSpecials($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "' ORDER BY priority, price");

		return $query->rows;
	}

	public function getProductRewards($product_id) {
		$product_reward_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_reward_data[$result['customer_group_id']] = array('points' => $result['points']);
		}

		return $product_reward_data;
	}

	public function getProductDownloads($product_id) {
		$product_download_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_download_data[] = $result['download_id'];
		}

		return $product_download_data;
	}

	public function getProductStores($product_id) {
		$product_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_store_data[] = $result['store_id'];
		}

		return $product_store_data;
	}

	public function getProductLayouts($product_id) {
		$product_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $product_layout_data;
	}

	public function getProductRelated($product_id) {
		$product_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "'");

		foreach ($query->rows as $result) {
			$product_related_data[] = $result['related_id'];
		}

		return $product_related_data;
	}

	public function getRecurrings($product_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = '" . (int) $product_id . "'");

		return $query->rows;
	}

	public function getTotalProductsByTaxClassId($tax_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE tax_class_id = '" . (int) $tax_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByStockStatusId($stock_status_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE stock_status_id = '" . (int) $stock_status_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByWeightClassId($weight_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE weight_class_id = '" . (int) $weight_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByLengthClassId($length_class_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE length_class_id = '" . (int) $length_class_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByDownloadId($download_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_to_download WHERE download_id = '" . (int) $download_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByManufacturerId($manufacturer_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product WHERE manufacturer_id = '" . (int) $manufacturer_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByAttributeId($attribute_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = '" . (int) $attribute_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByOptionId($option_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_option WHERE option_id = '" . (int) $option_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByProfileId($recurring_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_recurring WHERE recurring_id = '" . (int) $recurring_id . "'");

		return $query->row['total'];
	}

	public function getTotalProductsByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "product_to_layout WHERE layout_id = '" . (int) $layout_id . "'");

		return $query->row['total'];
	}

	public function getManufacturers($data = array()) {
		$this->stdelog->write(4, 'getManufacturers() :: is called');
		
		$prefix = '';

		$sql = "SELECT * FROM " . DB_PREFIX . "manufacturer";
		
		if (!empty($data['filter_name'])) {
			$sql .= " WHERE name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'name',
			'sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY " . $prefix . "name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$this->stdelog->write(4, $sql, 'getManufacturers() :: $sql');

		$query = $this->db->query($sql);

		return $query->rows;
	}




	/* Handy Prodcut Manager Methods
	--------------------------------------------------------------------------- */
	//////////////////////////////////////////////////////////////////////////////

	public function getValidFormats() {
		return array("jpg", "png", "gif", "jpeg");
	}

	public function addProductImageMain($data) {
		// Предполагается, что товар уже существует !
		// То есть, при добавлении нового ряда в списке, под товар уже создается запись!
		// Если же будет поодниночное добавление товара, то либо это будет другой метод модели, либо также будет сначала резервироваться ID товара, а потом к нему вешаться данные
		// todo...
		// Запросить, какая фотка была до этого
		//$sql = "UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$data['product_id'] . "'";
		//$this->db->query($sql);
		// todo...
		// Если заменяется главная фотка, то предыдущую необходимо бы удалить
		// Но проверить перед этим, не прикрплена ли она к другим товарам в качестве главной
		// или в качестве дополнительной
	}

	public function addProductImageAdditional($data) {
		$sql = "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $data['product_id'] . "', image = '" . $this->db->escape($data['image']) . "', sort_order = '" . (int) $data['image_additional_n'] . "' ";

		$this->stdelog->write(4, $sql, "addProductImageAdditional() \$sql");

		$this->db->query($sql);
	}

	public function editProductImageMain($data) {
		$sql = "UPDATE " . DB_PREFIX . "product SET image='" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int) $data['product_id'] . "'";

		$this->stdelog->write(4, $sql, "editProductImageMain() \$sql UPDATE MAIN");

		$this->db->query($sql);
	}

	public function editProductImageAdditional($data) {
		$sql = "UPDATE " . DB_PREFIX . "product_image SET image='" . $this->db->escape($data['image_new']) . "' WHERE product_id = '" . (int) $data['product_id'] . "' AND image='" . $this->db->escape($data['image_old']) . "' ";

		$this->stdelog->write(4, $sql, "editProductImageAdditional() \$sql UPDATE ADDITIONAL");

		$this->db->query($sql);
	}

	public function editProductImageMainFromFirstItem($data) {
		$sql = "UPDATE " . DB_PREFIX . "product SET image='" . $this->db->escape($data['image_new']) . "' WHERE product_id = '" . (int) $data['product_id'] . "'";

		$this->stdelog->write(4, $sql, "editProductImageMainFromFirstItem() :: UPDATE MAIN");

		$this->db->query($sql);

		$sql = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $data['product_id'] . "' AND image = '" . $this->db->escape($data['image_new']) . "'";

		$this->stdelog->write(4, $sql, "editProductImageMainFromFirstItem() :: DELETE NEW MAIN FROM ADDITITONAL WAS");

		$this->db->query($sql);
	}

	public function editProductImageMainAfterSorting($data) {
		$sql = "UPDATE " . DB_PREFIX . "product SET image='" . $this->db->escape($data['image_new']) . "' WHERE product_id = '" . (int) $data['product_id'] . "'";

		$this->stdelog->write(4, $sql, "editProductImageMainAfterSorting() \$sql UPDATE MAIN");

		$this->db->query($sql);

		$sql = "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $data['product_id'] . "', image = '" . $this->db->escape($data['image_old']) . "', sort_order = '0' ";

		$this->stdelog->write(4, $sql, "editProductImageMain() 2 \$sql OLD MAIN INSERT AS ADDITIONAL");

		$this->db->query($sql);

		$sql = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $data['product_id'] . "' AND image = '" . $this->db->escape($data['image_new']) . "'";

		$this->stdelog->write(4, $sql, "editProductImageMain() 3 \$sql DELETE NEW MAIN FROM ADDITITONAL");

		$this->db->query($sql);
	}

	public function editProductImageSorting($data) {
		foreach ($data['images'] as $index => $image) {
			$sql = "UPDATE " . DB_PREFIX . "product_image SET sort_order = '" . (int) $index . "' WHERE product_id = '" . (int) $data['product_id'] . "' AND image='" . $this->db->escape($image) . "'";

			$this->stdelog->write(4, $sql, "editProductImageSorting() \$sql");

			$this->db->query($sql);
		}
	}

	public function deleteProductImageMain($data) {
		$this->stdelog->write(4, 'deleteProductImageMain() is called');
		$this->stdelog->write(4, $data, 'deleteProductImageMain() : $data');
		
		$sql = "UPDATE " . DB_PREFIX . "product SET image = '' WHERE product_id = '" . (int) $data['product_id'] . "'";

		$this->stdelog->write(4, $sql, "deleteProductImageMain() \$sql");

		$this->db->query($sql);
		
		if (!$this->isImageFileRequiredToAnotherProduct($data['image'], $data['product_id'])) {
			$this->deleteImageFile($data['image']);
		}
	}

	public function deleteProductImageAdditional($data) {
		$this->stdelog->write(4, 'deleteProductImageAdditional() is called');
		$this->stdelog->write(4, $data, 'deleteProductImageAdditional() : $data');
		
		$sql = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $data['product_id'] . "' AND image = '" . $this->db->escape($data['image']) . "'";

		$this->stdelog->write(4, $sql, 'deleteProductImageAdditional() $sql');

		$this->db->query($sql);
		
		if (!$this->isImageFileRequiredToAnotherProduct($data['image'], $data['product_id'])) {
			$this->deleteImageFile($data['image']);
		}
	}

	/*
	 * Method check data in DB
	 */
	public function deleteImagesFilesWithProduct($product_id) {
		$this->stdelog->write(4, $product_id, 'deleteImagesFilesWithProduct() $product_id');
		$this->deleteMainImageFileWithProduct($product_id);
		$this->deleteAdittionalImagesFilesWithProduct($product_id);
	  return false;
	}	
	
	public function deleteMainImageFileWithProduct($product_id) {
		$this->stdelog->write(4, $product_id, 'deleteMainImageFileWithProduct() $product_id');
		
		$sql = "SELECT `image` FROM `".DB_PREFIX."product` WHERE `product_id`='".(int)$product_id."';";
		
		$this->stdelog->write(4, $sql, 'deleteMainImageFileWithProduct() : $sql');
		
		$query = $this->db->query($sql);
		
		$this->stdelog->write(4, $query, 'deleteMainImageFileWithProduct() : $query');
		
		if ($query->row) {	
			if (!$this->isImageFileRequiredToAnotherProduct($query->row['image'], $product_id)) {
				return $this->deleteImageFile($query->row['image']);
			}			
		}		
		return false;
	}
	
	public function deleteAdittionalImagesFilesWithProduct($product_id) {
		$this->stdelog->write(4, $product_id, 'deleteAdittionalImagesFilesWithProduct() $product_id');
		
		$sql = "SELECT `image` FROM `".DB_PREFIX."product_image` WHERE `product_id`=".(int)$product_id.";";
		
		$this->stdelog->write(4, $sql, 'deleteAdittionalImagesFilesWithProduct() : $sql');
		
		$query = $this->db->query($sql);
		
		$this->stdelog->write(4, $query, 'deleteAdittionalImagesFilesWithProduct() : $query');
		
		if ($query->rows) {
			foreach($query->rows as $item){				
				if (!$this->isImageFileRequiredToAnotherProduct($item['image'], $product_id)) {	
					$this->deleteImageFile($item['image']);
				}
			}
		}
		return false;
	}
	
	public function isImageFileRequiredToAnotherProduct($image, $product_id) {
		$this->stdelog->write(4, 'isImageFileRequiredToAnotherProduct() is called');
		$this->stdelog->write(4, $product_id, 'isImageFileRequiredToAnotherProduct() : $product_id');
		$this->stdelog->write(4, $image, 'isImageFileRequiredToAnotherProduct() : $image');
		
		// Не прикреплена ли эта фотографию к другому товару
		$sql = "SELECT `image` FROM `".DB_PREFIX."product_image` WHERE `image`= '" . $this->db->escape($image) . "' AND `product_id` != '".(int)$product_id."';";
		$this->stdelog->write(4, $sql, 'isImageFileRequiredToAnotherProduct() : $sql 1');
		
		$query = $this->db->query($sql);
		$this->stdelog->write(4, $query, 'isImageFileRequiredToAnotherProduct() : $query 1');
		
		if($query->num_rows > 0) {
			// Прикреплена
			$this->stdelog->write(4, 'isImageFileRequiredToAnotherProduct() : return true');
			return true;
		} else {
			// Не является ли эта фотография главной для другого товара?
			$sql2 = "SELECT `image` FROM `".DB_PREFIX."product` WHERE `image`= '" . $this->db->escape($image). "' AND `product_id` != '".(int)$product_id."' ;";
			$this->stdelog->write(4, $sql2, 'isImageFileRequiredToAnotherProduct() : $sql2');
			
			$query2 = $this->db->query($sql2);
			$this->stdelog->write(4, $query2, 'isImageFileRequiredToAnotherProduct() : $query2');
			
			if($query2->num_rows > 0) {
				// Является
				$this->stdelog->write(4, 'isImageFileRequiredToAnotherProduct() : return true');
				return true;
			} else {
				$this->stdelog->write(4, 'isImageFileRequiredToAnotherProduct() : return false');
				return false;				
			}
		}
		
		$this->stdelog->write(4, 'isImageFileRequiredToAnotherProduct() : return false');
		return false;
	}
	
	public function deleteImageFile($image) {
		$this->stdelog->write(4, $image, 'deleteImageFile() $image');
		
		$filepath = DIR_IMAGE . $image;

		if (is_file($filepath)){		
			unlink($filepath);
			return true;
		} else {
			return false;
		}
	}	
	

	public function editProductMainCategory($data) {
		$sql = "UPDATE " . DB_PREFIX . "product_to_category SET main_category = '0' WHERE product_id = '" . (int) $data['product_id'] . "'";

		$query = $this->db->query($sql);

		if (0 != $data['main_category_id']) {
			$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $data['product_id'] . "', category_id = '" . (int) $data['main_category_id'] . "'";

			$query = $this->db->query($sql);

			$sql = "UPDATE " . DB_PREFIX . "product_to_category SET main_category = '1' WHERE product_id = '" . (int) $data['product_id'] . "' AND category_id = '" . (int) $data['main_category_id'] . "'";

			$query = $this->db->query($sql);

			// tmp
			$this->cache->delete('product.seopath');
			$this->cache->delete('seo_pro');
		}
	}

	public function editProductCategories($data) {
		if ('add' == $data['action']) {
			$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $data['product_id'] . "', category_id = '" . (int) $data['value'] . "'";
		} else {
			$sql = "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $data['product_id'] . "' AND category_id = '" . (int) $data['value'] . "'";
		}

		$res = $this->db->query($sql);
	}

	/*
	 * A!
	 * In this method we update VARCHAR fields only!!
	 * If you will try to update fields with other types it may affects errors
	 */
  public function editProductIdentity($data) {
		$this->stdelog->write(4, 'editProductIdentity() is called');

		// To unset all SKU or MODEL from mass edit...
		if ('empty' == $data['value']) {
			$data['value'] = '';
		}

		$sql = "UPDATE " . DB_PREFIX . "product SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_id = '" . (int) $data['product_id'] . "'";

		$this->stdelog->write(4, $sql, 'editProductIdentity() $sql');

		$res = $this->db->query($sql);

		$this->stdelog->write(4, $res, 'editProductIdentity() $res');
	}

	// A! Points - is simple identity field!
  public function editProductReward($data) {
		$this->stdelog->write(4, 'editProductReward() is called');

		// $sql = "INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $data['product_id'] . "', customer_group_id = '" . (int) $data['customer_group_id'] . "', points = '" . (int)$data['value'] . "' ON DUPLICATE KEY UPDATE points = '" . (int)$data['value'] . "'";
		// ON DUPLICATE KEY UPDATE work only for PRIMARY KEY...

		$sql = "SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int) $data['product_id'] . "'";

		$this->stdelog->write(4, $sql, 'editProductReward() $sql SELECT');

		$res = $this->db->query($sql);

		if ($res->num_rows > 0) {
			$sql2 = "UPDATE " . DB_PREFIX . "product_reward SET customer_group_id = '" . (int) $data['customer_group_id'] . "', points = '" . (int)$data['value'] . "' WHERE product_id = '" . (int) $data['product_id'] . "'";

			$this->stdelog->write(4, $sql2, 'editProductReward() $sql2 UPDATE');

		} else {
			$sql2 = "INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $data['product_id'] . "', customer_group_id = '" . (int) $data['customer_group_id'] . "', points = '" . (int)$data['value'] . "'";

			$this->stdelog->write(4, $sql2, 'editProductReward() $sql2 INSERT');
		}

		$res2 = $this->db->query($sql2);

		$this->stdelog->write(4, $res2, 'editProductReward() $res2');
	}

	public function addDiscount($data) {
		$this->stdelog->write(4, 'addDiscount() is called');

		$sql = "INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $data['product_id'] . "', customer_group_id = '" . (int) $data['customer_group_id'] . "', quantity = '" . 0 . "', priority = '" . 0 . "', price = '" . (float) $data['price'] . "'";

		$this->stdelog->write(4, $sql, 'addDiscount() $sql');

		$res = $this->db->query($sql);

		if ($res) {
			return $this->db->getLastId();
		}

		$this->stdelog->write(4, $res, 'addDiscount() $res');

		return false;
	}

	public function editDiscount($data) {
		$this->stdelog->write(4, 'editDiscount() is called');

		$sql = "UPDATE " . DB_PREFIX . "product_discount SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_discount_id = '" . (int) $data['product_discount_id'] . "'";

		$this->stdelog->write(4, $sql, 'editDiscount() $sql');

		$res = $this->db->query($sql);
	}

	public function deleteDiscount($data) {
		$this->stdelog->write(4, 'deleteDiscount() is called');

		$sql = "DELETE FROM " . DB_PREFIX . "product_discount WHERE product_discount_id = '" . (int) $data['product_discount_id'] . "'";

		$this->stdelog->write(4, $sql, 'deleteDiscount() $sql');

		$res = $this->db->query($sql);
	}


	public function addSpecial($data) {
		$this->stdelog->write(4, 'addSpecial() is called');

		$sql = "INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $data['product_id'] . "', customer_group_id = '" . (int) $data['customer_group_id'] . "', priority = '" . 0 . "', price = '" . (float) $data['price'] . "'";

		$this->stdelog->write(4, $sql, 'addSpecial() $sql');

		$res = $this->db->query($sql);

		if ($res) {
			return $this->db->getLastId();
		}

		$this->stdelog->write(4, $res, 'addSpecial() $res');

		return false;
	}

	public function editSpecial($data) {
		$this->stdelog->write(4, 'editSpecial() is called');

		$sql = "UPDATE " . DB_PREFIX . "product_special SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_special_id = '" . (int) $data['product_special_id'] . "'";

		$this->stdelog->write(4, $sql, 'editSpecial() $sql');

		$res = $this->db->query($sql);
	}

	public function deleteSpecial($data) {
		$this->stdelog->write(4, 'deleteSpecial() is called');

		$sql = "DELETE FROM " . DB_PREFIX . "product_special WHERE product_special_id = '" . (int) $data['product_special_id'] . "'";

		$this->stdelog->write(4, $sql, 'deleteSpecial() $sql');

		$res = $this->db->query($sql);
	}

  public function addProductToStore($data) {
		$this->stdelog->write(4, 'addProductToStore() is called');

		$sql = "INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $data['product_id'] . "', store_id = '" . (int) $data['store_id'] . "'";

		$this->stdelog->write(4, $sql, 'addProductToStore() $sql');

		$this->db->query($sql);
	}

	public function deleteProductFromStore($data) {
		$this->stdelog->write(4, 'deleteProductFromStore() is called');

		$sql = "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $data['product_id'] . "' AND store_id = '" . (int) $data['store_id'] . "'";

		$this->stdelog->write(4, $sql, 'deleteProductFromStore() $sql');

		$this->db->query($sql);
	}

	public function addProductRelated($data) {
		$this->stdelog->write(4, 'addProductRelated() is called');
		
		$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $data['product_id'] . "', related_id = '" . (int) $data['related_id'] . "'";

		$this->stdelog->write(4, $sql, 'addProductRelated() $sql');

		return $this->db->query($sql);
	}
	
	public function deleteProductRelated($data) {
		$this->stdelog->write(4, 'deleteProductRelated() is called');
		
		$sql = "DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $data['product_id'] . "' AND related_id = '" . (int) $data['related_id'] . "'";
		
		$this->stdelog->write(4, $sql, 'deleteProductRelated() $sql');

		return $this->db->query($sql);
	}

	public function addProductFilter($data) {
		$this->stdelog->write(4, 'addProductFilter() is called');
		
		$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int) $data['product_id'] . "', filter_id = '" . (int) $data['filter_id'] . "'";

		$this->stdelog->write(4, $sql, 'addProductFilter() $sql');

		return $this->db->query($sql);
	}
	
	public function deleteProductFilter($data) {
		$this->stdelog->write(4, 'deleteProductFilter() is called');
		
		$sql = "DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int) $data['product_id'] . "' AND filter_id = '" . (int) $data['filter_id'] . "'";
		
		$this->stdelog->write(4, $sql, 'deleteProductFilter() $sql');

		return $this->db->query($sql);
	}

	public function editProductDescription($data) {
		//$sql = "UPDATE " . DB_PREFIX . "product_description SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_id = '" . (int) $data['product_id'] . "' AND language_id = '" . (int) $data['language_id'] . "'";
		$sql = "INSERT INTO `" . DB_PREFIX . "product_description` SET"
			. " `product_id` = '" . (int)$data['product_id'] . "',"
			. " `language_id` = '" . (int)$data['language_id'] . "',"
			. " `" . $data['field'] . "` = '" . $this->db->escape($data['value']) . "'"
			. " ON DUPLICATE KEY UPDATE"
			. " `" . $data['field'] . "` = '" . $this->db->escape($data['value']) . "'";

		$this->stdelog->write(4, $sql, 'editProductDescription() $sql');
		
		$res = $this->db->query($sql);
	}


	public function addNewAttribute($data) {
		$sql = "INSERT INTO " . DB_PREFIX . "attribute SET attribute_group_id = '" . (int) $data['attribute_group_id'] . "'";

		$this->db->query($sql);

		$attribute_id = $this->db->getLastId();

		foreach ($data['attribute_description'] as $language_id => $value) {
			$sql = "INSERT INTO " . DB_PREFIX . "attribute_description SET attribute_id = '" . (int) $attribute_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value) . "'";
			$this->db->query($sql);
		}

		return $attribute_id;
	}

	public function addProductAttribute($data) {
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($data['languages'] as $language) {
			$sql = "INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $data['product_id'] . "', attribute_id = '" . (int) $data['attribute_id'] . "', language_id = '" . (int) $language['language_id'] . "' ";

			$res = $this->db->query($sql);
		}
	}

	public function deleteProductAttribute($data) {
		$sql = "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $data['product_id'] . "' AND attribute_id = '" . (int) $data['attribute_id'] . "'";

		$res = $this->db->query($sql);
	}

	public function editProductAttributeValue($data) {
		$this->stdelog->write(4, 'editProductAttributeValue() is called');
    $sql = "INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$data['product_id'] . "',  attribute_id = '" . (int)$data['attribute_id'] . "', language_id = '" . (int)$data['language_id'] . "', " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' ON DUPLICATE KEY UPDATE " . $data['field'] . " = '" . $this->db->escape($data['value']) . "'";

    $this->stdelog->write(4, $sql, 'editProductAttributeValue() $sql');

    $res = $this->db->query($sql);

    $this->stdelog->write(4, $res, 'editProductAttributeValue() $res');
  }

	public function getAttributes($data = array()) {
		$sql = "SELECT "
			. "a.attribute_id, "
			. "a.attribute_group_id, "
			. "ad.language_id, "
			. "ad.name, "
			//. "pa.product_id, "
			. "(SELECT agd.name FROM " . DB_PREFIX . "attribute_group_description agd WHERE agd.attribute_group_id = a.attribute_group_id AND agd.language_id = '" . (int) $this->config->get('config_language_id') . "') AS attribute_group "
			. "FROM " . DB_PREFIX . "attribute a "
			//. "LEFT JOIN " . DB_PREFIX . "product_attribute pa ON (pa.attribute_id = a.attribute_id) "
			. "LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (ad.attribute_id = a.attribute_id) "
			. "WHERE ad.language_id = '" . (int) $this->config->get('config_language_id') . "' "
			. "AND a.attribute_id NOT IN (SELECT DISTINCT pa.attribute_id FROM " . DB_PREFIX . "product_attribute pa WHERE pa.language_id = '" . (int) $this->config->get('config_language_id') . "' AND pa.product_id = '" . (int) $data['product_id'] . "')";

		if (!empty($data['filter_name'])) {
			$sql .= " AND ad.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_attribute_group_id'])) {
			$sql .= " AND a.attribute_group_id = '" . $this->db->escape($data['filter_attribute_group_id']) . "'";
		}

		$sort_data = array(
			'ad.name',
			'attribute_group',
			'a.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY attribute_group, ad.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function collationInfo() {
		$outpup = false;
		
		$sql = "SHOW TABLE STATUS LIKE '" . DB_PREFIX . "product_attribute' ";

		$query = $this->db->query($sql);		
		
		if (false !== strpos($query->row['Collation'], 'utf8mb4')) {
			$outpup = 'utf8mb4';
		} elseif (false !== strpos($query->row['Collation'], 'utf8')) {
			$outpup = 'utf8';
		} else {
			$outpup = false;
		}

		$this->stdelog->write(3, $outpup, 'collationInfo() :: $outpup');

		return $outpup;
	}

	public function getAllAttributeValues() {
		$this->stdelog->write(4, 'model getAllAttributeValues() is called');
		
		$collate = ($this->collation) ? 'COLLATE ' . $this->collation . '_bin AS `text`' : '';

		$sql = "SELECT DISTINCT `text` $collate, `attribute_id`, `language_id` FROM `" . DB_PREFIX . "product_attribute` WHERE `text` != '' ORDER BY `attribute_id` ASC";

		$this->stdelog->write(4, $sql, 'getAllAttributeValues() :: $sql');

		$query = $this->db->query($sql);

		$this->stdelog->write(4, $query, 'getAllAttributeValues() :: $res');

		return $query->rows;
	}

	public function getAttributeValues($attribute_id, $language_id) {
		$this->stdelog->write(4, 'model getAttributeValues() is called');
		
		$collate = ($this->collation) ? 'COLLATE ' . $this->collation . '_bin AS `text`' : '';

		// from Attribute select 2.0 by alex2009 [OCMOD] - modified
		$sql = "SELECT DISTINCT `text` $collate FROM `" . DB_PREFIX . "product_attribute` WHERE `attribute_id` = '" . (int) $attribute_id . "' AND `language_id` = '" . (int) $language_id . "' AND `text` != ''";

		$this->stdelog->write(4, $sql, 'getAttributeValues() :: $sql');

		$query = $this->db->query($sql);

		$this->stdelog->write(4, $query, 'getAttributeValues() :: $res');

		return $query->rows;
	}

  public function getAllOptionValues() {
    $this->stdelog->write(4, 'model getAllOptionValues() is called');

		$sql = "SELECT * FROM " . DB_PREFIX . "option_value_description WHERE language_id = '" . (int) $this->config->get('config_language_id') . "'";

    $this->stdelog->write(4, $sql, 'getAllOptionValues() $sql');

		$res = $this->db->query($sql);

    $this->stdelog->write(4, $res, 'getProductOption() $res');

		return $res->rows;
	}

  public function getOptionsList($product_id) {
    $this->stdelog->write(4, 'model getOptionsList() is called');

		$sql = "SELECT *, name FROM " . DB_PREFIX . "option o"
			. " LEFT JOIN " . DB_PREFIX . "option_description od ON od.option_id = o.option_id"
			. " WHERE o.option_id NOT IN (SELECT option_id FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "')"
			. " AND od.language_id = '" . $this->config->get('config_language_id') . "'";

    $this->stdelog->write(4, $sql, 'getOptionsList() get required option $sql');

		$res = $this->db->query($sql);

    $this->stdelog->write(4, $res, 'getOptionsList() get required option $res');

		if ($res->num_rows) {
			return $res->rows;
		}

		return array();
	}

	public function addProductOption($data) {
		// return product_option_id
    $this->stdelog->write(4, 'addProductOption() is called');

		$sql = "INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $data['option_id'] . "', value = '', required = '1'";

    $this->stdelog->write(4, $sql, 'addProductOption() $sql');

    $res = $this->db->query($sql);

		$this->stdelog->write(4, $res, 'addProductOption() $res');

		return $this->db->getLastId();
	}

	public function editProductOption($data) {
		$this->stdelog->write(4, 'editProductOption() is called');

		$sql = "UPDATE " . DB_PREFIX . "product_option SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_id = '" . (int) $data['product_id'] . "' AND option_id = '" . (int) $data['option_id'] . "'";

		$this->stdelog->write(4, $sql, 'editProductOption() $sql');

		$res = $this->db->query($sql);

		$this->stdelog->write(4, $res, 'editProductOption() $res');
	}

  public function addProductOptionValue($data) {
    $this->stdelog->write(4, 'addProductOptionValue() is called');

		$sql = "INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . $this->db->escape($data['product_option_id']) . "', product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $data['option_id'] . "', option_value_id = '" . (int) $data['option_value_id'] . "', quantity = '0', subtract = '1', price = '0', price_prefix = '+', points = '0', points_prefix = '+', weight = '0', weight_prefix = '+' ";

    $this->stdelog->write(4, $sql, 'addProductOptionValue() $sql');

    $res = $this->db->query($sql);

		$this->stdelog->write(4, $res, 'addProductOptionValue() $res');

		return $this->db->getLastId();
	}

  public function editProductOptionValue($data) {
    $this->stdelog->write(4, 'editProductOptionValue() is called');

    //$sql = "UPDATE " . DB_PREFIX . "product_option_value SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_id = '" . (int) $data['product_id'] . "' AND option_id = '" . (int) $data['option_id'] . "' AND product_option_id = '" . (int) $data['product_option_id'] . "'";
    $sql = "UPDATE " . DB_PREFIX . "product_option_value SET " . $data['field'] . " = '" . $this->db->escape($data['value']) . "' WHERE product_option_value_id = '" . (int) $data['product_option_value_id'] . "'";

    $this->stdelog->write(4, $sql, 'editProductOptionValue() $sql');

    $res = $this->db->query($sql);

		$this->stdelog->write(4, $res, 'editProductOptionValue() $res');
	}

  public function deleteOptionFromProduct($data) {
    // Удаляет данные из таблиц product_option и product_option_value
    $this->stdelog->write(4, 'deleteOptionFromProduct() is called');

    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$data['product_id'] . "' AND option_id = '" . (int)$data['option_id'] . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$data['product_id'] . "' AND option_id = '" . (int)$data['option_id'] . "'");
  }

  public function deleteProductOptionValue($data) {
    // Удаляет данные из таблицы product_option_value
    $this->stdelog->write(4, 'deleteProductOptionValue() is called');

    $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$data['product_id'] . "' AND product_option_value_id = '" . (int)$data['product_option_value_id'] . "'");
  }

	public function getFilters($data) {
		$sql = "SELECT *"
			. ", fgd.name AS `group`"
			. " FROM " . DB_PREFIX . "filter f"
			. " LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fgd.filter_group_id = f.filter_group_id)"
			. " LEFT JOIN " . DB_PREFIX . "filter_description fd ON (fd.filter_id = f.filter_id)"
			. " WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'"
			. " AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND (fd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%' OR fgd.name LIKE '%" . $this->db->escape($data['filter_name']) . "%')";
		}

		$sql .= " ORDER BY f.sort_order ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	// create & clone new product
	public function addNewProduct($data_input) {
		$this->stdelog->write(4, 'addNewProduct() is called');
		$this->stdelog->write(4, $data_input, 'addNewProduct() $data_input');

		$product_id = $data_input['clone_product_id'];

		if ('clone_product' == $data_input['flag'] || 'clone_product_with_image' == $data_input['flag'] || 'create_new_product_with_copy_minimum' == $data_input['flag']) {
			$this->stdelog->write(4, 'addNewProduct() дошли до составления sql-запроса');

			$sql = "SELECT DISTINCT * FROM " . DB_PREFIX . "product p WHERE p.product_id = '" . (int) $product_id . "'";

			$this->stdelog->write(4, $sql, 'addNewProduct() \$sql');

			$query = $this->db->query($sql);

			$this->stdelog->write(4, $query, 'addNewProduct() \$query');

			if ($query->num_rows) {
				$data			 = $query->row;
				//$data['sku']	 = '';
				$data['upc']	 = '';
				$data['viewed']	 = '0';
				$data['keyword'] = '';
				$data['status']	 = '0';

				if ('clone_product' == $data_input['flag'] || 'create_new_product_with_copy_minimum' == $data_input['flag']) {
					$data['image']			 = '';
					$data['product_image']	 = array();
				} else {
					// clone_product_with_image
					$data['product_image'] = $this->getProductImages($product_id);
				}

				$data['product_description'] = $this->getProductDescriptions($product_id);
				$data['product_store']			 = $this->getProductStores($product_id);
				$data['product_attribute']	 = $this->getProductAttributes($product_id);
				$data['product_option']			 = $this->getProductOptions($product_id);
				$data['product_discount']		 = $this->getProductDiscounts($product_id);
				$data['product_special']		 = $this->getProductSpecials($product_id);
				//$data['product_image'] = $this->getProductImages($product_id);
				$data['product_download']		 = $this->getProductDownloads($product_id);
				$data['product_category']		 = $this->getProductCategories($product_id);

				$this->load->model('catalog/product');

				//if (true === method_exists($this->model_catalog_product, 'getProductMainCategoryId')) {
				if (is_callable([new ModelCatalogProduct($this->registry), 'getProductMainCategoryId'])) {
					$data['product_main_category'] = $this->model_catalog_product->getProductMainCategoryId($product_id);
				}
				
				$data['product_filter']				 = $this->getProductFilters($product_id);
				$data['product_related']			 = $this->getProductRelated($product_id);
				$data['product_reward']				 = $this->getProductRewards($product_id);
				$data['product_layout']				 = $this->getProductLayouts($product_id);
				$data['product_recurrings']		 = $this->getRecurrings($product_id); // 'recurring' in addProduct() !!
			} else {
				$data = array();
			}
		} else {
			$this->stdelog->write(4, 'addNewProduct() условие 1 не соблюдено');
		}

		if ('create_new_product' == $data_input['flag']) {
			// Создать новый товар вообще без копирования данных
			// Рассчитано на то, что пользователь осознанно добавил более 1 товара пустыми
			// По крайней мере, если он хотел добавить их пустыми, а мы ему впишем что-то, то ему надо будет все переопределять
			// намного дольше, чем при 1 добавленном товаре
			// Вернуть ид товара, только все остальные данные успешно склонированы

			$data = array();

			//$data['image'] = '';
			$data['model']			 = '';
			$data['sku']			 = '';
			$data['upc']			 = '';
			$data['ean']			 = '';
			$data['jan']			 = '';
			$data['isbn']			 = '';
			$data['mpn']			 = '';
			$data['location']		 = '';
			$data['quantity']		 = 0; //Q?
			$data['minimum']		 = 1;
			$data['subtract']		 = 1; //Q?
			$data['stock_status_id'] = 1; //Q? - settings??
			$data['date_available']	 = ''; //Q? - что означает?
			$data['manufacturer_id'] = 0;
			$data['shipping']		 = 1; //Q? - что означает?
			$data['price']			 = 0;
			$data['points']			 = 0;
			$data['weight']			 = 0.0;
			$data['weight_class_id'] = $this->config->get('config_weight_class_id');
			$data['length']			 = 0.0;
			$data['width']			 = 0.0;
			$data['height']			 = 0.0;
			$data['length_class_id'] = $this->config->get('config_length_class_id');
			$data['status']			 = '0';
			$data['tax_class_id']	 = '0'; //Q? - settings??
			$data['sort_order']		 = 0;

			$data['product_description'] = array(); // must be

			foreach ($data_input['languages'] as $lanugage) {
				$data['product_description'][$lanugage['language_id']]['name']				 = '';
				$data['product_description'][$lanugage['language_id']]['description']		 = '';
				$data['product_description'][$lanugage['language_id']]['tag']				 = '';
				$data['product_description'][$lanugage['language_id']]['meta_title']		 = '';
				$data['product_description'][$lanugage['language_id']]['meta_description']	 = '';
				$data['product_description'][$lanugage['language_id']]['meta_keyword']		 = '';
			}

			$data['keyword'] = ''; // must be
			// Следующие данные могут отстуствовать - проверяются на isset
			//
      $data['product_store'] = array(0);
			//$data['product_attribute'] = array();
			//$data['product_option'] = array();
			//$data['product_discount'] = array();
			//$data['product_special'] = $this->getProductSpecials($product_id);
			//$data['product_image'] = array();
			//$data['product_download'] = $this->getProductDownloads($product_id);
			//$data['product_category'] = $this->getProductCategories($product_id);
			//$data['product_filter'] = array();
			//$data['product_related'] = array();
			//$data['product_reward'] = array();
			//$data['product_layout'] = $this->getProductLayouts($product_id);
			//$data['product_recurrings'] = $this->getRecurrings($product_id); // 'recurring' in addProduct() !!
		} else {
			$this->stdelog->write(4, 'addNewProduct() условие 2 не соблюдено');
		}

		$this->stdelog->write(4, $data, 'addNewProduct() $data for addProduct()');

		if (count($data) > 0) {
			return $this->addProduct($data);
		}

		return false;
	}


	/*
	 * It is necessary to link atribute_values with atributes_id
	POST example...

	[attribute] => Array
	(
			[0] => 2
			[1] => 2
			[2] => 5
	)

	[attribute_value] => Array
	(
			[0] => 1
			[1] => 4
			[2] => 10
	)


	[attribute_value] => Array
	(
			[2] => Array
			(
				[0] => 1
				[1] => 4
			)
			[5] => Array
			(
				[0] => 10
			)
	)

	*/
	private function attributeValuesLinkToAttribute($attributes, $values) {
		$attributes_values = [];
		
		foreach ($attributes as $row => $attribute) {
			$attributes_values[$attribute][] = $values[$row];
		}
		
		return $attributes_values;
	}
	
	
	public function filterGetProducts($filter, $limits) {
		$this->stdelog->write(3, 'filterGetProducts() :: is called ');

		// Prepare Data
		$filter = $this->filterSQLPrepareData($filter);
		
		$this->stdelog->write(4, $filter, 'filterGetProducts() :: $filter after  $this->filterSQLPrepareData()');

		// Is different form filterCountProducts()
		$sql = "SELECT DISTINCT p.product_id FROM " . DB_PREFIX . "product p";
		
		// Если выборка идет по ИД товара, то нам зависимость от категории, атрибутов, опций и тд не нужна
		// Такой параметр есть только в списке товаров
		// 
		// Q? А как же артикул или модель? - Так они не уникальны (в базе). 
		// Модели могут быть похожими в разных категориях -- а там же LIKE
		// Остальные идентификационные поля зачастую используются НЕ по назначению.
		if (isset($filter['product_id']) && $filter['product_id']) {
			$sql .= " WHERE p.product_id = '" . (int) $filter['product_id'] . "'";
			goto handy_fn2_groupby; // ! is different from getTotalProducts()
		}	else {
			// Joins
			$this->stdelog->write(4, 'filterGetProducts() :: joins required');
			$sql .= $this->filterSQLAppendJoins($filter);
		}

		// Where begin
		$sql .= " WHERE p.product_id != '0'"; // :)

		// Where Category
		$sql .= $this->filterSQLAppendWhereCategories($filter);

		// Where Manufacturer
		$sql .= $this->filterSQLAppendWhereManufacturers($filter);
		
		// Where Name
		$sql .= $this->filterSQLAppendWhereName($filter);

		// Where Identifiers (model, sku, etc...)
		$sql .= $this->filterSQLAppendWhereIdentifiers($filter);
		
		// Where Attribute
		$sql .= $this->filterSQLAppendWhereAttributes($filter);
		
		// Where Option
		$sql .= $this->filterSQLAppendWhereOptions($filter);
		
		// Where Status
		if ('' !== $filter['status'] && !is_null($filter['status'])) {
			$sql .= " AND p.status = '" . (int) $filter['status'] . "'";
		}

		// Where Image	
		if ('' !== $filter['image'] && !is_null($filter['image'])) {
			if ($filter['image'] == 1) {
				$sql .= " AND (p.image IS NOT NULL AND p.image <> '' AND p.image <> 'no_image.png')";
			} else {
				$sql .= " AND (p.image IS NULL OR p.image = '' OR p.image = 'no_image.png')";
			}
		}		
		
		// Where Values Range (date, price, quantity)
		$sql .= $this->filterSQLAppendWhereValuesRange($filter);
		
		$sql .= $this->filterSQLAppendWhereDoubles($filter);
		
		handy_fn2_groupby:

		// Group - !A -- not for count(*)
		$sql .= ' GROUP BY p.product_id';
		
		// Sort - for Product List only
		$sql .= $this->filterSQLAppendSort($filter);
		
		// Limit - for Product List
		if ($limits['first_element']) {
			$sql .= " LIMIT " . (int) $limits['first_element'] . "," . (int) $limits['limit_n'] . ";";
		} else {
			$sql .= " LIMIT " . (int) $limits['limit_n'] . ";";
		}

		$this->stdelog->write(4, $sql, 'filterGetProducts() :: $sql');

		$query = $this->db->query($sql);

		$this->stdelog->write(3, $query, 'filterGetProducts() :: $query');

		if ($query) {
			$this->stdelog->write(3, $query->rows, 'filterGetProducts() :: $query->rows');

			$out = array();

			foreach ($query->rows as $value) {
				$out[] = $value['product_id'];
			}

			return $out;
		} else {
			return false;
		}
	}
	
	public function filterCountProducts($filter) {
		$this->stdelog->write(3, 'filterCountProducts() is called');

		// Prepare Data
		$filter = $this->filterSQLPrepareData($filter);
		
		$this->stdelog->write(4, $filter, 'filterCountProducts() :: $filter after  $this->filterSQLPrepareData()');

		// filterGetProducts()
		$sql = "SELECT COUNT(*) FROM " . DB_PREFIX . "product p";
		
		// Если выборка идет по ИД товара, то нам зависимость от категории, атрибутов, опций и тд не нужна
		// Такой параметр есть только в списке товаров
		// 
		// Q? А как же артикул или модель? - Так они не уникальны (в базе). 
		// Модели могут быть похожими в разных категориях -- а там же LIKE
		// Остальные идентификационные поля зачастую используются НЕ по назначению.
		
		if (isset($filter['product_id']) && $filter['product_id']) {
			$sql .= " WHERE p.product_id = '" . (int) $filter['product_id'] . "'";
			goto handy_fn1_finish;
		}	else {		
			// Joins
			$this->stdelog->write(4, 'filterCountProducts() :: joins required');
			$sql .= $this->filterSQLAppendJoins($filter);
		}
			
		// Where begin
		$sql .= " WHERE p.product_id != '0'"; // :)
			
		// Where Category
		$sql .= $this->filterSQLAppendWhereCategories($filter);

		// Where Manufacturer
		$sql .= $this->filterSQLAppendWhereManufacturers($filter);
		
		// Where Name
		$sql .= $this->filterSQLAppendWhereName($filter);

		// Where Identifiers (model, sku, etc...)
		$sql .= $this->filterSQLAppendWhereIdentifiers($filter);
		
		// Where Attribute
		$sql .= $this->filterSQLAppendWhereAttributes($filter);
		
		// Where Option
		$sql .= $this->filterSQLAppendWhereOptions($filter);
		
		// Where Status
		if ('' !== $filter['status'] && !is_null($filter['status'])) {
			$sql .= " AND p.status = '" . (int) $filter['status'] . "'";
		}

		// Where Image	
		if ('' !== $filter['image'] && !is_null($filter['image'])) {
			if ($filter['image'] == 1) {
				$sql .= " AND (p.image IS NOT NULL AND p.image <> '' AND p.image <> 'no_image.png')";
			} else {
				$sql .= " AND (p.image IS NULL OR p.image = '' OR p.image = 'no_image.png')";
			}
		}		
		
		// Where Values Range (date, price, quantity)
		$sql .= $this->filterSQLAppendWhereValuesRange($filter);
		
		$sql .= $this->filterSQLAppendWhereDoubles($filter);		
		
		handy_fn1_finish:

		$this->stdelog->write(3, $sql, 'filterCountProducts() :: $sql');

		$query = $this->db->query($sql);

		$this->stdelog->write(3, $query->row['COUNT(*)'], 'filterCountProducts() :: $query->row["COUNT(*)"]');
		
		return $query->row['COUNT(*)'];
	}
	
	private function filterSQLPrepareData($filter) {
		// If is $filter['attribute_value'] then is $filter['attribute']
		
		$this->stdelog->write(4, 'filterSQLPrepareData() is called');
		
		if (isset($filter['attribute'])) {
			$attributes_values = $this->attributeValuesLinkToAttribute($filter['attribute'], $filter['attribute_value']);
			
			$this->stdelog->write(4, $attributes_values, 'filterSQLPrepareData() :: $attributes_values');
		}

		if (isset($filter['attribute'])) {
			foreach ($filter['attribute'] as $row => $attribute) {
				if ('*' == $attribute) {
					unset($filter['attribute'][$row]);
				}
			}
		}

		$filter['attributes_values_prepared'] = [];
		
		if (isset($attributes_values)) {
			foreach ($attributes_values as $row => $attribute_value) {
				if ('*' == $attribute_value) {
					unset($attributes_values[$row]);
				}
			}
			
			$filter['attributes_values_prepared'] = $attributes_values;
		}		

		if (isset($filter['option'])) {
			foreach ($filter['option'] as $row => $option) {
				if ('*' == $option) {
					unset($filter['option'][$row]);
				}
			}
		}
		
		return $filter;
	}
	
	/*
	 * A!
	 * Before 1.15.0 there were mistake with JOIN for atribute and option
	 */	
	private function filterSQLAppendJoins($filter) {
		$sql = '';
	
		// Join Category
		if (isset($filter['category']) && 'notset' == $filter['category']) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (p2c.product_id = p.product_id)";
		}
		
		// Join product_description
		if (isset($filter['sort']) && false !== strpos($filter['sort'], 'pd.')) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id)";
		}
		
		return $sql;
	}

	private function filterSQLAppendWhereCategories($filter) {
		$sql = '';
		
		// main_category_id	
		if ($filter['main_category_id']) {		
			if ('*' == $filter['main_category_id']) {
				// Not Selected in the Filter -- do nothing
			} elseif ('notset' == $filter['main_category_id']) {
				// Select products which not have main_category in DB
				$sql .= " AND p.product_id NOT IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE main_category = '1')";
			} else {
				// Is int main_category_id
				$sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '" . (int) $filter['main_category_id'] . "' AND main_category = '1')";
			}
		}
		
		if (!isset($filter['category'])) {
			return $sql;
		}
		
		// category - array Is int category_id
		if (is_array($filter['category']) && count($filter['category']) > 0) {

			// For why? Is int main_category_id is already exist...
			// For HAVING (COUNT(*) ...
			if (isset($filter['main_category_id']) && '*' != $filter['main_category_id']) {
				if (!in_array($filter['main_category_id'], $filter['category'])) {
					$filter['category'][] = $filter['main_category_id']; // main_category is required to category
				}
			}

			# OR - AND
			if ('OR' == $filter['category_flag']) {
				// OR
				$sql .= " AND p.product_id IN ( SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id IN (";

				$i = 0;
				foreach ($filter['category'] as $category) {
					$sql .= $i ? ', ' : '';
					$sql .= (int) $category;
					$i++;
				}
				
				$sql .= ") )";
				
			} else {
				// AND
				$sql .= " AND p.product_id IN (SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id IN (";

				$i = 0;
				foreach ($filter['category'] as $category) {
					$sql .= $i ? ', ' : '';
					$sql .= (int) $category;
					$i++;
				}

				$sql .= ") GROUP BY product_id HAVING (COUNT(*) = " . (int) count($filter['category']) . ") ";

				$sql .= ")";
			}
		} 
		
		// category not set -- it means also that main category also not SET?
		if ('notset' == $filter['category']) {
			// Для этого по ходу все-таки нужен JOIN
			$sql .= " AND p2c.category_id IS NULL";
			
		}
		
		return $sql;
	}
	
	private function filterSQLAppendWhereManufacturers($filter) {
		$sql = '';
		
		if (isset($filter['manufacturer']) && count($filter['manufacturer']) > 0) {
			$sql .= " AND p.manufacturer_id IN (";

			$i = 0;
			foreach ($filter['manufacturer'] as $manufacturer) {
				$sql .= $i ? ', ' : '';
				$sql .= (int) $manufacturer;
				$i++;
			}

			$sql .= ")";
		}
		
		return $sql;
	}
	
	private function filterSQLAppendWhereName($filter) {
		$sql = '';

		if (!$filter['name']) return;
			
		$filter['name'] = array_filter($filter['name']);

		$n = count($filter['name']);
		
		if (!$n) return; // for mass edit -- POST hase empty fields

		$operator = 'AND';

		if ('OR' == $filter['name_flag']) {
			$operator = 'OR';
		}

		$sql .= " AND EXISTS (";
		$sql .= " SELECT 1 FROM " . DB_PREFIX . "product_description pd WHERE pd.product_id = p.product_id";
		$sql .= " AND (";
		$i = 0;
		foreach ($filter['name'] as $language_id => $value) {
			$sql .= ($i) ? " $operator" : '';
			
			if ('AND' == $operator) {
				$sql .= " (pd.name LIKE '%" . $this->db->escape($value) . "%' AND pd.language_id = '" . (int) $language_id . "')";
			} else {
				// OR - no need parentheses
				$sql .= " pd.name LIKE '%" . $this->db->escape($value) . "%'";
			}

			$i++;
		}
		$sql .= " )";
		if ('AND' == $operator && $n > 1) {
			$sql .= " GROUP BY pd.product_id HAVING COUNT(pd.product_id) = '" . (int) $n . "'";
		}
		$sql .= " )";

		return $sql;
	}
	
	private function filterSQLAppendWhereIdentifiers($filter) {
		$sql = '';
				
		$fields = [
			'model',
			'sku',
			'upc',
			'ean',
			'jan',
			'isbn',
			'mpn',
		];
		
		foreach ($fields as $name) {
			if (isset($filter[$name]) && !empty($filter[$name])) {
				$sql .= " AND p." . $name . " LIKE '%" . $this->db->escape($filter[$name]) . "%'";
			}
		}		
		
		return $sql;
	}
	
	/*
	 * Todo...
	 * Обработать option_value...
	 */
	private function filterSQLAppendWhereOptions($filter) {
		$sql = '';
		
		if (isset($filter['option']) && count($filter['option']) > 0) {
			$sql .= " AND EXISTS (";
			$sql .= " SELECT 1 FROM " . DB_PREFIX . "product_option po WHERE po.product_id = p.product_id AND po.option_id IN (";

				$i = 0;
				foreach ($filter['option'] as $option) {
					$sql .= $i ? ', ' : '';
					$sql .= (int) $option;
					$i++;
				}

			$sql .= ")";
			$sql .= ")";
		}
		
		return $sql;
	}
	
	private function filterSQLAppendWhereAttributes($filter) {
		$sql = '';
		
		if (isset($filter['attribute']) && count($filter['attribute']) > 0) {
			$filter['attribute'] = array_unique($filter['attribute']); // see [attribute][0] & [attribute][4] in POST example

			foreach ($filter['attribute'] as $attribute_id) {
				if ('*' == $attribute_id)
					continue;

				$sql .= " AND EXISTS (";
				
				$sql .= " SELECT 1 FROM " . DB_PREFIX . "product_attribute pa WHERE pa.product_id = p.product_id AND attribute_id = '" . (int) $attribute_id . "' AND language_id='" . (int) $this->config->get('config_language_id') . "'";

				$sql_attr_val = '';

				// Where Attribute Value
				if (isset($filter['attributes_values_prepared'][$attribute_id]) && count($filter['attributes_values_prepared'][$attribute_id]) > 0) {

					$i = 0;
					foreach ($filter['attributes_values_prepared'][$attribute_id] as $attribute_value) {
						if ('*' == $attribute_value)
							continue;

						// FUTURE 
						// $attribute_value can be empty value!!!

						$sql_attr_val	 .= $i ? ', ' : '';
						$sql_attr_val	 .= "'" . $this->db->escape($attribute_value) . "'";

						// Вспомогательные варианты значений для атрибутов	
						$variants = $this->helperAttributesEntitiesVariants($attribute_value);
						
						foreach ($variants as $variant) {
							$sql_attr_val .= ", '" . $this->db->escape($variant) . "'";
						}

						$i++; // A! $i
					}
				}

				if ($sql_attr_val) {
					$sql .= " AND text IN ($sql_attr_val)";
				}

				$sql .= ")";
			}
		}
		
		return $sql;
	}
	
	/*
	 * Attention!
	 * Differs from approach in getProducts() where it is checked via isset + !is_null()
	 * The shorthand is used here. And there was a copy-paste from the system method
	 */
	private function filterSQLAppendWhereValuesRange($filter) {
		$sql = '';
		
		if ($filter['quantity_min'] || '0' == $filter['quantity_min']) $sql .= " AND quantity >= '" . (int) $filter['quantity_min'] . "'";
		if ($filter['quantity_max'] || '0' == $filter['quantity_max']) $sql .= " AND quantity <= '" . (int) $filter['quantity_max'] . "'";
		
		if ($filter['price_min'] || '0' == $filter['price_min']) $sql .= " AND price >= '" . (float) $filter['price_min'] . "'";
		if ($filter['price_max'] || '0' == $filter['price_max']) $sql .= " AND price <= '" . (float) $filter['price_max'] . "'";
		
		// For future
		//if ($filter['tax_class_id']) $sql .= " AND tax_class_id = '" . (int) $filter['tax_class_id'] . "'";
		
		if ($filter['date_added_from']) $sql .= " AND date_added >= '" . $this->db->escape($filter['date_added_from']) . "'";
		if ($filter['date_added_before']) $sql .= " AND date_added <= '" . $this->db->escape($filter['date_added_before']) . "'";
		if ($filter['date_modified_from']) $sql .= " AND date_modified >= '" . $this->db->escape($filter['date_modified_from']) . "'";
		if ($filter['date_modified_before']) $sql .= " AND date_modified <= '" . $this->db->escape($filter['date_modified_before']) . "'";
		if ($filter['date_available_from']) $sql .= " AND date_available >= '" . $this->db->escape($filter['date_available_from']) . "'";
		if ($filter['date_available_before']) $sql .= " AND date_available <= '" . $this->db->escape($filter['date_available_before']) . "'";

		if ($filter['weight_min'] || '0' == $filter['weight_min']) $sql .= " AND weight >= '" . (float) $filter['weight_min'] . "'";
		if ($filter['weight_max'] || '0' == $filter['weight_max']) $sql .= " AND weight <= '" . (float) $filter['weight_max'] . "'";
		
		if ($filter['weight_class_id']) $sql .= " AND weight_class_id = '" . (int) $filter['weight_class_id'] . "'";
		
		if ($filter['length_min'] || '0' == $filter['length_min']) $sql .= " AND length >= '" . (float) $filter['length_min'] . "'";
		if ($filter['length_max'] || '0' == $filter['length_max']) $sql .= " AND length <= '" . (float) $filter['length_max'] . "'";
		
		if ($filter['width_min'] || '0' == $filter['width_min']) $sql .= " AND width >= '" . (float) $filter['width_min'] . "'";
		if ($filter['width_max'] || '0' == $filter['width_max']) $sql .= " AND width <= '" . (float) $filter['width_max'] . "'";
		
		if ($filter['height_min'] || '0' == $filter['height_min']) $sql .= " AND height >= '" . (float) $filter['height_min'] . "'";
		if ($filter['height_max'] || '0' == $filter['height_max']) $sql .= " AND height <= '" . (float) $filter['height_max'] . "'";
		
		if ($filter['length_class_id']) $sql .= " AND length_class_id = '" . (int) $filter['length_class_id'] . "'";
		
		return $sql;
	}
	
	private function filterSQLAppendWhereDoubles($filter) {
		$sql = '';
		
		if (isset($filter['doubles']) && count($filter['doubles']) > 0) {
			$sql .= ' AND (';
			
			foreach ($filter['doubles'] as $i => $field) {
				$sql .= ($i) ? ' OR' : '';
				// Not look for empty value doubles!
				$sql .= " $field IN ( SELECT $field FROM " . DB_PREFIX . "product WHERE $field != '' GROUP BY $field HAVING COUNT($field) > 1 )";
			}
			$sql .= ')';
		}
		
		return $sql;
	}
	

	// for Product List
	private function filterSQLAppendSort($filter) {
		$sql = '';
		
		$sort_data = array(
			'p.product_id',
			'p.sort_order',
			'pd.name',
			'p.model',
			'p.sku',
			'p.price',
			'p.quantity',
			//'p.status',
		);
		
		if (isset($filter['sort'])) {
			$parts = explode('/', $filter['sort']);

			if (in_array($parts[0], $sort_data)) {
				$sql .= " ORDER BY " . $parts[0];
				
				if ('ASC' == $parts[1]) {
					$sql .= " ASC";
				} elseif ('DESC' == $parts[1]) {
					$sql .= " DESC";
				}
			}			
		}
		
		if (empty($sql)) {
			$sql .= " ORDER BY p.product_id DESC";
		}
		
		return $sql;
	}
	
	
	

	/* Handy Prodcut Manager Mass Edit
	--------------------------------------------------------------------------- */
	//////////////////////////////////////////////////////////////////////////////
	public function massEditCategory($data) {
		$this->stdelog->write(4, 'massEditCategory() :: is called');
		$this->stdelog->write(4, $data, 'massEditCategory() :: $data : ');

		// Если нужно сбросить
		if ('delete_all_and_add_new' == $data['category_flag']) {
			$sql = "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $data['product_id'] . "'";

			$this->stdelog->write(4, $sql, 'massEditCategory() :: delete_all_and_add_new - $sql :');

			$this->db->query($sql);
		} else {
			// Что если главная категория была одной, а теперь присваивается другая?
			if ($data['main_category_id']) {
				$sql = "UPDATE " . DB_PREFIX . "product_to_category SET main_category = 0 WHERE product_id = '" . (int) $data['product_id'] . "'";

				$this->stdelog->write(4, $sql, 'massEditCategory():reset main category $sql');

				$this->db->query($sql);
			}
		}

		// Потом добавляем категории, одновременно делая одну из них главной
		// Главная категория неразрывно связана с категориями
		// Может так случиться, что человек назначает главной ту категорию, которая вообще еще не отмечена для товара
		if ($data['main_category_id'] && !in_array($data['main_category_id'], $data['categories'])) {
			$data['categories'][] = $data['main_category_id'];
		}

		if ('delete' == $data['category_flag']) {
			if (isset($data['categories'])) {
				foreach ($data['categories'] as $category) {
					$sql = "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $data['product_id'] . "' AND category_id = '" . (int) $category . "'";
					$this->stdelog->write(4, $sql, 'massEditCategory(): DELETE categories $sql ');

					$this->db->query($sql);
				}
			}
		} else {
			if (isset($data['categories'])) {
				foreach ($data['categories'] as $category) {
					$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $data['product_id'] . "', category_id = '" . (int) $category . "'";
					$this->stdelog->write(4, $sql, 'massEditCategory(): INSERT categories $sql ');

					$this->db->query($sql);
				}
			}
		}

		if ($data['main_category_id']) {
			$sql = "UPDATE " . DB_PREFIX . "product_to_category SET main_category = 1 WHERE product_id = '" . (int) $data['product_id'] . "' AND category_id = '" . (int) $data['main_category_id'] . "'";

			$this->stdelog->write(4, $sql, 'massEditCategory() :: UPDATE MAIN CATEGORY');

			$this->db->query($sql);
		}
	}

	public function massEditManufacturer($manufacturer_id, $product_id) {
		$this->stdelog->write(4, 'massEditManufacturer() :: is called');
		$this->stdelog->write(4, $manufacturer_id, 'massEditManufacturer() :: $manufacturer_id : ');
		$this->stdelog->write(4, $product_id, 'massEditManufacturer() :: $product_id : ');

		$sql = "UPDATE " . DB_PREFIX . "product SET manufacturer_id = '" . (int) $manufacturer_id . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditManufacturer() :: $sql : ');

		$this->db->query($sql);
	}

	/*
	 * A!
	 * Can be 0!
	 */
	public function massEditWeightField($weight, $product_id) {
		$this->stdelog->write(4, 'massEditWeightField() :: is called');

		$this->stdelog->write(4, $weight, 'massEditWeightField() :: $weight');

		if($weight) {
			$first_char = mb_substr($weight, 0, 1, 'UTF-8');

			if ('+' == $first_char || '-' == $first_char) {
				$weight = trim(mb_substr($weight, 1, mb_strlen($weight), 'UTF-8'));
			} else {
				return false; // Нельзя всем товарам назначать одинаковые значения!
			}

			$weight_value = (float) $weight;

			$weight_old = $this->getProductField('weight', $product_id);

			$this->stdelog->write(4, $weight_old, 'massEditWeightField() :: $weight_old');

			$weight_new = false;

			if ('+' == $first_char) {
				$weight_new = $weight_old + $weight_value;
			}

			if ('-' == $first_char) {
				$weight_new = $weight_old - $weight_value;
			}

			if ($weight_new) {
				$sql = "UPDATE " . DB_PREFIX . "product SET weight = '" . (float) $weight_new . "' WHERE product_id = '" . (int) $product_id . "'";

				$this->stdelog->write(4, $sql, 'massEditWeightField() :: $sql');

				$this->db->query($sql);
			}
		} else {
			// Передан 0
			$sql = "UPDATE " . DB_PREFIX . "product SET weight = '" . (float) 0 . "' WHERE product_id = '" . (int) $product_id . "'";

			$this->stdelog->write(4, $sql, 'massEditWeightField() :: $sql');

			$this->db->query($sql);
		}
	}

	public function massEditWeightClassField($weight_class_id, $product_id) {
		$this->stdelog->write(4, 'massEditWeightClassField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET weight_class_id = '" . (int) $weight_class_id . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditWeightClassField() :: $sql');

		$this->db->query($sql);
	}
	
	public function massEditDimensionFields($dimension = [], $product_id) {
		$this->stdelog->write(4, $dimension, 'massEditDimensionFields() :: is called with $dimension');
		
		$sql_append = '';
		
		$i = 0;
		foreach ($dimension as $field => $value) {
			$sql_append .= ($i >= 1) ? ', ' : '';
			$sql_append .= "`" . $this->db->escape($field) . "` = '" . (float) $value . "'";
			$i++;
		}

		$sql = "UPDATE " . DB_PREFIX . "product SET $sql_append WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditDimensionFields() :: $sql');

		$this->db->query($sql);
	}
	
	public function massEditLengthClassField($length_class_id, $product_id) {
		$this->stdelog->write(4, 'massEditLengthClassField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET length_class_id = '" . (int) $length_class_id . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditLengthClassField() :: $sql');

		$this->db->query($sql);
	}
	
	public function massEditSimpleField($field, $value, $product_id) {
		$this->stdelog->write(4, 'massEditSimpleField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET " . $this->db->escape($field) . " = '" . $this->db->escape($value) . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditSimpleField() :: $sql');

		$this->db->query($sql);
	}

	
	
	/*
	 * Q? massEditPriceField() обрабатывает не только price, но и ему подобные кастомные поля.
	 * Должна ли скидка меняться при массовом редактировании всех подобных полей??
	 * 
	 * Определить, надо ли присвоить всем товарам цену 0
	 * Определить, надо ли присвоить всем товарам одинаковую цену
	 * Определить, надо уменьшать или увеличивать цену
	 * Определить, надо изменить цену на % от изначальной цены или на конретное число
	 * 
	 * Определить round (с помощью полученного флага)
	 * Вычисления новой цены (с учетом выбранного round - A!)
	 * 
	 * Дополнительно
	 * При обновлении поля `price` выполнить также обновление скидок и акций для данного товара. При этом соблюсти выбранный способ округления round
	 */	
	public function massEditPriceField($field = 'price', $value = false, $product_id) {
		$this->stdelog->write(4, 'massEditPriceField() :: is called');
		$this->stdelog->write(4, $field, 'massEditPriceField() :: $field');
		$this->stdelog->write(4, $value, 'massEditPriceField() :: $value');
		$this->stdelog->write(4, $product_id, 'massEditPriceField() :: $product_id');
		
		$price_old = $this->getProductField($field, $product_id);		
		$this->stdelog->write(4, $price_old, 'massEditPriceField() :: $price_old');

		$value = trim($value);
		
		// NEW - есть скобки, значит это математическое выражение общего назначения...
		if (preg_match('/\((.*?)\)/', $value, $matches)) {
			$this->stdelog->write(3, 'massEditPriceField() :: call $this->helperParenthesizedExpression()');
			
			$value = $this->helperParenthesizedExpression($matches[1], $price_old);
			
			$this->stdelog->write(3, $value, "massEditPriceField() :: value of $price_old {$matches[1]}");
			
			return $value;
		}

		// OLD
		// Передан 0 - ответвляем
		// Q? - А как же округление?
		if ($value === '0' || $value === 0) {
			return $this->massEditPriceFieldToTheFixedValue($product_id, 'price', 0.00);
		}

		$first_char = mb_substr($value, 0, 1, 'UTF-8');
		
		$this->stdelog->write(4, $first_char, 'massEditPriceField() :: $first_char');

		
		// Передана конкретная сумма для всех товаров - такое тоже кому-то нужно - ответвляем
		// Q? - А как же округление?
		if ('+' != $first_char && '-' != $first_char) {
			return $this->massEditPriceFieldToTheFixedValue($product_id, 'price', $value);
		}
		
		
		// Если дошло сюда, то значит в значении есть + или -
		$value = mb_substr($value, 1, mb_strlen($value), 'UTF-8');
		
		$percent = false;

		// Percent value
		if (false !== strpos($value, '%')) {
			$percent = true;
			$percent_value = (float) $value;

			$this->stdelog->write(4, $percent, 'massEditPriceField() :: work with $percent');
			$this->stdelog->write(4, $percent_value, 'massEditPriceField() :: $percent_value');
		} else {
			$price_value = (float) $value;
			
			$this->stdelog->write(4, 'massEditPriceField() :: work with numeral value');			
			$this->stdelog->write(4, $price_value, 'massEditPriceField() :: $price_value');
		}
		
		$price_new = false;			
		
		if ('+' == $first_char) {
			if ($percent) {
				$price_new = $price_old + $price_old * $percent_value / 100;
				//$percent_value already exist
			} else {
				$price_new = $price_old + $price_value;
				$percent_value = (0 == $price_value) ? 0 : $price_value * 100 / $price_old;
			}
		}
		
		
		if ('-' == $first_char) {
			if ($percent) {
				$price_new = $price_old - $price_old * $percent_value / 100;
				//$percent_value already exist
			} else {
				$price_new = $price_old - $price_value;
				$percent_value = (0 == $price_value) ? 0 : $price_value * 100 / $price_old;
			}
		}
				
		// round_flag for this $field
		//$round_flag = $this->request->post['round_flag_' . $field];
		$round_flag = $this->request->post['round_flag'];
				
		$price_new = $this->helperRound($price_new, $round_flag);
		
		$this->stdelog->write(4, $price_new, 'massEditPriceField() :: $price_new');
		
		if ($price_new) {
			$sql = "UPDATE " . DB_PREFIX . "product SET " . $this->db->escape($field) . " = '" . (float) $price_new . "' WHERE product_id = '" . (int) $product_id . "'";

			$this->stdelog->write(4, $sql, 'massEditPriceField() :: $sql');

			$this->db->query($sql);
						
			// Auto Update Discount & Special
			if ('price' == $field) {
				$this->stdelog->write(4, $percent_value, 'massEditPriceField() :: $percent_value before $this->massUpdateDiscountWithPrice()');
				$this->massUpdateDiscountWithPrice($percent_value, $product_id, $first_char, $round_flag);
				$this->massUpdateSpecialWithPrice($percent_value, $product_id, $first_char, $round_flag);
			}
		}
		
		return;
	}
	
	public function massEditPriceFieldToTheFixedValue($product_id, $field = 'price', $value = 0.00) {
		$this->stdelog->write(4, 'massEditPriceFieldToTheFixedValue() :: is called');		
		$this->stdelog->write(4, $value, 'massEditPriceFieldToTheFixedValue() :: $value');

		$sql = "UPDATE " . DB_PREFIX . "product SET " . $this->db->escape($field) . " = '" . (float) $value . "' WHERE product_id = '" . (int) $product_id . "'";
		
		$this->stdelog->write(4, $sql, 'massEditPriceFieldToTheFixedValue() :: $sql');

		$query = $this->db->query($sql);
		
		$this->stdelog->write(4, $query, 'massEditPriceFieldToTheFixedValue() :: $query');
		
		return;
	}
	
	/*
	 * It is used when mass edit price
	 */
	public function massUpdateDiscountWithPrice($percent_value, $product_id, $char = '+', $round_flag) {
		$this->stdelog->write(4, 'massUpdateDiscountWithPrice() :: called');
		
		$this->stdelog->write(4, $percent_value, 'massUpdateDiscountWithPrice() :: $percent_value');
		
		$this->stdelog->write(4, $char, 'massUpdateDiscountWithPrice() :: $char');
		
		if ('+' != $char && '-' != $char) {
			$this->stdelog->write(4, 'massUpdateDiscountWithPrice() :: bad $char value');
			
			return false;
		}
		
		
		# UPDATE WITH MYSQL QUERY ONLY
		// SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '28'  // 0.0003
		// UPDATE " . DB_PREFIX . "product_discount SET price = round((price + price / 100 * 4), -2) WHERE product_id = '28' // 0.0002 - for unexist row
		// AND 0.0004 - for exist row
		// => DON'T CHECK IF row exits for this product_id
		if ('none' == $round_flag) {
			$this->stdelog->write(4, 'massUpdateDiscountWithPrice() :: UPDATE WITH MYSQL QUERY ONLY - ROUND NONE');
			
			if ('+' == $char) {
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = (price + price / 100 * " . (int) $percent_value . ") WHERE product_id = '" . (int) $product_id . "'";
			} else {
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = (price - price / 100 * " . (int) $percent_value . ") WHERE product_id = '" . (int) $product_id . "'";
			}

			$this->stdelog->write(4, $sql, 'massUpdateDiscountWithPrice() :: $sql UPDATE MYSQL');

			$query = $this->db->query($sql);
			
			$this->stdelog->write(4, $query, 'massUpdateDiscountWithPrice() :: $query UPDATE MYSQL');
			
			return;
		}
		
		if ('dozens' == $round_flag) {
			$this->stdelog->write(4, 'massUpdateDiscountWithPrice() :: UPDATE WITH MYSQL QUERY ONLY - ROUND DOZENS');
			
			if ('+' == $char) {
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = round((price + price / 100 * " . (int) $percent_value . "), -1) WHERE product_id = '" . (int) $product_id . "'";
			} else {
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = round((price - price / 100 * " . (int) $percent_value . "), -1) WHERE product_id = '" . (int) $product_id . "'";
			}

			$this->stdelog->write(4, $sql, 'massUpdateDiscountWithPrice() :: $sql UPDATE MYSQL');

			$query = $this->db->query($sql);
			
			$this->stdelog->write(4, $query, 'massUpdateDiscountWithPrice() :: $query UPDATE MYSQL');
			
			return;
		}
		
		if ('hundreds' == $round_flag) {
			$this->stdelog->write(4, 'massUpdateDiscountWithPrice() :: UPDATE WITH MYSQL QUERY ONLY - ROUND HUNDREDS');
			
			if ('+' == $char) {
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = round((price + price / 100 * " . (int) $percent_value . "), -2) WHERE product_id = '" . (int) $product_id . "'";
			} else {
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = round((price - price / 100 * " . (int) $percent_value . "), -2) WHERE product_id = '" . (int) $product_id . "'";
			}

			$this->stdelog->write(4, $sql, 'massUpdateDiscountWithPrice() :: $sql UPDATE MYSQL');

			$query = $this->db->query($sql);
			
			$this->stdelog->write(4, $query, 'massUpdateDiscountWithPrice() :: $query UPDATE MYSQL');
			
			return;
		}
		
		
		// UPDATE WITH PHP PROCESSING		
		$sql = "SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'";
		
		$this->stdelog->write(4, $sql, 'massUpdateDiscountWithPrice() :: $sql SELECT');
		
		$query = $this->db->query($sql);
		
		$this->stdelog->write(4, $query, 'massUpdateDiscountWithPrice() :: $query SELECT');
		
		if ($query->num_rows > 0) {
			foreach ($query->rows as $row) {
				$price_old = $row['price'];
				
				if ('+' == $char) {
					$price_new = $price_old + $price_old * $percent_value / 100;
				} else {
					$price_new = $price_old - $price_old * $percent_value / 100;
				}
				
				$price_new = $this->helperRound($price_new, $round_flag);
				
				$sql = "UPDATE " . DB_PREFIX . "product_discount SET price = '" . (float) $price_new . "' WHERE product_discount_id = '" . (int) $row['product_discount_id'] . "'";
				
				$this->stdelog->write(4, $sql, 'massUpdateDiscountWithPrice() :: $sql UPDATE');
				
				$query = $this->db->query($sql);
			}
		}
		
		return;		
	}
	
	/*
	 * It is used when mass edit price
	 */
	public function massUpdateSpecialWithPrice($percent_value, $product_id, $char = '+', $round_flag) {
		$this->stdelog->write(4, 'massUpdateSpecialWithPrice() :: called');
		
		$this->stdelog->write(4, $percent_value, 'massUpdateSpecialWithPrice() :: $percent_value');
		
		$this->stdelog->write(4, $char, 'massUpdateSpecialWithPrice() :: $char');
		
		if ('+' != $char && '-' != $char) {
			$this->stdelog->write(4, 'massUpdateSpecialWithPrice() :: bad $char value');
			
			return false;
		}
		
		
		# UPDATE WITH MYSQL QUERY ONLY
		// SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '28'  // 0.0003
		// UPDATE " . DB_PREFIX . "product_special SET price = round((price + price / 100 * 4), -2) WHERE product_id = '28' // 0.0002 - for unexist row
		// AND 0.0004 - for exist row
		// => DON'T CHECK IF row exits for this product_id
		if ('none' == $round_flag) {
			$this->stdelog->write(4, 'massUpdateSpecialWithPrice() :: UPDATE WITH MYSQL QUERY ONLY - ROUND NONE');
			
			if ('+' == $char) {
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = (price + price / 100 * " . (int) $percent_value . ") WHERE product_id = '" . (int) $product_id . "'";
			} else {
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = (price - price / 100 * " . (int) $percent_value . ") WHERE product_id = '" . (int) $product_id . "'";
			}

			$this->stdelog->write(4, $sql, 'massUpdateSpecialWithPrice() :: $sql UPDATE MYSQL');

			$query = $this->db->query($sql);
			
			$this->stdelog->write(4, $query, 'massUpdateSpecialWithPrice() :: $query UPDATE MYSQL');
			
			return;
		}
		
		if ('dozens' == $round_flag) {
			$this->stdelog->write(4, 'massUpdateSpecialWithPrice() :: UPDATE WITH MYSQL QUERY ONLY - ROUND DOZENS');
			
			if ('+' == $char) {
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = round((price + price / 100 * " . (int) $percent_value . "), -1) WHERE product_id = '" . (int) $product_id . "'";
			} else {
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = round((price - price / 100 * " . (int) $percent_value . "), -1) WHERE product_id = '" . (int) $product_id . "'";
			}

			$this->stdelog->write(4, $sql, 'massUpdateSpecialWithPrice() :: $sql UPDATE MYSQL');

			$query = $this->db->query($sql);
			
			$this->stdelog->write(4, $query, 'massUpdateSpecialWithPrice() :: $query UPDATE MYSQL');
			
			return;
		}
		
		if ('hundreds' == $round_flag) {
			$this->stdelog->write(4, 'massUpdateSpecialWithPrice() :: UPDATE WITH MYSQL QUERY ONLY - ROUND HUNDREDS');
			
			if ('+' == $char) {
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = round((price + price / 100 * " . (int) $percent_value . "), -2) WHERE product_id = '" . (int) $product_id . "'";
			} else {
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = round((price - price / 100 * " . (int) $percent_value . "), -2) WHERE product_id = '" . (int) $product_id . "'";
			}

			$this->stdelog->write(4, $sql, 'massUpdateSpecialWithPrice() :: $sql UPDATE MYSQL');

			$query = $this->db->query($sql);
			
			$this->stdelog->write(4, $query, 'massUpdateSpecialWithPrice() :: $query UPDATE MYSQL');
			
			return;
		}
		
		
		// UPDATE WITH PHP PROCESSING		
		$sql = "SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'";
		
		$this->stdelog->write(4, $sql, 'massUpdateSpecialWithPrice() :: $sql SELECT');
		
		$query = $this->db->query($sql);
		
		$this->stdelog->write(4, $query, 'massUpdateSpecialWithPrice() :: $query SELECT');
		
		if ($query->num_rows > 0) {
			foreach ($query->rows as $row) {
				$price_old = $row['price'];
				
				if ('+' == $char) {
					$price_new = $price_old + $price_old * $percent_value / 100;
				} else {
					$price_new = $price_old - $price_old * $percent_value / 100;
				}
				
				$price_new = $this->helperRound($price_new, $round_flag);
				
				$sql = "UPDATE " . DB_PREFIX . "product_special SET price = '" . (float) $price_new . "' WHERE product_special_id = '" . (int) $row['product_special_id'] . "'";
				
				$this->stdelog->write(4, $sql, 'massUpdateSpecialWithPrice() :: $sql UPDATE');
				
				$query = $this->db->query($sql);
			}
		}
		
		return;		
	}



	public function massEditDiscount($discounts, $product_id) {
		$this->stdelog->write(4, 'massEditDiscount() :: is called');

		if (isset($discounts['flag_clear'])) {
			// Удалить старые скидки
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'");

			unset($discounts['flag_clear']);
		}

		// Добавляем новые
		$price_base = $this->getProductField('price', $product_id);

		if ($price_base < 0.1) {
			$this->stdelog->write(4, 'WARNING -- massEditDiscount() :: $price_base is less 0.1');

			return false;
		}
		
		foreach ($discounts as $discount) {
			$value = trim($discount['price']);

			$first_char = mb_substr($value, 0, 1, 'UTF-8');

			if ('+' == $first_char || '-' == $first_char) {
				$value = ltrim($value, '+-');
			}

			$percent = false;

			if (false !== strpos($value, '%')) {
				$percent = true;
			}

			$price_value = (float) $value;

			$price_discount = false;

			if ('+' == $first_char) {
				if ($percent) {
					$price_discount = $price_base + ($price_base * $price_value / 100);
				} else {
					$price_discount = $price_base + $price_value;
				}
			}
			
			if ('-' == $first_char) {
				if ($percent) {
					$price_discount = $price_base - ($price_base * $price_value / 100);
				} else {
					$price_discount = $price_base - $price_value;
				}
			}

			if ('-' != $first_char && '+' != $first_char) {
				$price_discount = $price_value;
			}

			$this->stdelog->write(4, $price_base, 'massEditDiscount() :: $price_base');
			$this->stdelog->write(4, $price_discount, 'massEditDiscount() :: $price_discount');

			if ($price_discount) {
				$price_discount = $this->helperRound($price_discount, $this->request->post['round_flag']);
				
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $discount['customer_group_id'] . "', quantity = '" . (int) $discount['quantity'] . "', priority = '" . (int) $discount['priority'] . "', price = '" . (float) $price_discount . "', date_start = '" . $this->db->escape($discount['date_start']) . "', date_end = '" . $this->db->escape($discount['date_end']) . "'");
			}

		}
	}

	public function massEditSpecial($specials, $product_id) {
		$this->stdelog->write(4, 'massEditSpecial() :: is called');

		if (isset($specials['flag_clear'])) {
			// Удалить старые акции
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'");

			unset($specials['flag_clear']);
		}

		// Добавляем новые
		$price_base = $this->getProductField('price', $product_id);

		$this->stdelog->write(4, $price_base, 'massEditSpecial() :: $price_base');
		
		if ($price_base < 0.1) {
			$this->stdelog->write(1, 'WARNING -- massEditDiscount() :: $price_base is less 0.1');

			return false;
		}

		foreach ($specials as $special) {
			$value = trim($special['price']);

			$first_char = mb_substr($value, 0, 1, 'UTF-8');

			if ('+' == $first_char || '-' == $first_char) {
				$value = ltrim($value, '+-');
			}

			$percent = false;

			if (false !== strpos($value, '%')) {
				$percent = true;
			}

			$this->stdelog->write(4, $value, 'massEditSpecial() :: $value before (float)');

			$price_value = (float) $value;

			$this->stdelog->write(4, $price_value, 'massEditSpecial() :: $price_value after (float)');

			$price_special = false;

			if ('+' == $first_char) {
				if ($percent) {
					$price_special = $price_base + ($price_base * $price_value / 100);
				} else {
					$price_special = $price_base + $price_value;
				}
			}
			
			if ('-' == $first_char) {
				if ($percent) {
					$price_special = $price_base - ($price_base * $price_value / 100);
				} else {
					$price_special = $price_base - $price_value;
				}
			}

			if ('-' != $first_char && '+' != $first_char) {
				$price_special = $price_value;
			}

			$this->stdelog->write(4, $price_base, 'massEditSpecial() :: $price_base');
			$this->stdelog->write(4, $price_special, 'massEditSpecial() :: $price_special');

			if ($price_special) {
				$price_special = $this->helperRound($price_special, $this->request->post['round_flag']);
				
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $special['customer_group_id'] . "', priority = '" . (int) $special['priority'] . "', price = '" . (float) $price_special . "', date_start = '" . $this->db->escape($special['date_start']) . "', date_end = '" . $this->db->escape($special['date_end']) . "'");
			}

		}
	}

	/*
	 * A! Скобки не передаются сюда, а только то, что внутри них
	 * Перевести запятые к точке
	 * Обязательно вписать исходное число
	 */
	
	public function helperParenthesizedExpression($expression, $price_old) {
		if (!$this->matex) {
			require_once DIR_SYSTEM . 'library/handy/matex/Evaluator.php';

			$this->matex = new \Handy\Matex\Evaluator();
		}
		
		$expression = trim($expression);
		
		$expression = str_replace(',', '.', $expression); //1,5 -> 1.5
		
		return $this->matex->execute($price_old . $expression);
	}

	public function massEditQuantityField($value = false, $product_id) {
		$this->stdelog->write(4, 'massEditQuantityField() :: is called');

		$this->stdelog->write(4, $value, 'massEditQuantityField() :: $value');

		if ($value !== '0' && $value !== 0) {
			$value = trim($value);

			$first_char = mb_substr($value, 0, 1, 'UTF-8');

			if ('+' == $first_char || '-' == $first_char) {
				$value = ltrim($value, '+-');
			}

			$value = (float) $value;

			$value_old = $this->getProductField('quantity', $product_id);

			$value_new = $value; // Value from form

			if ('+' == $first_char) {
				$value_new = $value_old + $value;
			}

			if ('-' == $first_char) {
				$value_new = $value_old - $value;
			}

			if ('-' != $first_char && '+' != $first_char) {
				$value_new = $value;
			}

			$sql = "UPDATE " . DB_PREFIX . "product SET quantity = '" . (int) $value_new . "' WHERE product_id = '" . (int) $product_id . "'";

			$this->stdelog->write(4, $sql, 'massEditQuantityField() :: $sql');

			$this->db->query($sql);
		} else {
			// Передан 0
			$sql = "UPDATE " . DB_PREFIX . "product SET quantity = '0' WHERE product_id = '" . (int) $product_id . "'";

			$this->stdelog->write(4, $sql, 'massEditQuantityField() :: $sql');

			$this->db->query($sql);			
		}
	}

	public function massEditPoints($points, $product_id) {
		$this->stdelog->write(4, 'massEditPoints() is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET points = '" . (int) $points . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditQuantityField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditProductReward($product_reward, $product_id) {
		$this->stdelog->write(4, 'massEditProductReward() is called');

//		$this->stdelog->write(4, $product_reward, 'massEditProductReward():$product_reward');

		foreach ($product_reward as $customer_group_id => $item) {
			// $sql = "INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $customer_group_id . "', points = '" . (int) $item['points'] . "' ON DUPLICATE KEY UPDATE points = '" . (int) $item['points'] . "'";
			// ON DUPLICATE KEY UPDATE work only for PRIMARY KEY...

			$sql = "SELECT points FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'";

			$this->stdelog->write(4, $sql, 'editProductReward() $sql SELECT');

			$res = $this->db->query($sql);

			if ($res->num_rows > 0) {
				$sql2 = "UPDATE " . DB_PREFIX . "product_reward SET customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$item['points'] . "' WHERE product_id = '" . (int)$product_id . "'";

				$this->stdelog->write(4, $sql2, 'editProductReward() $sql2 UPDATE');

			} else {
				$sql2 = "INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$item['points'] . "'";

				$this->stdelog->write(4, $sql2, 'editProductReward() $sql2 INSERT');
			}

			$res2 = $this->db->query($sql2);

			$this->stdelog->write(4, $res2, 'editProductReward() $res2 product_id : ' . $product_id . ' : ');
		}
	}

	public function massEditMinimumField($minimum, $product_id) {
		$this->stdelog->write(4, 'massEditMinimumField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET minimum = '" . (int) $minimum . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditMinimumField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditStockStatusField($stock_status_id, $product_id) {
		$this->stdelog->write(4, 'massEditStockStatusField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET stock_status_id = '" . (int) $stock_status_id . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditStockStatusField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditTaxClassField($tax_class_id, $product_id) {
		$this->stdelog->write(4, 'massEditTaxClassField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET tax_class_id = '" . (int) $tax_class_id . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditTaxClassField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditSubtractField($subtract, $product_id) {
		$this->stdelog->write(4, 'massEditSubtractField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET subtract = '" . (int) $subtract . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditSubtractField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditShippingField($shipping, $product_id) {
		$this->stdelog->write(4, 'massEditShippingField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET shipping = '" . (int) $shipping . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditShippingField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditDateAvailableField($date_available, $product_id) {
		$this->stdelog->write(4, 'massEditDateAvailableField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET date_available = '" . $this->db->escape($date_available) . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditDateAvailableField() :: $sql');

		$this->db->query($sql);
	}
	
	public function massEditDateAddedField($date_added, $product_id) {
		$this->stdelog->write(4, 'massEditDateAddedField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET date_added = '" . $this->db->escape($date_added) . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditDateAddedField() :: $sql');

		$this->db->query($sql);
	}
	
	public function massEditDateModifiedField($date_modified, $product_id) {
		$this->stdelog->write(4, 'massEditDateModifiedField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET date_modified = '" . $this->db->escape($date_modified) . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditDateModifiedField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditNoindexField($noindex, $product_id) {
		$this->stdelog->write(4, 'massEditNoindexField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET noindex = '" . (int) $noindex . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditNoindexField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditStatusField($status, $product_id) {
		$this->stdelog->write(4, 'massEditStatusField() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET status = '" . (int) $status . "' WHERE product_id = '" . (int) $product_id . "'";

		$this->stdelog->write(4, $sql, 'massEditStatusField() :: $sql');

		$this->db->query($sql);
	}

	public function massEditProductStore($product_store, $product_id) {
		$this->stdelog->write(4, 'massEditProductStore() :: is called');

		$sql = "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $product_id . "'";

			$this->stdelog->write(4, $sql, 'massEditProductStore() :: $sql');

			$this->db->query($sql);

		foreach ($product_store as $store_id) {
			$sql = "INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "'";

			$this->stdelog->write(4, $sql, 'massEditProductStore() :: $sql');

			$this->db->query($sql);
		}
	}

	public function massEditProductRelated($product_related, $product_id)	{
		$this->stdelog->write(4, 'massEditProductRelated() :: is called');
		
		foreach ($product_related as $related_id) {
			$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $product_id . "', related_id = '" . (int) $related_id . "'";

			$this->stdelog->write(4, $sql, 'massEditProductRelated() :: $sql');

			$this->db->query($sql);
		}
	}
	
	public function massEditProductRelatedDelete($product_related_delete, $product_id)	{
		$this->stdelog->write(4, 'massEditProductRelatedDelete() :: is called');
		
		foreach ($product_related_delete as $related_id) {
			$sql = "DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "' AND related_id = '" . (int) $related_id . "'";

			$this->stdelog->write(4, $sql, 'massEditProductRelatedDelete() :: $sql');

			$this->db->query($sql);
		}
	}

	public function massEditProductFilter($product_filter, $product_id)	{
		$this->stdelog->write(4, 'massEditProductFilter() :: is called');
		
		foreach ($product_filter as $filter_id) {
			$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int) $product_id . "', filter_id = '" . (int) $filter_id . "'";

			$this->stdelog->write(4, $sql, 'massEditProductFilter() :: $sql');

			$this->db->query($sql);
		}
	}
	
	public function massEditProductFilterDelete($product_filter_delete, $product_id)	{
		$this->stdelog->write(4, 'massEditProductFilterDelete() :: is called');
		
		foreach ($product_filter_delete as $filter_id) {
			$sql = "DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int) $product_id . "' AND filter_id = '" . (int) $filter_id . "'";

			$this->stdelog->write(4, $sql, 'massEditProductFilterDelete() :: $sql');

			$this->db->query($sql);
		}
	}

	public function getProductField($field, $product_id) {
		$sql = "SELECT " . $this->db->escape($field) . " FROM " . DB_PREFIX . "product WHERE product_id = '" . (int) $product_id. "'";

		$this->stdelog->write(4, $sql, 'getProductField():$sql');

		$query = $this->db->query("SELECT " . $this->db->escape($field) . " FROM " . DB_PREFIX . "product WHERE product_id = '" . (int) $product_id. "'");

		return $query->row[$field];
	}

	public function massEditDescription($description, $product_id, $language_id) {
		$this->stdelog->write(4, 'massEditDescription() :: is called');

//		if (count($description) > 0) {
//			$sql = "UPDATE " . DB_PREFIX . "product_description SET";
//
//			$i = 0;
//			foreach ($description as $key => $value) {
//				if ($i) $sql .= ",";
//				$sql .= " " . $this->db->escape($key) ." = '" . $this->db->escape($value) . "'";
//				$i++;
//			}
//
//			$sql .= " WHERE product_id = '" . (int) $product_id. "' AND language_id = '" . (int) $language_id. "'";
//
//			$this->stdelog->write(4, $sql, 'massEditDescription() :: $sql');
//
//			$this->db->query($sql);
//		}
	
		
		if (count($description) > 0) {
			$sql = "INSERT INTO " . DB_PREFIX . "product_description SET"
				. " `product_id` = '" . (int)$product_id. "',"
				. " `language_id` = '" . (int)$language_id . "',";
			$i = 0;
			foreach ($description as $key => $value) {
				if ($i) $sql .= ",";
				$sql .= " " . $this->db->escape($key) ." = '" . $this->db->escape($value) . "'";
				$i++;
			}

			$sql .= " ON DUPLICATE KEY UPDATE";
			$i = 0;
			foreach ($description as $key => $value) {
				if ($i) $sql .= ",";
				$sql .= " " . $this->db->escape($key) ." = '" . $this->db->escape($value) . "'";
				$i++;
			}

			$this->stdelog->write(4, $sql, 'massEditDescription() :: $sql');

			$this->db->query($sql);
		}
	}

	// todo...
	public function replaceVars($search, $replace, $string) {
		$this->stdelog->write(4, 'replaceVars() is called');
		$this->stdelog->write(4, $search, 'replaceVars() :: $search');
		$this->stdelog->write(4, $replace, 'replaceVars() :: $replace');
		
		/*
		 *
		 * Functions:??
		 * lower_case_first -> lcfirst()
		 * upper_case_first -> ucfirst()
		 * cut($str, $cut) -> str_replace($cut, '', $str) // Причем найти совпадения в самых разных регистрах...
		 */

		// todo: variables [original_text] [ORIGINAL_TEXT]
		// для добавления какой-то типичной фразы в начало или конец <ADD_BLOCK class="added-block-1">Likes</ADD_BLOCK>[ORIGINAL_TEXT]

		$string = str_replace($search, $replace, $string);

		return $string;
	}

	public function massEditAttribute($data) {
		$this->stdelog->write(4, 'massEditAttribute() :: is called');
		$this->stdelog->write(4, $data, 'massEditAttribute() :: $data : ');
		$this->stdelog->write(4, $data['attribute_flag'], "massEditAttribute() :: \$data['attribute_flag']");
				
		if ('add' == $data['attribute_flag']) {
			return $this->massEditAttributeAdd($data);
		}
		
		if ('delete_all_and_add_new' == $data['attribute_flag']) {
			$sql = "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $data['product_id'] . "'";

			$this->stdelog->write(4, $sql, 'massEditAttribute() :: delete_all_and_add_new - $sql :');

			$this->db->query($sql);
			
			// +
			return $this->massEditAttributeAdd($data);
		}

		if ('delete' == $data['attribute_flag']) {
			return $this->massEditAttributeDelete($data);
		}
		
		if ('reset_values' == $data['attribute_flag']) {
			return $this->massEditAttributeResetValues($data);
		}		

		if ('update' == $data['attribute_flag']) {
			return $this->massEditAttributeUpdate($data);
		}		
		
		return;
	}

	public function massEditAttributeAdd($data) {
		$this->stdelog->write(4, 'massEditAttributeAdd() is called');
		
		foreach ($data['attribute'] as $attribute_key => $attribut_item) {
			if ('*' == $attribut_item) continue;

			foreach ($data['attribute_value'] as $language_id => $value) {
				$sql = "INSERT IGNORE INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $data['product_id'] . "', attribute_id = '" . (int) $attribut_item . "', language_id = '" . (int) $language_id . "'";

				if ('*' != trim($value[$attribute_key])) {
					$sql .= ", text = '" . $this->db->escape($value[$attribute_key]) . "'";
					
					$this->stdelog->write(4, $value[$attribute_key], 'massEditAttributeAdd() :: AHTUNG! $value[$attribute_key]');
				}

				$this->stdelog->write(4, $sql, 'massEditAttributeAdd() :: $sql');

				$query = $this->db->query($sql);
				
				$this->stdelog->write(4, $query, 'massEditAttributeAdd() :: $query');
			}
		}
		
		return;
	}
	
	
	/*
	 * Может быть выбран только атрибут - тогда удаляем по атрибуту
	 * А если выбран атрибут и значения - удаляем только по заданным значениями атрибута
	 * A! 
	 * Если выбран атрибут и его значение только для 1 языка - удаляем только по одному языку
	 */
	public function massEditAttributeDelete($data) {
		$this->stdelog->write(4, 'massEditAttributeDelete() is called');
		
		foreach ($data['attribute'] as $attribute_key => $attribut_item) {
			if ('*' == $attribut_item)
				continue;

			// A!
			// Значения атрибутов могут быть не выбраны, но поле attribute_value[2][0] присутствуют в запросе POST
			// if (count($data['attribute_value']) < 1) { не подходит для проверки...
			
			// Warning!
			// Значение * (соответствует селектору "Выберите значение") в attribute_value[2][0] конфликтовало с massEditAttributeAdd(), если при добавлении атрибута не выбрано никакое значение...
			// Q??
			// А почему конфликт, если там if ('*' != trim($value[$attribute_key])) {
			foreach ($data['attribute_value'] as $language_id => $value) {
				$flag_was_deleted_by_attribute_value = false; // set default for each itteration!
				
				if (isset($value[$attribute_key]) && '*' != trim($value[$attribute_key])) {
					$sql = "DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `attribute_id` = '" . (int) $attribut_item . "' AND `language_id` = '" . (int) $language_id . "' AND `text` = '" . $this->db->escape($value[$attribute_key]) . "'";

					$this->stdelog->write(4, $sql, 'massEditAttributeDelete() :: $sql delete attribute values by attribute value - `lang` ' . $language_id . ' and  `$value[$attribute_key]` ' . $value[$attribute_key]);

					$query = $this->db->query($sql);
					
					$this->stdelog->write(4, $query, 'massEditAttributeDelete() :: $query');
					
					$flag_was_deleted_by_attribute_value = true;
				} else {
					// Не удаляю по атрибуту в этом блоке, потому что есть 2 или более языка
					// Если удалили хотя бы по 1 значение атрибута хотя бы на 1 языке, тогда не трогаем удаление по атрибуту
				}
			}
			
			// Если не было удаления по языку, то удалять по attribute_id полностью
			if (isset($flag_was_deleted_by_attribute_value) && !$flag_was_deleted_by_attribute_value) {
				$sql = "DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `attribute_id` = '" . (int) $attribut_item . "'";

					$this->stdelog->write(4, $sql, 'massEditAttributeDelete() :: $sql delete attribute values by attribute_id');

					$query = $this->db->query($sql);
					
					$this->stdelog->write(4, $query, 'massEditAttributeDelete() :: $query');
			}
		}
		
		return;
	}
	
	/*
	 * Это обнуляет значение атрибутов для всех языков, которые присутствуют в системе
	 * Выбор значения атрибута в форме массового редактирования значения НЕ ИМЕЕТ
	 */
	public function massEditAttributeResetValues($data) {
		$this->stdelog->write(4, 'massEditAttributeResetValues() is called');
		
		foreach ($data['attribute'] as $attribute_key => $attribut_item) {
			if ('*' == $attribut_item)
				continue;

			$sql = "UPDATE `" . DB_PREFIX . "product_attribute` SET `text` = '' WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `attribute_id` = '" . (int) $attribut_item . "'";

			$this->stdelog->write(4, $sql, 'massEditAttributeResetValues() :: $sql delete attribute values by attribute_id');

			$this->db->query($sql);
		}
	}
	
	/*
	 * Attention!
	 * Фильтры могут быть заданы по атрибуту - вопросов нет
	 * А если будет задан по категории или бренду (etc), то может попасться товар,
	 * где данный атрибут не заполнен. Как тогда поступать?
	 * Можно задать поведение добавлять атрибут, если он отсутствует.
	 * !!
	 * Но в документации описано, что если атрибута нет, то он НЕ БУДЕТ добавлен (версия 1.12.0)
	 * https://help.sergetkach.com/article/24-%D0%9C%D0%B0%D1%81%D1%81%D0%BE%D0%B2%D0%BE%D0%B5-%D1%80%D0%B5%D0%B4%D0%B0%D0%BA%D1%82%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5-%D0%B8-%D0%B4%D0%BE%D0%B1%D0%B0%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5-%D0%B0%D1%82%D1%80%D0%B8%D0%B1%D1%83%D1%82%D0%BE%D0%B2-%D0%B2-OpenCart-%D1%81-%D0%BF%D0%BE%D0%BC%D0%BE%D1%89%D1%8C%D1%8E-%D0%BC%D0%BE%D0%B4%D1%83%D0%BB%D1%8F-Handy-Product-Manager
	 * В общем, посмотрим, что будут запрашивать покупатели, если вообще будут запрашивать
	 * 
	 * A!
	 * Проблемная ситуация
	 * Был одноязычный сайт. Добавили второй язык. Атрибуты почему-то не добавились (мб не через админку добавляли)
	 * При использовании флага "Заменить значения" в massEditAttributeUpdate() значения заменяются только для одного языка. А отсутствующие записи или пустота для второго языка остается...
	 * Хм, а если использовать флаг "Добавить выбранные к существующим" ?
	 * Он будет искать по языку админки... Или языку, который задан по умолчанию...
	 * Это сработало, когда язык админки совпадает с языком, на котором атрибуты присутствуют.
	 * getProductsForEditOnIteration() не дает нам товар, в котором отсутствует данный атрибут...
	 * И что дальше?
	 * Моя задача - дать инструмент, как решить этот вопрос. Это может быть просто статья, как это все провернуть, не
	 * обязательно создавать еще один запрос, который будет искать товары как-то иначе...
	 */
	public function massEditAttributeUpdate($data) {
		$this->stdelog->write(4, 'massEditAttributeUpdate() is called');
		
		foreach ($data['attribute'] as $attribute_key => $attribut_item) {
			if ('*' == $attribut_item)
				continue;
			
			foreach ($data['attribute_value'] as $language_id => $value) {
				if (isset($value[$attribute_key]) && '*' != trim($value[$attribute_key])) {
					//$sql = "UPDATE `" . DB_PREFIX . "product_attribute` SET `text` = '" . $this->db->escape($value[$attribute_key]) . "' WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `attribute_id` = '" . (int) $attribut_item . "' AND `language_id` = '" . (int) $language_id . "'";

					$sql = "INSERT INTO `" . DB_PREFIX . "product_attribute` SET "
						. " `product_id` = '" . (int) $data['product_id'] . "',"
						. " `attribute_id` = '" . (int) $attribut_item . "',"
						. " `language_id` = '" . (int) $language_id . "',"
						. " `text` = '" . $this->db->escape($value[$attribute_key]) . "'"
						. " ON DUPLICATE KEY UPDATE `text` = '" . $this->db->escape($value[$attribute_key]) . "'";

					$this->stdelog->write(4, $sql, 'massEditAttributeUpdate() :: update attribute values $sql');

					$query = $this->db->query($sql);
					
					$this->stdelog->write(4, $query, 'massEditAttributeUpdate() :: update attribute values $query');
				}				
			}
		}
	}

	public function massEditOption($data) {
		$this->stdelog->write(4, 'massEditOption() :: is called');
		$this->stdelog->write(4, $data, 'massEditOption() :: $data : ');
		$this->stdelog->write(4, $data['option_flag'], "massEditAttribute() :: \$data['option_flag']");
		
		if ('add' == $data['option_flag']) {
			return $this->massEditOptionAdd($data);
		}

		if ('delete_all_and_add_new' == $data['option_flag']) {
			// отличается от просто delete
			// тут удаляется все, а потом добавляется заданное
			$sql = "DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $data['product_id'] . "'";

			$this->stdelog->write(4, $sql, 'massEditOption() :: delete_all_and_add_new - $sql 1:');

			$this->db->query($sql);

			$sql = "DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int) $data['product_id'] . "'";

			$this->stdelog->write(4, $sql, 'massEditOption() :: delete_all_and_add_new - $sql 2:');

			$this->db->query($sql);
			
			// add Потом добавляем заявленные опции
			return $this->massEditOptionAdd($data);
		}
		
		// Only Selected Values!!
		// Бывает удаление опции в целом и отдельного значения в частности
		// Это нужно отличать!
		if ('delete' == $data['option_flag']) {
			return $this->massEditOptionDelete($data);
		}
		
		if ('update_requirement' == $data['option_flag']) {
			return $this->massEditOptionUpdateRequirement($data);
		}
	}

	public function massEditOptionAdd($data) {
		foreach ($data['option'] as $option_item) {
			
			# Сложная опция - массив
			if (isset($option_item['option_value']) && is_array($option_item['option_value'])) {
				// A! $option_item['option_value'] === option_value_id !!
				// сначала вставляются записи в product_option
				// потом доставляются значения в product_option_value

				$this->stdelog->write(4, $option_item, 'massEditOptionAdd() :: $option_item');
				
				foreach ($option_item['option_value'] as $key => $value) {
					// check for each itteration !!
					$product_option_id = $this->existOptionInProductOption($data['product_id'], $option_item['option_id']);

					if (!$product_option_id) {
						$sql = "INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $option_item['option_id'] . "', value = '', required = '" . (int) $option_item['option_require'] . "'";

						$this->stdelog->write(4, $sql, 'massEditOptionAdd() :: add option value array item in product_option');

						$this->db->query($sql);

						$product_option_id = $this->db->getLastId();
					} else {
						$sql = "UPDATE " . DB_PREFIX . "product_option SET product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $option_item['option_id'] . "', value = '" . $this->db->escape($option_item['option_value'][$key]) . "', required = '" . (int) $option_item['option_require'] . "' WHERE product_option_id = '" . (int) $product_option_id . "'";

						$this->stdelog->write(4, $sql, 'massEditOptionAdd() :: $product_option_id was exist - UPDATE');

						$this->db->query($sql);
					}

					if ($product_option_id) {

						//$product_option_id, $product_id, $option_id, $option_value_id,
						$product_option_value_id = $this->existOptionInProductOptionValue($product_option_id, $data['product_id'], $option_item['option_id'], $value);

						if (!$product_option_value_id) {
							$sql = "INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int) $product_option_id . "', product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $option_item['option_id'] . "', option_value_id = '" . (int) $value . "', quantity = '" . (int) $option_item['quantity'][$key] . "', subtract = '" . (int) $option_item['subtract'][$key] . "', price = '" . (float) $option_item['price'][$key] . "', price_prefix = '" . $this->db->escape($option_item['price_prefix'][$key]) . "', points = '" . (int) $option_item['points'][$key] . "', points_prefix = '" . $this->db->escape($option_item['points_prefix'][$key]) . "', weight = '" . (float) $option_item['weight'][$key] . "', weight_prefix = '" . $this->db->escape($option_item['weight_prefix'][$key]) . "'";
							$this->stdelog->write(4, $sql, 'massEditOptionAdd() :: add option value array item in product_option_value INSERT');
						} else {
							$sql = "UPDATE " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int) $product_option_id . "', product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $option_item['option_id'] . "', option_value_id = '" . (int) $value . "', quantity = '" . (int) $option_item['quantity'][$key] . "', subtract = '" . (int) $option_item['subtract'][$key] . "', price = '" . (float) $option_item['price'][$key] . "', price_prefix = '" . $this->db->escape($option_item['price_prefix'][$key]) . "', points = '" . (int) $option_item['points'][$key] . "', points_prefix = '" . $this->db->escape($option_item['points_prefix'][$key]) . "', weight = '" . (float) $option_item['weight'][$key] . "', weight_prefix = '" . $this->db->escape( $option_item['weight_prefix'][$key]) . "' WHERE product_option_value_id = '" . (int) $product_option_value_id . "'";
							$this->stdelog->write(4, $sql, 'massEditOptionAdd() :: add option value array item in product_option_value UPDATE');
						}

						$this->db->query($sql);
					}
				}
			} else {
				# Простая опция - строка
				
				// вставляются записи только в product_option
				// A!
				// Вообще, надо все эти операции разложить по разным методам.
				// Особенно, что касается обновления обязательности без замены значений - тут вообще надо создать отдельный флаг
				

				// check separately from $option_item['option_value'] !!
				$product_option_id = $this->existOptionInProductOption($data['product_id'], $option_item['option_id']);

				if (!isset($option_item['option_value']) || empty($option_item['option_value'])) {
					$option_item['option_value'] = false;
				}
				

				if (!$product_option_id) {
					$sql = "INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $option_item['option_id'] . "'";
					if ($option_item['option_value']) {
						$sql .= ", value = '" . $this->db->escape($option_item['option_value']) . "'";
					}
					$sql .= ", required = '" . (int) $option_item['option_require'] . "'";

					$this->stdelog->write(4, $sql, 'massEditOptionAdd() :: add option simple value INSERT');
				} else {
					$sql = "UPDATE " . DB_PREFIX . "product_option SET product_id = '" . (int) $data['product_id'] . "', option_id = '" . (int) $option_item['option_id'] . "'";
					if ($option_item['option_value']) {
						$sql .= ", value = '" . $this->db->escape($option_item['option_value']) . "'";
					}
					$sql .= ", required = '" . (int) $option_item['option_require'] . "' WHERE product_option_id = '" . (int) $product_option_id . "'";

					$this->stdelog->write(4, $sql, 'massEditOptionAdd() :: add option simple value UPDATE');
				}

				$this->db->query($sql);
			}
		}		
	}
	
	/*
	 * Внимание!
	 * Удаление почему-то отличается от удаления при флаге reset_values
	 * В данном случае удаление в тч по значениям опций!
	 */
	public function massEditOptionDelete($data) {
	 foreach ($data['option'] as $option_item) {
			if ('*' == $option_item) {
				continue;
			}

			$this->stdelog->write(4, $option_item, 'massEditOptionDelete() :: $option_item');

			// Внимание!
			// Если не выбрано ни одно значение опции, то поля $option_item['option_value'] НЕ СУЩЕСТВУЕТ!
			// А если поле существует, но НЕ является массивом, значит, это простая опция
			if (isset($option_item['option_value']) && is_array($option_item['option_value'])) {
				// A! $option_item['option_value'] === option_value_id !!
				//$flag_was_deleted_by_value = false; // Флаг не нужен. В значениях опций нет варианта "не выбрано". Если иттерация выполняется, значит там уже что-то было выбрано, и значит там уже есть массив...
				// Узнать количество значений у данной опции в данном товаре!!
				// Сравнить с количество элементов в массиве на удаление
				// И если одинаково, то цикл и не нужен
				// Просто сносим все нафиг
				$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "'";

				$query = $this->db->query($sql);

				if ($query->row['total'] > 0 && $query->row['total'] == count($option_item['option_value'])) {
					// В массиве столько же значений опций, сколько в само товаре. Значит надо снести все + саму опцию тоже
					$sql = "DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "'";

					$this->stdelog->write(4, $sql, 'massEditOptionDelete() :: product has equals countity option_values as array - delete product option $sql : ');
					$this->db->query($sql);

					$sql = "DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "'";

					$this->stdelog->write(4, $sql, 'massEditOptionDelete() :: product has equals countity option_values as array - delete product option values $sql : ');

					$this->db->query($sql);

					continue;
				}

				foreach ($option_item['option_value'] as $key => $value) {
					$sql = "DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "' AND `option_value_id` = '" . (int) $value . "'";

					$this->stdelog->write(4, $sql, 'massEditOptionDelete() :: delete option value- $sql : ');

					$this->db->query($sql);
				}
			} else {
				// Удаляются только опции, у которых вообще нет отдельных значений в product_option_value
				$sql = "DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "'";

				$this->stdelog->write(4, $sql, 'massEditOptionDelete() :: delete option - $sql : ');

				$this->db->query($sql);

				// Но! Помним, что человек мог просто не выбрать значений для опции, желая их всех удалить вместе с опцией					
				// А если массива нет, то надо проверить, а есть ли у этой опции вообще варианты значений или это простая опция...
				// Или вместо выполнения 2 запросов, выполнить удаление вслепую...
				// Q? А что проще: узнавать, есть ли у опции в принципе значения опций и потом удалять
				// Или выполнить удаление вслепую? И если у опции нет значений, то просто будет безрезультативный запрос
				// Что технически работает более быстро?
				// На демо данных (мало строк в таблице) вышел такой расклад:
				// SELECT * FROM `" . DB_PREFIX . "option_value` WHERE `option_id` = '4' - 0.0003 с. (пару раз 0.0004)
				// SELECT `option_value_id` FROM `" . DB_PREFIX . "option_value` WHERE `option_id` = '4' - 0.0003 с.
				// DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = '42' AND `option_id` = '4'  - 0.0002.

				$sql = "DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "'";

				$this->stdelog->write(4, $sql, 'massEditOptionDelete() :: delete option values - $sql : ');

				$query = $this->db->query($sql);

				$this->stdelog->write(4, $query, 'massEditOptionDelete() :: delete option values - $query : ');
			}
		}

		return;
	}
	
	// Но??
	// Если человек ошибется с флагом, то лучше все-таки иметь проверку на существование значения опции в POST...
	public function massEditOptionUpdateRequirement($data) {
		foreach ($data['option'] as $option_item) {
			if ('*' == $option_item) {
				continue;
			}
			
			$sql = "UPDATE `" . DB_PREFIX . "product_option` SET `required` = '" . (int) $option_item['option_require'] . "' WHERE `product_id` = '" . (int) $data['product_id'] . "' AND `option_id` = '" . (int) $option_item['option_id'] . "'";

			$this->stdelog->write(4, $sql, 'massEditOptionUpdateRequirement() :: delete option - $sql : ');

			$this->db->query($sql);
			
		}
	}

	public function existOptionInProductOption($product_id, $option_id) {
		$sql = "SELECT * FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "' AND option_id = '" . (int) $option_id . "'";

		$this->stdelog->write(4, $sql, 'existOptionInProductOption() :: $sql');

		$query = $this->db->query($sql);

		$this->stdelog->write(4, $query, 'existOptionInProductOption() :: $query');

		if ($query->num_rows < 1) {
			return false;
		} else {
			return $query->row['product_option_id'];
		}
	}

	public function existOptionInProductOptionValue($product_option_id, $product_id, $option_id, $option_value_id) {
		$sql = "SELECT * FROM " . DB_PREFIX . "product_option_value WHERE product_option_id = '" . (int) $product_option_id . "' AND product_id = '" . (int) $product_id . "' AND option_id = '" . (int) $option_id . "' AND option_value_id = '" . (int) $option_value_id . "'";

		$this->stdelog->write(4, $sql, 'existOptionInProductOptionValue() :: $sql');

		$query = $this->db->query($sql);

		$this->stdelog->write(4, $query, 'existOptionInProductOptionValue() :: $query');

		if ($query->num_rows < 1) {
			return false;
		} else {
			return $query->row['product_option_value_id'];
		}
	}

	public function massEditDate($product_id) {
		$this->stdelog->write(4, 'massEditDate() :: is called');

		$sql = "UPDATE " . DB_PREFIX . "product SET date_modified = '" . $this->db->escape( date("Y-m-d H:m:s", time()) ). "'";

		$this->stdelog->write(4, $sql, 'massEditDate() :: $sql');

		$this->db->query($sql);
	}




	/* Handy Prodcut Manager Helpers
	--------------------------------------------------------------------------- */
	//////////////////////////////////////////////////////////////////////////////

	public function callByLiveEdit($essence) {
		// callByLiveEdit() place for customize
		return false;
	}

	public function callByMassEdit() {
		// callByMassEdit() place for customize
		return false;
	}

	public function getH1() {
		$sql = "SHOW COLUMNS FROM " . DB_PREFIX . "product_description";
		$query = $this->db->query($sql);

		foreach ($query->rows as $key => $field) {
			if ('meta_h1' == $field['Field']) {
				return 'meta_h1';
			}

			if ('h1' == $field['Field']) {
				return 'h1';
			}
		}

		return false;
	}

	public function issetField($field) {
		$sql = "SHOW COLUMNS FROM " . DB_PREFIX . "product WHERE Field = '" . $this->db->escape($field) . "'";
		$query = $this->db->query($sql);
		
		if ($query->num_rows > 0) {
		return true;
		}

		return false;
	}

	public function getProductTableColumns() {
		$sql = "SHOW COLUMNS FROM " . DB_PREFIX . "product";
		$query = $this->db->query($sql);

		$result = array();

		foreach ($query->rows as $key => $field) {
			if (!$this->isStandartProductField($field['Field'])) {
				$result[$field['Field']] = $field['Type'];
			}
		}

		return $result;
	}

	public function isStandartProductField($field) {
		if (in_array($field, array(
			'product_id',
			'model',
			'sku',
			'upc',
			'ean',
			'jan',
			'isbn',
			'mpn',
			'location',
			'quantity',
			'stock_status_id',
			'image',
			'manufacturer_id',
			'shipping',
			'price',
			'points',
			'tax_class_id',
			'date_available',
			'weight',
			'weight_class_id',
			'length',
			'width',
			'height',
			'length_class_id',
			'subtract',
			'minimum',
			'sort_order',
			'status',
			'viewed',
			'date_added',
			'date_modified',
			'noindex', // for OpenCart PRO
			'options_buy', // for OpenCart PRO
		))) {
			return true;
		}

		return false;
	}

	public function hasMainCategoryColumn() {
		$sql = "SHOW COLUMNS FROM " . DB_PREFIX . "product_to_category;";
		$query = $this->db->query($sql);

		// Изначально в таблице 2 поля
		if ($query->num_rows > 2) {
			foreach ($query->rows as $field) {
				if ('main_category' == $field['Field']) {
					return true;
				}
			}
		}

		return false;
	}


	public function getCategoriesLevel1() {
		$array = array();

		$sql = "SELECT DISTINCT c.category_id, cd.name FROM " . DB_PREFIX . "category c"
			. " LEFT JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = c.category_id"
			. " WHERE c.parent_id = '0' AND cd.language_id = '" . $this->config->get('config_language_id') . "' ORDER BY cd.name ASC";

		$query = $this->db->query($sql);

		foreach ($query->rows as $result) {
			$array[] = $result['category_id'];
		}

		return $array;
	}

	public function getDescendantsTreeForCategory($category_id) {
		$array = array(
			'category_id' => $category_id,
			'category_name'	=> $this->getCategoryName($category_id)
		);

		// dauthers
		$sql = "SELECT category_id FROM " . DB_PREFIX . "category WHERE parent_id = '" . (int) $category_id . "'";

		$query = $this->db->query($sql);

		if ($query->num_rows > 0) {
			$array['has_children'] = 1;

			foreach ($query->rows as $result) {
				$array['children'][] = $this->getDescendantsTreeForCategory($result['category_id']);
			}
		} else {
			$array['has_children'] = false;
		}

		return $array;
	}

	public function getDescendantsLinear($category_id) {
		$array = array();

		$sql = "SELECT category_id FROM " . DB_PREFIX . "category_path WHERE path_id = '" . (int) $category_id . "' AND category_id != '" . (int) $category_id . "'";

		$query = $this->db->query($sql);

		if ($query->num_rows > 0) {
			foreach ($query->rows as $result) {
				$array[] = $result['category_id'];
			}
		}

		return $array;
	}

	public function getCategoryName($category_id, $language_id = false) {
		if (!$language_id)
			$language_id = $this->config->get('config_language_id');

		$sql = "SELECT name FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int) $category_id . "' AND language_id = '" . (int) $language_id . "'";

		$query = $this->db->query($sql);

		if (isset($query->row['name'])) {
			return $query->row['name'];
		}

		return 'No Category Name';
	}

	private function getProductData($product_id) {
		$this->stdelog->write(4, 'getProductData() is called');

    $query = $this->db->query("SELECT `sku`, `model`, `manufacturer_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "'");

    if ($query->row) {
      return $query->row;
    } else {
			$this->stdelog->write(4, $query, 'getProductData() $query error');
    }

    return false;
  }

	public function getManufacturerNameById($manufacturer_id) {
		$sql		 = "SELECT name FROM " . DB_PREFIX . "manufacturer WHERE manufacturer_id='" . (int) $manufacturer_id . "'";
		$result	 = $this->db->query($sql);
		if ($result->row) {
			return $result->row['name'];
		}
		return false;
	}


	/*
	 * В первую очередь все десятичные числа мы округляем
	 */
	public function helperRound($price, $round_flag) {
		$this->stdelog->write(4, 'helperRound() :: is called');
		$this->stdelog->write(4, $price, 'helperRound() :: $price on input');
		$this->stdelog->write(4, $round_flag, 'helperRound() :: $round_flag');

		$price_rounded = round($price);

		$this->stdelog->write(4, $price_rounded, 'helperRound() :: $price_rounded on start');

		$res = false;
		
		switch ($round_flag) {
			case 'none':
				$res = $price;
				break;
			case 'integer':
				$res = $price_rounded;
				break;
			case 'fives':
				$res = $this->helperRoundUpToAny($price_rounded, 5);
				break;
			case 'nines':
				$res = round($price_rounded, -1) - 1;				
				break;
			case 'dozens':
				$res = round($price_rounded, -1);
				break;
			case 'fifties':
				if ($price_rounded < 50)
					$res = 50;
				else {
					// 75
					// 150
					$res = $this->helperRoundUpToAny($price_rounded, 50);
				}
				break;
			case 'hundreds':
				$res = round($price_rounded, -2);
				break;
			case 'hundreds-with-nines':
				if ($price_rounded >= 150)
					$res = round($price_rounded, -2) - 1;
				else
					$res = 99;
				break;
			default:
				break;
		}
		
		$this->stdelog->write(4, $res, 'helperRound() :: $res');
		
		return $res;
	}
	
	// Изначально начал использовать метод 2 Округлите до ближайшего кратного 5, включите текущее число 
	// из лучшего ответа на https://coderoad.ru/4133859/Округление-до-ближайшего-кратного-пяти-в-PHP
	// 
	// Но была Проблема!
	// 1870 -> 1900
	// 5903 -> 5950
	// 
	// Потом задумался, и сделал свое решение	
	public function helperRoundUpToAny($n, $x = 5) {
		$this->stdelog->write(4, 'helperRoundUpToAny() is called');
		$this->stdelog->write(4, $n, 'helperRoundUpToAny() :: $n');
		$this->stdelog->write(4, $x, 'helperRoundUpToAny() :: $x');
		
		$modulo = $n % $x;
		
		//$this->stdelog->write(4, $modulo, 'helperRoundUpToAny() :: $modulo');
		//$this->stdelog->write(4, $x / 2, 'helperRoundUpToAny() :: $x / 2');
		
		if ($modulo >= $x / 2) {
			$res = $n - $modulo + $x;
		} else {
			$res = $n - $modulo;
		}
		
		//$this->stdelog->write(4, $res, 'helperRoundUpToAny() :: $res');
		
		return $res;
	}
	
	
	/* SEO URL
	------------------------------------------------------------ */

	public function translit($string, $setting) {
		$this->load->model('tool/translit');

		$string = html_entity_decode(mb_strtolower($string));

		$translit_function = $setting['translit_function'];

		if ($translit_function) {
			$string = $this->model_tool_translit->$translit_function($string);
		}

		$string = $this->model_tool_translit->clearWasteChars($string);

		return $string;
	}

	public function setURL($essence, $primary_key, $essence_id, $setting) {
		$this->stdelog->write(4, 'setURL() is called');

		$name = $this->getEssenceName($essence, $primary_key, $essence_id, $setting);

		if (!$name) {
			return false;
		}

		$this->stdelog->write(4, $name, '$name');

		if ($setting['translit_formula']) {
			// product

			$this->stdelog->write(4, $setting, '$setting');

			$model = '';
			$sku = '';
			$manufacturer_name = '';
			//$product_id = $essence_id;

			if (false !== strstr($setting['translit_formula'], '[model]') || false !== strstr($setting['translit_formula'], '[sku]') || false !== strstr($setting['translit_formula'], '[manufacturer_name]')) {
				$product_item = $this->getProductData($essence_id);

				if ($product_item) {
					$this->stdelog->write(4, $product_item, '$product_item');

					$model = $product_item['model'];
					$sku	 = $product_item['sku'];

					if (false !== strstr($setting['translit_formula'], '[manufacturer_name]')) {
						$manufacturer_name = $this->getManufacturerNameById($product_item['manufacturer_id'], $setting);

						$this->stdelog->write(4, $manufacturer_name, '$manufacturer_name');

					}
				}
			} else {
				$this->stdelog->write(4, 'Formula not contain data');
			}

			$string_to_translit = str_replace(array('[product_name]', '[product_id]', '[model]', '[sku]', '[manufacturer_name]'), array($name, $essence_id, $model, $sku, $manufacturer_name), $setting['translit_formula']);

			$this->stdelog->write(4, $string_to_translit, '$string_to_translit');
		} else {
			$string_to_translit = $name;
		}

		$keyword = $this->translit(mb_strtolower($string_to_translit), $setting);
		$keyword = $this->getUniqueUrl($keyword);

		$this->stdelog->write(4, $name, '$name');
		$this->stdelog->write(4, $keyword, '$keyword');

		// if success return inserted new SEO URL
		if ($this->insertURL($essence, $essence_id, $keyword)) {
			return $keyword;
		} else {
			return false;
		}
	}

	private function getEssenceName($essence, $primary_key, $essence_id, $setting) {

    $column_name = 'name';

    // Warning I (!)
    if ('information' == $essence) {
      $column_name = 'title';
    }

    if ('manufacturer' == $essence) {
      return $this->getManufacturerNameById($essence_id, $setting);
    }

    $sql = "SELECT `$column_name` FROM `" . DB_PREFIX . $essence . "_description` WHERE `" . $primary_key . "` = '" . (int)$essence_id . "' AND `language_id` = '" . (int)$setting['language_id'] . "'";

    $query = $this->db->query($sql);

    if($query) {
      return $query->row[$column_name]; // Warning I (!)
    } else {
      return false;
    }
  }
	
	public function getPricePrefixSymbols() {
		// Видел варианты, когда символы не соответствовали значениями...
		// <option value="u">+%</option>
		// Не факт, что так везде. Но надо быть готовым к такому раскладу...
		return [
			'=' => '=',
			'*' => '*',
			'/' => '/',
			'+%' => '+%',
			'-%' => '-%',
			//'u' => '+%',
			//'d' => '-%',
		];
	}

	/*
	 * КАВЫЧКИ
	 * Если есть значение с кавычками, то иметь ввиду, что это значение
	 * может быть вписано в админке и прогнано через escape,
	 * а может быть импортировано в базу без escape.
	 * А еще может быть, что значения для разных товаров сохранены в разных вариантах...
	 * 
	 * АПОСТРОФЫ
	 * Опять же при импорте модули ставят вместо апострофа код апострофа &#039;
	 * text IN ('В\'єтнам') не найдет в базе В&#039;єтнам
	 * При этом у меня на уровне формирования вьюшки в фильтрах и в с рабочей области апострофы приводятся к html-коду (без этого ломался js).
	 * Но при вызове ajax-запроса в PHP попадает уже обычный апостроф
	 * Но в базе может хранится спарсенный вариант с html-кодом апострофа
	 */
	private function helperAttributesEntitiesVariants($string) {
		$array = [];
		
		// КАВЫЧКИ
		if (false !== (strpos($string, '&quot;'))) {
			$array[] = str_replace('&quot;', '"', $string);
		}

		// АПОСТРОФЫ
		if (false !== (strpos($string, "'"))) {
			$array[] = str_replace("'", '&#039;', $string);
		}

		return $array; 
	}
	
	/*
	 * // For OC 2 - it is placed in install.php, not in model
	 */
	public function extensionUpdateDefaultValues() {
		$txt_fields_types = [
			'name'						 => 'varchar',
			'description'			 => 'text',
			'tag'							 => 'text',
			'meta_title'			 => 'varchar',
			'meta_description' => 'varchar',
			'meta_keyword'		 => 'varchar',
			// possible
			'meta_h1'					 => 'varchar',
			'h1'							 => 'varchar',
		];
		$txt_fields	= array_keys($txt_fields_types);
		
		$sql = "SHOW FULL COLUMNS FROM `" . DB_PREFIX . "product_description`";
		$query = $this->db->query($sql);
		
		foreach ($query->rows as $row) {
			if (in_array($row['Field'], $txt_fields)) {
				// A-1: Not Null
				if ('NO' == $row['Null']) {
					// A-2: Not defined default value
					if (NULL === $row['Default']) {
						$type	= ('varchar' == $txt_fields_types[$row['Field']]) ? 'VARCHAR(255)' : 'TEXT';
						$sql = "ALTER TABLE `" . DB_PREFIX . "product_description` CHANGE `" . $row['Field'] . "` `" . $row['Field'] . "` $type CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''";
						$query = $this->db->query($sql);
					}
				}
			}
		}
	}
	
}