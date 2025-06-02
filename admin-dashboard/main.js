// Navigation
const navItems = document.querySelectorAll(".nav-item");
const contentSections = document.querySelectorAll(
  ".content-section, .dashboard-content"
);

navItems.forEach((item) => {
  item.addEventListener("click", (e) => {
    e.preventDefault();
    navItems.forEach((nav) => nav.classList.remove("active"));
    contentSections.forEach((section) => section.classList.remove("active"));
    item.classList.add("active");
    const sectionId = item.getAttribute("data-section");
    document.getElementById(sectionId).classList.add("active");
  });
});

// Charts
const userGrowthCtx = document
  .getElementById("userGrowthChart")
  .getContext("2d");
const gameDistributionCtx = document
  .getElementById("gameDistributionChart")
  .getContext("2d");

// User Growth Chart
new Chart(userGrowthCtx, {
  type: "line",
  data: {
    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
    datasets: [
      {
        label: "New Users",
        data: [650, 850, 1100, 900, 1200, 1500],
        borderColor: "#6c5ce7",
        tension: 0.4,
        fill: true,
        backgroundColor: "rgba(108, 92, 231, 0.1)",
      },
    ],
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: "top", labels: { color: "#a4a6b3" } },
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: "rgba(255, 255, 255, 0.1)" },
        ticks: { color: "#a4a6b3" },
      },
      x: {
        grid: { color: "rgba(255, 255, 255, 0.1)" },
        ticks: { color: "#a4a6b3" },
      },
    },
  },
});

// Game Distribution Chart
new Chart(gameDistributionCtx, {
  type: "doughnut",
  data: {
    labels: ["Action", "RPG", "Strategy", "Sports", "Other"],
    datasets: [
      {
        data: [30, 25, 20, 15, 10],
        backgroundColor: [
          "#6c5ce7",
          "#00cec9",
          "#fd79a8",
          "#fdcb6e",
          "#00b894",
        ],
      },
    ],
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: "right", labels: { color: "#a4a6b3" } },
    },
  },
});

// ------------------- Event Management -------------------
(function () {
  let events = [];
  const eventGrid = document.getElementById("event_grid");
  const searchInput = document.getElementById("event_search_input");
  const addButton = document.getElementById("event_add_btn");
  const modal = document.getElementById("event_modal");
  const modalTitle = document.getElementById("event_modal_title");
  const closeModal = document.getElementById("event_close_modal");
  const cancelButton = document.getElementById("event_cancel_btn");
  const eventForm = document.getElementById("event_form");

  let editingEventId = null;

  // Fetch and initialize events
  fetch("./events-management/data.json")
    .then((response) => response.json())
    .then((data) => {
      events = data;
      filterEvents();
    })
    .catch((error) => console.error("Error loading events:", error));

  // Filter events
  function filterEvents(searchTerm = "") {
    const filteredEvents = events.filter(
      (event) =>
        event.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        event.description.toLowerCase().includes(searchTerm.toLowerCase())
    );
    renderEvents(filteredEvents);
  }

  // Render events
  function renderEvents(eventsToRender) {
    eventGrid.innerHTML = "";
    eventsToRender.forEach((event) => {
      const card = document.createElement("div");
      card.className = "event_card";
      card.innerHTML = `
        <img src="${event.image}" alt="${event.title}" class="event_card_image">
        <div class="event_card_content">
          <h3 class="event_card_title">${event.title}</h3>
          <p class="event_card_description">${event.description}</p>
          <div class="event_card_date">
            <i data-lucide="calendar" class="event_btn_icon"></i>
            ${formatDate(event.date)}
          </div>
          <div class="event_card_actions">
            <button onclick="editEvent(${event.id})" class="event_edit_btn">
              <i data-lucide="edit" class="event_btn_icon"></i>
            </button>
            <button onclick="deleteEvent(${event.id})" class="event_delete_btn">
              <i data-lucide="trash-2" class="event_btn_icon"></i>
            </button>
          </div>
        </div>
      `;
      eventGrid.appendChild(card);
    });
    lucide.createIcons();
  }

  // Format date
  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  // Show modal
  function showModal() {
    modal.classList.add("active");
  }

  // Hide modal
  function hideModal() {
    modal.classList.remove("active");
    eventForm.reset();
    editingEventId = null;
  }

  // Add event
  function addEvent(e) {
    e.preventDefault();
    const newEvent = {
      id: editingEventId || Date.now(),
      title: document.getElementById("event_title").value,
      description: document.getElementById("event_description").value,
      image: document.getElementById("event_image").value,
      date: document.getElementById("event_date").value,
    };
    if (editingEventId) {
      events = events.map((ev) => (ev.id === editingEventId ? newEvent : ev));
    } else {
      events.push(newEvent);
    }
    hideModal();
    filterEvents(searchInput.value);
  }

  window.editEvent = function (id) {
    const event = events.find((e) => e.id === id);
    if (!event) return;
    editingEventId = id;
    modalTitle.textContent = "Edit Event";
    document.getElementById("event_title").value = event.title;
    document.getElementById("event_description").value = event.description;
    document.getElementById("event_image").value = event.image;
    document.getElementById("event_date").value = event.date;
    showModal();
  };

  window.deleteEvent = function (id) {
    if (confirm("Are you sure you want to delete this event?")) {
      events = events.filter((event) => event.id !== id);
      filterEvents(searchInput.value);
    }
  };

  // Event listeners
  searchInput.addEventListener("input", (e) => filterEvents(e.target.value));
  addButton.addEventListener("click", () => {
    modalTitle.textContent = "Add New Event";
    showModal();
  });
  closeModal.addEventListener("click", hideModal);
  cancelButton.addEventListener("click", hideModal);
  eventForm.addEventListener("submit", addEvent);
})();

