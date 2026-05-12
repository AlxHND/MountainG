<?php
header('Content-type: application/json');

include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");
include("../classes/class.writers.php");


require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при удалении деска. Нужны права администратора.');

if (isset($_POST['id'])) {
	$id = (int)$_POST['id'];
	$writer_query = new WritersQuery();
	if ($writer_query->removeFromQuery($id)) {
		$string = json_encode(
			array(
				'success' => $id
			)
		);
	} else {
		$string = json_encode(
			array(
				'error' => 'Ошибка удаления галлереи из очереди тайтлов'
			)
		);
	}
} else {
	$string = json_encode(
		array(
			'error' => 'Wrong POST'
		)
	);
}


echo $string;
