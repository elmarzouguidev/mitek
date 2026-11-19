<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Small helpers shared by the storefront views for meta tags.
 */
final class Seo
{
    public const DESCRIPTION_LIMIT = 155;

    /**
     * A plain-text meta description generated from content (HTML or Markdown), cut at a word boundary.
     */
    public static function description(?string $text, int $limit = self::DESCRIPTION_LIMIT): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $plain = strip_tags(Str::markdown($text, ['html_input' => 'strip']));

        return self::limit(html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $limit);
    }

    /**
     * Squishes and cuts plain text at a word boundary (for descriptions typed by hand).
     */
    public static function limit(string $text, int $limit = self::DESCRIPTION_LIMIT): string
    {
        $plain = Str::squish($text);

        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }

        $cut = mb_substr($plain, 0, $limit - 1);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace === false ? $cut : mb_substr($cut, 0, $lastSpace), ' ,;:.-').'…';
    }

    /**
     * JSON for an inline `<script type="application/ld+json">`: tags and ampersands are escaped so the
     * data can never close the script element.
     *
     * @param  array<string, mixed>  $data
     */
    public static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_PRESERVE_ZERO_FRACTION);
    }
}
