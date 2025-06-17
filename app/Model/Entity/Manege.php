<?php
class Manege {
    protected ?int $id_manege;
    protected ?int $capacite;
    public ?string $nom_manege;
    const TABLE_NAME = 'attraction';
    
    public function __construct(
        ?int $id_manege = null,
        ?int $capacite = null,
        ?string $nom_manege = null,

    ){
         $this->id_manege = $id_manege;
        $this->capacite = $capacite;
        $this->nom_manege = $nom_manege;
    }

    // Getters
    public function getIdManege(): ?int {
        return $this->id_manege;
    }
    public function getCapacite(): ?int {
        return $this->capacite;
    }
    public function getNomManege(): ?string {
        return $this->nom_manege;
    }

    // Setters
    public function setIdManege(?int $id_manege): void {
        $this->id_manege = $id_manege;
    }
    public function setCapacite(?int $capacite): void {
        $this->capacite = $capacite;
    }
    public function setNomManege(?string $nom_manege): void {
        $this->nom_manege = $nom_manege;
    }
}