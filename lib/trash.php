<?php
	require_once(__DIR__."/../classes/class.trash.php");
	$trasher = new Trash;
	$images_trash_count = $trasher->getTrashImagesCount();
	$galleries_trash_count = $trasher->getTrashGalleriesCount();
	// $galleries_trash_trash_count = $trasher->getTrashTableGalleriesCount();
	$trasher->clearImagesSources();
?>
	Изображений в трэше: <?=$images_trash_count?>, 
	Галлерей в трэше: <?=$galleries_trash_count?>,
	
	Удаление для граббера:
	<br />
<?php

	if (isset($_GET['clear'])) {
		$gal = new Galleries();
		$res = $gal->clearTrash();
		if($res) {
			echo "<h1>Trash cleared</h1>";
		}
	} else {
		echo "Нет параметров<br>
			  <a href='index.php?act=trash&amp;clear=1'>Очистить треш</a>";
	}
?>