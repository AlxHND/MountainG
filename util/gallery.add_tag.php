<?php
function folderNameById ($imageId) {
    $imageId = (int)$imageId;

    if ($imageId != 0) {
      if ($imageId < 256000){
        $folderId = (int)ceil($imageId/1000);
        $folder = "1/".$folderId;
      } else {
        $mainFolder= (int)ceil($imageId/256000);
        $folderId = (int)ceil($imageId/1000); 
        $folder = $mainFolder."/".$folderId;
      }
    }

    return $folder;
}

header('Content-type: application/json');

require_once ("_auth.php");
require_once ("../classes/class.galleries.php");
require_once ("../classes/class.tags.php");

$user = $auth->requireTagAccessJson();

if ($user->allowedToTag()) {
	
	if (isset($_POST['id'], $_POST['tag'])) {
		$gal_id = (int)$_POST['id'];
		$tag 	= (int)$_POST['tag'];

		$dont_remove_tag = (isset($_POST['dont_remove_tag'])) ? true : false;

		$gallery = new Galleries($db->_db);

		$tags_added = $gallery->insertTag($gal_id, $tag, $dont_remove_tag);

		if ($tags_added) {
			$user->galleryTagAdded($gal_id, $tag);

			$string = array ( 'success' => $gal_id, 'tags_added' => $tags_added );
		} else {
			$string = array( 'error' => "Галера: {$gal_id}, Тег: {$tag} update error" );
		}
	} else {
		$string = array( 'error' => 'POST сформирован неверно' );
	}

}

echo json_encode($string);
