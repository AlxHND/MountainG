			<div class="thumb-own-info" id="<?=$galleryId?>" style="width: 1300px; height: auto; border: solid 2px #000; margin: 5px; margin-bottom: 50px; padding: 5px; display: block;">
				<script>var num = <?=$galleryId?>; galleries_array.push(num.toString());</script>
					<div style="float: right;">
						<img 
							src="images/del.png" 
							style="border: 0" 
							onclick="ExcludeAndHideGallery(<?=$site['id']?>, <?=$galleryId?>); getCurrentGalId();" 
							alt="Исключить галеру из очереди на добавление" 
							title="Исключить галеру из очереди на добавление"
						/>
					</div>
					<div style="display:block; position: relative; float: left;">
						<img 
							id="pick_thumb_<?=$galleryId?>" 
							src='<?=$thumb?>' 
							style="border: 2px solid #000;"
						/>
					</div>
					<div style="display:block; position: relative; float: left; z-index: 99;">
						<div style="font-size: 13px; position: relative; float:left; width: <?=$info_div_block_size?>px; min-height: 180; margin:10px; display: block-inline;"> 
								GID <a style="font-weight: bold;" href="index.php?act=galleries&amp;galid=<?=$galleryId?>" target="_blank"><?=$galleryId?></a>, 
								by: <b><?=$gallery['paysite']['name'] ?></b>, 
								Контент: <?=$gallery['contentCount'] . ($gallery['type'] == 'Movies' ? " сек" : " пикс")?>, 
								<b><?=$gallery['date']?></b>
								<br />
								Unique: <input type='checkbox' id='unique_<?=$galleryId?>'> | Была запощена на: <?php
									if ($gallery['posted'] && is_array($gallery['posted'])) {
										foreach ($gallery['posted'] as $posted_to_site_id) {
											?>
											<a href="index.php?act=sites&amp;site=<?=$posted_to_site_id?>" target="_blank"><b><?=$posted_to_site_id?></b></a>&nbsp;&nbsp;&nbsp;&nbsp;
											<?php
										}
									} else echo "Нигде";
								?>
								
								<?php 
								// var_dump($gallery['additional_titles']);
								if (isset($gallery['additional_titles']) && $gallery['additional_titles'] && is_array($gallery['additional_titles'])) { ?>
								<div style="width: 900px; height: 30px; display: block-inline; float: left; margin: 5px; border: 1px solid #000; padding: 4px; background-color: #ddd;">
									<select id="all_titles_select_<?=$galleryId?>" style="float: left;">
										<?php foreach ($gallery['additional_titles'] as $additional_title) { ?>
											<option value="<?=$additional_title['id']?>"><?=strtoupper($additional_title['language']).":".htmlspecialchars($additional_title['title'].($additional_title['used_on'] ? " Used on: ".$additional_title['used_on'] : ""))?></option>
										<?php } ?>
									</select>
									<div class="make_internal_buttons" onclick="use_additional_title(<?=$galleryId?>); return false;">
									Использовать
									</div>
									<div class="make_internal_buttons" onclick="reset_additional_title(<?=$galleryId?>); return false;">
									Ресетнуть ID тайтла
									</div>
									<div class="make_internal_buttons" onclick="show_current_additional_title(<?=$galleryId?>); return false;">
									Показать ID тайтла
									</div>
											
								</div>
								<div style="clear:both"></div>
								<?php } ?>								        
								<div id="title_<?=$galleryId?>" style="height:20px; margin-top: 8px; margin-bottom: 8px;">
									<input 
										id="own_title_<?=$galleryId?>" 
										name="own_title_<?=$galleryId?>" 
										value="<?=htmlspecialchars($gallery['title'])?>" 
										style="width: calc(100% - 45px); float: left;"
										onkeyup="chageLength(this.value,this.name)"
									/>
									<div id="own_title_<?=$galleryId?>L" style="margin-left:10px; padding: 4px; float: left; background: #e4e4e4;"><?php echo strlen($gallery['title'])?></div>
									<div style="visibility: hidden;">
										<input id="main_thumb_<?=$galleryId?>" name="main_thumb_<?=$galleryId?>" value="<?=$main_thumb?>">
										<input name="gal_id_<?=$galleryId?>" value="<?=$galleryId?>">
										<input id="used_title_id_<?=$galleryId?>" name="used_title_id_<?=$galleryId?>" value="0">
									</div>
								</div>
								<br />
						
									<div style="float: left; font-size: 18px; margin: 10px; margin-left: 0px;margin-top:10px; font-weight: bold;">Tags:</div>
	<?php 							$procTags = $galleries_worker->getTags($galleryId, true);
									$count = count($procTags);
									if (is_array($procTags)) {
							            foreach ($procTags as $tag_id => $tag_name) { ?>
											<div id="gal_<?=$galleryId?>_tag_<?=$tag_id?>" class="tag">
												<div style="font-size: 18px; margin-top: 5px; margin-right: 4px; margin-left: 4px; width: auto; height: auto; float: left;">
													<?=$tag_name?>
												</div>
												<div style="margin-bottom: 5px; width: auto; height: auto; float: left;">
													<img src="images/button_red_minus.png" onclick="remove_gallery_tag_make(<?=$galleryId?>,<?=$tag_id?>);" />
												</div>
											</div>
	<?php								}
									} ?>
									<div>
								        <select id="add_tag_<?=$galleryId?>" name="add_tag_<?=$galleryId?>" style="float: left; font-size: 18px; height: 38px; margin: 3px 5px 5px 15px; padding: 5px;">
               								<option value="0">No</option>
											<?php foreach ($sites_tags_list as $_tag_id => $_tag) { ?>
	                							<option value="<?=$_tag_id?>"><?=$_tag['name']?></option>
											<?php } ?>
	              						</select>
	              						<div style="float: left; padding: 0; margin: 2px;  margin-top: 5px;display: block;"><img src="images/add_button_small.png" onclick="add_new_tag(<?=$galleryId?>);" /></div>
              						</div>
								<div style="clear: both;"></div>
								<hr />
								<div style="width: 100%; float: right;">
									<div style="float: left; font-size: 18px; margin: 10px; margin-left: 0px;margin-top:10px; font-weight: bold;">Models:</div>
									<?php
									if($galleryModels = $models->getGalleryModels($galleryId)) {
										foreach ($galleryModels as $modelId) {
											if ($models->switchModel($modelId)) {
												$thumbURL =	HOSTING."/thumbs/p/180/". folderNameById($models->getPicture()) ."/".$models->getPicture() .".jpg"; ?>
												<div id='modelDD<?=$modelId?>'> 
													<dd 
														
														onmouseover="over('<?=$thumbURL?>')"
														onmousemove="move(event)"
														onmouseout="out()"
														style="float: left; font-size: 18px; margin: 10px; margin-left: 0px; margin-top:10px"
													>
														<?=$models->getName()?>
													</dd>
													<img 
														style="padding: 0px; margin-top: 15px;" 
														src="images/del.png" 
														id ="model_<?=$modelId?>" 
														onclick="deleteModelFromGal(<?=$modelId?>,<?=$galleryId?>)"
													/>
												</div>
									<?php							
											}
										}					
									} ?>
								</div>
						</div>
						<div style="clear: both;"></div>
					</div>
					<div style="clear: both;"></div>
					<div style="margin:6px; margin-top: 1px; margin-bottom: 25px; padding: 5px; width: 1274px; height: auto; border: 1px #000 solid; display: none; text-align: left;" name="addModelBlock" id="addModelBlock<?=$galleryId?>">
			        </div>					
					<div style="clear: both;"></div>
					<?php if(!isset($open_all_galleries) || !$open_all_galleries) { ?>
					<div style="margin:6px; padding: 5px; width: calc(100% - 22px); height: 26px; border: 1px #000 solid; display: block-inline; text-align: left;">
				        <input 
							style="float: left;" 
							type="button" 
							id="showGalleryButton<?=$galleryId?>" 
							value="Показать все тумбы" 
							onclick="make_ShowThumbs(<?=$galleryId?>,'<?=$gallery['type']?>');"
						/>
						<input 
							style="float: right;" 
							type="button" 
							id="galleryToRecrop<?=$galleryId?>" 
							value="Галеру в кроппер" 
							onclick="galleryToRecropAndHide(<?=$galleryId?>)"
						/>
			        </div>
			        <div style="clear: both;"></div>
			        <?php } else { 
			        	// использование горизонтально отресайженых тумб
			        	$thumbs_type = ($use_horiz_thumbs) ? 'horiz_thumbs' : $gallery['type'];
			        ?>
			        <div style="margin:6px; padding: 5px; width: 1274px; height: 26px; border: 1px #000 solid; display: block-inline; text-align: left;">
				            <input style="float: left;" type="button" id="showGalleryButton<?=$galleryId?>" value="Показать все тумбы">

							<input 
								style="float: right;" 
								type="button" 
								id="galleryToRecrop<?=$galleryId?>" 
								value="Галеру в кроппер" 
								onclick="galleryToRecropAndHide(<?=$galleryId?>)"
							/>
				            
			        	</div>
			        	<script>
						   make_ShowThumbs(<?=$galleryId?>,'<?=$thumbs_type?>');
						</script>
			        <?php } ?>
			        
					<div style="margin:6px; padding: 5px; width: calc(100% - 22px); height: auto; border: 1px #000 solid; display: block-inline; text-align: left;">
						<input class="make_gallery_button" type="button" value="+сейчас" onclick="add_site_gallery(<?=$site['id']?>, <?=$galleryId?>)">
						<input class="make_gallery_button"  type="button" value="+сегодня" onclick="add_site_gallery_to_query(<?=$site['id']?>, <?=$galleryId?>, 0)">
<?php					for ($i = 1; $i <= 70; $i++) { ?>
								<input class="make_gallery_button"  type="button" value="+<?=$i?> days" onclick="add_site_gallery_to_query(<?=$site['id']?>, <?=$galleryId?>, <?=$i?>)">
<?php 						} ?>
						<div style="clear: both;"></div>
					</div>
			</div>				
			<div style="clear: both;"></div>