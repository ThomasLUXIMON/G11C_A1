<?php
// AuthController.php centralise login, register, logout

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
            if (!empty($errors)) {                $this->json(['success' => false, 'message' => 'Données invalides', 'errors' => $errors], 400);
                return;
            }
            $userManager = new UserManager();
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
        $this->redirect('/G11C/G11C_A1/login');
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
            if (strlen($password) < 6) {
                $this->json(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères.'], 400);
                return;
            }
            if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $this->json(['success' => false, 'message' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.'], 400);
                return;
            }
            $userManager = new UserManager();
            if ($userManager->findByEmail($email)) {
                $this->json(['success' => false, 'message' => 'Cet email est déjà utilisé.'], 400);
                return;
            }
            $user = new User(null, $nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT), null, Type::agent);
            $userManager->insert($user);
            // Redirection si non-AJAX
            $isAjax = (
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            );
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Inscription réussie',
                    'redirect' => '/G11C/G11C_A1/login'
                ]);
            } else {
                $this->redirect('/G11C/G11C_A1/login');
            }
        } catch (Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

    // === Compte utilisateur ===
    public function showAccount(): void {
        $user = $this->getCurrentUser();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        $this->render('mon_compte', ['user' => $user]);
    }

    public function updateAccount(): void {
        if (!$this->isAuthenticated()) {
            $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            return;
        }
        $user = $this->getCurrentUser();
        $userManager = new UserManager();
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $tel = trim($_POST['tel'] ?? '');
        $motdepasse_actuel = $_POST['motdepasse_actuel'] ?? '';
        $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
        $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
        // Validation
        if (!$nom || !$prenom || !$email) {
            $this->json(['success' => false, 'message' => 'Champs obligatoires manquants.'], 400);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Email invalide.'], 400);
            return;
        }
        if ($nouveau_mdp || $confirmer_mdp) {
            if (!$motdepasse_actuel || !password_verify($motdepasse_actuel, $user->getMotDePasse())) {
                $this->json(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 400);
                return;
            }
            if ($nouveau_mdp !== $confirmer_mdp) {
                $this->json(['success' => false, 'message' => 'Les nouveaux mots de passe ne correspondent pas.'], 400);
                return;
            }
            $user->setMotDePasse(password_hash($nouveau_mdp, PASSWORD_DEFAULT));
        }
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setTel($tel);
        $userManager->updateUser($user);
        $this->json(['success' => true, 'message' => 'Compte mis à jour.']);
    }

    public function deleteAccount(): void {
        if (!$this->isAuthenticated()) {
            $this->json(['success' => false, 'message' => 'Non authentifié'], 401);
            return;
        }
        $user = $this->getCurrentUser();
        $userManager = new UserManager();
        $userManager->deleteById($user->getId());
        $this->destroyUserSession();
        $this->json(['success' => true, 'message' => 'Compte supprimé', 'redirect' => '/login']);
    }

    // --- Helpers ---
    
    private function createRememberToken(User $user): void {
        $token = bin2hex(random_bytes(32));
        $expires = new \DateTime('+' . REMEMBER_TOKEN_LIFETIME . ' seconds');
        $userManager = new UserManager();
        $userManager->setResetToken($user, $token, $expires);
        setcookie('remember_token', $token, $expires->getTimestamp(), '/', '', true, true);
    }

    private function clearRememberToken(User $user): void {
        $userManager = new UserManager();
        $userManager->clearResetToken($user);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }    private function getRedirectByRole(string $role): string {
        switch ($role) {
            case 'admin':
            case 'superviseur':
            case 'operateur':
                return '/G11C/G11C_A1/dashboard2';
            default:
                return '/G11C/G11C_A1/dashboard2';
        }
    }
}
