<?php
// filepath: c:\xampp\htdocs\gaming-zone\api\auth\login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

try {
    // Check if required files exist - FIXED PATH
    $userManagerPath = __DIR__ . '/../../classes/UserManager.php';
    if (!file_exists($userManagerPath)) {
        throw new Exception('UserManager class file not found at: ' . $userManagerPath);
    }

    require_once $userManagerPath;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    if (!isset($data['email']) || !isset($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }

    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($data['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password cannot be empty']);
        exit;
    }

    // Check if UserManager class exists
    if (!class_exists('UserManager')) {
        throw new Exception('UserManager class not found. Please check the class file.');
    }

    $userManager = new UserManager();
    $result = $userManager->loginUser($email, $password);

    echo json_encode($result);

} catch (Exception $e) {
    error_log('Login API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage() // Remove in production
    ]);
} catch (Error $e) {
    error_log('Login API Fatal Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server configuration error. Please contact support.',
        'debug' => $e->getMessage() // Remove in production
    ]);
}
?>