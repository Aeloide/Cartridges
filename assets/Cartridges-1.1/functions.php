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
`recycling_counteragents`.`company`,
`recycling_cartridges_worknames`.`workName`,
`recycling_breaks_content`.`checkId`,
`recycling_breaks_content`.`breakId`,
`recycling_breaks`.`breakName`,
`recycling_breaks`.`breakDate`
FROM `recycling_cartridges_works`
LEFT JOIN `recycling_events` ON `recycling_events`.`id`=`recycling_cartridges_works`.`eventId`
LEFT JOIN `recycling_cartridges` ON `recycling_cartridges`.`id`=`recycling_events`.`cartridge_out_id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
LEFT JOIN `recycling_breaks_content` ON `recycling_breaks_content`.`eventId`=`recycling_events`.`id`
LEFT JOIN `recycling_breaks` ON `recycling_breaks`.`id`=`recycling_breaks_content`.`breakId`
LEFT JOIN `recycling_checks` ON `recycling_checks`.`id`=`recycling_breaks_content`.`checkId`
LEFT JOIN `recycling_cartridges_worknames` ON `recycling_cartridges_worknames`.`id`=`recycling_cartridges_works`.`workId`
LEFT JOIN (SELECT * FROM `recycling_companies` WHERE `companyType`='2') AS `recycling_counteragents` ON `recycling_counteragents`.`id`=`recycling_checks`.`companyRefId`
WHERE `recycling_cartridges`.`id`='$cartridgeId'
GROUP BY `eventId`
ORDER BY `recycling_checks`.`checkDateAdded` DESC");

		$return = '<fieldset><legend>История работ с картриджем:</legend>
				<table class="mainTable zebra">
					<thead>
						<tr>
							<th>Картридж</th>
							<th>Работы</th>
							<th>Разбивка</th>							
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
						<td><a href=\"?w=breaks&id=$cartridgeEvent[breakId]\">$cartridgeEvent[breakName] от ".$intlFormatter->format($cartridgeEvent['breakDate'])."</a></td>						
						<td><a href=\"?getCheck=$cartridgeEvent[checkId]\">$cartridgeEvent[checkName] от ".$intlFormatter->format($cartridgeEvent['checkDateU'])."</a></td>
						<td>$cartridgeEvent[company]</td>									
						<td>".$portalYesNo[$cartridgeEvent['isPay']]."</td>							
					</tr>";			
			}
		}else{
			$return .= '<tr><td colspan="5"><i>Нет данных</i></td></tr>';				
		}

		$return .= '</tbody></table></fieldset>';
				
		
		$useEvents = $DB->query("SELECT
`recycling_events`.`id`,
`recycling_events`.`dt`,
`recycling_printers`.`printerName`,
`recycling_offices`.`officeName`,
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
					<tbody>';

		if($DB->num_rows($useEvents) > 0){		
			while($cartridgeEvent = $DB->fetch_assoc($useEvents)){
				$return .= "
						<tr>
							<td>$cartridgeEvent[id]</td>
							<td><b>$cartridgeEvent[inv_num]</b> ($cartridgeEvent[cartridgeName])</td>
							<td>$cartridgeEvent[printerName]</td>
							<td>".$intlFormatter->format($cartridgeEvent['dt'])."</td>
							<td>$cartridgeEvent[officeName]</td>				
						</tr>";			
			}
		}else{
			$return .= '<tr><td colspan="4"><i>Нет данных</i></td></tr>';				
		}

		$return .= '</tbody></table></fieldset>';		
		return $return;
	}

	function portalShow_GET_options(){
		global $DB, $portalCompanyTypes;

		$return = '<fieldset><legend>Авторизация:</legend>';
		
		if(isset($_SESSION['adminId'])){
			$return .= 'Авторизован как: <b>'.$DB->result("SELECT adminName FROM `recycling_admins` WHERE `id`='$_SESSION[adminId]'").'</b> [<a href="?quit">выход</a>]<br/>
			<form name="showParams">
				<input type="checkbox">Не показывать принтеры в статусе "выведен из оборота"<br/>
				<input type="checkbox">Не показывать картриджи в статусе "выведен из оборота"			
			</form>
			'; 
		}else{
			$adminsListArray = array();
			$adminsList = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");
			if($DB->num_rows($adminsList) == 0){				
				$salt = md5(time());
				$DB->query("INSERT INTO `recycling_admins`(`adminName`,`pass`,`salt`,`datereg`,`superAdm`) VALUES ('admin','".passHash('admin', $salt)."','$salt',NOW(),'1')");
				$adminsList = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");				
			}
			while($admin = $DB->fetch_assoc($adminsList)) $adminsListArray[$admin['id']] = $admin['adminName'];	
			
			$return .= '
				<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'">
				<table class="mainTable" style="background-color: lightpink; padding: 10px;">
					<thead>
						<tr>
							<th>Фамилия</th>
							<th>Пароль</th>
						</tr>
					</thead>
					<tbody>
						<tr><td>'.show_select(0, $adminsListArray, 'adminsAuthId').'</td><td><input type="password" name="adminsAuthPass"></td></tr>
						<tr><td colspan="2" align="center"><input type="submit" name="cmdAuthAdmin" value="авторизация"></td></tr>
					</tbody>
				</table>
				</form>
			</div>
			';
		}
	
		$return .= '</fieldset>';
			
		if(isset($_SESSION['adminId'])){

			$return .= '
			<fieldset><legend>Админы:</legend>
			<table class="mainTable zebra" id="ajaxedRows">
				<thead>
					<tr>
						<th>Фамилия</th>
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
			
			$adminsQuery = $DB->query("SELECT INET_NTOA(`lastip`) AS lastip, `recycling_admins`.`adminName`, `recycling_admins`.`id`, `recycling_admins`.`datereg`, `recycling_admins`.`datelogin`,`recycling_admins`.`superAdm`,`recycling_admins`.`adminStatus`,eventsList.eventsCount
FROM `recycling_admins`
LEFT JOIN (SELECT COUNT(*) AS eventsCount, admin_id FROM `recycling_events_admins` GROUP BY admin_id) AS eventsList ON eventsList.admin_id=`recycling_admins`.`id`
ORDER BY adminName");			
			while($pos = $DB->fetch_assoc($adminsQuery)){
				$return .= "
					<tr data-id=\"$pos[id]\" data-target=\"getAdminEditElement\" id=\"adm_$pos[id]\" class=\"blinking\">
						<td><a href=\"javascript://\" class=\"add-row\">$pos[adminName]</a></td>
						<td>$pos[eventsCount]</td>
						<td>$pos[datereg]</td>
						<td>$pos[datelogin]</td>
						<td>$pos[lastip]</td>
						<td>".(($pos['superAdm'] == 1) ? 'Да' : '')."</td>
						<td>".(($pos['adminStatus'] == 1) ? 'Активен' : 'Отключен')."</td>
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
			<table class="mainTable zebra" id="ajaxedRows">
				<thead>
					<tr>
						<th>Офис</th>
						<th>Событий</th>
						<th>Принтеров</th>	
						<th>Картиджей</th>
						<th>Компаний</th>						
						<th>Админы</th>
					</tr>
				</thead>
				<tbody>';

			$officeQuery = $DB->query("SELECT `recycling_offices`.*, adminName,
events.countEvents,
printers.countPrinters ,
cartridges.countCartridges,
compaines.countCompanies
FROM `recycling_offices`
LEFT JOIN (SELECT GROUP_CONCAT(adminName SEPARATOR ', ') AS adminName, office_id FROM `recycling_admins_offices` LEFT JOIN `recycling_admins` ON `recycling_admins_offices`.`admin_id`=`recycling_admins`.`id` GROUP BY office_id) AS `names` ON `names`.`office_id`=`recycling_offices`.`id`

LEFT JOIN (SELECT COUNT(*) AS countPrinters, officeId FROM `recycling_printers` GROUP BY officeId) AS `printers` ON printers.officeId=`recycling_offices`.`id`
LEFT JOIN (SELECT COUNT(*) AS countCartridges, office_id FROM `recycling_cartridges` GROUP BY office_id) AS `cartridges` ON cartridges.office_id=`recycling_offices`.`id`
LEFT JOIN (SELECT COUNT(*) AS countCompanies, officeId FROM `recycling_companies` GROUP BY officeId) AS `compaines` ON compaines.officeId=`recycling_offices`.`id`
LEFT JOIN (SELECT COUNT(*) AS countEvents, office_id FROM `recycling_events` GROUP BY office_id) AS `events` ON events.office_id=`recycling_offices`.`id`

ORDER BY `id`");

			if($DB->num_rows($officeQuery) > 0){
				while($pos = $DB->fetch_assoc($officeQuery)){
					$return .= "
						<tr data-id=\"$pos[id]\" data-target=\"getOfficeEditElement\" id=\"office_$pos[id]\" class=\"blinking\">
							<td><a href=\"javascript://\" class=\"add-row\">$pos[officeName]</a></td>
							<td>$pos[countEvents]</td>							
							<td>$pos[countPrinters]</td>	
							<td>$pos[countCartridges]</td>	
							<td>$pos[countCompanies]</td>								
							<td>$pos[adminName]</td>						
						</tr>";
				}				
			}else{
				$return .= '<tr><td colspan="6">Нет данных.</td></tr>';				
			}							
			
			$return .= '
				<tr data-id="new" data-target="getOfficeEditElement"><td colspan="6" style="text-align:right;">[<a href="javascript://" class="add-row">Добавить офис</a>]</td></tr>
				</tbody>
			</table>			
			</fieldset>			
			
			<fieldset><legend>Компании:</legend>		
			<table class="mainTable zebra" id="ajaxedRows">
				<thead>
					<tr>
						<th>Компания</th>
						<th>ИНН</th>
						<th>Тип</th>						
						<th>Офис</th>
						<th>Админы</th>
					</tr>
				</thead>
				<tbody>		
			';
			
			$companyQuery = $DB->query("SELECT `recycling_companies`.`id`, `recycling_companies`.`companyType`, `recycling_companies`.`company`, `recycling_companies`.`inn`, `recycling_offices`.`officeName`, names.adminName FROM `recycling_companies`
LEFT JOIN `recycling_offices` ON `recycling_companies`.`officeId`=`recycling_offices`.`id`
LEFT JOIN (
 SELECT GROUP_CONCAT(adminName SEPARATOR ', ') AS adminName, office_id FROM `recycling_admins_offices`
 LEFT JOIN `recycling_admins` ON `recycling_admins_offices`.`admin_id`=`recycling_admins`.`id` GROUP BY office_id) AS `names` ON `names`.`office_id`=`recycling_companies`.`officeId`
ORDER BY `recycling_companies`.`companyType`, `recycling_companies`.`officeId`, `company`");			
			
			if($DB->num_rows($companyQuery) > 0){			
				while($pos = $DB->fetch_assoc($companyQuery)){
					$return .= "
						<tr data-id=\"$pos[id]\" data-target=\"getCompanyEditElement\">
							<td><a href=\"javascript://\" class=\"add-row\">$pos[company]</a></td>
							<td>$pos[inn]</td>							
							<td>".$portalCompanyTypes[$pos['companyType']]."</td>
							<td>$pos[officeName]</td>
							<td>$pos[adminName]</td>						
						</tr>";
				}
			}else{
				$return .= '<tr><td colspan="5">Нет данных.</td></tr>';				
			}
			
			$return .= '
				<tr data-id="new" data-target="getCompanyEditElement"><td colspan="5" style="text-align:right;">[<a href="javascript://" class="add-row">Добавить компанию</a>]</td></tr>				
				</tbody>
			</table>
			</fieldset>';			

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
`recycling_counteragents`.`company` AS `refCompanyName`,
`recycling_counteragents`.`inn` AS `refInn`,
`recycling_breaks`.`breakName`
 FROM `recycling_checks`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_checks`.`companyId`
