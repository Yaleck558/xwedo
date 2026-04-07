<?php
// ============================================================
//  XwéDò – Liste des festivals
//  Fichier : festivals.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pdo = getDB();

// ── Paramètres GET ───────────────────────────────────────────
$q          = getPropre('q', '');
$categorie  = getPropre('categorie', '');
$tri        = getPropre('tri', 'date');
$ville      = getPropre('ville', '');
$page       = getPropre('page', 1);

// ── Catégories pour les filtres ──────────────────────────────
$categories = $pdo->query('SELECT * FROM categories_festival ORDER BY nom')->fetchAll();

// ── Construction de la requête ───────────────────────────────
$where  = ["f.statut = 'publie'"];
$params = [];

if (!empty($q)) {
    $where[]  = '(f.nom LIKE ? OR f.description LIKE ? OR f.lieu LIKE ?)';
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if (!empty($categorie)) {
    $where[]  = 'c.slug = ?';
    $params[] = $categorie;
}
if (!empty($ville)) {
    $where[]  = 'f.ville = ?';
    $params[] = $ville;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderSQL = match($tri) {
    'populaire' => 'ORDER BY total_reservations DESC, f.date_debut ASC',
    'nom'       => 'ORDER BY f.nom ASC',
    'recent'    => 'ORDER BY f.created_at DESC',
    default     => 'ORDER BY f.date_debut ASC',
};

// ── Comptage total ───────────────────────────────────────────
$countSQL = "
    SELECT COUNT(DISTINCT f.id)
    FROM festivals f
    LEFT JOIN categories_festival c ON f.categorie_id = c.id
    $whereSQL
";
$stmtCount = $pdo->prepare($countSQL);
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();

$pag    = paginer($total, FESTIVALS_PAR_PAGE, $page);
$urlBase = url('festivals.php?' . http_build_query(array_filter(['q'=>$q,'categorie'=>$categorie,'tri'=>$tri,'ville'=>$ville])));

// ── Récupération des festivals ───────────────────────────────
$sql = "
    SELECT
        f.*,
        c.nom        AS categorie_nom,
        c.slug       AS categorie_slug,
        c.icone      AS categorie_icone,
        u.nom        AS org_nom,
        u.prenom     AS org_prenom,
        COUNT(DISTINCT r.id) AS total_reservations,
        MIN(tb.prix) AS prix_min
    FROM festivals f
    LEFT JOIN categories_festival c  ON f.categorie_id    = c.id
    LEFT JOIN utilisateurs u         ON f.organisateur_id = u.id
    LEFT JOIN reservations r         ON f.id = r.festival_id AND r.statut = 'confirmee'
    LEFT JOIN types_billet tb        ON f.id = tb.festival_id AND tb.actif = 1
    $whereSQL
    GROUP BY f.id
    $orderSQL
    LIMIT ? OFFSET ?
";
$stmtF = $pdo->prepare($sql);
$stmtF->execute([...$params, FESTIVALS_PAR_PAGE, $pag['offset']]);
$festivals = $stmtF->fetchAll();

// ── Villes disponibles pour le filtre ───────────────────────
$villes = $pdo->query("SELECT DISTINCT ville FROM festivals WHERE statut='publie' AND ville IS NOT NULL ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);

$pageTitre = 'Festivals – XwéDò';
$pageDesc  = 'Découvrez tous les festivals culturels du Bénin : Vodun Days, WeLovEya, FInAB et bien plus.';

$pageCSS = <<<CSS
/* ── Page festivals ─────────────────────────────────────── */
.festivals-page { padding: 7rem 0 4rem; }

/* Hero bande */
.festivals-hero {
  background: var(--sand);
  padding: 4rem 5% 3rem;
  position: relative;
  overflow: hidden;
  margin-bottom: 3rem;
}
.festivals-hero::after {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(
    135deg,
    transparent 0px, transparent 28px,
    rgba(196,98,45,.03) 28px, rgba(196,98,45,.03) 29px
  );
  pointer-events: none;
}
.festivals-hero-inner {
  max-width: 1280px; margin: 0 auto;
  display: flex; align-items: flex-end;
  justify-content: space-between; gap: 2rem; flex-wrap: wrap;
  position: relative; z-index: 1;
}
.festivals-hero-title {
  font-family: var(--font-serif);
  font-size: clamp(2.4rem, 5vw, 4rem);
  font-weight: 300; color: var(--dusk); line-height: 1.1;
}
.festivals-hero-title em { font-style: italic; color: var(--terracotta); }
.festivals-hero-count {
  font-size: .85rem; color: var(--text-soft);
  font-weight: 300; margin-top: .6rem;
}

/* Barre de recherche */
.festivals-search {
  display: flex; align-items: center;
  background: var(--white);
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-full);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  min-width: 320px;
}
.festivals-search input {
  flex: 1; padding: .85rem 1.2rem;
  border: none; outline: none;
  font-size: .9rem; font-family: var(--font-sans);
  color: var(--text); background: transparent;
}
.festivals-search input::placeholder { color: var(--text-soft); }
.festivals-search button {
  padding: .85rem 1.4rem;
  background: var(--terracotta); color: var(--white);
  border: none; cursor: pointer;
  transition: background var(--transition);
  display: flex; align-items: center;
}
.festivals-search button:hover { background: var(--soil); }

/* Filtres */
.festivals-filters {
  max-width: 1280px; margin: 0 auto 2.5rem;
  padding: 0 5%;
  display: flex; align-items: center;
  gap: 1rem; flex-wrap: wrap;
}
.filter-label {
  font-size: .75rem; font-weight: 500;
  letter-spacing: .12em; text-transform: uppercase;
  color: var(--text-soft); flex-shrink: 0;
}
.filter-select {
  padding: .55rem 2rem .55rem .9rem;
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-full);
  font-size: .82rem; font-family: var(--font-sans);
  color: var(--text-mid); background: var(--white);
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' fill='none' stroke='%23A8937C' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right .7rem center;
  cursor: pointer; outline: none;
  transition: border-color var(--transition);
}
.filter-select:focus { border-color: var(--terracotta); }

/* Catégories pills */
.cat-pills {
  display: flex; gap: .5rem; flex-wrap: wrap; align-items: center;
  max-width: 1280px; margin: 0 auto 2.5rem; padding: 0 5%;
}
.cat-pill {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .45rem 1.1rem;
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-full);
  font-size: .8rem; color: var(--text-mid);
  background: var(--white);
  transition: all var(--transition);
  text-decoration: none;
}
.cat-pill:hover,
.cat-pill.active {
  background: var(--terracotta); color: var(--white);
  border-color: var(--terracotta);
}

