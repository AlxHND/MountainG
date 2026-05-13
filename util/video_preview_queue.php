<?php
header('Content-type: application/json');

require_once ("_auth.php");
require_once ("../classes/class.galleries.php");

$auth->requireAdminJson('Ошибка аутентификации при постановке видео-превью в очередь. Нужны права администратора.');

if (!isset($_POST['gal_id'])) {
	echo json_encode(array('error' => 'Не указан ID галереи'), JSON_UNESCAPED_UNICODE);
	exit;
}

$gal_id = (int)$_POST['gal_id'];
if ($gal_id <= 0) {
	echo json_encode(array('error' => 'Некорректный ID галереи'), JSON_UNESCAPED_UNICODE);
	exit;
}

$requestIp = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
$gallery = new Galleries($db->_db);
$result = $gallery->queueVideoPreviewJob($gal_id, $requestIp);

if (!$result || isset($result['error'])) {
	echo json_encode(array('error' => $result && isset($result['error']) ? $result['error'] : 'Не удалось поставить preview в очередь'), JSON_UNESCAPED_UNICODE);
	exit;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
