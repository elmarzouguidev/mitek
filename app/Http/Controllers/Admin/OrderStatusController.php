<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\IllegalStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Core\Order\Order;
use App\Services\Core\Order\TransitionOrderStatus;
use Illuminate\Http\RedirectResponse;

class OrderStatusController extends Controller
{
    public function update(UpdateOrderStatusRequest $request, Order $order, TransitionOrderStatus $transition): RedirectResponse
    {
        try {
            $transition->handle(
                $order,
                OrderStatus::from($request->validated('status')),
                $request->user(),
                $request->validated('note'),
                $request->safe()->only(['tracking_number', 'tracking_carrier']),
            );
        } catch (IllegalStatusTransition $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('admin.orders.status_updated'));
    }
}
