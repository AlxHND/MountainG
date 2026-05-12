<?php
header('Content-type: application/json');

include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при удалении из CDN очереди. Нужны права администратора.');

if (isset($_POST['gal_id'])) {
	$gal_id = (int)$_POST['gal_id'];
	$gallery_worker = new Galleries($db->_db);
	if ($gallery_worker->deleteGalleryFromCdnQuery($gal_id)) {
		$response = array('success' => $gal_id);
	} else {
		$response = array('error' => 'Ошибка удаления элемента CDN очереди');
	}
}


echo json_encode($response);
