<?php
include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.sites.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при работе с исключением галер на сайт. Нужны права администратора.');

if (isset($_POST['siteId']) && isset($_POST['galId'])) {
	$galId = (int)$_POST['galId'];
	$siteId = (int)$_POST['siteId'];
	if ($galId > 0 && $siteId > 0) {

		$sites = new Sites($db->_db);

		$sites->switchSite($siteId);

		if (!$q = $sites->excludeGallery($galId)) echo "failed";
	}
}
