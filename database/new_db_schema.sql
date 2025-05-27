-- Drop tables if they exist (in reverse order of creation to respect foreign key constraints)
DROP TABLE IF EXISTS UserGame;
DROP TABLE IF EXISTS GameRating;
DROP TABLE IF EXISTS Tournament;
DROP TABLE IF EXISTS Event;
DROP TABLE IF EXISTS Game;
DROP TABLE IF EXISTS ContentItem;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS Token;
DROP TABLE IF EXISTS User;

-- Create User table
CREATE TABLE User (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
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
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    token VARCHAR(255) NOT NULL UNIQUE,
    type VARCHAR(50) NOT NULL DEFAULT 'AUTH',
    expiresAt TIMESTAMP NULL,
    userId VARCHAR(36) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES User(id) ON DELETE CASCADE
);

-- Create Category table
CREATE TABLE Category (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(100) NOT NULL UNIQUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create ContentItem table (base table for inheritance)
CREATE TABLE ContentItem (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
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
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    userId VARCHAR(36) NOT NULL,
    gameId VARCHAR(36) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    ratedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_game_rating (userId, gameId),
    FOREIGN KEY (userId) REFERENCES User(id) ON DELETE CASCADE,
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
    FOREIGN KEY (id) REFERENCES ContentItem(id) ON DELETE CASCADE
);

-- Create UserGame table
CREATE TABLE UserGame (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    userId VARCHAR(36) NOT NULL,
    gameId VARCHAR(36) NOT NULL,
    score INT NOT NULL DEFAULT 0,
    playedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES User(id) ON DELETE CASCADE,
    FOREIGN KEY (gameId) REFERENCES Game(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO Category (name) VALUES 
('Action'),
('Adventure'),
('Puzzle'),
('Strategy'),
('Sports'),
('Racing');
