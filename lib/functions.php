<?php

define ("DEFAULT_USER_AGENT", "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8");


//
//
//
//
//
//
//


function clean_string($string) {
	//$string = strip_tags($string);
	$string = preg_replace('/[^.,"\'a-zA-Z0-9 _-]/', '', $string);
	$string = trim($string);
	return $string;
}

function http_curl_request($url, $content=array(), $curl_options = false) 
{
	$send_file = false;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, DEFAULT_USER_AGENT);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);

	$p_url = parse_url($url);

	curl_setopt($ch, CURLOPT_REFERER, 'http://'.$p_url['host']);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept:	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language:	en-us,en;q=0.5',
		//'Accept-Encoding:	gzip',
		'Accept-Charset:	ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		'DNT:	1',
		'Connection:	keep-alive',
		'Expect:',
	)); 

	if (isset($curl_options['proxy']))
	{
		curl_setopt($ch, CURLOPT_PROXY, $curl_options['proxy']);
		if ( isset($curl_options['proxy_access']) && !empty($curl_options['proxy_access']))
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $curl_options['proxy_access']);
	}

	if (!empty($content)) {
		curl_setopt($ch, CURLOPT_POST, 1);
		if ( isset($content['file_name']) )
		{
			$send_file = true;
			
			$form_name = $content['form_name'];
			$file_name = $content['file_name'];
			$file_content = $content['file_content'];
			unset($content['form_name']);
			unset($content['file_name']);
			unset($content['file_content']);
			$dir = false;
			while(!$dir)
			{
				$dir = md5($url . time());
				if ( is_dir($dir) )
					$dir = false;
				else
				{
					if (!mkdir($dir, 0777))
						$dir = false;
				}	
				$tmpfname = dirname(__FILE__) . DIRECTORY_SEPARATOR. $dir . DIRECTORY_SEPARATOR . $file_name ;
			}
			$handle = fopen($tmpfname, "w");
			fwrite($handle, $file_content);
			fclose($handle);
			
			$content[$form_name] = '@'.$tmpfname. ';type=application/x-tar';
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		
	}
	$res = curl_exec($ch);

	$return_value = array(
			'url' => $url,
			'http_code' => curl_getinfo($ch,CURLINFO_HTTP_CODE),
			'curl_errno' => curl_errno($ch),
			'curl_error' => curl_error($ch),
			'curl_info' => curl_getinfo($ch),
		);

	curl_close($ch);

	$return_value['request_header'] =  preg_split("/((\r*)\n){1,}/", substr($res,0, $return_value['curl_info']['header_size']));
	$return_value['request_content'] = substr($res, $return_value['curl_info']['header_size']);

	foreach($return_value['request_header'] as $header_line)
	{
		$header_line_ex = explode(': ',$header_line);
		if($header_line_ex[0] == 'Content-Encoding')
		{
			if ( trim($header_line_ex[1]) =='deflate')
				$return_value['request_content'] = gzinflate($return_value['request_content']);
			if ( trim($header_line_ex[1]) =='gzip')
				$return_value['request_content'] = gzdecode($return_value['request_content']);
		}
		if($header_line_ex[0] == 'Location')
			$redirect_url = trim($header_line_ex[1]);
	}

	if ($return_value['http_code'] == 302 && isset($redirect_url))
	{
		if (isset($curl_options['redirect_counter']) && $curl_options['redirect_counter'] > 5) return false;
		$curl_options['redirect_counter'] = isset($curl_options['redirect_counter'])?$curl_options['redirect_counter']+1:1;
		return http_curl_request($redirect_url, $content, $curl_options);
	}

	if ($send_file === true)
	{
		unlink($tmpfname);
		rmdir($dir);
	}
	return $return_value;
}


  function folderNameById ($imageId) {
  	$folder =  false;
    $imageId = (int)$imageId;

    if ($imageId != 0) {
      if ($imageId < 256000){
        $folderId = (int)ceil($imageId/1000);
        $folder = "1/".$folderId;
      } else {
        $mainFolder= (int)ceil($imageId/256000);
        $folderId = (int)ceil($imageId/1000); 
        $folder = $mainFolder."/".$folderId;
      }
    }

    return $folder;
  }

	function pc_next_permutation($p, $size) {
		// проходим массив сверху вниз в поисках числа, которое меньше следующего
		for ($i = $size - 1; isset($p[$i]) and $p[$i] >= $p[$i+1]; --$i) { }

		// если такого нет, прекращаем перестановки
		// массив перевернут: (1, 2, 3, 4) => (4, 3, 2, 1)
		if ($i == -1) { return false; }

		// проходим массив сверху вниз в поисках числа,
		// превосходящего найденное ранее
		for ($j = $size; $p[$j] <= $p[$i]; --$j) { }

		// переставляем их
		$tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;

		// теперь переворачиваем массив путем перестановки элементов,
		// начиная с конца
		for (++$i, $j = $size; $i < $j; ++$i, --$j) {
			$tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
		}
		return $p;
	}

	function all_array_combinations ($set) {
		$size = count($set) - 1;
		$perm = range(0, $size);
		$j = 0;
		do {
			foreach ($perm as $i) {
				$perms[$j][] = $set[$i];
			}
		} while ($perm = pc_next_permutation($perm, $size) and ++$j);
		return $perms;
	}


	function CSVtoArray($input_csv, $element_counter, $images_delim = false) {
		$csv_array = explode ("\n", $input_csv);
		$import_galleries = array ();
		$url = $element_counter['url'];

		if(isset($element_counter['title'])) $title = $element_counter['title'];
		if(isset($element_counter['description'])) $description = $element_counter['description'];
		if(isset($element_counter['tags'])) $tags = $element_counter['tags'];
		if(isset($element_counter['paysite'])) $paysite = $element_counter['paysite'];
		if(isset($element_counter['models'])) $models = $element_counter['models'];
		if(isset($element_counter['title_1'])) $title_1 = $element_counter['title_1'];
		if(isset($element_counter['title_2'])) $title_2 = $element_counter['title_2'];
		if(isset($element_counter['title_3'])) $title_3 = $element_counter['title_3'];
		if(isset($element_counter['title_4'])) $title_4 = $element_counter['title_4'];
		if(isset($element_counter['embed'])) $embed = $element_counter['embed'];
		if(isset($element_counter['images'])) $images = $element_counter['images'];
		if(isset($element_counter['duration'])) $duration = $element_counter['duration'];
		if(isset($element_counter['gallery_original_id'])) $gallery_original_id = $element_counter['gallery_original_id'];
		
		// var_dump($models);
	
		$i = 0;
		$is_url = false;
		foreach ($csv_array as $string) {
			$string = trim($string);
			$string_length = strlen($string);
			$string = explode("|", $string);

			$stringFieldsCount = count($string) - 1;
			$import_galleries [$i]['url'] = trim($string[$url]);
			$is_url = preg_match("#^http(s?)://#im", $import_galleries [$i]['url']);

			if (!$is_url) {
				echo strpos($import_galleries [$i] ['url'], "http://") . "<br />";
				$import_galleries [$i]['error'] = 404;
			} else {
				$import_galleries [$i]['folder'] = md5($import_galleries [$i] ['url']);

				if (isset($title) && $stringFieldsCount >= $title) $import_galleries [$i] ['title'] = substr($string[$title],0, 255);
					else $import_galleries [$i] ['title'] = "";

				if (isset($element_counter['description']) && $stringFieldsCount >= $description) {
					if (strcmp($string[$description], $import_galleries [$i] ['title']) == 0) {
						$import_galleries [$i] ['description'] = "";
					} else $import_galleries [$i] ['description'] = $string[$description];
				}
					else $import_galleries [$i] ['description'] = "";

				if (isset($element_counter['tags']) && $stringFieldsCount >= $tags) {
					$import_galleries[$i]['tags'] = explode($images_delim, $string[$tags]);

					// Сделать разбивку на моделей/теги
				} else {
					$import_galleries [$i] ['tags'] = "";
				}

				if (isset($element_counter['paysite']) && $stringFieldsCount >= $paysite) $import_galleries[$i]['paysite'] = strip_tags($string[$paysite]);
					else $import_galleries [$i] ['paysite'] = ""; 
				if (isset($element_counter['models']) && $stringFieldsCount >= $models) {
					$import_galleries[$i]['models'] = explode(",", strip_tags($string[$models]));
				} else {
					$import_galleries[$i]['models'] = "";
				}

				if (isset($title_1) && $stringFieldsCount >= $title_1) $import_galleries[$i]['additional_titles'][] = substr($string[$title_1],0, 255);
				if (isset($title_2) && $stringFieldsCount >= $title_2) $import_galleries[$i]['additional_titles'][] = substr($string[$title_2],0, 255);
				if (isset($title_3) && $stringFieldsCount >= $title_3) $import_galleries[$i]['additional_titles'][] = substr($string[$title_3],0, 255);
				if (isset($title_4) && $stringFieldsCount >= $title_4) $import_galleries[$i]['additional_titles'][] = substr($string[$title_4],0, 255);
				if (isset($embed) && $stringFieldsCount >= $embed) {
					$import_galleries[$i]['embed'] = $string[$embed];
				}
				if (isset($images) && $stringFieldsCount >= $images) {
					if($images_delim) $import_galleries[$i]['images'] = explode($images_delim, $string[$images]);
				}
				if (isset($duration) && $stringFieldsCount >= $duration) $import_galleries[$i]['duration'] = (int)$string[$duration];
				if (isset($gallery_original_id) && $stringFieldsCount >= $gallery_original_id) $import_galleries[$i]['gallery_original_id'] = (int)$string[$gallery_original_id];

				
			}	
			$i++;
		}
		// var_dump($import_galleries);
		return $import_galleries;
	}	

	function sanitize_non_utf($text) {
		$find[] = html_entity_decode('&#x2013;', ENT_COMPAT, 'UTF-8');  // en dash
		$find[] = html_entity_decode('&#x2014;', ENT_COMPAT, 'UTF-8');  // em dash
		$find[] = html_entity_decode('&#x2018;', ENT_COMPAT, 'UTF-8');  // left side single smart quote
		$find[] = html_entity_decode('&#x2019;', ENT_COMPAT, 'UTF-8');  // right side single smart quote
		$find[] = html_entity_decode('&#x201C;', ENT_COMPAT, 'UTF-8');  // left side double smart quote
		$find[] = html_entity_decode('&#x201D;', ENT_COMPAT, 'UTF-8');  // right side double smart quote
		$find[] = html_entity_decode('&#x201E;', ENT_COMPAT, 'UTF-8');  // low double smart quote
		$find[] = html_entity_decode('&#x2020;', ENT_COMPAT, 'UTF-8');  // dagger
		$find[] = html_entity_decode('&#x2021;', ENT_COMPAT, 'UTF-8');  // double dagger
		$find[] = html_entity_decode('&#x2022;', ENT_COMPAT, 'UTF-8');  // bullet
		$find[] = html_entity_decode('&#x201A;', ENT_COMPAT, 'UTF-8');  // single low smart quote
		$find[] = html_entity_decode('&#x2026;', ENT_COMPAT, 'UTF-8');  // elipsis (...)
		$find[] = html_entity_decode('&#x2030;', ENT_COMPAT, 'UTF-8');  // per thousand
		$find[] = html_entity_decode('&#x20AC;', ENT_COMPAT, 'UTF-8');  // euro
		$find[] = html_entity_decode('&#x2122;', ENT_COMPAT, 'UTF-8');  // TM
					  
		$replace[] = "-";
		$replace[] = "-";
		$replace[] = "'";
		$replace[] = "'";
		$replace[] = '"';
		$replace[] = '"';
		$replace[] = '"';
		$replace[] = '+';
		$replace[] = '++';
		$replace[] = '-';
		$replace[] = "'";
		$replace[] = "...";
		$replace[] = "";
		$replace[] = "E";
		$replace[] = "tm";

		$text = str_replace($find, $replace, $text);
		$text = trim($text);
		return $text;

	}
	function msword_conversion($str)
	{
		$str = str_replace(chr(130), ',', $str);    // baseline single quote
		$str = str_replace(chr(131), 'NLG', $str);  // florin
		$str = str_replace(chr(132), '"', $str);    // baseline double quote
		$str = str_replace(chr(133), '...', $str);  // ellipsis
		$str = str_replace(chr(134), '**', $str);   // dagger (a second footnote)
		$str = str_replace(chr(135), '***', $str);  // double dagger (a third footnote)
		$str = str_replace(chr(136), '^', $str);    // circumflex accent
		$str = str_replace(chr(137), 'o/oo', $str); // permile
		$str = str_replace(chr(138), 'Sh', $str);   // S Hacek
		$str = str_replace(chr(139), '<', $str);    // left single guillemet
		// $str = str_replace(chr(140), 'OE', $str);   // OE ligature
		$str = str_replace(chr(145), "'", $str);    // left single quote
		$str = str_replace(chr(146), "'", $str);    // right single quote
		// $str = str_replace(chr(147), '"', $str);    // left double quote
		// $str = str_replace(chr(148), '"', $str);    // right double quote
		$str = str_replace(chr(149), '-', $str);    // bullet
		$str = str_replace(chr(150), '-–', $str);    // endash
		$str = str_replace(chr(151), '--', $str);   // emdash
		// $str = str_replace(chr(152), '~', $str);    // tilde accent
		// $str = str_replace(chr(153), '(TM)', $str); // trademark ligature
		$str = str_replace(chr(154), 'sh', $str);   // s Hacek
		$str = str_replace(chr(155), '>', $str);    // right single guillemet
		// $str = str_replace(chr(156), 'oe', $str);   // oe ligature
		$str = str_replace(chr(159), 'Y', $str);    // Y Dieresis
		$str = str_replace('°C', '&deg;C', $str);    // Celcius is used quite a lot so it makes sense to add this in
		$str = str_replace('£', '&pound;', $str);
		$str = str_replace("'", "'", $str);
		$str = str_replace('"', '"', $str);
		$str = str_replace('–', '&ndash;', $str);

		return $str;
	}

	function LoadGif($imgname)
	{
	    /* Attempt to open */
	    $im = @imagecreatefromgif($imgname);

	    /* See if it failed */
	    if(!$im)
	    {
	        /* Create a blank image */
	        $im = imagecreatetruecolor (150, 30);
	        $bgc = imagecolorallocate ($im, 255, 255, 255);
	        $tc = imagecolorallocate ($im, 0, 0, 0);

	        imagefilledrectangle ($im, 0, 0, 150, 30, $bgc);

	        /* Output an error message */
	        imagestring ($im, 1, 5, 5, 'Error loading ' . $imgname, $tc);
	    }

	    return $im;
	}

	function setTitleUsedOnSite($title_id, $gal_id, $site_id) {
		$result = false;
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$db->debug = true;
		$updated_on = time();
		$sql = "UPDATE additional_titles
				SET used_on = ?
				WHERE id = ? AND gal_id = ?";
		if ($stmt = $db->prepare($sql)) {
			$binded = $stmt->bind_param("iii", $site_id, $title_id, $gal_id);
			
			if ($binded) { 
				if ($stmt->execute()) { 
					$stmt->close(); 
					$result = true;
				}
			}
		}
		return $result;
	}

	function queryGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $query_on, $title_id = false) {
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$db->query("SET NAMES 'utf8';");
		$sql = "INSERT INTO sites_galleries_make_query 
				(site_id, gal_id, title, gallery_unique, main_thumb, query_on) 
				VALUE (?, ?, ?, ?, ?, ?)";
		if ($stmt = $db->prepare($sql)) {
			$binded = $stmt->bind_param("iisiii", $site_id, $gal_id, $title, $gallery_unique, $main_thumb, $query_on);
			
			if ($binded) { 
				if ($stmt->execute()) { 
					$stmt->close(); 
					if ($title_id > 0) {
						setTitleUsedOnSite($title_id, $gal_id, $site_id);
					}
					return true; 
				}	
				else $log = new Logger ("queryGalleryToSite: execute error: '".$stmt->error."'",true);
			} else $log = new Logger ("queryGalleryToSite: bind error: '".$stmt->error."'",true);
			$stmt->close();
		} else $log = new Logger ("queryGalleryToSite: prepare error: '".$db->error."'",true);
		// var_dump($db->error);
		
		return false;
	}

	function removeFromQuery($site_id, $gal_id) {
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$result = false;
		$sql = "DELETE FROM sites_galleries_make_query
				WHERE site_id = ? AND gal_id = ?;";
		if ($stmt = $db->prepare($sql)) { 
			$stmt->bind_param("ii", $site_id, $gal_id); 
			if($stmt->execute()) $result = true;
			$stmt->close(); 
		}
		return $result;
	}


	function getSitesQueryDates($site_id, $from_date = false, $to_date = false) {
		$result = false;
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$sql = "SELECT gal_id, query_on
				FROM sites_galleries_make_query
				WHERE site_id = ?";
		if($from_date && $to_date) { $sql .= " AND (query_on >= ? and query_on <= ?)"; }
		$sql .= " ORDER BY query_on ASC";
		if ($stmt = $db->prepare($sql)) {
			if($from_date && $to_date) { $stmt->bind_param("iii",$site_id,$from_date,$to_date); }
			else { $stmt->bind_param("i",$site_id); }
			if($stmt->execute()) {
				$stmt->bind_result($gal_id, $query_on);
		    	while ($stmt->fetch()) { $result[$gal_id] = $query_on; 
		    		// echo $gal_id .":".$query_on."\n";
		    	}
			} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed");
			$stmt->close();	
		}
		return $result;	
	}


	function getSitesLastQueryDate($site_id) {
		$result = false;
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$sql = "SELECT MAX(query_on)
				FROM sites_galleries_make_query
				WHERE site_id = ?";
		if ($stmt = $db->prepare($sql)) {
			$stmt->bind_param("i",$site_id);
			if($stmt->execute()) {
				$stmt->bind_result($query_on);
		    	if ($stmt->fetch()) { $result = $query_on; 
		    		// echo $gal_id .":".$query_on."\n";
		    	}
			} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed");
			$stmt->close();	
		}
		return $result;	
	}

	function getSitesQueryByDay ($site_id, $day = 0) {
		$result = false;
		// сегодняшняя дата 00:00:00
		$current_date_time = strtotime(date("Y/m/d" ,time()));
		$day_time_increment = (int)$day * 60 * 60 * 24;
		$from_date = $current_date_time + $day_time_increment;
		$to_date = $from_date + (60*60*24) -1;
		return getSitesQueryDates($site_id, $from_date, $to_date);
	}

	function getSitesQueryCountByDay ($site_id, $day = 0) {
		$result = false;
		// сегодняшняя дата 00:00:00
		$current_date_time = strtotime(date("Y/m/d" ,time()));
		$day_time_increment = (int)$day * 60 * 60 * 24;
		$from_date = $current_date_time + $day_time_increment;
		$to_date = $from_date + (60*60*24) -1;
		// echo $from_date .":".$to_date."\n";
		return getSitesQuery($site_id, $from_date, $to_date);
	}

	function getSitesQuery($site_id, $from_date = false, $to_date = false) {
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$result = false;
		$sql = "(SELECT count(gal_id)
				FROM sites_galleries_make_query
				WHERE site_id = ?";
		if($from_date && $to_date) { $sql .= " AND (query_on >= ? and query_on <= ?)"; }
		$sql .= ")
				UNION ALL
				(SELECT count(gal_id)
				FROM writers_titles
				WHERE site_id = ? AND used = 0";
		if($from_date && $to_date) { $sql .= " AND (deadline >= ? and deadline <= ?)"; }				
		$sql .= ")";
		
		// var_dump($sql);
		if ($stmt = $db->prepare($sql)) {

			if($from_date && $to_date) { $stmt->bind_param("iiiiii",$site_id,$from_date,$to_date,$site_id,$from_date,$to_date); }
			else { $stmt->bind_param("ii",$site_id, $site_id); }

			if($stmt->execute()) {
				$stmt->bind_result($count);
		    	while ($stmt->fetch()) {
		    		$result += $count;
		    	}
			} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed");
			$stmt->close();	
		}
		// var_dump($db->error);
		
		return $result;	
	}

	function clearGrabQuery() {
		$result = false;
		$db = DB::get();
		if($db) {
			$sql = "TRUNCATE main_query;";
			if ($stmt = $db->prepare($sql)) { 
				if($stmt->execute()) $result = true;
				$stmt->close(); 
			}
		} else {
			$log = new Logger(__METHOD__.": нет коннекта к базе", true);
		}

		return $result;
	}

	function getFullMakeQuery() {
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);

		$result = false;
		if (!$db->connect_error) {
			$db->query("SET NAMES 'utf8';");
			$time = time();
			$sql = "SELECT sites_galleries_make_query.site_id, sites_galleries_make_query.gal_id, sites_galleries_make_query.title, sites_galleries_make_query.gallery_unique, sites_galleries_make_query.main_thumb, sites_galleries_make_query.query_on,
							sites.site_name
					FROM sites_galleries_make_query
					LEFT JOIN sites ON sites_galleries_make_query.site_id = sites.site_id
					ORDER BY sites_galleries_make_query.site_id ASC, sites_galleries_make_query.query_on ASC";
			if ($stmt = $db->prepare($sql)) {

				if($stmt->execute()) {
					$stmt->bind_result($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $query_on, $site_name);
					$count = 0;
			    	while ($stmt->fetch()) {
			    		$result[$count]['site_id'] = $site_id;
			    		$result[$count]['gal_id'] = $gal_id;
			    		$result[$count]['title'] = $title;
			    		$result[$count]['gallery_unique'] = $gallery_unique;
			    		$result[$count]['main_thumb'] = $main_thumb;
			    		$result[$count]['query_on'] = $query_on;
			    		$result[$count]['site_name'] = $site_name;
			    		$count++;
			    		
			    	}
				} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed: '".$stmt->error."'", true);
				$stmt->close();
			} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed: '".$db->error."'", true);	
		} else $log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);	
		return $result;	
	}


	function checkSitesQuery() {
		$db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		$db->query("SET NAMES 'utf8';");
		if (!$db->connect_error) {
			$time = time();
			$sql = "SELECT site_id, gal_id, title, gallery_unique, main_thumb
					FROM sites_galleries_make_query
					WHERE query_on < ".$time."
					LIMIT 0, 15;";
			if ($stmt = $db->prepare($sql)) {

				if($stmt->execute()) {
					$stmt->bind_result($site_id, $gal_id, $title, $gallery_unique, $main_thumb);
			    	while ($stmt->fetch()) {
			    		addGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb);
			    		removeFromQuery($site_id, $gal_id);
			    	}
				} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed: '".$stmt->error."'", true);
				$stmt->close();
			} else $log = new Logger ("Sites Query Cron: checkSitesQuery: stmt execute failed: '".$db->error."'", true);	
		} else $log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);	
	}

	function getGalleryUsedTimes($gal_id = false) {
		$result = false;
		$db = DB::get();
		if(!$gal_id) { $gal_id = (int)$gal_id; }


		$sql = "SELECT galleries.gal_id, count(sites_galleries.gal_id)
				FROM galleries
				LEFT JOIN sites_galleries ON galleries.gal_id = sites_galleries.gal_id
				WHERE galleries.gal_status = 'OK' AND times_used_on_sites = 0 ";
		if($gal_id) $sql .= " AND galleries.gal_id = ? ";
		$sql .= " GROUP BY galleries.gal_id";	
		if ($stmt = $db->prepare($sql)) {
			if($gal_id) $bind_param("i", $gal_id);
			if($stmt->execute()) {
				$stmt->bind_result($gal_id, $gal_used_counter);
		    	while ($stmt->fetch()) {
		    		$result[$gal_id] = $gal_used_counter;
		    	}
			} else $log = new Logger (__METHOD__.": stmt execute failed: '".$stmt->error."'", true);
			$stmt->close();
		} else $log = new Logger (__METHOD__.": stmt execute failed: '".$db->error."'", true);	

		return $result;
	}




	function updateGalleryUsedCounter($gal_id = false) {
		$db = DB::get();
		if (!$db->connect_error) {
			$time = time();
			$galleries = getGalleryUsedTimes($gal_id);
			
			if(is_array($galleries)) {
				$sql = 'UPDATE galleries SET times_used_on_sites = ?
						WHERE gal_id = ?;';
				if ($stmt = $db->prepare($sql)) {
					foreach($galleries as $gallery_id => $gal_used_counter) {
						echo "Gallery:".$gallery_id.":".$gal_used_counter."<br>";
						if ($stmt->bind_param("ii", $gal_used_counter, $gallery_id)) $stmt->execute();
					}
					$stmt->close();
				}
			}
		} else $log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);	
	}

	function deleteGalleryFromSite($site_id, $gal_id) {
		global $cache_worker, $db;
		
		$result = false;

		if ($db->_db) {
			if(!$cache_worker) {
				$cache_worker = new CacheRebuilder($db->_db);
			}
			$gallery = new Galleries($db->_db);
			$local_id = $gallery->getLocalId($site_id, $gal_id);

			if ($local_id) {
				// проверить, если галера уникальная
				$is_deleted = $cache_worker->deleteGalleryFromSite($site_id, $local_id);
				if ($is_deleted) {
					$gallery->deleteFromSite($site_id, $gal_id);
					$result = true;
				}
			}
				
			
		} else {
			// нет коннекта к MySQL
		}
		return $result;
		
	}

	function old_addGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $title_id = false) {
		global $cache_worker, $db;
		$result = false;

		$init_site_cache_key = SCRIPT_PRE.":init_sites_tags_models"; //hset (заменить на сортированый список)
		$init_additional_site_cahce_key = SCRIPT_PRE.":init_gallery_additional_cache"; // сортированый список

		$caching_servers = array();

		$current_time = time();

		if ((int)$site_id && (int)$gal_id && $title !="") {

			if(!$cache_worker) {
				$cache_worker = new CacheRebuilder($db->_db);
			}

			$siteUtils = new Sites($db->_db);
			$siteInformation = $siteUtils->SiteInformation($site_id);

			// перечисления серверов
			$caching_servers[] = $siteInformation['redis_server'];
			if(!in_array($siteInformation['additional_redis_server'], $caching_servers)) {
				$caching_servers[] = $siteInformation['additional_redis_server'];
			}


			$local_id = $siteUtils->AddGalleryId($site_id, $gal_id, $title, $siteInformation['urlLength'],$gallery_unique, $main_thumb);	

			if ($local_id) {

				$gallery_w = new Galleries($db->_db);
				$gal_models = $gallery_w->getGalleryModels($gal_id);
				$gal_type = $gallery_w->getGalleryType($gal_id);

				$start = get_time();
				$models_w = new CModels($db->_db);
				

				if($gal_models) {
					foreach ($gal_models as $model_id) {
						// var_dump($site_id, $model_id, $local_id, $gal_type);
						$models_w->addOneModelGalleryToSite($site_id, $model_id, $local_id, $gal_type);

					}
				}

				// addModelsToSite заменить на работу в цикле с plusOneModelGallery
				

				$finish = get_time();
				$exec_time = $finish - $start;
				$log = new Logger("CModels::addModelsToSite: Redis Init exec time: ".$exec_time);

				// добавление тегов тоже необходимо исправить!

				$cache_worker->addNewGalleries($site_id, array($local_id => $gal_id));

				$siteUtils->switchSite($site_id);
				
				$cache_worker->server_cacheGlobalGallery($gal_id);

				
				$result = $local_id;

				if($result) {
					$siteUtils->siteUpdated();
					if ($title_id) { setTitleUsedOnSite($title_id, $gal_id, $site_id); }
					addGalleryCacheQuery($site_id, $gal_id, $caching_servers);
				}				
			}
		}
		return $result;
	}



	function addGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $title_id = false) {
		$result = false;

		$current_time = time();
		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;
		$gallery_unique = (int)$gallery_unique;

		if ($site_id > 0 && $gal_id > 0 && $title !="") {

			$db = DB::get();

			if($db) {
				$use_adodb_connection = false;

				$pdo_db = new db_access();

				$sites_worker = new Sites($pdo_db->_db);
				$gallery_worker = new Galleries($pdo_db->_db);

				$site_info = $sites_worker->SiteInformation($site_id);
				$gallery_info = $gallery_worker->getMainGalleryInfo($gal_id);

				if($site_info && $gallery_info) {

					$gallery_id = $gallery_info['id'];
					if(!$title || $title == "") {
						$title = $gallery_info['title'];
					}
					$title = str_replace("\\", "", $title);
					$gal_status = $gallery_info['status'];
					$gal_type = strtolower($gallery_info['type']);
					$gal_niche = $gallery_info['niche'];
					$gal_paysite_id = (int)$gallery_info['paysite']['id'];
					$gal_is_embed = $gallery_info['embed_flag'];
					$video_embed = $gallery_info['video_embed'];


					$url_length = $site_info['urlLength'];

					if(preg_match("#^(movies|video)$#im", $gal_type)) $sql_content_counter = "video_count";
					elseif(preg_match("#^(pics|gif)$#im", $gal_type)) $sql_content_counter = "gals_count";
					else {
						// чота херня какая-то
						// если какой-то другой тип галеры, либо добавить новые, либо забить уже
					}

					if($gal_status == 'OK') {


						$caching_servers = $sites_worker->getSiteAdditionalCachingServers($site_id);
						$redis_server = $site_info['redis_server'];
						
						$gallery_tags = $gallery_worker->getGalleryTags($gal_id);
						$gallery_models = $gallery_worker->getGalleryModels($gal_id);

						if($site_info['vcdn_type'] == 'static' && !$gal_is_embed) {
							if(!$gallery_worker->isVideoCdnSynced($gal_id)) {
								$gallery_worker->insertVideosToCdnQuery($gal_id);
							}
						}

						// $local_id = $siteUtils->AddGalleryId($site_id, $gal_id, $title, $url_length, $gallery_unique, $main_thumb);	

						$url_desc = nonEngTitleToLatin($title);
						$url_desc = strtolower($url_desc);
						$url_desc = preg_replace ("/[^a-z0-9\s]/","",$url_desc);
						$url_desc = preg_replace ("/\s+/", " ", $url_desc);
						$url_desc = str_replace (" ", "-", $url_desc);
						$url_desc = trim(substr($url_desc, 0, $url_length));

						$added_on = time();
						$updated_on = time();
						$local_id = false;

						$db->autocommit(false);

						$all_query_ok = true;

						$sql = "INSERT INTO site_". $site_id . " 
								(gal_id, gal_type, gal_paysite, url_desc, time_added, own_title, own_main_thumb) 
								VALUE (?, ?, ?, ?, ?, ?, ?)";

						if ($stmt = $db->prepare($sql)) {
							if ($stmt->bind_param("isisisi", $gallery_id, $gal_type, $gal_paysite_id, $url_desc, $added_on, $title, $main_thumb)) {
								if ($stmt->execute()) { 
									$local_id = $stmt->insert_id;
									
								} else {  
									$all_query_ok = false; 
									$log = new Logger(__METHOD__.": Site: '".$site_id."', SQL execute error: '".$stmt->error."'", true);	  
								}
							} else {
								$all_query_ok = false;
								$log = new Logger(__METHOD__.": Bind param error: '".$stmt->error."'", true);	  
							}
							$stmt->close();
						} else {
							$all_query_ok = false; 
							$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
						}

						$sql_site_cache = false;


						if ($local_id) {

							$cache_gallery['id'] = $local_id;
							$cache_gallery['global_id'] = $gal_id;
							$cache_gallery['url_desc'] = $url_desc;
							$cache_gallery['time_added'] = $added_on;
							$cache_gallery['gal_type'] = $gal_type;
							$cache_gallery['gal_paysite'] = $gal_paysite_id;
							$cache_gallery['rating'] = 0;
							$cache_gallery['pageviews'] = 0;
							$cache_gallery['own_title'] = $title;
							$cache_gallery['own_main_thumb'] = $main_thumb;
							$cache_gallery['video_embed'] = $video_embed;
							

							$random_number = mt_rand(1,150);

							// увеличение числа раз использований на сайтах
							$sql =  'UPDATE galleries SET times_used_on_sites = times_used_on_sites + 1';
							if ($gallery_unique > 0) {
								$sql .= ', unique_gal = '.$gallery_unique.' ';							
							}
							$sql .= ' WHERE gal_id = '.$gal_id.';';
							$db->query($sql) ? null : $all_query_ok=false;
							// var_dump($all_query_ok);

							if($all_query_ok) {
								// апдейт таблиц сайтов-галер
								$sql = "INSERT INTO `sites_galleries` (gal_id, site_id, time_added, random_number)
										VALUES (".$gal_id.", ".$site_id.", ".$added_on.", ".$random_number.");";
								$db->query($sql) ? null : $all_query_ok=false;
								// var_dump($all_query_ok);

								$item_id = 0;
								$item_type = 'gallery';
								$change_type = 'added';

								$sql_main_server_cache[] = "(".$site_id.", ". $redis_server.", ".$gal_id.", ".$local_id.", 
														  '".$gal_type."', '".$item_type."', '".$change_type."', ".$item_id.", 
														  ".$added_on.", ".$updated_on.")";

								foreach($caching_servers as $cache_server_id) {
									$sql_site_cache[] = "(".$site_id.", ". $cache_server_id.", ".$gal_id.", ".$local_id.", 
														  '".$gal_type."', '".$item_type."', '".$change_type."', ".$item_id.", 
														  ".$added_on.", ".$updated_on.")";
								}
							}

							// echo "after sites_galleries insert:";
							// var_dump($all_query_ok);

							$sites_galleries_worker = new SitesGalleries;
							if($all_query_ok) {
								// теги сначала добавляются в таблицу тегов сайта
								if($gallery_tags && is_array($gallery_tags)) {
									// фикс таблицы со всеми тегами (на всякий случай)
									$sites_worker->addAllTagsToSite($site_id);
									$add_tags_result = $sites_galleries_worker->addTagToSiteGallery($site_id, $gal_id, $local_id, $gal_type, $gallery_tags, $added_on, $updated_on, $db);
									$add_tags_result ? $cache_gallery['gal_tags'] = $add_tags_result : $all_query_ok = false;
								}
							}
							// echo "after addAllTagsToSite:";
							// var_dump($all_query_ok);
							if($all_query_ok) {
								// теги сначала добавляются в таблицу тегов сайта
								if($gallery_models && is_array($gallery_models)) {
									$sites_worker->addAllModelsToSite($site_id);
									$add_models_result = $sites_galleries_worker->addModelToSiteGallery($site_id, $gal_id, $local_id, $gal_type, $gallery_models, $added_on, $updated_on, $db);
									$add_models_result ? $cache_gallery['gal_models'] = $add_models_result : $all_query_ok = false;
								}
							}
							// echo "after addAllModelsToSite:";
							// var_dump($all_query_ok);

							if($all_query_ok) {
								$sites_worker->addAllSourcesToSite($site_id);
								$add_models_result = $sites_galleries_worker->addPaysiteToSiteGallery($site_id, $gal_type, $gal_paysite_id, $added_on, $updated_on, $db);
								
							}
							// echo "after addAllSourcesToSite:";
							// var_dump($all_query_ok);
							if($all_query_ok) {
								if($sql_site_cache) {
									$all_query_ok = insertCacheQueryByArray($sql_site_cache, $db);
								}
							}
							// echo "after insertCacheQueryByArray:";
							// var_dump($all_query_ok);
							if($all_query_ok) {
								$db->commit();
								// cacheit
								$cache_worker = new CacheRebuilder();
								// $cache_worker->addNewGalleries($site_id, array($local_id => $gal_id));

								$gallery_cached = $cache_worker->new_addNewGalleries($site_id, $redis_server, array($local_id => $cache_gallery));
								if(!$gallery_cached) {
									$all_query_ok = insertCacheQueryByArray($sql_main_server_cache);
								}

								// $cache_worker->server_cacheGlobalGallery($gal_id);

								$sites_worker->switchSite($site_id);
								$sites_worker->siteUpdated();

								$result = $local_id;
							} else {
								$db->rollback();
								$log = new Logger("Провалено добавление галеры на сайт ".$site_id.", галера ".$gal_id.". Транзакция откачена.", true);
								// echo "Rolledback";
							}
							
							$db->autocommit(true);

						}

					} else {
						$log = new Logger(__METHOD__.": попытка добавить галеру #".$gal_id." в статусе '".$gal_status."' на сайт #".$site_id, true);
					}
					
				} else {
					$log = new Logger(__METHOD__.": Ошибка добавления, галлерея #'".$gal_id."' , или сайт #'".$site_id."' отсутствуют в базе", true);
				}
			} else {
				$log = new Logger(__METHOD__.": Ошибка добавления, нет коннекта к БД", true);	
			}

			
		} else {
			$log = new Logger(__METHOD__.": Ошибка добавления, неверные параметры site_id, gal_id или title пустой", true);
		}

		return $result;
	}


	function initSitesTagAndModels() {
		global $cache_worker, $db;
		/*
		$redis = new Redis();
		$connected = $redis->connect(REDIS_IP, REDIS_PORT,0.5);
		if ($connected) { 
			$redis->select(REDIS_SERVER); 

			$key = SCRIPT_PRE.":init_sites_tags_models";

			$sites = $redis->hGetAll($key);

			if($sites && is_array($sites) && count($sites)) {
				$redis->del($key);
				if(!$cache_worker) { $cache_worker = new CacheRebuilder($db->_db); 	}
				foreach ($sites as $site_id => $elem) {
					$start = get_time();
					$cache_worker->initializeSiteModels($site_id);
					$finish = get_time();
					$exec_time = $finish - $start;
					$log = new Logger("Site #".$site_id.", Cache::initializeSiteModels exec time: ".$exec_time);

					$start = get_time();
					$cache_worker->initializeSiteTags($site_id);
					$finish = get_time();
					$exec_time = $finish - $start;
					$log = new Logger("Site #".$site_id.", Cache::initializeSiteTags exec time: ".$exec_time);		
				}
			}
		}
		*/
	}

	// соеденение к базе может быть передано по ссылке. чтобы можно было делать транзакции в mysqli
	function addGalleryCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $caching_servers, &$db = NULL) { //caching_servers - массив
		$result = false;

		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;
		$gal_local_id = (int)$gal_local_id;
		$cache_server_id = false;

		if($db == NULL) {
			$db = DB::get();
		}

		if($caching_servers && is_array($caching_servers)) {


			if (!$db->connect_error && $site_id > 0 && $gal_id > 0 && $gal_local_id > 0) {

				$added_on = time();
				$updated_on = $added_on;
				$item_type = 'gallery';
				$change_type = 'added';
				$item_id = 0;

				$sql = "INSERT INTO `sites_cache_query` (site_id, cache_server_id, gal_id, gal_local_id, gal_type, 
														 item_type, change_type, item_id, added_on, updated_on, 
														 error_msg) 
							(
								SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ''
								FROM DUAL
								WHERE NOT EXISTS (
									SELECT id 
									FROM `sites_cache_query` 
					      			WHERE site_id = ? 
					      			AND cache_server_id = ?
					      			AND gal_id = ?
					      			AND item_type = 'gallery'
					      			AND change_type = 'added'
					      		) LIMIT 1
							)";
				if ($stmt = $db->prepare($sql)) {
					if ($stmt->bind_param("iiiisssiiiiii", $site_id, $cache_server_id, $gal_id, $gal_local_id, $gal_type,
														 $item_type, $change_type, $item_id, $added_on, $updated_on, 
													     $site_id, $cache_server_id, $gal_id)) {
						foreach($caching_servers as $cache_server_id) {
							if(!CachingServers::isServerExists($cache_server_id)) {
								$log = new Logger(__METHOD__.": Сервер кеширования '".$cache_server_id."' не существует", true);
								return false;
							}
							if($stmt->execute()) $result = true;
							else {
								$log = new Logger(__METHOD__.": Not inserted to caching query: site_id: ".$site_id.", gal_id: ".$gal_id.", cache_server_id ".$cache_server_id, true );
							}
						}
					} else {
						$log = new Logger (__METHOD__.": STMT bind params error '".$stmt->error."'", true);				
					}
					$stmt->close();
				} else {
					echo "Failed: Prepare STMT\n";
					$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
				}

			} else {
				$log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Нет массива кеширующих серверов", true);
		}

		return $result;
	}

		// соеденение к базе может быть передано по ссылке. чтобы можно было делать транзакции в mysqli
	function insertCacheQueryByArray($query, &$db = NULL) { //caching_servers - массив
		$result = false;

		$cache_server_id = false;

		if($db == NULL) {
			$db = DB::get();
		}

		if($query && is_array($query)) {

			$sql_query_str = implode(",", $query);

			if (!$db->connect_error && $sql_query_str) {



				$sql = "INSERT INTO `sites_cache_query` (site_id, cache_server_id, gal_id, gal_local_id, gal_type, 
														 item_type, change_type, item_id, added_on, updated_on) 
						VALUES ".$sql_query_str;

						// var_dump($sql);
				if ($stmt = $db->prepare($sql)) {
					if ($stmt->execute()) {
						$result = true;
					}
					$stmt->close();
				} else {
					echo "Failed: Prepare STMT\n";
					$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
				}

			} else {
				$log = new Logger (__METHOD__.": MySQL connect error: '".$db->connect_error."'", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Нет массива кеширующих серверов", true);
		}

		return $result;
	}

	function deleteGalleryCacheQuery($gal_id) {
		$result = false;

		$gal_id = (int)$gal_id;

		$db = DB::get();

		if (!$db->connect_error && $gal_id) {

			$added_on = time();
			$updated_on = $added_on;

			$sql = "DELETE 
					FROM `sites_cache_query` 
				    WHERE gal_id = ?";
			if ($stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("i", $gal_id)) {
					if($stmt->execute()) $result = true;
					else {
						$log = new Logger(__METHOD__.":  Not deleted from caching query: site_id: ".$site_id.", gal_id: ".$gal_id.", cache_server_id ".$cache_server_id, true );
					}
				} else {
					$log = new Logger (__METHOD__.": STMT bind params error '".$stmt->error."'", true);				
				}

				if($stmt->execute()) $result = true;
				else {
					$log = new Logger(__METHOD__.": Not deleted from caching query: site_id: ".$site_id.", gal_id: ".$gal_id.", cache_server_id ".$cache_server_id, true );
				}
				$stmt->close();

			} else {
				echo "Failed: Prepare STMT\n";
				$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
			}

		} else {
			$log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);
		}

		return $result;
	}

	function deleteCacheQueryById($id) {
		$result = false;

		$id = (int)$id;

		$db = DB::get();

		if (!$db->connect_error && $id) {

			$added_on = time();
			$updated_on = $added_on;

			$sql = "DELETE 
					FROM `sites_cache_query` 
				    WHERE id = ?";
			if ($stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("i", $id)) {
					if($stmt->execute()) $result = true;
					else {
						$log = new Logger(__METHOD__.":  Not deleted from caching query: site_id: ".$site_id.", gal_id: ".$gal_id.", cache_server_id ".$cache_server_id, true );
					}
				} else {
					$log = new Logger (__METHOD__.": STMT bind params error '".$stmt->error."'", true);				
				}
				$stmt->close();

			} else {
				echo "Failed: Prepare STMT\n";
				$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
			}

		} else {
			$log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);
		}

		return $result;
	}

	function setCacheQueryErrorById($id, $error_msg) {
		$result = false;
		$id = (int)$id;
		if ($id > 0) {
			$db = DB::get();
			if ($db) {
				$sql = "UPDATE sites_cache_query
						SET error = ?, error_msg = ?, updated_on = ?
						WHERE id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					$updated_on = time();
					$error_msg = substr((string)$error_msg, 0, 128);
					if ($stmt->bind_param("isii", G_CACHE_QUERY_ERROR_STATUS, $error_msg, $updated_on, $id)) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind failed: ".$stmt->error, true);
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": STMT failed: ".$db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__.": No DB connect", true);
			}
		}
		return $result;
	}

	function deleteCacheQueryBySiteId($site_id) {
		$result = false;

		$site_id = (int)$site_id;

		$db = DB::get();

		if (!$db->connect_error && $site_id) {

			$added_on = time();
			$updated_on = $added_on;

			$sql = "DELETE 
					FROM `sites_cache_query` 
				    WHERE site_id = ?";
			if ($stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("i", $site_id)) {
					if($stmt->execute()) $result = true;
					else {
						$log = new Logger(__METHOD__.":  Not deleted from caching query: site_id: ".$site_id.", gal_id: ".$gal_id.", cache_server_id ".$cache_server_id, true );
					}
				} else {
					$log = new Logger (__METHOD__.": STMT bind params error '".$stmt->error."'", true);				
				}

				$stmt->close();

			} else {
				echo "Failed: Prepare STMT\n";
				$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
			}

		} else {
			$log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);
		}

		return $result;
	}


	function getGalleryCacheQuery($use_site_id = false, $max_galleries_count = 50) {
		$result = false;

		$use_site_id = (int)$use_site_id;
		$max_galleries_count = (int)$max_galleries_count;

		$db = DB::get();

		if ($db) {				

				$sql = "SELECT  id, site_id, cache_server_id, gal_id, gal_local_id, gal_type, 
								item_type, change_type, item_id, added_on, updated_on, error,
								error_msg
						FROM sites_cache_query
						WHERE error = 0 ";
				if($use_site_id) $sql .= " AND site_id = ? ";
				$sql .= " ORDER BY added_on ASC ";
				$sql .=	" LIMIT ".$max_galleries_count.";";
				$stmt = $db->prepare($sql);

				if($stmt) {
					if($use_site_id) {
						$stmt->bind_param("i", $use_site_id);
					}
					if($stmt->execute()) {
						$id = false;
						$site_id = false;
						$cache_server_id = false;
						$gal_id = false;
						$gal_local_id = false;
						$gal_type = false;
						$item_type = false;
						$change_type = false;
						$item_id = false;
						$added_on = false;
						$updated_on = false;
						$error = false;
						$error_message = false;

						$counter = 0;

						$stmt->bind_result($id, $site_id, $cache_server_id, $gal_id, $gal_local_id, 
										   $gal_type, $item_type, $change_type, $item_id, $added_on, 
										   $updated_on, $error, $error_message);
						if($stmt->fetch()) {
							$result['id'] = $id;
							$result['site_id'] = $site_id;
							$result['cache_server_id'] = $cache_server_id;							
							$result['gal_id'] = $gal_id;
							$result['gal_local_id'] = $gal_local_id;
							$result['gal_type'] = $gal_type;
							$result['item_type'] = $item_type;
							$result['change_type'] = $change_type;
							$result['item_id'] = $item_id;
							$result['added_on'] = $added_on;
							$result['updated_on'] = $updated_on;
							$result['error'] = $error;
							$result['error_message'] = $error_message;
							$counter++;
						}
					} else { 
						$log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error,true); 
					}
					$stmt->close();
				} else { 
					$log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); 
				}
		} else { 
			$log = new Logger(__METHOD__.": No DB connect", true); 
		}


		return $result;
	}

	function clearCacheQueryFromNoneGalType() { // удаление говна мамонта без gal_type из очереди кеша
		$result = false;

		$db = DB::get();

		if (!$db->connect_error) {

			$sql = "DELETE 
					FROM `sites_cache_query` 
				    WHERE gal_type = 'none'";
			if ($stmt = $db->prepare($sql)) {
				if($stmt->execute()) $result = true;
				else {
					$log = new Logger(__METHOD__.":  Not deleted from caching query: site_id: ".$site_id.", gal_id: ".$gal_id.", cache_server_id ".$cache_server_id, true );
				}
				$stmt->close();

			} else {
				echo "Failed: Prepare STMT\n";
				$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
			}

		} else {
			$log = new Logger ("Sites Query Cron: checkSitesQuery: MySQL connect error: '".$db->connect_error."'", true);
		}
	}

	function processCacheQuery($use_site_id = false, $max_galleries_count = 4) {
		static $execution_time = 0;
		static $execution_count = 0;
		$execution_safe_count = 5;
		$execution_safe_time = 5;
		// выбираем один элемент из очереди

		$galleries_to_query = getGalleryCacheQuery($use_site_id, $max_galleries_count);
		$cache_server_id = false;
		$result = false;

		if($galleries_to_query) {
			$execution_count++;
			$start_exec = get_time();

			$id = false;
			$item_type = false;
			$change_type = false;
			$site_id = false;
			$gal_id = false;
			$gal_local_id = false;
			$gal_type = false;
			$item_id = false;
			$added_on = false;
			$cache_server_id = false;

			$cache_processed = false;

			extract($galleries_to_query);

			// var_dump($galleries_to_query);
			if($id) {
				$cache_worker = new CacheRebuilder;
				$cache_error_msg = '';
				
				// проверяем тип очереди
				// var_dump($item_type, $change_type);
				if($item_type == 'gallery' && $change_type == 'added') {
					// если галера, проводим через "добавление"
					$sites_galleries_worker = new SitesGalleries;
					$gallery_info = $sites_galleries_worker->getSiteGalleriesInfo($site_id, $gal_id);
					if($gallery_info) {
						$cache_processed = $cache_worker->new_addNewGalleries($site_id, $cache_server_id, $gallery_info);
						// echo "Processed:". $cache_processed."<br>";
						unset($gallery_worker);	
						if (!$cache_processed) {
							$cache_error_msg = "Gallery add cache rebuild failed";
						}
					} else {
						$cache_error_msg = "Gallery info not found";
					}
					
				} elseif($item_type == 'gallery' && $change_type == 'removed' && $site_id == 0 && $gal_local_id == 0) {
					// глобальное удаление галеры из кэша
					if ($cache_worker->setServerId($cache_server_id)) {
						$cache_worker->startTransaction();
						$cache_processed = $cache_worker->deleteGalleryCacheTransaction($gal_id);
						$transaction_result = $cache_worker->executeTransaction();
						if (!$transaction_result) {
							$cache_processed = false;
							$cache_error_msg = "Redis pipeline execution failed";
						}
					} else {
						$cache_error_msg = "Redis connection failed";
					}

				}  elseif($item_type == 'gallery' && $change_type == 'removed' && !preg_match("#^(pics|movies|gif)$#", $gal_type)) {
					// глобальное удаление галеры из кэша
					$gal_types = array('movies','pics','gif');

					if ($cache_worker->setServerId($cache_server_id)) {
						$cache_worker->startTransaction();
						foreach ($gal_types as $gal_type) {
							$cache_processed = $cache_worker->cacheItemTransaction($item_type, $change_type, $site_id, $gal_id, 
																				   $gal_local_id, $gal_type, $item_id, $added_on);
						}
						$transaction_result = $cache_worker->executeTransaction();
						if (!$transaction_result) {
							$cache_processed = false;
							$cache_error_msg = "Redis pipeline execution failed";
						}
					} else {
						$cache_error_msg = "Redis connection failed";
					}

				} else {
					// если теги/модели, обрабатываем транзакцию
					if ($cache_worker->setServerId($cache_server_id)) {
						$cache_worker->startTransaction();
						$cache_processed = $cache_worker->cacheItemTransaction($item_type, $change_type, $site_id, $gal_id, 
																				$gal_local_id, $gal_type, $item_id, $added_on);
						$transaction_result = $cache_worker->executeTransaction();
						if (!$transaction_result) {
							$cache_processed = false;
							$cache_error_msg = "Redis pipeline execution failed";
						}
						if($cache_processed && $gal_id) {
							$cache_processed = $cache_worker->cacheGallery($gal_id, $cache_server_id);
							if (!$cache_processed) {
								$cache_error_msg = "Gallery global cache rebuild failed";
							}
						}
						if($cache_processed && $item_type == 'model' && $change_type == 'added') {
							$cache_processed = $cache_worker->cacheModel($item_id, $cache_server_id);
							if (!$cache_processed) {
								$cache_error_msg = "Model cache rebuild failed";
							}
						}
					} else {
						$cache_error_msg = "Redis connection failed";
					}
				}

				if($cache_processed) {
					$result = deleteCacheQueryById($id);
				} elseif($cache_error_msg) {
					setCacheQueryErrorById($id, $cache_error_msg);
				}

				$finish_exec = get_time();
				$fn_exec_time = $finish_exec - $start_exec;

				// проверяем время выполнения функции и счетчик рекурсии
				// если время исполнения ниже, счетчик рекурсии меньше и очередь была непустой
				// процессим очередь еще раз
				if($cache_processed && $fn_exec_time < $execution_safe_time && $execution_count < $execution_safe_count) {
					unset($cache_worker);
					processCacheQuery($use_site_id, $max_galleries_count);
				}				
			}


		}

		return $result;
	}

	define("G_CACHE_QUERY_ERROR_STATUS", 13);

	function updErrorMsgGalleryCacheQuery($site_id, $gal_id, $cache_server_id, $error_msg) {
		$result = false;

		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;
		$cache_server_id = (int)$cache_server_id;

		if ($gal_id && $site_id) {
			$sql = "UPDATE sites_cache_query SET error = ?, error_msg = ?
				    WHERE site_id = ? 
				    AND gal_id = ?
				    AND cache_server_id = ?";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->bind_param("isiii", G_CACHE_QUERY_ERROR_STATUS, $error_msg, $site_id, $gal_id, $cache_server_id)) {
							if(!$stmt->execute()) { $log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error,true); }
							else $result = true;
						} else { $log = new Logger(__METHOD__.": STMT Bind Param failed: ".$db->error,true); }	
						$stmt->close();				
					} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }
				} else { $log = new Logger(__METHOD__.": SQL string is empty", true); }
			} else { $log = new Logger(__METHOD__.": No DB connect", true); }
		}
		return $result;
	}

	function resetErrorMsgGalleryCacheQuery($site_id, $gal_id, $cache_server_id) {
		$result = false;

		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;
		$cache_server_id = (int)$cache_server_id;

		if ($gal_id && $site_id) {
			$sql = "UPDATE sites_cache_query SET error = 0, error_msg = ''
				    WHERE site_id = ? 
				    AND gal_id = ?
				    AND cache_server_id = ?";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->bind_param("iii", $site_id, $gal_id, $cache_server_id)) {
							if(!$stmt->execute()) { $log = new Logger(__METHOD__.": STMT execute failed: ".$stmt->error,true); }
							else $result = true;
						} else { $log = new Logger(__METHOD__.": STMT Bind Param failed: ".$db->error,true); }	
						$stmt->close();				
					} else { $log = new Logger(__METHOD__.": STMT failed: ".$db->error,true); }
				} else { $log = new Logger(__METHOD__.": SQL string is empty", true); }
			} else { $log = new Logger(__METHOD__.": No DB connect", true); }
		}
		return $result;
	}

	setlocale(LC_ALL, 'en_US.UTF8');
	function nonEngTitleToLatin($string) {
		$string = ru2Lat($string);
		return toAscii($string);
	}

	function toAscii($str, $replace=array(), $delimiter = '-') {
		
		if( !empty($replace) ) { $str = str_replace((array)$replace, ' ', $str); }
		 $clean = iconv('utf-8', 'ASCII//TRANSLIT', $str);
		 $clean = preg_replace("/[^a-zA-Z0-9\/_|+\s-]/", '', $clean);
		 $clean = strtolower(trim($clean));

		 return $clean;
	}

	function ru2Lat($string) {

    $converter = array(

        'а' => 'a',   'б' => 'b',   'в' => 'v',

        'г' => 'g',   'д' => 'd',   'е' => 'e',

        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',

        'и' => 'i',   'й' => 'y',   'к' => 'k',

        'л' => 'l',   'м' => 'm',   'н' => 'n',

        'о' => 'o',   'п' => 'p',   'р' => 'r',

        'с' => 's',   'т' => 't',   'у' => 'u',

        'ф' => 'f',   'х' => 'h',   'ц' => 'c',

        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',

        'ь' => '',  'ы' => 'y',   'ъ' => '',

        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

        

        'А' => 'A',   'Б' => 'B',   'В' => 'V',

        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',

        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',

        'И' => 'I',   'Й' => 'Y',   'К' => 'K',

        'Л' => 'L',   'М' => 'M',   'Н' => 'N',

        'О' => 'O',   'П' => 'P',   'Р' => 'R',

        'С' => 'S',   'Т' => 'T',   'У' => 'U',

        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',

        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',

        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',

        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',

    );

    return strtr($string, $converter);

}