// ------------------- Tournament Management -------------------
(function () {
  let tournaments = [];
  const tournamentGrid = document.getElementById("tournament_grid"); // or rename in HTML to "tournament_grid"
  const tournamentSearchInput = document.getElementById("tournament_search_input"); // or rename in HTML
  const addTournamentButton = document.getElementById("tournament_add_btn");
  const tournamentModal = document.getElementById("tournament_modal"); // or rename in HTML
  const tournamentModalTitle = document.getElementById("tournament_modal_title");
  const tournamentCloseModal = document.getElementById("tournament_close_modal");
  const tournamentCancelButton = document.getElementById("tournament_cancel_btn");
  const tournamentForm = document.getElementById("tournament_form");

  let editingTournamentId = null;

  // Fetch and initialize tournaments
  fetch("/admin-dashboard/tournaments-management/data.json") // or your actual path
    .then((response) => response.json())
    .then((data) => {
      tournaments = data;
      filterTournaments();
    })
    .catch((error) => console.error("Error loading tournaments:", error));

  // Filter tournaments
  function filterTournaments(searchTerm = "") {
    const filtered = tournaments.filter(
      (t) =>
        t.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        t.description.toLowerCase().includes(searchTerm.toLowerCase())
    );
    renderTournaments(filtered);
  }

  // Render tournaments
  function renderTournaments(list) {
    tournamentGrid.innerHTML = "";
    list.forEach((tournament) => {
      const card = document.createElement("div");
      card.className = "tournament_card";
      card.innerHTML = `
        <img src="${tournament.image}" alt="${
        tournament.title
      }" class="event_card_image">
        <div class="event_card_content">
          <h3 class="event_card_title">${tournament.title}</h3>
          <p class="event_card_description">${tournament.description}</p>
          <div class="event_card_date" style="color: cornflowerblue;">
            <i data-lucide="calendar" class="event_btn_icon"></i>
            ${formatDate(tournament.date)}
          </div>
          <div class="tournament_card_actions">
            <button onclick="editTournament(${
              tournament.id
            })" class="event_edit_btn" style="color: cornflowerblue;">
              <i data-lucide="edit" class="event_btn_icon"></i>
            </button>
            <button onclick="deleteTournament(${
              tournament.id
            })" class="event_delete_btn">
              <i data-lucide="trash-2" class="event_btn_icon"></i>
            </button>
          </div>
        </div>
      `;
      tournamentGrid.appendChild(card);
    });
    lucide.createIcons();
  }

  // Format date
  function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  // Show modal
  function showModal() {
    tournamentModal.classList.add("active");
  }

  // Hide modal
  function hideModal() {
    tournamentModal.classList.remove("active");
    tournamentForm.reset();
    editingTournamentId = null;
  }

  // Add tournament
  function addTournament(e) {
    e.preventDefault();
    const newTournament = {
      id: editingTournamentId || Date.now(),
      title: document.getElementById("tournament_title").value,
      description: document.getElementById("tournament_description").value,
      team: document.getElementById("tournament_team_name").value,
      category: document.getElementById("tournament_category").value,
      image: document.getElementById("tournament_image").value,
      date: document.getElementById("tournament_date").value,
    };
    if (editingTournamentId) {
      tournaments = tournaments.map((t) =>
        t.id === editingTournamentId ? newTournament : t
      );
    } else {
      tournaments.push(newTournament);
    }
    hideModal();
    filterTournaments(tournamentSearchInput.value);
  }

  window.editTournament = function (id) {
    const tour = tournaments.find((t) => t.id === id);
    if (!tour) return;
    editingTournamentId = id;
    tournamentModalTitle.textContent = "Edit Tournament";
    document.getElementById("tournament_title").value = tour.title;
    document.getElementById("tournament_description").value = tour.description;
    document.getElementById("tournament_image").value = tour.image;
    document.getElementById("tournament_date").value = tour.date;
    showModal();
  };

  window.deleteTournament = function (id) {
    if (confirm("Are you sure you want to delete this tournament?")) {
      tournaments = tournaments.filter((t) => t.id !== id);
      filterTournaments(tournamentSearchInput.value);
    }
  };

  // Event listeners
  tournamentSearchInput.addEventListener("input", (e) =>
    filterTournaments(e.target.value)
  );
  addTournamentButton.addEventListener("click", () => {
    tournamentModalTitle.textContent = "Add New Tournament";
    showModal();
  });
  tournamentCloseModal.addEventListener("click", hideModal);
  tournamentCancelButton.addEventListener("click", hideModal);
  tournamentForm.addEventListener("submit", addTournament);
})();

