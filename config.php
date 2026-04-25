<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'magasin_info');

// Store Configuration
define('NOM_MAGASIN', 'TechStore');
define('EMAIL_DISTRIBUTEUR', 'distributeur@magasin.tn');
define('FRAIS_LIVRAISON', 7.00);

// Upload Configuration
define('UPLOAD_DIR_URL', 'uploads/');
define('UPLOAD_DIR_FS', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_DIR', UPLOAD_DIR_URL);

// External Images Folder Configuration
define('IMAGES_DIR_URL', 'images/');
define('IMAGES_DIR_FS', __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR);

if (!is_dir(UPLOAD_DIR_FS)) {
    @mkdir(UPLOAD_DIR_FS, 0775, true);
}

// Create PDO Connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Database Connection Error: ' . $e->getMessage());
}
?>