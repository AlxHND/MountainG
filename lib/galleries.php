<?php

if (!function_exists('galleries_list_h')) {
	function galleries_list_h($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('galleries_list_lower')) {
	function galleries_list_lower($value)
	{
		$value = (string)$value;
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($value, 'UTF-8');
		}
		return strtolower($value);
	}
}

if (!function_exists('galleries_build_page_url')) {
	function galleries_build_page_url(array $params, $page)
	{
		$params['offset'] = max(1, (int)$page);
		return $_SERVER['SCRIPT_NAME'] . '?' . http_build_query($params);
	}
}

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
			<div style="display:block; width:1400px; max-width:100%; text-align:left; font-size:12px; margin:15px auto; padding:10px 12px; background:#f7f8fb; border:1px solid #d8deea; box-sizing:border-box;">
				Галер в (uploaded|OK) : <b><?= $gallery_worker->getReadyGalleriesCount() ?></b>
				| Галер с фейл статусами (ошибки) : <b><?= $gallery_worker->getFailedGalleriesCount() ?></b>
				| Галер в процессинге (вероятные залипы) : <b><?= $gallery_worker->getProcessingGalleriesCount() ?></b>
				| Галер с горизонтальным ресайзом : <b><?= $gallery_worker->getResizedGalleriesCount() ?></b> и без: <b><?= $gallery_worker->getNotResizedGalleriesCount() ?></b> (только Pics)

			</div>
			<hr>
			<div style="display:block; width:1400px; max-width:100%; text-align:left; font-size:12px; margin:15px auto; padding:10px 12px; background:#f7f8fb; border:1px solid #d8deea; box-sizing:border-box;">
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
		<style type="text/css">
			.galleries-panel {
				width: 1400px;
				max-width: 100%;
				margin: 0 auto;
				text-align: left;
				font-size: 13px;
			}

			.galleries-controls {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				align-items: center;
				margin: 14px 0 12px;
				padding: 12px;
				background: #f7f8fb;
				border: 1px solid #d8deea;
				box-sizing: border-box;
			}

			.galleries-controls select,
			.galleries-controls input[type="text"],
			.galleries-search-row select,
			.galleries-search-row input[type="text"],
			.galleries-bulk-bar select,
			.galleries-bulk-bar input[type="text"],
			.galleries-pagination-jump input[type="number"] {
				height: 36px;
				padding: 0 10px;
				border: 1px solid #bfc7d6;
				box-sizing: border-box;
				background: #fff;
				font-size: 13px;
			}

			.galleries-controls input[type="submit"],
			.galleries-search-row input[type="submit"],
			.galleries-bulk-bar input[type="submit"],
			.galleries-pagination-jump button {
				height: 36px;
				padding: 0 14px;
				border: 1px solid #244db3;
				background: #2d5bd1;
				color: #fff;
				box-sizing: border-box;
				font-size: 13px;
				cursor: pointer;
			}

			.galleries-controls input[type="submit"]:hover,
			.galleries-search-row input[type="submit"]:hover,
			.galleries-bulk-bar input[type="submit"]:hover,
			.galleries-pagination-jump button:hover {
				background: #244db3;
			}

			.galleries-controls-main {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				align-items: center;
				width: 100%;
			}

			.galleries-control-group {
				display: flex;
				align-items: center;
				gap: 6px;
				padding: 0;
				background: #fff;
				border: 0;
			}

			.galleries-control-label {
				color: #5d6678;
				font-size: 11px;
				white-space: nowrap;
			}

			.galleries-control-group select {
				min-width: 120px;
			}

			.galleries-control-group-wide select {
				min-width: 180px;
				max-width: 260px;
			}

			.galleries-controls-link {
				margin-left: auto;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				height: 36px;
				padding: 0 14px;
				background: #2d5bd1;
				border: 1px solid #244db3;
				white-space: nowrap;
				box-sizing: border-box;
			}

			.galleries-controls-link a {
				color: #fff;
				text-decoration: none;
				font-weight: bold;
			}

			.galleries-controls-link:hover {
				background: #244db3;
			}

			.galleries-search-row {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				align-items: center;
				margin: 0 0 12px;
				padding: 12px;
				background: #f7f8fb;
				border: 1px solid #d8deea;
				box-sizing: border-box;
			}

			.galleries-server-search {
				flex: 1 1 520px;
				min-width: 320px;
			}

			.galleries-live-search {
				flex: 1 1 240px;
				min-width: 220px;
			}

			.galleries-bulk-bar {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				align-items: center;
				margin: 0 0 12px;
				padding: 12px;
				background: #f7f8fb;
				border: 1px solid #d8deea;
				box-sizing: border-box;
			}

			.galleries-bulk-left {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				align-items: center;
				flex: 1 1 auto;
				min-width: 320px;
			}

			.galleries-bulk-right {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				align-items: center;
				margin-left: auto;
				padding-left: 14px;
				border-left: 1px solid #d8deea;
			}

			.galleries-bulk-caption {
				color: #5d6678;
				font-size: 12px;
				white-space: nowrap;
			}

			.galleries-table-wrap {
				border: 1px solid #d8deea;
				background: #fff;
				overflow-x: auto;
			}

			.galleries-table {
				width: 100%;
				min-width: 1460px;
				border-collapse: collapse;
				background: #fff;
			}

			.galleries-table th,
			.galleries-table td {
				padding: 9px 10px;
				border-bottom: 1px solid #e6eaf2;
				vertical-align: top;
				font-size: 12px;
			}

			.galleries-table th {
				background: #f2f5fa;
				color: #223;
				font-weight: bold;
				white-space: nowrap;
			}

			.galleries-table tbody tr:hover {
				background: #fafcff;
			}

			.galleries-table-url,
			.galleries-table-title {
				word-break: break-word;
				text-align: left;
			}

			.galleries-table-affiliate,
			.galleries-table-paysite,
			.galleries-table-status,
			.galleries-table-actions,
			.galleries-table-vcdn {
				white-space: nowrap;
			}

			.galleries-table-affiliate-text,
			.galleries-table-paysite-text {
				display: block;
				max-width: 100%;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			.galleries-table-status,
			.galleries-table-actions,
			.galleries-table-vcdn {
				font-size: 11px;
			}

			.galleries-table-id {
				width: 32px;
				min-width: 32px;
				text-align: right;
				white-space: nowrap;
			}

			.galleries-table-checkbox {
				width: 28px;
				min-width: 28px;
				text-align: center;
				padding-left: 6px;
				padding-right: 6px;
			}

			.galleries-table-checkbox input[type="checkbox"] {
				margin: 0;
			}

			.galleries-summary {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin: 8px 0 10px;
				color: #444;
			}

			.galleries-empty {
				padding: 18px 12px;
				color: #666;
				text-align: center;
			}

			.galleries-muted {
				color: #6f7787;
				font-size: 11px;
			}

			.galleries-thumbs-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
				gap: 12px;
				margin-bottom: 12px;
			}

			.galleries-thumb-card {
				display: block;
				padding: 10px;
				background: #fff;
				border: 1px solid #d8deea;
				color: #222;
				text-decoration: none;
				box-sizing: border-box;
			}

			.galleries-thumb-card:hover {
				background: #fafcff;
				border-color: #c7d4ea;
			}

			.galleries-thumb-preview {
				display: flex;
				align-items: center;
				justify-content: center;
				min-height: 160px;
				padding: 6px;
				background: #f4f6fa;
				border: 1px solid #dfe5ef;
				margin-bottom: 10px;
			}

			.galleries-thumb-preview img {
				display: block;
				max-width: 100%;
				max-height: 230px;
				border: 1px solid #111;
				background: #fff;
			}

			.galleries-thumb-title {
				margin-bottom: 6px;
				font-weight: bold;
				font-size: 13px;
				line-height: 1.35;
				word-break: break-word;
			}

			.galleries-thumb-meta {
				margin-bottom: 4px;
				color: #485163;
				font-size: 12px;
				line-height: 1.35;
				word-break: break-word;
			}

			.galleries-thumb-submeta {
				color: #6f7787;
				font-size: 11px;
				line-height: 1.35;
			}

			.galleries-pagination {
				display: flex;
				flex-wrap: wrap;
				gap: 12px;
				align-items: center;
				justify-content: space-between;
				margin: 14px 0 0;
				padding: 12px;
				background: #f7f8fb;
				border: 1px solid #d8deea;
				box-sizing: border-box;
			}

			.galleries-pagination-links {
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
				align-items: center;
			}

			.galleries-pagination-link,
			.galleries-pagination-current,
			.galleries-pagination-ellipsis {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 32px;
				height: 32px;
				padding: 0 10px;
				box-sizing: border-box;
			}

			.galleries-pagination-link {
				background: #fff;
				border: 1px solid #d8deea;
				color: #2d5bd1;
				text-decoration: none;
			}

			.galleries-pagination-link:hover {
				background: #f0f5ff;
			}

			.galleries-pagination-current {
				background: #2d5bd1;
				border: 1px solid #244db3;
				color: #fff;
				font-weight: bold;
			}

			.galleries-pagination-ellipsis {
				color: #6f7787;
			}

			.galleries-pagination-jump {
				display: flex;
				flex-wrap: wrap;
				gap: 8px;
				align-items: center;
				margin: 0;
				padding: 0;
				background: transparent;
				border: 0;
			}

			.galleries-pagination-jump input[type="number"] {
				width: 88px;
			}

			.galleries-pagination-jump button {
				min-width: 92px;
			}
		</style>
		<div class="galleries-panel">
		<form name=selector id="galleryselector" action="<?=$_SERVER['SCRIPT_NAME'] ?>?act=galleries" method="post">
				<div class="galleries-controls">
					<div class="galleries-controls-main">
						<div class="galleries-control-group">
							<select name="type" id="type" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="all">Тип: все</option>
								<option value="Movies">Movies</option>
								<option value="Pics">Pics</option>
							</select>
						</div>
						<div class="galleries-control-group">
							<select name="niche" id="niche" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="all">Ниша: все</option>
								<?php $default->AllNichesToString("<option value=\"#NICHE#\">#NICHE#</option>"); ?>
							</select>
						</div>
						<div class="galleries-control-group galleries-control-group-wide">
							<select name="paysite" id="paysite" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="all">Платник: все</option>
								<?php if (isset ($_GET['details']) && $_GET['details'] == 'yes') echo $default->AllPaysitesToString ("<option value=\"#PAYSITE_ID#\">#PAYSITE#, галер: #GALLERIES_COUNT#, последний апдейт: #LAST_UPDATE#</option>", 'uploaded', 'Pics'); 
									else $sources->listSourcesGals ("<option value=\"#PAYSITE_ID#\">#PAYSITE# - (#GALLERIES_COUNT#)</option>", $status);  ?>
							</select>
						</div>
						<div class="galleries-control-group galleries-control-group-wide">
							<select name="category" id="category" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="all">Категория: все</option>
								<?php $default->AllTagsToString ("<option value=\"#TAG_ID#\">#TAG#</option>"); ?>
							</select>
						</div>
						<div class="galleries-control-group galleries-control-group-wide">
							<select name="status" id="status" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="all">Статус: все</option>
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
						</div>
						<div class="galleries-control-group">
							<select name="sort" id="sort" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="id">Сортировка: ID</option>
								<option value="title">title</option>
								<option value="date">date</option>
								<option value="paysite">paysite</option>
								<option value="niche">niche</option>
								<option value="pics">pics</option>
								<option value="status">status</option>
								<option value="likes">likes</option>
								<option value="pageviews">pageviews</option>
							</select>
						</div>
						<div class="galleries-control-group">
							<select name="order" id="order" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="asc">Порядок: A-Z</option>
								<option value="desc">Порядок: Z-A</option>
							</select>
						</div>
						<div class="galleries-control-group">
							<select name="num" id="num" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="50">На стр.: 50</option>
								<option value="100">100</option>
								<option value="150">150</option>
								<option value="200">200</option>
								<option value="250">250</option>
								<option value="300">300</option>
							</select>
						</div>
						<div class="galleries-control-group">
							<select name="thumbs" id="thumbs" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
								<option value="false"<?php if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'false') { ?> selected='selected' <?php }?>>Тумбы: нет</option>
								<option value="true"<?php if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') { ?> selected='selected' <?php }?>>Тумбы: да</option>
							</select>
						</div>

						<div id="searchResult" class="galleries-controls-link">
							<a href="<?=$main_query_string?>">Выбрать</a>
						</div>
					</div>
				</div>
			<hr>

			<div class="galleries-search-row">
				<input type="text" class="galleries-server-search" name="search" id="search" value="<?=$search?>" onblur="if(this.value=='') this.value='искать...';" onfocus="if(this.value=='искать...') this.value='';" style="background-color: #f9f9f9;">
				 по
				 <select name="searchby" id="searchby">
						<option value="title">Тайтлу</option>
						<option value="desc">Деску</option>
						<option value="titledesc">Тайтл+Деску</option>
						<option value="url">Урлу</option>
				</select>
				<input type="submit" value="Показать галеры" name="searchGalleries" onclick="searchBoxGalleryThumbs();"/>
			</div>
		</form>	

