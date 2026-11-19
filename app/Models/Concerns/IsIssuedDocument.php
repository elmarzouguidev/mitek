<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Fiscal documents (invoices, credit notes) are never edited or deleted once issued. Only the cached
 * PDF location may be written.
 */
trait IsIssuedDocument
{
    public static function bootIsIssuedDocument(): void
    {
        static::updating(function (self $document): void {
            $changed = array_diff(array_keys($document->getDirty()), ['pdf_path', 'updated_at']);

            if ($changed !== []) {
                throw new LogicException(static::class.' is immutable once issued (tried to change: '.implode(', ', $changed).').');
            }
        });

        static::deleting(function (self $document): void {
            throw new LogicException(static::class.' cannot be deleted once issued.');
        });
    }
}
