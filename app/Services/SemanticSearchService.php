<?php

namespace App\Services;

/** A deterministic embedding fallback; replace with a managed vector provider in production. */
class SemanticSearchService
{
    private const DIMENSIONS = 128;

    public function embed(string $text): array
    {
        $vector = array_fill(0, self::DIMENSIONS, 0.0);
        preg_match_all('/[a-z0-9]{2,}/i', strtolower($text), $matches);
        foreach ($matches[0] as $token) {
            $index = (int) sprintf('%u', crc32($token)) % self::DIMENSIONS;
            $vector[$index] += 1;
        }
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));

        return $norm ? array_map(fn ($v) => round($v / $norm, 6), $vector) : $vector;
    }

    public function similarity(array $left, array $right): float
    {
        if (count($left) !== self::DIMENSIONS || count($right) !== self::DIMENSIONS) {
            return 0;
        }

        return array_sum(array_map(fn ($a, $b) => $a * $b, $left, $right));
    }
}
