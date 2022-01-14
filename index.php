<!DOCTYPE html>
<?php
/**
  *	@desc Login page
*/
	
	//show error if login failed
	if(isset($_POST['error'])) {
		?><script>alert("<?= $_POST['error'];?>");</script><?php
	}
?>
<script>

	/**
	  * @desc 	preps username field for typing; 
	  * 	  	removes text and changes text color to black
	  * @param 	DOM Object input - username field
	  * @return none
	*/
	function focusUser(input) {
		if (input.value === "username") {
			input.value = "";
			input.style.color = "black";
		}
	}
	
	/**
	  * @desc	returns username field to default
	  * 		if no text in field
	  * @param	DOM Object input - username field
	  * @return	none
	*/
	function blurUser(input) {
		if (input.value === "") {
			input.value = "username";
			input.style.color = "#ccc";
		}
	}
	
	/**
	  * @desc	preps password field for typeing;
	  * 		removes text and changes text color to black;
	  * @param	DOM Object input - password field
	  * @return	none
	*/
	function focusPass(input) {
		if (input.value === "password") {
			input.value = "";
			input.type = "password";
			input.style.color = "black";
		}
	}
	
	/**
	  * @desc	returns password field to default
	  * 		if no text in field
	  * @param	DOM Object input - password field
	  * @return	none
	*/
	function blurPass(input) {
		if (input.value === "") {
			input.value = "password";
			input.type = "text";
			input.style.color = "#ccc";
		}
	}
</script>
<html>
	<head>
		<title>OPTS</title>
		<link rel="stylesheet" type="text/css" href="styles/index.css">
	</head>
	<body onload="document.getElementById('name').focus()">
		<div class="login-box">
			<div class="input-box">
				<form action="view/home.php" method="post">
					<input value="username" onfocus="focusUser(this)" onblur="blurUser(this)" class="input" type="text" name="name" id="name">
					<input value="password" onfocus="focusPass(this)" onblur="blurPass(this)" class="input" type="text" name="pass" id="pass">
					<input class="button" type="submit" name="login" value="Login">
				</form>
			</div>
		</div>
	</body>
</html>