<?php
// Point d'entrée HTTP de "menu"
// Ne contient aucune logique métier ni SQL : il connecte la BD et transfère tout au controller

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/MenuController.php';

$pdo = getConnexion();
$controller = new MenuController($pdo);
$controller->traiter($_SERVER['REQUEST_METHOD']);