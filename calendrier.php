<?php
// ============================================================
//  XwéDò – Calendrier des festivals
//  Fichier : calendrier.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pdo = getDB();

// ── Mois/Année en cours ou sélectionné ──────────────────────
$moisCourant = (int) ($_GET['mois'] ?? date('n'));
$anneeCourante = (int) ($_GET['annee'] ?? date('Y'));

// Sécuriser les valeurs
$moisCourant   = max(1, min(12, $moisCourant));
$anneeCourante = max(2024, min(2030, $anneeCourante));

// Navigation mois précédent / suivant
$moisPrev  = $moisCourant === 1  ? 12 : $moisCourant - 1;
$anneePrev = $moisCourant === 1  ? $anneeCourante - 1 : $anneeCourante;
$moisNext  = $moisCourant === 12 ? 1  : $moisCourant + 1;
$anneeNext = $moisCourant === 12 ? $anneeCourante + 1 : $anneeCourante;

// Infos du mois
$premierJour    = mktime(0, 0, 0, $moisCourant, 1, $anneeCourante);
$nbJours        = (int) date('t', $premierJour);
$jourDebutMois  = (int) date('N', $premierJour); // 1=Lun, 7=Dim
$moisNom = [
    1=>'Janvier', 2=>'Février',   3=>'Mars',    4=>'Avril',
    5=>'Mai',     6=>'Juin',      7=>'Juillet',  8=>'Août',
    9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'
];

// ── Festivals du mois ────────────────────────────────────────
$debutMois = sprintf('%04d-%02d-01', $anneeCourante, $moisCourant);
$finMois   = sprintf('%04d-%02d-%02d', $anneeCourante, $moisCourant, $nbJours);

$stmtMois = $pdo->prepare("
    SELECT f.*, c.nom AS cat_nom, c.icone AS cat_icone, c.slug AS cat_slug,
           MIN(tb.prix) AS prix_min
    FROM festivals f
    LEFT JOIN categories_festival c  ON f.categorie_id = c.id
    LEFT JOIN types_billet tb        ON f.id = tb.festival_id AND tb.actif = 1
    WHERE f.statut = 'publie'
      AND f.date_debut <= ? AND f.date_fin >= ?
    GROUP BY f.id
    ORDER BY f.date_debut ASC
");
$stmtMois->execute([$finMois, $debutMois]);
$festivalsMois = $stmtMois->fetchAll();

// Organiser par jour
$festivalsByDay = [];
foreach ($festivalsMois as $f) {
    $debut = max(1, (int)date('j', strtotime($f['date_debut'])) );
    $fin   = min($nbJours, (int)date('j', strtotime($f['date_fin'])));
    // Si le festival commence un autre mois
    if (date('Y-m', strtotime($f['date_debut'])) !== sprintf('%04d-%02d', $anneeCourante, $moisCourant)) {
        $debut = 1;
    }
    if (date('Y-m', strtotime($f['date_fin'])) !== sprintf('%04d-%02d', $anneeCourante, $moisCourant)) {
        $fin = $nbJours;
    }
    for ($j = $debut; $j <= $fin; $j++) {
        $festivalsByDay[$j][] = $f;
    }
}

// ── Prochains festivals (liste) ──────────────────────────────
$stmtProchains = $pdo->prepare("
    SELECT f.*, c.nom AS cat_nom, c.icone AS cat_icone,
           MIN(tb.prix) AS prix_min
    FROM festivals f
    LEFT JOIN categories_festival c  ON f.categorie_id = c.id
    LEFT JOIN types_billet tb        ON f.id = tb.festival_id AND tb.actif = 1
    WHERE f.statut = 'publie' AND f.date_fin >= CURDATE()
    GROUP BY f.id
    ORDER BY f.date_debut ASC
    LIMIT 20
");
$stmtProchains->execute();
$prochains = $stmtProchains->fetchAll();

// ── Catégories pour filtre ───────────────────────────────────
$categories = $pdo->query('SELECT * FROM categories_festival ORDER BY nom')->fetchAll();

$pageTitre = 'Calendrier des Festivals – XwéDò';
$pageDesc  = 'Consultez le calendrier complet des festivals culturels du Bénin. Ne manquez plus aucun événement.';

$pageCSS = <<<CSS
/* ── Calendrier ─────────────────────────────────────────── */
.cal-page { padding-top: 5.5rem; }

/* Hero */
.cal-hero {
  background: var(--dusk);
  padding: 3.5rem 5% 3rem;
  position: relative; overflow: hidden;
}
.cal-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(
    90deg,
    var(--terracotta) 0px, var(--terracotta) 18px,
    var(--ochre) 18px, var(--ochre) 32px,
    var(--sage) 32px, var(--sage) 46px,
    var(--terracotta-dark) 46px, var(--terracotta-dark) 60px,
    var(--ochre-light) 60px, var(--ochre-light) 72px
  );
  height: 4px; top: 0; bottom: auto;
}
.cal-hero-inner {
  max-width: 1280px; margin: 0 auto;
  display: flex; align-items: center;
  justify-content: space-between; gap: 2rem; flex-wrap: wrap;
}
.cal-hero-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 4vw, 3.2rem);
  font-weight: 300; color: var(--cream);
  line-height: 1.1;
}
.cal-hero-title em { font-style: italic; color: var(--ochre); }
.cal-hero-sub {
  font-size: .88rem; color: rgba(250,246,238,.5);
  margin-top: .5rem; font-weight: 300;
}

