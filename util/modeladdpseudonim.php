<?php
if (isset($_POST['name']) && isset($_POST['modelId'])) {
	$modelId = (int)$_POST['modelId'];
	$name = $_POST['name'];

	if ($modelId !== 0) {
		require_once("../config/config.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		require_once("../classes/class.models.php");
		require_once("../lib/functions.php");
		$models = new CModels($db->_db);

		$result = $models->insertPseudonim($modelId, $name);

		if ($result) echo $result;
		else echo "failed";
	}
}
