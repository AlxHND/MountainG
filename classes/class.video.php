<?php

class FileUtils
{
	public $errorFlag;
	protected $workFolder;
	protected $fileName;

	function __construct($tempPath)
	{
		$this->workFolder = $tempPath;
	}

	public function GetFile($url, $tempFilename)
	{
		$videoFolder = md5($url);
		$this->fileName = $videoFolder;

		$tempFolder = dirname($tempFilename);
		if (!is_dir($tempFolder)) {
			if (mkdir($tempFolder)) {
				if (chmod($tempFolder, 0777)) {
					$this->errorMsg = "chmod error. folder: " . $tempFolder;
					return FALSE;
				}
			} else {
				$this->errorMsg = "mkdir error. folder: " . $tempFolder;
				return FALSE;
			}
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		$__s = curl_exec($ch);
		$hostInfo = curl_getinfo($ch);
		curl_close($ch);

		$url = $hostInfo['url'];

		//		echo "<br>".$url."<br>";		

		preg_match('@^(?:http://)?([^/]+)@i', $url, $matches);
		$host = $matches[1];

		preg_match('/[^.]+\.[^.]+$/', $host, $matches);
		$header = "";

		$filename = substr($url, strripos($url, $host) + strlen($host), strlen($url));

		//		echo "<br>".$host."<br>";

		if ($sock = fsockopen($host, 80, $errno, $errstr)) {
			$sntHeader = "GET " . $filename . " HTTP/1.1\r\nHost: " . $host . "\r\nConnection: Close\r\n\r\n";
			fwrite($sock, $sntHeader);

			set_time_limit(180);

			do {
				$header .= fgets($sock, 128);
			} while (strpos($header, "\r\n\r\n") === false);

			if (strpos($header, "200 OK")) {
				if ($fw = fopen($tempFilename, "w")) {
					if (chmod($tempFilename, 0777)) {
						while (!feof($sock)) {
							$getfile = fgets($sock, 128);
							fputs($fw, $getfile);
						}
						fclose($sock);
						fclose($fw);
					} else {
						$this->errorMsg = "file chmod: " . $tempFolder;
						return FALSE;
					}
				} else {
					$this->errorMsg = "file open error. folder: " . $tempFolder;
					return FALSE;
				}
			} else {
				$this->errorMsg = "download error (not 200 OK). folder: " . $tempFolder;
				return FALSE;
			}
		} else {
			$this->errorMsg = "socket error. folder: " . $tempFolder;
			return FALSE;
		}
		//		echo $tempFilename."<br />";
		return $tempFilename;
	}
}

class VideoUtils extends FileUtils
{
	private $video_file = false;
	private $duration = false;
	private $error_msg = false;

	function __construct() {}

	public function setVideoFile($video_file)
	{
		$file = preg_replace("([^\w\s\d\-_~,;:\[\]\(\).])", '', $video_file);
		$file = preg_replace("([\.]{2,})", '', $file);
		$this->video_file = $file;
		$this->duration = $this->GetDuration($file);
	}

	private function cleanImagesDir($folder)
	{
		$result = true;
		$imagesFoundBeforeMake = $this->findScreenshots($folder);
		if (isset($imagesFoundBeforeMake) && is_array($imagesFoundBeforeMake)) {
			foreach ($imagesFoundBeforeMake as $file) {
				$finfo = new finfo(FILEINFO_MIME);
				if (is_file($file) && preg_match('#image\/(jpeg|bmp)#im', $finfo->file($file))) {
					if (!unlink($file)) {
						$result[] = $file;
						$log = new Logger(__CLASS__ . "->" . __METHOD__ . ": невозможно удалить файл при чистке директории (создание скринов) " . $file, true);
					}
				}
			}
		}
		return $result;
	}

	function makeScreenshots($video, $folder = false)
	{
		if (!$this->GetDuration($video)) return false;
		if ($folder === false) $tempPath = dirname($video); // исправить!!!
		else $tempPath =  TMPDIR . "/" . $folder;
		$output = false;
		if (!is_dir($tempPath)) {
			mkdir($tempPath);
			chmod($tempPath, 0777);
		} else {
			$clearResult = $this->cleanImagesDir($tempPath);
		}
		$ffmpegString = FFMPEG_PATH . " -i " . $video . " -r .2 -ss 1 " . $tempPath . "/%03d.jpg";
		echo $ffmpegString . "<br />";
		ob_start();
		passthru($ffmpegString);
		//passthru ($ffmpegString);
		$duration = ob_get_contents();
		ob_end_clean();
		$this->errorFlag = $duration;
		//var_dump($duration);
		$output = $this->findScreenshots($tempPath);
		//var_dump($output);
		if ($folder === false) return $tempPath;
		else return $output;
	}

