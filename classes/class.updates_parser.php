<?php

class UpdatesParser {
	function __construct($debug = false) {
		$this->error_code = 0;
		$this->archive_pages = array();
		$this->content_post_links = array();
		$this->page_md5 = false;
		$this->affiliate_program = false;
		$this->debug = $debug;
	}

	function getPage($url, $cookie = false) {
		$ckfile = tempnam ("/tmp", "CURLCOOKIE");
		$uagent = "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.13) Gecko/20101206 Ubuntu/10.04 (lucid) Firefox/3.6.13";
		$ch = curl_init( $url );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
			curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
			curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
			curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
			curl_setopt($ch, CURLOPT_MAXREDIRS, 4);       // останавливаться после 4-ого редиректа
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile);
			if(preg_match("#(metartmoney\.com)#im", $this->affiliate_program)) curl_setopt($ch, CURLOPT_COOKIE, "mam_AffiliateID=1854918"); 
			elseif(preg_match("#(buddyprofits\.com)#im", $this->affiliate_program)) curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id()."; splash=1;"); 
			if ($cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		$page_content = curl_exec( $ch );
		$page_responce_header = curl_getinfo($ch);
		if ($page_responce_header['http_code'] == 400 ||
			$page_responce_header['http_code'] == 401 ||
			$page_responce_header['http_code'] == 403 ||
			$page_responce_header['http_code'] == 404 ||
			$page_responce_header['http_code'] == 405 ||
			$page_responce_header['http_code'] == 408 ||
			$page_responce_header['http_code'] == 500 ||
			$page_responce_header['http_code'] == 502 ||
			$page_responce_header['http_code'] == 503 ||
			$page_responce_header['http_code'] == 504 ||
			$page_responce_header['http_code'] == 505) {
			$this->error_code = $page_responce_header['http_code'];
			$this->page_content = false;
			return false;
		} else {
			$this->error_code = 0;
			return $page_content;
		}
	}

	private function parseArchiveLinks($content) {
		$result = false;
		$html = new DOMDocument();
		$html->loadHTML ($content);
		if(preg_match("#(lucaskazan\.com|chaosmen\.com|seancody\.com|buddyprofits\.com|blueloot\.com|jakepays\.com|dominicford\.com)#im", $this->affiliate_program)) {
			if(preg_match("#(buddyprofits\.com)#im", $this->affiliate_program)) $query_string = '//div[@id="nav-below"]/div[@class="nav-previous"]/a'; // ссылки на архивные страницы
			elseif(preg_match("#(jakepays\.com)#im", $this->affiliate_program)) $query_string = '//div[@class="navigation"]/div[@class="alignleft"]/a'; // ссылки на архивные страницы
			elseif(preg_match("#(blueloot\.com)#im", $this->affiliate_program)) $query_string = '//span[@class="next"]/a[@class="pager_link"]'; // ссылки на архивные страницы
			elseif(preg_match("#(dominicford\.com)#im", $this->affiliate_program)) $query_string = '//div[@class="next"]/a'; // ссылки на архивные страницы
			elseif(preg_match("#(seancody\.com)#im", $this->affiliate_program)) $query_string = '//a[@id="movie-index-next"]'; // ссылки на архивные страницы
			elseif(preg_match("#(englishlads\.com)#im", $this->affiliate_program)) $query_string = '//a[@class="more"]'; // ссылки на архивные страницы
			elseif(preg_match("#(chaosmen\.com)#im", $this->affiliate_program)) $query_string = '//a[@class="pageResults"]'; // ссылки на архивные страницы
			elseif(preg_match("#(lucaskazan\.com)#im", $this->affiliate_program)) $query_string = '//div[@class="pagination"]/a'; // ссылки на архивные страницы
			$xpath = new DOMXPath( $html );
			$ht = $xpath->query( $query_string );
			// var_dump($ht->length);
			if ($ht) {
				foreach ($ht as $element) {
					$url = $element->getAttribute('href');
					if (preg_match("#(blueloot\.com)#im", $this->affiliate_program) && $url[0] == "/") $url = "http://www.randyblue.com" . $url;
					if (preg_match("#(dominicford\.com)#im", $this->affiliate_program) && $url[0] == "/") $url = "http://www.dominicford.com" . $url;
					if (preg_match("#(seancody\.com)#im", $this->affiliate_program) && $url[0] == "/") $url = "http://www.seancody.com" . $url;
					if (preg_match("#(englishlads\.com)#im", $this->affiliate_program) && $url[0] == "/") $url = "http://www.englishlads.com" . $url;
					if (preg_match("#(chaosmen\.com)#im", $this->affiliate_program) && $url[0] == "/") $url = "http://chaosmen.com" . $url;
					if (preg_match("#(lucaskazan\.com)#im", $this->affiliate_program) && $url[0] == "/") $url = "http://www.lucaskazan.com" . $url;
					if ($url) $result = $url;
				}
			}
		}
		return $result;
	}

	private function parseUrlsToContent($content, $content_type = false) {
		$result = false;
		$desc = false;
		$output = array();
		$links_used = array();

		
		libxml_use_internal_errors(true);
		// XML/RSS
		if (preg_match("#(kinkydollars\.com|xxxrewards\.com|manicamoney\.com|helixcash\.com)#im", $this->affiliate_program)) {
			$html = new DOMDocument('1.0', 'utf-8');
			$html->xmlStandalone = false;
		 	$html->loadXML ( $content );
		}
		else {
			$html = new DOMDocument();
			$html->loadHTML ( $content );
		}
		libxml_clear_errors();
		$xpath = new DOMXPath( $html );
		 // var_dump($content);
		$current_links_array = array();
		if (preg_match("#(buddyprofits\.com|jakepays\.com)#im", $this->affiliate_program)) {
			if (preg_match("#(buddyprofits\.com)#im", $this->affiliate_program)) $query_string = './/div/h2/a';
			elseif (preg_match("#(jakepays\.com)#im", $this->affiliate_program)) $query_string = './/div[@class="post"]/h2[@class="title"]/a';
			$ht = $xpath->query( $query_string );
			if (isset($ht) && $ht) {
				foreach ( $ht as $elm ) { 
					$url = $elm->getAttribute('href');
					// var_dump($url);
					$title = $elm->nodeValue;
					// var_dump($title);
					$content = $this->getPage($url);
					$video_link = $this->parseContent($content);
					if ($video_link && $video_link['pics'] != "") $output[] = array('title' => $title, 'url' => $video_link['pics'], 'desc' =>$video_link['desc']);
					if ($video_link && $video_link['video'] != "") $output[] = array('title' => $title, 'url' => $video_link['video'], 'desc' =>$video_link['desc']);
				}	
				// var_dump($output);
				return $output;
			}
		} elseif (preg_match("#(metartmoney\.com|gayhoopla\.com|lucaskazan\.com|chaosmen\.com|kinkydollars\.com|xxxrewards\.com|blueloot\.com|dominicford\.com|seancody\.com|englishlads\.com|manicamoney\.com|gunzblazing\.com|helixcash\.com)#im", $this->affiliate_program)) {
			if (preg_match("#(blueloot\.com)#im", $this->affiliate_program)) $query_string = './/div[@class="preview-box"]/div[@class="added"]/a[@class="link"]';
			elseif (preg_match("#(dominicford\.com)#im", $this->affiliate_program)) {
				$query_string = './/div[@id="affiliatePage"]/div[@id="left"]/table/tr';
			} elseif (preg_match("#(seancody\.com)#im", $this->affiliate_program)) {
				$query_string = './/div[@class="col3"]/a[@class="js-hover-image item"]';
			} elseif (preg_match("#(englishlads\.com)#im", $this->affiliate_program)) {
				$query_string = '//table/tr/td/table[2]/tr/td/table/tr[2]/td[2]/table/tr/td/center[3]/table/tr/td[2]/table/tr/td[1]';
			} elseif (preg_match("#(gayhoopla\.com)#im", $this->affiliate_program)) {
				$query_string = '//table/tr/td/table/tr/td';
			} elseif (preg_match("#(gunzblazing\.com|manicamoney\.com|helixcash\.com)#im", $this->affiliate_program)) {
				// костыль на костыле
				if (preg_match("#(manicamoney\.com|helixcash\.com)#im", $this->affiliate_program) && $content_type == 'video') {
					$query_string = '//videos/video';
				} else 	{
					$query_string = '//rss/channel/item';
				}
			} elseif (preg_match("#(kinkydollars\.com|xxxrewards\.com)#im", $this->affiliate_program)) {
				$query_string = '//videos/video';
			} elseif (preg_match("#(chaosmen\.com)#im", $this->affiliate_program)) {
				$query_string = '//td[@class="thumbnailBorder"]/a';
			} elseif (preg_match("#(lucaskazan\.com)#im", $this->affiliate_program)) {
				$query_string = '//div[@class="vid lg"]';
			} elseif(preg_match("#(metartmoney\.com)#im", $this->affiliate_program)) {
				if (!$content_type) $query_string = '//textarea';
				else $query_string = '//div[@class="v"]';
			} 
			$ht = $xpath->query( $query_string );
			// var_dump($query_string);
			// var_dump($ht);
			//  foreach($ht as $e) {
			//  	var_dump($e->nodeName, $e->childNodes->length);
			//  	foreach($e->childNodes as $node) {
			//  		echo $node->nodeName .":" .$node->nodeValue."\n";
			//  	}
			//  }
			//  echo "ht";
			if ($ht) {
				if(preg_match("#(metartmoney\.com)#im", $this->affiliate_program)) {
					// var_dump($ht->item(0)->nodeValue);
					if (!$content_type) {
						if ($ht->item(0) && $ht->item(0)->nodeValue) {
							$gals_array = explode("\n", $ht->item(0)->nodeValue);
							foreach ($gals_array as $each_string) {
								$gallery_item = explode("|", $each_string);
								$desc = "";
								if ($gallery_item && is_array($gallery_item) && count($gallery_item) == 2) {
									$output[] = array('title' => $gallery_item[1], 'url' =>$gallery_item[0], 'desc' => $desc);
								}
							}
						}
					} else {
						foreach($ht as $content_div) {
							$title_block = $content_div->getElementsByTagName('h1');
							if($title_block && $title_block->item(0) && $title_block->item(0)->nodeValue) {
								$title = $title_block->item(0)->nodeValue;
								$int_div_elements = $content_div->getElementsByTagName('div');
								$desc = "";
								if($int_div_elements) {
									foreach($int_div_elements as $div_elem) {
										if($div_elem->getAttribute("class") == 'v-movie'){
											$v_elem = $div_elem->getElementsByTagName('a');
											if($v_elem && $v_elem->item(0)) {
												if($link = $v_elem->item(0)->getAttribute("href")) {
													$output[] = array('title' => $title, 'url' =>$link, 'desc' => $desc);
												}
											}
											
										}
									}
								}
							}
						}
					}
				} elseif (preg_match("#(dominicford\.com)#im", $this->affiliate_program)) {
					foreach ($ht as $tr) {
						$tr_blocks = $tr->getElementsByTagName('td');
						if ($tr_blocks) {
							
							foreach ($tr_blocks as $elm) {
								$gallery_url = "";
								$title = false;
								$video_flag = false;
								$title_flag = false;
								$zip_flag = false;
								$links_array = array();
								$block_links = $elm->getElementsByTagName('a');
								$title_node = $elm->getElementsByTagName('b');
								if ($title_node) $title = $title_node->item(0)->nodeValue;
								if ($title && preg_match("#\.\.\.#", $title)) $title = false; // если в тайтле троеточие, он сбрасывается
								foreach ($block_links as $link) 
								{
									$content_link = false;
									$link = $link->getAttribute('href');
									if (preg_match("#(movie\.php\?MovieID|\.m4v|\.flv|\/assets\/zip/)#im", $link)) {
										if ($link[0] == "/") $gallery_url = "http://dominicford.com" . $link;
										else $gallery_url = $link;

										if (!$video_flag && preg_match("#(\.m4v|\.flv)#im", $link)) { // если видео
											$video_flag = true;
											$links_array[] = $gallery_url;
										} elseif (!$zip_flag && preg_match("#(\/assets\/zip/)#im", $link)) {
											$zip_flag = true;
											$links_array[] = $gallery_url;
										} elseif (!$title && !$title_flag && preg_match("#(movie\.php\?MovieID)#im", $link)) { // если тайтл пустой
												$content = $this->getPage($gallery_url);
												$title = $this->parseContent($content);
												$title_flag = true;
										}
									}									
								}
								foreach($links_array as $link) {
									$output[] = array('title' => $title, 'url' =>$link, 'desc' => $desc);
								}
							}
						}
					}
					
				} else {
					// var_dump($ht);
					foreach ($ht as $elm) {
						if (preg_match("#(gayhoopla\.com|lucaskazan\.com|chaosmen\.com|kinkydollars\.com|xxxrewards\.com|blueloot\.com|seancody\.com|englishlads\.com|gunzblazing\.com|manicamoney\.com|helixcash\.com)#im", $this->affiliate_program)) {

							if (preg_match("#(lucaskazan\.com)#im", $this->affiliate_program)) {
								$a_blocks = $elm->getElementsByTagName('a');
								if ($a_blocks) {
									$gallery_url = "";
									$title = false;
									$desc = false;
									$video_flag = false;
									$title_flag = false;
									$zip_flag = false;
									$links_array = array();
									foreach ($a_blocks as $block) {
										$current_url = $block->getAttribute('href');
										// var_dump($current_url);
										if ($current_url[0] == "/") $current_url = "http://www.lucaskazan.com/" . $current_url;
										if (!$video_flag && preg_match("#(\.mov)$#im", $current_url)) {
											$links_array[] = $current_url;
											$video_flag = true;
										} elseif (!$zip_flag && preg_match("#(\.zip)#im", $current_url)) {
											$links_array[] = $current_url;
											$zip_flag = true;
										} elseif (!$title_flag && preg_match("#(gallery_photo|gallery_reality)#im", $current_url)) {
											$content = $this->getPage($current_url);
											$title_desc = $this->parseContent($content);
											$title = $title_desc['title'];
											$desc = $title_desc['desc'];
											$title_flag = true;
										}
									}
									foreach($links_array as $link) {
										$output[] = array('title' => $title, 'url' =>$link, 'desc' => $desc);
									}

								}
							} elseif (preg_match("#(gayhoopla\.com)#im", $this->affiliate_program)) {
								$a_blocks = $elm->getElementsByTagName('table');
								if ($a_blocks) {
									$gallery_url = "";
									$title = false;
									$video_flag = false;
									$title_flag = false;
									$zip_flag = false;
									$links_array = array();
									foreach ($a_blocks as $block) {
										$n_block = $block->getElementsByTagName('td');
										$out = false;
										foreach($n_block as $td_block) {
											$td_attribute = $td_block->getAttribute('class');
											if($td_attribute == 'upd0') {
												$title = $td_block->getElementsByTagName('h2');
												if($title->item(0)->nodeValue) {
													$out['title'] = $title->item(0)->nodeValue;
												}
											} elseif($td_attribute == 'upd2') {
												$desc = $td_block->getElementsByTagName('p');
												if($desc->item(0)->nodeValue) {
													$out['desc'] = $desc->item(0)->nodeValue;
												}
											} elseif($td_attribute == 'upd3') {
												$urls = $td_block->getElementsByTagName('a');
												foreach($urls as $url_c) {
													$current_url = $url_c->getAttribute('href');
													if($current_url) {
														if ($current_url[0] == "/") $out['url'][] = "http://www.gayhoopla.com/" . $current_url;
														else $out['url'][] = $current_url;
													}

												}
											}
										}
										if ($out && is_array($out) && count($out) == 3 && isset($out['desc'])
										 && isset($out['title']) && isset($out['url']) && is_array($out['url'])) {
											foreach ($out['url'] as $ctt_link) {
												$output[] = array('title' => $out['title'], 'url' =>$ctt_link, 'desc' => $out['desc']);
											}
										}
									}
									// var_dump($output);


								}
							} elseif (preg_match("#(englishlads\.com)#im", $this->affiliate_program)) {
								$a_blocks = $elm->getElementsByTagName('a');
								if ($a_blocks) {
									$gallery_url = "";
									$title = false;
									$video_flag = false;
									$title_flag = false;
									$zip_flag = false;
									$links_array = array();
									foreach ($a_blocks as $block) {
										$current_url = $block->getAttribute('href');
										if ($current_url[0] == "/") $url = "http://www.englishlads.com/" . $current_url;
										if (!$video_flag && preg_match("#(trl_mp4\.zip)#im", $current_url)) {
											$links_array[] = $current_url;
											$video_flag = true;
											
										}
										elseif (!$zip_flag && preg_match("#(\.zip)#im", $current_url)) {
											if(!preg_match("#(trl\.zip|trs\.zip|trs_mp4\.zip)#im", $current_url)) {
												$links_array[] = $current_url;
												$zip_flag = true;
											}
										} elseif (!$title_flag) {
											$title = $block->nodeValue;
											$title_flag = true;
										}
									}
									foreach($links_array as $link) {
										$output[] = array('title' => $title, 'url' =>$link, 'desc' => $desc);
									}

								}
							} elseif (preg_match("#(gunzblazing\.com)#im", $this->affiliate_program)) {
								$link = $elm->getElementsByTagName('guid');
								$title = $elm->getElementsByTagName('title');
								if ($link->item(0)->nodeValue && $title->item(0)->nodeValue) {
									$url = $link->item(0)->nodeValue;
									$title = $title->item(0)->nodeValue;
									if($content_type == 'movies' || $content_type == 'video') {
										$content = $this->getPage($url);
										$video_link = $this->parseContent($content);
										$output[] = array('url' => $video_link, 'title' => $title, 'desc' => $desc);
									} else {
										$url = $link->item(0)->nodeValue;
										$output[] = array('url' => $url, 'title' => $title, 'desc' => $desc);
									}
						
								}
								// var_dump($video_link);								
							} elseif (preg_match("#(manicamoney\.com|helixcash\.com)#im", $this->affiliate_program)) {
								// var_dump($content_type);
								if($content_type == 'movies' || $content_type == 'video') {

									$link = $elm->getElementsByTagName('clipurl');
									if(!$link->item(0)) $link = $elm->getElementsByTagName('clip_url');
									$title = $elm->getElementsByTagName('title');
									$description = $elm->getElementsByTagName('description');
									$flv = $elm->getElementsByTagName('flv');
									if(!$flv->item(0)) $flv = $elm->getElementsByTagName('mp4');
									if ($link->item(0)->nodeValue && $title->item(0)->nodeValue && $flv->item(0)->nodeValue) {
										$video_link = $link->item(0)->nodeValue . $flv->item(0)->nodeValue;
										$title = $title->item(0)->nodeValue;
										if ($description->item(0)->nodeValue) $desc = $description->item(0)->nodeValue;
										else $desc = "";

										$output[] = array('url' => $video_link, 'title' => $title, 'desc' => $desc);
									} 
								} else {	
									
									$link = $elm->getElementsByTagName('link');
									$title = $elm->getElementsByTagName('title');
									$description = $elm->getElementsByTagName('description');
									// var_dump($description);
									$desc = "";
									foreach ($description as $destination) {
									    foreach($destination->childNodes as $child) {
									        if ($child->nodeType == XML_CDATA_SECTION_NODE) {
									        	if($child->textContent) {
									        		preg_match_all("#<p>(.*)</p>#", $child->textContent, $matches);
									        		if(isset($matches[1][0]) && $matches[1][0]) $desc = $matches[1][0];
									        	}
									        }
									    }
									}

									if ($link->item(0)->nodeValue && $title->item(0)->nodeValue) {
										$url = $link->item(0)->nodeValue;
										$title = $title->item(0)->nodeValue;
										if(!$desc) $desc = "";
										$output[] = array('url' => $url, 'title' => $title, 'desc' => $desc);							
									}
								}
							} elseif (preg_match("#(seancody\.com)#im", $this->affiliate_program)) {
								$url = $elm->getAttribute('href');
								if ($url[0] == "/") $url = "http://www.seancody.com/" . $url;
								$title = $elm->nodeValue;
								$content = $this->getPage($url);
								$video_link = $this->parseContent($content);
								$desc = "";
								$output[] = array('title' => $title, 'url' => $url, 'desc' => $desc);
								if ($video_link && $video_link != "") $output[] = array('title' => $title, 'url' => $video_link, 'desc' => $desc);
							} elseif (preg_match("#(chaosmen\.com)#im", $this->affiliate_program)) {
								$link = $elm->getAttribute( 'href' );
								if( strpos($link, 'affiliate_preview.php') !== false ) {
									preg_match_all("#(affiliate_preview.php\?video_id=([0-9]+))#im", $link, $matches);
									if($matches[0][0] && !in_array($matches[0][0], $links_used)) {
										$links_used[] = $matches[0][0];
										$url = "http://chaosmen.com/" . $matches[0][0];
										$content = $this->getPage($url);
										$video_link = $this->parseContent($content);
										if ($video_link['pics'] && $video_link['pics'] != "") $output[] = array('title' => $video_link['title'], 'url' => $video_link['pics'], 'desc' => $desc);
										if ($video_link['video'] && $video_link['pics'] != "") $output[] = array('title' => $video_link['title'], 'url' => $video_link['video'], 'desc' => $desc);
									}
								}
								
							}  elseif (preg_match("#(kinkydollars\.com|xxxrewards\.com)#im", $this->affiliate_program)) {
								$link = $elm->getElementsByTagName('clip_url');
								$title = $elm->getElementsByTagName('title');
								$flv = $elm->getElementsByTagName('flv');
								if ($link->item(0)->nodeValue && $title->item(0)->nodeValue && $flv->item(0)->nodeValue) {
									$video_link = $link->item(0)->nodeValue . $flv->item(0)->nodeValue;
									$title = $title->item(0)->nodeValue;	
									if($content_type == 'movies' || $content_type == 'video') $output[] = array('url' => $video_link, 'title' => $title, 'desc' => $desc);
								} 
							} else {
								$url = $elm->getAttribute('href');
								$title = $elm->getAttribute('title');
								if ($url[0] == "/") $url = "http://randyblue.com/" . $url; // временная заглушка
								$output[] = array('title' => $title, 'url' => $url, 'desc' => $desc);	
							}
						}
					}
				}

				$result = $output;
			}
		}

		return $result;
	}

	private function parseContent($content) {
		$output = false;
		$html = new DOMDocument();
		$html->loadHTML ( $content );
		$xpath = new DOMXPath( $html );
		if (preg_match("#(lucaskazan\.com|chaosmen\.com|buddyprofits\.com|jakepays\.com|dominicford\.com|seancody\.com|gunzblazing\.com)#im", $this->affiliate_program)) {
			// выдергивание тайтла
			if (preg_match("#(buddyprofits\.com)#im", $this->affiliate_program)) $query_string = './/h1[@class="entry-title"]';
			elseif (preg_match("#(jakepays\.com)#im", $this->affiliate_program)) $query_string = './/div[@class="post"]/h1[@class="title"]/a';
			elseif (preg_match("#(dominicford\.com)#im", $this->affiliate_program)) {
				$query_string = './/div[@id="title"]/span';
			} elseif(preg_match("#(lucaskazan\.com)#im", $this->affiliate_program)) {
				$x_content = $xpath->query( './/div[@class="p_left"]/p/span[@class="h1"]' );
				if ($x_content && $x_content->item(0)) {
					$title = trim($x_content->item(0)->nodeValue);
					$x_content = $xpath->query( './/div[@class="p_left"]/p[3]' );
					$desc = trim($x_content->item(0)->nodeValue);
				} else {
					$x_content = $xpath->query( './/div[@class="p_left"]/h1' );
					$title = trim($x_content->item(0)->nodeValue);
					$x_content = $xpath->query( './/div[@class="p_left"]/p[2]' );
					$desc = trim($x_content->item(0)->nodeValue);
				}
				return array('title' => $title, 'desc' => $desc);

			} elseif (preg_match("#(seancody\.com)#im", $this->affiliate_program)) {
				$video_url = false;
				$query_string = './/input[@id="movie-hidden-preview-http-host"]/@value';
				$ht = $xpath->query( $query_string );
				if ($ht && $ht->item(0)) {
					$host = $ht->item(0)->value;
					$query_string = './/input[@id="movie-hidden-preview-dir"]/@value';
					$ht = $xpath->query( $query_string );
					if ($ht && $ht->item(0)) {
						$video_path = $ht->item(0)->value;
						$query_string = './/input[@id="movie-hidden-preview-file-name"]/@value';
						$ht = $xpath->query( $query_string );
						if ($ht && $ht->item(0)) {
							$video_url = "http://".$host. "/".$video_path. "/". $ht->item(0)->value;

						}
					}
				}
				
				return $video_url;
			} elseif (preg_match("#(chaosmen\.com)#im", $this->affiliate_program)) {
				$video_url = false;
				$desc = false;
				$query_string = '//td[@class="previewBox"]/a';
				$ht = $xpath->query( $query_string );
				$site_url = "http://chaosmen.com/";
				$pics = "";
				foreach($ht as $node)
				{ 
					$link = $node->getAttribute( 'href' );
					if( strpos($link, '640.wmv') !== false )
						$video_url = $site_url . $link;

					if( strpos($link, '.zip') !== false )
						$pics = $site_url . $link;
				}
				$content = $xpath->query( '//td[@class="displayTable"]/p/span[@class="pageHeadingGreen"]' );
				$title = trim($content->item(0)->nodeValue);

				$content = $xpath->query( '//td[@class="displayTable"]/p/span[@class="pageHeadingWhite"]' );
				$title .= trim($content->item(0)->nodeValue);

				$content = $xpath->query( '//td[@class="displayTable"]/p/span[@class="videoSub"]' );
				$title .= trim($content->item(0)->nodeValue);


				$output['paysite'] = "Chaosmen.com";
				$output['video'] = $video_url;
				$output['pics'] = $pics;
				$output['desc'] = $desc;
				$output['title'] = $title;

			
				return $output;
			} elseif (preg_match("#(gunzblazing\.com)#im", $this->affiliate_program)) {
				$video_url = false;
				$query_string = '//a';
				$ht = $xpath->query( $query_string );
				if ($ht && $ht->item(0)) {
					$js_block_to_parse = $ht->item(0)->nodeValue;
					$matches = false;
					preg_match_all("#file:\s\'(.*)\'#im", $js_block_to_parse, $matches);
					if ($matches && isset($matches[1][0]) && preg_match("#(^(http:\/\/)(.*)\.(mp4|flv|wmv|avi|m4u|mpg|mpeg))#im", $matches[1][0])) return $matches[1][0];
					else {
						preg_match_all("#'file',\s'(.*)'#im", $js_block_to_parse, $matches);
						if ($matches && isset($matches[1][0]) && preg_match("#(^(.*)\.(mp4|flv|wmv|avi|m4u|mpg|mpeg))#im", $matches[1][0])) {
							$query_string = '//base';
							$video_link = $matches[1][0];
							$ht = $xpath->query( $query_string );
							if ($ht && $ht->item(0)) {
								$base_url = $ht->item(0)->getAttribute('href');
								$video_link = preg_replace("#^(.*)\/#im", $base_url, $video_link);
								return $video_link;	
							}
							
						}
					}
					return false;
				}
				
				return $video_url;
			}
			$ht = $xpath->query( $query_string );
			if ($ht && $ht->item(0)) {
				$desc = false;
				$title = $ht->item(0)->nodeValue;
				if (preg_match("#(dominicford\.com)#im", $this->affiliate_program)) return $title;
				// выдергивание названия платника
				if (preg_match("#(buddyprofits\.com)#im", $this->affiliate_program)) {
					$query_string = '//div[@class="entry-content"]/p';
					$ht_desc = $xpath->query( $query_string );
					if ($ht_desc && $ht_desc->item(1) != "") $desc = $ht_desc->item(1)->nodeValue;
					$query_string = './/div[@class="entry-utility"]/a[@rel="tag"]';
				} elseif (preg_match("#(jakepays\.com)#im", $this->affiliate_program)) $query_string = './/div[@class="entry"]/h2/em';
				$ht = $xpath->query( $query_string );
				if ($ht && $ht->item(0)) {
					$paysite = $ht->item(0)->nodeValue;
					// поиск архивов с контентом на странице
					if (preg_match("#(buddyprofits\.com)#im", $this->affiliate_program)) $query_string = './/div[@class="entry-content"]/p/a';
					elseif (preg_match("#(jakepays\.com)#im", $this->affiliate_program)) $query_string = './/div[@class="entry"]/p/span/a';
					$ht = $xpath->query( $query_string );
					$item_1 = "";
					$item_2 = "";
					$item_3 = "";
					if ($ht && $ht->item(0)) {
						// должно быть 2 архива, если одного нет, заменяем пустой строкой =>
						foreach ($ht as $item) {
							$temp_item = $item->getAttribute('href');
							if (!preg_match("#(\/BANNERS_)#", $temp_item)) $items[] = $temp_item;
						}
						if (preg_match("#(\/VIDS_|videos)#", $items[0])) {
							$video = $items[0];
							$pics = $items[1];
						} else {
							$video = $items[1];
							$pics = $items[0];
						}
						$output['paysite'] = $paysite;
						$output['video'] = $video;
						$output['pics'] = $pics;
						$output['title'] = $title;
						$output['desc'] = $desc;
					}


				}
			} 			
		}

		return $output;
	}

	public function getPageMD5($url) {
		$content = $this->getPage($url);
		if ($content) return md5($content);
		else return false;
	}

	function parseUpdatesFromUrl($url) {
		$content = $this->getPage($url);
		if ($content) $update = $this->parseContent($content);
		else $update = false;
		if ($update && is_array($update) && isset($update['title'])) return $update;
		else return false;
	}

	function getSiteUpdates($url, $affiliate_program, $content_type = false, $archive_depth = 4) {
		$content_urls = array();
		$this->affiliate_program = $affiliate_program;

		$able_to_parse = "metartmoney\.com|gayhoopla\.com|lucaskazan\.com|chaosmen\.com|blueloot\.com|kinkydollars\.com|xxxrewards\.com|dominicford\.com|seancody\.com|englishlads\.com|gunzblazing\.com|manicamoney\.com|buddyprofits\.com|jakepays\.com|helixcash\.com";
		if (!preg_match("#(".$able_to_parse.")#im", $this->affiliate_program)) return false;

		$counter = 0;
		$updates = false;
		while ($counter < $archive_depth) {
			$counter++;
			$content = $this->getPage($url);
			if ($content) {
				$content_urls_temp = $this->parseUrlsToContent($content, $content_type);
				 // var_dump($content_urls_temp);
				if ($content_urls_temp) {
					foreach ($content_urls_temp as $tmp_content) {
						$content_urls[] = $tmp_content;
					}
				} else {
					echo "Не найдены ссылки на блог-посты с контентом<br>";
					break;
				}
				$url = $this->parseArchiveLinks($content);
				if (!$url) {
					echo "Не найдена ссылка на еще один архив (parseArchiveLinks)<br>";
					break;
				}
			}
		}
		if ($this->debug == true) var_dump($content_urls);
		return $content_urls;		
	}	
}

?>