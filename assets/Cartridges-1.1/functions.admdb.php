<?php

function showAdmDbCartridgeAssignList($printerModelId = 0){
	global $DB, $pageTitle, $portalCartridgeColors;
	$pageTitle = 'Связи принтера и картриджей';
	
	if($printerModelId == 0) return;
	
	$printerName = $DB->result("SELECT modelName FROM `recycling_printers_models` WHERE `id`='$printerModelId'");
	
$cartridgesList = $DB->query("SELECT 
currentCartridges.cartridgeModelId AS mark,
`recycling_cartridges_models`.* FROM `recycling_cartridges_models`
LEFT JOIN (SELECT * FROM `recycling_printers_cartridges` WHERE printerModelId='$printerModelId') AS currentCartridges 
ON currentCartridges.cartridgeModelId=`recycling_cartridges_models`.`id` ORDER BY cartridgeName");	
	
	$return = '<fieldset class="editFormFieldset"><legend>Отметьте картриджи, подходящие для <b>'.$printerName.'</b></legend>
		<form method="post" id="editForm" action="'.$_SERVER['PHP_SELF'].'">
		<input type="hidden" name="printerModelId" value="'.$printerModelId.'">';
		
		while($cartridge = $DB->fetch_assoc($cartridgesList)){
			$return .= '
	<div>
      <input type="checkbox" id="cartridge_'.$cartridge['id'].'" name="cartridgeIdsArray[]" value="'.$cartridge['id'].'"'.(($cartridge['mark'] > 0) ? ' checked' : '').'>
      <label for="cartridge_'.$cartridge['id'].'"><b>'.$cartridge['cartridgeName'].'</b> '.$cartridge['cartridgeCapacity'].' стр., ('.$portalCartridgeColors[$cartridge['cartridgeColor']]['rus'].')</label>
    </div>';
		}

		$return .= '<br/>
		<input type="submit" name="cmdAssignPrinterModelWithCartridges" value="Сохранить"></form></fieldset>';	
	return $return;
}


function showAdmDbPrintersList($printerModelId = 0){
	global $DB, $pageTitle, $portalYesNo, $portalPrinterTypes;
	$pageTitle = 'Список моделей принтеров';
	
	$printerModels = $DB->query("SELECT
 `recycling_printers_models`.*,
`a`.`allCount`,
`b`.`workingCount`,
`c`.`brokenCount`,
`d`.`repairCount`,
`e`.`reserveCount`,
`f`.`outCount`,
`cartridgesList`.`cartridgesList`
 FROM
`recycling_printers_models`
LEFT JOIN (SELECT COUNT(*) AS allCount, `recycling_printers`.`modelId` FROM `recycling_printers` GROUP BY `recycling_printers`.`modelId`)
 AS `a` ON `a`.`modelId`=`recycling_printers_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS workingCount, `recycling_printers`.`modelId` FROM `recycling_printers` WHERE `statusId`='2' GROUP BY `recycling_printers`.`modelId`)
 AS `b` ON `b`.`modelId`=`recycling_printers_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS brokenCount, `recycling_printers`.`modelId` FROM `recycling_printers` WHERE `statusId`='3' GROUP BY `recycling_printers`.`modelId`)
 AS `c` ON `c`.`modelId`=`recycling_printers_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS repairCount, `recycling_printers`.`modelId` FROM `recycling_printers` WHERE `statusId`='4' GROUP BY `recycling_printers`.`modelId`)
 AS `d` ON `d`.`modelId`=`recycling_printers_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS reserveCount, `recycling_printers`.`modelId` FROM `recycling_printers` WHERE `statusId`='5' GROUP BY `recycling_printers`.`modelId`)
 AS `e` ON `e`.`modelId`=`recycling_printers_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS outCount, `recycling_printers`.`modelId` FROM `recycling_printers` WHERE `statusId`='1' GROUP BY `recycling_printers`.`modelId`)
 AS `f` ON `f`.`modelId`=`recycling_printers_models`.`id`
LEFT JOIN (SELECT GROUP_CONCAT(CONCAT('{\"',cartridgeName,'\":',cartridgeColor,'}') SEPARATOR ';') AS cartridgesList, printerModelId FROM `recycling_printers_cartridges`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_printers_cartridges`.`cartridgeModelId`
GROUP BY printerModelId) AS `cartridgesList` ON `cartridgesList`.`printerModelId`=`recycling_printers_models`.`id`
ORDER BY modelName");
 
	$return = '<fieldset><legend>База данных - Модели принтеров</legend>
				<table class="mainTable zebra" id="ajaxedRows">
					<thead>
						<tr>
							<th>Наименование модели</th>
							<th>Код модели</th>							
							<th>Тип</th>							
							<th>Сеть</th>							
							<th>Цветность</th>							
							<th>Всего</th> 
							<th>В работе</th>
							<th>Ожидает ремонта</th>							
							<th>Отдан в ремонт</th>
							<th>В резерве</th>
							<th>Выведен из оборота</th>
							<th>Картриджи</th>
						</tr>
					</thead>
					<tbody>';
				if($DB->num_rows($printerModels) > 0){
					while($printerModel = $DB->fetch_assoc($printerModels)){				
						$return .= "
						<tr data-id=\"$printerModel[id]\" data-target=\"getPrinterModelEditElement\" id=\"model_$printerModel[id]\" class=\"blinking\">
							<td><a href=\"javascript://\" class=\"add-row\">$printerModel[modelName]</a></td>
							<td>$printerModel[modelCode]</td>
							<td>".$portalPrinterTypes[$printerModel['deviceType']]."</td>							
							<td>".$portalYesNo[$printerModel['hasNetwork']]."</td>
							<td>".$portalYesNo[$printerModel['hasColor']]."</td>							
							<td>".(int)$printerModel['allCount']."</td>
							<td>".(int)$printerModel['workingCount']."</td>	
							<td>".(int)$printerModel['brokenCount']."</td>
							<td>".(int)$printerModel['repairCount']."</td>							
							<td>".(int)$printerModel['reserveCount']."</td>
							<td>".(int)$printerModel['outCount']."</td>
							<td><a href=\"?admDb=cartridgeList&amp;id=$printerModel[id]\">".showCartridgeListTxt($printerModel['cartridgesList'])."</a></td>
						</tr>";
					}
				}else{
					$return .= '<tr><td colspan="12" style="text-align: center;"><i>Нет записей. Добавьте новую модель принтера используя форму ниже</i></td></tr>';					
				}

				$return .= '
						<tr data-id="new" data-target="getPrinterModelEditElement">
							<td colspan="12" style="text-align: right;">[<a href="javascript://" class="add-row">Добавить модель</a>]</td>
						</tr>
					</tbody>
				</table>
	</fieldset>
	';	
	return $return;
}

