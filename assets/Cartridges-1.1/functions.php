<?php

	function show_cartridges_statistic($cartridgeId = 0){
		global $DB, $portalYesNo, $intlFormatter;
		$intlFormatter->setPattern('d MMMM YYYYг.');		
		if($cartridgeId== 0) return;
		
		$cartridgeEvents = $DB->query("
SELECT GROUP_CONCAT(workName SEPARATOR ', ') AS `grpTxt`, `recycling_cartridges_works`.*,
`recycling_cartridges`.`inv_num`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_checks`.`checkName`,
UNIX_TIMESTAMP(`recycling_checks`.`checkDate`) AS `checkDateU`,
`recycling_checks`.`isPay`,
`recycling_counteragents`.`refCompanyName`,
`recycling_cartridges_worknames`.`workName`,
`recycling_breaks_content`.`checkId`
FROM `recycling_cartridges_works`
LEFT JOIN `recycling_events` ON `recycling_events`.`id`=`recycling_cartridges_works`.`eventId`
LEFT JOIN `recycling_cartridges` ON `recycling_cartridges`.`id`=`recycling_events`.`cartridge_out_id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
LEFT JOIN `recycling_breaks_content` ON `recycling_breaks_content`.`eventId`=`recycling_events`.`id`
LEFT JOIN `recycling_checks` ON `recycling_checks`.`id`=`recycling_breaks_content`.`checkId`
LEFT JOIN `recycling_cartridges_worknames` ON `recycling_cartridges_worknames`.`id`=`recycling_cartridges_works`.`workId`
LEFT JOIN `recycling_counteragents` ON `recycling_counteragents`.`id`=`recycling_checks`.`companyRefId`
WHERE `recycling_cartridges`.`id`='$cartridgeId'
GROUP BY `eventId`
ORDER BY `recycling_checks`.`checkDateAdded` DESC");


		$return = '<fieldset><legend>История работ с картриджем:</legend>
				<table class="mainTable zebra">
					<thead>
						<tr>
							<th>Картридж</th>
							<th>Работы</th>
							<th>Счёт</th>
							<th>От компании</th>
							<th>Оплачен</th>							
						</tr>
					</thead>
					<tbody>
		';
		
		if($DB->num_rows($cartridgeEvents) > 0){		
			while($cartridgeEvent = $DB->fetch_assoc($cartridgeEvents)){
				$return .= "
							<tr>
								<td><b>$cartridgeEvent[inv_num]</b> ($cartridgeEvent[cartridgeName])</td>
								<td>$cartridgeEvent[grpTxt]</td>
								<td><a href=\"?getCheck=$cartridgeEvent[checkId]\">$cartridgeEvent[checkName] от ".$intlFormatter->format($cartridgeEvent['checkDateU'])."</a></td>
								<td>$cartridgeEvent[refCompanyName]</td>									
								<td>".$portalYesNo[$cartridgeEvent['isPay']]."</td>							
							</tr>";			
			}
		}else{
			$return .= '<tr><td colspan="6"><i>Нет данных</i></td></tr>';				
		}

		$return .= '</tbody>
				</table>
				</fieldset>';
				
		
		$useEvents = $DB->query("
SELECT
`recycling_events`.`id`,
`recycling_events`.`dt`,
`recycling_printers`.`printerName`,
`recycling_offices`.`office`,
`recycling_cartridges`.`inv_num`,
`recycling_cartridges_models`.`cartridgeName`
FROM `recycling_events`
LEFT JOIN `recycling_printers` ON `recycling_printers`.`id`=`recycling_events`.`printer_id`
LEFT JOIN `recycling_offices` ON `recycling_offices`.`id`=`recycling_events`.`office_id`
LEFT JOIN `recycling_cartridges` ON `recycling_cartridges`.`id`=`recycling_events`.`cartridge_in_id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
WHERE `cartridge_in_id`='$cartridgeId'
ORDER BY `recycling_events`.`dt` DESC");

		$return .= '<fieldset><legend>История использования картриджа:</legend>
				<table class="mainTable zebra">
					<thead>
						<tr>
							<th>№</th>
							<th>Картридж</th>
							<th>Принтер</th>
							<th>Установлен</th>							
							<th>Офис</th>
						</tr>
					</thead>
					<tbody>
		';

		if($DB->num_rows($useEvents) > 0){		
			while($cartridgeEvent = $DB->fetch_assoc($useEvents)){
				$return .= "
						<tr>
							<td>$cartridgeEvent[id]</td>
							<td><b>$cartridgeEvent[inv_num]</b> ($cartridgeEvent[cartridgeName])</td>
							<td>$cartridgeEvent[printerName]</td>
							<td>".$intlFormatter->format($cartridgeEvent['dt'])."</td>
							<td>$cartridgeEvent[office]</td>				
						</tr>";			
			}
		}else{
			$return .= '<tr><td colspan="4"><i>Нет данных</i></td></tr>';				
		}

		$return .= '</tbody>
				</table>
				</fieldset>';
		
		return $return;
	}

	function portalShow_GET_options(){
		global $DB;

		$return = '<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>Авторизация:</legend>';
		
		if(isset($_SESSION['adminId'])){
			$return .= 'Авторизован как: <b>'.$DB->result("SELECT fio FROM `recycling_admins` WHERE `id`='$_SESSION[adminId]'").'</b> [<a href="?quit">выход</a>]'; 
		}else{
			$adminsList = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");
			if($DB->num_rows($adminsList) == 0){				
				$salt = md5(time());
				$DB->query("INSERT INTO `recycling_admins`(`fio`,`pass`,`salt`,`datereg`,`superAdm`) VALUES ('admin','".passHash('admin', $salt)."','$salt',NOW(),'1')");
				$adminsList = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");				
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
								while($admin = $DB->fetch_assoc($adminsList)) $return .= "<option value=\"$admin[id]\">$admin[fio]</option>";			
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
			<table class="mainTable zebra" id="ajaxedRows">
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
					<tr data-id=\"$pos[id]\" data-target=\"getAdminEditElement\" id=\"adm_$pos[id]\" class=\"blinking\">
						<td><a href=\"javascript://\" class=\"add-row\">$pos[fio]</a></td>
						<td>$pos[eventsCount]</td>
						<td>$pos[datereg]</td>
						<td>$pos[datelogin]</td>
						<td>$pos[lastip]</td>
						<td>".(($pos['superAdm'] == 1) ? 'Да' : '')."</td>
						<td>".(($pos['status'] == 1) ? 'Активен' : 'Отключен')."</td>
					</tr>";
			}
			
			$return .= '
					<tr data-id="new" data-target="getAdminEditElement">
						<td colspan="7" style="text-align:right;">[<a href="javascript://" class="add-row">Добавить админа</a>]</td>
					</tr>		
			</tbody>
			</table>
			</fieldset>
			
			<fieldset><legend>Офисы:</legend>		
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
		global $DB, $portalYesNo, $intlFormatter;
		$intlFormatter->setPattern('d MMMM YYYYг.');
	
		$checksList = $DB->query("SELECT
`recycling_checks`.*,
UNIX_TIMESTAMP(`recycling_checks`.`checkDate`) AS `checkDateU`,
UNIX_TIMESTAMP(`recycling_checks`.`checkDateAdded`) AS `checkDateAddedU`,
`recycling_companies`.`company`,
`recycling_companies`.`inn`,
`recycling_counteragents`.`refCompanyName`,
`recycling_counteragents`.`refInn`,
`recycling_breaks`.`breakName`
 FROM `recycling_checks`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_checks`.`companyId`
LEFT JOIN `recycling_counteragents` ON `recycling_counteragents`.`id`=`recycling_checks`.`companyRefId`
LEFT JOIN `recycling_breaks` ON `recycling_breaks`.`id`=`recycling_checks`.`breakId`
ORDER BY `recycling_checks`.`id`");

		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;			
		$return = show_check_edit_element($id);
		
		$return .= '<fieldset><legend>
		<div class="floatRight"><div class="editFormAddElement"><a href="?w=payments&new">Добавить новый счёт</a></div></div>
		Счета:</legend>
			<table class="mainTable zebra" id="ajaxedRows">
				<thead>
					<tr>
						<th>Счёт</th>
						<th>Сумма</th>
						<th>Разбивка</th>							
						<th>Кому выставлен</th>
						<th>Кем выставлен</th>
						<th>Добавлен</th>							
						<th>Оплачен</th>
						<th>DL</th>
					</tr>
				</thead>
				<tbody>	
			';
			
			if($DB->num_rows($checksList) > 0){			
				while($check = $DB->fetch_assoc($checksList)){
					$return .= "
						<tr id=\"check_$check[id]\" class=\"blinking\" data-id=\"$check[id]\" data-target=\"getCheckChangeElement\">
							<td><a href=\"javascript://\" class=\"add-row\">$check[checkName] от ".$intlFormatter->format($check['checkDateU'])."</a></td>
							<td>$check[checkSumm]</td>
							<td><a href=\"?w=breaks&id=$check[breakId]\">$check[breakName]</a></td>	
							<td><b>$check[company]</b> ($check[inn])</td>
							<td><b>$check[refCompanyName]</b> ($check[refInn])</td>
							<td>".$intlFormatter->format($check['checkDateAddedU'])."</td>								
							<td>".$portalYesNo[$check['isPay']]."</td>
							<td>".(($check['mimeType']) ? "<a href=\"?getCheck=$check[id]\">Скачать</a>" : '')."</td>							
						</tr>
					";
				}
			}else{
				$return .= '<tr><td colspan="6" style="text-align: center;"><i>Пока нет счетов</i></td></tr>';
			}
		
			$return .= '
				</tbody>
			</table>
			</fieldset>';
		return $return;			
	}
	
	

	
	
	function printer_statistic_rc($printerId){
		global $DB;
$cartridgeReplaces = $DB->query("
SELECT
`recycling_events`.`id`,
`recycling_events`.`dt`,
`cartrige_out`.`cartridgeName` AS cartridge_out_name,
`cartrige_out`.`inv_num` AS cartridge_out_num,
`cartrige_in`.`cartridgeName` AS cartridge_in_name,
`cartrige_in`.`inv_num` AS cartridge_in_num,
`recycling_events`.`pages`,
`recycling_events_reasons`.`reason`
 FROM recycling_events
LEFT JOIN `recycling_events_stasuses` ON `recycling_events`.`status_id`=`recycling_events_stasuses`.`id`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN (SELECT `recycling_cartridges_models`.`cartridgeName`, `recycling_cartridges`.`id`, `recycling_cartridges`.`inv_num` FROM `recycling_cartridges` LEFT JOIN `recycling_cartridges_models`
ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`) AS `cartrige_in` ON `cartrige_in`.`id`=`recycling_events`.`cartridge_in_id`
LEFT JOIN (SELECT `recycling_cartridges_models`.`cartridgeName`, `recycling_cartridges`.`id`, `recycling_cartridges`.`inv_num` FROM `recycling_cartridges` LEFT JOIN `recycling_cartridges_models`
ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`) AS `cartrige_out` ON `cartrige_out`.`id`=`recycling_events`.`cartridge_out_id`
LEFT JOIN (SELECT `recycling_cartridges_models`.`cartridgeName`, `recycling_cartridges`.`id`, `recycling_cartridges`.`inv_num` FROM `recycling_cartridges` LEFT JOIN `recycling_cartridges_models`
ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`) AS `cartrige_new` ON `cartrige_new`.`id`=`recycling_events`.`cartridge_new_id`
WHERE `recycling_events`.`printer_id`='$printerId'
ORDER BY `recycling_events`.`id` ASC");

		$return = '
		<fieldset><legend>Информация о принтере</legend>	
		<table class="mainTable zebra">
			<thead>
				<tr>
					<th>id</th>
					<th>Дата</th>
					<th>Вставлен</th>					
					<th>Изъят</th>
					<th>Счётчик</th>					
					<th>Причина</th>
				</tr>
			</thead>
			<tbody>
			';
			$printedPages = 0;
			$plusPage = 0;
		$returnArr = array();
		while($cartridgeReplace = $DB->fetch_assoc($cartridgeReplaces)){
			$plusPage = $cartridgeReplace['pages'] - $printedPages;	
			$returnArr[] = "			
				<tr>
					<td>$cartridgeReplace[id]</td>
					<td>$cartridgeReplace[dt]</td>
					<td><b>$cartridgeReplace[cartridge_in_num]</b> ($cartridgeReplace[cartridge_in_name])</td>					
					<td><b>$cartridgeReplace[cartridge_out_num]</b> ($cartridgeReplace[cartridge_out_name])</td>
					<td>$cartridgeReplace[pages]</td>					
					<td>$cartridgeReplace[reason]".(($plusPage > 0) ? ", отпечатано ".$plusPage." страниц" : '')."</td>
				</tr>";			
			$printedPages = $cartridgeReplace['pages'];
		}
		
		$return .= implode("\n", array_reverse($returnArr));
		$return .= '</tbody></table>';
		return $return;		
	}
	
	
	function portalShowPrinterStatistics($printerId = 0){
		global $DB;
		
		if($printerId == 0) return;
		
		$pages = $DB->fetch_array("SELECT pages FROM `recycling_events` WHERE `recycling_events`.`printer_id`='$printerId' ORDER BY id ASC");
		$dates = $DB->fetch_array("SELECT DATE_FORMAT(FROM_UNIXTIME(`dt`), '%Y-%m-%d %H:%i') AS `dt` FROM `recycling_events` WHERE `recycling_events`.`printer_id`='$printerId' ORDER BY id ASC");
		$printerInfo = $DB->fetch_assoc("SELECT * FROM `recycling_printers` LEFT JOIN `recycling_printers_models` ON `recycling_printers`.`modelId`=`recycling_printers_models`.`id` WHERE `recycling_printers`.`id`='$printerId'");

		$return = '
		<fieldset><legend>Информация о принтере</legend>	
		<div class="tab">
		  <button class="tablinks" onclick="openTab(event, \'statistic\')" id="defaultOpenTab">Статистика</button>
		  <button class="tablinks" onclick="openTab(event, \'cartridgeReplaces\')">Замены картриджей</button>
		  <button class="tablinks" onclick="openTab(event, \'repairs\')">Ремонты</button>		  
		</div>

		<div id="statistic" class="tabcontent">
			<fieldset>
				<figure class="highcharts-figure"><div id="container"></div></figure>
			</fieldset>
			<script>
			document.addEventListener("DOMContentLoaded", function(event) {
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
			});
			</script>'.show_cartridge_replace_form($printerId).'
		</div>

		<div id="cartridgeReplaces" class="tabcontent">'.printer_statistic_rc($printerId).'</div>
		<div id="repairs" class="tabcontent">Нет данных по ремонтам</div>		
		
		</fieldset>
		';		
		return $return;
	}

	function portalShow_GET_breaks(){
		global $DB, $intlFormatter, $portalYesNo;

		$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$intlFormatter->setPattern('d MMMM YYYYг.');
/*
SELECT
`recycling_cartridges`.`inv_num`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_companies`.`company`,
`recycling_companies`.`inn`,
`recycling_printers`.`printerName`,
`recycling_printers`.`companyId`,
`recycling_events_reasons`.`reason`,
`recycling_events`.`dt` AS dt,
`recycling_events`.`id` AS `eventId`,
`a`.`dt` AS dtOut,
`a`.`remark` AS remark,
`recycling_breaks_content`.`breakId`,
`recycling_breaks_content`.`checkId`,
`recycling_checks`.`checkName`,
`recycling_checks`.`isPay`
 FROM `recycling_cartridges`
LEFT JOIN (
	SELECT a.* FROM `recycling_events` AS a
	INNER JOIN (
		SELECT `cartridge_out_id`, MAX(dt) mxdate
		FROM `recycling_events`
		GROUP BY `cartridge_out_id`
		) AS b ON a.cartridge_out_id=b.cartridge_out_id AND a.`dt`=b.mxdate
	ORDER BY id DESC
)
 AS `recycling_events` ON `recycling_events`.`cartridge_out_id`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_printers`.`companyId`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN (
	SELECT dt, remark, event_id FROM `recycling_events_admins`
	 WHERE `event_typ_id`='1'
	 ) AS a ON a.event_id=`recycling_events`.id
LEFT JOIN `recycling_breaks_content` ON `recycling_breaks_content`.`eventId`=`recycling_events`.`id`
LEFT JOIN `recycling_checks` ON `recycling_checks`.`id`=`recycling_breaks_content`.`checkId`
AND `is_paid`='0'
*/



		$eventsQuery = $DB->query("
SELECT 
`recycling_cartridges`.`inv_num`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_companies`.`company`,
`recycling_companies`.`inn`,
`recycling_printers`.`printerName`,
`recycling_printers`.`companyId`,
`recycling_events_reasons`.`reason`,
`recycling_events`.`dt`,
`recycling_events`.`id` AS `eventId`,
`a`.`dt` AS dtOut,
`a`.`remark` AS remark,
`recycling_breaks_content`.`breakId`,
`recycling_breaks_content`.`checkId`,
`recycling_breaks_content`.`worksCount`,
`recycling_checks`.`checkName`,

UNIX_TIMESTAMP(`recycling_checks`.`checkDate`) AS `checkDateU`,

`recycling_checks`.`isPay`
 FROM `recycling_events`
LEFT JOIN `recycling_cartridges` ON `recycling_events`.`cartridge_out_id`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_printers`.`companyId`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN (SELECT dt, remark, event_id FROM `recycling_events_admins` WHERE `event_typ_id`='1') AS a ON a.event_id=`recycling_events`.id
LEFT JOIN `recycling_breaks_content` ON `recycling_breaks_content`.`eventId`=`recycling_events`.`id`
LEFT JOIN `recycling_checks` ON `recycling_checks`.`id`=`recycling_breaks_content`.`checkId`
WHERE
".(($id > 0) ? "
`recycling_breaks_content`.`breakId`='$id'" : "
`recycling_events`.`office_id` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')

AND `recycling_events`.`id` IN (SELECT `eventId` FROM `recycling_breaks_content` WHERE `breakId` IS NULL)")."
 ORDER BY `recycling_printers`.`companyId`, `dt` DESC");
//AND `recycling_cartridges`.`status_id` IN ('1','0')


	
		$statusCompanies = array();
		
		$return = '<form method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>События для разбивки:</legend>
		<input type="hidden" name="breakId" value="'.$id.'">
		<table class="mainTable zebra" id="ajaxedRows">
			<thead>
				<tr>
					<th></th>
					<th>№</th>
					<th>Принтер</th>
					<th>Картридж</th>
					<th>Причина замены</th>					
					<th>Дата замены</th>
					<th>Передан в заправку</th>
					<th>Комментарий</th>					
					'.(($id > 0) ? '<th>Счёт</th><th>Оплачен</th><th>Работы</th>' : '').'
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
					$return .= '<tr class="companyName"><td colspan="'.(($id > 0) ? 11 : 8).'">Для компании '.$pos['company'].', ИНН '.$pos['inn'].'</td></tr>';					
				}else{
					$statusCompanies[$pos['company']]++;					
				}
				$events[] = $pos['eventId'];
				$return .= '
					<tr data-id="'.$pos['eventId'].'" data-target="getWorkCheckboxes">
						<td><input type="checkbox" name="events[]" value="'.$pos['eventId'].'" '.(($id > 0) ? '' : ' checked').'></td>
						<td>'.$statusCompanies[$pos['company']]."</td>
						<td>$pos[printerName]</td>
						<td><b>$pos[inv_num]</b> ($pos[cartridgeName])</td>
						<td>$pos[reason]</td>					
						<td>".$intlFormatter->format($pos['dt'])."</td>
						<td>".$intlFormatter->format($pos['dtOut'])."</td>
						<td>".$pos['remark']."</td>							
						".(($id > 0) ? isset($pos['isPay']) ? "<td>$pos[checkName] от ".$intlFormatter->format($pos['checkDateU'])."</td><td>".$portalYesNo[$pos['isPay']]."</td><td><a href=\"javascript://\" class=\"add-row\">Работы <span id=\"workCount_$pos[eventId]\">".(($pos['worksCount'] > 0) ? '('.$pos['worksCount'].')' : '<span class="warnBlinker">!</span>')."</span></a></td>" : '<td></td><td></td><td></td>' : '')."
					</tr>";
			}
				
		}
		
		if(count($statusCompanies) > 0){		
			$return .= '<tr style="background-color: white !important;"><td colspan="'.(($id > 0) ? 11 : 8).'"><br/>';		
			foreach($statusCompanies as $companyName => $cartridgeCount) $return .= "Компания: $companyName, картриджей: $cartridgeCount<br/>";
			$return .= 'Итого в заправку: '.array_sum($statusCompanies).'</td></tr>';		
		}else{
			$return .= '<tr><td colspan="'.(($id > 0) ? 11 : 8).'" style="text-align: center;"><i>Нет данных</i></td></tr>';
		}		
		$return .= '</tbody></table></fieldset>';
		
		if($id > 0){
			
			$checkVariants = $DB->query("SELECT `recycling_checks`.*, UNIX_TIMESTAMP(`checkDate`) as `checkDateU`, `recycling_companies`.`company`, `recycling_counteragents`.`refCompanyName` FROM `recycling_checks` LEFT JOIN `recycling_counteragents` ON `recycling_counteragents`.`id`=`recycling_checks`.`companyRefId` LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_checks`.`companyId` WHERE `breakId`='$id'");
			$checkVariantsArray = array();
			while($checkVariant = $DB->fetch_assoc($checkVariants))	$checkVariantsArray[$checkVariant['id']] = "$checkVariant[checkName] от ".$intlFormatter->format($checkVariant['checkDateU']).", для $checkVariant[company], получен от $checkVariant[refCompanyName], оплата: ".(($checkVariant['isPay'] == 0) ? 'не оплачен!' : 'оплачен');
			$return .= '<fieldset class="editFormFieldset"><legend>Счета:</legend>';
			if(count($checkVariantsArray) == 0){
				$return .= '<b>Нет доступных счетов.</b><br>Чтобы привязать конкретные события к счетам, нужно вначале добавить, а затем привязать какой-либо счёт к этой разбивке на <a href="?w=payments">странице Счета</a>.';
			}else{
				$return .= 'Связать отмеченное со счетом:<br />'.show_select(0, $checkVariantsArray, 'checkId', true).'<input type="submit" name="cmdAddEventsIntoCheck" value="сохранить"></fieldset>';
			}
			$return .= '</form>';
			
		}else{
			
			if($DB->num_rows($eventsQuery) > 0){
			
				$officesLists = $DB->query("SELECT * FROM `recycling_offices` 
	WHERE id IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
	ORDER BY `recycling_offices`.`id`");
				while($office = $DB->fetch_assoc($officesLists)){
					$officesList[$office['id']] = $office['office'];
				}
			
				$return .= '
				<fieldset class="editFormFieldset"><legend>Создание разбивки:</legend>
					<table>
						<tbody>
						<tr><td>Название</td><td><input type="text" name="breakName" placeholder="Заправка [номер]"></td></tr>						
						<tr><td>Дата</td><td><input type="date" name="breakDate" value="'.date("Y-m-d").'"></td></tr>						
						<tr><td>Офис</td><td>'.show_select(0, $officesList, 'officeId', true).'</td></tr>
						<tr><td colspan="2"><input type="submit" name="cmdAddEventsIntoBreak" value="сохранить"></td></tr>
						</tbody>
					</table>
				</fieldset>';		
			}
			
			$return .= '
			</form>
			<fieldset><legend>Прошлые заправки:</legend>
			<table class="mainTable zebra">
			<thead>
				<tr>
					<th>Название, дата</th>
					<th>Офис</th>
				</tr>
			</thead>
			<tbody>		
		';
		
			$breaksList = $DB->query("SELECT `recycling_breaks`.*, `recycling_offices`.`office` FROM `recycling_breaks` 
LEFT JOIN `recycling_offices` ON `recycling_breaks`.`officeId`=`recycling_offices`.`id`
WHERE `recycling_breaks`.`officeId` IN(SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
ORDER BY breakDate DESC");		

			if($DB->num_rows($breaksList)){
				$intlFormatter->setPattern('d MMMM YYYYг.');
				while($breakContent = $DB->fetch_assoc($breaksList)){
					$return .= "
						<tr>
							<td><a href=\"?w=breaks&amp;id=$breakContent[id]\">$breakContent[breakName] от ".$intlFormatter->format($breakContent['breakDate'])."</a></td>
							<td>$breakContent[office]</td>
						</tr>							
					";
				}		
			}else{
				$return .= '<tr><td colspan="'.(($id > 0) ? 11 : 8).'" style="text-align: center;"><i>Нет данных</i></td></tr>';
			}
		
		$return .= '</tbody>
		</table>
		</fieldset>';

		}
		return $return;
		
	}	
	
	function portalShow_GET_cartridges(){
		global $DB;
		
		$cartridges = $DB->query("SELECT 
`recycling_cartridges_stasuses`.`stasus`,
`recycling_offices`.`office`,
`recycling_printers`.`printerName`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_brands`.`brandName`,
`recycling_cartridges`.*
FROM`recycling_cartridges`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN `recycling_offices` ON `recycling_cartridges`.`office_id`=`recycling_offices`.`id`
LEFT JOIN `recycling_printers` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_brands` ON `recycling_brands`.`id`=`recycling_cartridges`.`brandId`
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
			
			$allowCartridges = $DB->query("SELECT `recycling_printers_cartridges`.`cartridgeModelId`, `recycling_cartridges_models`.`cartridgeName` FROM `recycling_printers_cartridges` LEFT JOIN `recycling_printers` ON `recycling_printers_cartridges`.`printerModelId`=`recycling_printers`.`modelId` LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_printers_cartridges`.`cartridgeModelId` WHERE `officeId`='$_SESSION[officeRegcartridgeId]' AND `recycling_printers`.`statusId`='2' GROUP BY cartridgeModelId");
			while($allowCartridge = $DB->fetch_assoc($allowCartridges)) $allowCartridgeArray[$allowCartridge['cartridgeModelId']] = $allowCartridge['cartridgeName'];			

			$allowBrands = $DB->query("SELECT * FROM `recycling_brands` ORDER BY brandName");
			while($allowBrand = $DB->fetch_assoc($allowBrands)) $allowBrandArray[$allowBrand['id']] = $allowBrand['brandName'];
			
			$return .= '<tr><td>модель:</td><td>'.show_select(0, $allowCartridgeArray, 'cartridgeModelId', true).'</td></tr>
				<tr><td>производитель:</td><td>'.show_select(0, $allowBrandArray, 'brandId', true).'</td></tr>			
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
					<th>Производитель</th>
					<th>Статус картриджа</th>
					<th>Принтер</th>
				</tr>
			</thead>
			<tbody>';
			
			while($cartridge = $DB->fetch_assoc($cartridges)){
				$return .= "
				<tr>
					<td>$cartridge[office]</td>
					<td><a href=\"?cartridgeHistory=$cartridge[id]\"><b>$cartridge[inv_num]</b> ($cartridge[cartridgeName])</a></td>
					<td>$cartridge[brandName]</td>
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

		$allowPrinters = $DB->query("SELECT * FROM `recycling_printers` WHERE `officeId` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') AND `statusId` IN (2,5) ORDER BY printerName");

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

			$lastPagesCnt = $DB->result("SELECT `pages` FROM `recycling_events` WHERE `cartridge_in_id`='$cartridgeInside[cartridge_inside]' AND `printer_id`='$selectPrinter' ORDER BY id DESC LIMIT 1");
		
			$return .= '
			<tr><td>Картридж в принтере:</td><td><input type="hidden" name="cartridge_out_id" value="'.$cartridgeInside['cartridge_inside'].'"><input type="text" value="'.(($cartridgeInside['inv_num'] > 0) ? $cartridgeInside['inv_num'].' ('.$cartridgeInside['cartridgeName'].')' : '(пусто)').'" disabled></td></tr>	
			<tr><td>Картридж для замены:</td><td><select name="cartridge_in_id"><option value="0" selected>-- выберите --</option>';
			while($cartridgeVariant = $DB->fetch_assoc($allowCartridges)) $return .= "<option value=\"$cartridgeVariant[id]\">$cartridgeVariant[inv_num] ($cartridgeVariant[cartridgeName])</option>";			
			$return .= '</select></td></tr>			
			<tr><td>Причина:</td><td><input type="radio" name="reason_id" id="one" value="1" checked>закончился тонер<input type="radio" name="reason_id" id="two" value="2">рекламация</td></tr>
			'.(($lastPagesCnt > 0) ? '<tr><td>Счётчик страниц:</td><td><input type="text" size="10" maxlength="10" value="'.$lastPagesCnt.' (предыдущее значение)" disabled></td></tr>' : '').'
			<tr><td>Счётчик страниц:</td><td><input type="text" name="pages" size="10" maxlength="10" placeholder="текущее значение"></td></tr>
			<tr><td>Примечание:</td><td><input type="text" name="remark"></td></tr>
			<tr><td colspan="2"><input type="submit" name="cmdAddEvent" value="заменить картридж"></td></tr>';
		}		
		$return .= '</tbody></table></fieldset></form>';
		return $return;		
	}
	
	function portalShow_GET_printers(){
		global $DB;
		$return = '';
		
		$printerList = $DB->query("SELECT 
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
		$return .= show_printer_edit_element($printerId);

		$return .= '		
		<fieldset><legend>Принтеры:</legend>		
		<table class="mainTable zebra" id="ajaxedRows">
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
			
			if($DB->num_rows($printerList) > 0){			
				while($row = $DB->fetch_assoc($printerList)){
					$return .= "
						<tr data-id=\"$row[id]\" data-target=\"getPrinterInfoElement\" id=\"printer_$row[id]\" class=\"blinking\">
							<td>$row[id]</td>
							<td>$row[office]</td>
							<td><a href=\"?statistic=printer&id=$row[id]\">$row[printerName]</a></td>
							<td>$row[printer_type_txt]</td>
							<td>$row[company]</td>
							<td>$row[cartrigdesForPrinter]</td>
							<td>".(($row['inv_num'] > 0) ? "<b>$row[inv_num]</b> ($row[cartridge_txt])" : '')."</td>
							<td>".((!empty($row['ip'])) ? '<a href="http://'.$row['ip'].'" target="_blank">'.$row['ip'].'</a>' : '').'</td>
							<td>'.$row['sn'].'</td>						
							<td>'.$row['stasus'].'</td>
							<td><a class="add-row" href="javascript://">изм.</a></td>
						</tr>
					';			
				}
			}else{
				$return .= '<tr><td colspan="11" style="text-align: center;"><i>нет записей</i></td></tr>';				
			}
		
			$return .= '
			<tr><td colspan="11" style="text-align: right;">[<a href="?w=printers&new">Добавить принтер</a>]</td></tr>			
		</tbody>
		</table>
		</fieldset>';		
		return $return;		
	}
	

	function portalShow_GET_journal($printerId = 0){
		global $DB, $intlFormatter, $portalYesNo;
		$return = '';		
		if($printerId == 0)	$return .= show_cartridge_replace_form($printerId);	
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
`recycling_checks`.`isPay`,
recycling_events.*
 FROM recycling_events
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_events_stasuses` ON `recycling_events`.`status_id`=`recycling_events_stasuses`.`id`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN `recycling_breaks_content` ON `recycling_breaks_content`.`eventId`=`recycling_events`.`id`
LEFT JOIN `recycling_checks` ON `recycling_checks`.`id`=`recycling_breaks_content`.`checkId`
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
ORDER BY dt DESC LIMIT $_SESSION[journalEvents]");

		while ($row = $DB->fetch_assoc($journalQuery)){
			$return .= '		
			<tr id="event_'.$row['id'].'" class="blinking">
				<td>'.$row['id'].'</td>
				<td><a href="?statistic=printer&id='.$row['printer_id'].'">'.$row['printerName'].'</a></td>
				<td>'.(($row['cartridge_new_name']) ? '' : "<a href=\"?cartridgeHistory=$row[cartridge_out_id]\"><b>$row[cartridge_out_num]</b> ($row[cartridge_out_name])</a>").'</td>				
				<td>'.(($row['cartridge_new_name']) ? "<a href=\"?cartridgeHistory=$row[cartridge_new_id]\"><b>$row[cartridge_new_num]</b> ($row[cartridge_new_name])</a>" : "<a href=\"?cartridgeHistory=$row[cartridge_in_id]\"><b>$row[cartridge_in_num]</b> ($row[cartridge_in_name])</a>")."</td>
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
				<td align="center">'.(isset($row['isPay']) ? $portalYesNo[$row['isPay']] : '').'</td>
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