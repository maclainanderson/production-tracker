<!DOCTYPE html>
<?php
/**
  *	@desc process tool out of toolroom
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//job data, values to choose from
	$job = array();
	$tool = array();
	$locations = array();
	$defects = array();
	$statuses = array();
	$toolTypes = array();
	$aperture = "";
	
	if ($conn) {
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
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tool = $row;
			}
		} else {
			//print_r(sqlsrv_errors());
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
		
		$result = sqlsrv_query($conn, "SELECT ID, TYPE FROM Customer_Tool_Types WHERE STATUS = 'Active' ORDER BY TYPE ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolTypes[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT Aperture FROM Program_Apertures WHERE PROGRAM = '" . $job['PROGRAM_NUMBER'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$aperture = $row['APERTURE'];
			}
		} else {
			print_r(sqlsrv_errors());
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
	var isMetric = false;
	var toolStatus = {};
	var job = {
		BATCH_NUMBER: <?= $job['BATCH_NUMBER'] ?>,
		WO_NUMBER: <?= $job['WO_NUMBER'] ?>,
		JOB_NUMBER: "<?= $job['JOB_NUMBER'] ?>",
		PO_NUMBER: "<?= $job['PO_NUMBER'] ?>",
		PROCESS: "<?= $job['PROCESS'] ?>",
		SEQNUM: "<?= $job['SEQNUM'] ?>",
		TARGET_DATE: "<?= date_format($job['TARGET_DATE'],'m/d/y') ?>",
		TOOL_IN: "<?= $job['TOOL_IN'] ?>".replace(/[+]/g, "%2B"),
		DATE_IN: "<?= date_format($job['DATE_IN'],'m/d/y') ?>",
		OPERATOR_IN: "<?= $job['OPERATOR_IN'] ?>",
		STATUS_IN: "<?= $job['STATUS_IN'] ?>",
		TOOL_OUT: "<?= $job['TOOL_OUT'] ?>".replace(/[+]/g, "%2B"),
		SPECIAL_INSTRUCTIONS: `<?= $job['SPECIAL_INSTRUCTIONS'] ?>`.replace(/[&]/g,"%26").replace(/\n/g,"%0A"),
		THICKNESS1: parseFloat("<?= $job['THICKNESS1'] ?>") || 0.00000,
		THICKNESS2: parseFloat("<?= $job['THICKNESS2'] ?>") || 0.00000,
		THICKNESS3: parseFloat("<?= $job['THICKNESS3'] ?>") || 0.00000,
		THICKNESS4: parseFloat("<?= $job['THICKNESS4'] ?>") || 0.00000,
		THICKNESS5: parseFloat("<?= $job['THICKNESS5'] ?>") || 0.00000,
		THICKNESS6: parseFloat("<?= $job['THICKNESS6'] ?>") || 0.00000,
		MACHINE_NUMBER: "<?= $job['MACHINE_NUMBER'] ?>",
		PROGRAM_NUMBER: "<?= $job['PROGRAM_NUMBER'] ?>",
		BOW: parseFloat("<?= $job['BOW'] ?>") || 0.00000,
		OPPOSITE: "<?= $job['OPPOSITE'] ?>",
		COMMENT: `<?= $job['COMMENT'] ?>`.replace(/[&]/g,"%26").replace(/\n/g,"%0A")
	};
	
	/**
	  *	@desc	insert date and operator name
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		document.getElementById("date-input").value = <?php if (!$isCurrent) { echo '"' . date_format($job['DATE_OUT'],'m/d/y') . '"'; } else { echo 'formatDate(new Date())'; } ?>;
		<?php if (!$isCurrent) { ?>
		document.getElementById("operator-input").value = '<?=$job['OPERATOR_OUT']?>';
		document.getElementById("opeartor-input").readOnly = false;
		<?php } else {
			if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?>
		document.getElementById("operator-input").value = "<?=$_SESSION['initials']?>";
			<?php } ?>
		document.getElementById("operator-input").readOnly = false;
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
	  *	@desc	switch between mm and in
	  *	@param	DOM Object button - to change label on
	  *	@return	none
	*/
	function switchUnit(button) {
		var units = document.getElementsByClassName('unit');
		var inputs = document.getElementsByClassName('thickness');
		
		if (isMetric) {
			button.innerHTML = "Metric";
			for (var i=0;i<units.length;i++) {
				units[i].innerHTML = "(in)";
			}
			isMetric = false;
		} else {
			button.innerHTML = "English";
			for (var i=0;i<units.length;i++) {
				units[i].innerHTML = "(mm)";
			}
			isMetric = true;
		}
		
		for (var i=0;i<inputs.length;i++) {
			inputs[i].value = convert(inputs[i].value);
		}
	}
	
	/**
	  *	@desc	convert input value to mm or in
	  *	@param	string value - value to convert
	  *	@return	string - converted data
	*/
	function convert(value) {
		if (value == "") {
			return "";
		} else {
			if (isMetric) {
				return (parseFloat(value) * 25.4).toFixed(3);
			} else {
				return (parseFloat(value) / 25.4).toFixed(3);
			}
		}
	}
	
	/**
	  *	@desc	create/display quality information
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
	  *	@desc	save tool quality to status object
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
			if ('TOOL' in toolStatus) {
				var conn = new XMLHttpRequest();
				var table = "Toolroom";
				var action = "update";
				var query = "";
				var d = new Date();
				
				job.DATE_OUT = formatDate(d);
				job.OPERATOR_OUT = document.getElementById("operator-input").value;
				job.STATUS_OUT = toolStatus.STATUS;
				job.CUSTOMER_TOOL_TYPE = document.getElementById("tool-type-select").value;
				job.COMMENT = document.getElementById("comment-textarea").value;
				
				if ("<?=$_POST['process']?>" != "FRAMING" && "<?=$_POST['process']?>" != "CONVERT" && "<?=$_POST['process']?>" != "LOGO") {
					job.THICKNESS1 = document.getElementById("thickness1-input").value;
					job.THICKNESS2 = document.getElementById("thickness2-input").value;
					job.THICKNESS3 = document.getElementById("thickness3-input").value;
					job.THICKNESS4 = document.getElementById("thickness4-input").value;
					job.THICKNESS5 = document.getElementById("thickness5-input").value;
					job.THICKNESS6 = document.getElementById("thickness6-input").value;
				}
				
				job.id = <?=$job['ID']?>;
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("success")) {
							saveTool();
						} else {
							alert("Job not completed. Contact IT Support to correct. " + conn.responseText);
						}
					}
				}
				
				Object.keys(job).forEach((item, index, array) => {
					if (item != "id") {
						query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[<]/g,"%3C").replace(/[>]/g,"%3E")}`;
					} else {
						query += `&condition=id&value=${job[item]}`;
					}
				})
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn.send();
			} else {
				alert("Complete quality first");
			}
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	save tool data
	  *	@param	none
	  *	@return	none
	*/
	function saveTool() {
		var tool = {
			LOCATION: document.getElementById("location-input").value,
			DRAWER: document.getElementById("drawer-input").value,
			STATUS: toolStatus.STATUS,
			REASON: toolStatus.REASON,
			STATUS_DATE: formatDate(new Date()),
			OPERATOR: toolStatus.OPERATOR,
		}
		
		if ("<?=$_POST['process']?>" != "FRAMING" && "<?=$_POST['process']?>" != "CONVERT" && "<?=$_POST['process']?>" != "LOGO") {
			tool.THICKNESS1 = document.getElementById("thickness1-input").value;
			tool.THICKNESS2 = document.getElementById("thickness2-input").value;
			tool.THICKNESS3 = document.getElementById("thickness3-input").value;
			tool.THICKNESS4 = document.getElementById("thickness4-input").value;
			tool.THICKNESS5 = document.getElementById("thickness5-input").value;
			tool.THICKNESS6 = document.getElementById("thickness6-input").value;
		}
		
		tool.id = <?=$tool['ID']?>;
		
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
					alert("Tool not saved. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query,true);
		conn.send();
	}
	
	/**
	  *	@desc	save tool's comment
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
	  *	@desc	save tool status
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
					if (["PURGED","RETIRED","NOGOOD"].includes(toolStatus.STATUS)) {
						abortNextJobs(toolStatus.TOOL);
					} else {
						addNextJob();
					}
				} else {
					alert("Tool status not saved. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	moves next jobs to abort history
	  *	@param	string tool - tool name to move
	  *	@return	none
	*/
	function abortNextJobs(tool) {
		var jobs = getJobsToAbort(tool);
		var conn = new XMLHttpRequest();
		var action = "insert";
		var table = "Abort_History";
		var successes = 0;
		var attempts = 0;
		
		for (var i=0;i<jobs.length;i++) {
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					attempts++;
					if (this.responseText.includes("Insert succeeded")) {
						successes++;
						if (attempts >= jobs.length) {
							if (successes >= jobs.length) {
								removeNextJobs(tool);
							} else {
								alert("Could not abort all jobs for this tool. Contact IT Support to correct.");
							}
						}
					}
				}
			}
			
			var query = '';
			
			Object.keys(jobs[i]).forEach((item, index, array) => {
				query += `&${item}=${jobs[i][item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			});
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query,false);
			conn.send();
		}
	}
	
	/**
	  *	@desc	gets currently scheduled jobs for tool
	  *	@param	string tool - tool name to search
	  *	@return array jobs - job data to abort
	*/
	function getJobsToAbort(tool) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering","Mastering_Queue","Toolroom","Toolroom_Queue",
					  "Electroforming","Electroforming_Queue","Shipping","Shipping_Queue"];
		var jobs = [];
		
		for (var i=0;i<tables.length;i++) {
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
					
					for (var j=0;j<result.length;j++) {
						if (tables[i].includes("Electroforming")) {
							if (!result[j]['OPERATOR_IN']) {
								jobs.push({
									BATCH_NUMBER: result[j]['BATCH_NUMBER'],
									TOOL: result[j]['TOOL_IN'],
									PROCESS: result[j]['PROCESS'],
									DEPARTMENT: "ELECTROFORMING",
									WO_NUMBER: result[j]['WO_NUMBER'],
									DATE: formatDate(new Date()),
									REASON: "BAD TOOL",
									OPERATOR: "<?=$_SESSION['initials']?>"
								});
							}
						} else if (tables[i].includes("Toolroom")) {
							if (!result[j]['OPERATOR_IN']) {
								jobs.push({
									BATCH_NUMBER: result[j]['BATCH_NUMBER'],
									TOOL: result[j]['TOOL_IN'],
									PROCESS: result[j]['PROCESS'],
									DEPARTMENT: "TOOLROOM",
									WO_NUMBER: result[j]['WO_NUMBER'],
									DATE: formatDate(new Date()),
									REASON: "BAD TOOL",
									OPERATOR: "<?=$_SESSION['initials']?>"
								});
							}
						} else if (tables[i].includes("Shipping")) {
							if (!result[j]['SELECT_OPERATOR']) {
								jobs.push({
									BATCH_NUMBER: result[j]['BATCH_NUMBER'],
									TOOL: result[j]['TOOL'],
									PROCESS: "SHIPPING",
									DEPARTMENT: "SHIPPING",
									WO_NUMBER: result[j]['WO_NUMBER'],
									DATE: formatDate(new Date()),
									REASON: "BAD TOOL",
									OPERATOR: "<?=$_SESSION['initials']?>"
								});
							}
						} else {
							if (!result[j]['OPERATOR_IN']) {
								jobs.push({
									BATCH_NUMBER: result[j]['BATCH_NUMBER'],
									TOOL: result[j]['TOOL_IN'],
									PROCESS: "MASTERING",
									DEPARTMENT: "MASTERING",
									WO_NUMBER: result[j]['WO_NUMBER'],
									DATE: formatDate(new Date()),
									REASON: "BAD TOOL",
									OPERATOR: "<?=$_SESSION['initials']?>"
								});
							}
						}
					}
					
				}
			}
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&condition="+(tables[i].includes("Ship") ? 'TOOL' : 'TOOL_IN')+"&value="+tool.replace(/[+]/g,"%2B"),false);
			conn.send();
		}
		
		return jobs;
	}
	
	/**
	  *	@desc	removes jobs for this tool
	  *	@param	string tool - tool name to delete
	  *	@return	none
	*/
	function removeNextJobs(tool) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var tables = ["Mastering","Mastering_Queue","Toolroom","Toolroom_Queue",
					  "Electroforming","Electroforming_Queue","Shipping","Shipping_Queue"];
		var successes = 0;
		var attempts = 0;
		
		for (var i=0;i<tables.length;i++) {
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					attempts++;
					if (this.responseText.includes("Deletion succeeded")) {
						successes++;
						if (attempts >= tables.length) {
							if (successes >= tables.length) {
								alert("Job completed");
								document.getElementsByTagName("body")[0].innerHTML += `<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
								document.getElementById("return-form").submit();
							} else {
								alert("Could not remove jobs with this tool. Contact IT Support to correct.");
							}
						}
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+(tables[i].includes("Ship") ? "&TOOL=" : "&TOOL_IN=")+tool.replace(/[+]/g,"%2B")+(tables[i].includes("Ship") ? "&SELECT_OPERATOR" : "&OPERATOR_IN" )+"=null",false);
			conn.send();
		}
	}
	
	/**
	  *	@desc	move next job in batch to current work
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
				if (item != 'ID') {
					query += `&${item}=${next[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				}
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
			alert("Job completed");
			document.getElementsByTagName("body")[0].innerHTML += `<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
			document.getElementById("return-form").submit();
		}
	}
	
	/**
	  *	@desc	get next job in batch
	  *	@param	none
	  *	@return	array containing job data
	*/
	function findNextJob() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var condition = "BATCH_NUMBER";
		var value = job.BATCH_NUMBER;
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
			if (item['SEQNUM'] < nextSeqNum && (item['TOOL_IN'] ? item['TOOL_IN'] : item['TOOL']) == job.TOOL_IN) {
				nextSeqNum = item['SEQNUM'];
				nextJob = index;
			}
		});
		
		return jobs[nextJob];
	}
	
	/**
	  *	@desc	remove next job from queue
	  *	@param	int woNumber - WO# of next job, string table - table next job is located in
	  *	@return	none
	*/
	function removeNextFromQueue(woNumber, table) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = table + "_Queue";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Job completed");
					document.getElementsByTagName("body")[0].innerHTML += '<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>';
					document.getElementById("return-form").submit();
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WO_NUMBER="+woNumber,false);
		conn.send();
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
		<title>Tool Out</title>
		<?php if($_POST['process'] == "FRAMING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/framingout.css">
		<?php } else if ($_POST['process'] == "LOGO") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/logoout.css">
		<?php //} else if ($_POST['process'] == "CONVERT") { ?>
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/backmachineout.css">
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
					<span id="comment-span">Comment</span><textarea rows="4" cols="57" id="comment-textarea"><?=$job['COMMENT']?></textarea>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="details">
					<span id="framed-tool-span">Framed Tool<input type="text" id="framed-tool-input" value="<?=$job['TOOL_OUT']?>" readonly></span>
					<span id="tool-type-span">Tool Type<select id="tool-type-select">
					<?php foreach($toolTypes as $toolType) { ?>
						<option value="<?=$toolType['TYPE']?>"><?=$toolType['TYPE']?></option>
					<?php } ?>
					</select></span>
				</div><br>
				<div class="deburr">
					<span id="deburr-label">Deburr</span><br>
					<span id="deburr-operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="deburr-operator-input"></span><br>
					<span id="deburr-date-span">Date<input type="text" id="deburr-date-input"></span>
				</div>
				<div class="program">
					<span id="program-span">Program #<input type="text" id="program-input" value="<?=$job['PROGRAM_NUMBER']?>" readonly></span><br>
					<span id="filename-span">File Name<input type="text" id="filename-input" readonly></span><br>
					<span id="aperture-span">Aperture<input type="text" id="aperture-input" value="<?=$aperture?>" readonly></span>
				</div>
				<?php //} else if ($_POST['process'] == "CONVERT") { ?>
					
				<?php } else if ($_POST['process'] == "LOGO") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>" readonly></span><br>
					<span id="tool-type-span">Tool Type<select id="tool-type-select">
					<?php foreach($toolTypes as $toolType) { ?>
						<option value="<?=$toolType['TYPE']?>"><?=$toolType['TYPE']?></option>
					<?php } ?>
					</select></span><br>
					<span id="location-span">Location<input id="location-input" value="<?=$tool['LOCATION']?>" readonly></span><span id="drawer-span">Drawer<input type="text" id="drawer-input" value="<?=$tool['DRAWER']?>" readonly></span><button onclick="popQuality()">Quality</button><br>
					<span id="comment-span">Comment</span><textarea rows="4" cols="57" id="comment-textarea"><?=$job['COMMENT']?></textarea>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="66" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_OUT']?>" readonly></span><br>
					<span id="tool-type-span">Tool Type<select id="tool-type-select">
					<?php foreach($toolTypes as $toolType) { ?>
						<option value="<?=$toolType['TYPE']?>"><?=$toolType['TYPE']?></option>
					<?php } ?>
					</select></span><br>
					<span id="location-span">Location<input id="location-input" value="<?=$tool['LOCATION']?>" readonly></span><span id="drawer-span">Drawer<input type="text" id="drawer-input" value="<?=$tool['DRAWER']?>" readonly></span><button onclick="popQuality()">Quality</button><br>
					<span id="comment-span">Comment</span><textarea rows="4" cols="57" id="comment-textarea"><?=$job['COMMENT']?></textarea>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="details">
					<span id="machine-span">Machine #<input type="text" id="machine-input" value="<?=$job['MACHINE_NUMBER']?>" readonly></span>
				</div><br>
				<button onclick="switchUnit(this)" id="unit-button">Metric</button>
				<span id="thickness-label">Thickness</span><br><br>
				<span id="thickness1-span"><input type="text" class="thickness" id="thickness1-input" value="<?=$job['THICKNESS1']?>"></span>
				<span id="thickness2-span"><input type="text" class="thickness" id="thickness2-input" value="<?=$job['THICKNESS2']?>"></span>
				<span id="thickness3-span"><input type="text" class="thickness" id="thickness3-input" value="<?=$job['THICKNESS3']?>"></span>
				<span id="thickness4-span"><input type="text" class="thickness" id="thickness4-input" value="<?=$job['THICKNESS4']?>"></span>
				<span id="thickness5-span"><input type="text" class="thickness" id="thickness5-input" value="<?=$job['THICKNESS5']?>"></span>
				<span id="thickness6-span"><input type="text" class="thickness" id="thickness6-input" value="<?=$job['THICKNESS6']?>"></span>
				<span class="unit">(in)</span>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
