<?php


	$only_no_empty_titles		= (isset($_POST['only_no_empty_titles']) && $_POST['only_no_empty_titles']) ? true : false;
	$gals_count 				= (isset($_POST['gals_count']) && $_POST['gals_count']) ? (int)$_POST['gals_count'] : 50;
	$open_all_galleries 		= (isset($_POST['open_all_galleries']) && $_POST['open_all_galleries']) ? true: false;
	$site_id 					= (isset($_POST['site'])) ? intval($_POST['site']) : false;
	$content_type 				= (isset($_POST['type'])) ? $_POST['type'] :  false;
	$sort_by					= (isset($_POST['sort'])) ? $_POST['sort'] : false;
	$dont_show_used 			= (isset($_POST['dont_show_used']) && $_POST['dont_show_used']) ? true : false;
	$days_range_random_query 	= (isset($_POST['days_range_random_query']) && $_POST['days_range_random_query']) ? (int)$days_range_random_query : 30;
	$max_galleries_per_day 		= (isset($_POST['max_galleries_per_day']) && $_POST['max_galleries_per_day'])  ? (int)$max_galleries_per_day : 24;
	$query_step_in_minutes		= (isset($_POST['query_step_in_minutes'])) ? (int)$_POST['query_step_in_minutes'] : 60;

	$exclude_tag 				= (isset($_POST['exclude_tag']) && $_POST['exclude_tag']) ? (int)$_POST['exclude_tag'] : 0;

	$with_tag 					= (isset($_POST['with_tag']) && $_POST['with_tag']) ? (int)$_POST['with_tag'] : 0;

	$rebuildFlag 				= (isset($_POST['rebuild'])) ? true : false;
	$randomize_added_gals 		= (isset($_POST['randomize_added_gals'])) ? true : false;

	$make_galleries 			= (isset($_POST['make-galleries'])) ? true : false;

	$show_site 					= (isset($_POST['show-site'])) ? true : false;

	$category 					= (isset($_POST['category'])) ? $_POST['category'] : false;

	$query_gallery_on 			= time();

	$use_titles_list			= (isset($_POST['use_titles_list'])) ? $_POST['use_titles_list'] : false;
	$titles_from_textfield		= (isset($_POST['titles_list'])) ? $_POST['titles_list'] : false;

	$mix_titles					= (isset($_POST['mix_titles'])) ? true : false;

	$mix_keywords_head			= (isset($_POST['mix_keywords_head'])) ? explode ("\n", $_POST['mix_keywords_head']) : false;
	$mix_keywords_tail			= (isset($_POST['mix_keywords_tail'])) ? explode ("\n", $_POST['mix_keywords_tail']) : false;

	$mix_add_random_num			= (isset($_POST['mix_add_random_num'])) ? true : false;


	unset($_POST['site'], $_POST['rebuild'], $_POST['only_no_empty_titles'], $_POST['gals_count'],
		  $_POST['open_all_galleries'], $_POST['type'], $_POST['sort'], $_POST['dont_show_used'],
		  $_POST['days_range_random_query'], $_POST['max_galleries_per_day'], $_POST['exclude_tag'],
		  $_POST['with_tag'],
		  $_POST['make-galleries'], $_POST['show-site'], $_POST['category'], $_POST['titles_list'],
		  $_POST['use_titles_list'], $_POST['query_step_in_minutes'], $_POST['randomize_added_gals'],
		  $_POST['mix_titles'], $_POST['mix_keywords_head'], $_POST['mix_keywords_tail'],
		  $_POST['mix_add_random_num']);


	// var_dump($mix_titles, $mix_keywords_head,$mix_keywords_tail, $mix_add_random_num);
	// // die;

	if($open_all_galleries && $gals_count > 500) {
		$open_all_galleries = false;
		echo "Нельзя показывать больше 500 галер с раскрытыми тумбами";
	}

	$galleries_worker 	= new Galleries($db->_db);
	$models 			= new CModels($db->_db);

	//
	// Секция генерации галер
	//

	if (isset($_GET['niche']) && $_GET['niche'] == 'on') {
?>	
		<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
			Сгенерировать фид с галерами на сайте: 
			<select name="site" id="site">
				<?php $default->AllSitesToString("<option value=\"#SITE_ID#\">#SITE#</option>"); ?>
			</select>
			ниша: <select name="category" id="category">
				<option value="all">All</option>
				<?php $default->AllTagsToString("<option value=\"#TAG_ID#\">#TAG#</option>"); ?>
			</select>
			<input type="submit" value="Сгенерировать" name="nicheOnRss" id="nicheOnRss" />
		</form>
<?php
	} else {

		if ($make_galleries && $site_id) {

			$make_galleries = $_POST;
			$titles_list = false;
			$siteInformation = $default->SiteInformation($site_id);

			if($randomize_added_gals) { shuffle($make_galleries); }	

			if($titles_from_textfield) {
				$titles_from_textfield = explode("\n", $titles_from_textfield);
				if(is_array($titles_from_textfield) && count($titles_from_textfield)) {
					$titles_list = $titles_from_textfield;
				} 
			}

			$galleries_counter = 0;
			
			foreach ($make_galleries as $gal_id) {

				if (is_string($gal_id) && strpos($gal_id, "u") !== false) {
					$gallery_unique = $site_id;
					$gal_id = (int)ltrim($gal_id, 'u');
				} else {
					$gallery_unique = false;
				}

				$main_thumb = 0;
				$used_title_id = false;

				if(	$titles_list && isset($titles_list[$galleries_counter]) && $titles_list[$galleries_counter] 
					&& strlen($titles_list[$galleries_counter]))
				{
					$title = $titles_list[$galleries_counter];
				} else {
					$title = $galleries_worker->getGalleryTitle($gal_id);	
				}

				if($mix_titles) {
					$head_key 		= (is_array($mix_keywords_head) && shuffle($mix_keywords_head)) ? trim($mix_keywords_head[0]) : "";
					$tail_key 		= (is_array($mix_keywords_tail) && shuffle($mix_keywords_tail)) ? trim($mix_keywords_tail[0]) : "";
					$random_number	= $mix_add_random_num ? rand(1000,100000000) : "";

					$title = ucfirst($head_key) . " " . $title . " " . $tail_key . " " . $random_number;
				}
				
				$gallery_queried = queryGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $query_gallery_on, $used_title_id);
				// $gallery_queried = true;

				if($gallery_queried) {
					echo "GID#".$gal_id." queried to site SID#".$site_id.", will appear on ".date("Y-m-d H:i", $query_gallery_on).", Title: ".$title."<br>";
					$query_step_in_seconds = $query_step_in_minutes * 60;
					$query_gallery_on = $query_gallery_on + $query_step_in_seconds;	
				} else {
					echo "<font style='color: red'>GID#".$gal_id." queried to site SID#".$site_id.": Error adding gallery</font><br>";
				}
				$galleries_counter++;				
			} 			
		} else {
	?>
			<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
				<div style="width: 100%; height: 60px; float: left; text-align: left;">
					<select name="site" id="site">
					<option value="0">Сайт</option>
					<?php	$default->AllMainSitesToString ("<option value=\"#SITE_ID#\"#CHECKED#>#SITE#</option>", $site_id); ?>
					</select>
					<select name="sort" id="sort">
						<option value="mix"<?php if($sort_by == 'mix') echo " selected";?>>mix</option>
						<option value="asc"<?php if($sort_by == 'asc') echo " selected";?>>A-Z</option>
						<option value="desc"<?php if($sort_by == 'desc') echo " selected";?>>Z-A</option>
					</select>
					<select name="gals_count" id="gals_count">
						<option value="10"<?php if($gals_count == 10) echo " selected";?>>10</option>
						<option value="25"<?php if($gals_count == 25) echo " selected";?>>25</option>
						<option value="50"<?php if($gals_count == 50 || !$gals_count) echo " selected";?>>50</option>
						<option value="100"<?php if($gals_count == 100) echo " selected";?>>100</option>
						<option value="150"<?php if($gals_count == 150) echo " selected";?>>150</option>
						<option value="200"<?php if($gals_count == 200) echo " selected";?>>200</option>
						<option value="250"<?php if($gals_count == 250) echo " selected";?>>250</option>
						<option value="300"<?php if($gals_count == 300) echo " selected";?>>300</option>
						<option value="350"<?php if($gals_count == 350) echo " selected";?>>350</option>
						<option value="400"<?php if($gals_count == 400) echo " selected";?>>400</option>
					</select>
					<select name="with_tag" id="with_tag">
						<option value="none">С тегом</option>
						<?php $default->AllTagsToString("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $with_tag); ?>
					</select>
					<select name="exclude_tag" id="exclude_tag">
						<option value="none">Без тега</option>
						<?php $default->AllTagsToString("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $exclude_tag); ?>
					</select>

					Unused only: <input type='checkbox' name="dont_show_used" id="dont_show_used" <?php if($dont_show_used) echo " checked"; ?> >
					Only w/titles: <input type='checkbox' name="only_no_empty_titles" id="only_no_empty_titles" <?php if($only_no_empty_titles) echo " checked"; ?> >
					Open images: <input type='checkbox' name='open_all_galleries' id='open_all_galleries' <?php if($open_all_galleries) echo " checked"; ?> >

					Mix titles: <input type='checkbox' name='mix_titles' id='mix_titles' <?php if($mix_titles) echo " checked"; ?> >
					Add random num: <input type='checkbox' name='mix_add_random_num' id='mix_add_random_num' <?php if($mix_add_random_num) echo " checked"; ?> >

					<input style="width: 60px; height:60px; float: right;" type="submit" value="Ok" name="show-site" id="show-site" />
					<br>					

					<div style="width: 1210px; float: left; text-align: left !important; margin-left: 20px; padding-top: 6px;">
						<input type="radio" name="type" id="type" value="mix"<?php if (!$content_type || $content_type == 'mix') echo " checked"; ?>>All
						<input type="radio" name="type" id="type" value="Pics"<?php if ($content_type == 'Pics') echo " checked"; ?>>Pics
						<input type="radio" name="type" id="type" value="Movies"<?php if ($content_type == 'Movies') echo " checked"; ?>>Movies
						<input type="radio" name="type" id="type" value="gif"<?php if ($content_type == 'gif') echo " checked"; ?>>GIFs
						<div style="float:right; width: 250px;">
							Открыть поле для микс-тайтлов: <input type='checkbox' name='use_titles_list' id='use_titles_list' <?php if($use_titles_list) echo " checked"; ?> >
						</div>
					</div>
					
				</div>
				<div style="clear: both;"></div>
			</form>
			<hr />

	<?php	if ($show_site) {
				if ($site_id) {

					$site = $default->SiteInformation($site_id);

					if ($site['site_own_titles'] && $site['site_own_main_thumbs']) { ?>
						<div style="width: 100%; margin: 2px;">
							<div style="width: 35%; background-color: #ddd; float: right; font-size: 15px; margin-right: 6px; padding: 5px; text-align: left;">
								<h4>Добавление галер:</h4>
								Нажатие F4 	- добавление текущей галеры в рандомный день<br>
								Ctrl+F1 	- добавление выбранной галеры<br>
								Ctrl+F2 	- добавление выбранной галеры в очередь на сегодня<br>
								Ctrl+1..0 	- добавление выранной галеры в очередь на +1-10 дней от сегодня<br>
							</div>

							<div style="width: 56%; background-color: #fff; float: left; font-size: 15px; padding: 0 5px; text-align: left;">
								Кол-во дней для рандомного добавления: <input style="font-size: 16px; height: 26px; width: 50px;" type="text" id="days_range_random_query" name="days_range_random_query" value="<?=$days_range_random_query?>">
								<br>
								Max gals per day (0 - нет ограничения): <input style="font-size: 16px; height: 26px; width: 50px;" type="text" id="max_galleries_per_day" name="max_galleries_per_day" value="<?=$max_galleries_per_day?>">
								<br>
 								Шаг очереди в минутах: <input  style="font-size: 16px; height: 26px; width: 50px;" type="text" id="query_step_in_minutes" name="query_step_in_minutes" onkeyup="changeQueryStep(); return false;" value="<?=$query_step_in_minutes?>">
								<br>
								Быстрое добавление на рандомную дату при нажатии на тумбу: <input type='checkbox' id='auto-mix-add'>
								<br>
								Быстрое добавление на сегодняшнюю дату при нажатии на тумбу : <input type='checkbox' id='auto-add'>

							</div>
						</div>
						<div style="clear: both;"></div>
						<hr />
					<?php } ?>
	<?php

					$use_horiz_thumbs = (isset($site['thumb_by_horiz_width']) && $site['thumb_by_horiz_width']) ? true : false;

					if (!$site['site_own_titles'] && !$site['site_own_main_thumbs']) { 
						$sites_tags_list = array(); 
					} else {
						$tags_worker = new Tags($db->_db);
						$sites_tags_list = $tags_worker->getAllTags($site['niche'], false, true); //ниша, вернет массив, а не строку, отстортировано по имени
					}

					$added_galleries_in_query = getSitesQuery($site_id);

					$make = New Maker($site, $content_type, $sort_by, $dont_show_used, $only_no_empty_titles, $gals_count, $exclude_tag, $with_tag);
					$makes = $make->Ret();
?>
					Всего добавленых в очередь галлерей: <b><?=$added_galleries_in_query?></b><br>
					<div style="position: relative; width: calc(100% - 6px); border: solid 3px #666;">
						<div class="site_query_date">Сегодня<br /><div id="queried_on_0"><?=$added_galleries_in_query ? getSitesQueryCountByDay($site_id,0) : 0 ?></div></div>
<?php 					for ($i = 1; $i <=29; $i++) { 
							if($i == 1) $day_t = " день";
							elseif ($i > 4 ) $day_t = " дней"; 
							else $day_t = " дня"; ?>
							<div class="site_query_date" onclick="show_site_added_query(<?=$site_id?>, <?=$i?>)">+<?=$i.$day_t?>
								<br>
								<div id="queried_on_<?=$i?>"><?=$added_galleries_in_query ? getSitesQueryCountByDay($site_id,$i) : 0 ?></div>
							</div>
<?php 					} ?>

						<div style="clear : both;"></div>	
					</div>
					<div id="site_query_by_date_elem"></div>


<?php 				if (is_array($makes) && count($makes)) { 
						$galleries_count = count($makes);
					?>

					<form name=stats enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">

						<?php if($mix_titles) { ?>
							<input type='checkbox' name='mix_titles' id='mix_titles' checked style="display: none;">
							<input type='checkbox' name='mix_add_random_num' id='mix_add_random_num' <?php if($mix_add_random_num) echo " checked"; ?> style="display: none;">
							Head:<textarea style="width: 45%; height: 200px;" name="mix_keywords_head"></textarea>
							Tail:<textarea style="width: 45%; height: 200px;" name="mix_keywords_tail"></textarea>
						<?php } ?>

						<?php 
						if (!$site['site_own_titles'] && !$site['site_own_main_thumbs']) { ?>
							<script>
								function update_checked_gals_counter() {
									document.getElementById('galleries_count').innerHTML = document.querySelectorAll('input.xthumb[type="checkbox"]:checked').length;
								}
								function update_titles_counter() {
									var titles = document.getElementById("titles_list").value;
									var titles_counter = titles.split(/\r|\r\n|\n/).length;
									document.getElementById('titles_count').innerHTML = titles_counter
								}
							</script>
							<div style="width: 100%">

						<?php if($use_titles_list) { ?>

							<div style="width: 20%; float: left; height: 100%; background-color: #eee; ">
								<div style="width:100%; height: 60px; padding: 10px 0;">
									Выбрано галер: <div id="galleries_count"><?=$galleries_count?></div><br>
									Тайтлов в списке:  <div id="titles_count">0</div>
								</div>
								<textarea onkeyup="update_titles_counter()" rows="<?=$galleries_count?>" name="titles_list" id="titles_list" wrap="off" style='width: 90%; overflow: scroll; padding:2px; margin: 5px;'></textarea>
							</div>
							<div style="width: 78%; float: right;">

						<?php } ?>

							<div style="display: none;"><input name="site" id="site" value="<?=$site_id?>"></div>
							<div style="float: left;">Шаг очереди в минутах: <input  style="font-size: 14px; height: 22px; width: 40px;" type="text" id="query_step_in_minutes" name="query_step_in_minutes" onkeyup="changeQueryStep(); return false;" value="<?=$query_step_in_minutes?>"></div>
							<div style="float: right"><h2><a href="javascript:select(true); update_checked_gals_counter();">Select All</a> | <a href="javascript:select(false);update_checked_gals_counter();">Deselect All</a></h2></div>
							<div style="clear: both;"></div>
						<?php } else { ?>
							<div class="counterChoosen">
								GID#<div id="current_gallery_id">0</div>
								Райтеру:<input id="writers_query_checkbox"  type='checkbox'>
							</div>
							
						<?php } ?>

<script>
	function isInView(el) {

	  el = document.getElementById(String(el));
	  if (el) {
	  	var top = el.offsetTop;
		  var left = el.offsetLeft;
		  var width = el.offsetWidth;
		  var height = el.offsetHeight;

		  while(el.offsetParent) {
		    el = el.offsetParent;
		    top += el.offsetTop;
		    left += el.offsetLeft;
		  }

		  if (height > window.innerHeight) height = window.innerHeight - 210;

		  return (
		    top >= window.pageYOffset &&
		    left >= window.pageXOffset &&
		    (top + height) <= (window.pageYOffset + window.innerHeight) &&
		    (left + width) <= (window.pageXOffset + window.innerWidth)
		  );
	  } else return false;
	  
	}

	function checkInViewArray(elems) {
		for (var i = 0; i < elems.length; i++) {
			var in_view = isInView(elems[i]);
			if(in_view) return elems[i];
		}
		return false;
	}
	var galleries_array = new Array();
</script>


<?php						$info_div_block_size = 1000;
							$sources = new Sources($db->_db);
							foreach ($makes as $galleryId => $gallery) {
								$main_thumb = 0;
								$gallery['paysite']['name'] = $sources->getSourceNameById($gallery['paysite']['id']);
								$gallery['posted'] = $default->galleryPostedTo($galleryId);
								if ($gallery['type']=='Pics') {
									$thumbClass = 'thumb';
									$gallery['thumb'] = $default->GetGalleryImage($galleryId);
									if(isset($gallery['gal_thumb']) && $gallery['gal_thumb']) {
										$imageId = $gallery['gal_thumb'];
										$main_thumb = $imageId;
									} else { $imageId = $default->GetGalleryImageId($galleryId); }
									if($use_horiz_thumbs) {
										$thumb = HOSTING . "/thumbs/x300/";
										$info_div_block_size = 970;
									} else $thumb = HOSTING . "/thumbs/p/180/";
								} elseif($gallery['type']=='gif') {
									$thumbClass = 'thumb-gif';
									$imageId = $default->GetGifFrame($galleryId);									
									$thumb = HOSTING . $imageId;
								} else {
									$thumbClass = 'thumb-movies';
									if(isset($gallery['gal_thumb']) && $gallery['gal_thumb']) {
										$imageId = $gallery['gal_thumb'];
										$main_thumb = $imageId;
									} else { $imageId = $default->GetMovieScreenshot($galleryId); }
									$thumb = HOSTING . "/thumbs/m/240/";
								}
								if ($imageId < 256000){
									$folderId = (int)ceil($imageId/1000);
									$folder = "1/".$folderId;
								} else {
									$mainFolder= (int)ceil($imageId/256000);
									$folderId = (int)ceil($imageId/1000);
									$folder = $mainFolder."/".$folderId;
								}
								$gallery['additional_titles'] = $galleries_worker->getAllAdditionTitles($galleryId);
								if($gallery['type']=='Pics' || $gallery['type']=='Movies') {
									$thumb .= $folder ."/".$imageId.".jpg";
								}
								if ($site['site_own_titles'] || $site['site_own_main_thumbs']) {
									include "templates/make.gallery.own-params.php";
								} else {
									include "templates/make-gallery.php";
								}
							}
							if (!$site['site_own_titles'] && !$site['site_own_main_thumbs']) {
?>
								<?php if($use_titles_list) { ?></div><?php } ?>

								<div style="clear:both"></div>
								<div style="float: right">
									<h2><a href="javascript:select(true); update_checked_gals_counter();">Select All</a> | <a href="javascript:select(false);update_checked_gals_counter();">Deselect All</a></h2>
								</div>
								<div style="clear:both"></div>
								Галеры из списка добавить рандомно: <input type="checkbox" name="randomize_added_gals" checked	>	
								<input type="submit" name="make-galleries" id="make-galleries" value="Make Galleries">
							</div>
<?php 						} ?>

							</form>
<?php					} else { ?>
							<h1>Галер для сайта больше нет, пиздуй ставить теги :)</h1>
<?php 					} ?>
						<div style="clear:both"></div>
						<hr>
<?php
				}
			}

		}	
	}
