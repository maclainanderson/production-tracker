<!DOCTYPE html>
<?php
/**
  *	@desc list of products
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
	
	//set up sql connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//lists of products and designs
	$products = array();
	$designs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Products");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$products[] = $row;
			}
		} else {
			var_dump(sqlsrv_errors());
		}
	} else {
		echo "Error: could not connect to database.";
		var_dump(sqlsrv_errors());
	}
	
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Product_Designs");
		if ($result) {
			while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
				$designs[] = $row;
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
	
	//set up variables
	var current = 0;
	var html = "";
	var products = [<?php
		foreach($products as $product) {
			echo '{';
			foreach($product as $key=>$value) {
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
	
	var designs = [<?php
		foreach($designs as $design) {
			echo '{';
			foreach($design as $key=>$value) {
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
	  *	@desc	go to first product
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous product
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next product
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < products.length - 1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last product
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(products.length-1);
	}
	
	/**
	  *	@desc	find product by array index
	  *	@param	int i - array index
	  *	@return	none
	*/
	function find(i) {
		current = parseInt(i);
		document.getElementById("product-input").value = products[current]['PRODUCT'];
		html = "";
		for(var i=0;i<designs.length;i++) {
			if (designs[i]['PRODUCT'] == products[current]['PRODUCT']) {
				html += "<option id=\""+designs[i]['DESIGN']+"\" value=\""+designs[i]['DESIGN']+"\">"+designs[i]['DESIGN']+"</option>";
			}
		}
		document.getElementById("design-select").innerHTML = html;
		document.getElementById("date-input").value = products[current]['DATE'] == "" ? "" : products[current]['DATE'] + " by " + products[current]['OPERATOR'];
	}
	
	/**
	  *	@desc	prep readOnly attributes and value for a new product
	  *	@param	none
	  *	@return	none
	*/
	function addProduct() {
		document.getElementById("product-input").value = "";
		document.getElementById("product-input").readOnly = false;
		document.getElementById("date-input").value = formatDate(new Date()) + " by <?=$_SESSION['initials']?>";
		document.getElementById("design-select").innerHTML = "";
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveProduct(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
		document.getElementById("add-design").disabled = false;
		document.getElementById("delete-design").disabled = false;
	}
	
	/**
	  *	@desc	prep readOnly attributes to edit a product
	  *	@param	none
	  *	@return	none
	*/
	function editProduct() {
		document.getElementById("product-input").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveProduct(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
		document.getElementById("add-design").disabled = false;
		document.getElementById("delete-design").disabled = false;
	}
	
	/**
	  *	@desc	create/display design input
	  *	@param	none
	  *	@return	none
	*/
	function addDesignForm() {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		modal.style.display = "block";
		modalContent.innerHTML = "<span class=\"close\" id=\"close\">&times;</span><input type=\"text\" name=\"design\" id=\"design\" autofocus><button id=\"submit\" onclick=\"addDesign()\">Submit</button>";
		closeForm();
	}
	
	/**
	  *	@desc	add design to product
	  *	@param	none
	  *	@return	none
	*/
	function addDesign() {
		var design = document.getElementById("design").value;
		document.getElementById("design-select").innerHTML += "<option id=\""+design+"\" value=\""+design+"\">"+design+"</option>";
		document.getElementById("close").click();
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
	  *	@desc	remove design from product
	  *	@param	none
	  *	@return	none
	*/
	function deleteDesign() {
		var design = document.getElementById("design-select").value;
		var option = document.getElementById(design);
		option.parentNode.removeChild(option);
	}
	
	/**
	  *	@desc	set fields to readOnly and find current item
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("product-input").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','editProduct()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteProduct()');
		document.getElementById("add-design").disabled = true;
		document.getElementById("delete-design").disabled = true;
		find(current);
	}
	
	/**
	  *	@desc	save new or updated product
	  *	@param	string s - whether new or updated
	  *	@return	none
	*/
	function saveProduct(s) {
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Products";
		var id = products[current]['ID'];
		var product = document.getElementById("product-input").value;
		var date = formatDate(new Date());
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Data updated") || conn.responseText.includes("Insert succeeded")) {
					saveProductDesigns(conn.responseText);
				} else {
					alert("Changes not saved. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&PRODUCT="+product+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']?>&condition=id&value="+id, true);
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
	  *	@desc	save designs to product
	  *	@param	string r - result of product save query
	  *	@return	none
	*/
	function saveProductDesigns(r){
		var conn = new XMLHttpRequest();
		var action1 = "select";
		var table1 = "Product_Designs";
		var condition = "PRODUCT";
		var value = document.getElementById("product-input").value;
		var conn2, action2, table2, conn3, action3, table3, product, options, design, designs = [], d, month, date, year, hour, minute, second, date, s, oldDesigns, newDesigns;
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				options = document.getElementById("design-select").children;
				oldDesigns = conn.responseText.split("Array");
				newDesigns = [];
				oldDesigns.shift();
				for (var i=0;i<oldDesigns.length;i++) {
					oldDesigns[i] = oldDesigns[i].split(">");
					oldDesigns[i].shift();
					for (var j=0;j<oldDesigns[i].length;j++) {
						oldDesigns[i][j] = oldDesigns[i][j].split("[")[0];
						if (oldDesigns[i][j].includes(")")) {
							oldDesigns[i][j] = oldDesigns[i][j].split(")")[0];
						}
						oldDesigns[i][j] = oldDesigns[i][j].trim();
					}
				}
				
				for (var i=0;i<options.length;i++) {
					for (var j=0;j<oldDesigns.length;j++) {
						if (options[i].value == oldDesigns[j][1]) {
							newDesigns.push(oldDesigns.splice(j, 1)[0]);
						}
					}
				}
				
				conn3 = new XMLHttpRequest();
				action3 = "delete";
				table3 = "Product_Designs";
				if (oldDesigns.length > 0) {
					for (var i=0;i<oldDesigns.length;i++) {
						value1 = oldDesigns[i][1];
						value2 = oldDesigns[i][2];
						
						conn3.onreadystatechange = function() {
							if (this.readyState == 4 && this.status == 200) {
								if (!conn3.responseText.includes("Deletion succeeded")) {
									alert("Could not delete old data. Contact IT Support. " + conn3.responseText);
								}
							}
						}
						
						conn3.open("GET","/db_query/sql2.php?action="+action3+"&table="+table3+"&DESIGN="+value1+"&PRODUCT="+value2, true);
						conn3.send();
					}
				}
				
				options = document.getElementById("design-select").children;
				for (var i=0;i<options.length;i++) {
					designs.push(options[i].value);
				}
				
				for (var i=0;i<designs.length;i++) {
					conn2 = new XMLHttpRequest();
					action2 = "";
					table2 = "Product_Designs";
					product = document.getElementById("product-input").value;
					design = designs[i];
					d = new Date();
					month = d.getMonth()+1;
					if (month < 10) {
						month = "0" + month;
					}
					date = d.getDate();
					if (date < 10) {
						date = "0" + date;
					}
					year = d.getFullYear()%100;
					hour = d.getHours();
					if (hour < 10) {
						hour = "0" + hour;
					}
					minute = d.getMinutes();
					if (minute < 10) {
						minute = "0" + minute;
					}
					second = d.getSeconds();
					if (second < 10) {
						second = "0" + second;
					}
					date = month + "/" + date + "/" + year + " " + hour + ":" + minute + ":" + second;
				
					for (var j=0;j<newDesigns.length;j++) {
						if (design == newDesigns[j][1]) {
							action2 = "update";
							id = newDesigns[j][0];
						}
					}
					if (action2 == "") {
						action2 = "insert";
						id = "";
					}
					
					conn2.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							if (r == "Insert succeeded.") {
								if (!conn2.responseText.includes("Data updated") && !conn2.responseText.includes("Insert succeeded")) {
									alert("Could not update product. Contact IT Support. " + conn2.responseText);
								}
							}
						}
					}
					
					conn2.open("GET","/db_query/sql2.php?action="+action2+"&table="+table2+"&DESIGN="+design+"&PRODUCT="+product+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&value="+id, false);
					conn2.send();
					
				}
				
				alert("Insertion succeeded.");
				window.location.replace("products.php");
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action1+"&table="+table1+"&condition="+condition+"&value="+value, true);
		conn.send();
	}
	
	/**
	  *	@desc	remove product
	  *	@param	none
	  *	@return	none
	*/
	function deleteProduct() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Products";
		var query = "&ID=" + (products[current][0]);
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				if (conn.responseText.includes("Deletion succeeded")) {
					deleteProductDesigns(products[current][1], conn.responseText);
				} else {
					alert("Product not deleted. Contact IT Support. " + conn.responseText);
				}
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, true);
		conn.send();
		
	}
	
	/**
	  *	@desc	remove designs associated with product
	  *	@param	string s - product name, string r - result of product delete query
	  *	@return	none
	*/
	function deleteProductDesigns(s, r) {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Product_Designs";
		var query = "&PRODUCT=" + s;
		
		if (true) {
			conn.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					if (conn.responseText.includes("Deletion succeeded")) {
						alert("Product deleted");
						window.location.replace("products.php");
					} else {
						alert("Product not deleted. Contact IT Support. " + conn.responseText);
					}
				}
			}
			
			conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+query, true);
			conn.send();
		} else {
			alert(r);
		}
	}
	
	/**
	  *	@desc	create/display product search form
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<table><thead><tr><th class="col1">Product</th><th class="col2">Designs</th><th class="col3">Modified</th><th class="col4">Operator</th></tr></thead><tbody>';
		
		products.forEach((item, index, array) => {
			html += '<tr id="'+index+'" onclick="selectRow(this)"><td class="col1">'+item['PRODUCT']+'</td><td class="col2"><select>';
			designs.forEach((item2, index2, array2) => {
				if (item2['PRODUCT'] == item['PRODUCT']) {
					html += '<option>'+item2['DESIGN']+'</option>';
				}
			});
			html += '</select></td><td class="col3">'+item['DATE']+'</td><td class="col4">'+item['OPERATOR']+'</td></tr>';
		});
		
		html += '</tbody></table>';
		modalContent.innerHTML = html;
		modalContent.classList.add("table-modal");
		document.getElementById("modal").style.display = "block";
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight search row, or confirm if already highlighted
	  *	@param	DOM Object tr - selected row
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
		<title>Products</title>
		<link rel="stylesheet" type="text/css" href="/styles/products.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="insertFirst()">
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="product-span">Product<br>
					<input id="product-input" type="text" readonly></span><br>
					<span id="designs-span">Designs<br>
					<select id="design-select">
					</select></span>
					<div class="buttons">
						<button id="add-design" onclick="addDesignForm()" disabled>Add</button>
						<button id="delete-design" onclick="deleteDesign()" disabled>Delete</button>
					</div>
					<span id="date-span">Last Modified<br>
					<input id="date-input" type="text" readonly></span>
				</div>
				<div class="controls">
					<button id="add" onclick="addProduct()">Add</button>
					<button onclick="insertFirst()">First</button>
					<button id="edit" onclick="editProduct()">Edit</button>
					<button onclick="goUp()">Up</button>
					<button id="delete" onclick="deleteProduct()">Delete</button>
					<button onclick="goDown()">Down</button>
					<button onclick="search()">Search</button>
					<button onclick="insertLast()">Last</button>
					<a href="../maintenance.php">Back</a>
				</div>
			</div>
		</div>
		<div id="modal" class="modal">
			<div id="modal-content" class="modal-content">
			</div>
		</div>
	</body>
</html>