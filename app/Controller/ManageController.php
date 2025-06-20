<?php
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
        // Permet de supporter application/json en plus de x-www-form-urlencoded
        if (empty($_POST)) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $_POST = $input;
            }
        }
        $nom = $_POST['nom_manege'] ?? $_POST['nom'] ?? null;
        $type = $_POST['type'] ?? null;
        $capacite = isset($_POST['capacite']) ? (int)$_POST['capacite'] : (isset($_POST['capacite_max']) ? (int)$_POST['capacite_max'] : 0);
        $statut = $_POST['statut'] ?? null;
        $duree_tour = isset($_POST['duree_tour']) ? (int)$_POST['duree_tour'] : 0;
        $age_minimum = isset($_POST['age_minimum']) ? (int)$_POST['age_minimum'] : 0;
        $taille_minimum = isset($_POST['taille_minimum']) ? (int)$_POST['taille_minimum'] : 0;
        if (!$nom || !$type || !$capacite || !$statut) {
            $this->json(['success' => false, 'message' => 'Champs obligatoires manquants'], 400);
            return;
        }
        $manege = new Manege(null, $nom, $type, $capacite, $duree_tour, $age_minimum, $taille_minimum, $statut);
        $manager = new ManegeManager();
        $ok = $manager->insert($manege);
        if ($ok) {
            $this->json(['success' => true, 'message' => 'Manège ajouté']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de l\'ajout'], 500);
        }
    }

    public function delete($id = null): void {
        $this->requireAuth();
        // Si l'id n'est pas passé en argument (cas GET/POST), on tente de le récupérer dans $_POST/$_GET
        if ($id === null) {
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
        }
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID manège manquant'], 400);
            return;
        }
        $manager = new ManegeManager();
        $ok = $manager->delete((int)$id);
        if ($ok) {
            $this->json(['success' => true, 'message' => 'Manège supprimé']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de la suppression'], 500);
        }
    }

    public function show($id): void {
        $this->requireAuth();
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID manège manquant'], 400);
            return;
        }
        $manager = new ManegeManager();
        $manege = $manager->findWithDetails((int)$id);
        if ($manege) {
            $data = [
                'id' => method_exists($manege, 'getId') ? $manege->getId() : ($manege['id'] ?? null),
                'nom' => method_exists($manege, 'getNom') ? $manege->getNom() : ($manege['nom'] ?? ''),
                'type' => method_exists($manege, 'getType') ? $manege->getType() : ($manege['type'] ?? ''),
                'capacite_max' => method_exists($manege, 'getCapaciteMax') ? $manege->getCapaciteMax() : ($manege['capacite_max'] ?? ''),
                'duree_tour' => method_exists($manege, 'getDureeTour') ? $manege->getDureeTour() : ($manege['duree_tour'] ?? ''),
                'age_minimum' => method_exists($manege, 'getAgeMinimum') ? $manege->getAgeMinimum() : ($manege['age_minimum'] ?? ''),
                'taille_minimum' => method_exists($manege, 'getTailleMinimum') ? $manege->getTailleMinimum() : ($manege['taille_minimum'] ?? ''),
                'statut' => method_exists($manege, 'getStatut') ? $manege->getStatut() : ($manege['statut'] ?? '')
            ];
            $this->json(['success' => true, 'data' => [$data]]);
        } else {
            $this->json(['success' => false, 'message' => 'Manège non trouvé'], 404);
        }
    }

    public function update($id): void {
        $this->requireAuth();
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID manège manquant'], 400);
            return;
        }
        // Support application/json et x-www-form-urlencoded
        $input = [];
        if (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
        } else {
            $input = $_POST;
        }
        $nom = $input['nom_manege'] ?? $input['nom'] ?? null;
        $type = $input['type'] ?? null;
        $capacite = isset($input['capacite']) ? (int)$input['capacite'] : (isset($input['capacite_max']) ? (int)$input['capacite_max'] : 0);
        $statut = $input['statut'] ?? null;
        $duree_tour = isset($input['duree_tour']) ? (int)$input['duree_tour'] : 0;
        $age_minimum = isset($input['age_minimum']) ? (int)$input['age_minimum'] : 0;
        $taille_minimum = isset($input['taille_minimum']) ? (int)$input['taille_minimum'] : 0;
        if (!$nom || !$type || !$capacite || !$statut) {
            $this->json(['success' => false, 'message' => 'Champs obligatoires manquants'], 400);
            return;
        }
        $data = [
            'nom' => $nom,
            'type' => $type,
            'capacite_max' => $capacite,
            'statut' => $statut,
            'duree_tour' => $duree_tour,
            'age_minimum' => $age_minimum,
            'taille_minimum' => $taille_minimum
        ];
        $manager = new ManegeManager();
        try {
            $ok = $manager->update((int)$id, $data);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            return;
        }
        if ($ok) {
            $this->json(['success' => true, 'message' => 'Manège modifié']);
        } else {
            $this->json(['success' => false, 'message' => 'Erreur lors de la modification'], 500);
        }
    }
}
