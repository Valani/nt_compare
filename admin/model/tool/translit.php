<?php

/**
 * @category  OpenCart
 * @package   SEO URL Generator PRO
 * @copyright © Serge Tkach, 2018–2024, https://sergetkach.com/link/287
 */

class ModelToolTranslit extends Model {

  public function getFunctionsList() {
    return array(
      'cyrillicToLatinaFromRus' => 'Русский в латиницу',
      'cyrillicToLatinaFromUkr' => 'Українська латиницею ',
      'cyrillicToLatinaFromBel' => 'Беларуская у лацінку',
      'cyrillicToLatinaFromKaz' => 'қазақша ішінде латын',
      // ... you can add your's translit function name here & translit function in this file

    );
  }


	public function cyrillicToLatinaFromRus($string) {
		// https://habrahabr.ru/post/187778/ взят за основу, но переделан
    // Поправку на правила транслтерации Яндекса выполнил fildenis https://opencartforum.com/profile/673970-fildenis/
    // Сайт для проверки соответствия правилам Яндекса http://translit-online.ru/yandex.html

    $arStrES = array("цх","сх","ех","хх");
    $arStrRS = array("ц$","с$","е$","х$");

    $replace = array(
      "А"=>"A","а"=>"a","Б"=>"B","б"=>"b","В"=>"V","в"=>"v","Г"=>"G","г"=>"g","Д"=>"D","д"=>"d",
      "Е"=>"E","е"=>"e","Ё"=>"Yo","ё"=>"yo","Ж"=>"Zh","ж"=>"zh","З"=>"Z","з"=>"z",
      "И"=>"I","и"=>"i",
      "Й"=>"J","й"=>"j","К"=>"K","к"=>"k","Л"=>"L","л"=>"l","М"=>"M","м"=>"m","Н"=>"N","н"=>"n",
			"О"=>"O","о"=>"o", "П"=>"P","п"=>"p","Р"=>"R","р"=>"r","С"=>"S","с"=>"s","Т"=>"T","т"=>"t",
			"У"=>"U","у"=>"u","Ф"=>"F","ф"=>"f","Х"=>"H","х"=>"h","Ц"=>"C","ц"=>"c","Ч"=>"Ch","ч"=>"ch",
			"Ш"=>"Sh","ш"=>"sh","Щ"=>"Shch","щ"=>"shch","Ы"=>"Y","ы"=>"y",
			"Э"=>"Eh","э"=>"eh","Ю"=>"Yu","ю"=>"yu","Я"=>"Ya","я"=>"ya","ъ"=>"","ь"=>"","$"=>"kh",
      "«"=>"", "»"=>"", "„"=>"", "“"=>"", "“"=>"", "”"=>"", "\•"=>"",
    );

    $string = str_replace($arStrES, $arStrRS, $string);

    return iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
	}


	public function cyrillicToLatinaFromUkr($string) {

    $arStrES = array("цх","сх","ех","хх");
    $arStrRS = array("ц$","с$","е$","х$");

    $replace = array(
      "А"=>"A","а"=>"a","Б"=>"B","б"=>"b","В"=>"V","в"=>"v","Г"=>"G","г"=>"g","Д"=>"D","д"=>"d",
      "Е"=>"E","е"=>"e","Ё"=>"Yo","ё"=>"yo","Ж"=>"Zh","ж"=>"zh","З"=>"Z","з"=>"z",
      // ukr customized.begin
      "И"=>"Y","и"=>"y",
      "І"=>"I","і"=>"i",
      "Ї"=>"Yi","ї"=>"yi",
      "Є"=>"Ye","є"=>"ye",
			// ukr customized.end
      "Й"=>"J","й"=>"j","К"=>"K","к"=>"k","Л"=>"L","л"=>"l","М"=>"M","м"=>"m","Н"=>"N","н"=>"n",
			"О"=>"O","о"=>"o", "П"=>"P","п"=>"p","Р"=>"R","р"=>"r","С"=>"S","с"=>"s","Т"=>"T","т"=>"t",
			"У"=>"U","у"=>"u","Ф"=>"F","ф"=>"f","Х"=>"H","х"=>"h","Ц"=>"C","ц"=>"c","Ч"=>"Ch","ч"=>"ch",
			"Ш"=>"Sh","ш"=>"sh","Щ"=>"Shch","щ"=>"shch","Ы"=>"Y","ы"=>"y",
			"Э"=>"Eh","э"=>"eh","Ю"=>"Yu","ю"=>"yu","Я"=>"Ya","я"=>"ya","ъ"=>"","ь"=>"","$"=>"kh",
      "«"=>"", "»"=>"", "„"=>"", "“"=>"", "“"=>"", "”"=>"", "\•"=>"",
    );

    $string = str_replace($arStrES, $arStrRS, $string);

    return iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
	}


