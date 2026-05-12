<?php

	ini_set('display_errors','1');
	error_reporting(E_ALL);
	require_once ("config/config.php");

	$link = mysqli_connect(DBHOST, DBUSER, DBPW, DBNAME) or die( "Сервер базы данных не доступен" );

	$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_delete_rss` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `gal_id` int(10) unsigned NOT NULL,
			  `site_id` int(10) unsigned NOT NULL,
			  `gal_local_id` int(10) unsigned NOT NULL,
			  `gal_url` varchar(240) NOT NULL,
			  `added_on` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `gal_id` (`gal_id`),
			  KEY `site_id` (`site_id`),
			  KEY `gal_local_id` (`gal_local_id`),
			  KEY `added_on` (`added_on`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";


	$sql[] = "CREATE TABLE IF NOT EXISTS `sites_searches` (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `search_key` varchar(254) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `approved` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `site_id` int(10) unsigned NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `approved` (`approved`,`site_id`,`added_on`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";


	$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_resized_to` (
			  `id` bigint(20) NOT NULL AUTO_INCREMENT,
			  `gal_id` int(10) unsigned NOT NULL,
			  `horiz_size` smallint(5) unsigned NOT NULL,
			  `status` enum('ok','error') NOT NULL,
			  `error` varchar(512) NOT NULL,
			  `updated_on` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `gal_id` (`gal_id`,`horiz_size`,`status`,`updated_on`)
			) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	
	$sql[] = "CREATE TABLE IF NOT EXISTS `caching_temp_t` (
			  `id` bigint(20) NOT NULL AUTO_INCREMENT,
			  `gal_id` int(10) unsigned NOT NULL,
			  `added_on` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `gal_id` (`gal_id`)
			) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";


	$sql[] = "DROP TABLE sites_models";

	
	$sql[] = "CREATE TABLE IF NOT EXISTS `sites_models` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `model_id` int(10) unsigned NOT NULL,
		  `site_id` int(10) unsigned NOT NULL,
		  `likes` int(10) unsigned NOT NULL DEFAULT '0',
		  `pageviews` int(10) unsigned NOT NULL DEFAULT '0',
		  `added_on` int(10) unsigned NOT NULL,
		  `updated_on` int(10) unsigned NOT NULL,
		  `gals_count` int(10) unsigned NOT NULL,
		  `video_count` int(10) unsigned NOT NULL,
		  `total_count` int(10) unsigned NOT NULL,
		  `category_of_age` tinyint(4) NOT NULL,
		  `name` varchar(64) COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `model_site_u` (`model_id`,`site_id`),
		  KEY `model_id` (`model_id`,`site_id`,`likes`,`pageviews`),
		  KEY `added_on` (`added_on`),
		  KEY `gals_count` (`gals_count`,`video_count`),
		  KEY `total_count` (`total_count`),
		  KEY `category_of_age` (`category_of_age`),
		  KEY `sort_name` (`name`),
		  KEY `name` (`name`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";


	$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_status_messages` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `gal_id` int(10) unsigned NOT NULL,
			  `status` varchar(64) NOT NULL,
			  `status_reason` varchar(254) NOT NULL,
			  `updated_on` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `gal_id` (`gal_id`)
			) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



	$sql[] = "ALTER TABLE `sites` CHANGE `additional_redis_server` `additional_redis_server` TINYINT( 3 ) NOT NULL DEFAULT '-1';";



	// $sql[] = "ALTER TABLE `sites_models`  ADD `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL  AFTER `site_id` ADD INDEX ( `name` ) ;";
	

	$sql[] = "ALTER TABLE `sites_models` ADD `md5` VARCHAR( 64 ) NOT NULL AFTER `name`, ADD INDEX ( `md5` ) ;";	
	

	$sql[] = "UPDATE sites_models
			INNER JOIN model ON sites_models.model_id = model.id_model
			SET sites_models.md5 = MD5(replace(replace(model.name, \"'\", ''), ' ', '-')),
				sites_models.name = model.name
			WHERE sites_models.md5 LIKE ''";

	$sql[] = "CREATE TABLE IF NOT EXISTS `cdn_sync_videos` (
		  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `sync_added_on` int(10) unsigned NOT NULL,
		  `file_status` enum('new','ok','delete','request_sent','request_failed','error') NOT NULL DEFAULT 'new',
		  `status_updated_on` int(10) unsigned NOT NULL,
		  `file_size` bigint(20) unsigned NOT NULL,
		  `error_message` varchar(200) NOT NULL DEFAULT '',
		  PRIMARY KEY (`file_id`),
		  KEY `gal_id` (`gal_id`),
		  KEY `file_status` (`file_status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	$sql[] = "ALTER TABLE `cdn_sync_videos` ADD UNIQUE (`gal_id`);";

	$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_videos` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `gal_id` int(10) unsigned NOT NULL,
			  `video_size` int(10) unsigned NOT NULL,
			  `is_hd` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `cdn_synced` tinyint(3) unsigned NOT NULL DEFAULT '0',
			  `original_width` smallint(5) unsigned NOT NULL,
			  `original_height` smallint(5) unsigned NOT NULL,
			  `videos_types_available` set('hd','low','original') NOT NULL DEFAULT 'original',
			  `video_status` enum('ok','file_not_found', 'new','delete') NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `gal_id` (`gal_id`),
			  KEY `is_hd` (`is_hd`),
			  KEY `cdn_synced` (`cdn_synced`),
			  KEY `videos_types_available` (`videos_types_available`),
			  KEY `video_status` (`video_status`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	// $sql[] = "ALTER TABLE `sites` ADD `vcdn_type` ENUM( 'dynamic', 'static' ) NOT NULL DEFAULT 'dynamic';";
	
	$sql[] = "INSERT INTO `galleries_videos` ( gal_id, video_size, is_hd, cdn_synced, original_width, original_height, videos_types_available, video_status)
			SELECT gal_id, 0, 5, 0, 0, 0, 'original', 'new' FROM galleries WHERE gal_type = 'Movies' AND gal_status IN ('uploaded', 'OK') AND gal_id NOT IN (SELECT gal_id FROM galleries_videos)";
	
	// $sql[] = "ALTER TABLE `galleries` ADD `is_long_url` BOOLEAN NULL DEFAULT FALSE AFTER `insource_original_id` ;";
	
	$sql[] = "ALTER TABLE `galleries` ADD INDEX ( `is_long_url` ) ;";

	$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_urls` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `gal_id` int(10) unsigned NOT NULL,
			  `gal_url` text NOT NULL,
			  `gal_md5` varchar(64) NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `gal_id` (`gal_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
	
	$sql[] = "CREATE TABLE IF NOT EXISTS `sites_stats_mini` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `site_id` int(10) unsigned NOT NULL,
			  `stat_date` date NOT NULL,
			  `uniqs_count` bigint(20) unsigned NOT NULL,
			  `pageviews_count` bigint(20) unsigned NOT NULL,
			  `updated_on` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `site_id` (`site_id`),
			  KEY `stat_date` (`stat_date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
	
	$sql[] = "TRUNCATE galleries_delete_rss";
	
	$sql[] = "ALTER TABLE `galleries_delete_rss` ADD UNIQUE (
			`gal_id` ,
			`site_id`
			);";
	
	 $sql[] = "CREATE TABLE IF NOT EXISTS `scr_users_exclude_paysites` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) NOT NULL,
			  `paysite_id` int(10) unsigned NOT NULL,
			  `added_on` int(10) unsigned NOT NULL,
			  `added_by` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `user_paysite_ids` (`user_id`,`paysite_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	// $sql[] = "ALTER TABLE `sites` ADD `use_unique_tags` TINYINT NOT NULL DEFAULT '0' COMMENT 'only for satellite sites' AFTER `use_galleries_from` ;";

	// $sql[] = "ALTER TABLE `sites` ADD `default_title_for_tag` VARCHAR( 254 ) CHARACTER SET utf16 COLLATE utf16_bin NOT NULL DEFAULT 'Free #TAG_NAME#';";

	// $sql[] = "ALTER TABLE `paysites` CHANGE `max_bitrate` `max_bitrate` INT( 10 ) UNSIGNED NOT NULL DEFAULT '2200';";

	// $sql[] = "ALTER TABLE `galleries` ADD `use_youtube_dl` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `times_used_on_sites` ;";


	foreach($sql as $sql_s) {
		$res = mysqli_query($link, $sql_s);
		if($res === false) echo "Ошибка при выполнении запроса: " . mysqli_error($link) . "<br>".$sql_s."<br>";

	}