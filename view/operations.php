<!DOCTYPE html>
<?php
/**
  *	@desc menu to get to daily operations
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
		<title>Daily Operations</title>
		<link rel="stylesheet" type="text/css" href="/styles/operations.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="logo">
					<img src="/images/orafol.png" height="50" width="150">
					<h1 style="display: inline-block; font-family: sans-serif; margin: 6.5px; vertical-align: top;">TOOL TRACKER</h1>
				</div>
				<div class="menu-button left">
					<a class="button" href="operations/mastering.php">Mastering</a>
				</div>
				<div class="menu-button">
					<a class="button" href="operations/electroforming.php">Electroforming</a>
				</div>
				<div class="menu-button right">
					<a class="button" href="operations/invoicing.php">Invoicing</a>
				</div>
				<div class="menu-button left">
					<a class="button" href="operations/toolroom.php">Tool Room</a>
				</div>
				<div class="menu-button">
					<a class="button" href="operations/shipping.php">Shipping</a>
				</div>
				<div class="menu-button right">
					<a class="button" href="home.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>