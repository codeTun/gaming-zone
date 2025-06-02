<?php
require_once '../database/new_db_connect.php';
require_once '../helpers/UUIDHelper.php';

class GameManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Create a new game
    public function createGame($name, $description, $categoryId, $imageUrl = null, $minAge = null, $targetGender = null) {
        try {
            $this->db->beginTransaction();
            
            $gameId = $this->generateUUID();
            
            // Insert into ContentItem first
            $stmt1 = $this->db->prepare("INSERT INTO ContentItem (id, name, description, imageUrl, type) VALUES (?, ?, ?, ?, 'GAME')");
            $stmt1->execute([$gameId, $name, $description, $imageUrl]);
            
            // Insert into Game table
            $stmt2 = $this->db->prepare("INSERT INTO Game (id, categoryId, minAge, targetGender) VALUES (?, ?, ?, ?)");
            $stmt2->execute([$gameId, $categoryId, $minAge, $targetGender]);
            
            $this->db->commit();
            return ["success" => true, "game_id" => $gameId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Error creating game: " . $e->getMessage()];
        }
    }
    
    // Get all games
    public function getAllGames() {
        try {
            $stmt = $this->db->query("SELECT ci.*, g.categoryId, g.minAge, g.targetGender, g.averageRating, c.name as categoryName
                                    FROM ContentItem ci 
                                    JOIN Game g ON ci.id = g.id 
                                    JOIN Category c ON g.categoryId = c.id
                                    WHERE ci.type = 'GAME'
                                    ORDER BY ci.createdAt DESC");
            return ["success" => true, "games" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching games: " . $e->getMessage()];
        }
    }
    
    // Rate a game
    public function rateGame($userId, $gameId, $rating) {
        try {
            // Insert or update rating
            $stmt = $this->db->prepare("INSERT INTO GameRating (userId, gameId, rating) 
                                      VALUES (?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE rating = VALUES(rating), ratedAt = CURRENT_TIMESTAMP");
            $stmt->execute([$userId, $gameId, $rating]);
            
            // Update average rating
            $this->updateAverageRating($gameId);
            
            return ["success" => true, "message" => "Game rated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error rating game: " . $e->getMessage()];
        }
    }
    
    // Update average rating for a game
    private function updateAverageRating($gameId) {
        $stmt = $this->db->prepare("UPDATE Game SET averageRating = (
                                   SELECT AVG(rating) FROM GameRating WHERE gameId = ?
                                   ) WHERE id = ?");
        $stmt->execute([$gameId, $gameId]);
    }
    
    // Record game play
    public function recordGamePlay($userId, $gameId, $score) {
        try {
            $playId = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO UserGame (id, userId, gameId, score) VALUES (?, ?, ?, ?)");
            $stmt->execute([$playId, $userId, $gameId, $score]);
            
            return ["success" => true, "play_id" => $playId];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error recording game play: " . $e->getMessage()];
        }
    }
    
    // Get user's game history
    public function getUserGameHistory($userId) {
        try {
            $stmt = $this->db->prepare("SELECT ug.*, ci.name as gameName, ci.imageUrl
                                       FROM UserGame ug
                                       JOIN Game g ON ug.gameId = g.id
                                       JOIN ContentItem ci ON g.id = ci.id
                                       WHERE ug.userId = ?
                                       ORDER BY ug.playedAt DESC");
            $stmt->execute([$userId]);
            
            return ["success" => true, "game_history" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching game history: " . $e->getMessage()];
        }
    }
    
    private function generateUUID() {
        return UUIDHelper::generate();
    }
}
?>
