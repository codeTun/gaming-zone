-- Create Users table
CREATE TABLE Users (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('USER', 'ADMIN') NOT NULL DEFAULT 'USER',
    birthDate DATE NULL,
    gender ENUM('MALE', 'FEMALE') NULL,
    imageUrl VARCHAR(500) NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Token table
CREATE TABLE Token (
    id VARCHAR(36) PRIMARY KEY,
    token VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL DEFAULT 'AUTH',
    expiresAt TIMESTAMP NULL,
    userId VARCHAR(36) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE
);

-- Create Category table
CREATE TABLE Category (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create ContentItem table (base table for inheritance)
CREATE TABLE ContentItem (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    imageUrl VARCHAR(500) NULL,
    type ENUM('GAME', 'EVENT', 'TOURNAMENT') NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Game table
CREATE TABLE Game (
    id VARCHAR(36) PRIMARY KEY,
    categoryId VARCHAR(36) NOT NULL,
    minAge INT NULL,
    targetGender ENUM('MALE', 'FEMALE') NULL,
    averageRating DECIMAL(3,2) DEFAULT 0.00,
    FOREIGN KEY (id) REFERENCES ContentItem(id) ON DELETE CASCADE,
    FOREIGN KEY (categoryId) REFERENCES Category(id)
);

-- Create GameRating table
CREATE TABLE GameRating (
    id VARCHAR(36) PRIMARY KEY,
    userId VARCHAR(36) NOT NULL,
    gameId VARCHAR(36) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    ratedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_game_rating (userId, gameId),
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (gameId) REFERENCES Game(id) ON DELETE CASCADE
);

-- Create Event table
CREATE TABLE Event (
    id VARCHAR(36) PRIMARY KEY,
    place VARCHAR(200) NOT NULL,
    startDate DATETIME NOT NULL,
    FOREIGN KEY (id) REFERENCES ContentItem(id) ON DELETE CASCADE
);

-- Create Tournament table
CREATE TABLE Tournament (
    id VARCHAR(36) PRIMARY KEY,
    startDate DATETIME NOT NULL,
    endDate DATETIME NOT NULL,
    prizePool DECIMAL(10,2) NULL,
    maxParticipants INT NOT NULL DEFAULT 50,
    FOREIGN KEY (id) REFERENCES ContentItem(id) ON DELETE CASCADE
);

-- Create UserGame table
CREATE TABLE UserGame (
    id VARCHAR(36) PRIMARY KEY,
    userId VARCHAR(36) NOT NULL,
    gameId VARCHAR(36) NOT NULL,
    score INT NOT NULL DEFAULT 0,
    playedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (gameId) REFERENCES Game(id) ON DELETE CASCADE
);

-- Create TournamentRegistration table
CREATE TABLE TournamentRegistration (
    id VARCHAR(36) PRIMARY KEY,
    userId VARCHAR(36) NOT NULL,
    tournamentId VARCHAR(36) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    teamName VARCHAR(100) NOT NULL,
    registeredAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING', 'CONFIRMED', 'CANCELLED') DEFAULT 'PENDING',
    UNIQUE KEY unique_user_tournament (userId, tournamentId),
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (tournamentId) REFERENCES Tournament(id) ON DELETE CASCADE
);

-- Create EventRegistration table
CREATE TABLE EventRegistration (
    id VARCHAR(36) PRIMARY KEY,
    userId VARCHAR(36) NOT NULL,
    eventId VARCHAR(36) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    registeredAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING', 'CONFIRMED', 'CANCELLED') DEFAULT 'PENDING',
    UNIQUE KEY unique_user_event (userId, eventId),
    FOREIGN KEY (userId) REFERENCES Users(id) ON DELETE CASCADE,
    FOREIGN KEY (eventId) REFERENCES Event(id) ON DELETE CASCADE
);

-- Insert sample categories with fixed IDs
INSERT INTO Category (id, name) VALUES 
('cat-001', 'Action'),
('cat-002', 'Adventure'),
('cat-003', 'Puzzle'),
('cat-004', 'Strategy'),
('cat-005', 'Sports'),
('cat-006', 'Racing');

-- Insert sample content and games with explicit UUIDs
INSERT INTO ContentItem (id, name, description, imageUrl, type) VALUES 
('game-001', 'Space Shooter', 'Classic arcade space shooting game', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400', 'GAME'),
('game-002', 'Puzzle Master', 'Mind-bending puzzle challenges', 'https://images.unsplash.com/photo-1606092195730-5d7b9af1efc5?w=400', 'GAME'),
('tournament-001', 'Spring Gaming Championship', 'Annual gaming tournament with cash prizes', 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400', 'TOURNAMENT'),
('event-001', 'Gaming Convention 2024', 'Meet fellow gamers and try new games', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400', 'EVENT'),
('event-002', 'Retro Gaming Night', 'Nostalgic evening with classic games', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=400', 'EVENT');

INSERT INTO Game (id, categoryId, minAge, targetGender, averageRating) VALUES 
('game-001', 'cat-001', 13, NULL, 4.5),
('game-002', 'cat-003', 8, NULL, 4.2);

INSERT INTO Tournament (id, startDate, endDate, prizePool, maxParticipants) VALUES 
('tournament-001', '2024-06-01 09:00:00', '2024-06-03 18:00:00', 5000.00, 100);

INSERT INTO Event (id, place, startDate) VALUES 
('event-001', 'Convention Center Downtown', '2024-05-15 10:00:00'),
('event-002', 'Gaming Cafe Central', '2024-06-20 19:00:00');
