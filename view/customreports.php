<?php
/**
  * @desc for building custom report queries
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//db schema variables
	$tables = array('Batches','Comment_History','Customers','Designs','Electroforming','Electroforming_History','Electroforming_Queue','Mastering','Mastering_History','Mastering_Queue','Processes','Shipping','Shipping_History','Shipping_Queue','Tank_Stress','Tool_Status_History','Tool_Tree','Toolroom','Toolroom_History','Toolroom_Queue');
	$columns = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME IN ('" . implode("','",$tables) . "') AND COLUMN_NAME <> 'ID' ORDER BY TABLE_NAME, COLUMN_NAME;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$columns[] = $row;
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
	
	var columns = [<?php
		foreach($columns as $column) {
			echo "[\"" . implode($column,"\",\"") . "\"],";
		}
	?>];
	
	function addTable(button) {
		var div = document.createElement('div');
		div.innerHTML = `<select class="table-select select-tables" onchange="setConditionsTables(); setJoinTables(); setSortTables(); setColumnList();">
							<?php foreach($tables as $table) {
								echo "<option value=\"$table\">$table</option>";
							} ?>
						</select>
						<button onclick="removeTable(this.parentNode)">Remove Table</button>`;
		div.classList.add("first");
		div.classList.add("table-div");
		var br = document.createElement('br');
		button.parentNode.insertBefore(div, button);
		button.parentNode.insertBefore(br, button);
		
		setConditionsTables();
		setSortTables();
		setColumnList();
		
		var counter = document.getElementsByClassName("select-tables").length;
		var joinCounter = document.getElementsByClassName("join-div").length;
		document.getElementsByClassName('join-container')[0].style.display = "block";
		if (joinCounter < counter-1) {
			document.getElementById('add-join').click();
		}
	}
	
	function removeTable(div) {
		div.parentNode.removeChild(div.previousElementSibling);
		div.parentNode.removeChild(div);
		
		setConditionsTables();
		setSortTables();
		setColumnList();
		
		if (document.getElementsByClassName('select-tables').length < 2) {
			document.getElementsByClassName('join-container')[0].style.display = "none";
			var joins = document.getElementsByClassName('join-div');
			for (var i=0;i<joins.length;i++) {
				joins[i].parentNode.removeChild(joins[i].previousElementSibling);
				joins[i].parentNode.removeChild(joins[i]);
			}
		} else {
			var joins = document.getElementsByClassName('join-div');
			for (var i=0;i<joins.length;i++) {
				var joinTables = joins[i].getElementsByClassName('table-select');
				if (joinTables[0].value == div.getElementsByClassName('select-tables')[0].value || joinTables[1].value == div.getElementsByClassName('select-tables')[0].value) {
					var selectTables = document.getElementsByClassName("select-tables");
					var keep1 = false;
					var keep2 = false;
					for (var j=0;j<selectTables.length;j++) {
						if (selectTables[j].value == joinTables[0].value) {
							keep1 = true;
						} 
						if (selectTables[j].value == joinTables[1].value) {
							keep2 = true;
						}
					}
					if (!keep1 || !keep2) {
						joins[i].parentNode.removeChild(joins[i].previousElementSibling);
						joins[i].parentNode.removeChild(joins[i]);
						i--;
					}
				}
			}
		}
	}
	
	function addJoin(button) {
		var tables = document.getElementsByClassName("select-tables");
		var div2 = document.createElement("div");
		html = `<select class="table-select join-tables" onchange="setJoinColumns(this)">`;
		for (var i=0;i<tables.length;i++) {
			html += `<option value="${tables[i].value}">${tables[i].value}</option>`;
		}
		html += `</select>
				 <select class="column-select join-columns">`;
		for (var i=0;i<columns.length;i++) {
			if (columns[i][0] == tables[0].value) {
				html += `<option value="${columns[i][1]}">${columns[i][1]}</option>`;
			}
		}
		html+= `</select>
				<span>=</span>
				<select class="table-select join-tables" onchange="setJoinColumns(this)">`;
		for (var i=0;i<tables.length;i++) {
			html += `<option value="${tables[i].value}"}>${tables[i].value}</option>`;
		}
		html += `</select>
				 <select class="column-select join-columns">`;
		for (var i=0;i<columns.length;i++) {
			if (columns[i][0] == tables[0].value) {
				html += `<option value="${columns[i][1]}">${columns[i][1]}</option>`;
			}
		}
		html += `</select>
				 <button onclick="removeJoin(this.parentNode)">Remove</button>`;
		div2.innerHTML = html;
		div2.classList.add("join-div");
		button.parentNode.insertBefore(div2,button);
		var br = document.createElement('br');
		button.parentNode.insertBefore(br,button);
	}
	
	function removeJoin(div) {
		if (document.getElementsByClassName('column-join').length > 1) {
			div.parentNode.removeChild(div.previousElementSibling);
		}
		div.parentNode.removeChild(div);
	}
	
	function addCondition(button) {
		var counter = document.getElementsByClassName("conditions-div").length;
		var tables = document.getElementsByClassName("select-tables");
		var div2 = document.createElement("div");
		html = `<select class="table-select table-conditions" onchange="setConditionsColumns(this);">`;
		for (var i=0;i<tables.length;i++) {
			html += `<option value="${tables[i].value}">${tables[i].value}</option>`;
		}
		html += `</select>
				 <select class="column-select column-conditions">`;
		for (var i=0;i<columns.length;i++) {
			if (columns[i][0] == tables[0].value) {
				html += `<option value="${columns[i][1]}">${columns[i][1]}</option>`;
			}
		}
		html+= `</select>
				<select class="conditions-operator-select">
					<option value="=">=</option>
					<option value="<>">!=</option>
					<option value="<">&lt;</option>
					<option value="<=">&lt;=</option>
					<option value=">">&gt;</option>
					<option value=">=">&gt;=</option>
					<option value="LIKE">LIKE</option>
				</select>
				<input type="text" class="condition-value">
				<input class="radio" type="radio" name="date-type${counter+1}" value="text"> Plain Text
				<input class="radio" type="radio" name="date-type${counter+1}" value="date"> Date
				<input class="radio" type="radio" name="date-type${counter+1}" value="datetime"> Date & Time
				<button onclick="removeCondition(this.parentNode)">Remove</button>`;
		div2.innerHTML = html;
		div2.className = "conditions-div";
		button.parentNode.insertBefore(div2,button);
		if (counter > 0) {
			var div3 = document.createElement("div");
			div3.className = "conditions-div";
			div3.innerHTML = `<select class="and-or-select"><option value="AND">AND</option><option value="OR">OR</option>`;
			button.parentNode.insertBefore(div3,div2);
		}
	}
	
	function removeCondition(div) {
		if (document.getElementsByClassName('column-conditions').length > 1) {
			div.parentNode.removeChild(div.previousElementSibling);
		}
		div.parentNode.removeChild(div);
	}
	
	function setConditionsTables() {
		var tables = document.getElementsByClassName("select-tables");
		var tablesValues = [];
		for (var i=0;i<tables.length;i++) {
			tablesValues[i] = tables[i].value;
		}
		
		var selects = document.getElementsByClassName("table-conditions");
		for (var i=0;i<selects.length;i++) {
			selects[i].innerHTML = '';
			for (var j=0;j<tablesValues.length;j++) {
				selects[i].innerHTML += `<option value="${tablesValues[j]}">${tablesValues[j]}</option>`;
			}
		}
		
		setConditionsColumns(null);
	}
	
	function setConditionsColumns(select) {
		if (select == null) {
			var selects = document.getElementsByClassName("column-conditions");
			for (var i=0;i<selects.length;i++) {
				selects[i].innerHTML = '';
				for(var j=0;j<columns.length;j++) {
					if (columns[j][0] == selects[i].previousElementSibling.value) {
						selects[i].innerHTML += `<option value="${columns[j][1]}">${columns[j][1]}</option>`;
					}
				}
			}
		} else {
			select.nextElementSibling.innerHTML = '';
			for (var i=0;i<columns.length;i++) {
				if (columns[i][0] == select.value) {
					select.nextElementSibling.innerHTML += `<option value="${columns[i][1]}">${columns[i][1]}</option>`;
				}
			}
		}
	}
	
	function setJoinTables() {
		var tables = document.getElementsByClassName("select-tables");
		var tablesValues = [];
		for (var i=0;i<tables.length;i++) {
			tablesValues[i] = tables[i].value;
		}
		
		var selects = document.getElementsByClassName("join-tables");
		for (var i=0;i<selects.length;i++) {
			selects[i].innerHTML = '';
			for (var j=0;j<tablesValues.length;j++) {
				selects[i].innerHTML += `<option value="${tablesValues[j]}">${tablesValues[j]}</option>`;
			}
		}
		
		setJoinColumns();
	}
	
	function setJoinColumns(select) {
		if (select == null) {
			var selects = document.getElementsByClassName("join-columns");
			for (var i=0;i<selects.length;i++) {
				selects[i].innerHTML = '';
				for (var j=0;j<columns.length;j++) {
					if (columns[j][0] == selects[i].previousElementSibling.value) {
						selects[i].innerHTML += `<option value="${columns[j][1]}">${columns[j][1]}</option>`;
					}
				}
			}
		} else {
			select.nextElementSibling.innerHTML = '';
			for (var i=0;i<columns.length;i++) {
				if (columns[i][0] == select.value) {
					select.nextElementSibling.innerHTML += `<option value="${columns[i][1]}">${columns[i][1]}</option>`;
				}
			}
		}
	}
	
	function setSortTables() {
		var tables = document.getElementsByClassName("select-tables");
		var tablesValues = [];
		for (var i=0;i<tables.length;i++) {
			tablesValues[i] = tables[i].value;
		}
		
		var selects = document.getElementsByClassName("sort-tables");
		for (var i=0;i<selects.length;i++) {
			selects[i].innerHTML = '';
			for (var j=0;j<tablesValues.length;j++) {
				selects[i].innerHTML += `<option value="${tablesValues[j]}">${tablesValues[j]}</option>`;
			}
		}
		
		setSortColumns();
	}
	
	function setSortColumns() {
		var select = document.getElementsByClassName("sort-columns")[0];
		select.innerHTML = '';
		for(var i=0;i<columns.length;i++) {
			if (columns[i][0] == select.previousElementSibling.value) {
				select.innerHTML += `<option value="${columns[i][1]}">${columns[i][1]}</option>`;
			}
		}
	}
	
	function setColumnList() {
		var tables = document.getElementsByClassName("select-tables");
		var tablesValues = [];
		for (var i=0;i<tables.length;i++) {
			tablesValues[i] = tables[i].value;
		}
		var columnList = document.getElementsByClassName("columns-select")[0];
		columnList.innerHTML = '';
		for (var i=0;i<columns.length;i++) {
			if (tablesValues.includes(columns[i][0])) {
				columnList.innerHTML += `<div onclick="selectColumn(this)" class="column">${columns[i][0]}.${columns[i][1]}</div>`;
			}
		}
	}
	
	function selectColumn(div) {
		if (div.style.color == "white") {
			div.style.color = "black";
			div.style.backgroundColor = "white";
		} else {
			div.style.color = "white";
			div.style.backgroundColor = "black";
		}
	}
	
	function buildQuery() {
		var tables = document.getElementsByClassName("select-tables");
		var query = "SELECT ";
		
		var possibleColumns = document.getElementsByClassName("column");
		var selectedColumns = [];
		for (var i=0;i<possibleColumns.length;i++) {
			if (possibleColumns[i].style.color == "white") {
				selectedColumns.push(possibleColumns[i].innerHTML);
			}
		}
		
		query += selectedColumns.join(", ");
		
		if (document.getElementsByClassName("join-container")[0].style.display == "block") {
			var tables = document.getElementsByClassName("select-tables");
			var joins = document.getElementsByClassName("join-div");
			var joinConditions = [];
			var usedTables = [];
			query += " FROM " + tables[0].value;
			usedTables.push(tables[0].value);
			for (var i=1;i<tables.length;i++) {
				joinConditions = [];
				query += " INNER JOIN " + tables[i].value + " ON ";
				for (var j=0;j<joins.length;j++) {
					if ((joins[j].getElementsByClassName('table-select')[0].value == tables[i].value && usedTables.includes(joins[j].getElementsByClassName('table-select')[1].value)) || (joins[j].getElementsByClassName('table-select')[1].value == tables[i].value && usedTables.includes(joins[j].getElementsByClassName('table-select')[0].value))) {
						var table1 = joins[j].getElementsByClassName('table-select')[0].value;
						var table2 = joins[j].getElementsByClassName('table-select')[1].value;
						var column1 = joins[j].getElementsByClassName('column-select')[0].value;
						var column2 = joins[j].getElementsByClassName('column-select')[1].value;
						joinConditions.push(table1 + "." + column1 + " = " + table2 + "." + column2);
					}
				}
				query += joinConditions.join(" AND ");
				usedTables.push(tables[i].value);
			}
		} else {
			query += " FROM " + tables[0].value;
		}
		
		var conditionsTables = document.getElementsByClassName("table-conditions");
		var conditionsColumns = document.getElementsByClassName("column-conditions");
		var conditionsOperators = document.getElementsByClassName("conditions-operator-select");
		var conditionsValues = document.getElementsByClassName("condition-value");
		var conditionsAndOr = document.getElementsByClassName("and-or-select");
		for (var i=0;i<conditionsValues.length;i++) {
			if (conditionsValues[i].value != "") {
				var dataTypes = document.getElementsByClassName("radio");
				if (i==0) {
					query += " WHERE " + conditionsTables[i].value + "." + conditionsColumns[i].value + " " + conditionsOperators[i].value + " ";
					if (dataTypes[i*3+1].checked) {
						query += "CAST('" + conditionsValues[i].value + "' AS DATE)";
					} else if (dataTypes[i*3+2].checked) {
						query += "CONVERT(DATETIME,'" + conditionsValues[i].value + "',1)";
					} else {
						query += "'" + conditionsValues[i].value + "'";
					}
				} else {
					query += " " + conditionsAndOr[i-1].value + " " + conditionsTables[i].value + "." + conditionsColumns[i].value + " " + conditionsOperators[i].value + " ";
					if (dataTypes[i*3+1].checked) {
						query += "CAST('" + conditionsValues[i].value + "' AS DATE)";
					} else if (dataTypes[i*3+2].checked) {
						query += "CONVERT(DATETIME,'" + conditionsValues[i].value + "',1)";
					} else {
						query += "'" + conditionsValues[i].value + "'";
					}
				}
			}
		}
		
		var sortTable = document.getElementsByClassName("sort-tables")[0].value;
		var sortColumn = document.getElementsByClassName("sort-columns")[0].value;
		var sortOrder = document.getElementsByClassName("sort-order")[0].value;
		query += " ORDER BY " + sortTable + "." + sortColumn + " " + sortOrder + ";";
		//console.log(query);
		sendQuery(selectedColumns,query);
	}
	
	function sendQuery(selectedColumns,query) {
		if (query.toUpperCase().includes("UPDATE") || query.toUpperCase().includes("DROP") || query.toUpperCase().includes("INSERT") || query.toUpperCase().includes("DELETE")|| query.toUpperCase().includes("--")) {
			alert("No SQL Injection!");
		} else {
			var conn = new XMLHttpRequest();
			var div = document.getElementById("reply");
			
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					div.innerHTML = this.responseText;
				}
			}
			
			conn.open("GET","customreport.php?query="+query+"&columns="+selectedColumns,false);
			conn.send();
		}
	}
</script>
<html>
	<head>
		<title>Custom Reports</title>
		<link rel="stylesheet" href="/styles/reports/customreports.css"></link>
	</head>
	<body>
		<div class="inner">
			<div class="table-container">
				<span>Select from:</span><br>
				<div class="first table-div">
					<select class="table-select select-tables" onchange="setConditionsTables(); setJoinTables(); setSortTables(); setColumnList();">
						<?php foreach($tables as $table) {
							echo "<option value=\"$table\">$table</option>";
						} ?>
					</select>
				</div><br>
				<button class="add-table" onclick="addTable(this)">Add Table</button><br>
			</div>
			<div class="join-container">
				<span>Join Conditions:</span><br>
				<button id="add-join" class="add-join" onclick="addJoin(this)">Add Join Condition</button>
			</div>
			<div class="conditions-container">
				<span>Conditions:</span><br>
				<span style="margin-left: 10px;">With the 'LIKE' keyword, use '%' for 0 or more unknown characters, or '_' for only one</span><br>
				<span style="margin-left: 10px;">Note that AND clauses are always operated first</span><br>
				<button class="add-condition" onclick="addCondition(this)">Add Condition</button>
			</div>
			<div class="order-container">
				<span>Sort by:</span>
				<div class="order-div">
					<select class="table-select sort-tables" onchange="setSortColumns()">
						<option value="Batches">Batches</option>
					</select>
					<select class="column-select sort-columns">
						<?php foreach($columns as $column) {
							if ($column[0] == $tables[0]) {
								echo "<option value=\"$column[1]\">$column[1]</option>";
							}
						} ?>
					</select>
					<select class="order-select sort-order">
						<option value="ASC">Ascending</option>
						<option value="DESC">Descending</option>
					</select>
				</div>
			</div>
			<div class="columns-container">
				<span>Columns to grab:</span>
				<div class="columns-select">
					<?php foreach($columns as $column) {
						if ($column[0] == $tables[0]) {
							echo "<div onclick=\"selectColumn(this)\" class=\"column\">$column[0].$column[1]</div>";
						}
					} ?>
				</div>
			</div>
			<button onclick="buildQuery()">Submit</button>
		</div>
		<div id="reply">
		</div>
	</body>
</html>