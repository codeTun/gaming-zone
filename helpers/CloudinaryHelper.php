<?php
require_once 'EnvLoader.php';

class CloudinaryHelper {
    private static $cloudName = null;
    private static $apiKey = null;
    private static $apiSecret = null;
    private static $uploadPreset = null;
    
    private static function loadConfig() {
        if (self::$cloudName === null) {
            EnvLoader::load();
            self::$cloudName = EnvLoader::get('CLOUDINARY_CLOUD_NAME');
            self::$apiKey = EnvLoader::get('CLOUDINARY_API_KEY');
            self::$apiSecret = EnvLoader::get('CLOUDINARY_API_SECRET');
            self::$uploadPreset = EnvLoader::get('CLOUDINARY_UPLOAD_PRESET', 'gaming_zone_uploads');
        }
    }
    
    // Upload image to Cloudinary
    public static function uploadImage($imageFile, $folder = 'gaming-zone', $publicId = null) {
        self::loadConfig();
        
        if (!self::$cloudName || !self::$apiKey || !self::$apiSecret) {
            return ['success' => false, 'message' => 'Cloudinary configuration missing'];
        }
        
        // Validate file
        $validationResult = self::validateImage($imageFile);
        if (!$validationResult['success']) {
            return $validationResult;
        }
        
        try {
            $timestamp = time();
            $params = [
                'timestamp' => $timestamp,
                'folder' => $folder
            ];
            
            if ($publicId) {
                $params['public_id'] = $publicId;
            }
            
            // Generate signature
            $signature = self::generateSignature($params);
            $params['signature'] = $signature;
            $params['api_key'] = self::$apiKey;
            
            // Prepare file for upload
            if (is_string($imageFile)) {
                // Base64 string
                $params['file'] = $imageFile;
            } else {
                // File upload
                $params['file'] = new CURLFile($imageFile['tmp_name'], $imageFile['type'], $imageFile['name']);
            }
            
            // Upload to Cloudinary
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/" . self::$cloudName . "/image/upload");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return ['success' => false, 'message' => 'Upload failed with HTTP code: ' . $httpCode];
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['secure_url'])) {
                return [
                    'success' => true,
                    'url' => $result['secure_url'],
                    'public_id' => $result['public_id'],
                    'format' => $result['format'],
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'bytes' => $result['bytes']
                ];
            } else {
                return ['success' => false, 'message' => 'Upload failed: ' . ($result['error']['message'] ?? 'Unknown error')];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
        }
    }
    
    // Delete image from Cloudinary
    public static function deleteImage($publicId) {
        self::loadConfig();
        
        try {
            $timestamp = time();
            $params = [
                'public_id' => $publicId,
                'timestamp' => $timestamp
            ];
            
            $signature = self::generateSignature($params);
            $params['signature'] = $signature;
            $params['api_key'] = self::$apiKey;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/" . self::$cloudName . "/image/destroy");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            return [
                'success' => $result['result'] === 'ok',
                'message' => $result['result'] === 'ok' ? 'Image deleted successfully' : 'Delete failed'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Delete error: ' . $e->getMessage()];
        }
    }
    
    // Generate transformation URL
    public static function getTransformationUrl($publicId, $transformations = []) {
        self::loadConfig();
        
        $baseUrl = "https://res.cloudinary.com/" . self::$cloudName . "/image/upload/";
        
        if (!empty($transformations)) {
            $transformString = '';
            foreach ($transformations as $key => $value) {
                $transformString .= $key . '_' . $value . ',';
            }
            $transformString = rtrim($transformString, ',') . '/';
            $baseUrl .= $transformString;
        }
        
        return $baseUrl . $publicId;
    }
    
    // Get optimized image URL with common transformations
    public static function getOptimizedUrl($publicId, $width = null, $height = null, $quality = 'auto', $format = 'auto') {
        $transformations = [
            'q' => $quality,
            'f' => $format
        ];
        
        if ($width) $transformations['w'] = $width;
        if ($height) $transformations['h'] = $height;
        if ($width && $height) $transformations['c'] = 'fill';
        
        return self::getTransformationUrl($publicId, $transformations);
    }
    
    // Validate uploaded image
    private static function validateImage($imageFile) {
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (is_string($imageFile)) {
            // Base64 validation
            if (strlen($imageFile) > $maxSize) {
                return ['success' => false, 'message' => 'Image too large (max 5MB)'];
            }
            return ['success' => true];
        }
        
        // File upload validation
        if (!isset($imageFile['tmp_name']) || !is_uploaded_file($imageFile['tmp_name'])) {
            return ['success' => false, 'message' => 'Invalid file upload'];
        }
        
        if ($imageFile['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Image too large (max 5MB)'];
        }
        
        if (!in_array($imageFile['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid image format. Allowed: JPEG, PNG, GIF, WebP'];
        }
        
        return ['success' => true];
    }
    
    // Generate Cloudinary signature
    private static function generateSignature($params) {
        ksort($params);
        $stringToSign = '';
        foreach ($params as $key => $value) {
            $stringToSign .= $key . '=' . $value . '&';
        }
        $stringToSign = rtrim($stringToSign, '&') . self::$apiSecret;
        
        return sha1($stringToSign);
    }
}
?>
