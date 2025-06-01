class TournamentManager {
  constructor() {
    this.tournaments = [];
    this.currentEditId = null;
    this.initialized = false;
  }

  async init() {
    if (this.initialized) return;
    this.bindEvents();
    this.initialized = true;
    lucide.createIcons();
  }

  bindEvents() {
    const addBtn = document.getElementById("tournament_add_btn");
    const modal = document.getElementById("tournament_modal");
    const closeBtn = document.getElementById("tournament_close_modal");
    const cancelBtn = document.getElementById("tournament_cancel_btn");
    const form = document.getElementById("tournament_form");
    const searchInput = document.getElementById("tournament_search_input");

    if (addBtn) addBtn.addEventListener("click", () => this.openModal());
    if (closeBtn) closeBtn.addEventListener("click", () => this.closeModal());
    if (cancelBtn) cancelBtn.addEventListener("click", () => this.closeModal());
    if (form) form.addEventListener("submit", (e) => this.handleSubmit(e));
    if (searchInput)
      searchInput.addEventListener("input", (e) =>
        this.searchTournaments(e.target.value)
      );

    if (modal) {
      modal.addEventListener("click", (e) => {
        if (e.target === modal) this.closeModal();
      });
    }
  }

  async loadTournaments() {
    if (
      !document.getElementById("tournaments") ||
      !document.getElementById("tournaments").classList.contains("active")
    ) {
      return;
    }

    try {
      // Try to load from your database API
      const response = await apiService.get("/tournaments.php");
      this.tournaments = Array.isArray(response)
        ? response
        : response.data || response.tournaments || response.result || [];
      
      // If API returns empty or no data, use mock data
      if (!this.tournaments || this.tournaments.length === 0) {
        console.warn("No tournaments from API, using mock data");
        this.tournaments = this.getMockTournaments();
      }
      
      this.renderTournaments();
    } catch (error) {
      console.error("Failed to load tournaments from API:", error);
      // Fallback to mock data
      this.tournaments = this.getMockTournaments();
      this.renderTournaments();
      this.showError("Could not load tournaments from server. Using local data.");
    }
  }

  getMockTournaments() {
    return [
      {
        id: "tournament-001",
        name: "Spring Gaming Championship",
        description: "Annual gaming tournament with cash prizes",
        imageUrl: "https://images.unsplash.com/photo-1511512578047-dfb367046420?w=500",
        startDate: "2024-06-01T09:00:00",
        endDate: "2024-06-03T18:00:00",
        prizePool: 5000.0,
        maxParticipants: 100,
        type: "TOURNAMENT",
      },
      {
        id: "tournament-002",
        name: "Summer Esports Cup",
        description: "Competitive gaming tournament for all skill levels",
        imageUrl: "https://images.unsplash.com/photo-1542751371-adc38448a05e?w=500",
        startDate: "2024-07-15T10:00:00",
        endDate: "2024-07-17T20:00:00",
        prizePool: 3000.0,
        maxParticipants: 64,
        type: "TOURNAMENT",
      },
    ];
  }

  renderTournaments(tournamentsToRender = this.tournaments) {
    const grid = document.getElementById("tournament_grid");
    if (!grid) return;

    if (tournamentsToRender.length === 0) {
      grid.innerHTML =
        '<div class="tournament_no_data">No tournaments found</div>';
      return;
    }

    grid.innerHTML = tournamentsToRender
      .map(
        (tournament) => `
            <div class="tournament_card">
                <div class="tournament_image_container">
                    <img src="${
                      tournament.imageUrl ||
                      "https://images.unsplash.com/photo-1511512578047-dfb367046420?w=500"
                    }" alt="${tournament.name}" class="tournament_image">
                    <div class="tournament_type_badge">Tournament</div>
                </div>
                <div class="tournament_content">
                    <h3 class="tournament_title">${tournament.name}</h3>
                    <p class="tournament_description">${
                      tournament.description
                    }</p>
                    <div class="tournament_meta">
                        <div class="tournament_meta_item tournament_prize">
                            <i data-lucide="dollar-sign"></i>
                            Prize: $${
                              tournament.prizePool?.toLocaleString() || "0"
                            }
                        </div>
                        <div class="tournament_meta_item tournament_participants">
                            <i data-lucide="users"></i>
                            Max: ${tournament.maxParticipants || 50}
                        </div>
                        <div class="tournament_meta_item tournament_date">
                            <i data-lucide="calendar"></i>
                            ${new Date(tournament.startDate).toLocaleDateString(
                              "en-US",
                              {
                                weekday: "short",
                                year: "numeric",
                                month: "short",
                                day: "numeric",
                              }
                            )}
                        </div>
                    </div>
                    <div class="tournament_actions">
                        <button class="tournament_edit_btn" onclick="tournamentManager.editTournament('${
                          tournament.id
                        }')">
                            <i data-lucide="edit-2"></i>
                            Edit
                        </button>
                        <button class="tournament_delete_btn" onclick="tournamentManager.deleteTournament('${
                          tournament.id
                        }')">
                            <i data-lucide="trash-2"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `
      )
      .join("");

    lucide.createIcons();
  }

  openModal(tournament = null) {
    const modal = document.getElementById("tournament_modal");
    const title = document.getElementById("tournament_modal_title");
    const form = document.getElementById("tournament_form");

    if (tournament) {
      this.currentEditId = tournament.id;
      title.textContent = "Edit Tournament";
      this.populateForm(tournament);
    } else {
      this.currentEditId = null;
      title.textContent = "Add New Tournament";
      form.reset();
      // Set default values
      document.getElementById('tournament_start_time').value = '09:00';
      document.getElementById('tournament_end_time').value = '18:00';
      document.getElementById('tournament_max_participants').value = '50';
      document.getElementById('tournament_prize_pool').value = '0';
    }

    modal.style.display = "flex";
  }

  closeModal() {
    const modal = document.getElementById("tournament_modal");
    modal.style.display = "none";
    this.currentEditId = null;
  }

  populateForm(tournament) {
    document.getElementById('tournament_title').value = tournament.name;
    document.getElementById('tournament_description').value = tournament.description;
    document.getElementById('tournament_image').value = tournament.imageUrl || '';
    document.getElementById('tournament_prize_pool').value = tournament.prizePool || 0;
    document.getElementById('tournament_max_participants').value = tournament.maxParticipants || 50;
    
    // Handle dates
    const startDate = new Date(tournament.startDate);
    const endDate = new Date(tournament.endDate);
    
    document.getElementById('tournament_start_date').value = startDate.toISOString().split('T')[0];
    document.getElementById('tournament_start_time').value = startDate.toTimeString().slice(0, 5);
    document.getElementById('tournament_end_date').value = endDate.toISOString().split('T')[0];
    document.getElementById('tournament_end_time').value = endDate.toTimeString().slice(0, 5);
  }

  async handleSubmit(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('tournament_title').value.trim(),
        description: document.getElementById('tournament_description').value.trim(),
        imageUrl: document.getElementById('tournament_image').value.trim(),
        startDate: document.getElementById('tournament_start_date').value + 'T' + 
                  document.getElementById('tournament_start_time').value + ':00',
        endDate: document.getElementById('tournament_end_date').value + 'T' + 
                document.getElementById('tournament_end_time').value + ':00',
        prizePool: parseFloat(document.getElementById('tournament_prize_pool').value) || 0,
        maxParticipants: parseInt(document.getElementById('tournament_max_participants').value) || 50,
        type: 'TOURNAMENT'
    };

    // Validate required fields
    if (!formData.name) {
        this.showError('Tournament name is required');
        return;
    }
    
    if (!formData.description) {
        this.showError('Tournament description is required');
        return;
    }

    // Validate dates
    if (new Date(formData.startDate) >= new Date(formData.endDate)) {
        this.showError('End date must be after start date');
        return;
    }

    try {
        let response;
        if (this.currentEditId) {
            // Update existing tournament
            response = await apiService.put(`/tournaments.php?id=${this.currentEditId}`, formData);
            this.showSuccess('Tournament updated successfully');
        } else {
            // Create new tournament
            response = await apiService.post('/tournaments.php', formData);
            this.showSuccess('Tournament created successfully');
        }
        
        console.log('Tournament operation response:', response);
        this.closeModal();
        await this.loadTournaments();
    } catch (error) {
        console.error('Failed to save tournament:', error);
        if (error.message.includes('404')) {
            this.showError('Tournament API endpoint not found. Please check your server configuration.');
        } else if (error.message.includes('500')) {
            this.showError('Server error. Please check your database connection.');
        } else {
            this.showError('Failed to save tournament: ' + error.message);
        }
        
        // Fallback: if this is a new tournament, add to mock data
        if (!this.currentEditId) {
            const newTournament = {
                id: `tournament-${Date.now()}`,
                ...formData
            };
            this.tournaments.push(newTournament);
            this.renderTournaments();
            this.closeModal();
            this.showSuccess('Tournament saved locally (server unavailable)');
        }
    }
  }

  async editTournament(id) {
    const tournament = this.tournaments.find((t) => t.id === id);
    if (tournament) {
      this.openModal(tournament);
    }
  }

  async deleteTournament(id) {
    if (!confirm("Are you sure you want to delete this tournament?")) return;

    try {
      const response = await apiService.delete(`/tournaments.php?id=${id}`);
      console.log('Delete response:', response);
      this.showSuccess("Tournament deleted successfully");
      await this.loadTournaments();
    } catch (error) {
      console.error("Failed to delete tournament:", error);
      if (error.message.includes('404')) {
        this.showError('Tournament not found or API endpoint unavailable.');
      } else if (error.message.includes('500')) {
        this.showError('Server error while deleting tournament.');
      } else {
        this.showError("Failed to delete tournament: " + error.message);
      }
      
      // Fallback: remove from local array
      this.tournaments = this.tournaments.filter(t => t.id !== id);
      this.renderTournaments();
      this.showSuccess('Tournament removed locally (server unavailable)');
    }
  }

  searchTournaments(query) {
    const filtered = this.tournaments.filter(
      (tournament) =>
        tournament.name.toLowerCase().includes(query.toLowerCase()) ||
        tournament.description.toLowerCase().includes(query.toLowerCase())
    );
    this.renderTournaments(filtered);
  }

  showSuccess(message) {
    this.showNotification(message, "success");
  }

  showError(message) {
    this.showNotification(message, "error");
  }

  showNotification(message, type) {
    const existing = document.querySelector(".notification");
    if (existing) existing.remove();

    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
            <i data-lucide="${
              type === "success" ? "check-circle" : "x-circle"
            }"></i>
            <span>${message}</span>
        `;

    notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${
              type === "success" ? "#22c55e" : "#ef4444"
            };
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

    setTimeout(() => {
      notification.remove();
    }, 5000);
  }
}

// Add animation styles for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { 
            transform: translateX(100%); 
            opacity: 0; 
        }
        to { 
            transform: translateX(0); 
            opacity: 1; 
        }
    }
`;
document.head.appendChild(style);

document.addEventListener("DOMContentLoaded", () => {
  window.tournamentManager = new TournamentManager();
});
