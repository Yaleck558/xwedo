<?php
// ============================================================
//  XwéDò – Statistiques globales (admin)
//  Fichier : admin/stats.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo = getDB();

// ── Revenus par mois (12 derniers mois) ──────────────────────
$revenusParMois = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois,
           DATE_FORMAT(created_at, '%b %Y') AS mois_label,
           COUNT(*) AS nb_resa,
           COALESCE(SUM(CASE WHEN statut='confirmee' THEN prix_total END), 0) AS revenus
    FROM reservations
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mois ASC
")->fetchAll();

// ── Inscriptions par mois ────────────────────────────────────
$inscritsParMois = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois,
           COUNT(*) AS nb
    FROM utilisateurs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY mois ASC
")->fetchAll();

// ── Top festivals par réservations ───────────────────────────
$topFestivals = $pdo->query("
    SELECT f.nom, f.slug, f.date_debut, f.date_fin,
           COUNT(r.id) AS nb_resa,
           COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.prix_total END),0) AS revenus,
           u.prenom AS org_prenom, u.nom AS org_nom
    FROM festivals f
    LEFT JOIN reservations r ON f.id = r.festival_id
    JOIN utilisateurs u ON f.organisateur_id = u.id
    WHERE f.statut = 'publie'
    GROUP BY f.id
    ORDER BY nb_resa DESC
    LIMIT 10
")->fetchAll();

