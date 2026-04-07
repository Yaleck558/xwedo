<?php
// ============================================================
//  XwéDò – Page d'accueil
//  Fichier : index.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pdo = getDB();

// ── Festival vedette (le plus récent publié à venir) ─────────
$vedette = $pdo->query("
    SELECT f.*, c.nom AS cat_nom, c.icone AS cat_icone,
           u.prenom AS org_prenom, u.nom AS org_nom,
           COUNT(DISTINCT r.id) AS nb_resa,
           MIN(tb.prix) AS prix_min
    FROM festivals f
    LEFT JOIN categories_festival c  ON f.categorie_id = c.id
    LEFT JOIN utilisateurs u         ON f.organisateur_id = u.id
    LEFT JOIN reservations r         ON f.id = r.festival_id AND r.statut = 'confirmee'
    LEFT JOIN types_billet tb        ON f.id = tb.festival_id AND tb.actif = 1
    WHERE f.statut = 'publie' AND f.date_fin >= CURDATE()
    GROUP BY f.id
    ORDER BY f.date_debut ASC
    LIMIT 1
")->fetch();

// ── Festivals en vedette (6 prochains) ───────────────────────
$festivals = $pdo->query("
    SELECT f.*, c.nom AS cat_nom, c.icone AS cat_icone,
           COUNT(DISTINCT r.id) AS nb_resa,
           MIN(tb.prix) AS prix_min
    FROM festivals f
    LEFT JOIN categories_festival c  ON f.categorie_id = c.id
    LEFT JOIN reservations r         ON f.id = r.festival_id AND r.statut = 'confirmee'
    LEFT JOIN types_billet tb        ON f.id = tb.festival_id AND tb.actif = 1
    WHERE f.statut = 'publie' AND f.date_fin >= CURDATE()
    GROUP BY f.id
    ORDER BY f.date_debut ASC
    LIMIT 6
")->fetchAll();

// ── Festivals populaires (top 3 par réservations) ───────────
$populaires = $pdo->query("
    SELECT f.*, COUNT(r.id) AS nb_resa, MIN(tb.prix) AS prix_min
    FROM festivals f
    LEFT JOIN reservations r  ON f.id = r.festival_id AND r.statut = 'confirmee'
    LEFT JOIN types_billet tb ON f.id = tb.festival_id AND tb.actif = 1
    WHERE f.statut = 'publie'
    GROUP BY f.id
    ORDER BY nb_resa DESC
    LIMIT 3
")->fetchAll();

// ── Catégories avec comptage ─────────────────────────────────
$categories = $pdo->query("
    SELECT c.*, COUNT(f.id) AS nb_festivals
    FROM categories_festival c
    LEFT JOIN festivals f ON c.id = f.categorie_id AND f.statut = 'publie'
    GROUP BY c.id
    ORDER BY nb_festivals DESC
")->fetchAll();

// ── Stats globales ────────────────────────────────────────────
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM festivals WHERE statut='publie')                   AS nb_festivals,
        (SELECT COUNT(*) FROM utilisateurs WHERE role='participant')             AS nb_participants,
        (SELECT COUNT(*) FROM reservations WHERE statut='confirmee')             AS nb_billets,
        (SELECT COUNT(DISTINCT ville) FROM festivals WHERE statut='publie' AND ville IS NOT NULL) AS nb_villes
")->fetch();

// ── Prochains festivals pour le calendrier (30 jours) ────────
$calendrier = $pdo->query("
    SELECT f.nom, f.slug, f.date_debut, f.date_fin,
           f.lieu, f.ville, f.image_principale,
           c.nom AS cat_nom, c.icone AS cat_icone
    FROM festivals f
    LEFT JOIN categories_festival c ON f.categorie_id = c.id
    WHERE f.statut = 'publie'
      AND f.date_debut >= CURDATE()
      AND f.date_debut <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY f.date_debut ASC
    LIMIT 5
")->fetchAll();

$pageTitre = APP_NAME . ' – ' . APP_SLOGAN;
$pageDesc  = 'Découvrez et réservez vos billets pour les meilleurs festivals culturels du Bénin : Vodun Days, WeLovEya, FInAB, FITHEB et bien plus sur XwéDò.';

$pageCSS = <<<CSS
/* ============================================================
   INDEX – Page d'accueil
============================================================ */

/* ── HERO ─────────────────────────────────────────────────── */
.hero {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 55% 45%;
  position: relative;
  overflow: hidden;
}

/* Côté gauche */
.hero-left {
  background: var(--sand);
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 10rem 5% 6rem 8%;
  overflow: hidden;
}
.arc {
  position: absolute;
  border-radius: 50%;
  border: 1px solid;
  pointer-events: none;
}
.arc-1 { width: 500px; height: 500px; border-color: rgba(196,98,45,.1);  bottom: -220px; left: -200px; }
.arc-2 { width: 280px; height: 280px; border-color: rgba(212,168,83,.2);  bottom: -100px; left: -60px; }
.arc-3 { width: 650px; height: 650px; border-color: rgba(196,98,45,.05); top: -260px; right: -180px; }

.hero-pretitle {
  display: flex; align-items: center; gap: 1.2rem;
  margin-bottom: 3rem;
  opacity: 0; animation: fadeUp 1s .3s ease forwards;
}
.pretitle-line-hero { width: 50px; height: 1px; background: var(--terracotta); }
.pretitle-text-hero {
  font-size: .75rem; letter-spacing: .2em;
  text-transform: uppercase; color: var(--terracotta); font-weight: 500;
}

.hero-h1 {
  font-family: var(--font-serif);
  font-size: clamp(4rem, 7vw, 7.5rem);
  font-weight: 300; line-height: 1.05;
  letter-spacing: -.01em; color: var(--dusk);
}
.hero-h1 .l1 {
  display: block;
  opacity: 0; transform: translateY(40px);
  animation: riseUp 1s .5s cubic-bezier(.16,1,.3,1) forwards;
}
.hero-h1 .l2 {
  display: block; font-style: italic; color: var(--terracotta);
  opacity: 0; transform: translateY(40px);
  animation: riseUp 1s .7s cubic-bezier(.16,1,.3,1) forwards;
}
.hero-h1 .l3 {
  display: block;
  opacity: 0; transform: translateY(40px);
  animation: riseUp 1s .9s cubic-bezier(.16,1,.3,1) forwards;
}
@keyframes riseUp  { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp  { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }

.hero-desc {
  margin-top: 1 rem;
  font-size: 1rem; line-height: 1.8;
  color: var(--text-mid); max-width: 400px; font-weight: 300;
  opacity: 0; animation: fadeUp 1s 1.2s ease forwards;
}

.hero-actions {
  margin-top: 3.5rem;
  display: flex; align-items: center; gap: 2rem;
  opacity: 0; animation: fadeUp 1s 1.5s ease forwards;
  flex-wrap: wrap;
}

.hero-stats {
  margin-top: 5rem;
  display: flex; gap: 3rem;
  opacity: 0; animation: fadeUp 1s 1.8s ease forwards;
  flex-wrap: wrap;
}
.hs-num {
  font-family: var(--font-serif);
  font-size: 2.5rem; font-weight: 600;
  color: var(--terracotta); line-height: 1; display: block;
}
.hs-label {
  font-size: .72rem; letter-spacing: .1em;
  text-transform: uppercase; color: var(--text-soft);
  margin-top: 4px; display: block;
}

/* Côté droit — festival vedette */
.hero-right {
  position: relative; overflow: hidden;
  display: flex; flex-direction: column;
  background: var(--dusk);
}
.hero-img-collage { position: absolute; inset: 0; overflow: hidden; }
.hero-img-main {
  position: absolute; inset: 0;
  background-size: cover; background-position: center;
  opacity: .35; transition: opacity 1.5s ease;
}
.hero-img-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(28,20,16,.9) 0%, rgba(28,20,16,.2) 60%, transparent 100%);
}

.spinning-badge {
  position: absolute; top: 7rem; right: 3.5rem;
  width: 110px; height: 110px;
  animation: rotateSpin 20s linear infinite; z-index: 5;
}
.spinning-badge svg { width: 100%; height: 100%; }
.spinning-badge-inner {
  position: absolute; inset: 28px;
  background: var(--ochre); border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
}
@keyframes rotateSpin { from { transform: rotate(0); } to { transform: rotate(360deg); } }

.feat-card-main {
  flex: 1; display: flex; flex-direction: column;
  justify-content: flex-end; padding: 5rem 4rem 3.5rem;
  position: relative; z-index: 2;
}
.feat-tag {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: .72rem; letter-spacing: .18em; text-transform: uppercase;
  color: var(--ochre); margin-bottom: 1.5rem;
}
.feat-tag-dot {
  width: 6px; height: 6px; background: var(--terracotta-light);
  border-radius: 50%; animation: pulseDot 2s ease-in-out infinite;
}
@keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1);} 50%{opacity:.6;transform:scale(.7);} }

