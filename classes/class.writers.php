<?php
class WritersQuery {
	private $db;

	function __construct() {		
		$this->db = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		if ($this->db->connect_error) {
			$this->db = false;
		    $log = new Logger('Connect Error (' . $this->db->connect_errno . ') '
		            . $this->db->connect_error, true);
		} else {
			$this->db->query("SET NAMES 'utf8';");
			$this->db->query("SET character_set_results = 'utf8';");
			$this->db->query("SET collation_connection = 'utf8_general_ci';");
		}
	}

	function pushGallery($site_id, $gal_id, $main_thumb, $query_on, $language = 'en') {
		$result = false;
		// var_dump($language);
		if($language == false) $language = 'en';
		if ($this->db) {
			$added_on = time();
			// $query_on == deadline
			$sql = "INSERT INTO writers_titles
					(site_id, gal_id, main_thumb, language, deadline, added_on)
					VALUE (?, ?, ?, ?, ?, ?)";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iiisii", $site_id, $gal_id, $main_thumb, $language, $query_on, $added_on);
				if ($binded) { 
					if ($stmt->execute()) { 
						$stmt->close(); 
						return true; 
					}	
					else $log = new Logger (__METHOD__.": execute error: '".$stmt->error."'",true);
				} else $log = new Logger (__METHOD__.": bind error: '".$stmt->error."'",true);
				$stmt->close();
			} else $log = new Logger (__METHOD__.": prepare error: '".$this->db->error."'",true);
		}
		return $result;
	}


	function popGallery($language = 'en') {
		$result = false;
		if ($this->db) {
			$sql = "SELECT writers_titles.id, writers_titles.site_id, writers_titles.gal_id, sites.keywords, writers_titles.language
					FROM writers_titles
					LEFT JOIN sites ON writers_titles.site_id = sites.site_id
					WHERE writers_titles.is_ready = 0 AND writers_titles.used = 0 AND writers_titles.language = ?
					ORDER BY writers_titles.deadline ASC
					LIMIT 1";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("s", $language);
				if ($binded) { 
					$stmt->bind_result($id, $site_id, $gal_id, $keywords, $language);
					if ($stmt->execute()) {
						if ($stmt->fetch()) {
							$result['id'] = $id; 
							$result['site_id'] = $site_id; 
							$result['gal_id'] = $gal_id;
							$result['keywords'] = $keywords;
							$result['language'] = $language;
						}
						$stmt->close(); 
						return $result; 
					}	
					else $log = new Logger (__METHOD__.": execute error: '".$stmt->error."'",true);
				} else $log = new Logger (__METHOD__.": bind error: '".$stmt->error."'",true);
				$stmt->close();
			} else $log = new Logger (__METHOD__.": prepare error: '".$this->db->error."'",true);
		}
		return $result;
	}

	function setGalleryReady($id, $gal_id, $site_id, $title, $writer_id) {
		$result = false;
		if ($title) { $title = sanitizeString($title); }
		// var_dump($title);
		$prepared_to_word_count = preg_replace("#[\.\,\&]#", "", $title);
		$title_length = strlen($prepared_to_word_count);
		$title_words_count = count(explode(" ", $prepared_to_word_count));
		$updated_on = time();
		if ($title_length > 12) {
			if ($this->db) {
				$sql = "UPDATE writers_titles
						SET title = ?, writer_id = ?, updated_on = ?, title_length = ?, title_words_count = ?, 
						is_ready = 1
						WHERE id = ? AND gal_id = ? AND site_id = ?";
				if ($stmt = $this->db->prepare($sql)) {
					$binded = $stmt->bind_param("siiiiiii", $title, $writer_id, $updated_on, $title_length, $title_words_count,
															$id, $gal_id, $site_id);
					if ($binded) { 
						if ($stmt->execute()) { 
							$stmt->close(); 
							$result = true;
						}
					}
				}
			}
		} else {
			$log = new Logger(__METHOD__.": Тайтл слишком короткий: Writer ID: '".$writer_id."', Title ID: '".$id."', GID: ".$gal_id.", Title: '".$title."', Title Length: '".$title_length."'", true);
		}
		
		return $result;
	}

	function queryLength($language = 'en') {
		$result = false;
		if ($this->db) {
			$sql = "SELECT COUNT(id)
					FROM writers_titles
					WHERE 		writers_titles.is_ready = 0 
							AND writers_titles.used = 0 
							AND writers_titles.language = ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("s", $language);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
						$stmt->close(); 
						return $result; 
					}	
					else $log = new Logger (__METHOD__.": execute error: '".$stmt->error."'",true);
				} else $log = new Logger (__METHOD__.": bind error: '".$stmt->error."'",true);
				$stmt->close();
			} else $log = new Logger (__METHOD__.": prepare error: '".$this->db->error."'",true);
		}
		return $result;

	}

	function popUnusedTitles() {
		$result = false;
		if ($this->db) {
		
			$sql = "SELECT id, gal_id, site_id, main_thumb, title, deadline
					FROM writers_titles
					WHERE is_ready = 1 AND used = 0
					ORDER BY deadline ASC
					LIMIT 100";
			if ($stmt = $this->db->prepare($sql)) {
				$stmt->bind_result($id, $gal_id, $site_id, $main_thumb, $title, $deadline);
				if ($stmt->execute()) {
					while ($stmt->fetch()) {
						$result[$id]['gal_id'] = $gal_id;
						$result[$id]['site_id'] = $site_id; 
						$result[$id]['main_thumb'] = $main_thumb; 
						$result[$id]['title'] = $title;
						$result[$id]['deadline'] = $deadline;
					}
					$stmt->close(); 
					return $result; 
				}	
				else $log = new Logger (__METHOD__.": execute error: '".$stmt->error."'",true);
				$stmt->close();
			}
		}
		return $result;
	}

	function setTitlesUsed() {
		$result = false;
		if ($this->db) {
			$unused_titles = $this->popUnusedTitles();
			if ($unused_titles && is_array($unused_titles)) {
				$this->db->autocommit(FALSE);	
				foreach($unused_titles as $title_id => $title_arr) {
					$gal_id = false;
					$site_id = false;
					$main_thumb = false;
					$title = false;
					$deadline = false;
					extract($title_arr);
					$sql = "UPDATE writers_titles
							SET used = 1
							WHERE id = ?";
					if ($stmt = $this->db->prepare($sql)) {
						$binded = $stmt->bind_param("i", $title_id);
						if ($binded) { 
							if (!$stmt->execute()) { $log = new Logger(__METHOD__.": SQL STMT execute failed \n '".$sql."'".$stmt->error, true); }
						}
					} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
					$stmt->close();
					$sql = "INSERT INTO sites_galleries_make_query 
							(site_id, gal_id, title, gallery_unique, main_thumb, query_on) 
							VALUE (?, ?, ?, 0, ?, ?)";
					if ($stmt = $this->db->prepare($sql)) {
						$binded = $stmt->bind_param("iisii", $site_id, $gal_id, $title, $main_thumb, $deadline);
						if ($binded) { 
							if (!$stmt->execute()) { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$stmt->error, true);}
						}
					} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
					$stmt->close();	
				}
				if ($this->db->commit()) $result = true;;
				
			}

		}
		
		return $result;
	}

	function galleriesDoneLastMonth ($writer_id, $year =false, $type = 'crop') {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			if ($month == 1) {
				$month = 12;
				$year--;
			} else $month--;
			$from_time = strtotime($year.'/'.$month.'/1  00:00:00');
			$days_in_month = date('t',$from_time);
			$day = $days_in_month;

			
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			// var_dump($from_time, $to_time);
			$sql = "SELECT COUNT(id)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
			
		}
		return $result;
	}

	function lettersDoneLastMonth($writer_id) {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			if ($month == 1) {
				$month = 12;
				$year--;
			} else $month--;
			$from_time = strtotime($year.'/'.$month.'/1  00:00:00');
			$days_in_month = date('t',$from_time);
			$day = $days_in_month;
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT SUM(title_length)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
			
		}
		return $result;
	}	

	function galleriesDoneThisMonth ($writer_id, $year =false, $type = 'crop') {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			$from_time = strtotime($year.'/'.$month.'/1  00:00:00');
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT COUNT(id)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
			
		}
		return $result;
	}

	function wordsDoneThisMonth($writer_id) {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			$from_time = strtotime($year.'/'.$month.'/1  00:00:00');
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT SUM(title_words_count)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
		}
		return $result;
	}

	function lettersDoneThisMonth($writer_id) {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			$from_time = strtotime($year.'/'.$month.'/1  00:00:00');
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT SUM(title_length)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
			
		}
		return $result;
	}



	function galleriesDoneToday($writer_id) {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			$from_time = strtotime($year.'/'.$month.'/'.$day.'  00:00:00');
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT COUNT(id)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
			
		}
		return $result;
	}

	function wordsDoneToday($writer_id) {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			$from_time = strtotime($year.'/'.$month.'/'.$day.'  00:00:00');
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT SUM(title_words_count)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
		}
		return $result;
	}

	function lettersDoneToday($writer_id) {
		$result = false;
		if ($this->db) {
			$year = date('Y');
			$month = date('m');
			$day = date('d');
			$from_time = strtotime($year.'/'.$month.'/'.$day.'  00:00:00');
			$to_time = strtotime($year.'/'.$month.'/'.$day.'  23:59:59');
			$sql = "SELECT SUM(title_length)
					FROM writers_titles
					WHERE writer_id = ?
					  AND is_ready = 1
					  AND updated_on >= ? 
					  AND updated_on <= ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("iii", $writer_id, $from_time, $to_time);
				if ($binded) { 
					$stmt->bind_result($count);
					if ($stmt->execute()) {
						if ($stmt->fetch()) { $result = $count; }
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
			
		}
		return $result;
	}

	function returnGalleryToWriter() {

	}

	function getWriterLanguage() {

	}

	function getQueryIdsBySite($site_id) {
		$result = false;
		if ($this->db) {
			$sql = "SELECT writers_titles.id
					FROM writers_titles
					WHERE writers_titles.site_id = ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("i", $site_id);
				if ($binded) { 
					$stmt->bind_result($id);
					if ($stmt->execute()) {
						while ($stmt->fetch()) {
							$result[] = $id; 
						}
						$stmt->close(); 
						return $result; 
					}	
					else $log = new Logger (__METHOD__.": execute error: '".$stmt->error."'",true);
				} else $log = new Logger (__METHOD__.": bind error: '".$stmt->error."'",true);
				$stmt->close();
			} else $log = new Logger (__METHOD__.": prepare error: '".$this->db->error."'",true);
		}
		return $result;		
	}

	function changeLanguageForTitles($site_id, $language) {
		$result = false;
		if ($this->db) {
			$query_ids = $this->getQueryIdsBySite($site_id);

			if ($query_ids && is_array($query_ids)) {
				// var_dump($query_ids);
				foreach($query_ids as $title_id) {
					$sql = "UPDATE writers_titles
							SET language = ?
							WHERE id = ?";
					if ($stmt = $this->db->prepare($sql)) {
						$binded = $stmt->bind_param("si", $language, $title_id );
						if ($binded) { 
							$stmt->execute();
							echo "Binded, executed<br>";
						}
					} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
					$stmt->close();
				}
			}
		}
		return $result;		
	}

	function getQuery() {
		$result = false;
		if ($this->db) {
			$sql = "SELECT id, gal_id, site_id, main_thumb, title, language, deadline, writer_id
					FROM writers_titles
					WHERE is_ready = 0 AND used = 0
					ORDER BY deadline ASC";
			if ($stmt = $this->db->prepare($sql)) {
				if($stmt->execute()) {
					$stmt->bind_result($id, $gal_id, $site_id, $main_thumb, $title, $language, $deadline, $writer_id);
					if ($stmt->execute()) {
						while ($stmt->fetch()) {
							$result[$id]['id'] = $id;
							$result[$id]['gal_id'] = $gal_id;
							$result[$id]['site_id'] = $site_id;
							$result[$id]['main_thumb'] = $main_thumb;
							$result[$id]['title'] = $title;
							$result[$id]['language'] = $language;
							$result[$id]['deadline'] = $deadline;
							$result[$id]['writer_id'] = $writer_id;
						}
						$stmt->close(); 
						return $result; 
					}
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
		}		
		return $result;		
	}

	function removeFromQuery($id) {
		$result = false;
		if ($this->db) {
			$sql = "DELETE 
					FROM writers_titles
					WHERE id = ?";
			if ($stmt = $this->db->prepare($sql)) {
				$binded = $stmt->bind_param("i", $id );
				if ($binded) { 
					$stmt->execute();
					$result = true;
				}
				$stmt->close();
			} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
				
		}		
		return $result;
	}


	function moveWholeQueryByDays($days, $site_id = false) {
		$result = false;
		$days = (int)$days;
		if($days != 0 && $days < 60) {
			$time_to_add = $days * 24 * 60 * 60;
			if($site_id && (int)$site_id > 0) {
				$site_id = (int)$site_id;
			} else {
				$site_id = false;
			}


			if ($this->db) {
						$sql = "UPDATE writers_titles
								SET deadline = deadline + ?";
						if($site_id) {
							$sql .= " WHERE site_id = ?";
						}
						if ($stmt = $this->db->prepare($sql)) {
							if($site_id) {
								$binded = $stmt->bind_param("ii", $time_to_add, $site_id );
							} else {
								$binded = $stmt->bind_param("i", $time_to_add);
							}
							if ($binded) { 
								$stmt->execute();
								// echo "Binded, executed<br>";
							}
						} else { $log = new Logger(__METHOD__.": SQL STMT Prepare failed \n '".$sql."'".$this->db->error, true);}
						$stmt->close();

			}
		}
		return $result;		
	}


		function getQueriedSites() {
		$result = array();
		if ($this->db) {
			$sql = "SELECT DISTINCT writers_titles.site_id, sites.site_name
					FROM writers_titles
					LEFT JOIN sites ON writers_titles.site_id = sites.site_id
					WHERE writers_titles.is_ready = 0
					GROUP BY writers_titles.site_id
					ORDER BY sites.site_name ASC
					";
			if ($stmt = $this->db->prepare($sql)) {
					$site_id = false;
					$site_name = false;
					$stmt->bind_result($site_id, $site_name);
					if ($stmt->execute()) {
						while ($stmt->fetch()) {
							$result[$site_id]['site_id'] = $site_id; 
							$result[$site_id]['site_name'] = $site_name;
						}
						$stmt->close(); 
						return $result; 
					}	
					else $log = new Logger (__METHOD__.": execute error: '".$stmt->error."'",true);
				$stmt->close();
			} else $log = new Logger (__METHOD__.": prepare error: '".$this->db->error."'",true);
		}
		return $result;
	}

}
?>