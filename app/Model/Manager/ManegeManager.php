<?php
require_once __DIR__ . '/../../../Core/BaseManager.php';
require_once __DIR__ . '/../Entity/Manege.php';
require_once __DIR__ . '/../../Helpers/Security.php';

/**
 * Classe ManageManager
 * Hérite de BaseManager pour gérer les opérations CRUD sur les manèges
 * Gère les interactions avec la base de données pour les manèges
 */
class ManegeManager extends BaseManager {
      // Configuration de la table et des propriétés
    protected string $table = 'maneges';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'nom', 'type', 'capacite_max', 'duree_tour', 
        'age_minimum', 'taille_minimum', 'statut'
    ];
    protected array $hidden = [];
    protected bool $timestamps = true;
    
    /**
     * Constructeur - appelle le constructeur parent pour initialiser la connexion DB
     */
    public function __construct() {
        parent::__construct();
    }
    
    // ===== MÉTHODES SPÉCIFIQUES AUX MANÈGES =====
    
    /**
     * Trouve tous les manèges avec leur statut
     */
    public function findAllWithStatus(): array {
        $sql = "SELECT m.*, 
                       COUNT(s.id) as sessions_actives,
                       MAX(s.heure_debut) as derniere_session
                FROM {$this->table} m
                LEFT JOIN sessions_manege s ON m.id = s.manege_id 
                    AND s.statut IN ('preparation', 'en_cours')
                GROUP BY m.id
                ORDER BY m.nom";
        
        $stmt = $this->query($sql);
        $results = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrateManege($row);
        }
        
        return $results;
    }
    
    /**
     * Trouve un manège par son ID avec ses détails complets
     */
    public function findWithDetails(int $id): ?Manege {
        $sql = "SELECT m.*,
                       COUNT(DISTINCT s.id) as total_sessions,
                       COUNT(DISTINCT CASE WHEN s.statut = 'en_cours' THEN s.id END) as sessions_actives,
                       AVG(s.nombre_passagers) as moyenne_passagers,
                       COUNT(DISTINCT c.id) as nombre_capteurs
                FROM {$this->table} m
                LEFT JOIN sessions_manege s ON m.id = s.manege_id
                LEFT JOIN logs_capteurs c ON m.id = c.manege_id
                WHERE m.id = ?
                GROUP BY m.id";
        
        $stmt = $this->query($sql, [$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrateManege($data) : null;
    }
    
    /**
     * Trouve les manèges par statut
     */
    public function findByStatus(string $statut): array {
        // Sécurisation de l'entrée pour usage SQL (exemple d'utilisation)
        $statut = sanitize_sql_input($statut);
        return $this->findAll(['statut' => $statut], 'nom ASC');
    }
    
    /**
     * Trouve les manèges disponibles (actifs et sans session en cours)
     */
    public function findAvailable(): array {
        $sql = "SELECT m.*
                FROM {$this->table} m
                LEFT JOIN sessions_manege s ON m.id = s.manege_id 
                    AND s.statut IN ('preparation', 'en_cours')
                WHERE m.statut = 'actif' 
                    AND s.id IS NULL
                ORDER BY m.nom";
        
        $stmt = $this->query($sql);
        $results = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrateManege($row);
        }
        
        return $results;
    }
      /**
     * Met à jour le statut d'un manège
     */
    public function updateStatus(int $id, string $nouveauStatut): bool {
        $statutsValides = ['actif', 'maintenance', 'ferme'];
        
        if (!in_array($nouveauStatut, $statutsValides)) {
            throw new InvalidArgumentException("Statut invalide: {$nouveauStatut}");
        }
        $sql = "UPDATE {$this->table} SET statut = :statut WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['statut' => $nouveauStatut, 'id' => $id]);
    }
    
    /**
     * Obtient les statistiques d'un manège
     */
    public function getStatistiques(int $id, ?DateTime $dateDebut = null, ?DateTime $dateFin = null): array {
        $conditions = ['manege_id' => $id];
        $params = [$id];
        
        $sqlWhere = "WHERE s.manege_id = ?";
        
        if ($dateDebut) {
            $sqlWhere .= " AND s.heure_debut >= ?";
            $params[] = $dateDebut->format('Y-m-d H:i:s');
        }
        
        if ($dateFin) {
            $sqlWhere .= " AND s.heure_debut <= ?";
            $params[] = $dateFin->format('Y-m-d H:i:s');
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_sessions,
                    SUM(s.nombre_passagers) as total_passagers,
                    AVG(s.nombre_passagers) as moyenne_passagers,
                    SUM(TIMESTAMPDIFF(MINUTE, s.heure_debut, s.heure_fin)) as duree_totale_minutes,
                    COUNT(CASE WHEN s.statut = 'termine' THEN 1 END) as sessions_terminees,
                    COUNT(CASE WHEN s.statut = 'annule' THEN 1 END) as sessions_annulees
                FROM sessions_manege s
                {$sqlWhere}";
        
        $stmt = $this->query($sql, $params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_sessions' => (int)$stats['total_sessions'],
            'total_passagers' => (int)$stats['total_passagers'],
            'moyenne_passagers' => round((float)$stats['moyenne_passagers'], 2),
            'duree_totale_heures' => round((float)$stats['duree_totale_minutes'] / 60, 2),
            'sessions_terminees' => (int)$stats['sessions_terminees'],
            'sessions_annulees' => (int)$stats['sessions_annulees'],
            'taux_completion' => $stats['total_sessions'] > 0 
                ? round(($stats['sessions_terminees'] / $stats['total_sessions']) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * Obtient les alertes récentes pour un manège
     */
    public function getAlertesRecentes(int $id, int $limite = 10): array {
        $sql = "SELECT a.*, s.heure_debut as session_debut
                FROM alertes a
                LEFT JOIN sessions_manege s ON a.session_id = s.id
                WHERE s.manege_id = ?
                ORDER BY a.created_at DESC
                LIMIT ?";
        
        $stmt = $this->query($sql, [$id, $limite]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si un manège peut être supprimé (pas de sessions actives)
     */
    public function canDelete(int $id): array {
        $sql = "SELECT COUNT(*) as sessions_actives
                FROM sessions_manege
                WHERE manege_id = ? AND statut IN ('preparation', 'en_cours')";
        
        $stmt = $this->query($sql, [$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $canDelete = $result['sessions_actives'] == 0;
        
        return [
            'can_delete' => $canDelete,
            'reason' => $canDelete ? null : 'Le manège a des sessions actives en cours'
        ];
    }
    
    /**
     * Recherche les manèges par nom ou type
     */
    public function search(string $terme): array {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE nom LIKE ? OR type LIKE ?
                ORDER BY nom";
        
        $searchTerm = "%{$terme}%";
        $stmt = $this->query($sql, [$searchTerm, $searchTerm]);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrateManege($row);
        }
        
        return $results;
    }
    
    /**
     * Obtient les capteurs associés à un manège
     */
    public function getCapteurs(int $id): array {
        $sql = "SELECT *
                FROM logs_capteurs
                WHERE manege_id = ?
                GROUP BY capteur_id, type_capteur
                ORDER BY capteur_id";
        
        $stmt = $this->query($sql, [$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Statistiques pour le dashboard
     */
    public function getDashboardStats(): array {
        $sql = "SELECT 
                    COUNT(*) as total_maneges,
                    COUNT(CASE WHEN statut = 'actif' THEN 1 END) as maneges_actifs,
                    COUNT(CASE WHEN statut = 'maintenance' THEN 1 END) as maneges_maintenance,
                    COUNT(CASE WHEN statut = 'inactif' THEN 1 END) as maneges_inactifs,
                    AVG(capacite_max) as capacite_moyenne
                FROM {$this->table}";
        
        $stmt = $this->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Répartition des manèges par type pour graphiques
     */
    public function getManegesByType(): array {
        $sql = "SELECT type, COUNT(*) as count 
                FROM {$this->table} 
                GROUP BY type 
                ORDER BY count DESC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statut en temps réel des manèges
     */
    public function getRealTimeStatus(): array {
        $sql = "SELECT m.id, m.nom, m.type, m.statut, m.capacite_max,
                       COUNT(s.id) as sessions_actives,
                       MAX(s.heure_debut) as derniere_activite,
                       (
                         SELECT ct.temperature
                         FROM capteur_temperatures ct
                         WHERE ct.manege_id = m.id
                         ORDER BY ct.timestamp_mesure DESC
                         LIMIT 1
                       ) as temperature
                FROM {$this->table} m
                LEFT JOIN sessions_manege s ON m.id = s.manege_id 
                    AND s.statut IN ('preparation', 'en_cours')
                GROUP BY m.id
                ORDER BY m.nom";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtient le nombre de sessions actives
     */
    public function getActiveSessionsCount(): int {
        $sql = "SELECT COUNT(*) as count FROM sessions_manege WHERE statut = 'en_cours'";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Obtient le nombre d'alertes en attente
     */
    public function getPendingAlertsCount(): int {
        $sql = "SELECT COUNT(*) as count FROM alertes WHERE acquittee = 0";
        $stmt = $this->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    // ===== MÉTHODES PRIVÉES =====
      /**
     * Hydrate un tableau de données en objet Manege
     */
    private function hydrateManege(array $data): Manege {
        return new Manege(
            $data['id'] ?? null,
            $data['nom'] ?? '',
            $data['type'] ?? '',
            $data['capacite_max'] ?? 0,
            $data['duree_tour'] ?? 0,
            $data['age_minimum'] ?? 0,
            $data['taille_minimum'] ?? 0,
            $data['statut'] ?? 'actif',
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }
    
    /**
     * Valide les données d'un manège avant insertion/mise à jour
     */
    private function validateManageData(array $data): array {
        $errors = [];
        
        if (empty($data['nom'])) {
            $errors[] = "Le nom du manège est requis";
        }
        
        if (empty($data['type'])) {
            $errors[] = "Le type du manège est requis";
        }
        
        if (!isset($data['capacite_max']) || $data['capacite_max'] <= 0) {
            $errors[] = "La capacité maximale doit être supérieure à 0";
        }
        
        if (!isset($data['duree_tour']) || $data['duree_tour'] <= 0) {
            $errors[] = "La durée du tour doit être supérieure à 0";
        }
          if (isset($data['statut']) && !in_array($data['statut'], ['actif', 'maintenance', 'ferme'])) {
            $errors[] = "Statut invalide";
        }
        
        return $errors;
    }
    
    /**
     * Surcharge de la méthode create pour ajouter la validation
     */
    public function create(array $data): ?object {
        $errors = $this->validateManageData($data);
        
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(', ', $errors));
        }
        
        return parent::create($data);
    }
    
    /**
     * Surcharge de la méthode update pour ajouter la validation
     */
    public function update(int $id, array $data): bool {
        if (!empty(array_intersect_key($data, array_flip($this->fillable)))) {
            $errors = $this->validateManageData($data);
            
            if (!empty($errors)) {
                throw new InvalidArgumentException(implode(', ', $errors));
            }
        }
        
        return parent::update($id, $data);
    }

    /**
     * Insère un nouveau manège dans la base de données
     */
    public function insert(Manege $manege): bool {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (nom, type, capacite_max, statut, duree_tour, age_minimum, taille_minimum) VALUES (:nom, :type, :capacite_max, :statut, :duree_tour, :age_minimum, :taille_minimum)");
        return $stmt->execute([
            'nom' => $manege->getNom(),
            'type' => $manege->getType(),
            'capacite_max' => $manege->getCapaciteMax(),
            'statut' => $manege->getStatut(),
            'duree_tour' => $manege->getDureeTour(),
            'age_minimum' => $manege->getAgeMinimum(),
            'taille_minimum' => $manege->getTailleMinimum()
        ]);
    }
}