.feat-name {
  font-family: var(--font-serif);
  font-weight: 300; font-style: italic;
  font-size: clamp(2.5rem, 4.5vw, 4.5rem);
  line-height: 1; color: var(--cream); margin-bottom: 1.5rem;
}
.feat-info { display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
.feat-detail {
  font-size: .78rem; font-weight: 300;
  color: rgba(250,246,238,.55);
  display: flex; align-items: center; gap: 6px;
}
.feat-visitors {
  margin-top: 1.5rem;
  display: inline-flex; align-items: center; gap: 10px;
  background: rgba(250,246,238,.08);
  border: 1px solid rgba(250,246,238,.15);
  border-radius: var(--radius-full); padding: .5rem 1.2rem;
}
.feat-visitors-num {
  font-family: var(--font-serif);
  font-size: 1.2rem; font-weight: 600; color: var(--ochre);
}
.feat-visitors-label { font-size: .68rem; letter-spacing: .1em; text-transform: uppercase; color: rgba(250,246,238,.5); }

.feat-bottom {
  border-top: 1px solid rgba(250,246,238,.08);
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.8rem 4rem; position: relative; z-index: 2;
}
.feat-nav-dots { display: flex; gap: .5rem; }
.feat-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: rgba(250,246,238,.3); cursor: pointer;
  transition: all .3s; border: none;
}
.feat-dot.active { background: var(--ochre); width: 22px; border-radius: 3px; }
.feat-link {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: .78rem; font-weight: 400; letter-spacing: .06em;
  color: rgba(250,246,238,.6); transition: color var(--transition);
}
.feat-link:hover { color: var(--ochre); }
.feat-link svg { transition: transform var(--transition); }
.feat-link:hover svg { transform: translateX(4px); }

