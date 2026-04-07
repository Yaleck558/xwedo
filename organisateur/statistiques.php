<?php
// ============================================================
//  XwéDò – Statistiques festival (organisateur)
//  Fichier : organisateur/statistiques.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('organisateur');

$pdo        = getDB();
$uid        = userId();
$festivalId = getPropre('id', 0);

$stmt = $pdo->prepare('SELECT * FROM festivals WHERE id = ? AND organisateur_id = ? LIMIT 1');
$stmt->execute([$festivalId, $uid]);
$festival = $stmt->fetch();
if (!$festival) {
    setFlash('erreur', 'Festival introuvable.');
    rediriger('organisateur/dashboard.php');
}

// ── Stats globales ───────────────────────────────────────────
$global = $pdo->prepare("
    SELECT
        COUNT(*)                                                                AS total_resa,
        COUNT(CASE WHEN statut='confirmee'  THEN 1 END)                         AS resa_confirmees,
        COUNT(CASE WHEN statut='annulee'    THEN 1 END)                         AS resa_annulees,
        COALESCE(SUM(CASE WHEN statut='confirmee' THEN prix_total END), 0)      AS revenus,
        COALESCE(SUM(CASE WHEN statut='confirmee' THEN quantite   END), 0)      AS billets_vendus,
        COALESCE(AVG(CASE WHEN statut='confirmee' THEN prix_total END), 0)      AS panier_moyen
    FROM reservations WHERE festival_id = ?
");
$global->execute([$festivalId]);
$global = $global->fetch();

// ── Ventes par type de billet ────────────────────────────────
$parType = $pdo->prepare("
    SELECT tb.nom, tb.prix, tb.quantite,
           COUNT(r.id)                                                 AS nb_resa,
           COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.quantite END), 0) AS vendu,
           COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.prix_total END),0) AS revenus
    FROM types_billet tb
    LEFT JOIN reservations r ON tb.id = r.type_billet_id
    WHERE tb.festival_id = ?
    GROUP BY tb.id
    ORDER BY revenus DESC
");
$parType->execute([$festivalId]);
$parType = $parType->fetchAll();

// ── Ventes par jour (30 derniers jours) ─────────────────────
$parJour = $pdo->prepare("
    SELECT DATE(r.created_at) AS jour,
           COUNT(*) AS nb,
           COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.prix_total END), 0) AS revenus
    FROM reservations r
    WHERE r.festival_id = ?
      AND r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(r.created_at)
    ORDER BY jour ASC
");
$parJour->execute([$festivalId]);
$parJour = $parJour->fetchAll();

// ── Ventes par ville ─────────────────────────────────────────
$parVille = $pdo->prepare("
    SELECT COALESCE(u.ville, 'Non précisée') AS ville,
           COUNT(r.id) AS nb
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    WHERE r.festival_id = ? AND r.statut = 'confirmee'
    GROUP BY u.ville
    ORDER BY nb DESC
    LIMIT 8
");
$parVille->execute([$festivalId]);
$parVille = $parVille->fetchAll();

$maxVille = !empty($parVille) ? max(array_column($parVille, 'nb')) : 1;

$pageTitre = 'Stats – ' . e($festival['nom']) . ' – XwéDò';

$pageCSS = <<<CSS
.stats-page { padding: 7rem 5% 4rem; max-width: 1100px; margin: 0 auto; }
.creer-breadcrumb {
  display: flex; align-items: center; gap: .5rem;
  font-size: .78rem; color: var(--text-soft); margin-bottom: .8rem;
}
.creer-breadcrumb a { color: var(--text-soft); transition: color var(--transition); }
.creer-breadcrumb a:hover { color: var(--terracotta); }
.page-title {
  font-family: var(--font-serif);
  font-size: clamp(1.6rem, 3vw, 2.4rem);
  font-weight: 300; color: var(--dusk); margin-bottom: 2.5rem;
}
.page-title em { font-style: italic; color: var(--terracotta); }

/* KPI cards */
.kpi-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1.2rem; margin-bottom: 2.5rem;
}
.kpi-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg); padding: 1.5rem;
  box-shadow: var(--shadow-sm);
}
.kpi-label {
  font-size: .7rem; font-weight: 500;
  letter-spacing: .14em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: .7rem;
}
.kpi-val {
  font-family: var(--font-serif);
  font-size: 2rem; font-weight: 600;
  color: var(--terracotta); line-height: 1;
}
.kpi-val.small { font-size: 1.3rem; }
.kpi-sub { font-size: .75rem; color: var(--text-soft); margin-top: .4rem; }

/* Grille 2 colonnes */
.stats-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }

.panel-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);
}
.panel-card-header {
  padding: 1.2rem 1.6rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.panel-card-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase; color: var(--text-mid);
}

/* Barre types billet */
.type-row { padding: 1rem 1.6rem; border-bottom: 1px solid rgba(196,98,45,.06); }
.type-row:last-child { border-bottom: none; }
.type-row-header { display: flex; justify-content: space-between; margin-bottom: .5rem; }
.type-row-nom { font-size: .88rem; font-weight: 500; color: var(--text); }
.type-row-rev { font-size: .88rem; font-weight: 500; color: var(--terracotta); font-family: var(--font-serif); }
.type-bar { height: 5px; border-radius: 3px; background: rgba(196,98,45,.1); overflow: hidden; }
.type-bar-fill { height: 100%; border-radius: 3px; background: var(--terracotta); }
.type-row-sub { display: flex; justify-content: space-between; font-size: .72rem; color: var(--text-soft); margin-top: .4rem; }

