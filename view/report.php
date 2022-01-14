<!DOCTYPE html>
<?php
/**
  * @desc gather data and build report
*/

	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//fetch data
	
	if (isset($_POST['report'])) {
		if ($conn) {
			switch($_POST['report']) {
				case "Tool Listing Report":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT ID, TOOL, DATE_CREATED, STATUS, REASON, STATUS_DATE, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL <= '" . $_POST['toolTo'] . "' AND TOOL >= '" . $_POST['toolFrom'] . "' ORDER BY TOOL;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					break;
				case "Nickel Usage":
					$fresnelTools = array();
					$reflexTools = array();
					$result = sqlsrv_query($conn, "SELECT ID, DATE_OUT, TOOL_OUT, PART_LENGTH, PART_WIDTH, THICKNESS1, THICKNESS2, THICKNESS3, THICKNESS4, THICKNESS5, THICKNESS6, JOB_NUMBER FROM Electroforming_History WHERE DATE_OUT >= CONVERT(datetime, '" . ($_POST['dateFrom']) . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") AND DATE_OUT <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND TOOL_OUT NOT LIKE 'RF%' ORDER BY DATE_OUT, TOOL_OUT;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$fresnelTools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
						var_dump($_POST);
					}
					$result = sqlsrv_query($conn, "SELECT ID, DATE_OUT, TOOL_OUT, PART_LENGTH, PART_WIDTH, THICKNESS1, THICKNESS2, THICKNESS3, THICKNESS4, THICKNESS5, THICKNESS6, JOB_NUMBER FROM Electroforming_History WHERE DATE_OUT >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") AND DATE_OUT <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND TOOL_OUT LIKE 'RF%' ORDER BY DATE_OUT;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$reflexTools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					break;
				case "E-Forming Cycle":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT Electroforming_History.ID, Electroforming_History.TOOL_OUT, Electroforming_History.TANK, Electroforming_History.STATION, Electroforming_History.DATE_IN, Electroforming_History.DATE_OUT, Electroforming_History.FORMING_DENSITY, Electroforming_History.FORMING_TIME, Electroforming_History.BUILDING_DENSITY, Electroforming_History.STATUS_OUT, Tool_Tree.LOCATION, Tool_Tree.DRAWER, Tank_Stress.STRESS FROM Electroforming_History INNER JOIN Tool_Tree ON Electroforming_History.TOOL_OUT = Tool_Tree.TOOL INNER JOIN Tank_Stress ON Electroforming_History.TANK = Tank_Stress.TANK WHERE Electroforming_History.TOOL_OUT <= '" . $_POST['toolTo'] . "' AND Electroforming_History.TOOL_OUT >= '" . $_POST['toolFrom'] . "' AND CONVERT(date,Tank_Stress.DATE) = CONVERT(date,Electroforming_History.DATE_OUT) ORDER BY TOOL_OUT;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					break;
				case "Reflexite Eform Yield":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, TOOL_OUT, DATE_OUT, TANK, STATUS_IN, STATUS_OUT FROM Electroforming_History WHERE DATE_OUT <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND DATE_OUT >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") AND TOOL_IN LIKE 'RF%' ORDER BY TOOL_IN;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					
					foreach($tools as $id => $tool) {
						$result = sqlsrv_query($conn, "SELECT ID, REASON, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $tool['TOOL_IN'] . "';");
						if ($result) {
							while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
								$tools[$id]['MANDREL']['REASON'] = $row['REASON'];
								$tools[$id]['MANDREL']['LOCATION'] = $row['LOCATION'];
								$tools[$id]['MANDREL']['DRAWER'] = $row['DRAWER'];
							}
						}
						
						$result = sqlsrv_query($conn, "SELECT ID, REASON, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $tool['TOOL_OUT'] . "';");
						if ($result) {
							while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
								$tools[$id]['TOOL']['REASON'] = $row['REASON'];
								$tools[$id]['TOOL']['LOCATION'] = $row['LOCATION'];
								$tools[$id]['TOOL']['DRAWER'] = $row['DRAWER'];
							}
						}
					}
					break;
				case "Toolroom Production Yield":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, TOOL_OUT, STATUS_IN, STATUS_OUT, OPERATOR_IN, OPERATOR_OUT, MACHINE_NUMBER FROM Toolroom_History WHERE DATE_OUT <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND DATE_OUT >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ")" . ($_POST['operator'] ? (" AND (OPERATOR_IN = '" . $_POST['operator'] . "' OR OPERATOR_OUT = '" . $_POST['operator'] . "') ORDER BY MACHINE_NUMBER;") : " ORDER BY MACHINE_NUMBER;"));
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					
					foreach($tools as $id => $tool) {
						$result = sqlsrv_query($conn, "SELECT ID, DATE_CREATED, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $tool['TOOL_IN'] . "';");
						if ($result) {
							while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
								$tools[$id]['MANDREL']['DATE'] = $row['DATE_CREATED'];
								$tools[$id]['MANDREL']['LOCATION'] = $row['LOCATION'];
								$tools[$id]['MANDREL']['DRAWER'] = $row['DRAWER'];
							}
						}
						
						$result = sqlsrv_query($conn, "SELECT ID, DATE_CREATED, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $tool['TOOL_OUT'] . "';");
						if ($result) {
							while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
								$tools[$id]['TOOL']['DATE'] = $row['DATE_CREATED'];
								$tools[$id]['TOOL']['LOCATION'] = $row['LOCATION'];
								$tools[$id]['TOOL']['DRAWER'] = $row['DRAWER'];
							}
						}
					}
					break;
				case "Fresnel Eform Yield":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, TOOL_OUT, DATE_OUT, TANK, STATUS_IN, STATUS_OUT FROM Electroforming_History WHERE DATE_OUT <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND DATE_OUT >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") AND TOOL_IN NOT LIKE 'RF%' ORDER BY TOOL_IN;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					
					foreach($tools as $id => $tool) {
						$result = sqlsrv_query($conn, "SELECT ID, REASON, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $tool['TOOL_IN'] . "';");
						if ($result) {
							while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
								$tools[$id]['MANDREL']['REASON'] = $row['REASON'];
								$tools[$id]['MANDREL']['LOCATION'] = $row['LOCATION'];
								$tools[$id]['MANDREL']['DRAWER'] = $row['DRAWER'];
							}
						}
						
						$result = sqlsrv_query($conn, "SELECT ID, REASON, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $tool['TOOL_OUT'] . "';");
						if ($result) {
							while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
								$tools[$id]['TOOL']['REASON'] = $row['REASON'];
								$tools[$id]['TOOL']['LOCATION'] = $row['LOCATION'];
								$tools[$id]['TOOL']['DRAWER'] = $row['DRAWER'];
							}
						}
					}
					break;
				case "Daily Operations - Eform":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT ID, TOOL_OUT, TANK, STATION, BUILDING_CURRENT, DATE_IN, FM_DATE, DATE_OUT FROM Electroforming_History WHERE DATE_IN <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND DATE_OUT >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") ORDER BY TANK;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					break;
				case "Daily Operations - Troom":
					$tools = array();
					$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, JOB_NUMBER, TARGET_DATE, PROCESS, PO_NUMBER, OPERATOR_IN FROM Toolroom_History WHERE DATE_IN <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND DATE_OUT >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") ORDER BY TARGET_DATE, TOOL_IN;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tools[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					break;
				case "Tank Status Report":
					$tanks = array();
					$result = sqlsrv_query($conn, "SELECT ID, TANK, DATE, STRESS, STRIP, TIME, CONSTANT, UNITS, MAIN, I_AUX, U_AUX, IAUX_MAIN, REMARK FROM Tank_Stress WHERE DATE <= DATEADD(d,1,CONVERT(datetime, '" . $_POST['dateTo'] . "', " . (strlen($_POST['dateTo']) == 8 ? "1" : "101") . ")) AND DATE >= CONVERT(datetime, '" . $_POST['dateFrom'] . "', " . (strlen($_POST['dateFrom']) == 8 ? "1" : "101") . ") AND TANK <= " . $_POST['tankTo'] . " AND TANK >= " . $_POST['tankFrom'] . " ORDER BY TANK, DATE;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$tanks[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
					break;
				case "In Progress Report":
					$jobs = array();
					$result = sqlsrv_query($conn, "SELECT ID, TANK, STATION, TOOL_OUT, BUILDING_CURRENT, DATE_IN, FM_DATE, FORMING_TIME, DATE_OUT FROM Electroforming WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '' AND (OPERATOR_OUT IS NULL OR OPERATOR_OUT = '') ORDER BY TANK ASC, STATION ASC;");
					if ($result) {
						while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
							$jobs[] = $row;
						}
					} else {
						var_dump(sqlsrv_errors());
					}
				default:
			}
		}
	}
	//var_dump($tools);
	//var_dump($tanks);
	//var_dump($fresnelTools);
	//var_dump($reflexTools);
	var_dump($jobs);
