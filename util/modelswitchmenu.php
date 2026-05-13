<?php
require_once("../config/config.php");
require_once(LIB_DIR . "/classes/Logger.php");
require_once(LIB_DIR . "/classes/class.db_access.php");
require_once(LIB_DIR . "/classes/class.models.php");
require_once(LIB_DIR . "/lib/functions.php");

$thumbUrlPre = HOSTING . "/thumbs/p/240";
$models = new CModels($db->_db);

$models->switchModel($_POST['modelId']);

$thumbId = $models->getPicture();
if ($thumbId == 0 || $thumbId == false) echo "false";
else {
	if ($thumbId < 256000) {
		$folderId = (int)ceil($thumbId / 1000);
		$folder = "1/" . $folderId;
	} else {
		$mainFolder = (int)ceil($thumbId / 256000);
		$folderId = (int)ceil($thumbId / 1000);
		$folder = $mainFolder . "/" . $folderId;
	}
	$thumbURL = $thumbUrlPre . "/" . $folder . "/" . $thumbId . ".jpg";
	echo $thumbURL;
}
