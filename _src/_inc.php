<?php

// --------------------------------------------------------------------------
// Подключение всех файлов библиотеки
// --------------------------------------------------------------------------

// Проверка порядка подключения
if(!defined('POETRY_DIR_PROJECT')) {
	die('POETRY_DIR_PROJECT not defined');
}

// Текущее размещение
define('POETRY_DIR_SRC', dirname(__FILE__) . '/');

// Подключение библиотеки
include_once POETRY_DIR_SRC . 'class.seopoetryfunc.php';
include_once POETRY_DIR_SRC . 'class.seopoetryterm.php';
include_once POETRY_DIR_SRC . 'class.seopoetrytext.php';
include_once POETRY_DIR_SRC . 'class.seopoetry.php';
include_once POETRY_DIR_SRC . 'func.poetry.php';

// Подключение функций-текстов
if(is_dir(POETRY_DIR_PROJECT . 'src')) {
	$arr = scandir(POETRY_DIR_PROJECT . 'src');
	foreach($arr as $k=>$v) {
		if($v[0] == '.' || $v[0] == '~' || $v[0] == '_') {
			continue;
		}
		include_once POETRY_DIR_PROJECT . 'src/' . $v;
	}
}

?>