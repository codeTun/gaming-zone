<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../classes/GameManager.php';
require_once '../../helpers/ValidationHelper.php';

$gameId = isset($_GET['id']) ? ValidationHelper::sanitize($_GET['id']) : null;

if (!$gameId) {
    echo json_encode(['success' => false, 'message' => 'Game ID is required']);
    exit;
}

try {
    $db = DatabaseConnection::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT ci.*, g.categoryId, g.minAge, g.targetGender, g.averageRating, c.name as categoryName
                         FROM ContentItem ci 
                         JOIN Game g ON ci.id = g.id 
                         JOIN Category c ON g.categoryId = c.id
                         WHERE ci.id = ? AND ci.type = 'GAME'");
    $stmt->execute([$gameId]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Game not found']);
        exit;
    }
    
    $game = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'data' => $game
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
