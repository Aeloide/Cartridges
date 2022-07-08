<?php

	function show_office_edit_element($officeId = 0){
		global $DB, $portalCompanyTypes;
		$officeParams = array('officeName' => '');
		if($officeId > 0) $officeParams = $DB->fetch_assoc("SELECT * FROM `recycling_offices` WHERE `id`='$officeId'");
		$checksQuery = $DB->query("SELECT  `a`.`admin_id` AS `mark`, `recycling_admins`.`id`, `recycling_admins`.`adminName` FROM `recycling_admins`LEFT JOIN (SELECT admin_id FROM `recycling_admins_offices` WHERE `recycling_admins_offices`.`office_id`='$officeId') AS a ON `a`.`admin_id`=`recycling_admins`.`id` ORDER BY adminName");
		$checkBoxTxt = '';
		if($DB->num_rows($checksQuery) > 0){
			while($officeCheckbox = $DB->fetch_assoc($checksQuery)) $checkBoxTxt .= '<input type="checkbox" name="adminsInOffice[]" value="'.$officeCheckbox['id'].'"'.(($officeCheckbox['mark'] > 0) ? ' checked' : '').'>'.$officeCheckbox['adminName'].'</br>';
		}
		
		return '
		<form method="post" action="index.php">
		<input type="hidden" name="officeId" value="'.$officeId.'">				
		<fieldset class="editFormFieldset">
		<table>
			<caption>'.(($officeId == 0) ? 'Добавить новый офис' : 'Изменить параметры').':</caption>
			<tbody>
				<tr><td>Название</td><td><input type="text" name="officeName" value="'.htmlspecialchars($officeParams['officeName']).'"></td></tr>
				<tr><td style="vertical-align: top;">Админы</td><td>'.$checkBoxTxt.'</td></tr>
				<tr><td colspan="2"><input type="submit" name="cmdSaveOfficeParams" value="Сохранить">'.(($officeId > 0) ? ' <input type="submit" name="cmdDeleteOffice" value="Удалить">' : '').'</td></tr>					
			</tbody>
		</table>
		</fieldset>
		</form>';
		
	}


	function show_company_edit_element($companyId = 0){
		global $DB, $portalCompanyTypes;
		
		$companyParams = array('company' => '', 'inn' => '', 'officeId' => 0, 'companyType' => 0);
		if($companyId > 0) $companyParams = $DB->fetch_assoc("SELECT * FROM `recycling_companies` WHERE `id`='$companyId'");

		$officeListArray = array();
		$officesLists = $DB->query("SELECT `id`, `officeName` FROM `recycling_offices` ORDER BY `recycling_offices`.`id`");
		while($office = $DB->fetch_assoc($officesLists)) $officeListArray[$office['id']] = $office['officeName'];
			
		return '
		<form method="post" action="index.php">
		<input type="hidden" name="companyId" value="'.$companyId.'">				
		<fieldset class="editFormFieldset">
		<table>
			<caption>'.(($companyId == 0) ? 'Добавить новую компанию' : 'Изменить параметры').':</caption>
			<tbody>
				<tr><td>Название</td><td><input type="text" name="company" value="'.htmlspecialchars($companyParams['company']).'"></td></tr>
				<tr><td>ИНН</td><td><input type="text" name="inn" value="'.$companyParams['inn'].'"></td></tr>
				<tr><td>Тип</td><td>'.show_select($companyParams['companyType'], $portalCompanyTypes, 'companyType').'</td></tr>					
				<tr><td>Офис</td><td>'.show_select($companyParams['officeId'], $officeListArray, 'officeId').'</td></tr>
				<tr><td colspan="2"><input type="submit" name="cmdSaveCompanyParams" value="Сохранить">'.(($companyId > 0) ? ' <input type="submit" name="cmdDeleteСompany" value="Удалить">' : '').'</td></tr>					
			</tbody>
		</table>
		</fieldset>
		</form>';
	}

	function show_printer_edit_element($printerId = 0){
		global $DB;
		
		$printerParams = array('officeId' => 0, 'modelId' => 0, 'companyId' => 0, 'printerName' => '', 'ip' => '', 'sn' => '', 'statusId' => 0);
		if($printerId > 0) $printerParams = $DB->fetch_assoc("SELECT * FROM `recycling_printers` WHERE `id`='$printerId'");
		$allowModelsArray = $allowCompanyArray = $printerStatusArray = array();
		
		$allowModels = $DB->query("SELECT `recycling_printers_models`.`id`, `recycling_printers_models`.`modelName`, `recycling_printers_types`.`printer_type_txt` FROM `recycling_printers_models` LEFT JOIN `recycling_printers_types` ON `recycling_printers_types`.`id`=`recycling_printers_models`.`deviceType` ORDER BY modelName");
		while($allowModel = $DB->fetch_assoc($allowModels)) $allowModelsArray[$allowModel['id']] = $allowModel['modelName'].' ('.$allowModel['printer_type_txt'].')';
 
 		$allowCompanies = $DB->query("SELECT `recycling_companies`.* FROM `recycling_companies` WHERE `officeId` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')");
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
				<tr><td>Офис</td><td>'.show_select($printerParams['officeId'], $_SESSION['myOfficesList'], 'officeId').'</td></tr>
				<tr><td>Модель</td><td>'.show_select($printerParams['modelId'], $allowModelsArray, 'modelId').'</td></tr>
				<tr><td>Владелец</td><td>'.show_select($printerParams['companyId'], $allowCompanyArray, 'companyId').'</td></tr>					
				<tr><td>Название в AD</td><td><input type="text" name="printerName" value="'.$printerParams['printerName'].'"></td></tr>
				<tr><td>IP</td><td><input type="text" name="ip" value="'.$printerParams['ip'].'"></td></tr>
				<tr><td>SN</td><td><input type="text" name="sn" value="'.$printerParams['sn'].'"></td></tr>
				<tr><td>Статус</td><td>'.show_select($printerParams['statusId'], $printerStatusArray, 'statusId').'</td></tr>
				<tr><td colspan="2"><input type="submit" name="cmdSavePrinterParams" value="Сохранить"></td></tr>					
			</tbody>
		</table>
		</fieldset>
		</form>';
	}

	function show_check_edit_element($checkId = 0){
		global $DB, $intlFormatter, $portalYesNo;		
		$intlFormatter->setPattern('d MMMM YYYYг.');
	
		$checkParams = array('checkName' => '', 'checkSumm' => '', 'breakId' => 0, 'companyId' => 0, 'companyRefId' => 0, 'isPay' => 0, 'checkDate' => '', 'checkDateAdded' => '');
		if($checkId > 0) $checkParams = $DB->fetch_assoc("SELECT * FROM `recycling_checks` WHERE `id`='$checkId'");
		$breaksArray = $companysRefArray = $companysArray = array();
		
		$breakList = $DB->query("SELECT `recycling_breaks`.*, `officeName` FROM `recycling_breaks` LEFT JOIN `recycling_offices` ON `recycling_offices`.`id`=`recycling_breaks`.`officeId` WHERE officeId IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') ORDER BY `breakDate` DESC LIMIT 30");
		while($break = $DB->fetch_assoc($breakList)) $breaksArray[$break['id']] = $break['breakName'].', создано '.$intlFormatter->format($break['breakDate']).', офис '.$break['officeName'];
		
		$companyList = $DB->query("SELECT `recycling_companies`.*, `officeName` FROM `recycling_companies` LEFT JOIN `recycling_offices` ON `recycling_offices`.`id`=`recycling_companies`.`officeId` WHERE officeId IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') ORDER BY officeId, company");
		while($company = $DB->fetch_assoc($companyList)) $companysArray[$company['id']] = $company['company'].((isset($company['inn'])) ? ' ('.$company['inn'].')' : '').', офис '.$company['officeName'];
		
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
				'.(($_SESSION['superAdm'] > 0) ? '<tr><td>Дата добавления</td><td><input type="date" name="checkDateAdded" value="'.(($checkParams['checkDateAdded'] == '') ? date("Y-m-d") : $checkParams['checkDateAdded']).'"></td></tr>' : '').'
				<tr><td>Сумма счёта</td><td><input type="text" name="checkSumm" value="'.$checkParams['checkSumm'].'"></td></tr>
				<tr><td>Разбивка</td><td>'.show_select($checkParams['breakId'], $breaksArray, 'breakId').'</td></tr>
				<tr><td>Кому выставлен</td><td>'.show_select($checkParams['companyId'], $companysArray, 'companyId').'</td></tr>
				<tr><td>Кем выставлен</td><td>'.show_select($checkParams['companyRefId'], $companysRefArray, 'companyRefId').'</td></tr>				
				<tr><td>Оплачен</td><td>'.show_select($checkParams['isPay'], $portalYesNo, 'isPay').'</td></tr>				
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