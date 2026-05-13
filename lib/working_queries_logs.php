<?php 
if (!function_exists('query_diag_h')) {
	function query_diag_h($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	function query_diag_int_filter($name) {
		if (!isset($_GET[$name]) || $_GET[$name] === '') {
			return false;
		}
		return (int)$_GET[$name];
	}

	function query_diag_enum_filter($name, $allowed) {
		if (!isset($_GET[$name]) || $_GET[$name] === '') {
			return false;
		}
		$value = (string)$_GET[$name];
		return in_array($value, $allowed, true) ? $value : false;
	}

	function query_diag_time($timestamp) {
		$timestamp = (int)$timestamp;
		return $timestamp > 0 ? date("Y-m-d H:i:s", $timestamp) : '&mdash;';
	}

	function query_diag_age($timestamp) {
		$timestamp = (int)$timestamp;
		if ($timestamp <= 0) {
			return '&mdash;';
		}
		$seconds = max(0, time() - $timestamp);
		if ($seconds < 60) {
			return $seconds . ' сек';
		}
		if ($seconds < 3600) {
			return floor($seconds / 60) . ' мин';
		}
		if ($seconds < 86400) {
			return floor($seconds / 3600) . ' ч';
		}
		return floor($seconds / 86400) . ' д';
	}

	function query_diag_reset_url() {
		return 'index.php?act=queries&amp;type=show_cache_query';
	}

	function query_diag_limit() {
		$limit = isset($_GET['q_limit']) ? (int)$_GET['q_limit'] : 100;
		if (!in_array($limit, array(50, 100, 250, 500), true)) {
			$limit = 100;
		}
		return $limit;
	}

	function query_diag_fetch_gallery_changes($limit) {
		$result = array();
		$db = DB::get();
		if (!$db) {
			return $result;
		}

		$where = array();
		if (($gal_id = query_diag_int_filter('g_gal_id')) !== false) {
			$where[] = "gal_id = ".$gal_id;
		}
		if (($site_id = query_diag_int_filter('g_site_id')) !== false) {
			$where[] = "site_id = ".$site_id;
		}
		if (($item_type = query_diag_enum_filter('g_item_type', array('tag','model','source','gallery','image'))) !== false) {
			$where[] = "item_type = '".$db->real_escape_string($item_type)."'";
		}
		if (($change_type = query_diag_enum_filter('g_change_type', array('added','removed','changed'))) !== false) {
			$where[] = "change_type = '".$db->real_escape_string($change_type)."'";
		}
		$state = query_diag_enum_filter('g_state', array('pending','processed','error'));
		if ($state == 'pending') {
			$where[] = "processed = 0 AND error = 0";
		} elseif ($state == 'processed') {
			$where[] = "processed = 1 AND error = 0";
		} elseif ($state == 'error') {
			$where[] = "error != 0";
		}

		$sql = "SELECT id, gal_id, site_id, item_type, change_type, item_id, processed, added_on, updated_on, error, error_msg
				FROM galleries_changes_query";
		if ($where) {
			$sql .= " WHERE ".implode(" AND ", $where);
		}
		$sql .= " ORDER BY added_on ASC LIMIT ".(int)$limit;

		$stmt = $db->prepare($sql);
		if ($stmt && $stmt->execute()) {
			$id = $gal_id = $site_id = $item_id = $processed = $added_on = $updated_on = $error = null;
			$item_type = $change_type = $error_msg = null;
			$stmt->bind_result($id, $gal_id, $site_id, $item_type, $change_type, $item_id, $processed, $added_on, $updated_on, $error, $error_msg);
			while($stmt->fetch()) {
				$result[] = compact("id", "gal_id", "site_id", "item_type", "change_type", "item_id", "processed", "added_on", "updated_on", "error", "error_msg");
			}
			$stmt->close();
		}
		return $result;
	}

	function query_diag_fetch_site_cache($limit) {
		$result = array();
		$db = DB::get();
		if (!$db) {
			return $result;
		}

		$where = array();
		if (($gal_id = query_diag_int_filter('c_gal_id')) !== false) {
			$where[] = "gal_id = ".$gal_id;
		}
		if (($site_id = query_diag_int_filter('c_site_id')) !== false) {
			$where[] = "site_id = ".$site_id;
		}
		if (($cache_server_id = query_diag_int_filter('c_cache_server_id')) !== false) {
			$where[] = "cache_server_id = ".$cache_server_id;
		}
		if (($item_type = query_diag_enum_filter('c_item_type', array('tag','model','source','gallery'))) !== false) {
			$where[] = "item_type = '".$db->real_escape_string($item_type)."'";
		}
		if (($change_type = query_diag_enum_filter('c_change_type', array('added','removed','changed'))) !== false) {
			$where[] = "change_type = '".$db->real_escape_string($change_type)."'";
		}
		if (($gal_type = query_diag_enum_filter('c_gal_type', array('none','pics','movies','gif'))) !== false) {
			$where[] = "gal_type = '".$db->real_escape_string($gal_type)."'";
		}
		$state = query_diag_enum_filter('c_state', array('pending','error'));
		if ($state == 'pending') {
			$where[] = "error = 0";
		} elseif ($state == 'error') {
			$where[] = "error != 0";
		}

		$sql = "SELECT id, site_id, cache_server_id, gal_id, gal_local_id, gal_type, item_type, change_type, item_id, added_on, updated_on, error, error_msg
				FROM sites_cache_query";
		if ($where) {
			$sql .= " WHERE ".implode(" AND ", $where);
		}
		$sql .= " ORDER BY added_on ASC LIMIT ".(int)$limit;

		$stmt = $db->prepare($sql);
		if ($stmt && $stmt->execute()) {
			$id = $site_id = $cache_server_id = $gal_id = $gal_local_id = $item_id = $added_on = $updated_on = $error = null;
			$gal_type = $item_type = $change_type = $error_msg = null;
			$stmt->bind_result($id, $site_id, $cache_server_id, $gal_id, $gal_local_id, $gal_type, $item_type, $change_type, $item_id, $added_on, $updated_on, $error, $error_msg);
			while($stmt->fetch()) {
				$result[] = compact("id", "site_id", "cache_server_id", "gal_id", "gal_local_id", "gal_type", "item_type", "change_type", "item_id", "added_on", "updated_on", "error", "error_msg");
			}
			$stmt->close();
		}
		return $result;
	}

	function query_diag_fetch_cdn($limit) {
		$result = array();
		$db = DB::get();
		if (!$db) {
			return $result;
		}

		$where = array();
		if (($gal_id = query_diag_int_filter('cdn_gal_id')) !== false) {
			$where[] = "gal_id = ".$gal_id;
		}
		if (($status = query_diag_enum_filter('cdn_status', array('new','ok','delete','request_sent','request_failed','error'))) !== false) {
			$where[] = "file_status = '".$db->real_escape_string($status)."'";
		}

		$sql = "SELECT gal_id, file_status, sync_added_on, status_updated_on, file_size, error_message
				FROM cdn_sync_videos";
		if ($where) {
			$sql .= " WHERE ".implode(" AND ", $where);
		}
		$sql .= " ORDER BY sync_added_on DESC LIMIT ".(int)$limit;

		$stmt = $db->prepare($sql);
		if ($stmt && $stmt->execute()) {
			$gal_id = $sync_added_on = $status_updated_on = $file_size = null;
			$file_status = $error_message = null;
			$stmt->bind_result($gal_id, $file_status, $sync_added_on, $status_updated_on, $file_size, $error_message);
			while($stmt->fetch()) {
				$result[] = compact("gal_id", "file_status", "sync_added_on", "status_updated_on", "file_size", "error_message");
			}
			$stmt->close();
		}
		return $result;
	}

	function query_diag_fetch_video_preview_jobs($limit) {
		$result = array();
		$db = DB::get();
		if (!$db) {
			return $result;
		}

		$where = array();
		if (($job_id = query_diag_int_filter('vp_job_id')) !== false) {
			$where[] = "VPJ.id = " . $job_id;
		}
		if (($gal_id = query_diag_int_filter('vp_gal_id')) !== false) {
			$where[] = "VPJ.gal_id = " . $gal_id;
		}
		if (($job_status = query_diag_enum_filter('vp_job_status', array('new','processing','done','error'))) !== false) {
			$where[] = "VPJ.job_status = '" . $db->real_escape_string($job_status) . "'";
		}
		if (($callback_status = query_diag_enum_filter('vp_callback_status', array('none','pending','sent','partial','error'))) !== false) {
			$where[] = "VPJ.callback_status = '" . $db->real_escape_string($callback_status) . "'";
		}

		$sql = "SELECT
					VPJ.id,
					VPJ.gal_id,
					VPJ.preview_id,
					VPJ.job_status,
					VPJ.callback_status,
					VPJ.preview_format,
					VPJ.requested_on,
					VPJ.started_on,
					VPJ.finished_on,
					VPJ.worker_ip,
					VPJ.attempts,
					VPJ.error_message,
					GVP.preview_status,
					GVP.generated_on,
					(SELECT COUNT(*) FROM video_preview_job_callbacks VPC WHERE VPC.job_id = VPJ.id) AS callbacks_total,
					(SELECT COUNT(*) FROM video_preview_job_callbacks VPC WHERE VPC.job_id = VPJ.id AND VPC.callback_status = 'pending') AS callbacks_pending,
					(SELECT COUNT(*) FROM video_preview_job_callbacks VPC WHERE VPC.job_id = VPJ.id AND VPC.callback_status = 'sent') AS callbacks_sent,
					(SELECT COUNT(*) FROM video_preview_job_callbacks VPC WHERE VPC.job_id = VPJ.id AND VPC.callback_status = 'error') AS callbacks_error
				FROM video_preview_jobs VPJ
				LEFT JOIN galleries_video_previews GVP ON GVP.gal_id = VPJ.gal_id";
		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}
		$sql .= " ORDER BY VPJ.requested_on DESC, VPJ.id DESC LIMIT " . (int)$limit;

		$stmt = $db->prepare($sql);
		if ($stmt && $stmt->execute()) {
			$id = $gal_id = $preview_id = $requested_on = $started_on = $finished_on = $attempts = $generated_on = 0;
			$callbacks_total = $callbacks_pending = $callbacks_sent = $callbacks_error = 0;
			$job_status = $callback_status = $preview_format = $worker_ip = $error_message = $preview_status = null;
			$stmt->bind_result(
				$id,
				$gal_id,
				$preview_id,
				$job_status,
				$callback_status,
				$preview_format,
				$requested_on,
				$started_on,
				$finished_on,
				$worker_ip,
				$attempts,
				$error_message,
				$preview_status,
				$generated_on,
				$callbacks_total,
				$callbacks_pending,
				$callbacks_sent,
				$callbacks_error
			);
			while ($stmt->fetch()) {
				$result[] = compact(
					"id",
					"gal_id",
					"preview_id",
					"job_status",
					"callback_status",
					"preview_format",
					"requested_on",
					"started_on",
					"finished_on",
					"worker_ip",
					"attempts",
					"error_message",
					"preview_status",
					"generated_on",
					"callbacks_total",
					"callbacks_pending",
					"callbacks_sent",
					"callbacks_error"
				);
			}
			$stmt->close();
		}

		return $result;
	}
}

