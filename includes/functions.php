<?php
// ============================================================
//  XwéDò – Fonctions utilitaires globales
//  Fichier : includes/functions.php
// ============================================================

// ============================================================
// SÉCURITÉ & NETTOYAGE
// ============================================================

/** Échappe une chaîne pour affichage HTML sécurisé */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** Génère un token aléatoire sécurisé */
function genererToken(int $longueur = 64): string
{
    return bin2hex(random_bytes($longueur / 2));
}

/** Génère un code billet unique (format : XW-XXXXXX-XXXX) */
function genererCodeBillet(): string
{
    return 'XW-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6))
         . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
}

/** Vérifie un token CSRF */
function verifierCSRF(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Génère et stocke un token CSRF en session */
function csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = genererToken(64);
    }
    return $_SESSION['csrf_token'];
}

/** Retourne un champ hidden CSRF prêt à l'emploi */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf()) . '">';
}

// ============================================================
// SLUGS & URLS
// ============================================================

/** Génère un slug propre depuis un texte (supporte caractères africains) */
function slugifier(string $texte): string
{
    $texte = mb_strtolower(trim($texte), 'UTF-8');

    // Translittération manuelle (accents + caractères fon/yoruba courants)
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ý'=>'y','ÿ'=>'y',
        'ñ'=>'n','ç'=>'c','æ'=>'ae','œ'=>'oe',
        'ɔ'=>'o','ɛ'=>'e','ŋ'=>'n','ɖ'=>'d','ʼ'=>'',
    ];
    $texte = strtr($texte, $map);
    $texte = preg_replace('/[^a-z0-9\s-]/', '', $texte);
    $texte = preg_replace('/[\s-]+/', '-', $texte);
    return trim($texte, '-');
}

