<?php
header('Content-type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

$string = json_encode(array('error' => 'Wrong POST'));

if (
	!isset($_GET['action'], $_GET['type'])
	|| !preg_match("#^(delete|grab)$#", $_GET['action'])
	|| !preg_match("#^(gallery)$#", $_GET['type'])
) {
	echo $string;
	exit;
}

if (preg_match("#^(delete|grab)$#", $_GET['action']) && (!isset($_GET['item_id']) || !intval($_GET['item_id']))) {
	echo json_encode(array('error' => 'Ошибка: не указан ID галлереи для "' . $_GET['action'] . ':' . $_GET['type'] . '"'));
	exit;
}

require_once("../config/config.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.galleries.php");
require_once("../classes/class.models.php");
require_once("../classes/class.sites.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.tags.php");
require_once("../classes/class.sources.php");
require_once("../classes/class.new-cache.php");

$user = new Users($db->_db);
$user_name = $user->getName();
$user_ip = $user->getIP();

if (!$user->isAdmin()) {
	$string = "Ошибка аутентификации при удалении галеры. Пользователь " . $user_name . " не имеет прав на удаление галер";
	$log = new Logger($string . ",ip:" . $user_ip, true);
	echo json_encode(array('error' => $string));
	exit;
}

$galleries = new Galleries($db->_db);
if ($_GET['action'] == 'delete' && $_GET['type'] == 'gallery') {
	$gal_id = intval($_GET['item_id']);
	$status = $galleries->getStatus($gal_id);
	if ($status) {
		if ($status == 'OK') {
			$gallery_sites = $galleries->getGallerySites($gal_id);
			foreach ($gallery_sites as $site_id => $local_gal_id) {
				echo $site_id . ":" . $gal_id . "<br>";
			}
		} else {
			$string = json_encode(array('success' => 'Галера удалена', 'id' => $gal_id));
		}
	} else {
		$string = json_encode(array('error' => 'Галера #' . $gal_id . ' не найдена - нет статуса'));
	}
}

echo $string;
