<?php
if (isset($_POST['name']) && isset($_POST['id'])) {
	$pseudonimId = (int)$_POST['id'];
	$name = $_POST['name'];

	if ($pseudonimId !== 0) {
		require_once("../config/config.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		require_once("../classes/class.models.php");
		require_once("../lib/functions.php");

		$models = new CModels($db->_db);

		$result = $models->updatePseudonim($pseudonimId, $name);

		if ($result) echo $result;
		else echo "failed";
	} else echo "failed";
} else echo "failed";