?>

<?php 
if(isset($site) && $site && isset($site['id'])) { ?>
<script>
var current_site_id = <?=$site['id']?>;
var current_gallery = document.getElementById('current_gallery_id');
var current_gallery_id = 0;

function setCurrentId(gal_id) {
	current_gallery.innerHTML = gal_id;
 	current_gallery_id = gal_id;
}

function getCurrentGalId() {
	var inview_element = checkInViewArray(galleries_array);
 	if(inview_element) {
 		current_gallery.innerHTML = inview_element;
 		current_gallery_id = inview_element;
 	}
}

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}
 document.onscroll=function(){
 	getCurrentGalId();
 };

 						jQuery(function($){
							$(document).ready(function() {
								getCurrentGalId();
								$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+F1',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery(<?=$site['id']?>, current_gallery_id);
						            	sleep(500); getCurrentGalId();
						            }
						        });
						        $.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+F2',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 0);
						            	sleep(500); getCurrentGalId();
						            }
						        });
						        $.Shortcuts.add({
						            type: 'down',
						            mask: 'F4',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query_random_day(<?=$site['id']?>, current_gallery_id);
						            	sleep(500); getCurrentGalId();
						            }
						        });
						        $.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+1',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 1);
						            	getCurrentGalId();
						            }
						        });	
						        $.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+2',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 2);
						            	getCurrentGalId();
						            }
						        });
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+3',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 3);
						            	getCurrentGalId();
						            }
						        });
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+4',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 4);
						            	getCurrentGalId();
						            }
						        });
						        $.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+5',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 5);
						            	getCurrentGalId();
						            }
						        });
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+6',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 6);
						            	getCurrentGalId();
						            }
						        });
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+7',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 7);
						            	getCurrentGalId();
						            }
						        });
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+8',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 8);
						            	getCurrentGalId();
						            }
						        });	
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+9',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 9);
						            	getCurrentGalId();
						            }
						        });	
						   		$.Shortcuts.add({
						            type: 'down',
						            mask: 'Ctrl+0',
						            enableInInput: true,
						            handler: function() {
						            	add_site_gallery_to_query(<?=$site['id']?>, current_gallery_id, 10);
						            	getCurrentGalId();
						            }
						        });							        
						        $.Shortcuts.start();
							});
						});

						update_checked_gals_counter();
</script>
<?php } ?>
