<?php

namespace App\Http\Resources\Admin;

use App\Models\Core\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'customer' => $this->company_name ?: $this->contact_name,
            'email' => $this->contact_email,
            'status' => ['value' => $this->status->value, 'label' => $this->status->label(), 'tone' => $this->status->tone()],
            'payment_method' => $this->payment_method->label(),
            'total_ttc' => $this->total_ttc->format(),
            'created_at' => $this->created_at->translatedFormat('d/m/Y H:i'),
        ];
    }
}
