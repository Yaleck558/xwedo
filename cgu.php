<?php
// ============================================================
//  XwéDò – Conditions Générales d'Utilisation
//  Fichier : cgu.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pageTitre = 'Conditions Générales d\'Utilisation – XwéDò';
$pageDesc  = 'Consultez les conditions générales d\'utilisation de la plateforme XwéDò.';

$pageCSS = <<<CSS
.legal-page { padding-top: 5.5rem; }
.legal-hero {
  background: var(--dusk); padding: 3.5rem 5% 3rem;
  position: relative; overflow: hidden;
}
.legal-hero::before {
  content: ''; position: absolute; top:0; left:0; right:0; height:4px;
  background: repeating-linear-gradient(
    90deg, var(--terracotta) 0, var(--terracotta) 18px,
    var(--ochre) 18px, var(--ochre) 32px,
    var(--sage) 32px, var(--sage) 46px
  );
}
.legal-hero-inner { max-width: 960px; margin: 0 auto; }
.legal-hero-title {
  font-family: var(--font-serif);
  font-size: clamp(1.8rem, 4vw, 3rem);
  font-weight: 300; color: var(--cream); line-height: 1.1;
}
.legal-hero-title em { font-style: italic; color: var(--ochre); }
.legal-hero-sub {
  font-size: .85rem; color: rgba(250,246,238,.4);
  margin-top: .6rem; font-weight: 300;
}
.legal-body {
  max-width: 960px; margin: 0 auto;
  padding: 4rem 5%; display: grid;
  grid-template-columns: 200px 1fr; gap: 3rem;
  align-items: start;
}
.legal-toc {
  position: sticky; top: 6rem;
  background: var(--white);
  border: 1px solid rgba(196,98,45,.1);
  border-radius: var(--radius-lg);
  padding: 1.4rem; box-shadow: var(--shadow-sm);
}
.legal-toc-title {
  font-size: .7rem; font-weight: 600; letter-spacing: .15em;
  text-transform: uppercase; color: var(--text-soft);
  margin-bottom: .8rem; padding-bottom: .6rem;
  border-bottom: 1px solid rgba(196,98,45,.08);
}
.legal-toc a {
  display: block; font-size: .78rem; color: var(--text-mid);
  padding: .35rem 0; border-bottom: 1px solid rgba(196,98,45,.04);
  transition: color var(--transition);
}
.legal-toc a:last-child { border-bottom: none; }
.legal-toc a:hover { color: var(--terracotta); }
.legal-content h2 {
  font-family: var(--font-serif);
  font-size: 1.4rem; font-weight: 400; color: var(--dusk);
  margin: 2.5rem 0 .8rem; padding-top: 1rem;
  border-top: 1px solid rgba(196,98,45,.08);
  scroll-margin-top: 7rem;
}
.legal-content h2:first-child { border-top: none; margin-top: 0; }
.legal-content p {
  font-size: .9rem; color: var(--text-mid);
  line-height: 1.85; font-weight: 300; margin-bottom: .8rem;
}
.legal-content ul { margin: .5rem 0 .8rem 1.2rem; }
.legal-content ul li {
  font-size: .9rem; color: var(--text-mid);
  line-height: 1.7; font-weight: 300;
  padding: .2rem 0; list-style: disc;
}
.legal-content strong { color: var(--text); font-weight: 500; }
.legal-highlight {
  background: rgba(196,98,45,.06);
  border-left: 3px solid var(--terracotta);
  padding: .8rem 1.2rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
  margin: 1rem 0; font-size: .88rem; color: var(--text-mid);
  font-weight: 300; line-height: 1.7;
}
@media (max-width: 768px) {
  .legal-body { grid-template-columns: 1fr; }
  .legal-toc { position: static; }
}
CSS;

require_once 'includes/header.php';
?>

