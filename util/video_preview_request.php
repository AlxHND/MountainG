<?php

require_once __DIR__ . "/video_preview_api_common.php";

$requestIp = video_preview_api_require_ip('request');

$gal_id = isset($_REQUEST['gal_id']) ? (int)$_REQUEST['gal_id'] : 0;
$callback_url = isset($_REQUEST['callback_url']) ? video_preview_api_validate_callback_url($_REQUEST['callback_url']) : '';

if ($gal_id <= 0) {
	video_preview_api_json(array('error' => 'Wrong gal_id'));
}

if (isset($_REQUEST['callback_url']) && $callback_url === false) {
	video_preview_api_json(array('error' => 'Wrong callback_url'));
}

$gallery = new Galleries($db->_db);
$result = $gallery->requestVideoPreview($gal_id, $callback_url, $requestIp);

if (!$result || isset($result['error'])) {
	video_preview_api_json(array(
		'status' => 'error',
		'error' => $result && isset($result['error']) ? $result['error'] : 'Preview request failed'
	));
}

video_preview_api_json($result);
