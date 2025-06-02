<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../classes/CloudinaryHelper.php';
require_once '../../classes/UserManager.php';

// Verify authentication
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    echo json_encode(['success' => false, 'message' => 'Authorization required']);
    exit;
}

$token = $matches[1];
$userManager = new UserManager();
$authResult = $userManager->verifyToken($token);

if (!$authResult['success']) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$userId = $authResult['user']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Handle different upload types
if (isset($_FILES['image'])) {
    // File upload
    $imageFile = $_FILES['image'];
    $folder = isset($_POST['folder']) ? $_POST['folder'] : 'users';
    $publicId = isset($_POST['public_id']) ? $_POST['public_id'] : $userId . '_' . time();
    
} else {
    // Base64 upload
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['image'])) {
        echo json_encode(['success' => false, 'message' => 'No image provided']);
        exit;
    }
    
    $imageFile = $data['image'];
    $folder = isset($data['folder']) ? $data['folder'] : 'users';
    $publicId = isset($data['public_id']) ? $data['public_id'] : $userId . '_' . time();
}

// Upload to Cloudinary
$result = CloudinaryHelper::uploadImage($imageFile, $folder, $publicId);

if ($result['success']) {
    // Update user's imageUrl in database if this is a profile picture
    if ($folder === 'users') {
        try {
            $db = DatabaseConnection::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE User SET imageUrl = ? WHERE id = ?");
            $stmt->execute([$result['url'], $userId]);
        } catch (PDOException $e) {
            error_log("Failed to update user image URL: " . $e->getMessage());
        }
    }
}

echo json_encode($result);
?>
