<!DOCTYPE html>
<?php
/**
  *	@desc process shipping job in
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of tools from job and customers
	$customers = array();
	$batchTools = array();
	$tools = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, CUSTOMER, NAME FROM Customers ORDER BY CUSTOMER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$customers[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, PO_NUMBER, ORDER_NUMBER, JOB_NUMBER, BELT_NUMBER, SPECIAL_INSTRUCTIONS, CUSTOMER, SEQNUM, SHIP_OPERATOR, SELECT_DATE, SELECT_OPERATOR FROM Shipping WHERE BATCH_NUMBER = " . $_POST['batch'] . " ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$batchTools[] = $row;
				if ($row['SHIP_OPERATOR'] == '') {
					$isCurrent = true;
				} else {
					$isCurrent = false;
				}
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		if (empty($batchTools)) {
			$result = sqlsrv_query($conn, "SELECT ID, TOOL, PO_NUMBER, ORDER_NUMBER, JOB_NUMBER, BELT_NUMBER, SPECIAL_INSTRUCTIONS, CUSTOMER, SEQNUM, SHIP_OPERATOR, SELECT_DATE, SELECT_OPERATOR FROM Shipping_History WHERE BATCH_NUMBER = " . $_POST['batch'] . " ORDER BY WO_NUMBER;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$batchTools[] = $row;
					$isCurrent = false;
				}
			} else {
				var_dump(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, DATE_CREATED, PO_NUMBER, ORDER_NUMBER, JOB_NUMBER, STATUS FROM Tool_Tree ORDER BY TOOL;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tools[] = $row;
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
	
	//set up tracking variables
	var selectedRow = 0;
	var removeTools = [];
	var seqnum = <?=$batchTools[0]['SEQNUM']?>;
	var originalTools = [<?php
		foreach($batchTools as $batchTool) {
			echo '"';
			echo addslashes($batchTool['TOOL']);
			echo '"';
			echo ',';
		}
	?>]
	
	var customers = [<?php
		foreach($customers as $customer) {
			echo '{';
			foreach($customer as $key=>$value) {
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
	
	var tools = [<?php
		foreach($tools as $tool) {
			echo '{';
			foreach($tool as $key=>$value) {
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
	  *	@desc	insert date and operator name
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		document.getElementById("date-input").value = <?php if (!$isCurrent) { echo '"' . date_format($batchTools[0]['SELECT_DATE'],'m/d/y') . '"'; } else { echo 'formatDate(new Date())'; } ?>;
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= !$isCurrent ? $batchTools[0]['SELECT_OPERATOR'] : $_SESSION['initials'] ?>";
		}
	}
	
	/**
	  *	@desc	get customer long name
	  *	@param	DOM Object select - contains customer short name
	  *	@return	none
	*/
	function insertDescription(select) {
		customers.forEach((item, index, array) => {
			if (item['CUSTOMER'] == select.value) { 
				document.getElementById("customer-input").value = item['NAME'];
			}
		});
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
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString();
		
		return date;
	}
	
	/**
	  *	@desc	highlight selected tool
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		var trs = tr.parentNode.children;
		for (var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		selectedRow = tr.id;
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("date-input").value == "") {
			msg = "Please enter a valid date";
		} else if (document.getElementById("operator-input").value == "") {
			msg = "Please enter your initials";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	save job data
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == '') {
			var trs = document.getElementById("tool-table").children[1].children;
			var successCounter = 0;
					
			var conn = [];
			var table = "Shipping";
		
			for(var i=0;i<trs.length;i++) {
			
				var d = new Date();
				
				if (originalTools.includes(trs[i].children[0].innerHTML)) {
					var action = "update";
				} else {
					var action = "insert";
				}
				
				var job = {
					SELECT_DATE: formatDate(d),
					SHIP_DATE: formatDate(new Date(d.setDate(d.getDate() + 1))),
					SELECT_OPERATOR: document.getElementById("operator-input").value,
					STATUS: "GOOD",
					SHIPPED_TO: document.getElementById("customer-select").value,
					PO_NUMBER: trs[i].children[1].innerHTML,
					ORDER_NUMBER: trs[i].children[2].innerHTML,
					JOB_NUMBER: trs[i].children[3].innerHTML,
					BELT_NUMBER: trs[i].children[4].innerHTML
				};
				
				if (originalTools.includes(trs[i].children[0].innerHTML)) {
					job.id = trs[i].id;
				} else {
					job.TOOL = trs[i].children[0].innerHTML,
					job.BATCH_NUMBER = <?=$_POST['batch']?>;
					job.WO_NUMBER = getNextWorkNumber();
					job.SEQNUM = seqnum;
				}
				
				var query = "";
			
				Object.keys(job).forEach((item, index, array) => {
					if (item != 'id') {
						query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					} else {
						query += `&condition=id&value=${job[item]}`;
					}
				})
				
				conn[i] = new XMLHttpRequest();
				
				conn[i].onreadystatechange = xmlResponse(i);
				
				conn[i].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn[i].send();
			}
			
			function xmlResponse(i) {
				return function() {
					if (conn[i].readyState == 4 && conn[i].status == 200) {
						if (conn[i].responseText.includes("Data updated") || conn[i].responseText.includes("Insert succeeded")) {
							successCounter++;
							if (successCounter >= trs.length) {
								abortTools();
							}
						} else {
							alert("Job update failed. Contact IT Support to correct. " + conn[i].responseText);
						}
					}
				}
			}
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	abort tools from removeTools
	  *	@param	none
	  *	@return	none
	*/
	function abortTools() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Shipping";
		var successes = 0;
		
		if (removeTools.length > 0) {
			for (var i=0;i<removeTools.length;i++) {
				conn.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						if (this.responseText.includes("Deletion succeeded")) {
							successes++;
							if (successes >= removeTools.length) {
								document.getElementsByTagName("body")[0].innerHTML += `<form action="shipping.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['batch']?>"></form>`;
								document.getElementById("return-form").submit();
							}
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&TOOL="+removeTools[i].replace(/[+]/g,"%2B"),false);
				conn.send();
			}
		} else {
			document.getElementsByTagName("body")[0].innerHTML += `<form action="shipping.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['batch']?>"></form>`;
			document.getElementById("return-form").submit();
		}
	}
	
	/**
	  *	@desc	get next available WO#
	  *	@param	none
	  *	@return	int (max + 1) - last used WO# incremented by 1
	*/
	function getNextWorkNumber() {
		var conn = new XMLHttpRequest();
		var tables = ["Mastering","Mastering_Queue","Mastering_History","Toolroom","Toolroom_Queue","Toolroom_History","Shipping","Shipping_Queue","Shipping_History","Electroforming","Electroforming_Queue","Electroforming_History","Abort_History"];
		var action = "select";
		var condition = "WO_NUMBER";
		var max = 0;
		
		tables.forEach((item, index, array) => {
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
					for (let job of response) {
						for (let x in job) {
							if (job[x] !== null && typeof job[x] == 'object') {
								job[x] = formatDate(new Date(job[x]['date']));
							}
						}
					}
					if (response.length > 0) {
						if (parseInt(response[0]['WO_NUMBER']) > max) {
							max = parseInt(response[0]['WO_NUMBER']);
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+item+"&action="+action+"&condition="+condition+"&value=(SELECT MAX("+condition+") FROM "+item+")",false);
			conn.send();
		});
		
		return max + 1;
	}
	
	/**
	  *	@desc	auto-format date fields to MM/DD/YY
	  *	@param	DOM Object input - date field to format
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
	  *	@desc	return to previous page
	  *	@param	none
	  *	@return none
	*/
	function goBack() {
		<?php if ($_POST['source'] == "retrieve.php") { ?>
		document.getElementsByTagName("body")[0].innerHTML += `<form action="/view/retrieve.php" method="POST" id="return-form" style="display: none;"><input type="text" value="/view/operations/shipping.php" name="returnpath"><input type="text" value="<?=$_POST['tool']?>" name="tool"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>`;
		<?php } else { ?>
		document.getElementsByTagName("body")[0].innerHTML += `<form action="shipping.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['batch']?>"></form>`;
		<?php } ?>
		document.getElementById("return-form").submit();
	}
	
	/**
	  *	@desc	show wait message to users
	  *	@param	none
	  *	@return	none
	*/
	function wait() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modalContent.innerHTML = "<h3>Please wait...</h3>";
		modal.style.display = "block";
		setTimeout(popToolList, 200);
	}
	
	/**
	  *	@desc	create/display list of tools
	  *	@param	none
	  *	@return	none
	*/
	function popToolList() {
		var searchText = document.getElementById("tool-input").value;
			
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		
		var html = `<table id="tool-list"><thead><tr><th class="col1">Tool</th><th class="col2">Tank Out</th></thead><tbody>`;
		tools.forEach((item, index, array) => {
			if (item['TOOL'].toUpperCase().includes(searchText.toUpperCase()) && (item['STATUS'] != "NOGOOD" && item['STATUS'] != "RETIRED" && item['STATUS'] != "PURGED")) {
				html += `<tr id="${item['ID']}" onclick="selectToolRow(this)">
							<td class="col1">${item['TOOL']}</td>
							<td class="col2">${item['DATE_OUT']}</td>
						</tr>`;
			}
		});
		
		html += `</tbody></table><span class="close" id="close">&times;</span><button onclick="saveToolList()">Save Tools</button><br><br><br><span># Selected:</span><br><span id="count">0</span></div>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight row in tool list
	  *	@param	DOM Object tr - row selected
	  *	@return	none
	*/
	function selectToolRow(tr) {
		if (tr.style.color != "white") {
			tr.style.backgroundColor = "black";
			tr.style.color = "white";
			document.getElementById("count").innerHTML = parseInt(document.getElementById("count").innerHTML) + 1;
		} else {
			tr.style.backgroundColor = "white";
			tr.style.color = "black";
			document.getElementById("count").innerHTML = parseInt(document.getElementById("count").innerHTML) - 1;
		}
	}
	
	/**
	  *	@desc	save highlighted tools to job data
	  *	@param	none
	  *	@return	none
	*/
	function saveToolList() {
		var trs = document.getElementById("tool-list").children[1].children;
		
		for (var i=0;i<trs.length;i++) {
			if (trs[i].style.color == "white") {
				tools.forEach((item, index, array) => {
					if (item['TOOL'].toUpperCase() == trs[i].children[0].innerHTML.toUpperCase()) {
						document.getElementById("tool-table").children[1].innerHTML += `<tr id="${item['ID']}" onclick="selectRow(this)">
																						<td class="col1">${item['TOOL']}</td>
																						<td class="col2"></td>
																						<td class="col3"></td>
																						<td class="col4"></td>
																						<td class="col5"></td>
																					   </tr>`;
					}
				});
			}
		}
		
		document.getElementById("close").click();
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
	  *	@desc	remove tool from batch
	  *	@param	none
	  *	@return	none
	*/
	function deleteTool() {
		removeTools.push(document.getElementById(selectedRow).children[0].innerHTML);
		document.getElementById(selectedRow).parentNode.removeChild(document.getElementById(selectedRow));
	}
	
	/**
	  *	@desc	create/display form for editing po#, belt#, etc.
	  *	@param	none
	  *	@return	none
	*/
	function popInfo() {
		var modalContent = document.getElementById("modal-content");
		var html = "";
		var tr = document.getElementById(selectedRow);
		var tool = tr.children[0].innerHTML;
		var poNumber = tr.children[1].innerHTML;
		var orderNumber = tr.children[2].innerHTML;
		var jobNumber = tr.children[3].innerHTML;
		var beltNumber = tr.children[4].innerHTML;
		
		html += '<span id="close">&times;</span><div style="text-align: left;">';
		html += '<span style="display: inline-block;">Tool<input style="width: 400px;" id="info-tool-input" type="text" value="'+tool+'"></span><br>'
		html += '<span style="display: inline-block; margin-left: 13px;">PO Number<input id="info-po-input" type="text" value="'+poNumber+'" onblur="this.value = this.value.toUpperCase();"></span><span style="display: inline-block; margin-left: 5px;">Job Number<input id="info-job-input" type="text" value="'+jobNumber+'" onblur="this.value = this.value.toUpperCase();"></span><br>';
		html += '<span style="display: inline-block;">Order Number<input id="info-order-input" type="text" value="'+orderNumber+'" onblur="this.value = this.value.toUpperCase();"></span><span style="display: inline-block; margin-left: 4px;">Belt Number<input id="info-belt-input" type="text" value="'+beltNumber+'" onblur="this.value = this.value.toUpperCase();"></span><br>';
		html += '<input style="width: auto; position: relative; top: 2px;" id="info-checkbox" type="checkbox"><span>Update All Tools</span>';
		html += '<button onclick="saveInfo()">Save</button></div>';
		
		modalContent.innerHTML = html;
		document.getElementById("modal").style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	save job info to job data
	  *	@param	none
	  *	@return	none
	*/
	function saveInfo() {
		var checkbox = document.getElementById("info-checkbox").checked;
		var poNumber = document.getElementById("info-po-input").value;
		var jobNumber = document.getElementById("info-job-input").value;
		var orderNumber = document.getElementById("info-order-input").value;
		var beltNumber = document.getElementById("info-belt-input").value;
		var tr = document.getElementById(selectedRow);
		
		if (checkbox) {
			var trs = tr.parentNode.children;
			for (var i=0;i<trs.length;i++) {
				trs[i].children[1].innerHTML = poNumber.toUpperCase();
				trs[i].children[2].innerHTML = orderNumber.toUpperCase();
				trs[i].children[3].innerHTML = jobNumber.toUpperCase();
				trs[i].children[4].innerHTML = beltNumber.toUpperCase();
			}
		} else {
			tr.children[1].innerHTML = poNumber.toUpperCase();
			tr.children[2].innerHTML = orderNumber.toUpperCase();
			tr.children[3].innerHTML = jobNumber.toUpperCase();
			tr.children[4].innerHTML = beltNumber.toUpperCase();
		}
		
		document.getElementById("close").click();
	}
</script>
<html>
	<head>
		<title>Select for Shipping</title>
		<link rel="stylesheet" type="text/css" href="/styles/shippingin.css">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
					<span id="customer-span">Customer<select id="customer-select" onchange="insertDescription(this)">
					<?php foreach($customers as $customer) { ?>
						<option value="<?=$customer['CUSTOMER']?>"<?php if ($batchTools[0]['CUSTOMER'] == $customer['CUSTOMER']) { ?> selected<?php } ?>><?=$customer['CUSTOMER']?></option>
					<?php } ?>
					</select><input type="text" id="customer-input" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="tool-list">
					<table id="tool-table">
						<thead>
							<tr>
								<th class="col1">Tool</th>
								<th class="col2">PO #</th>
								<th class="col3">Order #</th>
								<th class="col4">Job #</th>
								<th class="col5">Belt #</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($batchTools as $tool) { ?>
							<tr id="<?=$tool['ID']?>" onclick="selectRow(this)">
								<td class="col1"><?=$tool['TOOL']?></td>
								<td class="col2"><?=$tool['PO_NUMBER']?></td>
								<td class="col3"><?=$tool['ORDER_NUMBER']?></td>
								<td class="col4"><?=$tool['JOB_NUMBER']?></td>
								<td class="col5"><?=$tool['BELT_NUMBER']?></td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<input type="text" id="tool-input">
				</div>
				<div class="table-controls">
					<button onclick="popToolList()">Add</button>
					<button onclick="deleteTool()">Delete</button>
					<button onclick="popInfo()">Info</button>
				</div><br>
				<span id="special-span">Special Intructions</span><br><textarea rows="4" cols="80" id="special-textarea"><?=$batchTools[0]['SPECIAL_INSTRUCTIONS']?></textarea>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
