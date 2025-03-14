<?php

class ControllerExtensionModuleSearchSuggestion extends Controller {

	public function index() {

		$this->id = 'search_suggestion';
		
		$this->load->model('extension/module/search_suggestion');
		
		$options = $this->model_extension_module_search_suggestion->getOptions();;

		$this->document->addScript('catalog/view/javascript/search_suggestion.js');
		$this->document->addScript('catalog/view/javascript/search_suggestion_autocomplete.js');
		
		if (!empty($options['element'])) {
		  $data['element'] = htmlspecialchars_decode($options['element']);
		}
		
		$css = htmlspecialchars_decode($options['css']);
		foreach ($options['types_order'] as $type => $sort_order) {
			foreach ($options[$type]['fields'] as $field_name => $field_options ) {
				if (!empty($field_options['css'])) {
					$css .= PHP_EOL . '.search-suggestion.' . $type . ' .' . $field_name . ' {' . PHP_EOL . htmlspecialchars_decode($field_options['css']) . PHP_EOL .  '} ';
				}
			}						
		}
					
		if (!empty($options['width'])) {
			$css .= PHP_EOL . '#search .dropdown-menu {' . PHP_EOL . 'width: ' . $options['width'] . ' ; ' . PHP_EOL .  '} ';
		}

		if (!empty($options['color_scheme'])) {
			// convert HEX color (#xxxxxx) to RGB
			list($r, $g, $b) = sscanf(substr($options['color_scheme'], 1), '%2x%2x%2x');
			$css .= PHP_EOL . '.dropdown-menu li.disabled .search-suggestion .title {' . PHP_EOL . 'border-bottom: 2px solid ' . $options['color_scheme'] . '; ' . PHP_EOL .  '} ';
			$css .= PHP_EOL . '.dropdown-menu > .active > a, .dropdown-menu > .active > a:hover, .dropdown-menu > .active > a:focus {' . PHP_EOL . 'background-color: rgba(' . $r . ',' . $g . ',' . $b . ', 0.4)!important; ' . PHP_EOL .  '} ';
			$css .= PHP_EOL . '.search-suggestion .more {' . PHP_EOL . 'background-color: ' . $options['color_scheme'] . '; ' . PHP_EOL .  '} ';
		}
		
		$data['css'] = $css;

    return $this->load->view('extension/module/search_suggestion', $data);		
	}
	
