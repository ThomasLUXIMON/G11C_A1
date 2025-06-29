<?php
/**
 * Modèle de base pour l'architecture MVC
 * models/Model.php
 */

require_once __DIR__ . '/../Config/app.php';

abstract class BaseEntity {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Trouve un enregistrement par son ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Récupère tous les enregistrements
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Conditions WHERE
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $operator = $value[0];
                    $val = $value[1];
                    $whereClause[] = "$key $operator ?";
                    $params[] = $val;
                } else {
                    $whereClause[] = "$key = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        // ORDER BY
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        // LIMIT
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Crée un nouvel enregistrement
     */
    public function create($data) {
        // Filtrer les données selon fillable
        $filteredData = $this->filterFillable($data);
        
        // Ajouter timestamps si activé
        if ($this->timestamps) {
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($filteredData);
        $values = array_values($filteredData);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Met à jour un enregistrement
     */
    public function update($id, $data) {
        // Filtrer les données selon fillable
        $filteredData = $this->filterFillable($data);
        
        // Mettre à jour le timestamp
        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = [];
        $values = [];
        
        foreach ($filteredData as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . 
               " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Supprime un enregistrement
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Compte les enregistrements
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
    
    /**
     * Exécute une requête SQL personnalisée
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Filtre les données selon l'attribut fillable
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Retire les champs cachés d'un résultat
     */
    protected function hideFields($data) {
        if (empty($this->hidden)) {
            return $data;
        }
        
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Début d'une transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Valide une transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Annule une transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }
}

/**
 * Modèle pour les sessions de manège
 */
class SessionManege extends BaseEntity {
    protected $table = 'sessions_manege';
    protected $fillable = ['manege_id', 'operateur_id', 'heure_debut', 
                          'heure_fin', 'nombre_passagers', 'statut', 'commentaire'];
    
    /**
     * Démarre une nouvelle session
     */
    public function demarrerSession($manegeId, $operateurId) {
        $data = [
            'manege_id' => $manegeId,
            'operateur_id' => $operateurId,
            'heure_debut' => date('Y-m-d H:i:s'),
            'statut' => 'preparation'
        ];
        
        return $this->create($data);
    }
    
    /**
     * Récupère la session active d'un manège
     */
    public function getSessionActive($manegeId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE manege_id = ? AND statut IN ('preparation', 'en_cours')
             ORDER BY heure_debut DESC LIMIT 1"
        );
        $stmt->execute([$manegeId]);
        return $stmt->fetch();
    }
    
    /**
     * Récupère les contrôles de sécurité d'une session
     */
    public function getControlesSecurite($sessionId) {
        $sql = "SELECT * FROM controles_securite 
                WHERE session_id = ? 
                ORDER BY timestamp_controle DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère l'état des sièges pour une session
     */
    public function getEtatSieges($sessionId) {
        $sql = "SELECT cs.siege_numero, cs.etat_detection, cs.distance_capteur,
                       p.nom as passager_nom, p.validation_securite
                FROM controles_securite cs
                LEFT JOIN passagers p ON cs.session_id = p.session_id 
                    AND cs.siege_numero = p.siege_numero
                WHERE cs.session_id = ? AND cs.type_controle = 'siege'
                AND cs.timestamp_controle = (
                    SELECT MAX(timestamp_controle) 
                    FROM controles_securite 
                    WHERE session_id = ? 
                    AND siege_numero = cs.siege_numero
                    AND type_controle = 'siege'
                )
                ORDER BY cs.siege_numero";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $sessionId]);
        return $stmt->fetchAll();
    }
}

/**
 * Modèle pour les contrôles de sécurité
 */
class ControleSecurite extends BaseEntity {
    protected $table = 'controles_securite';
    protected $fillable = ['session_id', 'type_controle', 'siege_numero', 
                          'etat_detection', 'distance_capteur', 'validation', 
                          'timestamp_controle', 'donnees_capteur'];
    protected $timestamps = false;
    
    /**
     * Enregistre un contrôle depuis les données TIVA
     */
    public function enregistrerDepuisTiva($sessionId, $donneesTiva) {
        $data = [
            'session_id' => $sessionId,
            'type_controle' => 'siege',
            'siege_numero' => $donneesTiva['siege_numero'] ?? 1,
            'etat_detection' => $donneesTiva['state'] === 'occupied' ? 'occupe' : 'libre',
            'distance_capteur' => $donneesTiva['distance'] ?? null,
            'validation' => $donneesTiva['state'] === 'occupied',
            'timestamp_controle' => date('Y-m-d H:i:s'),
            'donnees_capteur' => json_encode($donneesTiva)
        ];
        
        return $this->create($data);
    }
    
    /**
     * Vérifie si tous les sièges sont sécurisés
     */
    public function verifierSecuriteComplete($sessionId) {
        $sql = "SELECT COUNT(DISTINCT siege_numero) as sieges_non_securises
                FROM controles_securite
                WHERE session_id = ? 
                AND type_controle = 'siege'
                AND etat_detection = 'occupe'
                AND validation = 0
                AND timestamp_controle >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        
        return $result['sieges_non_securises'] == 0;
    }
}

/**
 * Modèle pour les alertes
 */
class Alerte extends BaseEntity {
    protected $table = 'alertes';
    protected $fillable = ['session_id', 'type_alerte', 'niveau', 'source', 
                          'message', 'donnees', 'acquittee', 'acquittee_par', 
                          'acquittee_at'];
    
    /**
     * Crée une alerte de sécurité
     */
    public function creerAlerteSecurite($sessionId, $source, $message, $donnees = null) {
        return $this->create([
            'session_id' => $sessionId,
            'type_alerte' => 'securite',
            'niveau' => 'danger',
            'source' => $source,
            'message' => $message,
            'donnees' => json_encode($donnees)
        ]);
    }
    
    /**
     * Récupère les alertes non acquittées
     */
    public function getAlertesNonAcquittees($sessionId = null) {
        $sql = "SELECT a.*, s.manege_id, m.nom as manege_nom
                FROM {$this->table} a
                LEFT JOIN sessions_manege s ON a.session_id = s.id
                LEFT JOIN maneges m ON s.manege_id = m.id
                WHERE a.acquittee = 0";
        
        $params = [];
        if ($sessionId) {
            $sql .= " AND a.session_id = ?";
            $params[] = $sessionId;
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Acquitte une alerte
     */
    public function acquitter($alerteId, $operateurId) {
        return $this->update($alerteId, [
            'acquittee' => true,
            'acquittee_par' => $operateurId,
            'acquittee_at' => date('Y-m-d H:i:s')
        ]);
    }
}