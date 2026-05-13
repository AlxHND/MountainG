<?php
class SitesGalleries
{

	private $site_id = false;
	public $error = false;
	private $db = NULL;

	private $gal_id = false;
	private $gal_local_id = false;
	private $gal_type = false;
	private $gal_source = false;
	private $gal_tags = array();
	private $gal_models = array();

	function setSiteId($site_id)
	{
		$result = false;

		$site_id = (int)$site_id;

		if ($site_id > 0) {
			$this->site_id = $site_id;
			$result = true;
		} else {
			$this->error = __METHOD__ . ": Неверный параметр site_id, сайт не переключен";
		}

		return $result;
	}

	function checkGalleriesTables()
	{
		$result = false;
		if ($this->site_id) {
			$gals_in_site_table = $this->getGalleriesCountInSiteTable();
			$gals_in_sites_galleries_table = $this->getGalleriesCountInSitesGalleriesTable();

			if ($gals_in_site_table && $gals_in_sites_galleries_table) {

				if ($gals_in_site_table != $gals_in_sites_galleries_table) {
					$this->fixSiteGalleriesTable();
					// кол-во галлерей в таблицах site_# и sites_galleries не совпадают
					// необходимо исправить ситуацию
					// 1. Проверяем обе таблицы на ошибки, т.е. сравниваем с таблицей galleries в
					// 	  в поисках NULL (удаленных) или галлерей вне статуса ОК
					// 2. Если найдены битые галлереии, просходит следующее:
					// 	  - собираем список галлерей
					//	  - удаляем их из site_#_galleries_tags
					//	  - удаляем их из site_#_galleries_sources
					//	  - удаляем их из site_#_galleries_models
					//	  - удаляем из таблицы где они были найдены
					//	  - пересобираем счетчики sites_sources, sites_tags, sites_models
					// 3. Если не найдены, сравниваем, в какой таблице большее кол-во галлерей
					//	  если галер больше в site_#, происходит следующее:
					// 	  - собираем список галлерей
					//	  - удаляем их из site_#_galleries_tags
					//	  - удаляем их из site_#_galleries_sources
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

	function getGalleryInfo($gal_id)
	{
		$result = false;

		$this->gal_id = false;
		$this->gal_local_id = false;
		$this->gal_type = false;
		$this->gal_source = false;
		$this->gal_tags = array();
		$this->gal_models = array();

		$gal_id = (int)$gal_id;
		if ($this->site_id && $gal_id > 0) {

			$sql = "SELECT 	site_" . $this->site_id . ".id, site_" . $this->site_id . ".gal_id AS global_id, 
						   	site_" . $this->site_id . "_galleries_sources.gal_type, 
						   	site_" . $this->site_id . "_galleries_sources.source_id,
							(
								SELECT group_concat(site_" . $this->site_id . "_galleries_tags.tag_id)
								FROM site_" . $this->site_id . "_galleries_tags
								WHERE site_" . $this->site_id . "_galleries_tags.gal_id = global_id 
							) AS gal_tags,
							(
								SELECT group_concat(site_" . $this->site_id . "_galleries_models.model_id)
								FROM site_" . $this->site_id . "_galleries_models
								WHERE site_" . $this->site_id . "_galleries_models.gal_id = global_id 
							) AS gal_models
					FROM site_" . $this->site_id . "
					LEFT JOIN site_" . $this->site_id . "_galleries_sources ON site_" . $this->site_id . ".gal_id = site_" . $this->site_id . "_galleries_sources.gal_id
					WHERE site_" . $this->site_id . ".gal_id = '" . $gal_id . "'";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->execute()) {
					$gal_tags = array();
					$gal_models = array();
					$stmt->bind_result(
						$this->gal_id,
						$this->gal_local_id,
						$this->gal_type,
						$this->gal_source,
						$gal_tags,
						$gal_models
					);
					if ($stmt->fetch()) {
						if ($this->gal_id) {
							if ($gal_tags != NULL) {
								$a_gal_tags = explode(",", $gal_tags);
								if ($a_gal_tags) {
									$this->gal_tags = $a_gal_tags;
								}
							}
							if ($gal_models != NULL) {
								$a_gal_models = explode(",", $gal_models);
								if ($a_gal_models) {
									$this->gal_models = $a_gal_models;
								}
							}
						} else {
							//
						}
					} else {
						//
					}
				} else {
					//
				}
			} else {
				//
			}
		} else {
			// неверный параметр $gal_id
		}


		return $result;
	}

	function deleteGallery($gal_id)
	{
		$result = false;
		if ($this->site_id > 0) {
			$db = DB::get();
			if ($db) {
				$db->autocommit(false);

				$all_query_ok = true;

				$all_query_ok = $this->deleteGalleryTags($gal_id, $db);
				$all_query_ok ? $all_query_ok = $this->deleteGallerySource($gal_id, $db) : null;
				$all_query_ok ? $all_query_ok = $this->deleteGalleryModels($gal_id, $db) : null;
				$all_query_ok ? $all_query_ok = $this->deleteGalleryGalleriesSites($gal_id, $db) : null;
				$all_query_ok ? $all_query_ok = $this->deleteGallerySite($gal_id, $db) : null;


				if ($all_query_ok) {
					$db->commit();
					$result = true;
				} else {
					$db->rollback();
				}

				$db->autocommit(true);
			} else {
			}
		} else {
			// no site_id
		}


		return $result;
	}

	/* 

	Delete start

	 */

	//
	//
	//
	function deleteGalleryTags($gal_id, &$db = NULL)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($this->site_id > 0 && $gal_id > 0) {
			if ($db === NULL) {
				$db = DB::get();
			}

			if ($db) {
				$sql = "DELETE FROM site_" . $this->site_id . "_galleries_tags 
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute error: '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT bind_param error: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Input error, site_id: '" . $this->site_id . "', gal_id: '" . $gal_id . "'", true);
		}

		return $result;
	}

	//
	//
	//
	function deleteGallerySource($gal_id, &$db = NULL)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($this->site_id > 0 && $gal_id > 0) {
			if ($db === NULL) {
				$db = DB::get();
			}

			if ($db) {
				$sql = "DELETE FROM site_" . $this->site_id . "_galleries_sources 
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute error: '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT bind_param error: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Input error, site_id: '" . $this->site_id . "', gal_id: '" . $gal_id . "'", true);
		}

		return $result;
	}


	//
	//
	//
	function deleteGalleryModels($gal_id, &$db = NULL)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($this->site_id > 0 && $gal_id > 0) {
			if ($db === NULL) {
				$db = DB::get();
			}

			if ($db) {
				$sql = "DELETE FROM site_" . $this->site_id . "_galleries_models 
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute error: '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT bind_param error: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Input error, site_id: '" . $this->site_id . "', gal_id: '" . $gal_id . "'", true);
		}

		return $result;
	}


	//
	//
	//
	function deleteGalleryGalleriesSites($gal_id, &$db = NULL)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($this->site_id > 0 && $gal_id > 0) {
			if ($db === NULL) {
				$db = DB::get();
			}

			if ($db) {
				$sql = "DELETE FROM sites_galleries 
						WHERE site_id = ? AND gal_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("ii", $this->site_id, $gal_id)) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute error: '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT bind_param error: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Input error, site_id: '" . $this->site_id . "', gal_id: '" . $gal_id . "'", true);
		}

		return $result;
	}


	//
	//
	//
	function deleteGallerySite($gal_id, &$db = NULL)
	{
		$result = false;

		$gal_id = (int)$gal_id;

		if ($this->site_id > 0 && $gal_id > 0) {
			if ($db === NULL) {
				$db = DB::get();
			}

			if ($db) {
				$sql = "DELETE FROM site_" . $this->site_id . "
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $gal_id)) {
						if ($stmt->execute()) {
							$result = true;
						} else {
							$log = new Logger(__METHOD__ . ": STMT execute error: '" . $stmt->error . "'", true);
						}
					} else {
						$log = new Logger(__METHOD__ . ": STMT bind_param error: '" . $stmt->error . "'", true);
					}
				} else {
					$log = new Logger(__METHOD__ . ": STMT prepare error: '" . $db->error . "'", true);
				}
			} else {
				$log = new Logger(__METHOD__ . ": DB connect error", true);
			}
		} else {
			$log = new Logger(__METHOD__ . ": Input error, site_id: '" . $this->site_id . "', gal_id: '" . $gal_id . "'", true);
		}

		return $result;
	}

	/* 

	Delete end 

	*/

	function fixSiteGalleriesTable()
	{
		$result = false;
		$error_galleries = $this->getErrorSiteGalleries();
		if ($error_galleries) {
		} else {
			// все ок
		}
		return $result;
	}

	function getErrorSiteGalleries()
	{
		$result = false;
		if ($this->site_id) {

			$db = DB::get();
			if ($db) {
				$sql = "SELECT galleries.gal_id, site_" . $this->site_id . ".id, site_" . $this->site_id . ".gal_id AS internal_global_id
					FROM site_" . $site_id . "
					LEFT JOIN galleries ON galleries.gal_id = site_" . $site_id . ".gal_id
					WHERE galleries.gal_status != 'OK' OR galleries.gal_status = NULL";

				$stmt = $db->prepare($sql);
				if ($stmt) {
					$stmt->execute();

					$gal_id = false;
					$gal_local_id = false;
					$gal_local_id_internal = false;

					$stmt->bind_result($gal_id, $gal_local_id, $gal_local_id_internal);
					while ($stmt->fetch()) {
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

	function getGalleriesCountInSiteTable($gal_type = false)
	{
		$result = false;
		if ($this->site_id) {
			$db = DB::get();
			if ($db) {

				$where_used = false;

				$sql = "SELECT count(id) 
						FROM site_" . $this->site_id . ";";
				// рыба под изменения парамтров
				if ($gal_type) {
					if ($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " AND ";
						$where_used = true;
					}
				}

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

	public function getGalleriesCountInSitesGalleriesTable($gal_type = false)
	{
		$result = false;

		if ($this->site_id) {
			$db = DB::get();
			if ($db) {

				$where_used = false;

				$sql = "SELECT count(gal_id) 
						FROM sites_galleries
						WHERE site_id = " . $this->site_id . ";";
				// рыба под изменения парамтров
				if ($gal_type) {
					if ($where_used) {
						$sql .= " AND ";
					} else {
						$sql .= " AND ";
						$where_used = true;
					}
				}

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
}
