<?php
// Point d'entrée HTTP pour les statistiques (lecture seule). Transfère au contrôleur

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/StatistiqueController.php';

$pdo = getConnexion();
$controller = new StatistiqueController($pdo);
$controller->traiter($_SERVER['REQUEST_METHOD']);