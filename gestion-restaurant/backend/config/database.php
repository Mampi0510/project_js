<?php
// config/database.php
// Connexion PDO centralisée vers la base gestion_restaurant
// Les Models appellent getConnexion() pour récupérer un objet PDO prêt à l'emploi

function getConnexion() {
    $host = '127.0.0.1';
    $dbname = 'gestion_restaurant';
    $user = 'root';
    $password = ''; // vide sous XAMPP

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json'); //indiquer au navigateur que la réponse est du JSON
        echo json_encode(['erreur' => 'Connexion à la base de données impossible']);
        exit;
    }
}