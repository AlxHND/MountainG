<?php
class Grabber_new
{

	static $instanceCounter;
	static $instanceList;
	static $initialized;
	static $myPid;
	static $workTmpFolder;

	protected $Images; //ARRAY
	protected $GalleryURL; //VARCHAR
	protected $Count;   //INT
	private $errorCode;


	private $folderName;
	private $filesList;
	private $instanceId;

	public $contentType;


	//
	//	Создание и удаление папок для каждого отдельного pid, чтобы разделять разные версии запущеных скриптов, и чистить папки по деструкту корректно!!!!
	//


	function __construct()
	{
		$this->folderName = false;
		$this->filesList = array();
		$counter = ++self::$instanceCounter;
		$this->instanceId = md5(time() . $counter);
		if (isset(self::$instanceList)  && is_array(self::$instanceList)) {
			self::$instanceList[$this->instanceId] = false;
		} else {
			self::$myPid = getmypid() . time();
			$this->setMainFolder();
			self::$instanceList = array();
			self::$instanceList[$this->instanceId] = false;
		}
	}

	function __destruct()
	{
		$this->removeFiles();

		$folder = self::$instanceList[$this->instanceId];
		unset(self::$instanceList[$this->instanceId]);
		self::$instanceCounter--;
		if (self::$instanceCounter == 0 || count(self::$instanceList) == 0 || !in_array($folder, self::$instanceList)) {
			$this->removeDir();
			if (count(self::$instanceList) == 0) {
				unset($this->folderName);
				self::$instanceList = false;
				self::$myPid = false;
				self::$initialized = false;
			}
		}
	}


	private function removeDir()
	{
		if ($this->folderName && $this->folderName !== "") {
			$dirName = self::$workTmpFolder . "/" . $this->folderName;
			if (is_dir($dirName)) rmdir($dirName);
			if (count(self::$instanceList) == 0 && is_dir(self::$workTmpFolder)) rmdir(self::$workTmpFolder);
		}
	}

	private function removeFiles()
	{
		// var_dump($this->filesList);
		if (is_array($this->filesList) && count($this->filesList) > 0) {
			foreach ($this->filesList as $file) {
				if (is_file(self::$workTmpFolder . $file)) {

					// есть проблема с добавление видео всвязи с этим методом
					unlink(self::$workTmpFolder . $file);
				}
			}
		}
		unset($this->filesList);
		return true;
	}

	private function setMainFolder()
	{
		$result = false;
		if (!self::$initialized && self::$myPid) {
			$workTmpFolder = TMPDIR . "/" . self::$myPid;
			if (is_dir($workTmpFolder) || @mkdir($workTmpFolder, 0777)) {
				if (@chmod($workTmpFolder, 0777)) {
					$result = true;
					self::$workTmpFolder = $workTmpFolder;
					self::$initialized = true;
				} else {
					$log = new Logger(__METHOD__ . ": Cant chmod dir " . $workTmpFolder, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": Cant mkdir dir " . $workTmpFolder, true);
			}
		}
		return $result;
	}


	private function checkContentType($type = false)
	{
		$result = false;
		// var_dump($type);
		if ($type !== false) {
			if (
				$type == "application/x-gzip" ||
				$type == "application/zip" ||
				$type == "application/x-compressed" ||
				$type == "application/x-compress" ||
				$type == "multipart/x-zip" ||
				$type == "application/x-zip-compressed"
			) {
				$result = CONTENT_TYPE_ZIP;
			} elseif (preg_match('#image\/(gif)#im', $type)) {

				$result = CONTENT_TYPE_GIF;
			} elseif (preg_match('#image\/(jpeg|bmp)#im', $type)) {

				$result = CONTENT_TYPE_IMAGE;
			} elseif (
				preg_match('#video\/(avi|msvideo|x\-msvideo|x\-flv|mpeg|quicktime|mp4|x\-m4v|x\-ms\-wmv|x\-ms\-asf)#i', $type) ||
				preg_match('#flv\-application|application\/(octet\-stream|octet\-stream)#i', $type)
			) {
				$result = CONTENT_TYPE_VIDEO;
			} elseif (preg_match('#(text\/html|text\/x\-asm)#im', $type)) {
				$result = CONTENT_TYPE_HTML;
			} else $result = CONTENT_TYPE_ANY;
			$this->contentType = $result;
			// var_dump($result);
		}
		return $result;
	}

	private function ExcludeDoubles($array)
	{
		$count = 0;
		$output = array();

		foreach ($array as $element) {
			if (!in_array($element, $output)) {
				$output[$count] = $element;
				$count++;
			}
		}


		return $output;
	}

	private function getHtmlPage($url, $cookie = false)
	{

		$url 				= trim($url);
		$url 				= str_replace(" ", "%20", $url);
		$originalUrl 		= $url;
		$this->last_cookie 	= '';
		$cookie_jar 		= TMPDIR . "/cookie_jar.txt";
		$error_codes_array 	= array(400, 401, 403, 404, 405, 408, 500, 502, 503, 504, 505);
		$uagent 			= "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13";

		$ch = curl_init($originalUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
		curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
		curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
		curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
		curl_setopt($ch, CURLOPT_REFERER, $originalUrl);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);       // останавливаться после 4-ого редиректа
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);

		$content = curl_exec($ch);
		$curl_result = curl_getinfo($ch);
		$result_cookie = curl_getinfo($ch, CURLINFO_COOKIELIST);

		if (is_array($result_cookie) && is_array($result_cookie[0]) && isset($result_cookie[0][5], $result_cookie[0][6])) {
			$this->last_cookie = "{$result_cookie[0][5]}={$result_cookie[0][6]};";
		}


		$url = $curl_result['url'];

		if (in_array($curl_result['http_code'], $error_codes_array)) {
			$this->errorCode = $curl_result['http_code'];
			$result = false;
		}

		return $content;
	}

