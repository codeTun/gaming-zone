<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/UserGameManager.php';
require_once '../../helpers/ValidationHelper.php';

$gameId = isset($_GET['gameId']) ? ValidationHelper::sanitize($_GET['gameId']) : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate limit
if ($limit > 100) $limit = 100; // Prevent too large requests
if ($limit < 1) $limit = 10;

try {
    $userGameManager = new UserGameManager();
    
    if ($gameId) {
        // Game-specific leaderboard
        $result = $userGameManager->getGameLeaderboard($gameId, $limit);
    } else {
        // Overall leaderboard
        $result = $userGameManager->getOverallLeaderboard($limit);
    }
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result['leaderboard'],
            'count' => count($result['leaderboard']),
            'gameId' => $gameId,
            'limit' => $limit
        ]);
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