	function mergeVideos($videos, $outputVideo)
	{
		$mergingString = "cat ";
		// если есть файл аутпут - ошибка
		if (is_array($videos) && count($videos) > 1 && !is_file($outputVideo)) {
			foreach ($videos as $video) {
				if (is_file($video) && $this->GetDuration($video)) {
					$mergingString .= $video . " ";
				} else return false;
				// если нет лбюбого файла и они не видео - возврат ошибки
			}
			$mergingString .= " > " . $outputVideo;
			// echo "<br>".$mergingString."<br>";
			passthru($mergingString);

			if (is_file($outputVideo) && $this->GetDuration($outputVideo)) return true;
			else return false;
		}
		return false;
	}

	function convertToMpeg($video, $output, $width = 640, $height = 480)
	{
		$tempFolder = dirname($output);
		if (!is_dir($tempFolder)) {
			if (mkdir($tempFolder)) {
				chmod($tempFolder, 0777);
			} else {
				echo "mkdir error. folder: " . $tempFolder;
				return FALSE;
			}
		}
		if (is_file($video) && !is_file($output)) {
			$duration = $this->GetDuration($video);
			if ($duration && $duration > 5) {
				$ffmpegString = FFMPEG_PATH . " -i " . $video . " -sameq -s " . $width . "x" . $height . " " . $output;
				passthru($ffmpegString);
				$duration = $this->GetDuration($output);
				if ($duration && $duration > 5) {
					return true;
				} else unlink($output);
			}
		}
		return false;
	}

	function convertVideoBitrate($video, $output, $max_bitrate = 900)
	{
		return $this->convertVideo($video, $output, 'normal', $max_bitrate);
	}

	private function runCommand($command, &$outputLines = array())
	{
		$outputLines = array();
		$exitCode = 0;
		exec($command . " 2>&1", $outputLines, $exitCode);
		$this->errorFlag = implode("\n", $outputLines);

		return $exitCode === 0;
	}

	private function ensureDir($dir)
	{
		if (is_dir($dir)) {
			return true;
		}

		if (@mkdir($dir, 0777, true)) {
			@chmod($dir, 0777);
			return true;
		}

		return false;
	}

	private function removeTree($dir)
	{
		if (!is_dir($dir)) {
			return true;
		}

		$files = scandir($dir);
		if ($files === false) {
			return false;
		}

		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$path = $dir . "/" . $file;
			if (is_dir($path)) {
				$this->removeTree($path);
			} elseif (is_file($path)) {
				@unlink($path);
			}
		}

		return @rmdir($dir);
	}

	private function buildPreviewTimeline($duration, $clipCount, $clipLengthSeconds, $startOffset, $endOffset)
	{
		$duration = (float)$duration;
		$clipCount = (int)$clipCount;
		$clipLengthSeconds = (float)$clipLengthSeconds;
		$startOffset = max(0, (float)$startOffset);
		$endOffset = max(0, (float)$endOffset);

		$result = array();
		$usableStart = $startOffset;
		$usableEnd = max($usableStart, $duration - $endOffset);
		$usableDuration = $usableEnd - $usableStart;

		if ($duration <= 0 || $clipCount <= 0 || $clipLengthSeconds <= 0 || $usableDuration <= 0.2) {
			return $result;
		}

		if ($usableDuration <= $clipLengthSeconds) {
			$result[] = $usableStart;
			return $result;
		}

		$maxStart = max($usableStart, $usableEnd - $clipLengthSeconds);

		if ($clipCount === 1) {
			$result[] = min($maxStart, $usableStart + (($usableDuration - $clipLengthSeconds) / 2));
			return $result;
		}

		$step = ($maxStart - $usableStart) / ($clipCount - 1);
		for ($i = 0; $i < $clipCount; $i++) {
			$point = $usableStart + ($step * $i);
			if ($point > $maxStart) {
				$point = $maxStart;
			}
			$result[] = round($point, 3);
		}

		return $result;
	}

