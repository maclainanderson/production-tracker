<!DOCTYPE html>
<?php
/**
  *	@desc main shipping scheduling page
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
	
	//job list, reasons to abort
	$processes = array();
	$jobs = array();
	$incomingJobs = array();
	$reasons = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE DEPARTMENT = 'TOOLRM'");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TARGET_DATE, CUSTOMER, SELECT_DATE, SELECT_OPERATOR, SHIP_DATE, SHIP_OPERATOR, PACKING_SLIP, TOOL, WO_NUMBER, BELT_NUMBER, ON_HOLD FROM Shipping;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[$row['BATCH_NUMBER']][$row['ID']] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TARGET_DATE, CUSTOMER, SELECT_DATE, SELECT_OPERATOR, SHIP_DATE, SHIP_OPERATOR, PACKING_SLIP, TOOL, WO_NUMBER, BELT_NUMBER, ON_HOLD FROM Shipping_Queue;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$incomingJobs[$row['BATCH_NUMBER']][$row['ID']] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, REASON FROM Abort_Work_Order WHERE STATUS = 'Active'");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$reasons[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		var_dump(sqlsrv_errors());
	}
?>
<script src="/scripts/cookies.js"></script>
<script type="text/javascript">
	console.log(``);
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//set up tracking variables
	var incoming = false;
	var selectedRow = 0;
	var selectedToolRow = 0;
	var jobs = {<?php
		foreach($jobs as $batchNumber=>$batch) {
		echo '"' . $batchNumber . '": {';
		foreach($batch as $id=>$job) {
			echo '"' . $id . '": {';
			foreach($job as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo $value;
				}
				echo '`,';
			}
			echo '},';
		}
		echo '},';
	}
	?>};
	
	var incomingJobs = {<?php
		foreach($incomingJobs as $batchNumber=>$batch) {
		echo '"' . $batchNumber . '": {';
		foreach($batch as $id=>$job) {
			echo '"' . $id . '": {';
			foreach($job as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo $value;
				}
				echo '`,';
			}
			echo '},';
		}
		echo '},';
	}
	?>};
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	//up/down arrow keys scroll table up/down
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
	  *	@param	DOM Object tr - table row clicked on
	  *	@return	none
	*/
	function selectRow(tr) {
		if (!incoming) {
			if (jobs[tr.id][Object.keys(jobs[tr.id]).pop()]['SELECT_OPERATOR'] != "") {
				document.getElementById("hold-button").disabled = true;
			} else {
				document.getElementById("hold-button").disabled = false;
				if (jobs[tr.id][Object.keys(jobs[tr.id]).pop()]['ON_HOLD'] == "TRUE") {
					document.getElementById("hold-button").innerHTML = "Take Off Hold";
				} else {
					document.getElementById("hold-button").innerHTML = "Place On Hold";
				}
			}
		} else {
			if (incomingJobs[tr.id][Object.keys(incomingJobs[tr.id]).pop()]['SELECT_OPERATOR'] != "") {
				document.getElementById("hold-button").disabled = true;
			} else {
				document.getElementById("hold-button").disabled = false;
				if (incomingJobs[tr.id][Object.keys(incomingJobs[tr.id]).pop()]['ON_HOLD'] == "TRUE") {
					document.getElementById("hold-button").innerHTML = "Take Off Hold";
				} else {
					document.getElementById("hold-button").innerHTML = "Place On Hold";
				}
			}
		}
		
		setColors();
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		selectedRow = tr.id;
		
		document.getElementById("edit-button").disabled = false;
		document.getElementById("abort-button").disabled = false;
		document.getElementById("retrieve-button").disabled = true;
		document.getElementById("return-button").disabled = true;
		
		getTools(tr);
	}
	
	/**
	  *	@desc	display tools that are in shipping job
	  *	@param	DOM Object tr - table row clicked on
	  *	@return	none
	*/
	function getTools(tr) {
		var tbody = document.getElementById("tool-table").children[1];
		tbody.innerHTML = "";
		if (!incoming) {
			var ids = Object.keys(jobs[tr.id]);
			ids.sort(function (a, b) {
				if (typeof jobs[tr.id][a] == "string") {
					return -1;
				} else if (typeof jobs[tr.id][b] == "string") {
					return 1;
				} else {
					var a1 = jobs[tr.id][a]['TOOL'].split("-");
					var a2 = a1.pop();
					a1 = a1.join("-");
					var b1 = jobs[tr.id][b]['TOOL'].split("-");
					var b2 = b1.pop();
					b1 = b1.join("-");
					
					if (a1 < b1) {
						return -1;
					} else if (a1 > b1) {
						return 1;
					} else {
						if (parseInt(a2) == NaN || parseInt(b2) == NaN) {
							return a2 > b2;
						} else if (parseInt(a2) > parseInt(b2)) {
							return 1;
						} else {
							return 0;
						}
					}
				}
			});
			ids.forEach((item, index, array) => {
				tbody.innerHTML += `<tr onclick="selectToolRow(this)" id="${jobs[tr.id][item]['ID']}"><td class="col1">${jobs[tr.id][item]['TOOL']}</td><td class="col2">${jobs[tr.id][item]['BELT_NUMBER']}</td></tr>`;
			});
			/*jobs.forEach((item, index, array) => {
				if (item[0] == tr.id) {
					item.sort(function (a, b) {
						if (typeof a == "string") {
							return -1;
						} else if (typeof b == "string") {
							return 1;
						} else {
							var a1 = a[9].split("-");
							a2 = a1.pop();
							a1 = a1.join("-");
							var b1 = b[9].split("-");
							b2 = b1.pop();
							b1 = b1.join("-");
							
							if (a1 < b1) {
								return -1;
							} else if (a1 > b1) {
								return 1;
							} else {
								if (parseInt(a2) == NaN || parseInt(b2) == NaN) {
									return a2 > b2;
								} else {
									if (parseInt(a2) < parseInt(b2)) {
										return -1;
									} else if (parseInt(a2) > parseInt(b2)) {
										return 1;
									} else {
										return 0;
									}
								}
							}
						}
					});
					for (var i=1;i<item.length;i++) {
						tbody.innerHTML += `<tr onclick="selectToolRow(this)" id="${item[i][0]}"><td class="col1">${item[i][9]}</td><td class="col2">${item[i][11]}</td></tr>`;
					}
				}
			});*/
		} else {
			var ids = Object.keys(incomingJobs[tr.id]);
			ids.sort(function (a, b) {
				if (typeof incomingJobs[tr.id][a] == "string") {
					return -1;
				} else if (typeof incomingJobs[tr.id][b] == "string") {
					return 1;
				} else {
					var a1 = incomingJobs[tr.id][a]['TOOL'].split("-");
					var a2 = a1.pop();
					a1 = a1.join("-");
					var b1 = incomingJobs[tr.id][b]['TOOL'].split("-");
					var b2 = b1.pop();
					b1 = b1.join("-");
					
					if (a1 < b1) {
						return -1;
					} else if (a1 > b1) {
						return 1;
					} else {
						if (parseInt(a2) == NaN || parseInt(b2) == NaN) {
							return a2 > b2;
						} else if (parseInt(a2) > parseInt(b2)) {
							return 1;
						} else {
							return 0;
						}
					}
				}
			});
			ids.forEach((item, index, array) => {
				tbody.innerHTML += `<tr onclick="selectToolRow(this)" id="${incomingJobs[tr.id][item]['ID']}"><td class="col1">${incomingJobs[tr.id][item]['TOOL']}</td><td class="col2">${incomingJobs[tr.id][item]['BELT_NUMBER']}</td></tr>`;
			});
			/*incomingJobs.forEach((item, index, array) => {
				if (item[0] == tr.id) {
					item.sort(function (a, b) {
						if (typeof a == "string") {
							return -1;
						} else if (typeof b == "string") {
							return 1;
						} else {
							var a1 = a[9].split("-");
							a2 = a1.pop();
							a1 = a1.join("-");
							var b1 = b[9].split("-");
							b2 = b1.pop();
							b1 = b1.join("-");
							
							if (a1 < b1) {
								return -1;
							} else if (a1 > b1) {
								return 1;
							} else {
								if (parseInt(a2) == NaN || parseInt(b2) == NaN) {
									return a2 > b2;
								} else {
									if (parseInt(a2) < parseInt(b2)) {
										return -1;
									} else if (parseInt(a2) > parseInt(b2)) {
										return 1;
									} else {
										return 0;
									}
								}
							}
						}
					});
					for (var i=1;i<item.length;i++) {
						tbody.innerHTML += `<tr onclick="selectToolRow(this)" id="${item[i][0]}"><td class="col1">${item[i][9]}</td><td class="col2">${item[i][11]}</td></tr>`;
					}
				}
			});*/
		}
	}
	
	/**
	  *	@desc	highlight tool row, unhighlight others
	  *	@param	DOM Object tr - tool row clicked on
	  *	@return	none
	*/
	function selectToolRow(tr) {
		var trs = tr.parentNode.children;
		for (var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		if (jobs[selectedRow][Object.keys(jobs[selectedRow]).pop()]['SHIP_OPERATOR'] != '') {
			document.getElementById("return-button").disabled = true;
		} else {
			document.getElementById("return-button").disabled = false;
		}
		
		document.getElementById("retrieve-button").disabled = false;
		
		selectedToolRow = tr.id;
	}
	
	/**
	  *	@desc	create and submit form to edit shipping job
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		if (selectedRow != 0) {
			if (document.getElementById(selectedRow).children[3].innerHTML == "" && document.getElementById(selectedRow).children[3].innerHTML == "") {
				var batch = selectedRow;
				document.getElementsByTagName("BODY")[0].innerHTML += `<form action="editshipping.php" method="POST" style="display: none;" id="edit-form"><input type="text" name="batch" value="${batch}"><input type="submit"></form>`;
				document.getElementById("edit-form").submit();
			} else {
				alert("Job cannot be edited once started");
			}
		} else {
			alert("Select a job first");
		} 
	}
	
	/**
	  *	@desc	transfer from current work to abort history
	  *	@param	none
	  *	@return	none
	*/
	function abort() {
		var abortCounter = 0;
		var abortJobs = [];
		if (!incoming) {
			var batch = jobs[selectedRow];
		} else {
			var batch = incomingJobs[selectedRow];
		}
		
		for (var job in batch) {
			abortJobs.push({
				BATCH_NUMBER: batch[job]['BATCH_NUMBER'],
				TOOL: batch[job]['TOOL'],
				PROCESS: "SHIPPING",
				DEPARTMENT: "SHIPPING",
				REASON: "",
				WO_NUMBER: batch[job]['WO_NUMBER'],
				DATE: formatDate(new Date()),
				OPERATOR: ""
			});
		}
		
		if (abortJobs.length>0) {
			
			document.getElementById("modal").style.display = "block";
			var modalContent = document.getElementById("modal-content");
			modalContent.innerHTML = `<span>Reason for aborting job:</span><br>
									  <select id="reason-select">
									  <?php foreach($reasons as $reason) { ?>
									  <option value="<?=$reason['REASON']?>"><?=$reason['REASON']?></option>
									  <?php } ?>
									  </select><br>
									  <input onblur="this.value = this.value.toUpperCase();" type="text" id="abort-operator-input" <?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?> value="<?=$_SESSION['initials']?>" <?php } ?>>
									  <button id="submit-abort">Submit</button>`;
									  
			document.getElementById("submit-abort").addEventListener('click', function() {
		
				abortJobs.forEach((item, index, array) => {
					var conn = new XMLHttpRequest();
					var table = "Abort_History";
					var action = "insert";
					item.REASON = document.getElementById("reason-select").value;
					item.OPERATOR = document.getElementById("abort-operator-input").value;
					var query = "";
					
					Object.keys(item).forEach((item1, index1, array1) => {
						query += `&${item1}=${item[item1].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					});
					
					conn.onreadystatechange = function() {
						if (conn.readyState == 4 && conn.status == 200) {
							if (conn.responseText.includes("Insert succeeded")) {
								abortCounter++;
								checkAbortSuccess(abortCounter);
							} else {
								alert("Could not delete jobs. Contact support to correct. " + conn.responseText);
							}
						}
					}
					
					conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
					conn.send();
				});
			});
		} else {
			alert("Job updated");
			window.location.replace("shipping.php");
		}
	}
	
	/**
	  *	@desc	see if all jobs aborted successfully
	  *	@param	int counter - jobs aborted so far
	  *	@return	none
	*/
	function checkAbortSuccess(counter) {
		var trs = document.getElementById("tool-table").children[1].children;
		if (counter == trs.length) {
			deleteJobs();
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	remove jobs from current work
	  *	@param	none
	  *	@return	none
	*/
	function deleteJobs() {
		var deleteCounter = 0;
		var trs = document.getElementById("tool-table").children[1].children;
		
		if (trs.length>0) {
			for (var i=0;i<trs.length;i++) {
				var conn = new XMLHttpRequest();
				if (!incoming) {
					var table = "Shipping";
				} else {
					var table = "Shipping_Queue";
				}
				var action = "delete";
				var query = "&id="+trs[i].id;
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Deletion succeeded")) {
							deleteCounter++;
							checkDeleteSuccess(deleteCounter);
						} else {
							alert("Could not delete jobs. Contact support to correct. " + conn.responseText);
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
				conn.send();
			}
		} else {
			alert("Job updated");
			window.location.replace("shipping.php");
		}
	}
	
	/**
	  *	@desc	see if all jobs deleted successfully
	  *	@param	int counter - jobs deleted so far
	  *	@return	none
	*/
	function checkDeleteSuccess(counter) {
		var trs = document.getElementById("tool-table").children[1].children;
		if (counter == trs.length) {
			alert("Job deleted.");
			window.location.replace("shipping.php");
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	convert date object into string
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
				for (var batch in jobs) {
					if (trs[i].id == batch) {
						setColor(trs[i],batch);
					}
				}
			} else {
				for (var batch in incomingJobs) {
					if (trs[i].id == batch) {
						setColor(trs[i],batch);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	set row's color according to job status
	  *	@param	DOM Object tr - table row to modify, int batchNumber - batch number to find
	  *	@return	none
	*/
	function setColor(tr, batchNumber) {
		if (!incoming) {
			for (var job in jobs[batchNumber]) {
				if (jobs[batchNumber][job]['ON_HOLD'] == "TRUE") {
					tr.style.color = "black";
					tr.style.backgroundColor = "#e0e";
				} else {
					if (jobs[batchNumber][job]['SHIP_OPERATOR'] == "") {
						if (jobs[batchNumber][job]['SELECT_OPERATOR'] == "") {
							tr.style.color = "black";
							tr.style.backgroundColor = "#f00";
						} else {
							tr.style.color = "black";
							tr.style.backgroundColor = "#ff0";
						}
					} else {
						tr.style.color = "black";
						tr.style.backgroundColor = "white";
					}
				}
				break;
			}
		} else {
			for (var job in incomingJobs[batchNumber]) {
				if (incomingJobs[batchNumber][job]['ON_HOLD'] == "TRUE") {
					tr.style.color = "black";
					tr.style.backgroundColor = "#e0e";
				} else {
					if (incomingJobs[batchNumber][job]['SHIP_OPERATOR'] == "") {
						if (incomingJobs[batchNumber][job]['SELECT_OPERATOR'] == "") {
							tr.style.color = "black";
							tr.style.backgroundColor = "#f00";
						} else {
							tr.style.color = "black";
							tr.style.backgroundColor = "#ff0";
						}
					} else {
						tr.style.color = "black";
						tr.style.backgroundColor = "white";
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
		setCookie("sort_shipping_scheduling_order",document.getElementById("order-type").value);
		setCookie("sort_shipping_scheduling_filter",document.getElementById("filter-type").value);
		setCookie("sort_shipping_scheduling_filter_value",document.getElementById("filter-input").value);
		
		if (!incoming) {
			var keys = Object.keys(jobs);
		} else {
			var keys = Object.keys(incomingJobs);
		}
		
		keys.sort(function(a, b) {
			var jobA, jobB;
			
			if (!incoming) {
				for (var job in jobs[a]) {
					jobA = jobs[a][job];
					break;
				}
				for (var job in jobs[b]) {
					jobB = jobs[b][job];
					break;
				}
			} else {
				for (var job in incomingJobs[a]) {
					jobA = incomingJobs[a][job];
					break;
				}
				for (var job in incomingJobs[b]) {
					jobB = incomingJobs[b][job];
					break;
				}
			}
			switch(value) {
				case "linecolor":
					if (jobA['SELECT_OPERATOR'] == "") {
						if (jobB['SELECT_OPERATOR'] == "") {
							return 0;
						} else {
							return -1;
						}
					} else if (jobA['SHIP_OPERATOR'] == "") {
						if (jobB['SELECT_OPERATOR'] == "") {
							return 1;
						} else if (jobB['SHIP_OPERATOR'] == "") {
							return 0;
						} else {
							return -1;
						}
					} else {
						if (jobB['SHIP_OPERATOR'] == "") {
							return 0;
						} else {
							return -1;
						}
					}
					break;
				case "CUSTOMER":
				case "PACKING_SLIP":
				case "TARGET_DATE":
					if (jobA[value] < jobB[value]) {
						return -1;
					} else if (jobA[value] > jobB[value]) {
						return 1;
					} else {
						return 0;
					}
				default:
					return 0;
			}
		});
		
		fillSort(keys);
	}
	
	/**
	  *	@desc	fill in newly sorted array
	  *	@param	array keys - sorted keys of jobs object
	  *	@return	none
	*/
	function fillSort(keys) {
		var tbody = document.getElementById("batch-table").children[1];
		var html = "";
		if (document.getElementById("filter-type")) {
			var value = document.getElementById("filter-type").value;
			var keyword = document.getElementById("filter-input").value;
			if (value != "linecolor") {
				keyword = keyword.toUpperCase();
			}
		} else {
			var value = "none";
			var keyword = "";
		}
		
		if (!incoming) {
			keys.forEach((item, index, array) => {
				if (isAllowed(keyword, value, jobs[item])) {
					var job;
					for (var x in jobs[item]) {
						job = jobs[item][x];
						break;
					}
					html += `<tr id="${job['BATCH_NUMBER']}" onclick="selectRow(this)">
											<td class="col1">${job['TARGET_DATE']}</td>
											<td class="col2">${job['PACKING_SLIP']}</td>
											<td class="col3">${job['CUSTOMER']}</td>
											<td class="col4">${job['SELECT_OPERATOR']}</td>
											<td class="col5">${job['SELECT_DATE']}</td>
											<td class="col6">${job['SHIP_OPERATOR']}</td>
											<td class="col7">${job['SHIP_DATE']}</td>
										</tr>`;
				}
			});
		} else {
			keys.forEach((item, index, array) => {
				if (isAllowed(keyword, value, incomingJobs[item])) {
					var job;
					for (var x in incomingJobs[item]) {
						job = incomingJobs[item][x];
						break;
					}
					html += `<tr id="${job['BATCH_NUMBER']}" onclick="selectRow(this)">
											<td class="col1">${job['TARGET_DATE']}</td>
											<td class="col2">${job['PACKING_SLIP']}</td>
											<td class="col3">${job['CUSTOMER']}</td>
											<td class="col4">${job['SELECT_OPERATOR']}</td>
											<td class="col5">${job['SELECT_DATE']}</td>
											<td class="col6">${job['SHIP_OPERATOR']}</td>
											<td class="col7">${job['SHIP_DATE']}</td>
										</tr>`;
				}
			});
		}
		
		tbody.innerHTML = html;
		
		setColors();
	}
	
	/**
	  *	@desc	determine if row matches filter constraints
	  *	@param	string keyword - string to sort out, string value - column to filter by, array row - row to match
	  *	@return	true if match, false otherwise
	*/
	function isAllowed(keyword, value, row) {
		var valid = false;
		var job;
		for (var x in row) {
			job = row[x];
			break;
		}
		
		switch(value) {
			case "CUSTOMER":
				if (job["CUSTOMER"].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			case "linecolor":
				if (job['SHIP_OPERATOR'] == "") {
					if (job['SELECT_OPERATOR'] == "") {
						color = "red";
					} else {
						color = "yellow";
					}
				} else {
					color = "green";
				}
				
				if (color == keyword) {
					valid = true;
				}
				break;
			case "PACKING_SLIP":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,"."),"g").test(job['PACKING_SLIP'].toUpperCase()) && keyword.length == job['PACKING_SLIP'].length) {
						valid = true;
					}
				} else {
					if (job['PACKING_SLIP'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "TARGET_DATE":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(job['TARGET_DATE'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (job['TARGET_DATE'].toUpperCase().includes(keyword)) {
						valid = true;
					}
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
	  *	@desc	create/submit request to Retrieve Tool page
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/retrieve.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedToolRow).children[0].innerHTML}" name="tool"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	switch between Incoming Work and Current Schedule
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
		
		document.getElementById("edit-button").disabled = true;
		document.getElementById("abort-button").disabled = true;
		document.getElementById("retrieve-button").disabled = true;
		document.getElementById("return-button").disabled = true;
	}
	
	/**
	  *	@desc	archiveJob handler
	  *	@param	none
	  *	@return	none
	*/
	function archiveJobs() {
		for (var batch in jobs) {
			for (var job in jobs[batch]) {
				var outDate = new Date(formatDate(new Date(jobs[batch][job]['SHIP_DATE'])));
				var today = new Date(formatDate(new Date()));
				if (jobs[batch][job]['SHIP_OPERATOR'] != "" && jobs[batch][job]['SELECT_OPERATOR'] != '' && outDate < today) {
					archiveJob(job);
				}
			}
		}
	}
	
	/**
	  *	@desc	fetch data to move job to archive
	  *	@param	int id - db table ID
	  *	@return	array containing job data
	*/
	function getJobToDelete(id) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Shipping";
		var condition = "ID";
		var value = id;
		var job = {};
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				if (response.length > 0) {
					job = response[0];
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job;
	}
	
	/**
	  *	@desc	move job to archives
	  *	@param	int id - db table ID
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Shipping_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			if (job[item] && item != 'ID') {
				if (typeof job[item] === 'object') {
					query += `&${item}=${formatDate(new Date(job[item]['date']))}`;
				} else {
					query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
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
	  *	@desc	remove job from current work
	  *	@param	int id - db table ID
	  *	@return	none
	*/
	function deleteOldJob(id) {
		var conn = new XMLHttpRequest();
		var table = "Shipping";
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
	  *	@desc	remove reserved tool name
	  *	@param	none
	  *	@return	none
	*/
	function returnTool() {
		var tool = document.getElementById(selectedToolRow).children[0].innerHTML;
		for (var batch in jobs) {
			for (var job in jobs[batch]) {
				if (jobs[batch][job]['TOOL'] == tool) {
					var conn = new XMLHttpRequest();
					var action = "delete";
					if (!incoming) {
						var table = "Shipping";
					} else {
						var table = "Shipping_Queue";
					}
					var query = "&ID="+job;
					
					conn.onreadystatechange = function() {
						if (conn.readyState == 4 && conn.status == 200) {
							if (conn.responseText.includes("Deletion succeeded")) {
								alert("Tool returned to inventory");
								document.getElementById(selectedToolRow).parentNode.removeChild(document.getElementById(selectedToolRow));
							} else {
								alert("Could not return tool. Contact IT Support to correct.");
							}
						}
					}
					
					conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
					conn.send();
				}
			}
		}
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
									<option value="CUSTOMER">Customer</option>
									<option value="linecolor">Line Color</option>
									<option value="PACKING_SLIP">Packing Slip</option>
									<option value="TARGET_DATE">Target Date</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none">&lt;NONE&gt;</option>
									<option value="CUSTOMER">Customer</option>
									<option value="linecolor">Line Color</option>
									<option value="PACKING_SLIP">Packing Slip</option>
									<option value="TARGET_DATE">Target Date</option>
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
		
		if (checkCookie("sort_shipping_scheduling_order")) {
			document.getElementById("order-type").value = getCookie("sort_shipping_scheduling_order");
		}
		
		if (checkCookie("sort_shipping_scheduling_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_shipping_scheduling_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_shipping_scheduling_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_shipping_scheduling_filter_value");
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
		var table = incoming ? "Shipping_Queue" : "Shipping";
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
						for (var job in incomingJobs[selectedRow]) {
							incomingJobs[selectedRow][job]['ON_HOLD'] = value;
						}
					} else {
						for (var job in jobs[selectedRow]) {
							jobs[selectedRow][job]['ON_HOLD'] = value;
						}
					}
					setColors();
					document.getElementById(selectedRow).click();
				} else {
					alert("Could not update job. Contact IT support to correct.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&ON_HOLD="+value+"&condition=BATCH_NUMBER&value="+selectedRow,false);
		conn.send();
	}
</script>
<html>
	<head>
		<title>Shipping Scheduling</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/shipping.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="setColors(); checkSortBox(); archiveJobs();">
		<div class="container">
			<div class="outer">
				<div class="inner">
					<div class="main">
						<table id="batch-table">
							<thead>
								<tr>
									<th class="col1">Target</th>
									<th class="col2">Packing Slip</th>
									<th class="col3">Customer</th>
									<th class="col4">Operator In</th>
									<th class="col5">Date</th>
									<th class="col6">Operator Out</th>
									<th class="col7">Date</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($jobs as $batchNumber=>$batch) {
								$job = reset($batch); ?>
								<tr id="<?=$job['BATCH_NUMBER']?>" onclick="selectRow(this)">
									<td class="col1"><?=date_format($job['TARGET_DATE'],'m/d/y')?></td>
									<td class="col2"><?=$job['PACKING_SLIP']?></td>
									<td class="col3"><?=$job['CUSTOMER']?></td>
									<td class="col4"><?=$job['SELECT_OPERATOR']?></td>
									<td class="col5"><?=date_format($job['SELECT_DATE'],'m/d/y')?></td>
									<td class="col6"><?=$job['SHIP_OPERATOR']?></td>
									<td class="col7"><?=date_format($job['SHIP_DATE'],'m/d/y')?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="left">
						<table id="tool-table">
							<thead>
								<tr>
									<th class="col1">Tools</th>
									<th class="col2">Belt #</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>
					<div class="controls">
						<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
						<a href="addshipping.php">Add</a>
						<button id="edit-button" onclick="edit()" disabled>Edit</button>
						<button id="abort-button" onclick="abort()" disabled>Abort</button>
						<button id="retrieve-button" onclick="retrieveTool()" disabled>Retrieve Tool</button>
						<button id="return-button" onclick="returnTool()" disabled>Return to Inventory</button>
						<button id="hold-button" onclick="placeOnHold(this.innerHTML)" disabled>Place On Hold</button>
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