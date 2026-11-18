<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    public const SCALE = 2;

    /**
     * Cast the given value (from database to model).
     * Converts minor units to a major-unit amount with two decimal places.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        return round(((int) $value) / (10 ** self::SCALE), self::SCALE, PHP_ROUND_HALF_UP);
    }

    /**
     * Get the raw minor-unit value for external integrations.
     */
    public function getCentsValue(Model $model, string $key): ?int
    {
        $value = $model->getRawOriginal($key);

        return $value === null ? null : (int) $value;
    }

    /**
     * Prepare the given value for storage (from model to database).
     * Converts a major-unit amount to deterministic minor units.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::toMinorUnits($value);
    }

    private static function toMinorUnits(mixed $value): int
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new InvalidArgumentException('Money values must be finite.');
            }

            $value = sprintf('%.14F', $value);
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Money values must be numeric and non-negative.');
        }

        $value = trim($value);

        if (! preg_match('/^\+?(\d+)(?:\.(\d+))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Money values must be numeric and non-negative.');
        }

        $whole = ltrim($matches[1], '0') ?: '0';
        $fraction = $matches[2] ?? '';
        $maximumWhole = (string) intdiv(PHP_INT_MAX, 10 ** self::SCALE);

        if (strlen($whole) > strlen($maximumWhole)
            || (strlen($whole) === strlen($maximumWhole) && strcmp($whole, $maximumWhole) > 0)) {
            throw new InvalidArgumentException('Money value is outside the supported range.');
        }

        $fractionMinor = (int) str_pad(
            substr($fraction, 0, self::SCALE),
            self::SCALE,
            '0'
        );

        if (isset($fraction[self::SCALE]) && (int) $fraction[self::SCALE] >= 5) {
            $fractionMinor++;
        }

        if ($whole === $maximumWhole && $fractionMinor > PHP_INT_MAX % (10 ** self::SCALE)) {
            throw new InvalidArgumentException('Money value is outside the supported range.');
        }

        return ((int) $whole * (10 ** self::SCALE)) + $fractionMinor;
    }
}
