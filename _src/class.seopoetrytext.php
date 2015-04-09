<?php

// --------------------------------------------------------------------------
// Класс генерации текстов для сайта
// --------------------------------------------------------------------------

class seoPoetryText
{
	const DELIM = '[seo-poetry-text-breaker]'; // разделитель абзацев

	protected $list;  // Список рабочих предложений
	protected $prep;  // Список отработанных предложений
	protected $_main; // Базовый класс

	// Конструктор
	public function __construct($main) {
		$this->_main = $main;
		$this->Clean();
	}

	// Во имя добра
	public function Chain() {
		return $this;
	}

	// Сброс накопленного текста
	public function Clean() {
		$this->list = array();
		$this->prep = array();
		return $this;
	}

	// Добавление шаблона в текст
	public function Append($t) {
		$arr = seoPoetryFunc::flatArray(func_get_args());
		foreach($arr as $k=>$v) {
			$this->list[] = $v;	
		}
		return $this;
	}

	// Добавление одного из текстов в набор
	public function AppendOneOf($t) {
		$arr = seoPoetryFunc::flatArray(func_get_args());
		$this->_main->SeedRand();
		return $this->Append($arr[array_rand($arr)]);
	}

	// Перемешать и добавить список текстов
	public function AppendMix($t) {
		$arr = seoPoetryFunc::flatArray(func_get_args());
		$this->_main->SeedRand();
		shuffle($arr);
		return $this->Append($arr);
	}

	// Добавить разделитель абзацев
	public function AppendBreaker() {
		$this->list[] = self::DELIM;
		return $this;
	}

	// Вывод текущего списка строк
	public function getList() {
		return $this->list;
	}

	// Слияние предложений и разворачивание шаблонов
	public function showTexts() {
		$t = $this->makeText(implode(' ', $this->list));
		return seoPoetryFunc::strExplode($t, self::DELIM, 'filter');
	}

	// Отработка текстов перед последней обработкой
	public function makeText($str = '') {
		if(!is_scalar($str)) {
			$this->_main->Log('Not scalar found at Poetry_Text:makeMix()');
			$str = (string) $str;
		}
		// Разворачивание шаблонов
		$this->_main->SeedRand();
		while(preg_match('/{([^{}]*)}/', $str, $m)) {
			$t = substr($m[0], 1, -1);
			$s = explode('|', $t);
			$p = '';

			if($t) {
				$p = $s[array_rand($s)];
			}

			$str = str_replace($m[0], $p, $str);
		}
		return preg_replace('/\s\s+/', ' ', trim($str));
	}
}

?>