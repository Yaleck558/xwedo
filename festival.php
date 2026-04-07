<?php
// ============================================================
//  XwéDò – Détail d'un festival
//  Fichier : festival.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pdo  = getDB();
$slug = getPropre('slug', '');

if (empty($slug)) {
    http_response_code(404);
    require_once 'errors/404.php'; exit;
}

// ── Récupération du festival ─────────────────────────────────
$stmt = $pdo->prepare("
    SELECT f.*,
           c.nom AS categorie_nom, c.slug AS categorie_slug, c.icone AS categorie_icone,
           u.nom AS org_nom, u.prenom AS org_prenom
    FROM festivals f
    LEFT JOIN categories_festival c ON f.categorie_id = c.id
    LEFT JOIN utilisateurs u        ON f.organisateur_id = u.id
    WHERE f.slug = ? AND f.statut = 'publie'
    LIMIT 1
");
$stmt->execute([$slug]);
$festival = $stmt->fetch();

if (!$festival) {
    http_response_code(404);
    require_once 'errors/404.php'; exit;
}

// ── Artistes ─────────────────────────────────────────────────
$artistes = $pdo->prepare('SELECT * FROM artistes WHERE festival_id = ? ORDER BY nom');
$artistes->execute([$festival['id']]);
$artistes = $artistes->fetchAll();

// ── Programme ────────────────────────────────────────────────
$programme = $pdo->prepare("
    SELECT p.*, a.nom AS artiste_nom
    FROM programmations p
    LEFT JOIN artistes a ON p.artiste_id = a.id
    WHERE p.festival_id = ?
    ORDER BY p.date_heure ASC
");
$programme->execute([$festival['id']]);
$programme = $programme->fetchAll();

// ── Types de billets disponibles ─────────────────────────────
$billets = $pdo->prepare("
    SELECT *,
           (quantite IS NULL OR quantite - vendu > 0) AS disponible
    FROM types_billet
    WHERE festival_id = ? AND actif = 1
      AND (date_limite IS NULL OR date_limite >= CURDATE())
    ORDER BY prix ASC
");
$billets->execute([$festival['id']]);
$billets = $billets->fetchAll();

// ── Galerie images ───────────────────────────────────────────
$images = $pdo->prepare('SELECT * FROM images_festival WHERE festival_id = ? ORDER BY ordre ASC');
$images->execute([$festival['id']]);
$images = $images->fetchAll();

// ── Stats ────────────────────────────────────────────────────
$statsStmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE festival_id = ? AND statut = 'confirmee'");
$statsStmt->execute([$festival['id']]);
$totalBillets = (int) $statsStmt->fetchColumn();

$statut = statutDate($festival['date_debut'], $festival['date_fin']);

$pageTitre = e($festival['nom']) . ' – XwéDò';
$pageDesc  = !empty($festival['description']) ? tronquer(strip_tags($festival['description']), 160) : 'Découvrez ce festival sur XwéDò.';
$pageOg    = imgUrl($festival['image_principale']);

$pageCSS = <<<CSS
/* ── Page festival ───────────────────────────────────────── */

/* Hero */
.festival-hero {
  position: relative; height: min(75vh, 620px);
  display: flex; flex-direction: column; justify-content: flex-end;
  overflow: hidden;
}
.festival-hero-bg {
  position: absolute; inset: 0;
  background-size: cover; background-position: center;
  transition: transform 8s ease;
}
.festival-hero:hover .festival-hero-bg { transform: scale(1.04); }
.festival-hero-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(28,20,16,.85) 0%, rgba(28,20,16,.3) 60%, transparent 100%);
}
.festival-hero-content {
  position: relative; z-index: 2;
  max-width: 1280px; width: 100%; margin: 0 auto;
  padding: 0 5% 3.5rem;
}
.festival-hero-cat {
  display: inline-flex; align-items: center; gap: 6px;
  padding: .35rem .9rem;
  background: rgba(212,168,83,.15);
  border: 1px solid rgba(212,168,83,.3);
  border-radius: var(--radius-full);
  font-size: .72rem; font-weight: 500;
  letter-spacing: .12em; text-transform: uppercase;
  color: var(--ochre); margin-bottom: 1.2rem;
}
.festival-hero-title {
  font-family: var(--font-serif);
  font-size: clamp(2.5rem, 6vw, 5rem);
  font-weight: 300; font-style: italic;
  color: var(--cream); line-height: 1.05;
  margin-bottom: 1.2rem;
}
.festival-hero-meta {
  display: flex; align-items: center; gap: 2rem;
  flex-wrap: wrap;
}
.festival-hero-meta-item {
  display: flex; align-items: center; gap: 6px;
  font-size: .82rem; font-weight: 300;
  color: rgba(250,246,238,.65);
}
.festival-hero-statut { margin-left: auto; }

/* Corps */
.festival-body {
  max-width: 1280px; margin: 0 auto; padding: 3rem 5%;
  display: grid; grid-template-columns: 1fr 360px; gap: 4rem;
}

/* Colonne principale */
.festival-main {}
.festival-section { margin-bottom: 3.5rem; }
.festival-section-title {
  font-family: var(--font-serif);
  font-size: 1.7rem; font-weight: 300;
  color: var(--dusk); margin-bottom: 1.5rem;
  padding-bottom: .8rem;
  border-bottom: 1px solid rgba(196,98,45,.12);
}
.festival-desc {
  font-size: .95rem; line-height: 1.9;
  color: var(--text-mid); font-weight: 300;
}

/* Artistes */
.artistes-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1.5rem;
}
.artiste-card {
  text-align: center;
}
.artiste-photo {
  width: 90px; height: 90px; border-radius: 50%;
  object-fit: cover; margin: 0 auto .8rem;
  border: 2px solid rgba(196,98,45,.15);
}
.artiste-photo-placeholder {
  width: 90px; height: 90px; border-radius: 50%;
  background: var(--sand); margin: 0 auto .8rem;
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-serif); font-size: 1.6rem;
  color: var(--terracotta); border: 2px solid rgba(196,98,45,.15);
}
.artiste-nom {
  font-size: .88rem; font-weight: 500; color: var(--text);
  margin-bottom: .3rem;
}
.artiste-genre { font-size: .75rem; color: var(--text-soft); }

