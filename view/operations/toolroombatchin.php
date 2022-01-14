<!DOCTYPE html>
<?php
/**
  *	@desc process tool into toolroom job
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//process and tool details, value lists to choose from
	$process = array();
	$tools = array();
	$programs = array();
	$machines = array();
	$processes = array();
	$locations = array();
	$defects = array();
	$statuses = array();
	$jobs = array();
	$batchNum = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT BATCH_NUMBER FROM Toolroom WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$batchNum = $row['BATCH_NUMBER'];
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Toolroom WHERE BATCH_NUMBER = " . $batchNum . " AND PROCESS = '" . $_POST['process'] . "' AND (OPERATOR_IN IS NULL OR OPERATOR_IN = '');");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DURATION FROM Processes WHERE PROCESS = '" . $_POST['process'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$process = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		foreach($jobs as $job) {
			$result = sqlsrv_query($conn, "SELECT ID, TOOL, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$tools[$row['TOOL']] = $row;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, PROGRAM, APERTURE FROM Program_Apertures WHERE STATUS = 'Active';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$programs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS, DURATION FROM Processes WHERE DEPARTMENT = 'TOOLRM';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[$row['PROCESS']] = $row['DURATION'];
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, MACHINE, DESCRIPTION FROM Valid_Machines WHERE PROCESS = '" . $_POST['process'] . "' ORDER BY MACHINE;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$machines[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION FROM Inv_Locations WHERE STATUS = 'Active' ORDER BY LOCATION ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STATUS FROM Tool_Status WHERE STATE = 'Active' ORDER BY STATUS ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT FROM Valid_Defects WHERE STATUS = 'Active' ORDER BY DEFECT ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
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
	var toolStatuses = [];
	var isMetric = false;
	var selectedProgramRow = 0;
	var programs = [<?php
		foreach($programs as $program) {
			echo '{';
			foreach($program as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
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
	
	var machines = [<?php
		foreach($machines as $machine) {
			echo '{';
			foreach($machine as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
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
	
	var jobs = [<?php
		foreach($jobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
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
	
	var tools = {<?php
		foreach($tools as $name => $tool) {
			echo "\"$name\": {";
			foreach($tool as $key=>$value) {
				echo "\"$key\": `$value`,";
			}
			echo "},\n";
		}
	?>};
	
	/**
	  *	@desc	insert date and operator name
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		document.getElementById("date-input").value = formatDate(new Date());
		<?php if($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?>
		document.getElementById("operator-input").value = "<?=$_SESSION['initials']?>";
		<?php } ?>
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
	  *	@desc	create/display list of programs
	  *	@param	DOM Object button - search button clicked on
	  *	@return	none
	*/
	function popProgramList(button) {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var searchText = button.parentNode.previousElementSibling.previousElementSibling.previousElementSibling.children[0].value;
		var details = document.getElementsByClassName("details");
		var index;
		for (var i=0;i<details.length;i++) {
			if (details[i].children[7].children[0] == button.parentNode.previousElementSibling.previousElementSibling.previousElementSibling.children[0]) {
				index = i;
			}
		}
		var html = `<span class="close" id="close">&times;</span><input type="text" hidden value="${index}" id="index"><table id="program-table"><thead><tr><th class="col1">Program</th><th class="col2">Aperture</th></thead><tbody>`;
		
		programs.forEach((item, index, array) => {
			if (item['PROGRAM'].toUpperCase().includes(searchText.toUpperCase())) {
				html += `<tr onclick="selectProgramRow(this)"><td class="col1">${item['PROGRAM']}</td><td class="col2">${item['APERTURE']}</td></tr>`;
			}
		});
		
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		modal.style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight program row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectProgramRow(tr) {
		var trs = tr.parentNode.children;
		
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectProgramRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmProgramRow(this)');
	}
	
	/**
	  *	@desc	insert program to job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmProgramRow(tr) {
		var index = document.getElementById("index").value;
		document.getElementsByClassName("program-input")[index].value = tr.children[0].innerHTML;
		aperture = tr.children[1].innerHTML;
		if (isMetric) {
			document.getElementsByClassName("aperture-input")[index].value = aperture;
		} else {
			document.getElementsByClassName("aperture-input")[index].value = convert(aperture);
		}
		selectedProgramRow = tr.id;
		document.getElementById("close").click();
	}
	
	/**
	  *	@desc	get machine description
	  *	@param	none
	  *	@return	none
	*/
	function insertDescription() {
		machines.forEach((item, index, array) => {
			if (item['MACHINE'] == document.getElementById("machine-select").value) {
				document.getElementById("machine-input").value = item['DESCRIPTION'];
			}
		});
	}
	
	/**
	  *	@desc	switch between mm and in
	  *	@param	DOM Object button - to switch label
	  *	@return	none
	*/
	function switchUnit(button) {
		var units = document.getElementsByClassName('unit');
		var values = "<?=$_POST['process']?>" == "FRAMING" ? document.getElementsByClassName("aperture-input") : '';
		if (isMetric) {
			button.innerHTML = "Metric";
			for (var i=0;i<units.length;i++) {
				units[i].innerHTML = "(in)";
			}
			isMetric = false;
			for (var i=0;i<values.length;i++) {
				if ("<?=$_POST['process']?>" == "FRAMING") {
					if (values[i].value != "") {
						values[i].value = convert(values[i].value);
					}
				}
			}
		} else {
			button.innerHTML = "English";
			for (var i=0;i<units.length;i++) {
				units[i].innerHTML = "(mm)";
			}
			isMetric = true;
			for (var i=0;i<values.length;i++) {
				if ("<?=$_POST['process']?>" == "FRAMING") {
					if (values[i].value != "") {
						values[i].value = convert(values[i].value);
					}
				}
			}
		}
	}
	
	/**
	  *	@desc	convert from mm to in and vice versa
	  *	@param	string value - number to be converted
	  *	@return	none
	*/
	function convert(value) {
		if (isMetric) {
			return (parseFloat(value) * 25.4).toFixed(3);
		} else {
			return (parseFloat(value) / 25.4).toFixed(3);
		}
	}
	
	/**
	  *	@desc	create/display quality info for tool
	  *	@param	str tool - name of tool to assign quality to
	  *	@return	none
	*/
	function popQuality(tool) {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html;
		var i = 0;
		modal.style.display = "block";
		jobs.forEach((item, index, array) => {
			if (item['TOOL_IN'] == tool) {
				html = `<span class="close" id="close">&times;</span><div style="display: inline-block;">
						<span style="margin-left: 3px;">New Tool<input type="text" id="tool-quality-input" value="${item['TOOL_IN']}" style="width: 266px;"></span><br>
						<span style="margin-left: 31px;">Date<input type="text" id="date-quality-input" value="${document.getElementById("date-input").value}" style="width: 100px;"></span>
						<span>Location<select id="location-quality-select">
						<?php foreach($locations as $location) { ?>
						<option value="<?= $location['LOCATION'] ?>"><?= $location['LOCATION'] ?></option>
						<?php } ?>
						</select></span>
						<span>Drawer<input type="text" id="drawer-quality-input" style="width: 100px;"></span><br>
						<span style="margin-left: 5px;">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-quality-input" value="${document.getElementById("operator-input").value}" style="width: 100px;"></span>
						<span style="margin-left: 13px;">Status<select id="status-quality-select">
						<?php foreach($statuses as $status) { ?>
						<option value="<?= $status['STATUS'] ?>"><?= $status['STATUS'] ?></option>
						<?php } ?>
						</select></span>
						<span style="margin-left: 5px;">Defect<select id="defect-quality-select">
						<?php foreach($defects as $defect) { ?>
						<option value="<?= $defect['DEFECT'] ?>"><?= $defect['DEFECT'] ?></option>
						<?php } ?>
						</select></span></div>
						<button onclick="saveQuality(${index})" style="display: inline-block; vertical-align: top; width: 60px; margin-top: 8px; margin-right: 5px; margin-left: 5px; background-color: black; color: white;">Save</button><br>
						<span>Apply to Process<input type="text" id="process-quality-input" value="<?=$_POST['process']?>" readonly style="width: 100px;"></span>
						<span>Apply to WO #<input type="text" id="wo-quality-input" value="${item['WO_NUMBER']}" readonly style="width: 100px;"></span>`;
			
				i = index;
			}
		});
		
		modalContent.innerHTML = html;
			
		document.getElementById("location-quality-select").value = document.getElementsByClassName("location-input")[i].value;
		document.getElementById("drawer-quality-input").value = document.getElementsByClassName("drawer-input")[i].value;
		
		closeForm();
	}
	
	/**
	  *	@desc	save quality information to status object
	  *	@param	int index - index of the tool being assigned quality
	  *	@return	none
	*/
	function saveQuality(index) {
		toolStatuses[index] = {};
		toolStatuses[index].TOOL = document.getElementById("tool-quality-input").value;
		toolStatuses[index].STATUS = document.getElementById("status-quality-select").value;
		toolStatuses[index].REASON = document.getElementById("defect-quality-select").value;
		toolStatuses[index].PROCESS = document.getElementById("process-quality-input").value;
		toolStatuses[index].WO_NUMBER = document.getElementById("wo-quality-input").value;
		toolStatuses[index].OPERATOR = document.getElementById("operator-quality-input").value;
		toolStatuses[index].LOCATION = document.getElementById("location-quality-select").value;
		toolStatuses[index].DRAWER = document.getElementById("drawer-quality-input").value;
		
		document.getElementsByClassName("location-input")[index].value = document.getElementById("location-quality-select").value;
		document.getElementsByClassName("drawer-input")[index].value = document.getElementById("drawer-quality-input").value;
		
		document.getElementById("close").click();
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
	function saveJobs() {
		var msg = checkFields();
		
		if (msg == '') {
			var statusComplete = true;
			toolStatuses.forEach((item, index, array) => {
				if (item == undefined) {
					statusComplete = false;
				}
			});
			if (toolStatuses.length < jobs.length) {
				statusComplete = false;
			}
			if (statusComplete == true) {
				var counter = 0;
				var required = jobs.length;
				jobs.forEach((item, index, array) => {
					var conn = new XMLHttpRequest();
					var table = "Toolroom";
					var action = "update";
					var query = "";
					var d = new Date();
					var job = {
						DATE_IN: formatDate(d),
						OPERATOR_IN: document.getElementById("operator-input").value,
						TOOL_OUT: item['TOOL_IN'],
						DATE_OUT: formatDate(new Date(d.setDate(d.getDate() + <?= $processes[$_POST['process']] ?>))),
					};
					
					if ("<?=$_POST['process']?>" == "FRAMING") {
						job.PROGRAM_NUMBER = document.getElementsByClassName("program-input")[index].value;
					}
					
					job.COMMENT = document.getElementsByClassName("comment-textarea")[index].value;
					job.STATUS_IN = toolStatuses[index].STATUS;
					
					if ("<?=$_POST['process']?>" != "FRAMING" && "<?=$_POST['process']?>" != "LOGO") {
						job.MACHINE_NUMBER = document.getElementById("machine-select").value;
					}
					
					job.id = item['ID'];
					
					conn.onreadystatechange = function() {
						if (conn.readyState == 4 && conn.status == 200) {
							if (conn.responseText.includes("success")) {
								counter++;
								if (counter >= required) {
									saveTools();
								}
							} else {
								alert("Job not saved. Contact IT Support to correct. " + conn.responseText);
							}
						}
					}
					
					Object.keys(job).forEach((item2, index2, array2) => {
						if (item2 != 'id') {
							query += `&${item2}=${job[item2].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
						} else {
							query += `&condition=id&value=${job[item2]}`;
						}
					})
					
					conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
					conn.send();
				});
			} else {
				alert("Complete quality first");
			}
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	update TOOL_OUT with new data
	  *	@param	none
	  *	@return	none
	*/
	function saveTools() {
		var counter = 0;
		var required = toolStatuses.length;
		toolStatuses.forEach((item, index, array) => {
			var tool = {
				LOCATION: item.LOCATION,
				DRAWER: item.DRAWER,
				STATUS: item.STATUS,
				REASON: item.REASON,
				DATE_MODIFIED: formatDate(new Date()),
				OPERATOR: item.OPERATOR,
				id: tools[item.TOOL]['ID']
			}
			
			var conn = new XMLHttpRequest();
			var table = "Tool_Tree";
			var action = "update";
			var query = "";
			
			Object.keys(tool).forEach((item2, index2, array2) => {
				if (item2 != 'id') {
					query += `&${item2}=${tool[item2].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				} else {
					query += `&condition=id&value=${tool[item2]}`;
				}
			})
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("success")) {
						counter++;
						if (counter >= required) {
							saveComments();
						}
					} else {
						alert("Tool data not saved. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query,false);
			conn.send();
		});
	}
	
	/**
	  *	@desc	save new comment data
	  *	@param	none
	  *	@return	none
	*/
	function saveComments() {
		var counter = 0;
		var required = Object.keys(tools).length;
		for (var item in tools) {
			var containers = document.getElementsByClassName("details-container");
			for (var i=0;i<containers.length;i++) {
				if (containers[i].children[0].children[1].innerText == item) {
					var comment = {
						COMMENT: containers[i].getElementsByClassName("comment-textarea")[0].value,
						PROCESS: "<?=$_POST['process']?>",
						TOOL: item,
						DATE: formatDate(new Date()),
						OPERATOR: document.getElementById("operator-input").value
					}
				}
			}
			
			var conn = new XMLHttpRequest();
			var table = "Comment_History";
			var action = "insert";
			var query = "";
			
			Object.keys(comment).forEach((item, index, array) => {
				query += `&${item}=${comment[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			})
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						counter++;
						if (counter >= required) {
							saveStatuses();
						}
					} else {
						alert("Comment not saved. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			if (comment.COMMENT.length > 0) {
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
				conn.send();
			} else {
				counter++;
				if (counter >= required) {
					saveStatuses();
				}
			}
		}
	}
	
	/**
	  *	@desc	save tool status update
	  *	@param	none
	  *	@return	none
	*/
	function saveStatuses() {
		var counter = 0;
		var required = toolStatuses.length;
		toolStatuses.forEach((item, index, array) => {
			item.DATE = formatDate(new Date());
		
			var conn = new XMLHttpRequest();
			var table = "Tool_Status_History";
			var action = "insert";
			var query = "";
			
			Object.keys(item).forEach((item2, index2, array2) => {
				if (item2 != "LOCATION" && item2 != "DRAWER") {		
					query += `&${item2}=${item[item2].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				}
			})
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded.")) {
						counter++;
						if (counter >= required) {
							alert("Job updated");
							document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
							document.getElementById("return-form").submit();
						}
					} else {
						alert("Tool status not saved. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
			conn.send();
		});
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
	  *	@desc	show job details
	  *	@param	DOM Object div - block header for job
	  *	@return	none
	*/
	function showDetails(div) {
		var details = div.parentNode.getElementsByClassName("details")[0];
		if (details.style.display == "none") {
			details.style.display = "inline-block";
			div.children[0].classList.remove("right-arrow");
			div.children[0].classList.add("down-arrow");
		} else {
			details.style.display = "none";
			div.children[0].classList.remove("down-arrow");
			div.children[0].classList.add("right-arrow");
		}
	}
	
	function goBack() {
		document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
		document.getElementById("return-form").submit();
	}
</script>
<html>
	<head>
		<title>Tool In</title>
		<?php if ($_POST['process'] == "FRAMING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/framingbatchin.css">
		<?php } else if ($_POST['process'] == "LOGO") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/logobatchin.css">
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/backmachinebatchin.css">
		<?php } ?>
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<?php if ($_POST['process'] == "FRAMING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJobs()">Save</button><br>
					<button onclick="goBack()">Back</button><br>
					<button onclick="switchUnit(this)">Metric</button><br>
				</div><br><br>
				<?php foreach($jobs as $job) { ?>
				<div class="details-container"><span onclick="showDetails(this)" style="cursor: pointer;"><div class="down-arrow"></div><span class="tool"><strong><?=$job['TOOL_IN']?></strong></span><span style="float: right;">WO#: <?=$job['WO_NUMBER']?></span></span>
					<div class="details">
						<span class="location-span">Location<input type="text" class="location-input" value="<?=$tools[$job['TOOL_IN']]['LOCATION']?>" readonly></span><span class="drawer-span">Drawer<input type="text" class="drawer-input" value="<?=$tools[$job['TOOL_IN']]['DRAWER']?>" readonly></span><button onclick="popQuality(this.parentNode.parentNode.children[0].children[1].innerText)">Quality</button><br>
						<span class="comment-span">Comment</span><textarea rows="4" cols="57" class="comment-textarea"></textarea><br>
						<span class="program-span">Program #<input type="text" class="program-input"></span><span class="aperture-span">Aperture<input type="text" class="aperture-input" readonly></span><span class="unit">(in)</span>
						<div class="details-controls"><button onclick="popProgramList(this)" class="search-button">Search</button></div>
					</div>
				</div><br>
				<?php } ?>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="67" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else if ($_POST['process'] == "LOGO") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJobs()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div><br>
				<?php foreach($jobs as $job) { ?>
				<div class="details-container"><span onclick="showDetails(this)" style="cursor: pointer;"><div class="down-arrow"></div><span class="tool"><strong><?=$job['TOOL_IN']?></strong></span><span style="float: right;">WO#: <?=$job['WO_NUMBER']?></span></span>
					<div class="details">
						<span class="location-span">Location<input type="text" class="location-input" value="<?=$tools[$job['TOOL_IN']]['LOCATION']?>" readonly></span><span class="drawer-span">Drawer<input type="text" class="drawer-input" value="<?=$tools[$job['TOOL_IN']]['DRAWER']?>" readonly></span><button onclick="popQuality(this.parentNode.parentNode.children[0].children[1].innerText)">Quality</button><br>
						<span class="comment-span">Comment</span><textarea rows="4" cols="57" class="comment-textarea"></textarea><br>
					</div>
				</div><br>
				<?php } ?>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="66" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$jobs[0]['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$jobs[0]['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="machine-span">Machine<select id="machine-select" onchange="insertDescription()">
					<?php foreach($machines as $machine) { ?>
						<option value="<?=$machine['MACHINE']?>"><?=$machine['MACHINE']?></option>
					<?php } ?>
					</select><input type="text" id="machine-input" value="<?=$machines[0]['DESCRIPTION']?>" readonly></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJobs()">Save</button><br>
					<button onclick="goBack()">Back</button><br>
				</div>
				<?php foreach($jobs as $job) { ?>
				<div class="details-container"><span onclick="showDetails(this)" style="cursor: pointer;"><div class="down-arrow"></div><span class="tool"><strong><?=$job['TOOL_IN']?></strong></span><span style="float: right;">WO#: <?=$job['WO_NUMBER']?></span></span>
					<div class="details">
						<span class="location-span">Location<input type="text" class="location-input" value="<?=$tools[$job['TOOL_IN']]['LOCATION']?>" readonly></span><span class="drawer-span">Drawer<input type="text" class="drawer-input" value="<?=$tools[$job['TOOL_IN']]['DRAWER']?>" readonly></span><button onclick="popQuality(this.parentNode.parentNode.children[0].children[1].innerText)">Quality</button><br>
						<span class="comment-span">Comment</span><textarea rows="4" cols="57" class="comment-textarea"></textarea><br>
					</div>
				</div><br>
				<?php } ?>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
