<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function getHtmlPage($url) {

	$uagent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17";

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
	curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
	curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
	curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	// $verbose = fopen('/home/web1/xratedtravels.com/htdocs/aswerfghyu6ew4/logs/.curl_x', 'w+');
	// curl_setopt($ch, CURLOPT_STDERR, $verbose);
	$content = curl_exec( $ch ); 
	$info = curl_getinfo ($ch);

	return $content;
}


function parseJsonPage($url) : array
{
	$content = getHtmlPage($url);

	$js = json_decode($content);
	
	if(!$js) return [];
	
	$galleries = [];

	foreach($js as $gallery) {
		// var_dump($gallery);
		$title = str_replace(" {$gallery->gid}", "", $gallery->desc);
		$title = str_replace("Sean Cody ", "", $title);
		$title = str_replace("SeanCody ", "", $title);
		$galleries[] = ['gallery' => $gallery->g_url, 'title' =>$title ];
	}
	
	return $galleries;
}

if(!isset($_GET['keyword'])) {
    echo "Нет кея для парсинга";
    die;
}

$keyword = preg_replace('/\s+/','+', trim(strtolower($_GET['keyword'])));

// var_dump($keyword, $_GET['keyword']);

if(empty($keyword) || strlen($keyword) < 5) {
    echo "Кей короче 5 символов";
    die;
}

$parse_url = 'https://www.pornpics.com/search/srch.php?q='.$keyword;

$offset = empty($_GET['from_page']) ? 0 : (int)$_GET['from_page'];
$limit = empty($_GET['limit_per_page']) ? 20 : (int)$_GET['limit_per_page'];
$pages = empty($_GET['pages']) ? 1 : (int)$_GET['pages'];


$galleries = [];

for($i = 0; $i < $pages; $i++) {
	$offset = $i * $limit;
	$url = "{$parse_url}&limit={$limit}&offset={$offset}";
	// echo $url . PHP_EOL;
	$parsedGalleries = parseJsonPage($url);
	
	if(!empty($parsedGalleries)) {
		$galleries = array_merge($galleries, $parsedGalleries);
	}
	// echo "Page #".$i."\n\n\n\n";
}

$output_galleries = [];

foreach($galleries as $index => $gallery) {

	$models = [];
	$tags = [];
	$channels = [];

	$content = getHtmlPage($gallery['gallery']);
	
	libxml_use_internal_errors(true);
	$html = new DOMDocument();
	$html->loadHTML ($content);
	$xpath = new DOMXPath( $html );
	libxml_clear_errors();

	$query_string = '//div[contains(@class,"to-gall-info")]';
	$ht = $xpath->query($query_string);

	$all_a = $ht->item(0)->getElementsByTagName('a');

	
	foreach($all_a as $info) {
		// var_dump($info->childNodes->item(0)->nodeValue, $info->getAttribute("href"));

		if(strstr($info->getAttribute("href"), 'pornstars/')) {
			$models[] = $info->childNodes->item(0)->nodeValue;
		} elseif (strstr($info->getAttribute("href"), 'channels/')) {
			$channels[] = $info->childNodes->item(0)->nodeValue;
		} else {
			$tags[] = $info->childNodes->item(0)->nodeValue;
		}
	}

	$output_galleries[] = [
		'url' => $gallery['gallery'],
		'title' => $gallery['title'],
		'tags' => implode(',', $tags),
		'pornstars' => implode(',', $models),
		'channels' => implode(',', $channels),
	];

	
}

$saveToFile = '';

foreach($output_galleries as $gallery) {
	$saveToFile .= $gallery['url'] ."|". $gallery['title'] ."|". $gallery['tags'] ."|". $gallery['pornstars'] ."|". $gallery['channels'] ."\n";
}

$filename = 'storage/parsed-'.$keyword.'-'.date("Y-m-d-H-i-s").'.txt';

file_put_contents($filename,  $saveToFile);

header('Location: ./index.php', true, 302);