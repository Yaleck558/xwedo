<?php
// ============================================================
//  XwéDò – Connexion à la base de données (PDO)
//  Fichier : config/database.php
// ============================================================
define('DB_HOST', 'sql100.infinityfree.com');
define('DB_NAME', 'if0_41599391_xwedo');
define('DB_USER', 'if0_41599391');
define('DB_PASS', '~Pbv-L95Wg$C$hW');  // ← remplace ici
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO unique (singleton).
 * Lève une exception en cas d'échec de connexion.
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[XwéDò] Erreur BDD : ' . $e->getMessage());
            die(json_encode(['erreur' => 'Connexion à la base de données impossible.']));
        }
    }
    return $pdo;
}