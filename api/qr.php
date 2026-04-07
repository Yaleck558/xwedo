<?php
// ============================================================
//  XwéDò – Générateur QR Code
//  Fichier : api/qr.php
//  Usage : <img src="api/qr.php?code=XW-AB1234-CDEF&size=200">
// ============================================================

$code = trim($_GET['code'] ?? '');
$size = max(100, min(400, (int)($_GET['size'] ?? 220)));

if (empty($code)) {
    http_response_code(400);
    exit('Code manquant');
}

// ── Option 1 : lib phpqrcode locale (sans internet) ──────────
$libPath = __DIR__ . '/../phpqrcode/qrlib.php';
if (file_exists($libPath)) {
    require_once $libPath;
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=86400');
    ob_start();
    QRcode::png($code, false, QR_ECLEVEL_H, (int)($size / 21), 2);
    $png = ob_get_clean();
    echo $png;
    exit;
}

// ── Option 2 : Google Charts API (internet requis) ────────────
// Redirection directe — pas de vérification qui bloquerait
$url = 'https://chart.googleapis.com/chart'
     . '?chs=' . $size . 'x' . $size
     . '&cht=qr'
     . '&chl=' . rawurlencode($code)
     . '&choe=UTF-8'
     . '&chld=H|2';

header('Location: ' . $url, true, 302);
exit;