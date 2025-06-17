<?php
Class Capteur {
    private ?int $id_capteur;
    private ?int $id_manege;
    private ?float $temperature;
    private ?int $segment_7;
    private ?bool $lumiere_photo;
    const Table_name = "Infos_capteurs";
// Getters
    public function getId_Capteur(): ?int { return $this->id_capteur; }
    public function getId_Manege(): ?int { return $this->id_manege; }
    public function getAll(): ?array { return [
            'id_capteur' => $this->id_capteur,
            'id_manege' => $this->id_manege,
            'temperature' => $this->temperature,
            'segment_7' => $this->segment_7,
            'lumiere_photo' => $this->lumiere_photo
        ];
    }
}