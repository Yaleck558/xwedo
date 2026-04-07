<?php
// ============================================================
//  XwéDò – Gestion des sessions
//  Fichier : config/session.php
//  À inclure UNE SEULE FOIS, avant tout output HTML
// ============================================================

if (session_status() === PHP_SESSION_NONE) {

    // Paramètres de sécurité session
    ini_set('session.use_strict_mode',    '1');
    ini_set('session.use_only_cookies',   '1');
    ini_set('session.cookie_httponly',    '1');
    ini_set('session.cookie_samesite',    'Lax');
    // En production, activer : ini_set('session.cookie_secure', '1');

    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,  // true en HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('XWEDO_SESS');
    session_start();

    // Régénérer l'ID de session périodiquement (anti-fixation)
    if (!isset($_SESSION['_init'])) {
        session_regenerate_id(true);
        $_SESSION['_init']       = true;
        $_SESSION['_created_at'] = time();
    } elseif (time() - ($_SESSION['_created_at'] ?? 0) > 1800) {
        // Régénération toutes les 30 minutes
        session_regenerate_id(true);
        $_SESSION['_created_at'] = time();
    }
}

// ============================================================
// Fonctions utilitaires de session
// ============================================================

/** Vérifie si un utilisateur est connecté */
function estConnecte(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/** Retourne l'ID de l'utilisateur connecté, ou null */
function userId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/** Retourne le rôle de l'utilisateur connecté */
function userRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/** Vérifie si l'utilisateur a un rôle précis */
function estRole(string $role): bool
{
    return userRole() === $role;
}

/** Vérifie si l'utilisateur est admin */
function estAdmin(): bool
{
    return estRole('admin');
}

/** Vérifie si l'utilisateur est organisateur */
function estOrganisateur(): bool
{
    return estRole('organisateur');
}

/** Redirige vers login si non connecté */
function requireConnecte(string $redirect = '/login.php'): void
{
    if (!estConnecte()) {
        $base = defined('BASE_URL') ? BASE_URL : '';
        header('Location: ' . $base . $redirect);
        exit;
    }
}

/** Redirige si le rôle ne correspond pas */
function requireRole(string $role): void
{
    requireConnecte();
    if (!estRole($role)) {
        $base = defined('BASE_URL') ? BASE_URL : '';
        header('Location: ' . $base . '/index.php?erreur=acces_interdit');
        exit;
    }
}

/** Enregistre un message flash (affiché une seule fois) */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

/** Récupère et efface les messages flash */
function getFlash(string $type): array
{
    $msgs = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $msgs;
}

/** Détruit la session (déconnexion) */
function detruireSession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}