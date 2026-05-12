<?php
header('Content-type: application/json');

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

require_once ("../config/config.php");
require_once ("../classes/class.logger.php");
require_once ("../classes/class.db_access.php");
require_once ("../classes/class.galleries.php");
require_once ("../classes/class.users.php");	

$image_id = isset($_POST['thumbId']) ? intval($_POST['thumbId']) : 0;
$gal_id = isset($_POST['galId']) ? intval($_POST['galId']) : 0;
$userAuth = new Users($db->_db);
$userId = $userAuth->getId();

if (!$userAuth->allowedToTag() && !$userAuth->isAdmin()) {
	$string = "Ошибка аутентификации при снятии тумбы с RSS";
	$log = new Logger($string, true);
	echo json_encode(array('error' => $string));
	exit;
}

if (!$image_id || !$gal_id) {
	echo json_encode(array('error' => 'Не указан galId или thumbId'));
	exit;
}

$rssFlag = (isset($_POST['status']) && $_POST['status'] == 'true') ? 0 : 1;
$stmt = $db->_db->prepare("UPDATE galleries_pix SET rss_flag = :rss_flag WHERE image_id = :image_id AND gal_id = :gal_id");

if ($stmt && $stmt->execute(array(
	':rss_flag' => $rssFlag,
	':image_id' => $image_id,
	':gal_id' => $gal_id
))) {
	if ($rssFlag === 0) {
		$userAuth->galleryThumbRssUnset($userId, $gal_id, $image_id);
	} else {
		$userAuth->galleryThumbRssSet($userId, $gal_id, $image_id);
	}

	echo json_encode(array('success' => $image_id));
	exit;
}

$string = "Ошибка обновления RSS-флага";
$log = new Logger($string, true);
echo json_encode(array('error' => $string));
?>
