<?php
class ControllerApiTransfer extends Controller
{
    public function index(){
    }

    public function import_manufaturer()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        $mysqli = new mysqli("ilweb.mysql.ukraine.com.ua", "ilweb_prnawiteh", "Nawiteh2022-1", "ilweb_prnawiteh");

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli->connect_error;
            exit();
        }

        $sql = "SELECT m.id_manufacturer, m.name, ml_en.description as description_en, ml_en.short_description as short_description_en, ml_en.meta_title as meta_title_en, ml_en.meta_keywords as meta_keywords_en, ml_en.meta_description as meta_description_en, ml_ua.description as description_ua, ml_ua.short_description as short_description_ua, ml_ua.meta_title as meta_title_ua, ml_ua.meta_keywords as meta_keywords_ua, ml_ua.meta_description as meta_description_ua FROM ps_manufacturer m LEFT JOIN ps_manufacturer_lang ml_en ON (ml_en.id_manufacturer = m.id_manufacturer AND ml_en.id_lang=1) LEFT JOIN ps_manufacturer_lang ml_ua ON (ml_ua.id_manufacturer = m.id_manufacturer AND ml_ua.id_lang=2) WHERE 1 LIMIT 100,400";
        $result = $mysqli->query($sql);
        $i = 1;

        /*$this->db->query("DELETE FROM ".DB_PREFIX."seo_url WHERE query LiKE '%manufacturer_id=%'");
        $this->db->query("DELETE FROM ".DB_PREFIX."manufacturer");
        $this->db->query("DELETE FROM ".DB_PREFIX."manufacturer_description");
        $this->db->query("DELETE FROM ".DB_PREFIX."manufacturer_to_layout");
        $this->db->query("DELETE FROM ".DB_PREFIX."manufacturer_to_store");*/
        while ($row = $result->fetch_array()) {
            var_dump($row['id_manufacturer']);
            $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET manufacturer_id = ".intval($row['id_manufacturer']).", `name` = '" . $this->db->escape($row['name']) . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_description SET manufacturer_id = ".intval($row['id_manufacturer']).", language_id = 3, description = '".$this->db->escape($row['description_ua'])."', description3 = '".$this->db->escape($row['short_description_ua'])."', meta_description = '".$this->db->escape($row['meta_description_ua'])."', meta_keyword = '".$this->db->escape($row['meta_keywords_ua'])."', meta_title = '".$this->db->escape($row['meta_title_ua'])."',  meta_h1 = '".$this->db->escape($row['name'])."'");
            //$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_description SET manufacturer_id = ".intval($row['id_manufacturer']).", language_id = 2, description = '".$this->db->escape($row['description_en'])."', description3 = '".$this->db->escape($row['short_description_en'])."', meta_description = '".$this->db->escape($row['meta_description_en'])."', meta_keyword = '".$this->db->escape($row['meta_keywords_en'])."', meta_title = '".$this->db->escape($row['meta_title_en'])."',  meta_h1 = '".$this->db->escape($row['name'])."'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_layout SET manufacturer_id = ".intval($row['id_manufacturer']).", store_id = 0, layout_id = 0");
            $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = ".intval($row['id_manufacturer']).", store_id = 0");
            echo $i . '-ый добавлен!<br>';
            $i++;
        }

        echo 'Цикл закончен!<br>';
        $mysqli -> close();

    }

    public function repair_image_by_product_id(){
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        $mas_id = $this->config->get('dw_add_mas_id');
        $mas1c_id = $this->config->get('dw_add_mas1c_id');
        if($mas_id == ''){
            $mas_id = [];
        }else $mas_id = unserialize($mas_id);
        if($mas1c_id == ''){
            $mas1c_id = [];
        }else $mas1c_id = unserialize($mas1c_id);


        $mysqli = new mysqli("ilweb.mysql.ukraine.com.ua", "ilweb_prnawiteh", "Nawiteh2022-1", "ilweb_prnawiteh");

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli->connect_error;
            exit();
        }

        //$product_ids_from_csv = array_map('trim', file('path/to/product_ids.csv', FILE_IGNORE_NEW_LINES));
        $cnt_i_prod = 0;
        $sql_product = "SELECT * FROM ps_product WHERE id_product IN (182,194,291,334,373,642,773,786,791,795,799,876,970,988,994,1049,1055,1057,1059,1077,1109,1110,1118,1207,1215,1224,1226,1227,1234,1242,1258,1315,1335,1447,1448,1449,1500,1502,1753,1756,1757,1790,100001308,100001312,100001401,100001408,100001409,100001411,100001515,100001522,100001526,100001528,100001532,100001534,100001535,100001537,100001547,100001574,100001615,100001617,100001621,100001623,100001624,100001625,100001626,100001632,100001682,100001716,100001717,100001723,100001724,100001731,100001732,100001754,100001755,100001785,100006288,100006289,100006863,100006948,100006960,100007162,100007166,100007257,100007404,100007708,100008297,100008559,100008793,100008794,100008795,100008869,100008874,100009006,100009022,100009272,100009398,100009434,100009510,100009895,100009897,100010092,100010168,100010169,100010173,100010251,100010368,100010568,100010582,100010622,100010953,100011099,100011121,100011122,100011239,100011405,100011406,100011478,100011553,100011566,100011723,100011776)";
        $result_product = $mysqli->query($sql_product);

