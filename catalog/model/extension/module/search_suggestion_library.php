<?php
class ModelExtensionModuleSearchSuggestionLibrary extends Model {

  public function changeKeyboardLayout($text) {

    $ru = array('й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ', '\\', 'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'э', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю', '.');
    $en = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', '[', ']', '\\', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', ';', '\'', 'z', 'x', 'c', 'v', 'b', 'n', 'm', ',', '.', '/');

    if ($this->strposArray($text, $ru) !== false) {
      return str_replace($ru, $en, $text);
    } elseif ($this->strposArray($text, $en) !== false) {
      return str_replace($en, $ru, $text);
    } else {
      return false;
    }
  }
	
	public function transliterate($text) {
		
		$converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        'і' => 'i',   'ї' => 'i',   'є' => 'e',    
    );
		
    return strtr($text, $converter);
	}
	
  private function strposArray($haystack, $needles) {
    if (is_array($needles)) {
      foreach ($needles as $str) {
        $pos = strpos($haystack, $str);
        if ($pos !== false) {
          return $pos;
        }
      }
    }
    return false;
  }

}