/* Programme */
.programme-list { display: flex; flex-direction: column; gap: 0; }
.programme-item {
  display: grid; grid-template-columns: 90px 1fr;
  gap: 1.2rem; padding: 1.2rem 0;
  border-bottom: 1px solid rgba(196,98,45,.08);
  align-items: start;
}
.programme-item:last-child { border-bottom: none; }
.programme-time {
  font-size: .78rem; font-weight: 500;
  letter-spacing: .06em; color: var(--terracotta);
  padding-top: .1rem;
}
.programme-titre { font-weight: 500; font-size: .92rem; color: var(--text); margin-bottom: .3rem; }
.programme-artiste { font-size: .82rem; color: var(--text-soft); margin-bottom: .3rem; }
.programme-scene {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: .75rem; color: var(--text-soft);
}

/* Galerie */
.galerie-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr); gap: .8rem;
}
.galerie-item {
  aspect-ratio: 1; overflow: hidden; border-radius: var(--radius-sm);
  cursor: pointer;
}
.galerie-item img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .4s ease;
}
.galerie-item:hover img { transform: scale(1.08); }

/* Sidebar */
.festival-sidebar {}
.festival-sidebar-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.8rem;
  box-shadow: var(--shadow-sm);
  position: sticky; top: 6rem;
}
.sidebar-card-title {
  font-size: .72rem; font-weight: 500;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: 1.5rem;
}
.sidebar-infos { margin-bottom: 1.8rem; }
.sidebar-info-row {
  display: flex; align-items: flex-start; gap: .8rem;
  padding: .7rem 0;
  border-bottom: 1px solid rgba(196,98,45,.06);
  font-size: .85rem;
}
.sidebar-info-row:last-child { border-bottom: none; }
.sidebar-info-row svg { color: var(--terracotta); flex-shrink: 0; margin-top: 1px; }
.sidebar-info-label { color: var(--text-soft); font-size: .75rem; display: block; margin-bottom: 2px; }
.sidebar-info-val { color: var(--text); font-weight: 400; }

