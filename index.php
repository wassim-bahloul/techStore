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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>TechStore Admin — Tableau de Bord et Produits</title>
</head>
<body bgcolor="#0d0f14" text="#e2e8f0" style="font-family: Segoe UI, Arial, sans-serif; font-size: 19px; line-height: 1.4; margin: 0; padding: 0;">

<!-- TOP BAR -->
<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#161a23">
<tr>
  <td align="left" width="40%" style="padding: 12px 24px;">
    <b style="font-size: 1.2em; color: #3b82f6;">Tech<span style="color: #e2e8f0;">Store</span> <small style="font-size: 0.7em; color: #64748b; font-weight: normal;">Admin</small></b>
  </td>
  <td align="right" width="60%" style="padding: 12px 24px; border-left: 1px solid #2a3045;">
    <form method="POST" action="index.php" style="display: inline;">
      <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
      <?php if ($dashboard_filter !== 'all'): ?><input type="hidden" name="dashboard_filter" value="<?php echo htmlspecialchars($dashboard_filter); ?>"><?php endif; ?>
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
      <button type="submit" name="tab" value="dashboard" style="background: <?php echo ($active_tab === 'dashboard' ? '#1e2435' : 'none'); ?>; border: <?php echo ($active_tab === 'dashboard' ? '1px solid #3b82f6' : 'none'); ?>; color: <?php echo ($active_tab === 'dashboard' ? '#3b82f6' : '#64748b'); ?>; padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.9em; font-weight: 500; margin-right: 6px;">Tableau de bord</button>
      <button type="submit" name="tab" value="produits" style="background: <?php echo ($active_tab === 'produits' ? '#1e2435' : 'none'); ?>; border: <?php echo ($active_tab === 'produits' ? '1px solid #3b82f6' : 'none'); ?>; color: <?php echo ($active_tab === 'produits' ? '#3b82f6' : '#64748b'); ?>; padding: 6px 16px; border-radius: 8px; cursor: pointer; font-size: 0.9em; font-weight: 500;">Produits</button>
    </form>
  </td>
</tr>
</table>

<!-- MESSAGE -->
<?php if ($message && $message_type === 'success'): ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td style="padding: 12px 24px; background: rgba(16,185,129,.08); border-left: 4px solid #10b981; color: #10b981; font-weight: bold;">
    <?php echo $message; ?>
  </td>
