<?php
// ============================================================
//  XwéDò – Configuration générale
//  Fichier : config/config.php
// ============================================================

// --- Environnement -------------------------------------------
define('ENV', 'development');   // 'development' | 'production'
define('DEBUG', ENV === 'development');

// --- URL de base ---------------------------------------------
// En local : 'http://localhost/xwedo'
// En production : 'https://xwedo.bj'
define('BASE_URL', 'http://localhost/xwedo');

// --- Chemins absolus -----------------------------------------
define('ROOT_PATH',    dirname(__DIR__));          // /…/xwedo
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('UPLOADS_URL',  BASE_URL . '/public/uploads');

// --- Application ---------------------------------------------
define('APP_NAME',    'XwéDò');
define('APP_SLOGAN',  'Maison des Festivals Culturels du Bénin');
define('APP_EMAIL',   'contact@xwedo.bj');
define('APP_VERSION', '1.0.0');

// --- Sécurité ------------------------------------------------
define('HASH_COST', 12);           // bcrypt cost factor
define('TOKEN_LENGTH', 64);        // longueur des tokens (email, billet)
define('SESSION_LIFETIME', 7200);  // durée session en secondes (2h)

// --- Upload fichiers -----------------------------------------
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);  // 5 Mo
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_ALLOWED_EXT',   ['jpg', 'jpeg', 'png', 'webp']);

// --- Pagination ----------------------------------------------
define('FESTIVALS_PAR_PAGE', 12);
define('RESERVATIONS_PAR_PAGE', 20);

// --- Gestion des erreurs -------------------------------------
if (DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/erreurs.log');
}

// --- Timezone ------------------------------------------------
date_default_timezone_set('Africa/Porto-Novo');