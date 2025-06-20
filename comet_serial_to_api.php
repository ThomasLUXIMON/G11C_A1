<?php
// Script PHP : lit le port série USB et envoie les températures à l'API REST
// Nécessite l'extension PHP serial (php_serial.class.php) ou l'utilisation de fopen sur COM

$serialPort = 'COM3'; // À adapter selon ton PC
$baudrate = 9600;
$apiUrl = 'http://localhost/G11C/G11C_A1/api/sensors/reading';

// Nombre de manèges à boucler (à adapter dynamiquement si besoin)
$nbManeges = 5; // À adapter selon ta base
$currentManegeId = 1;

$fp = fopen($serialPort, 'r');
if (!$fp) {
    die("Impossible d'ouvrir le port série $serialPort\n");
}

stream_set_blocking($fp, false);
echo "Lecture du port série $serialPort...\n";

while (true) {
    $line = fgets($fp);
    if ($line && strpos($line, 'TEMP:') === 0) {
        $line = trim($line);
        if (preg_match('/TEMP:([\d\.\-]+)/', $line, $matches)) {
            $temperature = floatval($matches[1]);
            $data = [
                'temperature' => $temperature,
                'manege_id' => $currentManegeId
            ];
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data),
                    'timeout' => 5
                ]
            ];
            $context  = stream_context_create($options);
            $result = @file_get_contents($apiUrl, false, $context);
            echo date('H:i:s') . " - Température {$temperature}°C envoyée pour manège #{$currentManegeId} : ".$result."\n";
            $currentManegeId++;
            if ($currentManegeId > $nbManeges) $currentManegeId = 1;
            sleep(2); // Envoi toutes les 2 secondes
        }
    }
    usleep(500000); // 0.5s
}
