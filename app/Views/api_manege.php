<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'capteurs_temperature';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT * FROM maneges WHERE id = ?");
                $stmt->execute([$id]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->query("SELECT * FROM maneges ORDER BY created_at DESC");
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input && isset($input['nom'], $input['type'], $input['capacite_max'], $input['duree_tour'], $input['statut'])) {
                $stmt = $pdo->prepare("
                    INSERT INTO maneges (nom, type, capacite_max, duree_tour, age_minimum, taille_minimum, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['nom'],
                    $input['type'],
                    $input['capacite_max'],
                    $input['duree_tour'],
                    $input['age_minimum'] ?? 0,
                    $input['taille_minimum'] ?? 0,
                    $input['statut']
                ]);
                echo json_encode(['success' => true, 'message' => 'Manège ajouté']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Données invalides']);
            }
            break;

        case 'PUT':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $input = json_decode(file_get_contents('php://input'), true);
                if ($input && isset($input['nom'], $input['type'], $input['capacite_max'], $input['duree_tour'], $input['statut'])) {
                    $stmt = $pdo->prepare("
                        UPDATE maneges
                        SET nom = ?, type = ?, capacite_max = ?, duree_tour = ?, age_minimum = ?, taille_minimum = ?, statut = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $input['nom'],
                        $input['type'],
                        $input['capacite_max'],
                        $input['duree_tour'],
                        $input['age_minimum'] ?? 0,
                        $input['taille_minimum'] ?? 0,
                        $input['statut'],
                        $id
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Manège modifié']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Données invalides']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("DELETE FROM maneges WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Manège supprimé']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
            }
            break;

        case 'OPTIONS':
            http_response_code(200);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données : ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur système : ' . $e->getMessage()]);
}
?>