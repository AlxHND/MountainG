<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();

   
$sql = [];

$sql[] = 'ALTER TABLE models_images ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE sites_models ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE additional_titles ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE galleries_original_ids ENGINE=InnoDB;';



$sql[] = 'ALTER TABLE models_images MODIFY COLUMN model_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE models_images ADD CONSTRAINT fk_models_images_model_id FOREIGN KEY (model_id) REFERENCES model(id_model) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE sites_models MODIFY COLUMN model_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE sites_models ADD CONSTRAINT fk_sites_models_model_id FOREIGN KEY (model_id) REFERENCES model(id_model) ON DELETE CASCADE';
$sql[] = 'ALTER TABLE sites_models DROP INDEX sort_name';

$sql[] = 'ALTER TABLE additional_titles MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE additional_titles ADD CONSTRAINT fk_additional_titles_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';

$sql[] = 'ALTER TABLE galleries_original_ids MODIFY COLUMN gal_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_original_ids ADD CONSTRAINT fk_galleries_original_ids_gal_id FOREIGN KEY (gal_id) REFERENCES galleries(gal_id) ON DELETE CASCADE';


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
