<!DOCTYPE html>
<?php
/**
  *	@desc process eform job in
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//job data, mandrel data, lists to choose values from
	$locations = array();
	$statuses = array();
	$defects = array();
	$tank = array();
	$tanks = array();
	$job = array();
	$isCurrent = false;
	$mandrel = array();
	$activeJobs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION FROM Inv_Locations WHERE STATUS = 'Active' ORDER BY LOCATION;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STATUS FROM Tool_Status WHERE STATE = 'Active' ORDER BY STATUS;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT FROM Valid_Defects WHERE STATUS = 'Active' ORDER BY DEFECT;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Electroforming WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
				if ($row['OPERATOR_OUT'] == '') {
					$isCurrent = true;
				} else {
					$isCurrent = false;
				}
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Electroforming_History WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isCurrent = false;
				}
			} else {
				var_dump(sqlsrv_errors());
			}
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Electroforming_Queue WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isCurrent = false;
				}
			} else {
				var_dump(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STRESS, STRIP, TIME, CONSTANT, UNITS, MAIN, I_AUX, U_AUX, IAUX_MAIN, DATE FROM Tank_Stress WHERE TANK = '" . $job['TANK'] . "' AND CAST(DATE as DATE) = CONVERT(DATETIME,'" . ($job['OPERATOR_IN'] == '' ? date_format(date_create("now"),'m/d/y') : date_format($job['DATE_IN'],'m/d/y')) . "', 1);");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tank = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER, STATUS, REASON FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
		if ($result) {
			while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$mandrel = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, TOOL_OUT, TANK, STATION, SCHEDULE_TYPE FROM Electroforming WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$activeJobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
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
	var job = {};
	
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
	
	var tank = {<?php
		foreach($tank as $key=>$value) {
			echo '"' . $key . '": `';
			if ($value instanceof DateTime) {
				echo date_format($value,'m/d/y');
			} else {
				echo addslashes($value);
			}
			echo '`';
			echo ',';
		} ?>
	}
	
	var tanks = [<?php
		foreach($tanks as $item) {
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
	  *	@desc	insert date, operator name, tool status info
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		document.getElementById("date-input").value = <?php if ($job['OPERATOR_IN'] != '') { echo '"' . date_format($job['DATE_IN'],'m/d/y') . '"'; } else { echo 'formatDate(new Date())'; } ?>;
		document.getElementById("time-input").value = <?php if ($job['OPERATOR_IN'] != '') { echo '"' . date_format($job['DATE_IN'],'H:i:s') . '"'; } else { echo 'formatTime(new Date())'; } ?>;
		<?php if ($job['OPERATOR_IN'] != '') { ?>
		document.getElementById("operator-input").value = "<?=$job['OPERATOR_IN']?>";
		<?php } else { ?>
		document.getElementById("operator-input").value = "<?= ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") ? $_SESSION['initials'] : '' ?>";
		<?php } ?>
		
		document.getElementById("location-select").value = "<?=$mandrel['LOCATION']?>";
		document.getElementById("drawer-input").value = "<?=$mandrel['DRAWER']?>";
		document.getElementById("status-select").value = "<?=$mandrel['STATUS']?>";
		document.getElementById("defect-select").value = "<?=$mandrel['DEFECT']?>";
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
		if (year < 10) {
			year = "0" + year;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString();
		
		return date;
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - H:i:s
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
			
			cycleTime = Math.floor(cycleTime * 60) == cycleTime * 60 ? cycleTime * 60 : Math.floor(cycleTime * 60) + 1;
			
			document.getElementById("cycle-input").value = Math.floor(cycleTime / 60) + ":" + Math.floor(cycleTime % 60);
			
			var date = new Date(document.getElementById("date-input").value + " " + document.getElementById("time-input").value);
			
			var hours = parseInt(document.getElementById("cycle-input").value.split(":")[0]);
			hours *= 3600000;
			var minutes = parseInt(document.getElementById("cycle-input").value.split(":")[1]);
			minutes *= 60000;
			
			var newDate = date.getTime() + hours + minutes;
			
			var fmdate = new Date(date.getTime() + document.getElementById("forming-time-input").value * 60000);
			
			document.getElementById("forming-date-input").value = formatDate(fmdate) + " " + formatTime(fmdate);
			
			date.setTime(newDate);
			
			
			document.getElementById("date-out-input").value = formatDate(date);
			document.getElementById("time-out-input").value = formatTime(date);
		}
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById('tank-input').value == "" || document.getElementById("station-input").value == "") {
			msg = "Please enter a tank and station";
		} else if (document.getElementById("date-input").value == "" || document.getElementById("time-input").value == "") {
			msg = "Please enter a valid date";
		} else if (document.getElementById("date-out-input").value == "" || document.getElementById("time-out-input").value == "" ||
					document.getElementById("date-out-input").value.includes("NaN") || document.getElementById("time-out-input").value.includes("NaN")) {
			msg = "Please ensure current data is correct";
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
		if (tankFull(document.getElementById('tank-input').value,document.getElementById('station-input').value,
					new Date(document.getElementById('date-input').value+" "+document.getElementById('time-input').value),
					new Date(document.getElementById('date-out-input').value+" "+document.getElementById('time-out-input').value))) {
			alert("Tank/Station already in use for this date/time. Choose a different tank/station or a different time.");
			return;
		}
		
		var msg = checkFields();
		
		if (msg == '') {
			var conn = new XMLHttpRequest();
			var table = "Electroforming";
			var action = "update";
			var query = "";
			var mode;
			jobs.forEach((item, index, array) => {
				if (item['ID'] == <?=$job['ID']?>) {
					mode = item['MODE'];
				}
			});
			if (mode === undefined) {
				mode = "FORM";
			}
			job = {
				DATE_IN: formatDate(new Date(document.getElementById("date-input").value)) + " " + formatTime(new Date(document.getElementById("date-input").value + " " +document.getElementById("time-input").value)),
				OPERATOR_IN: document.getElementById("operator-input").value,
				STATUS_IN: document.getElementById("status-select").value,
				DATE_OUT: formatDate(new Date(document.getElementById("date-out-input").value)) + " " + formatTime(new Date(document.getElementById("date-out-input").value + " " + document.getElementById("time-out-input").value)),
				COMMENT: document.getElementById("comment-textarea").value,
				TANK: document.getElementById("tank-input").value,
				STATION: document.getElementById("station-input").value,
				CYCLE_TIME: document.getElementById("cycle-input").value,
				MODE: mode,
				PART_LENGTH: document.getElementById("length-input").value,
				PART_WIDTH: document.getElementById("width-input").value,
				FORMING_DENSITY: document.getElementById("forming-density-input").value,
				FORMING_TIME: document.getElementById("forming-time-input").value,
				BUILDING_DENSITY: document.getElementById("building-density-input").value,
				TARGET_THICKNESS: document.getElementById("target-thickness-input").value,
				FORMING_CURRENT: document.getElementById("forming-current-input").value,
				BUILDING_CURRENT: document.getElementById("building-current-input").value,
				FM_DATE: document.getElementById("forming-date-input").value,
				id: <?=$job['ID']?>
			}
			
			Object.keys(job).forEach((item, index, array) => {
				if (job[item]) {
					if (item != 'id') {
						query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					} else {
						query += `&condition=id&value=${job[item]}`;
					}
				}
			});
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Data updated")) {
						if (document.getElementById("comment-textarea").value.length > 0) {
							saveComment();
						} else {
							saveTool();
						}
					} else {
						alert("Data update failed. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn.send();
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	save comment on tool
	  *	@param	none
	  *	@return	none
	*/
	function saveComment() {
		var conn = new XMLHttpRequest();
		var table = "Comment_History";
		var action = "insert";
		var query = "";
		comment = {
			COMMENT: document.getElementById("comment-textarea").value,
			PROCESS: "Electroforming",
			TOOL: document.getElementById("tool-input").value,
			DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value
		}
		
		Object.keys(comment).forEach((item, index, array) => {
			query += `&${item}=${comment[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					saveTool();
				} else {
					alert("Comment not recorded. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	update tool status
	  *	@param	none
	  *	@return	none
	*/
	function saveTool() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "update";
		var query = "";
		tool = {
			STATUS: document.getElementById("status-select").value,
			REASON: document.getElementById("defect-select").value,
			LOCATION: document.getElementById("location-select").value,
			DRAWER: document.getElementById("drawer-input").value,
			DATE_MODIFIED: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value,
			TOOL: document.getElementById("tool-input").value
		}
		
		Object.keys(tool).forEach((item, index, array) => {
			if (item != "TOOL") {
				query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			} else {
				query += `&condition=TOOL&value=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					alert("Job updated");
					document.getElementsByTagName("body")[0].innerHTML += `<form action="electroforming.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
					document.getElementById("return-form").submit();
				} else {
					alert("Tool not updated. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	determine if tank/station is in use for chosen date
	  *	@param	string tank - tank for this job, string station - station for this job,
	  			Date dateIn - date this job goes in, Date dateOut - date this job goes out
	  *	@return	array job - job data
	*/
	function tankFull(tank, station, dateIn, dateOut) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Electroforming";
		var query = "&condition=TANK&value="+tank+"&condition2=STATION&value2="+station;
		var full = false;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				/*var jobs = conn.responseText.split("Array");
				jobs.shift();
				for (var i=0;i<jobs.length;i++) {
					jobs[i] = jobs[i].split(">");
					jobs[i].shift();
					for (var j=0;j<jobs[i].length;j++) {
						if (jobs[i][j].includes("DateTime")) {
							jobs[i][j] = jobs[i][j+1].split("[")[0].trim();
							jobs[i].splice(j+1,3);
						} else {
							jobs[i][j] = jobs[i][j].split("[")[0];
							if (j==jobs[i].length-1) {
								jobs[i][j] = jobs[i][j].split(")")[0];
							}
							jobs[i][j] = jobs[i][j].trim();
						}
					}
				}
				
				for (var i=0;i<jobs.length;i++) {
					if (
						(
							jobs[i]['TOOL_IN'] != document.getElementById('tool-input').value
						) && (
							jobs[i]['OPERATOR_IN'] != '' && jobs[i]['OPERATOR_OUT'] == ''
						)
					   ) {
						full = true;
					}
				}*/
				var jobs = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of jobs) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date'])) + " " + formatTime(new Date(job[x]['date']));
						}
					}
				}
				
				for (var i=0;i<jobs.length;i++) {
					if (
						(
							jobs[i]['TOOL_IN'] != document.getElementById('tool-input').value
						) && (
							(
								jobs[i]['OPERATOR_IN'] != '' && jobs[i]['OPERATOR_IN'] != null
							) && (
								jobs[i]['OPERATOR_OUT'] == null || jobs[i]['OPERATOR_OUT'] == ''
							)
						)
					   ) {
						full = true;
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query,false);
		conn.send();
		
		return full;
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
					if (item['TANK'] == jobs[j]['TANK'] && i+1 == jobs[j]['STATIONS']) {
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
	  *	@desc	auto-format time fields to HH:ii:ss
	  *	@param	DOM Object input - time field to format
	  *	@return	none
	*/
	function fixTime(input) {
		var key = event.keyCode || event.charCode;
		
		var regex = /\/|\-|\\|\*|\:/;
		
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
						input.value = "0" + input.value.slice(0,-1) + ":";
					} else {
						input.value += ":";
					}
					break;
				case 5:
					if (regex.test(input.value.charAt(4))) {
						var inputArr = input.value.split(regex);
						inputArr.pop();
						input.value = inputArr[0] + "/0" + inputArr.pop() + ":";
					} else {
						input.value += ":";
					}
					break;
				case 3:
				case 6:
					if (!regex.test(input.value.slice(-3))) {
						input.value = input.value.slice(0,-1) + ":" + input.value.slice(-1);
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
		<?php if ($_POST['source'] == "retrieve.php") { ?>
		document.getElementsByTagName("body")[0].innerHTML += `<form action="/view/retrieve.php" method="POST" id="return-form" style="display: none;"><input type="text" value="/view/operations/electroforming.php" name="returnpath"><input type="text" value="${'<?=$_POST['tool']?>'}" name="tool"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>`;
		<?php } else { ?>
		document.getElementsByTagName("body")[0].innerHTML += `<form action="electroforming.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
		<?php } ?>
		document.getElementById("return-form").submit();
	}
</script>
<html>
	<head>
		<title>Tank In</title>
		<link rel="stylesheet" type="text/css" href="/styles/electroformingin.css">
	</head>
	<body onload="initialize(); fillCurrent();">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><br>
					<span id="tool-span">Mandrel<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>" readonly></span><br>
					<span id="location-span">Location<select id="location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span>
					<span id="drawer-span">Drawer<input type="text" id="drawer-input"></span><br>
					<span id="status-span">Status<select id="status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span>
					<span id="defect-span">Defect<select id="defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><span id="date-span">Date In<input onkeydown="fixDate(this)" type="text" id="date-input" onblur="fillCurrent()"></span><span id="time-span">Time<input onkeydown="fixTime(this)" type="text" id="time-input" onblur="fillCurrent()"></span><br>
					<span id="date-out-span">Date Out<input type="text" id="date-out-input" readonly></span><span id="time-out-span">Time<input type="text" id="time-out-input" readonly></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { ?> disabled<?php } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="main">
					<div class="part-info">
						<span id="width-label">Width</span><span id="length-label">OD/L</span><br>
						<span id="size-span">Part Size (mm)<input type="text" id="length-input" onblur="fillCurrent()" value="<?=$job['PART_LENGTH']?>">×<input type="text" id="width-input" onblur="fillCurrent()" value="<?=$job['PART_WIDTH']?>"></span><br>
						<span id="forming-density-span">Forming Current Density (A/sq dm)<input type="text" id="forming-density-input" onblur="fillCurrent()" value="<?=$job['FORMING_DENSITY']?>"></span><br>
						<span id="forming-time-span">Forming Time (min)<input type="text" id="forming-time-input" onblur="fillCurrent()" value="<?=$job['FORMING_TIME']?>"></span><br>
						<span id="building-density-span">Building Current Density (A/sq dm)<input type="text" id="building-density-input" onblur="fillCurrent()" value="<?=$job['BUILDING_DENSITY']?>"></span><br>
						<span id="target-thickness-span">Target Form Thickness (mm)<input type="text" id="target-thickness-input" onblur="fillCurrent()" value="<?=$job['TARGET_THICKNESS']?>"></span>
					</div>
					<div class="current-info">
						<span id="forming-current-span">Forming Current (Amps)<input type="text" id="forming-current-input" value="<?=$job['FORMING_CURRENT']?>" readonly></span><br>
						<span id="building-current-span">Building Current (Amps)<input type="text" id="building-current-input" value="<?=$job['BUILDING_CURRENT']?>" readonly></span><br>
						<span id="cycle-time-span">Cycle Time (hrs, min)<input type="text" id="cycle-input" value="<?=$job['CYCLE_TIME']?>" readonly></span><br>
						<span id="forming-date-span">Projected FM Time<input type="text" id="forming-date-input"></span>
					</div>
				</div>
				<div class="tank-info">
					<span id="tank-span">Tank/Station<input type="text" id="tank-input" value="<?=$job['TANK']?>" readonly><input type="text" id="station-input" value="<?=$job['STATION']?>" readonly></span><button onclick="popTankList()">Change</button>
					<div id="stress-div">
						<span id="stress-span">Stress<br><input type="text" id="stress-input" value="<?=$tank['STRESS']?>" readonly></span>
						<span id="stress-date-span">Date<br><input type="text" id="stress-date-input" value="<?=date_format($tank['DATE'],'m/d/y')?>" readonly></span>
						<span id="strip-span">Strip<br><input type="text" id="strip-input" value="<?=$tank['STRIP']?>" readonly></span>
						<span id="mins-span">Mins<br><input type="text" id="mins-input" value="<?=$tank['TIME']?>" readonly></span>
						<span id="constant-span">Constant<br><input type="text" id="constant-input" value="<?=$tank['CONSTANT']?>" readonly></span>
						<span id="units-span">Units<br><input type="text" id="units-input" value="<?=$tank['UNITS']?>" readonly></span>
						<span id="imain-span">Imain<br><input type="text" id="imain-input" value="<?=$tank['MAIN']?>" readonly></span>
						<span id="iaux-span">Iaux<br><input type="text" id="iaux-input" value="<?=$tank['I_AUX']?>" readonly></span>
						<span id="uaux-span">Uaux<br><input type="text" id="uaux-input" value="<?=$tank['U_AUX']?>" readonly></span>
						<span id="iaux-imain-span">Iaux/Imain<br><input type="text" id="iaux-imain-input" value="<?=$tank['IAUX_MAIN']?>" readonly></span>
					</div>
				</div>
				<div class="comments">
					<span id="comment-span">Comments</span><textarea rows="4" cols="70" id="comment-textarea"></textarea><br>
					<span id="special-span">Special Instructions</span><textarea rows="4" cols="70" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>