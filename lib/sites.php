<?php
if(isset($_GET['site_tags'])) {
	$site_id = $_GET['site_tags'];
	$sort_by = "name";
	include "lib/sites_tags.php";
} else {

	//	добавить оценку того какой был предварительный парамтр у additional_redis_server
	//  для того чтобы при изменении с 0 на другой вариант, там перестраивался кэш, 
	//  либо при выставлении с какого-то параметра в 0, удалялся кеш

// разобраться с доп среверами, (-1 или 0 на отсутствие)

if(isset($_GET['videos_listing'])) {
	Galleries::getVideosListFromSite($_GET['videos_listing']);
}


$site_worker = new Sites($db->_db);

// $site_worker->fixSitesTags($_GET['site']);
if(isset($_GET['delete_site'], $_GET['for_real'], $_GET['site'])) {
	$site_id = (int)$_GET['site'];
	if($site_id > 0) {
		$site_worker->deleteSite($site_id);
		die;	
	}
	
} elseif(isset($_GET['cut_stats_to_week']) && isset($_GET['site_id'])) {
	$cache_worker->cutStatsToWeek($_GET['site_id']);
} elseif(isset($_GET['models_pageviews_to_db']) && isset($_GET['site_id'])) {
	echo "<h4>Resynch models' Pageviews from main server's Redis:</h4><br>";
	$cache_worker->siteModelsPageviewsToDb($_GET['site_id']);
	echo "<h4>Resynch models' Likes from main server's Redis:</h4><br>";
	$cache_worker->siteModelLikesToDb($_GET['site_id']);
	
} elseif(isset($_GET['site_sources']) && isset($_GET['site'])) {
	$s_t = $site_worker->new_getSiteSources($_GET['site']);
	foreach ($s_t as $value) {
		if($value['gals_count'] + $value['video_count'] != $value['total_count']) {
			echo "<font color='red'>error tag</font>";
		}
		echo $value['name'] ." - " . $value['gals_count'] ." - " . $value['video_count'] ." - " . $value['total_count'] ." - " . $value['added_on']."<br>";
	}
} elseif(isset($_GET['tables_rebuild']) && isset($_GET['site'])) {
	$site_id = (int)$_GET['site'];
	if($site_id > 0) {
		echo "Switching to site ". $site_id." .. ";
		if($sites_galleries->setSiteId($site_id)) {
			echo "OK<br>";
			echo "Поиск и удаление галер которые не должны быть в таблице сайта .. ";
			echo $sites_galleries->fixErrorGalleries() ? "OK" : "Failed";
			echo "<br>";
			$no_gal_type_gals_count = $sites_galleries->noGalTypeSiteGalleries($site_id);
			if($no_gal_type_gals_count) {
				echo "Нет типа галер у ".$no_gal_type_gals_count." галер, исправляю .. ";
				$sites_galleries->fixSiteGalleriesType($site_id);
				$no_gal_type_gals_count = $sites_galleries->noGalTypeSiteGalleries($site_id);
				echo "Осталось ".$no_gal_type_gals_count." галер без типа, возможна ошибка<br>";
			}
			echo "<h4>Проверка платников/источников сайта</h4>";

			$sites->fixSiteToNewTables($site_id);

			echo 'Проверка количества источников на сайте ... ';
			// чекает и исправляет нулевые значения gal_paysite
			$sites_galleries->fixSiteGalleriesSourcesNull($site_id);
			$site_sources = $sites->checkSourcesTables($site_id);
			
			if(!$site_sources || ($site_sources && $site_sources['status'] == 'error')) {
				echo '<font color="red">Тест провален '.$site_sources['main_table'].'|'.$site_sources['sites_sources_table'].'</font><br>';
			} elseif($site_sources && $site_sources['status'] == 'ok') {
				echo '<font color="green">Тест пройден '.$site_sources['main_table'].'|'.$site_sources['sites_sources_table'].'</font><br>';
			} else {
				echo '<font color="red">Тест провален, проверь логи</font><br>';
			}
			
			
			
		} else {
			echo "Error<br>";
		}
	} else {
		echo "<h1>Ошибка проверки сайтов, ID сайта имеет недопустимое значение</h1>";
	}
} elseif (isset($_GET['site_check_old'])) {
	$site_id = (int)$_GET['site_check'];
	if($site_id > 0) {


		echo 'Проверка количества источников на сайте ... ';
		$site_sources = $site_worker->checkSourcesTables($site_id);
		if($site_sources || $site_sources['status'] == 'error') {
			echo '<font color="red">Тест провален '.$site_sources['main_table'].'|'.$site_sources['sites_sources_table'].'</font><br>';
		} else {
			echo '<font color="green">Тест пройден</font><br>';
		}
		
		
	} else {
		"Ошибка в ID сайта";
		exit;
	}
} else {



	$siteId = false;
	$siteName = "";
	$siteNiche = false;
	$siteCategory = false;
	$siteURL = "";
	$exclude_category = false;
	$siteFtp = "";
	$siteFtpLogin = "";
	$siteFtpPass = "";
	$siteUploadFolder = "";
	$siteThumbSize = false;
	$silteUploadMethod = false;
	$siteUrlLength = "";
	$local_id_flag = false;
	$hand_flag = false;
	$tag_1 = 0;
	$tag_2 = 0;
	$or_tag = 0;
	$accept_gifs = 0;
	$site_type = 'mix';
	$newSite = true; // новый сайт
	$site_own_titles = 0;
	$site_own_main_thumbs = 0;
	$language = false;
	$use_galleries_from = 0;
	$digit_base_for_id = 10;
	$use_embed = 0;
	$use_by_horiz_thumbs = 0;
	$thumb_by_horiz_width = 0;
	$max_times_used_gals = -1;
	$additional_redis_server = -1;
	$vcdn_type = false;
	$use_unique_tags = false;
	$default_title_for_tag = false;

	$only_export_site = false;

	if (isset($_GET['cache_site_info_id'])) {
		$cache_worker->server_cacheSiteInfo($_GET['cache_site_info_id']);
		?><script>window.location = "?act=sites";</script><?php
	}


	if (isset($_GET['show_table_info'])) {
		$site_worker->getTableInfo();
	}


		if (isset($_POST['editsite']) || isset($_POST['addsite'])) {
				$and_tags = array();
				$or_tag = 0;
				//print_r($_POST);
				$name = $_POST['name'];
				$niche = $_POST['niche'];
				$url = $_POST['url'];
				$category = $_POST['category'];
				//удалить
				$ftp = false;
				$login = false;
				$pass = false;
				$folder= false;

				$method= 'cache';
				$local_id_flag = $_POST['local_id_flag'];
				$urlLength = $_POST['urlLength'];
				$exclude_category = $_POST['exclude_category'];
				$hand_flag = $_POST['hand_flag'];
				$accept_gifs = $_POST['accept_gifs'];
				$site_type = $_POST['site_type'];
				$site_own_titles = $_POST['site_own_titles'];
				$site_own_main_thumbs = $_POST['site_own_main_thumbs'];
				$language = $_POST['language'];
				$use_galleries_from = $_POST['use_galleries_from'];
				$digit_base_for_id = $_POST['digit_base_for_id'];
				$use_embed = $_POST['use_embed'];
				$use_by_horiz_thumbs = $_POST['use_by_horiz_thumbs'];
				$max_times_used_gals = $_POST['max_times_used_gals'];
				$additional_redis_server = $_POST['additional_redis_server'];
				$vcdn_type = $_POST['vcdn_type'];
				$use_unique_tags = $_POST['use_unique_tags'];
				$default_title_for_tag = $_POST['default_title_for_tag'];

				$thumb_by_horiz_width = ($use_by_horiz_thumbs) ? $_POST['thumb_by_horiz_width'] : false;

				
				$redis_server = (isset($_POST['redis_server'])) ? $_POST['redis_server'] : 0;
				if (isset($_POST['add_tag_1']) && intval($_POST['add_tag_1'])) $_POST['add_tag_1'];
				if (isset($_POST['add_tag_2']) && intval($_POST['add_tag_2'])) $and_tags[1] = $_POST['add_tag_2'];
				if (isset($_POST['or_tag']) && intval($_POST['or_tag'])) $or_tag = $_POST['or_tag'];

				$site_id = isset($_POST['site']) ? (int)$_POST['site'] : false;

				$only_export_site = (!empty($_POST['only_export_site']) && $_POST['only_export_site']) ? 1 : 0;
				

				$site_inf = compact('site_id', 'name', 'url', 'niche', 'urlLength', 'category', 'exclude_category', 
									'hand_flag', 'redis_server', 'and_tags', 'or_tag', 'accept_gifs', 'site_type', 
									'site_own_titles', 'site_own_main_thumbs', 'language', 'use_galleries_from', 
									'digit_base_for_id', 'use_embed', 'thumb_by_horiz_width', 'max_times_used_gals', 
									'additional_redis_server', 'vcdn_type', 'use_unique_tags', 'default_title_for_tag', 
									'local_id_flag', 'only_export_site');

				

				if (isset($_POST['editsite'])) {
					

					$site_updated = $site_worker->UpdateSite($site_inf);

					if($site_updated) {
						$cache_worker->server_cacheSiteInfo($site_id);

						echo "Сайт #{$site_id} обновлен<br>";	
					} else {
						echo "Ошибка! Сайт #{$site_id} не обновлен<br>";	
					}
					
				} else {
					

					$site_added = $site_worker->SiteAdd($site_inf);
					if ($site_added) {
						$cache_worker->server_cacheSiteInfo($site_added);

						echo "Добавлен сайт ". $site_added;						
					} else {
						echo "Ошибка добавления сайта!<br>";
					}
				}
		}

		if (isset($_GET['site'])|| (isset($_GET['add']) && $_GET['add'] == 'new')) {
			$siteCachedGalleriesNumber = false;
			if (isset($_GET['site'])) {
				$siteId = (int)$_GET['site'];
				if ($site = $default->SiteInformation($siteId)) {
				
					$siteId = $site['id'];
					$only_export_site = $site['only_export_site'];
					$siteName = $site['name'];
					$siteNiche = $site['niche'];
					$siteCategory = $site['category'];
					$siteURL = $site['galleryUrl'];
					$exclude_category = $site['exclude_category'];
					$siteFtp = $site['ftp'];
					$siteFtpLogin = $site['login'];
					$siteFtpPass = $site['pass'];
					$siteUploadFolder = $site['uploadFolder'];
					$siteThumbSize = $site['thumbSize'];
					$silteUploadMethod = $site['upload_flag'];
					$siteUrlLength = $site['urlLength'];
					$local_id_flag = $site['local_id_flag'];
					$hand_flag = $site['hand_flag'];
					$redis_server = $site['redis_server'];
					$tag_1 = $site['tag_1'];
					$tag_2 = $site['tag_2'];
					$or_tag = $site['or_tag'];
					$accept_gifs = $site['accept_gifs'];
					$site_type = $site['site_type'];
					$newSite = false;
					$site_own_titles = $site['site_own_titles'];
					$site_own_main_thumbs = $site['site_own_main_thumbs'];
					$language = $site['language'];
					$use_galleries_from = $site['use_galleries_from'];
					$digit_base_for_id = $site['digit_base_for_id'];
					$use_embed = $site['use_embed'];
					$vcdn_type = $site['vcdn_type'];
					$use_unique_tags = $site['use_unique_tags'];
					$default_title_for_tag = $site['default_title_for_tag'];

					if($site['thumb_by_horiz_width']) {
						$use_by_horiz_thumbs = 1;
						$thumb_by_horiz_width = $site['thumb_by_horiz_width'];
					}
					else {
						$use_by_horiz_thumbs = 0;
						$thumb_by_horiz_width = 0;	
					}
					$max_times_used_gals = $site['max_times_used_gals'];
					$additional_redis_server = $site['additional_redis_server'];
					$siteCachedGalleriesNumber = $cache_worker->siteGalleriesCount($siteId);
				}
			}
			if($siteId) {
				// $cache_worker->modelsLikesChangeToDb($siteId);
				// $cache_worker->modelsPageviewsChangeToDb($siteId);
				

				$scr_start = get_time();
				$cache_worker->updateSitesGalleriesPageviews($siteId);
				$cache_worker->updateSitesGalleriesLikes($siteId);
				$scr_finish = get_time();
				$scr_exec_time = $scr_finish - $scr_start;
				$log = new Logger("Site #".$siteId.", old updateSitesGalleriesPageviews exec time:".$scr_exec_time);				
?>
			<div style="width: 100%; padding: 10px; margin:10px; display: block; background-color: #CCC;">
				Сводная информация. Галер в основном кеше: <?=$siteCachedGalleriesNumber?> <br>
			</div>
<?php				
			}
?>
				<form id="sitesubmit" enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING']?>" method="post" onSubmit="return checkSiteForm()">
					<div align="center">
					<?php if (!$newSite) { ?><div style="display: none;"><input size="5" name="site" id="site" value="<?=$siteId?>"></div><?php } ?>
					<table class="disclaim" cellpadding="2" cellspacing="2" border="0">
						<?php if (!$newSite) { ?>
						<tr>
							<td bgcolor="#e4e4e4">ID: </td>
							<td bgcolor="#e4e4e4"><input size="5" name="site" id="site" value="<?=$siteId?>" disabled></td>
						</tr>
						<?php } ?>
						<tr>
							<td bgcolor="#e4e4e4">Название сайта (домен): </td>
							<td bgcolor="#e4e4e4">
								<input size="42" name="name" id="name" value="<?=$siteName?>">
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">URL сайта: </td>
							<td bgcolor="#e4e4e4">
								<input size="42" name="url" id="url" value="<?=$siteURL?>">
							</td>
						</tr>
						
						<tr>
							<td bgcolor="#e4e4e4">Мок сайт только для создания уникальности галер</td>
							<td bgcolor="#e4e4e4">
								<select name="only_export_site" id="only_export_site">
									<option value="0">Нет</option>
									<option value="1">Да</option>									
								</select>
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Это сайт-саттелит, использовать галлереи с сайта: </td>
							<td bgcolor="#e4e4e4">
								<select name="use_galleries_from" id="use_galleries_from">
									<option value="0">None</option>
									<?php	$default->AllMainSitesToString ("<option value=\"#SITE_ID#\"#CHECKED#>#SITE#</option>", $use_galleries_from); ?>
								</select>
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Система исчисления Local ID (по деволту десятичная система): </td>
							<td bgcolor="#e4e4e4">
								<select name="digit_base_for_id" id="digit_base_for_id">
									<option value="10" <?php if ($digit_base_for_id == 10 || $digit_base_for_id == 0 || !$digit_base_for_id) echo "selected='selected'"; ?>>10</option>
									<option value="16" <?php if ($digit_base_for_id == 16) echo "selected='selected'"; ?>>16</option>
									<option value="32" <?php if ($digit_base_for_id == 32) echo "selected='selected'"; ?>>32</option>
									<option value="36" <?php if ($digit_base_for_id == 36) echo "selected='selected'"; ?>>36</option>
									<option value="62" <?php if ($digit_base_for_id == 62) echo "selected='selected'"; ?>>62</option>
									<option value="64" <?php if ($digit_base_for_id == 64) echo "selected='selected'"; ?>>64</option>
									<option value="66" <?php if ($digit_base_for_id == 66) echo "selected='selected'"; ?>>66</option>
								</select>
						</tr>
						
						<tr>
							<td bgcolor="#e4e4e4">Длинна деска в урле сайта: </td>
							<td bgcolor="#e4e4e4">
								<input size="4" name="urlLength" id="urlLength" value="<?= $siteUrlLength ? $siteUrlLength : 200 ?>">
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Использовать локальные ID: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="local_id_flag" value="0"<?php if (!$local_id_flag) echo " checked"; ?>>Нет
								<input type="radio" name="local_id_flag" value="1"<?php if ($local_id_flag) echo " checked"; ?>>Да
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Тип vCDN: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="vcdn_type" value="dynamic"<?php if (!$vcdn_type || $vcdn_type == 'dynamic') echo " checked"; ?>>Динамик (HW)
								<input type="radio" name="vcdn_type" value="static"<?php if ($vcdn_type == 'static') echo " checked"; ?>>Статик (AH)
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Язык: </td>
							<td bgcolor="#e4e4e4">
								<select name="language">
								        <option value="en"<?php if (($language && $language == 'en') || !$language) echo " selected"?>>Английский</option>
								        <option value="ru"<?php if (($language && $language == 'ru')) echo " selected"?>>Русский</option>
								        <option value="nl"<?php if (($language && $language == 'nl')) echo " selected"?>>Голландский</option>
								        <option value="fr"<?php if (($language && $language == 'fr')) echo " selected"?>>Французский</option>
							</select>
							</td>
						</tr>						
						<tr>
							<td bgcolor="#e4e4e4">Принимать гифы: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="accept_gifs" value="0"<?php if ($accept_gifs == 0 ) echo " checked"; ?>>Нет
								<input type="radio" name="accept_gifs" value="1"<?php if ($accept_gifs == 1 ) echo " checked"; ?>>Да
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Принимать Embed: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="use_embed" value="0"<?php if ($use_embed == 0 ) echo " checked"; ?>>Нет
								<input type="radio" name="use_embed" value="1"<?php if ($use_embed == 1 ) echo " checked"; ?>>Да
							</td>
						</tr>
				
						<tr>
							<td bgcolor="#e4e4e4">Свои тайтлы: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="site_own_titles" value="0"<?php if ($site_own_titles == 0 ) echo " checked"; ?>>Нет
								<input type="radio" name="site_own_titles" value="1"<?php if ($site_own_titles == 1 ) echo " checked"; ?>>Да
							</td>
						</tr>
						<tr>
						<td bgcolor="#e4e4e4">Свои тумбы: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="site_own_main_thumbs" value="0"<?php if ($site_own_main_thumbs == 0 ) echo " checked"; ?>>Нет
								<input type="radio" name="site_own_main_thumbs" value="1"<?php if ($site_own_main_thumbs == 1 ) echo " checked"; ?>>Да
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Ручной отбор галер: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="hand_flag" value="0"<?php if ($hand_flag == 0 ) echo " checked"; ?>>Нет
								<input type="radio" name="hand_flag" value="1"<?php if ($hand_flag == 1 ) echo " checked"; ?>>Да
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Уникальные тайтлы: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="use_unique_tags" value="0"<?php if (!$use_unique_tags) echo " checked"; ?>>Нет
								<input type="radio" name="use_unique_tags" value="1"<?php if ($use_unique_tags) echo " checked"; ?>>Да
							</td>
						</tr>							
						<tr>
							<td bgcolor="#e4e4e4">Тип сайта: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="site_type" value="mix"<?php if ($site_type == 'mix' ) echo " checked"; ?>>Mix
								<input type="radio" name="site_type" value="pics"<?php if ($site_type == 'pics' ) echo " checked"; ?>>Pics
								<input type="radio" name="site_type" value="video"<?php if ($site_type == 'video' ) echo " checked"; ?>>Videos
								<input type="radio" name="site_type" value="gif"<?php if ($site_type == 'gif' ) echo " checked"; ?>>GIFs
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Niche: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="niche" value="Gay"<?php if ($siteNiche == 'Gay' || !$siteNiche) echo " checked"; ?>>Gay
								<input type="radio" name="niche" value="Straight"<?php if ($siteNiche == 'Straight' ) echo " checked"; ?>>Straight
								<input type="radio" name="niche" value="Shemale"<?php if ($siteNiche == 'Shemale' ) echo " checked"; ?>>Shemale
							</td>
						</tr>

						<tr>
							<td bgcolor="#e4e4e4">Добавлять только галеры использованные N раз: </td>
							<td bgcolor="#e4e4e4">
								<input type="radio" name="max_times_used_gals" value="-1"<?php if ($max_times_used_gals == -1 || $max_times_used_gals === false) echo " checked"; ?>>Принимать все
								<input type="radio" name="max_times_used_gals" value="0"<?php if ($max_times_used_gals == 0 ) echo " checked"; ?>>Только уникальные
								<input type="radio" name="max_times_used_gals" value="5"<?php if ($max_times_used_gals == 5 ) echo " checked"; ?>>До <b>5</b> раз
								<input type="radio" name="max_times_used_gals" value="10"<?php if ($max_times_used_gals == 10 ) echo " checked"; ?>>До <b>10</b> раз
								<input type="radio" name="max_times_used_gals" value="15"<?php if ($max_times_used_gals == 15 ) echo " checked"; ?>>До <b>15</b> раз
								<input type="radio" name="max_times_used_gals" value="20"<?php if ($max_times_used_gals == 20 ) echo " checked"; ?>>До <b>20</b> раз
							</td>
						</tr>
	

						<tr>
							<td bgcolor="#d4d4d4">Использовать тумбы по горизонтали: </td>
							<td bgcolor="#d4d4d4">
								<input type="radio" name="use_by_horiz_thumbs" value="0"<?php if ($use_by_horiz_thumbs == 0 ) echo " checked"; ?>>Нет
								<input type="radio" name="use_by_horiz_thumbs" value="1"<?php if ($use_by_horiz_thumbs == 1 ) echo " checked"; ?>>Да
								&nbsp;&nbsp;|&nbsp;&nbsp;Размер:
								<select name="thumb_by_horiz_width" id="thumb_by_horiz_width">
									<option value="0" <?php if (isset($thumb_by_horiz_width) && $thumb_by_horiz_width == '0') echo "selected='selected'"; ?>>Нет</option>
									<option value="300" <?php if (isset($thumb_by_horiz_width) && $thumb_by_horiz_width == '300') echo "selected='selected'"; ?>>300px</option>
									<option value="600" <?php if (isset($thumb_by_horiz_width) && $thumb_by_horiz_width == '600') echo "selected='selected'"; ?>>600px</option>
									<option value="800" <?php if (isset($thumb_by_horiz_width) && $thumb_by_horiz_width == '600') echo "selected='selected'"; ?>>800px</option>
								</select>
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Category: </td>
							<td bgcolor="#e4e4e4">
								<div style="float: left; width: 130px; display:block;">
									<select name="category" id="category" style="float: left;">
										<option value="0">General</option>
										<?php
											$default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $siteCategory);
										?>
									</select><br><br><br>
									<select name="or_tag" id="or_tag" style="float: left;">
										<option value="0">General</option>
										<?php
											$default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $or_tag);
										?>
									</select>
								</div>
								<div style="float: left; width: 30px; height: 20px; font-size: 20px; margin: 3px; padding-top: 18px; display:block;">
									OR
								</div>
							</td>
						</tr>

						<tr>
							<td bgcolor="#e4e4e4">AND! Categories: </td>
							<td bgcolor="#e4e4e4">
								<div>
								        <select id="add_tag_1" name="add_tag_1" style="float: left; font-size: 18px; height: 38px; margin: 5px; padding: 5px;">
               								<option value="0">No</option>
<?php												$default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $tag_1); ?>
	              						</select>
	              						<div style="float: left; padding: 0; margin: 2px;  margin-top: 5px;display: block;"></div>
           						</div>
								<div>
								        <select id="add_tag_2" name="add_tag_2" style="float: left; font-size: 18px; height: 38px; margin: 5px; padding: 5px;">
               								<option value="0">No</option>
