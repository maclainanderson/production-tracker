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
		<title>Programs</title>
		<link rel="stylesheet" type="text/css" href="/styles/programs.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="name-span">Program Name<input id="name-input" type="text"></span><br>
					<span id="short-span">Short Name<input id="short-input" type="text"></span><br>
					<span id="parent-span">Parent Program<input id="parent-input" type="text"></span><br>
					<span id="level-span">Security Level<input id="level-input" type="text"></span><br>
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
					<a href="../utilities.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>