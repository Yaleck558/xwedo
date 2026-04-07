<?php
// ============================================================
//  XwéDò – Gestion des billets (organisateur)
//  Fichier : organisateur/billets.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('organisateur');

$pdo        = getDB();
$uid        = userId();
$festivalId = getPropre('festival', 0);
$erreurs    = [];

// Charger le festival de l'organisateur
$stmt = $pdo->prepare('SELECT * FROM festivals WHERE id = ? AND organisateur_id = ? LIMIT 1');
$stmt->execute([$festivalId, $uid]);
$festival = $stmt->fetch();
if (!$festival) {
    setFlash('erreur', 'Festival introuvable.');
    rediriger('organisateur/dashboard.php');
}

// ── Traitement POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_type') {
            $nom   = postPropre('nom');
            $prix  = (float) str_replace(',', '.', $_POST['prix'] ?? '0');
            $qte   = (int) ($_POST['quantite'] ?? 0) ?: null;
            $limit = postPropre('date_limite') ?: null;
            $desc  = postPropre('description');

            if (empty($nom)) {
                $erreurs[] = 'Le nom du type de billet est requis.';
            } elseif ($prix < 0) {
                $erreurs[] = 'Le prix ne peut pas être négatif.';
            } else {
                $pdo->prepare("INSERT INTO types_billet (festival_id, nom, description, prix, quantite, date_limite) VALUES (?,?,?,?,?,?)")
                    ->execute([$festivalId, $nom, $desc ?: null, $prix, $qte, $limit]);
                setFlash('succes', 'Type de billet ajouté.');
                rediriger('organisateur/billets.php?festival=' . $festivalId);
            }
        }

        if ($action === 'toggle_actif') {
            $tbId = (int) ($_POST['tb_id'] ?? 0);
            $pdo->prepare('UPDATE types_billet SET actif = 1 - actif WHERE id = ? AND festival_id = ?')
                ->execute([$tbId, $festivalId]);
            rediriger('organisateur/billets.php?festival=' . $festivalId);
        }

        if ($action === 'delete_type') {
            $tbId = (int) ($_POST['tb_id'] ?? 0);
            // Ne supprimer que si aucune réservation existante
            $count = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE type_billet_id = ?');
            $count->execute([$tbId]);
            if ((int)$count->fetchColumn() > 0) {
                $erreurs[] = 'Impossible de supprimer ce type : des réservations existent.';
            } else {
                $pdo->prepare('DELETE FROM types_billet WHERE id = ? AND festival_id = ?')
                    ->execute([$tbId, $festivalId]);
                setFlash('succes', 'Type de billet supprimé.');
                rediriger('organisateur/billets.php?festival=' . $festivalId);
            }
        }
    }
}

// ── Données ──────────────────────────────────────────────────
$typesBillets = $pdo->prepare("SELECT *, (quantite IS NULL OR quantite - vendu > 0) AS disponible FROM types_billet WHERE festival_id = ? ORDER BY prix ASC");
$typesBillets->execute([$festivalId]);
$typesBillets = $typesBillets->fetchAll();

