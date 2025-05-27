<?php
require_once '../helpers/EnvLoader.php';

// Load environment variables
EnvLoader::load();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .test-section { 
            border: 1px solid #ccc; 
            margin: 10px 0; 
            padding: 15px; 
            border-radius: 5px; 
        }
        .test-header { 
            background-color: #f5f5f5; 
            margin: -15px -15px 10px -15px; 
            padding: 10px 15px; 
            font-weight: bold; 
        }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>Gaming Zone Database Test Results</h1>";

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<div class='test-header'>Test 1: Database Connection</div>";

try {
    $host = EnvLoader::get('DB_HOST', 'localhost');
    $user = EnvLoader::get('DB_USERNAME', 'root');
    $pass = EnvLoader::get('DB_PASSWORD', '');
    $dbname = EnvLoader::get('DB_NAME', 'gaming_zone_new');
    
    // Test connection without database
    $pdo = new PDO("mysql:host={$host}", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✓ MySQL connection successful</p>";
    
    // Check if database exists
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$dbname]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Database '{$dbname}' exists</p>";
    } else {
        echo "<p class='warning'>⚠ Database '{$dbname}' does not exist</p>";
        
        // Create database
        $pdo->exec("CREATE DATABASE {$dbname}");
        echo "<p class='success'>✓ Database '{$dbname}' created successfully</p>";
    }
    
    // Connect to the database
    $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✓ Connected to database '{$dbname}'</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}
echo "</div>";

// Test 2: Check Required Tables
echo "<div class='test-section'>";
echo "<div class='test-header'>Test 2: Table Structure Verification</div>";

$requiredTables = [
    'User', 'Token', 'Category', 'ContentItem', 
    'Game', 'GameRating', 'Event', 'Tournament', 'UserGame'
];

$existingTables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $existingTables[] = $row[0];
}

echo "<h3>Table Existence Check:</h3>";
$allTablesExist = true;
foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        echo "<p class='success'>✓ Table '{$table}' exists</p>";
    } else {
        echo "<p class='error'>✗ Table '{$table}' missing</p>";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "<p class='warning'>⚠ Some tables are missing. Running schema creation...</p>";
    
    try {
        $sql = file_get_contents(__DIR__ . '/new_db_schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^(DROP|--)/i', $statement)) {
                $pdo->exec($statement);
            }
        }
        echo "<p class='success'>✓ Database schema created successfully</p>";
        
        // Re-check tables
        $existingTables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Schema creation failed: " . $e->getMessage() . "</p>";
    }
}

echo "</div>";

// Test 3: Table Structure Details
echo "<div class='test-section'>";
echo "<div class='test-header'>Test 3: Table Structure Details</div>";

foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        echo "<h4>Table: {$table}</h4>";
        
        try {
            $stmt = $pdo->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll();
            
            echo "<table>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Error describing table {$table}: " . $e->getMessage() . "</p>";
        }
    }
}

echo "</div>";

// Test 4: Test Data Insertion
echo "<div class='test-section'>";
echo "<div class='test-header'>Test 4: Data Insertion Test</div>";

