<?php
// ============================================================
//  XwéDò – Réservation de billets
//  Fichier : billets.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireConnecte();

$pdo  = getDB();
$slug = getPropre('festival', '');

if (empty($slug)) { rediriger('festivals.php'); }

// ── Festival ─────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM festivals WHERE slug = ? AND statut = 'publie' LIMIT 1");
$stmt->execute([$slug]);
$festival = $stmt->fetch();
if (!$festival) { http_response_code(404); require_once 'errors/404.php'; exit; }

// ── Billets disponibles ──────────────────────────────────────
$stmtB = $pdo->prepare("
    SELECT *, (quantite IS NULL OR quantite - vendu > 0) AS disponible
    FROM types_billet
    WHERE festival_id = ? AND actif = 1
      AND (date_limite IS NULL OR date_limite >= CURDATE())
    ORDER BY prix ASC
");
$stmtB->execute([$festival['id']]);
$typeBillets = $stmtB->fetchAll();

$erreurs = [];
$succes  = false;
$reservation = null;

// ── Traitement du formulaire ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée, veuillez réessayer.';
    } else {
        $typeBilletId = (int) ($_POST['type_billet_id'] ?? 0);
        $quantite     = max(1, min(10, (int) ($_POST['quantite'] ?? 1)));

        // Vérifier le type de billet
        $stmtTB = $pdo->prepare("SELECT * FROM types_billet WHERE id = ? AND festival_id = ? AND actif = 1");
        $stmtTB->execute([$typeBilletId, $festival['id']]);
        $typeBillet = $stmtTB->fetch();

        if (!$typeBillet) {
            $erreurs[] = 'Type de billet invalide.';
        } elseif ($typeBillet['quantite'] !== null && ($typeBillet['quantite'] - $typeBillet['vendu']) < $quantite) {
            $erreurs[] = 'Nombre de places demandé non disponible. Il reste ' . ($typeBillet['quantite'] - $typeBillet['vendu']) . ' place(s).';
        } else {
            $prixTotal  = $typeBillet['prix'] * $quantite;
            $commission = calculerCommission($prixTotal);
            $codeBillet = genererCodeBillet();

            try {
                $pdo->beginTransaction();

                // Créer la réservation avec commission
                $stmtR = $pdo->prepare("
                    INSERT INTO reservations
                        (utilisateur_id, festival_id, type_billet_id, quantite,
                         prix_total, commission_taux, commission_montant,
                         montant_organisateur, statut, code_billet)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmee', ?)
                ");
                $stmtR->execute([
                    userId(), $festival['id'], $typeBilletId, $quantite,
                    $prixTotal,
                    $commission['taux'],
                    $commission['commission'],
                    $commission['net'],
                    $codeBillet
                ]);
                $reservationId = (int) $pdo->lastInsertId();

                // Mettre à jour le stock
                $pdo->prepare("UPDATE types_billet SET vendu = vendu + ? WHERE id = ?")
                    ->execute([$quantite, $typeBilletId]);

                // Notification
                $pdo->prepare("
                    INSERT INTO notifications (utilisateur_id, titre, message, lien)
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    userId(),
                    'Réservation confirmée – ' . $festival['nom'],
                    'Votre réservation de ' . $quantite . ' billet(s) pour ' . $festival['nom'] . ' est confirmée. Code : ' . $codeBillet,
                    url('profil.php?tab=reservations')
                ]);

                $pdo->commit();
                $succes = true;

                // Récupérer la réservation créée
                $stmt2 = $pdo->prepare("SELECT r.*, tb.nom AS type_nom FROM reservations r JOIN types_billet tb ON r.type_billet_id = tb.id WHERE r.id = ?");
                $stmt2->execute([$reservationId]);
                $reservation = $stmt2->fetch();

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('[XwéDò] Erreur réservation : ' . $e->getMessage());
                $erreurs[] = 'Une erreur est survenue. Veuillez réessayer.';
            }
        }
    }
}

$statut    = statutDate($festival['date_debut'], $festival['date_fin']);
$pageTitre = 'Réserver – ' . e($festival['nom']) . ' – XwéDò';

