<?php
// ============================================================
//  XwéDò – Profil utilisateur
//  Fichier : profil.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireConnecte();

$pdo    = getDB();
$tab    = getPropre('tab', 'profil');
$user   = utilisateurCourant();
$erreurs = [];

// ── Mise à jour du profil ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée.';
    } else {
        $action = $_POST['action'];

        if ($action === 'update_profil') {
            $nom    = postPropre('nom');
            $prenom = postPropre('prenom');
            $ville  = postPropre('ville');

            if (empty($nom) || empty($prenom)) {
                $erreurs[] = 'Nom et prénom sont requis.';
            } else {
                $pdo->prepare('UPDATE utilisateurs SET nom=?, prenom=?, ville=? WHERE id=?')
                    ->execute([$nom, $prenom, $ville ?: null, userId()]);
                $_SESSION['user_nom'] = $prenom . ' ' . $nom;
                setFlash('succes', 'Profil mis à jour avec succès.');
                rediriger('profil.php?tab=profil');
            }
        }

        if ($action === 'change_password') {
            $res = changerMotDePasse(userId(), $_POST['ancien_mdp'] ?? '', $_POST['nouveau_mdp'] ?? '');
            if (isset($res['succes'])) {
                setFlash('succes', 'Mot de passe modifié avec succès.');
                rediriger('profil.php?tab=securite');
            } else {
                $erreurs[] = $res['erreur'];
            }
        }

        if ($action === 'lire_notifs') {
            $pdo->prepare('UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?')
                ->execute([userId()]);
            rediriger('profil.php?tab=notifications');
        }
    }
}

// ── Données selon l'onglet ───────────────────────────────────
$reservations = [];
$notifications = [];

if ($tab === 'reservations') {
    $stmtR = $pdo->prepare("
        SELECT r.*, f.nom AS festival_nom, f.slug AS festival_slug,
               f.date_debut, f.date_fin, f.lieu, f.image_principale,
               tb.nom AS type_nom
        FROM reservations r
        JOIN festivals f ON r.festival_id = f.id
        JOIN types_billet tb ON r.type_billet_id = tb.id
        WHERE r.utilisateur_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmtR->execute([userId()]);
    $reservations = $stmtR->fetchAll();
}

if ($tab === 'notifications') {
    $stmtN = $pdo->prepare('SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmtN->execute([userId()]);
    $notifications = $stmtN->fetchAll();
}

$pageTitre = 'Mon profil – XwéDò';

$pageCSS = <<<CSS
.profil-page {
  padding: 7rem 5% 4rem;
  max-width: 1100px; margin: 0 auto;
}
.profil-header {
  display: flex; align-items: center; gap: 2rem;
  margin-bottom: 3rem; flex-wrap: wrap;
}
.profil-avatar-wrap { position: relative; flex-shrink: 0; }
.profil-avatar {
  width: 90px; height: 90px; border-radius: 50%;
  object-fit: cover;
  border: 3px solid rgba(196,98,45,.2);
}
.profil-avatar-placeholder {
  width: 90px; height: 90px; border-radius: 50%;
  background: var(--sand);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: 2.2rem;
  color: var(--terracotta);
  border: 3px solid rgba(196,98,45,.2);
}
.profil-nom {
  font-family: var(--font-serif);
  font-size: 2rem; font-weight: 300; color: var(--dusk);
}
.profil-email { font-size: .88rem; color: var(--text-soft); margin-top: .3rem; }
.profil-role {
  display: inline-block; margin-top: .6rem;
}

/* Onglets */
.profil-tabs {
  display: flex; gap: .3rem; margin-bottom: 2.5rem;
  border-bottom: 1px solid rgba(196,98,45,.12);
  overflow-x: auto; padding-bottom: 0;
}
.profil-tab {
  padding: .75rem 1.3rem;
  font-size: .82rem; font-weight: 400;
  color: var(--text-mid); background: none; border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer; white-space: nowrap;
  transition: all var(--transition);
  display: flex; align-items: center; gap: .5rem;
  margin-bottom: -1px;
}
.profil-tab:hover { color: var(--terracotta); }
.profil-tab.active {
  color: var(--terracotta);
  border-bottom-color: var(--terracotta);
  font-weight: 500;
}
.profil-tab-badge {
  background: var(--terracotta); color: var(--white);
  font-size: .65rem; font-weight: 600;
  min-width: 18px; height: 18px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
}

/* Sections */
.profil-section { display: none; }
.profil-section.active { display: block; }

/* Formulaire profil */
.profil-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
.profil-form-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 2rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.profil-card-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}

/* Réservations */
.resa-list { display: flex; flex-direction: column; gap: 1.2rem; }
.resa-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.4rem 1.6rem;
  box-shadow: var(--shadow-sm);
  display: flex; align-items: center; gap: 1.5rem;
  transition: box-shadow var(--transition);
}
.resa-card:hover { box-shadow: var(--shadow-md); }
.resa-img {
  width: 70px; height: 70px; border-radius: var(--radius-md);
  object-fit: cover; flex-shrink: 0;
}
.resa-info { flex: 1; min-width: 0; }
.resa-festival {
  font-family: var(--font-serif);
  font-size: 1.15rem; font-weight: 400; color: var(--dusk);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: .3rem;
}
.resa-meta { font-size: .8rem; color: var(--text-soft); margin-bottom: .4rem; }
.resa-type { font-size: .78rem; color: var(--text-mid); }
.resa-right { text-align: right; flex-shrink: 0; }
.resa-prix {
  font-family: var(--font-serif);
  font-size: 1.1rem; font-weight: 600; color: var(--terracotta);
  margin-bottom: .4rem;
}
.resa-code {
  font-size: .75rem; letter-spacing: .08em;
  color: var(--text-soft); font-family: monospace;
}

