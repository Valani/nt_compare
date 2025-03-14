<?php
class ControllerExtensionModuleEasyPhoto extends Controller {

	//versions (in 3.0 = only for 3.0 comment)
	//for 3.0 $this->config->get(module_var);
	private $path = 'extension/module/easyphoto';
	private $module = 'extension/extension';
	private $table = 'product';
	private $deny_image = array('no_image.jpg','no_image.png','placeholder.png','placeholder.jpg');
	private $current = '';

	public function install() {

		$response = $this->send();

		if($response['status'] && $response['content']){
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSettingValue('module_easyphoto', "module_easyphoto_key", $response['content']);
		}
	}

	public function index() {
		$data = $this->load->language($this->path);
		$a = 1;
		$response = $this->send();

		if($response['status'] && $response['content'] && $this->key($response['content'])){
			$a = 1;
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSettingValue('module_easyphoto', "module_easyphoto_key", $response['content']);
		}

		$this->document->setTitle(strip_tags($this->language->get('heading_title')));

		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting('module_easyphoto', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link($this->path, 'user_token=' . $this->session->data['user_token'], 'SSL'));
		}

		//30
		$data['https_catalog'] = HTTPS_CATALOG;
		$data['dir_image_const'] = DIR_IMAGE;
		//30

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => '<span style="color:#00b32d;font-weight:bold;">Easy Photo</span>',
			'href' => $this->url->link($this->path, 'user_token=' . $this->session->data['user_token'], 'SSL')
		);

		$data['action'] = $this->url->link($this->path, 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');

		//array vars
		$vars = array(
			'easyphoto_status',
			'easyphoto_key',
			'easyphoto_direct',
			'easyphoto_main',
			'easyphoto_name',
			'easyphoto_separate', //3.1+
			'easyphoto_from', //3.1-
			'easyphoto_language',
			'easyphoto_rename_direct', //4.0
			'easyphoto_rename_category', //4.0
			'easyphoto_rename_dir', //4.0
			'easyphoto_rename_name', //4.0
			'easyphoto_rename_mode', //4.0
			'easyphoto_allowed_ext', //4.0
			'easyphoto_rename_backup', //4.0
			'easyphoto_rename_search', //4.0
			'easyphoto_rename_table', //4.0
			'easyphoto_delete_table', //4.0
			'easyphoto_delete_direct' //4.0
		);

 		foreach($vars as $var){
			$var = 'module_' . $var; //only for 3.0

			if (isset($this->request->post[$var])) {
				$data[$var] = $this->request->post[$var];
			} else {
				$data[$var] = $this->config->get($var);

				//4.0
				if($var == 'module_easyphoto_allowed_ext' && empty($data[$var])){
					$data[$var] = 'jpg,jpeg,png,gif,bmp,webp,svg';
				}
				if(($var == 'module_easyphoto_rename_table' && empty($data[$var])) || ($var == 'module_easyphoto_delete_table' && empty($data[$var]))){
					$data[$var] = "banner_image=image" . PHP_EOL . "category_description=description" . PHP_EOL . "information_description=description" . PHP_EOL . "manufacturer_description=description" . PHP_EOL . "product_description=description" . PHP_EOL . "setting=value";
				}
				//4.0

			}
		}

		//3.1+
		$data['fields'] = array('name','model','sku','upc','ean','jan','isbn','mpn','location');
		//3.1-

		if($response['status'] && $response['content'] && empty($data['module_easyphoto_key'])){
			$this->model_setting_setting->editSettingValue('module_easyphoto', "module_easyphoto_key", $response['content']);
			$data['module_easyphoto_key'] = $response['content'];
		}
		//$data['module_easyphoto_key'] = $a?$data['module_easyphoto_key']:false;

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		//3.1+
		$data['more_info'] = false;

		// $proposal = 'https://microdata.pro/index.php?route=sale/proposal&module=Easyphoto';
		//
		// if($this->get_http_response_code($proposal) == 200){
		// 	$more_info = $this->xssСlean(file_get_contents($proposal));
		// 	$data['more_info'] = $more_info;
		// }
		//3.1-

		$data['path'] = $this->path; //4.0
		$data['user_token'] = $this->request->get['user_token'];

		//counters
		$image_query = $this->db->query("SELECT COUNT(image) as total FROM " . DB_PREFIX . "product");
		$data['count_image'] = $image_query->row['total'];
		$images_query = $this->db->query("SELECT COUNT(image) as total FROM " . DB_PREFIX . "product_image");
		$data['count_images'] = $images_query->row['total'];


		$dir_image = DIR_IMAGE . 'catalog/';
		$data['dir_image'] = $this->format_size($this->getTotalSize($dir_image));
		//counters

		//create table for backup
		$sql = "CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "easyphoto_rename_image
					(`item_id` int(11) NOT NULL AUTO_INCREMENT,
					`product_id` int(11) NOT NULL,
					`main` int(1) NOT NULL,
					`old` varchar(512) NOT NULL,
					`new` varchar(512) NOT NULL,
					`date_added` datetime NOT NULL,
					PRIMARY KEY (`item_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
		$this->db->query($sql);
		//create table for backup

		$data['i'] = 0;
		if(isset($this->session->data['rename_i']) && $this->session->data['rename_i'] > 0){
			$data['i'] = $this->session->data['rename_i'];
		}

		$this->response->setOutput($this->load->view($this->path, $data));
	}

	//4.0
	private function getTotalSize($dir){
    $dir = rtrim(str_replace('\\', '/', $dir), '/');

    if (is_dir($dir) === true) {
        $totalSize = 0;
        $os        = strtoupper(substr(PHP_OS, 0, 3));
        // If on a Unix Host (Linux, Mac OS)
        if ($os !== 'WIN' && function_exists('popen')) {
            $io = popen('/usr/bin/du -sb ' . $dir, 'r');
            if ($io !== false) {
                $totalSize = intval(fgets($io, 80));
                pclose($io);
                return $totalSize;
            }
        }
        // If on a Windows Host (WIN32, WINNT, Windows)
        if ($os === 'WIN' && extension_loaded('com_dotnet')) {
            $obj = new \COM('scripting.filesystemobject');
            if (is_object($obj)) {
                $ref       = $obj->getfolder($dir);
                $totalSize = $ref->size;
                $obj       = null;
                return $totalSize;
            }
        }
        // If System calls did't work, use slower PHP 5
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            $totalSize += $file->getSize();
        }
        return $totalSize;
    } else if (is_file($dir) === true) {
        return filesize($dir);
    }
	}

	private function format_size($size) {
		$units = explode(' ', 'B KB MB GB TB PB');

		$mod = 1024;

		for ($i = 0; $size > $mod; $i++) {
		  $size /= $mod;
		}

		$endIndex = strpos($size, ".")+3;

		return substr( $size, 0, $endIndex).' '.$units[$i];
	}

	private function searchInDB($old_image, $new_image){
		$tables_row = explode(PHP_EOL, $this->config->get('module_easyphoto_rename_table'));
		foreach($tables_row as $row){
			$table_data = explode("=", $row);
			if($table_data[0] && $table_data[1]){
				$table = $this->db->escape(trim($table_data[0]));
				$field = $this->db->escape(trim($table_data[1]));
				$query = $this->db->query("UPDATE " . DB_PREFIX . $table . " SET " . $field . " = REPLACE(" . $field . ", '" . $old_image . "', '" . $new_image . "') WHERE " . $field . " LIKE '%" . $old_image . "%'");
			}
		}
	}

	private function getSharedImages(){
		$multi_data = array();

		$multi_query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product WHERE image NOT LIKE '%shared_photo%'");
		foreach($multi_query->rows as $row){
			if(isset($multi_data[$row['image']])){ //если уже есть +
				$multi_data[$row['image']] = $multi_data[$row['image']] + 1;
			}else{
				$multi_data[$row['image']] = 1;
			}
		}

		$multi_query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product_image WHERE image NOT LIKE '%shared_photo%'");
		foreach($multi_query->rows as $row){
			if(isset($multi_data[$row['image']])){ //если уже есть +
				$multi_data[$row['image']] = $multi_data[$row['image']] + 1;
			}else{
				$multi_data[$row['image']] = 1;
			}
		}

		$multi_data_final = array();
		foreach($multi_data as $image => $count){
			if($count > 1 && $image && !in_array($image, $this->deny_image)){

				$image = str_replace("'", "\\'", $image);
				//rename shared
				$image_data = explode("/", $image);
				$image_name = end($image_data);

				$ext = strtolower(preg_replace('/^.*\.(.*)$/s', '$1', $image_name));

				$image_name = str_replace('.' . $ext, '', $image_name);

				$new_image = $this->db->escape($this->transform($image_name) . '.' . $ext);

				$directory = 'catalog/shared_photo/';
				$final_directory = DIR_IMAGE . $directory;

				if (!file_exists(rtrim($final_directory, '/'))) { //если нет директории создаем
					mkdir(rtrim($final_directory, '/'), 0777, true);
				}

				copy(DIR_IMAGE . $image, $final_directory .  $new_image);

				$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $directory . $new_image . "' WHERE image = '" . $image . "'");
				$this->db->query("UPDATE " . DB_PREFIX . "product_image SET image = '" . $directory . $new_image . "' WHERE image = '" . $image . "'");

				$this->searchInDB($image, $new_image);

				if(!$this->config->get('module_easyphoto_rename_mode')){ //по умолчанию удаляем старое фото после копирования, но если стоит настройка не удалять то не трогаем. Далее все равно будет функционал чистки от старых фото, так что можно не удалять а копировать, но потом запускать систему чистки от ненужных фото.
					if(is_file(DIR_IMAGE . $image)){
						unlink(DIR_IMAGE . $image);
					}
				}
				//rename shared
			}
		}
	}

	private function checkImage($image){
		if(is_file(DIR_IMAGE . $image)){ //если есть фото
			$image_data = explode('/', $image);
			$image_file_we = explode('.', end($image_data));
			$image_index_data = explode('-', $image_file_we[0]);
			if(count($image_index_data) > 1){ //если есть разделитель
				$index = end($image_index_data);
				if(is_numeric($index) && $index >= 1 && $index <= 30){ //если это цифра и от 1 до 30
					$image = str_replace('-' . $index . '.', '-' . ($index + 1) . '.', $image); //добавляем ему +1
				}else{
					$image = str_replace('.', '-1.' , $image);
				}
			}else{
				$image = str_replace('.', '-1.' , $image);
			}
			$this->current = $image;
			$this->checkImage($image); //заново смотрим наличие файла
		}else{
			$this->current = $image;
		}
	}

	//rename
	public function getCountPhoto(){ //1) забираем все фото get -> table
		$this->getSharedImages(); //сразу детектим мультифото и переименовываем их для общего доступа
		if(isset($this->request->get['table'])){
			$this->table = $this->request->get['table']; //'product_image';
		}
		$main_images_query = $this->db->query("SELECT image FROM " . DB_PREFIX . $this->table);
		$all = $main_images_query->num_rows;
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($all));
	}

	public function selectPercent(){ //2) дозируем по 1%

		$order_by = 'product_id';
		if(isset($this->request->get['table'])){
			$this->table = $this->request->get['table']; //'product_image';
			$order_by = 'product_image_id';
		}

		$error_photo = array();
		$json['renamed'] = 0;
		$i = $this->request->get['i'];
		$this->session->data['rename_i'] = $i;
		$limit = $this->request->get['count'];
		$all = $this->request->get['all'];
		$offset = $i * $this->request->get['count'];

		if($offset < $all){
			$main_images_query = $this->db->query("SELECT product_id, image FROM " . DB_PREFIX . $this->table . " ORDER BY " . $order_by . " DESC LIMIT " . $limit . " OFFSET " . $offset); //начинаем с новых

			$response = $this->renameBlock($main_images_query->rows);

			$json['i'] = $i+1;
			$json['all'] = $all;
			$json['error'] = $response['error_photo'];
			$json['renamed'] += $response['renamed'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function renameBlock($images){ //3)передаем все фото за шаг - здесь цикл, проверка и передача в rename_image_item
		$error_photo = array();
		$renamed = 0;
		$allowed_ext = explode(",", $this->config->get('module_easyphoto_allowed_ext'));

		foreach($images as $image){

			if(!$image['image']){ //если нет фото
				$error_photo[] = array('text' => 'В товаре id: ' . $image['product_id'] . ' не задано фото');
				continue;
			}

			if(strpos($image['image'], "shared_photo") !== false){ //если мультифото пропускаем
				continue;
			}

			if(!is_file(DIR_IMAGE . $image['image'])){ //если нет файла
				$error_photo[] = array('text' => 'В товаре id: ' . $image['product_id'] . ' фото физически отсутствует на сервере');
				continue;
			}

			if(in_array($image['image'], $this->deny_image)){ //если заглушка
				$error_photo[] = array('text' => 'В товаре id: ' . $image['product_id'] . ' вместо фото загрушка');
				continue;
			}

			$ext = strtolower(preg_replace('/^.*\.(.*)$/s', '$1', $image['image'])); //расширение файла

			if(!in_array($ext, $allowed_ext)){ //если расширение недопустимое - пытаемся его узнать из файла
				$info = getimagesize(DIR_IMAGE . $image['image']);
				$ext = image_type_to_extension($info[2]);
				if(!in_array(str_replace(".","",$ext), $allowed_ext)){ //если что-то не так все равно
					$error_photo[] = array('text' => 'В товаре id: ' . $image['product_id'] . ' фото в запрещенном формате');
					continue;
				}
			}

			$is_rename = $this->renameItem($image, $ext); //переименование
			$renamed += $is_rename;

		}

		return array('error_photo' => $error_photo, 'renamed' => $renamed);
	}

	private function renameItem($image, $ext){ //4) Принимаем фото и ext, формируем путь, копируем, обновляем в базе

		//директория для фото
		$directory_set = $this->config->get('module_easyphoto_rename_direct')?rtrim(ltrim($this->config->get('module_easyphoto_rename_direct'), '/'), '/'):'easyphoto';
		$directory = "catalog/" . $directory_set . "/";
		//директория для фото

		//Категории
		if($this->config->get('module_easyphoto_rename_category')){
			if($this->config->get('module_easyphoto_rename_category') == 1){ //Последняя категория (транслит)
				$categories = $this->getPathByProduct($image['product_id']);
				$category_id = end(explode('_', $categories));
				$category_name_query = $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
				if(isset($category_name_query->row['name']) && $category_name_query->row['name']){
					$category_path = $this->transform($category_name_query->row['name']);
					$directory .= $category_path .'/';
				}
			}
			if($this->config->get('module_easyphoto_rename_category') == 2){ //Последняя категория (id)
				$categories = $this->getPathByProduct($image['product_id']);
				$category_id = end(explode('_', $categories));
				$directory .= $category_id .'/';
			}
			if($this->config->get('module_easyphoto_rename_category') == 3){ //Полная вложенность категорий (транслит)
				$categories = $this->getPathByProduct($image['product_id']);
				foreach(explode('_', $categories) as $category_id){
					$category_name_query = $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$category_id . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
					if(isset($category_name_query->row['name']) && $category_name_query->row['name']){
						$category_path = $this->transform($category_name_query->row['name']);
						$directory .= $category_path .'/';
					}
				}
			}
			if($this->config->get('module_easyphoto_rename_category') == 4){ //Полная вложенность категорий (id)
				$categories = $this->getPathByProduct($image['product_id']);
				foreach(explode('_', $categories) as $category_id){
					$directory .= $category_id .'/';
				}
			}
		}
		//Категории

		//Папка
		if($this->config->get('module_easyphoto_rename_dir')){
			if($this->config->get('module_easyphoto_rename_dir') == 1){
				$directory .= $image['product_id'] .'/';
			}else{ //если другие папки
				if($this->config->get('module_easyphoto_rename_dir') == 'name'){
					$query_data = $this->db->query("SELECT `" . $this->db->escape($this->config->get('module_easyphoto_rename_dir')) . "` FROM " . DB_PREFIX ."product_description WHERE product_id = '" . (int)$image['product_id'] . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
				}else{
					$query_data = $this->db->query("SELECT `" . $this->db->escape($this->config->get('module_easyphoto_rename_dir')) . "` FROM " . DB_PREFIX ."product WHERE product_id = '" . (int)$image['product_id'] . "'");
				}
				if(isset($query_data->row[$this->config->get('module_easyphoto_rename_dir')]) && !empty($query_data->row[$this->config->get('module_easyphoto_rename_dir')])){
					$directory .= $this->transform($query_data->row[$this->config->get('module_easyphoto_rename_dir')]) .'/';
				}
			}
		}
		//Папка

		if(strpos($image['image'], $directory) !== false){ //если директория уже новая, но не трогаем
			return 0;
		}

		$final_directory = DIR_IMAGE . $directory;

		//фото
		if($this->config->get('module_easyphoto_rename_name')){
			if($this->config->get('module_easyphoto_rename_name') == 'name'){ //генерация с имени
				$product_name_query = $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$image['product_id'] . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
				if(isset($product_name_query->row['name']) && $product_name_query->row['name']){
					$image_name = $this->transform($product_name_query->row['name']);
				}
			}else{ //генерация с другого поля
				$query_data = $this->db->query("SELECT `" . $this->db->escape($this->config->get('module_easyphoto_rename_name')) . "` FROM " . DB_PREFIX ."product WHERE product_id = '" . (int)$image['product_id'] . "'");
				if(isset($query_data->row[$this->config->get('module_easyphoto_rename_name')]) && !empty($query_data->row[$this->config->get('module_easyphoto_rename_name')])){
					$image_name = $this->transform($query_data->row[$this->config->get('module_easyphoto_rename_name')]);
				}
			}

			$image_name .= '.' . $ext;
		}
		//фото

		if (!file_exists(rtrim($final_directory, '/'))) { //если нет директории создаем
			mkdir(rtrim($final_directory, '/'), 0777, true);
		}

		//Проверка есть ли фото
		$image_check = $this->checkImage($directory . $image_name);
		$image_name = str_replace($directory, '', $this->current);
		//Проверка есть ли фото

		if(DIR_IMAGE . $image['image'] != $final_directory . $image_name){ //если фото разные

			copy(DIR_IMAGE . $image['image'], $final_directory . $image_name); //копируем!

			if(!$this->config->get('module_easyphoto_rename_mode')){ //по умолчанию удаляем старое фото после копирования, но если стоит настройка не удалять то не трогаем. Далее все равно будет функционал чистки от старых фото, так что можно не удалять а копировать, но потом запускать систему чистки от ненужных фото.
				if(is_file(DIR_IMAGE . $image['image'])){
					unlink(DIR_IMAGE . $image['image']);
				}
			}

			//обновляем в базе путь к фото
			$image['image'] = str_replace("'", "\\'", $image['image']);
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $directory . $image_name . "' WHERE image = '" . $image['image'] . "'");
			$this->db->query("UPDATE " . DB_PREFIX . "product_image SET image = '" . $directory . $image_name . "' WHERE image = '" . $image['image'] . "'");
			//обновляем в базе путь к фото

			//пишем в базу старое и новое
			$main = 1;
			if($this->table != 'product'){
				$main = 0;
			}
			if($this->config->get('module_easyphoto_rename_backup')){
				$this->db->query("INSERT INTO " . DB_PREFIX . "easyphoto_rename_image SET product_id = '" . $image['product_id'] . "', main = '" . $main . "', old = '" . $image['image'] . "', new = '" . $directory . $image_name . "', date_added = NOW()");
			}
			//пишем в базу старое и новое

			//поиск в базе есть фото где-то используется
			if($this->config->get('module_easyphoto_rename_search')){
				$this->searchInDB($image['image'], $directory . $image_name); //old, new
			}
			//поиск в базе есть фото где-то используется

			return 1;
		}else{ //если фото одинаковые не надо переименовывать
			return 0;
		}

	}
	//rename

	//delete
	public function startDelete(){

		//getAllImage
		$in_db = array();
		$multi_query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product");
		foreach($multi_query->rows as $row){
			$in_db[$row['image']] = 1;
		}
		$multi_query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product_image");
		foreach($multi_query->rows as $row){
			$in_db[$row['image']] = 1;
		}
		//getAllImage

		//getAllSearcData
		$data_db = '';

		$tables_row = explode(PHP_EOL, $this->config->get('module_easyphoto_delete_table'));
		foreach($tables_row as $row){
			$table_data = explode("=", $row);
			if($table_data[0] && $table_data[1]){
				$table = $this->db->escape(trim($table_data[0]));
				$field = $this->db->escape(trim($table_data[1]));
				$query = $this->db->query("SELECT " . $field . " FROM " . DB_PREFIX . $table . " WHERE " . $field . " LIKE '%catalog/%'"); //берем все данные в которых есть путь к фото
				if($query->num_rows){
					foreach($query->rows as $row){
						$data_db .= $row[$field];
					}
				}
			}
		}
		//getAllSearcData

		$count_delete = 0;
		$dir = $this->config->get('module_easyphoto_delete_direct');
		$files = $this->getDirContents(DIR_IMAGE . 'catalog/' . $dir);

		foreach($files as $file){ //перебор файлов
			$delete = true;

			$image = str_replace(DIR_IMAGE, '', $file);
			$image = str_replace("'", "\\'", $image);

			if(isset($in_db[$image])){ //проверка есть ли фото в товаре - не удаляем
				$delete = false;
			}
			if(strpos($data_db, $image) !== false){ //проверка есть ли фото в таблицах базы - не удаляем
				$delete = false;
			}

			if($delete){ //удаляем фото
				if (file_exists($file)){
					if(!is_dir($file)){
						unlink($file);
						$count_delete++;
					}
				}
			}

		}

		if(!$count_delete){
			$count_delete = 'В папке лишних файлов нет.';
		}else{
			$count_delete = 'Удалено: ' . $count_delete . ' лишних фото';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($count_delete));
	}

	private function getDirContents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            $results[] = $path;
        } else if($value != "." && $value != "..") {
            $this->getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
	}
	//delete

	private function getPathByProduct($product_id) {
		$product_id = (int)$product_id;
		if ($product_id < 1) return false;

		static $path = null;
		if (!isset($path)) {
			$path = $this->cache->get('product.seopath');
			if (!isset($path)) $path = array();
		}

		if (!isset($path[$product_id])) {
			$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . $product_id . "' ORDER BY main_category DESC LIMIT 1");

			$path[$product_id] = $this->getPathByCategory($query->num_rows ? (int)$query->row['category_id'] : 0);

			$this->cache->set('product.seopath', $path);
		}

		return $path[$product_id];
	}

	private function getPathByCategory($category_id) {
		$category_id = (int)$category_id;
		if ($category_id < 1) return false;

		static $path = null;
		if (!isset($path)) {
			$path = $this->cache->get('category.seopath');
			if (!isset($path)) $path = array();
		}

		if (!isset($path[$category_id])) {
			$max_level = 10;

			$sql = "SELECT CONCAT_WS('_'";
			for ($i = $max_level-1; $i >= 0; --$i) {
				$sql .= ",t$i.category_id";
			}
			$sql .= ") AS path FROM " . DB_PREFIX . "category t0";
			for ($i = 1; $i < $max_level; ++$i) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "category t$i ON (t$i.category_id = t" . ($i-1) . ".parent_id)";
			}
			$sql .= " WHERE t0.category_id = '" . $category_id . "'";

			$query = $this->db->query($sql);

			$path[$category_id] = $query->num_rows ? $query->row['path'] : false;

			$this->cache->set('category.seopath', $path);
		}

		return $path[$category_id];
	}
	//4.0

	//3.1+
	private function get_http_response_code($url) {
	    $headers = get_headers($url);
	    return substr($headers[0], 9, 3);
	}
	//3.1-

	public function getForm($product_images) {
		$data = $this->load->language($this->path);
		$this->document->addScript('view/javascript/easyphoto/jquery.magnific-popup.min.js');
		$this->document->addStyle('view/javascript/easyphoto/magnific-popup.css');
		$data['product_images'] = $product_images['product_images'];
		$data['main_photo'] = $product_images['image'];
		$data['main_thumb'] = $product_images['thumb'];
		$data['user_token'] = $this->session->data['user_token'];
		$data['module_easyphoto_status'] = $this->config->get('module_easyphoto_status');
		$data['upload_link'] = "index.php?route=" . $this->path . "/upload";
		$data['resize_link'] = "index.php?route=" . $this->path . "/resize_rename";
		$data['clear_cart_link'] = "index.php?route=" . $this->path . "/clear_cart";
		$data['rotate_link'] = "index.php?route=" . $this->path . "/rotate";
		$data['easy_product_id'] = !isset($this->request->get['product_id']) ? false : "&product_id=" . $this->request->get['product_id'];
		$data['module_easyphoto_main'] = $this->config->get('module_easyphoto_main');
		$data['module_easyphoto_not_delete'] = $this->config->get('module_easyphoto_not_delete');
		$data['module_easyphoto_for'] = false; //for new product

		$data['https_catalog'] = HTTPS_CATALOG;

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if(isset($this->request->get['product_id'])){
			$language_id = $this->config->get('module_easyphoto_language') ? $this->config->get('module_easyphoto_language') : '1';

			$product_name_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "product_description WHERE product_id = '" . $this->request->get['product_id'] . "' AND language_id = '" . $language_id . "'");

			if(isset($product_name_query->row['name'])){
				$data['module_easyphoto_for'] = $product_name_query->row['name'];
			}
		}

		//trash_photo
		$data['trash_photos'] = array();
		if(isset($this->request->get['product_id'])){
			$in_product = array();
			$in_product[$product_images['image']] = $product_images['image'];
			foreach($product_images['product_images'] as $image_item){
				$in_product[$image_item['image']] = $image_item['image'];
			}
			$dir = DIR_IMAGE . $this->getDirectory() . $this->request->get['product_id'] . '/';
			if (file_exists($dir)){
				foreach(glob($dir . '*') as $file){
					if (file_exists($file)){
						$image = str_replace(DIR_IMAGE, "", $file);
						if(!in_array($image, $in_product)){
							$data['trash_photos'][$file] = array(
								'image' => $image,
								'thumb' => $this->model_tool_image->resize($image, 100, 100)
							);
						}
					}
				}
			}
		}

		$data['microtime_true'] = str_replace(".", "", microtime(true));

		return $this->load->view('extension/module/easyphoto_form', $data);
	}

	public function upload() {
		if(isset($this->request->files["easyphoto"]["name"])){
				if (!is_dir($this->getDirectory(1))) { //если нет директории
					mkdir($this->getDirectory(1), 0777, true);
				}
				if (!is_dir($this->getDirectory(1) . "tmp/")) { //если нет tmp директории
					mkdir($this->getDirectory(1) . "tmp/", 0777, true);
				}

				$tmp_file = $this->request->files["easyphoto"]['tmp_name'];
				$wrong_image = $this->wrongImage($tmp_file, $this->request->files["easyphoto"]["name"]);

				if(!$wrong_image){
					move_uploaded_file($tmp_file, $this->getDirectory(1) . "tmp/" . $this->request->files["easyphoto"]["name"]);
				}else{
					echo "Результаты диагностики загружаемого файла: " . PHP_EOL;
					foreach($wrong_image as $error_key => $error){
						echo " - " . $error . PHP_EOL;
					}
				}
		 }
	}

	public function wrongImage($image, $name) {
		$wrong_image = false;
		$allow_ext = array('jpg','jpeg','png','gif','bmp','webp','svg');

		//проверка по расширению файла
		if (!in_array(strtolower(substr(strrchr($name, '.'), 1)), $allow_ext)) {
			$wrong_image[] = 'Файл имеет неправильное расширение. Допустимы только фото!';
		}
		//проверка по расширению файла

		//проверка по типу файла
		if(function_exists('exif_imagetype') && !exif_imagetype($image)) {
			$wrong_image[] = "Тип файла не является изображением!";
		}
		//проверка по типу файла

		//проверка на php
		if (preg_match('/\<\?php/i', file_get_contents($image))) {
			$wrong_image[] = "В файле есть код php! Это не изображение!";
		}
		//проверка на php

		//проверка что это фото
		// if(@imagecreatefromstring(file_get_contents($image)) === false){
		// 	$wrong_image[] = "Файл не может быть обработан как фото";
		// }
		//проверка что это фото

		return $wrong_image;
	}


	public function resize_rename($from_model = array()) {
		if(!$from_model){ //прямая загрузка
			$photo = $this->request->get['photo'];
		}else{
			$photo = $from_model['image']; //фото передаем из модели при добавлении товара
		}

		$ext = "." . strtolower(preg_replace('/^.*\.(.*)$/s', '$1', $photo)); //расширение файла
		$directory = $this->getDirectory() . 'tmp/';

		//если товар уже есть - переименование фото и перемещение в директорию с id товара
		if(isset($this->request->get['product_id']) or isset($from_model['product_id'])){
			if(!$from_model){
				$product_id = $this->request->get['product_id'];
			}else{
				$product_id = $from_model['product_id'];
			}
			$language_id = $this->config->get('module_easyphoto_language') ? $this->config->get('module_easyphoto_language') : '1';

			//3.1+
			$name_from = false;
			if($this->config->get('module_easyphoto_name')){
				if(!$this->config->get('module_easyphoto_from')){
					$easyphoto_from = "name";
				}else{
					$easyphoto_from = $this->config->get('module_easyphoto_from');
				}
				if($easyphoto_from == "name"){
					$product_name_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "product_description WHERE product_id = '" . $product_id . "' AND language_id = '" . $language_id . "'");
					$name_from = isset($product_name_query->row['name'])?$product_name_query->row['name']:false;
				}else{
					$product_name_query = $this->db->query("SELECT " . $easyphoto_from . " FROM " . DB_PREFIX . "product WHERE product_id = '" . $product_id . "'");
					$name_from = isset($product_name_query->row[$easyphoto_from])?$product_name_query->row[$easyphoto_from]:false;
					//проверка на пустое поле
					if(empty($name_from)){
						$product_name_query = $this->db->query("SELECT name FROM " . DB_PREFIX . "product_description WHERE product_id = '" . $product_id . "' AND language_id = '" . $language_id . "'");
						$name_from = isset($product_name_query->row['name'])?$product_name_query->row['name']:false;
					}
				}
			}

			//новое название фото
			if($name_from && $this->config->get('module_easyphoto_name')){ //если есть имя товара в том языке + включена опция - фото из названия
				$photo_name = $this->transform($name_from);
			}else{
				$photo_name = $this->transform($photo); //если не надо названия или его нет - очистка фото от мусора
				$photo_name = str_replace("." . $ext, '', $photo_name); //убираем расширение
			}

			//скан папки для обхода фото и обнаружения последнего идентификатора
			$photo_dir_id = $this->getDirectory(1) . $product_id;
			if (!is_dir($photo_dir_id)) { //если нет директории
				mkdir($photo_dir_id, 0777, true); //создаем
			}

			if(!$this->config->get('module_easyphoto_separate')){
				$easyphoto_separate = "-";
			}else{
				$easyphoto_separate = trim($this->config->get('module_easyphoto_separate'));
				$easyphoto_separate = str_replace(array('"',"'","&","/"), "-", $easyphoto_separate);
			}
			//3.1-

			$all_photos = scandir($photo_dir_id); //берем все файлы в массив
			$counter = count($all_photos)-1; //счетчик для текущей фотографии
			//3.1+
			$new_photo_name = $photo_name . $easyphoto_separate . $counter . $ext; //новое имя фото со счетчиком
			//3.1-
			if (is_file($photo_dir_id . '/' . $new_photo_name)) {
				$new_photo_name = "alt_" . $new_photo_name;
			}

			if(!$from_model){ //прямая загрузка
				$old_file = $this->getDirectory(1) . "tmp/" . $photo;
			}else{
				$old_file = DIR_IMAGE . $photo;
			}

			if(is_file($old_file)){
				if(!$from_model){ //прямая загрузка
					rename($old_file, $photo_dir_id . '/' . $new_photo_name); //перемещаем в папку с id товара
				}else{
					copy($old_file, $photo_dir_id . '/' . $new_photo_name); //копируем! в папку с id товара - если новый товар
				}
			}

			$photo = $new_photo_name; //передаем уже новое фото на ресайз и в админку
			$directory = $this->getDirectory() . $product_id . '/';
		}

		$image = array();
		$image['mt'] = str_replace(".", "", microtime(true));
		$this->load->model('tool/image');
		$image['image'] = $directory . $photo;

		//webp
			$image_body = file_get_contents(DIR_IMAGE . $image['image']);
			$new_ext = $this->getImageMimeType($image_body, $image['image']);
				if($new_ext == 'webp'){
					$im = imagecreatefromwebp(DIR_IMAGE . $image['image']);
					unlink(DIR_IMAGE . $image['image']);
					$image['image'] = str_replace('.webp', '.png', $image['image']);
					imagepalettetotruecolor($im);
					imagealphablending($im, true);
					imagesavealpha($im, true);
					imagepng($im, DIR_IMAGE . $image['image']);
					imagedestroy($im);
				}
		//webp

		if(!$from_model){
			if (is_file(DIR_IMAGE . $image['image'])) {
				$image['thumb'] = $this->model_tool_image->resize($image['image'], 100, 100);
			} else {
				$image['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
			}
			if(!$image['thumb']){
				$image = false;
			}

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($image));
		}else{
			return $image['image'];
		}
	}

	//webp
		private function getBytesFromHexString($hexdata){
			for($count = 0; $count < strlen($hexdata); $count+=2){
				$bytes[] = chr(hexdec(substr($hexdata, $count, 2)));
			}

			return implode($bytes);
		}

		private function getImageMimeType($imagedata, $image_name){
			$return = NULL;

			$imagemimetypes = array(
				"jpg" => "FFD8",
				"png"  => "89504E470D0A1A0A",
				"gif"  => "474946",
				"bmp"  => "424D",
				"tiff" => "4949",
				"tiff" => "4D4D"
			);

			foreach($imagemimetypes as $mime => $hexbytes){
				$bytes = $this->getBytesFromHexString($hexbytes);
				if(substr($imagedata, 0, strlen($bytes)) == $bytes){
					$return = $mime;
				}
			}

			//webp
			if($return == NULL){
				$tmpfile = DIR_IMAGE . $image_name . '.tmp';
				file_put_contents($tmpfile, $imagedata);
				if(is_file($tmpfile)){
					if(exif_imagetype($tmpfile) === IMAGETYPE_WEBP){
						$return = 'webp';
					}
					unlink($tmpfile);
				}

				if(substr($imagedata, 0, 10) == 'RIFFP2WEBP'){
					$return = 'webp';
				}

				if(strpos($imagedata, '4  WEBPVP8') !== FALSE){ //4  WEBPVP8
					$return = 'webp';
				}
			}
			//webp

			return $return;
		}
	//webp

	public function clear_cart(){
		$count_cart = 0;

		if(isset($this->request->get['product_image_delete'])){
			foreach($this->request->get['product_image_delete'] as $filename){
				$file = DIR_IMAGE . $filename['image'];
				if (file_exists($file)){
					unlink($file);
					$count_cart++;
				}
			}
		}

		echo $count_cart;
	}

	public function clear_tmp(){
		$dir = DIR_IMAGE . $this->getDirectory() . 'tmp/';
		if (file_exists($dir)){
			foreach(glob($dir . '*') as $file){
				unlink($file);
			}
		}
	}

	public function transform($string){ //3.0
		if($string){
			$translit=array(
			  "ї"=>"yi","і"=>"i","А"=>"a","Б"=>"b","В"=>"v","Г"=>"g","Д"=>"d","Е"=>"e","Ё"=>"e","Ж"=>"zh","З"=>"z","И"=>"i","Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n","О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t","У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch","Ш"=>"sh","Щ"=>"shch","Ъ"=>"","Ы"=>"y","Ь"=>"","Э"=>"e","Ю"=>"yu","Я"=>"ya",
			  "Ї"=>"yi","І"=>"i","а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"e","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"shch","ъ"=>"","ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
			  "A"=>"a","B"=>"b","C"=>"c","D"=>"d","E"=>"e","F"=>"f","G"=>"g","H"=>"h","I"=>"i","J"=>"j","K"=>"k","L"=>"l","M"=>"m","N"=>"n","O"=>"o","P"=>"p","Q"=>"q","R"=>"r","S"=>"s","T"=>"t","U"=>"u","V"=>"v","W"=>"w","X"=>"x","Y"=>"y","Z"=>"z"
			);
			$string = str_replace("_", "-", $string);
			$string = mb_strtolower($string, 'UTF-8');
			$string = strip_tags($string);
			$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
			$string = strtr($string,$translit);
			$string = preg_replace("/[^a-zA-Z0-9_]/i","-",$string);
			$string = preg_replace("/\-+/i","-",$string);
			$string = preg_replace("/(^\-)|(\-$)/i","",$string);
			$string = preg_replace('/-{2,}/', '-', $string);
			$string = trim($string, "-");

			return $string;
		}
	}

	public function rotate() {

		$status = false;

		$degrees = $this->request->get['degrees'];
		$ext = strtolower(preg_replace('/^.*\.(.*)$/s', '$1', $this->request->get['photo']));

		$rotateFilename = str_replace("." . $ext, "", $this->request->get['photo']);
		$new_file = str_replace(array("_r90", "_r270"), "", $rotateFilename) . "_r" . $degrees . "." . $ext;
		$rotateFilename = DIR_IMAGE . $new_file; //новый файл

		copy(DIR_IMAGE . $this->request->get['photo'], $rotateFilename); //копируем  в name_r90.ext

		if($ext == 'png'){
		   header('Content-type: image/png');
		   $source = imagecreatefrompng($rotateFilename);
		   $bgColor = imagecolorallocatealpha($source, 255, 255, 255, 127);
		   $rotate = imagerotate($source, $degrees, $bgColor);
		   imagesavealpha($rotate, true);
		   imagepng($rotate, $rotateFilename);
			 $status = true;
		}

		if($ext == 'jpg' || $ext == 'jpeg'){
		   header('Content-type: image/jpeg');
		   $source = imagecreatefromjpeg($rotateFilename);
		   $rotate = imagerotate($source, $degrees, 0);
		   imagejpeg($rotate, $rotateFilename);
			 $status = true;
		}

		if($status){
			imagedestroy($source);
			imagedestroy($rotate);

			$image = array();
			$this->load->model('tool/image');
			$image['image'] = $new_file;
			$image['mt'] = str_replace(".", "", microtime(true));

			if (is_file(DIR_IMAGE . $image['image'])) {
				$image['thumb'] = $this->model_tool_image->resize($image['image'], 100, 100);
			} else {
				$image['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
			}
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($image));
		}

	}

	public function getDirectory($full = false) {

		$directory_set = $this->config->get('module_easyphoto_direct')?rtrim(ltrim($this->config->get('module_easyphoto_direct'), '/'), '/'):'easyphoto';
		$directory = "catalog/" . $directory_set . "/";

		if($full){
			$directory = DIR_IMAGE . $directory;
		}

		return $directory;
	}

	public function send() {
		$response = array();
		$response['content'] = '';
		$response['status'] = false;

		// $this->load->language($this->path);
		//
		// $domen = explode("//", HTTP_CATALOG);
		//
		// $prepare_data = array(
		// 	'email'     => $this->config->get('config_email'),
		// 	'module'    => $this->language->get('module') . " " . $this->language->get('version'),
		// 	'site' 	    => str_replace("/", "", $domen[1]),
		// 	'sec_user_token' => "3274507573",
		// 	'method'	=> 'POST',
		// 	'lang'		=> $this->config->get('config_language'),
		// 	'engine'	=> VERSION,
		// 	'date'		=> date("Y-m-d H:i:s")
		// );
		//
		// $curl = curl_init();
		//
	  // curl_setopt($curl, CURLOPT_URL, "https://microdata.pro/index.php?route=sale/easyphoto");
	  // curl_setopt($curl, CURLOPT_HEADER, false);
	  // curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	  // curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	  // curl_setopt($curl, CURLOPT_POST, true);
	  // curl_setopt($curl, CURLOPT_POSTFIELDS, $prepare_data);
	  // $a_number = curl_exec($curl);
	  // $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	  // curl_close($curl);
		//
		// if(!$code){
	  //   $domen = explode("//", HTTP_CATALOG);
	  //   $pd = $prepare_data;
	  //   $urlp = "https://microdata.pro/index.php?route=sale/easyphoto";
	  //   foreach ($pd as $key => $value) {$urlp .= '&' . $key . '=' . $value;}
	  //   $a_number = $this->xssСlean(file_get_contents(str_replace(" ", "---", $urlp)));
	  // }
		//
		// $response_type = explode("::", $a_number);
	  // if($a_number && isset($response_type[0]) && $response_type[0] != 'Notice'){
	  //   $this->a = $a_number;
		//
		// 	$response['content'] = $a_number;
		// 	$response['status'] = true;
		//
	  //   $this->db->query("UPDATE " . DB_PREFIX . "setting SET `value` = '" . $this->db->escape($a_number) . "', serialized = '0'  WHERE `" . (((int)VERSION >= 2)?'code':'group') . "` = 'unixml' AND `key` = 'unixml_key' AND store_id = '0'");
	  // }

		$response['content'] = true;
		$response['status'] = true;

		return $response;
	}

	public function key($key){
		$domen = explode("//", HTTP_CATALOG);
		$license = false;
		$a=0;if(isset($key) && !empty($key)){ $key_array = explode("327450", base64_decode(strrev(substr($key, 0, -7))));if($key_array[0] == base64_encode(str_replace("/", "", $domen[1])) && $key_array[1] == base64_encode(3274507473+100)){$a= 1;}}
		return $license=str_replace($key,str_replace("/", "", $domen[1]),$a);
	}

	private function xssСlean($data){
		// Fix &entity\n;
		$data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
		// Remove any attribute starting with "on" or xmlns
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
		// Remove javascript: and vbscript: protocols
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
		// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
		// Remove namespaced elements (we do not need them)
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
		do{
      // Remove really unwanted tags
      $old_data = $data;
      $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|script|title|xml)[^>]*+>#i', '', $data);
		}
		while ($old_data !== $data);
		// we are done...
		return $data;
	}

}
