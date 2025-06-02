<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration - match your existing setup
function getDatabaseConnection() {
    try {
        $host = 'localhost';
        $dbname = 'gaming_zone';
        $username = 'root';
        $password = ''; // Update if you have a password
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception('Database connection failed');
    }
}

// Helper function to execute database queries
function execute_query($conn, $query, $params = []) {
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return []; // Return empty array instead of false
    }
}

// PHP wrapper to call the Python recommendation system
function get_game_recommendations($user_id, $connection, $n_recommendations = 5) {
    try {
        // Option 1: Try to call Python script if available
        $python_script_path = __DIR__ . '/../ai_model/recommend.py';
        
        if (file_exists($python_script_path)) {
            $command = "python3 " . escapeshellarg($python_script_path) . " --user_id=" . escapeshellarg($user_id) . " --recommendations=" . intval($n_recommendations) . " 2>&1";
            $recommendations_json = shell_exec($command);
            
            if ($recommendations_json) {
                $recommendations = json_decode($recommendations_json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($recommendations)) {
                    return $recommendations;
                }
            }
        }
        
        // Option 2: Fallback to database-based recommendations
        return get_fallback_recommendations($user_id, $connection, $n_recommendations);
        
    } catch (Exception $e) {
        error_log("AI recommendation error: " . $e->getMessage());
        return get_fallback_recommendations($user_id, $connection, $n_recommendations);
    }
}

// Fallback recommendation system using database queries
function get_fallback_recommendations($user_id, $connection, $n_recommendations = 5) {
    try {
        // Get user's demographic info
        $user_query = "SELECT birthDate, gender FROM users WHERE id = ?";
        $user_data = execute_query($connection, $user_query, [$user_id]);
        
        if (empty($user_data)) {
            // If user not found, get popular games
            error_log("User not found: " . $user_id . ", getting popular games");
            return get_popular_games($connection, $n_recommendations);
        }
        
        $user = $user_data[0];
        $user_age = $user['birthDate'] ? date_diff(date_create($user['birthDate']), date_create('now'))->y : 25;
        
        // Get games the user hasn't played yet
        $recommendations_query = "
            SELECT DISTINCT 
                g.id as game_id,
                g.name as game_name,
                g.description,
                g.imageUrl as game_image,
                g.minAge,
                g.targetGender,
                g.averageRating as average_rating,
                c.name as category_name,
                (
                    -- Scoring algorithm
                    CASE 
                        WHEN g.averageRating IS NOT NULL THEN g.averageRating * 0.3
                        ELSE 2.5 * 0.3
                    END +
                    CASE 
                        WHEN g.targetGender = ? OR g.targetGender = 'All' THEN 1.5
                        ELSE 0.5
                    END +
                    CASE 
                        WHEN g.minAge <= ? THEN 1.0
                        ELSE 0.2
                    END +
                    CASE 
                        WHEN NOT EXISTS (
                            SELECT 1 FROM usergame 
                            WHERE userId = ? AND gameId = g.id
                        ) THEN 0.8
                        ELSE 0.0
                    END
                ) as predicted_rating
            FROM game g
            LEFT JOIN category c ON g.categoryId = c.id
            WHERE g.id NOT IN (
                SELECT DISTINCT gameId 
                FROM usergame 
                WHERE userId = ?
            )
            AND g.isActive = 1
            ORDER BY predicted_rating DESC, g.averageRating DESC
            LIMIT ?
        ";
        
        $recommendations = execute_query($connection, $recommendations_query, [
            $user['gender'] ?: 'All',
            $user_age,
            $user_id,
            $user_id,
            $n_recommendations
        ]);
        
        if (empty($recommendations)) {
            error_log("No personalized recommendations found for user: " . $user_id);
            return get_popular_games($connection, $n_recommendations);
        }
        
        // Format the recommendations
        return array_map(function($game) {
            return [
                'game_id' => $game['game_id'],
                'game_name' => $game['game_name'],
                'description' => $game['description'],
                'game_image' => $game['game_image'] ?: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                'category_name' => $game['category_name'] ?: 'Unknown',
                'min_age' => intval($game['minAge']),
                'target_gender' => $game['targetGender'],
                'average_rating' => floatval($game['average_rating']),
                'predicted_rating' => round(floatval($game['predicted_rating']), 2),
                'recommendation_reason' => generate_recommendation_reason($game)
            ];
        }, $recommendations);
        
    } catch (Exception $e) {
        error_log("Fallback recommendation error: " . $e->getMessage());
        return get_popular_games($connection, $n_recommendations);
    }
}

