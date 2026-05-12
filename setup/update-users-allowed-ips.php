<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Helpers\DB;

function storedIpToReadableIp($storedIp) {
	if ($storedIp === null || $storedIp === '' || (string)$storedIp === '0') {
		return false;
	}

	$ip = long2ip((int)$storedIp);

	if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		return false;
	}

	return $ip;
}

$db = DB::getInstance();

$sql = "CREATE TABLE IF NOT EXISTS `scr_users_allowed_ips` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `user_ip` bigint(20) NOT NULL,
  `added` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_ip_unique` (`user_id`,`user_ip`),
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`)
) DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1";

if ($db->exec($sql) === false) {
	print_r($db->errorInfo());
} else {
	echo "scr_users_allowed_ips OK\n";

	$backfillSql = "INSERT IGNORE INTO `scr_users_allowed_ips` (`user_id`, `user_ip`, `added`)
		SELECT `id`, `user_allowed_ip`,
		       CASE
		       	WHEN `user_added` > 0 THEN `user_added`
		       	ELSE UNIX_TIMESTAMP()
		       END
		FROM `scr_users_list`
		WHERE `user_allowed_ip` <> 0";

	$insertedRows = $db->exec($backfillSql);
	if ($insertedRows === false) {
		print_r($db->errorInfo());
	} else {
		echo "scr_users_allowed_ips backfill OK, inserted: ".$insertedRows."\n";

		$eligibleUsersCount = (int)$db->query("SELECT COUNT(*) FROM `scr_users_list` WHERE `user_allowed_ip` <> 0")->fetchColumn();
		$storedRowsCount = (int)$db->query("SELECT COUNT(*) FROM `scr_users_allowed_ips`")->fetchColumn();

		echo "Eligible users in source table: ".$eligibleUsersCount."\n";
		echo "Rows currently stored in scr_users_allowed_ips: ".$storedRowsCount."\n";

		$usersStmt = $db->query("SELECT `id`, `user_name`, `user_allowed_ip` FROM `scr_users_list` ORDER BY `id` ASC");
		$skippedZeroIps = array();
		$skippedInvalidIps = array();

		if ($usersStmt) {
			$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

			foreach ($users as $user) {
				$storedIp = $user['user_allowed_ip'];

				if ((string)$storedIp === '0' || $storedIp === null || $storedIp === '') {
					$skippedZeroIps[] = $user;
					continue;
				}

				$readableIp = storedIpToReadableIp($storedIp);
				if ($readableIp === false) {
					$user['decoded_ip'] = '';
					$skippedInvalidIps[] = $user;
				}
			}
		}

		echo "Skipped users with empty IP (0): ".count($skippedZeroIps)."\n";
		foreach ($skippedZeroIps as $user) {
			echo " - user #".$user['id']." ".$user['user_name']." has user_allowed_ip=0\n";
		}

		echo "Skipped users with invalid IP value: ".count($skippedInvalidIps)."\n";
		foreach ($skippedInvalidIps as $user) {
			echo " - user #".$user['id']." ".$user['user_name']." has invalid user_allowed_ip=".$user['user_allowed_ip']."\n";
		}
	}
}
