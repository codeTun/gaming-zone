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
                $stmt = $pdo->prepare("SELECT * FROM Category WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($category ?: ['error' => 'Category not found']);
            } else {
                $stmt = $pdo->query("SELECT * FROM Category ORDER BY name ASC");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            // Create new category - auto-generate ID if not provided
            $id = isset($input['id']) ? $input['id'] : 'cat-' . uniqid();
            $stmt = $pdo->prepare("INSERT INTO Category (id, name) VALUES (?, ?)");
            $stmt->execute([$id, $input['name']]);
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Category created successfully']);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Category ID required']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE Category SET name = ? WHERE id = ?");
            $stmt->execute([$input['name'], $_GET['id']]);
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                echo json_encode(['error' => 'Category ID required']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM Category WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
