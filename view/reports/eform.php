<?php

//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
//create report
	if ($conn) {
		$body = "";
		$today = date_create("now");
		$yesterday = date_create("now");
		$oneWeekAgo = date_create("now");
		
		date_sub($yesterday, date_interval_create_from_date_string('1 day'));
		date_sub($oneWeekAgo, date_interval_create_from_date_string('7 days'));
		
		//debugging purposes:
		//date_sub($today, date_interval_create_from_date_string('3 days'));
		//date_sub($yesterday, date_interval_create_from_date_string('4 days'));
		//date_sub($oneWeekAgo, date_interval_create_from_date_string('10 days'));
		
		$body .= "<style>
					html { font-family: sans-serif; }
					th { text-align: left; }
					.col1 { width: 400px; }
					.col2 { width: 100px; }
					.col3 { width: 100px; }
				  </style>";
		$body .= "Electroforming Daily Report";
		
	//parts tanked out
		$body .= "<br><br>PARTS TANKED OUT ON " . date_format($yesterday, "m/d/y") . ":<br><br>";
		
		$toolsOut = array();
		$params = array($yesterday);
		$result = sqlsrv_query($conn, "SELECT TOOL_OUT, TANK, STATUS_OUT, THICKNESS1, THICKNESS2, THICKNESS3, THICKNESS4, THICKNESS5, THICKNESS6, TARGET_THICKNESS FROM Electroforming_History WHERE CAST(DATE_OUT as DATE) = CONVERT(DATE,'" . date_format($yesterday,'m/d/y') . "',1);");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$toolsOut[] = $row;
			}
		}
		//var_dump($toolsOut);
		
		$body .= "<table><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Tank</th><th class=\"col3\">Status</th></tr></thead><tbody>";
		foreach($toolsOut as $tool) {
			$body .= "<tr><td class=\"col1\">" . $tool[0] . "</td><td class=\"col2\">" . $tool[1] . "</td><td class=\"col3\">" . $tool[2] . "</td></tr>";
		}
		$body .= "</tbody></table>";
	
	//overdue parts
		$body .= "<br><br>THE FOLLOWING PARTS ARE OVERDUE FOR TANKOUT<br><br>";
		
		$overdueTools = array();
		$result = sqlsrv_query($conn, "SELECT TOOL_OUT, TANK, DATE_OUT FROM Electroforming WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '' AND (OPERATOR_OUT IS NULL OR OPERATOR_OUT = '') AND DATE_OUT < CONVERT(datetime,'" . date_format($today, "m/d/y") . "',1);");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$overdueTools[] = $row;
			}
		}
		
		$body .= "<table><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Tank</th><th class=\"col3\">Date Out</th></tr></thead><tbody>";
		foreach($overdueTools as $tool) {
			$body .= "<tr><td class=\"col1\">" . $tool[0] . "</td><td class=\"col2\">" . $tool[1] . "</td><td class=\"col3\">" . date_format($tool[2],'m/d/y H:i') . "</td></tr>";
		}
		$body .= "</tbody></table>";
		
	//parts tanked in
		$body .= "<br><br>PARTS TANKED IN ON " . date_format($yesterday, "m/d/y") . ":<br><br>";
		
		$toolsIn = array();
		$result = sqlsrv_query($conn, "SELECT TOOL_OUT, TANK, CYCLE_TIME FROM Electroforming WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '' AND CAST(DATE_IN as DATE) = CONVERT(DATE,'" . date_format($yesterday, "m/d/y") . "',1);");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$toolsIn[] = $row;
			}
		}
		
		$body .= "<table><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Tank</th><th class=\"col3\">Cycle Time</th></tr></thead><tbody>";
		foreach($toolsIn as $tool) {
			$body .= "<tr><td class=\"col1\">" . $tool[0] . "</td><td class=\"col2\">" . $tool[1] . "</td><td class=\"col3\">" . $tool[2] . "</td></tr>";
		}
		$body .= "</tbody></table>";
		
	//no stress data
		$body .= "<br><br>PARTS TANKED IN DURING LAST 7 DAYS WITH NO STRESS VALUE ENTERED:<br><br>";
		
		$toolsLastSeven = array();
		$result = sqlsrv_query($conn, "SELECT TOOL_OUT, TANK, DATE_IN FROM Electroforming WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '' AND CONVERT(datetime, '" . date_format($oneWeekAgo, "m/d/y") . "', 1) < DATE_IN;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$toolsLastSeven[] = $row;
			}
		}
		
		$result = sqlsrv_query($conn, "SELECT TOOL_OUT, TANK, DATE_IN FROM Electroforming_History WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '' AND CONVERT(datetime, '" . date_format($oneWeekAgo, "m/d/y") . "', 1) < DATE_IN;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$toolsLastSeven[] = $row;
			}
		}
		
		$body .= "<table><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Tank</th><th class=\"col3\">Date In</th></tr></thead><tbody>";
		foreach($toolsLastSeven as $tool) {
			$tank = array();
			$result = sqlsrv_query($conn, "SELECT * FROM Tank_Stress WHERE CAST(DATE as DATE) = CONVERT(DATE,'" . date_format($tool[2],'m/d/y') . "',1) AND TANK = " . $tool[1] . ";");
			if ($result) {
				if (!sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
					$body .= "<tr><td class=\"col1\">" . $tool[0] . "</td><td class=\"col2\">" . $tool[1] . "</td><td class=\"col3\">" . date_format($tool[2],'m/d/y H:i') . "</td></tr>";
				}
			}
		}
		$body .= "</tbody></table>";
	
	//due out today
		$body .= "<br><br>PARTS DUE OUT TODAY, " . date_format($today, "m/d/y") . ":<br><br>";
		
		$toolsDueOut = array();
		$result = sqlsrv_query($conn, "SELECT TOOL_OUT, TANK, DATE_OUT FROM Electroforming WHERE CAST(DATE_OUT as DATE) = CONVERT(DATE,'" . date_format($today, "m/d/y") . "',1) AND OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$toolsDueOut[] = $row;
			}
		}
		
		$body .= "<table><thead><tr><th class=\"col1\">Tool</th><th class=\"col2\">Tank</th><th class=\"col3\">Time Out</th></tr></thead><tbody>";
		foreach($toolsDueOut as $tool) {
			$body .= "<tr><td class=\"col1\">" . $tool[0] . "</td><td class=\"col2\">" . $tool[1] . "</td><td class=\"col3\">" . date_format($tool[2],'H:i:s') . "</td></tr>";
		}
		$body .= "</tbody></table>";
		
	//parts in progress
		$body .= "<br><br>PARTS IN PROGRESS:<br><br>";
		
		$toolsInProgress = array();
		$result = sqlsrv_query($conn, "SELECT TANK, STATION, DATE_OUT, TOOL_OUT, MODE, BUILDING_CURRENT, FORMING_TIME, FORMING_CURRENT, DATE_IN FROM Electroforming WHERE OPERATOR_IN IS NOT NULL AND OPERATOR_IN <> '';");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$toolsInProgress[] = $row;
			}
		}
		
		foreach($toolsInProgress as $tool) {
			$body .= $tool[3] . "<br>";
			$body .= "TANK=" . $tool[0] . " STATION=" . $tool[1] . " DATE OUT=" . date_format($tool[2],'m/d/y H:i') . "<br>";
			if ($tool[4] == "FORM") {
				$d = $tool[8];
				if ($d === false) {
					$d = $tool[8];
				}
				date_add($d, date_interval_create_from_date_string(floor($tool[6]) . " minutes"));
				$body .= "MODE=" . $tool[4] . " FORM CURRENT=" . $tool[7] . "<br>";
				$body .= "BUILD CYCLE STARTS=" . date_format($d, "m/d/y H:i:s") . " BUILD CURRENT=" . $tool[5] . "<br><br><br>";
			} else {
				$body .= "MODE=" . $tool[4] . " BUILD CURRENT=" . $tool[5] . "<br><br><br>";
			}
		}
		
		echo $body;
	}
?>