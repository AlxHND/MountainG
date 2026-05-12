<?php
	ini_set('display_errors','1');
	error_reporting(E_ALL);

	include("config/config.php");
	include("classes/class.logger.php");
	include("classes/ftp.php");
	include("classes/parser.php");
	include("classes/grabber.php");
	include("classes/class.video.php");
	include("classes/class.cache.php");
	include("classes/class.sites.php");
	include("classes/class.models.php");
	include("classes/class.sources.php");
	include("classes/class.tags.php");
	include("classes/class.db_access.php");
	include("classes/class.stemming.php");
	include("classes/class.galleries.php");
	include("classes/class.grabber.php");
	include("classes/class.resizer.php");
	include("classes/class.new-cache.php");
	include("classes/GifFrameExtractor.php");
	include("classes/class.sitesgalleries.php");

	include("lib/functions.php");


	$fileFlagName = "temp/.cron.sites_query";
	
	if (file_exists($fileFlagName)) { $log = new Logger ("Один крон (cron.sites_query.php) уже запущен!", true, true); }
	else {
		$log = new Logger ("Запуск крона очереди добавления на сайт ".getmypid(), false, true);
		if ($fileFlag = fopen ($fileFlagName,"w")) {
			fclose($fileFlag);
			checkSitesQuery();
			initSitesTagAndModels();
			unlink ($fileFlagName);	
			$log = new Logger ("Запуск крона очереди добавления на сайт ".getmypid(), false, true);
		} else {
			$log = new Logger ("Не могу создать файл-флаг .cron.sites_query", true, true);
		}
	}
?>