<?php

if (!function_exists('deleted_content_h')) {
	function deleted_content_h($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!$userAuth->isAdmin()) {
	echo "<div style=\"padding:20px; color:#900;\">Недостаточно прав для просмотра удаленного контента.</div>";
	return;
}

$siteId = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$globalId = isset($_GET['global_id']) ? (int)$_GET['global_id'] : 0;
$contentType = isset($_GET['content_type']) ? strtolower(trim((string)$_GET['content_type'])) : 'all';
$sort = (isset($_GET['sort']) && strtolower((string)$_GET['sort']) === 'asc') ? 'asc' : 'desc';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

if (!in_array($contentType, array('all', 'gallery', 'video'), true)) {
	$contentType = 'all';
}

if (!in_array($limit, array(50, 100, 250, 500), true)) {
	$limit = 100;
}

$rssWorker = new SelectTools();
$rows = $rssWorker->getDeletedContentRegistry(array(
	'site_id' => $siteId,
	'global_id' => $globalId,
	'content_type' => $contentType,
	'sort' => $sort,
	'limit' => $limit,
));

$feedSiteId = $siteId > 0 ? $siteId : 0;
?>
<div style="width: 1400px; margin: 0 auto; text-align: left;">
	<h2 style="margin: 0 0 18px 0;">Удаленный контент</h2>

	<form method="get" action="index.php" style="padding: 12px; border:1px solid #ccc; background:#f8f8f8; margin-bottom:16px;">
		<input type="hidden" name="act" value="deleted_content">
		<label style="margin-right: 12px;">
			Site ID<br>
			<input type="text" name="site_id" value="<?=deleted_content_h($siteId ?: '')?>" style="width:90px;">
		</label>
		<label style="margin-right: 12px;">
			Global ID<br>
			<input type="text" name="global_id" value="<?=deleted_content_h($globalId ?: '')?>" style="width:110px;">
		</label>
		<label style="margin-right: 12px;">
			Тип<br>
			<select name="content_type">
				<option value="all" <?=$contentType === 'all' ? 'selected' : ''?>>Все</option>
				<option value="gallery" <?=$contentType === 'gallery' ? 'selected' : ''?>>Gallery</option>
				<option value="video" <?=$contentType === 'video' ? 'selected' : ''?>>Video</option>
			</select>
		</label>
		<label style="margin-right: 12px;">
			Сортировка<br>
			<select name="sort">
				<option value="desc" <?=$sort === 'desc' ? 'selected' : ''?>>Сначала новые</option>
				<option value="asc" <?=$sort === 'asc' ? 'selected' : ''?>>Сначала старые</option>
			</select>
		</label>
		<label style="margin-right: 12px;">
			Лимит<br>
			<select name="limit">
				<option value="50" <?=$limit === 50 ? 'selected' : ''?>>50</option>
				<option value="100" <?=$limit === 100 ? 'selected' : ''?>>100</option>
				<option value="250" <?=$limit === 250 ? 'selected' : ''?>>250</option>
				<option value="500" <?=$limit === 500 ? 'selected' : ''?>>500</option>
			</select>
		</label>
		<button type="submit" style="margin-top:18px;">Показать</button>
		<a href="index.php?act=deleted_content" style="margin-left:10px;">Сбросить</a>
	</form>

	<?php if ($feedSiteId > 0) { ?>
	<div style="padding: 12px; border:1px solid #d8d8d8; background:#fcfcfc; margin-bottom:16px;">
		<div style="font-weight:bold; margin-bottom:8px;">Тестовые delete feeds для site_id=<?=$feedSiteId?></div>
		<div style="margin-bottom:6px;">
			Gallery XML:
			<a target="_blank" href="/rssfeeder.php?pwd=<?=defined('RSS_FEEDER_PASSWORD') ? urlencode((string)RSS_FEEDER_PASSWORD) : ''?>&amp;site=<?=$feedSiteId?>&amp;deleted_ids=gallery">open</a>
			|
			plain:
			<a target="_blank" href="/rssfeeder.php?pwd=<?=defined('RSS_FEEDER_PASSWORD') ? urlencode((string)RSS_FEEDER_PASSWORD) : ''?>&amp;site=<?=$feedSiteId?>&amp;deleted_ids=gallery&amp;format=plain">open</a>
		</div>
		<div>
			Video XML:
			<a target="_blank" href="/rssfeeder.php?pwd=<?=defined('RSS_FEEDER_PASSWORD') ? urlencode((string)RSS_FEEDER_PASSWORD) : ''?>&amp;site=<?=$feedSiteId?>&amp;deleted_ids=video">open</a>
			|
			plain:
			<a target="_blank" href="/rssfeeder.php?pwd=<?=defined('RSS_FEEDER_PASSWORD') ? urlencode((string)RSS_FEEDER_PASSWORD) : ''?>&amp;site=<?=$feedSiteId?>&amp;deleted_ids=video&amp;format=plain">open</a>
		</div>
	</div>
	<?php } else { ?>
	<div style="padding: 10px 12px; margin-bottom:16px; background:#fff8dc; border:1px solid #ead79c;">
		Для тестовых ссылок фида укажи <strong>Site ID</strong>.
	</div>
	<?php } ?>

	<table cellpadding="6" cellspacing="0" border="1" width="100%" style="border-collapse: collapse; background:#fff;">
		<tr style="background:#efefef;">
			<th>ID строки</th>
			<th>Удалено</th>
			<th>Site ID</th>
			<th>Global ID</th>
			<th>Local ID</th>
			<th>Тип</th>
			<th>URL</th>
		</tr>
		<?php if ($rows) { ?>
			<?php foreach ($rows as $row) { ?>
			<tr>
				<td><?=deleted_content_h($row['id'])?></td>
				<td><?=date('Y-m-d H:i:s', (int)$row['added_on'])?></td>
				<td><?=deleted_content_h($row['site_id'])?></td>
				<td><strong><?=deleted_content_h($row['gal_id'])?></strong></td>
				<td><?=deleted_content_h($row['gal_local_id'])?></td>
				<td><?=deleted_content_h($row['gal_type'] !== '' ? $row['gal_type'] : 'unknown')?></td>
				<td style="word-break: break-all;"><?=deleted_content_h($row['gal_url'])?></td>
			</tr>
			<?php } ?>
		<?php } else { ?>
			<tr>
				<td colspan="7" style="text-align:center; color:#666;">Записей не найдено.</td>
			</tr>
		<?php } ?>
	</table>
</div>