try {
    // Test Category insertion
    $categoryId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $stmt = $pdo->prepare("INSERT INTO Category (id, name) VALUES (?, ?)");
    $stmt->execute([$categoryId, 'Test Category']);
    echo "<p class='success'>✓ Category insertion test passed</p>";
    
    // Test User insertion
    $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $stmt = $pdo->prepare("INSERT INTO User (id, name, username, email, password, role, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, 'Test User', 'testuser', 'test@example.com', password_hash('password', PASSWORD_DEFAULT), 'USER', 'MALE']);
    echo "<p class='success'>✓ User insertion test passed</p>";
    
    // Test ContentItem and Game insertion
    $gameId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $stmt = $pdo->prepare("INSERT INTO ContentItem (id, name, description, type) VALUES (?, ?, ?, 'GAME')");
    $stmt->execute([$gameId, 'Test Game', 'A test game']);
    
    $stmt = $pdo->prepare("INSERT INTO Game (id, categoryId, minAge, targetGender, averageRating) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$gameId, $categoryId, 13, 'MALE', 4.5]);
    echo "<p class='success'>✓ Game insertion test passed</p>";
    
    // Test relationships
    $stmt = $pdo->prepare("INSERT INTO GameRating (userId, gameId, rating) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $gameId, 5]);
    echo "<p class='success'>✓ GameRating insertion test passed</p>";
    
    $stmt = $pdo->prepare("INSERT INTO UserGame (userId, gameId, score) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $gameId, 1000]);
    echo "<p class='success'>✓ UserGame insertion test passed</p>";
    
    // Test Cloudinary configuration
    require_once '../helpers/CloudinaryHelper.php';
    
    EnvLoader::load();
    $cloudName = EnvLoader::get('CLOUDINARY_CLOUD_NAME');
    $apiKey = EnvLoader::get('CLOUDINARY_API_KEY');
    
    if ($cloudName && $apiKey) {
        echo "<p class='success'>✓ Cloudinary configuration found</p>";
        echo "<p class='info'>ℹ Cloud Name: {$cloudName}</p>";
        echo "<p class='info'>ℹ API Key: " . substr($apiKey, 0, 6) . "...</p>";
    } else {
        echo "<p class='warning'>⚠ Cloudinary configuration missing - image uploads will not work</p>";
    }
    
    // Clean up test data
    $pdo->exec("DELETE FROM UserGame WHERE userId = '{$userId}'");
    $pdo->exec("DELETE FROM GameRating WHERE userId = '{$userId}'");
    $pdo->exec("DELETE FROM Game WHERE id = '{$gameId}'");
    $pdo->exec("DELETE FROM ContentItem WHERE id = '{$gameId}'");
    $pdo->exec("DELETE FROM User WHERE id = '{$userId}'");
    $pdo->exec("DELETE FROM Category WHERE id = '{$categoryId}'");
    echo "<p class='info'>ℹ Test data cleaned up</p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Data insertion test failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 5: Check Foreign Key Constraints
echo "<div class='test-section'>";
echo "<div class='test-header'>Test 5: Foreign Key Constraints Test</div>";

try {
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = '{$dbname}' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreignKeys = $stmt->fetchAll();
    
    if (count($foreignKeys) > 0) {
        echo "<p class='success'>✓ Foreign key constraints found (" . count($foreignKeys) . " total)</p>";
        echo "<table>";
        echo "<tr><th>Table</th><th>Column</th><th>References</th><th>Constraint Name</th></tr>";
        foreach ($foreignKeys as $fk) {
            echo "<tr>";
            echo "<td>{$fk['TABLE_NAME']}</td>";
            echo "<td>{$fk['COLUMN_NAME']}</td>";
            echo "<td>{$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</td>";
            echo "<td>{$fk['CONSTRAINT_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No foreign key constraints found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>✗ Foreign key check failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test 6: Environment Variables Test
echo "<div class='test-section'>";
echo "<div class='test-header'>Test 6: Environment Variables Test</div>";

$envVars = ['JWT_SECRET_KEY', 'JWT_ALGORITHM', 'DB_HOST', 'DB_USERNAME', 'DB_NAME'];
foreach ($envVars as $var) {
    $value = EnvLoader::get($var);
    if ($value) {
        if ($var === 'JWT_SECRET_KEY') {
            $displayValue = str_repeat('*', min(strlen($value), 20)) . '...';
        } else {
            $displayValue = $value;
        }
        echo "<p class='success'>✓ {$var}: {$displayValue}</p>";
    } else {
        echo "<p class='error'>✗ {$var}: Not set</p>";
    }
}

echo "</div>";

echo "<div class='test-section'>";
echo "<div class='test-header'>Summary</div>";
echo "<p class='info'>Database testing completed. Check above sections for any issues.</p>";
echo "<p class='info'>If all tests pass, your database is ready for use!</p>";
echo "</div>";

echo "</body></html>";
?>