	public function makePreview($video, $output, array $options = array())
	{
		$video = (string)$video;
		$output = (string)$output;

		if (!is_file($video)) {
			$this->error_msg = "Preview source file not found: " . $video;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			return false;
		}

		$duration = $this->GetDuration($video);
		if (!$duration) {
			$this->error_msg = "Preview source duration not detected: " . $video;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			return false;
		}

		$clipCount = isset($options['clip_count']) ? (int)$options['clip_count'] : 10;
		$clipLengthMs = isset($options['clip_length_ms']) ? (int)$options['clip_length_ms'] : 1000;
		$startOffset = isset($options['start_offset']) ? (int)$options['start_offset'] : 5;
		$endOffset = isset($options['end_offset']) ? (int)$options['end_offset'] : 5;
		$targetWidth = isset($options['width']) ? (int)$options['width'] : 320;
		$targetHeight = isset($options['height']) ? (int)$options['height'] : 180;
		$fps = isset($options['fps']) ? (int)$options['fps'] : 30;
		$crf = isset($options['crf']) ? (int)$options['crf'] : 28;

		if ($clipCount < 1) $clipCount = 1;
		if ($clipCount > 20) $clipCount = 20;
		if ($clipLengthMs < 250) $clipLengthMs = 250;
		if ($clipLengthMs > 5000) $clipLengthMs = 5000;
		if ($targetWidth < 160) $targetWidth = 160;
		if ($targetWidth > 640) $targetWidth = 640;
		if ($targetHeight < 90) $targetHeight = 90;
		if ($targetHeight > 360) $targetHeight = 360;
		if ($fps < 24) $fps = 24;
		if ($fps > 48) $fps = 48;
		if ($crf < 24) $crf = 24;
		if ($crf > 40) $crf = 40;

		$clipLengthSeconds = $clipLengthMs / 1000;
		$timeline = $this->buildPreviewTimeline($duration, $clipCount, $clipLengthSeconds, $startOffset, $endOffset);

		if (!$timeline) {
			$this->error_msg = "Preview timeline is empty for file: " . $video;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			return false;
		}

		$outputDir = dirname($output);
		if (!$this->ensureDir($outputDir)) {
			$this->error_msg = "Preview output folder create failed: " . $outputDir;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			return false;
		}

		$tempDir = TMPDIR . "/preview_" . md5($video . "|" . $output . "|" . microtime(true));
		if (!$this->ensureDir($tempDir)) {
			$this->error_msg = "Preview temp folder create failed: " . $tempDir;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			return false;
		}

		@unlink($output);
		$concatListFile = $tempDir . "/concat.txt";
		$listRows = array();

		foreach ($timeline as $index => $startAt) {
			$partFile = $tempDir . "/part_" . str_pad((string)$index, 2, "0", STR_PAD_LEFT) . ".mp4";
			$partCommand = FFMPEG_PATH
				. " -y -ss " . escapeshellarg((string)$startAt)
				. " -i " . escapeshellarg($video)
				. " -t " . escapeshellarg((string)$clipLengthSeconds)
				. " -an -vf " . escapeshellarg(
					"scale=w=" . $targetWidth . ":h=" . $targetHeight . ":force_original_aspect_ratio=decrease," .
						"pad=" . $targetWidth . ":" . $targetHeight . ":(ow-iw)/2:(oh-ih)/2:black," .
						"fps=" . $fps
				)
				. " -c:v libx264 -preset slow -crf " . $crf
				. " -pix_fmt yuv420p "
				. escapeshellarg($partFile);
			$partOutput = array();
			if (!$this->runCommand($partCommand, $partOutput) || !is_file($partFile) || filesize($partFile) <= 0) {
				$this->error_msg = "Preview clip generation failed at index " . $index;
				$log = new Logger(__METHOD__ . ": " . $this->error_msg . ". Command output: " . implode("\n", $partOutput), true);
				$this->removeTree($tempDir);
				return false;
			}

			$listRows[] = "file '" . str_replace("'", "'\\''", $partFile) . "'";
		}

		if (file_put_contents($concatListFile, implode("\n", $listRows) . "\n") === false) {
			$this->error_msg = "Preview concat list create failed: " . $concatListFile;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			$this->removeTree($tempDir);
			return false;
		}

		$tempOutput = $tempDir . "/preview.mp4";

		// $concatCommand = FFMPEG_PATH
		// 	. " -y -f concat -safe 0 -i " . escapeshellarg($concatListFile)
		// 	. " -an -c:v libx264 -preset veryfast -crf " . $crf . " -movflags +faststart -pix_fmt yuv420p " . escapeshellarg($tempOutput);

		$concatCommand = FFMPEG_PATH
			. " -y -f concat -safe 0 -i " . escapeshellarg($concatListFile)
			. " -c copy"
			. " -movflags +faststart "
			. escapeshellarg($tempOutput);

		$concatOutput = array();
		if (!$this->runCommand($concatCommand, $concatOutput) || !is_file($tempOutput) || filesize($tempOutput) <= 0) {
			$this->error_msg = "Preview concat failed";
			$log = new Logger(__METHOD__ . ": " . $this->error_msg . ". Command output: " . implode("\n", $concatOutput), true);
			$this->removeTree($tempDir);
			return false;
		}

		if (!@rename($tempOutput, $output)) {
			if (!@copy($tempOutput, $output)) {
				$this->error_msg = "Preview final move failed: " . $output;
				$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
				$this->removeTree($tempDir);
				return false;
			}
			@unlink($tempOutput);
		}

		@chmod($output, 0666);
		$this->removeTree($tempDir);

		if (!is_file($output) || filesize($output) <= 0 || !$this->GetDuration($output)) {
			$this->error_msg = "Preview output validation failed: " . $output;
			$log = new Logger(__METHOD__ . ": " . $this->error_msg, true);
			return false;
		}

		$this->error_msg = false;
		return $output;
	}

