// Initial games data
const games = [
  {
    id: 1,
    name: "Cyber Warriors",
    genre: "Action",
    rating: 4.8,
    players: 15000,
    releaseDate: "2024-01-15",
  },
  {
    id: 2,
    name: "Medieval Quest",
    genre: "RPG",
    rating: 4.5,
    players: 12000,
    releaseDate: "2024-02-01",
  },
  {
    id: 3,
    name: "Sports League 2024",
    genre: "Sports",
    rating: 4.2,
    players: 8000,
    releaseDate: "2024-03-10",
  },
  {
    id: 4,
    name: "Strategy Masters",
    genre: "Strategy",
    rating: 4.6,
    players: 10000,
    releaseDate: "2024-02-20",
  },
  {
    id: 5,
    name: "Space Explorer",
    genre: "Action",
    rating: 4.7,
    players: 13000,
    releaseDate: "2024-01-30",
  },
];

// DOM Elements
const searchInput = document.getElementById("game_search_input");
const genreFilter = document.getElementById("game_genre_filter");
const gamesTableBody = document.getElementById("game_table_body");
const totalGamesElement = document.getElementById("game_total_games");
const totalPlayersElement = document.getElementById("game_total_players");
const averageRatingElement = document.getElementById("game_average_rating");

// Update stats
function updateStats() {
  totalGamesElement.textContent = games.length;

  const totalPlayers = games.reduce((sum, game) => sum + game.players, 0);
  totalPlayersElement.textContent = totalPlayers.toLocaleString();

  const averageRating =
    games.reduce((sum, game) => sum + game.rating, 0) / games.length;
  averageRatingElement.textContent = averageRating.toFixed(1);
}

// Filter games
function filterGames() {
  const searchTerm = searchInput.value.toLowerCase();
  const selectedGenre = genreFilter.value;

  const filteredGames = games.filter((game) => {
    const matchesSearch = game.name.toLowerCase().includes(searchTerm);
    const matchesGenre =
      selectedGenre === "All" || game.genre === selectedGenre;
    return matchesSearch && matchesGenre;
  });

  renderGames(filteredGames);
}

// Delete game
function deleteGame(id) {
  games = games.filter((game) => game.id !== id);
  updateStats();
  filterGames();
}

// Render games table
function renderGames(gamesToRender) {
  gamesTableBody.innerHTML = "";

  gamesToRender.forEach((game) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${game.name}</td>
      <td><span class="game_genre_tag">${game.genre}</span></td>
      <td>
        <div class="game_rating">
          <i data-lucide="star" fill="currentColor"></i>
          ${game.rating}
        </div>
      </td>
      <td>${game.players.toLocaleString()}</td>
      <td>${game.releaseDate}</td>
      <td>
        <button class="game_delete_btn" onclick="deleteGame(${game.id})">
          <i data-lucide="trash-2"></i>
        </button>
      </td>
    `;
    gamesTableBody.appendChild(row);
  });

  // Recreate icons for new elements
  lucide.createIcons();
}

// Event listeners
searchInput.addEventListener("input", filterGames);
genreFilter.addEventListener("change", filterGames);

// Initial render
updateStats();
filterGames();
