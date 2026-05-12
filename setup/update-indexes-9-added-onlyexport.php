<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();

   
$sql = [];

$sql[] = 'ALTER TABLE sites ADD `only_export_site` TINYINT NOT NULL DEFAULT 0 AFTER `local_id_flag` ';
$sql[] = 'ALTER TABLE galleries ADD `unique_for_export_site` SMALLINT UNSIGNED NOT NULL DEFAULT 0';
$sql[] = 'ALTER TABLE galleries ADD INDEX idx_unique_for_export_site (unique_for_export_site);';

$db = DB::getInstance();

foreach($sql as $sql_q) {
		echo $sql_q . ": Fixing.";
		try {
			$db->exec($sql_q);
			echo " OK<br>";
		} catch(Exception $e) {
			echo "Ошибка: ". $e->getMessage() ."<br>";
		}
}
