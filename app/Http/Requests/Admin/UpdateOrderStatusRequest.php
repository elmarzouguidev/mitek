<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    /**
     * Payment related statuses are reached by reviewing a proof, never by hand.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)->only([
                OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_carrier' => ['nullable', 'string', 'max:100'],
        ];
    }
}
