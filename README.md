🏪 TechStore Admin
Tableau de bord de gestion de stock pour boutique tech

PHPMySQLLicense

📸 Aperçu
L'application propose une interface d'administration sombre et minimaliste centrée sur deux vues principales : le tableau de bord pour la supervision du stock et la page produits pour la gestion complète du catalogue.

Tableau de bord
Cartes statistiques en temps réel (total produits, catégories, ruptures, stock faible)
Filtrage rapide par état de stock
Recherche instantanée et tri multi-critères
Enregistrement de vente en un clic depuis le tableau
Gestion des produits
Ajout / modification / suppression de produits
Upload de photos (JPG, PNG, GIF, WEBP)
Modale de détails produit au clic
Indicateurs visuels de stock : 🔴 rupture · 🟠 faible (≤3) · 🟢 ok
📁 Structure
techstore-admin/
├── config.php # Connexion BDD + constantes d'upload
├── index.php # Logique serveur (POST, SQL, sessions, redirections)
├── index.html # Template HTML (traité par PHP via include)
├── app.js # Modales, auto-submit, validation client
├── uploads/ # Photos produits (auto-créé)
└── README.md

---

## 🚀 Installation

### 1. Cloner

```bash
git clone https://github.com/votre-user/techstore-admin.git
cd techstore-admin
⚙️ Fonctionnalités
Fonctionnalité
Détails
Catégories	CRUD complet, compteur de produits, suppression conditionnelle
Produits	Ajout avec photo, modification via modale, suppression si stock = 0
Recherche	Instantanée sur réf., désignation, marque, catégorie, description
Tri	Par désignation, prix, marque, quantité — croissant / décroissant
Vente	Décrémentation directe depuis le tableau de bord
Indicateurs	Badge coloré : rupture (rouge), faible ≤3 (orange), ok (vert)
Filtres	Par catégorie (sidebar) + par état de stock (dashboard)

🛡️ Règles métier
❌ Référence produit unique — doublon refusé
❌ Nom de catégorie unique — doublon refusé
❌ Suppression produit uniquement si stock = 0
❌ Suppression catégorie uniquement si aucun produit en stock
❌ Prix strictement positif
❌ Quantité ≥ 0
❌ Vente impossible si stock insuffisant
✅ Suppression catégorie → ses produits en rupture supprimés en transaction
