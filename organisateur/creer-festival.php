<?php
// ============================================================
//  XwéDò – Créer / Éditer un festival
//  Fichier : organisateur/creer-festival.php
// ============================================================
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('organisateur');

$pdo       = getDB();
$uid       = userId();
$festivalId = getPropre('id', 0);
$modeEdit  = $festivalId > 0;
$festival  = null;
$erreurs   = [];

// ── Édition : charger le festival existant ───────────────────
if ($modeEdit) {
    $stmt = $pdo->prepare('SELECT * FROM festivals WHERE id = ? AND organisateur_id = ? LIMIT 1');
    $stmt->execute([$festivalId, $uid]);
    $festival = $stmt->fetch();
    if (!$festival) {
        setFlash('erreur', 'Festival introuvable.');
        rediriger('organisateur/dashboard.php');
    }
}

// ── Catégories ───────────────────────────────────────────────
$categories = $pdo->query('SELECT * FROM categories_festival ORDER BY nom')->fetchAll();

// ── Traitement POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée.';
    } else {
        $donnees = [
            'nom'          => postPropre('nom'),
            'description'  => trim($_POST['description'] ?? ''),
            'lieu'         => postPropre('lieu'),
            'ville'        => postPropre('ville'),
            'date_debut'   => postPropre('date_debut'),
            'date_fin'     => postPropre('date_fin'),
            'categorie_id' => (int) ($_POST['categorie_id'] ?? 0) ?: null,
            'site_web'     => postPropre('site_web'),
            'capacite_totale' => (int) ($_POST['capacite_totale'] ?? 0) ?: null,
            'statut'       => in_array($_POST['statut'] ?? '', ['brouillon','en_attente']) ? $_POST['statut'] : 'en_attente',
        ];

        // Validations
        if (empty($donnees['nom']))        $erreurs[] = 'Le nom du festival est requis.';
        if (empty($donnees['date_debut'])) $erreurs[] = 'La date de début est requise.';
        if (empty($donnees['date_fin']))   $erreurs[] = 'La date de fin est requise.';
        if (!empty($donnees['date_debut']) && !empty($donnees['date_fin'])
            && $donnees['date_fin'] < $donnees['date_debut']) {
            $erreurs[] = 'La date de fin doit être après la date de début.';
        }

        // Upload image
        $imageChemin = $festival['image_principale'] ?? null;
        if (!empty($_FILES['image_principale']['name'])) {
            $upload = uploadImage($_FILES['image_principale'], 'festivals');
            if ($upload) {
                $imageChemin = $upload;
            } else {
                $erreurs[] = 'Image invalide (formats acceptés : JPG, PNG, WEBP — max 5 Mo).';
            }
        }

        if (empty($erreurs)) {
            if ($modeEdit) {
                // Mise à jour
                $pdo->prepare("
                    UPDATE festivals SET
                        nom=?, description=?, lieu=?, ville=?,
                        date_debut=?, date_fin=?, categorie_id=?,
                        site_web=?, capacite_totale=?, statut=?, image_principale=?
                    WHERE id = ? AND organisateur_id = ?
                ")->execute([
                    $donnees['nom'], $donnees['description'], $donnees['lieu'], $donnees['ville'],
                    $donnees['date_debut'], $donnees['date_fin'], $donnees['categorie_id'],
                    $donnees['site_web'] ?: null, $donnees['capacite_totale'],
                    $donnees['statut'], $imageChemin, $festivalId, $uid
                ]);
                setFlash('succes', 'Festival mis à jour avec succès.');
            } else {
                // Création
                $slug = slugUnique($donnees['nom'], 'festivals', 'slug');
                $pdo->prepare("
                    INSERT INTO festivals
                        (organisateur_id, categorie_id, nom, slug, description, lieu, ville,
                         date_debut, date_fin, image_principale, site_web, capacite_totale, statut)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                ")->execute([
                    $uid, $donnees['categorie_id'], $donnees['nom'], $slug,
                    $donnees['description'], $donnees['lieu'], $donnees['ville'],
                    $donnees['date_debut'], $donnees['date_fin'],
                    $imageChemin, $donnees['site_web'] ?: null,
                    $donnees['capacite_totale'], $donnees['statut']
                ]);
                $newId = (int) $pdo->lastInsertId();
                setFlash('succes', 'Festival créé ! Ajoutez maintenant les artistes et les billets.');
                rediriger('organisateur/creer-festival.php?id=' . $newId);
            }
            rediriger('organisateur/dashboard.php');
        }
    }
}

// Pré-remplissage en mode édition
$val = fn(string $key, string $default = '') => e($festival[$key] ?? $default);
$pageTitre = ($modeEdit ? 'Modifier ' . e($festival['nom']) : 'Créer un festival') . ' – XwéDò';

