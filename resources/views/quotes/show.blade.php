<x-layouts.app :title="__('quotes.show.title', ['reference' => $quote->reference])">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ __('quotes.show.title', ['reference' => $quote->reference]) }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $quote->created_at->translatedFormat('d F Y à H:i') }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-700 ring-1 ring-inset ring-brand-200">{{ $quote->status->label() }}</span>
        </header>

        @if ($quote->admin_reply)
            <section class="mt-6 rounded-xl bg-brand-50 p-5">
                <h2 class="font-semibold text-brand-900">{{ __('quotes.show.reply') }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-800">{{ $quote->admin_reply }}</p>
                <p class="mt-3 text-xs text-slate-500">{{ __('quotes.show.replied_on', ['date' => $quote->replied_at->translatedFormat('d/m/Y')]) }}</p>
            </section>
            <p class="mt-3 text-sm text-slate-600">{{ __('quotes.show.order') }}</p>
        @else
            <p class="mt-6 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">{{ __('quotes.show.waiting') }}</p>
        @endif

        <section class="mt-8">
            <h2 class="text-xl font-semibold text-brand-900">{{ __('quotes.show.items') }}</h2>
            <ul class="mt-3 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                @foreach ($quote->items as $item)
                    <li class="flex justify-between gap-4 px-4 py-3 text-sm">
                        <span><span class="font-medium text-slate-900">{{ $item->name }}</span> <span class="text-slate-500">· {{ __('catalog.reference') }} {{ $item->reference }}</span></span>
                        <span>{{ __('quotes.show.quantity') }} : {{ $item->quantity }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($quote->message)
            <section class="mt-6">
                <h2 class="font-semibold text-brand-900">{{ __('quotes.show.message') }}</h2>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $quote->message }}</p>
            </section>
        @endif
    </div>
</x-layouts.app>
