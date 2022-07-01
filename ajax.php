<?php

	require("global.php");
	
	if($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['t'])){
		$funcName = $_GET['t'];		
		if(function_exists("ajax_GET_$funcName")) print call_user_func("ajax_GET_$funcName");		
	}
	
	function ajax_GET_getWorkCheckboxes(){
		global $DB;
		$eventId = (int)$_GET['id'];
		if($eventId > 0){
			$return = '';		
			$workCheckboxes = $DB->query("SELECT `recycling_cartridges_worknames`.*, mark FROM `recycling_cartridges_worknames` LEFT JOIN (SELECT eventId AS mark, workId FROM `recycling_cartridges_works` WHERE `eventId`='$eventId') AS `recycling_cartridges_works` ON `recycling_cartridges_worknames`.`id`=`recycling_cartridges_works`.`workId` ORDER BY `recycling_cartridges_worknames`.`id`");
			while($workCheckbox = $DB->fetch_assoc($workCheckboxes)) $return .= '<input type="checkbox" onchange="sendWorks('.$eventId.');" id="workIdArray_'.$eventId.'" value="'.$workCheckbox['id'].'"'.(($workCheckbox['mark'] > 0) ? ' checked' : '').'>'.$workCheckbox['workName'].'</br>';
			return $return;
		}
	}
	
	function ajax_GET_getCheckChangeElement(){
		return show_check_edit_element((int)$_GET['id']);	
	}
	
	function ajax_GET_getPrinterInfoElement(){
		return show_printer_edit_element((int)$_GET['id']);	
	}

	function ajax_GET_getAdminEditElement(){
		return show_admin_edit_element((int)$_GET['id']);	
	}
	
	function ajax_GET_getCompanyEditElement(){
		return show_company_edit_element((int)$_GET['id']);	
	}
	
	function ajax_GET_getOfficeEditElement(){
		return show_office_edit_element((int)$_GET['id']);	
	}
	
	

?>