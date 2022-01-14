<!DOCTYPE html>
<?php
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
?>
<html>
	<head>
		<title>Invoicing</title>
		<link rel="stylesheet" type="text/css" href="/styles/invoicing.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="header">
					<span>Order<select name="order-select">
						<option value="none"><NONE></option>
						<option value="datein">Date In</option>
						<option value="linecolor">Line Color</option>
						<option value="process">Process</option>
						<option value="targetdate">Target Date</option>
						<option value="tool">Tool</option>
					</select></span>
					<span>Filter<select name="filter-select">
						<option value="none"><NONE></option>
						<option value="datein">Date In</option>
						<option value="linecolor">Line Color</option>
						<option value="process">Process</option>
						<option value="targetdate">Target Date</option>
						<option value="tool">Tool</option>
					</select></span>
				</div>
				<div class="main">
					<table>
						<thead>
							<tr>
								<th>Target</th>
								<th>Tool</th>
								<th>Customer</th>
								<th>Shipped Date</th>
							</tr>
						</thead>
					</table>
					<span>Tool<input type="text" readonly></span>
				</div>
				<div class="left">
					<span id="job-span">Job #<input id="job-input" type="text">Batch<input type="text"></span><br>
					<span id="po-span">PO #<input id="po-input" type="text">Work Order<input type="text"></span><br>
					<span id="order-span">Order #<input id="order-input" type="text">Invoicing #<input type="text"></span><br>
					<span id="status-span">Status<input id="status-input" type="text">
				</div>
				<div class="controls">
					<button>Incoming Work</button>
					<button>Customer Status</button>
					<button>Retrieve Tool</button>
				</div>
				<div class="controls">
					<button>Invoice</button>
					<button>Order Status</button>
					<button style="margin-bottom: 4px;">Refresh</button>
					<a href="../operations.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>