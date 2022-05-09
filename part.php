	function FetchMysqlTable($table, $where = NULL, $order = NULL, $field = NULL){
		global $DB;
		$query = "SELECT * FROM $table";
		if (!is_null($where)) $query .= " WHERE $where";
		if (!is_null($order)) $query .= " ORDER BY $order";
		$res = $DB->query($query, __FILE__, __LINE__);
		//echo "=======$query=======";
		while ($row = $DB->fetch_assoc($res, __FILE__, __LINE__)) {
			if (isset ($row['id'])) {
				$d[$row['id']] = (is_null($field)) ? $row : $row[$field];
			} else {
				$d[] = (is_null($field)) ? $row : $row[$field];
			}
		}
		return $d;
	}
	
	
//	$d_offices = FetchMysqlTable('recycling_offices', NULL, 'id', 'office');
//	$office_id = isset($_POST['office_id']) ? $_POST['office_id'] : 1;
//	$admin_id = isset($_POST['admin_id']) ? $_POST['admin_id'] : 4;
//	$printer_id = isset($_POST['printer_id']) ? $_POST['printer_id'] : 36;

//	$d_printers = FetchMysqlTable('recycling_printers', "office_id=$_SESSION[officeId] AND status_id>0", 'printer');
//	$d_printers_print = FetchMysqlTable('recycling_printers', "office_id=$_SESSION[officeId]", 'printer', 'printer');
//	$d_printers_active = FetchMysqlTable('recycling_printers', "office_id=$_SESSION[officeId] AND status_id=1", 'printer', 'printer');
/*
	$d_cartridges = FetchMysqlTable('recycling_cartridges', "office_id=$_SESSION[officeId]", 'inv_num');
	if (is_array($d_cartridges)) {
		foreach ($d_cartridges as $id => $d) {
			$d_cartridges_print[$id] = '<b>' . $d_cartridges[$id]['inv_num'] . '</b> (' . $d_cartridges[$id]['cartridge'] . ')';
		}
	}
*/	
//	$d_admins = FetchMysqlTable('recycling_admins', NULL, 'fio', 'fio');



	/*
	$nevents = isset($_POST['nevents']) ? (int)$_POST['nevents'] : 100;
	if ($nevents <= 0) $nevents = 100;
	*/
	



<form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<table><tr valign="top">
<td>

	<table><tr valign="top"><td>
	<fieldset>
	<legend>офис:</legend>
	<select name='office_id' onChange='form1.submit();'>
	<?php
	foreach ($d_offices as $id => $office) {
		echo "<option value='$id'" . (($id == $_SESSION['officeId']) ? " selected" : '') . ">$office</option>";
	}
	?>
	</select>
	</fieldset>
	</td><td>
	<fieldset>
	<legend>одмин:</legend>
	<select name='admin_id' onChange='form1.submit();'>
	<?php
	foreach ($d_admins as $id => $admin) {
		echo "<option value='$id'" . (($id == $_SESSION['adminId']) ? " selected" : '') . ">$admin</option>";
	}
	?>
	</select>
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
	<select name='printer_id' onChange='form1.submit();'>
	<?php
	foreach ($d_printers_active as $id => $printer) {
		echo "<option value='$id'" . (($id == $printer_id) ? ' selected' : '') . ">$printer</option>";
	}
	?>
	</select>
	<br>
	картридж out:
	<?php
	$cartridge_inside_id = $DB->result("SELECT cartridge_inside FROM recycling_printers WHERE id=$printer_id");
	?>

	<input type="hidden" name="cartridge_out_id" value="<?php echo $cartridge_inside_id; ?>">
	<b><?php echo $d_cartridges_print[$cartridge_inside_id]; ?></b>

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

</td>
<td>
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
</form>
-->
