<x-account-layout :title="__('account.nav.quotes')" active="quotes">
    @if ($quotes->isEmpty())
        <p class="rounded-xl bg-slate-50 p-8 text-center text-slate-600">{{ __('account.quotes.empty') }}</p>
    @else
        <ul class="space-y-2">
            @foreach ($quotes as $quote)
                <li>
                    <a href="{{ route('quotes.show', $quote) }}" class="flex items-center justify-between gap-4 rounded-xl p-4 ring-1 ring-slate-200 hover:bg-slate-50">
                        <span><span class="font-medium text-slate-900">{{ $quote->reference }}</span><span class="block text-xs text-slate-500">{{ $quote->created_at->translatedFormat('d/m/Y') }} · {{ $quote->items_count }} {{ __('account.quotes.items') }}</span></span>
                        <span class="text-sm text-slate-600">{{ $quote->status->label() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $quotes->links() }}</div>
    @endif
</x-account-layout>
