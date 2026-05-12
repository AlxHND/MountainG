<?php
class FileUtils
{
	public $errorFlag;
	protected $workFolder;
	protected $fileName;

	function __construct ($tempPath) {
		$this->workFolder = $tempPath;
	}

	public function GetFile ($url) {
		$videoFolder = md5($url);
		$this->fileName = $videoFolder;

		$tempFolder = getcwd() ."/".$this->workFolder."/".$videoFolder."/";
		$tempFilename = $tempFolder . $videoFolder .".tmp";

		if (!is_dir($tempFolder)) {
			if (mkdir($tempFolder)) {
				if (chmod ($tempFolder, 0777)) {
					$this->errorMsg = "chmod error. folder: ".$tempFolder;
					return FALSE;
				}
			} else {
				$this->errorMsg = "mkdir error. folder: ".$tempFolder;
				return FALSE;
			}
		}
	
		preg_match('@^(?:http://)?([^/]+)@i',$url, $matches);
		$host = $matches[1];
		
		preg_match('/[^.]+\.[^.]+$/', $host, $matches);
		$header = "";
		
		$filename = substr($url,strripos($url,$host) + strlen($host), strlen($url));
		
		if ($sock = fsockopen($host, 80, $errno, $errstr)) {
	
			$sntHeader = "GET ".$filename." HTTP/1.1\r\nHost: ".$host."\r\nConnection: Close\r\n\r\n";
			fwrite($sock, $sntHeader); 
			
			set_time_limit(180);
	
			do {
				$header .= fgets ( $sock, 128 );
			} while ( strpos ( $header, "\r\n\r\n" ) === false );

			if (strpos($header, "200 OK")) {
				if ($fw = fopen($tempFilename, "w")) {
					if (chmod ($tempFilename, 0777)) {
						while (!feof($sock)) {
							$getfile = fgets($sock, 128);
							fputs($fw, $getfile);
						}
						fclose($sock);
						fclose($fw);
					} else {
						$this->errorMsg = "file chmod: ".$tempFolder;
						return FALSE;
					}
				} else {
					$this->errorMsg = "file open error. folder: ".$tempFolder;
					return FALSE;
				}
			} else {
				$this->errorMsg = "download error (not 200 OK). folder: ".$tempFolder;
				return FALSE;
				}
		} else {
			$this->errorMsg = "socket error. folder: ".$tempFolder;
			return FALSE;
		}
		return $tempFilename;
	}
}

class VideoUtils extends FileUtils
{
	function __construct () {
	}

	function makeScreenshots ($video) {
		$workingPath = getcwd ();
		$tempPath = $workingPath . "/".$this->workFolder."/" . $this->fileName;

		if (!is_dir($tempPath)) {
			mkdir($tempPath);
			chmod ($tempPath, 0777);
		}
		$ffmpegString = "ffmpeg -i ".$video." -r 1 -ss 1 ".$tempPath."/%03d.jpg";

		exec ($ffmpegString);
		return $tempPath;
	}

	function convertVideo ($video, $output, $param = 'normal') {
		$output = $output."/".$this->fileName.".mp4";
		$ffmpegString = "ffmpeg -i ".$video." -acodec libmp3lame -ab 96k -ac 2 -vcodec libx264 -vpre ".$param." -vpre ipod320 -threads 0 -crf 22 ".$output;
		exec($ffmpegString);
		return $output;
	}
}

	function findJpgs ($folder)
	{
		$count = 0;
		if ($dh = opendir($folder)){
			while (($file = readdir($dh)) !== false) {
				if ((filetype($folder. "/" .$file) == "file")
				&& (strpos($file, ".jpg",1)||strpos($file, ".jpeg",1)||strpos($file, ".JPG",1)||strpos($file, ".JPEG",1))) {
					$imagefiles[$count] = $folder. "/" .$file;
					$count++;
				}
			} return $imagefiles;
		} else return FALSE;
	}

	function showJpgs ($images) {
		sort($images);
		$needle = "/var/www";
		foreach ($images as $image) {
			$thumbPath = str_replace ($needle, "", $image);
			$fname = basename($image);
?>
			<div style='height=200px; float: left; margin:5px' id='<?=$fname?>' onClick='SelectImage(this.id);'>
				<img src=<?=$thumbPath?> height='180px;' /><br />
				<div align='right' >Upload: <input type='checkbox' checked='true' name='<?=$fname?>' id=thumb value=<?=$fname?> /></div>
			</div>
<?php
		}
	}
?>