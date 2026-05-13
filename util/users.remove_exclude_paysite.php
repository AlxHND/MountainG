<?php
header('Content-type: application/json');



require_once("../config/config.php");
require_once("../classes/Logger.php");
require_once("../classes/class.db_access.php");
require_once("../classes/class.tags.php");
require_once("../classes/class.galleries.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при удалении исключеного из выдачи юзера палтника. Нужны права администратора.');

if (isset($_POST['paysite_id']) && isset($_POST['user_id'])) {
	$user_id = $_POST['user_id'];
	$paysite_id = $_POST['paysite_id'];

	$paysite_removed = $userAuth->deleteExcludedPaysite($user_id, $paysite_id);
	if ($paysite_removed) {
		$string = json_encode(
			array(
				'success' => $user_id,
				'paysite_added' => $paysite_id
			)
		);
		// $userAuth->userAddedTag($userId, $gal_id, $tag);
	} else {
		$string = json_encode(
			array(
				'error' => 'Юзер: ' . $user_id . ', Платник: ' . $paysite_id . ' update error'
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


echo $string;
