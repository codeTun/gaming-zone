<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../classes/UserManager.php';
require_once '../../classes/UserGameManager.php';
require_once '../../classes/TournamentRegistrationManager.php';
require_once '../../classes/EventRegistrationManager.php';

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

try {
    $userId = $authResult['user']['id'];
    
    // Get user details
    $userResult = $userManager->getUserById($userId);
    
    if (!$userResult['success']) {
        echo json_encode($userResult);
        exit;
    }
    
    // Get user's game statistics
    $userGameManager = new UserGameManager();
    $gameStatsResult = $userGameManager->getUserGameStats($userId);
    
    // Get user's tournament registrations
    $tournamentRegManager = new TournamentRegistrationManager();
    $tournamentRegsResult = $tournamentRegManager->getUserTournamentRegistrations($userId);
    
    // Get user's event registrations
    $eventRegManager = new EventRegistrationManager();
    $eventRegsResult = $eventRegManager->getUserEventRegistrations($userId);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $userResult['user'],
            'gameStats' => $gameStatsResult['success'] ? $gameStatsResult['game_stats'] : [],
            'tournamentRegistrations' => $tournamentRegsResult['success'] ? $tournamentRegsResult['registrations'] : [],
            'eventRegistrations' => $eventRegsResult['success'] ? $eventRegsResult['registrations'] : []
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
