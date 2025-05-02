-- Drop tables if they exist (in reverse order of creation to respect foreign key constraints)
DROP TABLE IF EXISTS Aime;
DROP TABLE IF EXISTS UtilisateurTournoi;
DROP TABLE IF EXISTS Tournoi;
DROP TABLE IF EXISTS Jeu;
DROP TABLE IF EXISTS Categorie;
DROP TABLE IF EXISTS Jeton;
DROP TABLE IF EXISTS Utilisateur;
DROP TABLE IF EXISTS Role;

-- Create Role table
CREATE TABLE Role (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom ENUM('ADMIN', 'USER') NOT NULL
);

-- Create Utilisateur table
CREATE TABLE Utilisateur (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nomUtilisateur VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    motDePasse VARCHAR(255) NOT NULL,
    roleId INT NOT NULL,
    dateCreation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (roleId) REFERENCES Role(id)
);

-- Create Jeton table
CREATE TABLE Jeton (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    valeur VARCHAR(255) NOT NULL UNIQUE,
    idUtilisateur BIGINT NOT NULL,
    dateCreation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dateExpiration TIMESTAMP NULL,
    FOREIGN KEY (idUtilisateur) REFERENCES Utilisateur(id) ON DELETE CASCADE
);

-- Create Categorie table
CREATE TABLE Categorie (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Create Jeu table
CREATE TABLE Jeu (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    idCategorie BIGINT NOT NULL,
    datePublication DATE NOT NULL,
    image VARCHAR(255) NULL,
    FOREIGN KEY (idCategorie) REFERENCES Categorie(id)
);

-- Create Tournoi table
CREATE TABLE Tournoi (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    idJeu BIGINT NOT NULL,
    dateDebut DATETIME NOT NULL,
    dateFin DATETIME NOT NULL,
    prix VARCHAR(100) NULL,
    maxParticipant INT NOT NULL,
    statut ENUM('UPCOMING', 'ONGOING', 'COMPLETED') DEFAULT 'UPCOMING',
    FOREIGN KEY (idJeu) REFERENCES Jeu(id)
);

-- Create UtilisateurTournoi table (junction table)
CREATE TABLE UtilisateurTournoi (
    idUtilisateur BIGINT NOT NULL,
    idTournoi BIGINT NOT NULL,
    dateInscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idUtilisateur, idTournoi),
    FOREIGN KEY (idUtilisateur) REFERENCES Utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (idTournoi) REFERENCES Tournoi(id) ON DELETE CASCADE
);

-- Create Aime table (for likes)
CREATE TABLE Aime (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    idUtilisateur BIGINT NOT NULL,
    idJeu BIGINT NOT NULL,
    horodatage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (idUtilisateur, idJeu),
    FOREIGN KEY (idUtilisateur) REFERENCES Utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (idJeu) REFERENCES Jeu(id) ON DELETE CASCADE
);

-- Insert default roles
INSERT INTO Role (nom) VALUES ('ADMIN'), ('USER');

-- Insert sample categories
INSERT INTO Categorie (nom, description) VALUES 
('Arcade', 'Classic arcade-style games'),
('Memory', 'Games that test your memory'),
('Strategy', 'Strategic thinking games'),
('Puzzle', 'Brain teasers and puzzles');
