<?php

class ImportTool extends DBTools
{
	private $ImportedGalleries;

	private function ExplodeTags ($tags,$nidle = ",") {
		$count=0;
		$tempTagsArray = explode($nidle, $tags);
		
		$tags = array();
		foreach ($tempTagsArray as $tag) {
			$tag = trim($tag);
			if ($tagID = $this->TagId($tag)) {
				$tags[$count] = $tagID;
				$count++;
			}
		}
					
		if ($count > 0) return $tags;
		else return FALSE;
	}

	public function ShowImportErrors() {
		foreach ($this->ImportedGalleries as $gallery) {
			if (isset($gallery['error'])) echo $gallery['url'] . " : " . $gallery['error'] . "<br>";
		}

	}

	private function CSVtoArray ($csv_array, $element_counter) {

		$import_galleries = array ();
		$url = $element_counter['url'];

		if (isset($element_counter['title'])) $title = $element_counter['title'];
		if (isset($element_counter['description'])) $description = $element_counter['description'];
		if (isset($element_counter['tags'])) $tags = $element_counter['tags'];
		if (isset($element_counter['paysite'])) $paysite = $element_counter['paysite'];
		if (isset($element_counter['model'])) $model = $element_counter['model'];
	
		$i = 0;

		foreach ($csv_array as $string) {
			$string = trim($string);
			$string_length = strlen($string);
			$string = explode("|", $string);

			$stringFieldsCount = count($string) - 1;

			$import_galleries [$i]['url'] = $string [$url];
			if (strpos($import_galleries [$i]['url'], "http://") === FALSE) {
				echo strpos($import_galleries [$i] ['url'], "http://") . "<br />";
				$import_galleries [$i]['error'] = 404;
			} else {
				$import_galleries [$i]['folder'] = md5($import_galleries [$i] ['url']);

				if (isset($title) && $stringFieldsCount >= $title) $import_galleries [$i] ['title'] = mysql_escape_string(substr($string[$title],0, 255));
					else $import_galleries [$i] ['title'] = "";

				if (isset($element_counter['description']) && $stringFieldsCount >= $description) {
					if (strcmp($string[$description], $import_galleries [$i] ['title']) == 0) {
						$import_galleries [$i] ['description'] = "";
					} else $import_galleries [$i] ['description'] = mysql_escape_string($string[$description]);
				}
					else $import_galleries [$i] ['description'] = "";

				if (isset($element_counter['tags']) && $stringFieldsCount >= $tags) {
					if ($tagIds = $this->ExplodeTags($string[$tags])) {
						if (count($tagIds) > 0) $import_galleries [$i] ['tags'] = $tagIds ;
						else $import_galleries [$i] ['tags'] = "";
					} else $import_galleries [$i] ['tags'] = "";
				} else $import_galleries [$i] ['tags'] = "";

				if (isset($element_counter['paysite']) && $stringFieldsCount >= $paysite) $import_galleries [$i] ['paysite'] = strip_tags($string[$paysite]);
					else $import_galleries [$i] ['paysite'] = ""; 
				if (isset($element_counter['model']) && $stringFieldsCount >= $model) $import_galleries [$i] ['model'] = strip_tags($string[$model]);
					else $import_galleries [$i] ['model'] = "";
			}	
			$i++;
		}
		return $import_galleries;
	}

	function __construct ($input_csv, $input, $paysite, $content) {
		$input_csv = explode ("\n", $input_csv);
		$import_gallery = $this->CSVtoArray ($input_csv, $input);
		$count = count($import_gallery);

		$sql = "SELECT * FROM paysites WHERE paysite_id = '".$paysite."'";
		$sql = mysql_query($sql) or die(mysql_error());
		$row = mysql_fetch_array ($sql,MYSQL_ASSOC);

		$niche = $row ['paysite_niche'];
		$base_tags = $row ['paysite_category'];

		for ($i=0; $i < $count; $i++) {
			if (isset($import_gallery[$i]['error'])) {
				$import_gallery[$i]['error'] = "URL error";
				echo $import_gallery[$i]['url'] . " : " . $import_gallery[$i]['error'] . "<br />";
			} else {
				if (isset($import_gallery[$i]['folder'])) $folder = $import_gallery[$i]['folder'];
				$sql = ("SELECT gal_md5 FROM galleries WHERE gal_md5 = '".$folder."'");
				$sql = mysql_query($sql) or die(mysql_error());

				if(mysql_num_rows($sql)) {
					$import_gallery[$i]['error'] = "URL Exist";
				} else {

					$gallery_url = $import_gallery[$i]['url'];

					if (isset($import_gallery[$i]['title'])) $title = $import_gallery[$i]['title'];

					if (isset($import_gallery[$i]['description'])) $description = $import_gallery[$i]['description'];
						else $description = "";

					if (isset($import_gallery[$i]['model'])) $models = $import_gallery[$i]['model'];
						else $models = "";

					$sql = "SELECT hosted_flag FROM paysites WHERE paysite_id LIKE '".$paysite."'";
					$sql = mysql_query($sql) or die(mysql_error());
					$row = mysql_fetch_array ($sql,MYSQL_ASSOC);

					$hosted = (int)$row['hosted_flag'];

					$sql = "INSERT INTO galleries (gal_source, gal_md5, gal_title, gal_description, gal_paysite, gal_type, gal_status, gal_added, gal_niche, hosted_flag)
						VALUES ('{$gallery_url}','{$folder}', '{$title}', '{$description}', '{$paysite}', '{$content}', 'new', CURDATE(), '{$niche}','{$hosted}')";

					mysql_query($sql) or die(mysql_error());

					$sql = "SELECT gal_id FROM galleries WHERE gal_md5 = '".$folder."'";
					$sql = mysql_query($sql) or die(mysql_error());
					$row = mysql_fetch_array ($sql,MYSQL_ASSOC);
					$gal_id =  $row['gal_id'];
		
					$sql = "UPDATE paysites
						SET last_update = CURDATE() WHERE paysite_id = '".$paysite."'";
					mysql_query($sql) or die(mysql_error());

					if (isset($import_gallery[$i]['tags']) && is_array($import_gallery[$i]['tags'])) {
						foreach ($import_gallery[$i]['tags'] as $tag) {
							$sql = "INSERT INTO galleries_tags (gal_id, gal_tags, gal_niche)
								VALUES ('{$gal_id}', '{$tag}', '{$niche}')";
							mysql_query($sql) or die(mysql_error());
						}
					} else {
						$sql = "INSERT INTO galleries_tags (gal_id, gal_tags, gal_niche)
								VALUES ('{$gal_id}', '{$base_tags}', '{$niche}')";
							mysql_query($sql) or die(mysql_error());
					}
					
					if (isset($models) && $models !== "") {
						$sql = "SELECT model_id FROM models WHERE model_name LIKE '{$model}'";

						$sql = "INSERT INTO galleries_models (gal_id, gal_models, gal_niche)
							VALUES ('{$gal_id}', '{$model}', '{$niche}')";
					}
					

					echo $gallery_url . " : OK<br />";
				}
			}
		}
		$this->ImportedGalleries = $import_gallery;
	}

}

?>
