<?php
header('Content-type: application/json');
$string = json_encode(
	array(
		'error' => 'Could not add video to vCDN sync query'
	)
);

include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");
include("../classes/class.galleries.php");

if (isset($_POST['gal_id'])) {
	$gallery_worker = new Galleries($db->_db);
	$gal_id = $_POST['gal_id'];
	if (!$gallery_worker->isVideoCdnSynced($gal_id)) {
		if ($gallery_worker->insertVideosToCdnQuery($gal_id)) {
			$string = json_encode(
				array(
					'success' => 'added to sync query'
				)
			);
		}
	}
} else {
	$string = json_encode(
		array(
			'error' => 'Could not add video to vCDN sync query, gal_id was not set properly'
		)
	);
}



echo $string;
