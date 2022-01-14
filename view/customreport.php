<?php
/**
  * @desc for resolving custom report queries
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//result
	$response = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, $_REQUEST['query']);
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				foreach($row as $key => $value) {
					if ($value instanceof DateTime) {
						$row[$key] = date_format($value,'m/d/y H:i');
					}
				}
				$response[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		var_dump(sqlsrv_errors());
	}
?>
<html>
	<head>
		<title>Custom Report</title>
		<style>
			table {
				font-size: 14px;
				font-family: sans-serif;
				text-align: left;
			}
			
			td, th {
				padding: 5px;
			}
		</style>
	</head>
	<body>
		<table>
			<thead>
				<tr>
					<th>
					<?php echo implode('</th><th>',explode(',',$_REQUEST['columns'])); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($response as $row) {
					echo '<tr><td>' . implode('</td><td>',$row) . '</td></tr>';
				} ?>
			</tbody>
		</table>
	</body>
</html>