<!DOCTYPE html>
<?php
/**
  *	@desc main menu, also checked login information
*/
	session_start();
	
	//setup db connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//check to see if user is allowed and credentials are correct
	if (!isset($_SESSION['name']) && isset($_POST['login'])) {
		if (($_POST['name'] == "troom" && $_POST['pass'] == "troom") || ($_POST['name'] == "toolroom" && $_POST['pass'] == "toolroom")) {
			$_SESSION['name'] = 'troom';
			echo "Logged in successfully";
		} else if (($_POST['name'] == "eform" && $_POST['pass'] == "eform") || ($_POST['name'] == "electroforming" && $_POST['pass'] == "electroforming")) {
			$_SESSION['name'] = 'eform';
			echo "Logged in successfully";
		} else if (($_POST['name'] == "master" && $_POST['pass'] == "master") || ($_POST['name'] == "mastering" && $_POST['pass'] == "mastering")) {
			$_SESSION['name'] = "master";
			echo "Logged in successfully";
		} else {
			$ldap = ldap_connect("BC-DC-01", 389);
			ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
			if ($ldap) {
				$bind = ldap_bind($ldap, "oracal\\".$_POST['name'], $_POST['pass']);
				if ($bind) {
					unset($_POST['pass']);
					$dn = "dc=oracal,dc=com";
					$userdn = getDN($ldap, $_POST['name'], $dn);
					$groupdn = getDN($ldap, "OPTS Tool Tracker Users", $dn);
					if (checkGroup($ldap, $userdn, $groupdn)) {
						if ($conn) {
							$result = sqlsrv_query($conn, "SELECT Initials FROM INITIALS WHERE [USER] = '" . $_POST['name'] . "';");
							if ($result) {
								while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC)) {
									$_SESSION['initials'] = $row[0];
								}
							} else {
								print_r(sqlsrv_errors());
							}
						}
						$_SESSION['name'] = $_POST['name'];
						//$_SESSION['pass'] = $_POST['pass'];
						ldap_close($ldap);
						echo "Logged in successfully";
					} else {
						ldap_close($ldap);
						echo "<form id=\"redirect\" action=\"/index.php\" method=\"post\"><input name=\"error\" type=\"text\" hidden value=\"User not allowed\"><input type=\"submit\">";
						?><script>document.getElementById('redirect').submit();</script><?php
					}
				} else {
					ldap_close($ldap);
					echo "<form id=\"redirect\" action=\"/index.php\" method=\"post\"><input name=\"error\" type=\"text\" hidden value=\"Incorrect credentials\"><input type=\"submit\">";
					?><script>document.getElementById('redirect').submit();</script><?php
				}
			} else {
				ldap_close($ldap);
				echo "<form id=\"redirect\" action=\"/index.php\" method=\"post\"><input name=\"error\" type=\"text\" hidden value=\"Error connecting to server\"><input type=\"submit\">";
				?><script>document.getElementById('redirect').submit();</script><?php
			}
		}
	} else if (!isset($_POST['login']) && !isset($_SESSION['name'])) {
		header("Location: /index.php");
	}
	
	/**
	  *	@desc	gets FQDN of AD object
	  *	@param	Resource $ad - LDAP connection, string $samaccountname - AD object name, string $dn - base FQDN of domain
	  *	@return	string - FQDN of AD object
	*/
	function getDN($ad, $samaccountname, $basedn) {
		$attributes = array('dn');
		$result = ldap_search($ad, $basedn, "(samaccountname={$samaccountname})", $attributes);
		if ($result === FALSE) {
			return '';
		}
		$entries = ldap_get_entries($ad, $result);
		if ($entries['count'] > 0) {
			return $entries[0]['dn'];
		} else {
			return '';
		}
	}
	
	/**
	  *	@desc	determines if user is in group
	  *	@param	Resource $ad - LDAP connection, string $userdn - FQDN of user, string $groupdn - FQDN of group
	  *	@return	int - length of result array
	*/
	function checkGroup($ad, $userdn, $groupdn) {
		$attributes = array('members');
		$result = ldap_read($ad, $userdn, "(memberof={$groupdn})", $attributes);
		if ($result === FALSE) { return FALSE; };
		$entries = ldap_get_entries($ad, $result);
		return ($entries['count'] > 0);
	}
	
	//get user lists
	require_once("../utils.php");
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
		<title>Main Menu</title>
		<link rel="stylesheet" type="text/css" href="/styles/home.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="menu-outer">
			<div class="menu">
				<div class="logo">
					<img src="/images/orafol.png" height="50" width="150">
					<h3 style="display: inline-block; font-family: sans-serif; margin: 6.5px; vertical-align: top;">Production Tracking Service</h3>
				</div>
				<div class="menu-button left">
					<a class="button" href="operations.php">Operations</a>
				</div>
				<div class="menu-button">
					<a class="button" href="conditions.php">Tank Conditions</a>
				</div>
				<div class="menu-button">
					<a class="button" <?php if (in_array($_SESSION['name'], $admins) || in_array($_SESSION['name'], $schedulers)) { ?> href="scheduling.php" <?php } ?>>Scheduling</a>
				</div>
				<div class="menu-button right">
					<a class="button" href="retrieve.php">Retrieve Tool</a>
				</div>
				<div class="menu-button left">
					<a class="button" <?php if (in_array($_SESSION['name'], $admins) || in_array($_SESSION['name'], $schedulers)) { ?> href="design.php" <?php } ?>>Designs</a>
				</div>
				<div class="menu-button">
					<a class="button" href="receive.php">Recieve Blank</a>
				</div>
				<div class="menu-button">
					<a class="button" href="quality.php">Quality</a>
				</div>
				<div class="menu-button right">
					<a class="button" href="reports.php">Reports</a>
				</div>
				<div class="menu-button left">
					<a class="button" <?php if (in_array($_SESSION['name'], $admins)) { ?> href="customreports.php" <?php } ?>>Custom Reports (under construction)</a>
				</div>
				<div class="menu-button">
					<a class="button" <?php if (in_array($_SESSION['name'], $admins)) { ?> href="maintenance.php" <?php } ?>>Table Maintenance</a>
				</div>
				<div class="menu-button">
					<a class="button" <?php if (in_array($_SESSION['name'], $admins)) { ?> href="utilities.php" <?php } ?>>Utilities</a>
				</div>
				<div class="menu-button right">
					<a class="button" href="logout.php">Exit</a>
				</div>
			</div>
		</div>
	</body>
</html>