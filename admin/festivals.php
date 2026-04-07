<?php
// ============================================================
//  XwéDò – Gestion des festivals (admin)
//  Fichier : admin/festivals.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo = getDB();

// ── Filtres ──────────────────────────────────────────────────
$statut    = getPropre('statut', '');
$q         = getPropre('q', '');
$page      = getPropre('page', 1);
$parPage   = 20;

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifierCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $fid    = (int) ($_POST['festival_id'] ?? 0);

    $statutMap = [
        'publier'  => 'publie',
        'archiver' => 'archive',
        'brouillon'=> 'brouillon',
    ];

    if (isset($statutMap[$action]) && $fid) {
        $pdo->prepare("UPDATE festivals SET statut=? WHERE id=?")
            ->execute([$statutMap[$action], $fid]);
        // Notifier si publication
        if ($action === 'publier') {
            $f = $pdo->prepare("SELECT organisateur_id, nom FROM festivals WHERE id=?");
            $f->execute([$fid]); $f = $f->fetch();
            if ($f) {
                $pdo->prepare("INSERT INTO notifications (utilisateur_id, titre, message, lien) VALUES (?,?,?,?)")
                    ->execute([
                        $f['organisateur_id'],
                        'Festival publié – ' . $f['nom'],
                        'Votre festival "' . $f['nom'] . '" est maintenant visible sur XwéDò.',
                        url('festival.php?slug=' . slugifier($f['nom']))
                    ]);
            }
        }
        setFlash('succes', 'Statut mis à jour.');
        rediriger('admin/festivals.php?' . http_build_query(array_filter(['statut'=>$statut,'q'=>$q,'page'=>$page])));
    }

    if ($action === 'supprimer' && $fid) {
        $pdo->prepare("DELETE FROM festivals WHERE id=?")->execute([$fid]);
        setFlash('succes', 'Festival supprimé définitivement.');
        rediriger('admin/festivals.php');
    }
}

// ── Requête ──────────────────────────────────────────────────
$where = []; $params = [];
if (!empty($statut)) { $where[] = "f.statut = ?"; $params[] = $statut; }
if (!empty($q))      { $where[] = "(f.nom LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $pdo->prepare("SELECT COUNT(DISTINCT f.id) FROM festivals f JOIN utilisateurs u ON f.organisateur_id = u.id $whereSQL");
$total->execute($params); $total = (int) $total->fetchColumn();
$pag = paginer($total, $parPage, $page);

$festivals = $pdo->prepare("
    SELECT f.*, c.nom AS cat_nom, c.icone AS cat_icone,
           u.prenom AS org_prenom, u.nom AS org_nom,
           COUNT(DISTINCT r.id) AS nb_resa,
           COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.prix_total END),0) AS revenus
    FROM festivals f
    LEFT JOIN categories_festival c ON f.categorie_id = c.id
    JOIN utilisateurs u ON f.organisateur_id = u.id
    LEFT JOIN reservations r ON f.id = r.festival_id
    $whereSQL
    GROUP BY f.id
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?
");
$festivals->execute([...$params, $parPage, $pag['offset']]);
$festivals = $festivals->fetchAll();

$pageTitre = 'Festivals – Administration XwéDò';

$pageCSS = <<<CSS
.admin-page { padding: 7rem 5% 4rem; max-width: 1280px; margin: 0 auto; }
.admin-nav {
  display: flex; gap: .5rem; margin-bottom: 2rem; flex-wrap: wrap;
  margin-top: -1.5rem;
  position: relative;
  z-index: 2;
}
.admin-nav-link {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .55rem 1.1rem;
  border: 1px solid rgba(196,98,45,.15); border-radius: var(--radius-full);
  font-size: .78rem; color: var(--text-mid); background: var(--white);
  transition: all var(--transition);
}
.admin-nav-link:hover, .admin-nav-link.active {
  background: var(--terracotta); color: var(--white); border-color: var(--terracotta);
}
.page-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 1.5rem; margin-bottom: 2rem; flex-wrap: wrap;
}
.page-title {
  font-family: var(--font-serif);
  font-size: clamp(1.6rem,3vw,2.4rem);
  font-weight: 300; color: var(--dusk);
}
.page-title em { font-style: italic; color: var(--terracotta); }

