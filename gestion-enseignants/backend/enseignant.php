<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true);
switch ($method) {
    //liste des enseignants avec calcul de la prestation (nombre_heures * taux_horaire)
    case 'GET':
        $result = $conn->query("SELECT *, (nombre_heures * taux_horaire) AS prestation FROM enseignant");
        $data = $result->fetch_all(MYSQLI_ASSOC);
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
    /*bind_param("types", valeurs)
        Le premier argument indique le type de chaque valeur :
        s  →  string  (texte)
        d  →  double  (nombre décimal)
        i  →  integer (nombre entier)*/
    case 'POST':
        $stmt = $conn->prepare("INSERT INTO enseignant (matricule, nom, nombre_heures, taux_horaire) VALUES (?,?,?,?)");
        $stmt->bind_param("ssdd", 
            $body['matricule'], 
            $body['nom'], 
            $body['nombre_heures'], 
            $body['taux_horaire']);
        $ok = $stmt->execute();
        echo json_encode(["message" => $ok ? "Insertion réussie" : "Insertion échouée"]);
        break;

    // modification d'un enseignant avec les champs : id, nom, nombre_heures, taux_horaire
    case 'PUT':
        $stmt = $conn->prepare("UPDATE enseignant SET matricule=?, nom=?, nombre_heures=?, taux_horaire=? WHERE id=?");
        $stmt->bind_param("ssddi", 
            $body['matricule'], 
            $body['nom'],  
            $body['nombre_heures'], 
            $body['taux_horaire'], 
            $body['id']);
        $ok = $stmt->execute();
        echo json_encode(["message" => $ok ? "Modification réussie" : "Modification échouée"]);
        break;

    // suppression d'un enseignant avec le champ : id
    case 'DELETE':
        $stmt = $conn->prepare("DELETE FROM enseignant WHERE id=?");
        $stmt->bind_param("i", $body['id']);
        $ok = $stmt->execute();
        echo json_encode(["message" => $ok ? "Suppression réussie" : "Suppression échouée"]);
        break;
}
?>