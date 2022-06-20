<?php

	function portalShow_GET_options(){
		global $DB;

		$return = '<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>Авторизация:</legend>';
		
		if(isset($_SESSION['adminId'])){
			$return .= 'Авторизован как: <b>'.$DB->result("SELECT fio FROM `recycling_admins` WHERE `id`='$_SESSION[adminId]'").'</b> [<a href="?quit">выход</a>]'; 
		}else{
			$adminsListQ = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");
			if($DB->num_rows($adminsListQ) == 0){				
				$salt = md5(time());
				$DB->query("INSERT INTO `recycling_admins`(`fio`,`pass`,`salt`,`datereg`,`superAdm`) VALUES ('admin','".passHash('admin', $salt)."','$salt',NOW(),'1')");
				$adminsListQ = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");				
			}
			
			$return .= '
				<table class="mainTable" style="background-color: lightpink; padding: 10px;">
					<thead>
						<tr>
							<th>ФИО</th>
							<th>Пароль</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<select name="adminsAuthId"><option value="0" selected>-- выберите --</option>';
								while($row = $DB->fetch_assoc($adminsListQ)) $return .= "<option value=\"$row[id]\">$row[fio]</option>";			
								$return .= '
								</select>
							</td>
							<td><input type="password" name="adminsAuthPass"></td>
						</tr>
						<tr>
							<td colspan="2" align="center"><input type="submit" name="cmdAuthAdmin" value="авторизация"></td>
						</tr>
					</tbody>
				</table>
			</div>
			';
		}		

	
			$return .= '
			</fieldset>
			</form>';
			
		if(isset($_SESSION['adminId'])){

			$return .= '
			<fieldset><legend>Админы:</legend>
			<table class="mainTable zebra">
				<thead>
					<tr>
						<th>ФИО</th>
						<th>Действий</th>
						<th>Регистрация</th>
						<th>Последний вход</th>
						<th>IP</th>
						<th>Super</th>
						<th>Статус</th>
					</tr>
				</thead>
				<tbody>	
			';
			
			$adminsQuery = $DB->query("SELECT INET_NTOA(`lastip`) AS lastip, `recycling_admins`.`fio`, `recycling_admins`.`id`, `recycling_admins`.`datereg`, `recycling_admins`.`datelogin`,`recycling_admins`.`superAdm`,`recycling_admins`.`status`,eventsList.eventsCount
FROM `recycling_admins`
LEFT JOIN (SELECT COUNT(*) AS eventsCount, admin_id FROM `recycling_events_admins` GROUP BY admin_id) AS eventsList ON eventsList.admin_id=`recycling_admins`.`id`
ORDER BY fio");			
			while($pos = $DB->fetch_assoc($adminsQuery)){
				$return .= "
					<tr id=\"adm_$pos[id]\" class=\"blinking\">
						<td>$pos[fio]</td>
						<td>$pos[eventsCount]</td>
						<td>$pos[datereg]</td>
						<td>$pos[datelogin]</td>
						<td>$pos[lastip]</td>
						<td>".(($pos['superAdm'] == 1) ? 'Да' : '')."</td>
						<td>".(($pos['status'] == 1) ? 'Активен' : 'Отключен')."</td>
					</tr>";
			}
			
			$return .= '
			</tbody>
			</table>
			</fieldset>
			

			<fieldset><legend>Офисы:</legend>		
			<form name="form2" method="post" action="'.$_SERVER['PHP_SELF'].'">
			Офис: <select name="officeId">
			';
			$officesQuery = $DB->query("SELECT * FROM `recycling_offices` ORDER BY id");	
			while($office = $DB->fetch_assoc($officesQuery)) $return .= "<option value='$office[id]'>$office[office]</option>";				
			$return .= '
				</select> 		
			</form>
			<table class="mainTable zebra">
				<thead>
					<tr>
						<th>Компания</th>
						<th>Офис</th>
						<th>Админы</th>
					</tr>
				</thead>
				<tbody>		
			';
			
			$companyQuery = $DB->query("SELECT `recycling_companies`.`company`, `recycling_offices`.`office`, names.fio FROM `recycling_companies`
LEFT JOIN `recycling_offices` ON `recycling_companies`.`office_id`=`recycling_offices`.`id`
LEFT JOIN (
 SELECT GROUP_CONCAT(fio SEPARATOR ', ') AS fio, office_id FROM `recycling_admins_offices`
 LEFT JOIN `recycling_admins` ON `recycling_admins_offices`.`admin_id`=`recycling_admins`.`id`) AS `names` ON `names`.`office_id`=`recycling_companies`.`office_id`
ORDER BY `recycling_companies`.`office_id`, company");			
			
			if($DB->num_rows($companyQuery) > 0){			
				while($pos = $DB->fetch_assoc($companyQuery)){
					$return .= "
						<tr>
							<td>$pos[company]</td>
							<td>$pos[office]</td>
							<td>$pos[fio]</td>						
						</tr>";
				}
			}else{
				$return .= '<tr><td colspan="3">Нет записей. Добавьте админа в офис через форму выше.</td></tr>';				
			}
			
			$return .= '
				</tbody>
			</table>
			</fieldset>';
		}
		
			if($_SESSION['superAdm']){
			$return .= '
			<form name="form3" method="post" action="'.$_SERVER['PHP_SELF'].'">			
				Фамилия админа <input type="text" name="newAdminName">, пароль <input type="password" name="adminAuthPass"> пароль (повтор) <input type="password" name="adminAuthPass2th">			
				<input type="submit" name="cmdAddAdmin" value="добавить">			
			</form>
			<br>
			<form name="form4" method="post" action="'.$_SERVER['PHP_SELF'].'">			
				Название офиса <input type="text" name="newOfficeName">			
				<input type="submit" name="cmdAddOffice" value="добавить">			
			</form>
			<br>
			<form name="form5" method="post" action="'.$_SERVER['PHP_SELF'].'">			
				Компания <input type="text" name="newCompanyName">			
				<input type="submit" name="cmdAddCompany" value="добавить">			
			</form>
			<br>
			';
			}		
		
		
		return $return;		
	}	
	
	
	function portalShow_GET_payments(){
		return "Нет записей по счетам";
	}
	
	function portalShowPrinterStatistics($printerId = 0){
		global $DB;
		
		if($printerId == 0) return;
		
		$pages = $DB->fetch_array("SELECT pages FROM `recycling_events` WHERE `recycling_events`.`printer_id`='$printerId' ORDER BY id ASC");
		$dates = $DB->fetch_array("SELECT DATE_FORMAT(FROM_UNIXTIME(`dt`), '%Y-%m-%d %H:%i') AS `dt` FROM `recycling_events` WHERE `recycling_events`.`printer_id`='$printerId' ORDER BY id ASC");
		$printerInfo = $DB->fetch_assoc("SELECT * FROM `recycling_printers`
LEFT JOIN `recycling_printers_models` ON `recycling_printers`.`modelId`=`recycling_printers_models`.`id` WHERE `recycling_printers`.`id`='$printerId'");
		
		$return = '
		<fieldset><legend>Статистика:</legend>
			<figure class="highcharts-figure"><div id="container"></div></figure>
		</fieldset>
		<script>
			Highcharts.chart(\'container\', {
				  chart: { type: \'line\' },
				  title: { text: \'График напечатанных страниц\' },
				  xAxis: { categories: [\''.implode("','", $dates).'\'] },
				  yAxis: { title: { text: \'Напечатанные страницы, шт\' }
			  },
			  plotOptions: {
				  line: { dataLabels: { enabled: false }, enableMouseTracking: true }
			  },
			  series: [{ name: \''.$printerInfo['printerName'].'\', data: ['.implode(',', $pages).'] }]
			});
		</script>
		';		
		return $return.portalShow_GET_journal($printerId);
	}

	function portalShow_GET_breaks(){
		global $DB, $intlFormatter, $portalYesNo;

		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

		$eventsQuery = $DB->query("SELECT 
`recycling_cartridges`.`inv_num`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_companies`.`company`,
`recycling_companies`.`inn`,
`recycling_printers`.`printerName`,
`recycling_printers`.`companyId`,
`recycling_events_reasons`.`reason`,
`recycling_events`.`is_paid`,
`recycling_events`.`dt`,
`a`.`dt` AS dtOut,
`a`.`remark` AS remark
 FROM `recycling_events`
LEFT JOIN `recycling_cartridges` ON `recycling_events`.`cartridge_out_id`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_printers`.`companyId`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN (SELECT dt, remark, event_id FROM `recycling_events_admins` WHERE `event_typ_id`='1') AS a ON a.event_id=`recycling_events`.id
WHERE
".(($id > 0) ? "
`recycling_events`.`id` IN(SELECT eventId FROM `recycling_breaks_content` WHERE `breakId`='$id')" : "
`recycling_events`.`office_id` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
AND
`recycling_cartridges`.`status_id` IN ('1','0')
AND `is_paid`='0'" )."
ORDER BY `recycling_printers`.`companyId`, `dt` DESC");
	
		$statusCompanies = array();
		
		$return = '<fieldset><legend>События для разбивки:</legend>
		<table class="mainTable zebra">
			<thead>
				<tr>
					<th>№</th>
					<th>Принтер</th>
					<th>Картридж</th>
					<th>Причина замены</th>					
					<th>Дата замены</th>
					<th>Передан в заправку</th>
					<th>Комментарий</th>
					<th>Оплачен</th>
				</tr>
			</thead>
			<tbody>		
		';
		
		if($DB->num_rows($eventsQuery) > 0){
			$companyId = 0;
			$statusCompanies = array();
			while($pos = $DB->fetch_assoc($eventsQuery)){				
				if($companyId != $pos['companyId']){
					$statusCompanies[$pos['company']] = 1;					
					$companyId = $pos['companyId'];
					$return .= '<tr class="companyName"><td colspan="8">Для компании '.$pos['company'].', ИНН '.$pos['inn'].'</td></tr>';					
				}else{
					$statusCompanies[$pos['company']]++;					
				}	
				$return .= "
					<tr>
						<td>".$statusCompanies[$pos['company']]."</td>
						<td>$pos[printerName]</td>
						<td><b>$pos[inv_num]</b> ($pos[cartridgeName])</td>
						<td>$pos[reason]</td>					
						<td>".$intlFormatter->format($pos['dt'])."</td>
						<td>".$intlFormatter->format($pos['dtOut'])."</td>
						<td>".$pos['remark']."</td>	
						<td>".$portalYesNo[$pos['is_paid']]."</td>					
					</tr>";
			}
				
		}
		
		if(count($statusCompanies) > 0){		
			$return .= '<tr style="background-color: white !important;"><td colspan="8"><br/>';		
			foreach($statusCompanies as $companyName => $cartridgeCount) $return .= "Компания: $companyName, картриджей: $cartridgeCount<br/>";
			$return .= 'Итого в заправку: '.array_sum($statusCompanies).'</td></tr>';		
		}else{
			$return .= '<tr><td colspan="8" style="text-align: center;"><i>Нет данных</i></td></tr>';
		}
		
		$return .= '
		</tbody>
		</table>
		</fieldset>
		
		<fieldset><legend>Прошлые заправки:</legend>
		<table class="mainTable zebra">
			<thead>
				<tr>
					<th>Номер, дата</th>
					<th>Офис</th>
					<th>Контрагент</th>					
				</tr>
			</thead>
			<tbody>		
		';
		
		$breaksList = $DB->query("SELECT `recycling_breaks`.*, `recycling_offices`.`office`, `recycling_counteragents`.`refCompanyName` FROM `recycling_breaks` 
LEFT JOIN `recycling_offices` ON `recycling_breaks`.`officeId`=`recycling_offices`.`id`
LEFT JOIN `recycling_counteragents` ON `recycling_breaks`.`counterAgent`=`recycling_counteragents`.`id`
WHERE `recycling_breaks`.`officeId` IN(SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
ORDER BY breakDate");		

		if($DB->num_rows($breaksList)){
			$intlFormatter->setPattern('d MMMM YYYYг.');
			while($breakContent = $DB->fetch_assoc($breaksList)){
				$return .= "
					<tr>
						<td><a href=\"?w=breaks&amp;id=$breakContent[id]\">Заправка №$breakContent[breakNum] от ".$intlFormatter->format($breakContent['breakDate'])."</a></td>
						<td>$breakContent[office]</td>
						<td>$breakContent[refCompanyName]</td>
					</tr>							
				";
			}		
		}else{
			$return .= '<tr><td colspan="8" style="text-align: center;"><i>Нет данных</i></td></tr>';
		}
		
		$return .= '</tbody>
		</table>
		</fieldset>';		
		return $return;
		
	}	
	
	function portalShow_GET_cartridges(){
		global $DB;
		
		$cartridges = $DB->query("SELECT 
`recycling_cartridges_stasuses`.`stasus`,
`recycling_offices`.`office`,
`recycling_printers`.`printerName`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_cartridges`.*
FROM`recycling_cartridges`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN `recycling_offices` ON `recycling_cartridges`.`office_id`=`recycling_offices`.`id`
LEFT JOIN `recycling_printers` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
WHERE `recycling_cartridges`.`office_id` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
ORDER BY `recycling_cartridges`.`office_id`, status_id, id");

		$allowOffices = $DB->query("SELECT `recycling_offices`.* FROM `recycling_offices` LEFT JOIN `recycling_admins_offices` ON `recycling_offices`.`id`=`recycling_admins_offices`.`office_id` WHERE `recycling_admins_offices`.`admin_id`='$_SESSION[adminId]'");

		$return = '<form name="formRegCartridge" method="post" action="'.$_SERVER['PHP_SELF'].'">	
		<fieldset class="editFormFieldset"><legend>регистрация нового картриджа:</legend>
		<table>
			<tbody>		
				<tr>
					<td>новый картридж в офис:</td>
					<td><select name="officeRegcartridgeId" onChange="formRegCartridge.submit();"><option value="0" selected>-- выберите --</option>';
						while($allowOffice = $DB->fetch_assoc($allowOffices)) $return .= "<option value='$allowOffice[id]'".(($allowOffice['id'] == $_SESSION['officeRegcartridgeId']) ? ' selected' : '') . ">$allowOffice[office]</option>";
		$return .= '</select></td></tr>';	

		if((int)$_SESSION['officeRegcartridgeId'] > 0){
			
			$allowCartridges = $DB->query("SELECT
`recycling_printers_cartridges`.`cartridgeModelId`,
`recycling_cartridges_models`.`cartridgeName`
FROM `recycling_printers_cartridges`
LEFT JOIN `recycling_printers` ON `recycling_printers_cartridges`.`printerModelId`=`recycling_printers`.`modelId`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_printers_cartridges`.`cartridgeModelId`
 WHERE `officeId`='$_SESSION[officeRegcartridgeId]' AND `recycling_printers`.`statusId`='1' GROUP BY cartridgeModelId");			
			
			$return .= '<tr><td>модель:</td><td><select name="cartridgeModelId"><option value="0">-- выберите --</option>';
			while($allowCartridge = $DB->fetch_assoc($allowCartridges)) $return .= "<option value=\"$allowCartridge[cartridgeModelId]\">$allowCartridge[cartridgeName]</option>";			
			$return .= '</select></td></tr>
				<tr><td>инв.№ (0 - автогенерация номера):</td><td><input type="text" name="cartridgeInvNum" value="0" size="5"></td></tr>
				<tr><td>причина:</td><td><input type="radio" name="reason_new_id" value="3">покупка нового <input type="radio" name="reason_new_id" value="4" checked>покупка б/у </td></tr>				
				<tr><td colspan="2"><input type="submit" name="cmdAddCartridge" value="добавить новый картридж"></td></tr>			
			';
		}
		
		$return .= '</tbody></table></fieldset></form>		
		
		<fieldset><legend>Картриджи:</legend>
		<table class="mainTable zebra" id="tableCartridges">
			<thead>
				<tr>
					<th>Офис</th>
					<th>Картридж</th>
					<th>Статус картриджа</th>
					<th>Принтер</th>
				</tr>
			</thead>
			<tbody>';
			
			while($cartridge = $DB->fetch_assoc($cartridges)){
				$return .= "
				<tr>
					<td>$cartridge[office]</td>
					<td><b>$cartridge[inv_num]</b> ($cartridge[cartridgeName])</td>							
					<td>$cartridge[stasus]</td>
					<td>$cartridge[printerName]</td>						
				</tr>
				";			
			}
		
			$return .= '
		</tbody>
		</table>
		</fieldset>';		
		return $return;			
	}
	
	
	function show_cartridge_replace_form($printerId = 0){
		global $DB;

		$allowPrinters = $DB->query("SELECT * FROM `recycling_printers` WHERE `officeId` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') AND `statusId` IN (1,4) ORDER BY printerName");

		$selectPrinter = ($printerId > 0) ? $printerId : (($_SESSION['printerRCId'] > 0) ? $_SESSION['printerRCId'] : 0);
/*		
		if($DB->result("SELECT `model_id` FROM `recycling_printers` WHERE `id`='$selectPrinter'") == 0){
			return;			
		}
*/
		
		$return = '
		<form name="formChangeCartridge" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset class="editFormFieldset"><legend>Замена картриджа:</legend>
		<table>
			<tbody>
			<tr><td>Принтер:</td>
			<td><select name="printerRCId" onChange="formChangeCartridge.submit();" '.(($printerId > 0) ? 'disabled' : '').'><option value="0">--- выберите ---</option>';
			while($allowPrinter = $DB->fetch_assoc($allowPrinters)) $return .= "<option value='$allowPrinter[id]'".(($allowPrinter['id'] == $selectPrinter) ? ' selected' : '') . ">$allowPrinter[printerName]</option>";
			$return .= '					
			</select>
			</td>
		</tr>';
		
		if($selectPrinter > 0){
		
			$cartridgeInside = $DB->fetch_assoc("SELECT 
`recycling_printers`.`cartridge_inside`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_cartridges`.`inv_num` 
FROM recycling_printers 
LEFT JOIN `recycling_cartridges` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
WHERE `recycling_printers`.`id`='$selectPrinter'");

			$allowCartridges = $DB->query("SELECT
`recycling_cartridges`.`id`,
`recycling_cartridges`.`inv_num`,
`recycling_cartridges_models`.`cartridgeName`
FROM
`recycling_cartridges`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.id
WHERE `office_id`=(SELECT `officeId` FROM `recycling_printers` WHERE `recycling_printers`.`id`='$selectPrinter') 
AND `status_id`='2' 
AND cartridge_model_id IN (SELECT `cartridgeModelId` FROM `recycling_printers_cartridges`
	WHERE `recycling_printers_cartridges`.`printerModelId`=(SELECT `modelId` FROM `recycling_printers` WHERE `recycling_printers`.`id`='$selectPrinter')
) ORDER BY cartridgeName DESC");
		
			$return .= '
			<tr><td>Картридж в принтере:</td><td><input type="hidden" name="cartridge_out_id" value="'.$cartridgeInside['cartridge_inside'].'"><input type="text" value="'.(($cartridgeInside['inv_num'] > 0) ? $cartridgeInside['inv_num'].' ('.$cartridgeInside['cartridgeName'].')' : '(пусто)').'" disabled></td></tr>	
			<tr><td>Картридж для замены:</td><td><select name="cartridge_in_id">';
			while($cartridgeVariant = $DB->fetch_assoc($allowCartridges)) $return .= "<option value=\"$cartridgeVariant[id]\">$cartridgeVariant[inv_num] ($cartridgeVariant[cartridgeName])</option>";			
			$return .= '</select></td></tr>
			
			<tr>
				<td>Причина:</td>				
				<td>
					<label for="one"><input type="radio" name="reason_id" id="one" value="1" checked>закончился тонер</label>
					<input type="radio" name="reason_id" id="two" value="2">рекламация
				</td>
			</tr>
			<tr><td>Счётчик страниц:</td><td><input type="text" name="pages" size="10" maxlength="10"></td></tr>
			<tr><td>Примечание:</td><td><input type="text" name="remark"></td></tr>
			<tr><td colspan="2"><input type="submit" name="cmdAddEvent" value="заменить картридж"></td></tr>';
		}		
		$return .= '</tbody></table></fieldset></form>';
		return $return;		
	}
	
	function portalShow_GET_printers($printerId = 0){
		global $DB;
		$return = '';
		
		$query = $DB->query("SELECT 
 `recycling_printers_stasuses`.`stasus`,
 `recycling_companies`.`company`,
 `recycling_offices`.`office`,
 `recycling_cartridges`.`inv_num`,
 `recycling_cartridges_models`.`cartridgeName` AS cartridge_txt,
 `recycling_printers_types`.`printer_type_txt`,
 `cartridgeCross`.`cartrigdesForPrinter`,
 `recycling_printers`.*
 FROM `recycling_printers` 
 LEFT JOIN `recycling_offices` ON `recycling_printers`.`officeId`=`recycling_offices`.`id`
 LEFT JOIN `recycling_companies` ON `recycling_printers`.`companyId`=`recycling_companies`.`id`
 LEFT JOIN `recycling_printers_stasuses` ON `recycling_printers`.`statusId`=`recycling_printers_stasuses`.`id`
 LEFT JOIN `recycling_cartridges` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
 LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
 LEFT JOIN `recycling_printers_models` ON `recycling_printers_models`.`id`=`recycling_printers`.`modelId`
 LEFT JOIN `recycling_printers_types` ON `recycling_printers_models`.`deviceType`=`recycling_printers_types`.`id`
 LEFT JOIN (SELECT GROUP_CONCAT(cartridgeName SEPARATOR ', ') AS cartrigdesForPrinter, `recycling_printers_cartridges`.`printerModelId`
FROM `recycling_printers_cartridges`
LEFT JOIN `recycling_cartridges_models` ON `recycling_printers_cartridges`.`cartridgeModelId`=`recycling_cartridges_models`.`id`
GROUP BY `recycling_printers_cartridges`.`printerModelId`) AS `cartridgeCross` ON `cartridgeCross`.`printerModelId`=`recycling_printers`.`modelId`
 WHERE `recycling_printers`.`officeId` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
  ORDER BY office, statusId DESC, companyId, printerName");

		$printerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		
		if($printerId > 0){
			
			$modelParams = $DB->fetch_assoc("SELECT * FROM `recycling_printers` WHERE `id`='$printerId'");
			
			
			$printerStatuses = $DB->query("SELECT * FROM `recycling_printers_stasuses` ORDER BY id");
			
			$allowOffices = $DB->query("SELECT office_id, office FROM `recycling_admins_offices` 
LEFT JOIN `recycling_offices` ON `recycling_offices`.`id`=`recycling_admins_offices`.`office_id`
WHERE `admin_id`='$_SESSION[adminId]'");

			$allowModels = $DB->query("SELECT
`recycling_printers_models`.`id`,
`recycling_printers_models`.`modelName`,
`recycling_printers_types`.`printer_type_txt`
FROM `recycling_printers_models`
LEFT JOIN `recycling_printers_types` ON `recycling_printers_types`.`id`=`recycling_printers_models`.`deviceType`
 ORDER BY modelName");
 
			$allowCompanies = $DB->query("SELECT `recycling_companies`.* FROM `recycling_companies` WHERE `office_id` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')");
			
			$return .= '
			<form method="post" id="editForm" action="'.$_SERVER['PHP_SELF'].'">
			<input type="hidden" name="printerId" value="'.$printerId.'">				
			<fieldset class="editFormFieldset" id="editForm"><legend>Изменить параметры:</legend>
			<table>
				<tbody>
					<tr>
						<td>Офис</td>
						<td>
							<select name="officeId">
							<option value="0">--- выберите ---</option>';
			while($allowOffice = $DB->fetch_assoc($allowOffices)) $return .= "<option value='$allowOffice[office_id]'".(($allowOffice['office_id'] == $modelParams['officeId']) ? ' selected' : '') . ">$allowOffice[office]</option>";
							$return .= '					
							</select>
						</td>
					</tr>
					<tr>
						<td>Модель</td>
						<td>
							<select name="modelId">
							<option value="0">--- выберите ---</option>';
			while($allowModel = $DB->fetch_assoc($allowModels)) $return .= "<option value='$allowModel[id]'".(($allowModel['id'] == $modelParams['modelId']) ? ' selected' : '') . ">$allowModel[modelName] ($allowModel[printer_type_txt])</option>";
			$return .= '					
			</select>						
						</td>
					</tr>
					<tr>
						<td>Владелец</td>
						<td>
						<select name="companyId">
							<option value="0">--- выберите ---</option>';
			while($allowCompany = $DB->fetch_assoc($allowCompanies)) $return .= "<option value='$allowCompany[id]'".(($allowCompany['id'] == $modelParams['companyId']) ? ' selected' : '') . ">$allowCompany[company]</option>";
			$return .= '					
			</select>	</td>
					</tr>					
					<tr>
						<td>Название в AD</td>
						<td><input type="text" name="printerName" value="'.$modelParams['printerName'].'"></td>
					</tr>
					<tr>
						<td>IP</td>
						<td><input type="text" name="ip" value="'.$modelParams['ip'].'"></td>
					</tr>
					<tr>
						<td>SN</td>
						<td><input type="text" name="sn" value="'.$modelParams['sn'].'"></td>
					</tr>
					<tr>
						<td>Статус</td>
						<td>
							<select name="statusId">
							<option value="0">--- выберите ---</option>';
			while($printerStatus = $DB->fetch_assoc($printerStatuses)) $return .= "<option value='$printerStatus[id]'".(($printerStatus['id'] == $modelParams['statusId']) ? ' selected' : '') . ">$printerStatus[stasus]</option>";
			$return .= '					
			</select>	</td>
					</tr>
					<tr>
						<td colspan="2"><input type="submit" name="cmdSavePrinterParams" value="Сохранить"></td>
					</tr>					
				</tbody>
			</table>
			</fieldset>
			</form>
			';
		}

		$return .= '		
		<fieldset><legend>Принтеры:</legend>
		<table class="mainTable zebra">
			<thead>
				<tr>
					<th>ID</th>				
					<th>Офис</th>
					<th>Название в AD</th>
					<th>Тип</th>					
					<th>Владелец</th>
					<th>Типы картриджей</th>
					<th>Внутри</th>
					<th>IP</th>
					<th>SN</th>
					<th>Статус</th>
					<th></th>
				</tr>
			</thead>
			<tbody>';
			
			while($row = $DB->fetch_assoc($query)){
				$return .= "
					<tr>
						<td>$row[id]</td>
						<td>$row[office]</td>
						<td><a href=\"?statistic=printer&id=$row[id]\">$row[printerName]</a></td>
						<td>$row[printer_type_txt]</td>
						<td>$row[company]</td>
						<td>$row[cartrigdesForPrinter]</td>
						<td>".(($row['inv_num'] > 0) ? "<b>$row[inv_num]</b> ($row[cartridge_txt])" : '')."</td>
						<td>".((!empty($row['ip'])) ? "<a href=\"http://$row[ip]/\" target=\"_blank\">$row[ip]</a>" : '')."</td>
						<td>$row[sn]</td>						
						<td>$row[stasus]</td>
						<td><a href=\"?w=printers&id=$row[id]#editForm\">[e]</a></td>
					</tr>
				";			
			}
		
			$return .= '
		</tbody>
		</table>
		</fieldset>';		
		return $return;		
	}
	

	function portalShow_GET_journal($printerId = 0){
		global $DB, $intlFormatter;		
		$return = show_cartridge_replace_form($printerId);		
		$return .= '
		<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>Журнал:</legend>
		<table class="mainTable zebra">
			<thead>
				<tr>
					<th>id</th>
					<th>Принтер</th>
					<th>Изъят</th>
					<th>Вставлен</th>
					<th>Причина</th>
					<th>Счётчик</th>
					<th>Журнал событий</th>
					<th>Статус</th>
					<th>Оплачен?</th>
				</tr>
			</thead>
			<tbody>
			';

		$journalQuery = $DB->query("SELECT
`recycling_events_reasons`.`reason`,
`recycling_events_stasuses`.`stasus`,
`recycling_printers`.`printerName`,
`cartrige_in`.`cartridgeName` AS cartridge_in_name,
`cartrige_in`.`inv_num` AS cartridge_in_num,
`cartrige_out`.`cartridgeName` AS cartridge_out_name,
`cartrige_out`.`inv_num` AS cartridge_out_num,
`cartrige_new`.`cartridgeName` AS cartridge_new_name,
`cartrige_new`.`inv_num` AS cartridge_new_num,
`event_txt`.`event_txt`,
recycling_events.*
 FROM recycling_events
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_events_stasuses` ON `recycling_events`.`status_id`=`recycling_events_stasuses`.`id`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN (SELECT `recycling_cartridges_models`.`cartridgeName`, `recycling_cartridges`.`id`, `recycling_cartridges`.`inv_num` FROM `recycling_cartridges` LEFT JOIN `recycling_cartridges_models`
ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`) AS `cartrige_in` ON `cartrige_in`.`id`=`recycling_events`.`cartridge_in_id`
LEFT JOIN (SELECT `recycling_cartridges_models`.`cartridgeName`, `recycling_cartridges`.`id`, `recycling_cartridges`.`inv_num` FROM `recycling_cartridges` LEFT JOIN `recycling_cartridges_models`
ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`) AS `cartrige_out` ON `cartrige_out`.`id`=`recycling_events`.`cartridge_out_id`
LEFT JOIN (SELECT `recycling_cartridges_models`.`cartridgeName`, `recycling_cartridges`.`id`, `recycling_cartridges`.`inv_num` FROM `recycling_cartridges` LEFT JOIN `recycling_cartridges_models`
ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`) AS `cartrige_new` ON `cartrige_new`.`id`=`recycling_events`.`cartridge_new_id`
LEFT JOIN (
	SELECT GROUP_CONCAT(CONCAT( DATE_FORMAT( FROM_UNIXTIME(`recycling_events_admins`.`dt`), '%Y-%m-%d %H:%i'),' - ',`recycling_admins`.`fio`,': ',`recycling_events_stasuses`.`stasus`, ' <i>', `recycling_events_admins`.`remark`,'</i>') SEPARATOR '<br/>') AS event_txt, recycling_events_admins.`event_id` FROM recycling_events_admins
		LEFT JOIN `recycling_events_stasuses` ON `recycling_events_admins`.`event_typ_id`=`recycling_events_stasuses`.`id`
		LEFT JOIN `recycling_admins` ON `recycling_events_admins`.`admin_id`=`recycling_admins`.`id`
	 GROUP BY event_id ORDER BY `recycling_events_admins`.`id`
) AS event_txt ON `event_txt`.`event_id`=`recycling_events`.`id`
WHERE `recycling_events`.`office_id` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
".(($printerId > 0) ? " AND `recycling_events`.`printer_id`='$printerId'" : '')." ORDER BY dt DESC LIMIT $_SESSION[journalEvents]");

		while ($row = $DB->fetch_assoc($journalQuery)){
			$return .= '		
			<tr id="event_'.$row['id'].'" class="blinking">
				<td>'.$row['id'].'</td>
				<td>'.$row['printerName']."</td>
				<td>".(($row['cartridge_new_name']) ? '' : "<b>$row[cartridge_out_num]</b> ($row[cartridge_out_name])")."</td>				
				<td>".(($row['cartridge_new_name']) ? "<b>$row[cartridge_new_num]</b> ($row[cartridge_new_name])" : "<b>$row[cartridge_in_num]</b> ($row[cartridge_in_name])")."</td>
				<td>".$row['reason'].'</td>
				<td>'.$row['pages'].'</td>
				<td>'.$row['event_txt'].'</td>
				<td>';			
				if($row['status_id'] != 2) $return .= "<b>$row[stasus]</b>";
				if($row['status_id'] == 0) {
					$return .= '<input type="submit" name="cmdEvent[1]['.$row['id'].']" value="передать заправщику">';
				}elseif ($row['status_id'] == 1) {
					$return .= '<br /><input type="submit" name="cmdEvent[2]['.$row['id'].']" value="в резерв"><input type="submit" name="cmdEvent[3]['.$row['id'].']" value="вывести из оборота">';
				}
			$return .= '
				</td>
				<td align="center">'.(($row['is_paid'] == 1) ? 'yes' : "<input type='submit' name='cmdPay[$row[id]]' value='pay'>").'</td>
			</tr>';
		}
			$return .= '
		</tbody>
		</table>
		</fieldset>
		</form>';		
		return $return;
	}
	
	function addNewOffice($newOfficeName){
		global $DB;
		if($_SESSION['superAdm'] == 1){
			$officeName = mb_substr($newOfficeName, 0, 50);
			if(mb_strlen($officeName) != mb_strlen($newOfficeName)){
				print "Слишком длинное название";
				exit;
			}
			$DB->query("INSERT INTO `recycling_offices`(`office`) VALUES ('".$DB->escape($officeName)."'); ");
		}
	}
	
	function addNewUser(){
		global $DB;
		if($_SESSION['superAdm'] == 1){
			$admFio = mb_substr($_POST['newAdminName'], 0, 30);
			if(mb_strlen($admFio) < 5){
				print "Слишком короткое имя (менее 5 символов)";
				exit;
			}
				
			if(mb_strlen($_POST['adminAuthPass']) < 5){
				print "Слишком короткий пароль (менее 5 символов)";
				exit;
			}
				
			if($_POST['adminAuthPass'] != $_POST['adminAuthPass2th']){
				print "Введенные пароли не совпадают";
				exit;
			}
				
			if($DB->num_rows("SELECT * FROM `recycling_admins` WHERE `fio`='".$DB->escape($admFio)."'") > 0){
				print "Админ с таким ФИО уже есть";
				exit;
			}
		
			$salt = md5(time());
			$DB->query("INSERT INTO `recycling_admins`(`fio`,`pass`,`salt`,`datereg`) VALUES ('".$DB->escape($admFio)."','".passHash($_POST['adminAuthPass'], $salt)."','$salt',NOW())");
		}		
	}
	
	function saveCartridgeParams($cartridgeName, $cartridgeСolor, $cartridgeCapacity, $cartridgeModelId){
		global $DB, $portalCartridgeColors;
		if(mb_strlen($cartridgeName) <= 2){ print "Слишком короткое название модели"; exit; }		
		if(!array_key_exists($cartridgeСolor, $portalCartridgeColors) || $cartridgeСolor == 0){ print "Неверный цвет"; exit; }
		$DB->query("INSERT IGNORE INTO `recycling_cartridges_models`(`id`) VALUES ('$cartridgeModelId')");		
		$DB->query("UPDATE `recycling_cartridges_models` SET
		`cartridgeName`='".$DB->escape($cartridgeName)."',
		`cartridgeColor`='$cartridgeСolor',
		`cartridgeCapacity`='$cartridgeCapacity'
		WHERE `id`='$cartridgeModelId'");
		return true;
	}
	
?>