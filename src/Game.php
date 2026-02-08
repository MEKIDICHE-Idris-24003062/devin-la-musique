<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Game {
    // Ajustables
    private const ROUNDS_PER_MATCH = 5;
    private const WIN_THRESHOLD = 300;

    private const BASE_POINTS = 100;        // max par manche
    private const REVEAL_PENALTY = 15;      // points en moins par "découvrir plus"
    // Paliers reveal: 5 → 10 → 15 → 20 → 30
    private const INITIAL_SECONDS = 5;
    private const REVEAL_INCREMENT = 5;
    private const MAX_SECONDS = 30;

    public static function play(): void {
        $pdo = Db::pdo();
        $userId = (int)Auth::user()['id'];

        // Solo mode: no "match". Keep a simple running score and avoid repeats during the current session.
        $score = (int)(UserSettings::get($userId, 'solo_score', '0') ?? '0');

        $usedTrackIds = $_SESSION['solo_used_track_ids'] ?? [];
        if (!is_array($usedTrackIds)) $usedTrackIds = [];
        $usedTrackIds = array_values(array_unique(array_map('intval', $usedTrackIds)));

        $notIn = '';
        $params = [];
        if (!empty($usedTrackIds)) {
            $placeholders = implode(',', array_fill(0, count($usedTrackIds), '?'));
            $notIn = " AND t.id NOT IN ($placeholders)";
            $params = $usedTrackIds;
        }

        // pick random enabled track from the user's active playlist ONLY
        $activePlaylistId = (int)(UserSettings::get($userId, 'active_playlist_id', '0') ?? '0');
        if ($activePlaylistId <= 0) {
            view('play', ['error' => 'Choisis une playlist active pour jouer (Playlists → Sélectionner).']);
            return;
        }

        $sql =
            "SELECT t.*
             FROM playlist_tracks pt
             JOIN tracks t ON t.id = pt.track_id
             WHERE t.enabled=1 AND pt.deezer_playlist_id=? {$notIn}
             ORDER BY RANDOM() LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$activePlaylistId], $params));
        $track = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$track) {
            view('play', ['error' => 'Pas assez de musiques uniques pour compléter la partie (5 manches). Importe une playlist plus grande.']);
            return;
        }

        // create a new game row (no match/round in solo)
        $stmt = $pdo->prepare('INSERT INTO games(match_id, round, user_id, track_id, reveals, seconds_revealed) VALUES(NULL,NULL,:u,:t,0,:s)');
        $stmt->execute([':u' => $userId, ':t' => (int)$track['id'], ':s' => self::INITIAL_SECONDS]);
        $gameId = (int)$pdo->lastInsertId();

        // remember used track ids for this session
        $usedTrackIds[] = (int)$track['id'];
        $_SESSION['solo_used_track_ids'] = $usedTrackIds;

        $_SESSION['game_id'] = $gameId;

        view('play', [
            'track' => $track,
            'gameId' => $gameId,
            'clipSeconds' => self::INITIAL_SECONDS,
            'maxSeconds' => self::MAX_SECONDS,
            'pointsNow' => self::pointsNow(0),
            'score' => $score,
        ]);
    }

    /**
     * Redirect to a fresh Deezer preview URL (tokens expire quickly).
     */
    public static function preview(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Missing id';
            return;
        }

        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT deezer_track_id FROM tracks WHERE id=:id');
        $stmt->execute([':id' => $id]);
        $deezerId = (int)($stmt->fetchColumn() ?: 0);
        if (!$deezerId) {
            http_response_code(404);
            echo 'Track not found';
            return;
        }

        try {
            $data = Deezer::track($deezerId);
            $preview = $data['preview'] ?? null;
            if (!$preview) {
                http_response_code(404);
                echo 'No preview';
                return;
            }
            header('Location: ' . $preview, true, 302);
            return;
        } catch (\Throwable $e) {
            http_response_code(502);
            echo 'Deezer error: ' . $e->getMessage();
            return;
        }
    }

    public static function reveal(): void {
        header('Content-Type: application/json');

        $gameId = (int)($_SESSION['game_id'] ?? 0);
        if (!$gameId) {
            echo json_encode(['ok' => false, 'error' => 'Aucune partie en cours']);
            return;
        }

        $pdo = Db::pdo();
        $game = self::getGame($gameId);

        $prevSeconds = (int)$game['seconds_revealed'];
        $reveals = (int)$game['reveals'] + 1;
        $seconds = min(self::MAX_SECONDS, $prevSeconds + self::REVEAL_INCREMENT);

        $stmt = $pdo->prepare('UPDATE games SET reveals=:r, seconds_revealed=:s WHERE id=:id');
        $stmt->execute([':r' => $reveals, ':s' => $seconds, ':id' => $gameId]);

        echo json_encode([
            'ok' => true,
            'prevClipSeconds' => $prevSeconds,
            'clipSeconds' => $seconds,
            'pointsNow' => self::pointsNow($reveals),
        ]);
    }

    public static function guess(array $post): void {
        $gameId = (int)($_SESSION['game_id'] ?? 0);
        if (!$gameId) redirect('/play');

        $guessTitle = trim((string)($post['title'] ?? ''));
        $guessArtist = trim((string)($post['artist'] ?? ''));

        $pdo = Db::pdo();
        $game = self::getGame($gameId);

        $track = $pdo->prepare('SELECT * FROM tracks WHERE id=:id');
        $track->execute([':id' => (int)$game['track_id']]);
        $track = $track->fetch(PDO::FETCH_ASSOC);
        if (!$track) {
            view('play', ['error' => 'Track introuvable']);
            return;
        }

        $titleOk = Text::similarity($guessTitle, (string)$track['title']) >= 0.82;

        // Artist match: accept any contributor (feat) if Deezer provides them.
        $artistOk = false;
        $artistCandidates = [(string)$track['artist']];
        try {
            $deezerId = (int)($track['deezer_track_id'] ?? 0);
            if ($deezerId) {
                $dz = Deezer::track($deezerId);
                if (!empty($dz['contributors']) && is_array($dz['contributors'])) {
                    foreach ($dz['contributors'] as $c) {
                        $name = $c['name'] ?? null;
                        if ($name) $artistCandidates[] = (string)$name;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore and fallback to stored artist only
        }

        $artistCandidates = array_values(array_unique(array_filter(array_map('trim', $artistCandidates))));
        foreach ($artistCandidates as $cand) {
            if (Text::similarity($guessArtist, $cand) >= 0.82) {
                $artistOk = true;
                break;
            }
        }

        $points = self::pointsNow((int)$game['reveals']);
        if ($titleOk && $artistOk) {
            $final = $points;
        } elseif ($titleOk || $artistOk) {
            $final = (int)floor($points / 2);
        } else {
            $final = 0;
        }

        $stmt = $pdo->prepare('UPDATE games SET guessed_title=:gt, guessed_artist=:ga, points=:p WHERE id=:id');
        $stmt->execute([
            ':gt' => $titleOk ? 1 : 0,
            ':ga' => $artistOk ? 1 : 0,
            ':p' => $final,
            ':id' => $gameId,
        ]);

        // Solo mode: keep a running total score and always show it
        $userId = (int)Auth::user()['id'];
        $totalBefore = (int)(UserSettings::get($userId, 'solo_score', '0') ?? '0');
        $totalAfter = $totalBefore + $final;
        UserSettings::set($userId, 'solo_score', (string)$totalAfter);

        unset($_SESSION['game_id']);
        view('result', [
            'track' => $track,
            'titleOk' => $titleOk,
            'artistOk' => $artistOk,
            'final' => $final,
            'pointsBefore' => $points,
            'totalScore' => $totalAfter,
        ]);
    }

    private static function pointsNow(int $reveals): int {
        return max(0, self::BASE_POINTS - ($reveals * self::REVEAL_PENALTY));
    }

    /** @return array<string,mixed> */
    private static function getGame(int $gameId): array {
        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT * FROM games WHERE id=:id AND user_id=:u');
        $stmt->execute([':id' => $gameId, ':u' => (int)Auth::user()['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new \RuntimeException('Game not found');
        return $row;
    }
}
