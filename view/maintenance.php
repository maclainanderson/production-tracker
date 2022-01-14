<!DOCTYPE html>
<?php
/**
  *	@desc menu to get to maintenance options
*/
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
</script>
<html>
	<head>
		<title>Table Maintenance</title>
		<link rel="stylesheet" type="text/css" href="/styles/maintenance.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="button top left">
					<a href="maintenance/validdefects.php"><span>Valid Defects</span></a>
				</div>
				<div class="button top">
					<a href="maintenance/validmachinenumbers.php"><span>Valid Machine Numbers</span></a>
				</div>
				<div class="button top">
					<a href="maintenance/validinventorylocations.php"><span>Valid Inventory Locations</span></a>
				</div>
				<div class="button top">
					<a href="maintenance/validtankdefinitions.php"><span>Valid Tank Definitions</span></a>
				</div>
				<div class="button top right">
					<a href="maintenance/toolstatus.php"><span>Tool Status</span></a>
				</div><!--
				<div class="button">
					<a href="maintenance/preplatingdefinition.php"><span>Pre-Plating Definition</span></a>
				</div>-->
				<div class="button left">
					<a href="maintenance/aperture.php"><span>Aperture</span></a>
				</div>
				<div class="button">
					<a href="maintenance/parameters.php"><span>Parameters</span></a>
				</div><!--
				<div class="button">
					<a href="maintenance/masteringtables.php"><span>Mastering Tables</span></a>
				</div>
				<div class="button">
					<a href="maintenance/coloringorder.php"><span>Coloring Order</span></a>
				</div>-->
				<div class="button">
					<a href="maintenance/processdefinition.php"><span>Process Definition</span></a>
				</div>
				<div class="button">
					<a href="maintenance/workflow.php"><span>Work Flow</span></a>
				</div>
				<div class="button right">
					<a href="maintenance/abortworkorder.php"><span>Reasons to Abort Work Order</span></a>
				</div>
				<div class="button left">
					<a href="maintenance/abortbatch.php"><span>Reasons to Abort Batch</span></a>
				</div>
				<div class="button">
					<a href="maintenance/customertooltype.php"><span>Customer Tool Type</span></a>
				</div>
				<div class="button">
					<a href="maintenance/customers.php"><span>Customers</span></a>
				</div>
				<div class="button">
					<a href="maintenance/products.php"><span>Products</span></a>
				</div>
				<div class="button right">
					<a href="home.php"><span>Back</span></a>
				</div>
			</div>
		</div>
	</body>
</html>