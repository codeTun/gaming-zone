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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/database/new_db_connect.php';

class RecommendationAPI {
    private $pdo;
    
    public function __construct() {
        try {
            $database = DatabaseConnection::getInstance();
            $this->pdo = $database->getConnection();
            error_log("✅ Recommendations API: Database connection successful");
        } catch (Exception $e) {
            error_log("❌ Recommendations API: Database connection failed: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit();
        }
    }
    
    public function handleRequest() {
        try {
            error_log("🔄 Recommendations API: Handling request - Method: " . $_SERVER['REQUEST_METHOD']);
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'GET':
                case 'POST':
                    $this->getRecommendations();
                    break;
                default:
                    error_log("❌ Method not allowed: " . $method);
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    break;
            }
        } catch (Exception $e) {
            error_log("❌ Recommendations API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getRecommendations() {
        try {
            error_log("📥 Getting recommendations...");
            
            // Get input data
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $rawInput = file_get_contents('php://input');
                error_log("📤 POST input: " . $rawInput);
                $input = json_decode($rawInput, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON input: ' . json_last_error_msg());
                }
                
                $userId = $input['user_id'] ?? null;
                $numRecommendations = intval($input['recommendations'] ?? 5);
            } else {
                $userId = $_GET['user_id'] ?? null;
                $numRecommendations = intval($_GET['recommendations'] ?? 5);
            }
            
            error_log("🔍 User ID: $userId, Recommendations: $numRecommendations");
            
            if (!$userId) {
                error_log("❌ Missing user ID");
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'User ID is required'
                ]);
                return;
            }
            
            // Validate user exists
            error_log("👤 Validating user exists...");
            $userStmt = $this->pdo->prepare("SELECT id FROM Users WHERE id = :userId");
            $userStmt->execute(['userId' => $userId]);
            
            if (!$userStmt->fetch()) {
                error_log("❌ User not found: $userId");
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]);
                return;
            }
            
            error_log("✅ User validated successfully");
            
            // For now, skip AI and go directly to content-based recommendations
            // This will help us debug the basic functionality first
            error_log("🎯 Getting content-based recommendations...");
            $contentRecommendations = $this->getContentBasedRecommendations($userId, $numRecommendations);
            
            error_log("📊 Found " . count($contentRecommendations) . " content-based recommendations");
            
            echo json_encode([
                'success' => true,
                'recommendations' => $contentRecommendations,
                'type' => 'content_based',
                'message' => 'Content-based recommendations (AI temporarily disabled for debugging)',
                'debug_info' => [
                    'user_id' => $userId,
                    'count' => count($contentRecommendations),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("❌ Get recommendations error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get recommendations: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
    }
    
    private function getContentBasedRecommendations($userId, $numRecommendations) {
        try {
            error_log("🔍 Getting user preferences for user: $userId");
            
            // Get user's preferences based on ratings and game history
            $stmt = $this->pdo->prepare("
                SELECT 
                    g.categoryId,
                    AVG(gr.rating) as avg_rating,
                    COUNT(*) as rating_count
                FROM GameRating gr
                JOIN Game g ON gr.gameId = g.id
                WHERE gr.userId = :userId AND gr.rating >= 4
                GROUP BY g.categoryId
                ORDER BY avg_rating DESC, rating_count DESC
                LIMIT 3
            ");
            
            $stmt->execute(['userId' => $userId]);
            $preferredCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("📈 Found " . count($preferredCategories) . " preferred categories");
            
            if (empty($preferredCategories)) {
                error_log("👶 New user detected - returning popular games");
                return $this->getPopularGames($numRecommendations);
            }
            
            // Log preferred categories
            foreach ($preferredCategories as $cat) {
                error_log("⭐ Preferred category: " . $cat['categoryId'] . " (avg: " . $cat['avg_rating'] . ")");
            }
            
            // Get games from preferred categories that user hasn't played
            $categoryIds = array_column($preferredCategories, 'categoryId');
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            
            $sql = "
                SELECT 
                    g.id as game_id,
                    g.name as game_name,
                    g.description,
                    g.imageUrl as game_image,
                    g.averageRating as average_rating,
                    g.minAge,
                    g.targetGender,
                    c.name as category_name,
                    g.averageRating as predicted_rating
                FROM Game g
                LEFT JOIN Category c ON g.categoryId = c.id
                WHERE g.categoryId IN ($placeholders)
                AND g.id NOT IN (
                    SELECT DISTINCT gameId 
                    FROM GameRating 
                    WHERE userId = ?
                )
                AND g.id NOT IN (
                    SELECT DISTINCT gameId 
                    FROM UserGame 
                    WHERE userId = ?
                )
                ORDER BY g.averageRating DESC, RAND()
                LIMIT ?
            ";
            
            $params = array_merge($categoryIds, [$userId, $userId, $numRecommendations]);
            error_log("🔍 SQL params: " . json_encode($params));
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("🎮 Found " . count($results) . " category-based recommendations");
            
            if (empty($results)) {
                error_log("🔄 No category-based games found, falling back to popular games");
                return $this->getPopularGames($numRecommendations);
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("❌ Content-based recommendation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->getPopularGames($numRecommendations);
        }
    }
    
    private function getPopularGames($numRecommendations) {
        try {
            error_log("🔥 Getting popular games (limit: $numRecommendations)");
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    g.id as game_id,
                    g.name as game_name,
                    g.description,
                    g.imageUrl as game_image,
                    g.averageRating as average_rating,
                    g.minAge,
                    g.targetGender,
                    c.name as category_name,
                    g.averageRating as predicted_rating
                FROM Game g
                LEFT JOIN Category c ON g.categoryId = c.id
                WHERE g.averageRating IS NOT NULL
                ORDER BY g.averageRating DESC, RAND()
                LIMIT :limit
            ");
            
            $stmt->bindValue(':limit', $numRecommendations, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("🎮 Found " . count($results) . " popular games");
            
            // If still no results, get any games
            if (empty($results)) {
                error_log("🔄 No popular games found, getting any available games");
                $stmt = $this->pdo->prepare("
                    SELECT 
                        g.id as game_id,
                        g.name as game_name,
                        g.description,
                        g.imageUrl as game_image,
                        COALESCE(g.averageRating, 3.0) as average_rating,
                        g.minAge,
                        g.targetGender,
                        c.name as category_name,
                        COALESCE(g.averageRating, 3.0) as predicted_rating
                    FROM Game g
                    LEFT JOIN Category c ON g.categoryId = c.id
                    ORDER BY RAND()
                    LIMIT :limit
                ");
                
                $stmt->bindValue(':limit', $numRecommendations, PDO::PARAM_INT);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("🎲 Found " . count($results) . " random games");
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("❌ Popular games error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Return dummy data if everything fails
            return [
                [
                    'game_id' => 'demo-1',
                    'game_name' => 'Demo Adventure Game',
                    'description' => 'An exciting adventure game to get you started!',
                    'game_image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                    'average_rating' => 4.5,
                    'predicted_rating' => 4.2,
                    'category_name' => 'Adventure',
                    'minAge' => 13,
                    'targetGender' => 'All'
                ]
            ];
        }
    }
    
    // Temporarily comment out AI functionality for debugging
    /*
    private function getAIRecommendations($userId, $numRecommendations) {
        // AI functionality disabled for debugging
        return [];
    }
    
    private function enrichRecommendations($recommendations) {
        // AI functionality disabled for debugging
        return [];
    }
    */
}

// Initialize and handle the request
try {
    error_log("🚀 Recommendations API: Starting...");
    $api = new RecommendationAPI();
    $api->handleRequest();
    error_log("✅ Recommendations API: Completed successfully");
} catch (Exception $e) {
    error_log("❌ API initialization failed: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'API initialization failed: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>