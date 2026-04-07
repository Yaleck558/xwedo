<?php
// ============================================================
//  XwéDò – Erreur 500 : Erreur serveur
//  Fichier : errors/500.php
// ============================================================

// Chargement minimal — la BDD ou config peut être en cause
if (file_exists(dirname(__DIR__) . '/config/config.php')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
if (file_exists(dirname(__DIR__) . '/config/session.php')) {
    require_once dirname(__DIR__) . '/config/session.php';
}
if (file_exists(dirname(__DIR__) . '/includes/functions.php')) {
    require_once dirname(__DIR__) . '/includes/functions.php';
}

http_response_code(500);
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Erreur serveur – XwéDò</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,600;1,400&family=Outfit:wght@300;400;500&family=Yeseva+One&display=swap" rel="stylesheet">
  <?php if (defined('BASE_URL')): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
  <?php endif; ?>
  <style>
    /* Styles inline de secours si le CSS est inaccessible */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: #FAF6EE; color: #2A1F14; font-family: 'Outfit', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
  </style>
</head>
<body>
<section class="erreur-page">
  <div class="erreur-inner">

    <div class="erreur-code" aria-hidden="true">500</div>

    <div class="erreur-deco" aria-hidden="true">
      <svg width="120" height="120" viewBox="0 0 120 120" fill="none" opacity=".12">
        <circle cx="60" cy="60" r="55" stroke="#C4622D" stroke-width="1.5"/>
        <path d="M60 35 L60 65 M60 78 L60 80" stroke="#C4622D" stroke-width="4" stroke-linecap="round"/>
      </svg>
    </div>

    <h1 class="erreur-titre">Erreur serveur</h1>
    <p class="erreur-msg">
      Quelque chose s'est mal passé de notre côté.<br>
      Notre équipe a été informée. Revenez dans quelques instants.
    </p>

    <div class="erreur-actions">
      <a href="<?= $baseUrl ?>/index.php" class="btn-terra">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        <span>Retour à l'accueil</span>
      </a>
      <a href="javascript:location.reload()" class="btn-link">
        Réessayer
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
      </a>
    </div>

  </div>
</section>
</body>
</html>