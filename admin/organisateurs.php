<?php
// ============================================================
//  XwéDò – Gestion des utilisateurs & organisateurs (admin)
//  Fichier : admin/organisateurs.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo     = getDB();
$role    = getPropre('role', '');
$q       = getPropre('q', '');
$page    = getPropre('page', 1);
$parPage = 25;

// ── Actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifierCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $uid    = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'promouvoir' && $uid) {
        $pdo->prepare("UPDATE utilisateurs SET role='organisateur' WHERE id=? AND role='participant'")->execute([$uid]);
        $pdo->prepare("INSERT INTO notifications (utilisateur_id, titre, message) VALUES (?,?,?)")
            ->execute([$uid, 'Compte organisateur activé', 'Félicitations ! Votre compte a été promu organisateur. Vous pouvez maintenant créer des festivals.']);
        setFlash('succes', 'Utilisateur promu organisateur.');
        rediriger('admin/organisateurs.php');
    }

    if ($action === 'desactiver' && $uid) {
        if ($uid !== userId()) {
            $pdo->prepare("UPDATE utilisateurs SET actif = 1 - actif WHERE id=?")->execute([$uid]);
            setFlash('info', 'Statut utilisateur modifié.');
        }
        rediriger('admin/organisateurs.php');
    }

    if ($action === 'changer_role' && $uid) {
        $nouveauRole = $_POST['nouveau_role'] ?? '';
        if (in_array($nouveauRole, ['participant','organisateur','admin']) && $uid !== userId()) {
            $pdo->prepare("UPDATE utilisateurs SET role=? WHERE id=?")->execute([$nouveauRole, $uid]);
            setFlash('succes', 'Rôle mis à jour.');
        }
        rediriger('admin/organisateurs.php');
    }

    // Demandes organisateur
    if ($action === 'approuver_demande') {
        $did = (int) ($_POST['demande_id'] ?? 0);
        $demande = $pdo->prepare("SELECT * FROM demandes_organisateur WHERE id=?");
        $demande->execute([$did]); $demande = $demande->fetch();
        if ($demande) {
            $pdo->prepare("UPDATE demandes_organisateur SET statut='approuvee' WHERE id=?")->execute([$did]);
            $pdo->prepare("UPDATE utilisateurs SET role='organisateur' WHERE id=?")->execute([$demande['utilisateur_id']]);
            $pdo->prepare("INSERT INTO notifications (utilisateur_id, titre, message) VALUES (?,?,?)")
                ->execute([$demande['utilisateur_id'], 'Demande approuvée !', 'Votre demande pour devenir organisateur a été approuvée. Bienvenue dans l\'espace organisateur XwéDò !']);
            setFlash('succes', 'Demande approuvée, compte organisateur activé.');
        }
        rediriger('admin/organisateurs.php');
    }

    if ($action === 'rejeter_demande') {
        $did = (int) ($_POST['demande_id'] ?? 0);
        $note = postPropre('note_admin');
        $pdo->prepare("UPDATE demandes_organisateur SET statut='rejetee', note_admin=? WHERE id=?")->execute([$note ?: null, $did]);
        setFlash('info', 'Demande rejetée.');
        rediriger('admin/organisateurs.php');
    }
}

