						
					<div class='<?=$thumbClass?>' <?php if($thumbClass == 'thumbs') echo "style=\"height: 240px;\""; ?>>
							<div style="float: left;">
								<a href="<?=$_SERVER['SCRIPT_NAME']?>?act=galleries&amp;galid=<?=$galleryId?>" target="_blank" align="center">Edit</a>
							</div>
							<?php if ($site['hand_flag'] == 1) { ?>
								<div style="float: right;">
									<img src="images/del.png" border="0" onClick='ExcludeGallery(<?=$site['id']?>,<?=$galleryId?>);' />
								</div>
								<br />
							<?php } ?>
							<img src=<?=$thumb; ?>>
							<br />
							<div style='float: left' >
							    <strong>Title: </strong><?=$gallery['title'] ?>
								<strong>Paysite: </strong><?=$gallery['paysite']['name'] ?>
							</div>
							<br />
							<div style='float: right' >
								Unique <input onclick="select_unique_gallery(this.id)" type='checkbox' id='<?=$galleryId?>'>
							</div>
							<div style='float: left' >
								Сборка <input onclick="update_checked_gals_counter()" type='checkbox' checked='checked' value='<?=$galleryId?>' name='<?=$galleryId?>' id='thumb' class="xthumb">
							</div>
					</div>
