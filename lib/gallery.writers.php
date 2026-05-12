<?php
		$writer_query = new WritersQuery();

		if(isset($_POST['edit-gallery'])) {
			// var_dump($_POST);
			if (isset($_POST['gal_id'], $_POST['site_id'], $_POST['title_id'], $_POST['title'])
				&& $_POST['title'] && $_POST['site_id'] && $_POST['gal_id'] && $_POST['title_id']) {
				$gal_id = intval($_POST['gal_id']);
				$site_id = intval($_POST['site_id']);
				$title_id = intval($_POST['title_id']);
				$title = $_POST['title'];
				$writer_id = $user_id;

				// var_dump($title);
				
				$writer_query->setGalleryReady($title_id, $gal_id, $site_id, $title, $writer_id);
				// добавить тайтлометод в юзеров
				//$userAuth->userApprovedGallery($user_id, $_galleryId);
			} else {
				echo "Script error!";
				$log = new Logger("Ошибка при добавлении тайтла, отсутствуют параметры!", true);
			}
		}

		$galleryId = false;
		$gal_id = false;
		$site_id = false;
		$title_id = false;
		$title = false;
		$gallery = false;

		if (!isset($user_language)) $user_language = false;

		$letters_today = $writer_query->lettersDoneToday($user_id);
		$galleries_today = $writer_query->galleriesDoneToday($user_id);
		$words_today = $writer_query->wordsDoneToday($user_id);

		if(!$letters_today) $letters_today = 0;
		if(!$galleries_today) $galleries_today = 0;
		if(!$words_today) $words_today = 0;
?>
	<div style="margin 10px; padding: 10px; float: left;">
		Today stats: <b><?="Galleries: ".$galleries_today.", Words: ".$words_today.", Letters:".$letters_today ?></b>
	</div>
	<script>
	function placeCursorAtEnd() {
	  if (this.setSelectionRange) {
	    // Double the length because Opera is inconsistent about 
	    // whether a carriage return is one character or two.
	    var len = this.value.length * 2;
	    this.setSelectionRange(len, len);
	  } else {
	    // This might work for browsers without setSelectionRange support.
	    this.value = this.value;
	  }

	  if (this.nodeName === "TEXTAREA") {
	    // This will scroll a textarea to the bottom if needed
	    this.scrollTop = 999999;
	  }
	};

	window.onload = function() {
	  var input = document.getElementById("gallery_title");

	  if (obj.addEventListener) {
	    obj.addEventListener("focus", placeCursorAtEnd, false);
	  } else if (obj.attachEvent) {
	    obj.attachEvent('onfocus', placeCursorAtEnd);
	  }

	  input.focus();
	}
	</script>
	<div style="clear:both"></div>
	<hr style="height: 4px;">
