<?php
// ============================================================
//  XwéDò – Dashboard Administrateur
//  Fichier : admin/dashboard.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo = getDB();

// ── KPIs globaux ─────────────────────────────────────────────
$kpi = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM utilisateurs WHERE role='participant')                          AS nb_participants,
        (SELECT COUNT(*) FROM utilisateurs WHERE role='organisateur')                         AS nb_organisateurs,
        (SELECT COUNT(*) FROM festivals WHERE statut='publie')                                AS nb_festivals_actifs,
        (SELECT COUNT(*) FROM festivals WHERE statut='en_attente')                            AS nb_en_attente,
        (SELECT COUNT(*) FROM reservations WHERE statut='confirmee')                          AS nb_reservations,
        (SELECT COALESCE(SUM(prix_total),0) FROM reservations WHERE statut='confirmee')       AS revenus_total,
        (SELECT COUNT(*) FROM festivals)                                                       AS nb_festivals_total,
        (SELECT COUNT(*) FROM reservations WHERE DATE(created_at) = CURDATE())                AS resa_aujourd_hui
")->fetch();

// ── Festivals en attente de validation ───────────────────────
$enAttente = $pdo->query("
    SELECT f.*, c.nom AS cat_nom, c.icone AS cat_icone,
           u.nom AS org_nom, u.prenom AS org_prenom, u.email AS org_email
    FROM festivals f
    LEFT JOIN categories_festival c ON f.categorie_id = c.id
    JOIN utilisateurs u ON f.organisateur_id = u.id
    WHERE f.statut = 'en_attente'
    ORDER BY f.created_at ASC
")->fetchAll();

// ── Dernières inscriptions ───────────────────────────────────
$derniersUsers = $pdo->query("
    SELECT * FROM utilisateurs
    ORDER BY created_at DESC LIMIT 8
")->fetchAll();

// ── Dernières réservations ───────────────────────────────────
$dernieresResa = $pdo->query("
    SELECT r.*, f.nom AS festival_nom, u.prenom, u.nom AS user_nom
    FROM reservations r
    JOIN festivals f    ON r.festival_id    = f.id
    JOIN utilisateurs u ON r.utilisateur_id = u.id
    ORDER BY r.created_at DESC LIMIT 6
")->fetchAll();

// ── Traitement validation/rejet ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifierCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $fid    = (int) ($_POST['festival_id'] ?? 0);

    if ($action === 'valider' && $fid) {
        $pdo->prepare("UPDATE festivals SET statut='publie' WHERE id=?")->execute([$fid]);
        // Notifier l'organisateur
        $f = $pdo->prepare("SELECT organisateur_id, nom FROM festivals WHERE id=?");
        $f->execute([$fid]); $f = $f->fetch();
        if ($f) {
            $pdo->prepare("INSERT INTO notifications (utilisateur_id, titre, message, lien) VALUES (?,?,?,?)")
                ->execute([
                    $f['organisateur_id'],
                    'Festival publié – ' . $f['nom'],
                    'Votre festival "' . $f['nom'] . '" a été validé et est maintenant visible sur XwéDò.',
                    url('festival.php?slug=' . slugifier($f['nom']))
                ]);
        }
        setFlash('succes', 'Festival publié avec succès.');
        rediriger('admin/dashboard.php');
    }

    if ($action === 'rejeter' && $fid) {
        $pdo->prepare("UPDATE festivals SET statut='brouillon' WHERE id=?")->execute([$fid]);
        setFlash('info', 'Festival renvoyé en brouillon.');
        rediriger('admin/dashboard.php');
    }
}

$pageTitre = 'Administration – XwéDò';

$pageCSS = <<<CSS
/* ── Admin global ────────────────────────────────────────── */
.admin-page { padding: 7rem 5% 4rem; max-width: 1280px; margin: 0 auto; }

.admin-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 1.5rem; margin-bottom: 2.8rem; flex-wrap: wrap;
}
.admin-title {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 3.5vw, 2.8rem);
  font-weight: 300; color: var(--dusk);
}
.admin-title em { font-style: italic; color: var(--terracotta); }
.admin-subtitle { font-size: .85rem; color: var(--text-soft); margin-top: .4rem; }

