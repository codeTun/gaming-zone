// Navigation functionality
document.addEventListener('DOMContentLoaded', function() {
  console.log('🚀 User Interface loaded');
  
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Navigation handling
  const navItems = document.querySelectorAll('.nav-item');
  const sections = ['homeSection', 'gamesSection', 'tournamentSection', 'eventsSection'];
  
  navItems.forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      
      console.log('🔄 Navigation clicked:', this.getAttribute('id'));
      
      // Remove active class from all nav items
      navItems.forEach(nav => nav.classList.remove('active'));
      
      // Add active class to clicked item
      this.classList.add('active');
      
      // Hide all sections
      sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
          section.style.display = 'none';
        }
      });
      
      // Show target section
      const targetSection = this.getAttribute('id');
      let sectionToShow = '';
      
      switch(targetSection) {
        case 'homeLink':
          sectionToShow = 'homeSection';
          break;
        case 'gamesLink':
          sectionToShow = 'gamesSection';
          // Force games initialization with multiple attempts
          setTimeout(() => {
            console.log('🎮 Attempting to initialize games...');
            console.log('🎮 Games Manager type:', typeof window.gamesManager);
            console.log('🎮 Games Manager object:', window.gamesManager);
            
            if (window.gamesManager) {
              console.log('✅ Games manager found, initializing...');
              window.gamesManager.init();
            } else {
              console.error('❌ Games manager still not found, trying alternative...');
              
              // Try to create new instance if class is available
              if (typeof GamesManager !== 'undefined') {
                console.log('🔧 Creating new GamesManager instance...');
                window.gamesManager = new GamesManager();
                window.gamesManager.init();
              } else {
                console.error('❌ GamesManager class not available');
                
                // Last resort: Load games manually
                loadGamesManually();
              }
            }
          }, 300);
          break;
        case 'tournamentsLink':
          sectionToShow = 'tournamentSection';
          break;
        case 'eventsLink':
          sectionToShow = 'eventsSection';
          break;
      }
      
      const section = document.getElementById(sectionToShow);
      if (section) {
        section.style.display = 'block';
        console.log(`✅ Switched to section: ${sectionToShow}`);
      }
    });
  });

  // CTA Button functionality
  const browseGamesBtn = document.getElementById('browseGamesBtn');
  const viewTournamentsBtn = document.getElementById('viewTournamentsBtn');
  
  if (browseGamesBtn) {
    browseGamesBtn.addEventListener('click', () => {
      console.log('🎮 Browse Games button clicked');
      document.getElementById('gamesLink').click();
    });
  }
  
  if (viewTournamentsBtn) {
    viewTournamentsBtn.addEventListener('click', () => {
      console.log('🏆 View Tournaments button clicked');
      document.getElementById('tournamentsLink').click();
    });
  }
});

// Manual games loading as fallback
async function loadGamesManually() {
  console.log('🔧 Loading games manually as fallback...');
  
  try {
    const response = await fetch('http://localhost/gaming-zone/api/games.php');
    const games = await response.json();
    
    console.log('📦 Manual fetch result:', games);
    
    if (Array.isArray(games) && games.length > 0) {
      displayGamesManually(games);
    } else {
      showNoGamesMessage();
    }
  } catch (error) {
    console.error('❌ Manual games loading failed:', error);
    showErrorMessage(error.message);
  }
}

function displayGamesManually(games) {
  console.log('🎨 Displaying games manually...');
  
  const gamesGrid = document.getElementById('gamesGrid');
  if (!gamesGrid) {
    console.error('❌ Games grid not found');
    return;
  }
  
  const gamesHTML = games.map(game => `
    <div class="game-card" data-game-id="${game.id}">
      <div class="game-image">
        <img src="${game.imageUrl || 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'}" 
             alt="${game.name}" 
             onerror="this.src='https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400'">
        <div class="game-overlay">
          <button class="play-btn">
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
          <button class="btn btn-primary">
            <i class="fas fa-gamepad"></i>
            Play Game
          </button>
          <button class="btn btn-secondary">
            <i class="fas fa-info-circle"></i>
            Details
          </button>
        </div>
      </div>
    </div>
  `).join('');
  
  gamesGrid.innerHTML = gamesHTML;
  console.log(`✅ Successfully displayed ${games.length} games manually`);
}

function showNoGamesMessage() {
  const gamesGrid = document.getElementById('gamesGrid');
  if (gamesGrid) {
    gamesGrid.innerHTML = `
      <div class="no-games">
        <i class="fas fa-gamepad"></i>
        <h3>No games found</h3>
        <p>No games available in the database</p>
      </div>
    `;
  }
}

function showErrorMessage(message) {
  const gamesGrid = document.getElementById('gamesGrid');
  if (gamesGrid) {
    gamesGrid.innerHTML = `
      <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Error Loading Games</h3>
        <p>${message}</p>
        <button class="btn btn-primary" onclick="loadGamesManually()">
          <i class="fas fa-refresh"></i>
          Try Again
        </button>
      </div>
    `;
  }
}

// Handle Logout
function handleLogout() {
  if (confirm('Are you sure you want to logout?')) {
    console.log('👋 User logging out...');
    alert('Logged out successfully!');
  }
}
