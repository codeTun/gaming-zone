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
                    SELECT ci.id, ci.name as title, ci.description, ci.imageUrl, 
                           e.place, e.startDate as eventDate,
                           DATE_FORMAT(e.startDate, '%H:%i') as eventTime,
                           ci.createdAt, ci.createdAt as updatedAt
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
                    SELECT ci.id, ci.name as title, ci.description, ci.imageUrl, 
                           e.place, e.startDate as eventDate,
                           DATE_FORMAT(e.startDate, '%H:%i') as eventTime,
                           ci.createdAt, ci.createdAt as updatedAt
                    FROM ContentItem ci
                    JOIN Event e ON ci.id = e.id
                    WHERE ci.type = 'EVENT'
                    ORDER BY e.startDate DESC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new event
            $id = 'event-' . uniqid();
            
            // Validate required fields
            if (!isset($input['title']) || !isset($input['place']) || !isset($input['eventDate'])) {
                echo json_encode(['error' => 'Missing required fields: title, place, eventDate']);
                break;
            }
            
            // Prepare eventTime - if provided, append to eventDate
            $eventDateTime = $input['eventDate'];
            if (isset($input['eventTime']) && $input['eventTime']) {
                $eventDateTime .= ' ' . $input['eventTime'] . ':00';
            } else {
                $eventDateTime .= ' 10:00:00';
            }
            
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Insert into ContentItem
                $stmt = $pdo->prepare("
                    INSERT INTO ContentItem (id, name, description, imageUrl, type) 
                    VALUES (?, ?, ?, ?, 'EVENT')
                ");
                $stmt->execute([
                    $id,
                    $input['title'],
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
                    $eventDateTime
                ]);
                
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'id' => $id, 
                    'message' => 'Event created successfully'
                ]);
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;

        case 'PUT':
            // Update event
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Event ID required']);
                break;
            }
            
            $pdo->beginTransaction();
            
            try {
                $contentFields = [];
                $contentValues = [];
                
                // Build ContentItem update
                if (isset($input['title'])) {
                    $contentFields[] = "name = ?";
                    $contentValues[] = $input['title'];
                }
                if (isset($input['description'])) {
                    $contentFields[] = "description = ?";
                    $contentValues[] = $input['description'];
                }
                if (isset($input['imageUrl'])) {
                    $contentFields[] = "imageUrl = ?";
                    $contentValues[] = $input['imageUrl'];
                }
                
                if (!empty($contentFields)) {
                    $contentValues[] = $_GET['id'];
                    $stmt = $pdo->prepare("
                        UPDATE ContentItem 
                        SET " . implode(', ', $contentFields) . "
                        WHERE id = ?
                    ");
                    $stmt->execute($contentValues);
                }
                
                // Build Event table update
                $eventFields = [];
                $eventValues = [];
                
                if (isset($input['place'])) {
                    $eventFields[] = "place = ?";
                    $eventValues[] = $input['place'];
                }
                
                if (isset($input['eventDate'])) {
                    $eventDateTime = $input['eventDate'];
                    if (isset($input['eventTime']) && $input['eventTime']) {
                        $eventDateTime .= ' ' . $input['eventTime'] . ':00';
                    } else {
                        $eventDateTime .= ' 10:00:00';
                    }
                    $eventFields[] = "startDate = ?";
                    $eventValues[] = $eventDateTime;
                }
                
                if (!empty($eventFields)) {
                    $eventValues[] = $_GET['id'];
                    $stmt = $pdo->prepare("
                        UPDATE Event 
                        SET " . implode(', ', $eventFields) . "
                        WHERE id = ?
                    ");
                    $stmt->execute($eventValues);
                }
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
            } catch (Exception $e) {
                $pdo->rollback();
                throw $e;
            }
            break;

        case 'DELETE':
            // Delete event (ContentItem cascade will handle Event table)
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
