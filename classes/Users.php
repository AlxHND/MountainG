<?php
class Users {

	const SESSION_USER_ID = 'auth_user_id';
	const SESSION_USER_NAME = 'auth_user_name';

	private $_db, $db;

	private $id, $user_name, $user_pass, $user_allowed_ip, $allowed_operations, $add_models, $user_added, $user_last_login, $language, $current_gallery, $work_type, $change_time, $user_ip;

	private $authorized, $is_admin, $is_writer, $allowed_tags, $allowed_models, $allowed_crop, $allowed_uploads;

	function __construct(PDO $db_connect) {
		$this->_db 				= $db_connect;
		$this->db 				= $db_connect;

		$this->user_name  		= $this->getRequestUserName();

		$this->user_ip			= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false; // current ip

		$this->id 				= false;
		$this->user_pass 		= false;
		$this->authorized		= false;
		$this->is_admin			= false;
		$this->is_writer		= false;
		$this->allowed_tags		= false;
		$this->allowed_models	= false;
		$this->allowed_crop		= false;

		$user_data = false;

		if ($sessionUserId = $this->getSessionUserId()) {
			$user_data = $this->getUserById($sessionUserId);
		} elseif ($this->user_name) {
			$user_data = $this->getUserByName($this->user_name);
		}

		if(is_array($user_data)) {
			$this->initializeUser($user_data);

			if (!$this->authorized) {
				$this->clearSessionUser();
			}

		} else {
			$this->clearSessionUser();
		}
	}

	private function getRequestUserName() {
		return $this->getSessionUserName();
	}

	private function getSessionUserId() {
		return isset($_SESSION[self::SESSION_USER_ID]) ? (int)$_SESSION[self::SESSION_USER_ID] : 0;
	}

	private function getSessionUserName() {
		return isset($_SESSION[self::SESSION_USER_NAME]) ? trim((string)$_SESSION[self::SESSION_USER_NAME]) : false;
	}

	private function setSessionUser(Array $user) {
		$_SESSION[self::SESSION_USER_ID] = (int)$user['id'];
		$_SESSION[self::SESSION_USER_NAME] = $user['name'];
	}

	private function clearSessionUser() {
		unset($_SESSION[self::SESSION_USER_ID], $_SESSION[self::SESSION_USER_NAME]);
	}

	private function verifyPassword($password, $storedHash) {
		$password = (string)$password;
		$storedHash = (string)$storedHash;

		if ($password === '' || $storedHash === '') {
			return false;
		}

		if (function_exists('password_verify') && password_verify($password, $storedHash)) {
			return true;
		}

		$calculatedHash = crypt($password, $storedHash);

		if (!is_string($calculatedHash) || $calculatedHash === '') {
			return false;
		}

		return function_exists('hash_equals') ? hash_equals($storedHash, $calculatedHash) : ($storedHash === $calculatedHash);
	}

	private function storedHashNeedsUpgrade($storedHash) {
		$storedHash = (string)$storedHash;

		if ($storedHash === '' || !function_exists('password_get_info') || !function_exists('password_needs_rehash')) {
			return false;
		}

		$info = password_get_info($storedHash);

		if (empty($info['algo'])) {
			return true;
		}

		return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
	}

	private function updateStoredPasswordHash($userId, $password) {
		$userId = (int)$userId;
		$password = (string)$password;

		if (!$userId || $password === '' || !$this->_db) {
			return false;
		}

		$newHash = $this->cryptedPass($password);
		if ($newHash === '') {
			return false;
		}

		$stmt = $this->_db->prepare("UPDATE scr_users_list SET user_pass = :user_pass WHERE id = :user_id");

		return $stmt ? $stmt->execute(array(
			':user_pass' => $newHash,
			':user_id' => $userId
		)) : false;
	}

	private function updateLastLogin($userId) {
		$userId = (int)$userId;

		if (!$userId || !$this->_db) {
			return false;
		}

		$stmt = $this->_db->prepare("UPDATE scr_users_list SET user_last_login = :user_last_login WHERE id = :user_id");

		return $stmt ? $stmt->execute(array(
			':user_last_login' => time(),
			':user_id' => $userId
		)) : false;
	}

	private function resetUser() {
		$this->id 					= false;
		$this->user_name 			= false;
		$this->user_pass 			= false;
		$this->authorized			= false;
		$this->user_allowed_ip 		= false;
		$this->allowed_operations	= false;
		$this->add_models 			= false;
		$this->user_added 			= false;
		$this->user_last_login 		= false;
		$this->language 			= false;
		$this->current_gallery 		= false;
		$this->work_type 			= false;
		$this->change_time 			= false;
		$this->is_admin				= false;
		$this->is_writer			= false;
		$this->allowed_tags			= false;
		$this->allowed_models		= false;
		$this->allowed_crop			= false;
	}

	private function normalizeIpList($ipList) {
		$result = array();
		$parts = is_array($ipList) ? $ipList : preg_split('/[\s,;|]+/', (string)$ipList);

		if (!$parts) {
			return $result;
		}

		foreach ($parts as $ip) {
			$ip = trim((string)$ip);
			if ($ip === '') {
				continue;
			}

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !in_array($ip, $result, true)) {
				$result[] = $ip;
			}
		}