/* Filtres */
.filters-bar {
  display: flex; align-items: center; gap: 1rem;
  flex-wrap: wrap; margin-bottom: 1.5rem;
}
.filter-search {
  display: flex; align-items: center;
  background: var(--white);
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-full); overflow: hidden;
  flex: 1; min-width: 220px;
}
.filter-search input {
  flex: 1; padding: .65rem 1rem;
  border: none; outline: none;
  font-size: .85rem; font-family: var(--font-sans); background: transparent;
}
.filter-search button {
  padding: .65rem 1.1rem;
  background: var(--terracotta); color: var(--white); border: none;
  cursor: pointer; display: flex; align-items: center;
}
.filter-tabs {
  display: flex; gap: .3rem; flex-wrap: wrap;
}
.filter-tab {
  padding: .5rem 1rem;
  border: 1px solid rgba(196,98,45,.15);
  border-radius: var(--radius-full);
  font-size: .78rem; color: var(--text-mid); background: var(--white);
  transition: all var(--transition); cursor: pointer;
  text-decoration: none; white-space: nowrap;
}
.filter-tab:hover, .filter-tab.active {
  background: var(--terracotta); color: var(--white); border-color: var(--terracotta);
}

/* Table */
.admin-table-wrap { background: var(--white); border: 1px solid rgba(196,98,45,.1); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th {
  padding: .8rem 1.2rem;
  font-size: .68rem; font-weight: 500; letter-spacing: .12em;
  text-transform: uppercase; color: var(--text-soft); text-align: left;
  background: rgba(237,224,200,.35); border-bottom: 1px solid rgba(196,98,45,.08);
}
.admin-table td {
  padding: 1rem 1.2rem; font-size: .83rem; color: var(--text-mid);
  border-bottom: 1px solid rgba(196,98,45,.05); vertical-align: middle;
}
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: rgba(196,98,45,.02); }
.td-festival {
  display: flex; align-items: center; gap: .8rem;
}
.td-img {
  width: 42px; height: 42px; border-radius: var(--radius-sm);
  object-fit: cover; flex-shrink: 0;
}
.td-nom { font-weight: 500; color: var(--text); font-size: .88rem; }
.td-cat { font-size: .7rem; color: var(--text-soft); }
.td-actions { display: flex; gap: .4rem; flex-wrap: wrap; }
.ta-btn {
  padding: .35rem .75rem;
  border: 1px solid rgba(196,98,45,.18); border-radius: var(--radius-sm);
  font-size: .7rem; color: var(--text-mid); background: none; cursor: pointer;
  transition: all var(--transition); white-space: nowrap; text-decoration: none;
  display: inline-block;
}
.ta-btn:hover          { border-color: var(--terracotta); color: var(--terracotta); }
.ta-btn.green:hover    { border-color: #27AE60; color: #27AE60; }
.ta-btn.red:hover      { border-color: #C0392B; color: #C0392B; background: rgba(192,57,43,.04); }
.empty-msg { padding: 3rem; text-align: center; color: var(--text-soft); }

@media (max-width: 900px) {
  .admin-table th:nth-child(4),
  .admin-table td:nth-child(4),
  .admin-table th:nth-child(5),
  .admin-table td:nth-child(5) { display: none; }
}
CSS;

require_once '../includes/header.php';
?>

<div class="admin-page">

  <nav class="admin-nav">
    <a href="<?= url('admin/dashboard.php') ?>"     class="admin-nav-link">Vue d'ensemble</a>
    <a href="<?= url('admin/festivals.php') ?>"     class="admin-nav-link active">Festivals</a>
    <a href="<?= url('admin/organisateurs.php') ?>" class="admin-nav-link">Organisateurs</a>
    <a href="<?= url('admin/stats.php') ?>"         class="admin-nav-link">Statistiques</a>
  </nav>

  <div class="page-header">
    <h1 class="page-title">Gestion des <em>festivals</em></h1>
    <span style="font-size:.85rem; color:var(--text-soft);"><?= $total ?> festival<?= $total > 1 ? 's' : '' ?></span>
  </div>

  <!-- Filtres -->
  <div class="filters-bar">
    <form method="GET" class="filter-search">
      <?php if ($statut): ?><input type="hidden" name="statut" value="<?= e($statut) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Rechercher par nom, organisateur…">
      <button type="submit">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
    </form>
    <div class="filter-tabs">
      <?php
      $tabs = [''=> 'Tous', 'publie'=>'Publiés', 'en_attente'=>'En attente', 'brouillon'=>'Brouillons', 'archive'=>'Archivés'];
      foreach ($tabs as $val => $label):
      ?>
        <a href="?statut=<?= e($val) ?><?= $q ? '&q='.e($q) : '' ?>"
           class="filter-tab <?= $statut === $val ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Table -->
  <div class="admin-table-wrap">
    <?php if (empty($festivals)): ?>
      <p class="empty-msg">Aucun festival trouvé.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Festival</th>
            <th>Organisateur</th>
            <th>Dates</th>
            <th>Billets</th>
            <th>Revenus</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($festivals as $f): ?>
            <tr>
              <td>
                <div class="td-festival">
                  <img src="<?= imgUrl($f['image_principale']) ?>" alt="" class="td-img">
                  <div>
                    <div class="td-nom"><?= e(tronquer($f['nom'], 32)) ?></div>
                    <div class="td-cat"><?= e($f['cat_icone'] ?? '') ?> <?= e($f['cat_nom'] ?? '–') ?></div>
                  </div>
                </div>
              </td>
              <td><?= e($f['org_prenom'] . ' ' . $f['org_nom']) ?></td>
              <td style="white-space:nowrap;"><?= periodeFestival($f['date_debut'], $f['date_fin']) ?></td>
              <td><?= number_format($f['nb_resa'], 0, ',', ' ') ?></td>
              <td style="font-weight:500; color:var(--terracotta);"><?= formatPrix((float)$f['revenus']) ?></td>
              <td>
                <span class="badge <?= match($f['statut']) {
                  'publie'     => 'badge-green',
                  'en_attente' => 'badge-ochre',
                  'archive'    => 'badge-sage',
                  default      => 'badge-blue'
                } ?>">
                  <?= match($f['statut']) { 'publie'=>'Publié', 'en_attente'=>'En attente', 'archive'=>'Archivé', default=>'Brouillon' } ?>
                </span>
              </td>
              <td>
                <div class="td-actions">
                  <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" target="_blank" class="ta-btn">Voir</a>

                  <?php if ($f['statut'] !== 'publie'): ?>
                    <form method="POST" style="display:contents">
                      <?= csrfField() ?>
                      <input type="hidden" name="festival_id" value="<?= $f['id'] ?>">
                      <button type="submit" name="action" value="publier" class="ta-btn green">Publier</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($f['statut'] !== 'archive'): ?>
                    <form method="POST" style="display:contents">
                      <?= csrfField() ?>
                      <input type="hidden" name="festival_id" value="<?= $f['id'] ?>">
                      <button type="submit" name="action" value="archiver" class="ta-btn">Archiver</button>
                    </form>
                  <?php endif; ?>

                  <form method="POST" style="display:contents" onsubmit="return confirm('Supprimer définitivement ce festival et toutes ses données ?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="festival_id" value="<?= $f['id'] ?>">
                    <button type="submit" name="action" value="supprimer" class="ta-btn red">Suppr.</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?= htmlPagination($pag, url('admin/festivals.php?' . http_build_query(array_filter(['statut'=>$statut,'q'=>$q])))) ?>

</div>

<?php require_once '../includes/footer.php'; ?>