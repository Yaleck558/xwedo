<?php
// ============================================================
//  XwéDò – Erreur 403 : Accès interdit
//  Fichier : errors/403.php
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

http_response_code(403);
$pageTitre = 'Accès interdit – XwéDò';
$pageDesc  = 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="erreur-page">
  <div class="erreur-inner">

    <div class="erreur-code" aria-hidden="true">403</div>

    <div class="erreur-deco" aria-hidden="true">
      <svg width="120" height="120" viewBox="0 0 120 120" fill="none" opacity=".12">
        <circle cx="60" cy="60" r="55" stroke="#C4622D" stroke-width="1.5"/>
        <circle cx="60" cy="60" r="30" stroke="#D4A853" stroke-width="1"/>
        <line x1="30" y1="30" x2="90" y2="90" stroke="#C4622D" stroke-width="2"/>
        <line x1="90" y1="30" x2="30" y2="90" stroke="#C4622D" stroke-width="2"/>
      </svg>
    </div>

    <h1 class="erreur-titre">Accès interdit</h1>
    <p class="erreur-msg">
      Vous n'avez pas les autorisations nécessaires pour accéder à cette page.<br>
      <?php if (!estConnecte()): ?>
        Connectez-vous pour continuer.
      <?php else: ?>
        Votre compte ne dispose pas des droits requis.
      <?php endif; ?>
    </p>

    <div class="erreur-actions">
      <?php if (!estConnecte()): ?>
        <a href="<?= url('login.php') ?>" class="btn-terra">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          <span>Se connecter</span>
        </a>
      <?php else: ?>
        <a href="<?= url('index.php') ?>" class="btn-terra">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          <span>Retour à l'accueil</span>
        </a>
      <?php endif; ?>
      <a href="javascript:history.back()" class="btn-link">← Revenir en arrière</a>
    </div>

  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>