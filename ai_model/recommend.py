#!/usr/bin/env python3
import sys
import json
import argparse
import pickle
import os
import numpy as np
import pandas as pd
from datetime import datetime
import warnings
warnings.filterwarnings('ignore')

# Database connection (you'll need to install mysql-connector-python)
try:
    import mysql.connector
    MYSQL_AVAILABLE = True
except ImportError:
    MYSQL_AVAILABLE = False

def load_model_files():
    """Load the pre-trained model and associated files."""
    try:
        base_path = os.path.dirname(os.path.abspath(__file__))
        
        # Load model files
        model_path = os.path.join(base_path, 'db_recommendation_model.pkl')
        scaler_path = os.path.join(base_path, 'db_model_scaler.pkl')
        features_path = os.path.join(base_path, 'db_feature_columns.pkl')
        
        with open(model_path, 'rb') as f:
            model = pickle.load(f)
        
        with open(scaler_path, 'rb') as f:
            scaler = pickle.load(f)
        
        with open(features_path, 'rb') as f:
            feature_columns = pickle.load(f)
        
        return model, scaler, feature_columns
    
    except Exception as e:
        raise Exception(f"Failed to load model files: {str(e)}")

def get_database_connection():
    """Get database connection."""
    if not MYSQL_AVAILABLE:
        raise Exception("mysql-connector-python not installed")
    
    try:
        # Database configuration - adjust these settings
        config = {
            'user': 'root',
            'password': '',  # Update with your MySQL password
            'host': 'localhost',
            'database': 'gaming_zone',
            'raise_on_warnings': True
        }
        
        return mysql.connector.connect(**config)
    
    except Exception as e:
        raise Exception(f"Database connection failed: {str(e)}")

def get_user_data(user_id, cursor):
    """Get user data from database."""
    try:
        # Get user demographics
        user_query = "SELECT birthDate, gender FROM users WHERE id = %s"
        cursor.execute(user_query, (user_id,))
        user_result = cursor.fetchone()
        
        if not user_result:
            raise Exception(f"User {user_id} not found")
        
        birth_date, gender = user_result
        
        # Calculate age
        if birth_date:
            age = datetime.now().year - birth_date.year
        else:
            age = 25  # Default age
        
        return {
            'age': age,
            'gender': gender or 'All'
        }
    
    except Exception as e:
        raise Exception(f"Failed to get user data: {str(e)}")

def get_available_games(user_id, cursor):
    """Get games that user hasn't played yet."""
    try:
        # Get games user hasn't played
        games_query = """
        SELECT 
            g.id,
            g.name,
            g.description,
            g.imageUrl,
            g.minAge,
            g.targetGender,
            g.averageRating,
            c.name as category_name
        FROM game g
        LEFT JOIN category c ON g.categoryId = c.id
        WHERE g.id NOT IN (
            SELECT DISTINCT gameId 
            FROM usergame 
            WHERE userId = %s
        )
        AND g.isActive = 1
        ORDER BY g.averageRating DESC
        """
        
        cursor.execute(games_query, (user_id,))
        games = cursor.fetchall()
        
        return games
    
    except Exception as e:
        raise Exception(f"Failed to get available games: {str(e)}")

def prepare_features(user_data, game_data, feature_columns):
    """Prepare features for the model."""
    try:
        # Create feature vector based on your model's expected input
        features = []
        
        for game in game_data:
            # Create feature vector for this user-game pair
            feature_vector = {
                'user_age': user_data['age'],
                'user_gender_male': 1 if user_data['gender'].lower() == 'male' else 0,
                'user_gender_female': 1 if user_data['gender'].lower() == 'female' else 0,
                'game_min_age': game[4] or 0,  # minAge
                'game_target_gender_male': 1 if game[5] and game[5].lower() == 'male' else 0,
                'game_target_gender_female': 1 if game[5] and game[5].lower() == 'female' else 0,
                'game_target_gender_all': 1 if game[5] and game[5].lower() == 'all' else 0,
                'game_average_rating': game[6] or 2.5,  # averageRating
            }
            
            # Add category features (one-hot encoding)
            category_name = game[7] or 'Unknown'
            for col in feature_columns:
                if col.startswith('category_'):
                    category_feature = col.replace('category_', '')
                    feature_vector[col] = 1 if category_name.lower() == category_feature.lower() else 0
            
            # Ensure all required features are present
            complete_vector = []
            for col in feature_columns:
                complete_vector.append(feature_vector.get(col, 0))
            
            features.append(complete_vector)
        
        return np.array(features)
    
    except Exception as e:
        raise Exception(f"Failed to prepare features: {str(e)}")

