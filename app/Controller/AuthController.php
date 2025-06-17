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
            // Vérifier si c'est bien une requête POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['success' => false, 'message' => 'Méthode non autorisée'], 405);
                return;
            }

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

            // Rechercher l'utilisateur dans operateurs d'abord
            $stmt = $this->db->prepare("
                SELECT id, nom, prenom, email, mot_de_passe, role, actif 
                FROM operateurs 
                WHERE email = :email AND actif = 1
            ");
            $stmt->execute(['email' => $email]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            $userType = 'operateur';
            
            // Si pas trouvé dans operateurs, chercher dans Utilisateurs
            if (!$userData) {
                $stmt = $this->db->prepare("
                    SELECT id, nom, prenom, email, mot_de_passe, type, tel
                    FROM Utilisateurs 
                    WHERE email = :email
                ");
                $stmt->execute(['email' => $email]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                $userType = 'utilisateur';
            }

            if (!$userData || !password_verify($password, $userData['mot_de_passe'])) {
                $this->json(['success' => false, 'message' => 'Identifiants incorrects'], 401);
                return;
            }

            // Créer un objet User unifié
            $user = new User(
                $userData['id'],
                $userData['nom'],
                $userData['prenom'] ?? '',
                $userData['email'],
                $userData['mot_de_passe'],
                $userData['tel'] ?? null,
                $userData['role'] ?? $userData['type'] ?? 'utilisateur',
                null,
                null
            );

            // Créer la session
            $this->setUserSession($user);

            // Mettre à jour la dernière connexion pour les opérateurs
            if ($userType === 'operateur') {
                $updateStmt = $this->db->prepare("UPDATE operateurs SET derniere_connexion = NOW() WHERE id = :id");
                $updateStmt->execute(['id' => $user->getId()]);
            }

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
        
        // Créer la table si elle n'existe pas
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS remember_tokens (
                user_id INT PRIMARY KEY,
                token VARCHAR(255),
                expires_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES Utilisateurs(id) ON DELETE CASCADE
            )
        ");
        
        // Stocker en base
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
                return '/dashboard';
            case 'superviseur':
                return '/dashboard';
            case 'operateur':
                return '/dashboard';
            default:
                return '/dashboard';
        }
    }
}