</tr>
</table>
<?php endif; ?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
<tr>
  <!-- SIDEBAR -->
  <td width="220" bgcolor="#161a23" valign="top" style="border-right: 1px solid #2a3045; padding: 16px 0;">
    <div style="padding: 0 16px 12px; font-size: 0.75em; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; border-bottom: 1px solid #2a3045; margin-bottom: 8px;"><b>Catégories</b></div>
    
    <?php foreach ($categories as $cat): ?>
    <?php $is_cat_selected = ($selected_category_id === intval($cat['id'])); ?>
    <?php $can_delete_category = (intval($cat['nb_produits']) === 0 || intval($cat['nb_disponibles']) === 0); ?>
    <div style="display: flex; align-items: center; gap: 6px; margin: 0; padding: 0 0 0 0;">
      <form method="POST" action="index.php" style="margin: 0; flex: 1 1 auto;">
        <input type="hidden" name="categorie_filter" value="<?php echo intval($cat['id']); ?>">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
        <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
        <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
        <button type="submit" style="width: 100%; text-align: left; padding: 9px 16px; background: <?php echo ($is_cat_selected ? '#1e2435' : 'none'); ?>; border: none; border-left: 3px solid <?php echo ($is_cat_selected ? '#3b82f6' : 'transparent'); ?>; cursor: pointer; font-size: 0.875em; color: <?php echo ($is_cat_selected ? '#e2e8f0' : '#64748b'); ?>; display: flex; align-items: center; gap: 10px;">
          <span><?php echo htmlspecialchars($cat['nom']); ?></span>
        </button>
      </form>
      <?php if ($can_delete_category): ?>
      <form method="POST" action="index.php" style="margin: 0; flex: 0 0 auto;" onsubmit="return confirm('Supprimer cette catégorie ? Les produits en rupture seront aussi supprimés.');">
        <input type="hidden" name="action" value="delete_cat">
        <input type="hidden" name="id" value="<?php echo intval($cat['id']); ?>">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
        <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
        <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
        <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
        <?php if ($dashboard_filter !== 'all'): ?><input type="hidden" name="dashboard_filter" value="<?php echo htmlspecialchars($dashboard_filter); ?>"><?php endif; ?>
        <button type="submit" style="height: 100%; padding: 4px 8px; background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.35); color: #ef4444; border-radius: 8px; cursor: pointer; font-size: 0.7em; line-height: 1; min-width: 28px;">-</button>
      </form>
      <?php endif; ?>
      <span style="background: #1e2435; color: #64748b; font-size: 0.7em; padding: 1px 7px; border-radius: 20px; border: 1px solid #2a3045; display: inline-flex; align-items: center; white-space: nowrap;"><?php echo $cat['nb_produits']; ?></span>
    </div>
    <?php endforeach; ?>

    <form method="POST" action="index.php" style="margin: 8px 0 0;">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
      <button type="submit" style="width: 100%; text-align: left; padding: 9px 16px; background: none; border: none; border-left: 3px solid transparent; cursor: pointer; font-size: 0.875em; color: #64748b;">Tous les produits</button>
    </form>
    
    <form method="POST" action="index.php" style="margin-top: 12px; padding: 0 16px;">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
      <input type="hidden" name="show_add_cat" value="1">
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
      <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
      <?php if ($dashboard_filter !== 'all'): ?><input type="hidden" name="dashboard_filter" value="<?php echo htmlspecialchars($dashboard_filter); ?>"><?php endif; ?>
      <button type="submit" style="width: 100%; padding: 8px; background: none; border: 1px dashed #2a3045; color: #64748b; border-radius: 8px; cursor: pointer; font-size: 0.82em;">+ Nouvelle catégorie</button>
    </form>

    <?php if ($show_add_category_form): ?>
    <form method="POST" action="index.php" style="margin-top: 8px; padding: 0 16px;">
      <input type="hidden" name="action" value="add_cat">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
      <input type="hidden" name="show_add_cat" value="1">
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
      <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
      <?php if ($dashboard_filter !== 'all'): ?><input type="hidden" name="dashboard_filter" value="<?php echo htmlspecialchars($dashboard_filter); ?>"><?php endif; ?>
      <input type="text" name="nom" placeholder="Nom catégorie" required style="width: 100%; box-sizing: border-box; margin-bottom: 6px; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px; font-size: 0.8em;">
      <input type="text" name="description" placeholder="Description (optionnel)" style="width: 100%; box-sizing: border-box; margin-bottom: 6px; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px; font-size: 0.8em;">
      <button type="submit" style="width: 100%; padding: 8px; background: #3b82f6; border: none; color: #fff; border-radius: 8px; cursor: pointer; font-size: 0.82em;">Ajouter catégorie</button>
    </form>
    <?php endif; ?>
  </td>

  <!-- MAIN -->
  <td valign="top" style="padding: 28px;">

    <?php if ($active_tab === 'dashboard'): ?>
    <h2 style="font-size: 2em; font-weight: 600; margin-bottom: 20px;">Tableau de bord</h2>

    <form method="POST" action="index.php" data-auto-submit-form="1" style="margin: 0 0 16px;">
      <input type="hidden" name="tab" value="dashboard">
      <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
      <?php if ($dashboard_filter !== 'all'): ?><input type="hidden" name="dashboard_filter" value="<?php echo htmlspecialchars($dashboard_filter); ?>"><?php endif; ?>
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="text" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Rechercher..." data-auto-submit="search" style="padding: 5px 7px; width: 200px; background: #0d0f14; border: 1px solid #2a3045; border-radius: 8px; color: #e2e8f0; margin-right: 6px;">
      <span style="font-size: 0.85em; color: #64748b; margin-right: 6px;">Choix tri</span>
      <select name="sort_choice" data-auto-submit="sort" style="padding: 5px 7px; background: #0d0f14; border: 1px solid #2a3045; border-radius: 8px; color: #e2e8f0;">
        <option value="designation_asc" <?php echo ($sort_choice === 'designation_asc' ? 'selected' : ''); ?>>A-Z</option>
        <option value="designation_desc" <?php echo ($sort_choice === 'designation_desc' ? 'selected' : ''); ?>>Z-A</option>
        <option value="prix_asc" <?php echo ($sort_choice === 'prix_asc' ? 'selected' : ''); ?>>Prix croissant</option>
        <option value="prix_desc" <?php echo ($sort_choice === 'prix_desc' ? 'selected' : ''); ?>>Prix décroissant</option>
        <option value="marque_asc" <?php echo ($sort_choice === 'marque_asc' ? 'selected' : ''); ?>>Marque A-Z</option>
        <option value="marque_desc" <?php echo ($sort_choice === 'marque_desc' ? 'selected' : ''); ?>>Marque Z-A</option>
        <option value="quantite_asc" <?php echo ($sort_choice === 'quantite_asc' ? 'selected' : ''); ?>>Stock croissant</option>
        <option value="quantite_desc" <?php echo ($sort_choice === 'quantite_desc' ? 'selected' : ''); ?>>Stock décroissant</option>
      </select>
    </form>
    
    <table border="0" cellpadding="0" cellspacing="18">
    <tr>
      <td style="text-align: left; background: #161a23; border: 1px solid #2a3045; border-radius: 10px; padding: 18px; width: 190px;">
        <form method="POST" action="index.php" style="margin: 0;">
          <input type="hidden" name="tab" value="dashboard">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
          <input type="hidden" name="dashboard_filter" value="all">
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <button type="submit" style="width: 100%; text-align: left; background: transparent; border: none; color: inherit; cursor: pointer; padding: 0;">
            <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase;">Total produits</div>
            <div style="font-size: 2.05em; font-weight: 700; margin-top: 4px; color: #3b82f6;"><?php echo $stats['total_produits']; ?></div>
          </button>
        </form>
      </td>
      <td style="text-align: left; background: #161a23; border: 1px solid #2a3045; border-radius: 10px; padding: 18px; width: 190px;">
        <form method="POST" action="index.php" style="margin: 0;">
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <button type="submit" name="tab" value="produits" style="width: 100%; text-align: left; background: transparent; border: none; color: inherit; cursor: pointer; padding: 0;">
            <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase;">Catégories</div>
            <div style="font-size: 2.05em; font-weight: 700; margin-top: 4px; color: #10b981;"><?php echo $stats['total_categories']; ?></div>
          </button>
        </form>
      </td>
      <td style="text-align: left; background: #161a23; border: 1px solid #2a3045; border-radius: 10px; padding: 18px; width: 190px;">
        <form method="POST" action="index.php" style="margin: 0;">
          <input type="hidden" name="tab" value="dashboard">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
          <input type="hidden" name="dashboard_filter" value="rupture">
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <button type="submit" style="width: 100%; text-align: left; background: transparent; border: none; color: inherit; cursor: pointer; padding: 0;">
            <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase;">En rupture</div>
            <div style="font-size: 2.05em; font-weight: 700; margin-top: 4px; color: #ef4444;"><?php echo $stats['rupture']; ?></div>
          </button>
        </form>
      </td>
      <td style="text-align: left; background: #161a23; border: 1px solid #2a3045; border-radius: 10px; padding: 18px; width: 190px;">
        <form method="POST" action="index.php" style="margin: 0;">
          <input type="hidden" name="tab" value="dashboard">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
          <input type="hidden" name="dashboard_filter" value="faible">
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <button type="submit" style="width: 100%; text-align: left; background: transparent; border: none; color: inherit; cursor: pointer; padding: 0;">
            <div style="font-size: 0.85em; color: #64748b; text-transform: uppercase;">Stock faible (≤3)</div>
            <div style="font-size: 2.05em; font-weight: 700; margin-top: 4px; color: #f59e0b;"><?php echo $stats['stock_faible']; ?></div>
          </button>
        </form>
      </td>
    </tr>
    </table>

    <h2 style="font-size: 1.7em; font-family: Georgia, 'Times New Roman', serif; font-weight: 700; margin: 24px 0 20px; letter-spacing: 0.02em;">
      <?php
        if ($dashboard_filter === 'rupture') echo 'Produits en rupture';
        elseif ($dashboard_filter === 'faible') echo 'Produits à stock faible (≤3)';
        else echo 'Tous les produits';
      ?>
      <?php if ($selected_category_name !== ''): ?>
        — Catégorie : <?php echo htmlspecialchars($selected_category_name); ?>
      <?php endif; ?>
    </h2>

    <table width="100%" border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse;">
    <tr bgcolor="#1e2435">
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Photo</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Réf.</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Désignation</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Marque</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Prix</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Stock</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Vente</th>
    </tr>
    
    <?php foreach ($filtered_produits as $p):
      $styles = getStockStyle($p['quantite']);
      $product_image_url = resolveProductImageUrl($p['photo']);
    ?>
    <tr
      style="border-bottom: 1px solid #2a3045; cursor: pointer;"
      onclick="openProductModal(this)"
      data-id="<?php echo intval($p['id']); ?>"
      data-reference="<?php echo htmlspecialchars($p['reference'], ENT_QUOTES); ?>"
      data-designation="<?php echo htmlspecialchars($p['designation'], ENT_QUOTES); ?>"
      data-marque="<?php echo htmlspecialchars($p['marque'], ENT_QUOTES); ?>"
      data-prix="<?php echo htmlspecialchars(number_format($p['prix'], 2, '.', ''), ENT_QUOTES); ?>"
      data-quantite="<?php echo intval($p['quantite']); ?>"
      data-category-id="<?php echo intval($p['categorie_id']); ?>"
      data-categorie="<?php echo htmlspecialchars($p['cat_nom'], ENT_QUOTES); ?>"
      data-description="<?php echo htmlspecialchars((string)$p['description'], ENT_QUOTES); ?>"
      data-image-url="<?php echo htmlspecialchars($product_image_url, ENT_QUOTES); ?>"
    >
      <td style="border: 1px solid #2a3045; padding: 12px 14px;"><img src="<?php echo htmlspecialchars($product_image_url); ?>" width="52" height="52" style="border-radius: 6px; background: #1e2435;"></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><code style="font-size: 0.78em; color: #64748b;"><?php echo htmlspecialchars($p['reference']); ?></code></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><?php echo htmlspecialchars($p['designation']); ?></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><?php echo htmlspecialchars($p['marque']); ?></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><b><?php echo number_format($p['prix'], 2); ?> DT</b></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><span style="display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 0.78em; font-weight: 600; background: <?php echo $styles['bg']; ?>; color: <?php echo $styles['color']; ?>;"><?php echo $p['quantite']; ?></span></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;" onclick="event.stopPropagation();">
        <?php if ($p['quantite'] > 0): ?>
        <form method="POST" action="index.php" style="display: flex; gap: 6px; align-items: center;">
          <input type="hidden" name="action" value="update_stock">
          <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
          <input type="hidden" name="tab" value="dashboard">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
          <?php if ($dashboard_filter !== 'all'): ?><input type="hidden" name="dashboard_filter" value="<?php echo htmlspecialchars($dashboard_filter); ?>"><?php endif; ?>
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <input type="number" name="quantite_vendue" min="1" max="<?php echo $p['quantite']; ?>" value="1" style="width: 50px; padding: 4px 8px; background: #0d0f14; border: 1px solid #2a3045; border-radius: 8px; color: #e2e8f0; font-size: 0.75em;">
          <button type="submit" style="background: #ef4444; color: #fff; border: none; padding: 3px 8px; border-radius: 8px; cursor: pointer; font-size: 0.75em;">- Vente</button>
        </form>
        <?php else: ?>
        <span style="color: #ef4444; font-size: 0.8em;">Rupture</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </table>

    <?php elseif ($active_tab === 'produits'): ?>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td align="left"><h2 style="font-size: 2em; font-weight: 600; margin: 0;">Produits<?php if ($selected_category_name !== ''): ?> — Catégorie : <?php echo htmlspecialchars($selected_category_name); ?><?php endif; ?></h2></td>
      <td align="right">
        <form method="POST" action="index.php" style="display: inline;">
          <input type="hidden" name="tab" value="produits">
          <input type="hidden" name="show_add_produit" value="1">
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
          <button type="submit" style="background: #3b82f6; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.875em;">+ Nouveau produit</button>
        </form>
      </td>
    </tr>
    </table>

    <?php if ($show_add_product_form): ?>
    <form method="POST" action="index.php" enctype="multipart/form-data" style="margin: 14px 0 14px; padding: 12px; border: 1px solid #2a3045; border-radius: 8px; background: #161a23;">
      <input type="hidden" name="action" value="add_produit">
      <input type="hidden" name="tab" value="produits">
      <input type="hidden" name="show_add_produit" value="1">
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
      <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>

      <div style="margin-bottom: 8px; font-size: 0.8em; color: #64748b;">
        La photo est optionnelle. Si aucune image n'est fournie, "no image yet" sera affiché.
      </div>

      <table width="100%" border="0" cellpadding="4" cellspacing="0">
      <tr>
        <td width="16%"><input type="text" name="reference" required placeholder="Référence" style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td width="18%"><input type="text" name="designation" required placeholder="Désignation" style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td width="16%"><input type="text" name="marque" placeholder="Marque" style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td width="14%"><input type="number" name="prix" min="0.01" step="0.01" required placeholder="Prix" style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td width="12%"><input type="number" name="quantite" min="0" value="0" placeholder="Stock" style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td width="24%">
          <select name="categorie_id" required style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
            <option value="">Catégorie...</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo intval($cat['id']); ?>" <?php echo ($selected_category_id === intval($cat['id']) ? 'selected' : ''); ?>><?php echo htmlspecialchars($cat['nom']); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <td colspan="3"><input type="text" name="description" placeholder="Description (optionnel)" style="width: 100%; box-sizing: border-box; padding: 7px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td colspan="2"><input type="file" name="photo" accept="image/*" style="width: 100%; box-sizing: border-box; padding: 6px 8px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;"></td>
        <td><button type="submit" style="width: 100%; background: #3b82f6; color: #fff; border: none; padding: 8px 11px; border-radius: 8px; cursor: pointer; font-size: 0.86em;">+ Ajouter produit</button></td>
      </tr>
      </table>
    </form>
    <?php endif; ?>

    <form method="POST" action="index.php" data-auto-submit-form="1" style="margin: 14px 0 10px;">
      <input type="hidden" name="tab" value="produits">
      <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
      <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
      <input type="text" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Rechercher..." data-auto-submit="search" style="padding: 5px 7px; width: 200px; background: #0d0f14; border: 1px solid #2a3045; border-radius: 8px; color: #e2e8f0; margin-right: 6px;">
      <span style="font-size: 0.82em; color: #64748b; margin-right: 6px;">Choix tri</span>
      <select name="sort_choice" data-auto-submit="sort" style="padding: 5px 7px; background: #0d0f14; border: 1px solid #2a3045; border-radius: 8px; color: #e2e8f0;">
        <option value="designation_asc" <?php echo ($sort_choice === 'designation_asc' ? 'selected' : ''); ?>>A-Z</option>
        <option value="designation_desc" <?php echo ($sort_choice === 'designation_desc' ? 'selected' : ''); ?>>Z-A</option>
        <option value="prix_asc" <?php echo ($sort_choice === 'prix_asc' ? 'selected' : ''); ?>>Prix croissant</option>
        <option value="prix_desc" <?php echo ($sort_choice === 'prix_desc' ? 'selected' : ''); ?>>Prix décroissant</option>
        <option value="marque_asc" <?php echo ($sort_choice === 'marque_asc' ? 'selected' : ''); ?>>Marque A-Z</option>
        <option value="marque_desc" <?php echo ($sort_choice === 'marque_desc' ? 'selected' : ''); ?>>Marque Z-A</option>
        <option value="quantite_asc" <?php echo ($sort_choice === 'quantite_asc' ? 'selected' : ''); ?>>Stock croissant</option>
        <option value="quantite_desc" <?php echo ($sort_choice === 'quantite_desc' ? 'selected' : ''); ?>>Stock décroissant</option>
      </select>
    </form>

    <table width="100%" border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; margin-top: 20px;">
    <tr bgcolor="#1e2435">
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Réf.</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Désignation</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Marque</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Prix</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Stock</th>
      <th align="left" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; border: 1px solid #2a3045;">Actions</th>
    </tr>
    
    <?php foreach ($filtered_produits as $p):
      $styles = getStockStyle($p['quantite']);
      $product_image_url = resolveProductImageUrl($p['photo']);
    ?>
    <tr
      style="border-bottom: 1px solid #2a3045; cursor: pointer;"
      onclick="openProductModal(this)"
      data-id="<?php echo intval($p['id']); ?>"
      data-reference="<?php echo htmlspecialchars($p['reference'], ENT_QUOTES); ?>"
      data-designation="<?php echo htmlspecialchars($p['designation'], ENT_QUOTES); ?>"
      data-marque="<?php echo htmlspecialchars($p['marque'], ENT_QUOTES); ?>"
      data-prix="<?php echo htmlspecialchars(number_format($p['prix'], 2, '.', ''), ENT_QUOTES); ?>"
      data-quantite="<?php echo intval($p['quantite']); ?>"
      data-category-id="<?php echo intval($p['categorie_id']); ?>"
      data-categorie="<?php echo htmlspecialchars($p['cat_nom'], ENT_QUOTES); ?>"
      data-description="<?php echo htmlspecialchars((string)$p['description'], ENT_QUOTES); ?>"
      data-image-url="<?php echo htmlspecialchars($product_image_url, ENT_QUOTES); ?>"
    >
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><code style="font-size: 0.78em; color: #64748b;"><?php echo htmlspecialchars($p['reference']); ?></code></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><?php echo htmlspecialchars($p['designation']); ?></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><?php echo htmlspecialchars($p['marque']); ?></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><b><?php echo number_format($p['prix'], 2); ?> DT</b></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;"><span style="display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 0.78em; font-weight: 600; background: <?php echo $styles['bg']; ?>; color: <?php echo $styles['color']; ?>;"><?php echo $p['quantite']; ?></span></td>
      <td style="border: 1px solid #2a3045; padding: 10px 12px;" onclick="event.stopPropagation();">
        <button type="button" onclick="openEditProductModal(this)" style="background: #3b82f6; color: #fff; border: none; padding: 4px 10px; border-radius: 8px; cursor: pointer; font-size: 0.8em; margin-right: 6px;">Modifier</button>
        <form method="POST" action="index.php" style="display: inline-block;" onsubmit="return confirm('Êtes-vous sûr ?');">
          <input type="hidden" name="action" value="delete_produit">
          <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
          <input type="hidden" name="tab" value="produits">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <button type="submit" style="background: #ef4444; color: #fff; border: none; padding: 4px 10px; border-radius: 8px; cursor: pointer; font-size: 0.8em;">Supprimer</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </table>

    <div id="productEditModal" style="display: none; position: fixed; inset: 0; z-index: 10000; background: rgba(13,15,20,.72); backdrop-filter: blur(6px); padding: 24px; box-sizing: border-box; overflow: auto;">
      <div style="max-width: 920px; margin: 4vh auto 24px; background: #161a23; border: 1px solid #2a3045; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 70px rgba(0,0,0,.45);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #2a3045;">
          <div>
            <div style="font-size: 0.75em; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em;">Modifier produit</div>
            <div id="productEditTitle" style="font-size: 1.35em; font-weight: 700; color: #e2e8f0; margin-top: 4px;"></div>
          </div>
          <button type="button" onclick="closeProductEditModal()" style="background: none; border: 1px solid #2a3045; color: #e2e8f0; width: 36px; height: 36px; border-radius: 999px; cursor: pointer; font-size: 1.1em; line-height: 1;">&times;</button>
        </div>
        <form id="productEditForm" method="POST" action="index.php" enctype="multipart/form-data" style="padding: 18px 20px 20px;">
          <input type="hidden" name="action" value="update_produit">
          <input type="hidden" name="id" id="productEditId" value="">
          <input type="hidden" name="tab" value="produits">
          <input type="hidden" name="sort_choice" value="<?php echo htmlspecialchars($sort_choice); ?>">
          <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
          <?php if ($selected_category_id > 0): ?><input type="hidden" name="categorie_filter" value="<?php echo $selected_category_id; ?>"><?php endif; ?>

          <div style="display: grid; grid-template-columns: 1fr 280px; gap: 18px; align-items: start;">
            <div style="display: grid; gap: 12px;">
              <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                <input type="text" name="reference" id="productEditReference" required placeholder="Référence" style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
                <input type="text" name="designation" id="productEditDesignation" required placeholder="Désignation" style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
                <input type="text" name="marque" id="productEditMarque" placeholder="Marque" style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
                <input type="number" name="prix" id="productEditPrix" min="0.01" step="0.01" required placeholder="Prix" style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
                <input type="number" name="quantite" id="productEditQuantite" min="0" required placeholder="Quantité" style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
                <select name="categorie_id" id="productEditCategory" required style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
                  <option value="">Catégorie...</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo intval($cat['id']); ?>"><?php echo htmlspecialchars($cat['nom']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <textarea name="description" id="productEditDescription" placeholder="Description" rows="6" style="width: 100%; box-sizing: border-box; padding: 9px 10px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px; resize: vertical;"></textarea>
              <div style="font-size: 0.82em; color: #64748b;">L’image est optionnelle. Si tu en envoies une nouvelle, elle remplacera l’ancienne.</div>
              <input type="file" name="photo" accept="image/*" style="width: 100%; box-sizing: border-box; padding: 8px 9px; background: #0d0f14; border: 1px solid #2a3045; color: #e2e8f0; border-radius: 8px;">
            </div>
            <div>
              <img id="productEditImage" alt="Produit" style="width: 100%; max-height: 360px; object-fit: contain; background: #0d0f14; border: 1px solid #2a3045; border-radius: 16px; display: block; margin-bottom: 12px;">
              <div style="display: grid; gap: 8px; font-size: 0.88em; color: #64748b;">
                <div><b style="color: #e2e8f0;">ID:</b> <span id="productEditIdLabel"></span></div>
                <div><b style="color: #e2e8f0;">Catégorie actuelle:</b> <span id="productEditCategoryLabel"></span></div>
              </div>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;">
            <button type="button" onclick="closeProductEditModal()" style="background: transparent; color: #e2e8f0; border: 1px solid #2a3045; padding: 8px 14px; border-radius: 8px; cursor: pointer;">Annuler</button>
            <button type="submit" style="background: #3b82f6; color: #fff; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: 600;">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

    <?php endif; ?>

  </td>
