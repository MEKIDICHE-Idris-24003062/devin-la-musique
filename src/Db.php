<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Db {
    private static ?PDO $pdo = null;

    public static function init(): void {
        $path = Env::get('DB_PATH', __DIR__ . '/../data/app.db');
        $dir = dirname((string)$path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        self::$pdo = new PDO('sqlite:' . $path);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->exec('PRAGMA foreign_keys = ON;');

        self::migrate();
    }

    public static function pdo(): PDO {
        if (!self::$pdo) throw new \RuntimeException('DB not initialized');
        return self::$pdo;
    }

    private static function migrate(): void {
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
}
