<!DOCTYPE html>
<?php
/**
  *	@desc main shipping table for operations
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of jobs and processes
	$processes = array();
	$jobs = array();
	$incomingJobs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT ID, PROCESS FROM Processes WHERE DEPARTMENT = 'TOOLRM'");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$processes[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TARGET_DATE, CUSTOMER, SELECT_DATE, SELECT_OPERATOR, SHIP_DATE, SHIP_OPERATOR, PACKING_SLIP, TOOL, BELT_NUMBER FROM Shipping WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$jobs[$row['BATCH_NUMBER']][$row['ID']] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
		
		$result = sqlsrv_query($conn, "SELECT ID, BATCH_NUMBER, TARGET_DATE, CUSTOMER, SELECT_DATE, SELECT_OPERATOR, SHIP_DATE, SHIP_OPERATOR, PACKING_SLIP, TOOL, BELT_NUMBER FROM Shipping_Queue WHERE ON_HOLD IS NULL OR ON_HOLD <> 'TRUE';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$incomingJobs[$row['BATCH_NUMBER']][$row['ID']] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		var_dump(sqlsrv_errors());
	}
	
?>
<script src="/scripts/cookies.js"></script>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//set up tracking variables
	var incoming = false;
	var selectedRow = 0;
	var selectedToolRow = 0;
	var jobs = {
		<?php
		foreach($jobs as $batchNumber=>$batch) {
			echo "$batchNumber: {\n\t\t\t";
			foreach($batch as $id=>$job) {
				echo "$id: {\n\t\t\t\t";
				foreach($job as $key=>$value) {
					echo "'$key': `";
					if ($value instanceof DateTime) {
						echo date_format($value,'m/d/y');
					} else {
						echo $value;
					}
					echo "`,\n\t\t\t\t\t";
				}
				echo "},";
			}
			echo "},";
		}?>
	};
	
	var incomingJobs = {
		<?php
		foreach($incomingJobs as $batchNumber=>$batch) {
			echo "$batchNumber: {\n\t\t\t";
			foreach($batch as $id=>$job) {
				echo "$id: {\n\t\t\t\t";
				foreach($job as $key=>$value) {
					echo "'$key': `";
					if ($value instanceof DateTime) {
						echo date_format($value,'m/d/y');
					} else {
						echo $value;
					}
					echo "`,\n\t\t\t\t\t";
				}
				echo "},";
			}
			echo "},";
		}?>
	};
	
	window.addEventListener('keydown', function(e) {
		if ([38,40].indexOf(e.keyCode) > -1) {
			e.preventDefault();
		}
	}, false);
	
	document.onkeydown = function(evt) {
		evt = evt || window.event;
		var charCode = evt.keyCode || evt.which;
		if (charCode == "40" && document.getElementById(selectedRow).nextElementSibling) {
			document.getElementById(selectedRow).nextElementSibling.click();
		} else if (charCode == "38" && document.getElementById(selectedRow).previousElementSibling) {
			document.getElementById(selectedRow).previousElementSibling.click();
		} else {
			return;
		}
		document.getElementById(selectedRow).scrollIntoView();
	}
	
	/**
	  *	@desc	open sort/filter box, if session variable exists and is true
	  *	@param	none
	  *	@return	none
	*/
	function checkSortBox() {
		if (checkCookie("sort_expanded") && getCookie("sort_expanded") == "true") {
			document.getElementById("arrow").click();
			document.getElementsByClassName("filter-inner")[0].children[2].click();
		}
	}
	
	/**
	  *	@desc	highlight selected row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		if (document.getElementById(selectedRow) != undefined) {
			setColor(document.getElementById(selectedRow));
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		selectedRow = tr.id;
		
		getTools(tr);
	}
	
	/**
	  *	@desc	insert tools in shipping job
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function getTools(tr) {
		var tbody = document.getElementById("tool-table").children[1];
		tbody.innerHTML = "";
		if (!incoming) {
			var ids = Object.keys(jobs[tr.id]);
		} else {
			var ids = Object.keys(incomingJobs[tr.id]);
		}
		ids.sort(function (a, b) {
			if (!incoming) {
				var a1 = jobs[tr.id][a]['TOOL'].split("-");
				var b1 = jobs[tr.id][b]['TOOL'].split("-");
			} else {
				var a1 = incomingJobs[tr.id][a]['TOOL'].split("-");
				var b1 = incomingJobs[tr.id][b]['TOOL'].split("-");
			}
			
			var a2 = a1.pop();
			a1 = a1.join("-");
			var b2 = b1.pop();
			b1 = b1.join("-");
			
			if (a1 < b1) {
				return -1;
			} else if (a1 > b1) {
				return 1;
			} else {
				if (parseInt(a2) == NaN || parseInt(b2) == NaN) {
					return a2 > b2;
				} else {
					return a2 - b2;
				}
			}
		});
		
		if (!incoming) {
			for (var i=0;i<ids.length;i++) {
				tbody.innerHTML += `<tr onclick="selectToolRow(this)" id="${jobs[tr.id][ids[i]]['ID']}"><td class="col1">${jobs[tr.id][ids[i]]['TOOL']}</td><td class="col2">${jobs[tr.id][ids[i]]['BELT_NUMBER']}</td></tr>`;
			}
			
			if (jobs[selectedRow][ids[0]]['SELECT_OPERATOR'] != "") {
				document.getElementById('print-button').disabled = false;
				document.getElementById('select-button').disabled = false;
				document.getElementById('select-button').innerHTML = "View Details";
			} else {
				document.getElementById('print-button').disabled = true;
				document.getElementById('select-button').disabled = false;
				document.getElementById('select-button').innerHTML = "Select for Shipping";
			}
		} else {
			for (var i=0;i<ids.length;i++) {
				tbody.innerHTML += `<tr onclick="selectToolRow(this)" id="${incomingJobs[tr.id][ids[i]]['ID']}"><td class="col1">${incomingJobs[tr.id][ids[i]]['TOOL']}</td><td class="col2">${incomingJobs[tr.id][ids[i]]['BELT_NUMBER']}</td></tr>`;
			}
			
			document.getElementById('select-button').disabled = true;
			document.getElementById('print-button').disabled = true;
		}
		
		document.getElementById("retrieve-button").disabled = true;
		document.getElementById("return-button").disabled = true;
	}
	
	/**
	  *	@desc	highlight selected tool row
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectToolRow(tr) {
		var trs = tr.parentNode.children;
		for (var i=0;i<trs.length;i++) {
			trs[i].style.backgroundColor = "white";
			trs[i].style.color = "black";
		}
		
		tr.style.backgroundColor = "black";
		tr.style.color = "white";
		
		document.getElementById("retrieve-button").disabled = false;
		if (jobs[selectedRow][Object.keys(jobs[selectedRow]).pop()]['SHIP_OPERATOR'] != "") {
			document.getElementById("return-button").disabled = true;
		} else {
			document.getElementById("return-button").disabled = false;
		}
		
		selectedToolRow = tr.id;
	}
	
	/**
	  *	@desc	go to shippingin page
	  *	@param	none
	  *	@return	none
	*/
	function processIn() {
		var batchNumber = selectedRow;
		document.getElementsByTagName("BODY")[0].innerHTML += `<form action="shippingin.php" method="POST" style="display: none;" id="batch-form"><input type="text" name="batch" value="${batchNumber}"><input type="submit"></form>`;
		document.getElementById("batch-form").submit();
	}
	
	/**
	  *	@desc	get ship date, go to shippingout page
	  *	@param	none
	  *	@return	none
	*/
	function processOut(date) {
		var user = "<?=$_SESSION['name']?>" == "eform" || "<?=$_SESSION['name']?>" == "troom" || "<?=$_SESSION['name']?>" == "master" ? prompt("Enter your initials:") : "<?=$_SESSION['initials']?>";
		if (date != "") {
			if (date != "cancel") {
				var d = new Date(date);
				if (d.toString() == "Invalid Date") {
					alert("Invalid date");
					getDate();
				} else {
					var batchNumber = selectedRow;
					document.getElementsByTagName("BODY")[0].innerHTML += `<form action="shippingout.php" method="POST" style="display: none;" id="batch-form"><input type="text" name="user" value="${user}"><input type="text" name="date" value="${date}"><input type="text" name="batch" value="${batchNumber}"><input type="submit"></form>`;
					document.getElementById("batch-form").submit();
				}
			} else {
				document.getElementById("close").click();
			}
		} else {
			alert("Invalid date");
		}
	}
	
	/**
	  *	@desc	form to ask for date
	  *	@param	none
	  *	@return	string - date string
	*/
	function getDate() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		var html = "<span id=\"close\">&times;</span>";
		html += "<p>Enter the ship date:</p><input onkeydown=\"fixDate(this)\" id=\"ship-date-input\"><br>";
		html += "<button id=\"confirm-button\">Submit</button><button id=\"cancel-button\">Cancel</button>";
		modalContent.innerHTML = html;
		
		document.getElementById("confirm-button").onclick = function() {
			processOut(document.getElementById("ship-date-input").value);
		};
		
		document.getElementById("cancel-button").onclick = function() {
			processOut("cancel");
		};
		
		modal.style.display = "block";
		
		window.onkeydown = function(e) {
			if (modal.style.display == "block" && e.key === "Enter") {
				document.getElementById("confirm-button").click();
			}
		}
		
		document.getElementById("ship-date-input").focus();
		
		closeForm();
	}
	
	/**
	  *	@desc	setColor handler
	  *	@param	none
	  *	@return	none
	*/
	function setColors() {
		var trs = document.getElementsByClassName('main')[0].children[0].children[1].children;
		for (var i=0;i<trs.length;i++) {
			setColor(trs[i]);
		}
	}
	
	/**
	  *	@desc	adjust color of row based on job status
	  *	@param	DOM Object tr - row to adjust
	  *	@return	none
	*/
	function setColor(tr) {
		if (!incoming) {
			var job = jobs[tr.id][Object.keys(jobs[tr.id]).pop()];
		} else {
			var job = incomingJobs[tr.id][Object.keys(incomingJobs[tr.id]).pop()];
		}
		
		if (job['SHIP_OPERATOR'] == "") {
			if (job['SELECT_OPERATOR'] == "") {
				tr.style.color = "black";
				tr.style.backgroundColor = "#f00";
			} else {
				tr.style.color = "black";
				tr.style.backgroundColor = "#ff0";
			}
		} else {
			tr.style.color = "black";
			tr.style.backgroundColor = "white";
		}
	}
	
	/**
	  *	@desc	sort jobs array by given column
	  *	@param	string value - column to sort by
	  *	@return	none
	*/
	function sortBy(value) {
		setCookie("sort_shipping_operations_order",document.getElementById("order-type").value);
		setCookie("sort_shipping_operations_filter",document.getElementById("filter-type").value);
		setCookie("sort_shipping_operations_filter_value",document.getElementById("filter-input").value);
		
		if (!incoming) {
			var ids = Object.keys(jobs);
		} else {
			var ids = Object.keys(incomingJobs);
		}
		ids.sort(function(a, b) {
			if (!incoming) {
				var job1 = jobs[a][Object.keys(jobs[a]).pop()];
				var job2 = jobs[b][Object.keys(jobs[b]).pop()];
			} else {
				var job1 = incomingJobs[a][Object.keys(incomingJobs[a]).pop()];
				var job2 = incomingJobs[b][Object.keys(incomingJobs[b]).pop()];
			}
			
			switch(value) {
				case "customer":
					if (job1['CUSTOMER'] < job2['CUSTOMER']) {
						return -1;
					} else if (job1['CUSTOMER'] > job2['CUSTOMER']) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "linecolor":
					if (job1['SELECT_OPERATOR'] == "") {
						if (job2['SELECT_OPERATOR'] == "") {
							return 0;
						} else {
							return -1;
						}
					} else if (job1['SHIP_OPERATOR'] == "") {
						if (job2['SELECT_OPERATOR'] == "") {
							return 1;
						} else if (job2['SHIP_OPERATOR'] == "") {
							return 0;
						} else {
							return -1;
						}
					} else {
						if (job2['SHIP_OPERATOR'] != "") {
							return 0;
						} else {
							return 1;
						}
					}
					break;
				case "packingslip":
					if (job1['PACKING_SLIP'] < job2['PACKING_SLIP']) {
						return -1;
					} else if (job1['PACKING_SLIP'] > job2['PACKING_SLIP']) {
						return 1;
					} else {
						return 0;
					}
					break;
				case "targetdate":
					if (new Date(job1['TARGET_DATE']) < new Date(job2['TARGET_DATE'])) {
						return -1;
					} else if (new Date(job1['TARGET_DATE']) > new Date(job2['TARGET_DATE'])) {
						return 1;
					} else {
						return 0;
					}
					break;
				default:
					return 0;
			}
		});
		
		fillSort(ids);
	}
	
	/**
	  *	@desc	fill in sorted array
	  *	@param	array ids - sorted list of batch numbers
	  *	@return	none
	*/
	function fillSort(ids) {
		var tbody = document.getElementById("batch-table").children[1];
		var html = "";
		if (document.getElementById("filter-type")) {
			var value = document.getElementById("filter-type").value, keyword = document.getElementById("filter-input").value;
			if (value != "linecolor") {
				keyword = keyword.toUpperCase();
			}
		} else {
			value = "none";
			keyword = "";
		}
		
		if (!incoming) {
			for (var i=0;i<ids.length;i++) {
				if (isAllowed(keyword, value, jobs[ids[i]])) {
					var job = jobs[ids[i]][Object.keys(jobs[ids[i]]).pop()];
					html += `<tr id="${ids[i]}" onclick="selectRow(this)">
											<td class="col1">${job['TARGET_DATE']}</td>
											<td class="col2">${job['PACKING_SLIP']}</td>
											<td class="col3">${job['CUSTOMER']}</td>
											<td class="col4">${job['SELECT_OPERATOR']}</td>
											<td class="col5">${job['SELECT_DATE']}</td>
											<td class="col6">${job['SHIP_OPERATOR']}</td>
											<td class="col7">${job['SHIP_DATE']}</td>
										</tr>`;
				}
			}
		} else {
			for (var i=0;i<ids.length;i++) {
				if (isAllowed(keyword, value, incomingJobs[ids[i]])) {
					var job = incomingJobs[ids[i]][Object.keys(incomingJobs[ids[i]]).pop()];
					html += `<tr id="${ids[i]}" onclick="selectRow(this)">
											<td class="col1">${job['TARGET_DATE']}</td>
											<td class="col2">${job['PACKING_SLIP']}</td>
											<td class="col3">${job['CUSTOMER']}</td>
											<td class="col4">${job['SELECT_OPERATOR']}</td>
											<td class="col5">${job['SELECT_DATE']}</td>
											<td class="col6">${job['SHIP_OPERATOR']}</td>
											<td class="col7">${job['SHIP_DATE']}</td>
										</tr>`;
				}
			}
		}
		
		tbody.innerHTML = html;
		
		setColors();
	}
	
	/**
	  *	@desc	determine if row matches filter constraints
	  *	@param	string keyword - keyword to search for, string value - column to search in, array batch - batch to filter
	  *	@return	true if match, false otherwise
	*/
	function isAllowed(keyword, value, batch) {
		var valid = false;
		var job = batch[Object.keys(batch).pop()];
		switch(value) {
			case "customer":
				if (job['CUSTOMER'].toUpperCase().includes(keyword)) {
					valid = true;
				}
				break;
			case "linecolor":
				var color;
			
				if (job['SHIP_OPERATOR'] == "") {
					if (job['SELECT_OPERATOR'] == "") {
						color = "red";
					} else {
						color = "yellow";
					}
				} else {
					color = "green";
				}
				
				if (color == keyword) {
					valid = true;
				}
				break;
			case "packingslip":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,"."),"g").test(job['PACKING_SLIP'].toUpperCase()) && keyword.length == job['PACKING_SLIP'].length) {
						valid = true;
					}
				} else {
					if (job['PACKING_SLIP'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			case "targetdate":
				if (keyword.includes("X")) {
					if (RegExp(keyword.replace(/[X]/g,".").replace(/\//g,"\\/"),"g").test(job['TARGET_DATE'].toUpperCase())) {
						valid = true;
					}
				} else {
					if (job['TARGET_DATE'].toUpperCase().includes(keyword)) {
						valid = true;
					}
				}
				break;
			default:
				valid = true;
		}
		
		return valid;
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
	  *	@desc	go to Retrieve Tool page
	  *	@param	none
	  *	@return	none
	*/
	function retrieveTool() {
		var body = document.getElementsByTagName("body")[0];
		body.innerHTML += `<form id="retrieve-form" action="/view/retrieve.php" method="POST" style="display: none;"><input type="text" value="${window.location.pathname}" name="returnpath"><input type="text" value="${document.getElementById(selectedToolRow).children[0].innerHTML}" name="tool"></form>`;
		document.getElementById("retrieve-form").submit();
	}
	
	/**
	  *	@desc	switch between incoming and current work
	  *	@param	DOM Object bt - button to change label
	  *	@return	none
	*/
	function showIncoming(bt) {
		if (!incoming) {
			incoming = true;
			bt.innerHTML = "Current Work";
		} else {
			incoming = false;
			bt.innerHTML = "Incoming Work";
		}
		
		sortBy("none");
		
		document.getElementById("select-button").disabled = true;
		document.getElementById("print-button").disabled = true;
		document.getElementById("retrieve-button").disabled = true;
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return string date - MM/DD/YY
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
	  *	@desc	archiveJob handler
	  *	@param	none
	  *	@return	none
	*/
	function archiveJobs() {
		for (let batchNumber in jobs) {
			for (let id in jobs[batchNumber]) {
				var job = jobs[batchNumber][id];
				var outDate = new Date(formatDate(new Date(job['SHIP_DATE'])));
				var today = new Date(formatDate(new Date()));
				if (job['SHIP_OPERATOR'] != "" && job['SELECT_OPERATOR'] != '' && outDate < today) {
					archiveJob(job['ID']);
				}
			}
		}
		
		goToJob();
	}
	
	/**
	  *	@desc	move to previously selected job
	  *	@param	none
	  *	@return none
	*/
	function goToJob() {
		<?php if (isset($_POST['returnTool'])) { ?>
		document.getElementById("<?=$_POST['returnTool']?>").scrollIntoView();
		document.getElementById("<?=$_POST['returnTool']?>").click();
		<?php } ?>
	}
	
	/**
	  *	@desc	get job data to archive
	  *	@param	int id - DB ID to search for
	  *	@return	array containing job data
	*/
	function getJobToDelete(id) {
		var conn = new XMLHttpRequest();
		var action = "select";
		var table = "Shipping";
		var condition = "ID";
		var value = id;
		var job = {};
		
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
				
				job = response[0];
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&condition="+condition+"&value="+value, false);
		conn.send();
		
		return job;
	}
	
	/**
	  *	@desc	move job to archive
	  *	@param	int id - passed to getJobToDelete()
	  *	@return	none
	*/
	function archiveJob(id) {
		var job = getJobToDelete(id);
		var conn = new XMLHttpRequest();
		var table = "Shipping_History";
		var action = "insert";
		var query = "";
		
		Object.keys(job).forEach((item, index, array) => {
			if (item != 'ID' && job[item] != null && job[item] != undefined) {
				query += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
			}
		});
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Insert succeeded")) {
					deleteOldJob(id);
				} else {
					alert("Old job not archived. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	remove old job from current work
	  *	@param	int id - DB ID to search for
	  *	@return	none
	*/
	function deleteOldJob(id) {
		var conn = new XMLHttpRequest();
		var table = "Shipping";
		var action = "delete";
		var query = "&id="+id;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					return;
				} else {
					alert("Old job not removed from current work. Contact IT Support to correct. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?table="+table+"&action="+action+query, true);
		conn.send();
	}
	
	/**
	  *	@desc	cancel tool shipping job
	  *	@param	none
	  *	@return	none
	*/
	function returnTool() {
		var tool = document.getElementById(selectedToolRow).children[0].innerHTML;
		var conn = new XMLHttpRequest();
		var action = "delete";
		if (!incoming) {
			var table = "Shipping";
		} else {
			var table = "Shipping_Queue";
		}
		var query = "&ID="+selectedToolRow;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Tool returned to inventory");
					document.getElementById(selectedToolRow).parentNode.removeChild(document.getElementById(selectedToolRow));
				} else {
					alert("Could not return tool. Contact IT Support to correct.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, false);
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
	  *	@desc	auto-format filter date field to MM/DD/YY
	  *	@param	DOM Object input - date field to format
	  *	@return	none
	*/
	function fixFilterDate(input) {
		var key = event.keyCode || event.charCode;
		
		var regex = /\/|\-|\\|\*/;
		
		if (key==8 || key==46) {
			if (regex.test(input.value.slice(-1))) {
				input.value = input.value.slice(0,-1);
			}
		} else if (event.key == "Enter") {
			input.parentNode.nextElementSibling.click();
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
	  *	@desc	show filter options
	  *	@param	none
	  *	@return	none
	*/
	function showFilters() {
		var div = document.createElement("div");
		div.classList.add("filter-outer");
		div.innerHTML = `<div class="filter-inner">
							<div id="order-container">
								<span id="order-span">Order</span>
								<br>
								<select id="order-type" name="order-select">
									<option value="none">&lt;NONE&gt;</option>
									<option value="customer">Customer</option>
									<option value="linecolor">Line Color</option>
									<option value="packingslip">Packing Slip</option>
									<option value="targetdate">Target Date</option>
								</select>
							</div>
							<div id="filter-container">
								<span id="filter-span">Filter</span>
								<br>
								<select id="filter-type" name="filter-select" onchange="changeFilter(this)">
									<option value="none">&lt;NONE&gt;</option>
									<option value="customer">Customer</option>
									<option value="linecolor">Line Color</option>
									<option value="packingslip">Packing Slip</option>
									<option value="targetdate">Target Date</option>
								</select>
								<br>
								<input type="text" id="filter-input">
							</div>
							<button onclick="sortBy(document.getElementById('order-type').value)">Go</button>
						</div>`;
		document.getElementsByClassName("container")[0].appendChild(div);
		var arrow = document.getElementById("arrow");
		div.after(arrow);
		arrow.children[0].classList.remove("right-arrow");
		arrow.children[0].classList.add("left-arrow");
		arrow.setAttribute("onclick",'hideFilters()');
		
		setCookie("sort_expanded","true");
		
		if (checkCookie("sort_shipping_operations_order")) {
			document.getElementById("order-type").value = getCookie("sort_shipping_operations_order");
		}
		
		if (checkCookie("sort_shipping_operations_filter")) {
			document.getElementById("filter-type").value = getCookie("sort_shipping_operations_filter");
			changeFilter(document.getElementById("filter-type"));
		}
		
		if (checkCookie("sort_shipping_operations_filter_value")) {
			document.getElementById("filter-input").value = getCookie("sort_shipping_operations_filter_value");
		}
	}
	
	/**
	  *	@desc	hide filter options
	  *	@param	none
	  *	@return	none
	*/
	function hideFilters() {
		document.getElementsByClassName("container")[0].removeChild(document.getElementsByClassName("filter-outer")[0]);
		var arrow = document.getElementById("arrow");
		arrow.children[0].classList.add("right-arrow");
		arrow.children[0].classList.remove("left-arrow");
		arrow.setAttribute("onclick",'showFilters()');
		
		setCookie("sort_expanded","false");
	}
	
	/**
	  *	@desc	change filter field type
	  *	@param	none
	  *	@return	none
	*/
	function changeFilter(select) {
		var field = document.getElementById("filter-input");
		if (field) {
			document.getElementById("filter-container").removeChild(field);
		}
		if (select.value == "linecolor") {
			var select = document.createElement('select');
			select.id = "filter-input";
			select.innerHTML = `<option value="green">Black on Green</option><option value="yellow">Black on Yellow</option><option value="red">Black on Red</option>`;
			document.getElementById("filter-container").appendChild(select);
		} else {
			var input = document.createElement('input');
			input.type = "text";
			input.id = "filter-input";
			if (select.value == "targetdate") {
				input.setAttribute("onkeydown","fixFilterDate(this)");
			} else {
				input.onkeydown = function(e) {
					if (e.key == "Enter") {
						input.parentNode.nextElementSibling.click();
					}
				}
			}
			document.getElementById("filter-container").appendChild(input);
		}
	}
</script>
<html>
	<head>
		<title>Shipping</title>
		<link rel="stylesheet" type="text/css" href="/styles/shipping.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="setColors(); checkSortBox(); archiveJobs();">
		<div class="container">
			<div class="outer">
				<div class="inner">
					<div class="main">
						<table id="batch-table">
							<thead>
								<tr>
									<th class="col1">Target</th>
									<th class="col2">Packing Slip</th>
									<th class="col3">Customer</th>
									<th class="col4">Operator In</th>
									<th class="col5">Date</th>
									<th class="col6">Operator Out</th>
									<th class="col7">Date</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach($jobs as $batchNumber=>$batch) { 
								$job = reset($batch); ?>
								<tr id="<?=$batchNumber?>" onclick="selectRow(this)">
									<td class="col1"><?=date_format($job['TARGET_DATE'],'m/d/y')?></td>
									<td class="col2"><?=$job['PACKING_SLIP']?></td>
									<td class="col3"><?=$job['CUSTOMER']?></td>
									<td class="col4"><?=$job['SELECT_OPERATOR']?></td>
									<td class="col5"><?=date_format($job['SELECT_DATE'],'m/d/y')?></td>
									<td class="col6"><?=$job['SHIP_OPERATOR']?></td>
									<td class="col7"><?=date_format($job['SHIP_DATE'],'m/d/y')?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="left">
						<table id="tool-table">
							<thead>
								<tr>
									<th class="col1">Tools</th>
									<th class="col2">Belt #</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>
					<div class="controls">
						<button onclick="showIncoming(this)" title="This can take up to a minute. Be patient.">Incoming Work</button>
						<button id="select-button" onclick="processIn()" disabled>Select for Shipping</button>
						<button id="print-button" onclick="getDate()" disabled>Print Packing Slip</button>
						<button id="retrieve-button" onclick="retrieveTool()" disabled>Retrieve Tool</button>
						<button id="return-button" onclick="returnTool()" disabled>Return to Inventory</button>
						<a href="../operations.php">Back</a>
					</div>
				</div>
			</div>
			<div id="arrow" onclick="showFilters()">
		 		<div class="right-arrow">
				</div>
		 	</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>