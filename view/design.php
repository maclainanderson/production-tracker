<!DOCTYPE html>
<?php
	//get user lists
	require_once("../utils.php");
	
	session_start();
	
	if (!isset($_SESSION['name'])){
		header("Location: /index.php");
	}
	
	if (!in_array($_SESSION['name'], $admins) && !in_array($_SESSION['name'], $schedulers)) {
		header("Location: /view/home.php");
	}
	
	//set up connection
	$serverName = "OPTSAPPS02\SQLEXPRESS";
	$connectionInfo = array("Database"=>"OPTS Production Tracking Service");
	$conn = sqlsrv_connect($serverName, $connectionInfo);
	
	//list of designs
	$designs = array();
	
	//fetch data
	if ($conn) {
		$result = sqlsrv_query($conn, "SELECT * FROM Designs ORDER BY DESIGN ASC;");
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
	
	//handle input data
	if (!isset($_POST['returnpath'])) {
		$_POST['returnpath'] = "/view/home.php";
	}
	
	if (!isset($_POST['design'])) {
		$_POST['design'] = $designs[0][1];
	}
	
	//var_dump($designs);
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
	var isMetric = false;
	var designs = [<?php
		foreach($designs as $design) {
			echo '{';
			foreach($design as $id=>$value) {
				echo '"' . $id . '": `';
				if ($value instanceof DateTime) {
					echo date_format($value,'m/d/y');
				} else {
					echo addslashes(trim(str_replace(array("\n","\t","\r"), ' ', $value)));
				}
				echo '`';
				echo ',';
			}
			echo '}';
			echo ',';
		}
	?>];
	
	/**
	  *	@desc	find design if we got here from a specific one, otherwise find first design
	  *	@param	none
	  *	@return	none
	*/
	function initialize() {
		<?php if (isset($_POST['design'])) { ?>
		designs.forEach((item, index, array) => {
			if (item['DESIGN'] == "<?=$_POST['design']?>") {
				find(index);
			}
		});
		<?php } else { ?>
		insertFirst();
		<?php } ?>
	}
	
	/**
	  *	@desc	go to first design
	  *	@param	none
	  *	@return	none
	*/
	function insertFirst() {
		find(0);
		document.getElementById("add").disabled = false;
	}
	
	/**
	  *	@desc	go to previous design
	  *	@param	none
	  *	@return	none
	*/
	function goUp() {
		if (current > 0) {
			find(current-1);
		}
	}
	
	/**
	  *	@desc	go to next design
	  *	@param	none
	  *	@return	none
	*/
	function goDown() {
		if (current < designs.length - 1) {
			find(current+1);
		}
	}
	
	/**
	  *	@desc	go to last design
	  *	@param	none
	  *	@return	none
	*/
	function insertLast() {
		find(designs.length-1);
	}
	
	/**
	  *	@desc	find design by ID
	  *	@param	int i - array index in designs list
	  *	@return	none
	*/
	function find(i) {
		current = i;
		var degrees;
		var degreesDecimal;
		var minutes;
		var minutesDecimal;
		var seconds;
		document.getElementById("design").value = designs[current]['DESIGN'];
		document.getElementById("designer").value = designs[current]['OPERATOR'];
		document.getElementById("date").value = designs[current]['DATE'];
		document.getElementById("drawing").value = designs[current]['DRAWING'];
		document.getElementById("file").value = designs[current]['FILENAME'];
		document.getElementById("fresnel").value = designs[current]['FRESNEL_CONJUGATE'];
		document.getElementById("plano").value = designs[current]['PLANO_CONJUGATE'];
		document.getElementById("focal").value = designs[current]['FOCAL_LENGTH'];
		document.getElementById("grooves").value = designs[current]['GROOVES'];
		document.getElementById("pitch").value = designs[current]['MASTER_PITCH'];
		document.getElementById("radius").value = designs[current]['RADIUS'];
		document.getElementById("diameter").value = designs[current]['LENS_DIAMETER'];
		if (designs[current]['MAX_SLOPE'].includes(":")) {
			document.getElementById("slope").value = designs[current]['MAX_SLOPE'];
			document.getElementById("slope2").value = ((parseInt(designs[current]['MAX_SLOPE'].split(":")[0])) + (parseInt(designs[current]['MAX_SLOPE'].split(":")[1]) / 60) + (parseInt(designs[current]['MAX_SLOPE'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['MAX_SLOPE'].includes(".") && parseFloat(designs[current]['MAX_SLOPE']) != 0) {
			degrees = parseInt(designs[current]['MAX_SLOPE']);
			degreesDecimal = parseFloat(designs[current]['MAX_SLOPE']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("slope").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("slope2").value = designs[current]['MAX_SLOPE'];
		} else {
			document.getElementById("slope").value = "   :  :  ";
			document.getElementById("slope2").value = "0.00000";
		}
		if (designs[current]['MAX_DRAFT'].includes(":")) {
			document.getElementById("max-draft").value = designs[current]['MAX_DRAFT'];
			document.getElementById("max-draft2").value = ((parseInt(designs[current]['MAX_DRAFT'].split(":")[0])) + (parseInt(designs[current]['MAX_DRAFT'].split(":")[1]) / 60) + (parseInt(designs[current]['MAX_DRAFT'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['MAX_DRAFT'].includes(".") && parseFloat(designs[current]['MAX_DRAFT']) != 0) {
			degrees = parseInt(designs[current]['MAX_DRAFT']);
			degreesDecimal = parseFloat(designs[current]['MAX_DRAFT']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("max-draft").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("max-draft2").value = designs[current]['MAX_DRAFT'];
		} else {
			document.getElementById("max-draft").value = "   :  :  ";
			document.getElementById("max-draft2").value = "0.00000";
		}
		if (designs[current]['DIAMO_ANGLE'].includes(":")) {
			document.getElementById("tool-angle").value = designs[current]['DIAMO_ANGLE'];
			document.getElementById("tool-angle2").value = ((parseInt(designs[current]['DIAMO_ANGLE'].split(":")[0])) + (parseInt(designs[current]['DIAMO_ANGLE'].split(":")[1]) / 60) + (parseInt(designs[current]['DIAMO_ANGLE'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['DIAMO_ANGLE'].includes(".") && parseFloat(designs[current]['DIAMO_ANGLE']) != 0) {
			degrees = parseInt(designs[current]['DIAMO_ANGLE']);
			degreesDecimal = parseFloat(designs[current]['DIAMO_ANGLE']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("tool-angle").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("tool-angle2").value = designs[current]['DIAMO_ANGLE'];
		} else {
			document.getElementById("tool-angle").value = "   :  :  ";
			document.getElementById("tool-angle2").value = "0.00000";
		}
		document.getElementById("max-depth").value = designs[current]['MAX_GROOVE_DEPTH'];
		if (designs[current]['MIN_DRAFT'].includes(":")) {
			document.getElementById("min-draft").value = designs[current]['MIN_DRAFT'];
			document.getElementById("min-draft2").value = ((parseInt(designs[current]['MIN_DRAFT'].split(":")[0])) + (parseInt(designs[current]['MIN_DRAFT'].split(":")[1]) / 60) + (parseInt(designs[current]['MIN_DRAFT'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['MIN_DRAFT'].includes(".") && parseFloat(designs[current]['MIN_DRAFT']) != 0) {
			degrees = parseInt(designs[current]['MIN_DRAFT']);
			degreesDecimal = parseFloat(designs[current]['MIN_DRAFT']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("min-draft").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("min-draft2").value = designs[current]['MIN_DRAFT'];
		} else {
			document.getElementById("min-draft").value = "   :  :  ";
			document.getElementById("min-draft2").value = "0.00000";
		}
		if (designs[current]['PRISM_ANGLE'].includes(":")) {
			document.getElementById("prism").value = designs[current]['PRISM_ANGLE'];
			document.getElementById("prism2").value = ((parseInt(designs[current]['PRISM_ANGLE'].split(":")[0])) + (parseInt(designs[current]['PRISM_ANGLE'].split(":")[1]) / 60) + (parseInt(designs[current]['PRISM_ANGLE'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['PRISM_ANGLE'].includes(".") && parseFloat(designs[current]['PRISM_ANGLE']) != 0) {
			degrees = parseInt(designs[current]['PRISM_ANGLE']);
			degreesDecimal = parseFloat(designs[current]['PRISM_ANGLE']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("prism").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("prism2").value = designs[current]['PRISM_ANGLE'];
		} else {
			document.getElementById("prism").value = "   :  :  ";
			document.getElementById("prism2").value = "0.00000";
		}
		document.getElementById("prism-depth").value = designs[current]['PRISM_DEPTH'];
		if (designs[current]['TILT_ANGLE'].includes(":")) {
			document.getElementById("tilt-angle").value = designs[current]['TILT_ANGLE'];
			document.getElementById("tilt-angle2").value = ((parseInt(designs[current]['TILT_ANGLE'].split(":")[0])) + (parseInt(designs[current]['TILT_ANGLE'].split(":")[1]) / 60) + (parseInt(designs[current]['TILT_ANGLE'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['TILT_ANGLE'].includes(".") && parseFloat(designs[current]['TILT_ANGLE']) != 0) {
			degrees = parseInt(designs[current]['TILT_ANGLE']);
			degreesDecimal = parseFloat(designs[current]['TILT_ANGLE']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("tilt-angle").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("tilt-angle2").value = designs[current]['TILT_ANGLE'];
		} else {
			document.getElementById("tilt-angle").value = "   :  :  ";
			document.getElementById("tilt-angle2").value = "0.00000";
		}
		document.getElementById("pitch1").value = designs[current]['PITCH1'];
		if (designs[current]['GROOVE_ANGLE1'].includes(":")) {
			document.getElementById("groove-angle1-1").value = designs[current]['GROOVE_ANGLE1'];
			document.getElementById("groove-angle1-2").value = ((parseInt(designs[current]['GROOVE_ANGLE1'].split(":")[0])) + (parseInt(designs[current]['GROOVE_ANGLE1'].split(":")[1]) / 60) + (parseInt(designs[current]['GROOVE_ANGLE1'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['GROOVE_ANGLE1'].includes(".") && parseFloat(designs[current]['GROOVE_ANGLE1']) != 0) {
			degrees = parseInt(designs[current]['GROOVE_ANGLE1']);
			degreesDecimal = parseFloat(designs[current]['GROOVE_ANGLE1']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("groove-angle1-1").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("groove-angle1-2").value = designs[current]['GROOVE_ANGLE1'];
		} else {
			document.getElementById("groove-angle1-1").value = "   :  :  ";
			document.getElementById("groove-angle1-2").value = "0.00000";
		}
		if (designs[current]['BASE_ANGLE1'].includes(":")) {
			document.getElementById("base-angle1-1").value = designs[current]['BASE_ANGLE1'];
			document.getElementById("base-angle1-2").value = ((parseInt(designs[current]['BASE_ANGLE1'].split(":")[0])) + (parseInt(designs[current]['BASE_ANGLE1'].split(":")[1]) / 60) + (parseInt(designs[current]['BASE_ANGLE1'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['BASE_ANGLE1'].includes(".") && parseFloat(designs[current]['BASE_ANGLE1']) != 0) {
			degrees = parseInt(designs[current]['BASE_ANGLE1']);
			degreesDecimal = parseFloat(designs[current]['BASE_ANGLE1']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("base-angle1-2").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("base-angle1-2").value = designs[current]['BASE_ANGLE1'];
		} else {
			document.getElementById("base-angle1-1").value = "   :  :  ";
			document.getElementById("base-angle1-2").value = "0.00000";
		}
		document.getElementById("pitch2").value = designs[current]['PITCH2'];
		if (designs[current]['GROOVE_ANGLE2'].includes(":")) {
			document.getElementById("groove-angle2-1").value = designs[current]['GROOVE_ANGLE2'];
			document.getElementById("groove-angle2-2").value = ((parseInt(designs[current]['GROOVE_ANGLE2'].split(":")[0])) + (parseInt(designs[current]['GROOVE_ANGLE2'].split(":")[1]) / 60) + (parseInt(designs[current]['GROOVE_ANGLE2'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['GROOVE_ANGLE2'].includes(".") && parseFloat(designs[current]['GROOVE_ANGLE2']) != 0) {
			degrees = parseInt(designs[current]['GROOVE_ANGLE2']);
			degreesDecimal = parseFloat(designs[current]['GROOVE_ANGLE2']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("groove-angle2-1").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("groove-angle2-2").value = designs[current]['GROOVE_ANGLE2'];
		} else {
			document.getElementById("groove-angle2-1").value = "   :  :  ";
			document.getElementById("groove-angle2-2").value = "0.00000";
		}
		if (designs[current]['BASE_ANGLE2'].includes(":")) {
			document.getElementById("base-angle2-1").value = designs[current]['BASE_ANGLE2'];
			document.getElementById("base-angle2-2").value = ((parseInt(designs[current]['BASE_ANGLE2'].split(":")[0])) + (parseInt(designs[current]['BASE_ANGLE2'].split(":")[1]) / 60) + (parseInt(designs[current]['BASE_ANGLE2'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['BASE_ANGLE2'].includes(".") && parseFloat(designs[current]['BASE_ANGLE2']) != 0) {
			degrees = parseInt(designs[current]['BASE_ANGLE2']);
			degreesDecimal = parseFloat(designs[current]['BASE_ANGLE2']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("base-angle2-1").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("base-angle2-2").value = designs[current]['BASE_ANGLE2'];
		} else {
			document.getElementById("base-angle2-1").value = "   :  :  ";
			document.getElementById("base-angle2-2").value = "0.00000";
		}
		document.getElementById("pitch3").value = designs[current]['PITCH3'];
		if (designs[current]['GROOVE_ANGLE3'].includes(":")) {
			document.getElementById("groove-angle3-1").value = designs[current]['GROOVE_ANGLE3'];
			document.getElementById("groove-angle3-2").value = ((parseInt(designs[current]['GROOVE_ANGLE3'].split(":")[0])) + (parseInt(designs[current]['GROOVE_ANGLE3'].split(":")[1]) / 60) + (parseInt(designs[current]['GROOVE_ANGLE3'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['GROOVE_ANGLE3'].includes(".") && parseFloat(designs[current]['GROOVE_ANGLE3']) != 0) {
			degrees = parseInt(designs[current]['GROOVE_ANGLE3']);
			degreesDecimal = parseFloat(designs[current]['GROOVE_ANGLE3']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("groove-angle3-1").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("groove-angle3-2").value = designs[current]['GROOVE_ANGLE3'];
		} else {
			document.getElementById("groove-angle3-1").value = "   :  :  ";
			document.getElementById("groove-angle3-2").value = "0.00000";
		}
		if (designs[current]['BASE_ANGLE3'].includes(":")) {
			document.getElementById("base-angle3-1").value = designs[current]['BASE_ANGLE3'];
			document.getElementById("base-angle3-2").value = ((parseInt(designs[current]['BASE_ANGLE3'].split(":")[0])) + (parseInt(designs[current]['BASE_ANGLE3'].split(":")[1]) / 60) + (parseInt(designs[current]['BASE_ANGLE3'].split(":")[2]) / 3600)).toFixed(5);
		} else if (designs[current]['BASE_ANGLE3'].includes(".") && parseFloat(designs[current]['BASE_ANGLE3']) != 0) {
			degrees = parseInt(designs[current]['BASE_ANGLE3']);
			degreesDecimal = parseFloat(designs[current]['BASE_ANGLE3']) % degrees;
			minutes = parseInt(degreesDecimal * 60)
			if (minutes != 0) {
				minutesDecimal = (degreesDecimal * 60) % minutes;
				seconds = parseInt(minutesDecimal * 60);
			} else {
				seconds = 0;
			}
			degrees = degrees < 100 ? "0" + degrees : degrees;
			degrees = parseInt(degrees) < 10 ? "0" + degrees : degrees;
			minutes = minutes < 10 ? "0" + minutes : minutes;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			document.getElementById("base-angle3-1").value = degrees + ":" + minutes + ":" + seconds;
			document.getElementById("base-angle3-2").value = designs[current]['BASE_ANGLE3'];
		} else {
			document.getElementById("base-angle3-1").value = "   :  :  ";
			document.getElementById("base-angle3-2").value = "0.00000";
		}
		document.getElementById("features-textarea").value = designs[current]['FEATURES'];
		document.getElementById("comment-textarea").value = designs[current]['COMMENT'];
	}
	
	/**
	  *	@desc	clear fields for new design
	  *	@param	none
	  *	@return	none
	*/
	function newDesign() {
		document.getElementById("design").value = "";
		document.getElementById("design").readOnly = false;
		document.getElementById("designer").value = "";
		document.getElementById("designer").readOnly = false;
		document.getElementById("date").value = "";
		document.getElementById("date").readOnly = false;
		document.getElementById("drawing").value = "";
		document.getElementById("drawing").readOnly = false;
		document.getElementById("file").value = "";
		document.getElementById("file").readOnly = false;
		document.getElementById("fresnel").value = "";
		document.getElementById("fresnel").readOnly = false;
		document.getElementById("plano").value = "";
		document.getElementById("plano").readOnly = false;
		document.getElementById("focal").value = "";
		document.getElementById("focal").readOnly = false;
		document.getElementById("grooves").value = "";
		document.getElementById("grooves").readOnly = false;
		document.getElementById("pitch").value = "";
		document.getElementById("pitch").readOnly = false;
		document.getElementById("radius").value = "";
		document.getElementById("radius").readOnly = false;
		document.getElementById("diameter").value = "";
		document.getElementById("diameter").readOnly = false;
		document.getElementById("slope").value = "   :  :  ";
		document.getElementById("slope").readOnly = false;
		document.getElementById("slope2").value = "";
		document.getElementById("slope2").readOnly = false;
		document.getElementById("max-draft").value = "   :  :  ";
		document.getElementById("max-draft").readOnly = false;
		document.getElementById("max-draft2").value = "";
		document.getElementById("max-draft2").readOnly = false;
		document.getElementById("tool-angle").value = "   :  :  ";
		document.getElementById("tool-angle").readOnly = false;
		document.getElementById("tool-angle2").value = "";
		document.getElementById("tool-angle2").readOnly = false;
		document.getElementById("max-depth").value = "";
		document.getElementById("max-depth").readOnly = false;
		document.getElementById("min-draft").value = "   :  :  ";
		document.getElementById("min-draft").readOnly = false;
		document.getElementById("min-draft2").value = "";
		document.getElementById("min-draft2").readOnly = false;
		document.getElementById("prism").value = "   :  :  ";
		document.getElementById("prism").readOnly = false;
		document.getElementById("prism2").value = "";
		document.getElementById("prism2").readOnly = false;
		document.getElementById("prism-depth").value = "";
		document.getElementById("prism-depth").readOnly = false;
		document.getElementById("tilt-angle").value = "   :  :  ";
		document.getElementById("tilt-angle").readOnly = false;
		document.getElementById("tilt-angle2").value = "";
		document.getElementById("tilt-angle2").readOnly = false;
		document.getElementById("pitch1").value = "";
		document.getElementById("pitch1").readOnly = false;
		document.getElementById("groove-angle1-1").value = "   :  :  ";
		document.getElementById("groove-angle1-1").readOnly = false;
		document.getElementById("groove-angle1-2").value = "";
		document.getElementById("groove-angle1-2").readOnly = false;
		document.getElementById("base-angle1-1").value = "   :  :  ";
		document.getElementById("base-angle1-1").readOnly = false;
		document.getElementById("base-angle1-2").value = "";
		document.getElementById("base-angle1-2").readOnly = false;
		document.getElementById("pitch2").value = "";
		document.getElementById("pitch2").readOnly = false;
		document.getElementById("groove-angle2-1").value = "   :  :  ";
		document.getElementById("groove-angle2-1").readOnly = false;
		document.getElementById("groove-angle2-2").value = "";
		document.getElementById("groove-angle2-2").readOnly = false;
		document.getElementById("base-angle2-1").value = "   :  :  ";
		document.getElementById("base-angle2-1").readOnly = false;
		document.getElementById("base-angle2-2").value = "";
		document.getElementById("base-angle2-2").readOnly = false;
		document.getElementById("pitch3").value = "";
		document.getElementById("pitch3").readOnly = false;
		document.getElementById("groove-angle3-1").value = "   :  :  ";
		document.getElementById("groove-angle3-1").readOnly = false;
		document.getElementById("groove-angle3-2").value = "";
		document.getElementById("groove-angle3-2").readOnly = false;
		document.getElementById("base-angle3-1").value = "   :  :  ";
		document.getElementById("base-angle3-1").readOnly = false;
		document.getElementById("base-angle3-2").value = "";
		document.getElementById("base-angle3-2").readOnly = false;
		document.getElementById("features-textarea").value = "";
		document.getElementById("features-textarea").readOnly = false;
		document.getElementById("comment-textarea").value = "";
		document.getElementById("comment-textarea").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveDesign(\'add\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	set fields to edit design
	  *	@param	none
	  *	@return	none
	*/
	function editDesign() {
		document.getElementById("design").readOnly = false;
		document.getElementById("designer").readOnly = false;
		document.getElementById("date").readOnly = false;
		document.getElementById("drawing").readOnly = false;
		document.getElementById("file").readOnly = false;
		document.getElementById("fresnel").readOnly = false;
		document.getElementById("plano").readOnly = false;
		document.getElementById("focal").readOnly = false;
		document.getElementById("grooves").readOnly = false;
		document.getElementById("pitch").readOnly = false;
		document.getElementById("radius").readOnly = false;
		document.getElementById("diameter").readOnly = false;
		document.getElementById("slope").readOnly = false;
		document.getElementById("slope2").readOnly = false;
		document.getElementById("max-draft").readOnly = false;
		document.getElementById("max-draft2").readOnly = false;
		document.getElementById("tool-angle").readOnly = false;
		document.getElementById("tool-angle2").readOnly = false;
		document.getElementById("max-depth").readOnly = false;
		document.getElementById("min-draft").readOnly = false;
		document.getElementById("min-draft2").readOnly = false;
		document.getElementById("prism").readOnly = false;
		document.getElementById("prism2").readOnly = false;
		document.getElementById("prism-depth").readOnly = false;
		document.getElementById("tilt-angle").readOnly = false;
		document.getElementById("tilt-angle2").readOnly = false;
		document.getElementById("pitch1").readOnly = false;
		document.getElementById("groove-angle1-1").readOnly = false;
		document.getElementById("groove-angle1-2").readOnly = false;
		document.getElementById("base-angle1-1").readOnly = false;
		document.getElementById("base-angle1-2").readOnly = false;
		document.getElementById("pitch2").readOnly = false;
		document.getElementById("groove-angle2-1").readOnly = false;
		document.getElementById("groove-angle2-2").readOnly = false;
		document.getElementById("base-angle2-1").readOnly = false;
		document.getElementById("base-angle2-2").readOnly = false;
		document.getElementById("pitch3").readOnly = false;
		document.getElementById("groove-angle3-1").readOnly = false;
		document.getElementById("groove-angle3-2").readOnly = false;
		document.getElementById("base-angle3-1").readOnly = false;
		document.getElementById("base-angle3-2").readOnly = false;
		document.getElementById("features-textarea").readOnly = false;
		document.getElementById("comment-textarea").readOnly = false;
		document.getElementById("add").disabled = true;
		document.getElementById("edit").innerHTML = "Save";
		document.getElementById("edit").setAttribute('onclick','saveDesign(\'edit\')');
		document.getElementById("delete").innerHTML = "Cancel";
		document.getElementById("delete").setAttribute('onclick','cancel()');
	}
	
	/**
	  *	@desc	save new or edited design
	  *	@param	string s - either add or edit
	  *	@return	none
	*/
	function saveDesign(s) {
		var conn = new XMLHttpRequest();
		var action = s == "add" ? "insert" : "update";
		var table = "Designs";
		var id = designs[current]['ID'];
		var design = document.getElementById("design").value;
		var date = document.getElementById("date").value;
		var drawing = document.getElementById("drawing").value;
		var file = document.getElementById("file").value;
		var fresnel = document.getElementById("fresnel").value;
		var plano = document.getElementById("plano").value;
		var focal = document.getElementById("focal").value;
		var grooves = document.getElementById("grooves").value;
		var pitch = document.getElementById("pitch").value;
		var radius = document.getElementById("radius").value;
		var diameter = document.getElementById("diameter").value;
		var slope = document.getElementById("slope2").value == "" ? document.getElementById("slope").value : document.getElementById("slope2").value;
		var maxDraft = document.getElementById("max-draft2").value == "" ? document.getElementById("max-draft").value : document.getElementById("max-draft2").value;
		var toolAngle = document.getElementById("tool-angle2").value == "" ? document.getElementById("tool-angle").value : document.getElementById("tool-angle2").value;
		var maxDepth = document.getElementById("max-depth").value;
		var minDraft = document.getElementById("min-draft2").value == "" ? document.getElementById("min-draft").value : document.getElementById("min-draft2").value;
		var prism = document.getElementById("prism2").value == "" ? document.getElementById("prism").value : document.getElementById("prism2").value;
		var prismDepth = document.getElementById("prism-depth").value;
		var tiltAngle = document.getElementById("tilt-angle2").value == "" ? document.getElementById("tilt-angle").value : document.getElementById("tilt-angle2").value;
		var pitch1 = document.getElementById("pitch1").value;
		var grooveAngle1 = document.getElementById("groove-angle1-2").value == "" ? document.getElementById("groove-angle1-1").value : document.getElementById("groove-angle1-2").value;
		var baseAngle1 = document.getElementById("base-angle1-2").value == "" ? document.getElementById("base-angle1-1").value : document.getElementById("base-angle1-2").value;
		var pitch2 = document.getElementById("pitch2").value;
		var grooveAngle2 = document.getElementById("groove-angle2-2").value == "" ? document.getElementById("groove-angle2-1").value : document.getElementById("groove-angle2-2").value;
		var baseAngle2 = document.getElementById("base-angle2-2").value == "" ? document.getElementById("base-angle2-1").value : document.getElementById("base-angle2-2").value;
		var pitch3 = document.getElementById("pitch3").value;
		var grooveAngle3 = document.getElementById("groove-angle3-2").value == "" ? document.getElementById("groove-angle3-1").value : document.getElementById("groove-angle3-2").value;
		var baseAngle3 = document.getElementById("base-angle3-2").value == "" ? document.getElementById("base-angle3-1").value : document.getElementById("base-angle3-2").value;
		var features = document.getElementById("features-textarea").value;
		var comment = document.getElementById("comment-textarea").value;
		var d = new Date();
		var month = d.getMonth()+1;
		if (month < 10) {
			month = "0" + month;
		}
		var date = d.getDate();
		if (date < 10) {
			date = "0" + date;
		}
		var year = d.getFullYear()%100;
		var hour = d.getHours();
		if (hour < 10) {
			hour = "0" + hour;
		}
		var minute = d.getMinutes();
		if (minute < 10) {
			minute = "0" + minute;
		}
		var second = d.getSeconds();
		if (second < 10) {
			second = "0" + second;
		}
		var date = month + "/" + date + "/" + year + " " + hour + ":" + minute + ":" + second;
		
		conn.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				alert(conn.responseText);
				window.location.replace("design.php");
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&DESIGN="+design+"&=DRAWING"+drawing+"&FILENAME="+file+"&FRESNEL_CONJUGATE="+fresnel+"&PLANO_CONJUGATE="+plano+"&FOCAL_LENGTH="+focal+"&GROOVES="+grooves+"&MASTER_PITCH="+pitch+"&VARIABLE="+"false"+"&RADIUS="+radius+"&LENS_DIAMETER="+diameter+"&MAX_SLOPE="+slope+"&MAX_DRAFT="+maxDraft+"&DIAMO_ANGLE="+toolAngle+"&MAX_GROOVE_DEPTH="+maxDepth+"&MIN_DRAFT="+minDraft+"&PRISM_ANGLE="+prism+"&PRISM_DEPTH="+prismDepth+"&TILT_ANGLE="+tiltAngle+"&PITCH1="+pitch1+"&PITCH2="+pitch2+"&PITCH3="+pitch3+"&GROOVE_ANGLE1="+grooveAngle1+"&GROOVE_ANGLE2="+grooveAngle2+"&GROOVE_ANGLE3="+grooveAngle3+"&BASE_ANGLE1="+baseAngle1+"&BASE_ANGLE2="+baseAngle2+"&BASE_ANGLE3="+baseAngle3+"&FEATURES="+features+"&COMMENT="+comment+"&DATE="+date+"&OPERATOR=<?php echo $_SESSION['initials']; ?>&condition=id&="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	cancel new or edited design
	  *	@param	none
	  *	@return	none
	*/
	function cancel() {
		document.getElementById("design").readOnly = true;
		document.getElementById("designer").readOnly = true;
		document.getElementById("date").readOnly = true;
		document.getElementById("drawing").readOnly = true;
		document.getElementById("file").readOnly = true;
		document.getElementById("fresnel").readOnly = true;
		document.getElementById("plano").readOnly = true;
		document.getElementById("focal").readOnly = true;
		document.getElementById("grooves").readOnly = true;
		document.getElementById("pitch").readOnly = true;
		document.getElementById("radius").readOnly = true;
		document.getElementById("diameter").readOnly = true;
		document.getElementById("slope").readOnly = true;
		document.getElementById("slope2").readOnly = true;
		document.getElementById("max-draft").readOnly = true;
		document.getElementById("max-draft2").readOnly = true;
		document.getElementById("tool-angle").readOnly = true;
		document.getElementById("tool-angle2").readOnly = true;
		document.getElementById("max-depth").readOnly = true;
		document.getElementById("min-draft").readOnly = true;
		document.getElementById("min-draft2").readOnly = true;
		document.getElementById("prism").readOnly = true;
		document.getElementById("prism2").readOnly = true;
		document.getElementById("prism-depth").readOnly = true;
		document.getElementById("tilt-angle").readOnly = true;
		document.getElementById("tilt-angle2").readOnly = true;
		document.getElementById("pitch1").readOnly = true;
		document.getElementById("groove-angle1-1").readOnly = true;
		document.getElementById("groove-angle1-2").readOnly = true;
		document.getElementById("base-angle1-1").readOnly = true;
		document.getElementById("base-angle1-2").readOnly = true;
		document.getElementById("pitch2").readOnly = true;
		document.getElementById("groove-angle2-1").readOnly = true;
		document.getElementById("groove-angle2-2").readOnly = true;
		document.getElementById("base-angle2-1").readOnly = true;
		document.getElementById("base-angle2-2").readOnly = true;
		document.getElementById("pitch3").readOnly = true;
		document.getElementById("groove-angle3-1").readOnly = true;
		document.getElementById("groove-angle3-2").readOnly = true;
		document.getElementById("base-angle3-1").readOnly = true;
		document.getElementById("base-angle3-2").readOnly = true;
		document.getElementById("features-textarea").readOnly = true;
		document.getElementById("comment-textarea").readOnly = true;
		document.getElementById("add").disabled = false;
		document.getElementById("edit").innerHTML = "Edit";
		document.getElementById("edit").setAttribute('onclick','editDesign()');
		document.getElementById("delete").innerHTML = "Delete";
		document.getElementById("delete").setAttribute('onclick','deleteDesign()');
		find(current);
	}
	
	/**
	  *	@desc	remove design
	  *	@param	none
	  *	@return	none
	*/
	function deleteDesign() {
		var conn = new XMLHttpRequest();
		var action = "delete";
		var table = "Designs";
		var id = designs[current]['ID'];
		
		conn.onreadystatechange = function() {
			if (conn.readyState == 4 && conn.status == 200) {
				alert(conn.responseText);
				window.location.replace("design.php");
			}
		}
		
		conn.open("GET","/db_query/sql2.php?action="+action+"&table="+table+"&id="+id, true);
		conn.send();
	}
	
	/**
	  *	@desc	create search table based on data in search input
	  *	@param	none
	  *	@return	none
	*/
	function search() {
		var modalContent = document.getElementById("modal-content");
		var html = '<span id="close">&times;</span><br>';
		html += '<input type="text" id="search-input"><button id="search">Search</button>';
		modalContent.innerHTML = html;
		document.getElementById("modal").style.display = "block";
		
		document.getElementById("search").addEventListener('click', function() {
			var found = false;
			designs.forEach((item, index, array) => {
				if (item['DESIGN'].toUpperCase().includes(document.getElementById("search-input").value.toUpperCase()) && found == false) {
					getList(index);
					found = true;
				}
			});
		
			if (!found) {
				alert("Search term not found.");
			}
		});
		
		document.getElementById("search-input").focus();
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search").click();
			}
		}
		
		closeForm();
	}
	
	/**
	  *	@desc	get list of blanks from search
	  *	@param	index - array index of first match
	  *	@return	none
	*/
	function getList(index) {
		var modal = document.getElementById("modal");
		var modalContent = document.getElementById("modal-content");
		if (document.getElementsByTagName('table').length > 0) {
			modalContent.removeChild(document.getElementsByTagName('table')[0]);
		}
		
		html = '<table><thead><tr><th class="col1">Design</th><th class="col2">Drawing #</th><th class="col3">File Name</th><th class="col4">Date</th><th class="col5">Designer</th></tr></thead><tbody>';
		for (var i=0;i<designs.length;i++) {
			html += '<tr onclick="selectRow(this)" id="' + i + '"><td class="col1">' + designs[i]['DESIGN'] + '</td><td class="col2">' + designs[i]['DRAWING'] + '</td><td class="col3">' + designs[i]['FILENAME'] + '</td><td class="col4">' + designs[i]['DATE'] + '</td><td class="col5">' + designs[i]['OPERATOR'] + '</td></tr>';
		}
		html += '</tbody></table>';
		
		modalContent.innerHTML += html;
		
		document.getElementById(index).scrollIntoView();
		
		document.getElementById("search").onclick = function() {
			var found = false;
			designs.forEach((item, index, array) => {
				if (item['DESIGN'].toUpperCase().includes(document.getElementById("search-input").value.toUpperCase()) && found == false) {
					getList(index);
					found = true;
				}
			});
		
			if (!found) {
				alert("Search term not found.");
			}
		}
		
		document.getElementById("search-input").focus();
		document.getElementById("search-input").onkeydown = function(e) {
			if (e.key == "Enter") {
				document.getElementById("search").click();
			}
		}
		
		closeForm();
	}
	
	/**
	  *	@desc	highlight blank row, confirm if second click
	  *	@param	tr - row selected
	  *	@return	none
	*/
	function selectRow(tr) {
		if (tr.style.backgroundColor == 'black') {
			find(tr.id);
			document.getElementById('close').click();
		} else {
			for (var i=0;i<tr.parentNode.children.length;i++) {
				tr.parentNode.children[i].style.backgroundColor = "white";
				tr.parentNode.children[i].style.color = "black";
			}
			
			tr.style.backgroundColor = "black";
			tr.style.color = "white";
		}
		
		closeForm();
	}
	
	/**
	  *	@desc	set onclick to close modal form
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
	  *	@desc	auto-format date fields to MM/DD/YY
	  *	@param	DOM Object input - field to format
	  *	@return	none
	*/
	function fixDate(input) {
		var key = event.keyCode || event.charCode;
		
		var regex = /\/|\-|\\|\*/;
		
		if (key==8 || key==46) {
			if (regex.test(input.value.slice(-1))) {
				input.value = input.value.slice(0,-1);
			}
		} else {
			switch(input.value.length) {
				case 0:
					
					break;
				case 1:
				case 4:
				case 7:
				case 8:
					if (regex.test(input.value.slice(-1))) {
						input.value = input.value.slice(0,-1);
					}
					break;
				case 2:
					if (regex.test(input.value.charAt(1))) {
						input.value = "0" + input.value.slice(0,-1) + "/";
					} else {
						input.value += "/";
					}
					break;
				case 5:
					if (regex.test(input.value.charAt(4))) {
						var inputArr = input.value.split(regex);
						inputArr.pop();
						input.value = inputArr[0] + "/0" + inputArr.pop() + "/";
					} else {
						input.value += "/";
					}
					break;
				case 3:
				case 6:
					if (!regex.test(input.value.slice(-3))) {
						input.value = input.value.slice(0,-1) + "/" + input.value.slice(-1);
					}
					break;
				default:
			}
		}
	}
</script>
<html>
	<head>
		<title>Design Information</title>
		<link rel="stylesheet" type="text/css" href="/styles/design.css">
		<link rel="icon" href="/favicon.ico">
	</head>
	<body onload="initialize()">
		<div class="outer">
			<div class="inner">
				<div class="top">
					<div class="top-left">
						<div class="basics">
							<span style="margin-left: 23px;">Design<input type="text" id="design" readonly>
							Designer<input type="text" id="designer" readonly>
							Date<input onkeydown="fixDate(this)" type="text" id="date" readonly><br>
							Drawing #<input type="text" id="drawing" readonly></span>
						</div>
						<div class="details">
							<span style="margin-left: 61px;">File Name<input type="text" id="file" readonly></span><br>
							<div class="details-left">
								Fresnel Conjugate<input type="text" id="fresnel" readonly><span class="units">(in)</span><br>
								Plano Conjugate<input type="text" id="plano" readonly><span class="units">(in)</span><br>
								Focal Length<input type="text" id="focal" readonly><span class="units">(in)</span><br>
								Number of Grooves<input type="text" id="grooves" style="margin-right: 23px;" readonly><br>
								Master Pitch<input type="text" id="pitch" readonly><span class="units">(in)</span><br>
								Radius<input type="text" id="radius" readonly><span class="units">(in)</span><br>
								Lens Diameter<input type="text" id="diameter" readonly><span class="units">(in)</span>
							</div>
							<div class="details-right">
								Maximum Slope<input type="text" id="slope" readonly><input type="text" id="slope2" readonly><br>
								Maximum Draft<input type="text" id="max-draft" readonly><input type="text" id="max-draft2" readonly><br>
								Angle of Diamo. Tool<input type="text" id="tool-angle" readonly><input type="text" id="tool-angle2" readonly><br>
								Maximum Groove Depth<input type="text" id="max-depth" readonly><span class="units" style="margin-right: 86px;">(in)</span><br>
								Minimum Draft<input type="text" id="min-draft" readonly><input type="text" id="min-draft2" readonly><br>
								Prism Angle<input type="text" id="prism" readonly><input type="text" id="prism2" readonly>
							</div>
						</div>
					</div>
					<div class="controls">
						<div class="controls-left">
							<button class="small" id="add" onclick="newDesign()">Add</button><br>
							<button class="small" id="edit" onclick="editDesign()">Edit</button><br>
							<button class="small" id="delete" onclick="deleteDesign()">Delete</button><br>
							<button class="small" onclick="search()">Search</button>
						</div>
						<div class="controls-right">
							<button class="small" onclick="insertFirst()">First</button><br>
							<button class="small" onclick="goUp()">Up</button><br>
							<button class="small" onclick="goDown()">Down</button><br>
							<button class="small" onclick="insertLast()">Last</button>
						</div>
						<button class="big" id="unit-switch" onclick="switchUnit(this)" value="metric">Metric</button>
						<a class="big" href="<?=$_POST['returnpath']?>">Back</a>
					</div>
				</div>
				<div class="bottom">
					<span style="margin-left: 58px;">Prism Depth<input type="text" id="prism-depth" readonly>(in)</span>
					<span style="margin-left: 96px;">Tilt Angle<input type="text" id="tilt-angle" readonly><input type="text" id="tilt-angle2" readonly></span><br><br>
					<div class="angle-labels">
						<span id="pitch-label">Pitch</span><br>
						<span id="groove-label">Groove Angle</span><br>
						<span id="base-label">Base Angle</span><br>
					</div>
					<div class="orientation1">
						<input type="text" id="pitch1" readonly><span class="units">(in)</span>
						<input type="text" id="groove-angle1-1" readonly><input type="text" id="groove-angle1-2" readonly>
						<input type="text" id="base-angle1-1" readonly><input type="text" id="base-angle1-2" readonly>
					</div>
					<div class="orientation2">
						<input type="text" id="pitch2" readonly><span class="units">(in)</span>
						<input type="text" id="groove-angle2-1" readonly><input type="text" id="groove-angle2-2" readonly>
						<input type="text" id="base-angle2-1" readonly><input type="text" id="base-angle2-2" readonly>
					</div>
					<div class="orientation3">
						<input type="text" id="pitch3" readonly><span class="units">(in)</span>
						<input type="text" id="groove-angle3-1" readonly><input type="text" id="groove-angle3-2" readonly>
						<input type="text" id="base-angle3-1" readonly><input type="text" id="base-angle3-2" readonly>
					</div><br><br>
					<span style="margin-left: 66px; vertical-align: top;">Features:</span><textarea rows="4" cols="80" style="margin-left: 5px;" id="features-textarea" readonly></textarea>
				</div>
				<div class="comments">
					<span style="margin-left: 52px; vertical-align: top;">Comments:</span><textarea rows="4" cols="80" style="margin: 3px 4px;" id="comment-textarea" readonly></textarea>
				</div>
			</div>
		</div>
		<div id="modal"><div id="modal-content"></div></div>
	</body>
</html>