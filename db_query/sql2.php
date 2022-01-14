<?php

	//fetch permissions lists
	require_once('../utils.php');
	
	//start session
	session_start();
	
	//set up file output for errors
	$filename = "sql_err.txt";
	$handle = false;
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	
	if (isset($_REQUEST['q']) || isset($_REQUEST['action'])) {
	
		//establish connection
		$conn = sqlsrv_connect($serverName, $connectionInfo);
		
		if (file_exists($filename)) {
			if (is_writable($filename)) {
				$handle = fopen($filename, 'a');
				if (!$handle) {
					echo "Cannot open file for error output";
				} else {
					if ($conn) {
						if (isset($_REQUEST['action'])) {
							$keys = array_keys($_REQUEST);
							$values = array_values($_REQUEST);
							$params = array();
							$query = "";
							
							if ($_REQUEST['action'] == "insert") {
								$query .= "INSERT INTO " . $_REQUEST['table'] . " (" . $keys[2];
								for($i=3;$i<count($keys);$i++) {
									if ($keys[$i] != "id" && $keys[$i] != 'condition' && $keys[$i] != 'value') {
										$query .= ", $keys[$i]";
									}
								}
								$query .= ") VALUES (?";
								$params[] = $values[2];
								for($i=3;$i<count($values);$i++) {
									if ($keys[$i] != "id" && $keys[$i] != 'condition' && $keys[$i] != 'value') {
										$query .= ", ?";
										if ($values[$i] == "") {
											$params[] = null;
										} else {
											if ($date = date_create_from_format("Y-m-d H:i:s.u",$values[$i])) {
												$params[] = date_format($date,"Y-m-d H:i:s");
											} else {
												$params[] = $values[$i];
											}
										}
									}
								}
								$query .= ");";
								//echo $query . "<br>";
								//print_r($params);
								
								$result = sqlsrv_query($conn, $query, $params);
								if ($result) {
									echo "Insert succeeded.";
									fwrite($handle, date("Y/m/d H:i:s") . ": Successful query: " . $query . "\nParams: " . implode(",",$params) . "\n");
								} else {
									echo "Insert failed.";
									if ($handle !== false) {
										fwrite($handle, date("Y/m/d H:i:s") . ": Failed query: " . explode("]",sqlsrv_errors()[0]['message'])[3] . "\nQuery: " . $query . "\nParams: " . implode(",",$params) . "\n");
									}
								}
							}
							
							if ($_REQUEST['action'] == "update") {
								$query .= "UPDATE " . $_REQUEST['table'] . " SET " . $keys[2] . " = ?";
								if ($values[2] == "") {
									$params[] = null;
								} else {
									$params[] = $values[2];
								}
								for($i=3;$i<count($keys);$i++) {
									if (strpos($keys[$i],'condition') === false && strpos($keys[$i],'value') === false) {
										$query .= "," . $keys[$i] . " = ?";
										if ($values[$i] == "") {
											$params[] = null;
										} else {
											$params[] = $values[$i];
										}
									}
								}
								if ($_REQUEST['value'] != "null") {
									$params[] = $_REQUEST['value'];
								}
								$query .= " WHERE " . $_REQUEST['condition'];
								$query .= (strpos($_REQUEST['value'],"%") === false) ? ($_REQUEST['value'] == "null" ? " IS NULL" : " = ?") : " LIKE ?";
								if (isset($_REQUEST['condition2'])) {
									if ($_REQUEST['value2'] != "null") {
										$params[] = $_REQUEST['value2'];
									}
									$query .= " AND " . $_REQUEST['condition2'];
									$query .= (strpos($_REQUEST['value2'],"%") === false) ? ($_REQUEST['value2'] == "null" ? " IS NULL" : " = ?") : " LIKE ?";
								} else {
									$query .= ";";
								}
								
								//echo $query
								$result = sqlsrv_query($conn, $query, $params);
								if ($result) {
									echo "Data updated successfully.";
									fwrite($handle, date("Y/m/d H:i:s") . ": Successful query: " . $query . "\nParams: " . implode(",",$params) . "\n");
								} else {
									echo "Data update failed.";
									if ($handle !== false) {
										fwrite($handle, date("Y/m/d H:i:s") . ": Failed query: " . explode("]",sqlsrv_errors()[0]['message'])[3] . "\nQuery: " . $query . "\nParams: " . implode(",",$params) . "\n");
									}
								}
							}
							
							if ($_REQUEST['action'] == "delete") {
								$query .= "DELETE FROM " . $_REQUEST['table'] . " WHERE ";
								for ($i=2;$i<=count($keys)-1;$i++) {
									if ($i == count($keys)-1) {
										if ($values[$i] == 'null') {
											$query .= $keys[$i] . " IS NULL;";
										} else {
											$query .= $keys[$i] . " = ?;";
											$params[] = $values[$i];
										}
									} else {
										if ($values[$i] == 'null') {
											$query .= $keys[$i] . " IS NULL AND ";
										} else {
											$query .= $keys[$i] . " = ? AND ";
											$params[] = $values[$i];
										}
									}
								}
								$result = sqlsrv_query($conn, $query, $params);
								if ($result) {
									echo "Deletion succeeded.";
									fwrite($handle, date("Y/m/d H:i:s") . ": Successful query: " . $query . "\nParams: " . implode(",",$params) . "\n");
								} else {
									echo "Deletion failed.";
									if ($handle !== false) {
										fwrite($handle, date("Y/m/d H:i:s") . ": Failed query: " . explode("]",sqlsrv_errors()[0]['message'])[3] . "\nQuery: " . $query . "\nParams: " . implode(",",$params) . "\n");
									}
								}
							}
							
							if ($_REQUEST['action'] == "select") {
								if (isset($_REQUEST['date_range']) && $_REQUEST['date_range'] == 'true') {
									$query .= "SELECT * FROM " . $_REQUEST['table'] . " 
									WHERE (CONVERT(datetime, " . $_REQUEST['condition'] . ", 1) !> DATEADD(d,1,CONVERT(datetime, '" . $_REQUEST['value'] . "', 1)) 
									OR " . $_REQUEST['condition'] . " = '' 
									OR " . $_REQUEST['condition'] . " = null) 
									AND (CONVERT(datetime, " . $_REQUEST['condition2'] . ", 1) !< CONVERT(datetime, '" . $_REQUEST['value'] . "', 1)
									OR " . $_REQUEST['condition2'] . " = ''
									OR " . $_REQUEST['condition2'] . " = null)";
									if (isset($_REQUEST['ORDER_BY'])) {
										$query .= " ORDER BY " . $_REQUEST['ORDER_BY'];
									}
									$query .= ";";
								} else if (strpos($_REQUEST['value'], "%") !== false || strpos($_REQUEST['value'], "[") !== false) {
									$query .= "SELECT * FROM " . $_REQUEST['table'] . " WHERE " . $_REQUEST['condition'] . " LIKE '" . $_REQUEST['value'] . "'";
									if (isset($_REQUEST['ORDER_BY'])) {
										$query .= " ORDER BY " . $_REQUEST['ORDER_BY'];
									}
									$query .= ";";
								} else if (strpos($_REQUEST['value'], "SELECT")) {
									$query .= "SELECT * FROM " . $_REQUEST['table'] . " WHERE " . $_REQUEST['condition'] . " = " . $_REQUEST['value'];
									if (isset($_REQUEST['ORDER_BY'])) {
										$query .= " ORDER BY " . $_REQUEST['ORDER_BY'];
									}
									$query .= ";";
								} else {
									$query .= "SELECT * FROM " . $_REQUEST['table'] . " WHERE " . $_REQUEST['condition'] . " = ?";
									if (isset($_REQUEST['condition2'])) {
										$query .= " AND " . $_REQUEST['condition2'] . " = ?";
									}
									if (isset($_REQUEST['ORDER_BY'])) {
										$query .= " ORDER BY " . $_REQUEST['ORDER_BY'];
									}
									$query .= ";";
								}
								$params = array($_REQUEST['value']);
								if (isset($_REQUEST['value2'])) {
									$params[] = $_REQUEST['value2'];
								}
								$result = sqlsrv_query($conn, $query, $params);
								$results = array();
								if ($result) {
									while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
										$results[] = $row;
									}
									echo json_encode($results);
									fwrite($handle, date("Y/m/d H:i:s") . ": Successful query: " . $query . "\nParams: " . implode(",",$params) . "\n");
								} else {
									echo "Select failed.";
									if ($handle !== false) {
										fwrite($handle, date("Y/m/d H:i:s") . ": Failed query: " . explode("]",sqlsrv_errors()[0]['message'])[3] . "\nQuery: " . $query . "\nParams: " . implode(",",$params) . "\n");
									}
								}
							}
						}
					}
					fclose($handle);
				}
			} else {
				echo "Error output file is not writable";
			}
		} else {
			echo "Error output file does not exist";
		}
		
		sqlsrv_close($conn);
	}
?>