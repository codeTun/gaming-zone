<?php
class ResponseHelper {
    
    // Send success response
    public static function success($data = null, $message = 'Success') {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        return $response;
    }
    
    // Send error response
    public static function error($message = 'Error occurred', $code = null) {
        $response = ['success' => false, 'message' => $message];
        if ($code !== null) {
            $response['error_code'] = $code;
        }
        return $response;
    }
    
    // Send JSON response with HTTP status
    public static function sendJson($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    // Send success JSON response
    public static function sendSuccess($data = null, $message = 'Success', $httpCode = 200) {
        self::sendJson(self::success($data, $message), $httpCode);
    }
    
    // Send error JSON response
    public static function sendError($message = 'Error occurred', $httpCode = 400, $code = null) {
        self::sendJson(self::error($message, $code), $httpCode);
    }
}
?>
