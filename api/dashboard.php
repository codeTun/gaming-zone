<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all data for dashboard
    $dashboard = [];

    // Users statistics
    $stmt = $pdo->query("SELECT COUNT(*) as totalUsers, COUNT(CASE WHEN role = 'ADMIN' THEN 1 END) as totalAdmins FROM Users");
    $dashboard['users'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Categories
    $stmt = $pdo->query("SELECT * FROM Category ORDER BY name");
    $dashboard['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Games with categories
    $stmt = $pdo->query("
        SELECT ci.id, ci.name, ci.description, ci.imageUrl, g.categoryId, c.name as categoryName, 
               g.minAge, g.targetGender, g.averageRating, ci.createdAt
        FROM ContentItem ci
        JOIN Game g ON ci.id = g.id
        LEFT JOIN Category c ON g.categoryId = c.id
        WHERE ci.type = 'GAME'
        ORDER BY ci.name ASC
    ");
    $dashboard['games'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Events
    $stmt = $pdo->query("
        SELECT ci.id, ci.name, ci.description, ci.imageUrl, e.place, e.startDate, ci.createdAt
        FROM ContentItem ci
        JOIN Event e ON ci.id = e.id
        WHERE ci.type = 'EVENT'
        ORDER BY e.startDate ASC
    ");
    $dashboard['events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tournaments
    $stmt = $pdo->query("
        SELECT ci.id, ci.name, ci.description, ci.imageUrl, t.startDate, t.endDate, t.prizePool, ci.createdAt
        FROM ContentItem ci
        JOIN Tournament t ON ci.id = t.id
        WHERE ci.type = 'TOURNAMENT'
        ORDER BY t.startDate ASC
    ");
    $dashboard['tournaments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tournament Registrations
    $stmt = $pdo->query("
        SELECT tr.*, u.name as fullName, ci.name as tournamentName, t.startDate, t.endDate, t.prizePool
        FROM TournamentRegistration tr 
        JOIN Users u ON tr.userId = u.id 
        JOIN ContentItem ci ON tr.tournamentId = ci.id 
        JOIN Tournament t ON tr.tournamentId = t.id
        ORDER BY tr.registeredAt DESC
    ");
    $dashboard['tournamentRegistrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Event Registrations
    $stmt = $pdo->query("
        SELECT er.*, u.name as fullName, ci.name as eventName, e.place, e.startDate
        FROM EventRegistration er 
        JOIN Users u ON er.userId = u.id 
        JOIN ContentItem ci ON er.eventId = ci.id 
        JOIN Event e ON er.eventId = e.id
        ORDER BY er.registeredAt DESC
    ");
    $dashboard['eventRegistrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Game Ratings
    $stmt = $pdo->query("
        SELECT gr.*, u.username, ci.name as gameName 
        FROM GameRating gr 
        JOIN Users u ON gr.userId = u.id 
        JOIN ContentItem ci ON gr.gameId = ci.id 
        ORDER BY gr.ratedAt DESC
        LIMIT 50
    ");
    $dashboard['gameRatings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // User Games (Recent plays)
    $stmt = $pdo->query("
        SELECT ug.*, u.username, ci.name as gameName 
        FROM UserGame ug 
        JOIN Users u ON ug.userId = u.id 
        JOIN ContentItem ci ON ug.gameId = ci.id 
        ORDER BY ug.playedAt DESC 
        LIMIT 50
    ");
    $dashboard['userGames'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ContentItem WHERE type = 'GAME'");
    $dashboard['stats']['totalGames'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ContentItem WHERE type = 'EVENT'");
    $dashboard['stats']['totalEvents'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ContentItem WHERE type = 'TOURNAMENT'");
    $dashboard['stats']['totalTournaments'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM GameRating");
    $dashboard['stats']['totalRatings'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM TournamentRegistration");
    $dashboard['stats']['totalTournamentRegistrations'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM EventRegistration");
    $dashboard['stats']['totalEventRegistrations'] = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'data' => $dashboard]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
