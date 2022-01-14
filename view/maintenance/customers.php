<!DOCTYPE html>
<?php
/**
  *	@desc create/edit/view customers
*/
	//get user lists
	require_once("../../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins)){
		header("Location: /view/home.php");
	}
	
	//set up sql connection for loading data
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of customers
	$customers = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Customers");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$customers[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		echo "Error: could not connect to database.";
		var_dump(sqlsrv_errors());
	}
?>
<script type="text/javascript">
	
	//maintain session
	setInterval(function(){
		var conn = new XMLHttpRequest();
		conn.open("GET","/session.php");
		conn.send();
	},600000);
	
	//set up tracking variables
	var current = 0;
	var customers = [<?php
		foreach($customers as $customer) {
			echo '{';
			foreach($customer as $key=>$value) {
				echo '"' . $key . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value, "m/d/y H:i");
				} else {
					echo $value;
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	/**
	  *	@desc	go to first customer
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous customer
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next customer
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < customers.length - 1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last customer
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(customers.length-1);
	}
	
	/**
	  *	@desc	find customer by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("customer-input").value = customers[current]['CUSTOMER'];
		document.getElementById("parent-input").value = customers[current]['PARENT'];
		document.getElementById("name-input").value = customers[current]['NAME'];
		document.getElementById("address1-input").value = customers[current]['ADDRESS'];
		document.getElementById("address2-input").value = customers[current]['ADDRESS2'];
		document.getElementById("city-input").value = customers[current]['CITY'];
		document.getElementById("state-input").value = customers[current]['STATE'];
		document.getElementById("zip-input").value = customers[current]['ZIP'];
		document.getElementById("country-input").value = customers[current]['COUNTRY'];
		document.getElementById("contact-input").value = customers[current]['CONTACT'];
		document.getElementById("phone-input").value = customers[current]['PHONE'];
		document.getElementById("ext-input").value = customers[current]['EXT'];
		document.getElementById("fax-input").value = customers[current]['FAX'];
		document.getElementById("status-select").options.selectedIndex = customers[current]['STATUS'] == "Active" ? 0 : 1;
		document.getElementById("shipre1-input").value = customers[current]['LABEL1'];
		document.getElementById("shipre2-input").value = customers[current]['LABEL2'];
		//document.getElementById("shipto-textarea").innerHTML = customers[current];
		document.getElementById("date-input").value = customers[current]['DATE'] == "" ? "" : customers[current]['DATE'] + " by " + customers[current]['OPERATOR'];
	}
	
	/**
	  *	@desc	prep values and readOnly attributes for new customer
	  *	@param	none
	  *	@return	none
	*/
	function newCustomer() {
		document.getElementById("customer-input").value = "";
		document.getElementById("customer-input").readOnly = false;
		document.getElementById("parent-input").value = "";
		document.getElementById("parent-input").readOnly = false;
		document.getElementById("name-input").value = "";
		document.getElementById("name-input").readOnly = false;
		document.getElementById("address1-input").value = "";
		document.getElementById("address1-input").readOnly = false;
		document.getElementById("address2-input").value = "";
		document.getElementById("address2-input").readOnly = false;
		document.getElementById("city-input").value = "";
		document.getElementById("city-input").readOnly = false;
		document.getElementById("state-input").value = "";
		document.getElementById("state-input").readOnly = false;
		document.getElementById("zip-input").value = "";
		document.getElementById("zip-input").readOnly = false;
		document.getElementById("country-input").value = "";
		document.getElementById("country-input").readOnly = false;
		document.getElementById("contact-input").value = "";
		document.getElementById("contact-input").readOnly = false;
		document.getElementById("phone-input").value = "";
		document.getElementById("phone-input").readOnly = false;
		document.getElementById("ext-input").value = "";
		document.getElementById("ext-input").readOnly = false;
		document.getElementById("fax-input").value = "";
		document.getElementById("fax-input").readOnly = false;
		document.getElementById("status-select").options.selectedIndex = 0;
		document.getElementById("status-select").disabled = false;
		document.getElementById("shipre1-input").value = "";
		document.getElementById("shipre1-input").readOnly = false;
		document.getElementById("shipre2-input").value = "";
		document.getElementById("shipre2-input").readOnly = false;
		//document.getElementById("shipto-textarea").innerHTML = customers[current];
		document.getElementById("shipto-textarea").readOnly = false;
		document.getElementById("date-input").value = "";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveCustomer(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	prep readOnly attributes to edit a customer
	  *	@param	none
	  *	@return	none
	*/
	function edit() {
		document.getElementById("customer-input").readOnly = false;
		document.getElementById("parent-input").readOnly = false;
		document.getElementById("name-input").readOnly = false;
		document.getElementById("address1-input").readOnly = false;
		document.getElementById("address2-input").readOnly = false;
		document.getElementById("city-input").readOnly = false;
		document.getElementById("state-input").readOnly = false;
		document.getElementById("zip-input").readOnly = false;
		document.getElementById("country-input").readOnly = false;
		document.getElementById("contact-input").readOnly = false;
		document.getElementById("phone-input").readOnly = false;
		document.getElementById("ext-input").readOnly = false;
		document.getElementById("fax-input").readOnly = false;
		document.getElementById("status-select").disabled = false;
		document.getElementById("shipre1-input").readOnly = false;
		document.getElementById("shipre2-input").readOnly = false;
		document.getElementById("shipto-textarea").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveCustomer(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	set fields to readOnly and find current customer
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("customer-input").readOnly = true;
		document.getElementById("parent-input").readOnly = true;
		document.getElementById("name-input").readOnly = true;
		document.getElementById("address1-input").readOnly = true;
		document.getElementById("address2-input").readOnly = true;
		document.getElementById("city-input").readOnly = true;
		document.getElementById("state-input").readOnly = true;
		document.getElementById("zip-input").readOnly = true;
		document.getElementById("country-input").readOnly = true;
		document.getElementById("contact-input").readOnly = true;
		document.getElementById("phone-input").readOnly = true;
		document.getElementById("ext-input").readOnly = true;
		document.getElementById("fax-input").readOnly = true;
		document.getElementById("status-select").disabled = true;
		document.getElementById("shipre1-input").readOnly = true;
		document.getElementById("shipre2-input").readOnly = true;
		document.getElementById("shipto-textarea").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','edit()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteItem()');
		find(current);
	}
	
	/**
	  *	@desc	save customer data
	  *	@param	string s - whether added or updated
	  *	@return	none
	*/
	function saveCustomer(s) {
		var conn = new XMLHttpRequest();
		var type = s == "add" ? "insert" : "update";
		var table = "Customers";
		var id = customers[current]['ID'];
		var customer = document.getElementById("customer-input").value;
		var parent = document.getElementById("parent-input").value;
		var name = document.getElementById("name-input").value;
		var address1 = document.getElementById("address1-input").value.replace(/[#]/g, "%23");
		var address2 = document.getElementById("address2-input").value;
		var city = document.getElementById("city-input").value;
		var state = document.getElementById("state-input").value;
		var zip = document.getElementById("zip-input").value;
		var country = document.getElementById("country-input").value;
		var contact = document.getElementById("contact-input").value;
		var phone = document.getElementById("phone-input").value;
		var ext = document.getElementById("ext-input").value;
		var fax = document.getElementById("fax-input").value;
		var status = document.getElementById("status-select").value;
		var shipre1 = document.getElementById("shipre1-input").value;
		var shipre2 = document.getElementById("shipre2-input").value;
		var shipto = document.getElementById("shipto-textarea").innerHTML;
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Data updated")) {
					modifyOldRecords(customers[current]['CUSTOMER'],customer);
				} else if (conn.responseText.includes("Insert succeeded")) {
					alert("Changes saved");
					window.location.reload();
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+type+"&table="+table+"&CUSTOMER="+customer+"&NAME="+name+"&ADDRESS="+address1+"&ADDRESS2="+address2+"&CITY="+city+"&STATE="+state+"&ZIP="+zip+"&COUNTRY="+country+"&CONTACT="+contact+"&PHONE="+phone+"&EXT="+ext+"&FAX="+fax+"&LABEL1="+shipre1+"&LABEL2="+shipre2+"&STATUS="+status+"&PARENT="+parent+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	convert date object to string
	  *	@param	Date d - date to convert
	  *	@return	string date - MM/DD/YY
	*/
	function formatDate(d) {
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		if (year < 10) {
			year = "0" + year;
		}
		var hour = d.getHours();
		if (hour < 10) {
			hour = "0" + hour;
		}
		var minute = d.getMinutes();
		if (minute < 10) {
			minute = "0" + minute;
		}
		
		date = month.toString() + "/" + date.toString() + "/" + year.toString() + " " + hour.toString() + ":" + minute.toString();
		
		return date;
	}
	
	/**
	  *	@desc	modifies old records to match new name
	  *	@param	String oldCustomer - old customer name, String newCustomer - new customer name
	  *	@return	none
	*/
	function modifyOldRecords(oldCustomer,newCustomer) {
		var conn = [];
		var tables = ['Shipping','Shipping_Queue','Shipping_History','Tool_Tree'];
		var action = "update";
		var attempts = 0;
		var successes = 0;
		
		for (var i=0;i<tables.length;i++) {
			conn[i] = new XMLHttpRequest();
		}
		
		for (var i=0;i<conn.length;i++) {
			conn[i].onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					attempts++;
					if (this.responseText.includes("Data updated")) {
						successes++;
						if (attempts >= conn.length) {
							if (successes >= conn.length) {
								alert("Changes saved");
								window.location.replace("customers.php");
							} else {
								alert("Not all old records updated. Contact IT support to correct.");
							}
						}
					}
				}
			}
			
			conn[i].open("GET","/db_query/sql2.php?action="+action+"&table="+tables[i]+"&CUSTOMER="+newCustomer+"&condition=CUSTOMER&value="+oldCustomer,false);
			conn[i].send();
		}
	}
	
	/**
	  *	@desc	remove customer
	  *	@param	none
	  *	@return	none
	*/
	function deleteItem() {
		var conn = new XMLHttpRequest();
		var type = "delete";
		var table = "Customers";
		var id = customers[current]['ID'];
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					alert("Customer deleted");
					window.location.replace("customers.php");
				} else {
					alert("Customer not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+type+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	create/display search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Customer</th><th class="col2">Name</th><th class="col3">Address</th><th class="col4">Address2</th><th class="col5">City</th><th class="col6">State</th><th class="col7">Zip</th><th class="col8">Country</th><th class="col9">Contact</th><th class="col10">Phone</th><th class="col11">Ext</th><th class="col12">Fax</th><th class="col13">Label1</th><th class="col14">Label2</th><th class="col15">Operator</th><th class="col16">Status</th><th class="col17">Modified</th></tr></thead><tbody>';
		
		customers.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['CUSTOMER']+'</td><td class="col2">'+item['NAME']+'</td><td class="col3">'+item['ADDRESS']+'</td><td class="col4">'+item['ADDRESS2']+'</td><td class="col5">'+item['CITY']+'</td><td class="col6">'+item['STATE']+'</td><td class="col7">'+item['ZIP']+'</td><td class="col8">'+item['COUNTRY']+'</td><td class="col9">'+item['CONTACT']+'</td><td class="col10">'+item['PHONE']+'</td><td class="col11">'+item['EXT']+'</td><td class="col12">'+item['FAX']+'</td><td class="col13">'+item['LABEL1']+'</td><td class="col14">'+item['LABEL2']+'</td><td class="col15">'+item['OPERATOR']+'</td><td class="col16">'+item['STATUS']+'</td><td class="col17">'+item['DATE']+'</td></tr>';
		});
		
		html += '</tbody></table>';
		modalContent.innerHTML = html;
		document.getElementById("modal").style.display = "block";
		
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
	
	/**
	  *	@desc	highlight search row, or confirm if already highlighted
	  *	@param	none
	  *	@return	none
	*/
	function selectRow(tr) {
		if (tr.style.color == "white") {
			find(tr.id);
			document.getElementById("close").click();
		} else {
			var trs = tr.parentNode.children;
			for (var i=0;i<trs.length;i++) {
				trs[i].style.color = "black";
				trs[i].style.backgroundColor = "white";
			}
			
			tr.style.color = "white";
			tr.style.backgroundColor = "black";
		}
	}
</script>
<html>
	<head>
		<title>Customers</title>
		<link rel="stylesheet" type="text/css" href="/styles/customers.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="customer-span">Customer<input id="customer-input" type="text" readonly></span>
					<span id="parent-span">Parent<input id="parent-input" type="text" readonly></span><br>
					<span id="name-span">Name<input id="name-input" type="text" readonly></span><br>
					<span id="address1-span">Address Line 1<input id="address1-input" type="text" readonly></span><br>
					<span id="address2-span">Address Line 2<input id="address2-input" type="text" readonly></span><br>
					<span id="city-span">City<input id="city-input" type="text" readonly></span>
					<span id="state-span">State<input id="state-input" type="text" readonly></span>
					<span id="zip-span">Zip<input id="zip-input" type="text" readonly></span><br>
					<span id="country-span">Country<input id="country-input" type="text" readonly></span><br>
					<span id="contact-span">Contact<input id="contact-input" type="text" readonly></span><br>
					<span id="phone-span">Phone<input id="phone-input" type="text" readonly></span>
					<span id="ext-span">Ext<input id="ext-input" type="text" readonly></span><br>
					<span id="fax-span">Fax<input id="fax-input" type="text" readonly></span>
					<span id="status-span">Status<select id="status-select" disabled>
						<option value="Active">Active</option>
						<option value="Inactive">Inactive</option>
					</select></span><br>
					<span id="shipre1-span">Shipping Report 1<input id="shipre1-input" type="text" readonly></span><br>
					<span id="shipre2-span">Shipping Report 2<input id="shipre2-input" type="text" readonly></span><br>
					<span id="shipto-span">Ship To</span><textarea id="shipto-textarea" rows="4" cols="30" readonly></textarea><br>
					<span id="date-span">Last Modified<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button id="add" onclick="newCustomer()">Add</button>
					<button onclick="insertFirst()">First</button>
					<button id="edit" onclick="edit()">Edit</button>
					<button onclick="goUp()">Up</button>
					<button id="delete" onclick="deleteItem()">Delete</button>
					<button onclick="goDown()">Down</button>
					<button onclick="search()">Search</button>
					<button onclick="insertLast()">Last</button>
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>