<?php

require_once __DIR__ . "/video_preview_api_common.php";

$workerIp = video_preview_api_require_ip('worker');

$job_id = isset($_REQUEST['job_id']) ? (int)$_REQUEST['job_id'] : 0;
$status = isset($_REQUEST['status']) ? strtolower(trim((string)$_REQUEST['status'])) : '';
$error = isset($_REQUEST['error']) ? trim((string)$_REQUEST['error']) : '';

if ($job_id <= 0) {
	video_preview_api_json(array('error' => 'Wrong job_id'));
}

if (!preg_match('#^(ok|fail|error)$#', $status)) {
	video_preview_api_json(array('error' => 'Wrong status'));
}

$gallery = new Galleries($db->_db);
$result = $gallery->completeVideoPreviewJob($job_id, $status, $error, $workerIp);

if (!$result || isset($result['error'])) {
	video_preview_api_json(array(
		'status' => 'error',
		'error' => $result && isset($result['error']) ? $result['error'] : 'Preview completion failed'
	));
}

video_preview_api_json($result);
