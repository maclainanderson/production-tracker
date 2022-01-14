<!DOCTYPE html>
<?php
/**
  *	@desc main list of toolroom jobs in daily operations
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of jobs and processes
	$processes = array();
	$jobs = array();
	$incomingJobs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE DEPARTMENT = 'TOOLRM' ORDER BY PROCESS;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TARGET_DATE, TOOL_IN, DATE_IN, OPERATOR_IN, STATUS_IN, TOOL_OUT, OPERATOR_OUT, STATUS_OUT, DATE_OUT FROM Toolroom WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
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
			
			if ($job['TOOL_OUT'] != "") {
				$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_OUT'] . "';");
				if ($result) {
					$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
					if ($row) {
						$jobs[$id]['TOOL_LOCATION'] = $row['LOCATION'];
						$jobs[$id]['TOOL_DRAWER'] = $row['DRAWER'];
					} else {
						$jobs[$id]['TOOL_LOCATION'] = "";
						$jobs[$id]['TOOL_DRAWER'] = "";
					}
				}
			} else {
				$jobs[$id]['TOOL_LOCATION'] = "";
				$jobs[$id]['TOOL_DRAWER'] = "";
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, WO_NUMBER, JOB_NUMBER, PO_NUMBER, PROCESS, TARGET_DATE, TOOL_IN, DATE_IN, OPERATOR_IN, STATUS_IN, TOOL_OUT, OPERATOR_OUT, STATUS_OUT, DATE_OUT FROM Toolroom_Queue WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
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
			
			if ($job['TOOL_OUT'] != "") {
				$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_OUT'] . "';");
				if ($result) {
					$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
					if ($row) {
						$incomingJobs[$id]['TOOL_LOCATION'] = $row['LOCATION'];
						$incomingJobs[$id]['TOOL_DRAWER'] = $row['DRAWER'];
					} else {
						$incomingJobs[$id]['TOOL_LOCATION'] = "";
						$incomingJobs[$id]['TOOL_DRAWER'] = "";
					}
				}
			} else {
				$incomingJobs[$id]['TOOL_LOCATION'] = "";
				$incomingJobs[$id]['TOOL_DRAWER'] = "";
			}
		}
	} else {
		var_dump(sqlsrv_errors());
	}
	
	/**
	  *	@desc	determine if parquet batch already exists, to lump them all as one row
	  *	@param	array $j - tested job
	  *	@return	true if batch found, false otherwise
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
					echo $value;
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
	
	var incomingJobs = [<?php
		foreach($incomingJobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
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
	  *	@desc	highlight job row
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
	  *	@desc	insert job details
	  *	@param	int id - job ID to search for
	  *	@return	none
	*/
	function getDetails(id) {
		if (!incoming) {
			for (var i=0;i<jobs.length;i++) {
				if (jobs[i]['ID'] == id) {
					document.getElementById("tool-input").value = jobs[i]['TOOL_IN'];
					document.getElementById("job-input").value = jobs[i]['JOB_NUMBER'];
					document.getElementById("po-input").value = jobs[i]['PO_NUMBER'];
					document.getElementById("wo-input").value = jobs[i]['WO_NUMBER'];
					document.getElementById("batch-input").value = jobs[i]['BATCH_NUMBER'];
					document.getElementById("operator-in-input").value = jobs[i]['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = jobs[i]['OPERATOR_OUT'];
					document.getElementById("location-input").value = jobs[i]['MANDREL_LOCATION'];
					document.getElementById("drawer-input").value = jobs[i]['MANDREL_DRAWER'];
					document.getElementById("status-input").value = jobs[i]['STATUS_IN'];
					document.getElementById("new-tool-input").value = jobs[i]['TOOL_OUT'];
					document.getElementById("new-location-input").value = jobs[i]['TOOL_LOCATION'];
					document.getElementById("new-drawer-input").value = jobs[i]['TOOL_DRAWER'];
					document.getElementById("new-status-input").value = jobs[i]['STATUS_OUT'];
					
					if (jobs[i]['OPERATOR_IN'] != "") {
						if (jobs[i]['OPERATOR_OUT'] != "") {
							document.getElementById("in-button").disabled = false;
							document.getElementById("in-button").innerHTML = "Details In";
							document.getElementById("out-button").disabled = false;
							document.getElementById("out-button").innerHTML = "Details Out";
						} else {
							document.getElementById("in-button").disabled = false;
							document.getElementById("in-button").innerHTML = "Details In";
							document.getElementById("out-button").disabled = false;
							document.getElementById("out-button").innerHTML = "Process Out";
						}
					} else {
						document.getElementById("in-button").disabled = false;
						document.getElementById("in-button").innerHTML = "Process In";
						document.getElementById("out-button").disabled = true;
						document.getElementById("out-button").innerHTML = "Process Out";
					}
					
					break;
				}
			}
		} else {
			for (var i=0;i<incomingJobs.length;i++) {
				if (incomingJobs[i]['ID'] == id) {
					document.getElementById("tool-input").value = incomingJobs[i]['TOOL_IN'];
					document.getElementById("job-input").value = incomingJobs[i]['JOB_NUMBER'];
					document.getElementById("po-input").value = incomingJobs[i]['PO_NUMBER'];
					document.getElementById("wo-input").value = incomingJobs[i]['WO_NUMBER'];
					document.getElementById("batch-input").value = incomingJobs[i]['BATCH_NUMBER'];
					document.getElementById("operator-in-input").value = incomingJobs[i]['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = incomingJobs[i]['OPERATOR_OUT'];
					document.getElementById("location-input").value = incomingJobs[i]['MANDREL_LOCATION'];
					document.getElementById("drawer-input").value = incomingJobs[i]['MANDREL_DRAWER'];
					document.getElementById("status-input").value = incomingJobs[i]['STATUS_IN'];
					document.getElementById("new-tool-input").value = incomingJobs[i]['TOOL_OUT'];
					document.getElementById("new-location-input").value = incomingJobs[i]['TOOL_LOCATION'];
					document.getElementById("new-drawer-input").value = incomingJobs[i]['TOOL_DRAWER'];
					document.getElementById("new-status-input").value = incomingJobs[i]['STATUS_OUT'];
					
					document.getElementById("in-button").disabled = true;
					document.getElementById("in-button").innerHTML = "Process In";
					document.getElementById("out-button").disabled = true;
					document.getElementById("out-button").innerHTML = "Process Out";
					
					break;
				}
			}
		}
		document.getElementById("retrieve-button").disabled = false;
	}
	
	/**
	  *	@desc	go to toolroomin page
	  *	@param	none
	  *	@return	none
	*/
	function processIn() {
		var process = document.getElementById(selectedRow).children[2].innerHTML;
		if (process == "PARQUET" || process == "CONVERT") {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroomin.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
			document.getElementById("process-form").submit();
		} else if (document.getElementById('in-button').innerHTML == "Details In") {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroomin.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
			document.getElementById("process-form").submit();
		} else {
			var modal = document.getElementById('modal');
			var modalContent = document.getElementById("modal-content");
			modalContent.innerHTML = `<span id="close">&times;</span><br><p>Process entire belt?</p>
										<button id="batch-process-button">Yes</button><button id="single-process-button">No</button>`;
			modal.style.display = "block";
			document.getElementById("batch-process-button").onclick = function() {
				document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroombatchin.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
				document.getElementById("process-form").submit();
			}
			document.getElementById("single-process-button").onclick = function() {
				document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroomin.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
				document.getElementById("process-form").submit();
			}
			
			closeForm();
		}
	}
	
	/**
	  *	@desc	go to toolroomout page
	  *	@param	none
	  *	@return	none
	*/
	function processOut() {
		var process = document.getElementById(selectedRow).children[2].innerHTML;
		if (process == "PARQUET" || process == "CONVERT") {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroomout.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
			document.getElementById("process-form").submit();
		} else if (document.getElementById("out-button").innerHTML == "Details Out") {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroomout.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
			document.getElementById("process-form").submit();
		} else {
			var modal = document.getElementById("modal");
			var modalContent = document.getElementById("modal-content");
			modalContent.innerHTML = `<span id="close">&times;</span><br><p>Process entire belt?</p>
										<button id="batch-process-button">Yes</button><button id="single-process-button">No</button>`;
			modal.style.display = "block";
			document.getElementById("batch-process-button").onclick = function() {
				document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroombatchout.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
				document.getElementById("process-form").submit();
			}
			document.getElementById("single-process-button").onclick = function() {
				document.getElementsByTagName("BODY")[0].innerHTML += `<form action="toolroomout.php" method="POST" style="display: none;" id="process-form"><input type="text" name="id" value="${selectedRow}"><input type="text" name="process" value="${process}"><input type="submit"></form>`;
				document.getElementById("process-form").submit();
			}
			
			closeForm();
		}
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
	  *	@desc	adjust row color based on job status
	  *	@param	DOM Object tr - row to adjust, int index - job array index
	  *	@return	none
	*/
	function setColor(tr, index) {
		if (!incoming) {
			var d = new Date(jobs[index]['DATE_IN']);
			var d2 = new Date();
			var d3 = new Date(jobs[index]['DATE_OUT']);
			
			//Green/Black - not started
			if (jobs[index]['OPERATOR_IN'] == "") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#0f0";
			} else if (jobs[index]['OPERATOR_OUT'] == "" || jobs[index]['OPERATOR_OUT'] == null) {
				//Red/Black - finished
				if (d2 > d3) {
					tr.style.color = "black";
					tr.style.backgroundColor = "#f00";
				//Yellow/Black - in progress
				} else {
					tr.style.color = "black";
					tr.style.backgroundColor = "#ff0";
				}
			//White/Black - processed out
			} else {
				tr.style.color = "black";
				tr.style.backgroundColor = "white";
			}
		} else {
			var d = new Date(incomingJobs[index]['DATE_IN']);
			var d2 = new Date();
			var d3 = new Date(incomingJobs[index]['DATE_OUT']);
			
			//Green/Black - not started
			if (incomingJobs[index]['OPERATOR_IN'] == "") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#0f0";
			} else if (incomingJobs[index]['OPERATOR_OUT'] == "") {
				//Red/Black - finished
				if (d2 > d3) {
					tr.style.color = "black";
					tr.style.backgroundColor = "#f00";
				//Yellow/Black - in progress
				} else {
					tr.style.color = "black";
					tr.style.backgroundColor = "#ff0";
				}
			//White/Black - processed out
			} else {
				tr.style.color = "black";
				tr.style.backgroundColor = "white";
			}
		}
	}
	
	/**
	  *	@desc	sort jobs array by given column
	  *	@param	string value - column to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_toolroom_operations_order",document.getElementById("order-type").value);
		setCookie("sort_toolroom_operations_filter",document.getElementById("filter-type").value);
		setCookie("sort_toolroom_operations_filter_value",document.getElementById("filter-input").value);
		
		if (!incoming) {
			jobs.sort(function(a, b) {
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
					case "process":
						if (a['PROCESS'] < b['PROCESS']) {
							return -1;
						} else if (a['PROCESS'] > b['PROCESS']) {
							return 1;
						} else {
							return 0;
						}
						break;
					case "linecolor":
						var ad = new Date(a['DATE_OUT']);
						var bd = new Date(b['DATE_OUT']);
						var date = new Date();
						if (a['OPERATOR_IN'] == "") {
							if (b['OPERATOR_IN'] == "") {
								return 0;
							} else {
								return -1;
							}
							//"black"
							//"#0f0"
						} else if (a['OPERATOR_OUT'] == "") {
							if (date > ad) {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else if (date > bd) {
									return 0;
								} else {
									return 1;
								}
								//Red/Black - finished
								//"black"
								//"#f00"
							} else {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else if (date > bd) {
									return -1;
								} else {
									return 0;
								}
								//Yellow/Black - in progress
								//"black"
								//"#ff0"
							}
						} else {
							if (b['OPERATOR_OUT'] == "") {
								return 1;
							} else {
								return 0;
							}
						}
						break;
					default:
						return 0;
				}
			});
		} else {
			incomingJobs.sort(function(a, b) {
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
					case "process":
						if (a['PROCESS'] < b['PROCESS']) {
							return -1;
						} else if (a['PROCESS'] > b['PROCESS']) {
							return 1;
						} else {
							return 0;
						}
						break;
					case "linecolor":
						var ad = new Date(a['DATE_OUT']);
						var bd = new Date(b['DATE_OUT']);
						var date = new Date();
						if (a['OPERATOR_IN'] == "") {
							if (b['OPERATOR_IN'] == "") {
								return 0;
							} else {
								return -1;
							}
							//"black"
							//"#0f0"
						} else if (a['OPERATOR_OUT'] == "") {
							if (date > ad) {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else if (date > bd) {
									return 0;
								} else {
									return 1;
								}
								//Red/Black - finished
								//"black"
								//"#f00"
							} else {
								if (b['OPERATOR_IN'] == "") {
									return 1;
								} else if (date > bd) {
									return -1;
								} else {
									return 0;
								}
								//Yellow/Black - in progress
								//"black"
								//"#ff0"
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
	  *	@desc	fill in sorted jobs array to table
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[0].children[1];
		var html = "";
		if (document.getElementById("filter-type")) {
			var keyword, value = document.getElementById("filter-type").value;
			if (value == "linecolor") {
				keyword = document.getElementById("filter-input").value;
			} else {
				keyword = document.getElementById("filter-input").value.toUpperCase();
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
	  *	@desc	determines if row fits filter constraints
	  *	@param	string keyword - keyword to search for, string value - column to search in, array row - row to search in
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
				} else if (row['OPERATOR_OUT'] == "" || row['OPERATOR_OUT'] == null) {
					//Red/Black - finished
					if (d > d2) {
						color = "red";
					//Yellow/Black - in progress
					} else {
						color = "yellow";
					}
				//White/Black - processed out
				} else {
					color = "white";
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
	  *	@desc	go to Retrieve Tool page
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/retrieve.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedRow).children[1].innerHTML}" name="tool"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	switch to/from Incoming Work from/to Current Work
	  *	@param	DOM Object bt - button to change label on
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
		
		document.getElementById("in-button").disabled = true;
		document.getElementById("out-button").disabled = true;
		document.getElementById("retrieve-button").disabled = true;
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
	  *	@param	int id - job ID to search for
	  *	@return	array job - contains job data
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
				var jobs = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of jobs) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				if (jobs.length > 0) {
					job = jobs[0];
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job;
	}
	
	/**
	  *	@desc	move job to archives
	  *	@param	int id - ID of job to send to getJobToDelete()
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Toolroom_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			if (item != 'ID') {
				query += `&${item}=${job[item] == null ? '' : job[item].toString().replace(/[+]/g,"%2B").replace(/[#]/g,"%23").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
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
	  *	@desc	delete job from current schedule
	  *	@param	none
	  *	@return	none
	*/
	function removeFromCurrentWork(id) {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "delete";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded.")) {
					document.getElementById(id).parentNode.removeChild(document.getElementById(id));
				} else {
					alert("Old job not removed from current work. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&id="+id, true);
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
		
		if (checkCookie("sort_toolroom_operations_order")) {
			document.getElementById("order-type").value = getCookie("sort_toolroom_operations_order");
		}
		
		if (checkCookie("sort_toolroom_operations_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_toolroom_operations_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_toolroom_operations_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_toolroom_operations_filter_value");
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
				select.innerHTML += '<option value="' + processes[i][1] + '">' + processes[i][1] + '</option>';
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
</script>
<html>
	<head>
		<title>Tool Room</title>
		<link rel="stylesheet" type="text/css" href="/styles/toolroom.css">
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
						<span id="job-span">Job #<input id="job-input" type="text"></span>
						<span id="po-span">PO #<input id="po-input" type="text" readonly></span>
						<span id="work-span">Work Order<input id="wo-input" type="text"></span><br>
						<span id="batch-span">Batch<input id="batch-input" type="text" readonly></span>
						<span id="operator-in-span">Operator In<input id="operator-in-input" type="text"></span>
						<span id="operator-out-span">Operator Out<input id="operator-out-input" type="text" readonly></span><br>
						<span id="loc-span">Tool Location<input id="location-input" type="text" readonly></span>
						<span id="drawer-span">Drawer<input id="drawer-input" type="text" readonly></span>
						<span id="status-span">Status<input id="status-input" type="text" readonly></span><br>
						<span id="newname-span">New Tool<input id="new-tool-input" type="text" readonly></span><br>
						<span id="newloc-span">New Location<input id="new-location-input" type="text" readonly></span>
						<span id="newdrawer-span">Drawer<input id="new-drawer-input" type="text" readonly></span>
						<span id="newstatus-span">Status<input id="new-status-input" type="text" readonly></span>
					</div>
					<div class="controls">
						<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
						<button id="in-button" onclick="processIn()" disabled>Process In</button>
						<button id="out-button" onclick="processOut()" disabled>Process Out</button>
						<button id="retrieve-button" onclick="retrieveTool()" disabled>Retrieve Tool</button>
						<a href="../operations.php">Back</a>
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