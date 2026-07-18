<?php
// При изменении класса и таблицы вносить изменения в getModelInfo (class.sites.php)
class Sources {
	var $_db;

	function __construct(PDO $db_connect) {		
		$this->_db = $db_connect;
	}

	private function normalizeAffiliateProgramId($affiliateProgramId)
	{
		$affiliateProgramId = (int)$affiliateProgramId;
		return $affiliateProgramId > 0 ? $affiliateProgramId : null;
	}

	private function getAffiliateProgramRow($affiliateProgramId)
	{
		$affiliateProgramId = $this->normalizeAffiliateProgramId($affiliateProgramId);
		if (!$affiliateProgramId) {
			return false;
		}

		$sql = "SELECT affiliate_program_id, affiliate_program_name, affiliate_program_url, affiliate_program_description
				FROM affiliate_programs
				WHERE affiliate_program_id = :affiliate_program_id";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('affiliate_program_id' => $affiliateProgramId));

		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	private function resolveAffiliateString($affiliate, $affiliateProgramId)
	{
		$affiliate = trim((string)$affiliate);
		$affiliateProgram = $this->getAffiliateProgramRow($affiliateProgramId);

		if ($affiliate === '' && $affiliateProgram) {
			if (!empty($affiliateProgram['affiliate_program_url'])) {
				$affiliate = trim((string)$affiliateProgram['affiliate_program_url']);
			} else {
				$affiliate = trim((string)$affiliateProgram['affiliate_program_name']);
			}
		}

		return $affiliate;
	}

	public function getAffiliatePrograms()
	{
		$result = array();
		$sql = "SELECT affiliate_programs.affiliate_program_id, affiliate_programs.affiliate_program_name,
					affiliate_programs.affiliate_program_url, affiliate_programs.affiliate_program_description,
					affiliate_programs.created_at, affiliate_programs.updated_at,
					COUNT(paysites.paysite_id) AS paysites_count
				FROM affiliate_programs
				LEFT JOIN paysites
					ON paysites.affiliate_program_id = affiliate_programs.affiliate_program_id
				GROUP BY affiliate_programs.affiliate_program_id, affiliate_programs.affiliate_program_name,
					affiliate_programs.affiliate_program_url, affiliate_programs.affiliate_program_description,
					affiliate_programs.created_at, affiliate_programs.updated_at
				ORDER BY affiliate_program_name ASC";
		$stmt = $this->_db->prepare($sql);
		if ($stmt && $stmt->execute()) {
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		return $result;
	}

	public function getAffiliateProgramById($affiliateProgramId)
	{
		$affiliateProgramId = $this->normalizeAffiliateProgramId($affiliateProgramId);
		if (!$affiliateProgramId) {
			return false;
		}

		$sql = "SELECT affiliate_program_id, affiliate_program_name, affiliate_program_url, affiliate_program_description, created_at, updated_at
				FROM affiliate_programs
				WHERE affiliate_program_id = :affiliate_program_id";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('affiliate_program_id' => $affiliateProgramId));

		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	public function getAffiliateProgramPaysitesCount($affiliateProgramId)
	{
		$affiliateProgramId = $this->normalizeAffiliateProgramId($affiliateProgramId);
		if (!$affiliateProgramId) {
			return 0;
		}

		$sql = "SELECT COUNT(paysite_id) AS paysites_count
				FROM paysites
				WHERE affiliate_program_id = :affiliate_program_id";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('affiliate_program_id' => $affiliateProgramId));

		return (int)$stmt->fetchColumn();
	}

	public function saveAffiliateProgram($name, $url = null, $description = '', $affiliateProgramId = null)
	{
		$name = trim((string)$name);
		$url = trim((string)$url);
		$description = trim((string)$description);
		$affiliateProgramId = $this->normalizeAffiliateProgramId($affiliateProgramId);

		if ($name === '') {
			throw new Exception('Affiliate program name is required');
		}

		if ($url === '') {
			$url = null;
		}

		if (strlen($description) > 512) {
			$description = substr($description, 0, 512);
		}

		if ($affiliateProgramId) {
			$sql = "UPDATE affiliate_programs
					SET affiliate_program_name = :name,
						affiliate_program_url = :url,
						affiliate_program_description = :description,
						updated_at = NOW()
					WHERE affiliate_program_id = :affiliate_program_id";
			$params = array(
				'name' => $name,
				'url' => $url,
				'description' => $description,
				'affiliate_program_id' => $affiliateProgramId,
			);
		} else {
			$sql = "INSERT INTO affiliate_programs
					(affiliate_program_name, affiliate_program_url, affiliate_program_description, created_at, updated_at)
					VALUES
					(:name, :url, :description, NOW(), NOW())";
			$params = array(
				'name' => $name,
				'url' => $url,
				'description' => $description,
			);
		}

		$stmt = $this->_db->prepare($sql);
		$stmt->execute($params);

		return $affiliateProgramId ? $affiliateProgramId : (int)$this->_db->lastInsertId();
	}

	public function deleteAffiliateProgram($affiliateProgramId)
	{
		$affiliateProgramId = $this->normalizeAffiliateProgramId($affiliateProgramId);
		if (!$affiliateProgramId) {
			return false;
		}

		if ($this->getAffiliateProgramPaysitesCount($affiliateProgramId) > 0) {
			return false;
		}

		$sql = "DELETE FROM affiliate_programs WHERE affiliate_program_id = :affiliate_program_id";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('affiliate_program_id' => $affiliateProgramId));

		return $stmt->rowCount() > 0;
	}

	public function getPaysiteUpdateMarkerTypes()
	{
		return array(
			'latest' => 'Последний по времени',
			'backfill' => 'Последний из старых',
		);
	}

	public function getPaysiteUpdateMarkerById($markerId)
	{
		$markerId = (int)$markerId;
		if ($markerId <= 0) {
			return false;
		}

		$sql = "SELECT markers.id, markers.paysite_id, markers.marker_type, markers.update_title,
					markers.update_page_url, markers.update_inner_date, markers.created_at, markers.updated_at,
					paysites.paysite_name
				FROM paysite_update_markers AS markers
				LEFT JOIN paysites ON paysites.paysite_id = markers.paysite_id
				WHERE markers.id = :id";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('id' => $markerId));

		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	public function getPaysiteUpdateMarker($paysiteId, $markerType)
	{
		$paysiteId = (int)$paysiteId;
		$markerType = trim((string)$markerType);
		$types = $this->getPaysiteUpdateMarkerTypes();
		if ($paysiteId <= 0 || !isset($types[$markerType])) {
			return false;
		}

		$sql = "SELECT id, paysite_id, marker_type, update_title, update_page_url, update_inner_date, created_at, updated_at
				FROM paysite_update_markers
				WHERE paysite_id = :paysite_id
				AND marker_type = :marker_type";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array(
			'paysite_id' => $paysiteId,
			'marker_type' => $markerType,
		));

		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	public function getPaysiteUpdateMarkersByPaysite($paysiteId)
	{
		$paysiteId = (int)$paysiteId;
		if ($paysiteId <= 0) {
			return array();
		}

		$sql = "SELECT id, paysite_id, marker_type, update_title, update_page_url, update_inner_date, created_at, updated_at
				FROM paysite_update_markers
				WHERE paysite_id = :paysite_id
				ORDER BY marker_type ASC";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('paysite_id' => $paysiteId));

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function findPaysiteUpdateMarkers(array $filters = array())
	{
		$where = array();
		$params = array();
		$types = $this->getPaysiteUpdateMarkerTypes();

		if (!empty($filters['paysite_id'])) {
			$where[] = "markers.paysite_id = :paysite_id";
			$params['paysite_id'] = (int)$filters['paysite_id'];
		}

		if (!empty($filters['marker_type']) && isset($types[$filters['marker_type']])) {
			$where[] = "markers.marker_type = :marker_type";
			$params['marker_type'] = $filters['marker_type'];
		}

		if (!empty($filters['query'])) {
			$where[] = "(markers.update_title LIKE :query OR markers.update_page_url LIKE :query OR paysites.paysite_name LIKE :query)";
			$params['query'] = '%' . trim((string)$filters['query']) . '%';
		}

		$allowedSort = array(
			'updated_at' => 'markers.updated_at',
			'inner_date' => 'markers.update_inner_date',
			'paysite_name' => 'paysites.paysite_name',
			'marker_type' => 'markers.marker_type',
		);
		$sortBy = isset($filters['sort_by'], $allowedSort[$filters['sort_by']]) ? $allowedSort[$filters['sort_by']] : 'markers.updated_at';
		$sortDir = (isset($filters['sort_dir']) && strtolower((string)$filters['sort_dir']) === 'asc') ? 'ASC' : 'DESC';
		$limit = isset($filters['limit']) ? (int)$filters['limit'] : 100;
		if ($limit <= 0) {
			$limit = 100;
		}
		if ($limit > 1000) {
			$limit = 1000;
		}

		$sql = "SELECT markers.id, markers.paysite_id, markers.marker_type, markers.update_title,
					markers.update_page_url, markers.update_inner_date, markers.created_at, markers.updated_at,
					paysites.paysite_name
				FROM paysite_update_markers AS markers
				LEFT JOIN paysites ON paysites.paysite_id = markers.paysite_id";
		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}
		$sql .= " ORDER BY " . $sortBy . " " . $sortDir . ", markers.id " . $sortDir . " LIMIT " . $limit;

		$stmt = $this->_db->prepare($sql);
		$stmt->execute($params);

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function savePaysiteUpdateMarker($paysiteId, $markerType, $title, $pageUrl, $innerDate = null, $markerId = null)
	{
		$paysiteId = (int)$paysiteId;
		$markerId = (int)$markerId;
		$markerType = trim((string)$markerType);
		$title = trim((string)$title);
		$pageUrl = trim((string)$pageUrl);
		$types = $this->getPaysiteUpdateMarkerTypes();

		if ($paysiteId <= 0) {
			throw new Exception('Paysite ID is required');
		}
		if (!isset($types[$markerType])) {
			throw new Exception('Unknown marker type');
		}
		if ($title === '' && $pageUrl === '' && ($innerDate === null || $innerDate === '')) {
			throw new Exception('At least one marker field must be filled');
		}

		$innerDate = trim((string)$innerDate);
		if ($innerDate === '') {
			$innerDate = null;
		}

		if ($markerId > 0) {
			$sql = "UPDATE paysite_update_markers
					SET paysite_id = :paysite_id,
						marker_type = :marker_type,
						update_title = :update_title,
						update_page_url = :update_page_url,
						update_inner_date = :update_inner_date,
						updated_at = NOW()
					WHERE id = :id";
			$params = array(
				'paysite_id' => $paysiteId,
				'marker_type' => $markerType,
				'update_title' => $title,
				'update_page_url' => $pageUrl,
				'update_inner_date' => $innerDate,
				'id' => $markerId,
			);
		} else {
			$existing = $this->getPaysiteUpdateMarker($paysiteId, $markerType);
			if ($existing) {
				$markerId = (int)$existing['id'];
				return $this->savePaysiteUpdateMarker($paysiteId, $markerType, $title, $pageUrl, $innerDate, $markerId);
			}

			$sql = "INSERT INTO paysite_update_markers
					(paysite_id, marker_type, update_title, update_page_url, update_inner_date, created_at, updated_at)
					VALUES
					(:paysite_id, :marker_type, :update_title, :update_page_url, :update_inner_date, NOW(), NOW())";
			$params = array(
				'paysite_id' => $paysiteId,
				'marker_type' => $markerType,
				'update_title' => $title,
				'update_page_url' => $pageUrl,
				'update_inner_date' => $innerDate,
			);
		}

		$stmt = $this->_db->prepare($sql);
		$stmt->execute($params);

		return $markerId > 0 ? $markerId : (int)$this->_db->lastInsertId();
	}

	public function deletePaysiteUpdateMarker($markerId)
	{
		$markerId = (int)$markerId;
		if ($markerId <= 0) {
			return false;
		}

		$sql = "DELETE FROM paysite_update_markers WHERE id = :id";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(array('id' => $markerId));

		return $stmt->rowCount() > 0;
	}

	// возвращает массив заполненый данными о модели с id, объект переинициализируется в соответствии с айди.
	function getSource($source_id) {
		$source_id = (int)$source_id;
		$source_result = $this->getAllSources($source_id);
		return $source_result ? $source_result[$source_id] : false;
	}

	public function getSourceByName(string $name) {

		try {
			$sql = "SELECT
						paysite_id AS id, 
						paysite_name AS name, 
						paysite_link AS link, 
						legal_link
					FROM paysites 
					WHERE 
						LOWER(paysite_name) = :name";

			$stmt = $this->_db->prepare($sql);
			$stmt->execute(['name' => strtolower($name)]);

			return $stmt->fetchObject();	

		} catch(Exception $e) {
			throw new Exception("Database error, getSourceByName". $e->getMessage());
			
		}

		return null;
	}

	function getSourceNameById($source_id) {
		$db = DB::get();

		$source_id = (int)$source_id;

		$sql = "select paysite_name from `paysites` where paysite_id = $source_id";
		$q_result = $db->query($sql);

		if ($row = $q_result->fetch_assoc()) {
			return $row['paysite_name'];
		} else {
			return false;
		}
	}


	function getDuplicateSources() {
		$db = DB::get();

		$sql = "select paysite_name, count(paysite_name) from `paysites` 
				GROUP BY paysite_name
				HAVING COUNT(paysite_name)>1";
		$q_result = $db->query($sql);

		if ($row = $q_result->fetch_assoc()) {
			return $row;
		} else {
			return false;
		}
	}	

	/*

		$legal_links: -1 - with, 1 without

	*/
	function getAllSources($source_id = false, $niche = false, $legal_links = false, $category = false, $affiliate_program_id = false) {
		$source_info = array();

		$db = DB::get();

			$sql = "select paysites.*,
						affiliate_programs.affiliate_program_id AS linked_affiliate_program_id,
						affiliate_programs.affiliate_program_name AS linked_affiliate_program_name,
						affiliate_programs.affiliate_program_url AS linked_affiliate_program_url,
						affiliate_programs.affiliate_program_description AS linked_affiliate_program_description
					from `paysites`
					left join affiliate_programs
						on affiliate_programs.affiliate_program_id = paysites.affiliate_program_id";
		
			if ((int)$source_id > 0 || $niche || $category || $legal_links != false || (int)$affiliate_program_id > 0) {

			$sql .= " where";

			$sql_and = false;
			
			if ((int)$source_id > 0) {
				$sql .= " paysite_id = $source_id" ;
				$sql_and = true;
			}
			if ($niche && in_array(strtolower($niche), array('gay','straight','shemale'))) {
				$sql .= $sql_and ? " and " : " ";
				$sql .= "paysite_niche = '{$niche}'" ;
				$sql_and = true;
			}
			if ((int)$category > 0) {
				$sql .= $sql_and ? " and " : " ";
				$sql .= " paysite_category = $category" ;
				$sql_and = true;
			}
				if ($legal_links) {
					$sql .= $sql_and ? " and " : " ";
					$sql .= ($legal_links > 0) ? " legal_link != ''" : " legal_link = ''" ;
					$sql_and = true;
				}
				if ((int)$affiliate_program_id > 0) {
					$sql .= $sql_and ? " and " : " ";
					$sql .= " paysites.affiliate_program_id = " . (int)$affiliate_program_id;
					$sql_and = true;
				}
				
			}
		$q_result = $db->query($sql);

		if ($q_result) {
			while ($row = $q_result->fetch_assoc()) {
				$source_info[$row['paysite_id']]['id'] 					= $row['paysite_id'];
		        $source_info[$row['paysite_id']]['name'] 				= $row['paysite_name'];
					$programRuntimeValue = $row['paysite_affiliate'];
					if (!$programRuntimeValue && $row['linked_affiliate_program_url']) {
						$programRuntimeValue = $row['linked_affiliate_program_url'];
					} elseif (!$programRuntimeValue && $row['linked_affiliate_program_name']) {
						$programRuntimeValue = $row['linked_affiliate_program_name'];
					}
					$source_info[$row['paysite_id']]['program'] 			= $programRuntimeValue;
		        $source_info[$row['paysite_id']]['affiliateProgram'] 	= $row['linked_affiliate_program_name'] ? $row['linked_affiliate_program_name'] : $row['paysite_affiliate']; //дубль, чтобыне преписывать		        
					$source_info[$row['paysite_id']]['affiliateProgramId'] = $row['affiliate_program_id'] ? (int)$row['affiliate_program_id'] : 0;
					$source_info[$row['paysite_id']]['affiliateProgramUrl'] = $row['linked_affiliate_program_url'];
					$source_info[$row['paysite_id']]['affiliateProgramDescription'] = $row['linked_affiliate_program_description'];
					$source_info[$row['paysite_id']]['affiliateProgramLegacy'] = $row['paysite_affiliate'];
				$source_info[$row['paysite_id']]['link'] 				= $row['paysite_link'];
				$source_info[$row['paysite_id']]['legal_link'] 			= $row['legal_link'];
		        $source_info[$row['paysite_id']]['folder'] 				= $row['paysite_folder'];
		        $source_info[$row['paysite_id']]['info'] 				= $row['paysite_info'];
		        $source_info[$row['paysite_id']]['review'] 				= $row['paysite_review'];
		        $source_info[$row['paysite_id']]['paysiteReview'] 		= $row['paysite_review']; //дубль, чтобыне преписывать		        
		        
		        $source_info[$row['paysite_id']]['trial_length'] 		= $row['paysite_trial_length'];
		        $source_info[$row['paysite_id']]['trial_price'] 		= $row['paysite_trial_price'];
		        $source_info[$row['paysite_id']]['month_price'] 		= $row['paysite_month_price'];
		        $source_info[$row['paysite_id']]['clickhere_text'] 		= $row['paysite_clickhere_text'];
  				$source_info[$row['paysite_id']]['clickHereText'] 		= $row['paysite_clickhere_text'];  //дубль, чтобыне преписывать	

		        $source_info[$row['paysite_id']]['rating'] 				= $row['paysite_rating'];
		        $source_info[$row['paysite_id']]['niche'] 				= $row['paysite_niche'];
		        $source_info[$row['paysite_id']]['category'] 			= $row['paysite_category'];
		        $source_info[$row['paysite_id']]['hosted_flag'] 		= $row['hosted_flag'];
		        $source_info[$row['paysite_id']]['bitrate'] 			= $row['max_bitrate'];
				$source_info[$row['paysite_id']]['cropProfile'] 		= $row['crop_profile_id'];
				$source_info[$row['paysite_id']]['trialLength'] 		= $row['paysite_trial_length'];
				$source_info[$row['paysite_id']]['trialPrice'] 			= $row['paysite_trial_price'];
				$source_info[$row['paysite_id']]['fullPrice'] 			= $row['paysite_month_price'];	
				$source_info[$row['paysite_id']]['paysiteRating'] 		= $row['paysite_rating'];
				$source_info[$row['paysite_id']]['hosted'] 				= $row['hosted_flag'];
				$source_info[$row['paysite_id']]['bitrate'] 			= $row['max_bitrate'];
				$source_info[$row['paysite_id']]['update_type'] 		= $row['update_type'];
				$source_info[$row['paysite_id']]['paysite_update_page'] = $row['paysite_update_page'];
				$source_info[$row['paysite_id']]['video_update_page'] 	= $row['paysite_update_page_video'] ? $row['paysite_update_page_video'] : false;
				$source_info[$row['paysite_id']]['update_type_video'] 	= $row['update_type_video'];			
				$source_info[$row['paysite_id']]['single_update_page'] 	= $row['single_update_page'];
				$source_info[$row['paysite_id']]['update_page_md5'] 	= $row['update_page_md5'];
				$source_info[$row['paysite_id']]['set_cropped'] 		= $row['set_cropped'];
				$source_info[$row['paysite_id']]['use_original_ids'] 	= $row['use_original_ids'];
				$source_info[$row['paysite_id']]['lastUpdate'] 			= ($row['last_update'] == '0000-00-00') ? $row['last_update'] : "Никогда";
				$source_info[$row['paysite_id']]['crop'] 				= $this->getCropProfile($row['crop_profile_id']);
		    }
    	}
		return $source_info;		
	}


	public function getCropProfile($id) {
		$id = intval($id);

		$sql = "SELECT * FROM crop_profiles WHERE profile_id = ".$id.";";		
		
		$db = DB::get();
		$q_result = $db->query($sql);

		if($row = $q_result->fetch_assoc()) {
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
		
		return false;
	}	


	public function getAllSourcesShort() {
		$result = false;

			$db = DB::get();
			if($db) {
					$sql = "SELECT paysite_id, paysite_name, paysite_affiliate FROM  paysites";
					$stmt = $db->prepare($sql);
					if($stmt) {
							if($stmt->execute()){
								$paysite_id =false; $paysite_name = false; $paysite_affiliate = false;
								$stmt->bind_result($paysite_id, $paysite_name, $paysite_affiliate);
								while($stmt->fetch()) {
									$result[$paysite_id] = compact('paysite_id', 'paysite_name', 'paysite_affiliate');	
								}
							} else {
								$log = new Logger(__METHOD__.": No execute '".$stmt->error."'", true);
							}
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__.": No STMT '".$db->error."'", true);
					}
			} else {
					$log = new Logger(__METHOD__.": No DB connect", true);
			}

		return $result;
	}



	function sourcesCount() {
		$result = false;
		if($db = DB::get()) {
			$sql = "select count(paysite_id) from `paysites`";
		}
		$q_result = $db->query($sql);
		$row = $q_result->fetch_assoc();
		if ($row) {
			$result = $row['count(paysite_id)'];
		}
		return $result;
	}

	public function addCropProfile($cropElements) { // завязано на lib/paysites.php

		if ($cropElements['IM'] == "") {
			$sql = "INSERT INTO crop_profiles (crop_profile_name, crop_quality, cut_top, cut_bottom, cut_left, cut_right)
			VALUE ('{$cropElements['name']}','{$cropElements['quality']}','{$cropElements['top']}','{$cropElements['bottom']}','{$cropElements['left']}','{$cropElements['right']}')";
		} else {
			$sql = "INSERT INTO crop_profiles (crop_profile_name, IM_string, crop_quality, cut_top, cut_bottom, cut_left, cut_right)
			VALUE ('{$cropElements['name']}', '{$cropElements['IM']}','{$cropElements['quality']}','{$cropElements['top']}','{$cropElements['bottom']}','{$cropElements['left']}','{$cropElements['right']}')";
		}

		$db = DB::get();
		$db->query($sql);

		return $db->insert_id;
	}	

		function addSource($name, $affiliate, $niche, $category, $link, $crop, $hosted, $info = "", $paysiteReview = "", $trialLength = 0, $trialPrice = 0, $fullPrice = 0, $clickHereText = "", $paysiteRating = 0, $paysite_update_page = "", $update_type = "",$paysite_update_page_video = "",$update_type_video = "",$single_update_page = "", $set_cropped = 0, $bitrate = false, $use_original_ids = 0, $legal_link = '', $affiliate_program_id = null) {
		return $this->addPaysite($name, $affiliate, $niche, $category, $link, $crop, $hosted, $info, $paysiteReview, $trialLength, $trialPrice, $fullPrice, $clickHereText, $paysiteRating, $paysite_update_page, $update_type, $paysite_update_page_video, $update_type_video, $single_update_page, $set_cropped, $bitrate, $use_original_ids, $legal_link, $affiliate_program_id);
	}
	function addPaysite($name, $affiliate, $niche, $category, $link, $crop, $hosted, $info = "", $paysiteReview = "", $trialLength = 0, $trialPrice = 0, $fullPrice = 0, $clickHereText = "", $paysiteRating = 0, $paysite_update_page = "", $update_type = "",$paysite_update_page_video = "",$update_type_video = "",$single_update_page = "", $set_cropped = 0, $bitrate = false, $use_original_ids = false, $legal_link= '', $affiliate_program_id = null) {

		$db = DB::get();

		$result = false;
		$trialLength = (int)$trialLength;
		$trialPrice = (float)$trialPrice;
		$fullPrice = (float)$fullPrice;
		$paysiteRating = (float)$paysiteRating;
		$set_cropped = (int)$set_cropped;
		
		$folder = strtolower($name);
		$folder = trim(substr($folder,0,70));
		$folder = preg_replace ("/[^a-z0-9\s]/","",$folder);
		$folder = preg_replace ("/\s+/", "", $folder);
		$folder = str_replace (" ", "", $folder);
	
			$affiliate_program_id = $this->normalizeAffiliateProgramId($affiliate_program_id);
			$affiliate = $this->resolveAffiliateString($affiliate, $affiliate_program_id);
			$name 			= $db->escape_string($name);
			$affiliate 		= $db->escape_string($affiliate);
		$link 			= $db->escape_string($link);
		$legal_link		= $db->escape_string($legal_link);
		$info 			= $db->escape_string($info);
		$paysiteReview 	= $db->escape_string($paysiteReview);
		$clickHereText 	= $db->escape_string($clickHereText);

		$single_update_page = $single_update_page  ? 1 : 0;
		$use_original_ids = $use_original_ids ? 1 : 0;
		$bitrate = ((int)$bitrate < 1200) ? 1200 : (int)$bitrate;

		$update_page_md5 = "X";
		$update_page_video_md5 = "X";
		$updates_checked_on = 0;
		$video_updates_checked_on = 0;
		$last_update_page_check = 0;

			$sql =  "INSERT INTO paysites
					( paysite_name,paysite_affiliate,affiliate_program_id,paysite_link,paysite_folder,paysite_info,paysite_niche,paysite_category, crop_profile_id,hosted_flag,paysite_review, paysite_trial_length ,paysite_trial_price,paysite_month_price,paysite_clickhere_text,paysite_rating, paysite_update_page,update_type,paysite_update_page_video,update_type_video,single_update_page, set_cropped, max_bitrate, use_original_ids, update_page_md5, update_page_video_md5, updates_checked_on,video_updates_checked_on, last_update_page_check, legal_link )
					VALUES
					('".$name."','".$affiliate."',".($affiliate_program_id ? $affiliate_program_id : "NULL").",'".$link."','".$folder."','".$info."','".$niche."','".$category."', '".$crop."','".$hosted."','".$paysiteReview."', '".$trialLength."', '".$trialPrice."', '".$fullPrice."', '".$clickHereText."', '".$paysiteRating."', '".$paysite_update_page."',
						'".$update_type."','".$paysite_update_page_video."','".$update_type_video."','".$single_update_page."','".$set_cropped."','".$bitrate."', '".$use_original_ids."', '".$update_page_md5."', '".$update_page_video_md5."', '".$updates_checked_on."', '".$video_updates_checked_on."', '".$last_update_page_check."', '".$legal_link."')";
		
		if ($q_result = $db->query($sql)) {
			$result = $db->insert_id;
		}

		return $result;

	}


	function updatePaysite() {

	}

	public function updateSource($name, $affiliate, $niche, $category, $link, $crop, $hosted, $info, $paysiteReview, $trialLength, $trialPrice,
									 $fullPrice, $clickHereText, $paysiteRating, $id = false, $paysite_update_page = false, $update_type = "",$paysite_update_page_video = "",
									 $update_type_video = "",$single_update_page = "", $set_cropped = 0, $bitrate = false, $use_original_ids = 0, $legal_link = '', $affiliate_program_id = null) {

		$result = false;

		$trialLength = (int)$trialLength;
		$trialPrice = (float)$trialPrice;
		$fullPrice = (float)$fullPrice;
		$paysiteRating = (float)$paysiteRating;
		$single_update_page = $single_update_page  ? 1 : 0;
		$use_original_ids = $use_original_ids ? 1 : 0;
		$bitrate = ((int)$bitrate < 900) ? 900 : (int)$bitrate;
		$hostedFlagChanged = false;
		$paysiteNicheChanged = false;

		$folder = trim(substr(strtolower($name),0,70));
		$folder = preg_replace ("/[^a-z0-9\s]/","",$folder);
		$folder = preg_replace ("/\s+/", "", $folder);
		$folder = str_replace (" ", "", $folder);

		$db = DB::get();
	
			$affiliate_program_id = $this->normalizeAffiliateProgramId($affiliate_program_id);
			$affiliate = $this->resolveAffiliateString($affiliate, $affiliate_program_id);
			$name 			= $db->escape_string($name);
			$affiliate 		= $db->escape_string($affiliate);
		$link 			= $db->escape_string($link);
		$legal_link		= $db->escape_string($legal_link);
		$info 			= $db->escape_string($info);
		$paysiteReview 	= $db->escape_string($paysiteReview);
		$clickHereText 	= $db->escape_string($clickHereText);

		$id = (int)$id;

		//

		$sql = "SELECT hosted_flag, paysite_niche FROM paysites WHERE paysite_id = ".$id.";";
		
		$q_result = $db->query($sql);
		$row = $q_result->fetch_assoc();

		//

		if ($hosted !== $row['hosted_flag']) {
			$hostedFlagChanged = true;
		}

		if ($niche !== $row['paysite_niche']) {
				$paysiteNicheChanged = true;
		}

		$sql =  "UPDATE paysites
						SET paysite_name = '".$name."',
							paysite_affiliate = '".$affiliate."',
							affiliate_program_id = ".($affiliate_program_id ? $affiliate_program_id : "NULL").",
							paysite_link = '".$link."',
						legal_link = '".$legal_link."',
						paysite_folder = '".$folder."',
						paysite_info = '".$info."',
						paysite_niche = '".$niche."',
						paysite_category = '".$category."', 
						crop_profile_id = '".$crop."', 
						hosted_flag = '".$hosted."',
						paysite_review = '".$paysiteReview."', 
						paysite_trial_length ='".$trialLength."' ,
						paysite_trial_price = '".$trialPrice."',
						paysite_month_price ='".$fullPrice."',
						paysite_clickhere_text = '".$clickHereText."', 
						paysite_rating = '".$paysiteRating."', 
						paysite_update_page = '".$paysite_update_page."',
						update_type = '".$update_type."',
						paysite_update_page_video = '".$paysite_update_page_video."',
						update_type_video = '".$update_type_video."', 
						single_update_page ='".$single_update_page."', 
						set_cropped ='".$set_cropped."', 
						max_bitrate ='".$bitrate."', 
						use_original_ids = '".$use_original_ids."'
					WHERE paysite_id = ".$id.";";

		$stmt = $db->prepare($sql);

		if($stmt->execute()) {
			if ($hostedFlagChanged) { $this->updateGalleriesHostedFlag($id, $hosted); }
			if ($paysiteNicheChanged) { $this->updateGalleriesNiche($id, $niche); }

			return true;
		}

		return false;

		
	}


	private function updateGalleriesHostedFlag($paysite_id, $hosted) {
		$paysite_id = (int)$paysite_id;
		$sql = "UPDATE galleries SET hosted_flag = '".$hosted."' WHERE gal_paysite = '".$paysite_id."'";
		$db = DB::get();
		$db->query($sql);
	}

	private function updateGalleriesNiche($paysite_id, $niche) {
		$paysite_id = (int)$paysite_id;
		$niches_ar = array('Gay','Straight','Shemale');
		if ($paysite_id > 0 && in_array($niche, $niches_ar)) {
			$sql_update = "UPDATE galleries SET gal_niche = '".$niche."' WHERE gal_paysite = '".$paysite_id."'";
			$db = DB::get();
			$db->query($sql_update);
		}
	}


	public function checkUpdates($source_id, $content_type = false, $force_check = false) {
		$source_info = $this->getSource($source_id);
		$result = false;
		$new_page_md5 = false;
		if ($content_type == 'movies' || $content_type == 'video') {
			$page_md5 = $source_info['update_page_video_md5'];
			$update_page = $source_info['video_update_page'];
			$update_type = 'video';
		} else {
			$page_md5 = $source_info['update_page_md5'];
			$update_page = $source_info['update_page'];
			$update_type = 'main';
		}

		if ($source_info && $update_page != "") {
			$paysite_updater = new UpdatesParser();
			$new_page_md5 = $paysite_updater->getPageMD5($update_page);
			$able_to_parse = "gayhoopla\.com|lucaskazan\.com|chaosmen\.com|blueloot\.com|kinkydollars\.com|xxxrewards\.com|dominicford\.com|seancody\.com|englishlads\.com|gunzblazing\.com|buddyprofits\.com|jakepays\.com|manicamoney\.com|helixcash\.com";
			//if ($new_page_md5 != $page_md5 || $force_check) {
			$result = $paysite_updater->getSiteUpdates($update_page, $source_info['program'],$content_type);
			
			//}
		}
		if ($result && $new_page_md5) $this->updateUpdatesMD5($source_id,$new_page_md5,$update_type);
		return $result;
	}

	private function updateUpdatesMD5($source_id, $md5, $md5_type) {
		$result = false;
		$source_id = intval($source_id);
		if ($source_id && $md5) {
			if ($md5_type == 'movies') $md5_type = 'update_page_video_md5';
			else $md5_type = 'update_page_md5';
			$sql = "UPDATE paysites SET ".$md5_type." = '".$md5."' WHERE paysite_id = '".$source_id."'";
			if ( $this->_db->query($sql) === false) {
		       	print 'error inserting: '.$this->_db->errorInfo().'<BR>';
		       	$log = new Logger(__CLASS__."->".__METHOD__.": error inserting: ".$this->_db->errorInfo(), true);
			} else $result = true;
		}
		return $result;
	}

	public function allPaysitesGalsCount ($status = false, $type = false) {
		//$this->_db->debug = true;
		$result = false;
		$sql_where = false;
		$sql = "SELECT gal_paysite, COUNT(gal_id) as PaysiteCount FROM galleries ";
		if (($status && preg_match("#^(zip|newzip|unzipping|unzip_fail|zipupload|screened|zipupload_fail|new|fetching|fetching_fail|fetched|video_screening|screen_fail|video_converting|video_fail|video_converted|thumbing|thumbed|pics_resizing|pics_resized|grab_fail|grabbed|thumbs_fail|thumbs|upload_fail|uploaded|tagged|toregrab|OK|trash|delete|to_merge)$#", $status))
		|| ($type && preg_match("#^(Pics|Movies|embed)$#", $type))) {
			if ($status) {
				$sql_where = true;
				$sql .=" where gal_status = '".$status."' ";
			}
			if ($type) {
				if ($sql_where) $sql .= " and ";
				else $sql .= " where ";
				$sql .=" gal_type = '".$type."' ";
			}
		}
		$sql .= " group by gal_paysite";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($rows) {
				foreach ($rows as $row) {
					$result[$row['gal_paysite']] = $row['PaysiteCount'];
				}
			}
		}
		return $result; 
	}

	public function listSourcesGalsLight($string, $current_paysite_id = false) {
		$status = false;
		$type = false;
		return $this->listSourcesGals($string, $status, $type, $current_paysite_id);
	}

	public function listSourcesGals($string, $status = false, $type = false, $current_paysite_id = false) {

		$result = false;
		$output = "";

		$db = DB::get();

		$sql = "SELECT paysite_name, paysite_id, paysite_affiliate, paysites.affiliate_program_id, affiliate_programs.affiliate_program_name, affiliate_programs.affiliate_program_url,
					paysite_link, paysite_folder, paysite_info, paysite_niche,
					paysite_category, crop_profile_id, paysite_review, paysite_trial_length, paysite_trial_price, paysite_month_price,
					paysite_clickhere_text, paysite_rating, last_update, hosted_flag,
					IM_string, crop_quality, crop_profile_name, cut_top, cut_bottom, cut_left, cut_right,
					tag_name, max_bitrate
					FROM paysites
					LEFT JOIN affiliate_programs
					ON affiliate_programs.affiliate_program_id = paysites.affiliate_program_id
					LEFT JOIN crop_profiles
					ON crop_profiles.profile_id = paysites.crop_profile_id
					LEFT JOIN tags
				ON paysites.paysite_category = tags.tag_id
				ORDER BY paysite_name ASC";
		
		$q_result = $db->query($sql);
	
		if ($q_result) {

			$counter = false;

			if (preg_match("%\#GALLERIES\_COUNT\#%", $string)) {
				$galleries_count = $this->allPaysitesGalsCount($status, $type);
				$counter = true;
			} 

			while ($row = $q_result->fetch_assoc()) {
				$paysiteId = intval($row['paysite_id']);
				$paysite['id'] = $paysiteId;
				$paysite['name'] = $row['paysite_name'];
					$paysite['affiliateProgram'] = $row['affiliate_program_name'] ? $row['affiliate_program_name'] : $row['paysite_affiliate'];
					$paysite['affiliateProgramId'] = $row['affiliate_program_id'] ? (int)$row['affiliate_program_id'] : 0;
					$paysite['affiliateProgramUrl'] = $row['affiliate_program_url'];
				$paysite['link'] = $row['paysite_link'];
				$paysite['folder'] = $row['paysite_folder'];
				$paysite['info'] = $row['paysite_info'];
				$paysite['niche'] = $row['paysite_niche'];
				$paysite['category'] = $row['paysite_category'];
				$paysite['cropProfile'] = $row['crop_profile_id'];
				$paysite ['paysiteReview'] = $row['paysite_review'];
				$paysite['trialLength'] = $row['paysite_trial_length'];
				$paysite['trialPrice'] = $row['paysite_trial_price'];
				$paysite['fullPrice'] = $row['paysite_month_price'];
				$paysite['clickHereText'] = $row['paysite_clickhere_text'];
				$paysite['paysiteRating'] = $row['paysite_rating'];
				$paysite['bitrate'] = $row['max_bitrate'];
				$paysite['crop']['IM'] = $row['IM_string'];
				$paysite['crop']['quality'] = $row['crop_quality'];
				$paysite['crop']['name'] = $row['crop_profile_name'];
				$paysite['crop']['top'] = $row['cut_top'];
				$paysite['crop']['bottom'] = $row['cut_bottom'];
				$paysite['crop']['left'] = $row['cut_left'];
				$paysite['crop']['right'] = $row['cut_right'];

				$paysite['hosted'] = $row['hosted_flag'];
				$paysite['lastUpdate'] = ($row['last_update']  == '0000-00-00') ? "Никогда" : $row['last_update'];

				$category = ($paysite ['category']) ? $row ['tag_name'] : "general";

				$output = str_replace("#PAYSITE#", $paysite['name'], $string);
				$output = str_replace("#PAYSITE_ID#", $paysite['id'], $output);
				$output = str_replace("#PAYSITE_NICHE#", $paysite['niche'], $output);
				$output = str_replace("#PAYSITE_CATEGORY#", $category, $output);
				$output = str_replace("#LAST_UPDATE#", $paysite['lastUpdate'], $output);

				if ($counter) {
					$g_count = isset($galleries_count[$paysiteId]) ? $galleries_count[$paysiteId] : 0;
					$output = str_replace("#GALLERIES_COUNT#", $g_count, $output);
				}
				if ($current_paysite_id && $current_paysite_id == $paysiteId) {
					$output = str_replace("#CHECKED#", "selected", $output);
				} else {
					$output = str_replace("#CHECKED#", "", $output);
				}

				$output .= "\n";

				echo $output;
			}
			
		} else {
			//шибка исполнения мускула
		}
	}

	function galsCount($id) {
		$result = false;
		$id = intval($id);
		if ($id) {
			$rs = $this->_db->query("select count(gal_id) from `galleries` where gal_paysite = '".$id."'");
			$rows = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($rows) {
				$result = $rows[0]['count(gal_id)'];
			}
		}
		return $result;
	}	

	public function deleteSource($id) {
		$id = intval($id);
		if ($id) {
			$gals_count = $this->galsCount($id);
			if ($gals_count) return false;
			else {
				$sql = "delete from paysites where paysite_id = '".$id."'";	
				$rs = $this->_db->query($sql);			
				if ($rs) return true;
			}
		}
		return false;
	}

	function formattedListing ($format, $niches = false) {
		$result = false;
		if ($format) {
			$source_id = false;
			$tags = $this->getAllSources($source_id, $niches);

			foreach ($tags as $id => $tag) {
				$desc = strtolower($tag['name']);
				$desc = trim($desc);
				$desc = preg_replace ("/[^a-z0-9\s]/","",$desc);
				$desc = preg_replace ("/\s+/", " ", $desc);
				$tagUrlName = str_replace (" ", "-", $desc); // тоже самое в informator.php AddGalleryId()
				$tag['name'] .= " | ". preg_replace("#\s#", "",  $tag['name']). " | ". strtolower(preg_replace("#\s#", "",  $tag['name'])).".com";
				$result_p = preg_replace('/\#TAG_ID\#/', $tag['id'], $format);
				$result_p = preg_replace('/\#TAG_NAME\#/', $tag['name'], $result_p);
				$result .= preg_replace('/\#TAG_URL_NAME\#/', $tagUrlName, $result_p);
			}
		}
		return $result;
	}

	function paysiteUpdated(int $source_id) {
		$result = false;

		if ($source_id > 0) {
			$sql = "UPDATE paysites SET last_update = CURDATE() WHERE paysite_id = '".$source_id."'";
			if ( $this->_db->query($sql) === false) {
		       	print 'error inserting: '.$this->_db->errorInfo().'<BR>';
		       	$log = new Logger(__CLASS__."->".__METHOD__.": error inserting: ".$this->_db->errorInfo(), true);
			} else $result = true;
		}
		return $result;		
	}

}
