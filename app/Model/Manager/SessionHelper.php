<?php
// Ajout d'une méthode utilitaire pour démarrer une session et activer le manège
require_once __DIR__ . '/ManegeManager.php';

class SessionHelper {
    public static function startSessionAndActivateManege($manegeId) {
        $manegeManager = new ManegeManager();
        // Mettre le manège en actif
        $manegeManager->updateStatus($manegeId, 'actif');
        // Ici, tu peux aussi créer une session si besoin
        // ...
    }
}
