<?php
// ============================================================
//  XwéDò – Footer global
//  Fichier : includes/footer.php
// ============================================================
?>
</main><!-- /#main-content -->

<!-- ============================================================
     FOOTER
============================================================ -->
<footer id="footer">
<style>
/* ============================================================
   FOOTER
============================================================ */
#footer {
  background: var(--dusk);
  color: var(--cream);
  margin-top: 0;
}
/* Supprime l'espace résiduel entre le contenu et le footer */
#main-content > *:last-child {
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}
.kente-footer {
  height: 6px;
  background: repeating-linear-gradient(
    90deg,
    var(--terracotta)        0px,  var(--terracotta)        18px,
    var(--ochre)             18px, var(--ochre)             32px,
    var(--sage)              32px, var(--sage)              46px,
    var(--terracotta-dark)   46px, var(--terracotta-dark)   60px,
    var(--ochre-light)       60px, var(--ochre-light)       72px
  );
}
.footer-inner {
  max-width: 1280px; margin: 0 auto;
  padding: 4rem 5% 3rem;
}
.footer-grid {
  display: grid;
  grid-template-columns: 1.8fr 1fr 1fr 1fr;
  gap: 3rem;
}
.f-brand-logo {
  font-family: var(--font-display);
  font-size: 1.8rem; color: var(--ochre);
  display: inline-block; margin-bottom: 1.2rem;
}
.f-brand-logo::after {
  content: '•'; color: var(--terracotta-light);
  margin-left: 3px; font-size: .7rem; vertical-align: super;
}
.f-brand-text {
  font-size: .85rem; font-weight: 300;
  color: rgba(250,246,238,.55); line-height: 1.8;
  margin-bottom: 1.8rem; max-width: 280px;
}
.f-socials { display: flex; gap: .7rem; }
.f-social-btn {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1px solid rgba(250,246,238,.15);
  display: flex; align-items: center; justify-content: center;
  color: rgba(250,246,238,.5);
  transition: all var(--transition);
}
.f-social-btn:hover {
  border-color: var(--ochre); color: var(--ochre);
  background: rgba(212,168,83,.08);
}
.f-col-title {
  display: block; font-size: .7rem; font-weight: 500;
  letter-spacing: .2em; text-transform: uppercase;
  color: var(--ochre); margin-bottom: 1.4rem;
}
.f-links { display: flex; flex-direction: column; gap: .65rem; }
.f-links a {
  font-size: .85rem; font-weight: 300;
  color: rgba(250,246,238,.5); transition: color var(--transition);
}
.f-links a:hover { color: var(--cream); }
.footer-bottom {
  max-width: 1280px; margin: 0 auto;
  padding: 1.5rem 5%;
  border-top: 1px solid rgba(250,246,238,.06);
  display: flex; align-items: center;
  justify-content: space-between;
  flex-wrap: wrap; gap: 1rem;
  font-size: .78rem; color: rgba(250,246,238,.3);
}
@media (max-width: 1024px) {
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 2.5rem; }
}
@media (max-width: 600px) {
  .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
  .footer-bottom { flex-direction: column; text-align: center; }
}
</style>

  <div class="kente-footer" aria-hidden="true"></div>

  <div class="footer-inner">
    <div class="footer-grid">

      <div class="footer-brand">
        <a href="<?= url('index.php') ?>" class="f-brand-logo">XwéDò</a>
        <p class="f-brand-text">
          "Xwé" signifie maison en langue Fon — la maison numérique de tous les festivals culturels du Bénin.
          Vodun Days, WeLovEya, FInAB, FITHEB, Gaani et bien plus.
        </p>
        <div class="f-socials">
          <a href="#" class="f-social-btn" aria-label="Twitter/X">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.735-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <a href="#" class="f-social-btn" aria-label="Instagram">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          <a href="#" class="f-social-btn" aria-label="Facebook">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <a href="#" class="f-social-btn" aria-label="YouTube">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon fill="#1C1410" points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/></svg>
          </a>
        </div>
      </div>

      <div>
        <span class="f-col-title">Festivals</span>
        <ul class="f-links">
          <li><a href="<?= url('festivals.php?q=vodun') ?>">Vodun Days</a></li>
          <li><a href="<?= url('festivals.php?q=weloveya') ?>">WeLovEya Festival</a></li>
          <li><a href="<?= url('festivals.php?q=finab') ?>">FInAB 2026</a></li>
          <li><a href="<?= url('festivals.php?q=fitheb') ?>">FITHEB</a></li>
          <li><a href="<?= url('festivals.php?q=gaani') ?>">Gaani de Nikki</a></li>
          <li><a href="<?= url('festivals.php?q=masques') ?>">Festival des Masques</a></li>
          <li><a href="<?= url('festivals.php') ?>">Voir tous →</a></li>
        </ul>
      </div>

      <div>
        <span class="f-col-title">Plateforme</span>
        <ul class="f-links">
          <li><a href="<?= url('register.php?role=organisateur') ?>">Pour organisateurs</a></li>
          <li><a href="<?= url('festivals.php') ?>">Billetterie en ligne</a></li>
          <li><a href="<?= url('festivals.php?tri=populaire') ?>">Festivals populaires</a></li>
          <li><a href="<?= url('festivals.php?tri=date') ?>">Calendrier culturel</a></li>
          <?php if (estConnecte()): ?>
          <li><a href="<?= url('profil.php?tab=reservations') ?>">Mes billets</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div>
        <span class="f-col-title">À propos</span>
        <ul class="f-links">
          <li><a href="<?= url('a-propos.php') ?>">À propos</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact</a></li>
          <li><a href="<?= url('cgu.php') ?>">CGU</a></li>
          <li><a href="<?= url('confidentialite.php') ?>">Confidentialité</a></li>
        </ul>
      </div>

    </div>
  </div>

  <div class="footer-bottom">
    <span>© <?= date('Y') ?> XwéDò — Maison des Festivals Culturels du Bénin</span>
    <span>Fait avec ❤️ au 🇧🇯 Bénin</span>
  </div>