// ── Demandes en attente ───────────────────────────────────────
$demandes = $pdo->query("
    SELECT d.*, u.prenom, u.nom, u.email
    FROM demandes_organisateur d
    JOIN utilisateurs u ON d.utilisateur_id = u.id
    WHERE d.statut = 'en_attente'
    ORDER BY d.created_at ASC
")->fetchAll();

// ── Liste utilisateurs ────────────────────────────────────────
$where = []; $params = [];
if (!empty($role)) { $where[] = "u.role = ?"; $params[] = $role; }
if (!empty($q))    { $where[] = "(u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs u $whereSQL");
$total->execute($params); $total = (int)$total->fetchColumn();
$pag = paginer($total, $parPage, $page);

$users = $pdo->prepare("
    SELECT u.*,
           COUNT(DISTINCT f.id) AS nb_festivals,
           COUNT(DISTINCT r.id) AS nb_reservations
    FROM utilisateurs u
    LEFT JOIN festivals f    ON u.id = f.organisateur_id
    LEFT JOIN reservations r ON u.id = r.utilisateur_id
    $whereSQL
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$users->execute([...$params, $parPage, $pag['offset']]);
$users = $users->fetchAll();

$pageTitre = 'Utilisateurs – Administration XwéDò';

$pageCSS = <<<CSS
.admin-page { padding: 7rem 5% 4rem; max-width: 1280px; margin: 0 auto; }
.admin-nav { display: flex; gap: .5rem; margin-bottom: 2rem; flex-wrap: wrap; margin-top: 3.7rem; }
.admin-nav-link { display: inline-flex; align-items: center; gap: .5rem; padding: .55rem 1.1rem; border: 1px solid rgba(196,98,45,.15); border-radius: var(--radius-full); font-size: .78rem; color: var(--text-mid); background: var(--white); transition: all var(--transition); }
.admin-nav-link:hover, .admin-nav-link.active { background: var(--terracotta); color: var(--white); border-color: var(--terracotta); }
.page-title { font-family: var(--font-serif); font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 300; color: var(--dusk); margin-bottom: 2rem; }
.page-title em { font-style: italic; color: var(--terracotta); }

/* Demandes */
.demandes-section { margin-bottom: 2.5rem; }
.demandes-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: 1rem;
  display: flex; align-items: center; gap: .6rem;
}
.demandes-title .count {
  background: var(--terracotta); color: var(--white);
  font-size: .65rem; font-weight: 600;
  min-width: 18px; height: 18px; border-radius: 10px;
  display: inline-flex; align-items: center; justify-content: center; padding: 0 4px;
}
.demande-card {
  background: var(--white);
  border: 1px solid rgba(212,168,83,.25);
  border-left: 3px solid var(--ochre);
  border-radius: var(--radius-md);
  padding: 1.2rem 1.4rem;
  margin-bottom: .8rem;
  box-shadow: var(--shadow-sm);
}
.demande-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.demande-user { font-weight: 500; color: var(--text); font-size: .9rem; }
.demande-email { font-size: .75rem; color: var(--text-soft); }
.demande-org { font-size: .82rem; color: var(--text-mid); margin-top: .3rem; }
.demande-msg { font-size: .82rem; color: var(--text-mid); margin: .8rem 0; line-height: 1.6; font-style: italic; }
.demande-actions { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
.btn-approuver { padding: .55rem 1.2rem; background: rgba(39,174,96,.1); color: #1A6B3C; border: 1px solid rgba(39,174,96,.25); border-radius: var(--radius-sm); font-size: .78rem; font-weight: 500; cursor: pointer; transition: all var(--transition); }
.btn-approuver:hover { background: #27AE60; color: var(--white); }
.btn-rejeter-d { padding: .55rem 1.2rem; background: rgba(192,57,43,.08); color: #8B1A1A; border: 1px solid rgba(192,57,43,.2); border-radius: var(--radius-sm); font-size: .78rem; font-weight: 500; cursor: pointer; transition: all var(--transition); }
.btn-rejeter-d:hover { background: #C0392B; color: var(--white); }

/* Filtres + table */
.filters-bar { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.filter-search { display: flex; align-items: center; background: var(--white); border: 1.5px solid rgba(196,98,45,.18); border-radius: var(--radius-full); overflow: hidden; flex: 1; min-width: 220px; }
.filter-search input { flex: 1; padding: .65rem 1rem; border: none; outline: none; font-size: .85rem; font-family: var(--font-sans); background: transparent; }
.filter-search button { padding: .65rem 1.1rem; background: var(--terracotta); color: var(--white); border: none; cursor: pointer; display: flex; align-items: center; }
.filter-tabs { display: flex; gap: .3rem; flex-wrap: wrap; }
.filter-tab { padding: .5rem 1rem; border: 1px solid rgba(196,98,45,.15); border-radius: var(--radius-full); font-size: .78rem; color: var(--text-mid); background: var(--white); transition: all var(--transition); cursor: pointer; text-decoration: none; }
.filter-tab:hover, .filter-tab.active { background: var(--terracotta); color: var(--white); border-color: var(--terracotta); }

.admin-table-wrap { background: var(--white); border: 1px solid rgba(196,98,45,.1); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table th { padding: .8rem 1.2rem; font-size: .68rem; font-weight: 500; letter-spacing: .12em; text-transform: uppercase; color: var(--text-soft); text-align: left; background: rgba(237,224,200,.35); border-bottom: 1px solid rgba(196,98,45,.08); }
.admin-table td { padding: .9rem 1.2rem; font-size: .82rem; color: var(--text-mid); border-bottom: 1px solid rgba(196,98,45,.05); vertical-align: middle; }
.admin-table tr:last-child td { border-bottom: none; }
.admin-table tr:hover td { background: rgba(196,98,45,.02); }
.user-cell { display: flex; align-items: center; gap: .8rem; }
.user-av { width: 34px; height: 34px; border-radius: 50%; background: var(--sand); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-family: var(--font-serif); font-size: .9rem; color: var(--terracotta); font-weight: 600; }
.user-nom { font-weight: 500; color: var(--text); font-size: .85rem; }
.user-email { font-size: .72rem; color: var(--text-soft); }
.ta-btn { padding: .32rem .7rem; border: 1px solid rgba(196,98,45,.18); border-radius: var(--radius-sm); font-size: .7rem; color: var(--text-mid); background: none; cursor: pointer; transition: all var(--transition); }
.ta-btn:hover { border-color: var(--terracotta); color: var(--terracotta); }
.ta-btn.danger:hover { border-color: #C0392B; color: #C0392B; }
.inactif td { opacity: .55; }

@media (max-width: 768px) {
  .admin-table th:nth-child(4), .admin-table td:nth-child(4),
  .admin-table th:nth-child(5), .admin-table td:nth-child(5) { display: none; }
}
CSS;

require_once '../includes/header.php';
?>

<div class="admin-page">

  <nav class="admin-nav">
    <a href="<?= url('admin/dashboard.php') ?>"     class="admin-nav-link">Vue d'ensemble</a>
    <a href="<?= url('admin/festivals.php') ?>"     class="admin-nav-link">Festivals</a>
    <a href="<?= url('admin/organisateurs.php') ?>" class="admin-nav-link active">Organisateurs</a>
    <a href="<?= url('admin/stats.php') ?>"         class="admin-nav-link">Statistiques</a>
  </nav>

  <h1 class="page-title">Gestion des <em>utilisateurs</em></h1>

  <!-- Demandes organisateur en attente -->
  <?php if (!empty($demandes)): ?>
  <div class="demandes-section">
    <p class="demandes-title">
      Demandes organisateur en attente
      <span class="count"><?= count($demandes) ?></span>
    </p>
    <?php foreach ($demandes as $d): ?>
      <div class="demande-card">
        <div class="demande-top">
          <div>
            <p class="demande-user"><?= e($d['prenom'] . ' ' . $d['nom']) ?></p>
            <p class="demande-email"><?= e($d['email']) ?></p>
            <p class="demande-org">Organisation : <strong><?= e($d['organisation']) ?></strong></p>
          </div>
          <span style="font-size:.72rem; color:var(--text-soft);"><?= dateFormatFr($d['created_at']) ?></span>
        </div>
        <?php if (!empty($d['message'])): ?>
          <p class="demande-msg">"<?= e(tronquer($d['message'], 200)) ?>"</p>
        <?php endif; ?>
        <div class="demande-actions">
          <form method="POST" style="display:contents">
            <?= csrfField() ?>
            <input type="hidden" name="demande_id" value="<?= $d['id'] ?>">
            <button type="submit" name="action" value="approuver_demande" class="btn-approuver">✓ Approuver</button>
          </form>
          <form method="POST" style="display:contents">
            <?= csrfField() ?>
            <input type="hidden" name="demande_id" value="<?= $d['id'] ?>">
            <button type="submit" name="action" value="rejeter_demande" class="btn-rejeter-d">✗ Rejeter</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Filtres -->
  <div class="filters-bar">
    <form method="GET" class="filter-search">
      <?php if ($role): ?><input type="hidden" name="role" value="<?= e($role) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Rechercher par nom, email…">
      <button type="submit">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
    </form>
    <div class="filter-tabs">
      <a href="?<?= $q ? 'q='.e($q) : '' ?>" class="filter-tab <?= $role === '' ? 'active' : '' ?>">Tous (<?= $total ?>)</a>
      <?php
      $roleCounts = $pdo->query("SELECT role, COUNT(*) as nb FROM utilisateurs GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
      $roleLabels = ['participant'=>'Participants','organisateur'=>'Organisateurs','admin'=>'Admins'];
      foreach ($roleLabels as $rv => $rl):
      ?>
        <a href="?role=<?= $rv ?><?= $q ? '&q='.e($q) : '' ?>"
           class="filter-tab <?= $role === $rv ? 'active' : '' ?>">
          <?= $rl ?> (<?= $roleCounts[$rv] ?? 0 ?>)
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Table -->
  <div class="admin-table-wrap">
    <?php if (empty($users)): ?>
      <p style="padding:3rem; text-align:center; color:var(--text-soft);">Aucun utilisateur trouvé.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Rôle</th>
            <th>Ville</th>
            <th>Festivals</th>
            <th>Réservations</th>
            <th>Inscrit le</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr class="<?= !$u['actif'] ? 'inactif' : '' ?>">
              <td>
                <div class="user-cell">
                  <div class="user-av"><?= mb_strtoupper(mb_substr($u['prenom'], 0, 1, 'UTF-8'), 'UTF-8') ?></div>
                  <div>
                    <div class="user-nom"><?= e($u['prenom'] . ' ' . $u['nom']) ?></div>
                    <div class="user-email"><?= e($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge <?= match($u['role']) { 'admin'=>'badge-red', 'organisateur'=>'badge-ochre', default=>'badge-sage' } ?>">
                  <?= match($u['role']) { 'admin'=>'Admin', 'organisateur'=>'Organisateur', default=>'Participant' } ?>
                </span>
              </td>
              <td><?= e($u['ville'] ?? '–') ?></td>
              <td><?= $u['nb_festivals'] ?></td>
              <td><?= $u['nb_reservations'] ?></td>
              <td style="font-size:.75rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div style="display:flex; gap:.4rem; flex-wrap:wrap;">
                  <?php if ($u['role'] === 'participant'): ?>
                    <form method="POST" style="display:contents">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" name="action" value="promouvoir" class="ta-btn">→ Orga</button>
                    </form>
                  <?php endif; ?>
                  <?php if ($u['id'] !== userId()): ?>
                    <form method="POST" style="display:contents">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" name="action" value="desactiver" class="ta-btn <?= !$u['actif'] ? '' : 'danger' ?>">
                        <?= $u['actif'] ? 'Désactiver' : 'Réactiver' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?= htmlPagination($pag, url('admin/organisateurs.php?' . http_build_query(array_filter(['role'=>$role,'q'=>$q])))) ?>

</div>

<?php require_once '../includes/footer.php'; ?>