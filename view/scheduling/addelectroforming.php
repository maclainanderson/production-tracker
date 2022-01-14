<!DOCTYPE html>
<?php
/**
  *	@desc create new eform job
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
	
	//list of tools, tanks, current eform jobs, and value lists to select from
	$tools = array();
	$tanks = array();
	$activeJobs = array();
	$locations = array();
	$statuses = array();
	$defects = array();
	
	if ($conn) {
		if ($_POST['process'] == "NICKEL FLASHING") {
			$result = sqlsrv_query($conn, "SELECT ID, MANDREL, STATUS, REASON, LOCATION, DRAWER, MASTER_SIZE FROM Tool_Tree WHERE TOOL LIKE '%-[A-Z]' OR TOOL LIKE '%-[A-Z][A-Z]' ORDER BY TOOL;");
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
			
			$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER, MASTER_SIZE FROM Tool_Tree WHERE TOOL LIKE '%-[A-Z]' OR TOOL LIKE '%-[A-Z][A-Z]' ORDER BY TOOL;");
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
			$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER, MASTER_SIZE, PART_LENGTH, PART_WIDTH, FORMING_CURRENT, FORMING_TIME, BUILDING_CURRENT, TARGET_THICKNESS FROM Tool_Tree ORDER BY TOOL;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$tools[] = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TANK, STRESS, DATE FROM Tank_Stress ORDER BY TANK;");
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
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION FROM Inv_Locations WHERE STATUS = 'Active' ORDER BY LOCATION;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STATUS FROM Tool_Status WHERE STATE = 'Active' ORDER BY STATUS;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT FROM Valid_Defects WHERE STATUS = 'Active' ORDER BY DEFECT;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
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
		foreach($activeJobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
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
	  *	@desc	insert name, date, WO#
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
		
		document.getElementById("wo-input").value = getNextWorkNumber();
		
		<?php if ($_POST['process'] == "ELECTROFORMING") { ?>
		document.getElementById("date-in-input").value = formatDate(new Date());
		<?php } else { ?>
		document.getElementById("date-input").value = formatDate(new Date());
		<?php } ?>
		
		<?php if ($_POST['process'] == "ELECTROFORMING") { ?>
		document.getElementById("date-out-input").value = formatDate(new Date());
		<?php } ?>
	}
	
	/**
	  *	@desc	get next available batch#
	  *	@param	none
	  *	@return	none
	*/
	function getNextBatchNumber() {
		var conn = new XMLHttpRequest();
		var table = "Batches";
		var action = "select";
		var condition = "BATCH_NUMBER"
		var value = "(SELECT MAX(BATCH_NUMBER) FROM Batches)";
		
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
				
				batch.BATCH_NUMBER = parseInt(response[0]['BATCH_NUMBER']) + 1;
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value,false);
		conn.send();
	}
	
	/**
	  *	@desc	get next available WO#
	  *	@param	none
	  *	@return	int (max + 1) - last used WO# incremented by 1
	*/
	function getNextWorkNumber() {
		var conn = new XMLHttpRequest();
		var tables = ["Mastering","Mastering_Queue","Mastering_History","Toolroom","Toolroom_Queue","Toolroom_History","Shipping","Shipping_Queue","Shipping_History","Electroforming","Electroforming_Queue","Electroforming_History","Abort_History"];
		var action = "select";
		var condition = "WO_NUMBER";
		var max = 0;
		
		tables.forEach((item, index, array) => {
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
						if (parseInt(response[0]['WO_NUMBER']) > max) {
							max = parseInt(response[0]['WO_NUMBER']);
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+item+"&action="+action+"&condition="+condition+"&value=(SELECT MAX("+condition+") FROM "+item+")",false);
			conn.send();
		});
		
		return max + 1;
	}
	
	/**
	  *	@desc	get next available tool name
	  *	@param	none
	  *	@return	int (newForm+1) - last used tool name incremented by 1
	*/
	function getNewForm() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "MANDREL";
		var value = document.getElementById("tool-input").value;
		var newForm = 0;
		
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
		
		return newForm+1;
	}
	
	/**
	  *	@desc	fill in build current, form current, cycle time
	  *	@param	none
	  *	@return	none
	*/
	function fillCurrent() {
		if (document.getElementById("length-input").value != "" && document.getElementById("forming-density-input").value != "" && document.getElementById("forming-time-input").value != "" && document.getElementById("building-density-input").value != "" && document.getElementById("target-thickness-input").value != "") {
			var length = document.getElementById("length-input").value;
			var width = document.getElementById("width-input").value;
			if (length == 0 || width == 0) {
				var area = Math.PI * Math.pow((length / 200),2);
			} else {
				var area = (parseFloat(length) / 100) * (parseFloat(width) / 100);
			}
			
			var fmCurrent = (area * parseFloat(document.getElementById("forming-density-input").value));
			var bdCurrent = (area * parseFloat(document.getElementById("building-density-input").value));
			
			if (fmCurrent == fmCurrent.toFixed(0)) {
				document.getElementById("forming-current-input").value = fmCurrent;
			} else if (length == 0 || width == 0) {
				document.getElementById("forming-current-input").value = fmCurrent.toFixed(0);
			} else {
				document.getElementById("forming-current-input").value = parseInt(fmCurrent.toFixed(0)) + 1;
			}
			
			if (bdCurrent == bdCurrent.toFixed(0)) {
				document.getElementById("building-current-input").value = bdCurrent;
			} else if (length == 0 || width == 0) {
				document.getElementById("building-current-input").value = bdCurrent.toFixed(0);
			} else {
				document.getElementById("building-current-input").value = parseInt(bdCurrent.toFixed(0)) + 1;
			}
			
			var cycleTime = (parseFloat(document.getElementById('target-thickness-input').value) - (parseFloat(document.getElementById('forming-density-input').value) * (parseFloat(document.getElementById('forming-time-input').value)/60) * 0.01194)) / (0.01194*parseFloat(document.getElementById('building-density-input').value)) + parseFloat(document.getElementById('forming-time-input').value) / 60;
			
			document.getElementById("cycle-hours-input").value = Math.floor(cycleTime);
			
			if (Math.floor((cycleTime % 1) * 60) == (cycleTime % 1) * 60) {
				document.getElementById("cycle-minutes-input").value = (cycleTime % 1) * 60;
			} else {
				document.getElementById("cycle-minutes-input").value = Math.floor((cycleTime % 1) * 60) + 1;
			}
			
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
	  *	@return	string date - MM/DD/YY H:i
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
	  *	@desc	create/display list of tools
	  *	@param	none
	  *	@return	none
	*/
	function popToolList() {
		var searchText = document.getElementById("tool-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span>`;
		
		html += `<table id="tool-table"><thead><tr><th class="col1">Tool</th><th class="col2">Status</th><th class="col3">Reason</th><th class="col4">Location</th><th class="col5">Drawer</th></tr></thead><tbody>`;
		
		tools.forEach((item, index, array) => {
			if (item['TOOL'].toUpperCase().includes(searchText.toUpperCase())) {
				html += `<tr id="${item['ID']}" onclick="selectToolRow(this)"><td class="col1">${item['TOOL']}</td><td class="col2">${item['STATUS']}</td><td class="col3">${item['REASON']}</td><td class="col4">${item['LOCATION']}</td><td class="col5">${item['DRAWER']}</td></tr>`;
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight row in tool list
	  *	@param	DOM Object tr - selected tool row
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
	  *	@desc	insert tool to job data
	  *	@param	DOM Object tr - selected tool row
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
			
			<?php if ($_POST['process'] == "ELECTROFORMING") { ?>
			for (let tool of tools) {
				if (tool['ID'] == tr.id) {
					document.getElementById("diameter-input").value = tool['MASTER_SIZE'];
					document.getElementById("length-input").value = tool['PART_LENGTH'];
					document.getElementById("width-input").value = tool['PART_WIDTH'];
					document.getElementById("forming-density-input").value = tool['FORMING_CURRENT'];
					document.getElementById("forming-time-input").value = tool['FORMING_TIME'];
					document.getElementById("building-density-input").value = tool['BUILDING_CURRENT'];
					document.getElementById("target-thickness-input").value = tool['TARGET_THICKNESS'];
				}
			}
			<?php } ?>
			
			document.getElementById("close").click();
		}
	}
	
	/**
	  *	@desc	create/display list of tanks
	  *	@param	none
	  *	@return	none
	*/
	function popTankList() {
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span>`;
		
		html += `<table id="tank-table"><thead><tr><th class="col1">Tank</th><th class="col2">Station</th><th class="col3">Available</th><th class="col4">Schedule Type</th><th class="col5">Stress (PSI)</th><th class="col6">Date</th><th class="col7">Mandrel</th><th class="col8">Form #</th></tr></thead><tbody>`;
		
		tanks.forEach((item, index, array) => {
			for(var i=0;i<item['STATIONS'];i++) {
				isOccupied = false;
				for(var j=0;j<jobs.length;j++) {
					if (item['TANK'] == jobs[j]['TANK'] && i+1 == jobs[j]['STATION']) {
						isOccupied = true;
						html += `<tr id="${item['ID']}" onclick="selectTankRow(this)"><td class="col1">${item['TANK']}</td><td class="col2">${i+1}</td><td class="col3">No</td><td class="col4">${jobs[j]['SCHEDULE_TYPE']}</td><td class="col5">${item['STRESS']}</td><td class="col6">${item['DATE']}</td><td class="col7">${jobs[j]['TOOL_IN']}</td><td class="col8">${jobs[j]['TOOL_OUT'].split("-")[jobs[j]['TOOL_OUT'].split("-").length-1]}</td></tr>`;
						break;
					}
				}
				
				if (isOccupied == false) {
					html += `<tr id="${item['ID']}" onclick="selectTankRow(this)"><td class="col1">${item['TANK']}</td><td class="col2">${i+1}</td><td class="col3">Yes</td><td class="col4"></td><td class="col5">${item['STRESS']}</td><td class="col6">${item['DATE']}</td><td class="col7"></td><td class="col8"></td></tr>`;
				}
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight tank row
	  *	@param	DOM Object tr - tank row selected
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
	  *	@desc	insert tank into job data
	  *	@param	DOM Object tr - selected tank row
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
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
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
	  *	@desc	determine if tool is already in an eform job
	  *	@param	none
	  *	@return	int jobs.length - evaluated as false if no jobs found
	*/
	function isScheduled() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Electroforming";
		var condition = "TOOL_IN";
		var jobs = [];
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				jobs = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of jobs) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+document.getElementById("tool-input").value.replace(/[+]/g, "%2B"), false);
		conn.send();
		
		for (var i=jobs.length-1;i>=0;i--) {
			if (jobs[i]['OPERATOR_OUT']) {
				jobs.pop();
			}
		}
		
		return jobs.length;
	}
	
	/**
	  *	@desc	save job data
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == "" && !isScheduled()) {
			var d = new Date(document.getElementById("<?php if($_POST['process'] == "ELECTROFORMING") { echo "date-in-input"; } else { echo "date-input"; } ?>").value);
			
			<?php if ($_POST['process'] == "ELECTROFORMING") { ?>
			
			batch = {
				BATCH_NUMBER: "",
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date(document.getElementById("date-out-input").value)),
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value
			};
			
			getNextBatchNumber();
			
			job = {
				BATCH_NUMBER: batch.BATCH_NUMBER,
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				WO_NUMBER: getNextWorkNumber(),
				PROCESS: "ELECTROFORMING",
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
				BUILDING_CURRENT: document.getElementById("building-current-input").value
			};
			
			if (job.SCHEDULE_TYPE == 'Indefinite') {
				job.REPEAT = 0;
			}
			
			<?php } else if ($_POST['process'] == "CLEANING") { ?>
			
			batch = {
				BATCH_NUMBER: "",
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date()),
			}
			
			getNextBatchNumber();
			
			job = {
				BATCH_NUMBER: batch.BATCH_NUMBER,
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				WO_NUMBER: getNextWorkNumber(),
				PROCESS: "CLEANING",
				SEQNUM: 1,
				TARGET_DATE: formatDate(new Date()),
				TOOL_IN: document.getElementById("tool-input").value,
				DATE_IN: formatDate(new Date()),
				TOOL_OUT: document.getElementById("tool-input").value,
				DATE_OUT: formatDate(new Date()),
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
				MODE: "DONE"
			};
			
			<?php } else { ?>
			
			batch = {
				BATCH_NUMBER: "",
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date())
			};
			
			getNextBatchNumber();
			
			job = {
				BATCH_NUMBER: batch.BATCH_NUMBER,
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				WO_NUMBER: getNextWorkNumber(),
				PROCESS: "NICKEL FLASHING",
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
				PASSIVATED: document.getElementById("passivated-select").value
			};
			
			<?php } ?>
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "insert";
			var conn2 = new XMLHttpRequest();
			var table2 = "Electroforming";
			var action2 = "insert";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Insert succeeded.")) {
						var query2 = "";
						
						conn2.onreadystatechange = function() {
							if (conn2.readyState == 4 && conn2.status == 200) {
								if (conn2.responseText.includes("Insert succeeded.")) {
									if (job.PROCESS == "CLEANING") {
										alert("Job added");
										window.location.replace("electroforming.php");
									} else {
										addTool();
									}
								} else {
									alert("Batch created, but job entry failed. Contact support to correct. " + conn2.responseText);
								}
							}
						}
						
						Object.keys(job).forEach((item, index, array) => {
							query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
						})
						
						conn2.open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
						conn2.send();
					} else {
						alert("Batch not created. Contact IT Support to correct. " + conn1.responseText);
					}
				}
			}
			
			var query1 = "";
			
			Object.keys(batch).forEach((item, index, array) => {
				query1 += `&${item}=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			})
			
			conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
			conn1.send();
		} else if (isScheduled()) {
			alert("Tool already scheduled");
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	reserve new tool name
	  *	@param	none
	  *	@return	none
	*/
	function addTool() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "insert";
		
		var tool = {
			MANDREL: job.TOOL_IN,
			TOOL: job.TOOL_OUT,
			LEVEL: 0,
			STATUS: "PENDING"
		}
		
		var query = "";
		
		Object.keys(tool).forEach((item, index, array) => {
			query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					updateMandrel();
				} else {
					alert("Job scheduled, but new tool not added to database. Contact IT Support.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	update eform defaults for mandrel, if none exist
	  *	@param	none
	  *	@return	none
	*/
	function updateMandrel() {
		var mandrel = document.getElementById("tool-input").value;
		var newMandrel = {};
		for (let tool of tools) {
			if (mandrel == tool['TOOL']) {
				if (tool['PART_LENGTH'] == "" || tool['PART_LENGTH'] == "0") {
					newMandrel['PART_LENGTH'] = document.getElementById("length-input").value;
				}
				if (tool['PART_WIDTH'] == "" || tool['PART_WIDTH'] == "0") {
					newMandrel['PART_WIDTH'] = document.getElementById("width-input").value;
				}
				if (tool['FORMING_CURRENT'] == "" || tool['FORMING_CURRENT'] == "0.000") {
					newMandrel['FORMING_CURRENT'] = document.getElementById("forming-density-input").value;
				}
				if (tool['FORMING_TIME'] == "" || tool['FORMING_TIME'] == "0.000") {
					newMandrel['FORMING_TIME'] = document.getElementById("forming-time-input").value;
				}
				if (tool['BUILDING_CURRENT'] == "" || tool['BUILDING_CURRENT'] == "0.000") {
					newMandrel['BUILDING_CURRENT'] = document.getElementById("building-density-input").value;
				}
				if (tool['TARGET_THICKNESS'] == "" || tool['TARGET_THICKNESS'] == "0.000") {
					newMandrel['TARGET_THICKNESS'] = document.getElementById("target-thickness-input").value;
				}
				
				newMandrel["ID"] = tool['ID'];
			}
		}
		
		if (Object.keys(newMandrel).length > 1) {
			var conn = new XMLHttpRequest();
			var action = "update";
			var table = "Tool_Tree";
			var query = "";
			
			Object.keys(newMandrel).forEach((item, index, array) => {
				if (item != "ID") {
					query += `&${item}=${newMandrel[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`
				}
			});
			
			query += `&condition=ID&value=${newMandrel["ID"]}`;
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Data updated")) {
						alert("Job added");
						window.location.replace("electroforming.php");
					} else {
						alert("Job added, but electroforming defaults not updated. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
			conn.send();
		} else {
			alert("Job added");
			window.location.replace("electroforming.php");
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
</script>
<html>
	<head>
		<title>Add Electroforming Job</title>
		<?php if ($_POST['process'] == "CLEANING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addcleaning.css">
		<?php } else if ($_POST['process'] == "ELECTROFORMING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addelectroforming.css">
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addnickelflashing.css">
		<?php } ?>
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<?php if ($_POST['process'] == "CLEANING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input" readonly></span><span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input"></span><button onclick="wait()">Search</button><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="electroforming.php">Back</a>
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
				<span id="special-span">Special Instructions</span><textarea rows="4" cols="70" id="special-textarea"></textarea>
				<?php } else if ($_POST['process'] == "ELECTROFORMING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input" readonly></span><span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="tool-span">Mandrel<input type="text" id="tool-input"></span><button onclick="wait()">Search</button><br>
					<span id="diameter-span">Master Diameter<input type="text" id="diameter-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="electroforming.php">Back</a>
				</div>
				<div class="tank-info">
					<span id="tank-span">Tank / Station<input type="text" id="tank-input"><input type="text" id="station-input"></span><button id="tank-status-button" onclick="popTankList()">Tank Status</button><br>
					<span id="date-in-span">Date / Time In<input onkeydown="fixDate(this)" type="text" id="date-in-input"></span><br>
					<span id="date-out-span">Date / Time Out<input onkeydown="fixDate(this)" type="text" id="date-out-input"></span>
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
						<option value="Single">Single</option>
						<option value="Indefinite">Indefinite</option>
						<option value="Repeat">Repeat</option>
						<option value="Thru Generation">Thru Generation</option>
					</select></span><span id="repeat-span">Repeat<input type="number" id="repeat-input" value="1"></span>
				</div>
				<div class="part-info">
					<span id="width-label">Width</span><span id="length-label">OD/L</span><br>
					<span id="size-span">Part Size (mm)<input type="text" id="length-input" onblur="fillCurrent()">&times;<input type="text" id="width-input" onblur="fillCurrent()"></span><br>
					<span id="forming-density-span">Forming Current Density (A/sq dm)<input type="text" id="forming-density-input" onblur="fillCurrent()"></span><br>
					<span id="forming-time-span">Forming Time (min)<input type="text" id="forming-time-input" onblur="fillCurrent()"></span><br>
					<span id="building-density-span">Building Current Density (A/sq dm)<input type="text" id="building-density-input" onblur="fillCurrent()"></span><br>
					<span id="target-thickness-span">Target Form Thickness (mm)<input type="text" id="target-thickness-input" onblur="fillCurrent()"></span><br>
				</div>
				<div class="current-info">
					<span id="forming-current-span">Forming Current (Amps)<input type="text" id="forming-current-input" readonly></span><br>
					<span id="building-current-span">Building Current (Amps)<input type="text" id="building-current-input" readonly></span><br>
					<span id="cycle-time-span">Cycle Time (hrs, min)<input type="text" id="cycle-hours-input" readonly><input type="text" id="cycle-minutes-input" readonly></span>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea"></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input"></span><span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input"></span><button onclick="wait()">Search</button><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="electroforming.php">Back</a>
				</div>
				<div class="tank-info">
					<span id="tank-span">Tank / Station<input type="text" id="tank-input"><input type="text" id="station-input"></span><br>
					<span id="temp-span">Temperature<input type="text" id="temp-input"></span><br>
					<span id="time-span">Time (min)<input type="text" id="time-input"></span><br>
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
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea"></textarea>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>