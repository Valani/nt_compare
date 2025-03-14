<?php
 class ModelExtensionModuleSearchSuggestion extends Model{public function install(){$this->load->model('design/layout');$a28e49bf3302e1a5d8a1e389d28022780=$this->model_design_layout->getLayouts();foreach($a28e49bf3302e1a5d8a1e389d28022780 as $a0547383a7233fbb34e582a1853358905){$this->db->query("INSERT INTO ".DB_PREFIX."layout_module SET layout_id = '".(int)$a0547383a7233fbb34e582a1853358905['layout_id']."', code = 'search_suggestion', position = 'content_top', sort_order = '0'");}}public function getDefaultOptions(){return array('element'=>"#search input[name='search']",'types_order'=>array('manufacturer'=>array('sort'=>0),'category'=>array('sort'=>1),'category_filter'=>array('sort'=>2),'product'=>array('sort'=>3),'information'=>array('sort'=>4),),'width'=>"100%",'color_scheme'=>"#1cbaf7",'css'=>' 
#search  .dropdown-menu {
	position: absolute;
	top: 100%;
	left: 0;
	z-index: 1000;
	display: none;
	float: left;
	min-width: 270px;
	padding: 5px 0;
	margin: 2px 0 0;
	font-size: 12px;
	text-align: left;
	list-style: none;
	background-color: #fff;
	-webkit-background-clip: padding-box;
					background-clip: padding-box;
	border: 1px solid #ccc;
	border: 1px solid rgba(0, 0, 0, .15);
	border-radius: 4px;
	-webkit-box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
					box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
}
#search .dropdown-menu.pull-right {
	right: 0;
	left: auto;
}
#search .dropdown-menu .divider {
	height: 1px;
	margin: 9px 0;
	overflow: hidden;
	background-color: #e5e5e5;
}
#search .dropdown-menu > li > a,
#search .dropdown-menu  li.disabled {
	display: block;
	padding: 3px 10px;
	clear: both;
	font-weight: normal;
	line-height: 1.42857143;
	color: #333;
	white-space: unset;
	text-decoration: none;
}
#search .dropdown-menu  li.inline a {
	border-radius: 5px;
	padding: 5px 5px;
}
#search .dropdown-menu  li.more a {
	padding: 0;
}
#search .dropdown-menu > li > a:hover,
#search .dropdown-menu > li > a:focus {
	color: #262626;
	text-decoration: none;
	background-color: #f5f5f5;
	background-image: none;
}
#search .dropdown-menu > .active > a,
#search .dropdown-menu > .active > a:hover,
#search .dropdown-menu > .active > a:focus {
	color: #fff;
	text-decoration: none;
	background-image: none;
	outline: 0;
}
#search .dropdown-menu > .disabled > a,
#search .dropdown-menu > .disabled > a:hover,
#search .dropdown-menu > .disabled > a:focus {
	color: #777;
}
#search .dropdown-menu > .disabled > a:hover,
#search .dropdown-menu > .disabled > a:focus {
	text-decoration: none;
	cursor: not-allowed;
	background-color: transparent;
	background-image: none;
	filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
}

#search .dropdown-menu { 
	max-width: 100%;
	overflow: hidden auto;
	max-height: 60vh;
}
#search .dropdown-menu::-webkit-scrollbar-track {
	background-color: transparent;
}
#search .dropdown-menu::-webkit-scrollbar {
	width: 4px;
	background-color: white;
}
#search .dropdown-menu::-webkit-scrollbar-thumb {
	background-color: rgba(0,0,0,0.2);
	border-radius: 10px;
}

