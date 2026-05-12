<?php
class SitesGalleries {
	
	private $site_id = false;
	public $error = false;
	private $db = NULL;

	private $gal_id = false;
	private $gal_local_id = false;
	private $gal_type = false;
	private $gal_source = false;
	private $gal_tags = array();
	private $gal_models = array();

	function setSiteId($site_id) {
		$result = false;

		$site_id = (int)$site_id;

		if($site_id > 0) {
			$this->site_id = $site_id;
			$result = true;
		} else {
			$this->error = __METHOD__.": Неверный параметр site_id, сайт не переключен";
		}

		return $result;
	}

	function checkGalleriesTables() {
		$result = false;
		if($this->site_id) {
			$gals_in_site_table = $this->getGalleriesCountInSiteTable();
			$gals_in_sites_galleries_table = $this->getGalleriesCountInSitesGalleriesTable();
			// var_dump($gals_in_site_table, $gals_in_sites_galleries_table);
			if($gals_in_site_table && $gals_in_sites_galleries_table) {

				if($gals_in_site_table != $gals_in_sites_galleries_table) {
					// $this->fixSiteGalleriesTable();
					// кол-во галлерей в таблицах site_# и sites_galleries не совпадают
					// необходимо исправить ситуацию
					// 1. Проверяем обе таблицы на ошибки, т.е. сравниваем с таблицей galleries в
					// 	  в поисках NULL (удаленных) или галлерей вне статуса ОК
					// 2. Если найдены битые галлереии, просходит следующее:
					// 	  - собираем список галлерей
					//	  - удаляем их из site_#_galleries_tags
					//	  - удаляем их из site_#_galleries_models
					//	  - удаляем из таблицы где они были найдены
					//	  - пересобираем счетчики sites_sources, sites_tags, sites_models
					// 3. Если не найдены, сравниваем, в какой таблице большее кол-во галлерей
					//	  если галер больше в site_#, происходит следующее:
					// 	  - собираем список галлерей
					//	  - удаляем их из site_#_galleries_tags
					//	  - удаляем их из site_#_galleries_models
					//	  - форсированно добавляем галлереии по обыкновенной схеме, исключая добавление в site_#
					//		чтобы
					//	  - пересобираем счетчики sites_sources, sites_tags, sites_models
				}	
			} else {
				// один из счетчиков == false или 0
			}
			
		}
		return $result;
	}

	function getGalleryInfo($gal_id, &$db = NULL) {
		$result = false;

		$this->gal_id = false;
		$this->gal_local_id = false;
		$this->gal_type = false;
		$this->gal_source = false;
		$this->gal_tags = array();
		$this->gal_models = array();		

		$gal_id = (int)$gal_id;
		if($this->site_id && $gal_id > 0) {

			if($db == NULL) {
				$db = DB::get();
			}

			$sql = "SELECT 	site_".$this->site_id.".id, site_".$this->site_id.".gal_id AS global_id, 
						   	LOWER(galleries.gal_type),
						   	site_".$this->site_id.".gal_paysite,
							(
								SELECT group_concat(site_".$this->site_id."_galleries_tags.tag_id)
								FROM site_".$this->site_id."_galleries_tags
								WHERE site_".$this->site_id."_galleries_tags.gal_id = global_id 
							) AS gal_tags,
							(
								SELECT group_concat(site_".$this->site_id."_galleries_models.model_id)
								FROM site_".$this->site_id."_galleries_models
								WHERE site_".$this->site_id."_galleries_models.gal_id = global_id 
							) AS gal_models
					FROM site_".$this->site_id."
					LEFT JOIN galleries ON site_".$this->site_id.".gal_id = galleries.gal_id
					WHERE site_".$this->site_id.".gal_id = '".$gal_id."'";
			$stmt = $db->prepare($sql);

			if($stmt) {
				if($stmt->execute()) {

					$gal_tags = "";
					$gal_models = "";
					$stmt->bind_result($this->gal_local_id, $this->gal_id, $this->gal_type, 
									   $this->gal_source, $gal_tags, $gal_models);
					if($stmt->fetch()) {
						if($this->gal_id) {
							if($gal_tags != NULL) {
								$a_gal_tags = explode(",", $gal_tags);
								if($a_gal_tags) {
									$this->gal_tags = $a_gal_tags;	
								}
							}
							if($gal_models != NULL) {
								$a_gal_models = explode(",", $gal_models);
								if($a_gal_models) {
									$this->gal_models = $a_gal_models;	
								}
								
							}
							$result = true;
						} else {
							var_dump($stmt->error);
						}
					} else {

						var_dump($stmt->error);
					}
				} else {
					var_dump($stmt->error);
				}

			} else {
				//
			}


		} else {
			// неверный параметр $gal_id
		}	

		return $result;
	}

	function getSiteGalleryShortInfo($site_id, $gal_id) {
		$result = false;
		$site_id = (int)$site_id;
		$gal_id = (int)$gal_id;

		if($site_id > 0 && $gal_id) {

			$db = DB::get();

			$sql = "SELECT 	site_".$site_id.".id, 
							site_".$site_id.".gal_id AS global_id, 
						   	site_".$site_id.".gal_type,
						   	site_".$site_id.".gal_paysite,
						   	site_".$site_id.".url_desc,
						   	site_".$site_id.".own_title,
						   	site_".$site_id.".own_main_thumb,
						   	site_".$site_id.".time_added,
						   	site_".$site_id.".pageviews,
						   	site_".$site_id.".likes
					FROM site_".$site_id."
					WHERE site_".$site_id.".gal_id = ".$gal_id.";";

			$stmt = $db->prepare($sql);

			if($stmt) {
				if($stmt->execute()) {
					$id = false; 
					$global_id = false;
					$gal_type = false;
					$gal_title = false;
					$gal_paysite = false;
					$url_desc = false;
					$own_title = false;
					$own_main_thumb = false;
					$time_added = false;
					$pageviews = false;
					$likes = false;

					$stmt->bind_result($id, $global_id, $gal_type,$gal_paysite, 
									   $url_desc, $own_title, $own_main_thumb, $time_added, 
									   $pageviews, $likes);
					if($stmt->fetch()) {

						$gal_tags = false;
						$gal_models = false;

							$result = compact("id", "global_id", "gal_type", "gal_title", "gal_paysite", 
												"url_desc", "own_title", "own_main_thumb", "time_added",
												"pageviews", "likes");

					}
				} else {
					var_dump($stmt->error);
				}

			} else {
				$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."' ", true);
			}


		} else {
			$log = new Logger(__METHOD__.": gal_id или site_id не указаны ", true);
		}	

