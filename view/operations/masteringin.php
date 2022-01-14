<!DOCTYPE html>
<?php
/**
  *	@desc process mastering job in
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//job data, mastering data, design data, lists of values to choose from
	$job = array();
	$master = array();
	$design = array();
	$machines = array();
	$masters = array();
	$process = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
				if ($job['OPERATOR_OUT'] == '') {
					$isCurrent = true;
				} else {
					$isCurrent = false;
				}
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Mastering_History WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isCurrent = false;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		if ($job['IS_BLANK'] == "FALSE") {
			$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['BLANK'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$master = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DATE, DRAWING, FILENAME FROM Designs WHERE DESIGN = '" . $job['TOOL_IN'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$design = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, MACHINE, DESCRIPTION FROM Valid_Machines WHERE PROCESS = 'MASTERING';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$machines[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DURATION FROM Processes WHERE PROCESS = 'MASTERING';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$process = $row;
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
	
	//set up tracking data
	var job = {};
	var machines = [<?php
		foreach($machines as $machine) {
			echo '{';
			foreach($machine as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo $value;
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	/**
	  *	@desc	insert date
	  *	@param	none
	  *	@return	none
	*/
	function getDate() {
		var d = <?php if (!$isCurrent) { echo 'new Date("' . date_format($job['DATE_IN'],'m/d/y') . '")'; } else { echo 'new Date()'; } ?>;
		
		document.getElementById("date-input").value = formatDate(d);
	}
	
	/**
	  *	@desc	get machine description
	  *	@param	none
	  *	@return	none
	*/
	function getDescription() {
		for(var i=0;i<machines.length;i++) {
			if (machines[i]['MACHINE'] == document.getElementById("machine-select").value) {
				document.getElementById("machine-input").value = machines[i]['DESCRIPTION'];
				break;
			}
		}
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("date-input").value == "") {
			msg = "Please enter a valid date";
		} else if (document.getElementById("program-input").value == "") {
			msg = "Please enter a program number";
		} else if (document.getElementById("operator-input").value == "") {
			msg = "Please enter your initials";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	save job data
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == '') {
			var conn = new XMLHttpRequest();
			var table = "Mastering";
			var action = "update";
			var query = "";
			var d = new Date();
			job = {
				DATE_IN: formatDate(new Date),
				OPERATOR_IN: document.getElementById("operator-input").value,
				STATUS_IN: "GOOD",
				DATE_OUT: formatDate(new Date(d.setDate(d.getDate() + <?= $process['DURATION'] ?>))),
				MACHINE_NUMBER: document.getElementById("machine-input").value,
				PROGRAM_NUMBER: document.getElementById("program-input").value,
				COMMENT: document.getElementById("comment-textarea").value,
				id: <?= $_POST['id'] ?>
			}
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Data updated")) {
						alert("Job updated");
						document.getElementsByTagName("body")[0].innerHTML += `<form action="mastering.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
						document.getElementById("return-form").submit();
					} else {
						alert("Error updating job. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			Object.keys(job).forEach((item, index, array) => {
				if (item != "id") {
					query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				} else {
					query += `&condition=id&value=${job[item]}`;
				}
			})
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn.send();
		} else {
			alert(msg);
		}
	}
	
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
	  *	@desc	create/display design details
	  *	@param	none
	  *	@return	none
	*/
	function showDesign() {
		var design = document.getElementById("design-input").value;
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span>`;
		var conn = new XMLHttpRequest();
		var table = "Designs";
		var action = "select";
		var condition = "DESIGN";
		var value = design;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 & conn.status == 200) {
				var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let design of result) {
					for (let x in design) {
						if (design[x] !== null && typeof design[x] == 'object') {
							design[x] = formatDate(new Date(design[x]['date']));
						} else if (design[x] == null) {
							design[x] = '';
						}
					}
				}
				
				html += `<div style="display: inline-block;">
					<div style="display: inline-block;">
						<div style="display: inline-block;">
							<span style="margin-left: 78px;">Design<input type="text" id="design" readonly value="${result[0]['DESIGN']}">
							Designer<input type="text" id="designer" readonly value="${result[0]['OPERATOR']}">
							Date<input type="text" id="date" readonly value="${formatDate(new Date(result[0]['DATE']))}">
							Drawing #<input type="text" id="drawing" readonly value="${result[0]['DRAWING']}"></span>
						</div><br>
						<div style="display: inline-block;">
							<span style="margin-left: 58px;">File Name<input type="text" id="file" readonly value="${result[0]['FILENAME']}"></span><br>
							<div style="display: inline-block; text-align: right;">
								Fresnel Conjugate<input type="text" id="fresnel" readonly value="${result[0]['FRESNEL_CONJUGATE']}"><span class="units">(in)</span><br>
								Plano Conjugate<input type="text" id="plano" readonly value="${result[0]['PLANO_CONJUGATE']}"><span class="units">(in)</span><br>
								Focal Length<input type="text" id="focal" readonly value="${result[0]['FOCAL_LENGTH']}"><span class="units">(in)</span><br>
								Number of Grooves<input type="text" id="grooves" style="margin-right: 20px;" readonly value="${result[0]['GROOVES']}"><br>
								Master Pitch<input type="text" id="pitch" readonly value="${result[0]['MASTER_PITCH']}"><span class="units">(in)</span><br>
								Radius<input type="text" id="radius" readonly value="${result[0]['RADIUS']}"><span class="units">(in)</span><br>
								Lens Diameter<input type="text" id="diameter" readonly value="${result[0]['LENS_DIAMETER']}"><span class="units">(in)</span>
							</div>
							<div style="display: inline-block; text-align: right;">
								Maximum Slope<input type="text" id="slope" readonly value="${result[0]['MAX_SLOPE']}"><input type="text" id="slope2" readonly value="${result[0]['MAX_SLOPE'] ? (parseInt(result[0]['MAX_SLOPE'].split(':')[0]) + (parseInt(result[0]['MAX_SLOPE'].split(':')[1])/60) + (parseInt(result[0]['MAX_SLOPE'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
								Maximum Draft<input type="text" id="max-draft" readonly value="${result[0]['MAX_DRAFT']}"><input type="text" id="max-draft2" readonly value="${result[0]['MAX_DRAFT'] ? (parseInt(result[0]['MAX_DRAFT'].split(':')[0]) + (parseInt(result[0]['MAX_DRAFT'].split(':')[1])/60) + (parseInt(result[0]['MAX_DRAFT'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
								Angle of Diamo. Tool<input type="text" id="tool-angle" readonly value="${result[0]['DIAMO_ANGLE']}"><input type="text" id="tool-angle2" readonly value="${result[0]['DIAMO_ANGLE'] ? (parseInt(result[0]['DIAMO_ANGLE'].split(':')[0]) + (parseInt(result[0]['DIAMO_ANGLE'].split(':')[1])/60) + (parseInt(result[0]['DIAMO_ANGLE'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
								Maximum Groove Depth<input type="text" id="max-depth" readonly value="${result[0]['MAX_GROOVE_DEPTH']}"><span class="units" style="margin-right: 89px;">(in)</span><br>
								Minimum Draft<input type="text" id="min-draft" readonly value="${result[0]['MIN_DRAFT']}"><input type="text" id="min-draft2" readonly value="${result[0]['MIN_DRAFT'] ? (parseInt(result[0]['MIN_DRAFT'].split(':')[0]) + (parseInt(result[0]['MIN_DRAFT'].split(':')[1])/60) + (parseInt(result[0]['MIN_DRAFT'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
								Prism Angle<input type="text" id="prism" readonly value="${result[0]['PRISM_ANGLE']}"><input type="text" id="prism2" readonly value="${result[0]['PRISM_ANGLE'] ? (parseInt(result[0]['PRISM_ANGLE'].split(':')[0]) + (parseInt(result[0]['PRISM_ANGLE'].split(':')[1])/60) + (parseInt(result[0]['PRISM_ANGLE'].split(':')[2])/3600)).toFixed(5) : ''}">
							</div>
						</div>
					</div>
				</div><br>
				<div style="display: inline-block;">
					<span style="margin-left: 45px;">Prism Depth<input type="text" id="prism-depth" readonly value="${result[0]['PRISM_DEPTH']}">(in)</span>
					<span style="margin-left: 94px;">Tilt Angle<input type="text" id="tilt-angle" readonly value="${result[0]['TILT_ANGLE']}"><input type="text" id="tilt-angle2" readonly value="${result[0]['TILT_ANGLE'] ? (parseInt(result[0]['TILT_ANGLE'].split(':')[0]) + (parseInt(result[0]['TILT_ANGLE'].split(':')[1])/60) + (parseInt(result[0]['TILT_ANGLE'].split(':')[2])/3600)).toFixed(5) : ''}"></span><br><br>
					<div style="display: inline-block; vertical-align: top;">
						<span style="position: relative; top: 3px;">Pitch</span><br>
						<span style="position: relative; top: 10px;">Groove Angle</span><br>
						<span style="position: relative; top: 17px;">Base Angle</span><br>
					</div>
					<div style="display: inline-block; width: 220px;">
						<input type="text" id="pitch1" readonly value="${result[0]['PITCH1']}"><span class="units">(in)</span>
						<input type="text" id="groove-angle1-1" readonly value="${result[0]['GROOVE_ANGLE1']}"><input type="text" id="groove-angle1-2" readonly value="${result[0]['GROOVE_ANGLE1'] ? (parseInt(result[0]['GROOVE_ANGLE1'].split(':')[0]) + (parseInt(result[0]['GROOVE_ANGLE1'].split(':')[1])/60) + (parseInt(result[0]['GROOVE_ANGLE1'].split(':')[2])/3600)).toFixed(5) : ''}">
						<input type="text" id="base-angle1-1" readonly value="${result[0]['BASE_ANGLE1']}"><input type="text" id="base-angle1-2" readonly value="${result[0]['BASE_ANGLE1'] ? (parseInt(result[0]['BASE_ANGLE1'].split(':')[0]) + (parseInt(result[0]['BASE_ANGLE1'].split(':')[1])/60) + (parseInt(result[0]['BASE_ANGLE1'].split(':')[2])/3600)).toFixed(5) : ''}">
					</div>
					<div style="display: inline-block; width: 220px;">
						<input type="text" id="pitch2" readonly value="${result[0]['PITCH2']}"><span class="units">(in)</span>
						<input type="text" id="groove-angle2-1" readonly value="${result[0]['GROOVE_ANGLE2']}"><input type="text" id="groove-angle2-2" readonly value="${result[0]['GROOVE_ANGLE2'] ? (parseInt(result[0]['GROOVE_ANGLE2'].split(':')[0]) + (parseInt(result[0]['GROOVE_ANGLE2'].split(':')[1])/60) + (parseInt(result[0]['GROOVE_ANGLE2'].split(':')[2])/3600)).toFixed(5) : ''}">
						<input type="text" id="base-angle2-1" readonly value="${result[0]['BASE_ANGLE2']}"><input type="text" id="base-angle2-2" readonly value="${result[0]['BASE_ANGLE2'] ? (parseInt(result[0]['BASE_ANGLE2'].split(':')[0]) + (parseInt(result[0]['BASE_ANGLE2'].split(':')[1])/60) + (parseInt(result[0]['BASE_ANGLE2'].split(':')[2])/3600)).toFixed(5) : ''}">
					</div>
					<div style="display: inline-block; width: 220px;">
						<input type="text" id="pitch3" readonly value="${result[0]['PITCH3']}"><span class="units">(in)</span>
						<input type="text" id="groove-angle3-1" readonly value="${result[0]['GROOVE_ANGLE3']}"><input type="text" id="groove-angle3-2" readonly value="${result[0]['GROOVE_ANGLE3'] ? (parseInt(result[0]['GROOVE_ANGLE3'].split(':')[0]) + (parseInt(result[0]['GROOVE_ANGLE3'].split(':')[1])/60) + (parseInt(result[0]['GROOVE_ANGLE3'].split(':')[2])/3600)).toFixed(5) : ''}">
						<input type="text" id="base-angle3-1" readonly value="${result[0]['BASE_ANGLE3']}"><input type="text" id="base-angle3-2" readonly value="${result[0]['BASE_ANGLE3'] ? (parseInt(result[0]['BASE_ANGLE3'].split(':')[0]) + (parseInt(result[0]['BASE_ANGLE3'].split(':')[1])/60) + (parseInt(result[0]['BASE_ANGLE3'].split(':')[2])/3600)).toFixed(5) : ''}">
					</div><br><br>
					<span style="margin-left: 64px; vertical-align: top;">Features:</span><textarea rows="4" cols="80" style="margin-left: 5px;" id="features-textarea" readonly>${result[0]['FEATURES']}</textarea>
				</div>
				<div style="display: inline-block;">
					<span style="margin-left: 52px; vertical-align: top;">Comments:</span><textarea rows="4" cols="80" style="margin: 3px 4px;" id="comment-textarea" readonly>${result[0]['COMMENT']}</textarea>
				</div>`;
				
				modalContent.innerHTML = html;
				modal.style.display = "block";
				closeForm();
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value,true);
		conn.send();
	}
	
	/**
	  *	@desc	set onclick functions to close modal form
	  *	@param	none
	  *	@return	none
	*/
	function closeForm() {
		var modal = document.getElementById("modal");
		var span = document.getElementById("close");
		
		span.onclick = function() {
			modal.style.display = "none";
		}
		
		window.onclick = function(event) {
			if (event.target == modal) {
				modal.style.display = "none";
			}
		}
	}
	
	/**
	  *	@desc	auto-format date fields to MM/DD/YY
	  *	@param	DOM Object input - date field to format
	  *	@return	none
	*/
	function fixDate(input) {
		var key = event.keyCode || event.charCode;
		
		var regex = /\/|\-|\\|\*/;
		
		if (key==8 || key==46) {
			if (regex.test(input.value.slice(-1))) {
				input.value = input.value.slice(0,-1);
			}
		} else {
			switch(input.value.length) {
				case 0:
					
					break;
				case 1:
				case 4:
				case 7:
				case 8:
					if (regex.test(input.value.slice(-1))) {
						input.value = input.value.slice(0,-1);
					}
					break;
				case 2:
					if (regex.test(input.value.charAt(1))) {
						input.value = "0" + input.value.slice(0,-1) + "/";
					} else {
						input.value += "/";
					}
					break;
				case 5:
					if (regex.test(input.value.charAt(4))) {
						var inputArr = input.value.split(regex);
						inputArr.pop();
						input.value = inputArr[0] + "/0" + inputArr.pop() + "/";
					} else {
						input.value += "/";
					}
					break;
				case 3:
				case 6:
					if (!regex.test(input.value.slice(-3))) {
						input.value = input.value.slice(0,-1) + "/" + input.value.slice(-1);
					}
					break;
				default:
			}
		}
	}
	
	/**
	  *	@desc	return to previous page
	  *	@param	none
	  *	@return none
	*/
	function goBack() {
		document.getElementsByTagName("body")[0].innerHTML += `<form action="mastering.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
		document.getElementById("return-form").submit();
	}
</script>
<html>
	<head>
		<title>Pre-Cut Mastering</title>
		<link rel="stylesheet" type="text/css" href="/styles/masteringin.css">
	</head>
	<body onload="getDate()">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
				    <span id="job-span">Job #<input type="text" id="job-input" value="<?= $job['JOB_NUMBER'] ?>" readonly></span>
				    <span id="wo-span">WO #<input type="text" id="wo-input" value="<?= $job['WO_NUMBER'] ?>" readonly></span>
				    <span id="po-span">PO #<input type="text" id="po-input" value="<?= $job['PO_NUMBER'] ?>" readonly></span><br>
				    <span id="blank-span" <?php if ($job['IS_BLANK'] == "FALSE") { ?>style="margin-left: 0;"<?php } ?>><?php if($job['IS_BLANK'] == "TRUE") { ?>Blank<?php } else { ?>Recut Master<?php } ?><input type="text" id="blank-input" value="<?= $job['BLANK'] ?>" readonly></span><br>
				    <span id="location-span">Location<input type="text" id="location-input" <?php if($job['IS_BLANK'] == "FALSE") { echo "value=\"" . $master['LOCATION'] . "\""; } else { echo "disabled"; }?> readonly></span>
				    <span id="drawer-span">Drawer<input type="text" id="drawer-input" <?php if($job['IS_BLANK'] == "FALSE") { echo "value=\"" . $master['DRAWER'] . "\""; } else { echo "disabled"; }?> readonly></span><br>
				    <span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span>
				    <span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input" <?php if($_SESSION['name'] != "eform" && $_SESSION['name'] != "troom" && $_SESSION['name'] != "master") { echo 'value="' . (!$isCurrent ? $job['OPERATOR_IN'] : $_SESSION['initials']) . '"'; } ?>></span><br>
				    <span id="comment-span">Comments</span><textarea rows="3" cols="59" id="comment-textarea"></textarea>
				</div>
			    <div class="controls">
			        <button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
			        <button onclick="goBack()">Back</button>
				</div>
				<div class="details">
				    <span id="design-span">Design<input type="text" id="design-input" value="<?= $job['TOOL_IN'] ?>" readonly><button onclick="showDesign()">Design Info</button></span><br>
				    <span id="design-date-span">Created<input type="text" id="design-date-input" value="<?= date_format($design['DATE'],'m/d/y') ?>" readonly></span><br>
				    <span id="program-span">Program #<input type="text" id="program-input"></span><br>
				    <span id="machine-span">Machine #<select id="machine-select" onchange="getDescription()">
					<?php foreach($machines as $machine) { ?>
					<option value="<?=$machine['MACHINE']?>"><?=$machine['MACHINE']?></option>
					<?php } ?>
					</select><input type="text" id="machine-input" value="<?=$machines[0]['DESCRIPTION']?>" readonly></span><br>
				    <span id="file-span">File Name<input type="text" id="file-input" value="<?= $design['FILENAME'] ?>" readonly></span>
				    <span id="drawing-span">Drawing #<input type="text" id="drawing-input" value="<?= $design['DRAWING'] ?>" readonly></span><br>
				    <span id="special-span">Special Instructions</span><br><textarea rows="4" cols="71" id="special-textarea" readonly><?= $job['SPECIAL_INSTRUCTIONS'] ?></textarea>
				</div>			
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
