<?php
// ============================================================
//  XwéDò – Inscription
//  Fichier : register.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (estConnecte()) {
    rediriger(urlApresConnexion(userRole()));
}

$erreurs = [];
$donnees = ['nom' => '', 'prenom' => '', 'email' => '', 'ville' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée, veuillez réessayer.';
    } else {
        $donnees = [
            'nom'           => postPropre('nom'),
            'prenom'        => postPropre('prenom'),
            'email'         => postPropre('email'),
            'ville'         => postPropre('ville'),
            'mot_de_passe'  => $_POST['mot_de_passe'] ?? '',
        ];
        $confirm = $_POST['confirmer_mdp'] ?? '';

        // Validation
        if (empty($donnees['nom']))    $erreurs[] = 'Le nom est requis.';
        if (empty($donnees['prenom'])) $erreurs[] = 'Le prénom est requis.';
        if ($donnees['mot_de_passe'] !== $confirm) {
            $erreurs[] = 'Les mots de passe ne correspondent pas.';
        }

        if (empty($erreurs)) {
            $resultat = inscrireUtilisateur($donnees);
            if (isset($resultat['succes'])) {
                // Connexion automatique après inscription
                connecterUtilisateur($donnees['email'], $donnees['mot_de_passe']);
                setFlash('succes', 'Bienvenue sur XwéDò, ' . e($donnees['prenom']) . ' !');
                rediriger('index.php');
            } else {
                $erreurs[] = $resultat['erreur'];
            }
        }
    }
}

$pageTitre = 'Créer un compte – XwéDò';
$pageDesc  = 'Inscrivez-vous sur XwéDò pour réserver vos billets et ne manquer aucun festival culturel du Bénin.';

$pageCSS = <<<CSS
/* ── Réutilise le layout auth de login.php ───────────────── */
.auth-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
}
.auth-visual {
  position: relative; background: var(--dusk); overflow: hidden;
  display: flex; flex-direction: column; justify-content: flex-end; padding: 4rem;
}
.auth-visual-bg {
  position: absolute; inset: 0;
  background-image: url('https://images.unsplash.com/photo-1504609813442-a8924e83f76e?w=900&q=80');
  background-size: cover; background-position: center; opacity: .25;
}
.auth-visual-kente {
  position: absolute; top: 0; left: 0; right: 0; height: 5px;
  background: repeating-linear-gradient(
    90deg,
    var(--terracotta) 0px, var(--terracotta) 18px,
    var(--ochre) 18px, var(--ochre) 32px,
    var(--sage) 32px, var(--sage) 46px,
    var(--terracotta-dark) 46px, var(--terracotta-dark) 60px
  );
}
.auth-visual-logo {
  position: absolute; top: 2.5rem; left: 4rem;
  font-family: var(--font-display); font-size: 1.8rem; color: var(--ochre); z-index: 2;
}
.auth-visual-logo::after {
  content: '•'; color: var(--terracotta-light);
  margin-left: 3px; font-size: .7rem; vertical-align: super;
}
.auth-visual-content { position: relative; z-index: 2; }
.auth-visual-quote {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 300; font-style: italic;
  color: var(--cream); line-height: 1.2; margin-bottom: 1.5rem;
}
.auth-visual-quote em { color: var(--ochre); font-style: normal; }
.auth-visual-sub { font-size: .85rem; font-weight: 300; color: rgba(250,246,238,.5); }
.auth-visual-arcs { position: absolute; bottom: -120px; right: -80px; z-index: 1; }

.auth-form-panel {
  display: flex; flex-direction: column; justify-content: center;
  padding: 5rem 8% 4rem; background: var(--cream); overflow-y: auto;
}
.auth-form-inner { max-width: 460px; width: 100%; }
.auth-pretitle { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.auth-pretitle-line { width: 32px; height: 1px; background: var(--terracotta); }
.auth-pretitle-text {
  font-size: .72rem; letter-spacing: .2em;
  text-transform: uppercase; color: var(--terracotta); font-weight: 500;
}
.auth-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 3vw, 2.8rem);
  font-weight: 300; color: var(--dusk); line-height: 1.1; margin-bottom: .8rem;
}
.auth-title em { font-style: italic; color: var(--terracotta); }
.auth-subtitle { font-size: .9rem; color: var(--text-soft); font-weight: 300; margin-bottom: 2.5rem; line-height: 1.7; }