	//
	// Паблик методы
	//
	//

	public function prepareFolder($folderName = "")
	{
		//if ($this->folderName) $this->removeDir();

		$folderName = trim($folderName);
		$result = false;
		$dirName = "";

		if (self::$initialized) {

			if ($folderName != "" && preg_match("#[0-9a-z]{1,}#im", $folderName)) {
				$dirName = self::$workTmpFolder . "/" . $folderName;
				if (is_dir($dirName) || @mkdir($dirName, 0777)) {
					if (@chmod($dirName, 0777)) {
						$result = true;
						$this->folderName = $folderName;
						self::$instanceList[$this->instanceId] = $folderName;
					} else {
						$log = new Logger("Cant chmod dir " . $dirName, true);
						throw new Exception("Cant chmod dir " . $dirName);
					}
				} else {
					$log = new Logger("Cant chmod dir " . $dirName, true);
					throw new Exception("Cant mkdir dir " . $dirName);
				}
			}
		}

		return $result;
	}

	private function download_file($url, $file_name)
	{
		$result = false;
		$download_attempt = 0;
		$temp_file_name = self::$workTmpFolder . "/" . $this->folderName . "/" . getmypid() . ".tmp";

		//var_dump($file_name);
		do {
			$fs = fopen($url, "rb");

			if (!$fs) {
				$log = new Logger("FAILED to process {$url}, could not be downloaded (download_file)", true);
			} else {
				$fm = fopen($temp_file_name, "w");
				stream_set_timeout($fs, 30);

				while (!feof($fs)) {
					$contents = fread($fs, 4096); // Buffered download
					fwrite($fm, $contents);
					$info = stream_get_meta_data($fs);
					if ($info['timed_out']) {
						break;
					}
				}
				fclose($fm);
				fclose($fs);

				if (isset($info['timed_out']) && $info['timed_out']) {
					// Delete temp file if fails
					unlink($temp_file_name);
					$log = new Logger("FAILED on attempt " . $download_attempt . " - Connection timed out: ", $temp_file_name, true);
					$download_attempt++;
					if ($download_attempt < 5) {
						$log = new Logger("RETRYING: ", $temp_file_name, true);
					}
				} else {
					// Move temp file if succeeds
					//echo "Rename files";
					rename($temp_file_name, $file_name);
					$result = true;
					$log = new Logger("SUCCESS: " . $file_name,  true);
				}
			}
		} while ($download_attempt < 5 && (isset($info['timed_out']) && $info['timed_out']));

		return $result;
	}

	public function get_url_header_no_head($originalFile)
	{
		// fix unlimited redirects loop
		$result = false;
		preg_match('@^(?:http[s]{0,1}://)?([^/]+)(.*)@i', $originalFile, $matches);
		$hostname = $matches[1];
		$path = $matches[2];
		$line = "";
		$out = array();
		$url = $originalFile;
		//устанавливаем соединение, имя которого
		//передано в параметре $hostname
		$fd = fsockopen($hostname, 80, $errno, $errstr, 30);
		//проверяем успешность установки соединения
		if (!$fd) echo "$errstr ($errno)<br>/>\n";
		else {
			//формируем HTTP-запрос для передачи его серверу
			$headers = "GET $path HTTP/1.1\r\n";
			$headers .= "Host: $hostname\r\n";
			$headers .= "Connection: Close\r\n\r\n";
			//отправляем HTTP-запрос серверу
			fwrite($fd, $headers);
			$end = false;
			//получаем ответ
			while (!$end) {
				$line = fgets($fd, 1024);
				if (trim($line) == "") $end = true;
				else {
					// if(strpos("", $line))
					$out[] = $line;
				}
			}
			fclose($fd);
		}
		// var_dump($out[0]);
		preg_match("#^HTTP\/1\.1\s([0-9]+)\s(.*)$#im", $out[0], $matches);
		if ($matches && $matches[1] && $matches[2]) {
			$responce_code = $matches[1];
			$responce = trim($matches[2]);
			if ($responce_code == 302 || $responce_code == 301) {
				foreach ($out as $header_string) {
					if (preg_match("#^Location:\s(.*)$#im", $header_string, $matches)) {
						if ($matches[1]) {
							$url = trim($matches[1]);
							break;
						} else $url = false;
					}
				}
				if ($url) return $this->get_url_header_no_head($url);
			}
			$result['info']['http_code'] = $responce_code;
			$result['info']['url'] = $url;
			$result['header'] = $url;
			$result['info']['content_type'] = "";

			foreach ($out as $header_string) {
				if (preg_match("#^Content-Type:\s(.*)$#im", $header_string, $matches)) {
					$result['info']['content_type'] = trim($matches[1]);
				}
			}
		}

		return $result;
	}

