<?php
require_once '../database/new_db_connect.php';

class EventManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Create a new event
    public function createEvent($name, $description, $imageUrl, $place, $startDate) {
        try {
            $this->db->beginTransaction();
            
            $eventId = $this->generateUUID();
            
            // Insert into ContentItem first
            $stmt1 = $this->db->prepare("INSERT INTO ContentItem (id, name, description, imageUrl, type) VALUES (?, ?, ?, ?, 'EVENT')");
            $stmt1->execute([$eventId, $name, $description, $imageUrl]);
            
            // Insert into Event table
            $stmt2 = $this->db->prepare("INSERT INTO Event (id, place, startDate) VALUES (?, ?, ?)");
            $stmt2->execute([$eventId, $place, $startDate]);
            
            $this->db->commit();
            return ["success" => true, "event_id" => $eventId];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Error creating event: " . $e->getMessage()];
        }
    }
    
    // Get all events
    public function getAllEvents() {
        try {
            $stmt = $this->db->query("SELECT ci.*, e.place, e.startDate 
                                    FROM ContentItem ci 
                                    JOIN Event e ON ci.id = e.id 
                                    WHERE ci.type = 'EVENT' 
                                    ORDER BY e.startDate ASC");
            return ["success" => true, "events" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching events: " . $e->getMessage()];
        }
    }
    
    // Get upcoming events
    public function getUpcomingEvents() {
        try {
            $stmt = $this->db->prepare("SELECT ci.*, e.place, e.startDate 
                                       FROM ContentItem ci 
                                       JOIN Event e ON ci.id = e.id 
                                       WHERE ci.type = 'EVENT' AND e.startDate > NOW() 
                                       ORDER BY e.startDate ASC");
            $stmt->execute();
            
            return ["success" => true, "events" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching upcoming events: " . $e->getMessage()];
        }
    }
    
    // Get event by ID
    public function getEventById($eventId) {
        try {
            $stmt = $this->db->prepare("SELECT ci.*, e.place, e.startDate 
                                       FROM ContentItem ci 
                                       JOIN Event e ON ci.id = e.id 
                                       WHERE ci.id = ? AND ci.type = 'EVENT'");
            $stmt->execute([$eventId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Event not found"];
            }
            
            return ["success" => true, "event" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Update event
    public function updateEvent($eventId, $name, $description, $imageUrl, $place, $startDate) {
        try {
            $this->db->beginTransaction();
            
            // Update ContentItem
            $stmt1 = $this->db->prepare("UPDATE ContentItem SET name = ?, description = ?, imageUrl = ? WHERE id = ? AND type = 'EVENT'");
            $stmt1->execute([$name, $description, $imageUrl, $eventId]);
            
            // Update Event
            $stmt2 = $this->db->prepare("UPDATE Event SET place = ?, startDate = ? WHERE id = ?");
            $stmt2->execute([$place, $startDate, $eventId]);
            
            $this->db->commit();
            return ["success" => true, "message" => "Event updated successfully"];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Error updating event: " . $e->getMessage()];
        }
    }
    
    // Delete event
    public function deleteEvent($eventId) {
        try {
            // Deleting from ContentItem will cascade to Event due to foreign key
            $stmt = $this->db->prepare("DELETE FROM ContentItem WHERE id = ? AND type = 'EVENT'");
            $stmt->execute([$eventId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Event not found"];
            }
            
            return ["success" => true, "message" => "Event deleted successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error deleting event: " . $e->getMessage()];
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