// ── Répartition par catégorie ────────────────────────────────
$parCat = $pdo->query("
    SELECT c.nom, c.icone,
           COUNT(DISTINCT f.id) AS nb_festivals,
           COUNT(DISTINCT r.id) AS nb_resa
    FROM categories_festival c
    LEFT JOIN festivals f ON c.id = f.categorie_id AND f.statut = 'publie'
    LEFT JOIN reservations r ON f.id = r.festival_id AND r.statut = 'confirmee'
    GROUP BY c.id
    ORDER BY nb_resa DESC
")->fetchAll();

// ── Répartition par ville ────────────────────────────────────
$parVille = $pdo->query("
    SELECT COALESCE(f.ville, 'Non précisée') AS ville,
           COUNT(DISTINCT f.id) AS nb_festivals,
           COUNT(DISTINCT r.id) AS nb_resa
    FROM festivals f
    LEFT JOIN reservations r ON f.id = r.festival_id AND r.statut = 'confirmee'
    WHERE f.statut = 'publie'
    GROUP BY f.ville
    ORDER BY nb_resa DESC
    LIMIT 10
")->fetchAll();

// ── KPIs totaux ──────────────────────────────────────────────
$kpi = $pdo->query("
    SELECT
        (SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut='confirmee') AS revenus_total,
        (SELECT COUNT(*) FROM reservations WHERE statut='confirmee')                     AS total_resa,
        (SELECT COUNT(*) FROM utilisateurs)                                              AS total_users,
        (SELECT COUNT(*) FROM festivals WHERE statut='publie')                           AS total_festivals,
        (SELECT COALESCE(AVG(prix_total),0) FROM reservations WHERE statut='confirmee')  AS panier_moyen,
        (SELECT COUNT(*) FROM reservations WHERE DATE(created_at) = CURDATE())           AS resa_auj
")->fetch();

$maxMois = !empty($revenusParMois) ? max(array_column($revenusParMois, 'revenus')) ?: 1 : 1;
$maxCat  = !empty($parCat)         ? max(array_column($parCat, 'nb_resa')) ?: 1 : 1;
$maxVille= !empty($parVille)       ? max(array_column($parVille, 'nb_resa')) ?: 1 : 1;

$pageTitre = 'Statistiques – Administration XwéDò';

$pageCSS = <<<CSS
.admin-page { padding: 7rem 5% 4rem; max-width: 1280px; margin: 0 auto; }
.admin-nav { display: flex; gap: .5rem; margin-bottom: 2rem; flex-wrap: wrap;  margin-top: 3.7rem; }
.admin-nav-link { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1.1rem; border: 1px solid rgba(196,98,45,.15); border-radius: var(--radius-full); font-size: .78rem; color: var(--text-mid); background: var(--white); transition: all var(--transition); }
.admin-nav-link:hover, .admin-nav-link.active { background: var(--terracotta); color: var(--white); border-color: var(--terracotta); }
.page-title { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 300; color: var(--dusk); margin-bottom: 2.5rem; }
.page-title em { font-style: italic; color: var(--terracotta); }

/* KPI strip */
.kpi-strip {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 1rem; margin-bottom: 2.5rem;
}
.kpi-card { background: var(--white); border: 1px solid rgba(196,98,45,.1); border-radius: var(--radius-lg); padding: 1.4rem 1.6rem; box-shadow: var(--shadow-sm); }
.kpi-label { font-size: .68rem; font-weight: 500; letter-spacing: .14em; text-transform: uppercase; color: var(--text-soft); margin-bottom: .7rem; }
.kpi-val { font-family: var(--font-serif); font-size: 1.9rem; font-weight: 600; color: var(--terracotta); line-height: 1; }
.kpi-val.sm { font-size: 1.2rem; }
.kpi-sub { font-size: .72rem; color: var(--text-soft); margin-top: .3rem; }

/* Grille stats */
.stats-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
.stats-layout.full { grid-template-columns: 1fr; }

.panel-card { background: var(--white); border: 1px solid rgba(196,98,45,.1); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; }
.panel-card-header { padding: 1.2rem 1.6rem; border-bottom: 1px solid rgba(196,98,45,.08); }
.panel-card-title { font-size: .72rem; font-weight: 500; letter-spacing: .15em; text-transform: uppercase; color: var(--text-mid); }

/* Graphe barres revenus mensuels */
.chart-wrap { padding: 1.5rem 1.5rem .5rem; }
.chart-bars { display: flex; align-items: flex-end; gap: 6px; height: 140px; }
.chart-col { display: flex; flex-direction: column; align-items: center; gap: 5px; flex: 1; }
.chart-bar { width: 100%; border-radius: 3px 3px 0 0; min-height: 3px; transition: opacity var(--transition); }
.chart-bar.rev  { background: var(--terracotta); opacity: .8; }
.chart-bar.rev:hover { opacity: 1; }
.chart-bar.ins  { background: var(--sage); opacity: .7; }
.chart-bar-lbl  { font-size: .6rem; color: var(--text-soft); white-space: nowrap; }

/* Top festivals */
.top-table { width: 100%; border-collapse: collapse; }
.top-table th { padding: .7rem 1.2rem; font-size: .68rem; font-weight: 500; letter-spacing: .1em; text-transform: uppercase; color: var(--text-soft); text-align: left; background: rgba(237,224,200,.3); border-bottom: 1px solid rgba(196,98,45,.08); }
.top-table td { padding: .85rem 1.2rem; font-size: .82rem; color: var(--text-mid); border-bottom: 1px solid rgba(196,98,45,.05); }
.top-table tr:last-child td { border-bottom: none; }
.top-table tr:hover td { background: rgba(196,98,45,.02); }
.rank-num { font-family: var(--font-serif); font-size: 1rem; font-weight: 600; color: var(--sand-dark); }

/* Barres catégories / villes */
.bar-row { padding: .9rem 1.6rem; border-bottom: 1px solid rgba(196,98,45,.05); }
.bar-row:last-child { border-bottom: none; }
.bar-row-top { display: flex; justify-content: space-between; margin-bottom: .5rem; font-size: .85rem; }
.bar-row-nom { color: var(--text); font-weight: 400; }
.bar-row-nb  { font-weight: 500; color: var(--terracotta); font-family: var(--font-serif); }
.bar-bg { height: 4px; border-radius: 2px; background: rgba(196,98,45,.1); overflow: hidden; }
.bar-fill { height: 100%; border-radius: 2px; }
.bar-fill.terra { background: var(--terracotta); }
.bar-fill.sage  { background: var(--sage); }

@media (max-width: 900px)  { .stats-layout { grid-template-columns: 1fr; } }
@media (max-width: 480px)  { .kpi-strip { grid-template-columns: 1fr 1fr; } }
CSS;

require_once '../includes/header.php';
?>

<div class="admin-page">

  <nav class="admin-nav">
    <a href="<?= url('admin/dashboard.php') ?>"     class="admin-nav-link">Vue d'ensemble</a>
    <a href="<?= url('admin/festivals.php') ?>"     class="admin-nav-link">Festivals</a>
    <a href="<?= url('admin/organisateurs.php') ?>" class="admin-nav-link">Organisateurs</a>
    <a href="<?= url('admin/stats.php') ?>"         class="admin-nav-link active">Statistiques</a>
  </nav>

  <h1 class="page-title">Statistiques <em>globales</em></h1>

  <!-- KPIs -->
  <div class="kpi-strip">
    <div class="kpi-card reveal">
      <div class="kpi-label">Revenus totaux</div>
      <div class="kpi-val sm"><?= formatPrix((float)$kpi['revenus_total']) ?></div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Réservations</div>
      <div class="kpi-val"><?= number_format($kpi['total_resa'], 0, ',', ' ') ?></div>
      <div class="kpi-sub"><?= $kpi['resa_auj'] ?> aujourd'hui</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Panier moyen</div>
      <div class="kpi-val sm"><?= formatPrix((float)$kpi['panier_moyen']) ?></div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Utilisateurs</div>
      <div class="kpi-val"><?= number_format($kpi['total_users'], 0, ',', ' ') ?></div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Festivals publiés</div>
      <div class="kpi-val"><?= $kpi['total_festivals'] ?></div>
    </div>
  </div>

  <!-- Graphes mensuels -->
  <?php if (!empty($revenusParMois)): ?>
  <div class="panel-card reveal">
    <div class="panel-card-header">
      <p class="panel-card-title">Revenus mensuels (12 derniers mois)</p>
    </div>
    <div class="chart-wrap">
      <div class="chart-bars">
        <?php foreach ($revenusParMois as $m): ?>
          <div class="chart-col">
            <div class="chart-bar rev"
                 style="height:<?= max(3, round($m['revenus']/$maxMois*120)) ?>px"
                 title="<?= e($m['mois_label']) ?> : <?= formatPrix((float)$m['revenus']) ?> · <?= $m['nb_resa'] ?> résa"></div>
            <span class="chart-bar-lbl"><?= e(substr($m['mois_label'], 0, 3)) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="stats-layout">

    <!-- Top festivals -->
    <div class="panel-card reveal">
      <div class="panel-card-header">
        <p class="panel-card-title">Top 10 festivals</p>
      </div>
      <div style="overflow-x:auto;">
        <table class="top-table">
          <thead><tr><th>#</th><th>Festival</th><th>Billets</th><th>Revenus</th></tr></thead>
          <tbody>
            <?php foreach ($topFestivals as $i => $f): ?>
              <tr>
                <td><span class="rank-num"><?= $i+1 ?></span></td>
                <td>
                  <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" style="font-weight:500; color:var(--text);"><?= e(tronquer($f['nom'], 28)) ?></a>
                  <div style="font-size:.72rem; color:var(--text-soft);"><?= e($f['org_prenom'] . ' ' . $f['org_nom']) ?></div>
                </td>
                <td><?= number_format($f['nb_resa'], 0, ',', ' ') ?></td>
                <td style="font-weight:500; color:var(--terracotta); font-family:var(--font-serif);"><?= formatPrix((float)$f['revenus']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Côte droit -->
    <div>
      <!-- Par catégorie -->
      <div class="panel-card reveal">
        <div class="panel-card-header">
          <p class="panel-card-title">Par catégorie</p>
        </div>
        <?php foreach ($parCat as $c): ?>
          <div class="bar-row">
            <div class="bar-row-top">
              <span class="bar-row-nom"><?= e($c['icone'] ?? '') ?> <?= e($c['nom']) ?></span>
              <span class="bar-row-nb"><?= $c['nb_resa'] ?> résa</span>
            </div>
            <div class="bar-bg">
              <div class="bar-fill terra" style="width:<?= $maxCat > 0 ? round($c['nb_resa']/$maxCat*100) : 0 ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Par ville -->
      <div class="panel-card reveal">
        <div class="panel-card-header">
          <p class="panel-card-title">Festivals par ville</p>
        </div>
        <?php foreach ($parVille as $v): ?>
          <div class="bar-row">
            <div class="bar-row-top">
              <span class="bar-row-nom"><?= e($v['ville']) ?></span>
              <span class="bar-row-nb"><?= $v['nb_festivals'] ?> festival<?= $v['nb_festivals'] > 1 ? 's' : '' ?></span>
            </div>
            <div class="bar-bg">
              <div class="bar-fill sage" style="width:<?= $maxVille > 0 ? round($v['nb_resa']/$maxVille*100) : 0 ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once '../includes/footer.php'; ?>