</footer>

<!-- ============================================================
     JAVASCRIPT GLOBAL
============================================================ -->
<script>
// ── Nav : effet solid au scroll ──────────────────────────────
window.addEventListener('scroll', () => {
  document.getElementById('nav').classList.toggle('solid', window.scrollY > 60);
}, { passive: true });

// ── Burger menu mobile ───────────────────────────────────────
const burger      = document.getElementById('nav-burger');
const mobileMenu  = document.getElementById('nav-mobile-menu');
const overlay     = document.getElementById('nav-overlay');

function toggleMenu(force) {
  const open = force !== undefined ? force : !mobileMenu.classList.contains('open');
  burger.classList.toggle('open', open);
  mobileMenu.classList.toggle('open', open);
  overlay.classList.toggle('show', open);
  burger.setAttribute('aria-expanded', open);
  document.body.style.overflow = open ? 'hidden' : '';
}
burger?.addEventListener('click', () => toggleMenu());
overlay?.addEventListener('click', () => toggleMenu(false));

// ── Dropdown compte ──────────────────────────────────────────
const compteToggle   = document.getElementById('compte-toggle');
const compteDropdown = document.getElementById('compte-dropdown');

compteToggle?.addEventListener('click', (e) => {
  e.stopPropagation();
  const open = compteDropdown.classList.toggle('open');
  compteToggle.setAttribute('aria-expanded', open);
});
document.addEventListener('click', () => {
  compteDropdown?.classList.remove('open');
  compteToggle?.setAttribute('aria-expanded', 'false');
});
compteDropdown?.addEventListener('click', e => e.stopPropagation());

