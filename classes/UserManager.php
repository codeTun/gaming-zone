<?php
// filepath: c:\xampp\htdocs\gaming-zone\classes\UserManager.php

class UserManager {
    private $db;
    
    public function __construct() {
        try {
            // Try to include database connection with error handling
            $dbPath = __DIR__ . '/../database/new_db_connect.php';
            if (file_exists($dbPath)) {
                require_once $dbPath;
                $this->db = DatabaseConnection::getInstance()->getConnection();
            } else {
                // Fallback to simple database connection
                $this->connectToDatabase();
            }
        } catch (Exception $e) {
            // Fallback database connection
            $this->connectToDatabase();
        }
    }

    private function connectToDatabase() {
        try {
            // Basic database connection - update with your credentials
            $host = 'localhost';
            $dbname = 'gaming_zone';
            $username = 'root';
            $password = '';
            
            $this->db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    // Register a new user
    public function registerUser($name, $username, $email, $password, $birthDate = null, $gender = null, $role = 'USER') {
        try {
            // Check if user already exists
            $checkStmt = $this->db->prepare("SELECT id FROM Users WHERE email = ? OR username = ?");
            $checkStmt->execute([$email, $username]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "User already exists with this email or username"];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate ID
            $userId = 'user-' . uniqid();
            
            // Insert user
            $stmt = $this->db->prepare("INSERT INTO Users (id, name, username, email, password, role, birthDate, gender) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $username, $email, $hashedPassword, $role, $birthDate, $gender]);
            
            // Prepare user data for response
            $userData = [
                'id' => $userId,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'gender' => $gender
            ];
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            
            return [
                "success" => true, 
                "user" => $userData,
                "token" => $token,
                "token_type" => "Bearer"
            ];
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            return ["success" => false, "message" => "Registration error occurred"];
        }
    }
    
    // User login
    public function loginUser($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not found"];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Remove password from user data
                unset($user['password']);
                
                // Generate simple token
                $token = bin2hex(random_bytes(32));
                
                return [
                    "success" => true,
                    "user" => $user,
                    "token" => $token,
                    "token_type" => "Bearer"
                ];
            } else {
                return ["success" => false, "message" => "Invalid password"];
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            return ["success" => false, "message" => "Login error occurred"];
        }
    }
    
    // Store token in database for tracking/blacklisting
    private function storeToken($userId, $token) {
        try {
            $tokenId = 'token-' . uniqid(); // Simple ID generation instead of UUID
            $expiry = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt = $this->db->prepare("INSERT INTO Token (id, token, type, expiresAt, userId) VALUES (?, ?, 'AUTH', ?, ?)");
            $stmt->execute([$tokenId, $token, $expiry, $userId]);
        } catch (PDOException $e) {
            // Log error but don't fail the authentication process
            error_log("Failed to store token: " . $e->getMessage());
        }
    }
    
    // Verify token (simplified without JWT dependency)
    public function verifyToken($token) {
        try {
            $stmt = $this->db->prepare("SELECT t.*, u.id, u.name, u.username, u.email, u.role, u.gender, u.imageUrl
                                      FROM Token t
                                      JOIN Users u ON t.userId = u.id
                                      WHERE t.token = ? AND (t.expiresAt IS NULL OR t.expiresAt > NOW())");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Token not found or expired in database"];
            }
            
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                "success" => true, 
                "user" => [
                    'id' => $tokenData['id'],
                    'name' => $tokenData['name'],
                    'username' => $tokenData['username'],
                    'email' => $tokenData['email'],
                    'role' => $tokenData['role'],
                    'gender' => $tokenData['gender'],
                    'imageUrl' => $tokenData['imageUrl']
                ]
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Token verification error: " . $e->getMessage()];
        }
    }
    
    // Logout user (blacklist token)
    public function logoutUser($token) {
        try {
            $stmt = $this->db->prepare("DELETE FROM Token WHERE token = ?");
            $stmt->execute([$token]);
            
            return ["success" => true, "message" => "Logged out successfully"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Logout error: " . $e->getMessage()];
        }
    }
    
    // Refresh token (simplified without JWT)
    public function refreshToken($oldToken) {
        $verifyResult = $this->verifyToken($oldToken);
        
        if (!$verifyResult['success']) {
            return $verifyResult;
        }
        
        $user = $verifyResult['user'];
        
        // Generate new token
        $newToken = bin2hex(random_bytes(32));
        
        try {
            // Remove old token
            $this->db->prepare("DELETE FROM Token WHERE token = ?")->execute([$oldToken]);
            
            // Store new token
            $this->storeToken($user['id'], $newToken);
            
            return [
                "success" => true,
                "token" => $newToken,
                "token_type" => "Bearer",
                "user" => $user
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Token refresh error: " . $e->getMessage()];
        }
    }
    
    // Get user by ID
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("SELECT id, name, username, email, role, birthDate, gender, imageUrl, createdAt FROM Users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not found"];
            }
            
            return ["success" => true, "user" => $stmt->fetch(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
}
?>