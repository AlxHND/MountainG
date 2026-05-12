<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();

   
$sql = [];

$sql[] = "DELETE FROM galleries_tags WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM galleries_changes_query WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";

// $sql[] = 'ALTER TABLE galleries_status_messages ENGINE=InnoDB;';
// $sql[] = 'ALTER TABLE galleries_tags ENGINE=InnoDB;';
// $sql[] = 'ALTER TABLE galleries_to_merge ENGINE=InnoDB;';
// $sql[] = 'ALTER TABLE grabber ENGINE=InnoDB;';
// $sql[] = 'ALTER TABLE galleries_changes_query ENGINE=InnoDB;';

	
$sql[] = 'ALTER TABLE galleries_status_messages DROP INDEX gal_id';
// $sql[] = 'ALTER TABLE galleries_status_messages MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_status_messages ADD CONSTRAINT fk_galleries_status_messages_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE galleries_tags DROP INDEX gal_id';
// $sql[] = 'ALTER TABLE galleries_tags MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_tags ADD CONSTRAINT fk_galleries_tags_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

// $sql[] = 'ALTER TABLE galleries_to_merge MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_to_merge ADD CONSTRAINT fk_galleries_to_merge_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE grabber DROP PRIMARY KEY';
// $sql[] = 'ALTER TABLE grabber MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE grabber ADD CONSTRAINT fk_grabber_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE galleries_changes_query DROP INDEX gal_id';
// $sql[] = 'ALTER TABLE galleries_changes_query MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_changes_query ADD CONSTRAINT fk_galleries_changes_query_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';


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
