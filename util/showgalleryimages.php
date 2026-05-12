<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (isset ($_POST['galid']) && isset($_POST['galtype']) && intval($_POST['galid'])) {
	$galId = intval($_POST['galid']);
	if ($galId !== 0) {
		require_once ("../config/config.php");
		require_once ("../classes/class.logger.php");
		require_once ("../classes/class.db_access.php");		
		require_once ("../classes/class.images.php");
		
		$thumbUrlPre = HOSTING; 
		$thumbUrlPre .= ($_POST['galtype'] == 'Movies') ? "/thumbs/m/240" : ( ($_POST['galtype'] == 'horiz_thumbs') ? "/thumbs/x300" :  "/thumbs/p/180" );

		$images = new Images($db->_db);
		$galImages = $images->getGalImages($galId);

		if ($galImages) {
			if (count($galImages) == 0) {
				echo "Изображения не найдены";	
			} else {
				foreach ($galImages as $thumbId => $image) {
			        if ($thumbId < 256000){
			          $folderId = (int)ceil($thumbId/1000);
			          $folder = "1/".$folderId;
			        } else {
			          $mainFolder= (int)ceil($thumbId/256000);
			          $folderId = (int)ceil($thumbId/1000);
			          $folder = $mainFolder."/".$folderId;
			        } 					
			        $thumbURL = $thumbUrlPre ."/". $folder ."/".$thumbId.".jpg?".md5(time().getmypid());
			        if (isset($_POST['main_thumbs'])) { ?>
			        	<img style="width: 16.4%; height: auto;" id="thumb_preview_<?=$thumbId?>" onclick='set_make_gallery_main_thumb(<?=$_POST['galid']?>, <?=$thumbId?>, "<?=HOSTING?>", "<?=$_POST['galtype']?>"); setCurrentId(<?=$_POST['galid']?>);' src="<?=$thumbURL?>">
<?php			    } else { ?>
			        	<img class="width: 16.4%; height: auto;" id="thumb_preview_<?=$thumbId?>" src="<?=$thumbURL?>">
<?php			        
					}
				}

			}
		}
		else {
			echo "Ошибка! Проблема коннекта к базе";
		}
	} else {
		echo "Ошибка! Значение ID галлереи == 0";
	}
} else {
	echo "Ошибка! Проблема с получаемым ID галлереи.";
}
	
?>