// Переносимо зображення лише якщо товар є в списку з CSV-файлу
        //if (in_array($row['id_product'], $product_ids_from_csv)) {
        while ($row_product = $result_product->fetch_array()) {
            $cnt_i_prod++;
            $ex_product = $this->db->query("SELECT p.product_id, p.image, p.sku, p.id_1c, pd.name as name_ua FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (pd.product_id = p.product_id AND pd.language_id = 3) WHERE p.product_id = '".$row_product['id_product']."'");
            $ex_product = $ex_product->rows;

            /*if(in_array($ex_product[0]['product_id'], $mas_id)){
                continue;
            }
            if(in_array($ex_product[0]['id_1c'], $mas1c_id)){
                continue;
            }*/

            /*if(COUNT($ex_product) > 1 && $row_product['id_1c'] != ''){
                file_put_contents(DIR_LOGS.'impphoto_dubl.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."\nIDS:\n" ,FILE_APPEND);
                file_put_contents(DIR_LOGS.'impphoto_dubl.log', $ex_product[0]['product_id']."\n" ,FILE_APPEND);
                echo "---------------<br>1c - ".$row_product['id_1c'].'; Product old ID - '.$row_product['id_product'];
                echo "<br>IDs: <br>".$ex_product[0]['product_id'];
                for($ip=1;$ip<COUNT($ex_product);$ip++){
                    $mas_id[] = $ex_product[$ip]['product_id'];
                    echo "<br>".$ex_product[$ip]['product_id'];
                    file_put_contents(DIR_LOGS.'impphoto_dubl.log', $ex_product[$ip]['product_id']."\n" ,FILE_APPEND);
                    @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_product[$ip]['image']);
                    @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product[$ip]['image']['sku']).'.jpg');
                    @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product[$ip]['image']['sku']).'.png');
                    $this->db->query("UPDATE ".DB_PREFIX."product SET image = '' WHERE product_id = ".intval($ex_product[$ip]['product_id']));
                    $ex_images = $this->db->query("SELECT * FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product[$ip]['product_id']));
                    $ex_images = $ex_images->rows;
                    if(!empty($ex_images)){
                        foreach($ex_images as $ex_image){
                            @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_image['image']);
                        }
                    }
                    $this->db->query("DELETE FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product[$ip]['product_id']));
                }
                file_put_contents(DIR_LOGS.'impphoto_dubl.log', "---------------\n" ,FILE_APPEND);
                echo "<br>------------<br>";
            }*/
            if(COUNT($ex_product) == 0){
                file_put_contents(DIR_LOGS.'impphoto_not_exist.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."\n------------------\n" ,FILE_APPEND);
                continue;
            }
            if($row_product['id_1c'] == ''){
                file_put_contents(DIR_LOGS.'impphoto_empty_1c.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."\n------------------\n" ,FILE_APPEND);
                continue;
            }

            $ex_product = $ex_product[0];
            $mas_id[] = $ex_product['product_id'];
            $mas1c_id[] = $ex_product['id_1c'];
            $sql_image = "SELECT * FROM ps_image WHERE id_product = ".intval($row_product['id_product'])." ORDER BY position";
            $result_image = $mysqli->query($sql_image);
            if($result_image->num_rows > 0){
                file_put_contents(DIR_LOGS.'impphoto_changed.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."Product ID new: ".$ex_product['product_id']."\n------------------\n" ,FILE_APPEND);
            }else{
                file_put_contents(DIR_LOGS.'impphoto_new_photo.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."Product ID new: ".$ex_product['product_id']."\n------------------\n" ,FILE_APPEND);
                continue;
            }

            @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_product['image']);
            @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product['sku']).'.jpg');
            @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product['sku']).'.png');
            $ex_images = $this->db->query("SELECT * FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product['product_id']));
            $ex_images = $ex_images->rows;
            if(!empty($ex_images)){
                foreach($ex_images as $ex_image){
                    @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_image['image']);
                }
            }
            $this->db->query("DELETE FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product['product_id']));
            $iimg = 0;
            $cover_image_inserted = false;
            while ($row_image = $result_image->fetch_array()) {
                $mas_img = str_split($row_image['id_image']);
                $url_img_ar = implode('/', $mas_img);
                $url_img = 'https://nawiteh.com.ua/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.jpg';
                $doc_img = '/home/ilweb/nawiteh.com.ua/www/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.jpg';
                $ext = 'jpg';
                if (!file_exists($doc_img)) {
                    $url_img = 'https://nawiteh.com.ua/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.png';
                    $doc_img = '/home/ilweb/nawiteh.com.ua/www/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.png';
                    $ext = 'png';
                }
                $img_cur = file_get_contents($url_img);
                mkdir('/home/ilweb/nawiteh.ua/www/image/catalog/products/'.$this->transliterate($ex_product['sku']).'/');
                if ($row_image['cover'] == 1 && !$cover_image_inserted) {
                    $name_i_f = $this->transliterate($ex_product['name_ua']) . '_1.' . $ext;
                    $this->db->query("UPDATE " . DB_PREFIX . "product SET image = 'catalog/products/".$this->transliterate($ex_product['sku']).'/' . $name_i_f . "' WHERE product_id = " . intval($ex_product['product_id']));
                    $cover_image_inserted = true;
                } else {
                    $name_i_f = $this->transliterate($ex_product['name_ua']) . '_' . ($iimg + 2) . '.' . $ext;
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET image = 'catalog/products/".$this->transliterate($ex_product['sku']).'/' . $name_i_f . "', product_id = " . intval($ex_product['product_id']) . ", sort_order = " . intval($row_image['position']));
                }

                file_put_contents('/home/ilweb/nawiteh.ua/www/image/catalog/products/'.$this->transliterate($ex_product['sku']).'/' . $name_i_f, $img_cur);
                $iimg++;
            }
            echo $cnt_i_prod.". ".$ex_product['name_ua']." - ".$ex_product['product_id']." - ".$ex_product['id_1c']."<br>";

        }

        $this->db->query("DELETE FROM ".DB_PREFIX."setting WHERE `key` = 'dw_add_mas_id'");
        $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `key` = 'dw_add_mas_id', store_id = 0, `value` = '".serialize($mas_id)."', code = 'dw_add', serialized = 0");
        $this->db->query("DELETE FROM ".DB_PREFIX."setting WHERE `key` = 'dw_add_mas1c_id'");
        $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `key` = 'dw_add_mas1c_id', store_id = 0, `value` = '".serialize($mas1c_id)."', code = 'dw_add', serialized = 0");
        echo '<pre>';
        echo print_r($mas_id,1);
        echo '</pre>';
        //}
    }

    public function repair_image(){
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        $mas_id = $this->config->get('dw_add_mas_id');
        $mas1c_id = $this->config->get('dw_add_mas1c_id');
        if($mas_id == ''){
            $mas_id = [];
        }else $mas_id = unserialize($mas_id);
        if($mas1c_id == ''){
            $mas1c_id = [];
        }else $mas1c_id = unserialize($mas1c_id);


        $mysqli = new mysqli("ilweb.mysql.ukraine.com.ua", "ilweb_prnawiteh", "Nawiteh2022-1", "ilweb_prnawiteh");

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli->connect_error;
            exit();
        }

        //$product_ids_from_csv = array_map('trim', file('path/to/product_ids.csv', FILE_IGNORE_NEW_LINES));
        $cnt_i_prod = 0;
        $sql_product = "SELECT * FROM ps_product WHERE id_product IN (182,194,291,334,373,642,773,786,791,795,799,876,970,988,994,1049,1055,1057,1059,1077,1109,1110,1118,1207,1215,1224,1226,1227,1234,1242,1258,1315,1335,1447,1448,1449,1500,1502,1753,1756,1757,1790,100001308,100001312,100001401,100001408,100001409,100001411,100001515,100001522,100001526,100001528,100001532,100001534,100001535,100001537,100001547,100001574,100001615,100001617,100001621,100001623,100001624,100001625,100001626,100001632,100001682,100001716,100001717,100001723,100001724,100001731,100001732,100001754,100001755,100001785,100006288,100006289,100006863,100006948,100006960,100007162,100007166,100007257,100007404,100007708,100008297,100008559,100008793,100008794,100008795,100008869,100008874,100009006,100009022,100009272,100009398,100009434,100009510,100009895,100009897,100010092,100010168,100010169,100010173,100010251,100010368,100010568,100010582,100010622,100010953,100011099,100011121,100011122,100011239,100011405,100011406,100011478,100011553,100011566,100011723,100011776)";
        $result_product = $mysqli->query($sql_product);