/* ── BANDE KENTE ───────────────────────────────────────────── */
.kente-strip {
  height: 8px;
  background: repeating-linear-gradient(
    90deg,
    var(--terracotta)        0px,  var(--terracotta)      20px,
    var(--ochre)             20px, var(--ochre)           36px,
    var(--sage)              36px, var(--sage)            50px,
    var(--terracotta-dark)   50px, var(--terracotta-dark) 66px,
    var(--ochre-light)       66px, var(--ochre-light)     78px,
    var(--deep-green)        78px, var(--deep-green)      90px
  );
}

/* ── COUNTER STRIP ─────────────────────────────────────────── */
.counter-strip {
  background: var(--dusk);
 
}
.counter-inner {
  max-width: 1280px; margin: 0 auto;
  display: grid; grid-template-columns: repeat(4, 1fr);
  gap: 2rem; text-align: center;
}
.counter-item {}
.counter-num {
  font-family: var(--font-serif);
  font-size: 3.2rem; font-weight: 600;
  color: var(--ochre); line-height: 1; display: block;
}
.counter-label {
  font-size: .72rem; letter-spacing: .18em;
  text-transform: uppercase; color: rgba(250,246,238,.4);

}

/* ── FESTIVALS À LA UNE ────────────────────────────────────── */
.section-festivals {
  padding: 6rem 5%;
  max-width: 1280px; margin: 0 auto;
}
.section-head {
  display: flex; align-items: flex-end;
  justify-content: space-between; gap: 1.5rem;
  margin-bottom: 3rem; flex-wrap: wrap;
}

.festivals-home-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.8rem;
}

/* Card festival home */
.fest-card {
  background: var(--white);
  border-radius: var(--radius-lg); overflow: hidden;
  border: 1px solid rgba(196,98,45,.08);
  box-shadow: var(--shadow-sm);
  transition: transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s;
  display: flex; flex-direction: column;
}
.fest-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); }
.fest-card-img { position: relative; height: 200px; overflow: hidden; }
.fest-card-img img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .6s cubic-bezier(.16,1,.3,1);
}
.fest-card:hover .fest-card-img img { transform: scale(1.06); }
.fest-card-img-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(28,20,16,.6) 0%, transparent 55%);
}
.fest-card-cat {
  position: absolute; top: 1rem; left: 1rem;
  padding: .28rem .75rem;
  background: rgba(250,246,238,.92);
  border-radius: var(--radius-full);
  font-size: .68rem; font-weight: 500;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--terracotta);
}
.fest-card-statut { position: absolute; top: 1rem; right: 1rem; }
.fest-card-body { padding: 1.4rem 1.6rem; flex: 1; display: flex; flex-direction: column; }
.fest-card-date {
  font-size: .72rem; letter-spacing: .1em;
  text-transform: uppercase; color: var(--terracotta);
  font-weight: 500; margin-bottom: .5rem;
}
.fest-card-name {
  font-family: var(--font-serif);
  font-size: 1.35rem; font-weight: 400;
  color: var(--dusk); line-height: 1.2; margin-bottom: .45rem;
}
.fest-card-name a { transition: color var(--transition); }
.fest-card-name a:hover { color: var(--terracotta); }
.fest-card-lieu {
  display: flex; align-items: center; gap: 5px;
  font-size: .78rem; color: var(--text-soft);
  font-weight: 300; margin-bottom: 1rem;
}
.fest-card-desc {
  font-size: .82rem; color: var(--text-mid);
  font-weight: 300; line-height: 1.7; flex: 1; margin-bottom: 1rem;
}
.fest-card-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding-top: 1rem; border-top: 1px solid rgba(196,98,45,.08);
}
.fest-card-prix {
  font-family: var(--font-serif);
  font-size: 1.05rem; font-weight: 600; color: var(--terracotta);
}
.fest-card-prix small {
  font-family: var(--font-sans); font-size: .68rem;
  font-weight: 400; color: var(--text-soft); display: block;
}
.fest-card-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: .5rem 1.1rem;
  background: var(--terracotta); color: var(--white);
  font-size: .75rem; font-weight: 500;
  border-radius: var(--radius-full);
  transition: background var(--transition);
}
.fest-card-btn:hover { background: var(--soil); }

