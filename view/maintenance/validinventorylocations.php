<!DOCTYPE html>
<?php
/**
  *	@desc list of inventory locations
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
	
	//list of locations
	$locations = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Inv_Locations");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
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
	var isActive = "Active";
	var locations = [<?php
		foreach($locations as $location) {
			echo '{';
			foreach($location as $key=>$value) {
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
	  *	@desc	go to first location
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous location
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next location
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < locations.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last location
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(locations.length-1);
	}
	
	/**
	  *	@desc	find location by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("location-input").value = locations[current]['LOCATION'];
		document.getElementById("state-select").options.selectedIndex = locations[current]['STATUS'] == "Active" ? 0 : 1;
		document.getElementById("date-input").value = locations[current]['DATE'] != " " ? locations[current]['DATE'] + " by " + locations[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep readOnly attributes and values for a new location
	  *	@param	none
	  *	@return	none
	*/
	function newLocation() {
		document.getElementById("location-input").value = "";
		document.getElementById("location-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveLocation(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes for editing a location
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("location-input").readOnly = false;
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveLocation(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save location data
	  *	@param	string s - whether adding or updating location
	  *	@return	none
	*/
	function saveLocation(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Inv_Locations";
		var id = locations[current]['ID'];
		var location = document.getElementById("location-input").value;
		var status = document.getElementById("state-select").value == "active" ? "Active" : "Inactive";
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.reload();
				} else if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(locations[current]['LOCATION'],location);
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&LOCATION="+location+"&STATUS="+status+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
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
	  *	@param	String oldLocation - old location name, String newLocation - new location name
	  *	@return	none
	*/
	function modifyOldRecords(oldLocation,newLocation) {
		var conn = [];
		var tables = ['Blanks','Tool_Tree'];
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
								window.location.replace("validinventorylocations.php");
							} else {
								alert("Not all old records updated. Contact IT support to correct.");
							}
						}
					}
				}
			}
			
			conn[i].open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&LOCATION="+newLocation+"&condition=LOCATION&value="+oldLocation,false);
			conn[i].send();
		}
	}
	
	/**
	  *	@desc	set fields to readOnly and go to current location
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("location-input").readOnly = true;
		document.getElementById("state-select").disabled = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove location
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Inv_Locations";
		var id = locations[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Inventory location deleted");
					window.location.replace("validinventorylocations.php");
				} else {
					alert("Inventory location not deleted. Contact IT Support. " + conn.responseText);
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
		html += '<table><thead><tr><th class="col1">Location</th><th class="col2">State</th><th class="col3">Modified</th><th class="col4">Operator</th></tr></thead><tbody>';
		
		locations.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['LOCATION']+'</td><td class="col2">'+item['STATUS']+'</td><td class="col3">'+item['DATE']+'</td><td class="col4">'+item['OPERATOR']+'</td></tr>';
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
		<title>Valid Inventory Locations</title>
		<link rel="stylesheet" type="text/css" href="/styles/validinventorylocations.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="location-span">Location<br>
					<input id="location-input" type="text" readonly></span><br>
					<span id="state-span">State<br>
					<select id="state-select" disabled>
						<option value="active" id="active">Active</option>
						<option value="inactive" id="inactive">Inactive</option>
					</select></span>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button onclick="newLocation()" id="add">Add</button>
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