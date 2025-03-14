<?php

/************************copyright***************************/
/*                                                          */
/* telegram: https://t.me/PrutNikolay                       */
/* forum: https://opencartforum.com/profile/18336-exploits/ */
/* email: info@microdata.pro                                */
/* site: https://microdata.pro                              */
/*                                                          */
/************************copyright***************************/

require_once(DIR_SYSTEM . 'library/microdatapro.php');
class ControllerExtensionModuleMicrodataPro extends Controller {

	public function __construct($registry) {
		parent::__construct($registry);
		$this->mdp = new Microdatapro($this->registry);
	}

	public function install() {
		$this->mdp->init();
	}

	//index
		public function index() {
			$this->install();
			$data = $this->mdp->lang;

			$this->load->model('tool/image');
			$this->load->model('setting/setting');
			$this->load->model('catalog/attribute');
			$this->load->model('localisation/stock_status');
			$this->document->setTitle($data['heading_title']);

			$data['a'] 					 = $this->mdp->a;
			$data['link_main'] 	 = HTTPS_CATALOG;
			$data['message'] 		 = $this->mdp->message;
			$data['href_old'] 	 = $this->mdp->path;
			$data['token_name']  = $this->mdp->token();
			$data['token'] 			 = $this->session->data[$data['token_name']];
			$data['site_url'] 	 = $this->mdp->info('site');
			$data['success'] 		 = isset($this->request->get['success'])?true:false;
			$data['version'] 		 = $this->mdp->ver;
			$data['email'] 			 = $this->mdp->info('email');
			$data['store_name']  = $this->config->get('config_name');
			$data['cron_link'] 	 = HTTPS_CATALOG . 'index.php?route=' . $this->mdp->path . '/cron&key=';
			$data['lhref'] 			 = "https://microdata.pro/status/?module=microdatapro&domain=" . $this->mdp->info('site');
			$data['action'] 		 = $this->url->link($this->mdp->path . '&success=1', $data['token_name'] . '=' . $data['token'], 'SSL');
			$data['cancel'] 		 = $this->url->link($this->mdp->extension, $data['token_name'] . '=' . $data['token'], 'SSL');
			$data['refresh'] 		 = $this->url->link(((substr(VERSION, 0, 1) == 3)?$this->mdp->refresh:'extension/modification/refresh'), $data['token_name'] . '=' . $data['token'], 'SSL');

			if ($this->request->server['REQUEST_METHOD'] == 'POST') {

				foreach($this->request->post as $post_key => $post_data){
					unset($this->request->post[$post_key]);
					$this->request->post[str_replace('microdatapro_', $this->mdp->key() . '_', $post_key)] = $post_data;
				}

				$this->request->post[$this->mdp->key() . '_' . 'front_stores'] = $this->mdp->allstores();

				$this->model_setting_setting->editSetting($this->mdp->key(), $this->request->post);
				$this->response->redirect($this->url->link($this->mdp->path . (isset($this->request->get['success'])?"&success=1":""), $data['token_name'] . '=' . $data['token'], 'SSL'));
			}

			$this->mdp->breadcrumbs($data);
			$this->mdp->vars($data);
			$this->mdp->links($data);
			$this->mdp->sss($data);
			$this->mdp->types($data);
			$this->mdp->stores($data);
			$this->mdp->diagnost($data);

			foreach($data as $data_key => $data_value){
				unset($data[$data_key]);
				$data[str_replace($this->mdp->key() . '_', 'microdatapro_', $data_key)] = $data_value;
			}

			$data['stock_statuses'] =  $this->model_localisation_stock_status->getStockStatuses();
			foreach($data['stock_statuses'] as $stock_status_key => $stock_status_item){
				$data['stock_statuses'][$stock_status_key]['active'] = isset($data['microdatapro_stock_statuses'][$stock_status_item['stock_status_id']])?$data['microdatapro_stock_statuses'][$stock_status_item['stock_status_id']]:'';
			}

			$data['placeholder'] 	 = $this->model_tool_image->resize('no_image.png', 100, 100);
			$data['logoalt_thumb'] = $this->model_tool_image->resize((is_file(DIR_IMAGE . $data['microdatapro_logoalt']))?$data['microdatapro_logoalt']:'no_image.png', 100, 100);
			$data['attributes']    = $this->model_catalog_attribute->getAttributes(array('start'=>0,'limit'=>999,'sort'=>'ad.name','order'=>'ASC'));
			$data['header'] 		   = $this->load->controller('common/header');
			$data['footer'] 		   = $this->load->controller('common/footer');
			$data['column_left']   = $this->load->controller('common/column_left');

			$this->response->setOutput($this->loadView($this->mdp->path, $data));
		}
	//index

	//addKey
		public function addKey(){
			if(isset($this->request->post['txt']) && $this->request->post['txt'] && $this->mdp->status($this->request->post['txt'])){
				$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($this->request->post['txt']) . "' WHERE `key` = '" . $this->mdp->key() . "_key'");
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode(1));
			}
			return false;
		}
	//addKey

	//clear_old
		public function clear_old(){
			$find_files_data = $this->mdp->find_old(1);
			$find_files = $find_files_data[0];
			$find_tags = $find_files_data[1];
			if($find_files){
				$this->log->write("============================================================");
				$this->log->write("MicrodataPro " . $this->mdp->info('version') . " начало очистки шаблона от старых элементов разметки");
				foreach($find_files as $item){
					$file_html = file_get_contents($item);
					$file_html = preg_replace('/<meta property=(|"|\')og:(.*?)\/>/im', "", $file_html); //clear og:
					$file_data = str_ireplace($find_tags, "", $file_html);
					rename($item, $item."_mdb");
					$fp = fopen($item, "w");
					fwrite($fp, $file_data);
					fclose($fp);
					$this->log->write("microdatapro очищенный файл: " . $item);
					$this->log->write("microdatapro оригинальный файл: " . $item . "_mdb");
				}
				$this->log->write("MicrodataPro " . $this->mdp->info('version') . " завершение чистки шаблона, всего очищено (" . count($find_files) . ") файлов");
				$this->log->write("============================================================");
			}

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(count($find_files)));
		}
	//clear_old

	//loadView
    private function loadView($route, $data){
      if(substr(VERSION, 0, 1) == 3){
				$delete_old_twig = array(
					DIR_TEMPLATE . $this->mdp->path . '.twig',
					DIR_CATALOG . 'view/theme/default/template/' . $this->mdp->path . '/tc_og.twig',
					DIR_CATALOG . 'view/theme/default/template/' . $this->mdp->path . '/company.twig',
					DIR_CATALOG . 'view/theme/default/template/' . $this->mdp->path . '/product.twig',
					DIR_CATALOG . 'view/theme/default/template/' . $this->mdp->path . '/category_manufacturer.twig',
					DIR_CATALOG . 'view/theme/default/template/' . $this->mdp->path . '/information.twig',
				);

				foreach($delete_old_twig as $delete_file){
					if(is_file($delete_file)){
	          unlink($delete_file);
	        }
				}

        $this->registry->get('config')->set('template_engine', 'template'); //go to tpl
      }
      if(substr(VERSION, 0, 3) < 2.3){
        $route .= '.tpl';
      }else{
        if(is_file(DIR_APPLICATION . 'controller/module/microdatapro.php')){
          unlink(DIR_APPLICATION . 'controller/module/microdatapro.php');
        }
      }
      return $this->load->view($route, $data);
    }
  //loadView

}
