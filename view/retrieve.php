<!DOCTYPE html>
<?php
/**
  * @desc Retrieve Tool page, options for viewing, altering, and adding tools
*/
	require_once("../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//handle post data
	if (!isset($_POST['returnpath'])) {
		$_POST['returnpath'] = "/view/home.php";
	}
	
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of data to select from when altering tools
	$locations = array();
	$statuses = array();
	$defects = array();
	$types = array();
	$toolNames = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Inv_Locations ORDER BY LOCATION ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Tool_Status ORDER BY STATUS ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT, STATUS FROM Valid_Defects ORDER BY DEFECT ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT * FROM Customer_Tool_Types ORDER BY TYPE ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$types[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL, STATUS, REASON, LOCATION, DRAWER, BELT_NUMBER FROM Tool_Tree ORDER BY TOOL ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$toolNames[] = $row;
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
	var current = 0;
	var isMetric = true;
	var selectedRow = 0;
	var selectedJobRow = 0;
	var currentItem;
	var currentTool = "";
	var comments;
	var statusHistory;
	var currentComment = 0;
	var jobs = [];
	var toolNames = [<?php
		foreach($toolNames as $toolName) {
			echo '{';
			foreach($toolName as $key=>$value) {
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
	  *	@desc	if we got here from a specific tool, find that tool first
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?=$_POST['tool']?>" != "") {
			document.getElementById("tool-header").innerHTML = "<?=$_POST['tool']?>";
			popSearchForm();
			setTimeout(function() {
				document.getElementById("tbody").children[0].click();
				document.getElementById("tbody").children[0].click();
			}, 200);
		}
	}
	
	/**
	  *	@desc	find additional tool data when selected from list
	  *	@param	int i - array ID of tool
	  *	@return	none
	*/
	function find(i) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Tree";
		var id = i;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Selection failed")) {
					alert(conn.responseText);
				} else {
					var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
					for (let job of result) {
						for (let x in job) {
							if (job[x] !== null && typeof job[x] == 'object') {
								job[x] = formatDate(new Date(job[x]['date']));
							}
						}
					}
					
					currentItem = result[0];
					currentTool = result[0]['TOOL'];
					
					document.getElementById("close").click();
					
					document.getElementById("tool-header").innerHTML = result[0]['TOOL'];
					document.getElementById("diameter-input").value = result[0]['MASTER_SIZE'];
					document.getElementById("location-select").value = result[0]['LOCATION'];
					document.getElementById("drawer-input").value = result[0]['DRAWER'];
					document.getElementById("status-select").value = result[0]['STATUS'];
					document.getElementById("defect-select").value = result[0]['REASON'];
					document.getElementById("created-input").value = result[0]['DATE_CREATED'] = '' ? '' : formatDate(new Date(result[0]['DATE_CREATED']));
					document.getElementById("tooltype-select").value = result[0]['TOOL_TYPE'];
					document.getElementById("thickness1").value = result[0]['THICKNESS1'];
					document.getElementById("thickness2").value = result[0]['THICKNESS2'];
					document.getElementById("thickness3").value = result[0]['THICKNESS3'];
					document.getElementById("thickness4").value = result[0]['THICKNESS4'];
					document.getElementById("thickness5").value = result[0]['THICKNESS5'];
					document.getElementById("thickness6").value = result[0]['THICKNESS6'];
					document.getElementById("brightness1").value = result[0]['BRIGHTNESS1'];
					document.getElementById("brightness2").value = result[0]['BRIGHTNESS2'];
					document.getElementById("brightness3").value = result[0]['BRIGHTNESS3'];
					document.getElementById("save-button").disabled = false;
					
					getJobs();
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition=ID&value="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	show wait message to users
	  *	@param	none
	  *	@return	none
	*/
	function wait() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var table = document.getElementById("table");
		if (table) {
			modalContent.removeChild(table);
		}
		var div = document.createElement('div');
		div.innerHTML = "<h3>Please wait...</h3>";
		div.id = "wait-div";
		if (document.getElementById("search-input").value != '') {
			modalContent.appendChild(div);
			setTimeout(search, 200);
		}
		document.getElementById("search-input").focus();
	}
	
	function popSearchForm() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		
		modalContent.innerHTML = `<span class="close" id="close">&times;</span><input type="text" id="search-input" value="${document.getElementById('tool-header').innerHTML}"><button id="search-form-button" onclick="wait()">Search</button>`;
		modalContent.style.width = "1000px";
		modal.style.display = "block";
		closeForm();
		
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search-form-button").click();
			}
		}
		
		document.getElementById("search-form-button").click();
	}
	
	/**
	  *	@desc	create search form based on value in search field
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var searchText = document.getElementById("search-input").value;
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var waitDiv = document.getElementById("wait-div");
		modalContent.removeChild(waitDiv);
		var id = 0;
		modal.style.display = "block";
		var html = "<table id=\"table\"><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Status</th><th class=\"col3\">Reason</th><th class=\"col4\">Location</th><th class=\"col5\">Drawer</th><th class=\"col6\">Belt #</th></tr></thead><tbody id=\"tbody\">";
		for (var i=0;i<toolNames.length;i++) {
			if (toolNames[i]['TOOL'].toUpperCase().includes(searchText.toUpperCase())) {
				html += "<tr id=\""+toolNames[i]['ID']+"\" onclick=\"selectRow(this)\"><td class=\"col1\">" + toolNames[i]['TOOL'] + "</td><td class=\"col2\">" + toolNames[i]['STATUS'] + "</td><td class=\"col3\">" + toolNames[i]['REASON'] + "</td><td class=\"col4\">" + toolNames[i]['LOCATION'] + "</td><td class=\"col5\">" + toolNames[i]['DRAWER'] + "</td><td class=\"col6\">" + toolNames[i]['BELT_NUMBER'] + "</td></tr>";
			}
		}
		html += "</tbody></table>";
		
		modalContent.innerHTML += html;
		
		document.getElementById("search-input").value = searchText;
		
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search-form-button").click();
			}
		}
		
		selectedRow = 0;
		
		closeForm();
	}
	
	/**
	  *	@desc	if row not already selected then highlight selected row, unhighlight others else find that row and close table
	  *	@param	DOM Object tr - row selected
	  *	@return	none
	*/
	function selectRow(tr) {
		if (selectedRow != parseInt(tr.id)) {
			selectedRow = parseInt(tr.id);
			var trs = document.getElementById("tbody").children;
			for (var i=0;i<trs.length;i++) {
				trs[i].style.backgroundColor = "#fefefe";
				trs[i].style.color = "#000";
			}
			tr.style.backgroundColor = "#000";
			tr.style.color = "#fff";
		} else {
			find(parseInt(tr.id));
		}
	}
	
	/**
	  *	@desc	set onclick functions to close modal
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
	  *	@desc	find tool's past and current work
	  *	@param	none
	  *	@return	none
	*/
	function getJobs() {
		jobs = [];
		var tool = document.getElementById("tool-header").innerHTML;
		var tables = ["Mastering","Toolroom","Electroforming","Mastering_History","Toolroom_History","Electroforming_History","Shipping","Shipping_History"];
		var conn = new XMLHttpRequest();
		var action = "select";
		var value = tool.replace(/[+]/g, "%2B");
		var condition;
		
		conn.onreadystatechange = function () {
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
					var isFound = false;
					jobs.forEach((item2, index2, array2) => {
						if (item2[1] == item['WO_NUMBER']) {
							isFound = true;
						}
					});
					
					if (!isFound) {
						switch(conn.responseURL.split("table=")[1].split("&")[0]) {
							case "Mastering":
							case "Mastering_History":
								var job = [item['DATE_OUT'],item['WO_NUMBER']];
								if (tool == item['TOOL_IN']) {
									job.push("X",item['STATUS_IN'] || "");
								} else {
									job.push("","");
								}
								if (tool == item['TOOL_OUT']) {
									job.push("X",item['STATUS_OUT'] || "");
								} else {
									job.push("","");
								}
								job.push("MASTERING");
								job.push(item['ID']);
								if (item['OPERATOR_IN'] == "") {
									job.push("IN");
								} else if (item['OPERATOR_OUT'] == "") {
									job.push("OUT");
								} else {
									job.push("DONE");
								}
								
								jobs.push(job);
								break;
							case "Toolroom":
							case "Toolroom_History":
								var job = [item['DATE_OUT'],item['WO_NUMBER']];
								if (tool == item['TOOL_IN']) {
									job.push("X",item['STATUS_IN'] || "");
								} else {
									job.push("","");
								}
								if (tool == item['TOOL_OUT']) {
									job.push("X",item['STATUS_OUT'] || "");
								} else {
									job.push("","");
								}
								job.push(item['PROCESS']);
								job.push(item['ID']);
								if (item['OPERATOR_IN'] == "") {
									job.push("IN");
								} else if (item['OPERATOR_OUT'] == "") {
									job.push("OUT");
								} else {
									job.push("DONE");
								}
								
								jobs.push(job);
								break;
							case "Electroforming":
							case "Electroforming_History":
								var job = [item['DATE_OUT'].split(" ")[0],item['WO_NUMBER']];
								if (tool == item['TOOL_IN']) {
									job.push("X",item['STATUS_IN'] || "");
								} else {
									job.push("","");
								}
								if (tool == item['TOOL_OUT']) {
									job.push("X",item['STATUS_OUT'] || "");
								} else {
									job.push("","");
								}
								job.push(item['PROCESS']);
								job.push(item['ID']);
								if (item['OPERATOR_IN'] == "") {
									job.push("IN");
								} else if (item['OPERATOR_OUT'] == "") {
									job.push("OUT");
								} else {
									job.push("DONE");
								}
								
								jobs.push(job);
								break;
							case "Shipping":
							case "Shipping_History":
								var job = [item['SHIP_DATE'],item['WO_NUMBER']];
								if (tool == item['TOOL']) {
									job.push("X",item['STATUS'] || "","X",item['STATUS'] || "");
								} else {
									job.push("","","","");
								}
								job.push("SHIPPING");
								job.push(item['BATCH_NUMBER']);
								if (item['SELECT_OPERATOR'] == "") {
									job.push("IN");
								} else if (item['SHIP_OPERATOR'] == "") {
									job.push("OUT");
								} else {
									job.push("DONE");
								}
								
								jobs.push(job);
								break;
							default:
						}
					}
				});
				
				jobs.sort(function (a, b) {
					date1 = new Date(a[0]);
					date2 = new Date(b[0]);
					
					if (date1 < date2) {
						return -1;
					} else if (date2 > date1) {
						return 1;
					} else {
						return 0;
					}
				});
				
				document.getElementsByClassName("job-list")[1].innerHTML = "";
				
				jobs.forEach((item, index, array) => {
					document.getElementsByClassName("job-list")[1].innerHTML += '<tr onclick="selectJobRow(this)" data-status="'+item[8]+'" id="'+item[7]+'"><td class="col1">'+formatDate(new Date(item[0]))+'</td><td class="col2">'+item[1]+'</td><td class="col3">'+item[2]+'</td><td class="col4">'+item[3]+'</td><td class="col5">'+item[4]+'</td><td class="col6">'+item[5]+'</td><td class="col7">'+(item[6] == "ELECTROFORMING" ? "EFORM" : item[6])+'</td></tr>';
				});
			}
		}
		
		tables.forEach((item, index, array) => {
			if (index < 6) {
				condition = "TOOL_IN";
			} else {
				condition = "TOOL";
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+item+"&condition="+condition+"&value="+value, false);
			conn.send();
		});
		
		tables.forEach((item, index, array) => {
			if (index < 6) {
				condition = "TOOL_OUT";
			
				conn.open("GET","/db_query/sql2.php?action="+action+"&table="+item+"&condition="+condition+"&value="+value, false);
				conn.send();
			}
		});
	}
	
	/**
	  *	@desc	create eforming defaults form
	  *	@param	none
	  *	@return	none
	*/
	function electroformDefaults() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		var html = "<span class=\"close\" id=\"close\">&times;</span>";
		html += "<span style=\"margin-left: 245px;\">OD/L</span><span style=\"margin-left: 55px;\">Width</span><br>";
		html += "<span style=\"display: inline-block; width: 240px;\">Part Size (mm)</span><input type=\"text\" id=\"part-length-input\" value=\""+currentItem['PART_LENGTH']+"\">&times;<input type=\"text\" id=\"part-width-input\" value=\""+currentItem['PART_WIDTH']+"\"><br>";
		html += "<span style=\"display: inline-block; width: 240px;\">Pre-Plating Cycle</span><input type=\"text\" id=\"preplating-cycle-input\" value=\""+currentItem['PREPLATING']+"\"><br>";
		html += "<span style=\"display: inline-block; width: 240px;\">Forming Current Density (A/sq dm)</span><input type=\"text\" id=\"forming-current-input\" value=\""+currentItem['FORMING_CURRENT']+"\"><br>";
		html += "<span style=\"display: inline-block; width: 240px;\">Forming Time (min)</span><input type=\"text\" id=\"forming-time-input\" value=\""+currentItem['FORMING_TIME']+"\"><br>";
		html += "<span style=\"display: inline-block; width: 240px;\">Building Current Density (A/sq dm)</span><input type=\"text\" id=\"building-current-input\" value=\""+currentItem['FORMING_DENSITY']+"\"><br>";
		html += "<span style=\"display: inline-block; width: 240px;\">Target Form Thickness (mm)</span><input type=\"text\" id=\"target-thickness-input\" value=\""+currentItem['TARGET_THICKNESS']+"\">";
		html += "<button style=\"background-color: #000; margin-left: 50px; width: 100px; color: #fff;\" onclick=\"submitElectroformDefaults()\">Submit</button>";
		
		modalContent.innerHTML = html;
		modalContent.style.width = "500px";
		closeForm();
	}
	
	/**
	  *	@desc	save eform defaults changes to currently selected tool
	  *	@param	none
	  *	@return	none
	*/
	function submitElectroformDefaults() {
		currentItem['PART_LENGTH'] = document.getElementById("part-length-input").value;
		currentItem['PART_WIDTH'] = document.getElementById("part-width-input").value;
		currentItem['PREPLATING'] = document.getElementById("preplating-cycle-input").value;
		currentItem['FORMING_CURRENT'] = document.getElementById("forming-current-input").value;
		currentItem['FORMING_TIME'] = document.getElementById("forming-time-input").value;
		currentItem['BUILDING_CURRENT'] = document.getElementById("building-current-input").value;
		currentItem['TARGET_THICKNESS'] = document.getElementById("target-thickness-input").value;
		document.getElementById("close").click();
	}
	
	/**
	  *	@desc	find a tool's status history
	  *	@param	none
	  *	@return	none
	*/
	function pullStatusHistory() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Tool_Status_History";
		var condition = "TOOL";
		var value = currentItem['TOOL'].replace(/[+]/g, "%2B");
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				statusHistory = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let status of statusHistory) {
					for (let x in status) {
						if (status[x] !== null && typeof status[x] == 'object') {
							status[x] = formatDate(new Date(status[x]['date']));
						}
						if (status[x] == null) {
							status[x] = '';
						}
					}
				}
				popStatusHistory();
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, true);
		conn.send();
	}
	
	/**
	  *	@desc	create table of past status changes
	  *	@param	none
	  *	@return	none
	*/
	function popStatusHistory() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		var html = "<span class=\"close\" id=\"close\">&times;</span>";
		html += "<table class=\"status-table\"><thead><tr><th class=\"status-col1\">Date Changed</th><th class=\"status-col2\">Status</th><th class=\"status-col3\">Reason</th><th class=\"status-col4\">Operator</th><th class=\"status-col5\">Process</th><th class=\"status-col6\">Work Order #</th></tr></thead><tbody>";
		for(var i=0;i<statusHistory.length;i++) {
			html += "<tr id=\""+statusHistory[i]['ID']+"\"><td class=\"status-col1\">"+formatDate(new Date(statusHistory[i]['DATE']))+"</td><td class=\"status-col2\">"+statusHistory[i]['STATUS']+"</td><td class=\"status-col3\">"+statusHistory[i]['REASON']+"</td><td class=\"status-col4\">"+statusHistory[i]['OPERATOR']+"</td><td class=\"status-col5\">"+statusHistory[i]['PROCESS']+"</td><td class=\"status-col6\">"+statusHistory[i]['WO_NUMBER']+"</td></tr>";
		}
		html += "</tbody></table>";
		
		modalContent.innerHTML = html;
		modalContent.style.width = "700px";
		closeForm();
	}
	
	/**
	  *	@desc	fetch past comments on tool
	  *	@param	none
	  *	@return	none
	*/
	function pullCommentHistory() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Comment_History";
		var condition = "TOOL";
		var value = currentItem['TOOL'].replace(/[+]/g, "%2B");
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				comments = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let comment of comments) {
					for (let x in comment) {
						if (comment[x] !== null && typeof comment[x] == 'object') {
							comment[x] = formatDate(new Date(comment[x]['date']));
						}
						if (comment[x] == null) {
							comment[x] = '';
						}
					}
				}
				popCommentHistory();
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, true);
		conn.send();
	}
	
	/**
	  *	@desc	create form for viewing and adding comments
	  *	@param	none
	  *	@return	none
	*/
	function popCommentHistory() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		var html = "<span class=\"close\" id=\"close\">&times;</span>";
		html += "<div class=\"comment-left\"><span style=\"margin-left: 19px;\">Tool</span><input type=\"text\" id=\"comment-tool-input\" readonly><br>";
		html += "<span>Process</span><input type=\"text\" id=\"process-input\" readonly><br>";
		html += "<span style=\"margin-left: 18px;\">Date</span><input onkeydown=\"fixDate(this)\" type=\"text\" id=\"date-input\" readonly>";
		html += "<span style=\"margin-left: 5px;\">Operator</span><input onblur=\"this.value = this.value.toUpperCase();\" type=\"text\" id=\"operator-input\" readonly><br>";
		html += "<textarea rows=\"4\" cols=\"43\" style=\"margin-left: 4px;\" id=\"comment-textarea\" readonly></textarea></div>";
		html += "<div class=\"comment-right\"><button id=\"add-comment\" onclick=\"newComment('add')\">Add</button>";
		html += "<button onclick=\"insertFirst()\">First</button>";
		html += "<button id=\"edit-comment\" onclick=\"editComment('edit')\">Edit</button>";
		html += "<button onclick=\"goUp()\">Up</button>";
		html += "<button id=\"delete-comment\" onclick=\"deleteComment()\">Delete</button>";
		html += "<button onclick=\"goDown()\">Down</button>";
		html += "<button onclick=\"searchComment()\">Search</button>";
		html += "<button onclick=\"insertLast()\">Last</button></div>";
		
		modalContent.innerHTML = html;
		modalContent.style.width = "600px";
		closeForm();
		findComment(currentComment);
	}
	
	/**
	  *	@desc	start at first comment
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		findComment(0);
	}
	
	/**
	  *	@desc	go to previous comment
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (currentComment > 0) {
			findComment(currentComment-1);
		}
	}
	
	/**
	  *	@desc	go to next comment
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (currentComment < comments.length-1) {
			findComment(currentComment+1);
		}
	}
	
	/**
	  *	@desc	go to last comment
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		findComment(comments.length-1);
	}
	
	/**
	  *	@desc	find specific comment by id
	  *	@param	none
	  *	@return	none
	*/
	function findComment(i) {
		currentComment = i;
		if (comments.length < 1) {
			document.getElementById("comment-tool-input").value = document.getElementById("tool-header").innerHTML;
		} else {
			document.getElementById("comment-tool-input").value = comments[currentComment]['TOOL'];
			document.getElementById("process-input").value = comments[currentComment]['PROCESS'];
			document.getElementById("date-input").value = formatDate(new Date(comments[currentComment]['DATE']));
			document.getElementById("operator-input").value = comments[currentComment]['OPERATOR'];
			document.getElementById("comment-textarea").value = comments[currentComment]['COMMENT'];
		}
	}
	
	/**
	  *	@desc	clear fields for adding a new comment
	  *	@param	none
	  *	@return	none
	*/
	function newComment() {
		document.getElementById("process-input").value = "";
		document.getElementById("date-input").value = formatDate(new Date());
		document.getElementById("date-input").readOnly = false;
		document.getElementById("operator-input").value = "<?php echo $_SESSION['name']; ?>" == "eform" || "<?php echo $_SESSION['name']; ?>" == "master" || "<?php echo $_SESSION['name']; ?>" == "troom" ? "" : "<?php echo $_SESSION['initials']; ?>";
		document.getElementById("operator-input").readOnly = false;
		document.getElementById("comment-textarea").value = "";
		document.getElementById("comment-textarea").readOnly = false;
		document.getElementById("add-comment").disabled = true;
		document.getElementById("edit-comment").innerHTML = "Save";
		document.getElementById("edit-comment").setAttribute('onclick','saveComment(\'add\')');
		document.getElementById("delete-comment").innerHTML = "Cancel";
		document.getElementById("delete-comment").setAttribute('onclick','cancelComment()');
	}
	
	/**
	  *	@desc	change fields to editable for editing comment
	  *	@param	none
	  *	@return	none
	*/
	function editComment() {
		document.getElementById("comment-textarea").readOnly = false;
		document.getElementById("date-input").readOnly = false;
		document.getElementById("operator-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date());
		document.getElementById("add-comment").disabled = true;
		document.getElementById("edit-comment").innerHTML = "Save";
		document.getElementById("edit-comment").setAttribute('onclick','saveComment(\'edit\')');
		document.getElementById("delete-comment").innerHTML = "Cancel";
		document.getElementById("delete-comment").setAttribute('onclick','cancelComment()');
	}
	
	/**
	  *	@desc	save comment on tool
	  *	@param	none
	  *	@return	none
	*/
	function saveComment(s) {
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Comment_History";
		var id = comments.length < 1 ? '' : comments[currentComment]['ID'];
		var tool = document.getElementById("tool-header").innerHTML.replace(/[+]/g, "%2B");
		var process = document.getElementById("process-input").value;
		var operator = document.getElementById("operator-input").value;
		var comment = document.getElementById("comment-textarea").value.replace(/[#]/g, "%23").replace(/[&]/g, "%26").replace(/\n/g,"%0A");
		var d = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				alert(conn.responseText);
				pullCommentHistory();
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&COMMENT="+comment+"&PROCESS="+process+"&TOOL="+tool+"&DATE="+d+"&OPERATOR="+operator+"&condition=id&value="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	convert date object into formatted string
	  *	@param	Date d - date object to be converted
	  *	@return	string date - MM/DD/YY
	*/
	function formatDate(d) {
		var month, date, year;
		month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		year = d.getFullYear()%100;
		date = month + "/" + date + "/" + year;
		
		return date;
	}
	
	/**
	  *	@desc	change fields to read only, insert current tool
	  *	@param	none
	  *	@return	none
	*/
	function cancelComment() {
		document.getElementById("comment-textarea").readOnly = true;
		document.getElementById("add-comment").disabled = false;
		document.getElementById("edit-comment").innerHTML = "Edit";
		document.getElementById("edit-comment").setAttribute('onclick','editComment()');
		document.getElementById("delete-comment").innerHTML = "Delete";
		document.getElementById("delete-comment").setAttribute('onclick','deleteComment()');
		findComment(currentComment);
	}
	
	/**
	  *	@desc	delete tool comment
	  *	@param	none
	  *	@return	none
	*/
	function deleteComment() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Comment_History";
		var id = comments[currentComment]['ID'];
		currentComment--;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				alert(conn.responseText);
				pullCommentHistory();
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id);
		conn.send();
	}
	
	/**
	  *	@desc	switch units from in to mm or vice versa
	  *	@param	none
	  *	@return	none
	*/
	function switchUnit(button) {
		if (button.innerHTML == "Metric") {
			button.innerHTML = "Standard";
			var fields = document.getElementsByClassName("thickness");
			for (var i=0;i<fields.length;i++) {
				fields[i].value = currentItem[fields[i].id.toUpperCase()];
			}
			document.getElementById("unit").innerHTML = "(mm)";
		} else {
			button.innerHTML = "Metric";
			var fields = document.getElementsByClassName("thickness");
			for (var i=0;i<fields.length;i++) {
				fields[i].value = (fields[i].value / 25.4).toFixed(5);
			}
			document.getElementById("unit").innerHTML = "(in)";
		}
	}
	
	function selectJobRow(tr) {
		var trs = tr.parentNode.children;
		for (var i=0;i<trs.length;i++) {
			trs[i].style.color = "black";
			trs[i].style.backgroundColor = "white";
		}
		
		tr.style.color = "white";
		tr.style.backgroundColor = "black";
		
		selectedJobRow = tr.id;
	}
	
	/**
	  *	@desc	create and submit form to process a tool in to its current process
	  *	@param	none
	  *	@return	none
	*/
	function processIn() {
		var tr = document.getElementById(selectedJobRow);
		switch(tr.children[6].innerHTML) {
			case "MASTERING":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/masteringin.php" method="POST" style="display: none;"><input type="text" value="' + tr.id + '" name="id"><input type="text" name="tool" value="' + document.getElementById("tool-header").innerHTML + '"><input type="text" name="source" value="retrieve.php"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			case "BONDING":
			case "CONVERT":
			case "EDGEFLYCUT":
			case "EDGEGRIND":
			case "FRAMING":
			case "GRINDING-BACKMACHINE":
			case "LASERCUT":
			case "LASERWELD":
			case "LOGO":
			case "METROLOGY":
			case "PARQUET":
			case "VERTICAL MILL-MACHIN":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/toolroomin.php" method="POST" style="display: none;"><input type="text" name="process" value="' + tr.children[6].innerHTML + '"><input type="text" value="' + tr.id + '" name="id"><input type="text" name="tool" value="' + document.getElementById("tool-header").innerHTML + '"><input type="text" name="source" value="retrieve.php"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			case "CLEANING":
			case "ELECTROFORMING":
			case "ELECTROFOR":
			case "EFORM":
			case "NICKEL FLASHING":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/electroformingin.php" method="POST" style="display: none;"><input type="text" name="process" value="' + tr.children[6].innerHTML + '"><input type="text" value="' + tr.id + '" name="id"><input type="text" name="tool" value="' + document.getElementById("tool-header").innerHTML + '"><input type="text" name="source" value="retrieve.php"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			case "SHIPPING":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/shippingin.php" method="POST" style="display: none;"><input type="text" value="' + tr.id + '" name="batch"><input type="text" name="source" value="retrieve.php"><input type="text" name="tool" value="' + document.getElementById('tool-header').innerHTML + '"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			default:
		}
		
		document.getElementById("process-in-form").submit();
	}
	
	/**
	  *	@desc	create and submit form to process a tool out of its current process
	  *	@param	none
	  *	@return	none
	*/
	function processOut() {
		var tr = document.getElementById(selectedJobRow);
		switch(tr.children[6].innerHTML) {
			case "MASTERING":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/masteringout.php" method="POST" style="display: none;"><input type="text" value="' + tr.id + '" name="id"><input type="text" name="tool" value="' + document.getElementById("tool-header").innerHTML + '"><input type="text" name="source" value="retrieve.php"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			case "BONDING":
			case "CONVERT":
			case "EDGEFLYCUT":
			case "EDGEGRIND":
			case "FRAMING":
			case "GRINDING-BACKMACHINE":
			case "LASERCUT":
			case "LASERWELD":
			case "LOGO":
			case "METROLOGY":
			case "PARQUET":
			case "VERTICAL MILL-MACHIN":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/toolroomout.php" method="POST" style="display: none;"><input type="text" name="process" value="' + tr.children[6].innerHTML + '"><input type="text" value="' + tr.id + '" name="id"><input type="text" name="tool" value="' + document.getElementById("tool-header").innerHTML + '"><input type="text" name="source" value="retrieve.php"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			case "CLEANING":
			case "ELECTROFORMING":
			case "ELECTROFOR":
			case "EFORM":
			case "NICKEL FLASHING":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/electroformingout.php" method="POST" style="display: none;"><input type="text" name="process" value="' + tr.children[6].innerHTML + '"><input type="text" value="' + tr.id + '" name="id"><input type="text" name="tool" value="' + document.getElementById("tool-header").innerHTML + '"><input type="text" name="source" value="retrieve.php"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			case "SHIPPING":
				document.getElementsByTagName("body")[0].innerHTML += '<form id="process-in-form" action="/view/operations/shippingout.php" method="POST" style="display: none;"><input type="text" value="' + tr.id + '" name="batch"><input type="text" name="source" value="retrieve.php"><input type="text" name="tool" value="' + document.getElementById('tool-header').innerHTML + '"><input type="text" name="returnpath" value="<?=$_POST['returnpath']?>"></form>';
				break;
			default:
		}
		
		document.getElementById("process-in-form").submit();
	}
	
	/**
	  *	@desc	prep fields for inserting a new tool
	  *	@param	none
	  *	@return	none
	*/
	function newTool() {
		currentItem = {};
		document.getElementById("new-button").innerHTML = "Cancel";
		document.getElementById("new-button").onclick = cancelTool;
		document.getElementById("save-button").onclick = createTool;
		document.getElementById("save-button").disabled = false;
		
		var toolHeader = document.getElementById("tool-header");
		var toolInput = document.createElement('input');
		toolInput.id = "tool-input";
		document.getElementById("search-button").after(toolInput);
		toolHeader.parentNode.removeChild(toolHeader);
		document.getElementById("search-button").disabled = true;
		
		document.getElementById("diameter-input").value = "";
		document.getElementById("drawer-input").value = "";
		document.getElementById("created-input").value = formatDate(new Date());
		document.getElementById("thickness1").value = "";
		document.getElementById("thickness2").value = "";
		document.getElementById("thickness3").value = "";
		document.getElementById("thickness4").value = "";
		document.getElementById("thickness5").value = "";
		document.getElementById("thickness6").value = "";
		document.getElementById("brightness1").value = "";
		document.getElementById("brightness2").value = "";
		document.getElementById("brightness3").value = "";
		document.getElementsByClassName("job-list")[1].innerHTML = "";
		document.getElementById("location-select").value = "<NONE>";
		document.getElementById("status-select").value = "<NONE>";
		document.getElementById("defect-select").value = "<NONE>";
		document.getElementById("tooltype-select").value = "<NONE>";
		document.getElementById("status-button").disabled = true;
		document.getElementById("comment-button").disabled = true;
	}
	
	/**
	  *	@desc	submit new tool
	  *	@param	none
	  *	@return	none
	*/
	function createTool() {
		var conn = new XMLHttpRequest();
		var action = "insert";
		var table = "Tool_Tree";
		var query = "";
		currentItem['TOOL'] = document.getElementById("tool-input").value;
		currentItem['STATUS'] = document.getElementById("status-select").value;
		currentItem['REASON'] = document.getElementById("defect-select").value;
		currentItem['MASTER_SIZE'] = document.getElementById("diameter-input").value;
		currentItem['LOCATION'] = document.getElementById("location-select").value;
		currentItem['DRAWER'] = document.getElementById("drawer-input").value;
		currentItem['THICKNESS1'] = !isMetric ? (parseInt(document.getElementById("thickness1").value) * 0.0393701).toFixed(3) : document.getElementById("thickness1").value;
		currentItem['THICKNESS2'] = !isMetric ? (parseInt(document.getElementById("thickness2").value) * 0.0393701).toFixed(3) : document.getElementById("thickness2").value;
		currentItem['THICKNESS3'] = !isMetric ? (parseInt(document.getElementById("thickness3").value) * 0.0393701).toFixed(3) : document.getElementById("thickness3").value;
		currentItem['THICKNESS4'] = !isMetric ? (parseInt(document.getElementById("thickness4").value) * 0.0393701).toFixed(3) : document.getElementById("thickness4").value;
		currentItem['THICKNESS5'] = !isMetric ? (parseInt(document.getElementById("thickness5").value) * 0.0393701).toFixed(3) : document.getElementById("thickness5").value;
		currentItem['THICKNESS6'] = !isMetric ? (parseInt(document.getElementById("thickness6").value) * 0.0393701).toFixed(3) : document.getElementById("thickness6").value;
		currentItem['BRIGHTNESS1'] = document.getElementById("brightness1").value;
		currentItem['BRIGHTNESS2'] = document.getElementById("brightness2").value;
		currentItem['BRIGHTNESS3'] = document.getElementById("brightness3").value;
		currentItem['TOOL_TYPE'] = document.getElementById("tooltype-select").value;
		currentItem['DATE_CREATED'] = document.getElementById("created-input").value;
		currentItem['OPERATOR'] = "<?=$_SESSION['initials']?>";
		
		Object.keys(currentItem).forEach((item, index, array) => {
			query += `&${item}=${currentItem[item] == null ? 'null' : currentItem[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					alert("Tool added");
					window.location.reload();
				} else {
					alert("Failed to insert tool. Contact IT Support to correct.");
				}
			}
		};
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	cancel tool insert, bring up current tool or clear fields
	  *	@param	none
	  *	@return	none
	*/
	function cancelTool() {
		if (currentTool != "") {
			var toolInput = document.getElementById('tool-input');
			var toolHeader = document.createElement('h4');
			toolHeader.id = 'tool-header';
			document.getElementById("search-button").after(toolHeader);
			toolInput.parentNode.removeChild(toolInput);
			document.getElementById("tool-header").innerHTML = currentTool;
			document.getElementById('search-button').disabled = false;
			document.getElementById('search-button').click();
			setTimeout(function() {
				document.getElementById("tbody").children[0].click();
				document.getElementById("tbody").children[0].click();
			}, 200);
		} else {
			var toolInput = document.getElementById('tool-input');
			var toolHeader = document.createElement('h4');
			toolHeader.id = 'tool-header';
			document.getElementById("search-button").after(toolHeader);
			toolInput.parentNode.removeChild(toolInput);
			document.getElementById('search-button').disabled = false;
			document.getElementById("created-input").value = "";
			document.getElementById("save-button").disabled = true;
		}
		document.getElementById("new-button").innerHTML = "New";
		document.getElementById("new-button").onclick = newTool;
		document.getElementById("save-button").onclick = saveTool;
		document.getElementById("status-button").disabled = false;
		document.getElementById("comment-button").disabled = false;
	}
	
	/**
	  *	@desc	save edited tool
	  *	@param	none
	  *	@return	none
	*/
	function saveTool() {
		var conn = new XMLHttpRequest();
		var action = "update";
		var table = "Tool_Tree";
		var query = "";
		currentItem['MASTER_SIZE'] = document.getElementById("diameter-input").value;
		currentItem['LOCATION'] = document.getElementById("location-select").value;
		currentItem['DRAWER'] = document.getElementById("drawer-input").value;
		currentItem['STATUS'] = document.getElementById("status-select").value;
		currentItem['REASON'] = document.getElementById("defect-select").value;
		currentItem['THICKNESS1'] = !isMetric ? (parseFloat(document.getElementById("thickness1").value) * 0.0393701).toFixed(3) : document.getElementById("thickness1").value;
		currentItem['THICKNESS2'] = !isMetric ? (parseFloat(document.getElementById("thickness2").value) * 0.0393701).toFixed(3) : document.getElementById("thickness2").value;
		currentItem['THICKNESS3'] = !isMetric ? (parseFloat(document.getElementById("thickness3").value) * 0.0393701).toFixed(3) : document.getElementById("thickness3").value;
		currentItem['THICKNESS4'] = !isMetric ? (parseFloat(document.getElementById("thickness4").value) * 0.0393701).toFixed(3) : document.getElementById("thickness4").value;
		currentItem['THICKNESS5'] = !isMetric ? (parseFloat(document.getElementById("thickness5").value) * 0.0393701).toFixed(3) : document.getElementById("thickness5").value;
		currentItem['THICKNESS6'] = !isMetric ? (parseFloat(document.getElementById("thickness6").value) * 0.0393701).toFixed(3) : document.getElementById("thickness6").value;
		currentItem['BRIGHTNESS1'] = document.getElementById("brightness1").value;
		currentItem['BRIGHTNESS2'] = document.getElementById("brightness2").value;
		currentItem['BRIGHTNESS3'] = document.getElementById("brightness3").value;
		
		Object.keys(currentItem).forEach((item, index, array) => {
			if (item != "ID" && currentItem[item] != null) {
				query += `&${item}=${currentItem[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
		});
		
		query += `&condition=id&value=${currentItem['ID']}`;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					if (['PURGED','NOGOOD','RETIRED'].includes(currentItem.STATUS)) {
						abortJobs(document.getElementById('tool-header').innerHTML);
					}
					alert("Tool updated");
					document.getElementsByTagName("body")[0].innerHTML += `<form id="refresh-form" action="retrieve.php" method="POST" style="display: none;">
					<?php foreach($_POST as $name=>$data) { ?>
					<input type="text" name="<?=$name?>" value="<?php if ($name == 'tool') { ?>${document.getElementById("tool-header").innerHTML}<?php } else { echo $data; } ?>">
					<?php } 
					if (!isset($_POST['tool'])) { ?>
					<input type="text" name="tool" value="${document.getElementById('tool-header').innerHTML}">
					<?php } ?>
					</form>`;
					document.getElementById("refresh-form").submit();
				} else {
					alert("Failed to update tool. Contact IT Support to correct.");
				}
			}
		};
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
		conn.send();
	}
	
	/**
	  *	@desc	moves next jobs to abort history
	  *	@param	string tool - tool name to move
	  *	@return	none
	*/
	function abortJobs(tool) {
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
								removeJobs(tool);
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
	function removeJobs(tool) {
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
							if (successes < tables.length) {
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
	  *	@desc	auto-format a date field to fit MM/DD/YY format
	  *	@param	DOM Object input - date field to be formatted
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
	  *	@desc	go back to previous page
	  *	@param	none
	  *	@return	none
	*/
	function goBack() {
		if ("<?=$_POST['returnpath']?>".includes("operations/electroforming")) {
			document.getElementsByTagName("body")[0].innerHTML += `<form id="back-form" action="<?=$_POST['returnpath']?>" method="POST" style="display: none;"><input type="text" name="id" value="<?=$_POST['id']?>"></form>`;
			document.getElementById('back-form').submit();
		} else {
			document.getElementsByTagName("body")[0].innerHTML += `<a href="<?=$_POST['returnpath']?>" id="back-link"></a>`;
			document.getElementById('back-link').click();
		}
	}
</script>
<html>
	<head>
		<title>Retrieve Tool</title>
		<link rel="stylesheet" type="text/css" href="/styles/retrieve.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="main">
					<div class="top-left">
						<button id="search-button" onclick="popSearchForm();">Search</button><h4 id="tool-header"></h4><button id="quality-button">Q</button><br>
						<span id="diameter-span">Master Diameter(mm)<input type="text" id="diameter-input"></span><br>
						<div class="left">
							<span>Location<select id="location-select">
								<option value=""></option>
								<?php
									foreach ($locations as $location) {
										if ($location['STATUS'] == "Active") {
											echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>";
										}
									}
								?>
							</select></span><br>
							<span>Status<select id="status-select">
								<option value=""></option>
								<?php
									foreach ($statuses as $status) {
										if ($status['STATE'] == "Active") {
											echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>";
										}
									}
								?>
							</select></span>
						</div>
						<div class="right">
							<span>Drawer<input type="text" id="drawer-input"></span><br>
							<span>Defect<select id="defect-select">
								<option value=""></option>
								<?php
									foreach($defects as $defect) {
										if ($defect['STATUS'] == "Active") {
											echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>";
										}
									}
								?>
							</select></span>
						</div><br>
						<span id="created-span">Created<input type="text" id="created-input" readonly></span>
						<span>Tool Type<select id="tooltype-select">
								<?php
									foreach ($types as $type) {
										if ($type['STATUS'] == "Active") {
											echo "<option value=\"" . $type['TYPE'] . "\">" . $type['TYPE'] . "</option>";
										}
									}
								?>
						</select></span>
					</div>
					<div class="middle-left">
						<table>
							<thead class="job-list">
								<tr>
									<th class="col1">Date</th>
									<th class="col2">Wo #</th>
									<th class="col3">In</th>
									<th class="col4">Status</th>
									<th class="col5">Out</th>
									<th class="col6">Status</th>
									<th class="col7">Process</th>
								</tr>
							</thead>
							<tbody class="job-list">
							</tbody>
						</table>
					</div>
				</div>
				<div class="controls">
					<button onclick="electroformDefaults()">Electroform Defaults</button>
					<button id="status-button" onclick="pullStatusHistory()">Status History</button>
					<button id="comment-button" onclick="pullCommentHistory()">Comment History</button>
					<button onclick="processIn()">Process In</button>
					<button onclick="processOut()">Process Out</button>
					<?php if (in_array($_SESSION['name'], $admins) || in_array($_SESSION['name'], $schedulers)) { ?>
					<button id="new-button" onclick="newTool()">New</button>
					<?php } ?>
					<button id="save-button" onclick="saveTool()" disabled>Save</button>
					<button onclick="goBack()">Back</a>
				</div>
				<div class="bottom">
					<span>Measurements</span><br>
					<span>Thickness</span><br>
					<span>
						<input type="text" class="thickness" id="thickness1" style="margin-left: 0;">
						<input type="text" class="thickness" id="thickness2">
						<input type="text" class="thickness" id="thickness3">
						<input type="text" class="thickness" id="thickness4">
						<input type="text" class="thickness" id="thickness5">
						<input type="text" class="thickness" id="thickness6" style="margin-right: 3px;"><span id="unit">(mm)</span>
					</span><br>
					<span>Brightness(cd/lux.m2)
						<input type="text" id="brightness1" style="margin-left: 36px;">
						<input type="text" id="brightness2">
						<input type="text" id="brightness3">
					</span>
					<button style="float: right;" onclick="switchUnit(this)">Standard</button>
				</div>
			</div>
		</div>
		<div id="modal" class="modal">
			<div id="modal-content" class="modal-content">
			</div>
		</div>
	</body>
</html>