<?php

declare(strict_types=1);

namespace App;

final class Settings {
    public static function get(string $key, ?string $default = null): ?string {
        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE key=:k');
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val === false ? $default : (string)$val;
    }

    public static function set(string $key, ?string $value): void {
        $pdo = Db::pdo();
        if ($value === null) {
            $pdo->prepare('DELETE FROM settings WHERE key=:k')->execute([':k' => $key]);
            return;
        }
        $pdo->prepare('INSERT INTO settings(key,value) VALUES(:k,:v) ON CONFLICT(key) DO UPDATE SET value=excluded.value')
            ->execute([':k' => $key, ':v' => $value]);
    }
}
