<?php
// Model : toutes les requêtes SQL liées à la table ligne_commande (le détail des plats commandés)

class LigneCommande {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Récupère toutes les lignes d'une commande, avec le nom et le prix du plat
    // (jointure avec menu pour ne pas avoir à refaire une requête par ligne côté Controller)
    public function getByCommande($idcom) {
        $stmt = $this->pdo->prepare(
            "SELECT lc.idligne, lc.idcom, lc.idplat, lc.quantite, m.nomplat, m.pu,
                    (lc.quantite * m.pu) AS sousTotal
             FROM ligne_commande lc
             INNER JOIN menu m ON lc.idplat = m.idplat
             WHERE lc.idcom = :idcom"
        );
        $stmt->execute(['idcom' => $idcom]);
        return $stmt->fetchAll();
    }

    // Ajoute une ligne à une commande existante
    public function create($idcom, $idplat, $quantite) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ligne_commande (idcom, idplat, quantite) VALUES (:idcom, :idplat, :quantite)"
        );
        return $stmt->execute([
            'idcom'    => $idcom,
            'idplat'   => $idplat,
            'quantite' => $quantite,
        ]);
    }

    // Modifie la quantité d'une ligne précise (identifiée par son idligne)
    public function updateQuantite($idligne, $quantite) {
        $stmt = $this->pdo->prepare(
            "UPDATE ligne_commande SET quantite = :quantite WHERE idligne = :idligne"
        );
        $stmt->execute(['quantite' => $quantite, 'idligne' => $idligne]);
        return $stmt->rowCount();
    }

    // Supprime une ligne précise
    public function delete($idligne) {
        $stmt = $this->pdo->prepare("DELETE FROM ligne_commande WHERE idligne = :idligne");
        $stmt->execute(['idligne' => $idligne]);
        return $stmt->rowCount();
    }

    // Supprime toutes les lignes d'une commande (utile si on remplace entièrement le détail au PUT)
    public function deleteByCommande($idcom) {
        $stmt = $this->pdo->prepare("DELETE FROM ligne_commande WHERE idcom = :idcom");
        $stmt->execute(['idcom' => $idcom]);
        return $stmt->rowCount();
    }

    // Statistiques (utilisées plus tard pour le point "Liste des 10 plats les plus vendus")
    public function getTop10Plats() {
        $stmt = $this->pdo->query(
            "SELECT m.idplat, m.nomplat, SUM(lc.quantite) AS quantiteTotale
             FROM ligne_commande lc
             INNER JOIN menu m ON lc.idplat = m.idplat
             GROUP BY m.idplat, m.nomplat
             ORDER BY quantiteTotale DESC
             LIMIT 10"
        );
        return $stmt->fetchAll();
    }
}