<?php												$default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $tag_2); ?>
	              						</select>
	              						<div style="float: left; padding: 0; margin: 2px;  margin-top: 5px;display: block;"></div>
           						</div>           						
							</td>
						</tr>

															
						<tr>
							<td bgcolor="#e4e4e4">Excl. Category: </td>
							<td bgcolor="#e4e4e4">
								<select name="exclude_category" id="exclude_category">
									<option value="0">None</option>
									<?php
										$default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $exclude_category);
									?>
								</select>
							</td>
						</tr>						
						<tr>
							<td bgcolor="#e4e4e4">Redis Server</td>
							<td bgcolor="#e4e4e4">
								<select name="redis_server" id="redis_server">
									<?php
									CachingServers::reset();
									while(CachingServers::next()) { 
										$redis_server_id = CachingServers::currentID();
										$redis_server_name = CachingServers::currentName();
?>
										<option value="<?=$redis_server_id?>" <?php if (isset($redis_server) && $redis_server == $redis_server_id ) echo "selected='selected'"; ?>><?=$redis_server_name?></option>
<?php								}
									?>
								</select>
							</td>
						</tr>

						<tr>
							<td bgcolor="#e4e4e4">Additional Redis Server</td>
							<td bgcolor="#e4e4e4">
								<select name="additional_redis_server" id="additional_redis_server">
									<option value="-1" <?php if ($additional_redis_server === false || $additional_redis_server == -1) echo "selected='selected'"; ?>>Нет</option>
									<?php
									// var_dump($additional_redis_server);
									CachingServers::reset();
									while(CachingServers::next()) { 
										$redis_server_id = CachingServers::currentID();
										$redis_server_name = CachingServers::currentName();
?>
										<option value="<?=$redis_server_id?>" <?php if ($additional_redis_server == $redis_server_id ) echo "selected='selected'"; ?>><?=$redis_server_name?></option>
<?php								}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<td bgcolor="#e4e4e4">Дефолтный тайтл для тегов: </td>
							<td bgcolor="#e4e4e4">
								<input size="52" name="default_title_for_tag" id="default_title_for_tag" value="<?=$default_title_for_tag?>">
							</td>
						</tr>
						
					</table>
					<?php if (!$newSite) { ?>
						<input type="submit" value="Изменить сайт >>" name="editsite" id="editsite" />
					<?php } else { ?>
						<input type="submit" value="Добавить сайт >>" name="addsite" id="addsite" />
					<?php } ?>
					</div>
				</form>


