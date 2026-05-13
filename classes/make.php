<?php
class Maker
{
	private $niche;
	private $thumbSize;
	private $make;
	private $use_embed = 0;
	private $thumb_by_horiz_width = false;
	private $max_times_used_gals = false;
	private $exclude_tags = false;
	private $with_tag = 0;

	public function GetPaysiteGalleries($paysiteId, $siteId, $type)
	{
		$siteId = (int)$siteId;
		$paysiteId = (int)$paysiteId;
		$sql = "SELECT DISTINCT site_" . $siteId . ".gal_id, gal_source, gal_md5, gal_paysite, gal_title, gal_description, gal_niche, gal_added, gal_content_count, gal_type
				FROM site_" . $siteId . " 
				LEFT JOIN galleries ON site_" . $siteId . ".gal_id = galleries.gal_id
				WHERE gal_paysite = '" . $paysiteId . "'
				AND gal_type = '" . $type . "'
				LIMIT 0, 10000";

		$gallery = [];
		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$id = $row['gal_id'];
			$gallery[$id]['source'] = $row['gal_source'];
			$gallery[$id]['md5'] = $row['gal_md5'];
			$gallery[$id]['title'] = $row['gal_title'];
			$gallery[$id]['description'] = $row['gal_description'];
			$gallery[$id]['niche'] = $row['gal_niche'];
			$gallery[$id]['contentCount'] = $row['gal_content_count'];
			$gallery[$id]['type'] = $row['gal_type'];
			$gallery[$id]['date'] = $row['gal_added'];
			$gallery[$id]['paysite']['id'] = $row['gal_paysite'];
		}