$pageCSS = <<<CSS
/* ── Créer festival ──────────────────────────────────────── */
.creer-page { padding: 7rem 5% 4rem; max-width: 900px; margin: 0 auto; }

.creer-header { margin-bottom: 2.5rem; }
.creer-title {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 3.5vw, 2.6rem);
  font-weight: 300; color: var(--dusk);
}
.creer-title em { font-style: italic; color: var(--terracotta); }
.creer-breadcrumb {
  display: flex; align-items: center; gap: .5rem;
  font-size: .78rem; color: var(--text-soft);
  margin-bottom: 1rem;
}
.creer-breadcrumb a { color: var(--text-soft); transition: color var(--transition); }
.creer-breadcrumb a:hover { color: var(--terracotta); }
.creer-breadcrumb span { color: var(--text-mid); }

/* Sections formulaire */
.form-section {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 2rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.form-section-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-soft);
  margin-bottom: 1.6rem; padding-bottom: 1rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
  display: flex; align-items: center; gap: .6rem;
}
.form-section-title svg { color: var(--terracotta); }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; }

/* Upload image */
.upload-zone {
  border: 2px dashed rgba(196,98,45,.25);
  border-radius: var(--radius-md);
  padding: 2rem; text-align: center;
  cursor: pointer; transition: all var(--transition);
  position: relative; overflow: hidden;
  background: rgba(237,224,200,.1);
}
.upload-zone:hover, .upload-zone.dragover {
  border-color: var(--terracotta);
  background: rgba(196,98,45,.04);
}
.upload-zone input[type="file"] {
  position: absolute; inset: 0; opacity: 0;
  cursor: pointer; width: 100%;
}
.upload-icon { font-size: 2rem; margin-bottom: .6rem; opacity: .5; }
.upload-text { font-size: .85rem; color: var(--text-mid); }
.upload-hint { font-size: .75rem; color: var(--text-soft); margin-top: .3rem; }
.upload-preview {
  width: 100%; height: 180px; object-fit: cover;
  border-radius: var(--radius-sm); margin-bottom: .8rem;
  display: none;
}
.upload-preview.show { display: block; }

/* Statut toggle */
.statut-opts { display: flex; gap: .8rem; }
.statut-opt {
  flex: 1; padding: .8rem;
  border: 2px solid rgba(196,98,45,.15);
  border-radius: var(--radius-md);
  cursor: pointer; text-align: center;
  transition: all var(--transition);
}
.statut-opt input { display: none; }
.statut-opt:has(input:checked) {
  border-color: var(--terracotta);
  background: rgba(196,98,45,.04);
}
.statut-opt-label { font-size: .85rem; font-weight: 500; color: var(--text-mid); }
.statut-opt-sub { font-size: .72rem; color: var(--text-soft); margin-top: .2rem; }

/* Actions */
.creer-actions {
  display: flex; align-items: center; gap: 1rem;
  flex-wrap: wrap;
}

