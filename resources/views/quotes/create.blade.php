@php
    $input = 'mt-1 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-brand-600';
@endphp

<x-layouts.app :title="__('quotes.create.title')">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold tracking-tight text-brand-900">{{ __('quotes.create.title') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('quotes.create.intro') }}</p>

        @foreach ($summary->adjustments as $message)
            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200" role="status">{{ $message }}</p>
        @endforeach

        <section class="mt-6" aria-labelledby="items-title">
            <h2 id="items-title" class="text-xl font-semibold text-brand-900">{{ __('quotes.create.items') }}</h2>
            <ul class="mt-3 divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                @foreach ($summary->lines as $line)
                    <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <span><span class="font-medium text-slate-900">{{ $line->product->name }}</span> <span class="text-slate-500">· {{ $line->quantity }} ×</span></span>
                        <span class="text-slate-600">{{ __('quotes.create.indicative') }} : {{ $line->quote->unitHt }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <form method="POST" action="{{ route('quotes.store') }}" class="mt-8 space-y-4">
            @csrf
            <x-honeypot />
            <h2 class="text-xl font-semibold text-brand-900">{{ __('quotes.create.contact') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_name" class="block text-sm font-medium text-slate-700">{{ __('quotes.create.name') }}</label>
                    <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name', $prefill['contact_name']) }}" required class="{{ $input }}">
                    @error('contact_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-slate-700">{{ __('quotes.create.email') }}</label>
                    <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $prefill['contact_email']) }}" required class="{{ $input }}">
                    @error('contact_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact_phone" class="block text-sm font-medium text-slate-700">{{ __('quotes.create.phone') }}</label>
                    <input id="contact_phone" name="contact_phone" type="tel" value="{{ old('contact_phone', $prefill['contact_phone']) }}" required class="{{ $input }}">
                    @error('contact_phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="company_name" class="block text-sm font-medium text-slate-700">{{ __('quotes.create.company') }}</label>
                    <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $prefill['company_name']) }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="ice" class="block text-sm font-medium text-slate-700">{{ __('quotes.create.ice') }}</label>
                    <input id="ice" name="ice" type="text" inputmode="numeric" maxlength="15" value="{{ old('ice', $prefill['ice']) }}" class="{{ $input }}">
                    @error('ice')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="message" class="block text-sm font-medium text-slate-700">{{ __('quotes.create.message') }}</label>
                <textarea id="message" name="message" rows="4" class="{{ $input }}">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-800">{{ __('quotes.create.submit') }}</button>
        </form>
    </div>
</x-layouts.app>
