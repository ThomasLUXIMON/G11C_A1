<?php
abstract class BaseModel {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected bool $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function find(int $id): ?object {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? $this->hydrate($data) : null;
    }

    public function findAll(array $conditions = [], string $orderBy = null, int $limit = null): array {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

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

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->hydrate($row);
        }
        
        return $results;
    }

    public function create(array $data): ?object {
        $filteredData = $this->filterFillable($data);

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

        return $this->find($this->db->lastInsertId());
    }

    public function update(int $id, array $data): bool {
        $filteredData = $this->filterFillable($data);

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

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    protected function filterFillable(array $data): array {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hydrate(array $data): object {
        $className = static::class;
        $instance = new $className();
        
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }
        
        return $instance;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }

    public function commit(): bool {
        return $this->db->commit();
    }

    public function rollback(): bool {
        return $this->db->rollback();
    }
}