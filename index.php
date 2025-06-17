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
 * │   ├── BaseManager.php
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
 * │   │   │   ├── Capteur.php
 * │   │   └──Manager/
 *     |       ├── UserManager.php
 * │   └── Views/
 * │       ├── login.html
 * │       ├── dashboard.html
 * |       └── 
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
        APP_PATH . '/Controlleur/',
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
require_once CORE_PATH . '/BaseController.php';
require_once CORE_PATH . '/BaseModel.php';
require_once CORE_PATH . '/View.php';
require_once CORE_PATH . '/Router.php';

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