CREATE DATABASE IF NOT EXISTS immo_guinee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE immo_guinee;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  telephone VARCHAR(30) NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role ENUM('admin','prestataire','client') NOT NULL DEFAULT 'client',
  statut ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE annonces (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prestataire_id INT NOT NULL,
  titre VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  categorie ENUM('maison','villa','appartement','appartement_meuble','parcelle','commerce') NOT NULL,
  transaction ENUM('vente','location') NOT NULL,
  prix BIGINT UNSIGNED NOT NULL,
  periode ENUM('unique','nuit','mois','an') NOT NULL DEFAULT 'unique',
  ville VARCHAR(60) NOT NULL,
  quartier VARCHAR(80) NOT NULL,
  surface INT UNSIGNED NULL,
  chambres TINYINT UNSIGNED NULL,
  salles_bain TINYINT UNSIGNED NULL,
  statut ENUM('en_attente','publiee','refusee','archivee') NOT NULL DEFAULT 'en_attente',
  motif_refus VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (prestataire_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_recherche (statut, transaction, categorie, ville)
) ENGINE=InnoDB;

CREATE TABLE annonce_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT NOT NULL,
  chemin VARCHAR(200) NOT NULL,
  principale TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Une "demande" couvre : réservation (dates), offre d'achat, demande de visite
CREATE TABLE demandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT NOT NULL,
  client_id INT NOT NULL,
  type ENUM('reservation','achat','visite') NOT NULL,
  date_debut DATE NULL,
  date_fin DATE NULL,
  message VARCHAR(500) NULL,
  statut ENUM('en_attente','confirmee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
