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
            if (isset($_GET['userId'])) {
                // Get user's game history
                $stmt = $pdo->prepare("
                    SELECT ug.*, ci.name as gameName, c.name as categoryName
                    FROM UserGame ug 
                    JOIN ContentItem ci ON ug.gameId = ci.id 
                    JOIN Game g ON ug.gameId = g.id 
                    JOIN Category c ON g.categoryId = c.id 
                    WHERE ug.userId = ? 
                    ORDER BY ug.playedAt DESC
                ");
                $stmt->execute([$_GET['userId']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } elseif (isset($_GET['gameId'])) {
                // Get leaderboard for specific game
                $stmt = $pdo->prepare("
                    SELECT ug.*, u.username 
                    FROM UserGame ug 
                    JOIN Users u ON ug.userId = u.id 
                    WHERE ug.gameId = ? 
                    ORDER BY ug.score DESC, ug.playedAt ASC 
                    LIMIT 10
                ");
                $stmt->execute([$_GET['gameId']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                // Get all user games
                $stmt = $pdo->query("
                    SELECT ug.*, u.username, ci.name as gameName 
                    FROM UserGame ug 
                    JOIN Users u ON ug.userId = u.id 
                    JOIN ContentItem ci ON ug.gameId = ci.id 
                    ORDER BY ug.playedAt DESC 
                    LIMIT 50
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new user game record - auto-generate ID if not provided
            $id = isset($input['id']) ? $input['id'] : 'usergame-' . uniqid();
            $stmt = $pdo->prepare("
                INSERT INTO UserGame (id, userId, gameId, score) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $input['userId'],
                $input['gameId'],
                $input['score']
            ]);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Game score recorded successfully']);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'UserGame ID required']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE UserGame SET score = ? WHERE id = ?");
            $stmt->execute([$input['score'], $_GET['id']]);
            echo json_encode(['success' => true, 'message' => 'Score updated successfully']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'UserGame ID required']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM UserGame WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'message' => 'Game record deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