/* Navigation mois */
.cal-nav {
  display: flex; align-items: center; gap: 1rem;
}
.cal-nav-btn {
  display: flex; align-items: center; justify-content: center;
  width: 40px; height: 40px;
  border: 1px solid rgba(250,246,238,.2);
  border-radius: 50%;
  color: rgba(250,246,238,.7);
  transition: all var(--transition);
  background: none; cursor: pointer; text-decoration: none;
}
.cal-nav-btn:hover {
  border-color: var(--ochre); color: var(--ochre);
  background: rgba(212,168,83,.1);
}
.cal-nav-mois {
  font-family: var(--font-serif);
  font-size: 1.4rem; font-weight: 300;
  color: var(--cream); text-align: center; min-width: 200px;
}
.cal-nav-mois span { font-size: .85rem; color: rgba(250,246,238,.4); display: block; }

/* Corps */
.cal-body {
  max-width: 1280px; margin: 0 auto; padding: 3rem 5%;
  display: grid; grid-template-columns: 1fr 340px; gap: 2.5rem;
}

/* ── Grille calendrier ─── */
.cal-grid-wrap {}
.cal-grid-header {
  display: grid; grid-template-columns: repeat(7, 1fr);
  gap: 2px; margin-bottom: 2px;
}
.cal-grid-header-day {
  text-align: center; padding: .6rem 0;
  font-size: .7rem; font-weight: 600;
  letter-spacing: .12em; text-transform: uppercase;
  color: var(--text-soft);
}
.cal-grid {
  display: grid; grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}
