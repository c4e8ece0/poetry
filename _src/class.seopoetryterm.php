<?php

// --------------------------------------------------------------------------
// Класс генерации библиотек для сайта
// --------------------------------------------------------------------------

class seoPoetryTerm
{
	protected $dict;     // Словарь терминов
	protected $counter;  // Счётчик для getNext();
	protected $_main;    // Базовый класс
	protected $load_ok;  // Флаг удачной загрузки
	protected $build_ok; // Флаг удачной сборки
	protected $_pop;     // Популярности термов для пристыковки к спискам и термам при сборке

	// Конструктор
	public function __construct($main) {
		$this->_main = $main;
		$this->dict = array();
		$thid->load_ok  = 1; // Сбрасывается если что-то пошло не так
		$thid->build_ok = 0; // Устанавливается когда всё сохранено

	}

	// Во имя добра
	public function Chain() {
		return $this;
	}

	// Выборка вариантов для терма, его представления и индекса элемента
	// $_index === null - возвращает весь вектор значений или пустой array()
	// $if_null - результат, если терм не найден
	public function Get($_term, $_view, $_index = 0, $if_null = '') {
		$term  = seoPoetryFunc::strTerm($_term);
		$view  = seoPoetryFunc::strTerm($_view);
		$index = (int) $_index;

		// Попытка подгрузить терм
		$this->_load($term, $view);

		// Отлуп с дефолтом
		if(empty($this->dict[$term][$view])) {
			if(!$if_null) {
				$this->_main->Log(__METHOD__, sprintf('Term not found (%s.%s)', $term, $view));
				if($_index === null) {
					$if_null = array();
				}
			}
			return $if_null;
		}

		// Поиск по индексу
		if($_index !== null) {
			$i = (int) $_index;
			$n = count($this->dict[$term][$view]);
			return $this->dict[$term][$view][$i%$n];
		}
		// Возврат полного списка
		else {
			return $this->dict[$term][$view];
		}

		$this->_main->Log(__METHOD__, 'Terrible thing happens');
	}

	// Добавление к списку ручных термов
	public function Append($_term, $_view, $value) {
		$term  = seoPoetryFunc::strTerm($_term);
		$view  = seoPoetryFunc::strTerm($_view);
		$value = trim($value);
		if($term && $view && $value) {
			$this->dict[$term]['self'][] = (string) $_term;
			$this->dict[$term][$view][] = (string) $value;
		} else {
			$this->_main->Log(__METHOD__, sprintf('Bad cortage (%s|%s|%s)', $_term, $_view, $_value));
		}
		return $this;
	}

	// Создание архива для быстрого восстановления
	public function Build() {
		$this->_main->Log(__METHOD__, 'Build started');
		$dir_lib = $this->_main->getParamDir('dir_lib');
		if(!$dir_lib) {
			return $this;
		}

		// Сборка данных по файлам термов
		$arr = scandir($dir_lib);
		asort($arr);
		foreach($arr as $k=>$filename) {
			if(substr($filename, 0, 1) == '~' || is_dir($dir_lib.$filename)) {
				continue;
			}
			$ext = pathinfo($dir_lib . $filename, PATHINFO_EXTENSION);
			$con = file_get_contents($dir_lib . $filename);
			switch ($ext) {
				case 'tab':
					$this->_append_tab($con, $filename);
					break;
				case 'alt':
					$this->_append_alt($con);
					break;
				case 'pop':
					$this->_append_pop($con);
					break;
				case 'list':
					$this->_append_list($con);
					break;
				case 'flag':
					$this->_append_flag($con, $filename);
					break;
				case 'poetry':
					// internal use
					break;
				default:
					$this->_main->Log(__METHOD__, sprintf('bad extension (%s)', $ext));
					break;
			}
		}

		// Подготовка итоговой таблицы
		foreach ($this->dict as $k=>$v) {
			foreach($v as $a=>$b) {
				$this->dict[$k][$a] = array_unique(array_filter(seoPoetryFunc::flatArray($b), 'strlen'));
			}
		}

		// Расширение описаний весами/популярностями
		foreach($this->dict as $term=>$views) {
			$this->dict[$term]['pop'] = empty($this->_pop[$term]) ? 1 : $this->_pop[$term];
			if(empty($views['list'])) {
				continue;
			}
			$pre = array();
			foreach($views['list'] as $index=>$val) {
				$t = seoPoetryFunc::strTerm($val);
				$pre[$index] = empty($this->_pop[$t]) ? 1 : $this->_pop[$t];
			}
			if($pre) {
				$this->dict[$term]['list_pop'] = $pre;
			}
		}

		$this->_save()->_clean();
		$this->_main->Log(__METHOD__, 'Build ended');
		return $this;
	}

