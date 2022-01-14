<!DOCTYPE html>
<?php
/**
  *	@desc process eform job out
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//job data, mandrel data, lists of values to choose from
	$locations = array();
	$statuses = array();
	$defects = array();
	$toolTypes = array();
	$job = array();
	$mandrel = array();
	$finishedTool = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION FROM Inv_Locations WHERE STATUS = 'Active' ORDER BY LOCATION");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STATUS FROM Tool_Status WHERE STATE = 'Active' ORDER BY STATUS");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT FROM Valid_Defects WHERE STATUS = 'Active' ORDER BY DEFECT");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TYPE FROM Customer_Tool_Types WHERE STATUS = 'Active' ORDER BY TYPE;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolTypes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Electroforming WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
				if ($job['OPERATOR_OUT'] == "") {
					$isCurrent = true;
				} else {
					$isCurrent = false;
				}
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Electroforming_History WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isCurrent = false;
				}
			}
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Electroforming_Queue WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isCurrent = false;
				}
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER, STATUS, REASON FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_IN'] . "';");
		if ($result) {
			while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$mandrel = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT TOP 1 COMMENT FROM Comment_History WHERE TOOL = '" . $job['TOOL_IN'] . "' ORDER BY ID DESC;");
		if ($result) {
			while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$mandrel['COMMENT'] = $row['COMMENT'];
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION, DRAWER, STATUS, REASON, TOOL_TYPE FROM Tool_Tree WHERE TOOL = '" . $job['TOOL_OUT'] . "';");
		if ($result) {
			while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$finishedTool = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT TOP 1 COMMENT FROM Comment_History WHERE TOOL = '" . $job['TOOL_OUT'] . "' ORDER BY ID DESC;");
		if ($result) {
			while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$finishedTool['COMMENT'] = $row['COMMENT'];
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
	var job = {
	<?php $keys = array_keys($job);
	echo "\t";
	foreach($keys as $key) {
		if ($key != "ID") {
			if (gettype($job[$key]) == "string") {
				echo $key . ": `" . $job[$key] . "`,\n\t\t";
			} else if (gettype($job[$key]) == "NULL") {
				echo $key . ": '',\n\t\t";
			} else if ($job[$key] instanceof DateTime) {
				echo $key . ": `" . date_format($job[$key],'m/d/y H:i') . "`,\n\t\t";
			} else {
				echo $key . ": " . $job[$key] . ",\n\t\t";
			}
		}
	} ?>
	};
	
	/**
	  *	@desc	insert date, operator name, mandrel status data
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		document.getElementById("date-input").value = <?php if (!$isCurrent) { echo '"' . date_format($job['DATE_OUT'],'m/d/y') . '"'; } else { echo 'formatDate(new Date())'; } ?>;
		document.getElementById("time-input").value = <?php if (!$isCurrent) { echo '"' . date_format($job['DATE_OUT'],'H:i:s') . '"'; } else { echo 'formatTime(new Date())'; } ?>;
		<?php if (!$isCurrent) { ?>
		document.getElementById("operator-input").value = '<?=$job['OPERATOR_OUT']?>';
		document.getElementById("operator-input").readOnly = false;
		<?php } else {
			if ($_SESSION['name'] != "eform" && $_SESSION['name'] != "master" && $_SESSION['name'] != "troom") { ?>
		document.getElementById("operator-input").value = "<?=$_SESSION['initials']?>";
			<?php } ?>
		document.getElementById("operator-input").readOnly = false;
		<?php } ?>
		
		<?php if ($_POST['process'] == "EFORM") { ?>
		document.getElementById("mandrel-location-select").value = "<?=$mandrel['LOCATION']?>";
		document.getElementById("mandrel-drawer-input").value = "<?=$mandrel['DRAWER']?>";
		document.getElementById("mandrel-status-select").value = "<?=$mandrel['STATUS']?>";
		document.getElementById("mandrel-defect-select").value = "<?=$mandrel['DEFECT']?>";
		document.getElementById("tool-location-select").value = "<?=$finishedTool['LOCATION']?>";
		document.getElementById("tool-drawer-input").value = "<?=$finishedTool['DRAWER']?>";
		document.getElementById("tool-status-select").value = "<?=$finishedTool['STATUS']?>";
		document.getElementById("tool-defect-select").value = "<?=$finishedTool['DEFECT']?>";
		document.getElementById("tool-out-input").value = "<?=$job['TOOL_OUT']?>";
		document.getElementById("type-select").value = "<?=$finishedTool['TYPE']?>";
		<?php } else { ?>
		
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
		if (year < 10) {
			year = "0" + year;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString();
		
		return date;
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - H:i:s
	*/
	function formatTime(d) {
		var hour = d.getHours();
		if (hour < 10) {
			hour = "0" + hour;
		}
		var minute = d.getMinutes();
		if (minute < 10) {
			minute = "0" + minute;
		}
		var second = d.getSeconds();
		if (second < 10) {
			second = "0" + second;
		}
		
		date = hour.toString() + ":" + minute.toString() + ":" + second.toString();
		
		return date;
	}
	
	/**
	  *	@desc	get next available tool name
	  *	@param	string tool - TOOL_IN value
	  *	@return	int (newForm + 1) - last used tool name incremented by 1
	*/
	function getNewForm(tool) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "MANDREL";
		var value = tool;
		var newForm = 0;
		
		value = value.replace(/[+]/g, "%2B");
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of response) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDate(new Date(tool[x]['date']));
						}
					}
				}
				if (response.length > 0) {
					for (var i=0;i<response.length;i++) {
						if (parseInt(response[i]['TOOL'].split("-")[response[i]['TOOL'].split("-").length-1]) > newForm) {
							newForm = parseInt(response[i]['TOOL'].split("-").pop());
						}
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		if (parseInt(job.TOOL_OUT.split("-").pop()) == newForm+1) {
			newForm++;
		}
		return newForm+1;
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("date-input").value == "" || document.getElementById("time-input").value == "") {
			msg = "Please enter a valid date";
		} else if (document.getElementById("tool-location-select").value == "" || document.getElementById("tool-status-select").value == "") {
			msg = "Complete new form quality first";
		} else if (document.getElementById("operator-input").value == "") {
			msg = "Please enter your initials";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	check for unusual thicknesses
	  *	@param	none
	  *	@return	bool true if valid
	*/
	function checkThickness() {
		var valid = true;
		if (parseInt(document.getElementById("thickness1-input").value) > 10) {
			valid = false;
		} else if (parseInt(document.getElementById("thickness2-input").value) > 10) {
			valid = false;
		} else if (parseInt(document.getElementById("thickness3-input").value) > 10) {
			valid = false;
		} else if (parseInt(document.getElementById("thickness4-input").value) > 10) {
			valid = false;
		} else if (parseInt(document.getElementById("thickness5-input").value) > 10) {
			valid = false;
		} else if (parseInt(document.getElementById("thickness6-input").value) > 10) {
			valid = false;
		}
		
		return valid;
	}
	
	/**
	  *	@desc	check for <50% of elapsed cycle time
	  *	@param	none
	  *	@return bool true if valid
	*/
	function checkDate(button) {
		button.disabled = true;
		var startDate = new Date(job.DATE_IN);
		var now = new Date(document.getElementById("date-input").value + " " + document.getElementById("time-input").value);
		var endDate = new Date(job.DATE_OUT);
		if ((endDate-startDate) / 2 > now - startDate) {
			var modal = document.getElementById("modal");
			var modalContent = document.getElementById("modal-content");
			modalContent.innerHTML = '<span id="close">&times;</span><span class="warning-span" style="color:red;"><strong>WARNING:</strong></span><span>Tool has been in tank for &lt;50% of its cycle time. Continue anyway?</span><br><button onclick="document.getElementById(\'close\').click();saveJob();">Yes</button><button onclick="document.getElementById(\'close\').click()">No</button>';
			modal.style.display = "block";
			modalContent.style.width = "400px;";
			button.disabled = false;
			closeForm();
		} else {
			saveJob(button);
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
	  *	@desc	save job data
	  *	@param	none
	  *	@return	none
	*/
	function saveJob(button) {
		button.disabled = true;
		var msg = checkFields();
		
		if (msg == '') {
			if (!checkThickness()) {
				alert("One of your thickness values seems off. Check those values and try again.");
				button.disabled = false;
			//} else if (!checkDate()) {
			//	alert("Too soon for this!");
			} else {
				var conn = new XMLHttpRequest();
				if (isArchived()) {
					var table = "Electroforming_History";
				} else {
					var table = "Electroforming";
				}
				var action = "update";
				var query = "";
				
				job.DATE_OUT = formatDate(new Date(document.getElementById("date-input").value)) + " " + formatTime(new Date(document.getElementById("date-input").value + " " + document.getElementById("time-input").value));
				job.OPERATOR_OUT = document.getElementById("operator-input").value;
				<?php if ($_POST['process'] == "EFORM") { ?>
				job.STATUS_IN = document.getElementById("mandrel-status-select").value;
				job.STATUS_OUT = document.getElementById("tool-status-select").value;
				job.THICKNESS1 = document.getElementById("thickness1-input").value;
				job.THICKNESS2 = document.getElementById("thickness2-input").value;
				job.THICKNESS3 = document.getElementById("thickness3-input").value;
				job.THICKNESS4 = document.getElementById("thickness4-input").value;
				job.THICKNESS5 = document.getElementById("thickness5-input").value;
				job.THICKNESS6 = document.getElementById("thickness6-input").value;
				job.BRIGHTNESS1 = document.getElementById("brightness1-input").value;
				job.BRIGHTNESS2 = document.getElementById("brightness2-input").value;
				job.BRIGHTNESS3 = document.getElementById("brightness3-input").value;
				job.COMMENT = document.getElementById("tool-comment-textarea").value;
				job.TOOL_BOW = document.getElementById("bow-select").value;
				<?php } else { ?>
				job.STATUS_OUT = document.getElementById("status-select").value;
				job.COMMENT = document.getElementById("comment-textarea").value;
				<?php } ?>
				job.id = <?=$job['ID']?>;
				job.MODE = "DONE";
				
				Object.keys(job).forEach((item, index, array) => {
					if (item != "id") {
						query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					} else {
						query += `&condition=id&value=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
					}
				});
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						if (conn.responseText.includes("Data updated")) {
							if ("<?=$_POST['process']?>" == "EFORM" && !isArchived() && <?= var_export($isCurrent, true); ?>) {
								reschedule();
							}
							<?php if ($_POST['process'] == "EFORM") { ?>
							if (document.getElementById("mandrel-comment-textarea").value.length > 0 && document.getElementById("mandrel-comment-textarea").value != `<?=$mandrel[5]?>`) {
								saveMandrelComment();
							} else if (document.getElementById("tool-comment-textarea").value.length > 0) {
								saveToolComment();
							} else {
								saveMandrel();
							}
							<?php } else { ?>
							if (document.getElementById("comment-textarea").value.length > 0) {
								saveToolComment();
							} else {
								saveTool();
							}
							<?php } ?>
						} else {
							alert("Job not completed. Contact IT Support to correct. " + conn.responseText);
							counter--;
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
				conn.send();
			}
		} else {
			alert(msg);
			button.disabled = false;
		}
	}
	
	/**
	  *	@desc	determine if job is archived already
	  *	@param	none
	  *	@return	true if in Electroforming_History, false otherwise
	*/
	function isArchived() {
		var conn = new XMLHttpRequest();
		var table = "Electroforming_History";
		var action = "select";
		var query = "&condition=WO_NUMBER&value="+job.WO_NUMBER;
		var archived = false;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				if (response.length > 0) {
					archived = true;
				} else {
					archived = false;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query,false);
		conn.send();
		
		return archived;
	}
	
	/**
	  *	@desc	schedule next job if indefinite, thru generation, or repeat scheduling
	  *	@param	none
	  *	@return	none
	*/
	function reschedule() {
		sendEmail();
	
		var status = document.getElementById("mandrel-status-select").value;
		if ((job.REPEAT == 1 && job.SCHEDULE_TYPE != "Indefinite") || ((status == "NOGOOD" || status == "PURGED" || status == "RETIRED") && job.SCHEDULE_TYPE != "Thru Generation")) {
			return;
		} else {
			var newJob = {};
			
			for (var attr in job) {
				if (attr == "OPERATOR_IN" || attr == "STATUS_IN" || attr == "OPERATOR_OUT" || attr == "STATUS_OUT" || attr == "MODE" || attr == "THICKNESS1" || attr == "THICKNESS2" || attr == "THICKNESS3" || attr == "THICKNESS4" || attr == "THICKNESS5" || attr == "THICKNESS6" || attr == "BRIGHTNESS1" || attr == "BRIGHTNESS2" || attr == "BRIGHTNESS3") {
					newJob[attr] = "";
				} else {
					newJob[attr] = job[attr];
				}
			}
			
			if (newJob.SCHEDULE_TYPE != 'Indefinite') {
				newJob.REPEAT -= 1;
			
				if (newJob.SCHEDULE_TYPE == 'Repeat') {
					newJob.TOOL_OUT = newJob.TOOL_IN + "-" + getNewForm(newJob.TOOL_IN);
				} else if (newJob.SCHEDULE_TYPE == 'Thru Generation') {
					newJob.TOOL_IN = newJob.TOOL_OUT;
					newJob.TOOL_OUT = newJob.TOOL_OUT + "-1";
				}
			} else {
				newJob.TOOL_OUT = newJob.TOOL_IN + "-" + getNewForm(newJob.TOOL_IN);
			}
			
			newJob.WO_NUMBER = getNextWorkNumber();
			
			var conn = new XMLHttpRequest();
			var action = "insert";
			var table = "Electroforming";
			var query = "";
			
			Object.keys(newJob).forEach((item, index, array) => {
				query += `&${item}=${newJob[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			});
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (conn.responseText.includes("Insert succeeded")) {
						saveNewTool(newJob.TOOL_IN, newJob.TOOL_OUT);
						return;
					} else {
						alert("New job not scheduled. Contact IT support to correct. " + conn.responseText);
						return;
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
			conn.send();
		}
	}
	
	//for debugging purposes
	function sendEmail() {
		var conn = new XMLHttpRequest();
		conn.open("GET","/debugmail.php?process=<?=$_POST['process']?>&isArchived=" + isArchived() + "&isCurrent=<?= var_export($isCurrent, true) ?>&tool=" + job.TOOL_OUT.replace(/[+]/g,'%2B'),false);
		conn.send();
	}
	
	/**
	  *	@desc	save data of TOOL_OUT
	  *	@param	string mandrel - TOOL_IN value, string tool - TOOL_OUT value
	  *	@return	none
	*/
	function saveNewTool(mandrel, tool) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "insert";
		
		var tool = {
			MANDREL: mandrel,
			TOOL: tool,
			LEVEL: 0,
			STATUS: "GOOD"
		}
		
		var query = "";
		
		Object.keys(tool).forEach((item, index, array) => {
			query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					return;
				} else {
					alert("New job scheduled, but new tool not added to database. Contact IT Support.");
					return;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	save comment on TOOL_IN
	  *	@param	none
	  *	@return	none
	*/
	function saveMandrelComment() {
		var conn = new XMLHttpRequest();
		var table = "Comment_History";
		var action = "insert";
		var query = "";
		var comment = {
			COMMENT: document.getElementById("mandrel-comment-textarea").value,
			PROCESS: "Electroforming",
			TOOL: document.getElementById("tool-input").value,
			DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value
		};
		
		Object.keys(comment).forEach((item, index, array) => {
			query += `&${item}=${comment[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					if (document.getElementById("tool-comment-textarea").value.length > 0) {
						saveToolComment();
					} else {
						saveTool();
					}
				} else {
					alert("Mandrel comment not recorded. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	save comment on TOOL_OUT
	  *	@param	none
	  *	@return	none
	*/
	function saveToolComment() {
		var conn = new XMLHttpRequest();
		var table = "Comment_History";
		var action = "insert";
		var query = "";
		
		<?php if ($_POST['process'] == "EFORM") { ?>
		var comment = {
			COMMENT: document.getElementById("tool-comment-textarea").value,
			PROCESS: "ELECTROFORMING",
			TOOL: document.getElementById("tool-out-input").value,
			DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value
		};
		<?php } else { ?>
		var comment = {
			COMMENT: document.getElementById("comment-textarea").value,
			PROCESS: "CLEANING",
			TOOL: document.getElementById("tool-input").value,
			DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value
		};
		<?php } ?>
		
		Object.keys(comment).forEach((item, index, array) => {
			query += `&${item}=${comment[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					<?php if ($_POST['process'] == "EFORM") { ?>
					saveMandrel();
					<?php } else { ?>
					saveTool();
					<?php } ?>
				} else {
					alert("Tool comment not recorded. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	update TOOL_IN status
	  *	@param	none
	  *	@return	none
	*/
	function saveMandrel() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "update";
		var query = "";
		var tool = {
			STATUS: document.getElementById("mandrel-status-select").value,
			REASON: document.getElementById("mandrel-defect-select").value,
			LOCATION: document.getElementById("mandrel-location-select").value,
			DRAWER: document.getElementById("mandrel-drawer-input").value,
			STATUS_DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value,
			TOOL: document.getElementById("tool-input").value
		}
		
		Object.keys(tool).forEach((item, index, array) => {
			if (item != "TOOL") {
				query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			} else {
				query += `&condition=TOOL&value=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					saveTool();
				} else {
					alert("Mandrel not updated. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	save TOOL_OUT data
	  *	@param	none
	  *	@return	none
	*/
	function saveTool() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		if (toolExists()) {
			var action = "update";
		} else {
			var action = "insert";
		}
		var query = "";
		
		<?php if ($_POST['process'] == "EFORM") { ?>
		var tool = {
			MANDREL: document.getElementById("tool-input").value,
			BLANK: "",
			LEVEL: 0,
			STATUS: document.getElementById("tool-status-select").value,
			REASON: document.getElementById("tool-defect-select").value,
			STATUS_DATE: formatDate(new Date()),
			LOCATION: document.getElementById("tool-location-select").value,
			DRAWER: document.getElementById("tool-drawer-input").value,
			TOOL_TYPE: document.getElementById("type-select").value,
			DATE_CREATED: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value,
			THICKNESS1: document.getElementById("thickness1-input").value,
			THICKNESS2: document.getElementById("thickness2-input").value,
			THICKNESS3: document.getElementById("thickness3-input").value,
			THICKNESS4: document.getElementById("thickness4-input").value,
			THICKNESS5: document.getElementById("thickness5-input").value,
			THICKNESS6: document.getElementById("thickness6-input").value,
			BRIGHTNESS1: document.getElementById("brightness1-input").value,
			BRIGHTNESS2: document.getElementById("brightness2-input").value,
			BRIGHTNESS3: document.getElementById("brightness3-input").value,
			TOOL: document.getElementById("tool-out-input").value,
		}
		<?php } else if ($_POST['process'] == "CLEANING") { ?>
		var tool = {
			STATUS: document.getElementById("status-select").value,
			REASON: document.getElementById("defect-select").value,
			LOCATION: document.getElementById("location-select").value,
			DRAWER: document.getElementById("drawer-input").value,
			STATUS_DATE: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value,
			TOOL: document.getElementById("tool-input").value
		}
		<?php } else { ?>
		var tool = {
			MANDREL: "<?=$job['TOOL_IN']?>",
			BLANK: "",
			LEVEL: 0,
			STATUS: document.getElementById("status-select").value,
			REASON: document.getElementById("defect-select").value,
			LOCATION: document.getElementById("location-select").value,
			DRAWER: document.getElementById("drawer-input").value,
			STATUS_DATE: formatDate(new Date()),
			DATE_CREATED: formatDate(new Date()),
			OPERATOR: document.getElementById("operator-input").value,
			TOOL: document.getElementById("tool-input").value,
		}
		<?php } ?>
		
		Object.keys(tool).forEach((item, index, array) => {
			if (item != "TOOL" || action == "insert") {
				query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			} else {
				query += `&condition=TOOL&value=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated") || conn.responseText.includes("Insert succeeded")) {
					addNextJob();
					//alert("Job completed");
					//window.location.replace("electroforming.php");
				} else {
					alert("New tool not inserted. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	check if TOOL_OUT is already in tree
	  *	@param	none
	  *	@return	true if tool found, false otherwise
	*/
	function toolExists() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Tree";
		var query = "&condition=TOOL&value="+job.TOOL_OUT.replace(/[+]/g,"%2B");
		var exists = false;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				if (response.length > 0) {
					exists = true;
				} else {
					exists = false;
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query,false);
		conn.send();
		
		return exists;
	}
	
	/**
	  *	@desc	move next job to current schedule
	  *	@param	none
	  *	@return	none
	*/
	function addNextJob() {
		var next = findNextJob();
		if (next != "" && next != undefined) {
			var conn = new XMLHttpRequest();
			var action = "insert";
			var query = "";
			var table;
			
			switch(next['TABLE']) {
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
			
			delete next.TABLE;
			delete next.ID;
			
			Object.keys(next).forEach((item, index, array) => {
				query += `&${item}=${next[item] ? next[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A") : ''}`;
			});
			
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
			document.getElementsByTagName("body")[0].innerHTML += `<form action="electroforming.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
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
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let tool of response) {
					for (let x in tool) {
						if (tool[x] !== null && typeof tool[x] == 'object') {
							tool[x] = formatDate(new Date(tool[x]['date'])) + " " + formatTime(new Date(tool[x]['date']));
						}
					}
				}
				
				response.forEach((item, index, array) => {
					item['TABLE'] = conn.responseURL.split("table=")[1].split("&")[0];
				});
				
				for (var i=0;i<response.length;i++) {
					jobs.push(response[i]);
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
	  *	@desc	remove next job in batch from queue
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
					document.getElementsByTagName("body")[0].innerHTML += `<form action="electroforming.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
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
		var tables = ["Mastering","Mastering_Queue","Mastering_History","Toolroom","Toolroom_Queue","Toolroom_History","Shipping","Shipping_Queue","Shipping_History","Electroforming","Electroforming_Queue","Electroforming_History","Abort_History"];
		var action = "select";
		var condition = "WO_NUMBER";
		var max = 0;
		
		tables.forEach((item, index, array) => {
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
					for (let tool of response) {
						for (let x in tool) {
							if (tool[x] !== null && typeof tool[x] == 'object') {
								tool[x] = formatDate(new Date(tool[x]['date'])) + " " + formatTime(new Date(tool[x]['date']));
							}
						}
					}
					
					if (response.length > 0) {
						if (parseInt(response[0]["WO_NUMBER"]) > max) {
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
	  *	@desc	auto-format time fields to HH:ii:ss
	  *	@param	DOM Object input - time field to format
	  *	@return	none
	*/
	function fixTime(input) {
		var key = event.keyCode || event.charCode;
		
		var regex = /\/|\-|\\|\*|\:/;
		
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
						input.value = "0" + input.value.slice(0,-1) + ":";
					} else {
						input.value += ":";
					}
					break;
				case 5:
					if (regex.test(input.value.charAt(4))) {
						var inputArr = input.value.split(regex);
						inputArr.pop();
						input.value = inputArr[0] + "/0" + inputArr.pop() + ":";
					} else {
						input.value += ":";
					}
					break;
				case 3:
				case 6:
					if (!regex.test(input.value.slice(-3))) {
						input.value = input.value.slice(0,-1) + ":" + input.value.slice(-1);
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
		document.getElementsByTagName("body")[0].innerHTML += `<form action="/view/retrieve.php" method="POST" id="return-form" style="display: none;"><input type="text" value="/view/operations/electroforming.php" name="returnpath"><input type="text" value="${'<?=$_POST['tool']?>'}" name="tool"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>`;
		<?php } else { ?>
		document.getElementsByTagName("body")[0].innerHTML += `<form action="electroforming.php" method="POST" id="return-form" style="display: none;"><input type="text" name="returnTool" value="<?=$_POST['id']?>"></form>`;
		<?php } ?>
		document.getElementById("return-form").submit();
	}
</script>
<html>
	<head>
		<title>Tank Out</title>
		<?php if ($_POST['process'] == "EFORM") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/electroformingout.css">
		<?php } else if ($_POST['process'] == "CLEANING") { ?>
		<link rel="stylesheet" type="text/css" href="/styles/cleaningout.css">
		<?php } else { ?>
		<link rel="stylesheet" type="text/css" href="/styles/nickelflashingout.css">
		<?php } ?>
	</head>
	<body onload="initialize();">
		<div class="outer">
			<div class="inner">
				<?php if ($_POST['process'] == "EFORM") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><br>
					<span id="tool-span">Mandrel<input type="text" id="tool-input" value="<?=$job['TOOL_IN']?>" readonly></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input" value="<?=$job['OPERATOR_OUT']?>"></span><span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input" value="<?=date_format($job['DATE_OUT'],'m/d/y')?>"></span><span id="time-span">Time<input onkeydown="fixTime(this)" type="text" id="time-input" value="<?=date_format($job['DATE_OUT'],'H:i:s')?>"></span>
				</div>
				<div class="controls">
					<button onclick="checkDate(this)">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="mandrel-status-info">
					<h4 id="mandrel-label">Mandrel Quality</h4><br>
					<span id="mandrel-location-span">Location<select id="mandrel-location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span>
					<span id="mandrel-drawer-span">Drawer<input type="text" id="mandrel-drawer-input"></span><br>
					<span id="mandrel-status-span">Status<select id="mandrel-status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span>
					<span id="mandrel-defect-span">Defect<select id="mandrel-defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span>
				</div>
				<div class="tool-status-info">
					<h4 id="new-form-label">New Form Quality</h4><br>
					<span id="tool-location-span">Location<select id="tool-location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span>
					<span id="tool-drawer-span">Drawer<input type="text" id="tool-drawer-input"></span><br>
					<span id="tool-status-span">Status<select id="tool-status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span>
					<span id="tool-defect-span">Defect<select id="tool-defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span>
				</div>
				<div class="details">
					<span id="mandrel-comment-span">Mandrel Comments</span><textarea id="mandrel-comment-textarea" rows="4" cols="70"><?=$mandrel['COMMENT']?></textarea><br>
    				<span id="tool-comment-span">Tool Comments</span><textarea id="tool-comment-textarea" rows="4" cols="70"><?=$finishedTool['COMMENT']?></textarea><br>
    				<span id="tool-out-span">New Form<input type="text" id="tool-out-input" readonly=""></span><br>
					<span id="thickness-span">Thickness (mm)<input type="text" id="thickness1-input" value="<?=$job['THICKNESS1']?>"><input type="text" id="thickness2-input" value="<?=$job['THICKNESS2']?>"><input type="text" id="thickness3-input" value="<?=$job['THICKNESS3']?>"><input type="text" id="thickness4-input" value="<?=$job['THICKNESS4']?>"><input type="text" id="thickness5-input" value="<?=$job['THICKNESS5']?>"><input type="text" id="thickness6-input" value="<?=$job['THICKNESS6']?>"></span><br>
					<span id="brightness-span">Brightness (cd/lux.m2)<input type="text" id="brightness1-input" value="<?=$job['BRIGHTNESS1']?>"><input type="text" id="brightness2-input" value="<?=$job['BRIGHTNESS2']?>"><input type="text" id="brightness3-input" value="<?=$job['BRIGHTNESS3']?>"></span><br>
					<span id="bow-span">Tool Bow<select id="bow-select"><option value="No">No</option><option value="Yes">Yes</option></select></span><br>
					<span id="type-span">Tooltype<select id="type-select">
					<?php foreach($toolTypes as $type) { ?>
						<option value="<?=$type['TYPE']?>"><?=$type['TYPE']?></option>
					<?php } ?>
					</select></span>
				</div>
				<?php } else if ($_POST['process'] == "CLEANING") { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_OUT']?>" readonly></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input" value="<?=$job['OPERATOR_OUT']?>"></span><span id="date-span">Date/Time<input onkeydown="fixDate(this)" type="text" id="date-input" value="<?=date_format($job['DATE_OUT'],'m/d/y')?>"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="status-info">
					<span id="location-span">Location<select id="location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span>
					<span id="drawer-span">Drawer<input type="text" id="drawer-input"></span><br>
					<span id="status-span">Status<select id="status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span>
					<span id="defect-span">Defect<select id="defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span>
				</div>
				<span id="comment-span">Comments</span><textarea id="comment-textarea" rows="4" cols="70"><?=$finishedTool[6]?></textarea>
				<?php } else { ?>
				<div class="basic-info">
					<span id="job-span">Job #<input type="text" id="job-input" value="<?=$job['JOB_NUMBER']?>" readonly></span><span id="wo-span">WO #<input type="text" id="wo-input" value="<?=$job['WO_NUMBER']?>" readonly></span><span id="po-span">PO #<input type="text" id="po-input" value="<?=$job['PO_NUMBER']?>" readonly></span><br>
					<span id="tool-span">Tool<input type="text" id="tool-input" value="<?=$job['TOOL_OUT']?>" readonly></span><br>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input" value="<?=$job['OPERATOR_OUT']?>"></span><span id="date-span">Date<input onkeydown="fixDate(this)" type="text" id="date-input" value="<?=date_format($job['DATE_OUT'],'m/d/y')?>"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button><br>
					<button onclick="goBack()">Back</button>
				</div>
				<div class="tank-info">
					<span id="tank-span">Tank / Station<input type="text" id="tank-input" value="<?=$job['TANK']?>" readonly><input type="text" id="station-input" value="<?=$job['STATION']?>" readonly></span><br>
					<span id="temp-span">Temperature<input type="text" id="temp-input" value="<?=$job['TEMPERATURE']?>" readonly></span><br>
					<span id="time-span">Time (min)<input type="text" id="time-input" value="<?=$job['TIME']?>" readonly></span><br>
					<span id="passivated-span">Passivated<select id="passivated-select">
						<option <?php if ($job['PASSIVATED'] == "Yes") { echo "selected"; } else { echo "disabled"; } ?> value="Yes">Yes</option>
						<option <?php if ($job['PASSIVATED'] == "No") { echo "selected"; } else { echo "disabled"; } ?> value="No">No</option>
					</select></span>
				</div>
				<div class="status-info">
					<span id="location-span">Location<select id="location-select">
					<?php foreach($locations as $location) { ?>
						<option value="<?=$location['LOCATION']?>"><?=$location['LOCATION']?></option>
					<?php } ?>
					</select></span><span id="drawer-span">Drawer<input type="text" id="drawer-input"></span><br>
					<span id="status-span">Status<select id="status-select">
					<?php foreach($statuses as $status) { ?>
						<option value="<?=$status['STATUS']?>"><?=$status['STATUS']?></option>
					<?php } ?>
					</select></span><span id="defect-span">Defect<select id="defect-select">
					<?php foreach($defects as $defect) { ?>
						<option value="<?=$defect['DEFECT']?>"><?=$defect['DEFECT']?></option>
					<?php } ?>
					</select></span><br>
				</div><br>
				<span id="comment-span">Comments</span><br><textarea rows="4" cols="89" id="comment-textarea"><?=$finishedTool['COMMENT']?></textarea>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="89" id="special-textarea" readonly><?=$job['SPECIAL_INSTRUCTIONS']?></textarea>
				<?php } ?>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>