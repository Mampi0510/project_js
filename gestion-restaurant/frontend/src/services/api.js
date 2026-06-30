// Centralise tous les appels fetch() vers le backend PHP (api/*.php)
// Adapte BASE_URL si ton dossier gestion-restaurant n'est pas directement sous htdocs/

const BASE_URL = 'http://localhost/gestion-restaurant/api';

// Fonction générique : envoie une requête HTTP et renvoie le JSON déjà parsé.
// Centralise aussi la gestion des erreurs HTTP (4xx/5xx) en un seul endroit.
async function requete(url, options = {}) {
  const reponse = await fetch(url, {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });

  const donnees = await reponse.json();

  if (!reponse.ok) {
    // Le backend renvoie toujours { erreur: "..." } 
    throw new Error(donnees.erreur || 'Erreur inconnue du serveur');
  }

  return donnees;
}

// API Menu
export const menuApi = {
  getAll() {
    return requete(`${BASE_URL}/menu.php`);
  },
  search(terme) {
    return requete(`${BASE_URL}/menu.php?recherche=${encodeURIComponent(terme)}`);
  },
  create(plat) {
    return requete(`${BASE_URL}/menu.php`, {
      method: 'POST',
      body: JSON.stringify(plat),
    });
  },
  update(plat) {
    return requete(`${BASE_URL}/menu.php`, {
      method: 'PUT',
      body: JSON.stringify(plat),
    });
  },
  delete(idplat) {
    return requete(`${BASE_URL}/menu.php?idplat=${encodeURIComponent(idplat)}`, {
      method: 'DELETE',
    });
  },
};

// API Table (table_)
export const tableApi = {
  getAll() {
    return requete(`${BASE_URL}/table.php`);
  },
  getByOccupation(occupation) {
    return requete(`${BASE_URL}/table.php?occupation=${occupation}`);
  },
  create(table) {
    return requete(`${BASE_URL}/table.php`, {
      method: 'POST',
      body: JSON.stringify(table),
    });
  },
  update(table) {
    return requete(`${BASE_URL}/table.php`, {
      method: 'PUT',
      body: JSON.stringify(table),
    });
  },
  // Bascule rapide occupée/libre sans toucher à la désignation
  changerOccupation(idtable, occupation) {
    return requete(`${BASE_URL}/table.php`, {
      method: 'PUT',
      body: JSON.stringify({ idtable, occupation }),
    });
  },
  delete(idtable) {
    return requete(`${BASE_URL}/table.php?idtable=${encodeURIComponent(idtable)}`, {
      method: 'DELETE',
    });
  },
};
 
// API Commande (commande + ligne_commande)
export const commandeApi = {
  getAll() {
    return requete(`${BASE_URL}/commande.php`);
  },
  getById(idcom) {
    return requete(`${BASE_URL}/commande.php?idcom=${encodeURIComponent(idcom)}`);
  },
  searchByClient(terme) {
    return requete(`${BASE_URL}/commande.php?recherche=${encodeURIComponent(terme)}`);
  },
  getByPeriode(dateDebut, dateFin) {
    return requete(`${BASE_URL}/commande.php?dateDebut=${dateDebut}&dateFin=${dateFin}`);
  },
  // commande = { idcom, nomcli, typecom, idtable, datecom, lignes: [{idplat, quantite}, ...] }
  create(commande) {
    return requete(`${BASE_URL}/commande.php`, {
      method: 'POST',
      body: JSON.stringify(commande),
    });
  },
  update(commande) {
    return requete(`${BASE_URL}/commande.php`, {
      method: 'PUT',
      body: JSON.stringify(commande),
    });
  },
  marquerPayee(idcom) {
    return requete(`${BASE_URL}/commande.php`, {
      method: 'PUT',
      body: JSON.stringify({ idcom, action: 'payer' }),
    });
  },
  delete(idcom) {
    return requete(`${BASE_URL}/commande.php?idcom=${encodeURIComponent(idcom)}`, {
      method: 'DELETE',
    });
  },
  // Construit l'URL du PDF -- pas un fetch JSON, juste un lien à ouvrir dans un nouvel onglet
  urlAdditionPdf(idcom) {
    return `${BASE_URL}/addition.php?idcom=${encodeURIComponent(idcom)}`;
  },
};
 
// API Reservation (reserver)
export const reservationApi = {
  getAll() {
    return requete(`${BASE_URL}/reserver.php`);
  },
  searchByClient(terme) {
    return requete(`${BASE_URL}/reserver.php?recherche=${encodeURIComponent(terme)}`);
  },
  create(reservation) {
    return requete(`${BASE_URL}/reserver.php`, {
      method: 'POST',
      body: JSON.stringify(reservation),
    });
  },
  update(reservation) {
    return requete(`${BASE_URL}/reserver.php`, {
      method: 'PUT',
      body: JSON.stringify(reservation),
    });
  },
  delete(idreserv) {
    return requete(`${BASE_URL}/reserver.php?idreserv=${encodeURIComponent(idreserv)}`, {
      method: 'DELETE',
    });
  },
};
 
// API Statistiques (lecture seule)
export const statistiqueApi = {
  getRecetteTotale() {
    return requete(`${BASE_URL}/statistiques.php?type=recette_totale`);
  },
  getHistogramme6Mois() {
    return requete(`${BASE_URL}/statistiques.php?type=histogramme_6mois`);
  },
  getTop10Plats() {
    return requete(`${BASE_URL}/statistiques.php?type=top10_plats`);
  },
};