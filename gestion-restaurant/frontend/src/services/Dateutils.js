// src/services/dateUtils.js
// Centralise toute la manipulation de dates/heures du projet.
// Objectif : éviter les pièges classiques de toISOString() (qui convertit en UTC,
// donc peut renvoyer la date d'hier ou de demain selon l'heure et le fuseau local)
// et donner un format d'affichage cohérent partout dans l'application.

// Retourne la date du jour en heure LOCALE, au format "AAAA-MM-JJ".
// À utiliser à la place de `new Date().toISOString().slice(0, 10)`,
// qui peut se tromper de jour selon le fuseau horaire et l'heure de la journée.
export function dateAujourdhuiLocale() {
  const maintenant = new Date();
  const annee = maintenant.getFullYear();
  const mois = String(maintenant.getMonth() + 1).padStart(2, '0');
  const jour = String(maintenant.getDate()).padStart(2, '0');
  return `${annee}-${mois}-${jour}`;
}

// Combine une date ("AAAA-MM-JJ") avec une heure ("HH:MM") pour obtenir
// un DATETIME complet au format attendu par le backend ("AAAA-MM-JJ HH:MM:SS").
export function combinerDateEtHeure(date, heure) {
  return `${date} ${heure}:00`;
}

// Extrait la partie date ("AAAA-MM-JJ") d'un DATETIME complet renvoyé par le backend
// (ex: "2026-06-25 19:30:00" en "2026-06-25"). Utile pour comparer une réservation
// à une date donnée sans dépendre du format exact de l'heure.
export function extraireDate(datetime) {
  return datetime.split(' ')[0];
}

// Extrait l'heure ("HH:MM") d'un DATETIME complet.
export function extraireHeure(datetime) {
  return datetime.split(' ')[1].slice(0, 5);
}

// Formate un DATETIME complet pour l'affichage : "2026-06-25 19:30:00" en "25/06/2026 19:30"
export function formaterDateHeure(datetime) {
  const [datePart, heurePart] = datetime.split(' ');
  const [annee, mois, jour] = datePart.split('-');
  const heure = heurePart.slice(0, 5);
  return `${jour}/${mois}/${annee} ${heure}`;
}

// Formate une simple date ("AAAA-MM-JJ") pour l'affichage : "2026-06-25" en "25/06/2026"
export function formaterDate(date) {
  const [annee, mois, jour] = date.split('-');
  return `${jour}/${mois}/${annee}`;
}