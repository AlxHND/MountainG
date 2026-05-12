<?php

if(isset($_GET['image_id'])) {
	$image = new Images($db->_db);

	$image->getGalleryIdByImageId($_GET['image_id']);
	var_dump($image->getGalleryIdByImageId($_GET['image_id']));

	die;

	// https://p-thumbs.sexhound.com/8/1836/1835664.jpg 
}


if(isset($_GET['remove_gallery'], $_GET['site']) && (int)$_GET['remove_gallery'] > 0 && (int)$_GET['site'] > 0) {
	if(deleteGalleryFromSite($_GET['site'], $_GET['remove_gallery'])) {
		echo "Галлера '".$_GET['remove_gallery']."' с сайта '".$_GET['site']."' удалена успешно!<br>";
	} else {
		echo "Удаление галлереи '".(int)$_GET['remove_gallery']."' с сайта '".(int)$_GET['site']."' провалилась<br>";
	}
} else {
	if (isset($_POST['delete_button'], $_POST['selected_action']) && $_POST['selected_action'] == 'delete') {
		$resetGallery = new Galleries($db->_db);
		foreach($_POST as $item) {
			$pos = strpos($item, 'selected_gal_id_');
			if($pos !== false) {				
				if ($gallery_id = intval(substr($item, 16, strlen($item)))) {
					if ($resetGallery->deleteGallery($gallery_id)) {
						$userAuth->userRemovedGallery($userId, $gallery_id);
						echo "Галлерея #".$gallery_id." удалена<br>";
					} else  echo "Ошибка! Галлерея #".$gallery_id." не удалена<br>";
				}

			}
		}
	} elseif (isset($_POST['selected_action']) && $_POST['selected_action'] == 'reset_to_new') {
		$resetGallery = new Galleries($db->_db);
		foreach($_POST as $item) {
			$pos = strpos($item, 'selected_gal_id_');
			if($pos !== false) {
				$gallery_id = intval(substr($item, 16, strlen($item)));
				if ($gallery_id) {

					if ($resetGallery->resetToNew($gallery_id)) echo $gallery_id. " отправлена в рекроп<br>";

				}

			}
		}
	}

 
	$site_galleries_flag = false;
	
	$sources = new Sources($db->_db);

    if (isset($_REQUEST['reset_status']) && $user_type == 'admin') {
    	$gallery_reset = intval($_REQUEST['reset_status']);
    	if ($gallery_reset) {
    		$resetGallery = new Galleries($db->_db);
    		if ($resetGallery->resetToNew($gallery_reset)) echo $_REQUEST['reset_status']. " отправлена в рекроп<br>";
    	}
    }
    if (isset($_REQUEST['reset_video_fail']) && $user_type == 'admin') {
    	$gallery_reset = intval($_REQUEST['reset_video_fail']);
    	if ($gallery_reset) {
    		$resetGallery = new Galleries($db->_db);
    		if ($resetGallery->resetToFetched($gallery_reset)) echo "Галлерея #".$_REQUEST['reset_video_fail'] . " сброшена в fetched<br>";
    		else echo "Ошибка! Галлерея #<a href='index.php?act=galleries&amp;galid=".$_REQUEST['reset_video_fail']."'>". $_REQUEST['reset_video_fail'] . "</a> не сброшена в fetched<br>";
    	}
    }    

	//	Просмотр одиночной галеры, в случае если установлен флаг "tags" или установлена ID галеры
	if (isset($_GET['galid']) || (isset($_GET['tags']) && $user_type == 'admin') || $user_type == 'tags' || $user_type == 'croptags') {
		include "gallery.single.php";
	} elseif ($user_type == 'admin') {

		$gallery_worker = new Galleries($db->_db);

		$sortQueryString ="";
		$pagingSortQueryString = "";
		$search = false;
		$searchAddition = "";
		$currentPage = 1;
		$galsOffset = 0;
		$gCounter = 0;
		$status = false;
		$type = false;
		$niche = false;
		$paysite = false;

		if (isset($_REQUEST['search']) && $_REQUEST['search'] !== "") {
			$search = $_REQUEST['search'];
			$searchAddition = "&search=".$search;
		}
		if (isset($_REQUEST['sort']) && (preg_match('/^(id|local_id|title|date|paysite|niche|pics|status|pageviews|likes|rating)$/', $_REQUEST['sort']))) {
			$sort = $_REQUEST['sort'];
			$pagingSortQueryString ="&sort=".$sort;
		} else {
			$sort = (isset($_REQUEST['site']) && $_REQUEST['site']) ? 'local_id' : 'id';			
		}
		if (isset($_REQUEST['order']) && (preg_match('/^(asc|desc)$/', $_REQUEST['order']))) {
			$order = $_REQUEST['order'];
			$sortQueryString .="&order=".$order;
		} else $order = 'desc';

		if (isset($_REQUEST['num']) &&  (int)$_REQUEST['num']) {
			$galsPerPage = (int)$_REQUEST['num'] ;
			$sortQueryString .= "&num=".$galsPerPage;	
		} else $galsPerPage = 50;
		if (isset($_REQUEST['category']) && intval($_REQUEST['category'])) {
			$category = $_REQUEST['category'];
			$sortQueryString .= "&category=".$category;
		} else $category = false;	

		if (isset($_REQUEST['searchby'])) {
			if ($_REQUEST['searchby'] == 'url') $searchBy = 'url';
			elseif ($_REQUEST['searchby'] == 'desc') $searchBy = 'desc';
			elseif ($_REQUEST['searchby'] == 'titledesc') $searchBy = 'titledesc';
			else $searchBy = 'title';
		} else $searchBy = 'title';
		if ($search) $searchAddition .= "&searchby=".$searchBy;

		if (isset($_REQUEST['offset']) &&  (int)$_REQUEST['offset']) {
			$currentPage = (int)($_REQUEST['offset']);
			$galsOffset = (int)$_REQUEST['offset'];
			// $sortQueryString .="&offset=".$galsOffset;
			$galsOffset = ($galsOffset-1)*$galsPerPage;
			$gCounter = $galsOffset;

		}
		if (isset($_REQUEST['status']) && (preg_match('/^(zip|zipupload|new|grabbed|thumbs|uploaded|tagged|toregrab|OK|trash|delete|error|unzip_fail|fetching_fail|video_fail|all_fails|unzipping|all_ready|all_processing|all_delete)$/', $_REQUEST['status']))) {
			$status = $_REQUEST['status'];
			$sortQueryString .="&status=".$status;
		} 
		if (isset($_REQUEST['type']) && preg_match('/^(Pics|Movies)$/', $_REQUEST['type'])) {
			$type = $_REQUEST['type'];
			$sortQueryString .="&type=".$type;
		} 
		if (isset($_REQUEST['niche']) && preg_match('/^(Gay|Straight|Shemale)$/', $_REQUEST['niche'])) {
			$niche = $_REQUEST['niche'];
			$sortQueryString .="&niche=".$niche;
		}		
		if (isset($_REQUEST['paysite']) && (int)$_REQUEST['paysite']) {
			$paysite = (int)$_REQUEST['paysite'];
			$sortQueryString .="&paysite=".$paysite;
		}
		if (isset($_REQUEST['thumbs']) && $_REQUEST['thumbs'] == 'true') {
			$sortQueryString .="&thumbs=true";
		}
		$main_query_string ="./index.php?act=galleries";

		if (isset($_REQUEST['site']) && $sites->switchSite($_REQUEST['site'])) {
			$sortQueryString .= "&amp;site=".intval($_REQUEST['site']);
			$main_query_string .= "&amp;site=".intval($_REQUEST['site']);

			$galleries = $sites->getGalleriesList($sort, $order, $galsPerPage, $galsOffset, $type, $paysite, $status, $search, $category, $searchBy, $niche);
			$_galleriesCount = $sites->galleryCounter;
			$site_galleries_flag = true;
			$site_id = intval($_REQUEST['site']);
		} else {
			$galleries = $gallery_worker->getGalleriesList($sort, $order, $galsPerPage, $galsOffset, $type, $paysite, $status, $search, $category, $searchBy, $niche);
			$_galleriesCount = $gallery_worker->getCurrentGlasCount();
		}
		
		
		
		if (!$search) $search = "искать...";

		$pages = ceil($_galleriesCount / $galsPerPage);
		$use_vcdn = ($type == 'Movies' || ($sites && $sites->siteType() == 'video' && $sites->getVCDNType() == 'static')) ? true : false;
		
		if($galsOffset < 2) { // первая страница
?>
			<div style="float: left; display: block; width: 100%; text-align: left; font-size: 12px; margin: 15px;">
				Галер в (uploaded|OK) : <b><?= $gallery_worker->getReadyGalleriesCount() ?></b>
				| Галер с фейл статусами (ошибки) : <b><?= $gallery_worker->getFailedGalleriesCount() ?></b>
				| Галер в процессинге (вероятные залипы) : <b><?= $gallery_worker->getProcessingGalleriesCount() ?></b>
				| Галер с горизонтальным ресайзом : <b><?= $gallery_worker->getResizedGalleriesCount() ?></b> и без: <b><?= $gallery_worker->getNotResizedGalleriesCount() ?></b> (только Pics)

			</div>
			<hr>
			<div style="float: left; display: block; width: 100%; text-align: left; font-size: 12px; margin: 15px;">
			  Всего ОК галер: <?=$galleries_count = $gallery_worker->countGalleries(); ?>,
			  Галер ОК в кэше: <strong id="galleries_count_block"><?php 
			                      $galleries_cached = $cache_worker->galleriesCount();
			                      if ($galleries_cached && $galleries_cached == $galleries_count) echo $galleries_cached;
			                      elseif (!$galleries_cached) {
			?>
			                        Пусто
			<?php                        
			                      } else {
			?>
			                        Количество тегов в кэше не совпадает: <i><?=$galleries_cached?></i>
			<?php
			                      } 
			?></strong>
			  <input type="button" value="Пересобрать кэш ОК галер" id="init_ok_galleries" onclick="init_ok_galleries();">

			</div>
<?php 	}  ?>		
		<form name=selector id="galleryselector" action="<?=$_SERVER['SCRIPT_NAME'] ?>?act=galleries" method="post">
				<div style="width:100%; height: 30px; display: block;" >
					<div style="float: left; ">
						&nbsp;<select name="type" id="type" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="all">Тип</option>
							<option value="Movies">Movies</option>
							<option value="Pics">Pics</option>
						</select>					
						&nbsp;<select name="niche" id="niche" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="all">Ниша</option>
							<?php $default->AllNichesToString("<option value=\"#NICHE#\">#NICHE#</option>"); ?>
						</select>
						&nbsp;<select name="paysite" id="paysite" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="all">Платник</option>
							<?php if (isset ($_GET['details']) && $_GET['details'] == 'yes') echo $default->AllPaysitesToString ("<option value=\"#PAYSITE_ID#\">#PAYSITE#, галер: #GALLERIES_COUNT#, последний апдейт: #LAST_UPDATE#</option>", 'uploaded', 'Pics'); 
								else $sources->listSourcesGals ("<option value=\"#PAYSITE_ID#\">#PAYSITE# - (#GALLERIES_COUNT#)</option>", $status);  ?>
						</select>
						&nbsp;<select name="category" id="category" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="all">Категория</option>
							<?php $default->AllTagsToString ("<option value=\"#TAG_ID#\">#TAG#</option>"); ?>
						</select>					
						&nbsp;<select name="status" id="status" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="all">Статус</option>
							<option value="all">Все</option>
							<option value="OK">OK</option>
							<option value="all_ready">(OK|uploaded)</option>
							<option value="uploaded">Uploaded</option>
							<option value="tagged">Tagged</option>
							<option value="grabbed">Grabbed</option>
							<option value="new">New</option>
							<option value="zipupload">Zipupload</option>
							<option value="zip">Zip</option>
							<option value="newzip">NewZip</option>
							<option value="unzipping">Unzipping</option>
							<option value="all_fails">All Errors</option>
							<option value="screening_fail">Screen Errors</option>
							<option value="fetching_fail">Fetch Errors</option>
							<option value="unzip_fail">Unzip Errors</option>
							<option value="video_fail">Video Errror</option>
							<option value="all_processing">Processing</option>
							<option value="all_delete">Удаленные</option>
							
						</select>
						&nbsp;Sort by: <select name="sort" id="sort" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="id">ID</option>
							<option value="title">title</option>
							<option value="date">date</option>
							<option value="paysite">paysite</option>
							<option value="niche">niche</option>
							<option value="pics">pics</option>
							<option value="status">status</option>
							<option value="likes">likes</option>
							<option value="pageviews">pageviews</option>
						</select>
						&nbsp;P.page: <select name="num" id="num" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="50">50</option>
							<option value="100">100</option>
							<option value="150">150</option>
							<option value="200">200</option>
							<option value="250">250</option>
							<option value="300">300</option>
						</select>
						&nbsp;Тумбы: <select name="thumbs" id="thumbs" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="false"<?php if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'false') { ?> selected='selected' <?php }?>>Нет</option>
							<option value="true"<?php if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') { ?> selected='selected' <?php }?>>Да</option>
						</select>					
						&nbsp;<select name="order" id="order" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
							<option value="asc">A-Z</option>
							<option value="desc">Z-A</option>
						</select>					

					
						<div id="searchResult" class="searchResult">
							<a href="<?=$main_query_string?>">Выбрать</a>
						</div>
					</div>	
				</div>
				<div style="clear: both;"></div>
			<hr>

			<div style="width:100%; height: auto; display: block;" >
				<input type="text" name="search" id="search" value="<?=$search?>" onblur="if(this.value=='') this.value='искать...';" onfocus="if(this.value=='искать...') this.value='';" style="width:800px; height: 28px; background-color: #f9f9f9;">
				 по
				 <select name="searchby" id="searchby">
						<option value="title">Тайтлу</option>
						<option value="desc">Деску</option>
						<option value="titledesc">Тайтл+Деску</option>
						<option value="url">Урлу</option>
				</select>
				<div style="float: right;"><input type="submit" value="Показать галеры" name="searchGalleries" onclick="searchBoxGalleryThumbs();"/></div>
			</div>
		</form>	

<?php
			if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') {
				foreach ($galleries as $galleryId => $gallery) {
					$imageId = $gallery['image'];
					if ($imageId < 256000){
						$folderId = (int)ceil($imageId/1000);
						$folder = "1/".$folderId;
					} else {
						$mainFolder= (int)ceil($imageId/256000);
						$folderId = (int)ceil($imageId/1000);
						$folder = $mainFolder."/".$folderId;
					}
					if ($gallery['type'] == 'Pics') {
						$thumbURL = HOSTING."/thumbs/p/150/".$folder."/".$gallery['image'].".jpg";
						$widhtHeight = "width:150px; height:205px;";
					}
					else {
						$thumbURL = HOSTING."/thumbs/m/200/".$folder."/".$gallery['image'].".jpg";
						$widhtHeight = "width:200px; height:150px;";
					}
?>
			        <a href="index.php?act=galleries&amp;galid=<?=$galleryId?>">
			          <div style="margin:6px; padding: 3px; width: <?php if ($gallery['type'] == 'Pics') { ?>186px<?php } else { ?>206px<?php } ?>; height: 276px; border: 1px #000 solid; display: block-inline; float:left; text-align: center;">
			              <img src="<?=$thumbURL?>" style="border: solid 1px #000;">
			              <?=$gallery['affiliate']?> | <?=$gallery['niche']?><br />
			              Добавлена: <?=$gallery['added']?> | ID: <?=$galleryId?>
			          </div>
			        </a>
<?php
				}				
			} else {
?>
	<form name="galleries_block" id="galleries_block" action="<?=$_SERVER['SCRIPT_NAME'] ?>?act=galleries" method="post">
			<div style="width:600px; margin: 10px; padding:5px; height: auto; display: block; background-color: #ccc; border: 1px solid red;" >
				 Выбранные галеры: <select name="selected_action" id="selected_action">
						<option value="no">Ничего</option>
						<option value="delete">Удалить</option>
<?php if($status == 'fetching_fail') { ?>
						<option value="reset_to_new">Сбросить ошибку в New</option>
<?php } ?>
				</select>
				<div style="float: right;"><input type="submit" value="Применить" name="delete_button"  onclick="return confirm('Применить?');" /></div>
			</div>
			<table align="center">
			<tr>
				<td width=38 bgcolor="#c0c0c0"></td>
				<td width=38 bgcolor="#c0c0c0">
					<?php  if ($site_galleries_flag) { ?>
						<a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=local_id&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">LcID</a>
					<?php } ?>
				</td>
				<td width=41 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=id&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">ID</a></td>
				<td width=260 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=url&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">URL</a></td>
				<td width=260 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=title&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Title</a></td>
				<td width=50 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=niche&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Niche</a></td>
				<td width=40 bgcolor="#c0c0c0">Type</td>
				<td width=130 bgcolor="#d0d0d0">Affil.</td>
				<td width=130 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=paysite&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Paysite</a></td>
				<td width=50 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=pics&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Pics</a></td>
				<td width=75 bgcolor="#c0c0c0"><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=date&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Added</a></td>
				<td width=80 bgcolor="#c0c0c0">
					<?php 
					if ($site_galleries_flag) { ?>
						Удалить
					<?php	
					} else { ?>
						<a href="index.php?act=galleries<?=$sortQueryString?>&sort=status&order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Status</a>
<?php				} ?>
				</td>
				<td width=40 bgcolor="#c0c0c0"></td>
<?php 			if($use_vcdn) { ?>
					<td width=20 bgcolor="#c0c0c0">vCDN</td>
<?php 			} ?>
			</tr>
<?php
			if (is_array($galleries)) {
				foreach ($galleries as $galleryId => $gallery) {
					//var_dump($gallery);
					$imageId = $gallery['image'];
					if ($imageId < 256000){
						$folderId = (int)ceil($imageId/1000);
						$folder = "1/".$folderId;
					} else {
						$mainFolder= (int)ceil($imageId/256000);
						$folderId = (int)ceil($imageId/1000);
						$folder = $mainFolder."/".$folderId;
					}
					if ($gallery['type'] == 'Pics') {
						$thumbURL = HOSTING."/thumbs/p/150/".$folder."/".$gallery['image'].".jpg";
						$widhtHeight = "width:150px; height:205px;";
					}
					else {
						$thumbURL = HOSTING."/thumbs/m/200/".$folder."/".$gallery['image'].".jpg";
						$widhtHeight = "width:200px; height:150px;";
					}
?>
			<tr>
				<td bgcolor="#eeeeee"><input type="checkbox" name="selected_gal_id_<?=$galleryId?>" checked id="gallery_id_<?=$galleryId?>" value="selected_gal_id_<?=$galleryId?>"></td>
				<td bgcolor="#eeeeee"><?php if ($site_galleries_flag) echo $gallery['local_id']; else echo ++$gCounter;?></td>
				<td bgcolor="#eeeeee" style="font-weight: bold; text-align: right;"><a onmouseover="over('<?=$thumbURL?>')" onmousemove="move(event)" onmouseout="out()" href="index.php?act=galleries&amp;galid=<?=$galleryId?><?php if ($site_galleries_flag) { ?>&amp;site_id=<?=$site_id?>&amp;local_gal_id=<?=$gallery['local_id']?><?php } ?>"><?=$galleryId?></a></td>
				<td bgcolor="#eeeeee"><?=$gallery['url']?></td>
				<td bgcolor="#eeeeee"><?=$gallery['title']?></td>
				<td bgcolor="#eeeeee"><?=$gallery['niche']?></td>
				<td bgcolor="#eeeeee"><?=$gallery['type']?></td>
				<td bgcolor="#eeeeee"><?=$gallery['affiliate']?></td>				
				<td bgcolor="#eeeeee"><?=$gallery['paysite']?></td>
				<td bgcolor="#eeeeee"><?=$gallery['count']?></td>
				<td bgcolor="#eeeeee"><?php if ($site_galleries_flag) echo date("Y-m-d", $gallery['added']); else echo $gallery['added'];?></td>
				<td bgcolor="#eeeeee">
					<?php 
					if ($site_galleries_flag) { ?>
						<a onclick="return confirm('Точно удалить галеру #<?=$galleryId?> с сайта #<?=(int)$_REQUEST['site']?>');" target="_blank" href="index.php?act=galleries&amp;site=<?=(int)$_REQUEST['site']?>&amp;remove_gallery=<?=$galleryId?>"><b><red>X</red></b></a>
					<?php	
					} else {
						echo $gallery['status'];
					}
					?>
					
				</td>
				<td bgcolor="#eeeeee"><?php 
					if ($site_galleries_flag) {
						// $site_id = intval($_GET['site']);
						// echo $cache_worker->server_getGalleryPageviews($site_id, $gallery['local_id']);
						echo $gallery['pageviews'] . "|". $gallery['likes']. "|". $gallery['rating'];
					} else {
						if ($gallery['status'] == 'fetching_fail' || $gallery['status'] == 'unzip_fail') echo "<a href=\"index.php?act=galleries&amp;reset_status=".$galleryId."\">reset</a>";
						if ($gallery['status'] == 'fetched' || $gallery['status'] == 'screened' ) echo "<a href=\"index.php?act=grabber&amp;galid=".$galleryId."\">try grab</a>";
						elseif ($gallery['status'] == 'video_fail') echo "<a href=\"index.php?act=galleries&amp;reset_video_fail=".$galleryId."\">reset</a>";
						elseif ($gallery['status'] == 'new') echo "<a href=\"index.php?act=grabber&amp;galid=".$galleryId."\">grab</a>";
					}
				?></td>
<?php 			if($use_vcdn) { ?>
					<td width=20 bgcolor="#c0c0c0"><font color=<?=$gallery['cdn_synced'] ? "green>да" : "red>нет"?></font></td>
<?php 			} ?>				
			</tr>				
<?php
				}
			}
?>
			</table>
	</form>			
<?php
			}
			$queryString = $_SERVER['SCRIPT_NAME'] . "?act=galleries". $sortQueryString . $pagingSortQueryString . $searchAddition;
			$queryString = preg_replace('(\&offset=[0-9].*)', '',$queryString);
?>
			<div style="clear: both;"></div>
<?php
			for ($i=1; $i<=$pages; $i++) {
				if ($i !== $currentPage) {
?>
					<a href="<?=$queryString?>&amp;offset=<?=$i?>"><?=$i?></a>&nbsp;|&nbsp;
<?php					
				} else echo " ".$i. " | ";
			}
	} 
}
?>