<?php
class Cropper 
{

	protected $ThumbFile;
	protected $CropProfile;
	public function CropManualThumb ($OriginalImage, 
				$IMString = "-strip -filter Blackman -unsharp 1x0.4+1 -modulate 105,105,100",	
				$Width = 240,
				$Height = 180,
				$Quality = 95,
				$x, 
				$y, 
				$w, 
				$h) {
	
		$image_in = $OriginalImage;
		$imageId = str_replace("original-","",basename($OriginalImage));
		$image_out  = dirname($OriginalImage). "/" .$imageId; 

		list($oWidth, $oHeight_i, $type) = getimagesize($OriginalImage);
	
		$cropString = "convert ".$image_in."  -crop ".$w."x".$h."+".$x."+".$y." -resize ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
		$cropString = escapeshellcmd($cropString);
	
		exec($cropString);
	
		$this->ThumbFile = $image_out;
		return $image_out;
		
}

	public function ThumbFile () {
		return $this->ThumbFile;
	}

	public function CropMovieThumb ($OriginalImage, 
					$IMString = "-strip -filter Blackman -unsharp 1x0.5+1 -modulate 110,115,100",	
					$Width = 200,
					$Height = 150,
					$Quality = 90,
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
//		if ($imageRatio < 0.75) {

//		}
		$height_i =  $height_i - $CutTop - $CutBottom;
		$width_i = $width_i - $CutLeft - $CutRight;
		if ($height_i < $width_i) {
			$ratio = $height_i / $Height;
			$width_temp = $Width * $ratio;
			$tsz = $width_i /2 - $width_temp /2;
			$tsz = ceil(abs ($tsz));
			if ($tsz < $CutLeft) { $tsz +=$CutLeft;}
			$cropString = "convert ".$image_in."  -crop ".$width_temp."x".$height_i."+".$tsz."+".$CutTop." -resize ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
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
					$IMString = "-strip -filter Blackman -unsharp 1x0.5+1 -modulate 110,115,100",	
					$Width = 150,
					$Height = 200,
					$Quality = 90,
					$CutTop = 0, 
					$CutBottom = 0, 
					$CutLeft = 0, 
					$CutRight = 0) 
	{
		$image_in = $OriginalImage;
		$image_out  = dirname($OriginalImage). "/tn-" . $Width . "x" . $Height . "-" .basename($OriginalImage); 
		if (is_file($OriginalImage)) {
			list($width_i, $height_i, $type) = getimagesize($OriginalImage);
		} else return FALSE;
		$height_i =  $height_i - $CutTop - $CutBottom;
		$width_i = $width_i - $CutLeft - $CutRight;
		if ($height_i < $width_i) {
			$ratio = $height_i / $Height;
			$width_temp = $Width * $ratio;
			$tsz = $width_i /2 - $width_temp /2;
			$tsz = ceil(abs ($tsz));
			if ($tsz < $CutLeft) { $tsz +=$CutLeft;}
			$cropString = "convert ".$image_in."  -crop ".$width_temp."x".$height_i."+".$tsz."+".$CutTop." -resize ".$Width."x".$Height." ".$IMString." -format jpeg -quality ".$Quality." ".$image_out;
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
		if (is_file($image_out)) {
			chmod($image_out, 0777);
			return TRUE;
		}
		else return FALSE;
	}
}
?>