// Переносимо зображення лише якщо товар є в списку з CSV-файлу
        //if (in_array($row['id_product'], $product_ids_from_csv)) {
        while ($row_product = $result_product->fetch_array()) {
            $cnt_i_prod++;
            $ex_product = $this->db->query("SELECT p.product_id, p.image, p.sku, p.id_1c, pd.name as name_ua FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (pd.product_id = p.product_id AND pd.language_id = 3) WHERE p.id_1c = '".$row_product['id_1c']."'");
            $ex_product = $ex_product->rows;

            if(in_array($ex_product[0]['product_id'], $mas_id)){
                continue;
            }
            if(in_array($ex_product[0]['id_1c'], $mas1c_id)){
                continue;
            }

            if(COUNT($ex_product) > 1 && $row_product['id_1c'] != ''){
                file_put_contents(DIR_LOGS.'impphoto_dubl.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."\nIDS:\n" ,FILE_APPEND);
                file_put_contents(DIR_LOGS.'impphoto_dubl.log', $ex_product[0]['product_id']."\n" ,FILE_APPEND);
                echo "---------------<br>1c - ".$row_product['id_1c'].'; Product old ID - '.$row_product['id_product'];
                echo "<br>IDs: <br>".$ex_product[0]['product_id'];
                for($ip=1;$ip<COUNT($ex_product);$ip++){
                    $mas_id[] = $ex_product[$ip]['product_id'];
                    echo "<br>".$ex_product[$ip]['product_id'];
                    file_put_contents(DIR_LOGS.'impphoto_dubl.log', $ex_product[$ip]['product_id']."\n" ,FILE_APPEND);
                    @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_product[$ip]['image']);
                    @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product[$ip]['image']['sku']).'.jpg');
                    @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product[$ip]['image']['sku']).'.png');
                    $this->db->query("UPDATE ".DB_PREFIX."product SET image = '' WHERE product_id = ".intval($ex_product[$ip]['product_id']));
                    $ex_images = $this->db->query("SELECT * FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product[$ip]['product_id']));
                    $ex_images = $ex_images->rows;
                    if(!empty($ex_images)){
                        foreach($ex_images as $ex_image){
                            @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_image['image']);
                        }
                    }
                    $this->db->query("DELETE FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product[$ip]['product_id']));
                }
                file_put_contents(DIR_LOGS.'impphoto_dubl.log', "---------------\n" ,FILE_APPEND);
                echo "<br>------------<br>";
            }
            if(COUNT($ex_product) == 0){
                file_put_contents(DIR_LOGS.'impphoto_not_exist.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."\n------------------\n" ,FILE_APPEND);
                continue;
            }
            if($row_product['id_1c'] == ''){
                file_put_contents(DIR_LOGS.'impphoto_empty_1c.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."\n------------------\n" ,FILE_APPEND);
                continue;
            }

            $ex_product = $ex_product[0];
            $mas_id[] = $ex_product['product_id'];
            $mas1c_id[] = $ex_product['id_1c'];
            $sql_image = "SELECT * FROM ps_image WHERE id_product = ".intval($row_product['id_product'])." ORDER BY position";
            $result_image = $mysqli->query($sql_image);
            if($result_image->num_rows > 0){
                file_put_contents(DIR_LOGS.'impphoto_changed.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."Product ID new: ".$ex_product['product_id']."\n------------------\n" ,FILE_APPEND);
            }else{
                file_put_contents(DIR_LOGS.'impphoto_new_photo.log', "1c - ".$row_product['id_1c']."; Product old ID - ".$row_product['id_product']."Product ID new: ".$ex_product['product_id']."\n------------------\n" ,FILE_APPEND);
                continue;
            }

            @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_product['image']);
            @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product['sku']).'.jpg');
            @unlink('/home/ilweb/nawiteh.ua/www/image/catalog/'.$this->transliterate($ex_product['sku']).'.png');
            $ex_images = $this->db->query("SELECT * FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product['product_id']));
            $ex_images = $ex_images->rows;
            if(!empty($ex_images)){
                foreach($ex_images as $ex_image){
                    @unlink('/home/ilweb/nawiteh.ua/www/image/'.$ex_image['image']);
                }
            }
            $this->db->query("DELETE FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($ex_product['product_id']));
            $iimg = 0;
            $cover_image_inserted = false;
            while ($row_image = $result_image->fetch_array()) {
                $mas_img = str_split($row_image['id_image']);
                $url_img_ar = implode('/', $mas_img);
                $url_img = 'https://nawiteh.com.ua/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.jpg';
                $doc_img = '/home/ilweb/nawiteh.com.ua/www/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.jpg';
                $ext = 'jpg';
                if (!file_exists($doc_img)) {
                    $url_img = 'https://nawiteh.com.ua/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.png';
                    $doc_img = '/home/ilweb/nawiteh.com.ua/www/img/p/' . $url_img_ar . '/' . $row_image['id_image'] . '.png';
                    $ext = 'png';
                }
                $img_cur = file_get_contents($url_img);
                mkdir('/home/ilweb/nawiteh.ua/www/image/catalog/products/'.$this->transliterate($ex_product['sku']).'/');
                if ($row_image['cover'] == 1 && !$cover_image_inserted) {
                    $name_i_f = $this->transliterate($ex_product['name_ua']) . '_1.' . $ext;
                    $this->db->query("UPDATE " . DB_PREFIX . "product SET image = 'catalog/products/".$this->transliterate($ex_product['sku']).'/' . $name_i_f . "' WHERE product_id = " . intval($ex_product['product_id']));
                    $cover_image_inserted = true;
                } else {
                    $name_i_f = $this->transliterate($ex_product['name_ua']) . '_' . ($iimg + 2) . '.' . $ext;
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET image = 'catalog/products/".$this->transliterate($ex_product['sku']).'/' . $name_i_f . "', product_id = " . intval($ex_product['product_id']) . ", sort_order = " . intval($row_image['position']));
                }

                file_put_contents('/home/ilweb/nawiteh.ua/www/image/catalog/products/'.$this->transliterate($ex_product['sku']).'/' . $name_i_f, $img_cur);
                $iimg++;
            }
            echo $cnt_i_prod.". ".$ex_product['name_ua']." - ".$ex_product['product_id']." - ".$ex_product['id_1c']."<br>";

        }

        $this->db->query("DELETE FROM ".DB_PREFIX."setting WHERE `key` = 'dw_add_mas_id'");
        $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `key` = 'dw_add_mas_id', store_id = 0, `value` = '".serialize($mas_id)."', code = 'dw_add', serialized = 0");
        $this->db->query("DELETE FROM ".DB_PREFIX."setting WHERE `key` = 'dw_add_mas1c_id'");
        $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `key` = 'dw_add_mas1c_id', store_id = 0, `value` = '".serialize($mas1c_id)."', code = 'dw_add', serialized = 0");
        echo '<pre>';
        echo print_r($mas_id,1);
        echo '</pre>';
        //}
    }

    public function import_category()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        $mysqli = new mysqli("ilweb.mysql.ukraine.com.ua", "ilweb_prnawiteh", "Nawiteh2022-1", "ilweb_prnawiteh");

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli->connect_error;
            exit();
        }

        $sql = "SELECT c.id_category, c.id_parent, c.date_add, c.date_upd, c.active, c.position, cl_en.link_rewrite as link_rewrite_en, cl_ua.link_rewrite as link_rewrite_ua, cl_en.name as name_en, cl_en.description as description_en, cl_en.meta_title as meta_title_en, cl_en.meta_keywords as meta_keywords_en, cl_en.meta_description as meta_description_en, cl_ua.description as description_ua, cl_ua.name as name_ua, cl_ua.meta_title as meta_title_ua, cl_ua.meta_keywords as meta_keywords_ua, cl_ua.meta_description as meta_description_ua FROM ps_category c LEFT JOIN ps_category_lang cl_en ON (cl_en.id_category = c.id_category AND cl_en.id_lang=1) LEFT JOIN ps_category_lang cl_ua ON (cl_ua.id_category = c.id_category AND cl_ua.id_lang=2) WHERE 1 LIMIT 100,100";
        $result = $mysqli->query($sql);
        $i = 1;

        /* $this->db->query("DELETE FROM ".DB_PREFIX."seo_url WHERE query LiKE '%category_id=%'");
         $this->db->query("DELETE FROM ".DB_PREFIX."category");
         $this->db->query("DELETE FROM ".DB_PREFIX."category_description");
         $this->db->query("DELETE FROM ".DB_PREFIX."category_filter");
         $this->db->query("DELETE FROM ".DB_PREFIX."category_path");
         $this->db->query("DELETE FROM ".DB_PREFIX."category_to_layout");
         $this->db->query("DELETE FROM ".DB_PREFIX."category_to_store");*/
        while ($row = $result->fetch_array()) {
            var_dump($row['id_category']);
            $this->db->query("INSERT INTO " . DB_PREFIX . "category SET category_id = ".intval($row['id_category']).", parent_id = ".intval($row['id_parent']).", sort_order = ".intval($row['position']).", status = ".intval($row['active']).", `date_added` = '" . $this->db->escape($row['date_add']) . "', `date_modified` = '" . $this->db->escape($row['date_upd']) . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = ".intval($row['id_category']).", language_id = 3, description = '".$this->db->escape($row['description_ua'])."', `name` = '".$this->db->escape($row['name_ua'])."', meta_description = '".$this->db->escape($row['meta_description_ua'])."', meta_keyword = '".$this->db->escape($row['meta_keywords_ua'])."', meta_title = '".$this->db->escape($row['meta_title_ua'])."',  meta_h1 = '".$this->db->escape($row['name_ua'])."'");
            //$this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = ".intval($row['id_category']).", language_id = 2, description = '".$this->db->escape($row['description_en'])."', `name` = '".$this->db->escape($row['name_en'])."', meta_description = '".$this->db->escape($row['meta_description_en'])."', meta_keyword = '".$this->db->escape($row['meta_keywords_en'])."', meta_title = '".$this->db->escape($row['meta_title_en'])."',  meta_h1 = '".$this->db->escape($row['name_en'])."'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = ".intval($row['id_category']).", store_id = 0, layout_id = 0");
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = ".intval($row['id_category']).", store_id = 0");
            $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET language_id = 3, store_id = 0, query = 'category_id=".intval($row['id_category'])."', keyword = '".$this->db->escape($row['link_rewrite_ua'])."'");
            //$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET language_id = 2, store_id = 0, query = 'category_id=".intval($row['id_category'])."', keyword = '".$this->db->escape($row['link_rewrite_en'])."'");

            $level = 0;
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$row['id_parent'] . "' ORDER BY `level` ASC");
            foreach ($query->rows as $res) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$row['id_category'] . "', `path_id` = '" . (int)$res['path_id'] . "', `level` = '" . (int)$level . "'");

                $level++;
            }
            $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$row['id_category'] . "', `path_id` = '" . (int)$row['id_category'] . "', `level` = '" . (int)$level . "'");

            echo $i . '-ый добавлен!<br>';
            $i++;
        }

        echo 'Цикл закончен!<br>';
        $mysqli -> close();

    }

    public function import_products()
    {

        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        $mysqli = new mysqli("ilweb.mysql.ukraine.com.ua", "ilweb_prnawiteh", "Nawiteh2022-1", "ilweb_prnawiteh");

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli->connect_error;
            exit();
        }

        $sql = "SELECT p.id_product, p.id_manufacturer, p.id_category_default, p.ean13, p.isbn, p.upc, p.mpn, p.quantity, p.price, p.reference, p.width, p.height, p.depth, p.weight, p.active, p.available_date, p.date_add, p.date_upd, p.id_1c, pl_en.link_rewrite as link_rewrite_en, pl_ua.link_rewrite as link_rewrite_ua, pl_en.name as name_en, pl_en.description as description_en, pl_en.description_short as description_short_en, pl_en.meta_title as meta_title_en, pl_en.meta_keywords as meta_keywords_en, pl_en.meta_description as meta_description_en, pl_ua.description as description_ua, pl_ua.description_short as description_short_ua, pl_ua.name as name_ua, pl_ua.meta_title as meta_title_ua, pl_ua.meta_keywords as meta_keywords_ua, pl_ua.meta_description as meta_description_ua FROM ps_product p LEFT JOIN ps_product_lang pl_en ON (pl_en.id_product = p.id_product AND pl_en.id_lang=1) LEFT JOIN ps_product_lang pl_ua ON (pl_ua.id_product = p.id_product AND pl_ua.id_lang=2) WHERE 1 LIMIT 7000,500";
        $result = $mysqli->query($sql);


        /*$this->db->query("DELETE FROM ".DB_PREFIX."seo_url WHERE query LiKE '%product_id=%'");
        $this->db->query("DELETE FROM ".DB_PREFIX."product");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_description");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_attribute");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_discount");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_filter");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_image");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_option");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_option_value");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_recurring");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_related");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_related_article");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_special");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_to_category");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_to_download");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_to_layout");
        $this->db->query("DELETE FROM ".DB_PREFIX."product_to_store");*/
        $i = 1;
        while ($row = $result->fetch_array()) {
            var_dump($row['id_product']);
            $this->db->query("INSERT INTO " . DB_PREFIX . "product SET product_id = ".intval($row['id_product']).", quantity = ".intval($row['quantity']).", stock_status_id = 7, manufacturer_id = ".intval($row['id_manufacturer']).", price = ".floatval($row['price']).", width = ".floatval($row['width']).", `length` = ".floatval($row['depth']).", height = ".floatval($row['height']).", weight = ".floatval($row['weight']).", status = ".intval($row['active']).", `sku` = '" . $this->db->escape($row['reference']) . "', `model` = '" . $this->db->escape($row['reference']) . "', `upc` = '" . $this->db->escape($row['upc']) . "', `mpn` = '" . $this->db->escape($row['mpn']) . "', `ean` = '" . $this->db->escape($row['ean13']) . "', `isbn` = '" . $this->db->escape($row['isbn']) . "', `date_available` = '" . $this->db->escape($row['available_date']) . "', `date_added` = '" . $this->db->escape($row['date_add']) . "', `date_modified` = '" . $this->db->escape($row['date_upd']) . "', `id_1c` = '" . $this->db->escape($row['id_1c']) . "'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = ".intval($row['id_product']).", language_id = 3,  `name` = '".$this->db->escape($row['reference']).' '.$this->db->escape($row['name_ua'])."', meta_description = '".$this->db->escape($row['meta_description_ua'])."', meta_keyword = '".$this->db->escape($row['meta_keywords_ua'])."', meta_title = '".$this->db->escape($row['reference']).' '.$this->db->escape($row['meta_title_ua'])."',  meta_h1 = '".$this->db->escape($row['reference']).' '.$this->db->escape($row['name_ua'])."'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = ".intval($row['id_product']).", language_id = 2,  `name` = '".$this->db->escape($row['reference']).' '.$this->db->escape($row['name_en'])."', meta_description = '".$this->db->escape($row['meta_description_en'])."', meta_keyword = '".$this->db->escape($row['meta_keywords_en'])."', meta_title = '".$this->db->escape($row['reference']).' '.$this->db->escape($row['meta_title_en'])."',  meta_h1 = '".$this->db->escape($row['reference']).' '.$this->db->escape($row['name_en'])."'");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = ".intval($row['id_product']).", store_id = 0, layout_id = 0");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = ".intval($row['id_product']).", store_id = 0");
            $this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET language_id = 3, store_id = 0, query = 'product_id=".intval($row['id_product'])."', keyword = '".$this->db->escape($row['link_rewrite_ua'])."'");
            //$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET language_id = 2, store_id = 0, query = 'product_id=".intval($row['id_product'])."', keyword = '".$this->db->escape($row['link_rewrite_en'])."'");

            $manufacturer = $this->db->query("SELECT `name` FROM ".DB_PREFIX."manufacturer WHERE manufacturer_id = ".intval($row['id_manufacturer']));
            $manufacturer = $manufacturer->row;
            if($manufacturer){
                $this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET product_id = ".intval($row['id_product']).", attribute_id = 1959, language_id = 3, `text` = '".$this->db->escape($manufacturer['name'])."'");
            }

            $sql_cat = "SELECT * FROM ps_category_product WHERE id_product = ".intval($row['id_product']);
            $result_cat = $mysqli->query($sql_cat);
            while ($row_cat = $result_cat->fetch_array()) {
                $main_cat = 0;
                if($row['id_category_default'] == $row_cat['id_category']){
                    $main_cat = 1;
                }
                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET product_id = ".intval($row['id_product']).", category_id = ".intval($row_cat['id_category']).", main_category = ".intval($main_cat));
            }

            $sql_image = "SELECT * FROM ps_image WHERE id_product = ".intval($row['id_product'])." ORDER BY `position`";
            $result_image = $mysqli->query($sql_image);
            $iimg = 0;
            //sort by position
            while ($row_image = $result_image->fetch_array()) {
                $mas_img = str_split($row_image['id_image']);
                $url_img_ar = implode('/', $mas_img);
                $url_img = 'https://nawiteh.com.ua/img/p/'.$url_img_ar.'/'.$row_image['id_image'].'.jpg';
                $doc_img = '/home/ilweb/nawiteh.com.ua/www/img/p/'.$url_img_ar.'/'.$row_image['id_image'].'.jpg';
                $ext = 'jpg';
                if(!file_exists($doc_img)){
                    $url_img = 'https://nawiteh.com.ua/x                                                                                                                                    img/p/'.$url_img_ar.'/'.$row_image['id_image'].'.png';
                    $doc_img = '/home/ilweb/nawiteh.com.ua/www/img/p/'.$url_img_ar.'/'.$row_image['id_image'].'.png';
                    $ext = 'png';
                }
                $img_cur = file_get_contents($url_img);
                if($iimg == 0){
                    $sku_img = $this->transliterate($row['reference']);
                    $ext_new_img = $sku_img.'.'.$ext;
                    $this->db->query("UPDATE ".DB_PREFIX."product SET image = 'catalog/jcb/".$ext_new_img."' WHERE product_id = ".intval($row['id_product']));
                }else{
                    $sku_img = $this->transliterate($row['reference']).'-'.$this->transliterate($row['name_ua']);
                    $ext_new_img = $sku_img.'-'.($iimg+1).'.'.$ext;
                    $this->db->query("INSERT INTO ".DB_PREFIX."product_image SET image = 'catalog/jcb/".$ext_new_img."', product_id = ".intval($row['id_product']).", sort_order = ".intval($row_image['position']));
                }
                file_put_contents('/home/ilweb/nawiteh.com.ua/stage/image/catalog/jcb/'.$ext_new_img, $img_cur);
                $iimg++;
            }

            //Featured
            $sql_feature = "SELECT pfp.id_feature, pfvl.value FROM ps_feature_product pfp LEFT JOIN ps_feature_value_lang pfvl ON (pfvl.id_feature_value = pfp.id_feature_value AND pfvl.id_lang=2) WHERE pfp.id_product = ".intval($row['id_product']);
            $result_feature = $mysqli->query($sql_feature);
            $oct_stickers = [];
            $this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET product_id = ".intval($row['id_product']).", attribute_id = 1958, language_id = 3, `text` = '".$this->db->escape($row['reference'])."'");
            while ($row_feature = $result_feature->fetch_array()) {
                if($row_feature['id_feature'] == 17){
                    $oct_stickers['customer_oem'] = 'customer_oem';
                }
                if($row_feature['id_feature'] == 18){
                    $oct_stickers['customer_original'] = 'customer_original';
                }
                $this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET product_id = ".intval($row['id_product']).", attribute_id = ".intval($row_feature['id_feature']).", language_id = 3, `text` = '".$this->db->escape($row_feature['value'])."'");
            }
            if(!empty($row_feature)){
                $this->db->query("UPDATE ".DB_PREFIX."product SET oct_stickers = '".serialize($oct_stickers)."' WHERE product_id = ".intval($row['id_product']));
            }

            echo $i . '-ый добавлен!<br>';
            $i++;
        }
        echo 'Цикл закончен!<br>';
        $mysqli -> close();
    }
