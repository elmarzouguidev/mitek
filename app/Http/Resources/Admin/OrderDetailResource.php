<?php

namespace App\Http\Resources\Admin;

use App\Enums\OrderStatus;
use App\Models\Core\Order\Order;
use App\Models\Core\Order\OrderItem;
use App\Models\Core\Order\OrderPayment;
use App\Models\Core\Order\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Order
 */
class OrderDetailResource extends JsonResource
{
    /** Statuses an administrator sets by hand; the payment ones come from reviewing a proof. */
    private const MANUAL_STATUSES = [OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label(), 'tone' => $this->status->tone()],
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'created_at' => $this->created_at->translatedFormat('d/m/Y H:i'),
            'contact' => ['name' => $this->contact_name, 'email' => $this->contact_email, 'phone' => $this->contact_phone],
            'company' => $this->company_name ? [
                'name' => $this->company_name, 'ice' => $this->ice, 'if' => $this->if_number, 'rc' => $this->rc_number,
            ] : null,
            'shipping' => [
                'city' => $this->shipping_city,
                'address' => $this->shipping_address,
                'postal_code' => $this->shipping_postal_code,
                'carrier_mode' => $this->carrier_mode->value,
                'carrier_mode_label' => $this->carrier_mode->label(),
                'own_carrier_note' => $this->own_carrier_note,
                'zone' => $this->shipping_zone_name,
                'weight_grams' => $this->total_weight_grams,
                'tracking_number' => $this->tracking_number,
                'tracking_carrier' => $this->tracking_carrier,
            ],
            'customer_note' => $this->customer_note,
            'totals' => [
                'subtotal_ht' => $this->subtotal_ht->format(),
                'shipping_ht' => $this->shipping_ht->format(),
                'vat_total' => $this->vat_total->format(),
                'total_ttc' => $this->total_ttc->format(),
            ],
            'items' => $this->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'reference' => $item->reference,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price_ht' => $item->unit_price_ht->format(),
                'line_ttc' => $item->line_ttc->format(),
            ])->values(),
            'payments' => $this->payments->map(fn (OrderPayment $payment): array => [
                'id' => $payment->id,
                'method' => $payment->method->label(),
                'amount' => $payment->amount->format(),
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'admin_note' => $payment->admin_note,
                'reviewed_at' => $payment->reviewed_at?->translatedFormat('d/m/Y H:i'),
                'created_at' => $payment->created_at->translatedFormat('d/m/Y H:i'),
                'proofs' => $payment->getMedia(OrderPayment::PROOF)->map(fn (Media $media): array => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'is_image' => str_starts_with($media->mime_type, 'image/'),
                    'url' => route('admin.orders.proofs.show', [$payment, $media->id]),
                ])->values(),
            ])->values(),
            'history' => $this->history->map(fn (OrderStatusHistory $entry): array => [
                'id' => $entry->id,
                'from' => $entry->from_status?->label(),
                'to' => $entry->to_status->label(),
                'by' => $entry->user?->name,
                'note' => $entry->note,
                'created_at' => $entry->created_at->translatedFormat('d/m/Y H:i'),
            ])->values(),
            'next_statuses' => collect($this->allowedNextStatuses())
                ->filter(fn (OrderStatus $status): bool => in_array($status, self::MANUAL_STATUSES, true))
                ->map(fn (OrderStatus $status): array => ['value' => $status->value, 'label' => $status->label()])
                ->values(),
            'accepts_proof' => $this->acceptsProof(),
            'credit_note' => $this->creditNote ? ['number' => $this->creditNote->number, 'total' => $this->creditNote->total_ttc->format(), 'url' => route('admin.orders.credit-note', $this->resource)] : null,
            'invoice' => $this->invoice ? ['number' => $this->invoice->number, 'url' => route('admin.orders.invoice', $this->resource)] : null,
        ];
    }
}
