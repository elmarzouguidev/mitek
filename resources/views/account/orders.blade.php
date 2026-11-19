<x-account-layout :title="__('account.nav.orders')" active="orders">
    @if ($orders->isEmpty())
        <p class="rounded-xl bg-slate-50 p-8 text-center text-slate-600">{{ __('account.orders.empty') }}</p>
    @else
        <div class="overflow-x-auto rounded-xl ring-1 ring-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('account.orders.reference') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('account.orders.date') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('account.orders.status') }}</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('account.orders.total') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $order->reference }}</td>
                            <td class="px-4 py-3">{{ $order->created_at->translatedFormat('d/m/Y') }}</td>
                            <td class="px-4 py-3"><x-order-status-badge :status="$order->status" class="!px-2 !py-0.5 !text-xs" /></td>
                            <td class="px-4 py-3 text-right font-semibold">{{ $order->total_ttc }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('orders.show', $order) }}" class="font-medium text-brand-700 hover:underline">{{ __('account.orders.view') }}</a>
                                @if ($order->invoice)
                                    <a href="{{ route('orders.invoice', $order) }}" target="_blank" rel="noopener" class="ml-3 font-medium text-brand-700 hover:underline">{{ __('account.orders.invoice') }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-account-layout>
