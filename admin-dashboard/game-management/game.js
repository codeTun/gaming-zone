class GameManager {
  constructor() {
    this.games = [];
    this.categories = [];
    this.initialized = false;
    this.apiBase = 'http://localhost/gaming-zone/api';
  }

  async init() {
    if (this.initialized) return;
    console.log('🚀 Initializing GameManager with database API integration');
    
    await this.loadCategoriesFromAPI();
    await this.loadGamesFromAPI();
    this.bindEvents();
    this.updateStats();
    this.initialized = true;
    lucide.createIcons();
    console.log("✅ GameManager initialized with database connection");
  }

  async loadCategoriesFromAPI() {
    console.log('📡 Loading categories from database...');
    try {
      const response = await fetch(`${this.apiBase}/categories.php`);
      const data = await response.json();
      
      if (Array.isArray(data)) {
        this.categories = data;
        console.log(`✅ Loaded ${this.categories.length} categories from database`);
      } else {
        console.warn('⚠️ No categories found, using defaults');
        this.categories = [
          { id: 'cat-001', name: 'Action' },
          { id: 'cat-002', name: 'Adventure' },
          { id: 'cat-003', name: 'Puzzle' },
          { id: 'cat-004', name: 'Strategy' },
          { id: 'cat-005', name: 'Sports' },
          { id: 'cat-006', name: 'Racing' }
        ];
      }
    } catch (error) {
      console.error('❌ Failed to load categories:', error);
      // Use default categories if API fails
      this.categories = [
        { id: 'cat-001', name: 'Action' },
        { id: 'cat-002', name: 'Adventure' },
        { id: 'cat-003', name: 'Puzzle' },
        { id: 'cat-004', name: 'Strategy' },
        { id: 'cat-005', name: 'Sports' },
        { id: 'cat-006', name: 'Racing' }
      ];
    }
  }

  async loadGamesFromAPI() {
    console.log('📡 Loading games from database via API...');
    const tableBody = document.getElementById('game_table_body');
    
    try {
      // Show loading state
      if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="6" class="game_loading">Loading games from database...</td></tr>';
      }

      // Fetch games from API
      const response = await fetch(`${this.apiBase}/games.php`);
      const data = await response.json();
      
      console.log('📥 Games loaded from database:', data);
      
      if (Array.isArray(data)) {
        this.games = data;
        console.log(`✅ Successfully loaded ${this.games.length} games from database`);
      } else if (data.error) {
        console.error('❌ API Error:', data.error);
        this.games = [];
      } else {
        console.warn('⚠️ Unexpected API response format');
        this.games = [];
      }
      
      this.renderGamesFromDatabase();
      this.updateStats();
    } catch (error) {
      console.error("❌ Failed to load games from database:", error);
      this.games = [];
      if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="6" class="game_error">Failed to load games from database</td></tr>';
      }
    }
  }

  renderGamesFromDatabase() {
    const tableBody = document.getElementById('game_table_body');
    if (!tableBody) return;

    console.log(`🎨 Rendering ${this.games.length} games from database`);

    if (this.games.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="6" class="game_no_data">No games found in database</td></tr>';
      return;
    }

    tableBody.innerHTML = this.games.map(game => `
      <tr data-game-id="${game.id}">
        <td>
          <div class="game_name_cell">
            <img src="${game.imageUrl || 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=100'}" 
                 alt="${game.name}" class="game_thumb">
            <div>
              <div class="game_title">${game.name}</div>
              <div class="game_subtitle">${game.description || 'No description'}</div>
            </div>
          </div>
        </td>
        <td>
          <span class="game_category_badge">${game.categoryName || 'Unknown'}</span>
        </td>
        <td>
          <div class="game_rating">
            <span class="game_rating_value">${parseFloat(game.averageRating || 0).toFixed(1)}</span>
            <div class="game_stars">
              ${this.generateStars(parseFloat(game.averageRating || 0))}
            </div>
          </div>
        </td>
        <td>
          <span class="game_min_age">${game.minAge ? game.minAge + '+' : 'All Ages'}</span>
        </td>
        <td>
          <span class="game_date">${new Date(game.createdAt).toLocaleDateString()}</span>
        </td>
        <td>
          <div class="game_actions">
            <button class="game_edit_btn" onclick="gameManager.editGameFromDatabase('${game.id}')">
              <i data-lucide="edit-2"></i>
            </button>
            <button class="game_delete_btn" onclick="gameManager.deleteGameFromDatabase('${game.id}')">
              <i data-lucide="trash-2"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join('');

    lucide.createIcons();
  }

  generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = (rating % 1) >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let stars = '';
    
    // Add full stars
    for (let i = 0; i < fullStars; i++) {
      stars += '<i data-lucide="star" class="game_star game_star_filled"></i>';
    }
    
    // Add half star if needed
    if (hasHalfStar) {
      stars += '<i data-lucide="star" class="game_star game_star_half"></i>';
    }
    
    // Add empty stars
    for (let i = 0; i < emptyStars; i++) {
      stars += '<i data-lucide="star" class="game_star game_star_empty"></i>';
    }
    
    console.log(`⭐ Rating ${rating}: ${fullStars} full, ${hasHalfStar ? 1 : 0} half, ${emptyStars} empty stars`);
    return stars;
  }

  async editGameFromDatabase(id) {
    console.log('✏️ Editing game from database, ID:', id);
    const game = this.games.find((g) => g.id === id);
    if (game) {
      console.log('📝 Found game in database:', game);
      this.openGameModal(game);
    } else {
      console.error('❌ Game not found in local cache, fetching from API...');
      try {
        const response = await fetch(`${this.apiBase}/games.php?id=${id}`);
        const gameData = await response.json();
        if (gameData && !gameData.error) {
          this.openGameModal(gameData);
        } else {
          this.showError('Game not found in database');
        }
      } catch (error) {
        console.error('❌ Failed to fetch game from database:', error);
        this.showError('Failed to load game from database');
      }
    }
  }

  async deleteGameFromDatabase(id) {
    if (!confirm("Are you sure you want to delete this game from the database?")) return;

    console.log('🗑️ Deleting game from database, ID:', id);

    try {
      const response = await fetch(`${this.apiBase}/games.php?id=${id}`, {
        method: 'DELETE'
      });
      
      const result = await response.json();
      console.log('📥 Delete response from database:', result);

      if (result.success) {
        this.showSuccess("Game deleted from database successfully");
        await this.loadGamesFromAPI(); // Refresh from database
      } else {
        throw new Error(result.error || 'Delete failed');
      }
    } catch (error) {
      console.error("❌ Failed to delete game from database:", error);
      this.showError("Failed to delete game from database: " + error.message);
    }
  }

  bindEvents() {
    console.log("🔗 Setting up game event listeners...");
    
    const searchInput = document.getElementById('game_search_input');
    const genreFilter = document.getElementById('game_genre_filter');

    if (searchInput) {
      searchInput.addEventListener('input', (e) => this.searchGames(e.target.value));
    }

    if (genreFilter) {
      genreFilter.addEventListener('change', (e) => this.filterByGenre(e.target.value));
    }
  }

  searchGames(query) {
    const filtered = this.games.filter(game => 
      game.name?.toLowerCase().includes(query.toLowerCase()) || 
      game.description?.toLowerCase().includes(query.toLowerCase()) ||
      game.categoryName?.toLowerCase().includes(query.toLowerCase())
    );
    this.renderFilteredGames(filtered);
  }

  filterByGenre(genre) {
    if (genre === 'All') {
      this.renderGamesFromDatabase();
    } else {
      const filtered = this.games.filter(game => game.categoryName === genre);
      this.renderFilteredGames(filtered);
    }
  }

  renderFilteredGames(games) {
    const tableBody = document.getElementById('game_table_body');
    if (!tableBody) return;

    if (games.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="6" class="game_no_data">No games match your search</td></tr>';
      return;
    }

    // Use the same rendering logic but with filtered games
    const originalGames = this.games;
    this.games = games;
    this.renderGamesFromDatabase();
    this.games = originalGames; // Restore original array
  }

  updateStats() {
    const totalGames = this.games.length;
    const avgRating = totalGames > 0 ? 
      (this.games.reduce((sum, game) => sum + (parseFloat(game.averageRating) || 0), 0) / totalGames).toFixed(1) : 
      '0.0';

    document.getElementById('game_total_games').textContent = totalGames;
    document.getElementById('game_average_rating').textContent = avgRating;
    
    console.log(`📊 Updated stats - Games: ${totalGames}, Avg Rating: ${avgRating}`);
  }

  showSuccess(message) {
    this.showNotification(message, "success");
  }

  showError(message) {
    this.showNotification(message, "error");
  }

  showNotification(message, type) {
    // ...existing notification code...
    const existing = document.querySelector(".notification");
    if (existing) existing.remove();

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
      <i data-lucide="${type === "success" ? "check-circle" : "x-circle"}"></i>
      <span>${message}</span>
    `;

    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === "success" ? "#22c55e" : "#ef4444"};
      color: white;
      padding: 15px 20px;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      z-index: 10000;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 600;
      animation: slideIn 0.3s ease;
      max-width: 400px;
    `;

    document.body.appendChild(notification);
    lucide.createIcons();

    setTimeout(() => notification.remove(), 5000);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Initializing GameManager with database API integration");
  window.gameManager = new GameManager();
});