$pageCSS = <<<CSS
.billets-page {
  min-height: 100vh;
  padding: 7rem 5% 4rem;
  max-width: 1000px; margin: 0 auto;
}

/* En-tête festival mini */
.billets-festival-header {
  display: flex; align-items: center; gap: 1.5rem;
  padding: 1.5rem;
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  margin-bottom: 2.5rem;
  box-shadow: var(--shadow-sm);
}
.billets-festival-img {
  width: 80px; height: 80px; border-radius: var(--radius-md);
  object-fit: cover; flex-shrink: 0;
}
.billets-festival-nom {
  font-family: var(--font-serif);
  font-size: 1.4rem; font-weight: 300; color: var(--dusk);
  margin-bottom: .3rem;
}
.billets-festival-meta { font-size: .82rem; color: var(--text-soft); }
.billets-retour {
  margin-left: auto;
  display: flex; align-items: center; gap: 6px;
  font-size: .82rem; color: var(--text-soft);
  transition: color var(--transition); flex-shrink: 0;
}
.billets-retour:hover { color: var(--terracotta); }

/* Grille principale */
.billets-grid {
  display: grid; grid-template-columns: 1fr 360px; gap: 2.5rem;
}

/* Sélection billets */
.billets-section-title {
  font-size: .75rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: 1.2rem;
}

.billet-option {
  border: 2px solid rgba(196,98,45,.15);
  border-radius: var(--radius-md);
  padding: 1.2rem 1.4rem;
  margin-bottom: .9rem;
  cursor: pointer;
  transition: all var(--transition);
  position: relative;
}
.billet-option:hover { border-color: rgba(196,98,45,.4); }
.billet-option.selected {
  border-color: var(--terracotta);
  background: rgba(196,98,45,.03);
}
.billet-option input[type="radio"] {
  position: absolute; opacity: 0; width: 0; height: 0;
}
.billet-option-top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: .4rem;
}
.billet-option-nom { font-size: .95rem; font-weight: 500; color: var(--text); }
.billet-option-prix {
  font-family: var(--font-serif);
  font-size: 1.2rem; font-weight: 600; color: var(--terracotta);
}
.billet-option-desc { font-size: .82rem; color: var(--text-soft); margin-bottom: .4rem; line-height: 1.5; }
.billet-option-dispo { font-size: .75rem; color: var(--sage); }
.billet-option-dispo.complet { color: #C0392B; }
.billet-option-check {
  position: absolute; top: 1rem; right: 1rem;
  width: 20px; height: 20px; border-radius: 50%;
  border: 2px solid rgba(196,98,45,.3);
  display: flex; align-items: center; justify-content: center;
  transition: all var(--transition);
}
.billet-option.selected .billet-option-check {
  background: var(--terracotta); border-color: var(--terracotta);
}
.billet-option.selected .billet-option-check::after {
  content: ''; display: block;
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--white);
}

/* Quantité */
.qty-wrap {
  display: flex; align-items: center; gap: 1rem;
  margin: 1.5rem 0;
}
.qty-label {
  font-size: .82rem; font-weight: 500; color: var(--text-mid);
}
.qty-ctrl {
  display: flex; align-items: center; gap: .5rem;
}
.qty-btn {
  width: 36px; height: 36px;
  border: 1.5px solid rgba(196,98,45,.2);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; color: var(--terracotta);
  background: var(--white); cursor: pointer;
  transition: all var(--transition); line-height: 1;
}
.qty-btn:hover:not(:disabled) {
  background: var(--terracotta); color: var(--white);
  border-color: var(--terracotta);
}
.qty-btn:disabled { opacity: .35; cursor: not-allowed; }
.qty-val {
  width: 40px; text-align: center;
  font-size: 1rem; font-weight: 500; color: var(--text);
}

