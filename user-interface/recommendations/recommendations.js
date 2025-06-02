class RecommendationsManager {
  constructor() {
    this.currentUser = null;
    this.recommendations = [];
    this.isLoading = false;
    this.init();
  }

  init() {
    console.log('🤖 AI Recommendations Manager initialized');
    this.bindEvents();
  }

  bindEvents() {
    // Refresh recommendations button
    const refreshBtn = document.getElementById('refreshRecommendations');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', () => {
        console.log('🔄 Refreshing AI recommendations...');
        this.loadRecommendations();
      });
    }

    // Recommendation count change
    const countSelect = document.getElementById('recommendationCount');
    if (countSelect) {
      countSelect.addEventListener('change', () => {
        console.log('📊 Recommendation count changed, auto-refreshing...');
        this.loadRecommendations();
      });
    }
  }

  async loadRecommendations() {
    if (this.isLoading) {
      console.log('⏳ Already loading recommendations, skipping...');
      return;
    }

    console.log('🤖 Loading AI recommendations...');
    this.isLoading = true;

    const recommendationsGrid = document.getElementById('recommendationsGrid');
    if (!recommendationsGrid) {
      console.error('❌ Recommendations grid not found');
      this.isLoading = false;
      return;
    }

    // Check if user is authenticated
    this.currentUser = this.getCurrentUser();
    if (!this.currentUser || !this.currentUser.id) {
      this.showAuthenticationRequired();
      this.isLoading = false;
      return;
    }

    // Show loading state
    this.showLoadingState();

    try {
      const recommendationCount = document.getElementById('recommendationCount')?.value || 5;
      
      console.log('📡 Fetching AI recommendations from API...');
      const response = await fetch('/gaming-zone/api/recommendations.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          user_id: this.currentUser.id,
          recommendations: parseInt(recommendationCount)
        })
      });
      
      console.log('📡 Recommendations API response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const textResponse = await response.text();
        console.error('❌ Non-JSON response received:', textResponse);
        throw new Error('Server returned invalid response format. Please check server logs.');
      }
      
      const result = await response.json();
      console.log('📦 Recommendations API response data:', result);
      
      if (result.success && Array.isArray(result.recommendations) && result.recommendations.length > 0) {
        console.log(`✅ Found ${result.recommendations.length} AI recommendations`);
        this.recommendations = result.recommendations;
        this.displayRecommendations();
      } else if (result.success && Array.isArray(result.recommendations) && result.recommendations.length === 0) {
        console.log('ℹ️ No recommendations available for this user');
        this.showNoRecommendations();
      } else {
        console.error('❌ Invalid recommendations data structure:', result);
        throw new Error(result.error || 'Invalid recommendations data received from server');
      }
    } catch (error) {
      console.error('❌ Recommendations loading failed:', error);
      this.showError(error.message);
    } finally {
      this.isLoading = false;
    }
  }

  getCurrentUser() {
    // Try to get current user from global variable or localStorage
    if (window.currentUser) {
      return window.currentUser;
    }

    try {
      const storedUser = localStorage.getItem('gaming_zone_user');
      if (storedUser) {
        return JSON.parse(storedUser);
      }
    } catch (error) {
      console.error('❌ Error parsing stored user:', error);
    }

    return null;
  }

  showLoadingState() {
    const recommendationsGrid = document.getElementById('recommendationsGrid');
    if (recommendationsGrid) {
      recommendationsGrid.innerHTML = `
        <div class="no-recommendations loading">
          <i class="fas fa-brain fa-spin icon"></i>
          <h3>🤖 AI is analyzing your preferences...</h3>
          <p>Please wait while our machine learning model generates personalized recommendations</p>
          <div class="info-box">
            <i class="fas fa-info-circle"></i>
            Our AI considers your rating history, game preferences, and demographic data
          </div>
        </div>
      `;
    }
  }

  showAuthenticationRequired() {
    const recommendationsGrid = document.getElementById('recommendationsGrid');
    if (recommendationsGrid) {
      recommendationsGrid.innerHTML = `
        <div class="no-recommendations">
          <i class="fas fa-user-lock icon"></i>
          <h3>Authentication Required</h3>
          <p>Please login to get personalized AI recommendations</p>
          <button class="explore-games-btn" onclick="window.location.href='/gaming-zone/pages/loginuser/index.html'">
            <i class="fas fa-sign-in-alt"></i>
            Login Now
          </button>
        </div>
      `;
    }
  }

  showNoRecommendations() {
    const recommendationsGrid = document.getElementById('recommendationsGrid');
    if (recommendationsGrid) {
      recommendationsGrid.innerHTML = `
        <div class="no-recommendations">
          <i class="fas fa-robot icon"></i>
          <h3>No Recommendations Available</h3>
          <p>Our AI needs more data about your gaming preferences.</p>
          <p class="subtitle">Try rating some games or playing more to improve recommendations!</p>
          <button class="explore-games-btn" onclick="document.getElementById('gamesLink').click()">
            <i class="fas fa-gamepad"></i>
            Explore Games
          </button>
        </div>
      `;
    }
  }

  showError(message) {
    const recommendationsGrid = document.getElementById('recommendationsGrid');
    if (recommendationsGrid) {
      recommendationsGrid.innerHTML = `
        <div class="error-message">
          <i class="fas fa-exclamation-triangle icon"></i>
          <h3>AI Recommendations Error</h3>
          <p class="error-text">${message}</p>
          <div class="info-box">
            <i class="fas fa-info-circle"></i>
            The AI recommendation system may need to be configured or the model files may be missing.
          </div>
          <button class="try-again-btn" onclick="window.recommendationsManager.loadRecommendations()">
            <i class="fas fa-refresh"></i>
            Try Again
          </button>
        </div>
      `;
    }
  }

  displayRecommendations() {
    console.log('🎨 Displaying AI recommendations...', this.recommendations.length);
    
    const recommendationsGrid = document.getElementById('recommendationsGrid');
    if (!recommendationsGrid) {
      console.error('❌ Recommendations grid not found');
      return;
    }

    const recommendationsHTML = this.recommendations.map((rec, index) => {
      return this.createRecommendationCard(rec, index);
    }).join('');
    
    recommendationsGrid.innerHTML = recommendationsHTML;
    console.log(`✅ Successfully displayed ${this.recommendations.length} AI recommendations`);
  }

  createRecommendationCard(rec, index) {
    // Safely get recommendation properties with fallbacks
    const gameId = rec.game_id || rec.id || 'unknown';
    const gameName = rec.game_name || rec.name || 'Recommended Game';
    const gameDescription = rec.description || 'This game is recommended for you based on your preferences and gaming history.';
    const gameImageUrl = rec.game_image || rec.imageUrl || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400';
    const categoryName = rec.category_name || rec.categoryName || 'Gaming';
    const averageRating = parseFloat(rec.average_rating || rec.averageRating || 0);
    const predictedRating = parseFloat(rec.predicted_rating || rec.predictedRating || 0);
    const confidence = Math.round((predictedRating / 5) * 100);

    return `
      <div class="recommendation-card">
        <!-- AI Badge -->
        <div class="ai-badge">
          🤖 AI Pick #${index + 1}
        </div>

        <!-- Confidence Score -->
        <div class="confidence-score">
          ${confidence}% Match
        </div>
        
        <!-- Game Header with Image -->
        <div class="recommendation-image">
          <img src="${gameImageUrl}" 
               alt="${gameName}" 
               onerror="this.src='https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'">
        </div>
        
        <!-- Game Content -->
        <div class="recommendation-content">
          <!-- Game Title -->
          <h3 class="recommendation-title">${gameName}</h3>
          
          <!-- Category Badge -->
          <div class="recommendation-category">📂 ${categoryName}</div>
          
          <!-- Game Description -->
          <p class="recommendation-description">${gameDescription}</p>
          
          <!-- AI Prediction Details -->
          <div class="ai-prediction-details">
            <!-- Average Rating -->
            <div class="prediction-item">
              <div class="prediction-label">⭐ Avg Rating</div>
              <div class="prediction-value">${averageRating.toFixed(1)}/5.0</div>
            </div>
            
            <!-- AI Prediction -->
            <div class="prediction-item">
              <div class="prediction-label">🤖 AI Predicts</div>
              <div class="prediction-value ai-score">${predictedRating.toFixed(1)}/5.0</div>
            </div>
          </div>
          
          <!-- Action Buttons -->
          <div class="recommendation-actions">
            <button class="play-recommended-btn" onclick="window.recommendationsManager.playGame('${gameId}', '${gameName.replace(/'/g, "\\'")}', ${confidence}, ${predictedRating.toFixed(1)})">
              🎮 Play Recommended
            </button>
            
            <button class="info-btn" onclick="window.recommendationsManager.showGameInfo('${gameId}', '${gameName.replace(/'/g, "\\'")}', '${categoryName}', ${averageRating}, ${predictedRating.toFixed(1)}, ${confidence})">
              📊
            </button>
          </div>
        </div>
      </div>
    `;
  }

  playGame(gameId, gameName, confidence, predictedRating) {
    const successMessage = `🎮 Launching ${gameName}!

🤖 AI Confidence: ${confidence}%
⭐ Predicted Rating: ${predictedRating}/5.0

Enjoy your personalized recommendation!`;

    alert(successMessage);
  }

  showGameInfo(gameId, gameName, categoryName, averageRating, predictedRating, confidence) {
    const infoMessage = `📊 AI Analysis:

Game: ${gameName}
Category: ${categoryName}
Average Rating: ${averageRating}/5.0

🤖 AI Prediction: ${predictedRating}/5.0
📈 Confidence: ${confidence}%

This recommendation is based on your gaming history, preferences, and similar users.`;

    alert(infoMessage);
  }
}

// Initialize recommendations manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  window.recommendationsManager = new RecommendationsManager();
});

// Export for global access
window.RecommendationsManager = RecommendationsManager;