  public function cyrillicToLatinaFromBel($string) {
    // На правила русского языка наложены правила белорусского - https://ru.wikipedia.org/wiki/%D0%A2%D1%80%D0%B0%D0%BD%D1%81%D0%BB%D0%B8%D1%82%D0%B5%D1%80%D0%B0%D1%86%D0%B8%D1%8F_%D0%B1%D0%B5%D0%BB%D0%BE%D1%80%D1%83%D1%81%D1%81%D0%BA%D0%BE%D0%B3%D0%BE_%D0%B0%D0%BB%D1%84%D0%B0%D0%B2%D0%B8%D1%82%D0%B0_%D0%BB%D0%B0%D1%82%D0%B8%D0%BD%D0%B8%D1%86%D0%B5%D0%B9

    $arStrES = array("цх","сх","ех","хх");
    $arStrRS = array("ц$","с$","е$","х$");

    $replace = array(
      "А"=>"A","а"=>"a","Б"=>"B","б"=>"b","В"=>"V","в"=>"v","Г"=>"G","г"=>"g","Д"=>"D","д"=>"d",
      "Е"=>"Je","е"=>"je", // bel customized
      "Ё"=>"Jo","ё"=>"jo", // bel customized
      "Ж"=>"Zh","ж"=>"zh", // Под вопросом
      "З"=>"Z","з"=>"z",
      "І"=>"I","і"=>"i", // bel customized
      "Й"=>"J","й"=>"j","К"=>"K","к"=>"k","Л"=>"L","л"=>"l","М"=>"M","м"=>"m","Н"=>"N","н"=>"n",
			"О"=>"O","о"=>"o", "П"=>"P","п"=>"p","Р"=>"R","р"=>"r","С"=>"S","с"=>"s","Т"=>"T","т"=>"t",
			"У"=>"U","у"=>"u",
			"Ў"=>"U","ў"=>"u",  // bel customized
      "Ф"=>"F","ф"=>"f","Х"=>"H","х"=>"h","Ц"=>"C","ц"=>"c","Ч"=>"Ch","ч"=>"ch",
			"Ш"=>"Sh","ш"=>"sh","Щ"=>"Shch","щ"=>"shch","Ы"=>"Y","ы"=>"y",
			"Э"=>"Eh","э"=>"eh","Ю"=>"Yu","ю"=>"yu","Я"=>"Ja","я"=>"ja","ъ"=>"","ь"=>"","$"=>"kh",
      "«"=>"", "»"=>"", "„"=>"", "“"=>"", "“"=>"", "”"=>"", "\•"=>"",
    );

    $string = str_replace($arStrES, $arStrRS, $string);

    return iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
	}


  public function cyrillicToLatinaFromKaz($string) {
    // Казахский алфавит взят - https://www.zakon.kz/perevod_na_latinicu.html

    $arStrES = array("цх","сх","ех","хх");
    $arStrRS = array("ц$","с$","е$","х$");

    $replace = array(
      "А"=>"A","а"=>"a",
      "Ә"=>"Ae","ә"=>"ae", // kaz customized
      "Б"=>"B","б"=>"b","В"=>"V","в"=>"v","Г"=>"G","г"=>"g",
      "Ғ"=>"Gh","ғ"=>"gh", // kaz customized
      "Д"=>"D","д"=>"d",
      "Е"=>"E","е"=>"e",
      "Ё"=>"Yo","ё"=>"yo",
      "Ж"=>"Zh","ж"=>"zh",
      "З"=>"Z","з"=>"z",
      "И"=>"I","и"=>"i", "Й"=>"J","й"=>"j",
      "К"=>"K","к"=>"k",
      "Қ"=>"Q","қ"=>"q", // kaz customized
      "Л"=>"L","л"=>"l","М"=>"M","м"=>"m",
      "Н"=>"N","н"=>"n",
      "Ң"=>"N","ң"=>"n", // kaz customized
			"О"=>"O","о"=>"o",
      "Ө"=>"Oe","ө"=>"oe", // kaz customized
      "П"=>"P","п"=>"p","Р"=>"R","р"=>"r","С"=>"S","с"=>"s","Т"=>"T","т"=>"t",
			"У"=>"U","у"=>"u",
      "Ұ"=>"U","ұ"=>"u", // kaz customized
      "Ү"=>"U","ү"=>"u", // kaz customized
      "Ф"=>"F","ф"=>"f",
      "Х"=>"H","х"=>"h",
      "Һ"=>"H","һ"=>"h", // kaz customized
      "Ц"=>"C","ц"=>"c","Ч"=>"Ch","ч"=>"ch",
			"Ш"=>"Sh","ш"=>"sh","Щ"=>"Shch","щ"=>"shch",
      "ъ"=>"", // kaz customized
      "Ы"=>"Y","ы"=>"y",
      "І"=>"I","і"=>"i", // kaz customized
			"Э"=>"Eh","э"=>"eh","Ю"=>"Уu","ю"=>"yu","Я"=>"Ja","я"=>"ja","ъ"=>"","ь"=>"","$"=>"kh",
      "«"=>"", "»"=>"", "„"=>"", "“"=>"", "“"=>"", "”"=>"", "\•"=>"",
    );

    $string = str_replace($arStrES, $arStrRS, $string);

    return iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
	}


  public function clearWasteChars($str){
    $str = trim($str);
    $str = preg_replace('|_|','-',$str);
    $str = preg_replace('| |','-',$str); // Заменить одинарные пробелы на тире
    $str = preg_replace('/\s+/', '-', $str); // Убрать двойные пробелы
    $str = preg_replace('|-+|','-',$str); // Заменить поторяющиеся тире на единичное
    // $str = preg_replace('![^\w\d\s\-]*!u','',$str); // u - чтобы в том числе № и другие не ASCII-символы
		$str = preg_replace('/[^a-zA-Z0-9\-]/', '', $str); // Removes special chars
    $str = preg_replace( array('!^-!', '!-$!'),array('', ''), $str); // Убрать тире в начале и в конце строки

    return $str;
  }


}
