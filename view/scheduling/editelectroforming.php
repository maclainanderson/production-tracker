<!DOCTYPE html>
<?php
/**
  *	@desc edit existing eform job
*/
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins) && !in_array($_SESSION['name'], $schedulers)) {
		header("Location: /view/home.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of values to choose from, and current job data
	$tools = array();
	$tanks = array();
	$activeJobs = array();
	$locations = array();
	$statuses = array();
	$defects = array();
	$job = array();
	$isQueue = false;
	
	if ($conn) {
		if ($_POST['process'] == "NICKEL FLASHING") {
			$result = sqlsrv_query($conn, "SELECT ID, MANDREL, STATUS, REASON, LOCATION, DRAWER, MASTER_SIZE FROM Tool_Tree WHERE TOOL LIKE '%-[A-Z]' OR TOOL LIKE '%-[A-Z][A-Z]' ORDER BY TOOL ASC;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$alreadyExists = false;
					foreach($tools as $tool) {
						if ($tool['TOOL'] == $row['MANDREL']) {
							$alreadyExists = true;
						}
					}
					if ($alreadyExists == false) {
						$row['TOOL'] = $row['MANDREL'];
						unset($row['MANDREL']);
						$tools[] = $row;
					}
				}
			} else {
				print_r(sqlsrv_errors());
			}
			
			$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER, MASTER_SIZE FROM Tool_Tree WHERE TOOL LIKE '%-[A-Z]' OR TOOL LIKE '%-[A-Z][A-Z]' ORDER BY TOOL ASC;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$alreadyExists = false;
					foreach($tools as $tool) {
						if ($tool['TOOL'] == $row['TOOL']) {
							$alreadyExists = true;
						}
					}
					if ($alreadyExists == false) {
						$tools[] = $row;
					}
				}
			} else {
				print_r(sqlsrv_errors());
			}
		} else {
			$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER, MASTER_SIZE FROM Tool_Tree ORDER BY TOOL ASC;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$tools[] = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TANK, STRESS, DATE FROM Tank_Stress ORDER BY TANK ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				if (count($tanks) > 0) {
					$foundTank = false;
					for ($i = 0; $i < count($tanks) ; $i++) {
						if ($tanks[$i]['TANK'] == $row['TANK']) {
							$foundTank = true;
							
							$oldDate = $tanks[$i]['DATE'];
							$newDate = $row['DATE'];
							
							if ($oldDate->diff($newDate)->invert == 0) {
								$tanks[$i] = $row;
							}
						}
					}
					
					if (!$foundTank) {
						$tanks[] = $row;
					}
				} else {
					$tanks[] = $row;
				}
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, TOOL_OUT, TANK, STATION, SCHEDULE_TYPE FROM Electroforming;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$activeJobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TANK, STATIONS FROM Valid_Tanks;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				for($i=0;$i<count($tanks);$i++) {
					if ($tanks[$i]['TANK'] == $row['TANK']) {
						$tanks[$i]['STATIONS'] = $row['STATIONS'];
					}
				}
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION FROM Inv_Locations WHERE STATUS = 'Active' ORDER BY LOCATION ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STATUS FROM Tool_Status WHERE STATE = 'Active' ORDER BY STATUS ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT FROM Valid_Defects WHERE STATUS = 'Active' ORDER BY DEFECT ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Electroforming WHERE ID = '" . $_POST['id'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Electroforming_Queue WHERE ID = '" . $_POST['id'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isQueue = true;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
	} else {
		print_r(sqlsrv_errors());
	}
	
	function comp($a, $b) {
		
		return strcmp($a['TOOL'], $b['TOOL']);
		
	}
	
	usort($tools, "comp");
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//set up tracking variables
	var batch = {};
	var job = {};
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
	
	var tanks = [<?php
		foreach($tanks as $tank) {
			echo '{';
			foreach($tank as $key=>$value) {
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
	
	var jobs = [<?php
		foreach($activeJobs as $item) {
			echo '{';
			foreach($item as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y H:i');
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
	  *	@desc	insert current job data
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
		
		<?php if ($_POST['process'] == "EFORM") { ?>
		document.getElementById("date-out-input").value = formatDate(new Date());
		<?php } ?>
	}
	
	/**
	  *	@desc	fetch the next form of eform job
	  *	@param	none
	  *	@return	int newForm or (newForm + 1) - the last used form incremented by 1
	*/
	function getNewForm() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "MANDREL";
		var value = document.getElementById("tool-input").value;
		var newForm = 1;
		
		value = value.replace(/[+]/g, "%2B");
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of response) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				if (response.length > 0) {
					for (var i=0;i<response.length;i++) {
						if (parseInt(response[i]['TOOL'].split("-")[response[i]['TOOL'].split("-").length-1]) > newForm) {
							newForm = parseInt(response[i]['TOOL'].split("-")[response[i]['TOOL'].split("-").length-1]);
						}
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		if ("<?=$job['TOOL_IN']?>" == document.getElementById("tool-input").value) {
			return newForm;
		} else {
			return newForm+1;
		}
	}
	
	/**
	  *	@desc	fills in forming current, building current, and cycle time based on other fields
	  *	@param	none
	  *	@return	none
	*/
	function fillCurrent() {
		if (document.getElementById("length-input").value != "" && document.getElementById("width-input").value != "" && document.getElementById("forming-density-input").value != "" && document.getElementById("forming-time-input").value != "" && document.getElementById("building-density-input").value != "" && document.getElementById("target-thickness-input").value != "") {
			
			var area = (parseFloat(document.getElementById("length-input").value) / 100) * (parseFloat(document.getElementById("width-input").value) / 100);
			
			document.getElementById("forming-current-input").value = parseInt((area * parseFloat(document.getElementById("forming-density-input").value)) + 1);
			document.getElementById("building-current-input").value = parseInt((area * parseFloat(document.getElementById("building-density-input").value)) + 1);
			
			var cycleTime = (parseFloat(document.getElementById('target-thickness-input').value) - (parseFloat(document.getElementById('forming-density-input').value) * (parseFloat(document.getElementById('forming-time-input').value)/60) * 0.01194)) / (0.01194*parseFloat(document.getElementById('building-density-input').value)) + parseFloat(document.getElementById('forming-time-input').value) / 60;
			
			//placeholder for later
			document.getElementById("cycle-hours-input").value = parseInt(cycleTime);
			document.getElementById("cycle-minutes-input").value = parseInt((cycleTime % 1 * 60) + 1);
			
			var date = new Date(document.getElementById("date-in-input").value);
			
			var hours = parseInt(document.getElementById("cycle-hours-input").value);
			hours *= 3600000;
			var minutes = parseInt(document.getElementById("cycle-minutes-input").value);
			minutes *= 60000;
			
			var newDate = date.getTime() + hours + minutes;
			
			date.setTime(newDate);
			
			
			document.getElementById("date-out-input").value = formatDate(date);
		}
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - MM/YY/DD
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
	  *	@desc	show wait message to users
	  *	@param	none
	  *	@return	none
	*/
	function wait() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modalContent.innerHTML = "<h3>Please wait...</h3>";
		modal.style.display = "block";
		setTimeout(popToolList, 200);
	}
	
	/**
	  *	@desc	create/display list of tools to eform
	  *	@param	none
	  *	@return	none
	*/
	function popToolList() {
		var searchText = document.getElementById("tool-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = '<span class="close" id="close">&times;</span>';
		
		html += '<table id="tool-table"><thead><tr><th class="col1">Tool</th><th class="col2">Status</th><th class="col3">Reason</th><th class="col4">Location</th><th class="col5">Drawer</th></tr></thead><tbody>';
		
		tools.forEach((item, index, array) => {
			if (item['TOOL'].toUpperCase().includes(searchText.toUpperCase())) {
				html += '<tr id="' + item['ID'] + '" onclick="selectToolRow(this)"><td class="col1">' + item['TOOL'] + '</td><td class="col2">' + item['STATUS'] + '</td><td class="col3">' + item['REASON'] + '</td><td class="col4">' + item['LOCATION'] + '</td><td class="col5">' + item['DRAWER'] + '</td></tr>';
			}
		});
		html += '</tbody></table>';
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight tool row
	  *	@param	DOM Object tr - row clicked on
	  *	@return	none
	*/
	function selectToolRow(tr) {
		var trs = tr.parentNode.children;
		
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectToolRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmToolRow(this)');
	}
	
	/**
	  *	@desc	insert tool into job data
	  *	@param	DOM Object tr - row clicked on
	  *	@return	none
	*/
	function confirmToolRow(tr) {
		if (["NOGOOD","RETIRED","PURGED"].includes(tr.children[1].innerHTML)) {
			alert("Cannot schedule this tool");
		} else {
			document.getElementById("tool-input").value = tr.children[0].innerHTML;
			document.getElementById("status-select").value = tr.children[1].innerHTML;
			document.getElementById("defect-select").value = tr.children[2].innerHTML;
			document.getElementById("location-select").value = tr.children[3].innerHTML;
			document.getElementById("drawer-input").value = tr.children[4].innerHTML;
		
			<?php if ($_POST['process'] == "EFORM") { ?>
			for (let tool of tools) {
				if (tool['ID'] == tr.id) {
					document.getElementById("diameter-input").value = tool['MASTER_SIZE'];
				}
			}
			<?php } ?>
		
			document.getElementById("close").click();
		}
	}
	
	/**
	  *	@desc	create/display list of tanks and current in-use status
	  *	@param	none
	  *	@return	none
	*/
	function popTankList() {
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = '<span class="close" id="close">&times;</span>';
		
		html += '<table id="tank-table"><thead><tr><th class="col1">Tank</th><th class="col2">Station</th><th class="col3">Available</th><th class="col4">Schedule Type</th><th class="col5">Stress (PSI)</th><th class="col6">Date</th><th class="col7">Mandrel</th><th class="col8">Form #</th></tr></thead><tbody>';
		
		tanks.forEach((item, index, array) => {
			for(var i=0;i<item['STATIONS'];i++) {
				isOccupied = false;
				for(var j=0;j<jobs.length;j++) {
					if (item['TANK'] == jobs[j]['TANK'] && i+1 == jobs[j]['STATION']) {
						isOccupied = true;
						html += '<tr id="'+item['ID']+'" onclick="selectTankRow(this)"><td class="col1">'+item['TANK']+'</td><td class="col2">'+(i+1)+'</td><td class="col3">No</td><td class="col4">'+jobs[j]['SCHEDULE_TYPE']+'</td><td class="col5">'+item['STRESS']+'</td><td class="col6">'+item['DATE']+'</td><td class="col7">'+jobs[j]['TOOL_IN']+'</td><td class="col8">'+jobs[j]['TOOL_OUT'].split("-")[jobs[j]['TOOL_OUT'].split("-").length-1]+'</td></tr>';
						break;
					}
				}
				
				if (isOccupied == false) {
					html += '<tr id="'+item['ID']+'" onclick="selectTankRow(this)"><td class="col1">'+item['TANK']+'</td><td class="col2">'+(i+1)+'</td><td class="col3">Yes</td><td class="col4"></td><td class="col5">'+item['STRESS']+'</td><td class="col6">'+item['DATE']+'</td><td class="col7"></td><td class="col8"></td></tr>';
				}
			}
		});
		html += '</tbody></table>';
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight tank row
	  *	@param	DOM Object tr - row clicked on
	  *	@return	none
	*/
	function selectTankRow(tr) {
		var trs = tr.parentNode.children;
		
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectTankRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmTankRow(this)');
	}
	
	/**
	  *	@desc	insert tank/station into job data
	  *	@param	DOM Object tr - row clicked on
	  *	@return	none
	*/
	function confirmTankRow(tr) {
		document.getElementById("tank-input").value = tr.children[0].innerHTML;
		document.getElementById("station-input").value = tr.children[1].innerHTML;
		
		document.getElementById("close").click();
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
	  *	@desc	validate data for job
	  *	@param	none
	  *	@return	error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		switch("<?=$_POST['process']?>") {
			case "CLEANING":
				if (document.getElementById("date-input").value == "") {
					msg = "Invalid date";
				} else if (document.getElementById("tool-input").value == "") {
					msg = "Please select a tool";
				}
				break;
			case "NICKEL FLASHING":
				if (document.getElementById("date-input").value == "") {
					msg = "Invalid date";
				} else if (document.getElementById("tool-input").value == "") {
					msg = "Please select a tool";
				} else if (document.getElementById("tank-input").value == "" || document.getElementById("station-input").value == "") {
					msg = "Please choose a tank and station";
				} else if (document.getElementById("time-input").value == "") {
					msg = "Please enter a time";
				} else if (document.getElementById("temp-input").value == "") {
					msg = "Please enter a temperature";
				}
				break;
			default:
				if (document.getElementById("date-in-input").value == "" || document.getElementById("date-out-input").value == "") {
					msg = "Invalid date";
				} else if (document.getElementById("tool-input").value == "") {
					msg = "Please select a tool";
				} else if (document.getElementById("tank-input").value == "" || document.getElementById("station-input").value == "") {
					msg = "Please choose a tank and station";
				}
				
				var details = ["length-input","forming-density-input","building-density-input","forming-time-input","target-thickness-input","forming-current-input","building-current-input","cycle-hours-input","cycle-minutes-input"];
				for (var val of details) {
					if (document.getElementById(val).value == "") {
						msg = "Process details not set";
					}
				}
		}
		
		return msg;
	}
	
	/**
	  *	@desc	submit job changes
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == "") {
			var d = new Date(document.getElementById("<?php if($_POST['process'] == "EFORM") { echo "date-in-input"; } else { echo "date-input"; } ?>").value);
			<?php if ($_POST['process'] == "EFORM") { ?>
			
			batch = {
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date(document.getElementById("date-out-input").value)),
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value,
				BATCH_NUMBER: <?=$job['BATCH_NUMBER']?>
			};
			
			job = {
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				SEQNUM: 1,
				TARGET_DATE: formatDate(new Date(document.getElementById("date-out-input").value)),
				TOOL_IN: document.getElementById("tool-input").value,
				DATE_IN: formatDate(d),
				TOOL_OUT: document.getElementById("tool-input").value + "-" + getNewForm(),
				DATE_OUT: formatDate(new Date(document.getElementById("date-out-input").value)),
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
				TANK: document.getElementById("tank-input").value,
				STATION: document.getElementById("station-input").value,
				CYCLE_TIME: document.getElementById("cycle-hours-input").value + ":" + document.getElementById("cycle-minutes-input").value,
				SCHEDULE_TYPE: document.getElementById("schedule-select").value,
				REPEAT: document.getElementById("repeat-input").value,
				PART_LENGTH: document.getElementById("length-input").value,
				PART_WIDTH: document.getElementById("width-input").value,
				FORMING_DENSITY: document.getElementById("forming-density-input").value,
				FORMING_TIME: document.getElementById("forming-time-input").value,
				BUILDING_DENSITY: document.getElementById("building-density-input").value,
				TARGET_THICKNESS: document.getElementById("target-thickness-input").value,
				FORMING_CURRENT: document.getElementById("forming-current-input").value,
				BUILDING_CURRENT: document.getElementById("building-current-input").value,
				WO_NUMBER: document.getElementById("wo-input").value
			};
			
			<?php } else if ($_POST['process'] == "CLEANING") { ?>
			
			batch = {
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date()),
				BATCH_NUMBER: <?=$job['BATCH_NUMBER']?>
			}
			
			job = {
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				SEQNUM: 1,
				TARGET_DATE: formatDate(new Date()),
				TOOL_IN: document.getElementById("tool-input").value,
				DATE_IN: formatDate(new Date()),
				TOOL_OUT: document.getElementById("tool-input").value,
				DATE_OUT: formatDate(new Date()),
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
				MODE: "DONE",
				WO_NUMBER: document.getElementById("wo-input").value
			};
			
			<?php } else { ?>
			
			batch = {
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date()),
				BATCH_NUMBER: <?=$job['BATCH_NUMBER']?>
			};
			
			job = {
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				SEQNUM: 1,
				TARGET_DATE: formatDate(new Date()),
				TOOL_IN: document.getElementById("tool-input").value,
				DATE_IN: formatDate(new Date()),
				TOOL_OUT: document.getElementById("tool-input").value + "/EN",
				DATE_OUT: formatDate(new Date()),
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
				TANK: document.getElementById("tank-input").value,
				STATION: document.getElementById("station-input").value,
				TEMPERATURE: document.getElementById("temp-input").value,
				TIME: document.getElementById("time-input").value,
				PASSIVATED: document.getElementById("passivated-select").value,
				WO_NUMBER: document.getElementById("wo-input").value
			};
			
			<?php } ?>
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "update";
			var conn2 = new XMLHttpRequest();
			var table2 = "<?=$isQueue?>" ? "Electroforming_Queue" : "Electroforming";
			var action2 = "update";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Data updated")) {
						var query2 = "";
						
						conn2.onreadystatechange = function() {
							if (conn2.readyState == 4 && conn2.status == 200) {
								if (conn2.responseText.includes("Data updated")) {
									if (job.PROCESS === "CLEANING") {
										alert("Job updated");
										<?php if ($_POST['source'] == "holdlist") { ?>
										window.location.replace("holdlist.php");
										<?php } else { ?>
										window.location.replace("electroforming.php");
										<?php } ?>
									} else {
										addTool();
									}
								} else {
									alert("Batch updated, but job entry failed. Contact support to correct. " + conn2.responseText);
								}
							}
						}
						
						Object.keys(job).forEach((item, index, array) => {
							if (item != "WO_NUMBER") {
								query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							} else {
								query2 += `&condition=WO_NUMBER&value=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							}
						})
						
						conn2.open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
						conn2.send();
					} else {
						alert("Batch not updated. Contact IT Support to correct. " + conn1.responseText);
					}
				}
			}
			
			var query1 = "";
			
			Object.keys(batch).forEach((item, index, array) => {
				if (item != "BATCH_NUMBER") {
					query1 += `&${item}=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				} else {
					query1 += `&condition=BATCH_NUMBER&value=${batch[item]}`;
				}
			})
			
			conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
			conn1.send();
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	reserve tool name
	  *	@param	none
	  *	@return	none
	*/
	function addTool() {
		if ("<?=$job['TOOL_OUT']?>".replace(/[+]/g, "%2B") != job.TOOL_OUT.replace(/[+]/g,"%2B")) {
			if (removeOldTool()) {
				var conn = new XMLHttpRequest();
				var table = "Tool_Tree";
				var action = "select";
				var conn2 = new XMLHttpRequest();
				var action2 = "insert";
				
				var tool = {
					MANDREL: job.TOOL_IN,
					TOOL: job.TOOL_OUT,
					LEVEL: 0,
					STATUS: "PENDING"
				}
				
				var query2 = "";
				
				Object.keys(tool).forEach((item, index, array) => {
					query2 += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				});
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.split("Array").length <= 1) {
							conn2.onreadystatechange = function() {
								if (conn2.readyState == 4 && conn2.status == 200) {
									if (conn2.responseText.includes("Insert succeeded")) {
										alert("Job updated");
										<?php if ($_POST['source'] == "holdlist") { ?>
										window.location.replace("holdlist.php");
										<?php } else { ?>
										window.location.replace("electroforming.php");
										<?php } ?>
									} else {
										alert("Job scheduled, but new tool not added to database. Contact IT Support.");
									}
								}
							}
							
							conn2.open("GET","/db_query/sql2.php?table="+table+"&action="+action2+query2, true);
							conn2.send();
						} else {
							alert("Job updated");
							<?php if ($_POST['source'] == "holdlist") { ?>
							window.location.replace("holdlist.php");
							<?php } else { ?>
							window.location.replace("electroforming.php");
							<?php } ?>
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition=TOOL&value="+job.TOOL_OUT.toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A"), true);
				conn.send();
			}
		} else {
			alert("Job updated");
			<?php if ($_POST['source'] == "holdlist") { ?>
			window.location.replace("holdlist.php");
			<?php } else { ?>
			window.location.replace("electroforming.php");
			<?php } ?>
		}
	}
	
	/**
	  *	@desc	remove previously reserved tool name
	  *	@param	none
	  *	@return	true on success, false otherwise
	*/
	function removeOldTool() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Tool_Tree";
		var query = "&TOOL="+"<?=$job['TOOL_OUT']?>".replace(/[+]/g, "%2B");
		var success = false;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					success = true;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
		conn.send();
		
		return success;
	}
	
	/**
	  *	@desc	auto-format date field to MM/DD/YY
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
</script>
<html>
	<head>
		<title>Add Electroforming Job</title>
		<?php if ($_POST['process'] == "CLEANING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addcleaning.css">
		<?php } else if ($_POST['process'] == "EFORM") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addelectroforming.css">
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addnickelflashing.css">
		<?php } ?>
	</head>
	<body onload="initialize(); <?php if ($_POST['process'] == "EFORM") { ?>fillCurrent();<?php } ?>">
		<div class="outer">
			<div class="inner">
				<?php if ($_POST['process'] == "CLEANING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>"></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" value="<?=$job['PO_NUMBER']?>" id="po-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>"></span><button onclick="wait()">Search</button><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input" value="<?=date_format($job['DATE_IN'],'m/d/y H:i')?>"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="<?php if ($_POST['source'] == 'holdlist') { echo "holdlist.php"; } else { echo "electroforming.php"; } ?>">Back</a>
				</div>
				<div class="status-info">
					<span id="location-span">Location<select id="location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span><span id="drawer-span">Drawer<input type="text" id="drawer-input"></span><br>
					<span id="status-span">Status<select id="status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span><span id="defect-span">Defect<select id="defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span>
				</div><br>
				<span id="special-span">Special Instructions</span><textarea rows="4" cols="70" id="special-textarea"><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else if ($_POST['process'] == "EFORM") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>"></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>"></span><br>
					<span id="tool-span">Mandrel<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>"></span><button onclick="wait()">Search</button><br>
					<span id="diameter-span">Master Diameter<input type="text" id="diameter-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="<?php if ($_POST['source'] == 'holdlist') { echo "holdlist.php"; } else { echo "electroforming.php"; } ?>">Back</a>
				</div>
				<div class="tank-info">
					<span id="tank-span">Tank / Station<input type="text" id="tank-input" value="<?=$job['TANK']?>"><input type="text" id="station-input" value="<?=$job['STATION']?>"></span><button id="tank-status-button" onclick="popTankList()">Tank Status</button><br>
					<span id="date-in-span">Date / Time In<input onkeydown="fixDate(this)" type="text" id="date-in-input" value="<?=date_format($job['DATE_IN'],'m/d/y H:i')?>"></span><br>
					<span id="date-out-span">Date / Time Out<input type="text" id="date-out-input" value="<?=date_format($job['DATE_OUT'],'m/d/y H:i')?>"></span>
				</div>
				<div class="status-info">
					<span id="location-span">Location<select id="location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span><span id="drawer-span">Drawer<input type="text" id="drawer-input"></span><br>
					<span id="status-span">Status<select id="status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span><span id="defect-span">Defect<select id="defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span><br>
					<span id="schedule-span">Schedule<select id="schedule-select">
						<option <?php if ($job['SCHEDULE_TYPE'] == "Single") { echo 'selected '; } ?>value="Single">Single</option>
						<option <?php if ($job['SCHEDULE_TYPE'] == "Indefinite") { echo 'selected '; } ?>value="Indefinite">Indefinite</option>
						<option <?php if ($job['SCHEDULE_TYPE'] == "Repeat") { echo 'selected '; } ?>value="Repeat">Repeat</option>
						<option <?php if ($job['SCHEDULE_TYPE'] == "Thru Generation") { echo 'selected '; } ?>value="Thru Generation">Thru Generation</option>
					</select></span><span id="repeat-span">Repeat<input type="number" id="repeat-input" value="<?=$job['REPEAT']?>"></span>
				</div>
				<div class="part-info">
					<span id="width-label">Width</span><span id="length-label">OD/L</span><br>
					<span id="size-span">Part Size (mm)<input type="text" id="length-input" onblur="fillCurrent()" value="<?=$job['PART_LENGTH']?>">&times;<input type="text" id="width-input" onblur="fillCurrent()" value="<?=$job['PART_WIDTH']?>"></span><br>
					<span id="forming-density-span">Forming Current Density (A/sq dm)<input type="text" id="forming-density-input" onblur="fillCurrent()" value="<?=$job['FORMING_DENSITY']?>"></span><br>
					<span id="forming-time-span">Forming Time (min)<input type="text" id="forming-time-input" onblur="fillCurrent()" value="<?=$job['FORMING_TIME']?>"></span><br>
					<span id="building-density-span">Building Current Density (A/sq dm)<input type="text" id="building-density-input" onblur="fillCurrent()" value="<?=$job['BUILDING_DENSITY']?>"></span><br>
					<span id="target-thickness-span">Target Form Thickness (mm)<input type="text" id="target-thickness-input" onblur="fillCurrent()" value="<?=$job['TARGET_THICKNESS']?>"></span><br>
				</div>
				<div class="current-info">
					<span id="forming-current-span">Forming Current (Amps)<input type="text" id="forming-current-input" value="<?=$job['FORMING_CURRENT']?>" readonly></span><br>
					<span id="building-current-span">Building Current (Amps)<input type="text" id="building-current-input" value="<?=$job['BUILDING_CURRENT']?>" readonly></span><br>
					<span id="cycle-time-span">Cycle Time (hrs, min)<input type="text" id="cycle-hours-input" value="<?php if (strpos($job['CYCLE_TIME'],":") !== false) { echo explode(":",$job['CYCLE_TIME'])[0]; } else { echo explode(" ",$job['CYCLE_TIME'])[0]; }?>" readonly><input type="text" id="cycle-minutes-input" value="<?php if (strpos($job['CYCLE_TIME'],":") !== false) { echo explode(":",$job['CYCLE_TIME'])[1]; } else { echo explode(" ",$job['CYCLE_TIME'])[1]; }?>" readonly></span>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea"><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>"></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>"></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>"></span><button onclick="wait()">Search</button><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input" value="<?=date_format($job['DATE_IN'],'m/d/y H:i')?>"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="<?php if ($_POST['source'] == 'holdlist') { echo "holdlist.php"; } else { echo "electroforming.php"; } ?>">Back</a>
				</div>
				<div class="tank-info">
					<span id="tank-span">Tank / Station<input type="text" id="tank-input" value="<?=$job['TANK']?>"><input type="text" id="station-input" value="<?=$job['STATION']?>"></span><br>
					<span id="temp-span">Temperature<input type="text" id="temp-input" value="<?=$job['TEMPERATURE']?>"></span><br>
					<span id="time-span">Time (min)<input type="text" id="time-input" value="<?=$job['TIME']?>"></span><br>
					<span id="passivated-span">Passivated<select id="passivated-select"><option value="Yes">Yes</option><option value="No">No</option></select></span>
				</div>
				<div class="status-info">
					<span id="location-span">Location<select id="location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span><span id="drawer-span">Drawer<input type="text" id="drawer-input"></span><br>
					<span id="status-span">Status<select id="status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span><span id="defect-span">Defect<select id="defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span><br>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea"><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>