/* Erreurs */
.creer-erreurs {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.5rem;
}
.creer-erreurs li { font-size: .85rem; color: #8B1A1A; list-style: none; padding: .15rem 0; }
.creer-erreurs li::before { content: '⚠ '; }

@media (max-width: 640px) {
  .form-row, .form-row-3 { grid-template-columns: 1fr; }
  .statut-opts { flex-direction: column; }
}
CSS;

require_once '../includes/header.php';
?>

<div class="creer-page">

  <!-- Breadcrumb -->
  <div class="creer-header">
    <div class="creer-breadcrumb">
      <a href="<?= url('organisateur/dashboard.php') ?>">Mon espace</a>
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      <span><?= $modeEdit ? e($festival['nom']) : 'Nouveau festival' ?></span>
    </div>
    <h1 class="creer-title">
      <?= $modeEdit ? 'Modifier <em>' . e($festival['nom']) . '</em>' : 'Créer un <em>festival</em>' ?>
    </h1>
  </div>

  <?php if (!empty($erreurs)): ?>
    <ul class="creer-erreurs">
      <?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="form-festival">
    <?= csrfField() ?>

    <!-- Informations générales -->
    <div class="form-section">
      <p class="form-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Informations générales
      </p>

      <div class="form-group">
        <label class="form-label" for="nom">Nom du festival *</label>
        <input type="text" id="nom" name="nom" class="form-input"
               value="<?= $val('nom') ?>" placeholder="Ex : Vodun Days 2026" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-input form-textarea"
                  placeholder="Décrivez le festival, son histoire, ce que les visiteurs peuvent attendre…" rows="5"><?= $val('description') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="categorie_id">Catégorie</label>
          <select id="categorie_id" name="categorie_id" class="form-input form-select">
            <option value="">Sans catégorie</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($festival['categorie_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                <?= e($c['icone'] ?? '') ?> <?= e($c['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="site_web">Site web</label>
          <input type="url" id="site_web" name="site_web" class="form-input"
                 value="<?= $val('site_web') ?>" placeholder="https://…">
        </div>
      </div>
    </div>

    <!-- Lieu & Dates -->
    <div class="form-section">
      <p class="form-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Lieu & Dates
      </p>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="lieu">Lieu / Adresse</label>
          <input type="text" id="lieu" name="lieu" class="form-input"
                 value="<?= $val('lieu') ?>" placeholder="Place de l'Étoile Rouge, Cotonou">
        </div>
        <div class="form-group">
          <label class="form-label" for="ville">Ville</label>
          <select id="ville" name="ville" class="form-input form-select">
            <option value="">Choisir…</option>
            <?php
            $villes = ['Cotonou','Porto-Novo','Ouidah','Parakou','Natitingou','Abomey','Nikki','Savalou','Lokossa','Kandi','Djougou','Bohicon'];
            foreach ($villes as $v):
            ?>
              <option value="<?= e($v) ?>" <?= ($festival['ville'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row-3">
        <div class="form-group">
          <label class="form-label" for="date_debut">Date de début *</label>
          <input type="date" id="date_debut" name="date_debut" class="form-input"
                 value="<?= $val('date_debut') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="date_fin">Date de fin *</label>
          <input type="date" id="date_fin" name="date_fin" class="form-input"
                 value="<?= $val('date_fin') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="capacite_totale">Capacité max</label>
          <input type="number" id="capacite_totale" name="capacite_totale" class="form-input"
                 value="<?= $val('capacite_totale') ?>" placeholder="Illimitée si vide" min="1">
        </div>
      </div>
    </div>

    <!-- Image -->
    <div class="form-section">
      <p class="form-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        Image principale
      </p>
      <div class="upload-zone" id="upload-zone">
        <input type="file" name="image_principale" id="image-input" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($festival['image_principale'])): ?>
          <img src="<?= imgUrl($festival['image_principale']) ?>" class="upload-preview show" id="preview-img" alt="Aperçu">
        <?php else: ?>
          <img src="" class="upload-preview" id="preview-img" alt="Aperçu">
        <?php endif; ?>
        <div class="upload-icon" id="upload-placeholder-icon">🖼️</div>
        <p class="upload-text">Glissez une image ou cliquez pour parcourir</p>
        <p class="upload-hint">JPG, PNG ou WEBP — max 5 Mo — Recommandé : 1200 × 600 px</p>
      </div>
    </div>

    <!-- Statut de publication -->
    <div class="form-section">
      <p class="form-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Publication
      </p>
      <div class="statut-opts">
        <label class="statut-opt">
          <input type="radio" name="statut" value="brouillon"
                 <?= ($festival['statut'] ?? 'brouillon') === 'brouillon' ? 'checked' : '' ?>>
          <div class="statut-opt-label">💾 Brouillon</div>
          <div class="statut-opt-sub">Sauvegardé, non visible</div>
        </label>
        <label class="statut-opt">
          <input type="radio" name="statut" value="en_attente"
                 <?= ($festival['statut'] ?? '') === 'en_attente' ? 'checked' : '' ?>>
          <div class="statut-opt-label">⏳ Soumettre pour validation</div>
          <div class="statut-opt-sub">L'admin valide avant publication</div>
        </label>
      </div>
    </div>

    <!-- Actions -->
    <div class="creer-actions">
      <button type="submit" class="btn-terra">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        <span><?= $modeEdit ? 'Enregistrer les modifications' : 'Créer le festival' ?></span>
      </button>
      <a href="<?= url('organisateur/dashboard.php') ?>" class="btn-link">Annuler</a>

      <?php if ($modeEdit): ?>
        <div style="margin-left: auto; display:flex; gap:.8rem;">
          <a href="<?= url('organisateur/artistes.php?festival=' . $festivalId) ?>" class="btn-terra-outline">
            Gérer les artistes
          </a>
          <a href="<?= url('organisateur/billets.php?festival=' . $festivalId) ?>" class="btn-terra-outline">
            Gérer les billets
          </a>
        </div>
      <?php endif; ?>
    </div>

  </form>
</div>

<?php
$pageJS = <<<JS
// Prévisualisation image
const imageInput    = document.getElementById('image-input');
const previewImg    = document.getElementById('preview-img');
const placeholderIc = document.getElementById('upload-placeholder-icon');

imageInput?.addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    previewImg.src = ev.target.result;
    previewImg.classList.add('show');
    placeholderIc.style.display = 'none';
  };
  reader.readAsDataURL(file);
});

// Date fin >= date debut
document.getElementById('date_debut')?.addEventListener('change', function() {
  const fin = document.getElementById('date_fin');
  if (fin.value && fin.value < this.value) fin.value = this.value;
  fin.min = this.value;
});
JS;
require_once '../includes/footer.php';
?>