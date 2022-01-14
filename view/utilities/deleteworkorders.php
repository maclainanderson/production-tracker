<!DOCTYPE html>
<?php
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])) {
		header("location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
	}
?>
<html>
	<head>
		<title>Delete Work Orders</title>
		<link rel="stylesheet" type="text/css" href="/styles/deleteworkorders.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<h2>Delete Work Orders</h2>
				<div class="content">
					<span id="date-span">Prior to Out Date<input id="date-input" type="text"></span>
				</div><br>
				<div class="controls">
					<button>OK</button>
					<a href="../utilities.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>