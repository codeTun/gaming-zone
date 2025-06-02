<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific game
                $stmt = $pdo->prepare("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl,
                           g.categoryId, c.name as categoryName, g.minAge, 
                           g.targetGender, g.averageRating,
                           ci.createdAt, ci.createdAt as updatedAt
                    FROM ContentItem ci
                    JOIN Game g ON ci.id = g.id
                    LEFT JOIN Category c ON g.categoryId = c.id
                    WHERE ci.id = ? AND ci.type = 'GAME'
                ");
                $stmt->execute([$_GET['id']]);
                $game = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($game ?: ['error' => 'Game not found']);
            } else {
                // Get all games
                $stmt = $pdo->query("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl,
                           g.categoryId, c.name as categoryName, g.minAge, 
                           g.targetGender, g.averageRating,
                           ci.createdAt, ci.createdAt as updatedAt
                    FROM ContentItem ci
                    JOIN Game g ON ci.id = g.id
                    LEFT JOIN Category c ON g.categoryId = c.id
                    WHERE ci.type = 'GAME'
                    ORDER BY ci.createdAt DESC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new game
            $id = 'game-' . uniqid();
            
            // Validate required fields
            if (!isset($input['name']) || !isset($input['categoryId'])) {
                echo json_encode(['error' => 'Missing required fields: name, categoryId']);
                break;
            }
            
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Insert into ContentItem
                $stmt = $pdo->prepare("
                    INSERT INTO ContentItem (id, name, description, imageUrl, type) 
                    VALUES (?, ?, ?, ?, 'GAME')
                ");
                $stmt->execute([
                    $id,
                    $input['name'],
                    $input['description'] ?? null,
                    $input['imageUrl'] ?? null
                ]);
                
                // Insert into Game
                $stmt = $pdo->prepare("
                    INSERT INTO Game (id, categoryId, minAge, targetGender, averageRating) 
                    VALUES (?, ?, ?, ?, 0.00)
                ");
                $stmt->execute([
                    $id,
                    $input['categoryId'],
                    $input['minAge'] ?? null,
                    $input['targetGender'] ?? null
                ]);
                
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'id' => $id, 
                    'message' => 'Game created successfully'
                ]);
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
