console.log('🏆 Tournament.js file loaded');

class TournamentManager {
  constructor() {
    this.tournaments = [];
    this.filteredTournaments = [];
    this.apiBase = 'http://localhost/gaming-zone/api';
    this.initialized = false;
    this.currentUser = {
      id: 'user-001', // Demo user - in real app get from session
      username: 'Elazheri Iheb',
      email: 'iheb@example.com'
    };
    console.log('🏗️ TournamentManager constructor called');
  }

  async init() {
    console.log('🚀 TournamentManager.init() called');
    
    if (this.initialized) {
      console.log('🔄 Tournament manager already initialized, refreshing...');
      await this.loadTournaments();
      return;
    }

    console.log('🏆 First time initializing Tournament Manager...');
    await this.loadTournaments();
    this.bindEvents();
    this.initialized = true;
    console.log('✅ TournamentManager fully initialized');
  }

  async loadTournaments() {
    console.log('📡 Starting to fetch tournaments from API...');
    
    try {
      this.showLoading();
      
      const apiUrl = `${this.apiBase}/tournaments.php`;
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
      
      if (Array.isArray(data)) {
        this.tournaments = data;
        this.filteredTournaments = [...this.tournaments];
        console.log(`✅ Successfully loaded ${this.tournaments.length} tournaments:`, this.tournaments);
        this.renderTournaments();
      } else if (data && data.error) {
        console.error('❌ API returned error:', data.error);
        throw new Error(data.error);
      } else {
        console.error('❌ Invalid response format:', data);
        throw new Error('Invalid response format from API');
      }
    } catch (error) {
      console.error('❌ Failed to load tournaments:', error);
      this.showError('Failed to load tournaments: ' + error.message);
    }
  }

  showLoading() {
    console.log('🔄 Showing loading state...');
    const tournamentsGrid = document.getElementById('tournamentsGrid');
    if (tournamentsGrid) {
      tournamentsGrid.innerHTML = `
        <div style="
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          height: 300px;
          color: #94a3b8;
          grid-column: 1 / -1;
        ">
          <div style="
            width: 50px;
            height: 50px;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
          "></div>
          <p>Loading tournaments from database...</p>
        </div>
      `;
    }
  }

