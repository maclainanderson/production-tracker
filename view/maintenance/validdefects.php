<!DOCTYPE html>
<?php
/**
  *	@desc list of defects for tools
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
	
	//list of defects
	$defects = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Valid_Defects");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
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
	var defects = [<?php
		foreach($defects as $defect) {
			echo '{';
			foreach($defect as $key=>$value) {
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
	  *	@desc	go to first defect
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous defect
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next defect
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < defects.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last defect
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(defects.length-1);
	}
	
	/**
	  *	@desc	find defect by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("defect-input").value = defects[current]['DEFECT'];
		document.getElementById("state-select").options.selectedIndex = defects[current]['STATUS'] == "Active" ? 0 : 1;
		document.getElementById("date-input").value = defects[current]['DATE'] != " " ? defects[current]['DATE'] + " by " + defects[current]['OPERATOR'] : "";
	}
	
	/**
	  *	@desc	prep readOnly attributes and value for a new defect
	  *	@param	none
	  *	@return	none
	*/
	function newDefect() {
		document.getElementById("defect-input").value = "";
		document.getElementById("defect-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveDefect(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes for editing a defect
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("defect-input").readOnly = false;
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveDefect(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save defect data
	  *	@param	string s - whether adding or updating a defect
	  *	@return	none
	*/
	function saveDefect(s){
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Valid_Defects";
		var id = defects[current]['ID'];
		var defect = document.getElementById("defect-input").value;
		var status = document.getElementById("state-select").value == "active" ? "Active" : "Inactive";
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Insert succeeded")){
					alert("Changes saved");
					window.location.replace("validdefects.php");
				} else if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(defects[current]['DEFECT'],defect);
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&DEFECT="+defect+"&STATUS="+status+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
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
	  *	@desc	modifies old records to match new defect
	  *	@param	string oldDefect - old name, string newDefect - new name
	  *	@return	none
	*/
	function modifyOldRecords(oldDefect,newDefect) {
		var conn1 = new XMLHttpRequest();
		var conn2 = new XMLHttpRequest();
		var successes = 0;
		var failures = 0;
		
		conn1.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText.includes("Data updated")) {
					successes++;
					if (successes > 1) {
						alert("Changes saved");
						window.location.replace("validdefects.php");
					} else {
						if (failures == 1) {
							alert("Not all old records modified. Contact IT Support to correct.");
						}
					}
				} else {
					failures++;
					if (successes == 1 && failures == 1) {
						alert("Not all old records modified. Contact IT Support to correct.");
					} else {
						alert("Old records not modified. Contact IT Support to correct.");
					}
				}
			}
		}
		
		conn2.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText.includes("Data updated")) {
					successes++;
					if (successes > 1) {
						alert("Changes saved");
						window.location.replace("validdefects.php");
					} else {
						if (failures == 1) {
							alert("Not all old records modified. Contact IT Support to correct.");
						}
					}
				} else {
					failures++;
					if (successes == 1 && failures == 1) {
						alert("Not all old records modified. Contact IT Support to correct.");
					} else {
						alert("Old records not modified. Contact IT Support to correct.");
					}
				}
			}
		}
		
		conn1.open("GET","/db_query/sql2.php?action=update&table=Tool_Status_History&REASON="+newDefect+"&condition=REASON&value="+oldDefect,true);
		conn2.open("GET","/db_query/sql2.php?action=update&table=Tool_Tree&REASON="+newDefect+"&condition=REASON&value="+oldDefect,true);
		
		conn1.send();
		conn2.send();
	}
	
	/**
	  *	@desc	set fields to readOnly and find current item
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("defect-input").readOnly = true;
		document.getElementById("state-select").disabled = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove defect
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Valid_Defects";
		var id = defects[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Defect deleted");
					window.location.replace("validdefects.php");
				} else {
					alert("Defect not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	create/display defect search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Defect</th><th class="col2">State</th><th class="col3">Modified</th><th class="col4">Operator</th></tr></thead><tbody>';
		
		defects.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['DEFECT']+'</td><td class="col2">'+item['STATUS']+'</td><td class="col3">'+item['DATE']+'</td><td class="col4">'+item['OPERATOR']+'</td></tr>';
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
		<title>Valid Defects</title>
		<link rel="stylesheet" type="text/css" href="/styles/validdefects.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="defect-span">Defect<br>
					<input id="defect-input" type="text" readonly></span><br>
					<span id="state-span">State<br>
					<select id="state-select" disabled>
						<option value="active">Active</option>
						<option value="inactive">Inactive</option>
					</select></span>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button onclick="newDefect()" id="add">Add</button>
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