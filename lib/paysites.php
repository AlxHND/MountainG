<?php
$sources = new Sources($db->_db);

$set_cropped 		= (isset($_REQUEST['set_cropped']) && $_REQUEST['set_cropped']) ? 1 : 0;
$use_original_ids 	= (isset($_REQUEST['use_original_ids']) && $_REQUEST['use_original_ids']) ? 1 : 0;
$single_update_page = (isset($_REQUEST['single_update_page'])) ? $_REQUEST['single_update_page'] : 0;
$bitrate 			= (isset($_REQUEST['bitrate'])) ? (int)$_REQUEST['bitrate'] : 2200;

$paysite_info_txt = "";

if (isset($_REQUEST['delete_paysite']) && isset($_GET['siteid'])) {
	$paysite_id 	= (int)$_REQUEST['siteid'];
	$source_delete 	= $sources->deleteSource($paysite_id);
	$output_html 	= ($source_delete) ? "Платник ID:<strong>{$paysite_id}</strong> удален" : "Ошибка! Платник ID:<strong>{$paysite_id}</strong> не удален.";
	
	unset($_REQUEST);
	echo $output_html;

}	elseif (isset($_GET['check_updates']) && intval($_GET['check_updates'])) {

	$site_id = (int)$_GET['check_updates'];
	$source_info = $sources->getSource($site_id);

	if ($source_info) {

		$output_html = '<a href="index.php?act=paysites">Перейти к базе платников</a><hr>';
		$content_type = (isset($_GET['content_type'])) ? $_GET['content_type'] : false; 
		$updates = $sources->checkUpdates($site_id, $content_type);

		if (is_array($updates)) {

			$output_html .= "<br><div style='float: left; text-align: left; width: 100%;'>";
			
			foreach ($updates as $update) {
				$output_html .=  $update['url']."|".$update['title']."|".$update['desc']."|".$source_info['name']."<br>";
			}

			$output_html .=  "<div><div style='clear: both;'></div>";

		} else {
			$output_html .=  "Ошибка! У платника #{$site_id} нет УРЛа для апдейтов<hr>";
		}
		
	} else {
		$output_html .=  "Ошибка! Нет платника с ID #{$site_id}<hr>";
	}

	echo $output_html;
	
} else {
		
	if ($_GET["act"] == "paysites") {

			if ((isset($_GET['query']) && $_GET['query'] == 'add') || (isset($_GET['edit']) && isset($_GET['siteid'])) ) {
				
				if (isset($_REQUEST['crop']) && $_POST['crop'] == 'create') {
					$cropProfile['name']		= $_POST['profile-name'];
					$cropProfile['IM'] 			= $_POST['im-string'];
					$cropProfile['quality']		= $_POST['crop-quality'];
					$cropProfile['top']			= $_POST['crop-top'];
					$cropProfile['bottom']		= $_POST['crop-bottom'];
					$cropProfile['left']		= $_POST['crop-left'];
					$cropProfile['right']		= $_POST['crop-right'];
					$cropProfile['id'] 			= $sources->addCropProfile($cropProfile);
				}				
				
				if (isset($_REQUEST['paysite'], $_REQUEST['affiliate'], $_REQUEST['category'], $_REQUEST['niche'], $_REQUEST['link'])) {
					
					$paysite_legal_link = isset($_POST['legal_link']) ? $_POST['legal_link'] : '';
					$crop_id 			= (isset($cropProfile, $cropProfile['id'])) ? $cropProfile['id'] : (isset($_POST['crop']) ? $_POST['crop'] : 0);

					if (isset($_GET['edit'], $_GET['siteid'])) {

						$paysite_id = intval($_GET['siteid']);
						
						$sources->updateSource($_POST['paysite'], $_POST['affiliate'], $_POST['niche'], 
												$_POST['category'], $_POST['link'], $crop_id,
												$_POST['hosted'], $paysite_info_txt, $_POST['paysiteReview'], $_POST['trialLength'], 
												$_POST['trialPrice'], $_POST['fullPrice'], $_POST['clickHereText'], 
												$_POST['paysiteRating'], $paysite_id, $_POST['paysite_update_page'],
												$_POST['update_type'], $_POST['video_update_page'], $_POST['update_type_video'],
												$single_update_page, $set_cropped, $bitrate, $use_original_ids, $paysite_legal_link);

						echo "Платник: <strong>{$paysite_id}</strong> исправлена";
					} else {

						$paysite_id = $sources->addSource($_REQUEST['paysite'],$_REQUEST['affiliate'],$_REQUEST['niche'],
														  $_REQUEST['category'],$_REQUEST['link'],$crop_id,$_REQUEST['hosted'],
														  $paysite_info_txt, $_POST['paysiteReview'], $_POST['trialLength'], $_POST['trialPrice'], 
														  $_POST['fullPrice'], $_POST['clickHereText'], $_POST['paysiteRating'],
														  $_POST['paysite_update_page'], $_POST['update_type'],$_POST['video_update_page'],
														  $_POST['update_type_video'], $single_update_page, $set_cropped, $bitrate, $use_original_ids, $paysite_legal_link);

						if($paysite_id) echo "Платник: <strong>{$paysite_id}</strong> добавлен";
						else echo "Ошибка! Платник не добавлен!";
					}

					if (isset($paysite_id)) $cache_worker->server_cacheSource($paysite_id);					
				}

				

				if (isset($_GET['edit'], $_GET['siteid'])) { 
					$siteInfo = $sources->getSource((int)$_GET['siteid']); 
					if(isset($siteInfo['use_original_ids'])) $use_original_ids = $siteInfo['use_original_ids'];
				}
	?>

				<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
					<div align="center">
						<table class="disclaim" cellpadding="2" cellspacing="2">
							<tr>
								<td bgcolor="#e4e4e4" colspan="2">
									<div style="float: left; margin:15px; display: block; height:20pix; width: auto; padding: 4px;">
										Niche: 
										<select name="niche" id="niche">
											<option value="Gay" <?php if (isset($siteInfo) && $siteInfo['niche'] == 'Gay') echo "selected='selected'"; ?>>Gay</option>
											<option value="Straight" <?php if (isset($siteInfo) && $siteInfo['niche'] == 'Straight') echo "selected='selected'"; ?>>Straight</option>
											<option value="Shemale" <?php if (isset($siteInfo) && $siteInfo['niche'] == 'Shemale') echo "selected='selected'"; ?>>Shemale</option>
										</select>
									</div>
									<div style="float: left; margin:15px; display: block; height:20pix; width: auto; padding: 4px;">
										На ФХГ или хостед: 
										<select name="hosted" id="hosted">
											<option value="1" <?php if (isset($siteInfo) && $siteInfo['hosted'] == 1) echo "selected='selected'"; ?>>У себя</option>
											<option value="0"<?php if (isset($siteInfo) && $siteInfo['hosted'] == 0) echo "selected='selected'"; ?>>ФХГ</option>
										</select>
									</div>
									<div style="float: left; margin:15px; display: block; height:20pix; width: auto; padding: 4px;">
										Категория: 
										<select name="category" id="category">
											<option value="0">General</option>
											<?php
												if (isset($siteInfo)) $default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $siteInfo['category']);
												else $default->AllTagsToString ("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>");
											?>
										</select>
									</div>							
								</td>
							</tr>					
							<tr>
								<td bgcolor="#e4e4e4">Paysite: </td>
								<td bgcolor="#e4e4e4"><input size="42" name="paysite" id="paysite" <?php if (isset($siteInfo)) echo "value='".$siteInfo['name']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Affiliate: </td>
								<td bgcolor="#e4e4e4"><input size="42" name="affiliate" id="affiliate" <?php if (isset($siteInfo)) echo "value='".$siteInfo['affiliateProgram']."'";?>></td>
							</tr>
							
							<tr>
								<td bgcolor="#e4e4e4">Crop profile: </td>
								<td bgcolor="#e4e4e4">
									<select name="crop" id="crop" onchange="ShowCropper(this.value);">
										<?php
											if (isset($siteInfo)) $default->AllCropProfilesToString ("<option value=\"#PROFILE_ID#\" #SELECTED#>#PROFILE#</option>", $siteInfo['cropProfile']);
											else $default->AllCropProfilesToString ("<option value=\"#PROFILE_ID#\" #SELECTED#>#PROFILE#</option>"); 
										?>
										<option value="create">&nbsp;-&nbsp;Create</option>
									</select>
								</td>
							</tr>
														
							<tr>
								<td bgcolor="#e4e4e4" colspan = "2" align="left">
									<div id="create" style="display:none;">
										Профиль: <input size="30" name="profile-name" id="profile-name" <?php if (isset($siteInfo)) echo "value='".$siteInfo['crop']['name']."'";?>> <br /> <br />
										Строка мэджика: <input size="45" name="im-string" id="im-string"> <br /> <br />
										Отступа по сторонам: top: <input size="2" name="crop-top" id="crop-top" <?php if (isset($siteInfo)) echo "value='".$siteInfo['crop']['top']."'"; else echo "value='0'";?>>
										bottom: <input size="2" name="crop-bottom" id="crop-bottom" <?php if (isset($siteInfo)) echo "value='".$siteInfo['crop']['bottom']."'"; else echo "value='0'";?>>
										left: <input size="2" name="crop-left" id="crop-left" <?php if (isset($siteInfo)) echo "value='".$siteInfo['crop']['left']."'"; else echo "value='0'";?>>
										right: <input size="2" name="crop-right" id="crop-right" <?php if (isset($siteInfo)) echo "value='".$siteInfo['crop']['right']."'"; else echo "value='0'";?>> <br /> <br />
										качество: <input size="2" name="crop-quality" id="crop-quality" value="95">
									</div>
								</td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Ставить автоматически флаг "скроплено"</td>
								<td bgcolor="#e4e4e4">
									<select name="set_cropped" id="set_cropped">
											<option value="0" <?php if (isset($siteInfo) && $siteInfo['set_cropped'] == 0) echo "selected='selected'"; ?>>Нет</option>
											<option value="1"<?php if (isset($siteInfo) && $siteInfo['set_cropped']) echo "selected='selected'"; ?>>Да</option>
										</select>
								</td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Источник предоставляет ID своих галер (это для тубов - управление удалением галер по заданному ID)</td>
								<td bgcolor="#e4e4e4">
									<select name="use_original_ids" id="use_original_ids">
											<option value="0"<?php if (isset($use_original_ids) && $use_original_ids == 0) echo " selected='selected'"; ?>>Нет</option>
											<option value="1"<?php if (isset($use_original_ids) && $use_original_ids) echo " selected='selected'"; ?>>Да</option>
										</select>
								</td>
							</tr>

							
							<tr>
								<td bgcolor="#e4e4e4">Битрейт для конверта видео (в kbs): </td>
								<td bgcolor="#e4e4e4"><input size="42" name="bitrate" id="bitrate" <?php if (isset($siteInfo)) echo "value='".(int)$siteInfo['bitrate']."'";?>></td>
							</tr>					
							<tr>
								<td bgcolor="#e4e4e4">Линка: </td>
								<td bgcolor="#e4e4e4"><input size="42" name="link" id="link" <?php if (isset($siteInfo)) echo "value='".$siteInfo['link']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Линка на 2257: </td>
								<td bgcolor="#e4e4e4"><input size="42" name="legal_link" id="legal_link" <?php if (isset($siteInfo)) echo "value='".$siteInfo['legal_link']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">URL для апдейтов: 
									<select name="update_type" id="update_type">
										<option value="site" <?php if (isset($siteInfo) && $siteInfo['update_type'] == 'site') echo "selected='selected'"; ?>>Парсинг с сайта</option>
										<option value="xml" <?php if (isset($siteInfo) && $siteInfo['update_type'] == 'xml') echo "selected='selected'"; ?>>Парсинг XML</option>
										<option value="blog" <?php if (isset($siteInfo) && $siteInfo['update_type'] == 'blog') echo "selected='selected'"; ?>>Парсинг блога</option>
										<option value="manual" <?php if (isset($siteInfo) && $siteInfo['update_type'] == 'manual') echo "selected='selected'"; ?>>Ручной режим</option>
									</select><br />
									Оба типа на одной странице: <input type=checkbox <?php if (isset($siteInfo) && $siteInfo['single_update_page']) echo "checked=true"; ?> name="single_update_page"  />
								</td>
								<td bgcolor="#e4e4e4"><input size="46" name="paysite_update_page" id="paysite_update_page" <?php if (isset($siteInfo)) echo "value='".$siteInfo['paysite_update_page']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Доп. URL для апдейтов:
									<select name="update_type_video" id="update_type_video">
										<option value="onsite" <?php if (isset($siteInfo) && $siteInfo['update_type_video'] == 'onsite') echo "selected='selected'"; ?>>Парсинг с сайта</option>
										<option value="xml" <?php if (isset($siteInfo) && $siteInfo['update_type_video'] == 'xml') echo "selected='selected'"; ?>>Парсинг XML</option>
										<option value="blog" <?php if (isset($siteInfo) && $siteInfo['update_type_video'] == 'blog') echo "selected='selected'"; ?>>Парсинг блога</option>
										<option value="manual" <?php if (isset($siteInfo) && $siteInfo['update_type_video'] == 'manual') echo "selected='selected'"; ?>>Ручной режим</option>
									</select>
								</td>
								<td bgcolor="#e4e4e4"><input size="46" name="video_update_page" id="video_update_page" <?php if (isset($siteInfo)) echo "value='".$siteInfo['video_update_page']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Ревью: </td>
								<td bgcolor="#e4e4e4"><textarea rows="5" cols="60" name="paysiteReview" id="paysiteReview"><?php if (isset($siteInfo)) echo $siteInfo['paysiteReview']; ?></textarea>
							</td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Триал в днях: </td>
								<td bgcolor="#e4e4e4"><select name="trialLength" id="trialLength">
										<option value="0" <?php if (isset($siteInfo) && $siteInfo['trialLength'] == 0) echo "selected='selected'"; ?>>Нет триала</option>
										<option value="1"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 1) echo "selected='selected'"; ?>>1</option>
										<option value="2"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 2) echo "selected='selected'"; ?>>2</option>
										<option value="3"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 3) echo "selected='selected'"; ?>>3</option>
										<option value="4"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 4) echo "selected='selected'"; ?>>4</option>
										<option value="5"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 5) echo "selected='selected'"; ?>>5</option>
										<option value="6"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 6) echo "selected='selected'"; ?>>6</option>
										<option value="7"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 7) echo "selected='selected'"; ?>>7</option>
										<option value="8"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 8) echo "selected='selected'"; ?>>8</option>
										<option value="9"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 9) echo "selected='selected'"; ?>>9</option>
										<option value="10"<?php if (isset($siteInfo) && $siteInfo['trialLength'] == 10) echo "selected='selected'"; ?>>10</option>
									</select>
								</td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Стоимость триала: </td>
								<td bgcolor="#e4e4e4"><input size="6" name="trialPrice" id="trialPrice" <?php if (isset($siteInfo)) echo "value='".$siteInfo['trialPrice']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Стоимость месячной подписки: </td>
								<td bgcolor="#e4e4e4"><input size="6" name="fullPrice" id="fullPrice" <?php if (isset($siteInfo)) echo "value='".$siteInfo['fullPrice']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Альтернативный короткий Click Here! текст: </td>
								<td bgcolor="#e4e4e4"><input size="42" name="clickHereText" id="clickHereText" <?php if (isset($siteInfo)) echo "value='".$siteInfo['clickHereText']."'";?>></td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Рейтинг сайта: </td>
								<td bgcolor="#e4e4e4"><input size="6" name="paysiteRating" id="paysiteRating" <?php if (isset($siteInfo)) echo "value='".$siteInfo['paysiteRating']."'";?>></td>
							</tr>
						</table>
						<input type="submit" value="Add paysite >>" name="add-site" id="add-site" />

					</div>

					<?php if (isset($_GET['siteid'])) { ?>
						<div style="float: left;"><input onclick="return confirm('удалить платник?')" style="color: #FF0000; background: none;" type="submit" value="X" name="delete_paysite" id="delete_paysite" /></div>
					<?php } ?>
					<div style = "clear: both;"></div>
				</form>
	<?php   } else {  ?>			
	<div style="display: block;  width: 1200px; text-align: left; font-size: 12px; margin: 15px;">
		Всего платников: <?=$sources_count = $sources->sourcesCount(); ?>,
		Платников в кэше: <strong id="sources_count_block"><?php 
	                      $sources_cached = $cache_worker->sourcesCount();
	                      if ($sources_cached && $sources_cached == $sources_count) echo $sources_cached;
	                      elseif (!$sources_cached) {
	?>
	                        Пусто
	<?php                        
	                      } else {
	?>
	                        Количество моделей в кеше не совпадает: <i><?=$sources_cached?></i>
	<?php
	                      } 
	?></strong>
	  <input type="button" value="Пересобрать кeш платников" id="init_sources" onclick="init_sources();">
	</div>
	<br />

	<link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap/dist/css/bootstrap.min.css" />
	<link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.css" />
	<script src="//polyfill.io/v3/polyfill.min.js?features=es2015%2CIntersectionObserver" crossorigin="anonymous"></script>
	<script src="//unpkg.com/vue@latest/dist/vue.min.js"></script>
	<script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.js"></script>
	<script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue-icons.min.js"></script>	

	<?php	

				if(isset($_GET['duplicate'])) {
					var_dump($sources->getDuplicateSources());
				}

				$updateable_paysites = array(
												"metartmoney.com",
												"gayhoopla.com",
												"lucaskazan.com",
												"chaosmen.com",
												"kinkydollars.com",
												"xxxrewards.com",
												"englishlads.com",
												"buddyprofits.com",
												"jakepays.com",
												"blueloot.com",
												"seancody.com",
												"dominicford.com",
												"gunzblazing.com",
												"manicamoney.com",
												"helixcash.com"
											);

				$paysites_html_block = "";

				$source_id = false;
				$niche 			= isset($_REQUEST['niche']) ? $_REQUEST['niche'] : false;
				$legal_links 	= isset($_REQUEST['legal_links']) ? ($_REQUEST['legal_links'] == 'with' ? 1 : ($_REQUEST['legal_links'] == 'without' ? -1 : false)) : false;
				$category		= isset($_REQUEST['category']) ? (int)$_REQUEST['category'] : false;


				
				  
				  

				if($paysites = $sources->getAllSources($source_id , $niche, $legal_links, $category)) {

					foreach($paysites as $id => $paysite) {
						$paysite_array[] = "id: {$paysite['id']}, name:  {$paysite['name']}, program: {$paysite['affiliateProgram']}, niche: {$paysite['niche']}, category: {$default->TagName($paysite['category'])}, crop: {$paysite['cropProfile']}, updated: {$paysite['lastUpdate']}";
					}
					$all_paysites = implode(",\n", $paysite_array);
?>

<?php



					foreach($paysites as $id => $paysite) {
						
						$block_height = (isset($paysite['video_update_page']) && $paysite['video_update_page']) ? 40 : 20;

						$paysites_html_block .= "<div style='float:left; margin: 4px; border: #ccc solid 1px; display: block; width: 96%; height: {$block_height}px;'>
													<div style='float:left; margin: 4px;'>
														<a href={$_SERVER['SCRIPT_NAME']}?act=paysites&siteid={$id}&edit>Edit paysite</a> -> 
														ID: {$paysite ['id']} 
														name: <strong>{$paysite['name']}</strong> 
														aff. program: <strong>{$paysite['affiliateProgram']}</strong> 
														niche: {$paysite['niche']} 
														category: <strong>{$default->TagName($paysite['category'])}</strong> 
														crop profile: {$paysite['cropProfile']}
													</div>
													<div style='float:right; margin: 4px;'>{$paysite['lastUpdate']}";

								

						if (in_array($paysite['affiliateProgram'], $updateable_paysites)) {
							if ($paysite['paysite_update_page']) $paysites_html_block .= " | <a href='index.php?act=paysites&amp;check_updates=".$paysite ['id']."'>Проверить апдейты</a>";
							if ($paysite['video_update_page']) $paysites_html_block .=  " <br><a href='index.php?act=paysites&amp;check_updates=".$paysite ['id']."&amp;content_type=video'>Проверить видео апдейты</a>";
						}

						$paysites_html_block .=  "  </div>
												  </div>
							  					  <br>";
					}
				}

	?>

				<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
					Показывать платники: 
					<select name="niche" id="niche">
						<option value="0">All</option>
						<option value="Gay">Gay</option>
						<option value="Straight">Straight</option>
						<option value="Shemale">Shemale</option>
					</select>
					<select name="legal_links" id="legal_links">
						<option value="0">Все без учета 2257 ссылки</option>
						<option value="with">Только с 2257 ссылкой</option>
						<option value="without">Только без 2257 ссылки</option>
					</select>
					<select name="category" id="category">
						<option value="0">Все категории</option>
						<?php $default->AllTagsToString("<option value=\"#TAG_ID#\">#TAG#</option>"); ?>
					</select>
					<input type="submit" value="Показать платники" name="show_paysites" id="show_paysites" />
				</form>
				<?=$paysites_html_block?>
				<div class="menu">
					>> <a href="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>&amp;query=add">Add paysite</a>
					<br />
					<hr>
				</div>

	<?php
			}
		}
	}