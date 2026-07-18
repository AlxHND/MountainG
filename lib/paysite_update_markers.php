<?php

if (!function_exists('pum_h')) {
	function pum_h($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

$sources = new Sources($db->_db);
$markerTypes = $sources->getPaysiteUpdateMarkerTypes();
$message = '';
$messageType = 'ok';
$editingMarker = false;

if (!$userAuth->isAdmin()) {
	echo "<div style=\"padding:20px; color:#900;\">Недостаточно прав для управления маркерами апдейтов.</div>";
	return;
}

if (isset($_POST['save_update_marker'])) {
	$markerId = isset($_POST['marker_id']) ? (int)$_POST['marker_id'] : 0;
	$paysiteId = isset($_POST['paysite_id']) ? (int)$_POST['paysite_id'] : 0;
	$markerType = isset($_POST['marker_type']) ? trim((string)$_POST['marker_type']) : '';
	$updateTitle = isset($_POST['update_title']) ? trim((string)$_POST['update_title']) : '';
	$updatePageUrl = isset($_POST['update_page_url']) ? trim((string)$_POST['update_page_url']) : '';
	$updateInnerDate = isset($_POST['update_inner_date']) ? trim((string)$_POST['update_inner_date']) : '';

	try {
		$savedId = $sources->savePaysiteUpdateMarker($paysiteId, $markerType, $updateTitle, $updatePageUrl, $updateInnerDate, $markerId);
		$message = $markerId ? "Маркер #{$savedId} обновлен." : "Маркер #{$savedId} создан.";
	} catch (Exception $e) {
		$message = "Ошибка сохранения маркера: " . $e->getMessage();
		$messageType = 'error';
		$editingMarker = array(
			'id' => $markerId,
			'paysite_id' => $paysiteId,
			'marker_type' => $markerType,
			'update_title' => $updateTitle,
			'update_page_url' => $updatePageUrl,
			'update_inner_date' => $updateInnerDate,
			'paysite_name' => '',
		);
	}
}

if (isset($_POST['delete_update_marker']) && isset($_POST['marker_id'])) {
	$markerId = (int)$_POST['marker_id'];
	if ($sources->deletePaysiteUpdateMarker($markerId)) {
		$message = "Маркер #{$markerId} удален.";
	} else {
		$message = "Маркер #{$markerId} не удален.";
		$messageType = 'error';
	}
}

if (!$editingMarker && isset($_GET['edit_marker'])) {
	$editingMarker = $sources->getPaysiteUpdateMarkerById((int)$_GET['edit_marker']);
	if (!$editingMarker) {
		$message = "Маркер не найден.";
		$messageType = 'error';
	}
}

if (!$editingMarker && isset($_GET['paysite_id'], $_GET['marker_type'])) {
	$prefillPaysiteId = (int)$_GET['paysite_id'];
	$prefillMarkerType = trim((string)$_GET['marker_type']);
	$editingMarker = $sources->getPaysiteUpdateMarker($prefillPaysiteId, $prefillMarkerType);
	if (!$editingMarker && $prefillPaysiteId > 0 && isset($markerTypes[$prefillMarkerType])) {
		$paysiteInfo = $sources->getSource($prefillPaysiteId);
		$editingMarker = array(
			'id' => 0,
			'paysite_id' => $prefillPaysiteId,
			'marker_type' => $prefillMarkerType,
			'update_title' => '',
			'update_page_url' => '',
			'update_inner_date' => '',
			'paysite_name' => $paysiteInfo ? $paysiteInfo['name'] : '',
		);
	}
}

if (!$editingMarker) {
	$editingMarker = array(
		'id' => 0,
		'paysite_id' => isset($_GET['paysite_id']) ? (int)$_GET['paysite_id'] : 0,
		'marker_type' => isset($_GET['marker_type'], $markerTypes[$_GET['marker_type']]) ? $_GET['marker_type'] : 'latest',
		'update_title' => '',
		'update_page_url' => '',
		'update_inner_date' => '',
		'paysite_name' => '',
	);
}

$filterPaysiteId = isset($_GET['filter_paysite_id']) ? (int)$_GET['filter_paysite_id'] : (isset($_GET['paysite_id']) ? (int)$_GET['paysite_id'] : 0);
$filterMarkerType = isset($_GET['filter_marker_type'], $markerTypes[$_GET['filter_marker_type']]) ? $_GET['filter_marker_type'] : '';
$filterQuery = isset($_GET['filter_query']) ? trim((string)$_GET['filter_query']) : '';
$sortBy = isset($_GET['sort_by']) ? trim((string)$_GET['sort_by']) : 'updated_at';
$sortDir = (isset($_GET['sort_dir']) && strtolower((string)$_GET['sort_dir']) === 'asc') ? 'asc' : 'desc';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
if (!in_array($limit, array(50, 100, 250, 500), true)) {
	$limit = 100;
}

$markers = $sources->findPaysiteUpdateMarkers(array(
	'paysite_id' => $filterPaysiteId,
	'marker_type' => $filterMarkerType,
	'query' => $filterQuery,
	'sort_by' => $sortBy,
	'sort_dir' => $sortDir,
	'limit' => $limit,
));

$shortPaysites = $sources->getAllSourcesShort();
?>

<div style="width: 1450px; margin: 0 auto; text-align:left;">
	<div style="padding: 0 0 12px 0;">
		<a href="index.php?act=paysite_update_markers">Список маркеров</a>
		|
		<a href="index.php?act=paysites">К платникам</a>
	</div>

	<?php if ($message !== '') { ?>
		<div style="padding:10px 12px; margin-bottom:14px; border:1px solid <?=$messageType === 'error' ? '#d99' : '#bcd'?>; background: <?=$messageType === 'error' ? '#fff0f0' : '#f3fbff'?>;">
			<?=pum_h($message)?>
		</div>
	<?php } ?>

	<div style="float:left; width: 470px; margin-right: 20px;">
		<h3 style="margin-top:0;"><?= !empty($editingMarker['id']) ? 'Редактирование маркера' : 'Новый маркер' ?></h3>
		<form method="post" action="index.php?act=paysite_update_markers<?php if (!empty($editingMarker['id'])) { ?>&amp;edit_marker=<?=(int)$editingMarker['id']?><?php } ?>">
			<input type="hidden" name="marker_id" value="<?=(int)$editingMarker['id']?>">
			<table class="disclaim" cellpadding="4" cellspacing="2" width="100%">
				<tr>
					<td bgcolor="#e4e4e4" width="140">Paysite ID</td>
					<td bgcolor="#e4e4e4">
						<input type="text" name="paysite_id" value="<?=pum_h($editingMarker['paysite_id'])?>" style="width:100px;">
						<?php if (!empty($editingMarker['paysite_name'])) { ?>
							<span style="padding-left:8px; color:#666;"><?=pum_h($editingMarker['paysite_name'])?></span>
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td bgcolor="#e4e4e4">Тип маркера</td>
					<td bgcolor="#e4e4e4">
						<select name="marker_type">
							<?php foreach ($markerTypes as $typeKey => $typeLabel) { ?>
								<option value="<?=pum_h($typeKey)?>" <?=$editingMarker['marker_type'] === $typeKey ? 'selected' : ''?>><?=pum_h($typeLabel)?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr>
					<td bgcolor="#e4e4e4">Название апдейта</td>
					<td bgcolor="#e4e4e4"><input type="text" name="update_title" value="<?=pum_h($editingMarker['update_title'])?>" style="width:98%;"></td>
				</tr>
				<tr>
					<td bgcolor="#e4e4e4">Страница</td>
					<td bgcolor="#e4e4e4"><input type="text" name="update_page_url" value="<?=pum_h($editingMarker['update_page_url'])?>" style="width:98%;"></td>
				</tr>
				<tr>
					<td bgcolor="#e4e4e4">Внутренняя дата</td>
					<td bgcolor="#e4e4e4">
						<input type="text" name="update_inner_date" value="<?=pum_h($editingMarker['update_inner_date'])?>" style="width:180px;" placeholder="YYYY-MM-DD HH:MM:SS">
					</td>
				</tr>
			</table>
			<div style="padding-top:10px;">
				<input type="submit" name="save_update_marker" value="<?= !empty($editingMarker['id']) ? 'Сохранить' : 'Создать' ?>">
				<a href="index.php?act=paysite_update_markers" style="margin-left:10px;">Сбросить</a>
			</div>
		</form>
	</div>

	<div style="overflow:hidden;">
		<h3 style="margin-top:0;">Поиск и список</h3>
		<form method="get" action="index.php" style="padding:10px; background:#f6f6f6; border:1px solid #ddd; margin-bottom:12px;">
			<input type="hidden" name="act" value="paysite_update_markers">
			Paysite ID:
			<input type="text" name="filter_paysite_id" value="<?=pum_h($filterPaysiteId ?: '')?>" style="width:80px;">
			Тип:
			<select name="filter_marker_type">
				<option value="">Все</option>
				<?php foreach ($markerTypes as $typeKey => $typeLabel) { ?>
					<option value="<?=pum_h($typeKey)?>" <?=$filterMarkerType === $typeKey ? 'selected' : ''?>><?=pum_h($typeLabel)?></option>
				<?php } ?>
			</select>
			Поиск:
			<input type="text" name="filter_query" value="<?=pum_h($filterQuery)?>" style="width:220px;" placeholder="Название, страница, paysite">
			Сортировка:
			<select name="sort_by">
				<option value="updated_at" <?=$sortBy === 'updated_at' ? 'selected' : ''?>>Updated</option>
				<option value="inner_date" <?=$sortBy === 'inner_date' ? 'selected' : ''?>>Inner date</option>
				<option value="paysite_name" <?=$sortBy === 'paysite_name' ? 'selected' : ''?>>Paysite</option>
				<option value="marker_type" <?=$sortBy === 'marker_type' ? 'selected' : ''?>>Type</option>
			</select>
			<select name="sort_dir">
				<option value="desc" <?=$sortDir === 'desc' ? 'selected' : ''?>>DESC</option>
				<option value="asc" <?=$sortDir === 'asc' ? 'selected' : ''?>>ASC</option>
			</select>
			<select name="limit">
				<option value="50" <?=$limit === 50 ? 'selected' : ''?>>50</option>
				<option value="100" <?=$limit === 100 ? 'selected' : ''?>>100</option>
				<option value="250" <?=$limit === 250 ? 'selected' : ''?>>250</option>
				<option value="500" <?=$limit === 500 ? 'selected' : ''?>>500</option>
			</select>
			<input type="submit" value="Показать">
		</form>

		<table cellpadding="4" cellspacing="1" width="100%" style="background:#d8d8d8;">
			<tr style="background:#efefef;">
				<th width="40">ID</th>
				<th width="70">Paysite</th>
				<th width="150">Название</th>
				<th width="120">Тип</th>
				<th>Страница</th>
				<th width="150">Внутренняя дата</th>
				<th width="150">Updated</th>
				<th width="130">Действия</th>
			</tr>
			<?php if ($markers) { ?>
				<?php foreach ($markers as $marker) { ?>
					<tr style="background:#fff;">
						<td align="center"><?=(int)$marker['id']?></td>
						<td align="center">
							<strong><?=(int)$marker['paysite_id']?></strong><br>
							<span style="color:#666; font-size:11px;"><?=pum_h($marker['paysite_name'])?></span>
						</td>
						<td><?=pum_h($marker['update_title'])?></td>
						<td><?=isset($markerTypes[$marker['marker_type']]) ? pum_h($markerTypes[$marker['marker_type']]) : pum_h($marker['marker_type'])?></td>
						<td style="word-break: break-all;"><?=pum_h($marker['update_page_url'])?></td>
						<td align="center"><?=pum_h($marker['update_inner_date'])?></td>
						<td align="center"><?=pum_h($marker['updated_at'])?></td>
						<td align="center">
							<a href="index.php?act=paysite_update_markers&amp;edit_marker=<?=(int)$marker['id']?>">Edit</a>
							|
							<form method="post" action="index.php?act=paysite_update_markers" style="display:inline;" onsubmit="return confirm('Удалить маркер?');">
								<input type="hidden" name="marker_id" value="<?=(int)$marker['id']?>">
								<input type="submit" name="delete_update_marker" value="Delete" style="color:#900; background:none; border:none; cursor:pointer; text-decoration:underline;">
							</form>
						</td>
					</tr>
				<?php } ?>
			<?php } else { ?>
				<tr style="background:#fff;">
					<td colspan="8" align="center">Маркеры не найдены.</td>
				</tr>
			<?php } ?>
		</table>

		<?php if ($filterPaysiteId > 0 && isset($shortPaysites[$filterPaysiteId])) { ?>
			<div style="padding-top:10px; color:#666;">
				Быстрое редактирование для платника <strong><?=pum_h($shortPaysites[$filterPaysiteId]['paysite_name'])?></strong>:
				<a href="index.php?act=paysite_update_markers&amp;paysite_id=<?=$filterPaysiteId?>&amp;marker_type=latest">Последний по времени</a>
				|
				<a href="index.php?act=paysite_update_markers&amp;paysite_id=<?=$filterPaysiteId?>&amp;marker_type=backfill">Последний из старых</a>
			</div>
		<?php } ?>
	</div>

	<div style="clear:both;"></div>
</div>
