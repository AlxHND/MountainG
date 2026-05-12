<?php

header('Content-type: application/json');
require_once("../config/config.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.models.php");
require_once("../classes/class.sites.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.tags.php");
require_once("../classes/class.sources.php");
require_once("../classes/class.galleries.php");
require_once("../classes/class.new-cache.php");
require_once("../lib/functions.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при апдейте сайта. Нужны права администратора.');

if (isset($_POST['site_id'])) {

	$sites_tags = new Tags($db->_db);
	$site_worker =  new Sites($db->_db);

	$site = $site_worker->getSite($_POST['site_id']);
	// var_dump($_POST['site_id'], $_POST, $sites_tags->updateSitesTag($_POST['site_id'], $_POST));
	if ($site_worker->getId() && $sites_tags->updateSitesTag($_POST['site_id'], $_POST)) {
		$cache_worker = new CacheRebuilder($db->_db, $site_worker->redisServer());
		$cache_worker->server_initializeSiteTags($_POST['site_id'], $site_worker->redisServer());
		$string = json_encode(
			array(
				'success' => 'ok'
			)
		);
	} else {
		$string = json_encode(
			array(
				'error' => 'Ошибка изменения тега'
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
