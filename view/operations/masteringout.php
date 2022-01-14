<!DOCTYPE html>
<?php
/**
  *	@desc process mastering job out
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//job data, design data, lists to select values from
	$job = array();
	$design = array();
	$locations = array();
	$statuses = array();
	$defects = array();
	$types = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
				if ($job['OPERATOR_OUT'] == '') {
					$isCurrent = true;
				} else {
					$isCurrent = false;
				}
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Mastering_History WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isCurrent = false;
				}
			} else {
				print_r(sqlsrv_errors());
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DATE, DRAWING, FILENAME FROM Designs WHERE DESIGN = '" . $job['TOOL_IN'] . "';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$design = $row;
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
		
		$result = sqlsrv_query($conn, "SELECT ID, TYPE FROM Customer_Tool_Types WHERE STATUS = 'Active' ORDER BY TYPE ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$types[] = $row;
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
	
	//setup tracking data
	var job = {
		BATCH_NUMBER: <?= $job['BATCH_NUMBER'] ?>,
		WO_NUMBER: <?= $job['WO_NUMBER'] ?>,
		JOB_NUMBER: "<?= $job['JOB_NUMBER'] ?>",
		PO_NUMBER: "<?= $job['PO_NUMBER'] ?>",
		SEQNUM: <?= $job['SEQNUM'] ?>,
		TARGET_DATE: "<?= date_format($job['TARGET_DATE'],'m/d/y') ?>",
		TOOL_IN: "<?= $job['TOOL_IN'] ?>".replace(/[+]/g, "%2B"),
		DATE_IN: "<?= date_format($job['DATE_IN'],'m/d/y') ?>",
		OPERATOR_IN: "<?= $job['OPERATOR_IN'] ?>",
		STATUS_IN: "<?= $job['STATUS_IN'] ?>",
		DATE_OUT: "<?= date_format($job['DATE_OUT'],'m/d/y') ?>",
		SPECIAL_INSTRUCTIONS: "<?= $job['SPECIAL_INSTRUCTIONS'] ?>".replace(/[&]/g,"%26").replace(/\n/g,"%0A"),
		MACHINE_NUMBER: "<?= $job['MACHINE_NUMBER'] ?>",
		PROGRAM_NUMBER: "<?= $job['PROGRAM_NUMBER'] ?>",
		SIZE: <?= $job['SIZE'] ?>,
		TOOL_TYPE: "<?= $job['TOOL_TYPE'] ?>",
		COSMETIC: "<?= $job['COSMETIC'] ?>",
		WORK_TYPE: "<?= $job['WORK_TYPE'] ?>",
		COMMENT: "<?= $job['COMMENT'] ?>".replace(/[&]/g,"%26").replace(/\n/g,"%0A"),
		IS_BLANK: "<?= $job['IS_BLANK'] ?>",
		BLANK: "<?= $job['BLANK'] ?>"
	};
	
	var toolStatus = {};
	var comment = {};
	
	/**
	  *	@desc	insert date
	  *	@param	none
	  *	@return	none
	*/
	function getDate() {
		var d = <?php if (!$isCurrent) { echo 'new Date("' . date_format($job['DATE_OUT'],'m/d/y') . '")'; } else { echo 'new Date()'; } ?>;
		
		document.getElementById("date-input").value = formatDate(d);
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
	  *	@desc	create/display quality of master
	  *	@param	none
	  *	@return	none
	*/
	function popQuality() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		
		var html = `<span class="close" id="close">&times;</span><div style="display: inline-block;">
					<span style="margin-left: 3px;">New Tool<input type="text" id="tool-quality-input" value="${document.getElementById("new-master-input").value}" style="width: 266px;"></span><br>
					<span style="margin-left: 31px;">Date<input type="text" id="date-quality-input" value="${document.getElementById("date-input").value}" style="width: 100px;"></span>
					<span>Location<select id="location-quality-select">
					<?php foreach($locations as $location) { ?>
					<option value="<?= $location['LOCATION'] ?>"><?= $location['LOCATION'] ?></option>
					<?php } ?>
					</select></span>
					<span>Drawer<input type="text" id="drawer-quality-input" value="${document.getElementById('drawer-input').value}" style="width: 100px;"></span><br>
					<span style="margin-left: 5px;">Operator<input type="text" id="operator-quality-input" value="${document.getElementById("operator-input").value}" style="width: 100px;"></span>
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
					<span>Apply to Process<input type="text" id="process-quality-input" value="MASTERING" readonly style="width: 100px;"></span>
					<span>Apply to WO #<input type="text" id="wo-quality-input" value="${document.getElementById("wo-input").value}" readonly style="width: 100px;"></span>`;
					
		modalContent.innerHTML = html;
		
		document.getElementById("location-quality-select").value = document.getElementById("location-input").value;
		
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
	  *	@desc	save master quality info to status object
	  *	@param	none
	  *	@return	none
	*/
	function saveQuality() {
		toolStatus.TOOL = document.getElementById("tool-quality-input").value;
		toolStatus.STATUS = document.getElementById("status-quality-select").value;
		toolStatus.REASON = document.getElementById("defect-quality-select").value;
		toolStatus.PROCESS = document.getElementById("process-quality-input").value;
		toolStatus.WO_NUMBER = document.getElementById("wo-quality-input").value;
		
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
				job.TOOL_OUT = document.getElementById("new-master-input").value;
				job.DATE_OUT = formatDate(new Date());
				job.OPERATOR_OUT = document.getElementById("operator-input").value;
				job.STATUS_OUT = toolStatus.STATUS;
				job.CUSTOMER_TOOL_TYPE = document.getElementById("tool-type-select").value;
				job.id = <?= $job['ID'] ?>;
				
				var conn = new XMLHttpRequest();
				var table = "Mastering";
				var action = "update";
				var query = "";
				
				Object.keys(job).forEach((item, index, array) => {
					if (item != "id") {
						query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					} else {
						query += `&condition=id&value=${job[item]}`;
					}
				})
				
				conn.onreadystatechange = function() {
					if(conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Data updated")) {
							saveTool();
						} else {
							alert("Job not completed. Contact IT Support to correct. " + conn.responseText);
						}
					}
				}
				
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
	  *	@desc	save new master data
	  *	@param	none
	  *	@return	none
	*/
	function saveTool() {
		var tool = {
			MANDREL: document.getElementById("design-input").value,
			BLANK: <?php echo $job['IS_BLANK'] == "TRUE" ? "document.getElementById(\"blank-input\").value" : "\"\""; ?>,
			LEVEL: 0,
			LOCATION: document.getElementById("location-input").value,
			DRAWER: document.getElementById("drawer-input").value,
			TOOL_TYPE: document.getElementById("tool-type-select").value,
			DATE_CREATED: formatDate(new Date),
			OPERATOR: document.getElementById("operator-input").value,
			TOOL: document.getElementById("new-master-input").value
		}
		
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "update";
		var query = "";
		
		Object.keys(tool).forEach((item, index, array) => {
			if (item != "TOOL") {
				query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			} else {
				query += `&condition=${item}&value=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
		})
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated")) {
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
	  *	@desc	save master comment data
	  *	@param	none
	  *	@return	none
	*/
	function saveComment() {
		var comment = {
			COMMENT: document.getElementById("comment-textarea").value,
			PROCESS: "MASTERING",
			TOOL: document.getElementById("new-master-input").value,
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
	  *	@desc	save master status data
	  *	@param	none
	  *	@return	none
	*/
	function saveStatus() {
		toolStatus.DATE = formatDate(new Date());
		toolStatus.OPERATOR = document.getElementById("operator-quality-input").value;
	
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
					if (document.getElementById("framing").checked == true) {
						addFramingJob();
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
	  *	@desc	add framing job in toolroom, if required
	  *	@param	none
	  *	@return	none
	*/
	function addFramingJob() {
		var conn = new XMLHttpRequest();
		var table = "Toolroom";
		var action = "insert";
		var query = "";
		var date = new Date()
		var frame = {
			BATCH_NUMBER: job.BATCH_NUMBER,
			WO_NUMBER: getNextWorkNumber(),
			JOB_NUMBER: job.JOB_NUMBER,
			PO_NUMBER: job.PO_NUMBER,
			PROCESS: "FRAMING",
			SEQNUM: job.SEQNUM + 1,
			TARGET_DATE: formatDate(new Date(date.setDate(date.getDate() + 2))),
			TOOL_IN: job.TOOL_OUT.replace(/[+]/g, "%2B"),
			DATE_IN: formatDate(new Date()),
			DATE_OUT: formatDate(new Date(date)),
			SPECIAL_INSTRUCTIONS: job.SPECIAL_INSTRUCTIONS,
			THICKNESS1: 0.00000,
			THICKNESS2: 0.00000,
			THICKNESS3: 0.00000,
			THICKNESS4: 0.00000,
			THICKNESS5: 0.00000,
			THICKNESS6: 0.00000,
			CUSTOMER_TOOL_TYPE: job.CUSTOMER_TOOL_TYPE,
			BOW: 0.00000,
			OPPOSITE: "FALSE",
			BOW_AFTER_MACHINING: 0.00000,
			OPPOSITE_AFTER_MACHINING: "FALSE",
			BOW_AFTER_CUTOUT: 0.00000,
			OPPOSITE_AFTER_CUTOUT: "FALSE",
			BOW_AFTER_LAP: 0.00000,
			OPPOSITE_AFTER_LAP: "FALSE",
			COMMENT: job.COMMENT
		}
		
		Object.keys(frame).forEach((item, index, array) => {
			query += `&${item}=${frame[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		})
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded.")) {
					addNextJob();
					//alert("Job completed. Framing job added.");
					//window.location.replace("mastering.php");
				} else {
					alert("Job completed, but framing job not added. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	move next job in batch into current work
	  *	@param	none
	  *	@return	none
	*/
	function addNextJob() {
		var nextJob = findNextJob();
		if (nextJob != "" && nextJob != undefined) {
			var nextJob = {};
			var conn = new XMLHttpRequest();
			var action = "insert";
			var query = "";
			var table;
			
			switch(nextJob['TABLE']) {
				case "Mastering_Queue":
					table = "Mastering";
					break;
				case "Toolroom_Queue":
					table = "Toolroom";
					break;
				case "Electroforming_Queue":
					table = "Electroforming";
					break;
				case "Shipping_Queue":
					table = "Shipping";
					break;
			}
			
			Object.keys(nextJob).forEach((item, index, array) => {
				query += `&${item}=${nextJob[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			})
			
			conn.onreadystatechange = function() {
				if(conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						removeNextFromQueue(nextJob.WO_NUMBER, table);
					} else {
						alert("Next job in batch not added. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
			conn.send();
		} else {
			alert("Job completed");
			document.getElementsByTagName("body")[0].innerHTML += `<form action="mastering.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
			document.getElementById("return-form").submit();
		}
	}
	
	/**
	  *	@desc	get next job in batch
	  *	@param	none
	  *	@return	none
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
				result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
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
			if (item['SEQNUM'] < nextSeqNum && item['TOOL_IN'] == job.TOOL_IN) {
				nextSeqNum = item['SEQNUM'];
				nextJob = index;
			}
		});
		
		return jobs[nextJob];
	}
	
	/**
	  *	@desc	remove next job from queue
	  *	@param	int woNumber - WO# to delete, string table - table to delete from
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
					document.getElementsByTagName("body")[0].innerHTML += `<form action="mastering.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
					document.getElementById("return-form").submit();
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&WO_NUMBER="+woNumber,false);
		conn.send();
	}
	
	/**
	  *	@desc	get next available WO#
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
					jobs = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
					for (let job of jobs) {
						for (let x in job) {
							if (job[x] !== null && typeof job[x] == 'object') {
								job[x] = formatDate(new Date(job[x]['date']));
							}
						}
					}
					if (jobs.length > 0) {
						if (parseInt(jobs[0]['WO_NUMBER']) > max) {
							max = parseInt(jobs[0]['WO_NUMBER']);
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
		document.getElementsByTagName("body")[0].innerHTML += `<form action="mastering.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
		document.getElementById("return-form").submit();
	}
</script>
<html>
	<head>
		<title>Post-Cut Mastering</title>
		<link rel="stylesheet" type="text/css" href="/styles/masteringout.css">
	</head>
	<body onload="getDate()">
		<div class="outer">
		    <div class="inner">
			    <div class="basic-info">
			        <span id="job-span">Job #<input type="text" id="job-input" value="<?= $job['JOB_NUMBER'] ?>" readonly></span>
			        <span id="wo-span">WO #<input type="text" id="wo-input" value="<?= $job['WO_NUMBER'] ?>" readonly></span>
			        <span id="po-span">PO #<input type="text" id="po-input" value="<?= $job['PO_NUMBER'] ?>" readonly></span><br>
				    <span id="blank-span" <?php if($job[24] == "FALSE") { ?>style="margin-left: 0;"<?php } ?>><?php if($job['IS_BLANK'] == "TRUE") { ?>Blank<?php } else { ?>Recut Master<?php } ?><input type="text" id="blank-input" value="<?= $job['BLANK'] ?>" readonly></span><br>
			        <span id="location-span">Location<input type="text" id="location-input" readonly></span>
			        <span id="drawer-span">Drawer<input type="text" id="drawer-input" readonly></span><br>
			        <span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span>
				    <span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input" <?php if($_SESSION['name'] != "eform" && $_SESSION['name'] != "troom" && $_SESSION['name'] != "master") { echo 'value="' . (!$isCurrent ? $job['OPERATOR_OUT'] : $_SESSION['initials']) . '"'; } ?>></span>
			        <input type="checkbox" id="framing">Framing Required
			    </div>
			    <div class="controls">
			        <button onclick="saveJob()">Save</button><br>
			        <button onclick="goBack()">Back</button>
			    </div>
			    <div class="details">
			        <span id="design-span">Design<input type="text" id="design-input" value="<?= $job['TOOL_IN'] ?>" readonly></span><button onclick="showDesign()">Design Info</button><br>
			        <span id="design-date-span">Created<input type="text" id="design-date-input" value="<?= date_format($design['DATE'],'m/d/y') ?>" readonly></span><br>
			        <span id="drawing-span">Drawing #<input type="text" id="drawing-input" value="<?= $design['DRAWING'] ?>" readonly></span>
			        <span id="file-span">File Name<input type="text" id="file-input" value="<?= $design['FILENAME'] ?>" readonly></span><br>
			        <span id="tool-type-span">Tool Type<select id="tool-type-select">
						<?php foreach($types as $type) { ?>
						<option value="<?= $type['TYPE'] ?>"><?= $type['TYPE'] ?></option>
						<?php } ?>
					</select></span>
			    </div>
			    <div class="result">
			        <span id="new-master-span">New Master<input type="text" id="new-master-input" value="<?= $job['TOOL_OUT'] ?>" readonly></span><button id="quality-button" onclick="popQuality()">Q</button><br>
			        <span id="comment-span">Comment</span><br>
			        <textarea rows="4" cols="69" id="comment-textarea" value="<?= $job['COMMENT'] ?>"></textarea>
			    </div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
