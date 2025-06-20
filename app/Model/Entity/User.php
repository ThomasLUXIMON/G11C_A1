<?php
class User {
    private ?int $id;
    private ?string $email;
    private ?string $mot_de_passe;
    private ?string $nom;
    private ?string $prenom;
    private ?\DateTime $reset_token_expires_at;
    private ?string $reset_token_hash;
    private ?Type $type;
    private ?string $tel; 
    private ?Etat $etat;

    const TABLE_NAME = 'Utilisateurs';

    public function __construct(
        ?int $id = null,
        ?string $nom = null,
        ?string $prenom = null,
        ?string $email = null,
        ?string $mot_de_passe = null,
        ?string $tel = null,
        ?Type $type = null,
        ?string $reset_token_hash = null,
        ?\DateTime $reset_token_expires_at = null,
        ?Etat $etat = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->mot_de_passe = $mot_de_passe;
        $this->type = $type;
        $this->tel = $tel;
        $this->reset_token_hash = $reset_token_hash;
        $this->reset_token_expires_at = $reset_token_expires_at;
        $this->etat = $etat;
    } 

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function getEmail(): ?string { return $this->email; }
    public function getMotDePasse(): ?string { return $this->mot_de_passe; }
    public function getType(): ?string {
        return $this->type?->value;
    }
    public function getTel(): ?string { return $this->tel; }
    public function getResetTokenHash(): ?string { return $this->reset_token_hash; }
    public function getResetTokenExpiresAt(): ?\DateTime { return $this->reset_token_expires_at; }
    public function getEtat(): ?Etat { return $this->etat; }
    
    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setNom(?string $nom): void { $this->nom = $nom; }
    public function setPrenom(?string $prenom): void { $this->prenom = $prenom; }
    public function setEmail(?string $email): void { $this->email = $email; }
    public function setMotDePasse(?string $mot_de_passe): void { $this->mot_de_passe = $mot_de_passe; }
    public function setType(?Type $type): void { $this->type = $type; }
    public function setTel(?string $tel): void { $this->tel = $tel; }
    public function setEtat(?Etat $etat): void { $this->etat = $etat; }
}

enum Type: string {
    case agent = 'agent';
    case manager = 'manager';
    case admin = 'admin';
}

enum Etat: string {
    case actif = 'actif';
    case inactif = 'inactif';
}