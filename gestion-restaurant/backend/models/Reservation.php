<?php
// Model : toutes les requêtes SQL liées à la table reserver

class Reservation {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM reserver ORDER BY date_de_reserv DESC");
        return $stmt->fetchAll();
    }

    public function getById($idreserv) {
        $stmt = $this->pdo->prepare("SELECT * FROM reserver WHERE idreserv = :idreserv");
        $stmt->execute(['idreserv' => $idreserv]);
        $reservation = $stmt->fetch();
        return $reservation ?: null;
    }

    // Recherche par nom de client avec LIKE %...%
    public function searchByClient($terme) {
        $stmt = $this->pdo->prepare("SELECT * FROM reserver WHERE nomcli LIKE :terme ORDER BY date_de_reserv DESC");
        $stmt->execute(['terme' => '%' . $terme . '%']);
        return $stmt->fetchAll();
    }

    // Coeur de la règle métier : cherche les réservations existantes qui chevauchent
    // le créneau [debut, fin] demandé sur une table donnée.
    // $idreservAExclure permet d'ignorer la réservation elle-même lors d'une modification (PUT).
    public function trouverChevauchements($idtable, $debut, $fin, $idreservAExclure = null) {
        $sql = "SELECT * FROM reserver
                WHERE idtable = :idtable
                  AND date_de_reserv < :fin
                  AND date_reserve > :debut";

        $parametres = [
            'idtable' => $idtable,
            'fin'     => $fin,
            'debut'   => $debut,
        ];

        if ($idreservAExclure !== null) {
            $sql .= " AND idreserv != :idreserv_exclu";
            $parametres['idreserv_exclu'] = $idreservAExclure;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametres);
        return $stmt->fetchAll();
    }

    public function create($idreserv, $idtable, $dateDeReserv, $dateReserve, $nomcli) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO reserver (idreserv, idtable, date_de_reserv, date_reserve, nomcli)
             VALUES (:idreserv, :idtable, :date_de_reserv, :date_reserve, :nomcli)"
        );
        return $stmt->execute([
            'idreserv'       => $idreserv,
            'idtable'        => $idtable,
            'date_de_reserv' => $dateDeReserv,
            'date_reserve'   => $dateReserve,
            'nomcli'         => $nomcli,
        ]);
    }

    public function update($idreserv, $idtable, $dateDeReserv, $dateReserve, $nomcli) {
        $stmt = $this->pdo->prepare(
            "UPDATE reserver
             SET idtable = :idtable, date_de_reserv = :date_de_reserv,
                 date_reserve = :date_reserve, nomcli = :nomcli
             WHERE idreserv = :idreserv"
        );
        $stmt->execute([
            'idtable'        => $idtable,
            'date_de_reserv' => $dateDeReserv,
            'date_reserve'   => $dateReserve,
            'nomcli'         => $nomcli,
            'idreserv'       => $idreserv,
        ]);
        return $stmt->rowCount();
    }

    public function delete($idreserv) {
        $stmt = $this->pdo->prepare("DELETE FROM reserver WHERE idreserv = :idreserv");
        $stmt->execute(['idreserv' => $idreserv]);
        return $stmt->rowCount();
    }
}