LEFT JOIN (SELECT * FROM `recycling_companies` WHERE `companyType`='2') AS `recycling_counteragents` ON `recycling_counteragents`.`id`=`recycling_checks`.`companyRefId`
LEFT JOIN `recycling_breaks` ON `recycling_breaks`.`id`=`recycling_checks`.`breakId`
WHERE `recycling_checks`.`companyId` IN(SELECT `id` FROM `recycling_companies` WHERE `officeId` IN ('".implode("','", array_keys($_SESSION['myOfficesList']))."'))
ORDER BY `recycling_checks`.`id`");
		
		$return = '<fieldset>
				<table class="mainTable zebra" id="ajaxedRows">
				<caption>Счета</caption>			
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
						<tr data-id=\"$check[id]\" data-target=\"getCheckChangeElement\" id=\"check_$check[id]\" class=\"blinking\">
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
				$return .= '<tr><td colspan="8" style="text-align: center;"><i>Пока нет счетов</i></td></tr>';
			}
		
			$return .= '
					<tr data-id="new" data-target="getCheckChangeElement"><td colspan="8" style="text-align: right;">[<a href="javascript://" class="add-row">Добавить новый счёт</a>]</td></tr>			
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

		$eventsQuery = $DB->query("
SELECT 
`recycling_cartridges`.`inv_num`,
`recycling_cartridges`.`id` as `caId`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_companies`.`company`,
`recycling_companies`.`inn`,
`recycling_printers`.`printerName`,
`recycling_events_reasons`.`reason`,
`recycling_events`.`dt`,
`recycling_events`.`id` AS `eventId`,
`b`.`dt` AS dtOut,
`a`.`remark` AS remark,
`recycling_breaks_content`.`breakId`,
`recycling_breaks_content`.`checkId`,
`recycling_breaks_content`.`worksCount`,
`recycling_breaks_content`.`companyId`,
`recycling_checks`.`checkName`,
UNIX_TIMESTAMP(`recycling_checks`.`checkDate`) AS `checkDateU`,
`recycling_checks`.`isPay`
 FROM `recycling_events`
LEFT JOIN `recycling_cartridges` ON `recycling_events`.`cartridge_out_id`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_cartridges`.`cartridge_model_id`
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN (SELECT remark, event_id FROM `recycling_events_admins` WHERE `event_typ_id`='0') AS a ON a.event_id=`recycling_events`.id
LEFT JOIN (SELECT dt, event_id FROM `recycling_events_admins` WHERE `event_typ_id`='1') AS b ON b.event_id=`recycling_events`.id
LEFT JOIN `recycling_breaks_content` ON `recycling_breaks_content`.`eventId`=`recycling_events`.`id`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_breaks_content`.`companyId`
LEFT JOIN `recycling_checks` ON `recycling_checks`.`id`=`recycling_breaks_content`.`checkId`
WHERE ".(($id > 0) ? "`recycling_breaks_content`.`breakId`='$id'" : "`recycling_events`.`office_id` IN ('".implode("','", array_keys($_SESSION['myOfficesList']))."')
AND `recycling_events`.`id` IN (SELECT `eventId` FROM `recycling_breaks_content` WHERE `breakId` IS NULL)")."
 ORDER BY `recycling_breaks_content`.`companyId`, `dt` DESC");
 
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
		
		$statusCompanies = array();
	
		if($DB->num_rows($eventsQuery) > 0){
			$companyId = 0;
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
						<td><a href=\"?cartridgeHistory=$pos[caId]\" target=\"_blank\"><b>$pos[inv_num]</b> ($pos[cartridgeName])</a></td>
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
			
			$checkVariants = $DB->query("SELECT
			`recycling_checks`.*, UNIX_TIMESTAMP(`checkDate`) as `checkDateU`, `recycling_companies`.`company`, `recycling_counteragents`.`company` AS `refCompanyName` 
			FROM `recycling_checks` 
			LEFT JOIN (SELECT * FROM `recycling_companies` WHERE `companyType`='2') AS `recycling_counteragents` ON `recycling_counteragents`.`id`=`recycling_checks`.`companyRefId` 
			LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_checks`.`companyId` 
			WHERE `breakId`='$id'");
			$checkVariantsArray = array();
			while($checkVariant = $DB->fetch_assoc($checkVariants))	$checkVariantsArray[$checkVariant['id']] = "$checkVariant[checkName] от ".$intlFormatter->format($checkVariant['checkDateU']).", для $checkVariant[company], получен от $checkVariant[refCompanyName], оплата: ".(($checkVariant['isPay'] == 0) ? 'не оплачен!' : 'оплачен');
			$return .= '<fieldset class="editFormFieldset"><legend>Счета:</legend>';
			if(count($checkVariantsArray) == 0){
				$return .= '<b>Нет доступных счетов.</b><br>Чтобы привязать конкретные события к счетам, нужно вначале добавить, а затем привязать какой-либо счёт к этой разбивке на <a href="?w=payments">странице Счета</a>.';
			}else{
				$return .= 'Связать отмеченное со счетом:<br />'.show_select(0, $checkVariantsArray, 'checkId').'<input type="submit" name="cmdAddEventsIntoCheck" value="сохранить"></fieldset>';
			}
			$return .= '</form>';
			
		}else{
			
			if($DB->num_rows($eventsQuery) > 0){
				$return .= '
				<fieldset class="editFormFieldset"><legend>Создание разбивки:</legend>
					<table>
						<tbody>
						<tr><td>Название</td><td><input type="text" name="breakName" placeholder="Заправка [номер]"></td></tr>						
						<tr><td>Дата</td><td><input type="date" name="breakDate" value="'.date("Y-m-d").'"></td></tr>						
						<tr><td>Офис</td><td>'.show_select(0, $_SESSION['myOfficesList'], 'officeId').'</td></tr>
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
		
			$breaksList = $DB->query("SELECT `recycling_breaks`.*, `recycling_offices`.`officeName`, `a`.`breakId` AS `mark` FROM `recycling_breaks` 
LEFT JOIN `recycling_offices` ON `recycling_breaks`.`officeId`=`recycling_offices`.`id`
LEFT JOIN (SELECT breakId FROM `recycling_breaks_content` WHERE checkId IS NULL GROUP BY breakId) AS a ON a.breakId=recycling_breaks.id
WHERE `recycling_breaks`.`officeId` IN('".implode("','", array_keys($_SESSION['myOfficesList']))."')
ORDER BY breakDate DESC");		

			if($DB->num_rows($breaksList)){
				$intlFormatter->setPattern('d MMMM YYYYг.');
				while($breakContent = $DB->fetch_assoc($breaksList)){
					$return .= "
						<tr>
							<td><a href=\"?w=breaks&amp;id=$breakContent[id]\">$breakContent[breakName] от ".$intlFormatter->format($breakContent['breakDate'])."</a>".(($breakContent['mark'] > 0) ? ' <span class="warnBlinker">!</span>' : '')."</td>
							<td>$breakContent[officeName]</td>
						</tr>							
					";
				}		
			}else{
				$return .= '<tr><td colspan="2" style="text-align: center;"><i>Нет данных</i></td></tr>';
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
`recycling_offices`.`officeName`,
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
WHERE `recycling_cartridges`.`office_id` IN ('".implode("','", array_keys($_SESSION['myOfficesList']))."')
ORDER BY `recycling_cartridges`.`office_id`, status_id, id");

		$allowOfficesArray = array();
		$allowOffices = $DB->query("SELECT `recycling_offices`.* FROM `recycling_offices` LEFT JOIN `recycling_admins_offices` ON `recycling_offices`.`id`=`recycling_admins_offices`.`office_id` WHERE `recycling_admins_offices`.`admin_id`='$_SESSION[adminId]'");
		while($allowOffice = $DB->fetch_assoc($allowOffices)) $allowOfficesArray[$allowOffice['id']] = $allowOffice['officeName'];

		$return = '<form name="formRegCartridge" method="post" action="'.$_SERVER['PHP_SELF'].'">	
		<fieldset class="editFormFieldset">
		<table>
			<caption>Регистрация нового картриджа:</caption>
			<tbody>		
				<tr><td>новый картридж в офис:</td><td>'.show_select($_SESSION['officeRegcartridgeId'], $allowOfficesArray, ['name' => 'officeRegcartridgeId', 'onChange' => 'formRegCartridge.submit();']).'</td></tr>';	

		if((int)$_SESSION['officeRegcartridgeId'] > 0){
			
			$allowCartridges = $DB->query("SELECT `recycling_printers_cartridges`.`cartridgeModelId`, `recycling_cartridges_models`.`cartridgeName` FROM `recycling_printers_cartridges` LEFT JOIN `recycling_printers` ON `recycling_printers_cartridges`.`printerModelId`=`recycling_printers`.`modelId` LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_printers_cartridges`.`cartridgeModelId` WHERE `officeId`='$_SESSION[officeRegcartridgeId]' AND `recycling_printers`.`statusId`='2' GROUP BY cartridgeModelId");
			while($allowCartridge = $DB->fetch_assoc($allowCartridges)) $allowCartridgeArray[$allowCartridge['cartridgeModelId']] = $allowCartridge['cartridgeName'];			

			$allowBrands = $DB->query("SELECT * FROM `recycling_brands` ORDER BY brandName");
			while($allowBrand = $DB->fetch_assoc($allowBrands)) $allowBrandArray[$allowBrand['id']] = $allowBrand['brandName'];
			
			$return .= '<tr><td>модель:</td><td>'.show_select(0, $allowCartridgeArray, 'cartridgeModelId').'</td></tr>
				<tr><td>производитель:</td><td>'.show_select(0, $allowBrandArray, 'brandId').'</td></tr>			
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
					<td>$cartridge[officeName]</td>
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

		$allowPrinters = $DB->query("SELECT * FROM `recycling_printers` WHERE `officeId` IN ('".implode("','", array_keys($_SESSION['myOfficesList']))."') AND `statusId` IN (2,5) ORDER BY printerName");
		$allowPrinterArray = array();
		while($allowPrinter = $DB->fetch_assoc($allowPrinters)) $allowPrinterArray[$allowPrinter['id']] = $allowPrinter['printerName'];
		
		$selectPrinter = ($printerId > 0) ? $printerId : (($_SESSION['printerRCId'] > 0) ? $_SESSION['printerRCId'] : 0);
		
		$return = '
		<form name="formChangeCartridge" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<input type="hidden" value="'.$selectPrinter.'" name="printerId">
		<fieldset class="editFormFieldset">
		<table>
			<caption>Замена картриджа:</caption>	
			<tbody>
			<tr><td>Принтер:</td><td>'.show_select($selectPrinter, $allowPrinterArray, ['name' => 'printerRCId', 'onChange' => 'formChangeCartridge.submit();', 'disabled' => (($printerId > 0) ? true : false)]).'</td>
		</tr>';
		
		if($selectPrinter > 0){
			
			$hasColor = $DB->result("SELECT hasColor FROM `recycling_printers`
LEFT JOIN `recycling_printers_models` ON `recycling_printers_models`.`id`=`recycling_printers`.`modelId`
 WHERE `recycling_printers`.`id`='$selectPrinter'");		
		
			$cartridgeInside = $DB->fetch_assoc("SELECT 
`recycling_printers`.`cartridge_inside`,
`recycling_cartridges_models`.`cartridgeName`,
`recycling_cartridges`.`inv_num` 
FROM recycling_printers 
LEFT JOIN `recycling_cartridges` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
WHERE `recycling_printers`.`id`='$selectPrinter'");

			$allowCartridgesArray = array();
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
			while($cartridgeVariant = $DB->fetch_assoc($allowCartridges)) $allowCartridgesArray[$cartridgeVariant['id']] = "$cartridgeVariant[inv_num] ($cartridgeVariant[cartridgeName])";
			
			$lastPagesCnt = $DB->result("SELECT `pages` FROM `recycling_events` WHERE `cartridge_in_id`='$cartridgeInside[cartridge_inside]' AND `printer_id`='$selectPrinter' ORDER BY id DESC LIMIT 1");
		
			$return .= '
			<tr><td>Картридж в принтере:</td><td><input type="hidden" name="cartridge_out_id" value="'.$cartridgeInside['cartridge_inside'].'"><input type="text" value="'.(($cartridgeInside['inv_num'] > 0) ? $cartridgeInside['inv_num'].' ('.$cartridgeInside['cartridgeName'].')' : '(пусто)').'" disabled></td></tr>	
			<tr><td>Картридж для замены:</td><td>'.show_select(0, $allowCartridgesArray, 'cartridge_in_id').'</td></tr>			
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
		
		$printerList = $DB->query("SELECT 
 `recycling_printers_stasuses`.`stasus`,
 `recycling_companies`.`company`,
 `recycling_offices`.`officeName`,
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
 WHERE `recycling_printers`.`officeId` IN ('".implode("','", array_keys($_SESSION['myOfficesList']))."')
  ORDER BY officeName, statusId DESC, companyId, printerName");

		$return = '		
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
							<td>$row[officeName]</td>
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
			<tr data-id="new" data-target="getPrinterInfoElement"><td colspan="11" style="text-align: right;">[<a class="add-row" href="javascript://">Добавить принтер</a>]</td></tr>			
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
	SELECT GROUP_CONCAT(CONCAT( DATE_FORMAT( FROM_UNIXTIME(`recycling_events_admins`.`dt`), '%Y-%m-%d %H:%i'),' - ',`recycling_admins`.`adminName`,': ',`recycling_events_stasuses`.`stasus`, ' <i>', `recycling_events_admins`.`remark`,'</i>') SEPARATOR '<br/>') AS event_txt, recycling_events_admins.`event_id` FROM recycling_events_admins
		LEFT JOIN `recycling_events_stasuses` ON `recycling_events_admins`.`event_typ_id`=`recycling_events_stasuses`.`id`
		LEFT JOIN `recycling_admins` ON `recycling_events_admins`.`admin_id`=`recycling_admins`.`id`
	 GROUP BY event_id ORDER BY `recycling_events_admins`.`id`
) AS event_txt ON `event_txt`.`event_id`=`recycling_events`.`id`
WHERE `recycling_events`.`office_id` IN ('".implode("','", array_keys($_SESSION['myOfficesList']))."')
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

?>