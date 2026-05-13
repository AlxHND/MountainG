<?php

class CacheQuery
{
	private function executeQuerySql($sql)
	{
		if (!isset($this->_db) || !$this->_db instanceof PDO) {
			return false;
		}

		return $this->_db->exec($sql) !== false;
	}

	private function galleryCacheUpdate($query_type, $item_id = false, $start_value = false, $item_id_2 = false)
	{
		$result = false;
		if (preg_match("#^(ok_galleries|site_galleries|gallery|site_gallery|gallery_tags|gallery_models|gallery_images|gallery_source)$#", $query_type)) {
			$added_on = time();
			switch ($query_type) {
				case 'ok_galleries':
					$start_value = intval($start_value);
					$item_id = 0;
					$item_id_2 = 0;
					break;
				case 'gallery':
				case 'gallery_tags':
				case 'gallery_models':
				case 'gallery_images':
				case 'gallery_source':
				case 'site_galleries':
					$item_id = intval($item_id);
					$start_value = 0;
					$item_id_2 = 0;
					break;
				case 'site_gallery':
					$item_id = intval($item_id);
					$start_value = 0;
					$item_id_2 = intval($item_id);
					break;
				default:
					$query_type = false;
					break;
			}

			if ($query_type) {
				$sql = "insert into cache_rebuild_query
						(query_type, item_id, start_value, item_id_2, added_on)
						values ('" . $query_type . "','" . $item_id . "','" . $start_value . "','" . $item_id_2 . "','" . $added_on . "')";
				if ($this->executeQuerySql($sql)) {
					$result = true;
				}
			}
		}

		return $result;
	}

	public function cacheGallery($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = false;
		if ($this->getStatus($gal_id) == 'OK') {
			if ($this->galleryCacheUpdate('gallery', $gal_id)) $result = true;
			else $log = new Logger("Ошибка добавления галеры на кеширование", true);
		}
		return $result;
	}

	public function cacheGalleryTags($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = false;
		if ($this->getStatus($gal_id) == 'OK') {
			if ($this->galleryCacheUpdate('gallery_tags', $gal_id)) $result = true;
			else $log = new Logger("Ошибка добавления галеры на кеширование", true);
		}
		return $result;
	}

	public function cacheGalleryPaysite($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = false;
		if ($this->getStatus($gal_id) == 'OK') {
			if ($this->galleryCacheUpdate('gallery_source', $gal_id)) $result = true;
			else $log = new Logger("Ошибка добавления галеры на кеширование", true);
		}
		return $result;
	}

	public function cacheGalleryModels($gal_id)
	{
		$gal_id = intval($gal_id);
		$result = false;
		if ($this->getStatus($gal_id) == 'OK') {
			if ($this->galleryCacheUpdate('gallery_models', $gal_id)) $result = true;
			else $log = new Logger("Ошибка добавления галеры на кеширование", true);
		}
		return $result;
	}
}