<?php
			if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') {
?>
			<div class="galleries-summary">
				<div>Строк в текущей выборке: <strong id="galleries-visible-count"><?= is_array($galleries) ? count($galleries) : 0 ?></strong></div>
				<div class="galleries-muted">Локальные фильтры работают только по текущей странице</div>
			</div>
			<div class="galleries-thumbs-grid" id="galleries-thumbs-grid">
<?php
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
					$galleryTitleFilter = galleries_list_h(galleries_list_lower(isset($gallery['title']) ? $gallery['title'] : ''));
					$galleryPaysiteFilter = galleries_list_h(galleries_list_lower(isset($gallery['paysite']) ? $gallery['paysite'] : ''));
?>
			        <a href="index.php?act=galleries&amp;galid=<?=$galleryId?>" class="galleries-thumb-card galleries-row" data-title="<?=$galleryTitleFilter?>" data-paysite="<?=$galleryPaysiteFilter?>">
			        	<div class="galleries-thumb-preview">
			          		<img src="<?=$thumbURL?>" alt="<?=galleries_list_h($gallery['title'])?>" style="<?=$widhtHeight?>">
			          	</div>
			          	<div class="galleries-thumb-title"><?=$gallery['title'] ? $gallery['title'] : 'Без названия'?></div>
			          	<div class="galleries-thumb-meta"><?=$gallery['paysite']?> | <?=$gallery['affiliate']?></div>
			          	<div class="galleries-thumb-meta"><?=$gallery['type']?> | <?=$gallery['niche']?> | Pics: <?=$gallery['count']?></div>
			          	<div class="galleries-thumb-submeta">Добавлена: <?=$gallery['added']?> | ID: <?=$galleryId?></div>
			        </a>
