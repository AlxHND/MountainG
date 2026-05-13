<?php
header('Content-type: application/json');

require_once("../config/config.php");
require_once("../classes/Logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.galleries.php");


require_once("_auth.php");
$user = $auth->requireTagAccessJson();

if ($user->allowedToTag()) {

	if (isset($_POST['gal_id'])) {
		$gal_id = (int)$_POST['gal_id'];
		$gallery = new Galleries($db->_db);
		if ($gallery->deleteGallery($gal_id)) {
			$string = json_encode(
				array(
					'success' => $gal_id
				)
			);
			$userAuth->userRemovedGallery($userId, $gal_id);
		} else {
			$string = json_encode(
				array(
					'error' => 'Галера: ' . $gal_id . '. Ошибка удаления!'
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
	$string = "Ошибка аутентификации при удалении галеры. Пользователь " . $user_name . "не имеет прав на изменения";
	$log = new Logger($string . ",ip:" . $user_ip, true);
	$string = json_encode(
		array(
			'error' => $string
		)
	);
}

echo $string;
