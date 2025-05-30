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
            'GET /api/games.php' => 'Get all games',
            'GET /api/games.php?id={id}' => 'Get specific game',
            'POST /api/games.php' => 'Create new game',
            'PUT /api/games.php?id={id}' => 'Update game',
            'DELETE /api/games.php?id={id}' => 'Delete game',
            'POST /api/games/rate.php' => 'Rate a game',
            'POST /api/games/play.php' => 'Record game play'
        ],
        'tournaments' => [
            'GET /api/tournaments.php' => 'Get all tournaments',
            'GET /api/tournaments.php?id={id}' => 'Get specific tournament',
            'POST /api/tournaments.php' => 'Create tournament',
            'PUT /api/tournaments.php?id={id}' => 'Update tournament',
            'DELETE /api/tournaments.php?id={id}' => 'Delete tournament',
            'POST /api/tournaments/register.php' => 'Register for tournament'
        ],
        'events' => [
            'GET /api/events.php' => 'Get all events',
            'GET /api/events.php?id={id}' => 'Get specific event',
            'POST /api/events.php' => 'Create event',
            'PUT /api/events.php?id={id}' => 'Update event',
            'DELETE /api/events.php?id={id}' => 'Delete event',
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
