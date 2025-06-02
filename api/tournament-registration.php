<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

try {
    // Include database configuration
    require_once '../config/database.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if JSON decode failed
    if ($method === 'POST' && $input === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['tournamentId'])) {
                // Get all registrations for specific tournament
                $stmt = $pdo->prepare("
                    SELECT tr.*, u.name as fullName, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool, t.maxParticipants
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
                    SELECT tr.*, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool, t.maxParticipants
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
                    SELECT tr.*, u.name as fullName, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool, t.maxParticipants
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
            // Validate required fields
            if (!isset($input['userId']) || !isset($input['tournamentId']) || !isset($input['username']) || !isset($input['email']) || !isset($input['teamName'])) {
                echo json_encode(['error' => 'Missing required fields: userId, tournamentId, username, email, teamName']);
                break;
            }
            
            // Register user for tournament - auto-generate ID
            $id = 'treg-' . uniqid();
            
            // Validate that user exists
            $stmt = $pdo->prepare("SELECT id FROM Users WHERE id = ?");
            $stmt->execute([$input['userId']]);
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'User not found']);
                break;
            }
            
            // Validate that tournament exists and get max participants
            $stmt = $pdo->prepare("SELECT id, maxParticipants FROM Tournament WHERE id = ?");
            $stmt->execute([$input['tournamentId']]);
            $tournament = $stmt->fetch();
            if (!$tournament) {
                echo json_encode(['error' => 'Tournament not found']);
                break;
            }
            
            // Check current participants count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM TournamentRegistration WHERE tournamentId = ? AND status != 'CANCELLED'");
            $stmt->execute([$input['tournamentId']]);
            $currentParticipants = $stmt->fetchColumn();
            
            if ($currentParticipants >= $tournament['maxParticipants']) {
                echo json_encode(['error' => 'Tournament is full. Maximum participants reached.']);
                break;
            }
            
            // Check if user is already registered for this tournament
            $stmt = $pdo->prepare("SELECT id FROM TournamentRegistration WHERE userId = ? AND tournamentId = ?");
            $stmt->execute([$input['userId'], $input['tournamentId']]);
            if ($stmt->fetch()) {
                echo json_encode(['error' => 'User is already registered for this tournament']);
                break;
            }
            
            // Insert the registration
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
            
            if (empty($updateFields)) {
                echo json_encode(['error' => 'No fields to update']);
                break;
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
            break;
    }
    
} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // General error
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    // PHP Fatal error
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
