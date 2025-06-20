<?php
require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Model/Manager/ManegeManager.php';
require_once __DIR__ . '/../Model/Entity/Manege.php';

class DashboardController extends BaseController {
    
    public function index(): void {
        $user = $this->requireAuth();
        $this->render('dashboard2', ['user' => $user]);
    }
    
    public function getStats(): void {
        $this->requireAuth();
        
        $stats = $this->getDashboardData();
        $this->json(['success' => true, 'data' => $stats]);
    }
    
    public function getManegesData(): void {
        $this->requireAuth();
        
        $manegeManager = new ManegeManager();
        $maneges = $manegeManager->getRealTimeStatus();
        $this->json(['success' => true, 'maneges' => $maneges]);
    }
      private function getDashboardData(): array {
        $manegeManager = new ManegeManager();
        
        // Statistiques générales
        $generalStats = $manegeManager->getDashboardStats();
        
        // Répartition par type pour graphiques
        $manegesByType = $manegeManager->getManegesByType();
        
        // Statut en temps réel
        $realTimeStatus = $manegeManager->getRealTimeStatus();
        
        // Sessions actives et alertes via ManegeManager
        $activeSessions = $manegeManager->getActiveSessionsCount();
        $pendingAlerts = $manegeManager->getPendingAlertsCount();
          return [
            'total_maneges' => $generalStats['total_maneges'] ?? 0,
            'maneges_actifs' => $generalStats['maneges_actifs'] ?? 0,
            'maneges_maintenance' => $generalStats['maneges_maintenance'] ?? 0,
            'maneges_inactifs' => $generalStats['maneges_inactifs'] ?? 0,
            'capacite_moyenne' => round($generalStats['capacite_moyenne'] ?? 0, 1),
            'sessions_actives' => $activeSessions,
            'alertes_non_traitees' => $pendingAlerts,
            'maneges_by_type' => $manegesByType,
            'maneges_status' => $realTimeStatus,
            'taux_occupation' => $this->calculateOccupancyRate($realTimeStatus)
        ];
    }

    private function calculateOccupancyRate(array $maneges): float {
        if (empty($maneges)) return 0;
        
        $total = count($maneges);
        $active = array_filter($maneges, fn($m) => ($m['sessions_actives'] ?? 0) > 0);
        
        return round((count($active) / $total) * 100, 1);
    }

    public function getAlertsCount(): void {
        $this->requireAuth();
        
        $manegeManager = new ManegeManager();
        $count = $manegeManager->getPendingAlertsCount();
        
        $this->json(['success' => true, 'count' => $count]);
    }
    
    public function dashboard2(): void {
        $user = $this->requireAuth();
        // On peut passer des infos utilisateur si besoin
        $this->render('dashboard2', ['user' => $user]);
    }
    
    public function gestionManege(): void {
        $user = $this->requireAuth();
        $this->render('gestion_manege', ['user' => $user]);
    }
    
    public function startManegeSession(): void {
        $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $manegeId = $input['manege_id'] ?? null;
        if (!$manegeId) {
            $this->json(['success' => false, 'message' => 'ID manège manquant'], 400);
            return;
        }
        require_once __DIR__ . '/../Model/Manager/SessionHelper.php';
        try {
            \SessionHelper::startSessionAndActivateManege((int)$manegeId);
            $this->json(['success' => true, 'message' => 'Manège lancé et activé']);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}