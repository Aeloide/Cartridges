<?php
	date_default_timezone_set('Europe/Moscow');
	session_start();
	require("mysqli.class.php");
	$DB = new sql_mysql;	
	require("connect_settings.php");
//	$DB->connect('127.0.0.1','user','password','database',0);
	$DB->query("USE `cartriges`");
	$DB->query("SET NAMES 'utf8mb4'");

	function show_select($selected, $array, $selectname){
		//формирует select в html из массива
		$return = '<select name="'.$selectname.'" id="'.$selectname.'">';
		foreach($array as $key => $value) $return .= '<option value="'.$key.'"'.(($selected == $key) ? ' selected="selected"' : '').'>'.strip_tags($value).'</option>';
		return $return."</select>\n";
	}


?>