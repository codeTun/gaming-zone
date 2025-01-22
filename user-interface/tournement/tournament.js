const tournaments = [
  {
    id: 1,
    title: "Valorant Champions Tour",
    category: "fps",
    image:
      "https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&q=80&w=2070&h=800",
    prizePool: "$100,000",
    date: "2024-03-15",
    time: "18:00 GMT",
    maxTeams: 32,
    currentTeams: 28,
    format: "5v5 Double Elimination",
  },
  {
    id: 2,
    title: "League of Legends World Cup",
    category: "moba",
    image:
      "https://th.bing.com/th/id/OIP.nJJMatu4q9QkeEnGCfyLBQHaEK?rs=1&pid=ImgDetMain",
    prizePool: "$200,000",
    date: "2024-04-01",
    time: "16:00 GMT",
    maxTeams: 16,
    currentTeams: 12,
    format: "5v5 Single Elimination",
  },
  {
    id: 3,
    title: "Fortnite Championship",
    category: "battle-royale",
    image:
      "https://images.unsplash.com/photo-1538481199705-c710c4e965fc?auto=format&fit=crop&q=80&w=2070&h=800",
    prizePool: "$150,000",
    date: "2024-03-20",
    time: "20:00 GMT",
    maxTeams: 50,
    currentTeams: 42,
    format: "Solo & Duo Matches",
  },
  {
    id: 4,
    title: "Gran Turismo World Series",
    category: "racing",
    image:
      "https://images.unsplash.com/photo-1547394765-185e1e68f34e?auto=format&fit=crop&q=80&w=2070&h=800",
    prizePool: "$75,000",
    date: "2024-04-10",
    time: "19:00 GMT",
    maxTeams: 24,
    currentTeams: 20,
    format: "Time Trial & Race",
  },
  {
    id: 5,
    title: "Street Fighter VI Pro Tour",
    category: "fighting",
    image:
      "https://images.unsplash.com/photo-1551103782-8ab07afd45c1?auto=format&fit=crop&q=80&w=2070&h=800",
    prizePool: "$50,000",
    date: "2024-03-25",
    time: "17:00 GMT",
    maxTeams: 64,
    currentTeams: 45,
    format: "1v1 Double Elimination",
  },
];

// Initialize the page
document.addEventListener("DOMContentLoaded", () => {
  renderTournaments(tournaments);
  setupEventListeners();
  lucide.createIcons();
});

// Render tournaments
function renderTournaments(tournamentsToRender) {
  const grid = document.getElementById("tournamentsGrid");
  grid.innerHTML = "";

  tournamentsToRender.forEach((tournament) => {
    const card = createTournamentCard(tournament);
    grid.appendChild(card);
  });
}

// Create tournament card
function createTournamentCard(tournament) {
  const card = document.createElement("div");
  card.className = "tournament-item";
  card.innerHTML = `
        <img src="${tournament.image}" alt="${
    tournament.title
  }" class="tournament-item-image">
        <div class="tournament-item-content">
            <span class="tournament-item-category">${formatCategory(
              tournament.category
            )}</span>
            <h3 class="tournament-item-title">${tournament.title}</h3>
            <div class="tournament-item-details">
                <div class="tournament-item-detail">
                    <i data-lucide="calendar"></i>
                    <span>${formatDate(tournament.date)}</span>
                </div>
                <div class="tournament-item-detail">
                    <i data-lucide="clock"></i>
                    <span>${tournament.time}</span>
                </div>
                <div class="tournament-item-detail">
                    <i data-lucide="users"></i>
                    <span>${tournament.currentTeams}/${
    tournament.maxTeams
  } Teams</span>
                </div>
                <div class="tournament-item-detail">
                    <i data-lucide="swords"></i>
                    <span>${tournament.format}</span>
                </div>
            </div>
            <div class="tournament-prize">Prize Pool: ${
              tournament.prizePool
            }</div>
            <button class="btn btn-primary" onclick="openRegistrationModal(${
              tournament.id
            })">
                Register Now
            </button>
        </div>
    `;
  return card;
}

// Format category name
function formatCategory(category) {
  return category
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

// Format date
function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

// Setup event listeners
function setupEventListeners() {
  const searchInput = document.getElementById("tournamentSearchInput");
  const categoryFilter = document.getElementById("tournamentCategoryFilter");

  if (searchInput && categoryFilter) {
    searchInput.addEventListener("input", filterTournaments);
    categoryFilter.addEventListener("change", filterTournaments);
  }
}

// Filter tournaments
function filterTournaments() {
  const searchTerm = document
    .getElementById("tournamentSearchInput")
    .value.toLowerCase();
  const categoryFilter = document.getElementById(
    "tournamentCategoryFilter"
  ).value;

  const filteredTournaments = tournaments.filter((tournament) => {
    const matchesSearch = tournament.title.toLowerCase().includes(searchTerm);
    const matchesCategory =
      !categoryFilter || tournament.category === categoryFilter;
    return matchesSearch && matchesCategory;
  });

  renderTournaments(filteredTournaments);
  lucide.createIcons(); // Reinitialize icons for new content
}

// Modal functions
function openRegistrationModal(tournamentId) {
  const tournament = tournaments.find((t) => t.id === tournamentId);
  if (!tournament) return;

  const modal = document.getElementById("registrationModal");
  const tournamentTitle = document.getElementById("tournamentTitle");

  tournamentTitle.textContent = tournament.title;
  modal.classList.add("active");
}

function closeModal() {
  const modal = document.getElementById("registrationModal");
  modal.classList.remove("active");
}

// Handle registration form submission
function handleRegistration(event) {
  event.preventDefault();

  // Get form data
  const formData = {
    teamName: document.getElementById("teamName").value,
    playerCount: document.getElementById("playerCount").value,
    contactEmail: document.getElementById("contactEmail").value,
    discordId: document.getElementById("discordId").value,
    experience: document.getElementById("experience").value,
  };

  // Here you would typically send this data to your backend
  console.log("Registration submitted:", formData);

  // Show success message
  alert(
    "Registration successful! You will receive a confirmation email shortly."
  );

  // Close the modal
  closeModal();

  // Reset the form
  event.target.reset();
}
