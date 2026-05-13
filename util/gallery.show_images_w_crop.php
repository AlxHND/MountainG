<?php
if (isset($_POST['galid']) && isset($_POST['galtype']) && intval($_POST['galid'])) {
	$galId = intval($_POST['galid']);
	if ($galId !== 0) {
		require_once("../config/config.php");
		require_once("../classes/Logger.php");
		require_once("../classes/class.db_access.php");
		require_once("../classes/class.galleries.php");
		if ($_POST['galtype'] == 'Movies') $thumbUrlPre = HOSTING . "/thumbs/m/200";
		else $thumbUrlPre = HOSTING . "/thumbs/p/150";
		$images = new Galleries($db->_db);
		$galImages = $images->getAllImagesWithCropInfo($galId);
		if ($galImages) {
			if (count($galImages) == 0) {
				echo "Изображения не найдены";
			} else {
				foreach ($galImages as $thumbId => $image) {
					if ($thumbId < 256000) {
						$folderId = (int)ceil($thumbId / 1000);
						$folder = "1/" . $folderId;
					} else {
						$mainFolder = (int)ceil($thumbId / 256000);
						$folderId = (int)ceil($thumbId / 1000);
						$folder = $mainFolder . "/" . $folderId;
					}
					$thumbURL = $thumbUrlPre . "/" . $folder . "/" . $thumbId . ".jpg?" . md5(time() . getmypid());
					if ($image['user_id'] !== NULL) $color = "#a8ffa8";
					else $color = "#FFFFFF";
?>
					<img style="border: 4px solid <?= $color ?>;" id="thumb_preview_<?= $thumbId ?>" src="<?= $thumbURL ?>">
<?php
				}
			}
		} else {
			echo "Ошибка! Проблема коннекта к базе";
		}
	} else {
		echo "Ошибка! Значение ID галлереи == 0";
	}
} else {
	echo "Ошибка! Проблема с получаемым ID галлереи.";
}

?>