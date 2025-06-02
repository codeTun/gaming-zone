<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../classes/UserManager.php';
require_once '../../helpers/ResponseHelper.php';

// Get token from Authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    ResponseHelper::sendError('Authorization header missing or invalid', 401);
}

$token = $matches[1];

$userManager = new UserManager();
$result = $userManager->verifyToken($token);

echo json_encode($result);
?>