<?php
				}
				if (!is_array($galleries) || count($galleries) === 0) {
?>
				<div id="galleries-empty-row" class="galleries-empty" style="grid-column: 1 / -1;">Галереи по текущему фильтру не найдены.</div>
<?php
				}
?>
			</div>
<?php				
			} else {
?>
	<form name="galleries_block" id="galleries_block" action="<?=$_SERVER['SCRIPT_NAME'] ?>?act=galleries" method="post">
			<div class="galleries-bulk-bar">
				<div class="galleries-bulk-left">
					<input type="text" id="galleries-live-title-search" class="galleries-live-search" placeholder="Фильтр по title..." autocomplete="off" />
					<input type="text" id="galleries-live-paysite-search" class="galleries-live-search" placeholder="Фильтр по paysite..." autocomplete="off" />
				</div>
				<div class="galleries-bulk-right">
					<span class="galleries-bulk-caption">Выбранные галеры:</span>
					<select name="selected_action" id="selected_action">
						<option value="no">Ничего</option>
						<option value="delete">Удалить</option>
<?php if($status == 'fetching_fail') { ?>
						<option value="reset_to_new">Сбросить ошибку в New</option>
<?php } ?>
					</select>
					<input type="submit" value="Применить" name="delete_button"  onclick="return confirm('Применить?');" />
				</div>
			</div>
			<div class="galleries-summary">
				<div>Строк в текущей выборке: <strong id="galleries-visible-count"><?= is_array($galleries) ? count($galleries) : 0 ?></strong></div>
				<div class="galleries-muted">Локальные фильтры работают только по текущей странице</div>
			</div>
			<div class="galleries-table-wrap">
			<table align="center" class="galleries-table" id="galleries-table">
			<tbody>
			<tr>
				<th class="galleries-table-checkbox"><input type="checkbox" id="galleries-select-all" title="Выбрать все / снять выделение"></th>
				<th width=32><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=id&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">ID</a></th>
				<th width=360><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=url&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">URL</a></th>
				<th width=220><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=title&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Title</a></th>
				<th width=44><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=niche&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Niche</a></th>
				<th width=34>Type</th>
				<th width=92>Affil.</th>
				<th width=108><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=paysite&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Paysite</a></th>
				<th width=42><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=pics&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Pics</a></th>
				<th width=72><a href="index.php?act=galleries<?=$sortQueryString?>&amp;sort=date&amp;order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Added</a></th>
				<th width=72>
					<?php 
					if ($site_galleries_flag) { ?>
						Удалить
					<?php	
					} else { ?>
						<a href="index.php?act=galleries<?=$sortQueryString?>&sort=status&order=<?php if (isset($_GET['order']) && $_GET['order'] == 'asc') echo "desc"; else echo "asc"?>">Status</a>
<?php				} ?>
				</th>
				<th width=34></th>
<?php 			if($use_vcdn) { ?>
					<th width=18>vCDN</th>
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
					$galleryTitleFilter = galleries_list_h(galleries_list_lower(isset($gallery['title']) ? $gallery['title'] : ''));
					$galleryPaysiteFilter = galleries_list_h(galleries_list_lower(isset($gallery['paysite']) ? $gallery['paysite'] : ''));
?>
			<tr class="galleries-row" data-title="<?=$galleryTitleFilter?>" data-paysite="<?=$galleryPaysiteFilter?>">
				<td class="galleries-table-checkbox"><input type="checkbox" name="selected_gal_id_<?=$galleryId?>" id="gallery_id_<?=$galleryId?>" value="selected_gal_id_<?=$galleryId?>"></td>
				<td class="galleries-table-id" style="font-weight: bold;"><a onmouseover="over('<?=$thumbURL?>')" onmousemove="move(event)" onmouseout="out()" href="index.php?act=galleries&amp;galid=<?=$galleryId?><?php if ($site_galleries_flag) { ?>&amp;site_id=<?=$site_id?>&amp;local_gal_id=<?=$gallery['local_id']?><?php } ?>"><?=$galleryId?></a></td>
				<td class="galleries-table-url"><?=$gallery['url']?></td>
				<td class="galleries-table-title"><?=$gallery['title']?></td>
				<td><?=$gallery['niche']?></td>
				<td><?=$gallery['type']?></td>
				<td class="galleries-table-affiliate" title="<?=galleries_list_h($gallery['affiliate'])?>"><span class="galleries-table-affiliate-text"><?=$gallery['affiliate']?></span></td>				
				<td class="galleries-table-paysite" title="<?=galleries_list_h($gallery['paysite'])?>"><span class="galleries-table-paysite-text"><?=$gallery['paysite']?></span></td>
				<td><?=$gallery['count']?></td>
				<td><?php if ($site_galleries_flag) echo date("Y-m-d", $gallery['added']); else echo $gallery['added'];?></td>
				<td class="galleries-table-status">
					<?php 
					if ($site_galleries_flag) { ?>
						<a onclick="return confirm('Точно удалить галеру #<?=$galleryId?> с сайта #<?=(int)$_REQUEST['site']?>');" target="_blank" href="index.php?act=galleries&amp;site=<?=(int)$_REQUEST['site']?>&amp;remove_gallery=<?=$galleryId?>"><b><red>X</red></b></a>
					<?php	
					} else {
						echo $gallery['status'];
					}
					?>
					
				</td>
				<td class="galleries-table-actions"><?php 
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
					<td width=20 class="galleries-table-vcdn"><font color=<?=$gallery['cdn_synced'] ? "green>да" : "red>нет"?></font></td>
<?php 			} ?>				
			</tr>				
<?php
				}
			}
