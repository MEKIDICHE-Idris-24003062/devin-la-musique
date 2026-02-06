<?php

declare(strict_types=1);

namespace App;

final class UserSettings {
    public static function get(int $userId, string $key, ?string $default = null): ?string {
        $pdo = Db::pdo();
        $stmt = $pdo->prepare('SELECT value FROM user_settings WHERE user_id=:u AND key=:k');
        $stmt->execute([':u' => $userId, ':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val === false ? $default : (string)$val;
    }

    public static function set(int $userId, string $key, ?string $value): void {
        $pdo = Db::pdo();
        if ($value === null) {
            $pdo->prepare('DELETE FROM user_settings WHERE user_id=:u AND key=:k')->execute([':u' => $userId, ':k' => $key]);
            return;
        }
        $pdo->prepare('INSERT INTO user_settings(user_id,key,value) VALUES(:u,:k,:v) ON CONFLICT(user_id,key) DO UPDATE SET value=excluded.value')
            ->execute([':u' => $userId, ':k' => $key, ':v' => $value]);
    }
}
