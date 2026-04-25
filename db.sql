-- =============================================
-- BASE DE DONNÉES : Gestion Stock Magasin Info
-- =============================================

CREATE DATABASE IF NOT EXISTS magasin_info;
USE magasin_info;
ALTER DATABASE magasin_info CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des produits
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE,
    designation VARCHAR(200) NOT NULL,
    description TEXT,
    marque VARCHAR(100),
    prix DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL DEFAULT 0,
    photo VARCHAR(255),
    categorie_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table des commandes clients
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_client VARCHAR(150) NOT NULL,
    email_client VARCHAR(150) NOT NULL,
    telephone VARCHAR(30),
    adresse TEXT,
    total_ht DECIMAL(10,2) NOT NULL,
    frais_livraison DECIMAL(10,2) DEFAULT 7.00,
    total_ttc DECIMAL(10,2) NOT NULL,
    statut VARCHAR(20) DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des lignes de commande
CREATE TABLE IF NOT EXISTS lignes_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =============================================
-- DONNÉES INITIALES
-- =============================================

INSERT INTO categories (nom, description) VALUES
('Ordinateurs', 'Ordinateurs portables et de bureau'),
('Smartphones', 'Téléphones intelligents et accessoires'),
('Tablettes', 'Tablettes numériques'),
('Écrans PC', 'Moniteurs et écrans pour ordinateurs'),
('Écouteurs', 'Écouteurs, casques audio'),
('Imprimantes', 'Imprimantes jet d''encre et laser'),
('Scanners', 'Scanners de documents'),
('Cartouches d''encre', 'Cartouches pour imprimantes'),
('Disques durs', 'HDD, SSD internes et externes'),
('Chargeurs', 'Chargeurs et câbles'),
('Montres connectées', 'Smartwatches et bracelets connectés');

INSERT INTO produits (reference, designation, description, marque, prix, quantite, categorie_id) VALUES
('ORD-001', 'Laptop ProBook 450', 'Ordinateur portable 15.6" Intel i5, 8Go RAM, 512Go SSD', 'HP', 1299.99, 5, 1),
('ORD-002', 'ThinkPad E14', 'Laptop 14" AMD Ryzen 5, 16Go RAM, 256Go SSD', 'Lenovo', 1099.00, 3, 1),
('ORD-003', 'MacBook Air M2', 'Ultraportable Apple M2, 8Go, 256Go SSD', 'Apple', 2499.00, 0, 1),
('SMT-001', 'Galaxy S24', 'Smartphone 6.2" AMOLED 256Go Android 14', 'Samsung', 899.00, 8, 2),
('SMT-002', 'iPhone 15', 'Smartphone Apple 6.1" 128Go', 'Apple', 1099.00, 2, 2),
('SMT-003', 'Redmi Note 13', 'Smartphone 6.67" 128Go 5G', 'Xiaomi', 299.00, 15, 2),
('TAB-001', 'iPad 10ème gen', 'Tablette Apple 10.9" 64Go WiFi', 'Apple', 699.00, 4, 3),
('TAB-002', 'Galaxy Tab A8', 'Tablette 10.5" 64Go WiFi Android', 'Samsung', 299.00, 6, 3),
('ECR-001', 'Monitor UltraSharp 27"', 'Écran 4K IPS 60Hz USB-C', 'Dell', 599.00, 7, 4),
('ECR-002', 'ViewSonic 24"', 'Moniteur FHD 75Hz HDMI/VGA', 'ViewSonic', 199.00, 10, 4),
('ECO-001', 'WH-1000XM5', 'Casque Bluetooth ANC 30h autonomie', 'Sony', 349.00, 5, 5),
('ECO-002', 'AirPods Pro 2', 'Écouteurs intra-auriculaires ANC Apple', 'Apple', 299.00, 0, 5),
('IMP-001', 'LaserJet Pro M404', 'Imprimante laser N&B A4 38ppm', 'HP', 249.00, 3, 6),
('IMP-002', 'EcoTank ET-2850', 'Imprimante jet d''encre couleur WiFi', 'Epson', 199.00, 5, 6),
('DIS-001', 'SSD 1To Samsung 870', 'SSD SATA III 2.5" 1To 560Mo/s', 'Samsung', 89.00, 12, 9),
('DIS-002', 'HDD Externe 2To', 'Disque dur externe USB 3.0 portable', 'Seagate', 79.00, 8, 9),
('CHG-001', 'Chargeur 65W USB-C', 'Chargeur rapide GaN 65W compact', 'Anker', 39.00, 20, 10),
('MON-001', 'Galaxy Watch 6', 'Montre connectée 40mm GPS BT', 'Samsung', 249.00, 4, 11);
