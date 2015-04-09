<?php

// --------------------------------------------------------------------------
// Всякий сахар для Poetry
// --------------------------------------------------------------------------

// Приведение текста к нужной кодировке
if(!function_exists('poetry_return')) {
	function poetry_return($t, $tag_wrap = '') {
		$t = seoPoetryFunc::textPretiffy($t, $tag_wrap);
		_poetry()->GoodBye();
		// $t = iconv('FROM', 'TO//IGNORE', $t); // if needed
		return $t;
	}
}

// Типа синглтон
if(!function_exists('_poetry')) {
	function _poetry() {
		static $obj;
		if($obj == null) {
			$obj = new seoPoetry(
				POETRY_DIR_PROJECT . 'lib/',
				POETRY_DIR_PROJECT . 'cache/',
				POETRY_DIR_PROJECT . 'log/',
				POETRY_SEED_STR,
				POETRY_DEBUG_ENABLE,
				POETRY_CHARSET_EXTERN
			);
		}
		return $obj;
	}
}

?>