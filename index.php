<?php
	require("global.php");

	$_SESSION['journalEvents'] = isset($_SESSION['journalEvents']) ? $_SESSION['journalEvents'] : 100;

	$pageMenu = array(
		'journal' => array('title' => 'Журнал', 'head' => 'Журнал движения картриджей'),
		'printers' => array('title' => 'Принтеры', 'head' => 'Список принтеров'),
		'cartridges' => array('title' => 'Картриджи', 'head' => 'Список картриджей'),
		'breaks' => array('title' => 'Разбивки', 'head' => 'Разбивка счетов по компаниям'),
		'payments' => array('title' => 'Счета', 'head' => 'Выставленные счета на оплату'),
		'options' => array('title' => 'Опции', 'head' => 'Настройки и опции отображения')
	);
	
	
	if($_SERVER['REQUEST_METHOD'] == 'POST'){
		$pages = isset($_POST['pages']) ? (int)$_POST['pages'] : 0;
		$_SESSION['printerId'] = isset($_POST['printer_id']) ? $_POST['printer_id'] : $_SESSION['printerId'];
		$_SESSION['printerRegcartridgeId'] = isset($_POST['printerRegcartridgeId']) ? $_POST['printerRegcartridgeId'] : $_SESSION['printerRegcartridgeId'];

		foreach(array('dt', 'cartridge_out_id', 'cartridge_in_id', 'reason_id', 'printer_id', 'remark') as $k) {
			if(isset($_POST[$k])) $$k = $_POST[$k];
		}

		$dt = isset($dt) ? $dt : date("Y-m-d H:i:s");
	
		if (isset($_POST['cmdAddEvent'])){
			$officeId = $DB->result("SELECT office_id FROM `recycling_printers` WHERE `id`='$printer_id'");
			// создание события (замена картриджа)
			$DB->query("INSERT INTO recycling_events ( `office_id`, `printer_id`, `dt`, `cartridge_out_id`, `cartridge_in_id`, `reason_id`, `pages` ) VALUES ( '$officeId', '$printer_id', '$dt', '$cartridge_out_id', '$cartridge_in_id', '$reason_id', '$pages' )");
			$event_id = $DB->result("SELECT id FROM recycling_events WHERE `office_id`='$officeId' AND `printer_id`='$printer_id' AND `cartridge_out_id`='$cartridge_out_id' AND `cartridge_in_id`='$cartridge_in_id' ORDER BY id DESC LIMIT 1");
			$DB->query("INSERT INTO recycling_events_admins ( `event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ( '$event_id', '$dt', '$_SESSION[adminId]', '0', '".$DB->escape($remark)."' )");
			$DB->query("UPDATE recycling_cartridges SET `status_id`='0' WHERE `id`='$cartridge_out_id'");
			$DB->query("UPDATE recycling_cartridges SET `status_id`='4' WHERE `id`='$cartridge_in_id'");
			$DB->query("UPDATE recycling_printers SET `cartridge_inside`='$cartridge_in_id' WHERE `id`='$printer_id'");

		} elseif (isset($_POST['cmdAddCartridge'])){
			// Добавление картриджа
			foreach (array('cartridgeAdd', 'printerRegcartridgeId', 'cartridgeInvNum', 'reason_new_id') as $k) $$k = $_POST[$k];
			$officeId = $DB->result("SELECT `office_id` FROM `recycling_printers` WHERE `id`='$printerRegcartridgeId'");
			if($officeId == 0) exit;

			if ($cartridgeInvNum == 0) $cartridgeInvNum = $DB->result("SELECT MAX(inv_num) + 1 FROM recycling_cartridges WHERE `office_id`='$officeId'");
			$DB->query("INSERT INTO recycling_cartridges (`cartridge`, `office_id`, `inv_num`, `status_id`) VALUES ('$cartridgeAdd', '$officeId', '$cartridgeInvNum', '2')");
			$cartridge_new_id = $DB->result("SELECT `id` FROM `recycling_cartridges` WHERE `cartridge`='$cartridgeAdd' AND `office_id`='$officeId' AND `inv_num`='$cartridgeInvNum'");
			$DB->query("INSERT INTO recycling_events ( `office_id`, `dt`, `cartridge_new_id`, `reason_id`, `status_id` ) VALUES ('$officeId', '$dt', '$cartridge_new_id', '$reason_new_id', '5')");
			$event_id = $DB->result("SELECT id FROM recycling_events WHERE `office_id`='$officeId' AND `dt`='$dt' AND `cartridge_new_id`='$cartridge_new_id' ORDER BY id DESC LIMIT 1");
			$DB->query("INSERT INTO recycling_events_admins (`event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ('$event_id', '$dt', '$_SESSION[adminId]', '5', '".$DB->escape($remark)."')");
		} elseif (isset($_POST['cmdPay'])) {
			$event_id = key($_POST['cmdPay']);
			$DB->query("UPDATE recycling_events SET `is_paid`='1' WHERE `id`='$event_id'");
			$DB->query("INSERT INTO recycling_events_admins ( `event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ( '$event_id', '$dt', '$_SESSION[adminId]', '4', '".$DB->escape($remark)."' )");
		} elseif (isset($_POST['cmdEvent'])){
			$status_id = key($_POST['cmdEvent']);
			$event_id = key($_POST['cmdEvent'][$status_id]);
			$DB->query("UPDATE recycling_events SET `status_id`='$status_id' WHERE `id`='$event_id'");
			$DB->query("INSERT INTO recycling_events_admins ( `event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ( '$event_id', '$dt', '$_SESSION[adminId]', '$status_id', '".$DB->escape($remark)."' )");
			$DB->query("UPDATE recycling_cartridges SET `status_id`='$status_id' WHERE `id`=(SELECT cartridge_out_id FROM recycling_events WHERE `id`='$event_id')");
		} elseif (isset($_POST['cmdAuthAdmin'])){
			$adminId = (int)$_POST['adminsAuthId'];
			$adminPass = md5($_POST['adminsAuthPass']);
			if($DB->num_rows("SELECT * FROM `recycling_admins` WHERE `id`='$adminId' AND `pass`='$adminPass'") > 0) $_SESSION['adminId'] = $adminId;
		}
		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit;
	}elseif($_SERVER['REQUEST_METHOD'] == 'GET'){
		if(isset($_GET['quit'])){
			session_destroy();
			header("Location: ".$_SERVER['HTTP_REFERER']);
			exit;
		}	
		$content = 'Страница не найдена';		
		$contentFunction = isset($_GET['w']) ? $_GET['w'] : 'journal';		
		if(function_exists("portalShow_GET_$contentFunction")){
			$pageMenu[$contentFunction]['active'] = true;
			$content = '<fieldset><legend>Ошибка</legend>Требуется авторизация на странице "<a href="?w=options">Опции</a>"</fieldset>';			
			if(isset($_SESSION['adminId']) or $contentFunction == 'options') $content = call_user_func("portalShow_GET_$contentFunction");
		}
		
	}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $pageMenu[$contentFunction]['head']; ?></title>
