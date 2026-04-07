<?php
// ============================================================
//  XwéDò – Scanner QR Code (Interface staff)
//  Fichier : scanner.php
//  Accessible aux organisateurs et admins uniquement
// ============================================================
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireConnecte();
if (!estOrganisateur() && !estAdmin()) {
    rediriger('index.php');
}

$pdo = getDB();

// Festivals de l'organisateur (ou tous si admin)
if (estAdmin()) {
    $festivals = $pdo->query("
        SELECT id, nom, date_debut, date_fin, ville
        FROM festivals
        WHERE statut = 'publie'
        ORDER BY date_debut DESC
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT id, nom, date_debut, date_fin, ville
        FROM festivals
        WHERE statut = 'publie' AND organisateur_id = ?
        ORDER BY date_debut DESC
    ");
    $stmt->execute([userId()]);
    $festivals = $stmt->fetchAll();
}

$pageTitre = 'Scanner QR Code – XwéDò';

ob_start();
?>
/* ── Scanner ─────────────────────────────────────────────── */
.scanner-page {
  min-height: 100vh;
  background: var(--dusk);
  padding: 5.5rem 5% 3rem;
  display: flex; flex-direction: column; align-items: center;
}

.scanner-header {
  text-align: center; margin-bottom: 2rem;
}
.scanner-logo {
  font-family: var(--font-display);
  font-size: 1.8rem; color: var(--ochre);
  margin-bottom: .5rem; display: block;
}
.scanner-titre {
  font-family: var(--font-serif);
  font-size: 1.6rem; font-weight: 300;
  color: var(--cream); margin-bottom: .3rem;
}
.scanner-sub {
  font-size: .82rem; color: rgba(250,246,238,.4);
}

/* Sélecteur festival */
.scanner-select-wrap {
  width: 100%; max-width: 480px; margin-bottom: 1.5rem;
}
.scanner-select {
  width: 100%; padding: .85rem 1.2rem;
  background: rgba(250,246,238,.08);
  border: 1px solid rgba(250,246,238,.15);
  border-radius: var(--radius-md);
  color: var(--cream); font-size: .9rem;
  font-family: var(--font-sans); outline: none;
  cursor: pointer;
  transition: border-color var(--transition);
}
.scanner-select:focus { border-color: var(--ochre); }
.scanner-select option { background: var(--dusk); color: var(--cream); }

/* Zone principale scanner */
.scanner-main {
  width: 100%; max-width: 480px;
  display: flex; flex-direction: column; gap: 1rem;
}

/* Caméra */
.scanner-camera-wrap {
  position: relative;
  background: rgba(250,246,238,.05);
  border: 2px solid rgba(250,246,238,.15);
  border-radius: var(--radius-lg);
  overflow: hidden;
  aspect-ratio: 1;
  display: flex; align-items: center; justify-content: center;
}
.scanner-camera-wrap.active { border-color: var(--ochre); }
#scanner-video {
  width: 100%; height: 100%; object-fit: cover;
  display: none;
}
#scanner-video.show { display: block; }
.scanner-placeholder {
  display: flex; flex-direction: column; align-items: center;
  gap: 1rem; color: rgba(250,246,238,.3);
  padding: 2rem; text-align: center;
}
.scanner-placeholder svg { opacity: .4; }
.scanner-placeholder p { font-size: .85rem; }

