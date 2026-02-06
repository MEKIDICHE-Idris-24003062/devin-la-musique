<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Rooms {
    private const ROUNDS = 5;

    private static function backupSoloSession(): void {
        // Save solo progress so leaving multi restores it.
        if (!isset($_SESSION['solo_backup'])) {
            $_SESSION['solo_backup'] = [
                'match_id' => $_SESSION['match_id'] ?? null,
                'game_id' => $_SESSION['game_id'] ?? null,
            ];
        }
        // Clear solo in-progress markers while in multi
        unset($_SESSION['match_id'], $_SESSION['game_id']);
    }

    private static function restoreSoloSession(): void {
        $b = $_SESSION['solo_backup'] ?? null;
        unset($_SESSION['solo_backup']);
        if (is_array($b)) {
            if (!empty($b['match_id'])) $_SESSION['match_id'] = $b['match_id'];
            if (!empty($b['game_id'])) $_SESSION['game_id'] = $b['game_id'];
        }
    }

    public static function index(): void {
        Auth::require();
        view('rooms', ['error' => null]);
    }

    public static function create(array $post): void {
        Auth::require();
        csrf_check();

        $pdo = Db::pdo();
        $hostId = (int)Auth::user()['id'];

        $code = self::newCode($pdo);

        // Host chooses playlist: default to host's current active playlist (optional)
        $playlistId = (int)(UserSettings::get($hostId, 'active_playlist_id', '0') ?? '0');

        $pdo->prepare('INSERT INTO rooms(code, host_user_id, playlist_id, status) VALUES(:c,:h,:p,\'lobby\')')
            ->execute([':c' => $code, ':h' => $hostId, ':p' => $playlistId ?: null]);
        $roomId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT OR IGNORE INTO room_players(room_id, user_id, joined_at, last_seen_at) VALUES(:r,:u,datetime(\'now\'), datetime(\'now\'))')
            ->execute([':r' => $roomId, ':u' => $hostId]);
        $pdo->prepare('UPDATE room_players SET last_seen_at=datetime(\'now\') WHERE room_id=:r AND user_id=:u')
            ->execute([':r' => $roomId, ':u' => $hostId]);

        self::backupSoloSession();
        $_SESSION['room_id'] = $roomId;
        redirect('/room');
    }

    public static function join(array $post): void {
        Auth::require();
        csrf_check();

        $code = strtoupper(trim((string)($post['code'] ?? '')));
        if ($code === '') {
            view('rooms', ['error' => 'Code requis']);
            return;
        }

        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT id FROM rooms WHERE code=:c');
        $stmt->execute([':c' => $code]);
        $roomId = (int)($stmt->fetchColumn() ?: 0);
        if (!$roomId) {
            view('rooms', ['error' => 'Salon introuvable']);
            return;
        }

        $pdo->prepare('INSERT OR IGNORE INTO room_players(room_id, user_id, joined_at, last_seen_at) VALUES(:r,:u,datetime(\'now\'), datetime(\'now\'))')
            ->execute([':r' => $roomId, ':u' => (int)Auth::user()['id']]);
        $pdo->prepare('UPDATE room_players SET last_seen_at=datetime(\'now\') WHERE room_id=:r AND user_id=:u')
            ->execute([':r' => $roomId, ':u' => (int)Auth::user()['id']]);

        self::backupSoloSession();
        $_SESSION['room_id'] = $roomId;
        redirect('/room');
    }

    public static function leave(): void {
        // Leave room mode (doesn't remove you from the room permanently; just mark offline + exit UI)
        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if ($roomId) {
            $pdo->prepare("UPDATE room_players SET last_seen_at=datetime('now','-10 minutes') WHERE room_id=:r AND user_id=:u")
                ->execute([':r' => $roomId, ':u' => (int)Auth::user()['id']]);
        }

        unset($_SESSION['room_id'], $_SESSION['room_game_id']);
        self::restoreSoloSession();
        redirect('/play');
    }

    public static function ping(): void {
        header('Content-Type: application/json');
        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) {
            echo json_encode(['ok' => false]);
            return;
        }

        // If user was kicked, stop pinging (client will be redirected on next page load).
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_players WHERE room_id=:r AND user_id=:u');
        $stmt->execute([':r' => $roomId, ':u' => (int)Auth::user()['id']]);
        if ((int)$stmt->fetchColumn() === 0) {
            unset($_SESSION['room_id'], $_SESSION['room_game_id']);
            echo json_encode(['ok' => false, 'kicked' => true]);
            return;
        }

        $pdo->prepare("UPDATE room_players SET last_seen_at=datetime('now') WHERE room_id=:r AND user_id=:u")
            ->execute([':r' => $roomId, ':u' => (int)Auth::user()['id']]);
        echo json_encode(['ok' => true]);
    }

    public static function kick(array $post): void {
        Auth::require();

        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) redirect('/rooms');

        $room = self::getRoom($pdo, $roomId);
        if ((int)$room['host_user_id'] !== (int)Auth::user()['id']) {
            http_response_code(403);
            echo 'Host only';
            exit;
        }

        $userId = (int)($post['user_id'] ?? 0);
        if (!$userId || $userId === (int)$room['host_user_id']) {
            redirect('/room');
        }

        // Remove player from room; their browser will disappear from list and next actions redirect.
        $pdo->prepare('DELETE FROM room_players WHERE room_id=:r AND user_id=:u')
            ->execute([':r' => $roomId, ':u' => $userId]);

        // Optional: remove their scores for this room to avoid clutter
        $pdo->prepare('DELETE FROM room_games WHERE room_id=:r AND user_id=:u')
            ->execute([':r' => $roomId, ':u' => $userId]);

        redirect('/room');
    }

    public static function lobby(): void {
        Auth::require();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) redirect('/rooms');

        $pdo = Db::pdo();
        self::requireMember($pdo, $roomId, (int)Auth::user()['id']);
        $room = self::getRoom($pdo, $roomId);

        // Only show players active in the last ~90 seconds (browser closed => no more pings)
        $players = $pdo->prepare("SELECT u.username, rp.user_id
                                  FROM room_players rp
                                  JOIN users u ON u.id=rp.user_id
                                  WHERE rp.room_id=:r
                                    AND (rp.last_seen_at IS NULL OR rp.last_seen_at >= datetime('now','-90 seconds'))
                                  ORDER BY rp.joined_at ASC");
        $players->execute([':r' => $roomId]);
        $players = $players->fetchAll(PDO::FETCH_ASSOC);

        $playlistTitle = null;
        if (!empty($room['playlist_id'])) {
            $stmt = $pdo->prepare('SELECT title FROM playlists WHERE deezer_playlist_id=:id');
            $stmt->execute([':id' => (int)$room['playlist_id']]);
            $playlistTitle = $stmt->fetchColumn() ?: null;
        }

        view('room_lobby', [
            'room' => $room,
            'players' => $players,
            'playlistTitle' => $playlistTitle,
        ]);
    }

    /**
     * Host sets the room playlist to their own current playlist choice.
     * (Host goes to /playlists, picks one, then clicks this.)
     */
    public static function useMyPlaylist(): void {
        Auth::require();

        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) redirect('/rooms');

        $room = self::getRoom($pdo, $roomId);
        $hostId = (int)$room['host_user_id'];
        if ($hostId !== (int)Auth::user()['id']) {
            http_response_code(403);
            echo 'Host only';
            exit;
        }

        $playlistId = (int)(UserSettings::get($hostId, 'active_playlist_id', '0') ?? '0');
        if ($playlistId <= 0) {
            $pdo->prepare('UPDATE rooms SET playlist_id=NULL WHERE id=:r')->execute([':r' => $roomId]);
            redirect('/room');
        }

        // Ensure meta exists (Playlists page should have imported it, but safe)
        try {
            $meta = Deezer::playlist($playlistId);
            $title = (string)($meta['title'] ?? ('playlist #' . $playlistId));
            $pdo->prepare('INSERT OR REPLACE INTO playlists(deezer_playlist_id,title) VALUES(:id,:t)')
                ->execute([':id' => $playlistId, ':t' => $title]);
        } catch (\Throwable $e) {
            // ignore
        }

        $pdo->prepare('UPDATE rooms SET playlist_id=:p WHERE id=:r')->execute([':p' => $playlistId, ':r' => $roomId]);
        redirect('/room');
    }

    public static function start(array $post): void {
        Auth::require();
        csrf_check();

        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) redirect('/rooms');

        $room = self::getRoom($pdo, $roomId);
        if ((int)$room['host_user_id'] !== (int)Auth::user()['id']) {
            http_response_code(403);
            echo 'Host only';
            exit;
        }

        if ((string)$room['status'] !== 'lobby') {
            redirect('/room/play');
        }

        // Reset any previous run in this room so everyone starts at 0.
        $pdo->prepare('DELETE FROM room_games WHERE room_id=:r')->execute([':r' => $roomId]);

        // Pre-select 5 tracks for the room
        $playlistId = (int)($room['playlist_id'] ?? 0);
        $tracks = [];

        if ($playlistId > 0) {
            // Ensure no duplicate Deezer track within the 5 rounds
            $stmt = $pdo->prepare(
                "SELECT MIN(t.id) AS id
                 FROM playlist_tracks pt
                 JOIN tracks t ON t.id=pt.track_id
                 WHERE pt.deezer_playlist_id=:pid AND t.enabled=1
                 GROUP BY t.deezer_track_id
                 ORDER BY RANDOM() LIMIT " . self::ROUNDS
            );
            $stmt->execute([':pid' => $playlistId]);
            $tracks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        if (count($tracks) < self::ROUNDS) {
            // Fallback, still avoid duplicates by Deezer track id
            $stmt = $pdo->query('SELECT MIN(id) AS id FROM tracks WHERE enabled=1 GROUP BY deezer_track_id ORDER BY RANDOM() LIMIT ' . self::ROUNDS);
            $tracks = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $pdo->prepare('DELETE FROM room_rounds WHERE room_id=:r')->execute([':r' => $roomId]);
        $ins = $pdo->prepare('INSERT INTO room_rounds(room_id, round, track_id) VALUES(:r,:n,:t)');
        $n = 0;
        foreach ($tracks as $tid) {
            $n++;
            $ins->execute([':r' => $roomId, ':n' => $n, ':t' => (int)$tid]);
        }

        $pdo->prepare("UPDATE rooms SET status='running', started_at=datetime('now') WHERE id=:r")->execute([':r' => $roomId]);

        redirect('/room/play');
    }

    public static function play(): void {
        Auth::require();
        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) redirect('/rooms');

        self::requireMember($pdo, $roomId, (int)Auth::user()['id']);
        $room = self::getRoom($pdo, $roomId);
        if ((string)$room['status'] === 'lobby') redirect('/room');

        $userId = (int)Auth::user()['id'];

        // Determine next round for this user
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(round),0) FROM room_games WHERE room_id=:r AND user_id=:u');
        $stmt->execute([':r' => $roomId, ':u' => $userId]);
        $last = (int)$stmt->fetchColumn();
        $round = $last + 1;

        if ($round > self::ROUNDS) {
            redirect('/room/leaderboard');
        }

        // Get track for that round
        $stmt = $pdo->prepare('SELECT track_id FROM room_rounds WHERE room_id=:r AND round=:n');
        $stmt->execute([':r' => $roomId, ':n' => $round]);
        $trackId = (int)($stmt->fetchColumn() ?: 0);
        if (!$trackId) {
            view('play', ['error' => 'Salon mal initialisé (tracks manquants).']);
            return;
        }

        $track = $pdo->prepare('SELECT * FROM tracks WHERE id=:id');
        $track->execute([':id' => $trackId]);
        $track = $track->fetch(PDO::FETCH_ASSOC);
        if (!$track) {
            view('play', ['error' => 'Track introuvable']);
            return;
        }

        // Create room game row
        $pdo->prepare('INSERT INTO room_games(room_id,user_id,round,track_id,seconds_revealed) VALUES(:r,:u,:n,:t,5)')
            ->execute([':r' => $roomId, ':u' => $userId, ':n' => $round, ':t' => $trackId]);
        $roomGameId = (int)$pdo->lastInsertId();
        $_SESSION['room_game_id'] = $roomGameId;

        view('play', [
            'track' => $track,
            'gameId' => $roomGameId,
            'round' => $round,
            'roundsTotal' => self::ROUNDS,
            'clipSeconds' => 5,
            'maxSeconds' => 30,
            'pointsNow' => 100,
            'roomMode' => true,
        ]);
    }

    public static function reveal(): void {
        Auth::require();
        csrf_check();
        header('Content-Type: application/json');

        $pdo = Db::pdo();
        $id = (int)($_SESSION['room_game_id'] ?? 0);
        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'Aucune manche en cours']);
            return;
        }

        $stmt = $pdo->prepare('SELECT * FROM room_games WHERE id=:id AND user_id=:u');
        $stmt->execute([':id' => $id, ':u' => (int)Auth::user()['id']]);
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$g) {
            echo json_encode(['ok' => false, 'error' => 'Manche introuvable']);
            return;
        }

        $prev = (int)$g['seconds_revealed'];
        $reveals = (int)$g['reveals'] + 1;
        $seconds = min(30, $prev + 5);

        $pdo->prepare('UPDATE room_games SET reveals=:r, seconds_revealed=:s WHERE id=:id')
            ->execute([':r' => $reveals, ':s' => $seconds, ':id' => $id]);

        $pointsNow = max(0, 100 - ($reveals * 15));
        echo json_encode(['ok' => true, 'prevClipSeconds' => $prev, 'clipSeconds' => $seconds, 'pointsNow' => $pointsNow]);
    }

    public static function guess(array $post): void {
        Auth::require();
        csrf_check();

        $pdo = Db::pdo();
        $id = (int)($_SESSION['room_game_id'] ?? 0);
        if (!$id) redirect('/room/play');

        $stmt = $pdo->prepare('SELECT * FROM room_games WHERE id=:id AND user_id=:u');
        $stmt->execute([':id' => $id, ':u' => (int)Auth::user()['id']]);
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$g) redirect('/room/play');

        $trackStmt = $pdo->prepare('SELECT * FROM tracks WHERE id=:id');
        $trackStmt->execute([':id' => (int)$g['track_id']]);
        $track = $trackStmt->fetch(PDO::FETCH_ASSOC);
        if (!$track) redirect('/room/play');

        $guessTitle = trim((string)($post['title'] ?? ''));
        $guessArtist = trim((string)($post['artist'] ?? ''));

        $titleOk = Text::similarity($guessTitle, (string)$track['title']) >= 0.82;

        // Artist feat support
        $artistOk = false;
        $cands = [(string)$track['artist']];
        try {
            $dz = Deezer::track((int)$track['deezer_track_id']);
            if (!empty($dz['contributors']) && is_array($dz['contributors'])) {
                foreach ($dz['contributors'] as $c) {
                    $name = $c['name'] ?? null;
                    if ($name) $cands[] = (string)$name;
                }
            }
        } catch (\Throwable $e) {}
        $cands = array_values(array_unique(array_filter(array_map('trim', $cands))));
        foreach ($cands as $cand) {
            if (Text::similarity($guessArtist, $cand) >= 0.82) { $artistOk = true; break; }
        }

        $points = max(0, 100 - ((int)$g['reveals'] * 15));
        if ($titleOk && $artistOk) $final = $points;
        elseif ($titleOk || $artistOk) $final = (int)floor($points/2);
        else $final = 0;

        $pdo->prepare('UPDATE room_games SET guessed_title=:gt, guessed_artist=:ga, points=:p WHERE id=:id')
            ->execute([':gt' => $titleOk?1:0, ':ga' => $artistOk?1:0, ':p' => $final, ':id' => $id]);

        unset($_SESSION['room_game_id']);

        view('result', [
            'track' => $track,
            'titleOk' => $titleOk,
            'artistOk' => $artistOk,
            'final' => $final,
            'pointsBefore' => $points,
            'nextUrl' => '/room/play',
        ]);
    }

    public static function leaderboard(): void {
        Auth::require();
        $pdo = Db::pdo();
        $roomId = (int)($_SESSION['room_id'] ?? 0);
        if (!$roomId) redirect('/rooms');

        self::requireMember($pdo, $roomId, (int)Auth::user()['id']);

        $stmt = $pdo->prepare(
            "SELECT u.username,
                    COALESCE(SUM(rg.points),0) AS total_points,
                    COUNT(rg.id) AS rounds
             FROM room_players rp
             JOIN users u ON u.id=rp.user_id
             LEFT JOIN room_games rg ON rg.room_id=rp.room_id AND rg.user_id=rp.user_id
             WHERE rp.room_id=:r
             GROUP BY rp.user_id
             ORDER BY total_points DESC, rounds DESC, u.username ASC"
        );
        $stmt->execute([':r' => $roomId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $room = self::getRoom($pdo, $roomId);
        view('room_leaderboard', ['room' => $room, 'rows' => $rows]);
    }

    private static function newCode(PDO $pdo): string {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        while (true) {
            $c = '';
            for ($i=0;$i<5;$i++) $c .= $chars[random_int(0, strlen($chars)-1)];
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE code=:c');
            $stmt->execute([':c' => $c]);
            if ((int)$stmt->fetchColumn() === 0) return $c;
        }
    }

    private static function requireMember(PDO $pdo, int $roomId, int $userId): void {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM room_players WHERE room_id=:r AND user_id=:u');
        $stmt->execute([':r' => $roomId, ':u' => $userId]);
        if ((int)$stmt->fetchColumn() === 0) {
            // Kicked or not joined
            unset($_SESSION['room_id'], $_SESSION['room_game_id']);
            redirect('/rooms');
        }
    }

    private static function getRoom(PDO $pdo, int $roomId): array {
        $stmt = $pdo->prepare('SELECT * FROM rooms WHERE id=:id');
        $stmt->execute([':id' => $roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$room) {
            unset($_SESSION['room_id']);
            redirect('/rooms');
        }
        return $room;
    }
}