?>
<script src="/scripts/dailyeform.js"></script>
<script src="/scripts/dailytroom.js"></script>
<script src="/scripts/eformlist.js"></script>
<script src="/scripts/eformyieldlist.js"></script>
<script src="/scripts/nickelusage.js"></script>
<script src="/scripts/tanklist.js"></script>
<script src="/scripts/toollist.js"></script>
<script src="/scripts/troomyieldlist.js"></script>
<script src="/scripts/inprogress.js"></script>
<script type="text/javascript">
	
	/**
	  *	@desc	convert date object to date string
	  *	@param	Date d - date to be converted
	  *	@return	string date - MM/DD/YY
	*/
	function formatDate(d) {
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString();
		
		return date;
	}
	
	/**
	  *	@desc	convert date object to time string
	  *	@param	Date d - date to be converted
	  *	@return	string date - HH:mm:ss
	*/
	function formatTime(d) {
		var hour = d.getHours();
		if (hour < 10) {
			hour = "0" + hour;
		}
		var minute = d.getMinutes();
		if (minute < 10) {
			minute = "0" + minute;
		}
		var second = d.getSeconds();
		if (second < 10) {
			second = "0" + second;
		}
		
		date = hour.toString() + ":" + minute.toString() + ":" + second.toString();
		
		return date;
	}
	
	/**
	  *	@desc	determine proper report to run, supply it with table rows and range boundaries
	  *	@param	none
	  *	@return	none
	*/
	function printReport() {
		switch("<?=$_POST['report']?>") {
			case "Tool Listing Report":
				var toolHTML = `<?php
					foreach($tools as $tool) {
						echo '<tr><td class="col1">' . $tool['TOOL'] . '</td>' .
								 '<td class="col2">' . date_format($tool['DATE_CREATED'],'m/d/y') . '</td>' .
								 '<td class="col3">' . $tool['STATUS'] . '</td>' .
								 '<td class="col4">' . $tool['REASON'] . '</td>' .
								 '<td class="col5">' . date_format($tool['STATUS_DATE'],'m/d/y') . '</td>' .
								 '<td class="col6">' . $tool['LOCATION'] . '</td>' .
								 '<td class="col7">' . $tool['DRAWER'] . '</td></tr>';
					}
				?>`;
				buildToolList(toolHTML, "<?=$_POST['toolFrom']?>", "<?=$_POST['toolTo']?>");
				break;
			case "Nickel Usage":
				var fresnelHTML = `<?php
					$fresnelLbs = 0;
					foreach($fresnelTools as $tool) {
						$avg = 0;
						$count = 0;
						for ($i = 1;$i<6;$i++) {
							if ($tool['THICKNESS' . $i] != 0) {
								$avg = (($avg * $count) + $tool['THICKNESS' . $i]) / ($count+1);
								$count++;
							}
						}
						$lbs = round((((($tool['PART_LENGTH'] * ($tool['PART_WIDTH'] == 0 || $tool['PART_WIDTH'] == '' ? $tool['PART_LENGTH'] * M_PI : $tool['PART_WIDTH']) * $avg) / 1000) * 8.9) / 453.592),2);
						$fresnelLbs += $lbs;
						echo '<tr><td class="col1">' . date_format($tool['DATE_OUT'],'m/d/y H:i') . '</td>' .
								 '<td class="col2">' . $tool['TOOL_OUT'] . '</td>' .
								 '<td class="col3">' . $tool['PART_LENGTH'] . '</td>' .
								 '<td class="col4">' . $tool['PART_WIDTH'] . '</td>' .
								 '<td class="col5">' . $avg . '</td>' .
								 '<td class="col6">' . $lbs . '</td>' .
								 '<td class="col7">' . $tool['JOB_NUMBER'] . '</td></tr>';
					}
				?>`;
				var fresnelLength = fresnelHTML.match(/\/tr/g) ? fresnelHTML.match(/\/tr/g).length : 0;
				var fresnelLbs = <?=$fresnelLbs?>;
				
				var reflexHTML = `<?php
					$reflexLbs = 0;
					foreach($reflexTools as $id => $tool) {
						$avg = 0;
						$count = 0;
						for ($i = 1;$i<6;$i++) {
							if ($tool['THICKNESS' . $i] != 0) {
								$avg = (($avg * $count) + $tool['THICKNESS' . $i]) / ($count+1);
								$count++;
							}
						}
						$lbs = round((((($tool['PART_LENGTH'] * ($tool['PART_WIDTH'] == 0 || $tool['PART_WIDTH'] == '' ? $tool['PART_LENGTH'] * M_PI : $tool['PART_WIDTH']) * $avg) / 1000) * 8.9) / 453.592),2);
						$reflexLbs += $lbs;
						echo '<tr><td class="col1">' . date_format($tool['DATE_OUT'],'m/d/y H:i') . '</td>' .
								 '<td class="col2">' . $tool['TOOL_OUT'] . '</td>' .
								 '<td class="col3">' . $tool['PART_LENGTH'] . '</td>' .
								 '<td class="col4">' . $tool['PART_WIDTH'] . '</td>' .
								 '<td class="col5">' . $avg . '</td>' .
								 '<td class="col6">' . $lbs . '</td>' .
								 '<td class="col7">' . $tool['JOB_NUMBER'] . '</td></tr>';
					}
				?>`;
				var reflexLength = reflexHTML.match(/\/tr/g) ? reflexHTML.match(/\/tr/g).length : 0;
				var reflexLbs = <?=$reflexLbs?>;
				
				buildNickelList(fresnelHTML, reflexHTML, fresnelLength, reflexLength, fresnelLbs, reflexLbs, "<?=$_POST['dateFrom']?>", "<?=$_POST['dateTo']?>");
				break;
			case "E-Forming Cycle":
				var toolHTML = `<?php
					foreach($tools as $tool) {
						echo '<tr><td class="col1">' . $tool['TOOL_OUT'] . '</td>' .
								 '<td class="col2">' . $tool['TANK'] . '/' . $tool['STATION'] . '<br>' . $tool['STRESS'] . '</td>' .
								 '<td class="col3">' . date_format($tool['DATE_IN'],'m/d/y H:i') . '</td>' .
								 '<td class="col4">' . date_format($tool['DATE_OUT'],'m/d/y H:i') . '</td>' .
								 '<td class="col5">' . $tool['FORMING_DENSITY'] . '<br>' . round($tool['FORMING_TIME'],0) . '</td>' .
								 '<td class="col6">' . $tool['BUILDING_DENSITY'] . '</td>' .
								 '<td class="col7">' . $tool['STATUS_OUT'] . '</td>' .
								 '<td class="col8">' . $tool['LOCATION'] . '<br>' . $tool['DRAWER'] . '</td></tr>';
					}
				?>`;
				buildEformList(toolHTML, "<?=$_POST['toolFrom']?>", "<?=$_POST['toolTo']?>");
				break;
			case "Reflexite Eform Yield":
			case "Fresnel Eform Yield":
				var toolHTML = `<?php
					$total = 0;
					$retiredMandrels = 0;
					$pendingMandrels = 0;
					$goodMandrels = 0;
					$nogoodTools = 0;
					$pendingTools = 0;
					$goodTools = 0;
					foreach($tools as $tool) {
						if ($tool['STATUS_IN'] == "RETIRED") {
							$retiredMandrels++;
						} else if ($tool['STATUS_IN'] == "PENDING") {
							$pendingMandrels++;
						} else if ($tool['STATUS_IN'] == "GOOD") {
							$goodMandrels++;
						}
						
						if ($tool['STATUS_OUT'] == "NOGOOD") {
							$nogoodTools++;
						} else if ($tool['STATUS_OUT'] == "PENDING") {
							$pendingTools++;
						} else if ($tool['STATUS_OUT'] == "GOOD") {
							$goodTools++;
						}
						
						$total++;
						
						echo '<tr><td class="col1">' . $tool['TOOL_IN'] . '<br><span style="margin-left: 10px;">' . $tool['TOOL_OUT'] . '</span></td>' .
								 '<td class="col2">' . date_format($tool['DATE_OUT'],'m/d/y H:i') . '</td>' .
								 '<td class="col3">' . $tool['TANK'] . '</td>' .
								 '<td class="col4">' . $tool['STATUS_IN'] . '<br>' . $tool['STATUS_OUT'] . '</td>' .
								 '<td class="col5">' . ($tool['MANDREL']['REASON'] ? $tool['MANDREL']['REASON'] : 'N/A') . '<br>' . ($tool['TOOL']['REASON'] ? $tool['TOOL']['REASON'] : 'N/A') . '</td>' .
								 '<td class="col6">' . ($tool['MANDREL']['LOCATION'] ? $tool['MANDREL']['LOCATION'] : 'N/A') . '<br>' . ($tool['TOOL']['LOCATION'] ? $tool['TOOL']['LOCATION'] : 'N/A') . '</td>' .
								 '<td class="col7">' . ($tool['MANDREL']['DRAWER'] ? $tool['MANDREL']['DRAWER'] : 'N/A') . '<br>' . ($tool['TOOL']['DRAWER'] ? $tool['TOOL']['DRAWER']: 'N/A') . '</td></tr>';
					}
				?>`;
				buildEformYieldList(toolHTML, "<?=$_POST['dateFrom']?>", "<?=$_POST['dateTo']?>", <?=$total?>,<?=$retiredMandrels?>,<?=$pendingMandrels?>,<?=$goodMandrels?>,<?=$nogoodTools?>,<?=$pendingTools?>,<?=$goodTools?>,"<?=$_POST['report']?>");
				break;
			case "Toolroom Production Yield":
				var toolHTML = `<?php
					$total = 0;
					$nogoodIn= 0;
					$pendingIn = 0;
					$goodIn = 0;
					$nogoodOut = 0;
					$pendingOut = 0;
					$goodOut = 0;
					$runningTotal = 0;
					foreach($tools as $id => $tool) {
						if ($tool['STATUS_IN'] == "NOGOOD") {
							$nogoodIn++;
						} else if ($tool['STATUS_IN'] == "PENDING") {
							$pendingIn++;
						} else if ($tool['STATUS_IN'] == "GOOD") {
							$goodIn++;
						}
						
						if ($tool['STATUS_OUT'] == "NOGOOD") {
							$nogoodOut++;
						} else if ($tool['STATUS_OUT'] == "PENDING") {
							$pendingOut++;
						} else if ($tool['STATUS_OUT'] == "GOOD") {
							$goodOut++;
						}
						
						$total++;
						$runningTotal++;
						
						echo '<tr><td class="col1">' . $tool['TOOL_IN'] . '<br><span style="margin-left: 10px;">' . $tool['TOOL_OUT'] . '</span></td>' .
								 '<td class="col2">' . date_format($tool['MANDREL']['DATE'],'m/d/y') . '<br><span style="margin-left: 10px;">' . date_format($tool['TOOL']['DATE'],'m/d/y') . '</span></td>' .
								 '<td class="col3">' . $tool['STATUS_IN'] . '<br><span style="margin-left: 10px;">' . $tool['STATUS_OUT'] . '</span></td>' .
								 '<td class="col4">' . $tool['OPERATOR_IN'] . '<br><span style="margin-left: 10px;">' . $tool['OPERATOR_OUT'] . '</span></td>' .
								 '<td class="col5">' . $tool['MANDREL']['LOCATION'] . '<br><span style="margin-left: 10px;">' . $tool['TOOL']['LOCATION'] . '</span></td>' .
								 '<td class="col6">' . $tool['MANDREL']['DRAWER'] . '<br><span style="margin-left: 10px;">' . $tool['TOOL']['DRAWER'] . '</span></td></tr>';
						
						if (!isset($tools[$id+1]) || $tool['MACHINE_NUMBER'] != $tools[$id+1]['MACHINE_NUMBER']) {
							echo '<tr class="machine"><td><strong><span class="machine-label">Machine ID: ' . $tool['MACHINE_NUMBER'] . '</span><span class="machine-total">Total # of tools: ' . $runningTotal . '</span></strong></td></tr>';
							$runningTotal = 0;
						}
					}
				?>`;
				buildTroomYieldList(toolHTML, "<?=$_POST['dateFrom']?>", "<?=$_POST['dateTo']?>", "<?=$_POST['operator']?>", <?=$total?>,<?=$nogoodIn?>,<?=$pendingIn?>,<?=$goodIn?>,<?=$nogoodOut?>,<?=$pendingOut?>,<?=$goodOut?>);
				break;
			case "Daily Operations - Eform":
				var toolHTML = `<?php
					foreach($tools as $tool) {
						echo '<tr><td class="col1">' . $tool['TOOL_OUT'] . '</td>' .
								 '<td class="col2">' . $tool['TANK'] . '</td>' .
								 '<td class="col3">' . $tool['STATION'] . '</td>' .
								 '<td class="col4">' . round($tool['BUILDING_CURRENT'],0) . '</td>' .
								 '<td class="col5">' . date_format($tool['DATE_IN'],'m/d/y H:i') . '</td>' .
								 '<td class="col6">' . date_format($tool['FM_DATE'],'m/d/y H:i') . '</td>' .
								 '<td class="col7">' . date_format($tool['DATE_OUT'],'m/d/y H:i') . '</td></tr>';
					}
				?>`;
				buildDailyEformList(toolHTML, "<?=$_POST['dateFrom']?>", "<?=$_POST['dateTo']?>");
				break;
			case "Daily Operations - Troom":
				var toolHTML = `<?php
					foreach($tools as $tool) {
						echo '<tr><td class="col1">' . $tool['TOOL_IN'] . '</td>' .
								 '<td class="col2">' . $tool['JOB_NUMBER'] . '</td>' .
								 '<td class="col3">' . date_format($tool['TARGET_DATE'],'m/d/y') . '</td>' .
								 '<td class="col4">' . $tool['PROCESS'] . '</td>' .
								 '<td class="col5">' . $tool['PO_NUMBER'] . '</td>' .
								 '<td class="col6">' . $tool['OPERATOR_IN'] . '</td></tr>';
					}
				?>`;
				buildDailyTroomList(toolHTML, "<?=$_POST['dateFrom']?>", "<?=$_POST['dateTo']?>");
				break;
			case "Tank Status Report":
				var tankHTML = `<?php
					foreach($tanks as $tank) {
						echo '<tr>';
						for($i=1;$i<count($tank);$i++) {
							echo '<td class="col' . $i . '">' . ($tank[$i] instanceof DateTime ? date_format($tank[$i], "m/d/y") : $tank[$i]) . '</td>';
						}
						echo '</tr>';
					}
				?>`;
				buildTankList(tankHTML, "<?=$_POST['tankFrom']?>", "<?=$_POST['tankTo']?>", "<?=$_POST['dateFrom']?>", "<?=$_POST['dateTo']?>");
				break;
			case "In Progress Report":
				var jobHTML = `<?php
					foreach($jobs as $job) {
						echo '<tr><td class="col1">' . $job['TANK'] . '</td>' .
								 '<td class="col2">' . $job['STATION'] . '</td>' .
								 '<td class="col3">' . $job['TOOL_OUT'] . '</td>' .
								 '<td class="col4">' . ($job['BUILDING_CURRENT'] % 1.0 == 0 ? ((string)round($job['BUILDING_CURRENT']) . '.0') : round($job['BUILDING_CURRENT'], 1)) . '</td>' .
								 '<td class="col5">' . date_format($job['DATE_IN'],'m/d/y H:i') . '</td>' .
								 '<td class="col6">' . date_format(($job['FM_DATE'] == '' || $job['FM_DATE'] == null ? date_add($job['DATE_IN'], date_interval_create_from_date_string($job['FORMING_TIME'] . " minutes")) : $job['FM_DATE']), 'm/d/y H:i') . '</td>' .
								 '<td class="col7">' . date_format($job['DATE_OUT'],'m/d/y H:i') . '</td></tr>';
					}
				?>`;
				buildJobList(jobHTML);
			default:
				document.write(`<html>
									<head>
										<title>Report Error</title>
									</head>
									<body>
										<div id="modal"><div id="modal-content"></div></div>
									</body>
								</html>`);
		}
		
		document.close();
		
		window.setTimeout(window.print, 500);
		
		window.setTimeout(confirmPrint,600);
	}
	
	function confirmPrint() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById('modal-content');
		modal.style.display = "block";
		modalContent.innerHTML = `<p style="font-size: 14px;">Retry print?</p>
									<button onclick="window.location.reload()">Yes</button><button onclick="window.location.replace('reports.php')">No</button>`;
	}
	
</script>
<html>
	<body onload="printReport()">
	</body>
</html>