	function convertVideo($video, $output, $param = 'normal', $max_bitrate = false)
	{
		echo $video . " to " . $output . "<br />";
		$temp = dirname($video) . "/" . getmypid() . "-" . time() . "-test.mp4";

		// изменение битрейта
		$bitrate_addition = "";
		$preset_additions = "-preset medium";
		if ($max_bitrate) {
			$max_bitrate = (int)$max_bitrate;
			if ($max_bitrate < 600) {
				$max_bitrate = 600;
				$log = new Logger(__METHOD__ . ": max_bitrate был ниже 400, установлен в 400 для файла '" . $video . "'->'" . $output . "'", true);
			}
			$current_bitrate = $this->GetBitrate($video);
			$log = new Logger(__METHOD__ . ": Текущий битрейт: " . $current_bitrate);
			if ($current_bitrate && $current_bitrate > $max_bitrate) {
				$bitrate_addition = "-b:v " . $max_bitrate . "k -minrate " . $max_bitrate . "k -maxrate " . $max_bitrate . "k -bufsize " . $max_bitrate . "k ";
				$preset_additions = "";
			}
		} else {
			$log = new Logger(__METHOD__ . ": max_bitrate == false ");
		}

		$ffmpegString = FFMPEG_PATH . " -i " . $video . " " . $bitrate_addition . "-acodec copy -threads 12 -ab 64k -ac 2 -vcodec libx264 " . $preset_additions . " -threads 0 -crf 22 -g 24 -r 24 " . $temp;
		$log = new Logger($ffmpegString, true);
		ob_start();
		passthru($ffmpegString);
		$duration = ob_get_contents();
		ob_end_clean();
		$log = new Logger($duration, true);
		print_r($duration);
		echo ($temp);
		if ($this->GetDuration($temp)) {
			$log = new Logger($temp . " duration OK, qt-faststart go", true);
			ob_start();
			$qtString = "qt-faststart " . $temp . " " . $output;
			$log = new Logger($qtString, true);
			passthru($qtString);
			$outError = ob_get_contents();
			ob_end_clean();
			$log = new Logger($outError, true);

			print_r($outError);
			if (is_file($output)) {
				unlink($temp);
				$this->errorFlag = "qt-faststart OK";
				return $output;
			} else {
				unlink($temp);
				$this->errorFlag = "qt-faststart error";
				$log = new Logger($this->errorFlag, true);
				return FALSE;
			}
		} else {
			if (is_file($temp)) unlink($temp);
			echo $temp . " не найден - ошибка ffmpeg";
			$log = new Logger($temp . " не найден - ошибка ffmpeg", true);
			$this->errorFlag = $duration;
			return FALSE;
		}
	}

	// ошибка с галерой 44181 - нет папки в uploads и файла -> косяк