/* Billets dans sidebar */
.billet-list { display: flex; flex-direction: column; gap: .8rem; margin-bottom: 1.4rem; }
.billet-item {
  border: 1.5px solid rgba(196,98,45,.15);
  border-radius: var(--radius-md);
  padding: 1rem 1.2rem;
  transition: border-color var(--transition);
  cursor: pointer;
}
.billet-item:hover,
.billet-item.selected { border-color: var(--terracotta); background: rgba(196,98,45,.03); }
.billet-item-top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: .3rem;
}
.billet-item-nom { font-size: .88rem; font-weight: 500; color: var(--text); }
.billet-item-prix {
  font-family: var(--font-serif);
  font-size: 1.1rem; font-weight: 600; color: var(--terracotta);
}
.billet-item-dispo {
  font-size: .72rem; color: var(--text-soft);
}
.billet-item-dispo.complet { color: #C0392B; }

.btn-reserver {
  width: 100%; padding: 1rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .88rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase;
  border: none; border-radius: var(--radius-full);
  cursor: pointer; transition: background var(--transition);
  display: block; text-align: center;
}
.btn-reserver:hover { background: var(--soil); }
.btn-reserver:disabled {
  background: var(--text-soft); cursor: not-allowed;
}

.sidebar-partager {
  margin-top: 1.2rem; padding-top: 1.2rem;
  border-top: 1px solid rgba(196,98,45,.08);
  display: flex; align-items: center; gap: .6rem;
}
.sidebar-partager span { font-size: .75rem; color: var(--text-soft); flex-shrink: 0; }
.share-btn {
  flex: 1; padding: .5rem;
  border: 1px solid rgba(196,98,45,.15);
  border-radius: var(--radius-sm);
  font-size: .75rem; color: var(--text-mid);
  background: none; cursor: pointer;
  transition: all var(--transition); text-align: center;
}
.share-btn:hover { border-color: var(--terracotta); color: var(--terracotta); }

@media (max-width: 1024px) {
  .festival-body { grid-template-columns: 1fr; }
  .festival-sidebar-card { position: static; }
}
@media (max-width: 600px) {
  .artistes-grid { grid-template-columns: repeat(2, 1fr); }
  .galerie-grid  { grid-template-columns: repeat(2, 1fr); }
}
CSS;

require_once 'includes/header.php';
?>

<!-- Hero -->
<div class="festival-hero pt-nav">
  <div class="festival-hero-bg" style="background-image: url('<?= imgUrl($festival['image_principale']) ?>')"></div>
  <div class="festival-hero-overlay"></div>
  <div class="festival-hero-content">
    <?php if (!empty($festival['categorie_nom'])): ?>
      <div class="festival-hero-cat">
        <?= e($festival['categorie_icone'] ?? '') ?> <?= e($festival['categorie_nom']) ?>
      </div>
    <?php endif; ?>
    <h1 class="festival-hero-title"><?= e($festival['nom']) ?></h1>
    <div class="festival-hero-meta">
      <span class="festival-hero-meta-item">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <?= periodeFestival($festival['date_debut'], $festival['date_fin']) ?>
      </span>
      <span class="festival-hero-meta-item">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?= e($festival['lieu'] ?? $festival['ville'] ?? 'Bénin') ?>
      </span>
      <?php if ($totalBillets > 0): ?>
      <span class="festival-hero-meta-item">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <?= number_format($totalBillets, 0, ',', ' ') ?> participants
      </span>
      <?php endif; ?>
      <span class="festival-hero-statut">
        <span class="badge <?= match($statut['classe']) { 'en-cours'=>'badge-green', 'a-venir'=>'badge-ochre', default=>'badge-sage' } ?>">
          <span class="statut-dot <?= match($statut['classe']) { 'en-cours'=>'dot-vert', 'a-venir'=>'dot-orange', default=>'dot-gris' } ?>"></span>
          <?= e($statut['label']) ?>
        </span>
      </span>
    </div>
  </div>
</div>

<!-- Corps -->
<div class="festival-body">

  <!-- Colonne principale -->
  <div class="festival-main">

    <!-- Description -->
    <?php if (!empty($festival['description'])): ?>
    <div class="festival-section reveal">
      <h2 class="festival-section-title">À propos</h2>
      <div class="festival-desc"><?= nl2br(e($festival['description'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Artistes -->
    <?php if (!empty($artistes)): ?>
    <div class="festival-section reveal">
      <h2 class="festival-section-title">Artistes & Intervenants</h2>
      <div class="artistes-grid">
        <?php foreach ($artistes as $a): ?>
          <div class="artiste-card">
            <?php if (!empty($a['photo'])): ?>
              <img src="<?= imgUrl($a['photo']) ?>" alt="<?= e($a['nom']) ?>" class="artiste-photo">
            <?php else: ?>
              <div class="artiste-photo-placeholder">
                <?= mb_strtoupper(mb_substr($a['nom'], 0, 1, 'UTF-8'), 'UTF-8') ?>
              </div>
            <?php endif; ?>
            <p class="artiste-nom"><?= e($a['nom']) ?></p>
            <?php if (!empty($a['genre'])): ?>
              <p class="artiste-genre"><?= e($a['genre']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Programme -->
    <?php if (!empty($programme)): ?>
    <div class="festival-section reveal">
      <h2 class="festival-section-title">Programme</h2>
      <div class="programme-list">
        <?php foreach ($programme as $p): ?>
          <div class="programme-item">
            <div class="programme-time">
              <?= date('d/m', strtotime($p['date_heure'])) ?><br>
              <?= date('H:i', strtotime($p['date_heure'])) ?>
            </div>
            <div>
              <p class="programme-titre"><?= e($p['titre']) ?></p>
              <?php if (!empty($p['artiste_nom'])): ?>
                <p class="programme-artiste"><?= e($p['artiste_nom']) ?></p>
              <?php endif; ?>
              <?php if (!empty($p['scene'])): ?>
                <span class="programme-scene">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                  <?= e($p['scene']) ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Galerie -->
    <?php if (!empty($images)): ?>
    <div class="festival-section reveal">
      <h2 class="festival-section-title">Galerie</h2>
      <div class="galerie-grid">
        <?php foreach ($images as $img): ?>
          <div class="galerie-item">
            <img src="<?= imgUrl($img['chemin']) ?>" alt="<?= e($img['legende'] ?? $festival['nom']) ?>" loading="lazy">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <aside class="festival-sidebar">
    <div class="festival-sidebar-card">

      <p class="sidebar-card-title">Informations pratiques</p>

      <div class="sidebar-infos">
        <div class="sidebar-info-row">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          <div>
            <span class="sidebar-info-label">Dates</span>
            <span class="sidebar-info-val"><?= periodeFestival($festival['date_debut'], $festival['date_fin']) ?></span>
          </div>
        </div>
        <div class="sidebar-info-row">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <div>
            <span class="sidebar-info-label">Lieu</span>
            <span class="sidebar-info-val"><?= e($festival['lieu'] ?? 'Non précisé') ?></span>
          </div>
        </div>
        <?php if (!empty($festival['ville'])): ?>
        <div class="sidebar-info-row">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
          <div>
            <span class="sidebar-info-label">Ville</span>
            <span class="sidebar-info-val"><?= e($festival['ville']) ?></span>
          </div>
        </div>
        <?php endif; ?>
        <div class="sidebar-info-row">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <div>
            <span class="sidebar-info-label">Organisateur</span>
            <span class="sidebar-info-val"><?= e($festival['org_prenom'] . ' ' . $festival['org_nom']) ?></span>
          </div>
        </div>
      </div>

      <!-- Billets -->
      <?php if (!empty($billets) && $statut['classe'] !== 'termine'): ?>
        <p class="sidebar-card-title">Réserver des billets</p>
        <div class="billet-list">
          <?php foreach ($billets as $b): ?>
            <div class="billet-item <?= !$b['disponible'] ? 'opacity-50' : '' ?>" data-id="<?= $b['id'] ?>" data-prix="<?= $b['prix'] ?>">
              <div class="billet-item-top">
                <span class="billet-item-nom"><?= e($b['nom']) ?></span>
                <span class="billet-item-prix"><?= formatPrix((float)$b['prix']) ?></span>
              </div>
              <div class="billet-item-dispo <?= !$b['disponible'] ? 'complet' : '' ?>">
                <?php if (!$b['disponible']): ?>
                  Complet
                <?php elseif ($b['quantite'] !== null): ?>
                  <?= $b['quantite'] - $b['vendu'] ?> place<?= ($b['quantite'] - $b['vendu']) > 1 ? 's' : '' ?> restante<?= ($b['quantite'] - $b['vendu']) > 1 ? 's' : '' ?>
                <?php else: ?>
                  Places disponibles
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if (estConnecte()): ?>
          <a href="<?= url('billets.php?festival=' . $festival['slug']) ?>" class="btn-reserver">
            Réserver mes billets
          </a>
        <?php else: ?>
          <a href="<?= url('login.php') ?>" class="btn-reserver">
            Se connecter pour réserver
          </a>
        <?php endif; ?>

      <?php elseif ($statut['classe'] === 'termine'): ?>
        <div style="text-align:center; padding: 1rem 0; color: var(--text-soft); font-size: .85rem;">
          Ce festival est terminé.
        </div>
      <?php endif; ?>

      <!-- Partager -->
      <div class="sidebar-partager">
        <span>Partager</span>
        <button class="share-btn" onclick="navigator.share ? navigator.share({title: '<?= e($festival['nom']) ?>', url: window.location.href}) : navigator.clipboard.writeText(window.location.href)">
          Copier le lien
        </button>
      </div>

    </div>
  </aside>

</div>

<?php require_once 'includes/footer.php'; ?>