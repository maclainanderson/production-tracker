<!DOCTYPE html>
<?php
/**
  *	@desc main mastering scheduling page
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
	
	//list of jobs
	$jobs = array();
	$incomingJobs = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering_Queue;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$incomingJobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, REASON FROM Abort_Work_Order ORDER BY REASON ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$reasons[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
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
	var selectedRow = 0;
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
	
	//make up/down arrow keys move table up/down
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
		var trs = tr.parentNode.children;
		for(var i=0;i<trs.length;i++) {
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
	  *	@desc	grab details of job
	  *	@param	int id - array index for job
	  *	@return	none
	*/
	function getDetails(id) {
		if (!incoming) {
			for(var i=0;i<jobs.length;i++) {
				if (jobs[i]['ID'] == id) {
					document.getElementById("batch-input").value = jobs[i]['BATCH_NUMBER'];
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
				}
			}
		} else {
			for(var i=0;i<incomingJobs.length;i++) {
				if (incomingJobs[i]['ID'] == id) {
					document.getElementById("batch-input").value = incomingJobs[i]['BATCH_NUMBER'];
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
					if (incomingJobs[i]['OPERATOR_OUT'] == "") {
						document.getElementById("hold-button").disabled = false;
						if (incomingJobs[i]['ON_HOLD'] == "TRUE") {
							document.getElementById("hold-button").innerHTML = "Take Off Hold";
						} else {
							document.getElementById("hold-button").innerHTML = "Place On Hold";
						}
					} else {
						document.getElementById("hold-button").disabled = true;
					}
				}
			}
		}
		document.getElementById("edit-button").disabled = false;
		document.getElementById("abort-button").disabled = false;
		document.getElementById("retrieve-button").disabled = false;
	}
	
	/**
	  *	@desc	fetch status of TOOL_OUT
	  *	@param	string master - TOOL_OUT name to search for
	  *	@return	none
	*/
	function getMasterStatus(master) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "TOOL";
		
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
								if (j==38) {
									response[i][j] = response[i][j].split(")")[0];
								}
								response[i][j] = response[i][j].trim();
							}
						}
					}
					document.getElementById("location-input").value = response[0][10];
					document.getElementById("drawer-input").value = response[0][11];
					document.getElementById("status-input").value = response[0][5];
				} else {
					document.getElementById("location-input").value = "";
					document.getElementById("drawer-input").value = "";
					document.getElementById("status-input").value = "";
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+master,true);
		conn.send();
	}
	
	/**
	  *	@desc	create/submit form to edit page
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		if (document.getElementById("operator-in-input").value == "" && document.getElementById("operator-out-input").value == "" && selectedRow != 0) {
			var id = selectedRow;
			var batch = document.getElementById("batch-input").value;
			document.getElementsByTagName("BODY")[0].innerHTML += `<form action="editmastering.php" method="POST" style="display: none;" id="edit-form"><input type="text" name="id" value="${id}"><input type="text" name="batch" value="${batch}"><input type="submit"></form>`;
			document.getElementById("edit-form").submit();
		} else if (selectedRow == 0) {
			alert("Select a job first");
		} else {
			alert("Job cannot be edited once started");
		}
	}
	
	/**
	  *	@desc	move job to abort history
	  *	@param	none
	  *	@return	none
	*/
	function abort() {
		var job;
		var toolToDelete;
		if (!incoming) {
			jobs.forEach((item, index, array) => {
				if (item['ID'] == selectedRow) {
					job = {
						BATCH_NUMBER: item['BATCH_NUMBER'],
						TOOL: item['TOOL_IN'],
						PROCESS: "MASTERING",
						DEPARTMENT: "Mastering",
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
						PROCESS: "MASTERING",
						DEPARTMENT: "Mastering",
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
						if (deleteTool(toolToDelete)) {
							deleteJob(job.BATCH_NUMBER, job.WO_NUMBER);
						} else {
							alert("Job deleted, but tool name still reserved. Contact IT Support to correct. " + conn.responseText);
						}
					} else {
						alert("Could not delete jobs. Contact support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
			conn.send();
		});
		
		closeForm();
	}
	
	/**
	  *	@desc	remove reserved tool name
	  *	@param	string tool - tool to delete
	  *	@return	return true on success, false on failure
	*/
	function deleteTool(tool) {
		if (!hasChildren(tool)) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "delete";
		var query = "&TOOL="+tool;
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
	  *	@desc	delete job from current work
	  *	@param	int batch - batch to be passed to deleteBatch, int wo - WO_NUMBER to delete from table
	  *	@return	none
	*/
	function deleteJob(batch, wo) {
		var conn = new XMLHttpRequest();
		if (!incoming) {
			var table = "Mastering";
		} else {
			var table = "Mastering_Queue";
		}
		var action = "delete";
		var query = "&WO_NUMBER="+wo;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					if (getJobs(batch) >= 1) {
						if (!incoming) {
							addNextJob();
						} else {
							alert("Job aborted");
							window.location.replace("mastering.php");
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
	  *	@desc	delete batch if job is aborted
	  *	@param	int batch - batch number to delete
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
						window.location.replace("mastering.php");
					} else {
						addNextJob();
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	move next job in batch from queue to current schedule
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
			alert("Job completed");
			window.location.replace("mastering.php");
		}
	}
	
	/**
	  *	@desc	get next job from queue
	  *	@param	none
	  *	@return	array containing job data
	*/
	function findNextJob() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var condition = "BATCH_NUMBER";
		var value = job.BATCH_NUMBER;
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
	  *	@desc	remove next job from queue
	  *	@param	int woNumber - identifying info to find job, string table - queue table to delete from
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
					window.location.replace("mastering.php");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WO_NUMBER="+woNumber,false);
		conn.send();
	}
	
	/**
	  *	@desc	fetch all jobs for given batch
	  *	@param	int batchNumber - identifying number to search for
	  *	@return	array containing all job data
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
							job[i][j] = job[i][j+j].split("[")[0].trim();
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
	  *	@desc	modify row color according to job status
	  *	@param	DOM Object tr - row to modify, int index - array index to grab status from
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
		setCookie("sort_mastering_scheduling_order",document.getElementById("order-type").value);
		setCookie("sort_mastering_scheduling_filter",document.getElementById("filter-type").value);
		setCookie("sort_mastering_scheduling_filter_value",document.getElementById("filter-input").value);
		
		if (value == "none") {
			fillSort();
			return;
		}
		
		if (!incoming) {
			jobs.sort(function(a, b) {
				var ad = new Date(a['DATE_OUT']);
				var bd = new Date(b['DATE_OUT']);
				var date = new Date();
				
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
				var ad = new Date(a['DATE_OUT']);
				var bd = new Date(b['DATE_OUT']);
				var date = new Date();
				
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
	  *	@desc	fill newly sorted job list
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[0].children[1];
		var html = "";
		if (document.getElementById("filter-type")) {
			var keyword = document.getElementById("filter-input").value;
			var value = document.getElementById("filter-type").value;
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
	  *	@desc	determine if row matches filter constraints
	  *	@param	string keyword - string to filter by, string value - column to filter on, array row - row to match
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
	  *	@desc	create/submit request to Retrieve Tool
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/design.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedRow).children[1].innerHTML}" name="design"></form>`;
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
	  *	@desc	fetch job to move to archives
	  *	@param	int id - db table ID
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
					job["SEQNUM"] = response[0][5];
					job["TARGET_DATE"] = response[0][6];
					job["TOOL_IN"] = response[0][7].replace(/[+]/g,"%2B");
					job["DATE_IN"] = response[0][8];
					job["OPERATOR_IN"] = response[0][9];
					job["STATUS_IN"] = response[0][10];
					job["TOOL_OUT"] = response[0][11].replace(/[+]/g,"%2B");
					job["DATE_OUT"] = response[0][12];
					job["OPERATOR_OUT"] = response[0][13];
					job["STATUS_OUT"] = response[0][14];
					job["SPECIAL_INSTRUCTIONS"] = response[0][15].replace(/\n/g,"%0A").replace(/[&]/g,"%26");
					job["MACHINE_NUMBER"] = response[0][16];
					job["PROGRAM_NUMBER"] = response[0][17];
					job["SIZE"] = response[0][18]
					job["CUSTOMER_TOOL_TYPE"] = response[0][19];
					job["TOOL_TYPE"] = response[0][20];
					job["COSMETIC"] = response[0][21];
					job["WORK_TYPE"] = response[0][22];
					job["COMMENT"] = response[0][23].replace(/\n/g,"%0A").replace(/[&]/g,"%26");
					job["IS_BLANK"] = response[0][24];
					job["BLANK"] = response[0][25];
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job;
	}
	
	/**
	  *	@desc	move job to archives
	  *	@param	int id - job ID to move
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Mastering_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		})
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded.")) {
					removeFromCurrentWork();
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
	  *	@param	none
	  *	@return	none
	*/
	function removeFromCurrentWork() {
		var conn = new XMLHttpRequest();
		var table = "Mastering";
		var action = "delete";
		var id = job.id;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded.")) {
					return;
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
		
		if (checkCookie("sort_mastering_scheduling_order")) {
			document.getElementById("order-type").value = getCookie("sort_mastering_scheduling_order");
		}
		
		if (checkCookie("sort_mastering_scheduling_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_mastering_scheduling_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_mastering_scheduling_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_mastering_scheduling_filter_value");
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
	
	/**
	  *	@desc	place job on hold
	  *	@param	none
	  *	@return	none
	*/
	function placeOnHold(label) {
		var conn = new XMLHttpRequest();
		var table = incoming ? "Mastering_Queue" : "Mastering";
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
		<title>Mastering Scheduling</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/mastering.css">
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
									<th class="col2">Design</th>
									<th class="col3">Pre-Cut</th>
									<th class="col4">Post-Cut</th>
									<th class="col5">Size(in)</th>
									<th class="col6">Type</th>
									<th class="col7">ReCut New</th>
									<th class="col8">Cosmetics</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($jobs as $job) { ?>
								<tr id="<?= $job['ID'] ?>" onclick="selectRow(this)">
									<td class="col1"><?= date_format($job['TARGET_DATE'],"m/d/y") ?></td>
									<td class="col2"><?= $job['TOOL_IN'] ?></td>
									<td class="col3"><?= date_format($job['DATE_IN'],"m/d/y") ?></td>
									<td class="col4"><?= date_format($job['DATE_OUT'],"m/d/y") ?></td>
									<td class="col5"><?= $job['SIZE'] ?></td>
									<td class="col6"><?= $job['TOOL_TYPE'] ?></td>
									<td class="col7"><?= $job['WORK_TYPE'] ?></td>
									<td class="col8"><?= $job['COSMETIC'] ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="left">
						<span id="batch-span">Batch<input type="text" readonly id="batch-input"></span>
						<span id="po-span">PO #<input id="po-input" type="text"></span>
						<span id="job-span">Job #<input type="text" id="job-input"></span>
						<span id="wo-span">WO #<input id="wo-input" type="text"></span><br>
						<span id="blank-span">Blank<input id="blank-input" type="text"></span><br>
						<span id="recut-span">ReCut Master<input id="recut-input" type="text"></span><br>
						<span id="operator-in-span">Operator In<input id="operator-in-input" type="text"></span><span id="operator-out-span">Out<input id="operator-out-input" type="text"></span><br>
						<span id="machine-span">Machine<input id="machine-input" type="text"></span><span id="program-span">Program #<input id="program-input" type="text"></span><br>
						<span id="master-span">Master<input id="master-input" type="text"></span><span id="created-span">Created<input id="created-input" type="text"></span><br>
						<span id="location-span">Master Location<br><input id="location-input" type="text"></span>
						<span id="drawer-span">Drawer<br><input id="drawer-input" type="text"></span>
						<span id="status-span">Status<br><input id="status-input" type="text"></span>
					</div>
					<div class="controls">
						<div class="controls-left">
							<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
							<button onclick="switchUnit(this)">Metric</button>
							<button id="retrieve-button" onclick="retrieveTool()" disabled>Design</button>
							<button id="hold-button" onclick="placeOnHold(this.innerHTML)" disabled>Place On Hold</button>
						</div>
						<div class="controls-right">
							<a href="addmastering.php">Add</a>
							<button id="edit-button" onclick="edit()" disabled>Edit</button>
							<button id="abort-button" onclick="abort()" disabled>Abort</button>
							<a href="../scheduling.php">Back</a>
						</div>
					</div>
				</div>
			</div>
			<div id="arrow" onclick="showFilters()">
		 		<div class="right-arrow">
				</div>
		 	</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>