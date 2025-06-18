<?php
/**
 * Gestionnaire de connexion pour le système de gestion de manège
 * login.php
 */

session_start();

require_once __DIR__ . '/../../Config/database.php';
require_once __DIR__ . '/../Model/Manager/UserManager.php';
require_once __DIR__ . '/../Model/Entity/User.php';

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
    $pdo = Database::getInstance()->getConnection();
    $userManager = new UserManager($pdo);
    $user = $userManager->findByEmail($email);

    if (!$user) {
        // Simuler la vérification du mot de passe pour éviter le timing attack
        password_verify($password, '$2y$10$dummy.hash.to.prevent.timing.attack');
        
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }

    // Vérifier le mot de passe
    if (!password_verify($password, $user->getMotDePasse())) {
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }

    // Créer la session utilisateur
    $_SESSION['user_id'] = $user->getId();
    $_SESSION['user_email'] = $user->getEmail();
    $_SESSION['user_nom'] = $user->getNom();
    $_SESSION['user_prenom'] = $user->getPrenom();
    $_SESSION['user_type'] = $user->getType();

    echo json_encode(['success' => true, 'message' => 'Connexion réussie !', 'redirect' => 'dashboard.html']);
    exit;

} catch (PDOException $e) {
    error_log("Erreur de base de données lors de la connexion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur lors de la connexion: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>