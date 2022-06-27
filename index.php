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
		
		$dateVariables = array('checkDate', 'checkDateAdded', 'breakDate');
		foreach($dateVariables as $dateVariable) $$dateVariable = isset($_POST[$dateVariable]) ? $_POST[$dateVariable] : '';		


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
		'companyRefId',
		'printerId',
		'checkId',
		'breakId',
		'isPay',
		'brandId'
		);
		foreach($intVariables as $intVariable) $$intVariable = isset($_POST[$intVariable]) ? (int)$_POST[$intVariable] : 0;
		
		$textVariables = array(
		'remark',
		'modelCode',
		'modelName',
		'cartridgeName',
		'printerName',
		'breakName',
		'checkName',
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
			$DB->query("INSERT INTO `recycling_breaks_content`(`eventId`) VALUES ('$event_id')");			
		} elseif (isset($_POST['cmdAddCartridge'])){
			// Добавление картриджа
			if($cartridgeInvNum == 0) $cartridgeInvNum = $DB->result("SELECT IFNULL(MAX(inv_num) + 1, 1) FROM recycling_cartridges WHERE `office_id`='$_SESSION[officeRegcartridgeId]'");
			if($cartridgeModelId == 0){ print "Нужно выбрать модель картриджа";	exit; }
			if($brandId == 0){ print "Нужно производителя конкретно этого экземпляра картриджа"; exit; }			
			$cartridgeNewId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_cartridges`");
			$DB->query("INSERT INTO recycling_cartridges (`cartridge_model_id`, `brandId`, `office_id`, `inv_num`, `status_id`) VALUES ('$cartridgeModelId', '$brandId', '$_SESSION[officeRegcartridgeId]', '$cartridgeInvNum', '2')");
			$DB->query("INSERT INTO recycling_events ( `office_id`, `dt`, `cartridge_new_id`, `reason_id`, `status_id` ) VALUES ('$_SESSION[officeRegcartridgeId]', '$dt', '$cartridgeNewId', '$reason_new_id', '5')");
			$event_id = $DB->result("SELECT id FROM recycling_events WHERE `office_id`='$_SESSION[officeRegcartridgeId]' AND `dt`='$dt' AND `cartridge_new_id`='$cartridgeNewId' ORDER BY id DESC LIMIT 1");
			$DB->query("INSERT INTO recycling_events_admins (`event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ('$event_id', '$dt', '$_SESSION[adminId]', '5', '".$DB->escape($remark)."')");
			header("Location: ?w=journal#event_".$event_id);
			exit;
//		} elseif (isset($_POST['cmdPay'])) {
//			$event_id = key($_POST['cmdPay']);
//			$DB->query("UPDATE recycling_events SET `is_paid`='1' WHERE `id`='$event_id'");
//			$DB->query("INSERT INTO recycling_events_admins ( `event_id`, `dt`, `admin_id`, `event_typ_id`, `remark` ) VALUES ( '$event_id', '$dt', '$_SESSION[adminId]', '4', '".$DB->escape($remark)."' )");
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
		} elseif(isset($_POST['cmdSavePrinterParams'])){
			
			
	
			if($modelId == 0 || $officeId == 0 || $companyId == 0 || $statusId == 0){
				print "Поля Модель, Офис, Статус или Владелец не могут быть пустыми. Выберите значения в них.";
				exit;
			}
			
			if(mb_strlen($printerName) <= 2){
				print "Слишком короткое название принтера.";
				exit;				
			}
			
			if($DB->num_rows("SELECT * FROM `recycling_printers` WHERE `printerName`='".$DB->escape($printerName)."' AND `officeId`='$officeId'") > 0 && $printerId == 0){
				print "Такое имя принтера уже есть.";
				exit;					
			}
			
			if($printerId == 0){
				$printerId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_printers`");
				$DB->query("INSERT INTO `recycling_printers` (`id`) VALUES('$printerId')");
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

			header("Location: ?w=printers#printer_".$printerId);
			exit;				
			
		} elseif (isset($_POST['cmdAddEventsIntoCheck'])){			
			if($checkId > 0 && $breakId > 0){				
				$events = array();				
				if(isset($_POST['events'])) $events = (array)$_POST['events'];
				if(count($events) == 0){
					$DB->query("UPDATE `recycling_breaks_content` SET `checkId`=NULL WHERE `breakId`='$breakId'");
				}else{
					$DB->query("UPDATE `recycling_breaks_content` SET `checkId`='$checkId' WHERE `eventId` IN (".implode(",", $events).")");
				}
			}		
		} elseif (isset($_POST['cmdEditCheck'])){
			$checkSumm = floatval($_POST['checkSumm']);
			if($checkDate == ''){ print "Укажите дату выставления счёта"; exit; }
			if(mb_strlen($checkName) < 5){ print "Слишком короткое название счета"; exit; }
			if($checkSumm == 0){ print "Сумма счета не может быть 0"; exit; }
			if($companyId == 0){ print "Укажите компанию, для которой выставлен счёт"; exit; }			
			if($companyRefId == 0){ print "Укажите компанию, которая выставила счёт"; exit; }
			if($isPay == 0){ print "Укажите, оплачен ли на данный момент этот счёт"; exit; }			
			if($checkId == 0){
				$checkId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_checks`");
				$DB->query("INSERT INTO `recycling_checks`(`id`) VALUES('$checkId')");
			}			
			$DB->query("UPDATE `recycling_checks` SET `checkDate`='$checkDate', `checkDateAdded`='$checkDateAdded', `checkName`='".$DB->escape($checkName)."', `checkSumm`='$checkSumm', `companyRefId`='$companyRefId', `isPay`='$isPay', `companyId`='$companyId', `breakId`='$breakId' WHERE `id`='$checkId'");
			
			if(isset($_FILES)){				
				setlocale(LC_ALL, 'ru_RU.utf8'); /* Русская локаль для корректной работы basename() */
				$mimeType = mime_content_type($_FILES['userfile']['tmp_name']);
				if(in_array($mimeType, $allowCheckFileTypes)){
					if (move_uploaded_file($_FILES['userfile']['tmp_name'], __DIR__."/storage/".ca_check_path_create($checkId).'.bin')){
						$DB->query("UPDATE `recycling_checks` SET `fileName`='".$DB->escape(basename($_FILES['userfile']['name']))."', `fileSize`='".$_FILES['userfile']['size']."', `mimeType`='$mimeType' WHERE `id`='$checkId'");						
					}					
				}
			}			
			header("Location: ?w=payments#check_".$checkId);
			exit;			
		} elseif (isset($_POST['cmdAddEventsIntoBreak'])){
			if($officeId > 0 && mb_strlen($breakName) > 5){
				$events = (isset($_POST['events'])) ? (array)$_POST['events'] : array();
				if(count($events) == 0){
					print "Нужно выбрать события для внесения в разбивку";
					exit;					
				}				
				$breakId = $DB->result("SELECT IFNULL(MAX(id) + 1, 1) FROM `recycling_breaks`");
				$DB->query("INSERT INTO `recycling_breaks`(`id`,`breakName`,`officeId`) VALUES ('$breakId','".$DB->escape($breakName)."','$officeId')");
				$DB->query("UPDATE `recycling_breaks_content` SET `breakId`='$breakId' WHERE `eventId` IN ('".implode("','", $events)."')");
				$DB->query("UPDATE `recycling_breaks` SET `breakDate`=UNIX_TIMESTAMP('$breakDate') WHERE `id`='$breakId'");
				header("Location: ?w=breaks&id=".$breakId);
				exit;				
			}else{
				print "Не указан офис либо слишком короткое название разбивки";
				exit;
			}
		} elseif (isset($_POST['cmdAddWorksIntoEvent'])){
			$eventId = (int)$_POST['eventId'];
			$worksIds = isset($_POST['workIds']) ? $_POST['workIds'] : array();
			$DB->query("DELETE FROM `recycling_cartridges_works` WHERE `eventId`='$eventId'");
			if(count($worksIds) > 0) $DB->query("INSERT INTO `recycling_cartridges_works` (`eventId`, `workId`) VALUES ('$eventId','".implode("'),('$eventId','", $worksIds)."')");
			$DB->query("UPDATE `recycling_breaks_content` SET worksCount=(SELECT COUNT(*) FROM `recycling_cartridges_works` WHERE `recycling_cartridges_works`.`eventId`=`recycling_breaks_content`.`eventId`) WHERE `recycling_breaks_content`.`eventId`='$eventId'");
			exit;			
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
		}elseif(isset($_GET['getCheck'])){
			$checkId = (int)$_GET['getCheck'];
			if($checkId > 0){
				if(is_file(__DIR__."/storage/".ca_check_path($checkId).'.bin')){
					$fileParams = $DB->fetch_assoc("SELECT `fileName`, `fileSize`, `mimeType` FROM `recycling_checks` WHERE `id`='$checkId'");					
					header('Content-Type: '.$fileParams['mimeType']);
					header('Content-Disposition: attachment; filename="'.$fileParams['fileName'].'"');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: '.$fileParams['fileSize']);					
					print file_get_contents(__DIR__."/storage/".ca_check_path($checkId).'.bin');
				}
			}		
			exit;
		}elseif(isset($_GET['cartridgeHistory'])){			
			$pageTitle = 'История работ с картриджем';			
			$cartridgeId = (int)$_GET['cartridgeHistory'];			
			$content = show_cartridges_statistic($cartridgeId);			
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
	<div style="padding: 5px; text-align: center;">

		<?php
		
		if($_SESSION['superAdm'] > 0) echo '
			<div class="dbMenu floatRight">
			<div onclick="showDbMenu()" class="dropBtn">БД</div>
			  <div id="dbMenu" class="dbMenu-content">
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