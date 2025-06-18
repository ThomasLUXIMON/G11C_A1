<?php 
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/View.php';
require_once __DIR__ . '/../app/Model/Manager/UserManager.php';
require_once __DIR__ . '/../app/Model/Entity/User.php';

abstract class BaseController {
    protected View $view;
    protected PDO $db;
    
    public function __construct() {
        $this->view = new View();
        $this->db = Database::getInstance()->getConnection();
    }

    protected function render(string $view, array $data = []): void {
        $this->view->render($view, $data);
    }

    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }

    protected function requireAuth(): ?User {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            if ($this->isApiRequest()) {
                $this->json(['error' => 'Unauthorized'], 401);
            } else {
                $this->redirect('/login');
            }
        }

        return $this->getCurrentUser();
    }

    protected function requireRole(string $requiredRole): void {
        $user = $this->requireAuth();
        if (!$user) {
            $this->redirect('/login');
            return;
        }
        
        $roleHierarchy = ['utilisateur' => 1, 'operateur' => 2, 'superviseur' => 3, 'admin' => 4];
        
        $userLevel = $roleHierarchy[$user->getType()] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 999;
        
        if ($userLevel < $requiredLevel) {
            if ($this->isApiRequest()) {
                $this->json(['error' => 'Forbidden'], 403);
            } else {
                http_response_code(403);
                $this->render('errors/403');
            }
        }
    }

    protected function getCurrentUser(): ?User {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $userManager = new UserManager($this->db);
        return $userManager->findById($_SESSION['user_id']);
    }

    protected function setUserSession(User $user): void {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_name'] = trim($user->getPrenom() . ' ' . $user->getNom());
        $_SESSION['user_role'] = $user->getType();
        $_SESSION['login_time'] = time();
    }

    protected function destroyUserSession(): void {
        session_destroy();
        session_start();
    }

    private function isApiRequest(): bool {
        return strpos($_SERVER['REQUEST_URI'], '/api/') === 0 ||
               (isset($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    protected function validateInput(array $rules, array $data): array {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "Le champ {$field} est requis";
                continue;
            }
            
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Le champ {$field} doit être un email valide";
            }
            
            if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($value) < $matches[1]) {
                $errors[$field] = "Le champ {$field} doit contenir au moins {$matches[1]} caractères";
            }
        }
        
        return $errors;
    }
}