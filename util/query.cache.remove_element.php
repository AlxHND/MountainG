<?php
header('Content-type: application/json');
include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.sitesgalleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при удалении из очереди кэша. Нужны права администратора.');

if (isset($_POST['id'])) {
	$id = (int)$_POST['id'];
	$sites_galleries = new SitesGalleries();
	if ($sites_galleries->deleteFromCacheQueryById($id)) {
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