/* KPI */
.admin-kpi {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.2rem; margin-bottom: 2.8rem;
}
.kpi-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg); padding: 1.6rem 1.8rem;
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition), transform var(--transition);
}
.kpi-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
.kpi-card.accent { border-color: rgba(196,98,45,.3); background: rgba(196,98,45,.03); }
.kpi-label {
  font-size: .7rem; font-weight: 500;
  letter-spacing: .14em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: .8rem;
  display: flex; align-items: center; gap: .5rem;
}
.kpi-label svg { color: var(--terracotta); }
.kpi-val {
  font-family: var(--font-serif);
  font-size: 2.2rem; font-weight: 600;
  color: var(--terracotta); line-height: 1;
}
.kpi-val.sm { font-size: 1.4rem; }
.kpi-sub { font-size: .75rem; color: var(--text-soft); margin-top: .4rem; }

/* Badge alerte */
.alert-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: .35rem .9rem;
  background: rgba(192,57,43,.1); color: #8B1A1A;
  border-radius: var(--radius-full);
  font-size: .72rem; font-weight: 600;
  animation: pulse-badge 2s ease-in-out infinite;
}
@keyframes pulse-badge {
  0%,100% { box-shadow: 0 0 0 0 rgba(192,57,43,.3); }
  50%      { box-shadow: 0 0 0 6px rgba(192,57,43,0); }
}

/* Grille principale */
.admin-grid { display: grid; grid-template-columns: 1fr 360px; gap: 2rem; }

/* Panel carte */
.panel-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  overflow: hidden; box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.panel-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.2rem 1.6rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.panel-card-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase; color: var(--text-mid);
}

/* Card festival en attente */
.attente-card {
  padding: 1.4rem 1.6rem;
  border-bottom: 1px solid rgba(196,98,45,.06);
  transition: background var(--transition);
}
.attente-card:last-child { border-bottom: none; }
.attente-card:hover { background: rgba(196,98,45,.02); }
.attente-card-top {
  display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;
}
.attente-img {
  width: 56px; height: 56px; border-radius: var(--radius-sm);
  object-fit: cover; flex-shrink: 0;
}
.attente-info { flex: 1; min-width: 0; }
.attente-nom {
  font-family: var(--font-serif);
  font-size: 1.1rem; font-weight: 400; color: var(--dusk);
  margin-bottom: .2rem;
}
.attente-meta { font-size: .78rem; color: var(--text-soft); }
.attente-org  { font-size: .78rem; color: var(--text-mid); margin-top: .15rem; }
.attente-actions { display: flex; gap: .6rem; }
.btn-valider {
  flex: 1; padding: .6rem 1rem;
  background: rgba(39,174,96,.1); color: #1A6B3C;
  border: 1px solid rgba(39,174,96,.25);
  border-radius: var(--radius-sm); font-size: .78rem; font-weight: 500;
  cursor: pointer; transition: all var(--transition); text-align: center;
}
.btn-valider:hover { background: #27AE60; color: var(--white); border-color: #27AE60; }
.btn-rejeter {
  flex: 1; padding: .6rem 1rem;
  background: rgba(192,57,43,.08); color: #8B1A1A;
  border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); font-size: .78rem; font-weight: 500;
  cursor: pointer; transition: all var(--transition); text-align: center;
}
.btn-rejeter:hover { background: #C0392B; color: var(--white); border-color: #C0392B; }
.btn-voir-admin {
  padding: .6rem 1rem;
  background: none; color: var(--text-mid);
  border: 1px solid rgba(196,98,45,.2);
  border-radius: var(--radius-sm); font-size: .78rem;
  cursor: pointer; transition: all var(--transition); white-space: nowrap;
}
.btn-voir-admin:hover { border-color: var(--terracotta); color: var(--terracotta); }

/* Feed inscriptions */
.user-feed-item {
  display: flex; align-items: center; gap: 1rem;
  padding: .85rem 1.4rem;
  border-bottom: 1px solid rgba(196,98,45,.05);
  transition: background var(--transition);
}
.user-feed-item:last-child { border-bottom: none; }
.user-feed-item:hover { background: rgba(196,98,45,.02); }
.user-feed-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: var(--sand); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: .85rem; color: var(--terracotta);
  font-weight: 600;
}
.user-feed-nom { font-size: .85rem; font-weight: 500; color: var(--text); }
.user-feed-email { font-size: .72rem; color: var(--text-soft); }
.user-feed-role { margin-left: auto; }
.user-feed-date { font-size: .68rem; color: var(--text-soft); margin-top: 2px; text-align: right; }

