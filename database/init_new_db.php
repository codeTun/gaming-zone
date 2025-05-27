<?php
try {
    // Create database connection
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gaming_zone_new");
    echo "Database 'gaming_zone_new' created or already exists<br>";
    
    // Select the database
    $pdo->exec("USE gaming_zone_new");
    
    // Read and execute SQL schema
    $sql = file_get_contents(__DIR__ . '/new_db_schema.sql');
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database schema created successfully!<br>";
    echo "You can now use your gaming platform backend.";
    
} catch(PDOException $e) {
    echo "Database initialization error: " . $e->getMessage();
    die();
}
?>
