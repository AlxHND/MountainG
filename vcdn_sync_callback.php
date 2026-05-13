<?php
$scriptDir = getcwd() . "/sderrwqalkjt1isAre";
include($scriptDir . "/config/config.php");
include($scriptDir . "/classes/Logger.php");
include($scriptDir . "/classes/class.galleries.php");
include($scriptDir . "/classes/class.db_access.php");

echo 1;


$u_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "127.0.0.1";

//file_size - возможно неверно скачивается, учесть этот момент

$u_gal_id = false;
$u_pw = false;

if (isset($_GET['vid'])) {
	$u_vid = explode("-", $_GET['vid']);
	if ($u_vid && count($u_vid) > 0) {
		$u_gal_id = (int)$u_vid[0];
		$u_pw = $u_vid[1];
	}
}

$PID_X = getmypid();

$log = new Logger($PID_X . ": SYNC CALLBACK... Start. (IP:" . $u_ip . "), '" . serialize($_GET) . "'", true);


$u_callback_status = (isset($_GET['status']) && preg_match("#^(success|fail)$#", $_GET['status'])) ? $_GET['status'] : false;
$u_callback_message = (isset($_GET['message'])) ? preg_replace("#[^a-z0-9.-]+#i", ' ', $_GET['message']) : false;

$log = new Logger($PID_X . ": SYNC CALLBACK... u_callback_status:" . $u_callback_status . ", u_callback_message:" . $u_callback_message, true);


if (!$u_gal_id || $u_gal_id < 0) {
	$log = new Logger("SYNC CALLBACK... 'vid' param is empty or less than. (IP:" . $u_ip . "), '" . serialize($_GET) . "'", true);
	exit;
}
if (!$u_pw) {
	$log = new Logger("SYNC CALLBACK... Password is empty. '" . serialize($_GET) . "', (IP:" . $u_ip . ")", true);
	exit;
}
if (!$u_callback_status) {
	$log = new Logger("SYNC CALLBACK... Wrong 'status' param result - not 'fail|success'. (IP:" . $u_ip . ")", true);
	exit;
}

$secret = VCDN_CALLBACK_SECRET;
$key = str_replace('==', '', base64_encode(md5($secret . $u_gal_id, 1)));
$key = str_replace('/', '-', $key);
$key = md5($key);

if ($key != $u_pw) {
	$log = new Logger("SYNC CALLBACK... Wrong Password. Key:'" . $key . "', from url:'" . $u_pw . "', '" . serialize($_GET) . "' (IP:" . $u_ip . ")", true);
	exit;
}

$log = new Logger($PID_X . ": SYNC CALLBACK... Key OK", true);

$gallery_worker = new Galleries($db->_db);

if ($u_callback_status == 'success') {
	$gallery_worker->updateCdnQueryStatus($u_gal_id, 'ok');
	$log = new Logger($PID_X . ": SYNC CALLBACK... Set ok for gid#" . $u_gal_id . "", true);
} else {
	$gallery_worker->updateCdnQueryStatus($u_gal_id, 'error'); // изменить как-то
}


$log = new Logger("SYNC CALLBACK... Pass OK. , Status: '" . $u_callback_status . "', message: '" . $u_callback_message . "' (IP:" . $u_ip . ")");

echo 2;
