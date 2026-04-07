<?php
// ============================================================
//  XwéDò – Header global
//  Fichier : includes/header.php
//  Variables optionnelles avant l'include :
//    $pageTitre – titre de l'onglet
//    $pageDesc  – meta description
//    $pageOg    – image Open Graph
//    $pageCSS   – CSS spécifique à la page (string)
//    $pageJS    – JS spécifique à la page (dans footer.php)
// ============================================================

$pageTitre = $pageTitre ?? APP_NAME . ' – ' . APP_SLOGAN;
$pageDesc  = $pageDesc  ?? 'Découvrez et réservez vos billets pour les meilleurs festivals culturels du Bénin sur XwéDò.';
$pageOg    = $pageOg    ?? url('public/img/og-default.jpg');

// Notifications non lues
$nbNotifs = 0;
if (estConnecte()) {
    $stmtN = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0');
    $stmtN->execute([userId()]);
    $nbNotifs = (int) $stmtN->fetchColumn();
}

// ── Détection page active (PHP côté serveur) ─────────────────
$_pageCourante = basename($_SERVER['PHP_SELF']);

function navActif(string $fichier): string {
    global $_pageCourante;
    return $_pageCourante === $fichier ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitre) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <meta name="author" content="XwéDò">
  <meta property="og:title"       content="<?= e($pageTitre) ?>">
  <meta property="og:description" content="<?= e($pageDesc) ?>">
  <meta property="og:image"       content="<?= e($pageOg) ?>">
  <meta property="og:type"        content="website">
  <meta property="og:locale"      content="fr_BJ">
  <link rel="icon" type="image/png" href="<?= url('public/img/favicon.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400;1,600&family=Outfit:wght@200;300;400;500;600&family=Yeseva+One&display=swap" rel="stylesheet">

<style>
/* ============================================================
   VARIABLES & RESET
============================================================ */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --terracotta:       #C4622D;
  --terracotta-light: #E8895A;
  --terracotta-dark:  #A84E22;
  --soil:             #6B3A1F;
  --ochre:            #D4A853;
  --ochre-light:      #E8C97A;
  --sage:             #7A8C6E;
  --sage-light:       #A8BA9A;
  --cream:            #FAF6EE;
  --sand:             #EDE0C8;
  --sand-dark:        #DFD0B0;
  --deep-green:       #2D4A3E;
  --dusk:             #1C1410;
  --text:             #2A1F14;
  --text-mid:         #6B5442;
  --text-soft:        #A8937C;
  --white:            #FFFFFF;
  --radius-sm:        6px;
  --radius-md:        12px;
  --radius-lg:        20px;
  --radius-full:      100px;
  --shadow-sm:        0 2px 8px rgba(0,0,0,.06);
  --shadow-md:        0 6px 24px rgba(0,0,0,.09);
  --shadow-lg:        0 16px 48px rgba(0,0,0,.12);
  --transition:       .3s ease;
  --font-serif:       'Cormorant Garamond', serif;
  --font-sans:        'Outfit', sans-serif;
  --font-display:     'Yeseva One', serif;
}

html { scroll-behavior: smooth; }
body {
  background: var(--cream);
  color: var(--text);
  font-family: var(--font-sans);
  font-weight: 400;
  line-height: 1.6;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}
img { max-width: 100%; height: auto; display: block; }
a { text-decoration: none; color: inherit; }
ul { list-style: none; }
button { cursor: pointer; border: none; background: none; font-family: inherit; }
input, textarea, select { font-family: inherit; }

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: var(--sand); }
::-webkit-scrollbar-thumb { background: var(--terracotta); border-radius: 10px; }

/* ============================================================
   NAVIGATION
============================================================ */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 800;
  padding: 1.2rem 5%;
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 2rem;
  transition: all .5s ease;
}
nav.solid {
  background: rgba(250,246,238,.96);
  backdrop-filter: blur(22px);
  -webkit-backdrop-filter: blur(22px);
  border-bottom: 1px solid rgba(196,98,45,.12);
  box-shadow: 0 2px 30px rgba(0,0,0,.05);
}

/* Logo */
.nav-logo {
  font-family: var(--font-display);
  font-size: 1.65rem;
  color: var(--terracotta);
  position: relative; z-index: 2;
  white-space: nowrap;
}
.nav-logo::after {
  content: '•'; color: var(--ochre);
  margin-left: 3px; font-size: .65rem; vertical-align: super;
}

