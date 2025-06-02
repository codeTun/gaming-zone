<?php
require_once '../database/new_db_connect.php';

class CategoryManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Create a new category
    public function createCategory($name) {
        try {
            $categoryId = $this->generateUUID();
            $stmt = $this->db->prepare("INSERT INTO Category (id, name) VALUES (?, ?)");
            $stmt->execute([$categoryId, $name]);
            
            return ["success" => true, "category_id" => $categoryId];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error creating category: " . $e->getMessage()];
        }
    }
    
    // Get all categories
    public function getAllCategories() {
        try {
            $stmt = $this->db->query("SELECT * FROM Category ORDER BY name");
            return ["success" => true, "categories" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching categories: " . $e->getMessage()];
        }
    }
    
    // Get category by ID
    public function getCategoryById($categoryId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Category WHERE id = ?");
            $stmt->execute([$categoryId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Category not found"];
            }
            
            return ["success" => true, "category" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Update category
    public function updateCategory($categoryId, $name) {
        try {
            $stmt = $this->db->prepare("UPDATE Category SET name = ? WHERE id = ?");
            $stmt->execute([$name, $categoryId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Category not found"];
            }
            
            return ["success" => true, "message" => "Category updated successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error updating category: " . $e->getMessage()];
        }
    }
    
    // Delete category
    public function deleteCategory($categoryId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM Category WHERE id = ?");
            $stmt->execute([$categoryId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Category not found"];
            }
            
            return ["success" => true, "message" => "Category deleted successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error deleting category: " . $e->getMessage()];
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
