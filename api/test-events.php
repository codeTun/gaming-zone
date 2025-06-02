<?php
require_once '../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Testing Events Query</h2>";
    
    // Test the events query
    $stmt = $pdo->query("
        SELECT ci.id, ci.name as title, ci.description, ci.imageUrl, 
               e.place, e.startDate as eventDate,
               DATE_FORMAT(e.startDate, '%H:%i') as eventTime,
               ci.createdAt, ci.createdAt as updatedAt
        FROM ContentItem ci
        JOIN Event e ON ci.id = e.id
        WHERE ci.type = 'EVENT'
        ORDER BY e.startDate DESC
    ");
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($events) . " events:</p>";
    echo "<pre>";
    print_r($events);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
