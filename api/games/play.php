<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../classes/UserGameManager.php';
require_once '../../classes/UserManager.php';
require_once '../../helpers/ValidationHelper.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$validation = ValidationHelper::required($data, ['gameId', 'score']);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

$userId = $authResult['user']['id'];
$gameId = ValidationHelper::sanitize($data['gameId']);
$score = (int)$data['score'];

try {
    $userGameManager = new UserGameManager();
    $result = $userGameManager->recordGamePlay($userId, $gameId, $score);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
