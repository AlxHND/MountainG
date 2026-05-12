<?php

class DBTools {
	public $galleryCounter;

	function __construct($host,$rssThumbSizes) {
		$this->host = $host;
		$this->rssThumbSizes = $rssThumbSizes;
		$this->db = DB::get();
		if(!$this->db) {
			die('Прблема с DB, нет коннекта');
		}
	}

//
// Methods for RSS
//

	private $host;




	public function RssGetThumbs($galleryId) { // завязан на NewGetGalleryInfo

		$rss  = false;
		$galleryId = intval($galleryId);

		$sql = "SELECT image_id
				FROM galleries_pix
				WHERE gal_id =  '".$galleryId."';";

		$q_result = $this->db->query($sql);
		
		while ($row = $q_result->fetch_assoc()) {
			$rss[] = $row['image_id'];
		}

		return $rss;
	}

//
//
//
//
//
//
//
//


	public function GetPaysiteInfo($id) { // завязан на NewGetGalleryInfo
		if (is_string($id)) {
			$id = $this->db->real_escape_string($id);
			$sql = "SELECT DISTINCT * FROM paysites 
					LEFT JOIN crop_profiles
					ON crop_profiles.profile_id = paysites.crop_profile_id
					WHERE paysite_folder = '".$id."'";
			
		} else {
			$id = intval($id);
			$sql = "SELECT DISTINCT * FROM paysites 
					LEFT JOIN crop_profiles
					ON crop_profiles.profile_id = paysites.crop_profile_id
					WHERE paysite_id = ".$id.";";
		}

		$q_result = $this->db->query($sql);	

		if ($row = $q_result->fetch_assoc()) {

			$paysite ['id'] = $row['paysite_id'];
			$paysite ['name'] = $row['paysite_name'];
			$paysite ['affiliateProgram'] = $row['paysite_affiliate'];
			$paysite ['link'] = $row['paysite_link'];
			$paysite ['folder'] = $row['paysite_folder'];
			$paysite ['info'] = $row['paysite_info'];
			$paysite ['niche'] = $row['paysite_niche'];
			$paysite ['category'] = $row['paysite_category'];
			$paysite ['cropProfile'] = $row['crop_profile_id'];

			$paysite ['paysiteReview'] = $row['paysite_review'];
			$paysite ['trialLength'] = $row['paysite_trial_length'];
			$paysite ['trialPrice'] = $row['paysite_trial_price'];
			$paysite ['fullPrice'] = $row['paysite_month_price'];
			$paysite ['clickHereText'] = $row['paysite_clickhere_text'];
			$paysite ['paysiteRating'] = $row['paysite_rating'];

			$paysite ['crop']['IM'] = $row['IM_string'];
			$paysite ['crop']['quality'] = $row['crop_quality'];
			$paysite ['crop']['name'] = $row['crop_profile_name'];
			$paysite ['crop']['top'] = $row['cut_top'];
			$paysite ['crop']['bottom'] = $row['cut_bottom'];
			$paysite ['crop']['left'] = $row['cut_left'];
			$paysite ['crop']['right'] = $row['cut_right'];
			$paysite ['hosted'] = $row['hosted_flag'];
			$paysite ['bitrate'] = $row['max_bitrate'];
			$paysite['lastUpdate'] = $row['last_update'];

			$paysite['update_type'] = $row['update_type'];
			$paysite['paysite_update_page'] = $row['paysite_update_page'];

			$paysite['video_update_page'] = $row['paysite_update_page_video'];
			$paysite['update_type_video'] = $row['update_type_video'];
			
			$paysite['single_update_page'] = $row['single_update_page'];
			$paysite['update_page_md5'] = $row['update_page_md5'];
			$paysite['set_cropped'] = $row['set_cropped'];
			$paysite['use_original_ids'] = $row['use_original_ids'];
			
			
			if ($paysite['lastUpdate'] == '0000-00-00') $paysite['lastUpdate'] = "Никогда";
			return $paysite;
		} else return FALSE;		
	}



