<?php include("auth/auth.php"); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Движения картриджей</title>
<meta http-equiv="pragma" content="no-cache">
<link href="/main.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php
$htdocs = "/home/www/qqq/htdocs";
$inc_dir = $htdocs . "/inc";

//require_once($inc_dir . "/link_mssql.php");
//require_once($inc_dir . "/squid.php");
//require_once($inc_dir . "/iconv.php");
$link = require_once($inc_dir . "/link_mysql.php");
mysql_select_db('site');

$d_offices = FetchMysqlTable('recycling_offices', NULL, 'id', 'office');
$office_id = isset($_POST['office_id']) ? $_POST['office_id'] : 1;
$printer_id = isset($_POST['printer_id']) ? $_POST['printer_id'] : 36;

$d_printers = FetchMysqlTable('recycling_printers', "office_id=$office_id AND status_id>0", 'printer');
$d_printers_print = FetchMysqlTable('recycling_printers', "office_id=$office_id", 'printer', 'printer');
$d_printers_active = FetchMysqlTable('recycling_printers', "office_id=$office_id AND status_id=1", 'printer', 'printer');
$d_cartridges = FetchMysqlTable('recycling_cartridges', "office_id=$office_id", 'inv_num');
if (is_array($d_cartridges)) {
	foreach ($d_cartridges as $id => $d) {
		$d_cartridges_print[$id] = '<b>' . $d_cartridges[$id]['inv_num'] . '</b> (' . $d_cartridges[$id]['cartridge'] . ')';
	}
}
$d_admins = FetchMysqlTable('recycling_admins', NULL, 'fio', 'fio');
$admin_id = isset($_POST['admin_id']) ? $_POST['admin_id'] : 4;

$pages = isset($_POST['pages']) ? (int)$_POST['pages'] : 0;

$nevents = isset($_POST['nevents']) ? (int)$_POST['nevents'] : 100;
if ($nevents <= 0) $nevents = 100;

$d_events_stasuses = FetchMysqlTable('recycling_events_stasuses', NULL, 'id', 'stasus');
$d_events_reasons = FetchMysqlTable('recycling_events_reasons', NULL, 'id', 'reason');

