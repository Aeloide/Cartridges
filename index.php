<?php
	require("global.php");

	$pageMenu = array(
		'journal' => array('title' => 'Журнал', 'head' => 'Журнал движения картриджей'),
		'printers' => array('title' => 'Принтеры', 'head' => 'Список принтеров'),
		'cartridges' => array('title' => 'Картриджи', 'head' => 'Список картриджей'),
		'breaks' => array('title' => 'Разбивки', 'head' => 'Разбивка счетов по компаниям'),
		'payments' => array('title' => 'Счета', 'head' => 'Выставленные счета на оплату'),
		'options' => array('title' => 'Опции', 'head' => 'Настройки и опции отображения')
	);
	
	if($_SERVER['REQUEST_METHOD'] == 'POST'){

		$_SESSION['printerRCId'] = isset($_POST['printerRCId']) ? $_POST['printerRCId'] : $_SESSION['printerRCId'];
		$_SESSION['officeRegcartridgeId'] = isset($_POST['officeRegcartridgeId']) ? $_POST['officeRegcartridgeId'] : $_SESSION['officeRegcartridgeId'];

		$dt = isset($_POST['dt']) ? $_POST['dt'] : time();

		$intVariables = array(
		'pages',
		'reason_id',
		'reason_new_id',
		'cartridge_out_id',
		'cartridge_in_id',
		'cartridgeColor',
		'cartridgeCapacity',
		'cartridgeModelId',		
		'printerModelId',
		'deviceType',
		'hasNetwork',
		'hasColor',
		'adminsAuthId',
		'cartridgeInvNum',
		'officeId',
		'modelId',
		'statusId',
		'companyId',
		'printerId'
		);
		foreach($intVariables as $intVariable) $$intVariable = isset($_POST[$intVariable]) ? (int)$_POST[$intVariable] : 0;
		
		$textVariables = array(
		'remark',
		'modelCode',
		'modelName',
		'cartridgeName',
		'printerName',
		'ip',
		'sn'
		);
		foreach($textVariables as $textVariable) if(isset($_POST[$textVariable])) $$textVariable = mb_substr($_POST[$textVariable], 0, 50);
	
		if (isset($_POST['cmdAddEvent'])){
			$officeId = $DB->result("SELECT `officeId` FROM `recycling_printers` WHERE `id`='$_SESSION[printerRCId]'");
			// создание события (замена картриджа)
			$DB->query("INSERT INTO recycling_events ( `office_id`, `printer_id`, `dt`, `cartridge_out_id`, `cartridge_in_id`, `reason_id`, `pages` ) VALUES ( '$officeId', '$_SESSION[printerRCId]', '$dt', '$cartridge_out_id', '$cartridge_in_id', '$reason_id', '$pages' )");
			$event_id = $DB->result("SELECT id FROM recycling_events WHERE `office_id`='$officeId' AND `printer_id`='$_SESSION[printerRCId]' AND `cartridge_out_id`='$cartridge_out_id' AND `cartridge_in_id`='$cartridge_in_id' ORDER BY id DESC LIMIT 1");
			$DB->query("INSERT INTO recycling_events_admins ( `event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ( '$event_id', '$dt', '$_SESSION[adminId]', '0', '".$DB->escape($remark)."' )");
			$DB->query("UPDATE recycling_cartridges SET `status_id`='0' WHERE `id`='$cartridge_out_id'");
			$DB->query("UPDATE recycling_cartridges SET `status_id`='4' WHERE `id`='$cartridge_in_id'");
			$DB->query("UPDATE recycling_printers SET `cartridge_inside`='$cartridge_in_id' WHERE `id`='$_SESSION[printerRCId]'");
		} elseif (isset($_POST['cmdAddCartridge'])){
			// Добавление картриджа
			if($cartridgeInvNum == 0) $cartridgeInvNum = $DB->result("SELECT IFNULL(MAX(inv_num) + 1, 1) FROM recycling_cartridges WHERE `office_id`='$_SESSION[officeRegcartridgeId]'");
			if($cartridgeModelId == 0){	print "Нужно выбрать модель картриджа";	exit; }
			$cartridgeNewId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_cartridges`");
			$DB->query("INSERT INTO recycling_cartridges (`cartridge_model_id`, `office_id`, `inv_num`, `status_id`) VALUES ('$cartridgeModelId', '$_SESSION[officeRegcartridgeId]', '$cartridgeInvNum', '2')");
			$DB->query("INSERT INTO recycling_events ( `office_id`, `dt`, `cartridge_new_id`, `reason_id`, `status_id` ) VALUES ('$_SESSION[officeRegcartridgeId]', '$dt', '$cartridgeNewId', '$reason_new_id', '5')");
			$event_id = $DB->result("SELECT id FROM recycling_events WHERE `office_id`='$_SESSION[officeRegcartridgeId]' AND `dt`='$dt' AND `cartridge_new_id`='$cartridgeNewId' ORDER BY id DESC LIMIT 1");
			$DB->query("INSERT INTO recycling_events_admins (`event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ('$event_id', '$dt', '$_SESSION[adminId]', '5', '".$DB->escape($remark)."')");
			header("Location: ?w=journal#event_".$event_id);
			exit;
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
			$userParamsQ = $DB->query("SELECT * FROM `recycling_admins` WHERE `id`='$adminsAuthId'");
			if($DB->num_rows($userParamsQ) > 0){
				$userParams = $DB->fetch_assoc($userParamsQ);
				if(passHash($_POST['adminsAuthPass'], $userParams['salt']) == $userParams['pass']){
					$_SESSION['adminId'] = $adminsAuthId;
					$_SESSION['superAdm'] = $userParams['superAdm'];
					$DB->query("UPDATE `recycling_admins` SET `datelogin`=NOW(), `lastip`=INET_ATON('$_SERVER[REMOTE_ADDR]') WHERE `id`='$adminsAuthId'");
				}
			}
		} elseif (isset($_POST['cmdAddAdmin'])){
			addNewUser();
		} elseif (isset($_POST['cmdAddOffice'])){
			addNewOffice($_POST['newOfficeName']);
		} elseif (isset($_POST['cmdEditCartridgeModel']) && $_SESSION['superAdm'] == 1){
			if($cartridgeModelId > 0){
				saveCartridgeParams($cartridgeName, $cartridgeColor, $cartridgeCapacity, $cartridgeModelId);			
				header("Location: ?admDb=cartridges&id=$cartridgeModelId#model_".$cartridgeModelId);			
				exit;
			}
		} elseif (isset($_POST['cmdDeleteCartridgeModel']) && $_SESSION['superAdm'] == 1){
			if($DB->num_rows("SELECT * FROM `recycling_cartridges` WHERE cartridge_model_id='$cartridgeModelId'") == 0){
				$DB->query("DELETE FROM `recycling_cartridges_models` WHERE `id`='$cartridgeModelId'");
				header("Location: ?admDb=cartridges");			
			}else{
				print "Нельзя удалить модель, у которой есть записи в таблице картриджей. Вначале удалите картриджи.";
			}
			exit;
		} elseif (isset($_POST['cmdAddNewCartridgeModel']) && $_SESSION['superAdm'] == 1){			
			if($DB->num_rows("SELECT * FROM `recycling_cartridges_models` WHERE `cartridgeName`='".$DB->escape($cartridgeName)."'") > 0){ print "Такая модель уже есть"; exit; }
			$cartridgeModelId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_cartridges_models`");
			saveCartridgeParams($cartridgeName, $cartridgeColor, $cartridgeCapacity, $cartridgeModelId);			
			header("Location: ?admDb=cartridges&id=$cartridgeModelId#model_".$cartridgeModelId);			
			exit;			
		} elseif (isset($_POST['cmdEditPrinterModel']) && $_SESSION['superAdm'] == 1){

			if($printerModelId > 0){
				
				
				
				$DB->query("UPDATE `recycling_printers_models` SET `modelName`='".$DB->escape($modelName)."', `modelCode`='".$DB->escape($modelCode)."', `deviceType`='$deviceType', `hasNetwork`='$hasNetwork', `hasColor`='$hasColor' WHERE `id`='$printerModelId'");				


				header("Location: ?admDb=printers&id=$printerModelId#editForm");
				exit;				
			}
			
			
		} elseif (isset($_POST['cmdAddNewPrinterModel']) && $_SESSION['superAdm'] == 1){
			

			
			if($DB->num_rows("SELECT * FROM `recycling_printers_models` WHERE `modelName`='".$DB->escape($modelName)."'") > 0){ print "Такая модель уже есть"; exit; }
			$printerModelId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_printers_models`");
			
			$DB->query("INSERT IGNORE INTO `recycling_printers_models`(`id`) VALUES('$printerModelId')");

			$DB->query("UPDATE `recycling_printers_models` SET `modelName`='".$DB->escape($modelName)."', `modelCode`='".$DB->escape($modelCode)."', `deviceType`='$deviceType', `hasNetwork`='$hasNetwork', `hasColor`='$hasColor' WHERE `id`='$printerModelId'");				
			header("Location: ?admDb=printers&id=$printerModelId#model_".$printerModelId);	
			exit;	
		} elseif (isset($_POST['cmdDeletePrinterModel']) && $_SESSION['superAdm'] == 1){
			if($DB->num_rows("SELECT * FROM `recycling_printers` WHERE `modelId`='$printerModelId'") == 0){
				$DB->query("DELETE FROM `recycling_printers_models` WHERE `id`='$printerModelId'");
				header("Location: ?admDb=printers");			
			}else{
				print "Нельзя удалить модель, у которой есть записи в таблице принтеров. Вначале удалите принтеры.";
			}
			exit;
		} elseif(isset($_POST['cmdAssignPrinterModelWithCartridges']) && $_SESSION['superAdm'] == 1){
			$DB->query("DELETE FROM `recycling_printers_cartridges` WHERE `printerModelId`='$printerModelId'");
			if(is_array($_POST['cartridgeIdsArray'])){
				foreach($_POST['cartridgeIdsArray'] as $cartridgeModelId){
					$DB->query("INSERT INTO `recycling_printers_cartridges`(`printerModelId`,`cartridgeModelId`) VALUES ('$printerModelId','".(int)$cartridgeModelId."')");
				}			
			}
		} elseif($_POST['cmdSavePrinterParams']){
			
			
			
			//print_r($_POST);	
			
			if($modelId == 0 || $officeId == 0 || $companyId == 0 || $statusId == 0){
				print "Поля Модель, Офис, Статус или Владелец не могут быть пустыми. Выберите значения в них.";
				exit;
			}
			
			if(mb_strlen($printerName) <= 2){
				print "Слишком короткое название принтера.";
				exit;				
			}
			
			if($DB->num_rows("SELECT * FROM `recycling_printers` WHERE `printerName`='".$DB->escape($printerName)."' AND `officeId`='$officeId'")){
				
			}

			$query = "UPDATE `recycling_printers` SET

  `printerName`=".$DB->null_val($printerName).",
  `modelId`='$modelId',
  `officeId`='$officeId',
  `companyId`='$companyId',
  `sn`=".$DB->null_val($sn).",
  `ip`=".$DB->null_val($ip).",
  `statusId`='$statusId' WHERE `id`='$printerId'";
  
			$DB->query($query);
			//print $query;			

			//exit;
			
			
		}
		header("Location: ".$_SERVER['HTTP_REFERER']);
		exit;
	}elseif($_SERVER['REQUEST_METHOD'] == 'GET'){
		if(isset($_GET['quit'])){
			session_destroy();
			header("Location: ".$_SERVER['HTTP_REFERER']);
			exit;
		}elseif(isset($_GET['statistic']) && isset($_SESSION['adminId'])){			
			if($_GET['statistic'] == 'printer'){
				$id = (int)$_GET['id'];
				$pageTitle = 'График напечатанных страниц';
				$content = portalShowPrinterStatistics($id);
			}			
		}elseif(isset($_GET['admDb']) && $_SESSION['superAdm'] == 1){
			$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
			if($_GET['admDb'] == 'cartridges') $content = showAdmDbCartridgesList($id);
			if($_GET['admDb'] == 'printers') $content = showAdmDbPrintersList($id);			
			if($_GET['admDb'] == 'cartridgeList') $content = showAdmDbCartridgeAssignList($id);			
		}else{		
			$content = 'Страница не найдена';		
			$contentFunction = isset($_GET['w']) ? $_GET['w'] : 'journal';		
			if(function_exists("portalShow_GET_$contentFunction")){
				$pageMenu[$contentFunction]['active'] = true;
				$content = '<fieldset><legend>Ошибка</legend>Требуется авторизация на странице "<a href="?w=options">Опции</a>"</fieldset>';			
				if(isset($_SESSION['adminId']) or $contentFunction == 'options') $content = call_user_func("portalShow_GET_$contentFunction");
			}
			$pageTitle = $pageMenu[$contentFunction]['head'];
		}
		
	}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $pageTitle; ?></title>
