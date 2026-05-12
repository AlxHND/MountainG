<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<script>
	const zipChunkUploadSize = 16 * 1024 * 1024;
	let zipFinalizePollTimer = null;

	function zipSetProgress(percentValue) {
		document.getElementById("progress_div").style.display = "block";
		$('#bar').width(percentValue + '%');
		$('#percent').html(percentValue.toFixed(2) + '%');
	}

	function zipShowResult(message, isError) {
		var safeMessage = $('<div>').text(message === undefined || message === null ? '' : String(message)).html();
		var html = isError ? '<div style="color:#a10000;">' + safeMessage + '</div>' : '<div style="color:#0b6a21;">' + safeMessage + '</div>';
		document.getElementById("output_result").innerHTML = html;
	}

	function zipStopFinalizePoll() {
		if (zipFinalizePollTimer) {
			clearInterval(zipFinalizePollTimer);
			zipFinalizePollTimer = null;
		}
	}

	function zipStartFinalizePoll(uploadId) {
		zipStopFinalizePoll();
		if (!uploadId) {
			return;
		}

		zipFinalizePollTimer = setInterval(function() {
			fetch('util/upload_file_status.php?upload_id=' + encodeURIComponent(uploadId), {
				credentials: 'same-origin'
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				if (!data || !data.status || !data.status.stage) {
					return;
				}

				var stage = String(data.status.stage);
				var stageText = 'Идет финализация и добавление в БД... [' + stage + ']';
				if (data.status.context && data.status.context.gallery_id) {
					stageText += ' GID#' + data.status.context.gallery_id;
				}
				zipShowResult(stageText, false);
			})
			.catch(function() {
			});
		}, 1500);
	}

	function zipUploadRequest(formData, onProgress) {
		return new Promise(function(resolve, reject) {
			var xhr = new XMLHttpRequest();
			xhr.open('POST', 'util/upload_file.php', true);
			xhr.responseType = 'json';

			if (xhr.upload && onProgress) {
				xhr.upload.onprogress = onProgress;
			}

			xhr.onload = function() {
				var payload = null;

				try {
					if (xhr.response && typeof xhr.response === 'object') {
						payload = xhr.response;
					} else if (xhr.responseText) {
						payload = JSON.parse(xhr.responseText);
					} else {
						payload = {};
					}
				} catch (error) {
					reject({
						error: 'Некорректный ответ сервера.',
						raw_response: xhr.responseText ? xhr.responseText.slice(0, 600) : ''
					});
					return;
				}

				if (xhr.status >= 200 && xhr.status < 300) {
					resolve(payload);
				} else {
					if (!payload || typeof payload !== 'object') {
						payload = { error: 'HTTP ' + xhr.status };
					}
					reject(payload);
				}
			};

			xhr.onerror = function() {
				reject({ error: 'Network error during upload.' });
			};

			xhr.send(formData);
		});
	}

	async function upload_image() {
		var form = document.getElementById('myForm');
		var fileInput = form.querySelector('input[name="upfile"]');
		var paysite = form.querySelector('select[name="paysite"]').value;
		var title = form.querySelector('input[name="title"]').value;
		var desc = form.querySelector('input[name="desc"]').value;
		var button = document.getElementById('zip-upload-button');
		var file = fileInput.files[0];

		document.getElementById("output_result").innerHTML = '';

		if (!file) {
			zipShowResult('Нужно выбрать файл для загрузки.', true);
			return false;
		}

		if (!paysite || paysite === 'Paysite/Source site') {
			zipShowResult('Нужно выбрать paysite/source site.', true);
			return false;
		}

		var uploadId = 'zip_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
		var chunkCount = Math.ceil(file.size / zipChunkUploadSize);
		var uploadedBytes = 0;

		button.disabled = true;
		zipSetProgress(0);

		try {
			for (var chunkIndex = 0; chunkIndex < chunkCount; chunkIndex++) {
				var start = chunkIndex * zipChunkUploadSize;
				var end = Math.min(start + zipChunkUploadSize, file.size);
				var chunk = file.slice(start, end);
				var formData = new FormData();

				formData.append('upload_mode', 'chunk');
				formData.append('upload_id', uploadId);
				formData.append('file_name', file.name);
				formData.append('total_size', String(file.size));
				formData.append('chunk_index', String(chunkIndex));
				formData.append('chunk_offset', String(start));
				formData.append('upfile', chunk, file.name);

				var chunkResponse = await zipUploadRequest(formData, function(event) {
					if (!event.lengthComputable) {
						return;
					}

					var currentUploaded = uploadedBytes + event.loaded;
					zipSetProgress(Math.min(100, (currentUploaded / file.size) * 100));
				});

				if (chunkResponse.error) {
					throw new Error(chunkResponse.error);
				}

				uploadedBytes = end;
				zipSetProgress((uploadedBytes / file.size) * 100);
			}

			zipShowResult('Файл загружен. Идет финализация и добавление в БД...', false);
			zipStartFinalizePoll(uploadId);
			var finalizeData = new FormData();
			finalizeData.append('upload_mode', 'finalize');
			finalizeData.append('upload_id', uploadId);
			finalizeData.append('file_name', file.name);
			finalizeData.append('total_size', String(file.size));
			finalizeData.append('paysite', paysite);
			finalizeData.append('title', title);
			finalizeData.append('desc', desc);

			var finalizeResponse = await zipUploadRequest(finalizeData);
			if (finalizeResponse.error) {
				throw new Error(finalizeResponse.error);
			}

			zipStopFinalizePoll();
			zipSetProgress(100);
			zipShowResult('Файл загружен успешно. Галерея #' + finalizeResponse.gallery_id + ', тип: ' + finalizeResponse.gallery_type, false);
			form.reset();
		} catch (error) {
			zipStopFinalizePoll();
			if (error && error.raw_response) {
				console.log('ZIP upload raw response:', error.raw_response);
			}
			zipShowResult(error.message || error.error || 'Неизвестная ошибка загрузки.', true);
		} finally {
			button.disabled = false;
		}

		return false;
	}