if(isset($_GET['clear_grabber_query'])) {
	if(clearGrabQuery()) {
		echo "Очередь граббера очищена<br>";
	} else {
		echo "<b>Ошибка! Очередь граббера не очищена<b><br>";
	}
}
if (isset($_GET['type']) && $_GET['type'] == 'grab') { 
	$galls = new Galleries($db->_db);
	if(isset($_GET['process_gallery_by_id'])) {
		$galls->processGalleryById($_GET['process_gallery_by_id']);
	}
	?>

		Очередь граббера<br />
		<a href="index.php?act=queries&amp;type=grab&amp;clear_grabber_query=1" onclick="return confirm('Очистить очередь?');">Очистить очередь граббера</a>
<?php
		
    	if (isset($_GET['to_merge'])) $mainQuery = $galls->showQuery('to_merge');
    	else $mainQuery = $galls->showQuery();
	    if (is_array($mainQuery)) {
	        foreach ($mainQuery as $gal_id => $gallery) {
?>
				<div style="display: block; border: 1px solid #666; margin: 2px; width: 1100px; height: 27px; padding: 3px;" id="grab_query_element_<?=$gal_id?>">
					<div style="display: block-inline; width: 35px; margin: 5px; float: left">
			        	<a target="_blank" href="index.php?act=galleries&amp;galid=<?=$gal_id?>"><?=$gal_id?></a> 
			        </div>
			        <div style="display: block-inline; width: 700px; margin: 5px; float: left; text-align: left;">
			        	| Gal satus: <strong><?=$gallery['gal_status']?></strong> | 
			        	Gal type: <strong><?=$gallery['gal_type']?></strong>, Добавлена в рбаоту: <?=date("Y-m-d, h:i",$gallery['added'])?>
<?php            
						if ($gallery['gal_status'] == 'to_merge') {
							echo "Оригинальный ID#".$gallery['to_merge']['gal_id'];
							if (isset($gallery['to_merge']['status'])) {
								if (preg_match("#^(to_mpeg_fail|merging_fail|error)$#", $gallery['to_merge']['status'])) {
									echo ", <font color=\"red\">Ошибка объединения!</font> Статус: <strong>".$gallery['to_merge']['status']."</strong>, <a href=\"index.php?act=queries&amp;fix_to_merge=".$gal_id."\">попытаться сбросить</a>?";
								} else echo ", внутренний статус: <strong>".$gallery['to_merge']['status']."</strong>";
							}
							if (isset($gallery['to_merge']['original_status']))  echo ", pre-merge status: <strong>".$gallery['to_merge']['original_status']."</strong>";
						}

?>
						<a href="index.php?act=queries&amp;type=grab&amp;process_gallery_by_id=<?=$gal_id?>">Force grab</a>
					</div>
					<?php if($gallery['gal_status'] == 'new') { ?>
					<div style="display: block-inline; width: 310px; float: right">
						<input style="width: 140px; float: right;" type="button" value="Нах из очереди" onclick="remove_from_grab_query(<?=$gal_id?>)">
						<div style="clear: both;"></div>
					</div>
					<?php } ?>
				</div>
				<div style="clear: both;"></div>
<?php				
	        }
	    }	
    } elseif(isset($_GET['type']) && $_GET['type'] == 'descs') {
    	$writer_query = new WritersQuery();
    	$add_days = false;
    	$site_id = false;
    	if(isset($_REQUEST['update_query_date'])) {
    		if(isset($_REQUEST['add_days'])) {
    			$add_days = $_REQUEST['add_days'];
    			if(isset($_GET['site_id'])) {
	    			$site_id = $_REQUEST['site_id'];
	    		}

	    		$writer_query->moveWholeQueryByDays($add_days, $site_id);
    		} else {
    			echo "<h1>No days count was set, can't move query!</h1>";
    		}
    		
    	}
    	
    	$main_query = $writer_query->getQuery();
    	if ($main_query && is_array($main_query)) {
			$queried_sites = array();
    		$queried_sites = $writer_query->getQueriedSites();
?>    		
      <form enctype="multipart/form-data" action="index.php?<?=http_build_query($_GET)?>" method="post" id="update_query_date">
      	<select name="site_id">
      		<option value="0">All</option>
			<?php
				foreach($queried_sites as $site) { ?>
				<option value="<?=$site['site_id']?>"<?=($site['site_id'] == $site_id) ? ' selected' : false;?>><?=$site['site_name']?></option>
			<?php   } ?>
      </select> 
      <select name="add_days">
        <?php
        for($day=0; $day<60; $day++){ ?>
          <option value="<?=$day?>"<?=($day == $add_days) ? ' selected' : false;?>><?=$day?></option>
<?php   }
?>
      </select>        
      <input type="submit" value="Перенести очередь" name="update_query_date" />
      </form>  
<?php
    		$doubles_array = array();
    		foreach($main_query as $query_id => $query_element) {
    			/*
							$result[$id]['id'] = $id;
							$result[$id]['gal_id'] = $gal_id;
							$result[$id]['site_id'] = $site_id;
							$result[$id]['main_thumb'] = $main_thumb;
							$result[$id]['title'] = $title;
							$result[$id]['language'] = $language;
							$result[$id]['deadline'] = $deadline;
							$result[$id]['writer_id'] = $writer_id;
    			*/
				$id = false;
				$gal_id = false;
				$site_id = false;
				$main_thumb = false;
				$title = false;
				$language = false;
				$deadline = false;
				$writer_id = false;
				extract($query_element);
				// var_dump($query_element);

?>
				<div id="descs_query_element_<?=$query_id?>" style="display: block; border: 1px solid #666; margin: 2px; width: 1100px; height: 30px; padding: 3px; font-size: 16px;">
					<?php if($id && $gal_id && $site_id && $language) { 
						$needle = $site_id.":".$gal_id;
						if (in_array($needle, $doubles_array)) {
							$double_element = true;
						}
						else { $doubles_array[] = $needle; $double_element = false; }
					?>
					<div style="display: block-inline; width: 240px; margin: 5px; float: left; text-align: left; <?php if($double_element) { ?>border: red solid 1px;<?php } ?>">

						Site ID: <a target="_blank" href="index.php?act=sites&amp;site=<?=$site_id?>"><?=$site_id?></a> 
			        	Gal ID: <a target="_blank" href="index.php?act=galleries&amp;galid=<?=$gal_id?>"><?=$gal_id?></a> 
			        </div>
			        <div style="display: block-inline; width: 400px; margin: 5px; float: left">
			        	Queried on: <?=date("Y-m-d, h:i",$deadline)?>, 
			        	Language: <strong><?=$language?></strong>
					</div>
					<div style="display: block-inline; width: 210px; float: right">
						<input class="make_gallery_button" style="width: 180px;" type="button" value="Удалить из очереди" onclick="remove_from_descs_query(<?=$query_id?>)">
					</div>
					<?php } else { echo "В очереди есть ошибка, проверить таблицу"; } ?>
				</div>
				<div style="clear: both;"></div>
<?php							
    		}
    	} else {
    		echo "<h2>Очередь тайтлов пустая</h2>";
    	}
    } elseif(isset($_GET['type']) && $_GET['type'] == 'make_galleries') {
    	$galleries_query = getFullMakeQuery();
    	if ($galleries_query && is_array($galleries_query)) {
    		echo "<h3>".count($galleries_query)."</h3>";
    		$prev_site = false;
    		foreach($galleries_query as $query_id => $query_element) {
    			/*
							$result[$id]['id'] = $id;
							$result[$id]['gal_id'] = $gal_id;
							$result[$id]['site_id'] = $site_id;
							$result[$id]['main_thumb'] = $main_thumb;
							$result[$id]['title'] = $title;
							$result[$id]['language'] = $language;
							$result[$id]['deadline'] = $deadline;
							$result[$id]['writer_id'] = $writer_id;
    			*/
						
				$gal_id = false;
				$site_id = false;
				$main_thumb = false;
				$title = false;
				$gallery_unique = false;
				$query_on = false;
				$site_name = false;
				extract($query_element);
				// var_dump($query_element);
				if($site_id && $site_name && $site_id != $prev_site) {
					$prev_site = $site_id;
					?>
					<h1>Site ID: <?=$site_id?>, <?=strtoupper($site_name)?></h1>
					<?php
				}

?>
				<div id="make_query_element_<?=$site_id?>_<?=$gal_id?>" style="display: block; border: 1px solid #666; margin: 4px; width: 1100px; height: 30px; padding: 3px; font-size: 12px;">
					<?php if($gal_id && $site_id && $query_on) { ?>
					<div style="display: block-inline; width: 170px; margin: 5px; float: left; text-align: left;">
						Site ID: <a target="_blank" href="index.php?act=sites&amp;site=<?=$site_id?>"><?=$site_id?></a> 
			        	Gal ID: <a target="_blank" href="index.php?act=galleries&amp;galid=<?=$gal_id?>"><?=$gal_id?></a> 
			        </div>
			        <div style="display: block-inline; width: 700px; margin: 5px; float: left; text-align: left;">
			        	Queried on: <?=date("Y-m-d, h:i",$query_on)?>, 
			        	Title: <strong><?=$title?></strong>
					</div>
					<div style="display: block-inline; width: 210px; float: right">
						<input class="make_gallery_button" style="width: 180px;" type="button" value="Удалить из очереди" onclick="remove_from_make_query(<?=$site_id?>, <?=$gal_id?>)">
					</div>
					<?php } else { echo "В очереди есть ошибка, проверить таблицу"; } ?>
				</div>
				<div style="clear: both;"></div>
<?php							
    		}
    	} else {
    		echo "<h2>Очередь галер пустая</h2>";
    	}
    } elseif(isset($_GET['type']) && $_GET['type'] == 'show_cache_query') {
		$limit = query_diag_limit();
		$query_view = query_diag_enum_filter('q_view', array('changes','cache','cdn','preview'));
		if (!$query_view) {
			$query_view = 'changes';
		}
		$gallery_changes = ($query_view == 'changes') ? query_diag_fetch_gallery_changes($limit) : array();
		$site_cache = ($query_view == 'cache') ? query_diag_fetch_site_cache($limit) : array();
		$cdn_query = ($query_view == 'cdn') ? query_diag_fetch_cdn($limit) : array();
		$preview_jobs = ($query_view == 'preview') ? query_diag_fetch_video_preview_jobs($limit) : array();
?>
		<style>
			.query-dashboard { max-width: 1380px; margin: 0 auto; text-align: left; font-size: 13px; color: #222; }
			.query-dashboard h2 { margin: 24px 0 8px; font-size: 22px; }
			.query-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0 14px; }
			.query-tabs a { padding: 8px 12px; border: 1px solid #aeb4ba; background: #fff; color: #222; text-decoration: none; }
			.query-tabs a.active { background: #343a40; border-color: #343a40; color: #fff; }
			.query-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; padding: 10px; background: #f1f3f5; border: 1px solid #d5d9de; margin: 8px 0 12px; }
			.query-toolbar label { display: flex; flex-direction: column; gap: 3px; font-size: 11px; color: #555; }
			.query-toolbar input, .query-toolbar select { width: 112px; padding: 4px; border: 1px solid #aaa; background: #fff; }
			.query-toolbar button, .query-toolbar a { padding: 5px 9px; border: 1px solid #777; background: #fff; color: #222; text-decoration: none; cursor: pointer; }
			.query-count { margin: 0 0 8px; color: #555; }
			.query-table { width: 100%; border-collapse: collapse; table-layout: fixed; background: #fff; }
			.query-table th, .query-table td { border: 1px solid #d7d7d7; padding: 5px 6px; vertical-align: top; overflow-wrap: anywhere; }
			.query-table th { background: #e9ecef; font-weight: bold; }
			.query-table tr.query-error td { background: #ffe4e1; }
			.query-table tr.query-processed td { background: #fff3cd; }
			.query-table tr.query-ok td { background: #eef8ee; }
			.query-actions { display: flex; gap: 5px; flex-wrap: wrap; }
			.query-actions button { padding: 4px 7px; border: 1px solid #777; background: #fff; cursor: pointer; font-size: 12px; }
			.query-actions .delete { border-color: #9d2a2a; color: #9d2a2a; }
			.query-actions .reset { border-color: #2f6f9f; color: #2f6f9f; }
			.query-muted { color: #777; }
			.query-error-msg { color: #9d2a2a; font-weight: bold; }
		</style>
		<script>
			function queryPost(endpoint, payload, rowId, confirmText) {
				if (confirmText && !window.confirm(confirmText)) {
					return false;
				}
				var data = new URLSearchParams();
				for (var key in payload) {
					if (Object.prototype.hasOwnProperty.call(payload, key)) {
						data.append(key, payload[key]);
					}
				}
				fetch(endpoint, {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
					body: data.toString()
				}).then(function(response) {
					return response.json();
				}).then(function(result) {
					if (result.success) {
						var row = document.getElementById(rowId);
						if (row) {
							row.style.opacity = '0.35';
						}
					} else {
						alert(result.error || 'Ошибка операции');
					}
				}).catch(function() {
					alert('Ошибка запроса');
				});
				return false;
			}
			function remove_from_gallery_change_query(id) {
				return queryPost('util/query.galleries_changes.remove_element.php', {id: id}, 'gallery_change_query_element_' + id, 'Удалить элемент из очереди изменений?');
			}
			function reset_gallery_change_query(id) {
				return queryPost('util/query.galleries_changes.reset_element.php', {id: id}, 'gallery_change_query_element_' + id, 'Сбросить ошибку/processed и вернуть элемент в работу?');
			}
			function clear_processed_gallery_changes() {
				if (!window.confirm('Удалить все обработанные изменения из galleries_changes_query? Ошибочные строки останутся.')) {
					return false;
				}
				fetch('util/query.galleries_changes.clear_processed.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
					body: 'clear=1'
				}).then(function(response) {
					return response.json();
				}).then(function(result) {
					if (result.success) {
						alert('Удалено строк: ' + result.deleted);
						window.location.reload();
					} else {
						alert(result.error || 'Ошибка очистки');
					}
				}).catch(function() {
					alert('Ошибка запроса');
				});
				return false;
			}
			function remove_from_cache_query(id) {
				return queryPost('util/query.cache.remove_element.php', {id: id}, 'cache_query_element_' + id, 'Удалить элемент из очереди кеша?');
			}
			function reset_cache_query(id) {
				return queryPost('util/query.cache.reset_element.php', {id: id}, 'cache_query_element_' + id, 'Сбросить ошибку кеша и попробовать заново?');
			}
			function remove_from_cdn_query(galId) {
				return queryPost('util/query.cdn_sync.remove_element.php', {gal_id: galId}, 'cdn_sync_query_' + galId, 'Удалить видео из CDN очереди?');
			}
			function reset_cdn_query(galId) {
				return queryPost('util/query.cdn_sync.reset_element.php', {gal_id: galId}, 'cdn_sync_query_' + galId, 'Вернуть CDN статус в new?');
			}
			function process_video_preview_job(jobId) {
				if (!window.confirm('Запустить обработку preview job сейчас?')) {
					return false;
				}
				var data = new URLSearchParams();
				data.append('job_id', jobId);
				fetch('util/query.video_preview.process_job.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
					body: data.toString()
				}).then(function(response) {
					return response.json();
				}).then(function(result) {
					if (result.success) {
						window.location.reload();
					} else {
						alert(result.error || 'Ошибка обработки preview job');
					}
				}).catch(function() {
					alert('Ошибка запроса');
				});
				return false;
			}
			function remove_video_preview_job(jobId) {
				if (!window.confirm('Удалить preview job из очереди?')) {
					return false;
				}
				var data = new URLSearchParams();
				data.append('job_id', jobId);
				fetch('util/query.video_preview.remove_job.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
					body: data.toString()
				}).then(function(response) {
					return response.json();
				}).then(function(result) {
					if (result.success) {
						var row = document.getElementById('video_preview_job_' + jobId);
						if (row) {
							row.style.transition = 'opacity 0.2s ease';
							row.style.opacity = '0';
							window.setTimeout(function() {
								if (row.parentNode) {
									row.parentNode.removeChild(row);
								}
							}, 220);
						}
					} else {
						alert(result.error || 'Ошибка удаления preview job');
					}
				}).catch(function() {
					alert('Ошибка запроса');
				});
				return false;
			}
		</script>
		<div class="query-dashboard">
			<h2>Очереди кеша и CDN</h2>
			<div class="query-tabs">
				<a class="<?=$query_view == 'changes' ? 'active' : ''?>" href="index.php?act=queries&amp;type=show_cache_query&amp;q_view=changes&amp;q_limit=<?=$limit?>">Изменения галерей</a>
				<a class="<?=$query_view == 'cache' ? 'active' : ''?>" href="index.php?act=queries&amp;type=show_cache_query&amp;q_view=cache&amp;q_limit=<?=$limit?>">Redis cache</a>
				<a class="<?=$query_view == 'cdn' ? 'active' : ''?>" href="index.php?act=queries&amp;type=show_cache_query&amp;q_view=cdn&amp;q_limit=<?=$limit?>">CDN sync</a>
				<a class="<?=$query_view == 'preview' ? 'active' : ''?>" href="index.php?act=queries&amp;type=show_cache_query&amp;q_view=preview&amp;q_limit=<?=$limit?>">Video preview</a>
			</div>
			<form class="query-toolbar" method="get">
				<input type="hidden" name="act" value="queries">
				<input type="hidden" name="type" value="show_cache_query">
				<input type="hidden" name="q_view" value="<?=$query_view?>">
				<label>Лимит
					<select name="q_limit">
						<?php foreach(array(50,100,250,500) as $limit_option) { ?>
							<option value="<?=$limit_option?>"<?=$limit_option == $limit ? ' selected' : ''?>><?=$limit_option?></option>
						<?php } ?>
					</select>
				</label>
				<button type="submit">Применить</button>
				<a href="<?=query_diag_reset_url()?>">Сбросить фильтры</a>
			</form>

			<?php if($query_view == 'changes') { ?>
			<h2>Galleries changes query</h2>
			<form class="query-toolbar" method="get">
				<input type="hidden" name="act" value="queries">
				<input type="hidden" name="type" value="show_cache_query">
				<input type="hidden" name="q_view" value="changes">
				<input type="hidden" name="q_limit" value="<?=$limit?>">
				<label>Gal ID <input type="number" name="g_gal_id" value="<?=query_diag_h(isset($_GET['g_gal_id']) ? $_GET['g_gal_id'] : '')?>"></label>
				<label>Site ID <input type="number" name="g_site_id" value="<?=query_diag_h(isset($_GET['g_site_id']) ? $_GET['g_site_id'] : '')?>"></label>
				<label>Item
					<select name="g_item_type">
						<option value="">Все</option>
						<?php foreach(array('tag','model','source','gallery','image') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['g_item_type']) && $_GET['g_item_type'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<label>Change
					<select name="g_change_type">
						<option value="">Все</option>
						<?php foreach(array('added','removed','changed') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['g_change_type']) && $_GET['g_change_type'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<label>Статус
					<select name="g_state">
						<option value="">Все</option>
						<option value="pending"<?=isset($_GET['g_state']) && $_GET['g_state'] == 'pending' ? ' selected' : ''?>>pending</option>
						<option value="processed"<?=isset($_GET['g_state']) && $_GET['g_state'] == 'processed' ? ' selected' : ''?>>processed</option>
						<option value="error"<?=isset($_GET['g_state']) && $_GET['g_state'] == 'error' ? ' selected' : ''?>>error</option>
					</select>
				</label>
				<button type="submit">Фильтр</button>
			</form>
			<div class="query-count">Показано: <?=count($gallery_changes)?>, общий счетчик старого метода: <?=(int)$sites_galleries->getChangesQueryCount()?></div>
			<div class="query-toolbar">
				<button type="button" onclick="return clear_processed_gallery_changes()">Удалить все processed</button>
				<span class="query-muted">Удаляет только обработанные строки без ошибки: processed = 1, error = 0</span>
			</div>
			<table class="query-table">
				<tr>
					<th style="width:55px;">ID</th>
					<th style="width:80px;">Site</th>
					<th style="width:90px;">Gal</th>
					<th style="width:85px;">Item</th>
					<th style="width:85px;">Change</th>
					<th style="width:80px;">Item ID</th>
					<th style="width:95px;">State</th>
					<th>Time</th>
					<th>Error</th>
					<th style="width:150px;">Actions</th>
				</tr>
				<?php if($gallery_changes) { foreach($gallery_changes as $row) {
					$row_class = $row['error'] ? 'query-error' : ($row['processed'] ? 'query-processed' : 'query-ok');
				?>
					<tr id="gallery_change_query_element_<?=$row['id']?>" class="<?=$row_class?>">
						<td>#<?=$row['id']?></td>
						<td><a target="_blank" href="index.php?act=sites&amp;site=<?=$row['site_id']?>"><?=$row['site_id']?></a></td>
						<td><a target="_blank" href="index.php?act=galleries&amp;galid=<?=$row['gal_id']?>"><?=$row['gal_id']?></a></td>
						<td><?=query_diag_h($row['item_type'])?></td>
						<td><?=query_diag_h($row['change_type'])?></td>
						<td><?=$row['item_id']?></td>
						<td><?=$row['error'] ? 'error' : ($row['processed'] ? 'processed' : 'pending')?></td>
						<td>
							<div>added: <?=query_diag_time($row['added_on'])?></div>
							<div>updated: <?=query_diag_time($row['updated_on'])?></div>
							<div class="query-muted">age: <?=query_diag_age($row['added_on'])?></div>
						</td>
						<td class="<?=$row['error'] ? 'query-error-msg' : 'query-muted'?>"><?=query_diag_h($row['error_msg'])?></td>
						<td>
							<div class="query-actions">
								<button class="reset" onclick="return reset_gallery_change_query(<?=$row['id']?>)">Сброс</button>
								<button class="delete" onclick="return remove_from_gallery_change_query(<?=$row['id']?>)">Удалить</button>
							</div>
						</td>
					</tr>
				<?php }} else { ?>
					<tr><td colspan="10">Очередь изменений пуста по выбранным фильтрам</td></tr>
				<?php } ?>
			</table>
			<?php } ?>

			<?php if($query_view == 'cache') { ?>
			<h2>Sites cache query</h2>
			<form class="query-toolbar" method="get">
				<input type="hidden" name="act" value="queries">
				<input type="hidden" name="type" value="show_cache_query">
				<input type="hidden" name="q_view" value="cache">
				<input type="hidden" name="q_limit" value="<?=$limit?>">
				<label>Gal ID <input type="number" name="c_gal_id" value="<?=query_diag_h(isset($_GET['c_gal_id']) ? $_GET['c_gal_id'] : '')?>"></label>
				<label>Site ID <input type="number" name="c_site_id" value="<?=query_diag_h(isset($_GET['c_site_id']) ? $_GET['c_site_id'] : '')?>"></label>
				<label>Cache server <input type="number" name="c_cache_server_id" value="<?=query_diag_h(isset($_GET['c_cache_server_id']) ? $_GET['c_cache_server_id'] : '')?>"></label>
				<label>Gal type
					<select name="c_gal_type">
						<option value="">Все</option>
						<?php foreach(array('none','pics','movies','gif') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['c_gal_type']) && $_GET['c_gal_type'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<label>Item
					<select name="c_item_type">
						<option value="">Все</option>
						<?php foreach(array('tag','model','source','gallery') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['c_item_type']) && $_GET['c_item_type'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<label>Change
					<select name="c_change_type">
						<option value="">Все</option>
						<?php foreach(array('added','removed','changed') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['c_change_type']) && $_GET['c_change_type'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<label>Статус
					<select name="c_state">
						<option value="">Все</option>
						<option value="pending"<?=isset($_GET['c_state']) && $_GET['c_state'] == 'pending' ? ' selected' : ''?>>pending</option>
						<option value="error"<?=isset($_GET['c_state']) && $_GET['c_state'] == 'error' ? ' selected' : ''?>>error</option>
					</select>
				</label>
				<button type="submit">Фильтр</button>
			</form>
			<div class="query-count">Показано: <?=count($site_cache)?></div>
			<table class="query-table">
				<tr>
					<th style="width:55px;">ID</th>
					<th style="width:70px;">Site</th>
					<th style="width:70px;">Redis</th>
					<th style="width:90px;">Gal</th>
					<th style="width:75px;">Local</th>
					<th style="width:70px;">Type</th>
					<th style="width:85px;">Item</th>
					<th style="width:85px;">Change</th>
					<th style="width:75px;">Item ID</th>
					<th>Time</th>
					<th>Error</th>
					<th style="width:150px;">Actions</th>
				</tr>
				<?php if($site_cache) { foreach($site_cache as $row) {
					$row_class = $row['error'] ? 'query-error' : 'query-ok';
				?>
					<tr id="cache_query_element_<?=$row['id']?>" class="<?=$row_class?>">
						<td>#<?=$row['id']?></td>
						<td><a target="_blank" href="index.php?act=sites&amp;site=<?=$row['site_id']?>"><?=$row['site_id']?></a></td>
						<td>#<?=$row['cache_server_id']?></td>
						<td><a target="_blank" href="index.php?act=galleries&amp;galid=<?=$row['gal_id']?>"><?=$row['gal_id']?></a></td>
						<td><?=$row['gal_local_id']?></td>
						<td><?=query_diag_h($row['gal_type'])?></td>
						<td><?=query_diag_h($row['item_type'])?></td>
						<td><?=query_diag_h($row['change_type'])?></td>
						<td><?=$row['item_id']?></td>
						<td>
							<div>added: <?=query_diag_time($row['added_on'])?></div>
							<div>updated: <?=query_diag_time($row['updated_on'])?></div>
							<div class="query-muted">age: <?=query_diag_age($row['added_on'])?></div>
						</td>
						<td class="<?=$row['error'] ? 'query-error-msg' : 'query-muted'?>"><?=query_diag_h($row['error_msg'])?></td>
						<td>
							<div class="query-actions">
								<button class="reset" onclick="return reset_cache_query(<?=$row['id']?>)">Сброс</button>
								<button class="delete" onclick="return remove_from_cache_query(<?=$row['id']?>)">Удалить</button>
							</div>
						</td>
					</tr>
				<?php }} else { ?>
					<tr><td colspan="12">Очередь кеша пуста по выбранным фильтрам</td></tr>
				<?php } ?>
			</table>
			<?php } ?>

			<?php if($query_view == 'cdn') { ?>
			<h2>Gallery CDN storage sync query</h2>
			<form class="query-toolbar" method="get">
				<input type="hidden" name="act" value="queries">
				<input type="hidden" name="type" value="show_cache_query">
				<input type="hidden" name="q_view" value="cdn">
				<input type="hidden" name="q_limit" value="<?=$limit?>">
				<label>Gal ID <input type="number" name="cdn_gal_id" value="<?=query_diag_h(isset($_GET['cdn_gal_id']) ? $_GET['cdn_gal_id'] : '')?>"></label>
				<label>Status
					<select name="cdn_status">
						<option value="">Все</option>
						<?php foreach(array('new','ok','delete','request_sent','request_failed','error') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['cdn_status']) && $_GET['cdn_status'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<button type="submit">Фильтр</button>
			</form>
			<div class="query-count">Показано: <?=count($cdn_query)?></div>
			<table class="query-table">
				<tr>
					<th style="width:90px;">Gal</th>
					<th style="width:120px;">Status</th>
					<th>Time</th>
					<th style="width:110px;">File size</th>
					<th>Error</th>
					<th style="width:190px;">Actions</th>
				</tr>
				<?php if($cdn_query) { foreach($cdn_query as $row) {
					$row_class = in_array($row['file_status'], array('error','request_failed'), true) ? 'query-error' : ($row['file_status'] == 'ok' ? 'query-ok' : 'query-processed');
				?>
					<tr id="cdn_sync_query_<?=$row['gal_id']?>" class="<?=$row_class?>">
						<td><a target="_blank" href="index.php?act=galleries&amp;galid=<?=$row['gal_id']?>"><?=$row['gal_id']?></a></td>
						<td><?=query_diag_h($row['file_status'])?></td>
						<td>
							<div>added: <?=query_diag_time($row['sync_added_on'])?></div>
							<div>updated: <?=query_diag_time($row['status_updated_on'])?></div>
							<div class="query-muted">age: <?=query_diag_age($row['sync_added_on'])?></div>
						</td>
						<td><?=$row['file_size']?></td>
						<td class="<?=in_array($row['file_status'], array('error','request_failed'), true) ? 'query-error-msg' : 'query-muted'?>"><?=query_diag_h($row['error_message'])?></td>
						<td>
							<div class="query-actions">
								<button class="reset" onclick="return reset_cdn_query(<?=$row['gal_id']?>)">Сброс</button>
								<button class="delete" onclick="return remove_from_cdn_query(<?=$row['gal_id']?>)">Удалить</button>
								<?php if($row['file_status'] == "request_sent") { ?>
									<a href="index.php?act=cdn_query_check&amp;gal_id=<?=$row['gal_id']?>">Проверить</a>
								<?php } ?>
							</div>
						</td>
					</tr>
				<?php }} else { ?>
					<tr><td colspan="6">CDN очередь пуста по выбранным фильтрам</td></tr>
				<?php } ?>
			</table>
			<?php } ?>

			<?php if($query_view == 'preview') { ?>
			<h2>Video preview jobs</h2>
			<form class="query-toolbar" method="get">
				<input type="hidden" name="act" value="queries">
				<input type="hidden" name="type" value="show_cache_query">
				<input type="hidden" name="q_view" value="preview">
				<input type="hidden" name="q_limit" value="<?=$limit?>">
				<label>Job ID <input type="number" name="vp_job_id" value="<?=query_diag_h(isset($_GET['vp_job_id']) ? $_GET['vp_job_id'] : '')?>"></label>
				<label>Gal ID <input type="number" name="vp_gal_id" value="<?=query_diag_h(isset($_GET['vp_gal_id']) ? $_GET['vp_gal_id'] : '')?>"></label>
				<label>Job status
					<select name="vp_job_status">
						<option value="">Все</option>
						<?php foreach(array('new','processing','done','error') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['vp_job_status']) && $_GET['vp_job_status'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<label>Callback
					<select name="vp_callback_status">
						<option value="">Все</option>
						<?php foreach(array('none','pending','sent','partial','error') as $option) { ?>
							<option value="<?=$option?>"<?=isset($_GET['vp_callback_status']) && $_GET['vp_callback_status'] == $option ? ' selected' : ''?>><?=$option?></option>
						<?php } ?>
					</select>
				</label>
				<button type="submit">Фильтр</button>
			</form>
			<div class="query-count">Показано: <?=count($preview_jobs)?></div>
			<table class="query-table">
				<tr>
					<th style="width:60px;">Job</th>
					<th style="width:85px;">Gal</th>
					<th style="width:80px;">Preview</th>
					<th style="width:95px;">Job status</th>
					<th style="width:100px;">Preview status</th>
					<th style="width:95px;">Callback</th>
					<th style="width:110px;">Format</th>
					<th>Time</th>
					<th style="width:95px;">Worker</th>
					<th style="width:85px;">Attempts</th>
					<th style="width:120px;">Callbacks</th>
					<th>Error</th>
					<th style="width:110px;">Actions</th>
				</tr>
				<?php if($preview_jobs) { foreach($preview_jobs as $row) {
					$row_class = $row['job_status'] === 'error' ? 'query-error' : ($row['job_status'] === 'done' ? 'query-ok' : 'query-processed');
				?>
					<tr id="video_preview_job_<?=$row['id']?>" class="<?=$row_class?>">
						<td>#<?=$row['id']?></td>
						<td><a target="_blank" href="index.php?act=galleries&amp;galid=<?=$row['gal_id']?>"><?=$row['gal_id']?></a></td>
						<td><?=$row['preview_id'] ? '#'.$row['preview_id'] : '&mdash;'?></td>
						<td><?=query_diag_h($row['job_status'])?></td>
						<td><?=query_diag_h($row['preview_status'])?></td>
						<td><?=query_diag_h($row['callback_status'])?></td>
						<td><?=query_diag_h($row['preview_format'])?></td>
						<td>
							<div>requested: <?=query_diag_time($row['requested_on'])?></div>
							<div>started: <?=query_diag_time($row['started_on'])?></div>
							<div>finished: <?=query_diag_time($row['finished_on'])?></div>
							<div>generated: <?=query_diag_time($row['generated_on'])?></div>
							<div class="query-muted">age: <?=query_diag_age($row['requested_on'])?></div>
						</td>
						<td><?=query_diag_h($row['worker_ip'])?></td>
						<td><?=$row['attempts']?></td>
						<td>
							<div>total: <?=$row['callbacks_total']?></div>
							<div>pending: <?=$row['callbacks_pending']?></div>
							<div>sent: <?=$row['callbacks_sent']?></div>
							<div>error: <?=$row['callbacks_error']?></div>
						</td>
						<td class="<?=$row['job_status'] === 'error' ? 'query-error-msg' : 'query-muted'?>"><?=query_diag_h($row['error_message'])?></td>
						<td>
							<div class="query-actions">
								<button class="reset" onclick="return process_video_preview_job(<?=$row['id']?>)">Process</button>
								<button class="delete" onclick="return remove_video_preview_job(<?=$row['id']?>)">Удалить</button>
							</div>
						</td>
					</tr>
				<?php }} else { ?>
					<tr><td colspan="13">Очередь video preview пуста по выбранным фильтрам</td></tr>
				<?php } ?>
			</table>
			<?php } ?>
		</div>
<?php
    } else {

    }
?>
