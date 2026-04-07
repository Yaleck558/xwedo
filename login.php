<?php
// ============================================================
//  XwéDò – Connexion
//  Fichier : login.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Déjà connecté → redirection
if (estConnecte()) {
    rediriger(urlApresConnexion(userRole()));
}

$erreurs = [];
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée, veuillez réessayer.';
    } else {
        $email = postPropre('email');
        $mdp   = $_POST['mot_de_passe'] ?? '';

        $resultat = connecterUtilisateur($email, $mdp);

        if (isset($resultat['succes'])) {
            setFlash('succes', 'Bienvenue, ' . explode(' ', $_SESSION['user_nom'])[0] . ' !');
            rediriger(urlApresConnexion($resultat['role']));
        } else {
            $erreurs[] = $resultat['erreur'];
        }
    }
}

$pageTitre = 'Connexion – XwéDò';
$pageDesc  = 'Connectez-vous à votre compte XwéDò pour accéder à vos billets et vos festivals.';

$pageCSS = <<<CSS
/* ── Layout auth ─────────────────────────────── */
.auth-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
}

/* Panneau gauche — visuel */
.auth-visual {
  position: relative;
  background: var(--dusk);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 4rem;
}
.auth-visual-bg {
  position: absolute; inset: 0;
  background-image: url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=900&q=80');
  background-size: cover;
  background-position: center;
  opacity: .28;
}
.auth-visual-kente {
  position: absolute; top: 0; left: 0; right: 0;
  height: 5px;
  background: repeating-linear-gradient(
    90deg,
    var(--terracotta) 0px, var(--terracotta) 18px,
    var(--ochre) 18px, var(--ochre) 32px,
    var(--sage) 32px, var(--sage) 46px,
    var(--terracotta-dark) 46px, var(--terracotta-dark) 60px
  );
}
.auth-visual-content { position: relative; z-index: 2; }
.auth-visual-logo {
  position: absolute; top: 2.5rem; left: 4rem;
  font-family: var(--font-display);
  font-size: 1.8rem; color: var(--ochre); z-index: 2;
}
.auth-visual-logo::after {
  content: '•'; color: var(--terracotta-light);
  margin-left: 3px; font-size: .7rem; vertical-align: super;
}
.auth-visual-quote {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 300; font-style: italic;
  color: var(--cream); line-height: 1.2;
  margin-bottom: 1.5rem;
}
.auth-visual-quote em { color: var(--ochre); font-style: normal; }
.auth-visual-sub {
  font-size: .85rem; font-weight: 300;
  color: rgba(250,246,238,.5);
  letter-spacing: .05em;
}
.auth-visual-arcs {
  position: absolute; bottom: -120px; right: -80px; z-index: 1;
}

/* Panneau droit — formulaire */
.auth-form-panel {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 5rem 8% 4rem;
  background: var(--cream);
  overflow-y: auto;
}
.auth-form-inner { max-width: 420px; width: 100%; }

.auth-pretitle {
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 2rem;
}
.auth-pretitle-line { width: 32px; height: 1px; background: var(--terracotta); }
.auth-pretitle-text {
  font-size: .72rem; letter-spacing: .2em;
  text-transform: uppercase; color: var(--terracotta); font-weight: 500;
}

.auth-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 3vw, 2.8rem);
  font-weight: 300; color: var(--dusk);
  line-height: 1.1; margin-bottom: .8rem;
}
.auth-title em { font-style: italic; color: var(--terracotta); }
.auth-subtitle {
  font-size: .9rem; color: var(--text-soft);
  font-weight: 300; margin-bottom: 2.8rem;
  line-height: 1.7;
}

/* Champs */
.auth-field { margin-bottom: 1.4rem; }
.auth-field-label {
  display: block; font-size: .75rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-mid); margin-bottom: .55rem;
}
.auth-field-wrap { position: relative; }
.auth-field-wrap svg {
  position: absolute; left: 1rem; top: 50%;
  transform: translateY(-50%);
  color: var(--text-soft); pointer-events: none;
}
.auth-input {
  width: 100%;
  padding: .9rem 1rem .9rem 2.8rem;
  background: var(--white);
  border: 1.5px solid rgba(196,98,45,.18);
  border-radius: var(--radius-sm);
  font-size: .92rem; color: var(--text);
  transition: border-color var(--transition), box-shadow var(--transition);
  outline: none;
}
.auth-input:focus {
  border-color: var(--terracotta);
  box-shadow: 0 0 0 3px rgba(196,98,45,.1);
}
.auth-input::placeholder { color: var(--text-soft); }
.auth-input.erreur { border-color: #C0392B; }

/* Toggle mot de passe */
.auth-pwd-toggle {
  position: absolute; right: 1rem; top: 50%;
  transform: translateY(-50%);
  color: var(--text-soft); cursor: pointer;
  transition: color var(--transition);
  background: none; border: none; padding: 0;
  display: flex; align-items: center;
}
.auth-pwd-toggle:hover { color: var(--terracotta); }

/* Options */
.auth-options {
  display: flex; align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem; flex-wrap: wrap; gap: .5rem;
}
.auth-check-label {
  display: flex; align-items: center; gap: .6rem;
  font-size: .82rem; color: var(--text-mid); cursor: pointer;
}
.auth-check-label input[type="checkbox"] {
  width: 16px; height: 16px;
  accent-color: var(--terracotta);
  cursor: pointer;
}
.auth-forgot {
  font-size: .82rem; color: var(--text-soft);
  transition: color var(--transition);
}
.auth-forgot:hover { color: var(--terracotta); }

/* Bouton submit */
.auth-submit {
  width: 100%; padding: 1rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .88rem; font-weight: 500;
  letter-spacing: .1em; text-transform: uppercase;
  border: none; border-radius: var(--radius-full);
  cursor: pointer;
  transition: background var(--transition), transform .2s;
  position: relative; overflow: hidden;
  margin-bottom: 1.8rem;
}
.auth-submit::before {
  content: ''; position: absolute; inset: 0;
  background: var(--soil); transform: scaleX(0);
  transform-origin: left; transition: transform .4s;
}
.auth-submit:hover::before { transform: scaleX(1); }
.auth-submit span { position: relative; z-index: 1; }

/* Séparateur */
.auth-sep {
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 1.8rem;
}
.auth-sep::before,
.auth-sep::after {
  content: ''; flex: 1; height: 1px;
  background: rgba(196,98,45,.15);
}
.auth-sep span {
  font-size: .75rem; color: var(--text-soft);
  white-space: nowrap; letter-spacing: .05em;
}

/* Lien inscription */
.auth-switch {
  text-align: center;
  font-size: .85rem; color: var(--text-mid);
}
.auth-switch a {
  color: var(--terracotta); font-weight: 500;
  transition: color var(--transition);
}
.auth-switch a:hover { color: var(--soil); }

/* Erreurs globales */
.auth-erreurs {
  background: #FDECEA;
  border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm);
  padding: 1rem 1.2rem;
  margin-bottom: 1.8rem;
}
.auth-erreurs li {
  font-size: .85rem; color: #8B1A1A;
  list-style: none; padding: .15rem 0;
}
.auth-erreurs li::before { content: '⚠ '; }

