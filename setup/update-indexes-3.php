<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();

   
$sql = [];

$sql[] = "DELETE FROM galleries_pix 
WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";

$sql[] = "DELETE FROM galleries_resized_to 
WHERE gal_id NOT IN (SELECT gal_id id_model FROM galleries);";

$sql[] = "DELETE FROM galleries_source_pics 
WHERE gal_id NOT IN (SELECT gal_id id_model FROM galleries);";

// $sql[] = 'ALTER TABLE galleries_pix ENGINE=InnoDB;';
// $sql[] = 'ALTER TABLE galleries_resized_to ENGINE=InnoDB;';
// $sql[] = 'ALTER TABLE galleries_source_pics ENGINE=InnoDB;';


// $sql[] = 'ALTER TABLE galleries_pix MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_pix ADD CONSTRAINT fk_galleries_pix_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

// $sql[] = 'ALTER TABLE galleries_resized_to MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_resized_to ADD CONSTRAINT fk_galleries_resized_to_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

// $sql[] = 'ALTER TABLE galleries_source_pics MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_source_pics ADD CONSTRAINT fk_galleries_source_pics_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';


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
