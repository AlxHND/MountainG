<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/Users.php';

	

use App\Helpers\DB;

$sql = [];

		$sql[] = "CREATE TABLE IF NOT EXISTS `additional_titles` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `title` varchar(254) CHARACTER SET utf8 NOT NULL DEFAULT '',
		  `language` enum('en','nl','cz','de','be','es','ru','by','cn','jp','it','fr') COLLATE utf8_bin NOT NULL DEFAULT 'en',
		  `added_on` int(10) unsigned NOT NULL DEFAULT '0',
		  `used_on` int(10) unsigned NOT NULL DEFAULT '0',
		  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `gal_id` (`gal_id`,`language`,`user_id`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `banners` (
		  `id_banner` int(11) NOT NULL AUTO_INCREMENT,
		  `paysite_id` int(11) NOT NULL,
		  `width` int(11) NOT NULL,
		  `height` int(11) NOT NULL,
		  `ratio` int(11) NOT NULL,
		  `type` enum('gif','png','jpg','screen') COLLATE utf8_bin NOT NULL,
		  `text` varchar(254) COLLATE utf8_bin NOT NULL,
		  `special_link` varchar(254) COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id_banner`),
		  KEY `paysite_id` (`paysite_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `banners_spots` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `site_id` int(10) unsigned NOT NULL,
		  `paysite_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `category_1` int(10) unsigned NOT NULL,
		  `category_2` int(10) unsigned NOT NULL,
		  `max_width` int(10) unsigned NOT NULL,
		  `max_height` int(10) unsigned NOT NULL,
		  `min_width` int(10) unsigned NOT NULL,
		  `min_height` int(10) unsigned NOT NULL,
		  `onsite_position` enum('main','category','page','archive','other') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `onpage_position` enum('top','bottom','lsidebar','rsidebar','middle') NOT NULL,
		  `row` int(10) unsigned NOT NULL,
		  `column` int(10) unsigned NOT NULL,
		  `number` int(11) NOT NULL,
		  `use_if_empty` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `site_id` (`site_id`,`category_1`,`category_2`),
		  KEY `paysite_id` (`paysite_id`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `banners_spots_content` (
		  `id_spot` int(11) NOT NULL,
		  `id_banner` int(11) NOT NULL,
		  `paysite_id` int(10) unsigned NOT NULL,
		  `ratio` int(11) NOT NULL DEFAULT '0',
		  KEY `id_spot` (`id_spot`),
		  KEY `paysite_id` (`paysite_id`)
		) DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `cache_rebuild_process` (
		  `id` bigint(20) NOT NULL,
		  `added_on` bigint(20) NOT NULL,
		  KEY `id` (`id`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `caching_temp_t` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `gal_id` (`gal_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `cfg_redis_servers` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `server_name` varchar(128) NOT NULL,
		  `server_ip` bigint(20) unsigned NOT NULL,
		  `server_port` int(10) unsigned NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  `updated_on` int(10) unsigned NOT NULL,
		  `last_time_down` int(11) NOT NULL,
		  `db_number` tinyint(3) unsigned NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `crop_profiles` (
		  `profile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `crop_profile_name` varchar(64) NOT NULL DEFAULT '',
		  `IM_string` varchar(255) NOT NULL DEFAULT '-strip -filter Blackman -unsharp 1x0.6+1 -modulate 105,110,100',
		  `crop_quality` tinyint(3) unsigned NOT NULL DEFAULT '95',
		  `cut_top` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `cut_bottom` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `cut_left` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `cut_right` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`profile_id`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `error` (
		  `error_id` int(11) NOT NULL,
		  `error_description` varchar(255) NOT NULL,
		  PRIMARY KEY (`error_id`)
		) DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries` (
		  `gal_id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		  `gal_source` varchar(255) CHARACTER SET utf8 NOT NULL,
		  `gal_md5` varchar(64) CHARACTER SET utf8 NOT NULL,
		  `gal_paysite` int(64) unsigned NOT NULL DEFAULT '0',
		  `gal_title` varchar(256) CHARACTER SET utf8 NOT NULL,
		  `gal_description` varchar(254) CHARACTER SET utf8 NOT NULL,
		  `gal_thumb` int(10) unsigned DEFAULT '0',
		  `gal_added` date NULL DEFAULT '1000-01-01',
		  `gal_content_count` int(10) unsigned NOT NULL DEFAULT '0',
		  `gal_niche` enum('Gay','Straight','Shemale') CHARACTER SET utf8 NOT NULL DEFAULT 'Gay',
		  `gal_status` enum('zip','newzip','unzipping','unzip_fail','zipupload','zipupload_fail','new','fetching','fetching_fail','already_exists','fetched','video_screening','screen_fail','screened','gif_fail','video_converting','video_fail','video_converted','thumbing','thumbed','pics_resizing','pics_resized','grab_fail','grabbed','thumbs_fail','thumbs','upload_fail','uploaded','tagged','toregrab','OK','trash','delete','to_merge') CHARACTER SET utf8 NOT NULL DEFAULT 'new',
		  `gal_type` enum('New','Pics','Movies','embed','gif') CHARACTER SET utf8 NOT NULL DEFAULT 'Pics',
		  `embed` varchar(600) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
		  `hosted_flag` enum('1','0') NOT NULL DEFAULT '1',
		  `crop_flag` enum('0','1') NOT NULL DEFAULT '0',
		  `embed_flag` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  `error_flag` int(11) NOT NULL DEFAULT '0',
		  `status_change_time` int(10) unsigned NOT NULL DEFAULT '0',
		  `main_gal` int(10) unsigned NOT NULL DEFAULT '0',
		  `unique_gal` smallint(5) unsigned NOT NULL DEFAULT '0',
		  `random_number` int(11) NOT NULL DEFAULT '0',
		  `insource_original_id` bigint(20) unsigned NOT NULL DEFAULT '0',
		  `is_long_url` BOOLEAN NULL DEFAULT FALSE,
		  `times_used_on_sites` smallint(5) unsigned DEFAULT '0',
		  UNIQUE KEY `gal_md5` (`gal_md5`),
		  KEY `gal_status` (`gal_status`),
		  KEY ( `is_long_url` ),
		  KEY `gal_thumb` (`gal_thumb`),
		  KEY `hosted_flag` (`hosted_flag`),
		  KEY `crop_flag` (`crop_flag`),
		  KEY `unique_gal` (`unique_gal`),
		  KEY `random_number` (`random_number`),
		  KEY `embed_flag` (`embed_flag`),
		  KEY `insource_original_id` (`insource_original_id`),
		  KEY `times_used_on_sites` (`times_used_on_sites`),
		  FOREIGN KEY (`gal_paysite`) REFERENCES paysites(paysite_id) ON DELETE CASCADE
		  FULLTEXT KEY `gal_title` (`gal_title`)
		)  ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_original_ids` (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `original_id` int(10) unsigned NOT NULL,
		  `source_id` int(10) unsigned NOT NULL,
		  `gal_id` int(10) unsigned NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `original_id` (`original_id`,`source_id`,`gal_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_pix` (
		  `gal_id` int(10) unsigned NOT NULL,
		  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `image` varchar(128) NOT NULL DEFAULT '',
		  `ratio_w_h` float unsigned NOT NULL DEFAULT '0',
		  `rss_flag` tinyint(4) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`image_id`),
		  KEY `gal_id` (`gal_id`),
		  KEY `rss_flag` (`rss_flag`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_resized_to` (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `horiz_size` smallint(5) unsigned NOT NULL,
		  `status` enum('ok','error') NOT NULL,
		  `error` varchar(512) NOT NULL DEFAULT '',
		  `updated_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `gal_id` (`gal_id`,`horiz_size`,`status`,`updated_on`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_source_pics` (
		  `gal_id` int(11) NOT NULL DEFAULT '0',
		  `image_id` int(10) unsigned NOT NULL,
		  `gal_pics_md5` varchar(64) NOT NULL DEFAULT '',
		  KEY `gal_pics_md5` (`gal_pics_md5`),
		  KEY `gal_id` (`gal_id`)
		) DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_status_messages` (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `status` varchar(64) NOT NULL,
		  `status_reason` varchar(254) NOT NULL,
		  `updated_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `gal_id` (`gal_id`)
		) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_tags` (
		  `gal_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `gal_tags` int(10) unsigned NOT NULL DEFAULT '0',
		  `gal_niche` enum('Gay','Straight','Shemale') NOT NULL DEFAULT 'Gay',
		  UNIQUE KEY `gallery_tag_un` (`gal_id`,`gal_tags`),
		  KEY `gal_tags` (`gal_tags`),
		  KEY `gal_id` (`gal_id`)
		) DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_to_merge` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `merge_galleries` varchar(254) COLLATE utf8_bin NOT NULL,
		  `width` int(10) unsigned NOT NULL,
		  `height` int(10) unsigned NOT NULL,
		  `status` enum('new','to_mpeg','to_mpeg_fail','mpeg','merging','merging_fail','error') COLLATE utf8_bin NOT NULL,
		  `added` int(11) NOT NULL,
		  `status_change_time` int(11) NOT NULL,
		  `galleries_original_status` varchar(64) COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `grabber` (
		  `gal_id` int(11) NOT NULL,
		  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  `error_flag` int(11) NOT NULL,
		  `gal_type` enum('Pics','Movies') NOT NULL,
		  PRIMARY KEY (`gal_id`),
		  KEY `gal_type` (`gal_type`)
		) DEFAULT CHARSET=utf8;";


		$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_changes_query` (
				  `id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `gal_id` int(10) unsigned NOT NULL,
				  `site_id` int(10) unsigned NOT NULL,
				  `item_type` enum('tag','model','source','gallery','image') NOT NULL,
				  `change_type` enum('added','removed','changed') NOT NULL,
				  `item_id` int(10) unsigned NOT NULL,
				  `processed` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `added_on` int(10) unsigned NOT NULL,
				  `updated_on` int(10) unsigned NOT NULL,
				  `error` tinyint(4) NOT NULL DEFAULT '0',
				  `error_msg` varchar(64) NOT NULL DEFAULT '',
				  PRIMARY KEY (`id`),
				  KEY `gal_id` (`gal_id`),
				  KEY `processed` (`processed`),
				  KEY `added_on` (`added_on`),
				  KEY `error` (`error`),
				  KEY `change_type` ( `change_type`),
				  KEY `site_id` ( `site_id`),
				  KEY `item_type` ( `item_type`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `hosts` (
		  `host_id` int(11) NOT NULL AUTO_INCREMENT,
		  `host_url` varchar(255) NOT NULL DEFAULT 'http://cntt.filthyway.com',
		  `host_folder` varchar(255) NOT NULL DEFAULT '',
		  PRIMARY KEY (`host_id`)
		)  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `images_sources` (
		  `thumb_id` int(11) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(11) NOT NULL,
		  `image_source` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '',
		  `hash` varchar(64) COLLATE utf8_bin NOT NULL DEFAULT '',
		  `image_hash` varchar(254) COLLATE utf8_bin NOT NULL DEFAULT '',
		  `status` enum('new','fetching','fetching_fail','fetched','thumbing','thumbing_fail','tocrop','cropping','ok','error','delete') COLLATE utf8_bin NOT NULL DEFAULT 'new' COMMENT 'new - new thumb, fetching - fetching thumb, fetching_fail, fetched, thumbing - making thumb, thumbing_fail, tocrop - to crop manualy, cropping - cropping process, ok - ОК, error - some other error, shown in info',
		  `status_change_time` int(11) NOT NULL DEFAULT 0,
		  `info` varchar(256) COLLATE utf8_bin NOT NULL DEFAULT '' COMMENT 'used unix time when using processing status, or string for error',
		  `zip` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1  в случае ZIP, 0 в случае не ZIP',
		  PRIMARY KEY (`thumb_id`),
		  UNIQUE KEY `hash` (`hash`),
		  KEY `gal_id` (`gal_id`),
		  KEY `status` (`status`),
		  KEY `image_hash` (`image_hash`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `main_query` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `added` int(10) unsigned NOT NULL DEFAULT '0',
		  `priority` int(10) unsigned NOT NULL DEFAULT '1',
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `gal_id` (`gal_id`)
		)  ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `model` (
		  `id_model` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		  `name` varchar(120) NOT NULL,
		  `active` enum('yes','no') NOT NULL,
		  `sex` enum('female','shemale','male') NOT NULL,
		  `role` enum('top','bottom','versatile') NOT NULL,
		  `category_of_age` tinyint(4) NOT NULL DEFAULT '-1',
		  `hair` enum('bald','blond','brown','brunette','gray','red','white') NOT NULL,
		  `birth` date NOT NULL,
		  `zodiac` enum('none','aries','taurus','gemini','cancer','leo','virgo','libra','scorpio','saggitarius','capricorn','aquarius','pisces') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'none',
		  `eyes` enum('none','amber','blue','brown','gray','green','hazel') NOT NULL DEFAULT 'none',
		  `ethnic` enum('none','arab','american','euro','ebony','asian','latin','indian') NOT NULL DEFAULT 'none',
		  `body_type` enum('none','skinny','thin','slim','athletic','muscular','bodybuilder','chubby','fat') NOT NULL DEFAULT 'none',
		  `cock_n_boobs_type` enum('none','natural','mod') NOT NULL DEFAULT 'none',
		  `piercing` enum('none','yes','no') NOT NULL DEFAULT 'none',
		  `piercing_where` set('none','ears','lips','nose','nipples','tongue','navel','genitals') NOT NULL DEFAULT 'none',
		  `tattoo` set('none','yes','no') NOT NULL DEFAULT 'none',
		  `tattoo_description` varchar(254) NOT NULL,
		  `country` varchar(64) NOT NULL,
		  `body` varchar(60) NOT NULL,
		  `personal_site_id` int(11) NOT NULL,
		  `height` int(11) NOT NULL,
		  `size` int(11) NOT NULL,
		  `info` varchar(254) NOT NULL,
		  `picture` int(11) NOT NULL,
		  `classic` tinyint(4) NOT NULL DEFAULT '0',
		  `googleplus` varchar(254) NOT NULL,
		  `twitter` varchar(254) NOT NULL,
		  `facebook` varchar(254) NOT NULL,
		  `added_on` int(10) unsigned NOT NULL DEFAULT '0',
		  `main_image` int(10) unsigned NOT NULL DEFAULT '0',
		  `main_horiz_image` int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id_model`),
		  KEY `sex` (`sex`),
		  KEY `classic` (`classic`),
		  KEY `category_of_age` (`category_of_age`),
		  FULLTEXT KEY `name` (`name`)
		)  ENGINE=InnoDB   DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE `galleries_models` (
			`gallery_id` BIGINT UNSIGNED NOT NULL,
			`model_id` BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (`gallery_id`, `model_id`), -- Комбинированный первичный ключ
			INDEX `idx_model_id` (`model_id`), -- Индекс для model_id
			CONSTRAINT `fk_galleries_models_gallery` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`gal_id`) ON DELETE CASCADE,
			CONSTRAINT `fk_galleries_models_model` FOREIGN KEY (`model_id`) REFERENCES `model` (`id_model`) ON DELETE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `models_images` (
		  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `model_id` int(10) unsigned NOT NULL,
		  `image_md5` varchar(64) COLLATE utf8_bin NOT NULL,
		  `layout` enum('horiz','vertic','none') COLLATE utf8_bin NOT NULL DEFAULT 'none',
		  `status` enum('new','uploaded','upload_error','crop_error','cropped') COLLATE utf8_bin NOT NULL DEFAULT 'new',
		  `added_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`image_id`),
		  KEY `status` (`status`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `model_names` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `model_id` BIGINT UNSIGNED NOT NULL,
		  `name` varchar(64) NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `model_id` (`model_id`),
		  FULLTEXT KEY `name` (`name`)
		)  ENGINE=InnoDB   DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `model_trash` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `model_id` int(10) unsigned NOT NULL,
		  `model_data` text COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `model_id` (`model_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `paysites` (
		  `paysite_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `paysite_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
		  `paysite_affiliate` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
		  `paysite_link` varchar(255) NOT NULL DEFAULT '',
		  `legal_link` VARCHAR(256) NOT NULL DEFAULT '',
		  `paysite_folder` varchar(255) NOT NULL DEFAULT '',
		  `paysite_info` varchar(255) NOT NULL DEFAULT '',
		  `paysite_review` text NOT NULL,
		  `paysite_trial_length` smallint(6) NOT NULL,
		  `paysite_trial_price` float NOT NULL,
		  `paysite_month_price` float NOT NULL,
		  `paysite_clickhere_text` varchar(255) NOT NULL,
		  `paysite_rating` float NOT NULL,
		  `paysite_niche` enum('Gay','Straight','Shemale') NOT NULL DEFAULT 'Gay',
		  `paysite_category` int(10) unsigned NOT NULL DEFAULT '0',
		  `max_bitrate` int(10) unsigned NOT NULL DEFAULT '2200',
		  `crop_profile_id` int(10) unsigned NOT NULL DEFAULT '1',
		  `hosted_flag` enum('1','0') NOT NULL DEFAULT '1',
		  `last_update` date,
		  `single_update_page` tinyint(1) NOT NULL DEFAULT '1',
		  `paysite_update_page` varchar(254) NOT NULL,
		  `paysite_update_page_video` varchar(254) NOT NULL,
		  `update_page_md5` varchar(254) NOT NULL,
		  `update_page_video_md5` varchar(254) NOT NULL,
		  `updates_checked_on` bigint(20) NOT NULL DEFAULT 0,
		  `video_updates_checked_on` bigint(20) NOT NULL DEFAULT 0,
		  `last_update_page_check` bigint(20) NOT NULL,
		  `update_type` enum('manual','blog','xml','site') DEFAULT NULL,
		  `update_type_video` enum('manual','blog','xml','onsite') DEFAULT NULL,
		  `set_cropped` tinyint(4) NOT NULL DEFAULT '0',
		  `use_original_ids` tinyint(3) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`paysite_id`),
		  KEY `last_update` (`last_update`),
		  KEY `hosted_flag` (`hosted_flag`),
		  KEY `paysite_category` (`paysite_category`),
		  KEY `set_cropped` (`set_cropped`)
		)  ENGINE=InnoDB   DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `processing_galleries` (
		  `gal_id` int(11) NOT NULL,
		  `process` enum('video_screening','unzipping','unzipped','fetching','fetched','thumbing','thumbed','pics_resizing','pics_resized','video_converting','video_converted','video_merging','video_merged','error') COLLATE utf8_bin NOT NULL,
		  `added` INT(11) NOT NULL DEFAULT '0',
		  `error` varchar(254) COLLATE utf8_bin  NOT NULL DEFAULT '',
		  `prev_status` varchar(254) COLLATE utf8_bin NOT NULL DEFAULT '',
		  UNIQUE KEY `gal_id` (`gal_id`),
		  KEY `process` (`process`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `related_rebuilding_query` (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `site_id` int(10) unsigned NOT NULL,
		  `local_id` int(10) unsigned NOT NULL,
		  `gal_type` varchar(64) COLLATE utf8_bin NOT NULL,
		  `priority` smallint(6) NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `priority` (`priority`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_gallery_manual_recrop` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `user_id` int(10) unsigned NOT NULL,
		  `added` int(11) NOT NULL,
		  `recrop_reason` varchar(254) COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `gal_id` (`gal_id`),
		  KEY `user_id` (`user_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_manual_crop_history` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `image_id` int(10) unsigned NOT NULL,
		  `x_coord` int(10) unsigned NOT NULL,
		  `y_coord` int(10) unsigned NOT NULL,
		  `width` int(10) unsigned NOT NULL,
		  `height` int(10) unsigned NOT NULL,
		  `thumb_type` enum('vertic','horiz','square') COLLATE utf8_bin NOT NULL,
		  `updated` int(10) unsigned NOT NULL,
		  `user_id` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `image_id` (`image_id`),
		  KEY `updated` (`updated`),
		  KEY `user_id` (`user_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_manual_model_crop_history` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `model_id` int(10) unsigned NOT NULL,
		  `image_id` int(10) unsigned NOT NULL,
		  `x_coord` int(10) unsigned NOT NULL,
		  `y_coord` int(10) unsigned NOT NULL,
		  `width` int(10) unsigned NOT NULL,
		  `height` int(10) unsigned NOT NULL,
		  `thumb_type` enum('vertic','horiz','square') COLLATE utf8_bin NOT NULL,
		  `updated` int(10) unsigned NOT NULL,
		  `user_id` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `image_id` (`image_id`),
		  KEY `updated` (`updated`),
		  KEY `user_id` (`user_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_manual_recropped` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(11) NOT NULL,
		  `user_id` int(11) NOT NULL,
		  `date_recropped` int(11) NOT NULL,
		  PRIMARY KEY (`id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_users_list` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_name` varchar(64) COLLATE utf8_bin NOT NULL,
		  `user_pass` varchar(254) COLLATE utf8_bin NOT NULL,
		  `user_allowed_ip` bigint(20) NOT NULL,
		  `allowed_operations` enum('crop','tags','croptags','admin','descs') COLLATE utf8_bin NOT NULL,
		  `language` enum('en','nl','cz','de','be','es','ru','by','cn','jp','it','fr') COLLATE utf8_bin NOT NULL,
		  `add_models` enum('allowed','disallowed') COLLATE utf8_bin NOT NULL,
		  `user_added` int(11) NOT NULL,
		  `user_last_login` int(11) NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `user_name` (`user_name`),
		  KEY `user_allowed_ip` (`user_allowed_ip`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_users_allowed_ips` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) unsigned NOT NULL,
		  `user_ip` bigint(20) NOT NULL,
		  `added` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `user_ip_unique` (`user_id`,`user_ip`),
		  KEY `user_id` (`user_id`),
		  KEY `user_ip` (`user_ip`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_user_skeep_gallery` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `user_id` int(10) unsigned NOT NULL,
		  `skeep_reason` varchar(254) COLLATE utf8_bin NOT NULL,
		  `skeep_type` enum('tags','crop') COLLATE utf8_bin NOT NULL,
		  `added` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `gal_id` (`gal_id`,`skeep_type`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_working_list` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(11) unsigned NOT NULL,
		  `user_id` int(11) NOT NULL,
		  `work_type` enum('tags','crop') COLLATE utf8_bin NOT NULL,
		  `change_time` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `gal_id` (`gal_id`),
		  KEY `user_id` (`user_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `sites` (
		  `site_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `local_id_flag` enum('0','1') NOT NULL DEFAULT '0',
		  `only_export_site` TINYINT NOT NULL DEFAULT '0',		  
		  `site_name` varchar(255) NOT NULL DEFAULT '',
		  `site_niche` enum('All','Gay','Straight','Shemale') NOT NULL DEFAULT 'All',
		  `hand_flag` tinyint(1) NOT NULL DEFAULT '0',
		  `site_thumb_size` enum('150x205','180x240') NOT NULL DEFAULT '150x205',
		  `site_main_category` int(10) unsigned NOT NULL DEFAULT '0',
		  `site_categories` varchar(255) NOT NULL DEFAULT '',
		  `site_categories_exclude` varchar(255) NOT NULL DEFAULT '',
		  `or_tag` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'site_category OR or_tag',
		  `sites_gallery_url` varchar(255) NOT NULL DEFAULT '',
		  `sites_url_length` int(11) NOT NULL DEFAULT '70',
		  `upload_flag` enum('cache','ftp','local') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'ftp',
		  `site_ftp` varchar(255) NOT NULL DEFAULT '',
		  `site_login` varchar(64) NOT NULL DEFAULT '',
		  `site_pass` varchar(64) NOT NULL DEFAULT '',
		  `site_ftp_folder` varchar(255) NOT NULL DEFAULT '',
		  `last_update` bigint(20) NOT NULL DEFAULT '0',
		  `redis_server` int(11) NOT NULL DEFAULT '0',
		  `additional_redis_server` tinyint(3) NOT NULL DEFAULT '-1',
		  `pageviews_updated_on` int(11) NOT NULL DEFAULT '0',
		  `likes_updated_on` int(11) NOT NULL DEFAULT '0',
		  `accept_gifs` tinyint(4) NOT NULL DEFAULT '0',
		  `use_embed` tinyint(4) NOT NULL DEFAULT '0',
		  `site_type` enum('video','pics','gif','mix') NOT NULL DEFAULT 'mix',
		  `site_own_titles` tinyint(4) NOT NULL DEFAULT '0',
		  `site_own_main_thumbs` tinyint(4) NOT NULL DEFAULT '0',
		  `language` enum('en','nl','cz','de','be','es','ru','by','cn','jp','it','fr') CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'en',
		  `keywords` varchar(254) DEFAULT '',
		  `default_title_for_tag` VARCHAR( 254 ) CHARACTER SET utf16 COLLATE utf16_bin NOT NULL DEFAULT 'Free #TAG_NAME#',
		  `use_galleries_from` mediumint(8) unsigned NOT NULL DEFAULT '0',
		  `use_unique_tags` TINYINT NOT NULL DEFAULT '0' COMMENT 'only for satellite sites',
		  `digit_base_for_id` tinyint(1) unsigned NOT NULL DEFAULT '10',
		  `thumb_by_horiz_width` smallint(5) unsigned NOT NULL DEFAULT '0',
		  `max_times_used_gals` tinyint(4) NOT NULL DEFAULT '-1',
		  PRIMARY KEY (`site_id`),
		  KEY `pageviews_updated_on` (`pageviews_updated_on`),
		  KEY `likes_updated_on` (`likes_updated_on`),
		  KEY `accept_gifs` (`accept_gifs`),
		  KEY `site_type` (`site_type`),
		  KEY `language` (`language`),
		  KEY `language_2` (`language`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `sites_galleries` (
		  `gal_id` bigint(20) unsigned NOT NULL,
		  `site_id` int(10) unsigned NOT NULL,
		  `time_added` int(11) NOT NULL,
		  `random_number` int(11) NOT NULL,
		  PRIMARY KEY (`gal_id`,`site_id`),
		  KEY `gal_id` (`gal_id`),
		  KEY `random_number` (`random_number`),
		  KEY `time_added` (`time_added`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `sites_galleries_make_query` (
		  `site_id` int(10) unsigned NOT NULL,
		  `gal_id` int(10) unsigned NOT NULL,
		  `gallery_unique` int(10) unsigned NOT NULL DEFAULT '0',
		  `title` varchar(254) COLLATE utf8_bin NOT NULL,
		  `main_thumb` int(10) unsigned NOT NULL,
		  `query_on` int(10) unsigned NOT NULL,
		  UNIQUE KEY `site_gallery_id` (`site_id`,`gal_id`),
		  KEY `query_on` (`query_on`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

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
		  `md5` VARCHAR( 64 ) NOT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `model_site_u` (`model_id`,`site_id`),
		  KEY `model_id` (`model_id`,`site_id`,`likes`,`pageviews`),
		  KEY `added_on` (`added_on`),
		  KEY `gals_count` (`gals_count`,`video_count`),
		  KEY `total_count` (`total_count`),
		  KEY `category_of_age` (`category_of_age`),
		  KEY `sort_name` (`name`),
		  KEY `name` (`name`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

	$sql[] = "CREATE TABLE IF NOT EXISTS `sites_tags` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `tag_id` int(10) unsigned NOT NULL,
				  `site_id` int(10) unsigned NOT NULL,
				  `name` varchar(64) NOT NULL,
				  `folder_name` varchar(64) NOT NULL,
				  `title` VARCHAR( 254 ) CHARACTER SET utf16 COLLATE utf16_bin NOT NULL,
				  `description` varchar(256) NOT NULL DEFAULT '',
				  `keywords` varchar(256) NOT NULL DEFAULT '',
				  `md5` varchar(128) NOT NULL,
				  `gals_count` int(10) unsigned NOT NULL DEFAULT '0',
				  `video_count` int(10) unsigned NOT NULL DEFAULT '0',
				  `total_count` int(10) unsigned NOT NULL DEFAULT '0',
				  `pageviews` bigint(20) unsigned NOT NULL DEFAULT '0',
				  `likes` int(10) unsigned NOT NULL DEFAULT '0',
				  `added_on` int(10) unsigned NOT NULL,
				  `updated_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `site_tag_u` (`tag_id`,`site_id`),
				  KEY `site_id` (`site_id`),
				  KEY `md5` (`md5`),
				  KEY `gals_count` (`gals_count`),
				  KEY `video_count` (`video_count`),
				  KEY `total_count` (`total_count`),
				  KEY `added_on` (`added_on`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `sites_cache_query` (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`site_id` int(11) NOT NULL,
				`cache_server_id` int(11) NOT NULL DEFAULT '0',
				`gal_id` int(11) NOT NULL,
				`gal_local_id` int(10) unsigned NOT NULL,
				`gal_type` enum('none','pics','movies','gif') NOT NULL DEFAULT 'none',
				`item_type` enum('tag','model','source','gallery') NOT NULL,
				`change_type` enum('added','removed','changed') NOT NULL,
				`item_id` int(10) unsigned NOT NULL,
				`added_on` int(11) NOT NULL,
				`updated_on` int(10) unsigned NOT NULL,
				`error` tinyint(3) unsigned NOT NULL DEFAULT '0',
				`error_msg` varchar(128) NOT NULL,
				PRIMARY KEY (`id`),
				KEY `site_id` (`site_id`),
				KEY `server_id` (`cache_server_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `sites_sources` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`source_id` int(10) unsigned NOT NULL,
				`site_id` int(10) unsigned NOT NULL,
				`name` varchar(64) NOT NULL,
				`folder_name` varchar(64) NOT NULL,
				`description` varchar(256) NOT NULL DEFAULT '',
				`keywords` varchar(256) NOT NULL DEFAULT '',
				`md5` varchar(128) NOT NULL,
				`gals_count` int(10) unsigned NOT NULL DEFAULT '0',
				`video_count` int(10) unsigned NOT NULL DEFAULT '0',
				`total_count` int(10) unsigned NOT NULL DEFAULT '0',
				`pageviews` bigint(20) unsigned NOT NULL DEFAULT '0',
				`likes` int(10) unsigned NOT NULL DEFAULT '0',
				`added_on` int(10) unsigned NOT NULL,
				`updated_on` int(10) unsigned NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `site_source_u` (`source_id`,`site_id`),
				KEY `site_id` (`site_id`),
				KEY `md5` (`md5`),
				KEY `total_count` (`total_count`),
				KEY `video_count` (`video_count`),
				KEY `gals_count` (`gals_count`),
				KEY `added_on` (`added_on`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `tags_synonyms` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `tag_id` int(10) unsigned NOT NULL,
				  `synonym` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `synonym` (`synonym`),
				  KEY `tag_id` (`tag_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = 
				"CREATE TABLE IF NOT EXISTS `galleries_tags_import` (
				  `id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `gal_id` int(10) unsigned NOT NULL,
				  `tag_name` varchar(120) NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `gal_id` (`gal_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
		$sql[] = 
				"CREATE TABLE IF NOT EXISTS `tags_synonyms_blacklist` (
				  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  `name` varchar(64) NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY ( `name` )
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = 
				"CREATE TABLE IF NOT EXISTS `tags_candidates` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `tag_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `tag_name` (`tag_name`),
				  KEY `added_on` (`added_on`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = 
				"CREATE TABLE IF NOT EXISTS `tags_candidates_galleries` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `gal_id` int(10) unsigned NOT NULL,
				  `tag_candidate_id` int(10) unsigned NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `gal_id` (`gal_id`,`tag_candidate_id`)
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

		$sql[] = "CREATE TABLE IF NOT EXISTS `tags` (
		  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `tag_name` varchar(64) NOT NULL DEFAULT '',
		  `main_tag_id` INT NOT NULL DEFAULT '0',
		  `tag_niche` set('Gay','Straight','Shemale') NOT NULL DEFAULT '',
		  `tag_category` enum('Action','Category') NOT NULL DEFAULT 'Action',
		  `approved` TINYINT UNSIGNED NULL DEFAULT '0',
		  `niche` enum('all','gay','straight','shemale') NOT NULL,
		  PRIMARY KEY (`tag_name`),
		  UNIQUE KEY `tag_id` (`tag_id`,`tag_name`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `templates` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `site_id` int(11) NOT NULL,
		  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `mobile` tinyint(3) unsigned NOT NULL,
		  `type` tinyint(3) unsigned NOT NULL,
		  `template` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `sub_template` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `changed_on` int(10) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `site_id` (`site_id`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `template_backups` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `template_id` int(11) NOT NULL,
		  `changed_on` int(11) NOT NULL,
		  `template` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  `sub_template` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `template_id` (`template_id`,`changed_on`)
		)  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `tempMoveTable` (
		  `gal_id` int(11) NOT NULL,
		  PRIMARY KEY (`gal_id`)
		) DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `thumbs_tags` (
		  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `thumb_id` int(10) unsigned NOT NULL,
		  `tag_id` int(10) unsigned NOT NULL,
		  `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `thumb_id` (`thumb_id`),
		  KEY `tag_id` (`tag_id`),
		  KEY `gal_id` (`gal_id`)
		)  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `trash` (
		  `gal_id` int(10) unsigned NOT NULL DEFAULT '0',
		  `gal_paysite` int(10) unsigned NOT NULL DEFAULT '0',
		  `gal_title` varchar(255) NOT NULL DEFAULT '',
		  `gal_description` text NOT NULL,
		  `gal_md5` varchar(64) NOT NULL DEFAULT '',
		  `gal_source` varchar(255) NOT NULL DEFAULT '',
		  `gal_images` text NOT NULL,
		  `gal_added` date,
		  `gal_content_count` int(11) NOT NULL DEFAULT '0',
		  `gal_niche` enum('Gay','Straight','Shemale') NOT NULL DEFAULT 'Gay',
		  `gal_type` enum('Pics','Movies') NOT NULL DEFAULT 'Pics',
		  `gal_thumbs_1` text NOT NULL,
		  `gal_rss_thumbs` text NOT NULL,
		  `gal_models` varchar(255) NOT NULL DEFAULT '',
		  `gal_pics_md5` text NOT NULL,
		  `gal_pics_source` text NOT NULL,
		  `gal_tags` text NOT NULL,
		  `gal_status` varchar(64) NOT NULL DEFAULT ''
		) DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `trash_box_thumbs` (
		  `image_id` bigint(20) unsigned NOT NULL,
		  `gal_id` bigint(20) unsigned NOT NULL,
		  `image` varchar(254) COLLATE utf8_bin NOT NULL,
		  `rss_flag` int(11) NOT NULL,
		  `user_id` int(11) NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  UNIQUE KEY `image_id` (`image_id`),
		  KEY `gal_id` (`gal_id`,`user_id`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `writers_titles` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `gal_id` int(10) unsigned NOT NULL,
		  `site_id` int(10) unsigned NOT NULL,
		  `main_thumb` int(10) unsigned NOT NULL,
		  `language` enum('en','nl','cz','de','be','es','ru','by','cn','jp','it','fr') NOT NULL,
		  `title` varchar(254) NOT NULL,
		  `deadline` int(10) unsigned NOT NULL,
		  `writer_id` int(10) unsigned NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  `updated_on` int(10) unsigned NOT NULL,
		  `title_length` int(10) unsigned NOT NULL,
		  `title_words_count` int(10) unsigned NOT NULL,
		  `used` tinyint(4) NOT NULL DEFAULT '0',
		  `is_ready` tinyint(4) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `gal_id` (`gal_id`,`site_id`,`deadline`),
		  KEY `used` (`used`),
		  KEY `is_ready` (`is_ready`)
		)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `trash_box_thumbs` (
		  `image_id` bigint(20) unsigned NOT NULL,
		  `gal_id` bigint(20) unsigned NOT NULL,
		  `image` varchar(254) COLLATE utf8_bin NOT NULL,
		  `rss_flag` int(11) NOT NULL,
		  `user_id` int(11) NOT NULL,
		  `added_on` int(10) unsigned NOT NULL,
		  UNIQUE KEY `image_id` (`image_id`),
		  KEY `gal_id` (`gal_id`,`user_id`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_bin;";


		$sql[] = "CREATE TABLE IF NOT EXISTS `scr_gallery_update_history` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `gal_id` bigint(20) NOT NULL,
			  `change_type` enum('thumb_removed','tag_added','tag_removed','gallery_removed','gallery_approved','model_added','model_removed','title_updated','rss_unset','rss_set','tag_to_thumb','tag_to_thumb_removed') COLLATE utf8_bin NOT NULL,
			  `item_id` bigint(20) NOT NULL DEFAULT '0',
			  `updated` int(11) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `gallery_id` (`gal_id`,`user_id`)
			) DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;";

$sql[] = "CREATE TABLE IF NOT EXISTS `cdn_sync_videos` (
	`file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`gal_id` int(10) unsigned NOT NULL,
	`sync_added_on` int(10) unsigned NOT NULL,
	`file_status` enum('new','ok','delete','request_sent','request_failed','error') NOT NULL DEFAULT 'new',
	`status_updated_on` int(10) unsigned NOT NULL,
	`file_size` bigint(20) unsigned NOT NULL,
	`error_message` varchar(200) NOT NULL DEFAULT '',
	PRIMARY KEY (`file_id`),
	KEY `file_status` (`file_status`),
	UNIQUE KEY `gal_id` (`gal_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";	
  

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


  $sql[] = "INSERT INTO `galleries_videos` 
			( gal_id, video_size, is_hd, cdn_synced, original_width, original_height, videos_types_available, video_status)
  			SELECT gal_id, 0, 5, 0, 0, 0, 'original', 'new' 
				FROM galleries 
				WHERE gal_type = 'Movies' AND gal_status IN ('uploaded', 'OK') 
						AND gal_id NOT IN (SELECT gal_id FROM galleries_videos)";

  $sql[] = "CREATE TABLE IF NOT EXISTS `galleries_video_previews` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`gal_id` int(10) unsigned NOT NULL,
	`preview_status` enum('new','queued','processing','ok','error','delete') NOT NULL DEFAULT 'new',
	`preview_format` enum('mp4','webm') NOT NULL DEFAULT 'mp4',
	`source_video_size` bigint(20) unsigned NOT NULL DEFAULT '0',
	`preview_size` bigint(20) unsigned NOT NULL DEFAULT '0',
	`preview_width` smallint(5) unsigned NOT NULL DEFAULT '0',
	`preview_height` smallint(5) unsigned NOT NULL DEFAULT '0',
	`preview_duration_ms` int(10) unsigned NOT NULL DEFAULT '0',
	`preview_bitrate` int(10) unsigned NOT NULL DEFAULT '0',
	`clip_count` tinyint(3) unsigned NOT NULL DEFAULT '10',
	`clip_length_ms` smallint(5) unsigned NOT NULL DEFAULT '1000',
	`start_offset` smallint(5) unsigned NOT NULL DEFAULT '5',
	`end_offset` smallint(5) unsigned NOT NULL DEFAULT '5',
	`generated_on` int(10) unsigned NOT NULL DEFAULT '0',
	`updated_on` int(10) unsigned NOT NULL DEFAULT '0',
	`error_message` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	UNIQUE KEY `gal_id` (`gal_id`),
	KEY `preview_status` (`preview_status`),
	KEY `updated_on` (`updated_on`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

  $sql[] = "INSERT INTO `galleries_video_previews`
			( gal_id, preview_status, preview_format, source_video_size, preview_size, preview_width, preview_height,
			  preview_duration_ms, preview_bitrate, clip_count, clip_length_ms, start_offset, end_offset, generated_on, updated_on, error_message )
			SELECT galleries_videos.gal_id, 'new', 'mp4', galleries_videos.video_size, 0, 0, 0,
				   0, 0, 10, 1000, 5, 5, 0, UNIX_TIMESTAMP(), ''
			FROM galleries_videos
			WHERE galleries_videos.video_status = 'ok'
			  AND galleries_videos.gal_id NOT IN (SELECT gal_id FROM galleries_video_previews)";

  $sql[] = "CREATE TABLE IF NOT EXISTS `video_preview_jobs` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`gal_id` int(10) unsigned NOT NULL,
	`preview_id` int(10) unsigned NOT NULL DEFAULT '0',
	`job_status` enum('new','processing','done','error') NOT NULL DEFAULT 'new',
	`callback_status` enum('none','pending','sent','partial','error') NOT NULL DEFAULT 'none',
	`preview_format` enum('mp4','webm') NOT NULL DEFAULT 'mp4',
	`requested_on` int(10) unsigned NOT NULL DEFAULT '0',
	`started_on` int(10) unsigned NOT NULL DEFAULT '0',
	`finished_on` int(10) unsigned NOT NULL DEFAULT '0',
	`worker_ip` varchar(45) NOT NULL DEFAULT '',
	`attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
	`error_message` varchar(255) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`),
	KEY `gal_id` (`gal_id`),
	KEY `job_status` (`job_status`),
	KEY `requested_on` (`requested_on`),
	KEY `callback_status` (`callback_status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

  $sql[] = "CREATE TABLE IF NOT EXISTS `video_preview_job_callbacks` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`job_id` int(10) unsigned NOT NULL,
	`gal_id` int(10) unsigned NOT NULL,
	`callback_url` varchar(255) NOT NULL,
	`callback_token` varchar(64) NOT NULL,
	`callback_status` enum('pending','sent','error') NOT NULL DEFAULT 'pending',
	`callback_attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
	`callback_last_on` int(10) unsigned NOT NULL DEFAULT '0',
	`callback_error` varchar(255) NOT NULL DEFAULT '',
	`added_on` int(10) unsigned NOT NULL DEFAULT '0',
	`notified_on` int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`),
	KEY `job_id` (`job_id`),
	KEY `gal_id` (`gal_id`),
	KEY `callback_status` (`callback_status`),
	KEY `added_on` (`added_on`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";




	

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


$sql[] = "CREATE TABLE IF NOT EXISTS `galleries_delete_rss` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`gal_id` int(10) unsigned NOT NULL,
		`site_id` int(10) unsigned NOT NULL,
		`gal_local_id` int(10) unsigned NOT NULL,
		`gal_type` varchar(16) NOT NULL DEFAULT '',
		`gal_url` varchar(240) NOT NULL,
		`added_on` int(10) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		KEY `gal_id` (`gal_id`),
		KEY `site_id` (`site_id`),
		KEY `gal_local_id` (`gal_local_id`),
		KEY `gal_type` (`gal_type`),
		KEY `added_on` (`added_on`),
		UNIQUE ( `gal_id` ,`site_id` )
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

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

$ip = $_SERVER['REMOTE_ADDR'];

try {
	$db = DB::getInstance();
	foreach($sql as $sql_q) {
		$db->exec($sql_q);
	}

	$operations = "admin";
	$users = new Users();
	$result = $users->insertUser("alexlz", "ioaj12n431", $ip, $operations);

	if($result) {
		echo "Добавлен {$result['name']}";
	}
} catch(Exception $e) {
	echo "Ошибка: ". $e->getMessage();
}

		

		
