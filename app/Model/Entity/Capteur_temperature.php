<?php
/**
 * Classe Capteur_temperature
 * Hérite de la classe abstraite Capteur
 * Gère spécifiquement les capteurs de température des manèges
 */

require_once __DIR__ . '/Capteur.php';

class Capteur_temperature extends Capteur {
    
    // Propriétés spécifiques aux capteurs de température
    private ?float $temperature;
    private ?string $unite = '°C';
    private ?DateTime $derniereMesure;
    private ?string $etat = 'inactif';
    private ?DateTime $created_at = null;
    private ?DateTime $updated_at = null;
    
    /**
     * Constructeur de la classe Capteur_temperature
     */
    public function __construct(
        ?int $id_capteur = null,
        ?int $id_manege = null,
        ?string $informations = "Capteur de temperature",
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
        $this->created_at = new DateTime();
        $this->updated_at = new DateTime();
    }
    
    // ===== GETTERS SPÉCIFIQUES =====
    
    public function getTemperature(): ?float {
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
    
    public function getCreatedAt(): ?DateTime {
        return $this->created_at;
    }
    
    public function getUpdatedAt(): ?DateTime {
        return $this->updated_at;
    }
    
    // ===== SETTERS SPÉCIFIQUES =====
    
    public function setId_Capteur(?int $id_capteur): void {
        $this->id_capteur = $id_capteur;
    }
    
    public function setTemperature(?float $temperature): void {
        $this->temperature = $temperature;
        $this->updated_at = new DateTime();
    }
    
    public function setUnite(?string $unite): void {
        $this->unite = $unite;
    }
    
    public function setDerniereMesure(?DateTime $derniereMesure): void {
        $this->derniereMesure = $derniereMesure;
        $this->updated_at = new DateTime();
    }
    
    public function setEtat(?string $etat): void {
        if (in_array($etat, ['actif', 'inactif', 'alerte', 'erreur'])) {
            $this->etat = $etat;
            $this->updated_at = new DateTime();
        }
    }
    
    // ===== MÉTHODES SPÉCIFIQUES =====
    
    /**
     * Met à jour la température mesurée par le capteur
     */
    public function mettreAJourTemperature(float $nouvelleTemperature): void {
        $this->temperature = $nouvelleTemperature;
        $this->derniereMesure = new DateTime();
        $this->updated_at = new DateTime();
        
        // Mettre à jour l'état selon la température
        $this->evaluerEtat();
    }
    
    /**
     * Évalue et met à jour l'état du capteur selon la température
     */
    private function evaluerEtat(): void {
        if ($this->temperature === null) {
            $this->etat = 'erreur';
        } elseif ($this->temperature < 5 || $this->temperature > 40) {
            $this->etat = 'alerte';
        } elseif ($this->temperature > 50) {
            $this->etat = 'erreur';
        } else {
            $this->etat = 'actif';
        }
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
     * Vérifie si le capteur nécessite une attention
     */
    public function necessite_attention(): bool {
        return in_array($this->etat, ['alerte', 'erreur']);
    }
    
    /**
     * Obtient le niveau de criticité (pour les alertes)
     */
    public function getNiveauCriticite(): string {
        if ($this->temperature === null) {
            return 'erreur';
        }
        
        if ($this->temperature < 5) {
            return 'warning';
        } elseif ($this->temperature > 40 && $this->temperature <= 50) {
            return 'danger';
        } elseif ($this->temperature > 50) {
            return 'critique';
        }
        
        return 'normal';
    }
    
    /**
     * Génère un rapport de température
     */
    public function genererRapport(): array {
        return [
            'id_capteur' => $this->id_capteur,
            'id_manege' => $this->id_manege,
            'temperature_actuelle' => $this->getTemperatureFormatee(),
            'temperature_brute' => $this->temperature,
            'etat' => $this->etat,
            'niveau_criticite' => $this->getNiveauCriticite(),
            'derniere_mesure' => $this->derniereMesure ? $this->derniereMesure->format('Y-m-d H:i:s') : null,
            'fonctionnel' => $this->estFonctionnel(),
            'necessite_attention' => $this->necessite_attention()
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
            'etat' => $this->etat,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null
        ];
    }
    
    /**
     * Crée une instance à partir d'un tableau de données (depuis la BDD)
     */
    public static function fromArray(array $data): self {
        $derniereMesure = null;
        if (isset($data['derniere_mesure']) && $data['derniere_mesure']) {
            $derniereMesure = new DateTime($data['derniere_mesure']);
        }
        $instance = new self(
            $data['id_capteur'] ?? null,
            $data['id_manege'] ?? null,
            $data['informations'] ?? "Capteur de temperature",
            $data['temperature'] ?? null,
            $data['unite'] ?? '°C',
            $derniereMesure,
            $data['etat'] ?? 'actif'
        );
        
        // Restaurer les timestamps
        if (isset($data['created_at']) && $data['created_at']) {
            $instance->created_at = new DateTime($data['created_at']);
        }
        if (isset($data['updated_at']) && $data['updated_at']) {
            $instance->updated_at = new DateTime($data['updated_at']);
        }
        
        return $instance;
    }
    
    /**
     * Convertit en format JSON pour l'API
     */
    public function toJson(): array {
        return [
            'id' => $this->id_capteur,
            'manege_id' => $this->id_manege,
            'temperature' => $this->temperature,
            'unite' => $this->unite,
            'etat' => $this->etat,
            'niveau_criticite' => $this->getNiveauCriticite(),
            'temperature_formatee' => $this->getTemperatureFormatee(),
            'timestamp_mesure' => $this->derniereMesure ? $this->derniereMesure->format('c') : null,
            'derniere_mise_a_jour' => $this->updated_at ? $this->updated_at->format('c') : null
        ];
    }
}