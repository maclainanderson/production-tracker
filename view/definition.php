<!DOCTYPE html>
<?php
/**
  *	@desc defining new reports (not currently used)
*/
	//get user lists
	require_once("../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
	}
?>
<html>
	<head>
		<title>Report Definition</title>
		<link rel="stylesheet" type="text/css" href="/styles/definition.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="header"><h2>Report Definitions</h2></div>
				<select name="report">
					<option value="quality">Quality</option>
					<option value="eformingyield">E-Forming Yield</option>
					<option value="historicalstressdata">Historical Stress Data</option>
					<option value="commenthistory">Comment History Report</option>
					<option value="pendingstatus">Pending Status Report</option>
					<option value="fresneltraveler">Fresnel Production Traveler</option>
					<option value="numberbyoperator">Number of Tools by Operator</option>
					<option value="fresnelplatingschedule">Fresnel Daily Plated Schedule</option>
					<option value="fresnelpendingstatus">Fresnel Pending Status Report</option>
					<option value="fresnelpackingslipout">Fresnel Packing Slip - Out</option>
					<option value="masterreport">Master Report</option>
					<option value="toollisting">Tool Listing Report</option>
					<option value="travelerreport">Traveler Report</option>
					<option value="shippicklist">Ship Pick List</option>
					<option value="packingslip">Packing Slip - In</option>
					<option value="masterplatingyield">Master Plating Yield</option>
					<option value="fresnelproductiontraveler">Fresnel Production Traveler</option>
					<option value="fresnelpackingslipin">Fresnel Packing Slip - In</option>
					<option value="fresnelpendingstatus">Fresnel Pending Status Report</option>
					<option value="fresnelmasteryield">Fresnel Master Yield</option>
					<option value="toolroomproductionyield">Tool Room Production Yield</option>
					<option value="inventoryreport">Inventory Report</option>
					<option value="picklist">Pick List</option>
					<option value="fresneleformingyield">Fresnel E-Forming Yield</option>
					<option value="fresneltreestatus">Fresnel Tree Status</option>
					<option value="dailytoolshipmentreport">Daily Tool Shipment Report</option>
					<option value="customerstatusreport">Customer Status Report</option>
					<option value="eformdailyoperations">Eform Daily Operations</option>
					<option value="toolroomdailyoperations">Tool Room Daily Operations</option>
					<option value="toolroomtoolsbyoperator">Tool Room Tools By Operator</option>
					<option value="inventoryreportold">Inventory Report Old</option>
					<option value="orderstatusreport">Order Status Report</option>
					<option value="tankstatusreport">Tank Status Report</option>
					<option value="eformingcycle">E-Forming Cycle</option>
					<option value="currentinventory">Current Inventory</option>
					<option value="duplicatetoollocations">Duplicate Tool Locations</option>
					<option value="machinetracking">Machine Tracking</option>
					<option value="nickelusage">Nickel Usage</option>
					<option value="toolsbyoperator">Number of Tools by Operator</option>
				</select><br>
				<div class="controls-left">
					<button>Add</button>
					<button>Edit</button>
					<button>Delete</button>
					<button>Copy</button>
				</div>
				<div class="controls-right">
					<button>Report Layout</button>
					<button>Import</button>
					<button>Export</button>
					<a href="home.php">Back</a>
				</div>
				<textarea rows="3" cols="27" readonly></textarea>
			</div>
		</div>
	</body>
</html>