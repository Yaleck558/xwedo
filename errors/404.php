<?php
// ============================================================
//  XwéDò – Erreur 404 : Page introuvable
//  Fichier : errors/404.php
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

http_response_code(404);
$pageTitre = 'Page introuvable – XwéDò';
$pageDesc  = 'La page que vous cherchez est introuvable.';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="erreur-page">
  <div class="erreur-inner">

    <div class="erreur-code" aria-hidden="true">404</div>

    <div class="erreur-deco" aria-hidden="true">
      <!-- Motif adinkra décoratif SVG -->
      <svg width="120" height="120" viewBox="0 0 120 120" fill="none" opacity=".12">
        <circle cx="60" cy="60" r="55" stroke="#C4622D" stroke-width="1.5"/>
        <circle cx="60" cy="60" r="38" stroke="#C4622D" stroke-width="1"/>
        <circle cx="60" cy="60" r="20" stroke="#D4A853" stroke-width="1"/>
        <line x1="5" y1="60" x2="115" y2="60" stroke="#C4622D" stroke-width="0.8"/>
        <line x1="60" y1="5" x2="60" y2="115" stroke="#C4622D" stroke-width="0.8"/>
        <line x1="20" y1="20" x2="100" y2="100" stroke="#C4622D" stroke-width="0.5"/>
        <line x1="100" y1="20" x2="20" y2="100" stroke="#C4622D" stroke-width="0.5"/>
      </svg>
    </div>

    <h1 class="erreur-titre">Page introuvable</h1>
    <p class="erreur-msg">
      La page que vous cherchez a peut-être été déplacée, supprimée ou n'a jamais existé.<br>
      Pas de panique — revenez à l'accueil pour continuer votre exploration.
    </p>

    <div class="erreur-actions">
      <a href="<?= url('index.php') ?>" class="btn-terra">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Retour à l'accueil</span>
      </a>
      <a href="<?= url('festivals.php') ?>" class="btn-link">
        Voir les festivals
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

    <div class="erreur-suggestions">
      <p class="erreur-sugg-titre">Vous cherchiez peut-être…</p>
      <div class="erreur-sugg-liens">
        <a href="<?= url('festivals.php?categorie=traditionnel-vodun') ?>">Vodun Days</a>
        <a href="<?= url('festivals.php?categorie=musique-concerts') ?>">Musique</a>
        <a href="<?= url('festivals.php?tri=populaire') ?>">Populaires</a>
        <a href="<?= url('register.php') ?>">S'inscrire</a>
      </div>
    </div>

  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>