<!DOCTYPE html>
<?php
/**
  *	@desc main eform list for operations
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of jobs, processes, new tool names, and master diameters
	$processes = array();
	$jobs = array();
	$newForms = array();
	$masterDiameters = array();
	$mandrelLocations = array();
	$toolLocations = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE DEPARTMENT = 'ELECTROFOR' ORDER BY PROCESS");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TOOL_IN, DATE_IN, TOOL_OUT, DATE_OUT, OPERATOR_IN, STATUS_IN, OPERATOR_OUT, STATUS_OUT, TANK, STATION, CYCLE_TIME, MODE, SCHEDULE_TYPE, BATCH_NUMBER, PART_LENGTH, PART_WIDTH, FORMING_DENSITY, FORMING_TIME, BUILDING_DENSITY, TARGET_THICKNESS, FORMING_CURRENT, BUILDING_CURRENT FROM Electroforming WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = $row;
				$newMax = explode("-",$row['TOOL_OUT']);
				if (strpos($row['TOOL_OUT'], "EN") !== false) {
					$newForms[$row['ID']] = "/EN";
				} else {
					$newForms[$row['ID']] = $newMax[count($newMax) - 1];
				}
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		foreach($jobs as $job) {
			$result = sqlsrv_query($conn, "SELECT ID, MASTER_SIZE, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
			if ($result) {
				if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$masterDiameters[] = $row['MASTER_SIZE'];
					$mandrelLocations[] = [$row['LOCATION'],$row['DRAWER']];
				} else {
					$masterDiameters[] = "";
					$mandrelLocations[] = ['',''];
				}
			} else {
				var_dump(sqlsrv_errors());
			}
			
			$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_OUT'] . "';");
			if ($result) {
				if ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$toolLocations[] = [$row['LOCATION'],$row['DRAWER']];
				} else {
					$toolLocations[] = ['',''];
				}
			} else {
				var_dump(sqlsrv_errors());
			}
		}
	} else {
		var_dump(sqlsrv_errors());
	}
	
?>
<script src="/scripts/cookies.js"></script>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//setup tracking variables
	var selectedRow = 0;
	var processes = [<?php
		foreach($processes as $process) {
			echo '{';
			foreach($process as $key=>$value) {
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
		foreach($jobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
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
	
	var newForms = {<?php
		foreach($newForms as $id => $newForm) {
			echo $id . ": `" . $newForm . "`,";
		} ?>
	}
	
	var masterDiameters = {<?php
		foreach($masterDiameters as $id => $diameter) {
			echo "'" . $jobs[$id]['WO_NUMBER'] . "': '" . $diameter . "'," . PHP_EOL;
		}
	?>};
	
	var mandrelLocations = {<?php
		foreach($mandrelLocations as $id => $mandrelLocation) {
			echo "'" . $jobs[$id]['WO_NUMBER'] . "': ['" . $mandrelLocation[0] . "','" . $mandrelLocation[1] . "']," . PHP_EOL;
		}
	?>}
	
	var toolLocations = {<?php
		foreach($toolLocations as $id => $toolLocations) {
			echo "'" . $jobs[$id]['WO_NUMBER'] . "': ['" . $toolLocations[0] . "','" . $toolLocations[1] . "']," . PHP_EOL;
		}
	?>}
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	document.onkeydown = function(evt) {
		evt = evt || window.event;
		var charCode = evt.keyCode || evt.which;
		if (charCode == "40" && document.getElementById(selectedRow).nextElementSibling) {
			document.getElementById(selectedRow).nextElementSibling.click();
		} else if (charCode == "38" && document.getElementById(selectedRow).previousElementSibling) {
			document.getElementById(selectedRow).previousElementSibling.click();
		} else {
			return;
		}
		document.getElementById(selectedRow).scrollIntoView();
	}
	
	/**
	  *	@desc	initialize page by inserting certain values
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if (getCookie('viewing-date') == '' || getCookie('viewing-date') == formatDate(new Date())) {
			document.getElementById('change-date-input').value = formatDate(new Date())
		} else {
			document.getElementById('change-date-input').value = getCookie('viewing-date');
			changeDate();
			document.getElementById("<?=$_POST['id']?>").click();
		}
	}
	
	/**
	  *	@desc	open sort/filter box, if session variable exists and is true
	  *	@param	none
	  *	@return	none
	*/
	function checkSortBox() {
		if (checkCookie("sort_expanded") && getCookie("sort_expanded") == "true") {
			document.getElementById("arrow").click();
			document.getElementsByClassName("filter-inner")[0].children[3].click();
		}
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
	  *	@desc	highlight selected row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		var trs = tr.parentNode.children;
		
		for (var i=0;i<trs.length;i++) {
			for (var j=0;j<jobs.length;j++) {
				if (trs[i].id == jobs[j]['ID']) {
					setColor(trs[i],j);
				}
			}
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		selectedRow = tr.id;
		
		getDetails();
	}
	
	/**
	  *	@desc	insert job details when row is clicked
	  *	@param	none
	  *	@return	none
	*/
	function getDetails() {
		var i = 0;
		jobs.forEach((item, index, array) => {
			if (item['ID'] == selectedRow) {
				document.getElementById("tool-input").value = item['TOOL_OUT'];
				document.getElementById("batch-input").value = item['BATCH_NUMBER'];
				document.getElementById("po-input").value = item['PO_NUMBER'];
				document.getElementById("job-input").value = item['JOB_NUMBER'];
				document.getElementById("wo-input").value = item['WO_NUMBER'];
				document.getElementById("schedule-input").value = item['SCHEDULE_TYPE'];
				document.getElementById("operator-in-input").value = item['OPERATOR_IN'];
				document.getElementById("operator-out-input").value = item['OPERATOR_OUT'];
				document.getElementById("diameter-input").value = masterDiameters[item['WO_NUMBER']];
				document.getElementById("location-input").value = mandrelLocations[item['WO_NUMBER']][0];
				document.getElementById("drawer-input").value = mandrelLocations[item['WO_NUMBER']][1];
				document.getElementById("status-input").value = item['STATUS_IN'];
				document.getElementById("newlocation-input").value = toolLocations[item['WO_NUMBER']][0];
				document.getElementById("newdrawer-input").value = toolLocations[item['WO_NUMBER']][1];
				document.getElementById("newstatus-input").value = item['STATUS_OUT'];
				i = index;
			}
		});
		
		if (document.getElementById("operator-in-input").value == "") {
			if (document.getElementById(selectedRow).children[0].innerHTML == "CLEANING" || document.getElementById(selectedRow).children[0].innerHTML == "NICKEL FLASHING") {
				document.getElementById("process-in-button").disabled = true;
				document.getElementById("process-out-button").disabled = false;
			} else {
				document.getElementById("process-in-button").disabled = false;
				document.getElementById("process-out-button").disabled = true;
			}
			document.getElementById("process-in-button").innerHTML = "Process In";
			document.getElementById("process-out-button").innerHTML = "Process Out";
			document.getElementById("build-button").disabled = true;
		} else {
			if (jobs[i]['MODE'] == "") {
				document.getElementById('build-button').disabled = true;
			} else {
				document.getElementById('build-button').disabled = false;
			}
			document.getElementById("process-in-button").innerHTML = "Details In";
			document.getElementById("process-in-button").disabled = false;
			document.getElementById("process-out-button").disabled = false;
			if (document.getElementById("operator-out-input").value == "") {
				document.getElementById("process-out-button").innerHTML = "Process Out";
			} else {
				document.getElementById("process-out-button").innerHTML = "Details Out";
			}
		}
		
		document.getElementById("retrieve-button").disabled = false;
	}
	
	/**
	  *	@desc	go to electroformingin page
	  *	@param	none
	  *	@return	none
	*/
	function processIn() {
		var process = document.getElementById(selectedRow).children[0].innerHTML;
		var id = selectedRow;
		
		if (document.getElementById("process-in-button").innerHTML == "Details In") {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="electroformingin.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${id}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
		} else {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="electroformingin.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${id}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
		}
		document.getElementById("process-form").submit();
	}
	
	/**
	  *	@desc	switch from FORM to BUILD mode
	  *	@param	int id - DB ID of job
	  *	@return	none
	*/
	function buildJob(id) {
		var modal = document.getElementById('modal');
		var modalContent = document.getElementById('modal-content');
		var index;
		modal.style.display = "block";
		
		for (var i=0;i<jobs.length;i++) {
			if (jobs[i]['ID'] == id) {
				index = i;
				partLength = jobs[i]['PART_LENGTH'];
				partWidth = jobs[i]['PART_WIDTH'];
				formingDensity = jobs[i]['FORMING_DENSITY'];
				formingTime = jobs[i]['FORMING_TIME'];
				buildingDensity = jobs[i]['BUILDING_DENSITY'];
				targetThickness = jobs[i]['TARGET_THICKNESS'];
				formingCurrent = jobs[i]['FORMING_CURRENT'];
				buildingCurrent = jobs[i]['BUILDING_CURRENT'];
				cycleTime = jobs[i]['CYCLE_TIME'];
			}
		}
		
		var html = `<span id="close">&times;</span>
		<div class="fm-info">
		<div class="modal-left">
		<span>Part Length: </span><br>
		<span>Part Width: </span><br>
		<span>Forming Density: </span><br>
		<span>Forming Time: </span><br>
		<span>Building Density: </span><br>
		<span>Target Thickness: </span>
		</div><div class="modal-right">
		<span>${partLength}</span><br>
		<span>${partWidth}</span><br>
		<span>${formingDensity}</span><br>
		<span>${formingTime}</span><br>
		<span>${buildingDensity}</span><br>
		<span>${targetThickness}</span>
		</div></div>
		<div class="fm-info">
		<div class="modal-left">
		<span>Forming Current: </span><br>
		<span>Building Current: </span><br>
		<span>Cycle Time: </span>
		</div><div class="modal-right">
		<span>${formingCurrent}</span><br>
		<span>${buildingCurrent}</span><br>
		<span>${cycleTime}</span>
		</div></div><br>
		<span id="fm-operator-span">Operator:</span><input id="fm-operator-input" type="text" value="<?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "troom" && $_SESSION['name'] != "master") { echo $_SESSION['initials']; } ?>"><span id="fm-date-span" style="margin-left: 30px;">Date:</span><input id="fm-date-input" type="text" value="${formatDateTime(new Date())}"><button id="submit">Submit</button>`;
		
		if (jobs[index]['MODE'] == "BUILD" || jobs[index]['MODE'] == "DONE") {
			modalContent.innerHTML = "<p>Tool has already been set to build. Editing this time will overwrite the previous time.</p>";
		} else {
			modalContent.innerHTML = "<p>Please wait...</p>";
		}
		
		setTimeout( function() {
			modalContent.innerHTML = html;
			
			document.getElementById("submit").addEventListener('click', function() {
				var conn = new XMLHttpRequest();
				var table = "Electroforming";
				var action = "update";
				var query = "&FM_OPERATOR="+document.getElementById("fm-operator-input").value+"&FM_DATE="+document.getElementById("fm-date-input").value + ((jobs[index]['MODE'] == "BUILD" || jobs[index]['MODE'] == "DONE") ? "" : "&MODE=BUILD") + "&condition=id&value=" + id;
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Data updated")) {
							if (jobs[index]['MODE'] == "BUILD" || jobs[index]['MODE'] == "DONE") {
								alert("Tool form time updated");
							} else {
								alert("Tool now in building mode");
							}
							window.location.replace("electroforming.php");
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query,false);
				conn.send();
			});
			
			window.onkeydown = function(e) {
				if (modal.style.display == "block" && e.key === "Enter") {
					document.getElementById("submit").click();
				}
			}
		
			closeForm();
		}, 2000);
	}
	
	/**
	  *	@desc	go to electroformingout page
	  *	@param	none
	  *	@return	none
	*/
	function processOut() {
		var process = document.getElementById(selectedRow).children[0].innerHTML;
		var id = selectedRow;
		if (document.getElementById("process-out-button").innerHTML == "Details Out") {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="electroformingout.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${id}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
		} else {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="electroformingout.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${id}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
		}
		document.getElementById("process-form").submit();
	}
	
	/**
	  *	@desc	setColor handler
	  *	@param	none
	  *	@return	none
	*/
	function setColors() {
		var trs = document.getElementsByClassName('main')[0].children[0].children[1].children;
		for (var i=0;i<trs.length;i++) {
			for (var j=0;j<jobs.length;j++) {
				if (trs[i].id == jobs[j]['ID']) {
					setColor(trs[i],j);
				}
			}
		}
	}
	
	/**
	  *	@desc	adjust row color based on job status
	  *	@param	DOM Object tr - row to adjust, int index - array index of job
	  *	@return	none
	*/
	function setColor(tr, index) {
		var d = new Date(jobs[index]['DATE_IN']);
		var d2 = new Date();
		var d3 = new Date(jobs[index]['DATE_OUT']);
		
		if (jobs[index]['OPERATOR_IN'] == "") {
			//Green/Black line
			tr.style.color = "black";
			tr.style.backgroundColor = "#0f0";
		} else if (jobs[index]['MODE'] != "BUILD" && jobs[index]['MODE'] != "DONE") {
			if (((d2-d) / 1000 / 60) < jobs[index]['FORMING_TIME']) {
				//Yellow/black
				tr.style.color = "black";
				tr.style.backgroundColor = "#ff0";
				tr.getElementsByClassName('col9')[0].innerHTML = "FORM";
			} else {
				//Yellow/red
				tr.style.color = "#f00";
				tr.style.backgroundColor = "#ff0";
				tr.getElementsByClassName('col9')[0].innerHTML = "RAMP";
			}
		} else {
			if ((((d3-d2) / 1000 / 60) < 15) && (((d3-d2) / 1000 / 60) > 0)) {
				//Red/black
				tr.style.color = "black";
				tr.style.backgroundColor = "#f00";
				tr.getElementsByClassName('col9')[0].innerHTML = "BUILD";
			} else if ((d3 - d2) <= 0) {
				if (jobs[index]['MODE'] == "DONE") {
					//White
					tr.style.color = "black";
					tr.style.backgroundColor = "white";
					tr.getElementsByClassName('col9')[0].innerHTML = "DONE";
				} else {
					//Red/yellow
					tr.style.color = "#ff0";
					tr.style.backgroundColor = "#f00";
					tr.getElementsByClassName('col9')[0].innerHTML = "READY";
				}
			} else {
				if (d3.getMonth() == d2.getMonth() && d3.getFullYear() == d2.getFullYear() && d3.getDate() == d2.getDate()) {
					//Blue
					tr.style.color = "black";
					tr.style.backgroundColor = "#00e6ff";
					tr.getElementsByClassName('col9')[0].innerHTML = "BUILD";
				} else {
					//White
					tr.style.color = "black";
					tr.style.backgroundColor = "white";
					tr.getElementsByClassName('col9')[0].innerHTML = "BUILD";
				}
			}
		}
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - MM/DD/YY H:i:s
	*/
	function formatDateTime(d) {
		if (isNaN(d.getTime())) {
			return '';
		}
		
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		if (year < 10) {
			year = "0" + year;
		}
		var hour = d.getHours();
		if (hour < 10) {
			hour = "0" + hour;
		}
		var minute = d.getMinutes();
		if (minute < 10) {
			minute = "0" + minute;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString() + " " + hour.toString() + ":" + minute.toString();
		
		return date;
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return string date - MM/DD/YY
	*/
	function formatDate(d) {
		if (isNaN(d.getTime())) {
			return '';
		}
		
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		if (year < 10) {
			year = "0" + year;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString();
		
		return date;
	}
	
	/**
	  *	@desc	sort jobs array by given column
	  *	@param	string value - column to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_electroforming_operations_order",document.getElementById("order-type").value);
		setCookie("sort_electroforming_operations_filter",document.getElementById("filter-type").value);
		setCookie("sort_electroforming_operations_filter_value",document.getElementById("filter-input").value);
		setCookie("sort_electroforming_operations_progress",document.getElementById("progress-type").value);
		
		jobs.sort(function(a, b) {
			
			switch(value) {
				case "mandrel":
					if (a['TOOL_IN'] < b['TOOL_IN']) {
						return -1;
					} else if (a['TOOL_IN'] > b['TOOL_IN']) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "process":
					if (a['PROCESS'] < b['PROCESS']) {
						return -1;
					} else if (a['PROCESS'] > b['PROCESS']) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "tank":
					if (parseInt(a['TANK']) < parseInt(b['TANK'])) {
						return -1;
					} else if (parseInt(a['TANK']) > parseInt(b['TANK'])) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "indate":
					var d = new Date(a['DATE_IN']);
					var d2 = new Date(b['DATE_IN']);
					if (d < d2) {
						return -1;
					} else if (d > d2) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "outdate":
					var d = new Date(a['DATE_OUT']);
					var d2 = new Date(b['DATE_OUT']);
					if (d < d2) {
						return -1;
					} else if (d > d2) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "scheduling":
					if (a['SCHEDULE_TYPE'] < b['SCHEDULE_TYPE']) {
						return -1;
					} else if (a['SCHEDULE_TYPE'] > b['SCHEDULE_TYPE']) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "indatedesc":
					var d = new Date(a['DATE_IN']);
					var d2 = new Date(b['DATE_IN']);
					if (d < d2) {
						return 1;
					} else if (d > d2) {
						return -1;
					} else {
						return 0;
					}
						
					break;
				case "linecolor":
					var ad = new Date(a['DATE_IN']);
					var ad2 = new Date(a['DATE_OUT']);
					var bd = new Date(b['DATE_IN']);
					var bd2 = new Date(b['DATE_OUT']);
					var date = new Date();
					if (a['OPERATOR_IN'] == "") {
						if (b['OPERATOR_IN'] == "") {
								console.log(a);
								console.log(b);
								console.log("Neither job tanked in, return 0");
							return 0;
						} else {
								console.log(a);
								console.log(b);
								console.log("Only Job B tanked in, return -1");
							return -1;
						}
						//Green/Black line
						//"black"
						//"#0f0"
					} else if (a['MODE'] != "BUILD" && a['MODE'] != 'DONE') {
						if (((date-ad) / 1000 / 60) < a['FORMING_TIME']) {
							if (b['OPERATOR_IN'] == "") {
								console.log(a);
								console.log(b);
								console.log("Job A forming, Job B not tanked in, return 1");
								return 1;
							} else if (((date-bd) / 1000 / 60) < b['FORMING_TIME']) {
								console.log(a);
								console.log(b);
								console.log("Both jobs forming, return 0");
								return 0;
							} else {
								console.log(a);
								console.log(b);
								console.log("Job A forming, Job B at least ramping, return -1");
								return -1;
							}
							//Yellow/black
							//"black"
							//"#ff0"
							//"FORM"
						} else {
							if (b['MODE'] == "BUILD" || b['MODE'] == "DONE") {
								console.log(a);
								console.log(b);
								console.log("Job A ramping, Job B at least building, return -1");
								return -1;
							} else if (b['OPERATOR_IN'] == "" || ((date-bd) / 1000 / 60) < b['FORMING_TIME']) {
								console.log(a);
								console.log(b);
								console.log("Job A ramping, Job B not tanked in or still forming, return 1");
								return 1;
							} else {
								console.log(a);
								console.log(b);
								console.log("Both jobs ramping, return 0");
								return 0;
							}
							//Yellow/red
							//"#f00"
							//"#ff0"
							//"RAMP"
						}
					} else if (a['MODE'] == 'DONE') {
						if (b['MODE'] == 'DONE') {
								console.log(a);
								console.log(b);
								console.log("Both jobs done, return 0");
							return 0;
						} else {
								console.log(a);
								console.log(b);
								console.log("Job A done, Job B not done, return 1");
							return 1;
						}
					} else {
						if ((((ad2-date) / 1000 / 60) < 15) && (((ad2-date) / 1000 / 60) > 0)) {
							if (((bd2-date) / 1000 / 60) > 15) {
								console.log(a);
								console.log(b);
								console.log("Job A almost done, Job B building/forming/ramping/not tanked in, return 1");
								return 1;
							} else if (((bd2-date) / 1000 / 60) <= 0) {
								console.log(a);
								console.log(b);
								console.log("Job A almost done, Job B done or ready, return -1");
								return -1;
							} else {
								console.log(a);
								console.log(b);
								console.log("Both jobs almost done, return 0");
								return 0;
							}
							//Red/black
							//"black"
							//"#f00"
							//"BUILD"
						} else if ((ad2 - date) <= 0) {
							if (((bd2-date) / 1000 / 60) > 0 || (b['MODE'] != "BUILD" && b['MODE'] != "DONE") || b['OPERATOR_IN'] == "") {
								console.log(a);
								console.log(b);
								console.log("Job A ready, Job B building/forming/ramping/almost ready/not tanked in, return 1");
								return 1;
							} else if (b['MODE'] == "DONE") {
									console.log(a);
									console.log(b);
									console.log("Job A ready, Job B done, return -1");
								return -1;
							} else {
								console.log(a);
								console.log(b);
								console.log("Both jobs ready, return 0");
								return 0;
							}
							//Red/yellow
							//"#ff0"
							//"#f00"
							//"READY"
						} else if (date.getFullYear() == ad2.getFullYear() && date.getMonth() == ad2.getMonth() && date.getDate() == ad2.getDate()) {
							if ((((bd2-date) / 1000 / 60) < 15) && (b['MODE'] == "BUILD" || b['MODE'] == "DONE")) {
								console.log(a);
								console.log(b);
								console.log("Job A due out today, Job B almost done, return -1");
								return -1;
							} else if ((date.getFullYear() == bd2.getFullYear() && date.getMonth() == bd2.getMonth() && date.getDate() == bd2.getDate()) && b['MODE'] == "BUILD") {
								console.log(a);
								console.log(b);
								console.log("Both jobs due out today, return 0");
								return 0;
							} else {
								console.log(a);
								console.log(b);
								console.log("Job A due out today, Job B not due out today/not done, return 1");
								return 1;
							}
							//Blue/black
							//"black"
							//"#00e6ff"
							//"BUILD"
						} else {
							if (b['OPERATOR_IN'] == "" || (b['MODE'] != "BUILD" && b['MODE'] != "DONE")) {
								console.log(a);
								console.log(b);
								console.log("Job A building, Job B forming/ramping/not tanked in, return 1");
								return 1;
							} else if ((date.getFullYear() == bd2.getFullYear() && date.getMonth() == bd2.getMonth() && date.getDate() == bd2.getDate()) || (((bd2-date) / 1000 / 60) < 15)) {
								console.log(a);
								console.log(b);
								console.log("Job A building, Job B due out today or done, return -1");
								return -1;
							} else {
								console.log(a);
								console.log(b);
								console.log("Both jobs building, return 0");
								return 0;
							}
							//White/black
							//"black"
							//"white"
							//"BUILD"
						}
					}
					break;
				default:
					return 0;
			}
		});
		
		fillSort();
	}
	
	/**
	  *	@desc	fill in sorted array
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[0].children[1];
		var html = "";
		var keyword = document.getElementById("filter-input").value.toUpperCase(), value = document.getElementById("filter-type").value;
		
		jobs.forEach((item, index, array) => {
			if (isAllowed(keyword, value, item)) {
				html += `<tr id="${item['ID']}" onclick="selectRow(this)">
										<td class="col1">${item['PROCESS'] == "ELECTROFORMING" ? "EFORM" : item['PROCESS'] == "NICKEL FLASHING" ? "NI FLASH" : item['PROCESS']}</td>
										<td class="col2">${item['TOOL_IN']}</td>
										<td class="col3">${formatDateTime(new Date(item['DATE_IN']))}</td>
										<td class="col4">${formatDateTime(new Date(item['DATE_OUT']))}</td>
										<td class="col5">${item['TANK']}</td>
										<td class="col6">${item['STATION']}</td>
										<td class="col7">${item['CYCLE_TIME']}</td>
										<td class="col8">${newForms[item['ID']]}</td>
										<td class="col9"></td>
									</tr>`;
			}
		});
		
		tbody.innerHTML = html;
		
		setColors();
	}
	
	/**
	  *	@desc	determine if row matches filter constraints
	  *	@param	string keyword - keyword to search for, string value - column to search in, array row - row to be checked
	  *	@return	true if match, false otherwise
	*/
	function isAllowed(keyword, value, row) {
		var valid = false;
		
		switch(value) {
			case "mandrel":
				if (row['TOOL_IN'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			case "tank":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,"."),"g").test(row['TANK'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['TANK'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "indate":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(row['DATE_IN'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['DATE_IN'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "outdate":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(row['DATE_OUT'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['DATE_OUT'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "scheduling":
				if (row['SCHEDULE_TYPE'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			case "process":
				if (row['PROCESS'].toUpperCase() == keyword) {
					valid = true;
				}
				break;
			default:
				valid = true;
		}
		
		var result = valid && inProgressFilter(row);
		
		return result;
	}
	
	/**
	  *	@desc	filter based on 'work in progress' select
	  *	@param	array row - row to be checked
	  *	@return	none
	*/
	function inProgressFilter(row) {
		var inProgress = document.getElementById("progress-type").value;
		var valid = false;
		
		switch(inProgress) {
			case "none":
				if (row['OPERATOR_IN'] == "") {
					valid = true;
				}
				break;
			case "duetoday":
				if (row['DATE_OUT'].split(" ")[0] == document.getElementById("change-date-input").value || row['OPERATOR_IN'] == "" || row['OPERATOR_OUT'] != "") {
					valid = true;
				}
				break;
			default:
				valid = true;
		}
		
		return valid;
	}
	
	/**
	  *	@desc	go to Retrieve Tool
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/retrieve.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${selectedRow}" name="id"><input type="text" value="${document.getElementById(selectedRow).children[1].innerHTML}" name="tool"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	view a different date for job data
	  *	@param	none
	  *	@return	none
	*/
	function changeDate() {
		refreshJobs();
		
		var date = new Date(document.getElementById('change-date-input').value);
		if (date.toString() != "Invalid Date") {
			if (formatDate(date) != formatDate(new Date())) {
				setCookie("viewing-date",document.getElementById("change-date-input").value);
			} else {
				setCookie("viewing-date",'');
			}
			var d = new Date();
			if (date.getDate() == d.getDate() && date.getMonth() == d.getMonth() && date.getYear() == d.getYear()) {
				window.location.replace("electroforming.php");
			} else {
				getJobs(date);
				removeOutlierJobs(date);
				getToolData();
				sortBy(getCookie('sort-electroforming-operations-order'));
			}
		} else {
			alert("Invalid Date");
		}
	}
	
	/**
	  *	@desc	reset jobs array with new data
	  *	@param	none
	  *	@return	none
	*/
	function refreshJobs() {
		jobs = [];
		newForms = {};
		
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Electroforming";
		var condition = "TOOL_IN";
		var value = "%";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of response) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDateTime(new Date(job[x]['date']));
						}
					}
				}
				
				response.forEach((item, index, array) => {
					jobs.push({
						'ID': item['ID'],
						'WO_NUMBER': item['WO_NUMBER'],
						'JOB_NUMBER': item['JOB_NUMBER'],
						'PO_NUMBER': item['PO_NUMBER'],
						'PROCESS': item['PROCESS'],
						'TOOL_IN': item['TOOL_IN'],
						'DATE_IN': item['DATE_IN'],
						'TOOL_OUT': item['TOOL_OUT'],
						'DATE_OUT': item['DATE_OUT'],
						'OPERATOR_IN': item['OPERATOR_IN'],
						'STATUS_IN': item['STATUS_IN'],
						'OPERATOR_OUT': item['OPERATOR_OUT'],
						'STATUS_OUT': item['STATUS_OUT'],
						'TANK': item['TANK'],
						'STATION': item['STATION'],
						'CYCLE_TIME': item['CYCLE_TIME'],
						'MODE': item['MODE'],
						'SCHEDULE_TYPE': item['SCHEDULE_TYPE'],
						'BATCH_NUMBER': item['BATCH_NUMBER'],
						'FORMING_TIME': item['FORMING_TIME']
					});
					if (item['TOOL_OUT'].includes("EN")) {
						newForms[item['ID']] = "/EN";
					} else {
						newForms[item['ID']] = item['TOOL_OUT'].split("-")[item['TOOL_OUT'].split("-").length-1];
					}
				});
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
	}
	
	/**
	  *	@desc	get jobs for past or future date
	  *	@param	Date date - date to search for
	  *	@return	none
	*/
	function getJobs(date) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Electroforming_History","Electroforming_Queue"];
		var condition = "DATE_IN";
		var condition2 = "DATE_OUT";
		var dateString, month, day, year;
		month = date.getMonth() + 1;
		if (month < 10) {
			month = "0" + month;
		}
		day = date.getDate();
		if (day < 10) {
			day = "0" + day;
		}
		year = date.getFullYear() % 100;
		
		dateString = month + "/" + day + "/" + year;
		
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of response) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDateTime(new Date(job[x]['date']));
						}
					}
				}
				
				response.forEach((item, index, array) => {
					jobs.push({
						'ID': item['ID'],
						'WO_NUMBER': item['WO_NUMBER'],
						'JOB_NUMBER': item['JOB_NUMBER'],
						'PO_NUMBER': item['PO_NUMBER'],
						'PROCESS': item['PROCESS'],
						'TOOL_IN': item['TOOL_IN'],
						'DATE_IN': item['DATE_IN'],
						'TOOL_OUT': item['TOOL_OUT'],
						'DATE_OUT': item['DATE_OUT'],
						'OPERATOR_IN': item['OPERATOR_IN'],
						'STATUS_IN': item['STATUS_IN'],
						'OPERATOR_OUT': item['OPERATOR_OUT'],
						'STATUS_OUT': item['STATUS_OUT'],
						'TANK': item['TANK'],
						'STATION': item['STATION'],
						'CYCLE_TIME': item['CYCLE_TIME'],
						'MODE': "DONE",
						'SCHEDULE_TYPE': item['SCHEDULE_TYPE'],
						'BATCH_NUMBER': item['BATCH_NUMBER'],
						'FORMING_TIME': item['FORMING_TIME']
					});
					if (item['TOOL_OUT'].includes("EN")) {
						newForms[item['ID']] = "/EN";
					} else {
						newForms[item['ID']] = item['TOOL_OUT'].split("-")[item['TOOL_OUT'].split("-").length-1];
					}
				});
			}
		}
		
		for (var i=0;i<tables.length;i++) {
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&condition="+condition+"&condition2="+condition2+"&value="+dateString+"&date_range=true", false);
			conn.send();
		}
	}
	
	/**
	  *	@desc	remove jobs in array that fall outside date range
	  *	@param	Date date - date that must be included in job
	  *	@return	none
	*/
	function removeOutlierJobs(date) {
		var indexToRemove = [];
		jobs.forEach((item, index, array) => {
			var d1 = new Date(formatDate(new Date(item['DATE_IN'])));
			var d2 = new Date(formatDate(new Date(item['DATE_OUT'])));
			
			if (date < d1) {
				indexToRemove.push(index);
			} else if (d2 < date) {
				item['MODE'] = "DONE";
				item['OPERATOR_IN'] = "XX";
				item['OPERATOR_OUT'] = "XX";
			}
		});
		
		indexToRemove.sort((a, b) => {a - b;});
		
		for (var i=indexToRemove.length-1;i>=0;i--) {
			jobs.splice(indexToRemove[i],1);
		}
	}
	
	/**
	  *	@desc	handler for getting tool info
	  *	@param	none
	  *	@return	none
	*/
	function getToolData() {
		masterDiameters = {};
		mandrelLocations = {};
		toolLocations = {};
		jobs.forEach((item, index, array) => {
			getMandrel(index);
			getTool(index);
		});
	}
	
	/**
	  *	@desc	get master diameter for each tool, append to masterDiameters array
	  *	@param	index of job
	  *	@return	none
	*/
	function getMandrel(index) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Tree";
		var condition = "TOOL";
		var value = jobs[index]['TOOL_IN'].replace(/[+]/g,"%2B");
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of response) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDateTime(new Date(tool[x]['date']));
						}
					}
				}
				if (response.length > 0) {
					masterDiameters[jobs[index]['WO_NUMBER']] = response[0]['SIZE'];
					mandrelLocations[jobs[index]['WO_NUMBER']] = [response[0]['LOCATION'],response[0]['DRAWER']];
				} else {
					masterDiameters[jobs[index]['WO_NUMBER']] = '';
					mandrelLocations[jobs[index]['WO_NUMBER']] = ['',''];
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
	}
	
	/**
	  *	@desc	get master diameter for each tool, append to masterDiameters array
	  *	@param	index of job
	  *	@return	none
	*/
	function getTool(index) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Tree";
		var condition = "TOOL";
		var value = jobs[index]['TOOL_OUT'].replace(/[+]/g,"%2B");
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of response) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDateTime(new Date(tool[x]['date']));
						}
					}
				}
				if (response.length > 0) {
					toolLocations[jobs[index]['WO_NUMBER']] = [response[0]['LOCATION'],response[0]['DRAWER']];
				} else {
					toolLocations[jobs[index]['WO_NUMBER']] = ['',''];
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
	}
	
	/**
	  *	@desc	archiveJob handler
	  *	@param	none
	  *	@return	none
	*/
	function archiveJobs() {
		jobs.forEach((item, index, array) => {
			var outDate = new Date(item['DATE_OUT']);
			var today = new Date(formatDate(new Date()));
			if (item['MODE'] == "DONE" && outDate < today) {
				archiveJob(item['ID']);
			}
		});
		
		goToJob();
	}
	
	/**
	  *	@desc	move to previously selected job
	  *	@param	none
	  *	@return none
	*/
	function goToJob() {
		<?php if (isset($_POST['returnTool'])) { ?>
		document.getElementById("<?=$_POST['returnTool']?>").scrollIntoView();
		document.getElementById("<?=$_POST['returnTool']?>").click();
		<?php } ?>
	}
	
	/**
	  *	@desc	get job data to move to archive
	  *	@param	int id - DB ID to search for
	  *	@return	array containing job data
	*/
	function getJobToDelete(id) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Electroforming";
		var condition = "ID";
		var value = id;
		var job = {};
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				job = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of job) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDateTime(new Date(tool[x]['date']));
						}
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job[0];
	}
	
	/**
	  *	@desc	move job to archive
	  *	@param	int id - passed to getJobToDelete()
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Electroforming_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			if (item != 'ID') {
				if (job[item] != null) {
					query += `&${item}=${job[item].toString().replace(/[+]/g,"%2B").replace(/[#]/g,"%23").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				}
			}
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					deleteOldJob(id);
				} else {
					alert("Old job not archived. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	remove archived job from current work
	  *	@param	ind it - DB ID to delete
	  *	@return	none
	*/
	function deleteOldJob(id) {
		var conn = new XMLHttpRequest();
		var table = "Electroforming";
		var action = "delete";
		var query = "&id="+id;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					document.getElementById(id).parentNode.removeChild(document.getElementById(id));
				} else {
					alert("Old job not removed from current work. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
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
		} else if (event.key == "Enter") {
			input.parentNode.nextElementSibling.nextElementSibling.click();
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
	  *	@desc	show filter options
	  *	@param	none
	  *	@return	none
	*/
	function showFilters() {
		var div = document.createElement("div");
		div.classList.add("filter-outer");
		div.innerHTML = `<div class="filter-inner">
							<div id="order-container">
								<span id="order-span">Order</span>
								<br>
								<select id="order-type">
									<option value="none">&lt;NONE&gt;</option>
									<option value="mandrel">Mandrel</option>
									<option value="process">Process</option>
									<option value="tank">Tank</option>
									<option value="indate">Tank In Date</option>
									<option value="outdate">Tank Out Date</option>
									<option value="scheduling">Scheduling Type</option>
									<option value="indatedesc">Tank In Date Desc.</option>
									<option value="linecolor">Line Color</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" onchange="changeFilter(this)">
									<option value="none">&lt;NONE&gt;</option>
									<option value="mandrel">Mandrel</option>
									<option value="process">Process</option>
									<option value="tank">Tank</option>
									<option value="indate">Tank In Date</option>
									<option value="outdate">Tank Out Date</option>
									<option value="scheduling">Scheduling Type</option>
								</select>
								<br>
								<input type="text" id="filter-input">
							</div>
							<div id="in-progress-container">
								<span id="in-progress-span">Work in Progress</span>
								<br>
								<select id="progress-type">
									<option value="all">All</option>
									<option value="none">None</option>
									<option value="duetoday">Due Out Today</option>
								</select>
							</div>
							<button onclick="sortBy(document.getElementById('order-type').value)">Go</button>
						</div>`;
		document.getElementsByClassName("container")[0].appendChild(div);
		var arrow = document.getElementById("arrow");
		div.after(arrow);
		arrow.children[0].classList.remove("right-arrow");
		arrow.children[0].classList.add("left-arrow");
		arrow.setAttribute("onclick",'hideFilters()');
		
		setCookie("sort_expanded","true");
		
		if (checkCookie("sort_electroforming_operations_order")) {
			document.getElementById("order-type").value = getCookie("sort_electroforming_operations_order");
		}
		
		if (checkCookie("sort_electroforming_operations_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_electroforming_operations_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_electroforming_operations_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_electroforming_operations_filter_value");
		}
		
		if (checkCookie("sort_electroforming_operations_progress")) {
			document.getElementById("progress-type").value = getCookie("sort_electroforming_operations_progress");
		}
	}
	
	/**
	  *	@desc	hide filter options
	  *	@param	none
	  *	@return	none
	*/
	function hideFilters() {
		document.getElementsByClassName("container")[0].removeChild(document.getElementsByClassName("filter-outer")[0]);
		var arrow = document.getElementById("arrow");
		arrow.children[0].classList.add("right-arrow");
		arrow.children[0].classList.remove("left-arrow");
		arrow.setAttribute("onclick",'showFilters()');
		
		setCookie("sort_expanded","false");
	}
	
	/**
	  *	@desc	change filter field type
	  *	@param	none
	  *	@return	none
	*/
	function changeFilter(select) {
		var field = document.getElementById("filter-input");
		if (field) {
			document.getElementById("filter-container").removeChild(field);
		}
		if (select.value == "process") {
			var select = document.createElement('select');
			select.id = "filter-input";
			for (var i=0;i<processes.length;i++) {
				select.innerHTML += '<option value="' + processes[i]['PROCESS'] + '">' + processes[i]['PROCESS'] + '</option>';
			}
			document.getElementById("filter-container").appendChild(select);
		} else {
			var input = document.createElement('input');
			input.type = "text";
			input.id = "filter-input";
			if (select.value == "indate" || select.value == "outdate" || select.value == "indatedesc") {
				input.setAttribute("onkeydown","fixDate(this)");
			} else {
				input.onkeydown = function(e) {
					if (e.key == "Enter") {
						input.parentNode.nextElementSibling.nextElementSibling.click();
					}
				}
			}
			document.getElementById("filter-container").appendChild(input);
		}
	}
	
	/**
	  *	@desc	go to tank conditions and find date in
	  *	@param	none
	  *	@return	none
	*/
	function goToConditions() {
		var row = document.getElementById(selectedRow);
		var date = row ? row.children[2].innerHTML.split(" ")[0] : formatDate(new Date());
		var tank = row ? row.children[4].innerHTML : "1";
		document.body.innerHTML += `<form id="conditions-form" style="display: none;" action="/view/conditions.php" method="POST"><input type="text" value="${date}" name="date"><input type="text" value="${tank}" name="tank"><input type="text" value="${selectedRow}" name="id"><input type="text" name="source" value="electroforming.php"></form>`;
		document.getElementById("conditions-form").submit();
	}
</script>
<html>
	<head>
		<title>Electroforming</title>
		<link rel="stylesheet" type="text/css" href="/styles/electroforming.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="setColors(); checkSortBox(); archiveJobs(); initialize();">
		<div class="container">
			<div class="outer">
				<div class="inner">
					<div class="main">
						<table>
							<thead>
								<tr>
									<th class="col1">Process</th>
									<th class="col2">Mandrel</th>
									<th class="col3">Tank In</th>
									<th class="col4">Tank Out</th>
									<th class="col5">Tank</th>
									<th class="col6">Station</th>
									<th class="col7">Cycle Time</th>
									<th class="col8">New Form</th>
									<th class="col9">Mode</th>
								</tr>
							</thead>
							<tbody>
							<?php for($i=0;$i<count($jobs);$i++) { ?>
								<tr id="<?=$jobs[$i]['ID']?>" onclick="selectRow(this)">
									<td class="col1"><?php if ($jobs[$i]['PROCESS'] == "ELECTROFORMING") { echo "EFORM"; } else if ($jobs[$i]['PROCESS'] == "NICKEL FLASHING") { echo "NI FLASH"; } else { echo $jobs[$i]['PROCESS']; } ?></td>
									<td class="col2"><?=$jobs[$i]['TOOL_IN']?></td>
									<td class="col3"><?=date_format($jobs[$i]['DATE_IN'],'m/d/y H:i')?></td>
									<td class="col4"><?=date_format($jobs[$i]['DATE_OUT'],'m/d/y H:i')?></td>
									<td class="col5"><?=$jobs[$i]['TANK']?></td>
									<td class="col6"><?=$jobs[$i]['STATION']?></td>
									<td class="col7"><?=$jobs[$i]['CYCLE_TIME']?></td>
									<td class="col8"><?=$newForms[$jobs[$i]['ID']]?></td>
									<td class="col9"></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
						<input type="text" id="tool-input">
					</div>
					<div class="left">
						<span id="batch-span">Batch<input type="text" id="batch-input" readonly></span>
						<span id="wo-span">WO #<input id="wo-input" type="text" readonly></span>
						<span id="po-span">PO #<input id="po-input" type="text" readonly></span>
						<span id="job-span">Job #<input id="job-input" type="text" readonly></span><br>
						<span id="operator-in-span">Operator In<input id="operator-in-input" type="text" readonly></span>
						<span id="operator-out-span">Operator Out<input id="operator-out-input" type="text" readonly></span><br>
						<span id="schedule-span">Schedule Type<input id="schedule-input" type="text" readonly></span>
						<span id="diameter-span">Master Diameter<input id="diameter-input" type="text" readonly></span><br>
						<span id="location-span">Mandrel Loc<input id="location-input" type="text" readonly></span>
						<span id="drawer-span">Drawer<input id="drawer-input" type="text" readonly></span>
						<span id="status-span">Status<input id="status-input" type="text" readonly></span><br>
						<span id="newlocation-span">New Form Loc<input id="newlocation-input" type="text" readonly></span>
						<span id="newdrawer-span">Drawer<input id="newdrawer-input" type="text" readonly></span>
						<span id="newstatus-span">Status<input id="newstatus-input" type="text" readonly></span>
					</div>
					<div class="controls">
						<div class="controls-left">
							<button id="retrieve-button" onclick="retrieveTool()" disabled>Retrieve Tool</button>
							<button id="conditions-button" onclick="goToConditions()">Tank Condition</button>
							<a href="../operations.php">Back</a>
						</div>
						<div class="controls-right">
							<button onclick="processIn()" id="process-in-button" disabled>Process In</button>
							<button onclick="buildJob(selectedRow)" id="build-button" disabled>Set to Build</button>
							<button onclick="processOut()" id="process-out-button" disabled>Process Out</button>
						</div>
						<div class="viewing-date">
							<span>Viewing Date</span><br>
							<input type="text" id="change-date-input" style="width: 120px;" onchange="changeDate()">
						</div>
					</div>
				</div>
			</div>
			<div id="arrow" onclick="showFilters()">
		 		<div class="right-arrow">
				</div>
		 	</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>