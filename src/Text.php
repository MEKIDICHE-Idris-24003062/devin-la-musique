<?php

declare(strict_types=1);

namespace App;

final class Text {
    public static function normalize(string $s): string {
        $s = trim(mb_strtolower($s));
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9 ]+/i', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return trim($s);
    }

    /** returns 0..1 */
    public static function similarity(string $a, string $b): float {
        $a = self::normalize($a);
        $b = self::normalize($b);
        if ($a === '' || $b === '') return 0.0;
        if ($a === $b) return 1.0;

        $dist = levenshtein($a, $b);
        $max = max(strlen($a), strlen($b));
        if ($max === 0) return 0.0;
        $score = 1.0 - ($dist / $max);

        // Bonus if one contains the other
        if (str_contains($a, $b) || str_contains($b, $a)) {
            $score = max($score, 0.9);
        }
        return max(0.0, min(1.0, $score));
    }
}
