<?php

 /*
	* Правки згідно Постанові від 27 січня 2010 р. N 55 
  * "Про впорядкування транслітерації українського алфавіту латиницею" -- https://zakon.rada.gov.ua/laws/show/55-2010-%D0%BF#Text 
  * На невідповідність попередньої версії скритпа до постанови мою увагу зверну користувач https://opencartforum.com/profile/975967-maxbox/
 */

function sug_translit_ukr_title() {
	return 'Українська латиницею';
}

function sug_translit_ukr($string) {
	$arStrES = array(
		"зг",
	);
	$arStrRS = array(
		"з$",
	);
	
	$string = str_replace($arStrES, $arStrRS, $string);
	
	$replace = array(
		"А"	=> "A",
		"а"	=> "a",
		"Б"	=> "B",
		"б"	=> "b",
		"В"	=> "V",
		"в"	=> "v",
		"Г"	=> "H",
		"г"	=> "h",
		"Ґ"	=> "G",
		"ґ"	=> "g",
		"Д"	=> "D",
		"д"	=> "d",
		"Е"	=> "E",
		"е"	=> "e",
		"Є"	=> "Ie", // *
		"є"	=> "ie", // *
		"Ж"	=> "Zh",
		"ж"	=> "zh",
		"З"	=> "Z",
		"з"	=> "z",
		"И"	=> "Y",
		"и"	=> "y",
		"І"	=> "I",
		"і"	=> "i",
		"Ї"	=> "I", // *
		"ї"	=> "i", // *
		"Й"	=> "I", // *
		"й"	=> "i", // *
		"К"	=> "K",
		"к"	=> "k",
		"Л"	=> "L",
		"л"	=> "l",
		"М"	=> "M",
		"м"	=> "m",
		"Н"	=> "N",
		"н"	=> "n",
		"О"	=> "O",
		"о"	=> "o",
		"П"	=> "P",
		"п"	=> "p",
		"Р"	=> "R",
		"р"	=> "r",
		"С"	=> "S",
		"с"	=> "s",
		"Т"	=> "T",
		"т"	=> "t",
		"У"	=> "U",
		"у"	=> "u",
		"Ф"	=> "F",
		"ф"	=> "f",
		"Х"	=> "Kh",
		"х"	=> "kh",
		"Ц"	=> "Ts",
		"ц"	=> "ts",
		"Ч"	=> "Ch",
		"ч"	=> "ch",
		"Ш"	=> "Sh",
		"ш"	=> "sh",
		"Щ"	=> "Shch",
		"щ"	=> "shch",
		"ь"=>"", // не відтворюється
		"'"=>"", // не відтворюється
		"Ю"	=> "Iu", // *
		"ю"	=> "iu", // *
		"Я"	=> "Ia",
		"я"	=> "ia",
		
		"$"=> "gh",
	);
	
	$replace_in_start_position = [
		"Є"	=> "Ye",
		"є"	=> "ye",
		"Ї"	=> "Yi",
		"ї"	=> "yi",
		"Й"	=> "Y",
		"й"	=> "y",
		"Ю"	=> "Yu",
		"ю"	=> "yu",
		"Я"	=> "Ya",
		"я"	=> "ya",
	];
	
	$result = '';
	
	$words = array_filter(explode(' ', $string));

	foreach ($words as $word) {
		$first_char = mb_substr($word, 0, 1);
		
		if (isset($replace_in_start_position[$first_char])) {
			$translit_first_char = $replace_in_start_position[$first_char];
		} elseif (isset($replace[$first_char])) {
			$translit_first_char = $replace[$first_char];
		} else {
			$translit_first_char = $first_char;
		}

		$rest_word = mb_substr($word, 1);

		$result .= $translit_first_char . $rest_word;
		
		$result .= ' ';
	}

	return iconv("UTF-8", "UTF-8//IGNORE", strtr($result, $replace));
}