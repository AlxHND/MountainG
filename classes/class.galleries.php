<?php
function get_time()
{
	$start_time = explode(' ', microtime());
	$real_time = $start_time[1] . substr($start_time[0], 1);
	return $real_time;
}

function getThumbsSizes($content_type)
{
	global $rssThumbSizes, $rssMovieThumbs;

	if (preg_match("#^(video|movies|movs)$#im", $content_type)) {
		return $rssMovieThumbs;
	}

	if (preg_match("#^(pics|pictures)$#im", $content_type)) {
		return $rssThumbSizes;
	}

	return false;
}


class Galleries
{

	private $all_statuses = ['trash', 'delete', 'unzipping', 'fetching', 'video_screening', 'video_converting', 'thumbing', 'pics_resizing', 'OK', 'uploaded', 'unzip_fail', 'zipupload_fail', 'fetching_fail', 'screen_fail', 'gif_fail', 'video_fail', 'grab_fail', 'thumbs_fail', 'upload_fail'];
	private $fail_statuses = ['unzip_fail', 'zipupload_fail', 'fetching_fail', 'screen_fail', 'gif_fail', 'video_fail', 'grab_fail', 'thumbs_fail', 'upload_fail'];
	private $ready_statuses = ['OK', 'uploaded'];
	private $processing_statuses = ['unzipping', 'fetching', 'video_screening', 'video_converting', 'thumbing', 'pics_resizing'];
	private $delete_statuses = ['trash', 'delete'];
	private $current_galleries_count = 0;
	private $horiz_resize_ratio = false;
	private $error_on_insert_msg = false;

	private $gal_id = false;

	private $_db = null;

	private $isAllowedTagsFromTitle = true;
	private $addModelsFromImport = false;


	function __construct(PDO $db_connect = null)
	{
		if (!$db_connect) {
			$db = new db_access(); // заглушка для вызовов Galleries без параметров
			$this->_db = $db->_db;
		} else {
			$this->_db = $db_connect;
		}
	}


	public function disableTagsFromTitle()
	{
		$this->isAllowedTagsFromTitle = false;
	}

	public function forceAddModelsFromImport()
	{
		$this->addModelsFromImport = true;
	}




	public function getGalleryId()
	{
		return $this->gal_id;
	}


	public function setGalleryId($gal_id)
	{
		return $this->gal_id = ((int)$gal_id > 0) ? (int)$gal_id : false;
	}