	public function ajax() {
		
		$json = array();
		$data['products'] = array();

		$this->language->load('extension/module/search_suggestion');
		
		$this->load->model('extension/module/search_suggestion');
		$this->load->model('tool/image');
		
		$options = $this->model_extension_module_search_suggestion->getOptions();;

		if (isset($this->request->get['keyword'])) {
			
			$data_search_product = array();

			if (isset($options['product']['search_by']['name'])) {
				$data_search_product['filter_name'] = $this->request->get['keyword'];
			}
			if (isset($options['product']['search_by']['tags'])) {
				$data_search_product['filter_tag'] = $this->request->get['keyword'];
			}
			if (isset($options['product']['search_by']['description'])) {
				$data_search_product['filter_description'] = $this->request->get['keyword'];
			}
			if (isset($options['product']['search_by']['model'])) {
				$data_search_product['filter_model'] = $this->request->get['keyword'];
			}
			if (isset($options['product']['search_by']['sku'])) {
				$data_search_product['filter_sku'] = $this->request->get['keyword'];
			}

			if (isset($this->request->get['category_id'])) {
				$data_search_product['filter_category_id'] = $this->request->get['category_id'];
			}
	
			if (isset($options['product']['order'])) {
				if ($options['product']['order'] == 'rating') {
					$data_search_product['sort'] = 'rating';
				} else if ($options['product']['order'] == 'name') {
					$data_search_product['sort'] = 'pd.name';
				} else if ($options['product']['order'] == 'relevance') {
					$data_search_product['sort'] = 'relevance';
				}
			}
			if (isset($options['product']['order_dir'])) {
				if ($options['product']['order_dir'] == 'asc') {
					$data_search_product['order'] = 'ASC';
				} else if ($options['product']['order_dir'] == 'desc') {
					$data_search_product['order'] = 'DESC';
				}
			}
			if (isset($options['product']['limit'])) {
				$data_search_product['limit'] = $options['product']['limit'];
			}
			$data_search_product['start'] = 0;

			$search_model = 'model_extension_module_search_suggestion';
			// if sort is by relevance and module "search with morphology and relevance" exists
			if ($data_search_product['sort'] == 'relevance') {
				if ($this->config->get('module_search_mr_options')) {
					$this->load->model('extension/module/search_mr');
					$data_search_product['order'] = 'DESC';
					$search_model = 'model_extension_module_search_mr';
				} elseif ($this->config->get('module_search_engine_options')) {
					$this->load->model('extension/module/search_engine');
					$data_search_product['order'] = 'DESC';
					$search_model = 'model_extension_module_search_engine';
				}
			}


			foreach ($options['types_order'] as $type => $sort_order) {
								
				if ($type == 'product') {

					if (empty($options[$type]['status'])) {
						continue;
					}
					
					$this->load->model('extension/module/attributes_to_text');
					$results = $this->$search_model->getProducts($data_search_product);

					$product_total = $this->$search_model->getTotalProducts();

					if ($product_total) {

						if (!empty($options[$type]['title'][$this->config->get('config_language_id')]))	{
							$data['products'][] = array(
								'fields' => array( 
									'title' => array( 
										'title' => $options[$type]['title'][$this->config->get('config_language_id')]
									),
								),
								'href' => ''
							);								
						}
						
						$inline = !empty($options[$type]['inline']) ? 1: 0;

						if (!empty($options[$type]['fields']['stock']['in_stock_statuses'])) {
							$results = $this->addStockStatusId($results);
						}

						foreach ($results as $result) {

							$fields = array();

							$name = $result['name'];

							foreach ($options[$type]['fields'] as $field_name => $field_options ) {

								$fields[$field_name] = '';
								if (isset($field_options['show'])) {
									$fields[$field_name] = array( 
										'location' => $field_options['location'],
										'sort' => $field_options['sort'],
										'column' => isset($field_options['column']) ?  $field_options['column'] : 'center',
										'label' => array (
											'show' => isset($field_options['show_field_name']) ? $field_options['show_field_name'] : 0,
											'label' => $this->language->get($field_name)
										)	
									);

									if ($field_name == 'image') {
										if ($field_options['width'] && $field_options['height']) {
											$width  = $field_options['width'];
											$height = $field_options['height'];
										} else {
											$width  = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
											$height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
										}
										$result['image'] = ($result['image'] == '' || !file_exists(DIR_IMAGE . $result['image'])) ? 'no_image.png' : $result['image'];
										$text =  $this->model_tool_image->resize($result['image'], $width, $height);
									} elseif ($field_name == 'price') {
										$text = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
										if ((float) $result['special']) {
											$fields[$field_name]['special'] = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
										}
									} elseif ($field_name == 'description') {
										$text = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));
										$cut = isset($field_options['cut']) ? $field_options['cut'] : 100;
										$dots = strlen($text) > $cut ? '..' : '';
										$text = utf8_substr($text, 0, $cut) . $dots;
									} elseif ($field_name == 'attributes') {
										$text = $this->model_extension_module_attributes_to_text->getText($result['product_id'], $field_options);
									} elseif ($field_name == 'rating') {
										$rating = (int)$result[$field_name];
										$text = '';
										if ($rating || !empty($field_options['show_empty'])) {
											for ($i=1; $i<=5; $i++) {
												if ($rating < $i) {
													$text .= '<span class="fa fa-stack"><i class="fa fa-star-o fa-stack-1x"></i></span>';
												} else {
													$text .= '<span class="fa fa-stack"><i class="fa fa-star fa-stack-1x"></i><i class="fa fa-star-o fa-stack-1x"></i></span>';
												}
											}
										}
									} elseif ($field_name == 'cart') {
										$cart_code = isset($field_options['code']) ? $field_options['code'] : '<button type="button" onclick="ss_cart_add(\'product_id\', \'minimum\');" class="btn btn-primary btn-sm"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs hidden-sm hidden-md">button_cart</span></button>';
										
										$cart_code = htmlspecialchars_decode($cart_code);
										
										$cart_code = str_replace("product_id", $result['product_id'], $cart_code);
										$cart_code = str_replace("minimum", $result['minimum'] > 0 ? $result['minimum'] : 1, $cart_code);
										$cart_code = str_replace("button_cart", $this->language->get('button_cart'), $cart_code);										
										$text = $cart_code;

									} elseif ($field_name == 'stock') {

                    $fields[$field_name]['quantity_number'] = $result['quantity'];

                    if ($result['quantity'] > 0 && $this->config->get('config_stock_display')) {
                      $fields[$field_name]['class'] = 'in-stock';
                      $text = $result['quantity'];
                    } elseif ($result['quantity'] > 0) {
                      $fields[$field_name]['class'] = 'in-stock';
                      $text = $this->language->get('text_instock');
                    } elseif (!empty($result['stock_status_id']) && !empty($options[$type]['fields']['stock']['in_stock_statuses'])
												&& in_array($result['stock_status_id'], $options[$type]['fields']['stock']['in_stock_statuses'])) {
											$fields[$field_name]['class'] = 'in-stock';
											$text = $result['stock_status'];
										} else {
                      $text = $result['stock_status'];
                      $fields[$field_name]['class'] = 'out-stock';
										}

										if (!empty($result['stock_status_id'])) {
											$fields[$field_name]['class'] .= ' stock-' . $result['stock_status_id'];
										}
										$text = '<span class="value">' . $text . '</span>';
									} else {
										$text = htmlspecialchars_decode($result[$field_name]);
									}

									$fields[$field_name][$field_name] = $text;
								}
							}
							
							$title = '';
							if ($inline && !empty($options[$type]['inline_tooltip'])) {
								$title = $name;
							}

							$data['products'][] = array(
								'fields' => $fields,
								'href' => str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $result['product_id'])),
								'inline' => $inline,
								'title' => $title,
								'type' => $type
							);
						}
					}

