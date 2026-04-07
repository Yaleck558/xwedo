<?php
// ============================================================
//  XwéDò – Politique de Confidentialité
//  Fichier : confidentialite.php
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$pageTitre = 'Politique de Confidentialité – XwéDò';
$pageDesc  = 'Découvrez comment XwéDò collecte, utilise et protège vos données personnelles.';

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
.legal-highlight.vert {
  background: rgba(39,174,96,.06);
  border-left-color: #27AE60;
}
/* Tableau données */
.data-table {
  width: 100%; border-collapse: collapse;
  margin: 1rem 0; font-size: .85rem;
}
.data-table th {
  background: var(--dusk); color: var(--cream);
  padding: .7rem 1rem; text-align: left;
  font-weight: 500; font-size: .78rem;
  letter-spacing: .06em;
}
.data-table td {
  padding: .65rem 1rem; color: var(--text-mid);
  border-bottom: 1px solid rgba(196,98,45,.06);
  font-weight: 300; vertical-align: top;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:nth-child(even) td { background: rgba(237,224,200,.2); }
@media (max-width: 768px) {
  .legal-body { grid-template-columns: 1fr; }
  .legal-toc { position: static; }
  .data-table { font-size: .78rem; }
  .data-table th, .data-table td { padding: .5rem .6rem; }
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
      <h1 class="legal-hero-title">Politique de <em>Confidentialité</em></h1>
      <p class="legal-hero-sub">Dernière mise à jour : 1er janvier 2026</p>
    </div>
  </div>

  <div class="legal-body">
    <aside class="legal-toc">
      <p class="legal-toc-title">Sommaire</p>
      <a href="#collecte">1. Données collectées</a>
      <a href="#utilisation">2. Utilisation</a>
      <a href="#conservation">3. Conservation</a>
      <a href="#partage">4. Partage</a>
      <a href="#securite">5. Sécurité</a>
      <a href="#droits">6. Vos droits</a>
      <a href="#cookies">7. Cookies</a>
      <a href="#contact">8. Nous contacter</a>
    </aside>

    <div class="legal-content">

      <div class="legal-highlight vert">
        🔒 <strong>Notre engagement :</strong> vos données personnelles ne sont jamais vendues, jamais partagées à des fins publicitaires. Elles servent uniquement au fonctionnement de XwéDò.
      </div>

      <h2 id="collecte">1. Données collectées</h2>
      <p>XwéDò collecte uniquement les données nécessaires au fonctionnement du service :</p>

      <table class="data-table">
        <thead>
          <tr>
            <th>Donnée</th>
            <th>Pourquoi on la collecte</th>
            <th>Obligatoire ?</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Nom, prénom</strong></td>
            <td>Identification sur la plateforme et sur les billets</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td><strong>Adresse email</strong></td>
            <td>Connexion, notifications, confirmation de réservation</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td><strong>Mot de passe</strong></td>
            <td>Authentification (stocké en hash bcrypt, jamais en clair)</td>
            <td>Oui</td>
          </tr>
          <tr>
            <td><strong>Ville</strong></td>
            <td>Suggestions de festivals proches de vous</td>
            <td>Non</td>
          </tr>
          <tr>
            <td><strong>Historique réservations</strong></td>
            <td>Accès à vos billets, suggestions personnalisées</td>
            <td>Automatique</td>
          </tr>
          <tr>
            <td><strong>Adresse IP</strong></td>
            <td>Sécurité, détection de fraude, logs de scan</td>
            <td>Automatique</td>
          </tr>
        </tbody>
      </table>

      <h2 id="utilisation">2. Utilisation des données</h2>
      <p>Vos données sont utilisées exclusivement pour :</p>
      <ul>
        <li>Créer et gérer votre compte sur XwéDò</li>
        <li>Traiter vos réservations et générer vos billets numériques</li>
        <li>Vous envoyer des notifications liées à vos réservations (rappels, confirmations)</li>
        <li>Vous suggérer des festivals susceptibles de vous intéresser</li>
        <li>Assurer la sécurité de la plateforme et prévenir la fraude</li>
        <li>Améliorer nos services grâce à des statistiques anonymisées</li>
      </ul>

      <h2 id="conservation">3. Durée de conservation</h2>
      <ul>
        <li><strong>Données de compte :</strong> conservées tant que votre compte est actif.</li>
        <li><strong>Réservations et billets :</strong> conservés 3 ans après la date du festival pour des raisons comptables.</li>
        <li><strong>Logs de connexion :</strong> conservés 6 mois pour des raisons de sécurité.</li>
        <li>Après suppression de votre compte, vos données sont anonymisées sous 30 jours.</li>
      </ul>

      <h2 id="partage">4. Partage des données</h2>
      <p>XwéDò ne vend <strong>jamais</strong> vos données personnelles. Les seuls partages possibles sont :</p>
      <ul>
        <li><strong>Avec l'organisateur du festival :</strong> votre nom et prénom apparaissent lors du scan de votre billet à l'entrée.</li>
        <li><strong>Obligations légales :</strong> si la loi béninoise l'exige, XwéDò peut être contraint de communiquer des données aux autorités compétentes.</li>
      </ul>
      <div class="legal-highlight">
        Votre email ne sera jamais communiqué à un organisateur de festival sans votre consentement explicite.
      </div>

      <h2 id="securite">5. Sécurité des données</h2>
      <p>XwéDò met en œuvre des mesures techniques et organisationnelles pour protéger vos données :</p>
      <ul>
        <li><strong>Mots de passe :</strong> hachés avec bcrypt (coût 12) — jamais stockés en clair.</li>
        <li><strong>Connexions :</strong> sessions sécurisées avec régénération d'ID, protection CSRF.</li>
        <li><strong>Base de données :</strong> requêtes préparées PDO contre les injections SQL.</li>
        <li><strong>Billets :</strong> codes aléatoires via <code>random_bytes()</code>, impossibles à deviner.</li>
        <li><strong>Accès fichiers sensibles :</strong> bloqués par configuration Apache (.htaccess).</li>
      </ul>

      <h2 id="droits">6. Vos droits</h2>
      <p>Conformément aux lois en vigueur au Bénin, vous disposez des droits suivants sur vos données :</p>
      <ul>
        <li><strong>Droit d'accès :</strong> obtenir une copie de toutes vos données.</li>
        <li><strong>Droit de rectification :</strong> corriger des données inexactes depuis votre profil.</li>
        <li><strong>Droit à l'effacement :</strong> demander la suppression de votre compte et de vos données.</li>
        <li><strong>Droit d'opposition :</strong> vous opposer aux notifications de suggestions.</li>
        <li><strong>Droit à la portabilité :</strong> recevoir vos données dans un format lisible.</li>
      </ul>
      <p>Pour exercer ces droits, contactez-nous via <a href="<?= url('contact.php') ?>" style="color:var(--terracotta)">notre formulaire de contact</a>. Nous répondons sous 30 jours.</p>

      <h2 id="cookies">7. Cookies</h2>
      <p>XwéDò utilise uniquement les cookies strictement nécessaires au fonctionnement du service :</p>
      <ul>
        <li><strong>Cookie de session PHP :</strong> permet de vous maintenir connecté pendant votre visite. Supprimé à la fermeture du navigateur.</li>
        <li><strong>Cookie CSRF :</strong> protège la sécurité de vos formulaires. Aucun cookie publicitaire, aucun tracker tiers.</li>
      </ul>
      <div class="legal-highlight vert">
        XwéDò n'utilise pas Google Analytics, Facebook Pixel, ni aucun outil de tracking publicitaire.
      </div>

      <h2 id="contact">8. Nous contacter</h2>
      <p>Pour toute question relative à vos données personnelles ou pour exercer vos droits :</p>
      <ul>
        <li>Via notre <a href="<?= url('contact.php') ?>" style="color:var(--terracotta)">formulaire de contact</a></li>
        <li>Par email : <strong>contact@xwedo.bj</strong></li>
        <li>Adresse : Cotonou, République du Bénin</li>
      </ul>
      <p style="margin-top:2rem; font-size:.82rem; color:var(--text-soft);">
        Voir aussi nos <a href="<?= url('cgu.php') ?>" style="color:var(--terracotta)">Conditions Générales d'Utilisation</a>
      </p>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>