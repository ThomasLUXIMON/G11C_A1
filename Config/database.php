<?php
require_once __DIR__ . '/env.php';
/**
 * Configuration de la base de données AlwaysData
 * config/database.php
 */

// Utilisation des variables d'environnement via $_ENV
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'mysql-appg1d.alwaysdata.net');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'appg1d_projetcommun');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'appg1d_groupec');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? 'Dev$G11C');
if (!defined('DB_PORT')) define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
if (!defined('DB_CHARSET')) define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');


// Configuration série pour TIVA C
if (!defined('SERIAL_PORT')) define('SERIAL_PORT', '/dev/ttyACM0'); // Linux
// define('SERIAL_PORT', 'COM3'); // Windows
if (!defined('BAUD_RATE')) define('BAUD_RATE', 115200);

// Configuration des fichiers
if (!defined('DATA_FILE')) define('DATA_FILE', __DIR__ . '/data/seat_data.json');
if (!defined('LOG_FILE')) define('LOG_FILE', __DIR__ . '/logs/seat_log.txt');

// Configuration API
if (!defined('API_KEY')) define('API_KEY', '');

// Configuration système
if (!defined('SEUIL_DETECTION')) define('SEUIL_DETECTION', 25.0); // Distance en cm pour détecter une personne
if (!defined('TEMPS_ALERTE_OCCUPATION')) define('TEMPS_ALERTE_OCCUPATION', 30); // Secondes avant alerte
if (!defined('INTERVAL_VERIFICATION')) define('INTERVAL_VERIFICATION', 5); // Secondes entre vérifications

// Mode debug
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Timezone
date_default_timezone_set('Europe/Paris');

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/models/',
        __DIR__ . '/controllers/',
        __DIR__ . '/lib/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Gestion des erreurs
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Fonction helper pour la base de données
function db() {
    return Database::getInstance()->getConnection();
}

/**
 * Classe Database - Singleton pour la connexion PDO
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            
            $host =  DB_HOST;
            $dbname =  DB_NAME;
            $user =  DB_USER;
            $pass = DB_PASS;
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, $user, $pass, $options);
            
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Empêcher le clonage
    private function __clone() {}
    
    // Empêcher la désérialisation
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Script de création des tables
 */
class DatabaseSchema {
    
