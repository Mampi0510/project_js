<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true);
switch ($method) {
    //liste des enseignants avec calcul de la prestation (nombre_heures * taux_horaire)
    case 'GET':
        $stmt = $pdo->query("SELECT *, (nombre_heures * taux_horaire) AS prestation FROM enseignant");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //convertir les champs taux_horaire, nombre_heures et prestation en float pour éviter les problèmes de type lors de l'encodage JSON
        $data = array_map(function($row) {
            $row['taux_horaire'] = (float) $row['taux_horaire'];
            $row['nombre_heures'] = (float) $row['nombre_heures'];
            $row['prestation'] = (float) $row['prestation'];
        return $row;
    }, $data);        
        echo json_encode($data);
        break;

    // insertion d'un enseignant avec les champs : matricule, nom, nombre_heures, taux_horaire
    case 'POST':
        $stmt = $pdo->prepare("INSERT INTO enseignant (matricule, nom, nombre_heures, taux_horaire) VALUES (?,?,?,?)");
        $ok = $stmt->execute([
            $body['matricule'],
            $body['nom'],
            $body['nombre_heures'],
            $body['taux_horaire']
        ]);
        echo json_encode(["message" => $ok ? "Insertion réussie" : "Insertion échouée"]);
        break;

    // modification d'un enseignant avec les champs : id, nom, nombre_heures, taux_horaire
    case 'PUT':
        $stmt = $pdo->prepare("UPDATE enseignant SET matricule=?, nom=?, nombre_heures=?, taux_horaire=? WHERE id=?");
        $ok = $stmt->execute([
            $body['matricule'], 
            $body['nom'],
            $body['nombre_heures'],
            $body['taux_horaire'],
            $body['id']
        ]);
        echo json_encode(["message" => $ok ? "Modification réussie" : "Modification échouée"]);
        break;

    // suppression d'un enseignant avec le champ : id
    case 'DELETE':
        $stmt = $pdo->prepare("DELETE FROM enseignant WHERE id=?");
        $ok = $stmt->execute([$body['id']]);
        echo json_encode(["message" => $ok ? "Suppression réussie" : "Suppression échouée"]);
        break;
}
?>