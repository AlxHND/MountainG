<?php
header('Content-type: application/json');

require_once ("_auth.php");
require_once ("../classes/class.video.php");
require_once ("../classes/class.galleries.php");

$auth->requireAdminJson('Ошибка аутентификации при работе с видео-превью. Нужны права администратора.');

if (!isset($_POST['gal_id'])) {
	echo json_encode(array('error' => 'Не указан ID галереи'), JSON_UNESCAPED_UNICODE);
	exit;
}

$gal_id = (int)$_POST['gal_id'];
if ($gal_id <= 0) {
	echo json_encode(array('error' => 'Некорректный ID галереи'), JSON_UNESCAPED_UNICODE);
	exit;
}

$gallery = new Galleries($db->_db);
$result = $gallery->generateVideoPreview($gal_id);

if (!$result || empty($result['public_url'])) {
	$previewInfo = $gallery->getVideoPreviewInfo($gal_id);
	$message = 'Не удалось создать preview-видео';
	if ($previewInfo && !empty($previewInfo['error_message'])) {
		$message = $previewInfo['error_message'];
	}

	echo json_encode(array('error' => $message), JSON_UNESCAPED_UNICODE);
	exit;
}

echo json_encode(array(
	'success' => true,
	'gal_id' => $gal_id,
	'preview' => array(
		'status' => $result['preview_status'],
		'url' => $result['public_url'],
		'size' => (int)$result['preview_size'],
		'width' => (int)$result['preview_width'],
		'height' => (int)$result['preview_height'],
		'duration_ms' => (int)$result['preview_duration_ms'],
		'bitrate' => (int)$result['preview_bitrate'],
		'generated_on' => (int)$result['generated_on']
	)
), JSON_UNESCAPED_UNICODE);
?>
