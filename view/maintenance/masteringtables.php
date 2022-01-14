<!DOCTYPE html>
<?php
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
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
		<title>Mastering Tables</title>
		<link rel="stylesheet" type="text/css" href="/styles/masteringtables.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="button left">
					<a href="masteringtables/tooltypes.php">Tool Types</a>
				</div>
				<div class="button">
					<a href="masteringtables/cosmetics.php">Cosmetics</a>
				</div>
				<div class="button">
					<a href="masteringtables/cuttypes.php">Type of Cut</a>
				</div>
				<div class="button right">
					<a href="masteringtables/diamondtypes.php">Diamond Type</a>
				</div>
				<div id="back-div" class="button">
					<a id="back-a" href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>