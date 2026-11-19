@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;

    $lastRejected = $order->payments->first(fn ($payment) => $payment->status === PaymentStatus::Rejected);
@endphp

<x-layouts.app :title="__('orders.show.title', ['reference' => $order->reference])">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ __('orders.show.title', ['reference' => $order->reference]) }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('orders.show.placed_on', ['date' => $order->created_at->translatedFormat('d F Y à H:i')]) }}</p>
            </div>
            <x-order-status-badge :status="$order->status" />
        </header>

        @if ($order->status === OrderStatus::Cancelled)
            <p class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">{{ __('orders.show.cancelled_info') }}</p>
        @elseif ($order->payment_method === PaymentMethod::CashOnDelivery)
            <p class="mt-6 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-900">{{ __('orders.show.cod_info') }}</p>
        @elseif ($order->acceptsProof())
            <section class="mt-6 rounded-xl bg-brand-50 p-5" aria-labelledby="pay-title">
                <h2 id="pay-title" class="text-lg font-semibold text-brand-900">{{ $order->payment_method->label() }}</h2>
                <p class="mt-1 text-sm font-medium text-brand-900">{{ __('orders.show.amount', ['amount' => $order->total_ttc->format()]) }}</p>

                @if ($order->payment_method === PaymentMethod::Cheque)
                    <p class="mt-2 text-sm text-slate-700">{{ __('orders.show.cheque_payee', ['payee' => $payment->cheque_payee]) }}</p>
                @else
                    <dl class="mt-3 grid gap-1 text-sm sm:grid-cols-[8rem_1fr]">
                        <dt class="text-slate-600">{{ __('orders.show.bank') }}</dt><dd class="font-medium">{{ $payment->bank_name }}</dd>
                        <dt class="text-slate-600">{{ __('orders.show.holder') }}</dt><dd class="font-medium">{{ $payment->account_holder }}</dd>
                        <dt class="text-slate-600">{{ __('orders.show.rib') }}</dt>
                        <dd class="font-medium">{{ $payment->rib ?: __('orders.show.rib_missing') }}</dd>
                    </dl>
                @endif

                <p class="mt-3 text-sm text-slate-600">
                    {{ __('orders.show.delay', ['delay' => match ($order->payment_method) {
                        PaymentMethod::BankTransfer => $payment->bank_transfer_delay,
                        PaymentMethod::CashDeposit => $payment->cash_deposit_delay,
                        default => $payment->cheque_delay,
                    }]) }}
                </p>

                @if ($lastRejected)
                    <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ __('orders.show.proof_rejected') }} {{ $lastRejected->admin_note }}</p>
                @endif

                <form method="POST" action="{{ $proofUrl }}" enctype="multipart/form-data" class="mt-5 border-t border-brand-100 pt-5">
                    @csrf
                    <h3 class="font-semibold text-brand-900">{{ __('orders.show.proof_title') }}</h3>
                    <p class="mt-1 text-xs text-slate-600">{{ __('orders.show.proof_hint') }}</p>
                    <input type="file" name="proofs[]" multiple required accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" class="mt-3 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-1.5 file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                    @error('proofs')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('proofs.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('proof')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <button type="submit" class="mt-4 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">{{ __('orders.show.proof_button') }}</button>
                </form>
            </section>
        @elseif ($order->status === OrderStatus::PaymentSubmitted)
            <p class="mt-6 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-900">{{ __('orders.show.submitted_info') }}</p>
        @elseif (in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing], true))
            <p class="mt-6 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-900">{{ __('orders.show.paid_info') }}</p>
        @endif

        @if ($order->tracking_number)
            <section class="mt-6 rounded-xl p-5 ring-1 ring-slate-200">
                <h2 class="font-semibold text-brand-900">{{ __('orders.show.tracking') }}</h2>
                <p class="mt-1 text-sm">{{ __('orders.show.tracking_number') }} : <strong>{{ $order->tracking_number }}</strong>@if ($order->tracking_carrier) ({{ $order->tracking_carrier }})@endif</p>
            </section>
        @endif

        <section class="mt-8" aria-labelledby="items-title">
            <h2 id="items-title" class="text-xl font-semibold text-brand-900">{{ __('orders.show.items') }}</h2>
            <div class="mt-3 overflow-x-auto rounded-xl ring-1 ring-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('orders.cart.product') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('orders.show.quantity') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('orders.show.unit_ht') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('orders.show.total_ttc') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3"><span class="font-medium text-slate-900">{{ $item->name }}</span><span class="block text-xs text-slate-500">{{ __('orders.show.reference') }} {{ $item->reference }}</span></td>
                                <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ $item->unit_price_ht }}</td>
                                <td class="px-4 py-3 text-right font-medium">{{ $item->line_ttc }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <dl class="ml-auto mt-4 max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-600">{{ __('orders.show.subtotal_ht') }}</dt><dd>{{ $order->subtotal_ht }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-600">{{ __('orders.show.shipping_ht') }}</dt><dd>{{ $order->shipping_ht }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-600">{{ __('orders.show.vat') }}</dt><dd>{{ $order->vat_total }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-brand-900"><dt>{{ __('orders.show.grand_total') }}</dt><dd>{{ $order->total_ttc }}</dd></div>
            </dl>
        </section>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            <section class="rounded-xl p-5 ring-1 ring-slate-200">
                <h2 class="font-semibold text-brand-900">{{ __('orders.show.delivery_to') }}</h2>
                <address class="mt-2 text-sm not-italic text-slate-700">
                    {{ $order->contact_name }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_postal_code }} {{ $order->shipping_city }}<br>
                    {{ $order->contact_phone }}
                </address>
                <p class="mt-2 text-xs text-slate-500">
                    {{ $order->carrier_mode->label() }}@if ($order->own_carrier_note) : {{ $order->own_carrier_note }}@endif
                </p>
                @if ($order->carrier_mode === \App\Enums\CarrierMode::Mitek)
                    <p class="mt-1 text-xs text-slate-500">{{ __('orders.show.dispatch', ['days' => app(\App\Settings\ShippingSettings::class)->dispatch_days]) }}</p>
                @endif
            </section>

            <section class="rounded-xl p-5 ring-1 ring-slate-200">
                <h2 class="font-semibold text-brand-900">{{ __('orders.show.billing') }}</h2>
                <div class="mt-2 text-sm text-slate-700">
                    @if ($order->company_name)
                        <p class="font-medium">{{ $order->company_name }}</p>
                        @if ($order->ice)<p>ICE : {{ $order->ice }}</p>@endif
                        @if ($order->if_number)<p>IF : {{ $order->if_number }}</p>@endif
                        @if ($order->rc_number)<p>RC : {{ $order->rc_number }}</p>@endif
                    @else
                        <p>{{ $order->contact_name }}</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-500">{{ __('orders.show.payment') }} : {{ $order->payment_method->label() }}</p>
                </div>
            </section>
        </div>

        <section class="mt-8" aria-labelledby="history-title">
            <h2 id="history-title" class="text-xl font-semibold text-brand-900">{{ __('orders.show.history') }}</h2>
            <ol class="mt-3 space-y-2 border-l-2 border-slate-200 pl-4 text-sm">
                @foreach ($order->history as $entry)
                    <li>
                        <span class="font-medium text-slate-900">{{ $entry->to_status->label() }}</span>
                        <span class="text-xs text-slate-500">· {{ $entry->created_at->translatedFormat('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ol>
        </section>

        <p class="mt-8 text-sm text-slate-500">{{ __('orders.show.contact', ['email' => $general->email]) }}</p>
    </div>
</x-layouts.app>
