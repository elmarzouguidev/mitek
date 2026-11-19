<x-account-layout :title="__('account.title')" active="overview">
    <h2 class="text-xl font-semibold text-brand-900">{{ __('account.overview.welcome', ['name' => auth()->user()->name]) }}</h2>

    <section class="mt-6">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">{{ __('account.overview.recent_orders') }}</h3>
            <a href="{{ route('account.orders') }}" class="text-sm text-brand-700 hover:underline">{{ __('account.overview.see_all') }}</a>
        </div>
        @forelse ($orders as $order)
            <a href="{{ route('orders.show', $order) }}" class="mt-2 flex items-center justify-between gap-4 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-slate-50">
                <span><span class="font-medium text-slate-900">{{ $order->reference }}</span><span class="block text-xs text-slate-500">{{ $order->created_at->translatedFormat('d/m/Y') }}</span></span>
                <span class="flex items-center gap-3"><x-order-status-badge :status="$order->status" class="!px-2 !py-0.5 !text-xs" /><span class="text-sm font-semibold">{{ $order->total_ttc }}</span></span>
            </a>
        @empty
            <p class="mt-2 text-sm text-slate-500">{{ __('account.overview.none_orders') }}</p>
        @endforelse
    </section>

    <section class="mt-8">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">{{ __('account.overview.recent_quotes') }}</h3>
            <a href="{{ route('account.quotes') }}" class="text-sm text-brand-700 hover:underline">{{ __('account.overview.see_all') }}</a>
        </div>
        @forelse ($quotes as $quote)
            <a href="{{ route('quotes.show', $quote) }}" class="mt-2 flex items-center justify-between gap-4 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-slate-50">
                <span class="font-medium text-slate-900">{{ $quote->reference }}</span>
                <span class="text-sm text-slate-600">{{ $quote->status->label() }}</span>
            </a>
        @empty
            <p class="mt-2 text-sm text-slate-500">{{ __('account.overview.none_quotes') }}</p>
        @endforelse
    </section>
</x-account-layout>
