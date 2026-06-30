<?php
// Point d'entrée HTTP pour la ressource "reserver". transfère tout au contrôleur

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ReservationController.php';

$pdo = getConnexion();
$controller = new ReservationController($pdo);
$controller->traiter($_SERVER['REQUEST_METHOD']);