// Get popular games as last resort
function get_popular_games($connection, $n_recommendations = 5) {
    try {
        $popular_query = "
            SELECT 
                g.id as game_id,
                g.name as game_name,
                g.description,
                g.imageUrl as game_image,
                g.minAge,
                g.targetGender,
                g.averageRating as average_rating,
                c.name as category_name,
                g.averageRating as predicted_rating
            FROM game g
            LEFT JOIN category c ON g.categoryId = c.id
            WHERE g.isActive = 1
            ORDER BY g.averageRating DESC, g.createdAt DESC
            LIMIT ?
        ";
        
        $games = execute_query($connection, $popular_query, [$n_recommendations]);
        
        // Check if we got results
        if (empty($games)) {
            error_log("No games found in database, returning hardcoded recommendations");
            // Return hardcoded games if database is empty
            return [
                [
                    'game_id' => 'fallback1',
                    'game_name' => 'Adventure Quest',
                    'description' => 'An epic adventure game with stunning graphics.',
                    'game_image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                    'category_name' => 'Adventure',
                    'min_age' => 12,
                    'target_gender' => 'All',
                    'average_rating' => 4.2,
                    'predicted_rating' => 4.2,
                    'recommendation_reason' => 'Popular choice among gamers!'
                ],
                [
                    'game_id' => 'fallback2',
                    'game_name' => 'Racing Champions',
                    'description' => 'High-speed racing with realistic physics.',
                    'game_image' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400',
                    'category_name' => 'Racing',
                    'min_age' => 10,
                    'target_gender' => 'All',
                    'average_rating' => 4.0,
                    'predicted_rating' => 4.0,
                    'recommendation_reason' => 'Great racing experience!'
                ]
            ];
        }
        
        return array_map(function($game) {
            return [
                'game_id' => $game['game_id'],
                'game_name' => $game['game_name'],
                'description' => $game['description'],
                'game_image' => $game['game_image'] ?: 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                'category_name' => $game['category_name'] ?: 'Unknown',
                'min_age' => intval($game['minAge']),
                'target_gender' => $game['targetGender'],
                'average_rating' => floatval($game['average_rating']),
                'predicted_rating' => floatval($game['average_rating']),
                'recommendation_reason' => 'Popular choice among gamers!'
            ];
        }, $games);
        
    } catch (Exception $e) {
        error_log("Popular games error: " . $e->getMessage());
        // Return hardcoded fallback games
        return [
            [
                'game_id' => 'error_fallback1',
                'game_name' => 'Default Game',
                'description' => 'A great game to get started with.',
                'game_image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                'category_name' => 'Adventure',
                'min_age' => 10,
                'target_gender' => 'All',
                'average_rating' => 4.0,
                'predicted_rating' => 4.0,
                'recommendation_reason' => 'Recommended for you!'
            ]
        ];
    }
}

// Generate recommendation reason
function generate_recommendation_reason($game) {
    $reasons = [];
    
    if ($game['average_rating'] >= 4.0) {
        $reasons[] = "Highly rated";
    }
    if ($game['category_name']) {
        $reasons[] = "Great " . strtolower($game['category_name']) . " game";
    }
    if (empty($reasons)) {
        $reasons[] = "Recommended for you";
    }
    
    return implode(" • ", $reasons);
}

