<!DOCTYPE html>
<?php
/**
  *	@desc main eform scheduling page
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
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of jobs, processes, reasons to abort
	$processes = array();
	$reasons = array();
	$jobs = array();
	$incomingJobs = array();
	$newForms = array();
	$incomingNewForms = array();
	$masterDiameters = array();
	$mandrelLocations = array();
	$toolLocations = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE DEPARTMENT = 'ELECTROFOR' ORDER BY PROCESS ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, REASON FROM Abort_Work_Order ORDER BY REASON ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$reasons[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TOOL_IN, DATE_IN, TOOL_OUT, DATE_OUT, OPERATOR_IN, STATUS_IN, OPERATOR_OUT, STATUS_OUT, TANK, STATION, CYCLE_TIME, MODE, SCHEDULE_TYPE, FORMING_TIME, BATCH_NUMBER, ON_HOLD FROM Electroforming;");
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
		
		$result = sqlsrv_query($conn, "SELECT ID, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TOOL_IN, DATE_IN, TOOL_OUT, DATE_OUT, OPERATOR_IN, STATUS_IN, OPERATOR_OUT, STATUS_OUT, TANK, STATION, CYCLE_TIME, MODE, SCHEDULE_TYPE, FORMING_TIME, BATCH_NUMBER, ON_HOLD FROM Electroforming_Queue;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$incomingJobs[] = $row;
				$newMax = explode("-",$row['TOOL_OUT']);
				if (strpos($row['TOOL_OUT'], "EN") !== false) {
					$incomingNewForms[$row['ID']] = "/EN";
				} else {
					$incomingNewForms[$row['ID']] = $newMax[count($newMax) - 1];
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
	var incoming = false;
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
	
	var incomingJobs = [<?php
		foreach($incomingJobs as $job) {
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
		foreach($newForms as $id=>$newForm) {
			echo $id . ": `" . $newForm . "`,";
		}?>
	}
	
	var incomingNewForms = {<?php
		foreach($incomingNewForms as $id=>$newForm) {
			echo $id . ": `" . $newForm . "`,";
		}?>
	}
	
	var masterDiameters = {<?php
		foreach($masterDiameters as $id => $diameter) {
			echo "'" . $jobs[$id]['WO_NUMBER'] . "': '" . $diameter . "'," . PHP_EOL;
		}
	?>};
	
	var mandrelLocations = {<?php
		foreach($mandrelLocations as $id => $mandrelLocation) {
			echo "'" . $jobs[$id]['WO_NUMBER'] . "': {'LOCATION': '" . $mandrelLocation[0] . "','DRAWER': '" . $mandrelLocation[1] . "'}," . PHP_EOL;
		}
	?>}
	
	var toolLocations = {<?php
		foreach($toolLocations as $id => $toolLocations) {
			echo "'" . $jobs[$id]['WO_NUMBER'] . "': {'LOCATION': '" . $toolLocations[0] . "','DRAWER': '" . $toolLocations[1] . "'}," . PHP_EOL;
		}
	?>}
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	//up/down keys scroll through job list
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
	  *	@desc	open sort/filter box, if session variable exists and is true
	  *	@param	none
	  *	@return	none
	*/
	function checkSortBox() {
		if (checkCookie("sort_expanded") && getCookie("sort_expanded") == "true") {
			document.getElementById("arrow").click();
			document.getElementsByClassName("filter-inner")[0].children[2].click();
		}
	}
	
	/**
	  *	@desc	create/display list of processes
	  *	@param	none
	  *	@return	none
	*/
	function popProcessList() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		modalContent.style.width = "250px";
		
		var html = '<span class="close" id="close">&times;</span>';
		processes.forEach((item, index, array) => {
			if (index%2 == 0) {
				html += `<span id="${item['ID']}" style="display: inline-block; background-color: #ddd; width: 100%; margin-bottom: 3px;" onclick="selectProcessRow(this)">${item['PROCESS']}</span><br>`;
			} else {
				html += `<span id="${item['ID']}" style="display: inline-block; background-color: #fff; width: 100%; margin-bottom: 3px;" onclick="selectProcessRow(this)">${item['PROCESS']}</span><br>`;
			}
		});
		
		modalContent.innerHTML = html;
		
		closeForm();		
	}
	
	/**
	  *	@desc	highlight selected process, unhighlight others
	  *	@param	DOM Object span - selected process
	  *	@return	none
	*/
	function selectProcessRow(span) {
		if (span.style.backgroundColor != "#000") {
			Array.prototype.slice.call(span.parentNode.children).forEach((item, index, array) => {
				if (index > 0) {
					if (index%4 == 1) {
						item.style.backgroundColor = "#ddd";
						item.style.color = "#000";
					} else {
						item.style.backgroundColor = "#fff";
						item.style.color = "#000";
					}
				}
				if (index%2 == 1) {
					item.setAttribute('onclick','selectProcessRow(this)');
				}
			});
			span.style.backgroundColor = "#000";
			span.style.color = "#fff";
			span.setAttribute('onclick','confirmProcess(this)');
		}
	}
	
	/**
	  *	@desc	create/submit form for new eforming job
	  *	@param	DOM Object span - selected process
	  *	@return	none
	*/
	function confirmProcess(span) {
		var body = document.getElementsByTagName("BODY")[0];
		body.innerHTML += `<form style="display: none;" action="addelectroforming.php" method="post" id="process-form"><input name="process" type="text" value="${span.innerHTML}"></form>`;
		document.getElementById("process-form").submit();
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
	  *	@desc	highlight selected row, unhighlight others
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		var trs = tr.parentNode.children;
		
		for (var i=0;i<trs.length;i++) {
			if (!incoming) {
				for (var j=0;j<jobs.length;j++) {
					if (trs[i].id == jobs[j]['ID']) {
						setColor(trs[i],j);
					}
				}
			} else {
				for (var j=0;j<incomingJobs.length;j++) {
					if (trs[i].id == incomingJobs[j]['ID']) {
						setColor(trs[i],j);
					}
				}
			}
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		selectedRow = tr.id;
		
		getDetails();
	}
	
	/**
	  *	@desc	grab details of job
	  *	@param	none
	  *	@return	none
	*/
	function getDetails() {
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (item['ID'] == selectedRow) {
					document.getElementById("tool-input").value = item['TOOL_OUT'];
					document.getElementById("po-input").value = item['PO_NUMBER'];
					document.getElementById("job-input").value = item['JOB_NUMBER'];
					document.getElementById("wo-input").value = item['WO_NUMBER'];
					document.getElementById("schedule-input").value = item['SCHEDULE_TYPE'];
					document.getElementById("operator-in-input").value = item['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = item['OPERATOR_OUT'];
					document.getElementById("diameter-input").value = masterDiameters[item['WO_NUMBER']];
					document.getElementById("location-input").value = mandrelLocations[item['WO_NUMBER']]['LOCATION'];
					document.getElementById("drawer-input").value = mandrelLocations[item['WO_NUMBER']]['DRAWER'];
					document.getElementById("status-input").value = item['STATUS_IN'];
					document.getElementById("newlocation-input").value = toolLocations[item['WO_NUMBER']]['LOCATION'];
					document.getElementById("newdrawer-input").value = toolLocations[item['WO_NUMBER']]['DRAWER'];
					document.getElementById("newstatus-input").value = item['STATUS_OUT'];
					if (item['OPERATOR_IN'] == "") {
						document.getElementById("hold-button").disabled = false;
						if (item['ON_HOLD'] == "TRUE") {
							document.getElementById("hold-button").innerHTML = "Take Off Hold";
						} else {
							document.getElementById("hold-button").innerHTML = "Place On Hold";
						}
					} else {
						document.getElementById("hold-button").disabled = true;
					}
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (item['ID'] == selectedRow) {
					document.getElementById("tool-input").value = item['TOOL_OUT'];
					document.getElementById("po-input").value = item['PO_NUMBER'];
					document.getElementById("job-input").value = item['JOB_NUMBER'];
					document.getElementById("wo-input").value = item['WO_NUMBER'];
					document.getElementById("schedule-input").value = item['SCHEDULE_TYPE'];
					document.getElementById("operator-in-input").value = item['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = item['OPERATOR_OUT'];
					document.getElementById("status-input").value = item['STATUS_IN'];
					document.getElementById("newstatus-input").value = item['STATUS_OUT'];
					if (item['OPERATOR_IN'] == "") {
						document.getElementById("hold-button").disabled = false;
						if (item['ON_HOLD'] == "TRUE") {
							document.getElementById("hold-button").innerHTML = "Take Off Hold";
						} else {
							document.getElementById("hold-button").innerHTML = "Place On Hold";
						}
					} else {
						document.getElementById("hold-button").disabled = true;
					}
				}
			});
		}
		document.getElementById("edit-button").disabled = false;
		document.getElementById("abort-button").disabled = false;
		document.getElementById("retrieve-button").disabled = false;
		document.getElementById("hold-button").disabled = false;
	}
	
	/**
	  *	@desc	setColor handler
	  *	@param	none
	  *	@return	none
	*/
	function setColors() {
		var trs = document.getElementsByClassName('main')[0].children[0].children[1].children;
		for (var i=0;i<trs.length;i++) {
			if (!incoming) {
				for (var j=0;j<jobs.length;j++) {
					if (trs[i].id == jobs[j]['ID']) {
						setColor(trs[i],j);
					}
				}
			} else {
				for (var j=0;j<incomingJobs.length;j++) {
					if (trs[i].id == incomingJobs[j]['ID']) {
						setColor(trs[i],j);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	modify row color according to job status
	  *	@param	DOM Object tr - row to modify, int index - array index to grab job status from
	  *	@return	none
	*/
	function setColor(tr, index) {
		if (!incoming) {
			var d = new Date(jobs[index]['DATE_IN']);
			var d2 = new Date();
			var d3 = new Date(jobs[index]['DATE_OUT']);
			
			if (jobs[index]['ON_HOLD'] == "TRUE") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#e0e";
			} else {
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
		} else {
			var d = new Date(incomingJobs[index]['DATE_IN']);
			var d2 = new Date();
			var d3 = new Date(incomingJobs[index]['DATE_OUT']);
			
			if (incomingJobs[index]['ON_HOLD'] == "TRUE") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#e0e";
			} else {
				if (incomingJobs[index]['OPERATOR_IN'] == "") {
					//Green/Black line
					tr.style.color = "black";
					tr.style.backgroundColor = "#0f0";
				} else if (incomingJobs[index]['MODE'] != "BUILD" && incomingJobs[index]['MODE'] != "DONE") {
					if (((d2-d) / 1000 / 60) < incomingJobs[index]['FORMING_TIME']) {
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
						if (incomingJobs[index]['MODE'] == "DONE") {
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
		}
	}
	
	/**
	  *	@desc	create/submit form to edit job page
	  *	@param	none
	  *	@return	none
	*/
	function editJob() {
		document.getElementsByTagName("BODY")[0].innerHTML += "<form action=\"editelectroforming.php\" method=\"POST\" style=\"display: none;\" id=\"edit-form\"><input type=\"text\" name=\"process\" value=\"" + document.getElementById(selectedRow).children[0].innerHTML + "\"><input type=\"text\" name=\"id\" value=\"" + selectedRow + "\"><input type=\"submit\"></form>";
		document.getElementById("edit-form").submit();
	}
	
	/**
	  *	@desc	move job to abort history
	  *	@param	none
	  *	@return	none
	*/
	function abortJob() {
		var job;
		var toolToDelete;
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (item['ID'] == selectedRow) {
					job = {
						BATCH_NUMBER: item['BATCH_NUMBER'],
						TOOL: item['TOOL_IN'],
						PROCESS: item['PROCESS'],
						DEPARTMENT: "Electroforming",
						WO_NUMBER: item['WO_NUMBER'],
						DATE: formatDate(new Date())
					}
					
					toolToDelete = item['TOOL_OUT'];
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (item['ID'] == selectedRow) {
					job = {
						BATCH_NUMBER: item['BATCH_NUMBER'],
						TOOL: item['TOOL_IN'],
						PROCESS: item['PROCESS'],
						DEPARTMENT: "Electroforming",
						WO_NUMBER: item['WO_NUMBER'],
						DATE: formatDate(new Date())
					}
					
					toolToDelete = item['TOOL_OUT'];
				}
			});
		}
		
		var modal = document.getElementById('modal');
		document.getElementById("modal").style.display = "block";
		var modalContent = document.getElementById("modal-content");
		modalContent.innerHTML = `<span id="close">&times;</span>
								  <span>Reason for aborting job:</span><br>
								  <select id="reason-select">
								  <?php foreach($reasons as $reason) { ?>
								  <option value="<?=$reason['REASON']?>"><?=$reason['REASON']?></option>
								  <?php } ?>
								  </select><br>
								  <input onblur="this.value = this.value.toUpperCase();" type="text" id="abort-operator-input" <?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?> value="<?=$_SESSION['initials']?>" <?php } ?>>
								  <button id="submit-abort">Submit</button>`;
								  
		document.getElementById("submit-abort").addEventListener('click', function() {
			var conn = new XMLHttpRequest();
			var table = "Abort_History";
			var action = "insert";
			
			job.REASON = document.getElementById("reason-select").value;
			job.OPERATOR = document.getElementById("abort-operator-input").value;
			var query = "";
			
			Object.keys(job).forEach((item, index, array) => {
				query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			});
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						if (deleteTool(toolToDelete, job.PROCESS)) {
							deleteJob(job.BATCH_NUMBER, job.WO_NUMBER);
						} else {
							alert("Job aborted, but tool name still reserved. Contact IT Support to correct. " + conn.responseText);
						}
					} else {
						alert("Could not delete job. Contact support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
			conn.send();
		});
		
		closeForm();
	}
	
	/**
	  *	@desc	remove reserved tool from tree
	  *	@param	string tool - tool to remove, string process - only remove tool on certain values
	  *	@return	true on success, false otherwise
	*/
	function deleteTool(tool, process) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Tool_Tree";
		var query = "&TOOL="+tool.replace(/[+]/g,"%2B");
		var success = false;
		
		if (process != "CLEANING" && !hasChildren(tool)) {
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Deletion succeeded")) {
						success = true;
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
			conn.send();
		} else {
			success = true;
		}
		
		return success;
	}
	
	/**
	  *	@desc	find if tool has children in the tree
	  *	@param	string tool - name of tool to search
	  *	@return	int - number of children
	*/
	function hasChildren(tool) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Tree";
		var query = "&condition=MANDREL&value="+tool.replace(/[+]/g,"%2B");
		var count = 0;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				count = result.length;
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
		conn.send();
		
		return count;
	}
	
	/**
	  *	@desc	remove job from current schedule
	  *	@param	int batch - passed to deleteBatch/getJobs, int wo - job id to delete
	  *	@return	none
	*/
	function deleteJob(batch, wo) {
		var conn = new XMLHttpRequest();
		if (!incoming) {
			var table = "Electroforming";
		} else {
			var table = "Electroforming_History";
		}
		var action = "delete";
		var query = "&WO_NUMBER="+wo;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					if (getJobs(batch) >= 1) {
						if (!incoming) {
							alert("Job aborted");
							window.location.replace("electroforming.php");
						} else {
							addNextJob();
						}
					} else {
						deleteBatch(batch);
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	remove batch from batch list
	  *	@param	int batch - batch id to delete
	  *	@return	none
	*/
	function deleteBatch(batch) {
		var conn = new XMLHttpRequest();
		var table = "Batches";
		var action = "delete";
		var query = "&BATCH_NUMBER="+batch;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					if (!incoming) {
						alert("Job aborted");
						window.location.replace("electroforming.php");
					} else {
						addNextJob(batch);
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	fetch # of jobs by batch num
	  *	@param	int batchNumber - batch id to search for
	  *	@return	int - # of jobs
	*/
	function getJobs(batchNumber) {
		var conn = new XMLHttpRequest();
		var tables = ["Mastering","Mastering_Queue","Toolroom","Toolroom_Queue","Electroforming","Electroforming_Queue","Shipping","Shipping_Queue"];
		var action = "select";
		var condition = "BATCH_NUMBER";
		var value = batchNumber;
		var jobs = 0;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var job = conn.responseText.split("Array");
				job.shift();
				for (var i=0;i<job.length;i++) {
					job[i] = job[i].split(">");
					job[i].shift();
					for (var j=0;j<job[i].length;j++) {
						if (job[i][j].includes("DateTime")) {
							job[i][j] = job[i][j+1].split("[")[0].trim();
							job[i].splice(j+1,3);
						} else {
							job[i][j] = job[i][j].split("[")[0];
							if (j==job[i].length-1) {
								job[i][j] = job[i][j].split(")")[0];
							}
							job[i][j] = job[i][j].trim();
						}
					}
				}
				jobs += job.length;
			}
		}
		
		tables.forEach((item, index, array) => {
			conn.open("GET","/db_query/sql2.php?table="+item+"&action="+action+"&condition="+condition+"&value="+value,false);
			conn.send();
		});
		
		return jobs;
	}
	
	/**
	  *	@desc	move next job in batch to current schedule
	  *	@param	none
	  *	@return	none
	*/
	function addNextJob() {
		var next = findNextJob(batch);
		if (next != "" && next != undefined) {
			var conn = new XMLHttpRequest();
			var action = "insert";
			var query = "";
			var table = next["TABLE"].split("_")[0];
			delete next.TABLE;
			
			Object.keys(next).forEach((item, index, array) => {
				query += `&${item}=${next[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			})
			
			conn.onreadystatechange = function() {
				if(conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						removeNextFromQueue(next.WO_NUMBER, table);
					} else {
						alert("Next job in batch not added. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn.send();
		} else {
			alert("Job completed");
			window.location.replace("mastering.php");
		}
	}
	
	/**
	  *	@desc	get next job in batch
	  *	@param	none
	  *	@return	array containing job data
	*/
	function findNextJob(batch) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var condition = "BATCH_NUMBER";
		var jobs = [];
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of result) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				
				result.forEach((item, index, array) => {
					item['TABLE'] = conn.responseURL.split("table=")[1].split("&")[0];
				});
				
				for (var i=0;i<result.length;i++) {
					jobs.push(result[i]);
				}
			}
		}
		
		for (var i=0;i<tables.length;i++) {
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&condition="+condition+"&value="+batch,false);
			conn.send();
		}
		
		nextSeqNum = 100;
		nextJob = 0;
		
		jobs.forEach((item, index, array) => {
			if (item[5] < nextSeqNum && item[7] == job.TOOL_IN) {
				nextSeqNum = item[5];
				nextJob = index;
			}
		});
		
		return jobs[nextJob];
	}
	
	/**
	  *	@desc	remove next job from queue
	  *	@param	int woNumber - job id to remove, string table - table to remove from
	  *	@return	none
	*/
	function removeNextFromQueue(woNumber, table) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = table + "_Queue";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Job completed");
					window.location.replace("electroforming.php");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WO_NUMBER="+woNumber,false);
		conn.send();
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
	  *	@desc	sort job list by given column
	  *	@param	string value - column to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_electroforming_scheduling_order",document.getElementById("order-type").value);
		setCookie("sort_electroforming_scheduling_filter",document.getElementById("filter-type").value);
		setCookie("sort_electroforming_scheduling_filter_value",document.getElementById("filter-input").value);
		
		if (value == "none") {
			fillSort();
			return;
		}
		
		if (!incoming) {
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
						if (a['ON_HOLD'] == 'TRUE') {
							if (b['ON_HOLD'] == 'TRUE') {
								return 0;
							} else {
								return -1;
							}
							//Purple/black
							//"black"
							//"#f0f"
						} else if (a['OPERATOR_IN'] == "") {
							if (b['ON_HOLD'] == 'TRUE') {
								return 1;
							} else if (b['OPERATOR_IN'] == "") {
								return 0;
							} else {
								return -1;
							}
							//Green/Black line
							//"black"
							//"#0f0"
						} else if (a['MODE'] != "BUILD" && a['MODE'] != 'DONE') {
							if (((date-ad) / 1000 / 60) < a['FORMING_TIME']) {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else if (((date-bd) / 1000 / 60) < b['FORMING_TIME']) {
									return 0;
								} else {
									return -1;
								}
								//Yellow/black
								//"black"
								//"#ff0"
								//"FORM"
							} else {
								if (b['MODE'] == "BUILD" || b['MODE'] == "DONE") {
									return -1;
								} else if (b['OPERATOR_IN'] == "" || ((date-bd) / 1000 / 60) < b['FORMING_TIME']) {
									return 1;
								} else {
									return 0;
								}
								//Yellow/red
								//"#f00"
								//"#ff0"
								//"RAMP"
							}
						} else if (a['MODE'] == 'DONE') {
							if (b['MODE'] == 'DONE') {
								return 0;
							} else {
								return 1;
							}
						} else {
							if ((((ad2-date) / 1000 / 60) < 15) && (((ad2-date) / 1000 / 60) > 0)) {
								if ((((bd2-date) / 1000 / 60) > 15) || (b['MODE'] != "BUILD" && b['MODE'] != "DONE")) {
									return 1;
								} else if ((((bd2-date) / 1000 / 60) <= 0) && (b['MODE'] == "BUILD" || b['MODE'] == "DONE")) {
									return -1;
								} else {
									return 0;
								}
								//Red/black
								//"black"
								//"#f00"
								//"BUILD"
							} else if ((ad2 - date) <= 0) {
								if (((bd2-date) / 1000 / 60) > 0 || (b['MODE'] != "BUILD" && b['MODE'] != "DONE") || b['OPERATOR_IN'] == "") {
									return 1;
								} else if (b['MODE'] == "DONE") {
									return -1;
								} else {
									return 0;
								}
								//Red/yellow
								//"#ff0"
								//"#f00"
								//"READY"
							} else if (date.getFullYear() == ad2.getFullYear() && date.getMonth() == ad2.getMonth() && date.getDate() == ad2.getDate()) {
								if ((((bd2-date) / 1000 / 60) < 15) && (b['MODE'] == "BUILD" || b['MODE'] == "DONE")) {
									return -1;
								} else if ((date.getFullYear() == bd2.getFullYear() && date.getMonth() == bd2.getMonth() && date.getDate() == bd2.getDate()) && b['MODE'] == "BUILD") {
									return 0;
								} else {
									return 1;
								}
								//Blue/black
								//"black"
								//"#00e6ff"
								//"BUILD"
							} else {
								if (b['OPERATOR_IN'] == "" || (b['MODE'] != "BUILD" && b['MODE'] != "DONE")) {
									return 1;
								} else if ((date.getFullYear() == bd2.getFullYear() && date.getMonth() == bd2.getMonth() && date.getDate() == bd2.getDate()) || (((bd2-date) / 1000 / 60) < 15)) {
									return -1;
								} else {
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
		} else {
			incomingJobs.sort(function(a, b) {
				var ad = new Date(a['DATE_IN']);
				var ad2 = new Date(a['DATE_OUT']);
				var bd = new Date(b['DATE_IN']);
				var bd2 = new Date(b['DATE_OUT']);
				var date = new Date();
				
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
						if (a['OPERATOR_IN'] == "") {
							if (b['OPERATOR_IN'] == "") {
								return 0;
							} else {
								return -1;
							}
							//Green/Black line
							//"black"
							//"#0f0"
						} else if (a['MODE'] != "BUILD") {
							if (((date-ad) / 1000 / 60) < a['FORMING_TIME']) {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else if (((date-bd) / 1000 / 60) < b['FORMING_TIME']) {
									return 0;
								} else {
									return -1;
								}
								//Yellow/black
								//"black"
								//"#ff0"
								//"FORM"
							} else {
								if (b['MODE'] == "BUILD") {
									return -1;
								} else if (b['OPERATOR_IN'] == "" || ((date-bd) / 1000 / 60) < b['FORMING_TIME']) {
									return 1;
								} else {
									return 0;
								}
								//Yellow/red
								//"#f00"
								//"#ff0"
								//"RAMP"
							}
						} else {
							if ((((ad2-date) / 1000 / 60) < 15) && (((ad2-date) / 1000 / 60) > 0)) {
								if (((bd2-date) / 1000 / 60) > 15) {
									return 1;
								} else if (((bd2-date) / 1000 / 60) <= 0) {
									return -1;
								} else {
									return 0;
								}
								//Red/black
								//"black"
								//"#f00"
								//"BUILD"
							} else if ((ad2 - date) <= 0) {
								if (((bd2-date) / 1000 / 60) > 0 || b['MODE'] != "BUILD" || b['OPERATOR_IN'] == "") {
									return 1;
								} else {
									return 0;
								}
								//Red/yellow
								//"#ff0"
								//"#f00"
								//"DONE"
							} else {
								if (b['OPERATOR_IN'] == "" || b['MODE'] != "BUILD") {
									return 1;
								} else if (((bd2-date) / 1000 / 60) < 15) {
									return -1;
								} else {
									return 0;
								}
								//White
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
		}
		
		fillSort();
	}
	
	/**
	  *	@desc	fill newly sorted list
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[0].children[1];
		var html = "";
		if (document.getElementById("filter-type")) {
			var value = document.getElementById("filter-type").value;
			var keyword = document.getElementById("filter-input").value;
			if (value != "linecolor") {
				keyword = keyword.toUpperCase();
			}
		} else {
			value = "none";
			keyword = "";
		}
		
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (isAllowed(keyword, value, item)) {
					html += `<tr id="${item['ID']}" onclick="selectRow(this)">
											<td class="col1">${item['PROCESS'] == "ELECTROFORMING" ? "EFORM" : item['PROCESS'] == "NICKEL FLASHING" ? "NI FLASH" : item['PROCESS']}</td>
											<td class="col2">${item['TOOL_IN']}</td>
											<td class="col3">${item['DATE_IN']}</td>
											<td class="col4">${item['DATE_OUT']}</td>
											<td class="col5">${item['TANK']}</td>
											<td class="col6">${item['STATION']}</td>
											<td class="col7">${item['CYCLE_TIME']}</td>
											<td class="col8">${newForms[item['ID']]}</td>
											<td class="col9"></td>
										</tr>`;
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (isAllowed(keyword, value, item)) {
					html += `<tr id="${item['ID']}" onclick="selectRow(this)">
											<td class="col1">${item['PROCESS'] == "ELECTROFORMING" ? "EFORM" : item['PROCESS'] == "NICKEL FLASHING" ? "NI FLASH" : item['PROCESS']}</td>
											<td class="col2">${item['TOOL_IN']}</td>
											<td class="col3">${item['DATE_IN']}</td>
											<td class="col4">${item['DATE_OUT']}</td>
											<td class="col5">${item['TANK']}</td>
											<td class="col6">${item['STATION']}</td>
											<td class="col7">${item['CYCLE_TIME']}</td>
											<td class="col8">${incomingNewForms[item['ID']]}</td>
											<td class="col9"></td>
										</tr>`;
				}
			});
		}
		
		tbody.innerHTML = html;
		
		setColors();
	}
	
	/**
	  *	@desc	determine if row matches filter constraints
	  *	@param	string keyword - string to filter by, string value - column to filter on, array row - row to match
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
				if (row['SCHEDULING_TYPE'].toUpperCase().includes(keyword)) {
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
		
		return valid;
	}
	
	/**
	  *	@desc	create/submit form to Retrieve Tool
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/retrieve.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedRow).children[1].innerHTML}" name="tool"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	archiveJob handler
	  *	@param	none
	  *	@return	none
	*/
	function archiveJobs() {
		jobs.forEach((item, index, array) => {
			var outDate = new Date(formatDate(new Date(item['DATE_OUT'])));
			var today = new Date(formatDate(new Date()));
			if (item['MODE'] == "DONE" && outDate < today) {
				archiveJob(item['ID']);
			}
		});
	}
	
	/**
	  *	@desc	grab job data to archive
	  *	@param	int id - job ID to search for
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
				var response = conn.responseText.split("Array");
				response.shift();
				if (response.length > 0) {
					for (var i=0;i<response.length;i++) {
						response[i] = response[i].split(">");
						response[i].shift();
						for (var j=0;j<response[i].length;j++) {
							if (response[i][j].includes("DateTime")) {
								response[i][j] = response[i][j+1].split("[")[0].trim();
								response[i].splice(j+1,3);
							} else {
								response[i][j] = response[i][j].split("[")[0];
								if (j==response[i].length-1) {
									response[i][j] = response[i][j].split(")")[0];
								}
								response[i][j] = response[i][j].trim();
							}
						}
					}
					
					job["BATCH_NUMBER"] = response[0][1];
					job["WO_NUMBER"] = response[0][2];
					job["PO_NUMBER"] = response[0][3];
					job["JOB_NUMBER"] = response[0][4];
					job["PROCESS"] = response[0][5];
					job["SEQNUM"] = response[0][6];
					job["TARGET_DATE"] = response[0][7];
					job["TOOL_IN"] = response[0][8].replace(/[+]/g,"%2B");
					job["DATE_IN"] = response[0][9];
					job["OPERATOR_IN"] = response[0][10];
					job["STATUS_IN"] = response[0][11];
					job["TOOL_OUT"] = response[0][12].replace(/[+]/g,"%2B");
					job["DATE_OUT"] = response[0][13];
					job["OPERATOR_OUT"] = response[0][14];
					job["STATUS_OUT"] = response[0][15];
					job["SPECIAL_INSTRUCTIONS"] = response[0][16].replace(/\n/g,"%0A").replace(/[&]/g,"%26");
					job["THICKNESS1"] = response[0][17];
					job["THICKNESS2"] = response[0][18];
					job["THICKNESS3"] = response[0][19];
					job["THICKNESS4"] = response[0][20];
					job["THICKNESS5"] = response[0][21];
					job["THICKNESS6"] = response[0][22];
					job["BRIGHTNESS1"] = response[0][23];
					job["BRIGHTNESS2"] = response[0][24];
					job["BRIGHTNESS3"] = response[0][25];
					job["COMMENT"] = response[0][26].replace(/\n/g,"%0A").replace(/[&]/g,"%26");
					job["FM_OPERATOR"] = response[0][27];
					job["FM_DATE"] = response[0][28];
					job["TANK"] = response[0][29];
					job["STATION"] = response[0][30];
					job["CYCLE_TIME"] = response[0][31];
					job["MODE"] = response[0][32];
					job["SCHEDULE_TYPE"] = response[0][33];
					job["REPEAT"] = response[0][34];
					job["PART_LENGTH"] = response[0][35];
					job["PART_WIDTH"] = response[0][36];
					job["FORMING_DENSITY"] = response[0][37];
					job["FORMING_TIME"] = response[0][38];
					job["BUILDING_DENSITY"] = response[0][39];
					job["TARGET_THICKNESS"] = response[0][40];
					job["FORMING_CURRENT"] = response[0][41];
					job["BUILDING_CURRENT"] = response[0][42];
					job["TOOL_BOW"] = response[0][43];
					job["PASSIVATED"] = response[0][44];
					job["TEMPERATURE"] = response[0][45];
					job["TIME"] = response[0][46];
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job;
	}
	
	/**
	  *	@desc	move job to archives
	  *	@param	int id - job id to move
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Electroforming_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
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
	  *	@desc	remove job from current schedule
	  *	@param	int id - job id to delete
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
					return;
				} else {
					alert("Old job not removed from current work. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	switch between Incoming Work and Current Schedule
	  *	@param	DOM Object bt - button to change label of
	  *	@return	none
	*/
	function showIncoming(bt) {
		if (!incoming) {
			incoming = true;
			bt.innerHTML = "Current Work";
		} else {
			incoming = false;
			bt.innerHTML = "Incoming Work";
		}
		
		sortBy("none");
		
		document.getElementById("edit-button").disabled = true;
		document.getElementById("abort-button").disabled = true;
		document.getElementById("retrieve-button").disabled = true;
		document.getElementById("hold-button").disabled = true;
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
		} else if (event.key == "Enter") {
			input.parentNode.nextElementSibling.click();
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
								<select id="order-type" name="order-select">
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
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
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
							<button onclick="sortBy(document.getElementById('order-type').value)">Go</button>
						</div>`;
		document.getElementsByClassName("container")[0].appendChild(div);
		var arrow = document.getElementById("arrow");
		div.after(arrow);
		arrow.children[0].classList.remove("right-arrow");
		arrow.children[0].classList.add("left-arrow");
		arrow.setAttribute("onclick",'hideFilters()');
		
		setCookie("sort_expanded","true");
		
		if (checkCookie("sort_electroforming_scheduling_order")) {
			document.getElementById("order-type").value = getCookie("sort_electroforming_scheduling_order");
		}
		
		if (checkCookie("sort_electroforming_scheduling_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_electroforming_scheduling_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_electroforming_scheduling_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_electroforming_scheduling_filter_value");
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
						input.parentNode.nextElementSibling.click();
					}
				}
			}
			document.getElementById("filter-container").appendChild(input);
		}
	}
	
	/**
	  *	@desc	place job on hold
	  *	@param	none
	  *	@return	none
	*/
	function placeOnHold(label) {
		var conn = new XMLHttpRequest();
		var table = incoming ? "Electroforming_Queue" : "Electroforming";
		var action = "update";
		var value;
		
		if (label == "Place On Hold") {
			value = "TRUE";
		} else {
			value = "FALSE";
		}
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText.includes("Data updated")) {
					document.getElementById("hold-button").innerHTML = value == "TRUE" ? "Take Off Hold" : "Place On Hold";
					if (incoming) {
						for (var i=0;i<incomingJobs.length;i++) {
							if (incomingJobs[i]['ID'] == selectedRow) {
								incomingJobs[i]['ON_HOLD'] = value;
							}
						}
					} else {
						for (var i=0;i<jobs.length;i++) {
							if (jobs[i]['ID'] == selectedRow) {
								jobs[i]['ON_HOLD'] = value;
							}
						}
					}
					setColors();
					document.getElementById(selectedRow).click();
				} else {
					alert("Could not update job. Contact IT support to correct.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&ON_HOLD="+value+"&condition=id&value="+selectedRow,false);
		conn.send();
	}
</script>
<html>
	<head>
		<title>Electroforming Scheduling</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/electroforming.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="setColors(); checkSortBox(); archiveJobs();">
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
									<th class="col8">Form</th>
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
						<span id="po-span">PO #<input id="po-input" type="text" readonly></span>
						<span id="job-span">Job #<input id="job-input" type="text" readonly></span>
						<span id="wo-span">WO #<input id="wo-input" type="text" readonly></span><br>
						<span id="schedule-span">Schedule Type<input id="schedule-input" type="text" readonly></span>
						<span id="operator-in-span">Operator In<input id="operator-in-input" type="text" readonly></span>
						<span id="operator-out-span">Out<input id="operator-out-input" type="text" readonly></span><br>
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
							<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
							<button id="retrieve-button" onclick="retrieveTool()" disabled>Retrieve Tool</button>
							<a href="../scheduling.php">Back</a>
						</div>
						<div class="controls-right">
							<button onclick="popProcessList()">Add</button>
							<button id="edit-button" onclick="editJob()" disabled>Edit</button>
							<button id="abort-button" onclick="abortJob()" disabled>Abort</button>
							<button id="hold-button" onclick="placeOnHold(this.innerHTML)" disabled>Place On Hold</button>
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