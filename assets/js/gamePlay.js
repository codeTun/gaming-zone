class GamePlay {
    constructor() {
        this.gameId = this.getGameIdFromUrl();
        this.selectedRating = 0;
        this.token = localStorage.getItem('auth_token');
        this.init();
    }

    // Extract game ID from URL parameters
    getGameIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id');
    }

    async init() {
        if (!this.gameId) {
            this.showError('No game ID provided');
            return;
        }

        if (!this.token) {
            this.showError('Please login to play games');
            return;
        }

        await this.loadGame();
        this.setupEventListeners();
    }

    async loadGame() {
        try {
            const response = await fetch(`/api/games/get-by-id.php?id=${this.gameId}`);
            const result = await response.json();

            if (result.success) {
                this.displayGame(result.data);
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to load game: ' + error.message);
        }
    }

    displayGame(game) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('game-content').style.display = 'block';

        // Populate game information
        document.getElementById('game-name').textContent = game.name;
        document.getElementById('game-description').textContent = game.description;
        document.getElementById('game-category').textContent = game.categoryName;
        document.getElementById('game-rating').textContent = `★ ${game.averageRating}/5`;
        document.getElementById('game-age').textContent = game.minAge ? `${game.minAge}+` : 'All Ages';
        
        const gameImage = document.getElementById('game-image');
        if (game.imageUrl) {
            gameImage.src = game.imageUrl;
        } else {
            gameImage.src = '../../assets/images/default-game.jpg';
        }

        // Update page title
        document.title = `${game.name} - Gaming Zone`;
    }

    setupEventListeners() {
        // Rating stars
        const stars = document.querySelectorAll('.star');
        stars.forEach(star => {
            star.addEventListener('click', (e) => {
                this.selectedRating = parseInt(e.target.dataset.rating);
                this.updateStars();
                document.getElementById('submit-rating').style.display = 'inline-block';
            });

            star.addEventListener('mouseover', (e) => {
                const rating = parseInt(e.target.dataset.rating);
                this.highlightStars(rating);
            });
        });

        document.getElementById('rating-stars').addEventListener('mouseleave', () => {
            this.updateStars();
        });
    }

    highlightStars(rating) {
        const stars = document.querySelectorAll('.star');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    updateStars() {
        this.highlightStars(this.selectedRating);
    }

    showError(message) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('error').style.display = 'block';
        document.getElementById('error').textContent = message;
    }

    async submitScore() {
        const score = document.getElementById('score-input').value;
        if (!score || score < 0) {
            alert('Please enter a valid score');
            return;
        }

        try {
            const response = await fetch('/api/games/play.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    gameId: this.gameId,
                    score: parseInt(score)
                })
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Score submitted successfully!');
                document.getElementById('score-section').style.display = 'none';
            } else {
                alert('Failed to submit score: ' + result.message);
            }
        } catch (error) {
            alert('Error submitting score: ' + error.message);
        }
    }

    async submitRating() {
        if (this.selectedRating === 0) {
            alert('Please select a rating');
            return;
        }

        try {
            const response = await fetch('/api/games/rate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    gameId: this.gameId,
                    rating: this.selectedRating
                })
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Rating submitted successfully!');
                document.getElementById('submit-rating').style.display = 'none';
            } else {
                alert('Failed to submit rating: ' + result.message);
            }
        } catch (error) {
            alert('Error submitting rating: ' + error.message);
        }
    }
}

// Game play functions
function startGame() {
    // Simulate game play
    alert('Game started! This is where your actual game would run.');
    
    // Show score input after "game completion"
    setTimeout(() => {
        document.getElementById('score-section').style.display = 'block';
    }, 2000);
}

function submitScore() {
    gamePlay.submitScore();
}

function submitRating() {
    gamePlay.submitRating();
}

// Initialize when page loads
const gamePlay = new GamePlay();
