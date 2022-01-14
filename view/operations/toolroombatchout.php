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
	$jobs = array();
	$tools = array();
	$locations = array();
	$defects = array();
	$statuses = array();
	$toolTypes = array();
	$apertures = array();
	
	if ($conn) {
		$batchNum = 0;
		$result = sqlsrv_query($conn, "SELECT BATCH_NUMBER FROM Toolroom WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$batchNum = $row['BATCH_NUMBER'];
			}
		}
	
		$result = sqlsrv_query($conn, "SELECT * FROM Toolroom WHERE BATCH_NUMBER = " . $batchNum . " AND PROCESS = '" . $_POST['process'] . "' AND OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '' AND (OPERATOR_OUT = '' OR OPERATOR_OUT IS NULL);");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[] = $row;
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
		
		foreach($jobs as $job) {
			$result = sqlsrv_query($conn, "SELECT Program, Aperture FROM Program_Apertures WHERE PROGRAM = '" . $job['PROGRAM_NUMBER'] . "';");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$apertures[$row['PROGRAM']] = $row['APERTURE'];
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
	var isMetric = false;
	var toolStatuses = [];
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
	  *	@param	str tool - tool name to assign quality to
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
					
					job = {
						DATE_OUT: formatDate(d),
						OPERATOR_OUT: document.getElementById("operator-input").value,
						STATUS_OUT: toolStatuses[index].STATUS,
						CUSTOMER_TOOL_TYPE: document.getElementsByClassName("tool-type-select")[index].value,
						COMMENT: document.getElementsByClassName("comment-textarea")[index].value
					}
					
					if ("<?=$_POST['process']?>" != "FRAMING" && "<?=$_POST['process']?>" != "CONVERT" && "<?=$_POST['process']?>" != "LOGO") {
						job.THICKNESS1 = document.getElementsByClassName("thickness1-input")[index].value;
						job.THICKNESS2 = document.getElementsByClassName("thickness2-input")[index].value;
						job.THICKNESS3 = document.getElementsByClassName("thickness3-input")[index].value;
						job.THICKNESS4 = document.getElementsByClassName("thickness4-input")[index].value;
						job.THICKNESS5 = document.getElementsByClassName("thickness5-input")[index].value;
						job.THICKNESS6 = document.getElementsByClassName("thickness6-input")[index].value;
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
								alert("Job not completed. Contact IT Support to correct. " + conn.responseText);
							}
						}
					}
					
					Object.keys(job).forEach((item2, index2, array2) => {
						if (item2 != "id") {
							query += `&${item2}=${job[item2].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[<]/g,"%3C").replace(/[>]/g,"%3E")}`;
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
	  *	@desc	save tool data
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
				THICKNESS1: document.getElementsByClassName("thickness1-input")[index].value,
				THICKNESS2: document.getElementsByClassName("thickness2-input")[index].value,
				THICKNESS3: document.getElementsByClassName("thickness3-input")[index].value,
				THICKNESS4: document.getElementsByClassName("thickness4-input")[index].value,
				THICKNESS5: document.getElementsByClassName("thickness5-input")[index].value,
				THICKNESS6: document.getElementsByClassName("thickness6-input")[index].value,
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
						alert("Tool not saved. Contact IT Support to correct. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query,false);
			conn.send();
		});
	}
	
	/**
	  *	@desc	save tool's comment
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
			
			Object.keys(comment).forEach((item2, index, array) => {
				query += `&${item2}=${comment[item2].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			});
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded.")) {
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
	  *	@desc	save tool status
	  *	@param	none
	  *	@return	none
	*/
	function saveStatuses() {
		var counter = 0;
		var required = toolStatuses.length;
		toolStatuses.forEach((item, index, array) => {
			item.DATE = formatDate(new Date());
			item.TOOL = item.TOOL;
			
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
						
						if (["PURGED","RETIRED","NOGOOD"].includes(item.STATUS)) {
							abortNextJobs(item.TOOL);
						}
						
						if (counter >= required) {
							addNextJobs();
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
	function addNextJobs() {
		var counter = 0;
		var required = jobs.length;
		jobs.forEach((item, index, array) => {
			var next = findNextJob(item['BATCH_NUMBER'], item['TOOL_IN']);
			if (next != "" && next != undefined) {
				var conn = new XMLHttpRequest();
				var action = "insert";
				var query = "";
				var table = next["TABLE"].split("_")[0];
				delete next.TABLE;
				
				Object.keys(next).forEach((item2, index2, array2) => {
					if (item != 'ID') {
						query += `&${item2}=${next[item2].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					}
				})
				
				conn.onreadystatechange = function() {
					if(conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Insert succeeded")) {
							removeNextFromQueue(next.WO_NUMBER, table);
							counter++;
						} else {
							alert("Next job in batch not added. Contact IT Support to correct. " + conn.responseText);
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
				conn.send();
			} else {
				counter++;
			}
		});
		
		if (counter >= required) {
			alert("Job completed");
			document.getElementsByTagName("body")[0].innerHTML += `<form action="toolroom.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
			document.getElementById("return-form").submit();
		}
	}
	
	/**
	  *	@desc	get next job in batch
	  *	@param	int batch - batch number, str tool - tool name to search for
	  *	@return	array containing job data
	*/
	function findNextJob(batch, tool) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var tables = ["Mastering_Queue","Toolroom_Queue","Electroforming_Queue","Shipping_Queue"];
		var condition = "BATCH_NUMBER";
		var value = batch;
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
			if (item['SEQNUM'] < nextSeqNum && (item['TOOL_IN'] ? item['TOOL_IN'] : item['TOOL']) == tool) {
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
					return;
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
		<title>Tool Out</title>
		<?php if($_POST['process'] == "FRAMING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/framingbatchout.css">
		<?php } else if ($_POST['process'] == "LOGO") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/logobatchout.css">
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/backmachinebatchout.css">
		<?php } ?>
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<?php if ($_POST['process'] == "FRAMING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$jobs[0]['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$jobs[0]['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJobs()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<?php foreach($jobs as $id => $job) { ?>
				<div class="details-container"><span onclick="showDetails(this)" style="cursor: pointer;"><div class="down-arrow"></div><span class="tool"><strong><?=$job['TOOL_IN']?></strong></span><span style="float: right;">WO#: <?=$job['WO_NUMBER']?></span></span>
					<div class="details">
						<span class="location-span">Location<input class="location-input" value="<?=$tools[$job['TOOL_IN']]['LOCATION']?>" readonly></span><span class="drawer-span">Drawer<input type="text" class="drawer-input" value="<?=$tools[$job['TOOL_IN']]['DRAWER']?>" readonly></span><button onclick="popQuality(this.parentNode.parentNode.children[0].children[1].innerText)">Quality</button><br>
						<span class="comment-span">Comment</span><textarea rows="4" cols="57" class="comment-textarea"></textarea><br>
						<span class="framed-tool-span">Framed Tool<input type="text" class="framed-tool-input" value="<?=$job['TOOL_OUT']?>" readonly></span><br>
						<span class="tool-type-span">Tool Type<select class="tool-type-select">
						<?php foreach($toolTypes as $toolType) { ?>
							<option value="<?=$toolType['TYPE']?>"><?=$toolType['TYPE']?></option>
						<?php } ?>
						</select></span><br>
						<div class="deburr">
							<span class="deburr-label">Deburr</span><br>
							<span class="deburr-operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" class="deburr-operator-input"></span><br>
							<span class="deburr-date-span">Date<input type="text" class="deburr-date-input"></span>
						</div>
						<div class="program">
							<span class="program-span">Program #<input type="text" class="program-input" value="<?=$job['PROGRAM_NUMBER']?>" readonly></span><br>
							<span class="filename-span">File Name<input type="text" class="filename-input" readonly></span><br>
							<span class="aperture-span">Aperture<input type="text" class="aperture-input" value="<?=$apertures[$id]?>" readonly></span>
						</div>
					</div><br>
				<?php }} else if ($_POST['process'] == "LOGO") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$jobs[0]['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$jobs[0]['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJobs()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<?php foreach($jobs as $id => $job) { ?>
				<div class="details-container"><span onclick="showDetails(this)" style="cursor: pointer;"><div class="down-arrow"></div><span class="tool"><strong><?=$job['TOOL_IN']?></strong></span><span style="float: right;">WO#: <?=$job['WO_NUMBER']?></span></span>
					<div class="details">
						<span class="tool-type-span">Tool Type<select class="tool-type-select">
						<?php foreach($toolTypes as $toolType) { ?>
							<option value="<?=$toolType['TYPE']?>"><?=$toolType['TYPE']?></option>
						<?php } ?>
						</select></span><br>
						<span class="location-span">Location<input class="location-input" value="<?=$tools[$job['TOOL_IN']]['LOCATION']?>" readonly></span><span class="drawer-span">Drawer<input type="text" class="drawer-input" value="<?=$tools[$job['TOOL_IN']]['DRAWER']?>" readonly></span><button onclick="popQuality(this.parentNode.parentNode.children[0].children[1].innerText)">Quality</button><br>
						<span class="comment-span">Comment</span><textarea rows="4" cols="57" class="comment-textarea"></textarea>
						</div>
					</div>
				<?php } ?>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="66" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$jobs[0]['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$jobs[0]['PO_NUMBER']?>" readonly></span><br>
					<span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span><br>
				</div>
				<div class="controls">
					<button onclick="saveJobs()">Save</button><br>
					<button onclick="goBack()">Back</button><br>
					<button onclick="switchUnit(this)" id="unit-button">Metric</button>
				</div><br><br>
				<?php foreach($jobs as $job) { ?>
				<div class="details-container"><span onclick="showDetails(this)" style="cursor: pointer;"><div class="down-arrow"></div><span class="tool"><strong><?=$job['TOOL_IN']?></strong></span><span style="float: right;">WO#: <?=$job['WO_NUMBER']?></span></span>
					<div class="details">
						<span class="tool-type-span">Tool Type<select class="tool-type-select">
						<?php foreach($toolTypes as $toolType) { ?>
							<option value="<?=$toolType['TYPE']?>"><?=$toolType['TYPE']?></option>
						<?php } ?></select></span><br>
						<span class="location-span">Location<input class="location-input" value="<?=$tools[$job['TOOL_IN']]['LOCATION']?>" readonly></span><span class="drawer-span">Drawer<input type="text" class="drawer-input" value="<?=$tools[$job['TOOL_IN']]['DRAWER']?>" readonly></span><button onclick="popQuality(this.parentNode.parentNode.children[0].children[1].innerText)">Quality</button><br>
						<span class="comment-span">Comment</span><textarea rows="4" cols="57" class="comment-textarea"></textarea><br>
						<span class="machine-span">Machine #<input type="text" class="machine-input" value="<?=$job['MACHINE_NUMBER']?>" readonly></span>
						<span class="thickness-label">Thickness</span><br>
						<span class="thickness1-span"><input type="text" class="thickness thickness1-input"></span>
						<span class="thickness2-span"><input type="text" class="thickness thickness2-input"></span>
						<span class="thickness3-span"><input type="text" class="thickness thickness3-input"></span>
						<span class="thickness4-span"><input type="text" class="thickness thickness4-input"></span>
						<span class="thickness5-span"><input type="text" class="thickness thickness5-input"></span>
						<span class="thickness6-span"><input type="text" class="thickness thickness6-input"></span>
						<span class="unit">(in)</span>
					</div><br>
				</div>
				<?php }} ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
