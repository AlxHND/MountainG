<?php
	header('Content-type: application/json');

	require_once ("_auth.php");
	require_once ("../classes/class.galleries.php");

	$user = $auth->requireTagAccessJson();

	if ($user->allowedToTag()) {
	
		if (isset($_POST['thumb_id']) && isset($_POST['gal_id'])) {
			$thumb_id = (int)$_POST['thumb_id'];
			$gal_id = intval($_POST['gal_id']);
			$gallery = new Galleries($db->_db);
			if ($gallery->setGalThumb($gal_id, $thumb_id)) {
				$string = json_encode(
				array(
					'success' => $gal_id,
					'thumb_id' => $thumb_id,
					)
				);
				// $userAuth->userAddedThumbTag($userId, $thumb_id, $tag_id);
			} else {
				$string = json_encode(
				array(
					'error' => 'Тумба: ' .$thumb_id.', Галера: ' .$gal_id.' установка главной тумбы не прошло'
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
