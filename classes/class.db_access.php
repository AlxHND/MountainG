<?php

class db_access{
	private static $connection = false;
	public $_db;

	function __construct() { $this->connect(); }

	public function close_connection() {
		if($this->_db) $this->_db->Close();
		$log = new Logger(__METHOD__.": Connection closed manually");
	}

	public function connect() {

		$db_host = DBHOST;
		$db_name = DBNAME;
		$db_user = DBUSER;
		$db_pass = DBPW;

		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];

		try {
			self::$connection = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
			self::$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

			self::$connection->query("SET NAMES 'utf8';");
			self::$connection->query("SET character_set_results = 'utf8';");
			self::$connection->query("SET collation_connection = 'utf8_general_ci';");

			$this->_db = self::$connection;

	   } catch (\PDOException $e) {
			throw new \PDOException($e->getMessage(), (int)$e->getCode());
	   }

	   return self::$connection;
		
		
	}
}

$db = new db_access();



/*

class db_access{
	private static $connection;
	public static $_db;

	private function __construct() { } // block directly instantiating
	private function __clone() { } // block cloning of the object

	public static function get() {
    	if(!isset(self::$connection) || (!self::$connection )) {
            try {
                self::connect();
            } catch(exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
		}
		// return the instance
		return self::$connection;
	}


	public static function closeConnection() {
		if(self::$connection) {
			self::$connection->close();
			self::$connection = null;
		}
	}


	public static function connect() {

			$db_host = DBHOST;
			$db_name = DBNAME;
			$db_user = DBUSER;
			$db_pass = DBPW;

			$options = [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
			];

			try {
				self::$connection = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
				self::$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

				self::$connection->query("SET NAMES 'utf8';");
				self::$connection->query("SET character_set_results = 'utf8';");
				self::$connection->query("SET collation_connection = 'utf8_general_ci';");

				self::$_db = self::$connection;

			} catch (\PDOException $e) {
				throw new \PDOException($e->getMessage(), (int)$e->getCode());
			}

			var_dump(self::$_db);

			return self::$connection;
		
		
	}
}

*/


class DB {
	private static $instance; // stores the MySQLi instance
	private static $error = false;
	  
	private function __construct() { } // block directly instantiating
	private function __clone() { } // block cloning of the object

	public static function get() {
    	// create the instance if it does not exist
    	if(!isset(self::$instance) || (!self::$instance && !self::$error)) {
			// the MYSQL_* constants should be set to or
			//  replaced with your db connection details
            try {
                self::setConnection();
            } catch(exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
		}
		// return the instance
		return self::$instance;
   }

   public static function setConnection() {
   		self::$instance = new mysqli(DBHOST, DBUSER, DBPW, DBNAME);
		if(!self::$instance) self::$error = true;
		else {
			// $log = new Logger(__METHOD__.":DB connect set, PID#".getmypid(), true);
			self::$instance->query("SET NAMES 'utf8';");
			self::$instance->query("SET character_set_results = 'utf8';");
			self::$instance->query("SET collation_connection = 'utf8';");
		}
		if(self::$instance->connect_error) {
			throw new Exception('MySQL connection failed: ' . self::$instance->connect_error);
		}
   }

   // если ошибка была
   public static function isError() {
   		return self::$error;
   }

   public static function closeConnection() {
   		if(self::$instance) {
   			self::$instance->close();
   			self::$instance = false;
   			self::$error = false;
   		}
   }
}

class PDOConnection {
    private static $instance;
	private static $error = false;
	  
	private function __construct() { }
	private function __clone() { }

	public static function get() {
    	if(!isset(self::$instance) || (!self::$instance && !self::$error)) {
            try {
                self::$instance = self::setConnection();
            } catch(exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
		}
		// return the instance
		return self::$instance;
   }

   	public static function setConnection() {
        
        $db_host = DBHOST;
        $db_name = DBNAME;
        $db_user = DBUSER;
        $db_pass = DBPW;

        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Выбрасывать исключения при ошибках
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,  // Использовать настоящие подготовленные выражения
        ];

        $pdo = new PDO($dsn, $db_user, $db_pass, $options);

		$pdo->exec("SET NAMES 'utf8mb4'");
    	$pdo->exec("SET character_set_results = 'utf8mb4'");
    	$pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci'");


        return $pdo;
    }

    public static function isError() {
		return self::$error;
	}

	public static function closeConnection() {
			if(self::$instance) {
				self::$instance->close();
				self::$instance = false;
				self::$error = false;
			}
	}
}