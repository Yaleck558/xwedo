<?php
// ============================================================
//  XwéDò – Dashboard Organisateur
//  Fichier : organisateur/dashboard.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('organisateur');

$pdo = getDB();
$uid = userId();

// ── Stats globales de l'organisateur ────────────────────────
$stats = $pdo->prepare("
    SELECT
        COUNT(DISTINCT f.id)                                          AS total_festivals,
        COUNT(DISTINCT CASE WHEN f.statut='publie'   THEN f.id END)  AS festivals_actifs,
        COUNT(DISTINCT CASE WHEN f.statut='brouillon' OR f.statut='en_attente' THEN f.id END) AS festivals_attente,
        COUNT(DISTINCT r.id)                                          AS total_reservations,
        COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.prix_total END), 0)           AS revenus_bruts,
        COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.montant_organisateur END), 0) AS revenus_nets,
        COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.commission_montant END), 0)   AS total_commission
    FROM festivals f
    LEFT JOIN reservations r ON f.id = r.festival_id
    WHERE f.organisateur_id = ?
");
$stats->execute([$uid]);
$stats = $stats->fetch();

// ── Mes festivals ────────────────────────────────────────────
$festivals = $pdo->prepare("
    SELECT f.*,
           c.nom AS categorie_nom, c.icone AS categorie_icone,
           COUNT(DISTINCT r.id)  AS nb_reservations,
           COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.prix_total END), 0) AS revenus
    FROM festivals f
    LEFT JOIN categories_festival c ON f.categorie_id = c.id
    LEFT JOIN reservations r        ON f.id = r.festival_id
    WHERE f.organisateur_id = ?
    GROUP BY f.id
    ORDER BY f.created_at DESC
    LIMIT 10
");
$festivals->execute([$uid]);
$festivals = $festivals->fetchAll();

// ── Dernières réservations ───────────────────────────────────
$dernieresResa = $pdo->prepare("
    SELECT r.*, f.nom AS festival_nom, u.prenom, u.nom AS user_nom, tb.nom AS type_nom
    FROM reservations r
    JOIN festivals f     ON r.festival_id     = f.id
    JOIN utilisateurs u  ON r.utilisateur_id  = u.id
    JOIN types_billet tb ON r.type_billet_id  = tb.id
    WHERE f.organisateur_id = ?
    ORDER BY r.created_at DESC
    LIMIT 8
");
$dernieresResa->execute([$uid]);
$dernieresResa = $dernieresResa->fetchAll();

$pageTitre = 'Tableau de bord – XwéDò';

$pageCSS = <<<CSS
/* ── Dashboard organisateur ─────────────────────────────── */
.dash-page { padding: 7rem 5% 4rem; max-width: 1280px; margin: 0 auto; }

.dash-header {
  display: flex; align-items: flex-end;
  justify-content: space-between; gap: 1.5rem;
  margin-bottom: 2.8rem; flex-wrap: wrap;
}
.dash-greeting {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 3.5vw, 2.8rem);
  font-weight: 300; color: var(--dusk); line-height: 1.1;
}
.dash-greeting em { font-style: italic; color: var(--terracotta); }
.dash-date { font-size: .82rem; color: var(--text-soft); margin-top: .4rem; }

/* Cartes stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.2rem; margin-bottom: 2.8rem;
}
.stat-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.6rem 1.8rem;
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition), transform var(--transition);
}
.stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.stat-card-label {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .14em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: .8rem;
  display: flex; align-items: center; gap: .5rem;
}
.stat-card-label svg { color: var(--terracotta); }
.stat-card-val {
  font-family: var(--font-serif);
  font-size: 2.4rem; font-weight: 600;
  color: var(--terracotta); line-height: 1;
}
.stat-card-sub { font-size: .78rem; color: var(--text-soft); margin-top: .4rem; }

/* Grille principale */
.dash-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2rem;
}

/* Table festivals */
.dash-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.dash-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.4rem 1.8rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.dash-card-title {
  font-size: .8rem; font-weight: 500;
  letter-spacing: .12em; text-transform: uppercase; color: var(--text-mid);
}

