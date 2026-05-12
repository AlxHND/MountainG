<?php
header('Content-type: application/json');

include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");
include("../classes/class.sitesgalleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при сбросе очереди. Нужны права администратора.');

if (isset($_POST['id'])) {
	$id = (int)$_POST['id'];
	$sites_galleries = new SitesGalleries();
	if ($sites_galleries->resetChangeQueryById($id)) {
		$response = array('success' => $id);
	} else {
		$response = array('error' => 'Ошибка сброса элемента очереди изменений');
	}
}

echo json_encode($response);
