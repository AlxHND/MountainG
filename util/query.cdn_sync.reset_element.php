<?php
header('Content-type: application/json');

include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при сбросе CDN очереди. Нужны права администратора.');

if (isset($_POST['gal_id'])) {
	$gal_id = (int)$_POST['gal_id'];
	$gallery_worker = new Galleries($db->_db);
	if ($gallery_worker->updateCdnQueryStatus($gal_id, 'new')) {
		$response = array('success' => $gal_id);
	} else {
		$response = array('error' => 'Ошибка сброса CDN очереди');
	}
}


echo json_encode($response);