// Main API logic
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }
    
    // Get user ID from query parameter (sent from frontend)
    $user_id = $_GET['user_id'] ?? null;
    
    // If no user_id provided, return demo recommendations
    if (!$user_id || $user_id === 'null' || $user_id === 'undefined') {
        // Return demo recommendations for unauthenticated users
        $demo_recommendations = [
            [
                'game_id' => 'demo1',
                'game_name' => 'Epic Battle Arena',
                'description' => 'Join intense multiplayer battles in this action-packed arena fighter.',
                'game_image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                'category_name' => 'Action',
                'min_age' => 13,
                'target_gender' => 'All',
                'average_rating' => 4.5,
                'predicted_rating' => 4.3,
                'recommendation_reason' => 'Popular action game'
            ],
            [
                'game_id' => 'demo2',
                'game_name' => 'Mystic Quest',
                'description' => 'Embark on a magical journey through enchanted realms.',
                'game_image' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=400',
                'category_name' => 'RPG',
                'min_age' => 10,
                'target_gender' => 'All',
                'average_rating' => 4.2,
                'predicted_rating' => 4.0,
                'recommendation_reason' => 'Perfect for RPG lovers'
            ],
            [
                'game_id' => 'demo3',
                'game_name' => 'Speed Racer Pro',
                'description' => 'Experience high-speed racing with realistic physics.',
                'game_image' => 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400',
                'category_name' => 'Racing',
                'min_age' => 7,
                'target_gender' => 'All',
                'average_rating' => 4.0,
                'predicted_rating' => 3.8,
                'recommendation_reason' => 'Thrilling racing experience'
            ],
            [
                'game_id' => 'demo4',
                'game_name' => 'Puzzle Master',
                'description' => 'Challenge your mind with brain-teasing puzzles.',
                'game_image' => 'https://images.unsplash.com/photo-1606092195730-5d7b9af1efc5?w=400',
                'category_name' => 'Puzzle',
                'min_age' => 8,
                'target_gender' => 'All',
                'average_rating' => 4.1,
                'predicted_rating' => 3.9,
                'recommendation_reason' => 'Great for puzzle enthusiasts'
            ],
            [
                'game_id' => 'demo5',
                'game_name' => 'Space Explorer',
                'description' => 'Explore the vast universe in this sci-fi adventure.',
                'game_image' => 'https://images.unsplash.com/photo-1446776877081-d282a0f896e2?w=400',
                'category_name' => 'Adventure',
                'min_age' => 12,
                'target_gender' => 'All',
                'average_rating' => 4.3,
                'predicted_rating' => 4.1,
                'recommendation_reason' => 'Amazing space adventure'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'recommendations' => $demo_recommendations,
            'total' => count($demo_recommendations),
            'user_id' => 'demo',
            'method' => 'demo'
        ]);
        exit();
    }
    
    // Log the received user ID for debugging
    error_log("Recommendations API: Received user_id = " . $user_id);
    
    // Get number of recommendations (default 5, max 20)
    $n_recommendations = min(intval($_GET['count'] ?? 5), 20);
    if ($n_recommendations < 1) $n_recommendations = 5;
    
    // Get database connection using the corrected method
    $db = getDatabaseConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get recommendations
    $recommendations = get_game_recommendations($user_id, $db, $n_recommendations);
    
    if (empty($recommendations)) {
        // If no personalized recommendations found, return popular games
        $recommendations = get_popular_games($db, $n_recommendations);
        $method = 'popular_games_fallback';
    } else {
        $method = 'personalized_recommendations';
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'total' => count($recommendations),
        'user_id' => $user_id,
        'method' => $method
    ]);
    
} catch (Exception $e) {
    error_log("Recommendations API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load recommendations',
        'message' => $e->getMessage(),
        'debug_info' => [
            'user_id' => $_GET['user_id'] ?? 'not_provided',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>