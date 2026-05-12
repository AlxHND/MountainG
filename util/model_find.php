<?php
header('Content-type: application/json');
if (isset ($_POST['name'])) {
	$name = $_POST['name'];
	if (isset($_POST['niche'])) $niche = $_POST['niche'];
		else $niche = false;
	require_once ("../config/config.php");
	require_once (LIB_DIR."/classes/class.logger.php");
	require_once (LIB_DIR."/classes/class.db_access.php");
	require_once (LIB_DIR."/classes/class.models.php");
	require_once (LIB_DIR."/lib/functions.php");
	require_once (LIB_DIR."/classes/class.users.php");

	$models = new CModels($db->_db);
	$models_find = $models->find_models_by_string($_POST['name'], $niche);
	//var_dump($models_find);
	if (is_array($models_find)) {
		$result['success'] = 'true';
		foreach ($models_find as $model) {
			$result[$model['id']]['id'] = $model['id'];
			$result[$model['id']]['name'] = $model['name'];
			$result[$model['id']]['thumb'] = $model['thumb'];
		}
		$string = json_encode($result);
	} else $string = json_encode(array('error' => "Error!"));
} else $string = json_encode(array('error' => "Error!"));
echo $string;	
?>
