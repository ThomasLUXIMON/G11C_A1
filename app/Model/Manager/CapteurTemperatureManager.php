<?php
/**
 * CapteurTemperatureManager.php
 * Gestionnaire pour les données de température
 * À placer dans : app/Model/Manager/
 */

require_once __DIR__ . '/../../../Core/BaseManager.php';

class CapteurTemperatureManager extends BaseManager {
    protected string $table = 'capteur_temperatures';
    protected array $fillable = [
        'capteur_id',
        'temperature',
        'manege_id',
        'timestamp_mesure'
    ];

    /**
     * Enregistrer une nouvelle lecture de température
     */
    public function createReading(string $capteurId, float $temperature, ?int $manegeId = null): ?object {
        $data = [
            'capteur_id' => $capteurId,
            'temperature' => $temperature,
            'manege_id' => $manegeId,
            'timestamp_mesure' => date('Y-m-d H:i:s')
        ];

        // Enregistrer dans la table principale
        $result = $this->create($data);

        // Enregistrer aussi dans les logs
        $this->logReading($capteurId, $temperature, $manegeId);

        // Vérifier si la température est anormale et créer une alerte si nécessaire
        $this->checkTemperatureAlert($capteurId, $temperature, $manegeId);

        return $result;
    }

    /**
     * Logger la lecture dans la table logs_capteurs
     */
    private function logReading(string $capteurId, float $temperature, ?int $manegeId): void {
        $sql = "INSERT INTO logs_capteurs (capteur_id, type_capteur, manege_id, distance, etat, donnees_brutes, timestamp_mesure) 
                VALUES (?, 'temperature', ?, ?, ?, ?, ?)";
        
        $etat = $this->getTemperatureStatus($temperature);
        $donneesRaw = json_encode(['temperature' => $temperature, 'unit' => 'celsius']);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $capteurId,
            $manegeId,
            $temperature, // Utiliser le champ distance pour stocker la température
            $etat,
            $donneesRaw,
            date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Déterminer le statut basé sur la température
     */
    private function getTemperatureStatus(float $temperature): string {
        if ($temperature < 10) {
            return 'froid';
        } elseif ($temperature > 35) {
            return 'chaud';
        } elseif ($temperature > 40) {
            return 'danger';
        } else {
            return 'normal';
        }
    }

    /**
     * Vérifier et créer une alerte si la température est anormale
     */
    private function checkTemperatureAlert(string $capteurId, float $temperature, ?int $manegeId): void {
        $niveau = null;
        $message = null;

        if ($temperature < 5) {
            $niveau = 'warning';
            $message = "Température très basse détectée : {$temperature}°C";
        } elseif ($temperature > 40) {
            $niveau = 'danger';
            $message = "Température élevée détectée : {$temperature}°C";
        } elseif ($temperature > 50) {
            $niveau = 'critique';
            $message = "Température critique détectée : {$temperature}°C - Intervention immédiate requise";
        }

        if ($niveau) {
            // Récupérer la session active si elle existe
            $sessionId = $this->getActiveSession($manegeId);

            $sql = "INSERT INTO alertes (session_id, type_alerte, niveau, source, message, donnees) 
                    VALUES (?, 'technique', ?, ?, ?, ?)";

            $source = "capteur_temp_{$capteurId}";

            $donnees = json_encode([
                'temperature' => $temperature,
                'capteur_id' => $capteurId,
                'manege_id' => $manegeId
            ]);

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $niveau, $source, $message, $donnees]);
        }
    }

    /**
     * Récupérer la session active pour un manège
     */
    private function getActiveSession(?int $manegeId): ?int {
        if (!$manegeId) return null;

        $sql = "SELECT id FROM sessions_manege 
                WHERE manege_id = ? 
                AND statut IN ('preparation', 'en_cours') 
                ORDER BY heure_debut DESC 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$manegeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['id'] : null;
    }

    /**
     * Obtenir les dernières lectures de température
     */
    public function getLatestReadings(int $limit = 10, ?string $capteurId = null): array {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if ($capteurId) {
            $sql .= " WHERE capteur_id = ?";
            $params[] = $capteurId;
        }

        $sql .= " ORDER BY timestamp_mesure DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir les statistiques de température
     */
    public function getTemperatureStats(string $capteurId, string $period = '24h'): array {
        $dateLimit = match($period) {
            '1h' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            '24h' => date('Y-m-d H:i:s', strtotime('-24 hours')),
            '7d' => date('Y-m-d H:i:s', strtotime('-7 days')),
            '30d' => date('Y-m-d H:i:s', strtotime('-30 days')),
            default => date('Y-m-d H:i:s', strtotime('-24 hours'))
        };

        $sql = "SELECT 
                    COUNT(*) as total_readings,
                    AVG(temperature) as avg_temp,
                    MIN(temperature) as min_temp,
                    MAX(temperature) as max_temp,
                    STDDEV(temperature) as std_dev
                FROM {$this->table}
                WHERE capteur_id = ? 
                AND timestamp_mesure >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$capteurId, $dateLimit]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir l'historique pour un graphique
     */
    public function getChartData(string $capteurId, string $period = '24h'): array {
        $dateLimit = match($period) {
            '1h' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            '24h' => date('Y-m-d H:i:s', strtotime('-24 hours')),
            '7d' => date('Y-m-d H:i:s', strtotime('-7 days')),
            default => date('Y-m-d H:i:s', strtotime('-24 hours'))
        };

        // Adapter la granularité selon la période
        $groupBy = match($period) {
            '1h' => "DATE_FORMAT(timestamp_mesure, '%Y-%m-%d %H:%i')",
            '24h' => "DATE_FORMAT(timestamp_mesure, '%Y-%m-%d %H:00')",
            '7d' => "DATE_FORMAT(timestamp_mesure, '%Y-%m-%d')",
            default => "DATE_FORMAT(timestamp_mesure, '%Y-%m-%d %H:00')"
        };

        $sql = "SELECT 
                    $groupBy as period,
                    AVG(temperature) as avg_temp,
                    MIN(temperature) as min_temp,
                    MAX(temperature) as max_temp,
                    COUNT(*) as readings_count
                FROM {$this->table}
                WHERE capteur_id = ? 
                AND timestamp_mesure >= ?
                GROUP BY period
                ORDER BY period ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$capteurId, $dateLimit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}