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
            $type = $_GET['type'] ?? 'all'; // 'event', 'tournament', or 'all'
            
            if ($type === 'event') {
                $stmt = $pdo->query("
                    SELECT er.*, u.username, ci.name as eventName 
                    FROM EventRegistration er 
                    JOIN Users u ON er.userId = u.id 
                    JOIN ContentItem ci ON er.eventId = ci.id 
                    ORDER BY er.registeredAt DESC
                ");
            } elseif ($type === 'tournament') {
                $stmt = $pdo->query("
                    SELECT tr.*, u.username, ci.name as tournamentName 
                    FROM TournamentRegistration tr 
                    JOIN Users u ON tr.userId = u.id 
                    JOIN ContentItem ci ON tr.tournamentId = ci.id 
                    ORDER BY tr.registeredAt DESC
                ");
            } else {
                // Get both types with UNION
                $stmt = $pdo->query("
                    SELECT er.id, er.userId, er.eventId as itemId, er.username, er.email, er.registeredAt, er.status, 'EVENT' as type, ci.name as itemName
                    FROM EventRegistration er 
                    JOIN ContentItem ci ON er.eventId = ci.id
                    UNION ALL
                    SELECT tr.id, tr.userId, tr.tournamentId as itemId, tr.username, tr.email, tr.registeredAt, tr.status, 'TOURNAMENT' as type, ci.name as itemName
                    FROM TournamentRegistration tr 
                    JOIN ContentItem ci ON tr.tournamentId = ci.id
                    ORDER BY registeredAt DESC
                ");
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            $type = $input['type']; // 'event' or 'tournament'
            $id = $type . 'reg-' . uniqid();
            
            if ($type === 'event') {
                $stmt = $pdo->prepare("
                    INSERT INTO EventRegistration (id, userId, eventId, username, email, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $input['userId'],
                    $input['eventId'],
                    $input['username'],
                    $input['email'],
                    $input['status'] ?? 'PENDING'
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO TournamentRegistration (id, userId, tournamentId, username, email, teamName, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id,
                    $input['userId'],
                    $input['tournamentId'],
                    $input['username'],
                    $input['email'],
                    $input['teamName'],
                    $input['status'] ?? 'PENDING'
                ]);
            }
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => ucfirst($type) . ' registration successful']);
            break;

        case 'PUT':
            if (!isset($_GET['id']) || !isset($_GET['type'])) {
                echo json_encode(['error' => 'Registration ID and type required']);
                break;
            }
            
            $type = $_GET['type'];
            $table = $type === 'event' ? 'EventRegistration' : 'TournamentRegistration';
            
            $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
            $stmt->execute([$input['status'], $_GET['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Registration status updated successfully']);
            break;

        case 'DELETE':
            if (!isset($_GET['id']) || !isset($_GET['type'])) {
                echo json_encode(['error' => 'Registration ID and type required']);
                break;
            }
            
            $type = $_GET['type'];
            $table = $type === 'event' ? 'EventRegistration' : 'TournamentRegistration';
            
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Registration deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
