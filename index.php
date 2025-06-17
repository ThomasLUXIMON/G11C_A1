<?php
/**
 * Structure MVC Complète pour le Système de Gestion de Manège
 * 
 * Structure des dossiers:
 * /
 * ├── index.php (point d'entrée)
 * ├── Config/
 * │   ├── database.php
 * │   ├── routes.php
 * │   └── app.php
 * ├── Core/
 * │   ├── Router.php
 * │   ├── BaseController.php
 * │   ├── BaseModel.php
 * │   └── View.php
 * ├── app/
 * │   ├── Controlleur/
 * │   │   ├── AuthControlleur.php
 * │   │   ├── DashboardControlleur.php
 * │   │   ├── ManageControlleur.php
 * │   │   ├── SessionControlleur.php
 * │   │   ├── SecurityControlleur.php
 * │   │   └── TivaControlleur.php
 * │   ├── Model/
 * │   │   ├──Entity/
 * │   │   │   ├── User.php
 * │   │   │   ├── Operateur.php
 * │   │   │   ├── Manege.php
 * │   │   │   ├──
 * │   │   └──Manager/
 *     |       ├──User
 * │   └── Views/
 * │       ├── login.html
 * │       ├── dashboard.html
 * └── public/
 *     ├── css/
 *     ├── js/
 *     └── assets/
 */

// ==========================================
// INDEX.PHP - Point d'entrée principal
// ==========================================

session_start();

// Configuration des chemins
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Autoloader
spl_autoload_register(function ($class) {
    $paths = [
        CORE_PATH . '/',
        APP_PATH . '/Controllers/',
        APP_PATH . '/Models/',
        CONFIG_PATH . '/'
    ];
    
    // Nettoyer le nom de la classe
    $class = str_replace(['App\\Controllers\\', 'App\\Models\\', 'Core\\'], '', $class);
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Charger la configuration
require_once CONFIG_PATH . '/app.php';
require_once CONFIG_PATH . '/database.php';

// Gestion des erreurs
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Error: $message in $file on line $line");
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #ff6b6b; color: white; padding: 10px; margin: 10px;'>";
        echo "<strong>Error:</strong> $message in <strong>$file</strong> on line <strong>$line</strong>";
        echo "</div>";
    }
});

try {
    $router = require CONFIG_PATH . '/routes.php';
    $router->dispatch();
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<h1>Erreur Application</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        include APP_PATH . '/Views/errors/500.php';
    }
}
?>

<?php
// ==========================================
// CONFIG/APP.PHP - Configuration application
// ==========================================

// Configuration générale
define('APP_NAME', 'Système de Gestion de Manège');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', true); // false en production

// Configuration sécurité
define('SESSION_LIFETIME', 3600 * 8); // 8 heures
define('REMEMBER_TOKEN_LIFETIME', 30 * 24 * 3600); // 30 jours

// Configuration TIVA C
define('SERIAL_PORT', '/dev/ttyACM0'); // Linux
define('BAUD_RATE', 115200);
define('DATA_FILE', ROOT_PATH . '/data/seat_data.json');
define('LOG_FILE', ROOT_PATH . '/logs/seat_log.txt');

// Configuration API
define('API_KEY', 'your-api-key-here');

// Configuration système manège
define('SEUIL_DETECTION', 25.0);
define('TEMPS_ALERTE_OCCUPATION', 30);
define('INTERVAL_VERIFICATION', 5);

// Timezone
date_default_timezone_set('Europe/Paris');

// Gestion des erreurs selon l'environnement
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>

<?php
// ==========================================
// CONFIG/ROUTES.PHP - Configuration des routes
// ==========================================

require_once CORE_PATH . '/Router.php';

$router = new Router();

// ===== Routes d'authentification =====
$router->get('/', 'AuthController', 'showLogin');
$router->get('/login', 'AuthController', 'showLogin');
$router->post('/login', 'AuthController', 'login');
$router->get('/logout', 'AuthController', 'logout');
$router->get('/register', 'AuthController', 'showRegister');
$router->post('/register', 'AuthController', 'register');

// ===== Routes Dashboard =====
$router->get('/dashboard', 'DashboardController', 'index');
$router->get('/api/stats', 'DashboardController', 'getStats');
$router->get('/api/alerts-count', 'DashboardController', 'getAlertsCount');

// ===== Routes Manèges =====
$router->get('/maneges', 'ManageController', 'index');
$router->get('/maneges/create', 'ManageController', 'create');
$router->post('/maneges', 'ManageController', 'store');
$router->get('/maneges/{id}', 'ManageController', 'show');
$router->get('/maneges/{id}/edit', 'ManageController', 'edit');
$router->put('/maneges/{id}', 'ManageController', 'update');
$router->delete('/maneges/{id}', 'ManageController', 'delete');

// ===== Routes Sessions =====
$router->get('/sessions', 'SessionController', 'index');
$router->get('/sessions/create', 'SessionController', 'create');
$router->post('/sessions', 'SessionController', 'store');
$router->get('/sessions/{id}', 'SessionController', 'show');
$router->post('/sessions/{id}/start', 'SessionController', 'start');
$router->post('/sessions/{id}/stop', 'SessionController', 'stop');
$router->get('/sessions/{id}/monitoring', 'SessionController', 'monitoring');

// ===== Routes Sécurité =====
$router->get('/security', 'SecurityController', 'index');
$router->get('/security/alerts', 'SecurityController', 'alerts');
$router->post('/security/alerts/{id}/acknowledge', 'SecurityController', 'acknowledgeAlert');
$router->get('/security/controls/{sessionId}', 'SecurityController', 'controls');
$router->post('/security/validate/{sessionId}', 'SecurityController', 'validateSecurity');

