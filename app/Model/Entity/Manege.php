<?php
class Manege {
    protected ?int $id;
    protected string $nom;
    protected string $type;
    protected int $capacite_max;
    protected int $duree_tour;
    protected int $age_minimum;
    protected int $taille_minimum;
    protected string $statut;
    protected ?string $created_at;
    protected ?string $updated_at;
    
    const TABLE_NAME = 'maneges';
    
    public function __construct(
        ?int $id = null,
        string $nom = '',
        string $type = '',
        int $capacite_max = 0,
        int $duree_tour = 0,
        int $age_minimum = 0,
        int $taille_minimum = 0,
        string $statut = 'actif',
        ?string $created_at = null,
        ?string $updated_at = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->type = $type;
        $this->capacite_max = $capacite_max;
        $this->duree_tour = $duree_tour;
        $this->age_minimum = $age_minimum;
        $this->taille_minimum = $taille_minimum;
        $this->statut = $statut;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    // Getters
    public function getId(): ?int {
        return $this->id;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getCapaciteMax(): int {
        return $this->capacite_max;
    }

    public function getDureeTour(): int {
        return $this->duree_tour;
    }

    public function getAgeMinimum(): int {
        return $this->age_minimum;
    }

    public function getTailleMinimum(): int {
        return $this->taille_minimum;
    }

    public function getStatut(): string {
        return $this->statut;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string {
        return $this->updated_at;
    }

    // Setters
    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setNom(string $nom): void {
        $this->nom = $nom;
    }

    public function setType(string $type): void {
        $this->type = $type;
    }

    public function setCapaciteMax(int $capacite_max): void {
        $this->capacite_max = $capacite_max;
    }

    public function setDureeTour(int $duree_tour): void {
        $this->duree_tour = $duree_tour;
    }

    public function setAgeMinimum(int $age_minimum): void {
        $this->age_minimum = $age_minimum;
    }

    public function setTailleMinimum(int $taille_minimum): void {
        $this->taille_minimum = $taille_minimum;
    }

    public function setStatut(string $statut): void {
        $this->statut = $statut;
    }

    public function setCreatedAt(?string $created_at): void {
        $this->created_at = $created_at;
    }

    public function setUpdatedAt(?string $updated_at): void {
        $this->updated_at = $updated_at;
    }

    // Méthodes utilitaires
    public function isActif(): bool {
        return $this->statut === 'actif';
    }

    public function isEnMaintenance(): bool {
        return $this->statut === 'maintenance';
    }

    public function isFerme(): bool {
        return $this->statut === 'ferme';
    }

    public function getDureeTourFormatted(): string {
        $minutes = floor($this->duree_tour / 60);
        $seconds = $this->duree_tour % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getTailleMinimumFormatted(): string {
        return $this->taille_minimum > 0 ? $this->taille_minimum . ' cm' : 'Aucune restriction';
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type' => $this->type,
            'capacite_max' => $this->capacite_max,
            'duree_tour' => $this->duree_tour,
            'age_minimum' => $this->age_minimum,
            'taille_minimum' => $this->taille_minimum,
            'statut' => $this->statut,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}