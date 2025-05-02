<?php
require_once 'db_connect.php';

class GameManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Add new category
    public function addCategory($name, $description) {
        try {
            $stmt = $this->db->prepare("INSERT INTO Categorie (nom, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            return ["success" => true, "category_id" => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error adding category: " . $e->getMessage()];
        }
    }
    
    // Get all categories
    public function getAllCategories() {
        try {
            $stmt = $this->db->query("SELECT * FROM Categorie ORDER BY nom");
            return ["success" => true, "categories" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching categories: " . $e->getMessage()];
        }
    }
    
    // Add new game
    public function addGame($title, $description, $categoryId, $publishDate, $image = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO Jeu (titre, description, idCategorie, datePublication, image) 
                                      VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $categoryId, $publishDate, $image]);
            return ["success" => true, "game_id" => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error adding game: " . $e->getMessage()];
        }
    }
    
    // Get all games
    public function getAllGames() {
        try {
            $stmt = $this->db->query("SELECT j.*, c.nom as categorie 
                                    FROM Jeu j 
                                    JOIN Categorie c ON j.idCategorie = c.id 
                                    ORDER BY j.datePublication DESC");
            return ["success" => true, "games" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching games: " . $e->getMessage()];
        }
    }
    
    // Get game by ID
    public function getGameById($gameId) {
        try {
            $stmt = $this->db->prepare("SELECT j.*, c.nom as categorie 
                                       FROM Jeu j 
                                       JOIN Categorie c ON j.idCategorie = c.id 
                                       WHERE j.id = ?");
            $stmt->execute([$gameId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Game not found"];
            }
            
            return ["success" => true, "game" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Add like to a game
    public function likeGame($userId, $gameId) {
        try {
            // Check if already liked
            $checkStmt = $this->db->prepare("SELECT id FROM Aime WHERE idUtilisateur = ? AND idJeu = ?");
            $checkStmt->execute([$userId, $gameId]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "Game already liked by this user"];
            }
            
            $stmt = $this->db->prepare("INSERT INTO Aime (idUtilisateur, idJeu) VALUES (?, ?)");
            $stmt->execute([$userId, $gameId]);
            return ["success" => true, "like_id" => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error liking game: " . $e->getMessage()];
        }
    }
    
    // Remove like from a game
    public function unlikeGame($userId, $gameId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM Aime WHERE idUtilisateur = ? AND idJeu = ?");
            $stmt->execute([$userId, $gameId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Like not found"];
            }
            
            return ["success" => true, "message" => "Game unliked successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error unliking game: " . $e->getMessage()];
        }
    }
    
    // Get games liked by a user
    public function getLikedGames($userId) {
        try {
            $stmt = $this->db->prepare("SELECT j.*, c.nom as categorie, a.horodatage as likedAt
                                       FROM Jeu j
                                       JOIN Categorie c ON j.idCategorie = c.id
                                       JOIN Aime a ON j.id = a.idJeu
                                       WHERE a.idUtilisateur = ?
                                       ORDER BY a.horodatage DESC");
            $stmt->execute([$userId]);
            return ["success" => true, "liked_games" => $stmt->fetchAll()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error fetching liked games: " . $e->getMessage()];
        }
    }
}
?>
