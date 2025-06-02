<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create a simple database connection without .env dependency
class SimpleDatabase {
    private $pdo;
    
    public function __construct() {
        try {
            // Direct database connection - update these values for your setup
            $host = 'localhost';
            $dbname = 'gaming_zone';
            $username = 'root';
            $password = '';
            
            $this->pdo = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4", 
                $username, 
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

class EventRegistrationAPI {
    private $pdo;
    
    public function __construct() {
        try {
            $database = new SimpleDatabase();
            $this->pdo = $database->getConnection();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit();
        }
    }
    
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'GET':
                    $this->getEventRegistrations();
                    break;
                case 'POST':
                    $this->createEventRegistration();
                    break;
                case 'PUT':
                    $this->updateEventRegistration();
                    break;
                case 'DELETE':
                    $this->deleteEventRegistration();
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    break;
            }
        } catch (Exception $e) {
            error_log("Event Registration API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getEventRegistrations() {
        try {
            $eventId = $_GET['eventId'] ?? null;
            $userId = $_GET['userId'] ?? null;
            
            $sql = "SELECT 
                        er.id,
                        er.userId,
                        er.eventId,
                        er.username,
                        er.email,
                        er.registeredAt,
                        er.status,
                        u.name as userFullName,
                        ci.name as eventName,
                        e.place as eventPlace,
                        e.startDate as eventStartDate
                    FROM EventRegistration er
                    LEFT JOIN Users u ON er.userId = u.id
                    LEFT JOIN Event e ON er.eventId = e.id
                    LEFT JOIN ContentItem ci ON e.id = ci.id";
            
            $params = [];
            $conditions = [];
            
            if ($eventId) {
                $conditions[] = "er.eventId = :eventId";
                $params['eventId'] = $eventId;
            }
            
            if ($userId) {
                $conditions[] = "er.userId = :userId";
                $params['userId'] = $userId;
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY er.registeredAt DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($registrations);
            
        } catch (Exception $e) {
            error_log("Get event registrations error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch event registrations'
            ]);
        }
    }
    
    private function createEventRegistration() {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON input'
                ]);
                return;
            }
            
            // Validate required fields
            $requiredFields = ['userId', 'eventId', 'teamName', 'playerCount', 'contactEmail', 'discordId', 'experience'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields: ' . implode(', ', $missingFields)
                ]);
                return;
            }
            
            // Validate user exists
            $userStmt = $this->pdo->prepare("SELECT id, name, username FROM Users WHERE id = :userId");
            $userStmt->execute(['userId' => $input['userId']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]);
                return;
            }
            
            // Validate event exists
            $eventStmt = $this->pdo->prepare("
                SELECT e.id, ci.name as eventName, e.place, e.startDate 
                FROM Event e 
                JOIN ContentItem ci ON e.id = ci.id 
                WHERE e.id = :eventId
            ");
            $eventStmt->execute(['eventId' => $input['eventId']]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Event not found'
                ]);
                return;
            }
            
            // Check if user is already registered for this event
            $existingStmt = $this->pdo->prepare("
                SELECT id FROM EventRegistration 
                WHERE userId = :userId AND eventId = :eventId
            ");
            $existingStmt->execute([
                'userId' => $input['userId'],
                'eventId' => $input['eventId']
            ]);
            
            if ($existingStmt->fetch()) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'User is already registered for this event'
                ]);
                return;
            }
            
            // Generate unique ID
            $registrationId = $this->generateUUID();
            
            // Create event registration - Updated to match schema
            $insertStmt = $this->pdo->prepare("
                INSERT INTO EventRegistration (
                    id, 
                    userId, 
                    eventId, 
                    username, 
                    email, 
                    status
                ) VALUES (
                    :id, 
                    :userId, 
                    :eventId, 
                    :username, 
                    :email, 
                    :status
                )
            ");
            
            $success = $insertStmt->execute([
                'id' => $registrationId,
                'userId' => $input['userId'],
                'eventId' => $input['eventId'],
                'username' => $user['username'] ?? $user['name'],
                'email' => $input['contactEmail'],
                'status' => 'PENDING'
            ]);
            
            if ($success) {
                // Get the created registration with full details
                $detailStmt = $this->pdo->prepare("
                    SELECT 
                        er.id,
                        er.userId,
                        er.eventId,
                        er.username,
                        er.email,
                        er.registeredAt,
                        er.status,
                        u.name as userFullName,
                        ci.name as eventName,
                        e.place as eventPlace,
                        e.startDate as eventStartDate
                    FROM EventRegistration er
                    LEFT JOIN Users u ON er.userId = u.id
                    LEFT JOIN Event e ON er.eventId = e.id
                    LEFT JOIN ContentItem ci ON e.id = ci.id
                    WHERE er.id = :registrationId
                ");
                $detailStmt->execute(['registrationId' => $registrationId]);
                $registration = $detailStmt->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Event registration created successfully',
                    'data' => $registration
                ]);
            } else {
                throw new Exception('Failed to create event registration');
            }
            
        } catch (Exception $e) {
            error_log("Create event registration error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to create event registration: ' . $e->getMessage()
            ]);
        }
    }
    
    private function updateEventRegistration() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $registrationId = $_GET['id'] ?? $input['id'] ?? null;
            
            if (!$registrationId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Registration ID is required'
                ]);
                return;
            }
            
            // Validate registration exists
            $existingStmt = $this->pdo->prepare("SELECT * FROM EventRegistration WHERE id = :id");
            $existingStmt->execute(['id' => $registrationId]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Event registration not found'
                ]);
                return;
            }
            
            // Update only provided fields
            $updateFields = [];
            $params = ['id' => $registrationId];
            
            if (isset($input['status'])) {
                $updateFields[] = "status = :status";
                $params['status'] = $input['status'];
            }
            
            if (isset($input['email'])) {
                $updateFields[] = "email = :email";
                $params['email'] = $input['email'];
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'No fields to update'
                ]);
                return;
            }
            
            $sql = "UPDATE EventRegistration SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event registration updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update event registration');
            }
            
        } catch (Exception $e) {
            error_log("Update event registration error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update event registration: ' . $e->getMessage()
            ]);
        }
    }
    
    private function deleteEventRegistration() {
        try {
            $registrationId = $_GET['id'] ?? null;
            
            if (!$registrationId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Registration ID is required'
                ]);
                return;
            }
            
            // Check if registration exists
            $existingStmt = $this->pdo->prepare("SELECT id FROM EventRegistration WHERE id = :id");
            $existingStmt->execute(['id' => $registrationId]);
            
            if (!$existingStmt->fetch()) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Event registration not found'
                ]);
                return;
            }
            
            // Delete registration
            $deleteStmt = $this->pdo->prepare("DELETE FROM EventRegistration WHERE id = :id");
            $success = $deleteStmt->execute(['id' => $registrationId]);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Event registration deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete event registration');
            }
            
        } catch (Exception $e) {
            error_log("Delete event registration error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to delete event registration: ' . $e->getMessage()
            ]);
        }
    }
    
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// Initialize and handle the request
try {
    $api = new EventRegistrationAPI();
    $api->handleRequest();
} catch (Exception $e) {
    error_log("API initialization failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'API initialization failed: ' . $e->getMessage()
    ]);
}
?>