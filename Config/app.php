<?php
// ==========================================
// CONFIG/APP.PHP - Configuration application corrigée
// ==========================================

// Configuration générale
define('APP_NAME', 'Système de Gestion de Manège');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', true); // false en production

// Configuration sécurité
define('SESSION_LIFETIME', 3600 * 8); // 8 heures
define('REMEMBER_TOKEN_LIFETIME', 30 * 24 * 3600); // 30 jours

// Configuration TIVA C A REVOIR
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