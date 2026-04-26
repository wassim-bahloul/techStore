(function () {
  var phpDataEl = document.getElementById('php-data');
  var PHP_VARS = {
    skipSearchFocus: phpDataEl ? phpDataEl.getAttribute('data-skip-search-focus') === '1' : false,
    errorMessage: phpDataEl ? phpDataEl.getAttribute('data-error-message') || null : null
  };
  var forms = document.querySelectorAll('form[data-auto-submit-form="1"]');
  var timers = new WeakMap();
  var modal = document.getElementById('productModal');
  var editForm = document.getElementById('productEditForm');

  function restoreSearchFocus() {
    var shouldSkipSearchFocus = PHP_VARS.skipSearchFocus;
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

  if (PHP_VARS.errorMessage) {
    window.closeProductModal && window.closeProductModal();
    window.closeProductEditModal && window.closeProductEditModal();
    document.body.style.overflow = '';
    showSimpleAlert(PHP_VARS.errorMessage);
  }

})();