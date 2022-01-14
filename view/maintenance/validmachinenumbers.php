<!DOCTYPE html>
<?php
/**
  *	@desc list of machine numbers and associated processes
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
	
	//list of machines
	$machines = array();
	$processes = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Valid_Machines");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$machines[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS, DEPARTMENT FROM Processes WHERE Status = 'Active' ORDER BY DEPARTMENT ASC, PROCESS ASC;");
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
	
	//set up tracking variables
	var current = 0;
	var machines = [<?php
		foreach($machines as $machine) {
			echo '{';
			foreach($machine as $key=>$value) {
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
	  *	@desc	go to first machine
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous machine
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next machine
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < machines.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last machine
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(machines.length-1);
	}
	
	/**
	  *	@desc	find item by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("dept-input").value = machines[current]['DEPARTMENT'];
		document.getElementById("proc-input").value = machines[current]['PROCESS'];
		document.getElementById("machine-input").value = machines[current]['MACHINE'];
		document.getElementById("desc-input").value = machines[current]['DESCRIPTION'];
		document.getElementById("date-input").value = machines[current]['DATE'] != " " ? machines[current]['DATE'] + " by " + machines[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep readOnly attributes and value for a new machine
	  *	@param	none
	  *	@return	none
	*/
	function newMachine() {
		document.getElementById("dept-input").value = "";
		document.getElementById("dept-input").readOnly = false;
		document.getElementById("proc-input").value = "";
		document.getElementById("proc-input").readOnly = false;
		document.getElementById("machine-input").value = "";
		document.getElementById("machine-input").readOnly = false;
		document.getElementById("desc-input").value = "";
		document.getElementById("desc-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveMachine(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
		document.getElementById("search").disabled = false;
	}
	
	/**
	  *	@desc	prep readOnly attributes for editing a machine
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("dept-input").readOnly = false;
		document.getElementById("proc-input").readOnly = false;
		document.getElementById("machine-input").readOnly = false;
		document.getElementById("desc-input").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveMachine(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
		document.getElementById("search").disabled = false;
	}
	
	/**
	  *	@desc	save machine data
	  *	@param	string s - whether adding or updating machine
	  *	@return	none
	*/
	function saveMachine(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Valid_Machines";
		var id = machines[current]['ID'];
		var dept = document.getElementById("dept-input").value;
		var process = document.getElementById("proc-input").value;
		var machine = document.getElementById("machine-input").value;
		var desc = document.getElementById("desc-input").value;
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.reload();
				} else if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(machines[current]['MACHINE'],machine);
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&MACHINE="+machine+"&DESCRIPTION="+desc+"&DEPARTMENT="+dept+"&PROCESS="+process+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
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
	  *	@param	String oldMachine - old machine number, String newMachine - new machine number
	  *	@return	none
	*/
	function modifyOldRecords(oldMachine,newMachine) {
		var conn = [];
		var tables = ['Mastering','Mastering_Queue','Mastering_History','Toolroom','Toolroom_Queue','Toolroom_History'];
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
								window.location.replace("validmachinenumbers.php");
							} else {
								alert("Not all old records updated. Contact IT support to correct.");
							}
						}
					}
				}
			}
			
			conn[i].open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&MACHINE_NUMBER="+newMachine+"&condition=MACHINE_NUMBER&value="+oldMachine,false);
			conn[i].send();
		}
	}
	
	/**
	  *	@desc	set fields to readOnly and find current item
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("dept-input").readOnly = true;
		document.getElementById("proc-input").readOnly = true;
		document.getElementById("machine-input").readOnly = true;
		document.getElementById("desc-input").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		document.getElementById("search").disabled = true;
		find(current);
	}
	
	/**
	  *	@desc	remove machine
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Valid_Machines";
		var id = machines[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Machine number deleted");
					window.location.replace("validmachinenumbers.php");
				} else {
					alert("Machine number not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	create/display machine search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Machine</th><th class="col2">Description</th><th class="col3">Department</th><th class="col4">Process</th><th class="col5">Modified</th><th class="col6">Operator</th></tr></thead><tbody>';
		
		machines.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['MACHINE']+'</td><td class="col2">'+item['DESCRIPTION']+'</td><td class="col3">'+item['DEPARTMENT']+'</td><td class="col4">'+item['PROCESS']+'</td><td class="col5">'+item['DATE']+'</td><td class="col6">'+item['OPERATOR']+'</td></tr>';
		});
		
		html += '</tbody></table>';
		modalContent.innerHTML = html;
		modalContent.style.width = "700px";
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
	
	/**
	  *	@desc	create/display list of processes to choose from
	  *	@param	none
	  *	@return none
	*/
	function popProcessList() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br><table><thead><tr><th class="col1">Department</th><th class="col2">Process</th></tr></thead><tbody>';
		<?php foreach($processes as $process) { ?>
		html += '<tr onclick="selectProcessRow(this)"><td class="col1"><?=$process['DEPARTMENT']?></td><td class="col2"><?=$process['PROCESS']?></td></tr>';
		<?php } ?>
		html += '</tbody></table>';
		modalContent.innerHTML = html;
		modalContent.style.width = "300px";
		modal.style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight process row, or confirm if already highlighted
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectProcessRow(tr) {
		if (tr.style.color == "white") {
			document.getElementById('dept-input').value = tr.children[0].innerHTML;
			document.getElementById('proc-input').value = tr.children[1].innerHTML;
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
		<title>Valid Machine Numbers</title>
		<link rel="stylesheet" type="text/css" href="/styles/validmachinenumbers.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="dept-span">Department, Process<button onclick="popProcessList()" id="search" disabled>Search</button>
					<input id="dept-input" type="text" readonly><input id="proc-input" type="text" readonly></span>
					<span id="machine-span">Machine Number<br>
					<input id="machine-input" type="text" readonly></span><br>
					<span id="desc-span">Description<br>
					<input id="desc-input" type="text" readonly></span><br>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button id="add" onclick="newMachine()">Add</button>
					<button onclick="insertFirst()">First</button>
					<button id="edit" onclick="edit()">Edit</button>
					<button onclick="goUp()">Up</button>
					<button id="delete" onclick="deleteItem()">Delete</button>
					<button onclick="goDown()">Down</button>
					<button onclick="search()">Search</button>
					<button style="margin-bottom: 4px;" onclick="insertLast()">Last</button>
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>