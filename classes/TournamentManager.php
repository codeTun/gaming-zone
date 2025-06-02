<?php
require_once '../database/new_db_connect.php';

class TournamentManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Create a new tournament
    public function createTournament($name, $description, $imageUrl, $startDate, $endDate, $prizePool) {
        try {
            $this->db->beginTransaction();
            
            $tournamentId = $this->generateUUID();
            
            // Insert into ContentItem first
            $stmt1 = $this->db->prepare("INSERT INTO ContentItem (id, name, description, imageUrl, type) VALUES (?, ?, ?, ?, 'TOURNAMENT')");
            $stmt1->execute([$tournamentId, $name, $description, $imageUrl]);
            
            // Insert into Tournament table
            $stmt2 = $this->db->prepare("INSERT INTO Tournament (id, startDate, endDate, prizePool) VALUES (?, ?, ?, ?)");
            $stmt2->execute([$tournamentId, $startDate, $endDate, $prizePool]);
            
            $this->db->commit();
            return ["success" => true, "tournament_id" => $tournamentId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Error creating tournament: " . $e->getMessage()];
        }
    }
    
    // Get all tournaments
    public function getAllTournaments() {
        try {
            $stmt = $this->db->query("SELECT ci.*, t.startDate, t.endDate, t.prizePool 
                                    FROM ContentItem ci 
                                    JOIN Tournament t ON ci.id = t.id 
                                    WHERE ci.type = 'TOURNAMENT' 
                                    ORDER BY t.startDate DESC");
            return ["success" => true, "tournaments" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching tournaments: " . $e->getMessage()];
        }
    }
    
    // Get active tournaments
    public function getActiveTournaments() {
        try {
            $stmt = $this->db->prepare("SELECT ci.*, t.startDate, t.endDate, t.prizePool 
                                       FROM ContentItem ci 
                                       JOIN Tournament t ON ci.id = t.id 
                                       WHERE ci.type = 'TOURNAMENT' 
                                       AND t.startDate <= NOW() 
                                       AND t.endDate >= NOW() 
                                       ORDER BY t.startDate ASC");
            $stmt->execute();
            
            return ["success" => true, "tournaments" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching active tournaments: " . $e->getMessage()];
        }
    }
    
    // Get upcoming tournaments
    public function getUpcomingTournaments() {
        try {
            $stmt = $this->db->prepare("SELECT ci.*, t.startDate, t.endDate, t.prizePool 
                                       FROM ContentItem ci 
                                       JOIN Tournament t ON ci.id = t.id 
                                       WHERE ci.type = 'TOURNAMENT' 
                                       AND t.startDate > NOW() 
                                       ORDER BY t.startDate ASC");
            $stmt->execute();
            
            return ["success" => true, "tournaments" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching upcoming tournaments: " . $e->getMessage()];
        }
    }
    
    // Get tournament by ID
    public function getTournamentById($tournamentId) {
        try {
            $stmt = $this->db->prepare("SELECT ci.*, t.startDate, t.endDate, t.prizePool 
                                       FROM ContentItem ci 
                                       JOIN Tournament t ON ci.id = t.id 
                                       WHERE ci.id = ? AND ci.type = 'TOURNAMENT'");
            $stmt->execute([$tournamentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Tournament not found"];
            }
            
            return ["success" => true, "tournament" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Update tournament
    public function updateTournament($tournamentId, $name, $description, $imageUrl, $startDate, $endDate, $prizePool) {
        try {
            $this->db->beginTransaction();
            
            // Update ContentItem
            $stmt1 = $this->db->prepare("UPDATE ContentItem SET name = ?, description = ?, imageUrl = ? WHERE id = ? AND type = 'TOURNAMENT'");
            $stmt1->execute([$name, $description, $imageUrl, $tournamentId]);
            
            // Update Tournament
            $stmt2 = $this->db->prepare("UPDATE Tournament SET startDate = ?, endDate = ?, prizePool = ? WHERE id = ?");
            $stmt2->execute([$startDate, $endDate, $prizePool, $tournamentId]);
            
            $this->db->commit();
            return ["success" => true, "message" => "Tournament updated successfully"];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Error updating tournament: " . $e->getMessage()];
        }
    }
    
    // Delete tournament
    public function deleteTournament($tournamentId) {
        try {
            // Deleting from ContentItem will cascade to Tournament due to foreign key
            $stmt = $this->db->prepare("DELETE FROM ContentItem WHERE id = ? AND type = 'TOURNAMENT'");
            $stmt->execute([$tournamentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Tournament not found"];
            }
            
            return ["success" => true, "message" => "Tournament deleted successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error deleting tournament: " . $e->getMessage()];
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
