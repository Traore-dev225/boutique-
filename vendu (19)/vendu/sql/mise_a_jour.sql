-- Mise à jour d'une base "vendu" déjà existante (n'efface aucune donnée).
USE vendu;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commandes' AND COLUMN_NAME = 'mode_paiement');
SET @s = IF(@c = 0, 'ALTER TABLE commandes ADD COLUMN mode_paiement ENUM(''a_la_livraison'',''mobile_money'') NOT NULL DEFAULT ''a_la_livraison''', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commercants' AND COLUMN_NAME = 'plan_date_fin');
SET @s = IF(@c = 0, 'ALTER TABLE commercants ADD COLUMN plan_date_fin DATE DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commercants' AND COLUMN_NAME = 'reset_code');
SET @s = IF(@c = 0, 'ALTER TABLE commercants ADD COLUMN reset_code VARCHAR(6) DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commercants' AND COLUMN_NAME = 'reset_code_expire');
SET @s = IF(@c = 0, 'ALTER TABLE commercants ADD COLUMN reset_code_expire DATETIME DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'commercants' AND COLUMN_NAME = 'bloque');
SET @s = IF(@c = 0, 'ALTER TABLE commercants ADD COLUMN bloque TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS transactions_paiement (id INT AUTO_INCREMENT PRIMARY KEY, commande_id INT NOT NULL, transaction_id VARCHAR(100) NOT NULL UNIQUE, montant DECIMAL(12,2) NOT NULL, statut ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente', date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS abonnement_paiements (id INT AUTO_INCREMENT PRIMARY KEY, commercant_id INT NOT NULL, plan ENUM('pro','business') NOT NULL, montant DECIMAL(12,2) NOT NULL, transaction_id VARCHAR(100) NOT NULL UNIQUE, statut ENUM('en_attente','accepte','refuse') NOT NULL DEFAULT 'en_attente', date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (commercant_id) REFERENCES commercants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS commercant_employes (id INT AUTO_INCREMENT PRIMARY KEY, commercant_id INT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, mot_de_passe_hash VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (commercant_id) REFERENCES commercants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS signalements (id INT AUTO_INCREMENT PRIMARY KEY, produit_id INT NOT NULL, motif VARCHAR(100) NOT NULL, commentaire TEXT, traite TINYINT(1) NOT NULL DEFAULT 0, date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE) ENGINE=InnoDB;

SELECT 'Mise à jour terminée avec succès' AS resultat;
