<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';


use App\Helpers\DB;

$db = DB::getInstance();


    
// $sql_s = "SHOW CREATE TABLE galleries;";
// $res = mysqli_query($link, $sql_s);

// if ($res) {
//     $row = mysqli_fetch_assoc($res);
//     echo "<pre>" . $row['Create Table'] . "</pre>"; // Выводим структуру таблицы
// } else {
//     echo "Ошибка выполнения запроса: " . mysqli_error($link);
// }

//  die;
 
$sql = [];

$sql[] = "DELETE FROM model_names WHERE model_id NOT IN (SELECT id_model FROM model);";
$sql[] = "DELETE FROM galleries_models WHERE gallery_id NOT IN (SELECT gal_id FROM galleries);";
$sql[] = "DELETE FROM galleries_models WHERE model_id NOT IN (SELECT id_model FROM model);";

$sql[] = 'ALTER TABLE galleries ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE paysites MODIFY COLUMN last_update DATE NULL DEFAULT NULL;';

$sql[] = 'ALTER TABLE paysites ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE model ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE galleries_models ENGINE=InnoDB;';
$sql[] = 'ALTER TABLE model_names ENGINE=InnoDB;';

$sql[] = 'ALTER TABLE galleries MODIFY gal_id BIGINT UNSIGNED;';
$sql[] = 'ALTER TABLE galleries DROP INDEX gal_id;';

$sql[] = 'ALTER TABLE galleries MODIFY COLUMN gal_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY;';

$sql[] = 'ALTER TABLE model MODIFY COLUMN id_model BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;';

$sql[] = 'DELETE FROM model_names WHERE model_id NOT IN (SELECT id_model FROM model);';
$sql[] = 'ALTER TABLE model_names MODIFY COLUMN model_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE model_names DROP INDEX model_id;';
$sql[] = 'ALTER TABLE model_names ADD CONSTRAINT fk_model_model_id FOREIGN KEY (model_id) REFERENCES model(id_model) ON DELETE CASCADE';
$sql[] = 'ALTER TABLE `galleries` CHANGE `gal_paysite` `gal_paysite` int unsigned NULL DEFAULT NULL';
$sql[] = 'ALTER TABLE galleries ADD CONSTRAINT fk_galleries_paysite FOREIGN KEY (gal_paysite) REFERENCES paysites(paysite_id) ON DELETE SET NULL';

$sql[] = 'ALTER TABLE galleries_models MODIFY COLUMN model_id BIGINT UNSIGNED NOT NULL;';

$sql[] = 'ALTER TABLE galleries_models MODIFY COLUMN gallery_id BIGINT UNSIGNED NOT NULL;';

$sql[] = 'ALTER TABLE galleries_models MODIFY COLUMN id INT NOT NULL;';
$sql[] = 'ALTER TABLE galleries_models DROP PRIMARY KEY;';
$sql[] = 'ALTER TABLE galleries_models DROP COLUMN id;';

$sql[] = 'ALTER TABLE galleries_models MODIFY COLUMN gallery_id BIGINT UNSIGNED NOT NULL;';
$sql[] = 'ALTER TABLE galleries_models ADD PRIMARY KEY (gallery_id, model_id);';

$sql[] = 'DELETE FROM galleries_models WHERE gallery_id NOT IN (SELECT gal_id FROM galleries);';
$sql[] = 'ALTER TABLE galleries_models ADD CONSTRAINT fk_galleries_models_gallery FOREIGN KEY (gallery_id) REFERENCES galleries(gal_id) ON DELETE CASCADE, ADD CONSTRAINT fk_galleries_models_model FOREIGN KEY (model_id) REFERENCES model(id_model) ON DELETE CASCADE;';

$sql[] = 'ALTER TABLE galleries_models ADD INDEX idx_model_id (model_id);';



$db = DB::getInstance();

foreach($sql as $sql_q) {
		echo $sql . ": Fixing.";
		try {
			$db->exec($sql_q);
			echo " OK<br>";
		} catch(Exception $e) {
			echo "Ошибка: ". $e->getMessage() ."<br>";
		}
}
