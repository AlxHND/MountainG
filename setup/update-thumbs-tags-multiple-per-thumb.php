<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/../config/config.php');
require_once(dirname(__FILE__) . '/DB.php');

use App\Helpers\DB as SetupDB;

function update_log_message($message, $error = false)
{
	$line = '[update-thumbs-tags-multiple-per-thumb] ' . trim((string)$message);
	echo $line . "<br>\n";

	if (defined('LOG_FOLDER') && LOG_FOLDER) {
		$prefix = $error ? 'error-' : '';
		$logFile = rtrim(LOG_FOLDER, '/\\') . '/' . $prefix . date('Y-m-d') . '.log';
		@file_put_contents($logFile, date('d-m-Y, H:i:s') . ' > ' . $line . PHP_EOL, FILE_APPEND);
	}

	@error_log($line);
}

set_error_handler(function ($severity, $message, $file, $line) {
	$text = "PHP error [{$severity}] {$message} in {$file}:{$line}";
	update_log_message($text, true);

	if (!(error_reporting() & $severity)) {
		return true;
	}

	return false;
});

set_exception_handler(function ($exception) {
	$text = 'Uncaught exception: ' . $exception->getMessage()
		. ' in ' . $exception->getFile() . ':' . $exception->getLine()
		. ', trace: ' . $exception->getTraceAsString();
	update_log_message($text, true);
	exit(1);
});

register_shutdown_function(function () {
	$error = error_get_last();
	if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) {
		$text = 'Fatal shutdown error [' . $error['type'] . '] ' . $error['message']
			. ' in ' . $error['file'] . ':' . $error['line'];
		update_log_message($text, true);
	}
});

function q($sql, $db, $label = '')
{
	$labelText = $label !== '' ? $label : 'SQL';
	update_log_message($labelText . ' start: ' . $sql);

	try {
		$result = $db->query($sql);
	} catch (Exception $e) {
		$errorText = $labelText . ' failed: ' . $e->getMessage() . '; SQL: ' . $sql;
		update_log_message($errorText, true);
		throw new RuntimeException($errorText);
	}

	if ($result === false) {
		$errorText = $labelText . ' failed without exception; SQL: ' . $sql;
		update_log_message($errorText, true);
		throw new RuntimeException($errorText);
	}

	if ($result instanceof PDOStatement) {
		update_log_message($labelText . ' ok, rows: ' . $result->rowCount());
	} else {
		update_log_message($labelText . ' ok');
	}

	return $result;
}

function load_indexes($db)
{
	$indexes = array();
	$indexResult = q('SHOW INDEX FROM thumbs_tags', $db, 'SHOW INDEX');
	while ($row = $indexResult->fetch(PDO::FETCH_ASSOC)) {
		$keyName = $row['Key_name'];
		if (!isset($indexes[$keyName])) {
			$indexes[$keyName] = array(
				'unique' => ((int)$row['Non_unique'] === 0),
				'columns' => array(),
			);
		}

		$indexes[$keyName]['columns'][] = $row['Column_name'];
	}

	return $indexes;
}

update_log_message('Script started');

$db = SetupDB::getInstance();

if (!$db instanceof PDO) {
	update_log_message('PDO connection object is not initialized correctly', true);
	throw new RuntimeException('PDO connection object is not initialized correctly');
}

update_log_message('MySQL connection OK');
q('SELECT 1', $db, 'PDO ping');
update_log_message('Preparing thumbs_tags for multiple tags per thumb...');

$indexes = load_indexes($db);

if (isset($indexes['thumb_id']) && $indexes['thumb_id']['unique']) {
	update_log_message('Dropping legacy unique index `thumb_id`...');
	q('ALTER TABLE thumbs_tags DROP INDEX `thumb_id`', $db, 'DROP INDEX thumb_id');
} else {
	update_log_message('Legacy unique index `thumb_id` not found or already non-unique.');
}

update_log_message('Removing duplicate tag assignments inside the same gallery...');
$deleteDuplicatesResult = q(
	"DELETE tt1 FROM thumbs_tags tt1
	INNER JOIN thumbs_tags tt2
		ON tt1.gal_id = tt2.gal_id
		AND tt1.tag_id = tt2.tag_id
		AND tt1.id < tt2.id",
	$db,
	'DELETE duplicate gal/tag rows'
);

if ($deleteDuplicatesResult instanceof PDOStatement) {
	update_log_message('Duplicate cleanup affected rows: ' . $deleteDuplicatesResult->rowCount());
}

$indexes = load_indexes($db);

if (
	!isset($indexes['gal_tag'])
	|| !$indexes['gal_tag']['unique']
	|| implode(',', $indexes['gal_tag']['columns']) !== 'gal_id,tag_id'
) {
	update_log_message('Adding unique index `gal_tag` on (gal_id, tag_id)...');
	q(
		'ALTER TABLE thumbs_tags ADD UNIQUE KEY `gal_tag` (`gal_id`, `tag_id`)',
		$db,
		'ADD UNIQUE INDEX gal_tag'
	);
} else {
	update_log_message('Unique index `gal_tag` already exists.');
}

$indexes = load_indexes($db);

if (!isset($indexes['thumb_id'])) {
	update_log_message('Adding non-unique index `thumb_id`...');
	q(
		'ALTER TABLE thumbs_tags ADD KEY `thumb_id` (`thumb_id`)',
		$db,
		'ADD INDEX thumb_id'
	);
} else {
	update_log_message('Index `thumb_id` already exists.');
}

update_log_message('Done.');
