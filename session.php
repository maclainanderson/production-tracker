<?php

	session_start();
	
	if (isset($_SESSION['name'])) {
		$_SESSION['name'] = $_SESSION['name'];
	}

?>