<!DOCTYPE html>
<?php
/**
  *	@desc tank stress data from the last 6 months
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//tank stress data list
	$conditions = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Tank_Stress WHERE DATE > DATEADD(month,-6,GETDATE());");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$conditions[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		echo "Error: could not connect to database.";
		var_dump(sqlsrv_errors());
	}
?>
<script src="/scripts/cookies.js"></script>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//set up tracking variables
	var selectedRow = -1;
	var conditions = [<?php
		foreach($conditions as $condition) {
			echo '{';
			foreach($condition as $id=>$value) {
				echo '"' . $id . '": `';
				if ($value instanceof DateTime) {
					echo addslashes(trim(str_replace(array("\n","\t","\r"), ' ', date_format($value, "m/d/y H:i"))));
				} else {
					echo addslashes(trim(str_replace(array("\n","\t","\r"), ' ', $value)));
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	//up/down buttons scroll through list
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	document.onkeydown = function(evt) {
		evt = evt || window.event;
		var charCode = evt.keyCode || evt.which;
		if (charCode == "40" && document.getElementById(selectedRow).nextElementSibling) {
			document.getElementById(selectedRow).nextElementSibling.click();
		} else if (charCode == "38" && document.getElementById(selectedRow).previousElementSibling) {
			document.getElementById(selectedRow).previousElementSibling.click();
		} else {
			return;
		}
		document.getElementById(selectedRow).scrollIntoView();
	}
	
	/**
	  *	@desc	open sort/filter box, if session variable exists and is true
	  *	@param	none
	  *	@return	none
	*/
	function checkSortBox() {
		if (checkCookie("sort_expanded") && getCookie("sort_expanded") == "true") {
			document.getElementById("arrow").click();
			document.getElementsByClassName("filter-inner")[0].children[2].click();
		}
	}
	
	/**
	  *	@desc	highlight selected row, unhighlight others
	  *	@param	DOM Object tr - table row clicked on
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
		find(selectedRow);
	}
	
	/**
	  *	@desc	find and display details on tank stress
	  *	@param	int id - row id to search for
	  *	@return	none
	*/
	function find(id) {
		for(var i=0;i<conditions.length;i++) {
			if (conditions[i]['ID'] == id) {
				document.getElementById("tank-input").value = conditions[i]['TANK'];
				document.getElementById("date-input").value = conditions[i]['DATE'];
				document.getElementById("ratio-input").value = conditions[i]['IAUX_MAIN'];
				document.getElementById("strip-input").value = conditions[i]['STRIP'];
				document.getElementById("time-input").value = conditions[i]['TIME'];
				document.getElementById("constant-input").value = conditions[i]['CONSTANT'];
				document.getElementById("units-input").value = conditions[i]['UNITS'];
				document.getElementById("imain-input").value = conditions[i]['MAIN'];
				document.getElementById("iaux-input").value = conditions[i]['I_AUX'];
				document.getElementById("uaux-input").value = conditions[i]['U_AUX'];
				document.getElementById("remarks").value = conditions[i]['REMARK'];
				document.getElementById("operator-input").value = conditions[i]['OPERATOR'];
				break;
			}
		}
	}
	
	/**
	  *	@desc	prep fields for inserting new stress data
	  *	@param	none
	  *	@return	none
	*/
	function newCondition() {
		document.getElementById("tank-input").readOnly = false;
		document.getElementById("tank-input").value = "";
		document.getElementById("date-input").readOnly = false;
		document.getElementById("date-input").value = formatDateTime(new Date());
		document.getElementById("ratio-input").readOnly = false;
		document.getElementById("ratio-input").value = "";
		document.getElementById("strip-input").readOnly = false;
		document.getElementById("strip-input").value = "";
		document.getElementById("time-input").readOnly = false;
		document.getElementById("time-input").value = "";
		document.getElementById("constant-input").readOnly = false;
		document.getElementById("constant-input").value = "";
		document.getElementById("units-input").readOnly = false;
		document.getElementById("units-input").value = "";
		document.getElementById("imain-input").readOnly = false;
		document.getElementById("imain-input").value = "";
		document.getElementById("iaux-input").readOnly = false;
		document.getElementById("iaux-input").value = "";
		document.getElementById("uaux-input").readOnly = false;
		document.getElementById("uaux-input").value = "";
		document.getElementById("remarks").readOnly = false;
		document.getElementById("remarks").value = "";
		var user = "<?php echo $_SESSION['name']; ?>";
		document.getElementById("operator-input").value = (user != "eform" && user != "master" && user != "troom") ? "<?=$_SESSION['initials']?>" : "";
		document.getElementById("operator-input").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveCondition(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep fields for editing stress data
	  *	@param	none
	  *	@return	none
	*/
	function editCondition() {
		document.getElementById("tank-input").readOnly = false;
		document.getElementById("date-input").readOnly = false;
		document.getElementById("ratio-input").readOnly = false;
		document.getElementById("strip-input").readOnly = false;
		document.getElementById("time-input").readOnly = false;
		document.getElementById("constant-input").readOnly = false;
		document.getElementById("units-input").readOnly = false;
		document.getElementById("imain-input").readOnly = false;
		document.getElementById("iaux-input").readOnly = false;
		document.getElementById("uaux-input").readOnly = false;
		document.getElementById("remarks").readOnly = false;
		var user = "<?php echo $_SESSION['name']; ?>";
		document.getElementById("operator-input").value = (user != "eform" && user != "master" && user != "troom") ? "<?=$_SESSION['initials']?>" : "";
		document.getElementById("operator-input").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveCondition(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - MM/DD/YY H:i:s
	*/
	function formatDateTime(d) {
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
		var second = d.getSeconds();
		if (second < 10) {
			second = "0" + second;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString() + " " + hour.toString() + ":" + minute.toString() + ":" + second.toString();
		
		return date;
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("tank-input").value == "") {
			msg = "Please enter a valid tank";
		} else if (document.getElementById("date-input").value == "") {
			msg = "Please enter a date";
		} else if (document.getElementById("operator-input").value == "") {
			msg = "Please enter your initials";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	save new or edited stress data
	  *	@param	string s - either add or edit
	  *	@return	none
	*/
	function saveCondition(s) {
		var msg = checkFields();
		
		if (msg == "") {
			var conn = new XMLHttpRequest();
			var action = s == "add" ? "insert" : "update";
			var table = "Tank_Stress";
			if (selectedRow >= 0) {
				var id = selectedRow;
			} else { 
				var id = 0;
			}
			var data = {
				TANK: document.getElementById("tank-input").value,
				IAUX_MAIN: document.getElementById("ratio-input").value || "0.0000",
				STRIP: document.getElementById("strip-input").value,
				TIME: document.getElementById("time-input").value,
				CONSTANT: document.getElementById("constant-input").value,
				UNITS: document.getElementById("units-input").value || "0.0000",
				MAIN: document.getElementById("imain-input").value || "0.0000",
				I_AUX: document.getElementById("iaux-input").value || "0.0000",
				U_AUX: document.getElementById("uaux-input").value || "0.0000",
				REMARK: document.getElementById("remarks").value,
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDateTime(new Date(document.getElementById("date-input").value)),
				condition: 'id',
				value: id
			}
			
			var query = '';
			
			Object.keys(data).forEach((item, index, array) => {
				query += `&${item}=${data[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[`]/g, "")}`;
			});
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						alert("Tank condition added");
						document.getElementsByTagName("body")[0].innerHTML += `<form id="reload-form" method="POST" action="conditions.php" style="display: none;"><input type="text" name="tank" value="${data.TANK}"><input type="text" name="date" value="${data.DATE.split(" ")[0]}"></form>`;
						document.getElementById('reload-form').submit();
					} else if (conn.responseText.includes("Data updated")) {
						alert("Tank condition updated");
						document.getElementsByTagName("body")[0].innerHTML += `<form id="reload-form" method="POST" action="conditions.php" style="display: none;"><input type="text" name="tank" value="${data.TANK}"><input type="text" name="date" value="${data.DATE.split(" ")[0]}"></form>`;
						document.getElementById('reload-form').submit();
					} else {
						alert(conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, true);
			conn.send();
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	cancel new or edited stress data
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("tank-input").readOnly = true;
		document.getElementById("date-input").readOnly = true;
		document.getElementById("ratio-input").readOnly = true;
		document.getElementById("strip-input").readOnly = true;
		document.getElementById("time-input").readOnly = true;
		document.getElementById("constant-input").readOnly = true;
		document.getElementById("units-input").readOnly = true;
		document.getElementById("imain-input").readOnly = true;
		document.getElementById("iaux-input").readOnly = true;
		document.getElementById("uaux-input").readOnly = true;
		document.getElementById("remarks").readOnly = true;
		document.getElementById("operator-input").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','editCondition()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteCondition()');
		find(selectedRow);
	}
	
	/**
	  *	@desc	remove stress data
	  *	@param	none
	  *	@return	none
	*/
	function deleteCondition() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Tank_Stress";
		var id = selectedRow;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Deletion succeeded.");
					window.location.replace("conditions.php");
				} else {
					alert(conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	auto-format date fields to MM/DD/YY
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
		} else if (input.parentNode.id.includes("filter") && event.key == "Enter") {
			input.parentNode.nextElementSibling.click();
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
	  *	@desc	move to reports page
	  *	@param	none
	  *	@return	none
	*/
	function report() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="reports-form" action="/view/reports.php" method="POST" style="display: none;"><input type="text" value="conditions.php" name="from"></form>`;
		document.getElementById("reports-form").submit();
	}
	
	/**
	  *	@desc	show filter options
	  *	@param	none
	  *	@return	none
	*/
	function showFilters() {
		var div = document.createElement("div");
		div.classList.add("filter-outer");
		div.innerHTML = `<div class="filter-inner">
							<div id="order-container">
								<span id="order-span">Order</span>
								<br>
								<select id="order-type" name="order-select">
									<option value="none"></option>
									<option value="TANK/DATE">TANK/DATE</option>
									<option value="DATE/TANK">DATE/TANK</option>
									<option value="TANK/DESCENDING DATE">TANK/DESCENDING DATE</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none"></option>
									<option value="TANK">TANK</option>
									<option value="DATE">DATE</option>
								</select>
								<br>
								<input type="text" id="filter-input">
							</div>
							<button onclick="sortBy(document.getElementById('order-type').value)">Go</button>
						</div>`;
		document.getElementsByClassName("container")[0].appendChild(div);
		var arrow = document.getElementById("arrow");
		div.after(arrow);
		arrow.children[0].classList.remove("right-arrow");
		arrow.children[0].classList.add("left-arrow");
		arrow.setAttribute("onclick",'hideFilters()');
		
		setCookie("sort_expanded","true");
		
		if (checkCookie("sort_conditions_order")) {
			document.getElementById("order-type").value = getCookie("sort_conditions_order");
		}
		
		if (checkCookie("sort_conditions_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_conditions_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_conditions_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_conditions_filter_value");
		}
	}
	
	/**
	  *	@desc	hide filter options
	  *	@param	none
	  *	@return	none
	*/
	function hideFilters() {
		document.getElementsByClassName("container")[0].removeChild(document.getElementsByClassName("filter-outer")[0]);
		var arrow = document.getElementById("arrow");
		arrow.children[0].classList.add("right-arrow");
		arrow.children[0].classList.remove("left-arrow");
		arrow.setAttribute("onclick",'showFilters()');
		
		setCookie("sort_exepanded","false");
	}
	
	/**
	  *	@desc	change filter field type
	  *	@param	none
	  *	@return	none
	*/
	function changeFilter(select) {
		var field = document.getElementById("filter-input");
		if (field) {
			document.getElementById("filter-container").removeChild(field);
		}
		var input = document.createElement('input');
		input.type = "text";
		input.id = "filter-input";
		if (select.value == "DATE") {
			input.setAttribute("onkeydown","fixDate(this)");
		} else {
			input.onkeydown = function(e) {
				if (e.key == "Enter") {
					input.parentNode.nextElementSibling.click();
				}
			}
		}
		document.getElementById("filter-container").appendChild(input);
	}
	
	/**
	  *	@desc	sorts batches array by selected option
	  *	@param	string value - option to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_conditions_order",document.getElementById("order-type").value);
		setCookie("sort_conditions_filter",document.getElementById("filter-type").value);
		setCookie("sort_conditions_filter_value",document.getElementById("filter-input").value);
		
		conditions.sort(function(a, b) {
			
			switch(value) {
				case "TANK/DATE":
					if (parseInt(a['TANK']) < parseInt(b['TANK'])) {
						return -1;
					} else if (parseInt(a['TANK']) > parseInt(b['TANK'])) {
						return 1;
					} else {
						var ad = new Date(a['DATE'].split(" ")[0]);
						var bd = new Date(b['DATE'].split(" ")[0]);
						if (ad < bd) {
							return -1;
						} else if (bd < ad) {
							return 1;
						} else {
							return 0;
						}
					}
					break;
				case "DATE/TANK":
					var ad = new Date(a['DATE'].split(" ")[0]);
					var bd = new Date(b['DATE'].split(" ")[0]);
					
					if (ad < bd) {
						return -1;
					} else if (bd < ad) {
						return 1;
					} else {
						if (parseInt(a['TANK']) < parseInt(b['TANK'])) {
							return -1;
						} else if (parseInt(b['TANK']) < parseInt(a['TANK'])) {
							return 1;
						} else {
							return 0;
						}
					}
					break;
				case "TANK/DESCENDING DATE":
					if (parseInt(a['TANK']) < parseInt(b['TANK'])) {
						return -1;
					} else if (parseInt(a['TANK']) > parseInt(b['TANK'])) {
						return 1;
					} else {
						var ad = new Date(a['DATE'].split(" ")[0]);
						var bd = new Date(b['DATE'].split(" ")[0]);
						if (ad < bd) {
							return 1;
						} else if (bd < ad) {
							return -1;
						} else {
							return 0;
						}
					}
					break;
				default:
					if (parseInt(a['ID']) < parseInt(b['ID'])) {
						return -1;
					} else if (parseInt(b['ID']) < parseInt(a['ID'])) {
						return 1;
					} else {
						return 0;
					}
			}
		});
		
		fillSort();
	}
	
	/**
	  *	@desc	fills in newly sorted array
	  *	@param	none
	  *	@return	none
	*/
	function fillSort() {
		var tbody = document.getElementsByClassName("main")[0].children[0].children[1];
		var html = "";
		
		conditions.forEach((item, index, array) => {
			if (isAllowed(document.getElementById("filter-input").value.toUpperCase(), document.getElementById("filter-type").value, item)) {
				html += `<tr id="${item['ID']}" onclick="selectRow(this)">
										<td class="col1">${item['TANK']}</td>
										<td class="col2">${item['DATE']}</td>
										<td class="col3">${item['STRIP']}</td>
										<td class="col4">${item['TIME']}</td>
										<td class="col5">${item['CONSTANT']}</td>
										<td class="col6">${item['UNITS']}</td>
										<td class="col7">${item['MAIN']}</td>
										<td class="col8">${item['I_AUX']}</td>
										<td class="col9">${item['U_AUX']}</td>
										<td class="col10">${item['IAUX_MAIN']}</td>
										<td class="col11">${item['REMARK']}</td>
									</tr>`;
			}
		});
		
		tbody.innerHTML = html;
	}
	
	/**
	  *	@desc	allows rows if they match the filter values
	  *	@param	string keyword - keyword to filter by, string value - column to filter by, array row - row to check
	  *	@return true if match, false otherwise
	*/
	function isAllowed(keyword, value, row) {
		var valid = false;
		
		switch(value) {
			case "DATE":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(row['DATE'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['DATE'].includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "TANK":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,"."),"g").test(row['TANK'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['TANK'].includes(keyword)) {
						valid = true;
					}
				}
				break;
			default:
				valid = true;
		}
		
		return valid;
	}
	
	/**
	  *	@desc	find row from date and tank
	  *	@param	none
	  *	@return	none
	*/
	function findRow() {
		for (var i=0;i<conditions.length;i++) {
			if (conditions[i]['DATE'].split(" ")[0] == "<?=$_POST['date']?>" && conditions[i]['TANK'] == parseInt("<?=$_POST['tank']?>")) {
				document.getElementById(conditions[i]['ID']).click();
				document.getElementById(conditions[i]['ID']).scrollIntoView();
				break;
			} 
		}
	}
	
	/**
	  *	@desc	go back to electroforming page
	  *	@param	none
	  *	@return	none
	*/
	function backToEform() {
		document.body.innerHTML += `<form style="display: none;" id="eform-form" action="operations/electroforming.php" method="POST"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
		document.getElementById("eform-form").submit();
	}
	
	/**
	  *	@desc	auto calculate I_Aux/Imain based on other inputs
	  *	@param	none
	  *	@return	none
	*/
	function setIauxImain() {
		var iaux = document.getElementById('iaux-input');
		var imain = document.getElementById('imain-input');
		if (iaux.readOnly == false && imain.readOnly == false) {
			if (iaux.value != '' && imain.value != '') {
				if (imain.value == 0) {
					document.getElementById('ratio-input').value = 0;
				} else {
					document.getElementById('ratio-input').value = iaux.value / imain.value;
				}
			}
		}
	}
</script>
<html>
	<head>
		<title>Tank Conditions</title>
		<link rel="stylesheet" type="text/css" href="/styles/conditions.css">
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	</head>
	<body onload="checkSortBox();<?php if (isset($_POST['date'])) { ?> findRow();<?php } ?>">
		<div class="container">
			<div class="outer">
				<div class="inner">
					<div class="main">
						<table id="table">
							<thead>
								<tr>
									<th class="col1">Tank</th>
									<th class="col2">Date</th>
									<th class="col3">Strip</th>
									<th class="col4">Time</th>
									<th class="col5">Constant</th>
									<th class="col6">Units</th>
									<th class="col7">Imain</th>
									<th class="col8">I_Aux</th>
									<th class="col9">U_Aux</th>
									<th class="col10">I_Aux/Imain</th>
									<th class="col11">Remarks</th>
								</tr>
							</thead>
							<tbody id="tbody">
								<?php foreach($conditions as $condition) { ?>
								<tr id="<?=$condition['ID']?>" onclick="selectRow(this)">
									<td class="col1"><?=$condition['TANK']?></td>
									<td class="col2"><?=date_format($condition['DATE'],"m/d/y H:i")?></td>
									<td class="col3"><?=$condition['STRIP']?></td>
									<td class="col4"><?=$condition['TIME']?></td>
									<td class="col5"><?=$condition['CONSTANT']?></td>
									<td class="col6"><?=$condition['UNITS']?></td>
									<td class="col7"><?=$condition['MAIN']?></td>
									<td class="col8"><?=$condition['I_AUX']?></td>
									<td class="col9"><?=$condition['U_AUX']?></td>
									<td class="col10"><?=$condition['IAUX_MAIN']?></td>
									<td class="col11"><?=$condition['REMARK']?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="left">
						<div class="top">
							<span>Tank<input type="text" id="tank-input" readonly></span>
							<span>Date<input onkeydown="fixDate(this)" type="text" id="date-input" readonly></span>
							<span>I_Aux/Imain<input type="text" id="ratio-input" readonly></span>
							<span>Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input" style="width: 150px;" readonly></span>
						</div>
						<div class="bottom">
							<div class="bottom-top">
								<span style="margin-left: 5px;">Strip (A)<input type="text" id="strip-input" readonly></span>
								<span>Time (min)<input type="text" id="time-input" readonly></span>
								<span>Constant<input type="text" id="constant-input" readonly></span>
								<span>Units<input type="text" id="units-input" readonly></span>
							</div>
							<div class="bottom-middle">
								<span>Imain (A)<input type="text" id="imain-input" onblur="setIauxImain()" readonly></span>
								<span style="margin-left: 30px;">I_Aux<input type="text" id="iaux-input" onblur="setIauxImain()" readonly></span>
								<span style="margin-left: 14px;">U_Aux<input type="text" id="uaux-input" readonly></span>
							</div>
							<div class="bottom-bottom">
								<span id="remarks-span">Remarks</span><textarea rows="3" cols="50" id="remarks" readonly></textarea>
							</div>
						</div>
					</div>
					<div class="controls">
						<button id="add" onclick="newCondition()">Add</button>
						<button id="edit" onclick="editCondition()">Edit</button>
						<button id="delete" onclick="deleteCondition()">Delete</button>
						<button onclick="report()">Tank Status Report</button>
						<?php if (isset($_POST['source'])) { ?>
						<button onclick="backToEform()">Back</button>
						<?php } else { ?>
						<a href="home.php">Back</a>
						<?php } ?>
					</div>
				</div>
			</div>
			<div id="arrow" onclick="showFilters()">
		 		<div class="right-arrow">
				</div>
		 	</div>
		</div>
	</body>
</html>