	// Сохранение кеша текущего словаря
	protected function _save() {
		// Назначен каталог?
		$dir_cache = $this->_main->getParamDir('dir_cache');
		if(!$dir_cache) {
			return $this;
		}

		// Сохранение кеша
		$me  = md5(serialize($this->dict) . '-' . $this->_main->param('keylen'));
		$dir = $dir_cache . $me . '/';

		// Проверка готовности кеша
		if(file_exists($dir)) {
			$this->_main->Log(__METHOD__, sprintf('Cache-dir already exists (%s)', $dir));
			$this->build_ok = 1;
			return $this;
		}

		// Создание каталога для хранения кешей
		if(!file_exists($dir) && !mkdir($dir, $this->_main->param('dir_mode', null, 0777) )) {
			$this->_main->Log(__METHOD__, sprintf('Can\'t create dir (%s)', $dir));
			return $this;
		}

		// Файл-метка, что каталог создан из Poetry
		if(!file_put_contents($dir.'own.poetry', time(), LOCK_EX)) {
			$this->_main->Log(__METHOD__, sprintf('Can\'t mark dir (%s) as own.poetry', $dir));
			return $this;
		}

		// Сборка словаря по 1-буквенным термам
		// Для лучшего кеширования препроцессорами (меньше файлов - больше профит)
		$dump = array();
		$this->_main->Log(__METHOD__, 'Start preparing dump'); 
		foreach(array_keys($this->dict) as $boo => $name) {
			$dump[seoPoetryFunc::strSub($name, 0, $this->_main->param('keylen'))][$name] = $this->dict[$name];
		}
		$this->_main->Log(__METHOD__, 'Stop preparing dump (ok)'); 

		foreach($dump as $pref => $arr) {
			$chk = md5($pref);
			if(!file_put_contents($dir . $chk, '<?php return ' . var_export($arr, 1) .'; ?>' , LOCK_EX)) {
				$this->_main->Log(__METHOD__, sprintf('Can\'t save cache for %s(%s) to (%s)', $name, $chk, $dir_cache . $me)); 
			}
		}

		// Последний корректный каталог
		if(!file_put_contents($dir_cache.'last.poetry', $me, LOCK_EX)) {
			$this->_main->Log(__METHOD__, sprintf('Can\'t save cache name (%slast.poetry)', $dir_cache));
			return $this;
		}

		$this->build_ok = 1;
		return $this;
	}

	// Очистка кешей
	protected function _clean() {
		if(!$this->build_ok) {
			$this->_main->Log(__METHOD__, 'Build was not ok');
			return $this;
		}

		$dir = $this->_main->getParamDir('dir_cache');
		if(!$dir) {
			$this->_main->Log('Dir can\'t be void');
			return $this;
		}

		$last = file_get_contents($dir . 'last.poetry');
		if(!$last) {
			$this->_main->Log('last.poetry not exists or void');
			return $this;
		}

		foreach(scandir($dir) as $i=>$name) {
			if(false
				|| $name == $last
				|| strlen($name) != 32
				|| preg_match('/[^0-9a-f]/', $name)
				|| !is_dir($dir.$last)
			) {
				continue;
			}

			if(!file_exists($dir.$name . '/own.poetry')) {
				continue;
			}

			foreach(scandir($dir.$name) as $k=>$v) {
				if($v == '.' || $v == '..') {
					continue;
				}
				$path = $dir.$name.'/'.$v;
				if(!unlink($path)) {
					$this->_main->Log(__METHOD__, sprintf('Can\'t delete (%s)', $path));
				}
			}

			if(!rmdir($dir.$name)) {
				file_put_contents($dir.$name . '/own.poetry', 'PoetryTerm can\'t delete this folder, will try on next Build()');
				$this->_main->Log(__METHOD__, sprintf('Can\'t delete (%s)', $dir.$name));
			}
		}

		return $this;
	}

