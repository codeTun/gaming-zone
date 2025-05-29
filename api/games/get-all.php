<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/GameManager.php';

try {
    $gameManager = new GameManager();
    $result = $gameManager->getAllGames();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result['games'],
            'count' => count($result['games'])
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