		return $result;
	}

	function getAllSiteGalleriesUrls($site_id, $page = 0, $limit = 10000) {
		$result = false;
		$site_id = (int)$site_id;


		if($site_id > 0) {

			$db = DB::get();

			$sql = "SELECT 	site_".$site_id.".id, 
							site_".$site_id.".gal_id AS global_id, 
						   	LOWER(galleries.gal_type),
						   	site_".$site_id.".own_title,
						   	site_".$site_id.".url_desc,						   	
						   	site_".$site_id.".time_added
					FROM site_".$site_id."
					LEFT JOIN galleries ON site_".$site_id.".gal_id = galleries.gal_id
					ORDER BY site_".$site_id.".id ASC
					LIMIT ".$page.", ". $limit.";";

					// var_dump($sql);

			$stmt = $db->prepare($sql);

			if($stmt) {
				if($stmt->execute()) {
					$id = false; 
					$global_id = false;
					$gal_type = false;
					$gal_title = false;
					$url_desc = false;
					$time_added = false;


					$stmt->bind_result($id, $global_id, $gal_type, $gal_title, $url_desc, 
									   $time_added);
					while($stmt->fetch()) {
							$result[] = compact("id", "global_id", "gal_type", "gal_title", "url_desc",
							 					"time_added");

					}
				} else {
					var_dump($stmt->error);
				}

			} else {
				$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."' ", true);
			}


		} else {
			$log = new Logger(__METHOD__.": gal_id или site_id не указаны ", true);
		}	

		return $result;		
	}


	function getSiteGalleriesInfo($site_id, $gal_ids) {
		$result = false;
		$site_id = (int)$site_id;
		$gal_id_list = false;

		if(is_array($gal_ids)) {
			foreach ($gal_ids as $gal_id) {
				$gal_id = (int)$gal_id;
				if($gal_id > 0) {
					$gal_id_list[] = $gal_id;
				} else {
					$log = new Logger(__METHOD__.": в массиве галлерей неверные данные", true);
				}	
			}
		} else {
			$gal_id_list[] = $gal_ids;
		}

		if($site_id > 0 && $gal_id_list) {

			$db = DB::get();

			$sql = "SELECT 	site_".$site_id.".id, 
							site_".$site_id.".gal_id AS global_id, 
						   	LOWER(galleries.gal_type),
						   	galleries.gal_title,
						   	galleries.gal_paysite,
						   	site_".$site_id.".url_desc,
						   	site_".$site_id.".own_title,
						   	site_".$site_id.".own_main_thumb,
						   	site_".$site_id.".time_added,
						   	site_".$site_id.".pageviews,
						   	site_".$site_id.".likes,
							(
								SELECT group_concat(site_".$site_id."_galleries_tags.tag_id)
								FROM site_".$site_id."_galleries_tags
								WHERE site_".$site_id."_galleries_tags.gal_id = global_id 
							) AS gal_tags,
							(
								SELECT group_concat(site_".$site_id."_galleries_models.model_id)
								FROM site_".$site_id."_galleries_models
								WHERE site_".$site_id."_galleries_models.gal_id = global_id 
							) AS gal_models
					FROM site_".$site_id."
					LEFT JOIN galleries ON site_".$site_id.".gal_id = galleries.gal_id
					WHERE site_".$site_id.".gal_id IN (".implode(",", $gal_id_list).");";

			$stmt = $db->prepare($sql);

			if($stmt) {
				if($stmt->execute()) {
					$id = false; 
					$global_id = false;
					$gal_type = false;
					$gal_title = false;
					$gal_paysite = false;
					$url_desc = false;
					$own_title = false;
					$own_main_thumb = false;
					$time_added = false;
					$pageviews = false;
					$likes = false;
					$gal_tags_tmp = "";
					$gal_models_tmp = "";

					$stmt->bind_result($id, $global_id, $gal_type, $gal_title, $gal_paysite, 
									   $url_desc, $own_title, $own_main_thumb, $time_added, 
									   $pageviews, $likes, $gal_tags_tmp, $gal_models_tmp);
					while($stmt->fetch()) {

						$gal_tags = false;
						$gal_models = false;

							if($gal_tags_tmp != NULL) {
								$gal_tags_tmp = explode(",", $gal_tags_tmp);
								if($gal_tags_tmp) {
									$gal_tags = $gal_tags_tmp;	
								}
							}
							if($gal_models_tmp != NULL) {
								$gal_models_tmp = explode(",", $gal_models_tmp);
								if($gal_models_tmp) {
									$gal_models = $gal_models_tmp;	
								}
								
							}
							$result[] = compact("id", "global_id", "gal_type", "gal_title", "gal_paysite", 
												"url_desc", "own_title", "own_main_thumb", "time_added",
												"pageviews", "likes", "gal_tags", "gal_models");

					}
				} else {
					var_dump($stmt->error);
				}

			} else {
				$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."' ", true);
			}


		} else {
			$log = new Logger(__METHOD__.": gal_id или site_id не указаны ", true);
		}	

		return $result;
	}

	private function getChangeQueryDoubles() {
		$result = false;
		$db = DB::get();
		if($db) {
				$sql = "SELECT FST.id FROM `galleries_changes_query` FST
						INNER JOIN `galleries_changes_query` SND ON
						(
							FST.gal_id = SND.gal_id AND
							FST.site_id = SND.site_id AND
							FST.item_type = SND.item_type AND
							FST.item_id = SND.item_id
						)
						WHERE (FST.change_type = 'added' AND SND.change_type = 'removed') OR 
						(FST.change_type = 'removed' AND SND.change_type = 'added')";

				$stmt = $db->prepare($sql);
				if($stmt) {
						if($stmt->execute()) {
							$query_id = false;
							$stmt->bind_result($query_id);
							while($stmt->fetch()) {
								$result[] = $query_id;
							}
							
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
		} else {
			$log = new Logger(__METHOD__.": NO DB CONNECTION", true);
		}
		return $result;
	}

	function queryCount() {
		$db = DB::get();
		if($db) {
				$sql = "SELECT count(id) AS counter FROM `galleries_changes_query`";
				$stmt = $db->prepare($sql);
				if($stmt) {
						if($stmt->execute()) {
							$x = null;
							$stmt->bind_result($x);
							$stmt->fetch();
							return $x;
							
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
		} else {
			$log = new Logger(__METHOD__.": NO DB CONNECTION", true);
		}
		return false;
	}

	function deleteFromChangeQueryById($id_list, &$db = NULL) {
		$result = false;
		if($db == NULL) {
			$db = DB::get();
		}

		if($db) {
			$query_ids_to_delete = false;
			if($id_list && is_array($id_list)) {
				foreach ($id_list as $query_id) {
					$query_id = (int)$query_id;
					$query_id ? $query_ids_to_delete[] = $query_id : null;
				}
			} elseif($id_list) {
				$id_list = (int)$id_list;
				$id_list ? $query_ids_to_delete[] = $id_list : null;
			}
			if($query_ids_to_delete && is_array($query_ids_to_delete)) {
				$sql = "DELETE FROM galleries_changes_query WHERE id IN (".implode(",", $query_ids_to_delete).")";
				$stmt = $db->prepare($sql);
				if($stmt) {
						if($stmt->execute()) {
							$result = true;
							
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			}
		}
		return $result;
	}

	function clearChangeQuerieDoubles() {
		$result = true;
		$db = DB::get();

		$start = time();
		$query_ids_to_delete = $this->getChangeQueryDoubles();	

		$fin = time();
		$res = $fin-$start;
		// echo  "getChangeQueryDoubles:  {$res}\n";
		// var_dump($query_ids_to_delete);
		if($query_ids_to_delete) {
			$start = time();
			$this->deleteFromChangeQueryById($query_ids_to_delete);

			$fin = time();
			$res = $fin-$start;
			// echo  "deleteFromChangeQueryById:  {$res}\n";
		}
			
		return $result;
	}


	function getWorkFromQuery() {
		$result = false;

		$db = DB::get();

		if($db) {

			//$start = time();
			//$this->clearChangeQuerieDoubles();

			//$fin = time();
			//$res = $fin-$start;
			//echo  "clearChangeQuerieDoubles:  {$res}\n";

			$db->autocommit(false);

			$updated_on = time();

			$sql = "UPDATE galleries_changes_query UT
					SET UT.processed = 1, UT.updated_on = '".$updated_on."'
					WHERE UT.id IN (
						SELECT BT.id AS i_id FROM (
						          SELECT id
					                  FROM galleries_changes_query
					                  WHERE processed = 0 AND error = 0
					                  ORDER BY added_on ASC 
					                  LIMIT 1
					        ) AS BT
					)";
			$all_query_ok = true;

			$db->query($sql) ? null : $all_query_ok = false;
			// var_dump($all_query_ok);

			if($all_query_ok) {
				$all_query_ok = false;
				$sql = "SELECT SQL_NO_CACHE id, gal_id, site_id, item_type, change_type, item_id, processed, added_on, updated_on
					    FROM galleries_changes_query
					    WHERE processed = 1 AND error = 0 AND updated_on = ".$updated_on."
					    LIMIT 1";
				$stmt = $db->prepare($sql);
				if($stmt) {
						if($stmt->execute()) {

							$id = false;
							$gal_id = false;
							$site_id = false;
							$item_type = false;
							$change_type = false;
							$item_id = false;
							$processed = false;
							$added_on = false;
							$updated_on = false;

							$stmt->bind_result( $id, $gal_id, $site_id, $item_type, $change_type, $item_id, 
												$processed, $added_on, $updated_on);
							if($stmt->fetch()) {
								$result = compact("id", "gal_id", "site_id", "item_type", "change_type", "item_id", 
												  "processed", "added_on", "updated_on");
								$all_query_ok = true;
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();

				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			}

			if($all_query_ok) {
				$db->commit();
				// echo "COMMITED";
			} else {
				$db->rollback();
			}

			$db->autocommit(true);	
		} else {
			$log = new Logger(__METHOD__.": NO DB CONNECTION", true);
		}

		

		return $result;
	}

	function setQueryError(int $query_id, $error_message) {
		$result = false;
		if($query_id > 0) {
			$db = DB::get();
			if($db) {
				$updated_on = time();
				$sql = "UPDATE galleries_changes_query
						SET error_msg = ?, updated_on = ?, error = 1
						WHERE id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
						$error_message = substr((string)$error_message, 0, 64);
						$stmt->bind_param("sii", $error_message, $updated_on, $query_id);
						if($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": NO DB CONNECTION", true);
			}
		}
		return $result;
	}

	function resetChangeQueryById($id) {
		$result = false;
		$id = (int)$id;
		if ($id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "UPDATE galleries_changes_query
						SET processed = 0, error = 0, error_msg = '', updated_on = ?
						WHERE id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					$updated_on = time();
					if($stmt->bind_param("ii", $updated_on, $id) && $stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": DB error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		}
		return $result;
	}

	function deleteProcessedChangeQuery() {
		$result = false;
		$db = DB::get();
		if($db) {
			$sql = "DELETE FROM galleries_changes_query
					WHERE processed = 1 AND error = 0";
			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->execute()) {
					$result = $stmt->affected_rows;
				} else {
					$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": DB error: '".$db->error."'", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
		}
		return $result;
	}

	public function getChangesQueryCount() {
		$result = false;
		$db = DB::get();

		if($db) {

			$sql = "SELECT count(id)
					FROM galleries_changes_query";
			$stmt = $db->prepare($sql);
			if($stmt) {
					if($stmt->execute()) {
						$counter = false;
						$stmt->bind_result($counter);
						if($stmt->fetch()) {
							$result = $counter;
						}
					} else {
						$this->error = __METHOD__.": STMT execute error '".$stmt->error."'";
						$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);
					}
			} else {
				$this->error = __METHOD__.": DB error '".$db->error."'";
				$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
			}
		} else {
			$this->error = __METHOD__.": DB connection error ";
			$log = new Logger(__METHOD__.": DB connection error ", true);
		}
		
		return $result;
	}

	public function getChangesQuery($limit = 50, $page = 0) {
		$result = false;
		$db = DB::get();

		if($db) {
			$limit = (int)$limit;
			$page = (int)$page;
			if($limit >= 0 && $page >=0 ) {
				$offset = $page * $limit;
			} else {
				$offset = 0;
				$limit = 50;
			}
			$sql = "SELECT id, gal_id, site_id, item_type, change_type, item_id, processed, added_on, updated_on, error  
					FROM galleries_changes_query
					LIMIT ".$offset.",".$limit.";";
			$stmt = $db->prepare($sql);
			if($stmt) {
					if($stmt->execute()) {

						$id = null;
						$gal_id = null;
						$site_id = null;
						$item_type = null;
						$change_type = null;
						$item_id = null;
						$processed = null;
						$added_on = null;
						$updated_on = null;
						$error = null;

						$stmt->bind_result($id, $gal_id, $site_id, $item_type, $change_type, $item_id, $processed, $added_on, $updated_on, $error);
						while($stmt->fetch()) {
							$result[] = compact("id", "gal_id", "site_id", "item_type", "change_type", "item_id", "processed", "added_on", "updated_on", "error");
						}
					} else {
						$this->error = __METHOD__.": STMT execute error '".$stmt->error."'";
						$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);
					}
			} else {
				$this->error = __METHOD__.": DB error '".$db->error."'";
				$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
			}
		} else {
			$this->error = __METHOD__.": DB connection error ";
			$log = new Logger(__METHOD__.": DB connection error ", true);
		}
		
		return $result;
		
	}

	// дубль в sites
	public function galleryPostedTo($gal_id) {
		$result = array();
		$db = DB::get();

		if($db) {
			$sql = "SELECT site_id FROM sites_galleries WHERE gal_id = ?";
			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("i", $gal_id)) {
					if($stmt->execute()) {
						$site_id = false;
						$stmt->bind_result($site_id);
						while($stmt->fetch()) {
							$result[] = $site_id;
						}
					} else {
						$this->error = __METHOD__.": STMT execute error '".$stmt->error."'";
						$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);
					}
				} else {
					$this->error = __METHOD__.": STMT bind error '".$stmt->error."'";
					$log = new Logger(__METHOD__.": STMT bind error '".$stmt->error."'", true);
				}
			} else {
				$this->error = __METHOD__.": DB error '".$db->error."'";
				$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
			}
		} else {
			$this->error = __METHOD__.": DB connection error ";
			$log = new Logger(__METHOD__.": DB connection error ", true);
		}
		
		return $result;
	}	

	private function addItemToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, $item_type, $change_type, &$db = NULL) {
		$result = false;
		if($db == NULL) {
			$db = DB::get();
		}

		if($db) {
			if(preg_match("#^(tag|model|source|gallery)$#", $item_type)
			&& preg_match("#^(added|removed|changed)$#", $change_type)) {

				$added_on = time();
				$updated_on = $added_on;
	
				$item_id = (int)$item_id;

				// echo "Insert into sites_cache: ".$item_type."".$change_type."<br>";

				$sql = "INSERT INTO `sites_cache_query` (site_id, cache_server_id, gal_id, gal_local_id, gal_type, 
														 item_type, change_type, item_id, added_on, updated_on, 
														 error_msg) 
							(
								SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ''
								FROM DUAL
								WHERE NOT EXISTS (
									SELECT id 
									FROM `sites_cache_query` 
					      			WHERE site_id = ? 
					      			AND cache_server_id = ?
					      			AND gal_id = ?
					      			AND item_type = ?
					      			AND change_type = ?
					      		) LIMIT 1
							)";
				if ($stmt = $db->prepare($sql)) {
					// echo "STMT OK";
					if ($stmt->bind_param("iiiisssiiiiiiss", $site_id, $cache_server_id, $gal_id, $gal_local_id, $gal_type,
														 $item_type, $change_type, $item_id, $added_on, $updated_on, 
													     $site_id, $cache_server_id, $gal_id, $item_type, $change_type)) {
						 // echo "STMT Params OK";
						if($stmt->execute()) {
							 // echo "STMT Execute OK";
							$result = true;
						} else {
							$log = new Logger (__METHOD__.": STMT bind params error '".$stmt->error."'", true);	
						}
					} else {
						$log = new Logger (__METHOD__.": STMT bind params error '".$stmt->error."'", true);				
					}
					$stmt->close();
				} else {
					// echo "Failed: Prepare STMT\n";
					$log = new Logger (__METHOD__.": STMT error MySQL '".$db->error."'", true);				
				}
			} else {
				$this->error = __METHOD__.": Входящие параметры item_type или change_type не верные";
				$log = new Logger(__METHOD__.": Входящие параметры item_type или change_type не верные", true);	
			}
		} else {
			$this->error = __METHOD__.": DB connection error ";
			$log = new Logger(__METHOD__.": DB connection error ", true);
		}
		
		return $result;
	}

	private function addTagToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, &$db = NULL) {
		$result = false;
		$item_type = 'tag';
		$change_type = 'added';

		$result = $this->addItemToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, $item_type, $change_type, $db);
		
		return $result;

	}

	private function addRemoveTagFromCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, &$db = NULL) {
		$result = false;
		$item_type = 'tag';
		$change_type = 'removed';

		$result = $this->addItemToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, $item_type, $change_type, $db);
		
		return $result;

	}

	private function addModelToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, &$db = NULL) {
		$result = false;
		$item_type = 'model';
		$change_type = 'added';

		$result = $this->addItemToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, $item_type, $change_type, $db);
		
		return $result;

	}

	private function addRemoveModelToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, &$db = NULL) {
		$result = false;
		$item_type = 'model';
		$change_type = 'removed';

		$result = $this->addItemToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, $item_type, $change_type, $db);
		
		return $result;
	}

	private function addChangedGalleryToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, &$db = NULL) {
		$result = false;
		$item_type = 'gallery';
		$change_type = 'changed';

		$result = $this->addItemToCacheQuery($site_id, $gal_id, $gal_local_id, $gal_type, $item_id, $cache_server_id, $item_type, $change_type, $db);
		
		return $result;
	}

	private function getSiteNiche($site_id) {
		$result = false;
		$db = DB::get();
		$site_id = (int)$site_id;
		if($db) {
				$sql = "SELECT site_niche FROM sites WHERE site_id = ".$site_id.";";
				$stmt = $db->prepare($sql);
				if($stmt) {
						if($stmt->execute()) {
							$stmt->bind_result($result);
							if(!$stmt->fetch()) {
								$log = new Logger(__METHOD__.": Ошибка, нет ниши сайта: '".$site_id."', '".$stmt->error."'", true);
							}
							
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
		} else {
			$log = new Logger(__METHOD__.": NO DB CONNECTION", true);
		}
		return $result;
	}

	public function addAllTagsToSite($site_id) { // дубль в sites
		$result = false;
		$site_id = (int)$site_id;

		if($site_id > 0) {

				$niche = $this->getSiteNiche($site_id);

				$description = '';
				$keywords = '';
				$gals_count = 0;
				$video_count = 0;
				$total_count = 0;
				$pageviews = 0;
				$likes = 0;
				$added_on = 0;
				$updated_on = 0;

					$sql = "INSERT INTO sites_tags 
						(tag_id, site_id, name, folder_name, description, keywords, 
						md5, gals_count, video_count, total_count, pageviews, 
						likes, added_on, updated_on)

						SELECT tags.tag_id, ? , tags.tag_name, replace(replace(tags.tag_name, \"'\", ''), ' ', '-'), ?, ?, 
							   MD5(replace(replace(tags.tag_name, \"'\", ''), ' ', '-')), ?, ?, ?, ?, ?, ?, ?
						FROM tags 
						WHERE tag_niche LIKE ?
						AND tags.tag_id NOT IN (
										SELECT sites_tags.tag_id FROM sites_tags
										WHERE sites_tags.site_id = ?
										)";

					$db = DB::get();
				
					if($db) {
						if ($stmt = $db->prepare($sql)) {
							if ($stmt->bind_param("issiiiiiiisi", $site_id, $description, $keywords, $gals_count, 
														  		$video_count, $total_count, $pageviews, $likes, 
														  		$added_on, $updated_on, $niche, 
														  		$site_id)) {
								$stmt->execute();
							
								$result = true;
							}
							$stmt->close();
						} else {
							$log = new Logger(__METHOD__.": STMT error:".$stmt->error, true);
						}		
					} else {
						$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
					}
					// var_dump($db->error, $stmt->error);
		}
		return $result;
	}


	public function addAllModelsToSite($site_id) { // дубль в sites
		$result = false;
		$site_id = (int)$site_id;

		if($site_id > 0) {

				$model_sex = 'female';

				$niche = $this->getSiteNiche($site_id);

				if($niche == 'Gay' || $niche == 'gay') $model_sex = 'male';
				elseif($niche == 'Straight' || $niche == 'straight') $model_sex = 'female';
				elseif($niche == 'Shemale' || $niche == 'shemale') $model_sex = 'shemale';

				$gals_count = 0;
				$video_count = 0;
				$total_count = 0;
				$pageviews = 0;
				$likes = 0;
				$added_on = 0;
				$updated_on = 0;

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
				
					if($db) {
						if ($stmt = $db->prepare($sql)) {
							$binded = $stmt->bind_param("iiiiiiiisi",   $site_id, $gals_count, 
																  		$video_count, $total_count, $pageviews, 
																  		$likes, $added_on, $updated_on, $model_sex,
																  		$site_id);
							if ($binded) {
								$stmt->execute();
								$result = true;
							} else {
								$log = new Logger(__METHOD__.": Проблема со STMT bind_param: '".$stmt->error."'", true);
							}
							$stmt->close();
						} else {
							$log = new Logger(__METHOD__.": Проблема со STMT: '".$db->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
					}
			
			
		}
		return $result;
	}


	// обработка очереди изменений галлерей
	// галлереи берутся из таблицы изменений galleries_changes_query
	function processChangesQuery() {
		$result = false;

		$start = time();
		$query_item = $this->getWorkFromQuery();
		$fin = time();
		$res = $fin-$start;
		// echo  "getWorkFromQuery:  {$res}\n";

		// var_dump($query_item );

		if($query_item) {

			$id = false;
			$gal_id = false;
			$item_type = false;
			$change_type = false;
			$item_id = false;
			$processed = false;
			$added_on = false;
			$updated_on = false;
			$site_id = false;

			extract($query_item);

			// var_dump($query_item);

			if($id) {

				if($item_type == 'image') {
					if($change_type == 'removed') {

						$db = new db_access();

						$galleries_worker = new Galleries($db->_db);
						$start = time();
						$result = $galleries_worker->removeImagesFinal($item_id);
						$fin = time();
						$res = $fin-$start;
						echo  "removeImagesFinal {$item_id}:  {$res}\n";
						
					} else {
						$log = new Logger(__METHOD__.": Image can not be added via gallery_changes_query!", true);
					}
				} else {
					$gallery_info = $this->getSiteGalleryShortInfo($site_id, $gal_id);
					$local_id = $gallery_info['id'];
					$gal_type = strtolower($gallery_info['gal_type']);

					if($gallery_info) {
						$caching_servers = $this->getSiteCachingServers($site_id);
						if($caching_servers && is_array($caching_servers)) {


							switch ($item_type) {

								case 'tag':
									if($change_type == 'added') {
										$this->addAllTagsToSite($site_id);
										// echo "Tag adding Query<br>";
										$result = $this->addTagToSiteGallery($site_id, $gal_id, $local_id, $gal_type, array($item_id), $added_on, $updated_on);
										foreach ($caching_servers as $cache_server_id) {
											$this->addTagToCacheQuery($site_id, $gal_id, $local_id, $gal_type, $item_id, $cache_server_id);
										}
										// echo "Tag adding Query processed. Result:<br>";
										// var_dump($result);
										// echo "Result Fin";
										
									} elseif($change_type == 'removed') {
										// echo "Removing tag<br>";
										$result = $this->deleteTagFromSiteGallery($site_id, $gal_id, $local_id, $gal_type, array($item_id), $added_on, $updated_on);
										// echo "Removing result:<br>";
										// var_dump($result);
										// echo "<br>Caching query:<br>";
										foreach ($caching_servers as $cache_server_id) {
											// echo "<br>Server ".$cache_server_id."<br>";
											$this->addRemoveTagFromCacheQuery($site_id, $gal_id, $local_id, $gal_type, $item_id, $cache_server_id);	
										}
										// удалить, и минус счетчик на модели
									} else {
										$result = $this->processQueriedTag($id, $gal_id, $site_id, $change_type, $item_id);	
									}
									
									break;
								case 'model':
									if($change_type == 'added') {
											$this->addAllModelsToSite($site_id);
											$result = $this->addModelToSiteGallery($site_id, $gal_id, $local_id, $gal_type, array($item_id), $added_on, $updated_on);
											// echo "addModelToSiteGallery<br>"; var_dump($result); echo "<br>";
											foreach ($caching_servers as $cache_server_id) {
												$this->addModelToCacheQuery($site_id, $gal_id, $local_id, $gal_type, $item_id, $cache_server_id);
											}									
										
									} elseif($change_type == 'removed') {
										// echo "delete model {";
										$result = $this->deleteModelFromSiteGallery($site_id, $gal_id, $local_id, $gal_type, array($item_id), $added_on, $updated_on);
										foreach ($caching_servers as $cache_server_id) {
											$this->addRemoveModelToCacheQuery($site_id, $gal_id, $local_id, $gal_type, $item_id, $cache_server_id);
										}
										// echo "}\ndelete model";
										// удалить, и минус счетчик на модели
									} else {
										
										$result = $this->processQueriedTag($id, $gal_id, $site_id, $change_type, $item_id);	
									}
									break;
								case 'gallery':
									if($change_type == 'changed') {
										foreach ($caching_servers as $cache_server_id) {
											$this->addChangedGalleryToCacheQuery($site_id, $gal_id, $local_id, $gal_type, $item_id, $cache_server_id);
										}
										$result = true;
									}
							}
						} else {
							// no caching servers
						}
					} else {
						// no site info
					}
				}

					


				
				// echo __METHOD__." RESULT :<br>";
				// var_dump($result);
				if($result) {
					// echo "Deleted<br>";
					$start = time();						
					$this->deleteFromChangeQueryById(array($id));
					$fin = time();
					$res = $fin-$start;
					// echo  "deleteFromChangeQueryById {$id}:  {$res}\n";
				}
			}


		}

		return $result;
	}

	private function processQueriedTag($id, $gal_id, $site_id, $change_type, $tag_id) {
		$result = false;

		return $result;
	}


	private function processQueriedGallery($id, $gal_id, $change_type, $item_id) {
		$result = false;
		$id = (int)$id;
		$gal_id = (int)$gal_id;
		$item_id = (int)$item_id;
		$change_type_ok = preg_match("^(added|removed|changed)$", $change_type);

		if($change_type_ok) {
			switch ($change_type) {
				case 'removed':
					// get more info on removal
					break;

				case 'changed':
					// update on all chaches. related only to chaching
					break;
			}
		}

		return $result;
	}

	public function addTagToSiteGallery($site_id, $gal_id, $local_id, $gal_type, $tags_list, $added_on, $updated_on, &$db = NULL) {
		// var_dump($site_id, $gal_id, $local_id, $gal_type, $tags_list, $added_on, $updated_on);
		$result = false;
		$tags_used = null;
		$sql_tags_array = false;
		$all_query_ok = true;

		if($db == NULL) {
			$db = DB::get();
		}

		$sql_tags_values = "INSERT INTO `site_".$site_id."_galleries_tags`
							(gal_id, local_id, tag_id, gal_type, added_on)
							VALUES ";

		$gal_type_lowercase = strtolower($gal_type);

		// echo date("Y-m-d",$added_on)."<br>";

		foreach($tags_list as $gallery_tag) {

			$gallery_tag = (int)$gallery_tag;

			if($gallery_tag > 0) {

				$sql_tags_array[] = "(".$gal_id.", ".$local_id.", ".$gallery_tag.", '".$gal_type_lowercase."', ".$added_on.")";
				$tags_used[] = $gallery_tag;

			}
										
		}


		if($sql_tags_array) {
			$sql_tags = implode(",", $sql_tags_array);
			if($sql_tags) {
				$sql = $sql_tags_values . $sql_tags;
				$db->query($sql) ? null : $all_query_ok = false;

				if(preg_match("#^(movies|video)$#im", $gal_type)) $sql_content_counter = "video_count";
				elseif(preg_match("#^(pics|gif)$#im", $gal_type)) $sql_content_counter = "gals_count";
				else {
					$log = new Logger(__METHOD__.": ошибка, gal_type неизвестен '".$gal_type."'", true);
					$all_query_ok = false;		
				}
				if($all_query_ok) {

					$sql_sites_tags_update =   "UPDATE sites_tags
												SET 
													".$sql_content_counter." = ".$sql_content_counter." + 1, 
													total_count = total_count + 1, 
													updated_on = '".$updated_on."',
													added_on = CASE WHEN added_on = '0' THEN '".$added_on."' ELSE added_on END
												WHERE 
													site_id = ".$site_id." AND tag_id IN (".implode(",", $tags_used).");";
					if(!$db->query($sql_sites_tags_update)) { 
						// var_dump($db->error);
						$all_query_ok = false; 
					} else {
						$result = true;
					}
					// var_dump($db->error);
				}
			}
		}
		if($all_query_ok) {
			// echo __METHOD__.":all query OK<br>";
			$result = $tags_used;
		} else {
			// echo __METHOD__.":all query Fail<br>";
		}
		return $result;
	}


	public function deleteTagFromSiteGallery($site_id, $gal_id, $local_id, $gal_type, $tags_list, $added_on, $updated_on, &$db = NULL) {
		// var_dump($site_id, $gal_id, $local_id, $gal_type, $tags_list, $added_on, $updated_on);
		$result = false;
		$tags_used = false;
		$all_query_ok = true;

		if($db == NULL) {
			$db = DB::get();
		}


		$gal_type_lowercase = strtolower($gal_type);

		foreach($tags_list as $gallery_tag) {

			$gallery_tag = (int)$gallery_tag;

			if($gallery_tag > 0) {
				$tags_used[] = $gallery_tag;
			}
										
		}
		// echo "Delete Tags:<br>";
		// var_dump($tags_used);
		// echo "Delete fin<br>";
		if($tags_used) {
			$sql_tags = implode(",", $tags_used);
			if($sql_tags) {
				$sql = "DELETE FROM `site_".$site_id."_galleries_tags`
						WHERE gal_id = ".$gal_id." AND tag_id IN (".$sql_tags.");";
				// var_dump($sql);
				$db->query($sql) ? null : $all_query_ok = false;
				 // var_dump($db->error);

				if(preg_match("#^(movies|video)$#im", $gal_type)) $sql_content_counter = "video_count";
				elseif(preg_match("#^(pics|gif)$#im", $gal_type)) $sql_content_counter = "gals_count";
				else {
					$all_query_ok = false;		
				}
				if($all_query_ok) {

					$sql_sites_tags_update =   "UPDATE sites_tags
												SET 
													".$sql_content_counter." = ".$sql_content_counter." - 1, 
													total_count = total_count - 1, 
													updated_on = '".$updated_on."'
												WHERE 
													site_id = ".$site_id." AND tag_id IN (".$sql_tags.");";
					// var_dump($sql_sites_tags_update);
					if(!$db->query($sql_sites_tags_update)) { 
						var_dump($db->error);
						$all_query_ok = false; 
					} else {
						$result = true;
					}
					// var_dump($db->error);
				}
			}
		}
		if($all_query_ok) {
			$result = $tags_used;
		}
		return $result;
	}


	public function addModelToSiteGallery($site_id, $gal_id, $local_id, $gal_type, $models_list, $added_on, $updated_on, &$db = NULL) {
		$result = false;
		$models_used = null;
		$sql_models_array = null;
		$all_query_ok = true;

		if($db == NULL) {
			$db = DB::get();
		}
		$sql_models_values = "INSERT INTO `site_".$site_id."_galleries_models`
							(gal_id, local_id, model_id, gal_type, added_on)
							VALUES ";

		$gal_type_lowercase = strtolower($gal_type);

		foreach($models_list as $gallery_model) {

			$gallery_model = (int)$gallery_model;

			if($gallery_model > 0) {
				$sql_models_array[] = "(".$gal_id.", ".$local_id.", ".$gallery_model.", '".$gal_type_lowercase."', ".$added_on.")";
				$models_used[] = $gallery_model;
			}
										
		}


		if($sql_models_array) {
			$sql_models = implode(",", $sql_models_array);
			if($sql_models) {
				$sql = $sql_models_values . $sql_models;
				$db->query($sql) ? null : $all_query_ok = false;
				// var_dump($db->error);
				if($all_query_ok) {

					if(preg_match("#^(movies|video)$#im", $gal_type)) $sql_content_counter = "video_count";
					elseif(preg_match("#^(pics|gif)$#im", $gal_type)) $sql_content_counter = "gals_count";

					$sql_sites_models_update =   "UPDATE sites_models
												SET 
													".$sql_content_counter." = ".$sql_content_counter." + 1, 
													total_count = total_count + 1, 
													updated_on = '".$updated_on."',
													added_on = CASE WHEN added_on = '0' THEN '".$added_on."' ELSE added_on END
												WHERE 
													site_id = ".$site_id." AND model_id IN (".implode(",", $models_used).");";
					if(!$db->query($sql_sites_models_update)) { 
						$all_query_ok = false;
						$log = new Logger(__METHOD__.": DB error '".$db->error."' ", true);
					}
					if($all_query_ok) {
						$result = $models_used;
					}
				}
			}
		}
		
		return $result;
	}


	public function deleteModelFromSiteGallery($site_id, $gal_id, $local_id, $gal_type, $models_list, $added_on, $updated_on, &$db = NULL) {
		$result = false;
		$models_used = false;
		$all_query_ok = true;

		if($db == NULL) {
			$db = DB::get();
		}		

		$gal_type_lowercase = strtolower($gal_type);

		foreach($models_list as $gallery_model) {

			$gallery_model = (int)$gallery_model;

			if($gallery_model > 0) {
				$models_used[] = $gallery_model;
			}
										
		}
		// echo "Models used:";
		// var_dump($models_used);
		if($models_used) {
			$sql_models = implode(",", $models_used);
			if($sql_models) {
				$sql = "DELETE FROM `site_".$site_id."_galleries_models`
			    		WHERE gal_id = ".$gal_id." AND model_id IN (".$sql_models.")";
				
				$db->query($sql) ? null : $all_query_ok = false;
				// var_dump($db->error);
				if($all_query_ok) {

					if(preg_match("#^(movies|video)$#im", $gal_type)) $sql_content_counter = "video_count";
					elseif(preg_match("#^(pics|gif)$#im", $gal_type)) $sql_content_counter = "gals_count";
					else {
						// ошибка типа!
						// echo "\ntype error";
					}
					$sql_sites_models_update =   "UPDATE sites_models
												  SET 
													".$sql_content_counter." = ".$sql_content_counter." - 1, 
													total_count = total_count - 1, 
													updated_on = '".$updated_on."'
												   WHERE 
													site_id = ".$site_id." AND model_id IN (".$sql_models.")
													AND total_count > 0;";
					if(!$db->query($sql_sites_models_update)) { 
						$all_query_ok = false;
						// var_dump($db->error);
					}
					if($all_query_ok) {
						$result = true;
					}
				}
			}
		}
		
		return $result;
	}


	public function addPaysiteToSiteGallery($site_id, $gal_type, $gal_source_id, $added_on, $updated_on, &$db = NULL) {
		$result = false;
		$all_query_ok = true;

		$gal_source_id = (int)$gal_source_id;

		if($db == NULL) {
			$db = DB::get();
		}

		if(preg_match("#^(movies|video)$#im", $gal_type)) $sql_content_counter = "video_count";
		elseif(preg_match("#^(pics|gif)$#im", $gal_type)) $sql_content_counter = "gals_count";

		if($db) {
			$sql_sites_sources_update = "UPDATE sites_sources
										  SET 
										  	".$sql_content_counter." = ".$sql_content_counter." + 1, 
										  	total_count = total_count + 1, 
										  	updated_on = '".$updated_on."',
					   					  	added_on = CASE WHEN added_on = '0' THEN '".$added_on."' ELSE added_on END
										  WHERE 
										  	source_id = ".$gal_source_id." AND site_id = ".$site_id.";";
			// echo "\n\n".$sql_sites_sources_update."\n\n";
			if($db->query($sql_sites_sources_update)) { 
				// echo "SQL OK\n";
				$result = true; 
			} else {
				// echo "SQL Failed\n";
				$log = new Logger(__METHOD__.": Gallery paysite counter update failed. '".$db->error."'", true);	
			}
			
		} else {
			$log = new Logger(__METHOD__.": NO DB connection", true);
		}

		// echo "source_id:".$gal_source_id.", addPaysiteToSiteGallery result:\n";
		// var_dump($result);
		return $result;
	}	

	private function checkIfGalleryAdded($gal_id) {
		$result = false;



		return $result;
	}

	private function getGalleryProcessingInfo($gal_id) {
		$result = false;

		if($gal_id > 0 && $this->site_id > 0) {
			$sql = "SELECT 	site_".$this->site_id.".id AS gal_local_id, 
							LOWER(galleries.gal_type) AS gal_type,
							galleries.gal_paysite AS gal_source_id,
							(
								SELECT group_concat(galleries_tags.gal_tags)
								FROM galleries_tags
								WHERE galleries_tags.gal_id = ?
							) AS gal_tags,
							(
								SELECT group_concat(galleries_models.model_id)
								FROM galleries_models
								WHERE galleries_models.gallery_id = ?
							) AS gal_models
					FROM site_".$this->site_id."
					LEFT JOIN galleries ON galleries.gal_id = ?
					WHERE site_".$this->site_id.".gal_id = ? ";

			$db = DB::get();

			if($db) {
				if($stmt = $db->prepare($sql)) {
					if($stmt->bind_param("iiii", $gal_id, $gal_id, $gal_id, $gal_id)) {
						if($stmt->execute()) {

							$gal_local_id = false;
							$gal_type = false;
							$gal_tags = false;
							$gal_models = false;
							
							$stmt->bind_result($gal_id, $gal_local_id, $gal_type, $gal_tags, $gal_models);
							if($stmt->fetch()) {
								$result = compact("gal_local_id", "gal_type", "gal_tags", "gal_models");
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind_param error: '".$stmt->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		} else {
			// incoming params error
		}

		return $result;
	}
	


	function getSiteCachingServers($site_id) {
		$result = false;

		$sites = new Sites();
		$result = $sites->getSiteCachingServers($site_id);

		return $result;
	}



	function deleteGallery($gal_id) {
		$result = false;
		if($this->site_id > 0) {
			$db = DB::get();
			if($db) {

				$gallery_info_updated = $this->getGalleryInfo($gal_id);
				$cache_servers = $this->getSiteCachingServers($this->site_id);
				$log_action_id = $this->logGalleryDeletion();
				

				if($gallery_info_updated) {
					$db->autocommit(false);

					$all_query_ok = true;
					$all_query_ok = $this->deleteGalleryFromTags($gal_id, $db);
					if($this->gal_source) {
						$all_query_ok ? $all_query_ok = $this->deleteGalleryFromSource($gal_id, $db) : null;
					}
					$all_query_ok ? $all_query_ok = $this->deleteGalleryFromModels($gal_id, $db) : null;
					$all_query_ok ? $all_query_ok = $this->deleteGalleryFromGalleriesSites($gal_id, $db) : null;
					$all_query_ok ? $all_query_ok = $this->deleteGalleryFromSite($gal_id, $db) : null;
					
					// создаем очередь в кэш
					if($cache_servers && is_array($cache_servers)) {
						foreach($cache_servers as $cache_server_id) {
							$all_query_ok ? $all_query_ok = $this->queryGalleryToCacheRemove($cache_server_id, $db) : null;
						}
					}
					

					// echo"\n\n\nResult:\n";
					// var_dump($all_query_ok);
					if($all_query_ok) {
						$db->commit();
						$db->autocommit(true);
						$this->removeLogById($log_action_id);
						$result = true;
					} else {
						// echo ":Rollbacked!";
						$db->rollback();
						$db->autocommit(true);
					}

					
				} else {
					$log = new Logger(__METHOD__.": Failed to get GID#".$gal_id." Gallery info for deleteion from SID#".$this->site_id, true);
				}

				
			} else {

			}
		} else {
			// no site_id
		}
		

		return $result;
	}

	function logGalleryDeletion() {
		$result = false;
		if($this->gal_id > 0 && $this->site_id > 0) {
			$gallery_worker = new Galleries();
			$error = 1;
			$listed_on_sites = array($this->site_id);
			$result = $gallery_worker->logGalleryChange($this->gal_id, 'gallery', 'removed', $this->site_id, $error, $listed_on_sites);
		}
		return $result;
	}

	function removeLogById($log_id) {
		$result = false;

		$gallery_worker = new Galleries();
		$result = $gallery_worker->deleteLogItem($log_id);

		return $result;
	}

	/*
		Очередб кеширования
	*/

	function getSitesCacheQuery() {
		$result = false;

		$db = DB::get();

		if($db) {
			$sql = "SELECT id, site_id, cache_server_id, gal_id, gal_type, item_type, change_type, item_id, added_on, updated_on, error, error_msg
					FROM sites_cache_query";
				
				$stmt = $db->prepare($sql);
				if($stmt) {
						if($stmt->execute()) {

							$id = null;
							$site_id = null;
							$cache_server_id = null;
							$gal_id = null;
							$gal_type = null;
							$item_type = null;
							$change_type = null;
							$item_id = null;
							$added_on = null;
							$updated_on = null;
							$error = null;
							$error_msg = null;

							$stmt->bind_result($id, $site_id, $cache_server_id, $gal_id, $gal_type, $item_type, $change_type, $item_id, $added_on, $updated_on, $error, $error_msg);
							while($stmt->fetch()) {
								$result[] = compact("id", "site_id", "cache_server_id", "gal_id", "gal_type", "item_type", "change_type", "item_id", "added_on", "updated_on", "error", "error_msg");
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();

				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
		}

		return $result;
	}		

	function clearQueryByGalId($site_id, $gal_id, $cache_server_id, &$db = NULL) {
		$result = false;

		if($db == NULL ) {
			$db = DB::get();
		}

		if($db) {
			$sql = "DELETE FROM sites_cache_query 
					WHERE site_id = ? AND gal_id = ? AND cache_server_id = ?";
				
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("iii", $site_id, $gal_id, $cache_server_id)) {
						if($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__.": STMT bind error: '".$stmt->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
		}

		return $result;
	}

	function queryGalleryToCacheRemove($cache_server_id, &$db) {
		$result = false;

		if($this->site_id > 0 && $this->gal_id > 0) {
			if($db === NULL) {
				$db = DB::get();
			}

			if($db) {

				$added_on = time();
				$updated_on = $added_on;
				$item_type = 'gallery';
				$change_type = 'removed';
				$item_id = 0;

				// чистит очередь на апдейт кеширования
				$this->clearQueryByGalId($this->site_id, $this->gal_id, $cache_server_id);

				if($cache_server_id >= 0) {
					$sql = "INSERT INTO `sites_cache_query`
						(site_id, cache_server_id, gal_id, gal_local_id, gal_type, item_type, 
						 change_type, item_id, added_on, updated_on) 
						VALUES ";

					$sql_insert_array = array();
					$first_value = true;

					$sql_insert_array[] = "(".$this->site_id.", ".$cache_server_id.", 
											".$this->gal_id.",".$this->gal_local_id.",'".$this->gal_type."',
											'source', 'removed',
											".$this->gal_source.",".$added_on.",".$updated_on.")";
					if($this->gal_tags) {
						foreach($this->gal_tags as $tag_id) {
							$sql_insert_array[] = "(".$this->site_id.", ".$cache_server_id.", 
											".$this->gal_id.",".$this->gal_local_id.",'".$this->gal_type."',
											'tag', 'removed',
											".$tag_id.",".$added_on.",".$updated_on.")";
						}
					}

						if($this->gal_models) {
							foreach($this->gal_models as $model_id) {
								$sql_insert_array[] = "(".$this->site_id.", ".$cache_server_id.", 
												".$this->gal_id.",".$this->gal_local_id.",'".$this->gal_type."',
												'model', 'removed',
												".$model_id.",".$added_on.",".$updated_on.")";
							}
						}


						$sql_insert_array[] = "(".$this->site_id.", ".$cache_server_id.", 
											".$this->gal_id.",".$this->gal_local_id.",'".$this->gal_type."',
											'gallery', 'removed',
											0,".$added_on.",".$updated_on.")";

					$sql .= implode(",", $sql_insert_array);

					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
					}

				}

			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		}

		return $result;
	}

	/* 

	Delete start

	 */

	//
	//
	//
	function deleteGalleryFromTags($gal_id, &$db = NULL) {
		$result = false;

		$gal_id = (int)$gal_id;

		if($this->site_id > 0 && $gal_id > 0) {
			if($db === NULL) {
				$db = DB::get();
			}

			if($db) {
				if(!$this->gal_id) {
					$this->getGalleryInfo($gal_id, $db);
				}
				$sql = "DELETE FROM site_".$this->site_id."_galleries_tags 
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("i", $gal_id)) {
						if($stmt->execute()) {
							$result = true;
							$stmt->close();
							if($this->gal_tags && is_array($this->gal_tags) && $this->gal_type) {
								$tags_list = implode(",", $this->gal_tags);
								if(preg_match("#^(movies|video)$#im", $this->gal_type)) $sql_content_counter = "video_count";
								elseif(preg_match("#^(pics|gif)$#im", $this->gal_type)) $sql_content_counter = "gals_count";
								else {
									// чота херня какая-то
									// если какой-то другой тип галеры, либо добавить новые, либо забить уже
								}
								$sql = "UPDATE sites_tags
										SET total_count = total_count - 1, ".$sql_content_counter." = ".$sql_content_counter." - 1
										WHERE site_id = ".$this->site_id." AND tag_id IN (".$tags_list.")";
								$db->query($sql);
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind_param error: '".$stmt->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Input error, site_id: '".$this->site_id."', gal_id: '".$gal_id."'", true);
		}

		return $result;
	}

	//
	//
	//
	function deleteGalleryFromSource($gal_id, &$db = NULL) {
		$result = false;

		$gal_id = (int)$gal_id;

		if($this->site_id > 0 && $gal_id > 0) {
			if($db === NULL) {
				$db = DB::get();
			}

			if($db) {
				if(!$this->gal_id) {
					//бля, ну вот нахуяяяя?!
					$this->getGalleryInfo($gal_id, $db);
				}

				if($this->gal_source && $this->gal_type) {
					if(preg_match("#^(movies|video)$#im", $this->gal_type)) $sql_content_counter = "video_count";
					elseif(preg_match("#^(pics|gif)$#im", $this->gal_type)) $sql_content_counter = "gals_count";
					else {
						return false;
					}
					$sql = "UPDATE sites_sources
							SET total_count = total_count - 1, ".$sql_content_counter." = ".$sql_content_counter." - 1
							WHERE site_id = ".$this->site_id." AND source_id = ".$this->gal_source.";";
					$result = $db->query($sql);
					var_dump($db->error);
				} else {
					echo "Source error";
				}



			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Input error, site_id: '".$this->site_id."', gal_id: '".$gal_id."'", true);
		}

		return $result;
	}


	//
	//
	//
	function deleteGalleryFromModels($gal_id, &$db = NULL) {
		$result = false;

		$gal_id = (int)$gal_id;

		if($this->site_id > 0 && $gal_id > 0) {
			if($db === NULL) {
				$db = DB::get();
			}

			if($db) {
				$sql = "DELETE FROM site_".$this->site_id."_galleries_models 
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("i", $gal_id)) {
						if($stmt->execute()) {
							$result = true;
							$stmt->close();
							if($this->gal_models && is_array($this->gal_models) && $this->gal_models) {
								$models_list = implode(",", $this->gal_models);
								if(preg_match("#^(movies|video)$#im", $this->gal_type)) $sql_content_counter = "video_count";
								elseif(preg_match("#^(pics|gif)$#im", $this->gal_type)) $sql_content_counter = "gals_count";
								else {
									// чота херня какая-то
									// если какой-то другой тип галеры, либо добавить новые, либо забить уже
								}
								$sql = "UPDATE sites_models
										SET total_count = total_count - 1, ".$sql_content_counter." = ".$sql_content_counter." - 1
										WHERE site_id = ".$this->site_id." AND model_id IN (".$models_list.")";
								$result = $db->query($sql);
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind_param error: '".$stmt->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Input error, site_id: '".$this->site_id."', gal_id: '".$gal_id."'", true);
		}

		return $result;
	}


	//
	//
	//
	function deleteGalleryFromGalleriesSites($gal_id, &$db = NULL) {
		$result = false;

		$gal_id = (int)$gal_id;

		if($this->site_id > 0 && $gal_id > 0) {
			if($db === NULL) {
				$db = DB::get();
			}

			if($db) {
				$sql = "DELETE FROM sites_galleries 
						WHERE site_id = ? AND gal_id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("ii", $this->site_id, $gal_id)) {
						if($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind_param error: '".$stmt->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Input error, site_id: '".$this->site_id."', gal_id: '".$gal_id."'", true);
		}

		return $result;
	}


	//
	//
	//
	function deleteGalleryFromSite($gal_id, &$db = NULL) {
		$result = false;

		$gal_id = (int)$gal_id;

		if($this->site_id > 0 && $gal_id > 0) {
			if($db === NULL) {
				$db = DB::get();
				// echo "DB NULL";
			}

			if($db) {
				$sql = "DELETE FROM site_".$this->site_id."
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("i", $gal_id)) {
						if($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": STMT bind_param error: '".$stmt->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Input error, site_id: '".$this->site_id."', gal_id: '".$gal_id."'", true);
		}

		return $result;
	}	

	/* 

	Delete end 

	*/

	function fixSiteGalleriesTable() {
		$result = false;
		$error_galleries = $this->getErrorSiteGalleries();
		if($error_galleries) {
			
		} else {
			// все ок
		}
		return $result;
	}

	function getErrorSiteGalleries() {
		$result = false;
		if ($this->site_id) {

			$db = DB::get();
			if($db) {
				$sql = "SELECT galleries.gal_id, site_".$this->site_id.".id, site_".$this->site_id.".gal_id AS internal_global_id
					FROM site_".$this->site_id."
					LEFT JOIN galleries ON galleries.gal_id = site_".$this->site_id.".gal_id
					WHERE galleries.gal_status != 'OK' OR galleries.gal_status = NULL";

				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->execute();

					$gal_id = false;
					$gal_local_id = false;
					$gal_local_id_internal = false;

					$stmt->bind_result($gal_id, $gal_local_id, $gal_local_id_internal);
					while($stmt->fetch()) {
						$result[$gal_local_id]['gal_id'] = $gal_id;
						$result[$gal_local_id]['gal_local_id'] = $gal_local_id;
						$result[$gal_local_id]['gal_local_id_internal'] = $gal_local_id_internal;
					}
				} else {
					// stmt error
				}
			} else {
				// db error
			}
		}
		return $result;
	}	


	/*

	выборки ошибочных сайтов, все варианты

	*/
	function getErrorGalleriesFromSite() {
		$result = false;
		if ($this->site_id >0) {

			$db = DB::get();	
			// var_dump($this->site_id);
			if($db) {
				$sql = "SELECT DISTINCT
								galleries.gal_id, 
								site_".$this->site_id.".id, 
								site_".$this->site_id.".gal_id AS internal_global_id
						FROM site_".$this->site_id."
						LEFT JOIN galleries ON galleries.gal_id = site_".$this->site_id.".gal_id
						WHERE galleries.gal_status != 'OK' OR galleries.gal_status IS NULL";

				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->execute();

					$gal_global_id = false;
					$gal_local_id = false;
					$gal_site_global_id = false;

					if($stmt->bind_result($gal_global_id, $gal_local_id, $gal_site_global_id)) {
						while($stmt->fetch()) {
							$result[$gal_site_global_id]['gal_global_id'] = $gal_global_id;
							$result[$gal_site_global_id]['gal_local_id'] = $gal_local_id;
							$result[$gal_site_global_id]['gal_site_global_id'] = $gal_site_global_id;
						}

					} else {
						$log = new Logger(__METHOD__.": STMT bind_result error: '".$stmt->error."'", true);
					}					
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		}
		return $result;
	}

	function getErrorGalleriesFromSupportTables() {
		$result = false;
		if ($this->site_id) {

			$db = DB::get();
			if($db) {
				$sql = "SELECT DISTINCT
								site_".$this->site_id.".gal_id,
								site_".$this->site_id."_galleries_tags.gal_id AS gal_tags_id,
						FROM site_".$this->site_id."
						LEFT JOIN site_".$this->site_id."_galleries_tags ON site_".$this->site_id."_galleries_tags.gal_id = site_".$this->site_id.".gal_id
						WHERE ite_".$this->site_id."_galleries_tags.gal_id IS NULL";

				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->execute();

					$gal_id = false;
					$gal_tags_id = false;

					if($stmt->bind_result($gal_id, $gal_tags_id)) {
						while($stmt->fetch()) {
							$result[$gal_id]['gal_id'] = $gal_id;
							$result[$gal_id]['gal_tags_id'] = $gal_tags_id;
						}	
					} else {
						$log = new Logger(__METHOD__.": STMT bind_result error: '".$stmt->error."'", true);
					}					
				} else {
					$log = new Logger(__METHOD__.": STMT prepare error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": DB connect error", true);
			}
		}
		return $result;
	}	

	function fixErrorGalleries() {
		$result = false;

		if($this->site_id) {
			$error_galleries = $this->getErrorGalleriesFromSite();
			if($error_galleries) {
				foreach ($error_galleries as $gallery) {
					$gal_to_delete = $gallery['gal_site_global_id'];
					if($gal_to_delete) {
						echo "Удаляю битую галеру: ".$gal_to_delete.": ";
						$delete_result = $this->deleteGallery($gal_to_delete);
						if ($delete_result) { echo "OK\n"; }
						else { echo "Failed\n"; }
					}
					
				}
			}
		}	

		return $result;
	}
	/*

	выборки ошибочных сайтов, все варианты

	*/


	function getGalleriesCountInSiteTable($gal_type = false) {
		$result = false;
		if ($this->site_id) {
			$db = DB::get();
			if($db) {

				$where_used = false;

				$sql = "SELECT count(id) 
						FROM site_".$this->site_id.";";
				// рыба под изменения парамтров
				if($gal_type) {
					if($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " AND ";
						$where_used = true;
					}
				}
						
				$gals_count = null;

				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($gals_count);
			    	if($stmt->fetch()) {
			    		$result = $gals_count;
			    	}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие параметры", true);
		}
		return $result;
	}

	public function getGalleriesCountInSitesGalleriesTable($gal_type = false) {
		$result = false;

		if ($this->site_id) {
			$db = DB::get();
			if($db) {

				$where_used = false;

				$sql = "SELECT count(gal_id) 
						FROM sites_galleries
						WHERE site_id = ".$this->site_id.";";
				// рыба под изменения парамтров
				if($gal_type) {
					if($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " AND ";
						$where_used = true;
					}
				}
				$gals_count = null;			
				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->bind_result($gals_count);
			    	if($stmt->fetch()) {
			    		$result = $gals_count;
			    	}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие параметры", true);
		}
		return $result;
	}


	function fixSiteGalleriesType($site_id) {
		$result = false;
		$site_id = (int)$site_id;
		if ($site_id > 0) {
			$db = DB::get();
			if($db) {

				$where_used = false;

				$sql = "UPDATE
						    site_".$site_id." ST 
						INNER JOIN galleries GT ON ST.gal_id = GT.gal_id
						SET
						    ST.gal_type = LOWER(GT.gal_type)
						WHERE ST.gal_type = 'none'";
				// рыба под изменения парамтров
						
				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->close();
				} else {
					var_dump($db->error);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие параметры", true);
		}

		return $result;	
	}

	function fixSiteGalleriesSourcesNull($site_id) { // исправляет, если gal_paysite = 0
		$result = false;
		$site_id = (int)$site_id;
		if ($site_id > 0) {
			$db = DB::get();
			if($db) {

				$where_used = false;

				$sql = "UPDATE
						    site_".$site_id." ST 
						INNER JOIN galleries GT ON ST.gal_id = GT.gal_id
						SET
						    ST.gal_paysite = GT.gal_paysite
						WHERE ST.gal_paysite = 0";
				// рыба под изменения парамтров
						
				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->close();
				} else {
					var_dump($db->error);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие параметры", true);
		}

		return $result;	
	}	


	function fixSitesCacheQueryGalleriesType() {
		$result = false;

			$db = DB::get();
			if($db) {

				$where_used = false;

				$sql = "UPDATE
						    sites_cache_query ST 
						INNER JOIN galleries GT ON ST.gal_id = GT.gal_id
						SET
						    ST.gal_type = LOWER(GT.gal_type)
						WHERE ST.gal_type = 'none'";
				// рыба под изменения парамтров
						
				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": db error".$db->error.";", true);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}


		return $result;	
	}

	function noGalTypeSiteGalleries($site_id) {
		$result = false;
		$site_id = (int)$site_id;
		if ($site_id > 0) {
			$db = DB::get();
			if($db) {

				$where_used = false;

				$sql = "SELECT count(id) FROM site_".$site_id." 
						WHERE gal_type = 'none'";
				// рыба под изменения парамтров
						
				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$count = false;
					$stmt->bind_result($count);
					if($stmt->fetch()) {
						$result = $count;
					}
					$stmt->close();
				} else {
					var_dump($db->error);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие параметры", true);
		}

		return $result;	
	}



	function deleteFromCacheQueryById($id) {
		$result = false;
		$id = (int)$id;
		if ($id > 0) {
			$db = DB::get();
			if($db) {

				$sql = "DELETE FROM sites_cache_query WHERE id = '".$id."'";
						
				if($stmt = $db->prepare($sql)) {
					$stmt->execute();
					$result = true;
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": DB error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		} else {
			$log = new Logger(__METHOD__.": Неверные входящие параметры", true);
		}

		return $result;	
	}

	function resetCacheQueryById($id) {
		$result = false;
		$id = (int)$id;
		if ($id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "UPDATE sites_cache_query
						SET error = 0, error_msg = '', updated_on = ?
						WHERE id = ?";
				if($stmt = $db->prepare($sql)) {
					$updated_on = time();
					if($stmt->bind_param("ii", $updated_on, $id) && $stmt->execute()) {
						$result = true;
					} else {
						$log = new Logger(__METHOD__.": STMT execute error: '".$stmt->error."'", true);
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": DB error: '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
			}
		}

		return $result;	
	}


	// листинги для внешних json запросов
	function getSiteTagsListings($site_name, $content_type = 'all', $sort_by = false) {
		$result = false;
		$sql = false; 

		if(!preg_match('/^([a-z0-9-]{4,16}\.[a-z]{1,10})$/', $site_name)) {
			$site_name = false;
		}
		// var_dump($site_name);
		if($site_name && $content_type && preg_match("#^(pics|movies|all)$#im", $content_type)) {
				$sql = "SELECT sites_tags.tag_id, sites_tags.name, sites_tags.folder_name, sites_tags.md5, 
							   sites_tags.gals_count, sites_tags.video_count, sites_tags.total_count, sites_tags.pageviews, 
							   sites_tags.likes, sites_tags.added_on, sites_tags.updated_on, '#' AS link
						FROM sites_tags
						LEFT JOIN sites ON sites_tags.site_id = sites.site_id
						WHERE sites.site_name = '".$site_name."'";
				if($content_type == 'movies') {
					$sql .= " AND sites_tags.video_count > 0 ";
					$sort_content_column = "video_count";
				} elseif($content_type == 'pics') {
					$sql .= " AND sites_tags.gals_count > 0 ";
					$sort_content_column = "gals_count";
				} else {
					$sql .= " AND sites_tags.total_count > 0 ";
					$sort_content_column = "total_count";
				}
				if($sort_by == 'date') {
					$sql .= " ORDER BY sites_tags.added_on DESC";
				} elseif($sort_by == 'count') {
					$sql .= " ORDER BY sites_tags.".$sort_content_column." DESC";
				} else {
					$sql .= " ORDER BY sites_tags.name ASC";
				}
				

				$db = DB::get();

				// var_dump($db);
				if($db) {
					$id = false;
					$name = false;
					$folder_name = false;
					$md5 = false;
					$gals_count = false;
					$video_count = false;
					$total_count = false;
					$pageviews = false;
					$likes = false;
					$added_on = false;
					$updated_on = false;
					$link = false;
					$stmt = $db->prepare($sql);
					// var_dump($sql, $stmt);
					if($stmt) {
						if($stmt->execute()){
							$stmt->bind_result($id, $name, $folder_name, $md5, $gals_count, $video_count, $total_count, $pageviews, $likes, $added_on, $updated_on, $link);
							while($stmt->fetch()) {
								$result[$id] = compact("id", "name", "folder_name", "md5", "gals_count", "video_count", "total_count", "pageviews", "likes", "added_on", "updated_on", "link");
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
				}
		} else {
			$log = new Logger(__METHOD__.": запрос списка тегов провален, неверные входящие данные - имя домена или тип контента", true);
		}

		return $result;
	}


	// листинги для внешних json запросов
	function getSiteSourcesListings($site_name, $content_type = 'all', $sort_by = false) {
		$result = false;
		$sql = false; 

		if(!preg_match('/^([a-z0-9-]{4,16}\.[a-z]{1,10})$/', $site_name)) {
			$site_name = false;
		}

		if($site_name && $content_type && preg_match("#^(pics|movies|all)$#im", $content_type)) {
				$sql = "SELECT sites_sources.source_id, sites_sources.name, sites_sources.folder_name, sites_sources.md5, 
							   sites_sources.gals_count, sites_sources.video_count, sites_sources.total_count, sites_sources.pageviews, 
							   sites_sources.likes, sites_sources.added_on, sites_sources.updated_on, paysites.paysite_link AS link
						FROM sites_sources
						LEFT JOIN sites ON sites_sources.site_id = sites.site_id
						LEFT JOIN paysites ON sites_sources.source_id = paysites.paysite_id
						WHERE sites.site_name = '".$site_name."'";
				if($content_type == 'movies') {
					$sql .= " AND sites_sources.video_count > 0 ";
					$sort_content_column = "video_count";
				} elseif($content_type == 'pics') {
					$sql .= " AND sites_sources.gals_count > 0 ";
					$sort_content_column = "gals_count";
				} else {
					$sql .= " AND sites_sources.total_count > 0 ";
					$sort_content_column = "total_count";
				}
				if($sort_by == 'date') {
					$sql .= " ORDER BY sites_sources.added_on DESC";
				} elseif($sort_by == 'count') {
					$sql .= " ORDER BY sites_sources.".$sort_content_column." DESC";
				} else {
					$sql .= " ORDER BY sites_sources.name ASC";
				}
				

				$db = DB::get();
				if($db) {
					$id = false;
					$name = false;
					$folder_name = false;
					$md5 = false;
					$gals_count = false;
					$video_count = false;
					$total_count = false;
					$pageviews = false;
					$likes = false;
					$added_on = false;
					$updated_on = false;
					$link = false;
					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->execute()){
							$stmt->bind_result($id, $name, $folder_name, $md5, $gals_count, $video_count, $total_count, $pageviews, $likes, $added_on, $updated_on, $link);
							while($stmt->fetch()) {
								$result[$id] = compact("id", "name", "folder_name", "md5", "gals_count", "video_count", "total_count", "pageviews", "likes", "added_on", "updated_on", "link");
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
				}
		} else {
			$log = new Logger(__METHOD__.": запрос списка тегов провален, неверные входящие данные - имя домена или тип контента", true);
		}

		return $result;
	}


	// листинги для внешних json запросов
	function getSiteModelsListings($site_name, $content_type='all', $sort_by = false, $letter = false) {
		$result = [];
		$sql = false; 
		$use_letter = false;

		if(!preg_match('/^([a-z0-9-]{4,16}\.[a-z]{1,10})$/', $site_name)) {
			$site_name = false;
		}

		if($letter) {
			if(strlen($letter) == 1) {
				if(preg_match("#([a-z])#im", $letter)) {
					$use_letter = strtolower($letter);
				}
			}
		}

		if($site_name && $content_type && preg_match("#^(pics|movies|all)$#im", $content_type)) {
				$sql = "SELECT sites_models.model_id, sites_models.name, '#' AS folder_name, sites_models.md5, 
							   sites_models.gals_count, sites_models.video_count, sites_models.total_count, sites_models.pageviews, 
							   sites_models.likes, sites_models.added_on, sites_models.updated_on, '#' AS link
						FROM sites_models
						LEFT JOIN sites ON sites_models.site_id = sites.site_id
						WHERE sites.site_name = '".$site_name."'";
				if($use_letter) {
					$sql .= " AND LOWER(name) LIKE '".$use_letter."%'";
				}
				if($content_type == 'movies') {
					$sql .= " AND sites_models.video_count > 0 ";
					$sort_content_column = "video_count";
				} elseif($content_type == 'pics') {
					$sql .= " AND sites_models.gals_count > 0 ";
					$sort_content_column = "gals_count";
				} else {
					$sql .= " AND sites_models.total_count > 0 ";
					$sort_content_column = "total_count";
				}
				if($sort_by == 'date') {
					$sql .= " ORDER BY sites_models.added_on DESC";
				} elseif($sort_by == 'count') {
					$sql .= " ORDER BY sites_models.".$sort_content_column." DESC";
				} else {
					$sql .= " ORDER BY sites_models.name ASC";
				}
				
				// echo $sql;

				$db = DB::get();
				if($db) {
					$id = false;
					$name = false;
					$folder_name = false;
					$md5 = false;
					$gals_count = false;
					$video_count = false;
					$total_count = false;
					$pageviews = false;
					$likes = false;
					$added_on = false;
					$updated_on = false;
					$link = false;
					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->execute()){
							$stmt->bind_result($id, $name, $folder_name, $md5, $gals_count, $video_count, $total_count, $pageviews, $likes, $added_on, $updated_on, $link);
							while($stmt->fetch()) {
								$result[$id] = compact("id", "name", "folder_name", "md5", "gals_count", "video_count", "total_count", "pageviews", "likes", "added_on", "updated_on", "link");
							}
						} else {
							$log = new Logger(__METHOD__.": STMT execute error '".$stmt->error."'", true);
						}
					} else {
						$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
					}
				} else {
					$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
				}
		} else {
			$log = new Logger(__METHOD__.": запрос списка тегов провален, неверные входящие данные - имя домена или тип контента", true);
		}

		return $result;
	}
	
}
?>