def generate_recommendations(user_id, n_recommendations=5):
    """Generate game recommendations for a user."""
    try:
        # Load model
        model, scaler, feature_columns = load_model_files()
        
        # Connect to database
        if MYSQL_AVAILABLE:
            conn = get_database_connection()
            cursor = conn.cursor()
            
            try:
                # Get user data
                user_data = get_user_data(user_id, cursor)
                
                # Get available games
                games_data = get_available_games(user_id, cursor)
                
                if not games_data:
                    return []
                
                # Prepare features
                features = prepare_features(user_data, games_data, feature_columns)
                
                # Scale features
                features_scaled = scaler.transform(features)
                
                # Make predictions
                predictions = model.predict(features_scaled)
                
                # Combine games with predictions
                game_recommendations = []
                for i, game in enumerate(games_data):
                    game_recommendations.append({
                        'game_id': str(game[0]),
                        'game_name': game[1],
                        'description': game[2],
                        'game_image': game[3] or 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
                        'category_name': game[7] or 'Unknown',
                        'min_age': game[4] or 0,
                        'target_gender': game[5] or 'All',
                        'average_rating': float(game[6]) if game[6] else 0.0,
                        'predicted_rating': float(predictions[i]),
                        'recommendation_reason': 'AI recommended based on your preferences'
                    })
                
                # Sort by predicted rating and return top N
                game_recommendations.sort(key=lambda x: x['predicted_rating'], reverse=True)
                return game_recommendations[:n_recommendations]
            
            finally:
                cursor.close()
                conn.close()
        
        else:
            # Fallback when MySQL is not available
            return generate_fallback_recommendations(n_recommendations)
    
    except Exception as e:
        # Return fallback recommendations on any error
        return generate_fallback_recommendations(n_recommendations)

def generate_fallback_recommendations(n_recommendations=5):
    """Generate fallback recommendations when AI model fails."""
    fallback_games = [
        {
            'game_id': 'fb1',
            'game_name': 'Adventure Quest',
            'description': 'Embark on an epic journey through mystical lands.',
            'game_image': 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400',
            'category_name': 'Adventure',
            'min_age': 12,
            'target_gender': 'All',
            'average_rating': 4.2,
            'predicted_rating': 4.0,
            'recommendation_reason': 'Popular adventure game'
        },
        {
            'game_id': 'fb2',
            'game_name': 'Strategy Master',
            'description': 'Test your tactical skills in this challenging strategy game.',
            'game_image': 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=400',
            'category_name': 'Strategy',
            'min_age': 10,
            'target_gender': 'All',
            'average_rating': 4.1,
            'predicted_rating': 3.9,
            'recommendation_reason': 'Great for strategy lovers'
        },
        {
            'game_id': 'fb3',
            'game_name': 'Racing Championship',
            'description': 'High-speed racing with realistic physics and graphics.',
            'game_image': 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=400',
            'category_name': 'Racing',
            'min_age': 7,
            'target_gender': 'All',
            'average_rating': 4.0,
            'predicted_rating': 3.8,
            'recommendation_reason': 'Exciting racing experience'
        }
    ]
    
    return fallback_games[:n_recommendations]

def main():
    """Main function to handle command line arguments and generate recommendations."""
    try:
        # Parse command line arguments
        parser = argparse.ArgumentParser(description='Game Recommendation System')
        parser.add_argument('--user_id', required=True, help='User ID')
        parser.add_argument('--recommendations', type=int, default=5, help='Number of recommendations')
        args = parser.parse_args()
        
        # Generate recommendations
        recommendations = generate_recommendations(args.user_id, args.recommendations)
        
        # Output as JSON for PHP to read
        print(json.dumps(recommendations, indent=2))
    
    except Exception as e:
        # Output error as JSON
        error_response = {
            'error': str(e),
            'fallback_recommendations': generate_fallback_recommendations(5)
        }
        print(json.dumps(error_response, indent=2))

if __name__ == '__main__':
    main()
