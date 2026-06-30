<?php
// Controller dédié à la génération du PDF de l'addition pour une commande donnée
// Réutilise les Models Commande et LigneCommande déjà existants, aucun nouveau SQL ici

require_once __DIR__ . '/../lib/fpdf/fpdf.php';
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/LigneCommande.php';

class AdditionController {
    private $commande;
    private $ligneCommande;

    // Nom du restaurant affiché en en-tête du PDF -- à adapter si besoin
    private $nomRestaurant = 'RESTO MADA';

    public function __construct($pdo) {
        $this->commande = new Commande($pdo);
        $this->ligneCommande = new LigneCommande($pdo);
    }

    public function traiter($methode) {
        if ($methode === 'OPTIONS') {
            http_response_code(200);
            return;
        }

        if ($methode !== 'GET') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['erreur' => 'Méthode non autorisée']);
            return;
        }

        $this->genererPdf();
    }

    private function genererPdf() {
        if (empty($_GET['idcom'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['erreur' => 'idcom requis']);
            return;
        }

        $idcom = $_GET['idcom'];

        // Récupération des données via les Models existants
        $cmd = $this->commande->getById($idcom);
        if ($cmd === null) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['erreur' => 'Commande introuvable']);
            return;
        }

        $lignes = $this->ligneCommande->getByCommande($idcom);
        if (empty($lignes)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['erreur' => 'Cette commande ne contient aucun plat']);
            return;
        }

        // Calcul du total général (somme des sous-totaux de chaque ligne)
        $totalGeneral = 0;
        foreach ($lignes as $ligne) {
            $totalGeneral += $ligne['sousTotal'];
        }

        $pdf = new FPDF();
        $pdf->AddPage();

        // En-tête : nom du restaurant
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $this->nomRestaurant, 0, 1, 'C');
        $pdf->Ln(2);

        // Code commande
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Code Commande : ' . $cmd['idcom'], 0, 1, 'C');
        $pdf->Ln(6);

        // Nom du client et table
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, 'Nom du Client : ' . $cmd['nomcli'], 0, 1, 'L');
        if ($cmd['typecom'] === 'table') {
            $pdf->Cell(0, 7, 'Table : ' . $cmd['idtable'], 0, 1, 'L');
        } else {
            $pdf->Cell(0, 7, 'Type : A emporter', 0, 1, 'L');
        }
        $pdf->Ln(4);

        // Titre du tableau
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Votre facture en détail', 0, 1, 'C');
        $pdf->Ln(2);

        // En-tête du tableau (4 colonnes : Menu, PU, Unité, Total)
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(70, 8, 'Menu', 1, 0, 'L');
        $pdf->Cell(35, 8, 'PU (Ar)', 1, 0, 'R');
        $pdf->Cell(30, 8, 'Unite', 1, 0, 'C');
        $pdf->Cell(45, 8, 'Total (Ar)', 1, 1, 'R');

        // Lignes du tableau (une par plat commandé)
        $pdf->SetFont('Arial', '', 10);
        foreach ($lignes as $ligne) {
            $pdf->Cell(70, 8, $ligne['nomplat'], 1, 0, 'L');
            $pdf->Cell(35, 8, number_format($ligne['pu'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 8, $ligne['quantite'], 1, 0, 'C');
            $pdf->Cell(45, 8, number_format($ligne['sousTotal'], 0, ',', '.'), 1, 1, 'R');
        }

        $pdf->Ln(6);

        // Total général
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'TOTAL : ' . number_format($totalGeneral, 0, ',', '.') . ' Ariary', 0, 1, 'R');

        // Envoi du PDF directement au navigateur (le client peut le visualiser ou le télécharger)
        $pdf->Output('I', 'addition_' . $idcom . '.pdf');
    }
}