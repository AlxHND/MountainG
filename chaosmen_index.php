<?php
function pr($var, $html = false, $echo = true)
{
	if (!$echo) ob_start();
		else echo '<pre>';
	if (is_bool($var))
	{
		if ($var) echo 'TRUE';
			else echo 'FALSE';
	}
	else
	{
		$var = print_r ($var,true);
		if (!$html) echo $var;
		else
		{
			$var = str_replace('<br />', "<br />\n", $var);
			$var = str_replace('</p>', "</p>\n", $var);
			$var = str_replace('<ul>', "\n<ul>", $var);
			$var = str_replace('<li>', "\n<li>", $var);
			$var = htmlspecialchars($var);
			$var = wordwrap($var, 300);
			echo $var;
		}
	}
	if (!$echo)
	{
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	else echo '</pre>';
}



function string_sp2array($tags)
{
	return preg_split('/\s*\ \s*/',trim($tags),-1,PREG_SPLIT_NO_EMPTY);
}

function string2array($tags)
{
	return preg_split('/\s*,\s*/',trim($tags),-1,PREG_SPLIT_NO_EMPTY);
}

function array2string($tags)
{
	return implode(', ',$tags);
}
function http_curl_request($url, $content=array(), $curl_options = false) 
{
/*
	$file = 'temp/CONTENT'.md5($url);
	if ( is_file($file) )
		return file_get_contents($file);
// */
	if (defined('DEBUG_MODE'))
	{
		echo 'http_curl_request<br>';
		echo 'curl_version - <br>';
		pr(curl_version());
		echo 'URL = ' . $url .'<br>';
		echo 'content = <br>';
		pr($content);
		echo 'curl_options = <br>';
		pr($curl_options);
	}
	$send_file = false;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Trident/5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

	$p_url = parse_url($url);
	curl_setopt($ch, CURLOPT_REFERER, 'http://'.$p_url['host']);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept:	text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language:	en-us,en;q=0.5',
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

	if (defined('DEBUG_MODE'))
	{
		pr($return_value,true);
	}

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
/*
	if ($return_value['http_code'] == 302 && isset($redirect_url))
	{
		if (isset($curl_options['redirect_counter']) && $curl_options['redirect_counter'] > 5) return false;
		$curl_options['redirect_counter'] = isset($curl_options['redirect_counter'])?$curl_options['redirect_counter']+1:1;
		return http_curl_request($redirect_url, $content, $curl_options);
	}
*/
	if ($send_file === true)
	{
		unlink($tmpfname);
		rmdir($dir);
	}

//	file_put_contents($file, $return_value['request_content']);

	return $return_value['request_content'];
}







/*************************************************************************************/





class CParse_chaosmen
{
	var $site_rules = array(
		'url' => 'http://chaosmen.com/affiliate_index.php',
		'pagination_get' => '?page=',
	);

	function count_page( )
	{
		$oldSetting = libxml_use_internal_errors( true );
		libxml_clear_errors();

		//Загружаем страницу
		$html_content = http_curl_request($this->site_rules['url']);

		// Парсим внутреннюю страницу
		$html = new DOMDocument();
		$html->loadHTML ( $html_content );

		//Парсим
		$xpath = new DOMXPath( $html );

		//Получаем количество страниц на сайте
		$content = $xpath->query( '//select[@name="jgotopage"]/option ' );
		$total_pages = 0;
		foreach ( $content as $elm )
		{ 
			// получаем куда указывает ссылка
			$link = $elm->getAttribute( 'value' );

			// Поулчаем номер страницы
			$pos = strpos($link, $this->site_rules['pagination_get']);
			if ( $pos !== false )
			{
				$pos += strlen($this->site_rules['pagination_get']);
				$total_pages = max($total_pages, intval(substr($link,$pos)));
			}
		}
		return $total_pages;
	}

	function parse_main_page( $page_num = 0 )
	{
		$oldSetting = libxml_use_internal_errors( true );
		libxml_clear_errors();
		
		$site_url_parse = parse_url($this->site_rules['url']);
		$sie_url = $site_url_parse['scheme'] . '://' . $site_url_parse['host'] . '/';

		$main_page_url = $this->site_rules['url'].$this->site_rules['pagination_get'].$page_num;
		
		//Загружаем страницу
		$html_content = http_curl_request($main_page_url);

		// Парсим внутреннюю страницу
		$html = new DOMDocument();
		$html->loadHTML ( $html_content );

		//Парсим
		$xpath = new DOMXPath( $html );

		// Поулчаем все ссылки на внутринние страницы
		$content = $xpath->query( '//td[@class="thumbnailBorder"]/a' );

		$links = array();
		for ($i = 0; $i < $content->length; $i++)
		{
			$link = $sie_url . $content->item($i)->getAttribute( 'href' );
			if( strpos($link, 'video_index.php') !== false )
				$links[] = $link;
		}
		libxml_clear_errors();
		libxml_use_internal_errors( $oldSetting );
		return $links;
	}	




	function parse_internal_page( $internal_page_url )
	{

		$site_url_parse = parse_url($this->site_rules['url']);
		$sie_url = $site_url_parse['scheme'] . '://' . $site_url_parse['host'] . '/';

		$internal_page = array(
			'url' => $internal_page_url
			);

		$oldSetting = libxml_use_internal_errors( true );
		libxml_clear_errors();

		$html_content = http_curl_request($internal_page_url);

		// Парсим внутреннюю страницу
		$html = new DOMDocument();
		$html->loadHTML ( $html_content );

		//Парсим
		$xpath = new DOMXPath( $html );

		$content = $xpath->query( '//td[@class="previewBox"]/a' );
		foreach($content as $node)
		{ 
			$link = $node->getAttribute( 'href' );

			if( strpos($link, '640.wmv') !== false )
				$internal_page['wmv_640'] = $sie_url . $link;

			if( strpos($link, '.zip') !== false )
				$internal_page['zip'] = $sie_url . $link;
		}

		$content = $xpath->query( '//td[@class="displayTable"]/p/span[@class="pageHeadingGreen"]' );
		$title = trim($content->item(0)->nodeValue);

		$content = $xpath->query( '//td[@class="displayTable"]/p/span[@class="pageHeadingWhite"]' );
		$title .= trim($content->item(0)->nodeValue);

		$content = $xpath->query( '//td[@class="displayTable"]/p/span[@class="videoSub"]' );
		$title .= trim($content->item(0)->nodeValue);


		$internal_page['title'] = $title;

		$internal_page['timeparsing'] = time();
		//Восстанавливаем настройки
		libxml_clear_errors();
		libxml_use_internal_errors( $oldSetting );

		return $internal_page;
	}
}






















/*



надо по этой ссылке заходить на каждую страницу и собирать внутреннюю информацию со страниц. 
Какая инфа нужна на примере страницы http://chaosmen.com/affiliate_preview.php?video_id=1886:
Превью клип который на 640, т.е. http://chaosmen.com/af_preview/2013/1190-dom_peep/videos/1190-chaosmen_dom_peep_preview_640.wmv
Зип файл на картинки, т.е. в данном случае:
 http://chaosmen.com/af_preview/2013/1190-dom_peep/1190-chaosmen_dom_peep.zip
И название сета, в данном случае: Dom   : peep

Возвращать в виде:
Видео|Zip|Title



*/




set_time_limit(10);

//$parser = new CParseTubeSites_manhub();
//$parser = new CParseTubeSites_gaytube();
$parser = new CParse_chaosmen();

//Процесс работы

// Узнайм количество страниц на сайте для первой обработки
// В дальнейшем можно получать первые 5-10 страниц
$total_pages = $parser->count_page();
echo 'Total_pages: '.$total_pages.'<br>';


// Прозодим по всем страницам и получаем урлы на внутринние страницы

$total_pages = 12;
// Можно переберать или все страницы или только нужное количество
$intermal_pages = array();
for($i = 1; $i < $total_pages; $i++)
{
	$page_conent = $parser->parse_main_page( $i );
	$intermal_pages = array_merge($intermal_pages, $page_conent );
}
pr($intermal_pages);
//exit;

//$internal_page_content = $parser->parse_internal_page('http://chaosmen.com/affiliate_preview.php?video_id=1886&prev_page=video_index.php&page=1');
//pr($internal_page_content,true);
//exit;

// Парсим внутринние страницы
foreach($intermal_pages as $internal_page_url)
{
	//echo $internal_page_url.'<br>';
	$internal_page_content = $parser->parse_internal_page($internal_page_url);
	//pr($internal_page_content,true);
	
	$ret =	$internal_page_content['wmv_640'] . '|' .
			$internal_page_content['zip'] . '|' .
			$internal_page_content['title'];

	pr($ret);
}

