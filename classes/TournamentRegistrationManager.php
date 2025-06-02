<?php
require_once '../database/new_db_connect.php';
require_once '../helpers/UUIDHelper.php';

class TournamentRegistrationManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Register user for tournament
    public function registerForTournament($userId, $tournamentId, $username, $email, $teamName) {
        try {
            // Check if user is already registered
            $checkStmt = $this->db->prepare("SELECT id FROM TournamentRegistration WHERE userId = ? AND tournamentId = ?");
            $checkStmt->execute([$userId, $tournamentId]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "User already registered for this tournament"];
            }
            
            // Check if tournament exists
            $tournamentStmt = $this->db->prepare("SELECT ci.name FROM ContentItem ci 
                                                JOIN Tournament t ON ci.id = t.id 
                                                WHERE ci.id = ? AND ci.type = 'TOURNAMENT'");
            $tournamentStmt->execute([$tournamentId]);
            
            if ($tournamentStmt->rowCount() == 0) {
                return ["success" => false, "message" => "Tournament not found"];
            }
            
            $registrationId = UUIDHelper::generate();
            
            $stmt = $this->db->prepare("INSERT INTO TournamentRegistration (id, userId, tournamentId, username, email, teamName) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$registrationId, $userId, $tournamentId, $username, $email, $teamName]);
            
            return [
                "success" => true, 
                "registration_id" => $registrationId,
                "message" => "Successfully registered for tournament"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Registration error: " . $e->getMessage()];
        }
    }
    
    // Get user's tournament registrations
    public function getUserTournamentRegistrations($userId) {
        try {
            $stmt = $this->db->prepare("SELECT tr.*, ci.name as tournamentName, ci.description, ci.imageUrl,
                                              t.startDate, t.endDate, t.prizePool
                                       FROM TournamentRegistration tr
                                       JOIN Tournament t ON tr.tournamentId = t.id
                                       JOIN ContentItem ci ON t.id = ci.id
                                       WHERE tr.userId = ?
                                       ORDER BY tr.registeredAt DESC");
            $stmt->execute([$userId]);
            
            return ["success" => true, "registrations" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching registrations: " . $e->getMessage()];
        }
    }
    
    // Get tournament participants
    public function getTournamentParticipants($tournamentId) {
        try {
            $stmt = $this->db->prepare("SELECT tr.*, u.imageUrl as userImage
                                       FROM TournamentRegistration tr
                                       JOIN User u ON tr.userId = u.id
                                       WHERE tr.tournamentId = ?
                                       ORDER BY tr.registeredAt ASC");
            $stmt->execute([$tournamentId]);
            
            return ["success" => true, "participants" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching participants: " . $e->getMessage()];
        }
    }
    
    // Update registration status
    public function updateRegistrationStatus($registrationId, $status) {
        try {
            $validStatuses = ['PENDING', 'CONFIRMED', 'CANCELLED'];
            if (!in_array($status, $validStatuses)) {
                return ["success" => false, "message" => "Invalid status"];
            }
            
            $stmt = $this->db->prepare("UPDATE TournamentRegistration SET status = ? WHERE id = ?");
            $stmt->execute([$status, $registrationId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Registration not found"];
            }
            
            return ["success" => true, "message" => "Registration status updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error updating status: " . $e->getMessage()];
        }
    }
    
    // Cancel tournament registration
    public function cancelTournamentRegistration($userId, $tournamentId) {
        try {
            $stmt = $this->db->prepare("UPDATE TournamentRegistration SET status = 'CANCELLED' 
                                      WHERE userId = ? AND tournamentId = ?");
            $stmt->execute([$userId, $tournamentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Registration not found"];
            }
            
            return ["success" => true, "message" => "Tournament registration cancelled successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error cancelling registration: " . $e->getMessage()];
        }
    }
    
    // Get registration by ID
    public function getRegistrationById($registrationId) {
        try {
            $stmt = $this->db->prepare("SELECT tr.*, ci.name as tournamentName, ci.description, ci.imageUrl,
                                              t.startDate, t.endDate, t.prizePool
                                       FROM TournamentRegistration tr
                                       JOIN Tournament t ON tr.tournamentId = t.id
                                       JOIN ContentItem ci ON t.id = ci.id
                                       WHERE tr.id = ?");
            $stmt->execute([$registrationId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Registration not found"];
            }
            
            return ["success" => true, "registration" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Get tournament registration count
    public function getTournamentRegistrationCount($tournamentId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM TournamentRegistration 
                                      WHERE tournamentId = ? AND status != 'CANCELLED'");
            $stmt->execute([$tournamentId]);
            
            $result = $stmt->fetch();
            return ["success" => true, "count" => (int)$result['count']];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error counting registrations: " . $e->getMessage()];
        }
    }
}
?>
