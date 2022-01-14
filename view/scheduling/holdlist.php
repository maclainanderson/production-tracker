<?php
/**
  *	@desc	list of processes on hold
*/
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up sql connection for loading data
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of jobs and processes
	$jobs = array();
	$processes = array();
	$tools = array();
	$toolsOut = array();
	$reasons = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL_IN, DATE_IN, DATE_OUT, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM, TOOL_OUT FROM Mastering WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL_IN'],$row['TOOL_OUT']];
				unset($row['TOOL_OUT']);
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['DEPARTMENT'] = 'MASTERING';
				$jobs[$row['ID']]['PROCESS'] = 'MASTERING';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL_IN, DATE_IN, DATE_OUT, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM, TOOL_OUT FROM Mastering_Queue WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL_IN'],$row['TOOL_OUT']];
				unset($row['TOOL_OUT']);
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['DEPARTMENT'] = 'MASTERING';
				$jobs[$row['ID']]['PROCESS'] = 'MASTERING';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL_IN, DATE_IN, DATE_OUT, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM, PROCESS, TOOL_OUT FROM Toolroom WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL_IN'],$row['TOOL_OUT']];
				unset($row['TOOL_OUT']);
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['DEPARTMENT'] = 'TOOLROOM';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL_IN, DATE_IN, DATE_OUT, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM, PROCESS, TOOL_OUT FROM Toolroom_Queue WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL_IN'],$row['TOOL_OUT']];
				unset($row['TOOL_OUT']);
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['DEPARTMENT'] = 'TOOLROOM';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL_IN, DATE_IN, DATE_OUT, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM, PROCESS, TOOL_OUT FROM Electroforming WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL_IN'],$row['TOOL_OUT']];
				unset($row['TOOL_OUT']);
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['DEPARTMENT'] = 'ELECTROFORMING';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL_IN, DATE_IN, DATE_OUT, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM, PROCESS, TOOL_OUT FROM Electroforming_Queue WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL_IN'],$row['TOOL_OUT']];
				unset($row['TOOL_OUT']);
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['DEPARTMENT'] = 'ELECTROFORMING';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL, SELECT_DATE, SHIP_DATE, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM FROM Shipping WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL'],$row['TOOL']];
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['TOOL_IN'] = $row['TOOL'];
				unset($jobs[$row['ID']]['TOOL']);
				$jobs[$row['ID']]['DEPARTMENT'] = 'SHIPPING';
				$jobs[$row['ID']]['PROCESS'] = 'SHIPPING';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TOOL, SELECT_DATE, SHIP_DATE, TARGET_DATE, WO_NUMBER, JOB_NUMBER, PO_NUMBER, SEQNUM FROM Shipping_Queue WHERE ON_HOLD = 'TRUE' ORDER BY BATCH_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolsOut[$row['ID'] . "+" . $row['BATCH_NUMBER']] = [$row['TOOL'],$row['TOOL']];
				$jobs[$row['ID']] = $row;
				$jobs[$row['ID']]['TOOL_IN'] = $row['TOOL'];
				unset($jobs[$row['ID']]['TOOL']);
				$jobs[$row['ID']]['DEPARTMENT'] = 'SHIPPING';
				$jobs[$row['ID']]['PROCESS'] = 'SHIPPING';
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		foreach($jobs as $id => $job) {
			if ($job['DEPARTMENT'] != "MASTERING") {
				$result = sqlsrv_query($conn, "SELECT LOCATION, DRAWER, STATUS FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$jobs[$id]['LOCATION'] = $row['LOCATION'];
						$jobs[$id]['DRAWER'] = $row['DRAWER'];
						$jobs[$id]['STATUS'] = $row['STATUS'];
					}
				}
			} else {
				$jobs[$id]['LOCATION'] = "";
				$jobs[$id]['DRAWER'] = "";
				$jobs[$id]['STATUS'] = "";
			}
			
			$result = sqlsrv_query($conn, "SELECT OPERATOR, ID FROM Batches WHERE BATCH_NUMBER = '" . $job['BATCH_NUMBER'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$jobs[$id]['OPERATOR'] = $row['OPERATOR'];
				}
			}
			
			foreach($jobs as $id2 => $job2) {
				if ($job2['TOOL_IN'] == $job['TOOL_IN'] && $job2['BATCH_NUMBER'] == $job['BATCH_NUMBER'] && $job2['SEQNUM'] == ($job['SEQNUM'] + 1)) {
					$jobs[$id]['NEXT_PROCESS'] = $job2['PROCESS'];
				}
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE STATUS = 'Active' ORDER BY PROCESS;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, REASON FROM Abort_Work_Order WHERE STATUS = 'Active' ORDER BY REASON;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$reasons[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
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
	var selectedRow = 0;
	var jobs = [<?php
		foreach($jobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo str_replace(PHP_EOL, ' ', addslashes($value));
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	var processes = [<?php
		foreach($processes as $process) {
			echo '{';
			foreach($process as $key=>$value) {
				echo '"$key": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo str_replace(PHP_EOL, ' ', addslashes($value));
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	var toolsOut = {
	<?php foreach($toolsOut as $id => $tools) {
		echo "'" . $id . "': ['" . implode("','",$tools) . "'],";
	} ?>
	}
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	//make up/down keys scroll through list
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
	  *	@desc	highlight row and remove highlist from any others
	  *	@param	DOM Object tr - table row that was clicked on
	  *	@return	none
	*/
	function selectRow(tr) {
		selectedRow = tr.id;
		var trs = tr.parentNode.children;
		
		for (var i=0;i<trs.length;i++) {
			trs[i].style.color = "black";
			trs[i].style.backgroundColor = "white";
		}
		
		tr.style.color = "white";
		tr.style.backgroundColor = "black";
		
		for (var i=0;i<jobs.length;i++) {
			if (jobs[i]['ID'] == tr.id.split("+")[0]) {
				document.getElementById("job-input").value = jobs[i]['JOB_NUMBER'];
				document.getElementById("po-input").value = jobs[i]['PO_NUMBER'];
				document.getElementById("wo-input").value = jobs[i]['WO_NUMBER'];
				document.getElementById("department-input").value = jobs[i]['DEPARTMENT'];
				document.getElementById("location-input").value = jobs[i]['LOCATION'];
				document.getElementById("drawer-input").value = jobs[i]['DRAWER'];
				document.getElementById("scheduler-input").value = jobs[i]['OPERATOR'];
				document.getElementById("status-input").value = jobs[i]['STATUS'];
				document.getElementById("process-input").value = jobs[i]['NEXT_PROCESS'] || "";
			}
		}
	}
	
	/**
	  *	@desc	sorts jobs array by selected option
	  *	@param	string value - option to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_holdlist_order",document.getElementById("order-type").value);
		setCookie("sort_holdlist_filter",document.getElementById("filter-type").value);
		setCookie("sort_holdlist_filter_value",document.getElementById("filter-input").value);
		
		if (value == "none") {
			fillSort();
			return;
		}
		
		jobs.sort(function(a, b) {
			
			switch(value) {
				case "batch-number":
					if (parseInt(a['BATCH_NUMBER']) < parseInt(b['BATCH_NUMBER'])) {
						return -1;
					} else if (parseInt(a['BATCH_NUMBER']) > parseInt(b['BATCH_NUMBER'])) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "tool":
					if (a['TOOL_IN'].toUpperCase() < b['TOOL_IN'].toUpperCase()) {
						return -1;
					} else if (a['TOOL_IN'].toUpperCase() > b['TOOL_IN'].toUpperCase()) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "date-in":
					var ad = new Date(a['DATE_IN']);
					var bd = new Date(b['DATE_IN']);
					if (ad < bd) {
						return -1;
					} else if (ad > bd) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "date-out":
					var ad = new Date(a['DATE_OUT']);
					var bd = new Date(b['DATE_OUT']);
					if (ad < bd) {
						return -1;
					} else if (ad > bd) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "process":
					if (a['PROCESS'].toUpperCase() < b['PROCESS'].toUpperCase()) {
						return -1;
					} else if (a['PROCESS'].toUpperCase() > b['PROCESS'].toUpperCase()) {
						return 1;
					} else {
						return 0;
					}
					break;
				default:
					return 0;
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
		var tbody = document.getElementsByClassName("main")[0].children[1];
		var html = "";
		var keyword = document.getElementById("filter-input").value.toUpperCase();
		var value = document.getElementById("filter-type").value;
		
		jobs.forEach((item, index, array) => {
			if (isAllowed(keyword, value, item)) {
				html += `<tr id="${item['ID']}+${item['BATCH_NUMBER']}" onclick="selectRow(this)">
										<td class="col1">${item['BATCH_NUMBER']}</td>
										<td class="col2">${item['TOOL_IN']}</td>
										<td class="col3">${item['DATE_IN']}</td>
										<td class="col4">${item['DATE_OUT']}</td>
										<td class="col5">${item['TARGET_DATE']}</td>
										<td class="col6">${item['PROCESS']}</td>
									</tr>`;
			}
		});
		
		tbody.innerHTML = html;
	}
	
	/**
	  *	@desc	determines if row matches filter constraints
	  *	@param	string keyword - keyword to filter by, string value - column to filter by, array row - row to match
	  *	@return	true if match, false otherwise
	*/
	function isAllowed(keyword, value, row) {
		var valid = false;
		
		switch(value) {
			case "batch-number":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,"."),"g").test(row['BATCH_NUMBER'].toUpperCase()) && keyword.length == row['BATCH_NUMBER'].length) {
						valid = true;
					}
				} else {
					if (row['BATCH_NUMBER'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "tool":
				if (row['TOOL_IN'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			case "date-in":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(row['DATE_IN'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['DATE_IN'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "date-out":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(row['DATE_OUT'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (row['DATE_OUT'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "process":
				if (row['PROCESS'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			default:
				valid = true;
		}
		
		return valid;
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
									<option value="none">&lt;None&gt;</option>
									<option value="batch-number">Batch Number</option>
									<option value="tool">Tool</option>
									<option value="date-in">Date In</option>
									<option value="date-out">Date Out</option>
									<option value="process">Process</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none">&lt;None&gt;</option>
									<option value="batch-number">Batch Number</option>
									<option value="tool">Tool</option>
									<option value="date-in">Date In</option>
									<option value="date-out">Date Out</option>
									<option value="process">Process</option>
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
		
		if (checkCookie("sort_scheduling_order")) {
			document.getElementById("order-type").value = getCookie("sort_holdlist_order");
		}
		
		if (checkCookie("sort_scheduling_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_holdlist_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_scheduling_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_holdlist_filter_value");
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
		
		setCookie("sort_expanded");
	}
	
	/**
	  *	@desc	change filter field type
	  *	@param	DOM Object select - filter type
	  *	@return	none
	*/
	function changeFilter(select) {
		var field = document.getElementById("filter-input");
		if (field) {
			document.getElementById("filter-container").removeChild(field);
		}
		if (select.value == "process") {
			var select = document.createElement('select');
			select.id = "filter-input";
			for (var i=0;i<processes.length;i++) {
				select.innerHTML += '<option value="' + processes[i]['PROCESS'] + '">' + processes[i]['PROCESS'] + '</option>';
			}
			document.getElementById("filter-container").appendChild(select);
		} else {
			var input = document.createElement('input');
			input.type = "text";
			input.id = "filter-input";
			input.onkeydown = function(e) {
				if (e.key == "Enter") {
					input.parentNode.nextElementSibling.click();
				}
			}
			document.getElementById("filter-container").appendChild(input);
		}
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
	  *	@desc	go to scheduling details page
	  *	@param	none
	  *	@return	none
	*/
	function getDetails() {
		var html;
		
		for (var i=0;i<jobs.length;i++) {
			if (selectedRow.split("+")[0] == jobs[i]['ID']) {
				switch (jobs[i]['DEPARTMENT']) {
					case "MASTERING":
						html = '<form action="editmastering.php" method="POST" id="details-form" style="display: none;">';
						html += '<input type="text" name="id" value="' + selectedRow.split("+")[0] + '">';
						html += '<input type="text" name="batch" value="' + selectedRow.split("+")[1] + '">';
						html += '<input type="text" name="source" value="holdlist">';
						html += '</form>';
						break;
					case "TOOLROOM":
						html = '<form action="edittoolroom.php" method="POST" id="details-form" style="display: none;">';
						html += '<input type="text" name="woNumber" value="' + jobs[i]['WO_NUMBER'] + '">';
						html += '<input type="text" name="process" value="' + jobs[i]['PROCESS'] + '">';
						html += '<input type="text" name="source" value="holdlist">';
						html += '</form>';
						break;
					case "ELECTROFORMING":
						html = '<form action="editelectroforming.php" method="POST" id="details-form" style="display: none;">';
						html += '<input type="text" name="id" value="' + selectedRow.split("+")[0] + '">';
						html += '<input type="text" name="process" value="' + (jobs[i]['PROCESS'] == "ELECTROFORMING" ? "EFORM" : jobs[i]['PROCESS']) + '">';
						html += '<input type="text" name="source" value="holdlist">';
						html += '</form>';
						break;
					default:
						html = '<form action="editshipping.php" method="POST" id="details-form" style="display: none;">';
						html += '<input type="text" name="batch" value="' + selectedRow.split("+")[1] + '">';
						html += '<input type="text" name="source" value="holdlist">';
						html += '</form>';
				}
			}
		}
		
		document.getElementsByTagName("body")[0].innerHTML += html;
		document.getElementById("details-form").submit();
	}
	
	/**
	  *	@desc	release selected job from hold
	  *	@param	none
	  *	@return	none
	*/
	function release() {
		var tr = document.getElementById(selectedRow);
		var trs = tr.parentNode.children;
		var counter = 0;
		
		for (var i=0;i<trs.length;i++) {
			if (trs[i].children[0].innerHTML == tr.children[0].innerHTML) {
				counter++;
			}
		}
		
		var conn = new XMLHttpRequest();
		var action = "update";
		if (tr.children[5].innerHTML == "SHIPPING") {
			var tables = ['Shipping','Shipping_Queue'];
		} else if (tr.children[5].innerHTML == "MASTERING") {
			var tables = ['Mastering','Mastering_Queue'];
		} else if (['ELECTROFORMING','NICKEL FLASHING','CLEANING'].includes(tr.children[5].innerHTML)) {
			var tables = ['Electroforming','Electroforming_Queue'];
		} else {
			var tables = ['Toolroom','Toolroom_Queue'];
		}
		var successes = 0;
		var attempts = 0;
		
		for (var i=0;i<tables.length;i++) {
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					attempts++;
					if (this.responseText.includes("Data updated")) {
						successes++;
						if (attempts >= tables.length) {
							if (successes >= tables.length) {
								if (counter == 1) {
									releaseBatch();
								} else {
									alert("Released job");
									window.location.reload();
								}
							} else {
								alert("Could not update all tables. Contact IT Support to correct.");
							}
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&ON_HOLD=FALSE&condition="+(tables[i].includes("Shipping") ? "TOOL" : "TOOL_IN")+"&value="+tr.children[1].innerHTML.replace(/[+]/g,"%2B")+(tables[i].includes("Shipping") || tables[i].includes("Mastering") ? "" : "&condition2=PROCESS&value2="+tr.children[5].innerHTML), false);
			conn.send();
		}
	}
	
	/**
	  *	@desc	release hold on batch
	  *	@param	none
	  *	@return	none
	*/
	function releaseBatch() {
		var conn = new XMLHttpRequest();
		var action = "update";
		var table = "Batches";
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (this.responseText.includes("Data updated")) {
					alert("Released job");
					window.location.reload();
				} else {
					alert("Released job, but batch still on hold. Contact IT Support to correct.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&ON_HOLD=FALSE&condition=BATCH_NUMBER&value="+selectedRow.split("+")[1],false);
		conn.send();
	}
	
	/**
	  *	@desc	put job into abort history
	  *	@param	none
	  *	@return	none
	*/
	function abortJob() {
		var id = selectedRow;
		if (id != 0) {
			var abortJob = {
				BATCH_NUMBER: id.split("+")[1],
				TOOL: document.getElementById(id).children[1].innerHTML,
				PROCESS: document.getElementById(id).children[5].innerHTML,
				REASON: "",
				WO_NUMBER: document.getElementById("wo-input").value,
				OPERATOR: "",
				DATE: formatDate(new Date())
			}
			
			if (abortJob.PROCESS == "SHIPPING") {
				abortJob.DEPARTMENT = "SHIPPING";
			} else if (abortJob.PROCESS == "MASTERING") {
				abortJob.DEPARTMENT = "MASTERING";
			} else if (['ELECTROFORMING','NICKEL FLASHING','CLEANING'].includes(abortJob.PROCESS)) {
				abortJob.DEPARTMENT = "TOOLRM";
			} else {
				abortJob.DEPARTMENT = "ELECTROFOR";
			}
			
			document.getElementById("modal").style.display = "block";
			var modalContent = document.getElementById("modal-content");
			modalContent.innerHTML = `<span id="close">&times;</span><span>Reason for aborting job:</span><br>
									  <select id="reason-select">
									  <?php foreach($reasons as $reason) { ?>
									  <option value="<?=$reason['REASON']?>"><?=$reason['REASON']?></option>
									  <?php } ?>
									  </select><br>
									  <input onblur="this.value = this.value.toUpperCase();" type="text" id="abort-operator-input" <?php if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?> value="<?=$_SESSION['initials']?>" <?php } ?>>
									  <button id="submit-abort">Submit</button>`;
									  
			closeForm();
			
			document.getElementById("submit-abort").addEventListener('click', function() {
				var conn = new XMLHttpRequest();
				var table = "Abort_History";
				var action = "insert";
				abortJob.REASON = document.getElementById("reason-select").value;
				abortJob.OPERATOR = document.getElementById("abort-operator-input").value;
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Insert succeeded")) {
							if (deleteTool(id)) {
								deleteJob();
							} else {
								alert("Job aborted, but tool names still reserved. Contact IT Support to correct. " + conn.responseText);
							}
						} else {
							alert("Row not added to abort history");
						}
					}
				}
				
				var query = "";
				
				Object.keys(abortJob).forEach((item, index, array) => {
					query += `&${item}=${abortJob[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				});
			
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn.send();
			});
		} else {
			alert("Select a job first");
		}
	}
	
	/**
	  *	@desc	remove reserved tools from tool tree
	  *	@param	int id - array index for tool
	  *	@return	none
	*/
	function deleteTool(id) {
		var success = false;
		
		if (toolsOut[id][0] != toolsOut[id][1] && toolsOut[id][1] != "" && !hasChildren(toolsOut[id][1])) {
			var conn = new XMLHttpRequest();
			var action = "delete";
			var table = "Tool_Tree";
			var query = "&TOOL="+toolsOut[id][1].replace(/[+]/g, "%2B");
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Deletion succeeded")) {
						success = true;
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
			conn.send();
		} else {
			success = true;
		}
		
		return success;
	}
	
	/**
	  *	@desc	find if tool has children in the tree
	  *	@param	string tool - name of tool to search
	  *	@return	int - number of children
	*/
	function hasChildren(tool) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Tree";
		var query = "&condition=MANDREL&value="+tool.replace(/[+]/g,"%2B");
		var count = 0;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				count = result.length;
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
		conn.send();
		
		return count;
	}
	
	/**
	  *	@desc	remove aborted jobs from current schedule
	  *	@param	none
	  *	@return	none
	*/
	function deleteJob() {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "delete";
		var id = selectedRow;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					var minSeqnum = 100;
					var thisSeqnum;
					
					for (var i=0;i<jobs.length;i++) {
						if (jobs[i]['TOOL_IN'] == document.getElementById(id).children[1].innerHTML) {
							if (id == jobs[i]['ID'] + "+" + jobs[i]['BATCH_NUMBER']) {
								thisSeqnum = jobs[i]['SEQNUM'];
							}
							
							if (jobs[i][9] < minSeqnum) {
								minSeqnum = jobs[i]['SEQNUM'];
							}
						}
					}
					
					if (minSeqnum == thisSeqnum) {
						addNextJob();
					} else {
						alert("Job aborted");
					}
				} else {
					alert("Job deletion failed");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&id="+id.split("+")[0], false);
		conn.send();
	}
	
	/**
	  *	@desc	move next job in batch to current work, if it exists
	  *	@param	none
	  *	@return	none
	*/
	function addNextJob() {
		var next = findNextJob();
		if (next != "" && next != undefined) {
			var conn = new XMLHttpRequest();
			var action = "insert";
			var query = "";
			var table = next["TABLE"].split("_")[0];
			delete next.TABLE;
			
			Object.keys(next).forEach((item, index, array) => {
				query += `&${item}=${next[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			})
			
			conn.onreadystatechange = function() {
				if(conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						removeNextFromQueue(next.WO_NUMBER, table);
					} else {
						alert("Next job in batch not added. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn.send();
		} else {
			alert("Job aborted");
			window.location.replace("holdlist.php");
		}
	}
	
	/**
	  *	@desc	grab next job in batch, if applicable
	  *	@param	none
	  *	@return	array containing job details
	*/
	function findNextJob() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var condition = "BATCH_NUMBER";
		var value = document.getElementById(selectedRow).children[0].innerHTML;
		var jobs = [];
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of result) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				
				result.forEach((item, index, array) => {
					item['TABLE'] = conn.responseURL.split("table=")[1].split("&")[0];
				});
				
				for (var i=0;i<result.length;i++) {
					jobs.push(result[i]);
				}
			}
		}
		
		for (var i=0;i<tables.length;i++) {
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&condition="+condition+"&value="+value,false);
			conn.send();
		}
		
		nextSeqNum = 100;
		nextJob = 0;
		
		jobs.forEach((item, index, array) => {
			if (item[5] < nextSeqNum && item[7] == document.getElementById(selectedRow).children[1].innerHTML) {
				nextSeqNum = item[5];
				nextJob = index;
			}
		});
		
		return jobs[nextJob];
	}
	
	/**
	  *	@desc	remove next job from queue, after it is moved to current schedule
	  *	@param	int woNumber - identifying number for job, string table - db table the job is found in
	  *	@return	none
	*/
	function removeNextFromQueue(woNumber, table) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = table + "_Queue";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Job aborted");
					window.location.replace("holdlist.php");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WO_NUMBER="+woNumber,false);
		conn.send();
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to be converted
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
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString();
		
		return date;
	}
</script>
<html>
	<head>
		<title>Hold List</title>
		<link rel="stylesheet" href="/styles/scheduling/holdlist.css"></link>
	</head>
	<body>
		<div class="container">
			<div class="outer">
				<div class="inner">
					<table class="main">
						<thead>
							<tr>
								<th class="col1">Batch Number</th>
								<th class="col2">Tool</th>
								<th class="col3">Date In</th>
								<th class="col4">Date Out</th>
								<th class="col5">Target</th>
								<th class="col6">Process</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($jobs as $job) { ?>
							<tr onclick="selectRow(this)" id="<?=$job['ID'] . "+" . $job['BATCH_NUMBER']?>">
								<td class="col1"><?=$job['BATCH_NUMBER']?></td>
								<td class="col2"><?=$job['TOOL_IN']?></td>
								<td class="col3"><?=date_format($job['DATE_IN'],'m/d/y')?></td>
								<td class="col4"><?=date_format($job['DATE_OUT'],'m/d/y')?></td>
								<td class="col5"><?=date_format($job['TARGET_DATE'],'m/d/y')?></td>
								<td class="col6"><?=$job['PROCESS']?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<div class="details">
						<div class="details-left">
							<span>Job Number</span><input id="job-input" type="text" readonly><br>
							<span>PO Number</span><input id="po-input" type="text" readonly><br>
							<span>Work Order</span><input id="wo-input" type="text" readonly>
						</div>
						<div class="details-center">
							<span>Tool Location</span><input id="location-input" type="text" readonly><br>
							<span>Drawer</span><input id="drawer-input" type="text" readonly><br>
							<span>Status</span><input id="status-input" type="text" readonly>
						</div>
						<div class="details-right">
							<span>Scheduler</span><input id="scheduler-input" type="text" readonly><br>
							<span>Department</span><input id="department-input" type="text" readonly><br>
							<span>Next Process</span><input id="process-input" type="text" readonly>
						</div><br>
					</div>
					<div class="controls">
						<button onclick="getDetails()">Details</button>
						<button onclick="release()">Release</button>
						<button onclick="abortJob()">Abort</button>
						<a href="../scheduling.php">Back</a>
					</div>
				</div>
			</div>
			<div id="arrow" onclick="showFilters()">
		 		<div class="right-arrow">
				</div>
		 	</div>
		</div>
		<div id="modal">
			<div id="modal-content">
			</div>
		</div>
	</body>
</html>