<?php

header('Content-Type: application/json; charset=utf-8');

require_once("_auth.php");
require_once("../classes/class.galleries.php");
require_once("../classes/class.sources.php");

if (!function_exists('sanitize_non_utf')) {
	require_once dirname(__DIR__) . "/lib/functions.php";
}

$user = $auth->requireUploadJson();

function upload_json_response(array $payload, $statusCode = 200)
{
	http_response_code($statusCode);
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function upload_log($message, $error = false)
{
	$prefix = 'ZIP_UPLOAD';
	$uploadId = isset($_POST['upload_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_POST['upload_id']) : '';
	if ($uploadId !== '') {
		$prefix .= '[' . $uploadId . ']';
	}

	new Logger($prefix . ': ' . $message, $error);
}

function upload_trace_path($uploadId = '')
{
	$uploadId = upload_sanitize_upload_id($uploadId);
	if (!$uploadId) {
		$uploadId = 'unknown';
	}

	return upload_chunks_dir() . '/trace-' . $uploadId . '.log';
}

function upload_trace_status_path($uploadId = '')
{
	$uploadId = upload_sanitize_upload_id($uploadId);
	if (!$uploadId) {
		$uploadId = 'unknown';
	}

	return upload_chunks_dir() . '/trace-' . $uploadId . '.json';
}

function upload_trace($message, array $context = array())
{
	$uploadId = isset($_POST['upload_id']) ? (string)$_POST['upload_id'] : '';
	$line = date('Y-m-d H:i:s') . ' | ' . $message;
	if ($context) {
		$line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
	}

	$dir = upload_chunks_dir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}

	@file_put_contents(upload_trace_path($uploadId), $line . "\n", FILE_APPEND);
}

function upload_trace_status($stage, array $context = array())
{
	$uploadId = isset($_POST['upload_id']) ? (string)$_POST['upload_id'] : '';
	$payload = array(
		'upload_id' => upload_sanitize_upload_id($uploadId),
		'stage' => $stage,
		'time' => date('Y-m-d H:i:s'),
		'context' => $context
	);

	$dir = upload_chunks_dir();
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}

	@file_put_contents(upload_trace_status_path($uploadId), json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function upload_chunks_dir()
{
	return TMPDIR . '/.chunk_uploads';
}

function upload_ensure_dir($dir)
{
	if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
		throw new RuntimeException('Не удалось создать временную папку для chunk upload.');
	}
}

function upload_sanitize_upload_id($uploadId)
{
	$uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$uploadId);
	if ($uploadId === '') {
		return false;
	}

	return $uploadId;
}

function upload_part_path($uploadId)
{
	return upload_chunks_dir() . '/' . $uploadId . '.part';
}

function upload_meta_path($uploadId)
{
	return upload_chunks_dir() . '/' . $uploadId . '.json';
}

function upload_stream_append($sourcePath, $destinationPath)
{
	$source = fopen($sourcePath, 'rb');
	$destination = fopen($destinationPath, 'ab');

	if (!$source || !$destination) {
		if ($source) {
			fclose($source);
		}
		if ($destination) {
			fclose($destination);
		}
		throw new RuntimeException('Ошибка записи chunk во временный файл.');
	}

	stream_copy_to_stream($source, $destination);
	fclose($source);
	fclose($destination);
}

function upload_detect_mime($filePath)
{
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->file($filePath);

	if (
		preg_match('#video/(avi|msvideo|x-msvideo|x-flv|mpeg|quicktime|mp4|x-m4v|x-ms-wmv|x-ms-asf)#i', $mime)
		|| preg_match('#flv-application|application/(octet-stream|x-mp4)#i', $mime)
	) {
		return 'video/mp4';
	}

	if (preg_match('#application/(x-gzip|zip|x-zip|octet-stream)#i', $mime)) {
		$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
		if ($extension === 'zip') {
			return 'application/zip';
		}
	}

	return $mime;
}

function upload_detect_gallery_type($filePath)
{
	$mime = upload_detect_mime($filePath);

	if ($mime === 'video/mp4') {
		return array(
			'mime' => $mime,
			'ext' => 'tmp',
			'gallery_type' => 'Movies',
			'gallery_status' => 'newzip'
		);
	}

	if ($mime === 'application/zip') {
		return array(
			'mime' => $mime,
			'ext' => 'zip',
			'gallery_type' => 'New',
			'gallery_status' => 'newzip'
		);
	}

	return false;
}

function upload_finalize_gallery($db, $sources, $galleryWorker, $tempPath, $originalName, $paysiteId, $title, $desc)
{
	$paysiteId = (int)$paysiteId;
	upload_log("finalize start, source file '" . $originalName . "', tempPath '" . $tempPath . "', size=" . (@filesize($tempPath)));
	upload_trace('finalize start', array('file' => $originalName, 'temp_path' => $tempPath, 'size' => @filesize($tempPath), 'paysite' => $paysiteId));
	upload_trace_status('finalize_start', array('file' => $originalName, 'size' => @filesize($tempPath), 'paysite' => $paysiteId));

	if (false == $paysite = $sources->getSource($paysiteId)) {
		upload_log('finalize failed: paysite ' . $paysiteId . ' not found', true);
		upload_trace('paysite not found', array('paysite' => $paysiteId));
		upload_trace_status('error', array('message' => 'paysite not found'));
		throw new RuntimeException('Unknown Paysite/Source Site');
	}

	upload_trace('paysite loaded', array('paysite_id' => $paysite['id'], 'niche' => $paysite['niche']));
	upload_trace_status('paysite_loaded', array('paysite_id' => $paysite['id'], 'niche' => $paysite['niche']));

	$typeInfo = upload_detect_gallery_type($tempPath);
	if (!$typeInfo) {
		upload_log("finalize failed: invalid mime for '" . $tempPath . "'", true);
		upload_trace('mime detection failed', array('path' => $tempPath));
		upload_trace_status('error', array('message' => 'mime detection failed'));
		throw new RuntimeException('Invalid file format.');
	}
	upload_log("detected type " . $typeInfo['gallery_type'] . ", ext " . $typeInfo['ext'] . ", mime " . $typeInfo['mime']);
	upload_trace('mime detected', $typeInfo);
	upload_trace_status('mime_detected', $typeInfo);

	upload_trace('md5 start');
	upload_trace_status('md5_start');
	$md5 = md5_file($tempPath);
	upload_trace('md5 done', array('md5' => $md5));
	upload_trace_status('md5_done', array('md5' => $md5));

	upload_trace('setting session lock wait timeout', array('seconds' => 15));
	upload_trace_status('db_session_setup', array('innodb_lock_wait_timeout' => 15));
	try {
		$db->_db->exec("SET SESSION innodb_lock_wait_timeout = 15");
	} catch (Exception $e) {
		upload_trace('failed to set innodb_lock_wait_timeout', array('message' => $e->getMessage()));
	}

	upload_trace('addGallery start', array(
		'url' => $originalName,
		'paysite_id' => $paysite['id'],
		'niche' => $paysite['niche'],
		'type' => $typeInfo['gallery_type'],
		'status' => $typeInfo['gallery_status'],
		'title_length' => strlen((string)$title),
		'desc_length' => strlen((string)$desc)
	));
	upload_trace_status('db_insert_start', array('type' => $typeInfo['gallery_type'], 'status' => $typeInfo['gallery_status']));
	$galId = $galleryWorker->addGallery(
		$originalName,
		$paysite['id'],
		$paysite['niche'],
		$typeInfo['gallery_type'],
		$typeInfo['gallery_status'],
		(string)$title,
		(string)$desc,
		$md5
	);

	if (!$galId) {
		$insertError = $galleryWorker->getInsertError();
		upload_log('addGallery failed: ' . ($insertError ? $insertError : 'unknown DB insert error'), true);
		upload_trace('addGallery failed', array('error' => $insertError));
		upload_trace_status('error', array('message' => $insertError ? $insertError : 'addGallery failed'));
		throw new RuntimeException($insertError ? $insertError : 'Failed! Gallery was not added to DB');
	}
	upload_log('gallery added to DB: GID#' . $galId);
	upload_trace('addGallery done', array('gallery_id' => $galId));
	upload_trace_status('db_insert_done', array('gallery_id' => $galId));

	$finalPath = TMPDIR . '/' . $galId . '.' . $typeInfo['ext'];

	if ($typeInfo['gallery_type'] === 'Movies') {
		upload_log('setting fetched status for movie GID#' . $galId);
		upload_trace('setStatus fetched start', array('gallery_id' => $galId));
		$galleryWorker->setStatus($galId, 'fetched');
		upload_trace('setStatus fetched done', array('gallery_id' => $galId));
	}

	upload_log("renaming '" . $tempPath . "' to '" . $finalPath . "'");
	upload_trace('rename start', array('from' => $tempPath, 'to' => $finalPath));
	if (!rename($tempPath, $finalPath)) {
		$galleryWorker->setStatus($galId, 'fetching_fail');
		upload_log('rename failed during finalize for GID#' . $galId, true);
		upload_trace('rename failed', array('gallery_id' => $galId, 'to' => $finalPath));
		upload_trace_status('error', array('message' => 'rename failed', 'gallery_id' => $galId));
		throw new RuntimeException('Rename error during finalize.');
	}

	chmod($finalPath, 0666);
	upload_log('file renamed OK for GID#' . $galId);
	upload_trace('rename done', array('gallery_id' => $galId, 'path' => $finalPath));
	$sources->paysiteUpdated($paysite['id']);
	upload_trace('paysiteUpdated done', array('paysite_id' => $paysite['id']));
	$galleryWorker->addToQuery($galId);
	upload_log('finalize done for GID#' . $galId . ', queued for processing');
	upload_trace('addToQuery done', array('gallery_id' => $galId));
	upload_trace_status('done', array('gallery_id' => $galId, 'path' => $finalPath));

	return array(
		'gallery_id' => $galId,
		'gallery_type' => $typeInfo['gallery_type'],
		'path' => $finalPath
	);
}

function upload_handle_chunk()
{
	if (!isset($_FILES['upfile']['error']) || is_array($_FILES['upfile']['error'])) {
		throw new RuntimeException('Invalid chunk parameters.');
	}

	if ($_FILES['upfile']['error'] !== UPLOAD_ERR_OK) {
		throw new RuntimeException('Chunk upload error: ' . $_FILES['upfile']['error']);
	}

	$uploadId = upload_sanitize_upload_id(isset($_POST['upload_id']) ? $_POST['upload_id'] : '');
	$chunkIndex = isset($_POST['chunk_index']) ? (int)$_POST['chunk_index'] : -1;
	$chunkOffset = isset($_POST['chunk_offset']) ? (int)$_POST['chunk_offset'] : -1;
	$totalSize = isset($_POST['total_size']) ? (int)$_POST['total_size'] : 0;
	$fileName = basename(isset($_POST['file_name']) ? $_POST['file_name'] : '');

	if (!$uploadId || $chunkIndex < 0 || $chunkOffset < 0 || $totalSize <= 0 || $fileName === '') {
		throw new RuntimeException('Chunk metadata is invalid.');
	}
	upload_log("chunk start idx=" . $chunkIndex . ", offset=" . $chunkOffset . ", total=" . $totalSize . ", file='" . $fileName . "'");
	upload_trace('chunk start', array('index' => $chunkIndex, 'offset' => $chunkOffset, 'total' => $totalSize, 'file' => $fileName));

	upload_ensure_dir(upload_chunks_dir());

	$partPath = upload_part_path($uploadId);
	$metaPath = upload_meta_path($uploadId);

	if ($chunkIndex === 0) {
		if (is_file($partPath)) {
			unlink($partPath);
		}
		if (is_file($metaPath)) {
			unlink($metaPath);
		}
	}

	$currentSize = is_file($partPath) ? filesize($partPath) : 0;
	if ($currentSize !== $chunkOffset) {
		throw new RuntimeException('Chunk offset mismatch. Expected ' . $currentSize . ', received ' . $chunkOffset);
	}

	upload_stream_append($_FILES['upfile']['tmp_name'], $partPath);

	$uploadedSize = filesize($partPath);
	if ($uploadedSize > $totalSize) {
		throw new RuntimeException('Uploaded file is larger than declared size.');
	}
	upload_log("chunk saved idx=" . $chunkIndex . ", uploaded=" . $uploadedSize . "/" . $totalSize);
	upload_trace_status('chunk_saved', array('index' => $chunkIndex, 'uploaded_size' => $uploadedSize, 'total_size' => $totalSize));

	file_put_contents($metaPath, json_encode(array(
		'file_name' => $fileName,
		'total_size' => $totalSize,
		'updated_at' => time()
	)));

	upload_json_response(array(
		'success' => true,
		'uploaded_size' => $uploadedSize,
		'total_size' => $totalSize,
		'percent' => round(($uploadedSize / $totalSize) * 100, 2)
	));
}

function upload_handle_finalize($db)
{
	$uploadId = upload_sanitize_upload_id(isset($_POST['upload_id']) ? $_POST['upload_id'] : '');
	$totalSize = isset($_POST['total_size']) ? (int)$_POST['total_size'] : 0;
	$fileName = basename(isset($_POST['file_name']) ? $_POST['file_name'] : '');
	$title = isset($_POST['title']) ? $_POST['title'] : '';
	$desc = isset($_POST['desc']) ? $_POST['desc'] : '';
	$paysiteId = isset($_POST['paysite']) ? (int)$_POST['paysite'] : 0;

	if (!$uploadId || $totalSize <= 0 || $fileName === '' || $paysiteId <= 0) {
		throw new RuntimeException('Finalize metadata is invalid.');
	}
	upload_log("finalize request received, file='" . $fileName . "', total=" . $totalSize . ", paysite=" . $paysiteId);
	upload_trace('finalize request received', array('file' => $fileName, 'total' => $totalSize, 'paysite' => $paysiteId));
	upload_trace_status('finalize_requested', array('file' => $fileName, 'total' => $totalSize, 'paysite' => $paysiteId));

	$partPath = upload_part_path($uploadId);
	$metaPath = upload_meta_path($uploadId);

	if (!is_file($partPath)) {
		upload_log('finalize failed: partial file not found', true);
		upload_trace('partial file not found', array('path' => $partPath));
		upload_trace_status('error', array('message' => 'partial file not found'));
		throw new RuntimeException('Partial uploaded file not found.');
	}

	if (filesize($partPath) !== $totalSize) {
		upload_log('finalize failed: partial size mismatch, actual=' . filesize($partPath) . ', expected=' . $totalSize, true);
		upload_trace('partial size mismatch', array('actual' => filesize($partPath), 'expected' => $totalSize));
		upload_trace_status('error', array('message' => 'partial size mismatch', 'actual' => filesize($partPath), 'expected' => $totalSize));
		throw new RuntimeException('Partial uploaded file size mismatch.');
	}

	$sources = new Sources($db->_db);
	$galleryWorker = new Galleries($db->_db);
	$result = upload_finalize_gallery($db, $sources, $galleryWorker, $partPath, $fileName, $paysiteId, $title, $desc);

	if (is_file($metaPath)) {
		unlink($metaPath);
	}
	upload_log('finalize response success for GID#' . $result['gallery_id']);
	upload_trace('finalize response success', array('gallery_id' => $result['gallery_id']));

	upload_json_response(array(
		'success' => true,
		'message' => 'File is uploaded successfully.',
		'gallery_id' => $result['gallery_id'],
		'gallery_type' => $result['gallery_type']
	));
}

function upload_handle_classic($db)
{
	if (!isset($_FILES['upfile']['error']) || is_array($_FILES['upfile']['error'])) {
		throw new RuntimeException('Invalid parameters.');
	}

	switch ($_FILES['upfile']['error']) {
		case UPLOAD_ERR_OK:
			break;
		case UPLOAD_ERR_NO_FILE:
			throw new RuntimeException('No file sent.');
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			throw new RuntimeException('Exceeded filesize limit.');
		default:
			throw new RuntimeException('Unknown upload error.');
	}

	upload_ensure_dir(upload_chunks_dir());

	$tempPath = upload_chunks_dir() . '/classic-' . uniqid('', true) . '.part';
	if (!move_uploaded_file($_FILES['upfile']['tmp_name'], $tempPath)) {
		throw new RuntimeException('Failed to move uploaded file.');
	}
	upload_log("classic upload temp file created '" . $tempPath . "'");

	$sources = new Sources($db->_db);
	$galleryWorker = new Galleries($db->_db);
	$result = upload_finalize_gallery(
		$db,
		$sources,
		$galleryWorker,
		$tempPath,
		basename($_FILES['upfile']['name']),
		isset($_POST['paysite']) ? (int)$_POST['paysite'] : 0,
		isset($_POST['title']) ? $_POST['title'] : '',
		isset($_POST['desc']) ? $_POST['desc'] : ''
	);

	upload_json_response(array(
		'success' => true,
		'message' => 'File is uploaded successfully.',
		'gallery_id' => $result['gallery_id'],
		'gallery_type' => $result['gallery_type']
	));
}

try {
	$mode = isset($_POST['upload_mode']) ? $_POST['upload_mode'] : 'classic';

	switch ($mode) {
		case 'chunk':
			upload_handle_chunk();
			break;

		case 'finalize':
			upload_handle_finalize($db);
			break;

		case 'classic':
		default:
			upload_handle_classic($db);
			break;
	}
} catch (RuntimeException $e) {
	upload_log('runtime exception: ' . $e->getMessage(), true);
	upload_trace('runtime exception', array('message' => $e->getMessage()));
	upload_trace_status('error', array('message' => $e->getMessage()));
	upload_json_response(array('error' => $e->getMessage()), 400);
} catch (Exception $e) {
	upload_log('exception: ' . $e->getMessage(), true);
	upload_trace('exception', array('message' => $e->getMessage()));
	upload_trace_status('error', array('message' => $e->getMessage()));
	upload_json_response(array('error' => $e->getMessage()), 500);
}
