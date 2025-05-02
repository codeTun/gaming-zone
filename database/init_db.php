<?php
// First create database if it doesn't exist
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gaming_zone");
    echo "Database created or already exists successfully<br>";
    
    // Select the database
    $pdo->exec("USE gaming_zone");
    
    // Read SQL from file and execute
    $sql = file_get_contents(__DIR__ . '/db_schema.sql');
    $pdo->exec($sql);
    
    echo "Tables created successfully!";
} catch(PDOException $e) {
    echo "Database creation error: " . $e->getMessage();
    die();
}
?>
