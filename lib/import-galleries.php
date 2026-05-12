<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<?php if (@$_REQUEST['import-galleries']) { ?>

<div class="p-3 mb-2 bg-light text-dark" id="imported_list">
	<div class="d-grid gap-2">
		<button class="btn btn-primary" type="button" name="import-galleries" id="import-galleries" onclick="document.getElementById('imported_list').style.display = 'none';">Close block</button>	
	</div>
	<p class="text-md-start fs-6">

<?php
	if ($_POST['input_string'] === '') {
		echo "Пустой импорт";
	} else {

		// определение способа разбиения входящих списков для импорта
		// входящие $_POST [field*] * до 6ти
		// на выходе массив input с нумерацией входящих
		
		if (isset($_POST['paysite']) && $_POST['paysite'] == "0") {
			unset($_POST['paysite']);
		} else {
			$paysite = $_POST['paysite'];
		}

		$input = array();
		for ($i=1; $i<=7; $i++) {
			$array_name = "field" . $i;
			switch ($_POST[$array_name]) {
				case 'URL':
					$input['url'] = $i-1;	
					break;
				case 'Title':
					$input['title'] = $i-1;
					break;
				case 'Description':
					$input['description'] = $i-1;
					break;
				case 'Tags':
					$input['tags'] = $i-1;
					break;
				case 'Models':
					$input['models'] = $i-1;
					break;
				case 'Title1':
					$input['title_1'] = $i-1;
					break;
				case 'Title2':
					$input['title_2'] = $i-1;
					break;
				case 'Title3':
					$input['title_3'] = $i-1;
					break;
				case 'Title4':
					$input['title_4'] = $i-1;
					break;
				case 'Embed':
					$input['embed'] = $i-1;
					break;
				case 'Images':
					$input['images'] = $i-1;
					break;
				case 'Duration':
					$input['duration'] = $i-1;
					break;
				case 'Origianl IDs':
					$input['gallery_original_id'] = $i-1;
					break;

			}
			unset ($_POST[$array_name]);
		}
		// если импорт новых тайтлов для существующих галер
		$title_language =  false;

		if (isset($_POST['update_gals_titles_chkbx'])) { 
			$gal_titles_updater = true; 
			if (isset($_POST['add_title_language'])) $title_language = $_POST['add_title_language'];
		} else { 
			$gal_titles_updater = false; 
		}

		if(isset($_POST['images_delim']) && $_POST['images_delim']) {
			$images_delim = $_POST['images_delim'];
			unset($_POST['images_delim']);
		} else {
			$images_delim = ",";
		}
				
		$gallery_worker = new Galleries($db->_db);

		if(isset($_POST['disable_tags_from_title'])) {
			$gallery_worker->disableTagsFromTitle();
			unset($_POST['disable_tags_from_title']);
		}

		if(isset($_POST['force_add_models_from_import'])) {
			$gallery_worker->forceAddModelsFromImport();
			unset($_POST['force_add_models_from_import']);
		}

		$unique_for_export_site = 0;

		if(!empty($_POST['unique_for_export_site'])) {
			$unique_for_export_site = (int)$_POST['unique_for_export_site'];	
			unset($_POST['unique_for_export_site']);			
		}

		
		// конец разбора
		$import_galleries = CSVtoArray($_POST['input_string'], $input, $images_delim);

		// var_dump($import_galleries, $input);

		if ($import_galleries && is_array($import_galleries)) {

			$sources = new Sources($db->_db);

			$paysite = $sources->getSource($_POST['paysite']);
			$updates_imported = false;

			foreach ($import_galleries as $new_gallery) {
				if (($gal_titles_updater) || (isset($new_gallery['url']) && $paysite['id'])) {

					$title 				 = (isset($new_gallery['title'])) ? $new_gallery['title'] : "";
					$desc 				 = (isset($new_gallery['description'])) ? $new_gallery['description'] : "";
					$niche 				 = (isset($paysite['niche'])) ? $paysite['niche'] : "";
					$embed 				 = (isset($new_gallery['embed'])) ? $new_gallery['embed'] : false;
					$images 			 = (isset($new_gallery['images'])) ? $new_gallery['images'] : false;
					$duration 			 = (isset($new_gallery['duration'])) ? $new_gallery['duration'] : false;
					$tags 				 = (isset($new_gallery['tags'])) ? $new_gallery['tags'] : false;
					$models 			 = (isset($new_gallery['models'])) ? $new_gallery['models'] : false;
					$gallery_original_id = ($paysite['use_original_ids'] && isset($new_gallery['gallery_original_id'])) ? $new_gallery['gallery_original_id'] : 0;

					$import_error 		 = false;
					$new_galleryId 		 = false;

					$md5 				 = false;
					$main_gal 			 = 0;

					$message 			 = "";
					
					// импорт новых тайтлов для существующих галер
					if ($gal_titles_updater) {
						if ($title_added = $gallery_worker->addTitleToExistingGal($new_gallery['url'], $new_gallery['title'], $title_language)) {
							$message =  "Тайтл для GID <a href='index.php?act=galleries&amp;galid=".$title_added."'>".$title_added."</a>, ".$new_gallery['url']." добавлен<br>";
						} else {
							$import_error = true;
							$message = "Ошибка добавления тайтла для ".$new_gallery['url']."<br>";
						}
					} else {
					

						if($embed) {
							if($images && $duration) {
								$gallery_type = 'embed';
							} else {
								$import_error = true;
								$message = "В эмбед галере нет Images и Duration<br>";
							}
						} else {
							$gallery_type = 'New';
							$embed = false;
							$images = false;
							$duration = false;

							
						}


						// пренести в добавление, возвращать объект галеру, добавлять и сохранять через методы!!
						if (!$import_error && $new_galleryId = $gallery_worker->addGallery($new_gallery['url'], $paysite['id'], $niche, $gallery_type, "new", $title, $desc, $md5, $main_gal, $models, $embed, $images, $duration, $gallery_original_id, $tags, $unique_for_export_site)) {

							if (isset($new_gallery['additional_titles']) && is_array($new_gallery['additional_titles'])) {
								$message .= ($gallery_worker->insertAdditionalTitles($new_galleryId, $new_gallery['additional_titles'])) ? "Доп. тайтлы добавлен<br>" : "Ошибка добавления доп. тайтлов<br>";
							}						

							$log = new Logger ("Галлерея ".$new_galleryId." добавлена из импорта ");
							$message .= "Галлерея ".$new_galleryId.":".htmlentities($new_gallery['url'],ENT_QUOTES)." успешно добавлена<br>";

							$updates_imported = true;
						} else {
							$log = new Logger ("Ошибка добавления галлереи", true);
							$message .= "Ошибка: ".$new_gallery['url']." > <b>".$gallery_worker->getInsertError()."</b><br>";
						}
					}

					
					
				} else {
					$message = "Ошибка добавления галеры - неправильный входящий формат строки<br>";
				}

				echo $message;
								
			}

			if ($paysite && $updates_imported) {
				$sources = new Sources($db->_db);
				$sources->paysiteUpdated($paysite['id']);
			}
		}

	} ?>
	</p>
	<div class="d-grid gap-2">
		<button class="btn btn-primary" type="button" name="import-galleries" id="import-galleries" onclick="document.getElementById('imported_list').style.display = 'none';">Close block</button>	
	</div>
</div>

<?php } ?>

		<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
				<div class="shadow-sm row p-1 pt- m-0 mb-4 bg-secondary text-white fw-normal">
					<div class="col">
						<select class="form-select form-select-sm fs-6" name="unique_for_export_site">
							<option value="">Добавлять как уникальные</option>
							<?php foreach((new Sites($db->_db))->allOnlyExportSites() as $uniqueSite) : ?>
								<option value="<?=$uniqueSite['id']?>"><?=$uniqueSite['name']?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-4 fs-6">
						<label for="update_gals_titles_chkbx" class="text-white">Добавление тайтлов к существующим галерам:</label> 
						<input type="checkbox" name="update_gals_titles_chkbx" />
					</div>
					<div class="col fs-6">Язык только для добавления:</div>
					<div class="col">
						<select class="form-select form-select-sm fs-6"  name="add_title_language">
							<option value="en">Английский</option>
							<option value="ru">Русский</option>
							<option value="nl">Голландский</option>
							<option value="fr">Французский</option>
						</select>
					</div>
				</div>

				<div class="shadow-sm row p-1 pt- m-0 mb-4 bg-secondary text-white fw-normal">
					<div class="col fs-6">
						<label for="disable_tags_from_title" class="text-white">Отключить тайтл->теги</label>
						<input type="checkbox" name="disable_tags_from_title">
					</div>

					<div class="col fs-6">
						<label for="force_add_models_from_import" class="text-white">Автоматически добавлять моделей из импорта</label>
						<input type="checkbox" name="force_add_models_from_import">
					</div>
				</div>

				<div class="row mb-2">
				<div class="col-2">
				<select class="form-select form-select-sm" name="paysite" id="paysite">
					<option select>Select paysite</option>
					<?php $default->AllPaysitesToStringNoCount("<option value=\"#PAYSITE_ID#\">#PAYSITE#</option>"); ?>	
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field1' id='field1'>
					<option>None</option>
					<option selected>URL</option>
					<option>Title</option>
					<option>Description</option>
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>					
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field2' id='field2'>
					<option>None</option>
					<option>URL</option>
					<option selected>Title</option>
					<option>Description</option>
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field3' id='field3'>
					<option>None</option>
					<option>URL</option>
					<option>Title</option>
					<option>Description</option>
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field4' id='field4'>
					<option>None</option>
					<option>URL</option>
					<option>Title</option>
					<option>Description</option>
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field5' id='field5'>
					<option>None</option>
					<option>URL</option>
					<option>Title</option>
					<option>Description</option>
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field6' id='field6'>
					<option>None</option>Format: 
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>
				</select>
				</div>
				<div class="col">
				<select class="form-select form-select-sm" name='field7' id='field7'>
					<option>None</option>
					<option>URL</option>
					<option>Title</option>
					<option>Description</option>
					<option>Paysite</option>
					<option>Tags</option>
					<option>Models</option>
					<option>Title1</option>
					<option>Title2</option>
					<option>Title3</option>
					<option>Title4</option>
					<option>Embed</option>
					<option>Images</option>
					<option>Duration</option>
					<option>Origianl IDs</option>
				</select>
				</div>
				<div class="col-2">
					<div class="input-group">		
						<input class="form-control form-control-sm" name="images_delim" value=",">	
						<span class="input-group-text pb-0 pt-0" id="basic-addon2">Delim</span>
					</div>
				</div>
				
				</div>

				<div class="mb-3">
					<textarea class="form-control" id="exampleFormControlTextarea1" rows="20" name=input_string id="input_string"></textarea>
				</div>
				<div class="d-grid gap-2">
					<input class="btn btn-primary" type="submit" name="import-galleries" id="import-galleries" value="Import galleries"></input>
				</div>
		</form>
