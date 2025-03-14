<?php

/************************copyright***************************/
/*                                                          */
/* telegram: https://t.me/PrutNikolay                       */
/* forum: https://opencartforum.com/profile/18336-exploits/ */
/* email: info@microdata.pro                                */
/* site: https://microdata.pro                              */
/*                                                          */
/************************copyright***************************/

class MicrodataPro {

	public $ver = '8.1';
	public $path = 'extension/module/microdatapro';
	public $extension = 'marketplace/extension';
	public $refresh = 'marketplace/modification/refresh';
	public $datfile = '/microdatapro.min.max.dat';
	public $a = 0;
	public $message = '';
	public $lang = '';

	public function __construct($registry) {
		$this->request = $registry->get('request');
		$this->config = $registry->get('config');
		$this->currency = $registry->get('currency');
		$this->tax = $registry->get('tax');
		$this->session = $registry->get('session');
		$this->db = $registry->get('db');
		$this->url = $registry->get('url');
		$this->language = $registry->get('language');
	}

	//token
		public function token() {
	    return isset($this->request->get['token'])?'token':'user_token';
	  }
	//token

	//info
		public function info($key = false){
			$dmn = explode("//", defined('HTTP_CATALOG')?HTTP_CATALOG:HTTP_SERVER);
			$information = array(
				'site'		  => str_replace(array('www.','/'),'',$dmn[1]),
				'date'		  => date("Y-m-d H:i:s"),
				'lang'		  => $this->config->get('config_language'),
				'email'		  => $this->config->get('config_email'),
				'engine' 	  => VERSION,
				'module' 	  => strtolower(get_class()),
				'version'   => $this->ver,
				'sec_token' => "3274507573",
			);

			return $key?$information[$key]:$information;
		}
	//info

	//key
		public function key(){
			$key = 'microdatapro';
			if(substr(VERSION, 0, 1) == 3){ //oc3x
				$key = 'module_' . $key;
			}

			return $key;
		}
	//key

	//init
		public function init() {
			$this->status();
			$this->language();

			if(!$this->a){
				$txt = $this->auto();
				if($txt){
					$txt_data = explode("::", $txt);
					if($txt_data[0] == 'Key' && isset($txt_data[1]) && $txt_data[1]){
						$this->a = $txt_data[1];
						$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($this->a) . "', serialized = '0'  WHERE `" . (((int)VERSION >= 2)?'code':'group') . "` = '" . $this->key() . "' AND `key` = '" . $this->key() . "_key' AND store_id = '0'");
					}else{
						if(isset($txt_data[1])){
							$this->message = $txt_data[1];
						}
					}
					if(!$this->message){
						$this->message = $this->lang['text_no_active'];
					}
				}else{
					$this->message = $this->lang['text_no_active'];
				}
			}

			$lang_dir = $this->config->get('config_language');
			$query = $this->db->query("SELECT directory FROM " . DB_PREFIX . "language WHERE code = '" . $this->db->escape($this->config->get('config_language')) . "'");
			if($query->row['directory']){
				$lang_dir = $query->row['directory'];
			}

			$lang_filename = DIR_LANGUAGE . $lang_dir . '/' . $this->path . '.php';
			$lang_filepath = str_replace('/microdatapro.php', '', $lang_filename);
			$lang_filedata = '<?php $_[\'heading_title\'] = \'<span style="color:#00b32d;">Microdata PRO</span> - v <strong>' . $this->ver . '</strong>\';';

			if(is_file($lang_filename)){
				unlink($lang_filename);
			}

			if(!is_dir($lang_filepath)){
				mkdir($lang_filepath, 0777, true);
			}

