<?php
// ============================================================
//  XwéDò – À propos
//  Fichier : a-propos.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pdo = getDB();

// Stats pour la section chiffres
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM festivals WHERE statut='publie')              AS nb_festivals,
        (SELECT COUNT(*) FROM utilisateurs WHERE role='participant')        AS nb_participants,
        (SELECT COUNT(*) FROM reservations WHERE statut='confirmee')        AS nb_billets,
        (SELECT COUNT(DISTINCT ville) FROM festivals WHERE statut='publie') AS nb_villes
")->fetch();

$pageTitre = 'À propos – XwéDò';
$pageDesc  = 'Découvrez la mission de XwéDò, la maison numérique des festivals culturels du Bénin.';

$pageCSS = <<<CSS
/* ── À propos ──────────────────────────────────────────────── */
.apropos-page { padding-top: 5.5rem; }

/* Hero */
.apropos-hero {
  background: var(--dusk);
  min-height: 55vh;
  display: flex; align-items: center;
  position: relative; overflow: hidden;
  padding: 4rem 5%;
}
.apropos-hero::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0;
  height: 4px;
  background: repeating-linear-gradient(
    90deg,
    var(--terracotta) 0px, var(--terracotta) 18px,
    var(--ochre) 18px, var(--ochre) 32px,
    var(--sage) 32px, var(--sage) 46px,
    var(--terracotta-dark) 46px, var(--terracotta-dark) 60px
  );
}
.apropos-hero-arcs {
  position: absolute; bottom: -150px; right: -100px;
  pointer-events: none; opacity: .06;
}
.apropos-hero-inner {
  max-width: 1280px; margin: 0 auto; width: 100%;
  position: relative; z-index: 2;
}
.apropos-pretitle {
  display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;
}
.apropos-pretitle-line { width: 40px; height: 1px; background: var(--ochre); }
.apropos-pretitle-text {
  font-size: .72rem; letter-spacing: .2em;
  text-transform: uppercase; color: var(--ochre); font-weight: 500;
}
.apropos-hero-title {
  font-family: var(--font-serif);
  font-size: clamp(2.8rem, 6vw, 5rem);
  font-weight: 300; color: var(--cream);
  line-height: 1.1; margin-bottom: 1.5rem;
}
.apropos-hero-title em { font-style: italic; color: var(--ochre); }
.apropos-hero-desc {
  font-size: 1rem; color: rgba(250,246,238,.6);
  font-weight: 300; line-height: 1.8;
  max-width: 600px;
}

/* Section générique */
.apropos-section {
  max-width: 1280px; margin: 0 auto;
  padding: 5rem 5%;
}
.apropos-section-title {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 3.5vw, 2.8rem);
  font-weight: 300; color: var(--dusk);
  line-height: 1.15; margin-bottom: 1.2rem;
}
.apropos-section-title em { font-style: italic; color: var(--terracotta); }
.apropos-section-desc {
  font-size: .95rem; color: var(--text-mid);
  font-weight: 300; line-height: 1.9;
  max-width: 680px;
}

/* Nom XwéDò */
.nom-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 2rem; margin-top: 3rem;
}
.nom-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 2rem;
  box-shadow: var(--shadow-sm);
  text-align: center;
}
.nom-card-mot {
  font-family: var(--font-display);
  font-size: 3rem; color: var(--terracotta);
  margin-bottom: .5rem; display: block;
}
.nom-card-langue {
  font-size: .72rem; letter-spacing: .15em;
  text-transform: uppercase; color: var(--text-soft);
  margin-bottom: 1rem; display: block;
}
.nom-card-def {
  font-family: var(--font-serif);
  font-size: 1.2rem; font-weight: 300;
  font-style: italic; color: var(--dusk);
}

