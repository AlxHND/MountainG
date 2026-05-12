<?php
header('Content-type: application/json');



require_once("../config/config.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.tags.php");
require_once("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при ручном процессинге галеры. Нужны права администратора.');

if (isset($_POST['gal_id'])) {
	$gal_id = (int)$_POST['gal_id'];

	$gallery_worker = new Galleries;
	if ($gallery_worker->manualTitleToTagsAndProcessing($gal_id)) {
		$string = json_encode(
			array(
				'success' => $gal_id
			)
		);
	} else {
		$string = json_encode(
			array(
				'error' => 'Title was not processed GID#' . $gal_id
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
