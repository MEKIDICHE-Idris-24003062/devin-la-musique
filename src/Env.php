<?php

declare(strict_types=1);

namespace App;

final class Env {
    public static function load(string $path): void {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            $val = trim($val, "\"'");
            $_ENV[$key] = $val;
        }
    }

    public static function get(string $key, ?string $default = null): ?string {
        return $_ENV[$key] ?? $default;
    }
}