<?php		
		$gallery_to_work = $writer_query->popGallery($user_language);
		// var_dump($gallery_to_work, $user_language);

		if ($gallery_to_work) {
			$galleryId = $gallery_to_work['gal_id'];
			$site_id = $gallery_to_work['site_id'];
			$title_id = $gallery_to_work['id'];
			$site_keywords = $gallery_to_work['keywords'];
			$gallery = $default->NewGetGalleryInfo($galleryId);

			if ($gallery !== FALSE && isset($gallery['id'])) {
		
				// $userAuth->updateWorkingTable($user_id, $galleryId, 'tags');
				$models = new CModels($db->_db);
?>
				<form name=stats enctype="multipart/form-data" action="tags.php?writer=1" method="post">
					<div style="display: none;">
						<input name="gal_id" size="45" value="<?=$galleryId?>">
						<input name="site_id" size="45" value="<?=$site_id?>">
						<input name="title_id" size="45" value="<?=$title_id?>">
					</div>
					
					<script type="text/javascript" language="Javascript">
						function check_gallery_ok() {
							var result = false;
							var title_node = document.getElementById('gallery_title');
							var title = title_node.value;
							if (title.length >= 150) {
								alert ("Title is too long! It should be less than 150 letters!");
								return false;
							} else if (title.length < 20) {
								alert ('Title is to short, it should be at least 20+ symbols');	
								return false;
							}
							return true;
						}
					</script>
					

					<div style="width: 100%; margin-top: 50px; height: 30px; display: block-inline; float: left: margin: 3px;">
						<div style="font-size: 17px; float: left;">Main title: <b><?=htmlspecialchars($gallery['title'])?></b></div>
						<div style="clear:both"></div>
						<?php if ($gallery['description'] && $gallery['description'] != "") { ?> 
						<div style="font-size: 17px; float: left;">Available desc: <b><?=htmlspecialchars($gallery['description'])?></b></div>
						<div style="clear:both"></div>
						<?php } ?>
						<hr style="height: 1px;">
						<div style="float: left;">
							<div style="font-size: 16px; width:70px; padding: 3px; margin: 3px; float: left; text-align: left;">
								Write title:
							</div>
							<input style="font-size: 17px; margin: 4px;" name="title" id="gallery_title" size="110" value="" onkeyup="chageLength(this.value,this.name)" autofocus  spellcheck="true">
						</div>
						<div id="titleL" style="margin-left:10px; margin-top: 4px; padding: 6px; float: left; background: #e4e4e4;">0</div>
					</div>
					<div style="clear:both"></div>
					<div style="float: right;">
						<input type="submit" value="Approve and go to the next" name="edit-gallery" id="<?=$galleryId?>" onclick="return check_gallery_ok();" />
					</div>
					<div style="clear:both"></div>
					<hr>

<?php 				if($galleryModels = $models->getGalleryModels($gallery['id'])) { 	?>						
						<div id="models1s" style="width: 90%; height: auto; display: block-inline; margin: 3px; margin-left: 30px; padding:10px; border: 1px #000 solid; display: block-inline;">
<?php 						foreach ($galleryModels as $modelId) {
								if ($models->switchModel($modelId)) {
									$thumbURL =	HOSTING."/thumbs/p/150/". folderNameById($models->getPicture()) ."/".$models->getPicture() .".jpg";
?>									<dd id='modelDD<?=$modelId?>' style="font-size: 15px; float: left;">
										<img src="<?=$thumbURL?>"><br />
										<?=$models->getName()?>
									</dd>
<?php							}
							}		?>
							<div style="clear:both"></div>
						</div>
<?php				} 				?>					

					
					<style type="text/css">
					<!--
					.DragContainer, .OverDragContainer {
						float: left;
						margin: 3px;
						width: 1306px;
						border: #669999 2px solid;
						padding: 5px;
					}
					.DragBox, .OverDragBox, .DragDragBox, .miniDragBox {
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
					.OverDragBox, .DragDragBox {
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
					<div class="DragContainer" id="DragContainer1"> 
						<div class="DragBox" style="border: 1px #fff; background-color: #fff;">TAGS: </div>
<?php					if (is_array($gallery['tags']['id'])) {
							foreach ($gallery['tags']['id'] as $key => $tag_id) { 
								if ($tag_id) { ?>
								<div class="DragBox"><?=$gallery['tags']['name'][$key]?></div> 			
<?php							}
							}
						}
?>					
					</div> 
					<div style="clear:both"></div>
<?php 			if (isset($gallery['images'])) {
					if ($gallery['type'] == 'Pics') {
						$picsCount = $gallery['contentCount'];
						$thumbClass = 'thumb';
						$style_add = "width: 180px; height: 240px;";
						$thumbUrlPre = HOSTING . "/thumbs/p/180";
					} elseif ($gallery['type'] == 'gif') {
						$picsCount = $gallery['contentCount'];
						$thumbClass = 'thumb-gif';
						$thumbUrlPre = HOSTING;
					} else {
						$picsCount = count($gallery['images']['thumbs']);
						$thumbClass = 'thumb-movies';
						$style_add = "width: 212px; height: 159px;";
						$thumbUrlPre = HOSTING . "/thumbs/m/240";
					}

					if (isset($gallery['images']['thumbs']) && is_array($gallery['images']['thumbs'])) {
						foreach ($gallery['images']['thumbs'] as $thumbId => $thumbURL) {
							$folder = folderNameById($thumbId);
							$thumbURL = $thumbUrlPre ."/". $folder ."/".$thumbId.".jpg";
							$origImageURL = HOSTING ."/". $gallery['images']['url'][$thumbId];
?>
							<div class="<?=$thumbClass?>" style="<?=$style_add?> border: 0;">
								<img style="<?=$style_add?>;" src="<?php if($gallery['type'] == 'gif') echo $origImageURL; else echo $thumbURL;?>"><br />
								<div style="clear:both"></div>	
							</div>
<?php
						}
					}
?>
					<div style="clear:both"></div>
<?php			} 								?>
					<div style="float: right;">
						<input type="submit" value="Approve and go to the next" name="edit-gallery" id="<?=$galleryId?>" onclick="return check_gallery_ok();" />
					</div>
					<div style="clear:both"></div>
				</form>
<?php
			} else { echo "Gallery not found<br />\n\r"; }
		} else {  echo "Gallery not found. 0 galleries in a query<br />\n\r";  }
?>