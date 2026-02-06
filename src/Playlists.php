<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Playlists {
    public static function page(): void {
        $userId = (int)Auth::user()['id'];
        $active = (int)(UserSettings::get($userId, 'active_playlist_id', '0') ?? '0');

        view('playlists', [
            'activePlaylistId' => $active,
            'playlistResults' => $_SESSION['playlist_results'] ?? [],
            'playlistQuery' => $_SESSION['playlist_query'] ?? '',
        ]);
        unset($_SESSION['playlist_results'], $_SESSION['playlist_query']);
    }

    public static function search(array $post): void {
        $q = trim((string)($post['q'] ?? ''));
        $results = Deezer::searchPlaylists($q, 12);
        $_SESSION['playlist_query'] = $q;
        $_SESSION['playlist_results'] = $results;
        redirect('/playlists');
    }

    public static function select(array $post): void {
        $playlistId = (int)($post['playlist_id'] ?? 0);
        $userId = (int)Auth::user()['id'];

        if ($playlistId === 0) {
            UserSettings::set($userId, 'active_playlist_id', null);
            redirect('/playlists');
        }

        // Import playlist tracks (so game selection is fast + stable)
        $meta = Deezer::playlist($playlistId);
        $title = (string)($meta['title'] ?? ('playlist #' . $playlistId));
        $items = Deezer::playlistTracks($playlistId, 300);

        $pdo = Db::pdo();
        $pdo->prepare('INSERT OR REPLACE INTO playlists(deezer_playlist_id,title) VALUES(:id,:t)')
            ->execute([':id' => $playlistId, ':t' => $title]);

        $insTrack = $pdo->prepare('INSERT OR IGNORE INTO tracks(deezer_track_id,title,artist,album,release_date,preview_url,link,title_norm,artist_norm,enabled) VALUES(:id,:t,:a,:alb,:rd,:p,:l,:tn,:an,1)');
        $getTrackId = $pdo->prepare('SELECT id FROM tracks WHERE deezer_track_id=:id');
        $map = $pdo->prepare('INSERT OR IGNORE INTO playlist_tracks(deezer_playlist_id, track_id) VALUES(:pid,:tid)');

        foreach ($items as $t) {
            $insTrack->execute([
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
            $getTrackId->execute([':id' => (int)$t['deezer_track_id']]);
            $tid = (int)($getTrackId->fetchColumn() ?: 0);
            if ($tid) {
                $map->execute([':pid' => $playlistId, ':tid' => $tid]);
            }
        }

        UserSettings::set($userId, 'active_playlist_id', (string)$playlistId);
        redirect('/play');
    }

    public static function activeTitle(int $playlistId): ?string {
        if ($playlistId <= 0) return null;
        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT title FROM playlists WHERE deezer_playlist_id=:id');
        $stmt->execute([':id' => $playlistId]);
        $t = $stmt->fetchColumn();
        return $t === false ? null : (string)$t;
    }
}
