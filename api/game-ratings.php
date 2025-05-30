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
            if (isset($_GET['gameId'])) {
                // Get ratings for specific game
                $stmt = $pdo->prepare("
                    SELECT gr.*, u.username 
                    FROM GameRating gr 
                    JOIN Users u ON gr.userId = u.id 
                    WHERE gr.gameId = ? 
                    ORDER BY gr.ratedAt DESC
                ");
                $stmt->execute([$_GET['gameId']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                // Get all ratings
                $stmt = $pdo->query("
                    SELECT gr.*, u.username, ci.name as gameName 
                    FROM GameRating gr 
                    JOIN Users u ON gr.userId = u.id 
                    JOIN ContentItem ci ON gr.gameId = ci.id 
                    ORDER BY gr.ratedAt DESC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            $id = 'rating-' . uniqid();
            
            $pdo->beginTransaction();
            
            // Insert rating
            $stmt = $pdo->prepare("
                INSERT INTO GameRating (id, userId, gameId, rating) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $input['userId'],
                $input['gameId'],
                $input['rating']
            ]);
            
            // Update average rating
            $stmt = $pdo->prepare("
                UPDATE Game SET averageRating = (
                    SELECT AVG(rating) FROM GameRating WHERE gameId = ?
                ) WHERE id = ?
            ");
            $stmt->execute([$input['gameId'], $input['gameId']]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Rating added successfully']);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Rating ID required']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Update rating
            $stmt = $pdo->prepare("UPDATE GameRating SET rating = ? WHERE id = ?");
            $stmt->execute([$input['rating'], $_GET['id']]);
            
            // Get gameId for average calculation
            $stmt = $pdo->prepare("SELECT gameId FROM GameRating WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $gameId = $stmt->fetchColumn();
            
            // Update average rating
            $stmt = $pdo->prepare("
                UPDATE Game SET averageRating = (
                    SELECT AVG(rating) FROM GameRating WHERE gameId = ?
                ) WHERE id = ?
            ");
            $stmt->execute([$gameId, $gameId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Rating updated successfully']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Rating ID required']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Get gameId before deletion
            $stmt = $pdo->prepare("SELECT gameId FROM GameRating WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $gameId = $stmt->fetchColumn();
            
            // Delete rating
            $stmt = $pdo->prepare("DELETE FROM GameRating WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            // Update average rating
            $stmt = $pdo->prepare("
                UPDATE Game SET averageRating = COALESCE((
                    SELECT AVG(rating) FROM GameRating WHERE gameId = ?
                ), 0) WHERE id = ?
            ");
            $stmt->execute([$gameId, $gameId]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Rating deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
