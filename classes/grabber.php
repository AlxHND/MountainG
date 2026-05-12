<?php
//
// Класс граббера 
//

class Grabber extends Parser 
{

	protected $Folder; //VARCHAR
	protected $TempFiles; //ARRAY
	protected $md5; //ARRAY
	protected $errorFlag;

	public $gal_id;


	public function ShowMD5() {
		return $this->md5;
	}

	public function ShowFiles () {
		return $this->TempFiles;
	}

	public function ShowFolder () {
		return $this->Folder;
	}

	public function ShowError () {
		return $this->errorFlag;
	}

	function setFolderId($gal_id) {
		$this->gal_id = (int)$gal_id;
	}


	public function GetPictures($gallery_url = false, $images_array = false) {

		if($gallery_url === false) {
			$gallery_url = $this->GalleryURL;
		} else {
			$this->GalleryURL = $gallery_url;
		}

		$this->Folder = md5(time().$gallery_url);
		$TempFolder = getcwd() . "/temp/" . $this->Folder;
		if (!is_dir($TempFolder)) {
			$log = new Logger(__METHOD__.": Нет папки '".$TempFolder."' создаю", true);
			if (mkdir($TempFolder)) {
				$folder_result = is_dir($TempFolder) ? "OK" : "но что-то пошло не так";
				$log = new Logger(__METHOD__.": Папку создал '".$TempFolder."' ". $folder_result, true);
				$chmoded = chmod($TempFolder, 0777);
			} else {
				$this->TempFiles = FALSE;
				$this->errorFlag = 'Невозможно создать папку '.$TempFolder;
				return FALSE;
			}
		}
		
		if ($images_array === false) {
			$images_array = $this->Images;
		} else {
			$this->Images = array();
			foreach($images_array as $image_u) {
				$this->Images[] = $image_u;
			}
		}

		if($images_array && is_array($images_array)) {
			$i = 0;
			foreach($images_array as $image_url) {

				$filepath =  ($i < 9)  ? $TempFolder . "/0" . ($i+1) . ".jpg" : $TempFolder . "/". ($i+1) . ".jpg";

				if ($image_url) {
					$ch = curl_init( $image_url );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
					curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
					curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
					curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8");  // useragent
					curl_setopt($ch, CURLOPT_REFERER, $gallery_url);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
					curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
					curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
					
					$grabbedFile = curl_exec ($ch);
					$curlResult = curl_getinfo($ch);

					if ($curlResult ['content_type'] == 'image/jpeg' ||
						$curlResult ['content_type'] == 'image/jpg' ||
					 	$curlResult ['content_type'] == 'image/pjpeg' ||
					  	$curlResult ['content_type'] == 'image/jpeg; charset=binary'
					) {
						// $image = file_put_contents($filepath, $grabbedFile);

						$file_h = fopen($filepath, "w");
						
						if(!$file_h) {
							$log = new Logger(__METHOD__.": can't open file for writing '".$filepath."'", true);
							return false;
						}

						if(!fwrite($file_h, $grabbedFile)) {
							$log = new Logger(__METHOD__.": can't write to '".$filepath."'", true);
							return false;
						} else {
							$image = true;
						}
						fclose($file_h);						
						

						if ($image === FALSE) {
							$temp_pics_path[$i] = FALSE;
							$log = new Logger(__METHOD__.": невозможно записать файл '".$filepath."'", true);
						} else {
							$md5[$i] = md5_file($filepath);
							$temp_pics_path[$i] = $filepath;
						}
					}
					curl_close ($ch);
				} else {
					$log = new Logger (__METHOD__.": image_url в цикле == false", true);
				}
				$i++;
			}
		}

		$this->md5 = $md5; // ???? $this->Folder?

		if (isset($temp_pics_path)) {
			$this->TempFiles = $temp_pics_path;
			// var_dump($this->TempFiles);
			if($images_array) {
				$this->Count = count($images_array);
			}

			$this->FixGrabErrors();
			// var_dump($this->TempFiles);

			if ($this->Count == 0) {
				$this->TempFiles = FALSE;
				return FALSE;
			}
			return TRUE;
		} else {
			$this->TempFiles = FALSE;			
			return FALSE;
		}
	}
	
	private function FixGrabErrors () { // Удаление из массивов битых, и кривых файлов

		$count = $this->Count;
		$c = 0;

		for ($i=0; $i< $count;$i++) {
			if (isset($this->TempFiles[$i])) {
				if (!$this->TempFiles[$i]) {
					unset ($this->TempFiles[$i]);
					unset ($this->md5[$i]);
					unset ($this->Images[$i]);
					$this->Count--;
				} elseif (filesize($this->TempFiles[$i]) < 12000) {
					unlink ($this->TempFiles[$i]);
					unset ($this->TempFiles[$i]);
					unset ($this->md5[$i]);
					unset ($this->Images[$i]);
					$this->Count--;
				}
				else {
	// 				echo "{$i} OK!";
					$TempArray ['TempFiles'] [$c] = $this->TempFiles[$i];
					$TempArray ['md5'] [$c] = $this->md5[$i];
					$TempArray ['Images'] [$c] = $this->Images[$i];
					$c++;
				}
			}
		}
		// var_dump($TempArray);
		if (isset ($TempArray)) {
			$this->TempFiles = $TempArray ['TempFiles'];
			$this->md5 = $TempArray ['md5'];
			$this->Images = $TempArray ['Images'];
			$this->Count = count($TempArray ['Images']);
		}
	}
}
