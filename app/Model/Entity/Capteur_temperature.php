<?php
/**
 * Classe Capteur_temperature
 * Hérite de la classe abstraite Capteur
 * Gère spécifiquement les capteurs de température des manèges
 */
class Capteur_temperature extends Capteur {
    
    // Propriétés spécifiques aux capteurs de température
    private ?float $temperatureMin;
    private ?float $temperatureMax;
    private ?string $unite = '°C';
    private ?DateTime $derniereMesure;
    private ?string $etat = 'inactif'; // True = actif, False = inactif à modif
    
    /**
     * Constructeur de la classe Capteur_temperature
     */
    public function __construct(
        ?int $id_capteur = null,
        ?int $id_manege = null,
        ?float $informations = null,
        ?float $temperatureMin = -10.0,
        ?float $temperatureMax = 60.0,
        ?string $unite = '°C',
        ?DateTime $derniereMesure = null,
        ?string $etat = 'actif'
    ) {
        // Appel du constructeur parent
        $this->id_capteur = $id_capteur;
        $this->id_manege = $id_manege;
        $this->informations = $informations;
        
        // Initialisation des propriétés spécifiques
        $this->temperatureMin = $temperatureMin;
        $this->temperatureMax = $temperatureMax;
        $this->unite = $unite;
        $this->derniereMesure = $derniereMesure ?? new DateTime();
        $this->etat = $etat;
    }
    
    // ===== GETTERS SPÉCIFIQUES =====
    
    public function getTemperatureMin(): ?float {
        return $this->temperatureMin;
    }
    
    public function getTemperatureMax(): ?float {
        return $this->temperatureMax;
    }
    
    public function getUnite(): ?string {
        return $this->unite;
    }
    
    public function getDerniereMesure(): ?DateTime {
        return $this->derniereMesure;
    }
    
    public function getEtat(): ?string {
        return $this->etat;
    }
    
    // ===== SETTERS SPÉCIFIQUES =====
    
    public function setTemperatureMin(?float $temperatureMin): void {
        $this->temperatureMin = $temperatureMin;
    }
    
    public function setTemperatureMax(?float $temperatureMax): void {
        $this->temperatureMax = $temperatureMax;
    }
    
    public function setUnite(?string $unite): void {
        $this->unite = $unite;
    }
    
    public function setDerniereMesure(?DateTime $derniereMesure): void {
        $this->derniereMesure = $derniereMesure;
    }
    
    public function setEtat(?string $etat): void {
        $this->etat = $etat;
    }
    
    // ===== MÉTHODES SPÉCIFIQUES =====
    
    /**
     * Met à jour la température mesurée par le capteur
     */
    public function mettreAJourTemperature(float $nouvelleTemperature): void {
        $this->informations = $nouvelleTemperature;
        $this->derniereMesure = new DateTime();
        
        // Vérifier si la température est dans les limites acceptables
        $this->verifierLimites();
    }
    
    /**
     * Vérifie si la température est dans les limites acceptables
     */
    public function verifierLimites(): bool {
        if ($this->informations === null) {
            return false;
        }
        
        $temperatureOk = ($this->informations >= $this->temperatureMin && 
                         $this->informations <= $this->temperatureMax);
        
        if (!$temperatureOk) {
            $this->etat = 'alerte';
        } else if ($this->etat === 'alerte') {
            $this->etat = 'actif';
        }
        
        return $temperatureOk;
    }
    
    /**
     * Retourne la température formatée avec son unité
     */
    public function getTemperatureFormatee(): string {
        if ($this->informations === null) {
            return 'N/A';
        }
        
        return number_format($this->informations, 1) . ' ' . $this->unite;
    }
    
    /**
     * Vérifie si le capteur est en état de fonctionnement normal
     */
    public function estFonctionnel(): bool {
        return in_array($this->etat, ['actif', 'alerte']);
    }
    
    /**
     * Génère un rapport de température
     */
    public function genererRapport(): array {
        return [
            'id_capteur' => $this->id_capteur,
            'id_manege' => $this->id_manege,
            'temperature_actuelle' => $this->getTemperatureFormatee(),
            'temperature_min_autorisee' => $this->temperatureMin . ' ' . $this->unite,
            'temperature_max_autorisee' => $this->temperatureMax . ' ' . $this->unite,
            'etat' => $this->etat,
            'derniere_mesure' => $this->derniereMesure ? $this->derniereMesure->format('Y-m-d H:i:s') : null,
            'limites_respectees' => $this->verifierLimites(),
            'fonctionnel' => $this->estFonctionnel()
        ];
    }
    
    /**
     * Convertit l'objet en tableau pour la sauvegarde en base de données
     */
    public function toArray(): array {
        return [
            'id_capteur' => $this->id_capteur,
            'id_manege' => $this->id_manege,
            'informations' => $this->informations,
            'temperature_min' => $this->temperatureMin,
            'temperature_max' => $this->temperatureMax,
            'unite' => $this->unite,
            'derniere_mesure' => $this->derniereMesure ? $this->derniereMesure->format('Y-m-d H:i:s') : null,
            'etat' => $this->etat
        ];
    }
    
    /**
     * Crée une instance à partir d'un tableau de données
     */
    public static function fromArray(array $data): self {
        $derniereMesure = null;
        if (isset($data['derniere_mesure']) && $data['derniere_mesure']) {
            $derniereMesure = new DateTime($data['derniere_mesure']);
        }
        
        return new self(
            $data['id_capteur'] ?? null,
            $data['id_manege'] ?? null,
            $data['informations'] ?? null,
            $data['temperature_min'] ?? -10.0,
            $data['temperature_max'] ?? 60.0,
            $data['unite'] ?? '°C',
            $derniereMesure,
            $data['etat'] ?? 'actif'
        );
    }
}