</script>

<style>
	form {
		display: block;
		margin: 20px auto;
		background: #eee;
		border-radius: 10px;
		padding: 15px
	}

	.progress {
		display: none;
		position: relative;
		width: 100%;
		height: 35px;
		border: 1px solid #ddd;
		padding: 1px;
		border-radius: 4px;

	}

	.bar {
		background-color: #B4F5B4;
		width: 0%;
		height: 20px;
		border-radius: 3px;
	}

	.percent {
		position: absolute;
		display: inline-block;
		top: 3px;
		left: 48%;
	}
</style>
<?php

if (isset($_POST['doUpload']) || isset($_REQUEST['url-zip'])) {

	$error 				= false;
	// имя временного файла
	$tempFileName 		= md5(getmypid() . implode(",", getdate()));
	$zipFolderPath 		= TMPDIR;
	$destinationPath 	= $zipFolderPath . "/" . $tempFileName . ".tmp";

	$title 				= (isset($_POST['title'])) ? $_POST['title'] : "";
	$desc 				= (isset($_POST['desc'])) ? $_POST['desc'] : "";

	if (isset($_REQUEST['url-zip'])) { // закачка файла с урла
		$url = rtrim($_POST['file']);

		$zip = new Grabber_new();
		$destinationPath = $zip->fetchFile($url, CONTENT_TYPE_ZIP);

		if ($destinationPath) $destinationPath = TMPDIR . $destinationPath;
		$loggingFilename = $url;

		var_dump($destinationPath);
	} elseif (isset($_FILES)) {
		$tmp = $_FILES['file']['name'];

		$move_file = move_uploaded_file($tmp, $destinationPath);

		if (!move_uploaded_file($tmp, $destinationPath)) {
			$error = "Проблема с записью временного файла в папку:" . $destinationPath;
		}
		$loggingFilename = $tmp = $_FILES['file']['name'];
	} else {
		$destinationPath = false;
		$error = "Нет файла, или ссылки на архив";
	}

	// проверка файла
	if ($destinationPath && !$error) {
		// сохранение первичной информации
		$finfo = new finfo(FILEINFO_MIME);
		if (preg_match('#application\/(x\-gzip|zip|x\-zip)#i', $finfo->file($destinationPath))) {
			chmod($destinationPath, 0777);
			$sources = new Sources($db->_db);
			if ($paysite = $sources->getSource((int)$_POST['paysite'])) {

				$md5 = md5_file($destinationPath);
				$paysiteId = $paysite['id'];
				$paysiteNiche =  $paysite['niche'];
				$galleryType = "New";
				$galleryStatus = "newzip";
				$gallery = new Galleries($db->_db);

				$new_galleryId = $gallery->addGallery($loggingFilename, $paysiteId, $paysiteNiche, $galleryType, $galleryStatus, $title, $desc, $md5);

				if ($new_galleryId) {

					$new_galleryZipPath = $zipFolderPath . "/" . $new_galleryId . ".zip";

					var_dump($new_galleryZipPath);
					if (rename($destinationPath, $new_galleryZipPath)) {
						chmod($new_galleryZipPath, 0777);
						$gallery = new Galleries($db->_db);
						$gallery->addToQuery($new_galleryId);
						$log = new Logger("Галлерея " . $new_galleryId . " добавлена из " . $loggingFilename . ". Статус: " . $galleryStatus . " Тип: " . $galleryType);
						echo "Галлерея " . $new_galleryId . " успешно добавлена<br>";
						if ($paysiteId) {
							$sources = new Sources($db->_db);
							$sources->paysiteUpdated($paysiteId);
						}
					} else {
						unlink($destinationPath);
						$log = new Logger("Ошибка добавления галлереи", true);
						echo "Ошибка добавления галлереи<br>";
					}
				} else {
					unlink($destinationPath);
					$log = new Logger("Ошибка добавления галлереи", true);
					echo "Ошибка добавления галлереи<br>";
				}
			} else {
				unlink($destinationPath);
				$log = new Logger("Ошибка выборки платника " . (int)$_POST['paysite'], true);
				echo "Ошибка выборки платника " . (int)$_POST['paysite'] . "<br>";
			}
		} else {
			unlink($destinationPath);
			$log = new Logger("Файл закачаный с " . $loggingFilename . " не является зипом!", true);
			echo "Файл не является зипом, ошибка!<br>";
		}
	} else {
		if (!$loggingFilename) $loggingFilename = "Unknown";
		$log = new Logger("Файл " . $loggingFilename . " не загружен! " . $error, true);
		echo "Файл не загружен, ошибка!<br>" . $error;
		// логгирование ошибки	
	}
}

