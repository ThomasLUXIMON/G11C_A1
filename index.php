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
 * │   │   │   ├── Manege.php
 * │   │   │   ├──
 * │   │   └──Manager/
 *     |       ├──UserManager.php
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