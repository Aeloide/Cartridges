<?php
	error_reporting(E_ALL);
	ini_set('display_errors', TRUE);
	ini_set('display_startup_errors', TRUE);

	date_default_timezone_set('Europe/Moscow');
	session_start();

	$intlFormatter = new IntlDateFormatter('ru_RU', IntlDateFormatter::SHORT, IntlDateFormatter::SHORT);
	$intlFormatter->setPattern('d MMMM YYYYг. HH:mm');
	
	$_SESSION['journalEvents'] = isset($_SESSION['journalEvents']) ? $_SESSION['journalEvents'] : 300;
	$_SESSION['superAdm'] = isset($_SESSION['superAdm']) ? $_SESSION['superAdm'] : 0;
	
	$projectName = 'Cartridges-1.1';
	require('assets/'.$projectName."/mysqli.class.php");
	$DB = new sql_mysql;
	require("connect_settings.php");
//	$DB->connect('127.0.0.1','user','password','database',0);
//	$DB->query("USE `cartriges`");
	$DB->query("SET NAMES 'utf8mb4'");
	
	if(isset($_SESSION['adminId'])){
		if(!isset($_SESSION['myOfficesList'])){
			$officesLists = $DB->query("SELECT `id`, `officeName` FROM `recycling_offices` WHERE id IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') ORDER BY `recycling_offices`.`id`");
			while($office = $DB->fetch_assoc($officesLists)){
				$_SESSION['myOfficesList'][$office['id']] = $office['officeName'];
			}		
		}
	}
	
	require('assets/'.$projectName."/functions.php");
	require('assets/'.$projectName."/functions.forms.php");	
	require('assets/'.$projectName."/functions.admdb.php");
	
	$allowCheckFileTypes = array ('image/jpeg', 'image/png', 'application/pdf');
	
	$portalCartridgeColors = array(
		0 => array('eng' => '== select ==', 'rus' => '== выберите =='),
		1 => array('eng' => 'Black', 'rus' => 'Чёрный', 'class' => 'cartridgeColorBlack'),
		2 => array('eng' => 'Cyan', 'rus' => 'Голубой', 'class' => 'cartridgeColorCyan'),
		3 => array('eng' => 'Magenta', 'rus' => 'Пурпурный', 'class' => 'cartridgeColorMagenta'),
		4 => array('eng' => 'Yellow', 'rus' => 'Жёлтый', 'class' => 'cartridgeColorYellow')	
	);
	
	$portalPrinterTypes = array(0 => '== Выберите ==', 1 => 'МФУ', 2 => 'Принтер', 3 => 'Копир');
	$portalYesNo = array(0 => '== Выберите ==', 1 => 'Да', 2 => 'Нет');
	$portalCompanyTypes = array(1 => 'На обслуживании', 2 => 'Сервисная компания (заправки, ремонты)');
	
	function passHash($password, $salt){
		return md5(md5(md5($password).$salt).$salt);
	}
	
	function show_select($selected, $array, $inputOptions = array(), $zeropoint = true){
		if(!is_array($array)) return;
		$optionsTxt = $returnArray = array();
		if($zeropoint == true) $array = array(0 => '-- выберите --') + $array;
		if(!is_array($inputOptions)){ $optionsArray['name'] = $inputOptions; }else{ $optionsArray = $inputOptions; }
		foreach($optionsArray as $option => $value){
			if($option == 'disabled'){ $optionsTxt[] = (($value == 1 || $value == true) ? "disabled" : ''); continue; }
			$optionsTxt[] = " $option=\"$value\"";
		}
		foreach($array as $key => $value) $returnArray[] = '<option value="'.$key.'"'.(($selected == $key) ? ' selected="selected"' : '').'>'.strip_tags($value).'</option>';
		return "<select".implode('', $optionsTxt).">".implode("", $returnArray)."</select>";
	}	
	
	function ca_check_path($num){
		$num = sprintf("%06d", (int)$num);
		return substr($num, 0, 3)."/".substr($num, 3, 3);
	}
	
	function ca_check_path_create($num){
		global $basedir;
		$num = sprintf("%06d", (int)$num);
		if(!is_dir(__DIR__.'/storage/'.substr($num, 0, 3))) mkdir(__DIR__.'/storage/'.substr($num, 0, 3));
		return substr($num, 0, 3)."/".substr($num, 3, 3);
	}

?>