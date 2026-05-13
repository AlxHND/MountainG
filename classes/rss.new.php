<?php

class SelectTools
{

	private $thumb_select_mode = false;
	private $use_original_images = false;

	function __construct()
	{
		$this->db = DB::get();
		if (!$this->db) {
			die('Прблема с DB, нет коннекта');
		}
	}


	private function getTagId($name): int
	{
		$output = 0;

		$name = mysqli_escape_string($this->db, $name);

		$sql = "SELECT tag_id
			FROM tags
			WHERE tag_name = '" . $name . "'";
		$q_result = $this->db->query($sql);

		if ($row = $q_result->fetch_assoc()) {
			$output = $row['tag_id'];
		}

		return $output;
	}


	private function getModelId(string $name): int
	{
		$output = 0;

		$name = mysqli_escape_string($this->db, $name);

		$sql = "SELECT tag_id
				FROM model
				WHERE name = '" . $name . "'";
		$q_result = $this->db->query($sql);

		if ($row = $q_result->fetch_assoc()) {
			$output = $row['id_model'];
		}

		return $output;
	}


	public function selectTags(int $id)
	{

		$output = [];

		$sql = "SELECT tags.tag_id, tags.tag_name
			FROM galleries_tags 
			LEFT JOIN tags ON tags.tag_id = galleries_tags.gal_tags
			WHERE galleries_tags.gal_id = {$id}";

		$q_result = $this->db->query($sql);
		while ($row = $q_result->fetch_assoc()) {
			$output[$row['tag_id']] = $row['tag_name'];
		}


		return $output;
	}

