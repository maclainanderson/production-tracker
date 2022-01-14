<!DOCTYPE html>
<?php
/**
  * @desc for choosing a report to run and supplying search values
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of tools and tanks
	$tools = array();
	$tanks = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER FROM Tool_Tree ORDER BY TOOL;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tools[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TANK FROM Valid_Tanks ORDER BY TANK;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tanks[] = $row[1];
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
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
	
	//tracking variables
	var tools = [<?php
		foreach($tools as $tool) {
			echo '{';
			foreach($tool as $key=>$value) {
				echo '"' . $key . '": `';
				echo addslashes($value);
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	/**
	  *	@desc	auto-format a date field to fit MM/DD/YY format
	  *	@param	DOM Object input - date field to be formatted
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
	  *	@desc	add form to submit values to run report on
	  *	@param	none
	  *	@return	none
	*/
	function getDetails() {
		var div = document.getElementsByClassName('inner')[0];
		var forms = document.getElementsByTagName("form");
		if (forms.length > 0) {
			div.removeChild(forms[0]);
		}
		var brs = document.getElementsByTagName("br");
		if (brs.length > 0) {
			div.removeChild(brs[0]);
		}
		
		switch(document.getElementById('report-select').value) {
			case "Tool Listing Report":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Tool Listing Report"><div style="display: inline-block"><span>From:</span><input id="tool-from" name="toolFrom" type="text" style="width: 300px;"><button type="button" onclick="searchToolFrom()">Search</button><br><span style="margin-left: 21px;">To:</span><input id="tool-to" name="toolTo" type="text" style="width: 300px;"><button type="button" onclick="searchToolTo()">Search</button></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Nickel Usage":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Nickel Usage"><div style="display: inline-block"><span>From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "E-Forming Cycle":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="E-Forming Cycle"><div style="display: inline-block"><span>From:</span><input id="tool-from" name="toolFrom" type="text" style="width: 300px;"><button type="button" onclick="searchToolFrom()">Search</button><br><span style="margin-left: 21px;">To:</span><input id="tool-to" name="toolTo" type="text" style="width: 300px;"><button type="button" onclick="searchToolTo()">Search</button></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Reflexite Eform Yield":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Reflexite Eform Yield"><div style="display: inline-block"><span>From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Toolroom Production Yield":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Toolroom Production Yield"><div style="display: inline-block"><span>Operator:</span><input id="operator" name="operator" type="text" style="width: 300px;"><br><span>From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Fresnel Eform Yield":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Fresnel Eform Yield"><div style="display: inline-block"><span>From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Daily Operations - Eform":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Daily Operations - Eform"><div style="display: inline-block"><span>From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Daily Operations - Troom":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Daily Operations - Troom"><div style="display: inline-block"><span>From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "Tank Status Report":
				div.innerHTML += '<br><form action="report.php" method="POST" style="margin-top: 20px;"><input type="text" hidden name="report" value="Tank Status Report"><div style="display: inline-block; text-align: right;"><span>Tank From:</span><select name="tankFrom"><?php foreach($tanks as $tank) { echo '<option value="' . $tank . '">' . $tank . '</option>'; } ?></select><br><span>To:</span><select name="tankTo"><?php foreach($tanks as $tank) { echo '<option value="' . $tank . '">' . $tank . '</option>'; } ?></select></div><div style="display: inline-block; text-align: right;"><span>Date From:</span><input id="date-from" name="dateFrom" type="text" style="width: 300px;" onkeydown="fixDate(this)"><br><span style="margin-left: 21px;">To:</span><input id="date-to" name="dateTo" type="text" style="width: 300px;" onkeydown="fixDate(this)"></div><div style="text-align: center"><input type="submit" value="Print"></div></form>';
				break;
			case "In Progress Report":
				div.innerHTML += '<form action="report.php" method="POST" style="visibility: none;"><input hidden type="text" name="report" value="In Progress Report"><input hidden type="submit"></form>';
				document.getElementsByTagName("form")[0].submit();
				break;
			default:
		}
		
		var elems = div.getElementsByTagName("input");
		
		for (var i=0;i<elems.length;i++) {
			if (elems[i].id.includes("tool")) {
				elems[i].onkeydown = function(e) {
					if (e.key == "Enter") {
						this.nextElementSibling.click();
					}
				}
			}
		}
		
		document.getElementsByTagName("form")[0].onkeydown = function(e) {
			if (e.key == "Enter") {
				e.preventDefault();
			}
		}
		
		document.getElementsByTagName("form")[0].getElementsByTagName("input")[1].focus();
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
	  *	@desc	create tool list for toolFrom field
	  *	@param	none
	  *	@return	none
	*/
	function searchToolFrom() {
		var modal = document.getElementById('modal');
		var modalContent = document.getElementById('modal-content');
		var searchText = document.getElementById('tool-from').value;
		var html = '<span id="close">&times;</span><table><thead><tr><th class="col1">Tool</th><th class="col2">Status</th><th class="col3">Reason</th><th class="col4">Drawer</th><th class="col5">Location</th></tr></thead><tbody id="tool-table">';
		
		tools.forEach((item, index ,array) => {
			if (item['TOOL'].includes(searchText.toUpperCase())) {
				html += '<tr id="' + item['ID'] + '" onclick="selectToolFromRow(this)"><td class="col1">' + item['TOOL'] + '</td><td class="col2">' + item['STATUS'] + '</td><td class="col3">' + item['REASON'] + '</td><td class="col4">' + item['DRAWER'] + '</td><td class="col5">' + item['LOCATION'] + '</td><tr>';
			}
		});
		
		html += '</tbody></table>';
		
		modalContent.innerHTML = html;
		modal.style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	select tool from Tool From list
	  *	@param	none
	  *	@return	none
	*/
	function selectToolFromRow(tr) {
		var trs = document.getElementById('tool-table').children;
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectToolFromRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmToolFrom(this)');
	}
	
	/**
	  *	@desc	confirm tool from Tool From list
	  *	@param	none
	  *	@return	none
	*/
	function confirmToolFrom(tr) {
		document.getElementById('tool-from').value = tr.children[0].innerHTML;
		document.getElementById('close').click();
	}
	
	/**
	  *	@desc	create tool list for toolTo field
	  *	@param	none
	  *	@return	none
	*/
	function searchToolTo() {
		var modal = document.getElementById('modal');
		var modalContent = document.getElementById('modal-content');
		var searchText = document.getElementById('tool-to').value;
		var html = '<span id="close">&times;</span><table><thead><tr><th class="col1">Tool</th><th class="col2">Status</th><th class="col3">Reason</th><th class="col4">Drawer</th><th class="col5">Location</th></tr></thead><tbody id="tool-table">';
		
		tools.forEach((item, index ,array) => {
			if (item['TOOL'].includes(searchText.toUpperCase())) {
				html += '<tr id="' + item['ID'] + '" onclick="selectToolToRow(this)"><td class="col1">' + item['TOOL'] + '</td><td class="col2">' + item['STATUS'] + '</td><td class="col3">' + item['REASON'] + '</td><td class="col4">' + item['DRAWER'] + '</td><td class="col5">' + item['LOCATION'] + '</td><tr>';
			}
		});
		
		html += '</tbody></table>';
		
		modalContent.innerHTML = html;
		modal.style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	select tool from Tool To list
	  *	@param	none
	  *	@return	none
	*/
	function selectToolToRow(tr) {
		var trs = document.getElementById('tool-table').children;
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectToolToRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmToolTo(this)');
	}
	
	/**
	  *	@desc	confirm tool from Tool To list
	  *	@param	none
	  *	@return	none
	*/
	function confirmToolTo(tr) {
		document.getElementById('tool-to').value = tr.children[0].innerHTML;
		document.getElementById('close').click();
	}
</script>
<html>
	<head>
		<title>Reports</title>
		<link rel="stylesheet" type="text/css" href="/styles/reports.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="if ('<?=$_POST['from']?>' == 'conditions.php') { document.getElementsByClassName('controls')[0].children[0].click(); }">
		<div class="outer">
			<div class="inner">
				<div class="header"><h2>Report Menu</h2></div>
				<select id="report-select" name="report">
					<option value="Daily Operations - Eform">Daily Operations - Eform</option>
					<option value="Daily Operations - Troom">Daily Operations - Troom</option>
					<option value="E-Forming Cycle">E-Forming Cycle</option>
					<option value="Fresnel Eform Yield">Fresnel Eform Yield</option>
					<option value="Nickel Usage">Nickel Usage</option>
					<option value="Reflexite Eform Yield">Reflexite Eform Yield</option>
					<option value="Tank Status Report" <?php if ($_POST['from'] == "conditions.php") { echo "selected"; } ?>>Tank Status Report</option>
					<option value="Tool Listing Report">Tool Listing Report</option>
					<option value="Toolroom Production Yield">Toolroom Production Yield</option>
					<option value="In Progress Report">In Progress Report</option>
				</select>
				<div class="controls">
					<button onclick="getDetails()">Select</button>
					<a href="<?php if ($_POST['from'] == "conditions.php") { echo "conditions.php"; } else { echo "home.php"; } ?>">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>