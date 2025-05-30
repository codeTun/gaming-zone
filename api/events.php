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
                // Get specific event
                $stmt = $pdo->prepare("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl, e.place, e.startDate, ci.createdAt
                    FROM ContentItem ci
                    JOIN Event e ON ci.id = e.id
                    WHERE ci.id = ? AND ci.type = 'EVENT'
                ");
                $stmt->execute([$_GET['id']]);
                $event = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($event ?: ['error' => 'Event not found']);
            } else {
                // Get all events
                $stmt = $pdo->query("
                    SELECT ci.id, ci.name, ci.description, ci.imageUrl, e.place, e.startDate, ci.createdAt
                    FROM ContentItem ci
                    JOIN Event e ON ci.id = e.id
                    WHERE ci.type = 'EVENT'
                    ORDER BY e.startDate ASC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new event - auto-generate ID if not provided
            $id = isset($input['id']) ? $input['id'] : 'event-' . uniqid();
            
            $pdo->beginTransaction();
            
            // Insert into ContentItem
            $stmt = $pdo->prepare("
                INSERT INTO ContentItem (id, name, description, imageUrl, type) 
                VALUES (?, ?, ?, ?, 'EVENT')
            ");
            $stmt->execute([
                $id,
                $input['name'],
                $input['description'] ?? null,
                $input['imageUrl'] ?? null
            ]);
            
            // Insert into Event
            $stmt = $pdo->prepare("
                INSERT INTO Event (id, place, startDate) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $input['place'],
                $input['startDate']
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Event created successfully']);
            break;

        case 'PUT':
            // Update event
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Event ID required']);
                break;
            }
            
            $pdo->beginTransaction();
            
            // Update ContentItem
            $stmt = $pdo->prepare("
                UPDATE ContentItem 
                SET name = ?, description = ?, imageUrl = ? 
                WHERE id = ? AND type = 'EVENT'
            ");
            $stmt->execute([
                $input['name'],
                $input['description'] ?? null,
                $input['imageUrl'] ?? null,
                $_GET['id']
            ]);
            
            // Update Event
            $stmt = $pdo->prepare("
                UPDATE Event 
                SET place = ?, startDate = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $input['place'],
                $input['startDate'],
                $_GET['id']
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            break;

        case 'DELETE':
            // Delete event
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Event ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("DELETE FROM ContentItem WHERE id = ? AND type = 'EVENT'");
            $stmt->execute([$_GET['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