<?php
		} else {

			
			$sites_count 	= (isset($_REQUEST['sites_count'])) ? $_REQUEST['sites_count'] : 50;
			$page 			= (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 0;
			$niche 			= (isset($_REQUEST['niche'])) ? $_REQUEST['niche'] : false;
			$category 		= (isset($_REQUEST['category'])) ? $_REQUEST['category'] : false;
			$server_id 		= (isset($_REQUEST['server_id'])) ? $_REQUEST['server_id'] : false;
			$content_type 	= (isset($_REQUEST['content_type']) && preg_match("#^(video|videos|movies|vids)$#im", $_REQUEST['content_type'])) ? $_REQUEST['content_type'] : false;

			var_dump($sites_count = $site_worker->sitesCount($niche, $category, $content_type, $server_id));
?>

	  <form enctype="multipart/form-data" action="index.php?<?=http_build_query($_GET)?>" method="post" id="site_filter">
      	Отфильтровать сайты по
      <select name="niche">
        <option value="0">Ниша</option>
        <option value="Gay"<?=($niche == 'Gay') ? ' selected' : false;?>>Gay</option>
        <option value="Straight"<?=($niche == 'Straight') ? ' selected' : false;?>>Straight</option>
        <option value="Shemale"<?=($niche == 'Shemale') ? ' selected' : false;?>>Shemale</option>
      </select>
      <select name="content_type">
        <option value="0">Тип сайта</option>
        <option value="video"<?=($content_type == 'video') ? ' selected' : false;?>>Видео</option>
        <option value="pics"<?=($content_type == 'pics') ? ' selected' : false;?>>Пиксы</option>
        <option value="mix"<?=($content_type == 'mix') ? ' selected' : false;?>>Микс</option>
      </select>        
      <select name="server_id">
        <option value="-1">Сервер</option>
        <?php
        foreach($caching_servers as $e_c) { ?>
          <option value="<?=$e_c['id']?>"<?=($e_c['id'] == $server_id) ? ' selected' : false;?>><?=$e_c['name']?></option>
<?php   }
?>
      </select>
      <input type="submit" value="Filter" name="filterSites" />
      </form> 

		<div style="float: left; width: 900px; font-size: 16px; display: block; text-align: left;">
			Всего сайтов в выборке: <?=$sites_count?>
		</div>
		<div style="float: right; width: 360px; font-size: 16px; display: block;">
			<a href="index.php?act=sites&amp;add=new">Добавить новый сайт >></a>
		</div>
		<div style="clear: both;"></div>
	<hr>
		
		<div style="float: left; width: 1400px; padding: 4px;">
			<div style="padding-top:5px;  display:block; width: 25px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">#</div>
			<div style="padding-top:5px;  display:block; width: 180px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Сайт/Правка</div>
			<div style="padding-top:5px;  display:block; width: 45px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">ID</div>			
			<div style="padding-top:5px;  display:block; width: 230px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">URL к галере</div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Перейти к</div>
			<div style="padding-top:5px;  display:block; width: 60px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Ниша</div>
			<div style="padding-top:5px;  display:block; width: 45px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Тип</div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Категория</div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">vCDN</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Сервер</div>
			<div style="padding-top:5px;  display:block; width: 75px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Инфо сайта</div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Галер</div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;"></div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;"></div>
			<div style="padding-top:5px;  display:block; width: 65px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;">Cut 7/d</div>
			<div style="padding-top:5px;  display:block; width: 90px; height:22px; overflow: hidden; background-color: #bda; margin: 1px; float:left; font-weight: bold;"></div>

		</div>
		<div style="clear: both;"></div>
<?php
		$sites 				= $site_worker->getSitesList($sites_count, $page, $niche, $category, $content_type, $server_id);

		$row_number 		= 0;
		$source_site_server = array();

		foreach ($sites as $site) {

				$site_id = false;
				$local_id_flag = false;
				$site_name = false;
				$site_niche = false;
				$hand_flag = false;
				$site_main_category = false;
				$or_tag = false;
				$sites_gallery_url = false;
				$last_update = false;
				$redis_server = false;
				$pageviews_updated_on = false;
				$likes_updated_on = false;
				$use_embed = false;
				$site_type = false;
				$site_own_titles = false;
				$site_own_main_thumbs = false;
				$language = false;
				$use_galleries_from = false;
				$digit_base_for_id = false;
				$vcdn_type = false;
				extract($site);

				

				if(!$use_galleries_from) {
					$use_cut_stats_link = true;
				} else {
					$use_gallery_link_key = $redis_server."_".$use_galleries_from;
					if(!in_array($use_gallery_link_key, $source_site_server) ) {
						$source_site_server[] = $use_gallery_link_key;
						$use_cut_stats_link = true;
					} else {
						$use_cut_stats_link = false;
					}	
				} 

				CachingServers::setServer($redis_server);
				$site_redis_server_name = CachingServers::currentName();
				

				$site_main_category 	= ($site_main_category == 0) ? "All" : $default->TagName($site_main_category);
				$site_galleries_count 	= $site_worker->galleriesCount($site_id);
				$row_color 				= !(++$row_number % 2) ? "#ab9" :  "#cdb";
?>
	<div style="float: left; width: 1400px; padding: 0 4px;">
		<div style="padding-top:5px;  display:block; width: 25px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$row_number?></div>
		<div style="padding-top:5px;  display:block; width: 180px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><a href="index.php?act=sites&amp;site=<?=$site_id?>"><?=$site_name?></a></div>
		<div style="padding-top:5px;  display:block; width: 45px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><b><?=$site_id?></b></div>
		<div style="padding-top:5px;  display:block; width: 230px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$sites_gallery_url?></div>
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><a href="index.php?act=galleries&amp;site=<?=$site_id?>">Галлереи</a></div>
		<div style="padding-top:5px;  display:block; width: 60px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$site_niche?></div>
		<div style="padding-top:5px;  display:block; width: 45px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$site_type?></div>
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$site_main_category?></div>
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$vcdn_type?></div>		
		<div style="padding-top:5px;  display:block; width: 95px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$site_redis_server_name?></div>
		<div style="padding-top:5px;  display:block; width: 75px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><a href="index.php?act=sites&amp;cache_site_info_id=<?=$site_id?>"><?=$cache_worker->siteCached($site_id) ? 'В кеше' : 'Не в кеше';?></a></div>
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><?=$site_galleries_count?></div>
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><a href="index.php?act=sites&amp;models_pageviews_to_db=1&amp;site_id=<?=$site_id?>">Resynch models P/L Redis->DB</a></div>
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><a href="index.php?act=restore_cache&amp;site_id=<?=$site_id?>" onclick="confirm('Пересобрать кеш сайта <?=$site_name?>?')">Restore Cache</a></div>		
		<div style="padding-top:5px;  display:block; width: 65px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;">
			<?php if($use_cut_stats_link) { ?>
				<a href="index.php?act=sites&amp;cut_stats_to_week=1&amp;site_id=<?=$site_id?>">Cut stats</a>
			<?php } ?>
		</div>
		<div style="padding-top:5px;  display:block; width: 90px; height:52px; overflow: hidden; background-color: <?=$row_color?>; margin: 1px; float:left; font-weight: normal;"><a href="index.php?act=sites&amp;tables_rebuild=1&amp;site=<?=$site_id?>" onclick="return confirm('Пересборка таблиц сайта занимает время и будет очень серьезно грузить сервер. Тончно пересобрать сайта <?=$site_name?>?')">Пересборка таблиц</a></div>
	</div>
	
<?php
		}
?>
	<div style="clear: both;"></div>
	<hr>
	<a href="index.php?act=sites&amp;add=new">Добавить новый сайт >></a>
	<hr>
<?php		
	}
}

}