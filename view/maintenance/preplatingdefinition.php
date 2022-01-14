<!DOCTYPE html>
<?php
/**
  *	@desc preplating cycle definitions
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
	
	//list of cycles
	$cycles = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Pre_Plating_Cycles");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$cycles[] = $row;
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
	var cycles = [<?php
		foreach($cycles as $cycle) {
			echo '{';
			foreach($cycle as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y H:i');
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
	  *	@desc	go to first cycle
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous cycle
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next cycle
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < cycles.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last cycle
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(cycles.length-1);
	}
	
	/**
	  *	@desc	find cycle by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("plating-input").value = cycles[current]['CYCLE'];
		document.getElementById("state-select").options.selectedIndex = cycles[current]['STATUS'] == "Active" ? 0 : 1;
		document.getElementById("date-input").value = cycles[current]['DATE'] != " " ? cycles[current]['DATE'] + " by " + cycles[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep values and readOnly attributes for new cycle
	  *	@param	none
	  *	@return	none
	*/
	function newCycle() {
		document.getElementById("plating-input").value = "";
		document.getElementById("plating-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveCycle(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes to edit cycle
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("plating-input").readOnly = false;
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveCycle(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save cycle data
	  *	@param	string s - whether added or updated
	  *	@return	none
	*/
	function saveCycle(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Pre_Plating_Cycles";
		var id = cycles[current]['ID'];
		var cycle = document.getElementById("plating-input").value;
		var state = document.getElementById("state-select").value == "active" ? "Active" : "Inactive";
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Data updated") || conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.replace("preplatingdefinition.php");
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&CYCLE="+cycle+"&STATUS="+state+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&id="+id, true);
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
	  *	@desc	set fields to readOnly and find current item
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("plating-input").readOnly = true;
		document.getElementById("state-select").disabled = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove cycle
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Pre_Plating_Cycles";
		var id = cycles[current]['ID'];
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Pre-plating cycle deleted");
					window.location.replace("processdefinition.php");
				} else {
					alert("Pre-plating cycle not deleted. Contact IT Support. " + conn.responseText);
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
		html += '<table><thead><tr><th class="col1">Preplating</th><th class="col2">State</th><th class="col3">Modified</th><th class="col4">Operator</th></tr></thead><tbody>';
		
		cycles.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['CYCLE']+'</td><td class="col2">'+item['STATUS']+'</td><td class="col3">'+item['DATE']+'</td><td class="col4">'+item['OPERATOR']+'</td></tr>';
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
		<title>Pre-Plating Definition</title>
		<link rel="stylesheet" type="text/css" href="/styles/preplatingdefinition.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="plating-span">Cycle<br>
					<input id="plating-input" type="text" readonly></span><br>
					<span id="state-span">State<br>
					<select id="state-select" disabled>
						<option value="active">Active</option>
						<option value="inactive">Inactive</option>
					</select></span>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button onclick="newCycle()" id="add">Add</button>
					<button onclick="insertFirst()" id="first">First</button>
					<button onclick="edit()" id="edit">Edit</button>
					<button onclick="goUp()" id="up">Up</button>
					<button onclick="deleteItem()" id="delete">Delete</button>
					<button onclick="goDown()" id="down">Down</button>
					<button onclick="search()" id="search">Search</button>
					<button style="margin-bottom: 4px;" onclick="insertLast()" id="last">Last</button>
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>