/* Grille festivals */
.festivals-grid {
  max-width: 1280px; margin: 0 auto; padding: 0 5%;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 2rem;
}

/* Card festival */
.fest-card {
  background: var(--white);
  border-radius: var(--radius-lg);
  overflow: hidden;
  border: 1px solid rgba(196,98,45,.08);
  box-shadow: var(--shadow-sm);
  transition: transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s;
  display: flex; flex-direction: column;
}
.fest-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-lg);
}
.fest-card-img {
  position: relative; height: 210px; overflow: hidden;
}
.fest-card-img img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .6s cubic-bezier(.16,1,.3,1);
}
.fest-card:hover .fest-card-img img { transform: scale(1.06); }
.fest-card-img-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(28,20,16,.65) 0%, transparent 55%);
}
.fest-card-cat {
  position: absolute; top: 1rem; left: 1rem;
  display: inline-flex; align-items: center; gap: 5px;
  padding: .3rem .8rem;
  background: rgba(250,246,238,.92);
  border-radius: var(--radius-full);
  font-size: .7rem; font-weight: 500;
  letter-spacing: .06em; text-transform: uppercase;
  color: var(--terracotta);
}
.fest-card-statut {
  position: absolute; top: 1rem; right: 1rem;
}
.fest-card-body {
  padding: 1.4rem 1.6rem; flex: 1;
  display: flex; flex-direction: column;
}
.fest-card-date {
  font-size: .75rem; letter-spacing: .1em;
  text-transform: uppercase; color: var(--terracotta);
  font-weight: 500; margin-bottom: .6rem;
}
.fest-card-name {
  font-family: var(--font-serif);
  font-size: 1.45rem; font-weight: 400;
  color: var(--dusk); line-height: 1.2;
  margin-bottom: .5rem;
}
.fest-card-lieu {
  display: flex; align-items: center; gap: 5px;
  font-size: .82rem; color: var(--text-soft); font-weight: 300;
  margin-bottom: 1rem;
}
.fest-card-desc {
  font-size: .85rem; color: var(--text-mid);
  font-weight: 300; line-height: 1.7;
  flex: 1; margin-bottom: 1.2rem;
}
.fest-card-footer {
  display: flex; align-items: center;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid rgba(196,98,45,.08);
}
.fest-card-prix {
  font-family: var(--font-serif);
  font-size: 1.1rem; font-weight: 600;
  color: var(--terracotta);
}
.fest-card-prix small {
  font-family: var(--font-sans);
  font-size: .7rem; font-weight: 400;
  color: var(--text-soft); display: block;
}
.fest-card-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: .55rem 1.2rem;
  background: var(--terracotta); color: var(--white);
  font-size: .78rem; font-weight: 500;
  border-radius: var(--radius-full);
  transition: background var(--transition);
}
.fest-card-btn:hover { background: var(--soil); }

