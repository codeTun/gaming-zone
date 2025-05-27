<?php
require_once '../database/new_db_connect.php';
require_once '../helpers/JWTHelper.php';
require_once '../helpers/UUIDHelper.php';

class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConnection::getInstance()->getConnection();
    }
    
    // Register a new user
    public function registerUser($name, $username, $email, $password, $birthDate = null, $gender = null, $role = 'USER') {
        try {
            // Check if user already exists
            $checkStmt = $this->db->prepare("SELECT id FROM User WHERE email = ? OR username = ?");
            $checkStmt->execute([$email, $username]);
            
            if ($checkStmt->rowCount() > 0) {
                return ["success" => false, "message" => "User already exists with this email or username"];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate UUID for user
            $userId = $this->generateUUID();
            
            // Insert user
            $stmt = $this->db->prepare("INSERT INTO User (id, name, username, email, password, role, birthDate, gender) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $username, $email, $hashedPassword, $role, $birthDate, $gender]);
            
            // Prepare user data for token
            $userData = [
                'id' => $userId,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'gender' => $gender
            ];
            
            // Generate JWT token
            $payload = JWTHelper::createUserPayload($userData);
            $jwtToken = JWTHelper::generateToken($payload);
            
            // Store token in database for tracking
            $this->storeToken($userId, $jwtToken);
            
            return [
                "success" => true, 
                "user" => $userData,
                "token" => $jwtToken,
                "token_type" => "Bearer"
            ];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Registration error: " . $e->getMessage()];
        }
    }
    
    // User login
    public function loginUser($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM User WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not found"];
            }
            
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password'])) {
                // Remove password from user data
                unset($user['password']);
                
                // Generate JWT token
                $payload = JWTHelper::createUserPayload($user);
                $jwtToken = JWTHelper::generateToken($payload);
                
                // Store token in database for tracking
                $this->storeToken($user['id'], $jwtToken);
                
                return [
                    "success" => true,
                    "user" => $user,
                    "token" => $jwtToken,
                    "token_type" => "Bearer"
                ];
            } else {
                return ["success" => false, "message" => "Invalid password"];
            }
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Login error: " . $e->getMessage()];
        }
    }
    
    // Store token in database for tracking/blacklisting
    private function storeToken($userId, $token) {
        try {
            $tokenId = $this->generateUUID();
            $expiry = date('Y-m-d H:i:s', strtotime('+7 days'));
            
            $stmt = $this->db->prepare("INSERT INTO Token (id, token, type, expiresAt, userId) VALUES (?, ?, 'AUTH', ?, ?)");
            $stmt->execute([$tokenId, $token, $expiry, $userId]);
        } catch (PDOException $e) {
            // Log error but don't fail the authentication process
            error_log("Failed to store token: " . $e->getMessage());
        }
    }
    
    // Verify JWT token
    public function verifyToken($token) {
        $result = JWTHelper::verifyToken($token);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Check if token exists in database and is not blacklisted
        try {
            $stmt = $this->db->prepare("SELECT t.*, u.id, u.name, u.username, u.email, u.role, u.gender, u.imageUrl
                                      FROM Token t
                                      JOIN User u ON t.userId = u.id
                                      WHERE t.token = ? AND (t.expiresAt IS NULL OR t.expiresAt > NOW())");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "Token not found or expired in database"];
            }
            
            $tokenData = $stmt->fetch();
            
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
                ],
                "payload" => $result['payload']
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
    
    // Refresh token
    public function refreshToken($oldToken) {
        $verifyResult = $this->verifyToken($oldToken);
        
        if (!$verifyResult['success']) {
            return $verifyResult;
        }
        
        $user = $verifyResult['user'];
        
        // Generate new token
        $payload = JWTHelper::createUserPayload($user);
        $newToken = JWTHelper::generateToken($payload);
        
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
            $stmt = $this->db->prepare("SELECT id, name, username, email, role, birthDate, gender, imageUrl, createdAt FROM User WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() == 0) {
                return ["success" => false, "message" => "User not found"];
            }
            
            return ["success" => true, "user" => $stmt->fetch()];
        } catch (PDOException $e) {
            return ["success" => false, "message" => "Error: " . $e->getMessage()];
        }
    }
    
    // Generate UUID
    private function generateUUID() {
        return UUIDHelper::generate();
    }
}
?>
