<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Helpers\DB;

$db = DB::getInstance();

$sql = "CREATE TABLE IF NOT EXISTS `video_preview_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gal_id` int(10) unsigned NOT NULL,
  `preview_id` int(10) unsigned NOT NULL DEFAULT '0',
  `job_status` enum('new','processing','done','error') NOT NULL DEFAULT 'new',
  `callback_status` enum('none','pending','sent','partial','error') NOT NULL DEFAULT 'none',
  `preview_format` enum('mp4','webm') NOT NULL DEFAULT 'mp4',
  `requested_on` int(10) unsigned NOT NULL DEFAULT '0',
  `started_on` int(10) unsigned NOT NULL DEFAULT '0',
  `finished_on` int(10) unsigned NOT NULL DEFAULT '0',
  `worker_ip` varchar(45) NOT NULL DEFAULT '',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `error_message` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `gal_id` (`gal_id`),
  KEY `job_status` (`job_status`),
  KEY `requested_on` (`requested_on`),
  KEY `callback_status` (`callback_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

if ($db->exec($sql) === false) {
	print_r($db->errorInfo());
	exit(1);
}

echo "video_preview_jobs OK\n";

$sql = "CREATE TABLE IF NOT EXISTS `video_preview_job_callbacks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `gal_id` int(10) unsigned NOT NULL,
  `callback_url` varchar(255) NOT NULL,
  `callback_token` varchar(64) NOT NULL,
  `callback_status` enum('pending','sent','error') NOT NULL DEFAULT 'pending',
  `callback_attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `callback_last_on` int(10) unsigned NOT NULL DEFAULT '0',
  `callback_error` varchar(255) NOT NULL DEFAULT '',
  `added_on` int(10) unsigned NOT NULL DEFAULT '0',
  `notified_on` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `gal_id` (`gal_id`),
  KEY `callback_status` (`callback_status`),
  KEY `added_on` (`added_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

if ($db->exec($sql) === false) {
	print_r($db->errorInfo());
	exit(1);
}

echo "video_preview_job_callbacks OK\n";

$jobsCount = (int)$db->query("SELECT COUNT(*) FROM `video_preview_jobs`")->fetchColumn();
$callbacksCount = (int)$db->query("SELECT COUNT(*) FROM `video_preview_job_callbacks`")->fetchColumn();

echo "Rows in video_preview_jobs: " . $jobsCount . "\n";
echo "Rows in video_preview_job_callbacks: " . $callbacksCount . "\n";