	public function TagId ($tag) {
		$sql = "SELECT tag_id FROM tags WHERE tag_name = '".$tag."'";
		$q_result = $this->db->query($sql);
		if ($row = $q_result->fetch_assoc()) return $row['tag_id'];
		else return FALSE;
	}

	public function TagName ($id) {
		$id = intval($id);
		$sql = "SELECT tag_name FROM tags WHERE tag_id = ".$id.";";
		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row ? $row['tag_name'] : 0;
	}

	public function TagNiche ($id) {
		$id = intval($id);
		$sql = "SELECT tag_niche FROM tags WHERE tag_id = ".$id.";";
		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		
		return $row ? $row['tag_niche'] : false;
	}

	public function GalleryTags($id) {

		$id = (int)$id;
		$tag = false;

		$sql = "SELECT gal_tags FROM galleries_tags WHERE gal_id = '".$id."';";
		$q_result = $this->db->query($sql);

		$i= 0;

		while ($row = $q_result->fetch_assoc()) {
			$tag['id'][$i] = $row['gal_tags'];
			$tag['name'][$i] = $this->TagName($row['gal_tags']);
			$i++;
		}
		
		return $tag;
	}

	public function selectTaggedThumbs($id) {
		$id = intval($id);
		$tag = false;
		$sql = "SELECT thumb_id, tag_id FROM thumbs_tags
				WHERE gal_id = '".$id."';";
		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$tag[$row['tag_id']] = $row['thumb_id'];
		}
		return $tag;
	}

	public function GalleryNiche ($id) {
		$id = intval($id);
		$sql = "SELECT gal_niche FROM galleries WHERE gal_id = ".$id.";";
		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row['gal_niche'];
	}

	public function CropProfile ($id) {
		$id = intval($id);
		$sql = "SELECT * FROM crop_profiles WHERE profile_id = ".$id.";";
		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		
		$crop ['id'] = $id;
		$crop ['name'] = $row['crop_profile_name'];
		$crop ['IM'] = $row['IM_string'];
		$crop ['quality'] = $row['crop_quality'];
		$crop ['top'] = $row['cut_top'];
		$crop ['bottom'] = $row['cut_bottom'];
		$crop ['left'] = $row['cut_left'];
		$crop ['right'] = $row['cut_right'];
	
		return $crop;

	}


	

	private function NewGalleryImages($id) {
		$id = (int)$id;
		$output = false;

		$sql = "SELECT image_id,image FROM galleries_pix
				WHERE gal_id = ".$id." 
				ORDER BY image_id ASC;";
		
		$q_result = $this->db->query($sql);
		
		while ($row = $q_result->fetch_assoc()) {
			$imageId = $row['image_id'];
			$output['url'][$imageId] = $row['image'];
			$output['thumbs'][$imageId] = dirname($row['image']) ."/thumbs/tn-150x200-". basename($row['image']);
		} 

		return $output;
	}


	public function galleryPostedTo($id) {
		$id = intval($id);
		$result = array();
		if ($id !== 0) {
			$sql = "SELECT site_id FROM 
					sites_galleries 
					WHERE gal_id = ".$id."
					UNION ALL
					SELECT site_id FROM 
					sites_galleries_make_query 
					WHERE gal_id = ".$id.";";

			$q_result = $this->db->query($sql);
			while ($row = $q_result->fetch_assoc()) {
				$result[] = $row['site_id'];
			}
		}
		return $result;
	}

	public function getAllAdditionTitles($id, $with_used = false) {
		//	тоже самое есть в class.gallleries.php
		$id = intval($id);
		$result = false;
		if ($id !== 0) {
			$sql = "SELECT id, title, language, used_on FROM 
				additional_titles
				WHERE gal_id = ".$id;
			if ($with_used) $sql .= " AND used_on <> 0;";
			else $sql .= ";";

			$q_result = $this->db->query($sql);
			while ($row = $q_result->fetch_assoc()) {
				$result[$row['id']]['id'] = $row['id'];
				$result[$row['id']]['title'] = $row['title'];
				$result[$row['id']]['language'] = $row['language'];
				$result[$row['id']]['used_on'] = $row['used_on'];
			}
		}
		return $result;
	}

	public function NewGetGalleryInfo($id) {
		$id = intval($id);
		if ($id !== 0) {
			$sql = "SELECT galleries.gal_id, galleries.gal_source, galleries.gal_md5, galleries.gal_title,
						   galleries.gal_description, galleries.gal_niche, galleries.gal_status, galleries.gal_type,
						   galleries.gal_type, galleries.gal_added, galleries.gal_paysite, galleries.gal_content_count,
						   galleries.hosted_flag, galleries.crop_flag, galleries.main_gal, galleries.gal_thumb,
						   galleries.embed_flag, galleries.embed, galleries.is_long_url, 
						   galleries_resized_to.horiz_size, galleries.unique_for_export_site
					FROM galleries 
					LEFT JOIN galleries_resized_to ON galleries.gal_id = galleries_resized_to.gal_id
					WHERE galleries.gal_id = ".$id.";";

			$q_result = $this->db->query($sql);
			if ($row = $q_result->fetch_assoc()) {

				$gallery['id'] = $id;
				$gallery['source'] = $row['gal_source'];
				$gallery['md5'] = $row['gal_md5'];
				$gallery['title'] = $row['gal_title'];
				$gallery['description'] = $row['gal_description'];
				$gallery['niche'] = $row['gal_niche'];
				$gallery['status'] = $row['gal_status'];
				$gallery['type'] = $row['gal_type'];
				$gallery['date'] = $row['gal_added'];
				$gallery['paysite'] = $this->GetPaysiteInfo((int)$row['gal_paysite']);
				$gallery['tags'] = $this->GalleryTags($id);
				$gallery['cropProfile'] = $this->CropProfile($gallery['paysite']['cropProfile']);

				if ($gallery['status'] == "new") {
					return $gallery;
				}

				$gallery['contentCount'] = $row['gal_content_count'];
				$gallery['images'] = $this->NewGalleryImages($id);
				$gallery['uploadedRssThumbs'] = $this->RssGetThumbs($id);
				$gallery['posted_to'] = $this->galleryPostedTo($id);
				$gallery['additional_titles'] = $this->getAllAdditionTitles($id);

				if($gallery['type'] == 'gif') {
					foreach ($gallery['images']['url'] as $id => $image) {
						$gallery['images']['thumbs'][$id] = $image;
					}
				}
				$gallery['host'] = $this->host;
				$gallery['hosted'] = $row['hosted_flag'];
				$gallery['cropped'] = $row['crop_flag'];
				$gallery['main_gal'] = $row['main_gal'];
				
				$gallery['gal_thumb'] = $row['gal_thumb'];
				
				$gallery['horiz_size'] = $row['horiz_size'];
				$gallery['embed_flag'] = $row['embed_flag'];
				$gallery['embed'] = $row['embed'];
				$gallery['is_long_url'] = $row['is_long_url'];
				$gallery['unique_for_export_site'] = $row['unique_for_export_site'];
				
				return $gallery;	
			} else return FALSE;
		} else return FALSE;
	}


	public function GetMovieScreenshot ($id) {
		$id = intval($id);
		$sql = "SELECT DISTINCT image_id FROM 
			galleries_pix
			WHERE gal_id = ".$id.";";

		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row['image_id'];
	}

	public function GetGifFrame ($id) {
		$id = intval($id);
		$sql = "SELECT DISTINCT image FROM 
			galleries_pix
			WHERE gal_id = ".$id.";";

		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row['image'];
	}	

	public function GetGalleryType ($id) {
		$id = intval($id);
		$sql = "SELECT gal_type FROM 
			galleries
			WHERE gal_id = ".$id." LIMIT 1";

		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row['gal_type'];
	}

	public function GetGalleryImage ($id) {
		$id = intval($id);
		$sql = "SELECT image FROM 
			galleries_pix
			WHERE gal_id  = ".$id." LIMIT 1";

		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row['image'];
	}

	public function GetGalleryImageId ($id) {
		$id = intval($id);
		$sql = "SELECT image_id FROM 
			galleries_pix
			WHERE gal_id  = ".$id." LIMIT 1";

		$q_result = $this->db->query($sql);
		$row = $q_result->fetch_assoc();
		return $row['image_id'];
	}


	private function setGalleryCounterSQL($sql = false) {
		if ($sql) {
//			echo $sql;
			$q_result = $this->db->query($sql);
			$row = $q_result->fetch_assoc();
			$this->galleryCounter = $row['COUNT(gal_id)'];
//			var_dump($row);
		}
	}


	public function GetGalleryToTag($galleryId = FALSE, 
									$galleryNiche=FALSE,
									$galleryPaysite=FALSE,
									$galleryCategory=FALSE,
									$croppedTagFlag=FALSE, 
									$galleryType = false, 
									$skeep = false, 
									$sort = false, 
									$set_main_thumbs = false, 
									$no_merge_gals = false, 
									$is_worker = true,
									$exclude_paysites = false) {

		
		if($sort == "asc") $sql_sort = " ORDER BY gal_added ASC";
		elseif ($sort == "desc") $sql_sort = " ORDER BY gal_added DESC";
		else $sql_sort = " ORDER BY RAND()";

		if ($skeep) {

				$sql = "SELECT * FROM scr_user_skeep_gallery";
				$sql .= $sql_sort;

				$q_result = $this->db->query($sql);
				$res['count'] = $q_result->num_rows;
				if ($row = $q_result->fetch_assoc()) {
					$res['id'] = $row['gal_id'];
					$res['user_id'] = $row['user_id'];
					$res['skeep_reason'] = $row['skeep_reason'];
					$res['skeep_type'] = $row['skeep_type'];
					$res['added'] = $row['added'];
					return $res;
				} else return FALSE;
		}

		if (is_array($exclude_paysites) && $galleryPaysite && in_array($galleryPaysite, $exclude_paysites)) {
			echo "<h3>Номер выбранного платника, и платник в списке исключенных совпадает</h3>";
			return false;
		}

		if ($galleryId === FALSE) {

			$addition  = ($galleryNiche) ? " AND gal_niche = '" .$galleryNiche ."'" : "";
			$addition .= ($galleryCategory) ? " AND (gal_paysite IN (SELECT paysite_id FROM paysites WHERE paysite_category = '" .$galleryCategory ."') OR galleries.gal_id IN (SELECT gal_id FROM galleries_tags WHERE gal_tags = '".$galleryCategory."') )" : "";
			$addition .= ($galleryPaysite) ? " AND gal_paysite = '" .$galleryPaysite ."'" : "";
			$addition .= ($croppedTagFlag) ? " AND crop_flag = '1'" : "";
			$addition .= ($no_merge_gals) ? " AND main_gal = '0'" : "";
			$addition .= ($galleryType && preg_match('/^(pics|movies)$/im', $galleryType)) ? " AND gal_type = '".ucfirst($galleryType)."'" : "";
			

			if(is_array($exclude_paysites) && $exclude_paysites) {
				foreach($exclude_paysites as $e_p) {
					if(!((int)$e_p > 0 )) {
						echo "<h3>Ошибка входящих данных - список исключенных платников битый</h3>";
						return false;
					}
				}
				$addition .= " AND gal_paysite NOT IN (".implode(",", $exclude_paysites).")";
			}

			$sql  = ($set_main_thumbs) ? "SELECT * FROM galleries WHERE ((gal_status = 'uploaded' OR gal_status = 'OK') AND gal_thumb = '0')" : "SELECT * FROM galleries WHERE gal_status = 'uploaded'";
			$sql .= $addition . $sql_sort;

			$q_result = $this->db->query($sql);

			$res['count'] = $q_result->num_rows;
			
			if ($res['count'] > 0 && $row = $q_result->fetch_assoc()) {
				$res['id'] = $row['gal_id'];
				return $res;
			} else {
				return FALSE;
			}

		} elseif ((int)$galleryId > 0) {

			$galleryId = (int)$galleryId;
			$sql = "SELECT * FROM galleries WHERE gal_id = '".$galleryId."'";

			$q_result = $this->db->query($sql);			
			$res['count'] = $q_result->num_rows;
			if ($res['count'] > 0) {
				if ($row = $q_result->fetch_assoc()) {
					$res['id'] = $row['gal_id'];
					return $res;
				} else return FALSE;
			} else return FALSE;
		} else return FALSE;
	}



	public function SitesGetAll() {
		$sql = "SELECT sites.site_id, sites.site_name, sites.site_niche, 
					   sites.site_main_category, sites.site_categories,
					   sites.sites_gallery_url, sites.site_categories_exclude, 
					   sites.site_ftp, sites.site_login, sites.site_pass, 
					   sites.site_ftp_folder, sites.site_thumb_size,
					   sites.redis_server, sites.additional_redis_server,
					   COUNT(sites_galleries.gal_id) AS gals_count,
					   sites.only_export_site
				FROM sites
				LEFT JOIN sites_galleries ON sites.site_id = sites_galleries.site_id
				GROUP BY sites.site_id";
		$q_result = $this->db->query($sql);
		$i = 0;
		$start = get_time();
		while ($row = $q_result->fetch_assoc()) {
			$site[$i]['id'] = $row['site_id'];
			$site[$i]['name'] = $row['site_name'];
			$site[$i]['niche'] = $row['site_niche'];
			$site[$i]['category'] = $row['site_main_category'];
			$site[$i]['tag'] = $row['site_categories'];
			$site[$i]['galleryUrl'] = $row['sites_gallery_url'];
			$site[$i]['excludeTag'] = $row['site_categories_exclude'];
			$site[$i]['ftp'] = $row['site_ftp'];
			$site[$i]['login'] = $row['site_login'];
			$site[$i]['pass'] = $row['site_pass'];
			$site[$i]['uploadFolder'] = $row['site_ftp_folder'];
			$site[$i]['thumbSize'] = $row['site_thumb_size'];
			$site[$i]['galNumber'] = $row['gals_count'];
			$site[$i]['redis_server'] = $row['redis_server'];
			$site[$i]['additional_redis_server'] = $row['additional_redis_server'];
			$site[$i]['only_export_site'] = $row['only_export_site'];
			
			
			$i++;
			$flag = TRUE;
		}
		$finish = get_time();
		$exectime = $finish - $start;
		// echo "Exec: '".$exectime."'";
		if ($flag === TRUE) return $site;
		else return FALSE;
	}



	function sites_updateToRatingSystem($id) {
		$id = (int)$id;
		if($id) {
			$sql = "ALTER TABLE `site_".$id."` ADD `rating` FLOAT UNSIGNED NOT NULL DEFAULT '0' AFTER `likes`,
					ADD INDEX ( `rating` ) ;";
			$q_result = $this->db->query($sql);

			$sql = "UPDATE site_".$id."
					SET rating = likes/pageviews
					WHERE pageviews > 0";
			$q_result = $this->db->query($sql);
			return true;
		}
		return false;

	}

	public function SiteInformation ($siteId) {
		$sql = "SELECT * FROM sites WHERE site_id = '{$siteId}'";
		$q_result = $this->db->query($sql);
		if ($row = $q_result->fetch_assoc()) {

			$site['id'] = $row['site_id'];
			$site['name'] = $row['site_name'];
			$site['niche'] = $row['site_niche'];
			$site['category'] = $row['site_main_category'];
			$site['or_tag'] = $row['or_tag']; // ИЛИ тег
			$site['tag_1'] = 0;
			$site['tag_2'] = 0;
			$additional_tags = $row['site_categories'];
			if ($additional_tags && $unserialized_tags = @unserialize($additional_tags)) {
				if (is_array($unserialized_tags)) {
					foreach ($unserialized_tags as $key => $value) {
						$key_x = $key+1;
						$site['tag_'.$key_x] = $value;
					}
				}

			}
			$site['galleryUrl'] = $row['sites_gallery_url'];
			$site['excludeTag'] = $row['site_categories_exclude'];
			$site['exclude_category'] = $row['site_categories_exclude'];
			$site['ftp'] = $row['site_ftp'];
			$site['login'] = $row['site_login'];
			$site['pass'] = $row['site_pass'];
			$site['uploadFolder'] = $row['site_ftp_folder'];
			$site['thumbSize'] = $row['site_thumb_size'];
			$site['urlLength'] = $row['sites_url_length'];
			$site['upload_flag'] = $row['upload_flag'];
			$site['localIdFlag'] = $row['local_id_flag'];
			$site['local_id_flag'] = $row['local_id_flag'];
			$site['hand_flag'] = $row['hand_flag'];
			$site['redis_server'] = $row['redis_server'];
			$site['accept_gifs'] = $row['accept_gifs'];
			$site['site_type'] = $row['site_type'];
			$site['site_own_titles'] = $row['site_own_titles'];
			$site['site_own_main_thumbs'] = $row['site_own_main_thumbs'];
			$site['language'] = $row['language'];
			$site['use_galleries_from'] = $row['use_galleries_from'];
			$site['digit_base_for_id'] = $row['digit_base_for_id'];
			$site['use_embed'] = $row['use_embed'];
			$site['thumb_by_horiz_width'] = $row['thumb_by_horiz_width'];
			$site['max_times_used_gals'] = $row['max_times_used_gals'];
			$site['additional_redis_server'] = $row['additional_redis_server'];
			$site['vcdn_type'] = $row['vcdn_type'];
			$site['use_unique_tags'] = $row['use_unique_tags'];
			$site['default_title_for_tag'] = $row['default_title_for_tag'];
			$site['only_export_site'] = $row['only_export_site'];
			
		
			return $site;
		} else return FALSE;
			
	}

	public function GetAllTags($niche = FALSE) {
		if (!$niche) $niche= " WHERE tag_niche %LIKE% '{$niche}'";
		else $niche = "";
		$sql = "SELECT * FROM tags " . $niche . " ORDER BY tag_name ASC LIMIT 0,3000";

		$i = 0;
		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$tags[$i]['id'] = $row['tag_id'];
			$tags[$i]['name'] = $row['tag_name'];
			$i++;
		}
		return $tags;
	}

	public function GetAllActionTags($niche = FALSE) {
		$tags = array();
		if ($niche !== FALSE) $nicheInsertion = " AND tag_niche LIKE '%{$niche}%'";
		else $nicheInsertion = "";
		$sql = "SELECT * FROM tags WHERE tag_category = 'Action'" . $nicheInsertion . " ORDER BY tag_name ASC LIMIT 0,3000";

		$i = 0;
		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$tags[$i]['id'] = $row['tag_id'];
			$tags[$i]['name'] = $row['tag_name'];
			$i++;
		}
		return $tags;
	}

	public function GetAllCategoryTags($niche = FALSE) {

		$tags = array();

		if ($niche !== FALSE) $nicheInsertion = " AND tag_niche LIKE '%{$niche}%'";
		else $nicheInsertion = "";

		$sql = "SELECT * FROM tags WHERE tag_category = 'Category'" . $nicheInsertion . " ORDER BY tag_name ASC LIMIT 0,3000";

		$i = 0;
		$q_result = $this->db->query($sql);

		while ($row = $q_result->fetch_assoc()) {
			$tags[$i]['id'] = $row['tag_id'];
			$tags[$i]['name'] = $row['tag_name'];
			$i++;
		}
		return $tags;
	}

	public function PaysitesListing ($string, $checked_paysite_id = false) {
		$gals_status = 0;
		$content_type = 0;
		$paysites = $this->AllPaysitesToString($string, $gals_status, $content_type, $checked_paysite_id);
		echo $paysites;
		return $paysites;
	}

