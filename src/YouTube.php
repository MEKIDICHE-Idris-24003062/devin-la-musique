<?php

declare(strict_types=1);

namespace App;

final class YouTube {
    /**
     * Search music videos. Returns list of ['videoId' => string, 'title' => string].
     */
    public static function search(string $query, int $maxResults = 5): array {
        $key = Env::get('YOUTUBE_API_KEY');
        if (!$key) throw new \RuntimeException('YOUTUBE_API_KEY manquante dans .env');

        $region = Env::get('YOUTUBE_REGION', 'FR');
        $lang = Env::get('YOUTUBE_LANGUAGE', 'fr');

        $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
            'part' => 'snippet',
            'type' => 'video',
            'videoCategoryId' => '10',
            'maxResults' => $maxResults,
            'q' => $query,
            'regionCode' => $region,
            'relevanceLanguage' => $lang,
            'safeSearch' => 'none',
            'key' => $key,
        ]);

        $json = file_get_contents($url);
        if ($json === false) throw new \RuntimeException('YouTube API unreachable');
        $data = json_decode($json, true);
        if (!is_array($data)) throw new \RuntimeException('YouTube API invalid JSON');
        if (isset($data['error'])) throw new \RuntimeException('YouTube API error: ' . ($data['error']['message'] ?? 'unknown'));

        $out = [];
        foreach (($data['items'] ?? []) as $item) {
            $vid = $item['id']['videoId'] ?? null;
            $title = $item['snippet']['title'] ?? null;
            if (!$vid || !$title) continue;
            $out[] = ['videoId' => $vid, 'title' => $title];
        }
        return $out;
    }
}
