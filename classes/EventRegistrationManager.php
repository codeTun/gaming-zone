<?php
require_once '../database/new_db_connect.php';
require_once '../helpers/UUIDHelper.php';

class EventRegistrationManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Register user for event
    public function registerForEvent($userId, $eventId, $username, $email) {
        try {
            // Check if user is already registered
            $checkStmt = $this->db->prepare("SELECT id FROM EventRegistration WHERE userId = ? AND eventId = ?");
            $checkStmt->execute([$userId, $eventId]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "User already registered for this event"];
            }
            
            // Check if event exists
            $eventStmt = $this->db->prepare("SELECT ci.name FROM ContentItem ci 
                                           JOIN Event e ON ci.id = e.id 
                                           WHERE ci.id = ? AND ci.type = 'EVENT'");
            $eventStmt->execute([$eventId]);
            
            if ($eventStmt->rowCount() == 0) {
                return ["success" => false, "message" => "Event not found"];
            }
            
            $registrationId = UUIDHelper::generate();
            
            $stmt = $this->db->prepare("INSERT INTO EventRegistration (id, userId, eventId, username, email) 
                                      VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$registrationId, $userId, $eventId, $username, $email]);
            
            return [
                "success" => true, 
                "registration_id" => $registrationId,
                "message" => "Successfully registered for event"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Registration error: " . $e->getMessage()];
        }
    }
    
    // Get user's event registrations
    public function getUserEventRegistrations($userId) {
        try {
            $stmt = $this->db->prepare("SELECT er.*, ci.name as eventName, ci.description, ci.imageUrl,
                                              e.place, e.startDate
                                       FROM EventRegistration er
                                       JOIN Event e ON er.eventId = e.id
                                       JOIN ContentItem ci ON e.id = ci.id
                                       WHERE er.userId = ?
                                       ORDER BY er.registeredAt DESC");
            $stmt->execute([$userId]);
            
            return ["success" => true, "registrations" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching registrations: " . $e->getMessage()];
        }
    }
    
    // Get event participants
    public function getEventParticipants($eventId) {
        try {
            $stmt = $this->db->prepare("SELECT er.*, u.imageUrl as userImage
                                       FROM EventRegistration er
                                       JOIN User u ON er.userId = u.id
                                       WHERE er.eventId = ?
                                       ORDER BY er.registeredAt ASC");
            $stmt->execute([$eventId]);
            
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
            
            $stmt = $this->db->prepare("UPDATE EventRegistration SET status = ? WHERE id = ?");
            $stmt->execute([$status, $registrationId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Registration not found"];
            }
            
            return ["success" => true, "message" => "Registration status updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error updating status: " . $e->getMessage()];
        }
    }
    
    // Cancel event registration
    public function cancelEventRegistration($userId, $eventId) {
        try {
            $stmt = $this->db->prepare("UPDATE EventRegistration SET status = 'CANCELLED' 
                                      WHERE userId = ? AND eventId = ?");
            $stmt->execute([$userId, $eventId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Registration not found"];
            }
            
            return ["success" => true, "message" => "Event registration cancelled successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error cancelling registration: " . $e->getMessage()];
        }
    }
    
    // Get registration by ID
    public function getRegistrationById($registrationId) {
        try {
            $stmt = $this->db->prepare("SELECT er.*, ci.name as eventName, ci.description, ci.imageUrl,
                                              e.place, e.startDate
                                       FROM EventRegistration er
                                       JOIN Event e ON er.eventId = e.id
                                       JOIN ContentItem ci ON e.id = ci.id
                                       WHERE er.id = ?");
            $stmt->execute([$registrationId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Registration not found"];
            }
            
            return ["success" => true, "registration" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Get event registration count
    public function getEventRegistrationCount($eventId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM EventRegistration 
                                      WHERE eventId = ? AND status != 'CANCELLED'");
            $stmt->execute([$eventId]);
            
            $result = $stmt->fetch();
            return ["success" => true, "count" => (int)$result['count']];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error counting registrations: " . $e->getMessage()];
        }
    }
}
?>
