<?php
	ini_set('display_errors',1);
	error_reporting(E_ALL);
	$scriptDir = dirname($_SERVER ['SCRIPT_FILENAME']);
	require_once ($scriptDir. "/config/config.inc");
	require_once ($scriptDir . "/classes/mysql.php");
	require_once ($scriptDir . "/classes/informator.php");
	$DB = New MySQL (DBHOST, DBNAME, DBUSER, DBPW);
	$default = New DBTools(HOSTING,$rssThumbSizes);
	function CropManualThumb ($OriginalImage, $outputImage,
				$IMString = "-strip -filter Blackman -unsharp 1x0.4+1 -modulate 105,105,100",	
				$Width = 240,
				$Height = 320,
				$Quality = 95,
				$x, 
				$y, 
				$w, 
				$h) {

		$OriginalImage = str_replace('tn-','',$OriginalImage);
	
		$image_in = $OriginalImage;
		$image_out  = $outputImage;
		$originalH = $h;
		$originalW = $w;

		$h = $w/($Width/$Height);

//		list($oWidth, $oHeight_i, $type) = getimagesize($image_in);
	
		$cropString = "convert ".$image_in."  -crop ".$w."x".$h."+".$x."+".$y." -resize ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
		$cropString = escapeshellcmd($cropString);

		exec($cropString);
		list($oWidth, $oHeight_i, $type) = getimagesize($image_out);

		if ($oWidth < $Width || $oHeight_i < $Height) {
			unlink ($image_out);
			if ($oWidth < $Width) $w = ceil($originalH/($Height/$Width));
			else $h = $originalW/($Width/$Height);
			$cropString = "convert ".$image_in."  -crop ".$w."x".$h."+".$x."+".$y." -resize ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
			exec($cropString);
		}



		
	
		
		$image_out = $outputImage; 
	
		return $OriginalImage;
	}
    	$y = $_POST['top'];
	$x = $_POST['left'];
    	$w = $_POST['width'];
	$h = $_POST['height'];
	$imageId = (int)$_POST['alt'];
	$sql = "SELECT * FROM galleries_pix WHERE image_id LIKE '".$imageId."'";
	$sql = mysql_query($sql) or die (mysql_error());
	$row = mysql_fetch_array($sql, MYSQL_ASSOC);
	$galId = (int)$row['gal_id'];
	$imagePath = $row['image'];
	$sql1 = "SELECT gal_md5,gal_type,gal_paysite FROM galleries WHERE gal_id LIKE '".$galId."'";
	$sql1 = mysql_query($sql1) or die (mysql_error());
	
	$result = mysql_fetch_array($sql1, MYSQL_ASSOC);
	$galType = $result['gal_type'];
	$galPaysite = $result['gal_paysite'];
	$galMD5 =  $result['gal_md5'];
	if ($galType !== 'Pics') $imagePath = "/".$galPaysite."/".$galId."/".$galMD5."/".$imageId.".jpg";;

	if ($imageId < 256000){
		$folderId = (int)ceil($imageId/1000);
		$folder = "1/".$folderId;
	} else {
		$mainFolder= (int)ceil($imageId/256000);
		$folderId = (int)ceil($imageId/1000);	
		$folder = $mainFolder."/".$folderId;
	}
	if (mkdir(TMPDIR."/".$galId,0777)) {
		if (chmod (TMPDIR."/".$galId, 0777)) {
			$urlToGetImage = HOSTING. $imagePath;
			$tmpOriginalImageFile = TMPDIR."/".$galId."/".$imageId.".jpg";

			$ch = curl_init( $urlToGetImage);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
			curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
			curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8");  // useragent
			curl_setopt($ch, CURLOPT_REFERER, 'http://www.sexhoundlinks.com/');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
			
			$grabbedFile = curl_exec ($ch);
			$curlResult = curl_getinfo($ch);
			if ($curlResult ['content_type'] == 'image/jpeg') {
				if ($image = file_put_contents($tmpOriginalImageFile,$grabbedFile)) {
					curl_close ($ch);
					if (is_file($tmpOriginalImageFile)) {
						if ($galType == 'Pics') $thumbsToOutput = $rssThumbSizes;
						else $thumbsToOutput = $rssMovieThumbs;
						foreach ($thumbsToOutput as $rssThumbSize) {
							if ($galType == 'Pics') $uploadImagesFolder = "/thumbs/p/".$rssThumbSize['width']."/".$folder;
							else  $uploadImagesFolder = "/thumbs/m/".$rssThumbSize['width']."/".$folder;
							CropManualThumb(TMPDIR."/".$galId."/".$row['image_id'].".jpg",TMPDIR."/".$galId."/".$rssThumbSize['width'] ."x".$rssThumbSize['height'].$row['image_id'].".jpg","-strip -filter Blackman -unsharp 1x0.6+1 -modulate 105,105,100",$rssThumbSize['width'],$rssThumbSize['height'],95,$x,$y,$w,$h);
							$uploadFoldersTree = explode("/", $uploadImagesFolder);
							$tempFolderToMake = "";
							foreach ($uploadFoldersTree as $folderToMake) {
								if ($folderToMake !== "") {
									$tempFolderToMake .= "/" . $folderToMake;
								if (!@is_dir(UPLOADFOLDER . $tempFolderToMake)) {
									if(mkdir(UPLOADFOLDER . $tempFolderToMake, 0777) or die("Не могу создать директорию ".UPLOADFOLDER.$tempFolderToMake)) chmod(UPLOADFOLDER.$tempFolderToMake, 0777);
								}
								}
							}
							copy (TMPDIR."/".$galId."/".$rssThumbSize['width'] ."x".$rssThumbSize['height'].$row['image_id'].".jpg", UPLOADFOLDER. $uploadImagesFolder."/".$row['image_id'].".jpg");
							unlink (TMPDIR."/".$galId."/".$rssThumbSize['width'] ."x".$rssThumbSize['height'].$row['image_id'].".jpg");
						}
				
						if ($galType == 'Pics') {
							CropManualThumb(TMPDIR."/".$galId."/".$row['image_id'].".jpg",TMPDIR."/".$galId."/150x200-".$row['image_id'].".jpg","-strip -filter Blackman -unsharp 1x0.7+1 -modulate 105,105,100",150,200,95,$x,$y,$w,$h);
							copy (TMPDIR."/".$galId."/150x200-".$row['image_id'].".jpg", UPLOADFOLDER. dirname($row['image'])."/thumbs/tn-150x200-".basename($row['image']));
							unlink(TMPDIR."/".$galId."/150x200-".$row['image_id'].".jpg");
						}
						unlink(TMPDIR."/".$galId."/".$row['image_id'].".jpg");
						rmdir(TMPDIR."/".$galId);
				
						if ($galType == 'Pics') $thumb = HOSTING . "/thumbs/p/150/".$folder."/".$row['image_id'].".jpg";
						else $thumb = HOSTING . "/thumbs/m/200/".$folder."/".$row['image_id'].".jpg";
						die('{"success":"'.$thumb.'"}');
					} else $default->Logging("Error! ".$galId . ", can't copy file:".UPLOADFOLDER.$row['image'] ." to " .TMPDIR."/".$galId."/".$row['image_id'].".jpg");
				} else curl_close ($ch);
			} else curl_close ($ch);
		} else $default->Logging("Error! ".$galId . ", can't chmod dir:".TMPDIR."/".$galId);
	} else $default->Logging("Error! ".$galId . ", can't make dir:".TMPDIR."/".$galId);

?>
