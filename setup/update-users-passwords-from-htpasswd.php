<?php

require __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Helpers\DB;

function readHtpasswdEntries($filename) {
	$result = array();

	if (!is_file($filename) || !is_readable($filename)) {
		throw new RuntimeException("Файл не найден или не читается: ".$filename);
	}

	$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ($lines === false) {
		throw new RuntimeException("Не удалось прочитать файл: ".$filename);
	}

	foreach ($lines as $lineNumber => $line) {
		$line = trim($line);

		if ($line === '' || $line[0] === '#') {
			continue;
		}

		$parts = explode(':', $line, 2);
		if (count($parts) !== 2) {
			echo "Пропуск строки ".($lineNumber + 1).": неверный формат\n";
			continue;
		}

		$userName = trim($parts[0]);
		$passwordHash = trim($parts[1]);

		if ($userName === '' || $passwordHash === '') {
			echo "Пропуск строки ".($lineNumber + 1).": пустой логин или хэш\n";
			continue;
		}

		$result[$userName] = $passwordHash;
	}

	return $result;
}

$htpasswdFile = isset($argv[1]) && trim((string)$argv[1]) !== ''
	? trim((string)$argv[1])
	: dirname(__DIR__) . '/.htpasswd';

try {
	$entries = readHtpasswdEntries($htpasswdFile);
} catch (Exception $e) {
	echo $e->getMessage()."\n";
	exit(1);
}

echo "HTPASSWD source: ".$htpasswdFile."\n";
echo "Entries loaded: ".count($entries)."\n";

$db = DB::getInstance();

$selectStmt = $db->prepare("SELECT id, user_name, user_pass FROM scr_users_list WHERE user_name = :user_name LIMIT 1");
$updateStmt = $db->prepare("UPDATE scr_users_list SET user_pass = :user_pass WHERE id = :id");

$updated = 0;
$unchanged = 0;
$missing = array();

foreach ($entries as $userName => $passwordHash) {
	if (!$selectStmt || !$updateStmt) {
		echo "Не удалось подготовить SQL statement\n";
		exit(1);
	}

	$selectStmt->execute(array(':user_name' => $userName));
	$user = $selectStmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) {
		$missing[] = $userName;
		continue;
	}

	if ((string)$user['user_pass'] === $passwordHash) {
		$unchanged++;
		echo "User ".$userName.": hash already matches\n";
		continue;
	}

	$updateStmt->execute(array(
		':user_pass' => $passwordHash,
		':id' => $user['id']
	));

	$updated++;
	echo "User ".$userName.": hash updated from .htpasswd\n";
}

echo "Updated users: ".$updated."\n";
echo "Already matched: ".$unchanged."\n";
echo "Missing in DB: ".count($missing)."\n";

foreach ($missing as $userName) {
	echo " - ".$userName."\n";
}
