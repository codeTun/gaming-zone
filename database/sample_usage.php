<?php
require_once 'user_functions.php';
require_once 'game_functions.php';
require_once 'tournament_functions.php';

// Sample execution of the functions
echo "<h1>Sample Usage of Database Functions</h1>";

// Initialize user manager
$userManager = new UserManager();

// Register a new admin user
$adminResult = $userManager->registerUser('admin', 'admin@example.com', 'adminpassword', true);
echo "<h2>Admin Registration:</h2>";
echo "<pre>" . print_r($adminResult, true) . "</pre>";

// Register a regular user
$userResult = $userManager->registerUser('player1', 'player1@example.com', 'playerpassword');
echo "<h2>User Registration:</h2>";
echo "<pre>" . print_r($userResult, true) . "</pre>";

// Login as admin
$loginResult = $userManager->loginUser('admin@example.com', 'adminpassword');
echo "<h2>Admin Login:</h2>";
echo "<pre>" . print_r($loginResult, true) . "</pre>";

// Store the admin token for later use
$adminToken = $loginResult['success'] ? $loginResult['token'] : null;

// Initialize game manager
$gameManager = new GameManager();

// Get all categories
$categoriesResult = $gameManager->getAllCategories();
echo "<h2>All Categories:</h2>";
echo "<pre>" . print_r($categoriesResult, true) . "</pre>";

// Add a new game
if ($categoriesResult['success'] && count($categoriesResult['categories']) > 0) {
    $categoryId = $categoriesResult['categories'][0]['id'];
    $gameResult = $gameManager->addGame(
        'Whac-a-Mole', 
        'Classic arcade game where players use a mallet to hit moles that pop up from holes.',
        $categoryId,
        date('Y-m-d'),
        './assets/images/mole.png'
    );
    
    echo "<h2>Adding Game:</h2>";
    echo "<pre>" . print_r($gameResult, true) . "</pre>";
    
    // Store game ID for later use
    $gameId = $gameResult['success'] ? $gameResult['game_id'] : null;
}

// Initialize tournament manager
$tournamentManager = new TournamentManager();

// Create a tournament if we have a game
if (isset($gameId)) {
    $tournamentResult = $tournamentManager->createTournament(
        'Whac-a-Mole Championship',
        $gameId,
        date('Y-m-d H:i:s', strtotime('+1 week')),
        date('Y-m-d H:i:s', strtotime('+2 weeks')),
        '$500 Prize Pool',
        32
    );
    
    echo "<h2>Creating Tournament:</h2>";
    echo "<pre>" . print_r($tournamentResult, true) . "</pre>";
    
    // Store tournament ID
    $tournamentId = $tournamentResult['success'] ? $tournamentResult['tournament_id'] : null;
}

// Register player for tournament
if (isset($tournamentId) && $userResult['success']) {
    $registrationResult = $tournamentManager->registerUserForTournament($userResult['user_id'], $tournamentId);
    
    echo "<h2>Tournament Registration:</h2>";
    echo "<pre>" . print_r($registrationResult, true) . "</pre>";
}

// Like a game
if (isset($gameId) && $userResult['success']) {
    $likeResult = $gameManager->likeGame($userResult['user_id'], $gameId);
    
    echo "<h2>Liking Game:</h2>";
    echo "<pre>" . print_r($likeResult, true) . "</pre>";
}

// Get all games
$gamesResult = $gameManager->getAllGames();
echo "<h2>All Games:</h2>";
echo "<pre>" . print_r($gamesResult, true) . "</pre>";

// Get all tournaments
$tournamentsResult = $tournamentManager->getAllTournaments();
echo "<h2>All Tournaments:</h2>";
echo "<pre>" . print_r($tournamentsResult, true) . "</pre>";

echo "<p>Database operations completed!</p>";
?>