#search .dropdown-menu li {
	list-style-image:  none !important;
	clear: both;
}
#search .dropdown-menu li:not(.disabled, .inline, .more) {
	border-bottom: 1px solid #f1f1f1;
}
#search .dropdown-menu li.inline { 
	display: inline-block;
	margin-left: 5px;
	vertical-align: top;
}
#search .dropdown-menu li.inline .search-suggestion{ 
	text-align: center;
}
#search .dropdown-menu li .title {
	font-size: 1em;
	text-transform: none;
	line-height: normal;
}
#search .dropdown-menu li.disabled .title {
	width: fit-content;
	padding-bottom: 5px;
	font-size: 1.2em;
}
.search-suggestion {
	overflow: hidden;
	width: 100%;
	display: flex;
	gap: 15px;
}
.search-suggestion .center {
	flex-grow: 1;
	min-width: 0;
}
li:not(.inline, .more) .search-suggestion .center > div {
	margin-bottom: 3px;
}
.search-suggestion .left, .search-suggestion .right  {
	align-self: center;
	text-align: center;
}
.search-suggestion .label {
	font-weight: normal;
	color: darkgray;
	padding-left: 0;
	padding-right: 5px;
}
.search-suggestion  .image img {
	border-radius: 5px;
}
.search-suggestion  .price-old {
	/*text-decoration: line-through;*/
	text-decoration: none;
	display: block;
	margin-right: 2px;
	color: #979393;  
	position: relative;	
	font-weight: normal;
	font-size: 0.8em;
}
.search-suggestion  .price-old:before {
	content: "";
	border-bottom: 1px solid #979393;
	position: absolute;
	width: 100%;
	height: 50%;
	transform: rotate(-12deg);
	font-size: 0.8em;
}
.search-suggestion  .price-new {
	display: block;
	color: #ff2e2e;
}
.search-suggestion  .price-base {
	color: black;
}
.search-suggestion .more {
	line-height: 30px;
	text-align: center;
	font-size: 1.1em;
	color: white;
	opacity: 0.7;
}
.search-suggestion .more:hover {
	opacity: 1;
}
.search-suggestion .out-stock .value {
	color: #ff2e2e;
	color: white;
	background-color: #ff2e2e;
	width: fit-content;  
	padding: 1px 5px;
	border-radius: 4px;
	font-size: 0.8em;
	font-weight: 700;
}
.search-suggestion .in-stock .value {
	color: #00dd00;
	color: white;
	background-color: #00dd00;
	width: fit-content;  
	padding: 1px 5px;
	border-radius: 4px;
	font-size: 0.8em;
	font-weight: 700;
}
','product'=>array('status'=>1,'title'=>array(),'titles'=>array('en-gb'=>'Products','ru-ru'=>'Товары','uk-ua'=>'Товари'),'order'=>'name','order_dir'=>'asc','logic'=>'and','fix_keyboard_layout'=>1,'fix_transliteration'=>0,'limit'=>7,'more'=>1,'search_by'=>array('name'=>1,'tags'=>0,'description'=>0),'fields'=>array('image'=>array('sort'=>0,'show'=>1,'width'=>60,'height'=>60,'column'=>'left','location'=>'newline','css'=>''),'name'=>array('sort'=>1,'show'=>1,'column'=>'center','location'=>'newline','css'=>'font-weight: bold;
text-decoration: none;
margin-bottom: 3px;'),'price'=>array('sort'=>2,'show'=>1,'show_field_name'=>0,'column'=>'right','location'=>'newline','css'=>'font-size: 1.2em;
font-weight: 700;
letter-spacing: 1px;
white-space: nowrap;'),'manufacturer'=>array('sort'=>3,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'model'=>array('sort'=>4,'show'=>1,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'sku'=>array('sort'=>5,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'upc'=>array('sort'=>6,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'ean'=>array('sort'=>7,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'jan'=>array('sort'=>8,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'isbn'=>array('sort'=>9,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'mpn'=>array('sort'=>10,'show_field_name'=>1,'column'=>'center','location'=>'inline'),'stock'=>array('sort'=>11,'show_field_name'=>1,'column'=>'center','location'=>'newline'),'quantity'=>array('sort'=>11,'show_field_name'=>1,'column'=>'center','location'=>'newline'),'description'=>array('sort'=>12,'cut'=>50,'column'=>'center','location'=>'newline','css'=>''),'attributes'=>array('sort'=>13,'cut'=>50,'separator'=>' / ','show_field_name'=>1,'column'=>'center','location'=>'newline',),'rating'=>array('sort'=>14,'show'=>1,'show_field_name'=>1,'column'=>'center','location'=>'newline','show_empty'=>0,),'stock'=>array('sort'=>15,'show'=>1,'show_field_name'=>0,'column'=>'center','location'=>'newline',),'cart'=>array('sort'=>16,'show_field_name'=>0,'column'=>'center','location'=>'newline','code'=>'<button type="button" onclick="ss_cart_add(\'product_id\', \'minimum\');" class="btn btn-primary btn-sm"><i class="fa fa-shopping-cart"></i> <span class="hidden-xs hidden-sm hidden-md">button_cart</span></button>'))),'category_filter'=>array('status'=>1,'title'=>array(),'titles'=>array('en-gb'=>'Category filter','ru-ru'=>'Фильтр категорий','uk-ua'=>'Фільтр категорій'),'order'=>'count','order_dir'=>'desc','limit'=>6,'count'=>1,'inline'=>1,'inline_tooltip'=>1,'fields'=>array('image'=>array('sort'=>0,'show'=>1,'width'=>60,'height'=>60,'column'=>'left','location'=>'newline','css'=>''),'name'=>array('sort'=>1,'show'=>0,'location'=>'newline','css'=>'font-weight: bold;
text-decoration: none;'),'description'=>array('sort'=>2,'cut'=>50,'location'=>'newline',),),),'category'=>array('status'=>1,'title'=>array(),'titles'=>array('en-gb'=>'Categories','ru-ru'=>'Категории','uk-ua'=>'Категорії'),'order'=>'relevance','order_dir'=>'asc','logic'=>'and','limit'=>3,'fix_keyboard_layout'=>1,'fix_transliteration'=>0,'inline'=>1,'inline_tooltip'=>1,'search_by'=>array('name'=>array('status'=>1,'weight'=>20,),'description'=>array('status'=>0,'weight'=>10,),),'fields'=>array('image'=>array('sort'=>0,'show'=>1,'width'=>60,'height'=>60,'column'=>'left','location'=>'newline','css'=>''),'name'=>array('sort'=>1,'show'=>0,'location'=>'newline','css'=>'font-weight: bold;
text-decoration: none;'),'description'=>array('sort'=>2,'cut'=>50,'column'=>'center','location'=>'newline',),),),'manufacturer'=>array('status'=>1,'title'=>array(),'titles'=>array('en-gb'=>'Manufacturers','ru-ru'=>'Производители','uk-ua'=>'Виробники'),'order'=>'name','order_dir'=>'asc','logic'=>'or','limit'=>3,'fix_keyboard_layout'=>1,'fix_transliteration'=>0,'inline'=>1,'inline_tooltip'=>1,'search_by'=>array('name'=>1,),'fields'=>array('image'=>array('sort'=>0,'show'=>1,'width'=>60,'height'=>60,'column'=>'left','location'=>'newline','css'=>''),'name'=>array('sort'=>1,'show'=>0,'location'=>'newline','css'=>'font-weight: bold;
text-decoration: none;'),),),'information'=>array('status'=>1,'title'=>array(),'titles'=>array('en-gb'=>'Information','ru-ru'=>'Информация','uk-ua'=>'Інформація'),'order'=>'title','order_dir'=>'asc','logic'=>'and','fix_keyboard_layout'=>1,'fix_transliteration'=>0,'limit'=>3,'inline'=>1,'inline_tooltip'=>1,'search_by'=>array('title'=>array('status'=>1,'weight'=>20,),'description'=>array('status'=>0,'weight'=>10,),),'fields'=>array('title'=>array('sort'=>1,'show'=>1,'column'=>'center','location'=>'newline','css'=>'font-weight: bold;
text-decoration: none;'),'description'=>array('sort'=>2,'cut'=>50,'column'=>'center','location'=>'newline','css'=>''),),),);}}
//author sv2109 (sv2109@gmail.com) license for 1 product copy granted for Nawiteh (andrew.pv.mm@gmail.com nawiteh.com.ua,www.nawiteh.com.ua,stage.nawiteh.com.ua)