	public function getMainGalleryInfo($gal_id)
	{
		$gal_id = (int)$gal_id;
		$gallery = false;
		$sql = "SELECT gal_id, gal_source, gal_md5, gal_title, gal_description, gal_niche, gal_status, gal_type,
					   gal_added, gal_paysite, gal_content_count, hosted_flag, crop_flag, main_gal, unique_gal,
					   gal_thumb, embed_flag, embed
				FROM galleries 
				WHERE gal_id = ?;";

		$db = DB::get();

		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt->bind_param("i", $gal_id)) {
					if ($stmt->execute()) {
						$gal_id = false;
						$gal_source = false;
						$gal_md5 = false;
						$gal_title = false;
						$gal_description = false;
						$gal_niche = false;
						$gal_status = false;
						$gal_type = false;
						$gal_added = false;
						$gal_paysite = false;
						$gal_content_count = false;
						$hosted_flag = false;
						$crop_flag = false;
						$main_gal = false;
						$unique_gal = false;
						$gal_thumb = false;
						$embed_flag = false;
						$embed = false;

						$stmt->bind_result(
							$gal_id,
							$gal_source,
							$gal_md5,
							$gal_title,
							$gal_description,
							$gal_niche,
							$gal_status,
							$gal_type,
							$gal_added,
							$gal_paysite,
							$gal_content_count,
							$hosted_flag,
							$crop_flag,
							$main_gal,
							$unique_gal,
							$gal_thumb,
							$embed_flag,
							$embed
						);
						if ($stmt->fetch()) {

							$gallery['id'] = $gal_id;
							$gallery['source'] = $gal_source;
							$gallery['md5'] = $gal_md5;
							$gallery['title'] = $gal_title;
							$gallery['description'] = $gal_description;
							$gallery['niche'] = $gal_niche;
							$gallery['status'] = $gal_status;
							$gallery['type'] = $gal_type;
							$gallery['date'] = $gal_added;
							$gallery['paysite']['id'] = $gal_paysite;
							$gallery['paysite_id'] = $gal_paysite;
							$gallery['contentCount'] = $gal_content_count;
							$gallery['hosted'] = $hosted_flag;
							$gallery['cropped'] = $crop_flag;
							$gallery['main_gal'] = $main_gal;
							$gallery['unique'] = $unique_gal;
							$gallery['gal_thumb'] = $gal_thumb;
							$gallery['embed_flag'] = $embed_flag;

							$gallery['video_embed'] = ($gallery['embed_flag']) ? json_encode($embed) : false;

							if ($gallery['type'] == 'Movies' && $gallery['status'] == 'OK') $gallery['video_url'] = "/" . $gallery['paysite']['id'] . "/" . $gallery['id'] . "/" . $gallery['md5'] . "/" . $gallery['id'] . ".mp4";
							elseif ($gallery['type'] == 'gif' && $gallery['status'] == 'OK') $gallery['gif_url'] = "/" . $gallery['paysite']['id'] . "/" . $gallery['id'] . "/" . $gallery['md5'] . "/" . $gallery['id'] . ".gif";
						} else {
							$log = new Logger(__METHOD__ . ": STMT fetch failed : " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $gallery;
	}

	public function getGalleryType($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;

		$sql = "SELECT gal_type FROM galleries WHERE gal_id = ?";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$gal_type = "";
							$stmt->bind_result($gal_type);

							if ($stmt->fetch()) {
								$result = $gal_type;
							} else {
								$log = new Logger(__METHOD__ . ": STMT fetch failed : " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	public function getGalleryTitle($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;

		$sql = "SELECT gal_title FROM galleries WHERE gal_id = ?";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$stmt->bind_result($result);
							$stmt->fetch();
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	public function getOkVideoGalleries($niche = false, $count = 50, $page = 0, $order = 'desc', $category = false)
	{

		$sort = 'id';
		$type = 'Movies';
		$offset = $page * $count;
		$paysite = false;
		$status = 'OK';
		$search = false;
		$searchBy = 'title';
		$model = false;
		$main_gal = false;

		return $this->getGalleriesList($sort, $order, $count, $offset, $type, $paysite, $status, $search, $category, $searchBy, $niche, $model, $main_gal);
	}

	public function getGalleriesList_pseudoSearch($modelId, $niche)
	{

		$result = array();
		$modelId = (int)$modelId;

		if ($modelId) {
			$sql = "SELECT name FROM model_names WHERE model_id = " . $modelId . ";";
			$q_result = $this->_db->query($sql);
			$rows = $q_result->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($rows as $row) {
				$search_result = $this->getGalleriesList('asc', 'id', 300, false, false, false, false, $row['name'], false, 'titledesc', $niche, $modelId);
				if (is_array($search_result)) $result = $result + $search_result;
			}
		}
		return $result;
	}


	function fixVideosToCDN()
	{
		$gals = $this->getVideosNotInCDN();

		var_dump($gals);
		if ($gals) {
			foreach ($gals as $gallery) {
				$result = $this->insertVideosToCdnQuery($gallery['gal_id']);
				var_dump($gallery['gal_id'], $result);
			}
		}
	}

	public function getVideosNotInCDN()
	{
		$sql = "SELECT galleries_videos.gal_id, galleries_videos.cdn_synced, cdn_sync_videos.file_status FROM galleries_videos
				LEFT JOIN cdn_sync_videos ON galleries_videos.gal_id = cdn_sync_videos.gal_id
				WHERE galleries_videos.video_status = 'ok' AND cdn_sync_videos.gal_id IS NULL 
				ORDER BY galleries_videos.gal_id
				LIMIT 0, 300";

		$q_result = $this->_db->query($sql);
		$rows = $q_result->fetchAll(\PDO::FETCH_ASSOC);
		return $rows;
	}

	public function countVideosNotInCDN()
	{
		$sql = "SELECT count(galleries_videos.gal_id) AS gals_count FROM galleries_videos
				LEFT JOIN cdn_sync_videos ON galleries_videos.gal_id = cdn_sync_videos.gal_id
				WHERE galleries_videos.video_status = 'ok' AND cdn_sync_videos.gal_id IS NULL 
				ORDER BY galleries_videos.gal_id
				LIMIT 0, 100";

		$q_result = $this->_db->query($sql);
		$rows = $q_result->fetchAll();
		return $rows[0]['gals_count'];
	}

	public function getGalleriesList($sort = false, $order = false, $count = false, $offset = false, $type = false, $paysite = false, $status = false, $search = false, $category = false, $searchBy = 'title', $niche = false, $model = false, $main_gal = false)
	{

		$model_name 	=  null;
		$get_vcd_status = ($type == 'Movies') ? true : false;
		$category 		= (int)$category;

		if ($search === false && $model && $model = intval($model)) {
			$sql = "SELECT name FROM model WHERE id_model = :id_model;";

			try {
				$stmt = $this->_db->prepare($sql);
				$stmt->execute(array('id_model' => $model));
				$model_name = $stmt->fetchColumn();
				$search = $model_name;
				$model = $model_name ? $model : false;
			} catch (PDOException $e) {
				echo __METHOD__ . ': Ошибка БД: ' . $e->getMessage() . '<BR>';
				new Logger(__METHOD__ . ": Ошибка БД: " . $e->getMessage(), true);
				return false;
			}
		}

		$db = DB::get();
		if ($db) {
			$sql = "SELECT DISTINCT
					galleries.gal_id, galleries.gal_source, galleries.gal_title, galleries.gal_niche, galleries.gal_added,
					galleries.gal_type,galleries.gal_content_count,paysites.paysite_name, paysites.paysite_affiliate,
					galleries_pix.image_id, galleries_pix.image, galleries.gal_status, galleries.gal_paysite, galleries.gal_md5";
			if ($get_vcd_status) {
				$sql .= ", galleries_videos.cdn_synced";
				$video_sql = " LEFT JOIN galleries_videos ON galleries.gal_id = galleries_videos.gal_id";
			} else {
				$sql .= ", 'none'";
				$video_sql = "";
			}
			$sql .=	" FROM galleries
					LEFT JOIN paysites ON galleries.gal_paysite = paysites.paysite_id
					LEFT JOIN galleries_pix ON galleries.gal_id = galleries_pix.gal_id" . $video_sql;

			$countSql = "SELECT COUNT(gal_id) FROM galleries";

			switch ($searchBy) {
				case 'url':
					$searchSQL = "source";
					break;
				case 'titledesc':
					$searchTitleDesc = true;
					break;
				case 'desc':
					$searchSQL = 'description';
					break;
				default:
					$searchSQL = "title";
					break;
			}

			$sql_addition = "";

			if (($type && preg_match('/^(Pics|Movies)$/', $type)) || $main_gal || $category || $search || ($paysite && (int)$paysite) || preg_match('/^(Gay|Straight|Shemale)$/', $niche) || ($status && preg_match('/^(zip|zipupload|new|grabbed|thumbs|unzipping|uploaded|tagged|toregrab|OK|trash|delete|error|fetching_fail|video_fail|unzip_fail|all_fails|all_ready|all_processing|all_delete)$/', $status))) {

				$sql_addition .= " WHERE ";

				if ($main_gal) {
					if ($main_gal) {
						if (isset($typeFlag)) {
							$sql_addition .= " AND ";
						}
						$sql_addition .= " galleries.main_gal = '" . $main_gal . "' AND gal_status NOT LIKE 'delete' ";
						$typeFlag = true;
					}
				}

				if ($search) {
					if (isset($searchTitleDesc) && $searchTitleDesc) {
						$sql_addition .= " (LOWER(galleries.gal_title) LIKE '%" . strtolower($search) . "%' OR LOWER(galleries.gal_description) LIKE '%" . strtolower($search) . "%')";
					} else {
						$sql_addition .= " LOWER(galleries.gal_" . $searchSQL . ") LIKE '%" . strtolower($search) . "%'";
					}
					if ($model) {
						$sql_addition .= " AND galleries.gal_id NOT IN (SELECT gallery_id FROM galleries_models WHERE model_id = " . $model . ") ";
					}
					$typeFlag = true;
				}
				if ($type && preg_match('/^(Pics|Movies|embed|gif)$/', $type)) {
					if (isset($typeFlag)) {
						$sql_addition .= " AND ";
					}
					$sql_addition .= " galleries.gal_type = '" . $type . "'";
					$typeFlag = true;
				}
				if (($paysite && (int)$paysite)) {
					$paysite = (int)$paysite;
					if (isset($typeFlag)) {
						$sql_addition .= " AND ";
					}
					$sql_addition .= " galleries.gal_paysite = '" . $paysite . "'";
					$typeFlag = true;
				}
				if (($niche && preg_match('/^(Gay|Straight|Shemale)$/', $niche))) {
					if (isset($typeFlag)) {
						$sql_addition .= " AND ";
					}
					$sql_addition .= " galleries.gal_niche = '" . $niche . "'";
					$typeFlag = true;
				}
				// var_dump($status);
				if ($status) {

					if (!in_array($status, $this->all_statuses)) {
						$log = new Logger(__METHOD__ . ": ошибка статуса, статус во входящих '" . $status . "'", true);
					} else {
						if (isset($typeFlag)) {
							$sql_addition .= " AND ";
						}
						if (preg_match("#^(all_fails|all_ready|all_processing|all_delete)$#", $status)) {
							if ($status == 'all_fails') $use_statuses = implode("','", $this->fail_statuses);
							elseif ($status == 'all_processing') $use_statuses = implode("','", $this->processing_statuses);
							elseif ($status == 'all_delete') $use_statuses = implode("','", $this->delete_statuses);
							else $use_statuses = implode("','", $this->ready_statuses);
							$sql_addition .= " galleries.gal_status IN ('" . $use_statuses . "')";
						} else {
							$sql_addition .= " galleries.gal_status = '" . $status . "'";
						}

						$typeFlag = true;
					}
				}
				if ($category) {
					if (isset($typeFlag)) {
						$sql_addition .= " AND ";
					}
					$sql_addition .= " galleries.gal_id IN (SELECT gal_id FROM galleries_tags WHERE gal_tags = '" . $category . "')";
					$typeFlag = true;
				}
			}

			$count_sql = $countSql . $sql_addition;

			$sql .= $sql_addition . " GROUP BY galleries.gal_id ";

			switch ($sort) {
				case 'id':
					$sql .= " ORDER BY galleries.gal_id ";
					break;
				case 'title':
					$sql .= " ORDER BY galleries.gal_title ";
					break;
				case 'date':
					$sql .= " ORDER BY galleries.gal_added ";
					break;
				case 'paysite':
					$sql .= " ORDER BY paysites.paysite_name ";
					break;
				case 'niche':
					$sql .=  " ORDER BY galleries.gal_niche ";
					break;
				case 'pics':
					$sql .= " ORDER BY galleries.gal_content_count ";
					break;
				case 'status':
					$sql .= " ORDER BY galleries.gal_status ";
					break;
				default:
					$sql .= " ORDER BY galleries.gal_id ";
					break;
			}



			$sql .= (in_array($order, array('asc', 'desc'))) ? strtoupper($order) : "ASC";
			$sql .= ((int)$offset) ? " LIMIT " . (int)$offset : " LIMIT 0";
			$sql .= ((int)$count) ? ", " . (int)$count : ", 50";


			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$gal_id = false;
					$gal_source = false;
					$gal_title = false;
					$gal_niche = false;
					$gal_added = false;
					$gal_type = false;
					$gal_content_count = false;
					$paysite_name = false;
					$paysite_affiliate = false;
					$image_id = false;
					$image = false;
					$gal_status = false;
					$gal_paysite = false;
					$gal_md5 = false;
					$cdn_synced = false;
					$stmt->bind_result(
						$gal_id,
						$gal_source,
						$gal_title,
						$gal_niche,
						$gal_added,
						$gal_type,
						$gal_content_count,
						$paysite_name,
						$paysite_affiliate,
						$image_id,
						$image,
						$gal_status,
						$gal_paysite,
						$gal_md5,
						$cdn_synced
					);
					while ($stmt->fetch()) {
						$galleryId = $gal_id;
						$gallery[$galleryId]['id'] = $gal_id;
						$gallery[$galleryId]['title'] = $gal_title;
						$gallery[$galleryId]['niche'] = $gal_niche;
						$gallery[$galleryId]['url'] = $gal_source;
						$gallery[$galleryId]['gal_md5'] = $gal_md5;
						$gallery[$galleryId]['type'] = $gal_type;
						$gallery[$galleryId]['added'] = $gal_added;
						$gallery[$galleryId]['count'] = $gal_content_count;
						$gallery[$galleryId]['paysite_id'] = $gal_paysite;
						$gallery[$galleryId]['paysite'] = $paysite_name;
						$gallery[$galleryId]['affiliate'] = $paysite_affiliate;
						$gallery[$galleryId]['image'] = $image_id;
						$gallery[$galleryId]['orig_image'] = $image;
						$gallery[$galleryId]['status'] = $gal_status;
						$gallery[$galleryId]['cdn_synced'] = $cdn_synced;

						if ($gal_type == 'Movies' && $gal_status == 'OK') $gallery[$galleryId]['video_url'] = "/" . $gal_paysite . "/" . $gal_id . "/" . $gal_md5 . "/" . $gal_id . ".mp4";
						elseif ($gal_type == 'gif' && $gal_status == 'OK') $gallery[$galleryId]['gif_url'] = "/" . $gal_paysite . "/" . $gal_id . "/" . $gal_md5 . "/" . $gal_id . ".gif";
					}

					// var_dump($sql, $gallery);

					// die;
					//  echo $sql;
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		if (isset($gallery) && $gallery) {
			$this->setGalleryCounterSQL($count_sql);
			return $gallery;
		} else return FALSE;
	}

	public function getCurrentGlasCount()
	{
		return $this->current_galleries_count;
	}

	private function setGalleryCounterSQL($sql = false)
	{

		if ($sql) {

			$stmt = $this->_db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$this->current_galleries_count = $stmt->fetchColumn();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->errorInfo(), true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $this->_db->errorInfo(), true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": SQL string is empty", true);
		}
	}

	public function getLocalGalleryInfo($site_id, $local_gal_id)
	{
		$local_gal_id = (int)$local_gal_id;
		$site_id = (int)$site_id;
		$gallery = false;

		if ($local_gal_id && $site_id) {
			$sql = "SELECT time_added, url_desc, gal_id FROM 
					site_" . $site_id . " 
					WHERE id = ?;";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $local_gal_id)) {
							if ($stmt->execute()) {
								$time_added = 0;
								$url_desc = "";
								$gal_id = null;

								$stmt->bind_result($time_added, $url_desc, $gal_id);

								if ($stmt->fetch()) {
									$gallery['id'] = $local_gal_id;
									$gallery['time_added'] = $time_added;
									$gallery['url_desc'] = $url_desc;
									$gallery['global_id'] = $gal_id;
								} else {
									$log = new Logger(__METHOD__ . ": STMT fetch failed : " . $stmt->error, true);
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $gallery;
	}

	public function getLocalGalleryInfoByGID($site_id, $gal_id)
	{
		$gal_id = (int)$gal_id;
		$site_id = (int)$site_id;
		$gallery = false;

		if ($gal_id && $site_id) {
			$sql = "SELECT time_added, url_desc, gal_id, id FROM 
					site_" . $site_id . " 
					WHERE gal_id = ?;";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $gal_id)) {
							if ($stmt->execute()) {
								$time_added = 0;
								$url_desc = "";
								$gal_id = null;
								$local_gal_id = null;
								$stmt->bind_result($time_added, $url_desc, $gal_id, $local_gal_id);

								if ($stmt->fetch()) {
									$gallery['id'] = $gal_id;
									$gallery['local_id'] = $local_gal_id;
									$gallery['time_added'] = $time_added;
									$gallery['url_desc'] = $url_desc;
									$gallery['global_id'] = $gal_id;
								} else {
									$log = new Logger(__METHOD__ . ": STMT fetch failed : " . $stmt->error, true);
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $gallery;
	}

	public function getGalleryModelsWName($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;


		$sql = "SELECT model_id, name FROM 
				galleries_models 
				LEFT JOIN model ON galleries_models.model_id = model.id_model
				WHERE gallery_id = ?;";

		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$model_id = null;
							$model_name = "";
							$stmt->bind_result($model_id, $model_name);

							while ($stmt->fetch()) {
								$result[$model_id] = $model_name;
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return $result;
	}

	public function getGalleryModels($gal_id)
	{
		$result = array();
		$gal_id = (int)$gal_id;

		$sql = "SELECT model_id FROM 
				galleries_models 
				WHERE gallery_id = ?;";

		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$model_id = null;
							$stmt->bind_result($model_id);

							while ($stmt->fetch()) {
								$result[] = $model_id;
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return $result;
	}


	public function getGalleryTags($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		$gal_tags = null;


		$sql = "SELECT gal_tags FROM 
				galleries_tags 
				WHERE gal_id = ?;";

		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$stmt->bind_result($gal_tags);

							while ($stmt->fetch()) {
								$result[] = $gal_tags;
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return $result;
	}


	public function getLocalId($site_id, $gal_id)
	{
		$result = false;
		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;
		$local_gal_id = null;

		if ($site_id && $gal_id) {
			$sql = "SELECT id FROM site_" . $site_id . " WHERE gal_id = ?";

			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $gal_id)) {
							if ($stmt->execute()) {
								$stmt->bind_result($local_gal_id);

								if ($stmt->fetch()) {
									$result = $local_gal_id;
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}

	public function getLocalTitle($site_id, $local_gal_id)
	{
		$result = false;
		$site_id = (int)$site_id;
		$local_gal_id = (int)$local_gal_id;
		$own_title = "";

		if ($site_id && $local_gal_id) {
			$sql = "SELECT own_title FROM site_" . $site_id . " WHERE id = ?";

			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $local_gal_id)) {
							if ($stmt->execute()) {
								$stmt->bind_result($own_title);

								if ($stmt->fetch()) {
									$result = $own_title;
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}

	public function getGlobalId($site_id, $local_gal_id)
	{
		$site_id = (int)$site_id;
		$local_gal_id = (int)$local_gal_id;
		$result = false;

		if ($site_id > 0 && $local_gal_id > 0) {
			$sql = "SELECT gal_id FROM site_" . $site_id . " WHERE id = ?";

			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $local_gal_id)) {
							if ($stmt->execute()) {
								$gal_id = false;
								$stmt->bind_result($gal_id);

								if ($stmt->fetch()) {
									$result = $gal_id;
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}


	public function getLastAddedGalleries($site_id, $limit = 40, $page = 0)
	{
		$result = false;

		$site_id = (int)$site_id;
		$limit = (int)$limit;
		$page = (int)$page * $limit;
		$local_gal_id = null;

		if ($site_id && $limit) {
			$sql = "SELECT id FROM site_" . $site_id . " ORDER BY id DESC LIMIT ?, ?;";

			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("ii", $page, $limit)) {
							if ($stmt->execute()) {
								$stmt->bind_result($local_gal_id);

								while ($stmt->fetch()) {
									$result[] = $local_gal_id;
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}


	function getAllTitles()
	{
		$sql = "select gal_title from galleries where gal_status = 'OK'";
		$rs = $this->_db->query($sql);
		$title_rows = $rs->fetchAll(\PDO::FETCH_ASSOC);

		foreach ($title_rows as $title) {
			$title = strtolower(trim($title['gal_title']));
			$title = preg_replace("/[^a-z \']/im", "", $title);
			$title = preg_replace("/[\']/im", " ", $title);
			$title = explode(" ", $title);
			foreach ($title as $elem) {
				if (strlen($elem) > 3) {
					$stem = PorterStemmer::Stem($elem);
					$result[$stem] = $stem;
					if (strlen($stem) <= 3) $small[$stem] =  $stem;
				}
			}
		}
		asort($small);
	}

	function deleteNewGallery($id)
	{
		$gallery = $this->getMainGalleryInfo($id);

		if ($gallery && ($gallery['status'] == 'new' || $gallery['status'] == 'toregrab')) {
			$this->deleteGallery($id);
			return true;
		}
		return false;
	}

	function deleteGallery(int $id)
	{
		$result = true;

		$this->galleryToDeleteRss($id);

		if ($this->setStatus($id, 'delete')) {

			$sql = "DELETE FROM `sites_galleries` WHERE `gal_id` = '" . $id . "'";
			if ($this->_db->query($sql)) $result = true;

			// $sql = "DELETE FROM `galleries` WHERE `gal_id` = '".$id."'";
			// if ($this->_db->Execute($sql)) $result = true;
			$sql = "DELETE FROM `galleries_resized_to` WHERE `gal_id` = '" . $id . "'";
			if ($this->_db->query($sql)) {
				$result = true;
			}
		}

		return $result;
	}

	function galleryToDeleteRss(int $gal_id)
	{

		if ($gal_id < 0) {
			return false;
		}

		$db = DB::get();
		if (!$db) {
			$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			return false;
		}

		$sql = "SELECT 
						site_id, sites_gallery_url, use_galleries_from, digit_base_for_id
					FROM 
						sites 
					WHERE 
						site_id IN (SELECT site_id FROM sites_galleries WHERE gal_id = ?)
						OR
						use_galleries_from IN (SELECT site_id FROM sites_galleries WHERE gal_id = ?)";

		$stmt = $db->prepare($sql);

		if (!$stmt) {
			$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			return false;
		}

		$rss_list = false;
		$sql_sel = false;
		$sites_list = false;

		if (!$stmt->bind_param("ii", $gal_id, $gal_id) || !$stmt->execute()) {
			$log = new Logger(__METHOD__ . ": DB execute failed: '" . $stmt->error . "'", true);
			return false;
		}

		$site_id = false;
		$sites_gallery_url = false;
		$use_galleries_from = false;
		$digit_base_for_id = false;


		$stmt->bind_result($site_id, $sites_gallery_url, $use_galleries_from, $digit_base_for_id);

		while ($stmt->fetch()) {

			if ($use_galleries_from == 0) {
				$sql_sel[] = "SELECT " . $site_id . " AS site_id, id AS gal_local_id, gal_id, url_desc, gal_type 
									FROM site_" . $site_id . " WHERE gal_id = " . $gal_id;
			}

			$sites_list[$site_id] = [
				'site_id' => $site_id,
				'sites_gallery_url' => $sites_gallery_url,
				'use_galleries_from' => $use_galleries_from,
				'digit_base_for_id' => $digit_base_for_id,
			];
		}


		if (!$sites_list || ! $sql_sel) {
			return  true;
		}

		$site_gal_info = false;
		$sql = implode(" UNION ", $sql_sel);
		$stmt = $db->prepare($sql);

		if (!$stmt || !$stmt->execute()) {
			$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
			return false;
		}

		$gal_local_id = 0;
		$url_desc = null;
		$gal_type = null;
		$stmt->bind_result($site_id, $gal_local_id, $gal_id, $url_desc, $gal_type);

		while ($stmt->fetch()) {
			$site_gal_info[$site_id] = [
				'site_id' => $site_id,
				'gal_local_id' => $gal_local_id,
				'gal_id' => $gal_id,
				'url_desc' => $url_desc,
				'gal_type' => $gal_type,
			];
		}



		if (empty($site_gal_info)) {
			return true;
		}

		$added_on = time();

		foreach ($sites_list as $site_id => $site_info) {

			$url_rules = $site_info['sites_gallery_url'];
			$digit_base_for_id = $site_info['digit_base_for_id'];

			$use_site_id = ($site_info['use_galleries_from']) ? $site_info['use_galleries_from'] : $site_id;

			$type = $site_gal_info[$use_site_id]['gal_type'];
			$typeLower = strtolower((string)$type);
			if ($typeLower === 'movies') {
				$type = 'Movies';
			} elseif ($typeLower === 'pics') {
				$type = 'Pics';
			} elseif ($typeLower === 'gif') {
				$type = 'gif';
			} elseif ($typeLower === 'embed') {
				$type = 'embed';
			}
			$gal_global_id = $site_gal_info[$use_site_id]['gal_id'];
			$gal_local_id = $site_gal_info[$use_site_id]['gal_local_id'];

			$output_url = str_replace("#TYPE#", $type, $url_rules);

			if (isset($digit_base_for_id) && $digit_base_for_id) {
				$t_local_gal_id = base_convert($gal_local_id, 10, $digit_base_for_id);
				$t_global_gal_id = base_convert($gal_global_id, 10, $digit_base_for_id);
			} else {
				$t_local_gal_id = $gal_local_id;
				$t_global_gal_id = $gal_global_id;
			}

			$output_url = str_replace("#LOCALID#", $t_local_gal_id, $output_url);
			$output_url = str_replace("#ID#", $t_global_gal_id, $output_url);
			$output_url = str_replace("#GALNAME#", $url_desc, $output_url);

			$sql_insert[] = "(" . $gal_global_id . ", " . $site_id . ", " . $gal_local_id . ", '" . $db->real_escape_string($type ? $type : '') . "', '" . $output_url . "', " . $added_on . ")";
		}

		if (!$sql_insert) {
			return true;
		}

			$sql = "INSERT  INTO galleries_delete_rss
													(`gal_id`, `site_id`, `gal_local_id`, `gal_type`, `gal_url`, `added_on`)
													VALUES " . implode(", ", $sql_insert) . " ON DUPLICATE KEY UPDATE gal_type = VALUES(gal_type), added_on=" . $added_on . ";";

		$stmt = $db->prepare($sql);
		if (!$stmt || !$stmt->execute()) {
			$log = new Logger(__METHOD__ . ": DB execute failed: " . $db->error, true);
			echo __METHOD__ . ": DB execute failed: " . $db->error;
			return false;
		}


		return true;
	}

	function deleteFromSite($site_id, $gal_id)
	{
		$result = true;
		$gal_id = intval($gal_id);
		$site_id = intval($site_id);

		if ($gal_id < 0 || $site_id < 0) {
			return false;
		}

		$sql[] = "DELETE FROM sites_galleries WHERE gal_id = '" . $gal_id . "' AND site_id = '" . $site_id . "'";
		$sql[] = "DELETE FROM site_" . $site_id . " WHERE gal_id = '" . $gal_id . "'";
		$sql[] = "UPDATE galleries SET times_used_on_sites = times_used_on_sites - 1 WHERE gal_id = '" . $gal_id . "';";

		try {
			$this->_db->beginTransaction();

			foreach ($sql as $sql_c) {
				$this->_db->exec($sql_c);
			}

			$this->_db->commit();
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ":Удаление галлереи '" . $gal_id . "' с сайта '" . $site_id . "' провалилась: {$e->getMessage()}\n" . $sql_1 . "\n" . $sql_2, true);
			$this->_db->rollBack();
			return false;
		}

		return true;
	}



	function trashGallery($id)
	{
		return $this->setStatus($id, 'trash');
	}

	function approveGallery($id)
	{
		return $this->setStatus($id, 'OK');
	}

	function checkOriginalGalleryIdExists($gal_paysite, $insource_original_id)
	{
		$result = false;
		$gal_paysite = (int)$gal_paysite;
		$insource_original_id = (int)$insource_original_id;
		if ($gal_paysite && $insource_original_id) {
			$db = DB::get();
			if ($db) {
				$sql = "SELECT gal_id FROM galleries WHERE gal_paysite = ? AND insource_original_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("ii", $gal_paysite, $insource_original_id)) {
						if ($stmt->execute()) {
							$stmt->bind_result($result);
							$stmt->fetch();
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT params not binded: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": источник или оригинальный ID полученый от источника не указан", true);
		}


		return $result;
	}


	function clearTrash()
	{

		$db = DB::get();
		if (!$db) {
			$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			return false;
		}

		$sql = "DELETE FROM galleries WHERE gal_status = 'delete' OR gal_status = 'trash'";
		$stmt = $db->prepare($sql);

		if (!$stmt) {
			$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			return false;
		}

		return $stmt->execute() ? true : false;
	}

	function getInsertError()
	{
		return $this->error_on_insert_msg;
	}

	function addGallery(
		$url,
		int $paysiteId,
		$niche,
		$type = "New",
		$status = "new",
		$title = "",
		$desc = "",
		$md5 = false,
		int $main_gal = 0,
		$models = [],
		$embed = false,
		$images = false,
		$duration = false,
		int $insource_original_id = 0,
		$tags = false,
		int $unique_for_export_site = 0
	) {

		$result 					= false;
		$allow_to_add 				= false;
		$this->error_on_insert_msg 	= false;

		if ($insource_original_id && $original_id_exists = $this->checkOriginalGalleryIdExists($paysiteId, $insource_original_id)) {
			$this->error_on_insert_msg = "ID #" . $insource_original_id . " для источника #" . $paysiteId . " уже существует, ID существующей галеры #" . $original_id_exists . ";";
			return false;
		}

		if (($url && preg_match("#^http[s]{0,1}:\/\/(.*)#im", $url)) || ($url != "" && $md5 && $status == 'newzip')) {
			$allow_to_add = true;
		}

		// echo "Add gallery niche: '".$niche."'<br>";
		// echo "{$allow_to_add }|{$paysiteId}|{$status}|{$niche}|{$type}<br>";

		if (
			!$allow_to_add ||
			!$paysiteId ||
			!preg_match('/^(newzip|zip|zipupload|new|grabbed|thumbs|uploaded|tagged|toregrab|OK|trash|delete|error)$/', $status) ||
			!preg_match('/^(Gay|Straight|Shemale)$/', $niche) ||
			!preg_match('/^(New|Pics|Movies|gif|embed)$/', $type)
		) {
			$this->error_on_insert_msg = "Не разрешено добавление - неверный урл или входящие параметры";
			echo __METHOD__ . " :: Ошибка добавления галлереи - неверные входящие параметры. PyasiteId#{$paysiteId}, Niche: {$niche}, Type: {$type}<br>";
			$log = new Logger(__METHOD__ . " :: Ошибка добавления галлереи - неверные входящие параметры. PyasiteId#" . $paysiteId . ", Niche: '" . $niche . "', Type: '" . $type . "'", true);

			return false;
		}


		$md5 = (!$md5) ? md5($url) : $md5;

		if (strlen($url) > 254) {
			$is_long_url = 1;
			$long_url = $url;
			$url = "long url";
		} else {
			$is_long_url = 0;
			$long_url = false;
		}

		$unique_for_export_site = ($unique_for_export_site > 0) ? $unique_for_export_site : 0;

		$main_gal 			= (int)$main_gal;
		if (function_exists('upload_trace')) {
			upload_trace('addGallery: before sanitize', array(
				'paysite_id' => $paysiteId,
				'type' => $type,
				'status' => $status,
				'title_length' => strlen((string)$title),
				'desc_length' => strlen((string)$desc),
				'url_length' => strlen((string)$url)
			));
		}
		$title 				= sanitize_non_utf($title);
		$random_number 		= rand(1, 1000);
		$gal_thumb 			= 0;
		$status_change_time = 0;
		$embed_flag 		= $embed ? 1 : 0;
		$duration 			= (int)$duration ? (int)$duration : 0;
		if (function_exists('upload_trace')) {
			upload_trace('addGallery: after sanitize', array(
				'title_length' => strlen((string)$title),
				'desc_length' => strlen((string)$desc)
			));
		}

		try {
			$sql = 'INSERT INTO `galleries` 
					(
						`gal_source`, 
						`gal_md5`, 
						`gal_paysite`, 
						`gal_type`, 
						`gal_status`, 
						`gal_added`, 
						`gal_niche`, 
						`gal_title`, 
						`gal_description`, 
						`main_gal`, 
						`random_number`, 
						`embed_flag`, 
						`embed`, 
						`gal_content_count`, 
						`insource_original_id`, 
						`is_long_url`, 
						`gal_thumb`, 
						`status_change_time`,
						`unique_for_export_site`
					)
					VALUES (
						:gal_url, 
						:gal_md5, 
						:paysite_id, 
						:gal_type, 
						:gal_status, 
						:cur_date, 
						:niche, 
						:title, 
						:gal_desc, 
						:main_gal, 
						:random_number, 
						:embed_flag, 
						:embed, 
						:duration, 
						:insource_original_id, 
						:is_long_url, 
						:gal_thumb, 
						:status_change_time,
						:unique_for_export_site
					);';
			if (function_exists('upload_trace')) {
				upload_trace('addGallery: before prepare');
			}
			$stmt = $this->_db->prepare($sql);
			if (function_exists('upload_trace')) {
				upload_trace('addGallery: after prepare');
			}

			if (function_exists('upload_trace')) {
				upload_trace('addGallery: before execute');
			}
			$executed = $stmt->execute(
				array(
					':gal_url' => $url,
					':gal_md5' => $md5,
					':paysite_id' => $paysiteId,
					':gal_type' => $type,
					':gal_status' => $status,
					':cur_date' => date("Y-m-d"),
					':niche' => $niche,
					':title' => $title,
					':gal_desc' => $desc,
					':main_gal' => $main_gal,
					':random_number' => $random_number,
					':embed_flag' => $embed_flag,
					':embed' => $embed,
					':duration' => $duration,
					':insource_original_id' => $insource_original_id,
					':is_long_url' => $is_long_url,
					':gal_thumb' => $gal_thumb,
					':status_change_time' => $status_change_time,
					':unique_for_export_site' => $unique_for_export_site,
				)
			);
			if (function_exists('upload_trace')) {
				upload_trace('addGallery: after execute', array('executed' => $executed));
			}
		} catch (PDOException $e) {
			if (function_exists('upload_trace')) {
				upload_trace('addGallery: PDOException', array('message' => $e->getMessage()));
			}
			$this->error_on_insert_msg = strpos($e->getMessage(), "Duplicate entry") ? 'Галера уже существует' : $e->getMessage();
			return false;
		}

		if ($executed && $embed) {
			$gal_id = $this->_db->lastInsertId();
			$img_worker = new Images();
			$img_worker->insertImagesArray($gal_id, $images);
		}

		if ($executed === false) {
			if (strpos($this->_db->errorInfo(), "Duplicate entry") !== false) {
				echo 'Галера: \'' . $url . '\' уже есть в базе<BR>';
			} else {
				echo __METHOD__ . ' :: Ошибка добавления в базу данных: ' . $this->_db->errorInfo() . '<BR>';
				$log = new Logger(__METHOD__ . " :: Ошибка добавления в базу данных: " . $this->_db->errorInfo(), true);
			}
			return false;
		}

		$gal_id = $this->_db->lastInsertId();
		if (function_exists('upload_trace')) {
			upload_trace('addGallery: lastInsertId', array('gallery_id' => $gal_id));
		}

		if ($is_long_url) {
			$this->insertLongUrlForGallery($gal_id, $long_url, $md5);
		}

		if ($models) {
			$model_worker = new CModels($this->_db);
			foreach ($models as $model_name) {

				$model_name = trim($model_name);

				if (strlen($model_name) < 2) {
					continue;
				}

				$model_id = $model_worker->getModelByName($model_name, $niche);

				if ($model_id) {
					$this->addModel($gal_id, $model_id);
					$stop_words[] = strtolower($model_name);
				} elseif ($this->addModelsFromImport === true) {
					$sex = ($niche == 'Gay') ? 'male' : (($niche == 'Straight') ? 'female' : 'shemale');
					$model_id = $model_worker->addModel($model_name, $sex);
					if ($model_id) {
						$this->addModel($gal_id, $model_id);
					}
					$stop_words[] = strtolower($model_name);
				} else {
					// сделать здесь очередь моделей
					if ($tags) {
						$tags[] = $model_name;
					} else {
						$tags = [$model_name];
					}
				}
			}
		}

		if ($this->isAllowedTagsFromTitle === true) {
			$title_tags = cutTitleToTagCandidates($title);

			if ($title_tags) {
				$tags = ($tags) ? array_merge($tags, $title_tags) : $title_tags;
			}
		}


		if ($tags && is_array($tags)) {
			foreach ($tags as $new_tag) {
				if (preg_match("#([a-z0-9\s]){1,60}#im", $new_tag)) {
					$this->addTagToGalleriesImportQuery($gal_id, $new_tag);
				}
			}
		}

		if ($status == 'new') {
			$this->addToQuery($gal_id);
		}

		return  $gal_id;
	}


	public function manualTitleToTagsAndProcessing(int $gal_id)
	{
		$result = false;

		$gal_info = $this->getMainGalleryInfo($gal_id);

		if (!$gal_info) {
			return false;
		}

		$title = $gal_info['title'];

		if ($title) {
			$this->queryTitleAsTagsAndModels($gal_id, $title);
			$this->processGalleriesTagsImport($gal_id);
			$result = true;
		}

		return $result;
	}

	private function insertLongUrlForGallery(int $gal_id, $long_url, $gal_md5)
	{
		$result = true;

		if ($gal_id <= 0 || !$long_url || !$gal_md5) {
			$log = new Logger(__METHOD__ . ": GID#" . $gal_id . ", URL: '" . $long_url . "', No DB connect", true);
			return false;
		}

		$added_on = time();
		$sql = "INSERT INTO galleries_urls
				(gal_id, gal_url, gal_md5)
				VALUES (:gal_id, :gal_url, :gal_md5)";
		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute([
				':gal_id' => $gal_id,
				':long_url' => $long_url,
				':gal_md5' => $gal_md5
			]);
			$result = $this->_db->lastInsertId();
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": GID#" . $gal_id . ", URL: '" . $long_url . "', DB Error '" . $e->getMessage() . "'", true);
		}

		return $result;
	}

	public function getLongUrlByGalId(int $gal_id)
	{
		$result = true;
		$db = DB::get();

		if ($gal_id <= 0) {
			return false;
		}

		if (!$db) {
			$log = new Logger(__METHOD__ . ": GID#" . $gal_id . ", No DB connect", true);
			return false;
		}

		$added_on = time();
		$sql = "SELECT gal_url FROM galleries_urls
				WHERE gal_id = ?";

		$stmt = $db->prepare($sql);
		if ($stmt) {
			if ($stmt->bind_param("i", $gal_id)) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": GID#" . $gal_id . ", No execute '" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": GID#" . $gal_id . ", No BIND '" . $stmt->error . "'", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": GID#" . $gal_id . ", No STMT '" . $db->error . "'", true);
		}

		return $result;
	}


	private function queryTitleAsTagsAndModels($gal_id, $title)
	{
		$result = false;
		$title_tags = cutTitleToTagCandidates($title);

		if ($title_tags) {
			if (is_array($title_tags)) {
				foreach ($title_tags as $new_tag) {
					if (!preg_match("#([a-z0-9\s]){1,60}#im", $new_tag)) {
						$log = new Logger(__METHOD__ . ": GID:" . $gal_id . ", тег '" . $new_tag . "' нельзя использовать", true);
					} else {
						$this->addTagToGalleriesImportQuery($gal_id, $new_tag);
					}
				}
			}
		}
		return $result;
	}

	private function addTagToGalleriesImportQuery(int $gal_id, $tag_name)
	{
		$result = false;

		if ($gal_id > 0) {
			$tag_name = trim($tag_name);
			$tag_name = strtolower($tag_name);
			$tag_name = preg_replace("#^(^[a-z0-9-\s])$#im", "", $tag_name);

			if (strlen($tag_name) > 2) {
				$added_on = time();
				$sql = "INSERT INTO galleries_tags_import
						(gal_id, tag_name, added_on)
						VALUES (:gal_id, :tag_name, :added_on)";

				try {
					$stmt = $this->_db->prepare($sql);
					$stmt->execute(['gal_id' => $gal_id, 'tag_name' => $tag_name, 'added_on' => $added_on]);
					$result = $this->_db->lastInsertId();
				} catch (PDOException $e) {
					$log = new Logger(__METHOD__ . ": DB error: '" . $e->getMessage() . "'", true);
				}
			}
		}

		return $result;
	}


	private function deleteTagFromGalleriesImportQuery(int $id)
	{
		$result = false;

		if ($id > 0) {
			$db = DB::get();
			if ($db) {
				$added_on = time();
				$sql = "DELETE FROM galleries_tags_import
							WHERE id = ?";

				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $id)) {
						if ($stmt->execute()) {
							$result = true;
						}
					} else {
						$log = new Logger(__METHOD__ . ": No BIND '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No STMT '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}

		return $result;
	}

	private function deleteGalleryTagsFromGalleriesImportQuery(int $gal_id)
	{
		$result = false;

		if ($gal_id > 0) {
			$db = DB::get();
			if ($db) {
				$added_on = time();
				$sql = "DELETE FROM galleries_tags_import
							WHERE gal_id = ?";

				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$result = true;
						}
					} else {
						$log = new Logger(__METHOD__ . ": No BIND '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No STMT '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}

		return $result;
	}


	private function processGalleriesTagsImport($gal_id)
	{
		$result = false;

		$gallery_queried_tags = $this->getGalleriesImportedTags($gal_id);

		if ($gallery_queried_tags) {
			$tags_worker = new Tags($this->_db);
			$model_worker = new CModels($this->_db);
			foreach ($gallery_queried_tags as $tag_query_id => $tag_name) {
				// проверяем теги на синономы, есть синоним добавляем тег
				$tag_id = $tags_worker->getTagIdBySynonym($tag_name);
				if ($tag_id) {
					// добавить тег
					$this->insertTag($gal_id, $tag_id);
				} else {
					$model_id = false;
					if (strpos(" ", $tag_name)) {
						$model_id = $model_worker->getModelByName($tag_name);
					}
					if ($model_id) {
						$this->insertModel($gal_id, $model_id);
					} else {
						// нет синонима, проверяем черный список, есть черный список - пропускаем, тег удаляем
						$word_blacklisted = $tags_worker->isSynonymBlacklisted($tag_name);
						if (!$word_blacklisted) {
							// нет в списке, добавляем тег в таблицу кандидатов (tags_candidates) и результирующий
							$candidate_id = $tags_worker->isCandidateExists($tag_name);
							if (!$candidate_id) {
								$candidate_id = $tags_worker->addTagCandidate($tag_name);
							}
							// ID кандидата с ID галеры добавляем в tags_candidates_galleries
							$tags_worker->addTagCandidateGallery($gal_id, $candidate_id);
						}
					}
				}
				// удаляем из очереди галерных тегов
				$this->deleteGalleryTagsFromGalleriesImportQuery($tag_query_id);
			}
		}



		return $result;
	}

	private function getGalleriesImportedTags($gal_id)
	{
		$result = false;

		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$db = DB::get();
			if ($db) {
				$sql = "SELECT id, tag_name 
						FROM galleries_tags_import 
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						$id = false;
						$tag_name = false;

						$stmt->execute();
						$stmt->bind_result($id, $tag_name);

						while ($stmt->fetch()) {
							$result[$id] = $tag_name;
						}
					} else {
						$log = new Logger(__METHOD__ . ": Stmt error '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": Stmt error '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connection", true);
			}
		}

		return $result;
	}


	private function insertNewVideoGallery(int $id, $md5, $source = false, $status = 'new')
	{
		$result = false;
		$gallery = $this->getMainGalleryInfo($id);

		if ($gallery) {
			if ($source === false) $source = $gallery['source'];
			$lastInsertId = $this->addGallery($source, $gallery['paysite']['id'], $gallery['niche'], 'Movies', $status, $gallery['title'], $gallery['description'], $md5, $id);
			if ($lastInsertId) $result = $lastInsertId;
			else $log = new Logger(__METHOD__ . ":Ошибка добавления новой видео галлереи из сборного ZIP. Скорее всего файл уже существует", true);
		}
		return $result;
	}

	function setProcessingGallery($id, $status, $prevStatus)
	{
		$result = false;
		$id = (int)$id;

		if (
			$id && preg_match('/^(unzipping|fetching|fetching_fail|fetched|thumbing|thumbed|video_screening|pics_resizing|pics_resized|video_converting|video_converted|video_merging|video_merged|error)$/', $status)
			&& preg_match('/^(zip|newzip|unzipping|unzip_fail|zipupload|zipupload_fail|new|fetching|fetching_fail|fetched|video_screening|screen_fail|screened|video_converting|video_fail|video_converted|thumbing|thumbed|pics_resizing|pics_resized|grab_fail|grabbed|thumbs_fail|thumbs|upload_fail|uploaded|tagged|toregrab|OK|trash|delete)$/im', $prevStatus)
		) {
			$db = DB::get();
			if ($db) {
				$added_on = time();

				$sql = 'INSERT INTO `processing_galleries` 
							(`gal_id`, `process`, `prev_status`, `added`)
			 			VALUES 
			 				(?, ?, ?, ?);';
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("issi", $id, $status, $prevStatus, $added_on)) {
						if (!$stmt->execute()) {
							$log = new Logger(__METHOD__ . ": Галера не добавлена в таблицу processing_galleries: '" . $stmt->error . "'", true);
						} else $result = true;
					} else {
						$log = new Logger(__METHOD__ . ": STMT params not binded: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ":Ошибка добавления галлереи в - processing_galleries неверные входящие параметры", true);
		}
		return  $result;
	}

	private function removeProcessingGallery($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id) {
			$sql = "DELETE FROM `processing_galleries` WHERE `gal_id` = ?";
			$db = DB::get();
			if ($db) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					$stmt->bind_param("i", $gal_id);
					if (!$stmt->execute()) {
						$log = new Logger(__METHOD__ . ": Галера не удалена из таблицы processing_galleries: '" . $stmt->error . "'", true);
					} else $result = true;
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: '" . $db->error, true) . "'";
				}
			} else {
				$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			}
		}
		return $result;
	}

	public function getGalleryStatus(int $id)
	{
		if ($id < 1) {
			return null;
		}

		$result = false;

		$db = DB::get();

		if ($db) {
			$sql = "SELECT gal_status FROM galleries WHERE gal_id = '" . $id . "'";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
		}
		return $result;
	}

	private function galleryCroppedStatus(int $id)
	{
		$result = false;

		if ($id < 1) {
			return null;
		}

		$sql = "SELECT crop_flag FROM galleries WHERE gal_id = '" . $id . "'";
		$rs = $this->_db->query($sql);
		if ($rs === false) {
			print __METHOD__ . ': error grabbing: ' . $this->_db->errorInfo() . '<BR>';
			$log = new Logger(__METHOD__ . ": Ошибка выборки crop_flag галлереи " . $id . " из базы данных: " . $this->_db->errorInfo(), true);
		} else {
			$status = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($status) $result = $status[0]['crop_flag'];
		}

		return $result;
	}

	private function getGalleryPaysite($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		$gal_paysite = 0;
		if ($gal_id) {
			$sql = "SELECT gal_paysite FROM galleries WHERE gal_id = ?";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $gal_id)) {
							if ($stmt->execute()) {
								$stmt->bind_result($gal_paysite);

								if ($stmt->fetch()) {
									$result = $gal_paysite;
								} else {
									$log = new Logger(__METHOD__ . ": STMT fetch failed : " . $stmt->error, true);
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}

	private function getGalleryMD5($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		$gal_md5 = null;

		if ($gal_id) {
			$sql = "SELECT gal_md5 FROM galleries WHERE gal_id = ?";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $gal_id)) {
							if ($stmt->execute()) {
								$stmt->bind_result($gal_md5);

								if ($stmt->fetch()) {
									$result = $gal_md5;
								} else {
									$log = new Logger(__METHOD__ . ": STMT fetch failed : " . $stmt->error, true);
								}
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}

	public function resetToNew($id)
	{
		$this->setStatus($id, 'new');
	}

	public function resetToFetched($id)
	{
		$status = $this->getGalleryStatus($id);
		if ($status == 'video_fail' || $status == 'screen_fail') {
			if ($this->setStatus($id, 'fetched')) {
				$this->removeGalleryImages($id);
				$this->removeGalleryTags($id);
				$this->removeGalleryModels($id);
				$this->removeFromQuery($id);
				$this->addToQuery($id);
				return true;
			}
		}
		//$this->addToQuery($id);
		return false;
	}

	public function setStatus(int $id, $status)
	{ /* общий метод работы со статусами - изменяет статус галеры, устанавливает время изменения. !!! нет фиксации ошибки! */
		$result = false;

		if (
			$id > 0
			&& preg_match('/^(zip|newzip|unzipping|unzip_fail|zipupload|screened|zipupload_fail|new|fetching|fetching_fail|fetched|video_screening|screen_fail|video_converting|video_fail|video_converted|thumbing|thumbed|pics_resizing|pics_resized|grab_fail|grabbed|thumbs_fail|thumbs|upload_fail|uploaded|tagged|toregrab|OK|trash|delete|to_merge)$/im', $status)
			&& $prevStatus = $this->getGalleryStatus($id)
		) {



			$sql = "UPDATE galleries 
	      		  	SET gal_status = :status, status_change_time = :status_change_time";
			$sql .= ($status == "uploaded") ? ", crop_flag = '1' " : " ";
			$sql .= " WHERE gal_id = :gal_id";

			//   var_dump($sql);

			try {

				$status_change_time = time();

				$stmt = $this->_db->prepare($sql);
				$stmt->execute([
					'status' => $status,
					'status_change_time' => $status_change_time,
					'gal_id' => $id
				]);
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": DB execute failed: {$id}, to Status: {$status}, Error: {$e->getMessage()}", true);
				return false;
			}


			$sites = array();

			if (in_array($status, ['zipupload', 'unzipping', 'fetching', 'thumbing', 'pics_resizing', 'video_converting', 'video_screening'])) {

				if ($this->setProcessingGallery($id, $status, $prevStatus)) $result = true;
			} elseif (in_array($status, ['unzip_fail', 'zip', 'zipupload_fail', 'zipupload', 'fetching_fail', 'fetched', 'thumbed', 'pics_resized', 'screen_fail', 'screened', 'video_converted', 'video_fail', 'grab_fail', 'grabbed', 'thumbs_fail', 'thumbs', 'upload_fail', 'uploaded'])) {
				// удаление изображений из базы в случае ошибки закачки и граба и анзипа. граб и фетч дублируют те же действия
				if ($status == 'unzip_fail' || $status == 'grab_fail' || $status == 'fetching_fail' ||  $status == 'screen_fail') {
					// echo "Remove gallery images start<br>";
					$this->removeGalleryImages($id);
					// echo "Remove gallery images finish";
				}
				if ($this->removeProcessingGallery($id)) $result = true;
			} elseif (($status == 'fetched' || $status == 'new') && (preg_match('/^(ok|fetched|fetching_fail|thumbing_fail)$/', $prevStatus))) {

				if ($prevStatus != 'fetching_fail' && $prevStatus != 'thumbing_fail') {
					if ($status == 'new') $this->removeGalleryImages($id);
					$this->removeGalleryTags($id);
					$this->removeGalleryModels($id);
					if ($prevStatus == 'OK') {
						$sites = $this->galleryPostedTo($id);
						$this->removeGalleryFromSites($id, $sites);
					}
					$this->removeFromQuery($id);
					$this->removeGalleryManualRecrop($id);
					$this->removeGalleryCropHistory($id);
					$this->removeGallerySkeepCrop($id);
				}

				$result =  true;
			} elseif ($status == 'delete') {
				// перенести в очередь удалений
				// галера в треш, после удаления кешей и прочего, галера в статус 'delete'

				if ($prevStatus == 'delete') {
					$this->removeGalleryFromDb($id);
					return true;
				}

				if ($prevStatus == 'OK') {
					$sites = $this->galleryPostedTo($id);

					// старый вариант удлялки
					$tags = $this->getTags($id);
					$models = $this->getModels($id);
					$source_id = $this->getGalleryPaysite($id);

					$cache = new CacheRebuilder($this->_db);
					$cache->removeGallery($id, $source_id, $sites, $tags, $models);
				}
				// предыдущий статус передается для того чтобы получить имя файла
				$this->deleteVideoFile($id, $prevStatus);
				$this->deleteVideoPreview($id);

				if ($prevStatus == 'OK' || $prevStatus == 'uploaded') {
					$this->deleteVideoFromGalleriesVideos($id);
				}
				$this->removeGalleryImages($id);
				$this->removeGalleryTags($id);
				$this->removeGalleryModels($id);
				$this->removeGalleryTitle($id);
				$this->deleteGalleryTagsFromGalleriesImportQuery($id);

				if ($prevStatus == 'OK' && $sites) {
					$start = get_time();
					$this->removeGalleryFromSites($id, $sites);
					$this->queryCacheRemoval($id);
					$finish = get_time();
					$exec = $finish - $start;
					echo "removeGalleryFromSites: " . $exec . "<br>";
				}

				$this->removeFromQuery($id);
				$this->removeFromMergingQuery($id);
				$this->removeGalleryManualRecrop($id);
				$this->removeGalleryCropHistory($id);
				$this->removeGallerySkeepCrop($id);

				$result =  true;
			} else {
				$result = $stmt->rowCount();
			}

			$error = new Logger(__METHOD__ . ":galId:" . $id . ", смена статуса, статус установлен в: " . $status);
		}

		return $result;
	}


	private function queryCacheRemoval($gal_id)
	{
		$result = false;
		$db = DB::get();

		if ($db) {

			$sql = "INSERT INTO sites_cache_query (site_id, cache_server_id, gal_id, gal_type, item_type, change_type, item_id, added_on, updated_on, error, error_msg)
				VALUES ";
			$site_id = 0;
			$cache_server_id = 0;
			$gal_id = (int)$gal_id;
			$gal_type = 'none';
			$item_type = 'gallery';
			$change_type = 'removed';
			$item_id = 0;
			$added_on = time();
			$updated_on = $added_on;
			$error = 0;
			$error_msg = "";
			$sql_array = false;

			CachingServers::reset();
			while (CachingServers::next()) {
				$cache_server_id = CachingServers::currentID();
				$cache_server_id = (int)$cache_server_id;
				$sql_array[] = "(" . $site_id . ", " . $cache_server_id . ", " . $gal_id . ", '" . $gal_type . "', '" . $item_type . "', '" . $change_type . "', " . $item_id . ", " . $added_on . ", " . $updated_on . ", " . $error . ", '" . $error_msg . "')";
			}

			if ($sql_array) {
				$sql = $sql . implode(",", $sql_array);
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	public function getVideoFileInfo($id)
	{
		$result = false;
		$file = $this->getVideoFilePath($id);
		if ($file) {
			if (is_file($file)) {
				$video_u = new VideoUtils('temp');
				$result['bitrate'] = $video_u->GetBitrate($file);
				$result['filename'] = $file;
				$result['cdn_synced'] = $this->isVideoCdnSynced($id);
				$file_size = $this->getVideoSize($id);

				if ($file_size == false) {
					$file_size = $this->updateVideoFileSize($id);
				}
				$result['size'] = $file_size  / pow(1024, 2); // в Мб
			}
		} else {
			$log = new Logger(__METHOD__ . ": Невозможно получить папку видео файла галлереи #" . $id, true);
		}
		return $result;
	}

	private function deleteVideoFile($id, $status = false)
	{
		$result = false;
		$file = $this->getVideoFilePath($id, $status);
		if ($file) {
			if (unlink($file)) {
				$log = new Logger(__METHOD__ . ": Удалено видео:  #" . $file . ", Галера #" . $id);
			} else {
				$log = new Logger(__METHOD__ . ": Ошибка удаления видео:  #" . $file . ", Галера #" . $id, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Не найден файл видео, галлереи #" . $id, true);
		}
	}

	private function galleriesInSatus($status, $type = false, $single = false)
	{
		$result = false;
		if (preg_match('/^(zip|newzip|unzipping|unzip_fail|zipupload|screened|zipupload_fail|new|fetching|fetched|video_screening|screen_fail|video_converting|video_fail|video_converted|thumbing|thumbed|pics_resizing|pics_resized|grab_fail|grabbed|thumbs_fail|thumbs|upload_fail|uploaded|tagged|toregrab|OK|trash|delete|to_merge)$/im', $status)) {
			$sql = "select * from galleries where gal_status = '" . $status . "'";
			if ($type && preg_match('/^(pics|movies|embed|gif)$/im', $type)) $sql .= " and gal_type = '" . $type . "'";
			if ($single) $sql .= " limit 1";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery) {
					if ($single) {
						$result['id'] = $gallery[0]['gal_id'];
						$result['type'] = $gallery[0]['gal_type'];
					} else {
						foreach ($gallery as $galId) {
							$result[] = $galId['gal_id'];
						}
					}
				} else {
					$error = new Logger(__METHOD__ . "(" . $status . "," . $single . "): Выборка '" . $sql . "' пустая", true);
				}
			} else {
				$error = new Logger(__METHOD__ . "(" . $status . "," . $single . "): Ошибка выборки из БД: '" . $this->_db->errorInfo(), true);
			}
		}
		return $result;
	}


	function insertImage($gal_id, $fileMd5 = false)
	{ // пустой инсерт без доп параметров
		$result = false;
		$gal_id = (int)$gal_id;
		$db = DB::get();
		$lastInsertId = false;
		if ($db) {
			if ($gal_id) {
				if ($this->getGalleryMD5($gal_id)) {
					$sql = 'INSERT INTO `galleries_pix` 
								(`gal_id`, `rss_flag`, `image`) 
					 		VALUES 
					 			(?, 0, "");';

					$stmt = $db->prepare($sql);

					if ($stmt) {
						if ($stmt->bind_param("i", $gal_id)) {
							if ($stmt->execute()) {
								$lastInsertId = $db->insert_id;
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}

					if ($lastInsertId) {

						$result =  $lastInsertId;
						if ($fileMd5) {
							$sql = 'INSERT INTO `galleries_source_pics` 
										(`gal_id`, `image_id`, `gal_pics_md5`)
									VALUES (?, ?, ?);';
							if ($sql) {
								$stmt = $db->prepare($sql);
								if ($stmt) {
									if ($stmt->bind_param("iis", $gal_id, $lastInsertId, $fileMd5)) {
										if (!$stmt->execute()) {
											$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
										}
									} else {
										$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
									}
								} else {
									$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
								}
							} else {
								$log = new Logger(__METHOD__ . ": SQL string is empty", true);
							}
						}
					}
				}
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	function insertModelImage($id, $md5, $layout)
	{ // пустой инсерт без доп параметров
		$result = false;
		if (!$layout || !preg_match("#^(horiz|vertic)$#", $layout)) {
			$log = new Logger(__METHOD__ . " неверно указан лэйаут для изображения модели");
			return false;
		}
		$id = intval($id);
		if ($id) {
			$sql = 'INSERT INTO `models_images` (`model_id`, `image_md5`, `added_on`, `layout`) ';
			$sql .= ' VALUES (';
			$sql .= '\'' . $id . '\',';
			$sql .= '\'' . $md5 . '\',';
			$sql .= '\'' . time() . '\',';
			$sql .= '\'' . $layout . '\'';
			$sql .= ');';
			if ($this->_db->query($sql) === false) {
				print 'error inserting: ' . $this->_db->errorInfo() . '<BR>';
				$log = new Logger(__METHOD__ . ":error inserting: " . $this->_db->errorInfo(), true);
			} else {
				$result = $this->_db->lastInsertId();
			}
		}
		return $result;
	}

	private function setImageUploaded($image_id, $image)
	{
		$result = false;
		$image_id = intval($image_id);

		if ($image_id && $image) {
			$sql = "UPDATE galleries_pix SET image = ?, rss_flag = '1'
					WHERE image_id = ?";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("si", $image, $image_id)) {
							if (!$stmt->execute()) {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							} else $result = true;
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}


		return $result;
	}

	private function setModelImageUploaded($id)
	{
		$result = false;
		$id = intval($id);
		if ($id) {
			$sql = "UPDATE models_images SET status = 'uploaded' WHERE image_id = '" . $id . "'";
			if ($this->_db->query($sql)) {
				$result = true;
			} else {
				$log = new Logger(__METHOD__ . ":Статус картинки для модели не изменен: Image #" . $id, true);
			}
		}
		return $result;
	}

	public function setModelImageCropped($id)
	{
		$result = false;
		$id = intval($id);
		// $this->_db->debug = true;
		if ($id) {
			$sql = "UPDATE models_images SET status = 'cropped' WHERE image_id = '" . $id . "'";
			if ($this->_db->query($sql)) {
				$result = true;
			} else {
				$log = new Logger(__METHOD__ . ":Статус картинки для модели не изменен: Image #" . $id, true);
			}
		}
		return $result;
	}
	private function setContentType($id, $type)
	{
		$result = false;
		$id = intval($id);
		if ($id && preg_match('/^(New|Pics|Movies|gif|embed)$/im', $type)) {
			$db = DB::get();
			if ($db) {
				$sql = "UPDATE galleries SET gal_type = '" . $type . "' WHERE gal_id = '" . $id . "'";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			}
		}
		if (!$result) $log = new Logger(__METHOD__ . ":Тип контента не изменен GID:" . $id, true);
		return $result;
	}

	private function updateImagesCount($id, $pics)
	{
		$id = intval($id);
		$pics = intval($pics);
		$sql = "UPDATE galleries SET gal_content_count = '" . $pics . "' WHERE gal_id = '" . $id . "'";
		if ($this->_db->query($sql)) {
			$result = true;
		} else {
			$log = new Logger(__METHOD__ . ":updateImagesCount не проапдейчена, ID:" . $id, true);
		}
	}

	private function setMainGallery($id, $mainId)
	{
		$id = intval($id);
		$mainId = intval($mainId);
		$sql = "UPDATE galleries SET main_gal = '" . $mainId . "' WHERE gal_id = '" . $id . "'";
		if ($this->_db->query($sql)) {
			$result = true;
		} else {
			$log = new Logger(__METHOD__ . ":updateImagesCount не проапдейчена, ID:" . $id, true);
		}
	}

	private function updateDuration($gal_id, $duration)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		$duration = (int)$duration;
		$sql = "UPDATE galleries SET gal_content_count = ? WHERE gal_id = ?";
		$db = DB::get();
		if ($db) {
			$stmt = $db->prepare($sql);
			if ($stmt) {
				$stmt->bind_param("ii", $duration, $gal_id);
				if ($stmt->execute()) {
					$result = true;
				} else {
					$log = new Logger(__METHOD__ . ": Длительность видео не проапдейчена, GID:" . $gal_id, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
		}

		return $result;
	}

	private function checkImageMd5($md5, $model = false)
	{
		$result = false;

		if ($model) $sql = "SELECT image_id FROM models_images WHERE image_md5 = ?";
		else $sql = "SELECT image_id FROM galleries_source_pics WHERE gal_pics_md5 = ?";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("s", $md5)) {
						if ($stmt->execute()) {
							$image_id = 0;
							$stmt->bind_result($image_id);

							if ($stmt->fetch()) {
								$result = $image_id;
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return $result;
	}

	public function getModelImage($id)
	{
		return $this->getImage($id, true);
	}

	public function getImage($id, $model = false, $check_main_thumb = false)
	{
		$result = false;
		$id = intval($id);
		$model_id = 0;

		$db = DB::get();

		if ($db) {
			if ($model) {
				$sql = "SELECT model_id 
						FROM models_images 
						WHERE image_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $id)) {
						if ($stmt->execute()) {
							$stmt->bind_result($model_id);
							if ($stmt->fetch()) {
								$result = "/models/original/" . $model_id . "/" . $id . ".jpg";
							} else {
								$log = new Logger(__METHOD__ . " изобразжение #" . $id . " не найдено, STMT fetch failed : " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$sql = "SELECT image";
				if ($check_main_thumb) {
					$sql .= ", galleries_pix.gal_id, gal_thumb";
				}
				$sql .= " FROM galleries_pix ";
				if ($check_main_thumb) {
					$sql .= " LEFT JOIN galleries ON galleries_pix.gal_id = galleries.gal_id";
				}
				$sql .= " WHERE image_id = ?";
				// var_dump($sql, $id);
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $id)) {
						if ($stmt->execute()) {

							$image = null;
							$gal_id = 0;
							$gal_thumb = 0;

							if ($check_main_thumb) {

								$stmt->bind_result($image, $gal_id, $gal_thumb);
								if ($stmt->fetch()) {
									$result['gal_id'] = $gal_id;
									$result['image'] = $image;
									$result['gal_thumb'] = $gal_thumb;
								}
							} else {
								$stmt->bind_result($image);
								if ($stmt->fetch()) {
									$result = $image;
								}
							}

							if (!$result) {
								$log = new Logger(__METHOD__ . " изобразжение #" . $id . " не найдено, STMT fetch failed : " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return $result;
	}

	public function getAllImagesWithCropInfo($id)
	{
		$result = false;
		$id = intval($id);
		// $this->_db->debug = true;
		$sql = "SELECT galleries_pix.image_id, galleries_pix.image, galleries_pix.ratio_w_h, user_id
				FROM galleries_pix 
				left join scr_manual_crop_history on 
				scr_manual_crop_history.image_id = galleries_pix.image_id
				WHERE galleries_pix.gal_id = '" . $id . "'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			if ($images = $rs->fetchAll(\PDO::FETCH_ASSOC)) {
				foreach ($images as $image) {
					$result[$image['image_id']]['id'] = $image['image'];
					$result[$image['image_id']]['user_id'] = $image['user_id'];
					$result[$image['image_id']]['image'] = $image['image'];
					$result[$image['image_id']]['ratio'] = $image['ratio_w_h'];
				}
			} else {
				$log = new Logger(__METHOD__ . " изобразжения в галере #" . $id . " не найдено", true);
			}
		}
		//echo "Get All Images #".$id."<br>";
		//var_dump($result);
		return $result;
	}

	public function getAllImages(int $gal_id)
	{

		$result = [];

		$db = DB::get();

		if (!$db) {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
			return false;
		}

		try {
			$sql = "SELECT image_id, image FROM galleries_pix WHERE gal_id = ?";

			$stmt = $db->prepare($sql);
			$stmt->bind_param("i", $gal_id);
			$stmt->execute();

			$image_id = false;
			$image = false;
			$stmt->bind_result($image_id, $image);

			while ($stmt->fetch()) {
				$result[(int)$image_id] = $image;
			}
		} catch (Throwable $e) {
			$log = new Logger(__METHOD__ . ": " . $e->getMessage(), true);
			return false;
		} finally {
			if (isset($stmt) && $stmt instanceof mysqli_stmt) {
				$stmt->close();
			}
		}

		return $result;
	}

	public function getImagesRatio($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;


		$db = DB::get();
		if ($db) {
			$sql = "SELECT image_id, ratio_w_h 
					FROM galleries_pix 
					WHERE gal_id = ?";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->bind_param("i", $gal_id)) {
					if ($stmt->execute()) {
						$image_id = false;
						$ratio_w_h = false;
						$stmt->bind_result($image_id, $ratio_w_h);
						while ($stmt->fetch()) {
							$result[$image_id] = $ratio_w_h;
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT bind param failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	public function getPossibleMergeMovies(int $id)
	{
		$result = false;

		if ($id) {
			$sql = "SELECT gal_id FROM galleries WHERE main_gal = '" . $id . "' AND gal_type = 'Movies'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				if ($galleries = $rs->fetchAll(\PDO::FETCH_ASSOC)) {
					foreach ($galleries as $gallery) {
						$result[] = $gallery['gal_id'];
					}
				}
			}
		}
		return $result;
	}

	private function getNiche($gal_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		if ($gal_id) {
			$sql = "select gal_niche from galleries where gal_id = '" . $gal_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) $result = $row[0]['gal_niche'];
			}
		}
		return $result;
	}

	public function getStatus($gal_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		if ($gal_id) {
			$sql = "select gal_status from galleries where gal_id = '" . $gal_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) $result = $row[0]['gal_status'];
			}
		}
		return $result;
	}

	public function getGalsListTags($galleries)
	{
		$result = array();
		if (is_array($galleries)) {
			$galleries_array = array();
			foreach ($galleries as $g_id) {
				if ((int)$g_id) {
					$galleries_array[] = $g_id;
				}
			}

			if ($galleries_array) {


				$db = DB::get();
				if ($db) {

					$sql = "SELECT galleries_tags.gal_tags, tags.tag_name, galleries_tags.gal_id
								FROM galleries_tags 
								LEFT JOIN tags ON galleries_tags.gal_tags = tags.tag_id
								WHERE galleries_tags.gal_id IN (" . implode(",", $galleries_array) . ")
								ORDER BY tags.tag_name";

					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->execute()) {
							$tag_id = false;
							$tag_name = false;
							$gal_id = false;
							$stmt->bind_result($tag_id, $tag_name, $gal_id);
							while ($stmt->fetch()) {
								$result[$gal_id] = isset($result[$gal_id]) ? $tag_name . "," . $result[$gal_id] : $tag_name . ",";
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute error '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": DB error '" . $db->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No DB connection", true);
				}
			}
		}

		return $result;
	}

	public function getTags($gal_id, $full = false)
	{
		$gal_id = (int)$gal_id;
		$result = array();
		if ($gal_id > 0) {
			$db = DB::get();
			if ($db) {
				if ($full) {
					$sql = "SELECT galleries_tags.gal_tags, tags.tag_name 
							FROM galleries_tags 
							LEFT JOIN tags ON galleries_tags.gal_tags = tags.tag_id
							WHERE gal_id = '" . $gal_id . "'
							ORDER BY tags.tag_name";
				} else {
					$sql = "SELECT gal_tags 
							FROM galleries_tags 
							WHERE gal_id = '" . $gal_id . "'";
				}
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$tag_id = false;
						$tag_name = false;
						if ($full) {
							$stmt->bind_result($tag_id, $tag_name);
							while ($stmt->fetch()) {
								$result[$tag_id] = $tag_name;
							}
						} else {
							$stmt->bind_result($tag_id);
							while ($stmt->fetch()) {
								$result[] = $tag_id;
							}
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute error '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": DB error '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connection", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": gal_id <= 0 - неверные входящие параметры", true);
		}

		return $result;
	}

	public function getModels($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = array();
		$sql = "SELECT model_id FROM galleries_models WHERE gallery_id = '" . $gal_id . "'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($rows) {
				foreach ($rows as $row) {
					$result[] = $row['model_id'];
				}
			}
		}
		return $result;
	}


	public function insertThumbTag($gal_id, $thumb_id, $tag_id)
	{
		$result = false;
		$thumb_id = intval($thumb_id);
		$tag_id = intval($tag_id);
		$gal_id = intval($gal_id);
		if ($gal_id && $thumb_id && $tag_id) {
			if ($this->getImage($thumb_id)) {
				// One tag may belong to only one thumb inside a gallery, but a thumb may contain several tags.
				$sql = "INSERT INTO thumbs_tags
						(gal_id, thumb_id, tag_id)
						VALUES
						('" . $gal_id . "', '" . $thumb_id . "', '" . $tag_id . "')
						ON DUPLICATE KEY UPDATE
							thumb_id = VALUES(thumb_id),
							added_on = CURRENT_TIMESTAMP";
				// var_dump($sql);
				$rs = $this->_db->query($sql);
				if ($rs) $result = true;
			} else {
				echo "Нет такой тумбы";
			}
		}
		return $result;
	}

	public function removeThumbTag($gal_id, $thumb_id, $tag_id)
	{
		$result = false;
		$thumb_id = intval($thumb_id);
		$tag_id = intval($tag_id);
		$gal_id = intval($gal_id);
		if ($gal_id && $thumb_id && $tag_id) {
			if ($this->getImage($thumb_id)) {
				$sql = "delete from thumbs_tags
						where gal_id ='" . $gal_id . "' 
						and thumb_id ='" . $thumb_id . "' 
						and tag_id = '" . $tag_id . "'";
				$rs = $this->_db->query($sql);
				// var_dump($sql);
				if ($rs) $result = true;
			}
		}
		return $result;
	}

	public function insertTag(int $gal_id, int $tag_id, $dont_remove_tag = false)
	{
		$result = false;

		$start = get_time();
		if ($gal_id > 0 && $tag_id > 0) {

			$tag_worker = new Tags;
			$tag_info = $tag_worker->getTag($tag_id);

			if ($tag_info) {
				$gallery_info = $this->getMainGalleryInfo($gal_id);
				$main_tag_id = $tag_info['main_tag_id'];
				$gal_niche = false;
				$gal_status = false;

				if ($gallery_info && is_array($gallery_info)) {

					$gal_niche = $gallery_info['niche'];
					$gal_status = $gallery_info['status'];

					if ($gal_status != 'delete' && $gal_status != 'trash') {

						// рассмотреть вариант логирования и добавления одной транзакцией
						// т.к. сервер может и упасть
						$sql = "INSERT INTO galleries_tags
								(gal_id, gal_tags, gal_niche)
								SELECT '" . $gal_id . "', '" . $tag_id . "', '" . $gal_niche . "'
								FROM DUAL
								WHERE NOT EXISTS (
									SELECT gal_id 
									FROM `galleries_tags` 
							    	WHERE gal_id = '" . $gal_id . "' 
							    	AND gal_tags = '" . $tag_id . "'
							    ) LIMIT 1";

						try {
							$stmt = $this->_db->prepare($sql);
							$stmt->execute();
							$result = $stmt->rowCount() ? array($tag_id) : false;
						} catch (PDOException $e) {
							$log = new Logger(__METHOD__ . ": ОШИБКА DB: '" . $this->_db->errorInfo() . "'", true);
							return false;
						}

						if ($result && $gal_status == 'OK') {
							$item_type = 'tag';
							$change_type = 'added';
							$item_id = $tag_id;

							$this->logGalleryChange($gal_id, $item_type, $change_type, $item_id);
						}

						if ($main_tag_id && $main_tag_id != $tag_id) {
							$resulted_array = $this->insertTag($gal_id, $main_tag_id);
							if ($resulted_array) {
								$result = array_merge($result, $resulted_array);
							}
						}
					} else {
						$log = new Logger(__METHOD__ . ": Галера '" . $gal_id . "' имеет статус '" . $gal_status . "', нельзя добавить тег", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": Галера '" . $gal_id . "' не существует, нельзя добавить тег", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": Тег '" . $tag_id . "' не существует, нельзя добавить  в галеру '" . $gal_id . "'", true);
			}
		}

		$end = get_time();
		$exec_time = $end - $start;
		$log = new Logger(__METHOD__ . ":>>>Execution time. Insert tag - SQL: " . $exec_time);
		if ($result) {
			// необходимо удалить, когда будет готова очередь
			// $this->cache_updateTags($gal_id);
		}
		return $result;
	}

	public function removeTag($gal_id, $tag_id)
	{
		$result = false;
		$gal_id = intval($gal_id);
		$tag_id = intval($tag_id);
		if ($gal_id && $tag_id) {
			$gallery_info = $this->getMainGalleryInfo($gal_id);

			$gal_status = false;

			if ($gallery_info && is_array($gallery_info)) {

				$gal_status = $gallery_info['status'];

				$sql = "delete from galleries_tags
						where gal_id ='" . $gal_id . "' 
						and gal_tags = '" . $tag_id . "'";

				$rs = $this->_db->query($sql);

				if ($rs) {
					$result = true;

					if ($gal_status == 'OK') {
						$item_type = 'tag';
						$change_type = 'removed';
						$item_id = $tag_id;
						$this->logGalleryChange($gal_id, $item_type, $change_type, $item_id);
					}
				}
			} else {
				$log = new Logger(__METHOD__ . ": Галера '" . $gal_id . "' не существует, нельзя удалить тег", true);
			}
		}
		return $result;
	}

	private function updateTags($galleryId, $tags, $niche)
	{
		$galleryId = intval($galleryId);
		if ($galleryId && is_array($tags) && preg_match('/^(gay|straight|shemale)$/im', $niche)) {
			$sql = "delete from galleries_tags where gal_id = '" . $galleryId . "';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				foreach ($tags as $tag) {
					$sql = "INSERT INTO galleries_tags
							(gal_id,gal_tags, gal_niche)
							VALUE ('" . $galleryId . "', '" . $tag . "', '" . $niche . "')";
					$rs = $this->_db->query($sql);
					if ($rs === false) {
						print 'error inserting: ' . $this->_db->errorInfo() . '<BR>';
						$log = new Logger(__METHOD__ . ":error inserting: " . $this->_db->errorInfo(), true);
					}
				}
				return TRUE;
			}
		}
	}

	// добавляется модель к галлерее
	// внтри функции проверяется, существует ли галера и модель
	public function insertModel($gal_id, $model_id)
	{
		return $this->addModel($gal_id, $model_id);
	}
	public function addModel(int $gal_id, int $model_id)
	{
		$result = false;

		if ($model_id > 0 && $gal_id && $gal_id > 0 && $this->isModelExists($model_id)) {
			$gallery_info 	= $this->getMainGalleryInfo($gal_id);
			$gal_status 	= false;

			if ($gallery_info) {

				$gal_status = $gallery_info['status'];
				// рассмотреть вариант логирования и добавления одной транзакцией
				// т.к. сервер может и упасть
				$sql = 'INSERT INTO `galleries_models` (`model_id`, `gallery_id`) 
						SELECT :model_id_1, :gallery_id_1
						FROM DUAL
						WHERE NOT EXISTS (
							SELECT gallery_id 
							FROM `galleries_models` 
							WHERE gallery_id = :gallery_id 
							AND model_id = :model_id
						) LIMIT 1;';


				try {
					$stmt = $this->_db->prepare($sql);
					$stmt->execute(
						array(
							':gallery_id' => $gal_id,
							':model_id' => $model_id,
							':gallery_id_1' => $gal_id,
							':model_id_1' => $model_id
						)
					);
					$result = $stmt->rowCount();
				} catch (PDOException $e) {
					$log = new Logger(__METHOD__ . ": Ошибка БД " . $e->getMessage(), true);
					return false;
				}


				if ($result && $gal_status == 'OK') {
					$item_type = 'model';
					$change_type = 'added';
					$item_id = $model_id;
					$this->logGalleryChange($gal_id, $item_type, $change_type, $item_id);
				}
			} else {
				$log = new Logger(__METHOD__ . ": Галера '" . $gal_id . "' не существует, нельзя добавить модель", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": неверные входящие данные, либо не существует модель", true);
		}

		return $result;
	}


	function removeModel($gal_id, $model_id)
	{
		$result = false;

		$gal_id = intval($gal_id);
		$model_id = intval($model_id);


		if ($gal_id && $model_id) {

			$gallery_info = $this->getMainGalleryInfo($gal_id);

			$gal_status = false;

			if ($gallery_info && is_array($gallery_info)) {

				$gal_status = $gallery_info['status'];
				$affected_rows = false;

				$sql = "delete from galleries_models
						where gallery_id ='" . $gal_id . "' 
						and model_id = '" . $model_id . "'";

				$rs = $this->_db->query($sql);
				$affected_rows = $rs->rowCount();
				if ($rs && $affected_rows) {
					$result = true;

					if ($gal_status == 'OK') {
						$item_type = 'model';
						$change_type = 'removed';
						$item_id = $model_id;
						$this->logGalleryChange($gal_id, $item_type, $change_type, $item_id);
					}
				}
			} else {
				$log = new Logger(__METHOD__ . ": Галера '" . $gal_id . "' не существует, нельзя удалить тег", true);
			}
		}
		return $result;
	}

	// добавлена из-за того что galleriesExists принимают массив
	private function isGalleryExists($gal_id)
	{
		$result = false;
		$db = DB::get();
		$gal_id = (int)$gal_id;

		if ($gal_id) {
			$sql = "SELECT gal_id
					FROM galleries
					WHERE gal_id = ?";
			if ($stmt = $db->prepare($sql)) {
				$stmt->bind_param("i", $gal_id);
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": stmt execute failed: '" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": stmt execute failed: '" . $db->error . "'", true);
			}
		}

		return $result;
	}

	// добавлена чтобы не вызывать класс моделей.. стоит ли так делать - хз
	private function isModelExists($model_id)
	{
		$result = false;
		$db = DB::get();
		$model_id = (int)$model_id;

		if ($model_id) {
			$sql = "SELECT id_model
					FROM model
					WHERE id_model = ?";
			if ($stmt = $db->prepare($sql)) {
				$stmt->bind_param("i", $model_id);
				if ($stmt->execute()) {

					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": stmt execute failed: '" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": stmt execute failed: '" . $db->error . "'", true);
			}
		}

		return $result;
	}

	public function queryImageDelete($gal_id, $image_id)
	{
		return $this->logGalleryChange($gal_id, 'image', 'removed', $image_id);
	}

	// логируются только галеры в статусе ОК, и те которые запощены хотябы на одном сайте
	public function logGalleryChange(int $gal_id, $item_type, $change_type, int $item_id, int $error = 0, $gallery_listed_on_sites = false)
	{

		// $db = DB::get();
		$result = false;

		// добавлено для того чтобы избежать одного селекта при вызове функции из sitesgalleries для логирования удаления
		$gallery_listed_on_sites = ($item_type == 'image') ? array(0) : $this->galleryPostedTo($gal_id);

		// не работает логирование удаления изображения (форс?)

		if ($gallery_listed_on_sites) {

			// var_dump($gallery_listed_on_sites);


			$item_type_ok = in_array($item_type, array('tag', 'model', 'source', 'gallery', 'image'));
			$change_type_ok = in_array($change_type, array('added', 'removed', 'changed'));

			if ($item_type_ok && $change_type_ok && $item_id >= 0) {

				$added_on 	= time();
				$updated_on = $added_on;
				$site_id 	= 0;

				$sql = "INSERT INTO `galleries_changes_query` (gal_id, site_id, item_type, change_type, item_id, 
																   error, added_on, updated_on) 
								(
									SELECT :gal_id, :site_id, :item_type, :change_type, :item_id, 
																   :error, :added_on, :updated_on
									FROM DUAL
									WHERE NOT EXISTS (
										SELECT id 
										FROM `galleries_changes_query` 
						      			WHERE gal_id = :gal_id_1
						      			AND site_id = :site_id_1
						      			AND item_type = :item_type_1
						      			AND change_type = :change_type_1
						      			AND item_id = :item_id_1

						      		) LIMIT 1
								)";

				try {

					// var_dump($this->_db);
					$stmt = $this->_db->prepare($sql);
					foreach ($gallery_listed_on_sites as $site_id) {
						$stmt->execute(
							array(
								':gal_id' => $gal_id,
								':site_id' => $site_id,
								':item_type' => $item_type,
								':change_type' => $change_type,
								':item_id' => $item_id,
								':error' => $error,
								':added_on' => $added_on,
								':updated_on' => $updated_on,
								':gal_id_1' => $gal_id,
								':site_id_1' => $site_id,
								':item_type_1' => $item_type,
								':change_type_1' => $change_type,
								':item_id_1' => $item_id
							)
						);
						$result = $this->_db->lastInsertId();
					}
				} catch (PDOException $e) {
					$log = new Logger(__METHOD__ . ": DB Error: " . $e->getMessage(), true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": item_type, change_type or item_id MISSING or wrong incoming data", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": входящий параметр - список сайтов, не массив", true);
		}


		return $result;
	}


	public function deleteLogItem($log_id)
	{
		$result = false;
		$db = DB::get();

		if (!$db->connect_error) {
			$log_id = (int)$log_id;
			$sql = "DELETE FROM `galleries_changes_query` WHERE id = ?";

			if ($stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("i", $log_id)) {
					if ($stmt->execute()) {
						$result = true;
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT bind params error '" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT error MySQL '" . $db->error . "'", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": MySQL connect error: '" . $db->connect_error . "'", true);
		}
		return $result;
	}

	private function updateThumbs($gal_id, $thumbs, $gal_type)
	{
		$gal_id = (int)$gal_id;
		if ($gal_id > 0 && is_array($thumbs) && count($thumbs) > 0) {
			$sql = "SELECT image_id FROM galleries_pix
					WHERE gal_id = " . $gal_id . ";";
			$rs = $this->_db->query($sql);
			if ($rs) {
				if ($images = $rs->fetchAll(\PDO::FETCH_ASSOC)) {
					foreach ($images as $image) {
						$allThumbs[] = $image['image_id'];
					}
					if (isset($allThumbs)) {
						if (count($allThumbs) !== count($thumbs)) {
							foreach ($allThumbs as $thumb) {
								if (!in_array($thumb, $thumbs)) {
									$this->trashImage($thumb);
									// $this->removeImage($thumb, $gal_type);
								}
							}
						}
						return true;
					}
				} else $log = new Logger(__METHOD__ . " изображения в галере #" . $gal_id . " не найдено", true);
			} else $log = new Logger(__METHOD__ . " проблема с вызовом _db->Execute: '" . $sql . "'", true);
		}
	}

	// #к удалению всю очередь этих функций связанных с релейтедами
	public function rebuildGalleryRelated($id)
	{ // по глобальному ID, для едитов готовых галер
		$result = false;
		$id = intval($id);
		if ($id) {
			$sql = "SELECT site_id FROM sites";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$sites = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($sites) {
					$siteUtils = new Sites($this->_db);
					foreach ($sites as $site) {
						$rs = $this->_db->query("SELECT id FROM site_" . $site['site_id'] . " WHERE gal_id = '" . $id . "'");
						if ($rs) {
							$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
							if ($gallery) {
								$siteUtils->switchSite($site['site_id']);
								if (isset($gallery[0]['id']) && $gallery[0]['id']) {
									$siteUtils->queryRelatedResync($gallery[0]['id']);
								} else $log = new Logger(__METHOD__ . " ошибка добавления галеры #" . $id . " на ребилд в сайт #" . $site['site_id'], true);
								$result = true;
							}
						}
					}
				}
			}
		}
		return $result;
	}

	private function updateGalleryNiche($gallery_id)
	{
		$gallery_id = intval($gallery_id);
		if ($gallery_id) {
			$rs = $this->_db->query("SELECT paysite_niche FROM galleries
										LEFT JOIN paysites
										ON galleries.gal_paysite = paysites.paysite_id
										WHERE gal_id = '" . $gallery_id . "'");
			if ($rs) {
				$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($gallery) {
					$niche = $gallery[0]['paysite_niche'];

					$sql = "UPDATE galleries
							SET gal_niche = '" . $niche . "'
							WHERE gal_id = '" . $gallery_id . "';";
					$rs = $this->_db->query($sql);
					if ($rs === false) {
						print 'error inserting: ' . $this->_db->errorInfo() . '<BR>';
						$log = new Logger(__METHOD__ . ":error inserting: " . $this->_db->errorInfo(), true);
					} else return true;
				}
			}
		}
		return false;
	}

	public function setGalThumb($gal_id, $thumb_id)
	{
		$gal_id = intval($gal_id);
		if ($gal_id) {
			if ($thumb_id == 0) {
				$sql = "UPDATE galleries
						SET gal_thumb = '" . $thumb_id . "'
						WHERE gal_id = '" . $gal_id . "';";
			} else {
				$sql = "SELECT image_id FROM galleries_pix WHERE gal_id = '" . $gal_id . "' AND image_id = '" . $thumb_id . "'";
				$rs = $this->_db->query($sql);
				if ($rs && $rs->fetchAll(\PDO::FETCH_ASSOC)) {
					$sql = "UPDATE galleries
						SET gal_thumb = '" . $thumb_id . "'
						WHERE gal_id = '" . $gal_id . "';";
				} else {
					$log = new Logger(__METHOD__ . ": попытка установить главную тумбу, которая не существует в галере", true);
					return false;
				}
			}
			$rs = $this->_db->query($sql);
			if ($rs === false) {
				print 'error inserting: ' . $this->_db->errorInfo() . '<BR>';
				$log = new Logger(__METHOD__ . ":error inserting: " . $this->_db->errorInfo(), true);
			} else return true;
		}
		return false;
	}

	public function updateGalleryTitle(int $gal_id, string $title)
	{
		$result = false;
		$title = sanitize_non_utf($title);

		if ($gal_id < 0) {
			return false;
		}

		try {
			$sql = "UPDATE galleries
					SET gal_title = :gal_title
					WHERE gal_id = :gal_id";

			$stmt = $this->_db->prepare($sql);
			$stmt->execute(['gal_title' => $title, 'gal_id' => $gal_id]);
			$result = $stmt->rowCount();
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": DB execute failed: " . $e->getMessage(), true);
			return false;
		}


		return $result;
	}

	public function updateGalleryDescription(int $gal_id, string $description)
	{
		$result = false;
		$description = sanitize_non_utf($description);

		if ($gal_id < 0) {
			return false;
		}

		try {
			$sql = "UPDATE galleries
					SET gal_description = :description
					WHERE gal_id = :gal_id";

			$stmt = $this->_db->prepare($sql);
			$stmt->execute(['description' => $description, 'gal_id' => $gal_id]);
			$result = $stmt->rowCount();
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": DB execute failed: " . $e->getMessage(), true);
			return false;
		}

		return $result;
	}

	public function upateGalleryNoTitle($galleryId, $images, $paysite, $set_cropped)
	{
		return $this->updateGallery($galleryId, -9999, -9999, $images, $paysite, false, $set_cropped);
	}

	public function updateGallery($galleryId, $title, $description, $images, $paysite = 0, $tags = false, $set_cropped = false, $additional_titles = false)
	{
		$galleryId = intval($galleryId);
		$paysite = intval($paysite);
		$gallery = $this->getMainGalleryInfo($galleryId);
		if ($title == -9999) { // без изменения тайтла
			$no_title = true;
		} else {
			$title = sanitize_non_utf($title);
			$description = sanitize_non_utf($description);
			$no_title = false;
		}

		// var_dump($images);
		if ($galleryId && $gallery) {
			if (isset($paysite) && $paysite !== 0 && $paysite != $gallery['paysite']['id']) {
				$paysiteUpdate = ", gal_paysite = '" . intval($paysite) . "'";
				$paysiteChanged = true;
				$oldPaysite = $gallery['paysite']['id'];
				$oldImages = $this->getAllImages($galleryId);
				if ($gallery['type'] == 'Movies') {
					$oldMovie = $this->getVideoFilePath($galleryId);
				}
			} else {
				$paysiteChanged = false;
				$paysiteUpdate = "";
			}
			$this->updateThumbs($galleryId, $images, $gallery['type']);
			if ($tags !== false && is_array($tags)) {
				$this->updateTags($galleryId, $tags, $gallery['niche']);
			}
			if ($gallery['type'] == 'Pics' || $gallery['type'] == 'gif')	$count = count($images);
			else $count = $gallery['contentCount'];

			if ($no_title) {
				$sql = "UPDATE galleries
						SET gal_content_count = :gal_content_count
						" . $paysiteUpdate . "
						WHERE gal_id = :gal_id";
				$stmt = $this->_db->prepare($sql);
				$sql_executed = $stmt->execute(
					array(
						':gal_content_count' => $count,
						':gal_id' => $galleryId
					)
				);
			} else {
				$sql = "UPDATE galleries
						SET gal_title = :title, gal_description = :description, gal_content_count = :gal_content_count
						" . $paysiteUpdate . "
						WHERE gal_id = :gal_id";
				$stmt = $this->_db->prepare($sql);
				$sql_executed = $stmt->execute(
					array(
						':title' => $title,
						':description' => $description,
						':gal_content_count' => $count,
						':gal_id' => $galleryId
					)
				);
			}

			if ($sql_executed === false) {
				echo __METHOD__ . ' :: Ошибка добавления в базу данных: ' . $this->_db->errorInfo() . '<BR>';
				$log = new Logger(__METHOD__ . " :: Ошибка добавления в базу данных: " . $this->_db->errorInfo(), true);
			} else {
				if ($set_cropped) {
					$this->updateCroppedStatus($galleryId, 1);
				}
				if ($paysiteChanged && isset($oldImages) && $oldImages && is_array($oldImages) && $this->makeFoldersForGallery($galleryId) && (!isset($oldMovie) || (isset($oldMovie) && $oldMovie && is_file($oldMovie)))) {
					$imagesFolder = $this->galleryContentFolder($galleryId);
					$this->updateGalleryNiche($galleryId);
					if (isset($oldMovie) && is_dir(UPLOADFOLDER . $imagesFolder)) {
						$newMovie = UPLOADFOLDER . $imagesFolder . "/" . $galleryId . ".mp4";
						//var_dump($newMovie);
						if (is_file($oldMovie)) {
							copy($oldMovie, $newMovie);
							chmod($newMovie, 0777);
							if (is_file($newMovie)) unlink($oldMovie);
							$log = new Logger(__METHOD__ . ":" . $oldMovie . " -> " . $newMovie . " :Video moved");
						} else $log = new Logger(__METHOD__ . ":Video " . $oldMovie . " not found and can't move to " . $newMovie, true);
					}
					if (preg_match('/^(screened|fetched|video_converted|grabbed|uploaded|OK)$/im', $gallery['status']) && is_dir(UPLOADFOLDER . $imagesFolder)) {
						foreach ($oldImages as $imageId => $image) {
							if (is_file(UPLOADFOLDER . $image)) {
								$log = new Logger(__METHOD__ . ":" . UPLOADFOLDER . $image . " -> " . UPLOADFOLDER . $imagesFolder . basename($image), true);
								$sql = "update galleries_pix set image = '" . $imagesFolder . "/" . basename($image) . "' where image_id = '" . $imageId . "'";
								if ($rs = $this->_db->query($sql)) {
									copy(UPLOADFOLDER . $image, UPLOADFOLDER . $imagesFolder . "/" . basename($image));
									chmod(UPLOADFOLDER . $imagesFolder . "/" . basename($image), 0777);
									if (is_file(UPLOADFOLDER . $imagesFolder . "/" . basename($image))) unlink(UPLOADFOLDER . $image);
								}
							} else $log = new Logger(__METHOD__ . ":" . UPLOADFOLDER . $image . " not file");
						}
					}
				}
				if ($additional_titles && is_array($additional_titles) && $additional_titles) {
					foreach ($additional_titles as $additional_title) {
						if (isset($additional_title['id'], $additional_title['title'])) {
							if (isset($additional_title['language'])) $additional_title_language = $additional_title['language'];
							else $additional_title_language = false;
							$this->updateAdditionalTitle($galleryId, $additional_title['id'], $additional_title['title'], $additional_title_language);
						}
					}
				}
				if ($gallery['status'] == 'OK') {

					$item_type = 'gallery';
					$change_type = 'changed';
					$item_id = 0;
					$this->logGalleryChange($galleryId, $item_type, $change_type, $item_id);
				}
				$this->removeGallerySkeepCrop($galleryId);
			}
		}
	}



	public function galleryPostedTo($gal_id)
	{ // есть в class.sites.php
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$db = DB::get();
			if ($db) {
				$sql = "SELECT site_id FROM sites_galleries WHERE gal_id = '" . $gal_id . "'";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$site_id = false;
						$stmt->bind_result($site_id);
						while ($stmt->fetch()) {
							$result[] = $site_id;
						}
					}
				}
			}
		}

		return $result;
	}

	private function trashImage(int $image_id, int $user_id = 0)
	{

		$result 	= false;

		$gal_id 	= false;
		$image_src 	= false;
		$rss_flag 	= false;
		$added_on 	= time();
		$gal_thumb 	= 0;

		if ($image_id > 0) {

			$sql = "SELECT 
							galleries_pix.gal_id AS gid, 
							galleries_pix.image AS image, 
							galleries_pix.rss_flag AS rss_flag, 
							galleries.gal_thumb AS thumb

					FROM galleries_pix
					LEFT JOIN galleries ON galleries_pix.gal_id = galleries.gal_id
					WHERE galleries_pix.image_id = :image_id ";



			try {
				$stmt = $this->_db->prepare($sql);
				$stmt->execute(array(':image_id' => $image_id));

				$stmt->bindColumn('gid', $gal_id);
				$stmt->bindColumn('image', $image_src);
				$stmt->bindColumn('rss_flag', $rss_flag);
				$stmt->bindColumn('thumb', $gal_thumb);

				$stmt->fetch(PDO::FETCH_BOUND);
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": DB Error '" . $e->getMessage(), true);
				return false;
			}


			if ($gal_id) {
				$sql = "INSERT INTO `trash_box_thumbs` 
								(image_id, gal_id, image, rss_flag, user_id, added_on) 
						VALUES  (:image_id, :gal_id, :image, :rss_flag, :user_id, :added_on)";

				try {
					$stmt = $this->_db->prepare($sql);
					$stmt->execute(
						array(
							':image_id' => $image_id,
							':gal_id' => $gal_id,
							':image' => $image_src,
							':rss_flag' => $rss_flag,
							':user_id' => $user_id,
							':added_on' => $added_on
						)
					);
					$result = $stmt->rowCount();
				} catch (PDOException $e) {
					$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
					return false;
				}

				if ($result) {
					$this->queryImageDelete($gal_id, $image_id);
					if ($image_id == $gal_thumb) {
						$this->setGalThumb($gal_id, 0);
					}
					$rs = $this->_db->query('delete from galleries_pix where image_id = "' . $image_id . '"');
				}
			}
		}
		return $result;
	}


	public function deleteGalleryImage($gal_id, $thumb_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		$thumb_id = (int)$thumb_id;
		if ($gal_id > 0 && $thumb_id > 0) {
			$sql = "SELECT galleries.gal_type 
					FROM galleries_pix
					INNER JOIN galleries ON galleries.gal_id = galleries_pix.gal_id
					WHERE galleries_pix.gal_id = " . $gal_id . " AND galleries_pix.image_id = " . $thumb_id . ";";
			$db = DB::get();
			if ($db) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$gal_type = false;
						$stmt->bind_result($gal_type);
						$stmt->fetch();

						if ($gal_type) {
							// $result = $this->removeImage($thumb_id, $gal_type);
							$result = $this->trashImage($thumb_id);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT error '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": DB error '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connection", true);
			}
		}
		return $result;
	}


	private function removeImage($imageId, $gal_type, $model = false)
	{
		$imageId = (int)$imageId;
		if ($imageId > 0) {
			if (!$model) {
				$image = $this->getImage($imageId, false, true);

				// var_dump($image);

				if ($image && isset($image['gal_thumb']) && isset($image['image'])) {
					if ($imageId == $image['gal_thumb']) {
						$this->setGalThumb($image['gal_id'], 0);
					}
					$image = $image['image'];
					$sql = 'delete from galleries_pix where image_id = "' . $imageId . '"';
				} else {
					return false;
				}
			} else {
				$image = "models/";
				$sql = "delete from models_images where image_id = '" . $imageId . "'";
			}

			if ($image && $rs = $this->_db->query($sql)) {
				$folder = folderNameById($imageId);
				if ($model) {
					if (is_file(UPLOADFOLDER . $image . "/orginal/" . $imageId . ".jpg")) {
						unlink(UPLOADFOLDER . $image);
						if (is_file(UPLOADFOLDER . $image . "/150x200/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . $image . "/150x200/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . $image . "/180x240/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . $image . "/180x240/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . $image . "/240x320/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . $image . "/240x320/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . $image . "/200x150/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . $image . "/200x150/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . $image . "/240x180/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . $image . "/240x180/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . $image . "/320x240/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . $image . "/320x240/" . $folder . "/" . $imageId . ".jpg");
						}
					} else $log = new Logger(__METHOD__ . " Изображение модели не возможно удалить. Файл '" . UPLOADFOLDER . $image . "/orginal/" . $imageId . ".jpg" . "'", true);
				} else {
					if (is_file(UPLOADFOLDER . $image)) {
						unlink(UPLOADFOLDER . $image);
						//	вопрос с гифками, не помню что там делать
						$gal_type_folder_addition = false;

						$thumbs_sizes = getThumbsSizes($gal_type);
						// var_dump($gal_type);
						if ($gal_type == 'Pics') {
							$gal_type_folder_addition = "p";
						} elseif ($gal_type == 'Movies') {
							$gal_type_folder_addition = "m";
						}
						if ($gal_type_folder_addition && $thumbs_sizes && is_array($thumbs_sizes)) {
							foreach ($thumbs_sizes as $size) {
								$image_path = UPLOADFOLDER . "/thumbs/" . $gal_type_folder_addition . "/" . $size['width'] . "/" . $folder . "/" . $imageId . ".jpg";
								if (is_file($image_path)) {
									unlink($image_path); // проверку, удалено ли?
								} else {
									echo "<h4>Файл " . $image_path . " не найден, невозможно удалить</h4>";
								}
							}
						} else {
							echo "<h2>Ошибка входящих данных, неврзможно удалить изображение '" . $imageId . "'</h2>";
						}
					} else {
						$log = new Logger(__METHOD__ . " файл для удаления " . UPLOADFOLDER . $image . " не найден", true);
					}
					if ($rs = $this->_db->query('delete from galleries_source_pics where image_id = "' . $imageId . '"')) {
						return true;
					}
				}
			} else {
				return false;
			}
		}
	}

	private function getImageTrashInfo($image_id)
	{
		$result = false;

		$db = DB::get();

		if ($db) {
			$image_id = (int)$image_id;
			$sql = "SELECT gal_id, image, rss_flag, user_id, added_on FROM trash_box_thumbs
					WHERE image_id = ?";

			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->bind_param("i", $image_id)) {
					if ($stmt->execute()) {

						$gal_id = false;
						$image = false;
						$rss_flag = false;
						$user_id = false;
						$added_on = false;

						$stmt->bind_result($gal_id, $image, $rss_flag, $user_id, $added_on);
						$stmt->fetch();

						if ($gal_id) {
							$result = compact("gal_id", "image", "rss_flag", "user_id", "added_on");
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute error '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT bind_param error '" . $stmt->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB error '" . $db->error . "'", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connection", true);
		}

		return $result;
	}

	public function removeImagesFinal($image_id)
	{

		$image_id = (int)$image_id;
		$result = false;

		$trashed_image = $this->getImageTrashInfo($image_id);

		$gal_id = false;
		$image = false;
		$rss_flag = false;
		$user_id = false;
		$added_on = false;

		if ($trashed_image) {

			extract($trashed_image);
			if ($image) {
				$image_path = UPLOADFOLDER . $image;
				if (is_file($image_path)) {
					unlink($image_path);
				} else {
					$log = new Logger(__METHOD__ . ": Image '" . $image_path . "' not found. Additional info: Image #" . $image_id . ", GID#" . $gal_id . ", added on " . date("Y-m-d", $added_on), true);
				}

				$thumbs_folder_pre = folderNameById($image_id);
				global $rssThumbSizes, $rssMovieThumbs;
				$thumbs_paths = array(

					UPLOADFOLDER . "/thumbs/p/" . $rssThumbSizes['small']['width'] . "/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/p/" . $rssThumbSizes['medium']['width'] . "/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/p/" . $rssThumbSizes['big']['width'] . "/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/m/" . $rssMovieThumbs['small']['width'] . "/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/m/" . $rssMovieThumbs['medium']['width'] . "/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/m/" . $rssMovieThumbs['big']['width'] . "/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/x300/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/x600/" . $thumbs_folder_pre . "/" . $image_id . ".jpg",
					UPLOADFOLDER . "/thumbs/x800/" . $thumbs_folder_pre . "/" . $image_id . ".jpg"

				);

				foreach ($thumbs_paths as $thumb_to_delete) {
					// echo $thumb_to_delete."<br>";
					if (is_file($thumb_to_delete)) {
						if (!unlink($thumb_to_delete)) {
							$log = new Logger(__METHOD__ . ": Image file '" . $thumb_to_delete . "' was not deleted ", true);
						}
					}
				}
			} else {
				$log = new Logger(__METHOD__ . ": Image path is unavailable in DB for Image #" . $image_id . ", GID#" . $gal_id . ", added on " . date("Y-m-d", $added_on), true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No trashed info available for image_id '" . $image_id . "'", true);
		}


		$db = DB::get();
		$db->autocommit(false);
		$all_query_ok = true;

		$sql_strings = array(
			"delete from galleries_pix where image_id = ?",
			"delete from images_sources where thumb_id = ?",
			"delete from trash_box_thumbs where image_id = ?",
			"delete from galleries_source_pics where image_id = ?"
		);

		foreach ($sql_strings as $sql) {
			if ($all_query_ok) {
				if ($stmt = $db->prepare($sql)) {
					if ($stmt->bind_param("i", $image_id)) {
						if (!$stmt->execute()) {
							$all_query_ok = false;
							$log = new Logger(__METHOD__ . ": SQL execute error: '" . $stmt->error . "', '" . $sql . "'", true);
						}
					} else {
						$all_query_ok = false;
						$log = new Logger(__METHOD__ . ": Bind param error: '" . $stmt->error . "', '" . $sql . "'", true);
					}
				} else {
					$all_query_ok = false;
					$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "', '" . $sql . "'", true);
				}
			}
		}


		if ($all_query_ok) {
			$db->commit();
			$result = true;
		} else {
			$db->rollback();
			$log = new Logger(__METHOD__ . ": не все запросы на удаление изображений для галеры #" . $gal_id . " выполнены!", true);
		}

		$db->autocommit(true);

		return $result;
	}

	private function removeGalleryImages($gal_id)
	{
		$db = DB::get();

		$gal_id = (int)$gal_id;

		$images = $this->getAllImages($gal_id);

		$result = false;

		$db->autocommit(false);
		$all_query_ok = true;
		$sql_strings = array(
			"delete from galleries_pix where gal_id = ?",
			"delete from images_sources where gal_id = ?",
			"delete from galleries_source_pics where gal_id = ?"
		);

		foreach ($sql_strings as $sql) {
			if ($all_query_ok && $stmt = $db->prepare($sql)) {
				if ($stmt->bind_param("i", $gal_id)) {
					if (!$stmt->execute()) {
						$all_query_ok = false;
						$log = new Logger(__METHOD__ . ": SQL execute error: '" . $stmt->error . "'", true);
					}
				} else {
					$all_query_ok = false;
					$log = new Logger(__METHOD__ . ": Bind param error: '" . $stmt->error . "'", true);
				}
			} else {
				$all_query_ok = false;
				$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "'", true);
			}
		}


		if ($all_query_ok) {
			$db->commit();
			if (is_array($images)) {
				foreach ($images as $imageId => $image) {
					$folder = folderNameById($imageId);

					if (is_file(UPLOADFOLDER . $image)) {
						unlink(UPLOADFOLDER . $image);
						if (is_file(UPLOADFOLDER . "/thumbs/p/150/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/p/150/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/p/180/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/p/180/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/p/240/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/p/240/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/m/200/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/m/200/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/m/240/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/m/240/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/m/320/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/m/320/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/x300/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/x300/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/x600/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/x600/" . $folder . "/" . $imageId . ".jpg");
						}
						if (is_file(UPLOADFOLDER . "/thumbs/x600/" . $folder . "/" . $imageId . ".jpg")) {
							unlink(UPLOADFOLDER . "/thumbs/x800/" . $folder . "/" . $imageId . ".jpg");
						}
					} else {
						$log = new Logger(__METHOD__ . " файл для удаления " . UPLOADFOLDER . $image . " не найден", true);
					}
				}
			}
		} else {
			$db->rollback();
			$log = new Logger(__METHOD__ . ": не все запросы на удаление изображений для галеры #" . $gal_id . " выполнены!", true);
		}

		$db->autocommit(true);

		return $result;
	}

	private function removeGalleryFromDb(int $id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from galleries where gal_id = "' . $id . '"')) return true;
	}

	private function removeGalleryTags($id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from galleries_tags where gal_id = "' . $id . '"')) return true;
	}

	private function removeGalleryModels($id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from galleries_models where gallery_id = "' . $id . '"')) return true;
	}

	private function removeGalleryTitle($id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from galleries_urls where gal_id = "' . $id . '"')) return true;
	}

	private function removeGalleryManualRecrop($id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from scr_gallery_manual_recrop where gal_id = "' . $id . '"')) return true;
	}

	private function removeGalleryCropHistory($id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from scr_manual_crop_history where gal_id = "' . $id . '"')) return true;
	}

	private function removeGallerySkeepCrop($id)
	{
		$id = intval($id);
		if ($rs = $this->_db->query('delete from scr_user_skeep_gallery where gal_id = "' . $id . '"')) return true;
	}


	private function removeGalleryFromSites($gal_id, $sites = false)
	{

		$result = false;

		$gal_id = (int)($gal_id);

		if (!$sites) {
			$sql = "SELECT site_id FROM sites_galleries
					WHERE gal_id = '" . $gal_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) $sites = $rs->fetchAll(\PDO::FETCH_ASSOC);
			$sites_from_args = false;
		} else {
			$sites_from_args = true;
		}

		$all_sites_deleted = false;

		if ($sites && is_array($sites)) {
			$sites_galleries = new SitesGalleries;
			$all_sites_deleted = true;
			foreach ($sites as $site) {
				if ($sites_from_args) {
					$site_id = (int)$site;
				} else {
					$site_id = (int)$site['site_id'];
				}
				$sites_galleries->setSiteId($site_id);
				$delete_result = $sites_galleries->deleteGallery($gal_id);
				if ($delete_result == false) {
					$log = new Logger(__METHOD__ . ": Ошибка удаления галлереи #" . $gal_id . " с сайта #" . $site_id . ";", true);
					$all_sites_deleted = false;
				}
			}
		}

		if ($all_sites_deleted == true) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	// старая удалялка
	private function old_removeGalleryFromSites($id, $sites = false)
	{
		$id = intval($id);
		if (!$sites) {
			$sql = "SELECT site_id FROM sites_galleries";
			$rs = $this->_db->query($sql);
			if ($rs) $sites = $rs->fetchAll(\PDO::FETCH_ASSOC);
			$sites_from_args = false;
		} else $sites_from_args = true;
		if ($sites && is_array($sites)) {
			foreach ($sites as $site) {

				$sql = "SELECT id FROM";
				if ($sites_from_args) $sql .= " site_" . $site;
				else $sql .= " site_" . $site['site_id'];
				$sql .= " WHERE gal_id = '" . $id . "'";

				$rs = $this->_db->query($sql);

				if ($rs) {
					$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
					if ($gallery) {
						$localId = $gallery[0]['id'];
						if ($localId) {
							$rs = $this->_db->query('delete from site_' . $site['site_id'] . ' where gal_id = "' . $id . '"');
							$rs = $this->_db->query('delete from  site_' . $site['site_id'] . '_exclude_gals where gal_id = "' . $id . '"');
							$rs = $this->_db->query('delete from  site_' . $site['site_id'] . '_models_movies where local_id = "' . $localId . '"');
							$rs = $this->_db->query('delete from  site_' . $site['site_id'] . '_models_pics where local_id = "' . $localId . '"');
							$rs = $this->_db->query('delete from  site_' . $site['site_id'] . '_models_movies where local_id = "' . $localId . '"');
						}
					}
				}
			}
		}
	}

	private function getCropProfile($id)
	{
		$result = false;
		$id = intval($id);
		if ($id) {
			$sql = "SELECT crop_profile_name, IM_string, crop_quality, cut_top, cut_bottom, cut_left, cut_right, paysites.set_cropped
					FROM galleries 
					LEFT JOIN paysites ON galleries.gal_paysite = paysites.paysite_id 
					LEFT JOIN crop_profiles ON paysites.crop_profile_id = crop_profiles.profile_id
					WHERE galleries.gal_id = '" . $id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$crop['name'] = $row[0]['crop_profile_name'];
					$crop['IM'] = $row[0]['IM_string'];
					$crop['quality'] = $row[0]['crop_quality'];
					$crop['top'] = $row[0]['cut_top'];
					$crop['bottom'] = $row[0]['cut_bottom'];
					$crop['left'] = $row[0]['cut_left'];
					$crop['right'] = $row[0]['cut_right'];
					$crop['set_cropped'] = $row[0]['set_cropped'];
					$result = $crop;
				}
			}
		}
		return $result;
	}

	/*

	*/
	public function getResizedGalleriesCount()
	{
		$result = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT COUNT(gal_id) FROM galleries_resized_to";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	public function getNotResizedGalleriesCount()
	{
		$result = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT count(galleries.gal_id) FROM galleries
					LEFT JOIN galleries_resized_to ON galleries.gal_id = galleries_resized_to.gal_id
					WHERE galleries_resized_to.gal_id IS NULL
					AND galleries.gal_type = 'Pics' AND (gal_status = 'OK' OR gal_status = 'uploaded')";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result ? $result : 0;
	}


	// инсерт в таблицу галер, которые имеют тумбы с горизонтальным ресайзом (только пиксы)
	private function galleryToResized(int $gal_id, $status)
	{
		// нет проверки на дубли
		$result = false;

		if ($gal_id > 0 && preg_match("#^(ok|error)$#", $status)) {

			$updated_on = time();
			$size = 800;

			try {
				$sql = "INSERT INTO galleries_resized_to
					(gal_id, horiz_size, status, updated_on)
					VALUES(:gal_id, :size, :status, :updated_on)";
				$stmt = $this->_db->prepare($sql);
				$stmt->execute([
					'gal_id' => $gal_id,
					'size' => $size,
					'status' => $status,
					'updated_on' => $updated_on
				]);
				$result = $this->_db->lastInsertId();
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": DB error: '" . $e->getMessage() . "'", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No gal_id OR status fail", true);
		}

		return $result;
	}

	private function getGalleryToHorizResize()
	{
		$result = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT galleries.gal_id FROM galleries
					LEFT JOIN galleries_resized_to ON galleries.gal_id = galleries_resized_to.gal_id
					WHERE galleries_resized_to.gal_id IS NULL
					AND galleries.gal_type = 'Pics' AND (gal_status = 'OK' OR gal_status = 'uploaded')
					LIMIT 1";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	// только пиксовые пока
	public function processHorizThumbs($gal_id = false)
	{
		$result = false;
		if (!$gal_id) {
			$gal_id = $this->getGalleryToHorizResize();
		}
		$gal_id = (int)$gal_id;
		if ($gal_id) {
			$log = new Logger(__METHOD__ . ": Processing horizontal resize for #" . $gal_id);
			$only_horiz_resize = true;
			$result = $this->processThumbs($gal_id, 'Pics', $only_horiz_resize);
			if ($result) $log = new Logger(__METHOD__ . ": Processing horizontal resize for #" . $gal_id . " successfull");
			else $log = new Logger(__METHOD__ . ": Processing horizontal resize for #" . $gal_id . " failed");
		}
		return $result;
	}

	public function processThumbs($id = false, $type = false, $only_horiz_resize = false)
	{

		$result = false;

		if (!$id && !$type) {

			$gallery = ($randResult = rand(0, 1)) ? $this->galleriesInSatus('fetched', 'Pics', true) : $this->galleriesInSatus('grabbed', 'Movies', true);

			if (!$gallery) {
				$gallery = ($randResult) ? $this->galleriesInSatus('grabbed', 'Movies', true) : $this->galleriesInSatus('fetched', 'Pics', true);
			}
		} elseif ($id && ($type == 'Pics' || $type == 'Movies' || $type == 'embed')) {
			$gallery['id'] = $id;
			$gallery['type'] = $type;
		} else {
			$gallery = false;
		}

		if ($gallery && intval($gallery['id'])) {

			$images = $this->getAllImages($gallery['id']);
			$crop = $this->getCropProfile($gallery['id']);

			if ($images) {

				if ($gallery['type'] == 'Pics') {
					$this->updateImagesCount($gallery['id'], count($images));
				}

				echo "Thumbs processing<br>";
				$failed_h_thumb = false;

				foreach ($images as $imageId => $image) {
					if ($gallery['type'] == 'Pics') {
						if (!$only_horiz_resize) {
							// только вертикальные тумбы со стандартным кропом
							if (!$this->makeRssThumb($imageId, 150, BASE_HEIGHT, $gallery['type'], $crop)) {
								$this->setStatus($gallery['id'], 'thumbs_fail');
								return false;
							}

							if (!$this->makeRssThumb($imageId, 180, 240, $gallery['type'], $crop)) {
								$this->setStatus($gallery['id'], 'thumbs_fail');
								return false;
							}

							if (!$this->makeRssThumb($imageId, 240, 320, $gallery['type'], $crop)) {
								$this->setStatus($gallery['id'], 'thumbs_fail');
								return false;
							}
						}
						// тумбы с ресайзом по горизонтали (с кропом только по кроп-профилю - обрека логотипов)
						if (!$this->makeRssThumb($imageId, 300, 0, $gallery['type'], $crop, false, true)) {
							if (!$only_horiz_resize) {
								$this->setStatus($gallery['id'], 'thumbs_fail');
								return false;
							} else {
								$this->galleryToResized($gallery['id'], 'error');
								return false;
							}
						}

						if (!$this->makeRssThumb($imageId, 600, 0, $gallery['type'], $crop, false, true)) {
							if (!$only_horiz_resize) {
								$this->setStatus($gallery['id'], 'thumbs_fail');
								return false;
							} else {
								$this->galleryToResized($gallery['id'], 'error');
								return false;
							}
						}

						if (!$this->makeRssThumb($imageId, 800, 0, $gallery['type'], $crop, false, true)) {
							if (!$only_horiz_resize) {
								$this->setStatus($gallery['id'], 'thumbs_fail');
								return false;
							} else {
								$this->galleryToResized($gallery['id'], 'error');
								return false;
							}
						}
						if ($this->horiz_resize_ratio) {
							$this->updateImageRatio($imageId, $this->horiz_resize_ratio);
							$this->horiz_resize_ratio = false;
						}
					} else {
						if (!$this->makeRssThumb($imageId, 200, 150, $gallery['type'])) {
							$this->setStatus($gallery['id'], 'thumbs_fail');
							return false;
						}
						if (!$this->makeRssThumb($imageId, 240, 180, $gallery['type'])) {
							$this->setStatus($gallery['id'], 'thumbs_fail');
							return false;
						}
						if (!$this->makeRssThumb($imageId, 320, 240, $gallery['type'])) {
							$this->setStatus($gallery['id'], 'thumbs_fail');
							return false;
						}
					}
				}
				if (!$only_horiz_resize) {
					if ($type == 'embed') {
						$this->setContentType($gallery['id'], 'Movies');
						$gallery['type'] = 'Movies';
					} elseif ($gallery['type'] == 'Movies') {
						$this->addVideoToGalleriesVideosTable($gallery['id']); // 2017-02-01 Update
						$previewResult = $this->generateVideoPreview($gallery['id'], array(
							'width' => defined('VIDEO_PREVIEWS_DEFAULT_WIDTH') ? (int)VIDEO_PREVIEWS_DEFAULT_WIDTH : 320,
							'height' => defined('VIDEO_PREVIEWS_DEFAULT_HEIGHT') ? (int)VIDEO_PREVIEWS_DEFAULT_HEIGHT : 180
						));
						if (!$previewResult) {
							$previewInfo = $this->getVideoPreviewInfo($gallery['id']);
							$previewError = ($previewInfo && !empty($previewInfo['error_message'])) ? $previewInfo['error_message'] : 'preview generation failed';
							$log = new Logger(__METHOD__ . ": preview generation failed for GID#" . $gallery['id'] . ", " . $previewError, true);
						}
					}

					$this->setStatus($gallery['id'], 'uploaded');

					if ($gallery['type'] == 'Movies') {
						if (defined("VIDEOS_SYNC_DOMAIN")) {
							$this->syncCdnVideo($gallery['id']);
						}
					}
				}
				if ($gallery['type'] == 'Pics') {
					$th_status = 'ok';
					$this->galleryToResized($gallery['id'], $th_status);
				}
				// var_dump($gallery['type']);
				if (
					(isset($crop['set_cropped']) && $crop['set_cropped'])
					||
					($gallery['type'] == 'Movies' && defined("SET_MOVIES_CROPPED") && SET_MOVIES_CROPPED)
				) {
					$this->updateCroppedStatus($gallery['id'], 1);
				}
				$result = true;
			}
		} else {
			if ($type) $log = new Logger(__METHOD__ . ":Тумбы: Очередь на создание тумб пустая, нет галер для обработки типа: " . $type);
			else $log = new Logger(__METHOD__ . ":Тумбы: Очередь на создание тумб пустая, нет галер ни в пиксах, ни в мувисах");
		}

		return $result;
	}

	/* START 2017-02-01 */

	// обработка видео для  - добавление в отдельную видео-таблицу, работа с очередью CDN

	private function addVideoToGalleriesVideosTable($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$video_file_size = $this->getVideoSizeFromFile($gal_id);
			if ($video_file_size > 0) {

				$db = DB::get();

				if ($db) {
					$sql = "INSERT INTO galleries_videos
							( gal_id, video_size, is_hd, cdn_synced, original_width, original_height, videos_types_available, video_status )
							VALUES (" . $gal_id . ", " . $video_file_size . ", 5, 0, 0, 0, 'original', 'ok')";

					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->execute()) {
							$result = $db->insert_id;
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No DB connect", true);
				}
			} else {
				echo "filesize GID#" . $gal_id . " is 0 or below<br>";
			}
		} else {
			$log = new Logger(__METHOD__ . ":Галлерея: #" . $gal_id . " " . __METHOD__ . " неверные входящие (id)", true);
		}
		return $result;
	}


	public function fixOkGalleriesToCropped()
	{
		$sql = "UPDATE galleries SET crop_flag = '1' WHERE gal_status IN ('OK', 'uploaded')";
		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute();
			return $stmt->rowCount();
		} catch (PDOException $e) {

			return false;
		}
	}

	public function fixGalleriesVideosTable($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$video_file_size = $this->getVideoSizeFromFile($gal_id);
			if ($video_file_size > 0) {

				$db = DB::get();

				if ($db) {
					$sql = "INSERT INTO galleries_videos
							( gal_id, video_size, is_hd, cdn_synced, original_width, original_height, videos_types_available, video_status )
							SELECT gal_id, 0, 5, 0, 0, 0, 'original', 'ok'
							FROM galleries WHERE gal_type = 'Movies' AND embed_flag = 0 AND gal_status IN ('OK', 'uploaded') AND gal_id NOT IN (
								SELECT gal_id FROM galleries_videos
							)";

					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->execute()) {
							$result = $db->insert_id;
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No DB connect", true);
				}
			} else {
				echo "filesize for GID#'" . $gal_id . "' is 0 or below<br>";
			}
		} else {
			$log = new Logger(__METHOD__ . ":Галлерея: #" . $gal_id . " " . __METHOD__ . " неверные входящие (id)", true);
		}
		return $result;
	}

	private function getVideoSizeFromFile($gal_id)
	{
		$video_file = $this->getVideoFilePath($gal_id);
		if ($video_file) {
			return filesize($video_file);
		}
		return false;
	}

	private function getVideoSize($gal_id)
	{
		$video_file_size = false;
		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$sql = "SELECT video_size FROM galleries_videos WHERE gal_id = " . $gal_id . ";";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->execute()) {
							$stmt->bind_result($video_file_size);
							$stmt->fetch();
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}

		return $video_file_size;
	}

	public function isVideoCdnSynced($gal_id)
	{
		$cdn_synced = false;
		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$sql = "SELECT cdn_synced FROM galleries_videos WHERE gal_id = " . $gal_id . ";";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->execute()) {
							$stmt->bind_result($cdn_synced);
							$stmt->fetch();
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}

		return $cdn_synced;
	}

	private function deleteVideoFromGalleriesVideos($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$sql = "DELETE FROM galleries_videos WHERE gal_id = " . $gal_id . ";";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}

		return $result;
	}


	public function updateVideoFileSize($gal_id)
	{
		$result = false;
		$video_file_size = $this->getVideoSizeFromFile($gal_id);
		if ($video_file_size > 0) {
			$sql = "UPDATE galleries_videos SET video_size = ? WHERE gal_id = ?";

			$db = DB::get();
			if ($db) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					$stmt->bind_param("ii", $video_file_size, $gal_id);
					if ($stmt->execute()) {
						$result = $video_file_size;
					} else {
						$log = new Logger(__METHOD__ . ": Размер видео не проапдейчен, GID:" . $gal_id, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			}
		} else {
			// error
			echo "<h3>" . $gal_id . ":" . $video_file_size . "</h3>";
		}
		return $result;
	}

	public function fixVideoEmptyFilesize()
	{
		$sql = "SELECT gal_id FROM galleries_videos WHERE video_size = 0";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$gals_to_fix = false;
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$gal_id = false;
						$stmt->bind_result($gal_id);
						while ($stmt->fetch()) {
							$gals_to_fix[] = $gal_id;
						}
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}

					if ($gals_to_fix) {
						var_dump($gals_to_fix);
						foreach ($gals_to_fix as $gal_id) {
							$this->updateVideoFileSize($gal_id);
						}
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
	}

	public function getEmptyFilesizeVideosCount()
	{
		$sql = "SELECT count(gal_id) FROM galleries_videos WHERE video_size = 0";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$gal_id_count = 0;
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$gal_id = false;
						$stmt->bind_result($gal_id_count);
						$stmt->fetch();
						return $gal_id_count;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return false;
	}

	public function getAllVideosSize($size_in = 'gb')
	{
		$sql = "SELECT sum(video_size) FROM galleries_videos";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$gals_to_fix = false;
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$gal_size = false;
						$stmt->bind_result($gal_size);
						$stmt->fetch();
						if ($size_in == 'tb') {
							return number_format($gal_size / (1073741824 * 1024), 3);
						} elseif ($size_in == 'gb') {
							return $gal_size / 1073741824;
						} elseif ($size_in == 'mb') {
							return $gal_size / 1048576;
						} elseif ($size_in == 'kb') {
							return $gal_size / 1024;
						} else {
							return $gal_size;
						}
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return false;
	}

	public function getVideoPreviewPath($gal_id, $must_exist = false, $format = 'mp4')
	{
		$storage = $this->getVideoPreviewStorageInfo($gal_id, $format, false, $must_exist);
		return ($storage && isset($storage['path'])) ? $storage['path'] : false;
	}

	public function getVideoPreviewPublicUrl($gal_id, $must_exist = false, $format = 'mp4')
	{
		$storage = $this->getVideoPreviewStorageInfo($gal_id, $format, false, $must_exist);
		return ($storage && isset($storage['url'])) ? $storage['url'] : false;
	}

	public function getVideoPreviewRelativePath($gal_id, $must_exist = false, $format = 'mp4')
	{
		$storage = $this->getVideoPreviewStorageInfo($gal_id, $format, false, $must_exist);
		if (!$storage || !isset($storage['relative_path'])) {
			return false;
		}

		return ltrim($storage['relative_path'], '/');
	}

	private function getVideoPreviewStorageInfo($gal_id, $format = 'mp4', $previewInfo = false, $must_exist = false, $widthOverride = 0)
	{
		$gal_id = (int)$gal_id;
		$format = strtolower(trim((string)$format));

		if ($gal_id <= 0 || !preg_match('#^(mp4|webm)$#', $format)) {
			return false;
		}

		if (!$previewInfo) {
			$previewInfo = $this->getVideoPreviewInfo($gal_id);
		}

		if (!$previewInfo || empty($previewInfo['id'])) {
			return false;
		}

		$previewId = (int)$previewInfo['id'];
		if ($previewId <= 0) {
			return false;
		}

		$previewWidth = (int)$widthOverride;
		if ($previewWidth <= 0) {
			$previewWidth = isset($previewInfo['preview_width']) ? (int)$previewInfo['preview_width'] : 0;
		}
		if ($previewWidth <= 0) {
			$previewWidth = defined('VIDEO_PREVIEWS_DEFAULT_WIDTH') ? (int)VIDEO_PREVIEWS_DEFAULT_WIDTH : 320;
		}

		$basePath = rtrim(defined('VIDEO_PREVIEWS_FOLDER') ? VIDEO_PREVIEWS_FOLDER : (dirname(UPLOADFOLDER) . '/video_previews_mgx'), '/');
		$baseUrl = rtrim(defined('VIDEO_PREVIEWS_URL') ? VIDEO_PREVIEWS_URL : (HOSTING . '/video_previews_mgx'), '/');
		$galleryBucket = substr((string)$gal_id, 0, 1);
		$previewBucket = substr((string)$previewId, 0, 1);
		$fileName = $previewWidth . "_" . $previewId . "." . $format;
		$relativePath = "/" . $galleryBucket . "/" . $previewBucket . "/" . $fileName;
		$fullPath = $basePath . $relativePath;
		$fullUrl = $baseUrl . $relativePath;

		if ($must_exist && !is_file($fullPath)) {
			return false;
		}

		return array(
			'id' => $previewId,
			'width' => $previewWidth,
			'relative_path' => $relativePath,
			'path' => $fullPath,
			'url' => $fullUrl
		);
	}

	public function getVideoPreviewInfo($gal_id)
	{
		$gal_id = (int)$gal_id;

		if ($gal_id <= 0) {
			return false;
		}

		try {
			$sql = "SELECT *
					FROM galleries_video_previews
					WHERE gal_id = :gal_id
					LIMIT 1";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array(':gal_id' => $gal_id));
			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			return is_array($result) ? $result : false;
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	public function canRequestVideoPreview(int $gal_id, $allowUploaded = false)
	{
		if ($gal_id <= 0 || $gal_type = $this->getGalleryType($gal_id) !== 'Movies') {
			Logger::error(__METHOD__ . "#{$gal_id}, '{$gal_type}' - не Movies");
			return false;
		}

		$status = $this->getGalleryStatus($gal_id);
		if ($allowUploaded) {
			Logger::error(__METHOD__ . "#{$gal_id} status: '{$status}'");
			return in_array($status, array('uploaded', 'OK'), true);
		}

		return $status === 'OK';
	}

	private function getActiveVideoPreviewJob($gal_id)
	{
		$gal_id = (int)$gal_id;
		if ($gal_id <= 0) {
			return false;
		}

		try {
			$sql = "SELECT *
					FROM video_preview_jobs
					WHERE gal_id = :gal_id
					  AND job_status IN ('new', 'processing')
					ORDER BY id ASC
					LIMIT 1";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array(':gal_id' => $gal_id));
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return is_array($result) ? $result : false;
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	private function getVideoPreviewJobById($job_id)
	{
		$job_id = (int)$job_id;
		if ($job_id <= 0) {
			return false;
		}

		try {
			$stmt = $this->_db->prepare("SELECT * FROM video_preview_jobs WHERE id = :id LIMIT 1");
			$stmt->execute(array(':id' => $job_id));
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return is_array($result) ? $result : false;
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	private function updateVideoPreviewJobStatus($job_id, array $data)
	{
		$job_id = (int)$job_id;
		if ($job_id <= 0 || !$data) {
			return false;
		}

		$allowed = array(
			'preview_id',
			'job_status',
			'callback_status',
			'preview_format',
			'requested_on',
			'started_on',
			'finished_on',
			'worker_ip',
			'attempts',
			'error_message'
		);

		$setParts = array();
		$params = array(':id' => $job_id);

		foreach ($data as $field => $value) {
			if (!in_array($field, $allowed, true)) {
				continue;
			}

			$setParts[] = $field . " = :" . $field;
			$params[":" . $field] = $value;
		}

		if (!$setParts) {
			return false;
		}

		try {
			$sql = "UPDATE video_preview_jobs SET " . implode(", ", $setParts) . " WHERE id = :id";
			$stmt = $this->_db->prepare($sql);
			return $stmt->execute($params);
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	private function addVideoPreviewJobCallback($job_id, $gal_id, $callback_url)
	{
		$job_id = (int)$job_id;
		$gal_id = (int)$gal_id;
		$callback_url = trim((string)$callback_url);

		if ($job_id <= 0 || $gal_id <= 0 || $callback_url === '') {
			return false;
		}

		try {
			$sql = "SELECT *
					FROM video_preview_job_callbacks
					WHERE job_id = :job_id
					  AND callback_url = :callback_url
					  AND callback_status = 'pending'
					ORDER BY id DESC
					LIMIT 1";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array(
				':job_id' => $job_id,
				':callback_url' => $callback_url
			));
			$existing = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($existing) {
				return $existing;
			}

			$callbackToken = md5($job_id . '|' . $gal_id . '|' . $callback_url . '|' . microtime(true) . '|' . mt_rand(1000, 999999));
			$sql = "INSERT INTO video_preview_job_callbacks
					(job_id, gal_id, callback_url, callback_token, callback_status, callback_attempts, callback_last_on, callback_error, added_on, notified_on)
					VALUES
					(:job_id, :gal_id, :callback_url, :callback_token, 'pending', 0, 0, '', :added_on, 0)";
			$stmt = $this->_db->prepare($sql);
			$stmt->execute(array(
				':job_id' => $job_id,
				':gal_id' => $gal_id,
				':callback_url' => $callback_url,
				':callback_token' => $callbackToken,
				':added_on' => time()
			));

			return array(
				'id' => (int)$this->_db->lastInsertId(),
				'job_id' => $job_id,
				'gal_id' => $gal_id,
				'callback_url' => $callback_url,
				'callback_token' => $callbackToken,
				'callback_status' => 'pending'
			);
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	private function updateVideoPreviewJobCallbackStatus($callback_id, $status, $error = '', $attempts = 0)
	{
		$callback_id = (int)$callback_id;
		$attempts = (int)$attempts;
		$error = substr(trim((string)$error), 0, 255);
		if ($callback_id <= 0 || !preg_match('#^(pending|sent|error)$#', $status)) {
			return false;
		}

		try {
			$stmt = $this->_db->prepare("UPDATE video_preview_job_callbacks
										 SET callback_status = :callback_status,
											 callback_attempts = :callback_attempts,
											 callback_last_on = :callback_last_on,
											 callback_error = :callback_error,
											 notified_on = :notified_on
										 WHERE id = :id");
			return $stmt->execute(array(
				':callback_status' => $status,
				':callback_attempts' => $attempts,
				':callback_last_on' => time(),
				':callback_error' => $error,
				':notified_on' => ($status === 'sent') ? time() : 0,
				':id' => $callback_id
			));
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	private function sendVideoPreviewCallback($callback_url, array $payload, &$responseBody = '')
	{
		$callback_url = trim((string)$callback_url);
		if ($callback_url === '' || !function_exists('curl_init')) {
			$responseBody = 'curl unavailable';
			return false;
		}

		$process = curl_init($callback_url);
		if (!$process) {
			$responseBody = 'curl init failed';
			return false;
		}

		$postBody = http_build_query($payload, '', '&');
		curl_setopt($process, CURLOPT_POST, 1);
		curl_setopt($process, CURLOPT_POSTFIELDS, $postBody);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($process, CURLOPT_TIMEOUT, defined('VIDEO_PREVIEW_CALLBACK_TIMEOUT') ? (int)VIDEO_PREVIEW_CALLBACK_TIMEOUT : 15);
		curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($process, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: ' . strlen($postBody)
		));

		$responseBody = curl_exec($process);
		$httpCode = (int)curl_getinfo($process, CURLINFO_HTTP_CODE);
		$curlError = curl_error($process);
		curl_close($process);

		if ($responseBody === false || $curlError) {
			$responseBody = $curlError ? $curlError : 'empty callback response';
			return false;
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			$responseBody = 'HTTP ' . $httpCode . ': ' . substr((string)$responseBody, 0, 200);
			return false;
		}

		return true;
	}

	private function flushVideoPreviewCallbacks($job_id)
	{
		$job_id = (int)$job_id;
		if ($job_id <= 0) {
			return false;
		}

		$job = $this->getVideoPreviewJobById($job_id);
		if (!$job) {
			return false;
		}

		try {
			$stmt = $this->_db->prepare("SELECT *
										 FROM video_preview_job_callbacks
										 WHERE job_id = :job_id
										   AND callback_status = 'pending'
										 ORDER BY id ASC");
			$stmt->execute(array(':job_id' => $job_id));
			$callbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}

		if (!$callbacks) {
			$this->updateVideoPreviewJobStatus($job_id, array('callback_status' => 'none'));
			return true;
		}

		$previewPath = false;
		if ($job['job_status'] === 'done') {
			$previewPath = $this->getVideoPreviewRelativePath($job['gal_id'], true, $job['preview_format']);
		}

		$allSent = true;
		$sentCount = 0;
		foreach ($callbacks as $callback) {
			$attempts = isset($callback['callback_attempts']) ? ((int)$callback['callback_attempts'] + 1) : 1;
			$payload = array(
				'job_id' => (int)$job['id'],
				'gal_id' => (int)$job['gal_id'],
				'status' => ($job['job_status'] === 'done' ? 'ok' : 'fail'),
				'preview_path' => $previewPath ? $previewPath : '',
				'error' => ($job['job_status'] === 'done' ? '' : (string)$job['error_message']),
				'callback_token' => $callback['callback_token']
			);

			$responseBody = '';
			$sent = $this->sendVideoPreviewCallback($callback['callback_url'], $payload, $responseBody);
			if ($sent) {
				$sentCount++;
				$this->updateVideoPreviewJobCallbackStatus($callback['id'], 'sent', '', $attempts);
			} else {
				$allSent = false;
				$this->updateVideoPreviewJobCallbackStatus($callback['id'], 'error', substr((string)$responseBody, 0, 255), $attempts);
				$log = new Logger(__METHOD__ . ": callback failed for job#" . $job_id . ", url '" . $callback['callback_url'] . "', response '" . substr((string)$responseBody, 0, 200) . "'", true);
			}
		}

		$jobCallbackStatus = 'error';
		if ($allSent) {
			$jobCallbackStatus = 'sent';
		} elseif ($sentCount > 0) {
			$jobCallbackStatus = 'partial';
		}

		$this->updateVideoPreviewJobStatus($job_id, array('callback_status' => $jobCallbackStatus));
		return $allSent;
	}

	public function queueVideoPreviewJob($gal_id, $request_ip = '')
	{
		$gal_id = (int)$gal_id;
		$request_ip = trim((string)$request_ip);

		if (!$this->canRequestVideoPreview($gal_id, false)) {
			return array('error' => 'Для постановки preview в очередь галерея должна быть Movies и иметь статус OK');
		}

		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		$previewFormat = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';
		$existingPath = $this->getVideoPreviewRelativePath($gal_id, true, $previewFormat);
		if ($previewInfo && $previewInfo['preview_status'] === 'ok' && $existingPath) {
			return array(
				'status' => 'ok',
				'gal_id' => $gal_id,
				'preview_path' => $existingPath
			);
		}

		if (!$this->queueVideoPreview($gal_id)) {
			return array('error' => 'Не удалось подготовить preview к очереди');
		}

		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		$previewId = ($previewInfo && !empty($previewInfo['id'])) ? (int)$previewInfo['id'] : 0;
		$previewFormat = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';

		$job = $this->getActiveVideoPreviewJob($gal_id);
		if ($job) {
			return array(
				'status' => ($job['job_status'] === 'processing') ? 'processing' : 'queued',
				'gal_id' => $gal_id,
				'job_id' => (int)$job['id']
			);
		}

		try {
			$stmt = $this->_db->prepare("INSERT INTO video_preview_jobs
				(gal_id, preview_id, job_status, callback_status, preview_format, requested_on, started_on, finished_on, worker_ip, attempts, error_message)
				VALUES
				(:gal_id, :preview_id, 'new', 'none', :preview_format, :requested_on, 0, 0, :worker_ip, 0, '')");
			$stmt->execute(array(
				':gal_id' => $gal_id,
				':preview_id' => $previewId,
				':preview_format' => $previewFormat,
				':requested_on' => time(),
				':worker_ip' => $request_ip
			));
			$jobId = (int)$this->_db->lastInsertId();
			$this->updateVideoPreviewRecord($gal_id, array(
				'preview_status' => 'queued',
				'updated_on' => time(),
				'error_message' => ''
			));

			return array(
				'status' => 'queued',
				'gal_id' => $gal_id,
				'job_id' => $jobId
			);
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return array('error' => 'Не удалось создать preview job');
		}
	}

	public function requestVideoPreview($gal_id, $callback_url = '', $request_ip = '')
	{
		$gal_id = (int)$gal_id;
		$callback_url = trim((string)$callback_url);
		$request_ip = trim((string)$request_ip);

		if ($gal_id <= 0) {
			return array('error' => 'Некорректный ID галереи');
		}

		if (!$this->canRequestVideoPreview($gal_id, false)) {
			return array('error' => 'Для запроса preview галерея должна быть Movies и иметь статус OK');
		}

		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		$previewFormat = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';
		$existingPath = $this->getVideoPreviewRelativePath($gal_id, true, $previewFormat);

		if ($previewInfo && $previewInfo['preview_status'] === 'ok' && $existingPath) {
			return array(
				'status' => 'ok',
				'gal_id' => $gal_id,
				'preview_path' => $existingPath
			);
		}

		if ($callback_url === '') {
			return array('error' => 'Для постановки в очередь нужен callback_url');
		}

		if (!$this->queueVideoPreview($gal_id)) {
			return array('error' => 'Не удалось подготовить preview к очереди');
		}

		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		$previewId = ($previewInfo && !empty($previewInfo['id'])) ? (int)$previewInfo['id'] : 0;
		$previewFormat = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';

		$job = $this->getActiveVideoPreviewJob($gal_id);
		if (!$job) {
			try {
				$stmt = $this->_db->prepare("INSERT INTO video_preview_jobs
					(gal_id, preview_id, job_status, callback_status, preview_format, requested_on, started_on, finished_on, worker_ip, attempts, error_message)
					VALUES
					(:gal_id, :preview_id, 'new', 'pending', :preview_format, :requested_on, 0, 0, :worker_ip, 0, '')");
				$stmt->execute(array(
					':gal_id' => $gal_id,
					':preview_id' => $previewId,
					':preview_format' => $previewFormat,
					':requested_on' => time(),
					':worker_ip' => $request_ip
				));
				$jobId = (int)$this->_db->lastInsertId();
				$this->updateVideoPreviewRecord($gal_id, array(
					'preview_status' => 'queued',
					'updated_on' => time(),
					'error_message' => ''
				));
				$job = $this->getVideoPreviewJobById($jobId);
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
				return array('error' => 'Не удалось создать preview job');
			}
		} else {
			$this->updateVideoPreviewJobStatus($job['id'], array('callback_status' => 'pending'));
		}

		$callbackInfo = $this->addVideoPreviewJobCallback($job['id'], $gal_id, $callback_url);
		if (!$callbackInfo) {
			return array('error' => 'Не удалось сохранить callback');
		}

		return array(
			'status' => 'queued',
			'gal_id' => $gal_id,
			'job_id' => (int)$job['id'],
			'callback_token' => $callbackInfo['callback_token']
		);
	}

	public function getNextVideoPreviewJob($worker_ip = '')
	{
		$worker_ip = trim((string)$worker_ip);

		for ($attempt = 0; $attempt < 3; $attempt++) {
			try {
				$stmt = $this->_db->prepare("SELECT *
											 FROM video_preview_jobs
											 WHERE job_status = 'new'
											 ORDER BY requested_on ASC, id ASC
											 LIMIT 1");
				$stmt->execute();
				$job = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!$job) {
					return false;
				}

				$jobId = (int)$job['id'];
				$updated = $this->_db->prepare("UPDATE video_preview_jobs
												SET job_status = 'processing',
													started_on = :started_on,
													worker_ip = :worker_ip,
													attempts = attempts + 1
												WHERE id = :id
												  AND job_status = 'new'");
				$updated->execute(array(
					':started_on' => time(),
					':worker_ip' => $worker_ip,
					':id' => $jobId
				));

				if ($updated->rowCount() < 1) {
					continue;
				}

				$job = $this->getVideoPreviewJobById($jobId);
				if (!$job) {
					return false;
				}

				$gal_id = (int)$job['gal_id'];
				$previewInfo = $this->getVideoPreviewInfo($gal_id);
				$previewFormat = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';
				$storage = $this->getVideoPreviewStorageInfo($gal_id, $previewFormat, $previewInfo, false);
				$this->updateVideoPreviewRecord($gal_id, array(
					'preview_status' => 'processing',
					'updated_on' => time(),
					'error_message' => ''
				));

				return array(
					'job_id' => $jobId,
					'gal_id' => $gal_id,
					'source_video_path' => $this->getVideoFilePath($gal_id),
					'preview_path' => $storage ? $storage['path'] : '',
					'preview_relative_path' => $storage ? ltrim($storage['relative_path'], '/') : '',
					'preview_url' => $storage ? $storage['url'] : '',
					'preview_format' => $previewFormat,
					'preview_width' => ($previewInfo && !empty($previewInfo['preview_width'])) ? (int)$previewInfo['preview_width'] : (defined('VIDEO_PREVIEWS_DEFAULT_WIDTH') ? (int)VIDEO_PREVIEWS_DEFAULT_WIDTH : 320),
					'preview_height' => ($previewInfo && !empty($previewInfo['preview_height'])) ? (int)$previewInfo['preview_height'] : (defined('VIDEO_PREVIEWS_DEFAULT_HEIGHT') ? (int)VIDEO_PREVIEWS_DEFAULT_HEIGHT : 180),
					'clip_count' => ($previewInfo && !empty($previewInfo['clip_count'])) ? (int)$previewInfo['clip_count'] : 10,
					'clip_length_ms' => ($previewInfo && !empty($previewInfo['clip_length_ms'])) ? (int)$previewInfo['clip_length_ms'] : 1000,
					'start_offset' => ($previewInfo && isset($previewInfo['start_offset'])) ? (int)$previewInfo['start_offset'] : 5,
					'end_offset' => ($previewInfo && isset($previewInfo['end_offset'])) ? (int)$previewInfo['end_offset'] : 5
				);
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
				return false;
			}
		}

		return false;
	}

	public function refreshVideoPreviewMetaFromFile($gal_id)
	{
		$gal_id = (int)$gal_id;
		if ($gal_id <= 0) {
			return false;
		}

		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		if (!$previewInfo) {
			return false;
		}

		$format = !empty($previewInfo['preview_format']) ? $previewInfo['preview_format'] : 'mp4';
		$storage = $this->getVideoPreviewStorageInfo($gal_id, $format, $previewInfo, true);
		if (!$storage || empty($storage['path']) || !is_file($storage['path'])) {
			return false;
		}

		if (!class_exists('VideoUtils', false)) {
			require_once __DIR__ . '/class.video.php';
		}

		$video = new VideoUtils("temp");
		$previewSize = filesize($storage['path']);
		$previewDuration = $video->GetDuration($storage['path']);
		$previewFrame = $video->GetSize($storage['path']);
		$previewBitrate = $video->GetBitrate($storage['path']);

		$width = 0;
		$height = 0;
		if (is_array($previewFrame) && isset($previewFrame[0], $previewFrame[1])) {
			$width = (int)$previewFrame[0];
			$height = (int)$previewFrame[1];
		}

		$this->updateVideoPreviewRecord($gal_id, array(
			'preview_status' => 'ok',
			'source_video_size' => (int)$this->getVideoSizeFromFile($gal_id),
			'preview_size' => (int)$previewSize,
			'preview_width' => $width,
			'preview_height' => $height,
			'preview_duration_ms' => (int)$previewDuration * 1000,
			'preview_bitrate' => (int)$previewBitrate,
			'generated_on' => time(),
			'updated_on' => time(),
			'error_message' => ''
		));

		$result = $this->getVideoPreviewInfo($gal_id);
		if ($result) {
			$result['public_url'] = $this->getVideoPreviewPublicUrl($gal_id, true, $format);
			$result['relative_path'] = $this->getVideoPreviewRelativePath($gal_id, true, $format);
			$result['full_path'] = $storage['path'];
		}

		return $result;
	}

	public function completeVideoPreviewJob($job_id, $status = 'ok', $error_message = '', $worker_ip = '')
	{
		$job_id = (int)$job_id;
		$status = strtolower(trim((string)$status));
		$error_message = substr(trim((string)$error_message), 0, 255);
		$worker_ip = trim((string)$worker_ip);

		if ($job_id <= 0 || !preg_match('#^(ok|fail|error)$#', $status)) {
			return array('error' => 'Wrong job status');
		}

		$job = $this->getVideoPreviewJobById($job_id);
		if (!$job) {
			return array('error' => 'Job not found');
		}

		$gal_id = (int)$job['gal_id'];
		$jobUpdate = array(
			'finished_on' => time(),
			'worker_ip' => $worker_ip
		);

		$resultPreview = false;
		if ($status === 'ok') {
			$resultPreview = $this->refreshVideoPreviewMetaFromFile($gal_id);
			if (!$resultPreview || empty($resultPreview['relative_path'])) {
				$status = 'error';
				$error_message = 'Preview file not found after worker completion';
			}
		}

		if ($status === 'ok') {
			$jobUpdate['job_status'] = 'done';
			$jobUpdate['error_message'] = '';
			$jobUpdate['preview_id'] = !empty($job['preview_id']) ? (int)$job['preview_id'] : (($resultPreview && !empty($resultPreview['id'])) ? (int)$resultPreview['id'] : 0);
		} else {
			$jobUpdate['job_status'] = 'error';
			$jobUpdate['error_message'] = ($error_message !== '') ? $error_message : 'Preview worker failed';
			$this->updateVideoPreviewRecord($gal_id, array(
				'preview_status' => 'error',
				'updated_on' => time(),
				'error_message' => $jobUpdate['error_message']
			));
		}

		$this->updateVideoPreviewJobStatus($job_id, $jobUpdate);
		$this->flushVideoPreviewCallbacks($job_id);

		$job = $this->getVideoPreviewJobById($job_id);
		return array(
			'status' => ($job && $job['job_status'] === 'done') ? 'ok' : 'fail',
			'job_id' => $job_id,
			'gal_id' => $gal_id,
			'preview_path' => ($resultPreview && !empty($resultPreview['relative_path'])) ? $resultPreview['relative_path'] : '',
			'error' => ($job && !empty($job['error_message'])) ? $job['error_message'] : ''
		);
	}

	public function processVideoPreviewJob($job_id, $worker_ip = '')
	{
		$job_id = (int)$job_id;
		$worker_ip = trim((string)$worker_ip);

		if ($job_id <= 0) {
			return array('error' => 'Wrong job_id');
		}

		$job = $this->getVideoPreviewJobById($job_id);
		if (!$job) {
			return array('error' => 'Preview job not found');
		}

		$gal_id = (int)$job['gal_id'];
		if (!$this->canRequestVideoPreview($gal_id, false)) {
			return array('error' => 'Галерея для preview job должна быть Movies и иметь статус OK');
		}

		if ($job['job_status'] === 'done') {
			$previewPath = $this->getVideoPreviewRelativePath($gal_id, true, $job['preview_format']);
			return array(
				'success' => true,
				'job_id' => $job_id,
				'gal_id' => $gal_id,
				'preview_path' => $previewPath ? $previewPath : ''
			);
		}

		$this->updateVideoPreviewJobStatus($job_id, array(
			'job_status' => 'processing',
			'started_on' => time(),
			'worker_ip' => $worker_ip,
			'error_message' => ''
		));

		$result = $this->generateVideoPreview($gal_id);
		if (!$result) {
			$previewInfo = $this->getVideoPreviewInfo($gal_id);
			$error = ($previewInfo && !empty($previewInfo['error_message'])) ? $previewInfo['error_message'] : 'Preview generation failed';
			$this->updateVideoPreviewJobStatus($job_id, array(
				'job_status' => 'error',
				'finished_on' => time(),
				'worker_ip' => $worker_ip,
				'error_message' => substr($error, 0, 255)
			));
			return array('error' => $error);
		}

		return array(
			'success' => true,
			'job_id' => $job_id,
			'gal_id' => $gal_id,
			'preview_path' => !empty($result['relative_path']) ? $result['relative_path'] : ''
		);
	}

	public function processQueuedVideoPreviewJobs($limit = 3, $worker_ip = 'cron')
	{
		$limit = (int)$limit;
		$worker_ip = trim((string)$worker_ip);
		if ($limit <= 0) {
			$limit = 3;
		}
		if ($limit > 20) {
			$limit = 20;
		}
		if ($worker_ip === '') {
			$worker_ip = 'cron';
		}

		$processed = 0;
		$success = 0;
		$errors = 0;

		for ($i = 0; $i < $limit; $i++) {
			try {
				$stmt = $this->_db->prepare("SELECT id FROM video_preview_jobs WHERE job_status = 'new' ORDER BY requested_on ASC, id ASC LIMIT 1");
				$stmt->execute();
				$jobId = (int)$stmt->fetchColumn();
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true, true);
				break;
			}

			if ($jobId <= 0) {
				break;
			}

			$processed++;
			$result = $this->processVideoPreviewJob($jobId, $worker_ip);
			if ($result && !isset($result['error'])) {
				$success++;
				$log = new Logger(__METHOD__ . ": processed preview job#" . $jobId . ", GID#" . $result['gal_id'] . ", path '" . $result['preview_path'] . "'", false, true);
			} else {
				$errors++;
				$message = ($result && isset($result['error'])) ? $result['error'] : 'unknown preview job error';
				$log = new Logger(__METHOD__ . ": failed preview job#" . $jobId . ", " . $message, true, true);
			}
		}

		return array(
			'processed' => $processed,
			'success' => $success,
			'errors' => $errors
		);
	}

	public function deleteVideoPreviewJob($job_id)
	{
		$job_id = (int)$job_id;
		if ($job_id <= 0) {
			return array('error' => 'Wrong job_id');
		}

		$job = $this->getVideoPreviewJobById($job_id);
		if (!$job) {
			return array('error' => 'Preview job not found');
		}

		$gal_id = (int)$job['gal_id'];

		try {
			$this->_db->beginTransaction();

			$stmt = $this->_db->prepare("DELETE FROM video_preview_job_callbacks WHERE job_id = :job_id");
			$stmt->execute(array(':job_id' => $job_id));

			$stmt = $this->_db->prepare("DELETE FROM video_preview_jobs WHERE id = :job_id");
			$stmt->execute(array(':job_id' => $job_id));

			$previewInfo = $this->getVideoPreviewInfo($gal_id);
			$previewFormat = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';
			$previewExists = $this->getVideoPreviewRelativePath($gal_id, true, $previewFormat);
			if ($previewInfo && (!$previewExists || $previewInfo['preview_status'] !== 'ok')) {
				$this->updateVideoPreviewRecord($gal_id, array(
					'preview_status' => 'new',
					'updated_on' => time(),
					'error_message' => ''
				));
			}

			$this->_db->commit();
			return array(
				'success' => true,
				'job_id' => $job_id,
				'gal_id' => $gal_id
			);
		} catch (PDOException $e) {
			if ($this->_db->inTransaction()) {
				$this->_db->rollBack();
			}
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return array('error' => 'Ошибка удаления preview job');
		}
	}

	public function queueVideoPreview($gal_id, array $options = array())
	{
		$gal_id = (int)$gal_id;

		if ($gal_id <= 0 || $this->getGalleryType($gal_id) !== 'Movies') {
			return false;
		}

		$source_video_size = (int)$this->getVideoSizeFromFile($gal_id);
		if ($source_video_size <= 0) {
			$log = new Logger(__METHOD__ . ": preview queue skipped, no source video for GID#" . $gal_id, true);
			return false;
		}

		$preview_format = (isset($options['preview_format']) && preg_match('#^(mp4|webm)$#i', $options['preview_format']))
			? strtolower($options['preview_format'])
			: 'mp4';
		$preview_width = isset($options['width']) ? (int)$options['width'] : (defined('VIDEO_PREVIEWS_DEFAULT_WIDTH') ? (int)VIDEO_PREVIEWS_DEFAULT_WIDTH : 320);
		$preview_height = isset($options['height']) ? (int)$options['height'] : (defined('VIDEO_PREVIEWS_DEFAULT_HEIGHT') ? (int)VIDEO_PREVIEWS_DEFAULT_HEIGHT : 180);
		$clip_count = isset($options['clip_count']) ? (int)$options['clip_count'] : 10;
		$clip_length_ms = isset($options['clip_length_ms']) ? (int)$options['clip_length_ms'] : 1000;
		$start_offset = isset($options['start_offset']) ? (int)$options['start_offset'] : 5;
		$end_offset = isset($options['end_offset']) ? (int)$options['end_offset'] : 5;

		if ($preview_width < 160) $preview_width = 160;
		if ($preview_width > 640) $preview_width = 640;
		if ($preview_height < 90) $preview_height = 90;
		if ($preview_height > 360) $preview_height = 360;
		if ($clip_count < 1) $clip_count = 1;
		if ($clip_count > 20) $clip_count = 20;
		if ($clip_length_ms < 250) $clip_length_ms = 250;
		if ($clip_length_ms > 5000) $clip_length_ms = 5000;
		if ($start_offset < 0) $start_offset = 0;
		if ($end_offset < 0) $end_offset = 0;

		try {
			$sql = "INSERT INTO galleries_video_previews
					(gal_id, preview_status, preview_format, source_video_size, preview_size, preview_width, preview_height,
					 preview_duration_ms, preview_bitrate, clip_count, clip_length_ms, start_offset, end_offset, generated_on, updated_on, error_message)
					VALUES
					(:gal_id, 'new', :preview_format, :source_video_size, 0, :preview_width, :preview_height, 0, 0, :clip_count, :clip_length_ms, :start_offset, :end_offset, 0, :updated_on, '')
					ON DUPLICATE KEY UPDATE
						preview_status = 'new',
						preview_format = VALUES(preview_format),
						source_video_size = VALUES(source_video_size),
						preview_size = 0,
						preview_width = VALUES(preview_width),
						preview_height = VALUES(preview_height),
						preview_duration_ms = 0,
						preview_bitrate = 0,
						clip_count = VALUES(clip_count),
						clip_length_ms = VALUES(clip_length_ms),
						start_offset = VALUES(start_offset),
						end_offset = VALUES(end_offset),
						generated_on = 0,
						updated_on = VALUES(updated_on),
						error_message = ''";

			$stmt = $this->_db->prepare($sql);
			$result = $stmt->execute(array(
				':gal_id' => $gal_id,
				':preview_format' => $preview_format,
				':source_video_size' => $source_video_size,
				':preview_width' => $preview_width,
				':preview_height' => $preview_height,
				':clip_count' => $clip_count,
				':clip_length_ms' => $clip_length_ms,
				':start_offset' => $start_offset,
				':end_offset' => $end_offset,
				':updated_on' => time()
			));

			if ($result) {
				$log = new Logger(__METHOD__ . ": queued preview for GID#" . $gal_id . ", clips=" . $clip_count . ", clip_length_ms=" . $clip_length_ms);
			}

			return $result;
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	private function updateVideoPreviewRecord($gal_id, array $data)
	{
		$gal_id = (int)$gal_id;

		if ($gal_id <= 0 || !$data) {
			return false;
		}

		$allowed = array(
			'preview_status',
			'preview_format',
			'source_video_size',
			'preview_size',
			'preview_width',
			'preview_height',
			'preview_duration_ms',
			'preview_bitrate',
			'clip_count',
			'clip_length_ms',
			'start_offset',
			'end_offset',
			'generated_on',
			'updated_on',
			'error_message'
		);

		$setParts = array();
		$params = array(':gal_id' => $gal_id);

		foreach ($data as $field => $value) {
			if (!in_array($field, $allowed, true)) {
				continue;
			}

			$setParts[] = $field . " = :" . $field;
			$params[":" . $field] = $value;
		}

		if (!$setParts) {
			return false;
		}

		try {
			$sql = "UPDATE galleries_video_previews SET " . implode(", ", $setParts) . " WHERE gal_id = :gal_id";
			$stmt = $this->_db->prepare($sql);
			return $stmt->execute($params);
		} catch (PDOException $e) {
			$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
			return false;
		}
	}

	public function generateVideoPreview($gal_id, array $options = array())
	{
		$gal_id = (int)$gal_id;

		if ($gal_id <= 0 || $this->getGalleryType($gal_id) !== 'Movies') {
			return false;
		}

		if (!$this->queueVideoPreview($gal_id, $options)) {
			return false;
		}

		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		if (!$previewInfo) {
			return false;
		}

		$targetWidth = isset($options['width']) ? (int)$options['width'] : (isset($previewInfo['preview_width']) ? (int)$previewInfo['preview_width'] : 0);
		$targetHeight = isset($options['height']) ? (int)$options['height'] : (isset($previewInfo['preview_height']) ? (int)$previewInfo['preview_height'] : 0);
		if ($targetWidth <= 0) $targetWidth = defined('VIDEO_PREVIEWS_DEFAULT_WIDTH') ? (int)VIDEO_PREVIEWS_DEFAULT_WIDTH : 320;
		if ($targetHeight <= 0) $targetHeight = defined('VIDEO_PREVIEWS_DEFAULT_HEIGHT') ? (int)VIDEO_PREVIEWS_DEFAULT_HEIGHT : 180;

		$this->updateVideoPreviewRecord($gal_id, array(
			'preview_status' => 'processing',
			'preview_width' => $targetWidth,
			'preview_height' => $targetHeight,
			'updated_on' => time(),
			'error_message' => ''
		));

		$sourceVideo = $this->getVideoFilePath($gal_id);
		$storage = $this->getVideoPreviewStorageInfo($gal_id, $previewInfo['preview_format'], $previewInfo, false, $targetWidth);
		$outputPath = ($storage && isset($storage['path'])) ? $storage['path'] : false;

		if (!$sourceVideo || !$outputPath) {
			$this->updateVideoPreviewRecord($gal_id, array(
				'preview_status' => 'error',
				'updated_on' => time(),
				'error_message' => 'Source or output path not found'
			));
			return false;
		}

		$video = new VideoUtils("temp");
		$resultFile = $video->makePreview($sourceVideo, $outputPath, array(
			'clip_count' => (int)$previewInfo['clip_count'],
			'clip_length_ms' => (int)$previewInfo['clip_length_ms'],
			'start_offset' => (int)$previewInfo['start_offset'],
			'end_offset' => (int)$previewInfo['end_offset'],
			'width' => $targetWidth,
			'height' => $targetHeight,
			'fps' => 30,
			'crf' => 26
		));

		if (!$resultFile || !is_file($resultFile)) {
			$this->updateVideoPreviewRecord($gal_id, array(
				'preview_status' => 'error',
				'updated_on' => time(),
				'error_message' => $video->errorFlag ? substr($video->errorFlag, 0, 250) : 'Preview generation failed'
			));
			return false;
		}

		$previewSize = filesize($resultFile);
		$previewDuration = $video->GetDuration($resultFile);
		$previewFrame = $video->GetSize($resultFile);
		$previewBitrate = $video->GetBitrate($resultFile);

		$width = 0;
		$height = 0;
		if (is_array($previewFrame) && isset($previewFrame[0], $previewFrame[1])) {
			$width = (int)$previewFrame[0];
			$height = (int)$previewFrame[1];
		}

		$this->updateVideoPreviewRecord($gal_id, array(
			'preview_status' => 'ok',
			'source_video_size' => (int)$this->getVideoSizeFromFile($gal_id),
			'preview_size' => (int)$previewSize,
			'preview_width' => $width,
			'preview_height' => $height,
			'preview_duration_ms' => (int)$previewDuration * 1000,
			'preview_bitrate' => (int)$previewBitrate,
			'generated_on' => time(),
			'updated_on' => time(),
			'error_message' => ''
		));

		$result = $this->getVideoPreviewInfo($gal_id);
		if ($result) {
			$result['public_url'] = $this->getVideoPreviewPublicUrl($gal_id, true, $previewInfo['preview_format']);
			$result['relative_path'] = $this->getVideoPreviewRelativePath($gal_id, true, $previewInfo['preview_format']);
			$result['full_path'] = $resultFile;
		}

		$activeJob = $this->getActiveVideoPreviewJob($gal_id);
		if ($activeJob) {
			$this->updateVideoPreviewJobStatus($activeJob['id'], array(
				'job_status' => 'done',
				'finished_on' => time(),
				'error_message' => ''
			));
			$this->flushVideoPreviewCallbacks($activeJob['id']);
		}

		return $result;
	}

	private function getLegacyVideoPreviewPath($gal_id, $format = 'mp4')
	{
		$gal_id = (int)$gal_id;
		$format = strtolower(trim((string)$format));
		if ($gal_id <= 0 || !preg_match('#^(mp4|webm)$#', $format)) {
			return false;
		}

		$gallery_folder = $this->galleryContentFolder($gal_id);
		if (!$gallery_folder) {
			return false;
		}

		return UPLOADFOLDER . $gallery_folder . "/" . $gal_id . ".preview." . $format;
	}

	public function deleteVideoPreview($gal_id, $deleteRecord = true)
	{
		$gal_id = (int)$gal_id;
		if ($gal_id <= 0) {
			return false;
		}

		$result = true;
		$previewInfo = $this->getVideoPreviewInfo($gal_id);
		$format = ($previewInfo && !empty($previewInfo['preview_format'])) ? $previewInfo['preview_format'] : 'mp4';
		$paths = array();

		if ($previewInfo) {
			$storage = $this->getVideoPreviewStorageInfo($gal_id, $format, $previewInfo, false);
			if ($storage && !empty($storage['path'])) {
				$paths[] = $storage['path'];
			}
		}

		$legacyPath = $this->getLegacyVideoPreviewPath($gal_id, $format);
		if ($legacyPath) {
			$paths[] = $legacyPath;
		}

		$paths = array_unique(array_filter($paths));
		foreach ($paths as $path) {
			if (is_file($path) && !@unlink($path)) {
				$log = new Logger(__METHOD__ . ": preview file was not deleted '" . $path . "'", true);
				$result = false;
			}
		}

		if ($deleteRecord && $previewInfo) {
			try {
				$stmt = $this->_db->prepare("DELETE FROM galleries_video_previews WHERE gal_id = :gal_id");
				$stmt->execute(array(':gal_id' => $gal_id));
				$stmt = $this->_db->prepare("DELETE FROM video_preview_job_callbacks WHERE gal_id = :gal_id");
				$stmt->execute(array(':gal_id' => $gal_id));
				$stmt = $this->_db->prepare("DELETE FROM video_preview_jobs WHERE gal_id = :gal_id");
				$stmt->execute(array(':gal_id' => $gal_id));
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
				$result = false;
			}
		}

		return $result;
	}

	/* vCDN / CDN shit */

	private function updateGalleriesVideoCDNStatus($gal_id, $synced = true)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($synced) {
			$synced = 1;
		} else {
			$synced = 0;
		}
		if ($gal_id > 0) {
			$sql = "UPDATE galleries_videos SET cdn_synced = ? WHERE gal_id = ?";

			$db = DB::get();
			if ($db) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					$stmt->bind_param("ii", $synced, $gal_id);
					if ($stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__ . ": Размер видео не проапдейчен, GID:" . $gal_id, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
			}
		}
		return $result;
	}

	public function bulkUpdateGalleriesVideoCDNStatus($gals_ids, $synced = true)
	{
		$result = false;
		if (is_array($gals_ids)) {
			$galleries_to_process = false;
			foreach ($gals_ids as $gal_id) {
				$gal_id = (int)$gal_id;
				if ($gal_id > 0) {
					$galleries_to_process[] = $gal_id;
				}
			}
			if ($galleries_to_process) {
				if ($synced) {
					$synced = 1;
				} else {
					$synced = 0;
				}
				$sql = "UPDATE galleries_videos SET cdn_synced = ? WHERE gal_id IN (" . implode(",", $galleries_to_process) . ")";

				$db = DB::get();
				if ($db) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						$stmt->bind_param("i", $synced);
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": Размер видео не проапдейчен, GID:" . $gal_id, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
				}
			}
		}

		if ($gal_id > 0) {
		}
		return $result;
	}

	private function generateCdnCallbackUrl($gal_id)
	{
		$result = false;

		if (defined("VCDN_CALLBACK_SECRET")) {
			$secret = VCDN_CALLBACK_SECRET;
			$key = str_replace('==', '', base64_encode(md5($secret . $gal_id, 1)));
			$key = str_replace('/', '-', $key);
			$key = md5($key);

			$result = "http://" . SCRIPT_DOMAIN . "/vcdn_sync_callback.php?vid=" . $gal_id . "-" . $key;
		}

		return $result;
	}


	function generateCdnSyncUrl($gal_id)
	{
		$result = false;

		$gal_id = (int)$gal_id;
		if ($gal_id > 0) {
			$sql = "SELECT galleries.gal_md5, galleries.gal_paysite, galleries_videos.video_size
					FROM galleries_videos
					LEFT JOIN galleries ON galleries_videos.gal_id = galleries.gal_id
					WHERE galleries_videos.gal_id = " . $gal_id . ";";
			$db = DB::get();
			if ($db) {
				$stmt = $db->prepare($sql);


				if ($stmt->execute()) {
					$gal_md5 = false;
					$gal_paysite = false;
					$video_file_size = false;
					$stmt->bind_result($gal_md5, $gal_paysite, $video_file_size);
					if ($stmt->fetch()) {
						if ($video_file_size && $gal_md5 && $gal_paysite) {
							$callback_url = $this->generateCdnCallbackUrl($gal_id);
							$cdn_addition = "";
							$cdn_addition = defined("CDN_FILE_NAME_ADDITION") ? CDN_FILE_NAME_ADDITION : "";
							$result = "https://cp.ahcdn.com/api2/file/add?name=" . $cdn_addition . $gal_paysite . "/" . $gal_id . ".mp4&size=" . $video_file_size . "&location=http://" . VIDEOS_SYNC_DOMAIN . "/" . $gal_paysite . "/" . $gal_id . "/" . $gal_md5 . "/" . $gal_id . ".mp4&callback=" . $callback_url;

							$log = new Logger(__METHOD__ . ": CDN request URL " . $result);
						} else {
							$log = new Logger(__METHOD__ . ": No data found in 'galleries_videos' for GID#'" . $gal_id . "'. DB error msg: " . $db->error, true);
							echo "Fetch error";
						}
					} else {
						$log = new Logger(__METHOD__ . ": Fetch GID#" . $gal_id . " from 'galleries_videos' failed: " . $db->error, true);
						echo "Fetch error";
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			}
		}

		return $result;
	}


	public static function getVideosListFromSite($site_id)
	{
		$site_id = (int)$site_id;
		$sql = "SELECT galleries.gal_id, galleries.gal_md5, galleries.gal_paysite
				FROM site_" . $site_id . "
				LEFT JOIN galleries ON site_" . $site_id . ".gal_id = galleries.gal_id
				WHERE site_" . $site_id . ".gal_type = 'movies'";
		$db = DB::get();
		if ($db) {
			$stmt = $db->prepare($sql);
			if ($stmt->execute()) {
				$gal_id = false;
				$gal_md5 = false;
				$gal_paysite = false;
				$stmt->bind_result($gal_id, $gal_md5, $gal_paysite);
				$count = 0;
				while ($stmt->fetch()) {
					echo "<br>http://" . VIDEOS_SYNC_DOMAIN . "/" . $gal_paysite . "/" . $gal_id . "/" . $gal_md5 . "/" . $gal_id . ".mp4|" . filesize(UPLOADFOLDER . "/" . $gal_paysite . "/" . $gal_id . "/" . $gal_md5 . "/" . $gal_id . ".mp4");
					$count++;
				}
				echo "<h1>" . $count . "</h1>";
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		}
	}



	function deleteGalleryFromCdnQuery(int $gal_id)
	{
		$result = false;

		if ($gal_id > 0) {
			try {
				$sql = "DELETE FROM cdn_sync_videos	WHERE gal_id = :gal_id;";
				$stmt = $this->_db->prepare($sql);
				$stmt->execute(['gal_id' => $gal_id]);
				$result = true;
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $e->getMessage(), true);
			}
		}

		return $result;
	}


	function insertVideosToCdnQuery(int $gal_id)
	{
		$result = false;

		if ($gal_id > 0 && $this->getGalleryType($gal_id) == 'Movies') {

			$sql = "INSERT INTO cdn_sync_videos
					(gal_id, sync_added_on, file_status, status_updated_on, file_size, error_message)
					SELECT gal_id, " . time() . ", 'new', 0, 0, ''
					FROM galleries
					WHERE 
							gal_id = :gal_id
						AND 
							gal_id NOT IN (SELECT gal_id FROM 
											galleries_videos 
										   WHERE cdn_synced = 1)
						AND 
							gal_id NOT IN (SELECT gal_id FROM 
											cdn_sync_videos)";

			try {
				$stmt = $this->_db->prepare($sql);
				$stmt->execute(['gal_id' => $gal_id]);
				$log = new Logger(__METHOD__ . ": GID" . $gal_id . " quieried for CDN sync");
				$result = $this->_db->lastInsertId();
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
				return false;
			}
		}

		return $result;
	}


	function insertVideosFromSiteToCdnQuery(int $site_id)
	{
		$result = false;

		if ($site_id > 0) {
			$sql = "INSERT INTO cdn_sync_videos
							(gal_id, sync_added_on, file_status, status_updated_on, file_size, error_message)
							SELECT gal_id, " . time() . ", 'new', 0, 0, ''
							FROM site_" . $site_id . "
							WHERE 
									gal_type = 'movies' 
								AND 
									gal_id NOT IN (SELECT gal_id FROM 
													galleries_videos 
												   WHERE cdn_synced = 1)
								AND 
									gal_id NOT IN (SELECT gal_id FROM 
													cdn_sync_videos)";


			try {
				$stmt = $this->_db->prepare($sql);
				$stmt->execute();
				$result = $this->_db->lastInsertId();
			} catch (PDOException $e) {
				$log = new Logger(__METHOD__ . ": STMT error '" . $e->getMessage() . "'", true);
				return false;
			}
		} else {
			$log = new Logger(__METHOD__ . ": Site ID should be > 0", true);
		}

		return $result;
	}



	function updateCdnQueryStatus($gal_id, $status)
	{
		$result = false;
		if (preg_match("#^(new|ok|delete|request_sent|request_failed|error)$#", $status)) {
			$gal_id = (int)$gal_id;
			if ($gal_id > 0) {
				$sql = "UPDATE cdn_sync_videos SET file_status = ?, status_updated_on = ? WHERE gal_id = ?";

				$db = DB::get();
				if ($db) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						$status_updated_on = time();
						$stmt->bind_param("sii", $status, $status_updated_on, $gal_id);
						if ($stmt->execute()) {
							$result = true;
							if ($status == 'ok') {
								$this->updateGalleriesVideoCDNStatus($gal_id, true);
								$log = new Logger(__METHOD__ . ": #" . $gal_id . ", sync status set to OK");
							}
						} else {
							$log = new Logger(__METHOD__ . ": Размер видео не проапдейчен, GID:" . $gal_id, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": No SQL connect. ", true);
				}
			}
		}
		return $result;
	}

	function getVideosToCdnSyncCount()
	{
		$sql = "SELECT count(gal_id) FROM cdn_sync_videos WHERE file_status = 'new'";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$gal_id_count = false;
						$stmt->bind_result($gal_id_count);
						$stmt->fetch();
						return $gal_id_count;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return false;
	}

	function getAllCdnVideosFromQuery()
	{
		$result = false;
		$sql = "SELECT gal_id, file_status, sync_added_on 
				FROM cdn_sync_videos 
				ORDER BY sync_added_on DESC";
		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$gal_id = false;
						$file_status = false;
						$sync_added_on = false;
						$stmt->bind_result($gal_id, $file_status, $sync_added_on);
						while ($stmt->fetch()) {
							$result[] = compact("gal_id", "file_status", "sync_added_on");
						}

						return $result;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		return false;
	}

	function getCdnVideoToSync()
	{

		$gal_id = 0;
		$sql = "SELECT gal_id FROM cdn_sync_videos WHERE file_status = 'new'  ORDER BY sync_added_on DESC";

		try {
			$stmt = $this->_db->prepare($sql);
			$stmt->execute();
			$stmt->bindColumn('gal_id', $gal_id);
			$stmt->fetch();
			// var_dump($gal_id);
			return $gal_id;
		} catch (PDOException $e) {
			new Logger(__METHOD__ . ": Ошибка БД: " . $e->getMessage(), true);
		}
		return false;
	}

	function getCdnVideoStatusFromQuery(int $gal_id)
	{

		if ($gal_id > 0) {
			$sql = "SELECT gal_id, file_status, sync_added_on FROM cdn_sync_videos WHERE gal_id = " . $gal_id . ";";
			try {
				$file_status = null;
				$sync_added_on = null;
				$stmt = $this->_db->prepare($sql);
				$stmt->execute();
				$stmt->bindColumn('gal_id', $gal_id);
				$stmt->bindColumn('file_status', $file_status);
				$stmt->bindColumn('sync_added_on', $sync_added_on);
				if ($stmt->fetch()) {
					return compact("gal_id", "file_status", "sync_added_on");
				}
			} catch (PDOException $e) {
				new Logger(__METHOD__ . ": Ошибка БД: " . $e->getMessage(), true);
			}
		}

		return false;
	}

	function syncCdnVideo($gal_id)
	{
		$result = false;

		$sync_url = $this->generateCdnSyncUrl($gal_id);

		// return;
		if ($sync_url) {
			$process = curl_init($sync_url);

			$headers = array(
				'Authorization: Basic ' . base64_encode(CDN_SYNC_USER . ":" . CDN_SYNC_PASS),
				'Cache-Control: max-age=0',

			);
			// curl_setopt($process, CURLOPT_POST, 1);
			curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($process, CURLOPT_HEADER, 1);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
			$return = curl_exec($process);
			// var_dump($sync_url,$process, $return);
			curl_close($process);

			if (strpos($return, "status:	ok") !== false) {
				echo "<font color=green>OK</font>";
				$log = new Logger(__METHOD__ . ": #" . $gal_id . ", request responce - ok");
				$result = 'request_sent';
			} elseif (strpos($return, "already exists and have same size") !== false || strpos($return, "already exists in state") !== false) {
				echo "<font color=green>Already synced</font>";
				$log = new Logger(__METHOD__ . ": #" . $gal_id . ", request responce - file exists on storage");
				$this->updateCdnQueryStatus($gal_id, 'ok');
				$result = 'ok';
			} else {
				echo "<b><font color=green>NOT OK</font></b>";
				$log = new Logger(__METHOD__ . ": #" . $gal_id . ", request responce - ERROR");
				$this->updateCdnQueryStatus($gal_id, 'request_failed');
			}

			// var_dump($return);
		} else {
			echo "<b><font color=green>NOT OK</font></b>";
			$log = new Logger(__METHOD__ . ": #" . $gal_id . ", request responce - ERROR - generateCdnSyncUrl() Failed");
			$this->updateCdnQueryStatus($gal_id, 'request_failed');
		}
		return $result;
	}

	function processSyncCdnQuery($gal_id = false)
	{
		if (!$gal_id) {
			$gal_id = $this->getCdnVideoToSync();
		}


		if ($gal_id > 0) {
			echo "#Processing GID#" . $gal_id . ";#";
			$gal_type = $this->getGalleryType($gal_id);
			if ($gal_type == 'Movies') {
				$log = new Logger(__METHOD__ . ": syncCdnVideo #" . $gal_id);
				$sync_result = $this->syncCdnVideo($gal_id);
				if ($sync_result == 'request_sent') {
					$log = new Logger(__METHOD__ . ": syncCdnVideo #" . $gal_id . ": Request sent");
					$this->updateCdnQueryStatus($gal_id, 'request_sent');
				} elseif ($sync_result == 'ok') {
					$log = new Logger(__METHOD__ . ": syncCdnVideo #" . $gal_id . ": Already synced");
				} else {
					$log = new Logger(__METHOD__ . ": syncCdnVideo #" . $gal_id . ": Request failed");
				}
			} else {
				$this->deleteGalleryFromCdnQuery($gal_id);
				$log = new Logger(__METHOD__ . ": syncCdnVideo #" . $gal_id . " removed from sync - has type '" . $gal_type . "'", true);
			}
		}
	}




	/* END 2017-02-01 */

	private function makeModelThumb($imageId, $width, $height)
	{
		return $this->makeRssThumb($imageId, $width, $height, 'Pics', false, true);
	}

	private function makeRssThumb($imageId, $width, $height, $type, $crop = false, $model = false, $horiz_thumbs_resize = false)
	{
		$result = false;

		if ($model) $imageOrig = $this->getModelImage($imageId);
		else $imageOrig = $this->getImage($imageId);

		if ($width && ($height || $horiz_thumbs_resize) && $imageOrig && ($type == 'Pics' || $type == 'Movies' || $type == 'embed')) {
			$imageOrig = UPLOADFOLDER . $imageOrig;
			$resizer = new Resizer();
			if ($crop && isset($crop['IM']) && isset($crop['quality']) && isset($crop['top']) && isset($crop['bottom']) && isset($crop['left']) && isset($crop['right'])) {
				if ($horiz_thumbs_resize) {
					$image = $resizer->ResizeThumb($imageOrig, $width, $crop['IM'], $crop['quality'], $crop['top'], $crop['bottom'], $crop['left'], $crop['right']);
					$this->horiz_resize_ratio = getImageRatio($image);
				} else {
					$image = $resizer->CropThumb($imageOrig, $width, $height, $crop['IM'], $crop['quality'], $crop['top'], $crop['bottom'], $crop['left'], $crop['right']);
				}
			} else {
				if ($horiz_thumbs_resize) {
					$image = $resizer->ResizeThumb($imageOrig, $width);
					$this->horiz_resize_ratio = getImageRatio($image);
				} else {
					$image = $resizer->CropThumb($imageOrig, $width, $height);
				}
			}
			if ($image) {
				if ($model) $thumbFolder = $this->makeModelThumbsFolder($imageId, $width, $height);
				else {
					if ($type == 'embed') $thumbFolder = $this->makeRssThumbsFolder($imageId, $width, 'Movies');
					else $thumbFolder = $this->makeRssThumbsFolder($imageId, $width, $type, $horiz_thumbs_resize);
				}
				if ($thumbFolder) {
					if (copy($image, UPLOADFOLDER . "/" . $thumbFolder . "/" . $imageId . ".jpg")) {
						@chmod(UPLOADFOLDER . "/" . $thumbFolder . "/" . $imageId . ".jpg", 0777);
						$result = true;
					} else {
						$result = false;
						$error = new Logger(__CLASS__ . "|" . __FUNCTION__ . "|" . __METHOD__ . ":  thumbId: " . $imageId . ", тумба не сделана - ошибка копирования: " . $image . " в " . UPLOADFOLDER . "/" . $thumbFolder . "/" . $this->thumbId . ".jpg");
						return $result;
					}
				}
			} else {
				$result = false;
				$error = new Logger(__CLASS__ . "|" . __FUNCTION__ . "|" . __METHOD__ . ":  thumbId: " . $imageId . ", тумба не сделана - ошибка кропа");
				return $result;
			}
		}
		return $result;
	}

	//
	//
	//
	private function makeFoldersForGallery($galId, $thumbs = false)
	{
		$result = false;
		$uploadImagesFolder = $this->galleryContentFolder($galId);
		if ($thumbs) $uploadImagesFolder .= "/thumbs";
		$uploadFoldersTree = explode("/", $uploadImagesFolder);
		$tempFolderToMake = "";
		if (is_array($uploadFoldersTree)) {
			foreach ($uploadFoldersTree as $folderToMake) {
				if ($folderToMake !== "") {
					$tempFolderToMake .= "/" . $folderToMake;
					if (!@is_dir(UPLOADFOLDER . $tempFolderToMake)) {
						if (mkdir(UPLOADFOLDER . $tempFolderToMake, 0777) or die("Не могу создать директорию " . UPLOADFOLDER . $tempFolderToMake)) {
							chmod(UPLOADFOLDER . $tempFolderToMake, 0777);
						}
					}
				}
				if ($tempFolderToMake !== "") $result = $tempFolderToMake;
			}
		}
		return $result;
	}

	private function getHorizThumbPath($image_id, $width = 300)
	{
		$result = false;
		$width = (int)$width;
		$result = UPLOADFOLDER . "/thumbs/x" . $width . "/" . folderNameById($image_id) . "/" . $image_id . ".jpg";

		return $result;
	}

	public function getMaxBitrate($gal_id)
	{
		$result = false;
		$gal_id = (int)$gal_id;
		if ($gal_id) {
			$sql = "SELECT paysites.max_bitrate FROM galleries
  					INNER JOIN paysites ON galleries.gal_paysite = paysites.paysite_id
  					WHERE galleries.gal_id = ?;";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						$stmt->bind_param("i", $gal_id);
						if ($stmt->execute()) {
							$stmt->bind_result($result);
							$stmt->fetch();
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": неверный GAL_ID (символьный или отсутствует)", true);
		}
		return $result;
	}

	private function makeRssThumbsFolder($thumbId, $width, $type, $horiz_thumbs_resize = false)
	{
		$result = false;
		$imageId = $thumbId;
		if (
			$imageId != 0
			&& ($width == 150 || $width == 180 || $width == 200 || $width == 240 || $width == 320 || $width == 300 || $width == 600 || $width == 800)
			&& ($type == 'Pics' || $type == 'Movies')
		) {

			if ($horiz_thumbs_resize) {
				$uploadImagesFolder = "thumbs/x" . $width . "/" . folderNameById($imageId);
			} else {
				if ($type == 'Pics') $type = 'p';
				else $type = 'm';
				$uploadImagesFolder = "thumbs/" . $type . "/" . $width . "/" . folderNameById($imageId);
			}

			$uploadFoldersTree = explode("/", $uploadImagesFolder);
			$tempFolderToMake = "";
			if (is_array($uploadFoldersTree)) {
				foreach ($uploadFoldersTree as $folderToMake) {
					if ($folderToMake !== "") {
						$tempFolderToMake .= "/" . $folderToMake;
						if (!@is_dir(UPLOADFOLDER . $tempFolderToMake)) {
							if (mkdir(UPLOADFOLDER . $tempFolderToMake, 0777) or die("Не могу создать директорию " . UPLOADFOLDER . $tempFolderToMake)) {
								chmod(UPLOADFOLDER . $tempFolderToMake, 0777);
							}
						}
					}
					if ($tempFolderToMake !== "") $result = $tempFolderToMake;
				}
			}
		}
		return $result;
	}

	private function makeModelThumbsFolder($thumbId, $width, $height)
	{
		$result = false;
		$imageId = $thumbId;
		if ($imageId && (($width == 150 && $height == 200)
			||  ($width == 180 && $height == 240)
			||  ($width == 240 && $height == 320)
			||  ($width == 200 && $height == 150)
			||  ($width == 240 && $height == 180)
			||  ($width == 320 && $height == 240))) {
			$uploadImagesFolder = folderNameById($imageId);
			$uploadImagesFolder = "models/" . $width . "x" . $height . "/" . folderNameById($imageId);

			$uploadFoldersTree = explode("/", $uploadImagesFolder);
			$tempFolderToMake = "";
			if (is_array($uploadFoldersTree)) {
				foreach ($uploadFoldersTree as $folderToMake) {
					if ($folderToMake !== "") {
						$tempFolderToMake .= "/" . $folderToMake;
						if (!@is_dir(UPLOADFOLDER . $tempFolderToMake)) {
							if (mkdir(UPLOADFOLDER . $tempFolderToMake, 0777) or die("Не могу создать директорию " . UPLOADFOLDER . $tempFolderToMake)) {
								chmod(UPLOADFOLDER . $tempFolderToMake, 0777);
							}
						}
					}
					if ($tempFolderToMake !== "") $result = $tempFolderToMake;
				}
			}
		}
		return $result;
	}
	//
	//
	//  



	private function galleryContentFolder($id)
	{
		$result = false;
		$paysiteId = $this->getGalleryPaysite($id);
		if ($paysiteId) {
			$md5 = $this->getGalleryMD5($id);
			if ($md5) {
				$result = "/" . $paysiteId . "/" . $id . "/" . $md5;
			} else {
				$log = new Logger(__METHOD__ . ":ID:" . $id . ", невозможно получить md5 галлереии из базы данных", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ":ID:" . $id . ". Невозможно получить PaysiteId: this->getGalleryPaysite", true);
		}
		return $result;
	}


	public function uploadModelImage($id, $images, $layout)
	{
		if (!$layout || !preg_match("#^(horiz|vertic)$#", $layout)) {
			$log = new Logger(__METHOD__ . " неверно указан лэйаут для изображения модели");
			return false;
		}
		if (!is_array($images)) {
			$images = array($images);
		}
		return $this->uploadImages($id, $images, false, $layout, true);
	}

	private function setModelImageStatus($image_id, $status)
	{
		$image_id = intval($image_id);
		$result = false;
		if ($image_id && preg_match("#^(new|uploaded|upload_error|crop_error|cropped)$#", $status)) {
			$sql = "UPDATE models_images SET status = '" . $status . "' WHERE image_id = '" . $image_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) $result = true;
			else $log = new Logger(__METHOD__ . " ошибка апдейта тстатуса тумбы модели: '" . $sql . "'", true);
		}
		return $result;
	}

	private function uploadImages($id, $content, $video = false, $layout = false, $model = false)
	{
		$id = intval($id);
		$result = false;

		// echo "<h2>".$video."</h2>";
		// var_dump($id, $content, $video, $layout, $model);
		// echo "<h2>".$video." FIN</h2>";

		if ($video && $video == 'Pics') {
			$gal_type = 'Pics';
			$video = false;
		} elseif ($video && $video == 'gif') {
			$gal_type = 'gif';
			$video = false;
		} elseif ($video && $video == 'embed') {
			$gal_type = 'embed';
			$video = false;
		} else {
			$gal_type = 'Movies';
			$video = false;
		}

		if (is_array($content) && count($content) > 0) {
			$uploadImagesFolder = ($model) ? "/models/original/" . intval($id) : $this->galleryContentFolder($id);

			// var_dump($uploadImagesFolder);
			if ($uploadImagesFolder) {

				$FTP = new FTPtools(FTP, FTPUSER, FTPPW);

				foreach ($content as $tempFile) {
					//$tempFile = WRKDIR.ZIP_FOLDER.$tempFile;
					$file_exists = is_file($tempFile) ? " существует" : " не существует";
					// echo "Файл: '". $tempFile ."':". $file_exists. "\n";
					if (is_file($tempFile)) {
						$fileMd5 = md5_file($tempFile);
						$image_md5_check = $this->checkImageMd5($fileMd5, $model);
						if (!$image_md5_check) {
							if ($model) {
								$image_id = $this->insertModelImage($id, $fileMd5, $layout);
								$fileName = $image_id . ".jpg";
							} else {

								$image_id = $this->insertImage($id, $fileMd5);
								$fileName = $image_id . ".jpg";
							}

							if ($image_id || $video) {

								if (FTP == 'localhost' || $video) {
									if (!$video) {
										$uploadedImage = $FTP->copyFileToLocal($tempFile, $fileName, $uploadImagesFolder);

										if ($uploadedImage !== false) {
											if ($model) {
												$image_uploaded = $this->setModelImageUploaded($image_id);

												if ($layout == 'vertic' && !$this->makeModelThumb($image_id, 150, 200)) {
													$this->setModelImageStatus($image_id, 'crop_error');
													$log = new Logger(__METHOD__ . " не возмоно кропнуть тумбу модели, " . $image_id, true);
													return false;
												}
												if ($layout == 'vertic' && !$this->makeModelThumb($image_id, 180, 240)) {
													$this->setModelImageStatus($image_id, 'crop_error');
													$log = new Logger(__METHOD__ . " не возмоно кропнуть тумбу модели, " . $image_id, true);
													return false;
												}
												if ($layout == 'vertic' && !$this->makeModelThumb($image_id, 240, 320)) {
													$this->setModelImageStatus($image_id, 'crop_error');
													$log = new Logger(__METHOD__ . " не возмоно кропнуть тумбу модели, " . $image_id, true);
													return false;
												}
												if ($layout == 'horiz' && !$this->makeModelThumb($image_id, 200, 150)) {
													$this->setModelImageStatus($image_id, 'crop_error');
													$log = new Logger(__METHOD__ . " не возмоно кропнуть тумбу модели, " . $image_id, true);
													return false;
												}
												if ($layout == 'horiz' && !$this->makeModelThumb($image_id, 240, 180)) {
													$this->setModelImageStatus($image_id, 'crop_error');
													$log = new Logger(__METHOD__ . " не возмоно кропнуть тумбу модели, " . $image_id, true);
													return false;
												}
												if ($layout == 'horiz' && !$this->makeModelThumb($image_id, 320, 240)) {
													$this->setModelImageStatus($image_id, 'crop_error');
													$log = new Logger(__METHOD__ . " не возмоно кропнуть тумбу модели, " . $image_id, true);
													return false;
												}

												$result = $image_id;
											} else {
												$image_uploaded = $this->setImageUploaded($image_id, $uploadedImage);
											}

											if (!$image_uploaded) {
												if (!$model) $log = new Logger(__METHOD__ . ":Ошибка изменения изображения GalId:" . $id . ", Image ID: " . $image_id . " в таблице изображений. Путь к изображению на сервере: " . UPLOADFOLDER . "/" . $uploadImagesFolder, true);
												else $log = new Logger(__METHOD__ . ":Ошибка изменения изображения Model_id:" . $id . ", Image ID: " . $image_id . " в таблице изображений. Путь к изображению на сервере: " . UPLOADFOLDER . "/" . $uploadImagesFolder, true);
											} else {
												//													$log = new Logger(__METHOD__.":Изображение ".$tempFile." залито в папку ".UPLOADFOLDER ."/".$uploadImagesFolder.", ID изображения ".$image_id);
												$result = $image_id;
											}
										} else {
											$this->removeImage($image_id, $gal_type);
											$log = new Logger(__METHOD__ . ":Ошибка аплоада изображений " . $tempFile . " в папку " . UPLOADFOLDER . "/" . $uploadImagesFolder, true);
										}
									} else {
										echo "Здесь должна быть закачка готового видео в нужную папку!<br>";
									}
								} else {
									$this->removeImage($image_id, $gal_type);
									$log = new Logger(__METHOD__ . ":Ошибка аплоада изображения в галере ID:" . $id . ". Попытка залить на удаленный сервер", true);
								}
							} else {
								$log = new Logger(__METHOD__ . ":Ошибка аплоада изображения в галере ID:" . $id . ". Изображение не добавлено в базу данных", true);
							}
						} else {
							$log = new Logger(__METHOD__ . ":Ошибка аплоада изображения в галере ID:" . $id . ". Найдено идентичное изображение. OIMGID#" . $image_md5_check, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ":Ошибка аплоада изображения в галере ID:" . $id . ". Невозможно получить md5 галлереии из базы данных", true);
					}
				}
			} else {
				$log = new Logger(__METHOD__ . ":Ошибка аплоада изображений. ID:" . $id . ". невозможно получить папку - ID галлереи неверный, либо не коннект к базе", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ":Ошибка аплоада изображений. ID:" . $id . ". Параметр content не является массивои, или массив нулевой.", true);
		}
		return $result;
	}

	private function processImagesFromTemp($id, $images, $type = 'Pics', $layout = false)
	{
		sort($images);
		$layout = 'horiz';
		if ($this->uploadImages($id, $images, $type)) {
			if ($type == 'Pics') {
				$this->setStatus($id, 'fetched');
				$this->setContentType($id, 'Pics');
			}
			$log = new Logger(__METHOD__ . ":Unzipped: #" . $id . " Galleries->processImagesFromTemp() архив распакован");
			return true;
		} else {
			$log = new Logger(__METHOD__ . ":Ошибка аплоада файлов!!", true);
			$this->setStatus($id, 'fetching_fail');
		}
		return false;
	}

	private function processGifFromTemp(int $gal_id, $video, $mainGallery = false)
	{
		// если видео, то просто копиируется в TMPDIR. Название файла - $id + ".tmp";
		$result = false;

		if ($gal_id > 0) {
			if (is_file($video)) {
				$fileMd5 = md5_file($video);
				//if($this->checkImageMd5($fileMd5)) {
				$FTP = new FTPtools(FTP, FTPUSER, FTPPW);

				if ($galleryFolder = $this->galleryContentFolder($gal_id)) {
					$layout = 'horiz';
					$image_extractor = new GifFrameExtractor();

					$images = $image_extractor->extract($video);

					if ($images) {
						$counter = 0;
						$temp_gif_frames = false;

						foreach ($images as $image) {
							$counter++;
							if (isset($image['image']) && get_resource_type($image['image']) == "gd") {
								$gif_frame_filename = TMPDIR . "/" . $counter . "-" . getmypid() . "-" . time() . ".jpg";
								if (imagejpeg($image['image'], $gif_frame_filename)) {
									list($width, $height, $type, $attr) = getimagesize($gif_frame_filename);
									$layout = (($width / $height) >= 1) ? 'horiz' : 'vertic';
									imagedestroy($image['image']);
									$temp_gif_frames[] = $gif_frame_filename;
								} else {
									$log = new Logger(__METHOD__ . ":GIF #" . $gal_id . " Фрейм GIF'а #" . $counter . ", '" . $gif_frame_filename . "'' не создан, ошибка 	'imagejpeg'", true);
								}
							}
						}

						if ($temp_gif_frames && is_array($temp_gif_frames)) {

							$old_filename = $video;
							$new_filename = dirname($video) . "/" . $gal_id . ".gif";
							rename($old_filename, $new_filename);
							$video = $new_filename;

							if ($FTP->UploadToLocal($video, $galleryFolder)) {
								$this->processImagesFromTemp($gal_id, $temp_gif_frames);
								$this->setContentType($gal_id, 'gif', $layout);
								unlink($video);
								$this->setStatus($gal_id, 'ok');
								$this->updateCroppedStatus($gal_id, 1);
								// $this->insertImage($gal_id, $fileMd5);
								$log = new Logger(__METHOD__ . ":GIF #" . $gal_id . ". Добавлен!");
								$result = true;
							} else {
								$this->setStatus($gal_id, 'video_fail');
								$log = new Logger(__METHOD__ . ":GIF #" . $gal_id . " Gif не скопирован в папку '" . $galleryFolder . "'", true);
								if (is_file($video)) unlink($video);
							}
						} else {
							$this->setStatus($gal_id, 'video_fail');
							$log = new Logger(__METHOD__ . ":GIF #" . $gal_id . " Gif не разбит на изображения'", true);
							if (is_file($video)) unlink($video);
						}
					} else {
						$this->setStatus($gal_id, 'video_fail');
						$log = new Logger(__METHOD__ . ":GIF #" . $gal_id . " Ошибка разборки гифа на фреймы", true);
						unlink($video);
					}
				} else {
					$this->setStatus($gal_id, 'video_fail');
					$log = new Logger(__METHOD__ . ":GIF #" . $gal_id . " Ошибка Galleries->galleryContentFolder", true);
					unlink($video);
				}
				/*} else {
								$this->setStatus($gal_id, 'fetching_fail');
		  						$log = new Logger(__METHOD__.":GIF #".$gal_id." такой же гиф уже есть", true);
		  						unlink($video);
							}*/
			} else {
				$log = new Logger(__METHOD__ . ":Галлерея: #" . $gal_id . " " . __METHOD__ . ", " . $video . " Не файл!", true);
				$this->setStatus($gal_id, 'unzip_fail');
			}
		} else {
			$log = new Logger(__METHOD__ . ":Не получено ID для обработки видео!", true);
		}
		return $result;
	}

	private function processVideoFromTemp($newId, $video, $mainGallery = false)
	{
		// если видео, то просто копиируется в TMPDIR. Название файла - $id + ".tmp";
		$result = false;
		$newId = intval($newId);
		if ($newId) {
			if (is_file($video)) {
				$uploadVideoFile = TMPDIR . "/" . $newId . ".tmp";
				if (rename($video, $uploadVideoFile)) {
					chmod($uploadVideoFile, 0777);
					$this->setContentType($newId, 'Movies');
					$this->setStatus($newId, 'fetched');
					if ($mainGallery) $this->setMainGallery($newId, $mainGallery);
					$this->addToQuery($newId);
					$log = new Logger(__METHOD__ . ":Галлерея: #" . $newId . " " . __METHOD__ . " видео скопировано в " . $uploadVideoFile);
					$result = true;
				} else {
					$log = new Logger(__METHOD__ . ":Галлерея: #" . $newId . " " . __METHOD__ . ", " . $video . " не  возможно перенести в " . $uploadVideoFile, true);
					$this->setStatus($newId, 'unzip_fail');
				}
			} else {
				$log = new Logger(__METHOD__ . ":Галлерея: #" . $newId . " " . __METHOD__ . ", " . $video . " Не файл!", true);
				$this->setStatus($newId, 'unzip_fail');
			}
		} else {
			$log = new Logger(__METHOD__ . ":Не получено ID для обработки видео!", true);
		}
		return $result;
	}

	public function getVideoGalleryToProcess($status)
	{
		$result = false;

		if (!preg_match("#(fetched|screened)#", $status)) {
			$log = new Logger(__METHOD__ . ": Неверный статус, не входит в (fetched|screened)", true);
		}

		$sql = "SELECT gal_id FROM galleries WHERE gal_status = '" . $status . "' 
  				AND gal_type = 'Movies' ";

		if ($status == 'screened') $sql .= " AND gal_content_count > 0 ";

		$sql .=	" LIMIT 1";

		if ($db = DB::get()) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$stmt->bind_result($result);
						$stmt->fetch();
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->errorInfo(), true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	public function getGalleryToFetch()
	{
		return $this->getVideoGalleryToProcess('fetched');
	}

	public function getGalleryToVideoConvert()
	{
		return $this->getVideoGalleryToProcess('screened');
	}

	public function makeScreenshots($id = false)
	{

		if ($id === false) {
			$id = $this->getGalleryToFetch();
		}

		$id = (int)$id;
		$db = DB::get();

		if ($db) {
			if ($id) {

				$originalPath = TMPDIR . "/" . $id . ".tmp";
				if (is_file($originalPath)) {
					$FTP = new FTPtools(FTP, FTPUSER, FTPPW);
					echo "status setting";
					if ($this->setStatus($id, 'video_screening')) {


						$video = new VideoUtils("temp");
						DB::closeConnection();
						$screenshots = $video->makeScreenshots($originalPath, $id);
						$duration = $video->videoDuration();
						DB::setConnection();

						// удаление папки в тмп сделать

						if ($screenshots) {
							if ($this->processImagesFromTemp($id, $screenshots, 'Movies')) {
								foreach ($screenshots as $screenshot) {
									unlink($screenshot);
								}
								$this->updateDuration($id, $duration);
								$this->setStatus($id, 'screened');
								$this->addToQuery($id);
								if (is_dir(TMPDIR . "/" . $id)) @rmdir(TMPDIR . "/" . $id);
								$log = new Logger(__METHOD__ . ":Video #" . $id . ". Изображения получены успешно!");
							} else {
								$this->setStatus($id, 'screen_fail');
								$log = new Logger(__METHOD__ . ":Video #" . $id . " Изображения не скопированы в upload папку", true);
							}
						} else {
							$this->setStatus($id, 'screen_fail');
							$log = new Logger(__METHOD__ . ":Video #" . $id . " Изображения из видео не получены. Видео файл: " . $originalPath, true);
						}
					}
				} else {
					$this->setStatus($id, 'fetched');
					$this->addToQuery($id);
					$log = new Logger(__METHOD__ . ":ZIP #" . $id . " Galleries->makeScreenshots(). Невозможно переключить Статус. Процесс уже в таблице или ошибка базы данных.", true);
				}
			} else {
				$this->setStatus($id, 'screen_fail');
				$log = new Logger(__METHOD__ . ":Video #" . $id . " Изображения из видео не разобраны.", true);
			}
		} else {
			$log = new Logger(__CLASS__ . ":" . __METHOD__ . ": ошибка базы данных", true);
		}
	}




	public function processVideo($id = false)
	{
		$result = false;
		if (!$id) {
			$id = $this->getGalleryToVideoConvert();
		}
		$id = intval($id);
		if ($id) {
			if ($this->setStatus($id, 'video_converting')) {
				$originalPath = TMPDIR . "/" . $id . ".tmp";
				$outputPath = TMPDIR . "/" . $id . ".mp4";
				if (is_file($originalPath)) {
					$max_bitrate = $this->getMaxBitrate($id); // максимальный битрейт для платника
					$video = new VideoUtils("temp");
					// $outputVideo = $video->convertVideo($originalPath, $outputPath);
					DB::closeConnection();
					$outputVideo = $video->convertVideoBitrate($originalPath, $outputPath, $max_bitrate);
					DB::setConnection();
					if (is_file($outputVideo)) {
						$duration = $video->GetDuration($outputVideo);
						$this->updateDuration($id, $duration);
						$FTP = new FTPtools(FTP, FTPUSER, FTPPW);
						$galleryFolder = $this->galleryContentFolder($id);
						if ($galleryFolder) {
							if ($FTP->UploadToLocal($outputVideo, $galleryFolder)) {
								unlink($originalPath);
								unlink($outputVideo);
								$this->setStatus($id, 'grabbed');
								$this->addToQuery($id);
								$log = new Logger(__METHOD__ . ":Video #" . $id . ". Видео сконверчено!");
							} else {
								$this->setStatus($id, 'video_fail');
								$log = new Logger(__METHOD__ . ":Video #" . $id . " Видео не скопировано в upload папку ", true);
								if (is_file($outputVideo)) unlink($outputVideo);
							}
						} else {
							$this->setStatus($id, 'video_fail');
							$log = new Logger(__METHOD__ . ":Video #" . $id . " Ошибка Galleries->galleryContentFolder", true);
							unlink($outputVideo);
						}
					} else {
						$this->setStatus($id, 'video_fail');
						$log = new Logger(__METHOD__ . ":Video #" . $id . " Видео не сконверчено, вероятно это не видео файл: " . $originalPath, true);
					}
				} else {
					$this->setStatus($id, 'video_fail');
					$log = new Logger(__METHOD__ . ":Video #" . $id . " Не найден файл " . $originalPath, true);
				}
			} else {
				$this->setStatus($id, 'screened');
				$this->addToQuery($id);
				$log = new Logger(__METHOD__ . ":ZIP #" . $id . " Galleries->processVideo(). Невозможно переключить Статус. Процесс уже в таблице или ошибка базы данных.", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ":Нет галер Movies в статусе screened");
		}
		return $result;
	}

	/*
Добавление галлерей на объединение:

Входящие данные:
v	Массив $id галлерей для объединения
v	Первый элемент является $id под который будет происходить объединение

Работа:
	1. Проверка входящих данных:
		- проверка массива на наличие неверных данных
v		- присвоение $mainGalId значения 0го элемента массива
	2. Проверка состояния галлерей из массива.
		- проверка статусов
		- если хотябы один элемент находится в отличном от необъодимого статусе, весь процесс прекращается, записывается ошибка в лог. 
		N.B. Логи разрешить к показу из файла.
	3. Добавление галлерей в очередь на объединение.
v		- проверка размера основного видео
		- добавление галлерей в очередь объединения.
		- в таблицу galleries_to_merge они добавляются со статусом new, и каждой галлерее добавляется размер основного файла (width, height)
		- каждой галлерее присваеватся статус to_merge в основной таблице	
*/

	private function getPreMergingStatus($id)
	{
		$result = false;
		$id = intval($id);
		if ($id) {
			$sql = "SELECT galleries_original_status FROM galleries_to_merge WHERE merge_galleries = '" . $id . "'";
			$rs = $this->_db->query($sql);
			if ($rs === false) {
				print __METHOD__ . ': error grabbing: ' . $this->_db->errorInfo() . '<BR>';
				$log = new Logger(__METHOD__ . ":Ошибка выборки galleries_original_status галлереи " . $id . " из базы данных: " . $this->_db->errorInfo(), true);
			} else {
				$status = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($status) $result = $status[0]['galleries_original_status'];
			}
		}
		return $result;
	}

	private function getVideoFilePath($id, $status = false)
	{
		//
		//  при объединении видео, из-за to_merge в galleries не возможно собрать файл => перенести его в таблицу galleries_to_grab
		//  выставляя оригинальны статусы galleries перед добавлением в таблицу
		//
		$result = false;
		$gallery = intval($id);
		// echo "Галера: <br>";
		// var_dump($gallery);
		// echo "<br>";
		if (!$status) {
			$status = $this->getGalleryStatus($gallery);
		}

		if ($status == 'to_merge') {
			$status = $this->getPreMergingStatus($gallery);
		}
		// echo "Статус: <br>";
		// var_dump($status);
		// echo "<br>";

		$videoFile = false;

		if ($status === false) {
			echo "Галлерея #" . $gallery . " не существует, или не возможно получить ее статус<br>";
			$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " Галлерея #" . $gallery . " не существует, или не возможно получить ее статус", true);
			return false;
		} elseif (preg_match('/^(screened|fetched|video_converted|grabbed|uploaded|OK)$/im', $status)) {
			switch ($status) {
				case 'fetched':
				case 'screened':
					$videoFile = $originalPath = TMPDIR . "/" . $gallery . ".tmp";
					break;
				case 'uploaded':
				case 'OK':
				case 'grabbed':
				case 'video_converted':
					$videoFile = UPLOADFOLDER . $this->galleryContentFolder($gallery) . "/" . $gallery . ".mp4";
					break;
			}
			// var_dump($videoFile);
			if (!is_file($videoFile)) return false;
			else $result = $videoFile;
			// echo __METHOD__.":\n";
			// var_dump($result);
		}
		return $result;
	}
	/*
(mysqli): SELECT gal_status FROM galleries WHERE gal_id = '44180'   Статус:
string(8) "screened"
(mysqli): INSERT INTO galleries_to_merge (gal_id, merge_galleries, width, height, status, added, status_change_time) VALUES ('44180', '44180', '640', '360', 'new', '1363949446', '1363949446')   (mysqli): SELECT gal_status FROM galleries WHERE gal_id = '44180'   (mysqli): UPDATE galleries SET gal_status = 'to_merge', status_change_time = '1363949446' WHERE gal_id = '44180'   Галера:
int(44181)
(mysqli): SELECT gal_status FROM galleries WHERE gal_id = '44181'   Статус:
string(7) "fetched"
(mysqli): INSERT INTO galleries_to_merge (gal_id, merge_galleries, width, height, status, added, status_change_time) VALUES ('44180', '44181', '640', '360', 'new', '1363949446', '1363949446')   (mysqli): SELECT gal_status FROM galleries WHERE gal_id = '44181'   (mysqli): UPDATE galleries SET gal_status = 'to_merge', status_change_time = '1363949446' WHERE gal_id = '44181'   

проапдейтить процедуру так, чтобы менялся статус на to_merge в galleries, когда все 100% успешно!!!

*/
	private function galleriesExist($galleries)
	{
		if (is_array($galleries)) {
			foreach ($galleries as $gallery) {
				$gallery = intval($gallery);
				$sql = "SELECT gal_id FROM galleries WHERE gal_id = '" . $gallery . "'";
				$rs = $this->_db->query($sql);
				if ($rs) {
					$images = $rs->fetchAll(\PDO::FETCH_ASSOC);
					if (!$images) {
						return false;
					}
				} else return false;
			}
		} else {
			return false;
		}
		return true;
	}

	public function mergeGalleries($galleries)
	{
		$mainGallery = false;
		$width = false;
		$height = false;
		$videoFile = "";

		//$galleries = array(44177,44178);
		if (is_array($galleries) && count($galleries) > 1 && $this->galleriesExist($galleries)) {
			foreach ($galleries as $gallery) {
				// проверка галеры на существование + видео + нужный статус
				$videoFile = $this->getVideoFilePath($gallery);
				//var_dump($videoFile);
				if ($videoFile === false) {
					if ($mainGallery) $this->galleryToMergeError($mainGallery);
					echo "Галлерея #" . $gallery . " видео файл " . $videoFile . " не найден";
					$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " Галлерея #" . $gallery . " видео файл " . $videoFile . " не найден", true);
					return false;
				}
				// проверка есть ли mainGallery в galleries_to_merge
				if (!$mainGallery && !$width && !$height) {
					$mainGallery = intval($gallery);
					$video = new VideoUtils("temp");
					$picSize = $video->GetSize($videoFile);
					if (is_array($picSize) && isset($picSize[0]) && isset($picSize[1]) && intval($picSize[0]) && intval($picSize[1])) {
						$width = intval($picSize[0]);
						$height = intval($picSize[1]);
					} else {
						echo "Галлерея #" . $gallery . " не посчитать размер фрейма видео файла " . $videoFile;
						$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " Галлерея #" . $gallery . " не посчитать размер фрейма видео файла " . $videoFile, true);
						// не возможно посчитать размер фрейма видео файла
						return false;
					}
				}
				if ($mainGallery && $gallery && $width && $height) {
					// yесли уже есть главная галера и размеры видео
					$result = $this->addGalleryToMerge($mainGallery, $gallery, $width, $height);
					if (!$result) {
						// очистка galleries_to_merge и возврат статуса
						$this->galleryToMergeError($mainGallery);
					}
				} else {
					if ($mainGallery) $this->galleryToMergeError($mainGallery);
					echo "Галлерея #" . $gallery . " ошибка размеров видео, или номера галлереи " . $videoFile;
					$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " Галлерея #" . $gallery . " нет видео файла " . $videoFile, true);
					// $gallery, $width или $height не работают
					return false;
				}
			}
			foreach ($galleries as $gallery) {
				$this->addToQuery($gallery);
			}
		} else {
			echo "Video: " . __METHOD__ . " Ошибка входящих данных<br>";
			$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " Ошибка входящих данных", true);
		}
	}


	private function addGalleryToMerge($mainGallery, $gallery, $width, $height)
	{
		$result = false;
		$mainGallery = intval($mainGallery);
		$gallery = intval($gallery);
		$width = intval($width);
		$height = intval($height);
		$prev_status = $this->getGalleryStatus($gallery);
		if ($mainGallery && $gallery && $width && $height && $prev_status) {
			$sql = "INSERT INTO galleries_to_merge (gal_id, merge_galleries, width, height, status, added, status_change_time, galleries_original_status)";
			$sql .= " VALUES ('" . $mainGallery . "', '" . $gallery . "', '" . $width . "', '" . $height . "', 'new', '" . time() . "', '" . time() . "', '" . $prev_status . "')";
			if ($this->_db->query($sql) === false) {
				print 'error inserting: ' . $this->_db->errorInfo() . '<BR>';
				$log = new Logger(__METHOD__ . ":video: " . __METHOD__ . " error inserting galleries_to_merge: " . $this->_db->errorInfo(), true);
			} else {
				$lastInsertId = $this->_db->lastInsertId();
				$result =  $lastInsertId;
				$this->setStatus($gallery, "to_merge");
			}
		}
		return $result;
	}


	public function merging_videoToMPEG($gal_id = false)
	{
		if ($gal_id === false) $sql = "SELECT merge_galleries, gal_id FROM galleries_to_merge WHERE status = 'new'";
		else {
			$gal_id = intval($gal_id);
			$sql = "SELECT merge_galleries, gal_id FROM galleries_to_merge WHERE merge_galleries = '" . $gal_id . "'";
		}

		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery) {
				echo "merging_videoToMPEG:\n<br>Галера: " . $gallery[0]['merge_galleries'] . ",основная: " . $gallery[0]['merge_galleries'] . "\n<br>";
				$galId = $gallery[0]['merge_galleries'];
				$mainGallery = $gallery[0]['gal_id'];
				$videoFile = $this->getVideoFilePath($galId);
				echo __METHOD__ . ", входящий файл:'" . $videoFile . "'\n<br>";
				if ($videoFile) {
					$mpegFile = TMP_VIDEO_FOLDER . "/" . $galId . "/" . $galId . ".mpg";
					echo __METHOD__ . ", исходящий mpg файл:'" . $mpegFile . "'\n<br>";
					// апдейт статуса
					if (!$this->setMergingStatus($galId, 'to_mpeg')) return false;
					echo __METHOD__ . ", setMergingStatus:'OK'\n<br>";

					$video = new VideoUtils("temp");
					echo __METHOD__ . ", вызов video->convertToMpeg: '" . $videoFile . "'->'" . $mpegFile . "'\n<br>";
					$convertResult = $video->convertToMpeg($videoFile, $mpegFile);
					if ($convertResult) {
						// апдейт статуса
						echo "Convert success<br>";
						if (!$this->setMergingStatus($galId, 'mpeg')) return false;
					} else {
						echo "Convert to_mpeg_fail :(<br>";
						$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " ID:" . $galId . ", main ID:" . $mainGallery . ", Error converting video " . $videoFile . " to mpeg " . $mpegFile, true);
						// апдейт статуса
						$this->setMergingStatus($mainGallery, 'to_mpeg_fail', true);
					}
				} else {
					echo "Галлерея #" . $galId . " видео файл " . $videoFile . " не найден";
					$log = new Logger(__METHOD__ . ":Video: " . __METHOD__ . " Галлерея #" . $galId . " видео файл " . $videoFile . " не найден", true);
					return false;
				}
			} else return "no new";
		} else {
			echo __METHOD__ . ": No galleries found<br>";
			return "no new";
		}
	}

	private function processMPEGs()
	{
		$sql = "SELECT gal_id FROM galleries_to_merge WHERE status = 'mpeg'"; // OR status = 'merging'
		$rs = $this->_db->query($sql);
		if ($rs) {
			$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($gallery) {
				// var_dump($gallery);
				$mainGallery = $gallery[0]['gal_id'];
				$mainOutputFile = TMP_VIDEO_FOLDER . "/" . $gallery[0]['gal_id'] . "/" . $gallery[0]['gal_id'] . "-merged.mpg";
				$sql = "SELECT merge_galleries FROM galleries_to_merge WHERE gal_id = '" . $mainGallery . "' ORDER BY id ASC";
				$rs = $this->_db->query($sql);
				$galleries = $rs->fetchAll(\PDO::FETCH_ASSOC);
				//var_dump($galleries);
				if ($galleries) {
					// апдейт статуса
					if (!$this->setMergingStatus($mainGallery, 'merging', true)) return false;
					foreach ($galleries as $gallery) {
						$mpegFiles[] = TMP_VIDEO_FOLDER . "/" . $gallery['merge_galleries'] . "/" . $gallery['merge_galleries'] . ".mpg";
					}
					if (isset($mpegFiles)) {
						$video = new VideoUtils("temp");
						$log = new Logger(__METHOD__ . " объединение видео в: " . $mainOutputFile);
						$mergeResult = $video->mergeVideos($mpegFiles, $mainOutputFile);

						if ($mergeResult) {
							if (is_file($mainOutputFile)) {
								if (rename($mainOutputFile, TMPDIR . "/" . $mainGallery . ".tmp")) {
									foreach ($galleries as $gallery) {
										if ($gallery['merge_galleries'] == $mainGallery) {
											$this->setStatus($gallery['merge_galleries'], 'fetched');
											$this->removeGalleryImages($gallery['merge_galleries']);
											//$this->addToQuery($gallery['merge_galleries']);
										} else {
											if (is_file(TMPDIR . "/" . $gallery['merge_galleries'] . ".tmp")) unlink(TMPDIR . "/" . $gallery['merge_galleries'] . ".tmp");
											//echo "Removing gallery: ".$gallery['merge_galleries']."<br>";
											$this->setStatus($gallery['merge_galleries'], 'delete');
										}
									}
									$this->merging_Success($mainGallery);
								} else {
									$log = new Logger(__METHOD__ . ", Невозможно объединить видео. Невозможно переименовать файл " . $mainOutputFile . " в " . TMPDIR . "/" . $mainGallery . ".tmp", true);
									// апдейт статуса
									if (!$this->setMergingStatus($mainGallery, 'merging_fail', true)) return false;
								}
							} else {
								$log = new Logger(__METHOD__ . ", Невозможно объединить видео. Нет файла " . $mainOutputFile, true);
								// апдейт статуса
								if (!$this->setMergingStatus($mainGallery, 'merging_fail', true)) return false;
							}
						} else {
							$log = new Logger(__METHOD__ . ":Невозможно объединить видео ошибка video->convertToMpeg(videoFile,mpegFile)", true);
							// апдейт статуса
							if (!$this->setMergingStatus($mainGallery, 'merging_fail', true)) return false;
						}
					} else {
						// апдейт статуса
						if (!$this->setMergingStatus($mainGallery, 'merging_fail', true)) return false;
					}
				} else {
					$log = new Logger(__METHOD__ . "Не найдено галер", true);
					// echo "Не найдено галер";
				}
			} else {
				$log = new Logger(__METHOD__ . "No videos in status = 'mpeg' (GetRows)", true);
			}
		} else {
			$log = new Logger(__METHOD__ . "No videos in status = 'mpeg'", true);
			// echo "No videos in status = 'mpeg'\n";
		}
	}

	public function processMerging()
	{
		$videos = $this->merging_videoToMPEG();
		if ($videos == 'no new') {
			$log = new Logger(__METHOD__ . ":processMerging: No new: START processMPEGs");
			$videos = $this->processMPEGs();
			$log = new Logger(__METHOD__ . ":STOP processMPEGs");
		}
	}

	private function merging_Success($mainGallery)
	{
		$mainGallery = intval($mainGallery);
		if ($mainGallery) {
			$sql = "DELETE FROM `galleries_to_merge` WHERE `gal_id` = '" . $mainGallery . "'";
			if ($this->_db->query($sql)) $result = true;
		}
	}

	public function setMergingStatus($id, $status, $switchAll = false)
	{
		$result = false;
		$id = intval($id);
		if ($id && preg_match('/^(new|to_mpeg|to_mpeg_fail|mpeg|merging|merging_fail|error)$/', $status)) {
			// апдейт статуса
			$sql = "UPDATE galleries_to_merge SET status = '" . $status . "', status_change_time = '" . time() . "' WHERE ";
			if ($switchAll) $sql .= " gal_id = ";
			else $sql .= " merge_galleries = ";
			$sql .= "'" . $id . "'";
			if ($this->_db->query($sql)) {
				return true;
			} else {
				$log = new Logger(__METHOD__ . ":Невозможно сбросить статус галер объединяющихся в  " . $id . " в to_mpeg", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ":Неверный статус для галлереи #" . $id . ":" . $status, true);
		}
		return false;
	}

	private function galleryToMergeError($gallery)
	{
		if (!$this->setMergingStatus($gallery, 'error', true)) return false;
		//echo "Clearing gallery";
	}
	/*




Обработка галлерей на объединении:
    Конвертация всех файлов очереди в mpg файлы одного размера картинки
	1. Проверка наличия файла
	2. Попытка его сконвертить в mpg файл с рамером из базы.
	   ffmpeg -i videov.flv -s 720x480 -sameq video-rs.mpg

	Объединение:
	1. Объединение всех файлов
	2. закачка нового файла в temp в виде galid.tmp
	3. Выставление статуса grabbed для основной галеры

	Очистка:
	1. Удаление галлерей из таблицы объединения
	2. Удаление лишних галлерей из таблицы galleries
*/


	public function processOneZip(int $gal_id = null)
	{
		$result = false;

		$sql = "SELECT gal_id, gal_title FROM galleries WHERE ";
		$sql .= is_null($gal_id) ? "gal_status = 'newzip'" : "gal_id = {$gal_id}";
		$sql .= " LIMIT 1";

		$id = null;
		$title = null;

		$rs = $this->_db->query($sql);

		$gallery = [];
		$gallery = $rs->fetchAll(\PDO::FETCH_ASSOC);

		$id = $gallery[0]['gal_id'] ?? null;
		$title = $gallery[0]['gal_title'] ?? null;


		if (empty($id)) {
			$log = new Logger(__CLASS__ . ":" . __METHOD__ . ": ошибка базы данных", true);
			return false;
		}

		if ($this->setStatus($id, 'unzipping')) {
			$grabber = new Grabber_new();

			$metaData = [];
			$log = new Logger(__METHOD__ . ": ZIP_TEXT processOneZip start GID#" . $id . ", title is " . (empty($title) ? "empty" : "not empty"));

			$content = $grabber->unzipGallery($id, $metaData);
			$log = new Logger(__METHOD__ . ": ZIP_TEXT unzipGallery finished GID#" . $id . ", metadata title: " . (isset($metaData['title']) ? $metaData['title'] : 'not set'));

			if ($content !== false && (isset($content['image']) || isset($content['video']))) {
				$videoCount = 1;
				$newId = false;
				if (isset($content['image']) &&  isset($content['video'])) {
					$this->processImagesFromTemp($id, $content['image']);
					$this->addToQuery($id);
					// если есть видео
					foreach ($content['video'] as $video) {
						if (is_file($video)) {
							$fileMd5 = md5_file($video); // в добавочных Movies галлереях используется md5 файла вместо урла
							$source = false;
							$status = 'newzip';

							$newId = $this->insertNewVideoGallery($id, $fileMd5, $source, $status); // передается оригинальный ID и сохранить связь между галлереями

							if ($newId) {
								// echo "Добавлено новое ID:".$newId;
								$this->processVideoFromTemp($newId, $video);
								$videoCount++;
							}
						} else {
							$log = new Logger(__METHOD__ . ":Галлерея: #" . $id . " " . __METHOD__ . ", работа с ZIP, файл " . $video . " не найден", true);
						}
					}
				} elseif (isset($content['image'])) {
					// изображения копируются в локал из tmp, выставляются все флаги
					$this->processImagesFromTemp($id, $content['image']);
					$this->addToQuery($id);
				} elseif (isset($content['video'])) {
					$contentCount = count($content['video']);
					foreach ($content['video'] as $video) {
						// создается еще новая галлерея, type video
						if (is_file($video)) {
							if ($videoCount == 1) {
								$this->processVideoFromTemp($id, $video, $id);
								$videoCount++;
							} else {
								$fileMd5 = md5_file($video);
								$source = false;
								$status = 'newzip';
								if ($newId = $this->insertNewVideoGallery($id, $fileMd5, $source, $status)) {
									$this->processVideoFromTemp($newId, $video, $id);
								} else {
									$log = new Logger(__METHOD__ . ":ZIP #" . $id . " Galleries->processOneZip(). Ошибка при попытке создать еще одну галлерею для видео", true);
								}
							}
						} else {
							$log = new Logger(__METHOD__ . ":ZIP, работа с видео #" . $id . ": файл " . $video . " не найден", true);
						}
					}
				}

				if (empty($title) && isset($metaData['title'])) {

					$description = !empty($metaData['combined-desc']) ? $metaData['combined-desc'] : $metaData['description'];

					$this->updateGalleryTitle($gal_id, $metaData['title']);
					$this->updateGalleryDescription($gal_id, $description);
				} elseif (!empty($title)) {
					$log = new Logger(__METHOD__ . ": ZIP_TEXT metadata title update skipped GID#" . $id . ", gallery already has title");
				}
			} else {
				$this->setStatus($id, 'unzip_fail');
				$log = new Logger(__METHOD__ . ":ZIP #" . $id . " Galleries->processOneZip(). В content нет ни images, ни video", true);
			}
		} else {
			$this->setStatus($id, 'newzip');
			$log = new Logger(__METHOD__ . ":ZIP #" . $id . " Galleries->processOneZip(). Невозможно переключить Статус. Процесс уже в таблице или ошибка базы данных.", true);
		}
	}

	public function grabGalleryFile($id = false)
	{
		// $this->setStatus($id, 'new');

		$result = false;
		$gallery = false;

		$id ? $id = (int)$id : NULL;

		if ($id) {
			$sql  = "SELECT gal_id, gal_title, gal_source, gal_md5, gal_paysite, gal_type, is_long_url 
				 FROM galleries
				 WHERE gal_id = ?
				 LIMIT 1";
		} else {
			$sql  = "SELECT gal_id, gal_title, gal_source, gal_md5, gal_paysite, gal_type, is_long_url
				 FROM galleries
				 WHERE gal_status = 'new'
  				 AND (LTRIM(gal_source) LIKE 'http%' OR is_long_url = 1)
  				 LIMIT 1";
		}

		$db = DB::get();

		if ($db) {
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($id) {
					$stmt->bind_param("i", $id);
				}
				if ($stmt->execute()) {
					$gal_id = false;
					$gal_title = false;
					$gal_source = false;
					$gal_md5 = false;
					$gal_paysite = false;
					$gal_type = false;
					$is_long_url = false;

					$stmt->bind_result($gal_id, $gal_title, $gal_source, $gal_md5, $gal_paysite, $gal_type, $is_long_url);
					if ($stmt->fetch()) {
						$gallery = compact("gal_id", "gal_title", "gal_source", "gal_md5", "gal_paysite", "gal_type", "is_long_url");
					}
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}


		if (!$gallery) {
			echo "Ошибка grabGalleryFile граба. GetRows пустой. " . $this->_db->errorInfo();
			$log = new Logger(__METHOD__ . " Ошибка grabGalleryFile граба. GetRows пустой. " . $this->_db->errorInfo(), true);
			return false;
		}


		$file = ($is_long_url) ? $this->getLongUrlByGalId($gal_id) : trim($gallery['gal_source']);


		$galId = $gallery['gal_id'];
		$galTitle = $gallery['gal_title'];
		$galMd5 = $gallery['gal_md5'];
		$galPaysite = $gallery['gal_paysite'];
		$gallery_type = $gallery['gal_type'];

		$grabber = new Grabber_new();


		if (strpos($gal_source, 'pornhub.com') || strpos($gal_source, 'xvideos.com')) {

			$gal_info = strpos($gal_source, 'pornhub.com') ? $grabber->parsePornhubVideoInfo($file) : $grabber->parseXvideosVideoInfo($file);

			if ($gal_info) {

				$gal_info['title'] = str_replace(' - Pornhub.com', '', $gal_info['title']);
				$gal_info['title'] = str_replace(' - XVIDEOS.COM', '', $gal_info['title']);

				$this->updateGalleryTitle($gal_id, $gal_info['title']);

				foreach ($gal_info['tags'] as $add_tag) {
					$this->addTagToGalleriesImportQuery($gal_id, $add_tag);
				}

				$model_worker = new CModels($this->_db);
				foreach ($gal_info['models'] as $model_name) {
					$model_id = $model_worker->getModelByName($model_name);
					if ($model_id) {
						$this->addModel($gal_id, $model_id);
					}
				}

				$log = new Logger(__METHOD__ . ": PornHub Fetch started GID:" . $gal_id);
				DB::closeConnection();
				$grabResult = $grabber->fetchWithYoutubeDl($file);
				$log = new Logger(__METHOD__ . ": PornHub Fetch done GID:" . $gal_id);
				DB::get();
			}
		} else {
			// close connection

			DB::closeConnection();
			$grabResult = $grabber->fetchFile($file);
			DB::get();

			// restore connection
		}

		$log = new Logger(__METHOD__ . ":{$galId}, Grabbed: '" . $grabResult . "', Cont type: " . $grabber->contentType);


		// var_dump($file, $grabResult, $gallery_type, $grabber);

		$allowed_content_type = ($grabber->contentType === CONTENT_TYPE_ZIP ||
			$grabber->contentType === CONTENT_TYPE_VIDEO ||
			$grabber->contentType === CONTENT_TYPE_HTML ||
			$grabber->contentType == CONTENT_TYPE_GIF);

		if (($grabResult && $allowed_content_type) || $gallery_type == 'embed') {
			$tmpFile = TMPDIR . $grabResult;
			if ($gallery_type == 'embed') {
			} else {
				switch ($grabber->contentType) {
					case CONTENT_TYPE_GIF:
						$type = 'New';
						$this->setContentType($galId, 'gif');

						if ($this->processGifFromTemp($galId, $tmpFile) === false) {
							$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно перенести видео из " . $tmpFile, true);
							$this->setStatus($galId, 'fetching_fail');
						} else {
							$result = true;
						}
						break;
					case CONTENT_TYPE_ZIP:
						$type = 'New';
						$zipFolderPath = TMPDIR;
						$new_galleryZipPath = $zipFolderPath . "/" . $galId . ".zip";
						if (rename($tmpFile, $new_galleryZipPath)) {
							chmod($new_galleryZipPath, 0666);
							$this->setStatus($galId, 'newzip');
							$this->addToQuery($galId);
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " ошибка переноса временного файла " . $tmpFile . " в " . $new_galleryZipPath, true);
							$this->setStatus($galId, 'fetching_fail');
						}
						break;
					case CONTENT_TYPE_VIDEO:
						$type = 'Movies';
						$this->setContentType($galId, 'Movies');
						if ($this->processVideoFromTemp($galId, $tmpFile) === false) {
							$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно перенести видео из " . $tmpFile, true);
							$this->setStatus($galId, 'fetching_fail');
						} else {
							$result = true;
						}
						break;
					case CONTENT_TYPE_HTML:

						$grabber = new Grabber();
						$videoGrabber = new Grabber_new();
						$count = 0;

						if ($grabber->FindImages($file)) {

							$this->setStatus($galId, 'fetching');

							if (!$grabber->GetPictures()) {
								$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно сграбить изображения");
								$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно сграбить изображения", true);
								$this->setStatus($galId, 'fetching_fail');
								return false;
							}

							$type = 'Pics';
							$this->setContentType($galId, 'Pics');

							if (!$this->processImagesFromTemp($galId, $grabber->ShowFiles())) {
								$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно перенести изображения в UPLOADFOLDER", true);
								$this->setStatus($galId, 'fetching_fail');
								return false;
							}

							$count++;
							$this->setStatus($galId, 'fetched');

							if (!$galTitle) {
								$this->updateGalleryTitle($galId, $grabber->galTitle);
							}

							$this->addToQuery($galId);
							$result = true;
						} elseif ($videos = $videoGrabber->FindVideos($file)) {
							$this->setStatus($galId, 'fetching');
							if ($videos && is_array($videos) && count($videos) > 0 && isset($videos['video'])) {
								unset($videos['video']);
								$id = $galId;
								foreach ($videos as $video) {
									if ($count != 0) {
										$galId = $this->insertNewVideoGallery($id, md5($video), $video);
									} else {
										$type = 'Movies';
										$this->setContentType($galId, 'Movies');
										$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " грабим " . $video);
										$grabResult = $videoGrabber->fetchFile($video);
										$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " сграблено " . $video);
										if ($videoGrabber->contentType === CONTENT_TYPE_VIDEO) {
											$this->setContentType($galId, 'Movies');
											$tmpFile = TMPDIR . $grabResult;
											//var_dump($tmpFile);
											if ($this->processVideoFromTemp($galId, $tmpFile) === false) {
												$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно перенести видео из " . $tmpFile, true);
												$this->setStatus($galId, 'fetching_fail');
											} else {
												//$this->setStatus($galId, 'fetched');
												$this->setMainGallery($galId, $galId);
												$result = true;
											}
										} else {
											$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не видео: " . $tmpFile, true);
											$this->setStatus($galId, 'fetching_fail');
										}
									}
									$count++;
								}
							} else {
								$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " FindVideo() отдал пустой массив", true);
								$this->setStatus($galId, 'fetching_fail');
							}
						} else {
							$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " на галере не найдены изображения/видео/не смог ее сграбить", true);
							$this->setStatus($galId, 'fetching_fail');
						}
						break;
				}
			}
		} else {
			$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . ", " . __METHOD__ . ": Ошибка фетчинга галлереи, grabber->contentType не является HTML, VIDEO или ZIP'ом. Цифровой код проверки после скачивания файла: " . $grabber->contentType, true);
			$this->setStatus($galId, 'fetching_fail');
		}


		echo "<h2>" . __METHOD__ . " Fin</h2><br>";
		return $result;
	}

	public function addToQuery($gal_id, $priority = 1)
	{
		//echo "Добавляется галера #".$id." в очередь<br>";
		$result = false;
		$gal_id = (int)$gal_id;
		if ($priority !== 1) $priority = 0;
		$db = DB::get();
		if ($gal_id) {
			if ($db) {
				$added_on = time();

				$sql = 'INSERT INTO `main_query`
							(`gal_id`, `added`, `priority`) 
						VALUES
							(?, ?, ?);';

				$stmt = $db->prepare($sql);
				if ($stmt) {
					$stmt->bind_param("iii", $gal_id, $added_on, $priority);
					if ($stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ":Галлерея: #" . $gal_id . " " . __METHOD__ . " неверные входящие (id)", true);
		}
		return  $result;
	}

	public function showQuery($type = false)
	{
		$result = array();
		if ($type == 'to_merge') {
			$sql = "select galleries_to_merge.gal_id as main_id, main_query.gal_id,galleries.gal_status, 
  						   galleries.gal_type, main_query.priority, main_query.added 
	  				from galleries_to_merge
	  				left join main_query on galleries_to_merge.merge_galleries = main_query.gal_id 
	  				left join galleries on main_query.gal_id = galleries.gal_id order by added desc";
		} else $sql = "select main_query.gal_id, galleries.gal_status, galleries.gal_type, 
  							  main_query.priority, main_query.added 
		  				from main_query 
		  				left join galleries 
		  				on main_query.gal_id = galleries.gal_id order by added desc";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$galleries = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($galleries) {
				foreach ($galleries as $gallery) {
					$result[$gallery['gal_id']]['gal_status'] = $gallery['gal_status'];
					$result[$gallery['gal_id']]['gal_type'] = $gallery['gal_type'];
					$result[$gallery['gal_id']]['priority'] = $gallery['priority'];
					$result[$gallery['gal_id']]['added'] = $gallery['added'];
					if ($gallery['gal_status'] == 'to_merge') {
						$result[$gallery['gal_id']]['to_merge']['gal_id'] = $gallery['main_id'];
						$result[$gallery['gal_id']]['to_merge']['status'] = $this->getMergeStatus($gallery['gal_id']);
						$result[$gallery['gal_id']]['to_merge']['original_status'] = $this->getPreMergingStatus($gallery['gal_id']); // pre_merge_status
					}
				}
			}
		}
		return $result;
	}

	public function removeFromQuery($gal_id)
	{
		//echo "Удаляется галера #".$id." из очереди<br>";
		$result = false;
		$gal_id = intval($gal_id);
		if ($gal_id) {
			$sql = "DELETE FROM `main_query` WHERE `gal_id` = ?";
			$db = DB::get();
			if ($db) {
				if ($sql) {
					$stmt = $db->prepare($sql);
					if ($stmt) {
						if ($stmt->bind_param("i", $gal_id)) {
							if ($stmt->execute()) {

								$result = false;
							} else {
								$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
							}
						} else {
							$log = new Logger(__METHOD__ . ": STMT Bind Param failed: " . $db->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": SQL string is empty", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}

	public function getGalleryToProcess($force_process_status = false, $process_fetch = false, $gal_type = false, $gal_id = false)
	{
		$result = false;

		$where_set = false;

		$sql  = "SELECT main_query.gal_id,galleries.gal_status, galleries.gal_type, main_query.priority 
				FROM main_query 
				LEFT JOIN galleries ON main_query.gal_id = galleries.gal_id";
		if ($gal_id && (int)$gal_id > 0) {
			$gal_id = (int)$gal_id;
			$sql .= " WHERE main_query.gal_id = '" . $gal_id . "'";
		} else {
			if (!$process_fetch) {
				$sql .= " WHERE galleries.gal_status NOT IN ('new') ";
				$where_set = true;
			} else {
				$sql .= " WHERE galleries.gal_status = 'new' ";
				$where_set = true;
			}
			if ($force_process_status && preg_match("#^(newzip|fetched|screened|to_merge|grabbed)$#", $force_process_status)) {
				if ($where_set) $sql .= " AND ";
				else $sql .= " WHERE ";
				$sql .= " AND galleries.gal_status = '" . $force_process_status . "'";
				$log = new Logger(__METHOD__ . " force_process_status = " . $force_process_status, true);
			}
			$sql .= " ORDER BY id ASC LIMIT 1";
		}

		$gal_id = false;
		$gal_status = false;
		$gal_type = false;
		$priority = false;

		$db = DB::get();
		if ($db) {
			if ($sql) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {

						$stmt->bind_result($gal_id, $gal_status, $gal_type, $priority);

						if ($stmt->fetch()) {
							$result = compact("gal_id", "gal_status", "gal_type", "priority");
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": SQL string is empty", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		// echo "\nSQL:\n'".$sql."'\n\n";

		return $result;
	}

	private function grabEmbedGallery($galId)
	{
		$result = false;
		$count = 0;
		$gal_id = (int)$galId;
		// выборка изображений
		$grabber = new Grabber();
		$img_worker = new Images();
		$images_array = $img_worker->getImagesURLs($galId);
		if ($images_array && is_array($images_array)) {
			$this->setStatus($galId, 'fetching');
			// добавить в getPictures свои картинки
			$file = false;
			if ($grabber->GetPictures($file, $images_array)) {
				$gal_type = 'embed';

				if ($this->processImagesFromTemp($galId, $grabber->ShowFiles(), $gal_type)) {
					$count++;
					$this->setStatus($galId, 'fetched');
					$this->addToQuery($galId);
					$result = true;
				} else {
					$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно перенести изображения в UPLOADFOLDER", true);
					$this->setStatus($galId, 'fetching_fail');
				}
			} else {
				$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно сграбить изображения", true);
				$this->setStatus($galId, 'fetching_fail');
			}
		} else {
			$log = new Logger(__METHOD__ . ":Галлерея: #" . $galId . " " . __METHOD__ . " не возможно выбьрать изображения из БД", true);
			$this->setStatus($galId, 'fetching_fail');
		}
		return $result;
	}

	public function processGalleryById($gal_id)
	{
		$force_process_status = false;
		$process_fetch = false;
		$gal_type = false;
		return $this->processQuery($force_process_status, $process_fetch, $gal_type, $gal_id);
	}

	public function processQuery($force_process_status = false, $process_fetch = false, $gal_type = false, $gal_id = false)
	{ // force_process_status для тестов процесса

		$result = false;

		$gallery = $this->getGalleryToProcess($force_process_status, $process_fetch, $gal_type, $gal_id);

		if (empty($gallery)) {
			$log = new Logger(__METHOD__ . ": > > > Очередь пустая");
			return false;
		}

		$gal_id 	= $gallery['gal_id'];
		$status 	= $gallery['gal_status'];
		$priority 	= $gallery['priority'];
		$type 		= $gallery['gal_type'];

		$this->setGalleryId($gal_id);

		$log = new Logger(__METHOD__ . ":Обрабатывается галера #" . $gal_id . ":" . $status . ":" . $priority . ":" . $type . " из очереди");

		if ($status == 'new') {
			$log = new Logger(__METHOD__ . ":grabGalleryFile галеры #" . $gal_id . ", Статус:" . $status . ", тип галеры " . $type);
			($type == 'embed') ? $this->grabEmbedGallery($gal_id) : $this->grabGalleryFile($gal_id);
		} elseif (!$process_fetch) {

			$this->removeFromQuery($gal_id);

			if ($status == 'newzip') {
				//echo "processOneZip<br>";
				$log = new Logger(__METHOD__ . ":processOneZip галеры #" . $gal_id . ", Статус:" . $status . ", тип галеры " . $type);
				$this->processOneZip($gal_id);
			} elseif ($status == 'fetched' && $type == 'Movies') {
				//echo "makeScreenshots<br>";
				$log = new Logger(__METHOD__ . ":makeScreenshots галеры #" . $gal_id . ", Статус:" . $status . ", тип галеры " . $type);
				$this->makeScreenshots($gal_id);
			} elseif ($status == 'screened') {
				//echo "processVideo<br>";
				$log = new Logger(__METHOD__ . ":processVideo галеры #" . $gal_id . ", Статус:" . $status . ", тип галеры " . $type);
				$this->processVideo($gal_id);
			} elseif ($status == 'to_merge') {
				$log = new Logger(__METHOD__ . ":processMerging галеры #" . $gal_id . ", Статус:" . $status . ", тип галеры " . $type);
				$this->processMerging();
				$this->addToQuery($gal_id);
			} elseif (($status == 'fetched' && ($type == 'Pics' || $type == 'embed'))
				|| ($status == 'grabbed' && $type == 'Movies')
			) {
				$this->processThumbs($gal_id, $type);
				$this->processGalleriesTagsImport($gal_id);
			}
		}
	}


	public function processGrab()
	{
		$force_process_status = false;
		$process_fetch = true;
		$gal_id = false;
		$this->processQuery($force_process_status, $process_fetch, $gal_id);
	}


	//
	//	Все связаное с очередью кропа
	//

	public function updateCroppedStatus(int $galId, $status, $user = false)
	{
		$result = false;
		$status = ($status) ? 1 : 0;

		$sql = "UPDATE galleries
					SET crop_flag = '" . $status . "'
					WHERE gal_id = '" . $galId . "'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$result = true;
			if ($status == 1) {
				if ($this->ifGalleryManualRecrop($galId)) {
					if ($user) $this->setGalleryRecropped($galId, $user);
					$this->removeGalleryManualRecrop($galId);
				}
			}
		}

		return $result;
	}

	public function ifGalleryRecropped(int $galId)
	{

		$result = false;
		if ($galId > 0) {
			$sql = "SELECT gal_id FROM scr_manual_recropped WHERE gal_id = '" . $galId . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$res['count'] = count($row);
					if ($res['count'] > 0) {
						$res['id'] = $row[0]['gal_id'];
						return $res;
					}
				}
			}
		}
		return $result;
	}

	public function ifGalleryManualRecrop(int $galId)
	{
		$result = false;
		if ($galId > 0) {
			$sql = "SELECT gal_id FROM scr_gallery_manual_recrop WHERE gal_id = '" . $galId . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$res['count'] = count($row);
					if ($res['count'] > 0) {
						$res['id'] = $row[0]['gal_id'];
						return $res;
					}
				}
			}
		}
		return $result;
	}

	private function setGalleryRecropped(int $galId, int $user)
	{
		$result = false;
		if ($galId > 0 && $user > 0) {
			$sql = "insert into scr_manual_recropped (gal_id, user_id, date_recropped) value ('" . $galId . "','" . $user . "','" . time() . "')";

			$rs = $this->_db->query($sql);
		}
		return $result;
	}

	private function getUserCroppedGallery(int $galId)
	{
		$result = false;
		if ($this->galleryCroppedStatus($galId)) {
			$galId = intval($galId);
			$sql = "SELECT user_id FROM scr_manual_crop_history WHERE gal_id = '" . $galId . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$result = intval($row[0]['user_id']);
				} else $result = 0;
			} else $result = 0;
		}
		return $result;
	}

	function galleryToRecrop(int $galId, $reason = false)
	{
		$userId = $this->getUserCroppedGallery($galId);
		if ($reason !== false) {
			$reason = preg_replace("/[^a-z,.0-9-]/im", "", $reason);
		} else $reason = "No reason.";
		$result = false;
		if ($this->_db && $userId !== false) {
			$sql = "insert into scr_gallery_manual_recrop
					(gal_id, user_id, added, recrop_reason)
					value('" . $galId . "', '" . $userId . "', '" . time() . "', '" . $reason . "') ";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$result = true;
				$this->updateCroppedStatus($galId, 0);
			}
		}
		return $result;
	}

	function skeepCropGallery(int $galId, int $userId, $reason, $type)
	{
		$type = strtolower($type);
		if ($reason !== false) {
			$reason = preg_replace("/[^a-z,.0-9-]/im", "", $reason);
		} else $reason = "Причина не указана.";
		if ($this->_db && $galId && $userId && preg_match('/[crop|tags]/im', $type)) {
			$sql = "insert into scr_user_skeep_gallery
					(gal_id, user_id, added, skeep_reason ,skeep_type)
					value('" . $galId . "', '" . $userId . "', '" . time() . "', '" . $reason . "', '" . $type . "') ";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$result = true;
				$this->removeGalleryManualRecrop($galId);
			}
		}
		return $result;
	}

	function getSkeepedGalleries()
	{
		$result = false;
		$sql = "select gal_id, user_id, added, skeep_reason ,skeep_type from scr_user_skeep_gallery";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($row) {
				foreach ($row as $item) {
					$result[$item['gal_id']]['gal_id'] = $item['gal_id'];
					$result[$item['gal_id']]['user_id'] = $item['user_id'];
					$result[$item['gal_id']]['added'] = $item['added'];
					$result[$item['gal_id']]['skeep_reason'] = $item['skeep_reason'];
					$result[$item['gal_id']]['skeep_type'] = $item['skeep_type'];
				}
			}
		}

		return $result;
	}

	public function getMCropModelImage()
	{
		$sql = "SELECT image_id, model_id, layout FROM models_images WHERE status = 'uploaded'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($row) {
				$res['count'] = count($row);
				if ($res['count'] > 0) {
					$res['id'] = $row[0]['model_id'];
					$res['image_id'] = $row[0]['image_id'];
					$res['layout'] = $row[0]['layout'];
					return $res;
				}
			}
		}
	}

	function getModelImagesToCrop(int $model_id)
	{
		$result = false;
		if ($model_id > 0) {
			$sql = "SELECT image_id, model_id, layout FROM models_images WHERE model_id = '" . $model_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result['images']['url'][$row['image_id']] = $row['layout'];
					}
				}
			}
		}
		return $result;
	}


	function getModelImageToCrop(int $image_id)
	{
		$result = false;

		if ($image_id > 0) {
			$sql = "SELECT image_id, model_id, layout FROM models_images WHERE image_id = '" . $image_id . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result['images']['url'][$row['image_id']] = $row['layout'];
					}
				}
			}
		}
		return $result;
	}

	public function GetMCropGallery($galleryType = FALSE, $galleryNiche = FALSE, $galleryPaysite = FALSE, $galleryCategory = FALSE, $userId = false)
	{
		$result = false;

		$userId = (int)$userId > 0 ? (int)$userId :  0;

		$addition  = " AND gal_id NOT IN (SELECT gal_id FROM scr_working_list WHERE work_type = 'crop'";
		$addition .= ($userId) ? " AND user_id <> '" . $userId . "'" : "";
		$addition .= ") ";

		if ($userId) {
			$sql = "SELECT gal_id, recrop_reason FROM scr_gallery_manual_recrop WHERE user_id = '" . $userId . "' OR user_id = '0' " . $addition;
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$res['count'] = count($row);
					if ($res['count'] > 0) {
						$res['id'] = $row[0]['gal_id'];
						$res['recrop_reason'] = $row[0]['recrop_reason'];
						return $res;
					}
				}
			}
		}



		$addition .= ($galleryNiche) ? " AND gal_niche = '" . $galleryNiche . "'" : "";
		$addition .= ($galleryCategory) ? " AND gal_id IN (SELECT gal_id FROM galleries_tags WHERE gal_tags = '" . $galleryCategory . "')" : "";
		$addition .= ($galleryPaysite) ? " AND gal_paysite = '" . $galleryPaysite . "'" : "";

		if ($galleryType && ($galleryType == 'Pics' || $galleryType == 'Movies')) {
			if ($galleryType == 'Pics') {
				$sql = "SELECT gal_id
							FROM galleries 
							WHERE gal_status IN ('uploaded','OK','tagged') 
							AND gal_id NOT IN (SELECT gal_id FROM scr_user_skeep_gallery)
							AND crop_flag = '0'  AND gal_type = 'Pics' AND gal_content_count < 20"
					. $addition . " ORDER BY gal_added DESC";
			} else {
				$sql = "SELECT gal_id
							FROM galleries 
							WHERE gal_status = 'OK'
							AND gal_id NOT IN (SELECT gal_id FROM scr_user_skeep_gallery)
							AND crop_flag = '0'  AND gal_type = 'Movies' "
					. $addition . " ORDER BY gal_added DESC";
			}
		} else {
			$sql = "SELECT gal_id FROM galleries
						WHERE 
						gal_id NOT IN (SELECT gal_id FROM scr_user_skeep_gallery)
						AND (
						(crop_flag = '0' AND gal_type = 'Pics' AND gal_status IN ('uploaded','OK','tagged') AND gal_content_count <= 20)
						OR
						(crop_flag = '0' AND gal_type = 'Movies' AND gal_status = 'OK'))" .
				$addition .
				" ORDER BY gal_added DESC;";
		}

		$rs = $this->_db->query($sql);
		if ($rs) {
			$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
			$res = array();
			if ($row) {
				$res['count'] = count($row);
				if ($res['count'] > 0) {
					$res['id'] = $row[0]['gal_id'];
				}
				$result = $res;
			}
		}

		return $result;
	}

	function countGalleries($status = 'OK')
	{
		$result = false;
		if (preg_match('#^(OK|uploaded)$#', $status) && $this->_db) {
			$sql = "select count(gal_id) from galleries where gal_status = '" . $status . "';";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$result = $row[0]['count(gal_id)'];
				}
			}
		}
		return $result;
	}

	// количество голер в статусе ОК и uploaded
	public function getReadyGalleriesCount()
	{
		$result = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT COUNT(gal_id) FROM galleries
					WHERE gal_status IN ('OK','uploaded')";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}

	// количество галер с ошибками
	public function getFailedGalleriesCount()
	{
		$result = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT COUNT(gal_id) FROM galleries
					WHERE gal_status IN ('" . implode("','", $this->fail_statuses) . "')";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}


	// количество галер с ошибками
	public function getProcessingGalleriesCount()
	{
		$result = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT COUNT(gal_id) FROM galleries
					WHERE gal_status IN ('" . implode("','", $this->processing_statuses) . "')";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}

		return $result;
	}


	function getOKGalleries($start = 0, $finish = 9999999)
	{
		$result = array();
		if ($this->_db) {
			$sql = "select gal_id from galleries where gal_status = 'OK' order by gal_id limit " . $start . ", " . $finish . ";";
			var_dump($sql);
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($rows) {
					foreach ($rows as $row) {
						$result[] = $row['gal_id'];
					}
				}
			}
		}
		return $result;
	}

	public function getGalleriesToCache($only_horiz = true)
	{
		$result = false;
		if ($only_horiz) {
			$sql_add = " INNER JOIN galleries_resized_to ON GT.gal_id = galleries_resized_to.gal_id ";
		} else {
			$sql_add = "";
		}
		$sql = "SELECT GT.gal_id FROM galleries GT
				" . $sql_add . "
				LEFT JOIN caching_temp_t CT ON GT.gal_id = CT.gal_id
				
				WHERE CT.gal_id IS NULL AND GT.gal_status = 'OK'
				LIMIT 0,6000";

		$db = DB::get();
		if ($db) {
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$gal_id = null;
					$stmt->bind_result($gal_id);
					while ($stmt->fetch()) {
						$result[] = $gal_id;
					}
				} else {
					echo __METHOD__ . ": DB execute failed: " . $stmt->error;
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				echo __METHOD__ . ": STMT failed: " . $db->error;
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			echo __METHOD__ . ": DB connect failed: ";
			$log = new Logger(__METHOD__ . ": DB connect failed: ", true);
		}
		var_dump($result);
		return $result;
	}

	public function addCachedGallery($gal_id)
	{
		$result = false;
		$sql_insert = false;
		$added_on = time();
		if (is_array($gal_id)) {
			$sql_insert = " VALUES ";
			foreach ($gal_id as $value) {
				if ((int)$value) {
					$gallery = (int)$value;
					$sql_insert .= "('" . $gallery . "','" . $added_on . "'),";
				}
			}
			$sql_insert = rtrim($sql_insert, ",");
		} else {
			$gallery = (int)$gal_id;

			if ($gallery) {
				$sql_insert = " VALUES ";
				$sql_insert .= "('" . $gallery . "','" . $added_on . "')";
			}
		}
		// var_dump($sql_insert);
		if ($sql_insert) {
			$sql = "INSERT INTO caching_temp_t
					(gal_id, added_on) " . $sql_insert;
			$db = DB::get();
			if ($db) {
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB connect failed: ", true);
			}
		}
		return $result;
	}

	public function clearCachingTable()
	{
		$sql = "TRUNCATE caching_temp_t";
		$db = DB::get();
		if ($db) {
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$result = true;
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": DB connect failed: ", true);
		}
	}

	public function getRelatedGalleries($siteId, $galId, $global = false)
	{
		$result = false;
		$siteId = intval($siteId);
		$galId = intval($galId);
		$weightElement = array();
		$weightArray = array();
		if ($siteId && $galId) {
			$sql = "select galleries.gal_id, galleries.gal_title, galleries.gal_description, galleries.gal_type, galleries.gal_niche, 					galleries_tags.gal_tags
				from galleries_tags
				left join galleries on galleries.gal_id = galleries_tags.gal_id
				left join site_" . $siteId . " on site_" . $siteId . ".gal_id = galleries.gal_id";
			$sql .= " where site_" . $siteId . ".id = '" . $galId . "'";

			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$global_id = $row[0]['gal_id'];
					$models = $this->getGalleryModels($global_id);
					$title = $row[0]['gal_title'];
					$description = $row[0]['gal_description'];
					$type = $row[0]['gal_type'];
					$niche = $row[0]['gal_niche'];
					if ($type == 'Movies') $type = " galleries.gal_type = 'Movies' and ";
					elseif ($type == 'gif') $type = " galleries.gal_type = 'gif' and ";
					else $type = " galleries.gal_type = 'Pics' and ";
					if (preg_match("#(Gay|Straight|Shemale)#", $niche)) $niche = " galleries.gal_niche = '" . $niche . "' and ";
					else $niche = "";
					foreach ($row as $element) {
						$tags[] = $element['gal_tags'];
					}
					$title = strtolower(trim($title));
					$title = preg_replace("/[^a-z \']/im", "", $title);
					$title = preg_replace("/[\']/im", " ", $title);

					$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id,
							MATCH gal_title AGAINST ('" . $title . "') AS score
							from site_" . $siteId . "
							left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
							where " . $type . $niche . " MATCH gal_title AGAINST ('" . $title . "') group by site_" . $siteId . ".gal_id order by score desc;";
					$titleRs = $this->_db->query($sql);
					if ($titleRs) {
						$titleRow = $titleRs->fetchAll(\PDO::FETCH_ASSOC);
						if ($titleRow) {
							foreach ($titleRow as $titleCatch) {
								if ($titleCatch['id'] != $galId) $weightArray[$titleCatch['id']] = $titleCatch['score'];
							}
						}
					}

					$first_elem = true;
					if (isset($tags) && is_array($tags) && count($tags) > 0) {
						$sql = "select site_" . $siteId . ".gal_id, site_" . $siteId . ".id, count(site_" . $siteId . ".gal_id) as score
								from site_" . $siteId . "
								left join galleries_tags on galleries_tags.gal_id = site_" . $siteId . ".gal_id
								left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
								where " . $type . $niche . " galleries_tags.gal_tags IN (" . join(",", $tags) . ")
								GROUP BY site_" . $siteId . ".gal_id
								having score > 3
								ORDER BY score desc";
						$tagsRs = $this->_db->query($sql);
						if ($tagsRs) {
							$tagsRow = $tagsRs->fetchAll(\PDO::FETCH_ASSOC);
							if ($tagsRow) {
								foreach ($tagsRow as $tagCatch) {
									if ($tagCatch['id'] != $galId) {
										if (isset($weightArray[$tagCatch['id']])) $weightArray[$tagCatch['id']] += $tagCatch['score'] * 2.2;
										else $weightArray[$tagCatch['id']] = $tagCatch['score'] * 2.2;
									}
								}
							}
						}
					}

					if (isset($models) && is_array($models) && count($models) > 0) {
						foreach ($models as $model) {
							$model = intval($model);
							$sql = "select site_" . $siteId . ".gal_id, site_" . $siteId . ".id, count(site_" . $siteId . ".gal_id) as score
									from site_" . $siteId . "
									left join galleries_models on galleries_models.gallery_id = site_" . $siteId . ".gal_id
					                left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
									where " . $type . $niche . " galleries_models.model_id IN (" . join(",", $models) . ")
									GROUP BY site_" . $siteId . ".gal_id
									ORDER BY score desc";
							$modelRs = $this->_db->query($sql);
							if ($modelRs) {
								$modelRow = $modelRs->fetchAll(\PDO::FETCH_ASSOC);
								if ($modelRow) {
									foreach ($modelRow as $modelCatch) {
										if ($modelCatch['id'] != $galId) {
											if (isset($weightArray[$modelCatch['id']])) $weightArray[$modelCatch['id']] += $modelCatch['score'] * 4.2;
											else $weightArray[$modelCatch['id']] = $modelCatch['score'] * 4.2;
										}
									}
								}
							}
						}
					}

					if (isset($weightArray)) {
						arsort($weightArray);
						foreach ($weightArray as $id => $weightElement) {
							if ($weightElement >= 10) $exitArray[$id] = $weightElement;
							if ($weightElement >= 8) $exitArray_more_than_8[$id] = $weightElement;
							if ($weightElement >= 5) $exitArray_more_than_5[$id] = $weightElement;
						}
						if (isset($exitArray_more_than_5)) {
							if (!isset($exitArray) || count($exitArray) < 40) {
								if (isset($exitArray_more_than_8) && count($exitArray_more_than_8) > 40) $exitArray = $exitArray_more_than_8;
								else $exitArray = $exitArray_more_than_5;
							} else $exitArray = $exitArray;
						} else $exitArray = array();
						$result = $exitArray;
					}
				}
			}
		}
		return $result;
	}

	//
	//
	//		Старые функции
	//
	//
	//

	public function old_getRelatedGalleries_old($siteId, $galId, $global = false)
	{
		//$this->_db->debug = true;
		//$start = mktime();
		$result = false;
		$siteId = intval($siteId);
		$galId = intval($galId);

		$weightElement = array();
		//var_dump($galId);
		//var_dump($siteId);
		if ($siteId && $galId) {
			$sql = "select galleries.gal_id, galleries.gal_title, galleries.gal_description, galleries.gal_type, galleries.gal_niche, galleries_tags.gal_tags
					from galleries_tags
					left join galleries on galleries.gal_id = galleries_tags.gal_id
					left join site_" . $siteId . " on site_" . $siteId . ".gal_id = galleries.gal_id
					where site_" . $siteId . ".id = '" . $galId . "'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$global_id = $row[0]['gal_id'];
					$title = $row[0]['gal_title'];
					$description = $row[0]['gal_description'];
					$type = $row[0]['gal_type'];
					$niche = $row[0]['gal_niche'];
					if ($type == 'Movies') $type = " galleries.gal_type = 'Movies' and ";
					elseif ($type == 'gif') $type = " galleries.gal_type = 'gif' and ";
					else $type = " galleries.gal_type = 'Pics' and ";
					if (preg_match("#(Gay|Straight|Shemale)#", $niche)) $niche = " galleries.gal_niche = '" . $niche . "' and ";
					else $niche = "";
					foreach ($row as $element) {
						$tags[] = $element['gal_tags'];
					}
					$weightArray = array();
					$title = trim($title);
					$title = preg_replace("/[^a-z \']/im", "", $title);
					$title = preg_replace("/[\']/im", " ", $title);
					$title = explode(" ", $title);
					$counter = count($title);
					if ($counter) {
						$models = $this->getGalleryModels($global_id);
						//var_dump($models);
						for ($i = 0; $i < $counter; $i++) {
							if (strlen($title[$i]) >= 4) {
								$stem = PorterStemmer::Stem($title[$i]);
								$normalTitle[] = strtolower($stem);
							} elseif (preg_match('/ass|cum|sex/im', $title[$i])) {
								$normalTitle[] = strtolower($title[$i]);
							}
						}
						if (isset($normalTitle) && is_array($normalTitle)) $normalTitle = array_unique($normalTitle);
						//echo "\n\n";
						//echo "\nNormal title:\n";
						//var_dump($normalTitle);
						//echo "\n\n";
						//echo "\n\n";
						// поиск по каждому слову

						if (isset($normalTitle) && is_array($normalTitle)) {
							sort($normalTitle);
							foreach ($normalTitle as $titleKey) {
								$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id
										from site_" . $siteId . "
										left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
										where " . $type . $niche . " LOWER(galleries.gal_title) LIKE '%" . $titleKey . "%'";
								$titleRs = $this->_db->query($sql);
								if ($titleRs) {
									$titleRow = $titleRs->fetchAll(\PDO::FETCH_ASSOC);
									if ($titleRow) {
										foreach ($titleRow as $titleCatch) {
											if ($titleCatch['id'] != $galId) {
												if ($global) {
													if (isset($weightArray[$titleCatch['gal_id']])) {
														$weightArray[$titleCatch['gal_id']] += 3.4;
													} else {
														$weightArray[$titleCatch['gal_id']] = 3.4;
													}
												} else {
													if (isset($weightArray[$titleCatch['id']])) {
														$weightArray[$titleCatch['id']] += 3.4;
													} else {
														$weightArray[$titleCatch['id']] = 3.4;
													}
												}
											}
										}
									}
								}
							}
						}
						// var_dump($weightArray);
						if (isset($tags) && is_array($tags) && count($tags) > 0) {
							foreach ($tags as $tag) {
								$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id
										from site_" . $siteId . "
										left join galleries_tags on galleries_tags.gal_id = site_" . $siteId . ".gal_id
										left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
										where " . $type . $niche . " galleries_tags.gal_tags = '" . $tag . "'";
								$tagsRs = $this->_db->query($sql);
								if ($tagsRs) {
									$tagsRow = $tagsRs->fetchAll(\PDO::FETCH_ASSOC);
									if ($tagsRow) {
										foreach ($tagsRow as $tagCatch) {
											if ($tagCatch['id'] != $galId) {
												if ($global) {
													if (isset($weightArray[$tagCatch['gal_id']])) {
														$weightArray[$tagCatch['gal_id']] += 3.6;
													} else {
														$weightArray[$tagCatch['gal_id']] = 3.6;
													}
												} else {
													if (isset($weightArray[$tagCatch['id']])) {
														$weightArray[$tagCatch['id']] += 3.6;
													} else {
														$weightArray[$tagCatch['id']] = 3.6;
													}
												}
											}
										}
									}
								}
							}
						}
						if (isset($models) && is_array($models) && count($models) > 0) {
							foreach ($models as $model) {
								$model = intval($model);
								$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id
			                        from site_" . $siteId . "
			                        left join galleries_models on galleries_models.gallery_id = site_" . $siteId . ".gal_id
			                        left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
			                        where " . $type . $niche . " galleries_models.model_id = '" . $model . "'";
								$modelRs = $this->_db->query($sql);
								if ($modelRs) {
									$modelRow = $modelRs->fetchAll(\PDO::FETCH_ASSOC);
									//var_dump($modelRow);
									if ($modelRow) {
										foreach ($modelRow as $modelCatch) {
											if ($modelCatch['id'] != $galId) {
												if ($global) {
													if (isset($weightArray[$modelCatch['gal_id']])) {
														$weightArray[$modelCatch['gal_id']] += 4.3;
													} else {
														$weightArray[$modelCatch['gal_id']] = 4.3;
													}
												} else {
													if (isset($weightArray[$modelCatch['id']])) $weightArray[$modelCatch['id']] += 4.2;
													else $weightArray[$modelCatch['id']] = 4.2;
												}
											}
										}
									}
								}
							}
						}
						if (isset($weightArray)) {
							arsort($weightArray);
							foreach ($weightArray as $id => $weightElement) {
								if ($weightElement >= 10) $exitArray[$id] = $weightElement;
								if ($weightElement >= 8) $exitArray_more_than_8[$id] = $weightElement;
								if ($weightElement >= 5) $exitArray_more_than_5[$id] = $weightElement;
							}
							if (isset($exitArray_more_than_5)) {
								if (!isset($exitArray) || count($exitArray) < 40) {
									if (isset($exitArray_more_than_8) && count($exitArray_more_than_8) > 40) $exitArray = $exitArray_more_than_8;
									else $exitArray = $exitArray_more_than_5;
								} else $exitArray = $exitArray;
							} else $exitArray = array();
							$result = $exitArray;
							//echo "\nExit array:\n";
							//var_dump($exitArray);
						}
					}
					// поиск по тегам
					// совмещение результатов
				}
			}
			//echo "<br>".mktime() - $start."<br>";			
		}
		return $result;
	}


	public function old_getRelatedGalleries_new($siteId, $galId, $global = false)
	{
		//		$this->_db->debug = true;
		$result = false;
		$siteId = intval($siteId);
		$galId = intval($galId);
		$weightElement = array();
		$weightArray = array();
		if ($siteId && $galId) {
			$sql = "select galleries.gal_id, galleries.gal_title, galleries.gal_description, galleries.gal_type, galleries.gal_niche, 					galleries_tags.gal_tags
				from galleries_tags
				left join galleries on galleries.gal_id = galleries_tags.gal_id
				left join site_" . $siteId . " on site_" . $siteId . ".gal_id = galleries.gal_id";
			if ($global) $sql .= " where site_" . $siteId . ".gal_id = '" . $galId . "'";
			else $sql .= " where site_" . $siteId . ".id = '" . $galId . "'";

			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$global_id = $row[0]['gal_id'];
					$models = $this->getGalleryModels($global_id);
					$title = $row[0]['gal_title'];
					$description = $row[0]['gal_description'];
					$type = $row[0]['gal_type'];
					$niche = $row[0]['gal_niche'];
					if ($type == 'Movies') $type = " galleries.gal_type = 'Movies' and ";
					elseif ($type == 'gif') $type = " galleries.gal_type = 'gif' and ";
					else $type = " galleries.gal_type = 'Pics' and ";
					if (preg_match("#(Gay|Straight|Shemale)#", $niche)) $niche = " galleries.gal_niche = '" . $niche . "' and ";
					else $niche = "";
					foreach ($row as $element) {
						$tags[] = $element['gal_tags'];
					}
					$title = strtolower(trim($title));
					$title = preg_replace("/[^a-z \']/im", "", $title);
					$title = preg_replace("/[\']/im", " ", $title);

					$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id,
							MATCH gal_title AGAINST ('" . $title . "') AS score
							from site_" . $siteId . "
							left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
							where " . $type . $niche . " MATCH gal_title AGAINST ('" . $title . "') group by site_" . $siteId . ".gal_id order by score desc;";
					$titleRs = $this->_db->query($sql);
					if ($titleRs) {
						$titleRow = $titleRs->fetchAll(\PDO::FETCH_ASSOC);
						if ($titleRow) {
							foreach ($titleRow as $titleCatch) {
								if ($global && $titleCatch['gal_id'] != $galId) $weightArray[$titleCatch['gal_id']] = ($titleCatch['score']);
								elseif ($titleCatch['id'] != $galId) $weightArray[$titleCatch['id']] = ($titleCatch['score']);
							}
						}
					}

					$start = get_time();
					if (isset($tags) && is_array($tags) && count($tags) > 0) {
						foreach ($tags as $tag) {
							$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id
										from site_" . $siteId . "
										left join galleries_tags on galleries_tags.gal_id = site_" . $siteId . ".gal_id
										left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
										where " . $type . $niche . " galleries_tags.gal_tags = '" . $tag . "'";
							$tagsRs = $this->_db->query($sql);
							if ($tagsRs) {
								$tagsRow = $tagsRs->fetchAll(\PDO::FETCH_ASSOC);
								if ($tagsRow) {
									foreach ($tagsRow as $tagCatch) {
										if ($tagCatch['id'] != $galId) {
											if ($global) {
												if (isset($weightArray[$tagCatch['gal_id']])) {
													$weightArray[$tagCatch['gal_id']] += 2.6;
												} else {
													$weightArray[$tagCatch['gal_id']] = 2.6;
												}
											} else {
												if (isset($weightArray[$tagCatch['id']])) {
													$weightArray[$tagCatch['id']] += 2.6;
												} else {
													$weightArray[$tagCatch['id']] = 2.6;
												}
											}
										}
									}
								}
							}
						}
					}
					$finish = get_time();
					$exec_time = $finish - $start;
					//echo "\n\nExec time: ".$exec_time." \n\n";
					if (isset($models) && is_array($models) && count($models) > 0) {
						foreach ($models as $model) {
							$model = intval($model);
							$sql = "select  site_" . $siteId . ".gal_id, site_" . $siteId . ".id
			                        from site_" . $siteId . "
			                        left join galleries_models on galleries_models.gallery_id = site_" . $siteId . ".gal_id
			                        left join galleries on galleries.gal_id = site_" . $siteId . ".gal_id
			                        where " . $type . $niche . " galleries_models.model_id = '" . $model . "'";
							$modelRs = $this->_db->query($sql);
							if ($modelRs) {
								$modelRow = $modelRs->fetchAll(\PDO::FETCH_ASSOC);
								//var_dump($modelRow);
								if ($modelRow) {
									foreach ($modelRow as $modelCatch) {
										if ($modelCatch['id'] != $galId) {
											if ($global) {
												if (isset($weightArray[$modelCatch['gal_id']])) {
													$weightArray[$modelCatch['gal_id']] += 4.4;
												} else {
													$weightArray[$modelCatch['gal_id']] = 4.4;
												}
											} else {
												if (isset($weightArray[$modelCatch['id']])) $weightArray[$modelCatch['id']] += 4.4;
												else $weightArray[$modelCatch['id']] = 4.4;
											}
										}
									}
								}
							}
						}
					}
					if (isset($weightArray)) {
						arsort($weightArray);
						foreach ($weightArray as $id => $weightElement) {
							if ($weightElement >= 10) $exitArray[$id] = $weightElement;
							if ($weightElement >= 8) $exitArray_more_than_8[$id] = $weightElement;
							if ($weightElement >= 5) $exitArray_more_than_5[$id] = $weightElement;
						}
						if (isset($exitArray_more_than_5)) {
							if (!isset($exitArray) || count($exitArray) < 40) {
								if (isset($exitArray_more_than_8) && count($exitArray_more_than_8) > 40) $exitArray = $exitArray_more_than_8;
								else $exitArray = $exitArray_more_than_5;
							} else $exitArray = $exitArray;
						} else $exitArray = array();
						$result = $exitArray;
						//echo "\nExit array:\n";
						//var_dump($exitArray);
					}
					// поиск по тегам
					// совмещение результатов
				}
			}
			//echo "<br>".mktime() - $start."<br>";			
		}
		return $result;
	}

	// Utils

	//
	//	Пояснения к to_merge:
	//	gal_id - основная галера, общая для группы склеиваимых видео, merge_galleries - ID конкретной галлереи
	//

	public function getMergeStatus($gal_id)
	{
		$sql = "SELECT status 
				FROM galleries_to_merge
				WHERE merge_galleries = '" . $gal_id . "'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$items = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($items && isset($items[0]['status'])) return $items[0]['status'];
		}
		return false;
	}

	public function get_merging_table_content()
	{
		$sql = "SELECT * FROM galleries_to_merge";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$items = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($items) {
				return $items;
			}
		}
		return false;
	}

	public function get_processing_galleries()
	{
		$sql = "SELECT * FROM get_processing_galleries";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$items = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($items) {
				return $items;
			}
		}
		return false;
	}

	public function clear_processing_query()
	{
		$query = $this->showQuery();
		if ($query && is_array($query)) {
			foreach ($query as $query_item) {
			}
		}
	}

	public function removeFromMergingQuery($gal_id)
	{
		$ga_id = intval($gal_id);
		$sql = "delete from galleries_to_merge where merge_galleries = '" . $gal_id . "'";
		$rs = $this->_db->query($sql);
		if ($rs)	return true;
		return false;
	}

	public function resetGalleriesFromMergingQuery($gal_id)
	{
		$gal_id = intval($gal_id);
		$sql = "select merge_galleries from galleries_to_merge where gal_id = '" . $gal_id . "'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($rows && is_array($rows)) {
				foreach ($rows as $row) {
					if ($this->setStatus($row['merge_galleries'], 'uploaded')) {
						$this->removeFromMergingQuery($row['merge_galleries']);
						$this->removeFromQuery($row['merge_galleries']);
					} else echo "Failed to set status 'uploaded' for " . $row['merge_galleries'] . "<br>";
				}
			}
		}
		return false;
	}

	public function countGalleriesToGrab($paysite_id)
	{
		if (intval($paysite_id)) $add_paysite_sql = " AND gal_paysite = '" . intval($paysite_id) . "' ";
		else $add_paysite_sql = "";
		$sql = "SELECT COUNT(gal_id) 
				FROM galleries 
				WHERE gal_status IN ('new', 'toregrab')  
				AND gal_id NOT IN (
					SELECT gal_id FROM main_query
				)" . $add_paysite_sql;
		$stmt = $this->_db->prepare($sql);
		$rs = $stmt->execute();
		if ($rs === false) {
			$log = new Logger(__METHOD__ . ":Ошибка выборки галлерей новых галлерей из граббера: " . $this->_db->errorInfo(), true);
			echo "Ошибка выборки галлерей новых галлерей из граббера: " . $this->_db->errorInfo() . "<br>";
		} else {
			$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			if ($rows[0]['COUNT(gal_id)']) return intval($rows[0]['COUNT(gal_id)']);
		}
		return 0;
	}

	public function galleriesToGrab($page, $count, $sort_by, $sort_order, $paysite_id)
	{

		$sort_by 			= (preg_match("#(asc|desc)#im", $sort_by)) ? strtoupper($sort_by) : "ASC";
		$offset 			= (intval($page)) ? $count * $page : 0;
		$add_paysite_sql 	= (intval($paysite_id)) ? " AND gal_paysite = '" . intval($paysite_id) . "' " : "";
		$sortString 		= ($sort_by == 'title') ? " ORDER BY gal_title " . $sort_by : " ORDER BY gal_id " . $sort_by;

		$sql = "SELECT DISTINCT galleries.gal_id, galleries.gal_source, galleries.gal_paysite,
					   galleries.gal_title, paysites.paysite_name, paysites.paysite_niche,
					   paysites.paysite_category, 
					   IF (paysites.paysite_category = '0', 'None', tags.tag_name) as tag_name
				FROM galleries
				LEFT JOIN paysites ON galleries.gal_paysite = paysites.paysite_id
				LEFT JOIN tags ON paysites.paysite_category = tags.tag_id
				WHERE gal_status IN ('new', 'toregrab') 
				AND gal_id NOT IN (
					SELECT gal_id FROM main_query
				)
				" .
			$add_paysite_sql .
			$sortString . " 
				LIMIT " . $offset . ", " . $count . ";";
		$stmt = $this->_db->prepare($sql);
		$rs = $stmt->execute();
		if ($rs === false) {
			$log = new Logger(__METHOD__ . ":Ошибка выборки галлерей новых галлерей из граббера: " . $this->_db->errorInfo(), true);
			echo "Ошибка выборки галлерей новых галлерей из граббера: " . $this->_db->errorInfo() . "<br>";
		} else {
			return $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
	}

	public function getAdditionTitle($gal_id, $title_id) {}

	public function getAllAdditionTitles($id, $with_used = false)
	{
		//	тоже самое есть в class.gallleries.php
		$id = intval($id);
		$result = false;
		if ($id !== 0) {
			$sql = "SELECT id, title, language, used_on FROM 
				additional_titles
				WHERE gal_id = " . $id;
			if ($with_used) $sql .= " AND used_on <> 0;";
			else $sql .= ";";
			$db = DB::get();
			$q_result = $db->query($sql);
			while ($row = $q_result->fetch_assoc()) {
				$result[$row['id']]['id'] = $row['id'];
				$result[$row['id']]['title'] = $row['title'];
				$result[$row['id']]['language'] = $row['language'];
				$result[$row['id']]['used_on'] = $row['used_on'];
			}
		}
		return $result;
	}

	public function setAdditionalTitleUsedOn($title_id, $site_id) {}

	public function updateAdditionalTitle($gal_id, $title_id, $title, $language = false, $used_on = false)
	{
		$result = false;
		$title_id = (int)$title_id;
		$gal_id = (int)$gal_id;
		$sql = "UPDATE `additional_titles` SET `title` = ?";
		$sql_array = array($title);
		if ($language) {
			$sql .=  " `language` = :language";
			$sql_array[':language'] = $language;
		}
		if ($used_on) {
			$sql .=  " `used_on` = :used_on";
			$sql_array[':used_on'] = $used_on;
		}
		$sql .= "WHERE `gal_id` = :gal_id and `title_id` = :title_id";
		$sql_array[':gal_id'] = $gal_id;
		$sql_array[':title_id'] = $title_id;
		$stmt = $this->_db->prepare($sql);
		if ($stmt->execute($sql_array) === false) {
			// ошибка добавления в базу
			echo "Ошибка добавления в базу<br>";
		} else $result = true;
		return false;
	}

	public function deleteAdditionalTitle($gal_id, $title_id)
	{
		$title_id = (int)$title_id;
		$gal_id = (int)$gal_id;
		$sql = "DELETE FROM `additional_titles` WHERE
  				`gal_id` = :gal_id and title_id = :title_id";
		$stmt = $this->_db->prepare($sql);
		if ($stmt->execute(array(':gal_id' => $gal_id, ':title_id' => $title_id)) === false) {
			echo "Ошибка добавления в базу<br>";
			return false;
		} else {
			return true;
		}
	}

	private function getGalByUrl($gal_url)
	{
		$result = false;
		$gal_md5 = md5($gal_url);
		$sql = "SELECT gal_id FROM galleries WHERE gal_md5 = :gal_md5";
		$stmt = $this->_db->prepare($sql);
		$rs = $stmt->Execute(array(':gal_md5' => $gal_md5));
		if ($rs) {
			$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($row) {
				$result = $row[0]['gal_id'];
			}
		}

		return $result;
	}

	public function addTitleToExistingGal($gal_url, $title, $title_language)
	{
		$result = false;
		$gal_id = $this->getGalByUrl($gal_url);
		if ($gal_id) {
			$title_added = $this->insertAdditionalTitle($gal_id, $title, $title_language, true);
			if ($title_added) $result = $gal_id;
			else $this->error_message = "Не получилось добавить тайтл для GID:'" . $gal_id . "'";
		} else {
			$this->error_message = "Не найден gal_id для переданного урла";
		}
		return $result;
	}



	public function insertAdditionalTitle(int $gal_id, $title, $language = false, $gal_id_verified = false)
	{
		$result = false;

		if ($gal_id && $title) {

			if ($gal_id_verified || $this->getGalleryMD5($gal_id)) {

				$used_on = 0;
				$added_on = time();
				$prepare_array = array(
					':gal_id' => $gal_id,
					':title' => $title,
					':added_on' => $added_on,
					':used_on' => $used_on
				);
				if ($language) {
					if (preg_match("#(en|nl|cz|de|be|es|ru|by|cn|jp|it|fr)#", $language)) {
						$sql = "INSERT INTO additional_titles 
  								(gal_id, title, added_on, used_on, language) 
  								VALUE (:gal_id, :title, :added_on, :used_on, :language )";
						$prepare_array[':language'] = $language;
					} else {
						$log = new Logger(__METHOD__ . " : параметр language неверный: '" . $language . "'", true);
						return false;
					}
				} else {
					$sql = "INSERT INTO additional_titles 
  							(gal_id, title, added_on, used_on) 
  							VALUE (:gal_id, :title, :added_on, :used_on)";
				}

				$stmt = $this->_db->prepare($sql);

				if ($stmt->execute($prepare_array) === false) {
					// ошибка добавления в базу
					$log = new Logger(__METHOD__ . ":Ошибка добавления в базу дополнительного тайтла. GID: '" . $gal_id . "', Title: '" . $title . "'", true);
					echo "Ошибка добавления в базу дополнительного тайтла. GID: '" . $gal_id . "', Title: '" . $title . "'";
				} else $result = $this->_db->lastInsertId();
			} else {
				// ошибка - галеры нет (md5 == false)
				$log = new Logger(__METHOD__ . ":Ошибка добавления в базу дополнительного тайтла. Не найдена галера GID: '" . $gal_id . "'", true);
				echo "Ошибка добавления в базу дополнительного тайтла. Не найдена галера GID: '" . $gal_id . "'";
			}
		} else {
			// ошибка - id галеры не int или == 0
		}
		return $result;
	}

	public function insertAdditionalTitles(int $gal_id, $titles)
	{
		$result = false;
		if ($gal_id > 0) {
			if (is_array($titles) && $titles) {
				foreach ($titles as $title) {
					if (!$this->insertAdditionalTitle($gal_id, $title)) return false;
				}
				$result = true;
			} else {
				// ошибка titles не массив
			}
		} else {
			// ошибка - id галеры не int или == 0
		}
		return $result;
	}


	private function updateImageRatio($image_id, $horiz_resize_ratio)
	{
		$result = false;
		if ($image_id) {
			$db = DB::get();
			if ($db) {
				$sql = "UPDATE galleries_pix
						SET ratio_w_h = ?
						WHERE image_id = ?";
				// echo "EXECUTE";
				// var_dump($horiz_resize_ratio, $image_id);
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("di", $horiz_resize_ratio, $image_id)) {
						if ($stmt->execute()) {
							$result = true;
							var_dump($result);
						} else {
							$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": bind_param failed: " . $stmt->error, true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": No DB connect", true);
			}
		}
		return $result;
	}

	public function fixNoRatioGalleries($gal_count = false, $gal_id = false)
	{
		$result = false;
		$images_to_resize = false;
		$db = DB::get();
		if ($db) {
			$sql = "SELECT galleries_pix.gal_id, galleries_pix.image_id 
					FROM galleries_resized_to
					INNER JOIN galleries_pix ON galleries_resized_to.gal_id = galleries_pix.gal_id
					WHERE galleries_resized_to.status = 'ok'
					AND galleries_pix.ratio_w_h = 0 ";
			if ($gal_id) $sql .= " AND gal_id = ? ";
			if ($gal_count) $sql .= " LIMIT 0, " . (int)$gal_count . ";";
			else $sql .= " LIMIT 0, 10;";

			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($gal_id) $stmt->bind_param("i", $gal_id);

				if ($stmt->execute()) {
					$gallery = null;
					$image_id = 0;
					$stmt->bind_result($gallery, $image_id);
					while ($stmt->fetch()) {
						$image_path = $this->getHorizThumbPath($image_id);
						if ($image_path) {
							$ratio = getImageRatio($image_path);
							if ($ratio) {
								$result[$gallery] = $gallery;
								$images_to_resize[$image_id] = $ratio;
								$log = new Logger(__METHOD__ . ": ратио изображения IMID#" . $image_id . ", GID#" . $gallery);
							} else {
								echo "Ошибка счета ратио для GID#'" . $gallery . "', IMID#'" . $gallery . "'<br>\n";
							}
						}
					}
				} else {
					$log = new Logger(__METHOD__ . ": DB execute failed: " . $stmt->error, true);
				}

				if ($images_to_resize) {
					foreach ($images_to_resize as $image_id => $ratio) {
						$this->updateImageRatio($image_id, $ratio);
					}
				}
			} else {
				$log = new Logger(__METHOD__ . ": STMT failed: " . $db->error, true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": No DB connect", true);
		}
		// var_dump($result);
		return $result;
	}
}
