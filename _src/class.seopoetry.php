<?php

// --------------------------------------------------------------------------
// Мастер-класс
// --------------------------------------------------------------------------

class seoPoetry
{
	protected $_param; // Набор настроек
	protected $_seed;  // Число для инициализатора псевдослучайности
	protected $_log;   // Буффер для логов

	protected $__term; // Ссылка на объект с термами
	protected $__text; // Ссылка на объект с кешем

	// Конструктор
	public function __construct(
		$dir_lib,           /* Каталог со списками библитек термов */
		$dir_cache,         /* Каталог для кешей списков */
		$dir_log,           /* Каталог для логов */
		$seed_str,          /* Строки для инициализации случайности */
		$debug = 0,         /* Флаг отладки */
		$charset = 'UTF-8', /* Итоговая кодировка для вывода (внутри UTF-8) */
		$keylen  = 1,       /* Длина префикса для кешей */
		$void = ''
	) {
		$this->_param = array();
		$this->_log   = array();
		$this->_seed  = null;
		$this->param('dir_lib',   $dir_lib);
		$this->param('dir_cache', $dir_cache);
		$this->param('dir_log',   $dir_log);
		$this->param('seed_str',  $seed_str);
		$this->param('debug',     $debug);
		$this->param('charset',   $charset);
		$this->param('keylen',    $keylen);
	}

	// Посев псевдослучайности
	public function SeedRand($append = '') {
		$str = $this->param('seed_str') . ($append ? '---' . $append : '');
		$s = 0;
		for($i=0; $i<strlen($str); $i++) {
			$s+= ord($str[$i])*($i*$i*$i+1)/6;
		}
		srand((int)$s);
		return $this;
	}

	// Восстановление вселенной
	public function GoodBye() {
		srand(intval(microtime(true) * 1000));
		$this->Log();
		return $this;
	}

	// Объект для текстов
	public function NewText() {
		if($this->__text === null) {
			$this->__text = new seoPoetryText($this);
		}
		return $this->__text;
	}

	// Объект для термов
	public function NewTerm() {
		if($this->__term === null) {
			$this->__term = new seoPoetryTerm($this);
		}
		return $this->__term;
	}

	// Сборщик логов
	public function Log() {
		// Отлуп на логи
		if(!$this->param('debug')) {
			return '';
		}

		// Работа с логами
		if(!func_num_args()) {
			return $this->_log;
		}
		$arr = func_get_args();
		$str = implode("\t", seoPoetryFunc::flatArray(strftime("%Y-%m-%d %H:%M:%S"), $arr));

		// Сохранение в буффер
		$this->_log[] = $str;

		// Сохранение в файл
		$dir = $this->getParamDir('dir_log');
		if($dir) {
			file_put_contents(
				$dir . strftime('%Y-%m-%d') . '.txt',
				$str
				. (empty($_SERVER['REQUEST_URI']) ? '' : ' at url=(' . $_SERVER['REQUEST_URI'].')')
				. "\n",
				LOCK_EX | FILE_APPEND
			);
		}

		return $this;
	}

	// Управление параметрами
	public function param($name, $value = null, $def = null) {
		$name = (string) $name;
		if($value !== null) {
			$this->_param[$name] = $value;
		}
		if(!isset($this->_param[$name])) {
			$this->Log('Param not defined (' . $name . ')');
			return $def;
		}
		return $this->_param[$name];
	}

	// Проверка определения и существования требуемого каталога
	public function getParamDir($name) {
		$dir = $this->param($name, null, '');
		if(!$dir) { 
			$this->Log(__METHOD__, sprintf('Dir param %s not set', $dir));
		} elseif(!is_dir($dir)) {
			$this->Log(__METHOD__, sprintf('Dir for %s not found (%s)', $name, $dir));
			$dir = '';
		}

		return $dir;
	}
}

?>