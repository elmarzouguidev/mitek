<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable amount of money stored as integer centimes (1 DH = 100 centimes).
 */
final readonly class Money implements JsonSerializable, Stringable
{
    public const SCALE = 100;

    public const CURRENCY = 'MAD';

    private function __construct(public int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Build an amount from a decimal string or number expressed in dirhams, e.g. "1 250,50" or 1250.5.
     */
    public static function fromDecimal(string|int|float $amount): self
    {
        $normalized = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], (string) $amount);

        if (! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        return new self((int) round(((float) $normalized) * self::SCALE));
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function times(int $quantity): self
    {
        return new self($this->cents * $quantity);
    }

    /**
     * Apply a percentage (e.g. 20 for 20 %), rounded half up to the nearest centime.
     */
    public function percentage(int|float $percent): self
    {
        return new self((int) round($this->cents * $percent / 100, 0, PHP_ROUND_HALF_UP));
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isLessThanOrEqualTo(self $other): bool
    {
        return $this->cents <= $other->cents;
    }

    public function toDecimal(): float
    {
        return $this->cents / self::SCALE;
    }

    /**
     * French display format with non-breaking spaces, e.g. "1 250,50 DH".
     */
    public function format(string $thousandsSeparator = "\u{202F}"): string
    {
        return number_format($this->toDecimal(), 2, ',', $thousandsSeparator)."\u{00A0}DH";
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @return array{cents: int, decimal: float, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'cents' => $this->cents,
            'decimal' => $this->toDecimal(),
            'formatted' => $this->format(),
        ];
    }
}
