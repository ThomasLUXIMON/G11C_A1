<?php
require_once __DIR__ . '/../app/Model/Manager/CapteurTemperatureManager.php';

$capteurManager = new CapteurTemperatureManager();

// Récupérer le nombre total de manèges (à adapter si besoin)
require_once __DIR__ . '/../app/Model/Manager/ManegeManager.php';
$manegeManager = new ManegeManager();
$maneges = $manegeManager->findAllWithStatus();
$nbManeges = count($maneges);

if ($nbManeges === 0) {
    echo "Aucun manège trouvé.\n";
    exit(1);
}

$currentId = 1;
while (true) {
    // Générer une température aléatoire (à remplacer par la vraie lecture capteur)
    $temperature = rand(18, 35) + (mt_rand(0, 9) / 10);
    $capteurManager->createReading($temperature, $currentId);
    echo "Température $temperature°C insérée pour manège #$currentId\n";
    $currentId++;
    if ($currentId > $nbManeges) {
        $currentId = 1;
    }
    sleep(10);
}
