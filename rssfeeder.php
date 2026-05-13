<?php

$scriptDir = __DIR__;
if (!is_file($scriptDir . "/autoload.php")) {
	$scriptDir = getcwd() . "/sderrwqalkjt1isAre";
}

require_once($scriptDir . "/autoload.php");

\App\Services\RssFeederService::run($scriptDir);
