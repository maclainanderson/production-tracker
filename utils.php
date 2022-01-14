<!DOCTYPE html>
<?php
/**
  *	User lists and system parameters
*/
	//setup connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	$admins = array();
	$schedulers = array();
	
	if ($conn) {
		$result = sqlsrv_query($conn,"SELECT * FROM Admins;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$admins[$row['Name']] = $row['Username'];
			}
		}
		
		$result = sqlsrv_query($conn,"SELECT * FROM Schedulers;");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$schedulers[$row['Name']] = $row['Username'];
			}
		}
		
		$schedulers['Scheduler Account'] = 'sched';
	}
	
	$address = array("Name" => "ORAFOL Precision Technology Solutions","Address" => "200 Park Centre Drive","City"=>"Henrietta","State"=>"NY","ZIP"=>"14586","Phone"=>"(585)272-0309","Fax"=>"(585)272-0313");
	$security = array("Change Location"=>9,"Electroforming"=>9,"Measurements"=>9,"Quality History"=>9,"Comment History"=>9,"Process In"=>9,"Process Out"=>9,"Master Diameter"=>9,"Sec. Operations"=>9,"Print Traveler"=>9,"Work Order"=>9,"Work In Progress"=>9);
	$nextWO = "0000207592";
	$nextBatch = "30913";
	$nextSlip = "0000007371";
	$path = "C:\\ALL3";
	$clockIsActive = true;
	$clockType = "24";
	$workOrderAge = 370;
	$tankStatusAge = 185;
	$refreshInterval = 120;
	$includeAngle = "51:00:00";
?>