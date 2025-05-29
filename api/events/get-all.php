<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/EventManager.php';

try {
    $eventManager = new EventManager();
    $result = $eventManager->getAllEvents();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result['events'],
            'count' => count($result['events'])
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
