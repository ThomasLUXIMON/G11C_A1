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
     * Endpoint pour recevoir les données de température
     * POST /api/sensors/reading
     */
    public function receiveReading(): void {
        // Autoriser les requêtes CORS si nécessaire
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['temperature']) || !is_numeric($input['temperature'])) {
            $this->jsonResponse(['error' => 'Invalid temperature value'], 400);
            return;
        }
        $temperature = (float) $input['temperature'];
        if ($temperature < -50 || $temperature > 100) {
            $this->jsonResponse(['error' => 'Temperature out of range'], 400);
            return;
        }
        $manegeId = isset($input['manege_id']) ? (int) $input['manege_id'] : null;
        try {
            $capteurTemp = $this->temperatureManager->createReading(
                $temperature,
                $manegeId
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
     * GET /api/sensors/readings
     */
    public function getReadings(): void {
        header('Access-Control-Allow-Origin: *');
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $limit = min(max($limit, 1), 100);
        try {
            $capteurs = $this->temperatureManager->getLatestReadings($limit);
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
     * GET /api/sensors/stats
     */
    public function getStats(): void {
        header('Access-Control-Allow-Origin: *');
        $period = $_GET['period'] ?? '24h';
        if (!in_array($period, ['1h', '24h', '7d', '30d'])) {
            $this->jsonResponse(['error' => 'Invalid period'], 400);
            return;
        }
        try {
            $stats = $this->temperatureManager->getTemperatureStats($period);
            $this->jsonResponse([
                'success' => true,
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
     * GET /api/sensors/chart
     */
    public function getChartData(): void {
        header('Access-Control-Allow-Origin: *');
        $period = $_GET['period'] ?? '24h';
        if (!in_array($period, ['1h', '24h', '7d'])) {
            $this->jsonResponse(['error' => 'Invalid period'], 400);
            return;
        }
        try {
            $chartData = $this->temperatureManager->getChartData($period);
            $this->jsonResponse([
                'success' => true,
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