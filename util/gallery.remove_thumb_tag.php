<?php
	header('Content-type: application/json');

	require_once ("_auth.php");
	require_once ("../classes/class.galleries.php");

	$user = $auth->requireTagAccessJson();

	if ($user->allowedToTag()) {
	
		if (isset($_POST['tag_id']) && isset($_POST['thumb_id']) && isset($_POST['global_gal_id'])) {
			$thumb_id = (int)$_POST['thumb_id'];
			$tag_id = (int)$_POST['tag_id'];
			$gal_id = intval($_POST['global_gal_id']);
			$gallery = new Galleries($db->_db);
			if ($gallery->removeThumbTag($gal_id, $thumb_id, $tag_id)) {
				$string = json_encode(
				array(
					'success' => $thumb_id,
					'tag_id' => $tag_id,
					'thumb_id' => $thumb_id,
					)
				);
				$user->userRemovedThumbTag($user->getId(), $thumb_id, $tag_id);
			} else {
				$string = json_encode(
				array(
					'error' => 'Тумба: ' .$thumb_id.', Тег: ' .$tag_id.'. Удаление тега с тумбы не прошло'
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