.fest-table { width: 100%; border-collapse: collapse; }
.fest-table th {
  padding: .7rem 1.2rem;
  font-size: .7rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-soft); text-align: left;
  background: rgba(237,224,200,.3);
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.fest-table td {
  padding: 1rem 1.2rem;
  font-size: .85rem; color: var(--text-mid);
  border-bottom: 1px solid rgba(196,98,45,.05);
  vertical-align: middle;
}
.fest-table tr:last-child td { border-bottom: none; }
.fest-table tr:hover td { background: rgba(196,98,45,.02); }
.fest-table-nom {
  font-weight: 500; color: var(--text);
  display: flex; align-items: center; gap: .6rem;
}
.fest-table-img {
  width: 38px; height: 38px; border-radius: var(--radius-sm);
  object-fit: cover; flex-shrink: 0;
}
.fest-table-actions { display: flex; gap: .5rem; }
.action-btn {
  padding: .4rem .8rem;
  border: 1px solid rgba(196,98,45,.2);
  border-radius: var(--radius-sm);
  font-size: .72rem; color: var(--text-mid);
  background: none; cursor: pointer;
  transition: all var(--transition);
  white-space: nowrap;
}
.action-btn:hover {
  border-color: var(--terracotta); color: var(--terracotta);
  background: rgba(196,98,45,.04);
}

