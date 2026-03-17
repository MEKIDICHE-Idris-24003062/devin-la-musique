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

## Base de données
Par défaut, le projet utilise **SQLite** (fichier `data/app.db`).

Pour l'héberger sur Alwaysdata (ou autre hébergeur), tu peux passer en **MySQL** :
- Copier `.env.example` vers `.env`
- Mettre `DB_DRIVER=mysql` et renseigner `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`

Au premier lancement, l'application crée automatiquement les tables via `data/schema.mysql.sql`.

## Notes
- Le jeu ne tire que des musiques avec `preview_url` (extrait 30s).
- Plus tu cliques “Découvrir plus”, plus tu perds des points.
