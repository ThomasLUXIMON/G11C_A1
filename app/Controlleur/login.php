<?php
/**
 * Gestionnaire de connexion pour le système de gestion de manège
 * login.php
 */

session_start();
require_once '../../Config/database.php';

header('Content-Type: application/json');

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les données du formulaire
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validation des champs
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
        exit;
    }

    // Connexion à la base de données
    $db = database::getInstance()->getConnection();

    // Rechercher l'utilisateur par email
    // D'abord essayer dans la table operateurs
    $stmt = $db->prepare("
        SELECT id, nom, prenom, email, mot_de_passe, role, actif, derniere_connexion 
        FROM operateurs 
        WHERE email = :email AND actif = 1
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si pas trouvé dans operateurs, essayer dans Utilisateurs
    if (!$user) {
        $stmt = $db->prepare("
            SELECT id, nom, email, mot_de_passe, tel
            FROM Utilisateurs 
            WHERE email = :email
        ");
        $stmt->execute(['email' => $email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userData) {
            $user = [
                'id' => $userData['id'],
                'nom' => $userData['nom'],
                'prenom' => '', // Pas de prénom dans la table Utilisateurs
                'email' => $userData['email'],
                'mot_de_passe' => $userData['mot_de_passe'],
                'role' => 'utilisateur', // Rôle par défaut
                'actif' => 1,
                'tel' => $userData['tel']
            ];
        }
    }

    if (!$user) {
        // Simuler la vérification du mot de passe pour éviter le timing attack
        password_verify($password, '$2y$10$dummy.hash.to.prevent.timing.attack');
        
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }

    // Vérifier le mot de passe
    if (!password_verify($password, $user['mot_de_passe'])) {
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }

    // Mettre à jour la dernière connexion pour les opérateurs
    if (isset($user['role']) && $user['role'] !== 'utilisateur') {
        $updateStmt = $db->prepare("
            UPDATE operateurs 
            SET derniere_connexion = NOW() 
            WHERE id = :id
        ");
        $updateStmt->execute(['id' => $user['id']]);
    }

    // Créer la session utilisateur
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = trim($user['prenom'] . ' ' . $user['nom']);
    $_SESSION['user_role'] = $user['role'] ?? 'utilisateur';
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Gestion du "Se souvenir de moi"
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 jours
        
        // Stocker le token en base (optionnel, pour plus de sécurité)
        $stmt = $db->prepare("
            INSERT INTO remember_tokens (user_id, token, expires_at) 
            VALUES (:user_id, :token, FROM_UNIXTIME(:expires))
            ON DUPLICATE KEY UPDATE 
            token = VALUES(token), 
            expires_at = VALUES(expires_at)
        ");
        $stmt->execute([
            'user_id' => $user['id'],
            'token' => hash('sha256', $token),
            'expires' => $expires
        ]);
        
        // Créer le cookie
        setcookie('remember_token', $token, $expires, '/', '', true, true);
    }

    // Log de connexion réussie
    error_log("Connexion réussie pour: " . $email . " (ID: " . $user['id'] . ")");

    // Déterminer la page de redirection selon le rôle
    $redirect_url = 'dashboard.php';
    switch ($user['role']) {
        case 'admin':
            $redirect_url = 'admin/dashboard.php';
            break;
        case 'superviseur':
            $redirect_url = 'supervisor/dashboard.php';
            break;
        case 'operateur':
            $redirect_url = 'operator/dashboard.php';
            break;
        default:
            $redirect_url = 'dashboard.php';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'redirect' => $redirect_url,
        'user' => [
            'id' => $user['id'],
            'name' => $_SESSION['user_name'],
            'email' => $user['email'],
            'role' => $_SESSION['user_role']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur de base de données lors de la connexion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur lors de la connexion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>