					if (!empty($options[$type]['more']) && $product_total > count($data['products'])) {
						$remainder_cnt = $product_total - count($data['products']);
						if ($remainder_cnt > 0) {
							$data['products'][] = array(
								'fields' => array( 
									'more' => array( 
										'more' => $remainder_cnt . $this->language->get('more_results'),										
									),
								),
								'href' => str_replace('&amp;', '&', $this->url->link('product/search', 'search=' . $this->request->get['keyword'])),
								'type' => $type
							);
						}
					}

				} elseif ($type == 'category_filter') {
					if (empty($options[$type]['status'])) {
						continue;
					}
					if ($search_model != 'model_extension_module_search_engine') {
						continue;
					}

					$data_search_filter = $data_search_product;
					
					// get categories for all products
					$filter_category_id = null;
					if (isset($data_search_filter['filter_category_id'])) {
						$filter_category_id = $data_search_filter['filter_category_id'];
						unset($data_search_filter['filter_category_id']);
					}

					$data_search_filter['filter_only_categories'] = true;

					$results = $this->$search_model->getProducts($data_search_filter);

					$sort_method = 'sortName';
					if (isset($options[$type]['order']) && $options[$type]['order'] == 'count') {
							$sort_method = 'sortCount';
					}

					$sort_order = 'ASC';
					if (isset($options[$type]['order_dir']) && $options[$type]['order_dir'] == 'desc') {
						$sort_order = 'DESC';
					}

					$limit = null;
					if (!empty($options[$type]['limit'])) {
						$limit = $options[$type]['limit'];
					}

					usort($results, array($this, $sort_method));

					if ($sort_order == 'DESC') {
						$results = array_reverse($results);
					}

					if ($limit) {
						$results = array_slice($results, 0, $limit);
					}

					if ($results) {

						if (!empty($options[$type]['title'][$this->config->get('config_language_id')]))	{
							$data['products'][] = array(
								'fields' => array( 
									'title' => array( 
										'title' => $options[$type]['title'][$this->config->get('config_language_id')]
									),
								),
								'href' => ''
							);								
						}

						$inline = !empty($options[$type]['inline']) ? 1: 0;

						foreach ($results as $result) {

							$fields = array();

							$name = $result['name'];
							if (!empty($options[$type]['count'])) {
								$name .= " (" . $result['product_count'] . ")";	
							}

							foreach ($options[$type]['fields'] as $field_name => $field_options ) {

								$fields[$field_name] = '';
								if (isset($field_options['show'])) {
									$fields[$field_name] = array( 
										'location' => $field_options['location'],										
										'sort' => $field_options['sort'],
										'label' => array (
											'show' => isset($field_options['show_field_name']) ? $field_options['show_field_name'] : 0,
											'label' => $this->language->get($field_name)
										)	
									);

									if ($field_name == 'image') {
										if ($field_options['width'] && $field_options['height']) {
											$width  = $field_options['width'];
											$height = $field_options['height'];
										} else {
											$width  = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
											$height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
										}
										$result['image'] = ($result['image'] == '' || !file_exists(DIR_IMAGE . $result['image'])) ? 'no_image.png' : $result['image'];
										$text =  $this->model_tool_image->resize($result['image'], $width, $height);
									} elseif ($field_name == 'description') {
										$text = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));
										$cut = isset($field_options['cut']) ? $field_options['cut'] : 100;
										$dots = strlen($text) > $cut ? '..' : '';
										$text = utf8_substr($text, 0, $cut) . $dots;
									} elseif ($field_name == 'name') {
										$text = $name;
									} else {
										$text = htmlspecialchars_decode($result[$field_name]);
									}

									$fields[$field_name][$field_name] = $text;
								}
							}
							
							$active = $filter_category_id && $filter_category_id == $result['category_id'];

							$title = '';
							if ($inline && !empty($options[$type]['inline_tooltip'])) {
								$title = !$active ? $name : $this->language->get('text_remove_filter');
							}

							$data['products'][] = array(
								'fields' => $fields,
								'href' => str_replace('&amp;', '&', $this->url->link('product/search', 'search=' . $this->request->get['keyword'] . '&category_id=' . $result['category_id'])),
								'active' => $active,
								'inline' => $inline,
								'title' => $title,
								'category_id' => !$active ? $result['category_id'] : 0,
								'ajax' => 1,
								'type' => $type,
							);
						}
					}

				} elseif ($type == 'category') {

					if (empty($options[$type]['status'])) {
						continue;
					}
					
					$data_search = array();

					if (!empty($options[$type]['search_by']['name']['status'])) {
						$data_search['filter_name'] = $this->request->get['keyword'];
					}
					if (!empty($options[$type]['search_by']['description']['status'])) {
						$data_search['filter_description'] = $this->request->get['keyword'];
					}
					
					if (isset($options[$type]['order'])) {
						if ($options[$type]['order'] == 'name') {
							$data_search['sort'] = 'cd.name';
						} else if ($options[$type]['order'] == 'relevance') {
							$data_search['sort'] = 'relevance';
						}
					}
					if (isset($options[$type]['order_dir'])) {
						if ($options[$type]['order_dir'] == 'asc') {
							$data_search['order'] = 'ASC';
						} else if ($options[$type]['order_dir'] == 'desc') {
							$data_search['order'] = 'DESC';
						}
					}
					if (isset($options[$type]['limit'])) {
						$data_search['limit'] = $options[$type]['limit'];
					}
					$data_search['start'] = 0;

					$results = $this->model_extension_module_search_suggestion->getCategories($data_search);

					if ($results) {

						if (!empty($options[$type]['title'][$this->config->get('config_language_id')]))	{
							$data['products'][] = array(
								'fields' => array( 
									'title' => array( 
										'title' => $options[$type]['title'][$this->config->get('config_language_id')]
									),
								),
								'href' => ''
							);								
						}

						$inline = !empty($options[$type]['inline']) ? 1: 0;

						foreach ($results as $result) {

							$fields = array();

							$name = $result['name'];

							foreach ($options[$type]['fields'] as $field_name => $field_options ) {

								$fields[$field_name] = '';
								if (isset($field_options['show'])) {
									$fields[$field_name] = array( 
										'location' => $field_options['location'],
										'sort' => $field_options['sort'],
										'label' => array (
											'show' => isset($field_options['show_field_name']) ? $field_options['show_field_name'] : 0,
											'label' => $this->language->get($field_name)
										)	
									);

									if ($field_name == 'image') {
										if ($field_options['width'] && $field_options['height']) {
											$width  = $field_options['width'];
											$height = $field_options['height'];
										} else {
											$width  = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
											$height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
										}
										$result['image'] = ($result['image'] == '' || !file_exists(DIR_IMAGE . $result['image'])) ? 'no_image.png' : $result['image'];
										$text =  $this->model_tool_image->resize($result['image'], $width, $height);
									} elseif ($field_name == 'description') {
										$text = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));
										$cut = isset($field_options['cut']) ? $field_options['cut'] : 100;
										$dots = strlen($text) > $cut ? '..' : '';
										$text = utf8_substr($text, 0, $cut) . $dots;
									} else {
										$text = htmlspecialchars_decode($result[$field_name]);
									}

									$fields[$field_name][$field_name] = $text;
								}
							}

							$title = '';
							if ($inline && !empty($options[$type]['inline_tooltip'])) {
								$title = $name;
							}

							$data['products'][] = array(
								'fields' => $fields,
								'href' => str_replace('&amp;', '&', $this->url->link('product/category', 'path=' . $result['category_id'])),
								'inline' => $inline,
								'title' => $title,
								'type' => $type
							);
						}
					}

				} elseif ($type == 'manufacturer') {

					if (empty($options[$type]['status'])) {
						continue;
					}
					
					$data_search = array();

					if (isset($options[$type]['search_by']['name'])) {
						$data_search['filter_name'] = $this->request->get['keyword'];
					}
					if (isset($options[$type]['order'])) {
						if ($options[$type]['order'] == 'rating') {
							$data_search['sort'] = 'rating';
						} else if ($options[$type]['order'] == 'name') {
							$data_search['sort'] = 'm.name';
						} else if ($options[$type]['order'] == 'relevance') {
							$data_search['sort'] = 'relevance';
						}
					}
					if (isset($options[$type]['order_dir'])) {
						if ($options[$type]['order_dir'] == 'asc') {
							$data_search['order'] = 'ASC';
						} else if ($options[$type]['order_dir'] == 'desc') {
							$data_search['order'] = 'DESC';
						}
					}
					if (isset($options[$type]['limit'])) {
						$data_search['limit'] = $options[$type]['limit'];
					}
					$data_search['start'] = 0;

					$results = $this->model_extension_module_search_suggestion->getManufacturers($data_search);

					if ($results) {

						if (!empty($options[$type]['title'][$this->config->get('config_language_id')]))	{
							$data['products'][] = array(
								'fields' => array( 
									'title' => array( 
										'title' => $options[$type]['title'][$this->config->get('config_language_id')]
									),
								),
								'href' => ''
							);								
						}

						$inline = !empty($options[$type]['inline']) ? 1: 0;

						foreach ($results as $result) {

							$fields = array();

							$name = $result['name'];

							foreach ($options[$type]['fields'] as $field_name => $field_options ) {

								$fields[$field_name] = '';
								if (isset($field_options['show'])) {
									$fields[$field_name] = array( 
										'location' => $field_options['location'],
										'sort' => $field_options['sort'],
										'label' => array (
											'show' => isset($field_options['show_field_name']) ? $field_options['show_field_name'] : 0,
											'label' => $this->language->get($field_name)
										)	
									);

									if ($field_name == 'image') {
										if ($field_options['width'] && $field_options['height']) {
											$width  = $field_options['width'];
											$height = $field_options['height'];
										} else {
											$width  = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
											$height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
										}
										$result['image'] = ($result['image'] == '' || !file_exists(DIR_IMAGE . $result['image'])) ? 'no_image.png' : $result['image'];
										$text =  $this->model_tool_image->resize($result['image'], $width, $height);
									} else {
										$text = htmlspecialchars_decode($result[$field_name]);
									}

									$fields[$field_name][$field_name] = $text;
								}
							}

							$title = '';
							if ($inline && !empty($options[$type]['inline_tooltip'])) {
								$title = $name;
							}

							$data['products'][] = array(
								'fields' => $fields,
								'href' => str_replace('&amp;', '&', $this->url->link('product/manufacturer', 'manufacturer_id=' . $result['manufacturer_id'])),
								'inline' => $inline,
								'title' => $title,
								'type' => $type
							);
						}
					}

				} elseif ($type == 'information') {

					if (empty($options[$type]['status'])) {
						continue;
					}
					
					$data_search = array();

					if (!empty($options[$type]['search_by']['title']['status'])) {
						$data_search['filter_title'] = $this->request->get['keyword'];
					}
					if (!empty($options[$type]['search_by']['description']['status'])) {
						$data_search['filter_description'] = $this->request->get['keyword'];
					}
					if (isset($options[$type]['order'])) {
						if ($options[$type]['order'] == 'tite') {
							$data_search['sort'] = 'id.title';
						} else if ($options[$type]['order'] == 'relevance') {
							$data_search['sort'] = 'relevance';
						}
					}
					if (isset($options[$type]['order_dir'])) {
						if ($options[$type]['order_dir'] == 'asc') {
							$data_search['order'] = 'ASC';
						} else if ($options[$type]['order_dir'] == 'desc') {
							$data_search['order'] = 'DESC';
						}
					}
					if (isset($options[$type]['limit'])) {
						$data_search['limit'] = $options[$type]['limit'];
					}
					$data_search['start'] = 0;

					$results = $this->model_extension_module_search_suggestion->getInformations($data_search);

					if ($results) {

						if (!empty($options[$type]['title'][$this->config->get('config_language_id')]))	{
							$data['products'][] = array(
								'fields' => array( 
									'title' => array( 
										'title' => $options[$type]['title'][$this->config->get('config_language_id')]
									),
								),
								'href' => ''
							);								
						}

						$inline = !empty($options[$type]['inline']) ? 1: 0;

						foreach ($results as $result) {

							$fields = array();

							foreach ($options[$type]['fields'] as $field_name => $field_options ) {

								$fields[$field_name] = '';
								if (isset($field_options['show'])) {
									$fields[$field_name] = array( 
										'location' => $field_options['location'],
										'sort' => $field_options['sort'],
										'label' => array (
											'show' => isset($field_options['show_field_name']) ? $field_options['show_field_name'] : 0,
											'label' => $this->language->get($field_name)
										)	
									);

									if ($field_name == 'description') {
										$text = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));
										$cut = isset($field_options['cut']) ? $field_options['cut'] : 100;
										$dots = strlen($text) > $cut ? '..' : '';
										$text = utf8_substr($text, 0, $cut) . $dots;
									} else {
										$text = htmlspecialchars_decode($result[$field_name]);
									}

									$fields[$field_name][$field_name] = $text;
								}
							}

							$data['products'][] = array(
								'fields' => $fields,
								'href' => str_replace('&amp;', '&', $this->url->link('information/information', 'information_id=' . $result['information_id'])),
								'inline' => $inline,
								'type' => $type
							);
						}
					}

				} 
				
				
					
			}
		}
		
		if (empty($data['products'])) {
			$data['products'][] = array(
				'fields' => array( 
					'no_results' => array( 
						'no_results' => $this->language->get('text_no_result')
					),
				),
				'href' => ''
			);
		}
		
		$this->response->setOutput(json_encode($data['products']));
	}

  private function sortName($a, $b) {
    if (!isset($a['name']) || !isset($b['name'])) {
      return 0;
    }
    return strcmp($a["name"], $b["name"]);
  }

  private function sortCount($a, $b) {
    if (!isset($a['product_count']) || !isset($b['product_count'])) {
      return 0;
    }
    if ($a['product_count'] == $b['product_count']) {
        return 0;
    }

    return ($a['product_count'] < $b['product_count']) ? -1 : 1;
  }

	private function addStockStatusId($results) {
		
		$product_ids = array();
		
		foreach($results as $result) {
			$product_ids[] = $result['product_id'];
		}		
		
		if (!$product_ids) {
			return $results;
		}

		$stock_results = $this->db->query("SELECT product_id, stock_status_id FROM " . DB_PREFIX ."product WHERE product_id IN (" . implode(',', $product_ids) . ")");

		foreach($results as &$result) {
			foreach($stock_results->rows as $stock_result) {
				if ($result['product_id'] == $stock_result['product_id']) {
					$result['stock_status_id'] = $stock_result['stock_status_id'];
					break;
				}
			}
		}	

		return $results;
	}
}
//author sv2109 (sv2109@gmail.com) license for 1 product copy granted for Nawiteh (andrew.pv.mm@gmail.com nawiteh.com.ua,www.nawiteh.com.ua,stage.nawiteh.com.ua)
