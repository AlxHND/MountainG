<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Helpers\DB;

$db = DB::getInstance();

$sql = "CREATE TABLE IF NOT EXISTS `galleries_video_previews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gal_id` int(10) unsigned NOT NULL,
  `preview_status` enum('new','queued','processing','ok','error','delete') NOT NULL DEFAULT 'new',
  `preview_format` enum('mp4','webm') NOT NULL DEFAULT 'mp4',
  `source_video_size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `preview_size` bigint(20) unsigned NOT NULL DEFAULT '0',
  `preview_width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `preview_height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `preview_duration_ms` int(10) unsigned NOT NULL DEFAULT '0',
  `preview_bitrate` int(10) unsigned NOT NULL DEFAULT '0',
  `clip_count` tinyint(3) unsigned NOT NULL DEFAULT '10',
  `clip_length_ms` smallint(5) unsigned NOT NULL DEFAULT '1000',
  `start_offset` smallint(5) unsigned NOT NULL DEFAULT '5',
  `end_offset` smallint(5) unsigned NOT NULL DEFAULT '5',
  `generated_on` int(10) unsigned NOT NULL DEFAULT '0',
  `updated_on` int(10) unsigned NOT NULL DEFAULT '0',
  `error_message` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `gal_id` (`gal_id`),
  KEY `preview_status` (`preview_status`),
  KEY `updated_on` (`updated_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

if ($db->exec($sql) === false) {
	print_r($db->errorInfo());
	exit(1);
}

echo "galleries_video_previews OK\n";

$backfillSql = "INSERT IGNORE INTO `galleries_video_previews`
	(`gal_id`, `preview_status`, `preview_format`, `source_video_size`, `preview_size`, `preview_width`,
	 `preview_height`, `preview_duration_ms`, `preview_bitrate`, `clip_count`, `clip_length_ms`,
	 `start_offset`, `end_offset`, `generated_on`, `updated_on`, `error_message`)
	SELECT `gal_id`, 'new', 'mp4', `video_size`, 0, 0, 0, 0, 0, 10, 1000, 5, 5, 0, UNIX_TIMESTAMP(), ''
	FROM `galleries_videos`
	WHERE `video_status` = 'ok'";

$insertedRows = $db->exec($backfillSql);
if ($insertedRows === false) {
	print_r($db->errorInfo());
	exit(1);
}

echo "galleries_video_previews backfill OK, inserted: ".$insertedRows."\n";

$totalVideoRows = (int)$db->query("SELECT COUNT(*) FROM `galleries_videos` WHERE `video_status` = 'ok'")->fetchColumn();
$totalPreviewRows = (int)$db->query("SELECT COUNT(*) FROM `galleries_video_previews`")->fetchColumn();
$statusRows = $db->query("SELECT `preview_status`, COUNT(*) AS cnt FROM `galleries_video_previews` GROUP BY `preview_status` ORDER BY `preview_status` ASC");

echo "Eligible videos in galleries_videos: ".$totalVideoRows."\n";
echo "Rows currently stored in galleries_video_previews: ".$totalPreviewRows."\n";

if ($statusRows) {
	echo "Preview status breakdown:\n";
	foreach ($statusRows->fetchAll(PDO::FETCH_ASSOC) as $row) {
		echo " - ".$row['preview_status'].": ".$row['cnt']."\n";
	}
}
