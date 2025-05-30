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
            if (isset($_GET['tournamentId'])) {
                // Get all registrations for specific tournament
                $stmt = $pdo->prepare("
                    SELECT tr.*, u.name as fullName, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool
                    FROM TournamentRegistration tr 
                    JOIN Users u ON tr.userId = u.id 
                    JOIN ContentItem ci ON tr.tournamentId = ci.id 
                    JOIN Tournament t ON tr.tournamentId = t.id
                    WHERE tr.tournamentId = ? 
                    ORDER BY tr.registeredAt DESC
                ");
                $stmt->execute([$_GET['tournamentId']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } elseif (isset($_GET['userId'])) {
                // Get all tournament registrations for specific user
                $stmt = $pdo->prepare("
                    SELECT tr.*, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool
                    FROM TournamentRegistration tr 
                    JOIN ContentItem ci ON tr.tournamentId = ci.id 
                    JOIN Tournament t ON tr.tournamentId = t.id
                    WHERE tr.userId = ? 
                    ORDER BY tr.registeredAt DESC
                ");
                $stmt->execute([$_GET['userId']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            } else {
                // Get all tournament registrations
                $stmt = $pdo->query("
                    SELECT tr.*, u.name as fullName, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool
                    FROM TournamentRegistration tr 
                    JOIN Users u ON tr.userId = u.id 
                    JOIN ContentItem ci ON tr.tournamentId = ci.id 
                    JOIN Tournament t ON tr.tournamentId = t.id
                    ORDER BY tr.registeredAt DESC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Register user for tournament - auto-generate ID
            $id = 'treg-' . uniqid();
            
            // Validate that user exists
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE id = ?");
            $stmt->execute([$input['userId']]);
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'User not found']);
                break;
            }
            
            // Validate that tournament exists
            $stmt = $pdo->prepare("SELECT id FROM Tournament WHERE id = ?");
            $stmt->execute([$input['tournamentId']]);
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'Tournament not found']);
                break;
            }
            
            // Check if user is already registered for this tournament
            $stmt = $pdo->prepare("SELECT id FROM TournamentRegistration WHERE userId = ? AND tournamentId = ?");
            $stmt->execute([$input['userId'], $input['tournamentId']]);
            if ($stmt->fetch()) {
                echo json_encode(['error' => 'User is already registered for this tournament']);
                break;
            }
            
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
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Tournament registration successful']);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Registration ID required']);
                break;
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($input['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $input['status'];
            }
            if (isset($input['teamName'])) {
                $updateFields[] = "teamName = ?";
                $params[] = $input['teamName'];
            }
            if (isset($input['username'])) {
                $updateFields[] = "username = ?";
                $params[] = $input['username'];
            }
            if (isset($input['email'])) {
                $updateFields[] = "email = ?";
                $params[] = $input['email'];
            }
            
            $params[] = $_GET['id'];
            
            $stmt = $pdo->prepare("UPDATE TournamentRegistration SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Tournament registration updated successfully']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Registration ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM TournamentRegistration WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Tournament registration deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
