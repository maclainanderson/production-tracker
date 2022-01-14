<!DOCTYPE html>
<?php
/**
  *	@desc list of valid tanks
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
	
	//list of tanks
	$tanks = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Valid_Tanks");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tanks[] = $row;
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
	var tanks = [<?php
		foreach($tanks as $tank) {
			echo '{';
			foreach($tank as $key=>$value) {
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
	  *	@desc	go to first tank
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous tank
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next tank
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < tanks.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last tank
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(tanks.length-1);
	}
	
	/**
	  *	@desc	find tank by index
	  *	@param	int i - array index of tank
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("number-input").value = tanks[current]['TANK'];
		document.getElementById("stations-input").value = tanks[current]['STATIONS'];
		document.getElementById("type-select").options.selectedIndex = tanks[current]['TYPE'] == "Rotary" ? 0 : 1;
		document.getElementById("maintenance-input").value = tanks[current]['MAINTENANCE_DATE'];
		document.getElementById("date-input").value = tanks[current]['DATE'] != " " ? tanks[current]['DATE'] + " by " + tanks[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep readOnly attributes and values for adding a new tank
	  *	@param	none
	  *	@return	none
	*/
	function newTank() {
		document.getElementById("number-input").value = "";
		document.getElementById("number-input").readOnly = false;
		document.getElementById("stations-input").value = "";
		document.getElementById("stations-input").readOnly = false;
		document.getElementById("type-select").disabled = false;
		document.getElementById("maintenance-input").value = "";
		document.getElementById("maintenance-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveTank(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes for editing a tank
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("number-input").readOnly = false;
		document.getElementById("stations-input").readOnly = false;
		document.getElementById("type-select").disabled = false;
		document.getElementById("maintenance-input").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveTank(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save tank data
	  *	@param	string s - whether adding or updating
	  *	@return	none
	*/
	function saveTank(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Valid_Tanks";
		var id = tanks[current]['ID'];
		var tank = document.getElementById("number-input").value;
		var stations = document.getElementById("stations-input").value;
		var type = document.getElementById("type-select").value;
		var maintenanceDate = document.getElementById("maintenance-input").value;
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.reload();
				} else if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(tanks[current]['TANK'],tank);
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&TANK="+tank+"&STATIONS="+stations+"&TYPE="+type+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&MAINTENANCE_DATE="+maintenanceDate+(action == "update" ? "&condition=id&value="+id : ""), true);
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
	  *	@param	String oldTank - old tank number, String newTank - new tank number
	  *	@return	none
	*/
	function modifyOldRecords(oldTank,newTank) {
		var conn = [];
		var tables = ['Electroforming','Electroforming_Queue','Electroforming_History','Tank_Stress'];
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
								window.location.replace("validtankdefinitions.php");
							} else {
								alert("Not all old records updated. Contact IT support to correct.");
							}
						}
					}
				}
			}
			
			conn[i].open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&TANK="+newTank+"&condition=TANK&value="+oldTank,false);
			conn[i].send();
		}
	}
	
	/**
	  *	@desc	set fields to readOnly and find current tank
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("number-input").readOnly = true;
		document.getElementById("stations-input").readOnly = true;
		document.getElementById("type-select").disabled = true;
		document.getElementById("maintenance-input").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove tank
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Valid_Tanks";
		var id = tanks[current]['ID'];
		var query = "DELETE FROM Valid_Tanks WHERE ID = " + id + ";";
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Tank definition deleted");
					window.location.replace("validtankdefinitions.php");
				} else {
					alert("Tank definition not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	create/display tank list to choose from
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Tank</th><th class="col2">Stations</th><th class="col3">Type</th><th class="col4">Modified</th><th class="col5">Operator</th></tr></thead><tbody>';
		
		tanks.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['TANK']+'</td><td class="col2">'+item['STATIONS']+'</td><td class="col3">'+item['TYPE']+'</td><td class="col4">'+item['DATE']+'</td><td class="col5">'+item['OPERATOR']+'</td></tr>';
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
</script>
<html>
	<head>
		<title>Valid Tank Definitions</title>
		<link rel="stylesheet" type="text/css" href="/styles/validtankdefinitions.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="number-span">Tank Number<br>
					<input id="number-input" type="text" readonly></span><br>
					<span id="type-span">Tank Type<br>
					<select id="type-select" disabled>
						<option value="Rotary">Rotary</option>
						<option value="Stationary">Stationary</option>
					</select></span>
					<span id="stations-span">Number of Stations<br>
					<input id="stations-input" type="text" readonly></span><br>
					<span id="maintenance-span">Last Maintenance Date<br>
					<input id="maintenance-input" type="text" onkeydown="fixDate(this)" readonly></span><br>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button id="add" onclick="newTank()">Add</button>
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