$reservations = $pdo->prepare("
    SELECT r.*, u.prenom, u.nom AS user_nom, u.email,
           tb.nom AS type_nom
    FROM reservations r
    JOIN utilisateurs u  ON r.utilisateur_id  = u.id
    JOIN types_billet tb ON r.type_billet_id  = tb.id
    WHERE r.festival_id = ?
    ORDER BY r.created_at DESC
    LIMIT 100
");
$reservations->execute([$festivalId]);
$reservations = $reservations->fetchAll();

$pageTitre = 'Billets – ' . e($festival['nom']) . ' – XwéDò';

$pageCSS = <<<CSS
.billets-org-page { padding: 7rem 5% 4rem; max-width: 1100px; margin: 0 auto; }
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

.billets-org-grid { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; }

.panel-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  overflow: hidden; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;
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

/* Table types billets */
.tb-table { width: 100%; border-collapse: collapse; }
.tb-table th {
  padding: .7rem 1.2rem;
  font-size: .7rem; font-weight: 500; letter-spacing: .1em;
  text-transform: uppercase; color: var(--text-soft); text-align: left;
  background: rgba(237,224,200,.3);
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.tb-table td {
  padding: .9rem 1.2rem;
  font-size: .85rem; color: var(--text-mid);
  border-bottom: 1px solid rgba(196,98,45,.05); vertical-align: middle;
}
.tb-table tr:last-child td { border-bottom: none; }
.tb-nom { font-weight: 500; color: var(--text); }
.tb-prix { font-family: var(--font-serif); font-size: 1rem; font-weight: 600; color: var(--terracotta); }
.tb-progress { margin-top: .4rem; }
.progress-bar {
  height: 4px; border-radius: 2px;
  background: rgba(196,98,45,.12); overflow: hidden;
}
.progress-fill { height: 100%; border-radius: 2px; background: var(--terracotta); }
.progress-label { font-size: .68rem; color: var(--text-soft); margin-top: 2px; }

/* Table réservations */
.resa-table { width: 100%; border-collapse: collapse; }
.resa-table th {
  padding: .7rem 1.2rem;
  font-size: .7rem; font-weight: 500; letter-spacing: .1em;
  text-transform: uppercase; color: var(--text-soft); text-align: left;
  background: rgba(237,224,200,.3);
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.resa-table td {
  padding: .85rem 1.2rem;
  font-size: .82rem; color: var(--text-mid);
  border-bottom: 1px solid rgba(196,98,45,.05);
}
.resa-table tr:last-child td { border-bottom: none; }
.resa-table tr:hover td { background: rgba(196,98,45,.02); }
.resa-participant { font-weight: 500; color: var(--text); }
.resa-email { font-size: .72rem; color: var(--text-soft); }

/* Formulaire ajout type */
.form-sidebar {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.6rem; box-shadow: var(--shadow-sm);
}
.form-sidebar-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-mid); margin-bottom: 1.4rem;
  padding-bottom: .8rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}

.erreurs-org {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.5rem;
}
.erreurs-org li { font-size: .85rem; color: #8B1A1A; list-style: none; }
.erreurs-org li::before { content: '⚠ '; }
.empty-row { padding: 2rem; text-align: center; color: var(--text-soft); font-size: .85rem; }

@media (max-width: 900px) { .billets-org-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
  .resa-table th:nth-child(3),
  .resa-table td:nth-child(3) { display: none; }
}
CSS;

require_once '../includes/header.php';
?>

<div class="billets-org-page">
  <div class="creer-breadcrumb">
    <a href="<?= url('organisateur/dashboard.php') ?>">Mon espace</a>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    <a href="<?= url('organisateur/creer-festival.php?id=' . $festivalId) ?>"><?= e($festival['nom']) ?></a>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    <span>Billets & Réservations</span>
  </div>
  <h1 class="page-title">Billets & <em>Réservations</em></h1>

  <?php if (!empty($erreurs)): ?>
    <ul class="erreurs-org"><?php foreach($erreurs as $e_): ?><li><?= e($e_) ?></li><?php endforeach; ?></ul>
  <?php endif; ?>

  <div class="billets-org-grid">
    <div>
      <!-- Types de billets -->
      <div class="panel-card">
        <div class="panel-card-header">
          <span class="panel-card-title">Types de billets</span>
        </div>
        <?php if (empty($typesBillets)): ?>
          <p class="empty-row">Aucun type de billet créé.</p>
        <?php else: ?>
          <table class="tb-table">
            <thead><tr><th>Type</th><th>Prix</th><th>Ventes</th><th>Statut</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($typesBillets as $tb): ?>
                <tr>
                  <td>
                    <div class="tb-nom"><?= e($tb['nom']) ?></div>
                    <?php if (!empty($tb['description'])): ?>
                      <div style="font-size:.72rem; color:var(--text-soft);"><?= e(tronquer($tb['description'],50)) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="tb-prix"><?= formatPrix((float)$tb['prix']) ?></span></td>
                  <td>
                    <div><?= $tb['vendu'] ?><?= $tb['quantite'] ? ' / ' . $tb['quantite'] : '' ?></div>
                    <?php if ($tb['quantite']): ?>
                      <div class="tb-progress">
                        <div class="progress-bar">
                          <div class="progress-fill" style="width:<?= min(100, round($tb['vendu']/$tb['quantite']*100)) ?>%"></div>
                        </div>
                        <div class="progress-label"><?= round($tb['vendu']/$tb['quantite']*100) ?>% vendu</div>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $tb['actif'] ? 'badge-green' : 'badge-sage' ?>">
                      <?= $tb['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                  </td>
                  <td>
                    <div style="display:flex; gap:.4rem;">
                      <form method="POST" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_actif">
                        <input type="hidden" name="tb_id"  value="<?= $tb['id'] ?>">
                        <button type="submit" class="action-btn" style="padding:.35rem .7rem; border:1px solid rgba(196,98,45,.2); border-radius:var(--radius-sm); font-size:.72rem; color:var(--text-mid); background:none; cursor:pointer;">
                          <?= $tb['actif'] ? 'Désactiver' : 'Activer' ?>
                        </button>
                      </form>
                      <?php if ($tb['vendu'] == 0): ?>
                        <form method="POST" onsubmit="return confirm('Supprimer ce type ?')" style="display:inline">
                          <?= csrfField() ?>
                          <input type="hidden" name="action" value="delete_type">
                          <input type="hidden" name="tb_id"  value="<?= $tb['id'] ?>">
                          <button type="submit" class="action-btn" style="padding:.35rem .7rem; border:1px solid rgba(192,57,43,.2); border-radius:var(--radius-sm); font-size:.72rem; color:#C0392B; background:none; cursor:pointer;">
                            Suppr.
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

      <!-- Réservations -->
      <div class="panel-card">
        <div class="panel-card-header">
          <span class="panel-card-title">Réservations (<?= count($reservations) ?>)</span>
        </div>
        <?php if (empty($reservations)): ?>
          <p class="empty-row">Aucune réservation pour ce festival.</p>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="resa-table">
              <thead><tr><th>Participant</th><th>Type</th><th>Qté</th><th>Total</th><th>Statut</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach ($reservations as $r): ?>
                  <tr>
                    <td>
                      <div class="resa-participant"><?= e($r['prenom'] . ' ' . $r['user_nom']) ?></div>
                      <div class="resa-email"><?= e($r['email']) ?></div>
                    </td>
                    <td><?= e($r['type_nom']) ?></td>
                    <td><?= $r['quantite'] ?></td>
                    <td style="font-weight:500; color:var(--terracotta);"><?= formatPrix((float)$r['prix_total']) ?></td>
                    <td>
                      <span class="badge <?= match($r['statut']) { 'confirmee'=>'badge-green', 'annulee'=>'badge-red', default=>'badge-ochre' } ?>">
                        <?= match($r['statut']) { 'confirmee'=>'Confirmée', 'annulee'=>'Annulée', default=>'Attente' } ?>
                      </span>
                    </td>
                    <td style="font-size:.75rem;"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar ajout type -->
    <div>
      <div class="form-sidebar">
        <p class="form-sidebar-title">Ajouter un type de billet</p>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_type">
          <div class="form-group">
            <label class="form-label" for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" class="form-input" placeholder="Journée, Pass 3 jours, VIP…" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea name="description" class="form-input form-textarea" rows="2" placeholder="Ce que comprend ce billet…"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label" for="prix">Prix (FCFA) *</label>
            <input type="number" id="prix" name="prix" class="form-input" placeholder="0 = Gratuit" min="0" step="100" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="quantite">Quantité disponible</label>
            <input type="number" id="quantite" name="quantite" class="form-input" placeholder="Vide = illimitée" min="1">
          </div>
          <div class="form-group">
            <label class="form-label" for="date_limite">Date limite de vente</label>
            <input type="date" id="date_limite" name="date_limite" class="form-input"
                   max="<?= $festival['date_fin'] ?>">
          </div>
          <button type="submit" class="btn-terra" style="width:100%;">
            <span>Créer ce type</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>