		return $result;
	}

	private function ipToStorageValue($ip) {
		$ips = $this->normalizeIpList($ip);

		if (!$ips) {
			return false;
		}

		return ip2long($ips[0]);
	}

	private function storageValueToIp($value) {
		if ($value === null || $value === '' || $value === false) {
			return false;
		}

		$ip = long2ip((int)$value);

		return $ip ? $ip : false;
	}

	private function allowedIpsTableExists() {
		static $exists = null;

		if ($exists !== null) {
			return $exists;
		}

		$exists = false;

		if ($this->_db) {
			try {
				$stmt = $this->_db->query("SHOW TABLES LIKE 'scr_users_allowed_ips'");
				$exists = ($stmt && $stmt->fetch()) ? true : false;
			} catch (Exception $e) {
				$exists = false;
			}
		}

		return $exists;
	}

	private function getAllowedIpsForUser($userId, $primaryIp = false) {
		$userId = intval($userId);
		$result = array();

		foreach ($this->normalizeIpList($primaryIp) as $ip) {
			$result[] = $ip;
		}

		if (!$userId || !$this->allowedIpsTableExists()) {
			return $result;
		}

		try {
			$stmt = $this->_db->prepare("SELECT user_ip FROM scr_users_allowed_ips WHERE user_id = :user_id ORDER BY id ASC");
			if ($stmt && $stmt->execute(array(':user_id' => $userId))) {
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$ip = $this->storageValueToIp($row['user_ip']);
					if ($ip && !in_array($ip, $result, true)) {
						$result[] = $ip;
					}
				}
			}
		} catch (Exception $e) {
			return $result;
		}

		return $result;
	}

	private function replaceAllowedIps($userId, Array $ips) {
		$userId = intval($userId);

		if (!$userId || !$this->allowedIpsTableExists()) {
			return false;
		}

		try {
			$stmt = $this->_db->prepare("DELETE FROM scr_users_allowed_ips WHERE user_id = :user_id");
			$stmt->execute(array(':user_id' => $userId));

			$stmt = $this->_db->prepare("INSERT IGNORE INTO scr_users_allowed_ips (user_id, user_ip, added) VALUES (:user_id, :user_ip, :added)");
			foreach ($ips as $ip) {
				$userIp = $this->ipToStorageValue($ip);
				if ($userIp !== false) {
					$stmt->execute(array(
						':user_id' => $userId,
						':user_ip' => $userIp,
						':added' => time()
					));
				}
			}
		} catch (Exception $e) {
			$log = new Logger(__CLASS__."->".__METHOD__.": Ошибка обновления разрешенных IP: ".$e->getMessage(), true);
			return false;
		}

		return true;
	}

	private function getAlwaysAllowedIps() {
		$alwaysAllowedIp = defined('ALWAYS_ALLOWED_IP') ? ALWAYS_ALLOWED_IP : '';
		$allowedIps = array('127.0.0.1');

		if (!empty($alwaysAllowedIp)) {
			foreach ($this->normalizeIpList($alwaysAllowedIp) as $ip) {
				$allowedIps[] = $ip;
			}
		}

		return array_values(array_unique($allowedIps));
	}

	private function getAllowedIpsForUserData(Array $user_array) {
		$allowedIps = $this->getAlwaysAllowedIps();
		$userIpList = isset($user_array['ip']) ? $user_array['ip'] : false;

		if ($userIpList) {
			foreach ($this->normalizeIpList($userIpList) as $ip) {
				$allowedIps[] = $ip;
			}
		}

		return array_values(array_unique($allowedIps));
	}

	private function isRequestIpAllowedForUserData(Array $user_array) {
		$allowedIps = $this->getAllowedIpsForUserData($user_array);

		return $this->user_ip && in_array($this->user_ip, $allowedIps, true);
	}

	private function detectStoredHashType($storedHash) {
		$storedHash = (string)$storedHash;

		if ($storedHash === '') {
			return 'empty';
		}

		if (function_exists('password_get_info')) {
			$info = password_get_info($storedHash);
			if (!empty($info['algoName']) && $info['algoName'] !== 'unknown') {
				return 'password_hash:' . $info['algoName'];
			}
		}

		if (preg_match('/^\$1\$/', $storedHash)) {
			return 'crypt-md5';
		}

		if (preg_match('/^\$2[aby]\$/', $storedHash)) {
			return 'crypt-bcrypt';
		}

		if (preg_match('/^\$5\$/', $storedHash)) {
			return 'crypt-sha256';
		}

		if (preg_match('/^\$6\$/', $storedHash)) {
			return 'crypt-sha512';
		}

		return 'legacy-crypt-or-unknown';
	}

	private function initializeUser(Array $user_array) {
		$allowedIps = $this->getAlwaysAllowedIps();

		$this->resetUser();

		$this->id 					= isset($user_array['id']) ? $user_array['id'] : false;
		$this->user_name 			= isset($user_array['name']) ? $user_array['name'] : false;
		$this->user_pass 			= isset($user_array['pass']) ? $user_array['pass'] : false;
		$this->user_allowed_ip 		= isset($user_array['ip']) ? $user_array['ip'] : false;
		$this->allowed_operations 	= isset($user_array['operations']) ? $user_array['operations']  : false;
		$this->add_models 			= isset($user_array['add_models']) ? $user_array['add_models']  : false;
		$this->user_added 			= isset($user_array['added']) ? $user_array['added'] : false;
		$this->user_last_login 		= isset($user_array['last_login']) ? $user_array['last_login'] : false;
		$this->language 			= isset($user_array['language']) ? $user_array['language'] : false;
		$this->current_gallery 		= isset($user_array['current_gallery']) ? $user_array['current_gallery'] : false;
		$this->work_type 			= isset($user_array['work_type']) ? $user_array['work_type']: false;
		$this->change_time 			= isset($user_array['change_time']) ? $user_array['change_time']: false;

		if($this->user_allowed_ip) {
			foreach ($this->normalizeIpList($this->user_allowed_ip) as $ip) {
				$allowedIps[] = $ip;
			}
		}

		$allowedIps = array_values(array_unique($allowedIps));

		if($this->user_ip && in_array($this->user_ip, $allowedIps, true)) { // in_array, для списка айпишников
			
			$this->authorized = true;

			switch ($this->allowed_operations) {
				case 'admin':
					$this->is_admin 		= true;
					$this->is_writer		= true;
					$this->allowed_tags 	= true;
					$this->allowed_models 	= true;
					$this->allowed_crop 	= true;
					break;

				case 'tags':
					$this->is_writer		= true;
					$this->allowed_tags 	= true;
					$this->allowed_models 	= true;
					break;

				case 'crop':
					$this->allowed_crop 	= true;
					break;

				case 'croptags':
					$this->is_writer		= true;
					$this->allowed_tags 	= true;
					$this->allowed_models 	= true;
					$this->allowed_crop 	= true;
					break;

				case 'descs':
					$this->is_writer		= true;
					break;
			}

		}

		// var_dump($this);
	}

	private function userDataToArray(Array $user) {
		$primaryIp = $this->storageValueToIp($user['user_allowed_ip']);
		$allowedIps = $this->getAllowedIpsForUser($user['id'], $primaryIp);

		$result['id'] 				= $user['id'];
		$result['name'] 			= $user['user_name'];
		$result['pass'] 			= $user['user_pass'];
		$result['ip'] 				= implode(", ", $allowedIps);
		$result['primary_ip'] 		= $primaryIp;
		$result['operations'] 		= $user['allowed_operations'];
		$result['add_models'] 		= $user['add_models'];
		$result['added'] 			= $user['user_added'];
		$result['last_login'] 		= $user['user_last_login'];
		$result['language'] 		= $user['language'];
		$result['current_gallery']	= false;
		$result['work_type'] 		= false;
		$result['change_time'] 		= false;

		if ($working_gallery = $this->workingGalleriesForUser($user['id'])) {
			$result['current_gallery'] 	= $working_gallery['gal_id'];
		 	$result['work_type'] 		= $working_gallery['work_type'];
		 	$result['change_time'] 		= $working_gallery['change_time'];
		}

		return $result;
	}


	private function cryptedPass ($pass) {
		$pass = (string)$pass;

		if ($pass === '') {
			return '';
		}

		if (function_exists('password_hash')) {
			$hash = password_hash($pass, PASSWORD_DEFAULT);
			if (is_string($hash) && $hash !== '') {
				return $hash;
			}
		}

		return crypt($pass, base64_encode($pass));
	}

	/*

	/	Public

	*/

	public function getUserByName($name) {
		$sql = "SELECT * FROM scr_users_list WHERE user_name = :name";
		$stmt = $this->db->prepare($sql);
		if($stmt->execute(array(':name' => $name)) && $data = $stmt->fetch()) {
			return $this->userDataToArray($data);
		} else {
			return false;
		}

	}

	public function getUserById($id) {
		$id = (int)$id;

		if ($id <= 0) {
			return false;
		}

		$sql = "SELECT * FROM scr_users_list WHERE id = :id";
		$stmt = $this->db->prepare($sql);
		if($stmt->execute(array(':id' => $id)) && $data = $stmt->fetch()) {
			return $this->userDataToArray($data);
		}

		return false;
	}

	public function login($name, $password) {
		$name = trim((string)$name);
		$password = (string)$password;

		if ($name === '' || $password === '') {
			return false;
		}

		$user = $this->getUserByName($name);

		if (!$user || !$this->verifyPassword($password, $user['pass'])) {
			return false;
		}

		if ($this->storedHashNeedsUpgrade($user['pass'])) {
			$this->updateStoredPasswordHash($user['id'], $password);
			$refreshedUser = $this->getUserById($user['id']);
			if ($refreshedUser) {
				$user = $refreshedUser;
			}
		}

		$this->initializeUser($user);

		if (!$this->isAuthorized()) {
			$this->clearSessionUser();
			return false;
		}

		if (session_status() === PHP_SESSION_ACTIVE) {
			session_regenerate_id(true);
		}

		$this->setSessionUser($user);
		$this->updateLastLogin($user['id']);

		return $this->isAuthorized();
	}

	public function logout() {
		$this->clearSessionUser();

		if (session_status() === PHP_SESSION_ACTIVE) {
			session_regenerate_id(true);
		}

		$this->resetUser();
		$this->is_admin = false;
		$this->is_writer = false;
		$this->allowed_tags = false;
		$this->allowed_models = false;
		$this->allowed_crop = false;

		return true;
	}

	public function debugLoginAttempt($name, $password) {
		$name = trim((string)$name);
		$password = (string)$password;

		$result = array(
			'requested_name' => $name,
			'current_ip' => $this->user_ip ? $this->user_ip : '',
			'user_found' => false,
			'password_ok' => false,
			'ip_allowed' => false,
			'login_would_succeed' => false,
			'hash_type' => '',
			'allowed_ips' => array(),
			'operations' => '',
			'user_id' => 0,
			'hash_needs_upgrade' => false,
			'message' => ''
		);

		if ($name === '' || $password === '') {
			$result['message'] = 'Нужно указать логин и пароль.';
			return $result;
		}

		$user = $this->getUserByName($name);
		if (!$user) {
			$result['message'] = 'Пользователь не найден в scr_users_list.';
			return $result;
		}

		$result['user_found'] = true;
		$result['user_id'] = isset($user['id']) ? (int)$user['id'] : 0;
		$result['operations'] = isset($user['operations']) ? $user['operations'] : '';
		$result['allowed_ips'] = $this->getAllowedIpsForUserData($user);
		$result['hash_type'] = $this->detectStoredHashType(isset($user['pass']) ? $user['pass'] : '');
		$result['password_ok'] = $this->verifyPassword($password, isset($user['pass']) ? $user['pass'] : '');
		$result['ip_allowed'] = $this->isRequestIpAllowedForUserData($user);
		$result['hash_needs_upgrade'] = $result['password_ok'] ? $this->storedHashNeedsUpgrade(isset($user['pass']) ? $user['pass'] : '') : false;
		$result['login_would_succeed'] = ($result['password_ok'] && $result['ip_allowed']);

		if (!$result['password_ok']) {
			$result['message'] = 'Пароль не совпадает с хэшем в базе.';
		} elseif (!$result['ip_allowed']) {
			$result['message'] = 'Пароль совпал, но текущий IP не входит в разрешенные.';
		} else {
			$result['message'] = 'Логин через форму должен сработать.';
		}

		return $result;
	}

	function getUsers (int $id = 0, $name = false) {
		$result = false;
		$userAddition = "";

		if ($id > 0) {
			$userAddition = " WHERE id = '".$id."'";
		} elseif ($name) {
			$name = preg_replace ('/[^a-z]/im', "", $name);
			$userAddition = " WHERE user_name = '".$name."'";
		}

		$sql = "SELECT * FROM scr_users_list".$userAddition;
		$rows = $this->_db->query($sql);
		if ($rows) {
			foreach ($rows as $user) {
				$result[$user['id']] = $this->userDataToArray($user);
			}
		}
		return $result;
	}


	function getUserOperationsById (int $id) {
		$result = false;
		if ($id > 0) {
			$user = $this->getUsers($id);
			if (isset($user[$id])) {
				$result = $user[$id]['operations'];
			}
		}
		return $result;
	}

	// FIx it! >>
	function getUserOperationsByName ($name, $ip) {
		$result = false;
		if ($name) {
			$user = $this->getUsers(false, $name);
			$ips = $this->normalizeIpList($ip);
			if ($user) {
				foreach($user as $us){
					$userIps = $this->normalizeIpList($us['ip']);
					if (count(array_intersect($ips, $userIps))) {
						$result = $us['operations'];
					}
				}
			}
		}
		return $result;
	}

	function getUserLanguage (int $id) {
		$result = false;
		$id = intval($id);
		if ($id > 0) {
			$user = $this->getUsers($id);
			$result = (isset($user[$id])) ? $user[$id]['language'] : false;
		}
		return $result;
	}


	public function todayTaggedGals(int $user_id) {
		$result = 0;
		$user_id = intval($user_id);
		
		if ($user_id && $this->_db) {
			$sql = "SELECT count(DISTINCT gal_id) AS gals_tagged_today
					FROM scr_gallery_update_history
					WHERE change_type = 'gallery_approved' 
						AND user_id = '".$user_id."' 
						AND (updated BETWEEN ".strtotime(date("Y/m/d"))." AND ".strtotime(date("Y/m/d 23:59:59")).")";

			if ($rows = $this->_db->query($sql)) {
				$result = $rows->fetch(PDO::FETCH_OBJ)->gals_tagged_today;
			}

		}

		return $result;

	}

	/*
	/
	/	Exclude paysites
	/
	*/

	public function addExcludePaysite(int $user_id, int $paysite_id) {
		$result = false;

		$added_on = time();
		$added_by = 0;

		if($paysite_id <= 0 && $user_id <= 0) {
			return false;
		}

		$db = DB::get();
		
		if(!$db) {
			new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No DB connect", true);
			return false;
		}
					
		$sql = "INSERT INTO scr_users_exclude_paysites
				(user_id, paysite_id, added_on, added_by) VALUES (?, ?, ?, ?)";

		$stmt = $db->prepare($sql);

		if(!$stmt->bind_param("iiii", $user_id, $paysite_id, $added_on, $added_by)) {
			$log = new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No BIND '".$stmt->error."'", true);	
			return false;
		}
				
		if(!$stmt->execute()){
			new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No execute '".$stmt->error."'", true);	
			return false;
		}
			
		$result = $stmt->insert_id;	
		$stmt->close();

		return $result;
		
	}

	public function deleteExcludedPaysite($user_id, $paysite_id) {
		$result = false;

		$added_on = time();
		$added_by = 0;
		$user_id = (int)$user_id;
		$paysite_id = (int)$paysite_id;

		if($paysite_id > 0 && $user_id > 0) {
			$db = DB::get();
			if($db) {
					$sql = "DELETE FROM scr_users_exclude_paysites
							WHERE user_id = ? AND paysite_id = ?";
					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->bind_param("ii", $user_id, $paysite_id)) {
							if($stmt->execute()){
								$result = $stmt->affected_rows;	
							} else {
								$log = new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No execute '".$stmt->error."'", true);	
							}
						} else {
							$log = new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No BIND '".$stmt->error."'", true);	
						}
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No STMT '".$db->error."'", true);
					}
			} else {
					$log = new Logger(__METHOD__.": UID#".$user_id.", Paysite Id: '".$paysite_id."', No DB connect", true);
			}
		}
		return $result;
	}

	public function getExcludedPaysites($user_id) {
		$result = array();

		$user_id = (int)$user_id;

		if($user_id > 0) {
			$db = DB::get();
			if($db) {
					$sql = "SELECT paysite_id FROM  scr_users_exclude_paysites
							WHERE user_id = ?";
					$stmt = $db->prepare($sql);
					if($stmt) {
						if($stmt->bind_param("i", $user_id)) {
							if($stmt->execute()){
								$p_id = 0;
								$stmt->bind_result($p_id);
								while($stmt->fetch()) {
									$result[] = $p_id;	
								}
							} else {
								$log = new Logger(__METHOD__.": UID#".$user_id.", No execute '".$stmt->error."'", true);	
							}
						} else {
							$log = new Logger(__METHOD__.": UID#".$user_id.", No BIND '".$stmt->error."'", true);	
						}
						$stmt->close();
					} else {
						$log = new Logger(__METHOD__.": UID#".$user_id.", No STMT '".$db->error."'", true);
					}
			} else {
					$log = new Logger(__METHOD__.": UID#".$user_id.", No DB connect", true);
			}
		}
		return $result;
	}	


	private function userUpdateHistory (int $userId, $history_type, int $galId, int $itemId = 0) {

		$result = false;
		$start = get_time();

		if($userId && $galId 
		&& preg_match("/(tag_added|tag_removed|title_updated|thumb_removed|gallery_removed|model_added|model_removed|gallery_approved|rss_set|rss_unset|tag_to_thumb|tag_to_thumb_removed)/", $history_type)) {

			$sql = "INSERT INTO scr_gallery_update_history
					(`gal_id`, `change_type`, `item_id`, `updated`, `user_id`)
					VALUES (";
			$sql .= "'".$galId."',";
			$sql .= "'".$history_type."',";
			$sql .= "'".$itemId."',";
			$sql .= "'".time()."',";
			$sql .= "'".$userId."');";

			if ( $this->_db->query($sql) === false) {
			    print 'error inserting: '.$this->_db->ErrorInfo().'<BR>';
			    $log = new Logger (__CLASS__."->".__METHOD__.": Ошибка добавления в базу данных: ".$this->_db->ErrorInfo(), true);
			} else {
				$result = $this->_db->lastInsertId();
			}	
		}

		$end = get_time();
		$exec_time = $end - $start;

		if($result) $log = new Logger(__METHOD__.": ".$history_type.", execution time: ".$exec_time);

		return $result;
	}



	public function userAddedThumbTag($user_id, $thumb_id, $tag_id) {
		return $this->userUpdateHistory($user_id, 'tag_to_thumb', $thumb_id, $tag_id);
	}
	public function userRemovedThumbTag($user_id, $thumb_id, $tag_id) {
		return $this->userUpdateHistory($user_id, 'tag_to_thumb_removed', $thumb_id, $tag_id);
	}

	public function userUpdatedTitle($user_id, $gal_id) {
		return $this->userUpdateHistory($user_id, 'title_updated', $gal_id, 0);
	}	

	public function userAddedTag($user_id, $gal_id, $tag_id) { // замена внизу
		return $this->userUpdateHistory($user_id, 'tag_added', $gal_id, $tag_id);
	}

	public function userRemovedTag($user_id, $gal_id, $tag_id) {
		return $this->userUpdateHistory($user_id, 'tag_removed', $gal_id, $tag_id);
	}

	public function userRemovedThumb($user_id, $gal_id, $thumb_id) {
		return $this->userUpdateHistory($user_id, 'thumb_removed', $gal_id, $thumb_id);	
	}

	public function userRemovedGallery($user_id, $gal_id) {
		return $this->userUpdateHistory($user_id, 'gallery_removed', $gal_id, 0);
	}

	public function userApprovedGallery($user_id, $gal_id) {
		return $this->userUpdateHistory($user_id, 'gallery_approved', $gal_id, 0);
	}

	public function galleryThumbRssUnset($user_id, $gal_id, $thumb_id) {
		return $this->userUpdateHistory($user_id, 'rss_unset', $gal_id, $thumb_id);
	}	

	public function galleryThumbRssSet($user_id, $gal_id, $thumb_id) {
		return $this->userUpdateHistory($user_id, 'rss_set', $gal_id, $thumb_id);
	}	

	public function checkUserOperations ($name, $pass, $ip, $type) { // удаление
		$result = false;
		$name = preg_replace ('/[^a-zA-Z]/', "", $name);
		$ips = $this->normalizeIpList($ip);
		if ($name && $ips) {
			$user = $this->getUserByName($name);
			if ($user) {
				$userIps = $this->normalizeIpList($user['ip']);
				if ($this->verifyPassword($pass, $user['pass']) && strstr($user['operations'], $type) && count(array_intersect($ips, $userIps)) && isset($user['id'])) {
					$result = $user['id'];
				}
			}			
		}

		return $result;
	}

	function insertUser($name, $pass, $ip, $operations, $add_models = false, $language = 'en') {
		$result = false;
		// var_dump($language);
		$add_models =  ($add_models == 'allowed') ? 'allowed' : 'disallowed';
		$user_added = time();

		if ($this->_db) {
			$name = preg_replace ('/[^a-z]/im', "", $name);
			$pass = $this->cryptedPass($pass);
			$ips = $this->normalizeIpList($ip);
			$primaryIp = $ips ? $this->ipToStorageValue($ips[0]) : false;
			if ($name != "" && $pass != "" && $primaryIp !== false && ($operations == 'admin' || $operations == 'tags' || $operations == 'crop' || $operations == 'croptags' || $operations == 'tags' || $operations == 'descs')) {

				$sql = "insert into scr_users_list
						(user_name, user_pass, user_allowed_ip, allowed_operations, add_models, user_added, language, user_last_login)
						values (:user_name, :user_pass, :user_allowed_ip , :allowed_operations , :add_models , :user_added , :language, :user_last_login);";
			
				$stmt = $this->db->prepare($sql);

				$result = $stmt->execute(array(':user_name' => $name,
									 ':user_pass' => $pass, 
									 ':user_allowed_ip' => $primaryIp,
									 ':allowed_operations' => $operations,
									 ':add_models' => $add_models,
									 ':user_added' => $user_added,
									 ':language' => $language,
									 ':user_last_login' => 0));

				if ($result === false) {
			         print_r($this->_db->errorInfo());
			         $log = new Logger(__CLASS__."->".__METHOD__.": Ошибка добавления в базу данных: ".$this->_db->errorInfo(), true);
				} else {
					$result = array();
					$result['id'] = $this->_db->lastInsertId();
					$result['name'] = $name;
					$result['pass'] = $pass;
					$this->replaceAllowedIps($result['id'], $ips);
				}
			}
		}
		return $result;	
	}


	function updateUser($user_id, $name, $pass, $ip, $operations, $add_models = false, $language = 'en') {
		$result = false;
		if ($add_models && $add_models == 'allowed') $add_models = 'allowed';
		else $add_models = 'disallowed';
		if ($this->_db) {
			$name = preg_replace ('/[^a-z]/im', "", $name);
			$user_id = intval($user_id);
			$ips = $this->normalizeIpList($ip);
			$primaryIp = $ips ? $this->ipToStorageValue($ips[0]) : false;

			if (trim((string)$pass) === '' && $user_id) {
				$currentUser = $this->getUsers($user_id);
				$pass = isset($currentUser[$user_id]['pass']) ? $currentUser[$user_id]['pass'] : '';
			} else {
				$pass = $this->cryptedPass($pass);
			}

			if ($user_id && $name != "" && $pass != "" && $primaryIp !== false && ($operations == 'admin' || $operations == 'tags' || $operations == 'crop' || $operations == 'croptags' || $operations == 'descs')) {
				$sql = "update scr_users_list set
						user_name = :user_name,
						user_pass = :user_pass,
						user_allowed_ip = :user_allowed_ip,
						allowed_operations = :allowed_operations,
						add_models = :add_models,
						language = :language
						where id = :user_id";

				$stmt = $this->_db->prepare($sql);
				if (!$stmt || $stmt->execute(array(
					':user_name' => $name,
					':user_pass' => $pass,
					':user_allowed_ip' => $primaryIp,
					':allowed_operations' => $operations,
					':add_models' => $add_models,
					':language' => $language,
					':user_id' => $user_id
				)) === false) {
			         print_r($this->_db->errorInfo());
			         $log = new Logger (__CLASS__."->".__METHOD__.": Ошибка добавления в базу данных: ".$this->_db->errorInfo(), true);
				} else {
					$this->replaceAllowedIps($user_id, $ips);
					$result = true;
				}
			}
		}
		return $result;	
	}

	private function checkThumbHistory ($thumbId) {
		$thumbId = intval($thumbId);
		$result = false;
		if ($thumbId && $this->_db) {
			$sql = "select image_id from scr_manual_crop_history where image_id = '".$thumbId."'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$result = true;
				}
			}
		}
		return $result;
	}

	function updateCroppedThumb ($thumbId, $gal_id, $x_coord, $y_coord, $width, $height, $thumb_type, $user_id) {
		$thumbId = intval($thumbId);
		$gal_id = intval($gal_id);
		$x_coord = intval($x_coord);
		$y_coord = intval($y_coord);
		$width = intval($width);
		$height = intval($height);
		$user_id = intval($user_id);
		$thumbId = intval($thumbId);
		$result = false;
		if ($thumbId && $this->_db && preg_match('/[vertic|horiz|square]/im', $thumb_type) && $user_id 
		 	&&	($gal_id > 0 && $width > 0 && $height > 0 && $thumb_type)) {
			if ($this->checkThumbHistory($thumbId)) {
				$sql = "update scr_manual_crop_history set
						gal_id = '".$gal_id."',
						x_coord = '".$x_coord."',
						y_coord = '".$y_coord."',
						width = '".$width."',
						height = '".$height."',
						thumb_type = '".$thumb_type."',
						user_id = '".$user_id."',
						updated = '".time()."'
						where image_id = '".$thumbId."';";
			} else {
				$sql = "insert into scr_manual_crop_history
						(gal_id, image_id, x_coord, y_coord, width, height, thumb_type, user_id, updated) 
						value('".$gal_id."', '".$thumbId."', '".$x_coord."', '".$y_coord."', '".$width."', '".$height."', '".$thumb_type."', '".$user_id."', '".time()."')";
			}
			$rs = $this->_db->query($sql);
			if ($rs) $result = true;
		}
		return $result;
	}

	function getModelCropInfo ($gal_id) {
		$result = false;
		$sql = "select * from scr_manual_model_crop_history where model_id = '".$gal_id."'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($row) {
				foreach ($row as $item) {
					$result[$item['image_id']] = $item;
				}
					
			}
		}
		return $result;
	}

	private function checkModelThumbHistory ($thumbId) {
		$thumbId = intval($thumbId);
		$result = false;
		if ($thumbId && $this->_db) {
			$sql = "select image_id from scr_manual_model_crop_history where image_id = '".$thumbId."'";
			$rs = $this->_db->query($sql);
			if ($rs) {
				$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
				if ($row) {
					$result = true;
				}
			}
		}
		return $result;
	}	

	function updateModelCroppedThumb ($thumbId, $model_id, $x_coord, $y_coord, $width, $height, $thumb_type, $user_id) {
		$thumbId = intval($thumbId);
		$model_id = intval($model_id);
		$x_coord = intval($x_coord);
		$y_coord = intval($y_coord);
		$width = intval($width);
		$height = intval($height);
		$user_id = intval($user_id);
		$result = false;
		// $this->_db->debug = true;
		if ($thumbId && $this->_db && preg_match('/^(vertic|horiz|square)$/im', $thumb_type) && $user_id 
		 	&&	($model_id > 0 && $width > 0 && $height > 0 && $thumb_type)) {
			if ($this->checkModelThumbHistory($thumbId)) {
				$sql = "update scr_manual_model_crop_history set
						model_id = '".$model_id."',
						x_coord = '".$x_coord."',
						y_coord = '".$y_coord."',
						width = '".$width."',
						height = '".$height."',
						thumb_type = '".$thumb_type."',
						user_id = '".$user_id."',
						updated = '".time()."'
						where image_id = '".$thumbId."';";
			} else {
				$sql = "insert into scr_manual_model_crop_history
						(model_id, image_id, x_coord, y_coord, width, height, thumb_type, user_id, updated) 
						value('".$model_id."', '".$thumbId."', '".$x_coord."', '".$y_coord."', '".$width."', '".$height."', '".$thumb_type."', '".$user_id."', '".time()."')";
			}

			$rs = $this->_db->query($sql);
			if ($rs) $result = true;
		}
		return $result;
	}

	function  showWorkingTable () {
		$result = array();

		$sql = "select * from scr_working_list";
		$rs = $this->_db->query($sql);

		if ($rs && $row = $rs->fetch()) {
			$result[] = $row;
		}

		return $result;
	}

	function workingGalleriesForUser (int $user_id) {

		$result = array();

		if ($user_id) {
			$sql = "select * from scr_working_list where user_id = '".$user_id."'";
			$rs = $this->db->query($sql);

			if ($rs) {
				$row = $rs->fetch();
				$result = $row ? $row : false;
			}			
		}

		return $result;		
	}

	function currentGalleryUser($gal_id) {
		$gal_id = intval($gal_id);
		$result = false;

		if ($gal_id && $this->_db) {
			$sql = "SELECT user_id FROM scr_working_list WHERE gal_id = '".$gal_id."'";
			if($row = $this->_db->query($sql)) {
				$user = $row->fetch();
				$result = $user ? $user['user_id'] : false;
			}				
		}

		return $result;		
	}

	function clearUserWorkingTable(int $userId) {
		//if ($userId == 1) echo "remove";
		$userId = intval($userId);
		if ($userId > 0 ) {
			$sql = "DELETE FROM scr_working_list WHERE user_id = '".$userId."'";
			$rs = $this->_db->query($sql);
			if ($rs) $log = new Logger ("Из рабочей таблицы удалены галлереи находившеся в работе пользователя #". $userId);
		}
	}

	function updateWorkingTable (int $userId, int $gal_id, $work_type) {
		$userId = intval($userId);
		$gal_id = intval($gal_id);

		$result = false;

		if ($userId && $this->_db && $gal_id && preg_match("/^(crop|tags)$/", $work_type)) {

			$this->clearUserWorkingTable($userId);

			$sql = "INSERT INTO scr_working_list
					(gal_id, user_id, work_type, change_time) 
					VALUE('".$gal_id."', '".$userId."', '".$work_type."', '".time()."')";

			if(!$rs = $this->_db->query($sql)) {
				$log = new Logger ("Ошибка добавления галлереи в рабочую таблицу ", true);
			} else {
				$result = true;
			}

		} else {
			$log = new Logger ("Ошибка добавления галлереи в рабочую таблицу - ошибка входящих данных", true);
		}
		return $result;
	}


	private function ifGalleryManualRecrop($galId) {
		$galId = intval($galId);
		$result = false;
		if ($this->_db && $galId) {
				$sql = "SELECT gal_id FROM scr_gallery_manual_recrop WHERE gal_id = '".$galId."'";
				$rs = $this->_db->query($sql);
				if ($rs) {
					$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
					if ($row) {
						$res['count'] = count ($row);
						if ($res['count'] > 0){
							$res['id'] = $row[0]['gal_id'];
							return $res;
						}
					}
				}		
		}
		return $result;
	}

	function getGalleryCropInfo ($gal_id) {
		$result = false;
		$sql = "select * from scr_manual_crop_history where gal_id = '".$gal_id."'";
		$rs = $this->_db->query($sql);
		if ($rs) {
			$row = $rs->fetchAll(\PDO::FETCH_ASSOC);
			if ($row) {
				foreach ($row as $item) {
					$result[$item['image_id']] = $item;
				}
					
			}
		}
		return $result;
	}

	function yearHistory (int $userId, int $year = 2013, $type = 'crop') {
		$result = false;

		$currentMonth = ($year != (int)date('Y')) ? 12 : date('n');

		if (!$year || $year < 2013) $year = 2013;
		elseif($year > date('Y')) $year = date('Y');
		
		for ($i = 1; $i <= $currentMonth; $i++) {
			$month = $i;
			$from_date = strtotime($year.'/'.$month.'/1');
			//echo "<br>" .date('Y').'/'.$month.'/'.date('t',$from) . "<br>";
			$day = date('t',$from_date);
			$to_date = strtotime($year.'/'.$month.'/'.$day);
			$result[$i]['galleries'] = $this->getUserHistory($userId,$from_date, $to_date, $type);
			$result[$i]['from'] = $from_date;
			$result[$i]['to'] = $to_date;
			$result[$i]['year'] = $year;
			$result[$i]['month'] = $month;
		}
		return $result;
	}

	function monthHistory ($userId, $year, $month, $type = 'crop') {
		$result = false;
		if (!$year || $year < 2013) $year = 2013;
		elseif ($year > date('Y')) $year = date('Y');
		$month = intval($month);
		if ($month >= 1 && $month <= 12) {
			$from_date = strtotime($year.'/'.$month.'/1');
			$daysInMonth = date('t',$from_date);
			for ($i = 1; $i <= $daysInMonth; $i++) {
				$day = $i;
				$from_date = strtotime($year.'/'.$month.'/'.$day);
				$to_date = ($from_date + (60*60*24) -1);
				$result[$i]['galleries'] = $this->getUserHistory($userId,$from_date, $to_date, $type);
				$result[$i]['from'] = $from_date;
				$result[$i]['to'] = $to_date;
				$result[$i]['year'] = $year;
				$result[$i]['month'] = $month;
				$result[$i]['day'] = $day;
			}
		}
		return $result;
	}

	function dayHistory ($userId, $year, $month, $day, $type = 'crop') {
		$result = false;
		if (!$year || $year < 2013) $year = 2013;
		elseif ($year > date('Y')) $year = date('Y');

		$month = intval($month);
		$day = intval($day);
		
		if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
			$from_date = strtotime($year.'/'.$month.'/1');
			$daysInMonth = date('t',$from_date);
			if ($day <= $daysInMonth) {
				$from_date = strtotime($year.'/'.$month.'/'. $day);
				$to_date = ($from_date + (60*60*24) -1);
				$result['galleries'] = $this->getUserHistory($userId,$from_date, $to_date, $type);
				$result['from'] = $from_date;
				$result['to'] = $to_date;
				$result['year'] = $year;
				$result['month'] = $month;
				$result['day'] = $day;
			}
		}
		return $result;
	}

	private function getUserHistory (int $userId, $dateFrom = false, $dateTo = false, $type = 'crop') {
		
		$result = false;

		$history_sql_table 	= ($type == 'updates')  ? 'scr_gallery_update_history' : 'scr_manual_crop_history';
		$dateFrom 			= ($dateFrom) ? intval($dateFrom) : strtotime(date('Y').'/'.date('M').'/1');
		$dateTo 			= (!$dateTo || $dateTo > time()) ? time() : intval($dateTo);
		
		if ($userId > 0 && $dateFrom < $dateTo) {

			$sql = "SELECT gal_id, updated, count(gal_id) AS galleries_approved 
					FROM ".$history_sql_table." WHERE ";
			$sql .= ($type == 'updates') ? " change_type = 'gallery_approved' AND " : "";
			$sql .= " user_id = '".$userId."'";
			$sql .= " and (updated BETWEEN '".$dateFrom."' AND '".$dateTo."')";
			$sql .= " GROUP BY gal_id, updated ORDER BY updated";
			
			$rs = $this->_db->query($sql);

			if ($rs) {
				return $rs->fetchAll();
			}
		}
		return $result;		
	}

	public function galleryUpdateHistory (int $gal_id) {
		$result = false;
		
		if (!$gal_id) {
			return $result;
		}
			
		$sql = "select * from scr_gallery_update_history where gal_id = '".$gal_id."' order by updated asc";
		$rs = $this->_db->query($sql);
		if ($rs) {
			return $rs->fetchAll();
		}
		
		return $result;
	}

	public function ifGalleryUpdatedByUser(int $user_id, int $gal_id) {
		$result = false;

		if ($gal_id > 0) {
			$sql = "select gal_id from scr_gallery_update_history where gal_id = '".$gal_id."' and user_id = '".$user_id."'";
			$rs = $this->_db->query($sql);
			if ($rs && $rows = $rs->fetch()) {
				$result = true;
			}
		}
		return $result;
	}

	/*

	/ Работа с логом работника

	*/


	public function logGalleryChange($history_type, int $gal_id, int $item_id = 0) {
		return $this->userUpdateHistory($this->id, $history_type, $gal_id, $item_id);
	}



	public function galleryTagAdded(int $gal_id, int $tag_id) {
		return $this->logGalleryChange('tag_added', $gal_id, $tag_id);
	}

	public function galleryTagRemoved(int $gal_id, int $tag_id) {
		return $this->logGalleryChange('tag_removed', $gal_id, $tag_id);
	}

	public function galleryModelAdded(int $gal_id, int $model_id) {
		return $this->logGalleryChange('model_added', $gal_id, $model_id);
	}

	public function galleryModelRemoved(int $gal_id, int $model_id) {
		return $this->logGalleryChange('model_removed', $gal_id, $model_id);
	}

	public function galleryApproved(int $gal_id) {
		return $this->logGalleryChange('gallery_approved', $gal_id);
	}


	/*

	/	Геттеры

	*/


	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->user_name;
	}

	public function getIP() {
		return $this->user_ip;
	}

	public function getOperations() {
		return $this->allowed_operations;
	}

	public function isAdmin() {
		return $this->is_admin;
	}

	public function isWriter() {
		return $this->is_writer;
	}

	public function allowedToTag() {
		return $this->allowed_tags;
	}

	public function allowedToModel() {
		return $this->allowed_models;
	}

	public function allowedToCrop() {
		return $this->allowed_crop;
	}

	public function isAuthorized() {
		return $this->authorized;
	}

	public function allowedToUpload() {
		return $this->is_admin;
	}

	public function getLanguage() {
		return $this->language;
	}
}