function showCartridgeListTxt($cartridgesList = ''){
	global $portalCartridgeColors;
	$return = array();
	$cartridges = explode(';', $cartridgesList);
	foreach($cartridges as $cartridge){
		if($cartridgeParam = json_decode($cartridge, true)){
			$return[] = '<span class="cartridgeCircle '.$portalCartridgeColors[$cartridgeParam[key($cartridgeParam)]]['class'].'"></span><span>'.key($cartridgeParam).'</span>';
		}
	}
	$return = implode(", ", $return);
	return empty($return) ? '<i>нет данных</i>' : $return;
}

function showAdmDbCartridgesList($cartridgeModelId = 0){
	global $DB, $pageTitle, $portalCartridgeColors;
	$pageTitle = 'Список моделей картриджей';
	foreach($portalCartridgeColors as $key => $value) $colorVariants[$key] = $value['rus'];
	
	$cartridgeModels = $DB->query("SELECT
 `recycling_cartridges_models`.*,
`a`.`allCount`,
`b`.`reserveCount`,
`b`.`reserveInvs`,
`c`.`inPrinterCount`,
`c`.`inPrinterInvs`,
`d`.`outCount`
 FROM
`recycling_cartridges_models`
LEFT JOIN (SELECT COUNT(*) AS allCount, `recycling_cartridges`.`cartridge_model_id` FROM `recycling_cartridges` GROUP BY `recycling_cartridges`.`cartridge_model_id`)
 AS `a` ON `a`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS reserveCount, GROUP_CONCAT(inv_num SEPARATOR ', ') AS reserveInvs, `recycling_cartridges`.`cartridge_model_id` FROM `recycling_cartridges` WHERE `status_id`='2' GROUP BY `recycling_cartridges`.`cartridge_model_id`)
 AS `b` ON `b`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS inPrinterCount, GROUP_CONCAT(inv_num SEPARATOR ', ') AS inPrinterInvs, `recycling_cartridges`.`cartridge_model_id` FROM `recycling_cartridges` WHERE `status_id`='4' GROUP BY `recycling_cartridges`.`cartridge_model_id`)
 AS `c` ON `c`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
