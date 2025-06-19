<?php
/**
 * ApiTemperatureController.php
 * Contrôleur API pour recevoir les données de température
 */

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Model/Manager/CapteurTemperatureManager.php';

class ApiTemperatureController extends BaseController {
    private CapteurTemperatureManager $temperatureManager;

    public function __construct() {
        parent::__construct();
        $this->temperatureManager = new CapteurTemperatureManager();
    }

    /**
     * Endpoint pour recevoir les données d'un capteur
     * POST /api/sensors/{capteurId}/reading
     */
    public function receiveReading(string $capteurId): void {
        // Autoriser les requêtes CORS si nécessaire
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Vérifier que c'est une requête POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        // Récupérer les données JSON
        $input = json_decode(file_get_contents('php://input'), true);

        // Valider les données
        if (!isset($input['temperature']) || !is_numeric($input['temperature'])) {
            $this->jsonResponse(['error' => 'Invalid temperature value'], 400);
            return;
        }

        $temperature = (float) $input['temperature'];
        
        // Valider la plage de température (entre -50 et 100°C)
        if ($temperature < -50 || $temperature > 100) {
            $this->jsonResponse(['error' => 'Temperature out of range'], 400);
            return;
        }

        // Récupérer les données optionnelles
        $manegeId = isset($input['manege_id']) ? (int) $input['manege_id'] : null;
        $siegeNumero = isset($input['siege_numero']) ? (int) $input['siege_numero'] : null;

        try {
            // Enregistrer la lecture et recevoir un objet Capteur_temperature
            $capteurTemp = $this->temperatureManager->createReading(
                $capteurId,
                $temperature,
                $manegeId,
                $siegeNumero
            );

            if ($capteurTemp) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Temperature reading saved',
                    'data' => $capteurTemp->toJson(),
                    'rapport' => $capteurTemp->genererRapport()
                ], 200);
            } else {
                $this->jsonResponse(['error' => 'Failed to save reading'], 500);
            }
        } catch (Exception $e) {
            error_log("Error saving temperature: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Obtenir les dernières lectures
     * GET /api/sensors/{capteurId}/readings
     */
    public function getReadings(string $capteurId): void {
        header('Access-Control-Allow-Origin: *');
        
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $limit = min(max($limit, 1), 100); // Entre 1 et 100

        try {
            // Récupérer les capteurs sous forme d'objets Capteur_temperature
            $capteurs = $this->temperatureManager->getLatestReadings($limit, $capteurId);
            
            // Convertir en format JSON
            $readings = array_map(function($capteur) {
                return $capteur->toJson();
            }, $capteurs);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $readings,
                'count' => count($readings)
            ]);
        } catch (Exception $e) {
            error_log("Error fetching readings: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to fetch readings'], 500);
        }
    }

    /**
     * Obtenir les statistiques
     * GET /api/sensors/{capteurId}/stats
     */
    public function getStats(string $capteurId): void {
        header('Access-Control-Allow-Origin: *');
        
        $period = $_GET['period'] ?? '24h';
        
        if (!in_array($period, ['1h', '24h', '7d', '30d'])) {
            $this->jsonResponse(['error' => 'Invalid period'], 400);
            return;
        }

        try {
            $stats = $this->temperatureManager->getTemperatureStats($capteurId, $period);
            
            $this->jsonResponse([
                'success' => true,
                'capteur_id' => $capteurId,
                'period' => $period,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            error_log("Error fetching stats: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to fetch statistics'], 500);
        }
    }

    /**
     * Obtenir les données pour graphique
     * GET /api/sensors/{capteurId}/chart
     */
    public function getChartData(string $capteurId): void {
        header('Access-Control-Allow-Origin: *');
        
        $period = $_GET['period'] ?? '24h';
        
        if (!in_array($period, ['1h', '24h', '7d'])) {
            $this->jsonResponse(['error' => 'Invalid period'], 400);
            return;
        }

        try {
            $chartData = $this->temperatureManager->getChartData($capteurId, $period);
            
            $this->jsonResponse([
                'success' => true,
                'capteur_id' => $capteurId,
                'period' => $period,
                'data' => $chartData
            ]);
        } catch (Exception $e) {
            error_log("Error fetching chart data: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Failed to fetch chart data'], 500);
        }
    }

    /**
     * Test de connexion
     * GET /api/sensors/test
     */
    public function test(): void {
        header('Access-Control-Allow-Origin: *');
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'API is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => $_SERVER['SERVER_NAME'] ?? 'unknown'
        ]);
    }

    /**
     * Réponse JSON standardisée
     */
    private function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}