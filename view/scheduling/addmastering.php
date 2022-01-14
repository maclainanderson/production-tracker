<!DOCTYPE html>
<?php
/**
  *	@desc create new mastering job
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
	
	//values to choose from for job data
	$toolTypes = array();
	$cosmetics = array();
	$designs = array();
	$blanks = array();
	$masters = array();
	$process = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, TOOLTYPE FROM Tool_Types;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolTypes[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, COSMETIC FROM Cosmetics;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$cosmetics[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DESIGN, DRAWING, FILENAME FROM Designs ORDER BY DESIGN ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$designs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BLANK, LOCATION, DRAWER FROM Blanks ORDER BY BLANK ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$blanks[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, LOCATION, DRAWER, STATUS FROM Tool_Tree WHERE TOOL LIKE '%[A-Z]' ORDER BY TOOL ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$masters[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DURATION FROM Processes WHERE PROCESS = 'MASTERING';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$process = $row;
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
	var workType = "New";
	var batch = {};
	var designs = [<?php
		foreach($designs as $design) {
			echo '{';
			foreach($design as $key=>$value) {
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
	
	var masters = [<?php
		foreach($masters as $master) {
			echo '{';
			foreach($master as $key=>$value) {
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
	
	/**
	  *	@desc	insert operator name and WO#
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
		
		document.getElementById("wo-input").value = getNextWorkNumber();
	}
	
	/**
	  *	@desc	create/display list of designs
	  *	@param	none
	  *	@return	none
	*/
	function popDesignList() {
		var searchText = document.getElementById("design-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span><table id="design-table"><thead><tr><th class="col1">Design</th><th class="col2">Drawing</th><th class="col3">Filename</th></tr></thead><tbody>`;
		
		designs.forEach((item, index, array) => {
			if (item['DESIGN'].includes(searchText.toUpperCase())) {
				html += `<tr id="${item['ID']}" onclick="selectDesignRow(this)"><td class="col1">${item['DESIGN']}</td><td class="col2">${item['DRAWING']}</td><td class="col3">${item['FILENAME']}</td></tr>`;
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight selected design
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectDesignRow(tr) {
		var trs = tr.parentNode.children;
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectDesignRow(this)');
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmDesign(this)');
	}
	
	/**
	  *	@desc	insert selected design to job data
	  *	@param	DOM Object tr - design row selected
	  *	@return	none
	*/
	function confirmDesign(tr) {
		document.getElementById("design-input").value = tr.children[0].innerHTML;
		document.getElementById("drawing-input").value = tr.children[1].innerHTML;
		document.getElementById("file-input").value = tr.children[2].innerHTML;
		document.getElementById("close").click();
	}
	
	/**
	  *	@desc	create/display list of blanks
	  *	@param	none
	  *	@return	none
	*/
	function popBlankList() {
		var searchText = document.getElementById("blank-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span><table id="blank-table"><thead><tr><th class="col1">Blank</th><th class="col2">Location</th><th class="col3">Drawer</th></tr></thead><tbody>`;
		
		blanks.forEach((item, index, array) => {
			if (item['BLANK'].includes(searchText.toUpperCase())) {
				html += `<tr id="${item['ID']}" onclick="selectBlankRow(this)"><td class="col1">${item['BLANK']}</td><td class="col2">${item['LOCATION']}</td><td class="col3">${item['DRAWER']}</td></tr>`;
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight selected blank row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectBlankRow(tr) {
		var trs = tr.parentNode.children;
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectBlankRow(this)');
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmBlank(this)');
	}
	
	/**
	  *	@desc	insert blank to job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmBlank(tr) {
		document.getElementById("blank-input").value = tr.children[0].innerHTML;
		document.getElementById("close").click();
	}
	
	/**
	  *	@desc	create/display list of masters
	  *	@param	none
	  *	@return	none
	*/
	function popMasterList() {
		var searchText = document.getElementById("design-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span><table id="master-table"><thead><tr><th class="col1">Master</th><th class="col2">Location</th><th class="col3">Drawer</th><th class="col4">Status</th></tr></thead><tbody>`;
		
		masters.forEach((item, index, array) => {
			if (item['TOOL'].includes((searchText + "-").toUpperCase())) {
				html += `<tr id="${item['ID']}" onclick="selectMasterRow(this)"><td class="col1">${item['TOOL']}</td><td class="col2">${item['LOCATION']}</td><td class="col3">${item['DRAWER']}</td><td class="col4">${item['STATUS']}</td></tr>`;
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight selected master row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectMasterRow(tr) {
		var trs = tr.parentNode.children;
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectMasterRow(this)');
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmMaster(this)');
	}
	
	/**
	  *	@desc	insert master to job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmMaster(tr) {
		document.getElementById("master-input").value = tr.children[0].innerHTML;
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
	  *	@desc	switch between mm and in
	  *	@param	none
	  *	@return	none
	*/
	function switchUnit() {
		switch(isMetric) {
			case false:
				document.getElementById("unit").innerHTML = "(mm)";
				document.getElementById("unit-button").innerHTML = "English";
				isMetric = true;
				break;
			case true:
				document.getElementById("unit").innerHTML = "(in)";
				document.getElementById("unit-button").innerHTML = "Metric";
				isMetric = false;
				break;
			default:
		}
	}
	
	/**
	  *	@desc	set recut-master or blank field to readOnly
	  *	@param	DOM Object select - value determines result
	  *	@return	none
	*/
	function setWorkType(select) {
		switch(select.value) {
			case "Recut":
				document.getElementById("blank-input").disabled = true;
				document.getElementById("blank-search-button").disabled = true;
				document.getElementById("master-input").disabled = false;
				document.getElementById("master-search-button").disabled = false;
				break;
			default:
				document.getElementById("blank-input").disabled = false;
				document.getElementById("blank-search-button").disabled = false;
				document.getElementById("master-input").disabled = true;
				document.getElementById("master-search-button").disabled = true;
		}
		workType = select.value;
	}
	
	/**
	  *	@desc	determine if design is already part of a job
	  *	@param	string design - name of design
	  *	@return	int (jobs.length) - evaluated as false if no jobs found
	*/
	function isScheduled(design) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Mastering";
		var condition = "TOOL_IN";
		var jobs = [];
		
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
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+design, false);
		conn.send();
		
		return jobs.length;
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("design-input").value == "") {
			msg = "Please enter a valid design";
		} else if (document.getElementById("date-input").value == "") {
			msg = "Please enter a target date";
		} else if (document.getElementById("blank-input").value == "" && document.getElementById("master-input").value == "") {
			msg = "Please enter a valid blank or recut master";
		} else if (document.getElementById("operator-input").value == "") {
			msg = "Please enter your initials";
		} else if (document.getElementById("size-input").value == "") {
			msg = "Size cannot be empty";
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
		if (msg == "" && !isScheduled(document.getElementById("design-input").value)) {
			var d = new Date(document.getElementById("date-input").value);
			batch = {
				BATCH_NUMBER: "",
				OPERATOR: document.getElementById("operator-input").value,
				DATE: formatDate(new Date()),
				TARGET_DATE: formatDate(new Date(document.getElementById("date-input").value)),
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23")
			};
			
			getNextBatchNumber();
			
			var job = {
				BATCH_NUMBER: batch.BATCH_NUMBER,
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				WO_NUMBER: getNextWorkNumber(),
				TOOL_IN: document.getElementById("design-input").value,
				SEQNUM: 1,
				TARGET_DATE: formatDate(new Date(document.getElementById("date-input").value)),
				DATE_IN: formatDate(new Date(d.setDate(d.getDate() - <?= $process['DURATION'] ?>))),
				TOOL_OUT: getNextTool(),
				DATE_OUT: formatDate(new Date(document.getElementById("date-input").value)),
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
				SIZE: document.getElementById("size-input").value,
				TOOL_TYPE: document.getElementById("tool-type-select").value,
				COSMETIC: document.getElementById("cosmetics-select").value,
				WORK_TYPE: document.getElementById("recut-select").value,
				IS_BLANK: document.getElementById("recut-select").value == "Recut" ? "FALSE" : "TRUE",
				BLANK: document.getElementById("recut-select").value == "Recut" ? document.getElementById("master-input").value : document.getElementById("blank-input").value
			};
			
			var msg = "";
			
			for (var attr in job) {
				if (job[attr] == "NaN/NaN/NaN") {
					msg = "Invalid date";
				}
			}
			
			if (msg == "") {
				var conn1 = new XMLHttpRequest();
				var table1 = "Batches";
				var action1 = "insert";
				var conn2 = new XMLHttpRequest();
				var table2 = "Mastering";
				var action2 = "insert";
				
				conn1.onreadystatechange = function() {
					if (conn1.readyState == 4 && conn1.status == 200) {
						if (conn1.responseText.includes("Insert succeeded.")) {
							var query2 = "";
							
							conn2.onreadystatechange = function() {
								if (conn2.readyState == 4 && conn2.status == 200) {
									if (conn2.responseText.includes("Insert succeeded")) {
										addTool(job);
									} else {
										alert("Batch created, but job not entered. Contact support to correct. " + conn2.responseText);
									}
								}
							}
							
							Object.keys(job).forEach((item, index, array) => {
								query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							})
							
							conn2.open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
							conn2.send();
						} else {
							alert("Batch not created. Contact IT Support to correct. " + conn1.responseText);
						}
					}
				}
				
				var query1 = "";
				
				Object.keys(batch).forEach((item, index, array) => {
					query1 += `&${item}=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				});
				
				conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
				conn1.send();
			} else {
				alert(msg);
			}
		} else {
			alert(msg || "Tool already scheduled");
		}
	}
	
	/**
	  *	@desc	reserve new tool name
	  *	@param	array job - job data
	  *	@return	none
	*/
	function addTool(job) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "insert";
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					alert("Job added");
					window.location.replace("mastering.php");
				} else {
					alert("Job and batch added, but tool not successfully inserted to tree. Contact support to correct. " + conn.responseText);
				}
			}
		}
		
		var tool = {
			MANDREL: job.TOOL_IN,
			BLANK: job.BLANK,
			TOOL: job.TOOL_OUT,
			LEVEL: 1,
			STATUS: "GOOD",
			STATUS_DATE: formatDate(new Date()),
			MASTER_SIZE: job.SIZE,
		}
		
		var query = "";
		
		Object.keys(tool).forEach((item, index, array) => {
			query += `&${item}=${tool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
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
				var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of result) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				
				batch.BATCH_NUMBER = parseInt(result[0]['BATCH_NUMBER']) + 1;
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
	  *	@desc	get next available tool name for chosen design
	  *	@param	none
	  *	@return	string newTool - new tool name for design
	*/
	function getNextTool() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "TOOL";
		var value = document.getElementById("design-input").value + "-[A-Z]";
		var max = "A";
		
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
					response.forEach((item, index, array) => {
						newMaster = item['TOOL'].split("-")[item['TOOL'].split("-").length-1];
						if (newMaster > max) {
							max = newMaster;
						}
					});
					
					max = getNextKey(max);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value,false);
		conn.send();
		
		return document.getElementById("design-input").value + "-" + max;
	}
	
	/**
	  *	@desc	increments letter to next letter: a to b, A to B, z to aa, Z to AA
	  *	@param	none
	  *	@return	none
	*/
	function getNextKey(key) {
	  	if (key === 'Z' || key === 'z') {
	    	return String.fromCharCode(key.charCodeAt() - 25) + String.fromCharCode(key.charCodeAt() - 25);
	  	} else {
	    	var lastChar = key.slice(-1);
	    	var sub = key.slice(0, -1);
	    	if (lastChar === 'Z' || lastChar === 'z') {
	      		return getNextKey(sub) + String.fromCharCode(lastChar.charCodeAt() - 25);
	    	} else {
	      		return sub + String.fromCharCode(lastChar.charCodeAt() + 1);
			}
		}
		
		return key;
	};
	
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
	  *	@desc	create/display details of design
	  *	@param	none
	  *	@return	none
	*/
	function designDetails() {
		var design = document.getElementById("design-input").value;
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html = `<span class="close" id="close">&times;</span>`;
		if (design == "") {
			alert("Please enter a design first.");
		} else {
			for(var i=0;i<designs.length;i++) {
				if (designs[i]['DESIGN'] == design) {
					var conn = new XMLHttpRequest();
					var table = "Designs";
					var action = "select";
					var condition = "ID";
					var value = designs[i]['ID'];
					
					conn.onreadystatechange = function() {
						if (conn.readyState == 4 & conn.status == 200) {
							var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
							for (let design of result) {
								for (let x in design) {
									if (design[x] !== null && typeof design[x] == 'object') {
										design[x] = formatDate(new Date(design[x]['date']));
									}
									if (design[x] == null) {
										design[x] = '';
									}
								}
							}
							
							html += `<div style="display: inline-block;">
								<div style="display: inline-block;">
									<div style="display: inline-block;">
										<span style="margin-left: 78px;">Design<input type="text" id="design" readonly value="${result[0]['DESIGN']}">
										Designer<input type="text" id="designer" readonly value="${result[0]['OPERATOR']}">
										Date<input type="text" id="date" readonly value="${result[0]['DATE']}">
										Drawing #<input type="text" id="drawing" readonly value="${result[0]['DRAWING']}"></span>
									</div><br>
									<div style="display: inline-block;">
										<span style="margin-left: 58px;">File Name<input type="text" id="file" readonly value="${result[0]['FILENAME']}"></span><br>
										<div style="display: inline-block; text-align: right;">
											Fresnel Conjugate<input type="text" id="fresnel" readonly value="${result[0]['FRESNEL_CONJUGATE']}"><span class="units">(in)</span><br>
											Plano Conjugate<input type="text" id="plano" readonly value="${result[0]['PLANO_CONJUGATE']}"><span class="units">(in)</span><br>
											Focal Length<input type="text" id="focal" readonly value="${result[0]['FOCAL_LENGTH']}"><span class="units">(in)</span><br>
											Number of Grooves<input type="text" id="grooves" style="margin-right: 20px;" readonly value="${result[0]['GROOVES']}"><br>
											Master Pitch<input type="text" id="pitch" readonly value="${result[0]['MASTER_PITCH']}"><span class="units">(in)</span><br>
											Radius<input type="text" id="radius" readonly value="${result[0]['RADIUS']}"><span class="units">(in)</span><br>
											Lens Diameter<input type="text" id="diameter" readonly value="${result[0]['LENS_DIAMETER']}"><span class="units">(in)</span>
										</div>
										<div style="display: inline-block; text-align: right;">
											Maximum Slope<input type="text" id="slope" readonly value="${result[0]['MAX_SLOPE']}"><input type="text" id="slope2" readonly value="${result[0]['MAX_SLOPE'] ? (parseInt(result[0]['MAX_SLOPE'].split(':')[0]) + (parseInt(result[0]['MAX_SLOPE'].split(':')[1])/60) + (parseInt(result[0]['MAX_SLOPE'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
											Maximum Draft<input type="text" id="max-draft" readonly value="${result[0]['MAX_DRAFT']}"><input type="text" id="max-draft2" readonly value="${result[0]['MAX_DRAFT'] ? (parseInt(result[0]['MAX_DRAFT'].split(':')[0]) + (parseInt(result[0]['MAX_DRAFT'].split(':')[1])/60) + (parseInt(result[0]['MAX_DRAFT'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
											Angle of Diamo. Tool<input type="text" id="tool-angle" readonly value="${result[0]['DIAMO_ANGLE']}"><input type="text" id="tool-angle2" readonly value="${result[0]['TOOL_ANGLE'] ? (parseInt(result[0]['TOOL_ANGLE'].split(':')[0]) + (parseInt(result[0]['TOOL_ANGLE'].split(':')[1])/60) + (parseInt(result[0]['TOOL_ANGLE'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
											Maximum Groove Depth<input type="text" id="max-depth" readonly value="${result[0]['MAX_GROOVE_DEPTH']}"><span class="units" style="margin-right: 89px;">(in)</span><br>
											Minimum Draft<input type="text" id="min-draft" readonly value="${result[0]['MIN_DRAFT']}"><input type="text" id="min-draft2" readonly value="${result[0]['MIN_DRAFT'] ? (parseInt(result[0]['MIN_DRAFT'].split(':')[0]) + (parseInt(result[0]['MIN_DRAFT'].split(':')[1])/60) + (parseInt(result[0]['MIN_DRAFT'].split(':')[2])/3600)).toFixed(5) : ''}"><br>
											Prism Angle<input type="text" id="prism" readonly value="${result[0]['PRISM_ANGLE']}"><input type="text" id="prism2" readonly value="${result[0]['PRISM_ANGLE'] ? (parseInt(result[0]['PRISM_ANGLE'].split(':')[0]) + (parseInt(result[0]['PRISM_ANGLE'].split(':')[1])/60) + (parseInt(result[0]['PRISM_ANGLE'].split(':')[2])/3600)).toFixed(5) : ''}">
										</div>
									</div>
								</div>
							</div><br>
							<div style="display: inline-block;">
								<span style="margin-left: 45px;">Prism Depth<input type="text" id="prism-depth" readonly value="${result[0]['PRISM_DEPTH']}">(in)</span>
								<span style="margin-left: 94px;">Tilt Angle<input type="text" id="tilt-angle" readonly value="${result[0]['TILT_ANGLE']}"><input type="text" id="tilt-angle2" readonly value="${result[0]['TILT_ANGLE'] ? (parseInt(result[0]['TILT_ANGLE'].split(':')[0]) + (parseInt(result[0]['TILT_ANGLE'].split(':')[1])/60) + (parseInt(result[0]['TILT_ANGLE'].split(':')[2])/3600)).toFixed(5) : ''}"></span><br><br>
								<div style="display: inline-block; vertical-align: top;">
									<span style="position: relative; top: 3px;">Pitch</span><br>
									<span style="position: relative; top: 10px;">Groove Angle</span><br>
									<span style="position: relative; top: 17px;">Base Angle</span><br>
								</div>
								<div style="display: inline-block; width: 220px;">
									<input type="text" id="pitch1" readonly value="${result[0]['PITCH1']}"><span class="units">(in)</span>
									<input type="text" id="groove-angle1-1" readonly value="${result[0]['GROOVE_ANGLE1']}"><input type="text" id="groove-angle1-2" readonly value="${result[0]['GROOVE_ANGLE1'] ? (parseInt(result[0]['GROOVE_ANGLE1'].split(':')[0]) + (parseInt(result[0]['GROOVE_ANGLE1'].split(':')[1])/60) + (parseInt(result[0]['GROOVE_ANGLE1'].split(':')[2])/3600)).toFixed(5) : ''}">
									<input type="text" id="base-angle1-1" readonly value="${result[0]['BASE_ANGLE1']}"><input type="text" id="base-angle1-2" readonly value="${result[0]['BASE_ANGLE1'] ? (parseInt(result[0]['BASE_ANGLE1'].split(':')[0]) + (parseInt(result[0]['BASE_ANGLE1'].split(':')[1])/60) + (parseInt(result[0]['BASE_ANGLE1'].split(':')[2])/3600)).toFixed(5) : ''}">
								</div>
								<div style="display: inline-block; width: 220px;">
									<input type="text" id="pitch2" readonly value="${result[0]['PITCH2']}"><span class="units">(in)</span>
									<input type="text" id="groove-angle2-1" readonly value="${result[0]['GROOVE_ANGLE2']}"><input type="text" id="groove-angle2-2" readonly value="${result[0]['GROOVE_ANGLE2'] ? (parseInt(result[0]['GROOVE_ANGLE2'].split(':')[0]) + (parseInt(result[0]['GROOVE_ANGLE2'].split(':')[1])/60) + (parseInt(result[0]['GROOVE_ANGLE2'].split(':')[2])/3600)).toFixed(5) : ''}">
									<input type="text" id="base-angle2-1" readonly value="${result[0]['BASE_ANGLE2']}"><input type="text" id="base-angle2-2" readonly value="${result[0]['BASE_ANGLE2'] ? (parseInt(result[0]['BASE_ANGLE2'].split(':')[0]) + (parseInt(result[0]['BASE_ANGLE2'].split(':')[1])/60) + (parseInt(result[0]['BASE_ANGLE2'].split(':')[2])/3600)).toFixed(5) : ''}">
								</div>
								<div style="display: inline-block; width: 220px;">
									<input type="text" id="pitch3" readonly value="${result[0]['PITCH3']}"><span class="units">(in)</span>
									<input type="text" id="groove-angle3-1" readonly value="${result[0]['GROOVE_ANGLE3']}"><input type="text" id="groove-angle3-2" readonly value="${result[0]['GROOVE_ANGLE3'] ? (parseInt(result[0]['GROOVE_ANGLE3'].split(':')[0]) + (parseInt(result[0]['GROOVE_ANGLE3'].split(':')[1])/60) + (parseInt(result[0]['GROOVE_ANGLE3'].split(':')[2])/3600)).toFixed(5) : ''}">
									<input type="text" id="base-angle3-1" readonly value="${result[0]['BASE_ANGLE3']}"><input type="text" id="base-angle3-2" readonly value="${result[0]['BASE_ANGLE3'] ? (parseInt(result[0]['BASE_ANGLE3'].split(':')[0]) + (parseInt(result[0]['BASE_ANGLE3'].split(':')[1])/60) + (parseInt(result[0]['BASE_ANGLE3'].split(':')[2])/3600)).toFixed(5) : ''}">
								</div><br><br>
								<span style="margin-left: 64px; vertical-align: top;">Features:</span><textarea rows="4" cols="80" style="margin-left: 5px;" id="features-textarea" readonly>${result[0]['FEATURES']}</textarea>
							</div>
							<div style="display: inline-block;">
								<span style="margin-left: 52px; vertical-align: top;">Comments:</span><textarea rows="4" cols="80" style="margin: 3px 4px;" id="comment-textarea" readonly>${result[0]['COMMENT']}</textarea>
							</div>`;
							
							modalContent.innerHTML = html;
							closeForm();
						}
					}
					
					conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value,true);
					conn.send();
				}
			}
			
			modal.style.display = "block";
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
</script>
<html>
	<head>
		<title>New Mastering Job</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/addmastering.css">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
					<span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input" readonly></span><br>
					<span id="design-span">Design<input type="text" id="design-input"></span><button onclick="popDesignList()" id="design-search-button">Search</button><button onclick="designDetails()" style="margin-left: 5px;">Design Info</button><br>
					<span id="drawing-span">Drawing<input type="text" id="drawing-input" readonly></span><span id="file-span">File Name<input type="text" id="file-input" readonly></span><br>
					<span id="date-span">Target Date<input onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="mastering.php">Back</a>
				</div>
				<div class="design-info">
					<span id="recut-span"><select id="recut-select" oninput="setWorkType(this)">
						<option value="New">New</option>
						<option value="Recut">Recut</option>
						<option value="Reuse">Reuse</option>
					</select></span><br>
					<span id="blank-span">Blank<input type="text" id="blank-input"></span><button onclick="popBlankList()" id="blank-search-button">Search</button><br>
					<span id="master-span">Recut Master<input type="text" id="master-input" disabled></span><button onclick="popMasterList()" id="master-search-button" disabled>Search</button><br>
					<span id="size-span">Size<input type="text" id="size-input"></span><span id="unit">(in)</span><br>
					<span id="tool-type-span">Type<select id="tool-type-select">
						<?php foreach($toolTypes as $toolType) { ?>
							<option value="<?= $toolType['TOOLTYPE'] ?>"><?= $toolType['TOOLTYPE'] ?></option>
						<?php } ?>
					</select></span><br>
					<span id="cosmetics-span">Cosmetics<select id="cosmetics-select">
						<?php foreach($cosmetics as $cosmetic) { ?>
							<option value="<?= $cosmetic['COSMETIC'] ?>"><?= $cosmetic['COSMETIC'] ?></option>
						<?php } ?>
					</select></span>
					<button onclick="switchUnit()" id="unit-button">Metric</button>
				</div><br>
				<span id="special-span">Special Instructions</span><br><textarea rows="4" cols="68" id="special-textarea"></textarea>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>
