<?php
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);

	date_default_timezone_set('Europe/Moscow');
	session_start();

	$intlFormatter = new IntlDateFormatter('ru_RU', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
	$intlFormatter->setPattern('d MMMM YYYYг. HH:mm');
	
	$_SESSION['journalEvents'] = isset($_SESSION['journalEvents']) ? $_SESSION['journalEvents'] : 100;
	$_SESSION['superAdm'] = isset($_SESSION['superAdm']) ? $_SESSION['superAdm'] : 0;
	
	$projectName = 'Cartridges-1.1';
	require('assets/'.$projectName."/mysqli.class.php");
	$DB = new sql_mysql;
	require("connect_settings.php");
//	$DB->connect('127.0.0.1','user','password','database',0);
//	$DB->query("USE `cartriges`");
	$DB->query("SET NAMES 'utf8mb4'");
	
	require('assets/'.$projectName."/functions.php");
	require('assets/'.$projectName."/functions.admdb.php");
	
	$portalCartridgeColors = array(
		0 => array('eng' => '== select ==', 'rus' => '== выберите =='),
		1 => array('eng' => 'Black', 'rus' => 'Чёрный', 'class' => 'cartridgeColorBlack'),
		2 => array('eng' => 'Cyan', 'rus' => 'Голубой', 'class' => 'cartridgeColorCyan'),
		3 => array('eng' => 'Magenta', 'rus' => 'Пурпурный', 'class' => 'cartridgeColorMagenta'),
		4 => array('eng' => 'Yellow', 'rus' => 'Жёлтый', 'class' => 'cartridgeColorYellow')	
	);
	
	$portalPrinterTypes = array(0 => '== Выберите ==', 1 => 'МФУ', 2 => 'Принтер', 3 => 'Копир');
	$portalYesNo = array(0 => 'Нет', 1 => 'Да'); 
	
	function passHash($password, $salt){
		return md5(md5(md5($password).$salt).$salt);
	}
	
	function show_select($selected, $array, $selectname, $class = ''){
		$return = '<select class="'.$class.'" name="'.$selectname.'" id="'.$selectname.'">';
		foreach($array as $key => $value) $return .= '<option value="'.$key.'"'.(($selected == $key) ? ' selected="selected"' : '').'>'.strip_tags($value).'</option>';
		return $return."</select>";
	}

?>