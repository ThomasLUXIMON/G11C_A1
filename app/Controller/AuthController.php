<?php
// AuthController.php : centralise login, register, logout

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Model/Manager/UserManager.php';
require_once __DIR__ . '/../Model/Entity/User.php';

class AuthController extends BaseController {
    public function showLogin(): void {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            $this->redirect('/dashboard');
        }
        $this->render('login');
    }

    public function login(): void {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
                return;
            }
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            $errors = $this->validateInput([
                'email' => 'required|email',
                'password' => 'required|min:6'
            ], $_POST);
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => 'Données invalides', 'errors' => $errors], 400);
                return;
            }
            $userManager = new UserManager($this->db);
            $user = $userManager->findByEmail($email);
            if (!$user || !password_verify($password, $user->getMotDePasse())) {
                $this->json(['success' => false, 'message' => 'Identifiants incorrects'], 401);
                return;
            }
            $this->setUserSession($user);
            if ($remember) {
                $this->createRememberToken($user);
            }
            $redirect = $this->getRedirectByRole($user->getType());
            $this->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'redirect' => $redirect,
                'user' => [
                    'id' => $user->getId(),
                    'name' => trim($user->getPrenom() . ' ' . $user->getNom()),
                    'email' => $user->getEmail(),
                    'role' => $user->getType()
                ]
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors de la connexion: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    public function logout(): void {
        $user = $this->getCurrentUser();
        $this->destroyUserSession();
        if ($user) {
            $this->clearRememberToken($user);
        } else if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        $this->redirect('/login');
    }

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
            $userManager->insert($user);            $this->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'redirect' => '/G11C/G11C_A1/login'
            ]);
        } catch (Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    // --- Helpers ---
    private function createRememberToken(User $user): void {
        $token = bin2hex(random_bytes(32));
        $expires = new \DateTime('+' . REMEMBER_TOKEN_LIFETIME . ' seconds');
        $userManager = new UserManager($this->db);
        $userManager->setResetToken($user, $token, $expires);
        setcookie('remember_token', $token, $expires->getTimestamp(), '/', '', true, true);
    }

    private function clearRememberToken(User $user): void {
        $userManager = new UserManager($this->db);
        $userManager->clearResetToken($user);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }

    private function getRedirectByRole(string $role): string {
        switch ($role) {
            case 'admin':
            case 'superviseur':
            case 'operateur':
                return '/dashboard';
            default:
                return '/dashboard';
        }
    }
}