function sanitizeString($title) {
	$title = trim($title);
	$title = sanitize_non_utf($title);
	// $title = msword_conversion($title);
	// ошибки в русской части utf-8 доделать
	$title = preg_replace("#\s{1,}#", " ", $title);
	return $title;
}

function resizeHorizGalleryThumbs($gal_id) {
	global $db;
	$gallery = new Galleries($db->_db);
	if($gallery->getGalleryType($gal_id) =='Pics') {
		$status = $gallery->getGalleryStatus($gal_id);
		if(preg_match("#^(uploaded|OK)$#", $status)) $result = $gallery->processHorizThumbs($gal_id);
	}
	return $result;
}

function getImageRatio($filename) {
	$result = false;
	if($filename) {
		$size = getimagesize($filename);
		if($size) {
			if((int)$size[0] && (int)$size[1]) $result = $size[0] / $size[1];
		} else {
			$log = new Logger(__METHOD__.": Ошибка получения ратио изображения из файла: '".$filename."'",false);
		}
	} else {
		// нет filename
	}

	return $result;
}

function cutTitleToTagCandidates($title) {
		global $stop_words;				

		$result = false;

		$title_break = preg_replace("#([^a-z0-9-\s])#im", " a ", $title);
		$title_break = preg_replace("#([\s]{2,})#im", " ", $title_break);
		$title_break = trim($title_break);
		$title_break = strtolower($title_break);
		$title_array = explode(" ", $title_break);
		$tags = array();
		$tags_two_words = array();
		if($title_array) {
			if(!is_array($tags)) {
				$tags = array();
			}
			$t_array_length = count($title_array);
			$previous_deleted = false;
			for ($i = 0; $i < $t_array_length; $i++) {
				$_word = $title_array[$i];
				if(strlen($_word) > 2 && !in_array($_word, $stop_words)) {
					$tags[] = $_word;
					if($i > 0 && !$previous_deleted) {
						$tags_two_words[] = $title_array[$i-1] ." ".$_word;
					}
					$previous_deleted = false;
				} else {
					$previous_deleted = true;
				}
							
			}
		}

		if(count($tags) > 0 || count($tags_two_words) > 0) {
			$result = array_merge($tags, $tags_two_words);
		}

		return $result;
}