/* Réservations récentes */
.resa-feed { display: flex; flex-direction: column; }
.resa-feed-item {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem 1.4rem;
  border-bottom: 1px solid rgba(196,98,45,.06);
  transition: background var(--transition);
}
.resa-feed-item:last-child { border-bottom: none; }
.resa-feed-item:hover { background: rgba(196,98,45,.02); }
.resa-feed-avatar {
  width: 34px; height: 34px; border-radius: 50%;
  background: var(--sand); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: .95rem; color: var(--terracotta);
  font-weight: 600;
}
.resa-feed-info { flex: 1; min-width: 0; }
.resa-feed-nom {
  font-size: .85rem; font-weight: 500; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.resa-feed-festival { font-size: .75rem; color: var(--text-soft); }
.resa-feed-right { text-align: right; flex-shrink: 0; }
.resa-feed-prix { font-size: .88rem; font-weight: 500; color: var(--terracotta); }
.resa-feed-date { font-size: .7rem; color: var(--text-soft); }

/* Vide */
.dash-empty {
  padding: 3rem; text-align: center; color: var(--text-soft);
}
.dash-empty svg { opacity: .25; margin: 0 auto 1rem; display: block; }
.dash-empty p { font-size: .88rem; }

@media (max-width: 1024px) {
  .dash-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .fest-table th:nth-child(3),
  .fest-table td:nth-child(3) { display: none; }
}
CSS;

require_once '../includes/header.php';
?>

<div class="dash-page">

  <!-- En-tête -->
  <div class="dash-header">
    <div>
      <h1 class="dash-greeting">
        Bonjour, <em><?= e(explode(' ', $_SESSION['user_nom'])[0]) ?></em> 👋
      </h1>
      <p class="dash-date"><?= dateFormatFr(date('Y-m-d')) ?></p>
    </div>
    <a href="<?= url('organisateur/creer-festival.php') ?>" class="btn-terra">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      <span>Nouveau festival</span>
    </a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card reveal">
      <div class="stat-card-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Mes festivals
      </div>
      <div class="stat-card-val"><?= $stats['total_festivals'] ?></div>
      <div class="stat-card-sub"><?= $stats['festivals_actifs'] ?> publié(s)</div>
    </div>
    <div class="stat-card reveal">
      <div class="stat-card-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M2 9l3-3 3 3M2 15l3 3 3-3M13 6h8M13 12h8M13 18h8"/></svg>
        Réservations
      </div>
      <div class="stat-card-val"><?= number_format($stats['total_reservations'], 0, ',', ' ') ?></div>
      <div class="stat-card-sub">Total confirmées</div>
    </div>
    <div class="stat-card reveal">
      <div class="stat-card-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Revenus nets
      </div>
      <div class="stat-card-val" style="font-size: 1.5rem"><?= formatPrix((float)$stats['revenus_nets']) ?></div>
      <div class="stat-card-sub" style="color:var(--text-soft);">
        Brut : <?= formatPrix((float)$stats['revenus_bruts']) ?><br>
        <span style="color:rgba(196,98,45,.6);">Commission XwéDò (5%) : <?= formatPrix((float)$stats['total_commission']) ?></span>
      </div>
    </div>
    <div class="stat-card reveal">
      <div class="stat-card-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        En attente
      </div>
      <div class="stat-card-val"><?= $stats['festivals_attente'] ?></div>
      <div class="stat-card-sub">Brouillon / validation</div>
    </div>
  </div>

  <!-- Grille principale -->
  <div class="dash-grid">

    <!-- Mes festivals -->
    <div class="dash-card reveal">
      <div class="dash-card-header">
        <span class="dash-card-title">Mes festivals</span>
        <a href="<?= url('organisateur/creer-festival.php') ?>" class="btn-link" style="font-size:.78rem;">
          + Nouveau
        </a>
      </div>
      <?php if (empty($festivals)): ?>
        <div class="dash-empty">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          <p>Aucun festival créé pour le moment.</p>
        </div>
      <?php else: ?>
        <table class="fest-table">
          <thead>
            <tr>
              <th>Festival</th>
              <th>Dates</th>
              <th>Billets</th>
              <th>Revenus</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($festivals as $f): ?>
              <tr>
                <td>
                  <div class="fest-table-nom">
                    <img src="<?= imgUrl($f['image_principale']) ?>" alt="" class="fest-table-img">
                    <?= e(tronquer($f['nom'], 30)) ?>
                  </div>
                </td>
                <td><?= periodeFestival($f['date_debut'], $f['date_fin']) ?></td>
                <td><?= number_format($f['nb_reservations'], 0, ',', ' ') ?></td>
                <td><?= formatPrix((float)$f['revenus']) ?></td>
                <td>
                  <span class="badge <?= match($f['statut']) {
                    'publie'      => 'badge-green',
                    'en_attente'  => 'badge-ochre',
                    'archive'     => 'badge-sage',
                    default       => 'badge-blue'
                  } ?>">
                    <?= match($f['statut']) {
                      'publie'     => 'Publié',
                      'en_attente' => 'En attente',
                      'archive'    => 'Archivé',
                      default      => 'Brouillon'
                    } ?>
                  </span>
                </td>
                <td>
                  <div class="fest-table-actions">
                    <a href="<?= url('organisateur/creer-festival.php?id=' . $f['id']) ?>" class="action-btn">Éditer</a>
                    <a href="<?= url('organisateur/statistiques.php?id=' . $f['id']) ?>"  class="action-btn">Stats</a>
                    <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>"              class="action-btn">Voir</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Réservations récentes -->
    <div class="dash-card reveal">
      <div class="dash-card-header">
        <span class="dash-card-title">Réservations récentes</span>
        <a href="<?= url('organisateur/billets.php') ?>" class="btn-link" style="font-size:.78rem;">Tout voir →</a>
      </div>
      <?php if (empty($dernieresResa)): ?>
        <div class="dash-empty">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round"><path d="M2 9l3-3 3 3M2 15l3 3 3-3M13 6h8M13 12h8M13 18h8"/></svg>
          <p>Aucune réservation pour le moment.</p>
        </div>
      <?php else: ?>
        <div class="resa-feed">
          <?php foreach ($dernieresResa as $r): ?>
            <div class="resa-feed-item">
              <div class="resa-feed-avatar">
                <?= mb_strtoupper(mb_substr($r['prenom'], 0, 1, 'UTF-8'), 'UTF-8') ?>
              </div>
              <div class="resa-feed-info">
                <p class="resa-feed-nom"><?= e($r['prenom'] . ' ' . $r['user_nom']) ?></p>
                <p class="resa-feed-festival"><?= e(tronquer($r['festival_nom'], 28)) ?> · <?= e($r['type_nom']) ?></p>
              </div>
              <div class="resa-feed-right">
                <p class="resa-feed-prix"><?= formatPrix((float)$r['prix_total']) ?></p>
                <p class="resa-feed-date"><?= date('d/m', strtotime($r['created_at'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once '../includes/footer.php'; ?>