/** Slugifie et assure l'unicité en base */
function slugUnique(string $texte, string $table, string $colonne, ?int $exclureId = null): string
{
    $pdo  = getDB();
    $base = slugifier($texte);
    $slug = $base;
    $i    = 1;

    do {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$colonne` = ?";
        $params = [$slug];
        if ($exclureId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $exclureId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $existe = (int) $stmt->fetchColumn() > 0;
        if ($existe) {
            $slug = $base . '-' . $i++;
        }
    } while ($existe);

    return $slug;
}

/** Construit une URL absolue */
function url(string $chemin = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($chemin, '/');
}

/** Redirige vers une URL et arrête l'exécution */
function rediriger(string $chemin): void
{
    header('Location: ' . url($chemin));
    exit;
}

// ============================================================
// DATES & TEMPS
// ============================================================

/** Formate une date en français : "23 janvier 2026" */
function dateFormatFr(string $date): string
{
    $mois = [
        1=>'janvier',2=>'février',3=>'mars',4=>'avril',
        5=>'mai',6=>'juin',7=>'juillet',8=>'août',
        9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'
    ];
    $ts = strtotime($date);
    return date('j', $ts) . ' ' . $mois[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/** Formate une période de festival : "23 – 25 janv. 2026" */
function periodeFestival(string $debut, string $fin): string
{
    if ($debut === $fin) {
        return dateFormatFr($debut);
    }
    $tsD = strtotime($debut);
    $tsF = strtotime($fin);
    if (date('Y-m', $tsD) === date('Y-m', $tsF)) {
        return date('j', $tsD) . ' – ' . dateFormatFr($fin);
    }
    return dateFormatFr($debut) . ' – ' . dateFormatFr($fin);
}

/** Retourne "Dans X jours" / "En cours" / "Terminé" */
function statutDate(string $debut, string $fin): array
{
    $now  = time();
    $tsD  = strtotime($debut);
    $tsF  = strtotime($fin) + 86399; // fin de journée

    if ($now < $tsD) {
        $jours = (int) ceil(($tsD - $now) / 86400);
        return ['label' => "Dans $jours jour" . ($jours > 1 ? 's' : ''), 'classe' => 'a-venir'];
    }
    if ($now <= $tsF) {
        return ['label' => 'En cours', 'classe' => 'en-cours'];
    }
    return ['label' => 'Terminé', 'classe' => 'termine'];
}

// ============================================================
// IMAGES & UPLOADS
// ============================================================

/** Retourne l'URL d'une image ou une image par défaut */
function imgUrl(?string $chemin, string $defaut = 'default-festival.jpg'): string
{
    if (!empty($chemin) && file_exists(UPLOADS_PATH . '/' . $chemin)) {
        return UPLOADS_URL . '/' . e($chemin);
    }
    return url('public/img/' . $defaut);
}

/**
 * Gère l'upload d'une image.
 * Retourne le chemin relatif ou null en cas d'erreur.
 */
function uploadImage(array $fichier, string $sousRep = 'festivals'): ?string
{
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($fichier['size'] > UPLOAD_MAX_SIZE) {
        return null;
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fichier['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, UPLOAD_ALLOWED_TYPES, true)) {
        return null;
    }

    $ext     = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT, true)) {
        return null;
    }

    $dir = UPLOADS_PATH . '/' . $sousRep;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $nomFichier = uniqid('xw_', true) . '.' . $ext;
    $destination = $dir . '/' . $nomFichier;

    if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
        return null;
    }

    return $sousRep . '/' . $nomFichier;
}

// ============================================================
// PRIX & MONNAIE
// ============================================================

/** Formate un prix en FCFA : "5 000 FCFA" */
function formatPrix(float $montant): string
{
    if ($montant <= 0) {
        return 'Gratuit';
    }
    return number_format($montant, 0, ',', ' ') . ' FCFA';
}

// ============================================================
// COMMISSION XWÉDO
// ============================================================

/** Taux de commission de la plateforme (5%) */
function tauxCommission(): float
{
    static $taux = null;
    if ($taux === null) {
        try {
            $stmt = getDB()->query("SELECT valeur FROM configuration WHERE cle = 'commission_taux' LIMIT 1");
            $row  = $stmt->fetch();
            $taux = $row ? (float) $row['valeur'] : 5.0;
        } catch (Exception $e) {
            $taux = 5.0;
        }
    }
    return $taux;
}

/**
 * Calcule la commission et le montant organisateur.
 * Retourne ['brut', 'commission', 'net', 'taux']
 */
function calculerCommission(float $prixBrut): array
{
    $taux       = tauxCommission();
    $commission = round($prixBrut * $taux / 100);
    $net        = $prixBrut - $commission;
    return [
        'brut'       => $prixBrut,
        'commission' => $commission,
        'net'        => $net,
        'taux'       => $taux,
    ];
}

/**
 * Affiche un bloc récapitulatif de commission.
 * Usage : echo htmlCommission(10000)
 */
function htmlCommission(float $prixBrut): string
{
    $c = calculerCommission($prixBrut);
    return sprintf(
        '<div class="commission-recap">
            <div class="commission-row">
                <span>Prix billet</span>
                <strong>%s</strong>
            </div>
            <div class="commission-row commission-row-fee">
                <span>Commission XwéDò (%s%%)</span>
                <strong>- %s</strong>
            </div>
            <div class="commission-row commission-row-net">
                <span>Vous recevez</span>
                <strong>%s</strong>
            </div>
        </div>',
        formatPrix($c['brut']),
        $c['taux'],
        formatPrix($c['commission']),
        formatPrix($c['net'])
    );
}

// ============================================================
// PAGINATION
// ============================================================

/**
 * Calcule les données de pagination.
 * Retourne ['offset', 'total_pages', 'page_courante', 'total']
 */
function paginer(int $total, int $parPage, int $pageCourante): array
{
    $pageCourante = max(1, $pageCourante);
    $totalPages   = $total > 0 ? (int) ceil($total / $parPage) : 1;
    $pageCourante = min($pageCourante, $totalPages);
    $offset       = ($pageCourante - 1) * $parPage;

    return [
        'offset'        => $offset,
        'total_pages'   => $totalPages,
        'page_courante' => $pageCourante,
        'total'         => $total,
        'par_page'      => $parPage,
    ];
}

/** Génère le HTML de la pagination */
function htmlPagination(array $pag, string $urlBase): string
{
    if ($pag['total_pages'] <= 1) return '';

    $html = '<nav class="pagination" aria-label="Pagination">';
    $sep  = str_contains($urlBase, '?') ? '&' : '?';

    // Précédent
    if ($pag['page_courante'] > 1) {
        $html .= '<a href="' . e($urlBase . $sep . 'page=' . ($pag['page_courante'] - 1)) . '" class="pag-btn pag-prev">←</a>';
    }

    // Pages
    for ($i = 1; $i <= $pag['total_pages']; $i++) {
        if (
            $i === 1 || $i === $pag['total_pages']
            || abs($i - $pag['page_courante']) <= 2
        ) {
            $actif = $i === $pag['page_courante'] ? ' pag-actif' : '';
            $html .= '<a href="' . e($urlBase . $sep . 'page=' . $i) . '" class="pag-btn' . $actif . '">' . $i . '</a>';
        } elseif (abs($i - $pag['page_courante']) === 3) {
            $html .= '<span class="pag-dots">…</span>';
        }
    }

    // Suivant
    if ($pag['page_courante'] < $pag['total_pages']) {
        $html .= '<a href="' . e($urlBase . $sep . 'page=' . ($pag['page_courante'] + 1)) . '" class="pag-btn pag-next">→</a>';
    }

    $html .= '</nav>';
    return $html;
}

// ============================================================
// AFFICHAGE DES MESSAGES FLASH
// ============================================================

/** Affiche les messages flash HTML (succès, erreur, info) */
function afficherFlash(): string
{
    $html  = '';
    $types = ['succes' => 'flash-succes', 'erreur' => 'flash-erreur', 'info' => 'flash-info'];

    foreach ($types as $type => $classe) {
        foreach (getFlash($type) as $msg) {
            $html .= '<div class="flash ' . $classe . '" role="alert">'
                   . '<span>' . e($msg) . '</span>'
                   . '<button class="flash-close" onclick="this.parentElement.remove()">×</button>'
                   . '</div>';
        }
    }
    return $html;
}

// ============================================================
// DIVERS
// ============================================================

/** Tronque un texte proprement */
function tronquer(string $texte, int $longueur = 160): string
{
    if (mb_strlen($texte, 'UTF-8') <= $longueur) return $texte;
    return mb_substr($texte, 0, $longueur, 'UTF-8') . '…';
}

/** Retourne le nom complet d'un utilisateur */
function nomComplet(array $user): string
{
    return e(trim($user['prenom'] . ' ' . $user['nom']));
}

/** Valide un email */
function emailValide(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Valide un mot de passe (min 8 car., 1 maj., 1 chiffre) */
function mdpValide(string $mdp): bool
{
    return strlen($mdp) >= 8
        && preg_match('/[A-Z]/', $mdp)
        && preg_match('/[0-9]/', $mdp);
}

/** Nettoie les données POST */
function postPropre(string $cle, string $defaut = ''): string
{
    return trim(htmlspecialchars($_POST[$cle] ?? $defaut, ENT_QUOTES, 'UTF-8'));
}

/** Récupère un paramètre GET de façon sécurisée */
function getPropre(string $cle, mixed $defaut = null): mixed
{
    if (!isset($_GET[$cle])) return $defaut;
    if (is_int($defaut)) return (int) $_GET[$cle];
    return trim(htmlspecialchars($_GET[$cle], ENT_QUOTES, 'UTF-8'));
}