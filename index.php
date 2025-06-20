<?php
/**
 * Structure MVC 
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
 * │   ├── BaseManager.php
 * │   ├── BaseEntity.php
 * │   └── View.php
 * ├── app/
 * │   ├── Controller/
 * │   │   ├── AuthController.php
 * │   │   ├── DashboardController.php
 * │   │   ├── ManageController.php
 * │   │   ├── ApiTemperatureController.php
 * │   ├── Model/
 * │   │   ├──Entity/
 * │   │   │   ├── User.php
 * │   │   │   ├── Capteur.php
 * |   |   |   ├── Manege.php
 * |   |   |   └── Capteur_temperature.php
 * │   │   └──Manager/
 * |   |      ├── UserManager.php
 * |   |      ├── CapteurTemperatureManager.php
 * |   |      └── ManegeManager.php
 * │   └── Views/
 * │       ├── login.html
 * │       ├── dashboard2.html
 * │       ├── login.js
 * │       ├── dashboard2.js
 * │       ├── register.js
 * |       └── register.html
 * 
 */

// ==========================================
// INDEX.PHP - Point d'entrée principal
// ==========================================


/**
 * index.php - Point d'entrée principal
 * Système de Gestion de Manège
 */

session_start();

// Debug temporaire pour développement JSON/erreurs AJAX
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
        APP_PATH . '/Controller/',
        APP_PATH . '/Model/Entity/',
        APP_PATH . '/Model/Manager/',
        CONFIG_PATH . '/'
    ];
    
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

// Charger les classes core
require_once CORE_PATH . '/BaseController.php';
require_once CORE_PATH . '/BaseManager.php';
require_once CORE_PATH . '/View.php';
require_once CORE_PATH . '/Router.php';

// Charger TivaSerial si nécessaire
if (file_exists(ROOT_PATH . '/TivaSerial.php')) {
    require_once ROOT_PATH . '/TivaSerial.php';
}

// Gestion des erreurs
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Error: $message in $file on line $line");
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='background: #ff6b6b; color: white; padding: 10px; margin: 10px;'>";
        echo "<strong>Error:</strong> $message in <strong>$file</strong> on line <strong>$line</strong>";
        echo "</div>";
    }
});

// Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
// Correction : ne pas rediriger si la requête cible /login ou /register ou /api
if (!isset($_SESSION['user_id'])) {
    $uri = $_SERVER['REQUEST_URI'];
    if (
        !preg_match('#^/G11C/G11C_A1/(login|register|logout)(/|$)#', $uri) &&
        !preg_match('#^/G11C/G11C_A1/api/#', $uri) &&
        $uri !== '/G11C/G11C_A1/' &&
        $uri !== '/G11C/G11C_A1'
    ) {
        header('Location: /G11C/G11C_A1/login');
        exit;
    }
}

try {
    // Créer les tables si nécessaire (première installation)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        DatabaseSchema::createTables();
    }
    
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
        echo "<h1>Erreur 500</h1><p>Une erreur s'est produite.</p>";
    }
}