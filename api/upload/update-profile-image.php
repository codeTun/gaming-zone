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

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    // Get current user image to delete old one
    $stmt = $db->prepare("SELECT imageUrl FROM User WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();
    $oldImageUrl = $currentUser['imageUrl'];
    
    // Upload new image
    $publicId = 'user_' . $userId . '_' . time();
    
    if (isset($_FILES['image'])) {
        $uploadResult = CloudinaryHelper::uploadImage($_FILES['image'], 'users/profile', $publicId);
    } else {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!isset($data['image'])) {
            echo json_encode(['success' => false, 'message' => 'No image provided']);
            exit;
        }
        
        $uploadResult = CloudinaryHelper::uploadImage($data['image'], 'users/profile', $publicId);
    }
    
    if (!$uploadResult['success']) {
        echo json_encode($uploadResult);
        exit;
    }
    
    // Update user record
    $stmt = $db->prepare("UPDATE User SET imageUrl = ?, updatedAt = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$uploadResult['url'], $userId]);
    
    // Delete old image if exists
    if ($oldImageUrl && strpos($oldImageUrl, 'cloudinary.com') !== false) {
        // Extract public_id from URL
        $urlParts = parse_url($oldImageUrl);
        $pathParts = explode('/', $urlParts['path']);
        $publicIdWithFormat = end($pathParts);
        $oldPublicId = pathinfo($publicIdWithFormat, PATHINFO_FILENAME);
        
        // Add folder path if exists
        $folderIndex = array_search('upload', $pathParts);
        if ($folderIndex !== false && isset($pathParts[$folderIndex + 1])) {
            $folderPath = implode('/', array_slice($pathParts, $folderIndex + 1, -1));
            if ($folderPath) {
                $oldPublicId = $folderPath . '/' . $oldPublicId;
            }
        }
        
        CloudinaryHelper::deleteImage($oldPublicId);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile image updated successfully',
        'imageUrl' => $uploadResult['url'],
        'optimizedUrl' => CloudinaryHelper::getOptimizedUrl($uploadResult['public_id'], 200, 200)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
