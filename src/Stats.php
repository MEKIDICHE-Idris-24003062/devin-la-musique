<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Stats {
    public static function me(): void {
        $pdo = Db::pdo();
        $userId = (int)Auth::user()['id'];

        $tot = $pdo->prepare('SELECT COALESCE(SUM(points),0) FROM games WHERE user_id=:u');
        $tot->execute([':u' => $userId]);
        $totalPoints = (int)$tot->fetchColumn();

        $cnt = $pdo->prepare('SELECT COUNT(*) FROM games WHERE user_id=:u');
        $cnt->execute([':u' => $userId]);
        $games = (int)$cnt->fetchColumn();

        $best = $pdo->prepare('SELECT COALESCE(MAX(points),0) FROM games WHERE user_id=:u');
        $best->execute([':u' => $userId]);
        $bestScore = (int)$best->fetchColumn();

        $recent = $pdo->prepare('SELECT g.points, g.created_at, t.artist, t.title FROM games g JOIN tracks t ON t.id=g.track_id WHERE g.user_id=:u ORDER BY g.id DESC LIMIT 20');
        $recent->execute([':u' => $userId]);
        $recentRows = $recent->fetchAll(PDO::FETCH_ASSOC);

        view('me', [
            'totalPoints' => $totalPoints,
            'games' => $games,
            'bestScore' => $bestScore,
            'recent' => $recentRows,
        ]);
    }

    public static function leaderboard(): void {
        $pdo = Db::pdo();

        $rows = $pdo->query(
            "SELECT u.username,
                    COALESCE(SUM(g.points),0) AS total_points,
                    COUNT(g.id) AS games,
                    COALESCE(MAX(g.points),0) AS best
             FROM users u
             LEFT JOIN games g ON g.user_id = u.id
             GROUP BY u.id
             ORDER BY total_points DESC, best DESC, games DESC, u.username ASC
             LIMIT 50"
        )->fetchAll(PDO::FETCH_ASSOC);

        view('leaderboard', ['rows' => $rows]);
    }
}
