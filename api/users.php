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
                $stmt = $pdo->prepare("
                    SELECT id, name, username, email, role, birthDate, gender, imageUrl, createdAt, updatedAt 
                    FROM Users WHERE id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($user ?: ['error' => 'User not found']);
            } else {
                $stmt = $pdo->query("
                    SELECT id, name, username, email, role, birthDate, gender, imageUrl, createdAt, updatedAt 
                    FROM Users ORDER BY createdAt DESC
                ");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new user - auto-generate ID if not provided
            $id = isset($input['id']) ? $input['id'] : 'user-' . uniqid();
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO Users (id, name, username, email, password, role, birthDate, gender, imageUrl) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $input['name'],
                $input['username'],
                $input['email'],
                $hashedPassword,
                $input['role'] ?? 'USER',
                $input['birthDate'] ?? null,
                $input['gender'] ?? null,
                $input['imageUrl'] ?? null
            ]);
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'User created successfully']);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $input['name'];
            }
            if (isset($input['username'])) {
                $updateFields[] = "username = ?";
                $params[] = $input['username'];
            }
            if (isset($input['email'])) {
                $updateFields[] = "email = ?";
                $params[] = $input['email'];
            }
            if (isset($input['birthDate'])) {
                $updateFields[] = "birthDate = ?";
                $params[] = $input['birthDate'];
            }
            if (isset($input['gender'])) {
                $updateFields[] = "gender = ?";
                $params[] = $input['gender'];
            }
            if (isset($input['imageUrl'])) {
                $updateFields[] = "imageUrl = ?";
                $params[] = $input['imageUrl'];
            }
            
            $params[] = $_GET['id'];
            
            $stmt = $pdo->prepare("UPDATE Users SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'User ID required']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM Users WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