</tr>
</table>

<div id="productModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(13,15,20,.72); backdrop-filter: blur(6px); padding: 24px; box-sizing: border-box;">
  <div style="max-width: 860px; margin: 6vh auto 0; background: #161a23; border: 1px solid #2a3045; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 70px rgba(0,0,0,.45);">
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #2a3045;">
      <div>
        <div id="productModalCategory" style="font-size: 0.75em; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em;">Produit</div>
        <div id="productModalTitle" style="font-size: 1.35em; font-weight: 700; color: #e2e8f0; margin-top: 4px;"></div>
      </div>
      <button type="button" onclick="closeProductModal()" style="background: none; border: 1px solid #2a3045; color: #e2e8f0; width: 36px; height: 36px; border-radius: 999px; cursor: pointer; font-size: 1.1em; line-height: 1;">&times;</button>
    </div>
    <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 0;">
      <div style="padding: 20px; border-right: 1px solid #2a3045;">
        <img id="productModalImage" alt="Produit" style="width: 100%; max-height: 460px; object-fit: contain; background: #0d0f14; border: 1px solid #2a3045; border-radius: 16px; display: block;" />
      </div>
      <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 10px; font-size: 0.95em;">
          <div style="color: #64748b;">Référence</div><div id="productModalReference" style="color: #e2e8f0;"></div>
          <div style="color: #64748b;">Marque</div><div id="productModalMarque" style="color: #e2e8f0;"></div>
          <div style="color: #64748b;">Prix</div><div id="productModalPrix" style="color: #e2e8f0; font-weight: 700;"></div>
          <div style="color: #64748b;">Stock</div><div id="productModalStock" style="color: #e2e8f0;"></div>
        </div>
        <div>
          <div style="font-size: 0.78em; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px;">Description</div>
          <div id="productModalDescription" style="color: #cbd5e1; font-size: 0.95em; line-height: 1.55; white-space: pre-wrap; min-height: 120px;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
