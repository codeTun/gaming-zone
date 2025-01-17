// Sample games data
const games = [
  {
    id: 1,
    title: "The Legend of Adventure",
    category: "adventure",
    rating: 4.8,
    description: "Embark on an epic journey through mystical lands.",
    image: "https://picsum.photos/seed/game1/300/200",
  },
  {
    id: 2,
    title: "Space Warriors",
    category: "action",
    rating: 4.5,
    description: "Defend the galaxy in this action-packed space shooter.",
    image: "https://picsum.photos/seed/game2/300/200",
  },
  {
    id: 3,
    title: "Strategic Kingdoms",
    category: "strategy",
    rating: 4.2,
    description: "Build and manage your medieval kingdom.",
    image: "https://picsum.photos/seed/game3/300/200",
  },
  {
    id: 4,
    title: "Football Champions",
    category: "sports",
    rating: 4.6,
    description: "Lead your team to victory in this sports simulation.",
    image: "https://picsum.photos/seed/game4/300/200",
  },
  {
    id: 5,
    title: "Dragon Quest RPG",
    category: "rpg",
    rating: 4.9,
    description: "Experience an immersive role-playing adventure.",
    image: "https://picsum.photos/seed/game5/300/200",
  },
  {
    id: 6,
    title: "Ninja Warriors",
    category: "action",
    rating: 4.3,
    description: "Master the art of stealth and combat.",
    image: "https://picsum.photos/seed/game6/300/200",
  },
];

// DOM Elements
const gamesGrid = document.getElementById("gamesGrid");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const ratingFilter = document.getElementById("ratingFilter");

// Create star rating HTML
function getStarRating(rating) {
  const fullStars = Math.floor(rating);
  const hasHalfStar = rating % 1 >= 0.5;
  let stars = "";

  for (let i = 0; i < 5; i++) {
    if (i < fullStars) {
      stars += '<i class="fas fa-star"></i>';
    } else if (i === fullStars && hasHalfStar) {
      stars += '<i class="fas fa-star-half-alt"></i>';
    } else {
      stars += '<i class="far fa-star"></i>';
    }
  }

  return stars;
}

// Render games
function renderGames(filteredGames) {
  gamesGrid.innerHTML = "";

  filteredGames.forEach((game) => {
    const gameCard = document.createElement("div");
    gameCard.className = "game-card";

    gameCard.innerHTML = `
        <img src="${game.image}" alt="${game.title}" class="game-image">
        <div class="game-info">
          <h3 class="game-title">${game.title}</h3>
          <span class="game-category">${
            game.category.charAt(0).toUpperCase() + game.category.slice(1)
          }</span>
          <div class="game-rating">
            ${getStarRating(game.rating)}
            <span style="color: #666; margin-left: 5px;">(${game.rating})</span>
          </div>
          <p class="game-description">${game.description}</p>
        </div>
      `;

    gamesGrid.appendChild(gameCard);
  });
}

// Filter games
function filterGames() {
  const searchTerm = searchInput.value.toLowerCase();
  const selectedCategory = categoryFilter.value.toLowerCase();
  const selectedRating = parseFloat(ratingFilter.value);

  const filteredGames = games.filter((game) => {
    const matchesSearch =
      game.title.toLowerCase().includes(searchTerm) ||
      game.description.toLowerCase().includes(searchTerm);
    const matchesCategory =
      !selectedCategory || game.category === selectedCategory;
    const matchesRating = !selectedRating || game.rating >= selectedRating;

    return matchesSearch && matchesCategory && matchesRating;
  });

  renderGames(filteredGames);
}

// Event listeners
searchInput.addEventListener("input", filterGames);
categoryFilter.addEventListener("change", filterGames);
ratingFilter.addEventListener("change", filterGames);

// Initial render
renderGames(games);