	public function selectModels(int $id)
	{

		$output = [];

		$sql = "SELECT galleries_models.model_id, model.name
			FROM galleries_models 
			LEFT JOIN model ON galleries_models.model_id = model.id_model
			WHERE galleries_models.gallery_id = '" . $id . "'";
		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$output['model_' . $row['model_id']] = $row['name'];
		}
		return $output;
	}

	public function selectRssThumbs(int $id, $main_thumb = false)
	{

		if ($this->thumb_select_mode == 'random') {
			$sql = "SELECT image_id, image
					FROM galleries_pix
					WHERE gal_id = '" . $id . "' AND rss_flag = '1'
					ORDER BY RAND()
					LIMIT 1";
		} elseif ($main_thumb) {
			$sql = "SELECT image_id, image
				FROM galleries_pix
				WHERE gal_id = '" . $id . "' AND image_id = '" . intval($main_thumb) . "'";
		} else {
			$sql = "SELECT image_id, image
				FROM galleries_pix
				WHERE gal_id = '" . $id . "'
				AND rss_flag = '1'";
		}

		$q_result = $this->db->query($sql);
		while ($row = $q_result->fetch_assoc()) {
			$output[$row['image_id']] = $row['image'];
		}
		if (isset($output)) return $output;
		else return FALSE;
	}

	public function setThumbSelectMode($set_mode)
	{
		if (preg_match("#^(selected|random)$#", $set_mode)) {
			$this->thumb_select_mode = $set_mode;
			$result = true;
		} else $result = false;
		return $result;
	}

	public function setSelectOriginalImageMode()
	{
		$this->use_original_images = true;
	}

	public function isSatelite($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		$sql = "SELECT use_galleries_from FROM sites WHERE site_id = '" . $site_id . "'";
		$q_result = $this->db->query($sql);
		if ($row = $q_result->fetch_assoc()) {
			$result = $row['use_galleries_from'];
		}
		return $result;
	}

	public function getDeletedGalleries($site_id)
	{
		$result = false;
		$site_id = (int)$site_id;
		if ($site_id > 0) {

			$sql = "SELECT 
							gal_url
						FROM 
							galleries_delete_rss 
						WHERE 
							site_id  = ?
						ORDER BY added_on DESC";
			$stmt = $this->db->prepare($sql);
			if ($stmt) {

				if ($stmt->bind_param("i", $site_id)) {
					if ($stmt->execute()) {
						$gal_url = false;
						$stmt->bind_result($gal_url);
						while ($stmt->fetch()) {
							$result[] = $gal_url;
						}
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT params not binded: '" . $stmt->error . "'", true);
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $this->db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No Site_id connect. ", true);
		}
		// var_dump($site_id, $result);
		return $result;
	}


	public function selectSiteGalleries(int $siteId, int $limitation, $niche = 0, $type = 0, $excludeNiche = 0, $sort = false, int $offset = 0, $smart_thumbs = false, $thumbs_select_by = false)
	{
		$isSatelite = $this->isSatelite($siteId);
		if ($isSatelite) {
			$originalSiteId = $siteId;
			$siteId = $isSatelite;
		}

		$offset = $offset * $limitation;
		$sortOrder = $sort !== false ? "ASC" : "DESC";

		$where = [];
		$join = [];

		$where[] = "galleries.gal_status = 'OK'";

		// 1. Подключение ниш
		if ($niche) {
			$niches = is_array($niche) ? $niche : [$niche];
			foreach ($niches as $tagName) {
				if ($tagId = $this->getTagId($tagName)) {
					$where[] = "galleries.gal_id IN (
						SELECT gal_id FROM galleries_tags WHERE gal_tags = '{$tagId}'
					)";
				}
			}
		}

		// 2. Исключение ниш
		if ($excludeNiche) {
			$exclude = is_array($excludeNiche) ? $excludeNiche : [$excludeNiche];
			$excludeIds = [];
			foreach ($exclude as $tagName) {
				if ($tagId = $this->getTagId($tagName)) {
					$excludeIds[] = $tagId;
				}
			}
			if (!empty($excludeIds)) {
				$where[] = "galleries.gal_id NOT IN (
					SELECT gal_id FROM galleries_tags WHERE gal_tags IN (" . implode(',', $excludeIds) . ")
				)";
			}
		}

		// 3. Фильтрация по типу
		if ($type && in_array($type, ['Pics', 'Movies'])) {
			$where[] = "galleries.gal_type = '{$type}'";
		}

		// 4. Обязательное условие
		$where[] = "galleries.crop_flag = '1'";

		// 5. Сначала получаем настройки сайта
		$siteQuery = "SELECT site_name, sites_url_length, sites_gallery_url, site_thumb_size, site_own_titles, site_own_main_thumbs, digit_base_for_id FROM sites WHERE site_id = " . ($isSatelite ? (int)$originalSiteId : $siteId);
		$siteResult = $this->db->query($siteQuery);

		if (!$siteData = $siteResult->fetch_assoc()) {
			return false;
		}

		$thumbSize = explode('x', $siteData['site_thumb_size']);
		$urlRules = $siteData['sites_gallery_url'];
		$siteDomain = $siteData['site_name'];
		$digitBase = ($siteData['digit_base_for_id'] == 10) ? false : $siteData['digit_base_for_id'];
		$localIdFlag = strpos($urlRules, "#LOCALID#") !== false;

		// 6. Подготовка выбора умных тумбов
		$thumbSelect = '';
		if ($smart_thumbs) {
			if ($this->use_original_images && $this->thumb_select_mode == 'random') {
				$thumbSelect = "(SELECT CONCAT(gppx.image_id,':',gppx.image) FROM galleries_pix AS gppx WHERE gppx.gal_id = site_{$siteId}.gal_id AND rss_flag = '1' ORDER BY RAND() LIMIT 1) AS own_main_thumb, ";
			} elseif ($this->thumb_select_mode == 'random') {
				$thumbSelect = "(SELECT image_id FROM galleries_pix AS gppx WHERE gppx.gal_id = site_{$siteId}.gal_id AND rss_flag = '1' ORDER BY RAND() LIMIT 1) AS own_main_thumb, ";
			} elseif ($siteData['site_own_main_thumbs']) {
				$thumbSelect = "site_{$siteId}.own_main_thumb, ";
			}
		}

		// 7. Основной запрос к галереям
		$galleryQuery = "
			SELECT 
				site_{$siteId}.id,
				site_{$siteId}.url_desc,
				site_{$siteId}.pageviews,
				site_{$siteId}.likes,
				site_{$siteId}.url_desc,
				site_{$siteId}.time_added,
				" . ($siteData['site_own_titles'] ? "site_{$siteId}.own_title," : "") . "
				{$thumbSelect}
				galleries.unique_gal, galleries.gal_paysite, galleries.gal_md5, galleries.gal_id, galleries.gal_source, galleries.gal_title, 
				galleries.gal_description, galleries.gal_added, galleries.gal_content_count, galleries.gal_niche, 
				galleries.gal_type, galleries.gal_thumb, galleries.hosted_flag,
				paysites.paysite_affiliate, paysites.paysite_name
			FROM site_{$siteId}
			LEFT JOIN galleries ON galleries.gal_id = site_{$siteId}.gal_id
			LEFT JOIN sites ON sites.site_id = '{$siteId}'
			LEFT JOIN paysites ON paysites.paysite_id = galleries.gal_paysite
			WHERE " . implode(' AND ', $where) . "
			ORDER BY site_{$siteId}.id {$sortOrder}
			LIMIT {$offset}, {$limitation}
		";

		// var_dump($galleryQuery); die;
		$galleryResult = $this->db->query($galleryQuery);

		$output = [];
		while ($row = $galleryResult->fetch_assoc()) {
			$id = $row['gal_id'];

			$localId = $digitBase ? base_convert($row['id'], 10, $digitBase) : $row['id'];
			$globalId = $digitBase ? base_convert($id, 10, $digitBase) : $id;

			$url = str_replace(
				["#TYPE#", "#LOCALID#", "#ID#", "#GALNAME#"],
				[strtolower($row['gal_type']), $localId, $globalId, $row['url_desc']],
				$urlRules
			);

			$slug = preg_replace('#^https?://(www\.)?' . str_replace('.', '\.', $siteDomain) . '/#', '', $url);
			$slug = rtrim($slug, '/');

			$output[$id] = [
				'gal_id' => $row['gal_id'],
				'localId' => $row['id'],
				'unique_gal' => $row['unique_gal'],
				'md5' => $row['gal_md5'],
				'added_on' => $row['time_added'],
				'digibasedLocalId' => $localId,
				'localIdFlag' => $localIdFlag,
				'title' => isset($row['own_title']) && $row['own_title'] !== '' ? stripslashes($row['own_title']) : stripslashes($row['gal_title']),
				'description' => stripslashes($row['gal_description']),
				'niche' => $row['gal_niche'],
				'date' => $row['gal_added'],
				'type' => $row['gal_type'],
				'count' => $row['gal_content_count'],
				'source' => $row['gal_source'],
				'hosted' => $row['hosted_flag'],
				'thumbSize' => ['width' => $thumbSize[0], 'height' => $thumbSize[1]],
				'paysite' => $row['paysite_name'],
				'paysite_id' => $row['gal_paysite'],
				'sponsor' => $row['paysite_affiliate'],
				'gal_thumb' => $row['gal_thumb'],
				'url' => $url,
				'slug' => $slug,
				'pageviews' => $row['pageviews'],
				'likes' => $row['likes'],
				'own_main_thumb' => $row['own_main_thumb'] ?? $row['gal_thumb'],
			];
		}

		return !empty($output) ? $output : false;
	}


	public function getCJTubeGalleries($siteId, $limitation, $niche = 0, $type = 0, $excludeNiche = 0, $sort = false, $offset = 0, $smart_tumbs = false, $thumbs_select_by = false)
	{
		$nicheSelectString = "";
		$tagFlag = false;
		if ($niche === 0) {
			$nicheSelectString = "";
		} elseif (!(is_array($niche)) && $tagId = $this->getTagId($niche)) {
			$nicheSelectString = " LEFT JOIN galleries_tags ON galleries_tags.gal_id = galleries.gal_id WHERE galleries_tags.gal_tags = '" . $tagId . "' ";
		} elseif (is_array($niche)) {
			foreach ($niche as $nicheElement) {
				if ($tagId = $this->getTagId($nicheElement)) {
					if ($tagFlag) $nicheSelectString .= " AND ";
					else $nicheSelectString .= " WHERE ";
					$nicheSelectString .= " galleries.gal_id IN (SELECT site_" . $siteId . ".gal_id FROM site_" . $siteId . " LEFT JOIN galleries_tags ON galleries_tags.gal_id = site_" . $siteId . ".gal_id WHERE galleries_tags.gal_tags = '" . $tagId . "') ";
					$tagFlag = true;
				}
			}
		} else {
			return false;
		}


		$tagFlag = false;

		$typeSelectString = "";
		$nicheExcludeSelectString = "";

		if ($type !== 0) {
			$typeSelectString = ($nicheSelectString !== '') ? " AND galleries.gal_type = '" . $type . "' " : " WHERE galleries.gal_type = '" . $type . "' ";
		}

		if ($excludeNiche !== 0 && !is_array($excludeNiche)) {
			if (!(is_array($excludeNiche)) && $excludeTagId = $this->getTagId($excludeNiche)) {
				if ($nicheSelectString !== '' || $typeSelectString !== '') {
					if (isset($tagId) && $tagId !== $excludeTagId) {
						$nicheExcludeSelectString = " AND galleries.gal_id NOT IN (SELECT site_" . $siteId . ".gal_id FROM site_" . $siteId . " LEFT JOIN galleries_tags ON galleries_tags.gal_id = site_" . $siteId . ".gal_id WHERE galleries_tags.gal_tags = '" . $excludeTagId . "') ";
					}
				} else {
					$nicheExcludeSelectString = " WHERE galleries.gal_id NOT IN (SELECT site_" . $siteId . ".gal_id FROM site_" . $siteId . " LEFT JOIN galleries_tags ON galleries_tags.gal_id = site_" . $siteId . ".gal_id WHERE galleries_tags.gal_tags = '" . $excludeTagId . "') ";
				}
			} elseif (is_array($excludeNiche)) {
				foreach ($excludeNiche as $nicheElement) {
					if ($excludeTagId = $this->getTagId($nicheElement)) {
						if ((isset($tagId) && $tagId !== $excludeTagId) || !(isset($tagId))) {
							if ($tagFlag) $nicheExcludeSelectString .= " OR galleries_tags.gal_tags = '" . $excludeTagId . "'";
							else {
								if ($nicheSelectString !== '' || $typeSelectString !== '') $nicheExcludeSelectString .= " AND";
								else $nicheExcludeSelectString .= " WHERE";
								$nicheExcludeSelectString .= " galleries.gal_id NOT IN (SELECT site_" . $siteId . ".gal_id FROM site_" . $siteId . " LEFT JOIN galleries_tags ON galleries_tags.gal_id = site_" . $siteId . ".gal_id WHERE galleries_tags.gal_tags = '" . $excludeTagId . "'";
								$tagFlag = TRUE;
							}
						}
					}
				}
				if ($tagFlag) $nicheExcludeSelectString .= ") ";
			}
		}


		if ($nicheSelectString !== '' || $typeSelectString !== '' || $nicheExcludeSelectString !== '') {
			// $newCropperAdd = " AND galleries.crop_flag = '1' ";
			$newCropperAdd = " AND galleries.gal_status = 'OK' ";
		} else {
			// $newCropperAdd = " WHERE galleries.crop_flag = '1' ";
			$newCropperAdd = " WHERE galleries.gal_status = 'OK' ";
		}

		$siteId = (int)$siteId;
		$limitation = (int)$limitation;



		if ($sort !== false) {
			$sort = " ORDER BY galleries.gal_id ASC";
		} else {
			$sort = " ORDER BY galleries.gal_id DESC";
		}

		$offset = intval($offset);

		if ($offset !== 0) {
			$offset = $offset * $limitation;
			$offset = " LIMIT " . $offset . ", " . $limitation;
		} else {
			$offset = " LIMIT 0, " . $limitation;
		}

		$sql = "SELECT sites_url_length, sites_gallery_url, site_thumb_size, site_own_titles, 
					   site_own_main_thumbs, digit_base_for_id
				FROM sites";

		$sql .= " WHERE site_id = '" . $siteId . "'";
		$q_result = $this->db->query($sql);
		if ($row = $q_result->fetch_assoc()) {
			$thumbSize = explode("x", $row['site_thumb_size']);
			$urlRules = $row['sites_gallery_url'];
			$digit_base_for_id = $row['digit_base_for_id'];
			$url_length = $row['sites_url_length'];

			if (strstr($urlRules, "#LOCALID#")) $localIdFlag = TRUE;
			else $localIdFlag = FALSE;
			// var_dump($digit_base_for_id);
			if ($digit_base_for_id == 10) $digit_base_for_id = false;

			if ($smart_tumbs && $this->thumb_select_mode == 'random') {
				$sql .= " (SELECT image_id
							FROM galleries_pix AS gppx
							WHERE gppx.gal_id = galleries.gal_id AND rss_flag = '1'
							ORDER BY RAND()
							LIMIT 1) AS own_main_thumb, ";
				$smart_tumbs_sql = "";
			} else {
				if ($smart_tumbs || $this->thumb_select_mode == 'selected') $smart_tumbs_sql = " AND gal_thumb > 0 ";
				else $smart_tumbs_sql = "";
			}
			$sql = "SELECT galleries.gal_paysite,galleries.gal_id,  galleries.gal_md5,
					galleries.gal_source, galleries.gal_title, galleries.gal_description, galleries.gal_added, 
					galleries.gal_content_count, galleries.gal_niche, galleries.gal_type, galleries.gal_thumb,
					paysites.paysite_affiliate, 
					paysites.paysite_name, galleries.hosted_flag, galleries.embed
				FROM 
					galleries 
					LEFT JOIN paysites ON paysites.paysite_id = galleries.gal_paysite
				" . $nicheSelectString .
				$typeSelectString .
				$nicheExcludeSelectString .
				$newCropperAdd .
				$smart_tumbs_sql .
				$sort .
				$offset;
			// echo $sql;



			$q_result = $this->db->query($sql);
			while ($row = $q_result->fetch_assoc()) {
				$id = $row['gal_id'];

				$output[$id]['md5'] = $row['gal_md5'];
				$output[$id]['gal_id'] = $row['gal_id'];
				$output[$id]['localId'] = $row['gal_id'];
				$output[$id]['title'] = stripslashes($row['gal_title']);
				$output[$id]['title'] = stripslashes($output[$id]['title']);

				$output[$id]['description'] = stripslashes($row['gal_description']);
				$output[$id]['niche'] = $row['gal_niche'];
				$output[$id]['date'] = $row['gal_added'];
				$output[$id]['type'] = $row['gal_type'];
				$output[$id]['count'] = $row['gal_content_count'];
				$type = strtolower($output[$id]['type']);
				$output[$id]['source'] = $row['gal_source'];

				$output[$id]['hosted'] = $row['hosted_flag'];

				$output[$id]['thumbSize']['width'] =  $thumbSize[0];
				$output[$id]['thumbSize']['height'] =  $thumbSize[1];
				$output[$id]['paysite'] = $row['paysite_name'];
				$output[$id]['paysite_id'] = $row['gal_paysite'];
				$output[$id]['sponsor'] = $row['paysite_affiliate'];
				$output[$id]['gal_thumb'] = $row['gal_thumb'];
				$output[$id]['embed'] = $row['embed'];

				$url_desc = nonEngTitleToLatin($output[$id]['title']);
				$url_desc = strtolower($url_desc);
				$url_desc = preg_replace("/[^a-z0-9\s]/", "", $url_desc);
				$url_desc = preg_replace("/\s+/", " ", $url_desc);
				$url_desc = str_replace(" ", "-", $url_desc);
				$url_desc = trim(substr($url_desc, 0, $url_length));

				if (!$url_desc && strlen($url_desc) < 3) {
					$url_desc = "na";
				}

				if ($smart_tumbs && $this->thumb_select_mode == 'random' && isset($row['own_main_thumb']) && $row['own_main_thumb']) $output[$id]['own_main_thumb'] = $row['own_main_thumb'];
				elseif (($smart_tumbs || $this->thumb_select_mode == 'selected') && isset($row['own_main_thumb']) && $row['own_main_thumb']) $output[$id]['own_main_thumb'] = $row['own_main_thumb'];
				elseif (($smart_tumbs || $this->thumb_select_mode == 'selected') && isset($row['own_main_thumb']) && !$row['own_main_thumb'])  $output[$id]['own_main_thumb'] = $row['gal_thumb'];
				else $output[$id]['own_main_thumb'] = false;

				$output[$id]['url'] = str_replace("#TYPE#", $type, $urlRules);
				if (isset($digit_base_for_id) && $digit_base_for_id) {
					// var_dump($digit_base_for_id);
					$local_gal_id = base_convert($output[$id]['localId'], 10, $digit_base_for_id);
				} else {
					$local_gal_id = $output[$id]['localId'];
				}
				// var_dump($local_gal_id);
				$output[$id]['url'] = str_replace("#LOCALID#", $local_gal_id, $output[$id]['url']);
				$output[$id]['url'] = str_replace("#ID#", $id, $output[$id]['url']);
				$output[$id]['url'] = str_replace("#GALNAME#", $url_desc, $output[$id]['url']);
				if ($localIdFlag) {
					$output[$id]['localIdFlag'] = TRUE;
				} else {
					$output[$id]['localIdFlag'] = FALSE;
				}
			}
		}
		if (isset($output)) return $output;
		else return FALSE;
	}


	public function all(): array
	{
		// Получаем параметры из кастомного Request
		$params = [
			'sort' => $_GET['sort'] ?? 'id',
			'order' => $_GET['order'] ?? 'asc',
			'count' => $_GET['count'] ?? 50,
			'offset' => $_GET['offset'] ?? 0,
			'type' => $_GET['type'] ?? '',
			'paysite' => $_GET['paysite'] ?? 0,
			'status' => $_GET['status'] ?? '',
			'search' => $_GET['search'] ?? '',
			'category' => isset($_GET['category']) ? $this->getTagId($_GET['category']) : 0,
			'model' => isset($_GET['model']) ? $this->getModelId($_GET['model']) : 0,
			'searchBy' => $_GET['searchBy'] ?? 'title',
			'niche' => $_GET['niche'] ?? '',

			'main_gal' => (int)($_GET['main_gal'] ?? 0),
			'noniche' => isset($_GET['noniche']) ? $this->getTagId($_GET['noniche']) : 0,

			'exclusive_site' => (isset($_GET['exclusive_site']) && preg_match('/^[a-z0-9.-]+$/', $_GET['exclusive_site'])) ? $_GET['exclusive_site'] : '',
		];

		$exclusive_site = 0;

		if (!empty($params['exclusive_site'])) {
			$exclusiveRes = $this->db->query("SELECT site_id FROM sites WHERE site_name = '" . $params['exclusive_site'] . "'");

			$fetchResult = $exclusiveRes->fetch_row();

			$exclusive_site = $fetchResult ? (int)$fetchResult[0] : 0;

			if (empty($exclusive_site)) {
				// $log = new Logger(__METHOD__.": Запрошен неверный домен для эксклюзивного RSS", true );
				return [];
			}
		}

		$sqlVars = [];
		$filters = [];

		$filters[] = "gal_status = 'OK'";

		if ($exclusive_site) {
			$filters[] = "galleries.unique_for_export_site = :unique_for_export_site";
			$sqlVars['unique_for_export_site'] = $exclusive_site;
		}

		// Фильтр по основным галереям
		if ($params['main_gal']) {
			$filters[] = "galleries.main_gal = :main_gal";
			$sqlVars['main_gal'] = $params['main_gal'];
		}

		if ($params['noniche']) {
			$filters[] = "galleries.gal_id NOT IN (SELECT gal_id FROM galleries_tags WHERE galleries_tags.gal_tags = :noniche) ";
			$sqlVars['noniche'] = $params['noniche'];
		}

		// Фильтр по поиску
		if (!empty($params['search'])) {
			switch ($params['searchBy']) {
				case 'url':
					$searchColumn = 'gal_source';
					break;
				case 'desc':
					$searchColumn = 'gal_description';
					break;
				case 'titledesc':
					$searchColumn = "(LOWER(galleries.gal_title) LIKE :search OR LOWER(galleries.gal_description) LIKE :search)";
					break;
				default:
					$searchColumn = 'gal_title';
			}


			$filters[] = "LOWER($searchColumn) LIKE :search";
			$sqlVars['search'] = '%' . strtolower($params['search']) . '%';
		}

		// Фильтр по типу контента
		if (!empty($params['type'])) {
			$filters[] = "galleries.gal_type = :type";
			$sqlVars['type'] = $params['type'];
		}

		// Фильтр по paysite
		if ($params['paysite'] > 0) {
			$filters[] = "galleries.gal_paysite = :paysite";
			$sqlVars['paysite'] = $params['paysite'];
		}

		// Фильтр по нише
		if (!empty($params['niche'])) {
			$filters[] = "galleries.gal_niche = :niche";
			$sqlVars['niche'] = $params['niche'];
		}

		// Фильтр по статусу
		if (!empty($params['status'])) {
			$filters[] = "galleries.gal_status = :status";
			$sqlVars['status'] = $params['status'];
		}

		// Фильтр по категории
		if ($params['category'] > 0) {
			$filters[] = "galleries.gal_id IN (SELECT gal_id FROM galleries_tags WHERE gal_tags = :category)";
			$sqlVars['category'] = $params['category'];
		}

		// Составляем SQL-запрос
		$sql = "SELECT DISTINCT
					galleries.gal_id AS gal_id, 
					galleries.gal_source AS url, 
					'none' AS source, 
					galleries.hosted_flag AS hosted,
					galleries.gal_title AS title, 
					galleries.gal_niche AS niche, 
					galleries.gal_added AS added,    
					galleries.gal_type AS type,
					galleries.gal_content_count AS count,
					paysites.paysite_name AS paysite, 
					paysites.paysite_affiliate AS affiliate,
					galleries.gal_status AS status, 
					galleries.gal_paysite AS paysite_id, 
					galleries.gal_thumb AS gal_thumb,
					galleries.gal_md5 AS gal_md5
				FROM galleries
				LEFT JOIN paysites ON galleries.gal_paysite = paysites.paysite_id";

		if (!empty($filters)) {
			$sql .= " WHERE " . implode(" AND ", $filters);
		}

		// Сортировка
		$allowedSort = ['id', 'title', 'date', 'paysite', 'niche', 'pics', 'status'];
		$sortColumn = in_array($params['sort'], $allowedSort) ? $params['sort'] : 'id';

		$orderDirection = in_array(strtolower($params['order']), ['asc', 'desc']) ? strtoupper($params['order']) : 'ASC';
		$sql .= " ORDER BY galleries.gal_$sortColumn $orderDirection";

		// Лимит и оффсет
		$sql .= " LIMIT :offset, :count";
		$sqlVars['offset'] = $params['offset'];
		$sqlVars['count'] = $params['count'];

		try {
			$db = PDOConnection::get();
			$stmt = $db->prepare($sql);
			foreach ($sqlVars as $key => &$value) {
				$stmt->bindParam(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
			}
			$stmt->execute();

			$galleries = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			return $galleries;
		} catch (\Exception $e) {
			$log = new Logger(__METHOD__ . ": Ошибка запроса: " . $e->getMessage());
			return [];
		}
	}
}