		return $gallery;
	}

	private function addExcludeTag($tag)
	{
		// var_dump($tag);
		$tag = (int)$tag;
		if ($tag) {
			$this->exclude_tags[$tag] = $tag;
		}
	}



	private function removeExcludeTag($tag)
	{
		$tag = (int)$tag;
		if ($tag && $this->exclude_tags && array_key_exists($tag, $this->exclude_tags)) {
			unset($this->exclude_tags[$tag]);
			if (!count($this->exclude_tags)) {
				$this->exclude_tags = false;
			}
		}
	}

	private function SelectTags($tags, $type, $sort = 'asc', $accept_gifs = false, $language = false, $gals_count = 50)
	{

		$crop_flag = (defined("NO_CROP_FLAG") && NO_CROP_FLAG) ? " " : " AND crop_flag = '1' ";

		$gals_count = (int)$gals_count;

		if (!preg_match("#^(Pics|Movies|gif|mix)$#", $type)) {
			echo "Неправильно задан тип контента для сборки";
			exit;
		} else {
			if (preg_match("#^(Pics|Movies|gif)$#", $type))	$type_sql = " AND gal_type = '" . $type . "' ";
			else {
				if (!$accept_gifs) $type_sql = " AND (gal_type = 'Pics' OR gal_type = 'Movies')";
				else $type_sql = "";
			}
		}

		$use_embed 			= ($this->use_embed) ? "" : " AND galleries.embed_flag = 0 ";
		$only_horiz_sql		= ($this->thumb_by_horiz_width) ? " INNER JOIN galleries_resized_to ON galleries.gal_id = galleries_resized_to.gal_id " : "";
		$language_addition 	= ($language && $language != 'en') ? ", ( SELECT title FROM additional_titles WHERE additional_titles.gal_id = galleries.gal_id AND language = '" . $language . "' LIMIT 1) AS gal_title, " : ", gal_title, ";

		if ($sort == 'desc') $sort_by = " ORDER BY galleries.gal_id DESC";
		elseif ($sort == 'mix') $sort_by = " ORDER BY RAND()";
		else  $sort_by = " ORDER BY galleries.gal_id ASC";

		$exclude_gals 		= ($this->hand_flag == 1) ? " AND galleries.gal_id NOT IN (SELECT gal_id FROM site_" . $this->site . "_exclude_gals) " : " AND galleries.gal_id NOT IN (SELECT gal_id FROM site_" . $this->site . "_exclude_gals) ";
		$include_gals 		= ($this->with_tag) ? " AND galleries_tags.gal_id IN (SELECT A.gal_id FROM galleries_tags AS A WHERE A.gal_tags = " . $this->with_tag . " ) " : "";


		$max_times_used_gals = "";

		if ($this->max_times_used_gals !== false) {
			if ($this->max_times_used_gals == 0) {
				$max_times_used_gals = " AND times_used_on_sites = 0 ";
			} elseif ($this->max_times_used_gals) {
				$max_times_used_gals = " AND times_used_on_sites < " . $this->max_times_used_gals . " ";
			}
		}

		$nicheInsertion =  ($this->niche !== 'All') ? " AND galleries.gal_niche = '" . $this->niche . "' " : "";

		$limit = " LIMIT " . $gals_count;

		if ($tags['category'] == 0 && (!isset($tags['exclude_tag']) || !$tags['exclude_tag'])) {
			if ($this->dont_show_used) {
				$dont_show_used = " AND galleries.gal_id NOT IN ( SELECT gal_id FROM sites_galleries ) ";
			} else {
				$dont_show_used = "";
			}


			$sql = "SELECT DISTINCT galleries.gal_id, galleries.gal_source, galleries.gal_md5, 
									galleries.gal_paysite " . $language_addition . " galleries.gal_description, 
									galleries.gal_niche, galleries.gal_added, galleries.gal_content_count, 
									galleries.gal_type, gal_thumb
					FROM galleries " .
				$only_horiz_sql .
				" WHERE gal_status = 'OK' AND unique_for_export_site = 0 " . $nicheInsertion . "
					AND unique_gal = '0' " .
				$crop_flag .
				$type_sql .
				$dont_show_used .
				$use_embed .
				$max_times_used_gals .
				" AND galleries.gal_id NOT IN (SELECT gal_id FROM site_{$this->site}) 
					AND galleries.gal_id NOT IN (SELECT gal_id FROM sites_galleries_make_query WHERE site_id = '" . $this->site . "') 
					AND galleries.gal_id NOT IN (SELECT gal_id FROM writers_titles WHERE site_id = '" . $this->site . "') " .
				$exclude_gals . $include_gals . $sort_by . $limit;
			// echo $sql;
			$q_result = $this->db->query($sql);

			$gallery = [];

			while ($row = $q_result->fetch_assoc()) {
				$id = $row['gal_id'];
				$gallery[$id]['source'] = $row['gal_source'];
				$gallery[$id]['md5'] = $row['gal_md5'];
				$gallery[$id]['title'] = $row['gal_title'];
				$gallery[$id]['description'] = $row['gal_description'];
				$gallery[$id]['niche'] = $row['gal_niche'];
				$gallery[$id]['contentCount'] = $row['gal_content_count'];
				$gallery[$id]['type'] = $row['gal_type'];
				$gallery[$id]['date'] = $row['gal_added'];
				$gallery[$id]['paysite']['id'] = $row['gal_paysite'];
				$gallery[$id]['gal_thumb'] = $row['gal_thumb'];
			}

			return $gallery;
		} else {
			$tagAddition = " AND (gal_tags = '" . $tags['category'] . "'";
			if ($tags['or_tag']) {
				$tagAddition .= " OR gal_tags = '" . (int)$tags['or_tag'] . "'";
			}

			$tagAddition .= ") ";
			$tag_1 = (int)$tags['tag_1'];
			$tag_2 = (int)$tags['tag_2'];

			if ($tag_1) {
				$tagAddition .= " AND galleries_tags.gal_id IN (";
				if ($tag_2) {
					$tagAddition .= "SELECT A.gal_id FROM galleries_tags AS A
					INNER JOIN galleries_tags AS B ON A.gal_id = B.gal_id
					WHERE A.gal_tags = " . $tag_1 . " AND B.gal_tags = " . $tag_2;
				} else {
					$tagAddition .= "SELECT A.gal_id FROM galleries_tags AS A
					WHERE A.gal_tags = " . $tag_1;
				}
				$tagAddition .= ")";
			}
			// var_dump($this->exclude_tags);
			if ($this->exclude_tags) {
				$exclude_tag_set = false;
				foreach ($this->exclude_tags as $exclude_tag_id) {
					if ($exclude_tag_set) {
						$tagAddition .= " OR ";
					} else {
						$tagAddition .= " AND galleries_tags.gal_id NOT IN (SELECT A.gal_id FROM galleries_tags AS A WHERE ";
						$exclude_tag_set = true;
					}
					$tagAddition .= "A.gal_tags = " . $exclude_tag_id;
				}
				if ($exclude_tag_set) {
					$tagAddition .= ")";
				}
			}
		}

		$dont_show_used = ($this->dont_show_used) ? " AND galleries_tags.gal_id NOT IN ( SELECT gal_id FROM sites_galleries ) " : "";

		if ($sort == 'desc') {
			$sort_by = " ORDER BY galleries_tags.gal_id DESC";
		} elseif ($sort == 'mix') {
			$sort_by = " ORDER BY RAND()";
		} else {
			$sort_by = " ORDER BY galleries_tags.gal_id ASC";
		}

		$sql = "SELECT DISTINCT galleries_tags.gal_id, gal_source, gal_md5, gal_paysite " . $language_addition . " gal_description, galleries.gal_niche, gal_added, gal_content_count, gal_type, gal_thumb
			FROM galleries_tags
			LEFT JOIN galleries ON galleries_tags.gal_id = galleries.gal_id " .
			$only_horiz_sql .
			" WHERE galleries.gal_status = 'OK'
			AND unique_for_export_site = 0
			AND unique_gal = '0' " .
			$crop_flag .
			$use_embed .
			$dont_show_used .
			$type_sql .
			$tagAddition .
			$nicheInsertion .
			$max_times_used_gals .
			"AND galleries_tags.gal_id NOT IN (SELECT gal_id FROM site_" . $this->site . ")
			AND galleries_tags.gal_id NOT IN (SELECT gal_id FROM sites_galleries_make_query WHERE site_id = '" . $this->site . "')  
			AND galleries_tags.gal_id NOT IN (SELECT gal_id FROM writers_titles WHERE site_id = '" . $this->site . "') " .
			$exclude_gals . $include_gals . $sort_by . $limit;
		// echo $sql;

		$gallery = [];

		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$id = $row['gal_id'];
			$gallery[$id]['source'] = $row['gal_source'];
			$gallery[$id]['md5'] = $row['gal_md5'];
			$gallery[$id]['title'] = $row['gal_title'];
			$gallery[$id]['description'] = $row['gal_description'];
			$gallery[$id]['niche'] = $row['gal_niche'];
			$gallery[$id]['contentCount'] = $row['gal_content_count'];
			$gallery[$id]['type'] = $row['gal_type'];
			$gallery[$id]['date'] = $row['gal_added'];
			$gallery[$id]['paysite']['id'] = $row['gal_paysite'];
			$gallery[$id]['gal_thumb'] = $row['gal_thumb'];
		}

		return $gallery;
	}

	public function __construct($site, $type, $sort = 'desc', $dont_show_used = false, $only_no_empty_titles = false, $gals_count = 50, $exclude_tag_from_make = 0, $with_tag = 0)
	{

		$this->db = DB::get();
		if (!$this->db) {
			die('Прблема с DB, нет коннекта');
		}

		$this->make 			= false;
		$accept_gifs 			= false;
		$this->dont_show_used 	= $dont_show_used;
		$this->niche 			= $site['niche'];
		$this->thumbSize 		= $site['thumbSize'];
		$tags['category'] 		= $site['category'];
		$tags['or_tag'] 		= $site['or_tag'];
		$tags['tag_1'] 			= $site['tag_1'];
		$tags['tag_2'] 			= $site['tag_2'];
		$this->hand_flag 		= $site['hand_flag'];
		$this->site 			= $site['id'];
		$this->use_embed 		= ($site['use_embed']) ? 1 : 0;

		$this->with_tag 		= ((int)$with_tag  > 0) ? (int)$with_tag : 0;

		$this->addExcludeTag($site['excludeTag']);
		$this->addExcludeTag($exclude_tag_from_make);


		if (isset($site['thumb_by_horiz_width']) && $site['thumb_by_horiz_width']) {
			$this->thumb_by_horiz_width = true;
		}


		if (isset($site['max_times_used_gals'])) {
			switch ($site['max_times_used_gals']) {
				case -1:
					$this->max_times_used_gals = false;
					break;
				case 0:
					$this->max_times_used_gals = 0;
					break;
				case (int)$site['max_times_used_gals']:
					$this->max_times_used_gals = (int)$site['max_times_used_gals'];
					break;
			}
		}

		// var_dump($this->max_times_used_gals);
		if ($type == 'mix') {
			if ($site['site_type'] == 'video') $type = 'Movies';
			elseif ($site['site_type'] == 'pics') $type = 'Pics';
			elseif ($site['site_type'] == 'gif') $type = 'gif';

			$accept_gifs = (!$site['accept_gifs']) ? false : true;
		} else {
			if (($site['site_type'] == 'video' && $type != 'Movies') || ($site['site_type'] == 'pics' && $type != 'Pics')
				|| ($site['site_type'] == 'gif' && $type != 'gif')
			) {
				echo "Выбраный тип " . $type . " не совпадает с типом сайта " . $site['site_type'], "<br>";
				return false;
			}
		}

		$this->make = $this->SelectTags($tags, $type, $sort, $accept_gifs, $site['language'], $gals_count);
	}

	public function Ret()
	{
		return $this->make;
	}
}
