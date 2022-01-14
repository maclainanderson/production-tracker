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
		<title>Coloring Order</title>
		<link rel="stylesheet" type="text/css" href="/styles/coloringorder.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="color-span">Color Code<input id="color-input" type="text"></span><br>
					<span id="mode-span">Electroform Mode<input id="mode-input" type="text"></span><br>
					<span id="desc-span">Description<input id="desc-input" type="text"></span><br>
					<span id="priority-span">Priority<input id="priority-input" type="number" step="1"></span><br>
					<span id="status-span">Status<select>
						<option value="active">Active</option>
						<option value="inactive">Inactive</option>
					</select></span><br>
					<span id="date-span">Last Modified
					<input id="date-input" type="text"></span>
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
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>