<?php
include("../config/config.php");
include("../classes/class.logger.php");
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

	if (isset($_POST['modelId'], $_POST['galId'])) {
		$gal_id = (int)$_POST['galId'];
		$model_id = (int)$_POST['modelId'];

		if ($gal_id > 0 && $model_id > 0) {

			$gallery = new Galleries($db->_db);

			if ($gallery->addModel($gal_id, $model_id)) {

				$status = $gallery->getStatus($gal_id);

				/*if ($status = 'OK') {
					$models->addModelToSitesByGallery($model_id, $gal_id);
					$cache_worker = new CacheRebuilder($db->_db);
					$cache_worker->server_updateGalleryModels($gal_id);
				} */
				// echo $models->getName();
				$user->galleryModelAdded($gal_id, $model_id);

				echo "OK";
			} else {
				echo "failed";
			}
		}
	}
} else {
	// not allowed
}