LEFT JOIN (SELECT COUNT(*) AS outCount, `recycling_cartridges`.`cartridge_model_id` FROM `recycling_cartridges` WHERE `status_id`='3' GROUP BY `recycling_cartridges`.`cartridge_model_id`)
 AS `d` ON `d`.`cartridge_model_id`=`recycling_cartridges_models`.`id`
 ORDER BY cartridgeName");
 
	$return = '
			<fieldset><legend>База данных - Модели картриджей</legend>
			<table class="mainTable zebra" id="ajaxedRows">
					<thead>
						<tr>
							<th>Модель</th>
							<th>Ресурс</th>
							<th>Цветность</th>
							<th>Всего</th>
							<th>Резерв</th>
							<th>В аппаратах</th>
							<th>Вне оборота</th>
						</tr>
					</thead>
					<tbody>';
				if($DB->num_rows($cartridgeModels) > 0){
					while($cartridgeModel = $DB->fetch_assoc($cartridgeModels)){
						$return .= "
						<tr data-id=\"$cartridgeModel[id]\" data-target=\"getCartridgeModelEditElement\" id=\"model_$cartridgeModel[id]\" class=\"blinking\">
							<td><a class=\"add-row\" href=\"javascript://\">$cartridgeModel[cartridgeName]</a></td>
							<td>$cartridgeModel[cartridgeCapacity]</td>
							<td>".$colorVariants[$cartridgeModel['cartridgeColor']]."</td>										
							<td>$cartridgeModel[allCount]</td>
							<td>$cartridgeModel[reserveCount]</td>	
							<td>$cartridgeModel[inPrinterCount]</td>
							<td>$cartridgeModel[outCount]</td>	
						</tr>";
					}
				}else{
					$return .= '<tr><td colspan="7" style="text-align: center;"><i>Нет записей</i></td></tr>';					
				}

				$return .= '
						<tr data-id="new" data-target="getCartridgeModelEditElement">
							<td colspan="7" style="text-align: right;">[<a href="javascript://" class="add-row">Добавить модель</a>]</td>
						</tr>				
					</tbody>
				</table>
	</fieldset>';	
	return $return;
}