/* Centre — liens + recherche */
.nav-center-wrap {
  display: flex;
  align-items: center;
  gap: 0;
  background: rgba(250,246,238,.7);
  border: 1px solid rgba(196,98,45,.15);
  border-radius: var(--radius-full);
  padding: .35rem .35rem .35rem 1.4rem;
  backdrop-filter: blur(10px);
  transition: border-color var(--transition), box-shadow var(--transition);
}
nav.solid .nav-center-wrap {
  background: var(--white);
  box-shadow: var(--shadow-sm);
}
.nav-center-wrap:focus-within {
  border-color: var(--terracotta);
  box-shadow: 0 0 0 3px rgba(196,98,45,.08);
}

/* Liens de navigation */
.nav-links {
  display: flex;
  align-items: center;
  gap: 0;
  list-style: none;
}
.nav-links li { position: relative; }
.nav-links a {
  display: block;
  padding: .5rem 1.1rem;
  font-size: .78rem;
  font-weight: 400;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--text-mid);
  white-space: nowrap;
  transition: color var(--transition);
  position: relative;
}
.nav-links a::after {
  content: '';
  position: absolute;
  bottom: 0; left: 1.1rem; right: 1.1rem;
  height: 1.5px;
  background: var(--terracotta);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform var(--transition);
  border-radius: 1px;
}
.nav-links a:hover { color: var(--terracotta); }
.nav-links a:hover::after { transform: scaleX(1); }
.nav-links a.active { color: var(--terracotta); font-weight: 500; }
.nav-links a.active::after { transform: scaleX(1); }

/* Séparateur vertical entre liens et recherche */
.nav-sep {
  width: 1px;
  height: 20px;
  background: rgba(196,98,45,.18);
  margin: 0 .4rem;
  flex-shrink: 0;
}

/* Barre de recherche intégrée */
.nav-search {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex: 1;
  min-width: 180px;
}
.nav-search svg { color: var(--text-soft); flex-shrink: 0; }
.nav-search input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
  font-size: .82rem;
  font-family: var(--font-sans);
  color: var(--text);
  padding: .3rem 0;
  min-width: 120px;
}
.nav-search input::placeholder { color: var(--text-soft); }
.nav-search-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px; height: 32px;
  background: var(--terracotta);
  border-radius: var(--radius-full);
  color: var(--white);
  flex-shrink: 0;
  transition: background var(--transition), transform .2s;
  border: none; cursor: pointer;
}
.nav-search-btn:hover { background: var(--soil); transform: scale(1.05); }

/* Droite — notifs + compte */
.nav-right {
  display: flex;
  align-items: center;
  gap: 1rem;
  justify-content: flex-end;
}

/* Cloche notifs */
.nav-notif {
  position: relative;
  color: var(--text-mid);
  display: flex;
  align-items: center;
  transition: color var(--transition);
  padding: .4rem;
}
.nav-notif:hover { color: var(--terracotta); }
.nav-notif-badge {
  position: absolute; top: -2px; right: -2px;
  background: var(--terracotta); color: var(--white);
  font-size: .58rem; font-weight: 700;
  min-width: 16px; height: 16px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; padding: 0 3px;
  border: 1.5px solid var(--cream);
}

/* Bouton connexion */
.nav-login-btn {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .55rem 1.3rem;
  border: 1.5px solid rgba(196,98,45,.3);
  border-radius: var(--radius-full);
  font-size: .78rem;
  font-weight: 500;
  color: var(--terracotta);
  letter-spacing: .05em;
  transition: all var(--transition);
  white-space: nowrap;
}
.nav-login-btn:hover {
  background: var(--terracotta);
  color: var(--white);
  border-color: var(--terracotta);
}

