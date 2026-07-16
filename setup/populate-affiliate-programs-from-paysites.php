<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

$link = mysqli_connect(DBHOST, DBUSER, DBPW, DBNAME) or die("Сервер базы данных не доступен");
$link->set_charset('utf8');

function affiliate_program_clean_value($value)
{
	$value = trim((string)$value);
	$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
	$value = preg_replace('~\s+~u', ' ', $value);
	return trim($value);
}

function affiliate_program_parse_parts($value)
{
	$value = affiliate_program_clean_value($value);
	if ($value === '') {
		return false;
	}

	$prepared = $value;
	if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $prepared) && preg_match('~^[a-z0-9.-]+\.[a-z]{2,}([/:?#].*)?$~i', $prepared)) {
		$prepared = 'http://' . $prepared;
	}

	$parts = @parse_url($prepared);
	if (!$parts || empty($parts['host'])) {
		return false;
	}

	$host = strtolower(trim($parts['host']));
	$host = preg_replace('~^www\.~i', '', $host);
	$host = rtrim($host, '.');
	if ($host === '') {
		return false;
	}

	$path = isset($parts['path']) ? trim($parts['path']) : '';
	$path = $path !== '' ? '/' . trim($path, '/') : '';
	if ($path === '/') {
		$path = '';
	}

	return array(
		'host' => $host,
		'path' => $path,
	);
}

function affiliate_program_guess_canonical_key($value)
{
	$value = affiliate_program_clean_value($value);
	if ($value === '') {
		return '';
	}

	$parts = affiliate_program_parse_parts($value);
	if ($parts) {
		return $parts['host'] . $parts['path'];
	}

	return mb_strtolower($value, 'UTF-8');
}

function affiliate_program_guess_name($value)
{
	$value = affiliate_program_clean_value($value);
	$parts = affiliate_program_parse_parts($value);
	if ($parts) {
		return $parts['host'] . $parts['path'];
	}

	return $value;
}

function affiliate_program_guess_url($value)
{
	$value = affiliate_program_clean_value($value);
	$parts = affiliate_program_parse_parts($value);
	if (!$parts) {
		return null;
	}

	return $parts['host'] . $parts['path'];
}

function affiliate_program_pick_display_name(array $values)
{
	usort($values, function ($a, $b) {
		$len = mb_strlen($a, 'UTF-8') <=> mb_strlen($b, 'UTF-8');
		if ($len !== 0) {
			return $len;
		}
		return strcmp($a, $b);
	});

	return affiliate_program_guess_name($values[0]);
}

$selectSql = "SELECT DISTINCT TRIM(paysite_affiliate) AS affiliate_value
	FROM paysites
	WHERE TRIM(paysite_affiliate) <> ''";

$selectResult = mysqli_query($link, $selectSql);
if ($selectResult === false) {
	die("Не удалось получить список партнерок: " . mysqli_error($link) . "\n");
}

$checked = 0;
$groups = array();
$rawToKey = array();

while ($row = mysqli_fetch_assoc($selectResult)) {
	$rawValue = affiliate_program_clean_value($row['affiliate_value']);
	if ($rawValue === '') {
		continue;
	}

	$checked++;
	$key = affiliate_program_guess_canonical_key($rawValue);
	if ($key === '') {
		continue;
	}

	if (!isset($groups[$key])) {
		$groups[$key] = array(
			'values' => array(),
			'raw_map' => array(),
		);
	}

	$groups[$key]['values'][] = $rawValue;
	$groups[$key]['raw_map'][$rawValue] = $rawValue;
	$rawToKey[$rawValue] = $key;
}

$inserted = 0;
$updatedExisting = 0;
$mapped = 0;
$mergedDuplicates = 0;

