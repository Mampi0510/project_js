<?php
// Point d'entrée HTTP pour générer le PDF de l'addition d'une commande
// Contrairement aux autres points d'entrée, celui-ci ne renvoie pas du JSON mais un PDF

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AdditionController.php';

$pdo = getConnexion();
$controller = new AdditionController($pdo);
$controller->traiter($_SERVER['REQUEST_METHOD']);