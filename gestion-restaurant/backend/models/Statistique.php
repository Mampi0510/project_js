<?php
// Model dédié aux requêtes de reporting/agrégation qui touchent plusieurs tables
// (commande, ligne_commande, menu). Ne correspond à aucune table unique,
// mais regroupe la logique métier "statistiques" en un seul endroit

class Statistique {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Recette totale accumulée : somme des montants de toutes les commandes payées
    // Le montant d'une commande = somme(quantite * pu) sur toutes ses lignes
    public function getRecetteTotale() {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(lc.quantite * m.pu), 0) AS recetteTotale
             FROM commande c
             INNER JOIN ligne_commande lc ON c.idcom = lc.idcom
             INNER JOIN menu m ON lc.idplat = m.idplat
             WHERE c.paye = 1"
        );
        $resultat = $stmt->fetch();
        return (float) $resultat['recetteTotale'];
    }

    // Recette des 6 derniers mois (y compris le mois en cours), groupée par mois,
    // uniquement sur les commandes payées. Utilisé pour l'histogramme.
    public function getRecette6DerniersMois() {
        $stmt = $this->pdo->query(
            "SELECT DATE_FORMAT(c.datecom, '%Y-%m') AS mois,
                    COALESCE(SUM(lc.quantite * m.pu), 0) AS recette
             FROM commande c
             INNER JOIN ligne_commande lc ON c.idcom = lc.idcom
             INNER JOIN menu m ON lc.idplat = m.idplat
             WHERE c.paye = 1
               AND c.datecom >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(c.datecom, '%Y-%m')
             ORDER BY mois ASC"
        );
        $resultats = $stmt->fetchAll();

        // On complète les mois sans aucune commande payée avec une recette de 0,
        // pour que l'histogramme affiche bien 6 barres même si certains mois sont vides
        $moisComplets = [];
        for ($i = 5; $i >= 0; $i--) {
            $cle = date('Y-m', strtotime("-$i months"));
            $moisComplets[$cle] = 0;
        }
        foreach ($resultats as $ligne) {
            $moisComplets[$ligne['mois']] = (float) $ligne['recette'];
        }

        // On transforme en tableau de paires [mois => recette], prêt pour un graphique
        $sortie = [];
        foreach ($moisComplets as $mois => $recette) {
            $sortie[] = ['mois' => $mois, 'recette' => $recette];
        }
        return $sortie;
    }
}