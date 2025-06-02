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
            <span class="game_rating_value">${game.averageRating || '0.0'}</span>
            <div class="game_stars">
              ${this.generateStars(game.averageRating || 0)}
            </div>
          </div>
        </td>
        <td>
          <span class="game_players_count">N/A</span>
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
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let stars = '';
    for (let i = 0; i < fullStars; i++) {
      stars += '<i data-lucide="star" class="game_star game_star_filled"></i>';
    }
    if (hasHalfStar) {
      stars += '<i data-lucide="star" class="game_star game_star_half"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
      stars += '<i data-lucide="star" class="game_star game_star_empty"></i>';
    }
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

  openGameModal(game = null) {
    console.log("🔍 Opening game modal for database operation, game:", game);
    
    // Create modal HTML dynamically if it doesn't exist
    this.createGameModalIfNeeded();
    
    const modal = document.getElementById("game_modal");
    const title = document.getElementById("game_modal_title");
    const form = document.getElementById("game_form");

    if (!modal || !title || !form) {
      console.error("Game modal elements not found!");
      return;
    }

    if (game) {
      this.currentEditId = game.id;
      title.textContent = "Edit Game in Database";
      console.log('📝 Editing game from database:', game.id);
    } else {
      this.currentEditId = null;
      title.textContent = "Add New Game to Database";
      console.log('📝 Adding new game to database');
      form.reset();
    }

    modal.style.display = "flex";

    setTimeout(() => {
      this.populateCategoryDropdown();
      
      if (game) {
        this.populateGameForm(game);
      }

      document.getElementById("game_name")?.focus();
    }, 100);
  }

  createGameModalIfNeeded() {
    if (document.getElementById("game_modal")) return;
    
    const modalHTML = `
      <div class="game_modal" id="game_modal" style="display: none;">
        <div class="game_modal_content">
          <div class="game_modal_header">
            <h2 id="game_modal_title">Add New Game</h2>
            <button class="game_close_btn" id="game_close_modal">
              <i data-lucide="x"></i>
            </button>
          </div>
          <form id="game_form" class="game_form">
            <div class="game_form_group">
              <label for="game_name">Game Name *</label>
              <input type="text" id="game_name" class="game_input" required />
            </div>
            
            <div class="game_form_row">
              <div class="game_form_group">
                <label for="game_category">Category *</label>
                <select id="game_category" class="game_select" required>
                  <option value="">Select Category</option>
                </select>
              </div>
              
              <div class="game_form_group">
                <label for="game_min_age">Minimum Age</label>
                <input type="number" id="game_min_age" class="game_input" min="3" max="18" />
              </div>
            </div>
            
            <div class="game_form_group">
              <label for="game_target_gender">Target Gender</label>
              <select id="game_target_gender" class="game_select">
                <option value="">Any</option>
                <option value="MALE">Male</option>
                <option value="FEMALE">Female</option>
              </select>
            </div>
            
            <div class="game_form_group">
              <label for="game_description">Description</label>
              <textarea id="game_description" class="game_textarea" rows="4"></textarea>
            </div>
            
            <div class="game_form_group">
              <label for="game_image">Image URL</label>
              <input type="url" id="game_image" class="game_input" />
            </div>
            
            <div class="game_form_actions">
              <button type="button" class="game_secondary_btn" id="game_cancel_btn">
                <i data-lucide="x"></i>
                Cancel
              </button>
              <button type="submit" class="game_primary_btn">
                <i data-lucide="check"></i>
                Save Game
              </button>
            </div>
          </form>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    this.bindModalEvents();
  }

  bindModalEvents() {
    const modal = document.getElementById("game_modal");
    const closeBtn = document.getElementById("game_close_modal");
    const cancelBtn = document.getElementById("game_cancel_btn");
    const form = document.getElementById("game_form");

    if (closeBtn) closeBtn.onclick = () => this.closeGameModal();
    if (cancelBtn) cancelBtn.onclick = () => this.closeGameModal();
    if (form) form.onsubmit = (e) => this.handleGameSubmit(e);
    
    if (modal) {
      modal.onclick = (e) => {
        if (e.target === modal) this.closeGameModal();
      };
    }
  }

  populateCategoryDropdown() {
    const categorySelect = document.getElementById("game_category");
    if (!categorySelect) return;

    categorySelect.innerHTML = '<option value="">Select Category</option>' +
      this.categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
  }

  populateGameForm(game) {
    document.getElementById("game_name").value = game.name || "";
    document.getElementById("game_category").value = game.categoryId || "";
    document.getElementById("game_min_age").value = game.minAge || "";
    document.getElementById("game_target_gender").value = game.targetGender || "";
    document.getElementById("game_description").value = game.description || "";
    document.getElementById("game_image").value = game.imageUrl || "";
  }

  async handleGameSubmit(e) {
    e.preventDefault();
    
    console.log('💾 Saving game to database...');
    
    const submitBtn = document.querySelector('#game_form button[type="submit"]');
    const originalBtnText = submitBtn?.innerHTML || 'Save Game';
    if (submitBtn) {
      submitBtn.innerHTML = '<i data-lucide="loader"></i>Saving to DB...';
      submitBtn.disabled = true;
      lucide.createIcons();
    }

    try {
      const gameData = {
        name: document.getElementById('game_name').value.trim(),
        description: document.getElementById('game_description').value.trim(),
        imageUrl: document.getElementById('game_image').value.trim(),
        categoryId: document.getElementById('game_category').value,
        minAge: document.getElementById('game_min_age').value || null,
        targetGender: document.getElementById('game_target_gender').value || null
      };
      
      console.log('📊 Game data for database:', gameData);
      
      // Validate required fields
      if (!gameData.name) throw new Error('Game name is required');
      if (!gameData.categoryId) throw new Error('Category is required');

      // Save to database via API
      let result;
      if (this.currentEditId) {
        console.log(`🔄 Updating game in database, ID: ${this.currentEditId}`);
        result = await window.apiService.put(`/games.php?id=${this.currentEditId}`, gameData);
      } else {
        console.log('➕ Creating new game in database');
        result = await window.apiService.post('/games.php', gameData);
      }
      
      console.log('✅ Database operation successful:', result);
      
      if (result.success) {
        this.showSuccess(this.currentEditId ? 'Game updated in database' : 'Game saved to database successfully');
        this.closeGameModal();
        await this.loadGamesFromAPI(); // Refresh from database
      } else {
        throw new Error(result.error || 'Database operation failed');
      }

    } catch (error) {
      console.error('❌ Failed to save to database:', error);
      this.showError('Failed to save to database: ' + error.message);
    } finally {
      if (submitBtn) {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
      }
      lucide.createIcons();
    }
  }

  closeGameModal() {
    const modal = document.getElementById("game_modal");
    if (modal) modal.style.display = "none";
    this.currentEditId = null;
  }

  bindEvents() {
    const searchInput = document.getElementById('game_search_input');
    const genreFilter = document.getElementById('game_genre_filter');

    if (searchInput) {
      searchInput.addEventListener('input', (e) => this.searchGames(e.target.value));
    }

    if (genreFilter) {
      genreFilter.addEventListener('change', (e) => this.filterByGenre(e.target.value));
    }

    // Add "Add Game" button if it doesn't exist
    this.createAddGameButtonIfNeeded();
  }

  createAddGameButtonIfNeeded() {
    if (document.getElementById('game_add_btn')) return;
    
    const actionBar = document.querySelector('.game_search_filter');
    if (actionBar) {
      const addBtn = document.createElement('button');
      addBtn.id = 'game_add_btn';
      addBtn.className = 'game_primary_btn';
      addBtn.innerHTML = '<i data-lucide="plus-circle"></i>Add New Game';
      addBtn.onclick = () => this.openGameModal();
      actionBar.appendChild(addBtn);
      lucide.createIcons();
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
