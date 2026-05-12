<?php
header('Content-type: application/json');

include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");
include("../classes/class.sitesgalleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при очистке очереди изменений. Нужны права администратора.');

$sites_galleries = new SitesGalleries();
$deleted = $sites_galleries->deleteProcessedChangeQuery();
if ($deleted !== false) {
	$response = array(
		'success' => true,
		'deleted' => $deleted
	);
} else {
	$response = array('error' => 'Ошибка удаления обработанных изменений');
}


echo json_encode($response);