// ------------------- Dashboard Management -------------------
class DashboardManager {
  constructor() {
    this.apiBase = 'http://localhost/gaming-zone/api';
  }

  async init() {
    console.log('📊 Initializing Dashboard with real data...');
    await this.loadDashboardStats();
  }

  async loadDashboardStats() {
    try {
      // Show loading state on dashboard cards
      this.showLoadingState();
      
      // Load all stats in parallel
      const [usersData, gamesData, eventsData, tournamentsData] = await Promise.all([
        this.fetchUsers(),
        this.fetchGames(),
        this.fetchEvents(),
        this.fetchTournaments()
      ]);

      console.log('📊 Dashboard data loaded:', {
        users: usersData.length,
        games: gamesData.length,
        events: eventsData.length,
        tournaments: tournamentsData.length
      });

      this.updateDashboardStats(usersData, gamesData, eventsData, tournamentsData);
    } catch (error) {
      console.error('❌ Failed to load dashboard stats:', error);
      this.showErrorState();
    }
  }

  showLoadingState() {
    const elements = [
      'dashboard-total-users',
      'dashboard-active-games', 
      'dashboard-upcoming-events',
      'dashboard-active-tournaments'
    ];
    
    elements.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = '...';
      }
    });
  }

  showErrorState() {
    const elements = [
      'dashboard-total-users',
      'dashboard-active-games', 
      'dashboard-upcoming-events',
      'dashboard-active-tournaments'
    ];
    
    elements.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = 'Error';
      }
    });
  }

  async fetchUsers() {
    try {
      console.log('📡 Fetching users for dashboard...');
      const response = await fetch(`${this.apiBase}/users.php`);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      console.log('👥 Users data:', data);
      return Array.isArray(data) ? data : [];
    } catch (error) {
      console.error('❌ Failed to fetch users:', error);
      return [];
    }
  }

  async fetchGames() {
    try {
      console.log('📡 Fetching games for dashboard...');
      const response = await fetch(`${this.apiBase}/games.php`);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const data = await response.json();
      console.log('🎮 Games data:', data);
      return Array.isArray(data) ? data : [];
    } catch (error) {
      console.error('❌ Failed to fetch games:', error);
      return [];
    }
  }

  async fetchEvents() {
    try {
      console.log('📡 Fetching events for dashboard...');
      const response = await fetch(`${this.apiBase}/events.php`);
      console.log('📥 Events API response status:', response.status, response.statusText);
      
      if (!response.ok) {
        const errorText = await response.text();
        console.error('❌ Events API error response:', errorText);
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }
      
      const data = await response.json();
      console.log('📅 Events data received:', data);
      console.log('📅 Events data type:', typeof data, 'Is array:', Array.isArray(data));
      
      if (Array.isArray(data)) {
        console.log('✅ Events data is valid array with length:', data.length);
        return data;
      } else if (data && data.error) {
        console.error('❌ Events API returned error:', data.error);
        return [];
      } else {
        console.warn('⚠️ Events data is not an array:', data);
        return [];
      }
    } catch (error) {
      console.error('❌ Failed to fetch events:', error);
      return [];
    }
  }

  async fetchTournaments() {
    try {
      console.log('📡 Fetching tournaments for dashboard...');
      const response = await fetch(`${this.apiBase}/tournaments.php`);
      console.log('📥 Tournaments API response status:', response.status, response.statusText);
      
      if (!response.ok) {
        const errorText = await response.text();
        console.error('❌ Tournaments API error response:', errorText);
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }
      
      const data = await response.json();
      console.log('🏆 Tournaments data received:', data);
      console.log('🏆 Tournaments data type:', typeof data, 'Is array:', Array.isArray(data));
      
      if (Array.isArray(data)) {
        console.log('✅ Tournaments data is valid array with length:', data.length);
        return data;
      } else if (data && data.error) {
        console.error('❌ Tournaments API returned error:', data.error);
        return [];
      } else {
        console.warn('⚠️ Tournaments data is not an array:', data);
        return [];
      }
    } catch (error) {
      console.error('❌ Failed to fetch tournaments:', error);
      return [];
    }
  }

  updateDashboardStats(users, games, events, tournaments) {
    // Update Total Users
    const totalUsersElement = document.getElementById('dashboard-total-users');
    if (totalUsersElement) {
      totalUsersElement.textContent = users.length;
      console.log('✅ Updated dashboard total users:', users.length);
    }

    // Update Active Games (total games count)
    const activeGamesElement = document.getElementById('dashboard-active-games');
    if (activeGamesElement) {
      activeGamesElement.textContent = games.length;
      console.log('✅ Updated dashboard active games:', games.length);
    }

    // Update Upcoming Events - show all events for now, filter later if needed
    console.log('📅 Processing events data:', events);
    events.forEach(event => {
      console.log('Event:', event.title, 'Date:', event.eventDate, 'Parsed:', new Date(event.eventDate));
    });
    
    // For now, show total events count instead of filtering by date
    // This ensures we see all events in the dashboard
    const totalEvents = events.length;
    
    const upcomingEventsElement = document.getElementById('dashboard-upcoming-events');
    if (upcomingEventsElement) {
      upcomingEventsElement.textContent = totalEvents;
      console.log('✅ Updated dashboard total events:', totalEvents);
    }

    // Update Active Tournaments - show all tournaments for now
    console.log('🏆 Processing tournaments data:', tournaments);
    tournaments.forEach(tournament => {
      console.log('Tournament:', tournament.title, 'End Date:', tournament.endDate, 'Parsed:', new Date(tournament.endDate));
    });
    
    // For now, show total tournaments count
    const totalTournaments = tournaments.length;
    
    const activeTournamentsElement = document.getElementById('dashboard-active-tournaments');
    if (activeTournamentsElement) {
      activeTournamentsElement.textContent = totalTournaments;
      console.log('✅ Updated dashboard total tournaments:', totalTournaments);
    }

    console.log('📊 Dashboard stats updated successfully:', {
      totalUsers: users.length,
      activeGames: games.length,
      totalEvents: totalEvents,
      totalTournaments: totalTournaments
    });
  }
}

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM loaded - main.js");
  
  // Initialize navigation
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', async (e) => {
      e.preventDefault();
      
      const sectionName = item.getAttribute('data-section');
      console.log(`Navigation: switching to section "${sectionName}"`);
      
      // Hide all sections
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      
      // Show selected section
      const targetSection = document.getElementById(sectionName);
      if (targetSection) {
        targetSection.classList.add('active');
      }
      
      // Update nav items
      document.querySelectorAll('.nav-item').forEach(navItem => {
        navItem.classList.remove('active');
      });
      item.classList.add('active');
      
      // Initialize the appropriate manager based on section
      try {
        if (sectionName === 'events') {
          console.log('Events section activated, initializing EventManager');
          if (window.eventManager) {
            await window.eventManager.init();
          } else {
            console.error('EventManager not found on window object');
          }
        } else if (sectionName === 'games') {
          console.log('Games section activated, initializing GameManager');
          if (window.gameManager) {
            await window.gameManager.init();
          } else {
            console.error('GameManager not found on window object');
          }
        } else if (sectionName === 'tournaments') {
          console.log('Tournaments section activated, initializing TournamentManager');
          if (window.tournamentManager) {
            await window.tournamentManager.init();
            await window.tournamentManager.loadTournaments();
          } else {
            console.error('TournamentManager not found on window object');
          }
        } else if (sectionName === 'users') {
          console.log('Users section activated, initializing UserManager');
          if (window.userManager) {
            await window.userManager.init();
          } else {
            console.error('UserManager not found on window object');
          }
        }
      } catch (error) {
        console.error(`Failed to initialize ${sectionName} section:`, error);
      }
    });
  });

  // Check if we should initialize any manager based on current active section
  const activeSection = document.querySelector('.content-section.active');
  if (activeSection) {
    const sectionId = activeSection.id;
    console.log(`Initial active section: ${sectionId}`);
    
    if (sectionId === 'events' && window.eventManager) {
      console.log('Initializing EventManager for active events section');
      window.eventManager.init();
    } else if (sectionId === 'games' && window.gameManager) {
      console.log('Initializing GameManager for active games section');
      window.gameManager.init();
    } else if (sectionId === 'users' && window.userManager) {
      console.log('Initializing UserManager for active users section');
      window.userManager.init();
    }
  }
  
  // Global error handler
  window.addEventListener('error', (event) => {
    console.error('Global error caught:', event.message);
    alert('An unexpected error occurred. Please try again later.');
  });
  
  // Prevent form submission on Enter key press
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        console.log('Enter key pressed - form submission prevented');
      }
    });
  });
  
  window.dashboardManager = new DashboardManager();
});

