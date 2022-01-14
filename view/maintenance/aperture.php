<!DOCTYPE html>
<?php
/**
  *	@desc manage list of program apertures
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
	
	//list of apertures
	$apertures = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Program_Apertures");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$apertures[] = $row;
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
	var isMetric = true;
	var apertures = [<?php
		foreach($apertures as $aperture) {
			echo '{';
			foreach($aperture as $key=>$value) {
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
	  *	@desc	go to first aperture
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous aperture
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next aperture
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < apertures.length-1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last aperture
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(apertures.length-1);
	}
	
	/**
	  *	@desc	find aperture by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("program-input").value = apertures[current]['PROGRAM'];
		if (isMetric) {
			document.getElementById("aperture-input").value = apertures[current]['APERTURE'];
		} else {
			document.getElementById("aperture-input").value = (parseInt(apertures[current]['APERTURE']) * .03937007874).toFixed(3);
		}
		isActive = apertures[current]['STATUS'];
		if (isActive === "Active") {
			document.getElementById("state-select").options.selectedIndex = 0;
		} else {
			document.getElementById("state-select").options.selectedIndex = 1;
		}
		if (apertures[current]['DATE'] != " ") {
			document.getElementById("date-input").value = apertures[current]['DATE'] + " by " + apertures[current]['OPERATOR'];
		} else {
			document.getElementById("date-input").value = "";
		}
	}
	
	/**
	  *	@desc	prep values and readOnly attributes for new aperture
	  *	@param	none
	  *	@return	none
	*/
	function newAperture() {
		document.getElementById("program-input").value = "";
		document.getElementById("program-input").readOnly = false;
		document.getElementById("aperture-input").value = "";
		document.getElementById("aperture-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveAperture(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
		if (!isMetric) {
			switchUnit(document.getElementById("unit"));
		}
	}
	
	/**
	  *	@desc	prep readOnly attributes to edit aperture
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("program-input").readOnly = false;
		document.getElementById("aperture-input").readOnly = false;
		document.getElementById("state-select").options.selectedIndex = 0;
		document.getElementById("state-select").disabled = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveAperture(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
		if (!isMetric) {
			switchUnit(document.getElementById("unit"));
		}
	}
	
	/**
	  *	@desc	save aperture data
	  *	@param	string s - whether added or updated
	  *	@return	none
	*/
	function saveAperture(s){
		var conn = new XMLHttpRequest();
		var type = s == "add" ? "insert" : "update";
		var table = "Program_Apertures";
		var id = apertures[current]['ID'];
		var program = document.getElementById("program-input").value;
		var aperture = document.getElementById("aperture-input").value;
		var state = document.getElementById("state-select").value;
		if (state == "active") {
			state = "Active";
		} else {
			state = "Inactive";
		}
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(apertures[current]['PROGRAM'],program);
				} else if (conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.reload();
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+type+"&table="+table+"&PROGRAM="+program+"&APERTURE="+aperture+"&STATUS="+state+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
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
	  *	@param	String oldProgram - old program number, String newProgram - new program number
	  *	@return	none
	*/
	function modifyOldRecords(oldProgram,newProgram) {
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
								window.location.replace("aperture.php");
							} else {
								alert("Not all old records updated. Contact IT support to correct.");
							}
						}
					}
				}
			}
			
			conn[i].open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&PROGRAM_NUMBER="+newProgram+"&condition=PROGRAM_NUMBER&value="+oldProgram,false);
			conn[i].send();
		}
	}
	
	/**
	  *	@desc	set fields to readOnly and find current aperture
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("program-input").readOnly = true;
		document.getElementById("state-select").disabled = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	remove program aperture
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var type = "delete";
		var table = "Program_Apertures";
		var id = apertures[current]['ID'];
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Aperture deleted");
					window.location.replace("aperture.php");
				} else {
					alert("Aperture not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+type+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	switch between mm and in
	  *	@param	DOM Object button - to change label of
	  *	@return	none
	*/
	function switchUnit(button) {
		if (button.innerHTML == "Metric") {
			button.innerHTML = "Standard";
			document.getElementById("measure").innerHTML = "(mm)";
			isMetric = true;
			if (document.getElementById("aperture-input").value != "") {
				document.getElementById("aperture-input").value = apertures[current]['APERTURE'];
			}
		} else {
			button.innerHTML = "Metric";
			document.getElementById("measure").innerHTML = "(in)";
			isMetric = false;
			if (document.getElementById("aperture-input").value != "") {
				document.getElementById("aperture-input").value = (parseInt(document.getElementById("aperture-input").value) * .03937007874).toFixed(3);
			}
		}
	}
	
	/**
	  *	@desc	create/display search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Program</th><th class="col2">Aperture</th><th class="col3">Status</th><th class="col4">Modified</th><th class="col5">Operator</th></tr></thead><tbody>';
		
		apertures.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['PROGRAM']+'</td><td class="col2">'+item['APERTURE']+'</td><td class="col3">'+item['STATUS']+'</td><td class="col4">'+item['DATE']+'</td><td class="col5">'+item['OPERATOR']+'</td></tr>';
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
	  *	@param	none
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
		<title>Aperture</title>
		<link rel="stylesheet" type="text/css" href="/styles/aperture.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="program-span">Program Number:<br>
					<input id="program-input" type="text" readonly></span><br>
					<span id="aperture-span">Aperture<br>
					<input id="aperture-input" type="text" readonly><span id="measure">(mm)</span></span><br>
					<span id="state-span">State<br>
					<select id="state-select" disabled>
						<option value="active">Active</option>
						<option value="inactive">Inactive</option>
					</select></span>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button onclick="newAperture()" id="add">Add</button>
					<button onclick="insertFirst()" id="first">First</button>
					<button onclick="edit()" id="edit">Edit</button>
					<button onclick="goUp()" id="up">Up</button>
					<button onclick="deleteItem()" id="delete">Delete</button>
					<button onclick="goDown()" id="down">Down</button>
					<button onclick="search()">Search</button>
					<button onclick="insertLast()" id="last">Last</button>
					<button id="unit" onclick="switchUnit(this)">Standard</button>
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>