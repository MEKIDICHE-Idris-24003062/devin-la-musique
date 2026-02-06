<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth {
    public static function check(): bool { return isset($_SESSION['user']); }

    /** @return array{id:int,username:string,is_admin:int} */
    public static function user(): array {
        if (!self::check()) throw new \RuntimeException('Not logged in');
        return $_SESSION['user'];
    }

    public static function require(): void {
        if (!self::check()) redirect('/login');
    }

    public static function requireAdmin(): void {
        self::require();
        if (!(int)self::user()['is_admin']) {
            http_response_code(403);
            echo 'Admin only';
            exit;
        }
    }

    public static function register(array $post): void {
        $username = trim((string)($post['username'] ?? ''));
        $password = (string)($post['password'] ?? '');
        if ($username === '' || strlen($password) < 6) {
            view('register', ['error' => "Pseudo requis + mot de passe >= 6 chars"]);
            return;
        }

        $pdo = Db::pdo();

        // First user becomes admin
        $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $isAdmin = $count === 0 ? 1 : 0;

        $stmt = $pdo->prepare('INSERT INTO users(username, password_hash, is_admin) VALUES(:u,:p,:a)');
        try {
            $stmt->execute([
                ':u' => $username,
                ':p' => password_hash($password, PASSWORD_DEFAULT),
                ':a' => $isAdmin,
            ]);
        } catch (\Throwable $e) {
            view('register', ['error' => 'Pseudo déjà utilisé ?']);
            return;
        }

        redirect('/login');
    }

    public static function login(array $post): void {
        $username = trim((string)($post['username'] ?? ''));
        $password = (string)($post['password'] ?? '');

        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, is_admin FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($password, (string)$row['password_hash'])) {
            view('login', ['error' => 'Identifiants invalides']);
            return;
        }

        $_SESSION['user'] = [
            'id' => (int)$row['id'],
            'username' => (string)$row['username'],
            'is_admin' => (int)$row['is_admin'],
        ];

        redirect('/play');
    }

    public static function logout(): void {
        $_SESSION = [];
        session_destroy();
        redirect('/');
    }
}
