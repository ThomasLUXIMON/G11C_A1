<?php
class User {
    private ?int $id;
    private ?string $email;
    private ?string $mot_de_passe;
    private ?string $nom;
    private ?string $prenom;
    private ?DateTime $reset_token_expires_at;
    private ?string $reset_token_hash;
    private ?string $type; // ajout 
    private ?int $tel;
    const TABLE_NAME = 'Utilisateur';

   public function __construct(
        ?int $id = null,
        ?string $nom = null,
        ?string $prenom = null,
        ?string $email = null,
        ?string $mot_de_passe = null,
        ?string $tel = null,
        ?int $type = null,
        ?string $reset_token_hash = null,
        ?\DateTime $reset_token_expires_at = null
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
    } 
    // Getters
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function getEmail(): ?string { return $this->email; }
    public function getMotDePasse(): ?string { return $this->mot_de_passe; }
    public function getType(): ?string { return $this->type; }
    public function getTel(): ?string { return $this->tel; }
    public function getResetTokenHash(): ?string { return $this->reset_token_hash; }
    public function getResetTokenExpiresAt(): ?\DateTime { return $this->reset_token_expires_at; }
}
