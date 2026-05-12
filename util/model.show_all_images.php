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
	if (isset($_POST['model_id']) && isset($_POST['thumb_layout']) && preg_match("#^(horiz|vertic)$#", $_POST['thumb_layout'])) {
		$model_id = (int)$_POST['model_id'];
		$models = new CModels($db->_db);

		$layout = $_POST['thumb_layout'];


		if ($images = $models->allImages($model_id, $layout)) {

			if (isset($_POST['thumb_size']) && preg_match("#^(big|medium|small)$#", $_POST['thumb_size'])) $thumb_size = $_POST['thumb_size'];
			else $thumb_size = 'small';


			$string = json_encode(
				array(
					'success' => $model_id,
					'images' => $images[$thumb_size]
				)
			);
		} else {
			$string = json_encode(
				array(
					'error' => 'Нет изображений для модели: ' . $model_id
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
