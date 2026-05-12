<?php
	header('Content-type: application/json');

	require_once ("_auth.php");
	require_once ("../classes/class.galleries.php");

	$user = $auth->requireTagAccessJson();

	if ($user->allowedToTag()) {
	
		if (isset($_POST['thumb_id']) && isset($_POST['gal_id'])) {
			$thumb_id = (int)$_POST['thumb_id'];
			$gal_id = (int)$_POST['gal_id'];
			$gallery = new Galleries($db->_db);
			if ($gallery->deleteGalleryImage($gal_id, $thumb_id)) {
				$string = json_encode(
				array(
					'success' => $thumb_id					)
				);
				$user->userRemovedThumb($user->getId(), $gal_id, $thumb_id);
			} else {
				$string = json_encode(
				array(
					'error' => 'Тумба: ' .$thumb_id.'. Удаление тумбы не прошло'
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
	}

  echo $string;
?>
