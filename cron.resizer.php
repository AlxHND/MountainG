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
	include("classes/class.images.php");
	include("classes/GifFrameExtractor.php");
	include("classes/class.sitesgalleries.php");
	include("lib/functions.php");

	$fileFlagName = "temp/.cron-resizer";
	
	if (file_exists($fileFlagName)) {
		$log = new Logger ("Один крон cron-resizer уже запущен, запуск отменен", true, true);
	} else {
		$log = new Logger ("Запуск крона cron-resizer:".getmygid(), false, true);
		if ($fileFlag = fopen ($fileFlagName,"w")) {
			fclose($fileFlag);
			$gallery = new Galleries($db->_db);
			for($i = 0; $i <2; $i++) {
				$gallery->processHorizThumbs();
			}
			unlink ($fileFlagName);	
			$log = new Logger ("Стоп крона cron-resizer: ".getmygid(), false, true);
		} else  {
			$log = new Logger ("Не могу создать файл-флаг ".$fileFlagName, true, true);
		}
	}
?>
