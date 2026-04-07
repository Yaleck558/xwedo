<?php
// ============================================================
//  XwéDò – Contact
//  Fichier : contact.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$erreurs = [];
$succes  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCSRF($_POST['csrf_token'] ?? '')) {
        $erreurs[] = 'Session expirée, veuillez réessayer.';
    } else {
        $nom     = postPropre('nom');
        $email   = postPropre('email');
        $sujet   = postPropre('sujet');
        $message = trim($_POST['message'] ?? '');

        if (empty($nom))     $erreurs[] = 'Votre nom est requis.';
        if (!emailValide($email)) $erreurs[] = 'Email invalide.';
        if (empty($sujet))   $erreurs[] = 'Le sujet est requis.';
        if (strlen($message) < 20) $erreurs[] = 'Le message doit contenir au moins 20 caractères.';

        if (empty($erreurs)) {
            // Sauvegarder le message en BDD (table notifications admin)
            $pdo = getDB();
            $adminId = $pdo->query("SELECT id FROM utilisateurs WHERE role='admin' LIMIT 1")->fetchColumn();
            if ($adminId) {
                $pdo->prepare("INSERT INTO notifications (utilisateur_id, titre, message, lien) VALUES (?,?,?,?)")
                    ->execute([
                        $adminId,
                        '📩 Contact : ' . $sujet . ' — ' . $nom,
                        'De : ' . $nom . ' <' . $email . '>' . "\n" . $message,
                        url('admin/dashboard.php')
                    ]);
            }
            $succes = true;
        }
    }
}

$pageTitre = 'Contact – XwéDò';
$pageDesc  = 'Contactez l\'équipe XwéDò pour toute question sur les festivals, la billetterie ou la plateforme.';

$pageCSS = <<<CSS
.contact-page { padding-top: 5.5rem; }

/* Hero */
.contact-hero {
  background: var(--dusk); padding: 4rem 5% 3rem;
  position: relative; overflow: hidden;
}
.contact-hero::before {
  content: ''; position: absolute; top:0; left:0; right:0; height:4px;
  background: repeating-linear-gradient(
    90deg, var(--terracotta) 0px, var(--terracotta) 18px,
    var(--ochre) 18px, var(--ochre) 32px,
    var(--sage) 32px, var(--sage) 46px
  );
}
.contact-hero-inner { max-width: 1280px; margin: 0 auto; }
.contact-hero-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 4vw, 3.5rem);
  font-weight: 300; color: var(--cream); line-height: 1.1;
}
.contact-hero-title em { font-style: italic; color: var(--ochre); }
.contact-hero-sub {
  font-size: .9rem; color: rgba(250,246,238,.5);
  font-weight: 300; margin-top: .6rem;
}

/* Corps */
.contact-body {
  max-width: 1280px; margin: 0 auto; padding: 4rem 5%;
  display: grid; grid-template-columns: 1fr 400px; gap: 4rem;
}

/* Formulaire */
.contact-form-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 2.5rem;
  box-shadow: var(--shadow-sm);
}
.contact-form-title {
  font-family: var(--font-serif);
  font-size: 1.6rem; font-weight: 300; color: var(--dusk);
  margin-bottom: 2rem; padding-bottom: 1rem;
  border-bottom: 1px solid rgba(196,98,45,.1);
}
.contact-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.contact-submit {
  width: 100%; padding: 1rem;
  background: var(--terracotta); color: var(--cream);
  font-size: .88rem; font-weight: 500;
  letter-spacing: .08em; text-transform: uppercase;
  border: none; border-radius: var(--radius-full);
  cursor: pointer; transition: background var(--transition);
  position: relative; overflow: hidden;
  margin-top: .5rem;
}
.contact-submit::before {
  content: ''; position: absolute; inset: 0;
  background: var(--soil); transform: scaleX(0);
  transform-origin: left; transition: transform .4s;
}
.contact-submit:hover::before { transform: scaleX(1); }
.contact-submit span { position: relative; z-index: 1; }