.cal-cell {
  min-height: 100px;
  background: var(--white);
  border: 1px solid rgba(196,98,45,.08);
  border-radius: var(--radius-sm);
  padding: .5rem;
  transition: border-color var(--transition);
  position: relative; overflow: hidden;
}
.cal-cell:hover { border-color: rgba(196,98,45,.25); }
.cal-cell.vide { background: rgba(237,224,200,.2); }
.cal-cell.aujourd-hui {
  border-color: var(--terracotta);
  background: rgba(196,98,45,.03);
}
.cal-cell.aujourd-hui .cal-cell-num {
  background: var(--terracotta);
  color: var(--white);
}
.cal-cell-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 50%;
  font-size: .8rem; font-weight: 500; color: var(--text-mid);
  margin-bottom: .3rem;
}
.cal-cell-events { display: flex; flex-direction: column; gap: 2px; }
.cal-event-pill {
  display: block; padding: 2px 5px;
  border-radius: 3px; font-size: .65rem;
  font-weight: 500; line-height: 1.3;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  cursor: pointer; text-decoration: none;
  transition: opacity var(--transition);
}
.cal-event-pill:hover { opacity: .8; }
.cal-event-pill.cat-0 { background: rgba(196,98,45,.15); color: var(--terracotta-dark); }
.cal-event-pill.cat-1 { background: rgba(212,168,83,.2);  color: #8B6914; }
.cal-event-pill.cat-2 { background: rgba(122,140,110,.15);color: #3D5C30; }
.cal-event-pill.cat-3 { background: rgba(41,128,185,.12); color: #1A4D6E; }
.cal-event-pill.cat-4 { background: rgba(155,89,182,.12); color: #6C3483; }
.cal-event-pill.cat-5 { background: rgba(39,174,96,.12);  color: #1A6B3C; }
.cal-event-pill.cat-6 { background: rgba(231,76,60,.12);  color: #922B21; }
.cal-event-pill.cat-7 { background: rgba(52,152,219,.12); color: #1A4D6E; }
.cal-more {
  font-size: .62rem; color: var(--text-soft);
  padding: 1px 4px; cursor: pointer;
}

/* ── Sidebar ─── */
.cal-sidebar {}
.cal-sidebar-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  overflow: hidden; box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.cal-sidebar-header {
  padding: 1.2rem 1.4rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
  display: flex; align-items: center; justify-content: space-between;
}
.cal-sidebar-title {
  font-size: .72rem; font-weight: 600;
  letter-spacing: .14em; text-transform: uppercase; color: var(--text-mid);
}

/* Liste prochains festivals */
.prochain-list { display: flex; flex-direction: column; }
.prochain-item {
  display: flex; align-items: center; gap: 1rem;
  padding: .9rem 1.4rem;
  border-bottom: 1px solid rgba(196,98,45,.05);
  transition: background var(--transition);
  text-decoration: none; color: inherit;
}
.prochain-item:last-child { border-bottom: none; }
.prochain-item:hover { background: rgba(196,98,45,.03); }
.prochain-date-box {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center;
  min-width: 44px; height: 44px;
  background: var(--sand); border-radius: var(--radius-sm);
  flex-shrink: 0;
}
.prochain-date-box .jour {
  font-family: var(--font-serif);
  font-size: 1.3rem; font-weight: 600;
  color: var(--terracotta); line-height: 1;
}
.prochain-date-box .mois-court {
  font-size: .58rem; font-weight: 600;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-soft);
}
.prochain-info { flex: 1; min-width: 0; }
.prochain-nom {
  font-size: .88rem; font-weight: 500; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: .2rem;
}
.prochain-meta { font-size: .72rem; color: var(--text-soft); }
.prochain-prix {
  font-size: .8rem; font-weight: 600;
  color: var(--terracotta); font-family: var(--font-serif);
  flex-shrink: 0;
}

/* Légende catégories */
.cal-legend { display: flex; flex-direction: column; gap: .5rem; padding: 1.2rem 1.4rem; }
.cal-legend-item { display: flex; align-items: center; gap: .7rem; font-size: .8rem; color: var(--text-mid); }
.cal-legend-dot { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }

/* Stats du mois */
.cal-stats {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1px; background: rgba(196,98,45,.08);
  border-radius: var(--radius-md); overflow: hidden;
  margin-bottom: 1.5rem;
}
.cal-stat {
  background: var(--white);
  padding: 1rem 1.2rem; text-align: center;
}
.cal-stat-num {
  font-family: var(--font-serif);
  font-size: 2rem; font-weight: 600;
  color: var(--terracotta); line-height: 1;
  display: block;
}
.cal-stat-label {
  font-size: .7rem; color: var(--text-soft);
  letter-spacing: .08em; text-transform: uppercase;
  margin-top: .3rem; display: block;
}

/* Filtres catégories */
.cat-filter-pills {
  display: flex; gap: .4rem; flex-wrap: wrap;
  padding: 1rem 5%; max-width: 1280px; margin: 0 auto;
}
.cat-filter-pill {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .38rem .9rem;
  border: 1.5px solid rgba(196,98,45,.15);
  border-radius: var(--radius-full);
  font-size: .75rem; color: var(--text-mid);
  background: var(--white);
  transition: all var(--transition);
  text-decoration: none; cursor: pointer;
}
.cat-filter-pill:hover,
.cat-filter-pill.active {
  background: var(--terracotta); color: var(--white);
  border-color: var(--terracotta);
}

/* Responsive */
@media (max-width: 1024px) {
  .cal-body { grid-template-columns: 1fr; }
  .cal-sidebar { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
}
@media (max-width: 768px) {
  .cal-grid-wrap { overflow-x: auto; }
  .cal-grid, .cal-grid-header { min-width: 560px; }
  .cal-cell { min-height: 70px; }
  .cal-sidebar { grid-template-columns: 1fr; }
  .cal-hero-inner { flex-direction: column; align-items: flex-start; }
  .cal-nav-mois { min-width: 160px; font-size: 1.1rem; }
}
CSS;

require_once 'includes/header.php';

// Couleurs par index de catégorie
$catColors = [
    'traditionnel-vodun' => 0,
    'musique-concerts'   => 1,
    'theatre-danse'      => 2,
    'cinema-audiovisuel' => 3,
    'arts-visuels'       => 4,
    'gastronomie'        => 5,
    'litterature'        => 6,
    'sport-culture'      => 7,
    'chill-detente'      => 2,
];

$moisCourts = [
    1=>'Jan', 2=>'Fév', 3=>'Mar', 4=>'Avr',
    5=>'Mai', 6=>'Jun', 7=>'Jul', 8=>'Aoû',
    9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Déc'
];

$aujourdHui     = (int) date('j');
$moisAujoudhui  = (int) date('n');
$anneeAujoudhui = (int) date('Y');
$estMoisActuel  = ($moisCourant === $moisAujoudhui && $anneeCourante === $anneeAujoudhui);
?>

<div class="cal-page">

  <!-- Hero -->
  <div class="cal-hero">
    <div class="cal-hero-inner">
      <div>
        <h1 class="cal-hero-title">Calendrier <em>culturel</em></h1>
        <p class="cal-hero-sub">
          <?= count($festivalsMois) ?> festival<?= count($festivalsMois) > 1 ? 's' : '' ?>
          en <?= $moisNom[$moisCourant] ?> <?= $anneeCourante ?>
        </p>
      </div>

      <!-- Navigation mois -->
      <div class="cal-nav">
        <a href="?mois=<?= $moisPrev ?>&annee=<?= $anneePrev ?>" class="cal-nav-btn" aria-label="Mois précédent">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div class="cal-nav-mois">
          <?= $moisNom[$moisCourant] ?>
          <span><?= $anneeCourante ?></span>
        </div>
        <a href="?mois=<?= $moisNext ?>&annee=<?= $anneeNext ?>" class="cal-nav-btn" aria-label="Mois suivant">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
    </div>
  </div>

  <!-- Filtres catégories -->
  <div class="cat-filter-pills">
    <span class="cat-filter-pill active" data-cat="tous">Tous</span>
    <?php foreach ($categories as $c): ?>
      <span class="cat-filter-pill" data-cat="<?= e($c['slug']) ?>">
        <?= e($c['icone'] ?? '') ?> <?= e($c['nom']) ?>
      </span>
    <?php endforeach; ?>
  </div>

  <!-- Corps -->
  <div class="cal-body">

    <!-- Grille calendrier -->
    <div class="cal-grid-wrap">

      <!-- En-têtes jours -->
      <div class="cal-grid-header">
        <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $j): ?>
          <div class="cal-grid-header-day"><?= $j ?></div>
        <?php endforeach; ?>
      </div>

      <!-- Grille -->
      <div class="cal-grid" id="cal-grid">

        <!-- Cellules vides avant le 1er -->
        <?php for ($v = 1; $v < $jourDebutMois; $v++): ?>
          <div class="cal-cell vide"></div>
        <?php endfor; ?>

        <!-- Jours du mois -->
        <?php for ($jour = 1; $jour <= $nbJours; $jour++):
          $estAujourdhui = $estMoisActuel && $jour === $aujourdHui;
          $evts = $festivalsByDay[$jour] ?? [];
          $max  = 3;
        ?>
          <div class="cal-cell <?= $estAujourdhui ? 'aujourd-hui' : '' ?>">
            <span class="cal-cell-num"><?= $jour ?></span>
            <div class="cal-cell-events">
              <?php foreach (array_slice($evts, 0, $max) as $ev):
                $catSlug  = $ev['cat_slug'] ?? '';
                $catIdx   = $catColors[$catSlug] ?? 0;
              ?>
                <a href="<?= url('festival.php?slug=' . e($ev['slug'])) ?>"
                   class="cal-event-pill cat-<?= $catIdx ?>"
                   data-cat="<?= e($catSlug) ?>"
                   title="<?= e($ev['nom']) ?>">
                  <?= e($ev['cat_icone'] ?? '') ?> <?= e(tronquer($ev['nom'], 20)) ?>
                </a>
              <?php endforeach; ?>
              <?php if (count($evts) > $max): ?>
                <span class="cal-more">+<?= count($evts) - $max ?> autre<?= count($evts) - $max > 1 ? 's' : '' ?></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>

        <!-- Cellules vides après le dernier jour -->
        <?php
        $total = $jourDebutMois - 1 + $nbJours;
        $reste = $total % 7 === 0 ? 0 : 7 - ($total % 7);
        for ($v = 0; $v < $reste; $v++):
        ?>
          <div class="cal-cell vide"></div>
        <?php endfor; ?>

      </div><!-- /.cal-grid -->
    </div>

    <!-- Sidebar -->
    <aside class="cal-sidebar">

      <!-- Stats du mois -->
      <div class="cal-stats">
        <div class="cal-stat">
          <span class="cal-stat-num"><?= count($festivalsMois) ?></span>
          <span class="cal-stat-label">Festival<?= count($festivalsMois) > 1 ? 's' : '' ?> en <?= $moisNom[$moisCourant] ?></span>
        </div>
        <div class="cal-stat">
          <span class="cal-stat-num"><?= count($prochains) ?></span>
          <span class="cal-stat-label">À ne pas manquer</span>
        </div>
      </div>

      <!-- Prochains festivals -->
      <div class="cal-sidebar-card">
        <div class="cal-sidebar-header">
          <span class="cal-sidebar-title">Prochains festivals</span>
          <a href="<?= url('festivals.php?tri=date') ?>" class="btn-link" style="font-size:.72rem;">Tout voir →</a>
        </div>
        <div class="prochain-list">
          <?php if (empty($prochains)): ?>
            <p style="padding:1.5rem; text-align:center; color:var(--text-soft); font-size:.85rem;">
              Aucun festival à venir.
            </p>
          <?php else: ?>
            <?php foreach ($prochains as $f):
              $ts   = strtotime($f['date_debut']);
              $jour = date('j', $ts);
              $mois = $moisCourts[(int)date('n', $ts)];
            ?>
              <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" class="prochain-item">
                <div class="prochain-date-box">
                  <span class="jour"><?= $jour ?></span>
                  <span class="mois-court"><?= $mois ?></span>
                </div>
                <div class="prochain-info">
                  <p class="prochain-nom"><?= e($f['nom']) ?></p>
                  <p class="prochain-meta">
                    <?= e($f['cat_icone'] ?? '') ?> <?= e($f['cat_nom'] ?? '') ?>
                    <?php if (!empty($f['ville'])): ?> · <?= e($f['ville']) ?><?php endif; ?>
                  </p>
                </div>
                <span class="prochain-prix">
                  <?= $f['prix_min'] !== null ? formatPrix((float)$f['prix_min']) : 'Gratuit' ?>
                </span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Légende catégories -->
      <div class="cal-sidebar-card">
        <div class="cal-sidebar-header">
          <span class="cal-sidebar-title">Catégories</span>
        </div>
        <div class="cal-legend">
          <?php
          $legendColors = [
            '#C4622D', '#D4A853', '#7A8C6E', '#2980B9',
            '#9B59B6', '#27AE60', '#E74C3C', '#3498DB'
          ];
          foreach ($categories as $i => $c):
            $color = $legendColors[$i % count($legendColors)];
          ?>
            <a href="<?= url('festivals.php?categorie=' . e($c['slug'])) ?>" class="cal-legend-item" style="text-decoration:none;">
              <span class="cal-legend-dot" style="background: <?= $color ?>20; border: 2px solid <?= $color ?>; border-radius:3px;"></span>
              <?= e($c['icone'] ?? '') ?> <?= e($c['nom']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

    </aside>
  </div>

</div><!-- /.cal-page -->

<?php
$pageJS = <<<JS
// ── Filtre catégories ────────────────────────────────────────
document.querySelectorAll('.cat-filter-pill').forEach(pill => {
  pill.addEventListener('click', () => {
    document.querySelectorAll('.cat-filter-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');

    const cat = pill.dataset.cat;
    document.querySelectorAll('.cal-event-pill').forEach(ev => {
      if (cat === 'tous' || ev.dataset.cat === cat) {
        ev.style.display = '';
      } else {
        ev.style.display = 'none';
      }
    });
  });
});
JS;
require_once 'includes/footer.php';
?>