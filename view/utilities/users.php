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
	
	//get list of users
	$users = array();
	$ldap = ldap_connect("BC-DC-01", 389);
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	if ($ldap) {
		$bind = ldap_bind($ldap, $_SESSION['name'] . "@oracal.com", $_POST['pass']);
		if ($bind) {
			$dn = "dc=oracal,dc=com";
			$groupdn = getDN($ldap, "OPTS Tool Tracker Users", $dn);
			$result = ldap_search($ldap, $groupdn, '(samAccountName=*)');
			if ($result) {
				$entries = ldap_get_entries($ldap, $result);
				foreach($entries[0]['member'] as $id => $entry) {
					if ($id != 'count') {
						$users[] = explode(",",explode("CN=",$entry)[1])[0];
					}
				}
			} else {
				echo "search failed";
			}
			
			foreach($users as $user) {
				$result = ldap_search($ldap, $dn, '(CN=' . $user . ')', array("samAccountName"));
				if ($result) {
					$entries = ldap_get_entries($ldap, $result);
					foreach($entries[0]['samaccountname'] as $entry) {
						$users[$user] = explode("1",$entry)[0];
					}
				}
			}
		} else {
			ldap_close($ldap);
			echo "could not bind";
		}
	} else {
		ldap_close($ldap);
		echo "could not connect";
	}
	
	foreach($users as $key => $user) {
		if (is_int($key)) {
			unset($users[$key]);
		}
	}
	
	/**
	  *	@desc	gets FQDN of AD object
	  *	@param	Resource $ad - LDAP connection, string $samaccountname - AD object name, string $basedn - base FQDN of domain
	  *	@return	string - FQDN of AD object
	*/
	function getDN($ad, $samaccountname, $basedn) {
		$attributes = array('dn');
		$result = ldap_search($ad, $basedn, "(samaccountname=$samaccountname)", $attributes);
		if ($result === FALSE) {
			var_dump(ldap_error($ad));
			return '';
		}
		$entries = ldap_get_entries($ad, $result);
		if ($entries['count'] > 0) {
			return $entries[0]['dn'];
		} else {
			return '';
		}
	}
	
	$schedUsers = array_keys($schedulers);
	$adminUsers = array_keys($admins);
	$nonUsers = array('LW1','lean lift','CH1','TrueChem','Lasertrim','Zygo','Domain Admins','Zygo New York');
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	var schedulers = {<?php 
		foreach($schedulers as $key => $scheduler) {
			echo '"' . $key . '": "' . $scheduler . '",';
		}
	?>};
	var admins = {<?php 
		foreach($admins as $key => $admin) {
			echo '"' . $key . '": "' . $admin . '",';
		}
	?>};
	
	/**
	  *	@desc	highlight selected row, or unhighlight if already selected
	  *	@param	DOM Object tr - selected row
	  *	@return	none
	*/
	function selectRow(tr) {
		if (tr.style.backgroundColor == "black") {
			tr.style.backgroundColor = "white";
			tr.style.color = "black";
		} else {
			tr.style.backgroundColor = "black";
			tr.style.color = "white";
		}
	}
	
	/**
	  *	@desc	swap rows between general users and schedulers
	  *	@param	none
	  *	@return	none
	*/
	function swapGeneralToSchedulers() {
		var generalsToSwap = [];
		var schedulersToSwap = [];
		var generalList = document.getElementById("table1").children[0].children[1].children;
		var schedulerList = document.getElementById("table2").children[0].children[1].children;
		
		for (var i=0;i<generalList.length;i++) {
			if (generalList[i].style.backgroundColor == "black") {
				generalsToSwap.push(generalList[i]);
			}
		}
		
		for (var i=0;i<schedulerList.length;i++) {
			if (schedulerList[i].style.backgroundColor == "black") {
				schedulersToSwap.push(schedulerList[i]);
			}
		}
		
		for (var i=0;i<generalsToSwap.length;i++) {
			document.getElementById("table1").children[0].children[1].removeChild(generalsToSwap[i]);
			document.getElementById("table2").children[0].children[1].appendChild(generalsToSwap[i]);
		}
		
		for (var i=0;i<schedulersToSwap.length;i++) {
			document.getElementById("table2").children[0].children[1].removeChild(schedulersToSwap[i]);
			document.getElementById("table1").children[0].children[1].appendChild(schedulersToSwap[i]);
		}
		
		//sortLists();
	}
	
	/**
	  *	@desc	swap rows between general users and admins
	  *	@param	none
	  *	@return	none
	*/
	function swapGeneralToAdmins() {
		var generalsToSwap = [];
		var adminsToSwap = [];
		var generalList = document.getElementById("table1").children[0].children[1].children;
		var adminList = document.getElementById("table3").children[0].children[1].children;
		
		for (var i=0;i<generalList.length;i++) {
			if (generalList[i].style.backgroundColor == "black") {
				generalsToSwap.push(generalList[i]);
			}
		}
		
		for (var i=0;i<adminList.length;i++) {
			if (adminList[i].style.backgroundColor == "black") {
				adminsToSwap.push(adminList[i]);
			}
		}
		
		for (var i=0;i<generalsToSwap.length;i++) {
			document.getElementById("table1").children[0].children[1].removeChild(generalsToSwap[i]);
			document.getElementById("table3").children[0].children[1].appendChild(generalsToSwap[i]);
		}
		
		for (var i=0;i<adminsToSwap.length;i++) {
			document.getElementById("table3").children[0].children[1].removeChild(adminsToSwap[i]);
			document.getElementById("table1").children[0].children[1].appendChild(adminsToSwap[i]);
		}
		
		//sortLists();
	}
	
	/**
	  *	@desc	swap rows between admins and schedulers
	  *	@param	none
	  *	@return	none
	*/
	function swapSchedulerToAdmin() {
		var adminsToSwap = [];
		var schedulersToSwap = [];
		var adminList = document.getElementById("table3").children[0].children[1].children;
		var schedulerList = document.getElementById("table2").children[0].children[1].children;
		
		for (var i=0;i<adminList.length;i++) {
			if (adminList[i].style.backgroundColor == "black") {
				adminsToSwap.push(adminList[i]);
			}
		}
		
		for (var i=0;i<schedulerList.length;i++) {
			if (schedulerList[i].style.backgroundColor == "black") {
				schedulersToSwap.push(schedulerList[i]);
			}
		}
		
		for (var i=0;i<adminsToSwap.length;i++) {
			document.getElementById("table3").children[0].children[1].removeChild(adminsToSwap[i]);
			document.getElementById("table2").children[0].children[1].appendChild(adminsToSwap[i]);
		}
		
		for (var i=0;i<schedulersToSwap.length;i++) {
			document.getElementById("table2").children[0].children[1].removeChild(schedulersToSwap[i]);
			document.getElementById("table3").children[0].children[1].appendChild(schedulersToSwap[i]);
		}
		
		//sortLists();
	}
	
	/**
	  *	@desc	sort user lists alphabetically
	  *	@param	none
	  *	@return	none
	*/
	function sortLists() {
		document.getElementById("table1").children[0].children.sort(function(a, b) 
		{
			if (a.id < b.id) {
				return -1;
			} else {
				return 1;
			}
		});
	}
	
	/**
	  *	@desc	update user lists
	  *	@param	none
	  *	@return	none
	*/
	function saveUsers() {
		var schedulerRows = document.getElementById("table2").children[0].children[1].children;
		var adminRows = document.getElementById("table3").children[0].children[1].children;
		
		for (var i=0;i<schedulerRows.length;i++) {
			if (!schedulers.hasOwnProperty(schedulerRows[i].children[0].innerHTML)) {
				addUser("Schedulers",schedulerRows[i].children[0].innerHTML, schedulerRows[i].id);
			}
		}
		
		for (var i=0;i<adminRows.length;i++) {
			if (!admins.hasOwnProperty(adminRows[i].children[0].innerHTML)) {
				addUser("Admins",adminRows[i].children[0].innerHTML, adminRows[i].id);
			}
		}
		
		for (var user in schedulers) {
			var remove = true;
			for (var i=0;i<schedulerRows.length;i++) {
				if (schedulerRows[i].children[0].innerHTML == user) {
					remove = false;
				}
			}
			
			if (remove) {
				removeUser("Schedulers", schedulers[user]);
			}
		}
		
		for (var user in admins) {
			var remove = true;
			for (var i=0;i<adminRows.length;i++) {
				if (adminRows[i].children[0].innerHTML == user) {
					remove = false;
				}
			}
			
			if (remove) {
				removeUser("Admins", admins[user]);
			}
		}
		
		window.location.reload();
	}
	
	/**
	  *	@desc	add user to designated list
	  *	@param	str table - list to add to, str name - display name of user, str username - username of user
	  *	@return	none
	*/
	function addUser(table, name, username) {
		var conn = new XMLHttpRequest();
		var query = "?action=insert&table="+table+"&name="+name+"&username="+username;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (!conn.responseText.includes("Insert succeeded")) {
					alert(name + " could not be added to " + table + " list. Contact IT Support if problem persists.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php" + query, false);
		conn.send();
	}
	
	/**
	  *	@desc	remove user from designated list
	  *	@param	str table - list to remove from, str username - username of user
	  *	@return	none
	*/
	function removeUser(table, username) {
		var conn = new XMLHttpRequest();
		var query = "?action=delete&table="+table+"&username="+username;
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				if (!conn.responseText.includes("Deletion succeeded")) {
					alert(name + " could not be removed from " + table + " list. Contact IT Support if problem persists.");
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php" + query, false);
		conn.send();
	}
</script>
<html>
	<head>
		<title>Users</title>
		<link rel="stylesheet" type="text/css" href="/styles/users.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div id="container">
			<div id="left">
				<div id="table1">
					<table>
						<thead>
							<tr>
								<th>General</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($users as $key => $user) { 
							if (!in_array($key, $schedUsers) && !in_array($key, $adminUsers) && !in_array($key,$nonUsers)) { ?>
							<tr id="<?=$user?>" onclick="selectRow(this)">
								<td><?=$key?></td>
							</tr>
						<?php }
						} ?>
						</tbody>
					</table>
				</div>
			</div>
			<div id="center">
				<div id="swap1" onclick="swapGeneralToSchedulers()">
					<div class="left-arrow">
					</div>
					<div class="horizontal-line">
					</div>
					<div class="right-arrow">
					</div>
				</div>
				<div id="controls">
					<button onclick="saveUsers()">Save</button>
					<a href="/view/utilities.php">Back</a>
				</div>
				<div id="swap2" onclick="swapGeneralToAdmins()">
					<div class="left-arrow">
					</div>
					<div class="horizontal-line">
					</div>
					<div class="right-arrow">
					</div>
				</div>
			</div>
			<div id="right">
				<div id="table2">
					<table>
						<thead>
							<tr>
								<th>Schedulers</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($users as $key => $user) { 
							if (in_array($key, $schedUsers) && !in_array($key, $adminUsers)) { ?>
							<tr id="<?=$user?>" onclick="selectRow(this)">
								<td><?=$key?></td>
							</tr>
						<?php }
						} ?>
						</tbody>
					</table>
				</div>
				<div id="swap3" onclick="swapSchedulerToAdmin()">
					<div class="up-arrow">
					</div>
					<div class="vertical-line">
					</div>
					<div class="down-arrow">
					</div>
				</div>
				<div id="table3">
					<table>
						<thead>
							<tr>
								<th>Admins</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach($users as $key => $user) { 
							if (!in_array($key, $schedUsers) && in_array($key, $adminUsers)) { ?>
							<tr id="<?=$user?>" onclick="selectRow(this)">
								<td><?=$key?></td>
							</tr>
						<?php }
						} ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</body>
</html>