?>
<?php if (!is_array($galleries) || count($galleries) === 0) { ?>
			<tr id="galleries-empty-row">
				<td colspan="<?=$use_vcdn ? 12 : 11?>" class="galleries-empty">Галереи по текущему фильтру не найдены.</td>
			</tr>
<?php } ?>
			</tbody>
			</table>
			</div>
	</form>			
<?php
			}
			$paginationBaseParams = array('act' => 'galleries');
			if (isset($_REQUEST['site']) && (int)$_REQUEST['site'] > 0) {
				$paginationBaseParams['site'] = (int)$_REQUEST['site'];
			}
			if ($type) {
				$paginationBaseParams['type'] = $type;
			}
			if ($niche) {
				$paginationBaseParams['niche'] = $niche;
			}
			if ($paysite) {
				$paginationBaseParams['paysite'] = $paysite;
			}
			if ($category) {
				$paginationBaseParams['category'] = $category;
			}
			if ($status) {
				$paginationBaseParams['status'] = $status;
			}
			if ($sort) {
				$paginationBaseParams['sort'] = $sort;
			}
			if ($order) {
				$paginationBaseParams['order'] = $order;
			}
			if ($galsPerPage) {
				$paginationBaseParams['num'] = $galsPerPage;
			}
			if (isset($_REQUEST['thumbs'])) {
				$paginationBaseParams['thumbs'] = $_REQUEST['thumbs'] === 'true' ? 'true' : 'false';
			}
			if (isset($_GET['details']) && $_GET['details'] === 'yes') {
				$paginationBaseParams['details'] = 'yes';
			}
			if ($search && $search !== 'искать...') {
				$paginationBaseParams['search'] = $search;
				$paginationBaseParams['searchby'] = $searchBy;
			}

			$visiblePageLinks = 10;
			$halfWindow = (int)floor($visiblePageLinks / 2);
			$windowStart = max(1, $currentPage - $halfWindow);
			$windowEnd = $windowStart + $visiblePageLinks - 1;
			if ($windowEnd > $pages) {
				$windowEnd = $pages;
				$windowStart = max(1, $windowEnd - $visiblePageLinks + 1);
			}
