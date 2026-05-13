<?php

require_once __DIR__ . "/video_preview_api_common.php";

$workerIp = video_preview_api_require_ip('worker');

$gallery = new Galleries($db->_db);
$job = $gallery->getNextVideoPreviewJob($workerIp);

if (!$job) {
	video_preview_api_json(array('status' => 'empty'));
}

video_preview_api_json(array(
	'status' => 'ok',
	'job' => $job
));
