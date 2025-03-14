<?php
class ControllerApiExport1c extends Controller {

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
}