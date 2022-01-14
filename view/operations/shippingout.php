<!DOCTYPE html>
<?php
/**
  *	@desc process shipping job out
*/
	require '../../utils.php';

	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of job's tools and customer data
	$tools = array();
	$customer = array();
	var_dump($_POST);
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Shipping WHERE BATCH_NUMBER = " . $_POST['batch'] . " ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tools[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		foreach ($tools as $id => $tool) {
			$lastDepartment = "Mastering";
			$lastDate = new DateTime('1900-01-01 00:00:00');
			$result = sqlsrv_query($conn, "SELECT ID, TOOL_OUT, OPERATOR_OUT, DATE_OUT, THICKNESS1, THICKNESS2, THICKNESS3, THICKNESS4, THICKNESS5, THICKNESS6 FROM Toolroom_History WHERE TOOL_OUT = '" . $tool['TOOL'] . "' ORDER BY WO_NUMBER;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$newDate = $row['DATE_OUT'];
					if ($newDate > $lastDate) {
						if ($lastDepartment == "Mastering" || $lastDepartment == "Electroforming") {
							$lastDepartment = "Toolroom";
						}
						$lastDate = $newDate;
						$total = 0;
						foreach($row as $key=>$value) {
							if (strpos($key,'THICKNESS') !== false) {
								$tools[$id][$key] = $value;
								$total += $value;
							}
						}
						$tools[$id]['AVG_THICKNESS'] = round($total/6,5);
						$tools[$id]['OPERATOR_OUT'] = $row['OPERATOR_OUT'];
						$tools[$id]['DATE_OUT'] = $row['DATE_OUT'];
					}
				}
			} else {
				var_dump(sqlsrv_errors());
			}
			
			$result = sqlsrv_query($conn, "SELECT ID, TOOL_OUT, OPERATOR_OUT, DATE_OUT, THICKNESS1, THICKNESS2, THICKNESS3, THICKNESS4, THICKNESS5, THICKNESS6, BRIGHTNESS1, BRIGHTNESS2, BRIGHTNESS3 FROM Electroforming_History WHERE TOOL_OUT = '" . $tool['TOOL'] . "' ORDER BY WO_NUMBER;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$newDate = $row['DATE_OUT'];
					if ($newDate > $lastDate) {
						if ($lastDepartment == "Mastering" || $lastDepartment == "Toolroom") {
							$lastDepartment = "Electroforming";
							$lastDate = $newDate;
							$thicknessTotal = 0;
							$brightnessTotal = 0;
							
							foreach($row as $key=>$value) {
								if (strpos($key,'THICKNESS') !== false || strpos($key,'BRIGHTNESS') !== false) {
									$tools[$id][$key] = $value;
									if (strpos($key,'THICKNESS') !== false) {
										$thicknessTotal += $value;
									} else {
										$brightnessTotal += $value;
									}
								}
							}
							
							$tools[$id]['AVG_THICKNESS'] = round($thicknessTotal/6,5);
							$tools[$id]['AVG_BRIGHTNESS'] = round($brightnessTotal/3,5);
						}
						
						$tools[$id]['OPERATOR_OUT'] = $row['OPERATOR_OUT'];
						$tools[$id]['DATE_OUT'] = $row['DATE_OUT'];
					}
				}
			} else {
				var_dump(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Customers WHERE CUSTOMER = '" . $tools[0]['CUSTOMER'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$customer = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		var_dump(sqlsrv_errors());
	}
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//set up tracking variables
	var packingSlip = "<?=$tools[0]['PACKING_SLIP']?>" || getLastPackingSlip();
	var tools = [<?php
		foreach($tools as $tool) {
			echo '{';
			foreach($tool as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo addslashes($value);
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
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
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - MM/DD/YY H:i
	*/
	function formatDateTime(d) {
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		
		var hours = d.getHours();
		if (hours < 10) {
			hours = "0" + hours;
		}
		
		var minutes = d.getMinutes();
		if (minutes < 10) {
			minutes = "0" + minutes;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString() + " " + hours.toString() + ":" + minutes.toString();
		
		return date;
	}
	
	/**
	  *	@desc	save job data
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var successCounter = 0;
		var d = new Date();
				
		var conn = [];
		var ids = [<?php foreach($tools as $tool) { echo $tool['ID'] . ","; } ?>];
		var table = "Shipping";
		var action = "update";
	
		for(var i=0;i<ids.length;i++) {
			var query = "";
			var job = {
				PACKING_SLIP: packingSlip,
				SHIP_OPERATOR: "<?=$_POST['user']?>",
				SHIP_DATE: formatDate(new Date("<?=$_POST['date']?>")),
				id: ids[i]
			};
		
			Object.keys(job).forEach((item, index, array) => {
				if (item != 'id') {
					query += `&${item}=${job[item]}`;
				} else {
					query += `&condition=id&value=${job[item]}`;
				}
			})
			
			conn[i] = new XMLHttpRequest();
			
			conn[i].onreadystatechange = xmlResponse(i);
			
			conn[i].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn[i].send();
		}
		
		function xmlResponse(i) {
			return function() {
				if (conn[i].readyState == 4 && conn[i].status == 200) {
					if (conn[i].responseText.includes("Data updated successfully.")) {
						successCounter++;
						
						if (successCounter == ids.length) {
							updateLocations();
						}
					} else {
						alert("Job update failed. Contact IT Support to correct. " + conn[i].responseText);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	change locations of tools to SHIP_XXX
	  *	@param	none
	  *	@return	none
	*/
	function updateLocations() {
		var successCounter = 0;
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "update";
		
		for (var i=0;i<tools.length;i++) {
			var query = "";
			var location;
			if (tools[i]['CUSTOMER'] == "AVO" || tools[i]['CUSTOMER'] == "RTG" || tools[i]['CUSTOMER'] == "RFD") {
				location = "SHIP-AVO";
			} else if (tools[i]['CUSTOMER'] == "CHN") {
				location = "SHIP-CHN";
			} else if (tools[i]['CUSTOMER'] == "GER" || tools[i]['CUSTOMER'] == "ORA") {
				location = "SHIP-GER";
			} else if (tools[i]['CUSTOMER'] == "FOA" || tools[i]['CUSTOEMR'] == "APO") {
				location = "SHIP-FOA";
			} else {
				successCounter++;
				continue;
			}
			var tool = {
				LOCATION: location,
				DRAWER: "",
				TOOL: tools[i]['TOOL'].replace(/[+]/g,"%2B")
			}
			
			Object.keys(tool).forEach((item, index, array) => {
				if (item != "TOOL") {
					query += `&${item}=${tool[item]}`;
				} else {
					query += `&condition=TOOL&value=${tool[item]}`;
				}
			})
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Data updated")) {
						successCounter++;
					} else {
						alert("Could not update tool location. Contact IT Support to correct.");
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query,false);
			conn.send();
		}
		
		if (successCounter == tools.length) {
			buildPrintout();
		}
	}
	
	/**
	  *	@desc	ask user if the job is finished
	  *	@param	none
	  *	@return	none
	*/
	function testSlip() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById('modal-content');
		modal.style.display = "block";
		modalContent.innerHTML = `<p style="font-size: 14px;">Is the job completed?</p>
									<button onclick="backToShipping()">Yes</button><button onclick="retrySlip()">No</button>`;
	}
	
	/**
	  *	@desc	return to shipping table
	  *	@param	none
	  *	@return	none
	*/
	function backToShipping() {
		document.getElementsByTagName("body")[0].innerHTML += `<form action="shipping.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['batch']?>"></form>`;
		document.getElementById("return-form").submit();
	}
	
	/**
	  *	@desc	ask user if they need to print again
	  *	@param	none
	  *	@return	none
	*/
	function retrySlip() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById('modal-content');
		modal.style.display = "block";
		modalContent.innerHTML = `<p style="font-size: 14px;">Try again?</p>
									<button onclick="reloadPost()">Yes</button><button onclick="unprint()">No</button>`;
	}
	
	/**
	  *	@desc	reload with POST data
	  *	@param	none
	  *	@return	none
	*/
	function reloadPost() {
		var body = document.getElementsByTagName('body')[0];
		body.innerHTML += `<form visibility="hidden" action="shippingout.php" method="post" id="reload-form">
		
		<?php foreach ($_POST as $key=>$data) {
			echo "\n\t\t<input type=\"text\" name=\"$key\" value=\"$data\">";
		} ?>
		
		</form>`;
		
		document.getElementById('reload-form').submit();
	}
	
	/**
	  *	@desc	undo changes and go back to shipping table
	  *	@param	none
	  *	@return	none
	*/
	function unprint() {
		var successCounter = 0;
		var d = new Date();
				
		var conn = [];
		var ids = [<?php foreach($tools as $tool) { echo $tool['ID'] . ","; } ?>""];
		var table = "Shipping";
		var action = "update";
	
		for(var i=0;i<ids.length;i++) {
			var query = "";
			var job = {
				SHIP_OPERATOR: "",
				id: ids[i]
			};
		
			Object.keys(job).forEach((item, index, array) => {
				if (item != 'id') {
					query += `&${item}=${job[item]}`;
				} else {
					query += `&condition=id&value=${job[item]}`;
				}
			})
			
			conn[i] = new XMLHttpRequest();
			
			conn[i].onreadystatechange = xmlResponse(i);
			
			conn[i].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn[i].send();
		}
		
		function xmlResponse(i) {
			return function() {
				if (conn[i].readyState == 4 && conn[i].status == 200) {
					if (conn[i].responseText.includes("Data updated successfully.")) {
						successCounter++;
						
						if (successCounter == ids.length) {
							document.getElementsByTagName("body")[0].innerHTML += `<form action="shipping.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['batch']?>"></form>`;
							document.getElementById("return-form").submit();
						}
					} else {
						alert("Job update failed. Contact IT Support to correct. " + conn[i].responseText);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	build packing slip
	  *	@param	none
	  *	@return	none
	*/
	function buildPrintout() {
	
		document.write(`
		<html><head><title>Packing Slip</title>
		<link rel="stylesheet" type="text/css" href="/styles/shippingtoolroom.css">
		</head><body>
			<h2><?=$address['Name']?></h2>
			<p><?=$address['Address']?><br>
			<?=$address['City']?>, <?=$address['State']?> <?=$address['ZIP']?></p>
			<div>
				<p><strong>PACKING SLIP #</strong>: ${packingSlip}</p>
				<span>
					<strong>SHIP TO</strong>: <?=$customer['NAME']?><br>
												 <?=$customer['ADDRESS']?><br>
												 <?=$customer['ADDRESS2']?><br>
												 <?=$customer['CITY']?>, <?=$customer['STATE']?> <?=$customer['ZIP']?>
				</span>
				<span style="margin-left: 100px;">
					<strong>CUSTOMER</strong>: <?=$customer['SHIP_TO']?><br>
												  <?=$customer['ADDRESS']?><br>
												  <?=$customer['ADDRESS2']?><br>
												  <?=$customer['CITY']?>, <?=$customer['STATE']?> <?=$customer['ZIP']?>
				</span>
			</div>
			<br>
			<table>
				<thead>
					<tr>
						<th class="col1">TOOL</th>
						<th class="col2">PO#</th>
						<th class="col3">TARGET</th>
						<th class="col4">BELT#</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($tools as $tool) { ?>
					<tr>
						<td class="col1"><?=$tool['TOOL']?></td>
						<td class="col2"><?=$tool['PO_NUMBER']?></td>
						<td class="col3"><?=date_format($tool['TARGET_DATE'],'m/d/y')?></td>
						<td class="col4"><?=$tool['BELT_NUMBER']?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table><br><br>
			<span>Ship Date: ${formatDate(new Date("<?=$_POST['date']?>"))}
		<div id="modal"><div id="modal-content"></div></div></body></html>`);
		
		document.close();
		
		window.setTimeout(window.print, 500);
		
		setTimeout(testSlip, 600);
	}
	
	/**
	  *	@desc	get last used packing slip #
	  *	@param	none
	  *	@return	int (max + 1) - last used packing slip incremented by 1
	*/
	function getLastPackingSlip() {
		var conn = new XMLHttpRequest();
		var tables = ["Shipping_History","Shipping"];
		var action = "select";
		var condition = "PACKING_SLIP";
		var max = 0;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of response) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDateTime(new Date(job[x]['date']));
						}
					}
				}
				if (response.length > 0) {
					if (parseInt(response[0]['PACKING_SLIP']) > max) {
						max = parseInt(response[0]['PACKING_SLIP']);
					}
				}
			}
		}
		
		for (var i=0;i<tables.length;i++) {
			conn.open("GET","/db_query/sql2.php?table="+tables[i]+"&action="+action+"&condition="+condition+"&value=(SELECT MAX("+condition+") FROM "+tables[i]+")",false);
			conn.send();
		}
		
		return max + 1;
	}
</script>

<html>
<body onload="saveJob()"></body>
</html>