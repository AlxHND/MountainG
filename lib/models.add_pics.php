<?php

$error = false;

if ((isset($_POST['doUpload']) || isset($_REQUEST['url-zip'])) && isset($_POST['model_id']) && intval($_POST['model_id'])) {
	// имя временного файла
	$tempFileName = md5(getmypid() .implode(",", getdate()));
	$zipFolderPath = TMPDIR . "/.models";
	$destinationPath = $zipFolderPath ."/".intval($_POST['model_id'])."-" .$tempFileName. ".tmp";
	// чистка тайтла
	if (isset($_POST['title'])) $title = mysql_escape_string($_POST['title']);
	else $title = "";
	if (isset($_POST['desc'])) $desc = mysql_escape_string($_POST['desc']);
	else $desc = "";
	if (isset($_REQUEST['url-zip'])) {
		$url = $_POST['file'];
		$url = rtrim($url);
		// закачка файла с урла
		$zip = new Grabber_new();
		$destinationPath = $zip->fetchFile($url, CONTENT_TYPE_ZIP);
		if ($destinationPath) $destinationPath = TMPDIR . $destinationPath;
		$loggingFilename = $url;
	} elseif (isset($_FILES)) {
		$tmp = $_FILES ['file']['tmp_name'];
		if (!move_uploaded_file($tmp, $destinationPath)) {
			$error = "Проблема с записью временного файла в папку:".$destinationPath;
		}
		$loggingFilename = $tmp = $_FILES ['file']['name'];
	} else {
		$destinationPath = false;
		$error = "Нет файла, или ссылки на архив";
	}
	// проверка файла
	if ($destinationPath && !$error) {
		// сохранение первичной информации
		$finfo = new finfo(FILEINFO_MIME);
		// var_dump($finfo->file($destinationPath));

		if (preg_match('#image\/(jpeg)#im', $finfo->file($destinationPath))) {
			chmod($destinationPath, 0777);
			$gallery = new Galleries($db->_db);
			if ($image_id = $gallery->uploadModelImage($_POST['model_id'], $destinationPath, $_POST['layout'])) {
				echo "Изображение добавлено для модели: '".intval($_POST['model_id'])."'";
			} else{
				echo "Ошибка! Изображение не добавлено для модели: '".intval($_POST['model_id'])."'";
			}

		} else {
			unlink($destinationPath);
			$log = new Logger ("Файл закачаный с ".$loggingFilename." не является зипом!", true);
			echo "Файл не является зипом, ошибка!<br>";	
		}
	} else {
		if (!$loggingFilename) $loggingFilename = "Unknown";
		$log = new Logger ("Файл ".$loggingFilename." не загружен! ".$error, true);
		echo "Файл не загружен, ошибка!<br>".$error;
		// логгирование ошибки	
	}
}
//
//	аплоад пиксы
//

	$models = new CModels($db->_db);
	$models_list = $models->getModelsList(false, true);
?>

		<div id="Upload">
			<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . '?'. $_SERVER['QUERY_STRING']?>" method="post">
				Модель: <select name="model_id" id="model_id">
<?php 			foreach($models_list as $model) { ?>
					<option value="<?=$model['id_model']?>"><?=$model['name']?></option>
<?php			} ?>
				</select>	
				<input name="file" size="80" type="file" />
				<select name="layout" id="layout">
					<option value="vertic">Вертикальная</option>
					<option value="horiz">Горизонтальная</option>
				</select>
				<input type="submit" name="doUpload" value="Upload" />
			</form>
		</div>
		<hr>