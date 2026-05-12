<?php

if (isset($_GET['design_type']) && $_GET['design_type'] == 'multi') {
	include "gallery.multiple.php";
	exit;
} else {

	$gallery_worker = new Galleries($db->_db);

	if ($userAuth->isAdmin()) {
		echo "Видео не в CDN {$gallery_worker->countVideosNotInCDN()}";
	}

	if (isset($_GET['galid'], $_GET['force_switch_status'])) {
		if ($userAuth->isAdmin()) {

			$gallery_worker->setStatus($_GET['galid'], $_GET['force_switch_status']);
		}
	}

	$no_info = (isset($_REQUEST['no_info']) && $_REQUEST['no_info']) ? true : false;

	if (isset($_POST['resize_horiz_thumbs']) && isset($_GET['galid']) && (int)$_GET['galid']) {

		$resize_result = resizeHorizGalleryThumbs($_GET['galid']);

		if ($resize_result) {
			echo "Галера #<b>" . $_GET['galid'] . "</b> прошла ресайз успешно";
			$thumb_refresher = "?" . time();
		} else {
			echo "Ошибка ресайза тумб по горизонтали. Галера #<b>" . $_GET['galid'] . "</b>";
			$thumb_refresher = "";
		}
	}

	$site_id 			= (isset($_GET['site_id']) && (int)$_GET['site_id'] > 0) ? (int)$_GET['site_id'] : 0;
	$local_gal_id 		= (isset($_GET['local_gal_id']) && (int)$_GET['local_gal_id'] > 0) ? (int)$_GET['local_gal_id'] : 0;
	$global_gal_id 		= 0;
	$site_name 			= '';
	$local_title 		= '';
	$thumb_refresher 	= '';
	$site_exists 		= false;

	if ($site_id && $local_gal_id) {

		$site_exists = $sites->switchSite($site_id);

		if ($site_exists) {
			$global_gal_id = $gallery_worker->getGlobalId($site_id, $local_gal_id);
			$global_gal_id ? $site_name = $sites->getName() : $site_exists = false;
		}
	}


	$galleries_OKed_today = $cache_worker->getTaggedGalleryCounter($user_id);

	if (isset($_POST['deleteSiteGallery'])) {
		if ($userAuth->isAdmin()) {
			if ($site_id && $local_gal_id && $site_exists) {
				$sites_galleries = new SitesGalleries;
				$sites_galleries->setSiteId($site_id);
				$delete_result = $sites_galleries->deleteGallery($global_gal_id);
				if ($delete_result) {
					echo "<font color='green'>Галлерея GID#" . $global_gal_id . ", GLCID#" . $local_gal_id . " удалена с сайта " . $site_name . "</font><br>";
				} else {
					echo "<font color='red'>Ошибка! Галлерея GID#" . $global_gal_id . ", GLCID#" . $local_gal_id . " не удалена с сайта " . $site_name . "</font><br>";
				}
			} else {
				echo "Ошибка! Сайт, галлерея отсутсвуют в базе, либо налеры нет на сайте<br>";
			}
		} else {
			$log = new Logger("!!!!!! Попытка удалить галеру с сайта не админом: IP: " . $user_ip . ", Name:" . $user_name . ".", true);
			echo "<h2 color='red'>Ошибка! Нет права на удаление галеры<h2><br>";
		}
	} elseif (isset($_POST['deleteGallery'])) {
		if ($userAuth->isAdmin()) {
			echo "Kill gallery:\n";
			$gallery_worker->deleteGallery($_POST['gallery-id']);
			// $userAuth->userRemovedGallery($userId, $_POST['gallery-id']);
			// $cache_worker->server_deleteGalleryCache($_POST['gallery-id']);
			echo $_POST['gallery-id'] . " удален из базы<br>";
		} else {
			$gallery_worker->trashGallery($_POST['gallery-id']);
			$userAuth->userRemovedGallery($userId, $_POST['gallery-id']);
			$cache_worker->server_deleteGalleryCache($_POST['gallery-id']);
			echo $_POST['gallery-id'] . " в корзине<br>";
		}
		unset($_POST['gallery-id']);
	} elseif (isset($_REQUEST['merge-galleries']) && $userAuth->isAdmin()) {
		$mergGals = array_count_values($_REQUEST['merging']);
		$mergFail = false;
		foreach ($mergGals as $gal) {
			if ($gal > 1) $mergFail = true;
		}
		if ($mergFail === false && is_array($_REQUEST['merging']) && count($_REQUEST['merging']) > 1) {
			$mergGals = false;
			foreach ($_REQUEST['merging'] as $gal) {
				if (strstr($gal, 'No') === false) $mergGals[] = $gal;
			}
			$gallery_worker->mergeGalleries($mergGals);
		}
	} elseif (isset($_POST['edit-gallery']) || isset($_POST['edit-gallery-ok'])) {
		if (isset($_POST['edit-gallery'])) unset($_POST['edit-gallery']);
		if (isset($_POST['edit-gallery-ok'])) {
			unset($_POST['edit-gallery-ok']);
			$user_working_flag = true;
		}
		if (isset($_POST['model'])) unset($_POST['model']);
		if (isset($_POST['gallery-id'])) {
			// var_dump($_POST);
			$_galleryId = intval($_POST['gallery-id']);
			unset($_POST['gallery-id']);
			if (isset($_POST['title'])) {
				$title = $_POST['title'];
				unset($_POST['title']);
			}
			if (isset($_POST['description'])) {
				$description = $_POST['description'];
				unset($_POST['description']);
			}
			if (isset($_POST['paysite'])) {
				$paysite = $_POST['paysite'];
				unset($_POST['paysite']);
			} else {
				$paysite = 0;
			}
			if (isset($_POST['set_cropped'])) {
				$set_cropped = $_POST['set_cropped'];
				unset($_POST['set_cropped']);
			} else {
				$set_cropped = 0;
			}
			if (isset($_POST['ignore_set_cropped'])) {
				unset($_POST['ignore_set_cropped']);
				$set_cropped = 0;
			}
			$additional_gallery_titles = false;
			foreach ($_POST as $key => $value) {
				if (strpos($key, "additional_gallery_title_") !== false) {
					$title_id = substr($key, strlen("additional_gallery_title_"), strlen($key));
					$additional_title = $value;
					$additional_gallery_titles[$title_id]['id'] = $title_id;
					$additional_gallery_titles[$title_id]['title'] = $value;
				} elseif (strpos($key, "thumb") !== false) {
					$imagesList[] = $value;
				}
			}

			if (isset($imagesList)) {

				// исправление локальных галер (тайтлы)

				if ($site_id && $local_gal_id && $site_exists) {
					// исправление локальных галер (тайтлы)
					if ($userAuth->isAdmin()) {
						$sites = new Sites($db->_db);
						if ($global_gal_id) {
							$sites->switchSite($site_id);
							if ($sites->updateTitle($local_gal_id, $title)) {

								$cache_worker->updateGalleryLocalTitle($site_id, $local_gal_id, $title);
								$gallery_worker->upateGalleryNoTitle($global_gal_id, $imagesList, $paysite, $set_cropped);
								echo "Тайтл исправлен. Site ID #" . $site_id . ", Gal ID #" . $local_gal_id . " (local)<br />";
							} else {
								$log = new Logger("Попытка исправить локальную галеру провалена - тайтл не возможно поменять. Ошибка MySQL", true);
								echo "Ошибка! Попытка исправить локальную галеру провалена - тайтл не возможно поменять. Ошибка MySQL<br>";
							}
						} else {
							$log = new Logger("Попытка исправить локальную галеру провалена - сайт не найден", true);
							echo "Ошибка! Попытка исправить локальную галеру провалена - сайт не найден<br>";
						}
					} else {
						$log = new Logger("Попытка исправить локальную галеру юзером без статуса admin!", true);
						echo "Ошибка! Только администратор может исправлять галеры собраные на сайтах<br>";
					}
				} else {
					$gallery_worker->updateGallery($_galleryId, $title, $description, $imagesList, $paysite, false, $set_cropped);
					if (isset($_GET['tags']) || isset($user_working_flag)) {
						$log = new Logger("Update gal okay" . $_galleryId, true);
						$galleryStatus = 'OK';
						if ($gallery_worker->getStatus($_galleryId) != 'OK') {
							$galleries_OKed_today = $cache_worker->updateTaggedGalleryCounter($user_id);
						}
						$gallery_worker->approveGallery($_galleryId);
						$userAuth->userApprovedGallery($user_id, $_galleryId);
						$cache_worker->server_cacheGlobalGallery($_galleryId);
					} elseif ($gallery_worker->getStatus($_galleryId) == 'OK') {
						$cache_worker->server_cacheGlobalGallery($_galleryId);
					}
				}
			} else echo "Ошибка! В галлерее #<a href='index.php?act=galleries&amp;galid=" . $_galleryId . "'>" . $_galleryId . "</a> не были выбраны или отсутствуют тумбы. Апдейт галлери не возможен<br>";
		}
	}

	if (isset($_GET['manual_recrop'])) {
		$gallery_worker->galleryToRecrop($_GET['manual_recrop']);
	}

	if ($userAuth->isAdmin()) $queryStringAddition = "?act=galleries";
	else $queryStringAddition = "tags.php?act=galleries";


	$galleryId = false;
	$croppedTagFlag = false;
	$galleryNiche = false;
	$galleryPaysite = false;
	$galleryCategory = false;
	$galleryType = false;
	$skeeped = false;
	$sort_by_date = false;
	$set_main_thumbs = false;
	$exclude_paysite = false;

	if (isset($_GET['galid']) && ($user_type !== 'admin' && !$userAuth->ifGalleryUpdatedByUser($user_id, $_GET['galid']))) {
		$galleryId = false;
	} elseif (isset($_GET['galid']) && ($userAuth->isAdmin() || $userAuth->ifGalleryUpdatedByUser($user_id, $_GET['galid']))) {
		$galleryId = intval($_GET['galid']);
		// только для galid, и в тегах и без тегов

		//	tagFlag:
		//  1 -	Установлен флаг тегов и отсутствует galid 
		//  0 - Установлен флаг тегов и присутствует galid
		// -1 - Флаг тегов отсутствует

		$gallery = $default->NewGetGalleryInfo($galleryId);
		$queryStringAddition .= "&galid=" . $galleryId;
		if (isset($_GET['tags']) && preg_match("#^(true)$#", $_GET['tags'])) {
			$tagFlag = 0;
			$queryStringAddition .= "&tags=true";
			unset($_GET['tags']);
		} else $tagFlag = -1;
	} else {
		//
		//	Сборка строки по предыдущей
		//

		// if ($userAuth->isAdmin()) {
		if (isset($_GET['tags']) && preg_match("#^(true)$#", $_GET['tags'])) {
			$queryStringAddition .= "&tags=true";
			unset($_GET['tags']);
		}



		if (isset($_GET['niche']) && preg_match("#^(Straight|Gay|Shemale)$#", $_GET['niche'])) {
			$galleryNiche = $_GET['niche'];
			unset($_GET['niche']);
			$queryStringAddition .= "&niche=" . $galleryNiche;
		}


		if (isset($_GET['paysite']) && intval($_GET['paysite'])) {
			$galleryPaysite = intval($_GET['paysite']);
			unset($_GET['paysite']);
			$queryStringAddition .= "&paysite=" . $galleryPaysite;
		}
		if (isset($_GET['category']) && intval($_GET['category'])) {
			$galleryCategory = intval($_GET['category']);
			unset($_GET['category']);
			$queryStringAddition .= "&category=" . $galleryCategory;
		}
		if ((isset($_GET['crop']) && $_GET['crop'] == 'yes')) {
			$croppedTagFlag = TRUE;
			$queryStringAddition .= "&crop=yes";
		}
		if (isset($_GET['type']) && preg_match('/^(pics|movies)$/im', $_GET['type'])) {
			$galleryType = $_GET['type'];
			$queryStringAddition .= "&type=" . $galleryType;
			$galleries_type = $_GET['type'];
		} else {
			$galleryType = false;
			$galleries_type = "";
		}
		if (isset($_GET['skeeped']) && $_GET['skeeped'] == 'true') {
			$skeeped = true;
			$queryStringAddition .= "&skeeped=true";
		}
		if (isset($_GET['sort_by_date']) && preg_match("#^(asc|desc|true)$#", $_GET['sort_by_date'])) {
			$sort_by_date = $_GET['sort_by_date'];
			$queryStringAddition .= "&sort_by_date=" . $_GET['sort_by_date'];
		}
		if (isset($_GET['set_main_thumbs']) && $_GET['set_main_thumbs'] == 'true') {
			$set_main_thumbs = true;
			$queryStringAddition .= "&set_main_thumbs=true";
		}
		// }

		//else $croppedTagFlag = true;
		// echo $queryStringAddition;
		//
		//	Обработка гетов для теггинга, переделать в более удобоваримый формат. ??? попробовать разбор гета extract()'ом, для лучшей наглядности темплейта
		//
		if ($userAuth->isAdmin()) {
			$no_merge_gals_flag = false;
			$is_worker = false;
			$sources = new Sources($db->_db);
		} else {
			$no_merge_gals_flag = false;
			$is_worker = true;
		}

		$exclude_paysite = $userAuth->getExcludedPaysites($user_id);


		// для теггера, выборка специальная

		if ($user_type == 'tags') {
			$galleryNiche 	= 'Gay';
			$galleryType 	= 'Pics';
		}

		// для теггер

		$galleryToTagInfo = $default->GetGalleryToTag(
			$galleryId,
			$galleryNiche,
			$galleryPaysite,
			$galleryCategory,
			$croppedTagFlag,
			$galleryType,
			$skeeped,
			$sort_by_date,
			false /* set_main_thumbs*/,
			$no_merge_gals_flag,
			$is_worker,
			$exclude_paysite
		);
		$galleryId = $galleryToTagInfo ? $galleryToTagInfo['id'] : false;
		$galToTagCount  = $galleryToTagInfo ? $galleryToTagInfo['count'] : 0;
		$gallery = $galleryId ? $default->NewGetGalleryInfo($galleryId) : false;
		$tagFlag = 1;
	}
	if ($site_id && $local_gal_id) {
		$queryStringAddition .= "&amp;site_id=" . $site_id . "&amp;local_gal_id=" . $local_gal_id;
		$local_title = $gallery_worker->getLocalTitle($site_id, $local_gal_id);
	}
	// только для админа
	// var_dump($gallery['additional_titles']);	
	if ($tagFlag == 1) {

?>
		<form name=selector style="height: 30px; display: block;" id="block-block-block">
			<div style="width:100%; height: auto; display: block;">
				<div style="float: left;">
					&nbsp;
					<select name="type" id="type" onChange="searchTagsOptions(this.id,this.value,'<?= $galleries_type ?>')">
						<option value="all">Контент</option>
						<option value="pics" <?php if ($galleries_type == "pics") echo " selected"; ?>>Pics</option>
						<option value="movies" <?php if ($galleries_type == "movies") echo " selected"; ?>>Movies</option>
					</select>
					&nbsp;
					<select name="niche" id="niche" onChange="searchTagsOptions(this.id,this.value,'<?= $galleries_type ?>')">
						<option value="all">Ниша</option>
						<option value="Gay" <?php if ($galleryNiche == "Gay") echo " selected"; ?>>Gay</option>
						<option value="Shemale" <?php if ($galleryNiche == "Shemale") echo " selected"; ?>>Shemale</option>
						<option value="Straight" <?php if ($galleryNiche == "Straight") echo " selected"; ?>>Straight</option>
					</select>
					&nbsp;
					<select name="category" id="category"
						onChange="searchTagsOptions(this.id,this.value,'<?= $galleries_type ?>')">
						<option value="all">Категория</option>
						<?php
						$default->AllTagsToString("<option value=\"#TAG_ID#\">#TAG#</option>");
						?>
					</select>
					&nbsp;
					<select name="paysite" id="paysite" onChange="searchTagsOptions(this.id,this.value,'<?= $galleries_type ?>')">
						<option value="all">Платник</option>
						<?php $sources->listSourcesGalsLight("<option value=\"#PAYSITE_ID#\"#CHECKED#>#PAYSITE#, последний апдейт: #LAST_UPDATE#</option>", $galleryPaysite);  ?>
					</select>
					<select name="crop" id="crop" onChange="searchTagsOptions(this.id,this.value,'<?= $galleries_type ?>')">
						<option value="all">Все</option>
						<option value="yes" <?php if ($croppedTagFlag) echo " selected"; ?>>Только кропнутые</option>
					</select>
					<select name="sort_by_date" id="sort_by_date"
						onChange="searchTagsOptions(this.id,this.value,'<?= $galleries_type ?>')">
						<option value="all">В разнобой</option>
						<option value="asc" <?php if ($sort_by_date == "asc") echo " selected"; ?>>По дате A-Z</option>
						<option value="desc" <?php if ($sort_by_date == "desc") echo " selected"; ?>>По дате Z-A</option>
					</select>
				</div>
				<div id="searchResult" class="searchResult" style="float:right;"></div>
			</div>
		</form>
		<div style="clear: both;"></div>
		<?php if (isset($galToTagCount)) { ?><div style="font-size: 15px; padding: 15px;">Осталось проставить теги на
				<b><?= $galToTagCount ?></b> галерах
			</div><?php } ?>
		<div style="clear:both"></div>
	<?php

	}

	$ignore_set_cropped = false;
	$set_cropped = false;

	if ($gallery !== FALSE && isset($gallery['id'])) {

		$gallery_user 		= $userAuth->currentGalleryUser($galleryId, 'tags');
		$categoryTags 		= $default->GetAllCategoryTags($gallery['niche']);
		$actionTags 		= $default->GetAllActionTags($gallery['niche']);
		$set_cropped 		= $gallery['paysite']['set_cropped'] ? 1 : 0;
		$gallery_source_url =  (isset($gallery['is_long_url']) && $gallery['is_long_url']) ? $gallery_worker->getLongUrlByGalId($gallery['id']) : $gallery['source'];

		$userAuth->updateWorkingTable($user_id, $galleryId, 'tags');
		$models = new CModels($db->_db);
	?>
		<br />

		<form name=stats enctype="multipart/form-data" action="<?= $queryStringAddition ?>" method="post">
			<div style="display: none;"><input name="gallery-id" size="45" value="<?= $galleryId ?>"><input name="set_cropped"
					size="45" value="<?= $set_cropped ?>"></div>

			<script type="text/javascript" language="Javascript">
				var global_gal_id = <?= $gallery['id'] ?>;

				function check_gallery_ok() {
					var result = false;
					var gal_type = "<?= $gallery['type'] ?>";
					var counter = document.getElementById('counterChoosen');
					var title_node = document.getElementById('gallery_title');
					var title = title_node.value;
					if (title.length >= 100) {
						var new_title = prompt("Тайтл длиннее 100 символов!", title);
						if (new_title == null) {
							return false;
						}
						if (title.localeCompare(new_title) != 0) {
							title_nod.value = new_title;
						}
					}
					if (counter && (gal_type == 'Pics' || gal_type == 'Movies' || gal_type == 'gif')) {
						counter = parseInt(counter.innerHTML);
						//if (gal_type == 'Pics' && counter > 20) {
						//	alert ("Изображение не долно быть больше 20");
						//} else 
						if (counter < 3 && counter > 0) {
							return confirm("Изображений меньше 3х, зааппрувить галеру?");
						} else if (gal_type == 'Movies' && counter > 34) {
							return confirm("Изображений к видео больше 34, зааппрувить галеру?");
						} else if (counter == 0) {
							alert("Ошибка! Выбрано 0 изображений");
						} else {
							result = true;
						}
					} else {
						alert('Ошибка передачи данных в скрипт!');
					}
					return result;
				}

				function approve_model(model_id, name) {
					var gal_id = <?= $galleryId ?>;
					var element_name = "pre_added_approve_button" + model_id;
					var image_element = document.getElementById("pre_added_approve_button" + model_id);
					image_element.style.display = "none";
					result = modelAddGallery1(gal_id, model_id, name);
					if (result == true) remove_pre_model(model_id);
					else image_element.style.display = "block-inline";
				}

				function find_models(name, niche) {
					var $jq = jQuery.noConflict();
					$jq.post("util/model_find.php", {
						name: name,
						niche: niche
					}, function(data) {
						if ('success' in data) {
							delete data.success;
							for (var model in data) {
								console.log(data[model].id + ":" + data[model].name + ":" + data[model].thumb);
								if (check_model_on_gallery(data[model].id) == false) {
									model_pre_add(<?= $galleryId ?>, data[model].id, data[model].name, data[model].thumb);
								}
							}
						} else if ('error' in data) {

						} else alert("Ошибка передачи/приема данных");
					});
				}

				jQuery(function($) {
					$(document).ready(function() {
						$.Shortcuts.add({
							type: 'down',
							mask: 'Enter',
							enableInInput: true,
							handler: function() {
								document.getElementById("edit-gallery-ok").click();
								return false;
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+F1',
							enableInInput: true,
							handler: function() {
								window.location.hash = '#top';
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+F3',
							enableInInput: true,
							handler: function() {
								window.location.hash = '#tags';
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+F2',
							enableInInput: true,
							handler: function() {
								window.location.hash = '#images';
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+3',
							enableInInput: true,
							handler: function() {
								location.reload();
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+1',
							enableInInput: true,
							handler: function() {
								window.location.hash = '#bottom';
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+G',
							enableInInput: true,
							handler: function() {
								manual_title_to_tags_n_models(<?= $galleryId ?>);
							}
						});
						$.Shortcuts.add({
							type: 'down',
							mask: 'Ctrl+E',
							enableInInput: true,
							handler: function() {
								var model_name = GetSelectedText();
								model_name = model_name.toString();
								if (model_name.length > 1) find_models(model_name,
									"<?= $gallery['niche'] ?>");
								else alert("Selection error!");
							}
						});
						<?php if ($userAuth->isAdmin() || (isset($user_add_models) && $user_add_models === true)) { ?>
							$.Shortcuts.add({
								type: 'down',
								mask: 'Ctrl+F',
								enableInInput: true,
								handler: function() {
									var model_name = GetSelectedText();
									model_name = model_name.toString();
									if (model_name.length > 1) {
										model_name_url = encodeURIComponent(model_name);
										if (confirm("Добавить новую модель: " + model_name + "?")) {
											window.open('index.php?act=models&query=add&name=' +
												model_name_url, '_blank')();
										}
									} else alert("Selection error!");
								}
							});
						<?php } ?>
						$.Shortcuts.start();
					});
				});
			</script>
			<div id="top"></div>
			<?php if ($userAuth->isAdmin()) {
				if (isset($_GET['full_info'])) {
					var_dump($gallery);
				}
				if ($gallery['status'] == 'OK') {
					if (is_array($gallery['posted_to'])) { ?>
						Posted to:
						<div style="width: 1320px; border: 1px solid #777;">
							<?php
							$sites_worker = new Sites($db->_db);
							foreach ($gallery['posted_to'] as $posted_to) {
								$sites_worker->switchSite($posted_to);
								$urlRules = $sites_worker->getGalleryUrl();
								$gal_local_info = $gallery_worker->getLocalGalleryInfoByGID($posted_to, $galleryId);

								$urlRules = str_replace("#TYPE#", strtolower($gallery['type']), $urlRules);
								$urlRules = str_replace("#LOCALID#", $gal_local_info['local_id'], $urlRules);
								$urlRules = str_replace("#ID#", $galleryId, $urlRules);
								$urlRules = str_replace("#GALNAME#", $gal_local_info['url_desc'], $urlRules);
							?>
								<div style="float: left; margin: 3px; padding: 5px; width: 1300px; background-color: #eee;">
									<div style="float: left;">
										<?php if ($posted_to == $site_id) { ?><div style="float: left; margin-right: 10px; color: red;">&bullet;
											</div><?php } ?>
										Site ID: <?= $posted_to ?>, URL: <a href=<?= $urlRules ?>><?= $urlRules ?></a>
									</div>
									<div style="float: right;">
										<a
											href="index.php?act=galleries&amp;galid=<?= $galleryId ?>&amp;site_id=<?= $posted_to ?>&amp;local_gal_id=<?= $gal_local_info['local_id'] ?>">Локальный
											тайтл</a>
									</div>
									<div style="clear: both;"></div>
								</div>
								<br />
							<?php
							}
							?>
							<div style="clear: both;"></div>
						</div>
				<?php
					}
				}
				?>
				<hr>
				<div style="clear: both;"></div>
				<div style="float: left;">
					Отключить мусор из инфы: <input type="checkbox" <?= $no_info ? 'checked="true"' : false ?> name="no_info"
						id="no_info" />
				</div>
				<?php if ($gallery['type'] == 'Pics') { ?>
					<div style="float: right;">
						<?= $gallery['horiz_size'] ? "Тумбы в ресайзе OK" : "Нет тумб в ресайзе"; ?>
						| <input type="submit" value="Сделать ресайз тумб по горизонтали" name="resize_horiz_thumbs"
							id="<?= $galleryId ?>" onclick="return confirm();" />
					</div>

				<?php 					} ?>
				<div style="clear: both;"></div>
				<hr>
				<?php $gallery_user_info = $userAuth->getUsers($user_id);
				if ($gallery_user && $gallery_user_info && is_array($gallery_user_info) && isset($gallery_user_info[$gallery_user])) {
				?>
					С галерой работает: <a
						href="index.php?act=users&amp;id=<?= $gallery_user_info[$gallery_user]['id'] ?>"><?= $gallery_user_info[$gallery_user]['name'] ?></a>
					|
				<?php
				} ?>
				Платник:
				<?php if ($userAuth->isAdmin()) { ?>
					<select name="paysite" id="paysite" onChange="GalleryDefault(this.id);">
						<?php $sources->listSourcesGalsLight("<option value=\"#PAYSITE_ID#\" #CHECKED#>#PAYSITE#</option>", $gallery['paysite']['id']); ?>
					</select>
				<?php } else {
				?>
					<?= $gallery['paysite']['name'] ?>
				<?php
				} ?>
				| Партнерка: <strong><?= $gallery['paysite']['affiliateProgram'] ?></strong>
				| Ниша: <strong><?= $gallery['niche'] ?></strong> |
				<?php
				if (isset($gallery['hosted'])) { ?> Хостится у себя: <?= $gallery['hosted'] ? 'Да' : 'Нет' ?><?php } /*if isset end*/
																													if (!empty($gallery['unique_for_export_site'])) {
																														echo "| Уникальная для сайта " . $gallery['unique_for_export_site'];
																													}
																												} /*if admin end*/ ?>
					<br /><br />
					<div style="width: 1200px; height: 15px; display: block; float: none; text-overflow: ellipsis; overflow: hidden;">
						Исходный URL: <strong><a href="<?= $gallery_source_url ?>"><?= $gallery_source_url ?></a></strong></div>
					<hr>

					Сегодня обработано: <b><?= $userAuth->todayTaggedGals($user_id) ?></b>, из них новых <b><?= $galleries_OKed_today ?></b>
					|
					GID: <strong><a href="index.php?act=galleries&amp;galid=<?= $gallery['id'] ?>"><?= $gallery['id'] ?></a></strong> |
					<?php
					$galleryTextStorage = defined('GALLERY_TEXT_STORAGE') ? rtrim(GALLERY_TEXT_STORAGE, '/') : (defined('WRKDIR') ? rtrim(WRKDIR, '/') . "/storage/gallery_texts" : dirname(__DIR__) . "/storage/gallery_texts");
					if (is_file($galleryTextStorage . "/" . (int)$gallery['id'] . ".txt")) {
					?>
						<a target="_blank" href="util/gallery.zip_text_view.php?gal_id=<?= $gallery['id'] ?>">Текст из ZIP</a> |
					<?php } ?>
					Статус: <b><?= $gallery['status'] ?></b> |
					<?php
					if (isset($_GET['resize_thumbs']) && isset($_GET['galid']) && (int)$_GET['galid']) {
						$gallery_worker->processThumbs($gallery['id'], $gallery['type']);
					}
					if ($gallery['type'] == 'Movies') {
						$file_info = $gallery_worker->getVideoFileInfo($gallery['id']);
						$previewInfo = $gallery_worker->getVideoPreviewInfo($gallery['id']);
						$previewUrl = $gallery_worker->getVideoPreviewPublicUrl($gallery['id'], true);
						if ($file_info && is_array($file_info)) {
					?>
							Размер видео: <b><?= round($file_info['size'], 2) ?></b>Mb |
							Битрейт: <b><?= (int)$file_info['bitrate'] ?></b>kbs |
							Макс. битрейт <?= $gallery_worker->getMaxBitrate($gallery['id']) ?>
							<?php

							// var_dump($file_info['cdn_synced']);

							if ($file_info['cdn_synced']) echo "| CDN synced";
							else {

								if ($sync_status = $gallery_worker->getCdnVideoStatusFromQuery($galleryId)) {
									echo "<b>Quieried for CND sync. Status '" . $sync_status['file_status'] . "', Added: " . date("Y-m-d", $sync_status['sync_added_on']) . ".</b>";
								} else {
							?>
									<div id='cdn-sync-block' onclick="video_to_cdn_query(<?= $galleryId ?>);"
										style="margin: 2px auto;  padding: 4px;width: 100px; height: 14px; display: block; background-color: red; position: relative; color: white; top: 0px;">
										Sync to vCDN
									</div>
								<?php
								}
								?>

								&nbsp;<br>
							<?php

							}
							// $gallery_worker->processSyncCdnQuery($gallery['id']);
							// var_dump($gallery_worker->generateCdnSyncUrl($gallery['id']));
							$gallery_worker->processSyncCdnQuery();
							// var_dump($gallery_worker->getVideosToCdnSyncCount());

							$previewStatus = ($previewInfo && isset($previewInfo['preview_status'])) ? $previewInfo['preview_status'] : 'new';
							$previewGenerated = ($previewInfo && !empty($previewInfo['generated_on'])) ? date("Y-m-d H:i", (int)$previewInfo['generated_on']) : '';
							$previewSizeMb = ($previewInfo && !empty($previewInfo['preview_size'])) ? round($previewInfo['preview_size'] / 1048576, 2) : 0;
							$previewDurationSec = ($previewInfo && !empty($previewInfo['preview_duration_ms'])) ? round($previewInfo['preview_duration_ms'] / 1000, 1) : 0;
							?>
							<div style="margin: 8px 0 0 0; padding: 8px; background: #efefef; border: 1px solid #ccc; display: inline-block;">
								<span><strong>Видео preview:</strong> <?= $previewStatus ?></span>
								<?php if ($previewUrl) { ?>
									| <span><?= $previewSizeMb ?> Mb</span>
									| <span><?= $previewDurationSec ?> сек</span>
								<?php } ?>
								<?php if ($previewGenerated) { ?>
									| <span>Обновлено: <?= $previewGenerated ?></span>
								<?php } ?>
								<?php if ($previewInfo && !empty($previewInfo['error_message'])) { ?>
									| <span style="color:#a00;"><?= htmlspecialchars($previewInfo['error_message']) ?></span>
								<?php } ?>
								<div style="margin-top: 6px;">
									<input type="button"
										value="<?php if ($previewUrl) { ?>Перегенерировать preview<?php } else { ?>Сгенерировать preview<?php } ?>"
										onclick="return generate_video_preview(<?= $galleryId ?>);">
									<?php if ($previewUrl) { ?>
										<input type="button" value="Открыть preview"
											onclick="return open_video_preview_modal('<?= htmlspecialchars($previewUrl, ENT_QUOTES) ?>', <?= $galleryId ?>);">
									<?php } ?>
								</div>
							</div>
						<?php

						}
					}
					if ($userAuth->isAdmin() && $gallery['status'] == 'OK') {
						if ($cache_worker->gallery_cached($gallery['id'])) {
							//$cache_worker->cacheGalleryCheck($gallery['id']);
						?> В кэше | <?php } else { ?> Не в кэше | <?php }
									}

									if (isset($gallery['cropped'])) {
										if ($gallery['cropped'] == 1) {
											?>
							<a target="_blank" href="/xacropper/index.php?act=cropper&amp;galid=<?= $galleryId ?>">Откроплена</a> |
							<input type="button" value="Галеру в рекроп" id="galleryToRecrop<?= $galleryId ?>"
								onclick="gallery_to_recrop(<?= $galleryId ?>);">
						<?php
										} else {
						?> <a target="_blank" href="/xacropper/index.php?act=cropper&amp;galid=<?= $galleryId ?>">Не откроплена</a>
							<?php if ($set_cropped) { ?>,
							Игнорировать флаг всегда скроплено <input name="ignore_set_cropped" type='checkbox'>
						<?php } ?>
					<?php					}
										if ($userAuth->isAdmin() && $gallery['status'] == 'uploaded' && isset($_GET['galid'])) {
					?>
						<div style="float: right; margin-left:15px;">
							<input type="submit" value="Изменить и ОК галеру" name="edit-gallery-ok" id="<?= $galleryId ?>"
								onclick="return check_gallery_ok();" />
						</div>
					<?php
										}
					?>
					<div style="float: right;">
						<input type="submit" value="Изменить галеру" name="edit-gallery" id="<?= $galleryId ?>"
							onclick="return check_gallery_ok();" />
					</div>
					<div style="clear:both"></div>
					<hr>
					<?php
										if (isset($galleryToTagInfo['skeep_reason'])) { ?>
						<div style="width: 100%; height: 30px; display: block-inline; float: left; margin: 3px;">
							Галера пропущена по причине: <?= $galleryToTagInfo['skeep_reason'] ?>, Юзером #<?= $galleryToTagInfo['user_id'] ?>,
							Пропущено из <?= $galleryToTagInfo['skeep_type'] ?>
						</div>
					<?php
										}
					?>
					<div style="width: 100%; height: 30px; display: block-inline; float: left; margin: 3px;">
						<div style="float: left;">
							<div style="width:100px; padding: 3px; margin: 3px; float: left; text-align: left;">Тайтл:</div>
							<input name="title" id="gallery_title" size="122"
								value="<?php if ($local_title) echo htmlspecialchars($local_title);
										else echo htmlspecialchars($gallery['title']) ?>"
								onkeyup="chageLength(this.value,this.name)">
						</div>
						<div style="margin-left:10px; padding: 4px; float: left; background: #e4e4e4;">
							<div id="titleL"><?php echo strlen($gallery['title']) ?></div>
						</div>
					</div>

					<div style="width: 100%; height: 30px; display: block-inline; float: left; margin: 3px;">
						<div style=" float: left;">
							<div style="width:100px; padding: 3px; margin: 3px; float: left; text-align: left;">
								Деск:
							</div>
							<input name="description" size="125" value="<?php echo htmlspecialchars($gallery['description']) ?>"
								onkeyup="chageLength(this.value,this.name)">
						</div>
						<div style="margin-left:10px; padding: 4px; float: left; background: #e4e4e4;">
							<div id="descriptionL"><?php echo strlen($gallery['description']) ?></div>
						</div>
					</div>
					<?php
										if (!$site_id && !$local_gal_id && !$no_info) {
					?>
						<div
							style="width: 1280px; height: 80px; display: block-inline; float: left; margin: 16px; border: 1px solid #000; padding: 10px; background-color: #ddd;">
							<select id="all_titles_select" style="float: left;">
								<option value="0">Добавить тайтл</option>
								<?php if (isset($gallery['additional_titles']) && $gallery['additional_titles'] && is_array($gallery['additional_titles'])) { ?>
									<?php foreach ($gallery['additional_titles'] as $additional_title) { ?>
										<option value="<?= $additional_title['id'] ?>">
											<?= strtoupper($additional_title['language']) . ":" . htmlspecialchars($additional_title['title']) ?></option>
									<?php } ?>
								<?php } ?>
							</select>
							<div style="clear:both"></div>
							<hr />
							<div style="float: left;">
								<select id="add_title_language">
									<option value="en">Английский</option>
									<option value="ru">Русский</option>
									<option value="nl">Голландский</option>
									<option value="fr">Французский</option>
								</select>
								<input name="add_title" id="add_title" size="100" value="<?php echo htmlspecialchars($gallery['title']) ?>"
									onkeyup="chageLength(this.value,this.name)">
							</div>
							<div style="margin-left:10px; padding: 4px; float: left; background: #e4e4e4;">
								<div id="add_titleL"><?php echo strlen($gallery['title']) ?></div>
							</div>
							<div style="float: right; padding: 5px; border: 2px #666 solid; background-color: #555; color: #fff; font-weight: bold;"
								onclick="add_new_title(<?= $gallery['id'] ?>); return false;">
								Добавить...
							</div>
						</div>
						<div style="clear:both"></div>
						<?php
										}
										// боковое меню модели
										if (preg_match('#(ok|uploaded)#im', $gallery['status']) || isset($_GET['full_view'])) {
											if (isset($_GET['sex']) && $_GET['sex'] == 'ignore') $modelSex = false;
											elseif ($gallery['niche'] == 'Gay' || (isset($_GET['sex']) && $_GET['sex'] == 'male')) $modelSex = 'male';
											elseif ($gallery['niche'] == 'Straight' || (isset($_GET['sex']) && $_GET['sex'] == 'female')) $modelSex = 'female';
											elseif ($gallery['niche'] == 'Shemale' || (isset($_GET['sex']) && $_GET['sex'] == 'shemale')) $modelSex = 'shemale';
											else $modelSex = false;

											//$models_list = $models->getModelsList($modelSex, true);
											// закомментировал выпадающий блок с поиском модели - нет смысла в нем пока. Удалить?
											$models_list = false;
											if ($models_list && is_array($models_list) && count($models_list)) {
						?>
							<div id="modelBlock" class="model" onmouseover="showfullMenu(this.id)">
								<div>
									<select id="modelSelect" name="model" onchange="switchModelMenu(this.value)">
										<option value="0">None</option>
										<?php foreach ($models_list as $model) { ?>
											<option value="<?= $model['id_model'] ?>"><?= $model['name'] ?></option>
										<?php } ?>
									</select>
								</div>
								<div>
									<img id="modelImage" src='images/nomodel.png'
										style="width: 240px; height: 320px; position:relative; top: 10px; background:#FFFFFF; border: solid 1px #000; padding: 4px; display: block-inline;" />
								</div>
								<div>
									<dd name="ddAddModel" id="0"
										style="position: relative; display: block-inline; top: 9px; right: 20px; font-size: 12px; padding: 4px; border: 1px #666 solid;"
										onclick="modelAddGallery1(<?= $gallery['id'] ?>)">модель есть на галлерее</dd>
								</div>
								<div style="float: right; width:25px; position: relative; top: 40px; right: 8px; font-size: 12px; display: block-inline;"
									onclick="hidefullMenu('modelBlock')">hide</div>
								<?php if ($userAuth->isAdmin() || (isset($user_add_models) && $user_add_models === true)) { ?>
									<div
										style="float: left; position: relative; width: 65px; height: 14px; top: 40px; left: 12px; font-size: 12px; display: block-inline;">
										<a href="<?= $_SERVER['SCRIPT_NAME'] ?>?act=models&query=add" target="_blank">Добавить новую</a>
									</div>
								<?php } ?>
							</div>
						<?php
											}
										}
										if ($gallery['type'] == 'Movies' && $gallery['main_gal'] != 0) {
											$possibleMerge = $gallery_worker->getGalleriesList('asc', 'id', false, false, 'Movies', false, false, false, false, false, false, false, $gallery['main_gal']);
											if ($possibleMerge && is_array($possibleMerge) && count($possibleMerge) > 1) {
						?>
							Можно объединить с:
							<?php
												foreach ($possibleMerge as $mergeId => $merge) {
													$to_variants[] = $mergeId;
													if ($mergeId !== $gallery['id']) {
														if ($merge['type'] == 'Movies') $thumbUrlPre = HOSTING . "/thumbs/m/320";
														else $thumbUrlPre = HOSTING . "/thumbs/p/180";
														$thumbId = $merge['image'];
														$folder = folderNameById($thumbId);
														$thumbURL = $thumbUrlPre . "/" . $folder . "/" . $thumbId . ".jpg";
														$origImageURL = HOSTING . "/" . $merge['orig_image'];

														$imageSize = @getimagesize($origImageURL);
							?>
									<div
										style="margin:3px 3px 3px 30px;; padding: 5px; width: 91%; height: 26px; border: 1px #000 solid; display: block; text-align: left;">
										<div style='float:left; height: 24px; display: block-inline; overflow: hidden; '>
											<input type="button" id="showGalleryButton<?= $mergeId ?>" value="Показать все тумбы"
												onclick="showGalleryThumbs(<?= $mergeId ?>,'<?= $merge['type'] ?>');">
											<strong>ID:</strong> <a onmouseover="over('<?= $thumbURL ?>')" onmousemove="move(event)" onmouseout="out()"
												href="./index.php?act=galleries&galid=<?= $mergeId ?>"><?= $mergeId ?></a> |
											<strong>Платник:</strong> <?= $merge['paysite'] ?>
											<strong>Тайтл:</strong> <?= $merge['title'] ?>
											<?php if ($imageSize && is_array($imageSize)) { // размер видео кадра
											?>
												<strong>Размер кадра:</strong> <?= $imageSize[0] ?>x<?= $imageSize[1] ?>
											<?php 							} ?>
											<strong>Длительность видео:</strong> <?= $merge['count'] ?> сек.

										</div>
										<div style='float:right;'>
											<strong>Статус:</strong> <?= $merge['status'] ?>
										</div>
									</div>
									<div style="margin:6px; margin-top: 1px; margin-bottom: 25px; padding: 5px; width: 1300px; height: auto; border: 1px #000 solid; display: none; text-align: left;"
										name="addModelBlock" id="addModelBlock<?= $mergeId ?>">

									</div>
							<?php
													}
												}
							?>
							Объеденить галлереи в порядке:
							<?php
												$j = 0;
												foreach ($to_variants as $variant) {
													$j++;
							?>
								<select name="merging[]" id="<?= $variant ?>">
									<option value="No<?= $j ?>">Нет</option>
									<?php
													foreach ($to_variants as $variant_id) {
									?>
										<option value="<?= $variant_id ?>" <?php if ($variant_id == $variant) echo "selected" ?>><?= $variant_id ?></option>
									<?php
													}
									?>
								</select>
							<?php
												}
							?>
							<input type="submit" value="Объединить галеры" name="merge-galleries" id="<?= $gallery['id'] ?>"
								onclick="return confirm('Точно объеденить галеры?')" />
						<?php
											}
						?>
						<br>
					<?php
										}
					?>
					<div id="models1s"
						style="width: 90%; height: 24px; display: block-inline; margin: 3px; margin-left: 30px; padding:10px; border: 1px #000 solid; display: block-inline;">
						<?php
										if ($galleryModels = $models->getGalleryModels($gallery['id'])) {
											foreach ($galleryModels as $modelId) {
												if ($models->switchModel($modelId)) {
													$thumbURL =	HOSTING . "/thumbs/p/180/" . folderNameById($models->getPicture()) . "/" . $models->getPicture() . ".jpg";
						?>
									<dd id='modelDD<?= $modelId ?>' onmouseover="over('<?= $thumbURL ?>')" onmousemove="move(event)" onmouseout="out()"
										style="font-size: 15px; float: left;"><?= $models->getName() ?><img
											style="padding-left: 4px; padding-top: 5px;" src="images/del.png" id="<?= $modelId ?>"
											onclick="deleteModelFromGal(this.id,<?= $gallery['id'] ?>)"></dd>
						<?php
												}
											}
										}
						?>
					</div>
					<div style="clear:both"></div>
					<div style="width: 100%; text-align: right; margin:5px; display: none;" id="show_hide_models_menu"><a href="#"
							onclick="show_hide_models_menu(); return false;">Show/Hide models</a></div>
					<div id="models_pre_add"
						style="width: 90%; height: auto; margin: 3px; margin-left: 30px; padding:10px; display: none;">
					</div>
					<style type="text/css">
						<!--
						.DragContainer,
						.OverDragContainer {
							float: left;
							margin: 3px;
							width: 1306px;
							border: #669999 2px solid;
							padding: 5px;
						}

						.DragBox,
						.OverDragBox,
						.DragDragBox,
						.miniDragBox {
							border: #000 1px solid;
							padding: 2px;
							font-size: 14px;
							margin: 5px;
							width: 94px;
							cursor: pointer;
							font-family: verdana, tahoma, arial;
							background-color: #eee;
							float: left;

						}

						.OverDragContainer {
							background-color: #eee;
						}

						.OverDragBox,
						.DragDragBox {
							background-color: #ffff99;
						}

						.DragDragBox {
							filter: alpha(opacity=50);
							background-color: #ff99cc;
						}

						legend {
							font-weight: bold;
							font-size: 12px;
							color: #666699;
							font-family: verdana, tahoma, arial;
						}

						fieldset {
							padding: 3px;
						}

						.History {
							font-size: 10px;
							overflow: auto;
							width: 100%;
							font-family: verdana, tahoma, arial;
							height: 82px;
						}

						#DragContainer8 {
							border: #669999 1px solid;
							padding: 5px 0 0 5px;
							width: 110px;
							height: 40px;
						}

						.miniDragBox {
							float: left;
							margin: 0 5px 5px 0;
							width: 20px;
							height: 20px;
						}
						-->
					</style>
					<div class="DragContainer" id="DragContainer1" droppable>

						<?php
										$tagged_thumbs = $default->selectTaggedThumbs($galleryId);
										if (is_array($gallery['tags'])) {
											foreach ($gallery['tags']['id'] as $key => $tag_id) {
												if (!$tagged_thumbs || !array_key_exists($tag_id, $tagged_thumbs)) {
						?>

									<div class="DragBox" id="drag_tag_<?= $tag_id ?>" overClass="OverDragBox" dragClass="DragDragBox" draggable>
										<?= $gallery['tags']['name'][$key] ?></div>
						<?php
												} else {
													$thumb_tags_exist[$tagged_thumbs[$tag_id]]['id'] = $tag_id;
													$thumb_tags_exist[$tagged_thumbs[$tag_id]]['name'] = $gallery['tags']['name'][$key];
												}
											}
										}
										// var_dump($thumb_tags_exist);
						?>
					</div>

					<div style="clear:both"></div>
					<div style="float: right; text-align: right; margin: 5px; display: block; width: 100%; padding: 5px;"><a
							href="javascript:select(true);">Select All</a> | <a href="javascript:select(false);">Deselect All</a></div>
					<div style="clear:both"></div>
					<div id="images"></div>
					<?php
										if (isset($gallery['images'])) {
											if ($gallery['type'] == 'Pics') {
												$picsCount = $gallery['contentCount'];
												$thumbClass = 'thumb';
												$thumbUrlPre = HOSTING . "/thumbs/p/150";
											} elseif ($gallery['type'] == 'gif') {
												$picsCount = $gallery['contentCount'];
												$thumbClass = 'thumb-gif';
												$thumbUrlPre = HOSTING;
											} else {
												$picsCount = (isset($gallery['images']['thumbs']) && is_array($gallery['images']['thumbs'])) ? count($gallery['images']['thumbs']) : 0;
												$thumbClass = 'thumb-movies';
												$thumbUrlPre = HOSTING . "/thumbs/m/240";
											}

											$uploadedRss = $default->selectRssThumbs($galleryId);

											if (isset($gallery['images']['thumbs']) && is_array($gallery['images']['thumbs'])) {
												foreach ($gallery['images']['thumbs'] as $thumbId => $thumbURL) {
													if (array_key_exists($thumbId, $uploadedRss)) {
														$textDecoration = 'none';
													} else $textDecoration = 'line-through';
													$folder = folderNameById($thumbId);
													$thumbURL = $thumbUrlPre . "/" . $folder . "/" . $thumbId . ".jpg";
													$origImageURL = HOSTING . "/" . $gallery['images']['url'][$thumbId];
													if (isset($thumb_tags_exist) && isset($thumb_tags_exist[$thumbId]['id'])) {
														$droppable = "";
														// var_dump($thumb_tags_exist[$thumbId]['id']);
														$drag_box_element = "<div class=\"DragBox\" id=\"thumb_assigned_tag_" . $thumb_tags_exist[$thumbId]['id'] . "\"  overClass=\"OverDragBox\" dragClass=\"DragDragBox\" ondblclick=\"removeThumbTag(" . $thumbId . "," . $thumb_tags_exist[$thumbId]['id'] . ")\">" . $thumb_tags_exist[$thumbId]['name'] . "</div>";
													} else {
														$droppable = "droppable";
														$drag_box_element = "";
													}

					?>
								<div class="<?= $thumbClass ?>" id="<?= $thumbId ?>" <?= $droppable ?>>
									<div style="width: 100%; height:20px;">
										<div name="main_thumb" id="main<?= $thumbId ?>"
											style="float: left; width: 35px; text-align:left; padding-left:5px; color: <?php if ($gallery['gal_thumb'] == $thumbId) echo "green";
																														else echo "#DDD"; ?>; font:14px arial;"
											onClick="setMainThumb(<?= $gallery['id'] ?>,<?= $thumbId ?>)">
											MAIN
										</div>
										<div style="font: 14px arial; float: right; width: 65px; text-align:right; padding-right:5px;">
											#<?= $thumbId ?></div><br />
									</div>
									<div id="<?= $thumbId ?>" onClick="SelectImage(this.id);">
										<img
											src="<?php if ($gallery['type'] == 'gif') {
														echo $origImageURL;
													} else {
														echo $thumbURL;
													}
													echo $thumb_refresher; ?>"><br />
										<div align="right">
											Upload: <input type="checkbox" checked="true" name="thumb<?= $thumbId ?>" id="thumb"
												value="<?= $thumbId ?>" />
										</div>
									</div><?= $drag_box_element ?>
									<div style="clear:both"></div>
								</div>

						<?php
												}
											}
						?>
						<div style="clear:both"></div>
						<div style="float: right; text-align: right; margin: 5px; display: block; width: 100%; padding: 5px;"><a
								href="javascript:select(true);">Select All</a> | <a href="javascript:select(false);">Deselect All</a></div>
						<div style="clear:both"></div>
					<?php
										} else $picsCount = 0;
					?>
					<div style="clear:both"></div>
					<div id="tags"></div>
					Action tags: <br />
					<?php
										$current_letter = false;
										foreach ($actionTags as $tag) {
											if (is_array($gallery['tags']) && in_array($tag['id'], $gallery['tags']['id'])) $tag_exists = true;
											else $tag_exists = false;
											if ($current_letter != $tag['name'][0]) {
												$tag_name = "<b style='color: rgba(11, 65, 159, 1)'>" . $tag['name'][0] . "</b>" . substr($tag['name'], 1);
												$current_letter = $tag['name'][0];
											} else {
												$tag_name = $tag['name'];
											}
					?>
						<div class="catt" <?php if ($tag_exists) {
											?>onClick="remove_gallery_tag(<?= $gallery['id'] ?>,<?= $tag['id'] ?>);"
							style="background-color: #666; color: #fff; font-weight: 700;" <?php
																						} else {
																							?>onClick="add_gallery_tag(<?= $gallery['id'] ?>,<?= $tag['id'] ?>);" <?php
																												} ?> id="tag_<?= $tag['id'] ?>"><?= $tag_name ?></div>
					<?php
										}
					?>
					<div style="clear:both"></div><br />
					Categories: <br />
					<?php
										$current_letter = false;
										foreach ($categoryTags as $tag) {
											if (is_array($gallery['tags']) && in_array($tag['id'], $gallery['tags']['id'])) $tag_exists = true;
											else $tag_exists = false;
											if ($current_letter != $tag['name'][0]) {
												$tag_name = "<b style='color: rgba(11, 65, 159, 1)'>" . $tag['name'][0] . "</b>" . substr($tag['name'], 1);
												$current_letter = $tag['name'][0];
											} else {
												$tag_name = $tag['name'];
											}
					?>
						<div class="catt" <?php if ($tag_exists) { ?>onClick="remove_gallery_tag(<?= $gallery['id'] ?>,<?= $tag['id'] ?>);"
							style="background-color: #666; color: #fff; font-weight: 700;" <?php } else { ?>onClick="add_gallery_tag(<?= $gallery['id'] ?>,<?= $tag['id'] ?>);" <?php } ?>
							id="tag_<?= $tag['id'] ?>"><?= $tag_name ?></div>
					<?php
										}
					?>
					<div style="clear:both"></div>
					<br>
					<?php if ($local_gal_id > 0 && $site_name) { ?>
						<div style="float: left;">
							<input onclick="return confirm('удалить галеру с <?= $site_name ?>?')" style="color: #FF5000; background: none;"
								type="submit" value="Удалить с галеру с <?= $site_name ?>" name="deleteSiteGallery" id="<?= $galleryId ?>" />
							&nbsp;&nbsp;&nbsp;
						</div>
					<?php } ?>
					<div style="float: left;">
						<input onclick="return confirm('удалить галеру?')" style="color: #FF0000; background: none;" type="submit"
							value="Удалить галеру" name="deleteGallery" id="<?= $galleryId ?>" />
					</div>
					<?php
										if ($userAuth->isAdmin() && $gallery['status'] == 'uploaded' && isset($_GET['galid'])) {
					?>
						<div style="float: right; margin-left:15px;">
							<input type="submit" value="Изменить и ОК галеру" name="edit-gallery-ok" id="<?= $galleryId ?>"
								onclick="return check_gallery_ok();" />
						</div>
					<?php
										}
					?>
					<div id="bottom"></div>
					<div style="float: right;">
						<input type="submit" value="Изменить галеру" name="edit-gallery" id="edit-gallery-ok"
							onclick="return check_gallery_ok();" />
					</div>
					<div style="clear:both"></div>
		</form>
		<div class="counter">Всего <?= $picsCount ?> тумб</div>
		<div class="counterChoosen">Выбрано: <div id="counterChoosen"><?= $picsCount ?></div>
		</div>
		<?php if ($gallery['type']  == 'Movies') {
											$imageSize = @getimagesize($origImageURL);
		?>
			<div class="counterChoosen" style="margin-top:70px;">
				Длительность: <div id="counterChoosen"><?= $gallery['contentCount'] ?> сек</div>
			</div>
			<?php if (isset($imageSize[0]) && isset($imageSize[1])) { ?>
				<div class="counterChoosen" style="margin-top:140px;">
					Кадр: <div id="counterChoosen"><?= $imageSize[0] ?>x<?= $imageSize[1] ?></div>
				</div>
				<?php if (!isset($_GET['galid']) && $gallery['type'] == 'Movies') { ?>
					<script type="text/javascript">
						select(false);
					</script>
		<?php }
											}
										} ?>
<?php
									} else {
										echo "Очередь тегов пуста";
									}
								} else {
									echo "Галера не найдена<br />\n\r";
								}
?>
<div id="video-preview-modal"
	style="display:none; position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.82); z-index:10000;">
	<div
		style="width:720px; max-width:94%; margin:40px auto; background:#111; border:1px solid #444; padding:16px; color:#fff; position:relative;">
		<div style="font-size:18px; font-weight:bold; margin-bottom:10px;">Видео preview</div>
		<div id="video-preview-modal-status" style="font-size:13px; color:#ccc; margin-bottom:10px;"></div>
		<video id="video-preview-player" controls autoplay muted style="width:100%; max-height:70vh; background:#000;">
			<source id="video-preview-source" src="" type="video/mp4">
		</video>
		<div style="margin-top:12px; text-align:right;">
			<input type="button" value="Закрыть" onclick="return close_video_preview_modal();">
		</div>
	</div>
</div>
<script type="text/javascript" language="Javascript">
	function open_video_preview_modal(url, galId) {
		var modal = document.getElementById('video-preview-modal');
		var source = document.getElementById('video-preview-source');
		var player = document.getElementById('video-preview-player');
		var status = document.getElementById('video-preview-modal-status');
		var glue = (url.indexOf('?') === -1) ? '?' : '&';
		source.src = url + glue + 'v=' + (new Date().getTime());
		player.load();
		modal.style.display = 'block';
		status.innerHTML = 'GID #' + galId;
		return false;
	}

	function close_video_preview_modal() {
		var modal = document.getElementById('video-preview-modal');
		var source = document.getElementById('video-preview-source');
		var player = document.getElementById('video-preview-player');
		player.pause();
		source.src = '';
		player.load();
		modal.style.display = 'none';
		return false;
	}

	function generate_video_preview(galId) {
		var request = new XMLHttpRequest();
		var body = 'gal_id=' + encodeURIComponent(galId);
		request.open('POST', 'util/video_preview_generate.php', true);
		request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		request.onreadystatechange = function() {
			if (request.readyState !== 4) {
				return;
			}

			if (request.status !== 200) {
				alert('Ошибка генерации preview');
				return;
			}

			var data = null;
			try {
				data = JSON.parse(request.responseText);
			} catch (e) {
				alert('Некорректный ответ сервера');
				return;
			}

			if (!data || data.error) {
				alert(data && data.error ? data.error : 'Ошибка генерации preview');
				return;
			}

			if (data.preview && data.preview.url) {
				open_video_preview_modal(data.preview.url, galId);
			}
		};
		request.send(body);
		return false;
	}

	function closePage(uI) {
		var $jq = jQuery.noConflict();
		$jq.post("clear_out.php", {
			user_id: uI
		}, function(data) {

		});
	}
	window.onbeforeunload = function() {
		closePage(<?= $user_id ?>);
	};
	window.onunload = function() {
		closePage(<?= $user_id ?>);
	};
</script>
<?php
}
?>