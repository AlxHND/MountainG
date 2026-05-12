<?php
class Sites
{
	var $_db;

	// номер модели
	private $id = false;
	// имя, допускаемые символы a-zA-Z,"-","'"
	private $name;
	private $niche;
	private $handFlag;
	public $galleryCounter;
	public $site_own_titles;
	public $site_own_main_thumbs;
	private $language;
	private $keywords;
	private $additional_redis_server;
	private $vcdn_type;

	public 	$redis_server, $galleryUrl, $localId, $last_update, $accept_gifs, $site_type,
		$use_galleries_from, $thumb_by_horiz_width,
		$digit_base_for_id, $default_title_for_tag, $only_export_site;

	private $pw_transaction_data, $pw_site_id;

	function __construct(PDO $db_connect = null)
	{
		$this->_db = ($db_connect) ? $db_connect : new db_access();
		$this->redis_server = false;
		$this->id = false;
		$this->name = false;
		$this->niche = false;
		$this->handFlag = false;
		$this->galleryUrl = false;
		$this->localId = false;
		$this->redis_server = false;
		$this->last_update = false;
		$this->accept_gifs = false;
		$this->site_type = false;
		$this->site_own_titles = false;
		$this->site_own_main_thumbs = false;
		$this->language = false;
		$this->keywords = false;
		$this->gals_count = false;
		$this->use_galleries_from = false;
		$this->thumb_by_horiz_width = 0;
		$this->max_times_used_gals = -1;
		$this->additional_redis_server = -1;
		$this->vcdn_type = false;
		//parent::__construct();
	}

	function getId()
	{
		return $this->id;
	}
	function getName()
	{
		return $this->name;
	}
	function getNiche()
	{
		return $this->niche;
	}
	function getHandFlag()
	{
		return $this->handFlag;
	}
	function getGalleryUrl()
	{
		return $this->galleryUrl;
	}
	function ifLocalId()
	{
		return $this->localId;
	}
	function redisServer()
	{
		return intval($this->redis_server);
	}
	function redisServerAdditional()
	{
		return intval($this->additional_redis_server);
	}
	function lastUpdate()
	{
		return intval($this->last_update);
	}
	function acceptGifs()
	{
		return intval($this->accept_gifs);
	}
	function siteType()
	{
		return intval($this->site_type);
	}
	function getLanguage()
	{
		return $this->language;
	}
	function getKeywords()
	{
		return $this->keywords;
	}
	function getDigitalBaseForId()
	{
		return $this->digit_base_for_id;
	}
	function getThumbHorizWidth()
	{
		return $this->thumb_by_horiz_width;
	}
	function getVCDNType()
	{
		return $this->vcdn_type;
	}
	function getUseGalleriesFrom()
	{
		return $this->use_galleries_from ? $this->use_galleries_from : false;
	}

	function getTableInfo()
	{
		$this->_db->debug = true;
		$rs = $this->_db->query("show columns from sites");
		$site = $rs->fetchAll(\PDO::FETCH_ASSOC);
		print_r($site);
		$this->_db->debug = false;
	}

	// возвращает массив заполненый данными о модели с id, объект переинициализируется в соответствии с айди.
	function getSite($site_id, $count = false)
	{
		$site_id = (int)$site_id;

		$db = DB::get();

		if ($db && $site_id > 0) {
			$sql = 'SELECT  site_id, site_name, site_niche, hand_flag, sites_gallery_url, local_id_flag, redis_server,
							last_update, accept_gifs, site_type, site_own_titles, site_own_main_thumbs, language,
							keywords, use_galleries_from, digit_base_for_id, thumb_by_horiz_width, additional_redis_server,
							vcdn_type, default_title_for_tag, only_export_site
					FROM `sites`
					WHERE `site_id` = ?';
			if ($stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("i", $site_id)) {
					if ($stmt->execute()) {
						$site = array();
						$stmt->bind_result(
							$site['site_id'],
							$site['site_name'],
							$site['site_niche'],
							$site['hand_flag'],
							$site['sites_gallery_url'],
							$site['local_id_flag'],
							$site['redis_server'],
							$site['last_update'],
							$site['accept_gifs'],
							$site['site_type'],
							$site['site_own_titles'],
							$site['site_own_main_thumbs'],
							$site['language'],
							$site['keywords'],
							$site['use_galleries_from'],
							$site['digit_base_for_id'],
							$site['thumb_by_horiz_width'],
							$site['additional_redis_server'],
							$site['vcdn_type'],
							$site['default_title_for_tag'],
							$site['only_export_site']
						);
						$stmt->fetch();

						$this->id = $site['site_id'];
						$this->name = $site['site_name'];
						$this->niche = $site['site_niche'];
						$this->handFlag = $site['hand_flag'];
						$this->galleryUrl = $site['sites_gallery_url'];
						$this->localId = $site['local_id_flag'];
						$this->redis_server = intval($site['redis_server']);
						$this->last_update = intval($site['last_update']);
						$this->accept_gifs = intval($site['accept_gifs']);
						$this->site_type = $site['site_type'];
						$this->site_own_titles = $site['site_own_titles'];
						$this->site_own_main_thumbs = $site['site_own_main_thumbs'];
						$this->language = $site['language'];
						$this->keywords = $site['keywords'];
						$this->use_galleries_from = $site['use_galleries_from'];
						$this->digit_base_for_id = $site['digit_base_for_id'];
						$this->thumb_by_horiz_width = $site['thumb_by_horiz_width'];
						$this->additional_redis_server = $site['additional_redis_server'];
						$this->vcdn_type = $site['vcdn_type'];
						$this->default_title_for_tag = $site['default_title_for_tag'];
						$this->only_export_site =  $site['only_export_site'];
					} else {
						echo "error getting site data '" . $site_id . "'";
					}
				}
				$stmt->close();
				if ($this->id) {
					if ($count) {
						$sql = "select count(id) from site_" . $site_id . ";";
						if ($stmt = $db->prepare($sql)) {
							if ($stmt->execute()) {
								$stmt->bind_result($site['gals_count']);
								$this->gals_count = $site['gals_count'];
							}
							$stmt->close();
						}
					}
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT Prepare fail", true);
			}




			return $site;
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
			return false;
		}
	}



