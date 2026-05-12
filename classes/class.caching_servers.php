<?php
class CachingServers {
	private static $instance; // stores the instance
	private static $error = false;

	private static $servers_array = array();

	private static $id = false;
	private static $name = false;
	private static $ip = false;
	private static $port = false;
	private static $prev_server = false;
	private static $current_server = false;
	private static $next_server = false;

	private static $first_server = false;
	private static $last_server = false;

	private static $servers_count = 0;
	  
	private function __construct() { } // block directly instantiating
	private function __clone() { } // block cloning of the object

	// public static function get() {
 //    	if(!isset(self::$instance)) {
	// 		self::setConnection();
	// 	}
	// 	// return the instance
	// 	return self::$instance;
 //   	}

  	public static function addServer($id, $name, $ip, $port) {

  		$id = (int)$id;
  		$port = (int)$port;

  		if($id < 0) $id = false;
  		if($port < 0) $port = false;

  		if(!preg_match("([a-z0-9\s-]{1,64})", $name)) { $name = false; }

  		if($ip == 'localhost') $ip = "127.0.0.1";
  		if(!ip2long($ip)) $ip = false;

  		$result = false;

  		if($id !==false && $name && $ip && $port) {
  			if(!array_key_exists($id, self::$servers_array)) {
  				self::$servers_array[$id]['id'] = $id;
  				self::$servers_array[$id]['name'] = $name;
  				self::$servers_array[$id]['ip'] = $ip;
  				self::$servers_array[$id]['port'] = $port;
  				self::$servers_array[$id]['prev_server'] = false;
  				self::$servers_array[$id]['next_server'] = false;
  				
  				// формирование связанного списка для возможности прохождения по списку, удаления и добавления
  				// новых серверов
  				if(self::$first_server === false) {
  					self::$first_server = $id;
  					self::$last_server = $id;
  				} else {
  					$current_last_server_id = self::$last_server;
  					self::$last_server = $id;
  					self::$servers_array[$id]['prev_server'] = $current_last_server_id;
  					self::$servers_array[$current_last_server_id]['next_server'] = $id;  					
  				}
  				self::$servers_count++;
  				$result = self::$servers_count;
  			} else { self::$error = __METHOD__.": ID сервера #".$id." уже существует (пытался добавить id:".$id.", name:".$name.",ip:".$ip.",port:".$port.")";	 }
  		} else { self::$error = __METHOD__.": входящие параметры неверные"; }

  		return $result;
   	}

   	public static function setServer($id) {
  		$id = (int)$id;
  		if($id < 0) $id = false;

  		$result = false;

  		if($id !== false) {
  			self::$id = self::$servers_array[$id]['id'];
  			self::$name = self::$servers_array[$id]['name'];
  			self::$ip = self::$servers_array[$id]['ip'];
  			self::$port = self::$servers_array[$id]['port'];
  			self::$prev_server = self::$servers_array[$id]['prev_server'];
			self::$current_server = self::$id;
			self::$next_server = self::$servers_array[$id]['next_server'];
			$result = true;
  		}

  		return $result;
   	}



   	public static function next() {
   		$result = false;

   		if(self::$id !== false) {
   			if(self::$next_server !== false) {
   				self::setServer(self::$next_server);
   				$result = true;
   			} else {
   				self::resetToFirst();
   			}
   		} else {
   			self::resetToFirst(); 
   			if(self::$id !== false) $result = true;
   		}

   		return $result;
   	}

   	public static function previous() {
   		
   	}

   	public static function resetToFirst() {
   		if(self::$first_server !== false) {
   			self::setServer(self::$first_server);
   		} else {
	   		self::$id = false;
	  		self::$name = false;
	  		self::$ip = false;
	  		self::$port = false;
	  		self::$prev_server = false;
			self::$current_server = false;
			self::$next_server = false;
		}
   	}

   	public static function reset() {
  		self::$id = false;
	  	self::$name = false;
	  	self::$ip = false;
	  	self::$port = false;
	  	self::$prev_server = false;
		self::$current_server = false;
		self::$next_server = false;
   	}

   	public static function serverInfo() {
   		$result = false;
   		if(self::$id !== false) {
   			$result = self::$servers_array[self::$id ];
   		}
   		return $result;
   	}

	public static function currentID() {
  		return self::$id;
   	}
   	public static function currentIP() {
  		return self::$ip;
   	}
   	public static function currentName() {
  		return self::$name;
   	}   	

   	public static function currentPort() {
  		return self::$port;
   	}

   	public static function getServersCount() {
  		return self::$servers_count;
   	}

   	public static function getLastErrorMsg() {
   		return self::$error;
   	}

   	public static function listServers() {
   		var_dump(self::$servers_array);
   	}

}

?>