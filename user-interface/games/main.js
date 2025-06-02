document.addEventListener("DOMContentLoaded", () => {
  console.log('🎮 Games main.js file loaded');

  class GamesManager {
    constructor() {
      this.games = [];
      this.filteredGames = [];
      this.apiBase = 'http://localhost/gaming-zone/api';
      this.initialized = false;
      console.log('🏗️ GamesManager constructor called');
    }

    async init() {
      console.log('🚀 GamesManager.init() called');
      
      if (this.initialized) {
        console.log('🔄 Games manager already initialized, refreshing...');
        await this.loadGames();
        return;
      }

      console.log('🎮 First time initializing Games Manager...');
      await this.loadGames();
      this.bindEvents();
      this.initialized = true;
      console.log('✅ GamesManager fully initialized');
    }

    async loadGames() {
      console.log('📡 Starting to fetch games from API...');
      
      try {
        // Show loading state
        this.showLoading();
        
        const apiUrl = `${this.apiBase}/games.php`;
        console.log('📡 Making request to:', apiUrl);
        
        const response = await fetch(apiUrl, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        console.log('📥 Response received:', response.status, response.statusText);
        
        if (!response.ok) {
          const errorText = await response.text();
          console.error('❌ API Error Response:', errorText);
          throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const data = await response.json();
        console.log('📦 Raw API data:', data);
        console.log('📦 Data type:', typeof data, 'Is Array:', Array.isArray(data));
        
        if (Array.isArray(data)) {
          this.games = data;
          this.filteredGames = [...this.games];
          console.log(`✅ Successfully loaded ${this.games.length} games:`, this.games);
          this.renderGames();
        } else if (data && data.error) {
          console.error('❌ API returned error:', data.error);
          throw new Error(data.error);
        } else {
          console.error('❌ Invalid response format:', data);
          throw new Error('Invalid response format from API');
        }
      } catch (error) {
        console.error('❌ Failed to load games:', error);
        this.showError('Failed to load games: ' + error.message);
      }
    }

    showLoading() {
      console.log('🔄 Showing loading state...');
      const gamesGrid = document.getElementById('gamesGrid');
      if (gamesGrid) {
        gamesGrid.innerHTML = `
          <div class="loading-container">
            <div class="loading-spinner">
              <div class="spinner"></div>
            </div>
            <p>Loading games from database...</p>
          </div>
        `;
      } else {
        console.error('❌ gamesGrid element not found!');
      }
    }

    renderGames() {
      console.log(`🎨 Starting to render ${this.filteredGames.length} games...`);
      
      const gamesGrid = document.getElementById('gamesGrid');
      if (!gamesGrid) {
        console.error('❌ Games grid element not found!');
        return;
      }

      if (this.filteredGames.length === 0) {
        console.log('📭 No games to display');
        gamesGrid.innerHTML = `
          <div class="no-games">
            <i class="fas fa-gamepad"></i>
            <h3>No games found</h3>
            <p>No games available in the database</p>
          </div>
        `;
        return;
      }

      console.log('🔧 Building games HTML...');
      const gamesHTML = this.filteredGames.map((game, index) => {
        console.log(`🎯 Processing game ${index + 1}:`, game);
        return `
          <div class="game-card" data-game-id="${game.id}">
            <div class="game-image">
              <img src="${game.imageUrl || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'}" 
                   alt="${game.name}" 
                   onerror="this.src='https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'">
              <div class="game-overlay">
                <button class="play-btn" onclick="window.gamesManager.playGame('${game.id}')">
                  <i class="fas fa-play"></i>
                  Play Now
                </button>
              </div>
            </div>
            <div class="game-info">
              <h3 class="game-title">${game.name}</h3>
              <p class="game-description">${game.description || 'No description available'}</p>
              <div class="game-meta">
                <span class="game-category">
                  <i class="fas fa-tag"></i>
                  ${game.categoryName || 'Unknown'}
                </span>
                <span class="game-rating">
                  <i class="fas fa-star"></i>
                  ${game.averageRating || '0.0'}
                </span>
              </div>
              <div class="game-details">
                ${game.minAge ? `<span class="age-rating">Age: ${game.minAge}+</span>` : ''}
                ${game.targetGender ? `<span class="target-gender">${game.targetGender}</span>` : ''}
              </div>
              <div class="game-actions">
                <button class="btn btn-primary" onclick="window.gamesManager.playGame('${game.id}')">
                  <i class="fas fa-gamepad"></i>
                  Play Game
                </button>
                <button class="btn btn-secondary" onclick="window.gamesManager.viewDetails('${game.id}')">
                  <i class="fas fa-info-circle"></i>
                  Details
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      console.log('📝 Setting innerHTML with games HTML...');
      gamesGrid.innerHTML = gamesHTML;
      console.log(`✅ Successfully rendered ${this.filteredGames.length} games in DOM`);
    }

    bindEvents() {
      console.log('🔗 Binding search and filter events...');
      
      // Search functionality
      const searchInput = document.getElementById('searchInput');
      if (searchInput) {
        searchInput.addEventListener('input', (e) => {
          console.log('🔍 Search input changed:', e.target.value);
          this.filterGames(e.target.value, null, null);
        });
        console.log('✅ Search input bound');
      }

      // Category filter
      const categoryFilter = document.getElementById('gamesCategoryFilter');
      if (categoryFilter) {
        categoryFilter.addEventListener('change', (e) => {
          console.log('🏷️ Category filter changed:', e.target.value);
          const searchTerm = searchInput ? searchInput.value : '';
          const ratingFilter = document.getElementById('gamesRatingFilter')?.value || '';
          this.filterGames(searchTerm, e.target.value, ratingFilter);
        });
        console.log('✅ Category filter bound');
      }

      // Rating filter
      const ratingFilter = document.getElementById('gamesRatingFilter');
      if (ratingFilter) {
        ratingFilter.addEventListener('change', (e) => {
          console.log('⭐ Rating filter changed:', e.target.value);
          const searchTerm = searchInput ? searchInput.value : '';
          const categoryFilter = document.getElementById('gamesCategoryFilter')?.value || '';
          this.filterGames(searchTerm, categoryFilter, e.target.value);
        });
        console.log('✅ Rating filter bound');
      }
    }

    filterGames(searchTerm = '', category = '', rating = '') {
      console.log('🔍 Filtering games with:', { searchTerm, category, rating });
      
      this.filteredGames = this.games.filter(game => {
        const matchesSearch = !searchTerm || 
          game.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
          (game.description && game.description.toLowerCase().includes(searchTerm.toLowerCase()));
        
        const matchesCategory = !category || 
          (game.categoryName && game.categoryName.toLowerCase() === category.toLowerCase());
        
        const matchesRating = !rating || 
          (game.averageRating && parseFloat(game.averageRating) >= parseFloat(rating));

        return matchesSearch && matchesCategory && matchesRating;
      });

      console.log(`🔍 Filtered results: ${this.filteredGames.length} out of ${this.games.length} games`);
      this.renderGames();
    }

    playGame(gameId) {
      const game = this.games.find(g => g.id === gameId);
      if (game) {
        console.log('🎮 Playing game:', game.name);
        alert(`🎮 Launching ${game.name}!\n\nThis would normally open the game interface.`);
      }
    }

    viewDetails(gameId) {
      const game = this.games.find(g => g.id === gameId);
      if (game) {
        console.log('📋 Viewing details for:', game.name);
        alert(`📋 Game Details:\n\nName: ${game.name}\nCategory: ${game.categoryName}\nRating: ${game.averageRating}\nAge: ${game.minAge}+\n\nDescription: ${game.description}`);
      }
    }

    showError(message) {
      console.log('❌ Showing error message:', message);
      const gamesGrid = document.getElementById('gamesGrid');
      if (gamesGrid) {
        gamesGrid.innerHTML = `
          <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Error Loading Games</h3>
            <p>${message}</p>
            <button class="btn btn-primary" onclick="window.gamesManager.loadGames()">
              <i class="fas fa-refresh"></i>
              Try Again
            </button>
          </div>
        `;
      }
    }
  }

  // Create global instance immediately
  console.log('🌐 Creating global GamesManager instance...');
  window.gamesManager = new GamesManager();
  console.log('✅ Global GamesManager created:', window.gamesManager);

  // Test API directly
  console.log('🧪 Testing API connection...');
  fetch('http://localhost/gaming-zone/api/games.php')
    .then(response => {
      console.log('🧪 API Test Response:', response.status, response.statusText);
      return response.json();
    })
    .then(data => {
      console.log('🧪 API Test Data:', data);
    })
    .catch(error => {
      console.error('🧪 API Test Failed:', error);
    });
});
