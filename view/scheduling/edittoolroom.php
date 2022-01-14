<!DOCTYPE html>
<?php
/**
  *	@desc edit already existing toolroom job
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
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of tools, blanks, jobs, and processes
	$tools = array();
	$blanks = array();
	$process = array();
	$job = array();
	$parquets = array();
	$isQueue = false;
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER FROM Tool_Tree ORDER BY TOOL;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tools[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		if ($_POST['process'] == "FRAMING") {
			$result = sqlsrv_query($conn, "SELECT ID, BLANK, LOCATION, DRAWER FROM Blanks ORDER BY BLANK;");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$blanks[] = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DURATION FROM Processes WHERE PROCESS = '" . $_POST['process'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$process = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Toolroom WHERE WO_NUMBER = '" . $_POST['woNumber'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$isQueue = true;
			$result = sqlsrv_query($conn, "SELECT * FROM Toolroom_Queue WHERE WO_NUMBER = '" . $_POST['woNumber'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		if ($_POST['process'] == "PARQUET") {
			$result = sqlsrv_query($conn, "SELECT TOOL_IN, WO_NUMBER FROM Toolroom WHERE TOOL_OUT = '" . $job['TOOL_OUT'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$parquets[] = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
	} else {
		print_r(sqlsrv_errors());
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
					echo date_format($value,"m/d/y");
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
	
	if ("<?=$_POST['process']?>" == "FRAMING") {
		var blanks = [<?php
			foreach($blanks as $blank) {
				echo '{';
				foreach($blank as $key=>$value) {
					echo '"' . $key . '": `';
					if ($value instanceof DateTime) {
						echo date_format($value,"m/d/y");
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
	}
	
	if ("<?=$_POST['process']?>" == "PARQUET") {
		var parquets = [<?php
			foreach($parquets as $parquet) {
				echo '{';
				foreach($parquet as $key=>$value) {
					echo '"' . $key . '": `';
					if ($value instanceof DateTime) {
						echo date_format($value,"m/d/y");
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
	}
	
	/**
	  *	@desc	insert job data
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
		
		document.getElementById("job-input").value = "<?=$job['JOB_NUMBER']?>";
		document.getElementById("wo-input").value = "<?=$job['WO_NUMBER']?>";
		document.getElementById("po-input").value = "<?=$job['PO_NUMBER']?>";
		document.getElementById("date-input").value = "<?=date_format($job['DATE_IN'],'m/d/y')?>";
		document.getElementById("target-input").value = "<?=date_format($job['TARGET_DATE'],'m/d/y')?>";
		document.getElementById("special-textarea").value = `<?=$job['SPECIAL_INSTRUCTIONS']?>`;
		
		<?php if ($_POST['process'] == "PARQUET") { ?>
		var table = document.getElementById('tool-list').children[1];
		<?php foreach ($parquets as $parquet) { ?>
		table.innerHTML += '<tr id="' + table.children.length + '" onclick="selectRow(this)"><td><?=$parquet['TOOL_OUT']?></td></tr>';
		<?php } ?>
		<?php } else { ?>
		document.getElementById("tool-input").value = "<?=$job['TOOL_IN']?>";
		<?php } ?>
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date object to convert
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
	  *	@desc	highlight parquet row, unhighlight others
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
	  *	@desc	remove tool from parquet job
	  *	@param	none
	  *	@return	none
	*/
	function deleteTool() {
		var row = document.getElementById(selectedRow);
		var rows = row.parentNode.children;
		row.parentNode.removeChild(row);
		for (var i=0;i<rows.length;i++) {
			rows[i].id = i;
		}
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
		var isBlank = false;
	
		if ("<?=$_POST['process']?>" == "FRAMING") {
			if (document.getElementById("blank-input").checked) {
				isBlank = true;
			}
		}
		
		var searchText = document.getElementById("tool-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span>`;
		
		if (isBlank) {
			html += `<table id="blank-table"><thead><tr><th class="col1">Blank</th><th class="col2">Location</th><th class="col3">Drawer</th></tr></thead><tbody>`;
			
			blanks.forEach((item, index, array) => {
				if (item['BLANK'].toUpperCase().includes(searchText.toUpperCase())) {
					html += `<tr id="${item['ID']}" onclick="selectToolRow(this)"><td class="col1">${item['BLANK']}</td><td class="col2">${item['LOCATION']}</td><td class="col3">${item['DRAWER']}</td></tr>`;
				}
			});
			html += `</tbody></table>`;
			
		} else {
			html += `<table id="tool-table"><thead><tr><th class="col1">Tool</th><th class="col2">Status</th><th class="col3">Reason</th><th class="col4">Location</th><th class="col5">Drawer</th></tr></thead><tbody>`;
			
			tools.forEach((item, index, array) => {
				if (item['TOOL'].toUpperCase().includes(searchText.toUpperCase())) {
					html += `<tr id="${item['ID']}" onclick="selectToolRow(this)"><td class="col1">${item['TOOL']}</td><td class="col2">${item['STATUS']}</td><td class="col3">${item['REASON']}</td><td class="col4">${item['LOCATION']}</td><td class="col5">${item['DRAWER']}</td></tr>`;
				}
			});
			html += `</tbody></table>`;
		}
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight tool row, unhighlight others
	  *	@param	DOM Object tr - tool row selected
	  *	@return	none
	*/
	function selectToolRow(tr) {
		var trs = tr.parentNode.children;
		
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectToolRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmToolRow(this)');
	}
	
	/**
	  *	@desc	confirm this tool
	  *	@param	DOM Object tr - tool clicked on
	  *	@return	none
	*/
	function confirmToolRow(tr) {
		if (["NOGOOD","RETIRED","PURGED"].includes(tr.children[1].innerHTML)) {
			alert("Cannot schedule this tool");
		} else {
			if ("<?=$_POST['process']?>" == "PARQUET") {
				document.getElementById("tool-list").children[1].innerHTML += `<tr><td>${tr.children[0].innerHTML}</td></tr>`;
			} else {
				document.getElementById("tool-input").value = tr.children[0].innerHTML;
			}
		
			document.getElementById("close").click();
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
	  *	@desc	fill in target date given start date
	  *	@param	DOM Object input - start date field
	  *	@return	none
	*/
	function fillTarget(input) {
		var key = /^[0-9]?[0-9][-\/][0-9]?[0-9][-\/](?:[0-9]{2}){1,2}$/;
		var d = new Date(input.value);
		if (key.test(input.value.trim())) {
			input.value = formatDate(new Date(input.value));
			document.getElementById("target-input").value = formatDate(new Date(d.setDate(d.getDate() + <?=$process['DURATION']?>)));
		} else {
			document.getElementById("target-input").value = "invalid date input";
		}
	}
	
	/**
	  *	@desc	fill in start date given target date
	  *	@param	DOM Object input - target date field
	  *	@return	none
	*/
	function fillStart(input) {
		var key = /^[0-9]?[0-9][-\/][0-9]?[0-9][-\/](?:[0-9]{2}){1,2}$/;
		var d = new Date(input.value);
		if (key.test(input.value.trim())) {
			input.value = formatDate(new Date(input.value));
			document.getElementById("date-input").value = formatDate(new Date(d.setDate(d.getDate() - <?=$process['DURATION']?>)));
		} else {
			document.getElementById("date-input").value = "invalid date input";
		}
	}
	
	/**
	  *	@desc	make sure data entry is valid before submit
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		switch("<?=$_POST['process']?>") {
			case "PARQUET":
				if (document.getElementById("date-input").value == "" || document.getElementById("target-input").value == "") {
					msg = "Invalid date";
				} else if (document.getElementById("tool-list").children[1].children.length < 2) {
					msg = "Please select at least two tools";
				}
				break;
			default:
				if (document.getElementById("date-input").value == "" || document.getElementById("target-input").value == "") {
					msg = "Invalid date";
				} else if (document.getElementById("tool-input").value == "") {
					msg = "Please select a tool";
				}
		}
		
		return msg;
	}
	
	/**
	  *	@desc	submit job changes
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == "") {
			var d = new Date(document.getElementById("date-input").value);
			batch = {
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: document.getElementById("target-input").value,
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value,
				BATCH_NUMBER: "<?=$job['BATCH_NUMBER']?>"
			};
			
			<?php if ($_POST['process'] != "PARQUET") { ?>
			job = {
				BATCH_NUMBER: batch.BATCH_NUMBER,
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				PROCESS: "<?=$_POST['process']?>",
				SEQNUM: 1,
				TARGET_DATE: document.getElementById("target-input").value,
				TOOL_IN: document.getElementById("tool-input").value,
				DATE_IN: formatDate(d),
				DATE_OUT: formatDate(new Date(document.getElementById("target-input").value)),
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value
			};
			
			<?php if ($_POST['process'] == "CONVERTING") { ?>
			
			<?php } else if ($_POST['process'] == "FRAMING") { ?>
			var input = document.getElementById('tool-input');
			if (/[-][A-Z][-]/g.test(input.value)) {
				job.TOOL_OUT = input.value.split(/[-][A-Z][-]/g)[0] + "/0(" + job.TOOL_IN + ")";
			} else if (/[-][A-Z][A-Z][-]/g.test(input.value)) {
				job.TOOL_OUT = input.value.split(/[-][A-Z][A-Z][-]/g)[0] + "/0(" + job.TOOL_IN + ")";
			}
			<?php } else { ?>
			job.TOOL_OUT = job.TOOL_IN;
			<?php } ?>
			job.WO_NUMBER = <?=$job['WO_NUMBER']?>;
			<?php } ?>
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "update";
			var conn2 = new XMLHttpRequest();
			var table2 = "<?=$isQueue?>" ? "Toolroom_Queue" : "Toolroom";
			var action2 = "update";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Data updated")) {
						<?php if ($_POST['process'] == "PARQUET") { ?>
						successCounter = 0;
						conn2 = [];
						var query2 = "";
						var trs = document.getElementById('tool-list').children[1].children;
						
						var extrasDeleted = true;
						var extrasInserted = true;
						if (parquets.length < trs.length) {
							extrasAdded = addExtras(trs.length);
						} else if (parquets.length > trs.length) {
							extrasDeleted = deleteExtras(trs.length);
						}
						
						if (extrasDeleted && extrasInserted) {
							for(var i=0;i<trs.length;i++) {
								job = {
									BATCH_NUMBER: batch.BATCH_NUMBER,
									PO_NUMBER: document.getElementById("po-input").value,
									JOB_NUMBER: document.getElementById("job-input").value,
									PROCESS: "<?=$_POST['process']?>",
									SEQNUM: 1,
									TARGET_DATE: document.getElementById("target-input").value,
									TOOL_IN: trs[i].children[0].innerHTML,
									DATE_IN: formatDate(d),
									DATE_OUT: document.getElementById("target-input").value,
									SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
								};
								
								var toolIn = job.TOOL_IN.split("-");
								var toolOut = "";
								for(var j=0;j<toolIn.length-1;j++) {
									toolOut += toolIn[j] + "-";
								}
								toolOut += "(";
								for(var j=0;j<trs.length;j++) {
									if (j==trs.length-1) {
										toolOut += trs[j].children[0].innerHTML.split("-").pop();
									} else {
										toolOut += trs[j].children[0].innerHTML.split("-").pop() + "+";
									}
								}
								toolOut += ")";
								job.TOOL_OUT = toolOut;
								
								job.WO_NUMBER = parquets[i]['WO_NUMBER'];
								
								query2 = "";
								
								Object.keys(job).forEach((item, index, array) => {
									if (item != "WO_NUMBER") {
										query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
									} else {
										query2 += `&condition=WO_NUMBER&value=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
									}
								});
								
								conn2[i] = new XMLHttpRequest();
								
								conn2[i].onreadystatechange = xmlResponse(i);
								
								conn2[i].open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
								conn2[i].send();
							}
						}
						
						function xmlResponse(i) {
							return function() {
								if (conn2[i].readyState == 4 && conn2[i].status == 200) {
									if (conn2[i].responseText.includes("Data updated")) {
										successCounter++;
										checkSuccess(successCounter);
									} else {
										alert("Batch updated, but job entry failed. Contact support to correct. " + conn2.responseText);
									}
								}
							}
						}
						<?php } else { ?>
					
						var query2 = "";
						
						conn2.onreadystatechange = function() {
							if (conn2.readyState == 4 && conn2.status == 200) {
								if (conn2.responseText.includes("Data updated")) {
									if ("<?=$_POST['process']?>" == "CONVERT" || "<?=$_POST['process']?>" == "FRAMING") {
										alert("Job updated");
										<?php if ($_POST['source'] == "holdlist") { ?>
										window.location.replace("holdlist.php");
										<?php } else { ?>
										window.location.replace("toolroom.php");
										<?php } ?>
									} else {
										alert("Job updated");
										<?php if ($_POST['source'] == "holdlist") { ?>
										window.location.replace("holdlist.php");
										<?php } else { ?>
										window.location.replace("toolroom.php");
										<?php } ?>
									}
								} else {
									alert("Batch updated, but job entry failed. Contact support to correct. " + conn2.responseText);
								}
							}
						}
						
						Object.keys(job).forEach((item, index, array) => {
							if (item != "WO_NUMBER") {
								query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							} else {
								query2 += `&condition=WO_NUMBER&value=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							}
						})
						
						conn2.open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
						conn2.send();
						
						<?php } ?>
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
			});
			
			conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
			conn1.send();
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	add jobs to parquet that were not already present
	  *	@param	int n - length of tool list
	  *	@return	true on success, false otherwise
	*/
	function addExtras(n) {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "insert";
		var successCounter = 0;
		var newItems = [];
		var success = false;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					successCounter++;
					if (successCounter >= n-parquets.length) {
						newItems.forEach((item, index, array) => {
							parquets.push(item);
						});
						success = true;
					}
				}
			}
		}
		
		for (var i=0;i<n - parquets.length;i++) {
			var extra = {
				BATCH_NUMBER: batch.BATCH_NUMBER,
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				PROCESS: "<?=$_POST['process']?>",
				SEQNUM: 1,
				TARGET_DATE: document.getElementById("target-input").value,
				TOOL_IN: "PLACEHOLDER",
				DATE_IN: formatDate(new Date()),
				DATE_OUT: document.getElementById("target-input").value,
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
				WO_NUMBER: getNextWorkNumber(),
			};
			
			var query = "";
			
			Object.keys(extra).forEach((item, index, array) => {
				query += `&${item}=${extra[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			});
			
			newItems.push(["PLACEHOLDER",extra.WO_NUMBER]);
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
			conn.send();
		}
		
		return success;
	}
	
	/**
	  *	@desc	delete parquet jobs that have been removed
	  *	@param	int n - length of tool list
	  *	@return	none
	*/
	function deleteExtras(n) {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "delete";
		var successCounter = 0;
		var success = false;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					successCounter++;
					if (successCounter >= (parquets.length - n)) {
						success = true;;
					}
				}
			}
		}
		
		for (var i=parquets.length-1;i>n-1;i--) {
			var query = "&WO_NUMBER="+parquets[i]['WO_NUMBER'];
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query.replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A"), false);
			conn.send();
		}
		
		
		return success;
	}
	
	/**
	  *	@desc	fetch the next work order number to assign to a job
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
	  *	@desc	returns count of all jobs that a tool has been output from
	  *	@param	string tool - tool to search for
	  *	@return	int jobs - total number of jobs
	*/
	function getJobs(tool) {
		var conn = new XMLHttpRequest();
		var tables = ["Toolroom"];
		var action = "select";
		var condition = "TOOL_OUT";
		var value = tool;
		var jobs = 0;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var job = conn.responseText.split("Array");
				job.shift();
				for (var i=0;i<job.length;i++) {
					job[i] = job[i].split(">");
					job[i].shift();
					for (var j=0;j<job[i].length;j++) {
						if (job[i][j].includes("DateTime")) {
							job[i][j] = job[i][j+1].split("[")[0].trim();
							job[i].splice(j+1,3);
						} else {
							job[i][j] = job[i][j].split("[")[0];
							if (j==job[i].length-1) {
								job[i][j] = job[i][j].split(")")[0];
							}
							job[i][j] = job[i][j].trim();
						}
					}
				}
				jobs += job.length;
			}
		}
		
		tables.forEach((item, index, array) => {
			conn.open("GET","/db_query/sql2.php?table="+item+"&action="+action+"&condition="+condition+"&value="+value,false);
			conn.send();
		});
		
		return jobs;
	}
	
	/**
	  *	@desc	reserves new tool name, if applicable
	  *	@param	none
	  *	@return	none
	*/
	function addTool() {
		if (removeOldTool()) {
		
			var tool = {
				MANDREL: job.TOOL_IN,
				TOOL: job.TOOL_OUT,
				LEVEL: 0,
				STATUS: "GOOD",
			}
			
			if ("<?=$_POST['process']?>" == "PARQUET") {
				var toolIn = job.TOOL_IN.split("-");
				var mandrel = "";
				for(var j=0;j<toolIn.length-1;j++) {
					if (j == toolIn.length-2) {
						mandrel += toolIn[j];
					} else {
						mandrel += toolIn[j] + "-";
					}
				}
			}
			
			var conn = new XMLHttpRequest();
			var table = "Tool_Tree";
			var action = "insert";
			var query = "";
			
			Object.keys(tool).forEach((item, index, array) => {
				query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			});
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						alert("Job updated");
						<?php if ($_POST['source'] == "holdlist") { ?>
						window.location.replace("holdlist.php");
						<?php } else { ?>
						window.location.replace("toolroom.php");
						<?php } ?>
					} else {
						alert("Job updated, but tool name not reserved. Contact support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn.send();
		} else {
			alert("Job updated, but tool name not reserved. Contact support to correct. " + conn.responseText);
		}
	}
	
	/**
	  *	@desc	removes old tool name, if applicable
	  *	@param	none
	  *	@return	true on success, false otherwise
	*/
	function removeOldTool() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Tool_Tree";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					return true;
				} else {
					return false;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&TOOL="+job.TOOL_OUT, true);
		conn.send();
	}
	
	/**
	  *	@desc	determines if all jobs have been successfully scheduled
	  *	@param	none
	  *	@return	none
	*/
	function checkSuccess(counter) {
		if (counter == document.getElementById("tool-list").children[1].children.length) {
			alert("Job updated");
			<?php if ($_POST['source'] == "holdlist") { ?>
			window.location.replace("holdlist.php");
			<?php } else { ?>
			window.location.replace("toolroom.php");
			<?php } ?>
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	auto-formats date field to MM/DD/YY
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
		if ("<?=$_POST['process']?>" == "PARQUET") {
			var trs = document.getElementById("tool-list").children[1].children;
			for (i=0;i<trs.length;i++) {
				table = "Toolroom_History";
				condition = "TOOL_IN";
				
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
				
				conn.open("GET","/db_query/sql2.php?action=" + action + "&table=" + table + "&condition=" + condition + "&value=" + trs[i].children[0].innerHTML.replace(/[+]/g,"%2B") + "&condition2=PROCESS&value2=<?=$_POST['process']?>", false);
				conn.send();
			}
		} else {
			table = "Toolroom_History";
			condition = "TOOL_IN";
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Array")) {
						if (repeats) {
							repeats.push(document.getElementById("tool-input").value);
						} else {
							repeats = [document.getElementById("tool-input").value];
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action=" + action + "&table=" + table + "&condition=" + condition + "&value=" + document.getElementById("tool-input").value.replace(/[+]/g,"%2B") + "&condition2=PROCESS&value2=<?=$_POST['process']?>", false);
			conn.send();
		}
		
		if (repeats) {
			var modal = document.getElementById('modal');
			var modalContent = document.getElementById('modal-content');
			modalContent.style.width = "auto";
			var html = "<p>At least one of your jobs has already been processed. Repeated jobs:<p><ul>";
			
			for (i=0;i<repeats.length;i++) {
				html += "<li><p>Tool: <strong>" + repeats[i] + "</strong></p></li>";
			}
			
			html += "</ul><p>Continue anyway?</p><button style=\"margin-left: 75px;\" id=\"continue-button\">Yes</button><button style=\"margin-left: 50px;\" id=\"cancel-button\">No</button>";
			
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
		<title>Add Tool Room Job</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addtoolroom.css">
	</head>
	<body onload="initialize()">
		<div class="outer" <?php if ($_POST['process'] == "PARQUET") { ?> style="height: 460px;" <?php } ?>>
			<div class="inner" <?php if ($_POST['process'] == "PARQUET") { ?> style="height: 450px;" <?php } ?>>
				<?php if ($_POST['process'] == "PARQUET") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input" readonly></span><span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="date-span">Start Date<input onkeydown="fixDate(this)" type="text" id="date-input" onblur="fillTarget(this)"></span><span id="target-span">Target Date<input type="text" id="target-input" onblur="fillStart(this)"></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
					<table id="tool-list">
						<thead>
							<tr>
								<th>Tools</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><br>
					Tool<input type="text" id="tool-input" style="width: 400px;">
				</div>
				<div class="controls">
					<button onclick="checkForRepeats()">Save</button>
					<a href="<?php if ($_POST['source'] == 'holdlist') { echo 'holdlist.php'; } else { echo 'toolroom.php'; } ?>">Back</a>
					<button onclick="wait()" style="margin-top: 60px;">Add</button><br>
					<button onclick="deleteTool()">Delete</button>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="70" id="special-textarea"></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input" readonly></span><span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="tool-span"><button onclick="wait()" id="search-button">Search</button>Tool<input type="text" id="tool-input"></span><?php if ($_POST['process'] == "FRAMING") { ?><span id="blank-span"><input type="checkbox" id="blank-input">Blank</span><?php } ?><br>
					<span id="date-span">Start Date<input onkeydown="fixDate(this)" type="text" id="date-input" onblur="fillTarget(this)"></span><span id="target-span">Target Date<input type="text" id="target-input" onblur="fillStart(this)"></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="checkForRepeats()">Save</button>
					<a href="<?php if ($_POST['source'] == 'holdlist') { echo 'holdlist.php'; } else { echo 'toolroom.php'; } ?>">Back</a>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="70" id="special-textarea"></textarea>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
