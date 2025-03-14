<?php

 /*
 * https://translit.net/ge/
  * https://www.translitteration.com/transliteration/en/georgian/national/
  * https://translit.cc/ge/
  * 
  * + fixed by https://opencartforum.com/profile/684506-janri/
  * 
 */

function sug_translit_kat_title() {
	return 'ქართული ენა ტრანსლიტში';
}

function sug_translit_kat($string) {
	$replace = array(
		"ა"=>"a",
		"ბ"=>"b",
		"გ"=>"g",
		"დ"=>"d",
		"ე"=>"e",
		"ვ"=>"v",
		"ზ"=>"z",
		"თ"=>"th",
		"ი"=>"i",
		"კ"=>"k",
		"ლ"=>"l",
		"მ"=>"m",
		"ნ"=>"n",
		"ო"=>"o",
		"პ"=>"p",
		"ჟ"=>"zh",
		"რ"=>"r",
		"ს"=>"s",
		"ტ"=>"t",
		"უ"=>"u",
		"ფ"=>"p",
		"ქ"=>"q",
		"ღ"=>"gh",
		"ყ"=>"kq",
		"შ"=>"sh",
		"ჩ"=>"ch",
		"ც"=>"c",
		"ძ"=>"dz",
		"წ"=>"ts",
		"ჭ"=>"ch",
		"ხ"=>"kh",
		"ჯ"=>"j",
		"ჰ"=>"h",
		"«"=>"",
		"»"=>"",
		"„"=>"",
		"“"=>"",
		"“"=>"",
		"”"=>"",
		"\•"=>""
	);


	return iconv("UTF-8", "UTF-8//IGNORE", strtr($string, $replace));
}