<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();

   
$sql = [];
$sql[] = 'ALTER TABLE galleries DROP INDEX gal_description';
$sql[] = 'ALTER TABLE galleries MODIFY COLUMN gal_description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;';
$sql[] = 'ALTER TABLE galleries ADD FULLTEXT (gal_description);';

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
