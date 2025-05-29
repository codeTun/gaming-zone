<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/UserGameManager.php';

$gameId = isset($_GET['gameId']) ? $_GET['gameId'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

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
            'count' => count($result['leaderboard'])
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
