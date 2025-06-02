class GameNavigation {
    
    // Navigate to game play page with game ID
    static playGame(gameId) {
        if (!gameId) {
            console.error('Game ID is required');
            return;
        }
        
        // Redirect to game play page with game ID as parameter
        window.location.href = `/pages/game/play.html?id=${gameId}`;
    }
    
    // Navigate to tournament page with tournament ID
    static viewTournament(tournamentId) {
        if (!tournamentId) {
            console.error('Tournament ID is required');
            return;
        }
        
        window.location.href = `/pages/tournament/view.html?id=${tournamentId}`;
    }
    
    // Navigate to event page with event ID
    static viewEvent(eventId) {
        if (!eventId) {
            console.error('Event ID is required');
            return;
        }
        
        window.location.href = `/pages/event/view.html?id=${eventId}`;
    }
    
    // Get current page parameters
    static getUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = {};
        for (const [key, value] of urlParams) {
            params[key] = value;
        }
        return params;
    }
    
    // Get specific parameter by name
    static getParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
    
    // Create game card with play button
    static createGameCard(game) {
        return `
            <div class="game-card" data-game-id="${game.id}">
                <img src="${game.imageUrl || '../../assets/images/default-game.jpg'}" alt="${game.name}" class="game-image">
                <div class="game-info">
                    <h3>${game.name}</h3>
                    <p>${game.description}</p>
                    <div class="game-meta">
                        <span class="category">${game.categoryName}</span>
                        <span class="rating">★ ${game.averageRating}/5</span>
                    </div>
                    <button class="play-btn" onclick="GameNavigation.playGame('${game.id}')">
                        Play Game
                    </button>
                </div>
            </div>
        `;
    }
}

// Add to global scope for inline onclick handlers
window.GameNavigation = GameNavigation;
