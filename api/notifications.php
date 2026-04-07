<?php
// ============================================================
//  XwéDò – API Notifications temps réel
//  Fichier : api/notifications.php
//  Retourne les notifications non lues en JSON
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Doit être connecté
if (!estConnecte()) {
    echo json_encode(['ok' => false, 'nb' => 0, 'notifications' => []]);
    exit;
}

$pdo    = getDB();
$uid    = userId();
$action = $_GET['action'] ?? 'liste';

// ── Marquer une notif comme lue ──────────────────────────────
if ($action === 'lire' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $pdo->prepare('UPDATE notifications SET lue = 1 WHERE id = ? AND utilisateur_id = ?')
        ->execute([$id, $uid]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Marquer toutes comme lues ────────────────────────────────
if ($action === 'lire_tout') {
    $pdo->prepare('UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?')
        ->execute([$uid]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Récupérer les notifications récentes ────────────────────
$stmt = $pdo->prepare("
    SELECT id, titre, message, lien, lue, created_at
    FROM notifications
    WHERE utilisateur_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$uid]);
$notifs = $stmt->fetchAll();

$nbNonLues = 0;
$liste = [];

foreach ($notifs as $n) {
    if (!$n['lue']) $nbNonLues++;
    $liste[] = [
        'id'      => (int) $n['id'],
        'titre'   => $n['titre'],
        'message' => tronquer($n['message'], 80),
        'lien'    => $n['lien'],
        'lue'     => (bool) $n['lue'],
        'date'    => dateFormatFr(substr($n['created_at'], 0, 10)),
        'heure'   => substr($n['created_at'], 11, 5),
    ];
}

echo json_encode([
    'ok'            => true,
    'nb'            => $nbNonLues,
    'notifications' => $liste,
]);