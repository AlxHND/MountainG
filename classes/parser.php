<?php
// Класс парсера
// Методы:
// FindImages - парсинг изображений 
// Links - Возвращает все ссылки на картинки
// Count - Возвращает количеcтво картинок с урла


class Parser {
	protected $Images, $GalleryURL, $Count;

	public $galTitle = "";


	public function Links() {
		return $this->Images;
	}

	public function Count() {
		return $this->Count;
	}

	public function ShowURL() {
		return $this->GalleryURL;
	}


	private function ExcludeDoubles ($array) {
		$output = array();

		foreach ($array as $element) {
			if (!in_array($element, $output)) {
				$output[] = $element;
			}
		}
		
		return $output;
	}


	function getImageLinks($content, string $queryString = '//a', string $imageAttribute = 'href') {
		$result = false;
		// $content = getPage($url);
		echo "getImageLinks\n\n";
		if(!$content) {
			return false;
		}
		
		libxml_use_internal_errors(true);

		$html = new DOMDocument();
		$html->loadHTML($content);

		$xpath = new DOMXPath($html);

		$query_string = $queryString;
		$ht = $xpath->query( $query_string );

		foreach($ht as $value) {

				$hrefs = $value->getAttribute($imageAttribute);

				if(preg_match("#^([^\"^\'^\>]*?\.jpe?g[\?]?(.*))$#im",$hrefs)) {
					$result[] = $hrefs;	
				}


		}
		libxml_clear_errors();

		return $result;
	}

	private function fetchPage()
	{

		/**
		 * TODO разнести все по методам переделать парсинг с нуля
		 */
	}

