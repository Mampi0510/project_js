<?php
// Controller pour la ressource "table" (les tables physiques du restaurant)

require_once __DIR__ . '/../models/Table.php';

class TableController {
    private $table;

    public function __construct($pdo) {
        $this->table = new Table($pdo);
    }

    public function traiter($methode) {
        switch ($methode) {
            case 'GET':
                $this->afficher();
                break;
            case 'POST':
                $this->creer();
                break;
            case 'PUT':
                $this->modifier();
                break;
            case 'DELETE':
                $this->supprimer();
                break;
            case 'OPTIONS':
                http_response_code(200);
                break;
            default:
                http_response_code(405);
                echo json_encode(['erreur' => 'Méthode non autorisée']);
        }
    }

    private function afficher() {
        if (isset($_GET['occupation']) && $_GET['occupation'] !== '') {
            echo json_encode($this->table->getByOccupation($_GET['occupation']));
            return;
        }

        if (isset($_GET['idtable'])) {
            $table = $this->table->getById($_GET['idtable']);
            if ($table === null) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Table introuvable']);
                return;
            }
            echo json_encode($table);
            return;
        }

        echo json_encode($this->table->getAll());
    }

    private function creer() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idtable']) || empty($donnees['designation'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champs requis : idtable, designation']);
            return;
        }

        $occupation = isset($donnees['occupation']) ? (int)$donnees['occupation'] : 0;

        try {
            $this->table->create($donnees['idtable'], $donnees['designation'], $occupation);
            http_response_code(201);
            echo json_encode(['message' => 'Table créée avec succès', 'idtable' => $donnees['idtable']]);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                http_response_code(409);
                echo json_encode(['erreur' => 'Ce code table existe déjà']);
            } else {
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur lors de la création de la table']);
            }
        }
    }

    private function modifier() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idtable'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idtable requis']);
            return;
        }

        // On récupère l'existant pour permettre un PUT partiel
        // (ex: changer juste l'occupation sans renvoyer la désignation)
        $tableExistante = $this->table->getById($donnees['idtable']);
        if ($tableExistante === null) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Table introuvable']);
            return;
        }

        $designation = !empty($donnees['designation'])
            ? $donnees['designation']
            : $tableExistante['designation'];

        $occupation = isset($donnees['occupation'])
            ? (int)$donnees['occupation']
            : $tableExistante['occupation'];

        $this->table->update($donnees['idtable'], $designation, $occupation);
        echo json_encode(['message' => 'Table modifiée avec succès']);
    }

    private function supprimer() {
        if (empty($_GET['idtable'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idtable requis']);
            return;
        }

        try {
            $lignesSupprimees = $this->table->delete($_GET['idtable']);

            if ($lignesSupprimees === 0) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Table introuvable']);
                return;
            }

            echo json_encode(['message' => 'Table supprimée avec succès']);

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1451) {
                http_response_code(409);
                echo json_encode(['erreur' => 'Impossible de supprimer : cette table est utilisée dans des commandes ou réservations']);
            } else {
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur lors de la suppression']);
            }
        }
    }
}