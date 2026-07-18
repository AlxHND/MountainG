<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

$link = mysqli_connect(DBHOST, DBUSER, DBPW, DBNAME) or die("Сервер базы данных не доступен");

$queries = array(
	"CREATE TABLE IF NOT EXISTS `paysite_update_markers` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`paysite_id` int(10) unsigned NOT NULL,
		`marker_type` enum('latest','backfill') NOT NULL,
		`update_title` varchar(255) NOT NULL DEFAULT '',
		`update_page_url` varchar(255) NOT NULL DEFAULT '',
		`update_inner_date` datetime DEFAULT NULL,
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `paysite_marker_type` (`paysite_id`,`marker_type`),
		KEY `marker_type` (`marker_type`),
		KEY `update_inner_date` (`update_inner_date`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1",
);

foreach ($queries as $sql) {
	$res = mysqli_query($link, $sql);
	if ($res === false) {
		echo "Ошибка при выполнении запроса: " . mysqli_error($link) . "\n" . $sql . "\n";
	}
}

echo "Done.\n";
