<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/CategoryManager.php';

try {
    $categoryManager = new CategoryManager();
    $result = $categoryManager->getAllCategories();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => $result['categories'],
            'count' => count($result['categories'])
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
