<?php
class Resizer {
	private $ThumbFile;
	private $cropProfile;
	private $imString;

	private $fileList;
	private $folderName;
	private $workFolder;
	private $initialized;

	static $folderList;

	

	//
	//
	//	Создание и удаление папок для каждого отдельного pid, чтобы разделять разные версии запущеных скриптов, и чистить папки по деструкту корректно!!!!
	//	названия папок храняться в ключах массива для удобства деструкта
	//

	function __construct () {

		$this->fileList = array();
		$this->imString = IM_DEFAULT_STRING;
		$this->cropProfile = 0;
		$this->folderName = "th_".md5(time().getmypid());
		$this->initialized = false;
		$this->workFolder = false;

		if (isset(self::$folderList) && is_array(self::$folderList)) {
			if (array_key_exists($this->folderName, self::$folderList)) $this->folderName = "th_".md5(time().getmypid().count(self::$folderList));
		} else self::$folderList = array();
		self::$folderList[$this->folderName] = true;
		$this->setMainFolder();
	}

	function __destruct () {
//		var_dump($this->fileList);
		$this->removeFiles();
		$this->removeDir();
		unset (self::$folderList[$this->folderName]);
	}

	private function setMainFolder () {
		$result = false;
		$workTmpFolder = TMPDIR . "/" . $this->folderName;

		if ($workTmpFolder) {
			if (is_dir($workTmpFolder) || @mkdir($workTmpFolder,0777)) {
				if (@chmod ($workTmpFolder, 0777)) {
					$result = true;
					$this->workFolder = $workTmpFolder;
					$this->initialized = true;
				} else throw new Exception ("Cant chmod dir ".$workTmpFolder);
			} else throw new Exception ("Cant mkdir dir ".$workTmpFolder);
		}
		return $result;
	}

	private function removeDir () {
		if ($this->initialized && $this->workFolder && $this->folderName && $this->folderName !== "") {
			$dirName = TMPDIR . "/" . $this->folderName;
			if (is_dir($dirName)) rmdir($dirName);
		}
	}

	private function removeFiles () {
		if (is_array($this->fileList) && count($this->fileList) > 0) {
			foreach ($this->fileList as $file) {
				if (is_file($file)) unlink ($file);
			}
		}
		unset ($this->fileList);
		return true;
	}

	//
	//
	//

	public function setImString () {
		
	}

	public function manualThumbCrop ($OriginalImage, 
					$Width = 200,
					$Height = 150,
					$IMString = "-strip -filter Blackman -unsharp 1x0.5+1 -modulate 110,115,100",
					$Quality = 85,
					$CutTop = 0, 
					$CutBottom = 0, 
					$CutLeft = 0, 
					$CutRight = 0) 
	{
		$image_in = $OriginalImage;
		$imageId = str_replace("original-","",basename($OriginalImage));
		$image_out  = dirname($OriginalImage). "/" .$imageId; 
		list($width_i, $height_i, $type) = getimagesize($OriginalImage);
		$imageRatio = $height_i / $width_i;
		$height_i =  $height_i - $CutTop - $CutBottom;
		$width_i = $width_i - $CutLeft - $CutRight;

		if ($height_i < $width_i) {
			$ratio = $height_i / $Height;
			$width_temp = $Width * $ratio;
			$tsz = $width_i /2 - $width_temp /2;
			$tsz = ceil(abs ($tsz));
			if ($tsz < $CutLeft) { $tsz +=$CutLeft;}
			$cropString = "convert ".$image_in."  -auto-orient -strip -crop ".$width_temp."x".$height_i."+".$tsz."+".$CutTop." -thumbnail '".$Width."x".$Height."^'' ".$IMString." -extent ".$Width."x".$Height." -format jpeg -quality ".$Quality." ".$image_out;
			$cropString = escapeshellcmd($cropString);
		}
		else {
			if (($width_i / $height_i) > ($Width / $Height)) {
				$ratio = $Width / $Height;
				$width_i = $height_i * $ratio;	
				$height_temp = $height_i;
			} else {
				$ratio = $width_i / $Width;
				$height_temp = $Height * $ratio;
			}
			$tsz = $height_i / 2 - $height_temp / 2;
			$tsz = ceil(abs ($tsz));
			if ($tsz < $CutTop) { $tsz +=$CutTop;}
			$cropString = "convert ".$image_in."  -crop ".$width_i."x".$height_temp."+".$CutLeft."+".$tsz." -resize ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
			$cropString = escapeshellcmd($cropString);
		}

		$this->ThumbFile = $image_out;
		exec($cropString);

		if (is_file($image_out)) return TRUE;
		else return FALSE;
	}

