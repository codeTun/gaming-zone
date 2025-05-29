<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Gaming Zone API v1.0',
    'endpoints' => [
        'auth' => [
            'POST /api/auth/register.php' => 'Register new user',
            'POST /api/auth/login.php' => 'User login',
            'POST /api/auth/verify-token.php' => 'Verify JWT token',
            'POST /api/auth/logout.php' => 'User logout',
            'POST /api/auth/refresh-token.php' => 'Refresh JWT token'
        ],
        'games' => [
            'GET /api/games/get-all.php' => 'Get all games',
            'POST /api/games/create.php' => 'Create new game (Admin)',
            'POST /api/games/rate.php' => 'Rate a game',
            'POST /api/games/play.php' => 'Record game play'
        ],
        'tournaments' => [
            'GET /api/tournaments/get-all.php' => 'Get all tournaments',
            'POST /api/tournaments/create.php' => 'Create tournament (Admin)',
            'POST /api/tournaments/register.php' => 'Register for tournament'
        ],
        'events' => [
            'GET /api/events/get-all.php' => 'Get all events',
            'POST /api/events/create.php' => 'Create event (Admin)',
            'POST /api/events/register.php' => 'Register for event'
        ],
        'categories' => [
            'GET /api/categories/get-all.php' => 'Get all categories'
        ],
        'user' => [
            'GET /api/user/profile.php' => 'Get user profile and stats'
        ],
        'leaderboard' => [
            'GET /api/leaderboard/games.php' => 'Get game leaderboards'
        ],
        'upload' => [
            'POST /api/upload/image.php' => 'Upload image to Cloudinary',
            'POST /api/upload/update-profile-image.php' => 'Update profile image'
        ]
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