/* Récapitulatif sidebar */
.recap-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.8rem;
  box-shadow: var(--shadow-sm);
  position: sticky; top: 6rem;
}
.recap-title { font-size: .72rem; letter-spacing: .15em; text-transform: uppercase; color: var(--text-soft); margin-bottom: 1.5rem; }
.recap-ligne {
  display: flex; justify-content: space-between; align-items: center;
  padding: .7rem 0; border-bottom: 1px solid rgba(196,98,45,.06);
  font-size: .88rem; color: var(--text-mid);
}
.recap-ligne:last-of-type { border-bottom: none; }
.recap-ligne strong { color: var(--text); }
.recap-total {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 1.2rem;
  border-top: 1.5px solid rgba(196,98,45,.15);
  margin-top: .5rem;
}
.recap-total span:first-child { font-weight: 500; color: var(--text); }
.recap-total-prix {
  font-family: var(--font-serif);
  font-size: 1.5rem; font-weight: 600; color: var(--terracotta);
}
.recap-submit {
  width: 100%; padding: 1rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .88rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase;
  border: none; border-radius: var(--radius-full);
  cursor: pointer; transition: background var(--transition);
}
.recap-submit:hover { background: var(--soil); }
.recap-submit:disabled { background: var(--text-soft); cursor: not-allowed; }
.recap-note { font-size: .75rem; color: var(--text-soft); text-align: center; margin-top: .8rem; line-height: 1.5; }

/* Confirmation */
.confirm-card {
  max-width: 580px; margin: 0 auto;
  background: var(--white);
  border: 1px solid rgba(39,174,96,.2);
  border-radius: var(--radius-lg);
  padding: 3rem; text-align: center;
  box-shadow: var(--shadow-md);
}
.confirm-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: rgba(39,174,96,.1);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1.5rem; font-size: 2rem;
}
.confirm-titre {
  font-family: var(--font-serif);
  font-size: 2rem; font-weight: 300; color: var(--dusk);
  margin-bottom: .8rem;
}
.confirm-code {
  display: inline-block;
  font-size: 1.3rem; font-weight: 600;
  letter-spacing: .12em; color: var(--terracotta);
  background: rgba(196,98,45,.08);
  padding: .6rem 1.5rem; border-radius: var(--radius-full);
  margin: 1.2rem 0;
  font-family: monospace;
}
.confirm-detail { font-size: .88rem; color: var(--text-mid); line-height: 1.8; margin-bottom: 2rem; }
.confirm-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

