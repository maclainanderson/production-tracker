<!DOCTYPE html>
<?php
/**
  *	@desc list/edit/define processes
*/
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
	}
	
	//set up sql connection for loading data
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of processes
	$processes = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Processes");
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

?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//variable definitions
	var current = 0;
	var processes = [<?php
		foreach($processes as $process) {
			echo '{';
			foreach($process as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value, "m/d/y H:i");
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
	
	/**
	  *	@desc	go to first process
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous process
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next process
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < processes.length - 1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last process
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(processes.length - 1);
	}
	
	/**
	  *	@desc	find process by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("process-input").value = processes[current]['PROCESS'];
		document.getElementById("short-input").value = processes[current]['SHORT_NAME'];
		document.getElementById("dept-input").value = processes[current]['DEPARTMENT'];
		document.getElementById("time-input").value = processes[current]['DURATION'];
		document.getElementById("cap-input").value = processes[current]['MAX_TOOLS'];
		document.getElementById("sched-input").value = processes[current]['SCHEDSCR'];
		document.getElementById("in-input").value = processes[current]['DETAILS_IN'];
		document.getElementById("out-input").value = processes[current]['DETAILS_OUT'];
		document.getElementById("state-select").options.selectedIndex = processes[current]['STATUS'] == "Active" ? 0 : 1;
		document.getElementById("date-input").value = processes[current]['DATE'] != " " ? processes[current]['DATE'] + " by " + processes[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep readOnly attributes and value for a new process
	  *	@param	none
	  *	@return	none
	*/
	function newProcess() {
		document.getElementById("process-input").value = "";
		document.getElementById("process-input").readOnly = false;
		document.getElementById("short-input").value = "";
		document.getElementById("short-input").readOnly = false;
		document.getElementById("dept-input").value = "";
		document.getElementById("dept-input").readOnly = false;
		document.getElementById("time-input").value = "";
		document.getElementById("time-input").readOnly = false;
		document.getElementById("cap-input").value = "";
		document.getElementById("cap-input").readOnly = false;
		document.getElementById("sched-input").value = "";
		document.getElementById("sched-input").readOnly = false;
		document.getElementById("in-input").value = "";
		document.getElementById("in-input").readOnly = false;
		document.getElementById("out-input").value = "";
		document.getElementById("out-input").readOnly = false;
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveProcess(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes for editing a process
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("process-input").readOnly = false;
		document.getElementById("short-input").readOnly = false;
		document.getElementById("dept-input").readOnly = false;
		document.getElementById("time-input").readOnly = false;
		document.getElementById("cap-input").readOnly = false;
		document.getElementById("sched-input").readOnly = false;
		document.getElementById("in-input").readOnly = false;
		document.getElementById("out-input").readOnly = false;
		document.getElementById("state-select").disabled = false;
		document.getElementById("date-input").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveProcess(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save process data
	  *	@param	string s - whether added or updated
	  *	@return	none
	*/
	function saveProcess(s) {
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Processes";
		var id = processes[current]['ID'];
		var process = document.getElementById("process-input").value;
		var short = document.getElementById("short-input").value;
		var dept = document.getElementById("dept-input").value;
		var time = document.getElementById("time-input").value;
		var cap = document.getElementById("cap-input").value;
		var sched = document.getElementById("sched-input").value;
		var detailsIn = document.getElementById("in-input").value;
		var detailsOut = document.getElementById("out-input").value;
		var state = document.getElementById("state-select").value;
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(processes[current]['PROCESS'],process);
				} else if (conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.reload();
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&PROCESS="+process+"&DEPARTMENT="+dept+"&DURATION="+time+"&DETAILS_IN="+detailsIn+"&DETAILS_OUT="+detailsOut+"&SCHEDSCR="+sched+"&STATUS="+state+"&MAX_TOOLS="+cap+"&SHORT_NAME="+short+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
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
	  *	@desc	modifies old records to match new name
	  *	@param	String oldProcess - old process name, String newProcess - new process name
	  *	@return	none
	*/
	function modifyOldRecords(oldProcess,newProcess) {
		var conn = [];
		var tables = ['Electroforming','Electroforming_Queue','Electroforming_History','Toolroom','Toolroom_Queue','Toolroom_History','Abort_History','Comment_History','Tool_Status_History','Valid_Machines','Workflow_Processes'];
		var action = "update";
		var attempts = 0;
		var successes = 0;
		
		for (var i=0;i<tables.length;i++) {
			conn[i] = new XMLHttpRequest();
		}
		
		for (var i=0;i<conn.length;i++) {
			conn[i].onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					attempts++;
					if (this.responseText.includes("Data updated")) {
						successes++;
						if (attempts >= conn.length) {
							if (successes >= conn.length) {
								alert("Changes saved");
								window.location.replace("processdefinition.php");
							} else {
								alert("Not all old records updated. Contact IT support to correct.");
							}
						}
					}
				}
			}
			
			conn[i].open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&PROCESS="+newProcess+"&condition=PROCESS&value="+oldProcess,false);
			conn[i].send();
		}
	}
	
	/**
	  *	@desc	set fields to readOnly and find current process
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("process-input").readOnly = true;
		document.getElementById("short-input").readOnly = true;
		document.getElementById("dept-input").readOnly = true;
		document.getElementById("time-input").readOnly = true;
		document.getElementById("cap-input").readOnly = true;
		document.getElementById("sched-input").readOnly = true;
		document.getElementById("in-input").readOnly = true;
		document.getElementById("out-input").readOnly = true;
		document.getElementById("state-select").disabled = true;
		document.getElementById("date-input").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove process
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Processes";
		var id = processes[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Process deleted");
					window.location.replace("processdefinition.php");
				} else {
					alert("Process not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	create/display search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Process</th><th class="col2">Department</th><th class="col3">Duration</th><th class="col4">Details-In</th><th class="col5">Details-Out</th><th class="col6">SchedScr</th><th class="col7">Modified</th><th class="col8">Operator</th><th class="col9">State</th><th class="col10">Max Tools</th><th class="col11">Shortname</th></tr></thead><tbody>';
		
		processes.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['PROCESS']+'</td><td class="col2">'+item['DEPARTMENT']+'</td><td class="col3">'+item['DURATION']+'</td><td class="col4">'+item['DETAILS_IN']+'</td><td class="col5">'+item['DETAILS_OUT']+'</td><td class="col6">'+item['SCHEDSCR']+'</td><td class="col7">'+item['DATE']+'</td><td class="col8">'+item['OPERATOR']+'</td><td class="col9">'+item['STATUS']+'</td><td class="col10">'+item['MAX_TOOLS']+'</td><td class="col11">'+item['SHORT_NAME']+'</td></tr>';
		});
		
		html += '</tbody></table>';
		modalContent.innerHTML = html;
		document.getElementById("modal").style.display = "block";
		
		closeForm();
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
	  *	@desc	highlight search row, or confirm if already highlighted
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		if (tr.style.color == "white") {
			find(tr.id);
			document.getElementById("close").click();
		} else {
			var trs = tr.parentNode.children;
			for (var i=0;i<trs.length;i++) {
				trs[i].style.color = "black";
				trs[i].style.backgroundColor = "white";
			}
			
			tr.style.color = "white";
			tr.style.backgroundColor = "black";
		}
	}
</script>
<html>
	<head>
		<title>Process Definition</title>
		<link rel="stylesheet" type="text/css" href="/styles/processdefinition.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="process-span">Process
					<input id="process-input" type="text" readonly></span><br>
					<span id="short-span">Short Name
					<input id="short-input" type="text" readonly></span><br>
					<span id="dept-span">Department
					<input id="dept-input" type="text" readonly></span><br>
					<span id="time-span">Duration
					<input id="time-input" type="text" readonly>(days)</span><br>
					<span id="cap-span">Capacity
					<input id="cap-input" type="text" readonly>(tools)</span><br>
					<span><strong>Screen Definition</strong></span><br>
					<span id="sched-span">Schedule
					<input id="sched-input" type="text" readonly></span><br>
					<span id="in-span">Details In
					<input id="in-input" type="text" readonly></span><br>
					<span id="out-span">Details Out
					<input id="out-input" type="text" readonly></span><br>
					<span id="state-span">State
					<select id="state-select" disabled>
						<option value="Active">Active</option>
						<option value="Inactive">Inactive</option>
					</select></span><br>
					<span id="date-span">Last Modified
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button id="add" onclick="newProcess()">Add</button>
					<button onclick="insertFirst()">First</button>
					<button id="edit" onclick="edit()">Edit</button>
					<button onclick="goUp()">Up</button>
					<button id="delete" onclick="deleteItem()">Delete</button>
					<button onclick="goDown()">Down</button>
					<button onclick="search()">Search</button>
					<button onclick="insertLast()">Last</button>
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>