// Navigation handling
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Main.js DOM loaded - Setting up navigation and dashboard');
    
    // Initialize dashboard
    const dashboardManager = new DashboardManager();
    window.dashboardManager = dashboardManager;
    
    // Load dashboard stats immediately
    setTimeout(() => {
      dashboardManager.init();
    }, 500);
    
    const navItems = document.querySelectorAll('.nav-item');
    const contentSections = document.querySelectorAll('.content-section');

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            console.log('🔄 Navigation clicked:', this.getAttribute('data-section'));
            
            // Remove active class from all nav items and sections
            navItems.forEach(nav => nav.classList.remove('active'));
            contentSections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked nav item
            this.classList.add('active');
            
            // Show corresponding section
            const targetSection = this.getAttribute('data-section');
            const section = document.getElementById(targetSection);
            if (section) {
                section.classList.add('active');
                console.log('✅ Activated section:', targetSection);
                
                // Initialize managers when their sections are viewed
                setTimeout(() => {
                    if (targetSection === 'dashboard') {
                        console.log('📊 Dashboard section - refreshing stats...');
                        dashboardManager.init();
                    } else if (targetSection === 'users') {
                        console.log('👥 Users section - checking userManager...');
                        if (typeof window.UserManager !== 'undefined') {
                            if (!window.userManager) {
                                window.userManager = new window.UserManager();
                            }
                            window.userManager.init();
                        }
                    } else if (targetSection === 'games' && window.gameManager) {
                        console.log('🔄 Initializing Game Management...');
                        window.gameManager.init();
                      } else if (targetSection === 'events' && window.eventManager) {
                        console.log('🔄 Initializing Event Management...');
                        window.eventManager.init();
                      } else if (targetSection === 'tournaments' && window.tournamentManager) {
                        console.log('🔄 Initializing Tournament Management...');
                        window.tournamentManager.init();
                      }
                }, 200);
            }
        });
    });
});
