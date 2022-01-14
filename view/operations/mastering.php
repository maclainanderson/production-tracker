<!DOCTYPE html>
<?php
/**
  *	@desc main mastering table for operations
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of jobs
	$jobs = array();
	$incomingJobs = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering_Queue WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$incomingJobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
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
	
	//set up tracking variables
	var incoming = false;
	var isMetric = false;
	var selectedRow=0;
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
	  *	@desc	highlight selected row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		var trs = tr.parentNode.children;
		
		if (!incoming) {
			for (var j=0;j<jobs.length;j++) {
				if (selectedRow == jobs[j]['ID']) {
					setColor(document.getElementById(selectedRow),j);
				}
			}
		} else {
			for (var j=0;j<incomingJobs.length;j++) {
				if (selectedRow == incomingJobs[j]['ID']) {
					setColor(document.getElementById(selectedRow),j);
				}
			}
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		selectedRow = tr.id;
		
		setLinks();
		
		getDetails(tr.id);
	}
	
	/**
	  *	@desc	insert job data
	  *	@param	int id - DB ID of job
	  *	@return	none
	*/
	function getDetails(id) {
		if (!incoming) {
			for(var i=0;i<jobs.length;i++) {
				if (jobs[i]['ID'] == id) {
					document.getElementById("wo-input").value = jobs[i]['WO_NUMBER'];
					document.getElementById("job-input").value = jobs[i]['JOB_NUMBER'];
					document.getElementById("po-input").value = jobs[i]['PO_NUMBER'];
					if (jobs[i]['IS_BLANK'] == "TRUE") {
						document.getElementById("blank-input").value = jobs[i]['BLANK'];
						document.getElementById("recut-input").value = "";
					} else {
						document.getElementById("recut-input").value = jobs[i]['BLANK'];
						document.getElementById("blank-input").value = "";
					}
					document.getElementById("operator-in-input").value = jobs[i]['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = jobs[i]['OPERATOR_OUT'];
					document.getElementById("machine-input").value = jobs[i]['MACHINE_NUMBER'];
					document.getElementById("program-input").value = jobs[i]['PROGRAM_NUMBER'];
					document.getElementById("master-input").value = jobs[i]['TOOL_OUT'];
					getMasterStatus(jobs[i]['TOOL_OUT']);
				}
			}
		} else {
			for(var i=0;i<incomingJobs.length;i++) {
				if (incomingJobs[i]['ID'] == id) {
					document.getElementById("wo-input").value = incomingJobs[i]['WO_NUMBER'];
					document.getElementById("job-input").value = incomingJobs[i]['JOB_NUMBER'];
					document.getElementById("po-input").value = incomingJobs[i]['PO_NUMBER'];
					if (incomingJobs[i]['IS_BLANK'] == "TRUE") {
						document.getElementById("blank-input").value = incomingJobs[i]['BLANK'];
						document.getElementById("recut-input").value = "";
					} else {
						document.getElementById("recut-input").value = incomingJobs[i]['BLANK'];
						document.getElementById("blank-input").value = "";
					}
					document.getElementById("operator-in-input").value = incomingJobs[i]['OPERATOR_IN'];
					document.getElementById("operator-out-input").value = incomingJobs[i]['OPERATOR_OUT'];
					document.getElementById("machine-input").value = incomingJobs[i]['MACHINE_NUMBER'];
					document.getElementById("program-input").value = incomingJobs[i]['PROGRAM_NUMBER'];
					document.getElementById("master-input").value = incomingJobs[i]['TOOL_OUT'];
					getMasterStatus(incomingJobs[i]['TOOL_OUT']);
				}
			}
		}
	}
	
	/**
	  *	@desc	get status of TOOL_OUT
	  *	@param	string master - tool to search for
	  *	@return	none
	*/
	function getMasterStatus(master) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "TOOL";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of response) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDate(new Date(tool[x]['date']));
						}
					}
				}
				if (response.length > 0) {
					document.getElementById("location-input").value = response[0]['LOCATION'];
					document.getElementById("drawer-input").value = response[0]['DRAWER'];
					document.getElementById("status-input").value = response[0]['STATUS'];
					document.getElementById("created-input").value = response[0]['DATE_CREATED'] == "" ? "" : formatDate(new Date(response[0]['DATE_CREATED']));
				} else {
					document.getElementById("location-input").value = "";
					document.getElementById("drawer-input").value = "";
					document.getElementById("status-input").value = "";
					document.getElementById("created-input").value = "";
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+master,true);
		conn.send();
	}
	
	/**
	  *	@desc	set disabled property of buttons when a row is clicked on
	  *	@param	none
	  *	@return	none
	*/
	function setLinks() {
		if (!incoming) {
			for(var i=0;i<jobs.length;i++) {
				if (jobs[i]['ID'] == selectedRow) {
					if (jobs[i]['OPERATOR_OUT'] != '') {
						document.getElementById("in-button").value = 'Details In';
						document.getElementById("out-button").value = 'Details Out';
					} else if (jobs[i]['OPERATOR_IN'] != "") {
						document.getElementById("in-button").value = 'Details In';
						document.getElementById("out-button").value = 'Post-Cut';
					} else {
						document.getElementById("in-button").value = 'Pre-Cut';
						document.getElementById("out-button").value = 'Post-Cut';
					}
				}
			}
		} else {
			document.getElementById("in-button").disabled = true;
			document.getElementById("out-button").disabled = true;
		}
		document.getElementById("in-button").disabled = false;
		document.getElementById("out-button").disabled = false;
		document.getElementById("retrieve-button").disabled = false;
		document.getElementById("id-in").value = selectedRow;
		document.getElementById("id-out").value = selectedRow;
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
	  *	@desc	adjust color of row based on job status
	  *	@param	DOM Object tr - row to adjust, id index - array index
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
			} else if (jobs[index]['OPERATOR_OUT'] == "") {
				//Red/Black - finished
				if (d2 > d3) {
					tr.style.color = "black";
					tr.style.backgroundColor = "#f00";
				//Yellow/Black - in progress
				} else {
					tr.style.color = "black";
					tr.style.backgroundColor = "#ff0";
				}
			} else {
				//White/Black - post cut
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
			} else {
				//White/Black - post cut
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
		setCookie("sort_mastering_operations_order",document.getElementById("order-type").value);
		setCookie("sort_mastering_operations_filter",document.getElementById("filter-type").value);
		setCookie("sort_mastering_operations_filter_value",document.getElementById("filter-input").value);
	
		if (!incoming) {
			jobs.sort(function(a, b) {
				switch(value) {
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
							var ad = new Date(a['DATE_OUT']);
							var bd = new Date(b['DATE_OUT']);
							var date = new Date();
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
		} else {
			incomingJobs.sort(function(a, b) {
				switch(value) {
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
							var ad = new Date(a['DATE_OUT']);
							var bd = new Date(b['DATE_OUT']);
							var date = new Date();
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
	  *	@desc	fill in sorted jobs array
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[0].children[1];
		var html = "";
		var keyword;
		if (document.getElementById("filter-type")) {
			var value = document.getElementById("filter-type").value;
			if (value == "linecolor") {
				keyword = document.getElementById("filter-input").value;
			} else {
				keyword = document.getElementById("filter-input").value.toUpperCase();
			}
		} else {
			var value = "none";
			var keyword = "";
		}
		
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (isAllowed(keyword, value, item)) {
					html += `<tr id="${item['ID']}" onclick="selectRow(this)">
											<td class="col1">${item['TARGET_DATE']}</td>
											<td class="col2">${item['TOOL_IN']}</td>
											<td class="col3">${item['DATE_IN'].split(" ")[0]}</td>
											<td class="col4">${item['DATE_OUT'].split(" ")[0]}</td>
											<td class="col5">${isMetric ? item['SIZE'] * 25.4 : item['SIZE']}</td>
											<td class="col6">${item['TOOL_TYPE']}</td>
											<td class="col7">${item['WORK_TYPE']}</td>
											<td class="col8">${item['COSMETIC']}</td>
										</tr>`;
				}
			});
		} else {
			incomingJobs.forEach((item, index, array) => {
				if (isAllowed(keyword, value, item)) {
					html += `<tr id="${item['ID']}" onclick="selectRow(this)">
											<td class="col1">${item['TARGET_DATE']}</td>
											<td class="col2">${item['TOOL_IN']}</td>
											<td class="col3">${item['DATE_IN'].split(" ")[0]}</td>
											<td class="col4">${item['DATE_OUT'].split(" ")[0]}</td>
											<td class="col5">${isMetric ? item['SIZE'] * 25.4 : item['SIZE']}</td>
											<td class="col6">${item['TOOL_TYPE']}</td>
											<td class="col7">${item['WORK_TYPE']}</td>
											<td class="col8">${item['COSMETIC']}</td>
										</tr>`;
				}
			});
		}
		
		tbody.innerHTML = html;
		
		setColors();
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
	  *	@desc	determines if row fits filter constraints
	  *	@param	string keyword - keyword to search for, string value - column to search in, array row - row to be checked
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
			default:
				valid = true;
		}
		
		return valid;
	}
	
	/**
	  *	@desc	go to Retrieve Tool page
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/design.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedRow).children[1].innerHTML}" name="design"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	switch between incoming and current work
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
	  *	@desc	get job data for archiving
	  *	@param	int id - DB ID of job
	  *	@return	array containing job data
	*/
	function getJobToDelete(id) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Mastering";
		var condition = "ID";
		var value = id;
		var job = {};
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of response) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDate(new Date(tool[x]['date']));
						}
					}
				}
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
	  *	@desc	move job to archive
	  *	@param	int id - passed to getJobToDelete()
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Mastering_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			if (item != 'ID') {
				query += `&${item}=${job[item]}`;
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
	  *	@desc	remove archived job from current work
	  *	@param	none
	  *	@return	none
	*/
	function removeFromCurrentWork(id) {
		var conn = new XMLHttpRequest();
		var table = "Mastering";
		var action = "delete";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded.")) {
					document.getElementById(id).parentNode.removeChild(document.getElementById(id));
				} else {
					alert("Job not removed from current work. Contact IT Support to correct. " + conn.responseText);
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
									<option value="linecolor">Line Color</option>
									<option value="targetdate">Target Date</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none">&lt;NONE&gt;</option>
									<option value="linecolor">Line Color</option>
									<option value="targetdate">Target Date</option>
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
		
		if (checkCookie("sort_mastering_operations_order")) {
			document.getElementById("order-type").value = getCookie("sort_mastering_operations_order");
		}
		
		if (checkCookie("sort_mastering_operations_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_mastering_operations_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_mastering_operations_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_mastering_operations_filter_value");
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
			input.setAttribute("onkeydown","fixDate(this)");
			document.getElementById("filter-container").appendChild(input);
		}
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return string date - MM/DD/YY
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
	  *	@desc	switch units between metric and standard
	  *	@param	DOM Object button - activating button
	  *	@return	none
	*/
	function switchUnit(button) {
		if (button.innerHTML == "Metric") {
			button.innerHTML = "Standard";
			isMetric = true;
			document.getElementsByClassName("main")[0].children[0].children[0].children[0].children[4].innerHTML = "Size(mm)";
		} else {
			button.innerHTML = "Metric";
			isMetric = false;
			document.getElementsByClassName("main")[0].children[0].children[0].children[0].children[4].innerHTML = "Size(in)";
		}
		
		fillSort();
	}
</script>
<html>
	<head>
		<title>Mastering</title>
		<link rel="stylesheet" type="text/css" href="/styles/mastering.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="setColors(); checkSortBox(); archiveJobs();">
		<div class="container">
			<div class="outer">
				<div class="inner">
					<div class="header">
					</div>
					<div class="main">
						<table>
							<thead>
								<tr>
									<th class="col1">Target</th>
									<th class="col2">Design</th>
									<th class="col3">Pre-Cut</th>
									<th class="col4">Post-Cut</th>
									<th class="col5">Size(in)</th>
									<th class="col6">Type</th>
									<th class="col7">ReCut/New</th>
									<th class="col8">Cosmetics</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($jobs as $job) { ?>
								<tr id="<?= $job['ID'] ?>" onclick="selectRow(this)">
									<td class="col1"><?= date_format($job['TARGET_DATE'],'m/d/y') ?></td>
									<td class="col2"><?= $job['TOOL_IN'] ?></td>
									<td class="col3"><?= date_format($job['DATE_IN'],'m/d/y') ?></td>
									<td class="col4"><?= date_format($job['DATE_OUT'],'m/d/y') ?></td>
									<td class="col5"><?= $job['SIZE'] ?></td>
									<td class="col6"><?= $job['WORK_TYPE'] ?></td>
									<td class="col7"><?= $job['TOOL_TYPE'] ?></td>
									<td class="col8"><?= $job['COSMETIC'] ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="left">
						<span id="po-span">PO Number<input id="po-input" type="text" readonly></span>
						<span id="job-span">Job Number<input id="job-input" type="text" readonly></span>
						<span id="wo-span">WO #<input id="wo-input" type="text" readonly></span><br>
						<button class="quality">Q</button><span id="recut-span">ReCut Master<input id="recut-input" type="text" readonly></span>
						<span id="blank-span">Blank<input id="blank-input" type="text" readonly></span><br>
						<span id="created-span">Created<input id="created-input" type="text" readonly></span>
						<span id="operator-in-span">Operator In<input id="operator-in-input" type="text" readonly></span>
						<span id="operator-out-span">Out<input id="operator-out-input" type="text" readonly></span><br>
						<span id="machine-span">Machine<input id="machine-input" type="text" readonly></span>
						<span id="program-span">Program #<input id="program-input" type="text" readonly></span><br>
						<button class="quality">Q</button><span id="master-span">Master<input id="master-input" type="text" readonly></span><br>
						<span id="location-span">Master Location<br><input id="location-input" type="text" readonly></span>
						<span id="drawer-span">Drawer<br><input id="drawer-input" type="text" readonly></span>
						<span id="status-span">Status<br><input id="status-input" type="text" readonly></span>
					</div>
					<div class="controls">
						<div class="controls-left">
							<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
							<button onclick="switchUnit(this)">Metric</button>
							<button id="retrieve-button" onclick="retrieveTool()">Design</button>
						</div>
						<div class="controls-right">
							<form action="masteringin.php" method="post"><input type="text" id="id-in" name="id" hidden><input class="button" id="in-button" type="submit" value="Pre-Cut" disabled></form>
							<form action="masteringout.php" method="post"><input type="text" id="id-out" name="id" hidden><input class="button" id="out-button" type="submit" value="Post-Cut" disabled></form>
							<a href="../operations.php">Back</a>
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