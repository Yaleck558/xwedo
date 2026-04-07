<?php
// ============================================================
//  XwéDò – Gestion des artistes & programme
//  Fichier : organisateur/artistes.php
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

// Vérifier que le festival appartient à cet organisateur
$stmt = $pdo->prepare('SELECT * FROM festivals WHERE id = ? AND organisateur_id = ? LIMIT 1');
$stmt->execute([$festivalId, $uid]);
$festival = $stmt->fetch();
if (!$festival) {
    setFlash('erreur', 'Festival introuvable.');
    rediriger('organisateur/dashboard.php');
}

$erreurs = [];

// ── Traitement POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée.';
    } else {
        $action = $_POST['action'] ?? '';

        // Ajouter artiste
        if ($action === 'add_artiste') {
            $nom = postPropre('artiste_nom');
            if (empty($nom)) {
                $erreurs[] = 'Le nom de l\'artiste est requis.';
            } else {
                $photo = null;
                if (!empty($_FILES['artiste_photo']['name'])) {
                    $photo = uploadImage($_FILES['artiste_photo'], 'artistes');
                }
                $pdo->prepare("INSERT INTO artistes (festival_id, nom, biographie, genre, origine, site_web, photo) VALUES (?,?,?,?,?,?,?)")
                    ->execute([
                        $festivalId, $nom,
                        postPropre('artiste_bio') ?: null,
                        postPropre('artiste_genre') ?: null,
                        postPropre('artiste_origine') ?: null,
                        postPropre('artiste_site') ?: null,
                        $photo
                    ]);
                setFlash('succes', 'Artiste ajouté avec succès.');
                rediriger('organisateur/artistes.php?festival=' . $festivalId);
            }
        }

        // Supprimer artiste
        if ($action === 'delete_artiste') {
            $artId = (int) ($_POST['artiste_id'] ?? 0);
            $pdo->prepare('DELETE FROM artistes WHERE id = ? AND festival_id = ?')
                ->execute([$artId, $festivalId]);
            setFlash('succes', 'Artiste supprimé.');
            rediriger('organisateur/artistes.php?festival=' . $festivalId);
        }

        // Ajouter créneau programme
        if ($action === 'add_prog') {
            $titre = postPropre('prog_titre');
            $dh    = postPropre('prog_date_heure');
            if (empty($titre) || empty($dh)) {
                $erreurs[] = 'Titre et date/heure sont requis.';
            } else {
                $pdo->prepare("INSERT INTO programmations (festival_id, artiste_id, titre, description, scene, date_heure, duree_min) VALUES (?,?,?,?,?,?,?)")
                    ->execute([
                        $festivalId,
                        (int) ($_POST['prog_artiste_id'] ?? 0) ?: null,
                        $titre,
                        postPropre('prog_desc') ?: null,
                        postPropre('prog_scene') ?: null,
                        $dh,
                        (int) ($_POST['prog_duree'] ?? 0) ?: null,
                    ]);
                setFlash('succes', 'Créneau ajouté au programme.');
                rediriger('organisateur/artistes.php?festival=' . $festivalId);
            }
        }

        // Supprimer créneau
        if ($action === 'delete_prog') {
            $progId = (int) ($_POST['prog_id'] ?? 0);
            $pdo->prepare('DELETE FROM programmations WHERE id = ? AND festival_id = ?')
                ->execute([$progId, $festivalId]);
            setFlash('succes', 'Créneau supprimé.');
            rediriger('organisateur/artistes.php?festival=' . $festivalId);
        }
    }
}

// ── Données ──────────────────────────────────────────────────
$artistes   = $pdo->prepare('SELECT * FROM artistes WHERE festival_id = ? ORDER BY nom');
$artistes->execute([$festivalId]);
$artistes   = $artistes->fetchAll();

$programme  = $pdo->prepare("
    SELECT p.*, a.nom AS artiste_nom FROM programmations p
    LEFT JOIN artistes a ON p.artiste_id = a.id
    WHERE p.festival_id = ? ORDER BY p.date_heure ASC
");
$programme->execute([$festivalId]);
$programme  = $programme->fetchAll();

$pageTitre  = 'Artistes – ' . e($festival['nom']) . ' – XwéDò';

$pageCSS = <<<CSS
.artistes-page { padding: 7rem 5% 4rem; max-width: 1100px; margin: 0 auto; }
.artistes-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 1.5rem; margin-bottom: 2.5rem; flex-wrap: wrap;
}
.artistes-title {
  font-family: var(--font-serif);
  font-size: clamp(1.6rem, 3vw, 2.4rem);
  font-weight: 300; color: var(--dusk);
}
.artistes-title em { font-style: italic; color: var(--terracotta); }
.creer-breadcrumb {
  display: flex; align-items: center; gap: .5rem;
  font-size: .78rem; color: var(--text-soft); margin-bottom: .8rem;
}
.creer-breadcrumb a { color: var(--text-soft); transition: color var(--transition); }
.creer-breadcrumb a:hover { color: var(--terracotta); }

