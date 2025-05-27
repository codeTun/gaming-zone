<?php
require_once '../database/new_db_connect.php';

class UserGameManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Record a game play session
    public function recordGamePlay($userId, $gameId, $score) {
        try {
            $playId = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO UserGame (id, userId, gameId, score) VALUES (?, ?, ?, ?)");
            $stmt->execute([$playId, $userId, $gameId, $score]);
            
            return ["success" => true, "play_id" => $playId, "message" => "Game play recorded successfully"];
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
    
    // Get game statistics for a user
    public function getUserGameStats($userId) {
        try {
            $stmt = $this->db->prepare("SELECT 
                                           g.id as gameId,
                                           ci.name as gameName,
                                           ci.imageUrl,
                                           COUNT(ug.id) as totalPlays,
                                           MAX(ug.score) as highScore,
                                           AVG(ug.score) as averageScore,
                                           MIN(ug.playedAt) as firstPlayed,
                                           MAX(ug.playedAt) as lastPlayed
                                       FROM UserGame ug 
                                       JOIN Game g ON ug.gameId = g.id 
                                       JOIN ContentItem ci ON g.id = ci.id 
                                       WHERE ug.userId = ? 
                                       GROUP BY g.id, ci.name, ci.imageUrl 
                                       ORDER BY totalPlays DESC, highScore DESC");
            $stmt->execute([$userId]);
            
            return ["success" => true, "game_stats" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching game statistics: " . $e->getMessage()];
        }
    }
    
    // Get leaderboard for a specific game
    public function getGameLeaderboard($gameId, $limit = 10) {
        try {
            $stmt = $this->db->prepare("SELECT 
                                           u.id as userId,
                                           u.username,
                                           u.name,
                                           u.imageUrl as userImage,
                                           MAX(ug.score) as highScore,
                                           COUNT(ug.id) as totalPlays,
                                           MAX(ug.playedAt) as lastPlayed
                                       FROM UserGame ug 
                                       JOIN User u ON ug.userId = u.id 
                                       WHERE ug.gameId = ? 
                                       GROUP BY u.id, u.username, u.name, u.imageUrl 
                                       ORDER BY highScore DESC, lastPlayed DESC 
                                       LIMIT ?");
            $stmt->execute([$gameId, $limit]);
            
            return ["success" => true, "leaderboard" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching leaderboard: " . $e->getMessage()];
        }
    }
    
    // Get user's best score for a specific game
    public function getUserBestScore($userId, $gameId) {
        try {
            $stmt = $this->db->prepare("SELECT MAX(score) as bestScore, COUNT(*) as totalPlays 
                                       FROM UserGame 
                                       WHERE userId = ? AND gameId = ?");
            $stmt->execute([$userId, $gameId]);
            
            $result = $stmt->fetch();
            if ($result['bestScore'] === null) {
                return ["success" => false, "message" => "No games played yet"];
            }
            
            return ["success" => true, "best_score" => $result['bestScore'], "total_plays" => $result['totalPlays']];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching best score: " . $e->getMessage()];
        }
    }
    
    // Get overall leaderboard across all games
    public function getOverallLeaderboard($limit = 10) {
        try {
            $stmt = $this->db->prepare("SELECT 
                                           u.id as userId,
                                           u.username,
                                           u.name,
                                           u.imageUrl as userImage,
                                           COUNT(DISTINCT ug.gameId) as gamesPlayed,
                                           COUNT(ug.id) as totalPlays,
                                           SUM(ug.score) as totalScore,
                                           AVG(ug.score) as averageScore
                                       FROM UserGame ug 
                                       JOIN User u ON ug.userId = u.id 
                                       GROUP BY u.id, u.username, u.name, u.imageUrl 
                                       ORDER BY totalScore DESC, gamesPlayed DESC 
                                       LIMIT ?");
            $stmt->execute([$limit]);
            
            return ["success" => true, "leaderboard" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching overall leaderboard: " . $e->getMessage()];
        }
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
