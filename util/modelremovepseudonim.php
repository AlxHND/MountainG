<?php
if (isset($_POST['pseudonimId'])) {
	$pseudonimId = (int)$_POST['pseudonimId'];
	if ($pseudonimId !== 0) {
		require_once("../config/config.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		require_once("../classes/class.models.php");
		require_once("../lib/functions.php");

		$models = new CModels($db->_db);

		$result = $models->removePseudonim($pseudonimId);

		if ($result) echo "ok";
		else echo "failed";
	}
}
