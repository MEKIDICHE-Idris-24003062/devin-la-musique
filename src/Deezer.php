<?php

declare(strict_types=1);

namespace App;

final class Deezer {
    private static function getJson(string $url): array {
        $json = file_get_contents($url);
        if ($json === false) throw new \RuntimeException('Deezer API unreachable');
        $data = json_decode($json, true);
        if (!is_array($data)) throw new \RuntimeException('Deezer API invalid JSON');
        if (isset($data['error'])) {
            $msg = $data['error']['message'] ?? 'unknown';
            throw new \RuntimeException('Deezer API error: ' . $msg);
        }
        return $data;
    }

    /**
     * Get chart tracks for a country.
     * Country id: 16 = France.
     */
    public static function chartTracks(int $countryId = 16, int $limit = 200): array {
        $limit = max(1, min(300, $limit));
        $url = "https://api.deezer.com/chart/{$countryId}/tracks?limit={$limit}";
        $data = self::getJson($url);
        $items = $data['data'] ?? null;
        if (!is_array($items)) throw new \RuntimeException('Deezer chart payload missing data');

        $out = [];
        foreach ($items as $t) {
            $norm = self::normalizeTrack($t);
            if ($norm) $out[] = $norm;
        }
        return $out;
    }

    /** Get playlist meta */
    public static function playlist(int $playlistId): array {
        $url = "https://api.deezer.com/playlist/{$playlistId}";
        return self::getJson($url);
    }

    /** Search playlists */
    public static function searchPlaylists(string $query, int $limit = 10): array {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(25, $limit));
        $url = 'https://api.deezer.com/search/playlist?' . http_build_query([
            'q' => $query,
            'limit' => $limit,
        ]);
        $data = self::getJson($url);
        $items = $data['data'] ?? [];
        if (!is_array($items)) return [];

        $out = [];
        foreach ($items as $p) {
            $id = $p['id'] ?? null;
            $title = $p['title'] ?? null;
            $link = $p['link'] ?? null;
            $nb = $p['nb_tracks'] ?? null;
            $creator = $p['user']['name'] ?? null;
            if (!$id || !$title) continue;
            $out[] = [
                'id' => (int)$id,
                'title' => (string)$title,
                'link' => $link ? (string)$link : null,
                'nb_tracks' => $nb ? (int)$nb : null,
                'creator' => $creator ? (string)$creator : null,
            ];
        }
        return $out;
    }

    /** Get details for one track (fresh preview URL). */
    public static function track(int $trackId): array {
        $url = "https://api.deezer.com/track/{$trackId}";
        $data = self::getJson($url);
        if (!is_array($data)) throw new \RuntimeException('Deezer track payload invalid');
        return $data;
    }

    /** Get tracks from a playlist id (handles pagination). */
    public static function playlistTracks(int $playlistId, int $limit = 200): array {
        $limit = max(1, min(500, $limit));

        // Deezer returns playlist object with tracks pagination
        $url = "https://api.deezer.com/playlist/{$playlistId}";
        $data = self::getJson($url);

        $out = [];

        // First page is embedded
        $page = $data['tracks'] ?? null;
        if (!is_array($page)) return [];

        while (true) {
            $tracks = $page['data'] ?? [];
            if (is_array($tracks)) {
                foreach ($tracks as $t) {
                    $norm = self::normalizeTrack($t);
                    if ($norm) {
                        $out[] = $norm;
                        if (count($out) >= $limit) return $out;
                    }
                }
            }

            $next = $page['next'] ?? null;
            if (!$next) break;

            // Fetch next page (tracks endpoint)
            $page = self::getJson((string)$next);
        }

        return $out;
    }

    /** @return array<string,mixed>|null */
    private static function normalizeTrack(array $t): ?array {
        $id = $t['id'] ?? null;
        $title = $t['title'] ?? null;
        $preview = $t['preview'] ?? null;
        $link = $t['link'] ?? null;
        $artist = $t['artist']['name'] ?? null;
        $album = $t['album']['title'] ?? null;
        $release = $t['release_date'] ?? null;

        if (!$id || !$title || !$artist) return null;
        $previewUrl = $preview ? (string)$preview : null;
        $linkUrl = $link ? (string)$link : null;

        // Keep previews on http:// if your site is served over http://
        // (Some environments/tools may not like upgrading to https.)
        if ($previewUrl && str_starts_with($previewUrl, 'https://')) {
            $previewUrl = 'http://' . substr($previewUrl, 8);
        }
        if ($linkUrl && str_starts_with($linkUrl, 'https://')) {
            $linkUrl = 'http://' . substr($linkUrl, 8);
        }

        return [
            'deezer_track_id' => (int)$id,
            'title' => (string)$title,
            'artist' => (string)$artist,
            'album' => $album ? (string)$album : null,
            'release_date' => $release ? (string)$release : null,
            'preview_url' => $previewUrl,
            'link' => $linkUrl,
        ];
    }
}
