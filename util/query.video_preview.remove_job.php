<?php
header('Content-type: application/json');

require_once("_auth.php");
require_once("../classes/class.galleries.php");

$auth->requireAdminJson('Ошибка аутентификации при удалении preview job. Нужны права администратора.');

if (!isset($_POST['job_id'])) {
	echo json_encode(array('error' => 'Не указан job_id'), JSON_UNESCAPED_UNICODE);
	exit;
}

$job_id = (int)$_POST['job_id'];
if ($job_id <= 0) {
	echo json_encode(array('error' => 'Некорректный job_id'), JSON_UNESCAPED_UNICODE);
	exit;
}

$gallery = new Galleries($db->_db);
$result = $gallery->deleteVideoPreviewJob($job_id);

if (!$result || isset($result['error'])) {
	echo json_encode(array('error' => $result && isset($result['error']) ? $result['error'] : 'Ошибка удаления preview job'), JSON_UNESCAPED_UNICODE);
	exit;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
