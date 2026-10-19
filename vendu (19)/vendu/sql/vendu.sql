CREATE DATABASE IF NOT EXISTS vendu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vendu;

CREATE TABLE commercants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_boutique VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    ville VARCHAR(100) DEFAULT NULL,
    plan ENUM('gratuit','pro','business') NOT NULL DEFAULT 'gratuit',
    plan_date_fin DATE DEFAULT NULL,
    reset_code VARCHAR(6) DEFAULT NULL,
    reset_code_expire DATETIME DEFAULT NULL,
    bloque TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commercant_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix DECIMAL(12,2) NOT NULL,
    categorie VARCHAR(80) DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    statut ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commercant_id) REFERENCES commercants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commercant_id INT NOT NULL,
    client_nom VARCHAR(150) NOT NULL,
    client_telephone VARCHAR(30) NOT NULL,
    client_adresse VARCHAR(255) DEFAULT NULL,
    statut ENUM('nouvelle','confirmee','livree','annulee') NOT NULL DEFAULT 'nouvelle',
    montant_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    mode_paiement ENUM('a_la_livraison','mobile_money') NOT NULL DEFAULT 'a_la_livraison',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commercant_id) REFERENCES commercants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
) ENGINE=InnoDB;

CREATE TABLE transactions_paiement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    montant DECIMAL(12,2) NOT NULL,
    statut ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE abonnement_paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commercant_id INT NOT NULL,
    plan ENUM('pro','business') NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    statut ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commercant_id) REFERENCES commercants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE commercant_employes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commercant_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commercant_id) REFERENCES commercants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE signalements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    motif VARCHAR(100) NOT NULL,
    commentaire TEXT,
    traite TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_produits_commercant ON produits(commercant_id);
CREATE INDEX idx_commandes_commercant ON commandes(commercant_id);
CREATE INDEX idx_commande_lignes_commande ON commande_lignes(commande_id);