	function getGalleriesPageviews()
	{
		$result = false;

		if ($this->id) {
			$sql = "select id, pageviews from site_" . $this->id . ";";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result[$row['id']] = $row['pageviews'];
					}
				}
			}
		}
		return $result;
	}



	function getGalleriesLikes(): array
	{

		$site_id = $this->id;

		if ($site_id < 1) {
			return [];
		}

		$sql = "SELECT id, likes 
				FROM site_{$site_id};";
		$rs = $this->_db->query($sql);

		if (!$rs) {
			return [];
		}

		$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
		if (empty($rows)) {
			return [];
		}

		foreach ($rows as $row) {
			$result[$row['id']] = $row['likes'];
		}

		return $result;
	}


	function setLikes(int $gal_id, int $likes): bool
	{

		$site_id = $this->id;

		if ($site_id < 1 || $likes < 0) {
			return false;
		}

		$sql = "UPDATE site_{$site_id}
						SET likes = {$likes}, 
						rating = CASE 
							WHEN (pageviews) = 0 THEN 0 
							ELSE {$likes} / pageviews 
						END
						WHERE id = {$gal_id};";

		$rs = $this->_db->query($sql);

		if (!$rs) {
			return false;
		}

		return true;
	}

	function updateSiteLikes(int $gal_id, int $site_id, int $likes): bool
	{

		if ($site_id < 1 || $likes < 0) {
			return false;
		}

		$sql = "UPDATE site_{$site_id} 
				SET likes = likes + {$likes}, 
				rating = CASE 
					WHEN (pageviews) = 0 THEN 0 
					ELSE (likes + {$likes}) / pageviews 
				END
				WHERE id = {$gal_id};";

		$rs = $this->_db->query($sql);

		if (!$rs) {
			return false;
		}

		return true;
	}

	function setPageviews(int $gal_id, int $pageviews)
	{
		$site_id = $this->id;

		if ($site_id < 1 || $pageviews < 0) {
			return false;
		}

		$sql = "UPDATE site_{$site_id} 
				SET pageviews = {$pageviews}, 
					rating = CASE 
						WHEN {$pageviews} = 0 THEN 0 
						ELSE likes / {$pageviews} 
					END 
				WHERE id = {$gal_id};";
		$rs = $this->_db->query($sql);

		if (!$rs) {
			return false;
		}

		return true;
	}

	function updatePageviews(int $gal_id, int $pageviews)
	{

		$site_id = $this->id;

		if ($site_id < 1 || $pageviews < 0) {
			return false;
		}

		$sql = "UPDATE site_" . $this->id . " 
				SET pageviews = (pageviews + {$pageviews}) , 
				rating = CASE 
						WHEN (pageviews + {$pageviews}) = 0 THEN 0 
						ELSE likes / (pageviews + {$pageviews}) 
				END  
				WHERE id = '" . $gal_id . "';";
		$rs = $this->_db->query($sql);

		if (!$rs) {
			return false;
		}

		return true;
	}


	function pageviewsMassUpdateTransactionStart(int $site_id)
	{
		$this->pw_transaction_data = [];
		$this->pw_site_id = ($site_id > 0) ? $site_id : false;
	}

	function pageviewsMassUpdateTransactionAddPageview(int $gal_id, int $pw_changes)
	{
		if ($pw_changes > 0 && $gal_id > 0) {
			$this->pw_transaction_data[] =	"({$gal_id}, {$pw_changes}, 0)";
		}
	}

	private function checkMassUpdateIds(array $dataArray): array
	{

		$table = "site_" . (int)$this->pw_site_id;

		$ids = array_map(function ($row) {
			preg_match('/^\((\d+),\s*\d+,\s*\d+\)$/', $row, $matches);
			if (!empty($matches[1])) {
				return $matches[1];
			}
		}, $dataArray);

		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$sql = "SELECT id FROM {$table} WHERE id IN ($placeholders)";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute($ids);
		$existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

		return $existingIds;
	}

	function pageviewsMassUpdateTransactionExecute()
	{

		if ((int)$this->pw_site_id < 1) {
			$log = new Logger(__METHOD__ . ": Задан пустой pw_site_id", true);
			throw new Exception(__METHOD__ . ": Задан пустой pw_site_id");
		}

		$table = "site_" . (int)$this->pw_site_id;

		$existingIds = $this->checkMassUpdateIds($this->pw_transaction_data);

		$insertRows = [];

		foreach ($this->pw_transaction_data as $row) {
			preg_match('/^\((\d+),\s*\d+,\s*\d+\)$/', $row, $matches);
			if (!empty($matches[1]) && in_array($matches[1], $existingIds)) {
				$insertRows[] = $row;
			}
		}

		if (empty($insertRows)) {
			$log = new Logger(__METHOD__ . ": Пустой массив данных для апдейта, Site#" . (int)$this->pw_site_id, true);
			return false;
		}


		$this->_db->query("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'STRICT_TRANS_TABLES',''))");

		$sql = "INSERT INTO {$table} (id, pageviews, rating)
				VALUES " . implode(",", $insertRows) . "
				ON DUPLICATE KEY UPDATE
					pageviews = pageviews + VALUES(pageviews),
					rating = CASE 
						WHEN (pageviews + VALUES(pageviews)) = 0 THEN 0 
						ELSE likes / (pageviews + VALUES(pageviews)) 
					END
				;";

		$rs = $this->_db->query($sql);

		if (!$rs) {
			$log = new Logger(__METHOD__ . ": Transaction failed", true);
			return false;
		}

		$this->pw_transaction_data = NULL;
		$this->pw_site_id = NULL;

		return true;
	}

	function clearMassError(): void
	{
		$site_id = $this->pw_site_id;

		$sql = "DELETE FROM site_{$site_id} 
				WHERE 
					gal_paysite = 0 AND 
					gal_type = 'none' AND 
					time_added < 1
				";

		$rs = $this->_db->query($sql);
	}


	function updateSitePageviews(int $gal_id, int $site_id, int $pageviews): bool
	{

		if ($site_id < 1 || $pageviews < 0) {
			return false;
		}

		$sql = "UPDATE site_{$site_id} 
				SET pageviews = pageviews + {$pageviews} , 
					rating = (likes / pageviews + {$pageviews})
				WHERE id = {$gal_id};";

		$rs = $this->_db->query($sql);

		if (!$rs) {
			return false;
		}

		return true;
	}

	function updateTitleint(int $local_gal_id, string $title): bool
	{

		$site_id = $this->id;

		if ($site_id < 0 || empty($title)) {
			return false;
		}

		$sql = "UPDATE site_{$site_id} 
				SET own_title = :title 
				WHERE id = :id;";

		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(
				[
					':title' => $title,
					':id' => $local_gal_id
				]
			);
		} catch (PDOException $e) {
			echo __METHOD__ . ' :: Ошибка добавления в базу данных: ' . $e->getMessage() . '<BR>';
			$log = new Logger(__METHOD__ . " :: Ошибка добавления в базу данных: " . $e->getMessage(), true);
			return false;
		}

		return true;
	}


	function getSiteGalleries_new($local_id_first = true): array
	{

		$site_id = $this->id;

		if ($site_id < 1) {
			return [];
		}

		$sql = "select * from site_{$site_id};";

		$rs = $this->_db->query($sql);

		if (!$rs) {
			return [];
		}

		$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);

		if (empty($rows)) {
			return [];
		}

		foreach ($rows as $row) {
			if ($local_id_first) {
				$result[$row['id']] = $row['gal_id'];
			} else {
				$result[$row['gal_id']] = $row['id'];
			}
		}

		return $result;
	}



	function listGalleries($site_id, $gals_per_page = 50, $page)
	{
		if ($this->id && $this->only_export_site === 0) {
			$this->galleriesCount($this->id);
		} else {
			return 0;
		}
	}

	function getSiteGalleries_newCache($galleries_list = false, $own_info = false)
	{
		$result = false;
		// $this->_db->debug = true;
		if ($galleries_list !== false && is_array($galleries_list)) {
			$galleries_list_addition = " where id in (";
			$list_count = count($galleries_list);
			$counter = 0;
			foreach ($galleries_list as $local_id => $global_id) {
				$galleries_list_addition .= (int)$local_id;
				$counter++;
				if ($list_count > $counter) $galleries_list_addition .= ",";
			}
			$galleries_list_addition .= ")";
		} else $galleries_list_addition = "";

		if ($this->id) {
			$sql = "select site_" . $this->id . ".id, site_" . $this->id . ".gal_id,
					site_" . $this->id . ".url_desc, site_" . $this->id . ".time_added, 
					site_" . $this->id . ".likes, site_" . $this->id . ".pageviews, site_" . $this->id . ".rating, ";
			if ($own_info) $sql .= " site_" . $this->id . ".own_title, site_" . $this->id . ".own_main_thumb, ";
			$sql .= "galleries.gal_paysite,galleries.gal_type, galleries.embed_flag, galleries.embed
					from site_" . $this->id .
				" left join galleries on 
					site_" . $this->id . ".gal_id = galleries.gal_id" . $galleries_list_addition . ";";

			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result[$row['id']]['id'] = $row['id'];
						$result[$row['id']]['global_id'] = $row['gal_id'];
						$result[$row['id']]['url_desc'] = $row['url_desc'];
						$result[$row['id']]['time_added'] = $row['time_added'];
						$result[$row['id']]['gal_type'] = $row['gal_type'];
						$result[$row['id']]['gal_paysite'] = $row['gal_paysite'];
						$result[$row['id']]['rating'] = $row['rating'];
						$likes = ($row['likes']) ? intval($row['likes']) : 0;
						$result[$row['id']]['likes'] = $likes;
						$pageviews = ($row['pageviews']) ? intval($row['pageviews']) : 0;
						$result[$row['id']]['pageviews'] = $pageviews;

						if ($own_info) {
							$result[$row['id']]['own_title'] = $row['own_title'];
							$result[$row['id']]['own_main_thumb'] = $row['own_main_thumb'];
						}

						$result[$row['id']]['video_embed'] = $row['embed_flag'] ? $row['embed'] : false;

						if ($row['embed']) {
							var_dump($result[$row['id']]['video_embed']);
						}
					}
				} else {
					echo "no rows";
				}
			} else {
				echo "RS failed";
				var_dump($this->_db->errorInfo());
			}
		} else {
			echo "no id";
		}

		return $result;
	}

	function getSiteMovieGalleries_newCache()
	{
		$result = false;
		//$this->_db->debug = true;
		if ($this->id) {
			$sql = "select id, time_added from site_" . $this->id . " where gal_id in (select gal_id from galleries where gal_type = 'Movies')";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result[$row['id']]['id'] = $row['id'];
						$result[$row['id']]['time_added'] = $row['time_added'];
					}
				}
			}
		}

		return $result;
	}

	function getSitePicsGalleries_newCache()
	{
		$result = false;
		//$this->_db->debug = true;
		if ($this->id) {
			$sql = "select id, time_added from site_" . $this->id . " where gal_id in (select gal_id from galleries where gal_type = 'Pics')";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result[$row['id']]['id'] = $row['id'];
						$result[$row['id']]['time_added'] = $row['time_added'];
					}
				}
			}
		}

		return $result;
	}

	function listSiteGalleries()
	{
		$result = false;
		//$this->_db->debug = true;
		if ($this->id) {
			$sql = "select * from site_" . $this->id . ";";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result[$row['id']]['id'] = $row['id'];
						$result[$row['id']]['global_id'] = $row['gal_id'];
						$result[$row['id']]['url_desc'] = $row['url_desc'];
						$result[$row['id']]['time_added'] = $row['time_added'];
					}
				}
			}
		}

		return $result;
	}

	function getIdByName($name)
	{
		$result = false;
		$name = preg_replace('/[^a-z0-9-.]/', '', $name);
		if ($this->_db) {
			//$this->_db->debug = true;
			$rs = $this->_db->query('select site_id from `sites` where `site_name` = "' . $name . '"');
			$site = $rs->fetchAll(\PDO::FETCH_ASSOC);

			if (empty($site))
				return false;
			$site = $site[0];
			//pr($model);
			if (isset($site['site_id'])) $result = $site['site_id'];
		}
		return $result;
	}

	// смена модели по айди, достает все данные по модели $id из базы, иначе false
	function switchSite($id)
	{
		$site = $this->getSite($id);

		if (!is_null($site['only_export_site']) && $site['only_export_site'] === 1) {
			return false;
		}

		if (!is_array($site))
			return false;
		return $site;
	}

	// добавляется галлерея $id (init в таблицу из getModelGals)
	function excludeGallery($gal_id)
	{
		if ($this->id === false || (int)$gal_id == 0 || $this->_db === false)
			return false;

		//		$gals = $this->getModelGals();

		//		if (in_array($id, $gals)) return false;

		$sql = 'INSERT INTO `site_';
		$sql .= intval($this->id);
		$sql .= '_exclude_gals` (`gal_id`) ';
		$sql .= ' VALUES (';
		$sql .= intval($gal_id);
		$sql .= ');';

		if ($this->_db->query($sql) === false) {
			print 'error inserting: ' . $this->_db->errorInfo() . '<BR>';
		} else {
			$this->switchSite($this->id);
			return true;
		}
	}

	// используется вторая таблица, где хранится id модели, id галлереи где есть модель
	function getExcludedGals()
	{
		if ($this->id === false || $this->_db === false)
			return false;
		$rs = $this->_db->query('select * from `site_' . intval($this->id) . '_exclude_gals`');
		$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
		//pr($gallery_rs);
		$gallery = array();
		foreach ($gallery_rs as $value) {
			$gallery[] = $value['gallery_id'];
		}
		return $gallery;
	}


	function ifRelatedTableExist($type = 'pics')
	{
		if ($this->id === false)
			return false;
		if ($type !== 'pics') {
			$typeInsertion = 'Movies';
			$type = 'movies';
		}
		$rs = $this->_db->query("SHOW TABLES LIKE 'site_" . intval($this->id) . "_related_" . $type . "'");
		$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
		if ($gallery_rs) return true;
		else return false;
	}

	function getTagsGalleries($tag_id)
	{
		$result = false;
		$tag_id = intval($tag_id);
		if ($tag_id && $this->_db) {
			//$this->_db->debug = true;
			$sql = "select site_" . intval($this->id) . ".gal_id, site_" . intval($this->id) . ".id, 
							site_" . intval($this->id) . ".time_added, galleries.gal_type from site_" . intval($this->id) . "
					left join galleries_tags on galleries_tags.gal_id = site_" . intval($this->id) . ".gal_id
					left join galleries on site_" . intval($this->id) . ".gal_id = galleries.gal_id
					where galleries_tags.gal_tags = '" . $tag_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) $result = $rows;
			}
		}
		return $result;
	}


	function getSourcesGalleries_short($source_id)
	{
		$result = false;
		$source_id = intval($source_id);
		if ($source_id && $this->_db) {
			//$this->_db->debug = true;
			$sql = "select site_" . intval($this->id) . ".gal_id, site_" . intval($this->id) . ".id, 
							site_" . intval($this->id) . ".time_added, galleries.gal_type from site_" . intval($this->id) . "
					left join galleries on site_" . intval($this->id) . ".gal_id = galleries.gal_id
					where galleries.gal_paysite = '" . $source_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) $result = $rows;
			}
		}
		return $result;
	}



	function queryRelatedResync($galId = false)
	{
		$result = false;
		if ($this->id) {
			if ($galId) {
				$galId = intval($galId);
				$sql = "SELECT id, galleries.gal_type
					   FROM site_" . intval($this->id) . "
					   LEFT JOIN galleries ON site_" . intval($this->id) . ".gal_id = galleries.gal_id
					   WHERE id ='" . $galId . "'";
				$priority = 0;
			} else {
				$sql = "DELETE FROM related_rebuilding_query WHERE site_id = '" . $this->id . "';";
				if ($this->_db->query($sql)) echo $this->id . ":cleared<br>";
				$sql = "SELECT id, galleries.gal_type
					   FROM site_" . intval($this->id) . "
					   LEFT JOIN galleries ON site_" . intval($this->id) . ".gal_id = galleries.gal_id;";
				$priority = 1;
			}

			$ifTable = $this->ifRelatedTableExist();
			if ($ifTable) {
				//$this->_db->debug = true;
				$rs = $this->_db->query($sql);
				if ($rs) {
					$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
					if ($gallery_rs) {
						$result = true;
						foreach ($gallery_rs as $gallery) {
							if ($gallery['gal_type'] == 'Pics') $type = 'pics';
							else $type = 'movies';
							$sql = "INSERT INTO related_rebuilding_query (site_id, local_id, gal_type, priority)
									VALUES ('" . $this->id . "', '" . $gallery['id'] . "', '" . $type . "', '" . $priority . "')";
							if ($this->_db->query($sql)) $log = new Logger($this->id . ":" . $gallery['id'] . " добавлено в очередь перестройки релейтедов");
						}
					}
				}
				//$this->_db->debug = false;
			}
		}
		return $result;
	}

	function showRelatedQuery()
	{
		$sql = "SELECT id, local_id, site_id, gal_type
			   FROM related_rebuilding_query
			   ORDER BY id";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs) {
				foreach ($gallery_rs as $value) {
					echo $value['id'] . ":" . $value['local_id'] . ":" . $value['site_id'] . ":" . $value['gal_type'] . "<br>";
				}
			}
		}
	}

	public function getGlobalId($siteId, $id)
	{
		$result = false;
		$sql = "select gal_id from site_" . $siteId . " where id = '" . $id . "';";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs) {
				$result = $gallery_rs[0]['gal_id'];
			}
		}
		return $result;
	}

	function syncRelatedSite($type = 'pics', $force = false)
	{
		//$this->_db->debug = true;	
		$result = false;
		if ($this->_db === false) {
			$log = new Logger(__CLASS__ . "->" . __METHOD__ . ": нет коннекта к базе", true);
			return false;
		}

		if ($type == 'pics') {
			$typeInsertion = 'Pics';
		} else {
			$typeInsertion = 'Movies';
			$type = 'movies';
		}
		$sql = "SELECT id, local_id, site_id, gal_type
				   FROM related_rebuilding_query";
		if ($force) $sql .= " ORDER BY id DESC LIMIT 200";
		else $sql .= " ORDER BY id ASC LIMIT 35";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs) {
				$cGallery = new Galleries($this->_db);
				foreach ($gallery_rs as $value) {
					$id = $value['id'];
					$localId = $value['local_id'];
					$siteId = $value['site_id'];
					$cCache = new cache($siteId);
					if ($value['gal_type'] == 'Pics' || $value['gal_type'] == 'pics') $type = 'pics';
					else $type = 'movies';
					$this->_db->query("DELETE FROM related_rebuilding_query WHERE id = '" . $id . "'");
					//$log = new Logger ("Релейтеды для сайт #". $siteId. ", галеры #".$localId." собираются");
					//echo "Релейтеды для сайт #". $siteId. ", галеры #".$localId." собираются<br>";
					$related = $cGallery->getRelatedGalleries($siteId, $localId);
					//echo "Релейтеды для сайт #". $siteId. ", галеры #".$localId." собраны<br>";
					//$log = new Logger ("Релейтеды для сайт #". $siteId. ", галеры #".$localId." собраны");
					//echo "Рилейтеды для галлереи ".$localId.":<br>";
					//var_dump($related);
					if (is_array($related)) {
						$log = new Logger("Релейтеды для сайт #" . $siteId . ", галеры #" . $localId . " существуют, добавляются");
						$this->_db->query("DELETE FROM site_" . intval($siteId) . "_related_" . $type . " WHERE local_id = '" . $localId . "'");
						foreach ($related as $related_id => $related_weight) {
							if ($relatedInsert = $this->_db->query("INSERT INTO site_" . intval($siteId) . "_related_" . $type . " (local_id, related_id, relation_weight) VALUES ('" . $localId . "', '" . $related_id . "', '" . $related_weight . "')")) {
								//echo "Related ". $related_id. " added with weight ".$related_weight."<br>";
								//$log = new Logger ("Релейтед добавлен для #". $siteId. ", галеры #".$localId." вес:".$related_weight);
							} else {
								//echo "Error on related ". $related_id. " when try to add related to Site ".$siteId."<br>";
								$log = new Logger("Error on related " . $related_id . " when try to add related to Site " . $siteId, true);
							}
						}
						$logOk = new Logger("Релейтеды для сайт #" . $siteId . ", галеры #" . $localId . " собраны");
						$globalId = $this->getGlobalId($siteId, $localId);
						//var_dump($globalId);
						if ($globalId) {
							$cCache->reset('gallery', $globalId);
							$log = new Logger("Кэш сброшен для. Сайт #" . $siteId . ", галера #" . $localId . ", глобальный #" . $globalId, true);
						}
					} else {
						$log = new Logger("Ошибка добавление релейтедов. Сайт #" . $siteId . ", галера #" . $localId, true);
					}
					//			var_dump($value);
				}
			}
		}
		return true;
	}

	function getSiteGalleries($id)
	{
		$id = intval($id);
		$result = false;
		if ($id && $this->_db) {
			$this->switchSite($id);
			if ($this->localId) {
				$sql = "select id from site_" . $id . ";";
				$type = 'id';
			} else {
				$sql = "select gal_id from site_" . $id . ";";
				$type = 'gal_id';
			}
			$rs = $this->_db->query($sql);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery_rs) {
					foreach ($gallery_rs as $gallery) {
						$result[] = $gallery[$type];
					}
				}
			}
		}
		return $result;
	}

	function getModelInfo($id)
	{
		$id = intval($id);
		$output = array();

		if (!$id || $this->_db === false)
			return false;

		$rs = $this->_db->query('select * from `model` where `id_model` = "' . $id . '"');
		$model = $rs->fetchAll(\PDO::FETCH_ASSOC);

		if (empty($model))
			return false;

		$model = $model[0];
		//pr($model);

		$output['id'] = $model['id_model'];
		$output['name'] = $model['name'];
		$output['active'] = $model['active'];
		$output['sex'] = $model['sex'];
		$output['role'] = $model['role'];
		$output['hair'] = $model['hair'];
		$output['birth'] = $model['birth'];
		$output['body'] = $model['body_type'];
		$output['personal_site_id'] = $model['personal_site_id'];
		$output['height'] = $model['height'];
		$output['picture'] = $model['picture'];
		$output['size'] = $model['size'];
		$output['info'] = $model['info'];

		$output['eyes'] = $model['eyes'];
		$output['ethnic'] = $model['ethnic'];
		$output['cock_boobs'] = $model['cock_n_boobs_type'];
		$output['piercing'] = $model['piercing'];
		$output['tattoo'] = $model['tattoo'];
		$output['tattooDesc'] = $model['tattoo_description'];
		$output['country'] = $model['country'];

		if ($output['picture'] < 256000) {
			$folderId = (int)ceil($output['picture'] / 1000);
			$folder = "1/" . $folderId;
		} else {
			$mainFolder = (int)ceil($output['picture'] / 256000);
			$folderId = (int)ceil($output['picture'] / 1000);
			$folder = $mainFolder . "/" . $folderId;
		}
		$output['thumb'] = "/thumbs/p/240/" . $folder . "/" . $output['picture'] . ".jpg";

		return $output;
	}

	function getSourceInfo($id)
	{
		$id = intval($id);
		$output = array();

		if (!$id || $this->_db === false)
			return false;

		$rs = $this->_db->query('select * from `paysites` where `paysite_id` = "' . $id . '"');
		$source = $rs->fetchAll(\PDO::FETCH_ASSOC);

		if (empty($source))
			return false;

		$source = $source[0];
		//pr($model);

		$output['id'] = $source['paysite_id'];
		$output['name'] = $source['paysite_name'];

		return $output;
	}


	function getSourcesGalleries($sourceId, $type = 'pics', $page = 1, $limit = 48)
	{
		if ($this->id === false || $this->_db === false)
			return false;
		if ($type == 'gif' || $type == 'gifs') {
			$typeInsertion = 'gif';
			$type == 'gif';
		} elseif ($type !== 'pics') {
			$typeInsertion = 'Movies';
			$type = 'movies';
		}
		$modelId = intval($sourceId);
		$result = array();
		$limit = intval($limit);
		$page = intval($page) - 1;
		if ($page < 0 || $page == 0) $page = 0;
		else $page = $limit * $page;
		//var_dump($page);
		if ($sourceId) {
			$sql = "SELECT site_" . $this->id . ".id,galleries.gal_id, galleries.gal_title, galleries.gal_type, galleries.gal_content_count, galleries.hosted_flag, galleries.gal_paysite, 
					site_" . $this->id . ".url_desc, site_" . intval($this->id) . ".time_added
					FROM site_" . intval($this->id) . "
					LEFT JOIN galleries ON galleries.gal_id = site_" . intval($this->id) . ".gal_id
					LEFT JOIN paysites ON galleries.gal_paysite = paysites.paysite_id
					WHERE paysites.paysite_id = '" . $sourceId . "' order by site_" . intval($this->id) . ".time_added desc";
			if ($limit) $sql .= " limit " . $page . ", " . $limit . ";";
			//echo $sql;
			$rs = $this->_db->query($sql);
			//var_dump($rs);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				foreach ($gallery_rs as $value) {
					$gallery['global_id'] = $value['gal_id'];
					$gallery['local_id'] = $value['id'];
					$gallery['gal_title'] = $value['gal_title'];
					$gallery['gal_type'] = $value['gal_type'];
					$gallery['gal_content_count'] = $value['gal_content_count'];
					$gallery['hosted_flag'] = $value['hosted_flag'];
					$gallery['gal_paysite'] = $value['gal_paysite'];
					$gallery['thumb'] = $this->getGalleryImage($gallery['global_id']);
					$gallery['url_desc'] = $value['url_desc'];
					$gallery['added'] = $value['time_added'];
					$url = str_replace("#TYPE#", strtolower($gallery['gal_type']), $this->galleryUrl);
					$url = str_replace("#LOCALID#", $gallery['local_id'], $url);
					$url = str_replace("#ID#", $gallery['global_id'], $url);
					$url = str_replace("#GALNAME#", $gallery['url_desc'], $url);
					$gallery['url'] = $url;
					$galleries[] = $gallery;
				}
				if (isset($galleries) && is_array($galleries)) $result = $galleries;
			}
		}
		return $result;
	}

	function getRelatedGalleries($mainId, $type = 'pics', $page = 1)
	{
		if ($this->id === false || $this->_db === false)
			return false;
		if ($type == 'gif' || $type == 'gifs') {
			$typeInsertion = 'gif';
			$type == 'gif';
		} elseif ($type !== 'pics') {
			$typeInsertion = 'Movies';
			$type = 'movies';
		}
		$modelId = intval($mainId);
		$result = array();
		$limit = 18;
		$page = intval($page) - 1;
		if ($page < 0 || $page == 0) $page = 0;
		else $page = $limit * $page;
		//var_dump($page);
		if ($mainId) {
			$sql = "SELECT site_" . $this->id . "_related_" . $type . ".related_id, galleries.gal_id, galleries.gal_title, galleries.gal_type, galleries.gal_content_count, galleries.hosted_flag, galleries.gal_paysite, 
					site_" . $this->id . ".url_desc, site_" . intval($this->id) . ".time_added, paysites.paysite_name
					FROM site_" . intval($this->id) . "_related_" . $type . "
					LEFT JOIN site_" . $this->id . " ON site_" . $this->id . "_related_" . $type . ".related_id = site_" . $this->id . ".id
					LEFT JOIN galleries ON site_" . intval($this->id) . ".gal_id = galleries.gal_id
					LEFT JOIN paysites ON galleries.gal_paysite = paysites.paysite_id
					WHERE site_" . $this->id . "_related_" . $type . ".local_id = '" . $mainId . "' order by site_" . intval($this->id) . "_related_" . $type . ".relation_weight desc limit " . $page . ", " . $limit . ";";
			//echo $sql;
			$rs = $this->_db->query($sql);

			//var_dump($rs);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				foreach ($gallery_rs as $value) {
					//echo $value['gal_id']. "<br>";
					//var_dump($value);
					$gallery['global_id'] = $value['gal_id'];
					$gallery['local_id'] = $value['related_id'];
					$gallery['gal_title'] = $value['gal_title'];
					$gallery['gal_type'] = $value['gal_type'];
					$gallery['gal_content_count'] = $value['gal_content_count'];
					$gallery['hosted_flag'] = $value['hosted_flag'];
					$gallery['gal_paysite'] = $value['gal_paysite'];
					$gallery['thumb'] = $this->getGalleryImage($gallery['global_id']);
					$gallery['url_desc'] = $value['url_desc'];
					$gallery['source_name'] = $value['paysite_name'];
					$gallery['added'] = $value['time_added'];
					$galleries[$gallery['local_id']] = $gallery;
				}
				if (isset($galleries) && is_array($galleries)) $result = $galleries;
				//var_dump($galleries);
			}
		}
		return $result;
	}

	function getTagsListig($siteId = 17, $rules = false, $lang = 'english', $debug = false)
	{
		if (is_array($rules)) {
			if (isset($rules['sites'])) {
				$sites_counter = false;
				$exclude_counter = false;
				$tag_counter = false;
				if ($debug == true) $this->_db->debug = true;
				$sql = "select distinct `galleries_tags`.`gal_tags`, `tags`.`tag_name`, `tags`.`tag_id` from galleries_tags  LEFT JOIN tags ON galleries_tags.gal_tags = tags.tag_id where galleries_tags.gal_id IN ";
				foreach ($rules['sites'] as $site) {
					if ($sites_counter) $sql .= " OR  `galleries_tags`.`gal_id` in  ";
					$sql .= " (";
					$sql .= "(SELECT site_" . intval($site['id']) . ".gal_id FROM site_" . intval($site['id']);
					if (isset($site['tags']) && is_array($site['tags'])) {
						$sql .= " WHERE site_" . intval($site['id']) . ".gal_id IN (SELECT gal_id FROM galleries_tags WHERE ";
						foreach ($site['tags'] as $tag) {
							if ($tag_counter) $sql .= " AND ";
							$sql .= " gal_tags = '" . intval($tag) . "' ";
							$tag_counter = true;
						}
						$sql .= ")";
						$tag_counter = false;
					}
					$sql .= ")";
					if (isset($site['exclude_niches'])) {
						foreach ($site['exclude_niches'] as $exclude) {
							if (!$exclude_counter) {
								$sql .= " AND galleries_tags.gal_id NOT IN (SELECT site_" . intval($site['id']) . ".gal_id FROM site_" . intval($site['id']) . "
										 LEFT JOIN galleries_tags ON galleries_tags.gal_id = site_" . intval($site['id']) . ".gal_id WHERE ";
							} else $sql .= " AND ";
							$sql .= " galleries_tags.gal_tags = '" . intval($exclude) . "' ";
							$exclude_counter = true;
						}
						if ($exclude_counter) $sql .= ")";
					}
					$sql .= ")";
					$exclude_counter = false;
					$sites_counter = true;
				}
				$sql .= " order by `tags`.`tag_name` asc";
			}
			if ($debug == true) echo $sql;
		} else {
			$sql = "select distinct `tags`.`tag_name`, `tags`.`tag_id`
					from `tags`
					left join `galleries_tags` on `galleries_tags`.`gal_tags` = `tags`.`tag_id`
					where `galleries_tags`.`gal_id` in (select `gal_id` from `site_" . $siteId . "`) order by `tags`.`tag_name`";
			if ($debug == true) echo $sql;
		}
		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs) {
				foreach ($gallery_rs as $model) {
					if ($model['tag_id']) {
						$output['id'] = $model['tag_id'];
						$output['name'] = $model['tag_name'];
						$result[$output['id']] = $output;
					}
				}
				return $result;
			} else return false;
		} else return false;

		return false;
	}

	function getSiteTagsList($site_id, $niche = false, $type = false)
	{
		$result = false;
		$site_id = intval($site_id);
		$where_used = false;
		if ($niche && preg_match("#(gay|shemale|staright)#im", $niche)) $niche = ucfirst(strtolower($niche));
		else $nice = false;
		if ($type && preg_match("#(pics|movies)#im", $type)) $type = ucfirst(strtolower($type));
		elseif ($type == 'gifs' || $type == 'gifs') $type = 'gif';
		else $type = false;
		if ($site_id && $this->switchSite($site_id)) {
			if (!$nice && ! $type) {
				$sql = "select tags.tag_id, tags.tag_name, count(site_" . $site_id . ".gal_id) as tags_galleries_count
						from  site_" . $site_id . "
						left join galleries_tags on site_" . $site_id . ".gal_id = galleries_tags.gal_id
						left join tags on galleries_tags.gal_tags = tags.tag_id
						group by tags.tag_id
						order by tags.tag_name";
			} else {
				$sql = "select tags.tag_id, tags.tag_name, count(site_" . $site_id . ".gal_id) as tags_galleries_count
						from  site_" . $site_id . "
						left join galleries_tags on site_" . $site_id . ".gal_id = galleries_tags.gal_id
						left join tags on galleries_tags.gal_tags = tags.tag_id
						left join galleries on site_" . $site_id . ".gal_id = galleries.gal_id";
				if ($type) {
					$where_used = true;
					$sql .= " where galleries.gal_type = '" . $type . "' ";
				}
				if ($niche) {
					if (!$where_used) $sql .= " where ";
					else $sql .= " and ";
					$sql .= " galleries.gal_niche = '" . $niche . "' ";
				}
				$sql .= "group by tags.tag_id
						order by tags.tag_name";
			}
			// echo $sql ."<br>";
			// $this->_db->debug = true;

			$db = DB::get();

			if ($db) {

				if ($stmt = $db->prepare($sql)) {
					$tag_id =  false;
					$tag_name = false;
					$tags_galleries_count = false;
					$stmt->execute();
					$stmt->bind_result($tag_id, $tag_name, $tags_galleries_count);

					while ($stmt->fetch()) {
						if ($tag_id) {
							$output['id'] = $tag_id;
							$output['count'] = $tags_galleries_count;
							$output['name'] = $tag_name;
							$result[$output['id']] = $output;
						}
					}
				}
			}
		}
		return $result;
	}

	// главная функция
	function getSiteSourcesList($site_id, $niche = false, $type = false)
	{
		$result = false;
		$site_id = intval($site_id);
		$where_used = false;
		if ($niche && preg_match("#(gay|shemale|staright)#im", $niche)) $niche = ucfirst(strtolower($niche));
		else $nice = false;
		if ($type && preg_match("#(pics|movies)#im", $type)) $type = ucfirst(strtolower($type));
		elseif ($type == 'gifs' || $type == 'gifs') $type = 'gif';
		else $type = false;
		if ($site_id && $this->switchSite($site_id)) {
			if (!$nice && ! $type) {
				$sql = "select paysites.paysite_id, paysites.paysite_name, paysites.paysite_link, count(site_" . $site_id . ".gal_id) as sources_galleries_count
						from  site_" . $site_id . "
						left join galleries on site_" . $site_id . ".gal_id = galleries.gal_id
						left join paysites on paysites.paysite_id = galleries.gal_paysite
						group by paysites.paysite_id
						order by paysites.paysite_name";
			} else {
				$sql = "select paysites.paysite_id, paysites.paysite_name, paysites.paysite_link, count(site_" . $site_id . ".gal_id) as sources_galleries_count
						from  site_" . $site_id . "
						left join galleries on site_" . $site_id . ".gal_id = galleries.gal_id
						left join paysites on paysites.paysite_id = galleries.gal_paysite";
				if ($type) {
					$where_used = true;
					$sql .= " where galleries.gal_type = '" . $type . "' ";
				}
				if ($niche) {
					if (!$where_used) $sql .= " where ";
					else $sql .= " and ";
					$sql .= " galleries.gal_niche = '" . $niche . "' ";
				}
				$sql .= "group by paysites.paysite_id
						order by paysites.paysite_name";
			}
			// echo $sql ."<br>";
			//$this->_db->debug = true;

			$db = DB::get();

			if ($db) {

				if ($stmt = $db->prepare($sql)) {
					$paysite_id = false;
					$sources_galleries_count = false;
					$paysite_name = false;
					$paysite_link = false;
					$stmt->execute();
					$stmt->bind_result($paysite_id, $paysite_name, $paysite_link, $sources_galleries_count);

					while ($stmt->fetch()) {
						if ($paysite_id) {
							$output['id'] = $paysite_id;
							$output['count'] = $sources_galleries_count;
							$output['name'] = $paysite_name;
							$output['link'] = $paysite_link;
							$result[$output['id']] = $output;
						}
					}

					// var_dump($result);
				}
			}
		}
		return $result;
	}

	// новые таблицы
	function new_getSiteSources($site_id, $type = false)
	{
		$result = array();
		$site_id = (int)$site_id;
		$where_used = false;

		if ($type && !preg_match("#^(pics|movies|gifs)$#im", $type)) {
			$type = false;
		}


		if ($site_id > 0) {
			$db = DB::get();
			$sql = "SELECT name, folder_name, md5, gals_count, video_count, total_count, added_on
					FROM sites_sources";
			if ($type) {
				if ($type == 'movies') {
					$sql .= " WHERE video_count > 0";
				} elseif ($type == 'pics') {
					$sql .= " WHERE gals_count > 0";
				}
			} else {
				$sql .= " WHERE total_count > 0 AND added_on > 0";
			}
			$sql .= " AND site_id = " . $site_id . "
					 ORDER BY name ASC;";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$name 			= "";
					$folder_name 	= "";
					$md5 			= "";
					$gals_count 	= 0;
					$video_count 	= 0;
					$total_count 	= 0;
					$added_on 		= 0;

					$stmt->bind_result($name, $folder_name, $md5, $gals_count, $video_count, $total_count, $added_on);

					while ($stmt->fetch()) {
						$p_tmp['name'] = $name;
						$p_tmp['folder_name'] = $folder_name;
						$p_tmp['md5'] = $md5;
						$p_tmp['gals_count'] = $gals_count;
						$p_tmp['video_count'] = $video_count;
						$p_tmp['total_count'] = $total_count;
						$p_tmp['added_on'] = $added_on;
						$result[] = $p_tmp;
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT execute fail '" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT error '" . $db->error . "'", true);
			}
		}
		return $result;
	}



	function getGalleriesListig_debug($request_type, $listing_type, $rules = false, $galsPerPage = false, $page = 0)
	{
		$result = false;
		//var_dump($rules);
		if ($rules && isset($rules['sites'])) {
			$sql = "";
			$sites_counter = false;
			$exclude_counter = false;
			$tag_counter = false;
			foreach ($rules['sites'] as $site) {
				$sql = "(select ";
				if ($request_type == 'sources' && $listing_type == 'galleries' && $rules['source']) {
					$sql .= "`sites`.`local_id_flag`, `sites`.`sites_gallery_url`, `site_" . intval($site['id']) . "`.`id`, `site_" . intval($site['id']) . "`.`time_added`,`site_" . intval($site['id']) . "`.`url_desc`,`site_" . intval($site['id']) . "`.`pageviews`,`site_" . intval($site['id']) . "`.`likes`, `galleries`.`gal_id`, `galleries`.`gal_title`,
						  		`galleries`.`gal_type`, `galleries`.`gal_content_count`, `galleries`.`hosted_flag`,
								`galleries`.`gal_paysite`, `paysites`.`paysite_name`";
					$sql .= " from `sites`, `site_" . intval($site['id']) . "`
								left join `galleries` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id`
								left join `paysites` on `galleries`.`gal_paysite` = `paysites`.`paysite_id` ";
					$sql .= " where `sites`.`site_id` = '" . intval($site['id']) . "' and `paysites`.`paysite_id` = " . intval($rules['source']) . " AND `galleries`.`gal_id` in ";
				} elseif ($request_type == 'tags' && $listing_type == 'galleries') {
					$sql .= "`sites`.`local_id_flag`, `sites`.`sites_gallery_url`,`site_" . intval($site['id']) . "`.`id`, `site_" . intval($site['id']) . "`.`time_added`,`site_" . intval($site['id']) . "`.`url_desc`,`site_" . intval($site['id']) . "`.`pageviews`,`site_" . intval($site['id']) . "`.`likes`, `galleries`.`gal_id`, `galleries`.`gal_title`,
						  		`galleries`.`gal_type`, `galleries`.`gal_content_count`, `galleries`.`hosted_flag`,
								`galleries`.`gal_paysite`, `paysites`.`paysite_name`";
					$sql .=	" from `sites`, `site_" . intval($site['id']) . "`
								left join `galleries` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id`
								left join `paysites` on `galleries`.`gal_paysite` = `paysites`.`paysite_id` ";
					$sql .= " where `sites`.`site_id` = '" . intval($site['id']) . "' and `galleries`.`gal_id` in ";
				} elseif ($request_type == 'models' && $listing_type == 'galleries' && isset($rules['model'])) {
					$sql .= "`sites`.`local_id_flag`, `sites`.`sites_gallery_url`,`site_" . intval($site['id']) . "`.`id`, `site_" . intval($site['id']) . "`.`time_added`,`site_" . intval($site['id']) . "`.`url_desc`,`site_" . intval($site['id']) . "`.`pageviews`,`site_" . intval($site['id']) . "`.`likes`, `galleries`.`gal_id`, `galleries`.`gal_title`,
						  		`galleries`.`gal_type`, `galleries`.`gal_content_count`, `galleries`.`hosted_flag`,
								`galleries`.`gal_paysite`, `paysites`.`paysite_name`";
					$sql .=	" from `sites`, galleries_models
								left join `site_" . intval($site['id']) . "` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries_models`.`gallery_id`
								left join `galleries` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id`
								left join `paysites` on `galleries`.`gal_paysite` = `paysites`.`paysite_id` 
								left join `site_" . intval($site['id']) . "_models_pics` on `site_" . intval($site['id']) . "_models_pics`.`local_id` = `site_" . intval($site['id']) . "`.`id`
								left join `site_" . intval($site['id']) . "_models_movies` on `site_" . intval($site['id']) . "_models_movies`.`local_id` = `site_" . intval($site['id']) . "`.`id`";
					$sql .= " where `sites`.`site_id` = '" . intval($site['id']) . "' and `galleries_models`.`model_id` in ('" . $rules['model'] . "') and `galleries`.`gal_id` in ";
				}
				$sql .= " (select `site_" . intval($site['id']) . "`.`gal_id` from `site_" . intval($site['id']) . "` ";
				if (isset($site['tags']) && is_array($site['tags'])) {
					$sql .= " WHERE `site_" . intval($site['id']) . "`.`gal_id` IN (SELECT `gal_id` FROM `galleries_tags` WHERE ";
					foreach ($site['tags'] as $tag) {
						if ($tag_counter) $sql .= " AND ";
						$sql .= " `gal_tags` = '" . intval($tag) . "' ";
						$tag_counter = true;
					}
					$sql .= ")";
					$tag_counter = false;
				}
				$sql .= ")";
				if (isset($site['exclude_niches'])) {
					foreach ($site['exclude_niches'] as $exclude) {
						if (!$exclude_counter) {
							$sql .= " AND `galleries`.`gal_id` NOT IN (SELECT `site_" . intval($site['id']) . "`.`gal_id` FROM `site_" . intval($site['id']) . "` 
										 LEFT JOIN `galleries_tags` ON `galleries_tags`.`gal_id` = `site_" . intval($site['id']) . "`.`gal_id` WHERE ";
						} else $sql .= " AND ";
						$sql .= " `galleries_tags`.`gal_tags` = '" . intval($exclude) . "' ";
						$exclude_counter = true;
					}
					if ($exclude_counter) $sql .= ")";
				}
				$sql .= " order by `site_" . intval($site['id']) . "`.`time_added` DESC";
				$galsPerPage = abs(intval($galsPerPage));
				$page = abs(intval($page));
				if ($galsPerPage) {
					if ($page > 0) $page--;
					$page = $page * $galsPerPage;
					$sql .= " limit " . $page . ", " . $galsPerPage . " ";
				}
				$sql .= ")";
				$exclude_counter = false;
				$sites_counter = true;
				//$this->_db->debug = true;

				$rs = $this->_db->query($sql);
				if ($rs) {
					$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
					if ($gallery_rs) {
						//var_dump($gallery_rs);
						//$this->_db->debug = false;
						foreach ($gallery_rs as $value) {
							if ($value['gal_type']) {
								$gallery['global_id'] = $value['gal_id'];
								$gallery['local_id'] = $value['id'];
								$gallery['gal_title'] = $value['gal_title'];
								$gallery['gal_type'] = $value['gal_type'];
								$gallery['gal_content_count'] = $value['gal_content_count'];
								$gallery['hosted_flag'] = $value['hosted_flag'];
								$gallery['gal_paysite'] = $value['gal_paysite'];
								$gallery['thumb']['id'] = $this->getGalleryImage($gallery['global_id']);
								$gallery['url_desc'] = $value['url_desc'];
								$gallery['added'] = $value['time_added'];
								$gallery['site_id'] = intval($site['id']);
								$gallery['pageviews'] = $value['pageviews'];
								$gallery['likes'] = $value['likes'];
								$gallery['site_local_flag'] = $value['local_id_flag'];
								$url = str_replace("#TYPE#", strtolower($gallery['gal_type']),  $value['sites_gallery_url']);
								$url = str_replace("#LOCALID#", $gallery['local_id'], $url);
								$url = str_replace("#ID#", $gallery['global_id'], $url);
								$url = str_replace("#GALNAME#", $gallery['url_desc'], $url);
								$gallery['url'] = $url;
								if (isset($gallery['thumb']['id'])) {
									if ($gallery['thumb']['id'] < 256000) {
										$folderId = (int)ceil($gallery['thumb']['id'] / 1000);
										$folder = "1/" . $folderId;
									} else {
										$mainFolder = (int)ceil($gallery['thumb']['id'] / 256000);
										$folderId = (int)ceil($gallery['thumb']['id'] / 1000);
										$folder = $mainFolder . "/" . $folderId;
									}
									$gallery['thumb']['small'] = "/thumbs/p/150/" . $folder . "/" . $gallery['thumb']['id'] . ".jpg";
									$gallery['thumb']['medium'] = "/thumbs/p/180/" . $folder . "/" . $gallery['thumb']['id'] . ".jpg";
									$gallery['thumb']['big'] = "/thumbs/p/240/" . $folder . "/" . $gallery['thumb']['id'] . ".jpg";
								}
								$result[$gallery['global_id']] = $gallery;
							}
						}
					}
				}
			}
		}
		//$this->_db->debug = true;

		return $result;
	}

	function getGalleriesListig($request_type, $listing_type, $rules = false, $galsPerPage = false, $page = 0)
	{
		$result = false;
		//var_dump($rules);
		if ($rules && isset($rules['sites'])) {
			$sql = "";
			$sites_counter = false;
			$exclude_counter = false;
			$tag_counter = false;
			foreach ($rules['sites'] as $site) {
				$sql = "(select ";
				if ($request_type == 'sources' && $listing_type == 'galleries' && $rules['source']) {
					$sql .= "`sites`.`local_id_flag`,`site_" . intval($site['id']) . "`.`id`, `site_" . intval($site['id']) . "`.`time_added`,`site_" . intval($site['id']) . "`.`url_desc`, `site_" . intval($site['id']) . "`.`pageviews`,`site_" . intval($site['id']) . "`.`likes`, `galleries`.`gal_id`, `galleries`.`gal_title`,
						  		`galleries`.`gal_type`, `galleries`.`gal_content_count`, `galleries`.`hosted_flag`,
								`galleries`.`gal_paysite`, `paysites`.`paysite_name`";
					$sql .= " from `sites`, `site_" . intval($site['id']) . "`
								left join `galleries` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id`
								left join `paysites` on `galleries`.`gal_paysite` = `paysites`.`paysite_id` ";
					$sql .= " where `sites`.`site_id` = '" . intval($site['id']) . "' and `paysites`.`paysite_id` = " . intval($rules['source']) . " AND `galleries`.`gal_id` in ";
				} elseif ($request_type == 'tags' && $listing_type == 'galleries') {
					$sql .= "`sites`.`local_id_flag`,`site_" . intval($site['id']) . "`.`id`, `site_" . intval($site['id']) . "`.`time_added`,`site_" . intval($site['id']) . "`.`url_desc`, `site_" . intval($site['id']) . "`.`pageviews`,`site_" . intval($site['id']) . "`.`likes`, `galleries`.`gal_id`, `galleries`.`gal_title`,
						  		`galleries`.`gal_type`, `galleries`.`gal_content_count`, `galleries`.`hosted_flag`,
								`galleries`.`gal_paysite`, `paysites`.`paysite_name`";
					$sql .=	" from `sites`, `site_" . intval($site['id']) . "`
								left join `galleries` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id`
								left join `paysites` on `galleries`.`gal_paysite` = `paysites`.`paysite_id` ";
					$sql .= " where `sites`.`site_id` = '" . intval($site['id']) . "' and `galleries`.`gal_id` in ";
				} elseif ($request_type == 'models' && $listing_type == 'galleries' && isset($rules['model'])) {
					$sql .= "`sites`.`local_id_flag`,`site_" . intval($site['id']) . "`.`id`, `site_" . intval($site['id']) . "`.`time_added`,`site_" . intval($site['id']) . "`.`url_desc`, `site_" . intval($site['id']) . "`.`pageviews`,`site_" . intval($site['id']) . "`.`likes`, `galleries`.`gal_id`, `galleries`.`gal_title`,
						  		`galleries`.`gal_type`, `galleries`.`gal_content_count`, `galleries`.`hosted_flag`,
								`galleries`.`gal_paysite`, `paysites`.`paysite_name`";
					$sql .=	" from `sites`, galleries_models
								left join `site_" . intval($site['id']) . "` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries_models`.`gallery_id`
								left join `galleries` on `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id`
								left join `paysites` on `galleries`.`gal_paysite` = `paysites`.`paysite_id` 
								left join `site_" . intval($site['id']) . "_models_pics` on `site_" . intval($site['id']) . "_models_pics`.`local_id` = `site_" . intval($site['id']) . "`.`id`
								left join `site_" . intval($site['id']) . "_models_movies` on `site_" . intval($site['id']) . "_models_movies`.`local_id` = `site_" . intval($site['id']) . "`.`id`";
					$sql .= " where `sites`.`site_id` = '" . intval($site['id']) . "' and `galleries_models`.`model_id` in ('" . $rules['model'] . "') and `galleries`.`gal_id` in ";
				}
				$sql .= " (select `site_" . intval($site['id']) . "`.`gal_id` from `site_" . intval($site['id']) . "` ";
				if (isset($site['tags']) && is_array($site['tags'])) {
					$sql .= " WHERE `site_" . intval($site['id']) . "`.`gal_id` IN (SELECT `gal_id` FROM `galleries_tags` WHERE ";
					foreach ($site['tags'] as $tag) {
						if ($tag_counter) $sql .= " AND ";
						$sql .= " `gal_tags` = '" . intval($tag) . "' ";
						$tag_counter = true;
					}
					$sql .= ")";
					$tag_counter = false;
				}
				$sql .= ")";
				if (isset($site['exclude_niches'])) {
					foreach ($site['exclude_niches'] as $exclude) {
						if (!$exclude_counter) {
							$sql .= " AND `galleries`.`gal_id` NOT IN (SELECT `site_" . intval($site['id']) . "`.`gal_id` FROM `site_" . intval($site['id']) . "` 
										 LEFT JOIN `galleries_tags` ON `galleries_tags`.`gal_id` = `site_" . intval($site['id']) . "`.`gal_id` WHERE ";
						} else $sql .= " AND ";
						$sql .= " `galleries_tags`.`gal_tags` = '" . intval($exclude) . "' ";
						$exclude_counter = true;
					}
					if ($exclude_counter) $sql .= ")";
				}
				$sql .= " order by `site_" . intval($site['id']) . "`.`time_added` DESC)";
				$exclude_counter = false;
				$sites_counter = true;
				//$this->_db->debug = true;

				$rs = $this->_db->query($sql);
				if ($rs) {
					$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
					if ($gallery_rs) {
						//var_dump($gallery_rs);
						foreach ($gallery_rs as $value) {
							if ($value['gal_type']) {
								$gallery['global_id'] = $value['gal_id'];
								$gallery['local_id'] = $value['id'];
								$gallery['gal_title'] = $value['gal_title'];
								$gallery['gal_type'] = $value['gal_type'];
								$gallery['gal_content_count'] = $value['gal_content_count'];
								$gallery['hosted_flag'] = $value['hosted_flag'];
								$gallery['gal_paysite'] = $value['gal_paysite'];
								$gallery['thumb']['id'] = $this->getGalleryImage($gallery['global_id']);
								$gallery['url_desc'] = $value['url_desc'];
								$gallery['added'] = $value['time_added'];
								$gallery['site_id'] = intval($site['id']);
								$gallery['pageviews'] = $value['pageviews'];
								$gallery['likes'] = $value['likes'];
								$gallery['site_local_flag'] = $value['local_id_flag'];
								if (isset($gallery['thumb']['id'])) {
									if ($gallery['thumb']['id'] < 256000) {
										$folderId = (int)ceil($gallery['thumb']['id'] / 1000);
										$folder = "1/" . $folderId;
									} else {
										$mainFolder = (int)ceil($gallery['thumb']['id'] / 256000);
										$folderId = (int)ceil($gallery['thumb']['id'] / 1000);
										$folder = $mainFolder . "/" . $folderId;
									}
									$gallery['thumb']['small'] = "/thumbs/p/150/" . $folder . "/" . $gallery['thumb']['id'] . ".jpg";
									$gallery['thumb']['medium'] = "/thumbs/p/180/" . $folder . "/" . $gallery['thumb']['id'] . ".jpg";
									$gallery['thumb']['big'] = "/thumbs/p/240/" . $folder . "/" . $gallery['thumb']['id'] . ".jpg";
								}
								$result[$gallery['global_id']] = $gallery;
							}
						}
					}
				}
			}
		}
		//$this->_db->debug = true;

		return $result;
	}

	function getSourcesListig($siteId = 17, $rules = false, $content_type = false)
	{
		//if ($this->id) { 
		$where_set = false;
		if ($rules && isset($rules['sites'])) {
			$sql = "";
			$sites_counter = false;
			$exclude_counter = false;
			$tag_counter = false;
			//$this->_db->debug = true;
			//echo 122;
			/*
				$sql .= "select distinct `paysites`.`paysite_name`, `paysites`.`paysite_id`, `paysites`.`paysite_link`
						 from `paysites`
						 where `paysites`.`paysite_id` in ";
				$where_set = false;
				foreach ($rules['sites'] as $site) {
					if ($sites_counter) $sql .= " OR `paysites`.`paysite_id` in ";
					$sql .= " (
							SELECT `galleries`.`gal_paysite`
							FROM `site_".intval($site['id'])."`
							LEFT JOIN `galleries` on `site_".intval($site['id'])."`.`gal_id` = `galleries`.`gal_id` ";

					// добавляем лефт джоины вместо WHERE NOT IN / WHERE IN как в старом варианте
					if ((isset($site['tags']) && is_array($site['tags'])) || (isset($site['exclude_niches']) && is_array($site['exclude_niches']))) {
						$sql .= " LEFT JOIN `galleries_tags` ON `site_".intval($site['id'])."`.`gal_id` = `galleries_tags`.`gal_id` ";
					}
					
					if (isset($site['tags']) && is_array($site['tags'])) {
						$sql .= " WHERE ";
						$where_set = true;
						$tag_counter = false;
						foreach ($site['tags'] as $tag) {
							if ($tag_counter) $sql .= " AND ";
							$sql .= " `galleries_tags`.`gal_tags` = '".intval($tag)."' ";
							$tag_counter = true;
						}
						$tag_counter = false;
					}
					if (isset($site['exclude_niches'])) {
						if($where_set) $sql .= " AND ";
						else $sql .= " WHERE ";
						$where_set = true;
						$exclude_counter = false;
						foreach($site['exclude_niches'] as $exclude) {
							if ($exclude_counter) $sql .= " AND ";
							$sql .= " `galleries_tags`.`gal_tags` = '".intval($exclude)."' ";
							$exclude_counter = true;
						}
						$exclude_counter = false;
					}
					if ($content_type && preg_match("#^(pics|movies)$#im", $content_type)) {
						if ($where_set) $sql .= " AND ";
						else $sql .= " WHERE ";
						$where_set = true;
						if($content_type =='gif') $type_addition = $content_type;
						else $type_addition = ucfirst($content_type);
						$sql .= " `galleries`.`gal_type` = '".$type_addition."' ";
					}
					
					$sql .= "group by `galleries`.`gal_paysite`) ";
					$exclude_counter = false;
					$sites_counter = true;
				}
				$sql .= " ORDER BY `paysites`.`paysite_name`";
				*/
			$sql .= "select distinct `paysites`.`paysite_name`, `paysites`.`paysite_id`, `paysites`.`paysite_link`
						 from `paysites` 
						 INNER JOIN `galleries` ON `paysites`.`paysite_id` = `galleries`.`gal_paysite`";
			$where_set = false;
			foreach ($rules['sites'] as $site) {
				if ($sites_counter) $sql .= " OR `paysites`.`paysite_id` in ";
				$sql .= " INNER JOIN `site_" . intval($site['id']) . "` ON `galleries`.`gal_id` = `site_" . intval($site['id']) . "`.`gal_id`";

				// добавляем лефт джоины вместо WHERE NOT IN / WHERE IN как в старом варианте
				if ((isset($site['tags']) && is_array($site['tags'])) || (isset($site['exclude_niches']) && is_array($site['exclude_niches']))) {
					$sql .= " INNER JOIN `galleries_tags` ON `site_" . intval($site['id']) . "`.`gal_id` = `galleries_tags`.`gal_id` ";
				}

				if (isset($site['tags']) && is_array($site['tags'])) {
					$sql .= " WHERE ";
					$where_set = true;
					$tag_counter = false;
					foreach ($site['tags'] as $tag) {
						if ($tag_counter) $sql .= " AND ";
						$sql .= " `galleries_tags`.`gal_tags` = '" . intval($tag) . "' ";
						$tag_counter = true;
					}
					$tag_counter = false;
				}
				if (isset($site['exclude_niches'])) {
					if ($where_set) $sql .= " AND ";
					else $sql .= " WHERE ";
					$where_set = true;
					$exclude_counter = false;
					foreach ($site['exclude_niches'] as $exclude) {
						if ($exclude_counter) $sql .= " AND ";
						$sql .= " `galleries_tags`.`gal_tags` = '" . intval($exclude) . "' ";
						$exclude_counter = true;
					}
					$exclude_counter = false;
				}
				if ($content_type && preg_match("#^(pics|movies)$#im", $content_type)) {
					if ($where_set) $sql .= " AND ";
					else $sql .= " WHERE ";
					$where_set = true;
					if ($content_type == 'gif') $type_addition = $content_type;
					else $type_addition = ucfirst($content_type);
					$sql .= " `galleries`.`gal_type` = '" . $type_addition . "' ";
				}

				$sql .= "group by `paysites`.`paysite_id` ";
				$exclude_counter = false;
				$sites_counter = true;
			}
			$sql .= " ORDER BY `paysites`.`paysite_name`";
			//echo $sql;
		} else {
			/*
				$sql = "SELECT `paysites`.`paysite_name`, `paysites`.`paysite_id`, `paysites`.`paysite_link`
						FROM `paysites`
						WHERE `paysites`.`paysite_id` IN 
						(
							SELECT `galleries`.`gal_paysite` 
							FROM `site_".$siteId."`
							LEFT JOIN `galleries` on `site_".$siteId."`.`gal_id` = `galleries`.`gal_id`
							GROUP BY `galleries`.`gal_paysite`
						)
						ORDER BY `paysites`.`paysite_name`";
						*/


			$sql = "SELECT `paysites`.`paysite_name`, `paysites`.`paysite_id`, `paysites`.`paysite_link`
						FROM `paysites`
						INNER JOIN `galleries` ON `paysites`.`paysite_id` = `galleries`.`gal_paysite`
						INNER JOIN `site_" . $siteId . "` ON `galleries`.`gal_id` = `site_" . $siteId . "`.`gal_id`
						GROUP BY `paysites`.`paysite_id`
						ORDER BY `paysites`.`paysite_name`";
			//echo $sql;
		}
		//$this->_db->debug = true;
		$rs = $this->_db->query($sql);

		if ($rs) {
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs) {
				foreach ($gallery_rs as $model) {
					$output['id'] = $model['paysite_id'];
					$output['name'] = $model['paysite_name'];
					$output['link'] = $model['paysite_link'];
					$result[$output['id']] = $output;
				}
				return $result;
			} else return false;
		} else {
			$log = new Logger(__METHOD__ . ", запрос: \n" . $sql . "\n не выполнен. Ошибка: " . $this->_db->errorInfo(), true);
			return false;
		}
		//}
		return false;
	}

	// Функции для переноса во внешний файл работы с галерами

	function getGalleryImage($galId)
	{ // входящие ТОЛЬКО глобальные айди
		if ($this->_db === false)
			return false;
		$galId = intval($galId);
		if ($galId) {
			$rs = $this->_db->query("SELECT image_id FROM galleries_pix WHERE gal_id = '" . $galId . "' AND rss_flag = '1' LIMIT 1");
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs) {
				// var_dump($gallery_rs);
				return $gallery_rs[0]['image_id'];
			} else return false;
		}
	}

	function modelsListing($site_id, $hair, $body, $ethnic, $eyes, $with_count, $items_offset, $items_per_page, $letter)
	{
		//$with_count = true;
		return $this->getModelsList(false, $site_id, $hair, $body, $ethnic, $eyes, false, $with_count, $items_offset, $items_per_page, $letter);
	}

	function modelsListing_new($rules, $hair, $body, $ethnic, $eyes, $with_count, $items_offset, $items_per_page, $letter)
	{
		return $this->getModelsList(false, false, $hair, $body, $ethnic, $eyes, $rules, $with_count, $items_offset, $items_per_page, $letter);
	}

	function getModelsList($sex = false, $siteId = false, $hair = false, $body = false, $ethnic = false, $eyes = false, $rules = false, $with_count = true, $items_offset = false, $items_per_page = false, $letter = false, $content_type = false)
	{
		//$this->_db->debug = true;
		//var_dump($with_count);
		$result = false;
		if ($rules &&  isset($rules['sites'])) {
			$sql = "";
			$sites_counter = false;
			$exclude_counter = false;
			$tag_counter = false;
			$sql .= "select distinct ";
			$sql .=	" `model`.`id_model`, `model`.`name`, `model`.`picture`, `model`.`sex`, `model`.`role`,
							 `model`.`hair`, `model`.`birth`, `model`.`eyes`, `model`.`ethnic`, `model`.`body_type`, `model`.`cock_n_boobs_type`,
							 `model`.`piercing`, `model`.`piercing_where`, `model`.`tattoo`, `model`.`tattoo_description`, `model`.`country`,
							 `model`.`body`, `model`.`personal_site_id`, `model`.`height`, `model`.`size`, `model`.`info` ";
			$sql .= " from `galleries_models` ";
			$sql .= " left join `model` on `model`.`id_model` = `galleries_models`.`model_id` ";
			foreach ($rules['sites'] as $site) {
				$sql .= " left join `site_" . intval($site['id']) . "_models_pics` on `model`.`id_model` = `site_" . intval($site['id']) . "_models_pics`.`id_model` ";
				$sql .= " left join `site_" . intval($site['id']) . "_models_movies` on `model`.`id_model` = `site_" . intval($site['id']) . "_models_movies`.`id_model` ";
			}

			$sql .= " where ";
			$variable = false;
			if ($sex || $hair || $body || $ethnic || $eyes || $letter) {
				if ($sex && ($sex == 'male' || $sex == 'female')) {
					if ($variable) $sql .= " AND";
					$sql .= " sex = '" . $sex . "'";
					$variable = true;
				}
				if ($hair) {
					if ($variable) $sql .= " AND";
					$sql .= " hair = '" . $hair . "'";
					$variable = true;
				}
				if ($body) {
					if ($variable) $sql .= " AND";
					$sql .= " body_type = '" . $body . "'";
					$variable = true;
				}
				if ($ethnic) {
					if ($variable) $sql .= " AND";
					$sql .= " ethnic = '" . $ethnic . "'";
					$variable = true;
				}
				if ($eyes) {
					if ($variable) $sql .= " AND";
					$sql .= " eyes = '" . $eyes . "'";
					$variable = true;
				}
				if ($letter) {
					$letter = preg_replace("#[^a-z]#im", "", $letter);
					if (strlen($letter) > 1) $letter = $letter[0];
					if ($variable) $sql .= " AND";
					$sql .= " lower(`model`.`name`) like '" . strtolower($letter) . "%'";
					$variable = true;
				}
			}
			if ($variable) {
				$sql .= " and ";
				$variable = false;
			}
			if ($letter && strlen($letter) == 1 && preg_match("#[a-z]#im", $letter)) {
				$sql .= " lower(`model`.`name`) like '" . strtolower($letter) . "%'";
				$variable = true;
			}
			if ($variable) $sql .= " and ";
			$sql .= " `galleries_models`.`gallery_id` in ";
			$where_set = false;
			foreach ($rules['sites'] as $site) {
				if ($sites_counter) $sql .= " OR `galleries_models`.`gallery_id` in ";
				$sql .= " (
								select `site_" . intval($site['id']) . "`.`gal_id` from `site_" . intval($site['id']) . "` ";

				// добавляем лефт джоины вместо WHERE NOT IN / WHERE IN как в старом варианте
				if ((isset($site['tags']) && is_array($site['tags'])) || (isset($site['exclude_niches']) && is_array($site['exclude_niches']))) {
					$sql .= " LEFT JOIN `galleries_tags` ON `site_" . intval($site['id']) . "`.`gal_id` = `galleries_tags`.`gal_id` ";
				}
				if ($content_type && preg_match("#^(pics|movies)$#im", $content_type)) {
					$sql .= " LEFT JOIN `galleries` ON `site_" . intval($site['id']) . "`.`gal_id` = `galleries`.`gal_id` ";
				}
				if (isset($site['tags']) && is_array($site['tags'])) {
					$sql .= " WHERE ";
					$where_set = true;
					$tag_counter = false;
					foreach ($site['tags'] as $tag) {
						if ($tag_counter) $sql .= " AND ";
						$sql .= " `galleries_tags`.`gal_tags` = '" . intval($tag) . "' ";
						$tag_counter = true;
					}
					$tag_counter = false;
				}
				if (isset($site['exclude_niches'])) {
					if ($where_set) $sql .= " AND ";
					else $sql .= " WHERE ";
					$where_set = true;
					$exclude_counter = false;
					foreach ($site['exclude_niches'] as $exclude) {
						if ($exclude_counter) $sql .= " AND ";
						$sql .= " `galleries_tags`.`gal_tags` = '" . intval($exclude) . "' ";
						$exclude_counter = true;
					}
					$exclude_counter = false;
				}
				if ($content_type && preg_match("#^(pics|movies)$#im", $content_type)) {
					if ($where_set) $sql .= " AND ";
					else $sql .= " WHERE ";
					$where_set = true;
					if ($content_type == 'gif') $type_addition = $content_type;
					else $type_addition = ucfirst($content_type);
					$sql .= " `galleries`.`gal_type` = '" . $type_addition . "' ";
				}

				$sql .= ") ";
				$exclude_counter = false;
				$sites_counter = true;
			}
			$sql .= " order by `model`.`name`";
			if ($content_type) {
				// echo $sql;
				// $this->_db->debug = true;
			}
			//				echo $sql;
			$sqlCountPics = "SELECT COUNT(*) FROM site_" . intval($site['id']) . "_models_pics ";
			$sqlCountMovies = "SELECT COUNT(*) FROM site_" . intval($site['id']) . "_models_movies ";
		} else {
			$result = array();
			if ($siteId) $this->switchSite($siteId);
			if ($this->id === false || $this->_db === false)
				return false;
			$sql = "SELECT * FROM model";
			$variable = false;
			if ($sex || $hair || $body || $ethnic || $eyes || $letter) {
				$sql .= " WHERE";
				if ($sex && ($sex == 'male' || $sex == 'female')) {
					$sql .= " sex = '" . $sex . "'";
					$variable = true;
				}
				if ($hair) {
					if ($variable) $sql .= " AND";
					$sql .= " hair = '" . $hair . "'";
					$variable = true;
				}
				if ($body) {
					if ($variable) $sql .= " AND";
					$sql .= " body_type = '" . $body . "'";
					$variable = true;
				}
				if ($ethnic) {
					if ($variable) $sql .= " AND";
					$sql .= " ethnic = '" . $ethnic . "'";
					$variable = true;
				}
				if ($eyes) {
					if ($variable) $sql .= " AND";
					$sql .= " eyes = '" . $eyes . "'";
					$variable = true;
				}
				if ($letter) {
					$letter = preg_replace("#[^a-z]#im", "", $letter);
					if (strlen($letter) > 1) $letter = $letter[0];
					if ($variable) $sql .= " AND";
					$sql .= " lower(`model`.`name`) like '" . strtolower($letter) . "%'";
					$variable = true;
				}
			}
			if ($siteId) {
				if ($variable) $sql .= " and ";
				else $sql .= " where ";
				$sql .= " model.id_model in (select id_model from `site_" . intval($siteId) . "_models_pics`) OR model.id_model in (select id_model from `site_" . intval($siteId) . "_models_movies`) ";
			}

			$sql .= " ORDER BY `model`.`name` ASC";
		}


		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery_rs && is_array($gallery_rs) && count($gallery_rs) > 0) {
				foreach ($gallery_rs as $model) {
					$output['id'] = $model['id_model'];
					$output['name'] = $model['name'];
					if (isset($model['active'])) $output['active'] = $model['active'];
					if (isset($model['sex'])) $output['sex'] = $model['sex'];
					if (isset($model['role'])) $output['role'] = $model['role'];
					if (isset($model['hair'])) $output['hair'] = $model['hair'];
					if (isset($model['birth'])) $output['birth'] = $model['birth'];
					if (isset($model['body'])) $output['body'] = $model['body_type'];
					if (isset($model['personal_site_id'])) $output['personal_site_id'] = $model['personal_site_id'];
					if (isset($model['height'])) $output['height'] = $model['height'];
					if (isset($model['picture'])) $output['picture'] = $model['picture'];
					if (isset($model['size'])) $output['size'] = $model['size'];
					if (isset($model['info'])) $output['info'] = $model['info'];
					if (isset($model['eyes'])) $output['eyes'] = $model['eyes'];
					if (isset($model['ethnic'])) $output['ethnic'] = $model['ethnic'];
					if (isset($model['cock_n_boobs_type'])) $output['cock_boobs'] = $model['cock_n_boobs_type'];
					if (isset($model['piercing'])) $output['piercing'] = $model['piercing'];
					if (isset($model['tattoo'])) $output['tattoo'] = $model['tattoo'];
					if (isset($model['tattoo_description'])) $output['tattooDesc'] = $model['tattoo_description'];
					if (isset($model['country'])) $output['country'] = $model['country'];

					if (isset($output['picture'])) {
						if ($output['picture'] < 256000) {
							$folderId = (int)ceil($output['picture'] / 1000);
							$folder = "1/" . $folderId;
						} else {
							$mainFolder = (int)ceil($output['picture'] / 256000);
							$folderId = (int)ceil($output['picture'] / 1000);
							$folder = $mainFolder . "/" . $folderId;
						}
						$output['thumb']['small'] = "/thumbs/p/150/" . $folder . "/" . $output['picture'] . ".jpg";
						$output['thumb']['medium'] = "/thumbs/p/180/" . $folder . "/" . $output['picture'] . ".jpg";
						$output['thumb']['big'] = "/thumbs/p/240/" . $folder . "/" . $output['picture'] . ".jpg";
					}
					$modelMoviesGalleries = 0;
					$modelPicsGalleries = 0;
					if ($with_count && $siteId) {
						$sqlCountPics = "select count(id) from `site_" . intval($siteId) . "_models_pics`
			    						 where `id_model` = '" . $output['id'] . "'";
						$sqlCountMovies = "select count(id) from `site_" . intval($siteId) . "_models_movies`
			    						 where `id_model` = '" . $output['id'] . "'";
						$e_rs = $this->_db->query($sqlCountPics);
						if ($e_rs) {
							$model_rs = $e_rs->fetchAll(\PDO::FETCH_ASSOC);
							if ($model_rs) {
								//var_dump($model_rs);
								$modelPicsGalleries = $model_rs[0]['count(id)'];
							}
						}
						$e_rs = $this->_db->query($sqlCountMovies);
						if ($e_rs) {
							$model_rs = $e_rs->fetchAll(\PDO::FETCH_ASSOC);
							if ($model_rs) {
								//var_dump($model_rs);
								$modelMoviesGalleries = $model_rs[0]['count(id)'];
							}
						}
						$output['galleries']['movies'] = $modelMoviesGalleries;
						$output['galleries']['pics'] = $modelPicsGalleries;
					}
					$result[$output['id']] = $output;
				}
			}
		} else {
			$log = new Logger(__METHOD__ . ", запрос: \n" . $sql . "\n не выполнен. Ошибка: " . $this->_db->errorInfo(), true);
		}

		return $result;
	}

	// удалить если все ок
	function getModelsList_old($sex = false, $siteId = false, $hair = false, $body = false, $ethnic = false, $eyes = false, $rules = false, $with_count = true, $items_offset = false, $items_per_page = false)
	{
		if ($rules &&  isset($rules['sites'])) {
			$sql = "";
			$sites_counter = false;
			$exclude_counter = false;
			$tag_counter = false;
			foreach ($rules['sites'] as $site) {
				if ($sites_counter) $sql .= " UNION ";
				$sql .= "(select distinct ";
				$sql .=	" `model`.`id_model`, `model`.`name`, `model`.`picture`, `model`.`sex`, `model`.`role`,
							 `model`.`hair`, `model`.`birth`, `model`.`eyes`, `model`.`ethnic`, `model`.`body_type`, `model`.`cock_n_boobs_type`,
							 `model`.`piercing`, `model`.`piercing_where`, `model`.`tattoo`, `model`.`tattoo_description`, `model`.`country`,
							 `model`.`body`, `model`.`personal_site_id`, `model`.`height`, `model`.`size`, `model`.`info` ";
				$sql .= " from `galleries_models` ";
				$sql .= " left join `model` on `model`.`id_model` = `galleries_models`.`model_id` ";

				$sql .= " left join `site_" . intval($site['id']) . "_models_pics` on `model`.`id_model` = `site_" . intval($site['id']) . "_models_pics`.`id_model` ";
				$sql .= " left join `site_" . intval($site['id']) . "_models_movies` on `model`.`id_model` = `site_" . intval($site['id']) . "_models_movies`.`id_model` ";

				$sql .= " where `galleries_models`.`gallery_id` in ";
				$sql .= " (select `site_" . intval($site['id']) . "`.`gal_id` from `site_" . intval($site['id']) . "` ";
				if (isset($site['tags']) && is_array($site['tags'])) {
					$sql .= " WHERE site_" . intval($site['id']) . ".gal_id IN (SELECT gal_id FROM galleries_tags WHERE ";
					foreach ($site['tags'] as $tag) {
						if ($tag_counter) $sql .= " AND ";
						$sql .= " gal_tags = '" . intval($tag) . "' ";
						$tag_counter = true;
					}
					$sql .= ")";
					$tag_counter = false;
				}
				$sql .= ")";
				if (isset($site['exclude_niches'])) {
					foreach ($site['exclude_niches'] as $exclude) {
						if (!$exclude_counter) {
							$sql .= " AND `galleries_models`.`gallery_id` NOT IN (SELECT `site_" . intval($site['id']) . "`.`gal_id` FROM `site_" . intval($site['id']) . "`
										 LEFT JOIN `galleries_tags` ON `galleries_tags`.`gal_id` = `site_" . intval($site['id']) . "`.`gal_id` WHERE ";
						} else $sql .= " AND ";
						$sql .= " galleries_tags.gal_tags = '" . intval($exclude) . "' ";
						$exclude_counter = true;
					}
					if ($exclude_counter) $sql .= ")";
				}
				$sql .= " order by `model`.`name`)";
				$exclude_counter = false;
				$sites_counter = true;
			}
			//echo $sql;
			$sqlCountPics = "SELECT COUNT(*) FROM site_" . intval($site['id']) . "_models_pics ";
			$sqlCountMovies = "SELECT COUNT(*) FROM site_" . intval($site['id']) . "_models_movies ";
		} else {
			$result = array();
			if ($siteId) $this->switchSite($siteId);
			if ($this->id === false || $this->_db === false)
				return false;
			$sql = "SELECT * FROM model";
			$variable = false;
			if ($sex || $hair || $body || $ethnic || $eyes) {
				$sql .= " WHERE";
				if ($sex && ($sex == 'male' || $sex == 'female')) {
					$sql .= " sex = '" . $sex . "'";
					$variable = true;
				}
				if ($hair) {
					if ($variable) $sql .= " AND";
					$sql .= " hair = '" . $hair . "'";
					$variable = true;
				}
				if ($body) {
					if ($variable) $sql .= " AND";
					$sql .= " body_type = '" . $body . "'";
					$variable = true;
				}
				if ($ethnic) {
					if ($variable) $sql .= " AND";
					$sql .= " ethnic = '" . $ethnic . "'";
					$variable = true;
				}
				if ($eyes) {
					if ($variable) $sql .= " AND";
					$sql .= " eyes = '" . $eyes . "'";
					$variable = true;
				}
			}
			if ($siteId) {
				$sql .= " where model.id_model in (select id_model from `site_" . intval($siteId) . "_models_pics`) OR model.id_model in (select id_model from `site_" . intval($siteId) . "_models_movies`) ";
			}

			$sql .= " ORDER BY `model`.`name` ASC";
		}
		$log = new Logger($sql, true);

		$rs = $this->_db->query($sql);
		$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
		if ($gallery_rs && is_array($gallery_rs) && count($gallery_rs) > 0) {
			foreach ($gallery_rs as $model) {
				$output['id'] = $model['id_model'];
				$output['name'] = $model['name'];
				if (isset($model['active'])) $output['active'] = $model['active'];
				if (isset($model['sex'])) $output['sex'] = $model['sex'];
				if (isset($model['role'])) $output['role'] = $model['role'];
				if (isset($model['hair'])) $output['hair'] = $model['hair'];
				if (isset($model['birth'])) $output['birth'] = $model['birth'];
				if (isset($model['body'])) $output['body'] = $model['body_type'];
				if (isset($model['personal_site_id'])) $output['personal_site_id'] = $model['personal_site_id'];
				if (isset($model['height'])) $output['height'] = $model['height'];
				if (isset($model['picture'])) $output['picture'] = $model['picture'];
				if (isset($model['size'])) $output['size'] = $model['size'];
				if (isset($model['info'])) $output['info'] = $model['info'];
				if (isset($model['eyes'])) $output['eyes'] = $model['eyes'];
				if (isset($model['ethnic'])) $output['ethnic'] = $model['ethnic'];
				if (isset($model['cock_n_boobs_type'])) $output['cock_boobs'] = $model['cock_n_boobs_type'];
				if (isset($model['piercing'])) $output['piercing'] = $model['piercing'];
				if (isset($model['tattoo'])) $output['tattoo'] = $model['tattoo'];
				if (isset($model['tattoo_description'])) $output['tattooDesc'] = $model['tattoo_description'];
				if (isset($model['country'])) $output['country'] = $model['country'];

				if (isset($output['picture'])) {
					if ($output['picture'] < 256000) {
						$folderId = (int)ceil($output['picture'] / 1000);
						$folder = "1/" . $folderId;
					} else {
						$mainFolder = (int)ceil($output['picture'] / 256000);
						$folderId = (int)ceil($output['picture'] / 1000);
						$folder = $mainFolder . "/" . $folderId;
					}
					$output['thumb']['small'] = "/thumbs/p/150/" . $folder . "/" . $output['picture'] . ".jpg";
					$output['thumb']['medium'] = "/thumbs/p/180/" . $folder . "/" . $output['picture'] . ".jpg";
					$output['thumb']['big'] = "/thumbs/p/240/" . $folder . "/" . $output['picture'] . ".jpg";
				}


				$modelMoviesGalleries = 0;
				$modelPicsGalleries = 0;
				if ($siteId !== false && $with_count) {
					$siteId = intval($siteId);
					$sql = $sqlCountPics .  " WHERE id_model = '" . $model['id_model'] . "'";
					$e_rs = $this->_db->query($sql);
					if ($e_rs) {
						$model_rs = $e_rs->fetchAll(\PDO::FETCH_ASSOC);
						if ($model_rs) {
							//var_dump($model_rs);
							$modelPicsGalleries = $model_rs[0]['COUNT(*)'];
						}
					}
					$sql = $sqlCountMovies .  " WHERE id_model = '" . $model['id_model'] . "'";
					$e_rs = $this->_db->query($sql);
					if ($e_rs) {
						$model_rs = $e_rs->fetchAll(\PDO::FETCH_ASSOC);
						if ($model_rs) {
							$modelMoviesGalleries = $model_rs[0]['COUNT(*)'];
						}
					}
				}
				$output['galleries']['movies'] = $modelMoviesGalleries;
				$output['galleries']['pics'] = $modelPicsGalleries;
				$result[$output['id']] = $output;
			}
		}
		return $result;
	}

	function getLocalId($site_id, $gal_id)
	{
		$result = false;
		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;
		if ($site_id && $gal_id) {
			$sql = "SELECT id FROM site_" . $site_id . " WHERE gal_id = '" . $gal_id . "';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery_rs) {
					$result = $gallery_rs[0]['id'];
				}
			}
		}
		return $result;
	}

	// дубль в sitesGalleries
	public function galleryPostedTo($gal_id)
	{
		$result = array();
		$sql = "SELECT site_id FROM sites_galleries WHERE gal_id = '" . $gal_id . "'";
		// $this->_db->debug = true;
		$rs = $this->_db->query($sql);
		if ($rs) {
			$sites = $rs->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($sites as $site) {
				$result[] = $site['site_id'];
			}
		}
		return $result;
	}

	function getSiteGallery($galId)
	{
		if ($this->id === false || $this->_db === false)
			return false;
		$result = false;
		$galId = intval($galId);
		if ($galId) {
			$rs = $this->_db->query("SELECT site_" . $this->id . ".id,galleries.gal_id, galleries.gal_title, galleries.gal_type, galleries.gal_content_count, galleries.hosted_flag, galleries.gal_paysite, 
										site_" . $this->id . ".url_desc, site_" . intval($this->id) . ".time_added
										FROM site_" . $this->id . " 
										LEFT JOIN  galleries 
										ON site_" . $this->id . ".gal_id = galleries.gal_id
										WHERE site_" . $this->id . ".id = '" . $galId . "'");
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery_rs) {
					foreach ($gallery_rs as $value) {
						if ($value['gal_type']) {
							$gallery['global_id'] = $value['gal_id'];
							$gallery['local_id'] = $value['id'];
							$gallery['gal_title'] = $value['gal_title'];
							$gallery['gal_type'] = $value['gal_type'];
							$gallery['gal_content_count'] = $value['gal_content_count'];
							$gallery['hosted_flag'] = $value['hosted_flag'];
							$gallery['gal_paysite'] = $value['gal_paysite'];
							$gallery['thumb'] = $this->getGalleryImage($gallery['global_id']);
							$gallery['url_desc'] = $value['url_desc'];
							$gallery['added'] = $value['time_added'];
						}
					}
				}
			}
			return $result = $gallery;
		}
		return $result;
	}

	public function allOnlyExportSites(): array
	{
		try {
			$rs = $this->_db->prepare("SELECT site_id AS id, site_name AS name FROM sites WHERE only_export_site = 1 ORDER BY name ASC");
			$rs->execute();
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			return $rows;
		} catch (Exception $e) {
			var_dump($e->getMessage());
			new Logger(__METHOD__ . ": " . $e->getMessage(), true);
			return [];
		}
	}

	function galleriesCount(int $site_id)
	{

		if ($this->switchSite($site_id)) {
			$sql = "select count(id) from site_" . $site_id . ";";
			$rs = $this->_db->query($sql);
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($rows) {
				$result = intval($rows[0]['count(id)']);
			}
			return $result;
		}

		return 0;
	}

	function getUrlName($in)
	{
		$in = trim($in);
		$in = strtolower($in);
		$in = preg_replace("/\s{1,}/im", "-", $in);
		return $in;
	}

	public function GetAll()
	{
		$site = array();
		$sql = "SELECT * FROM sites ORDER by site_name";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($rows as $row) {
				$i = $row['site_id'];
				$site[$i]['id'] = $row['site_id'];
				$site[$i]['name'] = $row['site_name'];
				$site[$i]['niche'] = $row['site_niche'];
				$site[$i]['category'] = $row['site_main_category'];
				$site[$i]['tag'] = $row['site_categories'];
				$site[$i]['galleryUrl'] = $row['sites_gallery_url'];
				$site[$i]['excludeTag'] = $row['site_categories_exclude'];
				$site[$i]['thumbSize'] = $row['site_thumb_size'];
				$site[$i]['redis_server'] = $row['redis_server'];
				$site[$i]['last_update'] = $row['last_update'];
				$site[$i]['thumb_by_horiz_width'] = $row['thumb_by_horiz_width'];

				$additional_tags = $row['site_categories'];
				$site[$i]['tag_list'] = array();
				if ($additional_tags && $unserialized_tags = @unserialize($additional_tags)) {
					if (is_array($unserialized_tags)) {
						foreach ($unserialized_tags as $key => $value) {
							$key_x = $key + 1;
							$site[$i]['tag_list'][$key_x] = $value;
						}
					}
				}
			}
		}
		return $site;
	}

	//
	//	 не определено к какому классу определять
	//

	public function getGalleriesList($sort = false, $order = false, $count = false, $offset = false, $type = false, $paysite = false, $status = false, $search = false, $category = false, $searchBy = 'title', $niche = false, $model = false, $main_gal = false)
	{
		if ($this->id) {
			//var_dump($main_gal);
			$db = DB::get();

			if ($search === false && $model && $model = intval($model)) {
				$sql = "SELECT name FROM model WHERE id_model = " . $model . ";";

				$q_result = $db->query($sql);

				if ($row = $q_result->fetch_array()) $search = $row['name'];
				else $model = false;
			}

			$sql = "SELECT DISTINCT
						site_" . $this->id . ".id,
						site_" . $this->id . ".time_added,
						site_" . $this->id . ".url_desc, 
						site_" . $this->id . ".likes,
						site_" . $this->id . ".pageviews,
						site_" . $this->id . ".rating,
						site_" . $this->id . ".own_title,
						galleries.gal_id,
						galleries.gal_source,
						galleries.gal_title,
						galleries.gal_niche,
						galleries.gal_added,
						galleries.gal_type,
						galleries.gal_content_count,
						paysites.paysite_name,
						paysites.paysite_affiliate,
						galleries_pix.image_id,
						galleries_pix.image,
						galleries.gal_status";
			if ($type == 'Movies' || ($this->site_type == 'video' && $this->vcdn_type == 'static')) {
				$sql .= ", galleries_videos.cdn_synced";
				$video_sql = " LEFT JOIN galleries_videos ON site_" . $this->id . ".gal_id = galleries_videos.gal_id";
				$vcdn_used = true;
			} else {
				$video_sql = "";
				$vcdn_used = false;
			}
			$sql .=	" FROM site_" . $this->id . "
					INNER JOIN galleries ON
						site_" . $this->id . ".gal_id = galleries.gal_id
					LEFT JOIN paysites ON
						galleries.gal_paysite = paysites.paysite_id
					LEFT JOIN galleries_pix ON
						galleries.gal_id = galleries_pix.gal_id" . $video_sql;



			$countSql = "SELECT COUNT(site_" . $this->id . ".gal_id) FROM site_" . $this->id . "
						 LEFT JOIN galleries on site_" . $this->id . ".gal_id = galleries.gal_id ";

			$category = intval($category);

			if ($searchBy == 'url') $searchSQL = "source";
			elseif ($searchBy == 'titledesc') {
				$searchTitleDesc = true;
			} elseif ($searchBy == 'desc') {
				$searchSQL = "description";
			} else $searchSQL = "title";

			if (($type && preg_match('/^(Pics|Movies|gif)$/', $type)) || $main_gal || $category || $search || ($paysite && (int)$paysite) || preg_match('/^(Gay|Straight|Shemale)$/', $niche) || ($status && preg_match('/^(zip|zipupload|new|grabbed|thumbs|uploaded|tagged|toregrab|OK|trash|delete|error|fetching_fail|video_fail)$/', $status))) {
				$sql .= " WHERE ";
				$countSql .= " WHERE ";
				if ($main_gal) {
					if ($main_gal) {
						if (isset($typeFlag)) {
							$sql .= " AND ";
							$countSql .= " AND ";
						}
						$sql .= " galleries.main_gal = '" . $main_gal . "' AND gal_status NOT LIKE 'delete' ";
						$countSql .= " galleries.main_gal = '" . $main_gal . "'";
						$typeFlag = true;
					}
				}
				if ($search) {
					if (isset($searchTitleDesc) && $searchTitleDesc) {
						$sql .= " (LOWER(galleries.gal_title) LIKE '%" . strtolower($search) . "%' OR LOWER(galleries.gal_description) LIKE '%" . strtolower($search) . "%')";
						$countSql .= " (LOWER(galleries.gal_title) LIKE '%" . strtolower($search) . "%' OR LOWER(galleries.gal_description) LIKE '%" . strtolower($search) . "%')";
					} else {
						$sql .= " LOWER(galleries.gal_" . $searchSQL . ") LIKE '%" . strtolower($search) . "%'";
						$countSql .= " LOWER(galleries.gal_" . $searchSQL . ") LIKE '%" . strtolower($search) . "%'";
					}
					if ($model) {
						$sql .= " AND galleries.gal_id NOT IN (SELECT gallery_id FROM galleries_models WHERE model_id = " . $model . ") ";
					}
					$typeFlag = true;
				}
				if ($type && preg_match('/^(Pics|Movies|gif)$/', $type)) {
					if (isset($typeFlag)) {
						$sql .= " AND ";
						$countSql .= " AND ";
					}
					$sql .= " galleries.gal_type = '" . $type . "'";
					$countSql .= " galleries.gal_type = '" . $type . "'";
					$typeFlag = true;
				}
				if (($paysite && (int)$paysite)) {
					$paysite = (int)$paysite;
					if (isset($typeFlag)) {
						$sql .= " AND ";
						$countSql .= " AND ";
					}
					$sql .= " galleries.gal_paysite = '" . $paysite . "'";
					$countSql .= " galleries.gal_paysite = '" . $paysite . "'";
					$typeFlag = true;
				}
				if (($niche && preg_match('/^(Gay|Straight|Shemale)$/', $niche))) {
					if (isset($typeFlag)) {
						$sql .= " AND ";
						$countSql .= " AND ";
					}
					$sql .= " galleries.gal_niche = '" . $niche . "'";
					$countSql .= " galleries.gal_niche = '" . $niche . "'";
					$typeFlag = true;
				}
				if ($status) {
					if (isset($typeFlag)) {
						$sql .= " AND ";
						$countSql .= " AND ";
					}
					$sql .= " galleries.gal_status = '" . $status . "'";
					$countSql .= " galleries.gal_status = '" . $status . "'";
					$typeFlag = true;
				}
				if ($category) {
					if (isset($typeFlag)) {
						$sql .= " AND ";
						$countSql .= " AND ";
					}
					$sql .= " galleries.gal_id IN (SELECT gal_id FROM galleries_tags WHERE gal_tags = '" . $category . "')";
					$countSql .= " galleries.gal_id IN (SELECT gal_id FROM galleries_tags WHERE gal_tags = '" . $category . "')";
					$typeFlag = true;
				}
			}

			//echo $countSql;
			$sql .= " GROUP BY site_" . $this->id . ".id ";

			if ($sort && preg_match('/^(id|title|date|paysite|niche|pics|status|likes|pageviews|local_id|rating)$/', $sort)) {
				switch ($sort) {
					case 'id':
						$sql .= " ORDER BY galleries.gal_id";
						break;
					case 'title':
						$sql .= " ORDER BY galleries.gal_title";
						break;
					case 'date':
						$sql .= " ORDER BY galleries.gal_added";
						break;
					case 'paysite':
						if ($paysite == false) $sql .= " ORDER BY paysites.paysite_name";
						else $sql .= " ORDER BY galleries.gal_id";
						break;
					case 'niche':
						if ($niche == false) $sql .= " ORDER BY galleries.gal_niche";
						else $sql .= " ORDER BY galleries.gal_id";
						break;
					case 'pics':
						$sql .= " ORDER BY galleries.gal_content_count";
						break;
					case 'likes':
						$sql .= " ORDER BY site_" . $this->id . ".likes";
						break;
					case 'pageviews':
						$sql .= " ORDER BY site_" . $this->id . ".pageviews";
						break;
					case 'status':
						if ($status == false) $sql .= " ORDER BY galleries.gal_status";
						else $sql .= " ORDER BY galleries.gal_id";
						break;
					case 'local_id':
						$sql .= " ORDER BY site_" . $this->id . ".id";
						break;
					case 'rating':
						$sql .= " ORDER BY site_" . $this->id . ".rating";
						break;
				}
			} else $sql .= " ORDER BY site_" . $this->id . ".id";
			if ($order && preg_match('/^(asc|desc)$/', $order)) {
				if ($order == 'desc') $sql .= " DESC";
				else $sql .= " ASC";
			} else $sql .= " ASC";
			if ($offset) {
				$offset = (int)$offset;
				$sql .= " LIMIT " . $offset;
			} else $sql .= " LIMIT 0";
			if ($count && $count = (int)$count) $sql .= ", " . $count;
			else $sql .= ", 50";


			$urlRules = $this->getGalleryUrl();
			$digit_base_for_id = $this->getDigitalBaseForId();

			$db = DB::get();
			$q_result = $db->query($sql);
			if ($q_result) {
				while ($row = $q_result->fetch_array()) {
					$galleryId = $row['gal_id'];
					$gallery[$galleryId]['title'] = $row['own_title'] ? $row['own_title'] : $row['gal_title'];
					$gallery[$galleryId]['niche'] = $row['gal_niche'];
					$gallery[$galleryId]['local_id'] = $row['id'];
					$gallery[$galleryId]['type'] = $row['gal_type'];
					$gallery[$galleryId]['added'] = $row['time_added'];
					$gallery[$galleryId]['count'] = $row['gal_content_count'];
					$gallery[$galleryId]['paysite'] = $row['paysite_name'];
					$gallery[$galleryId]['affiliate'] = $row['paysite_affiliate'];
					$gallery[$galleryId]['image'] = $row['image_id'];
					$gallery[$galleryId]['orig_image'] = $row['image'];
					$gallery[$galleryId]['status'] = $row['gal_status'];
					$gallery[$galleryId]['likes'] = $row['likes'];
					$gallery[$galleryId]['pageviews'] = $row['pageviews'];
					$gallery[$galleryId]['rating'] = $row['rating'];
					$gallery[$galleryId]['cdn_synced'] = $vcdn_used ? $row['cdn_synced'] : false;

					if (isset($digit_base_for_id) && $digit_base_for_id && $digit_base_for_id != 10) {
						// var_dump($digit_base_for_id);
						$local_gal_id = base_convert($gallery[$galleryId]['local_id'], 10, $digit_base_for_id);
						$global_gal_id = base_convert($galleryId, 10, $digit_base_for_id);
					} else {
						$local_gal_id = $gallery[$galleryId]['local_id'];
						$global_gal_id = $galleryId;
					}

					$gallery[$galleryId]['url'] = str_replace("#TYPE#", strtolower($gallery[$galleryId]['type']), $urlRules);
					$gallery[$galleryId]['url'] = str_replace("#LOCALID#", $local_gal_id, $gallery[$galleryId]['url']);
					$gallery[$galleryId]['url'] = str_replace("#ID#", $global_gal_id, $gallery[$galleryId]['url']);
					$gallery[$galleryId]['url'] = str_replace("#GALNAME#", $row['url_desc'], $gallery[$galleryId]['url']);
					//				print_r($gallery);
					//var_dump($gallery[$galleryId]['url']);
				}
			}
			$q_result = $db->query($countSql);
			$row = $q_result->fetch_array();
			$this->galleryCounter = $row['COUNT(site_' . $this->id . '.gal_id)'];
			//var_dump($this->galleryCounter);
		}
		if (isset($gallery)) return $gallery;
		else return FALSE;
	}

	function siteUpdated()
	{
		$result = false;
		if ($this->id) {
			$db = DB::get();
			if ($db) {

				$sql = "update sites set last_update ='" . time() . "' where site_id = '" . $this->id . "'";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute error '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT error: '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connection", true);
			}
		}
		return $result;
	}

	function sitesGalleriesTableSync($site_id = false)
	{
		$result = false;
		$site_id = intval($site_id);
		if ($site_id && $this->switchSite($site_id)) {
			$sql = "SELECT galleries.gal_id FROM site_" . $site_id . "
					LEFT JOIN galleries ON galleries.gal_id = site_" . $site_id . ".gal_id
					WHERE galleries.gal_status = 'OK'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					// $this->_db->debug = true;
					foreach ($rows as $row) {
						if ($row['gal_id']) {
							$sql = 'INSERT INTO `sites_galleries` (gal_id, site_id)';
							$sql .= ' VALUES (';
							$sql .= intval($row['gal_id']);
							$sql .= ',';
							$sql .= intval($site_id);
							$sql .= ');';
							$insert_rs = $this->_db->query($sql);
							if (!$insert_rs) {
								$log = new Logger(__METHOD__ . ", Ошибка добавления галера в таблицу sites_galleries: ", true);
								echo __METHOD__ . ", Ошибка добавления галера в таблицу sites_galleries," . $row['gal_id'] . ", " . $site_id . "<br>";
							}
						} else $log = new Logger(__METHOD__ . ", Ошибочная галера в таблице site_" . $site_id, true);
					}
					$result = true;
				}
			}
		}
		return $result;
	}



	function getSiteToUpdateLikes()
	{
		$result = false;
		if ($this->_db) {
			$sql = "SELECT site_id FROM sites
					ORDER BY likes_updated_on ASC
					LIMIT 1";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if (isset($rows[0]['site_id'])) $result = $rows[0]['site_id'];
				else $log = new Logger(__METHOD__ . ", Ошибка выборки 'site_id': '" . $sql . "'", true);
			} else $log = new Logger(__METHOD__ . ", Ошибка выполнения SQL запроса: '" . $sql . "'", true);
		} else $log = new Logger(__METHOD__ . ", Нет коннекта к базе", true);
		return $result;
	}

	function setSiteLikesUpdated($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		if ($this->_db) {
			$time = time();
			$sql = "UPDATE sites SET likes_updated_on = '" . $time . "' WHERE site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) $result = true;
			else $log = new Logger(__METHOD__ . ", Ошибка выборки 'site_id': '" . $sql . "'", true);
		} else $log = new Logger(__METHOD__ . ", Нет коннекта к базе", true);
		return $result;
	}

	function getSiteToUpdatePageviews()
	{
		$result = false;
		if ($this->_db) {
			$sql = "SELECT site_id FROM sites
					ORDER BY pageviews_updated_on ASC
					LIMIT 1";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if (isset($rows[0]['site_id'])) $result = $rows[0]['site_id'];
				else $log = new Logger(__METHOD__ . ", Ошибка выборки 'site_id': '" . $sql . "'", true);
			} else $log = new Logger(__METHOD__ . ", Ошибка выполнения SQL запроса: '" . $sql . "'", true);
		} else $log = new Logger(__METHOD__ . ", Нет коннекта к базе", true);
		return $result;
	}

	function setSitePageviewsUpdated($site_id)
	{
		$result = false;
		$site_id = intval($site_id);
		if ($this->_db) {
			$time = time();
			$sql = "UPDATE sites SET pageviews_updated_on = '" . $time . "' WHERE site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) $result = true;
			else $log = new Logger(__METHOD__ . ", Ошибка выборки 'site_id': '" . $sql . "'", true);
		} else $log = new Logger(__METHOD__ . ", Нет коннекта к базе", true);
		return $result;
	}

	function deleteSite($site_id)
	{
		$site_id = intval($site_id);
		$this->switchSite($site_id);
		if ($site_id) {
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_exclude_gals`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_related_pics`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_related_movies`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_models_pics`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_models_movies`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_galleries_models`";
			$rs = $this->_db->query($sql);
			$sql = "DROP TABLE IF EXISTS `site_" . $site_id . "_galleries_tags`";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_galleries where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_cache_query where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_galleries_make_query where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_models where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_searches where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_sources where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_stats_mini where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_tags where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from galleries_changes_query where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites_tags where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
			$sql = "delete from sites where site_id = '" . $site_id . "'";
			$rs = $this->_db->query($sql);
		}
	}

	public function AddGalleryId($site_id, $gallery_id, $title, $urlLength = 70, $gallery_unique = 0, $main_thumb = 0)
	{
		$local_id = false;

		if (strlen($title) < 3) {
			return FALSE;
		} else {

			$site_info = $this->SiteInformation($site_id);

			$desc = nonEngTitleToLatin($title);
			// var_dump($title, $desc);
			$desc = strtolower($desc);
			$desc = trim(substr($desc, 0, $urlLength));
			$desc = preg_replace("/[^a-z0-9\s]/", "", $desc);
			$desc = preg_replace("/\s+/", " ", $desc);
			$desc = str_replace(" ", "-", $desc);
			$time = time();

			$db = DB::get();

			$sql = "INSERT INTO site_" . $site_id . " 
					(gal_id, url_desc, time_added, own_title, own_main_thumb) 
					VALUE (?, ?, ?, ?, ?)";

			if ($stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("isisi", $gallery_id, $desc, $time, $title, $main_thumb)) {
					if ($stmt->execute()) {
						$local_id = $stmt->insert_id;
					}
				}
				$stmt->close();
			}

			if ($local_id) {

				$db->autocommit(FALSE);
				$sql = 'UPDATE galleries SET times_used_on_sites = times_used_on_sites + 1
						WHERE gal_id = ?;';
				if ($stmt = $db->prepare($sql)) {
					if ($stmt->bind_param("i", $gallery_id)) $stmt->execute();
					$stmt->close();
				}
				$sql = "INSERT INTO `sites_galleries` (gal_id, site_id, time_added, random_number)
						VALUES (?, ?, ?, ?);";
				if ($stmt = $db->prepare($sql)) {
					$random_number = mt_rand(1, 100);
					if ($stmt->bind_param("iiii", $gallery_id, $site_id, $time, $random_number)) $stmt->execute();
					$stmt->close();
				}
				if (intval($gallery_unique)) {
					$sql = 'UPDATE galleries SET unique_gal = ?
							WHERE gal_id = ?;';
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param("ii", $gallery_unique, $gallery_id)) $stmt->execute();
						$stmt->close();
					}
				}
				$db->commit();
			}

			return $local_id;
		}
	}

	public function SiteInformation($site_id)
	{
		$db = DB::get();

		$sql = "SELECT site_id, site_name, site_niche, site_main_category, or_tag, site_categories, 
					   sites_gallery_url, site_categories_exclude, sites_url_length, local_id_flag,
					   hand_flag, redis_server, accept_gifs, site_type, site_own_titles, site_own_main_thumbs,
					   thumb_by_horiz_width, max_times_used_gals, additional_redis_server, vcdn_type
				FROM sites WHERE site_id = ?";
		if ($stmt = $db->prepare($sql)) {
			if ($stmt->bind_param("i", $site_id)) {
				$stmt->execute();

				$site 				= array();
				$additional_tags 	= "";

				$stmt->bind_result($site['id'], $site['name'], $site['niche'], $site['category'], $site['or_tag'], $additional_tags, $site['galleryUrl'], $site['excludeTag'], $site['urlLength'], $site['localIdFlag'], $site['hand_flag'], $site['redis_server'], $site['accept_gifs'], $site['site_type'], $site['site_own_titles'], $site['site_own_main_thumbs'], $site['thumb_by_horiz_width'], $site['max_times_used_gals'], $site['additional_redis_server'], $site['vcdn_type']);
				if ($stmt->fetch()) {
					$site['tag_1'] = 0;
					$site['tag_2'] = 0;

					if ($additional_tags && $unserialized_tags = @unserialize($additional_tags)) {
						if (is_array($unserialized_tags)) {
							foreach ($unserialized_tags as $key => $value) {
								$key_x = $key + 1;
								$site['tag_' . $key_x] = $value;
							}
						}
					}
					$stmt->close();
					return $site;
				}
			}
			$stmt->close();
		}

		return FALSE;
	}






	public function returnNotUsedTagsFromList($site_id, $tags_array)
	{
		$result = false;

		if ($tags_array && is_array($tags_array) && count($tags_array)) {
			$use_tags_array = false;
			foreach ($tags_array as $tag_id) {
				$tag_id = (int)$tag_id;
				if ($tag_id > 0) {
					$use_tags_array[] = $tag_id;
				}
			}

			if ($use_tags_array) {

				$use_tags = implode(",", $use_tags_array);

				$sql = "SELECT tag_id
						FROM sites_tags
						WHERE site_id = ?
						AND tag_id IN (" . $use_tags . ")";


				$db = DB::get();

				if ($db) {
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param("i", $site_id)) {
							$stmt->execute();
							$stmt->bind_result($tag_id);
							$result = $use_tags_array;
							while ($stmt->fetch()) {
								$array_key = array_search($tag_id, $result);
								if ($array_key) unset($result[$array_key]);
							}
						}
						$stmt->close();
					}
				} else {
					$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
				}
			}
		}
		return $result;
	}


	public function returnNotUsedModelsFromList($site_id, $models_array)
	{
		$result = false;

		if ($models_array && is_array($models_array) && count($models_array)) {
			$use_models_array = false;
			foreach ($models_array as $model_id) {
				$model_id = (int)$model_id;
				if ($model_id > 0) {
					$use_models_array[] = $model_id;
				}
			}

			if ($use_models_array) {

				$use_models = implode(",", $use_models_array);

				$sql = "SELECT model_id
						FROM sites_models
						WHERE site_id = ?
						AND model_id IN (" . $use_models . ")";


				$db = DB::get();

				if ($db) {
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param("i", $site_id)) {
							$stmt->execute();
							$stmt->bind_result($model_id);
							$result = $use_models_array;
							while ($stmt->fetch()) {
								$array_key = array_search($model_id, $result);
								if ($array_key) unset($result[$array_key]);
							}
						}
						$stmt->close();
					}
				} else {
					$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
				}
			}
		}
		return $result;
	}


	// инициализация сайтов, по сути и апдейт при добавлении новых тегов
	// added_on и updated_on в ноль, чтобы знать когда был добавлен тег (т.е. первая галера с этим тегом)
	public function addAllTagsToSite($site_id)
	{ // дубль в sites galleries
		$result = false;
		$site_id = (int)$site_id;

		if ($site_id > 0) {

			$this->switchSite($site_id);

			// var_dump($site_id, $this->id);

			$niche = "%" . $this->niche . "%";

			$description = '';
			$keywords = '';
			$gals_count = 0;
			$video_count = 0;
			$total_count = 0;
			$pageviews = 0;
			$likes = 0;
			$added_on = 0;
			$updated_on = 0;
			$default_title_for_tag = $this->default_title_for_tag;

			if ($this->id) {
				$sql = "INSERT INTO sites_tags 
						(tag_id, site_id, name, folder_name, title, description, keywords, 
						md5, gals_count, video_count, total_count, pageviews, 
						likes, added_on, updated_on)

						SELECT tags.tag_id, ? , tags.tag_name, replace(replace(tags.tag_name, \"'\", ''), ' ', '-'), replace('" . $default_title_for_tag . "', \"#TAG_NAME#\", tags.tag_name) 
								,?, ?, MD5(replace(replace(tags.tag_name, \"'\", ''), ' ', '-')), ?, ?, ?, ?, ?, ?, ?
						FROM tags 
						WHERE tag_niche LIKE ?
						AND tags.tag_id NOT IN (
										SELECT sites_tags.tag_id FROM sites_tags
										WHERE sites_tags.site_id = ?
										)";

				$db = DB::get();
				// var_dump($sql);
				if ($db) {
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param(
							"issiiiiiiisi",
							$site_id,
							$description,
							$keywords,
							$gals_count,
							$video_count,
							$total_count,
							$pageviews,
							$likes,
							$added_on,
							$updated_on,
							$niche,
							$site_id
						)) {
							$stmt->execute();

							$result = true;
						}
						$stmt->close();
					} else {
						var_dump($db);
						$log = new Logger(__METHOD__ . ": STMT error:" . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
				}
				// var_dump($db->error, $stmt->error);
			}
		}
		return $result;
	}


	public function addAllModelsToSite($site_id)
	{ // дубль в sitesmodels
		$result = false;
		$site_id = (int)$site_id;

		if ($site_id > 0) {

			$this->switchSite($site_id);

			$model_sex = 'female';

			$niche = $this->niche;
			if ($niche == 'Gay' || $niche == 'gay') $model_sex = 'male';
			elseif ($niche == 'Straight' || $niche == 'straight') $model_sex = 'female';
			elseif ($niche == 'Shemale' || $niche == 'shemale') $model_sex = 'shemale';

			$gals_count = 0;
			$video_count = 0;
			$total_count = 0;
			$pageviews = 0;
			$likes = 0;
			$added_on = 0;
			$updated_on = 0;

			if ($this->id) {
				$sql = "INSERT INTO sites_models 
						(	model_id, site_id, name, md5, 
							gals_count, video_count, total_count, pageviews, 
							likes, added_on, updated_on, category_of_age
						)

						SELECT model.id_model, ? , model.name, MD5(replace(replace(model.name, \"'\", ''), ' ', '-')), 
							   ?, ?, ?, ?, ?, ?, ?, model.category_of_age
						FROM model
						WHERE sex = ? 
						AND model.id_model NOT IN (
													SELECT sites_models.model_id FROM sites_models
													WHERE sites_models.site_id = ?
												)";

				$db = DB::get();

				if ($db) {
					if ($stmt = $db->prepare($sql)) {
						$binded = $stmt->bind_param(
							"iiiiiiiisi",
							$site_id,
							$gals_count,
							$video_count,
							$total_count,
							$pageviews,
							$likes,
							$added_on,
							$updated_on,
							$model_sex,
							$site_id
						);
						if ($binded) {
							$stmt->execute();

							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": Проблема со STMT bind_param: '" . $stmt->error . "'", true);
						}
						// var_dump($stmt->error);
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__ . ": Проблема со STMT: '" . $db->error . "'", true);
					}
				} else {

					$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
				}
			}
		}
		return $result;
	}


	public function addAllSourcesToSite($site_id)
	{
		$result = false;
		$site_id = (int)$site_id;

		if ($site_id > 0) {

			$this->switchSite($site_id);

			$description = '';
			$keywords = '';
			$niche = $this->niche;
			$gals_count = 0;
			$video_count = 0;
			$total_count = 0;
			$pageviews = 0;
			$likes = 0;
			$added_on = 0;
			$updated_on = 0;

			if ($this->id) {
				$sql = "INSERT INTO sites_sources
						(	source_id, site_id, name, 
							md5, 
							folder_name, 
							description, keywords, gals_count, video_count, total_count, pageviews, 
							likes, added_on, updated_on
						)

						SELECT paysites.paysite_id, ? , paysites.paysite_name, 
							   MD5(replace(replace(LOWER(paysites.paysite_name), \"'\", ''), ' ', '-')), 
							   replace(replace(LOWER(paysites.paysite_name), \"'\", ''), ' ', '-'), 
							   ?, ?, ?, ?, ?, ?, ?, ?, ?
						FROM paysites
						WHERE paysite_niche = ? 
						AND paysites.paysite_id NOT IN (
													SELECT sites_sources.source_id FROM sites_sources
													WHERE sites_sources.site_id = ?
												)";

				$db = DB::get();

				if ($db) {
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param(
							"issiiiiiiisi",
							$site_id,
							$description,
							$keywords,
							$gals_count,
							$video_count,
							$total_count,
							$pageviews,
							$likes,
							$added_on,
							$updated_on,
							$niche,
							$site_id
						)) {
							$stmt->execute();

							$result = true;
						}
						// var_dump($stmt->error);
						$stmt->close();
					}
					// var_dump($db->error);		
				} else {

					$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
				}
			}
		}
		return $result;
	}



	private function createExcludeGalsTable($site_id, &$db = NULL)
	{

		$site_id = (int)$site_id;
		$result = false;
		$db = DB::get();

		if ($db) {
			if ($site_id > 0) {
				$sql = "CREATE TABLE IF NOT EXISTS `site_" . $site_id . "_exclude_gals` (
	  					`id` int(11) NOT NULL AUTO_INCREMENT,
	  					`gal_id` int(10) UNSIGNED NOT NULL,
	  					PRIMARY KEY (`id`),
	  					UNIQUE KEY `gal_id` (`gal_id`)
						) AUTO_INCREMENT=1 ;";
				$result = $db->query($sql);
				// echo "Hand Flag Set to Yes!<br>";
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}
		return $result;
	}


	private function createTagsModelsGalsTables($site_id, &$db = NULL)
	{
		$site_id = (int)$site_id;
		$result = false;
		if ($db == NULL) {
			$db = DB::get();
		}

		if ($db) {
			if ($site_id > 0) {
				$sql = "CREATE TABLE IF NOT EXISTS `site_" . $site_id . "_galleries_models` (
					  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					  `gal_id` int(10) unsigned NOT NULL,
					  `local_id` int(10) unsigned NOT NULL,
					  `model_id` int(10) unsigned NOT NULL,
					  `gal_type` enum('pics','movies','gif','none') NOT NULL DEFAULT 'none',
					  `added_on` int(10) unsigned NOT NULL,
					  PRIMARY KEY (`id`),
					  UNIQUE KEY `local_model` (`local_id`,`model_id`),
					  KEY `local_id` (`local_id`),
					  KEY `gal_type` (`gal_type`),
					  KEY `model_id` (`model_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

				$galleries_models_ok = $db->query($sql);

				// var_dump($db->error);

				if ($galleries_models_ok) {
					$sql = "CREATE TABLE IF NOT EXISTS `site_" . $site_id . "_galleries_tags` (
							  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
							  `gal_id` int(10) unsigned NOT NULL,
							  `local_id` int(10) unsigned NOT NULL,
							  `tag_id` int(10) unsigned NOT NULL,
							  `gal_type` enum('pics','movies','gif','none') NOT NULL DEFAULT 'none',
							  `added_on` int(10) unsigned NOT NULL,
							  PRIMARY KEY (`id`),
							  UNIQUE KEY `local_tag` (`local_id`,`tag_id`),
							  KEY `local_id` (`local_id`),
							  KEY `gal_type` (`gal_type`),
							  KEY `tag_id` (`tag_id`)
							) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
					$result = $db->query($sql);
				}
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}
		return $result;
	}

	public function SiteAdd(array $site_info = [])
	{

		$name = false;
		$url = false;
		$niche = false;
		$urlLength = false;
		$local_id_flag = false;
		$category = false;
		$excludeCategory = false;
		$hand_flag = false;
		$redis_server = false;
		$and_tags = false;
		$or_tag = 0;
		$accept_gifs = 0;
		$site_type = 'mix';
		$site_own_titles = false;
		$site_own_main_thumbs = false;
		$language = false;
		$use_galleries_from = 0;
		$digit_base_for_id = 10;
		$use_embed = 0;
		$thumb_by_horiz_width = 0;
		$max_times_used_gals = -1;
		$additional_redis_server = -1;
		$vcdn_type = false;
		$use_unique_tags = false;
		$default_title_for_tag = false;

		$pageviews_updated_on = 0;
		$likes_updated_on = 0;
		$keywords = '';
		$only_export_site = false;

		extract($site_info);



		if ($only_export_site) {
			$sql = "INSERT INTO sites 
						(site_name, only_export_site, keywords, pageviews_updated_on, likes_updated_on)
						VALUE ('{$name}', 1, '', 0, 0)";


			try {
				$stmt = $this->_db->prepare($sql);
				$stmt->execute();
				return $this->_db->lastInsertId();
			} catch (Exception $e) {
				echo $e->getMessage();
				return false;
			}

			$res = $db->query($sql);
			var_dump($sql, $res);

			var_dump($db->query('SELECT * FROM sites WHERE only_export_site = 1'));

			var_dump($db->insert_id);
			if ($res && $db->insert_id) {


				return $db->insert_id;
			} else {
				return false;
			}
		}


		$result = false;


		$hand_flag = (int)$hand_flag;
		$urlLength = (int)$urlLength;
		$category = intval($category);
		$or_tag = intval($or_tag);
		$accept_gifs = intval($accept_gifs);
		$max_times_used_gals = (int)$max_times_used_gals;
		$use_embed = ($use_embed) ? 1 : 0;
		$local_id_flag = ($local_id_flag) ? 1 : 0;

		$use_unique_tags = ($use_unique_tags) ? 1 : 0;

		if (!preg_match("#(video|pics|gif|mix)#", $site_type)) {
			echo "не могу добавить сайт - тип сайтан указан неверно (video|pics|gif|mix)";
			return false;
		}

		if ($category == 0 && $or_tag != 0) {
			$or_tag = $category;
			$category = 0;
		}

		if (!($thumb_by_horiz_width && ($thumb_by_horiz_width == 300 || $thumb_by_horiz_width == 600 || $thumb_by_horiz_width == 800))) {
			$thumb_by_horiz_width = 0;
		}

		$redis_server = intval($redis_server);

		if ($additional_redis_server != -1 && $additional_redis_server !== false && (int)$additional_redis_server !== false) {
			$additional_redis_server = (int)$additional_redis_server;
		} else {
			$additional_redis_server = -1;
		}

		if (is_array($and_tags) && count($and_tags)) {
			foreach ($and_tags as $tag) {
				if (intval($tag) && $excludeCategory != intval($tag)) $tmp_and_tags[] = intval($tag);
			}
			if (isset($tmp_and_tags) && is_array($tmp_and_tags)) {
				$and_tags = serialize($tmp_and_tags);
				if (strlen($and_tags) > 254) {
					$and_tags = "";
					$log = new Logger("Длинне AND тегов для сайта " . $name . " ,больше 254 символов - не помещается в поле", true);
				}
			} else {
				$and_tags = "";
			}
		} else {
			$and_tags = "";
		}
		if (preg_match('#[a-zA-Z0-9\.].*#im', $name) && $urlLength) {

			if (!preg_match('#gay|straight|shemale|all#im', $niche)) {
				$niche = 'All';
			}

			// $local_id_flag = 1;

			$site_own_titles = ($site_own_titles) ? 1 : 0;
			$site_own_main_thumbs = ($site_own_main_thumbs) ? 1 : 0;
			$language = ($language && preg_match("#^(en|nl|cz|de|be|es|ru|by|cn|jp|it|fr)$#im", $language)) ? strtolower($language) : 'en';

			if (!$vcdn_type || !preg_match("#^(dynamic|static)$#", $vcdn_type)) {
				$vcdn_type = 'dynamic';
			}

			if (!(int)$digit_base_for_id || $digit_base_for_id < 10 || $digit_base_for_id > 64) {
				$digit_base_for_id = 10;
			}



			if ($db) {
				$db->autocommit(false);
				$default_title_for_tag = $db->real_escape_string($default_title_for_tag);
				$all_query_ok = true;
				$sql = "INSERT INTO sites 
						(site_name, sites_gallery_url, site_niche, site_main_category,  upload_flag, local_id_flag, site_categories_exclude, sites_url_length, hand_flag, redis_server, site_categories, or_tag, accept_gifs, site_type, site_own_titles, site_own_main_thumbs, language, use_galleries_from, digit_base_for_id, use_embed, thumb_by_horiz_width, max_times_used_gals, additional_redis_server,vcdn_type, use_unique_tags, default_title_for_tag, pageviews_updated_on,  likes_updated_on, keywords)
						VALUE ('" . $name . "','" . $url . "','" . $niche . "','" . $category . "', 'cache','" . $local_id_flag . "','" . $excludeCategory . "','" . $urlLength . "','" . $hand_flag . "', '" . $redis_server . "', '" . $and_tags . "', '" . $or_tag . "', '" . $accept_gifs . "', '" . $site_type . "', '" . $site_own_titles . "', '" . $site_own_main_thumbs . "', '" . $language . "', '" . $use_galleries_from . "', '" . $digit_base_for_id . "', '" . $use_embed . "', '" . $thumb_by_horiz_width . "', '" . $max_times_used_gals . "', '" . $additional_redis_server . "', '" . $vcdn_type . "', '" . $use_unique_tags . "', '" . $default_title_for_tag . "', '" . $pageviews_updated_on . "', '" . $likes_updated_on . "', '" . $keywords . "' )";
				if ($db->query($sql) && $db->insert_id) {
					$site_id = $db->insert_id;

					$sql = "CREATE TABLE IF NOT EXISTS `site_" . $site_id . "` (
		  					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  					`gal_id` int(10) NOT NULL,
		  					`gal_type` ENUM( 'pics', 'movies', 'gif', 'none' ) NOT NULL DEFAULT 'none',
		  					`status` TINYINT UNSIGNED NOT NULL DEFAULT '0',		  						
							`gal_paysite` INT UNSIGNED NOT NULL DEFAULT '0',
		  					`url_desc` varchar(254) NOT NULL,
		  					`time_added` INT UNSIGNED NOT NULL,
		  					`pageviews` BIGINT NOT NULL DEFAULT '0',
		  					`likes` BIGINT NOT NULL DEFAULT '0', 
		  					`rating` FLOAT UNSIGNED NOT NULL DEFAULT '0', 
		  					`own_title` varchar(254) NOT NULL, 
		  					`own_main_thumb` INT UNSIGNED NOT NULL DEFAULT '0',
		  					PRIMARY KEY (`id`),
		  					UNIQUE KEY `gal_id` (`gal_id`),
		  					KEY `url_desc` (`url_desc`),
		  					KEY `time_added` ( `time_added` ),
		  					INDEX ( `pageviews` ),
		  					INDEX ( `likes` ),
		  					INDEX ( `rating` ),
		  					INDEX ( `gal_paysite` ),
							INDEX ( `gal_type` )
							) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;";
					$db->query($sql) ? null : $all_query_ok = false;
				}

				$this->createExcludeGalsTable($site_id, $db);
				$this->createTagsModelsGalsTables($site_id, $db);

				if ($all_query_ok) {
					$db->commit();
					$result = $site_id;
				} else {
					$db->rollback();
				}
			} else {
				var_dump($db);
				$db->rollback();
			}


			$db->autocommit(true);
		} else {
			$log = new Logger(__METHOD__ . ": Ошибка добавления, нет коннекта к БД", true);
		}

		return $result;
	}

	public function UpdateSite($site_info = array())
	{

		$site_id = false;
		$name = false;
		$url = false;
		$niche = false;
		$urlLength = false;
		$category = false;
		$exclude_category = false;
		$local_id_flag = false;
		$hand_flag = false;
		$redis_server = false;
		$and_tags = false;
		$or_tag = 0;
		$accept_gifs = 0;
		$site_type = 'mix';
		$site_own_titles = false;
		$site_own_main_thumbs = false;
		$language = false;
		$use_galleries_from = 0;
		$digit_base_for_id = 10;
		$use_embed = 0;
		$thumb_by_horiz_width = 0;
		$max_times_used_gals = -1;
		$additional_redis_server = -1;
		$vcdn_type = false;
		$use_unique_tags = false;
		$default_title_for_tag = false;

		$only_export_site = 0;

		extract($site_info);

		if (!preg_match("#(video|pics|gif|mix)#", $site_type)) {
			echo "не могу проапдейтить сайт - тип сайтан указан неверно (video|pics|gif|mix)";
			exit;
		}

		// var_dump($digit_base_for_id, (!(int)$digit_base_for_id || (int)$digit_base_for_id < 10 || (int)$digit_base_for_id > 64), !(int)$digit_base_for_id, (int)$digit_base_for_id < 10, (int)$digit_base_for_id > 64);
		$result = false;

		$site_id 					= (int)$site_id;
		$only_export_site			= $only_export_site ? 1 : 0;
		$redis_server 				= intval($redis_server);
		$category 					= intval($category);
		$or_tag 					= intval($or_tag);
		$accept_gifs 				= intval($accept_gifs);
		$max_times_used_gals 		= (int)$max_times_used_gals;
		$local_id_flag 				= (int)$local_id_flag ? 1 : 0;
		$use_unique_tags 			= ($use_unique_tags) ? 1 : 0;
		$niche 						= !preg_match('#^(Gay|Straight|Shemale|All)$#', $niche) ? 'All' : $niche;
		$exclude_category 			= intval($exclude_category);
		$additional_redis_server 	= ($additional_redis_server != -1 && $additional_redis_server !== false && (int)$additional_redis_server !== false) ? (int)$additional_redis_server : -1;
		$language 					= ($language && preg_match("#^(en|nl|cz|de|be|es|ru|by|cn|jp|it|fr)$#im", $language)) ? strtolower($language) : 'en';
		$thumb_by_horiz_width 		= (!($thumb_by_horiz_width && ($thumb_by_horiz_width == 300 || $thumb_by_horiz_width == 600 || $thumb_by_horiz_width == 800))) ? 0 : $thumb_by_horiz_width;
		$urlLength 					= ((int)$urlLength) ? ", sites_url_length = '" . (int)$urlLength . "' " : "";
		$digit_base_for_id 			= (!(int)$digit_base_for_id || $digit_base_for_id < 10 || $digit_base_for_id > 64) ? 10 : $digit_base_for_id;
		$use_embed 					= ($use_embed) ? 1 : 0;
		$vcdn_type 					= (!$vcdn_type || !preg_match("#^(dynamic|static)$#", $vcdn_type)) ? 'dynamic' : $vcdn_type;
		var_dump($digit_base_for_id);
		if ($category == 0 && $or_tag != 0) {
			$or_tag = $category;
			$category = 0;
		}

		if (is_array($and_tags) && $and_tags) {
			foreach ($and_tags as $tag) {
				if (intval($tag) && $exclude_category != intval($tag)) $tmp_and_tags[] = intval($tag);
			}
			if (isset($tmp_and_tags) && is_array($tmp_and_tags)) {
				$and_tags = serialize($tmp_and_tags);
				if (strlen($and_tags) > 254) {
					$and_tags = "";
					$log = new Logger("Длинне AND тегов для сайта " . $name . " ,больше 254 символов - не помещается в поле", true);
				}
			} else {
				$and_tags = "";
			}
		} else {
			$and_tags = "";
		}


		$db = DB::get();

		if ($db) {
			$all_query_ok = true;

			$default_title_for_tag = $db->real_escape_string($default_title_for_tag);

			$sql = "UPDATE sites
					SET site_name = '" . $name . "', sites_gallery_url ='" . $url . "', site_main_category='" . $category . "',
					upload_flag='cache',local_id_flag='" . $local_id_flag . "',
					hand_flag='" . $hand_flag . "', redis_server = '" . $redis_server . "', site_categories = '" . $and_tags . "',
					or_tag = '" . $or_tag . "', accept_gifs = '" . $accept_gifs . "', site_type = '" . $site_type . "',
					site_own_titles = '" . $site_own_titles . "', site_own_main_thumbs = '" . $site_own_main_thumbs . "',
					language = '" . $language . "', use_galleries_from = '" . $use_galleries_from . "',
					digit_base_for_id = '" . $digit_base_for_id . "' " . $urlLength . ",
					site_categories_exclude = '" . $exclude_category . "', use_embed = '" . $use_embed . "',
					thumb_by_horiz_width = '" . $thumb_by_horiz_width . "', max_times_used_gals = '" . $max_times_used_gals . "',
					site_niche = '" . $niche . "', additional_redis_server = '" . $additional_redis_server . "', vcdn_type = '" . $vcdn_type . "',
					use_unique_tags = '" . $use_unique_tags . "', default_title_for_tag = '" . $default_title_for_tag . "' 
					WHERE site_id = '" . $site_id . "'";



			$db->autocommit(false);

			$db->query($sql) ? null : $all_query_ok = false;

			$this->createExcludeGalsTable($site_id, $db);
			$this->createTagsModelsGalsTables($site_id, $db);

			if ($all_query_ok) {
				$db->commit();
				$result = $site_id;
			} else {
				$db->rollback();
			}
			$db->autocommit(true);
		} else {
			$log = new Logger(__METHOD__ . ": Ошибка добавления, нет коннекта к БД", true);
		}
		return $result;
	}

	public function checkSourcesTables($site_id)
	{
		$result = false;
		$main_table_counter = $this->getSiteSourcesList($site_id);
		if ($main_table_counter) $main_table_counter = count($main_table_counter);
		else $main_table_counter = 0;
		$sites_sources_table_counter = $this->countSiteSourcesFromSSTable($site_id);

		$result['status'] = ($sites_sources_table_counter == $main_table_counter) ? 'ok' : 'error';
		$result['main_table'] = $main_table_counter;
		$result['sites_sources_table'] = $sites_sources_table_counter;

		return $result;
	}



	public function countSiteSourcesFromSSTable($site_id)
	{
		$result = false;
		$site_id = (int)$site_id;

		if ($site_id > 0) {
			$db = DB::get();
			if ($db) {
				$s_count = 0;
				$sql = "SELECT count(DISTINCT gal_paysite) 
						FROM site_" . $site_id . ";";
				if ($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($s_count);
					if ($stmt->fetch()) {
						$result = $s_count;
					}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Неверные входящие параметры", true);
		}
		return $result;
	}


	public function countSiteSources($site_id)
	{
		$result = false;
		$site_id = (int)$site_id;

		if ($site_id > 0) {
			$db = DB::get();
			if ($db) {
				$gals_count = 0;

				$sql = "SELECT count(id) 
						FROM site_sources
						WHERE site_id = " . $site_id . " AND total_count > 0 AND added_on > 0;";


				if ($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($gals_count);
					if ($stmt->fetch()) {
						$result = $gals_count;
					}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Неверные входящие параметры", true);
		}
		return $result;
	}





	private function resetSiteGalleriesSources($site_id, $source_id, &$db)
	{

		$result = false;
		if ($db == NULL) {
			$db = DB::get();
		}

		$site_id = (int)$site_id;
		$source_id = (int)$source_id;

		if ($db) {
			if ($site_id > 0 && $source_id) {
				$sql = "UPDATE sites_sources 
						SET gals_count = 0, video_count = 0, total_count = 0 
						WHERE site_id = " . $site_id . " AND source_id = " . $source_id . ";";
				$result = $db->query($sql);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}
		return $result;
	}

	function getSourceGalleriesTotalCount($site_id, $source_id, &$db = NULL)
	{
		return $this->getSourceGalleriesCount($site_id, $source_id, false, $db);
	}

	function getSourceGalleriesPicsCount($site_id, $source_id, &$db = NULL)
	{
		return $this->getSourceGalleriesCount($site_id, $source_id, 'pics', $db);
	}

	function getSourceGalleriesMoviesCount($site_id, $source_id, &$db = NULL)
	{
		return $this->getSourceGalleriesCount($site_id, $source_id, 'movies', $db);
	}

	function getSourceGalleriesCount($site_id, $source_id, $gal_type = false, &$db = NULL)
	{
		$result = false;

		$source_id = (int)$source_id;
		$site_id = (int)$site_id;
		if ($gal_type === false || preg_match("#^(movies|pics|gif)$#", $gal_type)) {
			$gal_type_ok = true;
		} else {
			$gal_type_ok = false;
		}

		if ($source_id > 0 && $site_id > 0 && $gal_type_ok) {
			if ($db == NULL) {
				$db = DB::get();
			}

			if ($db) {

				$sql = "SELECT count(id) 
						FROM site_" . $site_id . " 
						WHERE gal_paysite = '" . $source_id . "'";
				if ($gal_type) {
					$sql .= " AND gal_type = '" . $gal_type . "'";
				}

				$gals_count = 0;

				if ($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($gals_count);
					if ($stmt->fetch()) {
						$result = $gals_count;
					}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		}


		return $result;
	}


	function updateSourceGalleriesCounter($site_id, $source_id, $first_added_gallery, &$db = NULL)
	{
		$result = false;

		$source_id = (int)$source_id;
		$site_id = (int)$site_id;
		$first_added_gallery = (int)$first_added_gallery;

		if ($source_id > 0 && $site_id > 0 && $first_added_gallery > 0) {
			if ($db == NULL) {
				$db = DB::get();
			}

			$gals_source_total_count = $this->getSourceGalleriesTotalCount($site_id, $source_id, $db);
			$gals_source_pics_count = $this->getSourceGalleriesPicsCount($site_id, $source_id, $db);
			$gals_source_movies_count = $this->getSourceGalleriesMoviesCount($site_id, $source_id, $db);

			// var_dump($gals_source_total_count,$gals_source_pics_count,$gals_source_movies_count );

			$gals_source_total_count = (int)$gals_source_total_count;
			$gals_source_pics_count = (int)$gals_source_pics_count;
			$gals_source_movies_count = (int)$gals_source_movies_count;

			$updated_on = time();

			if ($gals_source_total_count >= 0 && $gals_source_pics_count >= 0 && $gals_source_movies_count >= 0) {
				$sql = "UPDATE sites_sources 
						SET gals_count = ?, video_count = ?, total_count = ?, added_on = ?, updated_on = ?
						WHERE site_id = ? AND source_id = ?";
				if ($stmt = $db->prepare($sql)) {
					$stmt->bind_param(
						"iiiiiii",
						$gals_source_pics_count,
						$gals_source_movies_count,
						$gals_source_total_count,
						$first_added_gallery,
						$updated_on,
						$site_id,
						$source_id
					);
					if ($stmt->execute()) {
						$result = $stmt->affected_rows;
					} else {
						$log = new Logger(__METHOD__ . ": Ошибка STMT: '" . $stmt->error . "'", true);
					}

					$stmt->close();
				} else {
					$log = new Logger(__METHOD__ . ": Ошибка БД: '" . $db->error . "'", true);
				}
			}
		}

		return $result;
	}



	// ТЕГИ
	private function resetSiteGalleriesTags($site_id, $tag_id, &$db)
	{

		$result = false;
		if ($db == NULL) {
			$db = DB::get();
		}

		$site_id = (int)$site_id;
		$tag_id = (int)$tag_id;

		if ($db) {
			if ($site_id > 0 && $tag_id) {
				$sql = "DELETE FROM site_" . $site_id . "_galleries_tags WHERE tag_id = " . $tag_id . ";";
				$galleries_delete_ok = $db->query($sql);
				$sql = "UPDATE sites_tags 
						SET gals_count = 0, video_count = 0, total_count = 0 
						WHERE site_id = " . $site_id . " AND tag_id = " . $site_id . ";";
				$result = $db->query($sql);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}
		return $result;
	}

	function getTagGalleriesTotalCount($site_id, $tag_id, &$db = NULL)
	{
		return $this->getTagGalleriesCount($site_id, $tag_id, false, $db);
	}

	function getTagGalleriesPicsCount($site_id, $tag_id, &$db = NULL)
	{
		return $this->getTagGalleriesCount($site_id, $tag_id, 'pics', $db);
	}

	function getTagGalleriesMoviesCount($site_id, $tag_id, &$db = NULL)
	{
		return $this->getTagGalleriesCount($site_id, $tag_id, 'movies', $db);
	}

	function getTagGalleriesCount($site_id, $tag_id, $gal_type = false, &$db = NULL)
	{
		$result = false;

		$tag_id = (int)$tag_id;
		$site_id = (int)$site_id;
		if ($gal_type === false || preg_match("#^(movies|pics|gif)$#", $gal_type)) {
			$gal_type_ok = true;
		} else {
			$gal_type_ok = false;
		}

		if ($tag_id > 0 && $site_id > 0 && $gal_type_ok) {
			if ($db == NULL) {
				$db = DB::get();
			}

			if ($db) {

				$sql = "SELECT count(id) 
									FROM site_" . $site_id . "_galleries_tags 
									WHERE tag_id = '" . $tag_id . "'";
				if ($gal_type) {
					$sql .= " AND gal_type = '" . $gal_type . "'";
				}

				$gals_count = 0;

				if ($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($gals_count);
					if ($stmt->fetch()) {
						$result = $gals_count;
					}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		}


		return $result;
	}

	function updateTagGalleriesCounter($site_id, $tag_id, $first_added_gallery, &$db = NULL)
	{
		$result = false;

		$tag_id = (int)$tag_id;
		$site_id = (int)$site_id;
		$first_added_gallery = (int)$first_added_gallery;

		if ($tag_id > 0 && $site_id > 0 && $first_added_gallery > 0) {
			if ($db == NULL) {
				$db = DB::get();
			}

			$gals_tag_total_count = $this->getTagGalleriesTotalCount($site_id, $tag_id, $db);
			$gals_tag_pics_count = $this->getTagGalleriesPicsCount($site_id, $tag_id, $db);
			$gals_tag_movies_count = $this->getTagGalleriesMoviesCount($site_id, $tag_id, $db);

			$gals_tag_total_count = (int)$gals_tag_total_count;
			$gals_tag_pics_count = (int)$gals_tag_pics_count;
			$gals_tag_movies_count = (int)$gals_tag_movies_count;

			$updated_on = time();

			if ($gals_tag_total_count >= 0 && $gals_tag_pics_count >= 0 && $gals_tag_movies_count >= 0) {
				$sql = "UPDATE sites_tags 
						SET gals_count = ?, video_count = ?, total_count = ?, added_on = ?, updated_on = ?
						WHERE site_id = ? AND tag_id = ?";
				if ($stmt = $db->prepare($sql)) {
					$stmt->bind_param(
						"iiiiiii",
						$gals_tag_pics_count,
						$gals_tag_movies_count,
						$gals_tag_total_count,
						$first_added_gallery,
						$updated_on,
						$site_id,
						$tag_id
					);
					if ($stmt->execute()) {
						$result = $stmt->affected_rows;
					} else {
						$log = new Logger(__METHOD__ . ": Ошибка STMT: '" . $stmt->error . "'", true);
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__ . ": Ошибка БД: '" . $db->error . "'", true);
				}
			}
		}

		return $result;
	}

	// Модели
	private function resetSiteGalleriesModels($site_id, $model_id, &$db)
	{

		$result = false;
		if ($db == NULL) {
			$db = DB::get();
		}

		$site_id = (int)$site_id;
		$model_id = (int)$model_id;

		if ($db) {
			if ($site_id > 0 && $model_id) {
				$sql = "DELETE FROM site_" . $site_id . "_galleries_models WHERE model_id = " . $model_id . ";";
				$galleries_delete_ok = $db->query($sql);
				$sql = "UPDATE sites_models 
						SET gals_count = 0, video_count = 0, total_count = 0 
						WHERE site_id = " . $site_id . " AND model_id = " . $site_id . ";";
				$result = $db->query($sql);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}
		return $result;
	}

	function getModelGalleriesTotalCount($site_id, $model_id, &$db = NULL)
	{
		return $this->getModelGalleriesCount($site_id, $model_id, false, $db);
	}

	function getModelGalleriesPicsCount($site_id, $model_id, &$db = NULL)
	{
		return $this->getModelGalleriesCount($site_id, $model_id, 'pics', $db);
	}

	function getModelGalleriesMoviesCount($site_id, $model_id, &$db = NULL)
	{
		return $this->getModelGalleriesCount($site_id, $model_id, 'movies', $db);
	}

	function getModelGalleriesCount($site_id, $model_id, $gal_type = false, &$db = NULL)
	{
		$result = false;

		$model_id = (int)$model_id;
		$site_id = (int)$site_id;
		if ($gal_type === false || preg_match("#^(movies|pics|gif)$#", $gal_type)) {
			$gal_type_ok = true;
		} else {
			$gal_type_ok = false;
		}

		if ($model_id > 0 && $site_id > 0 && $gal_type_ok) {
			if ($db == NULL) {
				$db = DB::get();
			}

			if ($db) {

				$sql = "SELECT count(id) 
						FROM site_" . $site_id . "_galleries_models 
						WHERE model_id = '" . $model_id . "'";
				if ($gal_type) {
					$sql .= " AND gal_type = '" . $gal_type . "'";
				}
				$gals_count = 0;
				if ($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($gals_count);
					if ($stmt->fetch()) {
						$result = $gals_count;
					}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		}


		return $result;
	}


	// аналогичная функция находится в class.models.php, думаю ее надо убрать оттуда и оставить геты-сеты и поиск
	function getSiteModelsList($site_id, $niche = false, $type = false)
	{
		$result = false;
		$site_id = intval($site_id);
		$where_used = false;
		if ($niche && preg_match("#(gay|shemale|staright)#im", $niche)) $niche = ucfirst(strtolower($niche));
		else $nice = false;
		if ($type && preg_match("#(pics|movies)#im", $type)) $type = ucfirst(strtolower($type));
		elseif ($type == 'gifs' || $type == 'gifs') $type = 'gif';
		else $type = false;
		if ($site_id) {
			if (!$niche && ! $type) {
				$sql = "select  galleries_models.model_id, model.name, model.picture, 
								model.category_of_age,
								count(site_" . $site_id . ".gal_id) as model_galleries_count,
								sites_models.likes, sites_models.pageviews
						from  site_" . $site_id . "
						left join galleries_models on site_" . $site_id . ".gal_id = galleries_models.gallery_id
						left join model on galleries_models.model_id = model.id_model
						left join sites_models on (
													sites_models.model_id = model.id_model 
													AND sites_models.site_id = '" . $site_id . "'
												  )
						group by galleries_models.model_id
						order by model.name";
			} else {
				$sql = "select  galleries_models.model_id, model.name, model.picture, 
								model.category_of_age, 
								count(site_" . $site_id . ".gal_id) as model_galleries_count,
								sites_models.likes, sites_models.pageviews
						from  site_" . $site_id . "
						left join galleries_models on site_" . $site_id . ".gal_id = galleries_models.gallery_id
						left join model on galleries_models.model_id = model.id_model
						left join sites_models on ( 
													sites_models.model_id = model.id_model 
													AND sites_models.site_id = '" . $site_id . "'
												  )
						left join galleries on galleries.gal_id = site_" . $site_id . ".gal_id";

				if ($type) {
					$where_used = true;
					$sql .= " where galleries.gal_type = '" . $type . "' ";
				}
				if ($niche) {
					if (!$where_used) $sql .= " where ";
					else $sql .= " and ";
					$sql .= " galleries.gal_niche = '" . $niche . "' ";
				}
				$sql .= "group by galleries_models.model_id
						 order by model.name";
			}
			//echo $sql ."<br>";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$gallery_rs = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery_rs) {
					foreach ($gallery_rs as $model) {
						// var_dump($model);
						if ($model['model_id']) {
							$output['id'] = $model['model_id'];
							$output['count'] = $model['model_galleries_count'];
							$output['name'] = $model['name'];
							$output['likes'] = $model['likes'];
							$output['category_of_age'] = $model['category_of_age'];
							if ($model['likes']) $output['likes'] = intval($model['likes']);
							else $output['likes'] = 0;
							if ($model['pageviews']) $output['pageviews'] = intval($model['pageviews']);
							else $output['pageviews'] = 0;
							$output['picture'] = $model['picture'];

							$result[$output['id']] = $output;
						}
					}
				}
			}
		}
		return $result;
	}

	function updateModelGalleriesCounter($site_id, $model_id, $first_added_gallery, &$db = NULL)
	{
		$result = false;

		$model_id = (int)$model_id;
		$site_id = (int)$site_id;
		$first_added_gallery = (int)$first_added_gallery;

		if ($model_id > 0 && $site_id > 0 && $first_added_gallery > 0) {
			if ($db == NULL) {
				$db = DB::get();
			}

			$gals_model_total_count = $this->getModelGalleriesTotalCount($site_id, $model_id, $db);
			$gals_model_pics_count = $this->getModelGalleriesPicsCount($site_id, $model_id, $db);
			$gals_model_movies_count = $this->getModelGalleriesMoviesCount($site_id, $model_id, $db);

			$gals_model_total_count = (int)$gals_model_total_count;
			$gals_model_pics_count = (int)$gals_model_pics_count;
			$gals_model_movies_count = (int)$gals_model_movies_count;

			$updated_on = time();

			if ($gals_model_total_count >= 0 && $gals_model_pics_count >= 0 && $gals_model_movies_count >= 0) {
				$sql = "UPDATE sites_models 
						SET gals_count = ?, video_count = ?, total_count = ?, added_on = ?, updated_on = ?
						WHERE site_id = ? AND model_id = ?";
				if ($stmt = $db->prepare($sql)) {
					$stmt->bind_param(
						"iiiiiii",
						$gals_model_pics_count,
						$gals_model_movies_count,
						$gals_model_total_count,
						$first_added_gallery,
						$updated_on,
						$site_id,
						$model_id
					);
					if ($stmt->execute()) {
						$result = $stmt->affected_rows;
					} else {
						$log = new Logger(__METHOD__ . ": Ошибка STMT: '" . $stmt->error . "'", true);
					}

					$stmt->close();
				} else {
					$log = new Logger(__METHOD__ . ": Ошибка БД: '" . $db->error . "'", true);
				}
			}
		}

		return $result;
	}

	/* галеры на сайте, с лайками и прочим, тоже дубль из class.models.php
		изменения: удален LEFT JOIN с sites_models */
	function getModelsGalleries($model_id)
	{ // есть в моделях
		$result = false;
		$model_id = (int)$model_id;
		$site_id = (int)$this->id;
		if ($model_id && $site_id && $this->_db) {
			// $this->_db->debug = true;
			$sql = "select  site_" . intval($site_id) . ".gal_id, site_" . intval($site_id) . ".id, 
							site_" . intval($site_id) . ".time_added, galleries.gal_type
					from site_" . intval($site_id) . "
					left join galleries_models on galleries_models.gallery_id = site_" . intval($site_id) . ".gal_id
					left join galleries on site_" . intval($site_id) . ".gal_id = galleries.gal_id
					where galleries_models.model_id = '" . $model_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) $result = $rows;
			}
		}
		return $result;
	}

	function getSiteCachingServers($site_id)
	{
		$result = false;

		if ($site_id > 0) {
			$db = DB::get();
			if ($db) {
				$sql = "SELECT redis_server FROM sites
						WHERE site_id = ? OR use_galleries_from = ?
						GROUP BY redis_server";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("ii", $site_id, $site_id)) {
						if ($stmt->execute()) {

							$redis_server = false;

							$stmt->bind_result($redis_server);
							$result = array();
							while ($stmt->fetch()) {
								$result[] = $redis_server;
							}
						} else {
							$log = new Logger(__METHOD__ . ":  '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ":  '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Неправильный входящший параметр - SITE_ID", true);
		}


		return $result;
	}


	function getSiteAdditionalCachingServers($site_id)
	{
		$result = false;

		if ($site_id > 0) {
			$db = DB::get();
			if ($db) {
				$sql = "SELECT redis_server FROM sites
						WHERE use_galleries_from = ?
						AND redis_server != (
											 SELECT redis_server 
											 FROM sites 
											 WHERE site_id = ?
											 )";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("ii", $site_id, $site_id)) {
						if ($stmt->execute()) {

							$redis_server = false;

							$stmt->bind_result($redis_server);
							$result = array();
							while ($stmt->fetch()) {
								$result[] = $redis_server;
							}
						} else {
							$log = new Logger(__METHOD__ . ":  '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ":  '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Неправильный входящший параметр - SITE_ID", true);
		}


		return $result;
	}


	/* фикс тэгов - основная функция */
	function fixSitesTags($site_id, $tag_id = false, &$db = NULL)
	{
		$result = false;

		// введено для того чтобы можно было в системе общего коммита использовать
		$local_db_connection = false;
		$galleries_tags_count = false;
		$all_query_ok = true;

		if ($db == NULL) {
			$db = DB::get();
			$local_db_connection = true;
		}

		if ($local_db_connection) {
			$db->autocommit(false);
		}

		if ($tag_id) {
			$tag_id = (int)$tag_id;
			if ($tag_id > 0) {
				$galleries_tags[]['id'] = $tag_id;
				$galleries_tags_count = 1;
			} else {
				$log = new Logger(__METHOD__ . ": Используется параметр tag_id, но не INT", true);
				return $result;
			}
		} else {
			$galleries_tags = $this->getSiteTagsList($site_id);
			is_array($galleries_tags) ? $galleries_tags_count = count($galleries_tags) : $galleries_tags_count = 0;
		}

		echo "Всего тегов: " . $galleries_tags_count . "<br>";

		if ($db) {

			$counter = 0;

			foreach ($galleries_tags as $tag) {

				$tag_id = $tag['id'];
				$counter++;

				echo $counter . ". Обрабатываем тег: " . $tag_id . ", ";

				$all_query_ok = true;

				$reset_tag_ok = $this->resetSiteGalleriesTags($site_id, $tag_id, $db);
				if ($reset_tag_ok) {
					$tag_galleries = $this->getTagsGalleries($tag_id);
					echo "Галер в теге: " . count($tag_galleries) . " .. ";
					if ($tag_galleries && is_array($tag_galleries)) {
						$sql = "INSERT INTO site_" . $site_id . "_galleries_tags
								(gal_id, local_id, tag_id, gal_type, added_on)
								VALUES ";
						$tags_used = false;
						$first_added_gallery = false;
						foreach ($tag_galleries as $gallery) {
							$gal_id = (int)$gallery['gal_id'];
							$gal_local_id = (int)$gallery['id'];
							$gal_type = strtolower($gallery['gal_type']);
							$gal_added_on = (int)$gallery['time_added'];
							if (!$first_added_gallery || $first_added_gallery > $gal_added_on) {
								$first_added_gallery = $gal_added_on;
							}

							if ($tags_used) {
								$sql .= ",";
							} else {
								$tags_used = true;
							}
							$sql .= "(" . $gal_id . "," . $gal_local_id . "," . $tag_id . ",'" . $gal_type . "'," . $gal_added_on . ")";
						}
						if ($tags_used) {
							// echo "\n".$sql."\n";
							echo " Executed, OK<br>";

							$db->query($sql) ? null : $all_query_ok = false;
						}
						if ($all_query_ok) {
							$tag_galleries_updated = $this->updateTagGalleriesCounter($site_id, $tag_id, $first_added_gallery, $db);
							$tag_galleries_updated ? $tags_array[] = $tag_id  : $all_query_ok = false;
						}
					}
				}

				// $db->query($sql) ? null : $all_query_ok = false;

			}
			// echo "Tags array:";
			// var_dump($tags_array);
			if ($tags_array && is_array($tags_array)) {
				$tags_list = implode(",", $tags_array);
				$sql = "UPDATE sites_tags 
						SET gals_count = 0, video_count = 0, total_count = 0, added_on = 0, updated_on = 0
						WHERE site_id = " . $site_id . " AND tag_id NOT IN (" . $tags_list . ");";
				$db->query($sql) ? null : $all_query_ok = false;
			}

			// только в случае если отдельно вызывается функция
			// т.е. если в функцию не передается указатель на коннект к базе
			if ($local_db_connection) {
				if ($all_query_ok) {
					$db->commit();
					$result = $site_id;
				} else {
					$db->rollback();
				}
				$db->autocommit(true);
			} else {
				$result = $all_query_ok;
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}

		return $result;
	}

	/* фикс тэгов - основная функция */
	function fixSitesAllTags($site_id, &$db = NULL)
	{
		return $this->fixSitesTags($site_id, false, $db);
	}

	/* фикс моделей - основная функция */
	function fixSitesModels($site_id, $model_id = false, &$db = NULL)
	{
		$result = false;

		// введено для того чтобы можно было в системе общего коммита использовать
		$local_db_connection = false;
		$galleries_models_count = false;
		$all_query_ok = true;

		if ($db == NULL) {
			$db = DB::get();
			$local_db_connection = true;
		}

		if ($local_db_connection) {
			$db->autocommit(false);
		}

		if ($model_id) {
			$model_id = (int)$model_id;
			if ($model_id > 0) {
				$galleries_models[]['id'] = $model_id;
				$galleries_models_count = 1;
			} else {
				$log = new Logger(__METHOD__ . ": Используется параметр model_id, но не INT", true);
				return $result;
			}
		} else {
			$galleries_models = $this->getSiteModelsList($site_id);
			is_array($galleries_models) ? $galleries_models_count = count($galleries_models) : $galleries_models_count = 0;
		}

		echo "Всего тегов: " . $galleries_models_count . "<br>";

		if ($db) {

			$counter = 0;
			$models_array = array();
			if ($galleries_models_count > 0) {
				foreach ($galleries_models as $model) {

					$model_id = $model['id'];
					$counter++;

					echo $counter . ". Обрабатываем модель: " . $model_id . ", ";

					$all_query_ok = true;

					$reset_model_ok = $this->resetSiteGalleriesModels($site_id, $model_id, $db);
					if ($reset_model_ok) {
						$model_galleries = $this->getModelsGalleries($model_id);
						echo "Галер с моделью: " . count($model_galleries) . " .. ";
						if ($model_galleries && is_array($model_galleries)) {
							$sql = "INSERT INTO site_" . $site_id . "_galleries_models
									(gal_id, local_id, model_id, gal_type, added_on)
									VALUES ";
							$models_used = false;
							$first_added_gallery = false;
							foreach ($model_galleries as $gallery) {
								$gal_id = (int)$gallery['gal_id'];
								$gal_local_id = (int)$gallery['id'];
								$gal_type = strtolower($gallery['gal_type']);
								$gal_added_on = (int)$gallery['time_added'];
								if (!$first_added_gallery || $first_added_gallery > $gal_added_on) {
									$first_added_gallery = $gal_added_on;
								}

								if ($models_used) {
									$sql .= ",";
								} else {
									$models_used = true;
								}
								$sql .= "(" . $gal_id . "," . $gal_local_id . "," . $model_id . ",'" . $gal_type . "'," . $gal_added_on . ")";
							}
							if ($models_used) {
								// echo "\n".$sql."\n";
								echo " Executed, OK<br>";

								$db->query($sql) ? null : $all_query_ok = false;
							}
							if ($all_query_ok) {
								$model_galleries_updated = $this->updateModelGalleriesCounter($site_id, $model_id, $first_added_gallery, $db);
								$model_galleries_updated ? $models_array[] = $model_id  : $all_query_ok = false;
							}
						}
					}
				}

				// обнуление моделей не из списка
				// можно исключить обнуление pageviews и likes, чтобы отслеживать косячных моделей
				if ($models_array && is_array($models_array)) {
					$models_list = implode(",", $models_array);
					$sql = "UPDATE sites_models 
							SET gals_count = 0, video_count = 0, total_count = 0, added_on = 0, updated_on = 0
							WHERE site_id = " . $site_id . " AND model_id NOT IN (" . $models_list . ");";
					$db->query($sql) ? null : $all_query_ok = false;
				}
			} else {
				echo "No models found<br>";
			}


			// только в случае если отдельно вызывается функция
			// т.е. если в функцию не передается указатель на коннект к базе
			if ($local_db_connection) {
				if ($all_query_ok) {
					$db->commit();
					$result = true;
				} else {
					$db->rollback();
				}
				$db->autocommit(true);
			} else {
				$result = $all_query_ok;
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}

		return $result;
	}

	/* фикс моделей - основная функция */
	function fixSitesAllModels($site_id, &$db = NULL)
	{
		return $this->fixSitesModels($site_id, false, $db);
	}


	/* фикс моделей - основная функция */
	function fixSitesSources($site_id, $source_id = false, &$db = NULL)
	{
		$result = false;

		// введено для того чтобы можно было в системе общего коммита использовать
		$local_db_connection = false;
		$galleries_sources_count = false;
		$all_query_ok = true;

		if ($db == NULL) {
			$db = DB::get();
			$local_db_connection = true;
		}

		if ($local_db_connection) {
			$db->autocommit(false);
		}

		if ($source_id) {
			$source_id = (int)$source_id;
			if ($source_id > 0) {
				$galleries_sources[]['id'] = $source_id;
				$galleries_sources_count = 1;
			} else {
				$log = new Logger(__METHOD__ . ": Используется параметр source_id, но не INT", true);
				return $result;
			}
		} else {
			$galleries_sources = $this->getSiteSourcesList($site_id);
			is_array($galleries_sources) ? $galleries_sources_count = count($galleries_sources) : $galleries_sources_count = 0;
		}

		echo "Всего патников/источников: " . $galleries_sources_count . "<br>";

		if ($db) {

			$counter = 0;
			$sources_array = false;

			foreach ($galleries_sources as $source) {

				$source_id = $source['id'];
				$counter++;

				echo $counter . ". Обрабатываем источник: " . $source_id . ", ";

				$all_query_ok = true;

				$reset_sources_ok = $this->resetSiteGalleriesSources($site_id, $source_id, $db);
				if ($reset_sources_ok) {
					$source_galleries = $this->getSourcesGalleries_short($source_id);
					echo "Галер источника: " . count($source_galleries) . " .. ";
					if ($source_galleries && is_array($source_galleries)) {

						//  удалить и проверить !!!!

						$sources_used = false;
						$first_added_gallery = false;
						$source_galleries_list = false;

						foreach ($source_galleries as $gallery) {
							$gal_id = (int)$gallery['gal_id'];
							$gal_local_id = (int)$gallery['id'];
							$gal_type = strtolower($gallery['gal_type']);
							$gal_added_on = (int)$gallery['time_added'];
							if (!$first_added_gallery || $first_added_gallery > $gal_added_on) {
								$first_added_gallery = $gal_added_on;
							}

							$source_galleries_list[] = $gal_id;
						}
						if ($source_galleries_list) {
							$sql = "UPDATE site_" . $site_id . " 
									SET gal_paysite = " . $source_id . "
									WHERE gal_paysite <> " . $source_id . " AND gal_id IN (" . implode(",", $source_galleries_list) . ")";

							$db->query($sql) ? null : $all_query_ok = false;
						}
						if ($all_query_ok) {
							$source_galleries_updated = $this->updateSourceGalleriesCounter($site_id, $source_id, $first_added_gallery, $db);
							$source_galleries_updated ? $sources_array[] = $source_id  : $all_query_ok = false;
						}
					}
				}

				// $db->query($sql) ? null : $all_query_ok = false;

			}
			// обнуление значений не вошедших в список моделей

			if ($sources_array && is_array($sources_array)) {
				$sources_list = implode(",", $sources_array);
				$sql = "UPDATE sites_sources 
						SET gals_count = 0, video_count = 0, total_count = 0, added_on = 0, updated_on = 0
						WHERE site_id = " . $site_id . " AND source_id NOT IN (" . $sources_list . ");";
				$db->query($sql) ? null : $all_query_ok = false;
			}

			// только в случае если отдельно вызывается функция
			// т.е. если в функцию не передается указатель на коннект к базе
			if ($local_db_connection) {
				if ($all_query_ok) {
					$db->commit();
					$result = true;
				} else {
					$db->rollback();
				}
				$db->autocommit(true);
			} else {
				$result = $all_query_ok;
			}
		} else {
			$log = new Logger(__METHOD__ . ": Нет коннекта к БД", true);
		}

		return $result;
	}

	/* фикс моделей - основная функция */
	function fixSitesAllSources($site_id, &$db = NULL)
	{
		return $this->fixSitesSources($site_id, false, $db);
	}

	//
	// добавление новых таблиц site_#_galleries_tags, site_#_galleries_models
	// и заполнение их
	//
	function fixSiteToNewTables($site_id)
	{
		if ($this->createTagsModelsGalsTables($site_id)) {

			$galleries_total_count = $this->galleriesCount($site_id);
			echo "Таблицы site_" . $site_id . "_galleries_tags, site_" . $site_id . "_galleries_models, созданы<br>
				  Собираем текущую информацию по платникам/источникам: <br>			
				  Всего галер: " . $galleries_total_count . "<br><br>";

			// $galleries_sources = $this->getSiteSourcesList($site_id);
			// is_array($galleries_sources) ? $galleries_sources_count = count($galleries_sources) : $galleries_sources_count = 0;

			// echo "Всего платников/источников: ".$galleries_sources_count."\n";

			$counter = 0;
			$sources_array = false;
			$tags_array = false;
			$models_array = false;

			$this->addAllSourcesToSite($site_id);
			$this->addAllTagsToSite($site_id);
			$this->addAllModelsToSite($site_id);

			$db = DB::get();
			$db->autocommit(false);

			$tags_ok = $this->fixSitesAllTags($site_id, $db);
			$models_ok = $this->fixSitesAllModels($site_id, $db);
			$sources_ok = $this->fixSitesAllSources($site_id, $db);

			if ($tags_ok && $models_ok && $sources_ok) {
				$db->commit();
				$result = $site_id;
			} else {
				$db->rollback();
			}

			$db->autocommit(true);
		}
	}


	function getSitesList($sites_count = 50, $page = 0, $niche = false, $category = false, $content_type = false, $server_id = -1)
	{
		$result = array();
		$db = DB::get();

		if ($db) {
			$sites_count = (int)$sites_count;
			$page = (int)$page;
			$page = $page * $sites_count;
			if ($sites_count > 0 && $page >= 0) {
				$sql = "SELECT site_id, local_id_flag, site_name, site_niche, hand_flag, site_main_category, or_tag, 
							   sites_gallery_url, last_update, redis_server, pageviews_updated_on, likes_updated_on, 
							   use_embed, site_type, site_own_titles, site_own_main_thumbs, language, use_galleries_from, 
							   digit_base_for_id, vcdn_type
						FROM sites";
				$where_used = false;
				if ($niche && preg_match("#^(gay|straight|shemale)$#im", $niche)) {
					$where_used = true;
					$sql .= " WHERE site_niche = '" . $niche . "'";
				}

				if ($category && preg_match("#([a-z0-9-\s])#", $category)) {
					if ($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " WHERE ";
						$where_used = true;
					}

					$sql .= "site_main_category = '" . $category . "'";
				}

				if ($content_type && preg_match("#^(video|pics|mix|gif)$#im", $content_type)) {
					if ($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " WHERE ";
						$where_used = true;
					}

					$sql .= "site_type = '" . $content_type . "'";
				}

				if ($server_id !== false && $server_id !== -1 && (int)$server_id >= 0) {
					if ($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " WHERE ";
						$where_used = true;
					}

					$sql .= "redis_server = '" . $server_id . "'";
				}

				$sql .= " LIMIT " . $page . ", " . $sites_count . ";";
				// echo $sql;
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {

						$site_id = false;
						$local_id_flag = false;
						$site_name = false;
						$site_niche = false;
						$hand_flag = false;
						$site_main_category = false;
						$or_tag = false;
						$sites_gallery_url = false;
						$last_update = false;
						$redis_server = false;
						$pageviews_updated_on = false;
						$likes_updated_on = false;
						$use_embed = false;
						$site_type = false;
						$site_own_titles = false;
						$site_own_main_thumbs = false;
						$language = false;
						$use_galleries_from = false;
						$digit_base_for_id = false;
						$vcdn_type = false;

						$stmt->bind_result($site_id, $local_id_flag, $site_name, $site_niche, $hand_flag, $site_main_category, $or_tag, $sites_gallery_url, $last_update, $redis_server, $pageviews_updated_on, $likes_updated_on, $use_embed, $site_type, $site_own_titles, $site_own_main_thumbs, $language, $use_galleries_from, $digit_base_for_id, $vcdn_type);



						while ($stmt->fetch()) {
							$result[] = compact("site_id", "local_id_flag", "site_name", "site_niche", "hand_flag", "site_main_category", "or_tag", "sites_gallery_url", "last_update", "redis_server", "pageviews_updated_on", "likes_updated_on", "use_embed", "site_type", "site_own_titles", "site_own_main_thumbs", "language", "use_galleries_from", "digit_base_for_id", "vcdn_type");
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT error: '" . $stmt->error . "' ", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": DB error: '" . $db->error . "' ", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": sites_count or page not an int or out of range", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connection", true);
		}
		return $result;
	}

	function sitesCount($niche = false, $category = false, $content_type = false, $server_id = -1)
	{
		$result = false;
		$db = DB::get();

		if ($db) {


			$sql = "SELECT count(site_id)
						FROM sites";
			$where_used = false;
			if ($niche && preg_match("#^(gay|straight|shemale)$#im", $niche)) {
				$where_used = true;
				$sql .= " WHERE site_niche = '" . $niche . "'";
			}

			if ($category && preg_match("#([a-z0-9-\s])#", $category)) {
				if ($where_used) {
					$sql .= " AND ";
				} else {
					$sql .= " WHERE ";
					$where_used = true;
				}

				$sql .= "site_main_category = '" . $category . "'";
			}

			if ($content_type && preg_match("#^(video|pics|mix|gif)$#im", $content_type)) {
				if ($where_used) {
					$sql .= " AND ";
				} else {
					$sql .= " WHERE ";
					$where_used = true;
				}

				$sql .= "site_type = '" . $content_type . "'";
			}
			if ($server_id !== false && $server_id !== -1 && (int)$server_id >= 0) {
				if ($where_used) {
					$sql .= " AND ";
				} else {
					$sql .= " WHERE ";
					$where_used = true;
				}

				$sql .= "redis_server = '" . $server_id . "'";
			}
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": STMT execute error '" . $stmt->error . "' ", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT prepare error '" . $db->error . "' ", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connection", true);
		}
		return $result;
	}
}
