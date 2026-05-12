<?php
	ini_set('display_errors','1');
  	error_reporting(E_ALL);

	require_once ("config/config.inc");
	require_once ("classes/class.logger.php");
	require_once ("classes/class.db_access.php");


	function createTableIfNotExist($table) {
		global $list_of_working_tables, $create_table_list, $tables_initializations_sql_list;

		$result = false;

		if($table && in_array($table, $list_of_working_tables)) {

			if(array_key_exists($table, $create_table_list)) {
				
				$sql = $create_table_list[$table];
				$db = DB::get();

				if($db) {
					$result = $db->query($sql);

					// усложнение связанное с инициализацией. вынести в отдельный метод
					if($result) {
						if(array_key_exists($table, $tables_initializations_sql_list)) {
							$db->query($tables_initializations_sql_list[$table]);
						}
					}
				}
			}

		} else {
			die("'".$table."' is NOT on tables LIST, script terminated!");
		}



		return $result;
	}


	function createSitesTableIfNotExist($site_prefix, $site_postfix) {
		global $sites_allowed_tables;

		$result = false;

		if(preg_match("#^(site_[0-9]{1,4})$#", $site_prefix) && in_array($site_postfix, $sites_allowed_tables)) {
				
			$db = DB::get();
			$sql = "";
			switch ($site_postfix) {
				case '_galleries_models':
						$sql = "CREATE TABLE IF NOT EXISTS `".$site_prefix."_galleries_models` (
							  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
							  `gal_id` int(10) unsigned NOT NULL,
							  `local_id` int(10) unsigned NOT NULL,
							  `model_id` int(10) unsigned NOT NULL,
							  `gal_type` enum('pics','movies','gif','none') NOT NULL DEFAULT 'none',
							  `added_on` int(10) unsigned NOT NULL,
							  PRIMARY KEY (`id`),
							  UNIQUE KEY `local_model` (`local_id`,`model_id`),
							  KEY `local_id` (`local_id`),
							  KEY `gal_type` (`gal_type`),
							  KEY `model_id` (`model_id`)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
					break;
				case '_galleries_tags':
						$sql = "CREATE TABLE IF NOT EXISTS `".$site_prefix."_galleries_tags` (
							  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
							  `gal_id` int(10) unsigned NOT NULL,
							  `local_id` int(10) unsigned NOT NULL,
							  `tag_id` int(10) unsigned NOT NULL,
							  `gal_type` enum('pics','movies','gif','none') NOT NULL DEFAULT 'none',
							  `added_on` int(10) unsigned NOT NULL,
							  PRIMARY KEY (`id`),
							  UNIQUE KEY `local_tag` (`local_id`,`tag_id`),
							  KEY `local_id` (`local_id`),
							  KEY `gal_type` (`gal_type`),
							  KEY `tag_id` (`tag_id`)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
					break;
				case '_exclude_gals':
						$sql = "CREATE TABLE IF NOT EXISTS `".$site_prefix."_exclude_gals` (
			  					`id` int(11) NOT NULL AUTO_INCREMENT,
			  					`gal_id` int(10) UNSIGNED NOT NULL,
			  					PRIMARY KEY (`id`),
			  					UNIQUE KEY `gal_id` (`gal_id`)
								) AUTO_INCREMENT=1 ;";
					break;
				

			}

			if($db) {
				$result = $db->query($sql);
			}		

		} else {
			die("'".$table."' is NOT on tables LIST, script terminated!");
		}



		return $result;
	}

	function addSiteColumnIfNotExist($site_prefix, $site_postfix, $column_to_fix) {
		global $sites_allowed_tables;

		$result = false;
		// var_dump($site_prefix, $site_postfix, $column_to_fix);
		if(preg_match("#^(site_[0-9]{1,4})$#", $site_prefix) 
		&& (in_array($site_postfix, $sites_allowed_tables) 
			|| $site_postfix == 'site')) {
				
			$db = DB::get();
			$sql = false;
			switch ($site_postfix) {
				case 'site':
						// сама таблица сайта
						$sql = "";
						if($column_to_fix == "status") {
							 $sql = "ALTER TABLE `".$site_prefix."` ADD `status` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `gal_id` ,
									 ADD INDEX ( `status` ) ;";
						} elseif($column_to_fix == "own_main_thumb") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD `own_main_thumb` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `gal_id`;";
						} elseif($column_to_fix == "own_title") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD `own_title`  VARCHAR( 254 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `gal_id`;";
						} elseif($column_to_fix == "gal_paysite") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD `gal_paysite` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `gal_id` ,
									ADD INDEX ( `gal_paysite` );";
						} elseif($column_to_fix == "gal_type") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD `gal_type` ENUM( 'pics', 'movies', 'gif', 'none' ) NOT NULL DEFAULT 'none' AFTER `gal_id` ,
									ADD INDEX ( `gal_type` );";
						} elseif($column_to_fix == "pageviews") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD `pageviews` BIGINT( 20 ) NOT NULL DEFAULT '0',
									ADD INDEX ( `pageviews` );";
						} elseif($column_to_fix == "likes") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD  `likes` BIGINT( 20 ) NOT NULL DEFAULT '0' ,
									ADD INDEX ( `likes` );";
						} elseif($column_to_fix == "rating") {
							$sql = "ALTER TABLE `".$site_prefix."` ADD  `rating` FLOAT UNSIGNED NOT NULL DEFAULT '0',
									ADD INDEX ( `rating` );";
						}
			
						
					break;
			}

			if($db && $sql) {
				$result = $db->query($sql);
			}		

		} else {
			die("'".$table."' is NOT on tables LIST, script terminated!");
		}



		return $result;
	}


	function addColumnIfNotExist($table, $column) {
		global $tables_column_add;

		$result = false;
		// var_dump($tables_column_add[$table][$column]);
		if($table && array_key_exists($table, $tables_column_add)) {

			if(array_key_exists($column, $tables_column_add[$table])) {
				
				$sql = $tables_column_add[$table][$column];
				$db = DB::get();

				if($db) {
					$result = $db->query($sql);
				}
			} else {
				echo "\t\t'".$column."' is NOT on column fix LIST!<br>";	
			}

		} else {
			echo "\t\t'".$table."' is NOT on tables LIST where possible to fix column!<br>";
		}



		return $result;
	}

	function checkAndFixModelsMd5Column() {
		$result = false;
		$db = DB::get();
		$sql = "UPDATE sites_models
				INNER JOIN model ON sites_models.model_id = model.id_model
				SET sites_models.md5 = MD5(replace(replace(model.name, \"'\", ''), ' ', '-'))
				WHERE sites_models.md5 LIKE ''";
		if($db) {
			$result = $db->query($sql);
			if(!$result) {
				var_dump($db->error);
			}
		} else {
			echo __METHOD__.":No DB connection<br>";
		}
		return $result;
	}


	$tables_schemas = array(
		"sites_models" => array("id", "model_id", "site_id", "likes", "pageviews", "added_on", "updated_on", "gals_count", "video_count", "total_count", "category_of_age", "name", "md5"),
		"tags" => array( "tag_id", "tag_name", "tag_niche", "tag_category", "approved", "niche", "main_tag_id")
	);

	$tables_column_add = array(
		"sites_models" => array(
				"md5" => "ALTER TABLE `sites_models` ADD `md5` VARCHAR( 64 ) NOT NULL AFTER `name`, ADD INDEX ( `md5` ) ;",
			),
		"tags" => array(
				"approved" => "ALTER TABLE `tags` ADD `approved` TINYINT UNSIGNED NULL DEFAULT '0' AFTER `tag_category` ;",
				"main_tag_id" => "ALTER TABLE `tags` ADD `main_tag_id` INT NOT NULL DEFAULT '0' AFTER `tag_name` ;"
			)		
	);




	$create_table_list = array(
		"galleries_changes_query" =>

			"CREATE TABLE IF NOT EXISTS `galleries_changes_query` (
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
				  KEY `error` (`error`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;",


		"images_sources" =>

			"CREATE TABLE IF NOT EXISTS `images_sources` (
				`thumb_id` int(11) NOT NULL AUTO_INCREMENT,
				`gal_id` int(11) NOT NULL,
				`image_source` varchar(256) COLLATE utf8_bin NOT NULL,
				`hash` varchar(64) COLLATE utf8_bin NOT NULL,
				`image_hash` varchar(254) COLLATE utf8_bin NOT NULL,
				`status` enum('new','fetching','fetching_fail','fetched','thumbing','thumbing_fail','tocrop','cropping','ok','error','delete') COLLATE utf8_bin NOT NULL DEFAULT 'new' COMMENT 'new - new thumb, fetching - fetching thumb, fetching_fail, fetched, thumbing - making thumb, thumbing_fail, tocrop - to crop manualy, cropping - cropping process, ok - ОК, error - some other error, shown in info',
				`status_change_time` int(11) NOT NULL,
				`info` varchar(256) COLLATE utf8_bin NOT NULL COMMENT 'used unix time when using processing status, or string for error',
				`zip` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1  в случае ZIP, 0 в случае не ZIP',
				PRIMARY KEY (`thumb_id`),
				UNIQUE KEY `hash` (`hash`),
				KEY `gal_id` (`gal_id`),
				KEY `status` (`status`),
				KEY `image_hash` (`image_hash`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;",


		"sites_cache_query" =>

			"CREATE TABLE IF NOT EXISTS `sites_cache_query` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",

		"sites_sources" =>

			"CREATE TABLE IF NOT EXISTS `sites_sources` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",


		"sites_tags" =>

			"CREATE TABLE IF NOT EXISTS `sites_tags` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `tag_id` int(10) unsigned NOT NULL,
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
				  UNIQUE KEY `site_tag_u` (`tag_id`,`site_id`),
				  KEY `site_id` (`site_id`),
				  KEY `md5` (`md5`),
				  KEY `gals_count` (`gals_count`),
				  KEY `video_count` (`video_count`),
				  KEY `total_count` (`total_count`),
				  KEY `added_on` (`added_on`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=421 ;",


		"tags_synonyms" => 

				"CREATE TABLE IF NOT EXISTS `tags_synonyms` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `tag_id` int(10) unsigned NOT NULL,
				  `synonym` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `synonym` (`synonym`),
				  KEY `tag_id` (`tag_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
		"galleries_tags_import" =>
				"CREATE TABLE IF NOT EXISTS `galleries_tags_import` (
				  `id` bigint(20) NOT NULL AUTO_INCREMENT,
				  `gal_id` int(10) unsigned NOT NULL,
				  `tag_name` varchar(120) NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `gal_id` (`gal_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
		"tags_synonyms_blacklist"	=>
				"CREATE TABLE IF NOT EXISTS `tags_synonyms_blacklist` (
				  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				  `name` varchar(64) NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY ( `name` )
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
		"tags_candidates" => 
				"CREATE TABLE IF NOT EXISTS `tags_candidates` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `tag_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `tag_name` (`tag_name`),
				  KEY `added_on` (`added_on`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
		"tags_candidates_galleries" =>
				"CREATE TABLE IF NOT EXISTS `tags_candidates_galleries` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `gal_id` int(10) unsigned NOT NULL,
				  `tag_candidate_id` int(10) unsigned NOT NULL,
				  `added_on` int(10) unsigned NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `gal_id` (`gal_id`,`tag_candidate_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;",
	);

	$tables_initializations_sql_list = array(
		"tags_synonyms" => "INSERT INTO tags_synonyms
							  (tag_id, synonym, added_on)
							  SELECT tag_id, tag_name, ".time()."
							  FROM tags"
	);

	$list_of_working_tables = array("additional_titles", 
									"banners", 
									"banners_spots", 
									"banners_spots_content", 
									"cache_rebuild_process", 
									"cache_rebuild_query", 
									"caching_temp_t", 
									"crop_profiles", 
									"error", 
									"galleries", 
									"galleries_changes_query", 
									"galleries_models", 
									"galleries_pix", 
									"galleries_resized_to", 
									"galleries_source_pics", 
									"galleries_status_messages", 
									"galleries_tags", 
									"galleries_to_merge", 
									"grabber", 
									"hosts", 
									"images_sources", 
									"main_query", 
									"model", 
									"model_names", 
									"model_trash", 
									"models_images", 
									"paysites", 
									"processing_galleries", 
									"related_rebuilding_query", 
									"scr_gallery_manual_recrop", 
									"scr_gallery_update_history", 
									"scr_manual_crop_history", 
									"scr_manual_model_crop_history", 
									"scr_manual_recropped", 
									"scr_user_skeep_gallery", 
									"scr_users_list", 
									"scr_working_list", 
									"sites", 
									"sites_cache_query", 
									"sites_galleries", 
									"sites_galleries_make_query", 
									"sites_models", 
									"sites_searches", 
									"sites_sources", 
									"sites_tags", 
									"tags", 
									"template_backups", 
									"templates", 
									"thumbs_tags", 
									"trash", 
									"trash_box_thumbs", 
									"writers_titles",
									"tags_synonyms",
									"galleries_tags_import",
									"tags_synonyms_blacklist",
									"tags_candidates",
									"tags_candidates_galleries");
	
	$sites_allowed_tables = array(
		"_galleries_models",
		"_galleries_tags",
		"_exclude_gals"
	);

	$sites_tables_structure = array(
		"site" => array(
			"id", "gal_id", "gal_type", "gal_paysite", "status", "url_desc", "time_added", "pageviews", "likes", "rating", "own_main_thumb", "own_title"
		),
		"_galleries_models" => array(
			"id", "gal_id", "local_id", "model_id", "gal_type", "added_on"
		),
		"_galleries_tags" => array(
			"id", "gal_id", "local_id", "tag_id", "gal_type", "added_on"
		),
		"_exclude_gals" => array(
			"id", "gal_id"
		)
	);




	$db = DB::get();

	if($db) {
		echo "<h3>Check models tables (new table should be galleries_models with 3 columns  id, gal_id and model_id)</h3>";
		$galleries_models_columns = false;
		$sql = "SELECT COLUMN_NAME 
				FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = '".DBNAME."' 
				AND TABLE_NAME = 'galleries_models'";
		$stmt = $db->prepare($sql);
		if($stmt) {
			$stmt->execute();
			if($stmt->bind_result($c_name)) {
				while($stmt->fetch()) {
					$galleries_models_columns[] = $c_name;
				}
			}
		}
		$items_count = 0;
		$sql = "SELECT count(gal_id) FROM galleries_models";
		$stmt = $db->prepare($sql);
		if($stmt) {
				$stmt->execute();
				if($stmt->bind_result($c_name)) {
					if($stmt->fetch()) {
						if($c_name == 0) {
							$items_count = $c_name;
						}
					} else {
						echo "STMT error '".$stmt->error."'";
					}
				} else {
					echo "STMT error '".$stmt->error."'";
				}
				$stmt->close();
		} else {
			echo "Could not prepare SQL statment, possibly galleries_models table not exists. DB error '".$db->error."'<br>";
		}
			$model_gallery = false;
			$sql = "SELECT COLUMN_NAME 
					FROM INFORMATION_SCHEMA.COLUMNS 
					WHERE TABLE_SCHEMA = '".DBNAME."' 
					AND TABLE_NAME = 'model_gallery'";
			$stmt = $db->prepare($sql);
			if($stmt) {
				$stmt->execute();
				if($stmt->bind_result($c_name)) {
					while($stmt->fetch()) {
						$model_gallery[] = $c_name;
					}
				} else {
					echo "STMT error '".$stmt->error."'";
				}
				$stmt->close();
			} else {
				echo "DB error :'".$sql."', error: '".$db->error."'<br>";
			}

		if($galleries_models_columns && count($galleries_models_columns) == 2) {
			echo "Looks like it's an old version of galleries_models table<br>
			\tchecking if OLD table model_gallery exists to fix issue .. ";
			if($model_gallery) {
				echo " .. old table exists, we could fix that shit<br>";
				echo "\t\tDropping model_gallery table .. ";
				$sql = "DROP TABLE IF EXISTS galleries_models";
				$drop_result = $db->query($sql);
				if($drop_result) {
					echo " .. dropped .. renaming model_gallery to galleries_models .. ";
					$sql = "RENAME TABLE `model_gallery` TO `galleries_models` ;";
					$rename_result = $db->query($sql);
					if($rename_result) {
						echo "model's galleries table FIXED<br>";
					} else {
						echo " renaming failed..<br>";
					}
				} else {
					echo " drop failed..<br>";
				}
			} else {
				echo " .. Error no old tables detected!!<br>";
			}
		} elseif(count($galleries_models_columns) == 3) {
			var_dump($galleries_models_columns);
			echo "galleries models table seems fine. (".$items_count." items total)<br>";
		} else {
			echo "Error detected, possibly there is no galleries_models table";
			if($model_gallery) {
					echo " .. old table exists, we could fix that shit<br>";
					$sql = "RENAME TABLE `model_gallery` TO `galleries_models` ;";
					$rename_result = $db->query($sql);
					if($rename_result) {
						echo "model's galleries table FIXED<br>";
					} else {
						echo " renaming failed..<br>";
					}
	 
			} else {
				echo " .. Error no old tables detected!! Could not fix that one :(<br>";
			}
		}
		
	}


	if($db) {
		echo "<h3>Check and fix if there unknown/missed tables</h3>";
		$sql = "show tables";
		$stmt = $db->prepare($sql);
		if($stmt) {
			$stmt->execute();
			if($stmt->bind_result($c_name)) {
				$list_of_actual_tables = array();
				while($stmt->fetch()) {
					$list_of_actual_tables[] = $c_name;
				}

				$sites_array = array();

				echo "<h3>Check there unknown tables</h3>";

				foreach($list_of_actual_tables as $actual_table) {
					if(preg_match("#^(site_[0-9].*[a-z_]*)#", $actual_table)) {
						$matches = false;
						preg_match_all("#^((site_[0-9]{1,4})[a-z_]*)#", $actual_table, $matches);
						if($matches && isset($matches[2][0])) {
							$site_table_prefix = $matches[2][0];
							$site_table_postfix = $matches[0][0];
							if(strlen($site_table_prefix) != strlen($site_table_postfix)) {
								$site_table_postfix = substr($site_table_postfix, strlen($site_table_prefix), strlen($site_table_postfix));
								// var_dump($site_table_postfix);
								if(!in_array($site_table_postfix, $sites_allowed_tables)) {
									echo "Unknown table in DB: ".$site_table_prefix.$site_table_postfix ."<br>";
								}
							} else {
								$sites_array[] = $site_table_prefix;
								// сама таблица сайта
							}								

						}
					} else {
						if(!in_array($actual_table, $list_of_working_tables)) {
							echo "Unknown table in DB: ".$actual_table ."<br>";
						}
					}
					
				}

				echo "<h3>Check and fix missed tables</h3>";

				foreach($list_of_working_tables as $working_table) {
					if(!in_array($working_table, $list_of_actual_tables)) {
						if(!preg_match("#^(site_[0-9].*[a-z]*)#", $working_table)) {
							echo "Missing table in DB: ". $working_table ." ";
							$fixed_table = createTableIfNotExist($working_table);
							if($fixed_table) echo "<font color='green'>Fixed table</font>";
							else  echo "<font color='red'>Failed to fix</font>";
							echo "<br>";

						}
					}
				}

				echo "<h3>Check and fix sites tables</h3>";
				$check_error = false;
				foreach($sites_array as $site_table) {
					foreach($sites_allowed_tables as $site_table_postfix) {
						$working_table = $site_table.$site_table_postfix;
						if(!in_array($working_table, $list_of_actual_tables)) {
							echo "Missing table in DB: ". $working_table ."<br>";
							$fixed_table = createSitesTableIfNotExist($site_table, $site_table_postfix);
							if($fixed_table) echo "<font color='green'>Fixed table</font>";
							else  echo "<font color='red'>Failed to fix</font>";
							echo "<br>";
							$check_error++;
						}
					}
				}

				if($check_error) {
					echo "\tFound ".$check_error." errors<br>";
				} else {
					echo "\tNo errors found<br>";
				}

			} else {
				echo "<font color='red'>DB error, chack failed</font>";
				$log = new Logger(__METHOD__.": STMT bind_result error: '".$stmt->error."'", true);
			}					
		} else {
			echo "<font color='red'>DB error, chack failed</font>";
			$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
		}

		echo "<h3>Check and fix tables structure</h3>";
		foreach($list_of_working_tables as $working_table) {
			if(array_key_exists($working_table, $tables_schemas)) {
				$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".DBNAME."' AND TABLE_NAME = '".$working_table."';";
				$stmt = $db->prepare($sql);
				echo "Check schema for '".$working_table."' .. ";
				if($stmt) {
					$stmt->execute();
					if($stmt->bind_result($c_name)) {
						$list_of_actual_tables = array();
						$list_of_actual_columns = array();
						while($stmt->fetch()) {
							$list_of_actual_columns[] = $c_name;
						}
						$check_error = false;
						echo "<h3>Checking '".$working_table."'</h3>";
						foreach($list_of_actual_columns as $actual_column) {
							if(!in_array($actual_column, $tables_schemas[$working_table])) {
								echo "\tUnknown column in table ".$working_table.": ". $actual_column ."<br>";
								$check_error = true;
							}
						}
						foreach($tables_schemas[$working_table] as $working_column) {
							if(!in_array($working_column, $list_of_actual_columns)) {
								echo "\tUnknown column in table ".$working_table.": ". $working_column .". Fixing.. ";
								$fixed_column = addColumnIfNotExist($working_table, $working_column);
								if($fixed_column) echo "<font color='green'>Fixed table</font>";
								else  echo "<font color='red'>Failed to fix</font>";
								echo "<br>";
								$check_error = $fixed_column;
							}
						}
						if($check_error) {
							echo "<font color='red'>Failed</font><br>";
						} else {
							echo "<font color='green'>OK</font><br>";
						}
					} else {
						echo "<font color='red'>DB error, chack failed</font><br>";
						$log = new Logger(__METHOD__.": STMT bind_result error: '".$stmt->error."'", true);
					}					
				} else {
					echo "<font color='red'>DB error, chack failed</font><br>";
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				echo "Schema for '".$working_table."' was not added to checklist<br>";
			}
			
		}
		echo "<h3>Check and fix Sites tables structures</h3>";
		foreach($sites_array as $site) {
			foreach($sites_tables_structure as $table_name => $table_structure) {
				if($table_name == "site") {
					$working_table = $site;
				} else {
					$working_table = $site . $table_name;
				}

					$sql = "SELECT COLUMN_NAME 
							FROM INFORMATION_SCHEMA.COLUMNS 
							WHERE TABLE_SCHEMA = '".DBNAME."' 
							AND TABLE_NAME = '".$working_table."';";
					// var_dump($sql);
					$stmt = $db->prepare($sql);
					echo "Check schema for '".$working_table."' .. ";
					if($stmt) {
						$stmt->execute();
						if($stmt->bind_result($c_name)) {
							$list_of_actual_columns = array();
							while($stmt->fetch()) {
								$list_of_actual_columns[] = $c_name;
							}
							$check_error = false;
							// var_dump($list_of_actual_columns);
							foreach($list_of_actual_columns as $actual_column) {
								if(!in_array($actual_column, $sites_tables_structure[$table_name])) {
									echo "\tUnknown column in table ".$working_table.": ". $actual_column ."<br>";
									$check_error = true;
								}
							}

							foreach($sites_tables_structure[$table_name] as $working_column) {
								if(!in_array($working_column, $list_of_actual_columns)) {
									echo "\tNot found column ".$working_column." in table ".$working_table." . Fixing.. ";
									$fixed_column = addSiteColumnIfNotExist($site, $table_name, $working_column);
									if($fixed_column) echo "<font color='green'>Fixed table</font>";
									else  echo "<font color='red'>Failed to fix</font>";
									echo "<br>";
									$check_error = $fixed_column;
								}
							}
							if($check_error) {
								echo "<font color='red'>Found errors, performed fix</font><br>";
							} else {
								echo "<font color='green'>No errors for the table</font><br>";
							}
						} else {
							echo "<font color='red'>DB error, chack failed</font><br>";
							$log = new Logger(__METHOD__.": STMT bind_result error: '".$stmt->error."'", true);
						}					
					} else {
						echo "<font color='red'>DB error, chack failed</font><br>";
						$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
					}

			}
		}
		echo "<h3>Check and fix sites_models MD5 column</h3>";
		echo "Checking .. ";
		echo checkAndFixModelsMd5Column() ? "<font color='green'>OK</font>" : "<font color='red'>Failed</font>";
		echo "<br>";
		$db = DB::get();

		if($db) {
			$sql = "ALTER TABLE `galleries_changes_query` CHANGE `item_type` `item_type` ENUM( 'tag', 'model', 'source', 'gallery', 'image' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;";
			$result = $db->query($sql);
		}
	} else {
		echo "DB connection failed!";
	}

?>