<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../classes/UserManager.php';

// Get token from Authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    echo json_encode(['success' => false, 'message' => 'Authorization header missing or invalid']);
    exit;
}

$token = $matches[1];

$userManager = new UserManager();
$result = $userManager->refreshToken($token);

echo json_encode($result);
?>
