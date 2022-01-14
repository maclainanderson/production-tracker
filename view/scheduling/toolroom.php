<!DOCTYPE html>
<?php
/**
  * @desc toolroom main scheduling page
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
	
	//list of jobs, toolroom processes, and reasons to abort
	$processes = array();
	$jobs = array();
	$incomingJobs = array();
	$reasons = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE DEPARTMENT = 'TOOLRM' ORDER BY PROCESS");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TARGET_DATE, TOOL_IN, DATE_IN, OPERATOR_IN, STATUS_IN, TOOL_OUT, OPERATOR_OUT, STATUS_OUT, DATE_OUT, ON_HOLD FROM Toolroom;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		foreach($jobs as $id => $job) {
			$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$jobs[$id]['MANDREL_LOCATION'] = $row['LOCATION'];
					$jobs[$id]['MANDREL_DRAWER'] = $row['DRAWER'];
				}
			}
			
			if ($job[11] != "") {
				$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_OUT'] . "';");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$jobs[$id]['TOOL_LOCATION'] = $row['LOCATION'];
						$jobs[$id]['TOOL_DRAWER'] = $row['DRAWER'];
					}
				}
			} else {
				$jobs[$id]['TOOL_LOCATION'] = "";
				$jobs[$id]['TOOL_DRAWER'] = "";
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TARGET_DATE, TOOL_IN, DATE_IN, OPERATOR_IN, STATUS_IN, TOOL_OUT, OPERATOR_OUT, STATUS_OUT, DATE_OUT, ON_HOLD FROM Toolroom_Queue;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$incomingJobs[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		foreach($incomingJobs as $id => $job) {
			$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$incomingJobs[$id]['MANDREL_LOCATION'] = $row['LOCATION'];
					$incomingJobs[$id]['MANDREL_DRAWER'] = $row['DRAWER'];
				}
			}
			
			if ($job[11] != "") {
				$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_OUT'] . "';");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$incomingJobs[$id]['TOOL_LOCATION'] = $row['LOCATION'];
						$incomingJobs[$id]['TOOL_DRAWER'] = $row['DRAWER'];
					}
				}
			} else {
				$incomingJobs[$id]['TOOL_LOCATION'] = "";
				$incomingJobs[$id]['TOOL_DRAWER'] = "";
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, REASON FROM Abort_Work_Order WHERE STATUS = 'Active';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$reasons[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
	} else {
		var_dump(sqlsrv_errors());
	}
	
	/**
	  *	@desc	tests to see if parquet job's batch is already present
	  *	@param	Array $j - job to be searched for
	  *	@return	true if found, false otherwise
	*/
	function batchAlreadyPresent($j) {
		foreach($GLOBALS['jobs'] as $job) {
			if ($job['BATCH_NUMBER'] == $j['BATCH_NUMBER']) {
				if (array_search($job, $GLOBALS['jobs']) < array_search($j, $GLOBALS['jobs'])) {
					return true;
				} else {
					return false;
				}
			}
		}
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
	var incoming = false;
	var processes = [<?php
		foreach($processes as $process) {
			echo '{';
			foreach($process as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,"m/d/y");
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
					echo date_format($value,"m/d/y");
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
					echo date_format($value,"m/d/y");
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
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	
	//up/down arrow keys move table up/down
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
		
		getDetails(tr.id);
	}
	
	/**
	  *	@desc	display details from the selected job
	  *	@param	int id - row id from selected job
	  *	@return	none
	*/
	function getDetails(id) {
		if (!incoming) {
			for (var i=0;i<jobs.length;i++) {
				if (jobs[i]['ID'] == id) {
					document.getElementById("tool-input").value = jobs[i]['TOOL_IN'];
					document.getElementById("batch-input").value = jobs[i]['BATCH_NUMBER'];
					document.getElementById("job-input").value = jobs[i]['JOB_NUMBER'];
					document.getElementById("po-input").value = jobs[i]['PO_NUMBER'];
					document.getElementById("wo-input").value = jobs[i]['WO_NUMBER'];
					document.getElementById("operator-in-input").value = jobs[i]['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = jobs[i]['OPERATOR_OUT'];
					document.getElementById("location-input").value = jobs[i]['MANDREL_LOCATION'];
					document.getElementById("drawer-input").value = jobs[i]['MANDREL_DRAWER'];
					document.getElementById("status-input").value = jobs[i]['STATUS_IN'];
					document.getElementById("new-tool-input").value = jobs[i]['TOOL_OUT'];
					document.getElementById("new-location-input").value = jobs[i]['TOOL_LOCATION'];
					document.getElementById("new-drawer-input").value = jobs[i]['TOOL_DRAWER'];
					document.getElementById("new-status-input").value = jobs[i]['STATUS_OUT'];
					if (jobs[i]['OPERATOR_IN'] == "") {
						document.getElementById("hold-button").disabled = false;
						if (jobs[i]['ON_HOLD'] == "TRUE") {
							document.getElementById("hold-button").innerHTML = "Take Off Hold";
						} else {
							document.getElementById("hold-button").innerHTML = "Place On Hold";
						}
					} else {
						document.getElementById("hold-button").disabled = true;
					}
					break;
				}
			}
		} else {
			for (var i=0;i<incomingJobs.length;i++) {
				if (incomingJobs[i]['ID'] == id) {
					document.getElementById("tool-input").value = incomingJobs[i]['TOOL_IN'];
					document.getElementById("batch-input").value = incomingJobs[i]['BATCH_NUMBER'];
					document.getElementById("job-input").value = incomingJobs[i]['JOB_NUMBER'];
					document.getElementById("po-input").value = incomingJobs[i]['PO_NUMBER'];
					document.getElementById("wo-input").value = incomingJobs[i]['WO_NUMBER'];
					document.getElementById("operator-in-input").value = incomingJobs[i]['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = incomingJobs[i]['OPERATOR_OUT'];
					document.getElementById("location-input").value = jobs[i]['MANDREL_LOCATION'];
					document.getElementById("drawer-input").value = jobs[i]['MANDREL_DRAWER'];
					document.getElementById("status-input").value = incomingJobs[i]['STATUS_IN'];
					document.getElementById("new-tool-input").value = incomingJobs[i]['TOOL_OUT'];
					document.getElementById("new-location-input").value = incomingJobs[i]['TOOL_LOCATION'];
					document.getElementById("new-drawer-input").value = incomingJobs[i]['TOOL_DRAWER'];
					document.getElementById("new-status-input").value = incomingJobs[i]['STATUS_OUT'];
					if (incomingJobs[i]['OPERATOR_IN'] == "") {
						document.getElementById("hold-button").disabled = false;
						if (incomingJobs[i]['ON_HOLD'] == "TRUE") {
							document.getElementById("hold-button").innerHTML = "Take Off Hold";
						} else {
							document.getElementById("hold-button").innerHTML = "Place On Hold";
						}
					} else {
						document.getElementById("hold-button").disabled = true;
					}
					break;
				}
			}
		}
		document.getElementById("edit-button").disabled = false;
		document.getElementById("abort-button").disabled = false;
		document.getElementById("retrieve-button").disabled = false;
		document.getElementById("hold-button").disabled = false;
	}
	
	/**
	  *	@desc	create list of processes to choose from
	  *	@param	none
	  *	@return	none
	*/
	function popProcessList() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		modalContent.style.width = "250px";
		
		var html = `<span class="close" id="close">&times;</span>`
		processes.forEach((item, index, array) => {
			if (index%2 == 0) {
				html += `<span id="${item['ID']}" style="display: inline-block; background-color: #ddd; width: 100%; margin-bottom: 3px;" onclick="selectProcessRow(this)">${item['PROCESS']}</span><br>`
			} else {
				html += `<span id="${item['ID']}" style="display: inline-block; background-color: #fff; width: 100%; margin-bottom: 3px;" onclick="selectProcessRow(this)">${item['PROCESS']}</span><br>`
			}
		});
		
		modalContent.innerHTML = html;
		
		closeForm();		
	}
	
	/**
	  *	@desc	highlight selected process row, unhighlight others
	  *	@param	DOM Object span - selected row
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
	  *	@desc	submit to new job page
	  *	@param	DOM Object span - process name clicked on
	  *	@return	none
	*/
	function confirmProcess(span) {
		var body = document.getElementsByTagName("BODY")[0];
		body.innerHTML += `<form style="display: none;" action="addtoolroom.php" method="post" id="process-form"><input name="process" type="text" value="${span.innerHTML}"></form>`;
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
	  *	@desc	go to edit job page with relevant data
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		if (document.getElementById("operator-in-input").value == "" && document.getElementById("operator-out-input").value == "" && selectedRow != 0) {
			var id = selectedRow;
			var woNumber = document.getElementById("wo-input").value;
			var process = document.getElementById(id).children[2].innerHTML;
			var batch = document.getElementById("batch-input").value;
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="edittoolroom.php" method="POST" style="display: none;" id="edit-form"><input type="text" name="id" value="${id}"><input type="text" name="woNumber" value="${woNumber}"><input type="text" name="batch" value="${batch}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
			document.getElementById("edit-form").submit();
		} else if (selectedRow == 0) {
			alert("Select a job first");
		} else {
			alert("Job cannot be edited once started");
		}
	}
	
	/**
	  *	@desc	put job into abort history
	  *	@param	none
	  *	@return	none
	*/
	function abort() {
		var successCounter = 0;
		var id = selectedRow;
		if (id != 0) {
			if (document.getElementById(id).children[2].innerHTML != "PARQUET") {
				var abortJob = {
					BATCH_NUMBER: document.getElementById("batch-input").value,
					TOOL: document.getElementById(id).children[1].innerHTML,
					PROCESS: document.getElementById(id).children[2].innerHTML,
					DEPARTMENT: "TOOLRM",
					REASON: "",
					WO_NUMBER: document.getElementById("wo-input").value,
					OPERATOR: "",
					DATE: formatDate(new Date())
				}
				
				document.getElementById("modal").style.display = "block";
				var modalContent = document.getElementById("modal-content");
				modalContent.innerHTML = `<span id="close">&times;</span><span>Reason for aborting job:</span><br>
										  <select id="reason-select">
										  <?php foreach($reasons as $reason) { ?>
										  <option value="<?=$reason['REASON']?>"><?=$reason['REASON']?></option>
										  <?php } ?>
										  </select><br>
										  <input onblur="this.value = this.value.toUpperCase();" type="text" id="abort-operator-input" <?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?> value="<?=$_SESSION['initials']?>" <?php } ?>>
										  <button id="submit-abort">Submit</button>`;
				
				closeForm();
										  
				document.getElementById("submit-abort").addEventListener('click', function() {
					var conn = new XMLHttpRequest();
					var table = "Abort_History";
					var action = "insert";
					abortJob.REASON = document.getElementById("reason-select").value;
					abortJob.OPERATOR = document.getElementById("abort-operator-input").value;
					
					conn.onreadystatechange = function() {
						if (conn.readyState == 4 && conn.status == 200) {
							if (conn.responseText.includes("Insert succeeded")) {
								if (deleteTool(id)) {
									deleteJob();
								} else {
									alert("Job aborted, but tool names still reserved. Contact IT Support to correct. " + conn.responseText);
								}
							} else {
								alert("Row not added to abort history");
							}
						}
					}
					
					var query = "";
					
					Object.keys(abortJob).forEach((item, index, array) => {
						query += `&${item}=${abortJob[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					});
				
					conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
					conn.send();
				});
			} else {
				var conn = [];
				var abortJob = {
					BATCH_NUMBER: document.getElementById("batch-input").value,
					TOOL: document.getElementById(id).children[1].innerHTML,
					PROCESS: document.getElementById(id).children[2].innerHTML,
					DEPARTMENT: "TOOLRM",
					REASON: "",
					OPERATOR: "",
					DATE: formatDate(new Date())
				}
				
				document.getElementById("modal").style.display = "block";
						var modalContent = document.getElementById("modal-content");
						modalContent.innerHTML = `<span id="close">&times;</span><span>Reason for aborting job:</span><br>
												  <select id="reason-select">
												  <?php foreach($reasons as $reason) { ?>
												  <option value="<?=$reason['REASON']?>"><?=$reason['REASON']?></option>
												  <?php } ?>
												  </select><br>
												  <input onblur="this.value = this.value.toUpperCase();" type="text" id="abort-operator-input" <?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?> value="<?=$_SESSION['initials']?>" <?php } ?>>
												  <button id="submit-abort">Submit</button>`;
				
				closeForm();
							  
				document.getElementById("submit-abort").addEventListener('click', function() {
					jobs.forEach((item, index, array) => {
						if (item[1] == document.getElementById('batch-input').value) {
							abortJob.WO_NUMBER = item[2];
							conn[conn.length] = new XMLHttpRequest();
							var table = "Abort_History";
							var action = "insert";
							abortJob.REASON = document.getElementById("reason-select").value;
							abortJob.OPERATOR = document.getElementById("abort-operator-input").value;
							abortJob.TOOL = item[7];
							conn[conn.length-1].onreadystatechange = xmlresponse(conn.length-1);
							
							var query = "";
							
							Object.keys(abortJob).forEach((item, index, array) => {
								query += `&${item}=${abortJob[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							});
						
							conn[conn.length-1].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
							conn[conn.length-1].send();
						}
					});
				});
			}
		} else {
			alert("Select a job first");
		}
		
		function xmlresponse(i) {
			return function() {
				if (conn[i].readyState == 4 && conn[i].status == 200) {
					if (conn[i].responseText.includes("Insert succeeded")) {
						successCounter++;
						if (checkSuccess(successCounter)) {
							if (deleteTool(id)) {
								deleteJob();
							} else {
								alert("Job aborted, but tool names still reserved. Contact IT Support to correct. " + conn.responseText);
							}
						}
					} else {
						alert("Batch created, but job entry failed. Contact support to correct. " + conn.responseText);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	determine if all parquet jobs are removed for batch
	  *	@param	int counter - total tools removed so far
	  *	@return	none
	*/
	function checkSuccess(counter) {
		var toolsCount = 0;
		
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (item['BATCH_NUMBER'] == document.getElementById('batch-input').value) {
					toolsCount++;
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (item['BATCH_NUMBER'] == document.getElementById('batch-input').value) {
					toolsCount++;
				}
			});
		}
		
		if (counter == toolsCount) {
			return true;
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	remove reserved tools from tool tree
	  *	@param	int id - array index for tool
	  *	@return	none
	*/
	function deleteTool(id) {
		var success = false;
		
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (item['ID'] == id) {
					if ((item['PROCESS'] == "PARQUET" || item['PROCESS'] == "CONVERT" || item['PROCESS'] == "FRAMING") && !hasChildren(item['TOOL_OUT'])) {
						var conn = new XMLHttpRequest();
						var action = "delete";
						var table = "Tool_Tree";
						var query = "&TOOL="+item['TOOL_OUT'].replace(/[+]/g, "%2B");
						
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
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (item['ID'] == id) {
					if ((item['PROCESS'] == "PARQUET" || item['PROCESS'] == "CONVERT" || item['PROCESS'] == "FRAMING") && !hasChildren(item['TOOL_OUT'])) {
						var conn = new XMLHttpRequest();
						var action = "delete";
						var table = "Tool_Tree";
						var query = "&TOOL="+item['TOOL_OUT'].replace(/[+]/g, "%2B");
						
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
				}
			});
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
	  *	@desc	remove aborted jobs from current schedule
	  *	@param	none
	  *	@return	none
	*/
	function deleteJob() {
		var successCounter = 0;
		if (document.getElementById(selectedRow).children[2].innerHTML != "PARQUET") {
			var conn = new XMLHttpRequest();
			var table = "Toolroom";
			var action = "delete";
			var id = selectedRow;
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Deletion succeeded")) {
						if (!incoming) {
							addNextJob();
						} else {
							alert("Job deleted");
							window.location.replace("toolroom.php");
							
						}
					} else {
						alert("Job deletion failed");
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&id="+id, false);
			conn.send();
		} else {
			var conn = [];
			if (!incoming) {
				jobs.forEach((item, index, array) => {
					if (item['BATCH_NUMBER'] == document.getElementById('batch-input').value) {
						conn[conn.length] = new XMLHttpRequest();
						var table = "Toolroom";
						var action = "delete";
						var query = "&id="+item['ID'];
							
						conn[conn.length-1].onreadystatechange = xmlresponseDelete(conn.length-1);
					
						conn[conn.length-1].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
						conn[conn.length-1].send();
					}
				});
			} else {
				incomingJobs.forEach((item, index, array) => {
					if (item['BATCH_NUMBER'] == document.getElementById('batch-input').value) {
						conn[conn.length] = new XMLHttpRequest();
						var table = "Toolroom_Queue";
						var action = "delete";
						var query = "&id="+item['ID'];
							
						conn[conn.length-1].onreadystatechange = xmlresponseDelete(conn.length-1);
					
						conn[conn.length-1].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
						conn[conn.length-1].send();
					}
				});
			}
		}
		
		function xmlresponseDelete(i) {
			return function() {
				if (conn[i].readyState == 4 && conn[i].status == 200) {
					if (conn[i].responseText.includes("Deletion succeeded")) {
						successCounter++;
						if (checkSuccess(successCounter)) {
							if (!incoming) {
								addNextJob();
							} else {
								alert("Job aborted");
								window.location.replace("toolroom.php");
							}
						}
					} else {
						alert("Job not deleted. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	move next job in batch to current work, if it exists
	  *	@param	none
	  *	@return	none
	*/
	function addNextJob() {
		var next = findNextJob();
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
			alert("Job aborted");
			window.location.replace("toolroom.php");
		}
	}
	
	/**
	  *	@desc	grab next job in batch, if applicable
	  *	@param	none
	  *	@return	array containing job details
	*/
	function findNextJob() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var condition = "BATCH_NUMBER";
		var value = document.getElementById("batch-input").value;
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
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&condition="+condition+"&value="+value,false);
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
	  *	@desc	remove next job from queue, after it is moved to current schedule
	  *	@param	int woNumber - identifying number for job, string table - db table the job is found in
	  *	@return	none
	*/
	function removeNextFromQueue(woNumber, table) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = table + "_Queue";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Job aborted");
					window.location.replace("toolroom.php");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WO_NUMBER="+woNumber,false);
		conn.send();
	}
	
	/**
	  *	@desc	convert date object to string
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
	  *	@desc	sets color of row according to job status
	  *	@param	DOM Object tr - row to change, int index - array index of job
	  *	@return	none
	*/
	function setColor(tr, index) {
		if (!incoming) {
			var d = new Date();
			var d2 = new Date(jobs[index]['DATE_OUT']);
			
			if (jobs[index]['ON_HOLD'] == "TRUE") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#e0e";
			} else {
				//Green/Black - not started
				if (jobs[index]['OPERATOR_IN'] == "") {
					tr.style.color = "black";
					tr.style.backgroundColor = "#0f0";
				} else if (jobs[index]['OPERATOR_OUT'] == "") {
					//Red/Black - finished
					if (d > d2) {
						tr.style.color = "black";
						tr.style.backgroundColor = "#f00";
					//Yellow/Black - in progress
					} else {
						tr.style.color = "black";
						tr.style.backgroundColor = "#ff0";
					}
				}
			}
		} else {
			var d = new Date();
			var d2 = new Date(incomingJobs[index]['DATE_OUT']);
			
			if (incomingJobs[index]['ON_HOLD'] == "TRUE") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#e0e";
			} else {
				//Green/Black - not started
				if (incomingJobs[index]['OPERATOR_IN'] == "") {
					tr.style.color = "black";
					tr.style.backgroundColor = "#0f0";
				} else if (incomingJobs[index]['OPERATOR_OUT'] == "") {
					//Red/Black - finished
					if (d > d2) {
						tr.style.color = "black";
						tr.style.backgroundColor = "#f00";
					//Yellow/Black - in progress
					} else {
						tr.style.color = "black";
						tr.style.backgroundColor = "#ff0";
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	sort job list by given column
	  *	@param	string value - column to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_toolroom_scheduling_order",document.getElementById("order-type").value);
		setCookie("sort_toolroom_scheduling_filter",document.getElementById("filter-type").value);
		setCookie("sort_toolroom_scheduling_filter_value",document.getElementById("filter-input").value);
		
		if (!incoming) {
			jobs.sort(function(a, b) {
				var index;
				var ad = new Date(a['DATE_OUT']);
				var bd = new Date(b['DATE_OUT']);
				var date = new Date();
				
				switch(value) {
					case "tool":
						if (a['TOOL_IN'] < b['TOOL_IN']) {
							return -1;
						} else if (a['TOOL_IN'] > b['TOOL_IN']) {
							return 1;
						} else {
							return 0;
						}
						break;
					case "targetdate":
						var d = new Date(a[name]);
						var d2 = new Date(b[name]);
						if (d < d2) {
							return -1;
						} else if (d > d2) {
							return 1;
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
							//"black"
							//"#0f0"
						} else if (a['OPERATOR_OUT'] == "") {
							if (b['OPERATOR_OUT'] == "") {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else {
									if (date > ad) {
										if (date > bd) {
											return 0;
										} else {
											return 1;
										}
										//Red/Black - finished
										//"black"
										//"#f00"
									} else {
										if (date > bd) {
											return -1;
										} else {
											return 0;
										}
										//Yellow/Black - in progress
										//"black"
										//"#ff0"
									}
								}
							} else {
								return -1;
							}
						} else {
							if (b['OPERATOR_OUT'] == "") {
								return 1;
							} else {
								return 0;
							}
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
					default:
						return 0;
				}
			});
		} else {
			incomingJobs.sort(function(a, b) {
				var index;
				var ad = new Date(a['DATE_OUT']);
				var bd = new Date(b['DATE_OUT']);
				var date = new Date();
				
				switch(value) {
					case "tool":
						if (a['TOOL_IN'] < b['TOOL_IN']) {
							return -1;
						} else if (a['TOOL_IN'] > b['TOOL_IN']) {
							return 1;
						} else {
							return 0;
						}
						break;
					case "targetdate":
						var d = new Date(a['TARGET_DATE']);
						var d2 = new Date(b['TARGET_DATE']);
						if (d < d2) {
							return -1;
						} else if (d > d2) {
							return 1;
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
							//"black"
							//"#0f0"
						} else if (a['OPERATOR_OUT'] == "") {
							if (b['OPERATOR_OUT'] == "") {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else {
									if (date > ad) {
										if (date > bd) {
											return 0;
										} else {
											return 1;
										}
										//Red/Black - finished
										//"black"
										//"#f00"
									} else {
										if (date > bd) {
											return -1;
										} else {
											return 0;
										}
										//Yellow/Black - in progress
										//"black"
										//"#ff0"
									}
								}
							} else {
								return -1;
							}
						} else {
							if (b['OPERATOR_OUT'] == "") {
								return 1;
							} else {
								return 0;
							}
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
					default:
						return 0;
				}
			});
		}
		
		fillSort();
	}
	
	/**
	  *	@desc	fill table with newly sorted job array
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
											<td class="col1">${item['TARGET_DATE']}</td>
											<td class="col2">${item['TOOL_IN']}</td>
											<td class="col3">${item['PROCESS']}</td>
											<td class="col4">${item['DATE_IN']}</td>
										</tr>`;
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (isAllowed(keyword, value, item)) {
					html += `<tr id="${item['ID']}" onclick="selectRow(this)">
											<td class="col1">${item['TARGET_DATE']}</td>
											<td class="col2">${item['TOOL_IN']}</td>
											<td class="col3">${item['PROCESS']}</td>
											<td class="col4">${item['DATE_IN']}</td>
										</tr>`;
				}
			});
		}
		
		tbody.innerHTML = html;
		
		setColors();
	}
	
	/**
	  *	@desc	determine if row matches filter constraints
	  *	@param	string keyword - string to match data to, string value - column to sort by, array row - row to match
	  *	@return	true if match, false otherwise
	*/
	function isAllowed(keyword, value, row) {
		var valid = false;
		
		switch(value) {
			case "linecolor":
				var d = new Date();
				var d2 = new Date(row['DATE_OUT']);
				var color;
				
				//Green/Black - not started
				if (row['OPERATOR_IN'] == "") {
					color = "green";
				} else if (row['OPERATOR_OUT'] == "") {
					//Red/Black - finished
					if (d > d2) {
						color = "red";
					//Yellow/Black - in progress
					} else {
						color = "yellow";
					}
				}
				
				if (color == keyword) {
					valid = true;
				}
				break;
			case "targetdate":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(row['TARGET_DATE'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['TARGET_DATE'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "process":
				if (row['PROCESS'].toUpperCase() == keyword) {
					valid = true;
				}
				break;
			case "tool":
				if (row['TOOL_IN'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			default:
				valid = true;
		}
		
		return valid;
	}
	
	/**
	  *	@desc	go to Retrieve Tool page, request selected tool
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/retrieve.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedRow).children[1].innerHTML}" name="tool"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	change from current schedule to incoming work and vice versa
	  *	@param	DOM Object bt - button clicked on to change label
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
	  *	@param	DOM Object input - date field to be formatted
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
	  *	@desc	archiveJob handler
	  *	@param	none
	  *	@return	none
	*/
	function archiveJobs() {
		for (var i=0;i<jobs.length;i++) {
			var outDate = new Date(formatDate(new Date(jobs[i]['DATE_OUT'])));
			var today = new Date(formatDate(new Date()));
			if (jobs[i]['OPERATOR_IN'] != "" && jobs[i]['OPERATOR_OUT'] != '' && outDate < today) {
				archiveJob(jobs[i]['ID']);
			}
		}
	}
	
	/**
	  *	@desc	fetch details on given job
	  *	@param	int id - Primary Key of job in db table
	  *	@return	array containing job details
	*/
	function getJobToDelete(id) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Toolroom";
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
					job["JOB_NUMBER"] = response[0][3];
					job["PO_NUMBER"] = response[0][4];
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
					job["MACHINE_NUMBER"] = response[0][23];
					job["PROGRAM_NUMBER"] = response[0][24];
					job["CUSTOMER_TOOL_TYPE"] = response[0][25];
					job["BOW"] = response[0][26];
					job["OPPOSITE"] = response[0][27];
					job["BOW_AFTER_MACHINING"] = response[0][28];
					job["OPPOSITE_AFTER_MACHINING"] = response[0][29];
					job["BOW_AFTER_CUTOUT"] = response[0][30];
					job["OPPOSITE_AFTER_CUTOUT"] = response[0][31];
					job["BOW_AFTER_LAP"] = response[0][32];
					job["OPPOSITE_AFTER_LAP"] = response[0][33];
					job["COMMENT"] = response[0][34].replace(/\n/g,"%0A").replace(/[&]/g,"%26");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job;
	}
	
	/**
	  *	@desc	move job into archives
	  *	@param	int id - db table id for fetching job
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Toolroom_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		})
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded.")) {
					removeFromCurrentWork(id);
				} else {
					alert("Job not archived. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	remove job from current schedule
	  *	@param	int id - id of job to remove
	  *	@return	none
	*/
	function removeFromCurrentWork(id) {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "delete";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded.")) {
					return;
				} else {
					alert("Old job not removed from current work. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&id="+id, true);
		conn.send();
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
									<option value="tool">Tool</option>
									<option value="linecolor">Line Color</option>
									<option value="targetdate">Target Date</option>
									<option value="process">Process</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none">&lt;NONE&gt;</option>
									<option value="tool">Tool</option>
									<option value="linecolor">Line Color</option>
									<option value="targetdate">Target Date</option>
									<option value="process">Process</option>
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
		
		if (checkCookie("sort_toolroom_scheduling_order")) {
			document.getElementById("order-type").value = getCookie("sort_toolroom_scheduling_order");
		}
		
		if (checkCookie("sort_toolroom_scheduling_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_toolroom_scheduling_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_toolroom_scheduling_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_toolroom_scheduling_filter_value");
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
		if (select.value == "linecolor") {
			var select = document.createElement('select');
			select.id = "filter-input";
			select.innerHTML = `<option value="green">Black on Green</option><option value="yellow">Black on Yellow</option><option value="red">Black on Red</option>`;
			document.getElementById("filter-container").appendChild(select);
		} else if (select.value == "process") {
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
			if (select.value == "targetdate") {
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
		var table = incoming ? "Toolroom_Queue" : "Toolroom";
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
		<title>Tool Room Scheduling</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/toolroom.css">
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
									<th class="col1">Target</th>
									<th class="col2">Tool</th>
									<th class="col3">Process</th>
									<th class="col4">Date In</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($jobs as $job) { 
								if ($job['PROCESS'] != "PARQUET" || !batchAlreadyPresent($job)) { ?>
								<tr id="<?=$job['ID']?>" onclick="selectRow(this)">
									<td class="col1"><?=date_format($job['TARGET_DATE'],'m/d/y')?></td>
									<td class="col2"><?=$job['TOOL_IN']?></td>
									<td class="col3"><?=$job['PROCESS']?></td>
									<td class="col4"><?=date_format($job['DATE_IN'],'m/d/y')?></td>
								</tr>
							<?php }
							} ?>
							</tbody>
						</table>
						<input type="text" id="tool-input">
					</div>
					<div class="left">
						<span id="batch-span">Batch<input id="batch-input" type="text" readonly></span>
						<span id="job-span">Job #<input id="job-input" type="text" readonly></span>
						<span id="po-span">PO #<input id="po-input" type="text" readonly></span>
						<span id="wo-span">WO #<input id="wo-input" type="text" readonly></span><br>
						<span id="operator-in-span">Operator In<input id="operator-in-input" type="text" readonly></span>
						<span id="operator-out-span">Operator Out<input id="operator-out-input" type="text" readonly></span><br>
						<span id="loc-span">Tool Location<input id="location-input" type="text" readonly></span>
						<span id="drawer-span">Drawer<input id="drawer-input" type="text" readonly></span>
						<span id="status-span">Status<input id="status-input" type="text" readonly></span><br>
						<span id="newname-span">Renamed Tool<input id="new-tool-input" type="text" readonly></span><br>
						<span id="newloc-span">Renamed Location<input id="new-location-input" type="text" readonly></span>
						<span id="newdrawer-span">Drawer<input id="new-drawer-input" type="text" readonly></span>
						<span id="newstatus-span">Status<input id="new-status-input" type="text" readonly></span>
					</div>
					<div class="controls">
						<button onclick="popProcessList()">Add</button>
						<button id="edit-button" onclick="edit()" disabled>Edit</button>
						<button id="abort-button" onclick="abort()" disabled>Abort</button>
						<button id="hold-button" onclick="placeOnHold(this.innerHTML)" disabled>Place On Hold</button>
					</div>
					<div class="controls">
						<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
						<button id="retrieve-button" onclick="retrieveTool()" disabled>Retrieve Tool</button>
						<a href="../scheduling.php">Back</a>
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