/* ── CATÉGORIES ────────────────────────────────────────────── */
.section-categories {
  background: var(--sand);
  padding: 5rem 5%;
}
.section-categories-inner { max-width: 1280px; margin: 0 auto; }
.cat-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 1rem; margin-top: 2.5rem;
}
.cat-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.8rem 1rem;
  text-align: center; cursor: pointer;
  transition: all .3s cubic-bezier(.16,1,.3,1);
  text-decoration: none; display: block;
}
.cat-card:hover {
  background: var(--terracotta);
  border-color: var(--terracotta);
  transform: translateY(-4px);
  box-shadow: var(--shadow-md);
}
.cat-card-icon { font-size: 2rem; margin-bottom: .7rem; display: block; }
.cat-card-nom {
  font-size: .82rem; font-weight: 500; color: var(--text);
  transition: color .3s;
}
.cat-card:hover .cat-card-nom { color: var(--cream); }
.cat-card-nb {
  font-size: .7rem; color: var(--text-soft);
  margin-top: .3rem; transition: color .3s;
}
.cat-card:hover .cat-card-nb { color: rgba(250,246,238,.65); }

/* ── POPULAIRES ────────────────────────────────────────────── */
.section-populaires {
  padding: 6rem 5%;
  max-width: 1280px; margin: 0 auto;
}
.pop-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 2.5rem; }
.pop-card {
  position: relative; border-radius: var(--radius-lg);
  overflow: hidden; aspect-ratio: 3/4;
  cursor: pointer;
}
.pop-card img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .6s cubic-bezier(.16,1,.3,1);
}
.pop-card:hover img { transform: scale(1.07); }
.pop-card-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(28,20,16,.85) 0%, rgba(28,20,16,.1) 60%, transparent 100%);
}
.pop-card-content {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 2rem 1.5rem;
}
.pop-rank {
  font-family: var(--font-serif);
  font-size: 3rem; font-weight: 600;
  color: rgba(212,168,83,.3); line-height: 1;
  margin-bottom: .3rem;
}
.pop-nom {
  font-family: var(--font-serif);
  font-size: 1.3rem; font-weight: 300; font-style: italic;
  color: var(--cream); line-height: 1.2; margin-bottom: .5rem;
}
.pop-resa {
  font-size: .75rem; color: rgba(250,246,238,.55);
  display: flex; align-items: center; gap: 5px;
}

/* ── CALENDRIER ────────────────────────────────────────────── */
.section-calendrier {
  background: var(--dusk);
  padding: 5rem 5%;
}
.section-calendrier-inner { max-width: 1280px; margin: 0 auto; }
.section-calendrier .section-title,
.section-calendrier .pretitle-text { color: var(--cream); }
.section-calendrier .section-title em { color: var(--ochre); }
.section-calendrier .pretitle-line { background: var(--ochre); }

.cal-list { display: flex; flex-direction: column; gap: 0; margin-top: 2.5rem; }
.cal-event {
  display: grid;
  grid-template-columns: 100px 1fr auto;
  gap: 2rem; align-items: center;
  padding: 1.4rem 1.8rem;
  border-left: 3px solid transparent;
  border-bottom: 1px solid rgba(250,246,238,.06);
  transition: all var(--transition); cursor: pointer;
  text-decoration: none; color: inherit;
}
.cal-event:last-child { border-bottom: none; }
.cal-event:hover {
  border-left-color: var(--ochre);
  background: rgba(250,246,238,.03);
}
.cal-date {
  text-align: center;
}
.cal-date-day {
  font-family: var(--font-serif);
  font-size: 2.2rem; font-weight: 600;
  color: var(--ochre); line-height: 1;
}
.cal-date-mois {
  font-size: .72rem; letter-spacing: .12em;
  text-transform: uppercase; color: rgba(250,246,238,.4);
  margin-top: 2px;
}
.cal-info {}
.cal-nom {
  font-family: var(--font-serif);
  font-size: 1.2rem; font-weight: 300; font-style: italic;
  color: var(--cream); margin-bottom: .3rem;
}
.cal-meta {
  display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;
}
.cal-meta-item {
  font-size: .75rem; color: rgba(250,246,238,.45);
  display: flex; align-items: center; gap: 5px;
}
.cal-cat {
  display: inline-flex; align-items: center; gap: 5px;
  padding: .25rem .7rem;
  background: rgba(212,168,83,.12);
  border-radius: var(--radius-full);
  font-size: .68rem; color: var(--ochre);
}
.cal-btn {
  padding: .55rem 1.3rem;
  border: 1px solid rgba(250,246,238,.15);
  border-radius: var(--radius-full);
  font-size: .75rem; color: rgba(250,246,238,.55);
  background: none; transition: all var(--transition); white-space: nowrap;
}
.cal-event:hover .cal-btn {
  border-color: var(--ochre); color: var(--ochre);
}

/* ── CTA INSCRIPTION ───────────────────────────────────────── */
.section-cta {
  padding: 6rem 5%;
  background: var(--sand);
}
.cta-inner {
  max-width: 1280px; margin: 0 auto;
  display: grid; grid-template-columns: 1fr 480px;
  gap: 4rem; align-items: center;
}
.cta-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 4vw, 3.2rem);
  font-weight: 300; color: var(--dusk); line-height: 1.15;
  margin-bottom: 1.2rem;
}
.cta-title em { font-style: italic; color: var(--terracotta); }
.cta-desc {
  font-size: .95rem; color: var(--text-mid);
  font-weight: 300; line-height: 1.8; margin-bottom: 2rem;
}
.cta-features { display: flex; flex-direction: column; gap: .8rem; }
.cta-feature {
  display: flex; align-items: center; gap: .8rem;
  font-size: .88rem; color: var(--text-mid);
}
.cta-feature-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--terracotta); flex-shrink: 0;
}

