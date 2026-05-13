<?php
header('Content-type: application/json');
if (isset($_POST['galid']) && intval($_POST['galid'])) {
	$galId = intval($_POST['galid']);
	if ($galId !== 0) {
		require_once("../config/config.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		require_once("../classes/class.galleries.php");

		$gallery = new Galleries($db->_db);
		$gallery_titles = $gallery->getAllAdditionTitles($galId);
		if ($gallery_titles) {
			array(
				'success' => 'true',
				'data' => $gallery_titles
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
