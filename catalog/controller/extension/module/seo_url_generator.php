<?php

/**
 * @category   OpenCart
 * @package    SEO URL Generator PRO
 * @copyright  © Serge Tkach, 2018–2024, http://sergetkach.com/
 */
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// for different PHP versions...
if (version_compare(PHP_VERSION, '7.2') >= 0) {
	$php_v = '72_73';
} elseif (version_compare(PHP_VERSION, '7.1') >= 0) {
	$php_v = '71';
} elseif (version_compare(PHP_VERSION, '5.6.0') >= 0) {
	$php_v = '56_70';
} elseif (version_compare(PHP_VERSION, '5.4.0') >= 0) {
	$php_v = '54_56';
} else {
	echo "Sorry! Version for PHP 5.3 Not Supported!<br>Please contact to author!";
	exit;
}

$file = DIR_SYSTEM . 'library/seo_url_generator/seo_url_generator_' . $php_v . '.php';

if (is_file($file)) {
	include $file;
} else {
	echo "No file '$file'<br>";
	exit;
}

class ControllerExtensionModuleSeoURLGenerator extends Controller {
	private $code = 'seo_url_generator';
	private $stde;
	private $stdelog;

	function __construct($registry) {
		parent::__construct($registry);
		
		$this->extension = new SeoUrlGenerator();

    // StdE Require
		// $this->load->library('stde'); autoload
		$this->stde = new StdE($registry);
		$this->registry->set('stde', $this->stde);
		$this->stde->setCode($this->code);
		$this->stde->setType('extension_monolithic');

    // StdeLog require
		$this->stdelog = new StdeLog($this->code);
		$this->registry->set('stdelog', $this->stdelog);
		$this->stdelog->setDebug($this->config->get('module_' . $this->code . '_debug'));
	}

	public function index() {
		// Нужны ли нам логи?
		// Надо же делать репорт
		// А можно даже слать письма на почту, если что-то пошло не так...
		
		// Надо отслеживать максимальное время исполнения скрипта
	
		// Надо определить, как хранить данные от вызова скрипта до следующего вызова по cron
		
		// Надо проходить по всем товарам с 1 до x
		// Что это даст?
		
		// Проверять ли актуализацию названий товаров при переборе? Тогда надо выбирать все товары, а не только с пустыми ЧПУ
		
		return $this->actionMassGenerateURL();
	}
	
	/*
	 * Base method
	 * Это у нас основой метод, который генерирует SEO URL
	 *
	 * Ему все равно, откуда поступают данные о товаре - из формы в админке или из базы при массовом редактировании
	 *
	 * Определить сущность
	 * Определить, какие переменные есть в формуле
	 * Вырезать из формулы лишние - (транслит сам это сделает)
	 * Транлитировать
	 * Запросить уникальность
	 * Если URL не уникален, то использовать индекс N - причем, это не зависит от того, есть ли в формуле генерации доп переменные или нет
	 */

	public function generateSeoUrl($a_data) {
		$this->stdelog->write(2, $a_data, 'generateSeoUrl() is called with $a_data');
		
		$this->load->model('extension/module/' . $this->code);
		
		// Setting
		// Нельзя, чтобы запрос настроек шел при каждой иттерации
		// В 3-ке надо, чтобы в данные этого метода передавались и настройки
		
		$name = $this->model_extension_module_seo_url_generator->essenceNameFilter($a_data['name'], $a_data['essence'], $a_data['setting']);
		
		$this->stdelog->write(4, $name, 'generateSeoUrl() : $name after $model->essenceNameFilter()');

		$keyword = '';
		
		if (isset($a_data['essence']) && $a_data['essence']) {
			if ('category' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data);
			} elseif ('product' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateProductKeyword($a_data);
			} elseif ('manufacturer' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data);
			} elseif ('information' == $a_data['essence']) {
				$keyword = $this->model_extension_module_seo_url_generator->generateOtherSystemsEssenceKeyword($a_data);
			} else {
				$keyword = $this->model_extension_module_seo_url_generator->generateNotSystemsEssenceKeyword($a_data);
			}

