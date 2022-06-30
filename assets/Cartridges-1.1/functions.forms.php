<?php

	function show_printer_edit_element($printerId = 0){
		global $DB;
		
		$printerParams = array('officeId' => 0, 'modelId' => 0, 'companyId' => 0, 'printerName' => '', 'ip' => '', 'sn' => '', 'statusId' => 0);
		if($printerId > 0) $printerParams = $DB->fetch_assoc("SELECT * FROM `recycling_printers` WHERE `id`='$printerId'");
		$allowModelsArray = $allowCompanyArray = $printerStatusArray = array();
		
		$allowModels = $DB->query("SELECT `recycling_printers_models`.`id`, `recycling_printers_models`.`modelName`, `recycling_printers_types`.`printer_type_txt` FROM `recycling_printers_models` LEFT JOIN `recycling_printers_types` ON `recycling_printers_types`.`id`=`recycling_printers_models`.`deviceType` ORDER BY modelName");
		while($allowModel = $DB->fetch_assoc($allowModels)) $allowModelsArray[$allowModel['id']] = $allowModel['modelName'].' ('.$allowModel['printer_type_txt'].')';
 
 		$allowCompanies = $DB->query("SELECT `recycling_companies`.* FROM `recycling_companies` WHERE `office_id` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')");
		while($allowCompany = $DB->fetch_assoc($allowCompanies)) $allowCompanyArray[$allowCompany['id']] = $allowCompany['company'];

		$printerStatuses = $DB->query("SELECT * FROM `recycling_printers_stasuses` ORDER BY id");
		while($printerStatus = $DB->fetch_assoc($printerStatuses)) $printerStatusArray[$printerStatus['id']] = $printerStatus['stasus'];
		
		return '
		<form method="post" action="index.php">
		<input type="hidden" name="printerId" value="'.$printerId.'">				
		<fieldset class="editFormFieldset">
		<table>
			<caption>'.(($printerId == 0) ? 'Добавить новый принтер' : 'Изменить параметры').':</caption>
			<tbody>
				<tr><td>Офис</td><td>'.show_select($printerParams['officeId'], $_SESSION['myOfficesList'], 'officeId', true).'</td></tr>
				<tr><td>Модель</td><td>'.show_select($printerParams['modelId'], $allowModelsArray, 'modelId', true).'</td></tr>
				<tr><td>Владелец</td><td>'.show_select($printerParams['companyId'], $allowCompanyArray, 'companyId', true).'</td></tr>					
				<tr><td>Название в AD</td><td><input type="text" name="printerName" value="'.$printerParams['printerName'].'"></td></tr>
				<tr><td>IP</td><td><input type="text" name="ip" value="'.$printerParams['ip'].'"></td></tr>
				<tr><td>SN</td><td><input type="text" name="sn" value="'.$printerParams['sn'].'"></td></tr>
				<tr><td>Статус</td><td>'.show_select($printerParams['statusId'], $printerStatusArray, 'statusId', true).'</td></tr>
				<tr><td colspan="2"><input type="submit" name="cmdSavePrinterParams" value="Сохранить"></td></tr>					
			</tbody>
		</table>
		</fieldset>
		</form>';
	}

	function show_check_edit_element($checkId = 0){
		global $DB, $intlFormatter, $portalYesNo;		

		$checkParams = array('checkName' => '', 'checkSumm' => '', 'breakId' => 0, 'companyId' => 0, 'companyRefId' => 0, 'isPay' => 0, 'checkDate' => '', 'checkDateAdded' => '');
		if($checkId > 0) $checkParams = $DB->fetch_assoc("SELECT * FROM `recycling_checks` WHERE `id`='$checkId'");
		$breaksArray = $companysRefArray = $companysArray = array();
		
		$breakList = $DB->query("SELECT `recycling_breaks`.*, `office` FROM `recycling_breaks` LEFT JOIN `recycling_offices` ON `recycling_offices`.`id`=`recycling_breaks`.`officeId` WHERE officeId IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') ORDER BY `breakDate` DESC LIMIT 30");
		while($break = $DB->fetch_assoc($breakList)) $breaksArray[$break['id']] = $break['breakName'].', создана '.$intlFormatter->format($break['breakDate']).', офис '.$break['office'];
		
		$companyList = $DB->query("SELECT `recycling_companies`.*, `office` FROM `recycling_companies` LEFT JOIN `recycling_offices` ON `recycling_offices`.`id`=`recycling_companies`.`office_id` WHERE office_id IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') ORDER BY office_id, company");
		while($company = $DB->fetch_assoc($companyList)) $companysArray[$company['id']] = $company['company'].((isset($company['inn'])) ? ' ('.$company['inn'].')' : '').', офис '.$company['office'];
		
		$companyRef = $DB->query("SELECT * FROM `recycling_companies` WHERE `companyType`='2' ORDER BY company");
		while($company = $DB->fetch_assoc($companyRef)) $companysRefArray[$company['id']] = $company['company'].((isset($company['inn'])) ? ' ('.$company['inn'].')' : '');

		return '
		<form enctype="multipart/form-data" action="index.php" method="post">
		<input type="hidden" name="checkId" value="'.$checkId.'">
		<fieldset class="editFormFieldset">
		<table class="mainTable">
			<caption>'.(($checkId == 0) ? 'Добавление нового счёта' : 'Изменение счёта').'</caption>
			<tbody>
				<tr><td>Название счёта</td><td><input type="text" name="checkName" value="'.$checkParams['checkName'].'"></td></tr>
				<tr><td>Дата счёта</td><td><input type="date" name="checkDate" value="'.$checkParams['checkDate'].'"></td></tr>
				'.(($_SESSION['superAdm'] > 0) ? '<tr><td>Дата добавления</td><td><input type="date" name="checkDateAdded" value="'.$checkParams['checkDateAdded'].'"></td></tr>' : '').'
				<tr><td>Сумма счёта</td><td><input type="text" name="checkSumm" value="'.$checkParams['checkSumm'].'"></td></tr>
				<tr><td>Разбивка</td><td>'.show_select($checkParams['breakId'], $breaksArray, 'breakId', true).'</td></tr>
				<tr><td>Кому выставлен</td><td>'.show_select($checkParams['companyId'], $companysArray, 'companyId', true).'</td></tr>
				<tr><td>Кем выставлен</td><td>'.show_select($checkParams['companyRefId'], $companysRefArray, 'companyRefId', true).'</td></tr>				
				<tr><td>Оплачен</td><td>'.show_select($checkParams['isPay'], $portalYesNo, 'isPay', true).'</td></tr>				
				<tr><td>Файл</td><td><input name="userfile" type="file" /></td></tr>
				<tr><td colspan="2"><input type="submit" name="cmdEditCheck" value="сохранить"></td></tr>
			</tbody>
		</table>
		</fieldset>
		</form>';
	}
	
	function show_admin_edit_element($adminId = 0){
		global $DB, $portalYesNo;
		
		if($_SESSION['superAdm'] == 1 || $adminId == $_SESSION['adminId']){
		
			$adminParams = array('adminName' => '', 'adminStatus' => 1, 'superAdm' => 2);
			if($adminId > 0) $adminParams = $DB->fetch_assoc("SELECT * FROM `recycling_admins` WHERE `id`='$adminId'");
			
			return '
			<form action="index.php" method="post">
			<input type="hidden" name="adminId" value="'.$adminId.'">
			<fieldset class="editFormFieldset">
			<table class="mainTable">
				<caption>'.(($adminId == 0) ? 'Добавление админа' : 'Изменение параметров').'</caption>
				<tbody>
					<tr><td>Фамилия</td><td><input type="text" name="adminName" value="'.$adminParams['adminName'].'"'.(($_SESSION['superAdm'] == 1) ? '' : ' disabled').'></td></tr>
					<tr><td>Пароль</td><td><input type="text" name="adminAuthPass"></td></tr>
					<tr><td>Повтор пароля</td><td><input type="text" name="adminAuthPass2th"></td></tr>				
					'.(($_SESSION['superAdm'] == 1) ? '
					<tr><td>Супер</td><td>'.show_select($adminParams['superAdm'], $portalYesNo, 'superAdm').'</td></tr>
					<tr><td>Активен</td><td>'.show_select($adminParams['adminStatus'], $portalYesNo, 'adminStatus').'</td></tr>				
					' : '').'
					<tr><td colspan="2"><input type="submit" name="cmdEditAdmin" value="Сохранить">'.(($adminId > 0) ? ' <input type="submit" name="cmdDeleteAdmin" value="Удалить">' : '').'</td></tr>
				</tbody>
			</table>
			</fieldset>
			</form>';
		
		}else{
			return 'Добавление новых или изменение параметров существующих пользователей доступно только админам со статусом "Super"';
		}
	}

?>