<?php
// ============================================================
//  XwéDò – API Suggestions personnalisées
//  Fichier : api/suggestions.php
//  Génère et envoie des notifications de suggestions
//  À appeler via cron ou manuellement
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();

/**
 * Envoie une notification à un utilisateur
 * si elle n'a pas déjà été envoyée récemment
 */
function envoyerNotif(
    PDO $pdo,
    int $userId,
    string $titre,
    string $message,
    ?string $lien = null
): bool {
    // Vérifier qu'on n'a pas déjà envoyé cette notif dans les 7 jours
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE utilisateur_id = ?
          AND titre = ?
          AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$userId, $titre]);
    if ((int)$stmt->fetchColumn() > 0) return false;

    $pdo->prepare("
        INSERT INTO notifications (utilisateur_id, titre, message, lien)
        VALUES (?, ?, ?, ?)
    ")->execute([$userId, $titre, $message, $lien]);
    return true;
}

$envoyes = 0;
$users   = $pdo->query("SELECT id, prenom, ville FROM utilisateurs WHERE role = 'participant' AND actif = 1")->fetchAll();

foreach ($users as $user) {
    $uid    = $user['id'];
    $prenom = $user['prenom'];
    $ville  = $user['ville'];

    // ── 1. Suggestions par ville ─────────────────────────────
    if (!empty($ville)) {
        $stmt = $pdo->prepare("
            SELECT f.nom, f.slug, f.date_debut
            FROM festivals f
            WHERE f.ville = ?
              AND f.statut = 'publie'
              AND f.date_debut >= CURDATE()
              AND f.date_debut <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND f.id NOT IN (
                  SELECT festival_id FROM reservations WHERE utilisateur_id = ?
              )
            ORDER BY f.date_debut ASC
            LIMIT 1
        ");
        $stmt->execute([$ville, $uid]);
        $fest = $stmt->fetch();

        if ($fest) {
            $envoyes += (int) envoyerNotif(
                $pdo, $uid,
                '🎪 Festival près de chez toi !',
                $prenom . ', "' . $fest['nom'] . '" arrive bientôt à ' . $ville . '. Ne manque pas ça !',
                url('festival.php?slug=' . $fest['slug'])
            );
        }
    }

    // ── 2. Suggestions par catégorie favorite ────────────────
    $stmtCat = $pdo->prepare("
        SELECT c.id, c.nom, c.slug, c.icone, COUNT(*) AS nb
        FROM reservations r
        JOIN festivals f ON r.festival_id = f.id
        JOIN categories_festival c ON f.categorie_id = c.id
        WHERE r.utilisateur_id = ? AND r.statut = 'confirmee'
        GROUP BY c.id
        ORDER BY nb DESC
        LIMIT 1
    ");
    $stmtCat->execute([$uid]);
    $catFav = $stmtCat->fetch();

    if ($catFav) {
        $stmtFest = $pdo->prepare("
            SELECT f.nom, f.slug, f.date_debut
            FROM festivals f
            WHERE f.categorie_id = ?
              AND f.statut = 'publie'
              AND f.date_debut >= CURDATE()
              AND f.id NOT IN (
                  SELECT festival_id FROM reservations WHERE utilisateur_id = ?
              )
            ORDER BY f.date_debut ASC
            LIMIT 1
        ");
        $stmtFest->execute([$catFav['id'], $uid]);
        $suggest = $stmtFest->fetch();

        if ($suggest) {
            $envoyes += (int) envoyerNotif(
                $pdo, $uid,
                $catFav['icone'] . ' Suggestion pour toi',
                'Basé sur tes réservations, "' . $suggest['nom'] . '" pourrait t\'intéresser !',
                url('festival.php?slug=' . $suggest['slug'])
            );
        }
    }

    // ── 3. Rappel 3 jours avant un festival réservé ──────────
    $stmtRappel = $pdo->prepare("
        SELECT f.nom, f.slug, f.date_debut
        FROM reservations r
        JOIN festivals f ON r.festival_id = f.id
        WHERE r.utilisateur_id = ?
          AND r.statut = 'confirmee'
          AND f.date_debut = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ");
    $stmtRappel->execute([$uid]);
    $rappels = $stmtRappel->fetchAll();

    foreach ($rappels as $r) {
        $envoyes += (int) envoyerNotif(
            $pdo, $uid,
            '⏰ Dans 3 jours : ' . $r['nom'],
            'Rappel : "' . $r['nom'] . '" commence dans 3 jours. Prépare-toi !',
            url('festival.php?slug=' . $r['slug'])
        );
    }

    // ── 4. Alerte places limitées ────────────────────────────
    $stmtPlaces = $pdo->prepare("
        SELECT f.nom, f.slug, tb.quantite, tb.vendu
        FROM festivals f
        JOIN types_billet tb ON f.id = tb.festival_id
        WHERE f.statut = 'publie'
          AND f.date_debut >= CURDATE()
          AND tb.quantite IS NOT NULL
          AND (tb.quantite - tb.vendu) > 0
          AND (tb.quantite - tb.vendu) <= 20
          AND f.id NOT IN (
              SELECT festival_id FROM reservations WHERE utilisateur_id = ?
          )
        ORDER BY (tb.quantite - tb.vendu) ASC
        LIMIT 1
    ");
    $stmtPlaces->execute([$uid]);
    $limité = $stmtPlaces->fetch();

    if ($limité) {
        $restant = $limité['quantite'] - $limité['vendu'];
        $envoyes += (int) envoyerNotif(
            $pdo, $uid,
            '🔥 Dernières places disponibles !',
            'Il reste seulement ' . $restant . ' place(s) pour "' . $limité['nom'] . '". Dépêche-toi !',
            url('festival.php?slug=' . $limité['slug'])
        );
    }

    // ── 5. Nouveau festival publié (dans les 48h) ────────────
    $stmtNew = $pdo->prepare("
        SELECT f.nom, f.slug, c.icone, c.nom AS cat_nom
        FROM festivals f
        LEFT JOIN categories_festival c ON f.categorie_id = c.id
        WHERE f.statut = 'publie'
          AND f.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
          AND f.date_debut >= CURDATE()
        ORDER BY f.created_at DESC
        LIMIT 1
    ");
    $stmtNew->execute();
    $nouveau = $stmtNew->fetch();

    if ($nouveau) {
        $envoyes += (int) envoyerNotif(
            $pdo, $uid,
            '🆕 Nouveau festival : ' . $nouveau['nom'],
            '"' . $nouveau['nom'] . '" vient d\'être ajouté sur XwéDò. Découvre-le !',
            url('festival.php?slug=' . $nouveau['slug'])
        );
    }
}

echo json_encode([
    'ok'      => true,
    'envoyes' => $envoyes,
    'users'   => count($users),
]);