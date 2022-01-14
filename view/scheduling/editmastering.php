<!DOCTYPE html>
<?php
/**
  *	@desc edit already existing mastering job
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
	
	//lists to choose job values from, and current job
	$toolTypes = array();
	$cosmetics = array();
	$designs = array();
	$blanks = array();
	$masters = array();
	$process = array();
	$job = array();
	$isQueue = false;
	
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
		
		$result = sqlsrv_query($conn, "SELECT * FROM Mastering WHERE ID = " . $_POST['id'] . ";");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$job = $row;
			}
		} else {
			print_r(sqlsrv_errors());
		}
		
		if (empty($job)) {
			$result = sqlsrv_query($conn, "SELECT * FROM Mastering_Queue WHERE ID = " . $_POST['id'] . ";");
			if ($result) {
				while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
					$job = $row;
					$isQueue = true;
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
	  *	@desc	insert current job data
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		if ("<?= $_SESSION['name'] ?>" != "eform" && "<?= $_SESSION['name'] ?>" != "master" && "<?= $_SESSION['name'] ?>" != "troom") {
			document.getElementById("operator-input").value = "<?= $_SESSION['initials'] ?>";
		}
		
		document.getElementById("wo-input").value = "<?=$job['WO_NUMBER']?>";
		document.getElementById("job-input").value = "<?=$job['JOB_NUMBER']?>";
		document.getElementById("po-input").value = "<?=$job['PO_NUMBER']?>";
		document.getElementById("date-input").value = "<?=date_format($job['DATE_IN'],'m/d/y')?>";
		document.getElementById("design-input").value = "<?=$job['TOOL_IN']?>";
		document.getElementById("special-textarea").value = `<?=$job['SPECIAL_INSTRUCTIONS']?>`;
		document.getElementById("size-input").value = "<?=$job['SIZE']?>";
		document.getElementById("tool-type-select").value = "<?=$job['TOOL_TYPE']?>";
		document.getElementById("cosmetics-select").value = "<?=$job['COSMETIC']?>";
		document.getElementById("recut-select").value = "<?=$job['WORK_TYPE']?>";
		<?php if ($job['IS_BLANK'] == "FALSE") { ?>
		document.getElementById("master-input").value = "<?=$job['BLANK']?>";
		document.getElementById("master-input").disabled = false;
		document.getElementById("blank-input").disabled = true;
		<?php } else { ?>
		document.getElementById("blank-input").value = "<?=$job['BLANK']?>";
		document.getElementById("blank-input").disabled = false;
		document.getElementById("master-input").disabled = true;
		<?php } ?>
	}
	
	/**
	  *	@desc	create/display list of designs to choose from
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
	  *	@desc	highlights design row
	  *	@param	DOM Object tr - design row clicked on
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
	  *	@desc	inserts design in job data
	  *	@param	DOM Object tr - design row clicked on
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
			if (item['LOCATION'].includes(searchText.toUpperCase())) {
				html += `<tr id="${item['ID']}" onclick="selectBlankRow(this)"><td class="col1">${item['BLANK']}</td><td class="col2">${item['LOCATION']}</td><td class="col3">${item['DRAWER']}</td></tr>`;
			}
		});
		html += `</tbody></table>`;
		
		modalContent.innerHTML = html;
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight blank row
	  *	@param	DOM Object tr - blank row clicked on
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
	  *	@desc	insert blank into job data
	  *	@param	DOM Object tr - blank row clicked on
	  *	@return	none
	*/
	function confirmBlank(tr) {
		document.getElementById("blank-input").value = tr.children[0].innerHTML;
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
		setTimeout(popMasterList, 200);
	}
	
	/**
	  *	@desc	create/display list of masters
	  *	@param	none
	  *	@return	none
	*/
	function popMasterList() {
		var searchText = document.getElementById("master-input").value;
		var modal = document.getElementById("modal");
		modal.style.display = "block";
		var modalContent = document.getElementById("modal-content");
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
	  *	@desc	highlight master row
	  *	@param	DOM Object tr - master row clicked on
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
	  *	@param	DOM Object tr - master row clicked on
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
	  *	@desc	set either recut-master or blank field to readOnly, depending on work type
	  *	@param	DOM Object select - value is work type
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
	  *	@desc	validates data to prevent errors
	  *	@param	none
	  *	@return	string msg - error message, if any
	*/
	function checkFields() {
		var msg = "";
		
		if (document.getElementById("date-input").value == "") {
			msg = "Invalid date";
		} else if (document.getElementById("design-input").value == "") {
			msg = "Please select a tool";
		} else if (document.getElementById("size-input").value == "") {
			msg = "Size cannot be empty";
		} else if (document.getElementById("master-input").value == "" && document.getElementById("blank-input").value == "") {
			msg = "Select either a blank or a re-cut master";
		}
		
		return msg;
	}
	
	/**
	  *	@desc	submits changes to job
	  *	@param	none
	  *	@return	none
	*/
	function saveJob() {
		var msg = checkFields();
		
		if (msg == "") {
			var d = new Date(document.getElementById("date-input").value);
			batch = {
				OPERATOR: document.getElementById("operator-input").value,
				MODIFIED_DATE: formatDate(new Date()),
				TARGET_DATE: document.getElementById("date-input").value,
				BATCH_INSTRUCTIONS: document.getElementById("special-textarea").value,
				id: <?=$_POST['batch']?>
			};
			
			var job = {
				PO_NUMBER: document.getElementById("po-input").value,
				JOB_NUMBER: document.getElementById("job-input").value,
				TOOL_IN: document.getElementById("design-input").value,
				TARGET_DATE: document.getElementById("date-input").value,
				DATE_IN: formatDate(new Date(d.setDate(d.getDate() - <?= $process['DURATION'] ?>))),
				DATE_OUT: document.getElementById("date-input").value,
				SPECIAL_INSTRUCTIONS: document.getElementById("special-textarea").value,
				SIZE: document.getElementById("size-input").value,
				TOOL_TYPE: document.getElementById("tool-type-select").value,
				COSMETIC: document.getElementById("cosmetics-select").value,
				WORK_TYPE: document.getElementById("recut-select").value,
				IS_BLANK: document.getElementById("recut-select").value == "Recut" ? "FALSE" : "TRUE",
				BLANK: document.getElementById("recut-select").value == "Recut" ? document.getElementById("master-input").value : document.getElementById("blank-input").value,
				id: <?=$_POST['id']?>
			};
			
			var conn1 = new XMLHttpRequest();
			var table1 = "Batches";
			var action1 = "update";
			var conn2 = new XMLHttpRequest();
			var table2 = "<?=$isQueue?>" ? "Mastering_Queue" : "Mastering";
			var action2 = "update";
			
			conn1.onreadystatechange = function() {
				if (conn1.readyState == 4 && conn1.status == 200) {
					if (conn1.responseText.includes("Data updated successfully")) {
						var query2 = "";
						
						conn2.onreadystatechange = function() {
							if (conn2.readyState == 4 && conn2.status == 200) {
								if (conn2.responseText.includes("Data updated successfully")) {
									alert("Job updated");
									<?php if ($_POST['source'] == 'holdlist') { ?>
									window.location.replace("holdlist.php");
									<?php } else { ?>
									window.location.replace("mastering.php");
									<?php } ?>
								} else {
									alert("Job not updated. Contact IT Support to correct. " + conn2.responsetText);
								}
							}
						}
						
						Object.keys(job).forEach((item, index, array) => {
							if (item != "id") {
								query2 += `&${item}=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							} else {
								query2 += `&condition=id&value=${job[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
							}
						})
						
						conn2.open("GET","/db_query/sql2.php?table="+table2+"&action="+action2+query2, true);
						conn2.send();
					} else {
						alert("Batch not updated. Contact IT Support to correct. " + conn1.responseText);
					}
				}
			}
			
			var query1 = "";
			
			Object.keys(batch).forEach((item, index, array) => {
				if (item != "id") {
					query1 += `&${item}=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				} else {
					query1 += `&condition=id&value=${batch[item].toString().replace(/[#]/g,"%23").replace(/[+]/g,"%2B").replace(/[&]/g,"%26").replace(/\n/g,"%0A")}`;
				}
			})
			
			conn1.open("GET","/db_query/sql2.php?table="+table1+"&action="+action1+query1, true);
			conn1.send();
		} else {
			alert(msg);
		}
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to be converted
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
	  *	@desc	create/display details of selected design, or else error message
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
							var result = conn.responseText.split("Array")
							result.shift();
							for (var i=0;i<result.length;i++) {
								result[i] = result[i].split(">");
								result[i].shift();
								for (var j=0;j<result[i].length;j++) {
									result[i][j] = result[i][j].split("[")[0];
									if (j==result[i].length-1) {
										result[i][j] = result[i][j].split(")")[0];
									}
									result[i][j] = result[i][j].trim();
								}
							}
							
							html += `<div style="display: inline-block;">
								<div style="display: inline-block;">
									<div style="display: inline-block;">
										<span style="margin-left: 78px;">Design<input type="text" id="design" readonly value="${result[0][1]}">
										Designer<input type="text" id="designer" readonly value="${result[0][32]}">
										Date<input type="text" id="date" readonly value="${result[0][31]}">
										Drawing #<input type="text" id="drawing" readonly value="${result[0][2]}"></span>
									</div><br>
									<div style="display: inline-block;">
										<span style="margin-left: 58px;">File Name<input type="text" id="file" readonly value="${result[0][3]}"></span><br>
										<div style="display: inline-block; text-align: right;">
											Fresnel Conjugate<input type="text" id="fresnel" readonly value="${result[0][4]}"><span class="units">(in)</span><br>
											Plano Conjugate<input type="text" id="plano" readonly value="${result[0][5]}"><span class="units">(in)</span><br>
											Focal Length<input type="text" id="focal" readonly value="${result[0][6]}"><span class="units">(in)</span><br>
											Number of Grooves<input type="text" id="grooves" style="margin-right: 20px;" readonly value="${result[0][7]}"><br>
											Master Pitch<input type="text" id="pitch" readonly value="${result[0][8]}"><span class="units">(in)</span><br>
											Radius<input type="text" id="radius" readonly value="${result[0][10]}"><span class="units">(in)</span><br>
											Lens Diameter<input type="text" id="diameter" readonly value="${result[0][11]}"><span class="units">(in)</span>
										</div>
										<div style="display: inline-block; text-align: right;">
											Maximum Slope<input type="text" id="slope" readonly value="${result[0][12]}"><input type="text" id="slope2" readonly><br>
											Maximum Draft<input type="text" id="max-draft" readonly value="${result[0][13]}"><input type="text" id="max-draft2" readonly><br>
											Angle of Diamo. Tool<input type="text" id="tool-angle" readonly value="${result[0][14]}"><input type="text" id="tool-angle2" readonly><br>
											Maximum Groove Depth<input type="text" id="max-depth" readonly value="${result[0][15]}"><span class="units" style="margin-right: 89px;">(in)</span><br>
											Minimum Draft<input type="text" id="min-draft" readonly value="${result[0][16]}"><input type="text" id="min-draft2" readonly><br>
											Prism Angle<input type="text" id="prism" readonly value="${result[0][17]}"><input type="text" id="prism2" readonly>
										</div>
									</div>
								</div>
							</div><br>
							<div style="display: inline-block;">
								<span style="margin-left: 45px;">Prism Depth<input type="text" id="prism-depth" readonly value="${result[0][18]}">(in)</span>
								<span style="margin-left: 94px;">Tilt Angle<input type="text" id="tilt-angle" readonly value="${result[0][19]}"><input type="text" id="tilt-angle2" readonly></span><br><br>
								<div style="display: inline-block; vertical-align: top;">
									<span style="position: relative; top: 3px;">Pitch</span><br>
									<span style="position: relative; top: 10px;">Groove Angle</span><br>
									<span style="position: relative; top: 17px;">Base Angle</span><br>
								</div>
								<div style="display: inline-block; width: 220px;">
									<input type="text" id="pitch1" readonly value="${result[0][20]}"><span class="units">(in)</span>
									<input type="text" id="groove-angle1-1" readonly value="${result[0][23]}"><input type="text" id="groove-angle1-2" readonly>
									<input type="text" id="base-angle1-1" readonly value="${result[0][26]}"><input type="text" id="base-angle1-2" readonly>
								</div>
								<div style="display: inline-block; width: 220px;">
									<input type="text" id="pitch2" readonly value="${result[0][21]}"><span class="units">(in)</span>
									<input type="text" id="groove-angle2-1" readonly value="${result[0][24]}"><input type="text" id="groove-angle2-2" readonly>
									<input type="text" id="base-angle2-1" readonly value="${result[0][27]}"><input type="text" id="base-angle2-2" readonly>
								</div>
								<div style="display: inline-block; width: 220px;">
									<input type="text" id="pitch3" readonly value="${result[0][22]}"><span class="units">(in)</span>
									<input type="text" id="groove-angle3-1" readonly value="${result[0][25]}"><input type="text" id="groove-angle3-2" readonly>
									<input type="text" id="base-angle3-1" readonly value="${result[0][28]}"><input type="text" id="base-angle3-2" readonly>
								</div><br><br>
								<span style="margin-left: 64px; vertical-align: top;">Features:</span><textarea rows="4" cols="80" style="margin-left: 5px;" id="features-textarea" readonly>${result[0][29]}</textarea>
							</div><br>
							<div style="display: inline-block;">
								<span style="margin-left: 52px; vertical-align: top;">Comments:</span><textarea rows="4" cols="80" style="margin: 3px 4px;" id="comment-textarea" readonly>${result[0][30]}</textarea>
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
</script>
<html>
	<head>
		<title>Edit Mastering Job</title>
		<link rel="stylesheet" type="text/css" href="/styles/scheduling/editmastering.css">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="basic-info">
					<span id="po-span">PO #<input type="text" id="po-input"></span><br>
					<span id="job-span">Job #<input type="text" id="job-input"></span><span id="wo-span">WO #<input type="text" id="wo-input" readonly></span><br>
					<span id="design-span">Design<input type="text" id="design-input"></span><button onclick="popDesignList()" id="design-search-button">Search</button><button onclick="designDetails()" style="margin-left: 5px;">Design Info</button><br>
					<span id="drawing-span">Drawing<input type="text" id="drawing-input" readonly></span><span id="file-span">File Name<input type="text" id="file-input" readonly></span><br>
					<span id="date-span">Target Date<input onblur="this.value = this.value.toUpperCase();" onkeydown="fixDate(this)" type="text" id="date-input"></span><span id="operator-span">Operator<input type="text" id="operator-input"></span>
				</div>
				<div class="controls">
					<button onclick="saveJob()">Save</button>
					<a href="<?php if ($_POST['source'] == "holdlist") { echo 'holdlist.php'; } else { echo 'mastering.php'; } ?>">Back</a>
				</div>
				<div class="design-info">
					<span id="recut-span"><select id="recut-select" oninput="setWorkType(this)">
						<option value="New">New</option>
						<option value="Recut">Recut</option>
						<option value="Reuse">Reuse</option>
					</select></span><br>
					<span id="blank-span">Blank<input type="text" id="blank-input"></span><button onclick="popBlankList()" id="blank-search-button">Search</button><br>
					<span id="master-span">Recut Master<input type="text" id="master-input" disabled></span><button onclick="wait()" id="master-search-button">Search</button><br>
					<span id="size-span">Size<input type="text" id="size-input"></span><span id="unit">(in)</span><br>
					<span id="tool-type-span">Type<select id="tool-type-select">
					<?php foreach($toolTypes as $toolType) { ?>
						<option value="<?=$toolType['TOOLTYPE']?>"><?=$toolType['TOOLTYPE']?></option>
					<?php } ?>
					</select></span><br>
					<span id="cosmetics-span">Cosmetics<select id="cosmetics-select">
					<?php foreach($cosmetics as $cosmetic) { ?>
						<option value="<?=$cosmetic['COSMETIC']?>"><?=$cosmetic['COSMETIC']?></option>
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

