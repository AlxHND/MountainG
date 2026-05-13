<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

include("config/config.php");
include("classes/Logger.php");
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
include("classes/class.sitesgalleries.php");
include("classes/GifFrameExtractor.php");
include("lib/functions.php");


$fileFlagName = "temp/.cron-grab";

if (file_exists($fileFlagName)) {
	$log = new Logger("Один крон граббера (fetch.cron.php) уже запущен!", true, true);
} else {
	$log = new Logger("Запуск крона граббера " . getmypid(), false, true);
	if ($fileFlag = fopen($fileFlagName, "w")) {
		fclose($fileFlag);
		$gallery = new Galleries($db->_db);
		$scr_start = get_time();
		$gallery->processGrab();

		$scr_finish = get_time();

		$scr_exec_time = $scr_finish - $scr_start;
		if ($scr_exec_time < 2) {
			$sites_galleries = new SitesGalleries();
			for ($i = 0; $i < 2; $i++) {
				$sites_galleries->processChangesQuery();
			}
		}
		unlink($fileFlagName);
		$log = new Logger("Стоп крона граббера " . getmypid(), false, true);
	} else  $log = new Logger("Не могу создать файл-флаг .cron-grab", true, true);
}
