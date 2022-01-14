<!DOCTYPE html>
<?php
/**
  *	@desc edit already existing shipping job
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
	
	//list of tools, customer, and active jobs
	$tools = array();
	$customers = array();
	$jobs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, DATE_CREATED, PO_NUMBER, ORDER_NUMBER, JOB_NUMBER, STATUS FROM Tool_Tree ORDER BY TOOL");
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
		
		$result = sqlsrv_query($conn, "SELECT * FROM Shipping WHERE BATCH_NUMBER = '" . $_POST['batch'] . "' ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = array_merge($row, ['IS_QUEUE' => false]);
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Shipping_Queue WHERE BATCH_NUMBER = '" . $_POST['batch'] . "' ORDER BY WO_NUMBER;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = array_merge($row, ['IS_QUEUE' => true]);
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
				echo '"' . '": `';
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
	
	var jobs = [<?php
		foreach($jobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
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
	  *	@desc	fill in existing job data
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
		
		document.getElementById("customer-select").value = jobs[0]['CUSTOMER'];
		document.getElementById("target-input").value = jobs[0]['TARGET_DATE'];
		
		jobs.forEach((item, index, array) => {
			document.getElementById("tool-table").children[1].innerHTML += '<tr id="'+item['ID']+'" onclick="selectRow(this)"><td class="col1">'+item['TOOL']+'</td><td class="col2">'+item['PO_NUMBER']+'</td><td class="col3">'+item['ORDER_NUMBER']+'</td><td class="col4">'+item['JOB_NUMBER']+'</td><td class="col5">'+item['BELT_NUMBER']+'</td></tr>';
		});
		
		document.getElementById("special-textarea").value = jobs[0]['SPECIAL_INSTRUCTIONS'];
	}
	
	/**
	  *	@desc	sets customer long name
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
	  *	@desc	create/display list of tools to add to job
	  *	@param	none
	  *	@return	none
	*/
	function popToolList() {
		var searchText = document.getElementById("tool-input").value;
				
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		
		var html = '<table id="tool-list"><thead><tr><th class="col1">Tool</th><th class="col2">Tank Out</th></thead><tbody>';
		tools.forEach((item, index, array) => {
			if (item['TOOL'].toUpperCase().includes(searchText.toUpperCase()) && (item['STATUS'] != "NOGOOD" && item['STATUS'] != "RETIRED" && item['STATUS'] != "PURGED")) {
				html += '<tr id="'+item['ID']+'" onclick="selectToolRow(this)"><td class="col1">'+item['TOOL']+'</td><td class="col2">'+item['DATE_CREATED']+'</td></tr>';
			}
		});
		
		html += '</tbody></table><div id="controls"><span class="close" id="close">&times;</span><button onclick="saveToolList()">Save Tools</button><br><br><br><span># Selected:</span><br><span id="count">0</span></div>';
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight tool row, unhighlight if already highlighted
	  *	@param	DOM Object tr - tool row clicked on
	  *	@return	none
	*/
	function selectToolRow(tr) {
		if (tr.style.color != "white") {
			tr.style.backgroundColor = "black";
			tr.style.color = "white";
			document.getElementById("count").innerHTML = parseInt(document.getElementById("count-value").innerHTML) + 1;
		} else {
			tr.style.backgroundColor = "white";
			tr.style.color = "black";
			document.getElementById("count").innerHTML = parseInt(document.getElementById("count-value").innerHTML) - 1;
		}
	}
	
	/**
	  *	@desc	saves all currently highlighted tools to job
	  *	@param	none
	  *	@return	none
	*/
	function saveToolList() {
		var trs = document.getElementById("tool-list").children[1].children;
		
		for (var i=0;i<trs.length;i++) {
			if (trs[i].style.color == "white") {
				tools.forEach((item, index, array) => {
					if (item['TOOL'].toUpperCase() == trs[i].children[0].innerHTML.toUpperCase()) {
						document.getElementById("tool-table").children[1].innerHTML += '<tr id="'+item['ID']+'" onclick="selectRow(this)"><td class="col1">'+item['TOOL']+'</td><td class="col2"></td><td class="col3"></td><td class="col4"></td><td class="col5"></td></tr>';
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
	  *	@desc	highlights row in main job table, unhighlights others
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
	  *	@desc	removes tool from list
	  *	@param	none
	  *	@return	none
	*/
	function deleteTool() {
		var tr = document.getElementById(selectedRow);
		
		tr.parentNode.removeChild(tr);
	}
	
	/**
	  *	@desc	validates form
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
	  *	@desc	saves shipping job (each tool given separate WO #)
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == "") {
			var trs = document.getElementById("tool-table").children[1].children;
			var successCounter = 0;
			var d = new Date(document.getElementById("target-input").value);
			batch = {
				OPERATOR: document.getElementById("operator-input").value,
				MODIFIED_DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date(document.getElementById("target-input").value)),
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value,
				BATCH_NUMBER: <?=$_POST['batch']?>
			};
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "update";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Data updated")) {
					
						var conn2 = [];
						var table2 = "Shipping";
						var seqNum = jobs[0]['SEQNUM'];
					
						for(var i=0;i<trs.length;i++) {
							var query2 = "";
							var job = {
								BATCH_NUMBER: batch.BATCH_NUMBER,
								JOB_NUMBER: trs[i].children[3].innerHTML,
								SEQNUM: seqNum,
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
							
							var action2 = "insert";
							var foundJob = false;
							
							jobs.forEach((item, index, array) => {
								if (trs[i].id == item['ID']) {
									action2 = "update";
									if (item['IS_QUEUE'] === "true") {
										table2 = "Shipping_Queue";
									}
									job.id = item['ID'];
									foundJob = index;
								}
							});
							
							if (foundJob !== false) {
								jobs.splice(foundJob, 1);
							} else {
								job.WO_NUMBER = getNextWorkNumber();
							}
						
							Object.keys(job).forEach((item, index, array) => {
								if (item != "id") {
									query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
								}
							});
							
							if (job.hasOwnProperty("id")) {
								query2 += `&condition=id&value=${job['id']}`;
							}
							
							conn2[i] = new XMLHttpRequest();
							
							conn2[i].onreadystatechange = xmlResponse(i);
							
							conn2[i].open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
							conn2[i].send();
						}
						
						function xmlResponse(i) {
							return function() {
								if (conn2[i].readyState == 4 && conn2[i].status == 200) {
									if (conn2[i].responseText.includes("Insert succeeded.") || conn2[i].responseText.includes("Data updated")) {
										updateTool(job.TOOL, job.BELT_NUMBER);
										successCounter++;
										checkSuccess(successCounter);
									} else {
										alert("Batch created, but job entry failed. Contact support to correct. " + conn2[i].responseText);
									}
								}
							}
						}
					} else {
						alert("Batch not updated. Contact IT Support to correct. " + conn1.responseText);
					}
				}
			}
			
			var query1 = "";
			
			Object.keys(batch).forEach((item, index, array) => {
				if (item != "BATCH_NUMBER") {
					query1 += `&${item}=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				} else {
					query1 += `&condition=BATCH_NUMBER&value=${batch[item]}`;
				}
			})
			
			conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
			conn1.send();
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	checks if all jobs saved successfully
	  *	@param	int counter - current count of successes
	  *	@return	none
	*/
	function checkSuccess(counter) {
		if (counter == document.getElementById("tool-table").children[1].children.length) {
			deleteOldJobs();
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
		var query = "&BELT_NUMBER="+beltNumber.replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")+"&condition=TOOL&value="+tool.replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A");
		var conn = new XMLHttpRequest();
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					return;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	removes jobs if they were removed from list but were already scheduled
	  *	@param	none
	  *	@return	none
	*/
	function deleteOldJobs() {
		var deleteCounter = 0;
	
		if (jobs.length>0) {
			jobs.forEach((item, index, array) => {
				var conn = new XMLHttpRequest();
				if (item['IS_QUEUE'] === "true") {
					var table = "Shipping_Queue";
				} else {
					var table = "Shipping";
				}
				var action = "delete";
				var query = "&id="+item['ID'];
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Deletion succeeded")) {
							deleteCounter++;
							checkDeleteSuccess(deleteCounter);
						} else {
							alert("Could not delete old jobs. Contact support to correct. " + conn.responseText);
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn.send();
			})
		} else {
			alert("Job updated");
			<?php if ($_POST['source'] == 'holdlist') { ?>
			window.location.replace("holdlist.php");
			<?php } else { ?>
			window.location.replace("shipping.php");
			<?php } ?>
		}
	}
	
	/**
	  *	@desc	checks if all deletions successful
	  *	@param	int counter - count of successes
	  *	@return	none
	*/
	function checkDeleteSuccess(counter) {
		if (counter == jobs.length) {
			alert("Job updated");
			<?php if ($_POST['source'] == 'holdlist') { ?>
			window.location.replace("holdlist.php");
			<?php } else { ?>
			window.location.replace("shipping.php");
			<?php } ?>
		} else {
			return;
		}
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
	  *	@desc	returns next available WO#
	  *	@param	none
	  *	@return	int (max + 1) - last used WO# incremented by 1
	*/
	function getNextWorkNumber() {
		var conn = new XMLHttpRequest();
		var tables = ["Mastering","Mastering_Queue","Mastering_History","Toolroom","Toolroom_Queue","Toolroom_History","Electroforming","Electroforming_Queue","Electroforming_History","Shipping","Shipping_Queue","Shipping_History","Abort_History"];
		var action = "select";
		var condition = "WO_NUMBER";
		var max = 0;
		
		tables.forEach((item, index, array) => {
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					var response = conn.responseText.split("Array");
					response.shift();
					if (response.length > 0) {
						for (var i=0;i<response.length;i++) {
							response[i] = response[i].split(">");
							response[i].shift();
							for (var j=0;j<response[i].length;j++) {
								if (response[i][j].includes("DateTime")) {
									response[i][j] = response[i][j+1].split("[")[0].trim();
									response[i].splice(j+1,3);
								} else {
									response[i][j] = response[i][j].split("[")[0];
									if (j==38) {
										response[i][j] = response[i][j].split(")")[0];
									}
									response[i][j] = response[i][j].trim();
								}
							}
						}
						
						if (parseInt(response[0][2]) > max) {
							max = parseInt(response[0][2]);
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
	  *	@desc	creates/displays job info form, for PO#, Belt#, etc
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
	  *	@desc	saves job info and closes form
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
				trs[i].children[1].innerHTML = poNumber;
				trs[i].children[2].innerHTML = orderNumber;
				trs[i].children[3].innerHTML = jobNumber;
				trs[i].children[4].innerHTML = beltNumber;
			}
		} else {
			tr.children[1].innerHTML = poNumber;
			tr.children[2].innerHTML = orderNumber;
			tr.children[3].innerHTML = jobNumber;
			tr.children[4].innerHTML = beltNumber;
		}
		
		document.getElementById("close").click();
	}
	
	/**
	  *	@desc	auto-format date field to MM/DD/YY
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
					if (conn.responseText.includes("Array")) {
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
		<title>Edit Shipping Job</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addshipping.css">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
					<span id="customer-span">Customer<select id="customer-select" onchange="insertDescription(this)">
					<?php foreach($customers as $customer) { ?>
						<option value="<?=$customer['CUSTOMER']?>"><?=$customer['CUSTOMER']?></option>
					<?php } ?>
					</select><input type="text" id="customer-input"></span><br>
					<span id="target-span">Target<input onkeydown="fixDate(this)" type="text" id="target-input"></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="checkForRepeats()">Save</button>
					<a href="<?php if ($_POST['source'] == 'holdlist') { echo 'holdlist.php'; } else { echo 'shipping.php'; } ?>">Back</a>
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
