<?php
require_once 'config/config.php';
require_once 'config/database.php';

$pdo = getDB();

$comptes = [
    [
        'prenom' => 'Kofi',
        'nom'    => 'Mensah',
        'email'  => 'orga@xwedo.bj',
        'mdp'    => 'Orga@2026',
        'role'   => 'organisateur',
    ],
    [
        'prenom' => 'Adélaïde',
        'nom'    => 'Dossou',
        'email'  => 'participant@xwedo.bj',
        'mdp'    => 'Part@2026',
        'role'   => 'participant',
    ],
];

$resultats = [];

foreach ($comptes as $c) {
    $hash = password_hash($c['mdp'], PASSWORD_BCRYPT, ['cost' => 12]);

    $existe = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? LIMIT 1");
    $existe->execute([$c['email']]);

    if ($existe->fetch()) {
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, role = ?, actif = 1 WHERE email = ?")
            ->execute([$hash, $c['role'], $c['email']]);
        $action = 'mis à jour';
    } else {
        $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, actif, email_verifie) VALUES (?,?,?,?,?,1,1)")
            ->execute([$c['nom'], $c['prenom'], $c['email'], $hash, $c['role']]);
        $action = 'créé';
    }

    $resultats[] = ['compte' => $c, 'action' => $action];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: sans-serif; background: #FAF6EE; display:flex; align-items:center; justify-content:center; min-height:100vh; padding:2rem; }
    .wrap { max-width: 560px; width:100%; }
    h2 { font-size:1.4rem; color:#1A6B3C; margin-bottom:1.5rem; text-align:center; }
    .card {
      background:#fff; border-radius:12px; padding:1.5rem;
      margin-bottom:1rem; box-shadow:0 4px 16px rgba(0,0,0,.08);
      border-left: 4px solid #C4622D;
    }
    .role { display:inline-block; padding:.25rem .8rem; border-radius:100px; font-size:.75rem; font-weight:600; text-transform:uppercase; margin-bottom:.8rem; }
    .role.admin       { background:#FDECEA; color:#8B1A1A; }
    .role.organisateur{ background:rgba(212,168,83,.2); color:#8B6914; }
    .role.participant  { background:rgba(122,140,110,.15); color:#3D5C30; }
    .row { display:flex; justify-content:space-between; padding:.4rem 0; border-bottom:1px solid #FAF6EE; font-size:.9rem; }
    .row:last-child { border-bottom:none; }
    .label { color:#888; }
    .value { font-weight:600; color:#C4622D; }
    .action { font-size:.75rem; color:#27AE60; margin-top:.5rem; }
    .btns { display:flex; gap:1rem; margin-top:1.5rem; flex-wrap:wrap; }
    .btn { flex:1; padding:.8rem; border-radius:100px; text-align:center; text-decoration:none; font-weight:500; font-size:.88rem; }
    .btn-admin { background:#C4622D; color:#fff; }
    .btn-orga  { background:#D4A853; color:#fff; }
    .btn-part  { background:#7A8C6E; color:#fff; }
    .warn { background:#FDECEA; border-radius:8px; padding:1rem; text-align:center; font-size:.8rem; color:#C0392B; margin-top:1rem; }
  </style>
</head>
<body>
<div class="wrap">
  <h2>✓ Comptes de test créés</h2>

  <!-- Admin (rappel) -->
  <div class="card">
    <span class="role admin">Admin</span>
    <div class="row"><span class="label">Email</span><span class="value">admin@xwedo.bj</span></div>
    <div class="row"><span class="label">Mot de passe</span><span class="value">Admin@2026</span></div>
  </div>

  <?php foreach ($resultats as $r): ?>
  <div class="card">
    <span class="role <?= $r['compte']['role'] ?>"><?= ucfirst($r['compte']['role']) ?></span>
    <div class="row"><span class="label">Nom</span><span class="value"><?= htmlspecialchars($r['compte']['prenom'] . ' ' . $r['compte']['nom']) ?></span></div>
    <div class="row"><span class="label">Email</span><span class="value"><?= htmlspecialchars($r['compte']['email']) ?></span></div>
    <div class="row"><span class="label">Mot de passe</span><span class="value"><?= htmlspecialchars($r['compte']['mdp']) ?></span></div>
    <p class="action">✓ Compte <?= $r['action'] ?> avec succès</p>
  </div>
  <?php endforeach; ?>

  <div class="btns">
    <a href="login.php" class="btn btn-admin">Connexion Admin</a>
    <a href="login.php" class="btn btn-orga">Connexion Orga</a>
    <a href="login.php" class="btn btn-part">Connexion Participant</a>
  </div>

  <div class="warn">
    ⚠️ Supprime ce fichier <strong>setup_comptes.php</strong> immédiatement après utilisation !
  </div>
</div>
</body>
</html>