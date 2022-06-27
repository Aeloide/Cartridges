<?php

	require("global.php");
	
	if($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['t'])){
		$funcName = $_GET['t'];		
		if(function_exists("ajax_GET_$funcName")){
			print call_user_func("ajax_GET_$funcName");
		}
	}
	
	function ajax_GET_getWorkCheckboxes(){
		global $DB;
		$eventId = (int)$_GET['id'];
		$return = '';		
		$workCheckboxes = $DB->query("
		SELECT `recycling_cartridges_worknames`.*, mark
FROM `recycling_cartridges_worknames`
LEFT JOIN (SELECT eventId AS mark, workId FROM `recycling_cartridges_works` WHERE `eventId`='$eventId') AS `recycling_cartridges_works` ON `recycling_cartridges_worknames`.`id`=`recycling_cartridges_works`.`workId`
ORDER BY `recycling_cartridges_worknames`.`id`");
		while($workCheckbox = $DB->fetch_assoc($workCheckboxes)){
			$return .= '<input type="checkbox" onchange="sendWorks('.$eventId.');" id="workIdArray_'.$eventId.'" value="'.$workCheckbox['id'].'"'.(($workCheckbox['mark'] > 0) ? ' checked' : '').'>'.$workCheckbox['workName'].'</br>';
		}
		return $return;		
	}
	
	function ajax_GET_getCheckChangeElement(){
		$checkId = (int)$_GET['id'];
		return show_check_edit_element($checkId);	
	}
	
	function ajax_GET_getPrinterInfoElement(){
		$printerId = (int)$_GET['id'];
		return show_printer_edit_element($printerId);	
	}	

?>