/* Formulaire alerte */
.cta-form-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 2.4rem;
  box-shadow: var(--shadow-md);
}
.cta-form-title {
  font-family: var(--font-serif);
  font-size: 1.5rem; font-weight: 300;
  color: var(--dusk); margin-bottom: .5rem;
}
.cta-form-sub { font-size: .82rem; color: var(--text-soft); margin-bottom: 2rem; }
.cta-form-field { margin-bottom: 1.1rem; }
.cta-form-label {
  display: block; font-size: .72rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-mid); margin-bottom: .5rem;
}
.cta-form-input {
  width: 100%; padding: .8rem 1rem;
  background: var(--cream);
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-sm);
  font-size: .88rem; color: var(--text); outline: none;
  font-family: var(--font-sans);
  transition: border-color var(--transition), box-shadow var(--transition);
}
.cta-form-input:focus {
  border-color: var(--terracotta);
  box-shadow: 0 0 0 3px rgba(196,98,45,.1);
}
.cta-form-input::placeholder { color: var(--text-soft); }
.cta-form-submit {
  width: 100%; padding: .95rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .85rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase;
  border: none; border-radius: var(--radius-full);
  cursor: pointer; margin-top: .5rem;
  transition: background var(--transition); position: relative; overflow: hidden;
}
.cta-form-submit::before {
  content: ''; position: absolute; inset: 0;
  background: var(--soil); transform: scaleX(0);
  transform-origin: left; transition: transform .4s;
}
.cta-form-submit:hover::before { transform: scaleX(1); }
.cta-form-submit span { position: relative; z-index: 1; }