function getGalleryInfoByGlobalId($gal_id) {

	$gallery_worker = new Galleries;
	$gallery_info = $gallery_worker->getMainGalleryInfo($gal_id);
    if ($gallery_info && is_array($gallery_info) && $gallery_info['status'] =='OK') {
            
            $gallery_info['models'] = serialize($gallery_worker->getGalleryModels($gal_id));
            $gallery_info['tags']   = serialize($gallery_worker->getGalleryTags($gal_id));
            $gallery_info['paysite_id'] = $gallery_info['paysite']['id'];

            if($gallery_info['type'] == 'Pics' || $gallery_info['type'] == 'gif' ) {
              $images = $gallery_worker->getAllImages($gal_id);
              if ($images && is_array($images)) {
                $gallery_info['images'] = serialize($images);
                $images_ratio = $gallery_worker->getImagesRatio($gal_id);

                if($images_ratio) {
                  $gallery_info['images_ratio'] = serialize($images_ratio);
                }
              } else {
               $log = new Logger ("Кэш: Галера ".$gal_id." не инициализирована - нет изображений, но статус ок", true);
              }
            } elseif($gallery_info['type'] == 'Movies' && (isset($gallery_info['video_url']) || isset($gallery_info['video_embed']))) {
              $images = $gallery_worker->getAllImages($gal_id);
              if ($images && is_array($images)) {
                $gallery_info['images'] = serialize($images);
              } else {
                $log = new Logger ("Кэш: Галера ".$gal_id." не инициализирована - нет изображений, но статус ок", true);
              }
          }
    } else {
        $log = new Logger ("Кэш: Галера ".$gal_id." не инициализирована - не найдена в базе", true);
    }
	return $gallery_info;

}
?>