			$lang_file = fopen($lang_filename, 'w');
			fwrite($lang_file, $lang_filedata);
			fclose($lang_file);
		}
	//init

	//status
		public function status($txt_manual = false){
			$txt = $txt_manual?$txt_manual:$this->check();

			if($txt){
				$key_array = explode("327450", base64_decode(strrev(substr($txt, 0, -7))));
				if($key_array[0] == base64_encode($this->info('site')) && $key_array[1] == base64_encode('3274507573')){
					$this->a = 1;
				}
			}
			if($txt_manual){
				return $this->a;
			}
		}
	//status

	//check
		public function check(){
			$txt = '';
			$key_query = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = '" . $this->key() . "_key'");
			if(!$key_query->num_rows){
				$this->db->query("INSERT INTO " . DB_PREFIX . "setting SET `value` = '', serialized = '0', `" . (((int)VERSION >= 2)?'code':'group') . "` = '" . $this->key() . "', `key` = '" . $this->key() . "_key', store_id = '0'");
			}else{
				$txt = $key_query->row['value'];
			}

			return $txt;
		}
	//check

	//auto
		public function auto(){
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "https://microdata.pro/index.php?route=sale/sale");
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->info());
			$auto = curl_exec($curl);
			curl_close($curl);

			return $auto;
		}
	//auto

	//sss
		public function sss(&$data){
			$data['schema_stock_statuses'] =  array(
				'InStock' => 'InStock - ' . $this->lang['text_in_stock'],
				'OutOfStock' => 'OutOfStock - ' . $this->lang['OutOfStock'],
				'LimitedAvailability' => 'LimitedAvailability - ' . $this->lang['LimitedAvailability'],
				'InStoreOnly' => 'InStoreOnly - ' . $this->lang['InStoreOnly'],
				'OnlineOnly' => 'OnlineOnly - ' . $this->lang['OnlineOnly'],
				'PreOrder' => 'PreOrder - ' . $this->lang['PreOrder'],
				'PreSale' => 'PreSale - ' . $this->lang['PreSale'],
				'Discontinued' => 'Discontinued - ' . $this->lang['Discontinued'],
				'SoldOut' => 'SoldOut - ' . $this->lang['SoldOut'],
			);
		}
	//sss

	//vars
		public function vars(&$data){
			$vars = array(
				'key',
				'status',
				'opengraph',
				'opengraph_meta',
				'twitter_account',
				'company',
				'company_type',
				'store_type',
				'hcard',
				'company_syntax',
				'email',
				'oh_1',
				'oh_2',
				'oh_3',
				'oh_4',
				'oh_5',
				'oh_6',
				'oh_7',
				'phones',
				'groups',
				'locations',
				'map',
				'product',
				'product_syntax',
				'product_breadcrumb',
				'product_gallery',
				'hide_price',
				'sku',
				'upc',
				'ean',
				'mpn',
				'isbn',
				'product_reviews',
				'product_related',
				'product_attribute',
				'product_in_stock',
				'stock_statuses',
				'category',
				'category_syntax',
				'category_range',
				'category_review',
				'category_gallery',
				'manufacturer',
				'manufacturer_syntax',
				'information',
				'information_syntax',
				'age_group',
				'target_gender',
				'profile_id',
				'attr_color',
				'attr_material',
				'attr_size',
				'logosize_1',
				'logosize_2',
				'category_thumb_1',
				'category_thumb_2',
				'category_popup_1',
				'category_popup_2',
				'cron_key',
				'category_list',
				'logoalt',
				'shipping_cost',
				'shipping_country',
				'shipping_time1_min',
				'shipping_time1_max',
				'shipping_time2_min',
				'shipping_time2_max',
				'return_days',
			);

			//add multistore vars
			foreach ($this->allstores() as $result) {
				$vars[] = 'phones'.$result['store_id'];
				$vars[] = 'groups'.$result['store_id'];
				$vars[] = 'locations'.$result['store_id'];
				$vars[] = 'map'.$result['store_id'];
			}

			foreach($vars as $var){
				if (isset($this->request->post[$var])) {
					$data[$this->key() . '_'  . $var] = $this->request->post[$this->key() . '_'  . $var];
				} else {
					$data[$this->key() . '_'  . $var] = $this->config->get($this->key() . '_'  . $var);
				}
			}
		}
	//vars

	//links
		public function links(&$data){
			$data['link_product'] 		 = false;
			$data['link_category'] 		 = false;
			$data['link_information']  = false;
			$data['link_manufacturer'] = false;

			$category_query = $this->db->query("SELECT category_id FROM " . DB_PREFIX ."category WHERE status = 1 LIMIT 0,1");
			if($category_query->num_rows){
				$data['link_category'] = HTTPS_CATALOG . 'index.php%3Froute=product/category%26path%3D' . $category_query->row['category_id'];
			}

			$product_query = $this->db->query("SELECT product_id FROM " . DB_PREFIX ."product WHERE status = 1 LIMIT 0,1");
			if($product_query->num_rows){
				$data['link_product'] = HTTPS_CATALOG . 'index.php%3Froute=product/product%26product_id%3D' . $product_query->row['product_id'];
			}

			$manufacturer_query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX ."manufacturer ORDER BY manufacturer_id DESC LIMIT 0,1");
			if($manufacturer_query->num_rows){
				$data['link_manufacturer'] = HTTPS_CATALOG . 'index.php%3Froute=product/manufacturer/info%26manufacturer_id%3D' . $manufacturer_query->row['manufacturer_id'];
			}

			$information_query = $this->db->query("SELECT information_id FROM " . DB_PREFIX ."information WHERE status = 1 LIMIT 0,1");
			if($information_query->num_rows){
				$data['link_information'] = HTTPS_CATALOG . 'index.php%3Froute=information/information%26information_id%3D' . $information_query->row['information_id'];
			}
		}
	//links

	//types
		public function types(&$data){
			$data['store_types'] = array();
			for($i=0; $i<30; $i++){
				$data['store_types'][$i] = array(
					'id' => $i + 1,
					'name' => $this->lang['text_storetype_' . $i]
				);
			}
		}
	//types

	//breadcrumbs
		public function breadcrumbs(&$data){
			$data['breadcrumbs'] = array();
			$link_module = ((substr(VERSION, 0, 1) == 3 || substr(VERSION, 0, 3) < 2.3)?$this->extension:'extension/extension');
			foreach(array($this->language->get('text_home') => 'common/dashboard', $data['text_module'] => $link_module, $data['heading_title'] => $this->path) as $btext => $blink){
				$data['breadcrumbs'][] = array(
					'text' => $btext,
					'href' => $this->url->link($blink, $data['token_name'] . '=' . $data['token'], 'SSL')
				);
			}
		}
	//breadcrumbs

	//stores
		public function stores(&$data){
			$data['stores'] = array();
			foreach ($this->allstores() as $result) {
				$data['stores'][] = array(
					'store_id' => $result['store_id'],
					'name'     => $result['name'],
					$this->key() . '_' . 'phones' => $data[$this->key() . '_' . 'phones'.$result['store_id']],
					$this->key() . '_' . 'groups' => $data[$this->key() . '_' . 'groups'.$result['store_id']],
					$this->key() . '_' . 'locations' => $data[$this->key() . '_' . 'locations'.$result['store_id']],
					$this->key() . '_' . 'map' => $data[$this->key() . '_' . 'map'.$result['store_id']]
				);
			}
		}
	//stores

	//diagnost
		public function diagnost(&$data){
			$data['count_errors']  = 0;
			$data['old_microdata'] = $this->find_old();
			$data['mod_files'] 		 = $this->mod_files();
			$data['mod_errors'] 	 = $this->mod_files(1);
			$data['other_modules'] = $this->find_other();
			$data['old_count'] 		 = count($data['old_microdata']);

			if($data['old_microdata']) $data['count_errors']++;
			if($data['mod_errors']) 	 $data['count_errors']++;
			if($data['other_modules']) $data['count_errors']++;
		}
	//diagnost

	//theme_dir
		public function theme_dir() {
			$theme_dir = $this->config->get((substr(VERSION, 0, 3) < 2.2)?'config_template':'config_theme');
			return str_replace('theme_default', 'default', $theme_dir);
		}
	//theme_dir

	//allstores
		public function allstores(){
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store ORDER BY url");

			return $query->rows;
		}
	//allstores

	//find_old
		public function find_old($original = false) {
			$old_microdata = array();
			$all_variants = array();

			foreach($this->getModFiles() as $file => $string){
				$file_full = str_replace("/system/", "/", DIR_SYSTEM) . str_replace("{theme}", $this->theme_dir(), $file);
				$file_content = "";
				if(is_file($file_full)){
				  $file_content = @file_get_contents($file_full);
				}
				$file = str_replace("{theme}", $this->theme_dir(), $file);

				foreach($this->find_old_list() as $tag){
					$variants = array(
						$tag,
						str_replace("http", "https", $tag),
						str_replace("=", " = ", $tag),
						str_replace('"', "'", $tag),
						str_replace('"', "'", str_replace("http", "https", $tag)),
					);
					foreach($variants as $variant){
						$all_variants[] = $variant;
						if (stripos($file_content, $variant)){
							if($original){
								$file = $file_full;
							}
							$old_microdata[$file] = $file;
						}
					}
				}
			}

			foreach($this->getMoreFiles() as $file => $string){
				$file_full = str_replace("/system/", "/", DIR_SYSTEM) . str_replace("{theme}", $this->theme_dir(), $file);
				$file_content = "";
				if(is_file($file_full)){
				  $file_content = @file_get_contents($file_full);
				}
				$file = str_replace("{theme}", $this->theme_dir(), $file);

				foreach($this->find_old_list() as $tag){
					$variants = array(
						$tag,
						str_replace("http", "https", $tag),
						str_replace("=", " = ", $tag),
						str_replace('"', "'", $tag),
						str_replace('"', "'", str_replace("http", "https", $tag)),
					);
					foreach($variants as $variant){
						$all_variants[] = $variant;
						if (stripos($file_content, $variant)){
							if($original){
								$file = $file_full;
							}
							$old_microdata[$file] = $file;
						}
					}
				}
			}

			if(!$original){
				return $old_microdata;
			}else{
				return array($old_microdata, $all_variants);
			}
		}
	//find_old

	//mod_files
		public function mod_files($key = 0) {
			$mod_files = array();

			$all_modified_files = $this->getModFiles();
			$mod_errors = count($all_modified_files)*2;

			foreach($all_modified_files as $file => $strings){
				$file = str_replace("{theme}", $this->theme_dir(), $file);

				foreach($strings as $string){
					$string = str_replace("&&&", "$", $string);

					$file_full = str_replace("/system/", "/", DIR_SYSTEM) . str_replace("{theme}", $this->theme_dir(), $file);
					$file_ocmod = DIR_MODIFICATION . str_replace("{theme}", $this->theme_dir(), $file);

					//fix
					if(!is_file($file_full)){
						$file_full = str_replace($this->theme_dir(), 'default', $file_full);
					}
					if(!is_file($file_ocmod)){
						$file_ocmod = str_replace($this->theme_dir(), 'default', $file_ocmod);
					}
					//fix

					if (strpos(file_get_contents($file_full), $string)){ //если есть строка для привязки
						$mod_errors--;
						$mod_files[$file] = array(
							'string' => $string,
							'status' => true,
						);
						$file_ocmod_content = @file_get_contents($file_ocmod);

						if (strpos($file_ocmod_content, $string) && (strpos($file_ocmod_content, "//microdatapro") || strpos($file_ocmod_content, "# microdatapro"))){ //если есть строка и модуль в  модификаторах
							$mod_errors--;
							$mod_files[$file]['ocmod'] = true;
						}else{
							$mod_files[$file]['ocmod'] = false;
						}
						break;
					}else{
						$mod_files[$file] = array(
							'string' => str_replace("&&&", "$", $strings),
							'status' => false,
							'ocmod'  => false
						);
					}

				}

			}

			if($key == 0){
				return $mod_files;
			}
			if($key == 1){
				return $mod_errors;
			}

		}
	//mod_files

	//find_other
		public function find_other() {
			$old_microdata = 0;
			foreach($this->getModFiles() as $file => $strings){
				$old_microdata += $this->file_scan($old_microdata, $file);
			}
			foreach($this->getMoreFiles() as $file => $strings){
				$old_microdata += $this->file_scan($old_microdata, $file);
			}

			return $old_microdata;
		}
	//find_other

	//file_scan
		public function file_scan($old_microdata, $file) {

			$file_full = DIR_MODIFICATION . str_replace("{theme}", $this->theme_dir(), $file);
			$file_content = "";
			if(is_file($file_full)){
			  $file_content = @file_get_contents($file_full);
			}

			foreach($this->find_old_list() as $tag){
				$variants = array(
					$tag,
					str_replace("http", "https", $tag),
					str_replace("=", " = ", $tag),
					str_replace('"', "'", $tag),
					str_replace('"', "'", str_replace("http", "https", $tag)),
				);
				foreach($variants as $variant){
					if (stripos($file_content, $variant)){
						$old_microdata++;
					}
				}
			}

			return $old_microdata;
		}
	//file_scan

	//getModFiles
		public function getModFiles(){
			$ext = (substr(VERSION, 0, 1) == 3)?'.twig':'.tpl';
			$tpl = (substr(VERSION, 0, 1) == 3)?'{{ ':'<?php echo &&&';
			return array(
				'system/library/document.php' => array("public function setTitle"),
				'catalog/controller/common/header.php' => array("&&&data['title']", "&&&data['name']"),
				'catalog/view/theme/{theme}/template/common/header' . $ext => array("{% for analytic", "</head>", "<body"),
				'catalog/controller/common/home.php' => array("&&&this->document->setTitle"),
				'catalog/controller/common/footer.php' => array("&&&data['contact']", "&&&data['powered']"),
				'catalog/view/theme/{theme}/template/common/footer' . $ext => array("</footer>", "</body>", "</html>"),
				'catalog/controller/product/product.php' => array("&&&this->model_catalog_product->updateViewed", "&&&data['column_left']"),
				'catalog/view/theme/{theme}/template/product/product' . $ext => array($tpl . "content_bottom", $tpl . "footer"),
				'catalog/controller/product/category.php' => array("&&&pagination = new", "&&&data['column_left']"),
				'catalog/view/theme/{theme}/template/product/category' . $ext => array($tpl . "content_bottom", $tpl . "footer"),
				'catalog/controller/product/manufacturer.php' => array("&&&pagination = new", "&&&data['column_left']"),
				'catalog/view/theme/{theme}/template/product/manufacturer_info' . $ext => array($tpl . "content_bottom", $tpl . "footer"),
				'catalog/controller/information/information.php' => array("&&&data['description']", "&&&data['column_left']"),
				'catalog/view/theme/{theme}/template/information/information' . $ext => array($tpl . "content_bottom", $tpl . "footer")
			);
		}
	//getModFiles

	//getMoreFiles
		public function getMoreFiles(){
			$ext = (substr(VERSION, 0, 1) == 3)?'.twig':'.tpl';
			return array(
				'catalog/view/theme/{theme}/template/common/home' . $ext => array(),
				'catalog/view/theme/{theme}/template/information/contact' . $ext => array(),
				'catalog/view/theme/{theme}/template/product/manufacturer_list' . $ext => array(),
				'catalog/view/theme/{theme}/template/product/review' . $ext => array(),
				'catalog/view/theme/{theme}/template/product/special' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/alltabs' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/bestseller' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/bestsellerpercategory' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/featured' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/latest' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/popular' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/product_categorytabs' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/product_tab' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/productany' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/productviewed' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/special' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/specialpercategory' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/anylist' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/product_review' . $ext => array(),
				'catalog/view/theme/{theme}/template/module/product_viewed' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/alltabs' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/bestseller' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/bestsellerpercategory' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/featured' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/latest' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/popular' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/product_categorytabs' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/product_tab' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/productany' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/productviewed' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/special' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/specialpercategory' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/anylist' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/product_review' . $ext => array(),
				'catalog/view/theme/{theme}/template/extension/module/product_viewed' . $ext => array(),
			);
		}
	//getMoreFiles

	//clear
		public function clear($text = '') {
			if($text){
				if(is_array($text)){foreach ($text as $str_item) {$text = $str_item;break;}}
				$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
				$text = str_replace("><", "> <", $text);
				$text = str_replace(array("<br />", "<br>"), " ", $text);
				$text = strip_tags($text);
				$find = array(PHP_EOL, "\r\n", "\r", "\n", "\t", '  ', '  ', '    ', '    ', '"', "'", "\\", '&varr;', '&nbsp;', '&pound;', '&euro;', '&para;', '&sect;', '&copy;', '&reg;', '&trade;', '&deg;', '&plusmn;', '&frac14;', '&frac12;', '&frac34;', '&times;', '&divide;', '&fnof;', '&Alpha;', '&Beta;', '&Gamma;', '&Delta;', '&Epsilon;', '&Zeta;', '&Eta;', '&Theta;', '&Iota;', '&Kappa;', '&Lambda;', '&Mu;', '&Nu;', '&Xi;', '&Omicron;', '&Pi;', '&Rho;', '&Sigma;', '&Tau;', '&Upsilon;', '&Phi;', '&Chi;', '&Psi;', '&Omega;', '&alpha;', '&beta;', '&gamma;', '&delta;', '&epsilon;', '&zeta;', '&eta;', '&theta;', '&iota;', '&kappa;', '&lambda;', '&mu;', '&nu;', '&xi;', '&omicron;', '&pi;', '&rho;', '&sigmaf;', '&sigma;', '&tau;', '&upsilon;', '&phi;', '&chi;', '&psi;', '&omega;', '&larr;', '&uarr;', '&rarr;', '&darr;', '&harr;', '&spades;', '&clubs;', '&hearts;', '&diams;', '&quot;', '&amp;', '&lt;', '&gt;', '&hellip;', '&prime;', '&Prime;', '&ndash;', '&mdash;', '&lsquo;', '&rsquo;', '&sbquo;', '&ldquo;', '&rdquo;', '&bdquo;', '&laquo;', '&raquo;'); //что чистим
				$text = str_replace($find, ' ', $text);
			}

			return $text;
		}
	//clear

	//clearImage
		public function clearImage($image) {
			$image = str_replace(" ", "%20", $image);
			$image = str_replace(array('id=','"',"'",'mainimage','selector'), '', $image);
			$image = rtrim($image, '%20');

			return $image;
		}
	//clearImage

	//mbCutString
		public function mbCutString($str, $length, $encoding='UTF-8'){
			if (function_exists('mb_strlen') && (mb_strlen($str, $encoding) <= $length)) {
				return $str;
			}
			if(function_exists('mb_substr')){
				$tmp = mb_substr($str, 0, $length, $encoding);
				return mb_substr($tmp, 0, mb_strripos($tmp, ' ', 0, $encoding), $encoding);
			}else{
				return $str;
			}
		}
	//mbCutString

	//getMinMaxCategory
		public function getMinMaxCategory(&$data){
			$data['total'] = 0;
			$data['min'] = 0;
			$data['max'] = 0;
			$filedata = array();

			if(isset($this->request->get['path']) && $this->request->get['path'] && $data['range']){
				$parts = explode('_', (string)$this->request->get['path']);
				$category_id = array_pop($parts);

				if(is_file(DIR_SYSTEM . $this->datfile)){ //если есть файл с количествами в категориях
					if(date("d", filectime(DIR_SYSTEM . $this->datfile)) == date('d')){ //если файл за сегодня
						$filedata = json_decode(file_get_contents(DIR_SYSTEM . $this->datfile), true);
						if(isset($filedata[$category_id])){
							$data['total'] = $filedata[$category_id]['total'];
							$data['min'] = $filedata[$category_id]['min'];
							$data['max'] = $filedata[$category_id]['max'];
						}
					}
				}

				if(!$data['total']){ //если нет количества товаров (означает что ранее не забрали с файла)
					$mm_query = $this->getMinMaxSQL($category_id);
					if($mm_query->num_rows > 1){
						$prices = array();
						foreach($mm_query->rows as $row){
							$prices[] = $row['special']?$row['special']:$row['price'];
						}

						$data['total'] = $mm_query->num_rows;

						$data['min'] = $this->currency->format($this->tax->calculate(round(min($prices), 2), 0, $this->config->get('config_tax')), $this->session->data['currency']);
						$data['max'] = $this->currency->format($this->tax->calculate(round(max($prices), 2), 0, $this->config->get('config_tax')), $this->session->data['currency']);
						$data['min'] = str_replace(array('&#8381;', '&#8372;'), '', $data['min']);
						$data['max'] = str_replace(array('&#8381;', '&#8372;'), '', $data['max']);
						$data['min'] = (float)rtrim(preg_replace('/[^\d.,]/', '', $data['min']), ".");
						$data['max'] = (float)rtrim(preg_replace('/[^\d.,]/', '', $data['max']), ".");

						$filedata[$category_id]['total'] = $data['total'];
						$filedata[$category_id]['min'] = $data['min'];
						$filedata[$category_id]['max'] = $data['max'];

						$fp = fopen(DIR_SYSTEM . $this->datfile, "w");
						fwrite($fp, json_encode($filedata));
						fclose($fp);
					}
				}

				$data['range'] = $data['total']?($data['total'] - 1):0; //если товаров 1 или меньше - не размечаем
			}
		}
	//getMinMaxCategory

	//getMinMaxSQL
		private function getMinMaxSQL($category_id){
			$sql = "SELECT p.price,
							(SELECT price FROM " . DB_PREFIX . "product_special ps
								WHERE ps.product_id = p.product_id
								AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'
								AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW())
								AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW()))
								ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special
							FROM " . DB_PREFIX . "category_path cp
							LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)
							LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)
							LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
							WHERE cp.path_id = '" . (int)$category_id . "'
							AND p.status = 1
							AND p.price > 0
							AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
							AND p.date_available <= '" . $this->db->escape(date('Y-m-d')) . "'";

							/*if($mm_query->num_rows > 1){ //multicurrency

								//currency
									$curs = 1;
									$curr_query = $this->db->query("SELECT value FROM " . DB_PREFIX . "currency WHERE currency_id = 4");
									$curs = $curr_query->row['value'];

									$prices = array();
									foreach($mm_query->rows as $row){
										$price = (int)$row['special']?$row['special']:$row['price'];
										if($row['currency_id'] == 4){ //грн
											$price = round($price/$curs);
										}
										if((int)$price){
											$prices[] = $price;
										}
									}
								//currency

								$data[$category_id]['total'] = $mm_query->num_rows;
								$data[$category_id]['min'] = (float)rtrim(preg_replace('/[^\d.,]/', '', $this->currency->format($this->tax->calculate(round(min($prices), 2), 0, $this->config->get('config_tax')), $this->session->data['currency'])), ".");
								$data[$category_id]['max'] = (float)rtrim(preg_replace('/[^\d.,]/', '', $this->currency->format($this->tax->calculate(round(max($prices), 2), 0, $this->config->get('config_tax')), $this->session->data['currency'])), ".");

							}*/

			return $this->db->query($sql);
		}
	//getMinMaxSQL

	//getMinMaxCron
		public function getMinMaxCron(){
			$data = array();

			$categories_query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE status = 1 ORDER BY category_id");
			foreach($categories_query->rows as $row){
				$category_id = $row['category_id'];
				$mm_query = $this->getMinMaxSQL($category_id);

				if($mm_query->num_rows > 1){
					$prices = array();
					foreach($mm_query->rows as $row){
						$prices[] = $row['special']?$row['special']:$row['price'];
					}

					$data[$category_id]['total'] = $mm_query->num_rows;
					$data[$category_id]['min'] = (float)rtrim(preg_replace('/[^\d.,]/', '', $this->currency->format($this->tax->calculate(round(min($prices), 2), 0, $this->config->get('config_tax')), $this->session->data['currency'])), ".");
					$data[$category_id]['max'] = (float)rtrim(preg_replace('/[^\d.,]/', '', $this->currency->format($this->tax->calculate(round(max($prices), 2), 0, $this->config->get('config_tax')), $this->session->data['currency'])), ".");

				}
			}

			$filedata = json_encode($data);
			$fp = fopen(DIR_SYSTEM . $this->datfile, "w");
			fwrite($fp, $filedata);
			fclose($fp);

			foreach($data as $category_id => $category_data){
				echo "id - " . $category_id . ": total: " . $category_data['total'] . ", min: " . $category_data['min'] . ", max: " . $category_data['max'];
				echo "<br>";
			}

		}
	//getMinMaxCron

	//storeType
		public function storeType($type = false) {
			$types = array(
				'AutoPartsStore',
				'BikeStore',
				'BookStore',
				'ClothingStore',
				'ComputerStore',
				'ConvenienceStore',
				'DepartmentStore',
				'ElectronicsStore',
				'Florist',
				'FurnitureStore',
				'GardenStore',
				'GroceryStore',
				'HardwareStore',
				'HobbyShop',
				'HomeGoodsStore',
				'JewelryStore',
				'LiquorStore',
				'MensClothingStore',
				'MobilePhoneStore',
				'MovieRentalStore',
				'MusicStore',
				'OfficeEquipmentStore',
				'OutletStore',
				'PawnShop',
				'PetStore',
				'ShoeStore',
				'SportingGoodsStore',
				'TireShop',
				'ToyStore',
				'WholesaleStore'
			);
			if($type){
				return $types[$type-1];
			}else{
				return $types;
			}
		}
	//storeType

	//checkVariable
		public function checkVariable(&$microdatapro_data, $key) {
			$opencart_variables = array(
				'category_manufacturer' => array(
					'breadcrumbs' => false,
					'description' => '',
					'results' => array(),
					'microdatapro_data' => array(
						'image' => '',
					),
				),
				'product' => array(
					'breadcrumbs' => false,
					'heading_title' => '',
					'popup' => '',
					'thumb' => '',
					'share' => '',
					'images' => array(),
					'manufacturer' => '',
					'model' => '',
					'description' => '',
					'special' => 0,
					'price' => 0,
					'options' => false,
					'microdatapro_data' => array(
						'quantity' => 0,
						'reviews' => 0,
						'rating' => 0,
						'sku' => 0,
						'upc' => 0,
						'ean' => 0,
						'isbn' => 0,
						'date_added' => date('Y.m.d'),
						'mpn' => 0,
						'results' => array()
					),
					'product_id' => 0,
					'attribute_groups' => false,
					'products' => array()
				),
				'information' => array(
				  'breadcrumbs' => false,
				  'heading_title' => '',
				  'description' => '',
				),
				'tc_og' => array(
					'microdatapro_data' => array(
						'meta_description' => 0,
						'image' => '',
					),
					'description' => '',
					'heading_title' => '',
					'breadcrumbs' => false,
				)
			);

			foreach($opencart_variables[$key] as $variable => $replacement){
				if(is_array($replacement)){
					foreach($replacement as $var => $rep){
						if(!isset($microdatapro_data[$variable][$var])){
							$microdatapro_data[$variable][$var] = $rep;
						}
					}
				}else{
					if(!isset($microdatapro_data[$variable])){
						$microdatapro_data[$variable] = $replacement;
					}
				}
			}
		}
	//checkVariable

	//find_old_list
		public function find_old_list() {
			return array(
		    'itemscope',
		    'itemscope=""',
		    'content="https://schema.org/InStock"',
				'itemtype="https://schema.org/Organization"',
				'itemtype="https://schema.org/Store"',
				'itemprop="priceRange"',
				'itemprop="hasMap"',
				'itemprop="telephone"',
				'itemprop="sameAs"',
				'itemprop="address"',
				'itemprop="addressLocality"',
				'itemprop="postalCode"',
				'itemprop="streetAddress"',
				'itemprop="geo"',
				'itemprop="latitude"',
				'itemprop="longitude"',
				'itemprop="location"',
				'itemprop="potentialAction"',
				'itemprop="target"',
				'itemprop="query-input"',
				'itemprop="openingHoursSpecification"',
				'itemprop="dayOfWeek"',
				'itemprop="opens"',
				'itemprop="closes"',
				'itemprop="brand"',
				'itemprop="manufacturer"',
				'itemprop="model"',
				'itemprop="gtin12"',
				'itemprop="category"',
				'itemprop="ratingCount"',
				'itemprop="itemCondition"',
				'itemprop="review"',
				'itemprop="author"',
				'itemprop="datePublished"',
				'itemprop="dateModified"',
				'itemprop="reviewRating"',
				'itemprop="additionalProperty"',
				'itemprop="value"',
				'itemprop="isRelatedTo"',
				'itemtype="https://schema.org/NewsArticle"',
				'itemprop="mainEntityOfPage"',
				'itemprop="headline"',
				'itemprop="author"',
				'itemprop="contentUrl"',
				'itemprop="width"',
				'itemprop="height"',
				'itemprop="publisher"',
				'itemprop="logo"',
		    'itemprop="itemListElement"',
		    'itemprop="itemListOrder"',
		    'itemprop="numberOfItems"',
		    'itemtype="https://schema.org/ListItem"',
		    'itemtype="https://schema.org/BreadcrumbList"',
		    'itemtype="https://schema.org/Thing"',
		    'itemtype="http://data-vocabulary.org/Breadcrumb"',
		    'itemprop="item"',
		    'itemprop="title"',
		    'itemprop="name"',
		    'itemprop="position"',
		    'itemprop="description"',
		    'itemtype="https://schema.org/Product"',
		    'itemprop="url"',
		    'itemprop="image"',
		    'itemprop="aggregateRating"',
				'itemtype="https://schema.org/AggregateRating"',
		    'itemprop="reviewCount"',
		    'itemprop="ratingValue"',
		    'itemprop="bestRating"',
		    'itemprop="worstRating"',
		    'itemtype="https://schema.org/Offer"',
		    'itemprop="offers"',
		    'itemprop="price"',
		    'itemprop="priceCurrency"',
		    'itemtype="https://schema.org/ItemList"',
		    'itemprop="propertiesList"',
		    'itemprop="availability"',
				'vocab="https://schema.org/"',
				'typeof="BreadcrumbList"',
				'property="itemListElement"',
				'typeof="ListItem"',
				'property="item"',
				'typeof="WebPage"',
				'property="name"',
				'property="position"',
				'itemtype="https://schema.org/AggregateOffer"',
				'itemprop="offerCount"',
				'itemprop="highPrice"',
				'itemprop="lowPrice"',
				'itemprop="priceCurrency"',
				'xmlns:v="http://rdf.data-vocabulary.org/#"',
				'typeof="v:Breadcrumb"',
				'rel="v:url"',
				'property="v:title"',
				'itemprop="email"',
				'itemprop="openingHours"',
				'property="og:',
				'itemtype="https://schema.org/PostalAddress"',
				'itemprop="addressCountry"',
				'ld+json'
		  );
		}
	//find_old_list

	//view_tc_og
		public function view_tc_og($data){
			$html = '';

			if($data['twitter']){
				$html .= '<!--microdatapro ' . $this->ver . ' twitter cards start -->';
				$html .= '<meta property="twitter:card" content="summary_large_image">';
				$html .= '<meta property="twitter:creator" content="' . $data['twitter_account'] . '">';
				$html .= '<meta property="twitter:site" content="' . $data['title'] . '">';
				$html .= '<meta property="twitter:title" content="' . $data['title'] . '">';
				$html .= '<meta property="twitter:description" content="' . $data['description'] . '">';
				$html .= '<meta property="twitter:image" content="' . $data['image'] . '">';
				$html .= '<meta property="twitter:image:alt" content="' . $data['title'] . '">';
				$html .= '<!--microdatapro ' . $this->ver . ' twitter cards end -->';
			}
			if($data['opengraph']){
			  $html .= '<!--microdatapro ' . $this->ver . ' open graph start -->';
			  $html .= '<meta property="og:locale" content="' . $data['locale'] . '">';
			  $html .= '<meta property="og:rich_attachment" content="true">';
			  $html .= '<meta property="og:site_name" content="' . $data['site_name'] . '">';
			  $html .= '<meta property="og:type" content="' . $data['og_type'] . '">';
			  $html .= '<meta property="og:title" content="' . $data['title'] . '">';
			  $html .= '<meta property="og:description" content="' . $data['description'] . '">';
			  $html .= '<meta property="og:image" content="' . $data['image'] . '">';
			  $html .= '<meta property="og:image:secure_url" content="' . $data['image'] . '">';
			  $html .= '<meta property="og:image:width" content="' . $data['image_width'] . '">';
			  $html .= '<meta property="og:image:height" content="' . $data['image_height'] . '">';
			  if($data['images']){
			    foreach($data['images'] as $image){
			      $html .= '<meta property="og:image" content="' . $data['image'] . '">';
			      $html .= '<meta property="og:image:secure_url" content="' . $data['image'] . '">';
			    }
			  }
			  $html .= '<meta property="og:url" content="' . $data['url'] . '">';
			  if($data['contacts'] && $data['organizations']){
			    $html .= '<meta property="business:contact_data:street_address" content="' . $data['street_address'] . '">';
			    $html .= '<meta property="business:contact_data:locality" content="' . $data['locality'] . '">';
			    $html .= '<meta property="business:contact_data:postal_code" content="' . $data['postal_code'] . '">';
			    $html .= '<meta property="business:contact_data:country_name" content="' . $data['country_name'] . '">';
			    $html .= '<meta property="place:location:latitude" content="' . $data['latitude'] . '">';
			    $html .= '<meta property="place:location:longitude" content="' . $data['longitude'] . '">';
			    $html .= '<meta property="business:contact_data:email" content="' . $data['email'] . '">';
			    if($data['telephone']){
			      $html .= '<meta property="business:contact_data:phone_number" content="' . $data['telephone'] . '">';
			    }
			  }
			  if($data['microdatapro_profile_id']){
			    $html .= '<meta property="fb:profile_id" content="' . $data['microdatapro_profile_id'] . '">';
			  }
			  if($data['product_page']){
			    $html .= '<meta property="product:product_link" content="' . $data['url'] . '">';
			    $html .= '<meta property="product:brand" content="' . $data['product_manufacturer'] . '">';
			    $html .= '<meta property="product:category" content="' . $data['product_category'] . '">';
			    $html .= '<meta property="product:availability" content="' . $data['product_stock'] . '">';
			    if($data['age_group']){
			      $html .= '<meta property="product:age_group" content="' . $data['age_group'] . '">';
			      if($data['age_group'] == "adult"){
			        $html .= '<meta property="og:restrictions:age" content="18+">';
			      }
			    }
			    $html .= '<meta property="product:condition" content="new">';
					foreach(array('ean','isbn','upc','color','material','size') as $type){
						if($data[$type]){
							$html .= '<meta property="product:' . $type . '" content="' . $data[$type] . '">';
						}
					}

			    $html .= '<meta property="product:target_gender" content="' . $data['target_gender'] . '">';
			    if($data['price']){
			      $html .= '<meta property="product:price:amount" content="' . $data['price'] . '">';
			      $html .= '<meta property="product:price:currency" content="' . $data['currency'] . '">';
			    }
			    if($data['special']){
			      $html .= '<meta property="product:sale_price:amount" content="' . $data['special'] . '">';
			      $html .= '<meta property="product:sale_price:currency" content="' . $data['currency'] . '">';
			    }
			    if($data['relateds']){
			      foreach($data['relateds'] as $related){
			        $html .= '<meta property="og:see_also" content="' . $related . '">';
			      }
			    }
			  }
			  $html .= '<!--microdatapro ' . $this->ver . ' open graph end -->';
			}

			return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
		}
	//view_tc_og

	//view_company
	  public function view_company($data){
	    $html = '';

			//syntax md
				if($data['company_syntax'] == "md" or $data['company_syntax'] == "all"){
				  $html .= '<!--microdatapro ' . $this->ver . ' company start [microdata] -->';
				  $html .= '<span itemscope itemtype="https://schema.org/' . $data['store_type'] . '">';
				  $html .= '<meta itemprop="name" content="' . $data['organization_name'] . '">';
				  $html .= '<link itemprop="url" href="' . $data['organization_url'] . '">';
				  $html .= '<link itemprop="image" href="' . $data['organization_logo'] . '">';
				  $html .= '<link itemprop="logo" href="' . $data['logo'] . '">';
				  $html .= '<meta itemprop="email" content="' . $data['organization_email'] . '">';
				  $html .= '<meta itemprop="priceRange" content="' . $data['code'] . '">';
				  if($data['organization_map']){
				    $html .= '<meta itemprop="hasMap" content="' . $data['organization_map'] . '">';
				  }
				  if($data['organization_phones']){
				    foreach($data['organization_phones'] as $phone){
				      $html .= '<meta itemprop="telephone" content="' . $phone . '">';
				    }
				  }
				  if($data['organization_groups']){
				    foreach($data['organization_groups'] as $group){
				      $html .= '<link itemprop="sameAs" href="' . $group . '">';
				    }
				  }
				  if($data['organization_locations']){
				    foreach($data['organization_locations'] as $location){
				      $html .= '<span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
				      $html .= '<meta itemprop="addressLocality" content="' . $location['addressLocality'] . '">';
				      $html .= '<meta itemprop="postalCode" content="' . $location['postalCode'] . '">';
				      $html .= '<meta itemprop="streetAddress" content="' . $location['streetAddress'] . '">';
				      $html .= '</span>';
				      $html .= '<span itemprop="location" itemscope itemtype="https://schema.org/Place">';
				      $html .= '<meta itemprop="name" content="' . $data['organization_name'] . '">';
				      $html .= '<link itemprop="image" href="' . $data['organization_logo'] . '">';
				      if($data['organization_phones']){
				        foreach($data['organization_phones'] as $phone){
				          $html .= '<meta itemprop="telephone" content="' . $phone . '">';
				        }
				      }
				      $html .= '<span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
				      $html .= '<meta itemprop="addressLocality" content="' . $location['addressLocality'] . '">';
				      $html .= '<meta itemprop="postalCode" content="' . $location['postalCode'] . '">';
				      $html .= '<meta itemprop="streetAddress" content="' . $location['streetAddress'] . '">';
				      $html .= '</span>';
				      $html .= '<span itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates">';
				      $html .= '<meta itemprop="latitude" content="' . $location['latitude'] . '">';
				      $html .= '<meta itemprop="longitude" content="' . $location['longitude'] . '">';
				      $html .= '<span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
				      $html .= '<meta itemprop="streetAddress" content="' . $location['streetAddress'] . '">';
				      $html .= '<meta itemprop="addressLocality" content="' . $location['addressLocality'] . '">';
				      $html .= '<meta itemprop="postalCode" content="' . $location['postalCode'] . '">';
				      $html .= '</span>';
				      $html .= '</span>';
				      $html .= '</span>';
				    }
				  }
				  $html .= '<span itemprop="potentialAction" itemscope itemtype="https://schema.org/SearchAction">';
				  $html .= '<meta itemprop="target" content="' . $data['organization_url'] . 'index.php?route=product/search&search={search_term_string}">';
				  $html .= '<input type="hidden" itemprop="query-input" name="search_term_string">';
				  $html .= '</span>';
				  if($data['organization_oh']){
				    foreach($data['organization_oh'] as $day => $oh){
				      $html .= '<span itemprop="openingHoursSpecification" itemscope itemtype="https://schema.org/OpeningHoursSpecification">';
				      $html .= '<link itemprop="dayOfWeek" href="https://schema.org/' . $day . '">';
				      $html .= '<meta itemprop="opens" content="' . $oh['open'] . '">';
				      $html .= '<meta itemprop="closes" content="' . $oh['close'] . '">';
				      $html .= '</span>';
				    }
				  }
				  $html .= '</span>';
				  $html .= '<!--microdatapro ' . $this->ver . ' company end [microdata] -->';
				}
			//syntax md

			//syntax json-ld
			  if($data['company_syntax'] == "ld" or $data['company_syntax'] == "all"){
			    $html .= '<!--microdatapro ' . $this->ver . ' search start [json-ld] -->';
			    $html .= '<script type="application/ld+json">';
			    $html .= '{';
			    $html .= '"@context": "https://schema.org",';
			    $html .= '"@type": "WebSite",';
			    $html .= '"url": "' . $data['organization_url'] . '",';
			    $html .= '"potentialAction": {';
			    $html .= '"@type": "SearchAction",';
			    $html .= '"target": {';
			    $html .= '"@type": "EntryPoint",';
			    $html .= '"urlTemplate": "' . $data['organization_url'] . 'index.php?route=product/search&search={search_term_string}"';
			    $html .= '},';
			    $html .= '"query-input": "required name=search_term_string"';
			    $html .= '}';
			    $html .= '}';
			    $html .= '</script>';
			    $html .= '<!--microdatapro ' . $this->ver . ' search end [json-ld] -->';
			    $html .= '<!--microdatapro ' . $this->ver . ' company start [json-ld] -->';
			    $html .= '<script type="application/ld+json">';
			    $html .= '{';
			    $html .= '"@context": "https://schema.org",';
			    $html .= '"@type": "' . $data['store_type'] . '",';
			    $html .= '"name": "' . $data['organization_name'] . '",';
			    $html .= '"url": "' . $data['organization_url'] . '",';
			    $html .= '"image": "' . $data['organization_logo'] . '",';
			    $html .= '"logo": "' . $data['logo'] . '",';
			    if (isset($data['organization_phones'][0])){
			      $html .= '"telephone" : "' . $data['organization_phones'][0] . '",';
			    }
			    $html .= '"email": "' . $data['organization_email'] . '",';
			    $html .= '"priceRange": "' . $data['code'] . '",';
			    if($data['organization_locations']){
			      foreach($data['organization_locations'] as $location){
			        $html .= '"address": {';
			        $html .= '"@type": "PostalAddress",';
			        $html .= '"addressLocality": "' . $location['addressLocality'] . '",';
			        $html .= '"postalCode": "' . $location['postalCode'] . '",';
			        $html .= '"streetAddress": "' . $location['streetAddress'] . '"';
			        $html .= '},';
			        $html .= '"location": {';
			        $html .= '"@type": "Place",';
			        $html .= '"address": {';
			        $html .= '"@type": "PostalAddress",';
			        $html .= '"addressLocality": "' . $location['addressLocality'] . '",';
			        $html .= '"postalCode": "' . $location['postalCode'] . '",';
			        $html .= '"streetAddress": "' . $location['streetAddress'] . '"';
			        $html .= '},';
			        $html .= '"geo": {';
			        $html .= '"@type": "GeoCoordinates",';
			        $html .= '"latitude": "' . $location['latitude'] . '",';
			        $html .= '"longitude": "' . $location['longitude'] . '"';
			        $html .= '}';
			        $html .= '},';
			        break;
			      }
			    }
			    $html .= '"potentialAction": {';
			    $html .= '"@type": "SearchAction",';
			    $html .= '"target": "' . $data['organization_url'] . 'index.php?route=product/search&search={search_term_string}",';
			    $html .= '"query-input": "required name=search_term_string"';
			    $html .= '}';
			    if($data['organization_phones']){
			      $html .= ',';
			      $html .= '"contactPoint" : [';
			      $pi = 1;
			      foreach($data['organization_phones'] as $phone){
			        $html .= '{';
			        $html .= '"@type" : "ContactPoint",';
			        $html .= '"telephone" : "' . $phone . '",';
			        $html .= '"contactType" : "customer service"';
			        $html .= '}';
			        if($pi != count($data['organization_phones'])){
			          $html .= ',';
			        }
			        $pi++;
			      }
			      $html .= ']';
			    }
			    if($data['organization_groups']){
			      $html .= ',';
			      $html .= '"sameAs" : [';
			      $gi = 1;
			      foreach($data['organization_groups'] as $group){
			        $html .= '"' . $group . '"';
			        if($gi != count($data['organization_groups'])){
			          $html .= ',';
			        }
			        $gi++;
			      }
			      $html .= ']';
			    }
			    if($data['organization_oh']){
			      $html .= ',';
			      $html .= '"openingHoursSpecification":[';
			      $ohi = 1;
			      foreach($data['organization_oh'] as $day => $oh){
			        $html .= '{';
			        $html .= '"@type": "OpeningHoursSpecification",';
			        $html .= '"dayOfWeek": "' . $day . '",';
			        $html .= '"opens": "' . $oh['open'] . '",';
			        $html .= '"closes": "' . $oh['close'] . '"';
			        $html .= '}';
			        if($ohi != count($data['organization_oh'])){
			          $html .= ',';
			        }
			        $ohi++;
			      }
			      $html .= ']';
			    }
			    $html .= '}';
			    $html .= '</script>';
			    $html .= '<!--microdatapro ' . $this->ver . ' company end [json-ld] -->';
			  }
			//syntax json-ld

			//hcard
			  if($data['config_hcard']){
			    $html .= '<!--microdatapro ' . $this->ver . ' company start [hCard] -->';
			    $html .= '<span class="vcard">';
			    $html .= '<span class="fn org"><span class="value-title" title="' . $data['organization_name'] . '"></span></span>';
			    $html .= '<span class="org"><span class="value-title" title="' . $data['organization_name'] . '"></span></span>';
			    $html .= '<span class="url"><span class="value-title" title="' . $data['organization_url'] . '"></span></span>';
			    if($data['organization_locations']){
			      foreach($data['organization_locations'] as $location){
			        $html .= '<span class="adr">';
			        $html .= '<span class="locality"><span class="value-title" title="' . $location['addressLocality'] . '"></span></span>';
			        $html .= '<span class="street-address"><span class="value-title" title="' . $location['streetAddress'] . '"></span></span>';
			        $html .= '<span class="postal-code"><span class="value-title" title="' . $location['postalCode'] . '"></span></span>';
			        $html .= '</span>';
			        $html .= '<span class="geo">';
			        $html .= '<span class="latitude"><span class="value-title" title="' . $location['latitude'] . '"></span></span>';
			        $html .= '<span class="longitude"><span class="value-title" title="' . $location['longitude'] . '"></span></span>';
			        $html .= '</span>';
			      }
			    }
			    if($data['organization_phones']){
			      foreach($data['organization_phones'] as $phone){
			        $html .= '<span class="tel"><span class="value-title" title="' . $phone . '"></span></span>';
			      }
			    }
			    $html .= '<span class="photo"><span class="value-title" title="' . $data['organization_logo'] . '"></span></span>';
			    $html .= '</span>';
			    $html .= '<!--microdatapro ' . $this->ver . ' company end [hCard ] -->';
			  }
			//hcard

	    return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
	  }
	//view_company

	//view_product
	  public function view_product($data){
	    $html = '';

			//breadcrumbs
				if($data['breadcrumbs']){
				  if($data['syntax'] == "md" or $data['syntax'] == "all"){
				    $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb start [microdata] -->';
				    $html .= '<span itemscope itemtype="https://schema.org/BreadcrumbList">';
				    foreach ($data['breadcrumbs'] as $key => $breadcrumb) {
				      $html .= '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
				      $html .= '<link itemprop="item" href="' . $breadcrumb['href'] . '">';
				      $html .= '<meta itemprop="name" content="' . $breadcrumb['text'] . '">';
				      $html .= '<meta itemprop="position" content="' . $key . '">';
				      $html .= '</span>';
				    }
				    $html .= '</span>';
				    $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb end [microdata] -->';
				  }
				  if($data['syntax'] == "ld" or $data['syntax'] == "all"){
				    $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb start [json-ld] -->';
				    $html .= '<script type="application/ld+json">';
				    $html .= '{';
				    $html .= '"@context": "https://schema.org",';
				    $html .= '"@type": "BreadcrumbList",';
				    $html .= '"itemListElement": [';
				    foreach ($data['breadcrumbs'] as $key => $breadcrumb) {
				      $html .= '{';
				      $html .= '"@type": "ListItem",';
				      $html .= '"position": ' . $key . ',';
				      $html .= '"item": {';
				      $html .= '"@id": "' . $breadcrumb['href'] . '",';
				      $html .= '"name": "' . $breadcrumb['text'] . '"';
				      $html .= '}';
				      $html .= '}';
				      if($key != count($data['breadcrumbs'])){
				        $html .= ',';
				      }
				    }
				    $html .= ']';
				    $html .= '}';
				    $html .= '</script>';
				    $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb end [json-ld] -->';
				  }
				}
			//breadcrumbs

			//syntax md
			  if($data['syntax'] == "md" or $data['syntax'] == "all"){
			    $html .= '<!--microdatapro ' . $this->ver . ' product start [microdata] -->';
			    $html .= '<span itemscope itemtype="https://schema.org/Product">';
			    $html .= '<meta itemprop="name" content="' . $data['name'] . '">';
			    $html .= '<link itemprop="url" href="' . $data['url'] . '">';
			    if($data['popup']){
			      $html .= '<link itemprop="image" href="' . $data['popup'] . '">';
			    }
			    if($data['manufacturer']){
			      $html .= '<span itemprop="brand" itemtype="https://schema.org/Brand" itemscope><meta itemprop="name" content="' . $data['manufacturer'] . '"></span>';
			      $html .= '<meta itemprop="manufacturer" content="' . $data['manufacturer'] . '">';
			    }
			    if($data['model']){
			      $html .= '<meta itemprop="model" content="' . $data['model'] . '">';
			    }
			    if($data['upc']){
			      $html .= '<meta itemprop="gtin12" content="' . $data['upc'] . '">';
			    }
			    if($data['ean']){
			      $html .= '<meta itemprop="gtin8" content="' . $data['ean'] . '">';
			    }
			    if($data['isbn']){
			      $html .= '<meta itemprop="productID" content="' . $data['isbn'] . '">';
			    }
			    if($data['mpn']){
			      $html .= '<meta itemprop="mpn" content="' . $data['mpn'] . '">';
			    }
			    if($data['sku']){
			      $html .= '<meta itemprop="sku" content="' . $data['sku'] . '">';
			    }
			    if($data['category']){
			      $html .= '<meta itemprop="category" content="' . $data['category'] . '">';
			    }
			    if($data['reviews']){
			      $html .= '<span itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
			      $html .= '<meta itemprop="ratingValue" content="' . $data['rating'] . '">';
			      $html .= '<meta itemprop="reviewCount" content="' . $data['reviewCount'] . '">';
			      $html .= '<meta itemprop="bestRating" content="5">';
			      $html .= '<meta itemprop="worstRating" content="1">';
			      $html .= '</span>';
			    }
			    $html .= '<span itemprop="offers" itemscope itemtype="https://schema.org/Offer">';
			    $html .= '<meta itemprop="priceCurrency" content="' . $data['code'] . '">';
			    $html .= '<meta itemprop="price" content="' . $data['price'] . '">';
			    $html .= '<meta itemprop="itemCondition" content="https://schema.org/' . $data['condition'] . '">';
			    $html .= '<link itemprop="availability" href="https://schema.org/' . $data['stock'] . '">';
			    $html .= '<meta itemprop="priceValidUntil" content="' . $data['price_valid'] . '">';
			    $html .= '<link itemprop="url" href="' . $data['url'] . '">';
			    $html .= '</span>';
			    $html .= '<meta itemprop="description" content="' . $data['description'] . '">';
			    if($data['reviews']){
			      foreach($data['reviews'] as $review_item){
			        $html .= '<span itemprop="review" itemscope itemtype="https://schema.org/Review">';
			        $html .= '<span itemprop="author" itemscope itemtype="https://schema.org/Person"><meta itemprop="name" content="' . $review_item['author'] . '"></span>';
			        $html .= '<meta itemprop="datePublished" content="' . $review_item['date_added'] . '">';
			        $html .= '<span itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
			        $html .= '<meta itemprop="worstRating" content = "1">';
			        $html .= '<meta itemprop="ratingValue" content="' . $review_item['rating'] . '">';
			        $html .= '<meta itemprop="bestRating" content="5">';
			        $html .= '</span>';
			        $html .= '<meta itemprop="description" content="' . $review_item['text'] . '">';
			        $html .= '</span>';
			      }
			    }
			    if($data['attributes']){
			      foreach ($data['attributes'] as $attribute){
			        $html .= '<span itemprop="additionalProperty" itemscope itemtype="https://schema.org/PropertyValue">';
			        $html .= '<meta itemprop="value" content="' . $attribute['text'] . '">';
			        $html .= '<meta itemprop="name" content="' . $attribute['name'] . '">';
			        $html .= '</span>';
			      }
			    }
			    if($data['products']){
			      foreach($data['products'] as $key => $product){
			        $html .= '<span id="related-product-' . $key . '" itemprop="isRelatedTo" itemscope itemtype="https://schema.org/Product">';
			        $html .= '<meta itemprop="name" content="' . $product['name'] . '">';
			        $html .= '<meta itemprop="description" content="' . $product['name'] . '">';
			        $html .= '<link itemprop="url" href="' . $product['href'] . '">';
			        $html .= '<link itemprop="image" href="' . $product['thumb'] . '">';
			        $html .= '<span itemprop="offers" itemscope itemtype="https://schema.org/Offer">';
			        $html .= '<meta itemprop="priceCurrency" content="' . $data['code'] . '">';
			        $html .= '<meta itemprop="price" content="' . $product['price'] . '">';
			        $html .= '</span>';
			        $html .= '</span>';
			      }
			    }
			    $html .= '</span>';
			    $html .= '<!--microdatapro ' . $this->ver . ' product end [microdata] -->';
			    $html .= '<!--microdatapro ' . $this->ver . ' image start[microdata] -->';
			    $html .= '<span itemscope itemtype="https://schema.org/ImageObject">';
			    $html .= '<meta itemprop="name" content="' . $data['name'] . '">';
			    $html .= '<meta itemprop="description" content="' . $data['name'] . '">';
			    $html .= '<link itemprop="thumbnailUrl" href="' . $data['thumb'] . '">';
			    $html .= '<link itemprop="contentUrl" href="' . $data['popup'] . '">';
			    $html .= '<meta itemprop="author" content="' . $data['author'] . '">';
			    $html .= '<meta itemprop="datePublished" content="' . $data['date_added'] . '">';
			    $html .= '</span>';
			    $html .= '<!--microdatapro ' . $this->ver . ' image end [microdata] -->';
			    if($data['images']){
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery start[microdata] -->';
			      $html .= '<span itemscope itemtype="https://schema.org/ImageGallery">';
			      foreach($data['images'] as $image){
			        $html .= '<span itemprop="associatedMedia" itemscope itemtype="https://schema.org/ImageObject">';
			        $html .= '<meta itemprop="name" content="' . $data['name'] . '">';
			        $html .= '<meta itemprop="description" content="' . $data['name'] . '">';
			        $html .= '<link itemprop="thumbnailUrl" href="' . $image['thumb'] . '">';
			        $html .= '<link itemprop="contentUrl" href="' . $image['popup'] . '">';
			        $html .= '<meta itemprop="author" content="' . $data['author'] . '">';
			        $html .= '<meta itemprop="datePublished" content="' . $data['date_added'] . '">';
			        $html .= '</span>';
			      }
			      $html .= '</span>';
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery end [microdata] -->';
			    }
			  }
			//syntax md

			//syntax json-ld
			  if($data['syntax'] == "ld" or $data['syntax'] == "all"){
			    $html .= '<!--microdatapro ' . $this->ver . ' product start [json-ld] -->';
			    $html .= '<script type="application/ld+json">';
			    $html .= '{';
			    $html .= '"@context": "https://schema.org",';
			    $html .= '"@type": "Product",';
			    $html .= '"url": "' . $data['url'] . '",';
			    if($data['category']){
			      $html .= '"category": "' . $data['category'] . '",';
			    }
			    if($data['popup']){
			      $html .= '"image": "' . $data['popup'] . '",';
			    }
			    if($data['manufacturer']){
			      $html .= '"brand": {"@type": "Brand","name": "' . $data['manufacturer'] . '"},';
			      $html .= '"manufacturer": "' . $data['manufacturer'] . '",';
			    }
			    if($data['model']){
			      $html .= '"model": "' . $data['model'] . '",';
			    }
			    if($data['upc']){
			      $html .= '"gtin12": "' . $data['upc'] . '",';
			    }
			    if($data['ean']){
			      $html .= '"gtin8": "' . $data['ean'] . '",';
			    }
			    if($data['isbn']){
			      $html .= '"productID": "' . $data['isbn'] . '",';
			    }
			    if($data['mpn']){
			      $html .= '"mpn": "' . $data['mpn'] . '",';
			    }
			    if($data['sku']){
			      $html .= '"sku": "' . $data['sku'] . '",';
			    }
			    if($data['reviews']){
			      $html .= '"aggregateRating": {';
			      $html .= '"@type": "AggregateRating",';
			      $html .= '"ratingValue": "' . $data['rating'] . '",';
			      $html .= '"reviewCount": "' . $data['reviewCount'] . '",';
			      $html .= '"bestRating": "5",';
			      $html .= '"worstRating": "1"';
			      $html .= '},';
			    }
			    $html .= '"description": "' . $data['description'] . '",';
			    $html .= '"name": "' . $data['name'] . '",';
			    $html .= '"offers": {';
			    $html .= '"@type": "Offer",';
			    $html .= '"availability": "https://schema.org/' . $data['stock'] . '",';
			    $html .= '"price": "' . $data['price'] . '",';
			    $html .= '"priceValidUntil": "' . $data['price_valid'] . '",';
			    $html .= '"url": "' . $data['url'] . '",';
			    $html .= '"priceCurrency": "' . $data['code'] . '",';

					//new

					$html .= '"shippingDetails": {';
					  $html .= '"@type": "OfferShippingDetails",';
					  $html .= '"shippingRate": {';
					    $html .= '"@type": "MonetaryAmount",';
					    $html .= '"value": ' . $data['shipping_cost'] . ',';
					    $html .= '"currency": "' . $data['code'] . '"';
					  $html .= '},';
					  $html .= '"shippingDestination": {';
					    $html .= '"@type": "DefinedRegion",';
					    $html .= '"addressCountry": "' . $data['shipping_country'] . '"';
					  $html .= '},';
					  $html .= '"deliveryTime": {';
					    $html .= '"@type": "ShippingDeliveryTime",';
					    $html .= '"handlingTime": {';
					      $html .= '"@type": "QuantitativeValue",';
					      $html .= '"minValue": ' . $data['shipping_time1_min'] . ',';
					      $html .= '"maxValue": ' . $data['shipping_time1_max'] . ',';
					      $html .= '"unitCode": "DAY"';
					    $html .= '},';
					    $html .= '"transitTime": {';
					      $html .= '"@type": "QuantitativeValue",';
					      $html .= '"minValue": ' . $data['shipping_time2_min'] . ',';
					      $html .= '"maxValue": ' . $data['shipping_time2_max'] . ',';
					      $html .= '"unitCode": "DAY"';
					    $html .= '}';
					  $html .= '}';
					$html .= '},';


					$html .= '"hasMerchantReturnPolicy": {';
					  $html .= '"@type": "http://schema.org/MerchantReturnPolicy",';
					  $html .= '"applicableCountry": "' . $data['shipping_country'] . '",';
					  $html .= '"returnPolicyCategory": "https://schema.org/MerchantReturnFiniteReturnWindow",';
					  $html .= '"merchantReturnDays": "' . $data['return_days'] . '",';
					  $html .= '"returnMethod": "https://schema.org/ReturnByMail",';
					  $html .= '"returnFees": "https://schema.org/FreeReturn"';
					$html .= '},';
					//new

			    $html .= '"itemCondition": "https://schema.org/' . $data['condition'] . '"';
			    $html .= '}';
			    if ($data['reviews']){
			      $html .= ',"review": [';
			      foreach ($data['reviews'] as $key => $review_item){
			        $html .= '{';
			        $html .= '"@type": "Review",';
			        $html .= '"author": {"@type": "Person", "name": "' . $review_item['author'] . '"},';
			        $html .= '"datePublished": "' . $review_item['date_added'] . '",';
			        $html .= '"description": "' . $review_item['text'] . '",';
			        $html .= '"reviewRating": {';
			        $html .= '"@type": "Rating",';
			        $html .= '"bestRating": "5",';
			        $html .= '"ratingValue": "' . $review_item['rating'] . '",';
			        $html .= '"worstRating": "1"';
			        $html .= '}';
			        $html .= '}';
			        if($key != count($data['reviews'])){
			          $html .= ',';
			        }
			      }
			      $html .= ']';
			    }
			    if($data['products']){
			      $html .= ',"isRelatedTo": [';
			      foreach($data['products'] as $key => $product){
			        $html .= '{';
			        $html .= '"@type": "Product",';
			        $html .= '"image": "' . $product['thumb'] . '",';
			        $html .= '"url": "' . $product['href'] . '",';
			        $html .= '"name": "' . $product['name'] . '",';
			        $html .= '"offers": {';
			        $html .= '"@type": "Offer",';
			        $html .= '"price": "' . $product['price'] . '",';
			        $html .= '"priceCurrency": "' . $data['code'] . '"';
			        $html .= '}';
			      $html .= '}';
			        if($key != count($data['products'])){
			          $html .= ',';
			        }
			      }
			      $html .= ']';
			    }
			    if($data['attributes']){
			      $html .= ',"additionalProperty":[';
			      foreach($data['attributes'] as $key => $attribute){
			        $html .= '{';
			        $html .= '"@type": "PropertyValue",';
			        $html .= '"name": "' . $attribute['name'] . '",';
			        $html .= '"value": "' . $attribute['text'] . '"';
			        $html .= '}';
			        if($key != count($data['attributes'])){
			        $html .= ',';
			        }
			      }
			      $html .= ']';
			    }
			    $html .= '}';
			    $html .= '</script>';
			    $html .= '<!--microdatapro ' . $this->ver . ' product end [json-ld] -->';
			    $html .= '<!--microdatapro ' . $this->ver . ' image start [json-ld] -->';
			    $html .= '<script type="application/ld+json">';
			    $html .= '{';
			    $html .= '"@context": "https://schema.org",';
			    $html .= '"@type": "ImageObject",';
			    $html .= '"author": "' . $data['author'] . '",';
			    $html .= '"thumbnailUrl": "' . $data['thumb'] . '",';
			    $html .= '"contentUrl": "' . $data['popup'] . '",';
			    $html .= '"datePublished": "' . $data['date_added'] . '",';
			    $html .= '"description": "' . $data['name'] . '",';
			    $html .= '"name": "' . $data['name'] . '"';
			    $html .= '}';
			    $html .= '</script>';
			    $html .= '<!--microdatapro ' . $this->ver . ' image end [json-ld] -->';
			    if($data['images']){
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery start [json-ld] -->';
			      $html .= '<script type="application/ld+json">';
			      $html .= '{';
			      $html .= '"@context": "https://schema.org",';
			      $html .= '"@type": "ImageGallery",';
			      $html .= '"associatedMedia":[';
			      $keyi = 1;
			      foreach($data['images'] as $image){
			        $html .= '{';
			        $html .= '"@type": "ImageObject",';
			        $html .= '"author": "' . $data['author'] . '",';
			        $html .= '"thumbnailUrl": "' . $image['thumb'] . '",';
			        $html .= '"contentUrl": "' . $image['popup'] . '",';
			        $html .= '"datePublished": "' . $data['date_added'] . '",';
			        $html .= '"description": "' . $data['name'] . '",';
			        $html .= '"name": "' . $data['name'] . '"';
			        $html .= '}';
			        if($keyi != count($data['images'])){
			        	$html .= ',';
			        }
			        $keyi++;
			      }
			      $html .= ']';
			      $html .= '}';
			      $html .= '</script>';
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery end [json-ld] -->';
			    }
			  }
			//syntax json-ld

	    return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
	  }
	//view_product

	//view_category_manufacturer
	  public function view_category_manufacturer($data){
	    $html = '';

			//breadcrumb
			  if($data['breadcrumbs']){
			    if($data['syntax'] == "md" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb start [microdata] -->';
			      $html .= '<span itemscope itemtype="https://schema.org/BreadcrumbList">';
			      foreach ($data['breadcrumbs'] as $key => $breadcrumb) {
			        $html .= '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
			        $html .= '<link itemprop="item" href="' . $breadcrumb['href'] . '">';
			        $html .= '<meta itemprop="name" content="' . $breadcrumb['text'] . '">';
			        $html .= '<meta itemprop="position" content="' . $key . '">';
			        $html .= '</span>';
			      }
			      $html .= '</span>';
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb end [microdata] -->';
			    }
			    if($data['syntax'] == "ld" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb start [json-ld] -->';
			      $html .= '<script type="application/ld+json">';
			      $html .= '{';
			      $html .= '"@context": "https://schema.org",';
			      $html .= '"@type": "BreadcrumbList",';
			      $html .= '"itemListElement": [';
			      foreach ($data['breadcrumbs'] as $key => $breadcrumb) {
			        $html .= '{';
			        $html .= '"@type": "ListItem",';
			        $html .= '"position": ' . $key . ',';
			        $html .= '"item": {';
			        $html .= '"@id": "' . $breadcrumb['href'] . '",';
			        $html .= '"name": "' . $breadcrumb['text'] . '"';
			        $html .= '}';
			        $html .= '}';
			        if($key != count($data['breadcrumbs'])){
			          $html .= ',';
			        }
			      }
			      $html .= ']';
			      $html .= '}';
			      $html .= '</script>';
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb end [json-ld] -->';
			    }
			  }
			//breadcrumb

			//product
			  if($data['range'] or $data['review']){
			    if($data['syntax'] == "md" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' product start [microdata] -->';
			      $html .= '<span itemscope itemtype="https://schema.org/Product">';
			      $html .= '<meta itemprop="name" content="' . $data['name'] . '">';
			      $html .= '<link itemprop="image" href="' . $data['image'] . '">';
			      $html .= '<span itemprop="brand" itemtype="https://schema.org/Brand" itemscope><meta itemprop="name" content="' . $data['name'] . '"></span>';
			      $html .= '<meta itemprop="description" content="' . $data['description'] . '">';
			      $html .= '<meta itemprop="sku" content="' . $data['sku'] . '">';
			      $html .= '<meta itemprop="mpn" content="' . $data['sku'] . '">';
			        if($data['review'] && $data['rating_count'] && $data['rating_value']){
			          $html .= '<span itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
			          $html .= '<meta itemprop="ratingCount" content="' . $data['rating_count'] . '">';
			          $html .= '<meta itemprop="ratingValue" content="' . $data['rating_value'] . '">';
			          $html .= '<meta itemprop="bestRating" content="5">';
			          $html .= '</span>';
			        }
			        if($data['range']){
			          $html .= '<span itemprop="offers" itemscope itemtype="https://schema.org/AggregateOffer">';
			          $html .= '<meta itemprop="highPrice" content="' . $data['max'] . '">';
			          $html .= '<meta itemprop="lowPrice" content="' . $data['min'] . '">';
			          $html .= '<meta itemprop="offerCount" content="' . $data['total'] . '">';
			          $html .= '<meta itemprop="priceCurrency" content="' . $data['code'] . '">';
			          $html .= '</span>';
			        }
			      $html .= '</span>';
			      $html .= '<!--microdatapro ' . $this->ver . ' product end [microdata] -->';
			    }

			    if($data['syntax'] == "ld" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' product start [json-ld] -->';
			        $html .= '<script type="application/ld+json">';
			        $html .= '{';
			        $html .= '"@context": "https://schema.org/",';
			        $html .= '"@type": "Product",';
			        $html .= '"name": "' . $data['name'] . '",';
			        $html .= '"image": "' . $data['image'] . '",';
			        $html .= '"brand": {"@type": "Brand","name": "' . $data['name'] . '"},';
			        $html .= '"description": "' . $data['description'] . '",';
			        $html .= '"sku": "' . $data['sku'] . '",';
			        $html .= '"mpn": "' . $data['sku'] . '"';
			        if($data['review']){
			          $html .= ',"aggregateRating": {';
			          $html .= '"@type": "AggregateRating",';
			          $html .= '"bestRating": "5",';
			          $html .= '"ratingValue": "' . $data['rating_value'] . '",';
			          $html .= '"ratingCount": "' . $data['rating_count'] . '"';
			          $html .= '}';
			        }
			        if($data['range']){
			          $html .= ',"offers": {';
			          $html .= '"@type": "AggregateOffer",';
			          $html .= '"lowPrice": "' . $data['min'] . '",';
			          $html .= '"highPrice": "' . $data['max'] . '",';
			          $html .= '"offerCount": "' . $data['total'] . '",';
			          $html .= '"priceCurrency": "' . $data['code'] . '"';
			          $html .= '}';
			        }
			        $html .= '}';
			        $html .= '</script>';
			      $html .= '<!--microdatapro ' . $this->ver . ' product end [json-ld] -->';
			    }
			  }
			//product

			//gallery
			  if($data['images']){
			    if($data['syntax'] == "md" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery start[microdata] -->';
			      $html .= '<span itemscope itemtype="https://schema.org/ImageGallery">';
			      foreach($data['images'] as $image){
			        $html .= '<span itemprop="associatedMedia" itemscope itemtype="https://schema.org/ImageObject">';
			        $html .= '<meta itemprop="name" content="' . $image['name'] . '">';
			        $html .= '<meta itemprop="description" content="' . $image['name'] . '">';
			        $html .= '<link itemprop="thumbnailUrl" href="' . $image['thumb'] . '">';
			        $html .= '<link itemprop="contentUrl" href="' . $image['popup'] . '">';
			        $html .= '<meta itemprop="author" content="' . $data['author'] . '">';
			        $html .= '<meta itemprop="datePublished" content="' . $image['date_added'] . '">';
			        $html .= '</span>';
			      }
			      $html .= '</span>';
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery end [microdata] -->';
			    }
			    if($data['syntax'] == "ld" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery start [json-ld] -->';
			      $html .= '<script type="application/ld+json">';
			      $html .= '{';
			      $html .= '"@context": "https://schema.org",';
			      $html .= '"@type": "ImageGallery",';
			      $html .= '"associatedMedia":[';
			      $key_i = 1;
			      foreach($data['images'] as $image){
			        $html .= '{';
			        $html .= '"@type": "ImageObject",';
			        $html .= '"author": "' . $data['author'] . '",';
			        $html .= '"thumbnailUrl": "' . $image['thumb'] . '",';
			        $html .= '"contentUrl": "' . $image['popup'] . '",';
			        $html .= '"datePublished": "' . $image['date_added'] . '",';
			        $html .= '"description": "' . $image['name'] . '",';
			        $html .= '"name": "' . $image['name'] . '"';
			        $html .= '}';
			        if($key_i != count($data['images'])){
			          $html .= ',';
			        }
			        $key_i++;
			      }
			      $html .= ']';
			      $html .= '}';
			      $html .= '</script>';
			      $html .= '<!--microdatapro ' . $this->ver . ' gallery end [json-ld] -->';
			    }
			  }
			//gallery

			//list
			  if($data['list']){
			    $html .= '<!--microdatapro ' . $this->ver . ' list start [json-ld] -->';
			    $html .= '<script type="application/ld+json">';
			    $html .= '{';
			    $html .= '"@context":"https://schema.org",';
			    $html .= '"@type":"ItemList",';
			    $html .= '"itemListElement":[';
			    foreach($data['list'] as $list_key => $list_item){
			      $html .= '{';
			      $html .= '"@type":"ListItem",';
			      $html .= '"position":' . $list_key . ',';
			      $html .= '"url":"' . $list_item . '"';
			      $html .= '}';
			      if($data['list_count'] != $list_key){
			        $html .= ',';
			      }
			    }
			    $html .= ']';
			    $html .= '}';
			    $html .= '</script>';
			    $html .= '<!--microdatapro ' . $this->ver . ' list end [json-ld] -->';
			  }
			//list

	    return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
	  }
	//view_category_manufacturer

	//view_information
	  public function view_information($data){
	    $html = '';

			//breadcrumb
			  if($data['breadcrumbs']){
			    if($data['syntax'] == "md" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb start [microdata] -->';
			      $html .= '<span itemscope itemtype="https://schema.org/BreadcrumbList">';
			      foreach ($data['breadcrumbs'] as $key => $breadcrumb) {
			        $html .= '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
			        $html .= '<link itemprop="item" href="' . $breadcrumb['href'] . '">';
			        $html .= '<meta itemprop="name" content="' . $breadcrumb['text'] . '">';
			        $html .= '<meta itemprop="position" content="' . $key . '">';
			        $html .= '</span>';
			      }
			      $html .= '</span>';
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb end [microdata] -->';
			    }
			    if($data['syntax'] == "ld" or $data['syntax'] == "all"){
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb start [json-ld] -->';
			      $html .= '<script type="application/ld+json">';
			      $html .= '{';
			      $html .= '"@context": "https://schema.org",';
			      $html .= '"@type": "BreadcrumbList",';
			      $html .= '"itemListElement": [';
			      foreach ($data['breadcrumbs'] as $key => $breadcrumb) {
			        $html .= '{';
			        $html .= '"@type": "ListItem",';
			        $html .= '"position": ' . $key . ',';
			        $html .= '"item": {';
			        $html .= '"@id": "' . $breadcrumb['href'] . '",';
			        $html .= '"name": "' . $breadcrumb['text'] . '"';
			        $html .= '}';
			        $html .= '}';
			        if($key != count($data['breadcrumbs'])){
			          $html .= ',';
			        }
			      }
			      $html .= ']';
			      $html .= '}';
			      $html .= '</script>';
			      $html .= '<!--microdatapro ' . $this->ver . ' breadcrumb end [json-ld] -->';
			    }
			  }
			//breadcrumb

			//syntax json-ld
			  if($data['syntax'] == "ld" or $data['syntax'] == "all"){
			    $html .= '<!--microdatapro ' . $this->ver . ' information start [json-ld] -->';
			    $html .= '<script type="application/ld+json">';
			    $html .= '{';
			    $html .= '"@context": "https://schema.org",';
			    $html .= '"@type": "NewsArticle",';
			    $html .= '"mainEntityOfPage":{';
			    $html .= '"@type":"WebPage",';
			    $html .= '"@id": "' . $data['url'] . '"';
			    $html .= '},';
			    $html .= '"headline": "' . $data['name'] . '",';
			    $html .= '"image": {';
			    $html .= '"@type": "ImageObject",';
			    $html .= '"url": "' . $data['logo'] . '",';
			    $html .= '"width": "' . $data['image_width'] . '",';
			    $html .= '"height": "' . $data['image_height'] . '"';
			    $html .= '},';
			    $html .= '"datePublished": "' . $data['date'] . '",';
			    $html .= '"dateModified": "' . $data['date'] . '",';
			    $html .= '"author": {';
			    $html .= '"@type": "Person",';
			    $html .= '"name": "' . $data['author'] . '",';
			    $html .= '"url": "' . $data['author_url'] . '"';
			    $html .= '},';
			    $html .= '"publisher": {';
			    $html .= '"@type": "Organization",';
			    $html .= '"image": "' . $data['logo'] . '",';
			    $html .= '"name": "' . $data['author'] . '",';
			    $html .= '"logo": {';
			    $html .= '"@type": "ImageObject",';
			    $html .= '"url": "' . $data['logo'] . '"';
			    $html .= '}';
			    $html .= '},';
			    $html .= '"description":"' . $data['description'] . '"';
			    $html .= '}';
			    $html .= '</script>';
			    $html .= '<!--microdatapro ' . $this->ver . ' information end [json-ld] -->';
			  }
			//syntax json-ld

			//syntax md
			  if($data['syntax'] == "md" or $data['syntax'] == "all"){
			    $html .= '<!--microdatapro ' . $this->ver . ' information start [microdata] -->';
			    $html .= '<span itemscope itemtype="https://schema.org/NewsArticle">';
			    $html .= '<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="' . $data['url'] . '">';
			    $html .= '<meta itemprop="headline" content="' . $data['name'] . '">';
			    $html .= '<span itemprop="author" itemscope itemtype="https://schema.org/Person">';
			    $html .= '<meta itemprop="name" content="' . $data['author'] . '">';
			    $html .= '<link itemprop="url" href="' . $data['author_url'] . '">';
			    $html .= '</span>';
			    $html .= '<meta itemprop="description" content="' . $data['description'] . '">';
			    $html .= '<span itemprop="image" itemscope itemtype="https://schema.org/ImageObject">';
			    $html .= '<link itemprop="contentUrl" href="' . $data['logo'] . '">';
			    $html .= '<link itemprop="url" href="' . $data['logo'] . '">';
			    $html .= '<meta itemprop="width" content="' . $data['image_width'] . '">';
			    $html .= '<meta itemprop="height" content="' . $data['image_height'] . '">';
			    $html .= '</span>';
			    if($data['organization']){
			      $html .= '<span itemprop="publisher" itemscope itemtype="https://schema.org/Organization">';
			      $html .= '<meta itemprop="image" content="' . $data['logo'] . '">';
			      foreach($data['organization'] as $address){
			        $html .= '<span itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">';
			        $html .= '<meta itemprop="addressLocality" content="' . $address['addressLocality'] . '">';
			        $html .= '<meta itemprop="postalCode" content="' . $address['postalCode'] . '">';
			        $html .= '<meta itemprop="streetAddress" content="' . $address['streetAddress'] . '">';
			        $html .= '</span>';
			      }
			      if($data['phones']){
			        foreach($data['phones'] as $phone){
			          $html .= '<meta itemprop="telephone" content="' . $phone . '">';
			        }
			      }
			      $html .= '<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">';
			      $html .= '<link itemprop="url" href="' . $data['logo'] . '">';
			      $html .= '<link itemprop="contentUrl" href="' . $data['logo'] . '">';
			      $html .= '</span>';
			      $html .= '<meta itemprop="name" content="' . $data['author'] . '">';
			      $html .= '</span>';
			    }
			    $html .= '<meta itemprop="datePublished" content="' . $data['date'] . '">';
			    $html .= '<meta itemprop="dateModified" content="' . $data['date'] . '">';
			    $html .= '</span>';
			    $html .= '<!--microdatapro ' . $this->ver . ' information end [microdata] -->';
			  }
			//syntax md

	    return str_replace('><', '>' . PHP_EOL . '<', $html . PHP_EOL);
	  }
	//view_information

	//language
		public function language($word = false){
			$from = 'en';

			$from_data = explode('-', $this->info('lang'));

			if(array_intersect(array('uk','ua','ukrainian','ukraine'), $from_data)){
				$from = 'ua';
			}
			if(array_intersect(array('ru','fr','russian','rus'), $from_data)){
				$from = 'ru';
			}

			$language['en']['text_address'] = '50.4016991,30.2525147//Kyiv, Ukraine//Tarasa Shevchenko st, 2//02000';
			$language['en']['text_main_page'] = 'Home';
			$language['en']['heading_title'] = get_class() . ' - v ' .  $this->ver;
			$language['en']['text_module'] = 'Extension';
			$language['en']['text_microdata_status']  = 'Status';
			$language['en']['text_in_stock'] = 'In Stock';
			$language['en']['OutOfStock'] = 'Out Of Stock';
			$language['en']['LimitedAvailability'] = 'Limited Availability';
			$language['en']['InStoreOnly'] = 'InStore Only';
			$language['en']['OnlineOnly'] = 'Online Only';
			$language['en']['PreOrder'] = 'PreOrder';
			$language['en']['PreSale'] = 'PreSale';
			$language['en']['Discontinued'] = 'Discontinued';
			$language['en']['SoldOut'] = 'SoldOut';
			$language['en']['text_storetype_0'] = 'AutoPartsStore';
			$language['en']['text_storetype_1'] = 'BikeStore';
			$language['en']['text_storetype_2'] = 'BookStore';
			$language['en']['text_storetype_3'] = 'ClothingStore';
			$language['en']['text_storetype_4'] = 'ComputerStore';
			$language['en']['text_storetype_5'] = 'ConvenienceStore';
			$language['en']['text_storetype_6'] = 'DepartmentStore';
			$language['en']['text_storetype_7'] = 'ElectronicsStore';
			$language['en']['text_storetype_8'] = 'Florist';
			$language['en']['text_storetype_9'] = 'FurnitureStore';
			$language['en']['text_storetype_10'] = 'GardenStore';
			$language['en']['text_storetype_11'] = 'GroceryStore';
			$language['en']['text_storetype_12'] = 'HardwareStore';
			$language['en']['text_storetype_13'] = 'HobbyShop';
			$language['en']['text_storetype_14'] = 'HomeGoodsStore';
			$language['en']['text_storetype_15'] = 'JewelryStore';
			$language['en']['text_storetype_16'] = 'LiquorStore';
			$language['en']['text_storetype_17'] = 'MensClothingStore';
			$language['en']['text_storetype_18'] = 'MobilePhoneStore';
			$language['en']['text_storetype_19'] = 'MovieRentalStore';
			$language['en']['text_storetype_20'] = 'MusicStore';
			$language['en']['text_storetype_21'] = 'OfficeEquipmentStore';
			$language['en']['text_storetype_22'] = 'OutletStore';
			$language['en']['text_storetype_23'] = 'PawnShop';
			$language['en']['text_storetype_24'] = 'PetStore';
			$language['en']['text_storetype_25'] = 'ShoeStore';
			$language['en']['text_storetype_26'] = 'SportingGoodsStore';
			$language['en']['text_storetype_27'] = 'tireShop';
			$language['en']['text_storetype_28'] = 'toyStore';
			$language['en']['text_storetype_29'] = 'WholesaleStore';
			$language['en']['button_save'] = 'Save';
			$language['en']['text_edit'] = 'Edit MicrodataPro settings';
			$language['en']['button_cancel'] = '&larr; in the module';
			$language['en']['text_company'] = 'Store';
			$language['en']['text_config_hcard'] = 'Mark hCard microdata';
			$language['en']['text_config_hcard_h'] = 'hCard - microformat for publishing contact information';
			$language['en']['text_company_syntax'] = 'Company data markup syntax';
			$language['en']['text_company_syntax_all']= 'MICRODATA + JSON-LD';
			$language['en']['text_company_syntax_md'] = 'MICRODATA';
			$language['en']['text_company_syntax_ld'] = 'JSON-LD';
			$language['en']['text_entry_telephone'] = 'All company phones';
			$language['en']['text_entry_telephone_h'] = 'Separate with commas. Enter in the international format +12-345-678-90-00 For example: +48-345-678-90-00, +72-345-678-90-00';
			$language['en']['text_entry_group'] = 'Groups in social networks';
			$language['en']['text_entry_group_h'] = 'Separate with commas, for example: https://facebook.com/group, https://instagram.com/link';
			$language['en']['text_entry_address'] = 'Addresses (representative offices, delivery points)';
			$language['en']['text_entry_address_h'] = 'Each new address from a new line in the format: latitude;longitude//City, Country//Address//Postal Code Separator: "//"';
			$language['en']['text_entry_map'] = 'Map link';
			$language['en']['text_entry_map_h'] = 'Just copy the link to the map';
			$language['en']['text_product_page'] = 'Product';
			$language['en']['text_syntax'] = 'Page markup syntax';
			$language['en']['text_opengraph'] = 'Form open graph';
			$language['en']['text_opengraph_h'] = 'Works on the product page.';
			$language['en']['text_twitter_account'] = 'Your twitter account';
			$language['en']['text_twitter_account_h'] = 'If not filled in - twitter cards will not be displayed';
			$language['en']['text_meta_desc'] = 'Meta description in opengraph';
			$language['en']['text_meta_desc_h'] = 'If not included, the product description is displayed in Opengraph';
			$language['en']['text_breadcrumb'] = 'Mark breadcrumbs';
			$language['en']['text_other_data_h'] = 'Include only if the data is displayed on the product page';
			$language['en']['text_reviews'] = 'Mark reviews';
			$language['en']['text_related'] = 'Mark related products';
			$language['en']['text_attribute'] = 'Mark attributes (characteristics)';
			$language['en']['text_in_stock'] = 'the status is always available';
			$language['en']['text_in_stock_h'] = 'Even when the product is out of stock (quantity= 0) it will be marked inStock';
			$language['en']['text_information_page'] = 'Information';
			$language['en']['text_category_page'] = 'Category';
			$language['en']['text_manufacturer_page'] = 'Manufacturer';
			$language['en']['text_hide_price'] = 'Hide price';
			$language['en']['text_oh'] = 'Working hours:';
			$language['en']['text_monday'] = 'Monday:';
			$language['en']['text_tuesday'] = 'tuesday:';
			$language['en']['text_wednesday'] = 'Wednesday:';
			$language['en']['text_thursday'] = 'thursday:';
			$language['en']['text_friday'] = 'Friday:';
			$language['en']['text_saturday'] = 'Saturday:';
			$language['en']['text_sunday'] = 'Sunday:';
			$language['en']['text_information'] = 'Information about the module';
			$language['en']['text_check'] = 'Check micromarkup';
			$language['en']['text_check_h'] = 'Before checking, donʼt forget to save the module settings';
			$language['en']['text_contacts'] = 'Developer contacts';
			$language['en']['text_contacts_h'] = 'If you have any questions about the module, write to the developer';
			$language['en']['text_email'] = 'Mail:&nbsp;';
			$language['en']['text_site'] = 'Site:&nbsp;';
			$language['en']['text_faq'] = 'Most popular questions and answers';
			$language['en']['text_faq_h'] = 'Read, maybe you will find the answer to your question there';
			$language['en']['text_old_microdata'] = 'Old microdata detected in files:';
			$language['en']['text_clear'] = 'Clear';
			$language['en']['text_success_removed'] = 'Old micromarkup successfully removed';
			$language['en']['text_store_type'] = 'type of store';
			$language['en']['text_store_type_h']= 'If nothing fits - donʼt choose. Will Store';
			$language['en']['text_select']= 'Select';
			$language['en']['text_social'] = 'Social networks';
			$language['en']['text_other'] = 'Other';
			$language['en']['text_other_modules'] = 'Other modules of the author';
			$language['en']['text_opengraph_h'] = 'Some templates have a basic one, but MicrodataPro forms the most complete OpenGraph markup. It is recommended to enable.';
			$language['en']['text_age_group_desc'] = 'If the store is for children only or adults only, select settings. For regular stores, you donʼt have to choose anything';
			$language['en']['text_age_group'] = 'type of store for social networks';
			$language['en']['text_age_default'] = 'Normal store';
			$language['en']['text_age_children'] = 'For children';
			$language['en']['text_age_adult'] = 'For adults';
			$language['en']['text_target_gender_desc'] = 'target gender of the product - for whom the products are intended. If you are not sure - do not choose anything.';
			$language['en']['text_target_gender'] = 'target gender of the product';
			$language['en']['text_target_gender_female'] = 'For women';
			$language['en']['text_target_gender_male'] = 'For men';
			$language['en']['text_target_gender_unisex'] = 'Unisex';
			$language['en']['text_profile_id'] = 'Your facebook profile';
			$language['en']['text_profile_id_desc'] = 'In this field you need to insert a link to your Facebook profile, for example: https://www.facebook.com/nikolay.prut';
			$language['en']['text_check_license'] = 'Check module license';
			$language['en']['text_attr_color'] = 'Attribute for color';
			$language['en']['text_attr_material'] = 'Attribute for material';
			$language['en']['text_attr_size'] = 'Attribute for size';
			$language['en']['text_gallery'] = 'Mark additional photo of the product as a gallery';
			$language['en']['text_diagnostic'] = 'Diagnostics (<span style="color:#00b32d;">ok</span>)';
			$language['en']['text_diagnostic_e'] = 'Diagnostics';
			$language['en']['text_for_work'] = 'the MicrodataPro module requires very little to work:';
			$language['en']['text_succ_mod'] = 'the module modifier has been successfully installed';
			$language['en']['text_click_view'] = 'Click to view information';
			$language['en']['text_err_mod'] = 'the module modifier is not fully installed (problems:';
			$language['en']['text_file_original'] = 'Original file:';
			$language['en']['text_modok'] = 'can be modified';
			$language['en']['text_in_file'] = 'In the file';
			$language['en']['text_find_string'] = 'found string (or beginning) for binding:';
			$language['en']['text_code_in_file'] = 'Module code will be in';
			$language['en']['text_opencart_file'] = 'Store file:';
			$language['en']['text_none_mode'] = 'cannot be modified.';
			$language['en']['text_why_mod'] = 'How to fix?';
			$language['en']['text_in_file_strong'] = 'Need to be in the file';
			$language['en']['text_start_string'] = 'was the beginning of the string:';
			$language['en']['text_or'] = 'or';
			$language['en']['text_support_text'] = 'If not, add it. If it does not work, write <a href="https://microdata.pro/support/" target="_blank" title="Opens in a new window">MicrodataPro technical support</a><br>After editing the file <a href="#" class="refresh">refresh modifier cache</a> and go back to MicrodataPro to check';
			$language['en']['text_module_in_file'] = 'Module in file:';
			$language['en']['text_installed'] = 'installed';
			$language['en']['text_added_code_before'] = 'Successfully added code before the line:';
			$language['en']['text_module_code'] = '...module code...';
			$language['en']['text_not_installed'] = 'not installed.';
			$language['en']['text_what_to_do'] = 'What to do?';
			$language['en']['text_if_in_orig'] = 'If in the original file';
			$language['en']['text_all_good'] = 'everything is good, try to <a href="#" class="refresh">refresh the modifier cache</a> and the module code should register.<br>If not, review the comments on the original file, fix and <a href="#" class="refresh">refresh the mod cache</a> - should work.<br>Also, check if <b>MicrodataPro for Opencart ' . $this->ver . '</b> is installed in modifiers.';
			$language['en']['text_for_good_work'] = '<p style="margin-top:10px;"><b>For the module to work correctly, donʼt forget to <a href="#" class="refresh">update modifier cache</a> and reset all caches set on the site</b></p>';
			$language['en']['text_old_microdata_not_find'] = 'Old markup elements in the template are not found';
			$language['en']['text_old_info'] = '<p>It often happens that templates already have built-in basic markup, which in 99% of cases does not work correctly and gives errors.</p><p>When detected such , it is recommended to clean it.</p><p>MicrodataPro has a built-in tool for cleaning old markup.</p><p>In your case, there is no built-in markup, nothing needs to be cleaned.</p>';
			$language['en']['text_old_microdata_find'] = 'Found old markup elements in the template!';
			$language['en']['text_recoment_clear'] = 'It is recommended to clear';
			$language['en']['text_cleared_info'] = '<p>It often happens that templates already have built-in basic markup, which in 99% of cases does not work correctly and gives errors.</p><p>When detected such , it is recommended to clean it.</p><p>MicrodataPro has a built-in tool for cleaning old markup.</p><p>In your case, the module saw old markup elements, it is recommended to clean it.</p><p><b> It is important to understand</b> that the automatic cleaning of the markup requires a further check for the correctness of the cleaning.</p>';
			$language['en']['text_other_modules_find'] = 'Other markup modules/modifiers found';
			$language['en']['text_other_modules_not_find'] = 'You have no other markup applications found';
			$language['en']['text_other_modules_info'] = '<p><b>Item 2 can also cause this error</b>. First, make sure that everything is OK with it.</p><p>Sometimes it happens that stores have some add-ons for micro-marking.</p><p>As a rule, they only mark the basic data and very often with errors. </p><p>For MicrodataPro to work properly, you need to remove all other markup mods and <a href="#" class="refresh">clear the modifier cache</a>.</p>';
			$language['en']['text_not_other_modules_info'] = '<p>Sometimes it happens that stores have some add-ons for micro-marking.</p><p>As a rule, they mark only the basic data and very often with errors. </p><p>For MicrodataPro to work properly, you need to remove all other markup modules.</p><p>You are fine, there are no other add-ons. Only MicrodataPro works.</p>';
			$language['en']['text_google_check'] = 'Check the site pages in google validator';
			$language['en']['text_link_check'] = '<p><b>After configuring</b> the module, it is recommended to check all pages for the correct operation of MicrodataPro</p><p>You can check the pages using the links</ ';
			$language['en']['text_main_page'] = 'Main page';
			$language['en']['text_in_new_tab'] = 'Opens in a new tab';
			$language['en']['text_info_page'] = 'Information page';
			$language['en']['text_empty_category'] = '<li>Category - there are no categories in the store yet</li>';
			$language['en']['text_empty_product'] = '<li>Product - there are no products in the store yet</li>';
			$language['en']['text_empty_brand'] = '<li>Manufacturer - there are no manufacturers in the store yet</li>';
			$language['en']['text_empty_info'] = '<li>Information page - there are no information pages in the store yet</li>';
			$language['en']['text_google_validator'] = '<p>If any page is not available, check if maintenance mode is enabled in the store settings, or insert any other page (products, categories, etc.) in <a href="https://search.google.com/structured-data/testing-tool" target="_blank" title="Opens in a new window">google validator</a></p>';
			$language['en']['text_mod_cleared'] = 'the cache of modifiers has been successfully updated, the page will be reloaded!';
			$language['en']['text_thanks_new'] = '<h3>Thank you for using the MicrodataPro module.</h3><h4>This is a great choice, I hope it meets all your expectations! <span style="color:#00b32d;">:)</span></h4><p>Sincerely, module developer <a href="https://t.me/PrutNikolay" target="_blank"> Prut Mykola</a></p>';
			$language['en']['text_yandex_validator'] = 'Y Yandex.Webmaster micromarkup validator';
			$language['en']['text_reviews_mod'] = 'Module reviews';
			$language['en']['text_on_site'] = 'on';
			$language['en']['text_old_microdata_deleted'] = 'Old markup elements deleted, reload the page';
			$language['en']['text_price_from_to'] = 'Mark prices from and to';
			$language['en']['text_all_rating'] = 'Mark the general rating of products';
			$language['en']['text_images_to_gallery'] = 'Mark product photos as a gallery';
			$language['en']['text_product_aggregator'] = 'Only for product aggregator!';
			$language['en']['text_category_rating'] = 'Rating of products in the category';
			$language['en']['text_support_info'] = 'Before writing to info@microdata.pro support, look here: <a href="https://microdata.pro/faq/" title="Opens in a new in the window" target="_blank" data-toggle="tooltip">https://microdata.pro/faq/</a><br>If you find it difficult to understand, it is better to contact the author of the module.';
			$language['en']['text_warning_info'] = 'Be sure to read the questions/answers: <a href="https://microdata.pro/faq/" target="_blank">https://microdata. pro/ faq/</a><br>Warnings in google and errors in Yandex: <a href="https://microdata.pro/blog/google-search-console-warning" target="_blank">https: // microdata.pro/blog/google-search-console-warning</a><br>Developer Telegram: <strong>@PrutNikolay</strong><br>Mail: <strong>info@microdata.pro</strong >' ;
			$language['en']['text_page_info'] = 'Attention! By specifying the data here, you must be sure that the visitor also sees it on the site pages. If this is not on the site, it is better to disable the store markup or add information to the site. Also remember that the address must be entered in coordinate format. //City, Country//Address//Postal code</b>';
			$language['en']['text_logo_size'] = 'Logo sizes';
			$language['en']['text_logo_alt'] = 'Alternative logo for markup';
			$language['en']['text_cron_key'] = 'Key for CRON price sampling link';
			$language['en']['text_recommended_to_fill'] = 'recommended to fill';
			$language['en']['text_products_carousel'] = 'Mark category products as carousel';
			$language['en']['text_photo_in_list'] = 'Mark photo in list';
			$language['en']['text_preview_size'] = 'Preview sizes';
			$language['en']['text_thumb_size'] = 'Sizes of large photos';
			$language['en']['text_status_from_status'] = 'Status in markup with product status:';
			$language['en']['text_shipping_cost'] = 'Shipping cost';
			$language['en']['text_shipping_cost_default'] = 'default 50';
			$language['en']['text_shipping_country'] = 'Shipping country';
			$language['en']['text_shipping_country_default'] = 'default US';
			$language['en']['text_product_agregator'] = 'Only for product agregator';
			$language['en']['text_shipping_time1_min'] = 'Minimum order processing time';
			$language['en']['text_shipping_time1_min_default'] = 'default 0';
			$language['en']['text_shipping_time1_max'] = 'Maximum order processing time';
			$language['en']['text_shipping_time1_max_default'] = 'default 1';
			$language['en']['text_shipping_time2_min'] = 'Minimum delivery time after order processing';
			$language['en']['text_shipping_time2_min_default'] = 'default 1';
			$language['en']['text_shipping_time2_max'] = 'Maximum delivery time after order processing';
			$language['en']['text_shipping_time2_max_default'] = 'default 3';
			$language['en']['text_return_days'] = 'Product return days';
			$language['en']['text_return_days_default'] = 'default is 14';
			$language['en']['text_no_active'] = '<div style="font-size:22px;text-align:center;margin-top:20px;"><h3><strong>Sorry, MicrodataPro couldnʼt automatically pull up the license key for this domain</strong></h3><small style="display:block;margin:10px 0;font-size:16px;">It happens, itʼs okay</small><div class= "alert alert-info" style="font-size:14px;line-height:16px;">To manually collect the key, follow the link: <a target="_blank" href="https://microdata.pro/key?s=' .base64_encode($this->info('site')) .'">https://microdata.pro/key</a> and paste it in the box below</div><div><textarea name="txt" id="key_manual" class="form-control" placeholder="Paste your license key here and wait a couple of seconds"></textarea></div><div style="margin-top:15px;border :1px solid #00C962;padding:15px;font-size:12px;"><p>Thanks for waiting, I hope that after activation the module will justify all your hopes. Sincerely, MicrodataPro developer Nikolay</p><p>In the meantime, you can read about the moduleʼs capabilities and see instructions on the site <a target="_blank" title="Opens in a new tab" href="https://microdata.pro" >https://microdata.pro</a></p></div></div>';

			$language['ua']['text_address'] = '50.4016991,30.2525147//Київ, Україна//бул. Тараса Шевченко 2//02000';
			$language['ua']['text_main_page'] = 'Головна';
			$language['ua']['heading_title'] = get_class() . ' - v ' .  $this->ver;
			$language['ua']['text_module'] = 'Доповнення';
			$language['ua']['text_microdata_status']  = 'Статус';
			$language['ua']['text_in_stock'] = 'В наявності';
			$language['ua']['OutOfStock'] = 'Немає в наявності';
			$language['ua']['LimitedAvailability'] = 'Обмежена кількість';
			$language['ua']['InStoreOnly'] = 'Доступний тільки в фізичному магазині';
			$language['ua']['OnlineOnly'] = 'Доступний тільки в онлайн';
			$language['ua']['PreOrder'] = 'Під замовлення';
			$language['ua']['PreSale'] = 'Попередня покупка';
			$language['ua']['Discontinued'] = 'Знятий з виробництва';
			$language['ua']['SoldOut'] = 'Товар розпроданий';
			$language['ua']['text_storetype_0'] = 'Автозапчастини';
			$language['ua']['text_storetype_1'] = 'Веломагазин';
			$language['ua']['text_storetype_2'] = 'Книгарня';
			$language['ua']['text_storetype_3'] = 'Магазин одягу';
			$language['ua']['text_storetype_4'] = 'Компʼютерний магазин';
			$language['ua']['text_storetype_5'] = 'Невеликий магазин';
			$language['ua']['text_storetype_6'] = 'Універсальний магазин';
			$language['ua']['text_storetype_7'] = 'Магазин електроніки';
			$language['ua']['text_storetype_8'] = 'Флористичний магазин';
			$language['ua']['text_storetype_9'] = 'Меблевий магазин';
			$language['ua']['text_storetype_10'] = 'Садовий магазин';
			$language['ua']['text_storetype_11'] = 'Продуктовий магазин';
			$language['ua']['text_storetype_12'] = 'Господарський магазин';
			$language['ua']['text_storetype_13'] = 'Хобі';
			$language['ua']['text_storetype_14'] = 'Продукти для дому';
			$language['ua']['text_storetype_15'] = 'Ювелірний магазин';
			$language['ua']['text_storetype_16'] = 'Лікеро-горілчаний магазин';
			$language['ua']['text_storetype_17'] = 'Магазин чоловічого одягу';
			$language['ua']['text_storetype_18'] = 'Магазин мобільних телефонів';
			$language['ua']['text_storetype_19'] = 'Магазин фільмів';
			$language['ua']['text_storetype_20'] = 'Музичний магазин';
			$language['ua']['text_storetype_21'] = 'Магазин офісного обладнання';
			$language['ua']['text_storetype_22'] = 'Стоковий магазин';
			$language['ua']['text_storetype_23'] = 'Ломбард';
			$language['ua']['text_storetype_24'] = 'Зоомагазин';
			$language['ua']['text_storetype_25'] = 'Взуттєвий магазин';
			$language['ua']['text_storetype_26'] = 'Магазин спортивних товарів';
			$language['ua']['text_storetype_27'] = 'Магазин шин та дисків';
			$language['ua']['text_storetype_28'] = 'Магазин іграшок';
			$language['ua']['text_storetype_29'] = 'Оптовий магазин';
			$language['ua']['button_save'] = 'Зберегти';
			$language['ua']['text_edit'] = 'Редагувати налаштування MicrodataPro';
			$language['ua']['button_cancel'] = '&larr; у модулі';
			$language['ua']['text_company'] = 'Магазин';
			$language['ua']['text_config_hcard'] = 'Розмічати мікродані hCard';
			$language['ua']['text_config_hcard_h'] = 'hCard - мікроформат для публікації контактної інформації';
			$language['ua']['text_company_syntax'] = 'Синтаксис розмітки даних про компанію';
			$language['ua']['text_company_syntax_all']= 'MICRODATA + JSON-LD';
			$language['ua']['text_company_syntax_md'] = 'MICRODATA';
			$language['ua']['text_company_syntax_ld'] = 'JSON-LD';
			$language['ua']['text_entry_telephone'] = 'Всі телефони компанії';
			$language['ua']['text_entry_telephone_h'] = 'Розділяти комами. Вводити у міжнародному форматі +12-345-678-90-00 Наприклад: +48-345-678-90-00, +72-345-678-90-00';
			$language['ua']['text_entry_group'] = 'Групи в соціальних мережах';
			$language['ua']['text_entry_group_h'] = 'Розділяти комами, Наприклад: https://facebook.com/group, https://instagram.com/link';
			$language['ua']['text_entry_address'] = 'Адреси (представництва, пункти видачі)';
			$language['ua']['text_entry_address_h'] = 'Кожна нова адреса з нового рядка у форматі: latitude;longitude//Місто, Країна//Адреса//Поштовий код Розділювач: "//"';
			$language['ua']['text_entry_map'] = 'Посилання на карту';
			$language['ua']['text_entry_map_h'] = 'Необхідно просто скопіювати посилання на карту';
			$language['ua']['text_product_page'] = 'Товар';
			$language['ua']['text_syntax'] = 'Синтаксис розмітки сторінки';
			$language['ua']['text_opengraph'] = 'Формувати open graph';
			$language['ua']['text_opengraph_h'] = 'Працює на сторінці товару.';
			$language['ua']['text_twitter_account'] = 'Ваш обліковий запис twitter';
			$language['ua']['text_twitter_account_h'] = 'якщо не заповнити - twitter cards не буде виведено';
			$language['ua']['text_meta_desc'] = 'Meta description в opengraph';
			$language['ua']['text_meta_desc_h'] = 'якщо не включено, в Opengraph виводиться опис товару';
			$language['ua']['text_breadcrumb'] = 'Розмічати хлібні крихти';
			$language['ua']['text_other_data_h'] = 'Включати лише якщо дані виведені на сторінці товару';
			$language['ua']['text_reviews'] = 'Розмічати відгуки';
			$language['ua']['text_related'] = 'Розмічати супутні товари';
			$language['ua']['text_attribute'] = 'Розмічати атрибути (характеристики)';
			$language['ua']['text_in_stock'] = 'В наявності';
			$language['ua']['text_in_stock_h'] = 'Навіть, коли товар не в наявності (кількість= 0) він буде в розмітці inStock';
			$language['ua']['text_information_page'] = 'Інформація';
			$language['ua']['text_category_page'] = 'Категорія';
			$language['ua']['text_manufacturer_page'] = 'Виробник';
			$language['ua']['text_hide_price'] = 'Приховати ціну';
			$language['ua']['text_oh'] = 'Час роботи:';
			$language['ua']['text_monday'] = 'Понеділок:';
			$language['ua']['text_tuesday'] = 'Вівторок:';
			$language['ua']['text_wednesday'] = 'Середа:';
			$language['ua']['text_thursday'] = 'Четвер:';
			$language['ua']['text_friday'] = 'Пʼятниця:';
			$language['ua']['text_saturday'] = 'Субота:';
			$language['ua']['text_sunday'] = 'Неділя:';
			$language['ua']['text_information'] = 'Інформація про модуль';
			$language['ua']['text_check'] = 'Перевірити мікророзмітку';
			$language['ua']['text_check_h'] = 'Перед перевіркою не забудьте зберегти налаштування модуля';
			$language['ua']['text_contacts'] = 'Контакти розробника';
			$language['ua']['text_contacts_h'] = 'якщо є питання щодо модуля, напишіть розробнику';
			$language['ua']['text_email'] = 'Пошта:&nbsp;';
			$language['ua']['text_site'] = 'Сайт:&nbsp;';
			$language['ua']['text_faq'] = 'Найпопулярніші запитання та відповіді';
			$language['ua']['text_faq_h'] = 'Почитайте, можливо Ви знайдете там відповідь на своє запитання';
			$language['ua']['text_old_microdata'] = 'Виявлено стару мікророзмітку у файлах:';
			$language['ua']['text_clear'] = 'Очистити';
			$language['ua']['text_success_removed'] = 'Стару мікророзмітку успішно видалено';
			$language['ua']['text_store_type'] = 'Тип магазину';
			$language['ua']['text_store_type_h']= 'якщо нічого не підходить - не вибирайте. Буде Store';
			$language['ua']['text_select']= 'Вибрати';
			$language['ua']['text_social'] = 'Соціальні мережі';
			$language['ua']['text_other'] = 'Інше';
			$language['ua']['text_other_modules'] = 'Інші модулі автора';
			$language['ua']['text_opengraph_h'] = 'У деяких шаблонах базова є, але MicrodataPro формує максимально повну розмітку OpenGraph. Рекомендується вмикати.';
			$language['ua']['text_age_group_desc'] = 'якщо магазин призначений лише для дітей або лише для дорослих, виберіть налаштування. Для звичайних магазинів нічого не треба вибирати';
			$language['ua']['text_age_group'] = 'Тип магазину для соціальних мереж';
			$language['ua']['text_age_default'] = 'Звичайний магазин';
			$language['ua']['text_age_children'] = 'Для дітей';
			$language['ua']['text_age_adult'] = 'Для дорослих';
			$language['ua']['text_target_gender_desc'] = 'Цільова стать продукту - для кого призначені товари. Якщо не впевнені - нічого не вибирайте.';
			$language['ua']['text_target_gender'] = 'Цільова стать товару';
			$language['ua']['text_target_gender_female'] = 'Для жінок';
			$language['ua']['text_target_gender_male'] = 'Для чоловіків';
			$language['ua']['text_target_gender_unisex'] = 'Унісекс';
			$language['ua']['text_profile_id'] = 'Ваш профіль facebook';
			$language['ua']['text_profile_id_desc'] = 'У це поле потрібно вставити посилання на профіль у facebook, наприклад: https://www.facebook.com/nikolay.prut';
			$language['ua']['text_check_license'] = 'Перевірити ліцензію модуля';
			$language['ua']['text_attr_color'] = 'Атрибут для кольору';
			$language['ua']['text_attr_material'] = 'Атрибут для матеріалу';
			$language['ua']['text_attr_size'] = 'Атрибут для розміру';
			$language['ua']['text_gallery'] = 'Розмічати доп.фото товару як галерею';
			$language['ua']['text_diagnostic'] = 'Діагностика (<span style="color:#00b32d;">ок</span>)';
			$language['ua']['text_diagnostic_e'] = 'Діагностика';
			$language['ua']['text_for_work'] = 'Для роботи модуля MicrodataPro необхідно зовсім небагато:';
			$language['ua']['text_succ_mod'] = 'Модифікатор модуля успішно встановлений';
			$language['ua']['text_click_view'] = 'Клікніть, щоб переглянути інформацію';
			$language['ua']['text_err_mod'] = 'Модифікатор модуля не повністю встановився (проблем:';
			$language['ua']['text_file_original'] = 'Файл оригінал:';
			$language['ua']['text_modok'] = 'можна модифікувати';
			$language['ua']['text_in_file'] = 'У файлі';
			$language['ua']['text_find_string'] = 'знайдено рядок (або початок) для привʼязки:';
			$language['ua']['text_code_in_file'] = 'Код модуля буде в';
			$language['ua']['text_opencart_file'] = 'Файл магазину:';
			$language['ua']['text_none_mode'] = 'не можна модифікувати.';
			$language['ua']['text_why_mod'] = 'як виправити?';
			$language['ua']['text_in_file_strong'] = 'Необхідно щоб у файлі';
			$language['ua']['text_start_string'] = 'було початок рядка:';
			$language['ua']['text_or'] = 'або';
			$language['ua']['text_support_text'] = 'якщо ні, додайте. Якщо не виходить, пишіть <a href="https://microdata.pro/support/" target="_blank" title="Відкриється в новому вікні">технічну підтримку MicrodataPro</a><br>Після правки файлу <a href="#" class="refresh">оновіть кеш модифікаторів</a> і зайдіть знову до MicrodataPro для контролю';
			$language['ua']['text_module_in_file'] = 'Модуль у файл:';
			$language['ua']['text_installed'] = 'встановлений';
			$language['ua']['text_added_code_before'] = 'Успішно доданий код перед рядком:';
			$language['ua']['text_module_code'] = '...код модуля...';
			$language['ua']['text_not_installed'] = 'не встановлено.';
			$language['ua']['text_what_to_do'] = 'Що робити?';
			$language['ua']['text_if_in_orig'] = 'якщо в оригінальному файлі';
			$language['ua']['text_all_good'] = 'все добре, спробуйте <a href="#" class="refresh">оновити кеш модифікаторів</a> і код модуля повинен прописатися.<br>Якщо ні, перегляньте зауваження щодо файлу оригіналу, виправте і <a href="#" class="refresh">оновіть кеш модифікаторів</a> - має все заробити.<br>Також, перевірте, чи встановлено <b>MicrodataPro for Opencart ' . $this->ver . '</b> у модифікаторах.';
			$language['ua']['text_for_good_work'] = '<p style="margin-top:10px;"><b>Для коректної роботи модуля не забудьте <a href="#" class="refresh">оновити кеш модифікаторів</a> та обнулити всі кеші встановлені на сайті</b></p>';
			$language['ua']['text_old_microdata_not_find'] = 'Старі елементи розмітки в шаблоні не знайдені';
			$language['ua']['text_old_info'] = '<p>Часто буває, що в шаблонах є вже вбудована базова розмітка, яка в 99% випадках працює не коректно і дає помилки.</p><p>При виявленні такої , рекомендується її чистити.</p><p>MicrodataPro має вбудований інструмент очищення старої розмітки.</p><p>У Вашому випадку вбудованої розмітки немає, чистити нічого не треба.</p>';
			$language['ua']['text_old_microdata_find'] = 'Знайдені старі елементи розмітки у шаблоні!';
			$language['ua']['text_recoment_clear'] = 'Рекомендується очистити';
			$language['ua']['text_cleared_info'] = '<p>Часто буває, що в шаблонах є вже вбудована базова розмітка, яка в 99% випадках працює не коректно і дає помилки.</p><p>При виявленні такої , рекомендується її чистити.</p><p>MicrodataPro має вбудований інструмент очищення старої розмітки.</p><p>У Вашому випадку модуль побачив старі елементи розмітки, рекомендується почистити.</p><p><b>Важливо розуміти</b> що автоматичне чищення розмітки потребує подальшої перевірки на коректність очищення.</p>';
			$language['ua']['text_other_modules_find'] = 'Виявлено інші модулі/модифікатори розмітки';
			$language['ua']['text_other_modules_not_find'] = 'У Вас не виявлено інших додатків розмітки';
			$language['ua']['text_other_modules_info'] = '<p><b>Цю помилку може викликати і 2 пункт</b>. Для початку переконайтеся, що з ним все гаразд.</p><p>Іноді буває, що в магазинах є якісь доповнення для мікророзмітки.</p><p>Як правило, вони розмічають лише базові дані і дуже часто з помилками .</p><p>Для правильної роботи MicrodataPro необхідно видалити всі інші моди розмітки та <a href="#" class="refresh">почистити кеш модифікаторів</a>.</p>';
			$language['ua']['text_not_other_modules_info'] = '<p>Іноді буває, що в магазинах є якісь доповнення для мікророзмітки.</p><p>Як правило, вони розмічають тільки базові дані і дуже часто з помилками .</p><p>Для правильної роботи MicrodataPro треба прибрати всі інші модулі для розмітки.</p><p>У Вас все добре, інших доповнень немає. Працює лише MicrodataPro.</p>';
			$language['ua']['text_google_check'] = 'Перевірте сторінки сайту у валідаторе google';
			$language['ua']['text_link_check'] = '<p><b>Після налаштування</b> модуля, рекомендується перевірити всі сторінки на коректність роботи MicrodataPro</p><p>Перевірити сторінки можна за посиланнями</ ';
			$language['ua']['text_main_page'] = 'Головна сторінка';
			$language['ua']['text_in_new_tab'] = 'Відкриється в новій вкладці';
			$language['ua']['text_info_page'] = 'Інформаційна сторінка';
			$language['ua']['text_empty_category'] = '<li>Категорія - в магазині поки що немає категорій</li>';
			$language['ua']['text_empty_product'] = '<li>Товар - у магазині поки що немає товарів</li>';
			$language['ua']['text_empty_brand'] = '<li>Виробник - у магазині поки що немає виробників</li>';
			$language['ua']['text_empty_info'] = '<li>Інформаційна сторінка - у магазині поки що немає інформаційних сторінок</li>';
			$language['ua']['text_google_validator'] = '<p>Якщо будь-яка сторінка недоступна, перевірте, чи не ввімкнено режим обслуговування в налаштуваннях магазину, або вставте будь-яку іншу сторінку (товари, категорії тощо) у <a href="https://search.google.com/structured-data/testing-tool" target="_blank" title="Відкриється в новому вікні">валідатор google</a></p>';
			$language['ua']['text_mod_cleared'] = 'Кеш модифікаторів успішно оновлено, сторінку буде перезавантажено!';
			$language['ua']['text_thanks_new'] = '<h3>Дякую за використання модуля MicrodataPro.</h3><h4>Це відмінний вибір, сподіваюся він виправдає всі Ваші очікування! <span style="color:#00b32d;">:)</span></h4><p>З повагою розробник модуля <a href="https://t.me/PrutNikolay" target="_blank"> Прут Микола</a></p>';
			$language['ua']['text_yandex_validator'] = 'Y Валідатор мікророзмітки Яндекс.Вебмайстер';
			$language['ua']['text_reviews_mod'] = 'Відгуки про модуль';
			$language['ua']['text_on_site'] = 'на';
			$language['ua']['text_old_microdata_deleted'] = 'Старі елементи розмітки видалені, перезавантажте сторінку';
			$language['ua']['text_price_from_to'] = 'Розмічати ціни від і до';
			$language['ua']['text_all_rating'] = 'Розмічати загальний рейтинг товарів';
			$language['ua']['text_images_to_gallery'] = 'Розмічати фото товарів як галерею';
			$language['ua']['text_product_agregator'] = 'Тільки для агрегатора товарів!';
			$language['ua']['text_category_rating'] = 'Рейтинг товарів у категорії';
			$language['ua']['text_support_info'] = 'Перед тим як написати в підтримку info@microdata.pro подивіться тут: <a href="https://microdata.pro/faq/" title="Відкриється в новому вікні" target="_blank" data-toggle="tooltip">https://microdata.pro/faq/</a><br>Якщо вам важко розібратися, краще зверніться до автора модуля.';
			$language['ua']['text_warning_info'] = 'Обовʼязково почитайте питання/відповіді: <a href="https://microdata.pro/faq/" target="_blank">https://microdata.pro/ faq/</a><br>Попередження у google та помилки в яндекс: <a href="https://microdata.pro/blog/google-search-console-warning" target="_blank">https:// microdata.pro/blog/google-search-console-warning</a><br>Телеграм розробника: <strong>@PrutNikolay</strong><br>Пошта: <strong>info@microdata.pro</strong>' ;
			$language['ua']['text_page_info'] = 'Увага! Вказуючи дані тут ви повинні бути впевнені, що відвідувач це також бачить на сторінках сайту. Якщо цього немає на сайті, краще вимкнути розмітку магазину або додати інформацію на сайт. Також памʼятайте, що адресу треба вводити у форматі координати. //Місто, Країна//Адреса//Поштовий код</b>';
			$language['ua']['text_logo_size'] = 'Розміри логотипу';
			$language['ua']['text_logo_alt'] = 'Альтернативний логотип для розмітки';
			$language['ua']['text_cron_key'] = 'Ключ для CRON посилання вибірки цін';
			$language['ua']['text_recommended_to_fill'] = 'рекомендується заповнювати';
			$language['ua']['text_products_carousel'] = 'Розмічати товари категорії як карусель';
			$language['ua']['text_photo_in_list'] = 'Розмічати фото у списку';
			$language['ua']['text_preview_size'] = 'Розміри превʼю';
			$language['ua']['text_thumb_size'] = 'Розміри великих фото';
			$language['ua']['text_status_from_status'] = 'Статус в розмітці при статусі товара:';
			$language['ua']['text_shipping_cost'] = 'Вартість доставки';
			$language['ua']['text_shipping_cost_default'] = 'за замовчуванням 50';
			$language['ua']['text_shipping_country'] = 'Країна доставки';
			$language['ua']['text_shipping_country_default'] = 'за замовчуванням US';
			$language['ua']['text_shipping_time1_min'] = 'Мінімальний час обробки замовлення (дні)';
			$language['ua']['text_shipping_time1_min_default'] = 'за замовчуванням 0';
			$language['ua']['text_shipping_time1_max'] = 'Максимальний час обробки замовлення (дні)';
			$language['ua']['text_shipping_time1_max_default'] = 'за замовчуванням 1';
			$language['ua']['text_shipping_time2_min'] = 'Мінімальний час доставки після обробки замовлення (дні)';
			$language['ua']['text_shipping_time2_min_default'] = 'за замовчуванням 1';
			$language['ua']['text_shipping_time2_max'] = 'Максимальний час доставки після обробки замовлення (дні)';
			$language['ua']['text_shipping_time2_max_default'] = 'за замовчуванням 3';
			$language['ua']['text_return_days'] = 'Повернення товару протягом (дні)';
			$language['ua']['text_return_days_default'] = 'за замовчуванням 14';
			$language['ua']['text_no_active'] = '<div style="font-size:22px;text-align:center;margin-top:20px;"><h3><strong>На жаль MicrodataPro не зміг автоматично підтягнути ключ ліцензії на цей домен</strong></h3><small style="display:block;margin:10px 0;font-size:16px;">Таке буває, нічого страшного</small><div class= "alert alert-info" style="font-size:14px;line-height:16px;">Щоб вручну забрати ключ, перейдіть за посиланням: <a target="_blank" href="https://microdata.pro/key?s=' . base64_encode($this->info('site')) . '">https://microdata.pro/key</a> і вставте його в поле нижче</div><div><textarea name="txt" id="key_manual" class="form-control" placeholder="Вставте сюди ключ ліцензії та зачекайте кілька секунд"></textarea></div><div style="margin-top:15px;border :1px solid #00C962;padding:15px;font-size:12px;"><p>Дякую за очікування, сподіваюся, що після активації модуль виправдає всі ваші надії. З повагою розробник MicrodataPro Микола</p><p>А поки що можна почитати про можливості модуля та переглянути інструкції на сайті <a target="_blank" title="Відкриється в новій вкладці" href="https://microdata.pro" >https://microdata.pro</a></p></div></div>';

			$language['ru']['text_address'] = '50.4016991,30.2525147//Киев, Украина//бул. Тараса Шевченко 2//02000';
			$language['ru']['text_main_page'] = 'Главная';
			$language['ru']['heading_title'] = get_class() . ' - v ' .  $this->ver;
			$language['ru']['text_module'] = 'Дополнения';
			$language['ru']['text_microdata_status']  = 'Статус';
			$language['ru']['text_in_stock'] = 'В наличии';
			$language['ru']['OutOfStock'] = 'Нет в наличии';
			$language['ru']['LimitedAvailability'] = 'Ограниченное количество';
			$language['ru']['InStoreOnly'] = 'Доступен только в физическом магазине';
			$language['ru']['OnlineOnly'] = 'Доступен только в онлайн';
			$language['ru']['PreOrder'] = 'Под заказ';
			$language['ru']['PreSale'] = 'Предварительная покупка';
			$language['ru']['Discontinued'] = 'Снят с производства';
			$language['ru']['SoldOut'] = 'Товар распродан';
			$language['ru']['text_storetype_0'] = 'Автозапчасти';
			$language['ru']['text_storetype_1'] = 'Веломагазин';
			$language['ru']['text_storetype_2'] = 'Книжный магазин';
			$language['ru']['text_storetype_3'] = 'Магазин одежды';
			$language['ru']['text_storetype_4'] = 'Компьютерный магазин';
			$language['ru']['text_storetype_5'] = 'Небольшой магазин';
			$language['ru']['text_storetype_6'] = 'Универсальный магазин';
			$language['ru']['text_storetype_7'] = 'Магазин электроники';
			$language['ru']['text_storetype_8'] = 'Флористический магазин';
			$language['ru']['text_storetype_9'] = 'Мебельный магазин';
			$language['ru']['text_storetype_10'] = 'Садовый магазин';
			$language['ru']['text_storetype_11'] = 'Продуктовый магазин';
			$language['ru']['text_storetype_12'] = 'Хозяйственный магазин';
			$language['ru']['text_storetype_13'] = 'Хобби';
			$language['ru']['text_storetype_14'] = 'Продукты для дома';
			$language['ru']['text_storetype_15'] = 'Ювелирный магазин';
			$language['ru']['text_storetype_16'] = 'Ликеро-водочный магазин';
			$language['ru']['text_storetype_17'] = 'Магазин мужской одежды';
			$language['ru']['text_storetype_18'] = 'Магазин мобильных телефонов';
			$language['ru']['text_storetype_19'] = 'Магазин фильмов';
			$language['ru']['text_storetype_20'] = 'Музыкальный магазин';
			$language['ru']['text_storetype_21'] = 'Магазин офисного оборудования';
			$language['ru']['text_storetype_22'] = 'Стоковый магазин';
			$language['ru']['text_storetype_23'] = 'Ломбард';
			$language['ru']['text_storetype_24'] = 'Зоомагазин';
			$language['ru']['text_storetype_25'] = 'Обувной магазин';
			$language['ru']['text_storetype_26'] = 'Магазин спортивных товаров';
			$language['ru']['text_storetype_27'] = 'Магазин шин и дисков';
			$language['ru']['text_storetype_28'] = 'Магазин игрушек';
			$language['ru']['text_storetype_29'] = 'Оптовый магазин';
			$language['ru']['button_save'] = 'Сохранить';
			$language['ru']['text_edit'] = 'Редактировать настройки MicrodataPro';
			$language['ru']['button_cancel'] = '&larr; В модули';
			$language['ru']['text_company'] = 'Магазин';
			$language['ru']['text_config_hcard'] = 'Размечать микроданные hCard';
			$language['ru']['text_config_hcard_h'] = 'hCard - микроформат для публикации контактной информации';
			$language['ru']['text_company_syntax'] = 'Синтаксис разметки данных о компании';
			$language['ru']['text_company_syntax_all']= 'MICRODATA + JSON-LD';
			$language['ru']['text_company_syntax_md'] = 'MICRODATA';
			$language['ru']['text_company_syntax_ld'] = 'JSON-LD';
			$language['ru']['text_entry_telephone'] = 'Все телефоны компании';
			$language['ru']['text_entry_telephone_h'] = 'Разделять запятыми. Вводить в международном формате +12-345-678-90-00 Например: +48-345-678-90-00,+72-345-678-90-00';
			$language['ru']['text_entry_group'] = 'Группы в социальных сетях';
			$language['ru']['text_entry_group_h'] = 'Разделять запятыми, Например: https://facebook.com/group, https://instagram.com/link';
			$language['ru']['text_entry_address'] = 'Адреса (представительства, пункты выдачи)';
			$language['ru']['text_entry_address_h'] = 'Каждый новый адрес с новой строки в формате: latitude;longitude//Город, Страна//Адрес//Почтовый код Разделитель: "//"';
			$language['ru']['text_entry_map'] = 'Ссылка на карту';
			$language['ru']['text_entry_map_h'] = 'Необходимо просто скопировать ссылку на карту';
			$language['ru']['text_product_page'] = 'Товар';
			$language['ru']['text_syntax'] = 'Синтаксис разметки страницы';
			$language['ru']['text_opengraph'] = 'Формировать open graph';
			$language['ru']['text_opengraph_h'] = 'Работает на странице товара.';
			$language['ru']['text_twitter_account'] = 'Ваш twitter аккаунт';
			$language['ru']['text_twitter_account_h'] = 'Если не заполнить - twitter cards не будет выведен';
			$language['ru']['text_meta_desc'] = 'Meta description в opengraph';
			$language['ru']['text_meta_desc_h'] = 'Если не включено, в opengraph выводится описание товара';
			$language['ru']['text_breadcrumb'] = 'Размечать хлебные крошки';
			$language['ru']['text_other_data_h'] = 'Включать только если данные выведены на странице товара';
			$language['ru']['text_reviews'] = 'Размечать отзывы';
			$language['ru']['text_related'] = 'Размечать сопутствующие товары';
			$language['ru']['text_attribute'] = 'Размечать атрибуты (характеристики)';
			$language['ru']['text_in_stock'] = 'Статус всегда в наличии';
			$language['ru']['text_in_stock_h'] = 'Даже когда товар не в наличии (количество= 0) он будет в разметке inStock';
			$language['ru']['text_information_page'] = 'Информация';
			$language['ru']['text_category_page'] = 'Категория';
			$language['ru']['text_manufacturer_page'] = 'Производитель';
			$language['ru']['text_hide_price'] = 'Скрыть цену';
			$language['ru']['text_oh'] = 'Время работы:';
			$language['ru']['text_monday'] = 'Понедельник:';
			$language['ru']['text_tuesday'] = 'Вторник:';
			$language['ru']['text_wednesday'] = 'Среда:';
			$language['ru']['text_thursday'] = 'Четверг:';
			$language['ru']['text_friday'] = 'Пятница:';
			$language['ru']['text_saturday'] = 'Суббота:';
			$language['ru']['text_sunday'] = 'Воскресенье:';
			$language['ru']['text_information'] = 'Информация о модуле';
			$language['ru']['text_check'] = 'Проверить микроразметку';
			$language['ru']['text_check_h'] = 'Перед проверкой не забудьте сохранить настройки модуля';
			$language['ru']['text_contacts'] = 'Контакты разработчика';
			$language['ru']['text_contacts_h'] = 'Если есть вопросы по модулю, напишите разработчику';
			$language['ru']['text_email'] = 'Почта:&nbsp;';
			$language['ru']['text_site'] = 'Сайт:&nbsp;';
			$language['ru']['text_faq'] 	 = 'Наиболее популярные вопросы и ответы';
			$language['ru']['text_faq_h'] = 'Почитайте, возможно Вы найдете там ответ на свой вопрос';
			$language['ru']['text_old_microdata'] = 'Обнаружена старая микроразметка в файлах:';
			$language['ru']['text_clear'] = 'Очистить';
			$language['ru']['text_success_removed'] = 'Старая микроразметка успешно удалена';
			$language['ru']['text_store_type'] = 'Тип магазина';
			$language['ru']['text_store_type_h']= 'Если ничего не подходит - не выбирайте. Будет Store';
			$language['ru']['text_select']= 'Выбрать';
			$language['ru']['text_social'] = 'Социальные сети';
			$language['ru']['text_other'] = 'Другое';
			$language['ru']['text_other_modules'] = 'Другие модули автора';
			$language['ru']['text_opengraph_h'] = 'В некоторых шаблонах базовая есть, но MicrodataPro формирует максимально полную разметку OpenGraph. Рекомендуется включать.';
			$language['ru']['text_age_group_desc'] = 'Если магазин предназначен только для детей или только для взрослых выберите настройку. Для обычных магазинов не надо ничего выбирать';
			$language['ru']['text_age_group'] = 'Тип магазина для соц сетей';
			$language['ru']['text_age_default'] = 'Обычный магазин';
			$language['ru']['text_age_children'] = 'Для детей';
			$language['ru']['text_age_adult'] = 'Для взрослых';
			$language['ru']['text_target_gender_desc'] = 'Целевой пол продукта - для кого предназначены товары. Если не уверены - ничего не выбирайте.';
			$language['ru']['text_target_gender'] = 'Целевой пол товара';
			$language['ru']['text_target_gender_female'] = 'Для женщин';
			$language['ru']['text_target_gender_male'] = 'Для мужчин';
			$language['ru']['text_target_gender_unisex'] = 'Унисекс';
			$language['ru']['text_profile_id'] = 'Ваш профиль facebook';
			$language['ru']['text_profile_id_desc'] = 'В это поле нужно вставить ссылку на профиль в facebook, например: https://www.facebook.com/nikolay.prut';
			$language['ru']['text_check_license'] = 'Проверить лицензию модуля';
			$language['ru']['text_attr_color'] = 'Атрибут для цвета';
			$language['ru']['text_attr_material'] = 'Атрибут для материала';
			$language['ru']['text_attr_size'] = 'Атрибут для размера';
			$language['ru']['text_gallery'] = 'Размечать доп.фото товара как галерею';
			$language['ru']['text_diagnostic'] = 'Диагностика (<span style="color:#00b32d;">ок</span>)';
			$language['ru']['text_diagnostic_e'] = 'Диагностика ';
			$language['ru']['text_for_work'] = 'Для работы модуля MicrodataPro необходимо совсем немного:';
			$language['ru']['text_succ_mod'] = 'Модификатор модуля успешно установлен';
			$language['ru']['text_click_view'] = 'Кликните для просмотра информации';
			$language['ru']['text_err_mod'] = 'Модификатор модуля не полностью установился (проблем:';
			$language['ru']['text_file_original'] = 'Файл оригинал:';
			$language['ru']['text_modok'] = 'можно модифицировать';
			$language['ru']['text_in_file'] = 'В файле';
			$language['ru']['text_find_string'] = 'найдена строка (или начало) для привязки:';
			$language['ru']['text_code_in_file'] = 'Код модуля будет в';
			$language['ru']['text_opencart_file'] = 'Файл магазина:';
			$language['ru']['text_none_mode'] = 'нельзя модифицировать.';
			$language['ru']['text_why_mod'] = 'Как исправить?';
			$language['ru']['text_in_file_strong'] = 'Необходимо что бы в файле';
			$language['ru']['text_start_string'] = 'было начало строки:';
			$language['ru']['text_or'] = 'или';
			$language['ru']['text_support_text'] = 'Если нет, добавьте. Если не получается, пишите в <a href="https://microdata.pro/support/" target="_blank" title="Откроется в новом окне">техническую поддержку MicrodataPro</a><br>После правки файла <a href="#" class="refresh">обновите кеш модификаторов</a> и зайдите опять в MicrodataPro для контроля';
			$language['ru']['text_module_in_file'] = 'Модуль в файл:';
			$language['ru']['text_installed'] = 'установлен';
			$language['ru']['text_added_code_before'] = 'Успешно добавлен код перед строкой:';
			$language['ru']['text_module_code'] = '...код модуля...';
			$language['ru']['text_not_installed'] = 'не установлен.';
			$language['ru']['text_what_to_do'] = 'Что делать?';
			$language['ru']['text_if_in_orig'] = 'Если в оригинальном файле';
			$language['ru']['text_all_good'] = 'все хорошо, попробуйте <a href="#" class="refresh">обновить кеш модификаторов</a> и код модуля должен прописаться.<br>Если нет, посмотрите замечания по файлу оригиналу, исправьте и <a href="#" class="refresh">обновите кеш модификаторов</a> - должно все заработать.<br>Также, проверьте установлен ли <b>MicrodataPro for Opencart ' . $this->ver . '</b> в модификаторах.';
			$language['ru']['text_for_good_work'] = '<p style="margin-top:10px;"><b>Для корректной работы модуля не забудьте <a href="#" class="refresh">обновить кеш модификаторов</a> и обнулить все кеши установленные на сайте</b></p>';
			$language['ru']['text_old_microdata_not_find'] = 'Старые элементы разметки в шаблоне не найдены';
			$language['ru']['text_old_info'] = '<p>Часто бывает что в шаблонах есть уже встроенная базовая разметка, которая в 99% случаях работает не корректно и дает ошибки.</p><p>При обнаружении такой, рекомендуется ее чистить.</p><p>MicrodataPro имеет встроенный инструмент очистки старой разметки.</p><p>В Вашем случае разметки встроенной нет, чистить ничего не надо.</p>';
			$language['ru']['text_old_microdata_find'] = 'Найдены старые элементы разметки в шаблоне!';
			$language['ru']['text_recoment_clear'] = 'Рекомендуется очистить';
			$language['ru']['text_cleared_info'] = '<p>Часто бывает что в шаблонах есть уже встроенная базовая разметка, которая в 99% случаях работает не корректно и дает ошибки.</p><p>При обнаружении такой, рекомендуется ее чистить.</p><p>MicrodataPro имеет встроенный инструмент очистки старой разметки.</p><p>В Вашем случае модуль увидил старые элементы разметки, рекомендуется почистить.</p><p><b>Важно понимать</b> что автоматическая чистка разметки нуждается в последующей проверки на корректность очистки.</p>';
			$language['ru']['text_other_modules_find'] = 'Обнаружены другие модули/модификаторы разметки';
			$language['ru']['text_other_modules_not_find'] = 'У Вас не обнаружены другие дополнения разметки';
			$language['ru']['text_other_modules_info'] = '<p><b>Эту ошибку может вызывать и 2 пункт</b>. Для начала убедитесь что с ним все в порядке.</p><p>Иногда бывает что в магазинах есть какие-то дополнения для микроразметки.</p><p>Как правило, они размечают только базовые данные и очень часто с ошибками.</p><p>Для правильной работы MicrodataPro необходимо удалить все другие моды разметки и <a href="#" class="refresh">почистить кеш модификаторов</a>.</p>';
			$language['ru']['text_not_other_modules_info'] = '<p>Иногда бывает что в магазинах есть какие-то дополнения для микроразметки.</p><p>Как правило, они размечают только базовые данные и очень часто с ошибками.</p><p>Для правильной работы MicrodataPro надо убрать все другие модули для разметки.</p><p>У Вас все хорошо, других дополнений нет. Работает только MicrodataPro.</p>';
			$language['ru']['text_google_check'] = 'Проверьте страницы сайта в валидаторе google';
			$language['ru']['text_link_check'] = '<p><b>После настройки</b> модуля, рекомендуется проверить все страницы на корректность работы MicrodataPro</p><p>Проверить страницы можно по ссылкам</p>';
			$language['ru']['text_main_page'] = 'Главная страница';
			$language['ru']['text_in_new_tab'] = 'Откроется в новой вкладке';
			$language['ru']['text_info_page'] = 'Информационная страница';
			$language['ru']['text_empty_category'] = '<li>Категория - в магазине пока что нет категорий</li>';
			$language['ru']['text_empty_product'] = '<li>Товар - в магазине пока что нет товаров</li>';
			$language['ru']['text_empty_brand'] = '<li>Производитель - в магазине пока что нет производителей</li>';
			$language['ru']['text_empty_info'] = '<li>Информационная страница - в магазине пока что нет информационных страниц</li>';
			$language['ru']['text_google_validator'] = '<p>Если какая либо страница недоступна, проверьте не включен ли режим обслуживания в настройках магазина, либо вставьте любую другую страницу (товара, категории и т.п.) в <a href="https://search.google.com/structured-data/testing-tool" target="_blank" title="Откроется в новом окне">валидатор google</a></p>';
			$language['ru']['text_mod_cleared'] = 'Кеш модификаторов успешно обновлен, страница будет перезагружена!';
			$language['ru']['text_thanks_new'] = '<h3>Спасибо за использование модуля MicrodataPro.</h3><h4>Это отличный выбор, надеюсь он оправдает все Ваши ожидания! <span style="color:#00b32d;">:)</span></h4><p>С уважением разработчик модуля <a href="https://t.me/PrutNikolay" target="_blank">Прут Николай</a></p>';
			$language['ru']['text_yandex_validator'] = 'Y Валидатор микроразметки Яндекс.Вебмастер';
			$language['ru']['text_reviews_mod'] = 'Отзывы о модуле';
			$language['ru']['text_on_site'] = 'на';
			$language['ru']['text_old_microdata_deleted'] = 'Старые элементы разметки удалены, перезагрузите страницу';
			$language['ru']['text_price_from_to'] = 'Размечать цены от и до';
			$language['ru']['text_all_rating'] = 'Размечать общий рейтинг товаров';
			$language['ru']['text_images_to_gallery'] = 'Размечать фото товаров как галерею';
			$language['ru']['text_product_agregator'] = 'Только для агрегатора товаров!';
			$language['ru']['text_category_rating'] = 'Рейтинг товаров в категории';
			$language['ru']['text_support_info'] = 'Перед тем как написать в поддержку info@microdata.pro посмотрите здесь: <a href="https://microdata.pro/faq/" title="Откроектся в новом окне" target="_blank" data-toggle="tooltip">https://microdata.pro/faq/</a><br>Если вам тяжело разобраться, лучше обратитесь к автору модуля.';
			$language['ru']['text_warning_info'] = 'Обязательно почитайте вопросы/ответы: <a href="https://microdata.pro/faq/" target="_blank">https://microdata.pro/faq/</a><br>Предупреждения в google и ошибки в яндекс: <a href="https://microdata.pro/blog/google-search-console-warning" target="_blank">https://microdata.pro/blog/google-search-console-warning</a><br>Телеграмм разработчика: <strong>@PrutNikolay</strong><br>Почта: <strong>info@microdata.pro</strong>';
			$language['ru']['text_page_info'] = 'Внимание! Указывая данные здесь вы должны быть уверены что посетитель это также видит на страницах сайта.<br>Если этого нет на сайте, лучше выключить разметку магазина или добавить информацию на сайт.<br>Также помните что Адрес надо вводить в формате <b>координаты//Город, Страна//Адрес//Почтовый код</b>';
			$language['ru']['text_logo_size'] = 'Размеры логотипа';
			$language['ru']['text_logo_alt'] = 'Альтернативный логотип для разметки';
			$language['ru']['text_cron_key'] = 'Ключ для CRON ссылки выборки цен';
			$language['ru']['text_recommended_to_fill'] = 'рекомендуется заполнять';
			$language['ru']['text_products_carousel'] = 'Размечать товары категории как карусель';
			$language['ru']['text_photo_in_list'] = 'Размечать фото в списке';
			$language['ru']['text_preview_size'] = 'Размеры превью';
			$language['ru']['text_thumb_size'] = 'Размеры больших фото';
			$language['ru']['text_status_from_status'] = 'Статус в разметке при статусе товара:';
			$language['ru']['text_shipping_cost'] = 'Cтоимость доставки';
			$language['ru']['text_shipping_cost_default'] = 'по умолчанию 50';
			$language['ru']['text_shipping_country'] = 'Cтрана доставки';
			$language['ru']['text_shipping_country_default'] = 'по умолчанию US';
			$language['ru']['text_shipping_time1_min'] = 'Минимальное время обработки заказа (дни)';
			$language['ru']['text_shipping_time1_min_default'] = 'по умолчанию 0';
			$language['ru']['text_shipping_time1_max'] = 'Максимальное время обработки заказа (дни)';
			$language['ru']['text_shipping_time1_max_default'] = 'по умолчанию 1';
			$language['ru']['text_shipping_time2_min'] = 'Минимальное время доставки после обработки заказа (дни)';
			$language['ru']['text_shipping_time2_min_default'] = 'по умолчанию 1';
			$language['ru']['text_shipping_time2_max'] = 'Максимальное время доставки после обработки заказа (дни)';
			$language['ru']['text_shipping_time2_max_default'] = 'по умолчанию 3';
			$language['ru']['text_return_days'] = 'Возврат товара на протяжении (дни)';
			$language['ru']['text_return_days_default'] = 'по умолчанию 14';
			$language['ru']['text_no_active'] = '<div style="font-size:22px;text-align:center;margin-top:20px;"><h3><strong>К сожалению MicrodataPro не смог автоматически подтянуть ключ лицензии на этот домен</strong></h3><small style="display:block;margin:10px 0;font-size:16px;">Такое бывает, ничего страшного</small><div class="alert alert-info" style="font-size:14px;line-height:16px;">Чтобы вручную забрать ключ перейдите по ссылке: <a target="_blank" href="https://microdata.pro/key?s=' . base64_encode($this->info('site')) . '">https://microdata.pro/key</a> и вставьте его в поле ниже</div><div><textarea name="txt" id="key_manual" class="form-control" placeholder="Вставьте сюда ключ лицензии и подождите пару секунд"></textarea></div><div style="margin-top:15px;border:1px solid #00C962;padding:15px;font-size:12px;"><p>Спасибо за ожидание, надеюсь что после активации модуль оправдает все ваши надежды. С уважением разработчик MicrodataPro Николай</p><p>А пока можно почитать о возможностях модуля и посмотреть инструкции на сайте <a target="_blank" title="Откроется в новой вкладке" href="https://microdata.pro">https://microdata.pro</a></p></div></div>';

			if($word){
				return isset($language[$from][$word])?$language[$from][$word]:'';
			}else{
				$this->lang = $language[$from];
			}

		}
	//language

}
