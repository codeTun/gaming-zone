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

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific tournament
                $stmt = $pdo->prepare("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl,
                           t.startDate, t.endDate, t.prizePool, t.maxParticipants,
                           ci.createdAt
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
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl,
                           t.startDate, t.endDate, t.prizePool, t.maxParticipants,
                           ci.createdAt
                    FROM ContentItem ci
                    JOIN Tournament t ON ci.id = t.id
                    WHERE ci.type = 'TOURNAMENT'
                    ORDER BY t.startDate ASC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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