	function GetDuration($videofile)
	{
		$output = false;
		$error_msg = false;
		if (is_file($videofile)) {
			$info_string = FFMPEG_PATH . " -i " . $videofile . " 2>&1";
			ob_start();
			passthru($info_string);
			$duration = ob_get_contents();
			ob_end_clean();
			if (
				preg_match('/Invalid data/', $duration)
				|| preg_match('/moov atom not found/', $duration)
				|| preg_match('/Operation not permitted/', $duration)
				|| preg_match('/No such file or directory/', $duration)
			) {
				$log = new Logger($duration, true);
				return false;
			}

			$search = '/Duration: (.*?),/';
			if (preg_match($search, $duration, $matches, PREG_OFFSET_CAPTURE, 3)) {
				$pointPos = strpos($matches[1][0], ".");
				$output = substr($matches[1][0], 0, $pointPos);
				$output = explode(":", $output);
				$output = ((int)$output[0]) * 3600 + ((int)$output[1]) * 60 + ((int)$output[2]);
				if ($output < 5) {
					$error_msg = __METHOD__ . ": output полученый от '" . $info_string . "', меньше 5.\n\t\tПолная строка: '" . $duration . "'";
					$output = false;
				}
			} else {
				$error_msg = __METHOD__ . ": Не найдена строка Duration. Вывод полученый строкой '" . $info_string . "':\n\t\t'" . $duration . "'";
			}
		} else {
			$error_msg = __METHOD__ . ": файл" . $videofile . " не найден";
		}
		if ($error_msg) $log = new Logger($error_msg, true);
		$this->duration = $output;
		return $output;
	}

	function videoDuration()
	{
		return $this->duration;
	}

	function GetSize($videofile)
	{
		$output = false;
		ob_start();
		passthru(FFMPEG_PATH . " -i \"{$videofile}\" 2>&1");
		$duration = ob_get_contents();
		ob_end_clean();
		if (preg_match('/Invalid data/', $duration)) return false;
		$search = '/([0-9]{3}x[0-9]{3})/';
		if ($duration = preg_match($search, $duration, $matches, PREG_OFFSET_CAPTURE, 3)) {
			$xPos = strpos($matches[1][0], "x");
			$X = substr($matches[1][0], 0, $xPos);
			$Y = substr($matches[1][0], $xPos + 1, strlen($matches[1][0]));
			$output = array($X, $Y);
		}
		return $output;
	}

	function GetBitrate($videofile)
	{
		$output = false;
		ob_start();
		passthru(FFMPEG_PATH . " -i \"{$videofile}\" 2>&1");
		$duration = ob_get_contents();
		ob_end_clean();
		if (preg_match('/Invalid data/', $duration)) return false;
		$search = '/bitrate:\s([0-9]{1,8})\skb\/s/';
		// var_dump($duration);
		if ($duration = preg_match($search, $duration, $matches, PREG_OFFSET_CAPTURE, 3)) {
			if (isset($matches[1][0]) && (int)$matches[1][0]) {
				$output = (int)$matches[1][0];
			} else {
				$log = new Logger(__METHOD__ . ": Не получилось получить битрейт файла '" . $videofile . "', match[1][0] fail!", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Не получилось получить битрейт файла '" . $videofile . "', preg_match fail!", true);
		}
		return $output;
	}

	function findScreenshots($folder)
	{
		$count = 0;
		if ($dh = opendir($folder)) {
			while (($file = readdir($dh)) !== false) {
				if ((filetype($folder . "/" . $file) == "file")
					&& (strpos($file, ".jpg", 1) || strpos($file, ".jpeg", 1) || strpos($file, ".JPG", 1) || strpos($file, ".JPEG", 1))
				) {
					$imagefiles[$count] = $folder . "/" . $file;
					$count++;
				}
			}
			if (isset($imagefiles)) return $imagefiles;
			else return false;
		} else return FALSE;
	}
}



function findScreenshots($folder)
{
	$count = 0;
	if ($dh = opendir($folder)) {
		while (($file = readdir($dh)) !== false) {
			if ((filetype($folder . "/" . $file) == "file")
				&& (strpos($file, ".jpg", 1) || strpos($file, ".jpeg", 1) || strpos($file, ".JPG", 1) || strpos($file, ".JPEG", 1))
			) {
				$imagefiles[$count] = $folder . "/" . $file;
				$count++;
			}
		}
		return $imagefiles;
	} else return FALSE;
}




//	$video = New VideoUtils ('upload');

//	$tempFilename = $video->GetFile('http://hot.buddyhosted.com/1/1C/1CE633C3H1/8/84/8405/e589698e0b/8405_02/01/8405_02_120sec_00.flv');
//	if ($tempFilename) $video->makeScreenshots($tempFilename); 
//	else echo $tempFilename;

//	$crop = New Cropper ();

//	$crop->CropThumb("/var/www/videos/d1b731ff43d37c813d2fdf1e323ef238/043.jpg", "-strip -filter Blackman -unsharp 1x0.4+1 -modulate 100,100,100", 240,180,90,0,10,0,0);	