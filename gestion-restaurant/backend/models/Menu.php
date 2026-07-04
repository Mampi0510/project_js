<?php
// Model : toutes les requêtes SQL liées à la table menu
// Aucune notion de HTTP ici, juste des méthodes qui parlent à la base de données

class Menu {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Retourne tous les plats, triés par idplat
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM menu ORDER BY idplat DESC");
        return $stmt->fetchAll();
    }

    // Retourne un seul plat par son id, ou null si introuvable
    public function getById($idplat) {
        $stmt = $this->pdo->prepare("SELECT * FROM menu WHERE idplat = :idplat");
        $stmt->execute(['idplat' => $idplat]);
        $plat = $stmt->fetch();
        return $plat ?: null;
    }

    // Recherche par nom de plat avec LIKE %...%
    public function search($terme) {
        $stmt = $this->pdo->prepare("SELECT * FROM menu WHERE nomplat LIKE :terme ORDER BY idplat DESC");
        $stmt->execute(['terme' => '%' . $terme . '%']);
        return $stmt->fetchAll();
    }

    // Crée un nouveau plat. Retourne true si succès.
    // Lance une PDOException en cas d'erreur SQL (ex: doublon), que le contrôleur attrapera.
    public function create($idplat, $nomplat, $pu) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO menu (idplat, nomplat, pu) VALUES (:idplat, :nomplat, :pu)"
        );
        return $stmt->execute([
            'idplat'  => $idplat,
            'nomplat' => $nomplat,
            'pu'      => $pu,
        ]);
    }

    // Modifie un plat existant
    public function update($idplat, $nomplat, $pu) {
        $stmt = $this->pdo->prepare(
            "UPDATE menu SET nomplat = :nomplat, pu = :pu WHERE idplat = :idplat"
        );
        $stmt->execute([
            'nomplat' => $nomplat,
            'pu'      => $pu,
            'idplat'  => $idplat,
        ]);
        return $stmt->rowCount(); // nombre de lignes modifiées (0 si idplat inexistant)
    }

    // Supprime un plat. Retourne le nombre de lignes supprimées (0 si introuvable).
    // Peut lancer une PDOException si le plat est référencé dans ligne_commande (FK).
    public function delete($idplat) {
        $stmt = $this->pdo->prepare("DELETE FROM menu WHERE idplat = :idplat");
        $stmt->execute(['idplat' => $idplat]);
        return $stmt->rowCount();
    }
}