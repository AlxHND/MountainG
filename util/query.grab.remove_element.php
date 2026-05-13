<?php
header('Content-type: application/json');

include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при удалении галеры из граба. Нужны права администратора.');

if (!isset($_POST['id'])) {
	$string = json_encode(
		array(
			'error' => 'Wrong POST'
		)
	);
}
$id = (int)$_POST['id'];
$galleries = new Galleries($db->_db);
if ($galleries->removeFromQuery($id)) {
	$string = json_encode(
		array(
			'success' => $id
		)
	);
} else {
	$string = json_encode(
		array(
			'error' => 'Ошибка удаления галлереи из очереди граба'
		)
	);
}

echo $string;
