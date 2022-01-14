<!DOCTYPE html>
<?php
/**
  *	@desc create/edit/list diamond types
*/
	//get user lists
	require_once("../../../utils.php");
	
	session_start();
	
	//redirect if not logged in
	if (!isset($_SESSION['name'])){
		header("Location: ../../index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
	}
	
	//set up sql connection for loading data
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of diamond types
	$types = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Diamond_Types");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$types[] = $row;
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
	var types = [<?php
		foreach($types as $type) {
			echo '{';
			foreach($type as $key=>$value) {
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
	
	/**
	  *	@desc	go to first type
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous type
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next type
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < types.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last type
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(types.length-1);
	}
	
	/**
	  *	@desc	find type by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("diamond-input").value = types[current]['TYPE'];
		document.getElementById("state-select").options.selectedIndex = types[current]['STATUS'] == "Active" ? 0 : 1;
		document.getElementById("date-input").value = types[current]['DATE'] != " " ? types[current]['DATE'] + " by " + types[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep values and readOnly attributes for new type
	  *	@param	none
	  *	@return	none
	*/
	function newType() {
		document.getElementById("diamond-input").value = "";
		document.getElementById("diamond-input").readOnly = false;
		document.getElementById("state-select").disabled = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveType(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes for new type
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("diamond-input").readOnly = false;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveType(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save type data
	  *	@param	string s - whether added or updated
	  *	@return	none
	*/
	function saveType(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Diamond_Types";
		var id = types[current]['ID'];
		var type = document.getElementById("diamond-input").value;
		var state = document.getElementById("state-select").value;
		var d = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Insert succeeded") || conn.responseText.includes("Data updated")) {
					alert("Changes saved");
					window.location.replace("diamondtypes.php");
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&TYPE="+type+"&STATUS="+state+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&id="+id, true);
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
		document.getElementById("diamond-input").readOnly = true;
		document.getElementById("state-select").disabled = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove diamond type
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Diamond_Types";
		var id = types[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Diamond type deleted");
					window.location.replace("diamondtypes.php");
				} else {
					alert("Diamond type not deleted. Contact IT Support. " + conn.responseText);
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
		html += '<table><thead><tr><th class="col1">Type</th><th class="col2">State</th><th class="col3">Modified</th><th class="col4">Operator</th></tr></thead><tbody>';
		
		types.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['TYPE']+'</td><td class="col2">'+item['STATUS']+'</td><td class="col3">'+item['DATE']+'</td><td class="col4">'+item['OPERATOR']+'</td></tr>';
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
		<title>Diamond Types</title>
		<link rel="stylesheet" type="text/css" href="/styles/diamondtypes.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="diamond-span">Diamond Type<input id="diamond-input" type="text" readonly></span><br>
					<span id="state-span">State<select id="state-select" disabled>
						<option value="Active">Active</option>
						<option value="Inactive">Inactive</option>
					</select></span><br>
					<span id="date-span">Last Modified<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button id="add" onclick="newType()">Add</button>
					<button onclick="insertFirst()">First</button>
					<button id="edit" onclick="edit()">Edit</button>
					<button onclick="goUp()">Up</button>
					<button id="delete" onclick="deleteItem()">Delete</button>
					<button onclick="goDown()">Down</button>
					<button onclick="search()">Search</button>
					<button onclick="insertLast()">Last</button>
					<a href="../masteringtables.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>