<?php

class UserManager {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM Utilisateurs");
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->mapToUser($row);
        }

        return $users;
    }

    public function findById(int $id): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateurs WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToUser($data) : null;
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateurs WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->mapToUser($data) : null;
    }

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
            'type' => $user->getType(),
            'tel' => $user->getTel()
        ]);
    }

    public function update(User $user): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Utilisateurs 
            SET nom = :nom, prenom = :prenom, email = :email, 
                mot_de_passe = :mot_de_passe, type = :type, tel = :tel
            WHERE id = :id
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
    }

    public function setResetToken(User $user, string $token, \DateTime $expiresAt): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Utilisateurs SET reset_token_hash = :hash, reset_token_expires_at = :expires WHERE id = :id
        ");
        return $stmt->execute([
            'hash' => hash('sha256', $token),
            'expires' => $expiresAt->format('Y-m-d H:i:s'),
            'id' => $user->getId()
        ]);
    }

    public function clearResetToken(User $user): bool {
        $stmt = $this->pdo->prepare("
            UPDATE Utilisateurs SET reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = :id
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