/* Notifications */
.notif-list { display: flex; flex-direction: column; gap: 0; }
.notif-item {
  display: flex; gap: 1rem; align-items: flex-start;
  padding: 1.1rem 1.4rem;
  border-bottom: 1px solid rgba(196,98,45,.06);
  background: var(--white);
  transition: background var(--transition);
}
.notif-item:first-child { border-radius: var(--radius-md) var(--radius-md) 0 0; }
.notif-item:last-child  { border-radius: 0 0 var(--radius-md) var(--radius-md); border-bottom: none; }
.notif-item.non-lue { background: rgba(196,98,45,.03); }
.notif-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--terracotta); flex-shrink: 0; margin-top: 6px;
  transition: opacity var(--transition);
}
.notif-item:not(.non-lue) .notif-dot { opacity: 0; }
.notif-info { flex: 1; }
.notif-titre { font-size: .88rem; font-weight: 500; color: var(--text); margin-bottom: .2rem; }
.notif-msg { font-size: .82rem; color: var(--text-mid); line-height: 1.5; }
.notif-date { font-size: .72rem; color: var(--text-soft); margin-top: .3rem; }

/* Vide */
.profil-empty {
  text-align: center; padding: 4rem 2rem;
  color: var(--text-soft);
}
.profil-empty-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: .4; }
.profil-empty p { font-size: .9rem; }

