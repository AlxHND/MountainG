<?php

$scriptDir = __DIR__;
if (!is_file($scriptDir . "/autoload.php")) {
	$scriptDir = getcwd() . "/sderrwqalkjt1isAre";
}

if (!defined('RSS_FEEDER_PASSWORD')) {
	define('RSS_FEEDER_PASSWORD', 'gfdE4wqkjHgdo');
}

if (!defined('RSS_FEEDER_CONTENT_URL')) {
	define('RSS_FEEDER_CONTENT_URL', 'xrhost.com/uploadedimages');
}

require_once($scriptDir . "/autoload.php");

\App\Services\RssFeederService::run($scriptDir);