/* Chiffres */
.chiffres-section {
  background: var(--sand);
  padding: 5rem 5%;
}
.chiffres-inner {
  max-width: 1280px; margin: 0 auto;
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 2rem; text-align: center;
}
.chiffre-item {}
.chiffre-num {
  font-family: var(--font-serif);
  font-size: 3.5rem; font-weight: 600;
  color: var(--terracotta); line-height: 1;
  display: block; margin-bottom: .5rem;
}
.chiffre-label {
  font-size: .78rem; letter-spacing: .12em;
  text-transform: uppercase; color: var(--text-soft);
}

/* Valeurs */
.valeurs-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem; margin-top: 3rem;
}
.valeur-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.08);
  border-radius: var(--radius-lg);
  padding: 2rem;
  box-shadow: var(--shadow-sm);
  transition: transform var(--transition), box-shadow var(--transition);
}
.valeur-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.valeur-icon {
  font-size: 2rem; margin-bottom: 1rem; display: block;
}
.valeur-titre {
  font-family: var(--font-serif);
  font-size: 1.2rem; font-weight: 400;
  color: var(--dusk); margin-bottom: .6rem;
}
.valeur-desc {
  font-size: .88rem; color: var(--text-mid);
  font-weight: 300; line-height: 1.7;
}

/* Mission/Vision 2 colonnes */
.mv-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 2rem; margin-top: 3rem;
}
.mv-card {
  border-radius: var(--radius-lg);
  padding: 2.5rem;
}
.mv-card.mission { background: var(--terracotta); color: var(--cream); }
.mv-card.vision  { background: var(--dusk); color: var(--cream); }
.mv-label {
  font-size: .72rem; letter-spacing: .2em;
  text-transform: uppercase; opacity: .6;
  margin-bottom: 1rem; display: block;
}
.mv-titre {
  font-family: var(--font-serif);
  font-size: 1.6rem; font-weight: 300;
  margin-bottom: 1rem; line-height: 1.2;
}
.mv-desc {
  font-size: .9rem; line-height: 1.8;
  opacity: .8; font-weight: 300;
}

/* CTA bas de page */
.apropos-cta {
  background: var(--dusk); padding: 5rem 5%;
  text-align: center;
}
.apropos-cta-title {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 3.5vw, 2.8rem);
  font-weight: 300; color: var(--cream);
  margin-bottom: 1rem;
}
.apropos-cta-title em { font-style: italic; color: var(--ochre); }
.apropos-cta-desc {
  font-size: .95rem; color: rgba(250,246,238,.5);
  font-weight: 300; margin-bottom: 2.5rem;
}
.apropos-cta-btns {
  display: flex; gap: 1rem;
  align-items: center; justify-content: center; flex-wrap: wrap;
}

