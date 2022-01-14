<!DOCTYPE html>
<?php
/**
  *	@desc storage for system parameters
*/
	//get user lists
	require_once("../../utils.php");

	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
	}
	
	if (isset($_POST['submit'])) {
		$address = array("Name" => $_POST['name'], "Address" => $_POST['address'], "City" => $_POST['city'], "State" => $_POST['state'], "ZIP" => $_POST['zip'], "Phone" => $_POST['phone'], "Fax" => $_POST['fax']);
		$security = array("Change Location" => intval($_POST['location']), "Electroforming" => intval($_POST['electroforming']), "Measurements" => intval($_POST['measurements']), "Quality History" => intval($_POST['quality']), "Comment History" => intval($_POST['comment']), "Process In" => intval($_POST['process-in']), "Process Out" => intval($_POST['process-out']), "Master Diameter" => intval($_POST['diameter']), "Sec. Operations" => intval($_POST['operations']), "Print Traveler" => intval($_POST['traveler']), "Work Order" => intval($_POST['security']), "Work In Progress" => intval($_POST['progress']));
		$nextWO = $_POST['next-wo'];
		$nextBatch = $_POST['next-batch'];
		$nextSlip = $_POST['next-slip'];
		if (strpos($_POST['clear-path'],"\\")) {
			$path = explode("\\",$_POST['clear-path'])[0] . "\\\\" . explode("\\",$_POST['clear-path'])[1];
		} else {
			$path = $_POST['clear-path'];
		}
		if ($_POST['clock-power'] == "true") {
			$clockIsActive = true;
		} else {
			$clockIsActive = false;
		}
		$clockType = $_POST['clock-type'];
		$workOrderAge = intval($_POST['delete-wo']);
		$tankStatusAge = intval($_POST['archive-tank']);
		$refreshInterval = intval($_POST['refresh-interval']);
		$includeAngle = $_POST['include-angle'];
	}
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	function insertValues() {
		document.getElementById("name-input").value = "<?php echo $address['Name']; ?>";
		document.getElementById("address-input").value = "<?php echo $address['Address']; ?>";
		document.getElementById("city-input").value = "<?php echo $address['City']; ?>";
		document.getElementById("state-input").value = "<?php echo $address['State']; ?>";
		document.getElementById("zip-input").value = "<?php echo $address['ZIP']; ?>";
		document.getElementById("phone-input").value = "<?php echo $address['Phone']; ?>";
		document.getElementById("fax-input").value = "<?php echo $address['Fax']; ?>";
		document.getElementById("location-input").value = "<?php echo $security['Change Location']; ?>";
		document.getElementById("quality-input").value = "<?php echo $security['Quality History']; ?>";
		document.getElementById("process-out-input").value = "<?php echo $security['Process Out']; ?>";
		document.getElementById("electroforming-input").value = "<?php echo $security['Electroforming']; ?>";
		document.getElementById("comment-input").value = "<?php echo $security['Comment History']; ?>";
		document.getElementById("operations-input").value = "<?php echo $security['Sec. Operations']; ?>";
		document.getElementById("measurements-input").value = "<?php echo $security['Measurements']; ?>";
		document.getElementById("process-in-input").value = "<?php echo $security['Process In']; ?>";
		document.getElementById("traveler-input").value = "<?php echo $security['Print Traveler']; ?>";
		document.getElementById("diameter-input").value = "<?php echo $security['Master Diameter']; ?>";
		document.getElementById("progress-input").value = "<?php echo $security['Work In Progress']; ?>";
		document.getElementById("security-input").value = "<?php echo $security['Work Order']; ?>";
		document.getElementById("next-wo-input").value = "<?php echo $nextWO; ?>";
		document.getElementById("next-batch-input").value = "<?php echo $nextBatch; ?>";
		document.getElementById("next-slip-input").value = "<?php echo $nextSlip; ?>";
		document.getElementById("clear-path-input").value = "<?php echo $path; ?>";
		if (<?php echo $clockIsActive ? "true" : "false"; ?>) {
			document.getElementById("clock-power").options.selectedIndex = 0;
		} else {
			document.getElementById("clock-power").options.selectedIndex = 1;
		}
		if (<?php echo $clockType; ?> == "12") {
			document.getElementById("clock-type").options.selectedIndex = 1;
		} else {
			document.getElementById("clock-type").options.selectedIndex = 0;
		}
		document.getElementById("delete-wo-input").value = "<?php echo $workOrderAge; ?>";
		document.getElementById("refresh-interval-input").value = "<?php echo $tankStatusAge; ?>";
		document.getElementById("archive-tank-input").value = "<?php echo $refreshInterval; ?>";
		document.getElementById("include-angle-input").value = "<?php echo $includeAngle; ?>";
	}
