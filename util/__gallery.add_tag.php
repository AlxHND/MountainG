<?php
	header('Content-type: application/json');

	require_once ("_auth.php");
	require_once ("../classes/class.galleries.php");
	require_once ("../classes/class.tags.php");

	$user = $auth->requireTagAccessJson();

	if (isset($_POST['id'], $_POST['tag'])) {
		$gal_id = (int)$_POST['id'];
		$tag = (int)$_POST['tag'];
		$dont_remove_tag = isset($_POST['dont_remove_tag']);

		$gallery = new Galleries($db->_db);
		$tags_added = $gallery->insertTag($gal_id, $tag, $dont_remove_tag);
		if ($tags_added) {
			echo json_encode(array(
				'success' => $gal_id,
				'tags_added' => $tags_added
			));
			$user->galleryTagAdded($gal_id, $tag);
		} else {
			echo json_encode(array(
				'error' => 'Галера: ' . $gal_id . ', Тег: ' . $tag . ' update error'
			));
		}
	} else {
		echo json_encode(array(
			'error' => 'Wrong POST'
		));
	}
?>
