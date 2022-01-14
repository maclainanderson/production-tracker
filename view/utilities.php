<!DOCTYPE html>
<?php
/**
  * @desc	menu options for various utilities
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
	
	function goToUsers() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modalContent.innerHTML = '<form action="utilities/users.php" method="POST"><span>For security purposes, re-enter your password:</span><br><input type="password" name="pass"><br><input type="submit" value="Submit"></form>';
		modal.style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	set onclick functions to close modal form
	  *	@param	none
	  *	@return	none
	*/
	function closeForm() {
		var modal = document.getElementById("modal");
		var span = document.getElementById("close");
		
		span.onclick = function() {
			modal.style.display = "none";
		}
		
		window.onclick = function(event) {
			if (event.target == modal) {
				modal.style.display = "none";
			}
		}
	}
</script>
<html>
	<head>
		<title>Utilities</title>
		<link rel="stylesheet" type="text/css" href="/styles/utilities.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="menu-button left">
					<button class="button" id="button" onclick="goToUsers()">Users</a>
				</div><!--
				<div class="menu-button">
					<a class="button" href="utilities/programs.php">Programs</a>
				</div>
				<div class="menu-button right">
					<a class="button" href="utilities/masschange.php">Mass Change</a>
				</div>
				<div class="menu-button left">
					<a class="button" href="utilities/deleteworkorders.php">Delete Work Orders</a>
				</div>-->
				<div class="menu-button ">
					<a class="button" href="utilities/deletetool.php">Delete Tool</a>
				</div><!--
				<div class="menu-button right">
					<a class="button" href="utilities/importtool.php">Import Tool</a>
				</div>-->
				<div class="menu-button right">
					<a class="button" href="home.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>