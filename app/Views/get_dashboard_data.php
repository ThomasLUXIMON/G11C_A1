<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'capteurs_temperature';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("
            SELECT m.id, m.nom, m.type, m.capacite_max, m.statut, m.created_at, m.updated_at, 
                   d.nb_passagers, d.temperature
            FROM maneges m
            LEFT JOIN donnees_manege d ON m.id = d.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $maneges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
            SELECT m.id, m.nom, m.type, m.capacite_max, m.statut, m.created_at, m.updated_at, 
                   d.nb_passagers, d.temperature
            FROM maneges m
            LEFT JOIN donnees_manege d ON m.id = d.id
            ORDER BY m.created_at DESC
        ");
        $maneges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $formatted_maneges = [];
    foreach ($maneges as $manege) {
        $formatted_maneges[] = [
            'id' => (int)$manege['id'],
            'nom_manege' => (string)$manege['nom'],
            'type' => (string)$manege['type'],
            'capacite_max' => (int)$manege['capacite_max'],
            'statut' => (string)$manege['statut'],
            'nb_passagers' => (int)$manege['nb_passagers'] ?? 0,
            'temperature' => (float)$manege['temperature'] ?? 0.0,
            'created_at' => $manege['created_at'],
            'updated_at' => $manege['updated_at']
        ];
    }

    echo json_encode([
        'success' => true, 
        'data' => $formatted_maneges,
        'count' => count($formatted_maneges)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur de base de données: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur système: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>