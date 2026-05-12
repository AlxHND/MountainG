<?php

namespace App\Helpers;

use \PDO;
use \PDOException;
use \Exception;

class DB {
    private static ?PDO $instance = null;

    private function __construct() {
 
    }

    private function __clone() {
 
    }

    /**
     * Возвращает экземпляр PDO или создает новый, если он не существует
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                self::setConnection();
            } catch (PDOException $e) {
                // Можно добавить логирование ошибки
                throw new Exception("Ошибка подключения к базе данных: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Настраивает подключение к базе данных
     */
    private static function setConnection(): void {
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

        self::$instance = new PDO($dsn, $db_user, $db_pass, $options);

		self::$instance->exec("SET NAMES 'utf8mb4'");
    	self::$instance->exec("SET character_set_results = 'utf8mb4'");
    	self::$instance->exec("SET collation_connection = 'utf8mb4_unicode_ci'");
    }

    /**
     * Закрытие соединения
     */
    public static function closeConnection(): void {
        if (self::$instance !== null) {
            self::$instance = null;  // Закрываем соединение
        }
    }
}