//$dt = date("Y-m-d H:i:s");
foreach (array('dt', 'printer_id', 'cartridge_out_id', 'cartridge_in_id', 'reason_id', 'remark') as $k) {
	if (isset($_POST[$k])) $$k = $_POST[$k];
	//$$k = $_POST[$k];
}
if (isset($_POST['cmdAddEvent'])) {
	// создание события (замена картриджа)
	$query = "
		INSERT INTO recycling_events (
			office_id, printer_id, dt, cartridge_out_id, cartridge_in_id, reason_id, pages
		) VALUES (
			$office_id, $printer_id, '$dt', $cartridge_out_id, $cartridge_in_id, $reason_id, $pages
		)
	";
	//echo $query;
	mysql_query($query);
	$event_id = mysql_result(mysql_query("
		SELECT id FROM recycling_events WHERE
			office_id=$office_id
			AND printer_id=$printer_id
			AND cartridge_out_id=$cartridge_out_id
			AND cartridge_in_id=$cartridge_in_id
		ORDER BY id DESC
		LIMIT 1
	"), 0);
	$query = "
		INSERT INTO recycling_events_admins (
			event_id, dt, admin_id, event_typ_id, remark
		) VALUES (
			$event_id, '$dt', $admin_id, 0, '$remark'
		)
	";
	//echo $query;
	mysql_query($query);
	mysql_query("UPDATE recycling_cartridges SET status_id=0 WHERE id=$cartridge_out_id");
	mysql_query("UPDATE recycling_cartridges SET status_id=4 WHERE id=$cartridge_in_id");
	mysql_query("UPDATE recycling_printers SET cartridge_inside=$cartridge_in_id WHERE id=$printer_id");
} elseif (isset($_POST['cmdAddCartridge'])) {
	foreach (array('cartridge_typ_add', 'cartridge_n_add', 'reason_new_id') as $k) {
		$$k = $_POST[$k];
	}
	if ($cartridge_n_add == 0) {
		// автогенерация Ca-N
		$cartridge_n_add = mysql_result(mysql_query("
			SELECT MAX(inv_num) + 1 FROM recycling_cartridges WHERE office_id=$office_id
		"), 0);
	}
	$query = "
		INSERT INTO recycling_cartridges (
			cartridge, office_id, inv_num, status_id
		) VALUES (
			'$cartridge_typ_add', $office_id, $cartridge_n_add, 2
		)
	";
	mysql_query($query);
	//echo $query;
	$cartridge_new_id = mysql_result(mysql_query("
		SELECT id FROM recycling_cartridges WHERE
			cartridge='$cartridge_typ_add' AND office_id=$office_id AND inv_num=$cartridge_n_add
	"), 0);
	$query = "
		INSERT INTO recycling_events (
			office_id, dt, cartridge_new_id, reason_id, status_id
		) VALUES (
			$office_id, '$dt', $cartridge_new_id, $reason_new_id, 5
		)
	";
	mysql_query($query);
	$event_id = mysql_result(mysql_query("
		SELECT id FROM recycling_events WHERE
			office_id=$office_id
			AND dt='$dt'
			AND cartridge_new_id=$cartridge_new_id
		ORDER BY id DESC
		LIMIT 1
	"), 0);
	$query = "
		INSERT INTO recycling_events_admins (
			event_id, dt, admin_id, event_typ_id, remark
		) VALUES (
			$event_id, '$dt', $admin_id, 5, '$remark'
		)
	";
	//echo $query;
	mysql_query($query);
} elseif (isset($_POST['cmdPay'])) {
	$event_id = key($_POST['cmdPay']);
	mysql_query("UPDATE recycling_events SET is_paid=1 WHERE id=$event_id");
	mysql_query("
		INSERT INTO recycling_events_admins (
			event_id, dt, admin_id, event_typ_id, remark
		) VALUES (
			$event_id, '$dt', $admin_id, 4, '$remark'
		)
	");

} elseif (isset($_POST['cmdEvent'])) {
	$status_id = key($_POST['cmdEvent']);
	$event_id = key($_POST['cmdEvent'][$status_id]);
	mysql_query("UPDATE recycling_events SET status_id=$status_id WHERE id=$event_id");
	mysql_query("
		INSERT INTO recycling_events_admins (
			event_id, dt, admin_id, event_typ_id, remark
		) VALUES (
			$event_id, '$dt', $admin_id, $status_id, '$remark'
		)
	");
	$cartridge_id = mysql_result(mysql_query("SELECT cartridge_out_id FROM recycling_events WHERE id=$event_id"), 0);
	mysql_query("UPDATE recycling_cartridges SET status_id=$status_id WHERE id=$cartridge_id");
//} elseif (isset($_POST['cmdPages'])) {
//	$event_id = key($_POST['cmdPages']);
//	$pages = $_POST['dpages'][$event_id];
//	mysql_query("UPDATE recycling_events SET pages=$pages WHERE id=$event_id");
}


/*
$lstFiles = isset($_POST['lstFiles']) ? $_POST['lstFiles'] : 0;
$lstFilesMove = isset($_POST['lstFilesMove']) ? $_POST['lstFilesMove'] : 0;
$lstContent = isset($_POST['lstContent']) ? $_POST['lstContent'] : 0;
$txtContent = isset($_POST['txtContent']) ? $_POST['txtContent'] : "";
$textInsert = isset($_POST['textInsert']) ? trim($_POST['textInsert']) : "";

$query = "SELECT * FROM $table_filter_list ORDER BY filename";
$res = mssql_query($query);
while ($a_row = mssql_fetch_assoc($res)) $d_files[$a_row['id']] = $a_row;

if (isset($_POST['cmdDel'])) {
	if (is_array($lstContent) and count($lstContent) > 0) {
		$query = "DELETE FROM $table_filter_content WHERE id IN (" . implode(",", $lstContent) . ")";
		$res = mssql_query($query);
		echo win2utf($query) . "<br>";
	}
} elseif (isset($_POST['cmdMove'])) {
	if (is_array($lstContent) and count($lstContent) > 0) {
		$query = "UPDATE $table_filter_content SET filter_group=${lstFilesMove} WHERE id IN (" . implode(",", $lstContent) . ")";
		$res = mssql_query($query);
		echo win2utf($query) . "<br>";
	}
} elseif (isset($_POST['cmdUpdate'])) {
	if (is_array($lstContent) and count($lstContent) == 1 and trim($txtContent) !== "") {
		list($content, $remark) = ConvertToContentAndRemark($txtContent);
		$content = utf2win($content);
		$remark = utf2win($remark);
		$query = "UPDATE $table_filter_content SET filter_content='$content', remark='$remark' WHERE id=" . $lstContent[0];
		$res = mssql_query($query);
		echo win2utf($query) . "<br>";
	}
} elseif (isset($_POST['cmdInsert'])) {
	if ($lstFiles > 0 and trim($txtContent) !== "") {
		list($content, $remark) = ConvertToContentAndRemark($txtContent);
		$content = utf2win($content);
		$remark = utf2win($remark);
		$query = "INSERT INTO $table_filter_content (filter_group, filter_content, remark) VALUES ($lstFiles, '$content', '$remark')";
		$res = mssql_query($query);
		echo win2utf($query) . "<br>";
	}
} elseif (isset($_POST['cmdInsertList'])) {
	if ($lstFiles > 0 and $textInsert !== "") {
		$d = explode("\n", $textInsert);
		foreach ($d as $v) {
			list($content, $remark) = ConvertToContentAndRemark($v);
			$content = utf2win($content);
			$remark = utf2win($remark);
			$query = "INSERT INTO $table_filter_content (filter_group, filter_content, remark) VALUES ($lstFiles, '$content', '$remark')";
			$res = mssql_query($query);
			echo win2utf($query) . "<br>";
		}
	}
}
*/
//echo "<pre>";
//print_r($d_cartridges[1]);
//print_r($d_printers[1]);
//echo "</pre>";
?>
<form name="form1" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
<table><tr valign="top"><td>
			
<table><tr valign="top"><td>
<fieldset>
<legend>офис:</legend>
<?php
echo "<select name='office_id' onChange='form1.submit();'>";
foreach ($d_offices as $id => $office) {
	echo "<option value='$id'" . (($id == $office_id) ? " selected" : '') . ">$office</option>";
}
echo "</select>";
?>
</fieldset>
</td><td>
<fieldset>
<legend>одмин:</legend>
<?php
echo "<select name='admin_id' onChange='form1.submit();'>";
foreach ($d_admins as $id => $admin) {
	echo "<option value='$id'" . (($id == $admin_id) ? " selected" : '') . ">$admin</option>";
}
echo "</select>";
?>
</fieldset>
</td><td>
<fieldset>
<legend>дата и время:</legend>
<input type="text" name="dt" value="<?php echo date("Y-m-d H:i:s"); ?>" size="20">
</fieldset>
</td><td>
<input type="submit" name="cmdRefresh" value="обновить страницу">
</td></tr></table>

<table><tr valign="top"><td>
<fieldset>
<legend>замена картриджа:</legend>
принтер:
<?php
echo "<select name='printer_id' onChange='form1.submit();'>";
foreach ($d_printers_active as $id => $printer) {
	echo "<option value='$id'" . (($id == $printer_id) ? ' selected' : '') . ">$printer</option>";
}
echo "</select>";
?>
<br>
картридж out:

<?php
$cartridge_inside_id = mysql_result(mysql_query("SELECT cartridge_inside FROM recycling_printers WHERE id=$printer_id"), 0);
?>

<input type="hidden" name="cartridge_out_id" value="<?=$cartridge_inside_id; ?>">
<b><?= $d_cartridges_print[$cartridge_inside_id]; ?></b>
<!--
<select name="cartridge_out_id">
	<?php
	$cartridge_inside_id = mysql_result(mysql_query("
		SELECT cartridge_in_id
		FROM recycling_events
		WHERE office_id=$office_id AND printer_id=$printer_id ORDER BY dt DESC LIMIT 1
	"), 0);
	foreach ($d_cartridges_print as $cartridge_id => $cartridge) {
		// проверяем, подходит ли картридж к выбранному принтеру
		if (!in_array($d_cartridges[$cartridge_id]['cartridge'], explode(',', $d_printers[$printer_id]['cartridge']))) continue;
		echo "<option value='$cartridge_id'" . (($cartridge_inside_id == $cartridge_id) ? " selected" : '') . ">$cartridge</option>";
	}
	?>
</select>
-->
картридж in:
<select name="cartridge_in_id">
	<?php
	foreach ($d_cartridges_print as $cartridge_id => $cartridge) {
		// проверяем, подходит ли картридж к выбранному принтеру
		if (!in_array($d_cartridges[$cartridge_id]['cartridge'], explode(',', $d_printers[$printer_id]['cartridge']))) continue;
		// проверяем, находится ли картридж в резерве
		if ($d_cartridges[$cartridge_id]['status_id'] != 2) continue;
		echo "<option value='$cartridge_id'>$cartridge</option>";
	}
	?>
</select>
<br>
причина:
<input type="radio" name="reason_id" value="1" checked>закончился тонер
<input type="radio" name="reason_id" value="2">рекламация

<br>
счётчик страниц:
<input type="text" name="pages" size="10" maxlength="10">

<br>
примечание:
<input type="text" name="remark" size="80" maxlength="200">
<br>
<input type="submit" name="cmdAddEvent" value="заменить картридж">
</fieldset>

<fieldset>
<legend>регистрация нового картриджа:</legend>
тип:
<input type="text" name="cartridge_typ_add" value="53X" size="5">
инв.№ (0 - автогенерация номера):
<input type="text" name="cartridge_n_add" value="0" size="5">

<br>
причина:
<input type="radio" name="reason_new_id" value="3">покупка нового
<input type="radio" name="reason_new_id" value="4" checked>покупка б/у

<br>
<input type="submit" name="cmdAddCartridge" value="добавить новый картридж">
</fieldset>

</td></tr></table>

</td><td>
<fieldset>
<legend>сводная статистика:</legend>
<?php
foreach ($d_cartridges as $cartridge_id => $d) {
	if ($d['status_id'] == 3) continue;
	$cartridge = $d['cartridge'];
	$d_statistic[$cartridge]['n']++;
	$d_statistic[$cartridge][$d['status_id']]++;
	$d_statistic_cartridge[$cartridge][$d['status_id']][] = $d_cartridges[$cartridge_id]['inv_num'];
}
ksort($d_statistic);
//echo "<pre>";
//print_r($d_cartridges);
//echo "</pre>";
//$res = mysql_query("SELECT * FROM recycling_cartridges WHERE status_id<>3");
//while ($row = mysql_fetch_assoc($result)) {
//}
?>
<table border="0" cellspacing="1" cellpadding="1">
	<tr bgcolor="lightblue">
		<th>Ca</th>
		<th width="40px">all</th>
		<th width="40px" title="inside printer">inside</th>
		<th width="40px">empty</th>
		<th width="40px">recycl.</th>
		<th width="40px">reserve</th>
	</tr>
	<?php
	$n = 0;
	foreach ($d_statistic as $cartridge => $d) {
		$n++;
		$cclass = ($n % 2 == 1) ? "tb2" : "tb22";
		echo "<tr class='$cclass' align='center'>";
		echo "  <td>$cartridge</td>";
//		echo "  <td>$d[n]</td>";
		$d_statistic_cartridge_all = array();
		foreach ($d_statistic_cartridge[$cartridge] as $ddd) {
			$d_statistic_cartridge_all = array_merge($d_statistic_cartridge_all, $ddd);
		}
		$cartridge_title = implode(', ', $d_statistic_cartridge_all);
		echo "  <td title='$cartridge_title'>$d[n]</td>";
		foreach (array(4, 0, 1, 2) as $k) {
			$cartridge_title = implode(', ', $d_statistic_cartridge[$cartridge][$k]);
			echo "  <td title='$cartridge_title'>$d[$k]</td>";
		}
		echo "</tr>";
	}
	?>
</table>
</fieldset>
</td></tr></table>

<fieldset>
<legend>Журнал:</legend>
<table border="0" cellspacing="1" cellpadding="1">
	<tr bgcolor="lightblue">
		<th>id</th>
		<th>Принтер</th>
		<th>Изъят</th>
		<th>Вставлен</th>
		<th>Причина</th>
		<th>Счётчик</th>
		<th>Журнал событий (отображать строк: <input type="text" name="nevents" size="4" value="<?php echo $nevents; ?>" maxlength="10">)</th>
		<th>Статус</th>
		<th>Оплачен?</th>
	</tr>
	<?php
	//$query = "SELECT * FROM recycling_events WHERE office_id=$office_id AND dt>DATE_SUB(CURDATE(),INTERVAL 1 YEAR) ORDER BY dt DESC";
	$where = "office_id=$office_id";
//	if (isset($_POST['cmdPages'])) {
//		$where .= " AND id <= $event_id";
//	}
	$query = "SELECT * FROM recycling_events WHERE $where ORDER BY dt DESC LIMIT $nevents";
	$res = mysql_query($query);
	$n = 0;
	while ($row = mysql_fetch_assoc($res)) {
		$n++;
		$cclass = ($n % 2 == 1) ? "tb2" : "tb22";
		//if ($row['is_recycled'] == 0) $cclass .= ' red';
		$event_id = $row['id'];
		$query2 = "SELECT * FROM recycling_events_admins WHERE event_id=$event_id ORDER BY id";
		$res2 = mysql_query($query2);
		unset ($ddoings);
		$kdoings = FALSE;
		while ($row2 = mysql_fetch_assoc($res2)) {
			if (!$kdoings) {
				$kdoings = TRUE;
				$pages_from_remark = (int) str_replace('.', '', str_replace(',', '', str_replace(' ', '', array_shift(explode(' ', trim($row2['remark']))))));		
			}
			$ddoings[] = $row2['dt'] . ': ' . $d_events_stasuses[$row2['event_typ_id']] . ' /' . $d_admins[$row2['admin_id']] . '/' . (($row2['remark'] != '') ? ' <i>' . $row2['remark'] . '</i>' : '');
		}
		$doings = implode('<br>', $ddoings);
		if ($row['cartridge_new_id'] > 0) {
			$td_cartridges = $d_cartridges_print[$row['cartridge_new_id']];
			$td_cartridges_in = $d_cartridges_print[$row['cartridge_new_id']];
			$td_cartridges_out = '';
		} else {
			$td_cartridges = $d_cartridges_print[$row['cartridge_out_id']] . '<br>&nbsp;&nbsp;&nbsp;=> ' . $d_cartridges_print[$row['cartridge_in_id']];
			$td_cartridges_in = $d_cartridges_print[$row['cartridge_in_id']];
			$td_cartridges_out = $d_cartridges_print[$row['cartridge_out_id']];
		}
		?>
		<tr class="<?=$cclass; ?>">
			<td><?=$row['id']; ?></td>
			<td nowrap><?=$d_printers_print[$row['printer_id']]; ?></td>
			<td nowrap><?=$td_cartridges_out; ?></td>
			<td nowrap><?=$td_cartridges_in; ?></td>
			<td><?php echo $d_events_reasons[$row['reason_id']]; ?></td>
			<td nowrap>
				<?php
				echo $row['pages'];
//				if ($row['pages'] > 0) {
//					echo $row['pages'];
//				} else {
//					echo "<input type='text' name='dpages[$event_id]' value='$pages_from_remark' size='6' maxlength='10'>";
//					echo "<input type='submit' name='cmdPages[$event_id]' value='set'>";
//				}
				?>
			</td>
			<td><?=$doings; ?></td>
			<td nowrap>
				<?php
				$status_id = $row['status_id'];
				if ($status_id != 2) echo '<b>' . $d_events_stasuses[$status_id] . '</b>';
				if ($status_id == 0) {
					?>
					<br><input type="submit" name="cmdEvent[1][<?=$event_id; ?>]" value="передать заправщику">
					<?php
				} elseif ($status_id == 1) {
					?>
					<br>
					<input type="submit" name="cmdEvent[2][<?=$event_id; ?>]" value="поставить в резерв">
					<input type="submit" name="cmdEvent[3][<?=$event_id; ?>]" value="вывести из оборота">
					<?php
				}
				?>
			</td>
			<td align="center">
				<?php
				$is_paid = ($row['is_paid'] == 1);
				if ($is_paid) {
					echo 'yes';
				} else {
					echo "<input type='submit' name='cmdPay[$event_id]' value='pay'>";
				}
				?>
			</td>
		</tr>
		<?
	}
	?>
</table>
</fieldset>

<pre><?php //print_r($_POST); ?></pre>
<!--
<td width="100%">
	<input type="text" name="txtContent" value="" size="80">
	<input type="submit" name="cmdUpdate" value="Update">
	<input type="submit" name="cmdInsert" value="Insert new">
	<br>
	<?
	$res = mssql_query("SELECT * FROM $table_filter_content WHERE filter_group=$lstFiles ORDER BY filter_content");
	?>
	фильтров: <?=mssql_num_rows($res); ?>
	<br>
	<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr valign="top"><td>
		<select name="lstContent[]" size="40" multiple="true" onChange="form1.txtContent.value=this.options[this.selectedIndex].text">
			<?php
			while ($a_row = mssql_fetch_array($res)) {
				$id = $a_row['id'];
				$content = win2utf($a_row['filter_content']);
				$remark = win2utf($a_row['remark']);
				$d_url[] = $content;
				?>
				<option value="<?php print $id; ?>"<? if (@in_array($id, $lstContent)) echo " selected"; ?>>
					<? echo $content . (($remark == "") ? "" : " # " . $remark); ?>
				</option>
				<?
			}
			?>
		</select>
	</td>
	<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<td width="100%">
		<input type="submit" name="cmdDel" value="Delete">
		<br><br>
		<input type="submit" name="cmdMove" value="Move to">
		<select name="lstFilesMove" size="1">
			<?
			foreach ($d_files as $k => $d) {
				?>
				<option value="<? print $k; ?>"<? if ($k == $lstFilesMove) echo " selected"; ?>>
					<? print win2utf($d['typ']) . " - " . win2utf($d['remark']); ?>
				</option>
				<?
			}
			?>
		</select>
		<br>
		<br>
		<fieldset>
		<legend>добавить группу сайтов:</legend>
		<textarea name="textInsert" cols="30" rows="18"></textarea>
		<br>
		<input type="submit" name="cmdInsertList" value="Добавить">
		</fieldset>
		<hr>
		<?
		?>
	</td></tr></table>
-->


</form>
	<pre>
	<?php
				print_r($d_printers_print)
	//print_r($d_cartridges); ?>
	</pre>
</body>
</html>

<?


function FetchMysqlTable($table, $where = NULL, $order = NULL, $field = NULL) {
	$query = "SELECT * FROM $table";
	if (!is_null($where)) $query .= " WHERE $where";
	if (!is_null($order)) $query .= " ORDER BY $order";
	$res = mysql_query($query);
	//echo "=======$query=======";
	while ($row = mysql_fetch_assoc($res)) {
		if (isset ($row['id'])) {
			$d[$row['id']] = (is_null($field)) ? $row : $row[$field];
		} else {
			$d[] = (is_null($field)) ? $row : $row[$field];
		}
	}
	return $d;
}
?>