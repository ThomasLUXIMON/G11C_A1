<?php
class DashboardController extends BaseController {
    
    public function index(): void {
        $user = $this->requireAuth();
        
        $data = [
            'user' => $user,
            'stats' => $this->getGeneralStats(),
            'recent_alerts' => $this->getRecentAlerts()
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
        
        $this->json(['success' => true, 'count' => $result['count']]);
    }
    
    private function getGeneralStats(): array {
        // Sessions actives
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM sessions_manege WHERE statut = 'en_cours'");
        $activeSessions = $stmt->fetch()['count'];
        
        // Alertes non traitées
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM alertes WHERE acquittee = 0");
        $pendingAlerts = $stmt->fetch()['count'];
        
        // Manèges actifs
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM maneges WHERE statut = 'actif'");
        $activeRides = $stmt->fetch()['count'];
        
        return [
            'active_sessions' => $activeSessions,
            'pending_alerts' => $pendingAlerts,
            'active_rides' => $activeRides,
            'last_update' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getRecentAlerts(): array {
        $stmt = $this->db->prepare("
            SELECT * FROM alertes 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}