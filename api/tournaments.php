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
                // Get specific tournament
                $stmt = $pdo->prepare("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl, t.startDate, t.endDate, t.prizePool, t.maxParticipants, ci.createdAt,
                           (SELECT COUNT(*) FROM TournamentRegistration WHERE tournamentId = ci.id AND status != 'CANCELLED') as currentParticipants
                    FROM ContentItem ci
                    JOIN Tournament t ON ci.id = t.id
                    WHERE ci.id = ? AND ci.type = 'TOURNAMENT'
                ");
                $stmt->execute([$_GET['id']]);
                $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($tournament ?: ['error' => 'Tournament not found']);
            } else {
                // Get all tournaments
                $stmt = $pdo->query("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl, t.startDate, t.endDate, t.prizePool, t.maxParticipants, ci.createdAt,
                           (SELECT COUNT(*) FROM TournamentRegistration WHERE tournamentId = ci.id AND status != 'CANCELLED') as currentParticipants
                    FROM ContentItem ci
                    JOIN Tournament t ON ci.id = t.id
                    WHERE ci.type = 'TOURNAMENT'
                    ORDER BY t.startDate ASC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new tournament - auto-generate ID if not provided
            $id = isset($input['id']) ? $input['id'] : 'tournament-' . uniqid();
            
            $pdo->beginTransaction();
            
            // Insert into ContentItem
            $stmt = $pdo->prepare("
                INSERT INTO ContentItem (id, name, description, imageUrl, type) 
                VALUES (?, ?, ?, ?, 'TOURNAMENT')
            ");
            $stmt->execute([
                $id,
                $input['name'],
                $input['description'] ?? null,
                $input['imageUrl'] ?? null
            ]);
            
            // Insert into Tournament
            $stmt = $pdo->prepare("
                INSERT INTO Tournament (id, startDate, endDate, prizePool, maxParticipants) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $input['startDate'],
                $input['endDate'],
                $input['prizePool'] ?? null,
                $input['maxParticipants'] ?? 50
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Tournament created successfully']);
            break;

        case 'PUT':
            // Update tournament
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Tournament ID required']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Update ContentItem
            $stmt = $pdo->prepare("
                UPDATE ContentItem 
                SET name = ?, description = ?, imageUrl = ? 
                WHERE id = ? AND type = 'TOURNAMENT'
            ");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? null,
                $input['imageUrl'] ?? null,
                $_GET['id']
            ]);
            
            // Update Tournament
            $stmt = $pdo->prepare("
                UPDATE Tournament 
                SET startDate = ?, endDate = ?, prizePool = ?, maxParticipants = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $input['startDate'],
                $input['endDate'],
                $input['prizePool'] ?? null,
                $input['maxParticipants'] ?? 50,
                $_GET['id']
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Tournament updated successfully']);
            break;

        case 'DELETE':
            // Delete tournament
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Tournament ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM ContentItem WHERE id = ? AND type = 'TOURNAMENT'");
            $stmt->execute([$_GET['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Tournament deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