/* Dropdown compte */
.nav-compte { position: relative; }
.nav-compte-btn {
  display: flex; align-items: center; gap: .6rem;
  border: 1.5px solid rgba(196,98,45,.2);
  border-radius: var(--radius-full);
  padding: .35rem .85rem .35rem .35rem;
  transition: border-color var(--transition), background var(--transition);
  cursor: pointer; background: none;
}
.nav-compte-btn:hover {
  border-color: var(--terracotta);
  background: rgba(196,98,45,.04);
}
.nav-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  object-fit: cover;
  display: flex; align-items: center; justify-content: center;
}
.nav-avatar-initiales {
  background: var(--terracotta); color: var(--white);
  font-size: .75rem; font-weight: 600;
  width: 28px; height: 28px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.nav-compte-nom {
  font-size: .8rem; color: var(--text-mid);
  max-width: 80px; overflow: hidden;
  text-overflow: ellipsis; white-space: nowrap;
}
.nav-dropdown {
  position: absolute; top: calc(100% + .8rem); right: 0;
  background: var(--white);
  border: 1px solid rgba(196,98,45,.12);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  min-width: 220px; padding: .5rem 0;
  opacity: 0; visibility: hidden;
  transform: translateY(-8px);
  transition: opacity .25s, transform .25s, visibility .25s;
  z-index: 900;
}
.nav-dropdown.open {
  opacity: 1; visibility: visible; transform: translateY(0);
}
.nav-dropdown-header {
  padding: .8rem 1.2rem 1rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
  margin-bottom: .4rem;
}
.nav-dropdown-header strong { display: block; font-size: .9rem; font-weight: 500; color: var(--text); }
.nav-dropdown-header span   { font-size: .75rem; color: var(--text-soft); }
.nav-dropdown a {
  display: block; padding: .55rem 1.2rem;
  font-size: .82rem; color: var(--text-mid);
  transition: background var(--transition), color var(--transition);
}
.nav-dropdown a:hover { background: rgba(196,98,45,.06); color: var(--terracotta); }
.nav-dropdown-sep { height: 1px; background: rgba(196,98,45,.08); margin: .4rem 0; }
.nav-dropdown-logout { color: #C0392B !important; }
.nav-dropdown-logout:hover { background: rgba(192,57,43,.06) !important; }

/* Burger mobile */
.nav-burger {
  display: none;
  flex-direction: column;
  justify-content: space-between;
  width: 22px; height: 16px;
  padding: 0; z-index: 2; cursor: pointer;
  background: none; border: none;
}
.nav-burger span {
  display: block; height: 1.5px;
  background: var(--text-mid); border-radius: 2px;
  transition: all .35s ease; transform-origin: center;
}
.nav-burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.nav-burger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.nav-burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* Menu mobile — TOUJOURS caché par défaut */
.nav-mobile-menu {
  display: none;
}

/* Overlay mobile */
.nav-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(28,20,16,.45); z-index: 700;
  backdrop-filter: blur(2px);
}
.nav-overlay.show { display: block; }

/* ============================================================
   RESPONSIVE — MOBILE (< 900px)
============================================================ */
@media (max-width: 900px) {

  /* Nav grid : logo | right */
  nav {
    grid-template-columns: auto 1fr;
    gap: .6rem;
    padding: .9rem 4%;
  }
  .nav-center-wrap { display: none !important; }
  .nav-burger      { display: flex; }
  .nav-right       { gap: .5rem; justify-content: flex-end; }
  .nav-login-btn   { padding: .5rem .9rem; font-size: .75rem; }
  .nav-compte-nom  { display: none; }
  .nav-compte-btn  { padding: .3rem .45rem .3rem .3rem; }

  /* Dropdown compte — masqué sur mobile (tout est dans le menu burger) */
  .nav-dropdown { display: none !important; }

  /* ── Menu mobile slide depuis la droite ── */
  .nav-mobile-menu {
    /* On le rend visible mais hors écran */
    display: flex !important;
    flex-direction: column;
    position: fixed;
    top: 0;
    right: 0;
    width: min(310px, 85vw);
    height: 100vh;
    background: var(--cream);
    z-index: 750;
    padding: 5rem 1.8rem 2rem;
    box-shadow: -4px 0 30px rgba(0,0,0,.15);
    overflow-y: auto;
    /* Caché via transform — plus fiable que right: -100% sur mobile */
    transform: translateX(100%);
    visibility: hidden;
    transition: transform .38s cubic-bezier(.16,1,.3,1),
                visibility .38s;
    will-change: transform;
  }
  /* Quand le burger est cliqué → open */
  .nav-mobile-menu.open {
    transform: translateX(0);
    visibility: visible;
  }

  /* Barre de recherche dans le menu mobile */
  .nav-mobile-search {
    display: flex; align-items: center; gap: .5rem;
    background: var(--white);
    border: 1.5px solid rgba(196,98,45,.18);
    border-radius: var(--radius-full);
    padding: .55rem .9rem;
    margin-bottom: 1.4rem;
  }
  .nav-mobile-search input {
    flex: 1; border: none; outline: none;
    font-size: .88rem; background: transparent; color: var(--text);
  }
  .nav-mobile-search input::placeholder { color: var(--text-soft); }

  /* Liens du menu */
  .nav-mobile-links {
    display: flex; flex-direction: column; gap: 0;
  }
  .nav-mobile-links a {
    display: block; padding: .9rem 0;
    font-size: .88rem; font-weight: 500;
    letter-spacing: .1em; text-transform: uppercase;
    color: var(--text-mid);
    border-bottom: 1px solid rgba(196,98,45,.08);
    transition: color var(--transition);
    text-decoration: none;
  }
  .nav-mobile-links a:hover,
  .nav-mobile-links a.active { color: var(--terracotta); }

  .nav-mobile-sep {
    height: 1px; background: rgba(196,98,45,.1);
    margin: 1.2rem 0;
  }
  .nav-mobile-bottom {
    display: flex; flex-direction: column;
    gap: .8rem; margin-top: auto; padding-top: 1.5rem;
  }
}

@media (max-width: 480px) {
  nav { padding: .8rem 4%; }
  .nav-logo { font-size: 1.35rem; }
  .nav-notif { padding: .25rem; }
}

/* ============================================================
   BOUTONS GLOBAUX
============================================================ */
.btn-terra {
  display: inline-flex; align-items: center; gap: 10px;
  padding: .9rem 2.2rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .82rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase;
  border-radius: var(--radius-full); border: none; cursor: pointer;
  transition: all var(--transition);
  position: relative; overflow: hidden;
}
.btn-terra::before {
  content: ''; position: absolute; inset: 0;
  background: var(--soil); border-radius: var(--radius-full);
  transform: scaleX(0); transform-origin: left;
  transition: transform .4s; z-index: 0;
}
.btn-terra:hover::before { transform: scaleX(1); }
.btn-terra > * { position: relative; z-index: 1; }
.btn-terra svg { transition: transform var(--transition); }
.btn-terra:hover svg { transform: translateX(4px); }

.btn-terra-outline {
  display: inline-flex; align-items: center; gap: 10px;
  padding: .85rem 2.2rem;
  background: transparent; color: var(--terracotta);
  font-size: .82rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase;
  border-radius: var(--radius-full);
  border: 1.5px solid var(--terracotta);
  cursor: pointer; transition: all var(--transition);
}
.btn-terra-outline:hover { background: var(--terracotta); color: var(--cream); }

.btn-link {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: .82rem; font-weight: 500;
  color: var(--text-mid); background: none; border: none; cursor: pointer;
  transition: color var(--transition);
}
.btn-link:hover { color: var(--terracotta); }
.btn-link svg { transition: transform var(--transition); }
.btn-link:hover svg { transform: translateX(4px); }

.btn-danger {
  display: inline-flex; align-items: center; gap: 8px;
  padding: .7rem 1.6rem;
  background: #C0392B; color: var(--white);
  font-size: .8rem; font-weight: 500;
  border-radius: var(--radius-full); border: none; cursor: pointer;
  transition: background var(--transition);
}
.btn-danger:hover { background: #96281B; }

/* ============================================================
   MESSAGES FLASH
============================================================ */
#flash-container {
  position: fixed; top: 5rem; right: 1.5rem;
  z-index: 1000;
  display: flex; flex-direction: column; gap: .6rem;
  max-width: 360px; width: calc(100% - 3rem);
  pointer-events: none;
}
.flash {
  display: flex; align-items: center;
  justify-content: space-between; gap: 1rem;
  padding: .9rem 1.2rem; border-radius: var(--radius-md);
  font-size: .85rem; box-shadow: var(--shadow-md);
  animation: flashIn .35s cubic-bezier(.16,1,.3,1) forwards;
  pointer-events: all;
}
@keyframes flashIn {
  from { opacity: 0; transform: translateX(20px); }
  to   { opacity: 1; transform: translateX(0); }
}
.flash-succes { background: #EDF7ED; color: #1E6A1E; border-left: 3px solid #2E8B2E; }
.flash-erreur { background: #FDECEA; color: #8B1A1A; border-left: 3px solid #C0392B; }
.flash-info   { background: #EBF4FB; color: #1A4D6E; border-left: 3px solid #2980B9; }
.flash-close {
  background: none; border: none; font-size: 1.1rem;
  cursor: pointer; opacity: .5; flex-shrink: 0; line-height: 1;
  transition: opacity var(--transition);
}
.flash-close:hover { opacity: 1; }

/* ============================================================
   FORMULAIRES PARTAGÉS
============================================================ */
.form-group { display: flex; flex-direction: column; gap: .5rem; margin-bottom: 1.4rem; }
.form-label {
  font-size: .78rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase; color: var(--text-mid);
}
.form-input, .form-select, .form-textarea {
  width: 100%; padding: .85rem 1.1rem;
  background: var(--white);
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-sm);
  font-size: .9rem; color: var(--text);
  transition: border-color var(--transition), box-shadow var(--transition);
  outline: none;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
  border-color: var(--terracotta);
  box-shadow: 0 0 0 3px rgba(196,98,45,.1);
}
.form-input::placeholder, .form-textarea::placeholder { color: var(--text-soft); }
.form-textarea { resize: vertical; min-height: 120px; }
.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' fill='none' stroke='%23A8937C' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 1rem center;
  padding-right: 2.5rem;
}
.form-error { font-size: .78rem; color: #C0392B; margin-top: .2rem; }
.form-hint  { font-size: .75rem; color: var(--text-soft); margin-top: .2rem; }

/* ============================================================
   BADGES & STATUTS
============================================================ */
.badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: .3rem .8rem; border-radius: var(--radius-full);
  font-size: .72rem; font-weight: 500;
  letter-spacing: .06em; text-transform: uppercase;
}
.badge-terra { background: rgba(196,98,45,.12);  color: var(--terracotta-dark); }
.badge-ochre { background: rgba(212,168,83,.15);  color: #8B6914; }
.badge-sage  { background: rgba(122,140,110,.12); color: #3D5C30; }
.badge-red   { background: rgba(192,57,43,.1);    color: #8B1A1A; }
.badge-blue  { background: rgba(41,128,185,.1);   color: #1A4D6E; }
.badge-green { background: rgba(39,174,96,.1);    color: #1A6B3C; }

.statut-dot {
  width: 6px; height: 6px; border-radius: 50%; display: inline-block;
  animation: pulseDot 2s ease-in-out infinite;
}
@keyframes pulseDot {
  0%,100% { opacity: 1; transform: scale(1); }
  50%     { opacity: .6; transform: scale(.7); }
}
.dot-vert   { background: #27AE60; }
.dot-orange { background: var(--terracotta); animation: none; }
.dot-gris   { background: var(--text-soft); animation: none; }

/* ============================================================
   PAGINATION
============================================================ */
.pagination {
  display: flex; align-items: center; justify-content: center;
  gap: .4rem; margin: 3rem 0 1rem; flex-wrap: wrap;
}
.pag-btn {
  display: flex; align-items: center; justify-content: center;
  width: 38px; height: 38px; border-radius: var(--radius-sm);
  font-size: .85rem; color: var(--text-mid);
  background: var(--white); border: 1px solid rgba(196,98,45,.15);
  transition: all var(--transition);
}
.pag-btn:hover { border-color: var(--terracotta); color: var(--terracotta); }
.pag-actif { background: var(--terracotta); color: var(--white) !important; border-color: var(--terracotta) !important; font-weight: 500; }
.pag-prev, .pag-next { width: auto; padding: 0 .9rem; }
.pag-dots { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; color: var(--text-soft); }

/* ============================================================
   PAGES D'ERREUR
============================================================ */
.erreur-page {
  min-height: 100vh; display: flex; align-items: center;
  justify-content: center; padding: 8rem 5% 4rem;
}
.erreur-inner { text-align: center; max-width: 560px; margin: 0 auto; }
.erreur-code {
  font-family: var(--font-display);
  font-size: clamp(7rem, 18vw, 12rem);
  color: var(--sand-dark); line-height: 1;
  letter-spacing: -.04em; margin-bottom: 1rem; user-select: none;
}
.erreur-deco { display: flex; justify-content: center; margin-bottom: 2rem; }
.erreur-titre {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 300; color: var(--dusk); margin-bottom: 1.2rem;
}
.erreur-msg {
  font-size: .95rem; color: var(--text-mid);
  line-height: 1.8; font-weight: 300; margin-bottom: 2.5rem;
}
.erreur-actions {
  display: flex; align-items: center; justify-content: center;
  gap: 2rem; flex-wrap: wrap; margin-bottom: 3rem;
}
.erreur-suggestions { border-top: 1px solid rgba(196,98,45,.12); padding-top: 2rem; }
.erreur-sugg-titre {
  font-size: .78rem; letter-spacing: .15em;
  text-transform: uppercase; color: var(--text-soft); margin-bottom: 1rem;
}
.erreur-sugg-liens {
  display: flex; align-items: center; justify-content: center;
  gap: .6rem; flex-wrap: wrap;
}
.erreur-sugg-liens a {
  padding: .5rem 1.2rem;
  border: 1px solid rgba(196,98,45,.2);
  border-radius: var(--radius-full);
  font-size: .8rem; color: var(--text-mid);
  transition: all var(--transition);
}
.erreur-sugg-liens a:hover {
  border-color: var(--terracotta); color: var(--terracotta);
  background: rgba(196,98,45,.04);
}

/* ============================================================
   UTILITAIRES
============================================================ */
.container    { max-width: 1280px; margin: 0 auto; padding: 0 5%; }
.container-sm { max-width: 860px;  margin: 0 auto; padding: 0 5%; }
.pt-nav { padding-top: 5.5rem; }

.section-pretitle { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.2rem; }
.pretitle-line { width: 40px; height: 1px; background: var(--terracotta); flex-shrink: 0; }
.pretitle-text {
  font-size: .72rem; letter-spacing: .2em;
  text-transform: uppercase; color: var(--terracotta); font-weight: 500;
}
.section-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 4vw, 3.2rem);
  font-weight: 300; line-height: 1.15; color: var(--dusk);
}
.section-title em { font-style: italic; color: var(--terracotta); }
.divider { height: 1px; background: rgba(196,98,45,.12); margin: 2rem 0; }

.reveal {
  opacity: 0; transform: translateY(30px);
  transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1);
}
.reveal.up { opacity: 1; transform: translateY(0); }

</style>

<?php if (!empty($pageCSS)) echo '<style>' . $pageCSS . '</style>'; ?>

</head>
<body>

<!-- ============================================================
     NAVIGATION
============================================================ -->
<nav id="nav" role="navigation" aria-label="Navigation principale">

  <!-- Logo -->
  <a href="<?= url('index.php') ?>" class="nav-logo">XwéDò</a>

  <!-- Centre : liens + séparateur + recherche -->
  <div class="nav-center-wrap">
    <ul class="nav-links">
      <li>
        <a href="<?= url('festivals.php') ?>" class="<?= navActif('festivals.php') ?>">
          Festivals
        </a>
      </li>
      <li>
        <a href="<?= url('calendrier.php') ?>" class="<?= navActif('calendrier.php') ?>">
          Calendrier
        </a>
      </li>
    </ul>

    <div class="nav-sep"></div>

    <form class="nav-search" method="GET" action="<?= url('festivals.php') ?>" role="search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input
        type="text"
        name="q"
        value="<?= e($_GET['q'] ?? '') ?>"
        placeholder="Rechercher un festival…"
        aria-label="Rechercher un festival"
        autocomplete="off"
      >
      <button type="submit" class="nav-search-btn" aria-label="Rechercher">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>
  </div>

  <!-- Droite : notifs + compte ou connexion -->
  <div class="nav-right">
    <?php if (estConnecte()): ?>

      <!-- Cloche notifications -->
      <a href="<?= url('profil.php?tab=notifications') ?>" class="nav-notif" aria-label="Notifications (<?= $nbNotifs ?> non lues)">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <?php if ($nbNotifs > 0): ?>
          <span class="nav-notif-badge"><?= $nbNotifs > 99 ? '99+' : $nbNotifs ?></span>
        <?php endif; ?>
      </a>

      <!-- Dropdown compte -->
      <div class="nav-compte">
        <button class="nav-compte-btn" id="compte-toggle" aria-haspopup="true" aria-expanded="false">
          <?php if (!empty($_SESSION['user_avatar'])): ?>
            <img src="<?= imgUrl($_SESSION['user_avatar']) ?>" alt="Avatar" class="nav-avatar">
          <?php else: ?>
            <span class="nav-avatar-initiales">
              <?= mb_strtoupper(mb_substr($_SESSION['user_nom'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8') ?>
            </span>
          <?php endif; ?>
          <span class="nav-compte-nom"><?= e(explode(' ', $_SESSION['user_nom'] ?? '')[0]) ?></span>
          <svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 4l4 4 4-4"/></svg>
        </button>

        <div class="nav-dropdown" id="compte-dropdown" role="menu">
          <div class="nav-dropdown-header">
            <strong><?= e($_SESSION['user_nom'] ?? '') ?></strong>
            <span><?= e($_SESSION['user_email'] ?? '') ?></span>
          </div>
          <a href="<?= url('profil.php') ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="display:inline;margin-right:6px;vertical-align:middle"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Mon profil
          </a>
          <a href="<?= url('profil.php?tab=reservations') ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" style="display:inline;margin-right:6px;vertical-align:middle"><path d="M2 9l3-3 3 3M2 15l3 3 3-3M13 6h8M13 12h8M13 18h8"/></svg>
            Mes réservations
          </a>

          <?php if (estOrganisateur()): ?>
            <div class="nav-dropdown-sep"></div>
            <a href="<?= url('organisateur/dashboard.php') ?>">Tableau de bord</a>
            <a href="<?= url('organisateur/creer-festival.php') ?>">Créer un festival</a>
          <?php endif; ?>

          <?php if (estAdmin()): ?>
            <div class="nav-dropdown-sep"></div>
            <a href="<?= url('admin/dashboard.php') ?>">Administration</a>
          <?php endif; ?>

          <div class="nav-dropdown-sep"></div>
          <a href="<?= url('logout.php') ?>" class="nav-dropdown-logout">Se déconnecter</a>
        </div>
      </div>

    <?php else: ?>
      <a href="<?= url('login.php') ?>" class="nav-login-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Se connecter
      </a>
    <?php endif; ?>

    <!-- Burger mobile -->
    <button class="nav-burger" id="nav-burger" aria-label="Ouvrir le menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>

</nav>

<!-- Menu mobile -->
<div class="nav-mobile-menu" id="nav-mobile-menu">
  <!-- Recherche mobile -->
  <form class="nav-mobile-search" method="GET" action="<?= url('festivals.php') ?>">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="q" placeholder="Rechercher un festival…" autocomplete="off">
  </form>

  <!-- Liens -->
  <nav class="nav-mobile-links">
    <a href="<?= url('festivals.php') ?>"  class="<?= navActif('festivals.php') ?>">Festivals</a>
    <a href="<?= url('calendrier.php') ?>" class="<?= navActif('calendrier.php') ?>">Calendrier</a>
    <?php if (estConnecte()): ?>
      <div class="nav-mobile-sep"></div>
      <a href="<?= url('profil.php') ?>">Mon profil</a>
      <a href="<?= url('profil.php?tab=reservations') ?>">Mes réservations</a>
      <?php if (estOrganisateur()): ?>
        <a href="<?= url('organisateur/dashboard.php') ?>">Tableau de bord</a>
      <?php endif; ?>
      <?php if (estAdmin()): ?>
        <a href="<?= url('admin/dashboard.php') ?>">Administration</a>
      <?php endif; ?>
    <?php endif; ?>
  </nav>

  <div class="nav-mobile-bottom">
    <?php if (estConnecte()): ?>
      <a href="<?= url('logout.php') ?>" class="btn-terra" style="width:100%; justify-content:center;">Se déconnecter</a>
    <?php else: ?>
      <a href="<?= url('login.php') ?>"   class="btn-terra-outline" style="width:100%; justify-content:center;">Se connecter</a>
      <a href="<?= url('register.php') ?>" class="btn-terra" style="width:100%; justify-content:center;">S'inscrire</a>
    <?php endif; ?>
  </div>
</div>

<!-- Overlay mobile -->
<div class="nav-overlay" id="nav-overlay"></div>

<!-- Flash messages -->
<div id="flash-container"><?= afficherFlash() ?></div>

<main id="main-content">