//
//
//
//
	public function AllPaysitesToStringNoCount($string, $gals_status = 0, $content_type = 0, $checked_paysite_id = false) {
		$no_count = true;
		$paysites = $this->AllPaysitesToString($string, $gals_status, $content_type, $checked_paysite_id, $no_count);
		echo $paysites;
		return $paysites;
	}

	public function AllPaysitesToString($string, $gals_status = 0, $content_type = 0, $checked_paysite_id = false, $no_count = false) {
		$result = false;
		$where_set = false;
		if($no_count) {
			$sql = "SELECT paysites.paysite_id, 
						   paysites.paysite_name, paysites.paysite_niche,
						   paysites.last_update, paysites.paysite_category,
						   (SELECT tags.tag_name FROM tags WHERE tags.tag_id = paysites.paysite_category ) AS tag_name
					FROM paysites ";
		} else {
			$sql = "SELECT paysites.paysite_id, 
						   paysites.paysite_name, paysites.paysite_niche,
						   paysites.last_update, paysites.paysite_category, 
					   	   count(galleries.gal_id), (SELECT tags.tag_name FROM tags WHERE tags.tag_id = paysites.paysite_category ) AS tag_name
					FROM paysites
					LEFT JOIN galleries ON paysites.paysite_id = galleries.gal_paysite ";
		}


		if ($gals_status !== 0 
			&& preg_match("#^(trash|delete|unzipping|fetching|video_screening|video_converting|thumbing|pics_resizing|OK|uploaded|unzip_fail|zipupload_fail|fetching_fail|screen_fail|gif_fail|video_fail|grab_fail|thumbs_fail|upload_fail|new)$#", $gals_status)
			) {
			$where_set = true;
			$sql .= " WHERE gal_status = '".$gals_status."' ";
		}
		if (preg_match("#^(pics|movies|embed|gif|video)$#im", $content_type)) {
			if($where_set) $sql .= " AND ";
			else $sql .= " WHERE ";
			$sql .= " gal_type = '".$content_type."' ";
		} else $content_type = "";

		$sql .= " GROUP BY paysites.paysite_id
				  ORDER BY paysites.paysite_name ASC";
		$gals_count = 0;

			$stmt = $this->db->prepare($sql);
			
			if($stmt) {
				if($stmt->execute()) {

						$paysite_id = 0;
						$paysite_name = "";
						$paysite_niche = "";
						$last_update = 0;
						$paysite_category_id = null;
						$category_name = null;

						if($no_count) {
							$stmt->bind_result($paysite_id, $paysite_name, $paysite_niche, 
										   	   $last_update, $paysite_category_id, $category_name);	
						} else {
							$stmt->bind_result($paysite_id, $paysite_name, $paysite_niche, 
										   	   $last_update, $paysite_category_id, $category_name, 
										   	   $gals_count);	
						}

						$i = 0;
						$output = array();
						
						while($stmt->fetch()) {
							if (!$paysite_category_id) $category_name = "general";

							$output[$i] = array();

							$output[$i] = str_replace("#PAYSITE#", $paysite_name, $string);
							$output[$i] = str_replace("#PAYSITE_ID#", $paysite_id, $output[$i]);
							$output[$i] = str_replace("#PAYSITE_NICHE#", $paysite_niche, $output[$i]);
							$output[$i] = str_replace("#PAYSITE_CATEGORY#", $category_name, $output[$i]);
							$output[$i] = str_replace("#LAST_UPDATE#", $last_update, $output[$i]);
							$output[$i] = str_replace("#GALLERIES_COUNT#", $gals_count, $output[$i]);
							if ($paysite_id == $checked_paysite_id) {
								$output[$i] = str_replace("#CHECKED#", "selected", $output[$i]);
							} else {
								$output[$i] = str_replace("#CHECKED#", "", $output[$i]);
							}
							$i++;
						}

						if(is_array($output)) {
							$result = implode("\n", $output) ;
						}
				}
			} else {
				$log = new Logger(__METHOD__.": Ошибка stmt: '".$this->db->error."'", true);	
			}

		return $result;
	}

	public function AllNichesToString ($string) {
		
		$niches = array('Gay', 'Straight', 'Shemale');

		foreach ($niches as $niche) {
			$output = str_replace("#NICHE#", $niche, $string);
			echo $output;
		}
	}

	public function AllTagsToString ($string, $tag = FALSE) {
		$sql = ('SELECT * FROM tags ORDER BY tag_name ASC LIMIT 0, 10000');
		$q_result = $this->db->query($sql);
		while ($row = $q_result->fetch_assoc()) {
			$output = str_replace("#TAG#", $row['tag_name'], $string);

			if ($tag == $row['tag_id']) $output = str_replace("#SELECTED#", "selected='true'", $output);
			else $output = str_replace("#SELECTED#", "", $output);

			$output = str_replace("#TAG_ID#", $row['tag_id'], $output);
			echo $output;
		}
	}

	public function AllCropProfilesToString ($string, $id = FALSE) {
		$sql = ('SELECT * FROM crop_profiles ORDER BY crop_profile_name ASC');
		$i = 0;
		$q_result = $this->db->query($sql);
		while ($row = $q_result->fetch_assoc()) {
			$output = str_replace("#PROFILE#", $row['crop_profile_name'], $string);
			if (isset($id) && $id == $row['profile_id']) $output = str_replace("#SELECTED#", "selected='true'", $output);
			else $output = str_replace("#SELECTED#", "", $output);
			$output = str_replace("#PROFILE_ID#", $row['profile_id'], $output);
			echo $output;
		}
	}

	public function AllMainSitesToString($string, $checked = false) {
		
		$this->AllSitesToString($string, $checked, true);
	}

	public function AllSitesToString ($string, $checked = false, $no_satelites = false) {
		
		$sql = 'SELECT * FROM sites ';
		if ($no_satelites) $sql .= ' WHERE use_galleries_from = 0 AND only_export_site = 0';
		$sql .= ' ORDER BY site_name ASC LIMIT 0, 10000';

		$i = 0;
		$q_result = $this->db->query($sql);
		$count = $q_result->num_rows;

		while ($row = $q_result->fetch_assoc()) {
			$output = str_replace("#SITE#", $row['site_name'], $string);
			$output = str_replace("#SITE_ID#", $row['site_id'], $output);
			if ($checked && $row['site_id'] == $checked) {
				$output = str_replace("#CHECKED#", " selected", $output);
			} else {
				$output = str_replace("#CHECKED#", "", $output);
			}
			echo $output;
		}
	}
	
	
	public function selectRssThumbs ($id) {
		$id = (int)$id;
		$sql = "SELECT image_id, image
			FROM galleries_pix
			WHERE gal_id = '".$id."'
			AND rss_flag = '1'";
		$q_result = $this->db->query($sql);
		while ($row = $q_result->fetch_assoc()) {
			$output[$row['image_id']] = $row['image'];
		}
		if (isset($output)) return $output;
		else return FALSE;
	}
}