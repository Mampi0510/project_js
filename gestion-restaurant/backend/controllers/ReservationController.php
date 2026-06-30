<?php
// Controller pour la ressource "reserver". Applique la règle métier de non-chevauchement
// avant toute création ou modification

require_once __DIR__ . '/../models/Reservation.php';

class ReservationController {
    private $reservation;

    public function __construct($pdo) {
        $this->reservation = new Reservation($pdo);
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
        if (isset($_GET['idreserv'])) {
            $resa = $this->reservation->getById($_GET['idreserv']);
            if ($resa === null) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Réservation introuvable']);
                return;
            }
            echo json_encode($resa);
            return;
        }

        if (isset($_GET['recherche']) && $_GET['recherche'] !== '') {
            echo json_encode($this->reservation->searchByClient($_GET['recherche']));
            return;
        }

        echo json_encode($this->reservation->getAll());
    }

    // POST : créer une réservation, en bloquant tout chevauchement de créneau
    private function creer() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idreserv']) || empty($donnees['idtable']) ||
            empty($donnees['date_de_reserv']) || empty($donnees['date_reserve']) ||
            empty($donnees['nomcli'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champs requis : idreserv, idtable, date_de_reserv, date_reserve, nomcli']);
            return;
        }

        // Cohérence des dates : le début doit être avant la fin
        if (strtotime($donnees['date_de_reserv']) >= strtotime($donnees['date_reserve'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'date_de_reserv doit être antérieure à date_reserve']);
            return;
        }

        // Règle métier centrale : on cherche un chevauchement AVANT d'insérer
        $conflits = $this->reservation->trouverChevauchements(
            $donnees['idtable'],
            $donnees['date_de_reserv'],
            $donnees['date_reserve']
        );

        if (count($conflits) > 0) {
            http_response_code(409);
            echo json_encode([
                'erreur' => 'Cette table est déjà réservée sur ce créneau',
                'conflits' => $conflits,
            ]);
            return;
        }

        try {
            $this->reservation->create(
                $donnees['idreserv'],
                $donnees['idtable'],
                $donnees['date_de_reserv'],
                $donnees['date_reserve'],
                $donnees['nomcli']
            );

            http_response_code(201);
            echo json_encode(['message' => 'Réservation créée avec succès', 'idreserv' => $donnees['idreserv']]);

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                http_response_code(409);
                echo json_encode(['erreur' => 'Ce code réservation existe déjà']);
            } elseif ($e->errorInfo[1] == 1452) {
                http_response_code(400);
                echo json_encode(['erreur' => 'idtable invalide (table inexistante)']);
            } else {
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur lors de la création de la réservation']);
            }
        }
    }

    // PUT : modifier une réservation, en réappliquant la vérification de chevauchement
    private function modifier() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idreserv'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idreserv requis']);
            return;
        }

        $resaExistante = $this->reservation->getById($donnees['idreserv']);
        if ($resaExistante === null) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Réservation introuvable']);
            return;
        }

        $idtable       = $donnees['idtable']        ?? $resaExistante['idtable'];
        $dateDeReserv  = $donnees['date_de_reserv'] ?? $resaExistante['date_de_reserv'];
        $dateReserve   = $donnees['date_reserve']   ?? $resaExistante['date_reserve'];
        $nomcli        = $donnees['nomcli']         ?? $resaExistante['nomcli'];

        if (strtotime($dateDeReserv) >= strtotime($dateReserve)) {
            http_response_code(400);
            echo json_encode(['erreur' => 'date_de_reserv doit être antérieure à date_reserve']);
            return;
        }

        // On exclut la réservation elle-même de la recherche de conflits
        $conflits = $this->reservation->trouverChevauchements(
            $idtable, $dateDeReserv, $dateReserve, $donnees['idreserv']
        );

        if (count($conflits) > 0) {
            http_response_code(409);
            echo json_encode([
                'erreur' => 'Cette table est déjà réservée sur ce créneau',
                'conflits' => $conflits,
            ]);
            return;
        }

        $this->reservation->update($donnees['idreserv'], $idtable, $dateDeReserv, $dateReserve, $nomcli);
        echo json_encode(['message' => 'Réservation modifiée avec succès']);
    }

    private function supprimer() {
        if (empty($_GET['idreserv'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idreserv requis']);
            return;
        }

        $lignesSupprimees = $this->reservation->delete($_GET['idreserv']);

        if ($lignesSupprimees === 0) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Réservation introuvable']);
            return;
        }

        echo json_encode(['message' => 'Réservation supprimée avec succès']);
    }
}