	public function FindImages($GURL) { // Парсинг ссылок на изображения
		$count = 0;

		$GURL = trim($GURL);
		$GURL = str_replace (" ","%20", $GURL);

		if (preg_match('#^https?:\/\/(www.)?seancody.com#', $GURL)) {
			$GURL = str_replace("page.php?", "?", $GURL);
			echo $GURL ."<br>";
		}
		$this->GalleryURL = $GURL;

		$uagent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17";

		$ch = curl_init( $this->GalleryURL );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
		curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
		curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
		curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
		curl_setopt($ch, CURLOPT_VERBOSE, true);

		// $verbose = fopen('/home/web1/xratedtravels.com/htdocs/aswerfghyu6ew4/logs/.curl_x', 'w+');
		// curl_setopt($ch, CURLOPT_STDERR, $verbose);

		$content = curl_exec( $ch );

		libxml_use_internal_errors(true);
		$html = new DOMDocument();
		$html->loadHTML ($content);
		$xpath = new DOMXPath( $html );
		libxml_clear_errors();

		$query_string = '//title';
		$ht = $xpath->query($query_string);
		
		if($ht && $ht->item(0)) {
			$this->galTitle = $ht->item(0)->nodeValue;
		}

		var_dump($this->galTitle);

		$query_string = "";
		$html = null;
		$xpath = null;
		$ht = null;

		$info = curl_getinfo ($ch);

		$this->GalleryURL = $info['url'];
		$GURL = $info['url'];
		
		// echo $GURL . "<br />";
		// echo "Curl info\n";
		// var_dump($info,$content);

	
		$http_s = (strpos($this->GalleryURL, "https") !== false) ? "https" : "http";

		$cut_url = str_replace($http_s."://","",$this->GalleryURL);


		if ($qPosition =  strpos($cut_url,"?")) {
			$cut_url = substr ($cut_url, 0, $qPosition);
		}

		$cut_url = explode ("/",$cut_url);

		if ($cut_url[count($cut_url)-1] == "") {
			unset ($cut_url[count ($cut_url)-1]);
		}

		$dir_count = count($cut_url);	

//		print_r($cut_url);
	
		// удаляется последний элемент массива, если в нем присутствует ? или . - признак файла
		if 	((strstr($cut_url[$dir_count - 1],"?") !== FALSE) || 
			(strstr($cut_url[$dir_count - 1],".") !== FALSE && (strstr($cut_url[$dir_count - 1],"htm") || strstr ($cut_url[$dir_count - 1],"php") || strstr ($cut_url[$dir_count - 1],"cfm")))) {
			$dir_count -= 1;
			unset ($cut_url[$dir_count]);
		}
	
		$header  = curl_getinfo( $ch );
		$gallery_domain = $header ["url"];
		$added_url = "";

		if(strstr($gallery_domain, 'xhamster.com')) {
			libxml_use_internal_errors(true);
			$html = new DOMDocument();
			$html->loadHTML ($content);
			$xpath = new DOMXPath( $html );
			libxml_clear_errors();

			$query_string = '//div[@class="gallery iItem "]/a';
			$ht = $xpath->query( $query_string );
			foreach ($ht as $ht_single) {
				$picture_html_url = $ht_single->getAttribute("href");
				if($picture_html_url) {
					$uagent = "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13";
					$ch = curl_init( $picture_html_url );
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
					curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
					curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
					curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
					curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
					curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
					$pics_content = curl_exec( $ch ); 

					$info = curl_getinfo ($ch);
					
					$html = new DOMDocument();
					$html->loadHTML ( $pics_content );
					$xpath = new DOMXPath( $html );

					$query_string = ".//img[@class='slideImg']";
					$pics_ht = $xpath->query( $query_string );
					// var_dump($pics_ht, $pics_ht->item(0), $pics_ht->item(0)->getAttribute("src"));
					if($pics_ht && $pics_ht->item(0)) {
						$temp_picture_url = $pics_ht->item(0)->getAttribute("src");
						if(strpos($temp_picture_url, ".jpg")) {
							$imageFinal[] =$temp_picture_url;
						}
					}
				}
				// break;
			}

			// var_dump($imageFinal);

			if (isset($imageFinal)) {
				$this->Images = $this->ExcludeDoubles($imageFinal);
				$this->Count = count($this->Images);
				curl_close( $ch );
				return TRUE;
			}
			curl_close( $ch );
			$this->Count = 0;
			$this->Images = FALSE;
			return FALSE;

		} elseif (strstr($gallery_domain, 'x-art.com') || strstr($gallery_domain, 'colette.com')) {
			$added_url = "http://";
			for ($i=0; $i < count($cut_url); $i++) {
				$added_url .= $cut_url[$i]."/";
			}

			$count = preg_match_all("#view_image.php\?(.*?)\"#im", $content, $matches);

			foreach($matches[1] as $imageLink) {
				$imagePage[] = $added_url ."view_image.php?" . $imageLink;
			}

            $uagent = "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13";
			foreach ($imagePage as $imageLink) {

				$ch = curl_init( $imageLink );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
				curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
				curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
				curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
				curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
				curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
				$content = curl_exec( $ch ); 

				$info = curl_getinfo ($ch);
				
				if($count = preg_match_all("#src=\"(.*?\.jpg)\"#im", $content, $matches)) {
					if (isset($matches[1][0])) $imageFinal[] = $added_url . $matches[1][0];
				}

			}
			if (isset($imageFinal)) {
				$this->Images = $this->ExcludeDoubles($imageFinal);
				$this->Count = count($this->Images);
				curl_close( $ch );
				return TRUE;
			}
			curl_close( $ch );
			$this->Count = 0;
			$this->Images = FALSE;
			return FALSE;
					

		} elseif (strstr($gallery_domain, 'alternadudes')) {
			$added_url = "http://";
			for ($i=0; $i < count($cut_url); $i++) {
				$added_url .= $cut_url[$i]."/";
			}

			$count = preg_match_all("#pictures\/([0-9].*?)\.html#im", $content, $matches);

			if (!$count) return FALSE;

			foreach($matches[1] as $imageLink) {
				$imageFinal[] = $added_url ."pictures/images/" . $imageLink . ".jpg";
			}

			//var_dump($imageFinal);
			if(isset($imageFinal)) {
				$this->Images = $this->ExcludeDoubles($imageFinal);
				$this->Count = count($this->Images);
				curl_close( $ch );
				return TRUE;
			}
			curl_close( $ch );
			$this->Count = 0;
			$this->Images = FALSE;
			return FALSE;			
		} elseif (strstr($gallery_domain, 'seancody.com')) {
			preg_match("#var\s*gallerySource\s*=\s*{(.*)};#im", $content, $match);
			if($match[1]) {
				$exploded_match = explode("},",$match[1]);
				if($exploded_match) {
					$pics_count = false;
					$hash = false;
					$pics_url = false;
					foreach ($exploded_match as $key => $array_value) {
						$array_value = str_replace("\"", "", $array_value);
						$array_value = str_replace("{", "", $array_value);
						if(strstr($array_value, "fullsize:") !== false) {
							$array_value = substr($array_value, 9, strlen($array_value));
							if(preg_match("#hash:(.*),#im", $array_value, $hash_match)) {
								if(preg_match("#path:(.*)#im", $array_value, $url_match)) {
									$hash = $hash_match[1];
									$pics_url = stripslashes($url_match[1]);
									var_dump($pics_url);
								}
							}
							
						} elseif(preg_match("#length:([0-9]+)#im", $array_value, $count_match)) {
							$pics_count = (int)$count_match[1];
						}
					}$images_count 	= 0;
					$added_url 		= array();
					if($pics_count && $hash && $pics_url) {
						for($i = 1; $i <= $pics_count; $i++) {
							$counter_s = ($i < 10) ? "0".$i : $i;
							$imageFinal[] = $pics_url . $counter_s . ".jpg" . $hash;
						}
					}

				}
				
			}

			//var_dump($imageFinal);
			if (isset($imageFinal)) {
				$this->Images = $this->ExcludeDoubles($imageFinal);
				$this->Count = count($this->Images);
				curl_close( $ch );
				return TRUE;
			}
			curl_close( $ch );
			$this->Count = 0;
			$this->Images = FALSE;
			return FALSE;			
		} else {
			/* 
			$count = preg_match_all("#<a.*?href[\s]*=[\s]*[\"\'\s]{0,}([^\"^\'^\>]*?\.jpe?g)[\"\'\s]{0,}.*?>#im", $content, $matches);
			*/

			// var_dump($content);
			echo "Call getImagesLink\n";

			if(strstr($gallery_domain, 'boyspornpics.com')) {
				$queryString = "//div[contains(concat(' ', normalize-space(@class), ' '), ' gallery-holder ')]
                 				//div[contains(concat(' ', normalize-space(@class), ' '), ' item ')]";
				$imageAttribute = 'data-href';
			} else {
				$queryString = '//a';
				$imageAttribute = 'href';
			}

			

			$matches[1] = $this->getImageLinks($content, $queryString, $imageAttribute);

			
			if($matches[1]) {
				$count = count($matches[1]);	
			}
			echo "End getImagesLink\n";
			
		}

		$image_url = array();
		
		if ($count == FALSE) {

			// парсит SeanCody
			$count = preg_match_all("#class\=\"movie\-photos\-thumbnail\".*?src=\s*[\"](.*?)[\"]#im", $content, $matches);
			$count1 = preg_match_all("#class\=\"movie\-stills\-thumbnail\".*?src\=\s*[\"](.*?)[\"]#im", $content, $matches1);

			//var_dump($matches1);
			
			if (($count !== FALSE && $count !== 0) || ($count1 !== FALSE && $count1 !== 0)) {
				$header  = curl_getinfo( $ch );
				$url = parse_url($header ["url"]);
				$url = $http_s."://" . $url['host'];

				if ($count !== FALSE && $count !== 0) {
					foreach($matches[1] as $bigImage) {
						if (strstr($bigImage,$http_s.":")) $image_url[] = trim(str_replace("thumbs/", "", $bigImage));
						else $image_url[] = $url ."/".trim(str_replace("thumbs/", "", $bigImage));
					}
				}

				if ($count1 !== FALSE && $count1 !== 0) {
					foreach ($matches1[1] as $bigImage) {
						if (strstr($bigImage,$http_s.":")) $image_url[] = trim(str_replace("thumbs/", "", $bigImage));
						else $image_url[] = $url ."/".trim(str_replace("thumbs/", "", $bigImage));
					}
				}
				curl_close( $ch );
				$this->Images = $this->ExcludeDoubles($image_url);
				$this->Count = count($this->Images);
				return TRUE;
			}
			curl_close( $ch );
			$this->Count = 0;
			$this->Images = FALSE;
			return FALSE;
		}
	
		$basecount = preg_match ("#<base.*?href=[\'\"]([^\"^\']*?)[\'\"].*?>#im", $content, $basematch);
		
		$images_count 	= 0;
		$added_url 		= array();

		if(is_array($matches) && isset($matches[1]) && is_array($matches[1])) {
			foreach($matches[1] as $image) {
				$image = str_replace (" ","%20", $image);
				// var_dump(strpos($image, "//"));
				if (strpos($image, "//") === 0) {
					// echo "AA";
					$image_url[$images_count] = $http_s.":" . $image;
					$images_count++;
				}  elseif ($basecount && !strstr($image, $http_s."://")) {
					// echo "CCC";
					$image_url[$images_count] = $basematch[$basecount] . $image;
					$images_count++;
				} elseif (strstr($image, "http://") !== false || strstr($image, "https://") !== false) {
					// echo "GG";
					$image_url[$images_count] = $image;
					$images_count++;
					// var_dump($images_count);
				} elseif (strstr($image, "../") !==FALSE) {
					// echo "TT";
					$subfolder_count = substr_count ($image, "../");
					$image = str_replace ("../","", $image);
					$added_url[$images_count] = $http_s.":/";
					if($dir_count-$subfolder_count <= 0) {
						$d_cnt = 1;
					} else {
						$d_cnt = ($dir_count-$subfolder_count);
					}
					for ($i=0; $i < $d_cnt; $i++) {
						$added_url[$images_count] = $added_url[$images_count] . "/" . $cut_url[$i];
					}
					$image_url[$images_count] = $added_url[$images_count] . "/" . htmlspecialchars($image);
					$images_count++;
				} elseif (strpos($image, "/") !== 0) {
					// echo "WW";
					$added_url[$images_count] = $http_s.":/";
					for ($i=0; $i < $dir_count; $i++) {
						$added_url[$images_count] = $added_url[$images_count] ."/" . $cut_url[$i];
					}
					$image_url[$images_count] = $added_url[$images_count] . "/" . htmlspecialchars($image);
					$images_count++;
				} elseif (strpos($image, "/") == 0) {
					// echo "ZZ";
					$image_url[$images_count] = $http_s."://" . $cut_url[0] . $image;
					$images_count++;
				} else {
					curl_close( $ch );
					$this->Images = FALSE;
					$this->Count = 0;
					return FALSE;
				}
			}
		}
		
		curl_close( $ch );
		$this->Images = $this->ExcludeDoubles($image_url);
		$this->Count = count($this->Images);
		var_dump($this->Images);
		return TRUE;
	}
	
}