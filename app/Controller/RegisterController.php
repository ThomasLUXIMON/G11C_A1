<?php
// app/Controller/RegisterController.php
require_once __DIR__ . '/../Model/Manager/UserManager.php';
require_once __DIR__ . '/../Model/Entity/User.php';

class RegisterController extends BaseController {
    public function showRegister(): void {
        $this->render('register');
    }

    public function register(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
                return;
            }
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if (!$nom || !$prenom || !$email || !$password || !$confirm) {
                $this->json(['success' => false, 'message' => 'Tous les champs sont requis.'], 400);
                return;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->json(['success' => false, 'message' => 'Adresse email invalide.'], 400);
                return;
            }
            if ($password !== $confirm) {
                $this->json(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.'], 400);
                return;
            }
            $userManager = new UserManager($this->db);
            if ($userManager->findByEmail($email)) {
                $this->json(['success' => false, 'message' => 'Cet email est déjà utilisé.'], 400);
                return;
            }
            $user = new User(null, $nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT), null, 'utilisateur');
            $userManager->insert($user);
            $this->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'redirect' => '/login.html'
            ]);
        } catch (Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }
}