/* Grid 2 colonnes pour nom/prénom */
.auth-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
}

.auth-field { margin-bottom: 1.3rem; }
.auth-field-label {
  display: block; font-size: .75rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-mid); margin-bottom: .55rem;
}
.auth-field-wrap { position: relative; }
.auth-field-wrap svg {
  position: absolute; left: 1rem; top: 50%;
  transform: translateY(-50%); color: var(--text-soft); pointer-events: none;
}
.auth-input {
  width: 100%; padding: .9rem 1rem .9rem 2.8rem;
  background: var(--white); border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-sm); font-size: .92rem; color: var(--text);
  transition: border-color var(--transition), box-shadow var(--transition); outline: none;
}
.auth-input:focus {
  border-color: var(--terracotta);
  box-shadow: 0 0 0 3px rgba(196,98,45,.1);
}
.auth-input::placeholder { color: var(--text-soft); }
.auth-input.erreur { border-color: #C0392B; }
.auth-input-no-icon { padding-left: 1rem; }

.auth-pwd-toggle {
  position: absolute; right: 1rem; top: 50%;
  transform: translateY(-50%); color: var(--text-soft);
  cursor: pointer; background: none; border: none; padding: 0;
  display: flex; align-items: center; transition: color var(--transition);
}
.auth-pwd-toggle:hover { color: var(--terracotta); }

/* Force de mot de passe */
.pwd-strength { margin-top: .6rem; }
.pwd-strength-bar {
  height: 3px; border-radius: 2px;
  background: rgba(196,98,45,.12); overflow: hidden; margin-bottom: .3rem;
}
.pwd-strength-fill {
  height: 100%; border-radius: 2px;
  transition: width .4s ease, background .4s;
  width: 0%;
}
.pwd-strength-label { font-size: .72rem; color: var(--text-soft); }

/* CGU checkbox */
.auth-cgu {
  display: flex; align-items: flex-start; gap: .8rem;
  margin-bottom: 1.8rem;
}
.auth-cgu input { margin-top: 3px; accent-color: var(--terracotta); flex-shrink: 0; }
.auth-cgu label {
  font-size: .82rem; color: var(--text-mid); line-height: 1.6; cursor: pointer;
}
.auth-cgu label a { color: var(--terracotta); transition: color var(--transition); }
.auth-cgu label a:hover { color: var(--soil); }

.auth-submit {
  width: 100%; padding: 1rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .88rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  border: none; border-radius: var(--radius-full);
  cursor: pointer; transition: background var(--transition), transform .2s;
  position: relative; overflow: hidden; margin-bottom: 1.8rem;
}
.auth-submit::before {
  content: ''; position: absolute; inset: 0;
  background: var(--soil); transform: scaleX(0);
  transform-origin: left; transition: transform .4s;
}
.auth-submit:hover::before { transform: scaleX(1); }
.auth-submit span { position: relative; z-index: 1; }

.auth-sep { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.8rem; }
.auth-sep::before, .auth-sep::after { content: ''; flex: 1; height: 1px; background: rgba(196,98,45,.15); }
.auth-sep span { font-size: .75rem; color: var(--text-soft); white-space: nowrap; }

.auth-switch { text-align: center; font-size: .85rem; color: var(--text-mid); }
.auth-switch a { color: var(--terracotta); font-weight: 500; transition: color var(--transition); }
.auth-switch a:hover { color: var(--soil); }

.auth-erreurs {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.8rem;
}
.auth-erreurs li { font-size: .85rem; color: #8B1A1A; list-style: none; padding: .15rem 0; }
.auth-erreurs li::before { content: '⚠ '; }

/* Onglets participant / organisateur */
.auth-role-tabs {
  display: flex; gap: .5rem;
  margin-bottom: 2rem;
  background: var(--sand); border-radius: var(--radius-full);
  padding: .3rem;
}
.auth-role-tab {
  flex: 1; padding: .6rem 1rem;
  border: none; border-radius: var(--radius-full);
  font-size: .8rem; font-weight: 500; cursor: pointer;
  color: var(--text-mid); background: transparent;
  transition: all var(--transition);
}
.auth-role-tab.active {
  background: var(--white); color: var(--terracotta);
  box-shadow: var(--shadow-sm);
}

@media (max-width: 860px) {
  .auth-page { grid-template-columns: 1fr; }
  .auth-visual { display: none; }
  .auth-form-panel { justify-content: flex-start; padding: 7rem 6% 4rem; }
  .auth-form-inner { max-width: 100%; }
  .auth-row { grid-template-columns: 1fr; }
}
CSS;

require_once 'includes/header.php';
?>

<section class="auth-page">

  <!-- Panneau visuel -->
  <div class="auth-visual">
    <div class="auth-visual-bg"></div>
    <div class="auth-visual-kente"></div>
    <a href="<?= url('index.php') ?>" class="auth-visual-logo">XwéDò</a>
    <div class="auth-visual-content">
      <p class="auth-visual-quote">
        Rejoignez la<br><em>communauté</em><br>culturelle
      </p>
      <p class="auth-visual-sub">Plus de 12 000 passionnés de culture béninoise</p>
    </div>
    <svg class="auth-visual-arcs" width="320" height="320" viewBox="0 0 320 320" fill="none" aria-hidden="true">
      <circle cx="320" cy="320" r="280" stroke="rgba(196,98,45,.08)" stroke-width="1"/>
      <circle cx="320" cy="320" r="200" stroke="rgba(212,168,83,.1)"  stroke-width="1"/>
      <circle cx="320" cy="320" r="120" stroke="rgba(196,98,45,.06)"  stroke-width="1"/>
    </svg>
  </div>

  <!-- Formulaire -->
  <div class="auth-form-panel">
    <div class="auth-form-inner">

      <div class="auth-pretitle">
        <span class="auth-pretitle-line"></span>
        <span class="auth-pretitle-text">Inscription gratuite</span>
      </div>

      <h1 class="auth-title">Créez votre<br>compte <em>XwéDò</em></h1>
      <p class="auth-subtitle">Réservez vos billets, suivez vos festivals préférés et ne manquez plus rien.</p>

      <!-- Onglets rôle -->
      <div class="auth-role-tabs" id="role-tabs">
        <button type="button" class="auth-role-tab active" data-role="participant">Participant</button>
        <button type="button" class="auth-role-tab" data-role="organisateur">Organisateur</button>
      </div>
      <input type="hidden" name="role_choisi" id="role-choisi" value="participant">

      <?php if (!empty($erreurs)): ?>
        <ul class="auth-erreurs">
          <?php foreach ($erreurs as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="POST" action="<?= url('register.php') ?>" novalidate id="form-register">
        <?= csrfField() ?>
        <input type="hidden" name="role" id="input-role" value="participant">

        <!-- Nom & Prénom -->
        <div class="auth-row">
          <div class="auth-field">
            <label class="auth-field-label" for="prenom">Prénom</label>
            <div class="auth-field-wrap">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" id="prenom" name="prenom" class="auth-input" value="<?= e($donnees['prenom']) ?>" placeholder="Koffi" autocomplete="given-name" required>
            </div>
          </div>
          <div class="auth-field">
            <label class="auth-field-label" for="nom">Nom</label>
            <div class="auth-field-wrap">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" id="nom" name="nom" class="auth-input" value="<?= e($donnees['nom']) ?>" placeholder="Ahounou" autocomplete="family-name" required>
            </div>
          </div>
        </div>

        <!-- Email -->
        <div class="auth-field">
          <label class="auth-field-label" for="email">Adresse email</label>
          <div class="auth-field-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input type="email" id="email" name="email" class="auth-input" value="<?= e($donnees['email']) ?>" placeholder="vous@email.com" autocomplete="email" required>
          </div>
        </div>

        <!-- Ville -->
        <div class="auth-field">
          <label class="auth-field-label" for="ville">Votre ville</label>
          <div class="auth-field-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <select id="ville" name="ville" class="auth-input form-select">
              <option value="">Choisir une ville…</option>
              <?php
              $villes = ['Cotonou','Porto-Novo','Ouidah','Parakou','Natitingou','Abomey','Nikki','Savalou','Lokossa','Kandi','Djougou','Bohicon'];
              foreach ($villes as $v):
              ?>
                <option value="<?= e($v) ?>" <?= $donnees['ville'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Mot de passe -->
        <div class="auth-field">
          <label class="auth-field-label" for="mot_de_passe">Mot de passe</label>
          <div class="auth-field-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="mot_de_passe" name="mot_de_passe" class="auth-input" placeholder="Min. 8 car., 1 maj., 1 chiffre" autocomplete="new-password" required>
            <button type="button" class="auth-pwd-toggle" id="pwd-toggle-1" aria-label="Voir">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <div class="pwd-strength">
            <div class="pwd-strength-bar"><div class="pwd-strength-fill" id="pwd-fill"></div></div>
            <span class="pwd-strength-label" id="pwd-label"></span>
          </div>
        </div>

        <!-- Confirmer mot de passe -->
        <div class="auth-field">
          <label class="auth-field-label" for="confirmer_mdp">Confirmer le mot de passe</label>
          <div class="auth-field-wrap">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input type="password" id="confirmer_mdp" name="confirmer_mdp" class="auth-input" placeholder="Répétez le mot de passe" autocomplete="new-password" required>
            <button type="button" class="auth-pwd-toggle" id="pwd-toggle-2" aria-label="Voir">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <!-- CGU -->
        <div class="auth-cgu">
          <input type="checkbox" id="cgu" name="cgu" value="1" required>
          <label for="cgu">
            J'accepte les <a href="<?= url('cgu.php') ?>" target="_blank">Conditions Générales d'Utilisation</a>
            et la <a href="<?= url('confidentialite.php') ?>" target="_blank">Politique de confidentialité</a> de XwéDò.
          </label>
        </div>

        <button type="submit" class="auth-submit">
          <span>Créer mon compte</span>
        </button>
      </form>

      <div class="auth-sep"><span>Déjà un compte ?</span></div>
      <p class="auth-switch">
        <a href="<?= url('login.php') ?>">← Se connecter</a>
      </p>

    </div>
  </div>

</section>

<?php
$pageJS = <<<JS
// Toggle visibilité mots de passe
function makePwdToggle(toggleId, inputId) {
  const toggle = document.getElementById(toggleId);
  const input  = document.getElementById(inputId);
  toggle?.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.querySelector('svg').style.opacity = show ? '.5' : '1';
  });
}
makePwdToggle('pwd-toggle-1', 'mot_de_passe');
makePwdToggle('pwd-toggle-2', 'confirmer_mdp');

// Indicateur de force du mot de passe
const pwdInput = document.getElementById('mot_de_passe');
const pwdFill  = document.getElementById('pwd-fill');
const pwdLabel = document.getElementById('pwd-label');

pwdInput?.addEventListener('input', () => {
  const v = pwdInput.value;
  let score = 0;
  if (v.length >= 8) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    { w: '0%',   c: 'transparent', l: '' },
    { w: '25%',  c: '#C0392B',     l: 'Très faible' },
    { w: '50%',  c: '#E67E22',     l: 'Faible' },
    { w: '75%',  c: '#F1C40F',     l: 'Moyen' },
    { w: '100%', c: '#27AE60',     l: 'Fort ✓' },
  ];
  const lv = levels[score] || levels[0];
  pwdFill.style.width      = lv.w;
  pwdFill.style.background = lv.c;
  pwdLabel.textContent     = lv.l;
});

// Onglets rôle
document.querySelectorAll('.auth-role-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.auth-role-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById('input-role').value = tab.dataset.role;
  });
});
JS;
require_once 'includes/footer.php';
?>