.artistes-grid-layout {
  display: grid; grid-template-columns: 1fr 360px; gap: 2rem;
}

/* Panel gauche — listes */
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

/* Liste artistes */
.artiste-row {
  display: flex; align-items: center; gap: 1rem;
  padding: .9rem 1.6rem;
  border-bottom: 1px solid rgba(196,98,45,.05);
  transition: background var(--transition);
}
.artiste-row:last-child { border-bottom: none; }
.artiste-row:hover { background: rgba(196,98,45,.02); }
.artiste-row-photo {
  width: 40px; height: 40px; border-radius: 50%;
  object-fit: cover; flex-shrink: 0; background: var(--sand);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: 1rem; color: var(--terracotta);
}
.artiste-row-info { flex: 1; min-width: 0; }
.artiste-row-nom { font-size: .9rem; font-weight: 500; color: var(--text); }
.artiste-row-meta { font-size: .75rem; color: var(--text-soft); }
.artiste-row-del {
  background: none; border: none; cursor: pointer;
  color: var(--text-soft); transition: color var(--transition); padding: .3rem;
}
.artiste-row-del:hover { color: #C0392B; }

/* Programme */
.prog-row {
  display: grid; grid-template-columns: 100px 1fr auto;
  gap: 1rem; align-items: center;
  padding: .9rem 1.6rem;
  border-bottom: 1px solid rgba(196,98,45,.05);
}
.prog-row:last-child { border-bottom: none; }
.prog-time { font-size: .78rem; font-weight: 500; color: var(--terracotta); }
.prog-info {}
.prog-titre { font-size: .88rem; font-weight: 500; color: var(--text); }
.prog-meta  { font-size: .75rem; color: var(--text-soft); }

/* Formulaires sidebar */
.form-sidebar {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.6rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.form-sidebar-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-mid); margin-bottom: 1.4rem;
  padding-bottom: .8rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}

