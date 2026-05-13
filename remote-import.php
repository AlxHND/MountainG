<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(array(
		'success' => false,
		'error' => 'Method Not Allowed',
	), JSON_UNESCAPED_UNICODE);
	exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/class.db_access.php';
require_once __DIR__ . '/classes/class.images.php';
require_once __DIR__ . '/classes/class.models.php';
require_once __DIR__ . '/classes/class.sources.php';
require_once __DIR__ . '/classes/class.galleries.php';
require_once __DIR__ . '/lib/functions.php';

function remoteImportGetRequestIp(): string
{
	return isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
}

function remoteImportAllowedIps(): array
{
	$ips = array('127.0.0.1');

	if (defined('ALWAYS_ALLOWED_IP') && ALWAYS_ALLOWED_IP) {
		$ips[] = ALWAYS_ALLOWED_IP;
	}

	if (defined('REMOTE_IMPORT_ALLOWED_IPS') && REMOTE_IMPORT_ALLOWED_IPS) {
		$extraIps = explode(',', REMOTE_IMPORT_ALLOWED_IPS);
		foreach ($extraIps as $ip) {
			$ip = trim($ip);
			if ($ip !== '') {
				$ips[] = $ip;
			}
		}
	}

	return array_values(array_unique($ips));
}

function remoteImportGetSecret(): string
{
	if (defined('REMOTE_IMPORT_TOKEN') && REMOTE_IMPORT_TOKEN) {
		return (string)REMOTE_IMPORT_TOKEN;
	}

	if (defined('VCDN_CALLBACK_SECRET') && VCDN_CALLBACK_SECRET) {
		return (string)VCDN_CALLBACK_SECRET;
	}

	return '';
}

function remoteImportReadPayload(): array
{
	$payload = array();

	if (isset($_POST['payload'])) {
		$payloadRaw = (string)$_POST['payload'];
		$decoded = json_decode($payloadRaw, true);
		if (is_array($decoded)) {
			$payload = $decoded;
		}
	}

	if (!$payload) {
		$rawBody = file_get_contents('php://input');
		if ($rawBody !== false && trim($rawBody) !== '') {
			$decoded = json_decode($rawBody, true);
			if (is_array($decoded)) {
				$payload = $decoded;
			}
		}
	}

	if (!$payload && !empty($_POST)) {
		$payload = $_POST;
	}

	return $payload;
}

function remoteImportResolvePaysite(PDO $db, string $slug): ?array
{
	$slug = trim(strtolower($slug));
	if ($slug === '') {
		return null;
	}

	$sql = "SELECT paysite_id AS id, paysite_name AS name, paysite_niche AS niche, paysite_folder AS folder
			FROM paysites
			WHERE LOWER(paysite_folder) = :slug OR LOWER(paysite_name) = :slug
			LIMIT 1";
	$stmt = $db->prepare($sql);
	$stmt->execute(array('slug' => $slug));
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	return $row ?: null;
}

function remoteImportResolveSiteId(PDO $db, $siteInput): int
{
	if (is_int($siteInput) || ctype_digit((string)$siteInput)) {
		$siteId = (int)$siteInput;
		if ($siteId < 1) {
			return 0;
		}

		$stmt = $db->prepare("SELECT site_id FROM sites WHERE site_id = :site_id LIMIT 1");
		$stmt->execute(array('site_id' => $siteId));

		return $stmt->fetchColumn() ? $siteId : 0;
	}

	$siteName = trim(strtolower((string)$siteInput));
	if ($siteName === '') {
		return 0;
	}

	$stmt = $db->prepare("SELECT site_id FROM sites WHERE LOWER(site_name) = :site_name LIMIT 1");
	$stmt->execute(array('site_name' => $siteName));
	$resolvedId = $stmt->fetchColumn();

	return $resolvedId ? (int)$resolvedId : 0;
}

function remoteImportNormalizeNiche($niche): string
{
	$niche = trim(strtolower((string)$niche));
	$map = array(
		'gay' => 'Gay',
		'straight' => 'Straight',
		'shemale' => 'Shemale',
	);

	return isset($map[$niche]) ? $map[$niche] : '';
}

$requestIp = remoteImportGetRequestIp();
$allowedIps = remoteImportAllowedIps();
$token = '';

if (isset($_SERVER['HTTP_X_IMPORT_TOKEN'])) {
	$token = trim((string)$_SERVER['HTTP_X_IMPORT_TOKEN']);
} elseif (isset($_POST['access_token'])) {
	$token = trim((string)$_POST['access_token']);
}

$payload = remoteImportReadPayload();
if ($token === '' && isset($payload['access_token'])) {
	$token = trim((string)$payload['access_token']);
}

$secret = remoteImportGetSecret();
$isAllowedIp = in_array($requestIp, $allowedIps, true);
$tokenOk = ($secret !== '' && $token !== '') ? hash_equals($secret, $token) : false;

