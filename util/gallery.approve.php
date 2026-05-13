<?php
header('Content-type: application/json');


include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");


require_once("_auth.php");
$user = $auth->requireTagAccessJson();

if ($user->allowedToTag()) {

	if (isset($_POST['gal_id'])) {
		$gal_id = (int)$_POST['gal_id'];
		$gallery = new Galleries($db->_db);
		if ($gallery->approveGallery($gal_id)) {
			$string = json_encode(
				array(
					'success' => $gal_id
				)
			);
			$user->galleryApproved($gal_id);
		} else {
			$string = json_encode(
				array(
					'error' => 'Галера: ' . $gal_id . '. Ошибка аппрува галеры!'
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
	$string = "Ошибка аутентификации при аппруве галеры";
	$log = new Logger($string, true);
	$string = json_encode(
		array(
			'error' => $string
		)
	);
}
echo $string;
