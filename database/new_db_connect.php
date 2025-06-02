<?php
require_once dirname(__DIR__) . '/helpers/EnvLoader.php';

class DatabaseConnection {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            // Load environment variables
            EnvLoader::load();
            
            $host = EnvLoader::get('DB_HOST', 'localhost');
            $user = EnvLoader::get('DB_USERNAME', 'root');
            $pass = EnvLoader::get('DB_PASSWORD', '');
            $dbname = EnvLoader::get('DB_NAME', 'gaming_zone');
            
            $this->conn = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4", 
                $user, 
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }
    }
    
    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>