@media (max-width: 1024px) {
  .chiffres-inner { grid-template-columns: repeat(2, 1fr); }
  .valeurs-grid   { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
  .nom-grid { grid-template-columns: 1fr; }
  .mv-grid  { grid-template-columns: 1fr; }
  .chiffres-inner { grid-template-columns: repeat(2, 1fr); }
  .valeurs-grid { grid-template-columns: 1fr; }
}
CSS;

require_once 'includes/header.php';
?>

<div class="apropos-page">

  <!-- Hero -->
  <section class="apropos-hero">
    <svg class="apropos-hero-arcs" width="600" height="600" viewBox="0 0 600 600" fill="none" aria-hidden="true">
      <circle cx="600" cy="600" r="500" stroke="white" stroke-width="1"/>
      <circle cx="600" cy="600" r="380" stroke="white" stroke-width="1"/>
      <circle cx="600" cy="600" r="260" stroke="white" stroke-width="1"/>
    </svg>
    <div class="apropos-hero-inner">
      <div class="apropos-pretitle">
        <span class="apropos-pretitle-line"></span>
        <span class="apropos-pretitle-text">Notre histoire</span>
      </div>
      <h1 class="apropos-hero-title">
        La maison de tous<br>les <em>festivals</em> du Bénin
      </h1>
      <p class="apropos-hero-desc">
        XwéDò est né d'un constat simple : les festivals culturels du Bénin sont extraordinaires,
        mais personne ne sait où les trouver. Nous avons décidé de changer ça.
      </p>
    </div>
  </section>

  <!-- Le nom XwéDò -->
  <section class="apropos-section reveal">
    <div class="section-pretitle">
      <span class="pretitle-line"></span>
      <span class="pretitle-text">L'origine du nom</span>
    </div>
    <h2 class="apropos-section-title">Pourquoi <em>XwéDò</em> ?</h2>
    <p class="apropos-section-desc">
      Le nom XwéDò est composé de deux mots en langue Fon, langue parlée
      par les peuples du sud du Bénin, héritiers du Royaume du Dahomey.
    </p>
    <div class="nom-grid">
      <div class="nom-card reveal">
        <span class="nom-card-mot">Xwé</span>
        <span class="nom-card-langue">Langue Fon · Bénin</span>
        <p class="nom-card-def">"Maison" — symbole d'accueil, de chaleur et de centralité</p>
      </div>
      <div class="nom-card reveal">
        <span class="nom-card-mot">Dò</span>
        <span class="nom-card-langue">Langue Fon · Bénin</span>
        <p class="nom-card-def">"Lieu de rassemblement et de célébration culturelle"</p>
      </div>
    </div>
  </section>

  <!-- Chiffres -->
  <div class="chiffres-section" id="counter-strip">
    <div class="chiffres-inner">
      <div class="chiffre-item">
        <span class="chiffre-num counter-num" data-target="<?= $stats['nb_festivals'] ?>" data-suffix="+">0</span>
        <span class="chiffre-label">Festivals référencés</span>
      </div>
      <div class="chiffre-item">
        <span class="chiffre-num counter-num" data-target="<?= $stats['nb_participants'] ?>" data-suffix="+">0</span>
        <span class="chiffre-label">Membres inscrits</span>
      </div>
      <div class="chiffre-item">
        <span class="chiffre-num counter-num" data-target="<?= $stats['nb_billets'] ?>" data-suffix="+">0</span>
        <span class="chiffre-label">Billets réservés</span>
      </div>
      <div class="chiffre-item">
        <span class="chiffre-num counter-num" data-target="<?= $stats['nb_villes'] ?>" data-suffix="">0</span>
        <span class="chiffre-label">Villes du Bénin</span>
      </div>
    </div>
  </div>

  <!-- Mission & Vision -->
  <section class="apropos-section">
    <div class="section-pretitle">
      <span class="pretitle-line"></span>
      <span class="pretitle-text">Ce qui nous anime</span>
    </div>
    <h2 class="apropos-section-title">Notre <em>mission</em></h2>
    <div class="mv-grid">
      <div class="mv-card mission reveal">
        <span class="mv-label">Mission</span>
        <p class="mv-titre">Centraliser et valoriser la culture béninoise</p>
        <p class="mv-desc">
          Offrir à chaque festival béninois une vitrine numérique digne de ce nom,
          et à chaque citoyen un accès simple et fiable à la richesse culturelle de son pays.
        </p>
      </div>
      <div class="mv-card vision reveal">
        <span class="mv-label">Vision</span>
        <p class="mv-titre">Faire du Bénin la capitale culturelle de l'Afrique de l'Ouest</p>
        <p class="mv-desc">
          Nous croyons que la culture est le plus grand atout du Bénin.
          XwéDò veut être le pont numérique entre ce patrimoine unique
          et le monde entier.
        </p>
      </div>
    </div>
  </section>

  <!-- Nos valeurs -->
  <section style="background: var(--sand); padding: 5rem 5%;">
    <div style="max-width:1280px; margin:0 auto;">
      <div class="section-pretitle">
        <span class="pretitle-line"></span>
        <span class="pretitle-text">Ce en quoi nous croyons</span>
      </div>
      <h2 class="apropos-section-title">Nos <em>valeurs</em></h2>
      <div class="valeurs-grid">
        <div class="valeur-card reveal">
          <span class="valeur-icon">🥁</span>
          <h3 class="valeur-titre">Authenticité culturelle</h3>
          <p class="valeur-desc">
            Nous respectons et préservons l'authenticité des festivals béninois.
            Chaque événement est présenté avec fidélité à sa tradition et à son histoire.
          </p>
        </div>
        <div class="valeur-card reveal">
          <span class="valeur-icon">🔒</span>
          <h3 class="valeur-titre">Sécurité et confiance</h3>
          <p class="valeur-desc">
            Chaque billet est unique et sécurisé par QR code. Les organisateurs
            sont vérifiés. Votre argent et votre expérience sont protégés.
          </p>
        </div>
        <div class="valeur-card reveal">
          <span class="valeur-icon">🌍</span>
          <h3 class="valeur-titre">Accessibilité pour tous</h3>
          <p class="valeur-desc">
            Que vous soyez à Cotonou ou à Nikki, que vous ayez 20 ou 60 ans,
            XwéDò est conçu pour être simple, rapide et accessible à tous.
          </p>
        </div>
        <div class="valeur-card reveal">
          <span class="valeur-icon">📱</span>
          <h3 class="valeur-titre">Innovation au service de la culture</h3>
          <p class="valeur-desc">
            Nous utilisons la technologie non pas pour remplacer la culture,
            mais pour l'amplifier et la rendre visible au-delà des frontières.
          </p>
        </div>
        <div class="valeur-card reveal">
          <span class="valeur-icon">🤝</span>
          <h3 class="valeur-titre">Solidarité avec les organisateurs</h3>
          <p class="valeur-desc">
            Notre commission de 5% est transparente et juste.
            Nous grandissons avec nos organisateurs, pas à leurs dépens.
          </p>
        </div>
        <div class="valeur-card reveal">
          <span class="valeur-icon">🇧🇯</span>
          <h3 class="valeur-titre">Fierté béninoise</h3>
          <p class="valeur-desc">
            XwéDò est fait au Bénin, pour le Bénin et par des Béninois.
            Chaque ligne de code est une déclaration d'amour à notre pays.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="apropos-cta">
    <h2 class="apropos-cta-title">Rejoignez la <em>communauté</em></h2>
    <p class="apropos-cta-desc">
      Participant, organisateur ou simple curieux — XwéDò a une place pour vous.
    </p>
    <div class="apropos-cta-btns">
      <a href="<?= url('festivals.php') ?>" class="btn-terra">
        <span>Explorer les festivals</span>
      </a>
      <?php if (!estConnecte()): ?>
        <a href="<?= url('register.php') ?>" class="btn-terra-outline" style="color:var(--cream); border-color:rgba(250,246,238,.3);">
          Créer un compte
        </a>
      <?php endif; ?>
      <a href="<?= url('contact.php') ?>" class="btn-link" style="color:rgba(250,246,238,.5);">
        Nous contacter →
      </a>
    </div>
  </section>

</div>

<?php
$pageJS = <<<JS
// Compteurs animés
function animateCount(el) {
  const target = parseInt(el.dataset.target) || 0;
  const suffix = el.dataset.suffix || '';
  const dur = 2000;
  const start = performance.now();
  const update = now => {
    const p = Math.min((now - start) / dur, 1);
    const eased = 1 - Math.pow(1 - p, 4);
    const val = Math.round(eased * target);
    el.textContent = (val >= 1000 ? val.toLocaleString('fr-FR') : val) + suffix;
    if (p < 1) requestAnimationFrame(update);
  };
  requestAnimationFrame(update);
}
const cObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.querySelectorAll('.counter-num').forEach(animateCount);
      cObs.unobserve(e.target);
    }
  });
}, { threshold: 0.4 });
const strip = document.getElementById('counter-strip');
if (strip) cObs.observe(strip);
JS;
require_once 'includes/footer.php';
?>