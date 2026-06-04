# Gestion des Enseignants

Application SPA de gestion des enseignants — Vue 3 (frontend) + PHP (backend) + MySQL (base de données).

---

## Stack technique

- **Frontend** : Vue 3 + Vite + Vue Router
- **Backend** : PHP (API REST)
- **Base de données** : MySQL via phpMyAdmin (XAMPP)
- **Graphiques** : Chart.js

---

## Prérequis

- [Node.js](https://nodejs.org/) (v18+)
- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL)
- [VS Code](https://code.visualstudio.com/) + extension [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar)
- [Vue.js DevTools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd) (Chrome)

---

## Structure du projet

```
gestion-enseignants/          ← projet Vue (n'importe où sur le disque)
├── src/
│   ├── views/
│   │   ├── LoginView.vue     ← page d'authentification
│   │   ├── AjoutView.vue     ← formulaire d'ajout d'enseignant
│   │   ├── ListeView.vue     ← tableau + modification + suppression
│   │   └── BilanView.vue     ← statistiques + graphique Chart.js
│   ├── router/
│   │   └── index.js          ← routes + navigation guards
│   ├── App.vue               ← navbar + RouterView
│   └── main.js
└── package.json

C:/xampp/htdocs/backend/      ← API PHP (dans htdocs de XAMPP)
├── config.php                ← connexion PDO + headers CORS
└── enseignant.php            ← CRUD complet (GET/POST/PUT/DELETE)
```

---

## Installation

### 1. Frontend Vue

```bash
cd gestion-enseignants
npm install
npm run dev
```

L'application tourne sur `http://localhost:5173`

### 2. Backend PHP

Copier le dossier `backend/` dans `C:/xampp/htdocs/` :

```
C:/xampp/htdocs/backend/
├── config.php
└── enseignant.php
```

Démarrer **Apache** et **MySQL** depuis le panneau XAMPP.

### 3. Base de données

Dans phpMyAdmin (`http://localhost/phpmyadmin`) :

1. Créer une base de données nommée `gestion-enseignants`
2. Exécuter ce SQL :

```sql
CREATE TABLE enseignant (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricule VARCHAR(20) UNIQUE,
  nom VARCHAR(100) NOT NULL,
  taux_horaire DECIMAL(10,2) NOT NULL,
  nombre_heures DECIMAL(10,2) NOT NULL
);
```

---

## Authentification

Identifiants hardcodés (mock) — **à remplacer par une vraie auth PHP** :

| Champ | Valeur |
|---|---|
| Nom d'utilisateur | `admin` |
| Mot de passe | `1234` |

La session est persistée via `localStorage`.

---

## API PHP

Base URL : `http://localhost/backend/enseignant.php`

| Méthode | Action | Body attendu |
|---|---|---|
| `GET` | Lister tous les enseignants | — |
| `POST` | Ajouter un enseignant | `{ matricule, nom, taux_horaire, nombre_heures }` |
| `PUT` | Modifier un enseignant | `{ id, nom, taux_horaire, nombre_heures }` |
| `DELETE` | Supprimer un enseignant | `{ id }` |

Toutes les réponses sont en **JSON**. La prestation (`nombre_heures × taux_horaire`) est calculée côté SQL dans le GET.

---

## Ce qui reste à faire

### Style
L'application est fonctionnelle mais sans style. Options recommandées :
- **Tailwind CSS** : `npm install -D tailwindcss` (moderne, utilitaire)
- **Bootstrap** : `npm install bootstrap` (rapide à mettre en place)

Pages à styliser :
- `LoginView.vue` — centrer le formulaire, ajouter un fond
- `AjoutView.vue` — styliser les inputs et bouton
- `ListeView.vue` — styliser le tableau et la modal
- `BilanView.vue` — mise en page des stats et du graphique
- `App.vue` — navbar horizontale avec style

### Améliorations possibles
- Authentification réelle via `auth.php` (base de données utilisateurs)
- Validation des champs côté Vue avant envoi (matricule unique, champs requis)
- Pagination sur `ListeView` si beaucoup d'enseignants
- Message de confirmation avant suppression (`confirm()` ou modal)
- Graphique camembert (type `'pie'`) en alternative à l'histogramme dans `BilanView`
- `auth.php` — fichier prévu mais non implémenté

### Sécurité (production)
- Remplacer l'auth hardcodée par une vraie table `utilisateurs`
- Ajouter des tokens JWT ou sessions PHP
- Valider les données côté PHP avant insertion

---

## Commandes utiles

```bash
npm run dev      # développement avec hot-reload
npm run build    # build production dans /dist
```

---

## Notes CORS

Le frontend Vue (`localhost:5173`) et le backend PHP (`localhost/backend`) tournent sur des ports différents. Les headers CORS sont configurés dans `config.php` et s'appliquent automatiquement à tous les appels API.

Si tu changes le port de Vue, mets à jour cette ligne dans `config.php` :
```php
header("Access-Control-Allow-Origin: http://localhost:5173");
```
