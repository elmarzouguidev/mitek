<?php

namespace App\Services\Core\Invoice;

use App\Models\Core\Invoice\Invoice;
use App\Models\Core\Order\OrderItem;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Renders an invoice to PDF with mPDF and caches the file on the private disk.
 */
class InvoicePdf
{
    public function __construct(private readonly PdfRenderer $renderer) {}

    /**
     * PDF bytes; rendered on first use, then served from the cache.
     */
    public function contents(Invoice $invoice): string
    {
        $disk = Storage::disk(Invoice::DISK);

        if ($invoice->pdf_path !== null && $disk->exists($invoice->pdf_path)) {
            return $disk->get($invoice->pdf_path);
        }

        $bytes = $this->render($invoice);
        $path = sprintf('invoices/%d/%s.pdf', $invoice->issued_at->year, $invoice->number);

        $disk->put($path, $bytes);
        $invoice->forceFill(['pdf_path' => $path])->save();

        return $bytes;
    }

    public function filename(Invoice $invoice): string
    {
        return $invoice->number.'.pdf';
    }

    /**
     * The HTML that is turned into the PDF.
     */
    public function html(Invoice $invoice): string
    {
        return View::make('invoices.pdf', $this->data($invoice))->render();
    }

    private function render(Invoice $invoice): string
    {
        return $this->renderer->render($this->html($invoice), $invoice->number, (string) ($invoice->seller['site'] ?? 'Mitek.ma'));
    }

    /**
     * @return array<string, mixed>
     */
    private function data(Invoice $invoice): array
    {
        $order = $invoice->order()->with('items')->firstOrFail();

        $vatGroups = $order->items
            ->groupBy('vat_rate')
            ->map(fn ($items, $rate): array => [
                'rate' => (int) $rate,
                'base' => Money::fromCents($items->sum(fn (OrderItem $item): int => $item->line_ht->cents)),
                'vat' => Money::fromCents($items->sum(fn (OrderItem $item): int => $item->line_vat->cents)),
            ])
            ->sortKeys()
            ->values();

        $itemsVat = $vatGroups->sum(fn (array $group): int => $group['vat']->cents);

        return [
            'invoice' => $invoice,
            'order' => $order,
            'seller' => $invoice->seller,
            'vatGroups' => $vatGroups,
            'shippingVat' => Money::fromCents($order->vat_total->cents - $itemsVat),
            'shippingVatRate' => $invoice->seller['vat_rate'] ?? null,
            'logo' => public_path('images/logo-mitek.png'),
        ];
    }
}
