<?php

	$msg = 'Part being tanked out and rescheduled.' . PHP_EOL .
		   'TOOL: ' . $_REQUEST['tool'] . PHP_EOL .
		   'PROCESS: ' . $_REQUEST['process'] . PHP_EOL .
		   'isArchived: ' . $_REQUEST['isArchived'] . PHP_EOL .
		   'isCurrent: ' . $_REQUEST['isCurrent'] . PHP_EOL .
		   'Condition 1: ' . ($_REQUEST['process'] == 'EFORM' ? 'TRUE' : 'FALSE') . PHP_EOL .
		   'Condition 2: ' . ($_REQUEST['isArchived'] == 'false' ? 'TRUE' : 'FALSE') . PHP_EOL .
		   'Condition 3: ' . ($_REQUEST['isCurrent'] == 'true' ? 'TRUE' : 'FALSE');
	mail('maclain.anderson@orafol.com','TANKING OUT',$msg,'From: OPTS@orafol.com');

?>