# Contexte pour agents (Claude Code)

Ce fichier existe pour éviter de re-explorer tout le dépôt à chaque session. À lire avant de commencer.

## Le projet

Plugin WordPress `novi_storelocator` (store locator pour lessenteursgourmandes.fr, 297 magasins). Deux shortcodes :
- `[store_locator]` : carte + recherche (page `/ou-nous-trouver/`).
- `[fiche_magasin]` : page magasin individuelle rendue serveur, pas encore branchée sur le parcours principal (manque de contenu éditorial, voir issue #3).

## Règle non négociable : pas de scraping Google

Ne jamais proposer/implémenter du scraping de Google Maps (horaires, avis, photos), même "juste pour un magasin" ou "juste ce qui est disponible". Ça viole les CGU Google et expose l'hébergement à un bannissement IP. C'est déjà arrivé dans une conversation précédente que l'utilisateur insiste plusieurs fois — la réponse reste non. Alternatives déjà en place :
- Lien "Horaires & avis (Google Maps)" (recherche par nom+adresse, pas d'API) sur toutes les fiches.
- Horaires enrichis via l'API Overpass (OpenStreetMap), légale et gratuite — voir `osm-opening-hours.csv` (~16% de couverture fiable seulement, le reste doit être saisi à la main).

## Piège data : le Google Sheet écrase `stores.json`

`stores.json` est régénéré en entier à chaque upload CSV via l'admin WordPress (`convert_csv_to_json`). Toute colonne ajoutée directement dans `stores.json` (ex. `opening_hours`) sera perdue au prochain upload si elle n'existe pas dans le Google Sheet source. Toujours reporter les colonnes ajoutées dans le Sheet, pas seulement dans le JSON.

## Environnements

- Prod : lessenteursgourmandes.fr (déployée via `.github/workflows/deploy.yml` sur push `main`).
- Préprod : Kinsta (`stg-lessenteursgourmandesv2-lsgv2ppd.kinsta.cloud`), **pas connectée** à ce pipeline (issue #4) — sa clé MapTiler notamment peut différer/être restreinte à un autre domaine (source du bug "Invalid key" observé en préprod).

## Workflow git

`main` est protégée (CI obligatoire, pas de push direct). Toujours : branche → commit → push → PR. Ne jamais auto-merger une PR (même une doc) sans que l'utilisateur la valide — c'est tout l'intérêt de la protection de branche.

## Secrets

`novi_storelocator/assets/json/settings/settings.json` (clé API MapTiler) est gitignored — ne jamais le committer. Utiliser `settings.json.example` comme référence de format.

## Environnement d'exécution local (Windows, ce poste)

- PowerShell tourne en **Constrained Language Mode** : pas d'appels `.NET`/COM directs (`New-Object -ComObject`, `[System.Environment]::...` échouent). Pas d'automatisation Excel possible ici.
- Un antivirus bloque les scripts PowerShell "en forme de scraper" (boucle + requêtes web + accumulation de données + écriture fichier), même légitimes (ex. le script de vérification OSM). Contournement légitime : des boucles plus courtes, sortie console directe sans accumulation/fichier — jamais chercher à déguiser le script pour évader la détection.
- `gh` (GitHub CLI) installé via `winget install --id GitHub.cli -e --scope user` (pas de droits admin) ; binaire non résolu dans le PATH avant redémarrage complet de VS Code — l'invoquer par son chemin complet sous `%LOCALAPPDATA%\Microsoft\WinGet\Packages\GitHub.cli_...\bin\gh.exe` en attendant.