/* ── RESPONSIVE ────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .hero { grid-template-columns: 1fr; }
  .hero-right { min-height: 50vh; }
  .feat-card-main { padding: 3rem 2rem 2rem; }
  .feat-bottom { padding: 1.5rem 2rem; }
  .spinning-badge { top: 2rem; right: 2rem; width: 80px; height: 80px; }
  .festivals-home-grid { grid-template-columns: repeat(2, 1fr); }
  .pop-grid { grid-template-columns: repeat(3, 1fr); }
  .cta-inner { grid-template-columns: 1fr; }
  .counter-inner { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .hero-left { padding: 8rem 6% 4rem; }
  .festivals-home-grid { grid-template-columns: 1fr; }
  .pop-grid { grid-template-columns: 1fr; }
  .cat-grid { grid-template-columns: repeat(3, 1fr); }
  .cal-event { grid-template-columns: 70px 1fr; }
  .cal-btn { display: none; }
  .hero-stats { gap: 2rem; }
}
@media (max-width: 480px) {
  .counter-inner { grid-template-columns: 1fr 1fr; }
  .cat-grid { grid-template-columns: repeat(2, 1fr); }
}
CSS;

require_once 'includes/header.php';
?>

<!-- ============================================================
     HERO
============================================================ -->
<section class="hero" aria-label="Présentation XwéDò">

  <!-- Gauche — texte -->
  <div class="hero-left">
    <div class="arc arc-1" aria-hidden="true"></div>
    <div class="arc arc-2" aria-hidden="true"></div>
    <div class="arc arc-3" aria-hidden="true"></div>

    <div class="hero-pretitle">
      <span class="pretitle-line-hero"></span>
      <span class="pretitle-text-hero">Bénin · Culture · Festivals</span>
    </div>

    <h1 class="hero-h1">
      <span class="l1">Maison</span>
      <span class="l2">des festivals</span>
      <span class="l3">du Bénin</span>
    </h1>

    <p class="hero-desc">
      Découvrez, suivez et réservez vos billets pour les meilleurs festivals culturels du Bénin —
      des cérémonies Vodun aux concerts modernes, tout en un seul endroit.
    </p>

    <div class="hero-actions">
      <a href="<?= url('festivals.php') ?>" class="btn-terra">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Explorer les festivals</span>
      </a>
      <?php if (!estConnecte()): ?>
        <a href="<?= url('register.php') ?>" class="btn-link">
          Créer un compte
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      <?php endif; ?>
    </div>

    <div class="hero-stats">
      <div>
        <span class="hs-num counter-num" data-target="<?= $stats['nb_festivals'] ?>" data-suffix="">0</span>
        <span class="hs-label">Festivals</span>
      </div>
      <div>
        <span class="hs-num counter-num" data-target="<?= $stats['nb_participants'] ?>" data-suffix="">0</span>
        <span class="hs-label">Participants</span>
      </div>
      <div>
        <span class="hs-num counter-num" data-target="<?= $stats['nb_villes'] ?>" data-suffix="">0</span>
        <span class="hs-label">Villes</span>
      </div>
    </div>
  </div>

  <!-- Droite — festival vedette -->
  <div class="hero-right">
    <div class="hero-img-collage">
      <div class="hero-img-main" id="hero-bg"
           style="background-image: url('<?= $vedette ? imgUrl($vedette['image_principale']) : url('public/img/default-festival.jpg') ?>')">
      </div>
      <div class="hero-img-overlay"></div>
    </div>

    <!-- Badge tournant -->
    <div class="spinning-badge" aria-hidden="true">
      <svg viewBox="0 0 110 110">
        <path id="circle-path" d="M55,55 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0" fill="none"/>
        <text font-size="10.5" font-family="Outfit, sans-serif" fill="rgba(250,246,238,.6)" letter-spacing="3">
          <textPath href="#circle-path">XwéDò · Festivals · Bénin · Culture ·</textPath>
        </text>
      </svg>
      <div class="spinning-badge-inner">🥁</div>
    </div>

    <?php if ($vedette): ?>
      <div class="feat-card-main">
        <div class="feat-tag">
          <span class="feat-tag-dot"></span>
          <?= e($vedette['cat_icone'] ?? '') ?> <?= e($vedette['cat_nom'] ?? 'Festival') ?> · À venir
        </div>
        <h2 class="feat-name"><?= e($vedette['nom']) ?></h2>
        <div class="feat-info">
          <span class="feat-detail">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= periodeFestival($vedette['date_debut'], $vedette['date_fin']) ?>
          </span>
          <span class="feat-detail">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($vedette['lieu'] ?? $vedette['ville'] ?? 'Bénin') ?>
          </span>
        </div>
        <?php if ($vedette['nb_resa'] > 0): ?>
          <div class="feat-visitors">
            <span class="feat-visitors-num"><?= number_format($vedette['nb_resa'], 0, ',', ' ') ?></span>
            <span class="feat-visitors-label">participants</span>
          </div>
        <?php endif; ?>
      </div>

      <div class="feat-bottom">
        <div class="feat-nav-dots" id="hero-dots">
          <button class="feat-dot active" data-index="0" aria-label="Festival 1"></button>
          <?php for ($i = 1; $i < count($festivals); $i++): ?>
            <button class="feat-dot" data-index="<?= $i ?>" aria-label="Festival <?= $i+1 ?>"></button>
          <?php endfor; ?>
        </div>
        <a href="<?= url('festival.php?slug=' . e($vedette['slug'])) ?>" class="feat-link">
          Découvrir
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    <?php endif; ?>
  </div>

</section>

<!-- Bande kente -->
<div class="kente-strip" aria-hidden="true"></div>

<!-- ============================================================
     COMPTEURS
============================================================ -->
<div class="counter-strip" id="counter-strip">
  
</div>

<!-- ============================================================
     FESTIVALS À LA UNE
============================================================ -->
<?php if (!empty($festivals)): ?>
<section class="section-festivals">
  <div class="section-head">
    <div>
      <div class="section-pretitle">
        <span class="pretitle-line"></span>
        <span class="pretitle-text">Prochains événements</span>
      </div>
      <h2 class="section-title">Festivals <em>à la une</em></h2>
    </div>
    <a href="<?= url('festivals.php') ?>" class="btn-link">
      Voir tous les festivals
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
  </div>

  <div class="festivals-home-grid">
    <?php foreach ($festivals as $f):
      $statut = statutDate($f['date_debut'], $f['date_fin']);
    ?>
      <article class="fest-card reveal">
        <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" class="fest-card-img">
          <img src="<?= imgUrl($f['image_principale']) ?>" alt="<?= e($f['nom']) ?>" loading="lazy">
          <div class="fest-card-img-overlay"></div>
          <?php if (!empty($f['cat_nom'])): ?>
            <span class="fest-card-cat"><?= e($f['cat_icone'] ?? '') ?> <?= e($f['cat_nom']) ?></span>
          <?php endif; ?>
          <span class="fest-card-statut">
            <span class="badge <?= match($statut['classe']) { 'en-cours'=>'badge-green', 'a-venir'=>'badge-ochre', default=>'badge-sage' } ?>">
              <span class="statut-dot <?= match($statut['classe']) { 'en-cours'=>'dot-vert', 'a-venir'=>'dot-orange', default=>'dot-gris' } ?>"></span>
              <?= e($statut['label']) ?>
            </span>
          </span>
        </a>
        <div class="fest-card-body">
          <p class="fest-card-date"><?= periodeFestival($f['date_debut'], $f['date_fin']) ?></p>
          <h3 class="fest-card-name">
            <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>"><?= e($f['nom']) ?></a>
          </h3>
          <p class="fest-card-lieu">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= e($f['lieu'] ?? $f['ville'] ?? 'Bénin') ?>
          </p>
          <div class="fest-card-footer">
            <div class="fest-card-prix">
              <?= $f['prix_min'] !== null ? formatPrix((float)$f['prix_min']) : 'Gratuit' ?>
              <small><?= $f['prix_min'] > 0 ? 'à partir de' : 'Entrée libre' ?></small>
            </div>
            <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" class="fest-card-btn">
              Voir
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CATÉGORIES
============================================================ -->
<?php if (!empty($categories)): ?>
<section class="section-categories">
  <div class="section-categories-inner">
    <div class="section-head">
      <div>
        <div class="section-pretitle">
          <span class="pretitle-line"></span>
          <span class="pretitle-text">Explorer par thème</span>
        </div>
        <h2 class="section-title">Toutes les <em>catégories</em></h2>
      </div>
    </div>
    <div class="cat-grid">
      <?php foreach ($categories as $c): ?>
        <a href="<?= url('festivals.php?categorie=' . e($c['slug'])) ?>" class="cat-card reveal">
          <span class="cat-card-icon"><?= e($c['icone'] ?? '🎭') ?></span>
          <span class="cat-card-nom"><?= e($c['nom']) ?></span>
          <span class="cat-card-nb"><?= $c['nb_festivals'] ?> festival<?= $c['nb_festivals'] > 1 ? 's' : '' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     FESTIVALS POPULAIRES
============================================================ -->
<?php if (!empty($populaires)): ?>
<section class="section-populaires">
  <div class="section-head">
    <div>
      <div class="section-pretitle">
        <span class="pretitle-line"></span>
        <span class="pretitle-text">Les plus appréciés</span>
      </div>
      <h2 class="section-title">Festivals <em>populaires</em></h2>
    </div>
    <a href="<?= url('festivals.php?tri=populaire') ?>" class="btn-link">
      Voir le classement
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
  </div>
  <div class="pop-grid">
    <?php foreach ($populaires as $i => $f): ?>
      <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" class="pop-card reveal">
        <img src="<?= imgUrl($f['image_principale']) ?>" alt="<?= e($f['nom']) ?>" loading="lazy">
        <div class="pop-card-overlay"></div>
        <div class="pop-card-content">
          <div class="pop-rank">0<?= $i+1 ?></div>
          <h3 class="pop-nom"><?= e($f['nom']) ?></h3>
          <p class="pop-resa">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <?= number_format($f['nb_resa'], 0, ',', ' ') ?> participants
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CALENDRIER
============================================================ -->
<?php if (!empty($calendrier)): ?>
<section class="section-calendrier" id="mission">
  <div class="section-calendrier-inner">
    <div class="section-head">
      <div>
        <div class="section-pretitle">
          <span class="pretitle-line"></span>
          <span class="pretitle-text">prochains jours</span>
        </div>
        <h2 class="section-title">Calendrier <em>culturel</em></h2>
      </div>
      <a href="<?= url('calendrier.php') ?>" class="feat-link">
        Calendrier complet
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

    <div class="cal-list">
      <?php foreach ($calendrier as $ev): ?>
        <a href="<?= url('festival.php?slug=' . e($ev['slug'])) ?>" class="cal-event reveal">
          <div class="cal-date">
            <div class="cal-date-day"><?= date('d', strtotime($ev['date_debut'])) ?></div>
            <div class="cal-date-mois">
              <?php
              $moisCourts = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',
                             7=>'Juil',8=>'Aoû',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];
              echo $moisCourts[(int)date('n', strtotime($ev['date_debut']))];
              ?>
            </div>
          </div>
          <div class="cal-info">
            <p class="cal-nom"><?= e($ev['nom']) ?></p>
            <div class="cal-meta">
              <span class="cal-meta-item">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <?= e($ev['lieu'] ?? $ev['ville'] ?? 'Bénin') ?>
              </span>
              <?php if (!empty($ev['cat_nom'])): ?>
                <span class="cal-cat"><?= e($ev['cat_icone'] ?? '') ?> <?= e($ev['cat_nom']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <span class="cal-btn">Voir →</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================================================
     CTA INSCRIPTION / ALERTES
============================================================ -->
<section class="section-cta" id="contact">
  <div class="cta-inner">
    <div class="reveal">
      <div class="section-pretitle">
        <span class="pretitle-line"></span>
        <span class="pretitle-text">Ne manquez plus rien</span>
      </div>
      <h2 class="cta-title">Restez connecté<br>à la <em>culture béninoise</em></h2>
      <p class="cta-desc">
        Inscrivez-vous gratuitement pour recevoir les alertes des nouveaux festivals,
        gérer vos réservations et suivre vos événements favoris.
      </p>
      <div class="cta-features">
        <div class="cta-feature"><span class="cta-feature-dot"></span>Alertes nouveaux festivals</div>
        <div class="cta-feature"><span class="cta-feature-dot"></span>Billets numériques sécurisés</div>
        <div class="cta-feature"><span class="cta-feature-dot"></span>Historique de vos réservations</div>
        <div class="cta-feature"><span class="cta-feature-dot"></span>Rappels automatiques avant chaque événement</div>
      </div>
    </div>

    <!-- Formulaire -->
    <div class="reveal">
      <?php if (!estConnecte()): ?>
        <div class="cta-form-card">
          <h3 class="cta-form-title">Créer un compte gratuit</h3>
          <p class="cta-form-sub">Rejoignez la communauté XwéDò dès aujourd'hui.</p>
          <form action="<?= url('register.php') ?>" method="GET">
            <div class="cta-form-field">
              <label class="cta-form-label" for="cta-prenom">Prénom</label>
              <input type="text" id="cta-prenom" class="cta-form-input" placeholder="Koffi, Adélaïde…">
            </div>
            <div class="cta-form-field">
              <label class="cta-form-label" for="cta-email">Email</label>
              <input type="email" id="cta-email" class="cta-form-input" placeholder="vous@email.com">
            </div>
            <div class="cta-form-field">
              <label class="cta-form-label" for="cta-ville">Votre ville</label>
              <select id="cta-ville" class="cta-form-input" style="appearance:none;">
                <option value="">Choisir une ville…</option>
                <?php
                $villes = ['Cotonou','Porto-Novo','Ouidah','Parakou','Natitingou','Abomey','Nikki','Savalou'];
                foreach ($villes as $v) echo "<option>$v</option>";
                ?>
              </select>
            </div>
            <button type="submit" class="cta-form-submit" id="cta-btn">
              <span>S'inscrire aux alertes</span>
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="cta-form-card" style="text-align:center; padding: 3rem;">
          <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
          <h3 class="cta-form-title" style="margin-bottom:.8rem;">Vous êtes connecté !</h3>
          <p style="font-size:.88rem; color:var(--text-soft); margin-bottom:2rem;">
            Explorez les festivals et réservez vos billets directement.
          </p>
          <a href="<?= url('festivals.php') ?>" class="btn-terra" style="display:inline-flex;">
            <span>Explorer les festivals</span>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php
// ── JS spécifique à l'accueil ────────────────────────────────
$festivalsJson = json_encode(array_map(fn($f) => [
    'img'    => imgUrl($f['image_principale']),
    'nom'    => $f['nom'],
    'slug'   => $f['slug'],
    'date'   => periodeFestival($f['date_debut'], $f['date_fin']),
    'lieu'   => $f['lieu'] ?? $f['ville'] ?? 'Bénin',
    'resa'   => $f['nb_resa'],
    'cat'    => ($f['cat_icone'] ?? '') . ' ' . ($f['cat_nom'] ?? ''),
], $festivals));

$pageJS = <<<JS
// ── Slideshow hero ──────────────────────────────────────────
const heroFestivals = $festivalsJson;
const heroBg   = document.getElementById('hero-bg');
const heroFeat = document.querySelector('.feat-name');
const heroDate = document.querySelector('.feat-detail');
const heroDots = document.querySelectorAll('.feat-dot');
const heroLink = document.querySelector('.feat-link');

let heroIdx = 0;
let heroTimer;

function setHeroFestival(idx) {
  if (!heroFestivals.length) return;
  const f = heroFestivals[idx];
  heroBg.style.opacity = '0';
  setTimeout(() => {
    heroBg.style.backgroundImage = "url('" + f.img + "')";
    heroBg.style.opacity = '0.35';
    if (heroFeat) heroFeat.textContent = f.nom;
    if (heroLink) heroLink.href = '/xwedo/festival.php?slug=' + f.slug;
  }, 700);
  heroDots.forEach((d, i) => d.classList.toggle('active', i === idx));
}

heroDots.forEach((dot, i) => {
  dot.addEventListener('click', () => {
    heroIdx = i;
    setHeroFestival(i);
    clearInterval(heroTimer);
    heroTimer = setInterval(nextHero, 5000);
  });
});

function nextHero() {
  heroIdx = (heroIdx + 1) % heroFestivals.length;
  setHeroFestival(heroIdx);
}
if (heroFestivals.length > 1) heroTimer = setInterval(nextHero, 5000);

// ── Compteurs animés ────────────────────────────────────────
function animateCount(el) {
  const target = parseInt(el.dataset.target) || 0;
  const suffix = el.dataset.suffix || '';
  const dur    = 2200;
  const start  = performance.now();
  const update = now => {
    const p     = Math.min((now - start) / dur, 1);
    const eased = 1 - Math.pow(1 - p, 4);
    const val   = Math.round(eased * target);
    el.textContent = (val >= 1000 ? val.toLocaleString('fr-FR') : val) + suffix;
    if (p < 1) requestAnimationFrame(update);
  };
  requestAnimationFrame(update);
}

const counterObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.querySelectorAll('.counter-num').forEach(animateCount);
      counterObs.unobserve(e.target);
    }
  });
}, { threshold: 0.4 });

const strip = document.getElementById('counter-strip');
if (strip) counterObs.observe(strip);

// Hero stats aussi
document.querySelectorAll('.hero-stats .counter-num').forEach(el => {
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { animateCount(e.target); obs.unobserve(e.target); }
    });
  }, { threshold: 0.5 });
  obs.observe(el);
});

// ── Calendrier hover glow ───────────────────────────────────
document.querySelectorAll('.cal-event').forEach(el => {
  el.addEventListener('mouseenter', () => el.style.boxShadow = '4px 0 20px rgba(196,98,45,.08)');
  el.addEventListener('mouseleave', () => el.style.boxShadow = '');
});
JS;

require_once 'includes/footer.php';
?>