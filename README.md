# wp-storelocator (NOVI Store Locator Uploader)

Plugin WordPress utilisé sur [lessenteursgourmandes.fr](https://lessenteursgourmandes.fr/ou-nous-trouver/) pour afficher le store locator (carte + recherche de magasins).

## Structure

- `novi_storelocator/novi_storelocator.php` — plugin WordPress principal (shortcode `[store_locator]`, page d'admin d'upload CSV → JSON, réglages).
- `novi_storelocator/novi_storelocator-save.php` — sauvegarde technique de l'ancienne version du plugin (ne pas activer).
- `novi_storelocator/assets/js/storelocator.js` — logique front (carte Leaflet, recherche par ville/code postal, calcul des magasins les plus proches, génération des fiches).
- `novi_storelocator/assets/css/storelocator.css` — styles du store locator.
- `novi_storelocator/assets/json/stores.json` — base des magasins (générée depuis un export CSV du Google Sheet, via la page d'admin du plugin).
- `novi_storelocator/assets/json/communes.json` — référentiel des communes/codes postaux pour la recherche.
- `lsg_store_locator_database.xlsx` — export de référence de la base magasins.

## Fonctionnement

1. L'équipe édite un Google Sheet des magasins, l'exporte en CSV.
2. Le CSV est uploadé depuis l'admin WordPress (menu "NOVI Store Locator Uploader"), converti en JSON et stocké dans `assets/json/stores.json`.
3. La page contenant le shortcode `[store_locator]` charge Leaflet + `storelocator.js`, qui affiche la carte, géolocalise l'utilisateur et liste les magasins les plus proches.

## Ticket en cours

Voir [READE.md](READE.md) : ajout d'un bouton "Je découvre" et de pages/fiches magasin individuelles (SEO/GEO), inspirées de la fiche magasin Beauty Success.