if (!$isAllowedIp || !$tokenOk) {
	http_response_code(403);
	echo json_encode(array(
		'success' => false,
		'error' => 'Access denied',
		'meta' => array(
			'ip_allowed' => $isAllowedIp,
			'token_valid' => $tokenOk,
		),
	), JSON_UNESCAPED_UNICODE);
	exit;
}

$rootPaysiteSlug = isset($payload['paysite']) ? (string)$payload['paysite'] : (isset($payload['paysite_slug']) ? (string)$payload['paysite_slug'] : '');
$rootSiteInput = isset($payload['site']) ? $payload['site'] : (isset($payload['site_slug']) ? $payload['site_slug'] : (isset($payload['unique_for_export_site']) ? $payload['unique_for_export_site'] : 0));

$items = array();
if (isset($payload['items']) && is_array($payload['items']) && count($payload['items']) > 0) {
	$items = $payload['items'];
} else {
	$items[] = array(
		'url' => isset($payload['url']) ? $payload['url'] : '',
		'title' => isset($payload['title']) ? $payload['title'] : '',
		'description' => isset($payload['description']) ? $payload['description'] : '',
		'paysite' => $rootPaysiteSlug,
		'site' => $rootSiteInput,
		'tags' => isset($payload['tags']) ? $payload['tags'] : array(),
		'models' => isset($payload['models']) ? $payload['models'] : array(),
		'gallery_original_id' => isset($payload['gallery_original_id']) ? (int)$payload['gallery_original_id'] : 0,
	);
}

if (count($items) === 0) {
	http_response_code(422);
	echo json_encode(array(
		'success' => false,
		'error' => 'Payload has no import items',
	), JSON_UNESCAPED_UNICODE);
	exit;
}

$remoteDb = $db->_db;
$galleryWorker = new Galleries($remoteDb);
$sourcesWorker = new Sources($remoteDb);

$added = array();
$failed = array();
$updatedPaysiteIds = array();

foreach ($items as $idx => $item) {
	$url = isset($item['url']) ? trim((string)$item['url']) : '';
	$title = isset($item['title']) ? trim((string)$item['title']) : '';
	$description = isset($item['description']) ? (string)$item['description'] : '';
	$itemPaysiteSlug = isset($item['paysite']) ? (string)$item['paysite'] : (isset($item['paysite_slug']) ? (string)$item['paysite_slug'] : $rootPaysiteSlug);
	$itemSiteInput = isset($item['site']) ? $item['site'] : (isset($item['site_slug']) ? $item['site_slug'] : $rootSiteInput);
	$galleryOriginalId = isset($item['gallery_original_id']) ? (int)$item['gallery_original_id'] : 0;
	$tags = isset($item['tags']) && is_array($item['tags']) ? $item['tags'] : false;
	$models = isset($item['models']) && is_array($item['models']) ? $item['models'] : array();

	if ($url === '') {
		$failed[] = array(
			'item' => $idx,
			'url' => '',
			'error' => 'Empty url',
		);
		continue;
	}

	$paysite = remoteImportResolvePaysite($remoteDb, $itemPaysiteSlug);
	if (!$paysite) {
		$failed[] = array(
			'item' => $idx,
			'url' => $url,
			'error' => "Paysite not found: '{$itemPaysiteSlug}'",
		);
		continue;
	}

	$niche = remoteImportNormalizeNiche(isset($paysite['niche']) ? $paysite['niche'] : '');
	if ($niche === '') {
		$failed[] = array(
			'item' => $idx,
			'url' => $url,
			'error' => "Invalid paysite niche for paysite #" . (int)$paysite['id'],
		);
		continue;
	}

	$resolvedSiteId = remoteImportResolveSiteId($remoteDb, $itemSiteInput);
	if (!$title) {
		$title = $url;
	}

	$newGalleryId = $galleryWorker->addGallery(
		$url,
		(int)$paysite['id'],
		$niche,
		'New',
		'new',
		$title,
		$description,
		false,
		0,
		$models,
		false,
		false,
		false,
		$galleryOriginalId,
		$tags,
		$resolvedSiteId
	);

	if ($newGalleryId) {
		$updatedPaysiteIds[(int)$paysite['id']] = (int)$paysite['id'];
		$added[] = array(
			'item' => $idx,
			'url' => $url,
			'paysite_slug' => $itemPaysiteSlug,
			'paysite_id' => (int)$paysite['id'],
			'site_input' => $itemSiteInput,
			'site_id' => $resolvedSiteId,
			'new_gallery_id' => (int)$newGalleryId,
		);
	} else {
		$failed[] = array(
			'item' => $idx,
			'url' => $url,
			'error' => $galleryWorker->getInsertError() ?: 'Insert failed',
		);
	}
}

foreach ($updatedPaysiteIds as $paysiteId) {
	$sourcesWorker->paysiteUpdated((int)$paysiteId);
}

echo json_encode(array(
	'success' => count($added) > 0,
	'added_count' => count($added),
	'failed_count' => count($failed),
	'added' => $added,
	'failed' => $failed,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
