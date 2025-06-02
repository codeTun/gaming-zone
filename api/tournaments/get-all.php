<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/TournamentManager.php';

try {
    $tournamentManager = new TournamentManager();
    $result = $tournamentManager->getAllTournaments();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result['tournaments'],
            'count' => count($result['tournaments'])
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
