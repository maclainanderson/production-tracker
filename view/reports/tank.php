<?php

//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
//create report
	if ($conn) {
		$body = "";
		$today = date_create("now");
		$date = date_create("now");
		
		//debugging purposes
		//date_sub($today, date_interval_create_from_date_string('1 day'));
		//date_sub($date, date_interval_create_from_date_string('1 day'));
		
		date_sub($date, date_interval_create_from_date_string('6 months'));
		$body .= "<style>
					html { font-family: sans-serif; }
					th { text-align: right; }
					td { text-align: right; }
					.col1 { width: 50px; }
					.col2 { width: 70px; }
					.col3 { width: 70px; }
					.col4 { width: 150px; }
					.col5 { width: 100px; }
				  </style>";
		$body .= "Tank Stress Report";
		
	//get list of tanks
		$tankMaint = array();
		
		$tanks = array();
		$result = sqlsrv_query($conn, "SELECT TANK, MAINTENANCE_DATE FROM Valid_Tanks WHERE TANK > 12 AND TANK < 34 ORDER BY TANK ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				$tanks[] = $row[0];
				$tankMaint[$row[0]] = $row[1];
			}
		}
	//remove duplicates
		$tanks = array_unique($tanks);
		$tanks = array_values($tanks);

	//set tank numbers as arrays instead of strings
		for($i=0;$i<count($tanks);$i++) {
			$tanks[$i] = str_split($tanks[$i],100);
		}
		
		
	//get readings
		$result = sqlsrv_query($conn, "SELECT ID, TANK, UNITS, DATE FROM Tank_Stress WHERE CONVERT(datetime, '" . date_format($date, "m/d/y") . "', 1) < CONVERT(datetime, DATE, 1) ORDER BY TANK ASC, CONVERT(datetime, DATE, 1) DESC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				foreach($tanks as $id => $tank) {
					
					if ($tank[0] == $row[1]) {
						$tanks[$id][] = $row;
					}
					
				}
			}
		}
		
	//get KAmpHrs
		foreach($tanks as $id => $tank) {
			array_splice($tanks[$id], 1, 0, 0);
		}
		$result = sqlsrv_query($conn, "SELECT ID, TANK, FORMING_CURRENT, FORMING_TIME, BUILDING_CURRENT, CYCLE_TIME, DATE_IN FROM Electroforming_History ORDER BY TANK ASC;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
				
				foreach($tanks as $id => $tank) {
					
					if ($tank[0] == $row[1]) {
						$d = $row[6];
						$d2 = $tankMaint["$tank[0]"];
						if ($d2 < $d) {
							$formCurrent = (float)$row[2];
							$formTime = (float)$row[3];
							$buildCurrent = (float)$row[4];
							$buildTime = ((float)explode(":",$row[5])[0]) * 60 + ((float)explode(":",$row[5])[1]) - $formTime;
							$kA = ($formCurrent * ($formTime / 60) + $buildCurrent * ($buildTime / 60)) / 1000;
							$tanks[$id][1] += $kA;
						}
					}
					
				}
			}
		}
		
	//remove tanks with no data, reorder tanks
		$idsToRemove = array();
		foreach($tanks as $id => $tank) {
			if (count($tank) < 3) {
				$idsToRemove[] = $id;
			}
		}
		
		foreach($idsToRemove as $id) {
			unset($tanks[$id]);
		}
		
		$tanks = array_values($tanks);
		
	//find stressed tanks
		$stressedTanks = array();
		
		foreach($tanks as $tank) {
			if (isset($tank[2]) && isset($tank[3])) {
				if (abs($tank[2][2] - $tank[3][2]) > 4) {
					$stressedTanks[] = $tank[0];
				}
			}
		}
		
	//find historically stressed tanks
		$oldStressedTanks = array();
		
		foreach($tanks as $id => $tank) {
			$varianceCount = 0;
			for ($i=count($tank)-1;$i>2;$i--) {
				if (abs($tank[$i][2] - $tank[$i-1][2]) >= 5) {
					$varianceCount++;
				}
			}
			
			if (count($tank) > 3) {
				array_splice($tanks[$id], 2, 0, [$varianceCount / (count($tank)-3)]);	//add % to tank data
			} else {
				array_splice($tanks[$id], 2, 0, [0]); //assume 0%
			}
			
			array_splice($tanks[$id], 3, 0, [count($tank)-3]); 	//add total readings to tank data
			
			if ($varianceCount / (count($tank)-3) > 0.1) {
				$oldStressedTanks[] = $tank[0];
			}
		}
		
	//find outdated readings
		$outdatedReadings = array();
		
		foreach($tanks as $tank) {
			if ($tank[4][3] < date_sub(date_create("now"), date_interval_create_from_date_string('1 day'))) {
				$outdatedReadings[] = $tank[0];
			}
		}
		
	//build output
		
		$body .= "<br><br>Tank(s) with stress value changes greater than 4 since last reading are:<br>";
		
		foreach($stressedTanks as $tank) {
			$body .= $tank . "<br>";
		}
		
		$body .= "<br>Tank(s) with excessive historical variation are:<br>";
		
		foreach($oldStressedTanks as $tank) {
			$body .= $tank . "<br>";
		}
		
		$body .= "<br>Tank(s) over 1 day since last stress reading are:<br>";
		
		foreach($outdatedReadings as $tank) {
			$body .= $tank . "<br>";
		}
		
		$body .= "<br>Summary:<br><table><thead><tr><th class=\"col1\">Tank</th><th class=\"col2\">Stress</th><th class=\"col3\">Change</th><th class=\"col4\">Date of Last Reading</th></tr></thead><tbody>";
		
		foreach($tanks as $tank) {
			$body .= "<tr><td class=\"col1\">" . $tank[0] . "</td><td class=\"col2\">" . round($tank[4][2],1) . "</td><td class=\"col3\">" . ($tank[4][2] - $tank[5][2]) . "</td><td class=\"col4\">" . date_format($tank[4][3],'m/d/y') . "</td></tr>";
		}
		
		$body .= "</tbody></table><br><table><thead><tr><th class=\"col1\">Tank</th><th class=\"col2\">Points</th><th class=\"col3\">%>4.5</th><th class=\"col4\">Maint. Date</th><th class=\"col5\">KAmpHrs</th></tr></thead><tbody>";
		
		foreach($tanks as $tank) {
			$body .= "<tr><td class=\"col1\">" . $tank[0] . "</td><td class=\"col2\">" . $tank[3] . "</td><td class=\"col3\">" . round($tank[2] * 100, 2) . "%</td><td class=\"col4\">" . date_format($tankMaint["$tank[0]"],'m/d/y') . "</td><td class=\"col5\">" . round($tank[1]) . "</td></tr>";
		}
		
		$body .= "</tbody></table>";
		
		echo $body;
	}
?>



































