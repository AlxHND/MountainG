<?php

header('Content-type: application/json');

require_once("../config/config.php");
require_once("../classes/class.logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.models.php");

require_once("_auth.php");
$user = $auth->requireTagAccessJson();

if ($user->allowedToTag()) {
	// var_dump($_POST);
	if (isset($_POST['model_id']) && isset($_POST['image_id']) && intval($_POST['model_id']) && intval($_POST['image_id']) && isset($_POST['thumb_layout']) && preg_match("#^(horiz|vertic)$#", $_POST['thumb_layout'])) {
		$model_id = (int)$_POST['model_id'];
		$image_id = (int)$_POST['image_id'];
		$layout = $_POST['thumb_layout'];
		$models = new CModels($db->_db);


		if ($models->switchModel($model_id)) {
			$updated = false;
			if ($layout == 'vertic') {
				$updated = $models->updateVerticImage($image_id);
				// var_dump($updated);
				$image = $models->getMainVerticImageUrl();
			} elseif ($layout == 'horiz') {
				$updated = $models->updateHorizImage($image_id);
				$image = $models->getMainHorizImageUrl();
			}


			if (isset($_POST['thumb_size']) && preg_match("#^(big|medium|small)$#", $_POST['thumb_size'])) $thumb_size = $_POST['thumb_size'];
			else $thumb_size = 'small';

			if ($updated && $image) {
				$string = json_encode(
					array(
						'success' => $model_id,
						'image' => $image['url']
					)
				);
			} else {
				$string = json_encode(
					array(
						'error' => 'Главное изображение моджели не проапдейчено, или невозможно выбрать главное изображение: ' . $model_id
					)
				);
			}
		} else {
			$string = json_encode(
				array(
					'error' => 'Невозможно переключить модель: ' . $model_id
				)
			);
		}
	} else {
		$string = json_encode(
			array(
				'error' => 'Wrong POST'
			)
		);
	}
} else {
	$string = "Ошибка аутентификации при добавлении тега. Пользователь " . $user_name . "не имеет прав на добавление тегов";
	$log = new Logger($string . ",ip:" . $user_ip, true);
	$string = json_encode(
		array(
			'error' => $string
		)
	);
}

echo $string;
