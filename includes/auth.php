<?php
// ============================================================
//  XwéDò – Authentification
//  Fichier : includes/auth.php
// ============================================================

/**
 * Connecte un utilisateur.
 * Retourne ['succes' => true, 'role' => $role] ou ['erreur' => 'message']
 */
function connecterUtilisateur(string $email, string $mdp): array
{
    if (!emailValide($email)) {
        return ['erreur' => 'Adresse email invalide.'];
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1 LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
        return ['erreur' => 'Email ou mot de passe incorrect.'];
    }

    // Mise en session
    session_regenerate_id(true);
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_nom']    = $user['prenom'] . ' ' . $user['nom'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_avatar'] = $user['avatar'];
    $_SESSION['_created_at'] = time();

    // Rehash si nécessaire
    if (password_needs_rehash($user['mot_de_passe'], PASSWORD_BCRYPT, ['cost' => HASH_COST])) {
        $nouveau = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
            ->execute([$nouveau, $user['id']]);
    }

    return ['succes' => true, 'role' => $user['role']];
}

/**
 * Inscrit un nouvel utilisateur (rôle participant par défaut).
 * Retourne ['succes' => true, 'id' => X] ou ['erreur' => 'message']
 */
function inscrireUtilisateur(array $donnees): array
{
    $nom    = trim($donnees['nom']    ?? '');
    $prenom = trim($donnees['prenom'] ?? '');
    $email  = mb_strtolower(trim($donnees['email'] ?? ''));
    $mdp    = $donnees['mot_de_passe'] ?? '';
    $ville  = trim($donnees['ville'] ?? '');

    // Validations
    if (empty($nom) || empty($prenom)) {
        return ['erreur' => 'Nom et prénom sont requis.'];
    }
    if (!emailValide($email)) {
        return ['erreur' => 'Adresse email invalide.'];
    }
    if (!mdpValide($mdp)) {
        return ['erreur' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.'];
    }

    $pdo = getDB();

    // Vérifier unicité email
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    if ((int) $stmt->fetchColumn() > 0) {
        return ['erreur' => 'Cette adresse email est déjà utilisée.'];
    }

    $hash  = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    $token = genererToken(64);

    $stmt = $pdo->prepare('
        INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, ville, token_email)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$nom, $prenom, $email, $hash, $ville ?: null, $token]);
    $id = (int) $pdo->lastInsertId();

    return ['succes' => true, 'id' => $id];
}

/**
 * Retourne les données complètes de l'utilisateur connecté.
 */
function utilisateurCourant(): ?array
{
    if (!estConnecte()) return null;

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ? LIMIT 1');
    $stmt->execute([userId()]);
    return $stmt->fetch() ?: null;
}

/**
 * Retourne le chemin de redirection après connexion selon le rôle.
 * CORRECTION : retourne un chemin relatif (sans BASE_URL)
 * car rediriger() appelle déjà url() en interne.
 */
function urlApresConnexion(string $role): string
{
    return match ($role) {
        'admin'        => 'admin/dashboard.php',
        'organisateur' => 'organisateur/dashboard.php',
        default        => 'index.php',
    };
}

/**
 * Déconnecte l'utilisateur et redirige.
 */
function deconnecter(): void
{
    detruireSession();
    rediriger('login.php');
}

/**
 * Enregistre une demande pour devenir organisateur.
 */
function demanderOrganisateur(int $userId, string $organisation, string $message): bool
{
    $pdo = getDB();

    // Vérifier qu'il n'y a pas déjà une demande en attente
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM demandes_organisateur WHERE utilisateur_id = ? AND statut = "en_attente"');
    $stmt->execute([$userId]);
    if ((int) $stmt->fetchColumn() > 0) return false;

    $stmt = $pdo->prepare('
        INSERT INTO demandes_organisateur (utilisateur_id, organisation, message)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$userId, $organisation, $message]);
    return true;
}

/**
 * Change le mot de passe d'un utilisateur (vérifie l'ancien).
 */
function changerMotDePasse(int $userId, string $ancien, string $nouveau): array
{
    if (!mdpValide($nouveau)) {
        return ['erreur' => 'Le nouveau mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.'];
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($ancien, $user['mot_de_passe'])) {
        return ['erreur' => 'Mot de passe actuel incorrect.'];
    }

    $hash = password_hash($nouveau, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')
        ->execute([$hash, $userId]);

    return ['succes' => true];
}