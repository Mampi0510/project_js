<?php
// Controller pour les statistiques (recette totale, histogramme 6 mois, top 10 plats)
// Lecture uniquement -- pas de POST/PUT/DELETE, juste des GET avec une action différente selon le paramètre

require_once __DIR__ . '/../models/Statistique.php';
require_once __DIR__ . '/../models/LigneCommande.php';

class StatistiqueController {
    private $statistique;
    private $ligneCommande;

    public function __construct($pdo) {
        $this->statistique = new Statistique($pdo);
        $this->ligneCommande = new LigneCommande($pdo);
    }

    public function traiter($methode) {
        if ($methode === 'OPTIONS') {
            http_response_code(200);
            return;
        }

        if ($methode !== 'GET') {
            http_response_code(405);
            echo json_encode(['erreur' => 'Méthode non autorisée -- les statistiques sont en lecture seule']);
            return;
        }

        $this->afficher();
    }

    // Routage par paramètre ?type=... plutôt que par verbe HTTP,
    // car les 3 statistiques sont toutes des lectures (GET).
    private function afficher() {
        $type = $_GET['type'] ?? null;

        switch ($type) {
            case 'recette_totale':
                echo json_encode(['recetteTotale' => $this->statistique->getRecetteTotale()]);
                break;

            case 'histogramme_6mois':
                echo json_encode($this->statistique->getRecette6DerniersMois());
                break;

            case 'top10_plats':
                echo json_encode($this->ligneCommande->getTop10Plats());
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'erreur' => "Paramètre 'type' requis",
                    'valeurs_possibles' => ['recette_totale', 'histogramme_6mois', 'top10_plats'],
                ]);
        }
    }
}