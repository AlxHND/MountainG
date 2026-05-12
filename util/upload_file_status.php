<?php
header('Content-Type: application/json; charset=utf-8');

require_once ("_auth.php");

$auth->requireUploadJson();

function upload_status_chunks_dir() {
	return TMPDIR . '/.chunk_uploads';
}

function upload_status_sanitize_upload_id($uploadId) {
	$uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$uploadId);
	return $uploadId !== '' ? $uploadId : false;
}

$uploadId = upload_status_sanitize_upload_id(isset($_GET['upload_id']) ? $_GET['upload_id'] : '');
if (!$uploadId) {
	echo json_encode(array('error' => 'Invalid upload_id'), JSON_UNESCAPED_UNICODE);
	exit;
}

$statusPath = upload_status_chunks_dir() . '/trace-' . $uploadId . '.json';
$logPath = upload_status_chunks_dir() . '/trace-' . $uploadId . '.log';

$payload = array(
	'upload_id' => $uploadId,
	'status_exists' => is_file($statusPath),
	'log_exists' => is_file($logPath),
	'status' => false,
	'last_log_lines' => array()
);

if (is_file($statusPath)) {
	$content = file_get_contents($statusPath);
	$data = json_decode($content, true);
	if (is_array($data)) {
		$payload['status'] = $data;
	}
}

if (is_file($logPath)) {
	$lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines) {
		$payload['last_log_lines'] = array_slice($lines, -5);
	}
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
?>