/* Erreurs */
.billets-erreurs {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.5rem;
}
.billets-erreurs li { font-size: .85rem; color: #8B1A1A; list-style: none; padding: .15rem 0; }
.billets-erreurs li::before { content: '⚠ '; }

@media (max-width: 860px) {
  .billets-grid { grid-template-columns: 1fr; }
  .recap-card { position: static; }
}
@media (max-width: 560px) {
  .billets-festival-header { flex-wrap: wrap; }
  .billets-retour { margin-left: 0; }
}
CSS;

require_once 'includes/header.php';
?>

<div class="billets-page">

  <!-- En-tête festival -->
  <div class="billets-festival-header">
    <img src="<?= imgUrl($festival['image_principale']) ?>" alt="<?= e($festival['nom']) ?>" class="billets-festival-img">
    <div>
      <h1 class="billets-festival-nom"><?= e($festival['nom']) ?></h1>
      <p class="billets-festival-meta">
        <?= periodeFestival($festival['date_debut'], $festival['date_fin']) ?>
        · <?= e($festival['lieu'] ?? $festival['ville'] ?? 'Bénin') ?>
      </p>
    </div>
    <a href="<?= url('festival.php?slug=' . e($festival['slug'])) ?>" class="billets-retour">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      Retour
    </a>
  </div>

  <?php if ($succes && $reservation): ?>
    <!-- Confirmation -->
    <div class="confirm-card">
      <div class="confirm-icon">✓</div>
      <h2 class="confirm-titre">Réservation confirmée !</h2>
      <p class="confirm-detail">
        <strong><?= $reservation['quantite'] ?> billet(s)</strong> – <?= e($reservation['type_nom']) ?><br>
        pour <strong><?= e($festival['nom']) ?></strong><br>
        <?= periodeFestival($festival['date_debut'], $festival['date_fin']) ?>
      </p>
      <div class="confirm-code"><?= e($reservation['code_billet']) ?></div>
      <p class="confirm-detail">
        Total payé : <strong><?= formatPrix((float)$reservation['prix_total']) ?></strong><br>
        Présentez ce QR code à l'entrée du festival.
      </p>

      <!-- QR Code généré localement — fonctionne sans internet -->
      <div id="qr-container" style="display:flex; flex-direction:column; align-items:center; margin:1.5rem 0;">
        <img
          src="<?= url('api/qr.php?code=' . urlencode($reservation['code_billet']) . '&size=220') ?>"
          alt="QR Code billet <?= e($reservation['code_billet']) ?>"
          style="background:#fff; padding:12px; border-radius:12px; border:2px solid rgba(196,98,45,.15); width:220px; height:auto;"
          onerror="this.outerHTML='<div style=\'padding:1.5rem;background:#FAF6EE;border-radius:12px;font-family:monospace;font-size:1.2rem;letter-spacing:.1em;color:#C4622D;border:2px dashed rgba(196,98,45,.3);\'><?= e($reservation['code_billet']) ?></div>'"
        >
      </div>
      <p style="font-size:.75rem; color:var(--text-soft); margin-bottom:1.5rem;">
        📱 Présentez ce QR code à l'entrée du festival
      </p>
      <div class="confirm-actions">
        <a href="<?= url('profil.php?tab=reservations') ?>" class="btn-terra">
          <span>Voir mes billets</span>
        </a>
        <a href="<?= url('festivals.php') ?>" class="btn-terra-outline">Explorer d'autres festivals</a>
      </div>
    </div>

  <?php else: ?>

    <?php if (!empty($erreurs)): ?>
      <ul class="billets-erreurs">
        <?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($statut['classe'] === 'termine'): ?>
      <div style="text-align:center; padding: 3rem; color: var(--text-soft);">
        <p style="font-family: var(--font-serif); font-size: 1.5rem; font-weight: 300; margin-bottom: .8rem;">Ce festival est terminé.</p>
        <a href="<?= url('festivals.php') ?>" class="btn-terra" style="margin-top:1rem;">Voir d'autres festivals</a>
      </div>
    <?php else: ?>

      <form method="POST" id="form-billets">
        <?= csrfField() ?>
        <input type="hidden" name="type_billet_id" id="type-billet-id" value="">
        <input type="hidden" name="quantite" id="input-quantite" value="1">

        <div class="billets-grid">
          <!-- Sélection -->
          <div>
            <p class="billets-section-title">Choisissez votre billet</p>

            <?php foreach ($typeBillets as $tb): ?>
              <label class="billet-option <?= !$tb['disponible'] ? '' : '' ?>"
                     data-id="<?= $tb['id'] ?>"
                     data-prix="<?= $tb['prix'] ?>"
                     data-nom="<?= e($tb['nom']) ?>">
                <input type="radio" name="_billet" value="<?= $tb['id'] ?>" <?= !$tb['disponible'] ? 'disabled' : '' ?>>
                <div class="billet-option-check"></div>
                <div class="billet-option-top">
                  <span class="billet-option-nom"><?= e($tb['nom']) ?></span>
                  <span class="billet-option-prix"><?= formatPrix((float)$tb['prix']) ?></span>
                </div>
                <?php if (!empty($tb['description'])): ?>
                  <p class="billet-option-desc"><?= e($tb['description']) ?></p>
                <?php endif; ?>
                <span class="billet-option-dispo <?= !$tb['disponible'] ? 'complet' : '' ?>">
                  <?php if (!$tb['disponible']): ?>
                    Complet
                  <?php elseif ($tb['quantite'] !== null): ?>
                    <?= $tb['quantite'] - $tb['vendu'] ?> place(s) restante(s)
                  <?php else: ?>
                    Places disponibles
                  <?php endif; ?>
                </span>
              </label>
            <?php endforeach; ?>

            <!-- Quantité -->
            <div class="qty-wrap">
              <span class="qty-label">Quantité :</span>
              <div class="qty-ctrl">
                <button type="button" class="qty-btn" id="qty-moins" disabled>−</button>
                <span class="qty-val" id="qty-display">1</span>
                <button type="button" class="qty-btn" id="qty-plus" disabled>+</button>
              </div>
            </div>
          </div>

          <!-- Récap sidebar -->
          <div>
            <div class="recap-card">
              <p class="recap-title">Récapitulatif</p>
              <div class="recap-ligne">
                <span>Type</span>
                <strong id="recap-type">–</strong>
              </div>
              <div class="recap-ligne">
                <span>Prix unitaire</span>
                <strong id="recap-prix-unit">–</strong>
              </div>
              <div class="recap-ligne">
                <span>Quantité</span>
                <strong id="recap-qty">1</strong>
              </div>
              <div class="recap-total">
                <span>Total</span>
                <span class="recap-total-prix" id="recap-total">–</span>
              </div>
              <div id="recap-commission" style="display:none; font-size:.72rem; color:var(--text-soft); padding:.5rem 0; border-bottom:1px solid rgba(196,98,45,.08); margin-bottom:.5rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:.2rem;">
                  <span>dont commission XwéDò (5%)</span>
                  <span id="recap-comm-montant">–</span>
                </div>
                <div style="display:flex; justify-content:space-between; color:var(--sage);">
                  <span>reversé à l'organisateur</span>
                  <span id="recap-comm-net">–</span>
                </div>
              </div>
              <button type="submit" class="recap-submit" id="btn-submit" disabled>
                Confirmer la réservation
              </button>
              <p class="recap-note">Votre billet sera disponible immédiatement dans votre espace personnel.</p>
            </div>
          </div>
        </div>
      </form>

    <?php endif; ?>
  <?php endif; ?>

</div>

<?php
$pageJS = <<<JS
const options    = document.querySelectorAll('.billet-option');
const hiddenId   = document.getElementById('type-billet-id');
const hiddenQty  = document.getElementById('input-quantite');
const qtyMoins   = document.getElementById('qty-moins');
const qtyPlus    = document.getElementById('qty-plus');
const qtyDisplay = document.getElementById('qty-display');
const recapType  = document.getElementById('recap-type');
const recapUnit  = document.getElementById('recap-prix-unit');
const recapQty   = document.getElementById('recap-qty');
const recapTotal = document.getElementById('recap-total');
const recapComm  = document.getElementById('recap-commission');
const recapCommM = document.getElementById('recap-comm-montant');
const recapCommN = document.getElementById('recap-comm-net');
const btnSubmit  = document.getElementById('btn-submit');

const TAUX_COMM  = 5; // 5% commission XwéDò

let selectedPrix = 0;
let qty = 1;

function formatPrix(p) {
  if (p <= 0) return 'Gratuit';
  return new Intl.NumberFormat('fr-FR').format(p) + ' FCFA';
}

function updateRecap() {
  if (!hiddenId.value) return;
  const total      = selectedPrix * qty;
  const commission = Math.round(total * TAUX_COMM / 100);
  const net        = total - commission;

  recapQty.textContent   = qty;
  hiddenQty.value        = qty;
  recapTotal.textContent = formatPrix(total);
  qtyMoins.disabled = qty <= 1;
  qtyPlus.disabled  = qty >= 10;

  // Afficher commission seulement si prix > 0
  if (total > 0) {
    recapComm.style.display = 'block';
    recapCommM.textContent  = formatPrix(commission);
    recapCommN.textContent  = formatPrix(net);
  } else {
    recapComm.style.display = 'none';
  }
}

options.forEach(opt => {
  opt.addEventListener('click', () => {
    const radio = opt.querySelector('input[type="radio"]');
    if (radio.disabled) return;

    options.forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
    radio.checked = true;

    hiddenId.value  = opt.dataset.id;
    selectedPrix    = parseFloat(opt.dataset.prix) || 0;
    qty = 1;
    qtyDisplay.textContent = '1';

    recapType.textContent  = opt.dataset.nom;
    recapUnit.textContent  = formatPrix(selectedPrix);
    btnSubmit.disabled     = false;
    qtyMoins.disabled      = true;
    qtyPlus.disabled       = false;

    updateRecap();
  });
});

qtyMoins.addEventListener('click', () => { if (qty > 1)  { qty--; qtyDisplay.textContent = qty; updateRecap(); } });
qtyPlus.addEventListener('click',  () => { if (qty < 10) { qty++; qtyDisplay.textContent = qty; updateRecap(); } });
JS;
require_once 'includes/footer.php';
?>