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
	$tool = array();
	$programs = array();
	$machines = array();
	$processes = array();
	$locations = array();
	$defects = array();
	$statuses = array();
	
	//if parquet, there are multiple jobs
	if ($_POST['process'] == "PARQUET") {
		$jobs = array();
	} else {
		$job = array();
	}
	
	if ($conn) {
		if ($_POST['process'] == "PARQUET") {
			$batchNumber = 0;
			$result = sqlsrv_query($conn, "SELECT BATCH_NUMBER FROM Toolroom WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$batchNumber = $row['BATCH_NUMBER'];
				}
			} else {
				print_r(sqlsrv_errors());
			}
			
			$result = sqlsrv_query($conn, "SELECT * FROM Toolroom WHERE BATCH_NUMBER = " . $batchNumber . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$jobs[] = $row;
					if ($row['OPERATOR_OUT'] == '') {
						$isCurrent = true;
					} else {
						$isCurrent = false;
					}
				}
			} else {
				print_r(sqlsrv_errors());
			}
			
			if (empty($jobs)) {
				$result = sqlsrv_query($conn, "SELECT * FROM Toolroom_History WHERE BATCH_NUMBER = " . $batchNumber . ";");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$jobs[] = $row;
						$isCurrent = false;
					}
				} else {
					print_r(sqlsrv_errors());
				}
			}
		} else {
			$result = sqlsrv_query($conn, "SELECT * FROM Toolroom WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					if ($row['OPERATOR_OUT'] == '') {
						$isCurrent = true;
					} else {
						$isCurrent = false;
					}
				}
			} else {
				print_r(sqlsrv_errors());
			}
			
			if (empty($job)) {
				$result = sqlsrv_query($conn, "SELECT * FROM Toolroom_History WHERE ID = " . $_POST['id'] . ";");
				if ($result) {
					while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
						$job = $row;
						$isCurrent = false;
					}
				} else {
					print_r(sqlsrv_errors());
				}
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
		
		if ($_POST['process'] != "PARQUET") {
			$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$tool = $row;
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
	
	if ($_POST['process'] == "PARQUET") {
		$mandrels = array();
		foreach($jobs as $job) {
			$toolIn = explode("-", $job['TOOL_IN']);
			$mandrels[count($mandrels)] = "";
			for ($i=0;$i<count($toolIn);$i++) {
				if ($i == count($toolIn)-2) {
					$mandrels[count($mandrels)-1] .= $toolIn[$i];
				} else if ($i < count($toolIn)-2) {
					$mandrels[count($mandrels)-1] .= $toolIn[$i] . "-";
				} else {
					continue;
				}
			}
		}
		
		$mandrels = array_unique($mandrels);
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
	var toolStatus = {};
	var isMetric = false;
	var aperture = "";
	var bow = "";
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
	
	<?php if ($_POST['process'] == "PARQUET") { ?>
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
	
	var mandrels = [<?php
		foreach($mandrels as $mandrel) {
			echo '"';
			echo $mandrel;
			echo '"';
			echo ',';
		}
	?>];
	<?php } ?>
	
	/**
	  *	@desc	insert date and operator name
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		document.getElementById("date-input").value = <?php if (!$isCurrent) { echo '"' . date_format($job['DATE_IN'],'m/d/y') . '"'; } else { echo 'formatDate(new Date())'; } ?>;
		<?php if($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?>
		document.getElementById("operator-input").value = "<?= !$isCurrent ? $job['OPERATOR_IN'] : $_SESSION['initials']?>";
		<?php } ?>
		<?php if($_POST['process'] == "CONVERT") { ?>
		var parts = "<?=$job['TOOL_IN']?>".split("(");
		var toolOut = parts[0];
		for (var i=1;i<parts.length;i++) {
			toolOut += "(";
			var part = parts[i].split(")");
			var parquets = part[0].split("+");
			for (var j=0;j<parquets.length;j++) {
				parquets[j] = "<input id=\"" + i + j + "\" type=\"text\" onclick=\"selectParquet(this)\" value=\"" + parquets[j] + "\" readonly>";
			}
			toolOut += parquets.join("+") + ")" + part[1];
		}
		document.getElementById("tool-out-span").innerHTML = toolOut;
		<?php } ?>
	}
	
	/**
	  *	@desc	insert X to parquet
	  *	@param	DOM Object input - parquet selected
	  *	@return	none
	*/
	function selectParquet(input) {
		if (!input.value.includes("X")) {
			input.value += "X";
		} else {
			input.value = input.value.slice(0,-1);
		}
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
	  *	@param	none
	  *	@return	none
	*/
	function popProgramList() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var searchText = document.getElementById("program-input").value;
		var html = `<span class="close" id="close">&times;</span><table id="program-table"><thead><tr><th class="col1">Program</th><th class="col2">Aperture</th></thead><tbody>`;
		
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
		document.getElementById("program-input").value = tr.children[0].innerHTML;
		aperture = tr.children[1].innerHTML;
		if (isMetric) {
			document.getElementById("aperture-input").value = aperture;
		} else {
			document.getElementById("aperture-input").value = convert(aperture);
		}
		selectedProgramRow = tr.id;
		document.getElementById("close").click();
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
		setTimeout(popMandrelList, 200);
	}
	
	/**
	  *	@desc	create/display list of mandrels
	  *	@param	none
	  *	@return	none
	*/
	function popMandrelList() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span><table id="mandrel-table"><thead><tr><th class="col1">Mandrel</th></thead><tbody>`;
		mandrels.forEach((item, index, array) => {
			html += `<tr onclick="selectMandrelRow(this)"><td class="col1">${item}</td></tr>`;
		});
		
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		modal.style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight mandrel row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectMandrelRow(tr) {
		var trs = tr.parentNode.children;
		
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectMandrelRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmMandrelRow(this)');
	}
	
	/**
	  *	@desc	insert mandrel to job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmMandrelRow(tr) {
		document.getElementById("mandrel-input").value = tr.children[0].innerHTML;
		var tbody = document.getElementById("tool-table").children[1];
		jobs.forEach((item, index, array) => {
			if (item['TOOL_IN'].length - document.getElementById('mandrel-input').value.length < 3 && item['TOOL_IN'].includes(document.getElementById('mandrel-input').value)) {
				tbody.innerHTML += `<tr>
										<td class="col1">item['TOOL_IN']</td>
										<td class="col2"><button class="sequence" onclick="moveUp(this)"><div class="upButton"></div></button><button class="sequence" onclick="moveDown(this)"><div class="downButton"></div></button></td>
										<td class="col3"><input type="checkbox"></td>
									</tr>`;
			}
		});
																			
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
		var value = "<?=$_POST['process']?>" == "FRAMING" ? document.getElementById("aperture-input").value : "";
		if (isMetric) {
			button.innerHTML = "Metric";
			for (var i=0;i<units.length;i++) {
				units[i].innerHTML = "(in)";
			}
			isMetric = false;
			if ("<?=$_POST['process']?>" == "FRAMING") {
				if (document.getElementById("aperture-input").value != "") {
					document.getElementById("aperture-input").value = convert(value);
				}
			}
		} else {
			button.innerHTML = "English";
			for (var i=0;i<units.length;i++) {
				units[i].innerHTML = "(mm)";
			}
			isMetric = true;
			if ("<?=$_POST['process']?>" == "FRAMING") {
				if (document.getElementById("aperture-input").value != "") {
					document.getElementById("aperture-input").value = aperture;
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
	  *	@param	none
	  *	@return	none
	*/
	function popQuality() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		
		var html = `<span class="close" id="close">&times;</span><div style="display: inline-block;">
					<span style="margin-left: 3px;">New Tool<input type="text" id="tool-quality-input" value="${document.getElementById("tool-input").value}" style="width: 266px;"></span><br>
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
					<button onclick="saveQuality()" style="display: inline-block; vertical-align: top; width: 60px; margin-top: 8px; margin-right: 5px; margin-left: 5px; background-color: black; color: white;">Save</button><br>
					<span>Apply to Process<input type="text" id="process-quality-input" value="<?=$_POST['process']?>" readonly style="width: 100px;"></span>
					<span>Apply to WO #<input type="text" id="wo-quality-input" value="${document.getElementById("wo-input").value}" readonly style="width: 100px;"></span>`;
					
		modalContent.innerHTML = html;
			
		document.getElementById("location-quality-select").value = document.getElementById("location-input").value;
		document.getElementById("drawer-quality-input").value = document.getElementById("drawer-input").value;
			
		closeForm();
	}
	
	/**
	  *	@desc	save quality information to status object
	  *	@param	none
	  *	@return	none
	*/
	function saveQuality() {
		toolStatus.TOOL = document.getElementById("tool-quality-input").value.replace(/[+]/g, "%2B");
		toolStatus.STATUS = document.getElementById("status-quality-select").value;
		toolStatus.REASON = document.getElementById("defect-quality-select").value;
		toolStatus.PROCESS = document.getElementById("process-quality-input").value;
		toolStatus.WO_NUMBER = document.getElementById("wo-quality-input").value;
		toolStatus.OPERATOR = document.getElementById("operator-quality-input").value;
		
		document.getElementById("location-input").value = document.getElementById("location-quality-select").value;
		document.getElementById("drawer-input").value = document.getElementById("drawer-quality-input").value;
		
		document.getElementById("close").click();
	}
	
	<?php if ($_POST['process'] == "PARQUET") { ?>
	/**
	  *	@desc	move tool up in parquet list
	  *	@param	DOM Object button - specific up button clicked
	  *	@return	none
	*/
	function moveUp(button) {
		var tr = button.parentNode.parentNode;
		if (tr.previousElementSibling != null) {
			tr.after(tr.previousElementSibling);
		}
	}
	
	/**
	  *	@desc	move tool down in parquet list
	  *	@param	DOM Object button - specific down button clicked
	  *	@return	none
	*/
	function moveDown(button) {
		var tr = button.parentNode.parentNode;
		if (typeof tr.nextElementSibling != 'undefined') {
			tr.nextElementSibling.after(tr);
		}
	}
	<?php } ?>
	
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
			<?php if ($_POST['process'] != "PARQUET") { ?>
			<?php if ($_POST['process'] == "CONVERT") { ?>
			var toolOut = "";
			var inputs = document.getElementById("tool-out-span").children;
			var str = document.getElementById("tool-out-span").innerText.split("+");
			var offset = 0;
			for (var i=0;i<str.length;i++) {
				if (str[i][0] == ")") {
					str[i] = inputs[i+offset].value + str[i];
					offset++;
					if (str[i][str[i].length-1] == "(") {
						str[i] += inputs[i+offset].value;
			        }
			    } else if (str[i][str[i].length-1] == "(" || str[i].length == 0) {
					str[i] += inputs[i+offset].value;
			    } else {
			    }
			}
			toolOut = str.join("+");
			if (document.getElementById("tool-input").value == toolOut) {
				alert("Please select a parquet");
				return;
			}
			<?php } ?>
			if ('TOOL' in toolStatus || "<?=$_POST['process']?>" == "CONVERT") {
				var conn = new XMLHttpRequest();
				var table = "Toolroom";
				var action = "update";
				var query = "";
				var d = new Date();
				var job = {
					DATE_IN: formatDate(d),
					OPERATOR_IN: document.getElementById("operator-input").value,
					TOOL_OUT: document.getElementById("tool-input").value,
					DATE_OUT: formatDate(new Date(d.setDate(d.getDate() + <?= $processes[$_POST['process']] ?>))),
				};
				
				<?php if ($_POST['process'] == "CONVERT") { ?>
				job.TOOL_OUT = toolOut;
				<?php } ?>
				
				if ("<?=$_POST['process']?>" == "FRAMING") {
					job.PROGRAM_NUMBER = document.getElementById("program-input").value;
				}
				
				if ("<?=$_POST['process']?>" != "CONVERT") {
					job.COMMENT = document.getElementById("comment-textarea").value;
					job.STATUS_IN = toolStatus.STATUS;
				}
				
				if ("<?=$_POST['process']?>" != "FRAMING" && "<?=$_POST['process']?>" != "CONVERT" && "<?=$_POST['process']?>" != "LOGO") {
					job.MACHINE_NUMBER = document.getElementById("machine-select").value;
				}
				
				job.id = <?=$job['ID']?>;
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("success")) {
							if ("<?=$_POST['process']?>" != "CONVERT") {
								saveTool();
							} else {
								alert("Job updated");
								document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
								document.getElementById("return-form").submit();
							}
						} else {
							alert("Job not saved. Contact IT Support to correct. " + conn.responseText);
						}
					}
				}
				
				Object.keys(job).forEach((item, index, array) => {
					if (item != 'id') {
						query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					} else {
						query += `&condition=id&value=${job[item]}`;
					}
				})
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn.send();
			} else {
				alert("Complete quality first");
			}
			<?php } else { ?>
			successCounter = 0;
			conn = [];
			var table = "Toolroom";
			var action = "update";
			var query = "";
			var trs = document.getElementById('tool-table').children[1].children;
			
			for(var i=0;i<trs.length;i++) {
				if (trs[i].children[2].children[0].checked == false) {
					var d = new Date();
					job = {
						DATE_IN: formatDate(d),
						OPERATOR_IN: document.getElementById("operator-input").value,
						DATE_OUT: formatDate(new Date(d.setDate(d.getDate() + <?=$processes[$_POST['process']]?>))),
					};
					
					var toolIn = trs[i].children[0].innerHTML.split("-");
					var toolOut = "";
					for(var j=0;j<toolIn.length-1;j++) {
						toolOut += toolIn[j] + "-";
					}
					toolOut += "(";
					for(var j=0;j<trs.length;j++) {
						if (trs[j].children[2].children[0].checked == false) {
							if (j==trs.length-1) {
								toolOut += trs[j].children[0].innerHTML.split("-").pop();
							} else {
								toolOut += trs[j].children[0].innerHTML.split("-").pop() + "+";
							}
						}
					}
					toolOut += ")";
					job.TOOL_OUT = toolOut;
					job.TOOL_IN = trs[i].children[0].innerHTML;
					
					Object.keys(job).forEach((item, index, array) => {
						if (item != "TOOL_IN") {
							query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
						} else {
							query += `&condition=TOOL_IN&value=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
						}
					})
					
					conn[i] = new XMLHttpRequest();
					
					conn[i].onreadystatechange = xmlResponse(i);
					
					conn[i].open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
					conn[i].send();
				} else {
					if (abortJob(trs[i])) {
						successCounter++;
						checkSuccess(successCounter);
					} else {
						alert("Return tool not successfully aborted. Contact support to correct. ");
					}
				}
			}
			
			function xmlResponse(i) {
				return function() {
					if (conn[i].readyState == 4 && conn[i].status == 200) {
						if (conn[i].responseText.includes("Data updated")) {
							successCounter++;
							checkSuccess(successCounter);
						} else {
							alert("Batch created, but job entry failed. Contact support to correct. " + conn.responseText);
						}
					}
				}
			}
			<?php } ?>
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	determine if all jobs succeeded
	  *	@param	int counter - jobs attempted so far
	  *	@return	none
	*/
	function checkSuccess(counter) {
		if (counter == document.getElementById('tool-table').children[1].children.length) {
			alert("Job updated");
			document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
			document.getElementById("return-form").submit();
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	move job to abort history
	  *	@param	DOM Object tr - selected row containing job id and tool name
	  *	@return	none
	*/
	function abortJob(tr) {
		var conn = new XMLHttpRequest();
		var table = "Abort_History";
		var action = "insert";
		var query = "";
		
		var abortJob = {
			BATCH_NUMBER: "<?=$jobs[0]['BATCH_NUMBER']?>",
			TOOL: tr.children[0].innerHTML,
			PROCESS: "PARQUET",
			DEPARTMENT: "TOOLRM",
			REASON: "Returned to inventory",
			WO_NUMBER: tr.id,
			OPERATOR: document.getElementById('operator-input').value,
			DATE: formatDate(new Date())
		}
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					if (deleteJob(tr.id)) {
						return true;
					}
				} else {
					alert("Row not added to abort history");
				}
			}
		}
		
		Object.keys(abortJob).forEach((item, index, array) => {
			query += `&${item}=${abortJob[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
	
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	remove job from current work
	  *	@param	int id - WO# to delete
	  *	@return	none
	*/
	function deleteJob(id) {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "delete";
		var query = "&WO_NUMBER="+id;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					return true;
				} else {
					alert("Returned job not removed from Toolroom. Contact support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
		conn.send();
	}
	
	<?php if ($_POST['process'] != "PARQUET") { ?>
	/**
	  *	@desc	update TOOL_OUT with new data
	  *	@param	none
	  *	@return	none
	*/
	function saveTool() {
		var tool = {
			LOCATION: document.getElementById("location-input").value,
			DRAWER: document.getElementById("drawer-input").value,
			STATUS: toolStatus.STATUS,
			REASON: toolStatus.REASON,
			DATE_MODIFIED: formatDate(new Date()),
			OPERATOR: toolStatus.OPERATOR,
			id: <?=$tool['ID']?>
		}
		
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "update";
		var query = "";
		
		Object.keys(tool).forEach((item, index, array) => {
			if (item != "id") {
				query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			} else {
				query += `&condition=id&value=${tool[item]}`;
			}
		})
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("success")) {
					if (document.getElementById("comment-textarea").value.length > 0) {
						saveComment();
					} else {
						saveStatus();
					}
				} else {
					alert("Tool data not saved. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query,true);
		conn.send();
	}
	
	/**
	  *	@desc	save new comment data
	  *	@param	none
	  *	@return	none
	*/
	function saveComment() {
		var comment = {
			COMMENT: document.getElementById("comment-textarea").value,
			PROCESS: "<?=$_POST['process']?>",
			TOOL: document.getElementById("tool-input").value,
			DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value
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
				if (conn.responseText.includes("Insert succeeded.")) {
					saveStatus();
				} else {
					alert("Comment not saved. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	save tool status update
	  *	@param	none
	  *	@return	none
	*/
	function saveStatus() {
		toolStatus.DATE = formatDate(new Date());
	
		var conn = new XMLHttpRequest();
		var table = "Tool_Status_History";
		var action = "insert";
		var query = "";
		
		Object.keys(toolStatus).forEach((item, index, array) => {
			query += `&${item}=${toolStatus[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		})
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded.")) {
					alert("Job updated");
					document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
					document.getElementById("return-form").submit();
				} else {
					alert("Tool status not saved. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	<?php } ?>
	
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
	
	function goBack() {
		<?php if ($_POST['source'] == "retrieve.php") { ?>
		document.getElementsByTagName("body")[0].innerHTML += `<form action="/view/retrieve.php" method="POST" id="return-form" style="display: none;"><input type="text" value="/view/operations/toolroom.php" name="returnpath"><input type="text" value="${'<?=$_POST['tool']?>'}" name="tool"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>`;
		<?php } else { ?>
		document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
		<?php } ?>
		document.getElementById("return-form").submit();
	}
</script>
<html>
	<head>
		<title>Tool In</title>
		<?php if ($_POST['process'] == "FRAMING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/framingin.css">
		<?php } else if ($_POST['process'] == "CONVERT") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/convertin.css">
		<?php } else if ($_POST['process'] == "LOGO") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/logoin.css">
		<?php } else if ($_POST['process'] == "PARQUET") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/parquetin.css">
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/backmachinein.css">
		<?php } ?>
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<?php if ($_POST['process'] == "FRAMING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>" readonly></span><br>
					<span id="location-span">Location<input id="location-input" value="<?=$tool['LOCATION']?>" readonly></span><span id="drawer-span">Drawer<input type="text" id="drawer-input" value="<?=$tool['DRAWER']?>" readonly></span><button onclick="popQuality()">Quality</button><br>
					<span id="comment-span">Comment</span><textarea rows="4" cols="57" id="comment-textarea"></textarea>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="details">
					<span id="program-span">Program #<input type="text" id="program-input"></span><span id="aperture-span">Aperture<input type="text" id="aperture-input" readonly></span><span class="unit">(in)</span>
					<div class="details-controls"><button onclick="popProgramList()" id="search-button">Search</button><button onclick="switchUnit(this)">Metric</button></div>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="67" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else if ($_POST['process'] == "CONVERT") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>"></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>"></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>"></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="tool-span">Parquet<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>"></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<span id="tool-out-label">New Tool:</span><span id="tool-out-span"></span>
				<?php } else if ($_POST['process'] == "LOGO") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>" readonly></span><br>
					<span id="location-span">Location<input id="location-input" value="<?=$tool['LOCATION']?>" readonly></span><span id="drawer-span">Drawer<input type="text" id="drawer-input" value="<?=$tool['DRAWER']?>" readonly></span><button onclick="popQuality()">Quality</button><br>
					<span id="comment-span">Comment</span><textarea rows="4" cols="57" id="comment-textarea"></textarea>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="66" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else if ($_POST['process'] == "PARQUET") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$jobs[0]['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$jobs[0]['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$jobs[0]['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="mandrel-span">Mandrel<input type="text" id="mandrel-input" value="<?=$mandrels[0]?>"><button onclick="wait()">Search</button></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<table id="tool-table">
					<thead>
						<tr>
							<th class="col1">Tool</th>
							<th class="col2">Sequence</th>
							<th class="col3">Return</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach($jobs as $job) { ?>
						<tr id="<?=$job['WO_NUMBER']?>">
							<td class="col1"><?=$job['TOOL_IN']?></td>
							<td class="col2"><button class="sequence" onclick="moveUp(this)"><div class="upButton"></div></button><button class="sequence" onclick="moveDown(this)"><div class="downButton"></div></button></td>
							<td class="col3"><input type="checkbox"></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>" readonly></span><br>
					<span id="location-span">Location<input type="text" id="location-input" value="<?=$tool['LOCATION']?>" readonly></span><span id="drawer-span">Drawer<input type="text" id="drawer-input" value="<?=$tool['DRAWER']?>" readonly></span><button onclick="popQuality()">Quality</button><br>
					<span id="comment-span">Comment</span><textarea rows="4" cols="57" id="comment-textarea"></textarea>
				</div>
				<div class="controls">
					<button onclick="saveJob()"<?php if (!$isCurrent) { echo ' disabled'; } ?>>Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="details">
					<span id="machine-span">Machine<select id="machine-select" onchange="insertDescription()">
					<?php foreach($machines as $machine) { ?>
						<option value="<?=$machine['MACHINE']?>"><?=$machine['MACHINE']?></option>
					<?php } ?>
					</select><input type="text" id="machine-input" value="<?=$machines[0]['DESCRIPTION']?>" readonly></span><br>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