/* Réservations récentes */
.resa-mini {
  display: flex; align-items: center; gap: .8rem;
  padding: .8rem 1.4rem;
  border-bottom: 1px solid rgba(196,98,45,.05);
}
.resa-mini:last-child { border-bottom: none; }
.resa-mini-info { flex: 1; min-width: 0; }
.resa-mini-nom   { font-size: .82rem; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.resa-mini-festi { font-size: .72rem; color: var(--text-soft); }
.resa-mini-prix  { font-size: .88rem; font-weight: 500; color: var(--terracotta); font-family: var(--font-serif); flex-shrink: 0; }

.empty-msg { padding: 2rem; text-align: center; color: var(--text-soft); font-size: .85rem; }

/* Nav admin */
.admin-nav {
  display: flex; gap: .5rem; margin-bottom: 2rem; flex-wrap: wrap;
  margin-top: 3.7rem;
}
.admin-nav-link {
  display: inline-flex; align-items: center; gap: .5rem;
  padding: .55rem 1.1rem;
  border: 1px solid rgba(196,98,45,.15);
  border-radius: var(--radius-full);
  font-size: .78rem; color: var(--text-mid);
  background: var(--white); transition: all var(--transition);
}
.admin-nav-link:hover,
.admin-nav-link.active {
  background: var(--terracotta); color: var(--white);
  border-color: var(--terracotta);
}
.admin-nav-link svg { flex-shrink: 0; }

@media (max-width: 1024px) { .admin-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px)  { .admin-kpi  { grid-template-columns: 1fr 1fr; } }
CSS;

require_once '../includes/header.php';
?>

<div class="admin-page">

  <!-- En-tête -->
  <div class="admin-header">
    <div>
      <h1 class="admin-title">Administration <em>XwéDò</em></h1>
      <p class="admin-subtitle"><?= dateFormatFr(date('Y-m-d')) ?> · Connecté en tant qu'administrateur</p>
    </div>
    <?php if (count($enAttente) > 0): ?>
      <span class="alert-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= count($enAttente) ?> festival<?= count($enAttente) > 1 ? 's' : '' ?> en attente
      </span>
    <?php endif; ?>
  </div>

  <!-- Navigation admin -->
  <nav class="admin-nav">
    <a href="<?= url('admin/dashboard.php') ?>" class="admin-nav-link active">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Vue d'ensemble
    </a>
    <a href="<?= url('admin/festivals.php') ?>" class="admin-nav-link">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      Festivals
    </a>
    <a href="<?= url('admin/organisateurs.php') ?>" class="admin-nav-link">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Organisateurs
    </a>
    <a href="<?= url('admin/stats.php') ?>" class="admin-nav-link">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Statistiques
    </a>
  </nav>

  <!-- KPIs -->
  <div class="admin-kpi">
    <div class="kpi-card reveal">
      <div class="kpi-label">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Participants
      </div>
      <div class="kpi-val"><?= number_format($kpi['nb_participants'], 0, ',', ' ') ?></div>
      <div class="kpi-sub">Comptes actifs</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Festivals actifs
      </div>
      <div class="kpi-val"><?= $kpi['nb_festivals_actifs'] ?></div>
      <div class="kpi-sub"><?= $kpi['nb_festivals_total'] ?> au total</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 9l3-3 3 3M2 15l3 3 3-3M13 6h8M13 12h8M13 18h8"/></svg>
        Réservations
      </div>
      <div class="kpi-val"><?= number_format($kpi['nb_reservations'], 0, ',', ' ') ?></div>
      <div class="kpi-sub"><?= $kpi['resa_aujourd_hui'] ?> aujourd'hui</div>
    </div>
    <div class="kpi-card accent reveal">
      <div class="kpi-label">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Revenus plateforme
      </div>
      <div class="kpi-val sm"><?= formatPrix((float)$kpi['revenus_total']) ?></div>
      <div class="kpi-sub">Billets confirmés</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        En attente
      </div>
      <div class="kpi-val" style="color: <?= $kpi['nb_en_attente'] > 0 ? '#E67E22' : 'var(--terracotta)' ?>">
        <?= $kpi['nb_en_attente'] ?>
      </div>
      <div class="kpi-sub">Festival<?= $kpi['nb_en_attente'] > 1 ? 's' : '' ?> à valider</div>
    </div>
    <div class="kpi-card reveal">
      <div class="kpi-label">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Organisateurs
      </div>
      <div class="kpi-val"><?= $kpi['nb_organisateurs'] ?></div>
      <div class="kpi-sub">Comptes orga</div>
    </div>
  </div>

  <!-- Grille principale -->
  <div class="admin-grid">

    <!-- Festivals en attente -->
    <div>
      <div class="panel-card reveal">
        <div class="panel-card-header">
          <span class="panel-card-title">Festivals en attente de validation</span>
          <a href="<?= url('admin/festivals.php?statut=en_attente') ?>" class="btn-link" style="font-size:.75rem;">Tout voir →</a>
        </div>
        <?php if (empty($enAttente)): ?>
          <p class="empty-msg">✓ Aucun festival en attente de validation.</p>
        <?php else: ?>
          <?php foreach ($enAttente as $f): ?>
            <div class="attente-card">
              <div class="attente-card-top">
                <img src="<?= imgUrl($f['image_principale']) ?>" alt="" class="attente-img">
                <div class="attente-info">
                  <p class="attente-nom"><?= e($f['nom']) ?></p>
                  <p class="attente-meta">
                    <?= e($f['cat_icone'] ?? '') ?> <?= e($f['cat_nom'] ?? 'Sans catégorie') ?>
                    · <?= periodeFestival($f['date_debut'], $f['date_fin']) ?>
                  </p>
                  <p class="attente-org">
                    Par <?= e($f['org_prenom'] . ' ' . $f['org_nom']) ?> — <?= e($f['org_email']) ?>
                  </p>
                </div>
              </div>
              <div class="attente-actions">
                <form method="POST" style="display:contents">
                  <?= csrfField() ?>
                  <input type="hidden" name="festival_id" value="<?= $f['id'] ?>">
                  <button type="submit" name="action" value="valider" class="btn-valider">✓ Publier</button>
                  <button type="submit" name="action" value="rejeter" class="btn-rejeter">✗ Rejeter</button>
                </form>
                <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" target="_blank" class="btn-voir-admin">Aperçu</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Réservations récentes -->
      <div class="panel-card reveal">
        <div class="panel-card-header">
          <span class="panel-card-title">Réservations récentes</span>
        </div>
        <?php foreach ($dernieresResa as $r): ?>
          <div class="resa-mini">
            <div class="resa-mini-info">
              <p class="resa-mini-nom"><?= e($r['prenom'] . ' ' . $r['user_nom']) ?></p>
              <p class="resa-mini-festi"><?= e(tronquer($r['festival_nom'], 32)) ?></p>
            </div>
            <span class="resa-mini-prix"><?= formatPrix((float)$r['prix_total']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (empty($dernieresResa)): ?>
          <p class="empty-msg">Aucune réservation.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dernières inscriptions -->
    <div>
      <div class="panel-card reveal">
        <div class="panel-card-header">
          <span class="panel-card-title">Dernières inscriptions</span>
          <a href="<?= url('admin/organisateurs.php') ?>" class="btn-link" style="font-size:.75rem;">Gérer →</a>
        </div>
        <?php foreach ($derniersUsers as $u): ?>
          <div class="user-feed-item">
            <div class="user-feed-avatar">
              <?= mb_strtoupper(mb_substr($u['prenom'], 0, 1, 'UTF-8'), 'UTF-8') ?>
            </div>
            <div style="flex:1; min-width:0;">
              <p class="user-feed-nom"><?= e($u['prenom'] . ' ' . $u['nom']) ?></p>
              <p class="user-feed-email"><?= e(tronquer($u['email'], 28)) ?></p>
            </div>
            <div class="user-feed-role">
              <span class="badge <?= match($u['role']) { 'admin'=>'badge-red', 'organisateur'=>'badge-ochre', default=>'badge-sage' } ?>" style="font-size:.62rem;">
                <?= match($u['role']) { 'admin'=>'Admin', 'organisateur'=>'Orga', default=>'Part.' } ?>
              </span>
              <p class="user-feed-date"><?= date('d/m/y', strtotime($u['created_at'])) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($derniersUsers)): ?>
          <p class="empty-msg">Aucun utilisateur.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php require_once '../includes/footer.php'; ?>