<?php
class DashboardController extends BaseController {
    
    public function index(): void {
        $user = $this->requireAuth();
        
        $data = [
            'user' => $user,
            'stats' => $this->getGeneralStats(),
            'recent_alerts' => $this->getRecentAlerts(),
            'active_sessions' => $this->getActiveSessions(),
            'maneges' => $this->getManegesStatus()
        ];
        
        $this->render('dashboard/index', $data);
    }
    
    public function getStats(): void {
        $this->requireAuth();
        
        $stats = $this->getGeneralStats();
        $this->json(['success' => true, 'data' => $stats]);
    }
    
    public function getAlertsCount(): void {
        $this->requireAuth();
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM alertes 
            WHERE acquittee = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        $this->json(['success' => true, 'count' => $result['count'] ?? 0]);
    }
    
    private function getGeneralStats(): array {
        // Sessions actives
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM sessions_manege WHERE statut = 'en_cours'");
        $activeSessions = $stmt->fetch()['count'] ?? 0;
        
        // Alertes non traitées
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM alertes WHERE acquittee = 0");
        $pendingAlerts = $stmt->fetch()['count'] ?? 0;
        
        // Manèges actifs
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM maneges WHERE statut = 'actif'");
        $activeRides = $stmt->fetch()['count'] ?? 0;
        
        // Total manèges
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM maneges");
        $totalRides = $stmt->fetch()['count'] ?? 0;
        
        // Passagers aujourd'hui
        $stmt = $this->db->query("
            SELECT COUNT(*) as count 
            FROM passagers p
            JOIN sessions_manege s ON p.session_id = s.id
            WHERE DATE(s.heure_debut) = CURDATE()
        ");
        $todayPassengers = $stmt->fetch()['count'] ?? 0;
        
        // Taux d'occupation moyen
        $stmt = $this->db->query("
            SELECT AVG(p.passenger_count / m.capacite_max * 100) as avg_rate
            FROM (
                SELECT session_id, COUNT(*) as passenger_count
                FROM passagers
                GROUP BY session_id
            ) p
            JOIN sessions_manege s ON p.session_id = s.id
            JOIN maneges m ON s.manege_id = m.id
            WHERE s.statut = 'termine' AND DATE(s.heure_debut) = CURDATE()
        ");
        $occupancyRate = round($stmt->fetch()['avg_rate'] ?? 0, 1);
        
        return [
            'active_sessions' => $activeSessions,
            'pending_alerts' => $pendingAlerts,
            'active_rides' => $activeRides,
            'total_rides' => $totalRides,
            'today_passengers' => $todayPassengers,
            'occupancy_rate' => $occupancyRate,
            'last_update' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getRecentAlerts(): array {
        $stmt = $this->db->prepare("
            SELECT a.*, m.nom as manege_nom
            FROM alertes a
            LEFT JOIN sessions_manege s ON a.session_id = s.id
            LEFT JOIN maneges m ON s.manege_id = m.id
            WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY a.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    private function getActiveSessions(): array {
        $stmt = $this->db->prepare("
            SELECT s.*, m.nom as manege_nom, o.nom as operateur_nom, o.prenom as operateur_prenom,
                   COUNT(p.id) as nombre_passagers_actuel
            FROM sessions_manege s
            JOIN maneges m ON s.manege_id = m.id
            JOIN operateurs o ON s.operateur_id = o.id
            LEFT JOIN passagers p ON s.id = p.session_id
            WHERE s.statut = 'en_cours'
            GROUP BY s.id
            ORDER BY s.heure_debut DESC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    private function getManegesStatus(): array {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   CASE WHEN s.id IS NOT NULL THEN 'occupé' ELSE 'libre' END as etat_actuel,
                   s.id as session_id,
                   COUNT(DISTINCT p.id) as passagers_actuels
            FROM maneges m
            LEFT JOIN sessions_manege s ON m.id = s.manege_id AND s.statut = 'en_cours'
            LEFT JOIN passagers p ON s.id = p.session_id
            WHERE m.statut = 'actif'
            GROUP BY m.id
            ORDER BY m.nom
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}