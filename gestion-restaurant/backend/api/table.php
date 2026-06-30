<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/TableController.php';

$pdo = getConnexion();
$controller = new TableController($pdo);
$controller->traiter($_SERVER['REQUEST_METHOD']);