<?php
require_once __DIR__ . '/../../../Core/BaseManager.php';
require_once __DIR__ . '/../Entity/User.php';

class UserManager extends BaseManager {
    protected string $table = 'Utilisateurs';
    protected string $primaryKey = 'id';
      public function __construct() {
        parent::__construct();
    }
    
    public function findAll(array $conditions = [], string $orderBy = null, int $limit = null): array {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->mapToUser($row);
        }

        return $users;
    }
    
    public function findById(int $id): ?User {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToUser($data) : null;    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToUser($data) : null;
    }
    
    public function insert(User $user): bool {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (nom, prenom, email, mot_de_passe, type, tel)
            VALUES (:nom, :prenom, :email, :mot_de_passe, :type, :tel)
        ");

        return $stmt->execute([
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'mot_de_passe' => $user->getMotDePasse(),
            'type' => $user->getType(),
            'tel' => $user->getTel()
        ]);
    }

    public function updateUser(User $user): bool {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET nom = :nom, prenom = :prenom, email = :email, 
                mot_de_passe = :mot_de_passe, type = :type, tel = :tel
            WHERE {$this->primaryKey} = :id
        ");

        return $stmt->execute([
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'mot_de_passe' => $user->getMotDePasse(),
            'type' => $user->getType(),
            'tel' => $user->getTel()
        ]);
    }    public function setResetToken(User $user, string $token, \DateTime $expiresAt): bool {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET reset_token_hash = :hash, reset_token_expires_at = :expires WHERE {$this->primaryKey} = :id
        ");
        return $stmt->execute([
            'hash' => hash('sha256', $token),
            'expires' => $expiresAt->format('Y-m-d H:i:s'),
            'id' => $user->getId()
        ]);
    }

    public function clearResetToken(User $user): bool {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET reset_token_hash = NULL, reset_token_expires_at = NULL WHERE {$this->primaryKey} = :id
        ");
        return $stmt->execute(['id' => $user->getId()]);
    }

    private function mapToUser(array $data): User {
        $expiresAt = null;
        if ($data['reset_token_expires_at']) {
            $expiresAt = new DateTime($data['reset_token_expires_at']);
        }

        return new User(
            $data['id'],
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['mot_de_passe'],
            $data['tel'],
            $data['type'] ?? 'utilisateur',
            $data['reset_token_hash'],
            $expiresAt
        );
    }
}
