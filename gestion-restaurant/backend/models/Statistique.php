<?php
// models/Statistique.php
// Model dédié aux requêtes de reporting/agrégation qui touchent plusieurs tables
// (commande, ligne_commande, menu). Ne correspond à aucune table unique,
// mais regroupe la logique métier "statistiques" en un seul endroit.

class Statistique {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Recette totale accumulée : somme des montants de toutes les commandes PAYEES.
    // Le montant d'une commande = somme(quantite * pu) sur toutes ses lignes.
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

    // Recette des 6 derniers mois calendaires (mois en cours inclus), groupée par mois,
    // uniquement sur les commandes payées. Utilisé pour l'histogramme.
    // On travaille sur des mois entiers (pas une période glissante de 180 jours)
    // pour éviter qu'un mois partiel génère une barre supplémentaire.
    public function getRecette6DerniersMois() {
        // Premier jour du mois le plus ancien à inclure :
        // DATE_SUB(début du mois en cours, 5 mois) = 6 mois au total (5 précédents + en cours)
        $stmt = $this->pdo->query(
            "SELECT DATE_FORMAT(c.datecom, '%Y-%m') AS mois,
                COALESCE(SUM(lc.quantite * m.pu), 0) AS recette
             FROM commande c
             INNER JOIN ligne_commande lc ON c.idcom = lc.idcom
             INNER JOIN menu m ON lc.idplat = m.idplat
             WHERE c.paye = 1
               AND c.datecom >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
             GROUP BY DATE_FORMAT(c.datecom, '%Y-%m')
             ORDER BY mois ASC"
        );
        $resultats = $stmt->fetchAll();

        // Construction des 6 mois calendaires exacts (mois en cours + 5 précédents),
        // avec recette = 0 pour les mois sans commande payée.
        // On utilise 'first day of' pour rester sur des mois entiers sans décalage.
        $moisComplets = [];
        for ($i = 5; $i >= 0; $i--) {
            $cle = date('Y-m', strtotime("first day of -$i months"));
            $moisComplets[$cle] = 0;
        }
        foreach ($resultats as $ligne) {
            if (isset($moisComplets[$ligne['mois']])) {
                $moisComplets[$ligne['mois']] = (float) $ligne['recette'];
            }
        }

        $sortie = [];
        foreach ($moisComplets as $mois => $recette) {
            $sortie[] = ['mois' => $mois, 'recette' => $recette];
        }
        return $sortie;
    }
}