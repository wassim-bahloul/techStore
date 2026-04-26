<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* TRAITEMENT POST */
 $message = '';
 $message_type = '';
 $action = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  /* Ajouter catégorie */
  if ($action === 'add_cat') {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    if ($nom) {
      try {
        $st = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
        $st->execute(array($nom, isset($_POST['description']) ? trim($_POST['description']) : ''));
        $message = 'Catégorie ajoutée !';
        $message_type = 'success';
      } catch (Exception $e) {
        $message = 'Erreur : Catégorie déjà existante';
        $message_type = 'error';
      }
    }
  }

  /* Ajouter produit */
  if ($action === 'add_produit') {
    $ref = isset($_POST['reference']) ? trim($_POST['reference']) : '';
    $desig = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $cat = isset($_POST['categorie_id']) ? intval($_POST['categorie_id']) : 0;
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
    $qty = isset($_POST['quantite']) ? intval($_POST['quantite']) : 0;
    
    $photo = '';
    $hasPhoto = !empty($_FILES['photo']['name']);

    if ($ref && $desig && $cat && $prix > 0) {
      if ($hasPhoto) {
        $ext = strtolower(substr($_FILES['photo']['name'], strrpos($_FILES['photo']['name'], '.') + 1));
        if (in_array($ext, array('jpg','jpeg','png','gif','webp'))) {
          $photo = uniqid('prod_') . '.' . $ext;
          move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR_FS . $photo);
        } else {
          $message = 'Erreur : image invalide';
          $message_type = 'error';
        }
      }

      if ($message_type !== 'error') {
        try {
          $st = $pdo->prepare("INSERT INTO produits (reference,designation,description,marque,prix,quantite,photo,categorie_id) VALUES (?,?,?,?,?,?,?,?)");
          $st->execute(array($ref, $desig, isset($_POST['description']) ? trim($_POST['description']) : '', isset($_POST['marque']) ? trim($_POST['marque']) : '', $prix, $qty, $photo, $cat));
          $message = 'Produit ajouté !';
          $message_type = 'success';
        } catch (Exception $e) {
          $message = 'Erreur : Référence déjà existante';
          $message_type = 'error';
        }
      }
    }
  }

  /* Modifier produit */
  if ($action === 'update_produit') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $ref = isset($_POST['reference']) ? trim($_POST['reference']) : '';
    $desig = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $cat = isset($_POST['categorie_id']) ? intval($_POST['categorie_id']) : 0;
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
    $qty = isset($_POST['quantite']) ? intval($_POST['quantite']) : -1;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $marque = isset($_POST['marque']) ? trim($_POST['marque']) : '';

    if ($id <= 0) {
      $message = 'Erreur : ID produit invalide';
      $message_type = 'error';
    } elseif ($ref === '' || $desig === '') {
      $message = 'Erreur : Référence et désignation obligatoires';
      $message_type = 'error';
    } elseif ($cat <= 0) {
      $message = 'Erreur : Catégorie invalide';
      $message_type = 'error';
    } elseif ($prix <= 0) {
      $message = 'Erreur : Le prix doit être positif';
      $message_type = 'error';
    } elseif ($qty < 0) {
      $message = 'Erreur : La quantité ne peut pas être négative';
      $message_type = 'error';
    } else {
      $st = $pdo->prepare("SELECT id, photo FROM produits WHERE id=?");
      $st->execute(array($id));
      $existingProduct = $st->fetch();

      if (!$existingProduct) {
        $message = 'Erreur : Produit introuvable';
        $message_type = 'error';
      } else {
        $st = $pdo->prepare("SELECT id FROM categories WHERE id=?");
        $st->execute(array($cat));
        $existingCategory = $st->fetch();

        if (!$existingCategory) {
          $message = 'Erreur : Catégorie invalide';
          $message_type = 'error';
        } else {
          $photo = $existingProduct['photo'];
          $hasPhoto = !empty($_FILES['photo']['name']);

          if ($hasPhoto) {
            $ext = strtolower(substr($_FILES['photo']['name'], strrpos($_FILES['photo']['name'], '.') + 1));
            if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
              $newPhoto = uniqid('prod_') . '.' . $ext;
              move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR_FS . $newPhoto);
              $photo = $newPhoto;
            } else {
              $message = 'Erreur : image invalide';
              $message_type = 'error';
            }
          }

          if ($message_type !== 'error') {
            try {
              $st = $pdo->prepare("UPDATE produits SET reference=?, designation=?, description=?, marque=?, prix=?, quantite=?, photo=?, categorie_id=? WHERE id=?");
              $st->execute(array($ref, $desig, $description, $marque, $prix, $qty, $photo, $cat, $id));
              $message = 'Produit modifié !';
              $message_type = 'success';
            } catch (Exception $e) {
              $message = 'Erreur : Référence déjà existante';
              $message_type = 'error';
            }
          }
        }
      }
    }
  }


  /* Supprimer produit */
  if ($action === 'delete_produit') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $st = $pdo->prepare("SELECT quantite FROM produits WHERE id=?");
    $st->execute(array($id));
    $p = $st->fetch();
    if (!$p) {
      $message = 'Erreur : Produit introuvable';
      $message_type = 'error';
    } elseif ($p['quantite'] > 0) {
      $message = 'Erreur : Stock non nul (' . $p['quantite'] . ')';
      $message_type = 'error';
    } else {
      $pdo->prepare("DELETE FROM produits WHERE id=?")->execute(array($id));
      $message = 'Produit supprimé !';
      $message_type = 'success';
    }
  }

  /* Supprimer catégorie */
  if ($action === 'delete_cat') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
      $message = 'Erreur : Catégorie introuvable';
      $message_type = 'error';
    } else {
      $st = $pdo->prepare("\n        SELECT c.id, c.nom, COUNT(p.id) AS nb_produits, COALESCE(SUM(CASE WHEN p.quantite > 0 THEN 1 ELSE 0 END), 0) AS nb_disponibles\n        FROM categories c\n        LEFT JOIN produits p ON p.categorie_id = c.id\n        WHERE c.id = ?\n        GROUP BY c.id\n      ");
      $st->execute(array($id));
      $cat = $st->fetch();

      if (!$cat) {
        $message = 'Erreur : Catégorie introuvable';
        $message_type = 'error';
      } elseif (intval($cat['nb_disponibles']) > 0) {
        $message = 'Erreur : suppression impossible, certains produits ont encore du stock';
        $message_type = 'error';
      } else {
        try {
          $pdo->beginTransaction();
          if (intval($cat['nb_produits']) > 0) {
            $pdo->prepare("DELETE FROM produits WHERE categorie_id=?")->execute(array($id));
          }
          $pdo->prepare("DELETE FROM categories WHERE id=?")->execute(array($id));
          $pdo->commit();

          if (isset($_POST['categorie_filter']) && intval($_POST['categorie_filter']) === $id) {
            $_POST['categorie_filter'] = 0;
          }

          $message = 'Catégorie supprimée !';
          $message_type = 'success';
        } catch (Exception $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          $message = 'Erreur : suppression impossible pour cette catégorie';
          $message_type = 'error';
        }
      }
    }
  }

  /* Ajouter au stock */
  if ($action === 'add_stock') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $qty = isset($_POST['quantite_ajoutee']) ? intval($_POST['quantite_ajoutee']) : 0;
    if ($qty <= 0) {
      $message = 'Erreur : Quantité invalide';
      $message_type = 'error';
    } else {
      $pdo->prepare("UPDATE produits SET quantite = quantite + ? WHERE id=?")->execute(array($qty, $id));
      $message = 'Stock ajouté. Nouveau stock : +' . $qty;
      $message_type = 'success';
    }
  }

  /* Mise à jour stock */
  if ($action === 'update_stock') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $qty = isset($_POST['quantite_vendue']) ? intval($_POST['quantite_vendue']) : 0;
    if ($qty <= 0) {
      $message = 'Erreur : Quantité invalide';
      $message_type = 'error';
    } else {
      $st = $pdo->prepare("SELECT quantite FROM produits WHERE id=?");
      $st->execute(array($id));
      $p = $st->fetch();
      if (!$p) {
        $message = 'Erreur : Produit introuvable';
        $message_type = 'error';
      } elseif ($p['quantite'] < $qty) {
        $message = 'Erreur : Stock insuffisant';
        $message_type = 'error';
      } else {
        $pdo->prepare("UPDATE produits SET quantite = quantite - ? WHERE id=?")->execute(array($qty, $id));
        $message = 'Vente enregistrée. Nouveau stock : ' . ($p['quantite'] - $qty);
        $message_type = 'success';
      }
    }
  }

  $_SESSION['flash_message'] = $message;
  $_SESSION['flash_type'] = $message_type;

  $redirectParams = array();
  $redirectParams['tab'] = isset($_POST['tab']) ? $_POST['tab'] : 'dashboard';

  if (isset($_POST['categorie_filter']) && intval($_POST['categorie_filter']) > 0) {
    $redirectParams['categorie_filter'] = intval($_POST['categorie_filter']);
  }

  if (isset($_POST['dashboard_filter']) && in_array($_POST['dashboard_filter'], array('all', 'rupture', 'faible'))) {
    $redirectParams['dashboard_filter'] = $_POST['dashboard_filter'];
  }

  if (isset($_POST['search_term']) && trim($_POST['search_term']) !== '') {
    $redirectParams['search_term'] = trim($_POST['search_term']);
  }

  if (isset($_POST['sort_choice']) && $_POST['sort_choice'] !== '') {
    $redirectParams['sort_choice'] = $_POST['sort_choice'];
  }

  if (isset($_POST['show_add_cat']) && $_POST['show_add_cat'] === '1') {
    $redirectParams['show_add_cat'] = '1';
  }

  if (isset($_POST['show_add_produit']) && $_POST['show_add_produit'] === '1') {
    $redirectParams['show_add_produit'] = '1';
  }

  $queryString = http_build_query($redirectParams);
  header('Location: index.php' . ($queryString !== '' ? '?' . $queryString : ''));
  exit;
}

