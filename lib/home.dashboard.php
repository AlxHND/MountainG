<?php

function dashboard_format_bytes($bytes)
{
	$bytes = (float)$bytes;
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	$unitIndex = 0;

	while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
		$bytes /= 1024;
		$unitIndex++;
	}

	return round($bytes, $unitIndex === 0 ? 0 : 2) . ' ' . $units[$unitIndex];
}

function dashboard_parse_ini_size($value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return 0;
	}

	$lastChar = strtolower(substr($value, -1));
	$number = (float)$value;

	switch ($lastChar) {
		case 'g':
			$number *= 1024;
		case 'm':
			$number *= 1024;
		case 'k':
			$number *= 1024;
	}

	return (int)$number;
}

function dashboard_meminfo()
{
	$result = array(
		'total' => false,
		'available' => false
	);

	$meminfoFile = '/proc/meminfo';
	if (!is_readable($meminfoFile)) {
		return $result;
	}

	$lines = file($meminfoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if (!$lines) {
		return $result;
	}

	$values = array();
	foreach ($lines as $line) {
		if (preg_match('/^([A-Za-z_]+):\s+(\d+)\s+kB$/', $line, $matches)) {
			$values[$matches[1]] = (int)$matches[2] * 1024;
		}
	}

	if (isset($values['MemTotal'])) {
		$result['total'] = $values['MemTotal'];
	}

	if (isset($values['MemAvailable'])) {
		$result['available'] = $values['MemAvailable'];
	} elseif (isset($values['MemFree'])) {
		$result['available'] = $values['MemFree'];
	}

	return $result;
}

function dashboard_redis_rows($cache_worker)
{
	$rows = array();

	if (!$cache_worker) {
		$rows[] = array('Redis', 'cache_worker не инициализирован');
		return $rows;
	}

	CachingServers::reset();

	while (CachingServers::next()) {
		$cacheServerId = CachingServers::currentID();
		$cacheServerName = CachingServers::currentName();
		$cacheState = $cache_worker->getCacheInfo($cacheServerId);

		if ($cacheState) {
			$rows[] = array(
				'Redis ' . $cacheServerName,
				'OK, uptime ' . $cacheState['uptime'] . ' days, memory ' . $cacheState['memory_used'] . ' / peak ' . $cacheState['memory_used_max'] . ', frag ' . $cacheState['memory_fragmentation']
			);
		} else {
			$rows[] = array('Redis ' . $cacheServerName, 'Нет соединения');
		}
	}

	if (!$rows) {
		$rows[] = array('Redis', 'Серверы не определены');
	}

	return $rows;
}

$phpMemoryLimitBytes = dashboard_parse_ini_size(ini_get('memory_limit'));
$uploadMaxBytes = dashboard_parse_ini_size(ini_get('upload_max_filesize'));
$postMaxBytes = dashboard_parse_ini_size(ini_get('post_max_size'));
$maxRequestUploadBytes = min($uploadMaxBytes > 0 ? $uploadMaxBytes : PHP_INT_MAX, $postMaxBytes > 0 ? $postMaxBytes : PHP_INT_MAX);
$meminfo = dashboard_meminfo();
$serverLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : array();
$chunkUploadBytes = 16 * 1024 * 1024;
$tmpDirFreeBytes = is_dir(TMPDIR) ? @disk_free_space(TMPDIR) : false;
$phpRemoteAddr = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
$phpRemoteXforwarded = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim((string)$_SERVER['HTTP_X_FORWARDED_FOR']) : '';

$dashboardRows = array(
	array('Load Average', $serverLoad ? implode(' / ', $serverLoad) : 'Не доступно'),
	array('PHP $_SERVER[REMOTE_ADDR]', $phpRemoteAddr !== '' ? $phpRemoteAddr : 'Не передан'),
	array('PHP $_SERVER[HTTP_X_FORWARDED_FOR]', $phpRemoteXforwarded !== '' ? $phpRemoteXforwarded : 'Не передан'),
	array('PHP memory_limit', $phpMemoryLimitBytes > 0 ? dashboard_format_bytes($phpMemoryLimitBytes) : ini_get('memory_limit')),
	array('PHP memory usage', dashboard_format_bytes(memory_get_usage(true))),
	array('PHP memory peak', dashboard_format_bytes(memory_get_peak_usage(true))),
	array('PHP max_execution_time', ini_get('max_execution_time') . ' sec'),
	array('PHP max_input_time', ini_get('max_input_time') . ' sec'),
	array('upload_max_filesize', $uploadMaxBytes > 0 ? dashboard_format_bytes($uploadMaxBytes) : ini_get('upload_max_filesize')),
	array('post_max_size', $postMaxBytes > 0 ? dashboard_format_bytes($postMaxBytes) : ini_get('post_max_size')),
	array('Max single request upload', $maxRequestUploadBytes < PHP_INT_MAX ? dashboard_format_bytes($maxRequestUploadBytes) : 'Без лимита'),
	array('Chunk upload size', dashboard_format_bytes($chunkUploadBytes)),
	array('Chunk upload mode', 'Активен для формы ZIP upload'),
	array('Temp dir free space', $tmpDirFreeBytes !== false ? dashboard_format_bytes($tmpDirFreeBytes) : 'Не доступно'),
	array('System memory total', $meminfo['total'] ? dashboard_format_bytes($meminfo['total']) : 'Не доступно'),
	array('System memory available', $meminfo['available'] ? dashboard_format_bytes($meminfo['available']) : 'Не доступно')
);

foreach (dashboard_redis_rows(isset($cache_worker) ? $cache_worker : false) as $redisRow) {
	$dashboardRows[] = $redisRow;
}
?>

<div style="width: 1200px; margin: 10px auto 20px; text-align: left;">
	<h2 style="margin: 0 0 12px;">Техническая информация</h2>
	<table cellpadding="6" cellspacing="0" border="1" style="width: 100%; border-collapse: collapse; background: #fff;">
		<thead>
			<tr style="background: #eceff5;">
				<th style="width: 320px; text-align: left;">Параметр</th>
				<th style="text-align: left;">Значение</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($dashboardRows as $row) { ?>
				<tr>
					<td><?= htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8') ?></td>
					<td><?= htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
</div>