  renderTournaments() {
    console.log(`🎨 Starting to render ${this.filteredTournaments.length} tournaments...`);
    
    const tournamentsGrid = document.getElementById('tournamentsGrid');
    if (!tournamentsGrid) {
      console.error('❌ Tournaments grid element not found!');
      return;
    }

    // Set grid layout  
    tournamentsGrid.style.cssText = `
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
      gap: 30px;
      margin-top: 30px;
      padding: 20px 0;
    `;

    if (this.filteredTournaments.length === 0) {
      console.log('📭 No tournaments to display');
      tournamentsGrid.innerHTML = `
        <div style="
          text-align: center;
          padding: 60px 20px;
          color: #94a3b8;
          grid-column: 1 / -1;
        ">
          <i class="fas fa-trophy" style="font-size: 4rem; color: #475569; margin-bottom: 20px;"></i>
          <h3 style="font-size: 1.5rem; color: #f8fafc; margin-bottom: 12px;">No tournaments found</h3>
          <p>No tournaments available in the database</p>
        </div>
      `;
      return;
    }

    const tournamentsHTML = this.filteredTournaments.map((tournament, index) => {
      console.log(`🏆 Processing tournament ${index + 1}:`, tournament);
      
      const startDate = new Date(tournament.startDate);
      const endDate = new Date(tournament.endDate);
      const now = new Date();
      const isActive = now >= startDate && now <= endDate;
      const isUpcoming = now < startDate;
      const isEnded = now > endDate;
      
      let statusBadge = '';
      let statusColor = '';
      
      if (isEnded) {
        statusBadge = '🏁 Ended';
        statusColor = '#ef4444';
      } else if (isActive) {
        statusBadge = '🔥 Live';
        statusColor = '#22c55e';
      } else if (isUpcoming) {
        statusBadge = '📅 Upcoming';
        statusColor = '#3b82f6';
      }

      return `
        <div class="tournament-card" style="
          background: rgba(248, 250, 252, 0.05);
          backdrop-filter: blur(20px);
          border: 1px solid rgba(248, 250, 252, 0.1);
          border-radius: 20px;
          overflow: hidden;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          position: relative;
          box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        " onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 25px 50px rgba(0, 0, 0, 0.3)'; this.style.borderColor='rgba(59, 130, 246, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 32px rgba(0, 0, 0, 0.1)'; this.style.borderColor='rgba(248, 250, 252, 0.1)'">
          
          <div class="tournament-image" style="
            position: relative;
            height: 220px;
            overflow: hidden;
            background: linear-gradient(45deg, #667eea, #764ba2);
          ">
            <img src="${tournament.imageUrl || 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400'}" 
                 alt="${tournament.name}" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='scale(1.05)'"
                 onmouseout="this.style.transform='scale(1)'"
                 onerror="this.src='https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400'">
            
            <div style="
              position: absolute;
              top: 15px;
              left: 15px;
              background: ${statusColor};
              color: white;
              padding: 6px 12px;
              border-radius: 20px;
              font-size: 0.8rem;
              font-weight: 700;
              text-transform: uppercase;
              letter-spacing: 0.5px;
            ">
              ${statusBadge}
            </div>
            
            <div style="
              position: absolute;
              top: 15px;
              right: 15px;
              background: rgba(0, 0, 0, 0.7);
              color: #22c55e;
              padding: 6px 12px;
              border-radius: 20px;
              font-size: 0.8rem;
              font-weight: 600;
            ">
              💰 $${tournament.prizePool || '0'}
            </div>
          </div>
          
          <div class="tournament-info" style="padding: 25px;">
            <h3 style="
              color: #f8fafc;
              margin: 0 0 12px 0;
              font-size: 1.4rem;
              font-weight: 700;
              line-height: 1.2;
            ">${tournament.name}</h3>
            
            <p style="
              color: #cbd5e1;
              font-size: 0.9rem;
              line-height: 1.6;
              margin-bottom: 20px;
              display: -webkit-box;
              -webkit-line-clamp: 2;
              -webkit-box-orient: vertical;
              overflow: hidden;
            ">${tournament.description || 'Join this exciting tournament and compete with the best players!'}</p>
            
            <div style="margin-bottom: 20px;">
              <div style="
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                color: #94a3b8;
                font-size: 0.85rem;
              ">
                <span>📅 Start: ${startDate.toLocaleDateString()} ${startDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
              </div>
              <div style="
                display: flex;
                justify-content: space-between;
                color: #94a3b8;
                font-size: 0.85rem;
              ">
                <span>🏁 End: ${endDate.toLocaleDateString()} ${endDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
              </div>
            </div>
            
            <div style="
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 25px;
              padding: 15px;
              background: rgba(59, 130, 246, 0.1);
              border-radius: 12px;
              border: 1px solid rgba(59, 130, 246, 0.2);
            ">
              <div style="text-align: center;">
                <div style="color: #93c5fd; font-size: 0.8rem; margin-bottom: 4px;">Max Players</div>
                <div style="color: #f8fafc; font-weight: 700; font-size: 1.1rem;">${tournament.maxParticipants || 50}</div>
              </div>
              <div style="text-align: center;">
                <div style="color: #93c5fd; font-size: 0.8rem; margin-bottom: 4px;">Prize Pool</div>
                <div style="color: #22c55e; font-weight: 700; font-size: 1.1rem;">$${tournament.prizePool || '0'}</div>
              </div>
            </div>
            
            <button onclick="window.tournamentManager.openRegistrationModal('${tournament.id}', '${tournament.name}')" 
                    ${isEnded ? 'disabled' : ''} 
                    style="
              width: 100%;
              background: ${isEnded ? 'rgba(156, 163, 175, 0.5)' : 'linear-gradient(135deg, #3b82f6, #1d4ed8)'};
              color: white;
              border: none;
              padding: 16px 24px;
              border-radius: 12px;
              font-weight: 700;
              font-size: 1rem;
              cursor: ${isEnded ? 'not-allowed' : 'pointer'};
              transition: all 0.3s ease;
              text-transform: uppercase;
              letter-spacing: 0.5px;
            " ${!isEnded ? `onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'"` : ''}>
              ${isEnded ? '🏁 Tournament Ended' : '🎮 Register Now'}
            </button>
          </div>
        </div>
      `;
    }).join('');

    tournamentsGrid.innerHTML = tournamentsHTML;
    console.log(`✅ Successfully rendered ${this.filteredTournaments.length} tournaments`);
  }

  bindEvents() {
    console.log('🔗 Binding tournament search and filter events...');
    
    const searchInput = document.getElementById('tournamentSearchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        console.log('🔍 Tournament search input changed:', e.target.value);
        this.filterTournaments(e.target.value);
      });
      console.log('✅ Tournament search input bound');
    }
  }

  filterTournaments(searchTerm = '') {
    console.log('🔍 Filtering tournaments with:', { searchTerm });
    
    this.filteredTournaments = this.tournaments.filter(tournament => {
      const matchesSearch = !searchTerm || 
        tournament.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (tournament.description && tournament.description.toLowerCase().includes(searchTerm.toLowerCase()));
      
      return matchesSearch;
    });

    console.log(`🔍 Filtered results: ${this.filteredTournaments.length} out of ${this.tournaments.length} tournaments`);
    this.renderTournaments();
  }

  openRegistrationModal(tournamentId, tournamentName) {
    console.log('🎯 Opening registration modal for tournament:', tournamentId);
    
    window.openTournamentRegistrationModal(tournamentId, tournamentName);
  }

  showError(message) {
    console.log('❌ Showing error message:', message);
    const tournamentsGrid = document.getElementById('tournamentsGrid');
    if (tournamentsGrid) {
      tournamentsGrid.innerHTML = `
        <div style="
          text-align: center;
          padding: 60px 20px;
          color: #94a3b8;
          grid-column: 1 / -1;
        ">
          <i class="fas fa-exclamation-triangle" style="font-size: 4rem; color: #475569; margin-bottom: 20px;"></i>
          <h3 style="font-size: 1.5rem; color: #f8fafc; margin-bottom: 12px;">Error Loading Tournaments</h3>
          <p style="margin-bottom: 20px;">${message}</p>
          <button onclick="window.tournamentManager.loadTournaments()" style="
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
          ">
            <i class="fas fa-refresh"></i>
            Try Again
          </button>
        </div>
      `;
    }
  }
}

// Create global instance
console.log('🌐 Creating global TournamentManager instance...');
window.tournamentManager = new TournamentManager();

console.log('✅ Tournament.js setup complete');
