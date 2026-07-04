<?php
// Model : toutes les requêtes SQL liées à la table commande (l'en-tête de la commande)

class Commande {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Liste toutes les commandes, triées par date décroissante (les plus récentes en premier)
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM commande ORDER BY datecom DESC, idcom DESC");
        return $stmt->fetchAll();
    }

    public function getById($idcom) {
        $stmt = $this->pdo->prepare("SELECT * FROM commande WHERE idcom = :idcom");
        $stmt->execute(['idcom' => $idcom]);
        $commande = $stmt->fetch();
        return $commande ?: null;
    }

    // Recherche par nom de client avec LIKE %...%
    public function searchByClient($terme) {
        $stmt = $this->pdo->prepare("SELECT * FROM commande WHERE nomcli LIKE :terme ORDER BY datecom DESC");
        $stmt->execute(['terme' => '%' . $terme . '%']);
        return $stmt->fetchAll();
    }

    // Liste des commandes entre deux dates (bornes incluses)
    public function getByPeriode($dateDebut, $dateFin) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM commande WHERE datecom BETWEEN :debut AND :fin ORDER BY datecom DESC"
        );
        $stmt->execute(['debut' => $dateDebut, 'fin' => $dateFin]);
        return $stmt->fetchAll();
    }

    // Crée seulement l'en-tête de commande (les lignes sont gérées séparément par le Controller)
    // Une commande est toujours créée non payée (paye = 0) ; le paiement est une étape distincte.
    public function create($idcom, $nomcli, $typecom, $idtable, $datecom) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO commande (idcom, nomcli, typecom, idtable, datecom, paye)
             VALUES (:idcom, :nomcli, :typecom, :idtable, :datecom, 0)"
        );
        return $stmt->execute([
            'idcom'   => $idcom,
            'nomcli'  => $nomcli,
            'typecom' => $typecom,
            'idtable' => $idtable, // peut être null si typecom = 'emporter'
            'datecom' => $datecom,
        ]);
    }

    // Marque une commande comme payée. Action métier distincte d'une simple modification
    // (ex: appelée quand le client règle l'addition, ce qui permet aussi de libérer la table).
    public function marquerPayee($idcom) {
        $stmt = $this->pdo->prepare("UPDATE commande SET paye = 1 WHERE idcom = :idcom");
        $stmt->execute(['idcom' => $idcom]);
        return $stmt->rowCount();
    }

    public function update($idcom, $nomcli, $typecom, $idtable, $datecom) {
        $stmt = $this->pdo->prepare(
            "UPDATE commande
             SET nomcli = :nomcli, typecom = :typecom, idtable = :idtable, datecom = :datecom
             WHERE idcom = :idcom"
        );
        $stmt->execute([
            'nomcli'  => $nomcli,
            'typecom' => $typecom,
            'idtable' => $idtable,
            'datecom' => $datecom,
            'idcom'   => $idcom,
        ]);
        return $stmt->rowCount();
    }

    // Supprime l'en-tête. Grâce à ON DELETE CASCADE sur ligne_commande,
    // les lignes associées sont supprimées automatiquement par MySQL.
    public function delete($idcom) {
        $stmt = $this->pdo->prepare("DELETE FROM commande WHERE idcom = :idcom");
        $stmt->execute(['idcom' => $idcom]);
        return $stmt->rowCount();
    }
}