<!DOCTYPE html>
<?php
/**
  * @desc view, edit tool quality
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of quality information
	$locations = array();
	$statuses = array();
	$defects = array();
	$processes = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Inv_Locations ORDER BY LOCATION ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Tool_Status ORDER BY STATUS ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Valid_Defects ORDER BY DEFECT ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Processes ORDER BY PROCESS ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
	}
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
</script>
<html>
	<head>
		<title>Quality</title>
		<link rel="stylesheet" type="text/css" href="/styles/quality.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="top">
					<div class="top-left">
						<button id="search-button">Search</button>
						<span>Tool<input type="text" id="tool-input"></span><br>
						<span style="margin-left: 40px;">Date<input type="text"></span>
						<span>Location<select>
							<option value=""></option>
							<?php 
								foreach ($locations as $location) {
									if ($location['STATUS'] == "Active") {
										echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>";
									}
								}
							?>
						</select></span>
						<span style="margin-left: 19px;">Drawer<input type="text"></span><br>
						<span style="margin-left: 30px;">Initials<input type="text"></span>
						<span style="margin-left: 13px;">Status<select>
							<option value=""></option>
							<?php 
								foreach ($statuses as $status) {
									if ($status['STATE'] == "Active") {
										echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>";
									}
								}
							?>
						</select></span>
						<span style="margin-left: 23px;">Defect<select>
							<option value=""></option>
							<?php 
								foreach ($defects as $defect) {
									if ($defect['STATUS'] == "Active") {
										echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>";
									}
								}
							?>
						</select></span>
					</div>
					<div class="controls">
						<button>Save</button>
						<a href="home.php">Back</a>
					</div><br>
					<span style="display: inline-block; vertical-align: top; margin-top: 5px; margin-left: 10px;">Comment</span>
					<textarea rows="2" cols="44" style="margin-top: 5px;"></textarea>
				</div>
				<div class="bottom-left">
					<span style="margin-left: 150px;">Tooldef</span>
					<table>
						<thead>
							<tr>
								<th>Defects</th>
								<th>Q0</th>
								<th>Q1</th>
								<th>Q2</th>
								<th>Q3</th>
								<th>Q4</th>
								<th>Q5</th>
								<th>Q6</th>
								<th>Q7</th>
								<th>Q8</th>
								<th>Q9</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
				<div class="bottom-right">
					<button>Search</button>
					<span>Apply to Process</span><br>
					<select>
						<option value=""></option>
							<?php 
								foreach ($processes as $process) {
									echo "<option value=\"" . $process['PROCESS'] . "\">" . $process['PROCESS'] . "</option>";
								}
							?>
					</select><br>
					<span>Apply to Work Order</span><br>
					<input type="text">
				</div>
			</div>
		</div>
	</body>
</html>