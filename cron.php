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
include("classes/class.writers.php");
include("classes/class.sitesgalleries.php");
include("classes/GifFrameExtractor.php");
include("lib/functions.php");

$fileFlagName = "temp/.crop";

$cron_pid = getmypid();

$gallery = false;

function shutdown()
{
	global $cron_pid, $gallery;
	$a = error_get_last();

	if ($a == null) {
		return false;
	} else {
		$gal_id = '-Unknown-';
		if (is_object($gallery)) {
			$gal_id = $gallery->getGalleryId();
		}
		$log = new Logger("Крон очереди галер: PID#" . $cron_pid . ", GID#" . $gal_id . ", Ошибка PHP: \n'" . $a['message'] . "'\n", true, true);
	}
}
register_shutdown_function('shutdown');

$scr_start = get_time();

if (file_exists($fileFlagName)) {
	$log = new Logger("Один крон уже запущен, запуск отменен", true, true);
} else {
	$log = new Logger("Запуск крона " . $cron_pid, false, true);
	if ($fileFlag = fopen($fileFlagName, "w")) {
		fclose($fileFlag);
		$gallery = new Galleries($db->_db);
		$gallery->processQuery();

		$scr_finish = get_time();

		$scr_exec_time = $scr_finish - $scr_start;
		$load = sys_getloadavg();

		if ($load[0] < 10 && $scr_exec_time < 800) {
			$writers = new WritersQuery();
			$writers->setTitlesUsed();
			$scr_second_finish = get_time();
			$scr_second_exec_time = $scr_second_finish - $scr_finish;
			if ($scr_second_exec_time < 1) {
				$start = time();
				var_dump($start);
				$gallery->processSyncCdnQuery();
				$fin = time();
				$res = $fin - $start;
				echo  "processSyncCdnQuery: {$res}\n";
				$start = time();
				$gallery->processHorizThumbs();
				$fin = time();
				$res = $fin - $start;
				echo  "processHorizThumbs:  {$res}\n";
				$start = time();
				$sites_galleries = new SitesGalleries();
				$sites_galleries->clearChangeQuerieDoubles();
				for ($i = 0; $i < 5; $i++) {
					$sites_galleries->processChangesQuery();
				}
				$fin = time();
				$res = $fin - $start;
				echo  "processChangesQuery:  {$res}\n";

				echo "Count: {$sites_galleries->queryCount()}\n";

				$start = time();
				$previewJobs = $gallery->processQueuedVideoPreviewJobs(3, 'cron:' . $cron_pid);
				$fin = time();
				$res = $fin - $start;
				echo "processQueuedVideoPreviewJobs: {$res}\n";
				echo "Preview jobs processed: " . (int)$previewJobs['processed'] . ", success: " . (int)$previewJobs['success'] . ", errors: " . (int)$previewJobs['errors'] . "\n";
			}
		} else {
			$log = new Logger("Крон очереди галер: PID#" . $cron_pid . ": Невозможно обработать очередь райтера. LA:" . $load[0] . ", Exec time:" . $scr_exec_time, true);
		}

		// $siteUtils = new Sites($db->_db);
		// $siteUtils->syncRelatedSite();

		// checkSitesQuery();
		// initSitesTagAndModels();
		//		
		unlink($fileFlagName);
		$log = new Logger("Стоп крона " . $cron_pid, false, true);
	} else {
		$log = new Logger("Не могу создать файл-флаг .crop", true, true);
	}
}
