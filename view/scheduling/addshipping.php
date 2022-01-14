<!DOCTYPE html>
<?php
/**
  *	@desc create new shipping job
*/
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins) && !in_array($_SESSION['name'], $schedulers)) {
		header("Location: /view/home.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of tools and customers
	$tools = array();
	$customers = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, DATE_CREATED, PO_NUMBER, ORDER_NUMBER, JOB_NUMBER FROM Tool_Tree ORDER BY TOOL");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tools[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, CUSTOMER, NAME FROM Customers ORDER BY CUSTOMER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$customers[] = $row;
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
	var batch = {};
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
	
	/**
	  *	@desc	insert operator name
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
	}
	
	/**
	  *	@desc	get customer's long name
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
							<td class="col2">${item['DATE_CREATED']}</td>
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
	  *	@desc	highlight row in main table
	  *	@param	DOM Object tr - row clicked on
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
	  *	@desc	remove tool from main list
	  *	@param	none
	  *	@return	none
	*/
	function deleteTool() {
		var tr = document.getElementById(selectedRow);
		
		tr.parentNode.removeChild(tr);
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("target-input").value == "") {
			msg = "Invalid date";
		} else if (document.getElementById("tool-table").children[1].children.length == 0) {
			msg = "Please select a tool";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	determine if tool is already in a shipping job
	  *	@param	none
	  *	@return	int count - evaluated as false if tool is not in a shipping job
	*/
	function isScheduled() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Shipping";
		var condition = "TOOL";
		var count = 0;
		var tools = [];
		var trs = document.getElementById("tool-table").children[1].children;
			
		for (var i=0;i<trs.length;i++) {
			tools.push(trs[i].children[0].innerHTML);
		}
		
		for (var i=0;i<tools.length;i++) {
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					var jobs = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
					for (let job of jobs) {
						for (let x in job) {
							if (job[x] !== null && typeof job[x] == 'object') {
								job[x] = formatDate(new Date(job[x]['date']));
							}
						}
					}
					count+=jobs.length;
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+tools[i].replace(/[+]/g, "%2B"), false);
			conn.send();
		}
		
		return count;
	}
	
	/**
	  *	@desc	save new shipping job
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == "" && !isScheduled()) {
			var trs = document.getElementById("tool-table").children[1].children;
			var successCounter = 0;
			var d = new Date(document.getElementById("target-input").value);
			batch = {
				BATCH_NUMBER: "",
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: document.getElementById("target-input").value,
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value
			};
			getNextBatchNumber();
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "insert";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Insert succeeded.")) {
					
						var conn2 = [];
						var jobs = [];
						var table2 = "Shipping";
						var action2 = "insert";
					
						for(var i=0;i<trs.length;i++) {
							jobs[i] = {
								BATCH_NUMBER: batch.BATCH_NUMBER,
								JOB_NUMBER: trs[i].children[3].innerHTML,
								WO_NUMBER: getNextWorkNumber(),
								SEQNUM: 1,
								TARGET_DATE: formatDate(new Date(document.getElementById("target-input").value)),
								TOOL: trs[i].children[0].innerHTML,
								SELECT_DATE: formatDate(new Date(d.setDate(d.getDate() - 1))),
								SHIP_DATE: formatDate(new Date(document.getElementById("target-input").value)),
								SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
								CUSTOMER: document.getElementById("customer-select").value,
								PO_NUMBER: trs[i].children[1].innerHTML,
								ORDER_NUMBER: trs[i].children[2].innerHTML,
								BELT_NUMBER: trs[i].children[4].innerHTML,
							};
							
							var query2 = "";
							
							Object.keys(jobs[i]).forEach((item, index, array) => {
								query2 += `&${item}=${jobs[i][item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							})
							
							conn2[i] = new XMLHttpRequest();
							
							conn2[i].onreadystatechange = xmlResponse(i);
							
							conn2[i].open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
							conn2[i].send();
						}
						
						function xmlResponse(i) {
							return function() {
								if (conn2[i].readyState == 4 && conn2[i].status == 200) {
									if (conn2[i].responseText.includes("Insert succeeded.")) {
										updateTool(jobs[i].TOOL, jobs[i].BELT_NUMBER);
										successCounter++;
										checkSuccess(successCounter);
									} else {
										alert("Batch created, but job entry failed. Contact support to correct. " + conn2.responseText);
									}
								}
							}
						}
					} else {
						alert("Batch not created. Contact IT Support to correct. " + conn1.responseText);
					}
				}
			}
			
			var query1 = "";
			
			Object.keys(batch).forEach((item, index, array) => {
				query1 += `&${item}=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			})
			
			conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
			conn1.send();
		} else if (isScheduled()) {
			alert("Tool(s) already scheduled");
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	check if all queries succeeded
	  *	@param	int counter - queries attempted so far
	  *	@return	none
	*/
	function checkSuccess(counter) {
		if (counter == document.getElementById("tool-table").children[1].children.length) {
			alert("Job inserted");
			window.location.replace("shipping.php");
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	assign belt # to tool
	  *	@param	string tool - tool name to update, string beltNumber - belt # to add
	  *	@return none;
	*/
	function updateTool(tool, beltNumber) {
		var action = "update";
		var table = "Tool_Tree";
		var query = "&BELT_NUMBER="+beltNumber+"&condition=TOOL&value="+tool;
		var conn = new XMLHttpRequest();
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					return;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query.replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A"), false);
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
	
	/**
	  *	@desc	get next available batch#
	  *	@param	none
	  *	@return	none
	*/
	function getNextBatchNumber() {
		var conn = new XMLHttpRequest();
		var table = "Batches";
		var action = "select";
		var condition = "BATCH_NUMBER"
		var value = "(SELECT MAX(BATCH_NUMBER) FROM Batches)";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let batch of response) {
					for (let x in batch) {
						if (batch[x] !== null && typeof batch[x] == 'object') {
							batch[x] = formatDate(new Date(batch[x]['date']));
						}
					}
				}
				
				batch.BATCH_NUMBER = parseInt(response[0]['BATCH_NUMBER']) + 1;
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value,false);
		conn.send();
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
					var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
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
	  *	@desc	determine if any jobs are repeats
	  *	@param	none
	  *	@return	none
	*/
	function checkForRepeats() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table, query, repeats = false;
		var trs = document.getElementById("tool-table").children[1].children;
		for (i=0;i<trs.length;i++) {
			table = "Shipping_History";
			condition = "TOOL";
			
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
					if (result.length > 0) {
						if (repeats) {
							repeats.push(trs[i].children[0].innerHTML);
						} else {
							repeats = [trs[i].children[0].innerHTML];
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action=" + action + "&table=" + table + "&condition=" + condition + "&value=" + trs[i].children[0].innerHTML.replace(/[+]/g,"%2B"), false);
			conn.send();
		}
		
		if (repeats) {
			var modal = document.getElementById('modal');
			var modalContent = document.getElementById('modal-content');
			modalContent.style.width = "auto";
			modalContent.style.textAlign = "left";
			var html = "<p>At least one of your jobs has already been processed. Repeated jobs:<p><ul>";
			
			for (i=0;i<repeats.length;i++) {
				html += "<li><p>Tool: <strong>" + repeats[i] + "</strong></p></li>";
			}
			
			html += "</ul><p>Continue anyway?</p><button style=\"margin-left: 75px; float: left;\" id=\"continue-button\">Yes</button><button style=\"margin-left: 50px; float: left;\" id=\"cancel-button\">No</button>";
			
			modalContent.innerHTML = html;
			modal.style.display = "block";
		
			document.getElementById("cancel-button").onclick = function() {
				modal.style.display = "none";
			}
			
			document.getElementById("continue-button").onclick = function() {
				saveJob();
			}
		} else {
			saveJob();
		}
	}
</script>
<html>
	<head>
		<title>Add Shipping Job</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addshipping.css">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
					<span id="customer-span">Customer<select id="customer-select" onchange="insertDescription(this)">
					<?php foreach($customers as $customer) { ?>
						<option value="<?=$customer['CUSTOMER']?>" <?php if ($customer['CUSTOMER'] == "RFD") { echo "selected"; } ?>><?=$customer['CUSTOMER']?></option>
					<?php } ?>
					</select><input type="text" id="customer-input"></span><br>
					<span id="target-span">Target<input onkeydown="fixDate(this)" type="text" id="target-input"></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="checkForRepeats()">Save</button>
					<a href="shipping.php">Back</a>
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
						</tbody>
					</table>
					<input type="text" id="tool-input">
					<div class="table-controls">
						<button onclick="wait()">Add</button>
						<button onclick="deleteTool()">Delete</button>
						<button onclick="popInfo()">Info</button>
					</div>
				</div><br>
				<span id="special-span">Special Intructions</span><br><textarea rows="4" cols="80" id="special-textarea"></textarea>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
