<?php
/* Controller : reçoit la requête HTTP, valide les données, appelle le Model Menu
 et renvoie une réponse JSON avec le bon code HTTP*/

require_once __DIR__ . '/../models/Menu.php';

class MenuController {
    private $menu;

    public function __construct($pdo) {
        $this->menu = new Menu($pdo);
    }

    // Point d'entrée : redirige vers la bonne méthode selon le verbe HTTP
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

    // GET : liste complète, recherche, ou fiche unique
    private function afficher() {
        if (isset($_GET['recherche']) && $_GET['recherche'] !== '') {
            echo json_encode($this->menu->search($_GET['recherche']));
            return;
        }

        if (isset($_GET['idplat'])) {
            $plat = $this->menu->getById($_GET['idplat']);
            if ($plat === null) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Plat introuvable']);
                return;
            }
            echo json_encode($plat);
            return;
        }

        echo json_encode($this->menu->getAll());
    }

    // POST : création d'un plat
    private function creer() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idplat']) || empty($donnees['nomplat']) || !isset($donnees['pu'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champs requis : idplat, nomplat, pu']);
            return;
        }

        if (!is_numeric($donnees['pu']) || $donnees['pu'] < 0) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Le prix unitaire doit être un nombre positif']);
            return;
        }

        try {
            $this->menu->create($donnees['idplat'], $donnees['nomplat'], $donnees['pu']);
            http_response_code(201);
            echo json_encode(['message' => 'Plat créé avec succès', 'idplat' => $donnees['idplat']]);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                http_response_code(409);
                echo json_encode(['erreur' => 'Ce code plat existe déjà']);
            } else {
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur lors de la création du plat']);
            }
        }
    }

    // PUT : modification d'un plat existant
    private function modifier() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idplat'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idplat requis']);
            return;
        }

        if (empty($donnees['nomplat']) || !isset($donnees['pu'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champs requis : nomplat, pu']);
            return;
        }

        if (!is_numeric($donnees['pu']) || $donnees['pu'] < 0) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Le prix unitaire doit être un nombre positif']);
            return;
        }

        $lignesModifiees = $this->menu->update($donnees['idplat'], $donnees['nomplat'], $donnees['pu']);

        if ($lignesModifiees === 0) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Plat introuvable']);
            return;
        }

        echo json_encode(['message' => 'Plat modifié avec succès']);
    }

    // DELETE : suppression d'un plat
    private function supprimer() {
        if (empty($_GET['idplat'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idplat requis']);
            return;
        }

        try {
            $lignesSupprimees = $this->menu->delete($_GET['idplat']);

            if ($lignesSupprimees === 0) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Plat introuvable']);
                return;
            }

            echo json_encode(['message' => 'Plat supprimé avec succès']);

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1451) {
                http_response_code(409);
                echo json_encode(['erreur' => 'Impossible de supprimer : ce plat est utilisé dans des commandes']);
            } else {
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur lors de la suppression']);
            }
        }
    }
}