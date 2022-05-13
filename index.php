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
<link href="assets/<?php echo $projectName; ?>/css/main.css" rel="stylesheet" type="text/css" />
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
?>
</body>
</html>