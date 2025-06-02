
# Database Integration Code for Your Web Application

import pickle
import numpy as np
import pandas as pd
from datetime import datetime

# Load the trained model
def load_recommendation_system():
    with open('db_recommendation_model.pkl', 'rb') as f:
        model = pickle.load(f)
    with open('db_model_scaler.pkl', 'rb') as f:
        scaler = pickle.load(f)
    with open('db_feature_columns.pkl', 'rb') as f:
        feature_columns = pickle.load(f)

    return model, scaler, feature_columns

# Your web application integration function
def get_game_recommendations(user_id, connection, n_recommendations=5):
    """
    Get recommendations for a user from your database

    Args:
        user_id (str): User ID from your database
        connection: Your database connection
        n_recommendations (int): Number of games to recommend

    Returns:
        list: Recommended games with all information
    """

    # Load model
    model, scaler, feature_columns = load_recommendation_system()

    # 1. Get user data
    user_query = "SELECT * FROM Users WHERE id = ?"
    user_data = execute_query(connection, user_query, [user_id])

    # 2. Get user's ratings
    ratings_query = """
        SELECT gr.*, c.name as category_name
        FROM GameRating gr
        JOIN Game g ON gr.gameId = g.id
        JOIN Category c ON g.categoryId = c.id
        WHERE gr.userId = ?
    """
    user_ratings = execute_query(connection, ratings_query, [user_id])

    # 3. Get user's games
    games_query = "SELECT * FROM UserGame WHERE userId = ?"
    user_games = execute_query(connection, games_query, [user_id])

    # 4. Get all available games
    all_games_query = """
        SELECT g.*, c.name as category_name, ci.name as game_name, ci.imageUrl
        FROM Game g
        JOIN Category c ON g.categoryId = c.id
        JOIN ContentItem ci ON g.id = ci.id
    """
    all_games = execute_query(connection, all_games_query)

    # 5. Get all categories
    categories_query = "SELECT * FROM Category"
    all_categories = execute_query(connection, categories_query)

    # 6. Generate recommendations using the model
    return generate_recommendations(
        user_data[0], user_ratings, user_games, all_games, all_categories,
        model, scaler, feature_columns, n_recommendations
    )

# Helper function to calculate user age
def calculate_user_age(birth_date):
    today = datetime.now().date()
    return today.year - birth_date.year - ((today.month, today.day) < (birth_date.month, birth_date.day))

# Main recommendation function
def generate_recommendations(user_data, user_ratings, user_games, all_games, all_categories, model, scaler, feature_columns, n_recommendations):
    # Calculate user profile
    user_age = calculate_user_age(user_data['birthDate'])

    # Analyze user preferences from ratings
    user_preferences = analyze_user_category_preferences(user_ratings)

    # Calculate gaming activity from games
    if user_games:
        avg_score = np.mean([g['score'] for g in user_games])
        games_played = len(user_games)
        max_score = max([g['score'] for g in user_games])
    else:
        avg_score = 500
        games_played = 1
        max_score = 500

    # Filter age-appropriate games user hasn't rated
    rated_game_ids = {rating['gameId'] for rating in user_ratings}
    eligible_games = [
        game for game in all_games
        if game['minAge'] <= user_age and game['id'] not in rated_game_ids
    ]

    recommendations = []

    for game in eligible_games:
        # Create feature vector for this user-game combination
        features = create_feature_vector(
            user_data, user_age, user_preferences, avg_score, games_played, max_score, game
        )

        # Predict rating
        feature_array = np.array([features.get(col, 0) for col in feature_columns]).reshape(1, -1)
        feature_scaled = scaler.transform(feature_array)
        predicted_rating = model.predict(feature_scaled)[0]
        predicted_rating = max(1.0, min(5.0, predicted_rating))

        recommendations.append({
            'game_id': game['id'],
            'game_name': game['game_name'],
            'game_image': game['imageUrl'],
            'category_name': game['category_name'],
            'min_age': game['minAge'],
            'target_gender': game['targetGender'],
            'average_rating': game['averageRating'],
            'predicted_rating': predicted_rating
        })

    # Sort by predicted rating and return top N
    recommendations.sort(key=lambda x: x['predicted_rating'], reverse=True)
    return recommendations[:n_recommendations]

# Feature creation function (implement based on your model)
def create_feature_vector(user_data, user_age, user_preferences, avg_score, games_played, max_score, game):
    # This should match the feature engineering in your trained model
    # Implementation details provided in the full system
    pass

def analyze_user_category_preferences(user_ratings):
    # Analyze user's category preferences from their ratings
    # Implementation details provided in the full system
    pass