<meta http-equiv="pragma" content="no-cache">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo $pageTitle; ?>">
<meta property="og:site_name" content="Учет картриджей и принтеров" />
<meta property="og:description" content="<?php echo $pageTitle; ?>">
<link href="assets/<?php echo $projectName; ?>/css/main.css" rel="stylesheet" type="text/css" />
<script src="assets/<?php echo $projectName; ?>/js/app.js"></script>
<script src="assets/highcharts/highcharts.js"></script>
<script src="assets/highcharts/accessibility.js"></script>
<link href="assets/highcharts/highcharts.css" rel="stylesheet" type="text/css" />
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body>

<div class="wrapper">
	<div class="box">
	<?php
		if($DB->result("SELECT DATABASE()") == 'cartriges_new'){
			print "	<fieldset  style='background: orange;'><legend>Внимание</legend><font color='red'><b>подключена тестовая база данных, можно жать чо угодно</b></font></fieldset>";
		}		
	?>

	<fieldset>
	<legend>Меню:</legend>
	<div style="padding: 5px; text-align: center;">

		<?php
		
		if($_SESSION['superAdm'] > 0) echo '
			<div class="dropdown" style="float: right;">
			<div onclick="myFunction()" class="dropbtn">БД</div>
			  <div id="myDropdown" class="dropdown-content">
				<a href="?admDb=printers">Принтеры</a>
				<a href="?admDb=cartridges">Картриджи</a>
			  </div>
			</div>';
			
		foreach($pageMenu as $menuElement => $value) $elements[] = ((isset($value['active'])) ? "<b>$value[title]</b>" : '<a href="?w='.$menuElement.'">'.$value['title'].'</a>');
		echo implode(' | ', $elements);
		?>
	</div>
	</fieldset>

	<?php
		print $content;
	?>
	</div>
</div>
</body>
</html>