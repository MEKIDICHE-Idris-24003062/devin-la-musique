# Devin-la-musique

Petit jeu en **PHP** : deviner une musique rap FR à partir d’un extrait audio (preview Deezer ~30s).

## Prérequis
- PHP 8.1+
- (Optionnel) Composer

## Installation rapide
1. Démarrer le serveur :
   ```bash
   php -S localhost:8000 -t public
   ```
2. Ouvrir http://localhost:8000
3. Crée un compte (le 1er devient admin) → Admin → importer le chart France.

## Notes
- Le jeu ne tire que des musiques avec `preview_url` (extrait 30s).
- Plus tu cliques “Découvrir plus”, plus tu perds des points.
