<?php
class Tags{
	var $_db;

	function __construct(PDO $db_connect = null) {
		$this->_db = ($db_connect) ? $db_connect :new db_access();
	}

	private function nicheString ($niches) {
		$niches_array = explode(",", $niches);
		$niches = "";
		$count = 0;
		foreach ($niches_array as $item) {
			if(preg_match('/(Gay|Straight|Shemale)/m', $item)) {
				$niches .= trim($item);
				$count++;
				if ($count < count($niches_array)) {
					$niches .=",";
				}
			}
		}
		return $niches;
	}

	function insertTag($name, $niches, $category, $approved = true, $main_tag_id = 0) {
		$result = false;
		$db = DB::get();
		if ($db) {
			$name = trim(strtolower($name));
			$name = preg_replace('/[^0-9a-z-\s]/', "", $name);
			$niches = $this->nicheString($niches);
			$approved = $approved ? 1 : 0;
			$main_tag_id = (int)$main_tag_id;
			$main_tag_id = $main_tag_id < 0	? 0 : $main_tag_id;		
			if (preg_match('/(Action|Category)/', $category) && $name != "" && $niches != "") {
				$sql = "INSERT INTO tags 
						(tag_name, tag_niche, tag_category, approved, main_tag_id) 
						VALUES (?, ?, ?, ?, ?)";
				$stmt = $db->prepare($sql);
				// var_dump($stmt, $db); die;
				if($stmt) {
					if($stmt->bind_param("sssii", $name, $niches, $category, $approved, $main_tag_id)) {
						if($stmt->execute()) {
							$result = $stmt->insert_id;
							if($result) {
								$this->addTagSynonym($result, $name);
							}
						} else {
							$log = new Logger(__METHOD__.": STMT error: '".$stmt->error."'", true);	
						}
					} else {
						$log = new Logger(__METHOD__.": STMT error: '".$stmt->error."'", true);	
					}
					
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": DB error: '".$db->error."'", true);
				}
			}
		}
		return $result;
	}

	function updateTag ($tag_id, $name, $niches, $category, $main_tag_id = 0) {
		$result = false;
		$tag_id = (int)$tag_id;
		if ($tag_id > 0) {
			$name = trim(strtolower($name));
			$name = preg_replace('/[^0-9a-z-\s]/', "", $name);
			$niches = $this->nicheString($niches);

			$main_tag_id = (int)$main_tag_id;
			$main_tag_id = $main_tag_id < 0	? 0 : $main_tag_id;	

			if (preg_match('/(Action|Category)/', $category) && $name != "" && $niches != "") {
				$db = DB::get();
				if($db) {
					$sql = "UPDATE tags SET 
								tag_name = ?, 
								tag_niche = ?, 
								tag_category = ?,
								main_tag_id = ? 
							WHERE tag_id = ?";

					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->bind_param("sssii", $name, $niches, $category, $main_tag_id, $tag_id)) {
							if($stmt->execute()) {
								$result = $stmt->affected_rows;
							} else {
								$log = new Logger(__METHOD__.": STMT error: '".$stmt->error."'", true);	
							}
						} else {
							$log = new Logger(__METHOD__.": STMT error: '".$stmt->error."'", true);	
						}					
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__.": DB error: '".$db->error."'", true);
					}
				}

			}
		}
		return $result;
	}

	/*
	
		Синонимы

		addTagSynonym($tag_id, $synonym)
		changeTagSynonym($synonym_id, $tag_id, $synonym)
		deleteTagSynonym($synonym_id)
		deleteTagSynonymsByTagId($tag_id)
		isSynonymExists($synonym)
		isSynonymBlacklisted($synonym)
		countSynonymsByTag($tag_id)
		countSynonyms($tag_id = false)
		getSynonyms($limit = false, $page = 0)
		getTagSynonyms($tag_id, $limit = false, $page = 0)
		getSynonyms($limit = false, $page = false, $tag_id = false)
		addTagCandidate($candidate_name)
		isCandidateExists($candidate_name)

	*/

	function addTagSynonym($tag_id, $synonym) {
		$result = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "INSERT INTO tags_synonyms (tag_id, synonym, added_on)
					(
						SELECT ?, ?, ?
						FROM DUAL
						WHERE NOT EXISTS (
							SELECT id 
							FROM tags_synonyms
							WHERE synonym = ?
						)
					)";

			// var_dump($sql);

			$stmt = $db->prepare($sql);
			if($stmt) {
				// var_dump($synonym);
				if($stmt->bind_param("isis", $tag_id, $synonym, $added_on, $synonym)) {
					if($stmt->execute()) {
						$result = $db->insert_id;	
						// var_dump($tag_id, $result);
					} else {
						$log = new Logger(__METHOD__.": Stmt Execute error '".$stmt->error."'", true);	
					}
				} else {
					$log = new Logger(__METHOD__.": Stmt Bind Param error '".$stmt->error."'", true);	
				}
				
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
			$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function changeTagSynonym($synonym_id, $tag_id, $synonym) {
		$result = false;

		$synonym_exists = $this->isSynonymExists($synonym);

		if(!$synonym_exists) {
			$db = DB::get();
			if($db) {
				$sql = "UPDATE tags_synonyms SET synonym = ?
						WHERE tag_id = ? AND id = ?";


				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("sii", $synonym, $tag_id, $synonym_id);
					$stmt->execute();
					$result = $stmt->affected_rows;
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
			}
		} else {
			// synonym exists ??

		}

				
		return $result;
	}

	function deleteTagSynonym($synonym_id) {
		$result = false;
		$synonym_id = (int)$synonym_id;
		if($synonym_id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "DELETE FROM tags_synonyms WHERE id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("i", $synonym_id);
					$stmt->execute();
					$result = $stmt->affected_rows;
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
			}
		}
		return $result;
	}

	function deleteTagSynonymsByTagId($tag_id) {
		$result = false;
		$tag_id = (int)$tag_id;
		if($tag_id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "DELETE FROM tags_synonyms WHERE tag_id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("i", $tag_id);
					$stmt->execute();
					$result = $stmt->affected_rows;
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
			}
		}
		return $result;
	}

	function isSynonymExists($synonym) {
		$reulst = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "SELECT id 
					FROM tags_synonyms 
					WHERE synonym = '".$synonym."'";
			$stmt = $db->prepare($sql);
			if($stmt) {
				$stmt->execute();
				$s_id = null;
				$stmt->bind_result($s_id);
				if($stmt->fetch()) {
					$result = $s_id;
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function isSynonymBlacklisted($synonym) {
		$result = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "SELECT id 
					FROM tags_synonyms_blacklist
					WHERE name = ?";
			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("s", $synonym)) {
					$stmt->execute();
					$s_id = null;
					$stmt->bind_result($s_id);
					if($stmt->fetch()) {
						$result = $s_id;
					}
					$stmt->close();	
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$stmt->error."'", true);
				}
					
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}


	function getTagIdBySynonym($synonym) {
		$result = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "SELECT tag_id 
					FROM tags_synonyms 
					WHERE synonym = ?";
			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("s", $synonym)) {
					$stmt->execute();
					$s_id = null;
					$stmt->bind_result($s_id);
					if($stmt->fetch()) {
						$result = $s_id;
					}	
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$stmt->error."'", true);
				}
				
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function countSynonymsByTag($tag_id) {
		return $this->countSynonyms($tag_id);
	}

	function countSynonyms($tag_id = false) {
		$result = false;
		$db = DB::get();
		$tag_id = (int)$tag_id;
		if($db) {
			$added_on = time();
			$sql = "SELECT count(id)
					FROM tags_synonyms";
			if($tag_id >0) {
				$sql .= " WHERE tag_id = '".$tag_id."'";
			}
			$stmt = $db->prepare($sql);
			if($stmt) {
				$stmt->execute();
				$s_id = null;
				$stmt->bind_result($s_id);
				if($stmt->fetch()) {
					$result = $s_id;
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function getTagSynonyms($tag_id, $limit = false, $page = false) {
		return $this->getSynonyms($limit, $page, $tag_id);
	}

	function getSynonyms($limit = false, $page = false, $tag_id = false) {
		$reulst = false;
		$db = DB::get();
		
			if($db) {
				$added_on = time();
				$sql = "SELECT id, synonym, added_on
						FROM tags_synonyms";
				if($tag_id !== false) {
					$tag_id = (int)$tag_id;
					$sql .= " WHERE tag_id = '".$tag_id."'";	
				}
				$sql .= " ORDER BY synonym ASC";

				if($limit !== false) {
					$limit = (int)$limit;
					if($limit > 0) {
						if($page !== false) {
							$page = abs((int)$page);
							$start_from = $page * $limit;
						} else {
							$start_from = 0;
						}
						$sql .= " LIMIT ".$start_from.", ".$limit.";";
					} else {
						// ошибка входящих
					}
				}
				
					 	
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->execute();

					$id = false;
					$synonym = false;
					$added_on = false;

					$stmt->bind_result($id, $synonym, $added_on);
					while($stmt->fetch()) {
						$result[] = compact("id", "synonym", "added_on");
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
					$log = new Logger(__METHOD__.": No DB connection", true);
			}
		return $result;
	}


	/*
		Кандидаты в теги
		addTagCandidate($candidate_name)
		isCandidateExists($candidate_name)
		getCandidateGalleries($candidate_id)
		addTagCandidateGallery($gal_id, $tag_candidate_id) 
		getCandidateById($candidate_id)
		deleteCandidateById($candidate_id)
		deleteCandidateByGalId($gal_id)
		getCandidatesList($count = 50, $page = 0)
		blacklistCandidateTag($candidate_id)
		blacklistWord($word)
	*/

	function addTagCandidate($candidate_name) {
		$result = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "INSERT INTO tags_candidates (tag_name, added_on)
					VALUES(?, ?)";

			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("si", $candidate_name, $added_on)) {
					$stmt->execute();
					$result = $stmt->insert_id;	
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function isCandidateExists($candidate_name) {
		$result = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "SELECT id 
					FROM tags_candidates 
					WHERE tag_name = ?";
			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("s", $candidate_name)) {
					$stmt->execute();
					$s_id = null;
					$stmt->bind_result($s_id);
					if($stmt->fetch()) {
						$result = $s_id;
					}	
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$stmt->error."'", true);
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function getCandidateGalleries($candidate_id) {
		$result = false;
		$candidate_id = (int)$candidate_id;
		if($candidate_id > 0) {
			$db = DB::get();

			if($db) {
				$added_on = time();
				$sql = "SELECT gal_id
						FROM tags_candidates_galleries
						WHERE tag_candidate_id = ?
						ORDER BY gal_id";
				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("i", $candidate_id)) {

						$gal_id = false;
						$stmt->execute();						
						$stmt->bind_result($gal_id);

						while($stmt->fetch()) {
							$result[] = $gal_id;
						}	
					} else {
						$log = new Logger(__METHOD__.": Stmt error '".$stmt->error."'", true);
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
					$log = new Logger(__METHOD__.": No DB connection", true);
			}
		}
		return $result;		
	}

	function addTagCandidateGallery($gal_id, $tag_candidate_id) {
		$result = false;
		
		$gal_id = (int)$gal_id;
		$tag_candidate_id = (int)$tag_candidate_id;
		if($gal_id > 0 && $tag_candidate_id > 0) {
			$db = DB::get();
			if($db) {
				$added_on = time();
				$sql = "INSERT INTO tags_candidates_galleries (gal_id, tag_candidate_id, added_on)
						VALUES(?, ?, ?)";

				$stmt = $db->prepare($sql);
				if($stmt) {
					if($stmt->bind_param("iii", $gal_id, $tag_candidate_id, $added_on)) {
						$stmt->execute();
						$result = $stmt->insert_id;	
					} else {
						$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
					$log = new Logger(__METHOD__.": No DB connection", true);
			}
		}
		return $result;
	}


	function deleteCandidateById($candidate_id) {
		$result = false;
		$candidate_id = (int)$candidate_id;
		if($candidate_id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "DELETE TC, TCG
						FROM tags_candidates TC
						LEFT JOIN tags_candidates_galleries TCG ON TC.id = TCG.tag_candidate_id
						WHERE TC.id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("i", $candidate_id);
					$stmt->execute();
					$result = true;
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
			}
		}
		return $result;		
	}


	function deleteCandidateByGalId($gal_id) {
		$result = false;
		$gal_id = (int)$gal_id;
		if($gal_id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "DELETE FROM tags_candidates_galleries
						WHERE gal_id = ?";
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->bind_param("i", $gal_id);
					$stmt->execute();
					$result = $stmt->affected_rows;
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
			}
		}
		return $result;		
	}


	function getCandidateById($candidate_id) {
		$result = false;
		$db = DB::get();
		$candidate_id = (int)$candidate_id;
		if($db) {
			$added_on = time();
			$sql = "SELECT id, tag_name, added_on,
						(
							SELECT count(id)
							FROM tags_candidates_galleries
							WHERE tag_candidate_id = ?
						) AS tags_count
					FROM tags_candidates
					WHERE id = ?";
			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("ii", $candidate_id, $candidate_id)) {
					$stmt->execute();
					$id = false;
					$tag_name = false;
					$added_on = false;
					$tags_count = false;
					$stmt->bind_result($id, $tag_name, $added_on, $tags_count);
					if($stmt->fetch()) {
						$result = compact("id", "tag_name", "added_on", "tags_count");
					}
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$stmt->error."'", true);
				}
				$stmt->close();
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;		
	}

	function getCandidatesList($limit = false, $page = false, $sort_by_count = false) {
		$result = false;
		$db = DB::get();
		
			if($db) {
				$added_on = time();
				$sql = "SELECT tags_candidates.id, tags_candidates.tag_name, tags_candidates.added_on, 
							   (SELECT count(tags_candidates_galleries.id) 
							   	FROM tags_candidates_galleries 
							   	WHERE tags_candidates.id = tags_candidates_galleries.tag_candidate_id) AS candidate_gals_count
						FROM tags_candidates";
				if($sort_by_count) {
					
					$sql .= " ORDER BY candidate_gals_count DESC";
				} else {
					$sql .= " ORDER BY added_on DESC";
				}

				if($limit !== false) {
					$limit = (int)$limit;
					if($limit > 0) {
						if($page !== false) {
							$page = abs((int)$page);
							$start_from = $page * $limit;
						} else {
							$start_from = 0;
						}
						
					} else {
						$start_from = 0;
						$limit = 50;
					}
					$sql .= " LIMIT ".$start_from.", ".$limit.";";
				}
				
					 	
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->execute();

					$id = false;
					$tag_name = false;
					$added_on = false;
					$candidate_gals_count = false;

					$stmt->bind_result($id, $tag_name, $added_on, $candidate_gals_count);
					while($stmt->fetch()) {
						$result[] = compact("id", "tag_name", "added_on", "candidate_gals_count");
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
					$log = new Logger(__METHOD__.": No DB connection", true);
			}
		return $result;
	}


	function getBlacklist($limit = false, $page = false) {
		$result = false;
		$db = DB::get();
		
			if($db) {
				$added_on = time();
				$sql = "SELECT id, name, added_on
						FROM tags_synonyms_blacklist";
				$sql .= " ORDER BY added_on DESC";

				if($limit !== false) {
					$limit = (int)$limit;
					if($limit > 0) {
						if($page !== false) {
							$page = abs((int)$page);
							$start_from = $page * $limit;
						} else {
							$start_from = 0;
						}
						$sql .= " LIMIT ".$start_from.", ".$limit.";";
					} else {
						// ошибка входящих
					}
				}
				
					 	
				$stmt = $db->prepare($sql);
				if($stmt) {
					$stmt->execute();

					$id = false;
					$name = false;
					$added_on = false;

					$stmt->bind_result($id, $name, $added_on);
					while($stmt->fetch()) {
						$result[] = compact("id", "name", "added_on");
					}
					$stmt->close();
				} else {
					$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
				}
			} else {
					$log = new Logger(__METHOD__.": No DB connection", true);
			}
		return $result;
	}

	
	function blacklistCandidateTag($candidate_id) {
		$result = false;

		$id = false;
		$tag_name = false;
		$added_on = false;
		$tags_count = false;

		$candidate = $this->getCandidateById($candidate_id);
		if($candidate && is_array($candidate)) {
			extract($candidate);
			if($this->blacklistWord($tag_name)) {
				$result = $this->deleteCandidateById($candidate_id);	
			} else {
				$log = new Logger(__METHOD__.": Can't blacklist Tag Candidate", true);
			}			
		}	

		return $result;
	}



	function blacklistWord($word) {
		$result = false;
		$db = DB::get();
		if($db) {
			$added_on = time();
			$sql = "INSERT INTO tags_synonyms_blacklist (name, added_on)
					(
						SELECT ?, ?
						FROM DUAL
						WHERE NOT EXISTS (
							SELECT id 
							FROM tags_synonyms_blacklist
							WHERE name = ?
						)
					)";

			$stmt = $db->prepare($sql);
			if($stmt) {
				if($stmt->bind_param("sis", $word, $added_on, $word)) {
					$stmt->execute();
					$result = $stmt->insert_id;	
				}
				$stmt->close();		
			} else {
				$log = new Logger(__METHOD__.": Stmt error '".$db->error."'", true);
			}
		} else {
				$log = new Logger(__METHOD__.": No DB connection", true);
		}
		return $result;
	}

	function ignoreCandidateTag($candidate_id) {
		$result = false;

		$id = false;
		$tag_name = false;
		$added_on = false;
		$tags_count = false;

		$candidate = $this->getCandidateById($candidate_id);
		if($candidate && is_array($candidate)) {
			$result = $this->deleteCandidateById($candidate_id);	
		}	

		return $result;
	}

	function approveCandidate($candidate_id, $niches, $category, $approved = true, $main_tag_id = 0) {
		$result = false;
		$candidate_id = (int)$candidate_id;
		$main_tag_id = (int)$main_tag_id;

		if($candidate_id > 0 && $main_tag_id >= 0) {
			if($main_tag_id > 0) {
				$main_tag_exists = $this->getTag($main_tag_id);
				if(!$main_tag_exists) {
					$main_tag_id = 0;
				}
			}

			$id = false;
			$tag_name = false;
			$added_on = false;
			$tags_count = false;

			$candidate = $this->getCandidateById($candidate_id);
			if($candidate) {
				extract($candidate);
				$candidate_added_id = $this->insertTag($tag_name, $niches, $category, $approved, $main_tag_id);

				if($candidate_added_id) {
					$this->approveCandidateGalleries($candidate_id, $candidate_added_id);
					$this->deleteCandidateById($candidate_id);
					$result = true;
				} else {
					$log = new Logger(__METHOD__.": Candidate tag TID:'".$candidate_id."', TNAME:'".$tag_name."' was not added to TAGS table :( ", false);
				}
			} else {
				// $candidate_id not exists
			}
		}

		return $result;
	}

	// исполняется только с удалением кандидата из списка кандидатов
	private function approveCandidateGalleries($candidate_id, $candidate_added_id) {
		$result = false;
		$candidate_added_id =(int)$candidate_added_id;
		if($candidate_added_id > 0) {
			$galleries = $this->getCandidateGalleries($candidate_id);
			$galleries_worker = new Galleries;
			if($galleries && is_array($galleries)) {
				foreach($galleries as $gal_id) {
					$galleries_worker->insertTag($gal_id, $candidate_added_id);
				}
			}	
		}		
		return $result;
	}

		// исполняется только с удалением кандидата из списка кандидатов
	private function approveCandidateGalleriesAsModel($candidate_id, $candidate_added_id) {
		$result = false;
		$candidate_added_id =(int)$candidate_added_id;
		if($candidate_added_id > 0) {
			$galleries = $this->getCandidateGalleries($candidate_id);
			$galleries_worker = new Galleries;
			if($galleries && is_array($galleries)) {
				foreach($galleries as $gal_id) {
					$galleries_worker->insertModel($gal_id, $candidate_added_id);
				}
			}	
		}		
		return $result;
	}

	public function addCandidateAsSynonym($candidate_id, $main_tag_id) {
		// берем данные о кандидате
		$result = false;
		$candidate_id = (int)$candidate_id;
		$main_tag_id = (int)$main_tag_id;
		if($candidate_id > 0 && $main_tag_id > 0) {
			$id = false;
			$tag_name = false;
			$added_on = false;
			$tags_count = false;

			$candidate = $this->getCandidateById($candidate_id);
			if($candidate) {
				extract($candidate);

				$candidate_added = $this->addTagSynonym($main_tag_id, $tag_name);

				if($candidate_added) {
					$this->approveCandidateGalleries($candidate_id, $main_tag_id);
					$this->deleteCandidateById($candidate_id);
					$result = true;
				} else {
					$log = new Logger(__METHOD__.": Candidate tag TID:'".$candidate_id."', TNAME:'".$tag_name."' was not added to TAGS table :( ", false);
				}
			} else {
				// $candidate_id not exists
			}
		}

		return $result;
	}


	function approveCandidateAsModel($candidate_id, $sex) {
		$result = false;
		$candidate_id = (int)$candidate_id;

		if($candidate_id > 0) {

			$id = false;
			$tag_name = false;
			$added_on = false;
			$tags_count = false;

			$candidate = $this->getCandidateById($candidate_id);
			if($candidate) {
				$model_worker = new CModels($this->_db);
				extract($candidate);
				$tag_name = ucwords($tag_name);
				$candidate_added_id = $model_worker->addModel($tag_name, $sex);

				if($candidate_added_id) {
					$this->approveCandidateGalleriesAsModel($candidate_id, $candidate_added_id);
					$this->deleteCandidateById($candidate_id);
					$result = true;
				} else {
					$log = new Logger(__METHOD__.": Candidate tag TID:'".$candidate_id."', TNAME:'".$tag_name."' was not added to TAGS table :( ", false);
				}
			} else {
				// $candidate_id not exists
			}
		}

		return $result;
	}

	

// Работа с очередью

// выбираем один айтем
// проверяем синонимы тегов
// если есть синоним, добавляем как тег в галеру
// если нет синонима проверяем стоп таблицу (блеклист)
// если нет проверяем если айтем есть в возможных тегах
// если нет в возможных тегах, добавляем возможный тег
// добавляем в таблицу возможных тегов галер айди возможного тега и ид галеры	
	

	function findAllZeroTags() {
		echo "start:";
		$sql = "select * from tags where tag_id = '0'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$rows = $rs->fetchAll();
			foreach ($rows as $row) {
				var_dump($row);
			}
		}
		echo "\n\n\n";
		$sql = "select * from galleries_tags where tag_id = '0'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$rows = $rs->fetchAll();
			foreach ($rows as $row) {
				var_dump($row);
			}
		}
		echo "finish";

	}

	function getTag($tag_id) {
		$result = false;
		$tag_id = intval($tag_id);
		if ($tag_id > 0) {
			$db = DB::get();
			if($db) {
				$sql = "SELECT tag_id, tag_name, main_tag_id, tag_niche, tag_category, approved, niche,
								(
									SELECT group_concat(synonym)
									FROM tags_synonyms
									WHERE tags.tag_id = tags_synonyms.tag_id AND synonym != tags.tag_name
								) AS synonyms 
						FROM tags 
						WHERE tag_id = ?";

				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("i", $tag_id)) {
							$stmt->execute();

							$id = false;
							$tag_name = false;
							$main_tag_id = false;
							$tag_niche = false;
							$tag_category = false;
							$approved = false;
							$niche = false;
							$synonym = false;

							$stmt->bind_result($id, $tag_name, $main_tag_id, $tag_niche, $tag_category, $approved, $niche, $synonym);
							if($stmt->fetch()) {
								$result['id'] = $id;
								$result['name'] = $tag_name;
								$result['niches'] = $tag_niche;
								$result['category'] = $tag_category;
								$result['main_tag_id'] = $main_tag_id;
								$result['synonym'] = $synonym;
								$niches = explode(",",$tag_niche);
								foreach($niches as $niche) {
									//var_dump($niche);
									if ($niche == 'Gay') $result['niche_array']['Gay'] = 'true';
									if ($niche == 'Straight') $result['niche_array']['Straight'] = 'true';
									if ($niche == 'Shemale') $result['niche_array']['Shemale'] = 'true';
								}					
							}
					}
					$stmt->close();
				}
			} else {
				$log = new Logger(__METHOD__.": NO DB connection", true);
			}
		}
		return $result;
	}

	function tagsCount ($niches = false, $no_array = false) {
		$result = false;
		$sql = "select count(tag_id) from tags";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$result = $rs->fetchColumn();
		}
		return $result;
	}

	function getAllTagsWithSynonyms($niches = false, $no_array = false, $sort_by_name = false) {
		$with_synonyms = true;
		return $this->getAllTags($niches, $no_array, $sort_by_name, $with_synonyms);
	}

	function getAllTagsSortedByName($niches = false, $no_array = false, $with_synonyms = false) {
		$sort_by_name = true;
		return $this->getAllTags($niches, $no_array, $sort_by_name, $with_synonyms);
	}

	function getSitesUniqueTags($site_id) {
		if((int)$site_id > 0) {
			$niches = false;
			$no_array = false;
			$sort_by_name = false;
			$with_synonyms = false;

			$site_id = (int)$site_id;

			return $this->getAllTags($niches, $no_array, $sort_by_name, $with_synonyms, $site_id);	
		} else {
			return false;
		}
		
	}

	function getAllTags($niches = false, $no_array = false, $sort_by_name = false, $with_synonyms = false, $site_id = false) {
		$result = array();
		if ($this->_db) {
			if($with_synonyms) {
				$sql = "SELECT tag_id, tag_name, main_tag_id, tag_niche, tag_category, approved, niche,
								(
									SELECT group_concat(synonym)
									FROM tags_synonyms
									WHERE tags.tag_id = tags_synonyms.tag_id AND synonym != tags.tag_name
									) AS synonyms
						FROM tags";
			} elseif($site_id) {
				$sql = "SELECT  tags.tag_id, tags.tag_name, tags.main_tag_id, tags.tag_niche, 
								tags.tag_category, tags.approved, tags.niche, '' AS synonyms,
								sites_tags.name, sites_tags.folder_name, sites_tags.title,
								sites_tags.description, sites_tags.keywords, 
								sites_tags.md5, sites_tags.gals_count, 
								sites_tags.video_count, sites_tags.total_count, 
								sites_tags.added_on, sites_tags.updated_on
						FROM tags
						LEFT JOIN sites_tags ON tags.tag_id =  sites_tags.tag_id
						WHERE sites_tags.site_id = ".$site_id.";";	
			} else {
				$sql = "SELECT tag_id, tag_name, main_tag_id, tag_niche, tag_category, approved, niche, '' AS synonyms
						FROM tags";	
			}
			
			if ($niches) {
				$niches_array = explode(",", $niches);
				$niches = "";
				$count = 0;
				foreach ($niches_array as $item) {
					if(preg_match('/(Gay|Straight|Shemale)/m', $item)) {
						if ($count ==0) $sql .= " where tag_niche like ";
						else $sql .= " and tag_niche like ";
						$sql .= " '%";
						$sql .= trim($item);
						$sql .= "%'";
						$count++;
					}
				}
			}
			if ($sort_by_name) {
				$sql .= " order by tag_name asc";
			}

			$db = DB::get();

			$stmt = $db->prepare($sql);

			if($stmt) {
				if ($stmt->execute()) {
					$tag_id = false;
					$tag_name = false;
					$tag_title = false;
					$main_tag_id = false;
					$tag_niche = false; 
					$tag_category = false;
					$approved = false;
					$niche = false;
					$synonyms = false;
					if($site_id) {
						$u_name = false;
						$u_folder_name = false;
						$u_title = false;
						$u_description = false;
						$u_keywords = false;
						$u_md5 = false;
						$u_gals_count = false;
						$u_video_count = false;
						$u_total_count = false;
						$u_added_on = false;
						$u_updated_on = false;					
						$stmt->bind_result($tag_id, $tag_name, $main_tag_id, $tag_niche, $tag_category, $approved, $niche, $synonyms, $u_name, $u_folder_name, $u_title, $u_description, $u_keywords, $u_md5, $u_gals_count, $u_video_count, $u_total_count, $u_added_on, $u_updated_on);
					} else {
						$stmt->bind_result($tag_id, $tag_name, $main_tag_id, $tag_niche, $tag_category, $approved, $niche, $synonyms);					}
					
					while($stmt->fetch()) {
						$result[$tag_id]['id'] = $tag_id;
						$result[$tag_id]['name'] = $tag_name;
						$result[$tag_id]['niches'] = $tag_niche;
						$result[$tag_id]['category'] = $tag_category;
						$niches = explode(",", $tag_niche);
						if ($no_array === false) {
							foreach($niches as $niche) {
								if ($niche == 'Gay') $result[$tag_id]['niche_array']['Gay'] = 'true';
								if ($niche == 'Straight') $result[$tag_id]['niche_array']['Straight'] = 'true';
								if ($niche == 'Shemale') $result[$tag_id]['niche_array']['Shemale'] = 'true';
							}
						}
						$result[$tag_id]['synonyms'] = $synonyms;

						if($site_id) {
							$result[$tag_id]['u_name'] = $u_name;
							$result[$tag_id]['u_folder_name'] = $u_folder_name;
							$result[$tag_id]['u_title'] = $u_title;
							$result[$tag_id]['u_description'] = $u_description;
							$result[$tag_id]['u_keywords'] = $u_keywords;
							$result[$tag_id]['u_md5'] = $u_md5;
							$result[$tag_id]['u_gals_count'] = $u_gals_count;
							$result[$tag_id]['u_video_count'] = $u_video_count;
							$result[$tag_id]['u_total_count'] = $u_total_count;
							$result[$tag_id]['u_added_on'] = $u_added_on;
							$result[$tag_id]['u_updated_on'] = $u_updated_on;
						}
					}
				}
			} else {
				$log = new Logger(__METHOD__.": DB error '".$db->error."'", true);
			}

				
		}
		return $result;
	}


	public function getSiteGalleriesTagsList($site_id, $sort_by = false) {
		$result = false;

		$db = DB::get();

		if($db) {
			if ($sort_by && in_array($sort_by, array("name","count","date"))) {

				switch ($sort_by) {
					case 'name':
						$sort_by = " ORDER BY name ASC";
						break;
					case 'count':
						$sort_by = " ORDER BY total_count ASC";
						break;
					case 'date':
						$sort_by = " ORDER BY added_on ASC";
						break;

					default:
						$sort_by = "";
						break;
				}
			}
			$sql = "SELECT 	id, tag_id, site_id, name, folder_name, title, description, 
							keywords, md5, gals_count, video_count, total_count, 
							pageviews, likes, added_on, updated_on
					FROM sites_tags
					WHERE site_id = ? " . $sort_by;
			if($db) {
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param("i", $site_id)) {
							$stmt->execute();

							$id = null;
							$tag_id = null;
							$site_id = null;
							$name = null;
							$folder_name = null;
							$title = null;
							$description = null;
							$keywords = null;
							$md5 = null;
							$gals_count = null;
							$video_count = null;
							$total_count = null;
							$pageviews = null;
							$likes = null;
							$added_on = null;
							$updated_on = null;

							$stmt->bind_result( $id, $tag_id, $site_id, $name, 
												$folder_name, $title, $description, $keywords,
												$md5, $gals_count, $video_count, 
												$total_count, $pageviews, $likes, 
												$added_on, $updated_on);
					    	while($stmt->fetch()) {
					    		$result[] = compact("id", "tag_id", "site_id", "name", "folder_name", "title", "description", "keywords", "md5", "gals_count", "video_count", "total_count", "pageviews", "likes", "added_on", "updated_on");
					    	}
						}
						$stmt->close();
					}		
					// var_dump($db);
				} else {
					$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
				}

		}

		return $result;

	}


	public function getSiteTagById($site_id, $tag_id) {
		$result = false;

		$site_id = (int)$site_id;
		$tag_id = (int)$tag_id;

		$db = DB::get();

		if($db) {
			
			$sql = "SELECT 	id, tag_id, site_id, name, folder_name, title, description, 
							keywords, md5, gals_count, video_count, total_count, 
							pageviews, likes, added_on, updated_on
					FROM sites_tags
					WHERE site_id = ? AND tag_id = ? ";
			if($db) {
					if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param("ii", $site_id, $tag_id)) {
							$stmt->execute();

							$id = null;
							$tag_id = null;
							$site_id = null;
							$name = null;
							$folder_name = null;
							$title = null;
							$description = null;
							$keywords = null;
							$md5 = null;
							$gals_count = null;
							$video_count = null;
							$total_count = null;
							$pageviews = null;
							$likes = null;
							$added_on = null;
							$updated_on = null;

							$stmt->bind_result( $id, $tag_id, $site_id, $name, 
												$folder_name, $title, $description, $keywords,
												$md5, $gals_count, $video_count, 
												$total_count, $pageviews, $likes, 
												$added_on, $updated_on);
					    	if($stmt->fetch()) {
					    		$result = compact("id", "tag_id", "site_id", "name", "folder_name", "title", "description", "keywords", "md5", "gals_count", "video_count", "total_count", "pageviews", "likes", "added_on", "updated_on");
					    	}
						}
						$stmt->close();
					}		
				} else {
					$log = new Logger(__METHOD__.": Нет коннекта к БД", true);
				}

		}

		return $result;

	}


	function updateSitesTag($site_id, $tag_info) {
		$result = false;
		// var_dump($site_id, $tag_info);

		// var_dump($this->getSiteGalleriesTagsList($site_id));
		if((int)$site_id > 0 && $tag_info && is_array($tag_info)) {
			$name = false;
			$folder_name = false;
			$title = false;
			$description = false;
			$keywords = false;
			$tag_id = false;
			
			extract($tag_info);

			// var_dump($tag_info);

			// var_dump($this->getSiteTagById($site_id, $tag_id));

			if($name && $folder_name && (int)$tag_id > 0) {
				// $folder_name = normalizer_normalize($folder_name);
				$db = DB::get();
				$sql = "UPDATE sites_tags 
						SET name = ?, folder_name = ?, title = ?, md5 = MD5(?),
							description = ?, keywords = ?
						WHERE site_id = ? AND tag_id = ?;";
				// var_dump($sql, $name, $folder_name, $site_id, $tag_id);
				if ($stmt = $db->prepare($sql)) {
						if ($stmt->bind_param("ssssssii", $name, $folder_name, $title, $folder_name, $description, $keywords, $site_id, $tag_id)) {
							$stmt->execute();
							$result = $stmt->affected_rows;	
						}
						 // var_dump($result); 
						$stmt->close();
				}
				 // var_dump($db); 
			} else {
				// echo "fail";
			}
		}

		
		return $result;
	}


	function formattedListing($format, $niches = false) {
		$result = false;
		if ($this->_db && $format) {
			$sort_by_date = true;
			$tags = $this->getAllTags($niches, false, $sort_by_date);
			foreach ($tags as $id => $tag) {
				$desc = strtolower($tag['name']);
				$desc = trim($desc);
				$desc = preg_replace ("/[^a-z0-9\s]/","",$desc);
				$desc = preg_replace ("/\s+/", " ", $desc);
				$tagUrlName = str_replace (" ", "-", $desc); // тоже самое в informator.php AddGalleryId()
				$result_p = preg_replace('/\#TAG_ID\#/', $tag['id'], $format);
				$result_p = preg_replace('/\#TAG_NAME\#/', $tag['name'], $result_p);
				$result_p = preg_replace('/\#TAG_NICHES\#/', $tag['niches'], $result_p);
				$result_p = preg_replace('/\#TAG_URL_NAME\#/', $tagUrlName, $result_p);
				$result .= preg_replace('/\#TAG_CATEGORY\#/', $tag['category'], $result_p);
			}
		}
		return $result;
	}

	function listUsedTags ($rules = false, $content_type = false) {
		$result = false;
		$sql = "";
		$where_set = false;
		if (is_array($rules)) {
			if (isset($rules['sites'])) {
				$sites_counter = false;
				$exclude_counter = false;
				$tag_counter = false;
				foreach ($rules['sites'] as $site) {
					if ($sites_counter) $sql .= " UNION ";
					$sql .= "(SELECT DISTINCT galleries_tags.gal_tags, tags.tag_name
							FROM galleries_tags LEFT JOIN tags ON galleries_tags.gal_tags = tags.tag_id 
							WHERE galleries_tags.gal_id IN ";
					$sql .= "(SELECT site_".intval($site['id']).".gal_id FROM site_".intval($site['id']);
					if (isset($site['tags']) && is_array($site['tags'])) {
						$sql .= " WHERE site_".intval($site['id']).".gal_id IN (SELECT gal_id FROM galleries_tags WHERE ";
						$where_set = true;
						foreach ($site['tags'] as $tag) {
							if ($tag_counter) $sql .= " AND ";
							$sql .= " gal_tags = '".intval($tag)."' ";
							$tag_counter = true;
						}
						$sql .= ")";
						$tag_counter = false;
					}
					if ($content_type && preg_match("#^(pics|movies)$#im", $content_type)) {
						if ($where_set) $sql .= " AND ";
						else $sql .= " WHERE ";
						$sql .= " site_".intval($site['id']).".gal_id IN (SELECT gal_id FROM galleries WHERE gal_type = '".ucfirst($content_type)."') ";
						$where_set = true;
					}
					$sql .= ")";
					if (isset($site['exclude_niches'])) {
						foreach($site['exclude_niches'] as $exclude) {
							if (!$exclude_counter) {
								$sql .= " AND galleries_tags.gal_id NOT IN (SELECT site_".intval($site['id']).".gal_id FROM site_".intval($site['id'])."
										 LEFT JOIN galleries_tags ON galleries_tags.gal_id = site_".intval($site['id']).".gal_id WHERE ";
							} else $sql .= " AND ";
							$sql .= " galleries_tags.gal_tags = '".intval($exclude)."' ";
							$exclude_counter = true;
						}
						if ($exclude_counter) $sql .= ")";
					}
					$sql .= ")";
					$exclude_counter = false;
					$sites_counter = true;
				}
			}
		}
		if ($content_type) {
			$log = new Logger($sql, true);
			//$this->_db->debug = true;
		}
		if ($sql != "") {
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll();
				if ($rows) {
					foreach ($rows as $row) {
						$result[$row['gal_tags']] = $row['tag_name'];
					}
				}
			}			
		}
		if (is_array($result)) asort($result);
		return $result;
	}

	public function getTagByName ($name) { /* принимает $name (string), выдает массив $result['id'],$result['name'], $result['niche'] или false */
		$result = false;
		$name = strtolower(trim($name));
		$name = preg_replace("/[^a-z0-9-]/", "", $name);

		if ($name != 'old-young') $name = preg_replace("/[-]/", " ", $name);
		if ($name && $this->_db) {
			//$this->_db->debug = true;
			$sql = "select * from tags where tag_name = '".$name."'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$rows = $rs->fetchAll();
				if ($rows) {
						$result['id'] = $rows[0]['tag_id'];
						$result['name'] = $rows[0]['tag_name'];
						$result['niches'] = $rows[0]['tag_niche'];
						$result['category'] = $rows[0]['tag_category'];
						//var_dump($rows[0]['tag_niche']);
						$niches = explode(",",$rows[0]['tag_niche']);
						foreach($niches as $niche) {
							//var_dump($niche);
							if ($niche == 'Gay') $result['niche_array']['Gay'] = 'true';
							if ($niche == 'Straight') $result['niche_array']['Straight'] = 'true';
							if ($niche == 'Shemale') $result['niche_array']['Shemale'] = 'true';
						}					
				}
			}
		}
		return $result;
	}	

}
?>