	public function get_url_header($originalFile)
	{
		preg_match('@^(?:http[s]{0,1}://)?([^/]+)@i', $originalFile, $matches);
		$host = $matches[1];
		// while ($curlResult['http_code'] == 302 || $counter < 6) {
		if ($host == 'cdn.ct.sexhoundlinks.com') $host = 'sexhoundlinks.com';

		$ch = curl_init($originalFile);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
		curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8");  // useragent
		curl_setopt($ch, CURLOPT_REFERER, "http://" . $host . "/");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // таймаут соединения
		curl_setopt($ch, CURLOPT_TIMEOUT, 240);        // таймаут ответа
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);       // останавливаться после 4-ого редиректа
		if (($host == 'fhg.sexart.com' || $host == 'fhg.alsscan.com' || $host == 'fhg.hollyrandall.com'
			|| $host == 'fhg.vivthomas.com' || $host == 'fhg.errotica-archives.com' || $host == 'fhg.eroticbeauty.com'
			|| $host == 'fhg.thelifeerotic.com') && !strpos($originalFile, ".mp4")) {
			// костыльь для sexart - не возвращает значение страницы если не принимать body
			curl_setopt($ch, CURLOPT_NOBODY, 0);
		} else curl_setopt($ch, CURLOPT_NOBODY, 1);
		// curl_setopt($ch, CURLOPT_HTTPGET, true);

		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		$grabbedFile = curl_exec($ch);
		$curlResult = curl_getinfo($ch);
		$originalFile = $curlResult['url'];
		// }
		// var_dump(curl_getinfo($ch));
		curl_close($ch);
		// var_dump($curlResult);
		if ($curlResult['http_code'] == 403 && strstr($curlResult['url'], 'http://refer.ccbill.com/cgi-bin/clicks.cgi?') !== false) {
			echo "CCbill link:" . $curlResult['url'] . "<br>";
			// var_dump(strpos($curlResult['url'],"HTML="));
			if (strpos($curlResult['url'], 'HTML=')) {
				$originalFile = substr($curlResult['url'], strpos($curlResult['url'], 'HTML=') + 5, strlen($curlResult['url']));
			} else {
				$originalFile = substr($curlResult['url'], strpos($curlResult['url'], 'html=') + 5, strlen($curlResult['url']));
			}
			// echo "No CCbill link:".$originalFile."<br>";
			return $this->get_url_header($originalFile);
		}
		return array('header' => $grabbedFile, 'info' => $curlResult);
	}

	public function parsePornhubVideoInfo(string $url)
	{
		$content = $this->getHtmlPage($url);

		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		$html->loadHTML($content);
		$xpath = new DOMXPath($html);
		libxml_clear_errors();

		$result = array(
			'title' => '',
			'tags' => array(),
			'models' => array()
		);


		$query_string = '//title';
		$ht = $xpath->query($query_string);

		if ($ht && $ht->item(0)) {
			$result['title'] = $ht->item(0)->nodeValue;
		}


		$query_string = '//div[@class="pornstarsWrapper js-pornstarsWrapper"]';
		$ht = $xpath->query($query_string);

		if ($ht && $ht->item(0)) {
			$all_a = $ht->item(0)->getElementsByTagName('a');

			$pornstar_names = array();

			foreach ($all_a as $ht_single) {
				if ($ht_single->getAttribute("data-mxptext")) {
					$result['models'][] = $ht_single->getAttribute("data-mxptext");
				}
			}
		}



		$query_string = '//div[@class="categoriesWrapper"]';
		$ht = $xpath->query($query_string);
		if ($ht && $ht->item(0)) {
			$all_a = $ht->item(0)->getElementsByTagName('a');

			$tags = array();

			foreach ($all_a as $ht_single) {
				if ($ht_single->nodeValue && strpos($ht_single->nodeValue, 'Suggest') === false) {
					$result['tags'][] = trim(strtolower($ht_single->nodeValue));
				}
			}
		}

		$query_string = '//div[@class="tagsWrapper"]';
		$ht = $xpath->query($query_string);

		if ($ht && $ht->length > 0) {
			$all_a = $ht->item(0)->getElementsByTagName('a');

			foreach ($all_a as $ht_single) {
				if ($ht_single->nodeValue && strpos($ht_single->nodeValue, 'Suggest') === false &&  !in_array($ht_single->nodeValue, $tags)) {
					$result['tags'][] = trim(strtolower($ht_single->nodeValue));
				}
			}
		}

		return $result;
	}


	public function parseXvideosVideoInfo(string $url)
	{
		$content = $this->getHtmlPage($url);

		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		$html->loadHTML($content);
		$xpath = new DOMXPath($html);
		libxml_clear_errors();

		$result = array(
			'title' => '',
			'tags' => array(),
			'models' => array()
		);


		$query_string = '//title';
		$ht = $xpath->query($query_string);

		if ($ht && $ht->item(0)) {
			$result['title'] = $ht->item(0)->nodeValue;
		}

		$query_string = '//div[@class="video-metadata video-tags-list ordered-label-list cropped"]';
		$ht = $xpath->query($query_string);


		if ($ht && $ht->item(0)) {
			$all_a = $ht->item(0)->getElementsByTagName('a');

			$tags = array();

			foreach ($all_a as $ht_single) {
				if ($ht_single->nodeValue && strpos($ht_single->nodeValue, '+') === false) {
					$result['tags'][] = trim(strtolower($ht_single->nodeValue));
				}
			}
		}


		return $result;
	}


	private function youtubedl_file($url, $folder, $filename)
	{
		$string = ('cd ' . $folder . ' && /usr/local/bin/yt-dlp ' . escapeshellarg($url) . ' -o ' . $filename);

		$log = new Logger('Start: ' . $string);

		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin
			1 => array("pipe", "w"),  // stdout
			2 => array("pipe", "w"),  // stderr
		);
		$process = proc_open($string, $descriptorspec, $pipes);

		$log = new Logger('Finish: ' . $string);

		var_dump($string, $process);
		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		$ret = proc_close($process);

		var_dump(array(
			'status' => $ret,
			'errors' => $stderr,
			'url_orginal' => $url,
			'output' => $stdout,
			'command' => $string
		));
		return json_encode(array(
			'status' => $ret,
			'errors' => $stderr,
			'url_orginal' => $url,
			'output' => $stdout,
			'command' => $string
		));
	}

	public function fetchWithYoutubeDl($source_url)
	{
		if ($this->folderName === false) $this->folderName = $this->instanceId;

		$tempFolder = $this->folderName;

		$this->prepareFolder($tempFolder);
		$result = false;
		$source_url = str_replace(" ", "%20", $source_url);
		preg_match('@^(?:http[s]{0,1}://)?([^/]+)@i', $source_url, $matches);
		$host = $matches[1];
		preg_match('/[^.]+\.[^.]+$/', $host, $matches);
		$filename = md5(time()) . ".tmp";
		$folder = self::$workTmpFolder . "/" . $this->folderName . "/";
		$filePath = $folder . $filename;

		$log = new Logger(__METHOD__ . ": URL: " . $source_url . ". File: " . $filePath);

		if ($this->youtubedl_file($source_url, $folder, $filename)) {
			if (is_file($filePath)) {
				// заплатка на апачи отдающие на контент text\plain
				$finfo = new finfo(FILEINFO_MIME);
				$type = $finfo->file($filePath);
				$mime = substr($type, 0, strpos($type, ';'));

				$this->checkContentType($mime);

				$log = new Logger(__METHOD__ . ": Файл: " . $filePath . ". Тип контента в скачаном файле: " . $this->contentType . ", mime:" . $mime);
				@chmod($filePath, 0666);
				$result = "/" . self::$myPid . "/" . $this->folderName . "/" . $filename;
				$this->filesList[] = "/" . $this->folderName . "/" . $filename;
			}
		} else {
			echo "download_file in " . __METHOD__ . " error. Image grqab from: " . $source_url . " to file: " . $filePath . "\n";
			$log = new Logger("download_file in " . __METHOD__ . " error. Image grqab from: " . $source_url . " to file: " . $filePath, true);
		}

		return $result;
	}

	public function fetchFile($originalFile, $type = CONTENT_TYPE_ANY)
	{

		if ($this->folderName === false) $this->folderName = $this->instanceId;

		$tempFolder = $this->folderName;

		$this->prepareFolder($tempFolder);
		$result = false;
		$originalFile = str_replace(" ", "%20", $originalFile);
		preg_match('@^(?:http[s]{0,1}://)?([^/]+)@i', $originalFile, $matches);
		$host = $matches[1];
		preg_match('/[^.]+\.[^.]+$/', $host, $matches);
		$filename = md5(time()) . ".tmp";
		$filePath = self::$workTmpFolder . "/" . $this->folderName . "/" . $filename;

		$no_header_sites = array('fhg.sexart.com', 'fhg.alsscan.com', 'fhg.hollyrandall.com', 'fhg.vivthomas.com', 'fhg.errotica-archives.com', 'fhg.eroticbeauty.com', 'fhg.thelifeerotic.com');

		$header = (in_array($host, $no_header_sites)) ? $this->get_url_header_no_head($originalFile) : $this->get_url_header($originalFile);

		if (preg_match('#(URL=(http[s]{0,1}:\/\/(.*[^\n\r])))#im', $header['header'], $match) && isset($match[2])) {
			preg_match('@^(?:http[s]{0,1}://)?([^/]+)@i', $match[2], $matches);
			$host = $matches[1];
			$log = new Logger("Grabber: '" . $originalFile . "'' =>'" . $match[2] . "', Host:" . $host);
			$originalFile = $match[2];
			$header = $this->get_url_header($originalFile);
		}

		$curlResult = $header['info'];

		if (
			$curlResult['http_code'] == 404 ||
			$curlResult['http_code'] == 406 ||
			$curlResult['http_code'] == 500 ||
			$curlResult['http_code'] == 403
		) {
			$error = "Error in " . __METHOD__ . " , URL " . $curlResult['http_code'] . ". " . $originalFile;

			$log = new Logger($error, true);
			$errorFlag = true;

			return $result;
		}

		$this->checkContentType($curlResult['content_type']);
		$log = new Logger("Тип контента: " . $this->contentType);

		if (strpos($host, 'ah-me.com') !== false) return $result; // исключить из списка ah-me - не отдает нормально страницу

		if (($type === CONTENT_TYPE_ANY || $this->contentType || $curlResult['content_type'] == "text\/plain") && $curlResult['http_code'] == 200) {
			$originalFile = $curlResult['url'];
			if ($this->download_file($originalFile, $filePath)) {
				if (is_file($filePath)) {
					// заплатка на апачи отдающие на контент text\plain
					$finfo = new finfo(FILEINFO_MIME);
					$type = $finfo->file($filePath);
					$mime = substr($type, 0, strpos($type, ';'));

					$this->checkContentType($mime);
					if ($this->contentType == CONTENT_TYPE_GIF) {
						var_dump(LoadGif($filePath));
					}
					$log = new Logger(__METHOD__ . ": Файл: " . $filePath . ". Тип контента в скачаном файле: " . $this->contentType . ", mime:" . $mime);
					@chmod($filePath, 0777);
					$result = "/" . self::$myPid . "/" . $this->folderName . "/" . $filename;
					$this->filesList[] = "/" . $this->folderName . "/" . $filename;
				}
			} else {
				$log = new Logger("download_file in " . __METHOD__ . " error. Image grqab from: " . $originalFile . " to file: " . $filePath, true);
			}
		} else {
			$log = new Logger("Not type " . $type . " by curl in " . __METHOD__ . " error. url: " . $originalFile, true);
		}


		return $result;
	}

	public function Links()
	{
		return $this->Images;
	}

	public function Count()
	{
		return $this->Count;
	}

	public function ShowURL()
	{
		return $this->GalleryURL;
	}

	//
	//	Функция парсера	
	//

	public function FindVideos($GURL)
	{
		$count = 0;
		$result = false;
		$GURL = trim($GURL);
		$GURL = str_replace(" ", "%20", $GURL);
		$this->GalleryURL = $GURL;
		$result = $this->fetchFile($GURL);

		$cut_url = str_replace("http://", "", $this->GalleryURL);

		if ($qPosition =  strpos($cut_url, "?")) {
			$cut_url = substr($cut_url, 0, $qPosition);
		}

		$cut_url = explode("/", $cut_url);
		if ($cut_url[count($cut_url) - 1] == "") {
			unset($cut_url[count($cut_url) - 1]);
		}
		$dir_count = count($cut_url);

		// удаляется последний элемент массива, если в нем присутствует ? или . - признак файла
		if ((strstr($cut_url[$dir_count - 1], "?") !== FALSE) ||
			(strstr($cut_url[$dir_count - 1], ".") !== FALSE && (strstr($cut_url[$dir_count - 1], "htm") || strstr($cut_url[$dir_count - 1], "php") || strstr($cut_url[$dir_count - 1], "cfm")))
		) {
			$dir_count -= 1;
			unset($cut_url[$dir_count]);
		}

		$added_url = "";

		if ($result && $this->contentType == CONTENT_TYPE_HTML) {
			if (is_file(TMPDIR . $result)) {
				$file = fopen(TMPDIR . $result, 'r');
				$content = fread($file, filesize(TMPDIR . $result));
				if (preg_match_all("#url: (\'|\")?(http://(.*)\.(mp4|flv|wmv)?)(\'|\")?,#", $content, $matches) && isset($matches[2])) {
					$result = array();
					foreach ($matches[2] as $match) {
						$result[] = $match;
						$log = new Logger("Найдено видео: " . $match);
					}
					$result['video'] = true;
				} else {
					$result = array();
					if (preg_match_all("#<a.*?href[\s]*=[\s]*(\"|\')?(http://(.*)\.(mp4|flv|avi|wmv|m4p|mpg|mpeg))(\"|\')?[.*]{0,}>#", $content, $matches)) {
						foreach ($matches[2] as $match) {
							if (!in_array($match, $result))	$result[] = $match;
							$log = new Logger("Найдено видео: " . $match);
						}
						$result['video'] = true;
					} elseif (preg_match_all("#<a.*?href[\s]*=[\s]*(\"|\')?(../(.*)\.(mp4|flv|avi|wmv|m4p|mpg|mpeg))(\"|\')?[.*]{0,}>#", $content, $matches)) {
						$added_url = "http:/";
						for ($i = 0; $i < $dir_count - 1; $i++) {
							$added_url .= "/" . $cut_url[$i];
						}
						foreach ($matches[2] as $match) {
							if (!in_array($match, $result))	$result[] = $added_url . "/" . $match;
							$log = new Logger("Найдено видео: " . $match);
						}
						$result['video'] = true;
					} elseif (preg_match_all("#<a.*?href[\s]*=[\s]*(\"|\')?((.*)\.(mp4|flv|avi|wmv|m4p|mpg|mpeg))(\"|\')?[.*]{0,}>#", $content, $matches)) {
						$added_url = "http:/";
						for ($i = 0; $i < $dir_count; $i++) {
							$added_url .= "/" . $cut_url[$i];
						}
						foreach ($matches[2] as $match) {
							if (!in_array($match, $result))	$result[] = $added_url . "/" . $match;
							$log = new Logger("Найдено видео: " . $match);
						}
						$result['video'] = true;
					}
				}
			}
		}
		return $result;
	}

	public function FindImages($GURL)
	{ // Парсинг ссылок на изображения
		$count = 0;

		$GURL = trim($GURL);
		$GURL = str_replace(" ", "%20", $GURL);
		if (preg_match('#^https?:\/\/(www.)?seancody.com#', $GURL)) {
			$GURL = str_replace("page.php?", "?", $GURL);
			//echo $GURL ."<br>";
		}
		$this->GalleryURL = $GURL;

		$uagent = "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13";
		$ch = curl_init($this->GalleryURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
		curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
		curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
		curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
		curl_setopt($ch, CURLOPT_REFERER, $GURL);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
		$content = curl_exec($ch);

		$curlResult = curl_getinfo($ch);

		// print_r($curlResult);

		$this->GalleryURL = $curlResult['url'];
		$GURL = $curlResult['url'];

		if (
			$curlResult['http_code'] == 404 ||
			$curlResult['http_code'] == 500 ||
			$curlResult['http_code'] == 403
		) {
			$log = new Logger("Error in " . __METHOD__ . " , URL " . $curlResult['http_code'] . ". " . $this->url, true);
			throw new Exception("Error in " . __METHOD__ . " , URL " . $curlResult['http_code'] . ". " . $this->url);
		}

		/*		if ($curlResult ['content_type'] == "application/x-gzip" ||
			$curlResult ['content_type'] == "application/zip" ||
			$curlResult ['content_type'] == "application/x-compressed" ||
			$curlResult ['content_type'] == "application/x-compress") {

			
		} else throw new Exception ("Not zip by curl in ".__METHOD__." error. url: ".$this->url);

*/

		$cut_url = str_replace("http://", "", $this->GalleryURL);

		if ($qPosition =  strpos($cut_url, "?")) {
			$cut_url = substr($cut_url, 0, $qPosition);
		}

		$cut_url = explode("/", $cut_url);
		if ($cut_url[count($cut_url) - 1] == "") {
			unset($cut_url[count($cut_url) - 1]);
		}
		$dir_count = count($cut_url);

		// удаляется последний элемент массива, если в нем присутствует ? или . - признак файла
		if ((strstr($cut_url[$dir_count - 1], "?") !== FALSE) ||
			(strstr($cut_url[$dir_count - 1], ".") !== FALSE && (strstr($cut_url[$dir_count - 1], "htm") || strstr($cut_url[$dir_count - 1], "php") || strstr($cut_url[$dir_count - 1], "cfm")))
		) {
			$dir_count -= 1;
			unset($cut_url[$dir_count]);
		}

		$header  = curl_getinfo($ch);
		$gallery_domain = $header["url"];
		$added_url = "";

		// парсинг x-art.com
		if (strstr($gallery_domain, 'xhamster.com')) {
			$html = new DOMDocument();
			$html->loadHTML($content);
			$xpath = new DOMXPath($html);
			$query_string = '//div[@class="gallery iItem"]/a';
			$ht = $xpath->query($query_string);
			foreach ($ht as $ht_single) {
				var_dump($ht->item(0)->nodeValue);
			}
		} elseif (strstr($gallery_domain, 'x-art.com')) {
			$added_url = "http://";
			for ($i = 0; $i < count($cut_url); $i++) {
				$added_url .= $cut_url[$i] . "/";
			}

			$count = preg_match_all("#view_image.php\?(.*?)\"#im", $content, $matches);

			foreach ($matches[1] as $imageLink) {
				$imagePage[] = $added_url . "view_image.php?" . $imageLink;
			}

			foreach ($imagePage as $imageLink) {

				$uagent = "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13";
				$ch = curl_init($imageLink);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
				curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
				curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
				curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
				curl_setopt($ch, CURLOPT_REFERER, $GURL);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
				curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
				curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
				$content = curl_exec($ch);

				$info = curl_getinfo($ch);

				if ($count = preg_match_all("#src=\"(.*?\.jpg)\"#im", $content, $matches)) {
					if (isset($matches[1][0])) $imageFinal[] = $added_url . $matches[1][0];
				}
			}
			if (isset($imageFinal)) {
				$this->Images = $this->ExcludeDoubles($imageFinal);
				$this->Count = count($this->Images);
				curl_close($ch);
				$result = true;
			} else {
				curl_close($ch);
				$this->Count = 0;
				$this->Images = false;
				$result = false;
			}

			return $result;
		} else {
			$count = preg_match_all(
				"#<a.*?href[\s]*=[\s]*[\"\'\s]{0,}([^\"^\'^\>]*?\.jpe?g)[\"\'\s]{0,}.*?>#im",
				$content,
				$matches
			); // Регулярка поиска .jpg не доделана, парсит только правильные ссылки вида <a href="....">
		}

		if ($count == FALSE) {

			// парсит SeanCody
			$count = preg_match_all("#class\=\"movie\-photos\-thumbnail\".*?src=\s*[\"](.*?)[\"]#im", $content, $matches);
			$count1 = preg_match_all("#class\=\"movie\-stills\-thumbnail\".*?src\=\s*[\"](.*?)[\"]#im", $content, $matches1);

			if (($count !== FALSE && $count !== 0) || ($count1 !== FALSE && $count1 !== 0)) {
				$header = curl_getinfo($ch);
				$url = parse_url($header["url"]);
				$url = "http://" . $url['host'];

				if ($count !== FALSE && $count !== 0) {
					foreach ($matches[1] as $bigImage) {
						if (strstr($bigImage, "http:")) $image_url[] = trim(str_replace("thumbs/", "", $bigImage));
						else $image_url[] = $url . "/" . trim(str_replace("thumbs/", "", $bigImage));
					}
				}

				if ($count1 !== FALSE && $count1 !== 0) {
					foreach ($matches1[1] as $bigImage) {
						if (strstr($bigImage, "http:")) $image_url[] = trim(str_replace("thumbs/", "", $bigImage));
						else $image_url[] = $url . "/" . trim(str_replace("thumbs/", "", $bigImage));
					}
				}
				curl_close($ch);
				$this->Images = $this->ExcludeDoubles($image_url);
				$this->Count = count($this->Images);
				return TRUE;
			}
			curl_close($ch);
			$this->Count = 0;
			$this->Images = FALSE;
			return FALSE;
		}

		$basecount = preg_match("#<base.*?href=[\'\"]([^\"^\']*?)[\'\"].*?>#im", $content, $basematch);

		$images_count = 0;
		$added_url = array();

		foreach ($matches[1] as $image) {
			$image = str_replace(" ", "%20", $image);
			if ($basecount && !strstr($image, "http://")) {
				$image_url[$images_count] = $basematch[$basecount] . $image;
				$images_count++;
			} elseif (strstr($image, "http://") !== false || strstr($image, "https://") !== false) {

				$image_url[$images_count] = $image;
				$images_count++;
			} elseif (strstr($image, "../") !== FALSE) {
				$subfolder_count = substr_count($image, "../");
				$image = str_replace("../", "", $image);
				$added_url[$images_count] = "http:/";
				for ($i = 0; $i < ($dir_count - $subfolder_count); $i++) {
					$added_url[$images_count] .= "/" . $cut_url[$i];
				}
				$image_url[$images_count] = $added_url[$images_count] . "/" . htmlspecialchars($image);
				$images_count++;
			} elseif (strpos($image, "/") !== 0) {
				$added_url[$images_count] = $cut_url[0];
				for (
					$i = 1;
					$i < $dir_count;
					$i++
				) {
					$added_url[$images_count] = $added_url[$images_count] . "/" . $cut_url[$i];
				}
				$image_url[$images_count] = $added_url[$images_count] . "/" . htmlspecialchars($image);
				$images_count++;
			} elseif (strpos($image, "/") == 0) {
				$image_url[$images_count] = "http://" . $cut_url[0] . $image;
				$images_count++;
			} else {
				curl_close($ch);
				$this->Images = FALSE;
				$this->Count = 0;
				return FALSE;
			}
		}
		curl_close($ch);
		$this->Images = $this->ExcludeDoubles($image_url);
		$this->Count = count($this->Images);
		// var_dump($this->Images);
		return true;
	}

	function unzipGallery(int $id, ?array &$metadataOut = null)
	{
		$result = false;

		$zip_fileName = TMPDIR . "/" . $id . ".zip";

		// var_dump($zip_fileName, is_file($zip_fileName), __DIR__);

		if ($id && is_file($zip_fileName)) {
			if ($this->folderName === false) $this->folderName = $this->instanceId;
			$tempFolder = $this->folderName;
			$this->prepareFolder($tempFolder);
			$filePath = self::$workTmpFolder . "/" . $this->folderName;
			$videoFiles = array();
			$entryFileName = false;
			$zip = new ZipArchive();


			$res = $zip->open($zip_fileName);

			if ($res === true) {
				$count = 0;
				$log = new Logger("ZIP_TEXT: scan start GID#".$id.", zip: ".$zip_fileName.", entries: ".$zip->numFiles);

				$metaTitle = null;
				$metaDescription = null;
				$metaModels = null;
				$metaDirectors = null;
				$textFiles = array();
				$primaryText = null;
				$zipEntriesDebug = array();

				for ($i = 0; $i < $zip->numFiles; $i++) {
					$entry = $zip->statIndex($i);

					$name = $entry['name'];

					$ext = strtolower(trim(pathinfo($name, PATHINFO_EXTENSION)));
					$zipEntriesDebug[] = "#".$i." name='".$name."' ext='".$ext."' size=".(int)$entry['size'];

					if (in_array($ext, ['txt', 'doc', 'rtf'], true)) {

						$blob = $zip->getFromIndex($i);
						$cleanName = str_replace("\\", "/", $name);
						$cleanName = str_replace(array("\r", "\n", "|"), " ", $cleanName);
						$textFiles[] = array(
							'type' => $ext,
							'name' => $cleanName
						);
						$log = new Logger("ZIP_TEXT: found GID#".$id.", index: ".$i.", type: ".$ext.", name: ".$cleanName.", size: ".(int)$entry['size']);

						if ($blob !== false) {
							if ($ext === 'txt') {
								// нормализация и парсинг Title / Description / Models
								$txtNorm = $this->normalizeZipText($blob);
								if ($primaryText === null) {
									$primaryText = $txtNorm;
									$log = new Logger("ZIP_TEXT: primary txt selected GID#".$id.", name: ".$cleanName.", text length: ".strlen($txtNorm));
								}

								// if ($metaTitle === null && preg_match('~^Title:\s*(.+)$~miu', $txtNorm, $m)) {
								// $metaTitle = trim($m[1]);
								// }

								// if ($metaDescription === null && preg_match('~^Description:\s*(.+)$~miu', $txtNorm, $m)) {
								// $metaDescription = trim($m[1]);
								// }
								// if ($metaModels === null && preg_match('~^Models?:\s*(.+)$~miu', $txtNorm, $m)) {
								// $metaModels = trim($m[1]);
								// }

								if (
									$metaTitle === null
									&& preg_match('~^(?:Title:?\s*(.+)|TITLE\s*(?:\R+(.+))|VIDEO TITLE:?\s*(.+))$~miu', $txtNorm, $m)
								) {
									if (!empty($m[1])) {
										$metaTitle = trim($m[1]); // вариант с Title: на той же строке
									} elseif (!empty($m[2])) {
										$metaTitle = trim($m[2]); // вариант с TITLE или Title: и перенос
									} elseif (!empty($m[3])) {
										$metaTitle = trim($m[3]); // вариант с TITLE или Title: и перенос
									}
								}

								if (
									$metaDescription === null
									&& preg_match('~^(?:Description:?\s*(.+)|DESCRIPTION\s*(?:\R+(.+)))$~miu', $txtNorm, $m)
								) {
									if (!empty($m[1])) {
										$metaDescription = trim($m[1]); // вариант с Title: на той же строке
									} elseif (!empty($m[2])) {
										$metaDescription = trim($m[2]); // вариант с TITLE или Title: и перенос
									}
								}

								if (
									$metaModels === null
									&& preg_match('~^(?:Models:?\s*(.+)|MODELS\s*(?:\R+(.+)))$~miu', $txtNorm, $m)
								) {
									if (!empty($m[1])) {
										$metaModels = trim($m[1]); // вариант с Title: на той же строке
									} elseif (!empty($m[2])) {
										$metaModels = trim($m[2]); // вариант с TITLE или Title: и перенос
									}
								}

								if (
									$metaModels === null
									&& preg_match('~^STARS\s*(.+)$~miu', $txtNorm, $m)
								) {
									$metaModels = trim($m[1]);
								}

								if (
									$metaModels === null
									&& preg_match('~^Starring:\s*(.+)$~miu', $txtNorm, $m)
								) {
									$metaModels = trim($m[1]);
								}

								if (
									$metaDirectors === null
									&& preg_match('~^DIRECTORS\s*(.+)$~miu', $txtNorm, $m)
								) {
									$metaDirectors = trim($m[1]);
								}
							}
						} else {
							$log = new Logger("ZIP_TEXT: getFromIndex failed GID#".$id.", index: ".$i.", name: ".$cleanName, true);
						}
					} elseif (($entry['size'] > 100000 && preg_match('#\.(flv|wmv|mp4|avi|mov)$#i', $entry['name'])) ||
						($entry['size'] > 15000 && preg_match('#\.(jpg|jpeg)$#i', $entry['name']))
					) {

						$isImage = false;
						if ($count < 9) $filepath = "0" . ($count + 1);
						else $filepath = $count + 1;
						if (preg_match(
							'#\.(jpg|jpeg)$#i',
							$entry['name']
						)) {
							$filename = $filepath . ".jpg";
							$isImage = true;
						} else {
							$entryFileName = substr($entry['name'], 0, strlen($entry['name']) - 3);
							if (!in_array(
								$entryFileName,
								$videoFiles
							)) $videoFiles[] = $entryFileName;
							else {
								$log = new Logger(__CLASS__ . "->" . __METHOD__ . ":
                    дублирование видео файла по называнию. " . $entryFileName . " уже присутствует в массиве", true);
								continue;
							}
							$filename = $filepath . ".tmp";
						}
						if ($file = $zip->getFromIndex($i)) {
							if (file_put_contents($filePath . "/" . $filename, $file)) {
								$count++;
								// файл добавляется в список файлов для очистки
								$this->filesList[] = "/" . $this->folderName . "/" . $filename;
								if ($isImage) {
									$checkfile['image'][$count] = self::$workTmpFolder . "/" . $this->folderName . "/" . $filename;
									$imageinfo = getimagesize($filePath . "/" . $filename);
									if ($imageinfo[0] < 400 || $imageinfo[1] < 400) {
										unset($checkfile['image'][$count]);
										$count--;
									}
								} else $checkfile['video'][$count] = self::$workTmpFolder . "/" . $this->folderName . "/" . $filename;
							}
						}
					}
				}
				$log = new Logger("ZIP_TEXT: entries GID#".$id.": ".implode("; ", $zipEntriesDebug));
				if (isset($checkfile)) {
					$log = new Logger("ZIP В файле " . $zip_fileName . " найден контент, ОК");
					$result = $checkfile;
				} else $log = new Logger("ZIP В файле " . $zip_fileName . " не найден контент");

				if ($textFiles) {
					$log = new Logger("ZIP_TEXT: text files summary GID#".$id.", count: ".count($textFiles).", primary length: ".strlen((string)$primaryText));
					$this->saveGalleryZipText($id, $textFiles, $primaryText);
				} else {
					$log = new Logger("ZIP_TEXT: no txt/doc/rtf files found GID#".$id.", zip: ".$zip_fileName);
				}

				if ($metadataOut !== null) {
					$combined = '';

					$combinedArray = [];

					if ($metaDirectors) {
						$combinedArray[] = "Directed by: {$metaDirectors}";
					}

					if ($metaModels) {
						$combinedArray[] = "Featuring: {$metaModels}";
					}



					if ($metaDescription) {
						$combinedArray[] = $metaDescription;
					}

					$combined = implode(', ', $combinedArray);

					$metadataOut = [
						'title' => $metaTitle ?? '',
						'description' => $metaDescription ?? '',
						'models' => $metaModels ?? '',
						'combined-desc' => $combined,
					];
				}
				$zip->close();
			} else {
				$log = new Logger("Ошибка! Не открывается ZIP Grabber_new->unzipGallery, " . $zip_fileName, true);
			}
		}
		return $result;
	}

	private function normalizeZipText($text)
	{
		$enc = @mb_detect_encoding($text, array('UTF-8', 'Windows-1251', 'ISO-8859-1', 'ASCII'), true);
		if ($enc && $enc !== 'UTF-8') {
			$text = @mb_convert_encoding($text, 'UTF-8', $enc);
		}
		$text = str_replace(array("\r\n", "\r"), "\n", $text);
		return trim($text);
	}

	private function saveGalleryZipText($galId, array $textFiles, $primaryText = null)
	{
		$result = false;
		$galId = (int)$galId;
		if ($galId <= 0) {
			$log = new Logger(__METHOD__.": ZIP_TEXT save skipped, wrong galId. GID#".$galId, true);
			return false;
		}

		$storage = $this->galleryTextStoragePath();
		$parent = dirname($storage);
		$log = new Logger(__METHOD__.": ZIP_TEXT save start GID#".$galId.", storage: ".$storage.", exists: ".(is_dir($storage) ? 'yes' : 'no').", parent: ".$parent.", parent writable: ".(is_writable($parent) ? 'yes' : 'no'));
		if (!is_dir($storage) && !mkdir($storage, 0777, true)) {
			$log = new Logger(__METHOD__.": Не могу создать папку текстов ".$storage, true);
			return false;
		}
		if (!is_writable($storage)) {
			$log = new Logger(__METHOD__.": ZIP_TEXT storage is not writable ".$storage, true);
			return false;
		}

		$headerParts = array('====ZIP_TEXT_FILES');
		foreach ($textFiles as $file) {
			$type = isset($file['type']) ? $file['type'] : 'file';
			$name = isset($file['name']) ? $file['name'] : '';
			$headerParts[] = $type . ':' . $name;
		}
		$header = implode('|', $headerParts) . "====";
		$body = ($primaryText !== null && trim($primaryText) !== '') ? trim($primaryText) : '';
		$content = $header . "\n" . $body . "\n";
		$filePath = $storage . "/" . $galId . ".txt";

		if (file_put_contents($filePath, $content) !== false) {
			@chmod($filePath, 0666);
			$log = new Logger(__METHOD__.": ZIP_TEXT saved GID#".$galId.", file: ".$filePath.", bytes: ".filesize($filePath).", text files: ".count($textFiles));
			$result = true;
		} else {
			$log = new Logger(__METHOD__.": Не могу сохранить текст ZIP для GID#".$galId." в ".$filePath, true);
		}

		return $result;
	}

	private function galleryTextStoragePath()
	{
		if (defined('GALLERY_TEXT_STORAGE')) {
			return rtrim(GALLERY_TEXT_STORAGE, '/');
		}
		if (defined('WRKDIR')) {
			return rtrim(WRKDIR, '/') . "/storage/gallery_texts";
		}
		return dirname(__DIR__) . "/storage/gallery_texts";
	}
}
