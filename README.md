# TechStore — Application Web Gestion de Stock
## Projet Programmation Web — P2I2 | FSS 2025-2026

---

## 📁 Structure des fichiers

```
magasin/
├── db.sql              ← Base de données (à importer dans phpMyAdmin)
├── config.php          ← Configuration DB + constantes
├── index.php           ← Page ADMIN (gestion stock, produits, catégories)
├── images/             ← Images catalogue (statique)
├── uploads/            ← Dossier photos produits (créé automatiquement)
└── README.md
```

---

## 🚀 Installation

### 1. Prérequis
- XAMPP / WAMP / LAMP (PHP 7.4+ et MySQL 5.7+)
- phpMyAdmin

### 2. Base de données
1. Ouvrir phpMyAdmin → http://localhost/phpmyadmin
2. Aller dans l'onglet **Importer**
3. Sélectionner le fichier `db.sql`
4. Cliquer **Exécuter**

### 3. Configuration
Ouvrir `config.php` et modifier si nécessaire :
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // votre utilisateur MySQL
define('DB_PASS', '');           // votre mot de passe
define('DB_NAME', 'magasin_info');

define('EMAIL_DISTRIBUTEUR', 'distributeur@magasin.tn'); // email du distributeur
```

### 4. Déploiement
Copier tout le dossier dans `htdocs/` (XAMPP) ou `www/` (WAMP) :
```
C:/xampp/htdocs/magasin/
```

### 5. Accès
| Page        | URL                                    |
|-------------|----------------------------------------|
| Admin       | http://localhost/magasin/index.php     |

---

## 📋 Fonctionnalités

### Page Admin (index.php)
| Fonctionnalité              | Description                                      |
|-----------------------------|--------------------------------------------------|
| Tableau de bord             | Statistiques : total produits, ruptures, alertes |
| Gestion catégories          | Ajout avec anti-doublon via sidebar              |
| Gestion produits            | CRUD complet avec upload photo                   |
| Filtrage par catégorie      | Clic sur sidebar = filtre automatique            |
| Recherche + tri             | Par désignation, prix, marque                    |
| Suppression conditionnelle  | Seulement si quantité = 0                        |
| Mise à jour stock (vente)   | Décrémentation avec vérification négatif         |

### Détails Interface Admin
| Fonctionnalité              | Description                                      |
|-----------------------------|--------------------------------------------------|
| Recherche automatique       | Rechargement automatique sans bouton             |
| Tri automatique             | Application immédiate au changement              |
| Fiche produit popup         | Détails, description, image agrandie             |
| Édition produit             | Modal modifier avec validations                  |
| Suppression catégorie       | Autorisée si vide ou stock total à 0             |

---

## 🗄️ Schéma Base de Données

```
categories
├── id (PK, AUTO_INCREMENT)
├── nom (UNIQUE, NOT NULL)
├── description
└── created_at

produits
├── id (PK, AUTO_INCREMENT)
├── reference (UNIQUE, NOT NULL)
├── designation (NOT NULL)
├── description
├── marque
├── prix (DECIMAL, > 0)
├── quantite (INT, ≥ 0)
├── photo
├── categorie_id (FK → categories.id)
└── created_at, updated_at
```

---

## ⚙️ Notes techniques
- Aucun framework CSS — CSS natif custom uniquement
- JavaScript vanilla (pas de jQuery)
- Protection contre les injections SQL (PDO préparé)
- Upload photos avec validation d'extension

---

## 👥 Auteurs
Projet réalisé dans le cadre du module Programmation Web
Faculté des Sciences de Sfax — P2I2 — 2025/2026
