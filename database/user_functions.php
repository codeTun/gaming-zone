<?php
require_once 'db_connect.php';

class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Register a new user
    public function registerUser($username, $email, $password, $isAdmin = false) {
        try {
            // Check if user already exists
            $checkStmt = $this->db->prepare("SELECT id FROM Utilisateur WHERE email = ? OR nomUtilisateur = ?");
            $checkStmt->execute([$email, $username]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "User already exists with this email or username"];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Get role ID
            $roleId = $isAdmin ? 1 : 2; // 1 for ADMIN, 2 for USER
            
            // Insert user
            $stmt = $this->db->prepare("INSERT INTO Utilisateur (nomUtilisateur, email, motDePasse, roleId) 
                                       VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $roleId]);
            
            return ["success" => true, "user_id" => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Registration error: " . $e->getMessage()];
        }
    }
    
    // User login
    public function loginUser($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT u.id, u.nomUtilisateur, u.email, u.motDePasse, r.nom as role 
                                      FROM Utilisateur u 
                                      JOIN Role r ON u.roleId = r.id
                                      WHERE u.email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not found"];
            }
            
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['motDePasse'])) {
                // Create a token
                $token = $this->generateToken($user['id']);
                
                // Return user data without password
                unset($user['motDePasse']);
                return [
                    "success" => true,
                    "user" => $user,
                    "token" => $token
                ];
            } else {
                return ["success" => false, "message" => "Invalid password"];
            }
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Login error: " . $e->getMessage()];
        }
    }
    
    // Generate authentication token
    private function generateToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt = $this->db->prepare("INSERT INTO Jeton (valeur, idUtilisateur, dateExpiration) VALUES (?, ?, ?)");
        $stmt->execute([$token, $userId, $expiry]);
        
        return $token;
    }
    
    // Verify token
    public function verifyToken($token) {
        try {
            $stmt = $this->db->prepare("SELECT j.id, j.idUtilisateur, j.dateExpiration, u.nomUtilisateur, r.nom as role
                                      FROM Jeton j
                                      JOIN Utilisateur u ON j.idUtilisateur = u.id
                                      JOIN Role r ON u.roleId = r.id
                                      WHERE j.valeur = ? AND (j.dateExpiration IS NULL OR j.dateExpiration > NOW())");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Invalid or expired token"];
            }
            
            return ["success" => true, "tokenData" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Token verification error: " . $e->getMessage()];
        }
    }
    
    // Get user by ID
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("SELECT u.id, u.nomUtilisateur, u.email, r.nom as role
                                      FROM Utilisateur u
                                      JOIN Role r ON u.roleId = r.id
                                      WHERE u.id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not found"];
            }
            
            return ["success" => true, "user" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
}
?>
