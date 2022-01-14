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
		<title>Import Tool</title>
		<link rel="stylesheet" type="text/css" href="/styles/importtool.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="tool-span">Tool<input id="tool-input" type="text"></span><br>
					<span id="level-span">Level<input id="level-input" type="text"></span><br>
					<span id="init-span">Initials<input id="init-input" type="text"></span><br>
					<span id="date-span">Created<input id="date-input" type="text"></span>
				</div>
				<div class="controls">
					<button>Add</button>
					<button>First</button>
					<button>Edit</button>
					<button>Up</button>
					<button>Delete</button>
					<button>Down</button>
					<button>Search</button>
					<button>Last</button>
					<button>Save</button>
					<a href="../utilities.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>