    public static function createTables() {
        $db = Database::getInstance()->getConnection();
        
        // Table des manèges
        $sql = "CREATE TABLE IF NOT EXISTS maneges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            type VARCHAR(50) NOT NULL,
            capacite_max INT NOT NULL,
            duree_tour INT NOT NULL COMMENT 'Durée en secondes',
            age_minimum INT DEFAULT 0,
            taille_minimum INT DEFAULT 0 COMMENT 'Taille en cm',
            statut ENUM('actif', 'maintenance', 'ferme') DEFAULT 'actif',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_statut (statut)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table des sessions de manège
        $sql = "CREATE TABLE IF NOT EXISTS sessions_manege (
            id INT AUTO_INCREMENT PRIMARY KEY,
            manege_id INT NOT NULL,
            operateur_id INT NOT NULL,
            heure_debut DATETIME NOT NULL,
            heure_fin DATETIME,
            nombre_passagers INT DEFAULT 0,
            statut ENUM('preparation', 'en_cours', 'termine', 'annule') DEFAULT 'preparation',
            commentaire TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (manege_id) REFERENCES maneges(id),
            FOREIGN KEY (operateur_id) REFERENCES operateurs(id),
            INDEX idx_statut (statut),
            INDEX idx_date (heure_debut)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table des contrôles de sécurité
        $sql = "CREATE TABLE IF NOT EXISTS controles_securite (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            type_controle ENUM('siege', 'harnais', 'barriere', 'capteur', 'urgence') NOT NULL,
            siege_numero INT,
            etat_detection ENUM('libre', 'occupe', 'erreur') NOT NULL,
            distance_capteur FLOAT COMMENT 'Distance en cm',
            validation BOOLEAN DEFAULT FALSE,
            timestamp_controle DATETIME NOT NULL,
            donnees_capteur JSON,
            FOREIGN KEY (session_id) REFERENCES sessions_manege(id) ON DELETE CASCADE,
            INDEX idx_session (session_id),
            INDEX idx_timestamp (timestamp_controle),
            INDEX idx_type (type_controle)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table des passagers
        $sql = "CREATE TABLE IF NOT EXISTS passagers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            siege_numero INT NOT NULL,
            nom VARCHAR(100),
            age INT,
            taille INT COMMENT 'Taille en cm',
            poids INT COMMENT 'Poids en kg',
            heure_embarquement DATETIME NOT NULL,
            heure_debarquement DATETIME,
            validation_securite BOOLEAN DEFAULT FALSE,
            temps_occupation INT COMMENT 'Durée en secondes',
            FOREIGN KEY (session_id) REFERENCES sessions_manege(id) ON DELETE CASCADE,
            INDEX idx_session_siege (session_id, siege_numero),
            UNIQUE KEY unique_session_siege (session_id, siege_numero)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table des alertes
        $sql = "CREATE TABLE IF NOT EXISTS alertes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT,
            type_alerte ENUM('securite', 'technique', 'urgence', 'maintenance') NOT NULL,
            niveau ENUM('info', 'warning', 'danger', 'critique') NOT NULL,
            source VARCHAR(50) NOT NULL COMMENT 'Ex: capteur_siege_5',
            message TEXT NOT NULL,
            donnees JSON,
            acquittee BOOLEAN DEFAULT FALSE,
            acquittee_par INT,
            acquittee_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES sessions_manege(id),
            FOREIGN KEY (acquittee_par) REFERENCES operateurs(id),
            INDEX idx_niveau (niveau),
            INDEX idx_acquittee (acquittee),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table de logs des capteurs
        $sql = "CREATE TABLE IF NOT EXISTS logs_capteurs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            capteur_id VARCHAR(50) NOT NULL,
            type_capteur VARCHAR(50) NOT NULL,
            manege_id INT,
            siege_numero INT,
            distance FLOAT,
            etat VARCHAR(20),
            donnees_brutes TEXT,
            timestamp_mesure DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (manege_id) REFERENCES maneges(id),
            INDEX idx_capteur (capteur_id),
            INDEX idx_timestamp (timestamp_mesure),
            INDEX idx_manege_siege (manege_id, siege_numero)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table des statistiques
        $sql = "CREATE TABLE IF NOT EXISTS statistiques (
            id INT AUTO_INCREMENT PRIMARY KEY,
            manege_id INT NOT NULL,
            date DATE NOT NULL,
            nombre_sessions INT DEFAULT 0,
            nombre_passagers INT DEFAULT 0,
            duree_totale_operation INT DEFAULT 0 COMMENT 'En secondes',
            duree_moyenne_session INT DEFAULT 0 COMMENT 'En secondes',
            taux_occupation FLOAT DEFAULT 0,
            nombre_alertes INT DEFAULT 0,
            revenus DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (manege_id) REFERENCES maneges(id),
            UNIQUE KEY unique_manege_date (manege_id, date),
            INDEX idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Table de configuration
        $sql = "CREATE TABLE IF NOT EXISTS configuration (
            cle VARCHAR(100) PRIMARY KEY,
            valeur TEXT,
            type VARCHAR(20) DEFAULT 'string',
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
        
        // Insertion des configurations par défaut
        $configs = [
            ['cle' => 'seuil_detection_siege', 'valeur' => '25', 'type' => 'float', 
             'description' => 'Distance en cm pour détecter une personne assise'],
            ['cle' => 'temps_alerte_occupation', 'valeur' => '30', 'type' => 'int', 
             'description' => 'Temps en secondes avant alerte occupation prolongée'],
            ['cle' => 'port_serie_tiva', 'valeur' => '/dev/ttyACM0', 'type' => 'string', 
             'description' => 'Port série pour communication avec TIVA C'],
            ['cle' => 'interval_verification', 'valeur' => '5', 'type' => 'int', 
             'description' => 'Intervalle en secondes entre vérifications'],
            ['cle' => 'mode_debug', 'valeur' => '0', 'type' => 'boolean', 
             'description' => 'Active/désactive le mode debug']
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO configuration (cle, valeur, type, description) 
                             VALUES (:cle, :valeur, :type, :description)");
        
        foreach ($configs as $config) {
            $stmt->execute($config);
        }
        
        return true;
    }
    
    /**
     * Insertion de données de test
     */
    public static function seedTestData() {
        $db = Database::getInstance()->getConnection();
        
        // Insérer un opérateur admin par défaut
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT IGNORE INTO operateurs (nom, prenom, email, mot_de_passe, role) 
                             VALUES ('Admin', 'System', 'admin@manege.local', :password, 'admin')");
        $stmt->execute(['password' => $password]);
        
        // Insérer des manèges de test
        $maneges = [
            ['nom' => 'Grande Roue', 'type' => 'roue', 'capacite_max' => 40, 'duree_tour' => 600, 
             'age_minimum' => 0, 'taille_minimum' => 0],
            ['nom' => 'Montagnes Russes', 'type' => 'roller_coaster', 'capacite_max' => 24, 
             'duree_tour' => 180, 'age_minimum' => 12, 'taille_minimum' => 140],
            ['nom' => 'Carrousel', 'type' => 'carrousel', 'capacite_max' => 30, 'duree_tour' => 300, 
             'age_minimum' => 3, 'taille_minimum' => 0]
        ];
        
        $stmt = $db->prepare("INSERT IGNORE INTO maneges (nom, type, capacite_max, duree_tour, 
                             age_minimum, taille_minimum) 
                             VALUES (:nom, :type, :capacite_max, :duree_tour, 
                             :age_minimum, :taille_minimum)");
        
        foreach ($maneges as $manege) {
            $stmt->execute($manege);
        }
        
        return true;
    }
}