<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

$link = mysqli_connect(DBHOST, DBUSER, DBPW, DBNAME) or die("Сервер базы данных не доступен");

$queries = array(
	"CREATE TABLE IF NOT EXISTS `affiliate_programs` (
		`affiliate_program_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`affiliate_program_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		`affiliate_program_url` varchar(255) DEFAULT NULL,
		`affiliate_program_description` varchar(512) NOT NULL DEFAULT '',
		`created_at` datetime NOT NULL,
		`updated_at` datetime NOT NULL,
		PRIMARY KEY (`affiliate_program_id`),
		UNIQUE KEY `affiliate_program_name` (`affiliate_program_name`),
		KEY `affiliate_program_url` (`affiliate_program_url`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1",
	"ALTER TABLE `paysites` ADD COLUMN `affiliate_program_id` int(10) unsigned DEFAULT NULL AFTER `paysite_affiliate`",
	"ALTER TABLE `paysites` ADD KEY `affiliate_program_id` (`affiliate_program_id`)",
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

echo "Done.\n";