<script>
(function () {
  var forms = document.querySelectorAll('form[data-auto-submit-form="1"]');
  var timers = new WeakMap();
  var modal = document.getElementById('productModal');
  var editForm = document.getElementById('productEditForm');

  function restoreSearchFocus() {
    var shouldSkipSearchFocus = <?php echo ($show_add_category_form || $show_add_product_form) ? 'true' : 'false'; ?>;
    if (shouldSkipSearchFocus) {
      return;
    }

    var searchInput = document.querySelector('form[data-auto-submit-form="1"] [data-auto-submit="search"]');

    if (!searchInput) {
      return;
    }

    searchInput.focus();
    if (typeof searchInput.setSelectionRange === 'function') {
      var length = searchInput.value.length;
      searchInput.setSelectionRange(length, length);
    }
  }

  function submitForm(form) {
    if (!form) {
      return;
    }

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }

    form.submit();
  }

  function setModalText(id, value) {
    var node = document.getElementById(id);
    if (node) {
      node.textContent = value;
    }
  }

  window.openProductModal = function (row) {
    if (!modal || !row) {
      return;
    }

    setModalText('productModalCategory', row.getAttribute('data-categorie') || 'Produit');
    setModalText('productModalTitle', row.getAttribute('data-designation') || '');
    setModalText('productModalReference', row.getAttribute('data-reference') || '');
    setModalText('productModalMarque', row.getAttribute('data-marque') || '-');
    setModalText('productModalPrix', (row.getAttribute('data-prix') || '0.00') + ' DT');
    setModalText('productModalStock', row.getAttribute('data-quantite') || '0');
    setModalText('productModalDescription', row.getAttribute('data-description') || 'Aucune description disponible.');

    var image = document.getElementById('productModalImage');
    var imageUrl = row.getAttribute('data-image-url') || '';
    if (image) {
      image.src = imageUrl;
      image.alt = row.getAttribute('data-designation') || 'Produit';
    }

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
  };

  window.closeProductModal = function () {
    if (!modal) {
      return;
    }

    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  window.openEditProductModal = function (button) {
    var editModal = document.getElementById('productEditModal');
    var row = button && typeof button.closest === 'function' ? button.closest('tr') : null;

    if (!editModal || !row) {
      return;
    }

    window.closeProductModal();

    var imageUrl = row.getAttribute('data-image-url') || '';
    var categoryId = row.getAttribute('data-category-id') || '';

    setModalText('productEditTitle', row.getAttribute('data-designation') || '');
    setModalText('productEditIdLabel', row.getAttribute('data-id') || '');
    setModalText('productEditCategoryLabel', row.getAttribute('data-categorie') || '');

    var idInput = document.getElementById('productEditId');
    var referenceInput = document.getElementById('productEditReference');
    var designationInput = document.getElementById('productEditDesignation');
    var marqueInput = document.getElementById('productEditMarque');
    var prixInput = document.getElementById('productEditPrix');
    var quantiteInput = document.getElementById('productEditQuantite');
    var categoryInput = document.getElementById('productEditCategory');
    var descriptionInput = document.getElementById('productEditDescription');
    var imageInput = document.getElementById('productEditImage');

    if (idInput) idInput.value = row.getAttribute('data-id') || '';
    if (referenceInput) referenceInput.value = row.getAttribute('data-reference') || '';
    if (designationInput) designationInput.value = row.getAttribute('data-designation') || '';
    if (marqueInput) marqueInput.value = row.getAttribute('data-marque') || '';
    if (prixInput) prixInput.value = row.getAttribute('data-prix') || '';
    if (quantiteInput) quantiteInput.value = row.getAttribute('data-quantite') || '0';
    if (categoryInput) categoryInput.value = categoryId;
    if (descriptionInput) descriptionInput.value = row.getAttribute('data-description') || '';
    if (imageInput) {
      imageInput.src = imageUrl;
      imageInput.alt = row.getAttribute('data-designation') || 'Produit';
    }

    editModal.style.display = 'block';
    document.body.style.overflow = 'hidden';
  };

  window.closeProductEditModal = function () {
    var editModal = document.getElementById('productEditModal');

    if (!editModal) {
      return;
    }

    editModal.style.display = 'none';
    document.body.style.overflow = '';
  };

  var editModal = document.getElementById('productEditModal');
  if (editModal) {
    editModal.addEventListener('click', function (event) {
      if (event.target === editModal) {
        window.closeProductEditModal();
      }
    });
  }

  if (modal) {
    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        window.closeProductModal();
      }
    });
  }

  function showSimpleAlert(message) {
    var text = String(message || '').trim();
    text = text.replace(/^Erreur\s*:\s*/i, '');
    var normalized = text.toLowerCase();
    var finalMessage = text;

    if (normalized === 'catégorie déjà existante') {
      finalMessage = 'Impossible d\'ajouter cette catégorie : elle existe déjà.';
    } else if (normalized === 'image invalide') {
      finalMessage = 'Image invalide. Formats acceptés: JPG, JPEG, PNG, GIF, WEBP.';
    } else if (normalized === 'référence déjà existante') {
      finalMessage = 'Cette référence existe déjà.';
    } else if (normalized === 'id produit invalide') {
      finalMessage = 'ID produit invalide.';
    } else if (normalized === 'référence et désignation obligatoires') {
      finalMessage = 'Référence et désignation sont obligatoires.';
    } else if (normalized === 'catégorie invalide') {
      finalMessage = 'Veuillez sélectionner une catégorie existante.';
    } else if (normalized === 'le prix doit être positif') {
      finalMessage = 'Le prix doit être un nombre positif.';
    } else if (normalized === 'la quantité ne peut pas être négative') {
      finalMessage = 'La quantité doit être un entier supérieur ou égal à 0.';
    } else if (normalized === 'produit introuvable') {
      finalMessage = 'Produit introuvable.';
    } else if (normalized.indexOf('stock non nul') === 0) {
      finalMessage = 'Impossible de supprimer ce produit: le stock n\'est pas nul.';
    } else if (normalized === 'catégorie introuvable') {
      finalMessage = 'Catégorie introuvable.';
    } else if (normalized === 'suppression impossible, certains produits ont encore du stock') {
      finalMessage = 'Suppression impossible: certains produits de cette catégorie ont encore du stock.';
    } else if (normalized === 'suppression impossible pour cette catégorie') {
      finalMessage = 'Suppression impossible pour cette catégorie.';
    } else if (normalized === 'quantité invalide') {
      finalMessage = 'Quantité invalide. Entrez une valeur supérieure à 0.';
    } else if (normalized === 'stock insuffisant') {
      finalMessage = 'Stock insuffisant pour cette opération.';
    }

    alert(finalMessage);
  }

  if (editForm) {
    editForm.addEventListener('submit', function (event) {
      var idInput = document.getElementById('productEditId');
      var referenceInput = document.getElementById('productEditReference');
      var designationInput = document.getElementById('productEditDesignation');
      var prixInput = document.getElementById('productEditPrix');
      var quantiteInput = document.getElementById('productEditQuantite');
      var categoryInput = document.getElementById('productEditCategory');

      var productId = idInput ? parseInt(idInput.value, 10) : 0;
      var reference = referenceInput ? referenceInput.value.trim() : '';
      var designation = designationInput ? designationInput.value.trim() : '';
      var prix = prixInput ? parseFloat(prixInput.value) : NaN;
      var quantite = quantiteInput ? parseInt(quantiteInput.value, 10) : NaN;
      var categoryId = categoryInput ? parseInt(categoryInput.value, 10) : 0;

      if (!productId || productId <= 0) {
        event.preventDefault();
        showSimpleAlert('ID produit invalide.');
        return;
      }

      if (reference === '' || designation === '') {
        event.preventDefault();
        showSimpleAlert('Référence et désignation sont obligatoires.');
        return;
      }

      if (!categoryId || categoryId <= 0) {
        event.preventDefault();
        showSimpleAlert('Veuillez sélectionner une catégorie existante.');
        return;
      }

      if (!Number.isFinite(prix) || prix <= 0) {
        event.preventDefault();
        showSimpleAlert('Le prix doit être un nombre positif.');
        return;
      }

      if (!Number.isInteger(quantite) || quantite < 0) {
        event.preventDefault();
        showSimpleAlert('La quantité doit être un entier supérieur ou égal à 0.');
        return;
      }

      var normalizedReference = reference.toLowerCase();
      var existingRows = document.querySelectorAll('tr[data-id][data-reference]');
      for (var i = 0; i < existingRows.length; i++) {
        var row = existingRows[i];
        var rowId = parseInt(row.getAttribute('data-id') || '0', 10);
        var rowReference = (row.getAttribute('data-reference') || '').trim().toLowerCase();

        if (rowId !== productId && rowReference !== '' && rowReference === normalizedReference) {
          event.preventDefault();
          showSimpleAlert('Cette référence existe déjà.');
          return;
        }
      }
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      window.closeProductModal();
      window.closeProductEditModal();
    }
  });

  forms.forEach(function (form) {
    var searchInput = form.querySelector('[data-auto-submit="search"]');
    var sortSelect = form.querySelector('[data-auto-submit="sort"]');

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        window.clearTimeout(timers.get(searchInput));
        timers.set(searchInput, window.setTimeout(function () {
          submitForm(form);
        }, 300));
      });
    }

    if (sortSelect) {
      sortSelect.addEventListener('change', function () {
        submitForm(form);
      });
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restoreSearchFocus);
  } else {
    restoreSearchFocus();
  }

  <?php if ($message_type === 'error' && $message !== ''): ?>
  window.closeProductModal && window.closeProductModal();
  window.closeProductEditModal && window.closeProductEditModal();
  document.body.style.overflow = '';
  showSimpleAlert(<?php echo json_encode($message); ?>);
  <?php endif; ?>

})();
</script>
</html>
