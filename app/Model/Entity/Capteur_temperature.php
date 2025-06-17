<?php
/**
 * Classe Capteur_temperature
 * Hérite de la classe abstraite Capteur
 * Gère spécifiquement les capteurs de température des manèges
 */
class Capteur_temperature extends Capteur {
    
    // Propriétés spécifiques aux capteurs de température
    private ?float $temperature;
    private ?string $unite = '°C';
    private ?DateTime $derniereMesure;
    private ?string $etat = 'inactif'; // True = actif, False = inactif à modif
    
    /**
     * Constructeur de la classe Capteur_temperature
     */
    public function __construct(
        ?int $id_capteur = null,
        ?int $id_manege = null,
        ?string $informations = "Capteur de temperature:",
        ?float $temperature = null,
        ?string $unite = '°C',
        ?DateTime $derniereMesure = null,
        ?string $etat = 'actif'
    ) {
        // Initialisation des propriétés
        $this->id_capteur = $id_capteur;
        $this->id_manege = $id_manege;
        $this->informations = $informations;
        $this->temperature = $temperature;
        $this->unite = $unite;
        $this->derniereMesure = $derniereMesure ?? new DateTime();
        $this->etat = $etat;
    }
    
    // ===== GETTERS SPÉCIFIQUES =====
    
    public function gettemperature(): ?float {
        return $this->temperature;
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
    
    public function settemperature(?float $temperature): void {
        $this->temperature = $temperature;
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
        $this->temperature = $nouvelleTemperature;
        $this->derniereMesure = new DateTime();
        // Vérifier si la température est dans les limites acceptables
        // (ajoutez ici une logique si besoin)
    }
    
    /**
     * Retourne la température formatée avec son unité
     */
    public function getTemperatureFormatee(): string {
        if ($this->temperature === null) {
            return 'N/A';
        }
        return number_format($this->temperature, 1) . ' ' . $this->unite;
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
            'etat' => $this->etat,
            'derniere_mesure' => $this->derniereMesure ? $this->derniereMesure->format('Y-m-d H:i:s') : null,
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
            'temperature' => $this->temperature,
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
            $data['temperature'] ?? null,
            $data['unite'] ?? '°C',
            $derniereMesure,
            $data['etat'] ?? 'actif'
        );
    }
}