//
//	аплоад галеры
//
$paysites_list_options = $default->AllPaysitesToString("<option value=\"#PAYSITE_ID#\">#PAYSITE#</option>");
?>
<h3>Load ZIP from URL</h3>
<form enctype="multipart/form-data" action="<?= $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'] ?>" method="post">
	<div class="mb-2">
		<select class="form-select" name="paysite" id="paysite">
			<option selected>Paysite/Source site</option>
			<?= $paysites_list_options; ?>
		</select>
	</div>
	<div class="mb-2">
		<input class="form-control" type="text" placeholder="URL" name="file" id="file">
	</div>
	<div class="input-group">
		<input class="form-control" type="text" placeholder="Title" aria-label="default input" name="title" id="title">
	</div>
	<div class="input-group">
		<input class="form-control" type="text" placeholder="Description" aria-label="default input" name="desc" id="desc">
		<input class="btn btn-outline-primary" type="submit" value="Upload file" name="url-zip" id="url-zip" />
	</div>
</form>

<hr>
<h3>Upload File</h3>
<form id="myForm" enctype="multipart/form-data" action="util/upload_file.php" method="post">

	<div class="mb-2">
		<select class="form-select" name="paysite" id="paysite">
			<option selected>Paysite/Source site</option>
			<?= $paysites_list_options; ?>
		</select>
	</div>
	<div class="mb-2">
		<input class="form-control" id="formFileLg" type="file" name="upfile">
	</div>
	<div class="input-group">
		<input class="form-control" type="text" placeholder="Title" aria-label="default input" name="title" id="title">
	</div>
	<div class="input-group">
		<input class="form-control" type="text" placeholder="Description" aria-label="default input" name="desc" id="desc">
		<button class="btn btn-outline-primary" type="button" id="zip-upload-button" onclick="return upload_image();">Upload file</button>
	</div>
</form>
<div style="margin: 8px 0 18px; font-size: 13px; color: #666;">Большие файлы отправляются кусками по 16 MB. Это снижает зависания и обрывы на архивах большого размера.</div>

<div class='progress' id="progress_div">
	<div class='bar' id='bar'></div>
	<div class='percent' id='percent'>0%</div>
</div>
<div id="output_result"></div>
</div>
