<?php

namespace App\Services\Core\Invoice;

use App\Models\Core\Invoice\CreditNote;
use App\Models\Core\Order\OrderItem;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Renders a credit note to PDF (negative amounts, VAT breakdown) and caches it on the private disk.
 */
class CreditNotePdf
{
    public function __construct(private readonly PdfRenderer $renderer) {}

    public function contents(CreditNote $creditNote): string
    {
        $disk = Storage::disk(CreditNote::DISK);

        if ($creditNote->pdf_path !== null && $disk->exists($creditNote->pdf_path)) {
            return $disk->get($creditNote->pdf_path);
        }

        $bytes = $this->renderer->render($this->html($creditNote), $creditNote->number, (string) ($creditNote->seller['site'] ?? 'Mitek.ma'));
        $path = sprintf('credit-notes/%d/%s.pdf', $creditNote->issued_at->year, $creditNote->number);

        $disk->put($path, $bytes);
        $creditNote->forceFill(['pdf_path' => $path])->save();

        return $bytes;
    }

    public function filename(CreditNote $creditNote): string
    {
        return $creditNote->number.'.pdf';
    }

    public function html(CreditNote $creditNote): string
    {
        $order = $creditNote->order()->with('items')->firstOrFail();
        $invoice = $creditNote->invoice()->firstOrFail();
        $negative = fn (Money $money): Money => Money::fromCents(-$money->cents);

        $vatGroups = $order->items
            ->groupBy('vat_rate')
            ->map(fn ($items, $rate): array => [
                'rate' => (int) $rate,
                'base' => Money::fromCents(-$items->sum(fn (OrderItem $item): int => $item->line_ht->cents)),
                'vat' => Money::fromCents(-$items->sum(fn (OrderItem $item): int => $item->line_vat->cents)),
            ])
            ->sortKeys()
            ->values();

        $itemsVat = $order->items->sum(fn (OrderItem $item): int => $item->line_vat->cents);

        return View::make('credit-notes.pdf', [
            'creditNote' => $creditNote,
            'invoice' => $invoice,
            'order' => $order,
            'seller' => $creditNote->seller,
            'vatGroups' => $vatGroups,
            'shippingVat' => Money::fromCents(-($order->vat_total->cents - $itemsVat)),
            'shippingVatRate' => $creditNote->seller['vat_rate'] ?? null,
            'negative' => $negative,
            'logo' => public_path('images/logo-mitek.png'),
        ])->render();
    }
}
