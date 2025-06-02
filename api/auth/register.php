<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../classes/UserManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$required = ['name', 'username', 'email', 'password'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize inputs
$name = htmlspecialchars(trim($data['name']));
$username = htmlspecialchars(trim($data['username']));
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$password = trim($data['password']);
$birthDate = isset($data['birthDate']) ? $data['birthDate'] : null;
$gender = isset($data['gender']) ? strtoupper($data['gender']) : null;

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate gender if provided
if ($gender && !in_array($gender, ['MALE', 'FEMALE'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid gender value']);
    exit;
}

// Map frontend gender values to backend values
if ($gender === 'M') $gender = 'MALE';
if ($gender === 'F') $gender = 'FEMALE';

$userManager = new UserManager();
$result = $userManager->registerUser($name, $username, $email, $password, $birthDate, $gender);

// Add token type to response for frontend
if ($result['success']) {
    $result['message'] = 'User registered successfully';
}

echo json_encode($result);
?>