/* Vide */
.festivals-empty {
  max-width: 1280px; margin: 0 auto; padding: 4rem 5%;
  text-align: center;
}
.festivals-empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: .4; }
.festivals-empty h3 {
  font-family: var(--font-serif); font-weight: 300;
  font-size: 1.6rem; color: var(--dusk); margin-bottom: .8rem;
}
.festivals-empty p { color: var(--text-soft); font-size: .9rem; }

@media (max-width: 768px) {
  .festivals-hero-inner { flex-direction: column; align-items: flex-start; }
  .festivals-search { min-width: 100%; width: 100%; }
  .festivals-grid { grid-template-columns: 1fr; }
}
CSS;

require_once 'includes/header.php';
?>

<div class="festivals-page">

  <!-- Hero bande -->
  <div class="festivals-hero">
    <div class="festivals-hero-inner">
      <div>
        <div class="section-pretitle">
          <span class="pretitle-line"></span>
          <span class="pretitle-text">Fesivals béninois</span>
        </div>
        <h1 class="festivals-hero-title">
          Les <em>festivals</em><br>du Bénin
        </h1>
        <p class="festivals-hero-count">
          <?= $total ?> festival<?= $total > 1 ? 's' : '' ?> disponible<?= $total > 1 ? 's' : '' ?>
        </p>
      </div>

      <!-- Recherche -->
      <form method="GET" action="<?= url('festivals.php') ?>" class="festivals-search">
        <input
          type="text"
          name="q"
          value="<?= e($q) ?>"
          placeholder="Rechercher un festival, un lieu…"
          aria-label="Rechercher"
        >
        <button type="submit" aria-label="Lancer la recherche">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
      </form>
    </div>
  </div>

  <!-- Pills catégories -->
  <div class="cat-pills">
    <a href="<?= url('festivals.php?' . http_build_query(array_filter(['q'=>$q,'tri'=>$tri,'ville'=>$ville]))) ?>"
       class="cat-pill <?= empty($categorie) ? 'active' : '' ?>">
      Tous
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?= url('festivals.php?' . http_build_query(array_filter(['q'=>$q,'categorie'=>$cat['slug'],'tri'=>$tri,'ville'=>$ville]))) ?>"
         class="cat-pill <?= $categorie === $cat['slug'] ? 'active' : '' ?>">
        <?= e($cat['icone'] ?? '') ?> <?= e($cat['nom']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Filtres -->
  <form method="GET" action="<?= url('festivals.php') ?>" class="festivals-filters">
    <?php if (!empty($q)):       ?><input type="hidden" name="q"         value="<?= e($q) ?>"><?php endif; ?>
    <?php if (!empty($categorie)):?><input type="hidden" name="categorie" value="<?= e($categorie) ?>"><?php endif; ?>

    <span class="filter-label">Trier par</span>
    <select name="tri" class="filter-select" onchange="this.form.submit()">
      <option value="date"      <?= $tri === 'date'      ? 'selected' : '' ?>>Date</option>
      <option value="populaire" <?= $tri === 'populaire' ? 'selected' : '' ?>>Popularité</option>
      <option value="nom"       <?= $tri === 'nom'       ? 'selected' : '' ?>>Nom A–Z</option>
      <option value="recent"    <?= $tri === 'recent'    ? 'selected' : '' ?>>Récents</option>
    </select>

    <?php if (!empty($villes)): ?>
      <span class="filter-label">Ville</span>
      <select name="ville" class="filter-select" onchange="this.form.submit()">
        <option value="">Toutes les villes</option>
        <?php foreach ($villes as $v): ?>
          <option value="<?= e($v) ?>" <?= $ville === $v ? 'selected' : '' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
  </form>

  <!-- Grille -->
  <?php if (empty($festivals)): ?>
    <div class="festivals-empty">
      <div class="festivals-empty-icon">🥁</div>
      <h3>Aucun festival trouvé</h3>
      <p>Essayez d'autres filtres ou revenez bientôt — de nouveaux festivals arrivent régulièrement.</p>
    </div>
  <?php else: ?>
    <div class="festivals-grid">
      <?php foreach ($festivals as $f):
        $statut  = statutDate($f['date_debut'], $f['date_fin']);
        $img     = imgUrl($f['image_principale'], 'default-festival.jpg');
      ?>
        <article class="fest-card reveal">
          <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" class="fest-card-img" aria-label="<?= e($f['nom']) ?>">
            <img src="<?= $img ?>" alt="<?= e($f['nom']) ?>" loading="lazy">
            <div class="fest-card-img-overlay"></div>

            <?php if (!empty($f['categorie_nom'])): ?>
              <span class="fest-card-cat">
                <?= e($f['categorie_icone'] ?? '') ?> <?= e($f['categorie_nom']) ?>
              </span>
            <?php endif; ?>

            <span class="fest-card-statut">
              <span class="badge <?= match($statut['classe']) {
                'en-cours' => 'badge-green',
                'a-venir'  => 'badge-ochre',
                default    => 'badge-sage'
              } ?>">
                <span class="statut-dot <?= match($statut['classe']) {
                  'en-cours' => 'dot-vert',
                  'a-venir'  => 'dot-orange',
                  default    => 'dot-gris'
                } ?>"></span>
                <?= e($statut['label']) ?>
              </span>
            </span>
          </a>

          <div class="fest-card-body">
            <p class="fest-card-date"><?= periodeFestival($f['date_debut'], $f['date_fin']) ?></p>
            <h2 class="fest-card-name">
              <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>"><?= e($f['nom']) ?></a>
            </h2>
            <p class="fest-card-lieu">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <?= e($f['lieu'] ?? $f['ville'] ?? 'Bénin') ?>
            </p>
            <?php if (!empty($f['description'])): ?>
              <p class="fest-card-desc"><?= e(tronquer($f['description'], 120)) ?></p>
            <?php endif; ?>

            <div class="fest-card-footer">
              <div class="fest-card-prix">
                <?= $f['prix_min'] !== null ? formatPrix((float)$f['prix_min']) : 'Gratuit' ?>
                <small><?= $f['prix_min'] > 0 ? 'à partir de' : 'Entrée libre' ?></small>
              </div>
              <a href="<?= url('festival.php?slug=' . e($f['slug'])) ?>" class="fest-card-btn">
                Voir
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?= htmlPagination($pag, $urlBase) ?>
  <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>