/* Graphe journalier (barres CSS) */
.chart-area { padding: 1.5rem; }
.chart-bars {
  display: flex; align-items: flex-end; gap: 4px;
  height: 120px; overflow-x: auto;
}
.chart-bar-wrap { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; min-width: 20px; }
.chart-bar {
  width: 100%; background: var(--terracotta); border-radius: 2px 2px 0 0;
  opacity: .75; transition: opacity var(--transition);
  min-height: 2px;
}
.chart-bar:hover { opacity: 1; }
.chart-bar-label { font-size: .6rem; color: var(--text-soft); white-space: nowrap; }

/* Villes */
.ville-row { padding: .8rem 1.6rem; border-bottom: 1px solid rgba(196,98,45,.05); }
.ville-row:last-child { border-bottom: none; }
.ville-row-top { display: flex; justify-content: space-between; margin-bottom: .4rem; }
.ville-row-nom { font-size: .85rem; font-weight: 400; color: var(--text); }
.ville-row-nb  { font-size: .82rem; font-weight: 500; color: var(--terracotta); }
.ville-bar { height: 4px; border-radius: 2px; background: rgba(196,98,45,.1); overflow: hidden; }
.ville-bar-fill { height: 100%; border-radius: 2px; background: var(--sage); }

@media (max-width: 768px) { .stats-grid-2 { grid-template-columns: 1fr; } }
@media (max-width: 480px) { .kpi-grid { grid-template-columns: 1fr 1fr; } }
CSS;

require_once '../includes/header.php';
?>

<div class="stats-page">
  <div class="creer-breadcrumb">
    <a href="<?= url('organisateur/dashboard.php') ?>">Mon espace</a>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    <a href="<?= url('organisateur/creer-festival.php?id=' . $festivalId) ?>"><?= e($festival['nom']) ?></a>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    <span>Statistiques</span>
  </div>
  <h1 class="page-title">Statistiques – <em><?= e($festival['nom']) ?></em></h1>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card reveal">
      <div class="kpi-label">Revenus totaux</div>
      <div class="kpi-val small"><?= formatPrix((float)$global['revenus']) ?></div>
      <div class="kpi-sub">Billets confirmés</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Billets vendus</div>
      <div class="kpi-val"><?= number_format($global['billets_vendus'], 0, ',', ' ') ?></div>
      <div class="kpi-sub"><?= $global['resa_confirmees'] ?> réservations</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Panier moyen</div>
      <div class="kpi-val small"><?= formatPrix((float)$global['panier_moyen']) ?></div>
      <div class="kpi-sub">Par réservation</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">Annulations</div>
      <div class="kpi-val"><?= $global['resa_annulees'] ?></div>
      <div class="kpi-sub">Sur <?= $global['total_resa'] ?> total</div>
    </div>
  </div>

  <div class="stats-grid-2">

    <!-- Types de billets -->
    <div class="panel-card reveal">
      <div class="panel-card-header">
        <p class="panel-card-title">Ventes par type de billet</p>
      </div>
      <?php
      $maxRev = !empty($parType) ? max(array_column($parType, 'revenus')) ?: 1 : 1;
      foreach ($parType as $t):
        $pct = $maxRev > 0 ? round($t['revenus'] / $maxRev * 100) : 0;
      ?>
        <div class="type-row">
          <div class="type-row-header">
            <span class="type-row-nom"><?= e($t['nom']) ?></span>
            <span class="type-row-rev"><?= formatPrix((float)$t['revenus']) ?></span>
          </div>
          <div class="type-bar">
            <div class="type-bar-fill" style="width:<?= $pct ?>%"></div>
          </div>
          <div class="type-row-sub">
            <span><?= $t['vendu'] ?><?= $t['quantite'] ? ' / ' . $t['quantite'] : '' ?> billet(s)</span>
            <span><?= formatPrix((float)$t['prix']) ?> / unité</span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Réservations par ville -->
    <div class="panel-card reveal">
      <div class="panel-card-header">
        <p class="panel-card-title">Participants par ville</p>
      </div>
      <?php foreach ($parVille as $v): ?>
        <div class="ville-row">
          <div class="ville-row-top">
            <span class="ville-row-nom"><?= e($v['ville']) ?></span>
            <span class="ville-row-nb"><?= $v['nb'] ?></span>
          </div>
          <div class="ville-bar">
            <div class="ville-bar-fill" style="width:<?= round($v['nb']/$maxVille*100) ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($parVille)): ?>
        <p style="padding:2rem; text-align:center; color:var(--text-soft); font-size:.85rem;">Aucune donnée disponible.</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Graphe ventes / jour -->
  <?php if (!empty($parJour)): ?>
  <div class="panel-card reveal">
    <div class="panel-card-header">
      <p class="panel-card-title">Évolution des ventes (30 derniers jours)</p>
    </div>
    <?php
    $maxJour = max(array_column($parJour, 'revenus')) ?: 1;
    ?>
    <div class="chart-area">
      <div class="chart-bars">
        <?php foreach ($parJour as $j): ?>
          <div class="chart-bar-wrap">
            <div class="chart-bar"
                 style="height:<?= max(2, round($j['revenus']/$maxJour*100)) ?>%"
                 title="<?= dateFormatFr($j['jour']) ?> : <?= formatPrix((float)$j['revenus']) ?> · <?= $j['nb'] ?> résa"></div>
            <span class="chart-bar-label"><?= date('d/m', strtotime($j['jour'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>