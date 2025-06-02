<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($method) {
        case 'POST':
            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'create_demo_user':
                        // Check if demo user exists
                        $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
                        $stmt->execute(['iheb@example.com']);
                        
                        if (!$stmt->fetch()) {
                            // Create demo user
                            $userId = 'user-001';
                            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO Users (id, name, username, email, password, role, birthDate, gender) 
                                VALUES (?, ?, ?, ?, ?, 'USER', '1995-01-01', 'MALE')
                            ");
                            $stmt->execute([
                                $userId,
                                'Elazheri Iheb',
                                'iheb_gamer',
                                'iheb@example.com',
                                $hashedPassword
                            ]);
                            
                            echo json_encode(['success' => true, 'message' => 'Demo user created']);
                        } else {
                            echo json_encode(['success' => true, 'message' => 'Demo user already exists']);
                        }
                        break;

                    case 'register':
                        // Validate required fields
                        $requiredFields = ['name', 'username', 'email', 'password', 'birthDate', 'gender'];
                        foreach ($requiredFields as $field) {
                            if (!isset($input[$field]) || empty(trim($input[$field]))) {
                                echo json_encode(['error' => "Missing required field: $field"]);
                                exit;
                            }
                        }

                        // Validate email format
                        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                            echo json_encode(['error' => 'Invalid email format']);
                            break;
                        }

                        // Validate username format (alphanumeric and underscore only)
                        if (!preg_match('/^[a-zA-Z0-9_]+$/', $input['username'])) {
                            echo json_encode(['error' => 'Username can only contain letters, numbers, and underscores']);
                            break;
                        }

                        // Validate password length
                        if (strlen($input['password']) < 6) {
                            echo json_encode(['error' => 'Password must be at least 6 characters long']);
                            break;
                        }

                        // Validate age (must be at least 13 years old)
                        $birthDate = new DateTime($input['birthDate']);
                        $today = new DateTime();
                        $age = $today->diff($birthDate)->y;
                        if ($age < 13) {
                            echo json_encode(['error' => 'You must be at least 13 years old to register']);
                            break;
                        }

                        // Check if email already exists
                        $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
                        $stmt->execute([$input['email']]);
                        if ($stmt->fetch()) {
                            echo json_encode(['error' => 'Email address is already registered']);
                            break;
                        }

                        // Check if username already exists
                        $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = ?");
                        $stmt->execute([$input['username']]);
                        if ($stmt->fetch()) {
                            echo json_encode(['error' => 'Username is already taken']);
                            break;
                        }

                        // Create new user
                        $userId = 'user-' . uniqid();
                        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare("
                            INSERT INTO Users (id, name, username, email, password, role, birthDate, gender) 
                            VALUES (?, ?, ?, ?, ?, 'USER', ?, ?)
                        ");
                        $stmt->execute([
                            $userId,
                            trim($input['name']),
                            trim($input['username']),
                            trim($input['email']),
                            $hashedPassword,
                            $input['birthDate'],
                            $input['gender']
                        ]);

                        // Generate authentication token
                        $tokenId = 'token-' . uniqid();
                        $token = bin2hex(random_bytes(32));
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

                        $stmt = $pdo->prepare("
                            INSERT INTO Token (id, token, type, expiresAt, userId) 
                            VALUES (?, ?, 'AUTH', ?, ?)
                        ");
                        $stmt->execute([$tokenId, $token, $expiresAt, $userId]);

                        echo json_encode([
                            'success' => true,
                            'message' => 'Registration successful',
                            'userId' => $userId,
                            'token' => $token
                        ]);
                        break;

                    case 'login':
                        // Validate required fields
                        if (!isset($input['email']) || !isset($input['password'])) {
                            echo json_encode(['error' => 'Email and password are required']);
                            break;
                        }

                        // Get user by email
                        $stmt = $pdo->prepare("SELECT id, name, username, email, password, role FROM Users WHERE email = ?");
                        $stmt->execute([$input['email']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($user && password_verify($input['password'], $user['password'])) {
                            // Store user in session
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];

                            // Generate new auth token
                            $tokenId = 'token-' . uniqid();
                            $token = bin2hex(random_bytes(32));
                            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

                            // Delete old tokens for this user
                            $stmt = $pdo->prepare("DELETE FROM Token WHERE userId = ? AND type = 'AUTH'");
                            $stmt->execute([$user['id']]);

                            // Insert new token
                            $stmt = $pdo->prepare("
                                INSERT INTO Token (id, token, type, expiresAt, userId) 
                                VALUES (?, ?, 'AUTH', ?, ?)
                            ");
                            $stmt->execute([$tokenId, $token, $expiresAt, $user['id']]);

                            unset($user['password']); // Don't send password back

                            echo json_encode([
                                'success' => true,
                                'user' => $user,
                                'token' => $token,
                                'message' => 'Login successful'
                            ]);
                        } else {
                            echo json_encode(['error' => 'Invalid email or password']);
                        }
                        break;

                    case 'logout':
                        // Delete user tokens
                        if (isset($_SESSION['user_id'])) {
                            $stmt = $pdo->prepare("DELETE FROM Token WHERE userId = ? AND type = 'AUTH'");
                            $stmt->execute([$_SESSION['user_id']]);
                        }

                        session_destroy();
                        echo json_encode(['success' => true, 'message' => 'Logout successful']);
                        break;

                    default:
                        echo json_encode(['error' => 'Invalid action']);
                        break;
                }
            } else {
                echo json_encode(['error' => 'Action parameter required']);
            }
            break;

        case 'GET':
            // Get current user session
            if (isset($_SESSION['user_id'])) {
                // Verify token is still valid
                $stmt = $pdo->prepare("
                    SELECT token FROM Token 
                    WHERE userId = ? AND type = 'AUTH' AND expiresAt > NOW()
                ");
                $stmt->execute([$_SESSION['user_id']]);
                
                if ($stmt->fetch()) {
                    echo json_encode([
                        'authenticated' => true,
                        'user' => [
                            'id' => $_SESSION['user_id'],
                            'name' => $_SESSION['user_name'],
                            'email' => $_SESSION['user_email'],
                            'role' => $_SESSION['user_role']
                        ]
                    ]);
                } else {
                    // Token expired, destroy session
                    session_destroy();
                    echo json_encode(['authenticated' => false, 'message' => 'Session expired']);
                }
            } else {
                echo json_encode(['authenticated' => false]);
            }
            break;

        default:
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
