<!DOCTYPE html>
<?php
/**
  * @desc basic logout script, destroys session and redirects to menu
*/
	session_start();
	session_unset();
	session_destroy();
	session_write_close();
	setcookie(session_name(),'',0,'/');
	session_regenerate_id(true);
	header("Location: /index.php");
	$_SERVER['PHP_AUTH_USER'] = "logout";
	$_SERVER['PHP_AUTH_PW'] = "logout";
?>