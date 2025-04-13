<?php
include_once '../dbconfig/dbconn.php';

class CreateTables {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->conn->exec($query);
    }

    public function createGamesTable() {
        $query = "CREATE TABLE IF NOT EXISTS games (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            genre VARCHAR(50) NOT NULL,
            release_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->conn->exec($query);
    }

    public function createScoresTable() {
        $query = "CREATE TABLE IF NOT EXISTS scores (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            game_id INT(11) NOT NULL,
            score INT(11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (game_id) REFERENCES games(id)
        )";

        $this->conn->exec($query);
    }

    public function run() {
        $this->createUsersTable();
        $this->createGamesTable();
        $this->createScoresTable();
        echo "Tables created successfully.";
    }
}

$createTables = new CreateTables();
$createTables->run();
?>