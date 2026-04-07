<?php
// ============================================================
//  XwéDò – Déconnexion
//  Fichier : logout.php
// ============================================================
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// Vérification CSRF optionnelle via GET token
if (!estConnecte()) {
    rediriger('index.php');
}

detruireSession();
session_start();
setFlash('info', 'Vous avez été déconnecté. À bientôt !');
rediriger('login.php');