/* Erreurs */
.profil-erreurs {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.5rem;
}
.profil-erreurs li { font-size: .85rem; color: #8B1A1A; list-style: none; padding: .15rem 0; }
.profil-erreurs li::before { content: '⚠ '; }

@media (max-width: 768px) {
  .profil-form-grid { grid-template-columns: 1fr; }
  .resa-card { flex-wrap: wrap; }
  .resa-right { width: 100%; text-align: left; }
}
@media (max-width: 480px) {
  .profil-header { flex-direction: column; text-align: center; }
}
CSS;

// Compter notifs non lues
$nbNonLues = 0;
$stmtNL = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0');
$stmtNL->execute([userId()]);
$nbNonLues = (int) $stmtNL->fetchColumn();

require_once 'includes/header.php';
?>

<div class="profil-page">

  <!-- En-tête profil -->
  <div class="profil-header">
    <div class="profil-avatar-wrap">
      <?php if (!empty($user['avatar'])): ?>
        <img src="<?= imgUrl($user['avatar']) ?>" alt="Avatar" class="profil-avatar">
      <?php else: ?>
        <div class="profil-avatar-placeholder">
          <?= mb_strtoupper(mb_substr($user['prenom'], 0, 1, 'UTF-8'), 'UTF-8') ?>
        </div>
      <?php endif; ?>
    </div>
    <div>
      <h1 class="profil-nom"><?= e($user['prenom'] . ' ' . $user['nom']) ?></h1>
      <p class="profil-email"><?= e($user['email']) ?></p>
      <span class="profil-role">
        <span class="badge <?= match($user['role']) { 'admin'=>'badge-red', 'organisateur'=>'badge-ochre', default=>'badge-sage' } ?>">
          <?= match($user['role']) { 'admin'=>'Administrateur', 'organisateur'=>'Organisateur', default=>'Participant' } ?>
        </span>
      </span>
    </div>
  </div>

  <!-- Onglets -->
  <div class="profil-tabs">
    <a href="?tab=profil" class="profil-tab <?= $tab === 'profil' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Mon profil
    </a>
    <a href="?tab=reservations" class="profil-tab <?= $tab === 'reservations' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M2 9l3-3 3 3M2 15l3 3 3-3M13 6h8M13 12h8M13 18h8"/></svg>
      Mes billets
    </a>
    <a href="?tab=notifications" class="profil-tab <?= $tab === 'notifications' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      Notifications
      <?php if ($nbNonLues > 0): ?>
        <span class="profil-tab-badge"><?= $nbNonLues ?></span>
      <?php endif; ?>
    </a>
    <a href="?tab=securite" class="profil-tab <?= $tab === 'securite' ? 'active' : '' ?>">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Sécurité
    </a>
  </div>

  <?php if (!empty($erreurs)): ?>
    <ul class="profil-erreurs">
      <?php foreach ($erreurs as $e_): ?><li><?= e($e_) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <!-- ── Onglet : Profil ─────────────────────────────────── -->
  <div class="profil-section <?= $tab === 'profil' ? 'active' : '' ?>">
    <div class="profil-form-card">
      <p class="profil-card-title">Informations personnelles</p>
      <form method="POST" action="?tab=profil">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_profil">
        <div class="profil-form-grid">
          <div class="form-group">
            <label class="form-label" for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" class="form-input" value="<?= e($user['prenom']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="nom">Nom</label>
            <input type="text" id="nom" name="nom" class="form-input" value="<?= e($user['nom']) ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-input" value="<?= e($user['email']) ?>" disabled style="opacity:.6; cursor:not-allowed;">
          <span class="form-hint">L'email ne peut pas être modifié.</span>
        </div>
        <div class="form-group">
          <label class="form-label" for="ville">Ville</label>
          <select id="ville" name="ville" class="form-input form-select">
            <option value="">Non précisée</option>
            <?php
            $villes = ['Cotonou','Porto-Novo','Ouidah','Parakou','Natitingou','Abomey','Nikki','Savalou','Lokossa','Kandi','Djougou','Bohicon'];
            foreach ($villes as $v):
            ?>
              <option value="<?= e($v) ?>" <?= ($user['ville'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-terra">
          <span>Enregistrer</span>
        </button>
      </form>
    </div>
  </div>

  <!-- ── Onglet : Réservations ───────────────────────────── -->
  <div class="profil-section <?= $tab === 'reservations' ? 'active' : '' ?>">
    <?php if (empty($reservations)): ?>
      <div class="profil-empty">
        <div class="profil-empty-icon">🎟️</div>
        <p>Vous n'avez pas encore de réservation.</p>
        <a href="<?= url('festivals.php') ?>" class="btn-terra" style="margin-top:1.5rem;">Explorer les festivals</a>
      </div>
    <?php else: ?>
      <div class="resa-list">
        <?php foreach ($reservations as $r): ?>
          <div class="resa-card">
            <img src="<?= imgUrl($r['image_principale']) ?>" alt="<?= e($r['festival_nom']) ?>" class="resa-img">
            <div class="resa-info">
              <p class="resa-festival">
                <a href="<?= url('festival.php?slug=' . e($r['festival_slug'])) ?>"><?= e($r['festival_nom']) ?></a>
              </p>
              <p class="resa-meta"><?= periodeFestival($r['date_debut'], $r['date_fin']) ?> · <?= e($r['lieu'] ?? '') ?></p>
              <p class="resa-type"><?= e($r['type_nom']) ?> × <?= $r['quantite'] ?></p>
            </div>
            <div class="resa-right">
              <p class="resa-prix"><?= formatPrix((float)$r['prix_total']) ?></p>
              <span class="badge <?= match($r['statut']) { 'confirmee'=>'badge-green', 'annulee'=>'badge-red', default=>'badge-ochre' } ?>">
                <?= match($r['statut']) { 'confirmee'=>'Confirmée', 'annulee'=>'Annulée', 'remboursee'=>'Remboursée', default=>'En attente' } ?>
              </span>
              <p class="resa-code"><?= e($r['code_billet']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Onglet : Notifications ──────────────────────────── -->
  <div class="profil-section <?= $tab === 'notifications' ? 'active' : '' ?>">
    <?php if ($nbNonLues > 0): ?>
      <form method="POST" style="margin-bottom:1rem;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="lire_notifs">
        <button type="submit" class="btn-link">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Tout marquer comme lu
        </button>
      </form>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
      <div class="profil-empty">
        <div class="profil-empty-icon">🔔</div>
        <p>Aucune notification pour le moment.</p>
      </div>
    <?php else: ?>
      <div class="notif-list" style="border: 1px solid rgba(196,98,45,.1); border-radius: var(--radius-lg); overflow: hidden;">
        <?php foreach ($notifications as $n): ?>
          <div class="notif-item <?= !$n['lue'] ? 'non-lue' : '' ?>">
            <div class="notif-dot"></div>
            <div class="notif-info">
              <p class="notif-titre"><?= e($n['titre']) ?></p>
              <p class="notif-msg"><?= e($n['message']) ?></p>
              <p class="notif-date"><?= dateFormatFr($n['created_at']) ?></p>
            </div>
            <?php if (!empty($n['lien'])): ?>
              <a href="<?= e($n['lien']) ?>" class="btn-link" style="flex-shrink:0; font-size:.75rem;">Voir →</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Onglet : Sécurité ───────────────────────────────── -->
  <div class="profil-section <?= $tab === 'securite' ? 'active' : '' ?>">
    <div class="profil-form-card">
      <p class="profil-card-title">Changer le mot de passe</p>
      <form method="POST" action="?tab=securite" style="max-width: 420px;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label class="form-label" for="ancien_mdp">Mot de passe actuel</label>
          <input type="password" id="ancien_mdp" name="ancien_mdp" class="form-input" autocomplete="current-password" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="nouveau_mdp">Nouveau mot de passe</label>
          <input type="password" id="nouveau_mdp" name="nouveau_mdp" class="form-input" autocomplete="new-password" required>
          <span class="form-hint">Min. 8 caractères, 1 majuscule, 1 chiffre.</span>
        </div>
        <button type="submit" class="btn-terra">
          <span>Modifier le mot de passe</span>
        </button>
      </form>
    </div>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>