function ajax_GET_getCartridgeModelEditElement(){
	global $DB, $portalCartridgeColors;
	$cartridgeModelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	foreach($portalCartridgeColors as $key => $value) $colorVariants[$key] = $value['rus'];
	
	$modelParams = array('cartridgeName' => '', 'cartridgeCapacity' => '', 'cartridgeColor' => 0);
	if($cartridgeModelId > 0) $modelParams = $DB->fetch_assoc("SELECT * FROM `recycling_cartridges_models` WHERE `id`='$cartridgeModelId'");
	return '
	<fieldset class="editFormFieldset">
	<form method="post" action="index.php">
	<input type="hidden" name="cartridgeModelId" value="'.$cartridgeModelId.'">
		<table class="mainTable">
			<caption>Редактирование картриджа</caption>				
			<tbody>
				<tr><td>Модель</td><td><input type="text" name="cartridgeName" value="'.$modelParams['cartridgeName'].'"></td></tr>
				<tr><td>Ёмкость</td><td><input type="text" name="cartridgeCapacity" value="'.$modelParams['cartridgeCapacity'].'"></td></tr>
				<tr><td>Цвет</td><td>'.show_select((int)$modelParams['cartridgeColor'], $colorVariants, 'cartridgeColor').'</td></tr>
				<tr><td colspan="2"><input type="submit" name="cmdEditCartridgeModel" value="Сохранить">'.(($cartridgeModelId > 0) ? ' <input type="submit" name="cmdDeleteCartridgeModel" value="Удалить">' : '').'</td></tr>
			</tbody>
		</table>
	</form>
	</fieldset>';
}


function ajax_GET_getPrinterModelEditElement(){
	global $DB, $portalYesNo, $portalPrinterTypes;
	$printerModelId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$modelParams = array('modelName' => '', 'modelCode' => '', 'deviceType' => 0, 'hasNetwork' => 0, 'hasColor' => 0);
	if($printerModelId > 0) $modelParams = $DB->fetch_assoc("SELECT * FROM `recycling_printers_models`
LEFT JOIN (SELECT GROUP_CONCAT(CONCAT('{\"',cartridgeName,'\":',cartridgeColor,'}') SEPARATOR ';') AS cartridgesList, printerModelId FROM `recycling_printers_cartridges`
LEFT JOIN `recycling_cartridges_models` ON `recycling_cartridges_models`.`id`=`recycling_printers_cartridges`.`cartridgeModelId`
GROUP BY printerModelId) AS `cartridgesList` ON `cartridgesList`.`printerModelId`=`recycling_printers_models`.`id`
WHERE `id`='$printerModelId'");

	return '
	<fieldset class="editFormFieldset">
	<form method="post" action="index.php">
		<input type="hidden" name="printerModelId" value="'.$printerModelId.'">
		<table class="mainTable">
			<caption>'.(($printerModelId == 0) ? 'Добавление новой модели принтера' : 'Редактирование модели').'</caption>
			<tbody>
				<tr><td>Модель</td><td><input type="text" name="modelName" placeholder="Наименование, например HP LaserJet Pro M426fdn" value="'.$modelParams['modelName'].'"></td></tr>
				<tr><td>Код модели</td><td><input type="text" name="modelCode" placeholder="Код модели производителя, например F6W14A" value="'.$modelParams['modelCode'].'"></td></tr>
				<tr><td>Тип</td><td>'.show_select((int)$modelParams['deviceType'], $portalPrinterTypes, 'deviceType').'</td></tr>
				<tr><td>Сеть</td><td>'.show_select((int)$modelParams['hasNetwork'], $portalYesNo, 'hasNetwork').'</td></tr>
				<tr><td>Цвет</td><td>'.show_select((int)$modelParams['hasColor'], $portalYesNo, 'hasColor').'</td></tr>
				'.(($printerModelId == 0) ? '' : '<tr><td>Картриджи</td><td><a href="?admDb=cartridgeList&id='.$printerModelId.'">'.showCartridgeListTxt($modelParams['cartridgesList']).'</a></td></tr>').'
				<tr><td colspan="2"><input type="submit" name="cmdEditPrinterModel" value="Сохранить">'.(($printerModelId > 0) ? ' <input type="submit" name="cmdDeletePrinterModel" value="Удалить">' : '').'</td></tr>
			</tbody>
		</table>
	</form>
	</fieldset>';	
}

?>