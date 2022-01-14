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
		<title>Mass Changes</title>
		<link rel="stylesheet" type="text/css" href="/styles/masschange.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body>
		<div class="outer">
			<div class="inner">
				<div class="content">
					<span id="mandrel-span">Mandrel<input id="mandrel-input" type="text"></span><br>
					<span id="type-span">Tool Type<select id="type-select">
						<option value="none"><NONE></option>
						<option value="10x10stamp">10X10STAMP</option>
						<option value="10x10tree">10X10TREE</option>
						<option value="allitestam">ALLITESTAM</option>
						<option value="allitetree">ALLITETREE</option>
						<option value="classstamp">CLASSSTAMP</option>
						<option value="classtree">CLASSTREE</option>
						<option value="fresdish">FRESDISH</option>
						<option value="fresnel">FRESNEL</option>
						<option value="frespip">FRESPIP</option>
						<option value="hpm1a">HPM1A</option>
						<option value="hpm1b">HPM1B</option>
						<option value="hpm2a">HPM2A</option>
						<option value="hpm2b">HPM2B</option>
						<option value="lenticular">LENTICULAR</option>
						<option value="meilitree">MEILITREE</option>
						<option value="meilitstam">MEILITSTAM</option>
						<option value="plano">PLANO</option>
						<option value="rf151tree">RF151TREE</option>
						<option value="rf201stamp">RF201STAMP</option>
						<option value="rf201tree">RF201TREE</option>
						<option value="rf250stamp">RF250STAMP</option>
						<option value="rf250tree">RF250TREE</option>
						<option value="rf262stamp">RF262STAMP</option>
						<option value="rf262tree">RF262TREE</option>
						<option value="rf301stamp">RF301STAMP</option>
						<option value="rf301tree">RF301TREE</option>
						<option value="rf350stamp">RF350STAMP</option>
						<option value="rf350tree">RF350TREE</option>
						<option value="rf362stamp">RF362STAMP</option>
						<option value="rf362tree">RF362TREE</option>
						<option value="rf601stamp">RF601STAMP</option>
						<option value="rf601tree">RF601TREE</option>
					</select></span>
				</div>
				<div class="controls">
					<button>Select Tools</button>
					<button>Save</button>
					<a href="../utilities.php">Back</a>
				</div>
			</div>
		</div>
	</body>
</html>