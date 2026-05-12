<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();

   
$sql = [];

$sql[] = "DELETE FROM processing_galleries WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_manual_crop_history WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_manual_recropped WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_user_skeep_gallery WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_working_list WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_manual_crop_history WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_manual_recropped WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_user_skeep_gallery WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM scr_working_list WHERE gal_id NOT IN (SELECT gal_id FROM galleries);";


$sql[] = 'ALTER TABLE images_sources ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE main_query ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE processing_galleries ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE scr_gallery_manual_recrop ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE scr_manual_crop_history ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE scr_manual_model_crop_history ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE scr_manual_recropped ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE scr_user_skeep_gallery ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE scr_working_list ENGINE=InnoDB;';


$sql[] = 'ALTER TABLE images_sources DROP INDEX gal_id';
$sql[] = 'ALTER TABLE images_sources MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE images_sources ADD CONSTRAINT fk_images_sources_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE main_query DROP INDEX gal_id';
$sql[] = 'ALTER TABLE main_query MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE main_query ADD CONSTRAINT fk_main_query_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE processing_galleries DROP INDEX gal_id';
$sql[] = 'ALTER TABLE processing_galleries MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE processing_galleries ADD CONSTRAINT fk_processing_galleries_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_gallery_manual_recrop DROP INDEX gal_id';
$sql[] = 'ALTER TABLE scr_gallery_manual_recrop MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_gallery_manual_recrop ADD CONSTRAINT fk_scr_gallery_manual_recrop_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';


$sql[] = 'ALTER TABLE scr_manual_crop_history MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_manual_crop_history ADD CONSTRAINT fk_scr_manual_crop_history_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_manual_model_crop_history MODIFY COLUMN model_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_manual_model_crop_history ADD CONSTRAINT fk_scr_manual_model_crop_history_model_id FOREIGN KEY (model_id) REFERENCES model(id_model) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_manual_recropped MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_manual_recropped ADD CONSTRAINT fk_scr_manual_recropped_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_user_skeep_gallery DROP INDEX gal_id';
$sql[] = 'ALTER TABLE scr_user_skeep_gallery MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_user_skeep_gallery ADD CONSTRAINT fk_scr_user_skeep_gallery_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_working_list DROP INDEX gal_id';
$sql[] = 'ALTER TABLE scr_working_list MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_working_list ADD CONSTRAINT fk_scr_working_list_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';


$sql[] = 'ALTER TABLE scr_manual_crop_history MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_manual_crop_history ADD CONSTRAINT fk_scr_manual_crop_history_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_manual_model_crop_history MODIFY COLUMN model_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_manual_model_crop_history ADD CONSTRAINT fk_scr_manual_model_crop_history_model_id FOREIGN KEY (model_id) REFERENCES model(id_model) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_manual_recropped MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_manual_recropped ADD CONSTRAINT fk_scr_manual_recropped_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_user_skeep_gallery DROP INDEX gal_id';
$sql[] = 'ALTER TABLE scr_user_skeep_gallery MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_user_skeep_gallery ADD CONSTRAINT fk_scr_user_skeep_gallery_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE scr_working_list DROP INDEX gal_id';
$sql[] = 'ALTER TABLE scr_working_list MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE scr_working_list ADD CONSTRAINT fk_scr_working_list_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

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
