<!DOCTYPE html>
<?php
/**
  *	@desc workflow definitions
  *			workflows are predefined groups of processes
*/
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	//redirect if not logged in
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
	
	//list of work flows, processes, and processes assigned to workflows
	$workflows = array();
	$processes = array();
	$workflowProcesses = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Workflows");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$workflows[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		echo "Error: could not connect to database.";
		var_dump(sqlsrv_errors());
	}
	
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
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Workflow_Processes ORDER BY SEQNUM");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$workflowProcesses[] = $row;
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
	
	//set up tracking variables
	var current = 0;
	var selectedRow = 0;
	var workflows = [<?php
		foreach($workflows as $workflow) {
			echo '{';
			foreach($workflow as $key=>$value) {
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
	
	var processes = [<?php
		foreach($processes as $process) {
			echo '{';
			foreach($process as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value, 'm/d/y');
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
	
	var workflowProcesses = [<?php
		foreach($workflowProcesses as $workflowProcesse) {
			echo '{';
			foreach($workflowProcesse as $key=>$value) {
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
	  *	@desc	find first workflow
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add-workflow").disabled = false;
	}
	
	/**
	  *	@desc	go to previous workflow
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next workflow
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < workflows.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last workflow
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(workflows.length-1);
	}
	
	/**
	  *	@desc	find item by ID
	  *	@param	int i - DB ID of workflow to search for
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("workflow-input").value = workflows[current]['WORKFLOW'];
		var tbody = document.getElementById("tbody");
		tbody.innerHTML = "";
		for(var i=0;i<workflowProcesses.length;i++) {
			if (workflowProcesses[i]['WORKFLOW'] == workflows[current]['WORKFLOW']) {
				tbody.innerHTML += "<tr id=\"" + workflowProcesses[i]['SEQNUM'] + "\" onclick=\"selectRow(this)\"><td class=\"col1\">" + workflowProcesses[i]['PROCESS'] + "</td><td class=\"col2\">" + workflowProcesses[i]['SEQNUM'] + "</td><td class=\"col3\">" + workflowProcesses[i]['HOLD'] + "</td></tr>";
			}
		}
		document.getElementById("date-input").value = workflows[current]['DATE'] != " " ? workflows[current]['DATE'] + " by " + workflows[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	highlight process row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		selectedRow = parseInt(tr.id);
		var trs = document.getElementById("tbody").children;
		for (var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
	}
	
	/**
	  *	@desc	prep readOnly attributes and value for new workflow definition
	  *	@param	none
	  *	@return	none
	*/
	function addWorkflow() {
		document.getElementById("workflow-input").value = "";
		document.getElementById("workflow-input").readOnly = false;
		document.getElementById("tbody").innerHTML = "";
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("add-workflow").disabled = true;
		document.getElementById("edit-workflow").innerHTML = "Save";
		document.getElementById("edit-workflow").setAttribute('onclick','saveWorkflow(\'add\')');
		document.getElementById("delete-workflow").innerHTML = "Cancel";
		document.getElementById("delete-workflow").setAttribute('onclick','cancel()');
		document.getElementById("add-process").disabled = false;
		document.getElementById("insert").disabled = false;
		document.getElementById("delete-process").disabled = false;
		document.getElementById("hold").disabled = false;
	}
	
	/**
	  *	@desc	prep readOnly attributes for editing a workflow
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("workflow-input").readOnly = false;
		document.getElementById("add-workflow").disabled = true;
		document.getElementById("edit-workflow").innerHTML = "Save";
		document.getElementById("edit-workflow").setAttribute('onclick','saveWorkflow(\'edit\')');
		document.getElementById("delete-workflow").innerHTML = "Cancel";
		document.getElementById("delete-workflow").setAttribute('onclick','cancel()');
		document.getElementById("add-process").disabled = false;
		document.getElementById("insert").disabled = false;
		document.getElementById("delete-process").disabled = false;
		document.getElementById("hold").disabled = false;
	}
	
	/**
	  *	@desc	create/display process list
	  *	@param	string s - whether row is added to bottom, or inserted above highlighted row
	  *	@return	none
	*/
	function addProcess(s) {
		if (s == "selected" && selectedRow == 0) {
			alert("Error: no process selected");
		} else {
			var modal = document.getElementById("modal");
			var modalContent = document.getElementById("modal-content");
			modal.style.display = "block";
			var html = "<span class=\"close\" id=\"close\">&times;</span><table class=\"process-modal\"><thead><tr><th class=\"col1\">Process</th><th class=\"col2\">Department</th></tr></thead><tbody>";
			for (var i=0;i<processes.length;i++) {
				html += "<tr onclick=\"insertProcess('" + s + "','" + processes[i]['ID'] + "')\"><td class=\"col1\">" + processes[i]['PROCESS'] + "</td><td class=\"col2\">" + processes[i]['DEPARTMENT'] + "</td></tr>";
			}
			html += "</tbody></table>";
			
			modalContent.innerHTML = html;
			
			closeForm();
		}
	}
	
	/**
	  *	@desc	add process to workflow
	  *	@param	string s - process is inserted or appended to bottom, int id - DB ID of process
	  *	@return	none
	*/
	function insertProcess(s, id) {
		document.getElementById("close").click();
		var tbody = document.getElementById("tbody");
		var trs = tbody.children;
		if (s == "bottom") {
			for (var i=0;i<processes.length;i++) {
				if (processes[i]['ID'] == id) {
					tbody.innerHTML += "<tr onclick=\"selectRow(this)\" id=\""+(trs.length+1)+"\"><td class=\"col1\">" + processes[i]['PROCESS'] + "</td><td class=\"col2\">" + (trs.length+1) + "</td><td class=\"col3\">NO</td></tr>";
				}
			}
		} else if (s == "selected") {
			var tr = document.getElementById(selectedRow);
			for (var i=0;i<processes.length;i++) {
				if (processes[i]['ID'] == id) {
					tbody.innerHTML += "<tr id=\"new\" onclick=\"selectRow(this)\"><td class=\"col1\">" + processes[i]['PROCESS'] + "</td><td class=\"col2\">" + (selectedRow) + "</td><td class=\"col3\">NO</td></tr>";
				}
			}
			for (var i=0;i<trs.length;i++) {
				if (parseInt(trs[i].id) >= selectedRow) {
					trs[i].children[1].innerHTML = parseInt(trs[i].children[1].innerHTML) + 1;
					trs[i].id = parseInt(trs[i].id)+1;
				}
			}
			document.getElementById("new").id=selectedRow;
			sortTable();
		}
	}
	
	/**
	  *	@desc	remove process from workflow
	  *	@param	none
	  *	@return	none
	*/
	function deleteProcess() {
		if (selectedRow == 0) {
			alert("Error: no process selected");
		} else {
			var tbody = document.getElementById("tbody");
			var trs = tbody.children;
			var tr = document.getElementById(selectedRow);
			tbody.removeChild(tr);
			for (var i=0;i<trs.length;i++) {
				if (trs[i].id > selectedRow) {
					trs[i].id = parseInt(trs[i].id) - 1;
					trs[i].children[1].innerHTML = parseInt(trs[i].children[1].innerHTML) - 1;
				}
			}
		}
	}
	
	/**
	  *	@desc	place process on hold
	  *	@param	none
	  *	@return	none
	*/
	function holdProcess() {
		if (selectedRow == 0) {
			alert("Error: no process selected");
		} else {
			var tr = document.getElementById(selectedRow);
			if (tr.children[2].innerHTML == "NO") {
				tr.children[2].innerHTML = "YES";
			} else {
				tr.children[2].innerHTML = "NO";
			}
		}
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
	  *	@desc	adjust table by sequence number
	  *	@param	none
	  *	@return	none
	*/
	function sortTable() {
		var table, rows, switching, i, x, y, shouldSwitch;
		table = document.getElementById("tbody");
		switching = true;
		/* Make a loop that will continue until
		no switching has been done: */
		while (switching) {
			// Start by saying: no switching is done:
			switching = false;
			rows = table.getElementsByTagName("TR");
			/* Loop through all table rows (except the
			first, which contains table headers): */
			for (i = 0; i < (rows.length - 1); i++) {
				// Start by saying there should be no switching:
				shouldSwitch = false;
				/* Get the two elements you want to compare,
				one from current row and one from the next: */
				x = rows[i].getElementsByTagName("TD")[1];
				y = rows[i + 1].getElementsByTagName("TD")[1];
				// Check if the two rows should switch place:
				if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
					// If so, mark as a switch and break the loop:
					shouldSwitch = true;
					break;
		 		}
			}
			if (shouldSwitch) {
				/* If a switch has been marked, make the switch
				and mark that a switch has been done: */
				rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
				switching = true;
			}
		}
	}
	
	/**
	  *	@desc	save workflow data
	  *	@param	string s - whether added or inserted
	  *	@return	none
	*/
	function saveWorkflow(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Workflows";
		var id = workflows[current]['ID'];
		var workflow = document.getElementById("workflow-input").value;
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Insert succeeded") || conn.responseText.includes("Data updated")) {
					saveWorkflowProcesses(conn.responseText);
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WORKFLOW="+workflow+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	save processes to workflow
	  *	@param	string r - result of workflow query
	  *	@return	none
	*/
	function saveWorkflowProcesses(r){
		var tbody = document.getElementById("tbody");
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Workflow_Processes";
		var condition = "WORKFLOW";
		var value = document.getElementById("workflow-input").value;
		var orderBy = "SEQNUM ASC";
		var conn2, action2, table2, conn3, action2, table3, tds, process, seqnum, hold, d, month, date, year, hour, minute, second, date, s, oldProcs, newProcs;
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				newProcs = [];
				/*
				oldProcs = conn.responseText.split("Array");
				oldProcs.shift();
				for (var i=0;i<oldProcs.length;i++) {
					oldProcs[i] = oldProcs[i].split(">");
					oldProcs[i].shift();
					for (var j=0;j<oldProcs[i].length;j++) {
						oldProcs[i][j] = oldProcs[i][j].split("[")[0];
						if (j==oldProcs[i].length-1) {
							oldProcs[i][j] = oldProcs[i][j].split(")")[0];
						}
						oldProcs[i][j] = oldProcs[i][j].trim();
					}
				}
				*/
				oldProcs = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of jobs) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date'])) + " " + formatTime(new Date(job[x]['date']));
						}
					}
				}
				
				for (var i=0;i<tbody.children.length;i++) {
					for (var j=0;j<oldProcs.length;j++) {
						if (tbody.children[i].children[0].innerHTML == oldProcs[j]['PROCESS']) {
							newProcs.push(oldProcs.splice(j, 1)[0]);
						}
					}
				}
				
				conn3 = new XMLHttpRequest();
				if (oldProcs.length > 0) {
					for (var i=0;i<oldProcs.length;i++) {
						var action3 = "delete";
						var table3 = "Workflow_Processes";
						
						conn3.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {
								if (!conn3.responseText.includes("Deletion succeeded")) {
									alert("Changes not saved. Contact IT Support. " + conn3.responseText);
								}
							}
						}
						
						conn3.open("GET","/db_query/sql2.php?action="+action3+"&table="+table3+"&PROCESS="+oldProcs[i]['PROCESS']+"&WORKFLOW="+oldProcs[i]['WORKFLOW'], true);
						conn3.send();
					}
				}
				
				var inOrder = false;
				while(inOrder == false) {
					inOrder = true;
					for (var i=1;i<tbody.children.length;i++) {
						if (parseInt(tbody.children[i].children[1].innerHTML) - parseInt(tbody.children[i-1].children[1].innerHTML) > 1) {
							inOrder = false;
							for (var j=i;j<tbody.children.length;j++) {
								tbody.children[j].children[1].innerHTML = parseInt(tbody.children[j].children[1].innerHTML) - 1;
							}
						}
					}
				}
				
				for (var i=0;i<tbody.children.length;i++) {
					conn2 = new XMLHttpRequest();
					table2 = "Workflow_Processes";
					action2 = "insert";
					var id = "";
					tds = tbody.children[i].children;
					process = tds[0].innerHTML;
					seqnum = tds[1].innerHTML;
					hold = tds[2].innerHTML;
					date = formatDate(new Date());
				
					for (var j=0;j<newProcs.length;j++) {
						if (tds[0].innerHTML == newProcs[j]['PROCESS']) {
							action2 = "update";
							id = newProcs[j]['ID'];
						}
					}
					
					conn2.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							if (r == "Data updated successfully." || r == "Insert succeeded.") {
								if (!conn2.responseText.includes("Data updated") && !conn2.responseText.includes("Insert succeeded")) {
									alert("Changes not saved. Contact IT Support. " + conn2.responseText);
								}
							}
						}
					}
					
					conn2.open("GET","/db_query/sql2.php?action="+action2+"&table="+table2+"&PROCESS="+process+"&WORKFLOW="+value+"&SEQNUM="+seqnum+"&HOLD="+hold+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, false);
					conn2.send();
					
				}
				
				
				alert("Insert succeeded.");
				window.location.replace("workflow.php");
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value+"&ORDER_BY="+orderBy, true);
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
	  *	@desc	set fields to readOnly, find current item
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("workflow-input").readOnly = true;
		document.getElementById("add-workflow").disabled = false;
		document.getElementById("edit-workflow").innerHTML = "Edit";
		document.getElementById("edit-workflow").setAttribute('onclick','edit()');
		document.getElementById("delete-workflow").innerHTML = "Delete";
		document.getElementById("delete-workflow").setAttribute('onclick','deleteItem()');
		document.getElementById("add-process").disabled = true;
		document.getElementById("insert").disabled = true;
		document.getElementById("delete-process").disabled = true;
		document.getElementById("hold").disabled = true;
		find(current);
	}
	
	/**
	  *	@desc	remove workflow
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Workflows";
		var id = workflows[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					deleteWorkflowProcess(workflows[current]['WORKFLOW'], conn.responseText);
				} else {
					alert("Workflow not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
		
	}
	
	/**
	  *	@desc	remove processes associated with workflow
	  *	@param	string s - workflow name, string r - result of workflow query
	  *	@return	none
	*/
	function deleteWorkflowProcess(s, r) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Workflow_Processes";
		
		if (true) {
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					if (conn.responseText.includes("Deletion succeeded")) {
						alert("Workflow deleted");
						window.location.replace("workflow.php");
					} else {
						alert("Workflow not deleted. Contact IT Support. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WORKFLOW="+s, true);
			conn.send();
		} else {
			alert(r);
		}
	}
	
	/**
	  *	@desc	create/display workflow search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Workflow</th><th class="col2">Processes</th><th class="col3">Modified</th><th class="col4">Operator</th></tr></thead><tbody>';
		
		workflows.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectSearchRow(this)"><td class="col1">'+item['WORKFLOW']+'</td><td class="col2"><select>';
			workflowProcesses.forEach((item2, index2, array2) => {
				if (item['WORKFLOW'] == item2['WORKFLOW']) {
					html += '<option>'+item2['SEQNUM']+' - '+item2['PROCESS']+'</option>';
				}
			});
			html += '</select></td><td class="col3">'+item['DATE']+'</td><td class="col4">'+item['OPERATOR']+'</td></tr>';
		});
		
		html += '</tbody></table>';
		modalContent.innerHTML = html;
		modalContent.classList.add("search-modal");
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
			document.getElementById("modal-content").classList.remove("search-modal");
		}
		
		window.onclick = function(event) {
			if (event.target == modal) {
				modal.style.display = "none";
				document.getElementById("modal-content").classList.remove("search-modal");
			}
		}
	}
	
	/**
	  *	@desc	highlight row in search list, or confirm selection if already highlighted
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectSearchRow(tr) {
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
		<title>Workflow</title>
		<link rel="stylesheet" type="text/css" href="/styles/workflow.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="workflow-span">Workflow Name
					<input id="workflow-input" type="text" readonly></span>
					<table class="workflow-table" id="table">
						<thead>
							<tr>
								<th class="col1">Process</th>
								<th class="col2">Seq. Number</th>
								<th class="col3">On Hold</th>
							</tr>
						</thead>
						<tbody id="tbody">
						</tbody>
					</table>
					<span class="buttons">
						<button id="add-process" onclick="addProcess('bottom')" disabled>Add</button>
						<button id="insert" onclick="addProcess('selected')" disabled>Insert</button>
						<button id="delete-process" onclick="deleteProcess()" disabled>Delete</button>
						<button id="hold" onclick="holdProcess()" disabled>Hold</button>
					</span>
				</div>
				<div class="controls">
					<button id="add-workflow" onclick="addWorkflow()">Add</button>
					<button onclick="insertFirst()">First</button>
					<button id="edit-workflow" onclick="edit()">Edit</button>
					<button onclick="goUp()">Up</button>
					<button id="delete-workflow" onclick="deleteItem()">Delete</button>
					<button onclick="goDown()">Down</button>
					<button onclick="search()">Search</button>
					<button onclick="insertLast()">Last</button>
					<a href="../maintenance.php">Back</a>
					<span id="date-span" style="margin-top: 45%;">Last Modified
					<input id="date-input" type="text" readonly></span>
				</div>
			</div>
		</div>
		<div id="modal" class="modal">
			<div id="modal-content" class="modal-content">
			</div>
		</div>
	</body>
</html>