/* Succès */
.contact-succes {
  text-align: center; padding: 3rem 2rem;
}
.contact-succes-icon {
  width: 70px; height: 70px; border-radius: 50%;
  background: rgba(39,174,96,.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; margin: 0 auto 1.5rem;
}
.contact-succes h3 {
  font-family: var(--font-serif);
  font-size: 1.6rem; font-weight: 300; color: var(--dusk);
  margin-bottom: .8rem;
}
.contact-succes p { font-size: .9rem; color: var(--text-mid); line-height: 1.7; }

/* Sidebar infos */
.contact-sidebar {}
.contact-info-card {
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 2rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.contact-info-title {
  font-size: .72rem; font-weight: 600;
  letter-spacing: .15em; text-transform: uppercase;
  color: var(--text-soft); margin-bottom: 1.4rem;
  padding-bottom: .8rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.contact-info-item {
  display: flex; align-items: flex-start; gap: .9rem;
  padding: .8rem 0; border-bottom: 1px solid rgba(196,98,45,.06);
}
.contact-info-item:last-child { border-bottom: none; }
.contact-info-icon {
  width: 36px; height: 36px; border-radius: 50%;
  background: rgba(196,98,45,.08);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; color: var(--terracotta);
}
.contact-info-label { font-size: .72rem; color: var(--text-soft); display: block; margin-bottom: 2px; }
.contact-info-val   { font-size: .88rem; color: var(--text); font-weight: 400; }

/* FAQ */
.faq-item {
  border-bottom: 1px solid rgba(196,98,45,.08);
  padding: 1rem 0;
}
.faq-item:last-child { border-bottom: none; }
.faq-q {
  font-size: .88rem; font-weight: 500; color: var(--text);
  cursor: pointer; display: flex; justify-content: space-between;
  align-items: center; gap: .5rem;
  background: none; border: none; width: 100%; text-align: left;
  padding: 0;
}
.faq-q svg { flex-shrink: 0; transition: transform var(--transition); color: var(--terracotta); }
.faq-q.open svg { transform: rotate(180deg); }
.faq-a {
  font-size: .82rem; color: var(--text-mid);
  line-height: 1.7; font-weight: 300;
  padding-top: .6rem; display: none;
}
.faq-a.show { display: block; }

/* Erreurs */
.contact-erreurs {
  background: #FDECEA; border: 1px solid rgba(192,57,43,.2);
  border-radius: var(--radius-sm); padding: 1rem 1.2rem; margin-bottom: 1.5rem;
}
.contact-erreurs li { font-size: .85rem; color: #8B1A1A; list-style: none; padding: .15rem 0; }
.contact-erreurs li::before { content: '⚠ '; }

@media (max-width: 900px) {
  .contact-body { grid-template-columns: 1fr; }
  .contact-form-row { grid-template-columns: 1fr; }
}
CSS;

require_once 'includes/header.php';
?>

<div class="contact-page">

  <!-- Hero -->
  <div class="contact-hero">
    <div class="contact-hero-inner">
      <div class="section-pretitle">
        <span class="pretitle-line" style="background:var(--ochre);"></span>
        <span class="pretitle-text" style="color:var(--ochre);">On est là pour vous</span>
      </div>
      <h1 class="contact-hero-title">Contactez <em>XwéDò</em></h1>
      <p class="contact-hero-sub">Une question, une idée, un problème ? Notre équipe vous répond rapidement.</p>
    </div>
  </div>

  <!-- Corps -->
  <div class="contact-body">

    <!-- Formulaire -->
    <div>
      <div class="contact-form-card reveal">
        <?php if ($succes): ?>
          <div class="contact-succes">
            <div class="contact-succes-icon">✓</div>
            <h3>Message envoyé !</h3>
            <p>
              Merci de nous avoir contactés.<br>
              Notre équipe vous répondra dans les plus brefs délais à l'adresse indiquée.
            </p>
            <a href="<?= url('contact.php') ?>" class="btn-terra" style="display:inline-flex; margin-top:1.5rem;">
              <span>Envoyer un autre message</span>
            </a>
          </div>
        <?php else: ?>
          <h2 class="contact-form-title">Envoyez-nous un message</h2>

          <?php if (!empty($erreurs)): ?>
            <ul class="contact-erreurs">
              <?php foreach ($erreurs as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <form method="POST" action="<?= url('contact.php') ?>">
            <?= csrfField() ?>

            <div class="contact-form-row">
              <div class="form-group">
                <label class="form-label" for="nom">Nom complet *</label>
                <input type="text" id="nom" name="nom" class="form-input"
                       value="<?= e($_POST['nom'] ?? (estConnecte() ? $_SESSION['user_nom'] : '')) ?>"
                       placeholder="Koffi Mensah" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="<?= e($_POST['email'] ?? (estConnecte() ? $_SESSION['user_email'] : '')) ?>"
                       placeholder="vous@email.com" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="sujet">Sujet *</label>
              <select id="sujet" name="sujet" class="form-input form-select" required>
                <option value="">Choisir un sujet…</option>
                <option value="Question générale">Question générale</option>
                <option value="Problème avec une réservation">Problème avec une réservation</option>
                <option value="Devenir organisateur">Devenir organisateur</option>
                <option value="Signaler un problème technique">Signaler un problème technique</option>
                <option value="Partenariat">Partenariat</option>
                <option value="Autre">Autre</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="message">Message *</label>
              <textarea id="message" name="message" class="form-input form-textarea" rows="6"
                        placeholder="Décrivez votre demande en détail…" required><?= e($_POST['message'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="contact-submit">
              <span>Envoyer le message</span>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar -->
    <aside class="contact-sidebar">

      <!-- Infos contact -->
      <div class="contact-info-card reveal">
        <p class="contact-info-title">Nos coordonnées</p>
        <div class="contact-info-item">
          <div class="contact-info-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <div>
            <span class="contact-info-label">Email</span>
            <span class="contact-info-val">contact@xwedo.bj</span>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </div>
          <div>
            <span class="contact-info-label">Adresse</span>
            <span class="contact-info-val">Cotonou, République du Bénin</span>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <div>
            <span class="contact-info-label">Réponse sous</span>
            <span class="contact-info-val">24 à 48 heures</span>
          </div>
        </div>
      </div>

      <!-- FAQ rapide -->
      <div class="contact-info-card reveal">
        <p class="contact-info-title">Questions fréquentes</p>

        <?php
        $faqs = [
            ['q' => 'Comment devenir organisateur ?',
             'r' => 'Créez un compte, choisissez "Organisateur" lors de l\'inscription. Votre demande sera examinée par l\'équipe XwéDò sous 48h.'],
            ['q' => 'Comment récupérer mon billet ?',
             'r' => 'Après votre réservation, un QR code unique vous est attribué. Retrouvez-le dans Mon profil → Mes réservations.'],
            ['q' => 'Puis-je annuler ma réservation ?',
             'r' => 'Contactez-nous via ce formulaire en indiquant votre code billet. Les remboursements sont traités selon la politique de chaque festival.'],
            ['q' => 'La billetterie est-elle sécurisée ?',
             'r' => 'Oui ! Chaque billet a un QR code unique. Une fois scanné à l\'entrée, il ne peut plus être utilisé. Zéro fraude possible.'],
        ];
        foreach ($faqs as $i => $faq):
        ?>
          <div class="faq-item">
            <button class="faq-q" data-faq="<?= $i ?>">
              <?= e($faq['q']) ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-a" id="faq-<?= $i ?>"><?= e($faq['r']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

    </aside>
  </div>

</div>

<?php
$pageJS = <<<JS
document.querySelectorAll('.faq-q').forEach(btn => {
  btn.addEventListener('click', () => {
    const id  = btn.dataset.faq;
    const ans = document.getElementById('faq-' + id);
    const open = ans.classList.toggle('show');
    btn.classList.toggle('open', open);
  });
});
JS;
require_once 'includes/footer.php';
?>