	// Загрузка указанного элемента из кеша(term+view) или индекса (term)
	protected function _load($term, $view) {
		// А вдруг...
		if(isset($this->dict[$term][$view])) {
			return;
		}

		// Получение файла с нужными токенами
		$lett  = seoPoetryFunc::strSub($term, 0, $this->_main->param('keylen'));

		// Проверка каталога с индексами
		$dir_cache = $this->_main->getParamDir('dir_cache');
		if(!$dir_cache) {
			return;
		}

		// Восстановление текущего дампа
		if(!file_exists($dir_cache . 'last.poetry')) {
			$this->_main->Log(__METHOD__, 'Current cache-name not found (' . $dir_cache . 'last.poetry' . ')');
			return;
		}

		// Поиск нужного файла
		$cur = file_get_contents($dir_cache . 'last.poetry');
		$md5 = md5($lett);
		$path = $dir_cache . $cur . '/' . $md5;
		if(!file_exists($path)) {
			$this->_main->Log(__METHOD__, 'Cache-file not exists (' . $path . ')');
			return;
		}

		// Загрузка кеша
		$pre = include $path;
		foreach($pre as $l=>$arr) {
			foreach($arr as $k=>$v) {
				$this->dict[$l][$k] = $v;
			}
		}
 		return;
	}

	// Разбор строковой таблицы
	// TODO Вынести deny++ в Append() с флагом обхода проверки
	protected function _append_tab($t, $filename) {
		$list  = seoPoetryFunc::strExplode($t, "\n");
		$deny  = array('alt' => '', 'self' => '', 'list');
		$names = seoPoetryFunc::strExplode(array_shift($list), "\t");

		// Проверка имен на допустимость
		foreach($names as $k=>$v) {
			if(isset($deny[$v])) {
				$this->_main->Log(__METHOD__, sprintf('reserved name used as tab-name (%s) at %s', $v, $filename));
			} elseif(!$v) {
				$this->_main->Log(__METHOD__, sprintf('void tab-name found at %s', $filename));
			}
		}
		// Разбор таблиц по полям
		foreach($list as $str_index=>$str) {
			$arr = array_pad(seoPoetryFunc::strExplode($str, "\t"), count($names), "");
			foreach($arr as $i=>$ww) {
				if(!$ww) {
					continue;
				}
				foreach($names as $c=>$name) {
					if(!$arr[$c]) {
						continue;
					}
					$this->Append($ww, $name, $arr[$c]);
				}
				// добавление заодно и альтернативного представления для себя самого
				$this->_append_alt($ww."\t".$ww);
			}
		}

		return $this;
	}

	// Разбор файла с перечислениями альтернативного написания (<token>.alt=[])
	protected function _append_alt($t) {
		$list = seoPoetryFunc::strExplode($t, "\n", 'filter');
		foreach($list as $k=>$v) {
			$arr = seoPoetryFunc::strExplode($v, "\t", 'filter');
			foreach($arr as $k=>$v) {
				foreach ($arr as $a=>$b) {
					$this->Append($v, 'alt', $b);
				}
			}
		}
		return $this;
	}

	// Разбор файла со списковыми значениями (<token>."list"=[])
	protected function _append_list($t) {
		$list = seoPoetryFunc::strExplode($t, "\n", 'filter');
		foreach($list as $k=>$v) {
			$arr = seoPoetryFunc::strExplode($v, "\t", 'filter');
			$name = seoPoetryFunc::strTerm(array_shift($arr));
			foreach($arr as $k=>$v) {
				$this->Append($name, 'list', $v);
			}
		}
		return $this;
	}

	// Добавление какого-то поля как признака
	protected function _append_flag($t, $filename) {
		$arr = seoPoetryFunc::strExplode($t, "\n", 'filter');
		list($name) = seoPoetryFunc::strExplode($filename, ".");
		foreach($arr as $k=>$v) {
			$this->Append($v, 'flag', $v);
		}
		return $this;
	}

	// Разбор файла с популярностями для единого дампа и разноса данных по термам
	protected function _append_pop($t) {
		$list = seoPoetryFunc::strExplode($t, "\n", 'filter');
		foreach($list as $k=>$str) {
			$arr = seoPoetryFunc::strExplode($str, "\t");
			$pop = intval(array_pop($arr));
			if(!$pop || !$arr) {
				continue;
			}
			$n = count($arr);
			for($i=0; $i<$n; $i++) {
				for($j=0; $j<$n; $j++) {
					$t = implode(' ', array_slice($arr, $i, $j+1));
					@$this->_pop[seoPoetryFunc::strTerm($t)]+= $pop;
				}
			}
		}
		return $this;
	}
}

?>