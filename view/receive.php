<!DOCTYPE html>
<?php
/**
  * @desc manage list of blanks (for mastering)
*/
	session_start();
	
	if(!isset($_SESSION['name'])) {
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of blanks and locations
	$blanks = array();
	$locations = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Blanks ORDER BY BLANK ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$blanks[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT LOCATION FROM Inv_Locations WHERE STATUS = 'Active' ORDER BY LOCATION ASC;");
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
	var isMetric = false;
	var blanks = [<?php
		foreach($blanks as $blank) {
			echo '{';
			foreach($blank as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
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
	
	/**
	  *	@desc	go to first blank
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		current = 0;
		find(current);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous blank
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			current--;
		}
		find(current);
	}
	
	/**
	  *	@desc	go to next blank
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < blanks.length - 1) {
			current++;
		}
		find(current);
	}
	
	/**
	  *	@desc	go to last blank
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		current = blanks.length - 1;
		find(current);
	}
	
	/**
	  *	@desc	find specific blank by id
	  *	@param	none
	  *	@return	none
	*/
	function find(i) {
		current = i;
		document.getElementById("blank-input").value = blanks[current]['BLANK'];
		document.getElementById("date-input").value = blanks[current]['DATE'];
		document.getElementById("location-select").value = blanks[current]['LOCATION'];
		document.getElementById("location-input").value = blanks[current]['DRAWER'];
		document.getElementById("initials").value = blanks[current]['OPERATOR'];
		document.getElementById("size-input").value = isMetric ? (blanks[current]['SIZE'] / 0.03937007874).toFixed(3) : blanks[current]['SIZE'];
		document.getElementById("date-created").value = blanks[current]['RECEIVE_DATE'];
		document.getElementById("material-select").value = blanks[current]['BASE_MATERIAL'];
		document.getElementById("additive-select").value = blanks[current]['ADDITIVE'];
		document.getElementById("thick-before").value = isMetric ? (blanks[current]['THICKNESS_BEFORE'] / 0.03937007874).toFixed(3) : blanks[current]['THICKNESS_BEFORE'];
		document.getElementById("thick-after").value = isMetric ? (blanks[current]['THICKNESS_AFTER'] / 0.03937007874).toFixed(3) : blanks[current]['THICKNESS_AFTER'];
		document.getElementById("hardness1-1").value = blanks[current]['CAL_HARDNESS1'];
		document.getElementById("hardness1-2").value = blanks[current]['CAL_HARDNESS2'];
		document.getElementById("hardness1-3").value = blanks[current]['CAL_HARDNESS3'];
		document.getElementById("hardness1-4").value = blanks[current]['CAL_HARDNESS4'];
		document.getElementById("hardness1-5").value = blanks[current]['CAL_HARDNESS5'];
		document.getElementById("hardness1-6").value = blanks[current]['CAL_HARDNESS6'];
		document.getElementById("comment1").value = blanks[current]['CAL_COMMENT'];
		document.getElementById("hardness2-1").value = blanks[current]['PTC_HARDNESS1'];
		document.getElementById("hardness2-2").value = blanks[current]['PTC_HARDNESS2'];
		document.getElementById("hardness2-3").value = blanks[current]['PTC_HARDNESS3'];
		document.getElementById("hardness2-4").value = blanks[current]['PTC_HARDNESS4'];
		document.getElementById("hardness2-5").value = blanks[current]['PTC_HARDNESS5'];
		document.getElementById("hardness2-6").value = blanks[current]['PTC_HARDNESS6'];
		document.getElementById("comment2").value = blanks[current]['PTC_COMMENT'];
	}
	
	/**
	  *	@desc	prep fields for entering a new blank
	  *	@param	none
	  *	@return	none
	*/
	function receiveBlank() {
		document.getElementById("blank-input").value = "";
		document.getElementById("blank-input").readOnly = false;
		document.getElementById("date-input").value = "";
		document.getElementById("date-input").readOnly = false;
		document.getElementById("location-select").options.selectedIndex = 0;
		document.getElementById("location-select").disabled = false;
		document.getElementById("location-input").value = "";
		document.getElementById("location-input").readOnly = false;
		document.getElementById("initials").value = "";
		document.getElementById("initials").readOnly = false;
		document.getElementById("size-input").value = "";
		document.getElementById("size-input").readOnly = false;
		document.getElementById("date-created").value = "";
		document.getElementById("date-created").readOnly = false;
		document.getElementById("material-select").options.selectedIndex = 0;
		document.getElementById("material-select").disabled = false;
		document.getElementById("additive-select").options.selectedIndex = 0;
		document.getElementById("additive-select").disabled = false;
		document.getElementById("thick-before").value = "";
		document.getElementById("thick-before").readOnly = false;
		document.getElementById("thick-after").value = "";
		document.getElementById("thick-after").readOnly = false;
		document.getElementById("hardness1-1").value = "";
		document.getElementById("hardness1-1").readOnly = false;
		document.getElementById("hardness1-2").value = "";
		document.getElementById("hardness1-2").readOnly = false;
		document.getElementById("hardness1-3").value = "";
		document.getElementById("hardness1-3").readOnly = false;
		document.getElementById("hardness1-4").value = "";
		document.getElementById("hardness1-4").readOnly = false;
		document.getElementById("hardness1-5").value = "";
		document.getElementById("hardness1-5").readOnly = false;
		document.getElementById("hardness1-6").value = "";
		document.getElementById("hardness1-6").readOnly = false;
		document.getElementById("comment1").value = "";
		document.getElementById("comment1").readOnly = false;
		document.getElementById("hardness2-1").value = "";
		document.getElementById("hardness2-1").readOnly = false;
		document.getElementById("hardness2-2").value = "";
		document.getElementById("hardness2-2").readOnly = false;
		document.getElementById("hardness2-3").value = "";
		document.getElementById("hardness2-3").readOnly = false;
		document.getElementById("hardness2-4").value = "";
		document.getElementById("hardness2-4").readOnly = false;
		document.getElementById("hardness2-5").value = "";
		document.getElementById("hardness2-5").readOnly = false;
		document.getElementById("hardness2-6").value = "";
		document.getElementById("hardness2-6").readOnly = false;
		document.getElementById("comment2").value = "";
		document.getElementById("comment2").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveBlank(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep fields for editing a blank
	  *	@param	none
	  *	@return	none
	*/
	function editBlank() {
		document.getElementById("blank-input").readOnly = false;
		document.getElementById("date-input").readOnly = false;
		document.getElementById("location-select").disabled = false;
		document.getElementById("location-input").readOnly = false;
		document.getElementById("initials").readOnly = false;
		document.getElementById("size-input").readOnly = false;
		document.getElementById("date-created").readOnly = false;
		document.getElementById("material-select").disabled = false;
		document.getElementById("additive-select").disabled = false;
		document.getElementById("thick-before").readOnly = false;
		document.getElementById("thick-after").readOnly = false;
		document.getElementById("hardness1-1").readOnly = false;
		document.getElementById("hardness1-2").readOnly = false;
		document.getElementById("hardness1-3").readOnly = false;
		document.getElementById("hardness1-4").readOnly = false;
		document.getElementById("hardness1-5").readOnly = false;
		document.getElementById("hardness1-6").readOnly = false;
		document.getElementById("comment1").readOnly = false;
		document.getElementById("hardness2-1").readOnly = false;
		document.getElementById("hardness2-2").readOnly = false;
		document.getElementById("hardness2-3").readOnly = false;
		document.getElementById("hardness2-4").readOnly = false;
		document.getElementById("hardness2-5").readOnly = false;
		document.getElementById("hardness2-6").readOnly = false;
		document.getElementById("comment2").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveBlank(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	submit new or edited blank
	  *	@param	string s - either add or edit
	  *	@return	none
	*/
	function saveBlank(s) {
		var conn = new XMLHttpRequest();
		var type = s == "add" ? "insert" : "update";
		var table = "Blanks";
		var id = blanks[current]['ID'];
		var blank = document.getElementById("blank-input").value;
		var receiveDate = document.getElementById("date-input").value;
		var location = document.getElementById("location-select").value;
		var drawer = document.getElementById("location-input").value;
		var size = document.getElementById("size-input").value;
		var date = document.getElementById("date-created").value;
		var material = document.getElementById("material-select").value;
		var additive = document.getElementById("additive-select").value;
		var thicknessBefore = document.getElementById("thick-before").value;
		var thicknessAfter = document.getElementById("thick-after").value;
		var calHardness1 = document.getElementById("hardness1-1").value;
		var calHardness2 = document.getElementById("hardness1-2").value;
		var calHardness3 = document.getElementById("hardness1-3").value;
		var calHardness4 = document.getElementById("hardness1-4").value;
		var calHardness5 = document.getElementById("hardness1-5").value;
		var calHardness6 = document.getElementById("hardness1-6").value;
		var comment1 = document.getElementById("comment1").value;
		var ptcHardness1 = document.getElementById("hardness2-1").value;
		var ptcHardness2 = document.getElementById("hardness2-2").value;
		var ptcHardness3 = document.getElementById("hardness2-3").value;
		var ptcHardness4 = document.getElementById("hardness2-4").value;
		var ptcHardness5 = document.getElementById("hardness2-5").value;
		var ptcHardness6 = document.getElementById("hardness2-6").value;
		var comment2 = document.getElementById("comment2").value;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				alert(conn.responseText);
				window.location.replace("receive.php");
			}
		}
		
		conn.open("GET", "/db_query/sql2.php?action="+type+"&table="+table+"&BLANK="+blank+"&LOCATION="+location+"&DRAWER="+drawer+"&SIZE="+size+"&BASE_MATERIAL="+material+"&ADDITIVE="+additive+"&THICKNESS_BEFORE="+thicknessBefore+"&THICKNESS_AFTER="+thicknessAfter+"&CAL_HARDNESS1="+calHardness1+"&CAL_HARDNESS2="+calHardness2+"&CAL_HARDNESS3="+calHardness3+"&CAL_HARDNESS4="+calHardness4+"&CAL_HARDNESS5="+calHardness5+"&CAL_HARDNESS6="+calHardness6+"&CAL_COMMENT="+comment1+"&PTC_HARDNESS1="+ptcHardness1+"&PTC_HARDNESS2="+ptcHardness2+"&PTC_HARDNESS3="+ptcHardness3+"&PTC_HARDNESS4="+ptcHardness4+"&PTC_HARDNESS5="+ptcHardness5+"&PTC_HARDNESS6="+ptcHardness6+"&PTC_COMMENT="+comment2+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&RECEIVE_DATE="+receiveDate+"&condition=id&value="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	cancel changes
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("blank-input").readOnly = true;
		document.getElementById("date-input").readOnly = true;
		document.getElementById("location-select").disabled = true;
		document.getElementById("location-input").readOnly = true;
		document.getElementById("initials").readOnly = true;
		document.getElementById("size-input").readOnly = true;
		document.getElementById("date-created").readOnly = true;
		document.getElementById("material-select").disabled = true;
		document.getElementById("additive-select").disabled = true;
		document.getElementById("thick-before").readOnly = true;
		document.getElementById("thick-after").readOnly = true;
		document.getElementById("hardness1-1").readOnly = true;
		document.getElementById("hardness1-2").readOnly = true;
		document.getElementById("hardness1-3").readOnly = true;
		document.getElementById("hardness1-4").readOnly = true;
		document.getElementById("hardness1-5").readOnly = true;
		document.getElementById("hardness1-6").readOnly = true;
		document.getElementById("comment1").readOnly = true;
		document.getElementById("hardness2-1").readOnly = true;
		document.getElementById("hardness2-2").readOnly = true;
		document.getElementById("hardness2-3").readOnly = true;
		document.getElementById("hardness2-4").readOnly = true;
		document.getElementById("hardness2-5").readOnly = true;
		document.getElementById("hardness2-6").readOnly = true;
		document.getElementById("comment2").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','editBlank()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteBlank()');
		find(current);
	}
	
	/**
	  *	@desc	remove blank
	  *	@param	none
	  *	@return	none
	*/
	function deleteBlank() {
		var conn = new XMLHttpRequest();
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				alert(conn.responseText);
				window.location.replace("receive.php");
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action=delete&table=Blanks&id=" + blanks[current]['ID'], true);
		conn.send();
	}
	
	/**
	  *	@desc	swap between mm and in, and vice versa
	  *	@param	DOM Object button - metric button for changing its label
	  *	@return	none
	*/
	function switchUnit(button) {
		if (button.innerHTML == "Metric") {
			button.innerHTML = "Standard";
			spans = document.getElementsByClassName("measure");
			for (var i=0;i<spans.length;i++) {
				spans[i].innerHTML = "mm";
			}
			isMetric = true;
			if (document.getElementById("size-input").value != "") {
				document.getElementById("size-input").value = (parseFloat(document.getElementById("size-input").value) / 0.03937007874).toFixed(3);
			}
			if (document.getElementById("thick-before").value != "") {
				document.getElementById("thick-before").value = (parseFloat(document.getElementById("thick-before").value) / 0.03937007874).toFixed(3);
			}
			if (document.getElementById("thick-after").value != "") {
				document.getElementById("thick-after").value = (parseFloat(document.getElementById("thick-after").value) / 0.03937007874).toFixed(3);
			}
		} else {
			button.innerHTML = "Metric";
			spans = document.getElementsByClassName("measure");
			for (var i=0;i<spans.length;i++) {
				spans[i].innerHTML = "in";
			}
			isMetric = false;
			if (document.getElementById("size-input").value != "") {
				document.getElementById("size-input").value = blanks[current]['SIZE'];
			}
			if (document.getElementById("thick-before").value != "") {
				document.getElementById("thick-before").value = blanks[current]['THICKNESS_BEFORE'];
			}
			if (document.getElementById("thick-after").value != "") {
				document.getElementById("thick-after").value = blanks[current]['THICKNESS_AFTER'];
			}
		}
	}
	
	/**
	  *	@desc	auto-format date field to MM/DD/YY
	  *	@param	DOM Object input - date field to update
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
	
	/**
	  *	@desc	search for blanks
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		html = "<span id=\"close\">&times;</span>";
		html += "<input type=\"text\" id=\"search-input\"><button id=\"search-button\">Search</button>";
		modalContent.innerHTML = html;
		modal.style.display = "block";
		
		document.getElementById("search-button").onclick = function() {
			var found = false;
			
			blanks.forEach((item, index, array) => {
				if (item['BLANK'].toUpperCase().includes(document.getElementById("search-input").value.toUpperCase()) && found == false) {
					found = true;
					getList(index);
				}
			});
			
			if (!found) {
				alert("Search term not found");
			}
		}
		
		document.getElementById("search-input").focus();
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search-button").click();
			}
		}
		
		closeForm();
	}
	
	/**
	  *	@desc	get list of blanks from search
	  *	@param	index - array index of first match
	  *	@return	none
	*/
	function getList(index) {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		if (document.getElementsByTagName('table').length > 0) {
			modalContent.removeChild(document.getElementsByTagName('table')[0]);
		}
		
		html = '<table><thead><tr><th class="col1">Blank</th><th class="col2">Location</th><th class="col3">Drawer</th><th class="col4">Date</th><th class="col5">Operator</th></tr></thead><tbody>';
		for (var i=0;i<blanks.length;i++) {
			html += '<tr onclick="selectRow(this)" id="' + i + '"><td class="col1">' + blanks[i]['BLANK'] + '</td><td class="col2">' + blanks[i]['LOCATION'] + '</td><td class="col3">' + blanks[i]['DRAWER'] + '</td><td class="col4">' + blanks[i]['DATE'] + '</td><td class="col5">' + blanks[i]['OPERATOR'] + '</td></tr>';
		}
		html += '</tbody></table>';
		
		modalContent.innerHTML += html;
		
		document.getElementById(index).scrollIntoView();
		
		document.getElementById("search-button").onclick = function() {
			var found = false;
			
			blanks.forEach((item, index, array) => {
				if (item['BLANK'].toUpperCase().includes(document.getElementById("search-input").value.toUpperCase()) && found == false) {
					found = true;
					getList(index);
				}
			});
			
			if (!found) {
				alert("Search term not found");
			}
		}
		
		document.getElementById("search-input").focus();
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search-button").click();
			}
		}
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight blank row, confirm if second click
	  *	@param	tr - row selected
	  *	@return	none
	*/
	function selectRow(tr) {
		if (tr.style.backgroundColor == 'black') {
			find(tr.id);
			document.getElementById('close').click();
		} else {
			for (var i=0;i<tr.parentNode.children.length;i++) {
				tr.parentNode.children[i].style.backgroundColor = "white";
				tr.parentNode.children[i].style.color = "black";
			}
			
			tr.style.backgroundColor = "black";
			tr.style.color = "white";
		}
		
		closeForm();
	}
	
	/**
	  *	@desc	set onclick to close modal form
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
	
</script>
<html>
	<head>
		<title>Receive a Blank</title>
		<link rel="stylesheet" type="text/css" href="/styles/receive.css">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="top-left">
					<br><span>Blank</span><input type="text" name="blank" id="blank-input" readonly><br>
					<span>Receive Date</span><input onkeydown="fixDate(this)" type="text" name="date" id="date-input" readonly>
					<span>Location</span><select name="location-select" id="location-select" disabled>
						<option value=""></option>
						<?php foreach($locations as $location) {
							echo "<option value=\"".$location['LOCATION']."\">".$location['LOCATION']."</option>";
						} ?>
					</select><input type="text" name="location-input" id="location-input" readonly><br>
					<span>Initials</span><input type="text" name="initials" id="initials" readonly>
				</div>
				<div class="controls">
					<div class="controls-left">
						<button class="small" id="add" onclick="receiveBlank()">Receive</button>
						<button class="small" id="edit" onclick="editBlank()">Edit</button>
						<button class="small" id="delete" onclick="deleteBlank()">Delete</button>
						<button class="small" onclick="search()">Search</button>
					</div>
					<div class="controls-right">
						<button class="small" onclick="insertFirst()">First</button>
						<button class="small" onclick="goUp()">Up</button>
						<button class="small" onclick="goDown()">Down</button>
						<button class="small" onclick="insertLast()">Last</button>
					</div>
					<button class="big" id="measurement-type" onclick="switchUnit(this)" value="metric">Metric</button>
					<a class="big" href="home.php">Back</a>
				</div>
				<div class="center">
					<span style="margin-left: 40px;">California Information</span><br>
					<div class="center-left">
						<span>Size</span><input type="text" name="size" id="size-input" readonly><span style="margin-right: 9px;"><span class="measure">in</span></span><br>
						<span>Date Created</span><input type="text" name="date-created" id="date-created" readonly><br>
						<span>Base Material</span><select name="material-select" id="material-select" disabled>
							<option value="Copper">Copper</option>
							<option value="Brass">Brass</option>
						</select><br>
						<span>Deposit + Additive</span><select name="additive-select" id="additive-select" disabled>
							<option value="Copper | McGean 401/402">Copper | McGean 401/402</option>
							<option value="Brass | McGean 401/402">Brass | McGean 401/402</option>
						</select>
					</div>
					<div class="center-right">
						<span>Thickness Before Plating</span><input type="text" name="thick-before" id="thick-before" readonly><span class="measure">in</span><br>
						<span>Thickness After Plating</span><input type="text" name="thick-after" id="thick-after" readonly><span class="measure">in</span><br>
					</div>
					<div class="center-bottom">
						<span style="margin-left: 70px;">Center ------------------------------------------------------------------------------------------------------------> Edge</span><br>
						<span>Hardness
							<input class="hardness" type="text" id="hardness1-1" readonly>
							<input class="hardness" type="text" id="hardness1-2" readonly>
							<input class="hardness" type="text" id="hardness1-3" readonly>
							<input class="hardness" type="text" id="hardness1-4" readonly>
							<input class="hardness" type="text" id="hardness1-5" readonly>
							<input class="hardness" type="text" id="hardness1-6" readonly>
						</span><br>
						<span>Comment<input type="text" name="comment1" id="comment1" readonly></span>
					</div>
				</div>
				<div class="bottom">
					<span style="margin-left: 40px;">Initial PTC Information</span><br>
					<span style="margin-left: 110px;">Center ------------------------------------------------------------------------------------------------------------> Edge</span><br>
					<span style="margin-left: 40px;">Hardness
						<input class="hardness" type="text" id="hardness2-1" readonly>
						<input class="hardness" type="text" id="hardness2-2" readonly>
						<input class="hardness" type="text" id="hardness2-3" readonly>
						<input class="hardness" type="text" id="hardness2-4" readonly>
						<input class="hardness" type="text" id="hardness2-5" readonly>
						<input class="hardness" type="text" id="hardness2-6" readonly>
					</span><br>
					<span style="margin-left: 40px;">Comment<input type="text" name="comment2" id="comment2" readonly></span>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>