// ── Notifications temps réel (polling 30s) ───────────────────
<?php if (estConnecte()): ?>
(function() {
  const badge    = document.querySelector('.nav-notif-badge');
  const notifBtn = document.querySelector('.nav-notif');
  const BASE_URL = '<?= url('api/notifications.php') ?>';

  // Créer le dropdown
  const dropdown = document.createElement('div');
  dropdown.id = 'notif-dropdown';
  dropdown.style.cssText = [
    'position:absolute',
    'top:calc(100% + .8rem)',
    'right:-10px',
    'background:#fff',
    'border:1px solid rgba(196,98,45,.15)',
    'border-radius:14px',
    'box-shadow:0 16px 48px rgba(0,0,0,.14)',
    'min-width:300px',
    'max-width:340px',
    'width:90vw',
    'opacity:0',
    'visibility:hidden',
    'transform:translateY(-8px)',
    'transition:opacity .25s,transform .25s,visibility .25s',
    'z-index:9999',
    'overflow:hidden',
    'font-family:inherit'
  ].join(';');

  if (notifBtn) {
    notifBtn.style.position = 'relative';
    notifBtn.appendChild(dropdown);
  }

  let dropdownOpen = false;

  function renderDropdown(data) {
    if (!data || !data.ok) return;

    // Badge
    if (badge) {
      if (data.nb > 0) {
        badge.textContent    = data.nb > 99 ? '99+' : data.nb;
        badge.style.display  = 'flex';
      } else {
        badge.style.display  = 'none';
      }
    }

    let html = `<div style="padding:.85rem 1.1rem;border-bottom:1px solid rgba(196,98,45,.08);
                     display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
      <strong style="font-size:.83rem;color:#1C1410;">Notifications</strong>
      ${data.nb > 0
        ? `<button onclick="window._xwdMarkAll()" style="font-size:.7rem;color:#C4622D;
               background:none;border:none;cursor:pointer;padding:0;">Tout lire</button>`
        : ''}
    </div>`;

    if (!data.notifications || data.notifications.length === 0) {
      html += `<div style="padding:1.8rem;text-align:center;color:#A8937C;font-size:.83rem;">
                 Aucune notification pour l'instant.
               </div>`;
    } else {
      data.notifications.forEach(n => {
        const lien = (n.lien && n.lien !== 'null' && n.lien !== '') ? n.lien : '<?= url('profil.php?tab=notifications') ?>';
        const bg   = n.lue ? '#fff' : 'rgba(196,98,45,.04)';
        const dot  = n.lue ? ''
          : `<span style="width:7px;height:7px;border-radius:50%;background:#C4622D;
                  flex-shrink:0;margin-top:5px;display:block;"></span>`;
        html += `<a href="${lien}"
                    onclick="window._xwdMarkOne(${n.id})"
                    style="display:flex;gap:.75rem;padding:.8rem 1.1rem;
                           border-bottom:1px solid rgba(196,98,45,.05);
                           background:${bg};text-decoration:none;cursor:pointer;
                           color:inherit;-webkit-tap-highlight-color:transparent;"
                    onmouseover="this.style.background='rgba(196,98,45,.06)'"
                    onmouseout="this.style.background='${bg}'">
                   ${dot}
                   <div style="flex:1;min-width:0;">
                     <p style="font-size:.82rem;font-weight:600;color:#1C1410;margin:0 0 .2rem;
                                overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${n.titre}</p>
                     <p style="font-size:.75rem;color:#6B5442;line-height:1.4;margin:0 0 .25rem;">${n.message}</p>
                     <p style="font-size:.67rem;color:#A8937C;margin:0;">${n.date} · ${n.heure}</p>
                   </div>
                 </a>`;
      });
    }

    html += `<div style="padding:.7rem 1.1rem;border-top:1px solid rgba(196,98,45,.08);text-align:center;">
               <a href="<?= url('profil.php?tab=notifications') ?>"
                  style="font-size:.77rem;color:#C4622D;font-weight:500;text-decoration:none;">
                 Voir toutes les notifications →
               </a>
             </div>`;

    dropdown.innerHTML = html;
  }

  // Fonctions globales pour onclick inline
  window._xwdMarkOne = function(id) {
    fetch(BASE_URL + '?action=lire&id=' + id).catch(() => {});
    // On laisse le lien naviguer normalement
  };
  window._xwdMarkAll = function() {
    fetch(BASE_URL + '?action=lire_tout')
      .then(() => chargerNotifs())
      .catch(() => {});
  };

  function ouvrirDropdown() {
    dropdownOpen = true;
    dropdown.style.opacity    = '1';
    dropdown.style.visibility = 'visible';
    dropdown.style.transform  = 'translateY(0)';
    chargerNotifs();
  }
  function fermerDropdown() {
    dropdownOpen = false;
    dropdown.style.opacity    = '0';
    dropdown.style.visibility = 'hidden';
    dropdown.style.transform  = 'translateY(-8px)';
  }

  // Clic sur la cloche — toggle
  notifBtn?.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    dropdownOpen ? fermerDropdown() : ouvrirDropdown();
  });

  // Clic en dehors → fermer
  document.addEventListener('click', function(e) {
    if (dropdownOpen && notifBtn && !notifBtn.contains(e.target)) {
      fermerDropdown();
    }
  });

  // Ne pas fermer si on clique dans le dropdown
  dropdown.addEventListener('click', function(e) {
    e.stopPropagation();
  });

  // Chargement initial + polling 30s
  chargerNotifs();
  setInterval(chargerNotifs, 30000);

  function chargerNotifs() {
    fetch(BASE_URL)
      .then(r => r.json())
      .then(data => renderDropdown(data))
      .catch(() => {});
  }
})();
<?php endif; ?>

// ── Scroll reveal ────────────────────────────────────────────
const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('up'); revealObs.unobserve(e.target); }
  });
}, { threshold: 0.08 });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── Fermer flash après 5s ────────────────────────────────────
document.querySelectorAll('.flash').forEach(el => {
  setTimeout(() => el.style.opacity === '' && el.remove(), 5000);
});

// ── Marquer le lien nav actif ────────────────────────────────
const currentPath = window.location.pathname;
const currentFile = currentPath.split('/').pop() || 'index.php';
const currentParams = window.location.search;

document.querySelectorAll('.nav-center a').forEach(a => {
  const href = a.getAttribute('href');
  if (!href) return;

  const hrefFile   = href.split('?')[0].split('/').pop();
  const hrefParams = href.includes('?') ? href.split('?')[1] : '';

  // Correspondance exacte fichier + paramètres
  if (hrefFile === currentFile) {
    if (!hrefParams) {
      // Lien sans paramètre → actif seulement si la page n'a pas de filtre catégorie/tri
      // (évite que "Festivals" soit actif sur "Vodun" ou "Musique")
      if (!currentParams.includes('categorie=') && !currentParams.includes('tri=')) {
        a.classList.add('active');
      }
    } else {
      // Lien avec paramètre → actif seulement si l'URL contient exactement ce paramètre
      const hrefParts = new URLSearchParams(hrefParams);
      const urlParts  = new URLSearchParams(currentParams);
      let match = true;
      hrefParts.forEach((val, key) => {
        if (urlParts.get(key) !== val) match = false;
      });
      if (match) a.classList.add('active');
    }
  }
});
</script>

<?php if (!empty($pageJS)) echo '<script>' . $pageJS . '</script>'; ?>

</body>
</html>