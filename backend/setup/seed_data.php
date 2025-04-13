<?php
include_once '../dbconfig/dbconn.php';

class SeedData {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function seedUsers() {
        $sql = "INSERT INTO users (username, password, email) VALUES
                ('user1', 'password1', 'user1@example.com'),
                ('user2', 'password2', 'user2@example.com'),
                ('user3', 'password3', 'user3@example.com')";

        try {
            $this->conn->exec($sql);
            echo "Users seeded successfully.";
        } catch (PDOException $exception) {
            echo "Error seeding users: " . $exception->getMessage();
        }
    }

    public function seedGames() {
        $sql = "INSERT INTO games (title, genre, release_date) VALUES
                ('Game 1', 'Action', '2023-01-01'),
                ('Game 2', 'Adventure', '2023-02-01'),
                ('Game 3', 'Puzzle', '2023-03-01')";

        try {
            $this->conn->exec($sql);
            echo "Games seeded successfully.";
        } catch (PDOException $exception) {
            echo "Error seeding games: " . $exception->getMessage();
        }
    }
}

$seedData = new SeedData();
$seedData->seedUsers();
$seedData->seedGames();
?>