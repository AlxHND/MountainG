<?php
	$scriptDir = dirname($_SERVER ['SCRIPT_FILENAME']);

	require_once ($scriptDir. "/config/config.inc");
	exec ("mysqldump --host=".DBHOST." --user=".DBUSER." --password=".DBPW." ".DBNAME." > ".$scriptDir."/dump/dump.sql");
?>
