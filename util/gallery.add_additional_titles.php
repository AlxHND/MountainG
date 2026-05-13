<?php
header('Content-type: application/json');
if (isset($_POST['gal_id']) && intval($_POST['gal_id'])) {
	$gal_id = intval($_POST['gal_id']);
	$title = $_POST['title'];

	if (isset($_POST['language']) && $_POST['language']) {
		$language = $_POST['language'];
	} else {
		$language = false;
	}
	if ($gal_id !== 0) {
		require_once("../config/config.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		require_once("../classes/class.galleries.php");


		$gallery = new Galleries($db->_db);
		// var_dump($language);
		$new_title_id = $gallery->insertAdditionalTitle($gal_id, $title, $language);
		if ($new_title_id) {
			$string = json_encode(
				array(
					'success' => 'true',
					'data' => $new_title_id
				)
			);
		} else {
			$string = json_encode(
				array(
					'error' => 'Ошибка! Проблема коннекта к базе'
				)
			);
		}
	} else {
		$string = json_encode(
			array(
				'error' => 'Ошибка! Значение ID галлереи == 0'
			)
		);
	}
} else {
	$string = json_encode(
		array(
			'error' => 'Ошибка! Проблема с получаемым ID галлереи.'
		)
	);
}
echo $string;
