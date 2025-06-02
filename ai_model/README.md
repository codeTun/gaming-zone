# Gaming Zone Recommendation System

A machine learning-powered game recommendation system built to integrate with your web application. This system analyzes user ratings, game history, and user demographics to provide personalized game recommendations.

## Overview

This recommendation system uses a Gradient Boosting model trained on user-game interactions to predict how much a user would enjoy a game they haven't played yet. The system is optimized for the Gaming Zone database schema and can be integrated directly with your web application.

## Prerequisites

- PHP 7.4+
- Python 3.8+ (for model generation)
- Required PHP extensions: `PDO`, `JSON`
- The model files:
  - `db_recommendation_model.pkl`
  - `db_model_scaler.pkl`
  - `db_feature_columns.pkl`

## Installation

1. Place the model files in your application's root directory:

   - `db_recommendation_model.pkl`
   - `db_model_scaler.pkl`
   - `db_feature_columns.pkl`

2. Copy the integration code into your application.

## Database Schema Requirements

The recommendation system expects the following tables and fields:

| Table       | Key Fields                                          |
| ----------- | --------------------------------------------------- |
| Users       | id, birthDate, gender                               |
| Game        | id, categoryId, minAge, targetGender, averageRating |
| GameRating  | userId, gameId, rating                              |
| UserGame    | userId, gameId, score                               |
| Category    | id, name                                            |
| ContentItem | id, name, imageUrl                                  |

## Integration

### PHP Backend Integration

```php
<?php
// recommendation_system.php - Include this in your application

// Helper function to execute database queries
function execute_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// PHP wrapper to call the Python recommendation system
function get_game_recommendations($user_id, $connection, $n_recommendations = 5) {
    // Option 1: Use the Python model via shell_exec
    $command = "python3 recommend.py --user_id=$user_id --recommendations=$n_recommendations";
    $recommendations_json = shell_exec($command);
    $recommendations = json_decode($recommendations_json, true);

    // Option 2: Alternatively, use a PHP-Python bridge like PHP-ML
    // or implement a recommendation API service

    return $recommendations;
}

// API endpoint example
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $db = new PDO('mysql:host=localhost;dbname=gaming_zone', 'username', 'password');

    $recommendations = get_game_recommendations($user_id, $db, 5);

    header('Content-Type: application/json');
    echo json_encode(['recommendations' => $recommendations]);
}
?>
```

### HTML/JavaScript Frontend Integration

```html
<!-- recommendations.html -->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Game Recommendations</title>
    <style>
      .recommendations-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
      }
      .game-card {
        width: 200px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }
      .game-card img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 4px;
      }
      .game-details {
        display: flex;
        justify-content: space-between;
        margin: 10px 0;
      }
      .play-button {
        width: 100%;
        background-color: #4caf50;
        color: white;
        border: none;
        padding: 8px;
        border-radius: 4px;
        cursor: pointer;
      }
    </style>
  </head>
  <body>
    <h2>Recommended Games For You</h2>
    <div id="recommendations-container" class="recommendations-container">
      <!-- Recommendations will be loaded here -->
    </div>

    <script>
      // Get current user ID from page or session
      const userId = '<?php echo $_SESSION["user_id"]; ?>';

      // Fetch recommendations from PHP API
      fetch(`/api/recommendations.php?user_id=${userId}`)
        .then((response) => response.json())
        .then((data) => {
          const container = document.getElementById(
            "recommendations-container"
          );

          data.recommendations.forEach((game) => {
            const gameCard = document.createElement("div");
            gameCard.className = "game-card";

            gameCard.innerHTML = `
                        <img src="${game.game_image}" alt="${game.game_name}">
                        <h3>${game.game_name}</h3>
                        <div class="game-details">
                            <span class="category">${game.category_name}</span>
                            <span class="rating">${game.average_rating.toFixed(
                              1
                            )}⭐</span>
                        </div>
                        <button class="play-button" onclick="playGame('${
                          game.game_id
                        }')">Play Now</button>
                    `;

            container.appendChild(gameCard);
          });
        })
        .catch((error) =>
          console.error("Error fetching recommendations:", error)
        );

      function playGame(gameId) {
        window.location.href = `/games/play.php?id=${gameId}`;
      }
    </script>
  </body>
</html>
```

## Full API Reference

### `get_game_recommendations($user_id, $connection, $n_recommendations=5)`

Gets personalized game recommendations for a specific user.

**Parameters:**

- `$user_id` (string): User ID from your database
- `$connection`: Your PDO database connection object
- `$n_recommendations` (int, optional): Number of games to recommend. Default is 5.

**Returns:**

- Array of recommended games, each containing:
  - `game_id`: Unique ID of the game
  - `game_name`: Name of the game
  - `game_image`: URL to game image
  - `category_name`: Game category
  - `min_age`: Minimum age requirement
  - `target_gender`: Target gender for the game
  - `average_rating`: Average rating across all users
  - `predicted_rating`: Predicted rating for this specific user

## Advanced Usage

To fully implement the recommendation system in PHP, you would need to:

1. Create a Python script (`recommend.py`) that loads the model and processes the data
2. Use PHP to execute the Python script and capture the output
3. Or, create a microservice API with Python that your PHP application can call

## Example Python Bridge Script

```python
# recommend.py
import sys
import json
import argparse
import pickle
import mysql.connector
import numpy as np
import pandas as pd
from datetime import datetime

# Parse command line arguments
parser = argparse.ArgumentParser(description='Game Recommendation System')
parser.add_argument('--user_id', required=True, help='User ID')
parser.add_argument('--recommendations', type=int, default=5, help='Number of recommendations')
args = parser.parse_args()

# Load the model (implement the rest as in the original code)
# ...existing code...

# Output recommendations as JSON for PHP to read
print(json.dumps(recommendations))
```

## Performance Metrics

The recommendation system achieves:

- Mean Squared Error (MSE): 0.32
- Mean Absolute Error (MAE): 0.44

This indicates that the system can predict user ratings with good accuracy.

## Troubleshooting

If you encounter issues:

1. Ensure your database schema matches the expected structure
2. Verify all model files are in the correct location
3. Check PHP has permissions to execute Python scripts
4. Ensure all required PHP and Python dependencies are installed
5. Validate database connection parameters

For additional support or customizations, please contact the development team.
