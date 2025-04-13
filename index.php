<?php

require_once 'backend/dbconfig/dbconn.php';

// Create a new instance of the Database class
$database = new Database();
$db = $database->getConnection();

// Check if the connection was successful
if ($db) {
    echo "Database connection established successfully.";
} else {
    echo "Failed to connect to the database.";
}


?>