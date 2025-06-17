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
    
    // Setters
}