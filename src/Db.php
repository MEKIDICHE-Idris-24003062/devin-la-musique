<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Db {
    private static ?PDO $pdo = null;

    public static function init(): void {
        $driver = Env::get('DB_DRIVER', 'sqlite');

        if ($driver === 'mysql') {
            $host = Env::get('DB_HOST');
            $port = Env::get('DB_PORT', '3306');
            $name = Env::get('DB_NAME');
            $user = Env::get('DB_USER');
            $pass = Env::get('DB_PASSWORD', '');

            if (!$host || !$name || !$user) {
                throw new \RuntimeException('Missing DB_HOST/DB_NAME/DB_USER for MySQL');
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Ensure FK constraints are enforced (default in InnoDB, but keep explicit).
            self::$pdo->exec('SET sql_mode = CONCAT(@@sql_mode, ",STRICT_ALL_TABLES")');

            self::migrateMySql();
            return;
        }

        // Default: SQLite (local dev)
        $path = Env::get('DB_PATH', __DIR__ . '/../data/app.db');
        $dir = dirname((string)$path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        self::$pdo = new PDO('sqlite:' . $path);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec('PRAGMA foreign_keys = ON;');

        self::migrateSqlite();
    }

    public static function pdo(): PDO {
        if (!self::$pdo) throw new \RuntimeException('DB not initialized');
        return self::$pdo;
    }

    private static function migrateSqlite(): void {
        // If schema changed (YouTube -> Deezer), we need to rebuild the tracks table.
        // We keep it simple: rename old table and create the new one.
        $pdo = self::$pdo;

        $exists = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='tracks'")->fetchColumn();
        if ($exists) {
            $cols = $pdo->query("PRAGMA table_info('tracks')")->fetchAll(PDO::FETCH_ASSOC);
            $names = array_map(fn($c) => (string)$c['name'], $cols);

            $oldYoutube = in_array('youtube_video_id', $names, true) && !in_array('deezer_track_id', $names, true);
            if ($oldYoutube) {
                // Rename old tracks table out of the way
                $pdo->exec("ALTER TABLE tracks RENAME TO tracks_youtube_old");
            }

            // Lightweight migrations for new columns
            if (!in_array('playlist_id', $names, true)) {
                $pdo->exec("ALTER TABLE tracks ADD COLUMN playlist_id INTEGER");
            }
            if (!in_array('playlist_title', $names, true)) {
                $pdo->exec("ALTER TABLE tracks ADD COLUMN playlist_title TEXT");
            }

            // Match mode columns (if games table already exists)
            $gamesExists = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='games'")->fetchColumn();
            if ($gamesExists) {
                $gcols = $pdo->query("PRAGMA table_info('games')")->fetchAll(PDO::FETCH_ASSOC);
                $gnames = array_map(fn($c) => (string)$c['name'], $gcols);
                if (!in_array('match_id', $gnames, true)) {
                    $pdo->exec("ALTER TABLE games ADD COLUMN match_id INTEGER");
                }
                if (!in_array('round', $gnames, true)) {
                    $pdo->exec("ALTER TABLE games ADD COLUMN round INTEGER");
                }
            }

            // Multiplayer presence
            $rpExists = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='room_players'")->fetchColumn();
            if ($rpExists) {
                $rpCols = $pdo->query("PRAGMA table_info('room_players')")->fetchAll(PDO::FETCH_ASSOC);
                $rpNames = array_map(fn($c) => (string)$c['name'], $rpCols);
                if (!in_array('last_seen_at', $rpNames, true)) {
                    $pdo->exec("ALTER TABLE room_players ADD COLUMN last_seen_at TEXT");
                }
            }
        }

        $sql = file_get_contents(__DIR__ . '/../data/schema.sql');
        if ($sql === false) throw new \RuntimeException('schema.sql missing');
        $pdo->exec($sql);
    }

    private static function migrateMySql(): void {
        $pdo = self::$pdo;
        $sql = file_get_contents(__DIR__ . '/../data/schema.mysql.sql');
        if ($sql === false) throw new \RuntimeException('schema.mysql.sql missing');
        $pdo->exec($sql);
    }
}
