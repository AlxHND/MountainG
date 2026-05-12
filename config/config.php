<?php

	define("REDIS_SERVER_BANNERS", "127.0.0.1");

	define("ALWAYS_ALLOWED_IP", '74.117.176.218');

	define("CDN_SYNC_USER", "shound-bot");
	define("CDN_SYNC_PASS", "yeshAthebhorgib0");
	define("VIDEOS_SYNC_DOMAIN", "xrhost.com/uploadedimages");
	define("VCDN_CALLBACK_SECRET", "yuIksackAbtakow9");
	define("SCRIPT_DOMAIN", "mountain-glamour.com");

	define("CDN_FILE_NAME_ADDITION", "");



	define("DBHOST", "localhost");
	define("DBUSER", "sexhound");
	define("DBPW", "wRWu9P356vsGBunJ");
	define("DBNAME", "sexhound");
	define("WRKDIR","/home/web1/mountain-glamour.com/htdocs/sderrwqalkjt1isAre");
	define("LIB_DIR","/home/web1/mountain-glamour.com/htdocs/sderrwqalkjt1isAre");

	define("HOSTING", "http://hosted2x.xrhost.com");
	define("UPLOADFOLDER","/home/web1/xrhost.com/htdocs/uploadedimages");
	define("MLOCALRT","/home/web1");
	define("TMPDIR","/home/web1/mountain-glamour.com/htdocs/sderrwqalkjt1isAre/temp");
	define("GALLERY_TEXT_STORAGE", dirname(__DIR__) . "/storage/gallery_texts");

	define("FTP", "localhost");
	define("FTPUSER", "xrhostimage");
	define("FTPPW", "5rUsp2mudRet");

	define("RSSPWD", "sqg2fdqkjHgdlo");

	define("FOLDERNEEDLE",'/home/web1/mountain-glamour.com/htdocs');
	define("ZIP_FOLDER", "/temp/zip");

	define("SCRIPT_PRE", "HOUNDXADS");
	define("DELIVERY_SITE", "http://houndxads.com/delivery.php");

	$rssThumbSizes['small']['width'] = 150;	$rssThumbSizes['small']['height'] = 205;
	$rssThumbSizes['medium']['width'] = 180; $rssThumbSizes['medium']['height'] = 240;
	$rssThumbSizes['big']['width'] = 240; $rssThumbSizes['big']['height'] = 320;

	$rssMovieThumbs['small']['width'] = 200; $rssMovieThumbs['small']['height'] = 150;
	$rssMovieThumbs['medium']['width'] = 240; $rssMovieThumbs['medium']['height'] = 180;
	$rssMovieThumbs['big']['width'] = 320; $rssMovieThumbs['big']['height'] = 240;

	define("CONTENT_TYPE_ANY",0);
	define("CONTENT_TYPE_IMAGE",1);
	define("CONTENT_TYPE_ZIP",4);
	define("CONTENT_TYPE_VIDEO",8);
	define("CONTENT_TYPE_HTML",16);
	define("CONTENT_TYPE_GIF",32);
	  
	define("TMP_FOLDER","/home/web1/mountain-glamour.com/htdocs/sderrwqalkjt1isAre/temp");
	define("TMP_VIDEO_FOLDER","/home/web1/mountain-glamour.com/htdocs/sderrwqalkjt1isAre/upload");
	define("IM_DEFAULT_STRING"," -filter Blackman -unsharp 1x0.6+1 -modulate 110,115,100");
	define("IM_DEFAULT_VIDEO_STRING"," -filter Blackman -unsharp 1x0.75+1 -modulate 110,120,100");
	define("LOG_FOLDER",'/home/web1/mountain-glamour.com/htdocs/sderrwqalkjt1isAre/logs');
	define("CACHE_PORT", 11211);

	define("BASE_HEIGHT",205);
	define("CRON_FLAG_FILE",TMP_FOLDER ."/.crop");
	//define ("FFMPEG_PATH", "/usr/local/ffmpeg/bin/ffmpeg");
	define("FFMPEG_PATH", "ffmpeg");
	define("REDIS_SERVER", 5);
	define("REDIS_IP", "199.101.135.47");
	define("REDIS_PORT", 6379);
	define("REDIS_SERVERS_COUNT",3);
//	define("REDIS_IP", "66.254.98.34");
//204.155.147.225
	define("REDIS_IP_1", "78.140.183.91"); // EU
	define("REDIS_IP_2", "204.12.243.186"); // WHolesale - тикет на доступ к редису выслать
//	define("REDIS_IP_3", "78.140.183.91");
//	define("REDIS_IP_3", "199.101.135.49"); //US - 49

$caching_servers = array(
	0 => array(
		'id' => 0,
		'name' => 'INXY-48',
		'ip' => '199.101.135.47',
		'port' => '6379'
	), 
	/*1 => array(
		'id' => 1,
		'name' => 'INXY-EU',
		'ip' => '78.140.183.91',
		'port' => '6379'
	), */
	/*3 => array(
		'id' => 3,
		'name' => 'INXY-49',
		'ip' => '199.101.135.49',
		'port' => '6379'
	), */
	/* 5 => array(
		'id' => 5,
		'name' => 'INXY-217-Sat',
		'ip' => '199.101.134.217',
		'port' => '6379'
	), */
	6 => array(
		'id' => 6,
		'name' => 'INXY-47-Sat',
		'ip' => '199.101.135.47',
		'port' => '6379'
	)/*,
	7 => array(
		'id' => 7,
		'name' => 'INXY-Cheap-1',
		'ip' => 'hawk907.dedicatedpanel.com',
		'port' => '6379'
	) */


);

$stop_words = array(
"the",
"this",
"that",
"about",
"and",
"out",
"double",
"she",
"its",
"free",
"for",
"nor",
"how",
"who",
"where",
"very",
"but",
"there",
"room",
"his",
"her",
"poor",
"love",
"loving",
"get",
"set",
"gets",
"getting",
"himself",
"loves",
"even",
"movie",
"movies",
"video",
"videos",
"photo",
"photos",
"look",
"looks",
"even",
"porn",
"soon",
"clip",
"clips",
"handsome",
"they",
"here",
"with",
"without",
"download",
"enjoys",
"enjoy",
"hot",
"although",
"the",
"wrong",
"gallery",
"galleries",
"tube",
"had",
"making",
"make",
"mobile",
"phone",
"gone",
"tgp",
"porno",
"xxx",
"xxxx",
"like",
"last",
"has",
"again",
"soon",
"just",
"together",
"too",
"over",
"all",
"until",
"doesn",
"could",
"see",
"what",
"was",
"didn",
"while",
"used",
"not",
"cannot",
"goes",
"more",
"are",
"some",
"when",
"real"
);

//	CachingServers::addServer(0, "INXY-48", "199.101.135.48", "6379");
//	CachingServers::addServer(1, "INXY-EU", "78.140.183.91", "6379");
//	CachingServers::addServer(2, "WholeServer 1", "204.12.243.186", "6379");
//	CachingServers::addServer(3, "INXY-49", "199.101.135.49", "6379");


	
date_default_timezone_set('Europe/London');
?>