?>
			<div style="clear: both;"></div>
			<?php if ($pages > 1) { ?>
			<div class="galleries-pagination">
				<div class="galleries-pagination-links">
					<?php if ($currentPage > 1) { ?>
						<a class="galleries-pagination-link" href="<?=galleries_list_h(galleries_build_page_url($paginationBaseParams, 1))?>"><<</a>
						<a class="galleries-pagination-link" href="<?=galleries_list_h(galleries_build_page_url($paginationBaseParams, $currentPage - 1))?>"><</a>
					<?php } ?>

					<?php if ($windowStart > 1) { ?>
						<span class="galleries-pagination-ellipsis">...</span>
					<?php } ?>

					<?php for ($i = $windowStart; $i <= $windowEnd; $i++) { ?>
						<?php if ($i === (int)$currentPage) { ?>
							<span class="galleries-pagination-current"><?=$i?></span>
						<?php } else { ?>
							<a class="galleries-pagination-link" href="<?=galleries_list_h(galleries_build_page_url($paginationBaseParams, $i))?>"><?=$i?></a>
						<?php } ?>
					<?php } ?>

					<?php if ($windowEnd < $pages) { ?>
						<span class="galleries-pagination-ellipsis">...</span>
					<?php } ?>

					<?php if ($currentPage < $pages) { ?>
						<a class="galleries-pagination-link" href="<?=galleries_list_h(galleries_build_page_url($paginationBaseParams, $currentPage + 1))?>">></a>
						<a class="galleries-pagination-link" href="<?=galleries_list_h(galleries_build_page_url($paginationBaseParams, $pages))?>">>></a>
					<?php } ?>
				</div>

				<form class="galleries-pagination-jump" action="<?=$_SERVER['SCRIPT_NAME']?>" method="get">
					<?php foreach ($paginationBaseParams as $paramKey => $paramValue) { ?>
						<input type="hidden" name="<?=galleries_list_h($paramKey)?>" value="<?=galleries_list_h($paramValue)?>">
					<?php } ?>
					<span class="galleries-muted">Стр. <?=$currentPage?> из <?=$pages?></span>
					<input type="number" name="offset" min="1" max="<?=$pages?>" value="<?=$currentPage?>">
					<button type="submit">Перейти</button>
				</form>
			</div>
			<?php } ?>
		</div>
		<script type="text/javascript">
			(function () {
				function setSelectValue(selectId, value) {
					var select = document.getElementById(selectId);
					if (!select || typeof value === 'undefined' || value === null) {
						return;
					}
					value = String(value);
					for (var i = 0; i < select.options.length; i += 1) {
						if (String(select.options[i].value) === value) {
							select.value = value;
							return;
						}
					}
				}

				setSelectValue('type', <?=json_encode($type ? $type : 'all')?>);
				setSelectValue('niche', <?=json_encode($niche ? $niche : 'all')?>);
				setSelectValue('paysite', <?=json_encode($paysite ? (string)$paysite : 'all')?>);
				setSelectValue('category', <?=json_encode($category ? (string)$category : 'all')?>);
				setSelectValue('status', <?=json_encode($status ? $status : 'all')?>);
				setSelectValue('sort', <?=json_encode($sort)?>);
				setSelectValue('order', <?=json_encode($order)?>);
				setSelectValue('num', <?=json_encode((string)$galsPerPage)?>);
				setSelectValue('thumbs', <?=json_encode((isset($_GET['thumbs']) && $_GET['thumbs'] === 'true') ? 'true' : 'false')?>);
				setSelectValue('searchby', <?=json_encode($searchBy)?>);

				var titleInput = document.getElementById('galleries-live-title-search');
				var paysiteInput = document.getElementById('galleries-live-paysite-search');
				var table = document.getElementById('galleries-table');
				var thumbsGrid = document.getElementById('galleries-thumbs-grid');
				if (!titleInput || !paysiteInput || (!table && !thumbsGrid)) {
					return;
				}

				var rows = table
					? Array.prototype.slice.call(table.querySelectorAll('tr.galleries-row'))
					: Array.prototype.slice.call(thumbsGrid.querySelectorAll('.galleries-row'));
				var selectAllCheckbox = document.getElementById('galleries-select-all');
				var countBlock = document.getElementById('galleries-visible-count');
				var emptyRow = document.getElementById('galleries-empty-row');

				function updateVisibleCount() {
					var visible = 0;
					rows.forEach(function (row) {
						if (row.style.display !== 'none') {
							visible += 1;
						}
					});
					if (countBlock) {
						countBlock.textContent = visible;
					}
					if (emptyRow) {
						emptyRow.style.display = visible === 0 ? '' : 'none';
					}
				}

				function applyFilters() {
					var titleQuery = titleInput.value.toLowerCase().trim();
					var paysiteQuery = paysiteInput.value.toLowerCase().trim();

					rows.forEach(function (row) {
						var titleValue = row.getAttribute('data-title') || '';
						var paysiteValue = row.getAttribute('data-paysite') || '';
						var titleMatches = titleQuery === '' || titleValue.indexOf(titleQuery) !== -1;
						var paysiteMatches = paysiteQuery === '' || paysiteValue.indexOf(paysiteQuery) !== -1;
						row.style.display = titleMatches && paysiteMatches ? '' : 'none';
					});

					updateVisibleCount();
				}

				function syncSelectAllState() {
					if (!selectAllCheckbox || !table) {
						return;
					}

					var visibleRows = rows.filter(function (row) {
						return row.style.display !== 'none';
					});

					if (visibleRows.length === 0) {
						selectAllCheckbox.checked = false;
						selectAllCheckbox.indeterminate = false;
						return;
					}

					var selectedCount = 0;
					visibleRows.forEach(function (row) {
						var checkbox = row.querySelector('input[type="checkbox"]');
						if (checkbox && checkbox.checked) {
							selectedCount += 1;
						}
					});

					selectAllCheckbox.checked = selectedCount === visibleRows.length;
					selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < visibleRows.length;
				}

				if (selectAllCheckbox) {
					selectAllCheckbox.addEventListener('change', function () {
						var shouldCheck = !!selectAllCheckbox.checked;
						rows.forEach(function (row) {
							if (row.style.display === 'none') {
								return;
							}
							var checkbox = row.querySelector('input[type="checkbox"]');
							if (checkbox) {
								checkbox.checked = shouldCheck;
							}
						});
						syncSelectAllState();
					});
				}

				rows.forEach(function (row) {
					var checkbox = row.querySelector('input[type="checkbox"]');
					if (checkbox) {
						checkbox.addEventListener('change', syncSelectAllState);
					}
				});

				titleInput.addEventListener('input', applyFilters);
				paysiteInput.addEventListener('input', applyFilters);
				applyFilters();
				syncSelectAllState();
			})();
		</script>
<?php
	} 
}
?>