/*
    public function transliterate($textcyr = null, $textlat = null) {
        $cyr = array(
            'Є',  'Ї',  'І',  'є',  'ї',  'і',  'ж',  'ч',  'щ',   'ш',  'ю',  'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ъ', 'ь', 'я', 'ы', ' ', '|', '/', '\'', '+', ')', '(', '.',
            'Ж',  'Ч',  'Щ',   'Ш',  'Ю',  'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ъ', 'Ь', 'Я', 'Ы', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $lat = array(
            'ye', 'yi', 'i', 'ye', 'yi', 'i', 'zh', 'ch', 'sht', 'sh', 'yu', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'q', 'y', '-', '', '', '', '', '', '', '',
            'Zh', 'Ch', 'Sht', 'Sh', 'Yu', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'c', 'Y', 'X', 'Q' , 'Y', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        if($textcyr) return str_replace($cyr, $lat, $textcyr);
        else if($textlat) return str_replace($lat, $cyr, $textlat);
        else return null;
    }
*/
    public function transliterate($textcyr = null, $textlat = null) {
        $cyr = [
            'Є','Ї','І','Ґ','є','ї','і','ґ','ж','ч','щ','ш','ю','а','б','в','г','д','е','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ь','я',
            'Ж','Ч','Щ','Ш','Ю','А','Б','В','Г','Д','Е','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ь','Я',
            ' ','|','/','\\','"','\'','`','.','№','&','@','$','%','^','*','(',')','[',']','{','}','?','!','<','>'
        ];
        $lat = [
            'Ye','Yi','I','G','ye','yi','i','g','zh','ch','shch','sh','yu','a','b','v','h','d','e','z','y','i','k','l','m','n','o','p','r','s','t','u','f','kh','ts','','ia',
            'Zh','Ch','Shch','Sh','Yu','A','B','V','H','D','E','Z','Y','I','K','L','M','N','O','P','R','S','T','U','F','Kh','Ts','','Ya',
            '-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-'
        ];

        if ($textcyr !== null) {
            $text = $textcyr;
            $direction = 'to_latin';
        } elseif ($textlat !== null) {
            $text = $textlat;
            $direction = 'to_cyrillic';
        } else {
            return null;
        }

        // Переводимо текст у нижній регістр
        $text = mb_strtolower($text, 'UTF-8');

        if ($direction === 'to_latin') {
            // Замінюємо кириличні символи на латинські
            $text = str_replace($cyr, $lat, $text);

            // Видаляємо всі символи, крім літер, цифр та дефісу
            $text = preg_replace('/[^a-z0-9-]/', '-', $text);

            // Замінюємо послідовності дефісів на один дефіс
            $text = preg_replace('/-+/', '-', $text);

            // Видаляємо дефіси на початку і в кінці рядка
            $text = trim($text, '-');

            // Обмежуємо довжину slug'а (наприклад, до 255 символів)
            $text = mb_substr($text, 0, 255, 'UTF-8');

            // Видаляємо дефіс в кінці, якщо він залишився після обрізання
            $text = rtrim($text, '-');
        } else {
            // Для зворотної транслітерації просто замінюємо символи
            $text = str_replace($lat, $cyr, $text);
        }

        return $text;
    }
    public function generate_review(){
        $names = ['Ніка','Адріан','Альфред','Анастасія','Настя','Анатолій','Толя','Аркадій','Арсен','Арсеній','Сеня','Артемій','Афанасій','Богдан','Даня','Борислав','Броніслав','Валентина','Валентин','Валерія','Валерій','Василь','Вася','Вероніка','Віталій','Віталія','Влада','Владислава','Влад','Владлен','Владлєн','Володимир','Володя','Георгій','Євген','Женя','Іван','Ігор','Йосип','Костя','Лев','Олег','Орест','Павло','Паша','Ростислав','Руслан','Святослав','Серафим','Семен','Станіслав','Ярослав','Адам','Анатолій','Андрій','Антон','Аркадій','Арсен','Арсеній','Артем','Артур','Богдан','Богуслав','Борис','Валентин','Валерій','Василь','Вадим','Вадім','Вадік','Вадик','Віктор','Віталій','Влад','Владислав','Володимир','Всеволод',"В'ячеслав",'Вячеслав','Гаврило','Геннадій','Генадій','Георгій','Герасим','Гліб','Глеб','Гнат','Григорій','Данило','Денис','Дмитро','Євген','Євгеній','Зорян','Іван','Ігор','Ілля','Кирило','Костянтин','Лев','Левко','Леонід','Любомир','Маркіян','Макар','Максим','Макс','Марко','Матвій','Микита','Микола','Мирон','Мирослав','Михайло','Нестор','Олег','Олександр','Олексій','Омелян','Орест','Остап','Павло','Пантелеймон','Панас','Петро','Пилип','Потап','Родіон','Роман','Ростислав','Руслан','Святослав','Семен','Сергій','Слава','Станислав','Станіслав','Степан','Тарас','Федір','Яків','Ян','Ярослав'];
        $category_id = isset($this->request->get['category_id']) ? intval($this->request->get['category_id']) : 0;
        if($category_id > 0){
            $products = $this->db->query("SELECT DISTINCT ptc.product_id, p.quantity FROM ".DB_PREFIX."product_to_category ptc LEFT JOIN ".DB_PREFIX."product p ON p.product_id = ptc.product_id WHERE ptc.category_id = '".$category_id."'")->rows;
            if(!empty($products)){
                foreach($products as $product){
                    if($product['quantity'] < 3){
                        $to = mt_rand(1, 3);
                    }else if($product['quantity'] < 50){
                        $to = mt_rand(3, 10);
                    }else $to = mt_rand(10, 30);
                    $start = 1654601853;
                    $dif = intval((time() - 1654601853)/($to+2));
                    var_dump($product['product_id']);
                    var_dump('-----');
                    for($i=0;$i<$to;$i++){
                        $start += $dif + mt_rand(1, 9);
                        $ind = mt_rand(0, COUNT($names) - 1);
                        var_dump($names[$ind]);
                        $mdate = date('Y-m-d H:i:s', $start);
                        $this->db->query("INSERT INTO ".DB_PREFIX."review SET product_id = ".intval($product['product_id']).", customer_id = 0, author = '".$this->db->escape($names[$ind])."', rating = ".mt_rand(4,5).", status = 1, date_added = '".$mdate."', date_modified = '".$mdate."'");
                    }
                    var_dump('-----');
                    echo "<br>";
                }
            }
        }else{
            echo "Оберіть категорію!";
        }
    }

    public function update_product_desc(){
        $file = '/home/ilweb/nawiteh.ua/www/work/output.xml';
        $feed = simplexml_load_string(file_get_contents($file));
        if($feed){
            if(!empty($feed->item)){
                foreach($feed->item as $tot){
                    $sku = strval($tot->product_article);
                    $description = strval($tot->description);
                    $products = $this->db->query("SELECT pd.description, pd.name, p.product_id FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (pd.product_id = p.product_id AND pd.language_id = 3) WHERE p.sku = '".$sku."'")->rows;
                    $k = 1;
                    if(!empty($products)){
                        foreach($products as $product){
                            $this->db->query("UPDATE ".DB_PREFIX."product_description SET description = '".($product['description'].$this->db->escape($description))."' WHERE product_id = ".intval($product['product_id']));
                            echo $k.". До товару ".$product['name']." (ID - )".$product['product_id']." доданий опис<br>";
                            echo $product['description'].'<hr><br>';
                            $k++;
                        }
                    }
                }
            }else{
                echo "Немає товарів!";
            }
        }else{
            echo "Пустий файл чи файл відсутній!";
        }
        @unlink($file);
    }

    public function import_product_reviews(){
        $file = '/home/ilweb/nawiteh.ua/www/work/review.xml';
        $feed = simplexml_load_string(file_get_contents($file));
        $k = 0;
        if($feed){
            if(!empty($feed->item)){
                foreach($feed->item as $tot){
                    $code = strval($tot->code);
                    $product = $this->db->query("SELECT pd.description, pd.name, p.product_id FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (pd.product_id = p.product_id AND pd.language_id = 3) WHERE p.product_id = '".$code."' LIMIT 1")->row;
                    if($product){
                        foreach($tot->review as $review){
                            $k++;
                            $name = strval($review->name);
                            $date_added = strval($tot->reviw->date);
                            $this->db->query("INSERT INTO ".DB_PREFIX."review SET product_id = ".intval($product['product_id']).", customer_id = 0, author = '".$this->db->escape($name)."', rating = 5, status = 1, date_added = '".$date_added."', date_modified = '".$date_added."'");
                            echo $k.". До товару ".$product['name']." (ID - ".$product['product_id'].") доданий відгук від ".$name." (".$date_added.")<br>";
                        }
                    }else echo "Товару з кодом $code не знайдено!";
                    echo "<hr>";
                }
            }else{
                echo "Немає відгуків!";
            }
        }else{
            echo "Пустий файл чи файл відсутній!";
        }
        @unlink($file);
    }

    public function import_1C_prices(){
        $mas_ex = [];
        $mas_not_ex = [];

        $file = '/home/ilweb/nawiteh.ua/prices/Prices.xml';
        $dw_feed_price_page = intval($this->config->get('dw_feed_price_page'));

        if($dw_feed_price_page == 0) $dw_feed_price_page = 1;
        $per_page = 100000;

        $feed = simplexml_load_string(file_get_contents($file));
        $total = COUNT($feed->DECLARHEAD->products->product);

        $start = ($dw_feed_price_page-1)*$per_page;
        $end = ($dw_feed_price_page-1)*$per_page + $per_page;
        if($end > $total){
            $end = $total;
        }
        $k = 0;
        for ($i=$start;$i < $end;$i++){
            $k++;
            $product = $feed->DECLARHEAD->products->product[$i];
            $ex_products = $this->db->query("SELECT product_id, stock_status_id FROM ".DB_PREFIX."product WHERE id_1c = '".strval($product['code'])."'");
            $ex_products = $ex_products->rows;
            if(!empty($ex_products)){
                foreach($ex_products as $ex_product){
                    $product_ex_id = $ex_product['product_id'];

                    $pricevalue = floatval(str_replace([' ',' ',','], ['','','.'], strval($product->pricevalue)));

                    if(strval($product->pricetype) == '000000001'){
                        $this->db->query("UPDATE ".DB_PREFIX."product SET price = ".floatval($pricevalue)." WHERE product_id = ".$product_ex_id);
                    }
                    if(strval($product->pricetype) == '000000005'){
                        $this->db->query("DELETE FROM ".DB_PREFIX."product_special WHERE product_id = ".intval($product_ex_id));
                        $this->db->query("INSERT INTO ".DB_PREFIX."product_special SET product_id = ".intval($product_ex_id).", customer_group_id = 2, price = ".floatval($pricevalue));
                    }
                    //echo $k." Product ID - ".$product_ex_id."<br>";
                }
            }else{
                //echo "Нет товара - ".strval($product['code'])."<br>";
            }
        }
    }

    public function import_1C_qty(){

        $file = '/home/ilweb/nawiteh.ua/quantities/ZalyshokXML.xml';
        $dw_feed_qty_page = 1;
        $per_page = 100000;
        $ids_exists = [];

        $feed = simplexml_load_string(file_get_contents($file));
        $total = COUNT($feed->DECLARHEAD->products->product);

        $start = ($dw_feed_qty_page-1)*$per_page;
        $end = ($dw_feed_qty_page-1)*$per_page + $per_page;
        if($end > $total){
            $end = $total;
        }
        for ($i=$start;$i < $end;$i++){
            $product = $feed->DECLARHEAD->products->product[$i];
            $ex_products = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product WHERE id_1c = '".strval($product['code'])."'");
            $ex_products = $ex_products->rows;
            $quantity = intval(str_replace([' ',' ',','], ['','','.'], strval($product->quantity)));

            if(!empty($ex_products)){
                foreach($ex_products as $ex_product){
                    $product_ex_id = $ex_product['product_id'];
                    if(!in_array($product_ex_id,$ids_exists)){
                        $ids_exists[] = $product_ex_id;
                    }
                    $this->db->query("UPDATE ".DB_PREFIX."product SET quantity = ".intval($quantity)." WHERE product_id = ".intval($product_ex_id));
                }
            }
        }
        if(!empty($ids_exists)){
            $this->db->query("UPDATE ".DB_PREFIX."product SET quantity = 0 WHERE product_id NOT IN (".implode(',',$ids_exists).")");
        }
    }

    public function import_1C_updates(){
        file_put_contents(DIR_LOGS.'1c_updates_product.log', date("Y-m-d H:i:s")."\n" ,FILE_APPEND);

        $file = '/home/ilweb/nawiteh.ua/quantities/ZalyshokXML.xml';
        $dw_feed_qty_page = intval($this->config->get('dw_feed_qty_page'));

        if($dw_feed_qty_page == 0) $dw_feed_qty_page = 1;
        $per_page = 100000;

        $feed = simplexml_load_string(file_get_contents($file));
        $total = COUNT($feed->DECLARHEAD->products->product);

        $start = ($dw_feed_qty_page-1)*$per_page;
        $end = ($dw_feed_qty_page-1)*$per_page + $per_page;
        if($end > $total){
            $end = $total;
        }
        $k = 0;
        for ($i=$start;$i < $end;$i++){
            $k++;
            $product = $feed->DECLARHEAD->products->product[$i];
            $ex_products = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product WHERE id_1c = '".strval($product['code'])."'");
            $ex_products = $ex_products->rows;
            $quantity = intval(str_replace([' ',' ',','], ['','','.'], strval($product->quantity)));
            $article = explode(' ',strval($product['article']));
            $productname = $article[0].' '.strval($product['productname']);

            if(empty($ex_products)){
                $this->db->query("INSERT INTO ".DB_PREFIX."product SET model = '".$this->db->escape($article[0])."', sku = '".$this->db->escape($article[0])."', quantity = ".intval($quantity).", stock_status_id = 7, price = 0, status = 0, id_1c = '".strval($product['code'])."', date_added = '".date('Y-m-d H:i:s')."', date_modified = '".date('Y-m-d H:i:s')."'");
                $product_ex_id = $this->db->getLastId();
                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_store SET product_id = ".intval($product_ex_id).", store_id = 0");
                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_layout SET product_id = ".intval($product_ex_id).", store_id = 0, layout_id = 0");
                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET product_id = ".intval($product_ex_id).", category_id = 505");
                $this->db->query("INSERT INTO ".DB_PREFIX."product_description SET product_id = ".intval($product_ex_id).", language_id = 3, `name` = '".$this->db->escape($productname)."'");
                $slug = $this->transliterate(strval($product['code']).'-'.$productname);
                $this->db->query("INSERT INTO ".DB_PREFIX."seo_url SET store_id = 0, language_id = 3, `query` = 'product_id=".$product_ex_id."', `keyword` = '".$slug."'");
                $this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET product_id = ".intval($product_ex_id).", attribute_id = 1958, language_id = 3, `text` = '".$this->db->escape($article[0])."'");

                file_put_contents(DIR_LOGS.'1c_updates_product.log', $k." Product ID - ".$product_ex_id." (".$new.")\n" ,FILE_APPEND);
            }
            //echo $k." Product ID - ".$product_ex_id." (".$new.")<br>";
        }
    }

    public function export_orders(){
        $dir = '/home/ilweb/nawiteh.com.ua/orders/';

        $orders = $this->db->query("SELECT * FROM ".DB_PREFIX."order WHERE send_1c = 0");
        $orders = $orders->rows;
        if(!empty($orders)){
            foreach($orders as $k=>$order){
                $t = strtotime($order['date_added']);
                $comment = [];
                if($order['comment'] != ''){
                    $comment[] = $order['comment'];
                }
                if($order['shipping_method'] != ''){
                    $comment[] = $order['shipping_method'];
                }
                if($order['shipping_zone'] != ''){
                    $comment[] = $order['shipping_zone'];
                }
                if($order['shipping_city'] != ''){
                    $comment[] = $order['shipping_city'];
                }
                if($order['shipping_address_1'] != ''){
                    $comment[] = $order['shipping_address_1'];
                }
                if($order['shipping_address_2'] != ''){
                    $comment[] = $order['shipping_address_2'];
                }
                $xml = '<?xml version="1.0" encoding="utf-8" ?>
<data>
    <id>'.$order['order_id'].'</id>
    <dealerid>'.$order['email'].'</dealerid>
    <dealerdescription>'.$order['firstname'].' '.$order['lastname'].'</dealerdescription> 
    <dealertel>'.$order['telephone'].'</dealertel>
    <reserve>0</reserve>
    <tip_oplati>1</tip_oplati>
    <ordernumber>ORDER'.$order['order_id'].'</ordernumber>
    <date>'.$t.'</date>
    <description>'.implode(', ', $comment).'</description>
    <products>
    ';
                $product_detail = $this->db->query("SELECT op.model, op.quantity, op.price, p.id_1c FROM ".DB_PREFIX."order_product op LEFT JOIN ".DB_PREFIX."product p ON p.product_id = op.product_id WHERE order_id = ".intval($order['order_id']));
                $product_detail = $product_detail->rows;
                if(!empty($product_detail)){
                    foreach($product_detail as $product){
                        $xml .= '<product article="'.$product['model'].'" code="'.$product['id_1c'].'"><quantity>'.$product['quantity'].'</quantity><price>'.$product['price'].'</price></product>';
                    }
                }
                $xml .= '
    </products>
</data>';

                file_put_contents($dir.'order-'.$order['order_id'].'.xml',$xml, FILE_APPEND);
                $this->db->query("UPDATE ".DB_PREFIX."order SET send_1c = 1 WHERE order_id = ".intval($order['order_id']));
                $this->db->query("INSERT INTO ".DB_PREFIX."order_history SET order_id = ".intval($order['order_id']).", order_status_id = ".intval($order['order_status_id']).", notify = 0, comment = 'Замовлення додано до списку файлів для передачі в 1С', date_added = '".date('Y-m-d H:i:s')."'");
                echo ($k+1).". Order ID - ".$order['order_id']."<br>";
            }
        }
    }

    public function work(){
        $product_attribute = $this->db->query("SELECT pa.product_id, p.oct_stickers FROM ".DB_PREFIX."product_attribute pa LEFT JOIN ".DB_PREFIX."product p ON p.product_id = pa.product_id WHERE pa.attribute_id = 17");
        $product_attribute = $product_attribute->rows;

        if(!empty($product_attribute)){
            foreach($product_attribute as $ps){
                $oct_stickers = $ps['oct_stickers'];
                if($oct_stickers == ''){
                    $oct_stickers = ['customer_oem' => 'customer_oem'];
                }else{
                    $oct_stickers = unserialize($oct_stickers);
                    $oct_stickers['customer_oem'] = 'customer_oem';
                }
                $this->db->query("UPDATE ".DB_PREFIX."product SET oct_stickers = '".serialize($oct_stickers)."' WHERE product_id = ".intval($ps['product_id']));
            }
        }
    }

    public function import_perkins_jcb(){
        header('Content-Type: text/html; charset=utf-8');
        $file = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/JCB-PERKINS.csv');
        $file = explode("\n", $file);
        $is_exist = 0;
        $txt = [];
        $txt[] = 'Product ID, 1C ID, Name before, Name after';
        if(!empty($file)){
            foreach($file as $k=>$row){
                if($k == 0) continue;
                echo $k.'. <br>';
                $row_ar = explode(',', $row);
                $code1 = trim($row_ar[1]);
                $code2 = trim($row_ar[2]);
                $sql = "SELECT p.product_id, pd.name FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_description pd ON (pd.product_id = p.product_id AND pd.language_id = 3) WHERE (p.model = '".$code1."' or p.model = '".$code2."') OR (pd.name LIKE '%".$code1."%' or pd.name LIKE '%".$code2."%')";
                $ex_product = $this->db->query($sql);
                $ex_product = $ex_product->rows;
                if($ex_product){
                    if(!empty($ex_product)){
                        foreach($ex_product as $product){
                            $row_txt = [];
                            $is_exist++;
                            $row_txt[] = $product['product_id'];
                            $row_txt[] = $product['id_1c'];
                            $row_txt[] = $product['name'];
                            $new_name = str_replace([$code1.' ,', $code2.' ,'], '',$product['name']);
                            $new_name = str_replace([$code1.' ', $code2.' '], '',$new_name);
                            $new_name = str_replace([$code1, $code2], '',$new_name);
                            $new_name = $code1.', '.$code2.', '.$new_name;
                            $row_txt[] = $new_name;
                            $txt[] = implode(',', $row_txt);
                            $this->db->query("UPDATE ".DB_PREFIX."product_description SET `name` = '".$this->db->escape($new_name)."' WHERE product_id = ".intval($product['product_id'])." AND language_id = 3");
                            $cats = $this->db->query("SELECT * FROM ".DB_PREFIX."product_to_category WHERE product_id = ".intval($product['product_id']));
                            $cats = $cats->rows;
                            $is_jcb = false;
                            $is_perkins = false;
                            if(!empty($cats)){
                                foreach($cats as $cat){
                                    if($cat['category_id'] == 11) $is_jcb = true;
                                    if($cat['category_id'] == 74) $is_perkins = true;
                                }
                            }
                            if(!$is_jcb){
                                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET product_id = ".intval($product['product_id']).", category_id = 11");
                            }
                            if(!$is_perkins){
                                $this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET product_id = ".intval($product['product_id']).", category_id = 74");
                            }
                            $this->db->query("UPDATE ".DB_PREFIX."product_to_category SET main_category = 0 WHERE product_id = ".intval($product['product_id']));
                            $this->db->query("UPDATE ".DB_PREFIX."product_to_category SET main_category = 1 WHERE product_id = ".intval($product['product_id'])." AND category_id = 74");
                            $this->db->query("DELETE FROM ".DB_PREFIX."product_attribute WHERE product_id = ".intval($product['product_id'])." AND (attribute_id = 1963 OR attribute_id = 1962)");
                            $this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET product_id = ".intval($product['product_id']).", attribute_id = 1962, language_id = 3, `text` = '".$this->db->escape($code1)."'");
                            $this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET product_id = ".intval($product['product_id']).", attribute_id = 1963, language_id = 3, `text` = '".$this->db->escape($code2)."'");
                            echo "Записан товар с ИД ".$product['product_id']."<br>";
                        }
                    }
                }
            }
        }
        $txt = implode("\n", $txt);
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/work/new_jcb_perkins2.csv',  mb_convert_encoding($txt, 'Windows-1251', 'UTF-8'));
    }

    public function workIn(){
        $prids = $this->db->query("SELECT p.product_id FROM ".DB_PREFIX."product p LEFT JOIN ".DB_PREFIX."product_to_category ptc ON ptc.product_id = p.product_id WHERE ptc.category_id is null");
        $prids = $prids->rows;
        if(!empty($prids)){
            foreach($prids as $prid){
                $this->db->query("DELETE FROM ".DB_PREFIX."product WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_attribute WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_description WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_discount WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_filter WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_image WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_option WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_option_value WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_related WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_special WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_to_category WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_to_layout WHERE product_id = ".intval($prid['product_id']));
                $this->db->query("DELETE FROM ".DB_PREFIX."product_to_store WHERE product_id = ".intval($prid['product_id']));
            }
        }
    }

    public function moveImages() {
        // Функція для переміщення файлу
        $moveFile = function($oldPath, $newPath) {
            if (!file_exists($oldPath)) {
                $this->log->write("File not found: $oldPath");
                return false;
            }
            
            $dir = dirname($newPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (rename($oldPath, $newPath)) {
                $this->log->write("Moved: $oldPath to $newPath");
                return true;
            } else {
                $this->log->write("Failed to move: $oldPath");
                return false;
            }
        };

        // Отримання списку товарів
        $query = $this->db->query("SELECT product_id, image FROM " . DB_PREFIX . "product WHERE image LIKE 'catalog/1%'");

        foreach ($query->rows as $row) {
            $oldPath = DIR_IMAGE . $row['image'];
            $newPath = str_replace('catalog/', 'catalog/products/', $oldPath);
            
            if ($moveFile($oldPath, $newPath)) {
                // Оновлення шляху в базі даних
                $newDbPath = str_replace('catalog/', 'catalog/products/', $row['image']);
                $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($newDbPath) . "' WHERE product_id = " . (int)$row['product_id']);
                
                // Переміщення додаткових зображень
                $additionalQuery = $this->db->query("SELECT product_image_id, image FROM " . DB_PREFIX . "product_image WHERE product_id = " . (int)$row['product_id']);
                
                foreach ($additionalQuery->rows as $additionalRow) {
                    $oldAdditionalPath = DIR_IMAGE . $additionalRow['image'];
                    $newAdditionalPath = str_replace('catalog/', 'catalog/products/', $oldAdditionalPath);
                    
                    if ($moveFile($oldAdditionalPath, $newAdditionalPath)) {
                        $newAdditionalDbPath = str_replace('catalog/', 'catalog/products/', $additionalRow['image']);
                        $this->db->query("UPDATE " . DB_PREFIX . "product_image SET image = '" . $this->db->escape($newAdditionalDbPath) . "' WHERE product_image_id = " . (int)$additionalRow['product_image_id']);
                    }
                }
            }
        }

        $this->log->write("Image relocation process completed.");
        
        // Відправка відповіді
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['success' => true, 'message' => 'Image relocation completed']));
    }
    public function removeUnusedImages() {
        $json = [];

        // Параметри
        $dry_run = isset($this->request->get['dry_run']) ? (bool)$this->request->get['dry_run'] : true;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 100;

        $directory = DIR_IMAGE . 'catalog/products/';
        $deleted_count = 0;
        $total_count = 0;
        $unused_files = [];

        try {
            if (!is_dir($directory)) {
                throw new Exception("Вказана директорія не існує");
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                if ($fileinfo->isFile() && in_array(strtolower($fileinfo->getExtension()), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $total_count++;
                    $filepath = $fileinfo->getPathname();
                    $relativePath = str_replace(DIR_IMAGE, '', $filepath);

                    // Перевірка використання файлу в базі даних
                    $query = $this->db->query("SELECT 
                        (SELECT COUNT(*) FROM " . DB_PREFIX . "product WHERE image = '" . $this->db->escape($relativePath) . "') +
                        (SELECT COUNT(*) FROM " . DB_PREFIX . "product_image WHERE image = '" . $this->db->escape($relativePath) . "') as total");

                    if ($query->row['total'] == 0) {
                        $unused_files[] = $filepath;
                        if (count($unused_files) >= $limit) {
                            break;
                        }
                    }
                }
            }

            // Видалення або виведення списку файлів
            foreach ($unused_files as $file) {
                if (!$dry_run) {
                    if (unlink($file)) {
                        $deleted_count++;
                        $this->log->write("Видалено невикористаний файл: " . $file);
                    } else {
                        $this->log->write("Помилка при видаленні файлу: " . $file);
                    }
                } else {
                    $deleted_count++;
                }
            }

            $json['success'] = true;
            $json['dry_run'] = $dry_run;
            $json['unused_files'] = $unused_files;
            $json['message'] = $dry_run 
                ? "Сухий запуск. Знайдено {$deleted_count} невикористаних файлів з {$total_count}."
                : "Процес завершено. Видалено {$deleted_count} з {$total_count} файлів.";
        } catch (Exception $e) {
            $json['success'] = false;
            $json['error'] = $e->getMessage();
            $this->log->write("Помилка при очищенні зображень: " . $e->getMessage());
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

}