			$this->stdelog->write(4, $keyword, 'generateSeoUrl() : BASE WORD');
		} else {
			$this->stdelog->write(1, 'generateSeoUrl() : $a_data["essence"] is empty');
		}

		$keyword = $this->model_extension_module_seo_url_generator->translit($keyword, $a_data['setting']);

		$this->stdelog->write(3, $keyword, 'generateSeoUrl() : $keyword after $model->translit()');

		// Make unique
		if (!$this->model_extension_module_seo_url_generator->isUnique($keyword, $a_data['essence'] . '_id', $a_data['essence_id'], $a_data['store_id'], $a_data['url_language_id'])) {
			$keyword = $this->model_extension_module_seo_url_generator->makeUniqueURL($keyword, $a_data['essence'] . '_id', $a_data['essence_id'], $a_data['store_id'], $a_data['url_language_id']);
			
			$this->stdelog->write(3, $keyword, 'generateSeoUrl() : $keyword was NOT unique. Then I get value from $model->makeUniqueURL()');
		}

		$this->stdelog->write(2, $keyword, 'generateSeoUrl() : return $keyword');
		
		return $keyword;
	}
	

	/*
	------------------------------------------------------------------------------
	Массовая генерация
	------------------------------------------------------------------------------
	*/

	public function actionMassGenerateURL() {
		// Узнаю кол-во итемов в базе сущности всего
		// Определяю лимит итемов за одно обращение (по умолчанию 200 товаров за 1 иттерацию через ajax)
		// Делаю выборку и в цикле присваиваю URL
		// Возращаю порядковый номер товара для начала следующей иттерации

		$this->stdelog->write(2, "\r\n\r\n\r\n\r\n\r\n= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\r\n\r\n"
			. "                                      * * *                                          \r\n\r\n"
			. "               ----- actionMassGenerateURL() : is called BY CRON ------             \r\n\r\n"
			. "                                      * * *                                          \r\n\r\n"
			. "= = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =\r\n\r\n");
		
		$this->stdelog->write(3, $this->request->get, 'actionMassGenerateURL() : $this->request->get');

		$this->load->language('extension/module/' . $this->code);
		$this->load->model('extension/module/' . $this->code);

		### DATA
		$json = array();
		
		$this->stdelog->write(3, $this->config->get('module_seo_url_generator_licence'), 'actionMassGenerateURL() : $this->config->get("module_seo_url_generator_licence")');
		
		if (!$this->extension->isValidLicence($this->config->get('module_seo_url_generator_licence'))) {
			$json['error'] = true;
			$json['answer'] = $this->language->get('error_licence');
		}

		// A!
		// For customized modules $primary_key may be different with key in seo_url.query!!
		// So it is necessary to use $query_key also
		// For example NewsBlog!

		//$step = $this->request->get['step'];
		if (isset($this->request->get['step'])) {
			$step = $this->request->get['step'];
		} else {
			$step = 1;
		}
		//$essence = $this->request->get['essence'];
		if (isset($this->request->get['essence'])) {
			$essence = $this->request->get['essence'];
		} else {
			$essence = 'product';
		}
		$primary_key = $essence . '_id'; // A! for defaults essences
		$query_key = $essence . '_id'; // A! dummy for default essences
		//$generation_type = $this->request->get['generationType'];
		if (isset($this->request->get['generationType'])) {
			$generation_type = $this->request->get['generationType'];
		} else {
			$generation_type = 'empty';
		}
		
		$this->stdelog->write(
			3, 
			"\r\n\actionMassGenerateURL() : vars\r\n$step = $step" . PHP_EOL 
				. "\$essence = $essence" . PHP_EOL 
				. "\$primary_key = $primary_key" . PHP_EOL 
				. "\$generation_type = $generation_type" . PHP_EOL 
				. "------------------------------------------" . PHP_EOL,
			'actionMassGenerateURL() variables'
		);
		
		// For custom_tab
		if (isset($this->request->get['custom_tab']) && 'false' !== $this->request->get['custom_tab']) { // (A!) Value was send with ajax
			$this->stdelog->write(2, 'actionMassGenerateURL() : It has custom_tab');

			$custom_tabs = $this->model_extension_module_seo_url_generator->getCustomTabs();
			$primary_key = $custom_tabs[$essence]['primary_key']; // A! for custom essences
			$query_key = $custom_tabs[$essence]['query_key']; // A!

			$this->stdelog->write(3, $custom_tabs, 'actionMassGenerateURL() : $custom_tabs');
		}
		
		### SETTING		
		$this->load->model('setting/store');
		
		$data['stores'] = array();

		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->config->get('config_name') . $this->language->get('text_default'),
		);

		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$data['stores'][] = array(
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
			);
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('setting/setting');
		
		$data['store_setting'] = array();
		
		foreach ($data['stores'] as $store) {
			$store_setting = $this->model_setting_setting->getSetting('module_' . $this->code, $store['store_id']);

			if (in_array($essence, array('category', 'product', 'manufacturer', 'information'))) {
				$data['store_setting'][$store['store_id']]['formula'] = $store_setting['module_seo_url_generator_' . $essence . '_formula'];
			} else {
				$this->stdelog->write(1, 'actionMassGenerateURL() : is not system essence');

				foreach ($data['languages'] as $language) {
					$data['store_setting'][$store['store_id']]['formula'][$language['language_id']] = false;
				}
			}

			$data['store_setting'][$store['store_id']]['language'] = $store_setting['module_seo_url_generator_language'];
			$data['store_setting'][$store['store_id']]['translit_function'] = $store_setting['module_seo_url_generator_translit_function'];
			$data['store_setting'][$store['store_id']]['delimiter_char'] = $store_setting['module_seo_url_generator_delimiter_char'];
			$data['store_setting'][$store['store_id']]['change_delimiter_char'] = $store_setting['module_seo_url_generator_change_delimiter_char'];
			$data['store_setting'][$store['store_id']]['custom_replace_from'] = $store_setting['module_seo_url_generator_custom_replace_from'];
			$data['store_setting'][$store['store_id']]['custom_replace_to'] = $store_setting['module_seo_url_generator_custom_replace_to'];
		}

		// limit
		//$limit_n = $this->config->get('module_seo_url_generator_limit');
		$limit_n = 10000;
		if (isset($this->request->get['limit'])) {
			$limit_n = $this->request->get['limit'];
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = "DESC";
		}
		
		### ACTION
		$n_essence_items = $this->model_extension_module_seo_url_generator->countEssenceItems($essence);

		if (false === $n_essence_items) {
			$this->stdelog->write(1, $n_essence_items, 'actionMassGenerateURL() : $n_essence_items is false - goto block_finish;');

			$json['error'] = true;
			$json['answer'] = sprintf($this->language->get('answer_step_item_error'), $step, $steps_all) . $this->language->get('error_steps_no_count');
			goto block_finish;
		}

		$steps_all = ceil($n_essence_items / $limit_n);

		$limits = array(
			'first_element'=>$limit_n * $step - $limit_n,
			'limit_n'=>$limit_n
		);

		$this->stdelog->write(
			3, 
			"\r\n\$n_essence_items = $n_essence_items" . PHP_EOL 
				. "\$steps_all = $steps_all" . PHP_EOL 
				. "\$first_element = " . ($limit_n * $step - $limit_n) . PHP_EOL 
				. "\$limit_n = $limit_n" . PHP_EOL 
				. "------------------------------------------" . PHP_EOL,
			'actionMassGenerateURL() variables'
		);

		$essence_list = $this->model_extension_module_seo_url_generator->getEssenceList($essence, $limits, $order);

		$this->stdelog->write(3, count($essence_list), 'actionMassGenerateURL() : quantity of items for foreach');

		if (count($essence_list) > 0) {

			foreach ($essence_list as $essence_item) {
				$this->stdelog->write(2, "\r\n\r\n\r\n\r\n                                      + + +                                         \r\n\r\n"
					. "------------------------------------------------------------------------------------\r\n\r\n"
					. "----- actionMassGenerateURL() : NEW ITTERATION ------\r\n\r\n"
					. "------------------------------------------------------------------------------------\r\n"
				);
				
				$essence_id = $essence_item[$primary_key];
				
				$this->stdelog->write(2, $essence_id, 'actionMassGenerateURL() : ' . $essence . ' with ' . $primary_key);
				
				$keywords_old_not_all_are_present = false;
				
				$keywords_old = $this->model_extension_module_seo_url_generator->getURLs($query_key, $essence_id);
				
				foreach ($data['stores'] as $store) {
					foreach ($data['languages'] as $language) {
						if (!isset($keywords_old[$store['store_id']][$language['language_id']])) {
							$keywords_old_not_all_are_present = true;
							$keywords_old[$store['store_id']][$language['language_id']] = false;
						}						
					}
				}
				
				$this->stdelog->write(3, $keywords_old, 'actionMassGenerateURL() : $keywords_old array with all keys');

				if ($keywords_old_not_all_are_present || 'replace' == $generation_type) {
					// Default
					$a_data = array(
						'essence'=>$essence,
						'essence_id'=>$essence_id
					);
					
					// Names with all languages
					$names = array();
					
					// $data['languages'] - it is neccessary for manufacturer dummy data
					$names = $this->model_extension_module_seo_url_generator->getEssenceNames($essence, $primary_key, $essence_id, $data['languages']);
					
					$this->stdelog->write(3, $names, 'actionMassGenerateURL() : $names');

					if (count($names) < 1) {
						$this->stdelog->write(1, $names, 'actionMassGenerateURL() : $names is not valid array');
						continue;
					}
										
					// $names content check below in foreach
					
					
					// Product
					if ('product' == $essence) {
						// It is necessary to get these data only once a essence itteration . Begin						
						$product_data = $this->model_extension_module_seo_url_generator->getProductData($essence_id);
						
						// if [manufacturer_name] is used in even one formula
						$use_manufacturer_name = false;
						
						foreach ($data['stores'] as $store) {
							foreach ($data['languages'] as $language) {
								if (false !== strstr($data['store_setting'][$store['store_id']]['formula'][$language['language_id']], '[manufacturer_name]')) {
									$use_manufacturer_name = true;
									break;
								}
							}
						}
						
						if ($use_manufacturer_name) {
							$product_data['manufacturer_name'] = $this->model_extension_module_seo_url_generator->getManufacturerNameById($product_data['manufacturer_id']);

							$this->stdelog->write(4, $product_data['manufacturer_name'], 'actionMassGenerateURL() : $use_manufacturer_name == true; $product_data["manufacturer_name"] value is');
						}
						
						$a_data = array_merge($a_data, $product_data);
						// It is necessary to get these data ony once a essence itteration . End
					}
					
					// Stores & Languages Itterations . Begin
					foreach ($data['stores'] as $store) {
						foreach ($data['languages'] as $language) {						
							$keyword_old = $keywords_old[$store['store_id']][$language['language_id']];
							
							/*
							 * Дано
							 * $keyword_old = en_mac
							 * $keyword_new = mac
							 * 
							 * $keywords_old_not_all_are_present
							 * Что будет при сравнении с разделителями и актуализацией? Все равно будерт перезапись?..
							 * Сама актуализация нужна для редактирования товара вручную, а не при массовой генерации
							 * При массовой генерации человек может нажать осознанно на оранжевую кнопку!!!
							 */
							
							if ('replace' != $generation_type && $keyword_old) {
								$this->stdelog->write(2, $keyword_old, 'actionMassGenerateURL() : $keyword_old already exist for ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
								continue;
							}
							
							
							
							// $names content check
							// Check if all languages has name value
							// Q?
							// А если формула генерации не содержит названия? :)
							// TODO... (OMG...)
							
							if (!isset($names[$language['language_id']])) {			
								$this->stdelog->write(1, 'actionMassGenerateURL() : ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] . ' doesn\'t have name value so it is impossible to generate SEO URL');
								// Write to common log also
								$this->log->write('ERROR -- SEO URL Generator: actionMassGenerateURL() :: ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] .   ' doesn\'t have name value so it is impossible to generate SEO URL');
								continue;
							}
								
							if (empty(trim($names[$language['language_id']]))) {
								$this->stdelog->write(1, 'actionMassGenerateURL() : ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']. ' has empty value of name so it is impossible to generate SEO URL');
								// Write to common log also
								$this->log->write('ERROR -- SEO URL Generator: actionMassGenerateURL() :: ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] . ' has empty value of name so it is impossible to generate SEO URL');
								continue;
							}
							
							
							
							$a_data['name']						 = $names[$data['store_setting'][$store['store_id']]['language'][$language['language_id']]];
							$a_data['store_id']				 = $store['store_id'];
							$a_data['url_language_id'] = $language['language_id'];

							$a_data['setting'] = array(
								'language'              =>$data['store_setting'][$store['store_id']]['language'][$language['language_id']],
								'translit_function'     =>$data['store_setting'][$store['store_id']]['translit_function'][$language['language_id']],
								'formula'               =>$data['store_setting'][$store['store_id']]['formula'][$language['language_id']],
								'delimiter_char'        =>$data['store_setting'][$store['store_id']]['delimiter_char'][$language['language_id']],
								'change_delimiter_char' =>$data['store_setting'][$store['store_id']]['change_delimiter_char'][$language['language_id']],
								'custom_replace_from'   =>$data['store_setting'][$store['store_id']]['custom_replace_from'][$language['language_id']],
								'custom_replace_to'     =>$data['store_setting'][$store['store_id']]['custom_replace_to'][$language['language_id']],
							);

							$keyword_new = $this->generateSeoUrl($a_data);
							
							if (!$keyword_new) {
								$this->stdelog->write(1, 'actionMassGenerateURL() : $keyword_new is not generated for ' . $essence . ' with ' . $primary_key . ' ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
								continue;
							}

							// Тут однозначно записываем новый ЧПУ в базу
							if (!$keyword_old) {								
								$this->model_extension_module_seo_url_generator->setURL($query_key, $a_data['essence_id'], $keyword_new, $store['store_id'], $language['language_id']);
								$this->stdelog->write(2, $keyword_new, 'actionMassGenerateURL() : $keyword_new is added for ' . $primary_key . ' : ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
								continue;
							}

							// Далее блок кода касается только случаев, когда 'replace' == $generation_type
							// Если пользователь осознанно уже выбрал replace, то актуализация и настройки разделителей вообще ни при чем!!
								if ($keyword_old != $keyword_new) {
									$this->model_extension_module_seo_url_generator->deleteURL($query_key, $essence_id, $store['store_id'], $language['language_id']);
									$this->model_extension_module_seo_url_generator->setURL($query_key, $a_data['essence_id'], $keyword_new, $store['store_id'], $language['language_id']);
                  
                  // Why should I redirect? I process freshly added products that even not to get into the index ...
									// $this->model_extension_module_seo_url_generator->setRedirect($keyword_new, $keyword_old, $query_key, $a_data['essence_id'], $store['store_id'], $language['language_id']);

								$this->stdelog->write(2, 'actionMassGenerateURL() : write redirect for ' . $primary_key . ' : ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id'] . ' :: ' . $keyword_old . ' => ' . $keyword_new);

						} else {
              $this->stdelog->write(2, 'actionMassGenerateURL() : $keyword_new is the same as $keyword_old for ' . $primary_key . ' : ' . $essence_id . ' - store_id ' . $store['store_id'] . ' - language_id ' . $language['language_id']);
            continue;
              }
            }
					}
					// Stores & Languages Itterations . End
					
				} else {
					$this->stdelog->write(2, 'actionMassGenerateURL() : Nothing to do.');
				}
			}
		} else {
			$this->stdelog->write(1, 'actionMassGenerateURL() : count($essence_list) < 0');
		}

		if (!isset($json['error'])) {
			// success

			if ($step == $steps_all) {
				// todo...
				// $json['answer'] = $this->language->get('success_item_step_finish'); - поставить финальную запись
				$json['answer'] = sprintf($this->language->get('answer_step_item_success'), $step, $steps_all);

				// is different for OC 3
				if ($this->config->get('config_seo_pro')) {
					$this->cache->delete('seopro');
				}
			} else {
				$json['answer'] = sprintf($this->language->get('answer_step_item_success'), $step, $steps_all);
			}
			
		} else {
			// error
			// todo - !!
			$this->stdelog->write(1, 'actionMassGenerateURL() : error marker is present');
			
		}

		block_finish:

		$json['step'] = $step ++;
		$json['steps_all'] = $steps_all;

		$this->stdelog->write(3, $json, 'actionMassGenerateURL() : $json before json_encode()');

		$this->response->addHeader('Content-type: application/json; charset=UTF-8');
		$this->response->setOutput(json_encode($json));
	}
}