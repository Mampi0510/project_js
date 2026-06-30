<?php
// controllers/CommandeController.php
// Controller pour la ressource "commande" : orchestre les Models Commande et LigneCommande.
// C'est ici que vit la logique de transaction (en-tête + plusieurs lignes = une seule opération atomique).

require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/LigneCommande.php';
require_once __DIR__ . '/../models/Table.php';

class CommandeController {
    private $pdo;
    private $commande;
    private $ligneCommande;
    private $table;

    public function __construct($pdo) {
        $this->pdo = $pdo; // on garde une référence directe pour gérer la transaction ici
        $this->commande = new Commande($pdo);
        $this->ligneCommande = new LigneCommande($pdo);
        $this->table = new Table($pdo);
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

    // ----------------------------------------------------
    // GET : liste, recherche par client, par période, ou fiche détaillée
    // ----------------------------------------------------
    private function afficher() {
        // Fiche détaillée d'une commande avec ses lignes : ?idcom=A0057
        if (isset($_GET['idcom'])) {
            $cmd = $this->commande->getById($_GET['idcom']);
            if ($cmd === null) {
                http_response_code(404);
                echo json_encode(['erreur' => 'Commande introuvable']);
                return;
            }
            $cmd['lignes'] = $this->ligneCommande->getByCommande($_GET['idcom']);
            echo json_encode($cmd);
            return;
        }

        // Recherche par nom de client : ?recherche=maria
        if (isset($_GET['recherche']) && $_GET['recherche'] !== '') {
            echo json_encode($this->commande->searchByClient($_GET['recherche']));
            return;
        }

        // Liste entre deux dates (ou une seule date si dateDebut == dateFin) :
        // ?dateDebut=2026-06-01&dateFin=2026-06-30
        if (isset($_GET['dateDebut']) && isset($_GET['dateFin'])) {
            echo json_encode($this->commande->getByPeriode($_GET['dateDebut'], $_GET['dateFin']));
            return;
        }

        // Par défaut : liste complète des en-têtes (sans le détail des lignes, pour rester léger)
        echo json_encode($this->commande->getAll());
    }

    // ----------------------------------------------------
    // POST : créer une commande complète (en-tête + lignes) en une transaction
    // ----------------------------------------------------
    private function creer() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        // Validation de l'en-tête
        if (empty($donnees['idcom']) || empty($donnees['nomcli']) || empty($donnees['typecom']) || empty($donnees['datecom'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Champs requis : idcom, nomcli, typecom, datecom']);
            return;
        }

        if (!in_array($donnees['typecom'], ['table', 'emporter'])) {
            http_response_code(400);
            echo json_encode(['erreur' => "typecom doit être 'table' ou 'emporter'"]);
            return;
        }

        // Si commande sur table, idtable est obligatoire ; si à emporter, il doit être absent/null
        if ($donnees['typecom'] === 'table' && empty($donnees['idtable'])) {
            http_response_code(400);
            echo json_encode(['erreur' => "idtable requis quand typecom = 'table'"]);
            return;
        }
        $idtable = $donnees['typecom'] === 'table' ? $donnees['idtable'] : null;

        // Validation des lignes : il faut au moins un plat
        if (empty($donnees['lignes']) || !is_array($donnees['lignes'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'Le champ lignes doit contenir au moins un plat']);
            return;
        }
        foreach ($donnees['lignes'] as $ligne) {
            if (empty($ligne['idplat']) || empty($ligne['quantite']) || $ligne['quantite'] < 1) {
                http_response_code(400);
                echo json_encode(['erreur' => 'Chaque ligne doit avoir idplat et quantite (>= 1)']);
                return;
            }
        }

        // -------- Début de la transaction --------
        try {
            $this->pdo->beginTransaction();

            // 1. Insertion de l'en-tête
            $this->commande->create(
                $donnees['idcom'],
                $donnees['nomcli'],
                $donnees['typecom'],
                $idtable,
                $donnees['datecom']
            );

            // 2. Insertion de chaque ligne (si une seule échoue, l'exception remonte
            //    et on annule TOUT, y compris l'en-tête déjà inséré)
            foreach ($donnees['lignes'] as $ligne) {
                $this->ligneCommande->create($donnees['idcom'], $ligne['idplat'], $ligne['quantite']);
            }

          // 3. Si la commande est sur table, on occupe automatiquement la table :
            //    une réservation seule ne suffit pas à occuper, mais une commande active si.
            //    Fait dans la même transaction : si l'insertion échoue plus haut, la table
            //    ne sera jamais marquée occupée pour une commande qui n'existe pas.
            error_log('DEBUG idtable reçu = ' . var_export($idtable, true));
            if ($idtable !== null) {
                $nbLignesModifiees = $this->table->changerOccupation($idtable, 1);
                error_log('DEBUG changerOccupation a modifié ' . $nbLignesModifiees . ' ligne(s)');
            }

            // 4. Si on arrive ici, tout s'est bien passé : on valide définitivement
            $this->pdo->commit();

            http_response_code(201);
            echo json_encode(['message' => 'Commande créée avec succès', 'idcom' => $donnees['idcom']]);

        } catch (PDOException $e) {
            // Annule tout ce qui a été fait depuis beginTransaction()
            $this->pdo->rollBack();

            if ($e->errorInfo[1] == 1062) {
                http_response_code(409);
                echo json_encode(['erreur' => 'Ce code commande existe déjà']);
            } elseif ($e->errorInfo[1] == 1452) {
                // 1452 = la FK pointe vers une valeur inexistante (idtable ou idplat invalide)
                http_response_code(400);
                echo json_encode(['erreur' => 'idtable ou idplat invalide (référence inexistante)']);
            } else {
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur lors de la création de la commande']);
            }
        }
    }

    // ----------------------------------------------------
    // PUT : modifier l'en-tête, OU marquer une commande comme payée
    // (action=payer dans le corps JSON déclenche le second cas)
    // ----------------------------------------------------
    private function modifier() {
        $donnees = json_decode(file_get_contents('php://input'), true);

        if (empty($donnees['idcom'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idcom requis']);
            return;
        }

        $cmdExistante = $this->commande->getById($donnees['idcom']);
        if ($cmdExistante === null) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Commande introuvable']);
            return;
        }

        // Cas particulier : marquer la commande comme payée
        // body attendu : {"idcom": "A0057", "action": "payer"}
        if (isset($donnees['action']) && $donnees['action'] === 'payer') {
            $this->commande->marquerPayee($donnees['idcom']);

            // La table se libère automatiquement au paiement, comme l'exige le sujet :
            // "une table réservée ne peut pas être prise... mais peut être libérée
            // si l'addition est payée". Seulement pour les commandes sur table
            // (idtable est null pour les commandes à emporter).
            if ($cmdExistante['idtable'] !== null) {
                $this->table->changerOccupation($cmdExistante['idtable'], 0);
            }

            echo json_encode(['message' => 'Commande marquée comme payée']);
            return;
        }

        $nomcli  = $donnees['nomcli']  ?? $cmdExistante['nomcli'];
        $typecom = $donnees['typecom'] ?? $cmdExistante['typecom'];
        $idtable = $donnees['idtable'] ?? $cmdExistante['idtable'];
        $datecom = $donnees['datecom'] ?? $cmdExistante['datecom'];

        $this->commande->update($donnees['idcom'], $nomcli, $typecom, $idtable, $datecom);
        echo json_encode(['message' => 'Commande modifiée avec succès']);
    }

    // ----------------------------------------------------
    // DELETE : supprimer une commande (les lignes suivent via ON DELETE CASCADE)
    // ----------------------------------------------------
    private function supprimer() {
        if (empty($_GET['idcom'])) {
            http_response_code(400);
            echo json_encode(['erreur' => 'idcom requis']);
            return;
        }

        // On récupère la commande AVANT de la supprimer, pour connaître sa table
        // et son statut de paiement -- une fois supprimée, ces infos ne sont plus accessibles.
        $cmdExistante = $this->commande->getById($_GET['idcom']);
        if ($cmdExistante === null) {
            http_response_code(404);
            echo json_encode(['erreur' => 'Commande introuvable']);
            return;
        }

        $this->commande->delete($_GET['idcom']);

        // Si la commande était sur table et n'avait pas encore été payée,
        // on libère la table : elle ne doit pas rester occupée pour une commande
        // qui n'existe plus. Si elle était déjà payée, la table a déjà été libérée
        // au moment du paiement -- pas besoin de le refaire.
        if ($cmdExistante['idtable'] !== null && $cmdExistante['paye'] != 1) {
            $this->table->changerOccupation($cmdExistante['idtable'], 0);
        }

        echo json_encode(['message' => 'Commande supprimée avec succès']);
    }
}