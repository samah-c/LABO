<?php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Charger la config
        $config = require_once __DIR__ . '/config.php';
        $db = $config['db'];
        
        try {
            $this->connection = new PDO(
                "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
                $db['user'],
                $db['pass']
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base TDW");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}