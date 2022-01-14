<!DOCTYPE html>
<?php
/**
  *	@desc	shows basic details about currently scheduled batches
*/
	require_once("../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up sql connection for loading data
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//various lists of jobs and batches
	$batches = array();
	$mastering = array();
	$toolroom = array();
	$electroforming = array();
	$shipping = array();
	$batchesToRemove = array();
	$reasons = array();
	$processes = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Batches ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$batches[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, TOOL_IN FROM Mastering ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$mastering[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, PROCESS, TOOL_IN FROM Toolroom ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolroom[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, PROCESS, TOOL_IN FROM Electroforming ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$electroforming[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, TOOL FROM Shipping ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$shipping[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, TOOL_IN FROM Mastering_Queue ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$mastering[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, PROCESS, TOOL_IN FROM Toolroom_Queue ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolroom[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, PROCESS, TOOL_IN FROM Electroforming_Queue ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$electroforming[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, SEQNUM, TOOL FROM Shipping_Queue ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$shipping[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, REASON FROM Abort_Batch;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$reasons[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes ORDER BY PROCESS;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		echo "Error: could not connect to database.";
		var_dump(sqlsrv_errors());
	}
	
	//find next process and next sequence number
	foreach($batches as $id => $batch) {
		$keepBatch = false;
		$currentSeqNum = 100;
		$nextSeqNum = 101;
		$currentProcess = "";
		$currentDepartment = "";
		$nextProcess = "";
		
		
		foreach($mastering as $master) {
			if ($batch['BATCH_NUMBER'] == $master['BATCH_NUMBER']) {
			
				//keep this batch if currently scheduled
				$keepBatch = true;
				
				if ($master['SEQNUM'] < $currentSeqNum) {
					$nextSeqNum = $currentSeqNum;
					$nextProcess = $currentProcess;
					$currentSeqNum = $master['SEQNUM'];
					$currentProcess = "MASTERING";
					$currentDepartment = "MASTERING";
					
					if ($currentProcess == $nextProcess) {
						$nextProcess = "";
					}
					
				} else if ($master['SEQNUM'] > $currentSeqNum && $master['SEQNUM'] < $nextSeqNum) {
					$nextSeqNum = $master['SEQNUM'];
					$nextProcess = "MASTERING";
				}
			}
		}
		
		foreach($toolroom as $tool) {
			if ($batch['BATCH_NUMBER'] == $tool['BATCH_NUMBER']) {
			
				//keep this batch if currently scheduled
				$keepBatch = true;
				
				if ($tool['SEQNUM'] < $currentSeqNum) {
					$nextSeqNum = $currentSeqNum;
					$nextProcess = $currentProcess;
					$currentSeqNum = $tool['SEQNUM'];
					$currentProcess = $tool['PROCESS'];
					$currentDepartment = "TOOLROOM";
					
					if ($currentProcess == $nextProcess) {
						$nextProcess = "";
					}
					
				} else if ($tool['SEQNUM'] < $nextSeqNum && $tool['SEQNUM'] > $currentSeqNum) {
					$nextSeqNum = $tool['SEQNUM'];
					$nextProcess = $tool['PROCESS'];
				}
			}
		}
		
		foreach($electroforming as $electroform) {
			if ($batch['BATCH_NUMBER'] == $electroform['BATCH_NUMBER']) {
			
				//keep this batch if currently scheduled
				$keepBatch = true;
				
				if ($electroform['SEQNUM'] < $currentSeqNum) {
					$nextSeqNum = $currentSeqNum;
					$nextProcess = $currentProcess;
					$currentSeqNum = $electroform['SEQNUM'];
					$currentProcess = $electroform['PROCESS'];
					$currentDepartment = "ELECTROFORM";
					
				} else if ($electroform['SEQNUM'] < $nextSeqNum && $electroform['SEQNUM'] > $currentSeqNum) {
					$nextProcess = $electroform['PROCESS'];
				}
			}
		}
		
		foreach($shipping as $ship) {
			if ($batch['BATCH_NUMBER'] == $ship['BATCH_NUMBER']) {
				
				//keep this batch if currently scheduled
				$keepBatch = true;
				
				if ($ship['SEQNUM'] < $currentSeqNum) {
					$nextSeqNum = $currentSeqNum;
					$nextProcess = $currentProcess;
					$currentSeqNum = $ship['SEQNUM'];
					$currentProcess = "SHIPPING";
					$currentDepartment = "SHIPPING";
					
				} else if ($ship['SEQNUM'] < $nextSeqNum && $ship['SEQNUM'] > $currentSeqNum) {
					$nextProcess = "SHIPPING";
				}
			}
		}
		
		$batches[$id]['CURRENT_DEPARTMENT'] = $currentDepartment;
		$batches[$id]['CURRENT_PROCESS'] = $currentProcess;
		$batches[$id]['NEXT_PROCESS'] = $nextProcess;
		
		//remove batch if no jobs scheduled
		if ($keepBatch != true) {
			unset($batches[$id]);
		}
	}
	
	foreach($batches as $id => $batch) {
		$tables = array("Mastering","Mastering_Queue","Toolroom","Toolroom_Queue","Electroforming","Electroforming_Queue","Shipping","Shipping_Queue");
		$tools = array();
		foreach($tables as $table) {
			if ($table != "Shipping" && $table != "Shipping_Queue") {
				$result = sqlsrv_query($conn, "SELECT TOOL_IN FROM " . $table . " WHERE BATCH_NUMBER = " . $batch['BATCH_NUMBER'] . " ORDER BY TOOL_IN ASC;");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$found = false;
						foreach($tools as $tool) {
							if ($tool == $row['TOOL_IN']) {
								$found = true;
							}
						}
						if (!$found) {
							$tools[] = $row['TOOL_IN'];
						}
					}
				} else {
					var_dump(sqlsrv_errors());
				}
			} else {
				$result = sqlsrv_query($conn, "SELECT TOOL FROM " . $table . " WHERE BATCH_NUMBER = " . $batch['BATCH_NUMBER'] . " ORDER BY TOOL ASC;");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$found = false;
						foreach($tools as $tool) {
							if ($tool == $row['TOOL']) {
								$found = true;
							}
						}
						if (!$found) {
							$tools[] = $row['TOOL'];
						}
					}
				} else {
					var_dump(sqlsrv_errors());
				}
			}
		}
		$batches[$id]['TOOLS'] = $tools;
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
	var selectedRow = 0;
	var batches = [<?php
		foreach($batches as $batch) {
			echo '{';
			foreach($batch as $key=>$value) {
				if (gettype($value) == "array") {
					echo '"TOOLS": [';
					foreach($value as $i) {
						echo '`';
						if ($i instanceof DateTime) {
							echo date_format($i,'m/d/y');
						} else {
							echo str_replace(PHP_EOL, ' ', addslashes($i));
						}
						echo '`';
						echo ',';
					}
					echo '],';
				} else {
					echo '"' . $key . '": `';
					if ($value instanceof DateTime) {
						echo date_format($value,'m/d/y');
					} else {
						echo str_replace(PHP_EOL, ' ', addslashes($value));
					}
					echo '`';
					echo ',';
				}
			}
			echo '}';
			echo ',';
		}
	?>];
	
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
	
	for (var i=0;i<batches.length;i++) {
		batches[i]['BATCH_INSTRUCTIONS'].replace("\\n","\r\n");
	}
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	//make up/down keys scroll through list
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
	  *	@desc	highlight row and remove highlist from any others
	  *	@param	DOM Object tr - table row that was clicked on
	  *	@return	none
	*/
	function selectRow(tr) {
		selectedRow = tr.id;
		var trs = tr.parentNode.children;
		
		setColors();
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		for (var i=0;i<batches.length;i++) {
			if (batches[i][0] == tr.id) {
				if (batches[i][8] == "TRUE") {
					document.getElementById("hold-button").innerHTML = "Hold Off";
				} else {
					document.getElementById("hold-button").innerHTML = "Hold On";
				}
			}
		}
		
		getDetails();
	}
	
	/**
	  *	@desc	fill in details on the batch selected
	  *	@param	none
	  *	@return	none
	*/
	function getDetails() {
		var jobs = getJobs(document.getElementById(selectedRow).children[0].innerHTML);
		console.log(jobs);
		jobs.sort(function(a, b) {
			var a1 = a['TOOL'].split("-");
			a2 = a1.pop();
			a1 = a1.join("-");
			var b1 = b['TOOL'].split("-");
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
		});
		var table = document.getElementsByClassName('secondary')[0].children[1];
		table.innerHTML = "";
		jobs.forEach((item, index, array) => {
			var beltNumber;
			if (item['BELT_NUMBER'] != undefined && item['BELT_NUMBER'] != '') {
				beltNumber = item['BELT_NUMBER'];
			} else {
				for (let x of batches) {
					if (x['ID'] == selectedRow) {
						beltNumber = x['BELT_NUMBER'];
					}
				}
			}
			if (!alreadyExists(item['TOOL'])) {
				table.innerHTML += `<td class="col1">${item['TOOL']}</td><td class="col2">${beltNumber}</td>`;
			} else {
				for(var i=0;i<table.children.length;i++) {
					if (table.children[i].children[0].innerHTML == item['TOOL'] && table.children[i].children[1].innerHTML != '') {
						table.children[i].children[1].innerHTML = beltNumber;
					}
				}
			}
		});
		
		batches.forEach((item, index, array) => {
			if (item['BATCH_NUMBER'] == document.getElementById(selectedRow).children[0].innerHTML) {
				document.getElementById('special-textarea').value = item['BATCH_INSTRUCTIONS'];
				document.getElementById("target-input").value = item['TARGET_DATE'];
			}
		});
	}
	
	/**
	  *	@desc	determines if tool is already in tool list
	  *	@param	string tool - tool name to search for
	  *	@return	true if found, nothing otherwise
	*/
	function alreadyExists(tool) {
		var trs = document.getElementsByClassName('secondary')[0].children[1].children;
		for(var i=0;i<trs.length;i++) {
			if (trs[i].children[0].innerHTML == tool) {
				return true;
			}
		}
	}
	
	/**
	  *	@desc	fetch jobs for selected batch row
	  *	@param	int batchNumber - ID for batch row
	  *	@return	Array jobs - containing job data
	*/
	function getJobs(batchNumber) {
		var conn = new XMLHttpRequest();
		var tables = ["Mastering","Toolroom","Electroforming","Shipping","Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var currentIndex = 0;
		var action = "select";
		var condition = "BATCH_NUMBER";
		var value = batchNumber;
		var jobs = [];
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var jobList = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				
				for (let job of jobList) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				
				switch(currentIndex) {
					case 0:
					case 4:
						jobList.forEach((item, index, array) => {
							jobs.push({
								BATCH_NUMBER: item['BATCH_NUMBER'],
								TOOL: item['TOOL_IN'],
								PROCESS: "Mastering",
								DEPARTMENT: "Mastering",
								WO_NUMBER: item['WO_NUMBER'],
								TOOL_OUT: item['TOOL_OUT']
							});
						});
						break;
					case 1:
					case 5:
						jobList.forEach((item, index, array) => {
							jobs.push({
								BATCH_NUMBER: item['BATCH_NUMBER'],
								TOOL: item['TOOL_IN'],
								PROCESS: item['PROCESS'],
								DEPARTMENT: "Toolroom",
								WO_NUMBER: item['WO_NUMBER'],
								TOOL_OUT: item['TOOL_OUT']
							});
						});
						break;
					case 2:
					case 6:
						jobList.forEach((item, index, array) => {
							jobs.push({
								BATCH_NUMBER: item['BATCH_NUMBER'],
								TOOL: item['TOOL_IN'],
								PROCESS: item['PROCESS'],
								DEPARTMENT: "Electroforming",
								WO_NUMBER: item['WO_NUMBER'],
								TOOL_OUT: item['TOOL_OUT']
							});
						});
						break;
					case 3:
					case 7:
						jobList.forEach((item, index, array) => {
							jobs.push({
								BATCH_NUMBER: item['BATCH_NUMBER'],
								TOOL: item['TOOL'],
								PROCESS: "Shipping",
								DEPARTMENT: "Shipping",
								WO_NUMBER: item['WO_NUMBER'],
								TOOL_OUT: item['TOOL'],
								BELT_NUMBER: item['BELT_NUMBER']
							});
						});
						break;
					default:
						alert("wat");
				}
				currentIndex++;
			}
		}
		
		tables.forEach((item, index, array) => {
			conn.open("GET","/db_query/sql2.php?table="+item+"&action="+action+"&condition="+condition+"&value="+value,false);
			conn.send();
		});
		
		return jobs;
	}
	
	/**
	  *	@desc	set close button and window onclick properties to close modal
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
	  *	@desc	remove batch and all scheduled jobs from list
	  *	@param	none
	  *	@return	none
	*/
	function abortBatch() {
		var batchNumber = document.getElementById(selectedRow).children[0].innerHTML;
		var jobsToAbort = getJobs(batchNumber);
		var modal = document.getElementById('modal');
		document.getElementById("modal").style.display = "block";
		var modalContent = document.getElementById("modal-content");
		modalContent.innerHTML = `<span id="close">&times;</span>
								  <span>Reason for aborting batch:</span><br>
								  <select id="reason-select">
								  <?php foreach($reasons as $reason) { ?>
								  <option value="<?=$reason['REASON']?>"><?=$reason['REASON']?></option>
								  <?php } ?>
								  </select><br>
								  <input onblur="this.value = this.value.toUpperCase();" type="text" id="abort-operator-input" <?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?> value="<?=$_SESSION['initials']?>" <?php } ?>>
								  <button id="submit-abort">Submit</button>`;
								  
		document.getElementById("submit-abort").addEventListener('click', function() {
			var abortCounter = 0;
			
			jobsToAbort.forEach((item, index, array) => {
				var conn = new XMLHttpRequest();
				var table = "Abort_History";
				var action = "insert";
				item.REASON = document.getElementById("reason-select").value;
				item.OPERATOR = document.getElementById("abort-operator-input").value;
				item.DATE = formatDate(new Date());
				delete item.BELT_NUMBER;
				
				var query = "";
				
				Object.keys(item).forEach((item1, index1, array1) => {
					if (item1 != "TOOL_OUT") {
						query += `&${item1}=${item[item1]}`;
					}
				});
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Insert succeeded")) {
							abortCounter++;
							checkAbortSuccess(abortCounter, jobsToAbort.length, jobsToAbort);
						} else {
							alert("Could not delete jobs. Contact support to correct. " + conn.responseText);
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
				conn.send();
			});
		});
		
		closeForm();
	}
	
	/**
	  *	@desc	check if all jobs abort, then call deleteTools and deleteJobs
	  *	@param	int counter - jobs aborted, int jobCount - jobs required to abort, Array jobs - aborted jobs array to pass to deleteTools
	  *	@return	none
	*/
	function checkAbortSuccess(counter, jobCount, jobs) {
		if (counter == jobCount) {
			if (deleteTools(jobs)) {
				deleteJobs();
			} else {
				return;
			}
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	remove tools from tool tree
	  *	@param	Array jobs - aborted jobs to get tool name from
	  *	@return	true on success, false on failure
	*/
	function deleteTools(jobs) {
		var counter = 0;
		
		jobs.forEach((item, index, array) => {
			var conn = new XMLHttpRequest();
			var action = "delete";
			var table = "Tool_Tree";
			var query = "";
			switch(item["PROCESS"]) {
				case "ELECTROFORMING":
				case "NICKEL FLASHING":
				case "CONVERT":
				case "PARQUET":
				case "FRAMING":
					query = "&TOOL="+item["TOOL_OUT"].replace(/[+]/g, "%2B");
					break;
				default:
					counter++;
			}
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Deletion succeeded")) {
						counter++;
					}
				}
			}
			
			if (query != "") {
				conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
				conn.send();
			}
		});
		
		if (counter >= jobs.length) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	  *	@desc	delete old job data from schedule
	  *	@param	none
	  *	@return	none
	*/
	function deleteJobs() {
		var deleteCounter = 0;
		var batchNumber = document.getElementById(selectedRow).children[0].innerHTML;
		var jobsToDelete = getJobs(batchNumber);
		
		if (jobsToDelete.length>0) {
			jobsToDelete.forEach((item, index, array) => {
				var conn = new XMLHttpRequest();
				var table = item['DEPARTMENT'];
				var action = "delete";
				var query = "&WO_NUMBER="+item['WO_NUMBER'];
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Deletion succeeded")) {
							deleteCounter++;
							checkDeleteSuccess(deleteCounter, jobsToDelete.length);
						} else {
							alert("Could not delete jobs. Contact support to correct. " + conn.responseText);
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn.send();
			});
		} else {
			alert("Job aborted");
			window.location.replace("scheduling.php");
		}
	}
	
	/**
	  *	@desc	check if all old jobs deleted
	  *	@param	int counter - jobs deleted, int jobs - jobs required to delete
	  *	@return	none
	*/
	function checkDeleteSuccess(counter, jobs) {
		if (counter == jobs) {
			alert("Job aborted.");
			window.location.replace("scheduling.php");
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	creates and submits a form to edit selected batch
	  *	@param	none
	  *	@return	none
	*/
	function editBatch() {
		if (selectedRow != 0) {
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="scheduling/editbatch.php" method="POST" style="display: none;" id="edit-form"><input type="text" name="batch" value="${document.getElementById(selectedRow).children[0].innerHTML}"><input type="submit"></form>`;
			document.getElementById("edit-form").submit();
		} else {
			alert("Select a batch first");
		}
	}		
	
	/**
	  *	@desc	turn date object into formatted string
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
	  *	@desc	sorts batches array by selected option
	  *	@param	string value - option to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_scheduling_order",document.getElementById("order-type").value);
		setCookie("sort_scheduling_filter",document.getElementById("filter-type").value);
		setCookie("sort_scheduling_filter_value",document.getElementById("filter-input").value);
		
		batches.sort(function(a, b) {
			
			switch(value) {
				case "batch-number":
					if (parseInt(a['BATCH_NUMBER']) < parseInt(b['BATCH_NUMBER'])) {
						return -1;
					} else if (parseInt(a['BATCH_NUMBER']) > parseInt(b['BATCH_NUMBER'])) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "department":
					name = 'CURRENT_DEPARTMENT';
					break;
				case "next-process":
					name = 'NEXT_PROCESS';
					break;
				case "process":
					name = 'CURRENT_PROCESS';
					break;
				case "scheduler":
					name = 'OPERATOR';
					break;
				default:
					return 0;
			}
			
			if (a[name].toUpperCase() < b[name].toUpperCase()) {
				return -1;
			} else if (a[name].toUpperCase() > b[name].toUpperCase()) {
				return 1;
			} else {
				return 0;
			}
			
		});
		
		fillSort();
	}
	
	/**
	  *	@desc	fills in newly sorted array
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[1];
		var html = "";
		var keyword = document.getElementById("filter-input").value.toUpperCase();
		var value = document.getElementById("filter-type").value;
		
		batches.forEach((item, index, array) => {
			if (isAllowed(keyword, value, item)) {
				html += `<tr id="${item['ID']}" onclick="selectRow(this)">
										<td class="col1">${item['BATCH_NUMBER']}</td>
										<td class="col2">${item['CURRENT_DEPARTMENT']}</td>
										<td class="col3">${item['CURRENT_PROCESS']}</td>
										<td class="col4">${item['NEXT_PROCESS']}</td>
										<td class="col5">${item['OPERATOR']}</td>
									</tr>`;
			}
		});
		
		tbody.innerHTML = html;
	}
	
	/**
	  *	@desc	determines if row matches filter constraints
	  *	@param	string keyword - keyword to filter by, string value - column to filter by, array row - row to match
	  *	@return	true if match, false otherwise
	*/
	function isAllowed(keyword, value, row) {
		var valid = false;
		
		switch(value) {
			case "batch-number":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,"."),"g").test(row['BATCH_NUMBER'].toUpperCase()) && keyword.length == row['BATCH_NUMBER'].length) {
						valid = true;
					}
				} else {
					if (row['BATCH_NUMBER'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "department":
				if (row['CURRENT_DEPARTMENT'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			case "next-process":
				if (row['NEXT_PROCESS'].toUpperCase() == keyword) {
					valid = true;
				}
				break;
			case "process":
				if (row['CURRENT_PROCESS'].toUpperCase() == keyword) {
					valid = true;
				}
				break;
			case "scheduler":
				if (row['OPERATOR'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			default:
				valid = true;
		}
		
		return valid;
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
									<option value="none">&lt;None&gt;</option>
									<option value="batch-number">Batch Number</option>
									<option value="department">Department</option>
									<option value="next-process">Next Process</option>
									<option value="process">Process</option>
									<option value="scheduler">Scheduler</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none">&lt;None&gt;</option>
									<option value="batch-number">Batch Number</option>
									<option value="department">Department</option>
									<option value="next-process">Next Process</option>
									<option value="process">Process</option>
									<option value="scheduler">Scheduler</option>
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
		
		if (checkCookie("sort_scheduling_order")) {
			document.getElementById("order-type").value = getCookie("sort_scheduling_order");
		}
		
		if (checkCookie("sort_scheduling_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_scheduling_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_scheduling_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_scheduling_filter_value");
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
		
		setCookie("sort_expanded");
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
		if (select.value == "process" || select.value == "next-process") {
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
			input.onkeydown = function(e) {
				if (e.key == "Enter") {
					input.parentNode.nextElementSibling.click();
				}
			}
			document.getElementById("filter-container").appendChild(input);
		}
	}
	
	/**
	  *	@desc	search batches by tool
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html = "<span id=\"close\">&times;</span>";
		html += "<input type=\"text\" id=\"search-input\"><button id=\"search-button\">Search</button>";
		modalContent.innerHTML = html;
		modal.style.display = "block";
		
		document.getElementById("search-input").focus();
		
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search-button").click();
			}
		}
		
		document.getElementById("search-button").onclick = function() {
			var searchText = document.getElementById("search-input").value;
			for (var i=0;i<batches.length;i++) {
				var jobs = batches[i]['TOOLS'];
				for (var j=0;j<jobs.length;j++) {
					if (jobs[j].includes(searchText)) {
						selectedRow = batches[i]['ID'];
						document.getElementById("close").click();
						document.getElementById(selectedRow).click();
						document.getElementById(selectedRow).scrollIntoView();
						return;
					}
				}
			}
		};
		
		closeForm();
	}
	
	/**
	  *	@desc	place batch on hold
	  *	@param	String label - text on button
	  *	@return	none
	*/
	function placeOnHold(label) {
		var conn = new XMLHttpRequest();
		var tables = ['Batches','Mastering','Mastering_Queue','Toolroom','Toolroom_Queue',
					  'Electroforming','Electroforming_Queue','Shipping','Shipping_Queue'];
		var value;
		var action = "update";
		var successes = 0, attempts = 0;
		
		if (label == "Hold On") {
			value = "TRUE";
		} else {
			value = "FALSE";
		}
		
		for (var i=0;i<tables.length;i++) {
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					attempts++;
					if (this.responseText.includes("Data updated")) {
						successes++;
						if (successes >= tables.length) {
							for (var j=0;j<batches.length;j++) {
								if (batches[j]['ID'] == selectedRow) {
									batches[j]['ON_HOLD'] = value;
								}
							}
							document.getElementById(selectedRow).click();
						} else if (attempts >= tables.length) {
							alert((value == "TRUE" ? "Could not place batch on hold. " : "Could not take batch off hold. ") + "Contact IT Support to correct.");
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&ON_HOLD="+value+"&condition=BATCH_NUMBER&value="+document.getElementById(selectedRow).children[0].innerHTML+(tables[i] != "Batches" ? "&condition2="+((tables[i] == "Shipping" || tables[i] == "Shipping_Queue" ? "SELECT_OPERATOR" : "OPERATOR_IN")+"&value2=null") : ""),false);
			conn.send();
		}
	}
	
	/**
	  *	@desc	set colors to purple if batch is on hold
	  *	@param	none
	  *	@return	none
	*/
	function setColors() {
		var trs = document.getElementsByClassName("main")[0].children[1].children;
		for (var i=0;i<trs.length;i++) {
			for (var j=0;j<batches.length;j++) {
				if (trs[i].id == batches[j]['ID']) {
					setColor(trs[i],j);
				}
			}
		}
	}
	
	/**
	  *	@desc	set color of row
	  *	@param	DOM Object tr - row to set, int j - index of batch
	  *	@return	none
	*/
	function setColor(tr, index) {
		tr.style.color = "black";
		if (batches[index]['ON_HOLD'] == "TRUE") {
			tr.style.backgroundColor = "#e0e";
		} else {
			tr.style.backgroundColor = "white";
		}
	}
</script>
<html>
	<head>
		<title>Scheduling</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="checkSortBox(); setColors();">
		<div class="container">
			<div class="outer">
				<div class="inner">
					<div class="top-left">
						<span class="date">Target Date<input type="text" readonly id="target-input"></span><br>
						<table class="main">
							<thead>
								<tr>
									<th class="col1">Batch #</th>
									<th class="col2">Department</th>
									<th class="col3">Current</th>
									<th class="col4">Next</th>
									<th class="col5">Scheduler</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($batches as $batch) { ?>
								<tr id="<?= $batch['ID'] ?>" onclick="selectRow(this)">
									<td class="col1"><?= $batch['BATCH_NUMBER'] ?></td>
									<td class="col2"><?= $batch['CURRENT_DEPARTMENT'] ?></td>
									<td class="col3"><?= $batch['CURRENT_PROCESS'] ?></td>
									<td class="col4"><?= $batch['NEXT_PROCESS'] ?></td>
									<td class="col5"><?= $batch['OPERATOR'] ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="controls">
						<button onclick="search()">Search</button>
						<a id="add" href="scheduling/addbatch.php">Add</a>
						<button onclick="editBatch()" id="edit">Edit</button>
						<button onclick="abortBatch()" id="delete">Delete</button>
						<button onclick="placeOnHold(this.innerHTML)" id="hold-button">Hold On</button>
						<a id="hold-list" href="scheduling/holdlist.php">Hold List</a>
						<a id="back" href="home.php">Back</a>
					</div>
					<div class="details">
						<table class="secondary">
							<thead>
								<tr>
									<th class="col1">Tools</th>
									<th class="col2">Belt#</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>
					<div class="special">
						<span>Special Instructions</span><br>
						<textarea id="special-textarea" cols="33" rows="7" readonly></textarea>
					</div>
					<div class="links">
						<a style="margin-right: 11px;" href="scheduling/mastering.php">Mastering</a>
						<a style="margin-right: 11px;" href="scheduling/toolroom.php">Tool Room</a>
						<a style="margin-right: 11px;" href="scheduling/electroforming.php">Electroforming</a>
						<a style="margin-right: 11px;" href="scheduling/shipping.php">Shipping</a>
						<a href="scheduling/invoicing.php">Invoicing</a>
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