<?php

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
		
		$cartridges = $DB->query("SELECT `recycling_cartridges_stasuses`.`stasus`, `recycling_offices`.`office`, `recycling_printers`.`printer`, `recycling_cartridges`.* FROM `recycling_cartridges` 
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
LEFT JOIN `recycling_cartridges` AS `cartrige_in` ON `cartrige_in`.`id`=`recycling_events`.`cartridge_in_id`
LEFT JOIN `recycling_cartridges` AS `cartrige_out` ON `cartrige_out`.`id`=`recycling_events`.`cartridge_out_id`
LEFT JOIN `recycling_cartridges` AS `cartrige_new` ON `cartrige_new`.`id`=`recycling_events`.`cartridge_new_id`
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
	
	function show_select($selected, $array, $selectname){
		//формирует select в html из массива
		$return = '<select name="'.$selectname.'" id="'.$selectname.'">';
		foreach($array as $key => $value) $return .= '<option value="'.$key.'"'.(($selected == $key) ? ' selected="selected"' : '').'>'.strip_tags($value).'</option>';
		return $return."</select>\n";
	}

?>