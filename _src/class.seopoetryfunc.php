<?php

// --------------------------------------------------------------------------
// Всякие вспомогательные функции (ок ок, статичные методы)
// --------------------------------------------------------------------------

class seoPoetryFunc
{
	// Получение числа для посева mt_srand() из строки
	public static function seedFromStr($t) {
		trigger_error(__METHOD__ . ' nothing happen');
	}

	// Приведение всех параметров функции к векторному виду
	public static function flatArray() {
		$res = array();

		foreach(func_get_args() as $arr) {
			if(!is_array($arr)) {
				$arr = (array) $arr;
			}

			$arr = array_values($arr);
			$n = count($arr);

			for($i=0; $i<$n; $i++) {
				if(is_array($arr[$i])) {
					$r = self::flatArray($arr[$i]);
					$k = count($r);
					for($j=0; $j<$k; $j++) {
						$res[] = $r[$j];
					}
				}
				else {
					$res[] = $arr[$i];
				}
			}
		}
		return $res;
	}

	// Нормализация терма
	public static function strTerm($t) {
		return trim(mb_strtolower($t, 'UTF-8'));
	}

	// Разбиение текста и зачистка пустых элементов
	public static function strExplode($t, $delim, $f_filter = 0) {
		$arr = array_map('trim', explode($delim, $t));
		if($f_filter) {
			$arr = array_filter($arr, 'strlen');
		}
		return $arr;
	}

	// Вырезание кусков строки
	public static function strSub($str, $start, $len) {
		return mb_substr($str, $start, $len, 'UTF-8');
	}

	// Расстояние на котором можно использовать дубли слова
	public static function textCleanDoubles($texts, $len, $logger = null) {
		// TODO func
	}

	// Сборка случайного списка из взвешенных элементов
	public static function weightedRand($list, $weight, $from, $to) {
		$num = rand($from, $to);
		$max = $weight ? max($weight) : 1;
		$arr = array();
		$pm  = getrandmax();

		foreach($list as $k=>$v) {
			$vw = isset($weight[$k]) ? $weight[$k] : 1;
			$p = $vw / $max;
			$s = rand() / $pm;
			$arr[$v] = 1000 * $p * $s;
		} 
		arsort($arr);

		return array_slice(array_keys($arr), 0, $num);
	}

	// Наведение красоты в векторе абзацев
	public static function textPretiffy($texts, $wrap_tag = '', $logger = null) {
		foreach($texts as $k=>$v) {
			$v = trim($v);
			if(!$v) {
				unset($texts[$k]);
				continue;
			}
			//$v = preg_replace('/([^0-9]{2})\s*([\.,!?])\s*([^0-9]{2})/isu', '\\1\\2 \\3', $v);
			$a = mb_strtoupper(mb_substr($v, 0, 1, 'UTF-8'));
			$b = mb_substr($v, 1, 1000000, 'UTF-8');
			$v = $a . $b;
			$v = preg_replace('/\s+/isu', ' ', $v);

			// Подавление дублей слов
			$arr = explode(' ', $v);
			$prev = null;
			foreach($arr as $a=>$b) {
				if($prev === null) {
					$prev = $b;
					continue;
				}
				if($prev == $b) {
					unset($arr[$a]);
					continue;
				}
				$prev = $b;
			}
			$v = implode(' ', $arr);
			$v = preg_replace('/\s+\./isu', '.', $v);
			$texts[$k] = $v;
		}

		if($wrap_tag) {
			foreach($texts as $k=>$v) {
				$texts[$k] = '<' . $wrap_tag . '>' . $v . '</' . $wrap_tag . '>';
			}
		}

		return implode("\n", $texts);
	}
}

?>