<div class="legal-page">
  <div class="legal-hero">
    <div class="legal-hero-inner">
      <div class="section-pretitle">
        <span class="pretitle-line" style="background:var(--ochre)"></span>
        <span class="pretitle-text" style="color:var(--ochre)">Documents légaux</span>
      </div>
      <h1 class="legal-hero-title">Conditions <em>Générales</em><br>d'Utilisation</h1>
      <p class="legal-hero-sub">Dernière mise à jour : 1er janvier 2026</p>
    </div>
  </div>

  <div class="legal-body">
    <aside class="legal-toc">
      <p class="legal-toc-title">Sommaire</p>
      <a href="#objet">1. Objet</a>
      <a href="#definitions">2. Définitions</a>
      <a href="#inscription">3. Inscription</a>
      <a href="#billets">4. Billets</a>
      <a href="#prix">5. Prix & commission</a>
      <a href="#qrcode">6. QR Code</a>
      <a href="#organisateurs">7. Organisateurs</a>
      <a href="#responsabilites">8. Responsabilités</a>
      <a href="#donnees">9. Données personnelles</a>
      <a href="#modifications">10. Modifications</a>
    </aside>

    <div class="legal-content">
      <h2 id="objet">1. Objet</h2>
      <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'utilisation de la plateforme <strong>XwéDò</strong>, maison numérique des festivals culturels du Bénin. En créant un compte ou en utilisant nos services, vous acceptez sans réserve les présentes CGU.</p>

      <h2 id="definitions">2. Définitions</h2>
      <ul>
        <li><strong>Plateforme :</strong> le site web XwéDò et ses services associés.</li>
        <li><strong>Participant :</strong> utilisateur inscrit qui réserve des billets.</li>
        <li><strong>Organisateur :</strong> utilisateur validé qui crée et gère des festivals.</li>
        <li><strong>Billet numérique :</strong> QR code unique attribué après réservation confirmée.</li>
        <li><strong>Commission :</strong> pourcentage prélevé par XwéDò sur chaque transaction.</li>
      </ul>

      <h2 id="inscription">3. Inscription et compte</h2>
      <p>L'inscription est gratuite et ouverte à toute personne physique majeure. L'utilisateur s'engage à fournir des informations exactes. Chaque compte est personnel et non cessible.</p>
      <div class="legal-highlight">
        XwéDò se réserve le droit de suspendre tout compte en cas de violation des CGU, de fraude ou de comportement abusif.
      </div>

      <h2 id="billets">4. Billets et réservations</h2>
      <p>Toute réservation confirmée est définitive. Le participant reçoit un QR code unique, valable une seule fois pour l'événement concerné.</p>
      <ul>
        <li>Un billet ne peut être utilisé qu'une seule fois : après scan, il est automatiquement invalidé.</li>
        <li>La revente d'un billet est strictement interdite et entraîne son annulation sans remboursement.</li>
        <li>Chaque participant est limité à <strong>10 billets maximum</strong> par réservation.</li>
        <li>Les billets ne sont pas remboursables sauf annulation du festival par l'organisateur.</li>
      </ul>

      <h2 id="prix">5. Prix et commission</h2>
      <p>Les prix des billets sont fixés par les organisateurs et affichés en <strong>Francs CFA (FCFA)</strong>. XwéDò prélève une commission de <strong>5%</strong> sur chaque transaction, clairement affichée lors de la réservation.</p>
      <div class="legal-highlight">
        Exemple : billet à 10 000 FCFA → commission XwéDò : 500 FCFA → organisateur reçoit : 9 500 FCFA.
      </div>

      <h2 id="qrcode">6. QR Code et contrôle d'accès</h2>
      <p>Chaque réservation génère un QR code unique au format <strong>XW-XXXXXX-XXXX</strong>, seul document valable pour accéder au festival.</p>
      <ul>
        <li>Le QR code est présenté sur smartphone ou imprimé à l'entrée du festival.</li>
        <li>Un QR code ne peut être scanné qu'une seule fois. Toute réutilisation est automatiquement rejetée.</li>
        <li>En cas de perte, contactez XwéDò avec votre email de réservation.</li>
      </ul>

      <h2 id="organisateurs">7. Organisateurs</h2>
      <p>L'organisateur s'engage à fournir des informations exactes sur ses festivals, à honorer tous les billets vendus et à informer XwéDò immédiatement en cas d'annulation. En cas d'annulation, l'intégralité des billets doit être remboursée.</p>

      <h2 id="responsabilites">8. Responsabilités</h2>
      <p>XwéDò agit en tant qu'intermédiaire technique. XwéDò n'est pas responsable du contenu des festivals, de leur qualité ou de leur annulation par l'organisateur.</p>

      <h2 id="donnees">9. Données personnelles</h2>
      <p>XwéDò collecte et traite vos données conformément à sa <a href="<?= url('confidentialite.php') ?>" style="color:var(--terracotta)">Politique de Confidentialité</a>. Vos données ne sont jamais vendues à des tiers.</p>

      <h2 id="modifications">10. Modifications des CGU</h2>
      <p>XwéDò se réserve le droit de modifier ces CGU à tout moment. Les utilisateurs seront informés par notification sur la plateforme. La poursuite de l'utilisation vaut acceptation des nouvelles conditions.</p>
      <p style="margin-top:2rem; font-size:.82rem; color:var(--text-soft);">
        Pour toute question : <a href="<?= url('contact.php') ?>" style="color:var(--terracotta)">formulaire de contact</a>
      </p>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>