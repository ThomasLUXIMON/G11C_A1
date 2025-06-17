<?php
class AuthController extends BaseController {
    
    public function showLogin(): void {
        // Si déjà connecté, rediriger
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            $this->redirect('/dashboard');
        }
        
        $this->render('auth/login');
    }
    
    public function login(): void {
        try {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);

            // Validation
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

            // Créer la session
            $this->setUserSession($user);

            // Gestion "Se souvenir de moi"
            if ($remember) {
                $this->createRememberToken($user);
            }

            // Déterminer la redirection selon le rôle
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
        $this->destroyUserSession();
        
        // Supprimer le cookie remember me
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        $this->redirect('/login');
    }
    
    private function createRememberToken(User $user): void {
        $token = bin2hex(random_bytes(32));
        $expires = time() + REMEMBER_TOKEN_LIFETIME;
        
        // Stocker en base (vous devrez créer cette table)
        $stmt = $this->db->prepare("
            INSERT INTO remember_tokens (user_id, token, expires_at) 
            VALUES (:user_id, :token, FROM_UNIXTIME(:expires))
            ON DUPLICATE KEY UPDATE 
            token = VALUES(token), 
            expires_at = VALUES(expires_at)
        ");
        $stmt->execute([
            'user_id' => $user->getId(),
            'token' => hash('sha256', $token),
            'expires' => $expires
        ]);
        
        setcookie('remember_token', $token, $expires, '/', '', true, true);
    }
    
    private function getRedirectByRole(string $role): string {
        switch ($role) {
            case 'admin':
                return '/admin/dashboard';
            case 'superviseur':
                return '/supervisor/dashboard';
            case 'operateur':
                return '/operator/dashboard';
            default:
                return '/dashboard';
        }
    }
}