<?php
// Point d'entrée HTTP de la "commande". transfère tout au contrôleur.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/CommandeController.php';

$pdo = getConnexion();
$controller = new CommandeController($pdo);
$controller->traiter($_SERVER['REQUEST_METHOD']);