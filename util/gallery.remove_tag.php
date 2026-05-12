<?php
	
header('Content-type: application/json');

require_once ("_auth.php");
require_once ("../classes/class.galleries.php");

$user = $auth->requireTagAccessJson();

if ($user->allowedToTag()) {
	
	if (isset($_POST['id']) && isset($_POST['tag'])) {
		$galId = (int)$_POST['id'];
		$tag = (int)$_POST['tag'];

		$gallery = new Galleries($db->_db);

		if ($gallery->removeTag($galId, $tag)) {
			$string = array('success' => $galId, 'tag_id' => $tag, );
			$user->galleryTagRemoved($galId, $tag);
		} else {
			$string =array( 'error' => "Галера: {$galId}, Тег: {$tag} update error" );
		}

	} else {
			$string = array( 'error' => 'Wrong POST' );
	}
}

echo json_encode($string);