<meta http-equiv="pragma" content="no-cache">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo $pageMenu[$contentFunction]['head']; ?>">
<meta property="og:site_name" content="Учет картриджей и принтеров" />
<meta property="og:description" content="<?php echo $pageMenu[$contentFunction]['head']; ?>">
<!--
<meta property="og:url" content="<?php //print $SiteURL; ?>">
<meta property="og:image" content="<?php //print $SiteURL.$PageImage; ?>" />
-->
<link href="main.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body>

<fieldset>
<legend>Меню:</legend>
<div style="padding: 5px;">
	<?php
	foreach($pageMenu as $menuElement => $value) $elements[] = ((isset($value['active'])) ? "<b>$value[title]</b>" : '<a href="?w='.$menuElement.'">'.$value['title'].'</a>');
	echo implode(' | ', $elements);
	?>
</div>
</fieldset>

<?php

	print $content;
	
	function portalShow_GET_options(){
		global $DB;
		
		$companyQuery = $DB->query("SELECT `recycling_companies`.`company`, `recycling_offices`.`office`, names.fio FROM `recycling_companies`
LEFT JOIN `recycling_offices` ON `recycling_companies`.`office_id`=`recycling_offices`.`id`
LEFT JOIN (
 SELECT GROUP_CONCAT(fio SEPARATOR ', ') AS fio, office_id FROM `recycling_admins_offices`
 LEFT JOIN `recycling_admins` ON `recycling_admins_offices`.`admin_id`=`recycling_admins`.`id`) AS `names` ON `names`.`office_id`=`recycling_companies`.`office_id`
ORDER BY `recycling_companies`.`office_id`, company");

		$return = '<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>Авторизация:</legend>';

		if(isset($_SESSION['adminId'])){
			$return .= 'Авторизован как: <b>'.$DB->result("SELECT fio FROM `recycling_admins` WHERE `id`='$_SESSION[adminId]'").'</b> [<a href="?quit">выход</a>]'; 
		}else{
			$adminsListQ = $DB->query("SELECT * FROM `recycling_admins` ORDER BY id");
			$return .= '<div style="background-color: lightpink; padding: 10px; max-width: 300px;">фио<select name="adminsAuthId"><option value="0" selected>-- выберите --</option>';
			while($row = $DB->fetch_assoc($adminsListQ)){
				$return .= "<option value=\"$row[id]\">$row[fio]</option>";
			}			
			$return .= '</select>
			<br />пароль<input type="password" name="adminsAuthPass">
			<br /><input type="submit" name="cmdAuthAdmin" value="авторизация">	
			</div>
			';			
		}		

		$return .= '
		</fieldset>
		</form>
		<fieldset><legend>Компании и админы:</legend>
		<table class="zebra">
			<thead>
				<tr>
					<th>Компания</th>
					<th>Офис</th>
					<th>Админы</th>
				</tr>
			</thead>
			<tbody>		
		';
		
		while($pos = $DB->fetch_assoc($companyQuery)){

		$return .= "
				<tr>
					<td>$pos[company]</td>
					<td>$pos[office]</td>
					<td>$pos[fio]</td>						
				</tr>";
		}
		
		$return .= '
			</tbody>
		</table>
		</fieldset>';		
		return $return;		
	}	
	
	
	function portalShow_GET_payments(){
		return "Нет записей по счетам";
	}
	
	function portalShow_GET_breaks(){
		global $DB;
		
		$eventsQuery = $DB->query("SELECT 
`recycling_cartridges`.`cartridge`,
`recycling_cartridges`.`inv_num`,
`recycling_companies`.`company`,
`recycling_printers`.`printer`,
`recycling_events_reasons`.`reason`,
`recycling_events`.`dt`

 FROM `recycling_events`
LEFT JOIN `recycling_cartridges` ON `recycling_events`.`cartridge_out_id`=`recycling_cartridges`.`id`
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_companies` ON `recycling_companies`.`id`=`recycling_printers`.`company_id`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
WHERE
`recycling_events`.`office_id` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
AND
`recycling_cartridges`.`status_id`='1'
AND `is_paid`='0'
ORDER BY `recycling_printers`.`company_id`,`dt`");

		$return .= '
		<fieldset><legend>События для разбивки:</legend>
		<table class="zebra">
			<thead>
				<tr>
					<th>Компания</th>
					<th>Принтер</th>
					<th>Картридж</th>
					<th>Причина замены</th>					
					<th>Дата замены</th>					
				</tr>
			</thead>
			<tbody>		
		';


		while($pos = $DB->fetch_assoc($eventsQuery)){

		$return .= "
				<tr>
					<td>$pos[company]</td>
					<td>$pos[printer]</td>
					<td><b>$pos[inv_num]</b> ($pos[cartridge])</td>
					<td>$pos[reason]</td>					
					<td>$pos[dt]</td>					
				</tr>";
		}
		
			$return .= '
			</tbody>
		</table>
		</fieldset>';		
		return $return;
		
	}	
	
	function portalShow_GET_cartridges(){
		global $DB;
		
		$offices = $DB->fetch_array("SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]'");
		
		$cartridges = $DB->query("		
SELECT `recycling_cartridges_stasuses`.`stasus`, `recycling_offices`.`office`, `recycling_printers`.`printer`, `recycling_cartridges`.* FROM `recycling_cartridges` 
LEFT JOIN `recycling_cartridges_stasuses` ON `recycling_cartridges`.`status_id`=`recycling_cartridges_stasuses`.`id`
LEFT JOIN `recycling_offices` ON `recycling_cartridges`.`office_id`=`recycling_offices`.`id`
LEFT JOIN `recycling_printers` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
WHERE `recycling_cartridges`.`office_id` IN ('".implode("','", $offices)."')
ORDER BY `recycling_cartridges`.`office_id`, status_id, id");

		$allowPrinters = $DB->query("SELECT id, printer FROM `recycling_printers` WHERE `office_id` IN ('".implode("','", $offices)."') AND `recycling_printers`.`status_id`='1' ORDER BY printer");

		$return = '		
		<form name="form3" method="post" action="'.$_SERVER['PHP_SELF'].'">	
		<fieldset><legend>регистрация нового картриджа:</legend>
		для принтера
		<select name="printerRegcartridgeId" onChange="form3.submit();">
		';
		
		if((int)$_SESSION['printerRegcartridgeId'] == 0) $return .= '<option value="0" selected>-- выберите --</option>'; 
		while($allowPrinter = $DB->fetch_assoc($allowPrinters)) $return .= "<option value='$allowPrinter[id]'".(($allowPrinter['id'] == $_SESSION['printerRegcartridgeId']) ? ' selected' : '') . ">$allowPrinter[printer]</option>";
		
		$return .= '
		</select>		
		<br />тип:
		';
		
		if($_SESSION['printerRegcartridgeId'] > 0){
			$return .= '<select name="cartridgeAdd"><option value="0">-- выберите --</option>';
			$cartridgeIds = $DB->result("SELECT `cartridge` FROM `recycling_printers` WHERE `id`='$_SESSION[printerRegcartridgeId]'");
			$cartridgeIds = explode(',', $cartridgeIds);
			foreach($cartridgeIds as $cartridgeId) $return .= "<option value=\"$cartridgeId\">$cartridgeId</option>";			
			$return .= '</select>';
		}else{
			$return .= '<b>выберите принтер</b>';				
		}
		
		$return .= '
		<br/>
		инв.№ (0 - автогенерация номера):
		<input type="text" name="cartridgeInvNum" value="0" size="5">
		<br>причина:
		<input type="radio" name="reason_new_id" value="3">покупка нового
		<input type="radio" name="reason_new_id" value="4" checked>покупка б/у		
		<br><input type="submit" name="cmdAddCartridge" value="добавить новый картридж">		
		</fieldset>
		</form>		
		
		<fieldset><legend>Картриджи:</legend>
		<table class="zebra" id="tableCartridges">
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
						<td><b>$cartridge[inv_num]</b> ($cartridge[cartridge])</td>							
						<td>$cartridge[stasus]</td>
						<td>$cartridge[printer]</td>						
					</tr>
				";			
			}
		
			$return .= '
		</tbody>
		</table>
		</fieldset>';		
		return $return;			
	}
	
	
	function show_cartridge_replace_form(){
		global $DB;

		$allowPrinters = $DB->query("SELECT * FROM recycling_printers WHERE `office_id` IN (SELECT office_id FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') AND `status_id`='1' ORDER BY printer");

		$return = '
		<form name="form2" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>замена картриджа:</legend>
		принтер:
		<select name="printer_id" onChange="form2.submit();"><option value="0">--- выберите ---</option>';
		while($allowPrinter = $DB->fetch_assoc($allowPrinters)) $return .= "<option value='$allowPrinter[id]'".(($allowPrinter['id'] == $_SESSION['printerId']) ? ' selected' : '') . ">$allowPrinter[printer]</option>";
		$return .= '</select>';
		
		if($_SESSION['printerId'] > 0){
			
			$cartridgeInside = $DB->fetch_assoc("SELECT `recycling_printers`.`cartridge_inside`, `recycling_printers`.`cartridge`, `recycling_cartridges`.`cartridge` AS cartridge_name, `recycling_cartridges`.`inv_num` FROM recycling_printers LEFT JOIN `recycling_cartridges` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id` WHERE `recycling_printers`.`id`='$_SESSION[printerId]'");
			$allowCartridges = $DB->query("SELECT * FROM `recycling_cartridges` WHERE `office_id`=(SELECT office_id FROM `recycling_printers` WHERE `recycling_printers`.`id`='$_SESSION[printerId]') AND `status_id`='2' AND cartridge IN ('".implode("','", explode(',', $cartridgeInside['cartridge']))."')");
		
			$return .= '		
			<br>картридж out: <input type="hidden" name="cartridge_out_id" value="'.$cartridgeInside['cartridge_inside'].'">
			<b>'.$cartridgeInside['inv_num'].'</b> ('.$cartridgeInside['cartridge_name'].')
			картридж in:
			<select name="cartridge_in_id">';
			
			while($cartridgeVariant = $DB->fetch_assoc($allowCartridges)) $return .= "<option value=\"$cartridgeVariant[id]\">$cartridgeVariant[inv_num] ($cartridgeVariant[cartridge])</option>";			

			$return .= '
			</select>
			<br>причина:
			<input type="radio" name="reason_id" value="1" checked>закончился тонер
			<input type="radio" name="reason_id" value="2">рекламация

			<br>счётчик страниц:<input type="text" name="pages" size="10" maxlength="10">
			<br>примечание:<input type="text" name="remark" size="80" maxlength="200">
			<br><input type="submit" name="cmdAddEvent" value="заменить картридж">';
		
		}
		
		$return .= '		
		</fieldset>
		</form>	
		';
		return $return;		
	}
	
	function portalShow_GET_printers(){
		global $DB;
		
		$query = $DB->query("SELECT 
 `recycling_printers_stasuses`.`stasus`,
 `recycling_companies`.`company`,
 `recycling_offices`.`office`,
 `recycling_cartridges`.`inv_num`,
 `recycling_cartridges`.`cartridge` AS cartridge_txt,
 `recycling_printers_types`.`printer_type_txt`,
 `recycling_printers`.*
 FROM `recycling_printers` 
 LEFT JOIN `recycling_offices` ON `recycling_printers`.`office_id`=`recycling_offices`.`id`
 LEFT JOIN `recycling_companies` ON `recycling_printers`.`company_id`=`recycling_companies`.`id`
 LEFT JOIN `recycling_printers_stasuses` ON `recycling_printers`.`status_id`=`recycling_printers_stasuses`.`id`
 LEFT JOIN `recycling_cartridges` ON `recycling_printers`.`cartridge_inside`=`recycling_cartridges`.`id`
 LEFT JOIN `recycling_printers_types` ON `recycling_printers`.`type_id`=`recycling_printers_types`.`id`
 WHERE `recycling_printers`.`office_id` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]')
  ORDER BY office, status_id desc, printer");
  
		$return = '	
		<fieldset><legend>Принтеры:</legend>
		<table class="zebra">
			<thead>
				<tr>
					<th>ID</th>				
					<th>Офис</th>
					<th>Название</th>
					<th>Тип</th>					
					<th>Владелец</th>
					<th>Типы картриджей</th>
					<th>Внутри</th>
					<th>IP</th>
					<th>SN</th>
					<th>Статус</th>
				</tr>
			</thead>
			<tbody>';
			
			while($row = $DB->fetch_assoc($query)){
				$return .= "
					<tr>
						<td>$row[id]</td>
						<td>$row[office]</td>
						<td>$row[printer]</td>
						<td>$row[printer_type_txt]</td>
						<td>$row[company]</td>
						<td>$row[cartridge]</td>
						<td>".(($row['inv_num'] > 0) ? "<b>$row[inv_num]</b> ($row[cartridge_txt])" : '')."</td>
						<td>".((!empty($row['ip'])) ? "<a href=\"http://$row[ip]/\" target=\"_blank\">$row[ip]</a>" : '')."</td>
						<td>$row[sn]</td>						
						<td>$row[stasus]</td>
					</tr>
				";			
			}
		
			$return .= '
		</tbody>
		</table>
		</fieldset>';		
		return $return;		
	}
	

	function portalShow_GET_journal(){
		global $DB;		
		$return = show_cartridge_replace_form();		
		$return .= '
		<form name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'">
		<fieldset><legend>Журнал:</legend>
		<table class="zebra">
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

		$res = $DB->query("SELECT
`recycling_events_reasons`.`reason`,
`recycling_events_stasuses`.`stasus`,
`recycling_printers`.`printer`,
`cartrige_in`.`cartridge` AS cartridge_in_name,
`cartrige_in`.`inv_num` AS cartridge_in_num,
`cartrige_out`.`cartridge` AS cartridge_out_name,
`cartrige_out`.`inv_num` AS cartridge_out_num,
`cartrige_new`.`cartridge` AS cartridge_new_name,
`cartrige_new`.`inv_num` AS cartridge_new_num,
`event_txt`.`event_txt`,
recycling_events.*
 FROM recycling_events
LEFT JOIN `recycling_printers` ON `recycling_events`.`printer_id`=`recycling_printers`.`id`
LEFT JOIN `recycling_events_stasuses` ON `recycling_events`.`status_id`=`recycling_events_stasuses`.`id`
LEFT JOIN `recycling_events_reasons` ON `recycling_events`.`reason_id`=`recycling_events_reasons`.`id`
LEFT JOIN `cartrige_in` ON `cartrige_in`.`id`=`recycling_events`.`cartridge_in_id`
LEFT JOIN `cartrige_out` ON `cartrige_out`.`id`=`recycling_events`.`cartridge_out_id`
LEFT JOIN `cartrige_new` ON `cartrige_new`.`id`=`recycling_events`.`cartridge_new_id`
LEFT JOIN (
	SELECT GROUP_CONCAT(CONCAT(recycling_events_admins.`dt`,': ',`recycling_admins`.`fio`,' / ',`recycling_events_stasuses`.`stasus`, ' / <i>', recycling_events_admins.`remark`,'</i>') SEPARATOR '<br/>') AS event_txt, recycling_events_admins.`event_id` FROM recycling_events_admins
		LEFT JOIN `recycling_events_stasuses` ON `recycling_events_admins`.`event_typ_id`=`recycling_events_stasuses`.`id`
		LEFT JOIN `recycling_admins` ON `recycling_events_admins`.`admin_id`=`recycling_admins`.`id`
	 GROUP BY event_id ORDER BY `recycling_events_admins`.`id`
) AS event_txt ON `event_txt`.`event_id`=`recycling_events`.`id`
WHERE `recycling_events`.`office_id` IN (SELECT `office_id` FROM `recycling_admins_offices` WHERE `admin_id`='$_SESSION[adminId]') ORDER BY dt DESC LIMIT $_SESSION[journalEvents]");

		while ($row = $DB->fetch_assoc($res)){
			$return .= '		
			<tr>
				<td>'.$row['id'].'</td>
				<td>'.$row['printer']."</td>
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
					$return .= '<br /><input type="submit" name="cmdEvent[2]['.$row['id'].']" value="поставить в резерв"><input type="submit" name="cmdEvent[3]['.$row['id'].']" value="вывести из оборота">';
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

?>
</body>
</html>