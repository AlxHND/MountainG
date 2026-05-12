<?php


		$fast_tags_array['Gay'] = array('anal', 'muscle', 'twinks', 'fetish', 'bears', 'mature', 'latino', 'studs', 'uncut', 'facial', 'groupsex', 'solo', 'black', 'pornstar', 'hunks');
		$fast_tags_array['Straight'] = array('anal', 'teens', 'hardcore', 'erotic', 'euro', 'small tits', 'babes', 'big tits', 'milf', 'pornstar', 'shaved','mature','hairy','stockings','latino', 'black','outdoors','uniform', 'amateurs','moms', 'groupsex', 'orgy', 'gangbang','russian','lesbians');
		$fast_tags_array['Shemales'] = array();

		$sites_tags_list = false;
		$tags_worker = false;
		$sortQueryString ="";
		$pagingSortQueryString = "";
		$search = false;
		$searchAddition = "";
		$currentPage = 1;
		$galsOffset = 0;
		$gCounter = 0;
		$status = 'uploaded';
		$type = false;
		$niche = false;
		$paysite = false;
		$use_all_tags = true;

		if (isset($_REQUEST['search']) && $_REQUEST['search'] !== "") {
			$search = $_REQUEST['search'];
			$searchAddition = "&search=".$search;
		}
		if (isset($_REQUEST['sort']) && (preg_match('/^(id|local_id|title|date|paysite|niche|pics|status|pageviews|likes|rating)$/', $_REQUEST['sort']))) {
			$sort = $_REQUEST['sort'];
			$pagingSortQueryString ="&sort=".$sort;
		} else {
			if(isset($_REQUEST['site']) && $_REQUEST['site']) $sort = 'local_id';
			else { $sort = 'id';}
			
		}
		if (isset($_REQUEST['order']) && (preg_match('/^(asc|desc)$/', $_REQUEST['order']))) {
			$order = $_REQUEST['order'];
			$sortQueryString .="&order=".$order;
		} else $order = 'asc';
		if (isset($_REQUEST['num']) &&  (int)$_REQUEST['num']) {
			$galsPerPage = (int)$_REQUEST['num'] ;
			$sortQueryString .= "&num=".$galsPerPage;	
		} else $galsPerPage = 20;
		if (isset($_REQUEST['category']) && intval($_REQUEST['category'])) {
			$category = $_REQUEST['category'];
			$sortQueryString .= "&category=".$category;
		} else $category = false;		
		if (isset($_REQUEST['order']) && (preg_match('/^(asc|desc)$/', $_REQUEST['order']))) {
			$order = $_REQUEST['order'];
			$sortQueryString .= "&order=".$order;
		} else $order = false;
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
			$galsOffset = ($galsOffset-1)*$galsPerPage;
			$gCounter = $galsOffset;

		}
		if (isset($_REQUEST['status']) && (preg_match('/^(zip|zipupload|new|grabbed|thumbs|uploaded|tagged|toregrab|OK|trash|delete|error|fetching_fail|video_fail|all_fails|all_ready|all_processing|all_delete)$/', $_REQUEST['status']))) {
			$status = $_REQUEST['status'];
			$sortQueryString .="&status=".$status;
		} 
		if (isset($_REQUEST['type']) && preg_match('/^(Pics|Movies)$/', $_REQUEST['type'])) {
			$type = $_REQUEST['type'];
			$sortQueryString .="&status=".$type;
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
		$main_query_string ="./index.php?act=galleries&tags=true&design_type=multi";

		$gallery_worker = new Galleries;

	$galleries = $gallery_worker->getGalleriesList($sort, $order, $galsPerPage, $galsOffset, $type, $paysite, $status, $search, $category, $searchBy, $niche);
	$_galleriesCount = $gallery_worker->getCurrentGlasCount();
?>
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
						<?php $default->AllNichesToString ("<option value=\"#NICHE#\">#NICHE#</option>"); ?>
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
						<option value="uploaded">Uploaded</option>						
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
					&nbsp;Per page: <select name="num" id="num" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
						<option value="50">50</option>
						<option value="100">100</option>
					</select>
					&nbsp;Тумбы: <select name="thumbs" id="thumbs" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
						<option value="false"<?php if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'false') { ?> selected='selected' <?php }?>>Нет</option>
						<option value="true"<?php if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') { ?> selected='selected' <?php }?>>Да</option>
					</select>					
					&nbsp;&nbsp;&nbsp;Порядок: <select name="order" id="order" onChange="searchOptionsSwitch('main',this.id,this.value,'<?=$main_query_string?>')">
						<option value="asc">A-Z</option>
						<option value="desc">Z-A</option>
					</select>					

				</div>	
				<div id="searchResult" class="searchResult" style="float:right;"><a href="<?=$main_query_string?>">Выбрать условия</a></div>
			</div>

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
	if($galleries) {
		foreach ($galleries as $galleryId => $gallery) {
?>
				<div class="thumb-own-info" id="<?=$galleryId?>" style="width: 1220px; height: auto; border: solid 2px #000; margin: 5px; margin-bottom: 50px; padding: 5px; display: block;">
					<div style="position: relative; width:50px; height:50px; padding: 0; right:-1200px; top: -30px; display: block-inline;"><img src="images/button.delete.big.png" onclick="galleryToTrash(<?=$galleryId?>)"></div>
					<div style="position: relative; float:left; width: 1200px; height: 180; margin:10px; display: block-inline; top: -50px;">
						<div style="float: left; margin: 2px; z-index: 99; width: 100%;">
							<div style="float: right;">
								<div onClick='galleryApprove(<?=$galleryId?>);' id="approve_<?=$galleryId?>" class="multi_approve_button">
									Аппрув галеры
								</div>
								<div onclick="galleryToTrash(<?=$galleryId?>)" class="multi_trash_button">
										Удалить галеру
								</div>
							</div>
							Gallery ID: <a href="index.php?act=galleries&amp;galid=<?=$galleryId?>"><b><?=$galleryId?></b></a>, 
							Tube: <b><?=$gallery['paysite']?></b>, 
							Link: <a href="<?=$gallery['url']?>"><?=$gallery['url']?></a><br />
							Added: <?=$gallery['added']?><br />
							<?php if ($user_type == 'admin') { ?>
								Статус: <?=$gallery['status']?><br />
								Длительность/пиксы: <?=$gallery['count']?><br />
							<?php } ?>
							<div id="title_<?=$galleryId?>" style="height:20px; margin-top: 8px; margin-bottom: 8px;">Title: <input size="40" name="upadate_title_<?=$galleryId?>" id="upadate_title_<?=$galleryId?>" type="text" value="<?=$gallery['title']?>" onkeyup="prechange_gallery_title(<?=$galleryId?>)"></div><br />
<?php 						if (preg_match('#(ok|uploaded)#im', $gallery['status'])) { ?>							
								<div style="float: left; font-size: 18px; margin: 10px; margin-left: 0px;margin-top:10px;">
									Tags:
								</div>
<?php								$procTags = $gallery_worker->getTags($galleryId, true);

									if(!$tags_worker) {
										$tags_worker = new Tags($db->_db);
									}
									if(!isset($sites_tags_list[$gallery['niche']])) {
										$sites_tags_list[$gallery['niche']] = $tags_worker->getAllTags($gallery['niche'], false, true);	
									}
									$use_fast_tags = array();
									$used_tags = array();
									$count = count($procTags);
									if (is_array($procTags)) {
							            foreach ($procTags as $tag_id => $tag_name) {
							            	if(in_array($tag_name, $fast_tags_array[$gallery['niche']])) {
							            		$used_tags[] = $tag_name;
							            	}
							            	?>
											<div id="gal_<?=$galleryId?>_tag_<?=$tag_id?>" class="tag">
												<div style="font-size: 18px; margin-top: 5px; margin-right: 4px; margin-left: 4px; width: auto; height: auto; float: left;">
													<?=$tag_name?>
												</div>
												<div style="margin-bottom: 5px; width: auto; height: auto; float: left;">
													<img src="images/button_red_minus.png" border=0 onclick="remove_gallery_tag_make(<?=$galleryId?>,<?=$tag_id?>);" />
												</div>
											</div>
<?php									}
									}
									$use_fast_tags = array_diff($fast_tags_array[$gallery['niche']], $used_tags);
									$fast_tags_block = false;
?>									<div>
								        <select id="add_tag_<?=$galleryId?>" name="add_tag_<?=$galleryId?>" style="float: left; font-size: 18px; height: 38px; margin: 5px; padding: 5px;">
               								<option value="0">No</option>
<?php
               								foreach ($sites_tags_list[$gallery['niche']] as $_tag_id => $_tag) {
               									if($use_all_tags) {
               										$fast_tags_block[] = '<div id="gal_'.$galleryId.'_add_tag_'.$_tag_id.'" class="catt" style="background-color: #666; color: #fff; font-size: 15px;" onClick="add_new_tag_fast('.$galleryId.','.$_tag_id.');">'.$_tag["name"].'</div>';
               									} else {
               										if(in_array($_tag['name'], $use_fast_tags)) {
	               										$fast_tags_block[] = '<div id="gal_'.$galleryId.'_add_tag_'.$_tag_id.'" class="catt" style="background-color: #666; color: #fff; font-size: 15px;" onClick="add_new_tag_fast('.$galleryId.','.$_tag_id.');">'.$_tag["name"].'</div>';
	               									}	
               									}
               									
?>
	                							<option value="<?=$_tag_id?>"><?=$_tag['name']?></option>
<?php              							}
?>
	              						</select>
	              						<div style="float: left; padding: 0; margin: 2px;  margin-top: 5px;display: block;"><img src="images/add_button_small.png" border=0 onclick="add_new_tag(<?=$galleryId?>);" /></div>
              						</div>
              						
									<br />
			          	</div>
					</div>


							
							<div style="clear: both;"></div>
							<div style="width: 100%; float: left; margin: 10px; display: block-inline; position: relative; top: -40px;">
<?php
							$thumbs = $gallery_worker->getAllImages($galleryId);
							
							if (is_array($thumbs)) {
								foreach ($thumbs as $thumbId => $th) {
									if ($gallery['type'] == 'Movies') {
										$thumbUrlPre = HOSTING . "/thumbs/m/".$rssMovieThumbs['small']['width']."/";
										$width = $rssMovieThumbs['small']['width'];
										$height = $rssMovieThumbs['small']['height'];
									} elseif ($gallery['type'] == 'horiz_thumbs') {
										$thumbUrlPre = HOSTING . "/thumbs/x300/";
										$width = 300;
										$height = "auto !important;";
									} else {
										$thumbUrlPre = HOSTING . "/thumbs/p/".$rssThumbSizes['small']['width']."/";	
										$width = $rssThumbSizes['small']['width'];
										$height = $rssThumbSizes['small']['height'];
									}

									if ($thumbId < 256000){
										$folderId = (int)ceil($thumbId/1000);
										$thumbUrlPre .= "1/".$folderId;
									} else {
										$mainFolder= (int)ceil($thumbId/256000);
										$folderId = (int)ceil($thumbId/1000);
										$thumbUrlPre .= $mainFolder."/".$folderId;
									}
								  ?>
								  <div id="thumb_<?=$thumbId?>" style="display: block; width:<?=$width?>px; height: <?=$height+20?>px; float: left; margin: 4px;">
					              	<img style='margin:2px; border: solid 1px #000;' width='<?=$width?>' height='<?=$height?>' onClick='if(confirm("Sure delete Image?")) { deleteThumb(<?=$galleryId?>, <?=$thumbId?>); }' src='<?=$thumbUrlPre."/".$thumbId.".jpg"?>' >
					              	<div style="float: right;">
					              		<img src="images/del.png" style="width:15px; height:15px;" border="0" onClick='deleteThumb(<?=$galleryId?>, <?=$thumbId?>);' />
					              	</div>
					              </div>
					              <?php
					          	}
							}
?>          
							</div>
							<?php if($fast_tags_block) { ?>
							<div style="width: 100%; float: left; margin: 10px; display: block-inline; position: relative; top: -40px;">
								<?php echo implode(" ", $fast_tags_block); ?>
							</div>
							<?php } ?>
							<div style="clear: both;"></div>							
							<div style="clear: both;"></div>
							<div onClick='galleryApprove(<?=$galleryId?>);' id="approve_<?=$galleryId?>" class="multi_approve_button">
								Аппрув галеры
							</div>
							<div onclick="galleryToTrash(<?=$galleryId?>)" class="multi_trash_button">
								Удалить галеру
							</div>
				</div>
<?php						}
		}
	} else {
?>
		<h1>No galleries found</h1>
<?php		
	}
?>
							<div style="clear: both;"></div>
