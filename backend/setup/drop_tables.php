<?php
// This file will drop the existing tables from the database

include_once '../dbconfig/dbconn.php';

class DropTables {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function dropAllTables() {
        $tables = ['scores', 'games', 'users'];

        foreach ($tables as $table) {
            $query = "DROP TABLE IF EXISTS " . $table;
            try {
                $this->conn->exec($query);
                echo "Table " . $table . " dropped successfully.<br>";
            } catch (PDOException $exception) {
                echo "Error dropping table " . $table . ": " . $exception->getMessage() . "<br>";
            }
        }
    }
}

$dropTables = new DropTables();
$dropTables->dropAllTables();
?>