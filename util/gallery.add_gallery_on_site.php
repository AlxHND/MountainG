<?php
header('Content-type: application/json');

$string = json_encode(
	array(
		'error' => 'Gallery insert failed'
	)
);
include("../config/config.php");
include("../classes/class.logger.php");
include("../classes/class.db_access.php");
include("../classes/class.models.php");
include("../classes/class.sources.php");
include("../classes/class.tags.php");
include("../classes/class.galleries.php");
include("../classes/class.sites.php");
include("../classes/class.sitesgalleries.php");
include("../classes/class.new-cache.php");
include("../lib/functions.php");
include("../classes/class.writers.php");

require_once("_auth.php");
$auth->requireAdminJson('Ошибка аутентификации при работе с добавлением галер на сайт. Нужны права администратора.');



if (isset($_POST['site_id'], $_POST['gal_id'], $_POST['title'], $_POST['gallery_unique'], $_POST['main_thumb'])) {

	$gal_id = (int)$_POST['gal_id'];
	$site_id = (int)$_POST['site_id'];
	$gallery_unique = (int)$_POST['gallery_unique'];
	$main_thumb = (int)$_POST['main_thumb'];
	$title = trim($_POST['title']);

	$used_title_id = (isset($_POST['used_title_id'])) ? $_POST['used_title_id'] : 0;
	$min_title_length = defined("MIN_TITLE_LENGTH") ? MIN_TITLE_LENGTH : 3;

	if (isset($_POST['plus_day'], $_POST['day_query_step'])) {

		if ($_POST['plus_day'] == 0) $days_offset = time() + intval($_POST['plus_day']) * 86400;
		else $days_offset = strtotime(date("Y/m/d", (time() + (intval($_POST['plus_day']) * 86400))));

		$query_step_in_seconds = ((int)$_POST['day_query_step'] > 0) ? (int)$_POST['day_query_step'] : 0;

		$query_on = $days_offset + $query_step_in_seconds;

		if (isset($_POST['writers_query']) && $_POST['writers_query']) {
			$writers_query = true;
			$sites = new Sites($db->_db);
			$sites->switchSite($site_id);
			$language = $sites->getLanguage();
			// var_dump($language);
			$writer_query = new WritersQuery();

			if ($writer_query->pushGallery($site_id, $gal_id, $main_thumb, $query_on, $language)) {
				$string = json_encode(
					array(
						'success' => $gal_id

					)
				);
			}
		} else {
			$writers_query = false;

			if (strlen($title) < $min_title_length) {
				$string = json_encode(
					array(
						'error' => 'Тайтл слишком короткий!'
					)
				);
			} else {
				if (queryGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $query_on, $used_title_id)) {
					$string = json_encode(
						array(
							'success' => $gal_id,
							'query_step_in_seconds' => $query_step_in_seconds
						)
					);
				}
			}
		}
	} else {
		if (strlen($title) < $min_title_length) {
			$string = json_encode(
				array(
					'error' => 'Тайтл слишком короткий!'
				)
			);
		} else {
			if (addGalleryToSite($site_id, $gal_id, $title, $gallery_unique, $main_thumb, $used_title_id)) {
				$string = json_encode(
					array(
						'success' => $gal_id
					)
				);
			}
		}
	}
}

echo $string;
