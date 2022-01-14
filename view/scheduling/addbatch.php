<!DOCTYPE html>
<?php
/**
  *	@desc create new batch
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
	
	//set up sql connection for loading data
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//various lists to choose job values from
	$customers = array();
	$tools = array();
	$tooltypes = array();
	$designs = array();
	$processes = array();
	$locations = array();
	$statuses = array();
	$defects = array();
	$blanks = array();
	$masters = array();
	$processLengths = array();
	$tanks = array();
	$activeJobs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT CUSTOMER, NAME FROM Customers");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$customers[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL_TYPE, LOCATION, DRAWER, STATUS, REASON, TOOL, PART_LENGTH, PART_WIDTH, FORMING_CURRENT, FORMING_TIME, BUILDING_CURRENT, TARGET_THICKNESS FROM Tool_Tree ORDER BY TOOL ASC");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tools[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT TYPE FROM Customer_Tool_Types");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$tooltypes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DESIGN FROM Designs ORDER BY DESIGN ASC");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$designs[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS, DEPARTMENT FROM Processes WHERE STATUS = 'Active' ORDER BY PROCESS ASC");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, LOCATION, STATUS FROM Inv_Locations ORDER BY LOCATION ASC");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$locations[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, STATUS, STATE FROM Tool_Status ORDER BY STATUS ASC");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$statuses[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, DEFECT, STATUS FROM Valid_Defects ORDER BY DEFECT ASC");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$defects[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
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
		
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS, DURATION FROM Processes WHERE STATUS = 'Active';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processLengths[$row['PROCESS']] = $row['DURATION'];
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TANK, STRESS, DATE FROM Tank_Stress ORDER BY TANK;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				if (count($tanks) > 0) {
					$foundTank = false;
					for ($i = 0; $i < count($tanks) ; $i++) {
						if ($tanks[$i]['TANK'] == $row['TANK']) {
							$foundTank = true;
							
							$oldDate = $tanks[$i]['DATE'];
							$newDate = $row['DATE'];
							
							if ($oldDate->diff($newDate)->invert == 0) {
								$tanks[$i] = $row;
							}
						}
					}
					
					if (!$foundTank) {
						$tanks[] = $row;
					}
				} else {
					$tanks[] = $row;
				}
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TANK, STATIONS FROM Valid_Tanks;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				for($i=0;$i<count($tanks);$i++) {
					if ($tanks[$i]['TANK'] == $row['TANK']) {
						$tanks[$i]['STATIONS'] = $row['STATIONS'];
					}
				}
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, TOOL_IN, TOOL_OUT, TANK, STATION, SCHEDULE_TYPE FROM Electroforming;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$activeJobs[] = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
	} else {
		echo "Error: could not connect to database.";
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
	var selectedTool = -1;
	var selectedProcess = -1;
	var selectedTools = [];
	var searchText = "";
	var lastSeqNum = 0;
	var batch = {};
	var jobs = [];
	var workType;
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
	
	var designs = [<?php
		foreach($designs as $design) {
			echo '{';
			foreach($design as $key=>$value) {
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
	
	var processes = [<?php
		foreach($processes as $process) {
			echo '{';
			foreach($process as $key=>$value) {
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
	
	var blanks = [<?php
		foreach($blanks as $blank) {
			echo '{';
			foreach($blank as $key=>$value) {
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
	
	var tanks = [<?php
		foreach($tanks as $tank) {
			echo '{';
			foreach($tank as $key=>$value) {
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
	
	var masters = [<?php
		foreach($masters as $master) {
			echo '{';
			foreach($master as $key=>$value) {
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
	
	var electroJobs = [<?php
		foreach($activeJobs as $job) {
			echo '{';
			foreach($job as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y H:i');
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
	
	var processLengths = {
		<?php $keys = array_keys($processLengths);
		echo "\t";
		foreach($keys as $key) {
			if ($key != "ID") {
				if (gettype($processLengths[$key]) == "string") {
					echo "[\"" . $key . "\"]: `" . $processLengths[$key] . "`,\n\t\t";
				} else if (gettype($processLengths[$key]) == "NULL") {
					echo "[\"" . $key . "\"]: '',\n\t\t";
				} else {
					echo "[\"" . $key . "\"]: " . $processLengths[$key] . ",\n\t\t";
				}
			}
		} ?>
	}
	
	/**
	  *	@desc	insert operator name
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
	}
	
	/**
	  *	@desc	create/display tool search input
	  *	@param	none
	  *	@return	none
	*/
	function popToolSearch() {
		if (hasDesigns() || isMastering()) {
			alert("Cannot schedule tools and designs in the same batch");
		} else {
			var modal = document.getElementById("modal");
			var modalContent = document.getElementById("modal-content");
			modalContent.style.width = "550px";
			modalContent.style.textAlign = "left";
			modal.style.display = "block";
			var html = "<span class=\"close\" id=\"close\">&times;</span><div class=\"tool-search-content\"><input type=\"text\" id=\"tool-search-input\" value=\""+searchText+"\"><br><select id=\"tool-search-select\">";
			html += "<option value=\"\"></option><?php foreach ($tooltypes as $tooltype) { echo "<option value=\\\"" . $tooltype['TYPE'] . "\\\">" . $tooltype['TYPE'] . "</option>"; } ?>";
			html += "</select><span id=\"count-box\"><span># Selected:</span><span id=\"count\">"+selectedTools.length+"</span></span></div><div class=\"tool-search-controls\"><button id=\"wait-button\" onclick=\"wait()\">Search Tools</button><button onclick=\"saveToolList()\">Save Tools</button></div>";
			
			modalContent.innerHTML = html;
			
			document.getElementById("tool-search-input").focus();
			
			document.getElementById("tool-search-input").onkeydown = function(e) {
				if (e.key == "Enter") {
					document.getElementById("wait-button").click();
				}
			}
			
			if (searchText.length > 0) {
				document.getElementById("wait-button").click();
			}
			
			closeForm();
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
		var h3 = document.createElement("h3");
		h3.id = "waiting-label";
		h3.innerHTML = "Please wait...";
		modalContent.appendChild(h3);
		modal.style.display = "block";
		setTimeout(popToolList, 200);
	}
	
	/**
	  *	@desc	create/display list of tools
	  *	@param	none
	  *	@return	none
	*/
	function popToolList() {
		searchText = document.getElementById("tool-search-input").value;
		var searchType = document.getElementById("tool-search-select").value;
		var modalContent = document.getElementById("modal-content");
		modalContent.removeChild(document.getElementById("waiting-label"));
		var html = "<table class=\"tool-search-table\"><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Status</th></tr></thead><tbody id=\"tool-search-tbody\">";
		for (var i=0;i<tools.length;i++) {
			if (tools[i]['TOOL'].toUpperCase().includes(searchText.toUpperCase()) && (tools[i]['TOOL_TYPE'] == searchType || searchType == "")) {
				if (selectedTools.length > 0) {
					var found = false;
					for(var j=0;j<selectedTools.length;j++) {
						if (tools[i]['ID'] == selectedTools[j]['ID']) {
							found = true;
						}
					}
					
					if (found) {
						html += "<tr style=\"background-color: black; color: white;\" id=\""+tools[i]['ID']+"\" onclick=\"selectSearchRow(this)\"><td class=\"col1\">" + tools[i]['TOOL'] + "</td><td class=\"col2\">" + tools[i]['STATUS'] + "</td></tr>";
					} else {
						html += "<tr id=\""+tools[i]['ID']+"\" onclick=\"selectSearchRow(this)\"><td class=\"col1\">" + tools[i]['TOOL'] + "</td><td class=\"col2\">" + tools[i]['STATUS'] + "</td></tr>";
					}
				} else {
					html += "<tr id=\""+tools[i]['ID']+"\" onclick=\"selectSearchRow(this)\"><td class=\"col1\">" + tools[i]['TOOL'] + "</td><td class=\"col2\">" + tools[i]['STATUS'] + "</td></tr>";
				}
			}
		}
		html += "</tbody></table>";
		
		try {
			modalContent.removeChild(modalContent.getElementsByTagName("TABLE")[0]);
		} catch {
			
		}
		modalContent.innerHTML += html;
		closeForm();
	}
	
	/**
	  *	@desc	highlight tool/design search row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectSearchRow(tr) {
		if (tr.style.backgroundColor == "black") {
			tr.style.backgroundColor = "white";
			tr.style.color = "black";
			document.getElementById("count").innerHTML = parseInt(document.getElementById("count").innerHTML) - 1;
		} else {
			tr.style.backgroundColor = "black";
			tr.style.color = "white";
			document.getElementById("count").innerHTML = parseInt(document.getElementById("count").innerHTML) + 1;
		}
		
		if (["NOGOOD","RETIRED","PURGED"].includes(tr.children[1].innerHTML)) {
			setTimeout(function(){
				tr.style.backgroundColor = "white";
				tr.style.color = "black";
				document.getElementById("count").innerHTML = parseInt(document.getElementById("count").innerHTML) - 1;
			}, 200);
			
		}
	}
	
	/**
	  *	@desc	save currently highlighted tools to batch
	  *	@param	none
	  *	@return	none
	*/
	function saveToolList() {
		var trs = document.getElementById("tool-search-tbody").children;
		var toolList = document.getElementById("tool-tbody");
		selectedTools = [];
		var hasPending = false;
		for (var i=0;i<trs.length;i++) {
			if (trs[i].style.backgroundColor == "black") {
				var alreadyExists = false;
				var tools = toolList.children;
				for (var j=0;j<tools.length;j++) {
					if (tools[j].children[0].innerHTML == trs[i].children[0].innerHTML) {
						alreadyExists = true;
					}
				}
				if (!alreadyExists) {
					selectedTools.push([trs[i].id,trs[i].children[0].innerHTML]);
				}
				if (trs[i].children[1].innerHTML == "PENDING") {
					hasPending = true;
				}
			}
		}
		
		var modalContent = document.getElementById('modal-content');
		var html = "<span class=\"close\" id=\"close\">&times;</span>";
		if (hasPending) {
			html += "<p>Some of the tools you selected are pending. Keep selection?</p>";
			html += "<button id=\"keep-button\">Yes</button><button id=\"discard-button\">No</button>";
			modalContent.innerHTML = html;
			closeForm();
		
			document.getElementById("keep-button").onclick = function() {
				searchText = "";
				for(var i=0;i<selectedTools.length;i++) {
					toolList.innerHTML += "<tr data-tool-type=\"tool\" id=\""+selectedTools[i][0]+"\" onclick=\"selectToolRow(this)\"><td>"+selectedTools[i][1]+"</td></tr>";
				}
				if (selectedTool == -1) {
					selectedTool = selectedTools[0][0];
				}
				document.getElementById("close").click();
				selectedTools = [];
				createJobs();
			};
			
			document.getElementById("discard-button").onclick = function() {
				popToolSearch();
			};
		} else {
			searchText = "";
			for(var i=0;i<selectedTools.length;i++) {
				toolList.innerHTML += "<tr data-tool-type=\"tool\" id=\""+selectedTools[i][0]+"\" onclick=\"selectToolRow(this)\"><td>"+selectedTools[i][1]+"</td></tr>";
			}
			if (selectedTool == -1) {
				selectedTool = selectedTools[0][0];
			}
			document.getElementById("close").click();
			selectedTools = [];
			createJobs();
		}
	}
	
	/**
	  *	@desc	create/display design search field
	  *	@param	none
	  *	@return	none
	*/
	function popDesignSearch() {
		if (hasTools() || isNotMastering()) {
			alert("Cannot schedule tools and designs in the same batch");
		} else {
			var modal = document.getElementById("modal");
			var modalContent = document.getElementById("modal-content");
			modalContent.style.width = "550px";
			modalContent.style.textAlign = "left";
			modal.style.display = "block";
			var html = "<span class=\"close\" id=\"close\">&times;</span><div class=\"design-search-content\"><input type=\"text\" id=\"design-search-input\"><br></div><div class=\"design-search-controls\"><button id=\"design-search-button\" onclick=\"popDesignList()\">Search Designs</button><button onclick=\"saveDesignList()\">Save Designs</button></div>";
			
			modalContent.innerHTML = html;
			
			document.getElementById("design-search-input").focus();
			
			document.getElementById("design-search-input").onkeydown = function(e) {
				if (e.key == "Enter") {
					document.getElementById("design-search-button").click();
				}
			}
			
			closeForm();
		}
	}
	
	/**
	  *	@desc	create/display list of designs
	  *	@param	none
	  *	@return	none
	*/
	function popDesignList() {
		var searchText = document.getElementById("design-search-input").value;
		var modalContent = document.getElementById("modal-content");
		var html = "<table class=\"design-search-table\"><thead><tr><th class=\"col1\">Design</th></tr></thead><tbody id=\"design-search-tbody\">";
		for (var i=0;i<designs.length;i++) {
			if (designs[i]['DESIGN'].toUpperCase().includes(searchText.toUpperCase())) {
				html += "<tr id=\""+designs[i]['ID']+"\" onclick=\"selectSearchRow(this)\"><td class=\"col1\">" + designs[i]['DESIGN'] + "</td></tr>";
			}
		}
		html += "</tbody></table>";
		
		modalContent.innerHTML += html;
		closeForm();
	}
	
	/**
	  *	@desc	save list of designs to batch
	  *	@param	none
	  *	@return	none
	*/
	function saveDesignList() {
		var trs = document.getElementById("design-search-tbody").children;
		var toolList = document.getElementById("tool-tbody");
		for (var i=0;i<trs.length;i++) {
			if (trs[i].style.backgroundColor == "black") {
				toolList.innerHTML += "<tr data-tool-type=\"design\" id=\""+trs[i].id+"\" onclick=\"selectToolRow(this)\"><td>"+trs[i].children[0].innerHTML+"</td></tr>";
				if (selectedTool == -1) {
					selectedTool = trs[i].id;
				}
			}
		}
		document.getElementById("close").click();
		
		createJobs();
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
	  *	@desc	highlight row in main tool list
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectToolRow(tr) {
		selectedTool = tr.id;
		var tbody = tr.parentNode;
		for (var i=0;i<tbody.children.length;i++) {
			tbody.children[i].style.backgroundColor = "white";
			tbody.children[i].style.color = "black";
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
	}
	
	/**
	  *	@desc	remove tool from batch
	  *	@param	none
	  *	@return	none
	*/
	function deleteTool() {
		var tr = document.getElementById(selectedTool);
		
		var indexesToRemove = [];
		jobs.forEach((item, index, array) => {
			if (item[0] == tr.children[0].innerHTML) {
				indexesToRemove[indexesToRemove.length] = index;
			}
		});
		
		var counter = 0;
		indexesToRemove.forEach((item, index, array) => {
			jobs.splice(item-counter,1);
			counter++;
		});
		
		document.getElementById("tool-tbody").removeChild(document.getElementById(selectedTool));
		selectedTool = -1;
	}
	
	/**
	  *	@desc	create/display list of processes
	  *	@param	none
	  *	@return	none
	*/
	function popProcessList() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modalContent.style.textAlign = "left";
		var html = "<span class=\"close\" id=\"close\">&times;</span><table id=\"process-search-table\"><thead><tr><th class=\"col1\">Process</th><th class=\"col2\">Department</th></tr></thead><tbody id=\"process-search-tbody\">";
		for (var i=0;i<processes.length;i++) {
			html += "<tr id=\""+processes[i]['ID']+"\" onclick=\"selectProcessSearchRow(this)\"><td class=\"col1\">"+processes[i]['PROCESS']+"</td><td class=\"col2\">"+processes[i]['DEPARTMENT']+"</td></tr>";
		}
		html += "</tr></tbody></table>";
		
		modalContent.innerHTML = html;
		modal.style.display = "block";
		modalContent.style.width = "335px";
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight selected process
	  *	@param	DOM Object tr - selected process row
	  *	@return	none
	*/
	function selectProcessSearchRow(tr) {
		var tbody = tr.parentNode;
		for (var i=0;i<tbody.children.length;i++) {
			tbody.children[i].style.backgroundColor = "white";
			tbody.children[i].style.color = "black";
			tbody.children[i].setAttribute('onclick','selectProcessSearchRow(this)');
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		tr.setAttribute('onclick','saveProcess('+tr.id+')');
		
		closeForm();
	}
	
	/**
	  *	@desc	insert process into batch data
	  *	@param	int id - db ID of process
	  *	@return	none
	*/
	function saveProcess(id) {
		var tbody = document.getElementById("process-tbody");
		for (var i=0;i<processes.length;i++) {
			if (processes[i]['ID'] == id) {
				if (((hasDesigns() || isMastering()) && processes[i]['PROCESS'] != "MASTERING") || ((hasTools() || isNotMastering()) && processes[i]['PROCESS'] == "MASTERING")) {
					alert("Cannot schedule tools and designs in the same batch");
				} else {
					tbody.innerHTML += "<tr id=\""+id+"\" onclick=\"selectProcessRow(this)\"><td class=\"col1\">"+processes[i]['PROCESS']+"</td><td class=\"col2\">"+(lastSeqNum+1)+"</td><td class=\"col3\">NO</td></tr>";
					lastSeqNum++;
					if (selectedProcess == -1) {
						selectedProcess = id;
					}
				}
			}
		}
		
		document.getElementById("close").click();
		
		createJobs();
	}
	
	/**
	  *	@desc	determine if tools are scheduled (as opposed to designs)
	  *	@param	none
	  *	@return	true if at least one tool is found, false otherwise
	*/
	function hasTools() {
		var trs = document.getElementById("tool-tbody").children;
		var found = false;
		for (var i=0;i<trs.length;i++) {
			var testReg = /[A-Za-z0-9]*[-]/;
			if (testReg.test(trs[i].children[0].innerHTML)) {
				found = true;
			}
		}
		
		return found;
	}
	
	/**
	  *	@desc	determine if designs are scheduled (as opposed to tools)
	  *	@param	none
	  *	@return	true if at least one design is found, false otherwise
	*/
	function hasDesigns() {
		var trs = document.getElementById("tool-tbody").children;
		var found = false;
		for (var i=0;i<trs.length;i++) {
			var testReg = /[-]/;
			if (!testReg.test(trs[i].children[0].innerHTML)) {
				found = true;
			}
		}
		
		return found;
	}
	
	/**
	  *	@desc	determine if mastering is scheduled
	  *	@param	none
	  *	@return	true if mastering is found in process list, false otherwise
	*/
	function isMastering() {
		var trs = document.getElementById("process-tbody").children;
		var found = false;
		for (var i=0;i<trs.length;i++) {
			if (trs[i].children[0].innerHTML == "MASTERING") {
				found = true;
			}
		}
		
		return found;
	}
	
	/**
	  *	@desc	determine if non-mastering process is scheduled
	  *	@param	none
	  *	@return	true if anything other than mastering is found, false otherwise
	*/
	function isNotMastering() {
		var trs = document.getElementById("process-tbody").children;
		var found = false;
		for (var i=0;i<trs.length;i++) {
			if (trs[i].children[0].innerHTML != "MASTERING") {
				found = true;
			}
		}
		
		return found;
	}
	
	/**
	  *	@desc	createJob handler
	  *	@param	none
	  *	@return	none
	*/
	function createJobs() {
		var processList = document.getElementById("process-tbody").children;
		var toolList = document.getElementById("tool-tbody").children;
		
		for (var i=0;i<processList.length;i++) {
			for (var j=0;j<toolList.length;j++) {
				var found = false;
				for (var k=0;k<jobs.length;k++) {
					if (jobs[k][1] == processList[i].children[0].innerHTML && jobs[k][0] == toolList[j].children[0].innerHTML) {
						found = true;
					}
				}
				if (!found) {
					createJob(processList[i].id, toolList[j].id);
				}
			}
		}
	}
	
	/**
	  *	@desc	creates default job for process/tool combo
	  *	@param	string process - selected process, string tool - selected tool
	  *	@return	none
	*/
	function createJob(process, tool) {
		selectedTool = tool;
		selectedProcess = process;
		document.getElementById("details").click();
		document.getElementById("save").click();
	}
	
	/**
	  *	@desc	highlight process in main process table
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectProcessRow(tr) {
		selectedProcess = tr.id;
		var tbody = tr.parentNode;
		for (var i=0;i<tbody.children.length;i++) {
			tbody.children[i].style.backgroundColor = "white";
			tbody.children[i].style.color = "black";
		}
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
	}
	
	/**
	  *	@desc	shift process back in sequence
	  *	@param	none
	  *	@return	none
	*/
	function moveProcessUp() {
		var tr = document.getElementById(selectedProcess);
		if (tr.previousElementSibling != null) {
			tr.after(tr.previousElementSibling);
		}
		setSeqNum();
		
		jobs.forEach((item, index, array) => {
			if (item[1] == tr.children[0].innerHTML) {
				if ("DATE_IN" in item[2]) {
					delete item[2].TARGET_DATE;
					delete item[2].DATE_IN;
					delete item[2].DATE_OUT;
				} else {
					delete item[2].TARGET_DATE;
					delete item[2].SELECT_DATE;
					delete item[2].SHIP_DATE;
				}
			}
		});
	}
	
	/**
	  *	@desc	shift process forward in sequence
	  *	@param	none
	  *	@return	none
	*/
	function moveProcessDown() {
		var tr = document.getElementById(selectedProcess);
		if (typeof tr.nextElementSibling != 'undefined') {
			tr.nextElementSibling.after(tr);
		}
		setSeqNum();
		
		jobs.forEach((item, index, array) => {
			if (item[1] == tr.children[0].innerHTML) {
				if ("DATE_IN" in item[2]) {
					delete item[2].TARGET_DATE;
					delete item[2].DATE_IN;
					delete item[2].DATE_OUT;
				} else {
					delete item[2].TARGET_DATE;
					delete item[2].SELECT_DATE;
					delete item[2].SHIP_DATE;
				}
			}
		});
	}
	
	/**
	  *	@desc	remove process from schedule
	  *	@param	none
	  *	@return	none
	*/
	function deleteProcess() {
		var tr = document.getElementById(selectedProcess);
	
		var indexesToRemove = [];
		jobs.forEach((item, index, array) => {
			if (item[1] == tr.children[0].innerHTML) {
				indexesToRemove.push(index);
			}
		});
		
		for (var i=indexesToRemove.length-1;i>=0;i--) {
			jobs.splice(indexesToRemove[i],1);
		}
		
		tr.parentNode.removeChild(tr);
		selectedProcess = -1;
		lastSeqNum--;
		setSeqNum();
		
	}
	
	/**
	  *	@desc	set seqnum to proper order
	  *	@param	none
	  *	@return	none
	*/
	function setSeqNum() {
		var tbody = document.getElementById("process-tbody");
		for (var i=0;i<tbody.children.length;i++) {
			tbody.children[i].children[1].innerHTML = (i+1).toString();
		}
	}
	
	/**
	  *	@desc	create/display job details page
	  *	@param	none
	  *	@return	none
	*/
	function editDetails() {
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		modalContent.style.width = "650px";
		modalContent.style.textAlign = "left";
		if (selectedProcess != -1) {
			var process = document.getElementById(selectedProcess).children[0].innerHTML;
		} else {
			process = -1;
		}
		var department = -1, html;
		for (var i=0;i<processes.length;i++) {
			if (processes[i]['PROCESS'] == process) {
				department = processes[i]['DEPARTMENT'];
			}
		}
		var tool = document.getElementById(selectedTool).children[0].innerHTML;
		var partWidth, partLength, formingCurrent, formingTime, buildingCurrent, targetThickness;
		if (department == "ELECTROFOR" && process == "ELECTROFORMING") {
			tools.forEach((item, index, array) => {
				if (tool == item['TOOL']) {
					partLength = item['PART_LENGTH'];
					partWidth = item['PART_WIDTH'];
					formingCurrent = item['FORMING_CURRENT'];
					formingTime = item['FORMING_TIME'];
					buildingCurrent = item['BUILDING_CURRENT'];
					targetThickness = item['TARGET_THICKNESS'];
				}
			});
		}
		var toolType = document.getElementById(selectedTool).dataset.toolType;
		var toolList = document.getElementById("tool-tbody").children;
		var html = "<span class=\"close\" id=\"close\">&times;</span>";
		
		var alreadyExists = false;
		var id;
		jobs.forEach((item, index, array) => {
			if (document.getElementById(selectedTool).children[0].innerHTML == item[0] && document.getElementById(selectedProcess).children[0].innerHTML == item[1]) {
				alreadyExists = true;
				id = index;
			}
		});
		
		if (processIsValid(process, tool)) {
			if (alreadyExists === false) {
				var d = new Date();
				switch (department) {
					case "MASTERING":
						if (toolType == "design") {
							html += `<div class="basic-info">
										<span style="margin-left: 2px;">PO Number<input type="text" id="po-details-input" style="width: 75px;" value="${document.getElementById('po-input').value}"></span>
										<span>Job Number<input type="text" id="job-details-input" style="width: 75px;" value="${document.getElementById('job-input').value}"></span><span style="margin-left: 5px;">WO Number<input type="text" id="wo-details-input" style="width: 75px;" readonly></span><br>
										<span style="margin-left: 32px;">Design<input type="text" id="design-details-input" value="${tool}" style="width: 75px;"></span><button onclick="goToDesign()">Design Info</button><br>
										<span style="margin-left: 21px;">Operator<input type="text" id="operator-details-input" style="width: 75px;" value="${document.getElementById('operator-input').value}"></span><span style="margin-left: 4px;">Target Date<input onkeydown="fixDate(this)" type="text" id="target-date-details-input" style="width: 75px;" value="${formatDate(new Date(d.setDate(d.getDate+2)))}"></span><br>
									</div><div class="details-controls"><button id="save" onclick="saveDetails('MASTERING')" style="width: 60px; float: right;">Save</button></div><div class="details">
										<select onchange="setWorkType(this)" id="work-type-details-select" style="margin-left: 161px; margin-bottom: 3px;">
											<option value="New">New</option>
											<option value="Re-Cut">Re-Cut</option>
											<option value="Re-Use">Re-Use</option>
										</select><br>
										<button onclick="popBlankList()" style="width: 60px;">Search</button><span style="margin-left: 61px;">Blank<input type="text" id="blank-details-input" style="width: 200px;"></span><br>
										<button onclick="popMasterList()" style="width: 60px;">Search</button><span style="margin-left: 5px;">Re-Cut Master<input disabled type="text" id="master-details-input" style="width: 200px;"></span><br>
										<span style="margin-left: 129px;">Size<input type="text" id="size-details-input"></span><span id="unit" style="margin-left: 5px;">(in)</span><br>
										<select id="tool-type-details-select" style="margin-left: 161px; margin-bottom: 3px;">
											<option value="Hard">Hard</option>
											<option value="Soft">Soft</option>
										</select><br><select id="cosmetic-details-select" style="margin-left: 161px; margin-bottom: 3px;">
											<option value="Clear">Clear</option>
											<option value="Low Glare">Low Glare</option>
										</select><button onclick="switchUnit(this)" value="0" style="float: right;">Metric</button></div>
										<span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;"></textarea>`;
						} else {
							html += "Error: Please select a valid design first.";
						}
						break;
					case "TOOLRM":
						if (toolType == "tool") {
							html += `<div class="basic-info">
										<span>Job Number<input type="text" id="job-details-input" style="width: 100px;" value="${document.getElementById('job-input').value}"></span><span style="margin-left: 5px">WO Number<input type="text" id="wo-details-input" style="width: 100px;" readonly></span><br>
										<span style="margin-left: 50px;">Tool<input type="text" id="tool-details-input" value="${tool}" style="width: 292px;"></span><br>
										<span style="margin-left: 13px;">Start Date<input onkeydown="fixDate(this)" type="text" id="start-date-details-input" style="width: 100px;" onblur="fillTarget(this)"></span><span style="margin-left: 9px;">PO Number<input type="text" id="po-details-input" style="width: 100px;" value="${document.getElementById('po-input').value}"></span><br>
										<span style="margin-left: 4px;">Target Date<input onkeydown="fixDate(this)" type="text" id="target-date-details-input" style="width: 100px;" onblur="fillStart(this)"></span><span style="margin-left: 27px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><br>
									</div><button id="save" onclick="saveDetails('TOOLRM')" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><br>
									<span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;"></textarea>`;
						} else {
							html += "Error: please select a valid tool first.";
						}
						break;
					case "ELECTROFOR":
						if (toolType == "tool") {
							switch(process) {
								case "CLEANING":
									html += `<div class="basic-info">
												<span>Job Number<input type="text" id="job-details-input" style="width: 100px;" value="${document.getElementById('job-input').value}"></span><span style="margin-left: 5px;">WO Number<input type="text" id="wo-details-input" style="width: 100px;" readonly></span><br>
												<span style="margin-left: 51px;">Tool<input type="text" id="tool-details-input" value="${tool}" style="width: 291px;"></span><br>
												<span style="margin-left: 3px;">PO Number<input type="text" id="po-details-input" style="width: 100px;" value="${document.getElementById('po-input').value}"></span><br>
												<span style="margin-left: 21px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><span style="margin-left: 53px;">Date<input onkeydown="fixDate(this)" type="text" id="start-date-details-input" style="width: 100px;" value="${formatDate(new Date())}">
											</div><button id="save" onclick="saveDetails('CLEANING')" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><div style="margin-bottom: 3px;" class="details-quality">
												<span style="display: inline-block; margin-left: 24px;">Location<select id="location-details-select" style="height: 21px; margin-left: 5px; width: 104px;">
													<?php foreach ($locations as $location) { if ($location['STATUS'] == "Active") { echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>"; } } ?>
												</select></span><span style="margin-left: 36px; display: inline-block;">Drawer<input type="text" id="drawer-details-input" style="width: 100px;"></span><br>
												<span style="display: inline-block; margin-left: 37px;">Status<select id="status-details-select" style="height: 21px; margin-left: 5px; width: 104px;">
													<?php foreach ($statuses as $status) { if ($status['STATE'] == "Active") { echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>"; } } ?>
												</select></span><span style="margin-left: 41px; display: inline-block;">Defect<select id="defect-details-select" style="height: 21px; margin-left: 5px; width: 104px;">
													<?php foreach ($defects as $defect) { if ($defect['STATUS'] == "Active") { echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>"; } } ?>
												</select></span></div>
											<span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;"></textarea>`;
									break;
								case "ELECTROFORMING":
									html += `<div class="basic-info" style="width: 700px;">
												<span style="margin-left: 48px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><span style="margin-left: 5px;">Job #<input type="text" id="job-details-input" style="width: 100px;" value="${document.getElementById('job-input').value}"></span><span style="margin-left: 5px;">WO #<input type="text" id="wo-details-input" style="width: 100px;" readonly></span><span style="margin-left: 5px;">PO #<input type="text" id="po-details-input" style="width: 100px;" value="${document.getElementById('po-input').value}"></span><br>
												<span style="margin-left: 53px;">Mandrel<input type="text" id="mandrel-details-input" value="${tool}" style="width: 398px;"></span></div>
												<button id="save" onclick="saveDetails('ELECTROFORMING')" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><div style="display: inline-block; vertical-align: top;">
												<span style="margin-left: 19px;">Tank / Station<input type="text" id="tank-details-input" style="width: 40px;"><input type="text" id="station-details-input" style="width: 40px; margin-left: 16px;"><button onclick="popTankList()" style="width: 100px; margin-left: 48px;">Tank Status</button></span><br>
												<span style="margin-left: 16px;">Date / Time In<input onkeydown="fixDate(this)" type="text" id="date-in-details-input" style="width: 120px;" value="${formatDateTime(new Date())}" onblur="fillCurrent()"></span><br>
												<span style="margin-left: 5px;">Date / Time Out<input onkeydown="fixDate(this)" type="text" id="date-out-details-input" style="width: 120px;" value="${formatDateTime(new Date())}" readonly></span><br>
											</div><div style="display: inline-block; width: 300px; margin-left: 5px;">
												<span style="display: inline-block;">Location<br><select id="location-details-select" style="height: 21px;">
													<?php foreach ($locations as $location) { if ($location['STATUS'] == "Active") { echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; margin-left: 5px;">Drawer<br><input type="text" id="drawer-details-input" style="margin-left: 0px; width: 100px;"></span><br>
												<span style="display: inline-block;">Status<br><select id="status-details-select" style="height: 21px;">
													<?php foreach ($statuses as $status) { if ($status['STATE'] == "Active") { echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; margin-left: 5px;">Defect<br><select id="defect-details-select" style="height: 21px;">
													<?php foreach ($defects as $defect) { if ($defect['STATUS'] == "Active") { echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>"; } } ?>
												</select></span><span display: inline-block;>Schedule Type<br><select id="schedule-type-details-select" style="height: 21px;">
													<option value="Single">Single</option>
													<option value="Indefinite">Indefinite</option>
													<option value="Repeat">Repeat</option>
													<option value="Thru Generation">Thru Generation</option></select>
												<input type="number" step="1" value="1" id="repeat-details-input" style="width: 100px;"></div>
											<div style="display: inline-block; width: 360px;">
												<span style="float: right; margin-right: 33px;">Width</span><span style="float: right; margin-right: 35px;">OD/L</span><br>
												<span style="margin-left: 124px;">Part Size (mm)<input type="text" id="part-size-length-details-input" style="width: 48px; margin-right: 5px;" onblur="fillCurrent()" value="${partLength}">&times;<input type="text" id="part-size-width-details-input" style="width: 48px;" onblur="fillCurrent()" value="${partWidth}"></span><br>
												<span>Forming Current Density (A/sq dm)<input type="text" id="forming-density-details-input" style="width: 118px;" onblur="fillCurrent()" value="${formingCurrent}"></span><br>
												<span style="margin-left: 96px;">Forming Time (min)<input type="text" id="forming-time-details-input" style="width: 118px;" onblur="fillCurrent()" value="${formingTime}"></span><br>
												<span style="margin-left: 2px;">Building Current Density (A/sq dm)<input type="text" id="building-density-details-input" style="width: 118px;" onblur="fillCurrent()" value="${buildingCurrent}"></span><br>
												<span style="margin-left: 39px;">Target Form Thickness (mm)<input type="text" id="target-thickness-details-input" style="width: 118px;" onblur="fillCurrent()" value="${targetThickness}"></span>
											</div><div style="display: inline-block; vertical-align: top; margin-top: 15px;">
												<span>Forming Current (Amps)<input type="text" id="forming-current-details-input" style="width: 109px;" readonly></span><br>
												<span style="margin-left: 1px;">Building Current (Amps)<input type="text" id="building-current-details-input" style="width: 109px;" readonly></span><br>
												<span style="margin-left: 9px;">Cycle Time (Hrs, Mins)<input type="text" id="cycle-time-hours-details-input" style="width: 50px;" readonly><input type="text" id="cycle-time-minutes-details-input" style="width: 50px;" readonly></span>
											</div><br><span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;"></textarea>`;
									modalContent.style.width = "800px";
									break;
								case "NICKEL FLASHING":
									html += `<div class="basic-info" style="width: 700px;">
												<span style="margin-left: 8px;">Job Number<input type="text" id="job-details-input" style="width: 100px;" value="${document.getElementById('job-input').value}"></span><span style="margin-left: 5px;">WO #<input type="text" id="wo-details-input" style="width: 100px;" readonly></span><span style="margin-left: 5px;">PO #<input type="text" id="po-details-input" style="width: 100px;" value="${document.getElementById('po-input').value}"></span><br>
												<span style="margin-left: 59px;">Tool<input type="text" id="tool-details-input" value="${tool}" style="width: 250px;"></span><br>
											</div><button id="save" onclick="saveDetails('NICKEL FLASHING');" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><div style="display: inline-block; width: auto; margin-bottom: 5px;">
												<span style="margin-left: 29px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><br>
												<span style="margin-left: 55px;">Date<input onkeydown="fixDate(this)" type="text" id="date-details-input" style="width: 100px;" value="${formatDateTime(new Date())}"></span><br>
												<span>Tank / Station<input type="text" id="tank-details-input" style="width: 44px;"><input type="text" id="station-details-input" style="width: 44px; margin-left: 8px;"></span><br>
												<span style="margin-left: 6px;">Temperature<input type="text" id="temp-details-input" style="width: 100px;"></span><br>
												<span style="margin-left: 18px;">Time (min)<input type="text" id="time-details-input" style="width: 100px;"></span><br>
												<span style="margin-left: 16px;">Passivated<select id="passivated-details-select" style="margin-left: 5px; height: 21px;"><option value="Yes">Yes</option><option value="No">No</option></select>
											</div><div style="display: inline-block; width: 300px; vertical-align: top; margin-left: 45px; margin-top: 25px;">
												<span style="display: inline-block;">Location<br><select id="location-details-select" style="height: 21px; margin-top: 6px;">
													<?php foreach ($locations as $location) { if ($location['STATUS'] == "Active") { echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; vertical-align: top; margin-left: 5px;">Drawer<br><input type="text" id="drawer-details-input" style="margin-left: 0; margin-top: 6px;"></span><span style="display: inline-block; margin-top: 3px;">Status<br><select id="status-details-select" style="height: 21px; margin-top: 6px;">
													<?php foreach ($statuses as $status) { if ($status['STATE'] == "Active") { echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; vertical-align: top; margin-left: 5px; margin-top: 3px;">Defect<br><select id="defect-details-select" style="height: 21px; margin-top: 6px;">
													<?php foreach ($defects as $defect) { if ($defect['STATUS'] == "Active") { echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>"; } } ?>
												</select></span>
											</div><br><span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;"></textarea>`;
									modalContent.style.width = "800px";
									break;
								default:
							}
						} else {
							html += "Error: please select a valid tool first.";
						}
						break;
					case "SHIPPING":
						if (toolType == "tool") {
							html += `<div class="basic-info" style="width: 500px;">
										<span>Packing Slip<input type="text" id="packing-slip-details-input" style="width: 100px;"></span><br>
										<span style="margin-left: 17px;">Customer<input type="text" id="customer-details-input" style="width: 150px;" value="${document.getElementById('customer-select').value}"><br>
										<span style="margin-left: 5px;">Target Date<input onkeydown="fixDate(this)" type="text" id="target-date-details-input" style="width: 100px;" value="${document.getElementById('target-input').value}"></span><br>
										<span style="margin-left: 22px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><br>
									</div><div style="display: inline-block; vertical-align: top; float: right; margin-right: 8px;"><button id="save" onclick="saveDetails('SHIPPING')">Save</button><br><button onclick="pickList()">Pick List</button></div><div style="display: inline-block; height: 230px;">
										<table><thead><tr><th style="width: 400px;">Tool</th><th style="width: 100px;">PO #</th><th style="width: 100px;">Order #</th><th style="width: 100px;">Belt #</th><th style="width: 100px;">Job #</th></tr></thead><tbody id="shipping-table">`;
							for (var i=0;i<toolList.length;i++) {
								html += `<tr><td style="width: 400px;">${toolList[i].children[0].innerHTML}</td><td style="width: 100px;">${document.getElementById('po-input').value}</td><td style="width: 100px;">${document.getElementById('order-input').value}</td><td style="width: 100px;">${document.getElementById("belt-input").value}</td><td style="width: 100px;">${document.getElementById('job-input').value}</td></tr>`;
							}
							html += `</tbody></table>
									</div><span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;"></textarea>`;
							modalContent.style.width = "900px";
						} else {
							html += "Error: please select a valid tool first.";
						}
						break;
					default:
						html += "Error: no process selected.";
				}
			} else {
				switch (department) {
					case "MASTERING":
						if (toolType == "design") {
							html += `<div class="basic-info">
										<span style="margin-left: 2px;">PO Number<input type="text" id="po-details-input" style="width: 75px;" value="${jobs[id][2]['PO_NUMBER']}"></span>
										<span>Job Number<input type="text" id="job-details-input" style="width: 75px;" value="${jobs[id][2]['JOB_NUMBER']}"></span><span style="margin-left: 5px;">WO Number<input type="text" id="wo-details-input" style="width: 75px;" value="${jobs[id][2]['WO_NUMBER']}" readonly></span><br>
										<span style="margin-left: 32px;">Design<input type="text" id="design-details-input" value="${tool}" style="width: 75px;"></span><button onclick="goToDesign()">Design Info</button><br>
										<span style="margin-left: 21px;">Operator<input type="text" id="operator-details-input" style="width: 75px;" value="${document.getElementById('operator-input').value}"></span><span style="margin-left: 4px;">Target Date<input onkeydown="fixDate(this)" type="text" id="target-date-details-input" style="width: 75px;" value="${jobs[id][2]['TARGET_DATE']}"></span><br>
									</div><div class="details-controls"><button id="save" onclick="saveDetails('MASTERING')" style="width: 60px; float: right;">Save</button></div><div class="details">
										<select onchange="setWorkType(this)" id="work-type-details-select" style="margin-left: 161px; margin-bottom: 3px;">
											<option `;
							if (jobs[id][2]['WORK_TYPE'] == "New") {
								html += `selected `;
							}
							html+= `value="New">New</option>
									<option `;
							if (jobs[id][2]['WORK_TYPE'] == 'Re-Cut') {
								html += `selected `;
							}
							html+= `value="Re-Cut">Re-Cut</option>
									<option `;
							if (jobs[id][2]['WORK_TYPE'] == 'Re-Use') {
								html += `selected `;
							}
							html+= `value="Re-Use">Re-Use</option>
									</select><br>`;
							if (workType != "Re-Cut") {
								html +=`<button onclick="popBlankList()" style="width: 60px;">Search</button><span style="margin-left: 61px;">Blank<input type="text" id="blank-details-input" style="width: 200px;" value="${jobs[id][2]['BLANK']}"></span><br>
										<button onclick="popMasterList()" style="width: 60px;">Search</button><span style="margin-left: 5px;">Re-Cut Master<input disabled type="text" id="master-details-input" style="width: 200px;"></span><br>`;
							} else {
								html +=`<button onclick="popBlankList()" style="width: 60px;">Search</button><span style="margin-left: 61px;">Blank<input disabled type="text" id="blank-details-input" style="width: 200px;"></span><br>
										<button onclick="popMasterList()" style="width: 60px;">Search</button><span style="margin-left: 5px;">Re-Cut Master<input type="text" id="master-details-input" style="width: 200px;" value="${jobs[id][2]['BLANK']}"></span><br>`;
							}
							html += `	<span style="margin-left: 129px;">Size<input type="text" id="size-details-input" value="${jobs[id][2]['SIZE']}"></span><span id="unit" style="margin-left: 5px;">(in)</span><br>
										<select id="tool-type-details-select" style="margin-left: 161px; margin-bottom: 3px;">
											<option `;
							if (jobs[id][2]['TOOL_TYPE'] == 'Hard') {
								html += `selected `;
							}
							html+= `value="Hard">Hard</option>
									<option `;
							if (jobs[id][2]['TOOL_TYPE'] == 'Soft') {
								html+= `selected `;
							}
							html+= `value="Soft">Soft</option>
									</select><br><select id="cosmetic-details-select" style="margin-left: 161px; margin-bottom: 3px;">
										<option `;
							if (jobs[id][2]['COSMETIC'] == 'Clear') {
								html += `selected `;
							}
							html+= `value="Clear">Clear</option>
									<option `;
							if (jobs[id][2]['COSMETIC'] == 'Low Glare') {
								html += `selected `;
							}
							html+= `value="Low Glare">Low Glare</option>
									</select><button onclick="switchUnit(this)" value="0" style="float: right;">Metric</button></div>
									<span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;">${jobs[id][2]['SPECIAL_INSTRUCTIONS']}</textarea>`;
						} else {
							html += "Error: Please select a valid design first.";
						}
						break;
					case "TOOLRM":
						if (toolType == "tool") {
							html += `<div class="basic-info">
										<span>Job Number<input type="text" id="job-details-input" style="width: 100px;" value="${jobs[id][2]['JOB_NUMBER']}"></span><span style="margin-left: 5px">WO Number<input type="text" id="wo-details-input" style="width: 100px;" value="${jobs[id][2]['WO_NUMBER']}" readonly></span><br>
										<span style="margin-left: 50px;">Tool<input type="text" id="tool-details-input" value="${tool}" style="width: 292px;"></span><br>
										<span style="margin-left: 13px;">Start Date<input onkeydown="fixDate(this)" type="text" id="start-date-details-input" style="width: 100px;" onblur="fillTarget(this)" value="${jobs[id][2]['DATE_IN']}"></span><span style="margin-left: 9px;">PO Number<input type="text" id="po-details-input" style="width: 100px;" value="${jobs[id][2]['PO_NUMBER']}"></span><br>
										<span style="margin-left: 4px;">Target Date<input onkeydown="fixDate(this)" type="text" id="target-date-details-input" style="width: 100px;" onblur="fillStart(this)" value="${jobs[id][2]['TARGET_DATE']}"></span><span style="margin-left: 27px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><br>
									</div><button id="save" onclick="saveDetails('TOOLRM')" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><br>
									<span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;">${jobs[id][2]['SPECIAL_INSTRUCTIONS']}</textarea>`;
						} else {
							html += "Error: please select a valid tool first.";
						}
						break;
					case "ELECTROFOR":
						if (toolType == "tool") {
							switch(process) {
								case "CLEANING":
									html += `<div class="basic-info">
												<span>Job Number<input type="text" id="job-details-input" style="width: 100px;" value="${jobs[id][2]['JOB_NUMBER']}"></span><span style="margin-left: 5px;">WO Number<input type="text" id="wo-details-input" style="width: 100px;" value="${jobs[id][2]['WO_NUMBER']}" readonly></span><br>
												<span style="margin-left: 51px;">Tool<input type="text" id="tool-details-input" value="${tool}" style="width: 291px;"></span><br>
												<span style="margin-left: 3px;">PO Number<input type="text" id="po-details-input" style="width: 100px;" value="${jobs[id][2]['PO_NUMBER']}"></span><br>
												<span style="margin-left: 21px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><span style="margin-left: 53px;">Date<input onkeydown="fixDate(this)" type="text" id="date-details-input" style="width: 100px;" value="${jobs[id][2]['DATE_IN']}">
											</div><button id="save" onclick="saveDetails('CLEANING')" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><div style="margin-bottom: 3px;" class="details-quality">
												<span style="display: inline-block; margin-left: 24px;">Location<select id="location-details-select" style="height: 21px; margin-left: 5px; width: 104px;">
													<?php foreach ($locations as $location) { if ($location['STATUS'] == "Active") { echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>"; } } ?>
												</select></span><span style="margin-left: 36px; display: inline-block;">Drawer<input type="text" id="drawer-details-input" style="width: 100px;"></span><br>
												<span style="display: inline-block; margin-left: 37px;">Status<select id="status-details-select" style="height: 21px; margin-left: 5px; width: 104px;">
													<?php foreach ($statuses as $status) { if ($status['STATE'] == "Active") { echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>"; } } ?>
												</select></span><span style="margin-left: 41px; display: inline-block;">Defect<select id="defect-details-select" style="height: 21px; margin-left: 5px; width: 104px;">
													<?php foreach ($defects as $defect) { if ($defect['STATUS'] == "Active") { echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>"; } } ?>
												</select></span></div>
											<span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;">${jobs[id][2]['SPECIAL_INSTRUCTIONS']}</textarea>`;
									break;
								case "ELECTROFORMING":
									html += `<div class="basic-info" style="width: 700px;">
												<span style="margin-left: 48px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><span style="margin-left: 5px;">Job #<input type="text" id="job-details-input" style="width: 100px;" value="${jobs[id][2]['JOB_NUMBER']}"></span><span style="margin-left: 5px;">WO #<input type="text" id="wo-details-input" style="width: 100px;" value="${jobs[id][2]['WO_NUMBER']}" readonly></span><span style="margin-left: 5px;">PO #<input type="text" id="po-details-input" style="width: 100px;" value="${jobs[id][2]['PO_NUMBER']}"></span><br>
												<span style="margin-left: 53px;">Mandrel<input type="text" id="mandrel-details-input" value="${tool}" style="width: 398px;"></span></div>
												<button id="save" onclick="saveDetails('ELECTROFORMING')" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><div style="display: inline-block; vertical-align: top;">
												<span style="margin-left: 19px;">Tank / Station<input type="text" id="tank-details-input" style="width: 40px;" value="${jobs[id][2]['TANK']}"><input type="text" id="station-details-input" style="width: 40px; margin-left: 16px;" value="${jobs[id][2]['STATION']}"><button onclick="popTankList()" style="width: 100px; margin-left: 48px;">Tank Status</button></span><br>
												<span style="margin-left: 16px;">Date / Time In<input onkeydown="fixDate(this)" type="text" id="date-in-details-input" style="width: 120px;" value="${jobs[id][2]['DATE_IN']}" onblur="fillCurrent()"></span><br>
												<span style="margin-left: 5px;">Date / Time Out<input onkeydown="fixDate(this)" type="text" id="date-out-details-input" style="width: 120px;" value="${jobs[id][2]['DATE_OUT']}" readonly></span><br>
											</div><div style="display: inline-block; width: 300px; margin-left: 5px;">
												<span style="display: inline-block;">Location<br><select id="location-details-select" style="height: 21px;">
													<?php foreach ($locations as $location) { if ($location['STATUS'] == "Active") { echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; margin-left: 5px;">Drawer<br><input type="text" id="drawer-details-input" style="margin-left: 0px; width: 100px;"></span><br>
												<span style="display: inline-block;">Status<br><select id="status-details-select" style="height: 21px;">
													<?php foreach ($statuses as $status) { if ($status['STATE'] == "Active") { echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; margin-left: 5px;">Defect<br><select id="defect-details-select" style="height: 21px;">
													<?php foreach ($defects as $defect) { if ($defect['STATUS'] == "Active") { echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>"; } } ?>
												</select></span><span display: inline-block;>Schedule Type<br><select id="schedule-type-details-select" style="height: 21px;">
													<option value="Single">Single</option>
													<option value="Indefinite">Indefinite</option>
													<option value="Repeat">Repeat</option>
													<option value="Thru Generation">Thru Generation</option></select>
												<input type="number" step="1" value="1" id="repeat-details-input" style="width: 100px;" value="${jobs[id][2]['REPEAT']}"></div>
											<div style="display: inline-block; width: 360px;">
												<span style="float: right; margin-right: 33px;">Width</span><span style="float: right; margin-right: 35px;">OD/L</span><br>
												<span style="margin-left: 124px;">Part Size (mm)<input type="text" id="part-size-length-details-input" style="width: 48px; margin-right: 5px;" value="${jobs[id][2]['PART_LENGTH']}" onblur="fillCurrent()">&times;<input type="text" id="part-size-width-details-input" style="width: 48px;" value="${jobs[id][2]['PART_WIDTH']}" onblur="fillCurrent()"></span><br>
												<span>Forming Current Density (A/sq dm)<input type="text" id="forming-density-details-input" style="width: 118px;" value="${jobs[id][2]['FORMING_DENSITY']}" onblur="fillCurrent()"></span><br>
												<span style="margin-left: 96px;">Forming Time (min)<input type="text" id="forming-time-details-input" style="width: 118px;" value="${jobs[id][2]['FORMING_TIME']}" onblur="fillCurrent()"></span><br>
												<span style="margin-left: 2px;">Building Current Density (A/sq dm)<input type="text" id="building-density-details-input" style="width: 118px;" value="${jobs[id][2]['BUILDING_DENSITY']}" onblur="fillCurrent()"></span><br>
												<span style="margin-left: 39px;">Target Form Thickness (mm)<input type="text" id="target-thickness-details-input" style="width: 118px;" value="${jobs[id][2]['TARGET_THICKNESS']}" onblur="fillCurrent()"></span>
											</div><div style="display: inline-block; vertical-align: top; margin-top: 15px;">
												<span>Forming Current (Amps)<input type="text" id="forming-current-details-input" style="width: 109px;" value="${jobs[id][2]['FORMING_CURRENT']}" readonly></span><br>
												<span style="margin-left: 1px;">Building Current (Amps)<input type="text" id="building-current-details-input" style="width: 109px;" value="${jobs[id][2]['BUILDING_CURRENT']}" readonly></span><br>
												<span style="margin-left: 9px;">Cycle Time (Hrs, Mins)<input type="text" id="cycle-time-hours-details-input" style="width: 50px;" value="${jobs[id][2]['CYCLE_TIME'].split(':')[0]}" readonly><input type="text" id="cycle-time-minutes-details-input" style="width: 50px;" value="${jobs[id][2]['CYCLE_TIME'].split(':')[1]}" readonly></span>
											</div><br><span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;">${jobs[id][2]['SPECIAL_INSTRUCTIONS']}</textarea>`;
									modalContent.style.width = "800px";
									break;
								case "NICKEL FLASHING":
									html += `<div class="basic-info" style="width: 700px;">
												<span style="margin-left: 8px;">Job Number<input type="text" id="job-details-input" style="width: 100px;" value="${jobs[id][2]['JOB_NUMBER']}"></span><span style="margin-left: 5px;">WO #<input type="text" id="wo-details-input" style="width: 100px;" value="${jobs[id][2]['WO_NUMBER']}" readonly></span><span style="margin-left: 5px;">PO #<input type="text" id="po-details-input" style="width: 100px;" value="${jobs[id][2]['PO_NUMBER']}"></span><br>
												<span style="margin-left: 59px;">Tool<input type="text" id="tool-details-input" value="${tool}" style="width: 250px;"></span><br>
											</div><button id="save" onclick="saveDetails('NICKEL FLASHING');" style="width: 60px; vertical-align: top; float: right; margin-right: 5px; margin-top: 8px;">Save</button><div style="display: inline-block; width: auto; margin-bottom: 5px;">
												<span style="margin-left: 29px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><br>
												<span style="margin-left: 55px;">Date<input onkeydown="fixDate(this)" type="text" id="date-details-input" style="width: 100px;" value="${jobs[id][2]['DATE_IN']}"></span><br>
												<span>Tank / Station<input type="text" id="tank-details-input" style="width: 44px;" value="${jobs[id][2]['TANK']}"><input type="text" id="station-details-input" style="width: 44px; margin-left: 8px;" value="${jobs[id][2]['STATION']}"></span><br>
												<span style="margin-left: 6px;">Temperature<input type="text" id="temp-details-input" style="width: 100px;" value="${jobs[id][2]['TEMPERATURE']}"></span><br>
												<span style="margin-left: 18px;">Time (min)<input type="text" id="time-details-input" style="width: 100px;" value="${jobs[id][2]['TIME']}"></span><br>
												<span style="margin-left: 16px;">Passivated<select id="passivated-details-select" style="margin-left: 5px; height: 21px;"><option value="Yes">Yes</option><option value="No">No</option></select>
											</div><div style="display: inline-block; width: 300px; vertical-align: top; margin-left: 45px; margin-top: 25px;">
												<span style="display: inline-block;">Location<br><select id="location-details-select" style="height: 21px; margin-top: 6px;">
													<?php foreach ($locations as $location) { if ($location['STATUS'] == "Active") { echo "<option value=\"" . $location['LOCATION'] . "\">" . $location['LOCATION'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; vertical-align: top; margin-left: 5px;">Drawer<br><input type="text" id="drawer-details-input" style="margin-left: 0; margin-top: 6px;"></span><span style="display: inline-block; margin-top: 3px;">Status<br><select id="status-details-select" style="height: 21px; margin-top: 6px;">
													<?php foreach ($statuses as $status) { if ($status['STATE'] == "Active") { echo "<option value=\"" . $status['STATUS'] . "\">" . $status['STATUS'] . "</option>"; } } ?>
												</select></span><span style="display: inline-block; vertical-align: top; margin-left: 5px; margin-top: 3px;">Defect<br><select id="defect-details-select" style="height: 21px; margin-top: 6px;">
													<?php foreach ($defects as $defect) { if ($defect['STATUS'] == "Active") { echo "<option value=\"" . $defect['DEFECT'] . "\">" . $defect['DEFECT'] . "</option>"; } } ?>
												</select></span>
											</div><br><span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;">${jobs[id][2]['SPECIAL_INSTRUCTIONS']}</textarea>`;
									modalContent.style.width = "800px";
									break;
								default:
							}
						} else {
							html += "Error: please select a valid tool first.";
						}
						break;
					case "SHIPPING":
						if (toolType == "tool") {
							html += `<div class="basic-info" style="width: 500px;">
										<span>Packing Slip<input type="text" id="packing-slip-details-input" style="width: 100px;"></span><br>
										<span style="margin-left: 17px;">Customer<input type="text" id="customer-details-input" style="width: 150px;" value="${document.getElementById('customer-select').value}"></span><br>
										<span style="margin-left: 5px;">Target Date<input onkeydown="fixDate(this)" type="text" id="target-date-details-input" style="width: 100px;" value="${jobs[id][2]['TARGET_DATE']}"></span><br>
										<span style="margin-left: 22px;">Operator<input type="text" id="operator-details-input" style="width: 100px;" value="${document.getElementById('operator-input').value}"></span><br>
									</div><div style="display: inline-block; vertical-align: top; float: right; margin-right: 8px;"><button id="save" onclick="saveDetails('SHIPPING')">Save</button><br><button onclick="pickList()">Pick List</button></div><div style="display: inline-block; height: 230px;">
										<table><thead><tr><th style="width: 400px;">Tool</th><th style="width: 100px;">PO #</th><th style="width: 100px;">Order #</th><th style="width: 100px;">Belt #</th><th style="width: 100px;">Job #</th></tr></thead><tbody id="shipping-table">`;
							for (var i=0;i<toolList.length;i++) {
								html += `<tr><td style="width: 400px;">${toolList[i].children[0].innerHTML}</td><td style="width: 100px;">${document.getElementById('po-input').value}</td><td style="width: 100px;">${document.getElementById('order-input').value}</td><td style="width: 100px;">${document.getElementById('belt-input').value}</td><td style="width: 100px;">${document.getElementById('job-input').value}</td></tr>`;
							}
							html += `</tbody></table>
									</div><span id="special-details-span">Special Instructions</span><textarea id="specinst-details-textarea" rows="4" cols="70" style="margin-top: 3px;">${jobs[id][2]['SPECIAL_INSTRUCTIONS']}</textarea>`;
							modalContent.style.width = "900px";
						} else {
							html += "Error: please select a valid tool first.";
						}
						break;
					default:
						html += "Error: no process selected.";
				}
			}
			
			modalContent.innerHTML = html;
			
			switch(department) {
				case "MASTERING":
				case "SHIPPING":
					fillTarget();
					break;
				case "TOOLRM":
					fillStart();
					fillTarget();
					break;
				default:
			}
			
			if (department == "ELECTROFOR") {
				tools.forEach((item, index, array) => {
					if (item['TOOL'] == tool) {
						document.getElementById("location-details-select").value = item['LOCATION'];
						document.getElementById("drawer-details-input").value = item['DRAWER'];
						document.getElementById("status-details-select").value = item['STATUS'];
						document.getElementById("defect-details-select").value = item['REASON'];
						if (process == "ELECTROFORMING" && alreadyExists === true) {
							document.getElementById('schedule-type-details-select').value = jobs[id][2]['SCHEDULE_TYPE'];
						} else if (process == "NICKEL FLASHING" && alreadyExists === true) {
							document.getElementById('passivated-details-select').value = jobs[id][2]['PASSIVATED'];
						}
					}
				});
			}
			
			if ((!alreadyExists || !("TARGET_DATE" in jobs[id][2])) && document.getElementById('target-input').value != '') {
				//set target/start dates
				var trs = document.getElementById('process-tbody').children;
				var targetDate = new Date(document.getElementById('target-input').value);
				var totalDays = 0;
				
				//check for process after this one
	loop: {		for(var i=0;i<trs.length;i++) {
					if (trs[i].children[1].innerHTML > document.getElementById(selectedProcess).children[1].innerHTML) {
						//if process found, see if date is already set for that process
						for (var j=0;j<jobs.length;j++) {
							if (tool == jobs[j][0] && trs[i].children[0].innerHTML == jobs[j][1]) {
								//job found, so use this date as new target and stop looking for processes
								if ("TARGET_DATE" in jobs[j][2]) {
									if ("DATE_IN" in jobs[j][2]) {
										targetDate = new Date(jobs[j][2]['DATE_IN']);
									} else if ("SELECT_DATE" in jobs[j][2]) {
										targetDate = new Date(jobs[j][2]['SELECT_DATE']);
									} else {
										targetDate = new Date(jobs[j][2]['TARGET_DATE']);
										totalDays += processLengths[jobs[j][1]];
									}
									break loop;
								}
							}
						}
						//if reached here, no date for that process yet
						totalDays += processLengths[trs[i].children[0].innerHTML];
					}
				}}
			
				switch (department) {
					case "MASTERING":
						if (trs.length > 1) {
							document.getElementById('target-date-details-input').value = formatDate(new Date(targetDate.setDate(targetDate.getDate() - totalDays)));
						} else {
							document.getElementById('target-date-details-input').value = formatDate(targetDate);
						}
						break;
					case "TOOLRM":
						if (trs.length > 1) {
							document.getElementById('target-date-details-input').value = formatDate(new Date(targetDate.setDate(targetDate.getDate() - totalDays)));
							document.getElementById('start-date-details-input').value = formatDate(new Date(targetDate.setDate(targetDate.getDate() - processLengths[process])));
						} else {
							document.getElementById('target-date-details-input').value = formatDate(targetDate);
							document.getElementById('start-date-details-input').value = formatDate(new Date(targetDate.setDate(targetDate.getDate() - processLengths[process])));
						}
						break;
					case "ELECTROFOR":
						switch (process) {
							case "CLEANING":
								if (trs.length > 1) {
									document.getElementById('date-details-input').value = formatDate(new Date(targetDate.setDate(targetDate.getDate() - totalDays)));
								} else {
									document.getElementById('date-details-input').value = formatDate(targetDate);
								}
								break;
							case "ELECTROFORMING":
								if (trs.length > 1) {
									document.getElementById('date-in-details-input').value = formatDateTime(new Date(targetDate.setDate(targetDate.getDate() - totalDays)));
									document.getElementById('date-out-details-input').value = formatDateTime(new Date(document.getElementById('date-in-details-input').value));
								} else {
									document.getElementById('date-in-details-input').value = formatDateTime(targetDate);
									document.getElementById('date-out-details-input').value = formatDateTime(targetDate);
								}
								break;
							case "NICKEL FLASHING":
								if (trs.length > 1) {
									document.getElementById('date-details-input').value = formatDateTime(new Date(targetDate.setDate(targetDate.getDate() - totalDays)));
								} else {
									document.getElementById('date-details-input').value = formatDateTime(targetDate);
								}
								break;
							default:
						}
						break;
					case "SHIPPING":
						if (trs.length > 1) {
							document.getElementById('target-date-details-input').value = formatDate(new Date(targetDate.setDate(targetDate.getDate() - totalDays)));
						} else {
							document.getElementById('target-date-details-input').value = formatDate(targetDate);
						}
						break;
					default:
				}
			}
		} else {
			modalContent.innerHTML = `<h3>Invalid tool for this process</h3>`;
		}
		closeForm();
	}
	
	/**
	  *	@desc	determine if tool can be used for this process
	  *	@param	none
	  *	@return	true if valid tool, false otherwise
	*/
	function processIsValid(process, tool) {
		switch(process) {
			case "CONVERT":
				return tool.includes("+");
				break;
			case "PARQUET":
				return tool.split(")")[tool.split(")").length-1] != "";
				break;
			default:
				return true;
		}
	}
	
	/**
	  *	@desc	create job data, save to batch array
	  *	@param	string process - name of process which is being saved
	  *	@return	none
	*/
	function saveDetails(process) {
		var alreadyExists = false;
		var id = jobs.length;
		jobs.forEach((item, index, array) => {
			if (document.getElementById(selectedTool).children[0].innerHTML == item[0] && document.getElementById(selectedProcess).children[0].innerHTML == item[1]) {
				alreadyExists = true;
				id = index;
			}
		});
		
		var trs = document.getElementById('process-tbody').children;
		var targetDate = new Date(document.getElementById('target-input').value);
		var totalDays = 0;
		var tool = document.getElementById(selectedTool).children[0].innerHTML;
		
		//check for process after this one
loop: {	for(var i=0;i<trs.length;i++) {
			if (trs[i].children[1].innerHTML > document.getElementById(selectedProcess).children[1].innerHTML) {
				//if process found, see if date is already set for that process
				for (var j=0;j<jobs.length;j++) {
					if (tool == jobs[j][0] && trs[i].children[0].innerHTML == jobs[j][1]) {
						//job found, so use this date as new target and stop looking for processes
						if ("DATE_IN" in jobs[j][2]) {
							targetDate = new Date(jobs[j][2]['DATE_IN']);
						} else if ("SELECT_DATE" in jobs[j][2]) {
							targetDate = new Date(jobs[j][2]['SELECT_DATE']);
						} else {
							targetDate = new Date(jobs[j][2]['TARGET_DATE']);
							totalDays += processLengths[jobs[j][1]];
						}
						break loop;
					}
				}
				//if reached here, no date for that process yet
				totalDays += processLengths[trs[i].children[0].innerHTML];
			}
		}}
		
		targetDate = new Date(targetDate.setDate(targetDate.getDate() - totalDays));
		
		if (alreadyExists === false) {
			switch(process) {
				case "MASTERING":
					var d = new Date(document.getElementById("target-date-details-input").value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML, "MASTERING",{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						WO_NUMBER: getNextWorkNumber(),
						TOOL_IN: document.getElementById("design-details-input").value,
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
						DATE_IN: formatDate(new Date(d.setDate(d.getDate() - <?= $processLengths['MASTERING'] ?>))),
						TOOL_OUT: getNextTool(),
						DATE_OUT: formatDate(new Date(document.getElementById("target-date-details-input").value)),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						SIZE: document.getElementById("size-details-input").value,
						TOOL_TYPE: document.getElementById("tool-type-details-select").value,
						COSMETIC: document.getElementById("cosmetic-details-select").value,
						WORK_TYPE: document.getElementById("work-type-details-select").value,
						IS_BLANK: document.getElementById("work-type-details-select").value == "Re-Cut" ? "FALSE" : "TRUE",
						BLANK: document.getElementById("work-type-details-select").value == "Re-Cut" ? document.getElementById("master-details-input").value : document.getElementById("blank-details-input").value
					}];
					break;
				case "TOOLRM":
					var d = new Date(document.getElementById("start-date-details-input").value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML,document.getElementById(selectedProcess).children[0].innerHTML,{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						WO_NUMBER: getNextWorkNumber(),
						PROCESS: document.getElementById(selectedProcess).children[0].innerHTML,
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
						TOOL_IN: document.getElementById("tool-details-input").value,
						DATE_IN: formatDate(d),
						DATE_OUT: formatDate(new Date(document.getElementById("target-date-details-input").value)),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
					}];
					break;
				case "CLEANING":
					var d = new Date(document.getElementById('start-date-details-input').value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML, "CLEANING",{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						WO_NUMBER: getNextWorkNumber(),
						PROCESS: "CLEANING",
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDate(d),
						TOOL_IN: document.getElementById("tool-details-input").value,
						DATE_IN: formatDate(d),
						TOOL_OUT: document.getElementById("tool-details-input").value,
						DATE_OUT: formatDate(d),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						MODE: "DONE"
					}];
					break;
				case "ELECTROFORMING":
					var d = new Date(document.getElementById('date-in-details-input').value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML, "ELECTROFORMING",{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						WO_NUMBER: getNextWorkNumber(),
						PROCESS: "ELECTROFORMING",
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDateTime(new Date(document.getElementById("date-out-details-input").value)),
						TOOL_IN: document.getElementById("mandrel-details-input").value,
						DATE_IN: formatDateTime(d),
						TOOL_OUT: document.getElementById("mandrel-details-input").value + "-" + (getNewForm() + 1),
						DATE_OUT: formatDateTime(new Date(document.getElementById("date-out-details-input").value)),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						TANK: document.getElementById("tank-details-input").value,
						STATION: document.getElementById("station-details-input").value,
						CYCLE_TIME: document.getElementById("cycle-time-hours-details-input").value + ":" + document.getElementById("cycle-time-minutes-details-input").value,
						SCHEDULE_TYPE: document.getElementById("schedule-type-details-select").value,
						REPEAT: document.getElementById("repeat-details-input").value,
						PART_LENGTH: document.getElementById("part-size-length-details-input").value,
						PART_WIDTH: document.getElementById("part-size-width-details-input").value,
						FORMING_DENSITY: document.getElementById("forming-density-details-input").value,
						FORMING_TIME: document.getElementById("forming-time-details-input").value,
						BUILDING_DENSITY: document.getElementById("building-density-details-input").value,
						TARGET_THICKNESS: document.getElementById("target-thickness-details-input").value,
						FORMING_CURRENT: document.getElementById("forming-current-details-input").value,
						BUILDING_CURRENT: document.getElementById("building-current-details-input").value
					}];
					break;
				case "NICKEL FLASHING":
					var d = new Date(document.getElementById('date-details-input').value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML, "NICKEL FLASHING",{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						WO_NUMBER: getNextWorkNumber(),
						PROCESS: "NICKEL FLASHING",
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDateTime(d),
						TOOL_IN: document.getElementById("tool-details-input").value,
						DATE_IN: formatDateTime(d),
						TOOL_OUT: document.getElementById("tool-details-input").value + "/EN",
						DATE_OUT: formatDateTime(d),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						TANK: document.getElementById("tank-details-input").value,
						STATION: document.getElementById("station-details-input").value,
						TEMPERATURE: document.getElementById("temp-details-input").value,
						TIME: document.getElementById("time-details-input").value,
						PASSIVATED: document.getElementById("passivated-details-select").value
					}];
					break;
				case "SHIPPING":
					var tools = document.getElementById("shipping-table").children;
					for (var i=0;i<tools.length;i++) {
						var d = new Date(document.getElementById('target-date-details-input').value);
						jobs[id+i] = [tools[i].children[0].innerHTML, "SHIPPING",{
							BATCH_NUMBER: batch.BATCH_NUMBER,
							JOB_NUMBER: tools[i].children[4].innerHTML,
							WO_NUMBER: getNextWorkNumber(),
							SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
							TARGET_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
							TOOL: tools[i].children[0].innerHTML,
							SELECT_DATE: formatDate(new Date(d.setDate(d.getDate() - 1))),
							SHIP_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
							SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
							CUSTOMER: document.getElementById("customer-details-input").value,
							PO_NUMBER: tools[i].children[1].innerHTML,
							ORDER_NUMBER: tools[i].children[2].innerHTML,
							BELT_NUMBER: tools[i].children[3].innerHTML.replace(/[#]/g,"%23"),
						}];
					}
					break;
				default:
			}
		} else {
			switch(process) {
				case "MASTERING":
					var d = new Date(document.getElementById("target-date-details-input").value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML,process,{
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						TOOL_IN: document.getElementById("design-details-input").value,
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
						DATE_IN: formatDate(new Date(d.setDate(d.getDate() - <?= $processLengths['MASTERING'] ?>))),
						DATE_OUT: formatDate(new Date(document.getElementById("target-date-details-input").value)),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						SIZE: document.getElementById("size-details-input").value,
						TOOL_TYPE: document.getElementById("tool-type-details-select").value,
						COSMETIC: document.getElementById("cosmetic-details-select").value,
						WORK_TYPE: document.getElementById("work-type-details-select").value,
						IS_BLANK: document.getElementById("work-type-details-select").value == "Re-Cut" ? "FALSE" : "TRUE",
						BLANK: document.getElementById("work-type-details-select").value == "Re-Cut" ? document.getElementById("master-details-input").value : document.getElementById("blank-details-input").value,
						WO_NUMBER: jobs[id][2]['WO_NUMBER']
					}];
					break;
				case "TOOLRM":
					var d = new Date(document.getElementById("start-date-details-input").value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML,document.getElementById(selectedProcess).children[0].innerHTML,{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						PROCESS: document.getElementById(selectedProcess).children[0].innerHTML,
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: document.getElementById("target-date-details-input").value,
						TOOL_IN: document.getElementById("tool-details-input").value,
						DATE_IN: formatDate(d),
						DATE_OUT: document.getElementById("target-date-details-input").value,
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						WO_NUMBER: jobs[id][2]['WO_NUMBER']
					}];
					break;
				case "CLEANING":
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML,document.getElementById(selectedProcess).children[0].innerHTML,{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						PROCESS: "CLEANING",
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDate(new Date()),
						TOOL_IN: document.getElementById("tool-details-input").value,
						DATE_IN: formatDate(new Date()),
						TOOL_OUT: document.getElementById("tool-details-input").value,
						DATE_OUT: formatDate(new Date()),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						MODE: "DONE",
						WO_NUMBER: jobs[id][2]['WO_NUMBER']
					}];
					break;
				case "ELECTROFORMING":
					var d = new Date(document.getElementById('date-in-details-input').value);
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML,document.getElementById(selectedProcess).children[0].innerHTML,{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						PROCESS: "ELECTROFORMING",
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDateTime(new Date(document.getElementById("date-out-details-input").value)),
						TOOL_IN: document.getElementById("mandrel-details-input").value,
						DATE_IN: formatDateTime(d),
						TOOL_OUT: document.getElementById("mandrel-details-input").value + "-" + (getNewForm() + 1),
						DATE_OUT: formatDateTime(new Date(document.getElementById("date-out-details-input").value)),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						TANK: document.getElementById("tank-details-input").value,
						STATION: document.getElementById("station-details-input").value,
						CYCLE_TIME: document.getElementById("cycle-time-hours-details-input").value + ":" + document.getElementById("cycle-time-minutes-details-input").value,
						SCHEDULE_TYPE: document.getElementById("schedule-type-details-select").value,
						REPEAT: document.getElementById("repeat-details-input").value,
						PART_LENGTH: document.getElementById("part-size-length-details-input").value,
						PART_WIDTH: document.getElementById("part-size-width-details-input").value,
						FORMING_DENSITY: document.getElementById("forming-density-details-input").value,
						FORMING_TIME: document.getElementById("forming-time-details-input").value,
						BUILDING_DENSITY: document.getElementById("building-density-details-input").value,
						TARGET_THICKNESS: document.getElementById("target-thickness-details-input").value,
						FORMING_CURRENT: document.getElementById("forming-current-details-input").value,
						BUILDING_CURRENT: document.getElementById("building-current-details-input").value,
						WO_NUMBER: jobs[id][2]['WO_NUMBER']
					}];
					break;
				case "NICKEL FLASHING":
					jobs[id] = [document.getElementById(selectedTool).children[0].innerHTML, document.getElementById(selectedProcess).children[0].innerHTML,{
						BATCH_NUMBER: batch.BATCH_NUMBER,
						PO_NUMBER: document.getElementById("po-details-input").value,
						JOB_NUMBER: document.getElementById("job-details-input").value,
						PROCESS: "NICKEL FLASHING",
						SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
						TARGET_DATE: formatDateTime(new Date()),
						TOOL_IN: document.getElementById("tool-details-input").value,
						DATE_IN: formatDateTime(new Date()),
						TOOL_OUT: document.getElementById("tool-details-input").value + "/EN",
						DATE_OUT: formatDateTime(new Date()),
						SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
						TANK: document.getElementById("tank-details-input").value,
						STATION: document.getElementById("station-details-input").value,
						TEMPERATURE: document.getElementById("temp-details-input").value,
						TIME: document.getElementById("time-details-input").value,
						PASSIVATED: document.getElementById("passivated-details-select").value,
						WO_NUMBER: jobs[id][2]['WO_NUMBER']
					}];
					break;
				case "SHIPPING":
					var tools = document.getElementById("shipping-table").children;
					for (var i=0;i<tools.length;i++) {
						var d = new Date(document.getElementById('target-date-details-input').value);
						jobs[id] = [tools[i].children[0].innerHTML, "SHIPPING",{
							BATCH_NUMBER: batch.BATCH_NUMBER,
							JOB_NUMBER: tools[i].children[4].innerHTML,
							SEQNUM: document.getElementById(selectedProcess).children[1].innerHTML,
							TARGET_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
							TOOL: tools[i].children[0].innerHTML,
							SELECT_DATE: formatDate(new Date(d.setDate(d.getDate() - 1))),
							SHIP_DATE: formatDate(new Date(document.getElementById("target-date-details-input").value)),
							SPECIAL_INSTRUCTIONS: document.getElementById("specinst-details-textarea").value.replace(/[&]/g,"%26").replace(/\n/g,"%0A").replace(/[#]/g,"%23"),
							CUSTOMER: document.getElementById("customer-details-input").value,
							PO_NUMBER: tools[i].children[1].innerHTML,
							ORDER_NUMBER: tools[i].children[2].innerHTML,
							BELT_NUMBER: tools[i].children[3].innerHTML.replace(/[#]/g,"%23"),
							WO_NUMBER: jobs[id][2]['WO_NUMBER']
						}];
					}
					break;
				default:
			}
		}
		
		var modalContent = document.getElementById('modal-content');
		
		if (new Date(jobs[id][2]['TARGET_DATE']) > targetDate) {
			modalContent.innerHTML = `<h3>This process overlaps the next one or exceeds the batch target date. Are you sure you wish to continue?</h3>
									  <button style="width: 70px;" id="close">Yes</button>
    								  <button style="width: 70px;" onclick="editDetails()">No</button>`;
			modalContent.style.width = "450px";
			modalContent.style.textAlign = "center";
			closeForm();
		} else {
			document.getElementById("close").click();
		}
	}
	
	/**
	  *	@desc	create/display list of tanks
	  *	@param	none
	  *	@return	none
	*/
	function popTankList() {
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		modalContent.style.textAlign = "left";
		var html = `<span class="close" id="close">&times;</span>`;
		
		html += `<table id="tank-table"><thead><tr><th class="col1">Tank</th><th class="col2">Station</th><th class="col3">Available</th><th class="col4">Schedule Type</th><th class="col5">Stress (PSI)</th><th class="col6">Date</th><th class="col7">Mandrel</th><th class="col8">Form #</th></tr></thead><tbody>`;
		
		tanks.forEach((item, index, array) => {
			for(var i=0;i<item['STATIONS'];i++) {
				isOccupied = false;
				for(var j=0;j<electroJobs.length;j++) {
					if (item['TANK'] == electroJobs[j]['TANK'] && i+1 == electroJobs[j]['STATION']) {
						isOccupied = true;
						html += `<tr id="${item['ID']}" onclick="selectTankRow(this)"><td class="col1">${item['TANK']}</td><td class="col2">${i+1}</td><td class="col3">No</td><td class="col4">${electroJobs[j]['SCHEDULE_TYPE']}</td><td class="col5">${item['STRESS']}</td><td class="col6">${item['DATE']}</td><td class="col7">${electroJobs[j]['TOOL_IN']}</td><td class="col8">${electroJobs[j]['TOOL_OUT'].split("-")[electroJobs[j]['TOOL_OUT'].split("-").length-1]}</td></tr>`;
						break;
					}
				}
				
				if (isOccupied == false) {
					html += `<tr id="${item['ID']}" onclick="selectTankRow(this)"><td class="col1">${item['TANK']}</td><td class="col2">${i+1}</td><td class="col3">Yes</td><td class="col4"></td><td class="col5">${item['STRESS']}</td><td class="col6">${item['DATE']}</td><td class="col7"></td><td class="col8"></td></tr>`;
				}
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		modalContent.style.width = "1000px";
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight selected tank row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectTankRow(tr) {
		var trs = tr.parentNode.children;
		
		for(var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
			trs[i].setAttribute('onclick','selectTankRow(this)');
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		tr.setAttribute('onclick','confirmTankRow(this)');
	}
	
	/**
	  *	@desc	insert tank to eform job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmTankRow(tr) {
		editDetails();
	
		document.getElementById("tank-details-input").value = tr.children[0].innerHTML;
		document.getElementById("station-details-input").value = tr.children[1].innerHTML;
	}
	
	/**
	  *	@desc	create/display list of blanks
	  *	@param	none
	  *	@return	none
	*/
	function popBlankList() {
		workType = document.getElementById("work-type-details-select").value;
		var searchText = document.getElementById("blank-details-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		modalContent.style.textAlign = "left";
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
	  *	@desc	insert blank to mastering job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmBlank(tr) {
		editDetails();
		document.getElementById("work-type-details-select").value = workType;
		document.getElementById("blank-details-input").value = tr.children[0].innerHTML;
	}
	
	/**
	  *	@desc	create/display list of masters
	  *	@param	none
	  *	@return	none
	*/
	function popMasterList() {
		workType = document.getElementById("work-type-details-select").value;
		var searchText = document.getElementById("master-details-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
		modalContent.style.textAlign = "left";
		var html = `<span class="close" id="close">&times;</span><table id="master-table"><thead><tr><th class="col1">Master</th><th class="col2">Location</th><th class="col3">Drawer</th><th class="col4">Status</th></tr></thead><tbody>`;
		
		masters.forEach((item, index, array) => {
			if (item['TOOL'].includes(searchText.toUpperCase())) {
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
	  *	@desc	insert selected master to mastering job data
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function confirmMaster(tr) {
		editDetails();
		document.getElementById("work-type-details-select").value = workType;
		document.getElementById("master-details-input").value = tr.children[0].innerHTML;
	}
	
	/**
	  *	@desc	set either recut-master or blank field to readOnly
	  *	@param	DOM Object select - value determines result
	  *	@return	none
	*/
	function setWorkType(select) {
		switch(select.value) {
			case "Re-Cut":
				document.getElementById("blank-details-input").disabled = true;
				document.getElementById("master-details-input").disabled = false;
				break;
			default:
				document.getElementById("blank-details-input").disabled = false;
				document.getElementById("master-details-input").disabled = true;
		}
		workType = select.value;
	}
	
	/**
	  *	@desc	fill in target date based on start date
	  *	@param	DOM Object input - start date to count from
	  *	@return	none
	*/
	function fillTarget(input) {
		if (input == undefined) {
			var process = document.getElementById(selectedProcess).children[0].innerHTML;
			var d = new Date();
			document.getElementById("target-date-details-input").value = formatDate(new Date(d.setDate(d.getDate() + processLengths[process])));
		} else {
			var key = /^[0-9]?[0-9][-\/][0-9]?[0-9][-\/](?:[0-9]{2}){1,2}$/;
			var d = new Date(input.value);
			if (key.test(input.value)) {
				input.value = formatDate(new Date(input.value));
				document.getElementById("target-date-details-input").value = formatDate(new Date(d.setDate(d.getDate() + processLengths[document.getElementById(selectedProcess).children[0].innerHTML])));
			} else {
				document.getElementById("target-date-details-input").value = "invalid date input";
			}
		}
	}
	
	/**
	  *	@desc	fill start date based on target date
	  *	@param	DOM Object input - target date to count backwards from
	  *	@return	none
	*/
	function fillStart(input) {
		if (input == undefined) {
			document.getElementById("start-date-details-input").value = formatDate(new Date());
		} else {
			var key = /^[0-9]?[0-9][-\/][0-9]?[0-9][-\/](?:[0-9]{2}){1,2}$/;
			var d = new Date(input.value);
			if (key.test(input.value)) {
				input.value = formatDate(new Date(input.value));
				document.getElementById("start-date-details-input").value = formatDate(new Date(d.setDate(d.getDate() - processLengths[document.getElementById(selectedProcess).children[0].innerHTML])));
			} else {
				document.getElementById("start-date-details-input").value = "invalid date input";
			}
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
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - MM/DD/YY H:i
	*/
	function formatDateTime(d) {
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		
		var hours = d.getHours();
		if (hours < 10) {
			hours = "0" + hours;
		}
		
		var minutes = d.getMinutes();
		if (minutes < 10) {
			minutes = "0" + minutes;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString() + " " + hours.toString() + ":" + minutes.toString();
		
		return date;
	}
	
	/**
	  *	@desc	fill build current, form current, cycle time
	  *	@param	none
	  *	@return	none
	*/
	function fillCurrent() {
		if (document.getElementById("part-size-length-details-input").value != "" && document.getElementById("part-size-width-details-input").value != "" && document.getElementById("forming-density-details-input").value != "" && document.getElementById("forming-time-details-input").value != "" && document.getElementById("building-density-details-input").value != "" && document.getElementById("target-thickness-details-input").value != "") {
			
			var area = (parseFloat(document.getElementById("part-size-length-details-input").value) / 100) * (parseFloat(document.getElementById("part-size-width-details-input").value) / 100);
			
			document.getElementById("forming-current-details-input").value = parseInt((area * parseFloat(document.getElementById("forming-density-details-input").value)) + 1);
			document.getElementById("building-current-details-input").value = parseInt((area * parseFloat(document.getElementById("building-density-details-input").value)) + 1);
			var cycleTime = (parseFloat(document.getElementById('target-thickness-details-input').value) - (parseFloat(document.getElementById('forming-density-details-input').value) * (parseFloat(document.getElementById('forming-time-details-input').value)/60) * 0.01194)) / (0.01194*parseFloat(document.getElementById('building-density-details-input').value)) + parseFloat(document.getElementById('forming-time-details-input').value) / 60;

			
			//placeholder for later
			document.getElementById("cycle-time-hours-details-input").value = parseInt(cycleTime);
			document.getElementById("cycle-time-minutes-details-input").value = parseInt((cycleTime % 1 * 60) + 1);
			
			var date = new Date(document.getElementById("date-in-details-input").value);
			
			var hours = parseInt(document.getElementById("cycle-time-hours-details-input").value);
			hours *= 3600000;
			var minutes = parseInt(document.getElementById("cycle-time-minutes-details-input").value);
			minutes *= 60000;
			
			var newDate = date.getTime() + hours + minutes;
			
			date.setTime(newDate);
			
			
			document.getElementById("date-out-details-input").value = formatDateTime(date);
		}
	}
	
	/**
	  *	@desc	get next available tool name
	  *	@param	none
	  *	@return	string - design name + next master key
	*/
	function getNextTool() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "TOOL";
		var value = document.getElementById("design-details-input").value + "-[A-Z]";
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
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value,false);
		conn.send();
		
		jobs.forEach((item, index, array) => {
			if (item[1] == "MASTERING") {
				if (max < item[2]["TOOL_OUT"].split("-")[item[2]["TOOL_OUT"].split("-").length-1]) {
					max = item[2]["TOOL_OUT"].split("-")[item[2]["TOOL_OUT"].split("-").length-1];
				}
			}
		});
		
		return document.getElementById("design-details-input").value + "-" + getNextKey(max);
	}
	
	/**
	  *	@desc	get next key: a to b, A to B, z to aa, Z to AA
	  *	@param	string key - current key
	  *	@return	string key - current key incremented by 1
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
				var response = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
				for (let job of response) {
					for (let x in job) {
						if (job[x] !== null && typeof job[x] == 'object') {
							job[x] = formatDate(new Date(job[x]['date']));
						}
					}
				}
				
				batch.BATCH_NUMBER = parseInt(response[0]['BATCH_NUMBER']) + 1;
				document.getElementById("batch-number").innerHTML = "Batch #" + (parseInt(response[0]['BATCH_NUMBER']) + 1);
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
		var tables = ["Mastering","Mastering_History","Mastering_Queue","Toolroom","Toolroom_History","Toolroom_Queue","Shipping","Shipping_History","Shipping_Queue","Electroforming","Electroforming_History","Electroforming_Queue","Abort_History"];
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
		
		jobs.forEach((item, index, array) => {
			if (item[2]["WO_NUMBER"] > max) {
				max = parseInt(item[2]["WO_NUMBER"]);
			}
		});
		
		return max + 1;
	}
	
	/**
	  *	@desc	get next available tool name
	  *	@param	none
	  *	@return	string newForm - last used tool name
	*/
	function getNewForm() {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		var action = "select";
		var condition = "MANDREL";
		var value = document.getElementById("mandrel-details-input").value.replace(/[+]/g,"%2B");
		var newForm = 1;
		
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
					for (var i=0;i<response.length;i++) {
						if (parseInt(response[i]['TOOL'].split("-")[response[i]['TOOL'].split("-").length-1]) > newForm) {
							newForm = parseInt(response[i]['TOOL'].split("-")[response[i]['TOOL'].split("-").length-1]);
						}
					}
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return newForm;
	}
	
	/**
	  *	@desc	validate data
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (jobs.length <= 0) {
			msg = "No jobs defined";
		} else if (document.getElementById("target-input").value == "") {
			msg = "Please enter a target date";
		} else if (document.getElementById("belt-input").value == "") {
			msg = "Please enter a belt number";
		} else if (document.getElementById("operator-input").value == "") {
			msg = "Please enter your initials";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	save batch data
	  *	@param	none
	  *	@return	none
	*/
	function saveBatch() {
		var msg = checkFields();
		
		if (msg == "") {
			batch.OPERATOR = document.getElementById('operator-input').value;
			batch.DATE = formatDate(new Date());
			batch.TARGET_DATE = formatDate(new Date(document.getElementById('target-input').value));
			batch.BATCH_INSTRUCTIONS = document.getElementById("comment-textarea").value;
			batch.BELT_NUMBER = document.getElementById("belt-input").value.toUpperCase();
			getNextBatchNumber();
			var successCounter = 0;
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "insert";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Insert succeeded.")) {
					
						var conn2 = [];
						var action2 = "insert";
					
						for(var i=0;i<jobs.length;i++) {
							
							var query2 = "";
							
							switch (jobs[i][1]) {
								case "MASTERING":
									if (jobs[i][2].SEQNUM == 1) {
										table2 = "Mastering";
									} else {
										table2 = "Mastering_Queue";
									}
									break;
								<?php foreach($processes as $process) {
									if ($process['DEPARTMENT'] == "TOOLRM") {
										echo 'case "' . $process['PROCESS'] . '":' . PHP_EOL;
									}
								} ?>
									if (jobs[i][2].SEQNUM == 1) {
										table2 = "Toolroom";
									} else {
										table2 = "Toolroom_Queue";
									}
									break;
								<?php foreach($processes as $process) {
									if ($process['DEPARTMENT'] == "ELECTROFOR") {
										echo 'case "' . $process['PROCESS'] . '":' . PHP_EOL;
									}
								} ?>
									if (jobs[i][2].SEQNUM == 1) {
										table2 = "Electroforming";
									} else {
										table2 = "Electroforming_Queue";
									}
									break;
								case "SHIPPING":
									if (jobs[i][2].SEQNUM == 1) {
										table2 = "Shipping";
									} else {
										table2 = "Shipping_Queue";
									}
									jobs[i][2].CUSTOMER = document.getElementById("customer-select").value;
									jobs[i][2].JOB_NUMBER = document.getElementById("job-input").value;
									jobs[i][2].PO_NUMBER = document.getElementById("po-input").value;
									jobs[i][2].ORDER_NUMBER = document.getElementById("order-input").value;
									jobs[i][2].BELT_NUMBER = document.getElementById("belt-input").value;
									break;
								default:
							}
							
							if (document.getElementById("checkbox").checked) {
								jobs[i][2].SPECIAL_INSTRUCTIONS = document.getElementById("comment-textarea").value;
							}
							
							Object.keys(jobs[i][2]).forEach((item, index, array) => {
								query2 += `&${item}=${jobs[i][2][item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							})
							
							conn2[i] = new XMLHttpRequest();
							
							conn2[i].onreadystatechange = xmlResponse(i);
							
							conn2[i].open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
							conn2[i].send();
						}
						
						function xmlResponse(i) {
							return function() {
								if (conn2[i].readyState == 4 && conn2[i].status == 200) {
									if (conn2[i].responseText.includes("Insert succeeded.")) {
										successCounter++;
										checkSuccess(successCounter);
									} else {
										alert("Batch created, but job entry failed. Contact support to correct. " + conn2.responseText);
									}
								}
							}
						}
					} else {
						alert("Batch creation failed. " + conn1.responseText);
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
	}
	
	/**
	  *	@desc	determine if all jobs were successfully input
	  *	@param	int counter - queries attempted so far
	  *	@return	none
	*/
	function checkSuccess(counter) {
		if (counter == jobs.length) {
			reserveTools();
			alert("Batch scheduled successfully");
			window.location.replace("../scheduling.php");
		} else {
			return;
		}
	}
	
	/**
	  *	@desc	reserveTool handler
	  *	@param	none
	  *	@return	none
	*/
	function reserveTools() {
		jobs.forEach((item, index, array) => {
			if (item[2].hasOwnProperty("TOOL_OUT")) {
				if (item[2].TOOL_OUT != item[2].TOOL_IN) {
					reserveTool(item[2].TOOL_IN, item[2].TOOL_OUT, false);
				}
			} else if (item[2].hasOwnProperty("TOOL")) {
				reserveTool(item[2].TOOL, item[2].TOOL, true);
			}
		});
		
		return;
	}
	
	/**
	  *	@desc	reserve tool name in tree
	  *	@param	string mandrel - TOOL_IN value, string tool - TOOL_OUT value
	  *	@return	none
	*/
	function reserveTool(mandrel, tool, shipping) {
		var conn = new XMLHttpRequest();
		var table = "Tool_Tree";
		if (shipping) {
			var action = "update";
			var newTool = {
				BELT_NUMBER: document.getElementById("belt-input").value,
				condition: "TOOL",
				value: tool
			};
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (!conn.responseText.includes("Data updated")) {
						alert("Belt number not added to tool. Contact IT Support.");
					}
				}
			}
		} else {
			var action = "insert";
			var newTool = {
				MANDREL: mandrel,
				TOOL: tool,
				LEVEL: 0,
				STATUS: "PENDING",
				BELT_NUMBER: document.getElementById("belt-input").value
			};
			
			conn.onreadystatechange = function() {
				if (conn.readyState == 4 && conn.status == 200) {
					if (!conn.responseText.includes("Insert succeeded")) {
						alert("New tool not added to database. Contact IT Support.");
					}
				}
			}
		}
			
		var query = "";
		
		Object.keys(newTool).forEach((item, index, array) => {
			query += `&${item}=${newTool[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
		});
			
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, false);
		conn.send();
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
	  *	@desc	determine if any toolroom or shipping jobs are repeats
	  *	@param	none
	  *	@return	none
	*/
	function checkForRepeats() {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table, query, repeats = false;
		
		for (i=0;i<jobs.length;i++) {
			if (jobs[i][1] != "MASTERING" && jobs[i][1] != "ELECTROFORMING" && jobs[i][1] != "CLEANING" && jobs[i][1] != "NICKEL FLASHING") {
				if (jobs[i][1] == "SHIPPING") {
					table = "Shipping_History";
					condition = "TOOL";
				} else {
					table = "Toolroom_History";
					condition = "TOOL_IN";
				}
				
				conn.onreadystatechange = function() {
					if (conn.readyState == 4 && conn.status == 200) {
						var result = JSON.parse(conn.responseText.includes("<!DOCTYPE html>") ? conn.responseText.split("<!DOCTYPE html>")[1] : conn.responseText);
						if (result.length > 0) {
							if (repeats) {
								repeats.push([jobs[i][0],jobs[i][1]]);
							} else {
								repeats = [[jobs[i][0],jobs[i][1]]];
							}
						}
					}
				}
				
				conn.open("GET","/db_query/sql2.php?action=" + action + "&table=" + table + "&condition=" + condition + "&value=" + jobs[i][0].replace(/[+]/g,"%2B") + (jobs[i][1] != "SHIPPING" ? "&condition2=PROCESS&value2=" + jobs[i][1] : ""), false);
				conn.send();
			}
		}
		
		if (repeats) {
			var modal = document.getElementById('modal');
			var modalContent = document.getElementById('modal-content');
			var html = "<p>At least one of your shipping or toolroom jobs has already been processed. Repeated jobs:<p><ul>";
			modalContent.style.width = "auto";
			
			for (i=0;i<repeats.length;i++) {
				html += "<li><p>Tool: <strong>" + repeats[i][0] + "</strong></p><p>Process: <strong>" + repeats[i][1] + "</strong></p></li>";
			}
			
			html += "</ul><p>Continue anyway?</p><button style=\"margin-left: 120px;\" id=\"continue-button\">Yes</button><button style=\"margin-left: 80px;\" id=\"cancel-button\">No</button>";
			
			modalContent.innerHTML = html;
			modal.style.display = "block";
		
			document.getElementById("cancel-button").onclick = function() {
				modal.style.display = "none";
			}
		
			document.getElementById("continue-button").onclick = function() {
				saveBatch();
			}
		} else {
			saveBatch();
		}
	}
</script>
<html>
<head>
	<link type="text/css" rel="stylesheet" href="/styles/scheduling/addbatch.css">
	<title>Add Batch</title>
</head>
	<body onload="getNextBatchNumber(); initialize();">
		<div class="outer">
			<div class="inner">
				<div class="time-stamp">
					<span id="batch-number">Batch #0000000000</span>
					<span id="target-span">Target Date<input onkeydown="fixDate(this)" type="text" id="target-input"></span>
					<span id="operator-span">Operator<input onblur="this.value = this.value.toUpperCase();" type="text" id="operator-input"></span>
				</div>
				<div class="job-info">
					<span id="customer-span">Customer<select id="customer-select">
						<?php foreach($customers as $customer) {
							echo "<option value=\"" . $customer['CUSTOMER'] . "\">" . $customer['CUSTOMER'] . " - " . $customer['NAME'] . "</option>";
						} ?>
					</select></span>
					<span id="job-info-middle">
						<span id="job-span">Job #<input onblur="this.value = this.value.toUpperCase();" type="text" id="job-input"></span>
						<span id="po-span">PO #<input onblur="this.value = this.value.toUpperCase();" type="text" id="po-input"></span>
					</span>
					<span id="job-info-right">
						<span id="belt-span">Belt #<input onblur="this.value = this.value.toUpperCase();" type="text" id="belt-input"></span>
						<span id="order-span">Order #<input onblur="this.value = this.value.toUpperCase();" type="text" id="order-input"><span>
					</span>
				</div>
				<div class="lists">
					<div class="tool-list">
						<span id="tool-list-controls">
							<button id="add-tool" onclick="popToolSearch()">Add Tool</button>
							<button id="add-design" onclick="popDesignSearch()">Add Design</button>
							<button id="delete-tool" onclick="deleteTool()">Delete</button>
						</span>
						<table id="tool-table">
							<thead id="tool-thead">
								<tr>
									<th>Tool</th>
								</tr>
							</thead>
							<tbody id="tool-tbody">
							</tbody>
						</table>
					</div>
					<div class="process-list">
						<span id="process-list-controls">
							<button id="add-process" onclick="popProcessList()">Add</button>
							<button id="move-process-up" onclick="moveProcessUp()">Move Up</button>
							<button id="move-process-down" onclick="moveProcessDown()">Move Down</button>
							<button id="delete-process" onclick="deleteProcess()">Delete</button>
						</span>
						<table id="process-table">
							<thead id="process-thead">
								<tr>
									<th class="col1">Process</th>
									<th class="col2">Seq #</th>
									<th class="col3">Hold</th>
								</tr>
							</thead>
							<tbody id="process-tbody">
							</tbody>
						</table>
					</div>
				</div>
				<div class="controls">
					<button onclick="checkForRepeats()">Save</button>
					<a href="../scheduling.php">Cancel</a>
					<button id="details" onclick="editDetails()">Details</button>
				</div><br>
				<span>Special Instructions for Belt</span><br>
				<textarea rows="4" cols="70" id="comment-textarea"></textarea><br>
				<input id="checkbox" type="checkbox" value="ApplyToAll">Apply to All Processes
			</div>
		</div>
		<div id="modal" class="modal">
			<div id="modal-content" class="modal-content">
			</div>
		</div>
	</body>
</html>