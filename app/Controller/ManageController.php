<?php
// ManageController.php : gestion CRUD des manèges via MVC
require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Model/Manager/ManegeManager.php';
require_once __DIR__ . '/../Model/Entity/Manege.php';

class ManageController extends BaseController {
    public function index(): void {
        $this->requireAuth();
        $manager = new ManegeManager();
        $maneges = $manager->findAllWithStatus(); // Correction : on utilise la bonne méthode
        // Conversion en tableau associatif pour le frontend (évite undefined)
        $data = array_map(function($manege) {
            return [
                'id' => method_exists($manege, 'getId') ? $manege->getId() : ($manege['id'] ?? null),
                'nom' => method_exists($manege, 'getNom') ? $manege->getNom() : ($manege['nom'] ?? ''),
                'type' => method_exists($manege, 'getType') ? $manege->getType() : ($manege['type'] ?? ''),
                'capacite_max' => method_exists($manege, 'getCapaciteMax') ? $manege->getCapaciteMax() : ($manege['capacite_max'] ?? ''),
                'statut' => method_exists($manege, 'getStatut') ? $manege->getStatut() : ($manege['statut'] ?? '')
            ];
        }, $maneges);
        $this->json(['success' => true, 'data' => $data]);
    }

    public function store(): void {
        $this->requireAuth();
        $nom = $_POST['nom_manege'] ?? $_POST['nom'] ?? null;
        $type = $_POST['type'] ?? null;
        $capacite = $_POST['capacite'] ?? $_POST['capacite_max'] ?? null;
        $statut = $_POST['statut'] ?? null;
        // Champs optionnels
        $duree_tour = $_POST['duree_tour'] ?? null;
        $age_minimum = $_POST['age_minimum'] ?? null;
        $taille_minimum = $_POST['taille_minimum'] ?? null;
        if (!$nom || !$type || !$capacite || !$statut) {
            $this->json(['success' => false, 'message' => 'Champs obligatoires manquants'], 400);
            return;
        }
        $manege = new Manege(null, $nom, $type, $capacite, $statut, $duree_tour, $age_minimum, $taille_minimum);
        $manager = new ManegeManager();
        $ok = $manager->insert($manege);
        if ($ok) {
            $this->json(['success' => true, 'message' => 'Manège ajouté']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'ajout'], 500);
        }
    }
    // (Tu peux ajouter show, update, delete ici si besoin)
}
