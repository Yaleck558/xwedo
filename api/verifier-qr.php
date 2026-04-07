<?php
// ============================================================
//  XwéDò – API Vérification QR Code
//  Fichier : api/verifier-qr.php
//  Appelée par scanner.php lors du scan
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// Seuls les organisateurs et admins peuvent scanner
if (!estConnecte() || (!estOrganisateur() && !estAdmin())) {
    http_response_code(403);
    echo json_encode(['statut' => 'erreur', 'message' => 'Accès non autorisé.']);
    exit;
}

$pdo        = getDB();
$code       = trim($_POST['code'] ?? $_GET['code'] ?? '');
$festivalId = (int) ($_POST['festival_id'] ?? $_GET['festival_id'] ?? 0);
$lieuScan   = trim($_POST['lieu'] ?? 'Entrée principale');

if (empty($code)) {
    echo json_encode(['statut' => 'erreur', 'message' => 'Code billet manquant.']);
    exit;
}

// ── Rechercher la réservation ─────────────────────────────────
$stmt = $pdo->prepare("
    SELECT r.*,
           f.nom   AS festival_nom,
           f.date_debut, f.date_fin, f.id AS fid,
           u.prenom, u.nom AS user_nom, u.email,
           tb.nom  AS type_billet_nom
    FROM reservations r
    JOIN festivals f    ON r.festival_id    = f.id
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    JOIN types_billet tb ON r.type_billet_id = tb.id
    WHERE r.code_billet = ?
    LIMIT 1
");
$stmt->execute([$code]);
$resa = $stmt->fetch();

// ── Code introuvable ──────────────────────────────────────────
if (!$resa) {
    // Logger la tentative invalide
    $pdo->prepare("INSERT INTO logs_scan (reservation_id, festival_id, code_billet, statut, scanne_par, lieu_scan, ip_address) VALUES (0, ?, ?, 'invalide', ?, ?, ?)")
        ->execute([$festivalId, $code, userId(), $lieuScan, $_SERVER['REMOTE_ADDR'] ?? null]);

    echo json_encode([
        'statut'  => 'invalide',
        'couleur' => 'rouge',
        'icone'   => '✗',
        'titre'   => 'QR Code invalide',
        'message' => 'Ce code billet n\'existe pas dans notre système.',
        'code'    => $code,
    ]);
    exit;
}

// ── Vérifier que c'est bien pour ce festival ──────────────────
if ($festivalId > 0 && $resa['fid'] !== $festivalId) {
    echo json_encode([
        'statut'  => 'invalide',
        'couleur' => 'rouge',
        'icone'   => '✗',
        'titre'   => 'Mauvais festival',
        'message' => 'Ce billet est pour "' . $resa['festival_nom'] . '", pas pour ce festival.',
        'code'    => $code,
    ]);
    exit;
}

// ── Réservation annulée ───────────────────────────────────────
if ($resa['statut'] === 'annulee' || $resa['statut'] === 'remboursee') {
    echo json_encode([
        'statut'  => 'invalide',
        'couleur' => 'rouge',
        'icone'   => '✗',
        'titre'   => 'Billet annulé',
        'message' => 'Cette réservation a été annulée ou remboursée.',
        'participant' => $resa['prenom'] . ' ' . $resa['user_nom'],
        'code'    => $code,
    ]);
    exit;
}

// ── Déjà scanné ───────────────────────────────────────────────
if ($resa['scanne']) {
    $dateScan = $resa['date_scan'] ? date('d/m/Y à H:i', strtotime($resa['date_scan'])) : 'inconnu';

    // Logger la tentative de double scan
    $pdo->prepare("INSERT INTO logs_scan (reservation_id, festival_id, code_billet, statut, scanne_par, lieu_scan, ip_address) VALUES (?, ?, ?, 'deja_utilise', ?, ?, ?)")
        ->execute([$resa['id'], $resa['fid'], $code, userId(), $lieuScan, $_SERVER['REMOTE_ADDR'] ?? null]);

    echo json_encode([
        'statut'      => 'deja_utilise',
        'couleur'     => 'rouge',
        'icone'       => '✗',
        'titre'       => 'Billet déjà utilisé !',
        'message'     => 'Ce billet a déjà été scanné le ' . $dateScan . '. Refusez l\'entrée.',
        'participant' => $resa['prenom'] . ' ' . $resa['user_nom'],
        'festival'    => $resa['festival_nom'],
        'type_billet' => $resa['type_billet_nom'],
        'quantite'    => $resa['quantite'],
        'code'        => $code,
        'date_scan'   => $dateScan,
    ]);
    exit;
}

// ── VALIDE — Marquer comme scanné ─────────────────────────────
try {
    $pdo->beginTransaction();

    // Invalider le QR code
    $pdo->prepare("
        UPDATE reservations
        SET scanne      = 1,
            date_scan   = NOW(),
            scanne_par  = ?,
            lieu_scan   = ?
        WHERE id = ?
    ")->execute([userId(), $lieuScan, $resa['id']]);

    // Logger le scan valide
    $pdo->prepare("INSERT INTO logs_scan (reservation_id, festival_id, code_billet, statut, scanne_par, lieu_scan, ip_address) VALUES (?, ?, ?, 'valide', ?, ?, ?)")
        ->execute([$resa['id'], $resa['fid'], $code, userId(), $lieuScan, $_SERVER['REMOTE_ADDR'] ?? null]);

    $pdo->commit();

    echo json_encode([
        'statut'      => 'valide',
        'couleur'     => 'vert',
        'icone'       => '✓',
        'titre'       => 'Billet valide !',
        'message'     => 'Accès autorisé. Donnez le ticket à ' . $resa['prenom'] . '.',
        'participant' => $resa['prenom'] . ' ' . $resa['user_nom'],
        'email'       => $resa['email'],
        'festival'    => $resa['festival_nom'],
        'type_billet' => $resa['type_billet_nom'],
        'quantite'    => $resa['quantite'],
        'prix'        => number_format($resa['prix_total'], 0, ',', ' ') . ' FCFA',
        'code'        => $code,
        'scanne_a'    => date('H:i'),
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[XwéDò] Erreur scan : ' . $e->getMessage());
    echo json_encode([
        'statut'  => 'erreur',
        'couleur' => 'rouge',
        'icone'   => '!',
        'titre'   => 'Erreur système',
        'message' => 'Une erreur est survenue. Réessayez.',
    ]);
}