<?php

require_once '../Entity/User.php'; // Inclure la classe User

class UserManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Récupérer tous les utilisateurs
    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM Utilisateurs");
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->mapToUser($row);
        }

        return $users;
    }

    // Récupérer un utilisateur par son ID
    public function findById(int $id): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToUser($data) : null;
    }

    // Insérer un nouvel utilisateur
    public function insert(User $user): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO Utilisateurs (nom, prenom, email, mot_de_passe, type, tel)
            VALUES (:nom, :prenom, :email, :mot_de_passe, :type, :tel)
        ");

        return $stmt->execute([
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'mot_de_passe' => $user->getMotDePasse(),
            'tel' => $user->getTel()
        ]);
    }

    // Mapper un tableau associatif vers un objet User
    private function mapToUser(array $data): User {
        return new User(
            $data['id'],
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['mot_de_passe'],
            $data['tel'],
            $data['reset_token_hash'],
            $data['reset_token_expires_at'] ?? null,
        );
    }
}
