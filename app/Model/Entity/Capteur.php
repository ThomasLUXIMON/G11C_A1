<?php
abstract Class Capteur {
    protected ?int $id_capteur;
    protected ?int $id_manege;
    protected ?string $informations;
// Getters
    public function getId_Capteur(): ?int { return $this->id_capteur; }
    public function getId_Manege(): ?int { return $this->id_manege; }
    public function getInformation(): ?string {return $this->informations; }
}