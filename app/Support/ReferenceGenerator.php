<?php

namespace App\Support;

use Closure;

/**
 * Human-friendly unique references such as SR-7K3M9P (no ambiguous characters).
 */
final class ReferenceGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * @param  Closure(string): bool  $exists  tells whether a candidate is already taken
     */
    public static function make(string $prefix, Closure $exists, int $length = 6): string
    {
        do {
            $reference = $prefix.'-';

            for ($index = 0; $index < $length; $index++) {
                $reference .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while ($exists($reference));

        return $reference;
    }
}
