<?php
require_once 'db_connect.php';

class TournamentManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Create a new tournament
    public function createTournament($name, $gameId, $startDate, $endDate, $prize, $maxParticipants) {
        try {
            $stmt = $this->db->prepare("INSERT INTO Tournoi (nom, idJeu, dateDebut, dateFin, prix, maxParticipant) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $gameId, $startDate, $endDate, $prize, $maxParticipants]);
            return ["success" => true, "tournament_id" => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error creating tournament: " . $e->getMessage()];
        }
    }
    
    // Get all tournaments
    public function getAllTournaments() {
        try {
            $stmt = $this->db->query("SELECT t.*, j.titre as jeuTitre, 
                                            (SELECT COUNT(*) FROM UtilisateurTournoi WHERE idTournoi = t.id) as participantCount 
                                     FROM Tournoi t 
                                     JOIN Jeu j ON t.idJeu = j.id 
                                     ORDER BY t.dateDebut DESC");
            return ["success" => true, "tournaments" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching tournaments: " . $e->getMessage()];
        }
    }
    
    // Get tournament by ID
    public function getTournamentById($tournamentId) {
        try {
            $stmt = $this->db->prepare("SELECT t.*, j.titre as jeuTitre, j.description as jeuDescription, 
                                               (SELECT COUNT(*) FROM UtilisateurTournoi WHERE idTournoi = t.id) as participantCount 
                                        FROM Tournoi t 
                                        JOIN Jeu j ON t.idJeu = j.id 
                                        WHERE t.id = ?");
            $stmt->execute([$tournamentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Tournament not found"];
            }
            
            return ["success" => true, "tournament" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Register user for tournament
    public function registerUserForTournament($userId, $tournamentId) {
        try {
            // Check if user is already registered
            $checkStmt = $this->db->prepare("SELECT * FROM UtilisateurTournoi WHERE idUtilisateur = ? AND idTournoi = ?");
            $checkStmt->execute([$userId, $tournamentId]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "User already registered for this tournament"];
            }
            
            // Check if tournament is full
            $tournamentStmt = $this->db->prepare("SELECT maxParticipant, 
                                                        (SELECT COUNT(*) FROM UtilisateurTournoi WHERE idTournoi = ?) as currentCount 
                                                 FROM Tournoi WHERE id = ?");
            $tournamentStmt->execute([$tournamentId, $tournamentId]);
            $tournamentData = $tournamentStmt->fetch();
            
            if ($tournamentData['currentCount'] >= $tournamentData['maxParticipant']) {
                return ["success" => false, "message" => "Tournament is already full"];
            }
            
            // Register user
            $stmt = $this->db->prepare("INSERT INTO UtilisateurTournoi (idUtilisateur, idTournoi) VALUES (?, ?)");
            $stmt->execute([$userId, $tournamentId]);
            
            return ["success" => true, "message" => "Successfully registered for tournament"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error registering for tournament: " . $e->getMessage()];
        }
    }
    
    // Unregister user from tournament
    public function unregisterUserFromTournament($userId, $tournamentId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM UtilisateurTournoi WHERE idUtilisateur = ? AND idTournoi = ?");
            $stmt->execute([$userId, $tournamentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not registered for this tournament"];
            }
            
            return ["success" => true, "message" => "Successfully unregistered from tournament"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error unregistering from tournament: " . $e->getMessage()];
        }
    }
    
    // Get tournaments a user is registered for
    public function getUserTournaments($userId) {
        try {
            $stmt = $this->db->prepare("SELECT t.*, j.titre as jeuTitre, ut.dateInscription
                                       FROM Tournoi t
                                       JOIN Jeu j ON t.idJeu = j.id
                                       JOIN UtilisateurTournoi ut ON t.id = ut.idTournoi
                                       WHERE ut.idUtilisateur = ?
                                       ORDER BY t.dateDebut");
            $stmt->execute([$userId]);
            
            return ["success" => true, "tournaments" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching user tournaments: " . $e->getMessage()];
        }
    }
    
    // Get participants of a tournament
    public function getTournamentParticipants($tournamentId) {
        try {
            $stmt = $this->db->prepare("SELECT u.id, u.nomUtilisateur, u.email, ut.dateInscription
                                       FROM Utilisateur u
                                       JOIN UtilisateurTournoi ut ON u.id = ut.idUtilisateur
                                       WHERE ut.idTournoi = ?
                                       ORDER BY ut.dateInscription");
            $stmt->execute([$tournamentId]);
            
            return ["success" => true, "participants" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching participants: " . $e->getMessage()];
        }
    }
}
?>
