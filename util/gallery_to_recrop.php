<?php
header('Content-type: application/json');
if (isset($_POST['galid']) && intval($_POST['galid'])) {
	$galId = intval($_POST['galid']);
	if ($galId !== 0) {
		require_once("../config/config.php");
		require_once("../classes/class.galleries.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		$recropGallery = new Galleries($db->_db);
		if ($recropGallery->galleryToRecrop($galId)) $output = json_encode(array('success' => $galId));
		else $output = json_encode(array('error' => "Ошибка отправки в рекроп для галеры #" . $galId));
	} else $output = json_encode(array('error' => "Ошибка! Значение ID галлереи == 0"));
} else $output = json_encode(array('error' => "Ошибка входящих данных"));
echo $output;