if (isset($_SESSION['flash_message'])) {
  $message = $_SESSION['flash_message'];
  $message_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : '';
  unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

/* LECTURE DONNÉES */
 $categories = $pdo->query("SELECT c.*, COUNT(p.id) as nb_produits, COALESCE(SUM(CASE WHEN p.quantite > 0 THEN 1 ELSE 0 END), 0) as nb_disponibles FROM categories c LEFT JOIN produits p ON p.categorie_id=c.id GROUP BY c.id ORDER BY c.nom")->fetchAll();
 $produits = $pdo->query("SELECT p.*, c.nom as cat_nom FROM produits p JOIN categories c ON c.id=p.categorie_id ORDER BY c.nom, p.designation")->fetchAll();

 $selected_category_id = isset($_REQUEST['categorie_filter']) ? intval($_REQUEST['categorie_filter']) : 0;
 $dashboard_filter = isset($_REQUEST['dashboard_filter']) ? $_REQUEST['dashboard_filter'] : 'all';
if (!in_array($dashboard_filter, array('all', 'rupture', 'faible'))) {
  $dashboard_filter = 'all';
}

 $search_term = isset($_REQUEST['search_term']) ? trim($_REQUEST['search_term']) : '';

 $sort_choice = isset($_REQUEST['sort_choice']) ? $_REQUEST['sort_choice'] : '';
 $sort_by = isset($_REQUEST['sort_by']) ? $_REQUEST['sort_by'] : 'designation';
 $sort_dir = isset($_REQUEST['sort_dir']) ? $_REQUEST['sort_dir'] : 'asc';

if ($sort_choice !== '') {
  if ($sort_choice === 'designation_desc') {
    $sort_by = 'designation';
    $sort_dir = 'desc';
  } elseif ($sort_choice === 'marque_asc') {
    $sort_by = 'marque';
    $sort_dir = 'asc';
  } elseif ($sort_choice === 'marque_desc') {
    $sort_by = 'marque';
    $sort_dir = 'desc';
  } elseif ($sort_choice === 'prix_asc') {
    $sort_by = 'prix';
    $sort_dir = 'asc';
  } elseif ($sort_choice === 'prix_desc') {
    $sort_by = 'prix';
    $sort_dir = 'desc';
  } elseif ($sort_choice === 'quantite_asc') {
    $sort_by = 'quantite';
    $sort_dir = 'asc';
  } elseif ($sort_choice === 'quantite_desc') {
    $sort_by = 'quantite';
    $sort_dir = 'desc';
  } else {
    $sort_by = 'designation';
    $sort_dir = 'asc';
  }
}

if (!in_array($sort_by, array('designation', 'marque', 'prix', 'quantite'))) {
  $sort_by = 'designation';
}
if (!in_array($sort_dir, array('asc', 'desc'))) {
  $sort_dir = 'asc';
}

 $sort_choice = $sort_by . '_' . $sort_dir;

 $show_add_category_form = isset($_REQUEST['show_add_cat']) && $_REQUEST['show_add_cat'] === '1';
 $show_add_product_form = isset($_REQUEST['show_add_produit']) && $_REQUEST['show_add_produit'] === '1';

if ($action === 'add_cat' && $message_type === 'error') {
  $show_add_category_form = true;
}
if ($action === 'add_cat' && $message_type === 'success') {
  $show_add_category_form = false;
}

if ($action === 'add_produit' && $message_type === 'error') {
  $show_add_product_form = true;
}
if ($action === 'add_produit' && $message_type === 'success') {
  $show_add_product_form = false;
}

 $selected_category_name = '';
foreach ($categories as $cat) {
  if (intval($cat['id']) === $selected_category_id) {
    $selected_category_name = $cat['nom'];
    break;
  }
}

 $filtered_produits = array();
foreach ($produits as $prod) {
  if ($selected_category_id > 0 && intval($prod['categorie_id']) !== $selected_category_id) {
    continue;
  }

  if ($dashboard_filter === 'rupture' && intval($prod['quantite']) !== 0) {
    continue;
  }

  if ($dashboard_filter === 'faible' && !(intval($prod['quantite']) > 0 && intval($prod['quantite']) <= 3)) {
    continue;
  }

  if ($search_term !== '') {
    $needle = strtolower($search_term);
    $haystack = strtolower(
      (string)$prod['reference'] . ' ' .
      (string)$prod['designation'] . ' ' .
      (string)$prod['marque'] . ' ' .
      (string)$prod['cat_nom'] . ' ' .
      (string)$prod['description']
    );
    if (strpos($haystack, $needle) === false) {
      continue;
    }
  }

  $filtered_produits[] = $prod;
}

if (count($filtered_produits) > 1) {
  usort($filtered_produits, function($a, $b) use ($sort_by, $sort_dir) {
    if ($sort_by === 'quantite') {
      $cmp = intval($a['quantite']) - intval($b['quantite']);
    } elseif ($sort_by === 'prix') {
      $pa = floatval($a['prix']);
      $pb = floatval($b['prix']);
      if ($pa == $pb) $cmp = 0;
      elseif ($pa < $pb) $cmp = -1;
      else $cmp = 1;
    } elseif ($sort_by === 'marque') {
      $cmp = strcasecmp((string)$a['marque'], (string)$b['marque']);
    } else {
      $cmp = strcasecmp((string)$a['designation'], (string)$b['designation']);
    }

    if ($cmp === 0) {
      $cmp = strcasecmp((string)$a['designation'], (string)$b['designation']);
    }

    if ($sort_dir === 'desc') return -$cmp;
    return $cmp;
  });
}

/* STATISTIQUES */
 $stats = array('total_produits' => count($produits), 'total_categories' => count($categories), 'rupture' => 0, 'stock_faible' => 0);
foreach ($produits as $prod) {
  if ($prod['quantite'] == 0) $stats['rupture']++;
  elseif ($prod['quantite'] <= 3) $stats['stock_faible']++;
}

function getStockStyle($qty) {
  if ($qty == 0) return array('color' => '#ef4444', 'bg' => 'rgba(239,68,68,.15)');
  if ($qty <= 3) return array('color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.15)');
  return array('color' => '#10b981', 'bg' => 'rgba(16,185,129,.15)');
}

function resolveProductImageUrl($photo) {
  $safePhoto = basename((string)$photo);
  if ($safePhoto !== '' && is_file(UPLOAD_DIR_FS . $safePhoto)) return UPLOAD_DIR_URL . $safePhoto;

  return 'https://placehold.co/360x240/1e2435/64748b?text=no+image+yet';
}

 $active_tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'dashboard';
if (!in_array($active_tab, array('dashboard', 'produits'))) {
  $active_tab = 'dashboard';
}

include 'index.html';