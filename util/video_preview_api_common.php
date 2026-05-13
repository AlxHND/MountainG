<?php

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../classes/Logger.php";
require_once __DIR__ . "/../classes/class.db_access.php";
require_once __DIR__ . "/../classes/class.galleries.php";

function video_preview_api_json(array $payload)
{
	header('Content-type: application/json');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE);
	exit;
}

function video_preview_api_client_ip()
{
	return isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
}

function video_preview_api_allowed_ips($value)
{
	$result = array();
	$list = preg_split('#[\s,;|]+#', (string)$value);
	foreach ($list as $item) {
		$item = trim($item);
		if ($item !== '') {
			$result[] = $item;
		}
	}
	return array_values(array_unique($result));
}

function video_preview_api_ip_allowed($ip, $allowedValue)
{
	$ip = trim((string)$ip);
	if ($ip === '') {
		return false;
	}

	$allowed = video_preview_api_allowed_ips($allowedValue);
	if (defined('ALWAYS_ALLOWED_IP') && ALWAYS_ALLOWED_IP) {
		$allowed[] = ALWAYS_ALLOWED_IP;
	}

	$allowed = array_values(array_unique(array_filter($allowed)));
	return in_array($ip, $allowed, true);
}

function video_preview_api_require_ip($type = 'request')
{
	$ip = video_preview_api_client_ip();
	$configValue = ($type === 'worker' && defined('VIDEO_PREVIEW_WORKER_ALLOWED_IPS'))
		? VIDEO_PREVIEW_WORKER_ALLOWED_IPS
		: (defined('VIDEO_PREVIEW_API_ALLOWED_IPS') ? VIDEO_PREVIEW_API_ALLOWED_IPS : '');

	if (!video_preview_api_ip_allowed($ip, $configValue)) {
		new Logger(__FUNCTION__ . ": denied " . $type . " IP '" . $ip . "'", true);
		video_preview_api_json(array('error' => 'Access denied', 'ip' => $ip));
	}

	return $ip;
}

function video_preview_api_validate_callback_url($url)
{
	$url = trim((string)$url);
	if ($url === '') {
		return '';
	}

	if (strlen($url) > 255) {
		return false;
	}

	if (!preg_match('#^https?://#i', $url)) {
		return false;
	}

	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return false;
	}

	return $url;
}
