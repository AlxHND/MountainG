<?php
include("../config/config.php");
include("../classes/Logger.php");
include("../classes/class.db_access.php");
include("../classes/class.models.php");
include("../classes/class.sources.php");
include("../classes/class.tags.php");
include("../classes/class.galleries.php");
include("../classes/class.sites.php");
include("../classes/class.new-cache.php");
include("../lib/functions.php");

require_once("_auth.php");
$user = $auth->requireTagAccessJson();

if ($user->allowedToModel()) {
	$galId = (int)$_POST['galId'];
	$modelId = (int)$_POST['modelId'];

	if ($galId > 0 && $modelId > 0) {

		$gallery = new Galleries($db->_db);

		if ($gallery->removeModel($galId, $modelId)) {
			echo "OK";
			$user->galleryModelRemoved($galId, $modelId);

			// $status = $gallery->getStatus($galId);
			// if ($status = 'OK') {
			// 	$cache_worker = new CacheRebuilder($db->_db);
			// 	$cache_worker->server_updateGalleryModels($galId);
			// }
		} else {
			echo "failed";
		}
	}
} else {
	// not allowed
}