/* Erreurs */
.artistes-erreurs {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.5rem;
}
.artistes-erreurs li { font-size: .85rem; color: #8B1A1A; list-style: none; padding: .15rem 0; }
.artistes-erreurs li::before { content: '⚠ '; }

.empty-row {
  padding: 2rem; text-align: center;
  color: var(--text-soft); font-size: .85rem;
}

@media (max-width: 900px) {
  .artistes-grid-layout { grid-template-columns: 1fr; }
}
CSS;

require_once '../includes/header.php';
?>

<div class="artistes-page">

  <div class="artistes-header">
    <div>
      <div class="creer-breadcrumb">
        <a href="<?= url('organisateur/dashboard.php') ?>">Mon espace</a>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        <a href="<?= url('organisateur/creer-festival.php?id=' . $festivalId) ?>"><?= e($festival['nom']) ?></a>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Artistes & Programme</span>
      </div>
      <h1 class="artistes-title">Artistes & <em>Programme</em></h1>
    </div>
  </div>

  <?php if (!empty($erreurs)): ?>
    <ul class="artistes-erreurs">
      <?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <div class="artistes-grid-layout">

    <!-- Colonne principale -->
    <div>

      <!-- Liste artistes -->
      <div class="panel-card">
        <div class="panel-card-header">
          <span class="panel-card-title">Artistes (<?= count($artistes) ?>)</span>
        </div>
        <?php if (empty($artistes)): ?>
          <p class="empty-row">Aucun artiste ajouté pour le moment.</p>
        <?php else: ?>
          <?php foreach ($artistes as $a): ?>
            <div class="artiste-row">
              <?php if (!empty($a['photo'])): ?>
                <img src="<?= imgUrl($a['photo']) ?>" alt="" class="artiste-row-photo">
              <?php else: ?>
                <div class="artiste-row-photo">
                  <?= mb_strtoupper(mb_substr($a['nom'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                </div>
              <?php endif; ?>
              <div class="artiste-row-info">
                <p class="artiste-row-nom"><?= e($a['nom']) ?></p>
                <p class="artiste-row-meta">
                  <?= !empty($a['genre'])   ? e($a['genre'])   : '' ?>
                  <?= !empty($a['origine']) ? ' · ' . e($a['origine']) : '' ?>
                </p>
              </div>
              <form method="POST" onsubmit="return confirm('Supprimer cet artiste ?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete_artiste">
                <input type="hidden" name="artiste_id" value="<?= $a['id'] ?>">
                <button type="submit" class="artiste-row-del" aria-label="Supprimer">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Programme -->
      <div class="panel-card">
        <div class="panel-card-header">
          <span class="panel-card-title">Programme (<?= count($programme) ?> créneaux)</span>
        </div>
        <?php if (empty($programme)): ?>
          <p class="empty-row">Aucun créneau programmé.</p>
        <?php else: ?>
          <?php foreach ($programme as $p): ?>
            <div class="prog-row">
              <div>
                <div class="prog-time"><?= date('d/m · H:i', strtotime($p['date_heure'])) ?></div>
                <?php if (!empty($p['duree_min'])): ?>
                  <div style="font-size:.7rem; color: var(--text-soft);"><?= $p['duree_min'] ?> min</div>
                <?php endif; ?>
              </div>
              <div class="prog-info">
                <p class="prog-titre"><?= e($p['titre']) ?></p>
                <p class="prog-meta">
                  <?= !empty($p['artiste_nom']) ? e($p['artiste_nom']) : '' ?>
                  <?= !empty($p['scene']) ? ' · ' . e($p['scene']) : '' ?>
                </p>
              </div>
              <form method="POST" onsubmit="return confirm('Supprimer ce créneau ?')">
                <?= csrfField() ?>
                <input type="hidden" name="action"  value="delete_prog">
                <input type="hidden" name="prog_id" value="<?= $p['id'] ?>">
                <button type="submit" class="artiste-row-del" aria-label="Supprimer">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar formulaires -->
    <div>

      <!-- Ajouter artiste -->
      <div class="form-sidebar">
        <p class="form-sidebar-title">Ajouter un artiste</p>
        <form method="POST" enctype="multipart/form-data">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_artiste">
          <div class="form-group">
            <label class="form-label" for="artiste_nom">Nom *</label>
            <input type="text" id="artiste_nom" name="artiste_nom" class="form-input" placeholder="Angélique Kidjo" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="artiste_genre">Genre artistique</label>
            <input type="text" id="artiste_genre" name="artiste_genre" class="form-input" placeholder="Afropop, Jazz, Théâtre…">
          </div>
          <div class="form-group">
            <label class="form-label" for="artiste_origine">Origine</label>
            <input type="text" id="artiste_origine" name="artiste_origine" class="form-input" placeholder="Bénin, Sénégal…">
          </div>
          <div class="form-group">
            <label class="form-label" for="artiste_bio">Biographie</label>
            <textarea id="artiste_bio" name="artiste_bio" class="form-input form-textarea" rows="3" placeholder="Courte biographie…"></textarea>
          </div>
          <div class="form-group">
            <label class="form-label" for="artiste_photo">Photo</label>
            <input type="file" id="artiste_photo" name="artiste_photo" class="form-input" accept="image/*" style="padding:.5rem;">
          </div>
          <button type="submit" class="btn-terra" style="width:100%;">
            <span>Ajouter l'artiste</span>
          </button>
        </form>
      </div>

      <!-- Ajouter créneau programme -->
      <div class="form-sidebar">
        <p class="form-sidebar-title">Ajouter un créneau</p>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add_prog">
          <div class="form-group">
            <label class="form-label" for="prog_titre">Titre du spectacle *</label>
            <input type="text" id="prog_titre" name="prog_titre" class="form-input" placeholder="Concert d'ouverture" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="prog_artiste_id">Artiste</label>
            <select id="prog_artiste_id" name="prog_artiste_id" class="form-input form-select">
              <option value="">Sans artiste spécifique</option>
              <?php foreach ($artistes as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="prog_date_heure">Date & heure *</label>
            <input type="datetime-local" id="prog_date_heure" name="prog_date_heure" class="form-input"
                   min="<?= $festival['date_debut'] ?>T00:00"
                   max="<?= $festival['date_fin'] ?>T23:59" required>
          </div>
          <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label" for="prog_duree">Durée (min)</label>
              <input type="number" id="prog_duree" name="prog_duree" class="form-input" placeholder="90" min="1">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label" for="prog_scene">Scène</label>
              <input type="text" id="prog_scene" name="prog_scene" class="form-input" placeholder="Scène principale">
            </div>
          </div>
          <br>
          <button type="submit" class="btn-terra" style="width:100%;">
            <span>Ajouter au programme</span>
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>