/* Viseur */
.scanner-viewfinder {
  position: absolute; inset: 0;
  display: none; pointer-events: none;
}
.scanner-viewfinder.show { display: block; }
.scanner-viewfinder::before,
.scanner-viewfinder::after {
  content: '';
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  border: 2px solid rgba(212,168,83,.6);
}
.scanner-viewfinder::before {
  width: 60%; height: 60%;
  border-radius: 8px;
}
.scanner-corner {
  position: absolute;
  width: 20px; height: 20px;
  border-color: var(--ochre);
  border-style: solid;
}
.scanner-corner.tl { top: 20%; left: 20%; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
.scanner-corner.tr { top: 20%; right: 20%; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
.scanner-corner.bl { bottom: 20%; left: 20%; border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }
.scanner-corner.br { bottom: 20%; right: 20%; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }
.scanner-line {
  position: absolute;
  top: 20%; left: 20%; right: 20%;
  height: 2px; background: var(--ochre);
  animation: scanLine 2s ease-in-out infinite;
  opacity: .7;
}
@keyframes scanLine {
  0%   { top: 20%; }
  50%  { top: 80%; }
  100% { top: 20%; }
}

/* Bouton caméra */
.scanner-btn-cam {
  width: 100%; padding: 1rem;
  background: var(--terracotta); color: var(--cream);
  border: none; border-radius: var(--radius-full);
  font-size: .9rem; font-weight: 500;
  letter-spacing: .06em; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: .7rem;
  transition: background var(--transition);
}
.scanner-btn-cam:hover { background: var(--soil); }
.scanner-btn-cam.active {
  background: rgba(192,57,43,.2);
  border: 1px solid rgba(192,57,43,.4);
  color: #FF6B6B;
}

/* Saisie manuelle */
.scanner-manual {
  display: flex; gap: .6rem;
}
.scanner-manual-input {
  flex: 1; padding: .8rem 1rem;
  background: rgba(250,246,238,.08);
  border: 1px solid rgba(250,246,238,.15);
  border-radius: var(--radius-md);
  color: var(--cream); font-size: .9rem;
  font-family: var(--font-sans); outline: none;
  letter-spacing: .08em; text-transform: uppercase;
  transition: border-color var(--transition);
}
.scanner-manual-input::placeholder { color: rgba(250,246,238,.25); text-transform: none; letter-spacing: 0; }
.scanner-manual-input:focus { border-color: var(--ochre); }
.scanner-manual-btn {
  padding: .8rem 1.2rem;
  background: var(--ochre); color: var(--dusk);
  border: none; border-radius: var(--radius-md);
  font-weight: 600; font-size: .85rem;
  cursor: pointer; transition: background var(--transition);
  white-space: nowrap;
}
.scanner-manual-btn:hover { background: var(--ochre-light); }

/* Résultat */
.scanner-result {
  border-radius: var(--radius-lg);
  padding: 1.8rem;
  display: none; animation: resultIn .4s cubic-bezier(.16,1,.3,1);
}
@keyframes resultIn {
  from { opacity: 0; transform: translateY(10px) scale(.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.scanner-result.show { display: block; }
.scanner-result.vert  {
  background: rgba(39,174,96,.12);
  border: 1.5px solid rgba(39,174,96,.3);
}
.scanner-result.rouge {
  background: rgba(192,57,43,.12);
  border: 1.5px solid rgba(192,57,43,.3);
}
.scanner-result.orange {
  background: rgba(230,126,34,.12);
  border: 1.5px solid rgba(230,126,34,.3);
}

.result-icon {
  font-size: 2.5rem; text-align: center;
  margin-bottom: .8rem; display: block;
  animation: iconPop .4s cubic-bezier(.16,1,.3,1) .1s both;
}
@keyframes iconPop {
  from { transform: scale(0); }
  to   { transform: scale(1); }
}
.result-titre {
  font-family: var(--font-serif);
  font-size: 1.4rem; font-weight: 300;
  text-align: center; margin-bottom: 1rem;
}
.vert  .result-titre { color: #5DDE8A; }
.rouge .result-titre { color: #FF6B6B; }
.orange .result-titre { color: #F5A623; }

.result-infos {
  background: rgba(250,246,238,.05);
  border-radius: var(--radius-md);
  padding: 1rem;
}
.result-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: .4rem 0;
  border-bottom: 1px solid rgba(250,246,238,.06);
  font-size: .82rem;
}
.result-row:last-child { border-bottom: none; }
.result-row-label { color: rgba(250,246,238,.4); }
.result-row-val   { color: var(--cream); font-weight: 500; text-align: right; }

.result-actions {
  display: flex; gap: .6rem; margin-top: 1rem;
}
.result-btn {
  flex: 1; padding: .7rem;
  border-radius: var(--radius-full); border: none;
  font-size: .82rem; font-weight: 500; cursor: pointer;
  transition: all var(--transition); text-align: center;
}
.result-btn-ok {
  background: rgba(39,174,96,.2); color: #5DDE8A;
  border: 1px solid rgba(39,174,96,.3);
}
.result-btn-ok:hover { background: rgba(39,174,96,.35); }
.result-btn-reset {
  background: rgba(250,246,238,.08); color: rgba(250,246,238,.6);
  border: 1px solid rgba(250,246,238,.12);
}
.result-btn-reset:hover { background: rgba(250,246,238,.15); }

/* Historique */
.scanner-history {
  width: 100%; max-width: 480px; margin-top: 1rem;
}
.scanner-history-title {
  font-size: .7rem; font-weight: 600;
  letter-spacing: .15em; text-transform: uppercase;
  color: rgba(250,246,238,.25); margin-bottom: .6rem;
}
.history-list { display: flex; flex-direction: column; gap: .4rem; }
.history-item {
  display: flex; align-items: center; gap: .8rem;
  padding: .6rem .9rem;
  background: rgba(250,246,238,.04);
  border-radius: var(--radius-sm);
  font-size: .78rem;
}
.history-dot {
  width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.history-dot.vert  { background: #27AE60; }
.history-dot.rouge { background: #C0392B; }
.history-info { flex: 1; min-width: 0; color: rgba(250,246,238,.6); }
.history-nom  { color: rgba(250,246,238,.85); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.history-time { color: rgba(250,246,238,.3); font-size: .7rem; margin-left: auto; flex-shrink: 0; }

@media (max-width: 500px) {
  .scanner-page { padding: 5rem 4% 2rem; }
}
<?php
$pageCSS = ob_get_clean();

require_once 'includes/header.php';
?>

<div class="scanner-page">

  <!-- En-tête -->
  <div class="scanner-header">
    <span class="scanner-logo">XwéDò</span>
    <h1 class="scanner-titre">Scanner de billets</h1>
    <p class="scanner-sub">Présentez le QR code du billet à la caméra</p>
  </div>

  <!-- Sélecteur festival -->
  <div class="scanner-select-wrap">
    <select class="scanner-select" id="festival-select">
      <option value="0">— Sélectionner un festival —</option>
      <?php foreach ($festivals as $f): ?>
        <option value="<?= $f['id'] ?>">
          <?= e($f['nom']) ?> · <?= dateFormatFr($f['date_debut']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Zone principale -->
  <div class="scanner-main">

    <!-- Caméra -->
    <div class="scanner-camera-wrap" id="camera-wrap">
      <video id="scanner-video" autoplay playsinline></video>
      <canvas id="scanner-canvas" style="display:none;"></canvas>

      <!-- Viseur -->
      <div class="scanner-viewfinder" id="viewfinder">
        <div class="scanner-corner tl"></div>
        <div class="scanner-corner tr"></div>
        <div class="scanner-corner bl"></div>
        <div class="scanner-corner br"></div>
        <div class="scanner-line"></div>
      </div>

      <!-- Placeholder -->
      <div class="scanner-placeholder" id="cam-placeholder">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round">
          <rect x="3" y="3" width="5" height="5" rx="1"/>
          <rect x="16" y="3" width="5" height="5" rx="1"/>
          <rect x="3" y="16" width="5" height="5" rx="1"/>
          <path d="M21 16h-3a2 2 0 0 0-2 2v3M16 21h3M21 21v-3"/>
          <path d="M10 3h1M10 7h1M7 10v1M3 10v1M10 14v1M14 10h1M14 7h1"/>
        </svg>
        <p>Appuyez sur "Activer la caméra"<br>pour scanner un QR code</p>
      </div>
    </div>

    <!-- Bouton caméra -->
    <button class="scanner-btn-cam" id="btn-camera">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
      Activer la caméra
    </button>

    <!-- Saisie manuelle -->
    <div class="scanner-manual">
      <input
        type="text"
        id="input-code"
        class="scanner-manual-input"
        placeholder="Saisir le code manuellement (XW-XXXXXX-XXXX)"
        maxlength="20"
      >
      <button class="scanner-manual-btn" id="btn-verifier">Vérifier</button>
    </div>

    <!-- Résultat -->
    <div class="scanner-result" id="scanner-result">
      <span class="result-icon" id="result-icon"></span>
      <p class="result-titre" id="result-titre"></p>
      <div class="result-infos" id="result-infos"></div>
      <div class="result-actions">
        <button class="result-btn result-btn-ok" id="btn-suivant">
          ✓ Suivant
        </button>
        <button class="result-btn result-btn-reset" id="btn-reset">
          Réinitialiser
        </button>
      </div>
    </div>

  </div>

  <!-- Historique des scans -->
  <div class="scanner-history">
    <p class="scanner-history-title">Scans de la session</p>
    <div class="history-list" id="history-list">
      <p style="color:rgba(250,246,238,.2); font-size:.78rem;">Aucun scan pour le moment</p>
    </div>
  </div>

</div>

<?php
$apiUrl  = url('api/verifier-qr.php');
ob_start();
?>
const festivalSelect = document.getElementById('festival-select');
const btnCamera      = document.getElementById('btn-camera');
const btnVerifier    = document.getElementById('btn-verifier');
const btnSuivant     = document.getElementById('btn-suivant');
const btnReset       = document.getElementById('btn-reset');
const inputCode      = document.getElementById('input-code');
const video          = document.getElementById('scanner-video');
const canvas         = document.getElementById('scanner-canvas');
const cameraWrap     = document.getElementById('camera-wrap');
const viewfinder     = document.getElementById('viewfinder');
const placeholder    = document.getElementById('cam-placeholder');
const resultEl       = document.getElementById('scanner-result');
const resultIcon     = document.getElementById('result-icon');
const resultTitre    = document.getElementById('result-titre');
const resultInfos    = document.getElementById('result-infos');
const historyList    = document.getElementById('history-list');

let stream      = null;
let scanning    = false;
let scanTimeout = null;
let sessionScans = [];

// ── Caméra ────────────────────────────────────────────────────
btnCamera.addEventListener('click', async () => {
  if (stream) {
    // Arrêter la caméra
    stream.getTracks().forEach(t => t.stop());
    stream   = null;
    scanning = false;
    video.classList.remove('show');
    video.srcObject = null;
    placeholder.style.display = '';
    viewfinder.classList.remove('show');
    cameraWrap.classList.remove('active');
    btnCamera.classList.remove('active');
    btnCamera.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg> Activer la caméra`;
    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment', width: 640, height: 640 }
    });
    video.srcObject = stream;
    video.classList.add('show');
    placeholder.style.display = 'none';
    viewfinder.classList.add('show');
    cameraWrap.classList.add('active');
    btnCamera.classList.add('active');
    btnCamera.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="1" y1="1" x2="23" y2="23"/><path d="M21 21H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3m3-3h6l2 3h4a2 2 0 0 1 2 2v9.34"/><circle cx="12" cy="13" r="4"/></svg> Arrêter la caméra`;
    scanning = true;
    scanFrame();
  } catch (err) {
    alert('Impossible d\'accéder à la caméra : ' + err.message);
  }
});

// ── Scan frame par frame via jsQR ─────────────────────────────
function scanFrame() {
  if (!scanning || !stream) return;

  if (video.readyState === video.HAVE_ENOUGH_DATA) {
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

    // Utiliser jsQR si disponible
    if (typeof jsQR !== 'undefined') {
      const code = jsQR(imageData.data, imageData.width, imageData.height);
      if (code && code.data) {
        verifierCode(code.data);
        scanning = false; // Pause après scan
        setTimeout(() => { if (stream) scanning = true; scanFrame(); }, 3000);
        return;
      }
    }
  }
  requestAnimationFrame(scanFrame);
}

// ── Vérification manuelle ─────────────────────────────────────
btnVerifier.addEventListener('click', () => {
  const code = inputCode.value.trim().toUpperCase();
  if (!code) { inputCode.focus(); return; }
  verifierCode(code);
});

inputCode.addEventListener('keydown', e => {
  if (e.key === 'Enter') btnVerifier.click();
});

// ── Appel API ─────────────────────────────────────────────────
async function verifierCode(code) {
  const festId = festivalSelect.value;

  btnVerifier.disabled = true;
  btnVerifier.textContent = '…';

  try {
    const formData = new FormData();
    formData.append('code', code);
    formData.append('festival_id', festId);
    formData.append('lieu', 'Entrée principale');

    const res  = await fetch('$apiUrl', { method: 'POST', body: formData });
    const data = await res.json();

    afficherResultat(data);
    ajouterHistorique(data);
  } catch (err) {
    afficherResultat({
      statut: 'erreur', couleur: 'rouge', icone: '!',
      titre: 'Erreur réseau', message: 'Vérifiez votre connexion internet.'
    });
  } finally {
    btnVerifier.disabled = false;
    btnVerifier.textContent = 'Vérifier';
  }
}

// ── Afficher le résultat ──────────────────────────────────────
function afficherResultat(data) {
  resultEl.className = 'scanner-result show ' + (data.couleur || 'rouge');

  const icons = { vert: '✓', rouge: '✗', orange: '⚠' };
  resultIcon.textContent  = data.icone || icons[data.couleur] || '?';
  resultTitre.textContent = data.titre || '';

  let html = '';
  if (data.participant) html += row('Participant', data.participant);
  if (data.festival)    html += row('Festival',    data.festival);
  if (data.type_billet) html += row('Type billet', data.type_billet);
  if (data.quantite)    html += row('Quantité',    data.quantite + ' billet(s)');
  if (data.prix)        html += row('Prix payé',   data.prix);
  if (data.scanne_a)    html += row('Scanné à',    data.scanne_a);
  if (data.date_scan)   html += row('Déjà scanné', data.date_scan);
  if (data.message)     html += `<p style="margin-top:.8rem; font-size:.8rem; color:rgba(250,246,238,.5); text-align:center;">${data.message}</p>`;

  resultInfos.innerHTML = html;

  // Vibration pour feedback haptique
  if (navigator.vibrate) {
    navigator.vibrate(data.statut === 'valide' ? [100] : [200, 100, 200]);
  }

  // Feedback sonore
  playBeep(data.statut === 'valide');
}

function row(label, val) {
  return `<div class="result-row">
    <span class="result-row-label">${label}</span>
    <span class="result-row-val">${val}</span>
  </div>`;
}

// ── Historique ────────────────────────────────────────────────
function ajouterHistorique(data) {
  sessionScans.unshift(data);
  if (sessionScans.length > 10) sessionScans.pop();

  historyList.innerHTML = sessionScans.map(s => `
    <div class="history-item">
      <div class="history-dot ${s.statut === 'valide' ? 'vert' : 'rouge'}"></div>
      <div class="history-info">
        <div class="history-nom">${s.participant || s.code || '–'}</div>
        <div>${s.festival || s.message || ''}</div>
      </div>
      <span class="history-time">${new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'})}</span>
    </div>
  `).join('');
}

// ── Boutons reset/suivant ─────────────────────────────────────
btnSuivant.addEventListener('click', () => {
  resultEl.classList.remove('show');
  inputCode.value = '';
  inputCode.focus();
  if (stream) { scanning = true; scanFrame(); }
});

btnReset.addEventListener('click', () => {
  resultEl.classList.remove('show');
  inputCode.value = '';
});

// ── Bip sonore ────────────────────────────────────────────────
function playBeep(success) {
  try {
    const ctx  = new (window.AudioContext || window.webkitAudioContext)();
    const osc  = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.value = success ? 880 : 220;
    osc.type = 'sine';
    gain.gain.setValueAtTime(.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(.001, ctx.currentTime + .3);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + .3);
  } catch(e) {}
}
<?php
$pageJS = ob_get_clean();
require_once 'includes/footer.php';
?>