foreach ($groups as $key => $group) {
	$rawValues = array_values($group['raw_map']);
	if (count($rawValues) > 1) {
		$mergedDuplicates += count($rawValues) - 1;
	}

	$displayName = affiliate_program_pick_display_name($rawValues);
	$guessedUrl = null;

	foreach ($rawValues as $rawValue) {
		$maybeUrl = affiliate_program_guess_url($rawValue);
		if ($maybeUrl !== null && $maybeUrl !== '') {
			$guessedUrl = $maybeUrl;
			break;
		}
	}

	$nameSql = mysqli_real_escape_string($link, $displayName);
	$urlSql = $guessedUrl !== null ? "'" . mysqli_real_escape_string($link, $guessedUrl) . "'" : "NULL";

	$insertSql = "INSERT INTO affiliate_programs
		(affiliate_program_name, affiliate_program_url, affiliate_program_description, created_at, updated_at)
		VALUES
		('" . $nameSql . "', " . $urlSql . ", '', NOW(), NOW())
		ON DUPLICATE KEY UPDATE
			affiliate_program_url = CASE
				WHEN (affiliate_programs.affiliate_program_url IS NULL OR affiliate_programs.affiliate_program_url = '')
					AND VALUES(affiliate_program_url) IS NOT NULL
					AND VALUES(affiliate_program_url) <> ''
				THEN VALUES(affiliate_program_url)
				ELSE affiliate_programs.affiliate_program_url
			END,
			updated_at = NOW()";

	if (mysqli_query($link, $insertSql) === false) {
		echo "Ошибка при добавлении партнерки: " . mysqli_error($link) . "\n" . $insertSql . "\n";
		continue;
	}

	$selectProgramSql = "SELECT affiliate_program_id
		FROM affiliate_programs
		WHERE affiliate_program_name = '" . $nameSql . "'
		LIMIT 1";
	$programResult = mysqli_query($link, $selectProgramSql);
	if ($programResult === false || !($programRow = mysqli_fetch_assoc($programResult))) {
		echo "Ошибка при получении ID партнерки: " . mysqli_error($link) . "\n" . $selectProgramSql . "\n";
		continue;
	}

	$affiliateProgramId = (int)$programRow['affiliate_program_id'];
	if (mysqli_affected_rows($link) === 1) {
		$inserted++;
	} else {
		$updatedExisting++;
	}

	foreach ($rawValues as $rawValue) {
		$updatePaysitesSql = "UPDATE paysites
			SET affiliate_program_id = " . $affiliateProgramId . "
			WHERE affiliate_program_id IS NULL
			AND TRIM(paysite_affiliate) = '" . mysqli_real_escape_string($link, $rawValue) . "'";
		if (mysqli_query($link, $updatePaysitesSql) === false) {
			echo "Ошибка при привязке платников: " . mysqli_error($link) . "\n" . $updatePaysitesSql . "\n";
		} else {
			$mapped += max(0, (int)mysqli_affected_rows($link));
		}
	}
}

$statsSql = "SELECT
	(SELECT COUNT(*) FROM affiliate_programs) AS total_programs,
	(SELECT COUNT(*) FROM affiliate_programs WHERE affiliate_program_url IS NOT NULL AND affiliate_program_url <> '') AS programs_with_url,
	(SELECT COUNT(*) FROM paysites WHERE affiliate_program_id IS NOT NULL) AS mapped_paysites";
$statsResult = mysqli_query($link, $statsSql);
$stats = $statsResult ? mysqli_fetch_assoc($statsResult) : array(
	'total_programs' => 0,
	'programs_with_url' => 0,
	'mapped_paysites' => 0,
);

echo "Done.\n";
echo "Checked source strings: " . (int)$checked . "\n";
echo "Canonical groups: " . (int)count($groups) . "\n";
echo "Inserted programs: " . (int)$inserted . "\n";
echo "Touched existing programs: " . (int)$updatedExisting . "\n";
echo "Merged duplicate raw values: " . (int)$mergedDuplicates . "\n";
echo "Mapped paysites: " . (int)$mapped . "\n";
echo "Total programs: " . (int)$stats['total_programs'] . "\n";
echo "Programs with URL: " . (int)$stats['programs_with_url'] . "\n";
