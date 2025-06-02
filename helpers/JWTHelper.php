<?php
require_once 'EnvLoader.php';

class JWTHelper {
    private static $secretKey = null;
    private static $algorithm = null;
    
    private static function loadConfig() {
        if (self::$secretKey === null) {
            EnvLoader::load();
            self::$secretKey = EnvLoader::get('JWT_SECRET_KEY', 'fallback-secret-key-change-this');
            self::$algorithm = EnvLoader::get('JWT_ALGORITHM', 'HS256');
        }
    }
    
    // Generate JWT token
    public static function generateToken($payload) {
        self::loadConfig();
        
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secretKey, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    // Verify and decode JWT token
    public static function verifyToken($token) {
        self::loadConfig();
        
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return ['success' => false, 'message' => 'Invalid token format'];
            }
            
            list($base64Header, $base64Payload, $base64Signature) = $parts;
            
            // Verify signature
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secretKey, true);
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            if (!hash_equals($base64Signature, $expectedSignature)) {
                return ['success' => false, 'message' => 'Invalid token signature'];
            }
            
            // Decode payload
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['success' => false, 'message' => 'Token expired'];
            }
            
            return ['success' => true, 'payload' => $payload];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Token verification failed: ' . $e->getMessage()];
        }
    }
    
    // Create payload for user
    public static function createUserPayload($user, $expirationHours = null) {
        EnvLoader::load();
        
        if ($expirationHours === null) {
            $expirationHours = (int) EnvLoader::get('JWT_EXPIRATION_HOURS', 168); // 7 days default
        }
        
        return [
            'iss' => EnvLoader::get('APP_NAME', 'gaming-zone'),
            'aud' => 'gaming-zone-users',
            'iat' => time(),
            'exp' => time() + ($expirationHours * 3600),
            'user_id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'gender' => $user['gender']
        ];
    }
}
?>