	public function CropThumb (	$OriginalImage, 
					$Width = 150,
					$Height = 200,
					$IMString = "-strip -filter Blackman -unsharp 1x0.5+1 -modulate 110,115,100",
					$Quality = 90,
					$CutTop = 0, 
					$CutBottom = 0, 
					$CutLeft = 0, 
					$CutRight = 0) 
	{
		
		if($Width > $Height) { $IMString = IM_DEFAULT_VIDEO_STRING; } 
		else { $IMString = IM_DEFAULT_STRING; }


		$image_in = $OriginalImage;
		$image_out  = $this->workFolder. "/tn-" . $Width . "x" . $Height . "-" .basename($OriginalImage); 
		if (is_file($OriginalImage)) {
			if (!list($width_i, $height_i, $type) = getimagesize($OriginalImage)) {
				$error = new Logger ($OriginalImage." не изображение");
	        	return FALSE;	
			}
		} else {
	        $error = new Logger ($OriginalImage." нет файла", true);
	        return FALSE;
	    }
		$height_i =  $height_i - $CutTop - $CutBottom;
		$width_i = $width_i - $CutLeft - $CutRight;

		if ($height_i < $width_i) {
			$ratio = $height_i / $Height;
			$width_temp = $Width * $ratio;
			$tsz = $width_i /2 - $width_temp /2;
			$tsz = ceil(abs ($tsz));
			if ($tsz < $CutLeft) { $tsz +=$CutLeft;}
			$cropString = "convert ".$image_in."  -crop ".$width_temp."x".$height_i."+".$tsz."+".$CutTop." -thumbnail ".$Width."x".$Height."^ -gravity center -extent ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
			$cropString = escapeshellcmd($cropString);
		}
		else {
			if (($width_i / $height_i) > ($Width / $Height)) {
				$ratio = $Width / $Height;
				$width_i = $height_i * $ratio;	
				$height_temp = $height_i;
			} else {
				$ratio = $width_i / $Width;
				$height_temp = $Height * $ratio;
			}
			$tsz = $height_i / 2 - $height_temp / 2;
			$tsz = ceil(abs ($tsz));
			if ($tsz < $CutTop) { $tsz +=$CutTop;}
			$cropString = "convert ".$image_in."  -crop ".$width_i."x".$height_temp."+".$CutLeft."+".$tsz." -thumbnail  ".$Width."x".$Height."^ -gravity center -extent ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
			echo $cropString ."<br>";
			$cropString = escapeshellcmd($cropString);
		}

		exec($cropString);
		$this->ThumbFile = $image_out;

		if (is_file($image_out)) {
			$this->fileList[] = $image_out;
			return $image_out;
		}
		else {
			$error = new  Logger (__CLASS__ . "|" . __FUNCTION__ . "|" . __METHOD__.": ошибка создания тумбы (ошибка convert) из ".$image_in." в".$image_out);
			return FALSE;
		}
	}

	public function ResizeThumb (	$OriginalImage, 
					$Width = 150,
					$IMString = "-strip -filter Blackman -unsharp 1x0.5+1 -modulate 110,115,100",
					$Quality = 85,
					$CutTop = 0, 
					$CutBottom = 0, 
					$CutLeft = 0, 
					$CutRight = 0) 
	{
		
		$IMString = IM_DEFAULT_STRING;

		$image_in = $OriginalImage;
		$image_out  = $this->workFolder. "/tn-" . $Width . "-" .basename($OriginalImage); 
		if (is_file($OriginalImage)) {
			if (!list($width_i, $height_i, $type) = getimagesize($OriginalImage)) {
				$error = new Logger ($OriginalImage." не изображение");
	        	return FALSE;	
			}
		} else {
	        $error = new Logger ($OriginalImage." нет файла", true);
	        return FALSE;
	    }
		$height_i =  $height_i - $CutTop - $CutBottom;
		$width_i = $width_i - $CutLeft - $CutRight;

		$x = $CutTop;
		$y = $CutLeft;

		$cropString = "convert ".$image_in." -crop ".$width_i."x".$height_i."+".$x."+".$y." -resize ".$Width." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
		// echo "<br>".$cropString ."<br>";
		$cropString = escapeshellcmd($cropString);

		exec($cropString);
		$this->ThumbFile = $image_out;

		if (is_file($image_out)) {
			$this->fileList[] = $image_out;
			return $image_out;
		}
		else {
			$error = new  Logger (__CLASS__ . "|" . __FUNCTION__ . "|" . __METHOD__.": ошибка создания тумбы (ошибка convert) из ".$image_in." в".$image_out, false);
			return FALSE;
		}
	}
}
?>