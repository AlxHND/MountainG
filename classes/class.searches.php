<?php
class Searches{
	private $db = false;

	function __construct() {
		$this->db = DB::get();
	}

	function isExists($search_term, $site_id = false) {
		$result = false;
		$site_id = (int)$site_id;
		if($this->db) {
			$added_on = time();
			$sql = "SELECT id FROM sites_searches
					WHERE search_key LIKE ?";
			if($site_id) {
				$sql .= " AND site_id = ?";
			}
			if ($stmt = $this->db->prepare($sql)) {
					if($site_id) $stmt->bind_param("si", $search_term, $site_id);
					else $stmt->bind_param("s", $search_term);

					$id = null;

					if ($stmt->execute()) {
				   		$stmt->bind_result($id);
						if($stmt->fetch()) { 
							$result = $id;
						}
					} else { $log = new Logger(__METHOD__.": Ошибка execute. SQL:'".$stmt->error."' ", true); }
					$stmt->close();
			} else { $log = new Logger(__METHOD__.": Ошибка stmt. SQL:'".$sql."', Error:".$this->db->error, true); }
		} else { $log = new Logger(__METHOD__.": DB connetion fail", true);}
		return $result;		
	}

	function insertSearch($search_term, $site_id, $check_if_exists = false) {
		$result = false;
		$site_id = (int)$site_id;
		if($this->db) {
			if($check_if_exists) {
				$not_unique = $this->isExists($search_term, $site_id);
				echo "not unique: ";
				var_dump($not_unique);
				if($not_unique) {
					$log = new Logger(__METHOD__.": сёрч неуникальный для сайта #".$site_id.", '".$search_term."'", true);
					return $result;
				}
			}
			$added_on = time();
			$sql = "INSERT INTO sites_searches
					(search_key, site_id, added_on)
					VALUES (?, ?, ?)";
			if ($stmt = $this->db->prepare($sql)) {
					$stmt->bind_param("sii", $search_term, $site_id, $added_on);
					if ($stmt->execute()) {
				   		$gal_id = $stmt->insert_id;
				   		var_dump($gal_id);
				   		if($gal_id) { $result = $gal_id; }
				   		else { $log = new Logger(__METHOD__."Ошибка добавления.", true); }
					} else { $log = new Logger(__METHOD__.": Ошибка execute. SQL:'".$stmt->error."' ", true); }
					$stmt->close();
			} else { $log = new Logger(__METHOD__.": Ошибка stmt. SQL:'".$sql."', Error:".$this->db->error, true); }
		} else { $log = new Logger(__METHOD__.": DB connetion fail", true);}
		return $result;
	}

	function insertSearchUnique($search_term, $site_id) {
		return $this->insertSearch($search_term, $site_id, true);
	}

	function approveSearch($search_id) {
		$result = false;
		$search_id = (int)$search_id;
		if($search_id) {
			if($this->db) {
				$added_on = time();
				$sql = "UPDATE sites_searches SET approved = 1 WHERE id = ?";
				if ($stmt = $this->db->prepare($sql)) {
						$stmt->bind_param("i", $search_id);
						if ($stmt->execute()) $result = true;
						else { $log = new Logger(__METHOD__.": Ошибка execute. SQL:'".$stmt->error."' ", true); }
						$stmt->close();
				} else { $log = new Logger(__METHOD__.": Ошибка stmt. SQL:'".$sql."', Error:".$this->db->error, true); }
			} else { $log = new Logger(__METHOD__.": DB connetion fail", true);}	
		} else { $log = new Logger(__METHOD__.": SEARCH_ID == 0", true);}
		
		return $result;		
	}

	function getSearches($count = 50, $page = 0, $site_id = false, $approved = false, $added_on = false) {
		$result = false;
		$count = (int)$count;
		$page = (int)$page;
		$site_id = (int)$site_id;

		$offset = $page * $count;
		$offset_sql = "";
		$approved_sql = false;
		$site_id_sql = false;
		$where_set = false;

		if($site_id) {
			if($this->db) {
				$added_on = time();
				$sql = "SELECT id, search_key, added_on, approved, site_id FROM sites_searches";

				if($site_id) {
					if(!$where_set) {
						$where_sql = " WHERE ";
						$where_set = true;
					} else {
						$where_sql = " AND ";
					}

					$site_id_sql = $where_sql . " site_id = ".$site_id." ";

				}

				if($approved !== false) {
					if(!$where_set) {
						$where_sql = " WHERE ";
						$where_set = true;
					} else {
						$where_sql = " AND ";
					}

					if($approved) $approved = 1;
					else $approved = 0;

					$approved_sql = $where_sql . " approved = ".$approved." ";

				}

				$offset_sql = "LIMIT ".$offset.", ".$count.";";
				$sql =  $sql . $site_id_sql . $approved_sql . $offset_sql;
				
				if ($stmt = $this->db->prepare($sql)) {
					if($stmt->execute()) {

						$id = null;
						$search_key = null;

						$stmt->bind_result($id, $search_key, $added_on, $approved, $site_id);
						while($stmt->fetch()) { 
							$result[$id]['id'] = $id; 
							$result[$id]['search_key'] = $search_key; 
							$result[$id]['added_on'] = $added_on; 
							$result[$id]['approved'] = $approved; 
							$result[$id]['site_id'] = $site_id; 
						}
					} else { $log = new Logger(__METHOD__.": Ошибка execute. SQL:'".$stmt->error."' ", true); }
					$stmt->close();
				} else { $log = new Logger(__METHOD__.": Ошибка stmt. SQL:'".$sql."', Error:".$this->db->error, true); }
			} else { $log = new Logger(__METHOD__.": DB connetion fail", true);}	
		} else { $log = new Logger(__METHOD__.": SEARCH_ID == 0", true);}
		
		return $result;			
	}
}
?>