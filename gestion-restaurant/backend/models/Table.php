<?php
// Model : toutes les requêtes SQL liées à la table table_ (les tables physiques du restaurant)

class Table {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM table_ ORDER BY idtable DESC");
        return $stmt->fetchAll();
    }

    public function getById($idtable) {
        $stmt = $this->pdo->prepare("SELECT * FROM table_ WHERE idtable = :idtable");
        $stmt->execute(['idtable' => $idtable]);
        $table = $stmt->fetch();
        return $table ?: null;
    }

    // Filtre par occupation : 0 = libre, 1 = occupée
    public function getByOccupation($occupation) {
        $stmt = $this->pdo->prepare("SELECT * FROM table_ WHERE occupation = :occupation ORDER BY idtable");
        $stmt->execute(['occupation' => $occupation]);
        return $stmt->fetchAll();
    }

    public function create($idtable, $designation, $occupation) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO table_ (idtable, designation, occupation) VALUES (:idtable, :designation, :occupation)"
        );
        return $stmt->execute([
            'idtable'     => $idtable,
            'designation' => $designation,
            'occupation'  => $occupation,
        ]);
    }

    public function update($idtable, $designation, $occupation) {
        $stmt = $this->pdo->prepare(
            "UPDATE table_ SET designation = :designation, occupation = :occupation WHERE idtable = :idtable"
        );
        $stmt->execute([
            'designation' => $designation,
            'occupation'  => $occupation,
            'idtable'     => $idtable,
        ]);
        return $stmt->rowCount();
    }

    public function delete($idtable) {
        $stmt = $this->pdo->prepare("DELETE FROM table_ WHERE idtable = :idtable");
        $stmt->execute(['idtable' => $idtable]);
        return $stmt->rowCount();
    }

    public function changerOccupation($idtable, $occupation) {
        $stmt = $this->pdo->prepare(
            "UPDATE table_ SET occupation = :occupation WHERE idtable = :idtable"
        );
        $stmt->execute(['occupation' => $occupation, 'idtable' => $idtable]);
        return $stmt->rowCount();
    }
}