</script>
<html>
	<head>
		<title>Parameters</title>
		<link rel="stylesheet" type="text/css" href="/styles/parameters.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertValues()">
		<div class="outer">
			<div class="inner">
				<form action="parameters.php" method="post">
					<div class="address-container">
						<span id="name-span">Name<input name="name" id="name-input" type="text"></span><br>
						<span id="address-span">Address<input name="address" id="address-input" type="text"></span><br>
						<span id="city-span">City<input name="city" id="city-input" type="text"></span>
						<span id="state-span">State<input name="state" id="state-input" type="text"></span>
						<span id="zip-span">Zip<input name="zip" id="zip-input" type="text"></span>
						<span id="phone-span">Phone<input name="phone" id="phone-input" type="text"></span>
						<span id="fax-span">Fax<input name="fax" id="fax-input" type="text"></span>
					</div>
					<div class="retrieve-container">
						<span><strong>Retrieve Tool Security</strong></span><br>
						<span id="location-span">Change Location<input name="location" id="location-input" type="text"></span>
						<span id="quality-span">Quality History<input name="quality" id="quality-input" type="text"></span>
						<span id="process-out-span">Process Out<input name="process-out" id="process-out-input" type="text"></span><br>
						<span id="electroforming-span">Electroforming<input name="electroforming" id="electroforming-input" type="text"></span>
						<span id="comment-span">Comment History<input name="comment" id="comment-input" type="text"></span>
						<span id="operations-span">Sec. Operations<input name="operations" id="operations-input" type="text"></span><br>
						<span id="measurements-span">Measurements<input name="measurements" id="measurements-input" type="text"></span>
						<span id="process-in-span">Process In<input name="process-in" id="process-in-input" type="text"></span>
						<span id="traveler-span">Print Traveler<input name="traveler" id="traveler-input" type="text"></span><br>
						<span id="diameter-span">Master Diameter<input name="diameter" id="diameter-input" type="text"></span>
						<span id="progress-span">Work In Progress<input name="progress" id="progress-input" type="text"></span><br>
						<span id="security-span">Security for Applying Status to Work Order<input name="security" id="security-input" type="text"></span>
					</div>
					<div class="misc-container">
						<span id="work-span">Next Work Order<input name="next-wo" id="next-wo-input" class="next" type="text"></span>
						<span id="batch-span">Next Batch<input name="next-batch" id="next-batch-input" class="next" type="text"></span><br>
						<span>Next Packing Slip<input name="next-slip" id="next-slip-input" class="next" type="text"></span>
						<span id="clear-span">All Clear Path<input name="clear-path" id="clear-path-input" class="next" type="text"></span><br>
						<span id="clock-span">Clock<select id="clock-power" name="clock-power">
							<option value="true">On</option>
							<option value="false">Off</option>
						</select><select id="clock-type" name="clock-type">
							<option value="24">24 Hour</option>
							<option value="12">12 Hour</option>
						</select></span><br>
						<span id="delete-span">Delete Work Orders (days)<input name="delete-wo" id="delete-wo-input" class="interval" type="number" step="1"></span>
						<span id="refresh-span">Refresh Interval (secs)<input name="refresh-interval" id="refresh-interval-input" class="interval" type="number" step="1"></span><br>
						<span id="archive-span">Archive Tank Status (days)<input name="archive-tank" id="archive-tank-input" class="interval" type="number" step="1"></span>
						<span id="angle-span">Include Angle<input name="include-angle" id="include-angle-input" class="interval" type="text"></span>
					</div>
					<div class="controls">
						<input name="submit" id="submit" type="submit" value="Save">
						<a href="../maintenance.php">Back</a>
					</div>
				</form>
			</div>
		</div>
	</body>
</html>