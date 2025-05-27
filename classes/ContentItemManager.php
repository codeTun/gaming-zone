<?php
require_once '../database/new_db_connect.php';

class ContentItemManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Create a new content item
    public function createContentItem($name, $description, $imageUrl, $type) {
        try {
            $contentId = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO ContentItem (id, name, description, imageUrl, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$contentId, $name, $description, $imageUrl, $type]);
            
            return ["success" => true, "content_id" => $contentId];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error creating content item: " . $e->getMessage()];
        }
    }
    
    // Get all content items
    public function getAllContentItems() {
        try {
            $stmt = $this->db->query("SELECT * FROM ContentItem ORDER BY createdAt DESC");
            return ["success" => true, "content_items" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching content items: " . $e->getMessage()];
        }
    }
    
    // Get content items by type
    public function getContentItemsByType($type) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ContentItem WHERE type = ? ORDER BY createdAt DESC");
            $stmt->execute([$type]);
            
            return ["success" => true, "content_items" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching content items: " . $e->getMessage()];
        }
    }
    
    // Get content item by ID
    public function getContentItemById($contentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ContentItem WHERE id = ?");
            $stmt->execute([$contentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Content item not found"];
            }
            
            return ["success" => true, "content_item" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Update content item
    public function updateContentItem($contentId, $name, $description, $imageUrl) {
        try {
            $stmt = $this->db->prepare("UPDATE ContentItem SET name = ?, description = ?, imageUrl = ? WHERE id = ?");
            $stmt->execute([$name, $description, $imageUrl, $contentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Content item not found"];
            }
            
            return ["success" => true, "message" => "Content item updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error updating content item: " . $e->getMessage()];
        }
    }
    
    // Delete content item
    public function deleteContentItem($contentId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM ContentItem WHERE id = ?");
            $stmt->execute([$contentId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Content item not found"];
            }
            
            return ["success" => true, "message" => "Content item deleted successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error deleting content item: " . $e->getMessage()];
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
