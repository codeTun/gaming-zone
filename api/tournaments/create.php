<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../classes/TournamentManager.php';
require_once '../../classes/UserManager.php';
require_once '../../helpers/ValidationHelper.php';

// Verify authentication and admin role
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

if ($authResult['user']['role'] !== 'ADMIN') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$validation = ValidationHelper::required($data, ['name', 'description', 'startDate', 'endDate']);
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'message' => $validation['message']]);
    exit;
}

$name = ValidationHelper::sanitize($data['name']);
$description = ValidationHelper::sanitize($data['description']);
$imageUrl = isset($data['imageUrl']) ? ValidationHelper::sanitize($data['imageUrl']) : null;
$startDate = $data['startDate'];
$endDate = $data['endDate'];
$prizePool = isset($data['prizePool']) ? (float)$data['prizePool'] : null;

// Validate dates
$startDateValidation = ValidationHelper::date($startDate, 'Y-m-d H:i:s');
if (!$startDateValidation['valid']) {
    echo json_encode(['success' => false, 'message' => 'Invalid start date format']);
    exit;
}

$endDateValidation = ValidationHelper::date($endDate, 'Y-m-d H:i:s');
if (!$endDateValidation['valid']) {
    echo json_encode(['success' => false, 'message' => 'Invalid end date format']);
    exit;
}

try {
    $tournamentManager = new TournamentManager();
    $result = $tournamentManager->createTournament($name, $description, $imageUrl, $startDate, $endDate, $prizePool);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