/* Responsive */
@media (max-width: 860px) {
  .auth-page { grid-template-columns: 1fr; }
  .auth-visual { display: none; }
  .auth-form-panel {
    justify-content: flex-start;
    padding: 7rem 6% 4rem;
  }
  .auth-form-inner { max-width: 100%; }
}
CSS;

require_once 'includes/header.php';
?>

<section class="auth-page">

  <!-- Panneau visuel gauche -->
  <div class="auth-visual">
    <div class="auth-visual-bg"></div>
    <div class="auth-visual-kente"></div>


    <div class="auth-visual-content">
      <p class="auth-visual-quote">
        La maison de tous<br>les <em>festivals</em><br>du Bénin
      </p>
      <p class="auth-visual-sub">Vodun Days · WeLovEya · FInAB · FITHEB · Gaani</p>
    </div>

    <!-- Arcs décoratifs -->
    <svg class="auth-visual-arcs" width="320" height="320" viewBox="0 0 320 320" fill="none" aria-hidden="true">
      <circle cx="320" cy="320" r="280" stroke="rgba(196,98,45,.08)" stroke-width="1"/>
      <circle cx="320" cy="320" r="200" stroke="rgba(212,168,83,.1)"  stroke-width="1"/>
      <circle cx="320" cy="320" r="120" stroke="rgba(196,98,45,.06)"  stroke-width="1"/>
    </svg>
  </div>

  <!-- Panneau formulaire droit -->
  <div class="auth-form-panel">
    <div class="auth-form-inner">

      <div class="auth-pretitle">
        <span class="auth-pretitle-line"></span>
        <span class="auth-pretitle-text">Espace membre</span>
      </div>

      <h1 class="auth-title">Bon retour<br>sur <em>XwéDò</em></h1>
      <p class="auth-subtitle">Connectez-vous pour accéder à vos billets et vos festivals.</p>

      <?php if (!empty($erreurs)): ?>
        <ul class="auth-erreurs">
          <?php foreach ($erreurs as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="POST" action="<?= url('login.php') ?>" novalidate>
        <?= csrfField() ?>

        <!-- Email -->
        <div class="auth-field">
          <label class="auth-field-label" for="email">Adresse email</label>
          <div class="auth-field-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <input
              type="email"
              id="email"
              name="email"
              class="auth-input <?= !empty($erreurs) ? 'erreur' : '' ?>"
              value="<?= e($email) ?>"
              placeholder="vous@email.com"
              autocomplete="email"
              required
            >
          </div>
        </div>

        <!-- Mot de passe -->
        <div class="auth-field">
          <label class="auth-field-label" for="mot_de_passe">Mot de passe</label>
          <div class="auth-field-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input
              type="password"
              id="mot_de_passe"
              name="mot_de_passe"
              class="auth-input <?= !empty($erreurs) ? 'erreur' : '' ?>"
              placeholder="Votre mot de passe"
              autocomplete="current-password"
              required
            >
            <button type="button" class="auth-pwd-toggle" id="pwd-toggle" aria-label="Voir le mot de passe">
              <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <!-- Options -->
        <div class="auth-options">
          <label class="auth-check-label">
            <input type="checkbox" name="se_souvenir" value="1">
            Se souvenir de moi
          </label>
          <a href="<?= url('mot-de-passe-oublie.php') ?>" class="auth-forgot">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="auth-submit">
          <span>Se connecter</span>
        </button>
      </form>

      <div class="auth-sep"><span>Pas encore de compte ?</span></div>

      <p class="auth-switch">
        Rejoignez XwéDò →
        <a href="<?= url('register.php') ?>">Créer un compte</a>
      </p>

    </div>
  </div>

</section>

<?php
$pageJS = <<<JS
// Toggle affichage mot de passe
const pwdInput  = document.getElementById('mot_de_passe');
const pwdToggle = document.getElementById('pwd-toggle');
const eyeIcon   = document.getElementById('eye-icon');

pwdToggle?.addEventListener('click', () => {
  const show = pwdInput.type === 'password';
  pwdInput.type = show ? 'text' : 'password';
  eyeIcon.innerHTML = show
    ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round"/>'
    : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
});
JS;
require_once 'includes/footer.php';
?>