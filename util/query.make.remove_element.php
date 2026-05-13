<?php
header('Content-type: application/json');
include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");
include("../classes/class.sites.php");
include("../lib/functions.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при удалении жлемента из мейка. Нужны права администратора.');

if (isset($_POST['site_id'], $_POST['gal_id'])) {
	$site_id = (int)$_POST['site_id'];
	$gal_id = (int)$_POST['gal_id'];
	if (removeFromQuery($site_id, $gal_id)) {
		$string = json_encode(
			array(
				'success' => $gal_id
			)
		);
	} else {
		$string = json_encode(
			array(
				'error' => 'Ошибка удаления галлереи из очереди сборки'
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
