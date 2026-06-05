<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "gestion-enseignants");

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Erreur connexion: " . $conn->connect_error
    ]));
}

$conn->set_charset("utf8");
?>