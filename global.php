<?php
	date_default_timezone_set('Europe/Moscow');
	session_start();
	$projectName = 'Cartridges-1.1';
	require('assets/'.$projectName."/mysqli.class.php");
	$DB = new sql_mysql;	
	require("connect_settings.php");
//	$DB->connect('127.0.0.1','user','password','database',0);
	$DB->query("USE `cartriges`");
	$DB->query("SET NAMES 'utf8mb4'");

	require('assets/'.$projectName."/functions.php");
	

?>