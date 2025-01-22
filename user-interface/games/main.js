document.addEventListener("DOMContentLoaded", () => {
  // Games Section Elements
  const gamesGrid = document.getElementById("gamesGrid");
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("gamesCategoryFilter");
  const ratingFilter = document.getElementById("gamesRatingFilter");

  // Sample games data
  const games = [
    {
      id: 1,
      title: "The Legend of Adventure",
      category: "adventure",
      rating: 4.8,
      description: "Embark on an epic journey through mystical lands.",
      image: "https://assets-prd.ignimgs.com/2023/03/30/legend-of-zelda-games-in-order-copy-2-1-1680206161110.jpg",
    },
    {
      id: 2,
      title: "Space Warriors",
      category: "action",
      rating: 4.5,
      description: "Defend the galaxy in this action-packed space shooter.",
      image: "https://metadata-static.plex.tv/a/gracenote/ac58b5bd27ce338d464e1c201ea1e806.jpg",
    },
    {
      id: 3,
      title: "Strategic Kingdoms",
      category: "strategy",
      rating: 4.2,
      description: "Build and manage your medieval kingdom.",
      image: "https://th.bing.com/th/id/R.a6d16adec7a211bbe311cfed78373697?rik=%2bnB4Z%2fQRyzKDKw&pid=ImgRaw&r=0",
    },
    {
      id: 4,
      title: "Football Champions",
      category: "sports",
      rating: 4.6,
      description: "Lead your team to victory in this sports simulation.",
      image: "https://kanae331.files.wordpress.com/2010/10/fcbarcelona.jpg?w=640",
    },
    {
      id: 5,
      title: "Dragon Quest RPG",
      category: "rpg",
      rating: 4.9,
      description: "Experience an immersive role-playing adventure.",
      image: "https://th.bing.com/th/id/R.9137ed956c1fe81b5b812b49aef7d780?rik=NYCtiqtw1w9Psw&pid=ImgRaw&r=0",
    },
    {
      id: 6,
      title: "Ninja Warriors",
      category: "action",
      rating: 4.3,
      description: "Master the art of stealth and combat.",
      image: "https://th.bing.com/th/id/OIP.avvRZFeQ6LeRlwwYcypdnAHaFZ?rs=1&pid=ImgDetMain",
    },
  ];

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

  // Capitalize first letter
  function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }

  // Render games
  function renderGames(filteredGames) {
    if (!gamesGrid) return; // Ensure gamesGrid exists
    gamesGrid.innerHTML = "";

    filteredGames.forEach((game) => {
      const gameCard = document.createElement("div");
      gameCard.className = "game-card";

      gameCard.innerHTML = `
        <img src="${game.image}" alt="${game.title}" class="game-image">
        <div class="game-info">
          <h3 class="game-title">${game.title}</h3>
          <span class="game-category">${capitalizeFirstLetter(
            game.category
          )}</span>
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

  // Event listeners for Games Section
  if (searchInput && categoryFilter && ratingFilter) {
    searchInput.addEventListener("input", filterGames);
    categoryFilter.addEventListener("change", filterGames);
    ratingFilter.addEventListener("change", filterGames);
  }

  // Initial render
  renderGames(games);
});
