<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

$link = mysqli_connect(DBHOST, DBUSER, DBPW, DBNAME) or die("Сервер базы данных не доступен");

$queries = array(
	"ALTER TABLE `galleries_delete_rss` ADD COLUMN `gal_type` varchar(16) NOT NULL DEFAULT '' AFTER `gal_local_id`",
	"ALTER TABLE `galleries_delete_rss` ADD KEY `gal_type` (`gal_type`)",
	"UPDATE galleries_delete_rss
		LEFT JOIN galleries ON galleries.gal_id = galleries_delete_rss.gal_id
		SET galleries_delete_rss.gal_type = IFNULL(galleries.gal_type, galleries_delete_rss.gal_type)
		WHERE galleries_delete_rss.gal_type = ''",
	"UPDATE galleries_delete_rss
		SET gal_type = CASE
			WHEN LOWER(gal_type) = 'movies' THEN 'Movies'
			WHEN LOWER(gal_type) = 'pics' THEN 'Pics'
			WHEN LOWER(gal_type) = 'gif' THEN 'gif'
			WHEN LOWER(gal_type) = 'embed' THEN 'embed'
			ELSE gal_type
		END",
	"UPDATE galleries_delete_rss
		SET gal_type = 'Movies'
		WHERE gal_type = ''
		AND LOWER(gal_url) LIKE '%/movies/%'",
	"UPDATE galleries_delete_rss
		SET gal_type = 'Pics'
		WHERE gal_type = ''
		AND LOWER(gal_url) LIKE '%/pics/%'",
	"UPDATE galleries_delete_rss
		SET gal_type = 'gif'
		WHERE gal_type = ''
		AND LOWER(gal_url) LIKE '%/gif/%'",
	"UPDATE galleries_delete_rss
		SET gal_type = 'embed'
		WHERE gal_type = ''
		AND LOWER(gal_url) LIKE '%/embed/%'",
);

foreach ($queries as $sql) {
	$res = mysqli_query($link, $sql);
	if ($res === false) {
		$error = mysqli_error($link);
		if (
			stripos($error, 'Duplicate column name') === false
			&& stripos($error, 'Duplicate key name') === false
		) {
			echo "Ошибка при выполнении запроса: " . $error . "\n" . $sql . "\n";
		}
	}
}

$result = mysqli_query(
	$link,
		"SELECT
			COUNT(*) AS total_rows,
			SUM(CASE WHEN LOWER(gal_type) = 'movies' THEN 1 ELSE 0 END) AS movies_rows,
			SUM(CASE WHEN LOWER(gal_type) <> 'movies' AND gal_type <> '' THEN 1 ELSE 0 END) AS gallery_rows,
			SUM(CASE WHEN gal_type = '' THEN 1 ELSE 0 END) AS unknown_rows
		FROM galleries_delete_rss"
);

if ($result && ($row = mysqli_fetch_assoc($result))) {
	echo "Done.\n";
	echo "Rows total: " . (int)$row['total_rows'] . "\n";
	echo "Rows video: " . (int)$row['movies_rows'] . "\n";
	echo "Rows gallery: " . (int)$row['gallery_rows'] . "\n";
	echo "Rows unknown: " . (int)$row['unknown_rows'] . "\n";
}
