<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Admin {
    public static function dashboard(): void {
        $pdo = Db::pdo();
        $tracks = $pdo->query('SELECT * FROM tracks ORDER BY id DESC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
        view('admin', [
            'tracks' => $tracks,
            'playlistResults' => $_SESSION['admin_playlist_results'] ?? [],
            'playlistQuery' => $_SESSION['admin_playlist_query'] ?? '',
        ]);
        unset($_SESSION['admin_playlist_results'], $_SESSION['admin_playlist_query']);
    }

    public static function importChart(array $post): void {
        $limit = (int)($post['limit'] ?? 200);
        $countryId = (int)($post['country_id'] ?? 16); // 16=France

        $items = Deezer::chartTracks($countryId, $limit);
        self::insertTracks($items);

        redirect('/admin');
    }

    public static function playlistSearch(array $post): void {
        $q = trim((string)($post['q'] ?? ''));
        $results = Deezer::searchPlaylists($q, 12);
        $_SESSION['admin_playlist_query'] = $q;
        $_SESSION['admin_playlist_results'] = $results;
        redirect('/admin');
    }

    public static function importPlaylist(array $post): void {
        $playlistId = (int)($post['playlist_id'] ?? 0);
        if (!$playlistId) redirect('/admin');

        $items = Deezer::playlistTracks($playlistId);
        self::insertTracks($items);

        redirect('/admin');
    }

    private static function insertTracks(array $items): void {
        $pdo = Db::pdo();
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO tracks(deezer_track_id,title,artist,album,release_date,preview_url,link,title_norm,artist_norm) VALUES(:id,:t,:a,:alb,:rd,:p,:l,:tn,:an)');

        foreach ($items as $t) {
            $stmt->execute([
                ':id' => (int)$t['deezer_track_id'],
                ':t' => (string)$t['title'],
                ':a' => (string)$t['artist'],
                ':alb' => $t['album'],
                ':rd' => $t['release_date'],
                ':p' => $t['preview_url'],
                ':l' => $t['link'],
                ':tn' => Text::normalize((string)$t['title']),
                ':an' => Text::normalize((string)$t['artist']),
            ]);
        }
    }

    public static function toggleTrack(array $post): void {
        $id = (int)($post['id'] ?? 0);
        $pdo = Db::pdo();
        $pdo->prepare('UPDATE tracks SET enabled = CASE enabled WHEN 1 THEN 0 ELSE 1 END WHERE id=:id')->execute([':id' => $id]);
        redirect('/admin');
    }
}