// ===== Routes TIVA/API =====
$router->get('/api/tiva/status', 'TivaController', 'getStatus');
$router->get('/api/tiva/realtime', 'TivaController', 'getRealTimeData');
$router->post('/api/tiva/calibrate', 'TivaController', 'calibrate');
$router->post('/api/tiva/command', 'TivaController', 'sendCommand');
$router->post('/api/tiva/reset', 'TivaController', 'reset');

// ===== Routes Admin =====
$router->get('/admin/dashboard', 'AdminController', 'dashboard');
$router->get('/admin/operateurs', 'AdminController', 'operateurs');
$router->get('/admin/config', 'AdminController', 'config');
$router->post('/admin/config', 'AdminController', 'updateConfig');

return $router;



// ==========================================
// CORE/ROUTER.PHP - Routeur principal
// ==========================================

class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $path, string $controller, string $method): void {
        $this->addRoute('GET', $path, $controller, $method);
    }

    public function post(string $path, string $controller, string $method): void {
        $this->addRoute('POST', $path, $controller, $method);
    }

    public function put(string $path, string $controller, string $method): void {
        $this->addRoute('PUT', $path, $controller, $method);
    }

    public function delete(string $path, string $controller, string $method): void {
        $this->addRoute('DELETE', $path, $controller, $method);
    }

    private function addRoute(string $httpMethod, string $path, string $controller, string $method): void {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $this->basePath . $path,
            'controller' => $controller,
            'action' => $method
        ];
    }

    public function dispatch(): void {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Support pour PUT et DELETE via _method
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $requestUri, $requestMethod)) {
                $this->executeRoute($route, $requestUri);
                return;
            }
        }

        $this->handleNotFound();
    }

    private function matchRoute(array $route, string $requestUri, string $requestMethod): bool {
        return $route['method'] === $requestMethod && 
               $this->pathMatches($route['path'], $requestUri);
    }

    private function pathMatches(string $routePath, string $requestUri): bool {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        return preg_match('/^' . $pattern . '$/', $requestUri);
    }

    private function executeRoute(array $route, string $requestUri): void {
        $controllerClass = $route['controller'];
        
        if (!class_exists($controllerClass)) {
            throw new Exception("Controller {$controllerClass} not found");
        }

        $controller = new $controllerClass();
        $method = $route['action'];

        if (!method_exists($controller, $method)) {
            throw new Exception("Method {$method} not found in {$controllerClass}");
        }

        $params = $this->extractParams($route['path'], $requestUri);
        call_user_func_array([$controller, $method], $params);
    }

    private function extractParams(string $routePath, string $requestUri): array {
        preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $pattern = str_replace('/', '\/', $pattern);
        
        preg_match('/^' . $pattern . '$/', $requestUri, $matches);
        array_shift($matches);
        
        return $matches;
    }

    private function handleNotFound(): void {
        http_response_code(404);
        include APP_PATH . '/Views/errors/404.php';
    }
}

// ==========================================
// CORE/BASECONTROLLER.PHP - Contrôleur de base
// ==========================================

abstract class BaseController {
    protected View $view;
    
    public function __construct() {
        $this->view = new View();
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

    protected function requireAuth(): ?Operateur {
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
        $roleHierarchy = ['operateur' => 1, 'superviseur' => 2, 'admin' => 3];
        
        $userLevel = $roleHierarchy[$user->role] ?? 0;
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

    protected function getCurrentUser(): ?Operateur {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $operateurModel = new Operateur();
        return $operateurModel->find($_SESSION['user_id']);
    }

    protected function setUserSession(Operateur $operateur): void {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $operateur->id;
        $_SESSION['user_email'] = $operateur->email;
        $_SESSION['user_name'] = $operateur->getFullName();
        $_SESSION['user_role'] = $operateur->role;
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

// ==========================================
// CORE/BASEMODEL.PHP - Modèle de base
// ==========================================

abstract class BaseModel {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected bool $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function find(int $id): ?object {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(array $conditions = [], string $orderBy = null, int $limit = null): array {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $operator = $value[0];
                    $val = $value[1];
                    $whereClause[] = "$key $operator ?";
                    $params[] = $val;
                } else {
                    $whereClause[] = "$key = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($row);
        }
        
        return $results;
    }

    public function create(array $data): ?object {
        $filteredData = $this->filterFillable($data);

        if ($this->timestamps) {
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = array_keys($filteredData);
        $values = array_values($filteredData);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->find($this->db->lastInsertId());
    }

    public function update(int $id, array $data): bool {
        $filteredData = $this->filterFillable($data);

        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        $values = [];

        foreach ($filteredData as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    protected function filterFillable(array $data): array {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hydrate(array $data): object {
        $className = static::class;
        $instance = new $className();
        
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        
        return $instance;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }

    public function commit(): bool {
        return $this->db->commit();
    }

    public function rollback(): bool {
        return $this->db->rollback();
    }
}

// ==========================================
// CORE/VIEW.PHP - Gestionnaire de vues
// ==========================================

class View {
    private string $viewsPath;
    private array $data = [];

    public function __construct() {
        $this->viewsPath = APP_PATH . '/Views';
    }

    public function render(string $view, array $data = []): void {
        $this->data = array_merge($this->data, $data);
        extract($this->data);

        $viewFile = $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }

        // Inclure le layout si ce n'est pas une vue partielle
        if (strpos($view, 'layouts/') !== 0 && strpos($view, 'partials/') !== 0) {
            include $this->viewsPath . '/layouts/app.php';
        } else {
            include $viewFile;
        }
    }

    public function setGlobal(string $key, $value): void {
        $this->data[$key] = $value;
    }

    public function getViewPath(string $view): string {
        return $this->viewsPath . '/' . str_replace('.', '/', $view) . '.php';
    }
}