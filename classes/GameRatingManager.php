<?php
require_once '../database/new_db_connect.php';

class GameRatingManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Create or update a game rating
    public function rateGame($userId, $gameId, $rating) {
        try {
            if ($rating < 1 || $rating > 5) {
                return ["success" => false, "message" => "Rating must be between 1 and 5"];
            }
            
            $ratingId = $this->generateUUID();
            
            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing ratings
            $stmt = $this->db->prepare("INSERT INTO GameRating (id, userId, gameId, rating) 
                                      VALUES (?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE rating = VALUES(rating), ratedAt = CURRENT_TIMESTAMP");
            $stmt->execute([$ratingId, $userId, $gameId, $rating]);
            
            // Update average rating for the game
            $this->updateGameAverageRating($gameId);
            
            return ["success" => true, "message" => "Game rated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error rating game: " . $e->getMessage()];
        }
    }
    
    // Get rating by user and game
    public function getUserGameRating($userId, $gameId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM GameRating WHERE userId = ? AND gameId = ?");
            $stmt->execute([$userId, $gameId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Rating not found"];
            }
            
            return ["success" => true, "rating" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Get all ratings for a game
    public function getGameRatings($gameId) {
        try {
            $stmt = $this->db->prepare("SELECT gr.*, u.username, u.name 
                                       FROM GameRating gr 
                                       JOIN User u ON gr.userId = u.id 
                                       WHERE gr.gameId = ? 
                                       ORDER BY gr.ratedAt DESC");
            $stmt->execute([$gameId]);
            
            return ["success" => true, "ratings" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching game ratings: " . $e->getMessage()];
        }
    }
    
    // Get all ratings by a user
    public function getUserRatings($userId) {
        try {
            $stmt = $this->db->prepare("SELECT gr.*, ci.name as gameName, ci.imageUrl 
                                       FROM GameRating gr 
                                       JOIN Game g ON gr.gameId = g.id 
                                       JOIN ContentItem ci ON g.id = ci.id 
                                       WHERE gr.userId = ? 
                                       ORDER BY gr.ratedAt DESC");
            $stmt->execute([$userId]);
            
            return ["success" => true, "ratings" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching user ratings: " . $e->getMessage()];
        }
    }
    
    // Delete a rating
    public function deleteRating($userId, $gameId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM GameRating WHERE userId = ? AND gameId = ?");
            $stmt->execute([$userId, $gameId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Rating not found"];
            }
            
            // Update average rating for the game
            $this->updateGameAverageRating($gameId);
            
            return ["success" => true, "message" => "Rating deleted successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error deleting rating: " . $e->getMessage()];
        }
    }
    
    // Update average rating for a game
    private function updateGameAverageRating($gameId) {
        $stmt = $this->db->prepare("UPDATE Game SET averageRating = (
                                   SELECT COALESCE(AVG(rating), 0) FROM GameRating WHERE gameId = ?
                                   ) WHERE id = ?");
        $stmt->execute([$gameId, $gameId]);
    }
    
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
