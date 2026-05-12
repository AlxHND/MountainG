<?php
	ini_set('display_errors','1');
	error_reporting(E_ALL);

	include ("config/config.php");
	include ("classes/class.logger.php");
	include ("classes/class.new-cache.php");
	include ("classes/class.sites.php");
	include ("classes/class.db_access.php");
	include ("classes/class.galleries.php");
	include ("classes/class.sources.php");
	include ("classes/class.models.php");
	include ("classes/class.tags.php");
	include ("classes/class.sitesgalleries.php");
	include ("lib/functions.php");
	
	// название блокировщика файла
	$fileFlagName = TMPDIR."/.cron.pageviews-likes";

	if (file_exists($fileFlagName)) $log = new Logger ("Один крон апдейта Likes-Pageviews уже запущен", true, true);
	else {
		$pid = getmypid();
		$log = new Logger ("Cron ".$pid." - Pageviews-Likes START",false, true);
		if ($fileFlag = fopen ($fileFlagName,"w")) {
			fclose($fileFlag);
			$sites = new Sites($db->_db);
			$cache_worker = new CacheRebuilder($db->_db);
			$sites_on_new_stats = array(9, 1, 2, 3, 4, 5, 6, 7, 10, 11, 12, 13, 14, 20, 22, 23, 24, 25, 26, 27, 28, 30, 31);
			  $site_id = $sites->getSiteToUpdatePageviews();
			 // foreach($sites_on_new_stats as $site_id) {
			  // $site_id = 29;
				if ($site_id) {
					$sites->setSitePageviewsUpdated($site_id);
					if(in_array($site_id, $sites_on_new_stats)) { 
						$scr_start = get_time();
						$cache_worker->updateSitesGalleriesPageviews($site_id);
						$scr_finish = get_time();
						$scr_exec_time = $scr_finish - $scr_start;
						$log = new Logger("Site #".$site_id.", NEW updateSitesGalleriesPageviews exec time:".$scr_exec_time, false, true);
					} else {
						$scr_start = get_time();
						$cache_worker->sitePageviewsToDb($site_id);
						$scr_finish = get_time();
						$scr_exec_time = $scr_finish - $scr_start;
						$log = new Logger("Site #".$site_id.", old sitePageviewsToDb exec time:".$scr_exec_time, false, true);
					}
				}

				// $site_id = $sites->getSiteToUpdateLikes();
				if ($site_id) {
					$sites->setSiteLikesUpdated($site_id);

					if(in_array($site_id, $sites_on_new_stats)) {
						$scr_start = get_time();
						$cache_worker->updateSitesGalleriesLikes($site_id);
						$scr_finish = get_time();
						$scr_exec_time = $scr_finish - $scr_start;
						$log = new Logger("Site #".$site_id.", NEW updateSitesGalleriesLikes exec time:".$scr_exec_time, false, true);
					} else {
						$scr_start = get_time();
						$cache_worker->siteLikesToDb($site_id);
						$scr_finish = get_time();
						$scr_exec_time = $scr_finish - $scr_start;
						$log = new Logger("Site #".$site_id.", old siteLikesToDb exec time:".$scr_exec_time, false, true);
					}

					
				}
			// }			
			unlink ($fileFlagName);
		} else  $log = new Logger ("Не могу создать файл-флаг .crop", true, true);
		$log = new Logger ("Cron ".$pid." - Pageviews-Likes STOP", false, true);
	}