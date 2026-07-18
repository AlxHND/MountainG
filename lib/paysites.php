<?php
$sources = new Sources($db->_db);
$affiliatePrograms = $sources->getAffiliatePrograms();

if (!function_exists('paysite_affiliate_runtime_value')) {
	function paysite_affiliate_runtime_value($paysite)
	{
		if (!empty($paysite['affiliateProgramUrl'])) {
			return $paysite['affiliateProgramUrl'];
		}
		if (!empty($paysite['affiliateProgram'])) {
			return $paysite['affiliateProgram'];
		}
		if (!empty($paysite['affiliateProgramLegacy'])) {
			return $paysite['affiliateProgramLegacy'];
		}
		return '';
	}
}

if (!function_exists('paysite_h')) {
	function paysite_h($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('paysite_lower')) {
	function paysite_lower($value)
	{
		$value = (string)$value;
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($value, 'UTF-8');
		}
		return strtolower($value);
	}
}

$set_cropped 		= (isset($_REQUEST['set_cropped']) && $_REQUEST['set_cropped']) ? 1 : 0;
$use_original_ids 	= (isset($_REQUEST['use_original_ids']) && $_REQUEST['use_original_ids']) ? 1 : 0;
$single_update_page = (isset($_REQUEST['single_update_page'])) ? $_REQUEST['single_update_page'] : 0;
$bitrate 			= (isset($_REQUEST['bitrate'])) ? (int)$_REQUEST['bitrate'] : 2200;
$affiliate_program_id = (isset($_REQUEST['affiliate_program_id']) && (int)$_REQUEST['affiliate_program_id'] > 0) ? (int)$_REQUEST['affiliate_program_id'] : 0;

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
	$content_type = (isset($_GET['content_type']) && $_GET['content_type'] === 'video') ? 'video' : '';
	$marker_types = $sources->getPaysiteUpdateMarkerTypes();
	$check_updates_message = '';
	$check_updates_message_type = 'ok';

	if ($source_info) {

		if (isset($_POST['save_check_update_marker'])) {
			$marker_type = isset($_POST['marker_type']) ? trim((string)$_POST['marker_type']) : '';
			$update_title = isset($_POST['update_title']) ? trim((string)$_POST['update_title']) : '';
			$update_page_url = isset($_POST['update_page_url']) ? trim((string)$_POST['update_page_url']) : '';
			$update_inner_date = isset($_POST['update_inner_date']) ? trim((string)$_POST['update_inner_date']) : '';

			try {
				$saved_marker_id = $sources->savePaysiteUpdateMarker($site_id, $marker_type, $update_title, $update_page_url, $update_inner_date);
				$check_updates_message = "Маркер #{$saved_marker_id} сохранен как " . (isset($marker_types[$marker_type]) ? $marker_types[$marker_type] : $marker_type) . ".";
			} catch (Exception $e) {
				$check_updates_message = "Ошибка сохранения маркера: " . $e->getMessage();
				$check_updates_message_type = 'error';
			}
		}

		$current_markers = $sources->getPaysiteUpdateMarkersByPaysite($site_id);
		$current_markers_map = array();
		foreach ($current_markers as $current_marker) {
			$current_markers_map[$current_marker['marker_type']] = $current_marker;
		}

		$check_updates_url = 'index.php?act=paysites&check_updates=' . $site_id . ($content_type === 'video' ? '&content_type=video' : '');

		$output_html = '<div style="width:1400px; margin:0 auto; text-align:left;">';
		$output_html .= '<div style="padding: 0 0 10px 0;"><a href="index.php?act=paysites">Перейти к базе платников</a> | <a href="index.php?act=paysite_update_markers&amp;filter_paysite_id=' . $site_id . '">Все маркеры платника</a></div>';
		$output_html .= '<h3 style="margin:0 0 10px 0;">Проверка ' . ($content_type === 'video' ? 'видео апдейтов' : 'апдейтов') . ' для платника #' . $site_id . ' - ' . paysite_h($source_info['name']) . '</h3>';

		if ($check_updates_message !== '') {
			$output_html .= '<div style="padding:10px 12px; margin-bottom:14px; border:1px solid ' . ($check_updates_message_type === 'error' ? '#d99' : '#bcd') . '; background:' . ($check_updates_message_type === 'error' ? '#fff0f0' : '#f3fbff') . ';">' . paysite_h($check_updates_message) . '</div>';
		}

		$output_html .= '<table cellpadding="4" cellspacing="1" width="100%" style="background:#d8d8d8; margin-bottom:14px;">';
		$output_html .= '<tr style="background:#efefef;"><th width="150">Маркер</th><th width="170">Внутренняя дата</th><th>Название</th><th>Страница</th><th width="160">Updated</th><th width="120">Действие</th></tr>';
		foreach ($marker_types as $marker_type_key => $marker_type_label) {
			$marker_row = isset($current_markers_map[$marker_type_key]) ? $current_markers_map[$marker_type_key] : false;
			$output_html .= '<tr style="background:#fff;">';
			$output_html .= '<td><strong>' . paysite_h($marker_type_label) . '</strong></td>';
			$output_html .= '<td>' . ($marker_row && $marker_row['update_inner_date'] ? paysite_h($marker_row['update_inner_date']) : '<span style="color:#999;">-</span>') . '</td>';
			$output_html .= '<td>' . ($marker_row && $marker_row['update_title'] !== '' ? paysite_h($marker_row['update_title']) : '<span style="color:#999;">-</span>') . '</td>';
			$output_html .= '<td>' . ($marker_row && $marker_row['update_page_url'] !== '' ? '<a href="' . paysite_h($marker_row['update_page_url']) . '" target="_blank">' . paysite_h($marker_row['update_page_url']) . '</a>' : '<span style="color:#999;">-</span>') . '</td>';
			$output_html .= '<td>' . ($marker_row && $marker_row['updated_at'] ? paysite_h($marker_row['updated_at']) : '<span style="color:#999;">-</span>') . '</td>';
			$output_html .= '<td><a href="index.php?act=paysite_update_markers&amp;paysite_id=' . $site_id . '&amp;marker_type=' . paysite_h($marker_type_key) . '">Открыть</a></td>';
			$output_html .= '</tr>';
		}
		$output_html .= '</table>';

		$updates = $sources->checkUpdates($site_id, $content_type);

		if (is_array($updates)) {

			$output_html .= "<div style='float: left; text-align: left; width: 100%;'>";
			$output_html .= "<table cellpadding='4' cellspacing='1' width='100%' style='background:#d8d8d8;'>";
			$output_html .= "<tr style='background:#efefef;'>
								<th width='40'>#</th>
								<th width='360'>URL</th>
								<th width='260'>Title</th>
								<th>Description</th>
								<th width='170'>Inner date</th>
								<th width='230'>Сохранить маркер</th>
							</tr>";

			foreach ($updates as $index => $update) {
				$update_url = isset($update['url']) ? trim((string)$update['url']) : '';
				$update_title = isset($update['title']) ? trim((string)$update['title']) : '';
				$update_desc = isset($update['desc']) ? trim((string)$update['desc']) : '';
				$update_inner_date = isset($update['date']) ? trim((string)$update['date']) : '';

				$output_html .= "<tr style='background:#fff; vertical-align:top;'>";
				$output_html .= "<td>" . ($index + 1) . "</td>";
				$output_html .= "<td>" . ($update_url !== '' ? "<a href='" . paysite_h($update_url) . "' target='_blank'>" . paysite_h($update_url) . "</a>" : "<span style='color:#999;'>-</span>") . "</td>";
				$output_html .= "<td>" . ($update_title !== '' ? paysite_h($update_title) : "<span style='color:#999;'>-</span>") . "</td>";
				$output_html .= "<td style='font-size:11px; color:#444;'>" . ($update_desc !== '' ? nl2br(paysite_h($update_desc)) : "<span style='color:#999;'>-</span>") . "</td>";
				$output_html .= "<td>" . ($update_inner_date !== '' ? paysite_h($update_inner_date) : "<span style='color:#999;'>-</span>") . "</td>";
				$output_html .= "<td>
									<form method='post' action='" . $check_updates_url . "' style='margin:0;'>
										<input type='hidden' name='save_check_update_marker' value='1'>
										<input type='hidden' name='update_title' value='" . paysite_h($update_title) . "'>
										<input type='hidden' name='update_page_url' value='" . paysite_h($update_url) . "'>
										<input type='text' name='update_inner_date' value='" . paysite_h($update_inner_date) . "' placeholder='Дата (опц.)' style='width:180px; margin-bottom:4px;'>
										<br>
										<button type='submit' name='marker_type' value='latest' style='width:180px; margin-bottom:4px;'>Записать как: Последний по времени</button>
										<br>
										<button type='submit' name='marker_type' value='backfill' style='width:180px;'>Записать как: Последний из старых</button>
									</form>
								</td>";
				$output_html .= "</tr>";
			}

			$output_html .=  "</table></div><div style='clear: both;'></div>";

		} else {
			$output_html .=  "Ошибка! У платника #{$site_id} нет УРЛа для апдейтов<hr>";
		}
		
		$output_html .= '</div>';
	} else {
		$output_html = "Ошибка! Нет платника с ID #{$site_id}<hr>";
	}

	echo $output_html;
	
	} else {
			
		if ($_GET["act"] == "paysites") {

				echo '<div style="padding: 0 0 12px 0; text-align:left; width:1200px; margin:0 auto;"><a href="index.php?act=affiliate_programs">Управление affiliate programs</a></div>';

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
													$single_update_page, $set_cropped, $bitrate, $use_original_ids, $paysite_legal_link, $affiliate_program_id);

						echo "Платник: <strong>{$paysite_id}</strong> исправлена";
					} else {

							$paysite_id = $sources->addSource($_REQUEST['paysite'],$_REQUEST['affiliate'],$_REQUEST['niche'],
															  $_REQUEST['category'],$_REQUEST['link'],$crop_id,$_REQUEST['hosted'],
															  $paysite_info_txt, $_POST['paysiteReview'], $_POST['trialLength'], $_POST['trialPrice'], 
															  $_POST['fullPrice'], $_POST['clickHereText'], $_POST['paysiteRating'],
															  $_POST['paysite_update_page'], $_POST['update_type'],$_POST['video_update_page'],
															  $_POST['update_type_video'], $single_update_page, $set_cropped, $bitrate, $use_original_ids, $paysite_legal_link, $affiliate_program_id);

						if($paysite_id) echo "Платник: <strong>{$paysite_id}</strong> добавлен";
						else echo "Ошибка! Платник не добавлен!";
					}

					if (isset($paysite_id)) $cache_worker->server_cacheSource($paysite_id);					
				}

				

					if (isset($_GET['edit'], $_GET['siteid'])) { 
						$siteInfo = $sources->getSource((int)$_GET['siteid']); 
						if(isset($siteInfo['use_original_ids'])) $use_original_ids = $siteInfo['use_original_ids'];
						$siteUpdateMarkers = $sources->getPaysiteUpdateMarkersByPaysite((int)$_GET['siteid']);
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
								<td bgcolor="#e4e4e4">
									<input size="42" name="affiliate" id="affiliate" <?php if (isset($siteInfo)) echo "value='".htmlspecialchars(isset($siteInfo['affiliateProgramLegacy']) && $siteInfo['affiliateProgramLegacy'] !== '' ? $siteInfo['affiliateProgramLegacy'] : $siteInfo['affiliateProgram'], ENT_QUOTES, 'UTF-8')."'";?>>
									<div style="font-size:11px; color:#666; padding-top:4px;">Если поле пустое, будет использован URL или Name выбранной affiliate program.</div>
								</td>
							</tr>
							<tr>
								<td bgcolor="#e4e4e4">Affiliate program: </td>
								<td bgcolor="#e4e4e4">
									<select name="affiliate_program_id" id="affiliate_program_id">
										<option value="0">-- Не выбрано --</option>
										<?php if ($affiliatePrograms) {
											foreach ($affiliatePrograms as $affiliateProgram) {
												$selected = '';
												if (isset($siteInfo['affiliateProgramId']) && (int)$siteInfo['affiliateProgramId'] === (int)$affiliateProgram['affiliate_program_id']) {
													$selected = " selected='selected'";
												}
										?>
										<option value="<?=$affiliateProgram['affiliate_program_id']?>"<?=$selected?>>
											<?=htmlspecialchars($affiliateProgram['affiliate_program_name'], ENT_QUOTES, 'UTF-8')?><?php if (!empty($affiliateProgram['affiliate_program_url'])) { ?> | <?=htmlspecialchars($affiliateProgram['affiliate_program_url'], ENT_QUOTES, 'UTF-8')?><?php } ?>
										</option>
										<?php
											}
										} ?>
									</select>
								</td>
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

						<?php if (isset($_GET['siteid']) && (int)$_GET['siteid'] > 0) { ?>
							<div style="text-align:left; width:1200px; margin:10px auto 0; padding:10px; background:#f8f8f8; border:1px solid #ddd;">
								<strong>Маркеры апдейтов:</strong>
								<a href="index.php?act=paysite_update_markers&amp;filter_paysite_id=<?=(int)$_GET['siteid']?>">все</a>
								|
								<a href="index.php?act=paysite_update_markers&amp;paysite_id=<?=(int)$_GET['siteid']?>&amp;marker_type=latest">последний по времени</a>
								|
								<a href="index.php?act=paysite_update_markers&amp;paysite_id=<?=(int)$_GET['siteid']?>&amp;marker_type=backfill">последний из старых</a>
								<?php if (!empty($siteUpdateMarkers)) { ?>
									<div style="padding-top:8px; color:#555; font-size:12px;">
										<?php foreach ($siteUpdateMarkers as $marker) { ?>
											<div>
												<?=htmlspecialchars($marker['marker_type'], ENT_QUOTES, 'UTF-8')?>:
												<strong><?=htmlspecialchars($marker['update_title'], ENT_QUOTES, 'UTF-8')?></strong>
												<?php if (!empty($marker['update_inner_date'])) { ?> | <?=htmlspecialchars($marker['update_inner_date'], ENT_QUOTES, 'UTF-8')?><?php } ?>
											</div>
										<?php } ?>
									</div>
								<?php } ?>
							</div>
						<?php } ?>

						<?php if (isset($_GET['siteid'])) { ?>
							<div style="float: left;"><input onclick="return confirm('удалить платник?')" style="color: #FF0000; background: none;" type="submit" value="X" name="delete_paysite" id="delete_paysite" /></div>
						<?php } ?>
					<div style = "clear: both;"></div>
				</form>
	<?php   } else {  ?>			
	<div style="display: block; width: 1200px; text-align: left; font-size: 12px; margin: 15px auto;">
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
					$affiliate_program_filter = isset($_REQUEST['affiliate_program_filter']) ? (int)$_REQUEST['affiliate_program_filter'] : false;

					if($paysites = $sources->getAllSources($source_id , $niche, $legal_links, $category, $affiliate_program_filter)) {
						foreach($paysites as $id => $paysite) {
							$affiliateProgramLabel = $paysite['affiliateProgram'] ? paysite_h($paysite['affiliateProgram']) : '<span style="color:#999;">legacy</span>';
							$affiliateProgramUrl = $paysite['affiliateProgramUrl'] ? paysite_h($paysite['affiliateProgramUrl']) : '';
							$affiliateProgramLegacy = !empty($paysite['affiliateProgramLegacy']) ? paysite_h($paysite['affiliateProgramLegacy']) : '';
							$affiliateRuntimeValue = paysite_affiliate_runtime_value($paysite);
							$categoryName = $default->TagName($paysite['category']);
							$lastUpdate = isset($paysite['lastUpdate']) ? trim((string)$paysite['lastUpdate']) : '';
							$lastUpdateTs = $lastUpdate !== '' ? strtotime($lastUpdate) : 0;
							$bitrateValue = isset($paysite['bitrate']) ? (int)$paysite['bitrate'] : 0;
							$hasLegalLink = !empty($paysite['legal_link']);
							if ($lastUpdateTs === false) {
								$lastUpdateTs = 0;
							}
							$actions = array();
							$actions[] = "<a href='" . $_SERVER['SCRIPT_NAME'] . "?act=paysites&amp;siteid={$id}&amp;edit'>Edit</a>";
							$actions[] = "<a href='index.php?act=paysite_update_markers&amp;filter_paysite_id={$id}'>Markers</a>";
							if (in_array($affiliateRuntimeValue, $updateable_paysites, true)) {
								if ($paysite['paysite_update_page']) {
									$actions[] = "<a href='index.php?act=paysites&amp;check_updates={$paysite['id']}'>Апдейты</a>";
								}
								if ($paysite['video_update_page']) {
									$actions[] = "<a href='index.php?act=paysites&amp;check_updates={$paysite['id']}&amp;content_type=video'>Видео апдейты</a>";
								}
							}

							$paysites_html_block .= "<tr class='paysites-row' data-name='" . paysite_h(paysite_lower($paysite['name'])) . "' data-program='" . paysite_h(paysite_lower(strip_tags($affiliateProgramLabel))) . "'>
								<td data-sort='" . (int)$paysite['id'] . "'>" . (int)$paysite['id'] . "</td>
								<td data-sort='" . paysite_h(paysite_lower($paysite['name'])) . "'><strong>" . paysite_h($paysite['name']) . "</strong></td>
								<td data-sort='" . paysite_h(paysite_lower(strip_tags($affiliateProgramLabel))) . "'>
									<div><strong>{$affiliateProgramLabel}</strong></div>" .
									($affiliateProgramUrl ? "<div class='paysites-muted'>{$affiliateProgramUrl}</div>" : "") .
									($affiliateProgramLegacy && $affiliateProgramLegacy !== $affiliateProgramUrl && $affiliateProgramLegacy !== strip_tags($affiliateProgramLabel) ? "<div class='paysites-legacy'>legacy: {$affiliateProgramLegacy}</div>" : "") .
								"</td>
								<td data-sort='" . paysite_h($paysite['niche']) . "'>" . paysite_h($paysite['niche']) . "</td>
								<td data-sort='" . paysite_h(paysite_lower($categoryName)) . "'>" . paysite_h($categoryName) . "</td>
								<td data-sort='" . $bitrateValue . "'>" . ($bitrateValue > 0 ? $bitrateValue . " kbps" : "<span class='paysites-muted'>-</span>") . "</td>
								<td data-sort='" . ($hasLegalLink ? 1 : 0) . "'>" . ($hasLegalLink ? "Да" : "Нет") . "</td>
								<td data-sort='" . (int)$lastUpdateTs . "'>" . ($lastUpdate !== '' ? paysite_h($lastUpdate) : "<span class='paysites-muted'>-</span>") . "</td>
								<td>" . implode(" <span class='paysites-sep'>|</span> ", $actions) . "</td>
							</tr>";
					}
				}

	?>

				<style type="text/css">
					.paysites-panel {
						width: 1400px;
						margin: 0 auto;
						text-align: left;
						font-size: 13px;
					}

					.paysites-controls {
						display: flex;
						flex-wrap: wrap;
						gap: 10px;
						align-items: center;
						margin: 14px 0 12px;
						padding: 12px;
						background: #f7f8fb;
						border: 1px solid #d8deea;
					}

					.paysites-controls select,
					.paysites-controls input[type="text"] {
						height: 32px;
						padding: 0 8px;
						border: 1px solid #bfc7d6;
						box-sizing: border-box;
					}

					.paysites-controls input[type="submit"] {
						height: 32px;
						padding: 0 12px;
					}

					.paysites-live-search {
						min-width: 260px;
						flex: 1 1 260px;
					}

					.paysites-summary {
						display: flex;
						justify-content: space-between;
						align-items: center;
						margin: 8px 0 10px;
						color: #444;
					}

					.paysites-table-wrap {
						border: 1px solid #d8deea;
						background: #fff;
						overflow-x: auto;
					}

					.paysites-table {
						width: 100%;
						border-collapse: collapse;
						min-width: 1180px;
					}

					.paysites-table th,
					.paysites-table td {
						padding: 10px 12px;
						border-bottom: 1px solid #e6eaf2;
						vertical-align: top;
					}

					.paysites-table th {
						background: #f2f5fa;
						color: #223;
						font-weight: bold;
						white-space: nowrap;
					}

					.paysites-table tbody tr:hover {
						background: #fafcff;
					}

					.paysites-sortable {
						cursor: pointer;
						user-select: none;
					}

					.paysites-sort-indicator {
						display: inline-block;
						width: 14px;
						color: #667;
					}

					.paysites-muted {
						color: #6f7787;
						font-size: 11px;
						margin-top: 2px;
						word-break: break-word;
					}

					.paysites-legacy {
						color: #8b6c3b;
						font-size: 11px;
						margin-top: 2px;
					}

					.paysites-sep {
						color: #999;
					}

					.paysites-empty {
						padding: 18px 12px;
						color: #666;
					}
				</style>

				<div class="paysites-panel">
					<form class="paysites-controls" enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
						<span>Показывать платники:</span>
						<select name="niche" id="niche">
							<option value="0">All</option>
							<option value="Gay" <?php if ($niche === 'Gay') echo "selected='selected'"; ?>>Gay</option>
							<option value="Straight" <?php if ($niche === 'Straight') echo "selected='selected'"; ?>>Straight</option>
							<option value="Shemale" <?php if ($niche === 'Shemale') echo "selected='selected'"; ?>>Shemale</option>
						</select>
						<select name="legal_links" id="legal_links">
							<option value="0" <?php if ($legal_links === false) echo "selected='selected'"; ?>>Все без учета 2257 ссылки</option>
							<option value="with" <?php if ($legal_links === 1) echo "selected='selected'"; ?>>Только с 2257 ссылкой</option>
							<option value="without" <?php if ($legal_links === -1) echo "selected='selected'"; ?>>Только без 2257 ссылки</option>
						</select>
						<select name="category" id="category">
							<option value="0">Все категории</option>
							<?php $default->AllTagsToString("<option value=\"#TAG_ID#\" #SELECTED#>#TAG#</option>", $category); ?>
						</select>
						<select name="affiliate_program_filter" id="affiliate_program_filter">
							<option value="0">Все affiliate programs</option>
							<?php if ($affiliatePrograms) {
								foreach ($affiliatePrograms as $affiliateProgram) {
									$selected = ($affiliate_program_filter && (int)$affiliate_program_filter === (int)$affiliateProgram['affiliate_program_id']) ? " selected='selected'" : '';
							?>
							<option value="<?=$affiliateProgram['affiliate_program_id']?>"<?=$selected?>><?=htmlspecialchars($affiliateProgram['affiliate_program_name'], ENT_QUOTES, 'UTF-8')?></option>
							<?php
								}
							} ?>
						</select>
						<input type="submit" value="Показать платники" name="show_paysites" id="show_paysites" />
						<input type="text" id="paysites-live-search" class="paysites-live-search" placeholder="Фильтр по названию платника..." autocomplete="off" />
						<input type="text" id="paysites-program-search" class="paysites-live-search" placeholder="Фильтр по имени программы..." autocomplete="off" />
					</form>

					<div class="paysites-summary">
						<div>Строк в текущей выборке: <strong id="paysites-visible-count"><?= isset($paysites) && is_array($paysites) ? count($paysites) : 0 ?></strong></div>
						<div><a href="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>&amp;query=add">Add paysite</a></div>
					</div>

					<div class="paysites-table-wrap">
						<table class="paysites-table" id="paysites-table">
							<thead>
								<tr>
									<th class="paysites-sortable" data-column-index="0" data-sort-type="number">ID <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="1" data-sort-type="text">Name <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="2" data-sort-type="text">Affiliate program <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="3" data-sort-type="text">Niche <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="4" data-sort-type="text">Category <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="5" data-sort-type="number">Bitrate <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="6" data-sort-type="number">2257 <span class="paysites-sort-indicator"></span></th>
									<th class="paysites-sortable" data-column-index="7" data-sort-type="number">Updated <span class="paysites-sort-indicator"></span></th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php if ($paysites_html_block !== '') { ?>
									<?=$paysites_html_block?>
								<?php } else { ?>
									<tr id="paysites-empty-row">
										<td colspan="9" class="paysites-empty">Платники по текущему фильтру не найдены.</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>

					<div class="menu">
						<hr>
					</div>
				</div>

				<script type="text/javascript">
					(function () {
						var searchInput = document.getElementById('paysites-live-search');
						var programSearchInput = document.getElementById('paysites-program-search');
						var table = document.getElementById('paysites-table');
						if (!searchInput || !programSearchInput || !table) {
							return;
						}

						var tbody = table.querySelector('tbody');
						var countBlock = document.getElementById('paysites-visible-count');
						var emptyRow = document.getElementById('paysites-empty-row');
						var headers = table.querySelectorAll('.paysites-sortable');
						var currentSort = {
							index: 0,
							direction: 'asc',
							type: 'number'
						};

						function getRows() {
							return Array.prototype.slice.call(tbody.querySelectorAll('tr.paysites-row'));
						}

						function updateCount() {
							var visible = 0;
							getRows().forEach(function (row) {
								if (row.style.display !== 'none') {
									visible += 1;
								}
							});
							countBlock.textContent = visible;
							if (emptyRow) {
								emptyRow.style.display = visible === 0 ? '' : 'none';
							}
						}

						function filterRows() {
							var query = searchInput.value.toLowerCase().trim();
							var programQuery = programSearchInput.value.toLowerCase().trim();
							getRows().forEach(function (row) {
								var name = row.getAttribute('data-name') || '';
								var programName = row.getAttribute('data-program') || '';
								var nameMatches = query === '' || name.indexOf(query) !== -1;
								var programMatches = programQuery === '' || programName.indexOf(programQuery) !== -1;
								row.style.display = nameMatches && programMatches ? '' : 'none';
							});
							updateCount();
						}

						function getCellSortValue(row, columnIndex) {
							var cell = row.cells[columnIndex];
							if (!cell) {
								return '';
							}
							return cell.getAttribute('data-sort') || cell.textContent || '';
						}

						function sortRows(columnIndex, sortType, direction) {
							var rows = getRows();
							rows.sort(function (a, b) {
								var aValue = getCellSortValue(a, columnIndex);
								var bValue = getCellSortValue(b, columnIndex);

								if (sortType === 'number') {
									aValue = parseFloat(aValue || '0');
									bValue = parseFloat(bValue || '0');
								} else {
									aValue = aValue.toLowerCase();
									bValue = bValue.toLowerCase();
								}

								if (aValue < bValue) {
									return direction === 'asc' ? -1 : 1;
								}
								if (aValue > bValue) {
									return direction === 'asc' ? 1 : -1;
								}
								return 0;
							});

							rows.forEach(function (row) {
								tbody.appendChild(row);
							});

							headers.forEach(function (header) {
								var indicator = header.querySelector('.paysites-sort-indicator');
								if (!indicator) {
									return;
								}
								if (parseInt(header.getAttribute('data-column-index'), 10) === columnIndex) {
									indicator.textContent = direction === 'asc' ? '▲' : '▼';
								} else {
									indicator.textContent = '';
								}
							});
						}

						headers.forEach(function (header) {
							header.addEventListener('click', function () {
								var columnIndex = parseInt(header.getAttribute('data-column-index'), 10);
								var sortType = header.getAttribute('data-sort-type') || 'text';
								var direction = 'asc';
								if (currentSort.index === columnIndex) {
									direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
								}
								currentSort = {
									index: columnIndex,
									direction: direction,
									type: sortType
								};
								sortRows(columnIndex, sortType, direction);
								filterRows();
							});
						});

						searchInput.addEventListener('input', filterRows);
						programSearchInput.addEventListener('input', filterRows);
						sortRows(currentSort.index, currentSort.type, currentSort.direction);
						filterRows();
					})();
				</script>

	<?php
			}
		}
	}
