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
		<title>Delete Tool</title>
		<link rel="stylesheet" type="text/css" href="/styles/deletetool.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="content">
					<button id="search-button">Search</button><span id="tool-span">Tool<input id="tool-input" type="text"><button id="quality-button">Q</button></span><br>
					<span id="init-span">Initials<input id="init-input" type="text"></span><br>
					<span id="location-span">Location<select id="location-select">
						<option value="apold">APOLD</option>
						<option value="mchg">B/MCHG</option>
						<option value="foi">FOI</option>
						<option value="inventory">INVENTORY</option>
						<option value="lwspare">LW SPARE</option>
						<option value="maptc">MAPTC</option>
						<option value="moptc">MOPTC</option>
						<option value="orafol">ORAFOL</option>
						<option value="prod">PROD</option>
						<option value="ptcreject">PTCREJECT</option>
						<option value="purged">PURGED</option>
						<option value="refle">REFLE</option>
						<option value="shp-china">SHP_CHINA</option>
						<option value="shp-rfd">SHP_RFD</option>
						<option value="shp-rtg">SHP_RTG</option>
						<option value="tank">TANK</option>
						<option value="wip">WIP</option>
					</select><input id="location-input" type="text"></span><br>
					<span id="status-span">Status<select id="status-select">
						<option value="good">GOOD</option>
						<option value="notgood">NOT GOOD</option>
					</select>
				</div>
				<div class="controls">
					<button>Delete</button>
					<a href="../utilities.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>