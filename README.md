# wp-storelocator (NOVI Store Locator Uploader)

Plugin WordPress utilisé sur [lessenteursgourmandes.fr](https://lessenteursgourmandes.fr/ou-nous-trouver/) pour afficher le store locator (carte + recherche de magasins) des 297 points de vente Les Senteurs Gourmandes.

## Structure

- `novi_storelocator/novi_storelocator.php` — plugin WordPress principal : shortcode `[store_locator]` (carte + recherche), shortcode `[fiche_magasin]` (page magasin individuelle, SEO/GEO — voir issue #3), page d'admin d'upload CSV → JSON, réglages.
- `novi_storelocator/novi_storelocator-save.php` — sauvegarde technique de l'ancienne version du plugin (ne pas activer).
- `novi_storelocator/assets/js/storelocator.js` — logique front : carte Leaflet, recherche par ville/code postal, calcul des magasins les plus proches, fiches de la liste, modale "Je découvre" (horaires + statut ouvert/fermé calculés côté client, téléphone, lien Google Maps).
- `novi_storelocator/assets/js/fiche-magasin.js` — mini-carte pour la page `[fiche_magasin]`.
- `novi_storelocator/assets/css/storelocator.css` — styles du store locator, de la modale et de la fiche magasin.
- `novi_storelocator/assets/json/stores.json` — base des magasins (générée depuis un export CSV du Google Sheet, via la page d'admin du plugin). 47 magasins ont un champ `opening_hours` enrichi depuis OpenStreetMap (voir `osm-opening-hours.csv` et issue #2).
- `novi_storelocator/assets/json/communes.json` — référentiel des communes/codes postaux pour la recherche.
- `osm-opening-hours.csv` — horaires récupérés via l'API Overpass (OpenStreetMap), à reporter dans le Google Sheet source pour survivre au prochain export CSV.
- `lsg_store_locator_database.xlsx` — export de référence de la base magasins.

## Fonctionnement

1. L'équipe édite un Google Sheet des magasins, l'exporte en CSV.
2. Le CSV est uploadé depuis l'admin WordPress (menu "NOVI Store Locator Uploader"), converti en JSON et stocké dans `assets/json/stores.json`. **Toute colonne ajoutée dans `stores.json` mais absente du Google Sheet sera perdue au prochain upload** — reporter les colonnes dans le Sheet avant de re-uploader (ex. `opening_hours`, `opening_hours_source`, voir issue #2).
3. La page contenant le shortcode `[store_locator]` charge Leaflet + `storelocator.js`, affiche la carte, géolocalise l'utilisateur, liste les magasins les plus proches, et propose un bouton "Je découvre" (quand le magasin a du contenu à montrer : téléphone, horaires, ou service signature) ouvrant une modale plutôt qu'une page à part, pour éviter d'atterrir sur une fiche vide.
4. Réglages disponibles dans l'admin (clé API MapTiler, couleurs des boutons, URL de la page fiche magasin).

## Ce qu'on ne fait pas (et pourquoi)

Les horaires, avis et photos Google ne sont **pas scrapés** depuis Google Maps : ça viole les CGU de Google, expose le serveur à un bannissement, et le contenu dupliqué n'aide pas le référencement. À la place :
- un lien direct "Horaires & avis (Google Maps)" est affiché sur toutes les fiches (les vraies données, en direct, hébergées chez Google) ;
- les horaires affichés dans la modale viennent soit d'OpenStreetMap (source légale et gratuite, ~16% de couverture fiable), soit d'une saisie manuelle à venir (issue #2) ;
- le contenu SEO original (texte de présentation, produits mis en avant) doit être rédigé par l'équipe (issue #3).

## Workflow de développement

- **`main` est protégée** : pas de push direct, la CI (lint PHP + validation JSON/JS) doit passer avant de merger une Pull Request.
- Le travail se fait sur des branches (`feature/...`, `fix/...`, `chore/...`), une Pull Request par sujet, reliée à une issue quand c'est pertinent.
- Le suivi du travail se fait via les [issues GitHub](https://github.com/Lucas-tsl/wp-storelocator/issues), pas dans ce README.

## CI/CD

- **CI** (`.github/workflows/ci.yml`) : sur chaque push/PR, vérifie la syntaxe PHP (`php -l`), la validité de tous les fichiers JSON, et la syntaxe JS.
- **CD** (`.github/workflows/deploy.yml`) : sur chaque push sur `main` touchant `novi_storelocator/`, déploie le plugin en production via SFTP (secrets `SFTP_HOST`, `SFTP_USERNAME`, `SFTP_PASSWORD`, `SFTP_PORT`, `SFTP_REMOTE_PATH`). Déclenchable aussi manuellement (`workflow_dispatch`).
- ⚠️ L'environnement de préproduction Kinsta n'est **pas** branché sur ce pipeline (voir issue #4) : il a sa propre copie du plugin, à synchroniser manuellement pour l'instant.

## Sécurité

- `novi_storelocator/assets/json/settings/settings.json` (clé API MapTiler) est **exclu du dépôt** (`.gitignore`) car le repo est public — voir `settings.json.example` pour le format attendu.
