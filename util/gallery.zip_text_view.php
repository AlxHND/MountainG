<?php
include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при просмотре текста из ZIP. Нужны права администратора.');

$galId = isset($_GET['gal_id']) ? (int)$_GET['gal_id'] : 0;
if ($galId <= 0) {
	header('HTTP/1.1 400 Bad Request');
	echo "Wrong gallery ID";
	exit;
}

function gallery_zip_text_storage_path()
{
	if (defined('GALLERY_TEXT_STORAGE')) {
		return rtrim(GALLERY_TEXT_STORAGE, '/');
	}
	if (defined('WRKDIR')) {
		return rtrim(WRKDIR, '/') . "/storage/gallery_texts";
	}
	return dirname(__DIR__) . "/storage/gallery_texts";
}

$filePath = gallery_zip_text_storage_path() . "/" . $galId . ".txt";
if (!is_file($filePath)) {
	header('HTTP/1.1 404 Not Found');
	echo "ZIP text file not found";
	exit;
}

$content = file_get_contents($filePath);
$content = $content === false ? '' : $content;
$lines = preg_split("/\n/", $content, 2);
$header = isset($lines[0]) ? trim($lines[0]) : '';
$body = isset($lines[1]) ? $lines[1] : '';
$files = array();

if (preg_match('/^====ZIP_TEXT_FILES\|(.*)====$/', $header, $matches)) {
	$parts = explode('|', $matches[1]);
	foreach ($parts as $part) {
		$part = trim($part);
		if ($part !== '') {
			$files[] = $part;
		}
	}
}

function zip_text_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE HTML>
<html lang="ru">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>ZIP text GID <?= $galId ?></title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			color: #222;
		}

		.files {
			margin: 12px 0;
			padding: 10px;
			background: #f1f3f5;
			border: 1px solid #d5d9de;
		}

		pre {
			white-space: pre-wrap;
			word-wrap: break-word;
			padding: 14px;
			border: 1px solid #d5d9de;
			background: #fff;
			font-size: 14px;
			line-height: 1.45;
		}

		a {
			color: #0645ad;
		}
	</style>
</head>

<body>
	<h1>ZIP text for gallery #<?= $galId ?></h1>
	<p><a href="../index.php?act=galleries&amp;galid=<?= $galId ?>">Назад к галерее</a></p>
	<div class="files">
		<strong>Файлы в архиве:</strong>
		<?php if ($files) { ?>
			<ul>
				<?php foreach ($files as $file) { ?>
					<li><?= zip_text_h($file) ?></li>
				<?php } ?>
			</ul>
		<?php } else { ?>
			<span>служебная строка не найдена</span>
		<?php } ?>
	</div>
	<pre><?= zip_text_h(trim($body)) ?></pre>
</body>

</html>